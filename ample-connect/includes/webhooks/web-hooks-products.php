
<?php
if (!defined('ABSPATH')) {
    exit;
}
require_once dirname(plugin_dir_path(__FILE__)) . '/wc_classes/class-wc-products.php';


// Webhook for Product Update Notification from Ample
add_action('rest_api_init', function() {
    register_rest_route('webhooks/v1', '/products', array(
        'methods' => 'POST',
        'callback' => 'handle_products_webhook',
        'permission_callback' => '__return_true'
    ));
});


// Function to handle Products Webhook
function handle_products_webhook(WP_REST_Request $request) {

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
            "entity_id": 20,
            "entity_type": "Product",
            "event_type": "update",
            "changed_on": "2024-10-29T09:34:28.169-04:00",
            "refetch_url": "/integrations/woocommerce/products/20",
            "webhook_signature": "secret" -- configurable
        }
        */

        if ($data['entity_type'] == 'Product') {
            $product_id =  sanitize_text_field($data['entity_id']);
            
            $product_url = AMPLE_CONNECT_API_BASE_URL . sanitize_text_field($data['refetch_url']);

            $product_data = ample_request($product_url);
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

            $product_url = AMPLE_CONNECT_API_BASE_URL . sanitize_text_field($data['refetch_url']);

            $product_data = ample_request($product_url)[0];
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
        /* Sample Request
        {
            "entity_id": 25,
            "entity_type": "Sku",
            "event_type": "create",
            "changed_on": "2024-10-29T09:34:28.169-04:00",
            "refetch_url": "/integrations/woocommerce/products/14",
            "webhook_signature": "secret" -- configurable
        }
        */
        
        if ($data['entity_type'] == 'Product' || $data['entity_type'] == 'Sku') {

            if ($data['entity_type'] == 'Product') 
                $product_id =  sanitize_text_field($data['entity_id']);
            else
                $sku_id = sanitize_text_field($data['entity_id']);

            
            $product_url = AMPLE_CONNECT_API_BASE_URL . sanitize_text_field($data['refetch_url']);

            $product_data = ample_request($product_url);
            
            $woo_client = new WC_Products();
            
            if ($woo_client->add_product($product_data[0])) {
                return new WP_REST_Response(array("message" => "Product details got updated!"), 200);
            } else {
                return new WP_REST_Response(array("message" => "Product updation was not successful!"), 404);
            }
        } 
    }
}


