<?php
if (!defined('ABSPATH')) {
    exit;
}

class  Client_Information {
    /* Class to hold and return Client's information
        related to orders
    */

    private static $available_to_order = null;
    private static $client_id = null;
    private static $credit_cards = null;
    private static $status = null;

    private static function fetch_information() {
        
        if (!is_user_logged_in()) {
            self::$available_to_order = null;
            self::$client_id = null;
            self::$credit_cards = null;
            return false;
        }

        $user_id = get_current_user_id();

        // Get the client id of the customer
        $client_id = get_user_meta($user_id, "client_id", true);
        if (!$client_id) {
            return false;
        }

        $client_url = AMPLE_CONNECT_WOO_CLIENT_URL . $client_id;
        $client = ample_request($client_url);

        if (empty($client)) {
            return false;
        }

        self::$client_id = $client['id'];
        $prescriptions = $client['prescriptions'];
        foreach ($prescriptions as $prescription) {
            if ($prescription['is_current'] == 1) {
                self::$available_to_order = $prescription['available_to_order'];
                break;
            }
        }   
        
        self::$credit_cards = $client['credit_cards'];

        // Fetching registration
        $registration = $client['registration'];
        self::$status = $registration['status'];

        return true;
    }

    /**
     * Get the available_to_order value
     * 
     * @return mixed The available_to_order value or null if not set
     */
    public static function get_available_to_order() {
        if (self::$available_to_order === null) {
            self::fetch_information();
        }
        
        return self::$available_to_order;
    }

    /**
     * Get the client_id value
     * 
     * @return mixed The client_id value or null if not set
     */
    public static function get_client_id() {
        if (self::$client_id === null) {
            self::fetch_information();
        }
        
        return self::$client_id;
    }

    /**
     * Get the credit_card_token_id value
     * 
     * @return mixed The credit_card_token_id value or null if not set
     */
    public static function get_credit_cards() {
        if (self::$credit_cards === null or empty(self::$credit_cards)) {
            self::fetch_information();
        }
        //self::fetch_information();
        return self::$credit_cards;
    }

    public static function get_status() {
        if (self::$status === null) {
            self::fetch_information();
        }
        //self::fetch_information();
        return self::$status;
    }

}