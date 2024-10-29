<?php

final class WC_Gateway_APPpago extends WC_Payment_Gateway_CC
{

    protected $APIXPay;
    protected $module_version;
    protected $oConfig;
    protected $amount_handler;
    public static $lastId = 0;
    public static $lastColumn = null;
    public static $alreadyEnqueuedNotice = false;

    const GATEWAY_ID = 'apppago';

    public function __construct()
    {
        require_once "class-wc-gateway-apppago-api.php";
        require_once "class-wc-gateway-apppago-amount.php";
        require_once "class-wc-gateway-apppago-amount-models.php";
        require_once "constant_apppago.php";

        $this->id = static::GATEWAY_ID;
        $this->method_title = __('APPpago', 'apppago');
        $this->method_description = __('Allow the customer to pay by installments.', 'apppago');

        $this->module_version = '1.0.6';

        $this->has_fields = true;
        $this->icon = plugins_url('assets/images/apppago.png', plugin_dir_path(__FILE__));

        //what plugin supports
        $this->supports = array('products');

        //migration of old fields values into new first range of price fields
        $options = get_option('woocommerce_apppago_settings');

        //Set Config Form
        parent::init_settings();
        $this->oConfig = new WC_Gateway_APPpago_Configuration($this->settings);
        $this->form_fields = $this->oConfig->get_form_fields();

        $this->amount_handler = new AmountFactory();

        $this->title = __('APPpago', 'apppago');

        //Set Description on payment page
        $this->description = __('You can pay in installments of your amount', 'apppago');
        $this->instructions = $this->description;

        //Add JS script in Front and BO
        add_action('wp_enqueue_scripts', array($this, 'add_checkout_script'));
        add_action('admin_enqueue_scripts', array($this, 'add_admin_script'));

        //Custom Field
        add_action('woocommerce_before_add_to_cart_button', array($this, 'apppago_display_custom_field'));
        add_action('woocommerce_before_shop_loop_item_title', array($this, 'apppago_display_custom_badge'), 10, 0);
        //End Custom Field
        add_action('woocommerce_api_wc_gateway_' . $this->id, array($this, 'wc_apppago_payment_return'));
        add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'apppago_checkConfigs'));
        //Box installments in my account order page and thank you page
        add_action('woocommerce_view_order', array($this, 'wc_apppago_myorder'), 20);
        add_action('woocommerce_thankyou', array($this, 'wc_apppago_show_order_and_empty_cart'), 20);
        //Widget
        add_action('wp_dashboard_setup', array($this, 'apppago_dashboard_widgets'));
        //Add extra Column in order page
        add_filter('manage_edit-shop_order_columns', array($this, 'apppago_order_column'));
        add_action('manage_shop_order_posts_custom_column', array($this, 'apppago_order_column_content'));
        //Add Fiter in order page
        add_action('restrict_manage_posts', array($this, 'filter_orders_by_payment_method'), 20);
        add_filter('request', array($this, 'filter_orders_by_payment_method_query'));
        //Add custom order status
        add_action('init', array($this, 'register_instalments_payment_order_status'), 25);
        add_filter('wc_order_statuses', array($this, 'add_instalments_payment_to_order_statuses'));
        //Set payment title in checkout
        add_action('woocommerce_checkout_update_order_review', array($this, 'set_title'));

        add_filter('woocommerce_order_number', array($this, 'change_woocommerce_order_number'));
        add_filter('woocommerce_available_payment_gateways', array($this, 'handle_payment_gateway_visaulization'));
        add_filter('woocommerce_order_data_store_cpt_get_orders_query', array($this, 'handle_order_ap_payment_id_custom_query_var'), 10, 2);
    }

    public function __destruct()
    {
        //Add JS script in Front and BO
        remove_action('wp_enqueue_scripts', array($this, 'add_checkout_script'));
        remove_action('admin_enqueue_scripts', array($this, 'add_admin_script'));

        //Custom Field
        remove_action('woocommerce_before_add_to_cart_button', array($this, 'apppago_display_custom_field'));
        remove_action('woocommerce_before_shop_loop_item_title', array($this, 'apppago_display_custom_badge'), 10, 0);
        //End Custom Field
        remove_action('woocommerce_api_wc_gateway_' . $this->id, array($this, 'wc_apppago_payment_return'));
        remove_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'apppago_checkConfigs'));
        //Box installments in my account order page and thank you page
        remove_action('woocommerce_view_order', array($this, 'wc_apppago_myorder'), 20);
        remove_action('woocommerce_thankyou', array($this, 'wc_apppago_show_order_and_empty_cart'), 20);
        //Widget
        remove_action('wp_dashboard_setup', array($this, 'apppago_dashboard_widgets'));
        //Add extra Column in order page
        remove_action('manage_edit-shop_order_columns', array($this, 'apppago_order_column'));
        remove_action('manage_shop_order_posts_custom_column', array($this, 'apppago_order_column_content'));
        //Add Fiter in order page
        remove_action('restrict_manage_posts', array($this, 'filter_orders_by_payment_method'), 20);
        remove_action('request', array($this, 'filter_orders_by_payment_method_query'));
        //Add custom order status
        remove_action('init', array($this, 'register_instalments_payment_order_status'), 25);
        remove_action('wc_order_statuses', array($this, 'add_instalments_payment_to_order_statuses'));
        //Set payment title in checkout
        remove_action('woocommerce_checkout_update_order_review', array($this, 'set_title'));

        remove_filter('woocommerce_order_number', array($this, 'change_woocommerce_order_number'));
        remove_filter('woocommerce_available_payment_gateways', array($this, 'handle_payment_gateway_visaulization'));
        remove_filter('woocommerce_order_data_store_cpt_get_orders_query', array($this, 'handle_order_ap_payment_id_custom_query_var'), 10, 2);
    }

  
    public function handle_payment_gateway_visaulization($available_gateways)
    {
        if (isset(WC()->cart)) {
            $categories = array();
            foreach (WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
               $product_id = $cart_item['product_id'];
               array_push($categories, ...wc_get_product_term_ids($product_id, 'product_cat'));
            }
            $categories = array_unique($categories);

            if (isset($available_gateways['apppago'])
                && (!$this->verify_amount_valid()
                || count(array_intersect($categories, $this->retireve_categories())) == 0)) {
                unset($available_gateways['apppago']);
            } 
        }
        return $available_gateways;
    }

    private function verify_amount_valid()
    {
        $cartTotal = $this->amount_handler->from_wc_cart(WC()->cart)->value();

        $minCart = $this->oConfig->settings['ap_min_cart'];
        $maxCart = $this->oConfig->settings['ap_max_cart'];

        $minValid = empty($minCart) || floatval($minCart) < $cartTotal;
        $maxValid = empty($maxCart) || floatval($maxCart) > $cartTotal;
        
        return $minValid && $maxValid;
    }

    private function retireve_categories()
    {
        $enabledCategories = $this->oConfig->settings['ap_categories'];

        if ($enabledCategories) {
            if (!is_array($enabledCategories)) {
                $enabledCategories = json_decode($enabledCategories);
            }
        } else {
            $enabledCategories = array();
        }

        return $enabledCategories;
    }

    public function change_woocommerce_order_number($order_id)
    {
        $prefix = 'AP-';
        $new_order_id = $prefix . $order_id;
        return $new_order_id;
    }


    public function register_instalments_payment_order_status()
    {
        register_post_status('wc-incomplete-inst', array(
            'label' => __('Incomplete instalments payment', 'apppago'),
            'public' => true,
            'exclude_from_search' => false,
            'show_in_admin_all_list' => true,
            'show_in_admin_status_list' => true,
            'label_count' => _n_noop('Incomplete inst. (%s)', 'Incomplete inst. (%s)', 'apppago')
        ));

        register_post_status('wc-completed-inst', array(
            'label' => __('Completed instalments payment', 'apppago'),
            'public' => true,
            'exclude_from_search' => false,
            'show_in_admin_all_list' => true,
            'show_in_admin_status_list' => true,
            'label_count' => _n_noop('Completed inst. (%s)', 'Completed inst. (%s)', 'apppago')
        ));
    }

    public function add_instalments_payment_to_order_statuses($order_statuses)
    {
        return self::get_order_status($order_statuses);
    }

    public static function get_order_status($order_statuses)
    {
        $new_order_statuses = array();

        foreach ($order_statuses as $key => $status) {
            $new_order_statuses[$key] = $status;
        }

        $new_order_statuses['wc-incomplete-inst'] = __('Incomplete instalments payment', 'apppago');
        $new_order_statuses['wc-completed-inst'] = __('Completed instalments payment', 'apppago');

        return $new_order_statuses;
    }

    public function set_title()
    {
        if ($this->oConfig->get_installments_number(WC()->cart)['pay_ins']) {
            $this->title = __('Payment in installments', 'apppago');
        } else {
            $this->title = __('Credit card', 'apppago');
        }
    }

    /**
     * checks apppago credentials and saves settings
     * check on min/max installments number, if not valid, the plugin is still available but the payment can be made only in a single solution
     * 
     */
    public function apppago_checkConfigs()
    {
        //saves post data to db
        $this->process_admin_options();

        $postDati = $this->get_post_admin_options();

        $this->oConfig = new WC_Gateway_APPpago_Configuration($this->settings);

        $api = new WC_APPpago_Api(WC_APPpago::get_local_domain(), get_site_url() . '?wc-api=WC_Gateway_APPpago');

        $api->set_settings($postDati['ap_merchant_id'], $postDati['ap_service'], $postDati['ap_secret']);

        try {
            $api->checkConfigs();
        } catch (\Exception $exc) {
            WC_Admin_Settings::add_error($exc->getMessage());

            $option = get_option('woocommerce_apppago_settings');
            $option['enabled'] = 'no';

            update_option('woocommerce_apppago_settings', $option);
        }

        $resInsNumber = $this->oConfig->check_range_configs();

        if (!$resInsNumber['res']) {
            WC_Admin_Settings::add_error($resInsNumber['msg']);
        }

    }

    /**
     * returns submitted setting options
     * 
     * @return boolean
     */
    public function get_post_admin_options()
    {
        $post_data = $this->get_post_data();

        $fields = array();

        foreach ($this->get_form_fields() as $key => $value) {
            if ('title' !== $this->get_field_type($value)) {
                try {
                    $fields[$key] = $this->get_field_value($key, $value, $post_data);
                } catch (Exception $e) {
                    $fields[$key] = false;
                }
            }
        }

        return $fields;
    }

    //Add Fiter in order page
    public function filter_orders_by_payment_method()
    {
        global $typenow;

        if ('shop_order' === $typenow) {
            // get all payment methods, even inactive ones
            $gateways = WC()->payment_gateways->payment_gateways();
            ?>
            <select name="_shop_order_payment_method" id="dropdown_shop_order_payment_method">
                <option value="">
                    <?php esc_html_e('All Payment Methods', 'apppago'); ?>
                </option>

                <?php foreach ($gateways as $id => $gateway) { ?>
                    <option value="<?php echo esc_attr($id); ?>" <?php echo esc_attr(isset($_GET['_shop_order_payment_method']) ? selected($id, $_GET['_shop_order_payment_method'], false) : ''); ?>>
                        <?php echo esc_html($gateway->get_method_title()); ?>
                    </option>
                <?php } ?>
            </select>
            <?php
        }
    }

    public function filter_orders_by_payment_method_query($vars)
    {
        global $typenow;

        if ('shop_order' === $typenow && isset($_GET['_shop_order_payment_method'])) {
            $vars['meta_key'] = '_payment_method';

            $vars['meta_value'] = wc_clean($_GET['_shop_order_payment_method']);
        }

        return $vars;
    }

    /**
     * Add extra Column in order page
     * 
     * @param type $columns
     * @return type
     */
    public function apppago_order_column($columns)
    {
        $columns['payment_method'] = __('Payment Method', 'apppago');
        $columns['installments_number'] = __('Installments', 'apppago');
        $columns['installments_status'] = __('Last Installment', 'apppago');

        return $columns;
    }

    /**
     * sets content for extra columns
     * 
     * @global type $post
     * @param type $column
     */
    public function apppago_order_column_content($column)
    {
        global $post;

        self::$lastColumn = $column;

        if (is_array(get_post_meta($post->ID, 'apppago_installments', true))) {
            $data = get_post_meta($post->ID, 'apppago_installments', true);
        } else {
            $data = json_decode(get_post_meta($post->ID, 'apppago_installments', true));
        }

        if ('payment_method' === $column) {
            if ($data == null) {
                return;
            }

            if ($data->installments != null && count($data->installments) > 1) {
                echo esc_html(__('Payment in installments', 'apppago') . ' ' . $this->title);
            } else {
                echo esc_html(__('Credit card', 'apppago') . ' ' . $this->title);
            }
        } elseif ('installments_number' === $column) {
            if ($data == null || $data->installments == null || count($data->installments) <= 1) {
                return;
            }

            $total_installments = count($data->installments);

            $payed = 0;

            foreach ($data->installments as $set) {
                if ($set->transactionDate != '') {
                    $payed += 1;
                }
            }

            echo esc_html($payed . '/' . $total_installments);
        } elseif ('installments_status' === $column) {
            if (is_array(get_post_meta($post->ID, 'apppago_installments', true))) {
                $data = get_post_meta($post->ID, 'apppago_installments', true);
            } else {
                $data = json_decode(get_post_meta($post->ID, 'apppago_installments', true));
            }

            if ($data == null || $data->installments == null || count($data->installments) <= 1) {
                return;
            }

            $status = __APPPAGO_ICON_KO__;

            foreach ($data->installments as $set) {
                if ($set->transactionDate && $set->transactionStatus != __APPPAGO_TS_PAYED__) {
                    $status = __APPPAGO_ICON_KO__;
                    break;
                } else {
                    $status = __APPPAGO_ICON_OK__;
                }
            }

            echo esc_html($status);
        }
    }

    /**
     * displays installments counters on dashboard
     * 
     * @global type $wp_meta_boxes
     */
    public function apppago_dashboard_widgets()
    {
        global $wp_meta_boxes;
        wp_add_dashboard_widget('apppago_widget', __('Status of installment transactions', 'apppago'), array($this, 'apppago_widget'));
    }

    /**
     * calculates data for dashboard counters
     * 
     */
    public function apppago_widget()
    {
        $query = new WC_Order_Query(array(
            'limit' => -1,
            'orderby' => 'date',
            'order' => 'DESC',
            'payment_method' => 'apppago',
            'return' => 'ids',
        ));
        $orders = $query->get_orders();

        $ok = 0;
        $ko = 0;
        $completed = 0;

        foreach ($orders as $orders_id) {
            if (get_post_meta($orders_id, 'apppago_installments', true) != null) {
                $recurrences = json_decode(get_post_meta($orders_id, 'apppago_installments', true));
                $status = false;
                $count = 0;
                $recurrence = 0;

                if (isset($recurrences->recurrencesSet)) {  //for compatibility with old data structure
                    foreach ($recurrences->recurrencesSet as $set) {
                        $recurrence += 1;
                        if ($set->actualChargeDate == '' && $set->lastChargeAttemptDate != '') {
                            $status = false;
                            break;
                        } elseif ($set->actualChargeDate != '' && $set->lastChargeAttemptDate != '') {
                            $status = true;
                            $count += 1;
                        } else {
                            $status = true;
                        }
                    }
                } else if (isset($recurrences->installments)) {
                    foreach ($recurrences->installments as $set) {
                        $recurrence += 1;

                        if ($set->transactionStatus == __APPPAGO_TS_UNSOLVED__) {
                            $status = false;
                            break;
                        } else {
                            $status = true;
                            if ($set->transactionDate != null) {
                                $count += 1;
                            }
                        }
                    }
                }

                if ($status) {
                    if ($count == $recurrence) {
                        $completed += 1;
                    } else {
                        $ok += 1;
                    }
                } else {
                    $ko += 1;
                }
            }
        }

        $path = plugin_dir_path(__DIR__);
        $logo = plugins_url('assets/images/apppago.png', plugin_dir_path(__FILE__));
        $okText = __('Transaction opened', 'apppago');
        $koText = __('Transaction with problem', 'apppago');
        $completedText = __('Transaction completed', 'apppago');
        include_once $path . 'templates/' . __FUNCTION__ . ".php";
    }

    public function wc_apppago_myorder($order_id)
    {
        $aOrderInstallments = json_decode(get_post_meta($order_id, 'apppago_installments', true));
        $path = plugin_dir_path(__DIR__);
        $domain = get_site_url();
        include_once $path . 'templates/' . __FUNCTION__ . ".php";
    }

    /**
     * adds badge ('payable in installments') under product miniature
     * 
     * @global type $post
     */
    public function apppago_display_custom_badge()
    {
        if ($this->oConfig->is_enabled()) {
            global $post;

            $ret = $this->oConfig->payable_in_installments($post->ID);

            if ($ret['res']) {
                echo '<div class="apppago-custom-field-wrapper">'
                . '<span class="apppago-custom-badge" style="text-transform: uppercase;padding: 3px;width: 100%;max-width: 400px;margin-bottom: 1em;border: 1px solid;border-radius: 3px;/*color: black;border-color: black;*/display: inline-block;margin-top: 1em;"">'
                . esc_html(__('product available in', 'apppago'))
                . ' ' . esc_html($ret['max_ins']) . ' ' . esc_html(__('installments', 'apppago')) . '</span>'
                . '</div>';
            }
        }
    }

    /**
     * adds additional info ('payable in installments') in product detail page - FrontOffice - Shop
     * 
     * @global type $post
     */
    public function apppago_display_custom_field()
    {
        if ($this->oConfig->is_enabled()) {
            global $post;

            $ret = $this->oConfig->payable_in_installments($post->ID);

            if ($ret['res']) {
                echo '<div class="apppago-custom-field-wrapper" style="background-color: lightgrey; border-left-color: #0f4094; border-left-style: outset; padding: 15px; max-width: 600px; display: block; margin-bottom: 30px;"">'
                . '<table style="margin: 0;">'
                . '<tr>'
                . '<td style="border: 0; vertical-align: middle; padding: 0; background-color: lightgrey; padding-right: 8px;"><h5 style="padding-left: 10px; margin: 0; font-weight: bold;">' . esc_html(__('INSTALLABLE PRODUCT', 'apppago')) . '</h5></td>'
                . '<td rowspan="2" style="border: 0; vertical-align: middle; padding: 0; background-color: lightgrey; width: 115px;"><img src=' . esc_url(plugins_url('assets/images/apppago.png', plugin_dir_path(__FILE__))) . ' style="max-height: 45px; float: right;"></td>'
                . '</tr>'
                . '<tr>'
                . '<td style="border: 0; vertical-align: middle; padding: 0; background-color: lightgrey; padding-right: 8px;"><h6 style="padding-left:10px; margin: 0;">' . esc_html(__('you can pay this products in', 'apppago')) . ' ' . esc_html($ret['max_ins']) . ' ' . esc_html(__('installments', 'apppago')) . '</h6></td>'
                . '</tr>'
                . '</table></div>';
            }
        }
    }

    /**
     * payment option form
     * 
     */
    public function form()
    {
        $installmentsInfo = $this->oConfig->get_installments_number(WC()->cart);

        $amount = $this->amount_handler->from_wc_cart(WC()->cart)->value_str();

        $serviceID = $this->oConfig->ap_service;
        $uniqueID = $this->oConfig->ap_secret;
        $merchantID = $this->oConfig->ap_merchant_id;
        $possibleRates = $this->retrieve_possible_rates_string();
        $ratePaymentsAvaible = $installmentsInfo['pay_ins'];
        $paymentId = $this->generate_payment_id();
        $domain = WC_APPpago::get_local_domain();
        $statusUpdateCallbackUrl = get_rest_url(null, 'apppago/recurrence-hook');
        $hashPass = sha1('paymentId='.$paymentId.'domain='.$domain.'serviceSmallpay='.$serviceID.'totalAmount='.$amount.'possibleRates='.$possibleRates.'statusUpdateCallbackUrl='.$statusUpdateCallbackUrl.'uniqueId='.$uniqueID);

        $path = plugin_dir_path(__DIR__);
        include_once $path . 'templates/' . __FUNCTION__ . ".php";
    }

    private function retrieve_possible_rates_string()
    {
        $possRate = $this->oConfig->ap_rates;
        $possLength = count($possRate);
        $possibleRates = '';
        for($i=0;$i<$possLength;$i++){
            if($i=== $possLength-1){
                $possibleRates .= $possRate[$i];
            } else {
                $possibleRates .= $possRate[$i].',';
            }
        }
        return $possibleRates;
    }

    private function generate_payment_id()
    {
        return current_time('timestamp') . '_' . WC()->cart->get_cart_hash();
    }

    /**
     * Add JS & CSS to checkout page
     */
    public function add_checkout_script()
    {
        wp_enqueue_style('style_apppago', plugins_url('assets/css/apppago.css', plugin_dir_path(__FILE__)));

        wp_register_script('apppago-passepartout', APPPAGO_SCRIPT, array(), $this->module_version, false);
        
        wp_enqueue_script('apppago-passepartout');
        wp_enqueue_script('apppago', plugins_url('assets/js/apppago.js', plugin_dir_path(__FILE__)), array( 'jquery' ), $this->module_version, false);
    }

    /**
     * Add JS & CSS to WC BackOffice
     */
    public function add_admin_script()
    {
        wp_enqueue_style('select2css', plugins_url('assets/css/select2.min.css', plugin_dir_path(__FILE__)));
        wp_enqueue_style('apppago_style', plugins_url('assets/css/apppago.css', plugin_dir_path(__FILE__)));

        wp_enqueue_script('select2', plugins_url('assets/js/select2.min.js', plugin_dir_path(__FILE__)), array('jquery'), $this->module_version, true);
        wp_enqueue_script('apppago_xpay_build_config', plugins_url('assets/js/apppago_back.js', plugin_dir_path(__FILE__)), array(), $this->module_version, true);
    }

    /**
     * Return true if APPpago is avaiable between payment methods
     */
    public function is_available()
    {
        if (is_add_payment_method_page()) { //Check if user is not in add payment method page in his account
            return false;
        }

        if (get_woocommerce_currency() !== "EUR") { //Check if currency is EURO
            return false;
        }

        if (class_exists("WC_Subscriptions_Cart") && WC_Subscriptions_Cart::cart_contains_subscription()) { //Check if cart contains subscription
            return false;
        }

        return parent::is_available();
    }

    public function get_payment_id_from_order($order)
    {
        return $order->get_meta('ap_payment_id');
    }

    /**
     * Funzione obbigatoria per WP, processa il pagamento e fa il redirect
     *
     * @param type $order_id
     * @return type
     */
    public function process_payment($order_id)
    {
        $order = new WC_Order($order_id);

        $paymentId = sanitize_text_field($_POST['apppago_button_paymentid']);
        if (!isset($paymentId) || empty($paymentId)) {
            return false;
        }

        $order->update_meta_data('ap_payment_id', $paymentId);
        $order->save();
        
        $resp = $this->verify_checkout_completed($order);
        
        if (!isset($resp)) {
            return false;
        }

        if ($resp['status'] === __APPPAGO_IP_ACTIVE__) {
            return array('result' => 'success', 'redirect' => get_rest_url(null, 'apppago/payment-return/' . $order->get_id()));
        }
        return false;
    }


    /**
     * Retrieve the recurrence payment for the order
     * @return bool
     * @throws Exception
     */
    public function verify_checkout_completed($order)
    {
        $api = new WC_APPpago_Api(WC_APPpago::get_local_domain(), null);
        $api->set_settings($this->oConfig->ap_merchant_id, $this->oConfig->ap_service, $this->oConfig->ap_secret);
        $api->set_orderReference($this->get_payment_id_from_order($order));

        try {
            $resp = $api->retrieve_recurrences();
        
            if ($resp) {
                return $api->response;
            }
        } catch (\Exception $ex) {
            wc_add_notice($ex->getMessage(), "error");
            return null;
        }
        return null;
    }

    /**
     * handles return from payment gateway
     * 
     * @param type $data
     * @return array('status'=> 'OK'|'KO', 'response'=> WP_REST_Response)
     */
    public function wc_apppago_payment_return($data)
    {
        //needed to add error notice
        WC()->frontend_includes();

        WC()->session = new WC_Session_Handler();
        WC()->session->init();

        $params = $data->get_params();

        $order = new WC_Order($params["paymentId"]);

        $api = new WC_APPpago_Api(WC_APPpago::get_local_domain(), null);
        $api->set_orderReference($this->get_payment_id_from_order($order));
        $api->set_settings($this->oConfig->ap_merchant_id, $this->oConfig->ap_service, $this->oConfig->ap_secret);

        try {
            $api->retrieve_recurrences();

            $response = $api->response;

            update_post_meta($order->get_id(), 'apppago_installments', json_encode($response));

            if ($response['status'] !== __APPPAGO_IP_ACTIVE__) {
                $error = __('The first payment wasn\'t made or the transaction was unsuccessful', 'apppago');

                WC_APPpago_Logger::LogExceptionError(new \Exception('APPpago return - ' . $error . ' - ' . json_encode($response)));

                wc_add_notice($error, "error");

                return array(
                    'status' => 'KO',
                    'response' => new WP_REST_Response($error, 303, array("Location" => wc_get_checkout_url()))
                );
            }

            $config = get_option('woocommerce_apppago_settings');

            if ($order->get_status() != $config['ap_incomplete_status'] && $order->get_status() != $config['ap_complete_status'] && $order->get_status() != 'processing') {
                $order->payment_complete();

                $order->update_status($config['ap_incomplete_status']);
            }

            return array(
                 'status' => 'OK',
                 'response' => new WP_REST_Response(null, "200", array("Refresh" => "1; URL=" . $this->get_return_url($order)))
            );
        } catch (\Exception $exc) {
            wc_add_notice($exc->getMessage(), "error");
            return array(
                 'status' => 'KO',
                 'response' => new WP_REST_Response($exc->getMessage(), 303, array("Location" => wc_get_checkout_url()))
            );
        }
    }

    /**
     * empties the cart and shows order detail
     * 
     * @param type $order_id
     */
    function wc_apppago_show_order_and_empty_cart($order_id)
    {
        //can't be done in wc_apppago_payment_return because session is not available
        //therefore user's cart can't be cleared
        WC()->cart->empty_cart(true);

        $this->wc_apppago_myorder($order_id);
    }

    /**
     * handles installments status update
     * 
     * @param type $data
     * @return array('status'=> 'OK'|'KO', 'response'=> WP_Error|WP_REST_Response)
     */
    public function wc_apppago_status_callback($data)
    {
        $request = json_decode(file_get_contents('php://input'), true);

        if ($request == false || !is_array($request)) {
            $error = __('Request format not valid', 'apppago');

            WC_APPpago_Logger::LogExceptionError(new \Exception('Status update callback - ' . $error . ' - ' . json_encode($request)));

            return array(
                'status' => 'KO',
                'response' => new WP_Error('data_missing', array('data_missing' => array('status' => 500, 'message' => $error)))
            );
        }

        $orders = wc_get_orders(array('ap_payment_id' => $request['paymentId']));

        if (!isset($orders) || count($orders) != 1) {
            $error = __('Order not found', 'apppago');

            WC_APPpago_Logger::LogExceptionError(new \Exception('Status update callback - ' . $error . ' - ' . json_encode(array(
                                'calculatedHashPass' => $calculatedHashPass,
                                'ap_secret' => $this->settings['ap_secret'],
                                'apppagoRequest' => $request
                            )))
            );

            return array(
                'status' => 'KO',
                'response' => new WP_Error('order_not_found', array('order_not_found' => array('status' => 500, 'message' => $error)))
            );
        }

        $calculatedHashPass = sha1('paymentId=' . $request['paymentId'] . 'domain=' . $request['domain'] . 'timestamp=' . $request['timestamp'] . 'uniqueId=' . $this->settings['ap_secret']);

        if ($calculatedHashPass != $request['hashPass']) {
            $error = __('Invalid hashPass', 'apppago');

            WC_APPpago_Logger::LogExceptionError(new \Exception('Status update callback - ' . $error . ' - ' . json_encode(array(
                                'calculatedHashPass' => $calculatedHashPass,
                                'ap_secret' => $this->settings['ap_secret'],
                                'apppagoRequest' => $request
                            )))
            );

            return array(
                'status' => 'KO',
                'response' => new WP_Error('invalid_hashPass', array('invalid_hashPass' => array('status' => 500, 'message' => $error)))
            );
        }

        if (!isset($request['installments'])) {
            $error = __('Missing installments info', 'apppago');

            WC_APPpago_Logger::LogExceptionError(new \Exception('Status update callback - ' . $error . ' - ' . json_encode($request)));

            return array(
                'status' => 'KO',
                'response' => new WP_Error('missing_installments', array('missing_installments' => array('status' => 500, 'message' => $error)))
            );
        }
        
        $order = new WC_Order($orders[0]->get_id());

        update_post_meta($order->get_id(), 'apppago_installments', json_encode($request));

        if ($request['status'] !== __APPPAGO_IP_ACTIVE__) {
            $error = __('The first payment wasn\'t made or the transaction was unsuccessful', 'apppago');

            WC_APPpago_Logger::LogExceptionError(new \Exception('Status update callback - ' . $error . ' - ' . json_encode($request)));

            return array(
                'status' => 'KO',
                'response' => new WP_Error('first_payment_error', array('first_payment_error' => array('status' => 500, 'messagge' => $error)))
            );
        }

        header('Content-Type: application/json');

        $config = get_option('woocommerce_apppago_settings');

        if ("wc-" . $order->get_status() != $config['ap_incomplete_status']) {
            $order->payment_complete();

            if (count($request['installments']) > 1) {
                $config = get_option('woocommerce_apppago_settings');

                $order->update_status($config['ap_incomplete_status']);
            }
        } else {
            $completed = false;

            foreach ($request['installments'] as $set) {
                if ($set['transactionStatus'] == __APPPAGO_TS_UNSOLVED__) {
                    $completed = false;
                    break;
                } elseif (in_array($set['transactionStatus'], array(__APPPAGO_TS_PAYED__, __APPPAGO_TS_DELETED__))) {
                    $completed = true;
                } else {
                    $completed = false;
                }
            }

            if ($completed) {
                $config = get_option('woocommerce_apppago_settings');
                $order->update_status($config['ap_complete_status']);
            }
        }

        return array(
            'status' => 'OK',
            'response' => new WP_REST_Response(array(), 200)
        );
    }

    public function handle_order_ap_payment_id_custom_query_var( $query, $query_vars )
    {
        if ( ! empty( $query_vars['ap_payment_id'] ) ) {
            $query['meta_query'][] = array(
                'key' => 'ap_payment_id',
                'value' => esc_attr( $query_vars['ap_payment_id'] ),
            );
        }
    
        return $query;
    }

    public static function explode_paymentId($paymentId)
    {
        $temp = explode('-', $paymentId);

        return array(
            'order_id' => $temp[0],
            'timestamp' => $temp[1] ?? null,
            'post_id' => $temp[0]
        );
    }

}
