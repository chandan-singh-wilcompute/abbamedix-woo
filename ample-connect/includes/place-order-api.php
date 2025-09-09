<?php
if (!defined('ABSPATH')) {
    exit;
}
require_once plugin_dir_path(__FILE__) . '/customer-functions.php';

add_filter('woocommerce_add_to_cart_validation', 'custom_add_to_cart_validation', 10, 5);
function custom_add_to_cart_validation($passed, $product_id, $quantity, $variation_id = 0, $variations = array()) {
    
    if (is_user_logged_in()) {
        // $allowed_skus = get_purchasable_products();
        $allowed_skus = Ample_Session_Cache::get('purchasable_products');
        
        if (!empty($allowed_skus)) {
            // Extract SKU strings from the complex session structure
            $sku_strings = array();
            foreach ($allowed_skus as $product_data) {
                if (isset($product_data['id'])) {
                    $sku_strings[] = 'sku-' . $product_data['id'];
                }
                // Also extract SKU IDs from nested skus array
                if (isset($product_data['skus']) && is_array($product_data['skus'])) {
                    foreach ($product_data['skus'] as $sku_data) {
                        if (isset($sku_data['id'])) {
                            $sku_strings[] = $product_data['id'] . '-' . $sku_data['id'];
                        }
                    }
                }
            }
            
            $allowed_ids = get_product_ids_by_skus($sku_strings);
            
            if (!in_array($product_id, $allowed_ids)) {
                wc_add_notice('This product cannot be added to the cart.', 'error');
                return false;
            }
        } else {
            wc_add_notice('This product cannot be added to the cart.', 'error');
            return false;
        }
    } else {
        wc_add_notice('You need to be logged in to add this product to the cart.', 'error');
        return false;
    }
    
    // OPTIMIZED: Use cached product IDs instead of database query
    $allowed_product_ids = ample_get_cached_purchasable_product_ids();
    
    if (empty($allowed_product_ids) || (count($allowed_product_ids) === 1 && $allowed_product_ids[0] === 0)) {
        wc_add_notice('This product cannot be added to the cart.', 'error');
        return false;
    }
    
    // Quick array lookup instead of database query
    if (!in_array($product_id, $allowed_product_ids)) {
        wc_add_notice('This product cannot be added to the cart.', 'error');
        return false;
    }

    // OPTIMIZED: Get package size efficiently
    $current_product = wc_get_product($variation_id ?: $product_id);
    $current_item_gram = ((float)$current_product->get_meta('RX Reduction')) * $quantity;

    ample_connect_log("current item gram - " . $current_item_gram);

    // OPTIMIZED: Calculate cart total package size more efficiently
    $current_on_cart = Ample_Session_Cache::get('current_on_cart', 0);
    if ($current_on_cart == 0) {

        $total = 0;
        if ( WC()->cart ) {
            foreach ( WC()->cart->get_cart() as $cart_item ) {
                $product = $cart_item['data']; // WC_Product object
                $quantity = $cart_item['quantity'];

                // Make sure 'rx_reduction' meta exists
                $rx_reduction = floatval( $product->get_meta('RX Reduction') );

                $total += $rx_reduction * $quantity;
            }
        }

        $current_on_cart = $total;
    }
    
    ample_connect_log("current available - " . $current_on_cart);
    $total_grams = $current_item_gram + $current_on_cart;
    ample_connect_log("total - " . $total_grams);
    // $order = get_order_id_from_api();
    $order_id = Ample_Session_Cache::get('order_id');

    if ($order_id) {
        // $get_available_to_order = Client_Information::get_available_to_order();
        $available_to_order  = Ample_Session_Cache::get('available_to_order');
        ample_connect_log("availablee to order - " . $available_to_order);
        
        if ($available_to_order < $total_grams) {
            wc_add_notice('There is not enough room on the prescription to cover your purchase quantity.', 'error');
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
        return;
    }
    $current_product = wc_get_product($variation_id ?: $product_id);
    $sku = $current_product->get_sku();
    $sku_array = explode("-", $sku);
    $sku_id = $sku_array[1] ?? null;
    
    if (!$sku_id) {
        ample_connect_log("Invalid SKU format for product ID: " . ($variation_id ?: $product_id));
        return;
    }

    $order_id = Ample_Session_Cache::get('order_id');
    $order_items = Ample_Session_Cache::get('order_items');
    
    if ($order_id) {
        try {
            
            $found_in_cart = false;
            foreach ($order_items as $a_item) {
                if (isset($a_item['sku_id']) && $a_item['sku_id'] == $sku_id) {
                    $found_in_cart = true;
                    break;
                }
            }

            if (!$found_in_cart) {
                add_to_order($order_id, $sku_id, $quantity);
            }
        } catch (Exception $e) {
            ample_connect_log('Exception during add to order: ' . $e->getMessage());
        }
    } else {
        ample_connect_log('No order_id available during add to cart');
    }
}




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



// OPTIMIZED: Only register cart calculations on cart/checkout pages
add_action('wp', 'ample_conditional_cart_hooks');

/**
 * Conditionally register cart-related hooks only when needed
 */
function ample_conditional_cart_hooks() {
    // Only register cart calculation hooks on cart and checkout pages
    if (is_cart()) {
        add_action('woocommerce_before_cart', 'ample_optimized_cart_calculations', 10);
    }
    
    if (is_checkout()) {
        add_action('woocommerce_before_checkout_form', 'ample_optimized_checkout_calculations', 10);
    }
}
function ample_optimized_cart_calculations() {
    // Only run if cart exists and has items
    if (!WC()->cart || WC()->cart->is_empty()) {
        return;
    }

    // Cache the calculation to avoid multiple runs
    static $cart_hash = '';
    $current_hash = WC()->cart->get_cart_hash();
    if ($cart_hash === $current_hash) {
        return; // Already calculated for this cart state
    }
    $cart_hash = $current_hash;

    $total_package_size = 0;
    $get_available_to_order = Ample_Session_Cache::get('available_to_order');
    
    // Loop through cart items once
    foreach (WC()->cart->get_cart() as $cart_item) {
        $product = wc_get_product($cart_item['variation_id'] ?: $cart_item['product_id']);
        if ($product) {
            $package_size = $product->get_attribute('package-size');
            $quantity = $cart_item['quantity'];
            $total_package_size += floatval($package_size) * $quantity;
        }
    }

    // Output the total package size (only on cart page)
    echo '<div id="total-package-size" style="display: none;">' . $total_package_size . '</div>';
    echo '<div id="available-to-order" style="display: none;">' . $get_available_to_order . '</div>';   
}


// Hook into cart quantity changes - try multiple hooks to ensure we catch the change
// add_action('woocommerce_cart_item_quantity_changed', 'cart_item_quantity_update_ample', 10, 3);
add_action('woocommerce_after_cart_item_quantity_update', 'cart_item_quantity_update_ample_after', 10, 4);
// function cart_item_quantity_update_ample($cart_item_key, $quantity, $old_quantity) {
//     error_log('Ample Connect: Original hook fired - cart_item_key: ' . $cart_item_key . ', quantity: ' . $quantity . ', old_quantity: ' . $old_quantity);
    
//     // Skip if quantity didn't actually change
//     if ($quantity === $old_quantity) {
//         error_log('Ample Connect: Quantity unchanged, skipping');
//         return;
//     }

//     $cart_item = WC()->cart->get_cart_item($cart_item_key);
//     if (!$cart_item) {
//         return;
//     }

//     $product_id = $cart_item['variation_id'] ? $cart_item['variation_id'] : $cart_item['product_id'];
//     $product = wc_get_product($product_id);
//     if (!$product) {
//         return;
//     }

//     // Get the SKU ID for the product or variation
//     $sku = $product->get_sku();
//     $sku_id = '';
//     if (!empty($sku) && strpos($sku, '-') !== false) {
//         $sku_id = explode("-", $sku)[1];
//     }

//     if (empty($sku_id)) {
//         error_log('Ample Connect: No valid SKU ID found for product ' . $product_id);
//         return;
//     }

//     $order_id = Ample_Session_Cache::get('order_id');
//     $order_items = Ample_Session_Cache::get('order_items');
    
//     if (!$order_id || !$order_items) {
//         error_log('Ample Connect: No order ID or order items found in session');
//         return;
//     }

//     // Find the order item ID for this SKU
//     $order_item_id = null;
//     foreach ($order_items as $item) {
//         if (isset($item['sku_id']) && $item['sku_id'] == $sku_id) {
//             $order_item_id = $item['id'];
//             break;
//         }
//     }

//     if (!$order_item_id) {
//         error_log('Ample Connect: No order item found for SKU ' . $sku_id);
//         return;
//     }

//     // Update quantity in Ample
//     error_log('Ample Connect: Attempting to update quantity for SKU ' . $sku_id . ' from ' . $old_quantity . ' to ' . $quantity);
//     error_log('Ample Connect: Order ID: ' . $order_id . ', Order Item ID: ' . $order_item_id);
//     $response = change_item_quantity($order_id, $order_item_id, $quantity);
    
//     if (is_wp_error($response)) {
//         error_log('Ample Connect: API call failed: ' . $response->get_error_message());
//         // Optionally revert the cart change or show error message
//         wc_add_notice('Failed to update quantity in Ample system. Please try again.', 'error');
//     } else {
//         error_log('Ample Connect: Quantity updated successfully for SKU ' . $sku_id . ' to ' . $quantity);
//         error_log('Ample Connect: API Response: ' . print_r($response, true));
//         // Refresh session data to get updated order information
//         // Client_Information::fetch_information();
//     }
// }

// Alternative hook for quantity changes
function cart_item_quantity_update_ample_after($cart_item_key, $quantity, $old_quantity, $cart) {
    error_log('Ample Connect: Alternative hook fired - cart_item_key: ' . $cart_item_key . ', quantity: ' . $quantity . ', old_quantity: ' . $old_quantity);
    
    ample_connect_log("cart_item_quantity_update_ample_after");
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
    // ample_connect_log($response);
    
    if (is_wp_error($response)) {
        error_log('Ample Connect: Alternative hook - API call failed: ' . $response->get_error_message());
        wc_add_notice('Failed to update quantity in Ample system. Please try again.', 'error');
    } else {
        error_log('Ample Connect: Alternative hook - Quantity updated successfully for SKU ' . $sku_id . ' to ' . $quantity);
        error_log('Ample Connect: Alternative hook - API Response: ' . print_r($response, true));
        // Refresh session data to get updated order information
        // Client_Information::fetch_information();
    }
}





// checkout page 
// OPTIMIZED: Now registered conditionally in ample_conditional_cart_hooks()
// add_action('woocommerce_before_checkout_form', 'ample_optimized_checkout_calculations');
function ample_optimized_checkout_calculations() {
    // Only run if cart exists and has items
    if (!WC()->cart || WC()->cart->is_empty()) {
        return;
    }

    // Cache the calculation to avoid multiple runs
    static $checkout_cart_hash = '';
    $current_hash = WC()->cart->get_cart_hash();
    if ($checkout_cart_hash === $current_hash) {
        return; // Already calculated for this cart state
    }
    $checkout_cart_hash = $current_hash;

    $total_package_size = 0;
    $get_available_to_order = Ample_Session_Cache::get('available_to_order');
    
    // Loop through checkout items once
    foreach (WC()->cart->get_cart() as $cart_item) {
        $product = wc_get_product($cart_item['variation_id'] ?: $cart_item['product_id']);
        if ($product) {
            $package_size = $product->get_attribute('package-size');
            $quantity = $cart_item['quantity'];
            $total_package_size += floatval($package_size) * $quantity;
        }
    }

    // Handle shipping method selection (only on checkout)
    $chosen_shipping_method = Ample_Session_Cache::get('applied_shipping_rate'); 
    if (!empty($chosen_shipping_method)) {
        // Store for later (your own key)
        WC()->session->set('chosen_shipping_methods', ["custom_shipping_api" => $chosen_shipping_method]);
    }

    // Output the total package size (only on checkout page)
    echo '<div id="total-package-size" style="display: none;">' . $total_package_size . '</div>';
    echo '<div id="available-to-order" style="display: none;">' . $get_available_to_order . '</div>';
}


// OPTIMIZED: Only register when on shop pages 
add_action('wp', 'ample_conditional_login_status_filtering');

/**
 * Conditionally register login status filtering only when needed
 */
function ample_conditional_login_status_filtering() {
    // Only register product query filtering on shop-related pages
    if (is_shop() || is_product_category() || is_product_tag() || is_product_taxonomy()) {
        add_action('woocommerce_product_query', 'filter_products_based_on_login_status', 10);
    }
}
// TEMPORARILY DISABLED FOR DEBUG - add_action('woocommerce_product_query', 'filter_products_based_on_login_status');
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

// Apply individual API discounts from order_items session data to cart items
add_action('woocommerce_before_calculate_totals', 'apply_individual_api_discounts_to_cart', 15, 1);

/**
 * Apply individual API discounts from order_items session to WooCommerce cart
 * This syncs individual discounts from third-party system to WooCommerce checkout
 */
function apply_individual_api_discounts_to_cart($cart) {
    // Standard guards
    if (is_admin() && !defined('DOING_AJAX')) return;
    if (!$cart || $cart->is_empty()) return;
    
    // Only run on checkout page for discounts
    if (!is_checkout()) return;
    
    // Cache to prevent multiple runs for same cart state
    static $discounts_cart_hash = '';
    $current_hash = $cart->get_cart_hash();
    if ($discounts_cart_hash === $current_hash) {
        return; // Already calculated for this cart state
    }
    $discounts_cart_hash = $current_hash;

    ample_connect_log("Applying individual API discounts to cart");
    
    // Apply API discounts to individual cart items from order_items session
    foreach ($cart->get_cart() as $cart_item_key => $cart_item) {
        if (!empty($cart_item['api_discounts'])) {
            $total_discount = 0;
            $notes = [];
            foreach ($cart_item['api_discounts'] as $discount) {
                $amount = floatval($discount['dollar_amount']) / 100;
                $total_discount += $amount;
                $notes[] = $discount['discount_type_name'] . " -$" . number_format($amount, 2);
            }

            if ($total_discount > 0) {
                $label = $cart_item['data']->get_name() . ' discount';
                if (!empty($notes)) {
                    $label .= ' (' . implode(', ', $notes) . ')';
                }
                $cart->add_fee($label, -$total_discount);
                ample_connect_log("Applied individual discount: $total_discount for " . $cart_item['data']->get_name());
            }
        }
    }
}
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

// OPTIMIZED: Combined totals and fees calculation
// add_action('woocommerce_before_calculate_totals', 'ample_optimized_cart_totals_calculation', 15, 1);
// function ample_optimized_cart_totals_calculation($cart) {
//     // Standard guards
//     if (is_admin() && !defined('DOING_AJAX')) return;
//     if (!$cart || $cart->is_empty()) return;

//     // Cache to prevent multiple runs for same cart state
//     static $totals_cart_hash = '';
//     $current_hash = $cart->get_cart_hash();
//     if ($totals_cart_hash === $current_hash) {
//         return; // Already calculated for this cart state
//     }
//     $totals_cart_hash = $current_hash;

//     // Only run on checkout page for discounts
//     if (!is_checkout()) return;

//     ample_connect_log("optimized cart totals calculation called");
    
//     // 1. Apply API discounts to individual cart items
//     foreach ($cart->get_cart() as $cart_item_key => $cart_item) {
//         if (!empty($cart_item['api_discounts'])) {
//             $total_discount = 0;
//             $notes = [];
//             foreach ($cart_item['api_discounts'] as $discount) {
//                 $amount = floatval($discount['dollar_amount']) / 100;
//                 $total_discount += $amount;
//                 $notes[] = $discount['discount_type_name'] . " -$" . number_format($amount, 2);
//             }

//             if ($total_discount > 0) {
//                 $label = $cart_item['data']->get_name() . ' discount';
//                 if (!empty($notes)) {
//                     $label .= ' (' . implode(', ', $notes) . ')';
//                 }
//                 $cart->add_fee($label, -$total_discount);
//             }
//         }
//     }

//     // 2. Apply custom taxes and fees (integrated from apply_selected_custom_discount)
//     $custom_taxes = Ample_Session_Cache::get('custom_tax_data');
//     if (!empty($custom_taxes) && is_array($custom_taxes)) {
//         foreach ($custom_taxes as $tax_label => $amount_cents) {
//             $amount_dollars = floatval($amount_cents) / 100;
//             $cart->add_fee(ucfirst($tax_label), $amount_dollars, false);
//         }
//     }

//     // 3. Apply fixed amount discounts
//     $discounts = Ample_Session_Cache::get('applied_discounts', []);
//     if ($discounts) {
//         foreach($discounts as $d) {
//             $cart->add_fee($d['desc'], -(floatval($d['amount'])/100), false);
//         }
//     }
    
//     // 4. Apply percentage-based policies
//     $policies = Ample_Session_Cache::get('applied_policies', []);
//     if ($policies) {
//         foreach($policies as $p){
//             $cart_total = 0;
//             foreach ($cart->get_cart() as $item) {
//                 $cart_total += $item['line_total'] + $item['line_tax'];
//             }
//             $cart_total += $cart->get_shipping_total() + $cart->get_shipping_tax();
//             $fee_total = 0;
//             foreach ($cart->get_fees() as $fee) {
//                 $fee_total += $fee->amount;
//             }
//             $cart_total += $fee_total;
//             $discount_amount = $cart_total * (floatval($p['percentage']) / 100);
//             $cart->add_fee($p['desc'], -$discount_amount, false);
//         }
//     }
// }



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
        // Client_Information::fetch_information();
        wp_send_json_success('Quantity updated successfully');
    }
}

