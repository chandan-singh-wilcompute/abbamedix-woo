<?php
/**
 * Order Tracking Admin
 * 
 * This file contains functions to display and manage tracking information in the admin order view.
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Add tracking information meta box to the order edit screen
 */
add_action('add_meta_boxes', 'ample_connect_add_tracking_meta_box');

/**
 * Register the tracking meta box
 */
function ample_connect_add_tracking_meta_box() {
    add_meta_box(
        'ample_connect_tracking_info',
        __('Tracking Information', 'ample-connect-plugin'),
        'ample_connect_tracking_meta_box_content',
        'shop_order',
        'side',
        'default'
    );
}

/**
 * Display the tracking meta box content
 * 
 * @param WP_Post $post Post object
 */
function ample_connect_tracking_meta_box_content($post) {
    $order = wc_get_order($post->ID);
    $tracking_number = $order->get_meta('_tracking_number');
    $carrier = $order->get_meta('_shipping_carrier');
    $tracking_url = $order->get_meta('_tracking_url');
    
    wp_nonce_field('ample_connect_save_tracking_data', 'ample_connect_tracking_nonce');
    
    ?>
    <p>
        <label for="ample_connect_carrier"><?php _e('Carrier', 'ample-connect-plugin'); ?>:</label><br>
        <input type="text" id="ample_connect_carrier" name="ample_connect_carrier" value="<?php echo esc_attr($carrier); ?>" style="width: 100%;">
    </p>
    <p>
        <label for="ample_connect_tracking_number"><?php _e('Tracking Number', 'ample-connect-plugin'); ?>:</label><br>
        <input type="text" id="ample_connect_tracking_number" name="ample_connect_tracking_number" value="<?php echo esc_attr($tracking_number); ?>" style="width: 100%;">
    </p>
    <p>
        <label for="ample_connect_tracking_url"><?php _e('Tracking URL', 'ample-connect-plugin'); ?>:</label><br>
        <input type="url" id="ample_connect_tracking_url" name="ample_connect_tracking_url" value="<?php echo esc_url($tracking_url); ?>" style="width: 100%;">
    </p>
    <p>
        <button type="button" class="button" id="ample_connect_mark_shipped"><?php _e('Mark as Shipped', 'ample-connect-plugin'); ?></button>
    </p>
    <script>
        jQuery(document).ready(function($) {
            $('#ample_connect_mark_shipped').on('click', function() {
                // Set order status to shipped
                $('#order_status').val('wc-shipped').trigger('change');
            });
        });
    </script>
    <?php
}

/**
 * Save tracking information when the order is saved
 */
add_action('woocommerce_process_shop_order_meta', 'ample_connect_save_tracking_data', 10, 2);

/**
 * Save tracking data from the meta box
 * 
 * @param int $order_id Order ID
 * @param WP_Post $post Post object
 */
function ample_connect_save_tracking_data($order_id, $post) {
    // Check if our nonce is set
    if (!isset($_POST['ample_connect_tracking_nonce'])) {
        return;
    }
    
    // Verify the nonce
    if (!wp_verify_nonce($_POST['ample_connect_tracking_nonce'], 'ample_connect_save_tracking_data')) {
        return;
    }
    
    // Get the order object
    $order = wc_get_order($order_id);
    
    // Save tracking data
    if (isset($_POST['ample_connect_carrier'])) {
        $order->update_meta_data('_shipping_carrier', sanitize_text_field($_POST['ample_connect_carrier']));
    }
    
    if (isset($_POST['ample_connect_tracking_number'])) {
        $order->update_meta_data('_tracking_number', sanitize_text_field($_POST['ample_connect_tracking_number']));
    }
    
    if (isset($_POST['ample_connect_tracking_url'])) {
        $order->update_meta_data('_tracking_url', esc_url_raw($_POST['ample_connect_tracking_url']));
    }
    
    // Save the order
    $order->save();
    
    // If the order status is being changed to shipped, sync with Ample
    if (isset($_POST['order_status']) && $_POST['order_status'] === 'wc-shipped') {
        $ample_order_id = $order->get_meta('_external_order_number');
        if ($ample_order_id) {
            // Sync tracking info with Ample
            $tracking_data = array(
                'tracking_number' => $order->get_meta('_tracking_number'),
                'carrier' => $order->get_meta('_shipping_carrier'),
                'tracking_url' => $order->get_meta('_tracking_url')
            );
            
            update_ample_order_tracking($ample_order_id, $tracking_data);
        }
    }
}

/**
 * Update tracking information in Ample
 * 
 * @param int $ample_order_id Ample order ID
 * @param array $tracking_data Tracking data
 * @return array|WP_Error Response from API or error
 */
function update_ample_order_tracking($ample_order_id, $tracking_data) {
    $url = AMPLE_CONNECT_ORDERS_URL . '/' . $ample_order_id . '/tracking';
    
    return ample_request($url, 'PUT', $tracking_data);
}

/**
 * Add a column for tracking information to the orders list
 */
add_filter('manage_edit-shop_order_columns', 'ample_connect_add_tracking_column', 20);

/**
 * Add tracking column to orders list
 * 
 * @param array $columns Existing columns
 * @return array Modified columns
 */
function ample_connect_add_tracking_column($columns) {
    $new_columns = array();
    
    foreach ($columns as $column_name => $column_info) {
        $new_columns[$column_name] = $column_info;
        
        if ($column_name === 'order_status') {
            $new_columns['tracking_info'] = __('Tracking', 'ample-connect-plugin');
        }
    }
    
    return $new_columns;
}

/**
 * Populate the tracking column in the orders list
 */
add_action('manage_shop_order_posts_custom_column', 'ample_connect_populate_tracking_column', 10, 2);

/**
 * Display tracking information in the orders list
 * 
 * @param string $column Column name
 * @param int $post_id Post ID
 */
function ample_connect_populate_tracking_column($column, $post_id) {
    if ($column === 'tracking_info') {
        $order = wc_get_order($post_id);
        $tracking_number = $order->get_meta('_tracking_number');
        
        if (!empty($tracking_number)) {
            $tracking_url = $order->get_meta('_tracking_url');
            $carrier = $order->get_meta('_shipping_carrier');
            
            if (!empty($tracking_url)) {
                echo '<a href="' . esc_url($tracking_url) . '" target="_blank">' . esc_html($tracking_number) . '</a>';
            } else {
                echo esc_html($tracking_number);
            }
            
            if (!empty($carrier)) {
                echo '<br><small>' . esc_html($carrier) . '</small>';
            }
        } else {
            echo '—';
        }
    }
}
