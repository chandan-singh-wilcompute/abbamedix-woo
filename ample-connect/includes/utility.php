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
function ample_request($endpoint, $method = 'GET', $data = [], $headers = [], $log = false) {
    // echo '<pre>';
    // echo print_r($endpoint);
    // echo '</pre>';
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
        my_debug_log("New Request Arrived - ");
        my_debug_log("EndPoint: " . $endpoint);
        my_debug_log($data);
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
    if ($log)
        my_debug_log($response);

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