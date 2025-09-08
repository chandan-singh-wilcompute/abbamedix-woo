<?php
if (!defined('ABSPATH')) {
    exit;
}

// Webhook for Patient Details Update Notification from Ample
add_action('rest_api_init', function() {
    register_rest_route('webhooks/v1', 'clients', array(
        'methods' => 'POST',
        'callback' => 'handle_clients_webhook',
    ));
});

// Function to handle product update webhook
function handle_clients_webhook(WP_REST_Request $request) {
    $data = $request->get_json_params();
    
    $verification = verify_webhook_request($data['webhook_signature']);
    if (is_wp_error($verification)) {
        return $verification;
    }

    return new WP_REST_Response('Webhook received', 200);
}