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

    $response = wp_remote_request($endpoint, $args);

    return $response;
}

// Handle API response and check token expiry
function handle_response($response, $log = false) {
    // if ($log)
    //     ample_connect_log($response, true);

    ample_connect_log($response);
    if (is_wp_error($response)) {
        return ['error' => $response->get_error_message()];
    }

    $body = json_decode(wp_remote_retrieve_body($response), true);
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
    if ( ! $customer_id ) {


        return 0;
    }

    // Delete persistent cart stored in user meta
    delete_user_meta( $customer_id, '_woocommerce_persistent_cart_1' );

    // Delete session from WooCommerce session table
    global $wpdb;

    $table = $wpdb->prefix . 'woocommerce_sessions';

    // Delete session based on user ID (stored as session_key)
    $wpdb->delete(
        $table,
        array( 'session_key' => $customer_id ),
        array( '%s' )
    );
    return 1;
}

