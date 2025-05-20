<?php
if (!defined('ABSPATH')) {
    exit;
}
require_once dirname(plugin_dir_path(__FILE__)) . '/wc_classes/class-wc-customers.php';

// Webhook for Patient Details Update Notification from Ample
add_action('rest_api_init', function() {
    register_rest_route('webhooks/v1/', 'clients', array(
        'methods' => 'POST',
        'callback' => 'handle_clients_webhook',
        'permission_callback' => '__return_true'
    ));
});

// Function to handle product update webhook
function handle_clients_webhook(WP_REST_Request $request) {
    $data = $request->get_json_params();
    
    if (!validate_webhook_request_params($data)) {
        return new WP_REST_Response(array("message" => "Missing Required Data!"), 200);
    }

    $verification = verify_webhook_request($data['webhook_signature']);
    if (is_wp_error($verification)) {
        return $verification;
    }

    if ($data["event_type"] == "update") {
        /* Sample Request
        {
            "entity_id": 15,
            "entity_type": "Registration",
            "event_type": "update",
            "changed_on": "2024-10-29T09:34:28.169-04:00",
            "refetch_url": "/integrations/woocommerce/clients/20",
            "webhook_signature": "secret" -- configurable
        }
        */

        if ($data['entity_type'] == 'Registration') {
            $registration_id =  sanitize_text_field($data['entity_id']);
            
            $fetch_url = AMPLE_CONNECT_API_BASE_URL . sanitize_text_field($data['refetch_url']);

            $registration_data = ample_request($fetch_url);

            $woo_client = new WC_Customers ();

            if ($woo_client->update_customer($registration_data)) {
                return new WP_REST_Response(array("message" => "Customer details got updated!"), 200);
            } else {
                return new WP_REST_Response(array("message" => "Customer not found or something went wrong!"), 404);
            }
        } else if ($data['entity_type'] == 'Reset password') {
            $client_id =  sanitize_text_field($data['entity_id']);

            $woo_client = new WC_Customers ();

            if ($woo_client->reset_password($client_id)) {
                return new WP_REST_Response(array("message" => "Password reset link has been sent!"), 200);
            } else {
                return new WP_REST_Response(array("message" => "Customer not found or something went wrong!"), 404);
            }
        }

    } else if ($data["event_type"] == "create") {
        /*
        {
            "entity_id": 25,
            "entity_type": "Client",
            "event_type": "create",
            "changed_on": "2024-10-29T09:34:28.169-04:00",
            "refetch_url": "/integrations/woocommerce/clients/25",
            "webhook_signature": "secret" -- configurable
        }
        */

        if ($data['entity_type'] == 'Client') {
            $client_id =  sanitize_text_field($data['entity_id']);
            
            $fetch_url = AMPLE_CONNECT_API_BASE_URL . sanitize_text_field($data['refetch_url']);

            $client_data = ample_request($fetch_url);
            $woo_client = new WC_Customers ();

            if ($woo_client->create_customer($client_data)) {
                return new WP_REST_Response(array("message" => "A new customer added!"), 200);
            } else {
                return new WP_REST_Response(array("message" => "Customer creation failed!"), 404);
            }
        }
    }

    return new WP_REST_Response('Webhook received but nothing to do!', 200);
}