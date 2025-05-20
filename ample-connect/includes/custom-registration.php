<?php

if (!defined('ABSPATH')) {
    exit;
}
// require_once plugin_dir_path(__FILE__) . '/utility.php';

function custom_registration_enqueue_scripts() {
    // Enqueue script
    wp_enqueue_script('custom-registration', plugin_dir_url(__FILE__) . '../assets/js/custom-registration.js', array('jquery'), null, true);
    wp_enqueue_script( 'custom-address-validation', plugin_dir_url(__FILE__) . '../assets/js/custom-address-validation.js', array( 'jquery' ), null, true );
    
    // Localize the script with ajax_url
    wp_localize_script('custom-registration', 'custom_registration', array(
        'ajax_url' => admin_url('admin-ajax.php')
    ));


    global $ample_connect_settings;
    if ($ample_connect_settings['account_not_approved_message']){
        $status_message = $ample_connect_settings['account_not_approved_message'];
    }else{
        $status_message = "Account is not approved";
    }
    // Localize the script with status_message
    wp_localize_script('custom-address-validation', 'custom_address_validation', array(
        'status_message' => $status_message
    ));

    if (is_checkout()) {
        wp_enqueue_script('custom-order', plugin_dir_url(__FILE__) . '../assets/js/custom-place-order.js', array('jquery'), null, true);

        // Get the current user ID
        // $current_user_id = get_current_user_id();

        // // Get the client_id from user meta
        // $client_id = get_user_meta($current_user_id, 'client_id', true);
        $status = Client_Information::get_status();

        // Localize the script with ajax_url and client_id
        wp_localize_script('custom-order', 'custom_order', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'status' => $status
        ));
    }
}
add_action('wp_enqueue_scripts', 'custom_registration_enqueue_scripts');

function update_user_client_id_registration_id() {
    $user_id = sanitize_text_field($_POST['user_id']);
    $client_id = sanitize_text_field($_POST['client_id']);
    $active_registration_id = sanitize_text_field($_POST['active_registration_id']);
    $client_login_id = sanitize_text_field($_POST['client_login_id']);
    $updated = update_user_meta( $user_id, 'client_id', $client_id );
    $updated = update_user_meta( $user_id, 'client_login_id', $client_login_id );
    $updated = $updated && update_user_meta( $user_id, 'active_registration_id', $active_registration_id );

    if ( $updated ) {
        wp_send_json_success( 'User meta updated successfully.' );
    } else {
        wp_send_json_error( 'Failed to update user meta.' );
    }
}
add_action('wp_ajax_update_user_client_id_registration_id', 'update_user_client_id_registration_id');
add_action('wp_ajax_nopriv_update_user_client_id_registration_id', 'update_user_client_id_registration_id');

// Handle form submission
function custom_registration_action()
{
    // Retrieve form data
    $form_fields = $_POST['form_fields'];
    $first_name = sanitize_text_field($form_fields['name']);
    $middle_name = sanitize_text_field($form_fields['email']);
    $last_name = sanitize_text_field($form_fields['message']);
    $dob_year = intval($form_fields['field_871115a']);
    $dob_month = intval($form_fields['field_434268a']);
    $dob_day = intval($form_fields['field_701927b']);
    $gender = sanitize_text_field($form_fields['field_2a0ac5c']);
    $is_veteran = isset($form_fields['field_583befa']) ? 'Yes' : 'No';
    $address_pobox = sanitize_text_field($form_fields['field_84f70c1']);
    $address_street = sanitize_text_field($form_fields['field_e48311f']);
    $address_city = sanitize_text_field($form_fields['field_cea79fa']);
    $address_province = sanitize_text_field($form_fields['field_46e27da']);
    $address_postal_code = sanitize_text_field($form_fields['field_81d3861']);
    $mailing_pobox = sanitize_text_field($form_fields['field_5dcb0ca']);
    $mailing_street = sanitize_text_field($form_fields['field_3b67ce9']);
    $mailing_city = sanitize_text_field($form_fields['field_b03cb1a']);
    $mailing_province = sanitize_text_field($form_fields['field_cbac568']);
    $mailing_postal_code = sanitize_text_field($form_fields['field_c97e896']);
    $shipping_pobox = sanitize_text_field($form_fields['field_696ba6d']);
    $shipping_street = sanitize_text_field($form_fields['field_4d0a974']);
    $shipping_city = sanitize_text_field($form_fields['field_afdd091']);
    $shipping_province = sanitize_text_field($form_fields['field_51adbb6']);
    $shipping_postal_code = sanitize_text_field($form_fields['field_3e39d38']);
    $has_caregiver = isset($form_fields['field_1c2a8a5']) ? 'Yes' : 'No';
    $email = sanitize_text_field($form_fields['field_728ed5d1']);
    $password = sanitize_text_field($form_fields['field_f4cc2f5']);
    $billing_phone = sanitize_text_field($form_fields['field_583befa']);
    // Validate form data (add your own validation as needed)

    // Create user
    $username = sanitize_user($first_name . $last_name);
    $username = custom_registration_generate_unique_username($username);
    $user_id = wp_create_user($username, $password, $email);
    if (is_wp_error($user_id)) {
        wp_send_json_error(array('message' => 'Error creating user: ' . $user_id->get_error_message()));
    }

    // Set user as a Customer
    $result = wp_update_user(array(
        'ID' => $user_id,
        'role' => 'customer',
    ));

    // Update user meta
    update_user_meta($user_id, 'first_name', $first_name);
    update_user_meta($user_id, 'middle_name', $middle_name);
    update_user_meta($user_id, 'last_name', $last_name);
    update_user_meta($user_id, 'billing_first_name', $first_name);
    update_user_meta($user_id, 'billing_middle_name', $middle_name);
    update_user_meta($user_id, 'billing_last_name', $last_name);
    update_user_meta($user_id, 'dob_year', $dob_year);
    update_user_meta($user_id, 'dob_month', $dob_month);
    update_user_meta($user_id, 'dob_day', $dob_day);
    update_user_meta($user_id, 'gender', $gender);
    update_user_meta($user_id, 'is_veteran', $is_veteran);
    update_user_meta($user_id, 'billing_address_1', $address_pobox);
    update_user_meta($user_id, 'billing_address_2', $address_street);
    update_user_meta($user_id, 'billing_city', $address_city);
    update_user_meta($user_id, 'billing_state', $address_province);
    update_user_meta($user_id, 'billing_phone', $billing_phone);
    update_user_meta($user_id, 'billing_postcode', $address_postal_code);
    update_user_meta($user_id, 'mailing_pobox', $mailing_pobox);
    update_user_meta($user_id, 'mailing_street', $mailing_street);
    update_user_meta($user_id, 'mailing_city', $mailing_city);
    update_user_meta($user_id, 'mailing_province', $mailing_province);
    update_user_meta($user_id, 'mailing_postal_code', $mailing_postal_code);
    update_user_meta($user_id, 'shipping_address_1', $shipping_pobox);
    update_user_meta($user_id, 'shipping_address_2', $shipping_street);
    update_user_meta($user_id, 'shipping_city', $shipping_city);
    update_user_meta($user_id, 'shipping_state', $shipping_province);
    update_user_meta($user_id, 'shipping_postcode', $shipping_postal_code);
    update_user_meta($user_id, 'has_caregiver', $has_caregiver);
    update_user_meta($user_id, 'status', 'Lead');

    // Send a success response
    wp_send_json_success(array(
        'message' => 'Registration successful!',
        'user_id' => $user_id,
        'redirect_url' => wc_get_page_permalink('myaccount') // Redirect to the My Account page
    ));
}
add_action('wp_ajax_custom_registration_action', 'custom_registration_action');
add_action('wp_ajax_nopriv_custom_registration_action', 'custom_registration_action');


// Register AJAX endpoint for retrieving token
add_action('wp_ajax_nopriv_get_admin_token', 'get_admin_token_callback'); // For logged-out users
add_action('wp_ajax_get_admin_token', 'get_admin_token_callback'); // For logged-in users

function get_admin_token_callback() {

    $token = get_ample_api_token();
    echo json_encode(array('token' => $token)); 
    wp_die();
}

?>