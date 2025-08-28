<?php
if (!defined('ABSPATH')) {
    exit;
}

// Function to register a customer on ample
function register_patient_on_ample($patient_data) {
    // ample_connect_log("register patient ample called");
    $body = array (
        "language_id" => "EN",
        "registration_attributes" => [
            "first_name" => $patient_data['first_name'],
            "middle_name" => $patient_data['middle_name'],
            "last_name" => $patient_data['last_name'],
            "telephone_1" => "555-555-5555",
            "date_of_birth" => $patient_data['date_of_birth'],
            "email" => $patient_data['email'],
            "status" => 'Lead'
        ],
        "password" => $patient_data['password'],
        "password_confirmation" => $patient_data['password']
    );

    // ample_connect_log("Body contents = ");
    // ample_connect_log($body);
    
    $response = ample_request(AMPLE_CONNECT_CLIENTS_URL, 'POST', $body);

    return $response;
}

// Function to update other details of patient on ample 
function update_registration_details_on_ample($client_id, $active_reg_id, $reg_data) {

    // ample_connect_log("update registration details ample called");
    $api_url = AMPLE_CONNECT_CLIENTS_URL . "/$client_id/registrations/$active_reg_id";

    $response = ample_request($api_url, 'PUT', $reg_data);

    return $response;
}

// Function to get purchasable products for a patient/customer/client
function get_purchasable_products_and_store_in_session($user_id = "") {

    // Get the currently logged-in user's ID
    if ($user_id == "")
        $user_id = get_current_user_id();
    
    // Get the client id of the customer
    $client_id = get_user_meta($user_id, "client_id", true);

    if (!$client_id) {
        return array();
    }

    if (Ample_Session_Cache::has('purchasable_products')) {
        return true;
    }

    $purchasable_products_url = AMPLE_CONNECT_WOO_CLIENT_URL . $client_id . '/purchasable_products';
    
    $products = ample_request($purchasable_products_url);

    if (empty($products)) {
        return array();
    }

    $allowed_skus = array();
    foreach ($products as $product) {
        $allowed_skus[] = 'sku-' . $product['id'];
    }

    Ample_Session_Cache::set('purchasable_products', $allowed_skus);

    return true;
}

// Function to get Prescription details
function get_prescription_details($client_id = null) {

    if ($client_id == null) {
        // Get the currently logged-in user's ID
        $user_id = get_current_user_id();
        // Get the client id of the customer
        $client_id = get_user_meta($user_id, "client_id", true);
    }
    

    if (!$client_id) {
        return array();
    }

    $clients_url = AMPLE_CONNECT_WOO_CLIENT_URL . '/' . $client_id;
    
    $client_data = ample_request($clients_url);

    if (!isset($client_data['prescriptions'])) {
        return array();
    }

    $prescription_data = $client_data['prescriptions'];
    return $prescription_data;
}

// Function to get registration details
function get_registration_details($client_id = null, $registration_id = null) {

    if ($registration_id == null) {
        return array();
    }

    if ($client_id == null) {
        // Get the currently logged-in user's ID
        $user_id = get_current_user_id();
        // Get the client id of the customer
        $client_id = get_user_meta($user_id, "client_id", true);
    }

    if (!$client_id) {
        return array();
    }
    
    $api_url = AMPLE_CONNECT_CLIENTS_URL . '/' . $client_id . '/registrations' . '/' . $registration_id;
    
    $registration_data = ample_request($api_url, 'GET');
    return $registration_data;
}

// Function to update registration details
function update_registration_details($client_id, $registration_id, $reg_data) {

    if ($registration_id == null) {
        return array();
    }

    if ($client_id == null) {
        return array();
    }

    if ($reg_data == null) {
        return array();
    }
    
    $api_url = AMPLE_CONNECT_CLIENTS_URL . '/' . $client_id . '/registrations' . '/' . $registration_id;
    
    $registration_data = ample_request($api_url, 'PUT', $reg_data);
    return $registration_data;
}

// Function to get shipping rates and store in wc session
function get_shipping_rates_and_store_in_session($user_id = "") {

    // If cart is empty, don't call API
    if ( WC()->cart->is_empty() ) {
        Ample_Session_Cache::delete('custom_shipping_rates'); // optional: clear stored rates
        return;
    }

    // Get the currently logged-in user's ID
    if ($user_id == "")
        $user_id = get_current_user_id();
    
    // Get the client id of the customer
    $client_id = get_user_meta($user_id, "client_id", true);

    if (!$client_id) {
        return array();
    }

    if (!Ample_Session_Cache::has('order_id')) {
        get_order_from_api_and_update_session($user_id);
    }

    $order_id = Ample_Session_Cache::get('order_id');

    $url = AMPLE_CONNECT_PORTAL_URL . "/orders/{$order_id}/shipping_rates"; 
    $api_url = add_query_arg(['client_id' => $client_id], $url);

    $data = ample_request($api_url);

    // echo '<pre>';
    // echo 'shipping rates api data';
    // print_r($data);
    // echo '</pre>';
    $shipping_options = [];
    if(is_array($data) && !array_key_exists("error", $data)) {
        $shipping_options = array_merge(...array_values($data));
    }
    
    // ample_connect_log("Shipping methods - \n");
    // ample_connect_log($shipping_options);

    Ample_Session_Cache::set('custom_shipping_rates', $shipping_options);

    // return $shipping_options;
    return;
}

// Function to ger an Order Details
function get_order_from_api_and_update_session($user_id = "") {

    if ($user_id == "")
        $user_id = get_current_user_id();

    // Get the client id of the customer
    $client_id = get_user_meta($user_id, "client_id", true);

    if (!$client_id) {
        return array();
    }

    // ample_connect_log("Get order id from api user id: " . $user_id . " client id: " . $client_id);

    $api_url = add_query_arg(array('client_id' => $client_id), AMPLE_CONNECT_API_BASE_URL . '/v1/portal/orders/current_order');
    $data = ample_request($api_url, 'GET');
    
    store_current_order_to_session($data, $user_id);

    return true;
}

function store_current_order_to_session($data, $user_id) {
    // Retrive order_id and store it in wc session

    $current_order_id = Ample_Session_Cache::get('order_id', false);

    if (!$current_order_id) {
        if ( !WC()->cart->is_empty() ) {
            clear_customer_cart($user_id);
        }
    } else if ($current_order_id != $data['id']) {
        clear_customer_cart($user_id);
    }

    Ample_Session_Cache::set('order_id', $data['id']);

    // Retrive tax data to store in session
    $tax_data = array();
    if (isset($data['taxes']) && is_array($data['taxes'])) {

        $tax_data = $data['taxes'];
    }

    // Store tax data in wc session
    Ample_Session_Cache::set('custom_tax_data', $tax_data);

    // Retrieve applicable_discount_codes
    $applicable_discounts = [];
    if (isset($data['applicable_discount_codes']) && is_array($data['applicable_discount_codes'])) {
        foreach ($data['applicable_discount_codes'] as $discount) {
            if (isset($discount['description'], $discount['dollar'])) {
                $description = trim($discount['description']);
                // Normalize code
                if (!empty($discount['code'])) {
                    $code = $discount['code'];
                } else {
                    // Convert description to lowercase, remove special chars, replace spaces with underscores
                    $code = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '_', $description));
                }
                $applicable_discounts[] = [
                    'id'           => $discount['id'],
                    'code'        => $code,
                    'description' => $description,
                    'amount' => $discount['dollar']
                ];
            }
        }
    }
    // Store Applicable Discounts in wc session
    Ample_Session_Cache::set('applicable_discounts', $applicable_discounts);


    // Retrieve applied_discounts
    $applied_discount_codes = [];
    if (isset($data['discounts']) && is_array($data['discounts'])) {
        foreach ($data['discounts'] as $discount) {
            $applied_discount_codes[$discount['discount_code_id']] = $discount['id'];
        }
    }
    // Store Applied Discounts in wc session
    Ample_Session_Cache::set('applied_discount_codes', $applied_discount_codes);

    // $applied_disc = Ample_Session_Cache::get('applied_discounts', []);
    $applied_pol = Ample_Session_Cache::get('applied_policies', []);
    // Retrieve policy events
    $policy_events = [];
    if (isset($data['policy_events']) && is_array($data['policy_events'])) {
        foreach ($data['policy_events'] as $policy_event) {
            $policy_events[$policy_event['policy_id']] = $policy_event['amount_used'];
        }
    }
    // Store Applied Discounts in wc session
    // Ample_Session_Cache::set('policy_events', $policy_events);

    // Retrieve applicable_policies
    $applicable_policies = [];
    if (isset($data['applicable_policies']) && is_array($data['applicable_policies'])) {
        foreach ($data['applicable_policies'] as $policy) {
            if (isset($policy['enabled']) && $policy['enabled'] == true) {
                $name = trim($policy['name']);
                
                $applicable_policies[] = [
                    'id'           => $policy['id'],
                    'name' => $name,
                    'percent' => $policy['percentage_discount'],
                    'covers_shipping' => $policy['covers_shipping']
                ];
                if (empty($policy_events)) {
                    Ample_Session_Cache::delete('applied_policies');
                }
                else if (array_key_exists($policy['id'], $policy_events)) {
                    if (empty($applied_pol) || !in_array($policy['id'], array_column($applied_pol, 'id'))) {
                        $applied_pol[] = [
                            'id' => $policy['id'],
                            'amount' => 0,
                            'percentage' => $policy['percentage_discount'],
                            'type' => 'policy',
                            'desc' => $name . " (" . $policy['percentage_discount'] . "% off)"
                        ];
                        Ample_Session_Cache::set('applied_policies', $applied_pol);
                    }
                }
            }
        }
    }
    // Store Applicable Discounts in wc session
    Ample_Session_Cache::set('applicable_policies', $applicable_policies);
    // Ample_Session_Cache::set('applied_discounts', $new_discounts);
    

    if (isset($data['shipping_rate'])) {
        Ample_Session_Cache::set('applied_shipping_rate', $data['shipping_rate']['id']);
    } else {
        Ample_Session_Cache::set('applied_shipping_rate', '');
    }

    // Retrieve policy breakdown
    if (isset($data['policy_breakdown']) && is_array($data['policy_breakdown'])) {
        $policy_data = [];
        $breakdown = $data['policy_breakdown'];

        $fields_to_extract = [
            'name',
            'max_amount',
            'percentage_discount',
            'covers_shipping',
            'per_gram_limit',
            'enabled',
            'unlimited',
            'remaining_amount_for_current_period'
        ];

        foreach ($fields_to_extract as $field) {
            if (isset($breakdown[$field])) {
                $policy_data[$field] = $breakdown[$field];
            }
        }

        // Store policy details in wc session
        Ample_Session_Cache::set('policy_details', $policy_data);
    }

    // Retrieve order-items in wc session
    $order_items = $data['order_items'];
    // Store policy details in wc session
    Ample_Session_Cache::set('order_items', $order_items);

    if (!empty($order_items)) {
        // Loop through WooCommerce cart items
        // Build SKU lookup array once
        $cart_sku_map = [];
        foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
            $product   = $cart_item['data'];
            $sku        = (string) $product->get_sku(); // normalize to string
            $sku_array = explode("-", $sku);
            $sku_id = $sku_array[1];
            $cart_sku_map[$sku_id] = $cart_item_key;   // map sku_id → cart item key
        }

        // Now you can efficiently check API items
        foreach ( $order_items as $order_item ) {
            $api_sku_id = (string) $order_item['sku_id']; // normalize to string

            if ( isset($cart_sku_map[$api_sku_id]) ) {
                $cart_item_key = $cart_sku_map[$api_sku_id];

                // Apply discount or whatever you need
                if(!empty($order_item['discounts'])) {
                    WC()->cart->cart_contents[$cart_item_key]['api_discounts'] = $order_item['discounts'];
                    WC()->cart->set_session();
                } 
                else {
                    if ( isset( WC()->cart->cart_contents[$cart_item_key]['api_discounts'] ) ) {
                        unset( WC()->cart->cart_contents[$cart_item_key]['api_discounts'] );
                    }
                }
            }
        }

        
    }
    // foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
    //     ample_connect_log("API Discount -- ");
    //     ample_connect_log($cart_item_key);
    //     if ( isset( WC()->cart->cart_contents[$cart_item_key]['api_discounts'] ) ) {
    //         ample_connect_log(WC()->cart->cart_contents[$cart_item_key]['api_discounts']);
    //     }
    // }
    // // ✅ Save order in session
    // Ample_Session_Cache::set('current_order_data', $data);

    return true;
}

// Function to add an item to order
function add_to_order($order_id, $sku_id, $quantity) {

    // Who called me?
    $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2); 
    // [0] = this function, [1] = caller
    if (isset($trace[1])) {
        $caller = $trace[1];
        $caller_info = (isset($caller['class']) ? $caller['class'] . '::' : '') . $caller['function'] . '()';
        ample_connect_log("Add to order was called by: " . $caller_info);
    }

    $user_id = get_current_user_id();
    // Get the client id of the customer
    $client_id = get_user_meta($user_id, 'client_id', true);

    $url = AMPLE_CONNECT_PORTAL_URL . "/orders/{$order_id}/add_to_order";
    $body = array(
        'quantity' => $quantity,
        'sku_id' => $sku_id,
        'client_id' => $client_id
    );
    $data = ample_request($url, 'PUT', $body);
    
    // if (is_array($data) && array_key_exists('id', $data)) {
    //     Client_Information::fetch_information();
    //     store_current_order_to_session($data, $user_id);
    //     // get_shipping_rates_and_store_in_session();
    // }
    
    return $data;
}

function change_item_quantity($order_id, $order_item_id, $updated_quantity) {

    // Who called me?
    $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2); 
    // [0] = this function, [1] = caller
    if (isset($trace[1])) {
        $caller = $trace[1];
        $caller_info = (isset($caller['class']) ? $caller['class'] . '::' : '') . $caller['function'] . '()';
        ample_connect_log("change item quantity was called by: " . $caller_info);
    }

    $user_id = get_current_user_id();
    $client_id = get_user_meta($user_id, 'client_id', true);
    
    if (!$client_id) {
        return new WP_Error('no_client_id', 'Client ID not found for user');
    }

    // Build URL with client_id as query parameter
    // $url = add_query_arg(
    //     array('client_id' => $client_id),
    //     AMPLE_CONNECT_PORTAL_URL . "/orders/{$order_id}/change_item_quantity"
    // );

    $url = AMPLE_CONNECT_PORTAL_URL . "/orders/{$order_id}/change_item_quantity";
    // Send order_item_id and quantity in request body as expected by the API
    $body = array(
        'order_item_id' => $order_item_id,
        'quantity' => $updated_quantity,
        'client_id' => $client_id
    );

    $data = ample_request($url, 'PUT', $body);

    // if (is_array($data) && array_key_exists('id', $data)) {
    //     Client_Information::fetch_information();
    //     store_current_order_to_session($data, $user_id);
    //     // get_shipping_rates_and_store_in_session();
    // }
    
    return $data;
}

// Function to remove an item from order/cart
function remove_from_order($order_id, $order_item_id) {

    $user_id = get_current_user_id();
    // Get the client id of the customer
    $client_id = get_user_meta($user_id, 'client_id', true);

    if (!$client_id) {
        return new WP_Error('no_client_id', 'Client ID not found for user');
    }

    $url = AMPLE_CONNECT_PORTAL_URL . "/orders/{$order_id}/remove_from_order";
    $body = array(
        'order_item_id' => $order_item_id,
        'client_id' => $client_id
    );
    $data = ample_request($url, 'PUT', $body);
    
    if (is_wp_error($data)) {
        return $data;
    }
    
    // if (is_array($data) && array_key_exists('id', $data)) {
    //     Client_Information::fetch_information();
    //     store_current_order_to_session($data, $user_id);
    //     // get_shipping_rates_and_store_in_session();
    // }
    return $data;
}

// Function to apply a discount
function add_discount_to_order($discount_id) {

    $user_id = get_current_user_id();
    // Get the client id of the customer
    $client_id = get_user_meta($user_id, 'client_id', true);
    $order_id = Ample_Session_Cache::get('order_id');
    $url = AMPLE_CONNECT_PORTAL_URL . "/orders/{$order_id}/apply_discount_code";
    $body = array(
        'discount_code_id' => $discount_id,
        'client_id' => $client_id
    );
    $data = ample_request($url, 'PUT', $body);
    if (is_array($data) && array_key_exists('id', $data)) {
        store_current_order_to_session($data, $user_id);
        // get_shipping_rates_and_store_in_session();
    }
    
    return $data;
}

// Function to remove a discount
function remove_discount_from_order($discount_id) {

    $user_id = get_current_user_id();
    // Get the client id of the customer
    $client_id = get_user_meta($user_id, 'client_id', true);
    $order_id = Ample_Session_Cache::get('order_id');
    $url = AMPLE_CONNECT_PORTAL_URL . "/orders/{$order_id}/remove_discount";
    $body = array(
        'discount_id' => $discount_id,
        'client_id' => $client_id
    );
    $data = ample_request($url, 'PUT', $body);
    if (is_array($data) && array_key_exists('id', $data)) {
        store_current_order_to_session($data, $user_id);
        // get_shipping_rates_and_store_in_session();
    }
    
    return $data;
}

// Function to apply policy 
function add_policy_to_order($policy_id) {

    $user_id = get_current_user_id();
    // Get the client id of the customer
    $client_id = get_user_meta($user_id, 'client_id', true);
    $order_id = Ample_Session_Cache::get('order_id');
    $url = AMPLE_CONNECT_PORTAL_URL . "/orders/{$order_id}/apply_policy";
    $body = array(
        'policy_id' => $policy_id,
        'client_id' => $client_id
    );
    $data = ample_request($url, 'PUT', $body);
    if (is_array($data) && array_key_exists('id', $data)) {
        ample_connect_log("After policy apply, storig current order");
        store_current_order_to_session($data, $user_id);
        // get_shipping_rates_and_store_in_session();
    }
    // ample_connect_log("add policy url = " . $url . " policy id: " . $policy_id);
    // ample_connect_log($data);
    return $data;
}

// Function to remove policy 
function remove_policy_from_order($policy_id) {

    $user_id = get_current_user_id();
    // Get the client id of the customer
    $client_id = get_user_meta($user_id, 'client_id', true);
    $order_id = Ample_Session_Cache::get('order_id');
    $url = AMPLE_CONNECT_PORTAL_URL . "/orders/{$order_id}/remove_policy";
    $body = array(
        'policy_id' => $policy_id,
        'client_id' => $client_id
    );
    $data = ample_request($url, 'PUT', $body);
    if (is_array($data) && array_key_exists('id', $data)) {
        store_current_order_to_session($data, $user_id);
        // get_shipping_rates_and_store_in_session();
    }
    
    return $data;
}

// Function to call Ample order purchase api
function purchase_order_on_ample($body=[]) {

    $user_id = get_current_user_id();
    // Get the client id of the customer
    $client_id = get_user_meta($user_id, "client_id", true);
    if (!$client_id) {
        return false;
    }

    $user_info = get_userdata($user_id);

    if (!empty($user_info->first_name) || !empty($user_info->last_name)) {
        $name = trim($user_info->first_name . ' ' . $user_info->last_name);
    } else {
        $name = $user_info->display_name;
    }

    // $order = get_order_id_from_api();
    $order_id = Ample_Session_Cache::get('order_id');

    // ample_connect_log("Order Id: " . $order_id);
    $api_url = AMPLE_CONNECT_PORTAL_URL . "/orders/{$order_id}/purchase"; 
    
    if ($body == []) {
        $body = array(
            'placed_by' => $name,
            "extra_info" => "VAC policy applied",
            'client_id' => $client_id
        );
    } else {
        $body['client_id'] = $client_id;
    }
    
    $response = ample_request($api_url, 'POST', $body);
    $response["ample_order_id"] = $order_id;

    return $response;
}

// Getting product ids from SKUs
function get_product_ids_by_skus($skus) {
    global $wpdb;
    $placeholders = implode(',', array_fill(0, count($skus), '%s'));
    $query = $wpdb->prepare("
        SELECT p.ID
        FROM {$wpdb->posts} p
        INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
        WHERE p.post_type = 'product'
        AND p.post_status = 'publish'
        AND pm.meta_key = '_sku'
        AND pm.meta_value IN ($placeholders)
    ", $skus);

    return $wpdb->get_col($query);
}

// Getting All products available in Woocommerce
function get_all_product_ids() {
    $args = array(
        'post_type' => 'product',
        'fields' => 'ids',
        'posts_per_page' => -1,
    );
    $query = new WP_Query($args);
    return $query->posts;
}

// Function to send credit card token to AO
function add_credit_card_token($temp_token, $street_name, $street_number, $postal_code) {
    // Get the currently logged-in user's ID
    $user_id = get_current_user_id();
    // Get the client id of the customer
    $client_id = get_user_meta($user_id, 'client_id', true);

    $body = array('dataKey' => $temp_token, 'street_name' => $street_name, 'street_number' => $street_number, 'postal_code' => $postal_code, 'client_id' => $client_id);

    $url = AMPLE_CONNECT_PORTAL_URL . "/credit_card_tokens/create_with_temp_token";
    $response_data = ample_request($url, 'POST', $body);
    if ($response_data) {
        return true;
    }

    return false;
}

// Function to delete a credit card token from AO
function remove_credit_card_token($card_id) {
    // Get the currently logged-in user's ID
    $user_id = get_current_user_id();
    // Get the client id of the customer
    $client_id = get_user_meta($user_id, 'client_id', true);

    $body = array('client_id' => $client_id);

    $url = AMPLE_CONNECT_PORTAL_URL . "/credit_card_tokens/{$card_id}";
    $response_data = ample_request($url, 'DELETE', $body);
    if ($response_data) {
        return true;
    }

    return false;
}

// function apply_custom_tax_from_session( $cart ) {
//     if ( is_admin() && ! defined( 'DOING_AJAX' ) ) {
//         return;
//     }

//     $custom_taxes = Ample_Session_Cache::get('custom_tax_data');
//     if (!empty($custom_taxes) && is_array($custom_taxes)) {
//         foreach ($custom_taxes as $tax_label => $amount_cents) {
//             $amount_dollars = floatval($amount_cents) / 100;

//             // Add a fee labeled as tax
//             $cart->add_fee(ucfirst($tax_label), $amount_dollars, false); // true => taxable
//         }
//     }

//     // // Get tax data from session (old)
//     // $tax_data = WC()->session->get( 'custom_tax_data' );
//     // // ample_connect_log("tax data 2");
//     // // ample_connect_log(print_r($tax_data, true));

//     // if ( ! empty( $tax_data ) && isset( $tax_data['tax_amount'] ) ) {

//     //     $tax_amount = floatval( $tax_data['tax_amount'] );
//     //     $tax_label = isset( $tax_data['tax_type'] ) ? strtoupper(sanitize_text_field( $tax_data['tax_type'] )) : 'Custom Tax';

//     //     // Remove previously added custom tax fees to avoid duplication
//     //     foreach ( $cart->get_fees() as $fee_key => $fee ) {
//     //         if ( $fee->name === $tax_label ) {
//     //             unset( $cart->fees[$fee_key] );
//     //         }
//     //     }

//     //     // Apply tax as a fee
//     //     if ( $tax_amount > 0 ) {
//     //         $cart->add_fee( $tax_label, $tax_amount, true );
//     //     }
//     // }
// }
// add_action( 'woocommerce_cart_calculate_fees', 'apply_custom_tax_from_session', 10 );

function refresh_order_cached_data() {
    get_order_from_api_and_update_session();
}
add_action( 'woocommerce_thankyou', 'refresh_order_cached_data' );


// function display_custom_tax_debug_info() {
//     $tax_data = WC()->session->get( 'custom_tax_data' );

//     if ( ! empty( $tax_data ) && isset( $tax_data['tax_amount'] ) ) {
//         $tax_amount = floatval( $tax_data['tax_amount'] );
//         $tax_label = isset( $tax_data['tax_type'] ) ? sanitize_text_field( $tax_data['tax_type'] ) : 'Custom Tax';
//         $cart_subtotal = WC()->cart->subtotal;
//         $tax_value = $cart_subtotal * ( $tax_amount / 100 );

//         echo "<tr class='custom-tax-debug'>
//                 <th>Debug: {$tax_label} ({$tax_amount}%)</th>
//                 <td>" . wc_price( $tax_value ) . "</td>
//             </tr>";
//     }
// }
// add_action( 'woocommerce_review_order_before_order_total', 'display_custom_tax_debug_info' );



