<?php
/**
 * Order Tracking Display
 * 
 * This file contains functions to display tracking information on the order details page.
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Add tracking information to the order details page
 */
add_action('woocommerce_order_details_after_order_table', 'ample_connect_display_tracking_info', 10, 1);

/**
 * Display tracking information on the order details page
 * 
 * @param WC_Order $order Order object
 */
function ample_connect_display_tracking_info($order) {
    // Only show for shipped orders
    if ($order->get_status() !== 'shipped') {
        return;
    }
    
    $tracking_number = $order->get_meta('_tracking_number');
    $carrier = $order->get_meta('_shipping_carrier');
    $tracking_url = $order->get_meta('_tracking_url');
    
    if (!empty($tracking_number)) {
        echo '<h2>' . __('Tracking Information', 'ample-connect-plugin') . '</h2>';
        echo '<table class="woocommerce-table shop_table tracking_info">';
        echo '<tbody>';
        
        // Carrier
        if (!empty($carrier)) {
            echo '<tr>';
            echo '<th>' . __('Carrier', 'ample-connect-plugin') . '</th>';
            echo '<td>' . esc_html($carrier) . '</td>';
            echo '</tr>';
        }
        
        // Tracking number
        echo '<tr>';
        echo '<th>' . __('Tracking Number', 'ample-connect-plugin') . '</th>';
        echo '<td>' . esc_html($tracking_number) . '</td>';
        echo '</tr>';
        
        // Tracking link
        if (!empty($tracking_url)) {
            echo '<tr>';
            echo '<th>' . __('Track Package', 'ample-connect-plugin') . '</th>';
            echo '<td><a href="' . esc_url($tracking_url) . '" target="_blank">' . __('Track your package', 'ample-connect-plugin') . '</a></td>';
            echo '</tr>';
        }
        
        echo '</tbody>';
        echo '</table>';
    }
}

/**
 * Add tracking information to order emails
 */
add_action('woocommerce_email_after_order_table', 'ample_connect_email_tracking_info', 10, 4);

/**
 * Display tracking information in order emails
 * 
 * @param WC_Order $order Order object
 * @param bool $sent_to_admin Whether the email is being sent to admin
 * @param bool $plain_text Whether the email is plain text
 * @param WC_Email $email Email object
 */
function ample_connect_email_tracking_info($order, $sent_to_admin, $plain_text, $email) {
    // Only show for shipped orders and customer emails
    if ($order->get_status() !== 'shipped' || $sent_to_admin) {
        return;
    }
    
    $tracking_number = $order->get_meta('_tracking_number');
    $carrier = $order->get_meta('_shipping_carrier');
    $tracking_url = $order->get_meta('_tracking_url');
    
    if (!empty($tracking_number)) {
        if ($plain_text) {
            echo "\n\n" . __('Tracking Information', 'ample-connect-plugin') . "\n";
            if (!empty($carrier)) {
                echo __('Carrier', 'ample-connect-plugin') . ': ' . $carrier . "\n";
            }
            echo __('Tracking Number', 'ample-connect-plugin') . ': ' . $tracking_number . "\n";
            if (!empty($tracking_url)) {
                echo __('Track your package', 'ample-connect-plugin') . ': ' . $tracking_url . "\n";
            }
        } else {
            echo '<h2>' . __('Tracking Information', 'ample-connect-plugin') . '</h2>';
            echo '<table class="td" cellspacing="0" cellpadding="6" style="width: 100%; margin-bottom: 20px;">';
            echo '<tbody>';
            
            // Carrier
            if (!empty($carrier)) {
                echo '<tr>';
                echo '<th style="text-align: left; border-bottom: 1px solid #e5e5e5;">' . __('Carrier', 'ample-connect-plugin') . '</th>';
                echo '<td style="text-align: left; border-bottom: 1px solid #e5e5e5;">' . esc_html($carrier) . '</td>';
                echo '</tr>';
            }
            
            // Tracking number
            echo '<tr>';
            echo '<th style="text-align: left; border-bottom: 1px solid #e5e5e5;">' . __('Tracking Number', 'ample-connect-plugin') . '</th>';
            echo '<td style="text-align: left; border-bottom: 1px solid #e5e5e5;">' . esc_html($tracking_number) . '</td>';
            echo '</tr>';
            
            // Tracking link
            if (!empty($tracking_url)) {
                echo '<tr>';
                echo '<th style="text-align: left; border-bottom: 1px solid #e5e5e5;">' . __('Track Package', 'ample-connect-plugin') . '</th>';
                echo '<td style="text-align: left; border-bottom: 1px solid #e5e5e5;"><a href="' . esc_url($tracking_url) . '" target="_blank">' . __('Track your package', 'ample-connect-plugin') . '</a></td>';
                echo '</tr>';
            }
            
            echo '</tbody>';
            echo '</table>';
        }
    }
}
