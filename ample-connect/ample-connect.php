<?php
/**
 * Plugin Name: Ample Connect
 * Text Domain: ample-connect-plugin
 * Plugin URI: https://www.groweriq.ca/
 * Description: Ample Connect is a plugin that syncs with Ample medical portal data.
 * Version: 1.1.0
 * Author: Chandan Singh
 * Author URI: https://www.groweriq.ca/
 */

// Prevent direct access to the file
if (!defined('ABSPATH')) {
    exit;
}

// Custom Log Function
function my_debug_log($message) {
    $file = WP_CONTENT_DIR . '/my-debug.log'; // Path to the custom log file
    $timestamp = date('Y-m-d H:i:s'); // Optional: Add a timestamp

    if (is_array($message) || is_object($message)) {
        $formattedMessage = print_r($message, true);
    } else {
        $formattedMessage = $message;
    }

    // Combine timestamp and message
    $log_message = "[$timestamp] " . $formattedMessage . PHP_EOL;

    // Append the message to the log file
    file_put_contents($file, $log_message, FILE_APPEND);
}

// Define global configuration settings
define('AMPLE_CONNECT_API_BASE_URL', 'https://abbatestbox.sandbox.onample.com/api');
define('AMPLE_CONNECT_LOGIN_URL', AMPLE_CONNECT_API_BASE_URL . '/v1/users/login');
define('AMPLE_CONNECT_PORTAL_URL', AMPLE_CONNECT_API_BASE_URL . '/v1/portal');
define('AMPLE_CONNECT_CLIENTS_URL', AMPLE_CONNECT_API_BASE_URL . '/v2/clients');
define('AMPLE_CONNECT_ORDERS_URL', AMPLE_CONNECT_API_BASE_URL . '/v2/orders');
define('AMPLE_CONNECT_WOO_PRODUCT_URL', AMPLE_CONNECT_API_BASE_URL . '/integrations/woocommerce/products/');
define('AMPLE_CONNECT_WOO_CLIENT_URL', AMPLE_CONNECT_API_BASE_URL . '/integrations/woocommerce/clients/');
define('AMPLE_CONNECT_WOO_ORDER_URL', AMPLE_CONNECT_API_BASE_URL . '/integrations/woocommerce/orders/');


// Include the main plugin class. 
require_once plugin_dir_path(__FILE__) . 'includes/utility.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-ample-connect.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-ample-token-manager.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-client-information.php';
require_once plugin_dir_path(__FILE__) . 'includes/custom-registration.php';  
require_once plugin_dir_path(__FILE__) . 'includes/place-order-api.php';
require_once plugin_dir_path(__FILE__) . 'includes/webhooks/web-hooks-utils.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-order-process.php';

// Include functions.php to load custom functions
add_action('plugins_loaded', 'ample_connect_include_functions');
function ample_connect_include_functions() {
    require_once plugin_dir_path(__FILE__) . 'includes/functions.php';
    require_once plugin_dir_path(__FILE__) . 'includes/product-restrictions.php';
    require_once plugin_dir_path(__FILE__) . 'includes/webhooks/web-hooks-products.php';
    require_once plugin_dir_path(__FILE__) . 'includes/webhooks/web-hooks-clients.php';
    require_once plugin_dir_path(__FILE__) . 'includes/custom-shipping.php';
    require_once plugin_dir_path(__FILE__) . 'includes/wc_classes/class-wc-orders.php';
    require_once plugin_dir_path(__FILE__) . 'includes/wc-order-hooks.php';
    require_once plugin_dir_path(__FILE__) . 'includes/order-tracking-display.php';
    require_once plugin_dir_path(__FILE__) . 'includes/admin/order-tracking-admin.php';
    require_once plugin_dir_path(__FILE__) . 'includes/webhooks/web-hooks-orders.php';
    //require_once plugin_dir_path(__FILE__) . 'includes/custom-payment.php';
}

// Run the plugin
function run_ample_connect() {
    $plugin = new Ample_Connect();
    $plugin->run();
}
run_ample_connect();
