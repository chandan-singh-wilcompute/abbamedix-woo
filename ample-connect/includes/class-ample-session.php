<?php

if (!defined('ABSPATH')) exit;

class Ample_Session_Cache {

    /**
     * Check if WooCommerce session is available and initialized
     */
    public static function is_session_available() {
        return function_exists('WC') && 
            WC() && 
            WC()->session && 
            WC()->session->has_session();
    }

    /**
     * Set session data with optional timestamp.
     */
    public static function set($key, $value) {
        if (self::is_session_available()) {
            WC()->session->set($key, $value);
            WC()->session->set($key . '_timestamp', time());
        } else {
            ample_connect_log("Session is not available for setting key: " . $key);
        }
    }

    /**
     * Get session data.
     */
    public static function get($key, $default=null) {
        if (self::is_session_available()) {
            return WC()->session->get($key, $default);
        }
        return null;
    }

    /**
     * Delete session data and its timestamp.
     */
    public static function delete($key) {
        if (self::is_session_available()) {
            WC()->session->__unset($key);
            WC()->session->__unset($key . '_timestamp');
        }
    }

    /**
     * Check if session data exists.
     */
    public static function has($key) {
        return self::get($key) !== null;
    }

    /**
     * Check if session data is older than a given number of seconds.
     */
    public static function is_older_than($key, $seconds) {
        if (self::is_session_available()) {
            $timestamp = WC()->session->get($key . '_timestamp');
            if ($timestamp) {
                return (time() - $timestamp) > $seconds;
            }
        }
        return false;
    }

    /**
     * Clear all ample-connect session data
     */
    public static function clear_all() {
        if (self::is_session_available()) {
            $session_keys = [
                'purchasable_products',
                'order_id',
                'custom_shipping_rates',
                'custom_tax_data',
                'applicable_discounts',
                'policy_details',
                'order_items',
                'available_to_order',
                'credit_cards',
                'status',
                'session_initialized',
                'prescriptions',
                'current_prescription',
                'applicable_discount_codes',
                'applied_policies',
                'applicable_policies',
                'order_note',
                'external_order_number',
                'last_completed_order_id'
            ];
            
            foreach ($session_keys as $key) {
                self::delete($key);
            }
        }
    }
}
