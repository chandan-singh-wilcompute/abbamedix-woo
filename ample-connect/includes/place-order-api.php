<?php
if (!defined('ABSPATH')) {
    exit;
}
require_once plugin_dir_path(__FILE__) . '/customer-functions.php';

/**
 * Add to cart with Ample API BEFORE adding to WooCommerce cart
 * This ensures systems stay in sync and respects Ample's business constraints
 * Uses the real add_to_order endpoint to validate constraints and add the item
 */
function add_to_ample_before_woocommerce($order_id, $sku_id, $quantity) {
    try {
        $user_id = get_current_user_id();
        $client_id = get_user_meta($user_id, 'client_id', true);
        
        if (!$client_id) {
            return 'Client ID not found.';
        }

        // Use the real add_to_order endpoint to validate constraints BEFORE WooCommerce
        $url = AMPLE_CONNECT_PORTAL_URL . "/orders/{$order_id}/add_to_order";
        $body = array(
            'quantity' => $quantity,
            'sku_id' => $sku_id,
            'client_id' => $client_id
        );
        
        // ample_connect_log("API-First Validation: Checking constraints with Ample add_to_order - Order: {$order_id}, SKU: {$sku_id}, Qty: {$quantity}");
        
        // Make API call to Ample's add_to_order endpoint 
        $validation_response = ample_request($url, 'PUT', $body);
        
        // Handle token expiration
        if ($validation_response === "Token_Expired") {
            return 'Authentication expired. Please refresh the page and try again.';
        }
        
        // If add_to_order succeeds, the item is now in Ample - refresh session data
        if (is_array($validation_response) && !isset($validation_response['error']) && !isset($validation_response['error_code'])) {
            ample_connect_log("API-First: add_to_order succeeded - item added to Ample, updating session with response data");
            
            // Update session directly with the API response data (avoid unnecessary current_order API call)
            $user_id = get_current_user_id();
            store_current_order_to_session($validation_response, $user_id);
            
            // No need to track processed items since redundant hook is now removed
            return true;
        }
        
        // Check for Ample API errors in the response
        if (is_array($validation_response)) {
            // Check for specific Ample error codes and messages
            if (isset($validation_response['error_code'])) {
                $error_code = $validation_response['error_code'];
                $error_message = $validation_response['error'] ?? $validation_response['message'] ?? 'Unknown error';
                
                ample_connect_log("API-First Validation Failed - Error Code: {$error_code}, Message: {$error_message}");
                
                // Use comprehensive error parsing function from utility.php
                return get_parsed_ample_error($validation_response);
                
            } elseif (isset($validation_response['error'])) {
                // Generic error field
                $error_message = $validation_response['error'];
                ample_connect_log("API-First Validation Failed - Generic Error: {$error_message}");
                
                return get_parsed_ample_error($validation_response);
            }
        }
        
        ample_connect_log("API-First: add_to_order succeeded");
        return true;
        
    } catch (Exception $e) {
        ample_connect_log("API-First Exception: " . $e->getMessage());
        return 'Add to order failed due to system error.';
    }
}

add_filter('woocommerce_add_to_cart_validation', 'custom_add_to_cart_validation', 10, 5);
function custom_add_to_cart_validation($passed, $product_id, $quantity, $variation_id = 0, $variations = array()) {

    if (!is_user_logged_in()) {
        wc_add_notice('You need to be logged in to add this product to the cart.', 'error');
        return false;
    }
    
    // if (is_user_logged_in()) {
    //     // $allowed_skus = get_purchasable_products();
    //     // $allowed_skus = Ample_Session_Cache::get('purchasable_products');
        
    //     if (!empty($allowed_skus)) {
    //         // Extract SKU strings from the complex session structure
    //         $sku_strings = array();
    //         foreach ($allowed_skus as $product_data) {
    //             if (isset($product_data['id'])) {
    //                 $sku_strings[] = 'sku-' . $product_data['id'];
    //             }
    //             // Also extract SKU IDs from nested skus array
    //             if (isset($product_data['skus']) && is_array($product_data['skus'])) {
    //                 foreach ($product_data['skus'] as $sku_data) {
    //                     if (isset($sku_data['id'])) {
    //                         $sku_strings[] = $product_data['id'] . '-' . $sku_data['id'];
    //                     }
    //                 }
    //             }
    //         }
            
    //         $allowed_ids = get_product_ids_by_skus($sku_strings);
            
    //         if (!in_array($product_id, $allowed_ids)) {
    //             wc_add_notice('This product cannot be added to the cart.', 'error');
    //             return false;
    //         }
    //     } else {
    //         wc_add_notice('This product cannot be added to the cart.', 'error');
    //         return false;
    //     }
    // } else {
    //     wc_add_notice('You need to be logged in to add this product to the cart.', 'error');
    //     return false;
    // }
    
    // OPTIMIZED: Use cached product IDs instead of database query
    $allowed_product_ids = ample_get_cached_purchasable_product_ids();
    
    if (empty($allowed_product_ids) || !in_array($product_id, $allowed_product_ids) || (count($allowed_product_ids) === 1 && $allowed_product_ids[0] === 0)) {
        wc_add_notice('This product cannot be added to the cart.', 'error');
        return false;
    }
    
    // // Quick array lookup instead of database query
    // if (!in_array($product_id, $allowed_product_ids)) {
    //     wc_add_notice('This product cannot be added to the cart.', 'error');
    //     return false;
    // }

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

    // CRITICAL: API-First Validation - Validate with Ample BEFORE allowing WooCommerce to proceed
    // This prevents sync issues when Ample rejects items due to business constraints
    // $current_product = wc_get_product($variation_id ?: $product_id);
    // $sku = $current_product->get_sku();
    // $sku_array = explode("-", $sku);
    // $sku_id = $sku_array[1] ?? null;
    
    // if ($sku_id && $order_id) {
    //     ample_connect_log("API-First: Starting Ample add_to_order for SKU {$sku_id} with quantity {$quantity}");
        
    //     $api_add_result = add_to_ample_before_woocommerce($order_id, $sku_id, $quantity);
        
    //     if ($api_add_result !== true) {
    //         // Ample rejected the addition - block WooCommerce from proceeding
    //         $error_message = is_string($api_add_result) ? $api_add_result : 'Cannot add item due to order constraints.';
    //         wc_add_notice($error_message, 'error');
    //         ample_connect_log("API-First: BLOCKED cart addition - " . $error_message);
    //         return false;
    //     }
        
    //     ample_connect_log("API-First: APPROVED and ADDED to Ample - proceeding with WooCommerce");
    //     // Item is now in both Ample and will be added to WooCommerce
    //     // custom_add_to_order() will skip duplicate addition since item is marked as processed
    // }


    // --- REPLACEMENT START ---
    /**
     * API-first: If item already exists in Ample order, call change_item_quantity()
     * otherwise call add_to_ample_before_woocommerce() to add it.
     */
    // $current_product = wc_get_product($variation_id ?: $product_id);
    $sku = $current_product->get_sku();
    $sku_array = explode("-", $sku);
    $sku_id = $sku_array[1] ?? null;

    if ($sku_id && $order_id) {
        ample_connect_log("API-First: Handling SKU {$sku_id} with incoming qty {$quantity}");

        // find matching order item in session
        $order_items = Ample_Session_Cache::get('order_items', []);
        $matching_order_item = null;
        if (!empty($order_items) && is_array($order_items)) {
            foreach ($order_items as $it) {
                if (isset($it['sku_id']) && (string)$it['sku_id'] === (string)$sku_id) {
                    $matching_order_item = $it;
                    break;
                }
            }
        }

        if ($matching_order_item && isset($matching_order_item['id'])) {
            // Item exists in Ample — compute final quantity and call change_item_quantity
            $order_item_id = $matching_order_item['id'];

            // Get existing quantity in cart (before this add) so we can calculate final qty
            $existing_cart_qty = 0;
            if (WC()->cart) {
                foreach (WC()->cart->get_cart() as $ci) {
                    $prod = $ci['data'];
                    if ($prod && $prod->get_sku() === $current_product->get_sku()) {
                        $existing_cart_qty = intval($ci['quantity']);
                        break;
                    }
                }
            }

            $new_quantity = $existing_cart_qty + intval($quantity);
            if ($new_quantity < 1) {
                $new_quantity = intval($quantity);
            }

            ample_connect_log("API-First: SKU exists in Ample order (order_item_id={$order_item_id}). Calling change_item_quantity to set qty: {$new_quantity}");

            $response = change_item_quantity($order_id, $order_item_id, $new_quantity);

            if (is_wp_error($response)) {
                $msg = $response->get_error_message();
                wc_add_notice('Failed to update quantity with Ample: ' . $msg, 'error');
                ample_connect_log("API-First: change_item_quantity failed - {$msg}");
                return false;
            }

            // Update session directly with the API response data (consistent with add_to_order)
            if (is_array($response) && !is_wp_error($response)) {
                $user_id = get_current_user_id();
                store_current_order_to_session($response, $user_id);
            }

            // Mark SKU as processed in session so the quantity-update hook doesn't duplicate the API call
            $processed = WC()->session->get('ample_validation_processed', []);
            if (!is_array($processed)) $processed = [];
            $processed[$sku_id] = true;
            WC()->session->set('ample_validation_processed', $processed);

            ample_connect_log("API-First: change_item_quantity succeeded for SKU {$sku_id}");
            return true;
        }

        // Item not in Ample yet — call existing add_to_ample_before_woocommerce()
        ample_connect_log("API-First: Item not present in Ample, calling add_to_order for SKU {$sku_id}");
        $api_add_result = add_to_ample_before_woocommerce($order_id, $sku_id, $quantity);

        if ($api_add_result !== true) {
            $error_message = is_string($api_add_result) ? $api_add_result : 'Cannot add item due to order constraints.';
            wc_add_notice($error_message, 'error');
            ample_connect_log("API-First: BLOCKED cart addition - " . $error_message);
            return false;
        }

        // mark processed so later hooks know the add was handled
        $processed = WC()->session->get('ample_validation_processed', []);
        if (!is_array($processed)) $processed = [];
        $processed[$sku_id] = true;
        WC()->session->set('ample_validation_processed', $processed);

        ample_connect_log("API-First: APPROVED and ADDED to Ample - proceeding with WooCommerce");
    }
    // --- REPLACEMENT END ---



    return $passed;
}

// COMMENTED OUT: This hook is redundant since API-first validation already handles adding items to Ample
// The woocommerce_add_to_cart_validation hook now does everything this hook was doing, preventing duplicate API calls
// add_action('woocommerce_add_to_cart', 'custom_add_to_order', 10, 6);
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
    
    if (!$order_id) {
        ample_connect_log('No order_id available during add to cart');
        return;
    }

    // NOTE: This function is no longer called since the hook is commented out
    // API-first validation in woocommerce_add_to_cart_validation handles everything
    ample_connect_log("WARNING: custom_add_to_order() should not be called - hook was commented out for optimization");

    // Traditional flow - check if item exists in current order and add if not
    $order_items = Ample_Session_Cache::get('order_items', []);
    
    try {
        $found_in_cart = false;
        foreach ($order_items as $a_item) {
            if (isset($a_item['sku_id']) && $a_item['sku_id'] == $sku_id) {
                $found_in_cart = true;
                break;
            }
        }

        if (!$found_in_cart) {
            ample_connect_log("Adding item to Ample order: SKU {$sku_id}, Qty: {$quantity}");
            add_to_order($order_id, $sku_id, $quantity);
            
            // No longer needed - tracking removed with redundant hook optimization
        } else {
            ample_connect_log("Item {$sku_id} already exists in Ample order, skipping addition");
        }
    } catch (Exception $e) {
        ample_connect_log('Exception during add to order: ' . $e->getMessage());
    }
}

// COMMENTED OUT: Tracking system no longer needed since we removed the redundant hook
/**
 * Simple tracker for items processed during API-first validation
 * This prevents duplicate API calls between validation and cart addition phases
 */
/*
class Ample_Validation_Tracker {
    private static $processed_items = array();
    
    public static function mark_processed($sku_id, $quantity) {
        $item_key = $sku_id . '_' . $quantity;
        self::$processed_items[$item_key] = true;
    }
    
    public static function is_processed($sku_id, $quantity) {
        $item_key = $sku_id . '_' . $quantity;
        return isset(self::$processed_items[$item_key]);
    }
    
    public static function clear_all() {
        self::$processed_items = array();
    }
}

// Helper function to track items processed during validation
function mark_validation_item_processed($sku_id, $quantity) {
    Ample_Validation_Tracker::mark_processed($sku_id, $quantity);
}
*/




add_action('woocommerce_cart_item_removed', 'ample_cart_updated', 10, 2);
function ample_cart_updated($cart_item_key, $cart) {
    $line_item = $cart->removed_cart_contents[$cart_item_key] ?? null;
    if (!$line_item) {
        ample_connect_log("Cart item not found in removed_cart_contents for key: $cart_item_key");
        return;
    }

    $product = wc_get_product($line_item['variation_id'] ?: $line_item['product_id']);
    if (!$product) {
        ample_connect_log("Product not found for cart item key: $cart_item_key");
        return;
    }

    $sku_id = explode("-", $product->get_sku())[1] ?? null;
    if (!$sku_id) {
        ample_connect_log("No SKU ID extracted for product ID: " . $product->get_id());
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
                ample_connect_log('API call failed: ' . $response->get_error_message());
            } else {
                ample_connect_log('API call succeeded: ' . wp_remote_retrieve_body($response));
                
                // Update session directly with API response data
                if (is_array($response) && !is_wp_error($response)) {
                    $user_id = get_current_user_id();
                    store_current_order_to_session($response, $user_id);
                }
            }
        } else {
            ample_connect_log("No matching order item found for SKU: $sku_id");
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
    // error_log('Ample Connect: Alternative hook fired - cart_item_key: ' . $cart_item_key . ', quantity: ' . $quantity . ', old_quantity: ' . $old_quantity);
    
    ample_connect_log("cart_item_quantity_update_ample_after");
    // Skip if quantity didn't actually change
    if ($quantity === $old_quantity) {
        return;
    }


    $cart_item = $cart->get_cart_item($cart_item_key);
    if (!$cart_item) {
        ample_connect_log('Ample Connect: No cart item found for key: ' . $cart_item_key);
        return;
    }

    $product_id = $cart_item['variation_id'] ? $cart_item['variation_id'] : $cart_item['product_id'];
    $product = wc_get_product($product_id);
    if (!$product) {
        ample_connect_log('Ample Connect: No product found for ID: ' . $product_id);
        return;
    }

    // Get the SKU ID for the product or variation
    $sku = $product->get_sku();
    $sku_id = '';
    if (!empty($sku) && strpos($sku, '-') !== false) {
        $sku_id = explode("-", $sku)[1];
    }

    if (empty($sku_id)) {
        ample_connect_log('Ample Connect: No valid SKU ID found for product ' . $product_id);
        return;
    }

    // Check if this quantity change was already handled during add-to-cart validation
    $processed = WC()->session->get('ample_validation_processed', []);
    if (!empty($processed[$sku_id])) {
        // clear marker and skip API call (it was already handled during validation)
        unset($processed[$sku_id]);
        WC()->session->set('ample_validation_processed', $processed);
        ample_connect_log("Skipping change_item_quantity: already handled in validation for SKU {$sku_id}");
        return;
    }

    $order_id = Ample_Session_Cache::get('order_id');
    $order_items = Ample_Session_Cache::get('order_items');
    
    if (!$order_id || !$order_items) {
        ample_connect_log('Ample Connect: No order ID or order items found in session');
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
        ample_connect_log('Ample Connect: No order item found for SKU ' . $sku_id);
        return;
    }

    // Update quantity in Ample
    ample_connect_log('Ample Connect: Alternative hook - Attempting to update quantity for SKU ' . $sku_id . ' from ' . $old_quantity . ' to ' . $quantity);
    ample_connect_log('Ample Connect: Alternative hook - Order ID: ' . $order_id . ', Order Item ID: ' . $order_item_id);
    $response = change_item_quantity($order_id, $order_item_id, $quantity);
    // ample_connect_log($response);
    
    if (is_wp_error($response)) {
        ample_connect_log('Ample Connect: Alternative hook - API call failed: ' . $response->get_error_message());
        wc_add_notice('Failed to update quantity in Ample system. Please try again.', 'error');
    } else {
        ample_connect_log('Ample Connect: Alternative hook - Quantity updated successfully for SKU ' . $sku_id . ' to ' . $quantity);
        ample_connect_log('Ample Connect: Alternative hook - API Response: ' . print_r($response, true));
        
        // Update session directly with API response data
        if (is_array($response) && !is_wp_error($response)) {
            $user_id = get_current_user_id();
            store_current_order_to_session($response, $user_id);
        }
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


