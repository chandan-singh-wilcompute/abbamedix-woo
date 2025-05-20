<?php
if (!defined('ABSPATH')) {
    exit;
}


class Ample_Token_Manager {
    private static $instance = null;
    private $option_name = 'ample_token';
    private $token_expired = false;

    private function __construct() {
        // Empty constructor
    }

    // Singleton instance
    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    // Set / reset token expired status
    public function set_token_expired($expired = false) {
        $this->token_expired = $expired;
    }

    // Retrieve token, refresh if expired
    public function get_token() {

        if ($this->token_expired) {
            return $this->refresh_token();
        }

        $token_data = get_option($this->option_name, []);
        
        if (!empty($token_data['token']) && time() < $token_data['expires_at']) {
            return $token_data['token'];
        }

        return $this->refresh_token();
    }

    // Refresh token
    public function refresh_token() {
        global $ample_connect_settings;
        $ample_admin_username = $ample_connect_settings['ample_admin_username'];
        $ample_admin_password = $ample_connect_settings['ample_admin_password'];
        $response = wp_remote_post(AMPLE_CONNECT_LOGIN_URL, [
            'body' => [
                'username' => $ample_admin_username,
                'password' => $ample_admin_password,
            ],
        ]);


        if (is_wp_error($response)) {
            error_log('API Token Refresh Error: ' . $response->get_error_message());
            return false;
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);
        
        if (!isset($body['token'])) {
            error_log('API Token Refresh Failed: No token received.');
            return false;
        }

        $expires_at = time() + 3600; // Assume API returns expires_in in seconds

        update_option($this->option_name, [
            'token' => $body['token'],
            'expires_at' => $expires_at,
        ]);

        return $body['token'];
    }
}

// Initialize the class
Ample_Token_Manager::get_instance();