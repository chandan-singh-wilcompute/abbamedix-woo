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
                if (!empty($response['order_items'][0]['discounts'])) {
                    $discounts = $response['order_items'][0]['discounts'];
                    if (!empty($discounts)) {
                        WC()->cart->cart_contents[ $cart_item_key ]['discount_percentage'] = $discounts[0];
                    }
                }
                error_log('API call succeeded: ' . wp_remote_retrieve_body($response));
            }
        } catch (Exception $e) {
            error_log('Exception during add to order: ' . $e->getMessage());
            // Don't remove from cart on exception
        }
    } else {
        error_log('No order_id available during add to cart');
    }
}

add_action('woocommerce_remove_cart_item', 'ample_cart_updated', 10, 2);
function ample_cart_updated($cart_item_key, $cart) {
    $line_item = $cart->removed_cart_contents[$cart_item_key];
    $product = wc_get_product($line_item['variation_id'] ?: $line_item['product_id']);
    $sku_id = explode("-", $product->get_sku())[1];
    
    // $order = get_order_id_from_api();
    $order_id = Ample_Session_Cache::get('order_id');
    $order_items = Ample_Session_Cache::get('order_items');
    if($order_id) {
        $order_item_id = null;
        foreach ($order_items as $item) {
            if ($item['sku_id'] == $sku_id) {
                $order_item_id = $item['id'];
                break;
            }
        }

        if($order_item_id){
            $response = remove_from_order($order_id, $order_item_id);
            if (is_wp_error($response)) {
                error_log('API call failed: ' . $response->get_error_message());
            } else {
                error_log('API call succeeded: ' . wp_remote_retrieve_body($response));
            }
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


add_action( 'woocommerce_after_cart_item_quantity_update', 'cart_item_quantity_update_ample', 20, 4 );
function cart_item_quantity_update_ample($cart_updated) {
    foreach (WC()->cart->get_cart() as $cart_item_key => $cart_item) {
        $updated_quantity = $cart_item['quantity'];
        $product_id = $cart_item['variation_id'] ? $cart_item['variation_id'] : $cart_item['product_id'];
        $product = wc_get_product($product_id);

        // Get the SKU ID for the product or variation
        $sku = $product->get_sku();
        
        // If SKU is in the format with a hyphen, split and get the second part
        $sku_id = '';
        if (!empty($sku) && strpos($sku, '-') !== false) {
            $sku_id = explode("-", $sku)[1];
        }

        // $order = get_order_id_from_api();
        $order_id = Ample_Session_Cache::get('order_id');
        $order_items = Ample_Session_Cache::get('order_items');
        if($order_id){
            $order_item_id = null;
            foreach ($order_items as $item) {
                if ($item['sku_id'] == $sku_id) {
                    $order_item_id = $item['id'];
                    break;
                }
            }
        
            if($order_item_id){
                $response = change_item_quantity($order_id, $order_item_id, $updated_quantity);
                if (is_wp_error($response)) {
                    error_log('API call failed: ' . $response->get_error_message());
                } else {
                    error_log('API call succeeded: ' . wp_remote_retrieve_body($response));
                }
            }
        }
    }
}


function change_item_quantity($order_id, $order_item_id, $updated_quantity) {

    $url = AMPLE_CONNECT_PORTAL_URL . "/orders/{$order_id}/change_item_quantity";
    $body = array('order_item_id' => $order_item_id, 'quantity' => $updated_quantity);

    $data = ample_request($url, 'PUT', $body);
    if (array_key_exists('id', $data)) {
        store_current_order_to_session($data);
    }
    return $data;
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
add_action( 'woocommerce_before_calculate_totals', 'apply_discount_after_api', 10, 1 );
function apply_discount_after_api( $cart ) {
    // Loop through each item in the cart
    foreach ( $cart->get_cart() as $cart_item_key => $cart_item ) {
        // Check if discount data exists in the cart item
        if ( isset( $cart_item['discount_percentage'] ) ) {
            // Get the discount percentage from the cart item data
            $discount_percentage = $cart_item['discount_percentage'];
            
            // Get the original price of the cart item
            $original_price = $cart_item['data']->get_price();
            
            // Calculate the discount amount
            $discount_amount = ( (float)($original_price) * (float)$discount_percentage ) / 100;
            
            // Calculate the new price
            $new_price = $original_price - $discount_amount;
            
            // Set the new price for the cart item
            $cart_item['data']->set_price( $new_price );
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
