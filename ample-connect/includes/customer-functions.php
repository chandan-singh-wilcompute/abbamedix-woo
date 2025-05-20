<?php
if (!defined('ABSPATH')) {
    exit;
}

// Function to get purchasable products for a patient/customer/client
function get_purchasable_products() {

    // Get the currently logged-in user's ID
    $user_id = get_current_user_id();
    // Get the client id of the customer
    $client_id = get_user_meta($user_id, "client_id", true);

    if (!$client_id) {
        return array();
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
    
    return $allowed_skus;
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

// Function to ger an Order Details
function get_order_id_from_api() {

    $user_id = get_current_user_id();

    // Get the client id of the customer
    $client_id = get_user_meta($user_id, "client_id", true);

    if (!$client_id) {
        return array();
    }
    
    $body = array("client_id" => $client_id); 

    $data = ample_request(AMPLE_CONNECT_API_BASE_URL . "/v1/portal/orders/current_order", 'GET', $body);
    $tax_data = array();
    if (isset($data['taxes']) && is_array($data['taxes'])) {
        $tax_type = array_key_first($data['taxes']);

        // Get the corresponding value
        $tax_val = ((float)$data['taxes'][$tax_type]) / 100;

        $tax_data["tax_type"] = $tax_type;
        $tax_data["tax_amount"] = $tax_val;
        // // Loop through all keys dynamically
        // foreach ($data['user'] as $key => $value) {
        //     echo ucfirst($key) . ": " . $value . "<br>";
        // }
    }
    // Store tax data in wc session
    store_tax_details_in_session( $tax_data );

    return $data ?? false;
}

// Function to add an item to order
function add_to_order($order_id, $sku_id, $quantity) {

    $user_id = get_current_user_id();
    // Get the client id of the customer
    $client_id = get_user_meta($user_id, 'client_id', true);
    $body = array('quantity' => $quantity, 'sku_id' => $sku_id, 'client_id' => $client_id);

    $url = AMPLE_CONNECT_PORTAL_URL . "/orders/{$order_id}/add_to_order"; 
    $data = ample_request($url, 'PUT', $body);
    return $data;

}

// Function to remove an item from order/cart
function remove_from_order($order_id, $order_item_id) {

    $user_id = get_current_user_id();
    // Get the client id of the customer
    $client_id = get_user_meta($user_id, 'client_id', true);

    $body = array('order_item_id' => $order_item_id, 'client_id' => $client_id);

    $url = AMPLE_CONNECT_PORTAL_URL . "/orders/{$order_id}/remove_from_order";
    $data = ample_request($url, 'PUT', $body);
    return $data;
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

function store_tax_details_in_session( $tax_data ) {
    if ( WC()->session ) {
        WC()->session->__unset( 'custom_tax_data' );
        WC()->session->set( 'custom_tax_data', $tax_data );
    }
}

function apply_custom_tax_from_session( $cart ) {
    if ( is_admin() && ! defined( 'DOING_AJAX' ) ) {
        return;
    }
    $order_id = get_order_id_from_api();
    // Get tax data from session
    $tax_data = WC()->session->get( 'custom_tax_data' );

    if ( ! empty( $tax_data ) && isset( $tax_data['tax_amount'] ) ) {

        $tax_amount = floatval( $tax_data['tax_amount'] );
        $tax_label = isset( $tax_data['tax_type'] ) ? strtoupper(sanitize_text_field( $tax_data['tax_type'] )) : 'Custom Tax';

        // Remove previously added custom tax fees to avoid duplication
        foreach ( $cart->get_fees() as $fee_key => $fee ) {
            if ( $fee->name === $tax_label ) {
                unset( $cart->fees[$fee_key] );
            }
        }

        // Apply tax as a fee
        if ( $tax_amount > 0 ) {
            $cart->add_fee( $tax_label, $tax_amount, true );
        }
    }
}
add_action( 'woocommerce_cart_calculate_fees', 'apply_custom_tax_from_session' );

function clear_custom_tax_session() {
    if ( WC()->session ) {
        WC()->session->__unset( 'custom_tax_data' );
    }
}
add_action( 'woocommerce_thankyou', 'clear_custom_tax_session' );


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