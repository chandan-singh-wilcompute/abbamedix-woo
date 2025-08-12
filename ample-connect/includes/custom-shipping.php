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

                $this->init();
            }

            function init() {

                $this->init_form_fields();
                $this->init_settings();
                add_action('woocommerce_update_options_shipping_' . $this->id, array($this, 'process_admin_options'));
            }

            // public function calculate_shipping($package = array()) {

            //     $cache_key = 'custom_shipping_rates_user_' . $user_id;
            //     $shipping_options = get_transient($cache_key);

            //     if (!$shipping_options) {
            //         $user_id = get_current_user_id();
            //         // Get the client id of the customer
            //         $client_id = get_user_meta($user_id, 'client_id', true);

            //         $order = get_order_id_from_api();
            //         $order_id = $order['id'];

            //         $url = AMPLE_CONNECT_PORTAL_URL . "/orders/{$order_id}/shipping_rates"; 
            //         ample_connect_log("shipping url");
            //         ample_connect_log($url);
            //         $api_url = add_query_arg (
            //             array( 'client_id' => $client_id ),
            //             $url
            //         );

            //         $data = ample_request($api_url);
            //         $shipping_options = array_merge(...array_values($data));

            //         ample_connect_log("shipping options");
            //         ample_connect_log($shipping_options);
                    
            //         // Cache for 2 minutes
            //         set_transient($cache_key, $shipping_options, 120);
            //     }
                
            //     $this->add_rate(array(
            //         'id'    => 'select_shipping_placeholder',
            //         'label' => __('-- Select Shipping Method --', 'woocommerce'),
            //         'cost'  => 0,
            //     ));
                
            //     if (!empty($shipping_options)) {
                    
            //         foreach ($shipping_options as $option) {
            //             $rate = array(
            //                 'id'    => $option['id'],
            //                 'label' => preg_replace('/(?<!^)([A-Z])/', ' $1', $option['service']),
            //                 'cost'  => (float)$option['rate'],
            //             );
            //             $this->add_rate($rate);
            //         }
            //     }
            // }

            // public function calculate_shipping($package = array()) {
            //     $user_id = get_current_user_id();
            //     $client_id = get_user_meta($user_id, 'client_id', true);

            //     $order = get_order_id_from_api();
            //     $order_id = $order['id'];

            //     $url = AMPLE_CONNECT_PORTAL_URL . "/orders/{$order_id}/shipping_rates"; 
            //     $api_url = add_query_arg(array('client_id' => $client_id), $url);

            //     $data = ample_request($api_url);
            //     $shipping_options = array_merge(...array_values($data));

            //     ample_connect_log("Fetched shipping options from API:");
            //     ample_connect_log($shipping_options);

            //     // Add placeholder first
            //     $this->add_rate(array(
            //         'id'    => 'select_shipping_placeholder',
            //         'label' => __('-- Select Shipping Method --', 'woocommerce'),
            //         'cost'  => 0,
            //     ));

            //     if (!empty($shipping_options)) {
            //         foreach ($shipping_options as $option) {
            //             $rate = array(
            //                 'id'    => $option['id'],
            //                 'label' => preg_replace('/(?<!^)([A-Z])/', ' $1', $option['service']),
            //                 'cost'  => (float)$option['rate'],
            //             );
            //             $this->add_rate($rate);
            //         }
            //     }
            // }

            public function calculate_shipping($package = array()) {

                // get_shipping_rates_and_store_in_session();
                
                if (!Ample_Session_Cache::has('custom_shipping_rates')) {
                    get_shipping_rates_and_store_in_session();
                }

                $shipping_options = Ample_Session_Cache::get('custom_shipping_rates');
                //$shipping_options = get_shipping_rates_and_store_in_session();

                // Add placeholder
                $this->add_rate([
                    'id'    => 'select_shipping_placeholder',
                    'label' => __('-- Select Shipping Method --', 'woocommerce'),
                    'cost'  => 0,
                ]);

                // Add real rates
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