<?php
if (!defined('ABSPATH')) {
    exit;
}
// require_once dirname(plugin_dir_path(__FILE__)) . '/utility.php';

// Function to check if request is valid
function validate_webhook_request_params($req_data) {
    // if (!array_key_exists("webhook_signature", $req_data) || !array_key_exists("entity_type", $req_data) || !array_key_exists("event_type", $req_data) || !array_key_exists("entity_id", $req_data) || !array_key_exists("refetch_url", $req_data)) {
    //     return false;
    // }
    if (!array_key_exists("webhook_signature", $req_data) || !array_key_exists("resource_type", $req_data) || !array_key_exists("change_type", $req_data) || !array_key_exists("resource_id", $req_data) || !array_key_exists("resource_url", $req_data)) {
        return false;
    }

    return true;
}

// Function to verify Webhook Request for Authentification
function verify_webhook_request($signature) {
    if ($signature) {
        global $ample_connect_settings;
        $secret = $ample_connect_settings['webhook_secret'];

        if ($secret !== $signature) {
            return new WP_Error('invalid_signature', 'Invalid signature', array('status' => 403));
        }

        return true;
    }
    return new WP_Error('no_signature', 'Request does not have signature.', array('status' => 403));
}


function get_user_id_by_client_id( $client_id ) {
    if ( ! $client_id ) {
        return false;
    }

    $user_query = new WP_User_Query( array(
        'meta_key'   => 'client_id',
        'meta_value' => $client_id,
        'number'     => 1,
        'fields'     => 'ID', // Only get user IDs
    ) );

    $users = $user_query->get_results();

    if ( ! empty( $users ) ) {
        return $users[0]; // Return the first matching user ID
    }

    return false; // No user found
}

