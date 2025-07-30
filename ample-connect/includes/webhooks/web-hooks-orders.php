<?php
if (!defined('ABSPATH')) {
    exit;
}

// Webhook for Order Details Update Notification from Ample
add_action('rest_api_init', function() {
    register_rest_route('webhooks/v1/', 'orders', array(
        'methods' => 'POST',
        'callback' => 'handle_orders_webhook',
        'permission_callback' => '__return_true'
    ));
});

// Function to handle product update webhook
function handle_orders_webhook(WP_REST_Request $request) {
    $data = $request->get_json_params();
    $jsonData = json_encode($data, JSON_PRETTY_PRINT);
    // ample_connect_log("order webhook received");
    // ample_connect_log($jsonData);

    if (!validate_webhook_request_params($data)) {
        return new WP_REST_Response(array("message" => "Missing Required Data!"), 200);
    }

    $verification = verify_webhook_request($data['webhook_signature']);
    if (is_wp_error($verification)) {
        return $verification;
    }

    if ($data["resource_type"] == "Order") {
        // Retrieve Client_id
        $client_id = $data['client_id'];
        if (empty($client_id)) {
            return new WP_REST_Response(array("message" => "No client detected!"), 200);
        }

        // Get User id of the client
        $user_id = get_user_id_by_client_id( $client_id );
        if (!$user_id) {
            return new WP_REST_Response(array("message" => "Invalid client!"), 200);
        }
        
        if ($data['change_type'] == 'update') {

            $changes = $data['changes'];
            if (empty($changes)) {
                return new WP_REST_Response(array("message" => "No changes detected!"), 200);
            }

            $order = $changes["order"];
            if (empty($order)) {
                return new WP_REST_Response(array("message" => "No changes detected!"), 200);
            }

            $orderItems = check_for_order_item($changes);

            if (count($orderItems) === 0) {
                // Changes are related to order itself
                // Order got refused on Ample
                if ($order['attributes']['status'] == "Order Refused") {
                    
                    // Clear the cart
                    if (clear_customer_cart( $user_id )) {
                        return new WP_REST_Response(array("message" => "Cart cleared!"), 200);
                    }
                }

            } else {
                // Changes are related to order-items
                foreach ($orderItems as $itemId => $itemData) {
                    $itemAttr = $itemData["attributes"];
                    if ($itemData["action"] == "create") {

                        $product_sku = 'sku-' . $itemAttr['product_id'];
                        $product_id = wc_get_product_id_by_sku( $product_sku );
                        $variation_sku = $itemAttr['product_id'] . '-' . $itemAttr['sku_id'];
                        $variation_id = wc_get_product_id_by_sku( $variation_sku );

                        if (!$product_id or !$variation_id) {
                            return new WP_REST_Response(array("message" => "Product couldn't found!"), 404);
                        }

                        $quantity = $itemAttr['quantity'];
                        $variation_data = [
                            'package-size' => $itemAttr['unit_grams'] . 'g'
                        ];

                        // $variation_slug = get_variation_attribute_slugs($variation_id);
                        // return new WP_REST_Response(array("message" => "Product details are as follows!", "products" => ["product_id" => $product_id, "variation_id" => $variation_id, "quantity" => $quantity, "variation_data" => $variation_data, "variation_slug" => $variation_slug]), 200);

                        $result = custom_add_product_to_cart($user_id, $itemAttr, $itemId, $product_id, $quantity, $variation_id, $variation_data);

                        // Code to debug
                        /*
                            , "data" => $result, "products" => ["product_id" => $product_id, "variation_id" => $variation_id, "quantity" => $quantity, "variation_data" => $variation_data]
                        */

                        if ($result['success']) {
                            return new WP_REST_Response(array("message" => "Product added to the cart!"), 200);
                        } else {
                            return new WP_REST_Response(array("message" => "Product couldn't add to the cart!"), 404);
                        }
                    } else if ($itemData["action"] == "update") {
                        $match = find_cart_item_by_custom_id( $itemId );

                        if ( $match ) {
                            $cart_item_key = $match['key'];
                            wp_set_current_user($user_id);
                            WC()->session = new WC_Session_Handler();
                            WC()->session->init();
                            WC()->customer = new WC_Customer($user->ID);
                            WC()->cart = new WC_Cart();
                            WC()->cart->get_cart(); // Load cart items

                            WC()->cart->set_quantity( $cart_item_key, $itemAttr['quantity'], true );

                            if ($itemAttr['total_cost'] != $itemAttr['total_cost_without_discounts']) {
                                $discounted_price_per_unit = ($itemAttr['total_cost'] / 100) / $itemAttr['quantity'];
                                $match['item']['data']->set_price( $discounted_price_per_unit );
                            }

                            WC()->cart->calculate_totals();
                            return new WP_REST_Response(array("message" => "Product updated to the cart!"), 200);

                        } else {
                            return new WP_REST_Response(array("message" => "No such product in the cart!"), 200);
                        }
                    }
                }
            }

        } else if ($data['change_type'] == 'create') {
            return new WP_REST_Response(array("message" => "Request received and processed!"), 200);
        }
    }

    return new WP_REST_Response('Webhook received but nothing to do!', 200);
}

// // Function to clear cart to the particular customer
// function clear_customer_cart( $customer_id ) {
//     if ( ! $customer_id ) {


//         return 0;
//     }

//     // Delete persistent cart stored in user meta
//     delete_user_meta( $customer_id, '_woocommerce_persistent_cart_1' );

//     // Delete session from WooCommerce session table
//     global $wpdb;

//     $table = $wpdb->prefix . 'woocommerce_sessions';

//     // Delete session based on user ID (stored as session_key)
//     $wpdb->delete(
//         $table,
//         array( 'session_key' => $customer_id ),
//         array( '%s' )
//     );
//     return 1;
// }

// Function to check if order item entry is there into the changes
function check_for_order_item ($changes) {
    $orderItemsData = [];

    foreach($changes as $key => $value) {
        if (preg_match('/^order_items\.(\d+)$/', $key, $matches)) {
            // $id = $matches[1]; // Extracted ID
            // $value['id'] = (int)$id; // Add it to the value
            $orderItemsData[$matches[1]] = $value;
            break; 
        }
    }

    return $orderItemsData;
}

// Function to find an cart item from cart-item-id
function find_cart_item_by_cart_item_id( $item_id ) {
    foreach ( WC()->cart->get_cart() as $key => $item ) {
        if ( isset( $item['cart_item_id'] ) && $item['cart_item_id'] === $item_id ) {
            return [ 'key' => $key, 'item' => $item ];
        }
    }
    return false;
}

// Function to add an item to the cart
function custom_add_product_to_cart($user_id, $itemAttr, $itemId, $product_id, $quantity = 1, $variation_id = 0, $variation_data = array() ) {    
    // 1. Switch to the user's session
    wp_set_current_user($user_id);
    WC()->session = new WC_Session_Handler();
    WC()->session->init();
    WC()->customer = new WC_Customer($user->ID);
    WC()->cart = new WC_Cart();
    WC()->cart->get_cart(); // Load cart items

    // // 2. Clear cart if not empty
    // if (!WC()->cart->is_empty()) {
    //     WC()->cart->empty_cart();
    // }

    // 3. Add product to cart
    $cart_item_key = WC()->cart->add_to_cart($product_id, $quantity, $variation_id, $variation_data, ['cart_item_id' => $itemId]);
    if ($itemAttr['total_cost'] != $itemAttr['total_cost_without_discounts']) {
        $discounted_price_per_unit = ($itemAttr['total_cost']/100) / $itemAttr['quantity'];
        WC()->cart->cart_contents[ $cart_item_key ]['data']->set_price( $discounted_price_per_unit );
    }
    WC()->cart->calculate_totals();
    // 4. Save the cart
    WC()->session->save_data();

    if ( !$cart_item_key ) {
        $notices      = wc_get_notices( 'error' );
        $error_msgs   = [];

        if ( ! empty( $notices ) ) {
            foreach ( $notices as $notice ) {
                $message = $notice['notice'];
                $error_msgs[] = $message;
            }
        } else {
            $fallback_msg = 'add_to_cart failed but no WooCommerce error notices found.';
            $error_msgs[] = $fallback_msg;
        }

        return [
            'success' => false,
            'message' => 'Failed to add product to cart',
            'errors'  => $error_msgs
        ];
    } else {
        return [
            'success'   => true,
            'message'   => 'Product added to cart',
            'cart_key'  => $cart_item_key
        ];
    }
}

// Function to update an item into the cart
/**
 * Update a WooCommerce cart item (quantity, price, etc.)
 *
 * @param string $cart_item_key Cart item key to identify the item.
 * @param int|null $new_quantity New quantity (null to leave unchanged).
 * @param float|null $custom_price New price per item (null to keep original price).
 * @return bool True on success, false on failure.
 */
function update_cart_item_by_key( $cart_item_key, $new_quantity = null, $custom_price = null ) {
    if ( ! WC()->cart ) {
        return false;
    }

    $cart = WC()->cart->get_cart();

    if ( ! isset( $cart[ $cart_item_key ] ) ) {
        error_log( "Cart item not found for key: $cart_item_key" );
        return false;
    }

    // Update quantity
    if ( $new_quantity !== null ) {
        WC()->cart->set_quantity( $cart_item_key, $new_quantity, true );
    }

    // Update custom price
    if ( $custom_price !== null ) {
        WC()->cart->cart_contents[ $cart_item_key ]['data']->set_price( $custom_price );
    }

    // Recalculate totals
    WC()->cart->calculate_totals();

    return true;
}


/**
 * Get required attribute slugs for a variation in WooCommerce.
 *
 * @param int $variation_id The variation product ID.
 * @return array|null Associative array of required attribute slugs and values, or null on failure.
 */
function get_variation_attribute_slugs( $variation_id ) {
    $variation = wc_get_product( $variation_id );

    if ( ! $variation || ! $variation->is_type( 'variation' ) ) {
        error_log("Invalid variation ID: $variation_id");
        return null;
    }

    $attributes = $variation->get_attributes();
    $required_attributes = [];

    foreach ( $attributes as $key => $value ) {
        // Ensure correct formatting (some values may be term IDs — convert to names)
        if ( taxonomy_exists( str_replace( 'attribute_', '', $key ) ) && is_numeric( $value ) ) {
            $term = get_term( $value );
            if ( ! is_wp_error( $term ) && $term ) {
                $value = $term->slug;
            }
        }

        $required_attributes[ $key ] = $value;
    }

    return $required_attributes;
}







