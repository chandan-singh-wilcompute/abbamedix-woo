<?php
if (!defined('ABSPATH')) {
    exit;
}

class  Client_Information {
    /* Class to fetch and cache Client's information
        related to orders
    */

    public static function fetch_information() {
        
        if (!is_user_logged_in()) {
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

        $prescriptions = $client['prescriptions'];
        Ample_Session_Cache::set('prescriptions', $prescriptions);
        $available_to_order = 0;
        foreach ($prescriptions as $prescription) {
            if ($prescription['is_current'] == 1) {
                Ample_Session_Cache::set('current_prescription', $prescription);
                $available_to_order = $prescription['available_to_order'];
                break;
            }
        }  
        Ample_Session_Cache::set('available_to_order', $available_to_order);
    
        
        $credit_cards = $client['credit_cards'];
        Ample_Session_Cache::set('credit_cards', $credit_cards);

        // Fetching registration
        $registration = $client['registration'];
        $status = $registration['status'];
        $needs_renewal = $client['needs_renewal'];
        Ample_Session_Cache::set('status', $status);
        Ample_Session_Cache::set('needs_renewal', $needs_renewal);
        return true;
    }

}