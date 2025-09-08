<?php
if (!defined('ABSPATH')) {
    exit;
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
