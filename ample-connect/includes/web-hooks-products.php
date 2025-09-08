<?php
if (!defined('ABSPATH')) {
    exit;
}
require_once plugin_dir_path(__FILE__) . '/class-wc-products.php';


// Webhook for Product Update Notification from Ample
add_action('rest_api_init', function() {
    register_rest_route('webhooks/v1', '/products', array(
        'methods' => 'POST',
        'callback' => 'handle_products_webhook',
    ));
});


// Function to handle Products Webhook
function handle_products_webhook(WP_REST_Request $request) {

    $data = $request->get_json_params();

    if (!array_key_exists("webhook_signature", $data) || !array_key_exists("entity_type", $data) || !array_key_exists("event_type", $data) || !array_key_exists("entity_id", $data) || !array_key_exists("refetch_url", $data)) {
        return new WP_REST_Response(array("message" => "Missing Required Data!"), 200);
    }
    
    $verification = verify_webhook_request($data['webhook_signature']);
    if (is_wp_error($verification)) {
        return $verification;
    }

    $token = Ample_Connect_API::get_token();
    if (is_wp_error($token)) {
        return new WP_REST_Response(array("message" => "Woo Token Error "), 200);
    }

    if ($data["event_type"] == "update") {

        if ($data['entity_type'] == 'Product') {
            $product_id =  sanitize_text_field($data['entity_id']);
            
            $product_url = AMPLE_CONNECT_API_BASE_URL . sanitize_text_field($data['refetch_url']). '?token=' . $token;

            $response = wp_remote_get($product_url, ['timeout' => 300]);
            if (is_wp_error($response)) {
                error_log('Error fetching data from API: ' . $response->get_error_message());
                return;
            }
            
            $product_data = json_decode(wp_remote_retrieve_body($response), true);
            // return new WP_REST_Response(array("message" => $product_data[0]), 200);
            $woo_client = new WC_Products();
            
            if ($woo_client->update_product($product_data[0])) {
                return new WP_REST_Response(array("message" => "Product details got updated!"), 200);
            } else {
                return new WP_REST_Response(array("message" => "Product updation was not successful!"), 404);
            }

        } else if ($data['entity_type'] == 'Sku') {
            $sku_id = sanitize_text_field($data['entity_id']);
            $refetch_url = sanitize_text_field($data['refetch_url']);
            $ref_url_parts = explode("/", $refetch_url);
            $product_id = array_pop($ref_url_parts);

            $product_url = AMPLE_CONNECT_API_BASE_URL . sanitize_text_field($data['refetch_url']). '?token=' . $token;

            $response = wp_remote_get($product_url, ['timeout' => 300]);
            if (is_wp_error($response)) {
                error_log('Error fetching data from API: ' . $response->get_error_message());
                return;
            }
            
            $product_data = json_decode(wp_remote_retrieve_body($response), true)[0];
            $skuData = null;

            foreach ($product_data['skus'] as $index => $sku) {
                if ($sku['id'] == $sku_id) {
                    $skuData = $sku;
                    break;
                }
            }

            if ($skuData) {
                
                $woo_client = new WC_Products();

                if ($woo_client->update_sku($skuData, $product_id)) {
                    return new WP_REST_Response(array("message" => "Product details got updated!"), 200);
                } else {
                    return new WP_REST_Response(array("message" => "Product updation was not successful!"), 404);
                }
            }  else {
                return new WP_REST_Response(array("message" => "SKU Doesn't Exist!"), 200);
            }
        }
    } else if ($data["event_type"] == "create") { 
        if ($data['entity_type'] == 'Product' || $data['entity_type'] == 'Sku') {

            if ($data['entity_type'] == 'Product') 
                $product_id =  sanitize_text_field($data['entity_id']);
            else
                $sku_id = sanitize_text_field($data['entity_id']);

            
            $product_url = AMPLE_CONNECT_API_BASE_URL . sanitize_text_field($data['refetch_url']). '?token=' . $token;

            $response = wp_remote_get($product_url, ['timeout' => 300]);

            if (is_wp_error($response)) {
                error_log('Error fetching data from API: ' . $response->get_error_message());
                return;
            }
    
            $product_data = json_decode(wp_remote_retrieve_body($response), true);
            
            $woo_client = new WC_Products();
            
            if ($woo_client->add_product($product_data[0])) {
                return new WP_REST_Response(array("message" => "Product details got updated!"), 200);
            } else {
                return new WP_REST_Response(array("message" => "Product updation was not successful!"), 404);
            }
        } 
    }
}

