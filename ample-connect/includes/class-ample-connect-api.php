<?php

class Ample_Connect_API {

    private static $token = null;

    /**
     * Get the token for API authentication
     * 
     * @return string|WP_Error The token string or a WP_Error object if the login fails
     */
    public static function get_token() {
        if (self::$token === null) {
            $username = 'apiuser';
            $password = 'APIatwp2024';
            
            $login_response = wp_remote_post(AMPLE_CONNECT_LOGIN_URL, array(
                'body' => array(
                    'username' => $username,
                    'password' => $password
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
        }

        return self::$token;
    }
}
