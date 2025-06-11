<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

add_action('rest_api_init', 'register_custom_update_api_endpoints');

function register_custom_update_api_endpoints() {
    register_rest_route('custom/v1', '/update-customer', array(
        'methods' => 'POST',
        'callback' => 'update_customer_user_by_client_id',
        // 'permission_callback' => '__return_true'
        'permission_callback' => function () {
            return current_user_can('edit_users');
        }
    ));
}

function update_customer_user_by_client_id(WP_REST_Request $request) {
    $client_id = sanitize_text_field($request->get_param('client_id'));
    $first_name = sanitize_text_field($request->get_param('first_name'));
    $last_name = sanitize_text_field($request->get_param('last_name'));
    $nickname = sanitize_text_field($request->get_param('nickname'));
    $email = sanitize_text_field($request->get_param('email'));
    $billing_first_name = sanitize_text_field($request->get_param('billing_first_name'));
    $billing_last_name = sanitize_text_field($request->get_param('billing_last_name'));
    $billing_company = sanitize_text_field($request->get_param('billing_company'));
    $billing_address_1 = sanitize_text_field($request->get_param('billing_address_1'));
    $billing_address_2 = sanitize_text_field($request->get_param('billing_address_2'));
    $billing_city = sanitize_text_field($request->get_param('billing_city'));
    $billing_postcode = sanitize_text_field($request->get_param('billing_postcode'));
    $billing_country = sanitize_text_field($request->get_param('billing_country'));
    $billing_phone = sanitize_text_field($request->get_param('billing_phone'));
    $billing_email = sanitize_text_field($request->get_param('billing_email'));
    $shipping_first_name = sanitize_text_field($request->get_param('shipping_first_name'));
    $shipping_last_name = sanitize_text_field($request->get_param('shipping_last_name'));
    $shipping_company = sanitize_text_field($request->get_param('shipping_company'));
    $shipping_address_1 = sanitize_text_field($request->get_param('shipping_address_1'));
    $shipping_address_2 = sanitize_text_field($request->get_param('shipping_address_2'));
    $shipping_city = sanitize_text_field($request->get_param('shipping_city'));
    $shipping_postcode = sanitize_text_field($request->get_param('shipping_postcode'));
    $shipping_country = sanitize_text_field($request->get_param('shipping_country'));
    $shipping_phone = sanitize_text_field($request->get_param('shipping_phone'));
    $password = sanitize_text_field($request->get_param('password'));

    $user_query = new WP_User_Query(array(
        'meta_key' => 'client_id',
        'meta_value' => $client_id,
        'number' => 1,
    ));

    $users = $user_query->get_results();

    if (empty($users)) {
        return new WP_Error('user_not_found', 'User not found', array('status' => 404));
    }

    $user_id = $users[0]->ID;
    $user_data = array('ID' => $user_id);

    if (!empty($first_name)) {
        update_user_meta($user_id, 'first_name', $first_name);
    }

    if (!empty($last_name)) {
        update_user_meta($user_id, 'last_name', $last_name);
    }

    if (!empty($nickname)) {
        update_user_meta($user_id, 'nickname', $nickname);
    }

    if (!empty($email)) {
        $user_data['user_email'] = $email;
    }

    if (!empty($password)) {
        $user_data['user_pass'] = $password;
    }

    // Update user data (email and password)
    $user_update = wp_update_user($user_data);

    if (is_wp_error($user_update)) {
        return new WP_Error('update_failed', $user_update->get_error_message(), array('status' => 400));
    }

    if (!empty($billing_first_name)) {
        update_user_meta($user_id, 'billing_first_name', $billing_first_name);
    }

    if (!empty($billing_last_name)) {
        update_user_meta($user_id, 'billing_last_name', $billing_last_name);
    }

    if (!empty($billing_company)) {
        update_user_meta($user_id, 'billing_company', $billing_company);
    }

    if (!empty($billing_address_1)) {
        update_user_meta($user_id, 'billing_address_1', $billing_address_1);
    }

    if (!empty($billing_address_2)) {
        update_user_meta($user_id, 'billing_address_2', $billing_address_2);
    }

    if (!empty($billing_city)) {
        update_user_meta($user_id, 'billing_city', $billing_city);
    }

    if (!empty($billing_postcode)) {
        update_user_meta($user_id, 'billing_postcode', $billing_postcode);
    }

    if (!empty($billing_country)) {
        update_user_meta($user_id, 'billing_country', $billing_country);
    }

    if (!empty($billing_phone)) {
        update_user_meta($user_id, 'billing_phone', $billing_phone);
    }

    if (!empty($billing_email)) {
        update_user_meta($user_id, 'billing_email', $billing_email);
    }

    if (!empty($shipping_first_name)) {
        update_user_meta($user_id, 'shipping_first_name', $shipping_first_name);
    }

    if (!empty($shipping_last_name)) {
        update_user_meta($user_id, 'shipping_last_name', $shipping_last_name);
    }

    if (!empty($shipping_company)) {
        update_user_meta($user_id, 'shipping_company', $shipping_company);
    }

    if (!empty($shipping_address_1)) {
        update_user_meta($user_id, 'shipping_address_1', $shipping_address_1);
    }

    if (!empty($shipping_address_2)) {
        update_user_meta($user_id, 'shipping_address_2', $shipping_address_2);
    }

    if (!empty($shipping_city)) {
        update_user_meta($user_id, 'shipping_city', $shipping_city);
    }

    if (!empty($shipping_postcode)) {
        update_user_meta($user_id, 'shipping_postcode', $shipping_postcode);
    }

    if (!empty($shipping_country)) {
        update_user_meta($user_id, 'shipping_country', $shipping_country);
    }

    if (!empty($shipping_phone)) {
        update_user_meta($user_id, 'shipping_phone', $shipping_phone);
    }

    return new WP_REST_Response(array('message' => 'User updated successfully', 'user_id' => $user_id), 200);
}
?>
