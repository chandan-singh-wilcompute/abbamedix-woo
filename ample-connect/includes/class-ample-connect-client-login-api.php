<?php

class Ample_Connect_Client_Login_API {

    private static $token = null;
    private static $available_to_order = null;
    private static $client_id = null;
    private static $credit_card_token_id = null;

    /**
     * Get the token for API authentication
     * 
     * @return string|WP_Error The token string or a WP_Error object if the login fails
     */
    public static function get_client_token() {
        global $ample_connect_settings;
        // Get the current user ID
        $current_user_id = get_current_user_id();
        // Get the client_login_id from user meta
        $client_login_id = get_user_meta($current_user_id, 'client_login_id', true);
        if (self::$token === null) {
            // $client_login_id = $client_login_id;
            // $client_login_id = "5185-4725-7256-9574";
            // $client_password = '12345678';
            $client_login_id = $ample_connect_settings['client_login_id'];
            $client_password = $ample_connect_settings['client_login_password'];
            
            $login_response = wp_remote_post(AMPLE_CONNECT_API_BASE_URL.'/v1/portal/clients/login', array(
                'body' => array(
                    'client_id' => $client_login_id,
                    'password' => $client_password
                )
            ));

            if (is_wp_error($login_response)) {
                return $login_response;
            }

            $login_body = wp_remote_retrieve_body($login_response);
            $login_data = json_decode($login_body, true);

            if (!isset($login_data['token'])) {
                return new WP_Error('login_failed', 'Login failed: ' . $login_data['message']);
            }
            self::$token = $login_data['token'];
            self::$client_id = $login_data['id'];
            $prescriptions = $login_data['prescriptions'];
            foreach ($prescriptions as $prescription) {
                if ($prescription['is_current'] == 1) {
                    self::$available_to_order = $prescription['available_to_order'];
                    break;
                }
            }   
            
            self::$credit_card_token_id = $login_data['credit_cards'][0]['id'];
        }

        return self::$token;
    }

    /**
     * Get the available_to_order value
     * 
     * @return mixed The available_to_order value or null if not set
     */
    public static function get_available_to_order() {
        if (self::$token === null) {
            self::get_client_token();
        }
        
        return self::$available_to_order;
    }

    /**
     * Get the client_id value
     * 
     * @return mixed The client_id value or null if not set
     */
    public static function get_client_id() {
        if (self::$token === null) {
            self::get_client_token();
        }
        
        return self::$client_id;
    }

    /**
     * Get the credit_card_token_id value
     * 
     * @return mixed The credit_card_token_id value or null if not set
     */
    public static function get_credit_card_token_id() {
        if (self::$token === null) {
            self::get_client_token();
        }
        
        return self::$credit_card_token_id;
    }
}
