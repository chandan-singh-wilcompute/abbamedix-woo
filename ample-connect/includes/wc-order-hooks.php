<?php
/**
 * WooCommerce Order Hooks
 * 
 * This file contains hooks to sync WooCommerce order status changes with Ample.
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Hook into WooCommerce order status changes
 */
add_action('woocommerce_order_status_changed', 'ample_connect_order_status_changed', 10, 4);

/**
 * Handle order status changes and sync with Ample
 * 
 * @param int $order_id The WooCommerce order ID
 * @param string $status_from Previous status
 * @param string $status_to New status
 * @param WC_Order $order Order object
 */
function ample_connect_order_status_changed($order_id, $status_from, $status_to, $order) {
    // Get the Ample order ID from the WooCommerce order
    $ample_order_id = get_post_meta($order_id, '_external_order_number', true);
    
    if (!$ample_order_id) {
        // No Ample order ID found, so this order might not be linked to Ample
        return;
    }
    
    // Map WooCommerce status to Ample status
    $ample_status = map_wc_status_to_ample($status_to);
    
    if ($ample_status) {
        // Send status update to Ample API
        $result = update_ample_order_status($ample_order_id, $ample_status);
        
        if (is_wp_error($result)) {
            // Log error
            error_log('Failed to update Ample order status: ' . $result->get_error_message());
        } else {
            // Add note to the order
            $order->add_order_note(sprintf(__('Order status synchronized with Ample: %s', 'ample-connect-plugin'), $ample_status));
        }
    }
}

/**
 * Map WooCommerce order status to Ample status
 * 
 * @param string $wc_status WooCommerce order status
 * @return string|false Ample status or false if no mapping exists
 */
function map_wc_status_to_ample($wc_status) {
    $status_map = array(
        'pending'    => 'pending',
        'processing' => 'processing',
        'on-hold'    => 'on_hold',
        'completed'  => 'completed',
        'cancelled'  => 'cancelled',
        'refunded'   => 'refunded',
        'failed'     => 'failed',
        'shipped'    => 'shipped',
    );
    
    // Remove 'wc-' prefix if present
    $wc_status = str_replace('wc-', '', $wc_status);
    
    return isset($status_map[$wc_status]) ? $status_map[$wc_status] : false;
}

/**
 * Update order status in Ample
 * 
 * @param int $ample_order_id Ample order ID
 * @param string $status New status
 * @return array|WP_Error Response from API or error
 */
function update_ample_order_status($ample_order_id, $status) {
    $url = AMPLE_CONNECT_ORDERS_URL . '/' . $ample_order_id . '/status';
    
    $body = array(
        'status' => $status
    );
    
    return ample_request($url, 'PUT', $body);
}
