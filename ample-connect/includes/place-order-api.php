<?php
if (!defined('ABSPATH')) {
    exit;
}
require_once plugin_dir_path(__FILE__) . '/customer-functions.php';

add_filter('woocommerce_add_to_cart_validation', 'custom_add_to_cart_validation', 10, 5);
function custom_add_to_cart_validation($passed, $product_id, $quantity, $variation_id, $variations) {
    
    if (is_user_logged_in()) {
        // $allowed_skus = get_purchasable_products();
        $allowed_skus = Ample_Session_Cache::get('purchasable_products');
        if (!empty($allowed_skus)) {
            $allowed_ids = get_product_ids_by_skus($allowed_skus);
            if (!in_array($product_id, $allowed_ids)) {
                wc_add_notice('This product cannot be added to the cart.', 'error');
                return false;
            }
        }
    } else {
        wc_add_notice('You need to be logged in to add this product to the cart.', 'error');
        return false;
    }

    if ($variation_id) {
        $variation = wc_get_product($variation_id);
        $attribute_package_size = ((float)$variation->get_attribute('package-size')) * $quantity;
    } else {
        $product = wc_get_product($product_id);
        $attribute_package_size = ((float)$product->get_attribute('package-size'))  * $quantity;
    }

    $total_package_size = 0;
    foreach (WC()->cart->get_cart() as $cart_item) {
        $product = wc_get_product($cart_item['variation_id'] ?: $cart_item['product_id']);
        $package_size = $product->get_attribute('package-size');
        $quantity = $cart_item['quantity'];
        $total_package_size += floatval($package_size) * $quantity;
    }
    $total_package_size += $attribute_package_size;
    // $order = get_order_id_from_api();
    $order_id = Ample_Session_Cache::get('order_id');

    if ($order_id) {
        // $get_available_to_order = Client_Information::get_available_to_order();
        $get_available_to_order  = Ample_Session_Cache::get('available_to_order');
        if ($get_available_to_order < $total_package_size) {
            wc_add_notice('Insufficient available quantity to order. Only ' . $get_available_to_order . ' grams are available to order.', 'error');
            return false;
        }
    } else {
        wc_add_notice('Failed to get order ID', 'error');
        return false;
    }

    return $passed;
}

add_action('woocommerce_add_to_cart', 'custom_add_to_order', 10, 6);
function custom_add_to_order($cart_item_key, $product_id, $quantity, $variation_id, $variation, $cart_item_data) {
    // Validate session is available
    if (!Ample_Session_Cache::is_session_available()) {
        ample_connect_log("Session not available during add to cart");
        return;
    }

    if ($variation_id) {
        $variation = wc_get_product($variation_id);
        $sku = $variation->get_sku();
        $sku_array = explode("-", $sku);
        $sku_id = $sku_array[1];
    } else {
        $product = wc_get_product($product_id);
        $sku_id = $product->get_sku();
    }

    $order_id = Ample_Session_Cache::get('order_id');
    
    if ($order_id) {
        try {
            $response = add_to_order($order_id, $sku_id, $quantity);
            if (is_wp_error($response)) {
                error_log('API call failed: ' . $response->get_error_message());
                // Don't remove from cart on API failure
                return;
            } else {
                // if (!empty($response['order_items'])) {
                //     foreach($response['order_items'] as $order_item) {
                //         if ($order_item['sku_id'] == $sku_id) {
                //             if(!empty($order_item['discounts'])) {
                //                 WC()->cart->cart_contents[$cart_item_key]['api_discounts'] = $order_item['discounts'];
                //             } else {
                //                 if ( isset( WC()->cart->cart_contents[$cart_item_key]['api_discounts'] ) ) {
                //                     unset( WC()->cart->cart_contents[$cart_item_key]['api_discounts'] );
                //                 }
                //             }
                //         }
                //     }
                // }
                // if (!empty($response['order_items'][0]['discounts'])) {
                //     $discounts = $response['order_items'][0]['discounts'];
                //     if (!empty($discounts)) {
                //         WC()->cart->cart_contents[ $cart_item_key ]['discount_percentage'] = $discounts[0];
                //     }
                // }
                // error_log('API call succeeded: ' . wp_remote_retrieve_body($response));
            }
        } catch (Exception $e) {
            error_log('Exception during add to order: ' . $e->getMessage());
            // Don't remove from cart on exception
        }
    } else {
        error_log('No order_id available during add to cart');
    }
}

// add_action('woocommerce_remove_cart_item', 'ample_cart_updated', 10, 2);
// function ample_cart_updated($cart_item_key, $cart) {
//     $line_item = $cart->removed_cart_contents[$cart_item_key];
//     $product = wc_get_product($line_item['variation_id'] ?: $line_item['product_id']);
//     $sku_id = explode("-", $product->get_sku())[1];
    
//     // $order = get_order_id_from_api();
//     $order_id = Ample_Session_Cache::get('order_id');
//     $order_items = Ample_Session_Cache::get('order_items');
//     if($order_id) {
//         $order_item_id = null;
//         foreach ($order_items as $item) {
//             if ($item['sku_id'] == $sku_id) {
//                 $order_item_id = $item['id'];
//                 break;
//             }
//         }

//         if($order_item_id){
//             $response = remove_from_order($order_id, $order_item_id);
//             if (is_wp_error($response)) {
//                 error_log('API call failed: ' . $response->get_error_message());
//             } else {
//                 error_log('API call succeeded: ' . wp_remote_retrieve_body($response));
//             }
//         }
//     }

// }

add_action('woocommerce_cart_item_removed', 'ample_cart_updated', 10, 2);
function ample_cart_updated($cart_item_key, $cart) {
    $line_item = $cart->removed_cart_contents[$cart_item_key] ?? null;
    if (!$line_item) {
        error_log("Cart item not found in removed_cart_contents for key: $cart_item_key");
        return;
    }

    $product = wc_get_product($line_item['variation_id'] ?: $line_item['product_id']);
    if (!$product) {
        error_log("Product not found for cart item key: $cart_item_key");
        return;
    }

    $sku_id = explode("-", $product->get_sku())[1] ?? null;
    if (!$sku_id) {
        error_log("No SKU ID extracted for product ID: " . $product->get_id());
        return;
    }

    $order_id   = Ample_Session_Cache::get('order_id');
    $order_items = Ample_Session_Cache::get('order_items');

    if ($order_id && $order_items) {
        $order_item_id = null;
        foreach ($order_items as $item) {
            if ($item['sku_id'] == $sku_id) {
                $order_item_id = $item['id'];
                break;
            }
        }

        if ($order_item_id) {
            $response = remove_from_order($order_id, $order_item_id);
            if (is_wp_error($response)) {
                error_log('API call failed: ' . $response->get_error_message());
            } else {
                error_log('API call succeeded: ' . wp_remote_retrieve_body($response));
            }
        } else {
            error_log("No matching order item found for SKU: $sku_id");
        }
    }
}



add_action('woocommerce_before_cart', 'calculate_total_package_size');
function calculate_total_package_size() {
    $total_package_size = 0;
    // $get_available_to_order = Client_Information::get_available_to_order();
    $get_available_to_order  = Ample_Session_Cache::get('available_to_order');
    // Loop through cart items
    foreach (WC()->cart->get_cart() as $cart_item) {
        $product = wc_get_product($cart_item['variation_id'] ?: $cart_item['product_id']);
        $package_size = $product->get_attribute('package-size');
        $quantity = $cart_item['quantity'];
        $total_package_size += floatval($package_size) * $quantity;
    }

    // Output the total package size
    echo '<div id="total-package-size" style="display: none;">' . $total_package_size . '</div>';
    echo '<div id="available-to-order" style="display: none;">' . $get_available_to_order . '</div>';   
}


// Hook into cart quantity changes - try multiple hooks to ensure we catch the change
// add_action('woocommerce_cart_item_quantity_changed', 'cart_item_quantity_update_ample', 10, 3);
add_action('woocommerce_after_cart_item_quantity_update', 'cart_item_quantity_update_ample_after', 10, 4);
function cart_item_quantity_update_ample($cart_item_key, $quantity, $old_quantity) {
    error_log('Ample Connect: Original hook fired - cart_item_key: ' . $cart_item_key . ', quantity: ' . $quantity . ', old_quantity: ' . $old_quantity);
    
    // Skip if quantity didn't actually change
    if ($quantity === $old_quantity) {
        error_log('Ample Connect: Quantity unchanged, skipping');
        return;
    }

    $cart_item = WC()->cart->get_cart_item($cart_item_key);
    if (!$cart_item) {
        return;
    }

    $product_id = $cart_item['variation_id'] ? $cart_item['variation_id'] : $cart_item['product_id'];
    $product = wc_get_product($product_id);
    if (!$product) {
        return;
    }

    // Get the SKU ID for the product or variation
    $sku = $product->get_sku();
    $sku_id = '';
    if (!empty($sku) && strpos($sku, '-') !== false) {
        $sku_id = explode("-", $sku)[1];
    }

    if (empty($sku_id)) {
        error_log('Ample Connect: No valid SKU ID found for product ' . $product_id);
        return;
    }

    $order_id = Ample_Session_Cache::get('order_id');
    $order_items = Ample_Session_Cache::get('order_items');
    
    if (!$order_id || !$order_items) {
        error_log('Ample Connect: No order ID or order items found in session');
        return;
    }

    // Find the order item ID for this SKU
    $order_item_id = null;
    foreach ($order_items as $item) {
        if (isset($item['sku_id']) && $item['sku_id'] == $sku_id) {
            $order_item_id = $item['id'];
            break;
        }
    }

    if (!$order_item_id) {
        error_log('Ample Connect: No order item found for SKU ' . $sku_id);
        return;
    }

    // Update quantity in Ample
    error_log('Ample Connect: Attempting to update quantity for SKU ' . $sku_id . ' from ' . $old_quantity . ' to ' . $quantity);
    error_log('Ample Connect: Order ID: ' . $order_id . ', Order Item ID: ' . $order_item_id);
    $response = change_item_quantity($order_id, $order_item_id, $quantity);
    
    if (is_wp_error($response)) {
        error_log('Ample Connect: API call failed: ' . $response->get_error_message());
        // Optionally revert the cart change or show error message
        wc_add_notice('Failed to update quantity in Ample system. Please try again.', 'error');
    } else {
        error_log('Ample Connect: Quantity updated successfully for SKU ' . $sku_id . ' to ' . $quantity);
        error_log('Ample Connect: API Response: ' . print_r($response, true));
        // Refresh session data to get updated order information
        // Client_Information::fetch_information();
    }
}

// Alternative hook for quantity changes
function cart_item_quantity_update_ample_after($cart_item_key, $quantity, $old_quantity, $cart) {
    error_log('Ample Connect: Alternative hook fired - cart_item_key: ' . $cart_item_key . ', quantity: ' . $quantity . ', old_quantity: ' . $old_quantity);
    
    // Skip if quantity didn't actually change
    if ($quantity === $old_quantity) {
        return;
    }

    $cart_item = $cart->get_cart_item($cart_item_key);
    if (!$cart_item) {
        error_log('Ample Connect: No cart item found for key: ' . $cart_item_key);
        return;
    }

    $product_id = $cart_item['variation_id'] ? $cart_item['variation_id'] : $cart_item['product_id'];
    $product = wc_get_product($product_id);
    if (!$product) {
        error_log('Ample Connect: No product found for ID: ' . $product_id);
        return;
    }

    // Get the SKU ID for the product or variation
    $sku = $product->get_sku();
    $sku_id = '';
    if (!empty($sku) && strpos($sku, '-') !== false) {
        $sku_id = explode("-", $sku)[1];
    }

    if (empty($sku_id)) {
        error_log('Ample Connect: No valid SKU ID found for product ' . $product_id);
        return;
    }

    $order_id = Ample_Session_Cache::get('order_id');
    $order_items = Ample_Session_Cache::get('order_items');
    
    if (!$order_id || !$order_items) {
        error_log('Ample Connect: No order ID or order items found in session');
        return;
    }

    // Find the order item ID for this SKU
    $order_item_id = null;
    foreach ($order_items as $item) {
        if (isset($item['sku_id']) && $item['sku_id'] == $sku_id) {
            $order_item_id = $item['id'];
            break;
        }
    }

    if (!$order_item_id) {
        error_log('Ample Connect: No order item found for SKU ' . $sku_id);
        return;
    }

    // Update quantity in Ample
    error_log('Ample Connect: Alternative hook - Attempting to update quantity for SKU ' . $sku_id . ' from ' . $old_quantity . ' to ' . $quantity);
    error_log('Ample Connect: Alternative hook - Order ID: ' . $order_id . ', Order Item ID: ' . $order_item_id);
    $response = change_item_quantity($order_id, $order_item_id, $quantity);
    
    if (is_wp_error($response)) {
        error_log('Ample Connect: Alternative hook - API call failed: ' . $response->get_error_message());
        wc_add_notice('Failed to update quantity in Ample system. Please try again.', 'error');
    } else {
        error_log('Ample Connect: Alternative hook - Quantity updated successfully for SKU ' . $sku_id . ' to ' . $quantity);
        error_log('Ample Connect: Alternative hook - API Response: ' . print_r($response, true));
        // Refresh session data to get updated order information
        Client_Information::fetch_information();
    }
}





// checkout page 
add_action('woocommerce_before_checkout_form', 'calculate_total_package_size_in_checkout_page');
function calculate_total_package_size_in_checkout_page() {
    $total_package_size = 0;
    // $get_available_to_order = Client_Information::get_available_to_order();
    $get_available_to_order  = Ample_Session_Cache::get('available_to_order');
    // Loop through checkout items
    foreach (WC()->cart->get_cart() as $cart_item) {
        $product = wc_get_product($cart_item['variation_id'] ?: $cart_item['product_id']);
        $package_size = $product->get_attribute('package-size');
        $quantity = $cart_item['quantity'];
        $total_package_size += floatval($package_size) * $quantity;
    }

    // Output the total package size
    echo '<div id="total-package-size" style="display: none;">' . $total_package_size . '</div>';
    echo '<div id="available-to-order" style="display: none;">' . $get_available_to_order . '</div>';
}


add_action('woocommerce_product_query', 'filter_products_based_on_login_status');
function filter_products_based_on_login_status($query) {
    if (is_user_logged_in()) {
        // $allowed_skus = get_purchasable_products();
        $allowed_skus = Ample_Session_Cache::get('purchasable_products');
        if (!empty($allowed_skus)) {
            $allowed_ids = get_product_ids_by_skus($allowed_skus);
            if (!empty($allowed_ids)) {
                $query->set('post__in', $allowed_ids);
            } else {
                // If there are no allowed products, set to a non-existing ID
                $query->set('post__in', array(0));
            }
        } else {
            // If there are no allowed products, set to a non-existing ID
            $query->set('post__in', array(0));
        }
    } else {
        // Non-logged-in users or clients without purchasable products: show no products
        $query->set('post__in', array(0));
    }
}

// Apply discount to cart items
// add_action( 'woocommerce_before_calculate_totals', 'apply_discount_after_api', 10, 1 );
// function apply_discount_after_api( $cart ) {
//     // Loop through each item in the cart
//     foreach ( $cart->get_cart() as $cart_item_key => $cart_item ) {
//         // Check if discount data exists in the cart item
//         if ( isset( $cart_item['discount_percentage'] ) ) {
//             // Get the discount percentage from the cart item data
//             $discount_percentage = $cart_item['discount_percentage'];
            
//             // Get the original price of the cart item
//             $original_price = $cart_item['data']->get_price();
            
//             // Calculate the discount amount
//             $discount_amount = ( (float)($original_price) * (float)$discount_percentage ) / 100;
            
//             // Calculate the new price
//             $new_price = $original_price - $discount_amount;
            
//             // Set the new price for the cart item
//             $cart_item['data']->set_price( $new_price );
//         }
//     }
// }

add_action('woocommerce_before_calculate_totals', 'apply_api_discounts_to_cart_items', 20, 1);
function apply_api_discounts_to_cart_items($cart) {
    if (is_admin() && !defined('DOING_AJAX')) return;

    foreach ($cart->get_cart() as $cart_item_key => $cart_item) {
        if (!empty($cart_item['api_discounts'])) {

            // Ensure we always have the original price saved
            // if (!isset($cart_item['original_price'])) {
            //     $cart->cart_contents[$cart_item_key]['original_price'] = $cart_item['data']->get_regular_price();
            //     if (!$cart->cart_contents[$cart_item_key]['original_price']) {
            //         $cart->cart_contents[$cart_item_key]['original_price'] = $cart_item['data']->get_price();
            //     }
            // }

            // $original_price = $cart->cart_contents[$cart_item_key]['original_price'];

            // Calculate discount
            $total_discount = 0;
            $notes = [];
            foreach ($cart_item['api_discounts'] as $discount) {
                $amount = floatval($discount['dollar_amount']) / 100;
                $total_discount += $amount;
                $notes[] = $discount['discount_type_name'] . " -$" . number_format($amount, 2);
            }

            // // Apply new price based on ORIGINAL
            // $new_price = max(0, $original_price - $total_discount);
            // $cart_item['data']->set_price($new_price);

            // // Save discount note
            // $cart->cart_contents[$cart_item_key]['custom_discount_note'] = implode(', ', $notes);

            if ($total_discount > 0) {
                $label = $cart_item['data']->get_name() . ' discount';
                if (!empty($notes)) {
                    $label .= ' (' . implode(', ', $notes) . ')';
                }

                $cart->add_fee($label, -$total_discount);
            }

            // ample_connect_log("Applied discount → original: $original_price, discount: $total_discount, new: $new_price");
        }
    }
}



// Allow 0 amount orders
add_filter('woocommerce_cart_needs_payment', 'allow_zero_total_checkout', 10, 2);
function allow_zero_total_checkout($needs_payment, $cart) {
    if ($cart->get_total('edit') <= 0) {
        return false; // Don't require payment method for free orders
    }
    return $needs_payment;
}

// AJAX handler for quantity updates
add_action('wp_ajax_ample_update_quantity', 'ample_update_quantity_ajax');
function ample_update_quantity_ajax() {
    // Verify nonce
    if (!wp_verify_nonce($_POST['nonce'], 'ample_connect_nonce')) {
        wp_send_json_error('Invalid nonce');
        return;
    }
    
    $cart_item_key = sanitize_text_field($_POST['cart_item_key']);
    $quantity = intval($_POST['quantity']);
    $old_quantity = intval($_POST['old_quantity']);
    
    error_log('Ample Connect: AJAX quantity update - Key: ' . $cart_item_key . ', Old: ' . $old_quantity . ', New: ' . $quantity);
    
    // Get cart item
    $cart_item = WC()->cart->get_cart_item($cart_item_key);
    if (!$cart_item) {
        wp_send_json_error('Cart item not found');
        return;
    }
    
    $product_id = $cart_item['variation_id'] ? $cart_item['variation_id'] : $cart_item['product_id'];
    $product = wc_get_product($product_id);
    if (!$product) {
        wp_send_json_error('Product not found');
        return;
    }
    
    // Get the SKU ID for the product or variation
    $sku = $product->get_sku();
    $sku_id = '';
    if (!empty($sku) && strpos($sku, '-') !== false) {
        $sku_id = explode("-", $sku)[1];
    }
    
    if (empty($sku_id)) {
        wp_send_json_error('No valid SKU ID found');
        return;
    }
    
    $order_id = Ample_Session_Cache::get('order_id');
    $order_items = Ample_Session_Cache::get('order_items');
    
    if (!$order_id || !$order_items) {
        wp_send_json_error('No order ID or order items found');
        return;
    }
    
    // Find the order item ID for this SKU
    $order_item_id = null;
    foreach ($order_items as $item) {
        if (isset($item['sku_id']) && $item['sku_id'] == $sku_id) {
            $order_item_id = $item['id'];
            break;
        }
    }
    
    if (!$order_item_id) {
        wp_send_json_error('No order item found for SKU');
        return;
    }
    
    // Update quantity in Ample
    $response = change_item_quantity($order_id, $order_item_id, $quantity);
    
    if (is_wp_error($response)) {
        wp_send_json_error($response->get_error_message());
    } else {
        // Refresh session data
        Client_Information::fetch_information();
        wp_send_json_success('Quantity updated successfully');
    }
}

