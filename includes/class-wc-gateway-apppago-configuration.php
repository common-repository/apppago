<?php

class WC_Gateway_APPpago_Configuration
{

    public $settings;
    public $ap_merchant_id;
    public $ap_secret;
    public $ap_service;
    public $ap_rates;

    public function __construct($settings)
    {
        $this->settings = $settings;
        $this->init_settings();
    }

    public function init_settings()
    {
        $this->ap_merchant_id = isset($this->settings['ap_merchant_id']) ? trim($this->settings['ap_merchant_id']) : false;
        $this->ap_secret = isset($this->settings['ap_secret']) ? trim($this->settings['ap_secret']) : false;
        $this->ap_service = isset($this->settings['ap_service']) ? trim($this->settings['ap_service']) : false;
        $this->ap_rates = isset($this->settings['ap_rates']) ? $this->settings['ap_rates'] : false;
    }

    //SETTINGS FORM MANAGEMENT

    public function get_form_fields()
    {
        $form_fields = array(
            'module_description' => array(
                'title' => '',
                'type' => 'title',
                'description' => __('From this page it is possible to insert the general configurations of the module. We remind you to enable the individual products you wish to offer with installment payment directly from the product sheet.', 'apppago'),
                'class' => 'style_title'
            ),
            'title_section_1' => array(
                'title' => __('Gateway APPpago configuration', 'apppago'),
                'type' => 'title',
            ),
            'enabled' => array(
                'title' => __('Enable/Disable', 'apppago'),
                'type' => 'checkbox',
                'label' => __('Enable APPpago Payment Module.', 'apppago'),
                'default' => 'no'
            ),
            'ap_merchant_id' => array(
                'title' => __('Merchant ID', 'apppago') . ' *',
                'type' => 'text',
                'desc_tip' => __('Given to Merchant by APPpago', 'apppago')
            ),
            'ap_service' => array(
                'title' => __('Service ID', 'apppago') . ' *',
                'type' => 'text',
                'desc_tip' => __('Given to Merchant by APPpago', 'apppago')
            ),
            'ap_secret' => array(
                'title' => __('Unique ID', 'apppago') . ' *',
                'type' => 'text',
                'desc_tip' => __('Given to Merchant by APPpago', 'apppago')
            ),
            'ap_rates' => array(
                'title' => __('Possible Rates', 'apppago') . ' *',
                'type' => 'multiselect',
                'options' => $this->get_options_for_possible_rates(),
                'desc_tip' => __('Possible Rates Helper', 'apppago'),
                'class'=>'categories-select2'
            ),
            'options_title' => array(
                'title' => __('APPpago Options', 'apppago'),
                'type' => 'title',
                'description' => __('Using this configurator you can set categories that can be paid in installments with relative price ranges and number of installments', 'apppago'),
            ),
        );

        $form_fields = array_merge(
            $form_fields,
            array(
                'range_title' => array(
                    'title' => ucfirst(__('first range of price', 'apppago')),
                    'type' => 'title',
                    'class' => 'title'
                ),
               'ap_categories' => array(
                   'title' => __('Installment categories', 'apppago'),
                   'type' => 'multiselect',
                   'options' => $this->get_options_config_catefories_tree(),
                   'desc_tip' => __('Check all the categories you want the payment in installments to be enabled on', 'apppago'),
                   'class' => 'categories-select2'
               ),
               'ap_min_cart' => array(
                   'title' => __('Price range from - €', 'apppago'),
                   'type' => 'text',
                   'desc_tip' => __('The minimum value of products for which it is possible to make an installment payment.', 'apppago')
               ),
               'ap_max_cart' => array(
                   'title' => __('Price range to - €', 'apppago'),
                   'type' => 'text',
                   'desc_tip' => __('The maximum value of products for which it is possible to make an installment payment.', 'apppago')
               )
            )
        );

        $form_fields = array_merge($form_fields, array(
            'ap_incomplete_status' => array(
                'title' => __('Creation order Status', 'apppago'),
                'type' => 'select',
                'description' => __('Status of order at creation', 'apppago'),
                'default' => 'wc-incomplete-inst',
                'desc_tip' => true,
                'options' => $this->get_options_order_status(),
                'class' => 'build_style font-style'
            ),
            'ap_complete_status' => array(
                'title' => __('Completed payment Status', 'apppago'),
                'type' => 'select',
                'description' => __('Status of order at the end of installments', 'apppago'),
                'default' => 'wc-completed-inst',
                'desc_tip' => true,
                'options' => $this->get_options_order_status(),
                'class' => 'build_style font-style'
            )
        ));

        return $form_fields;
    }

    public function get_options_order_status()
    {
        return WC_Gateway_APPpago::get_order_status(wc_get_order_statuses());
    }

    public function get_options_for_possible_rates()
    {
        return [
            "3"=>3,
            "4"=>4,
            "5"=>5,
            "6"=>6,
            "7"=>7,
            "8"=>8,
            "9"=>9,
            "10"=>10,
            "11"=>11,
            "12"=>12,
            "13"=>13,
            "14"=>14
        ];
    }

    public function get_options_config_catefories_tree()
    {
        $categories = get_terms(['taxonomy' => 'product_cat', 'hide_empty' => false]);

        $parentCategories = array();
        $childCategories = array();

        foreach ($categories as $category) {
            if ($category->parent == 0) {
                $parentCategories[] = $category;
            } else {
                if (!array_key_exists($category->parent, $childCategories)) {
                    $childCategories[$category->parent] = array();
                }

                $childCategories[$category->parent][] = $category;
            }
        }

        $options = array();

        foreach ($parentCategories as $parentCategory) {
            $options[$parentCategory->term_id] = $parentCategory->name;

            $childOptions = $this->get_child_options($childCategories, $parentCategory->term_id);

            foreach ($childOptions as $key => $childOption) {
                $options[$key] = $parentCategory->name . ' -> ' . $childOption;
            }
        }

        return $options;
    }

    private function get_child_options($childCategories, $id)
    {
        $options = array();

        if (array_key_exists($id, $childCategories)) {
            foreach ($childCategories[$id] as $childCategory) {
                $options[$childCategory->term_id] = $childCategory->name;

                $childOptions = $this->get_child_options($childCategories, $childCategory->term_id);

                foreach ($childOptions as $childKey => $childOption) {
                    $options[$childKey] = $childCategory->name . ' -> ' . $childOption;
                }
            }
        }

        return $options;
    }

    //CONFIGURATION MANAGEMENT
    public function is_enabled()
    {
        if ($this->settings['enabled'] == 'yes') {
            return true;
        }

        return false;
    }

    /**
     * categories saved as payable in installments
     * 
     * @return array
     */
    private function get_enabled_categories()
    {
        $enabledCategories = $this->settings['ap_categories'];

        if ($enabledCategories) {
            if (!is_array($enabledCategories)) {
                $enabledCategories = json_decode($enabledCategories);
            }
        } else {
            $enabledCategories = array();
        }

        return $enabledCategories;
    }

    /**
     * checks groups selected min/max installments and amount
     * 
     * @param string $range
     * @return array
     */
    public function check_range_configs()
    {
        $ret = array('res' => true, 'msg' => '');

        $resInsNumber = $this->check_config_installments();

        $ret['msg'] = ucfirst(__('first range of price', 'apppago')) . ': ';

        $errorMsg = array();

        if (!$resInsNumber['res']) {
            $ret['res'] = false;
            $errorMsg[] = $resInsNumber['msg'];
        }

        $resAmount = $this->check_config_amounts();

        if (!$resAmount['res']) {
            $ret['res'] = false;
            $errorMsg[] = $resAmount['msg'];
        }

        $ret['msg'] .= implode(' | ', $errorMsg);

        return $ret;
    }

    /**
     * checks if selected min and max installments numbers, in config section, are valid
     * 
     * @param string $range
     * @return array
     */
    public function check_config_installments()
    {
        $res = false;
        $msg = array();

        $rates = $this->get_rage_rates();

        $rateCounts = count($rates);

        $min = min($rates);
        $max = max($rates);

        if ($rateCounts < 1) {
            array_push($msg, __('Invalid number of settable installments.', 'apppago'));   
        }

        if(count($msg) >0){
            return array(
                'res' => false,
                'msg' =>  implode(' | ', $msg)
            );
        } else {
            return array(
                'res' => true,
                'msg' =>  ''
            );
        }
    }

    private function get_rage_rates() {
        return array_map('intval', $this->ap_rates);
    }

    /**
     * checks price ranges min/max amounts
     * 
     * @param string $range
     * @return array
     */
    private function check_config_amounts()
    {
        $ret = array('res' => true, 'msg' => '');

        $minGroup = (float) $this->settings['ap_min_cart'];
        $maxGroup = (float) $this->settings['ap_max_cart'];

        $errorMsg = array();

        if ($minGroup < 0) {
            $ret['res'] = false;
            $errorMsg[] = __('Invalid min amount.', 'apppago');
        }
        
        if (($maxGroup < $minGroup && $maxGroup <= 0)) {
            $ret['res'] = false;
            $errorMsg[] = __('Invalid max amount.', 'apppago');
        }

        $ret['msg'] .= implode(' | ', $errorMsg);

        return $ret;
    }

    /**
     * finds the payment configs based on the price
     * 
     * @param float $amount
     * @return array
     */
    private function get_price_range_configs($amount)
    {
        $ret = array('res' => true);

        if ($ret['res']) {
            if ($this->check_amount_in_range($amount)) {
                $ret['min_ins'] = min($this->get_rage_rates());
                $ret['max_ins'] = max($this->get_rage_rates());
            } else {
                $ret['min_ins'] = 0;
                $ret['max_ins'] = 0;
            }

            $ret['min_a'] = (float) $this->settings['ap_min_cart'];
            $ret['max_a'] = (float) $this->settings['ap_max_cart'];
            $ret['categories'] = $this->get_enabled_categories();
        }

        return $ret;
    }

    /**
     * 
     * @param string $range
     * @param float $amount
     * @return boolean
     */
    private function check_amount_in_range($amount)
    {
        $minRange = (float) $this->settings['ap_min_cart'];
        $maxRange = (float) $this->settings['ap_max_cart'];

        if ($minRange >= 0 && $maxRange >= 0) {
            if ($amount >= $minRange && $amount <= $maxRange) {
                return true;
            } else if ($minRange == 0 && $amount <= $maxRange) {
                return true;
            } else if ($maxRange == 0 && $amount >= $minRange) {
                return true;
            }
        }

        return false;
    }

    /**
     * finds the range of installments for the given cart of products
     * 
     * @param Cart $cart
     * @return array
     */
    public function get_installments_number($cart)
    {
        $min = null;
        $max = null;
        $payInOneInstallment = true;

        if ($cart !== null) {
            $payInOneInstallment = false;

            $products = $cart->get_cart();

            /**
             * for each product gets the price range settings from configuration and checks if there are product categories that can be paid in installments
             * than calculates MIN and MAX number of installments that can be used overall for all the products
             * 
             * if there are products that aren't in any of the seted ranges of price or if their categories cannot be paid in installments, returns 1 as MAX and MIN number of installments
             */
            foreach ($products as $product) {
                $p = wc_get_product($product['product_id']);

                // if it is a variable product, the price of the selected variant is retrived
                if ($p->is_type('variable')) {
                    $pPrice = (float) $p->get_price();

                    $pv = new \WC_Product_Variation($product['variation_id']);
                    $pPrice = $pv->get_price();
                } else {
                    $pPrice = (float) $p->get_price();
                }

                $rangeProps = $this->get_price_range_configs($pPrice);

                if (!$rangeProps['res']) {
                    $payInOneInstallment = true;
                    break;
                }

                $categories = wc_get_product_term_ids($product['product_id'], 'product_cat');

                if (count(array_intersect($categories, $rangeProps['categories'])) == 0) {
                    $payInOneInstallment = true;
                    break;
                }

                if ($min == null || $rangeProps['min_ins'] > $min) {
                    $min = $rangeProps['min_ins'];
                }

                if ($max == null || $rangeProps['max_ins'] < $max) {
                    $max = $rangeProps['max_ins'];
                }
            }

            //if $max < $min there aren't numbers of installments in common so you can't pay in installments
            if ($min > $max) {
                $payInOneInstallment = true;
            }
        }

        if ($payInOneInstallment) {
            return array(
                'min' => 1,
                'max' => 1,
                'pay_ins' => false
            );
        } else {
            return array(
                'min' => $min,
                'max' => $max,
                'pay_ins' => true
            );
        }
    }

    /**
     * checks if selected installments number, in payment form, is between config min and max OR if it is a payment in one solution
     * 
     * @param Cart $cart
     * @param int $installments
     * @return boolean
     */
    public function check_installments($cart, $installments)
    {
        $insNumbers = $this->get_installments_number($cart);

        if (($installments >= $insNumbers['min'] && $installments <= $insNumbers['max']) || $installments == 1) {
            return true;
        }

        return false;
    }

    /**
     * checks if a product is payable in more than 1 installment 
     * 
     * @param type $productId
     * @return type
     */
    public function payable_in_installments($productId)
    {
        $ret = array(
            'res' => false,
            'max_ins' => null
        );

        $product = wc_get_product($productId);

        if ($product !== false) {
            $rangeProps = $this->get_price_range_configs((float) $product->get_price());

            if ($rangeProps['res']) {
                $categories = wc_get_product_term_ids($productId, 'product_cat');

                if ($rangeProps['max_ins'] > 1 && count(array_intersect($categories, $rangeProps['categories'])) > 0) {
                    $ret['res'] = true;
                    $ret['max_ins'] = $rangeProps['max_ins'];
                }
            }
        }

        return $ret;
    }

}
