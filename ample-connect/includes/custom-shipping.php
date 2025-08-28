<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}
require_once plugin_dir_path(__FILE__) . '/customer-functions.php';

// Register the custom shipping method
function custom_shipping_api_init() {
    if (!class_exists('WC_Custom_Shipping_Method')) {
        class WC_Custom_Shipping_Method extends WC_Shipping_Method {
            
            public function __construct() {
                $this->id                 = 'custom_shipping_api';
                $this->method_title       = __('Custom Shipping API', 'woocommerce');
                $this->method_description = __('Retrieve shipping rates from an external API', 'woocommerce');
                $this->supports = array(
                    'no-shipping-cache' // 🚀 disables caching
                );
                $this->init();
            }

            function init() {

                $this->init_form_fields();
                $this->init_settings();
                add_action('woocommerce_update_options_shipping_' . $this->id, array($this, 'process_admin_options'));
            }

            public function calculate_shipping($package = array()) {
                if (is_checkout()) {
                    get_shipping_rates_and_store_in_session();
                }
                
                $shipping_options = [];

                // if (!Ample_Session_Cache::has('custom_shipping_rates')) {
                //     get_shipping_rates_and_store_in_session();
                // }

                $shipping_options = Ample_Session_Cache::get('custom_shipping_rates');
                // $shipping_options = get_shipping_rates_and_store_in_session();

                // Add placeholder
                $this->add_rate([
                    'id'    => 'select_shipping_placeholder',
                    'label' => __('-- Select Shipping Method --', 'woocommerce'),
                    'cost'  => 0,
                ]);

                // Add real rates
                if ($shipping_options) {
                    foreach ($shipping_options as $option) {
                        $this->add_rate([
                            'id'    => $option['id'],
                            'label' => preg_replace('/(?<!^)([A-Z])/', ' $1', $option['service']),
                            'cost'  => (float)$option['rate'],
                        ]);
                    }
                }
            }
        }
    }
}
add_action('woocommerce_shipping_init', 'custom_shipping_api_init');

function add_custom_shipping_method($methods) {
    $methods['custom_shipping_api'] = 'WC_Custom_Shipping_Method';
    return $methods;
}
add_filter('woocommerce_shipping_methods', 'add_custom_shipping_method');


// Register custom order status
function register_shipped_order_status() {
    register_post_status('wc-shipped', array(
        'label'                     => _x('Shipped', 'Order status', 'ample-connect-plugin'),
        'public'                    => true,
        'show_in_admin_status_list'  => true,
        'show_in_admin_all_list'     => true,
        'label_count'                => _n_noop('Shipped (%s)', 'Shipped (%s)', 'ample-connect-plugin')
    ));
}
add_action('init', 'register_shipped_order_status');

// Add to WooCommerce Order Statuses
function add_shipped_to_order_statuses($order_statuses) {
    $order_statuses['wc-shipped'] = _x('Shipped', 'Order status', 'ample-connect-plugin');
    return $order_statuses;
}
add_filter('wc_order_statuses', 'add_shipped_to_order_statuses');