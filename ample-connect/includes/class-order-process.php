<?php
if (!defined('ABSPATH')) {
    exit;
}
require_once plugin_dir_path(__FILE__) . '/customer-functions.php';

class Order_Process {
    /* Class to hold and return Client's information
        related to orders
    */

    public static function purchase_order($card_token_id) {
        
        if (!is_user_logged_in()) {
            return false;
        }

        $body = array(
            'credit_card_token_id' => $card_token_id,
            'payment_type' => 'credit_card'
        );

        $response = purchase_order_on_ample($body);

        return $response;
    }
}