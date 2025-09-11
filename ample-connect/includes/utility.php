<?php
if (!defined('ABSPATH')) {
    exit;
}

// Get token for ample
function get_ample_api_token($expired = false) {
    if ($expired == true) {
        Ample_Token_Manager::get_instance()->set_token_expired($expired);
    }

    return Ample_Token_Manager::get_instance()->get_token();
}

// Handle API requests
function ample_request($endpoint, $method = 'GET', $data = [], $headers = [], $log = true) {
    // ample_connect_log("ample request called");
    // echo '<pre>';
    // echo print_r($endpoint);
    // echo '</pre>';

    // $backtrace = debug_backtrace();

    // if (isset($backtrace[1])) {
    //     $caller = $backtrace[1];

    //     $called_from_function = $caller['function'] ?? 'N/A';
    //     $called_from_file     = $caller['file'] ?? 'N/A';
    //     $called_from_line     = $caller['line'] ?? 'N/A';

    //     ample_connect_log("Called from function: " . $called_from_function . ", Called from file: " . $called_from_file . ", On line: " . $called_from_line);
    // } else {
    //     ample_connect_log("No caller found");
    // }

    $response = api_call($endpoint, $method, $data, $headers, $log);

    

    if ($log)
        $api_data = handle_response($response, true);
    else
        $api_data = handle_response($response);

    // echo '<pre>';
    // echo 'api response = ';
    // echo print_r($api_data);
    // echo '</pre>';

    if ($api_data == "Token_Expired") {
        // ample_connect_log("Token Expired");
        $response = api_call($endpoint, $method, $data, $headers, $log, true);

        if ($log)
            $api_data = handle_response($response, true);
        else
            $api_data = handle_response($response);

        return $api_data;
    } else {
        return $api_data;
    }
}

// Function to call API 
function api_call($endpoint, $method, $data, $headers, $log, $expired = false) {
    $token = get_ample_api_token($expired);

    if ($method != 'GET')
        $body = json_encode($data);
    else 
        $body = $data;

    if ($log) {
        ample_connect_log("New Request Arrived - ", true);
        ample_connect_log("EndPoint: " . $endpoint, true);
        //ample_connect_log($data, true);
    }

    $args = [
        'method'    => $method,
        'timeout'   => 300,
        'headers'   => array_merge($headers, [
            'Authorization' => 'Token ' . $token,
            'Content-Type'  => 'application/json',
        ]),
        'body'      => (!empty($body)) ? $body : null,
    ];

     // ⏱ Start timer
    $start_time = microtime(true);

    $response = wp_remote_request($endpoint, $args);
    
    // ⏱ End timer
    $end_time = microtime(true);
    $duration = round(($end_time - $start_time), 3); // seconds, 3 decimals

    if ($log) {
        ample_connect_log("API call took {$duration} seconds.", true);
    }
    
    return $response;
}

// Handle API response and check token expiry
function handle_response($response, $log = false) {
    // if ($log)
    //     ample_connect_log($response, true);

    // ample_connect_log($response);
    if (is_wp_error($response)) {
        return ['error' => $response->get_error_message()];
    }

    $content_type = wp_remote_retrieve_header($response, 'content-type');

    if (strpos($content_type, 'application/json') !== false) {
        $body = json_decode(wp_remote_retrieve_body($response), true);
    } else {
        // Return raw body (PDF, image, etc.)
        $body = wp_remote_retrieve_body($response);
    }

    // $body = json_decode(wp_remote_retrieve_body($response), true);
    $status = wp_remote_retrieve_response_code($response);

    // echo '<pre>';
    // echo print_r($status);
    // echo '</pre>';

    // Check if token is expired then refresh it
    if ($status == "401" || (isset($body['error_code']) && $body['error_code'] == 'misc.api_user_token.expired')) {
        // return ['error' => 'Token expired. Try again.'];
        return "Token_Expired";
    } else if (isset($body['error_code']) && $body['error_code'] == 'orders.not_found') {
        WC()->session->__unset('cached_order_data');
        $user_id = get_current_user_id();
        clear_customer_cart($user_id);
    } else if (isset($body['error_code']) && $body['error_code'] != "policies.apply_failed" && $body['error_code'] != "orders.cannot_modify_after_purchase") {
        wp_send_json_error([
            'message' => 'Document not found.',
            'error_code' => $body['error_code']
        ]);
    }

    return $body;
}


// Generate unique username
function custom_registration_generate_unique_username ($username)
{
    $original_username = $username;
    $counter = 1;

    while (username_exists($username)) {
        $username = $original_username . $counter;
        $counter++;
    }

    return $username;
}


// Function to clear cart to the particular customer
function clear_customer_cart( $customer_id ) {
    ample_connect_log("clear cart got called! customer id is: " . $customer_id);

    if ( ! $customer_id ) {
        return false;
    }

    // 1. Delete persistent cart (saved carts)
    $blog_id = get_current_blog_id();
    delete_user_meta( $customer_id, '_woocommerce_persistent_cart_' . $blog_id );

    // 2. Delete active session
    global $wpdb;
    $table = $wpdb->prefix . 'woocommerce_sessions';
    $wpdb->delete(
        $table,
        array( 'session_key' => (string) $customer_id ),
        array( '%s' )
    );

    // 3. Clear cart immediately if this is the current logged-in user
    if ( is_user_logged_in() && get_current_user_id() == $customer_id && function_exists('WC') && WC()->cart ) {
        WC()->cart->empty_cart();
        WC()->session->set('cart', []); // make sure it's flushed
    }

    return true;
}

function get_product_id_by_sku( $sku ) {
    global $wpdb;

    $product_id = wc_get_product_id_by_sku( $sku );

    return $product_id ? intval( $product_id ) : false;
}

/**
 * Parse Ample API error responses into user-friendly messages
 * 
 * @param string|null $error_code Ample error code
 * @param string $error_message Raw error message from Ample
 * @param array $response Full API response with constraints/limits
 * @return string User-friendly error message
 */
function parse_ample_error_message($error_code, $error_message, $response) {
    // Check for constraint information in the response
    $constraints = $response['constraints'] ?? [];
    
    // Parse by error code first (most specific)
    if ($error_code) {
        switch ($error_code) {
            // Order validation errors
            case 'orders.quantity_must_be_greater_than_zero':
                return 'Quantity must be greater than 0.';
                
            case 'orders.sku_not_found':
            case 'clients.sku_not_found':
                return 'The selected product is no longer available.';
                
            case 'orders.no_price_set_for_sku':
                return "This product doesn't have a price set. Please contact support.";
                
            // Inventory and availability errors  
            case 'orders.not_enough_product_available':
                if (isset($constraints['available_quantity'])) {
                    return "Only {$constraints['available_quantity']} units available for this product.";
                }
                return 'Not enough product available.';
                
            case 'orders.product_not_available':
                return 'This product is currently not available.';
                
            case 'orders.sku_discontinued':
                return 'This product has been discontinued.';
                
            // Gram limit errors
            case 'orders.grams_per_day_limit_reached':
                if (isset($constraints['grams_per_day_limit'])) {
                    return "Daily limit of {$constraints['grams_per_day_limit']} grams reached.";
                }
                return 'Daily gram limit reached.';
                
            case 'orders.monthly_gram_limit_reached':
                if (isset($constraints['monthly_gram_limit'])) {
                    return "Monthly limit of {$constraints['monthly_gram_limit']} grams reached.";
                }
                return 'Monthly gram limit reached.';
                
            case 'orders.remaining_grams_exceeded':
                if (isset($constraints['remaining_grams'])) {
                    return "Only {$constraints['remaining_grams']} grams remaining on your prescription.";
                }
                return 'Prescription gram limit exceeded.';
                
            case 'orders.total_grams_exceeds_limit':
                if (isset($constraints['max_grams']) && isset($constraints['current_grams'])) {
                    $remaining = $constraints['max_grams'] - $constraints['current_grams'];
                    return "Adding this item would exceed your limit. You have {$remaining} grams remaining.";
                }
                return 'Total gram limit would be exceeded.';
                
            // Bottle and container limits
            case 'orders.bottle_limit_reached':
                if (isset($constraints['bottle_limit'])) {
                    return "Maximum of {$constraints['bottle_limit']} bottles allowed per order.";
                }
                return 'Bottle limit reached for this order.';
                
            case 'orders.max_bottles_per_sku_reached':
                if (isset($constraints['max_bottles_per_sku'])) {
                    return "Maximum of {$constraints['max_bottles_per_sku']} bottles allowed for this product.";
                }
                return 'Maximum bottles per product reached.';
                
            case 'orders.container_limit_reached':
                return 'Container limit reached for this order.';
                
            // Prescription and authorization errors
            case 'orders.prescription_expired':
                if (isset($constraints['expiry_date'])) {
                    return "Your prescription expired on {$constraints['expiry_date']}. Please renew your prescription.";
                }
                return 'Your prescription has expired. Please renew to continue ordering.';
                
            case 'orders.prescription_not_active':
                return 'Your prescription is not active. Please contact support.';
                
            case 'orders.client_not_authorized':
                return 'You are not authorized to place orders. Please contact support.';
                
            case 'orders.client_account_suspended':
                return 'Your account has been suspended. Please contact support.';
                
            // Payment and pricing errors
            case 'orders.insufficient_funds':
                if (isset($constraints['available_balance'])) {
                    return "Insufficient funds. Available balance: \${$constraints['available_balance']}.";
                }
                return 'Insufficient funds in your account.';
                
            case 'orders.price_changed':
                if (isset($constraints['new_price'])) {
                    return "Product price has changed to \${$constraints['new_price']}. Please refresh and try again.";
                }
                return 'Product price has changed. Please refresh the page.';
                
            case 'orders.discount_no_longer_valid':
                return 'The applied discount is no longer valid.';
                
            // Order state errors
            case 'orders.already_placed':
                return 'This order has already been placed.';
                
            case 'orders.cannot_modify_after_purchase':
                return 'Cannot modify order after purchase.';
                
            case 'orders.order_locked':
                return 'This order is currently locked for processing.';
                
            case 'orders.order_cancelled':
                return 'This order has been cancelled.';
                
            // Category and product type restrictions
            case 'orders.category_not_allowed':
                if (isset($constraints['allowed_categories'])) {
                    return "This product category is not allowed. Allowed categories: " . implode(', ', $constraints['allowed_categories']);
                }
                return 'This product category is not allowed for your account.';
                
            case 'orders.product_type_restricted':
                return 'This product type is restricted for your account.';
                
            case 'orders.concentration_too_high':
                if (isset($constraints['max_concentration'])) {
                    return "Maximum allowed concentration is {$constraints['max_concentration']}%.";
                }
                return 'Product concentration exceeds your limit.';
                
            // Session and timing errors
            case 'orders.session_expired':
                return 'Your session has expired. Please refresh the page and try again.';
                
            case 'orders.too_many_requests':
                return 'Too many requests. Please wait a moment and try again.';
                
            // System and server errors
            case 'orders.system_error':
                return 'A system error occurred. Please try again later.';
                
            case 'orders.service_unavailable':
                return 'Service is temporarily unavailable. Please try again later.';
        }
    }
    
    // Fall back to original error message if no specific handling
    return $error_message ?: 'An error occurred while processing your request.';
}

// Helper function to get parsed error message from API response without triggering wp_send_json_error
function get_parsed_ample_error($response) {
    if (!is_array($response) || !isset($response['error_code'])) {
        return null;
    }
    
    return parse_ample_error_message(
        $response['error_code'], 
        $response['error_message'] ?? $response['message'] ?? null, 
        $response
    );
}
