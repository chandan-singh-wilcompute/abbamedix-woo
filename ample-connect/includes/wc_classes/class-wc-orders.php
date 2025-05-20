<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}


function update_order_shipping_details($order_id, $tracking_number, $carrier, $tracking_url) {
    if (!$order_id) {
        return;
    }

    // Get the order object
    $order = wc_get_order($order_id);

    if (!$order) {
        return;
    }

    // Update Shipping Details
    $order->update_meta_data('_tracking_number', $tracking_number);
    $order->update_meta_data('_shipping_carrier', $carrier);
    $order->update_meta_data('_tracking_url', $tracking_url);

    // Save changes
    $order->save();

    // Change order status to "shipped" (Custom status)
    $order->update_status('wc-shipped', __('Order has been shipped.', 'ample-connect-plugin'));
}

// Function to get order id from ample order id
function get_order_id_by_ample_order_id($ample_order_id) {
    $args = array(
        'post_type'   => 'shop_order',
        'meta_query'  => array(
            array(
                'key'     => '_external_order_number',
                'value'   => $ample_order_id,
                'compare' => '='
            )
        )
    );

    $orders = get_posts($args);
    return !empty($orders) ? $orders[0]->ID : false;
}


// Function to cancel an order
function cancel_and_refund_order($order_id) {

    $order = wc_get_order($order_id);

    if ($order && $order->get_status() !== 'cancelled') {
        // Refund the order if paid
        if ($order->get_total() > 0) {
            $refund = wc_create_refund(array(
                'amount'     => $order->get_total(),
                'reason'     => 'Order cancelled by admin.',
                'order_id'   => $order_id,
            ));

            if (is_wp_error($refund)) {
                return 'Refund failed: ' . $refund->get_error_message();
            }
        }

        // Cancel the order
        $order->update_status('cancelled', __('Order has been cancelled and refunded.', 'my-custom-woocommerce-plugin'));

        return true;
    }

    return false;
}


