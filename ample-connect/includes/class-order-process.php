<?php
if (!defined('ABSPATH')) {
    exit;
}
require_once plugin_dir_path(__FILE__) . '/customer-functions.php';

class  Order_Process {
    /* Class to hold and return Client's information
        related to orders
    */

    public static function purchase_order($card_token_id) {
        
        if (!is_user_logged_in()) {
            return false;
        }

        $user_id = get_current_user_id();
        // Get the client id of the customer
        $client_id = get_user_meta($user_id, "client_id", true);
        if (!$client_id) {
            return false;
        }

        $order = get_order_id_from_api();
        $order_id = $order['id'];
        // my_debug_log("Order Id: " . $order_id);
        $api_url = AMPLE_CONNECT_PORTAL_URL . "/orders/{$order_id}/purchase"; 
        
        $body = array(
            'credit_card_token_id' => $card_token_id,
            'payment_type' => 'credit_card',
            'client_id' => $client_id
        );

        $response = ample_request($api_url, 'POST', $body);
        $response["ample_order_id"] = $order_id;

        return $response;
        // $status_code = wp_remote_retrieve_response_code( $response );

        // if ( $status_code == 200 ) {
        //     return true;
        //     // Process the response body
        // } 

        // return false;
    }
}