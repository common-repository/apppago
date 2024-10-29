<?php

/**
 * Plugin Name: APPpago
 * Plugin URI:
 * Description: Official APPpago plugin.
 * Version: 1.0.6
 * Author: SmallPay Srl
 * Author URI: https://www.smallpay.it
 * Text Domain: apppago
 * Domain Path: /lang
 *
 * Copyright: © 2021-2022, SmallPay Srl
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 */
/**
 * Required functions
 */
if (!defined('ABSPATH')) {
    exit;
}

define('APWC_PLUGIN_VERSION', '1.0.6');

class WC_APPpago
{

    /**
     * Plugin's version.
     *
     * @since 1.1.4
     *
     * @var string
     */
    public $version;

    /**
     * Plugin's absolute path.
     *
     * @var string
     */
    public $path;

    /**
     * Plugin's URL.
     *
     * @since 1.1.4
     *
     * @var string
     */
    public $plugin_url;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->version = APWC_PLUGIN_VERSION; //CHANGE

        $this->path = untrailingslashit(plugin_dir_path(__FILE__));
        $this->plugin_url = untrailingslashit(plugins_url('/', __FILE__));

        add_action('init', array($this, 'init'));
        add_action('admin_init', array($this, 'admin_init'));
        add_action('rest_api_init', array($this, 'register_routes'));

        add_filter('plugin_action_links_' . plugin_basename(__FILE__), array($this, 'my_plugin_action_links'));

        //Add custom page for terms and condition
        add_filter('init', function ($template) {
            if (isset($_GET['tos'])) {
                $tos = sanitize_text_field($_GET['tos']);
                include plugin_dir_path(__FILE__) . 'templates/tos.php';
                die;
            }
        });
    }

    /**
     * Init
     */
    public function init()
    {
        if (!$this->wc_apppago_is_plugin_woocommerce_active()) {
            add_action('admin_notices', function () {
                echo '<div class="error"><p>';
                echo esc_html(__('APPpago is inactive because WooCommerce is not installed.', 'apppago'));
                echo '</p></div>';
            });
            return;
        }

        $this->load_plugin_textdomain();
        $this->init_gateway();
        $this->init_admin_order_details();
    }

    /**
     * Ogni volta che si ricarica la pagina admin
     * si può fare controlli e fare uscire alert
     */
    public function admin_init()
    {
        
    }

    public static function wc_apppago_is_plugin_woocommerce_active()
    {
        $active_plugins = (array) get_option('active_plugins', array());
        
        if (count($active_plugins) === 0) {
            $active_plugins = (array) get_site_option('active_sitewide_plugins', array()); 
        }
        
        return in_array('woocommerce/woocommerce.php', $active_plugins) || array_key_exists('woocommerce/woocommerce.php', $active_plugins) || preg_grep("/woocommerce.php/", $active_plugins);
    }

    public function my_plugin_action_links($links)
    {
        $section = 'apppago';
        $plugin_links = array(
            '<a href="' . admin_url('admin.php?page=wc-settings&tab=checkout&section=' . $section) . '">' . __('Settings', 'apppago') . '</a>',
        );
        return array_merge($plugin_links, $links);
    }

    /**
     * Init gateway
     */
    public function init_gateway()
    {
        if (!class_exists('WC_Payment_Gateway')) {
            return;
        }
        include_once($this->path . '/includes/config_apppago.php');
        include_once($this->path . '/includes/class-wc-gateway-apppago.php');
        include_once($this->path . '/includes/class-wc-gateway-apppago-configuration.php');
        include_once($this->path . '/includes/class-wc-gateway-apppago-order-payment-info.php');
        require_once($this->path . '/includes/class-wc-gateway-apppago-logger.php');

        $this->gateway = new WC_Gateway_APPpago();
        add_filter('woocommerce_payment_gateways', array($this, 'add_gateway'));
    }

    /**
     * Load translations.
     *
     * @since 1.1.4
     */
    public function load_plugin_textdomain()
    {
        load_plugin_textdomain('apppago', false, dirname(plugin_basename(__FILE__)) . '/lang');
    }

    /**
     * Add APPpago to WC Gateways.
     *
     * @param array $methods List of payment methods.
     *
     * @return array List of payment methods.
     */
    public function add_gateway($methods)
    {
        $methods[] = $this->gateway;

        return $methods;
    }

    /**
     *
     */
    public function init_admin_order_details()
    {
        include_once($this->path . '/includes/class-wc-gateway-apppago-admin-order-details.php');

        $this->admin_order_details = new WC_Gateway_APPpago_Admin_Order_Details();
        $this->admin_order_details->set_meta_box_apppago();
    }

    /**
     *
     */
    public function xpay_style_scripts()
    {
        woocommerce_enqueue_styles('xpay-style', plugins_url('assets/css/apppago.css', plugin_dir_path(__FILE__)));
    }

    public static function is_plugin_woocommerce_active()
    {
        $active_plugins = (array) get_option('active_plugins', array());
        return in_array('woocommerce/woocommerce.php', $active_plugins) || array_key_exists('woocommerce/woocommerce.php', $active_plugins);
    }

    public function is_plugin_apppago_active()
    {
        return is_plugin_active('apppago/apppago.php');
    }

    public function deactivate_plugins()
    {
        deactivate_plugins('apppago/apppago.php');
    }

    public static function get_local_domain()
    {
        $domain = get_site_url();

        $domain = trim($domain, '/');

        if (!preg_match('#^http(s)?://#', $domain)) {
            $domain = 'http://' . $domain;
        }

        $urlParts = parse_url($domain);

        $domain = $urlParts['host'];

        return $domain;
    }

    /**
     * registers custom endpoints
     * 
     */
    public function register_routes()
    {
        register_rest_route('apppago', '/payment-return/(?P<paymentId>[^/]+)', array(
            array(
                'methods' => 'GET',
                'callback' => array($this, 'wc_apppago_payment_return'),
                'permission_callback' => '__return_true',
                'args' => array(array(
                    'paymentId'
                )),
            ),
        ));

        register_rest_route('apppago', '/recurrence-hook', array(
            array(
                'methods' => 'POST',
                'callback' => array($this, 'wc_apppago_status_callback'),
                'permission_callback' => '__return_true'
            )
        ));
    }

    /**
     * handles return from payment gateway
     * 
     * @param type $data
     * @return \WP_REST_Response
     */
    public function wc_apppago_payment_return($data)
    {
        return $this->gateway->wc_apppago_payment_return($data)['response'];
    }

    /**
     * handles installments status update
     * 
     * @param type $data
     * @return WP_Error|WP_REST_Response
     */
    public function wc_apppago_status_callback($data)
    {
        return $this->gateway->wc_apppago_status_callback($data)['response'];
    }

}

/**
 * Get order property with compatibility for WC lt 3.0.
 *
 * @since 1.7.0
 *
 * @param WC_Order $order Order object.
 * @param string   $key   Order property.
 *
 * @return mixed Value of order property.
 */
function wc_ap_get_order_prop($order, $key)
{
    switch ($key) {
        case 'order_currency':
            return is_callable(array($order, 'get_currency')) ? $order->get_currency() : $order->get_order_currency();
            break;
        default:
            $getter = array($order, 'get_' . $key);
            return is_callable($getter) ? call_user_func($getter) : $order->{ $key };
    }
}

/**
 * Return instance of WC_APPpago.
 *
 * @since 1.1.4
 *
 * @return WC_APPpago
 */
function wc_apppago()
{
    static $plugin;

    if (!isset($plugin)) {
        $plugin = new WC_APPpago();
    }

    return $plugin;
}

// Provides backward compatibility.
$GLOBALS['WC_Gateway_APPpago'] = wc_apppago();
