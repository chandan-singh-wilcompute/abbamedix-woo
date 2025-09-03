<?php

if (!defined('ABSPATH')) {
    exit;
}
// require_once plugin_dir_path(__FILE__) . '/utility.php';
require_once plugin_dir_path(__FILE__) . '/customer-functions.php';

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

        $status = Ample_Session_Cache::get('status');

        // Localize the script with ajax_url and client_id
        wp_localize_script('custom-order', 'custom_order', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'status' => $status
        ));
    }
}
add_action('wp_enqueue_scripts', 'custom_registration_enqueue_scripts');

function update_user_client_id_registration_id() {
    if (!is_user_logged_in()) {
        wp_send_json_error('Unauthorized', 403);
    }

    $posted_user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;

    // Optional nonce validation if provided by caller
    if (isset($_POST['security']) && !wp_verify_nonce($_POST['security'], 'ample_nonce_data')) {
        wp_send_json_error('Invalid nonce', 400);
    }

    // Ensure the current user owns this account or has privilege to edit
    if ($posted_user_id !== get_current_user_id() && !current_user_can('edit_user', $posted_user_id)) {
        wp_send_json_error('Unauthorized', 403);
    }

    $client_id = sanitize_text_field($_POST['client_id'] ?? '');
    $active_registration_id = sanitize_text_field($_POST['active_registration_id'] ?? '');
    $client_login_id = sanitize_text_field($_POST['client_login_id'] ?? '');

    $updated  = update_user_meta($posted_user_id, 'client_id', $client_id);
    $updated &= update_user_meta($posted_user_id, 'client_login_id', $client_login_id);
    $updated &= update_user_meta($posted_user_id, 'active_registration_id', $active_registration_id);

    if ($updated) {
        wp_send_json_success('User meta updated successfully.');
    } else {
        wp_send_json_error('Failed to update user meta.');
    }
}
add_action('wp_ajax_update_user_client_id_registration_id', 'update_user_client_id_registration_id');

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
// Removed insecure public token endpoint


// Register a customer/patient on the websitehw
function handle_elementor_form($record, $handler) {
    // ample_connect_log("patient form submitted");
    $form_name = $record->get_form_settings('form_name');
    if ($form_name == 'Registration Form') {
        $raw_fields = $record->get('fields');
        $form_fields = [];
        foreach ($raw_fields as $id => $field) {
            $form_fields[$id] = sanitize_text_field($field['value']);
        }

        $email = sanitize_text_field($form_fields['email']);
        // Check if email is already registered
        if (email_exists($email)) {
            // $handler->add_action( 'redirect_url', home_url( '/registration/?reg_msg=email_exists' ) );
            $handler->add_response_data( 'redirect_url', home_url( '/registration/?reg_msg=email_exists' ) );
            return;
        }

        // $form_fields = $record->get('fields');
        // ample_connect_log($form_fields);
        // Retrieve form data
        $first_name = sanitize_text_field($form_fields['firstname']);
        $middle_name = sanitize_text_field($form_fields['middle_name']);
        $last_name = sanitize_text_field($form_fields['last_name']);
        // ample_connect_log("name = $first_name $middle_name $last_name");
        $dob_year = intval($form_fields['field_871115a']);
        $dob_month = intval($form_fields['field_434268a']);
        $dob_day = intval($form_fields['field_701927b']);
        $gender = sanitize_text_field($form_fields['field_2a0ac5c']);
        $is_veteran = sanitize_text_field($form_fields['field_583befa']);
        $address_pobox = sanitize_text_field($form_fields['field_84f70c1']);
        $address_street = sanitize_text_field($form_fields['field_e48311f']);
        $address_city = sanitize_text_field($form_fields['field_cea79fa']);
        $address_province = sanitize_text_field($form_fields['province_1']);
        $address_postal_code = sanitize_text_field($form_fields['field_81d3861']);
        $mailing_pobox = sanitize_text_field($form_fields['field_5dcb0ca']);
        $mailing_street = sanitize_text_field($form_fields['field_3b67ce9']);
        $mailing_city = sanitize_text_field($form_fields['field_b03cb1a']);
        $mailing_province = sanitize_text_field($form_fields['province_2']);
        $mailing_postal_code = sanitize_text_field($form_fields['field_c97e896']);
        $shipping_pobox = sanitize_text_field($form_fields['field_696ba6d']);
        $shipping_street = sanitize_text_field($form_fields['field_4d0a974']);
        $shipping_city = sanitize_text_field($form_fields['field_afdd091']);
        $shipping_province = sanitize_text_field($form_fields['province_3']);
        $shipping_postal_code = sanitize_text_field($form_fields['field_3e39d38']);
        $has_caregiver = isset($form_fields['field_1c2a8a5']) ? 'Yes' : 'No';
        $is_consent = isset($form_fields['field_e365754']) ? 'Yes' : 'No';

        $password = sanitize_text_field($form_fields['password']);
        $billing_phone = sanitize_text_field($form_fields['field_b3f82c6']);
        $alternate_phone = sanitize_text_field($form_fields['field_7134d38']);
        
        // Validate form data (add your own validation as needed)

        // Create user
        $username = sanitize_user($first_name . $last_name);
        $username = custom_registration_generate_unique_username($username);
        $user_id = wp_create_user($username, $password, $email);

        if (is_wp_error($user_id)) {
            //return array('status' => false, 'message' => 'Error creating user: ' . $user_id->get_error_message());
            $handler->add_response_data( 'redirect_url', home_url( '/registration/?reg_msg=error' ) );
            return;
        }

        // Set user as a Customer
        // $result = wp_update_user(array(
        //     'ID' => $user_id,
        //     'role' => 'customer',
        // ));

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
        update_user_meta($user_id, 'date_of_birth', "$dob_year-$dob_month-$dob_day");
        update_user_meta($user_id, 'gender', $gender);
        update_user_meta($user_id, 'is_veteran', $is_veteran);
        update_user_meta($user_id, 'billing_address_1', $address_pobox);
        update_user_meta($user_id, 'billing_address_2', $address_street);
        update_user_meta($user_id, 'billing_city', $address_city);
        update_user_meta($user_id, 'billing_state', $address_province);
        update_user_meta($user_id, 'billing_phone', $billing_phone);
        update_user_meta($user_id, 'alternate_phone', $alternate_phone);
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
        update_user_meta($user_id, 'is_consent', $is_consent);
        update_user_meta($user_id, 'status', 'Lead');


        $billing_phone = $billing_phone ? $billing_phone : '555-555-5555';
        $patient_data = array(
            'first_name' => $first_name,
            'middle_name' => $middle_name,
            'last_name' => $last_name,
            'date_of_birth' => "$dob_year-$dob_month-$dob_day",
            'telephone_1' => $billing_phone,
            'email' => $email,
            'password' => $password
        );

        $ample_client_response = register_patient_on_ample($patient_data);

        if (!isset($ample_client_response['id'])) {
            $handler->add_response_data( 'redirect_url', home_url( '/my-account/?registration=failure' ) );
            return;
            
            // $ample_client_response = register_patient_on_ample($patient_data);
            // if (!isset($ample_client_response['id'])) {
            //     $handler->add_response_data( 'redirect_url', home_url( '/my-account/?registration=failure' ) );
            //     return; 
            // }
        }

        $client_id = $ample_client_response['id'];
        $active_registration_id = $ample_client_response['active_registration_id'];
        $client_login_id = $ample_client_response['client_id'];

        $reg_data = array(
            'gender' => $gender,
            'telephone_1' => $billing_phone,
            'telephone_2' => $alternate_phone,
            'street_1' => $address_street,
            'street_2' => $address_pobox,
            'city' => $address_city,
            'province' => $address_province,
            'postal_code' => $address_postal_code,
            'mailing_street_1' => $mailing_street,
            'mailing_street_2' => $mailing_pobox,
            'mailing_city' => $mailing_city,
            'mailing_province' => $mailing_province,
            'mailing_postal_code' => $mailing_postal_code,
        );

        $update_response = update_registration_details_on_ample($client_id, $active_registration_id, $reg_data);

        update_user_meta( $user_id, 'client_id', $client_id );
        update_user_meta( $user_id, 'client_login_id', $client_login_id );
        update_user_meta( $user_id, 'active_registration_id', $active_registration_id );

        // Send a success response
        // return array(
        //     'status' => true,
        //     'message' => 'Registration successful!',
        //     'user_id' => $user_id,
        //     'redirect_url' => wc_get_page_permalink('myaccount') // Redirect to the My Account page
        // );

        $handler->add_response_data( 'redirect_url', home_url( '/my-account/?registration=success' ) );
        return;
    } else if ($form_name == 'Renewal Form') {
        $raw_fields = $record->get('fields');
        $form_fields = [];
        foreach ($raw_fields as $id => $field) {
            $form_fields[$id] = sanitize_text_field($field['value']);
        }

        // Retrieve form data
        $first_name = sanitize_text_field($form_fields['firstname']);
        $middle_name = sanitize_text_field($form_fields['middle_name']);
        $last_name = sanitize_text_field($form_fields['last_name']);
        $title = sanitize_text_field($form_fields['title']);
        $prefered_name = sanitize_text_field($form_fields['preferred_name']);
        $title = sanitize_text_field($form_fields['title']);
        // ample_connect_log("name = $first_name $middle_name $last_name");
        $dob_year = intval($form_fields['year']);
        $dob_month = intval($form_fields['month']);
        $dob_day = intval($form_fields['day']);
        $gender = sanitize_text_field($form_fields['gender']);
        $address_line1 = sanitize_text_field($form_fields['field_03852e1']);
        $address_line2 = sanitize_text_field($form_fields['field_bdd52fc']);
        $address_city = sanitize_text_field($form_fields['field_5a3a81c']);
        $address_province = sanitize_text_field($form_fields['province']);
        $address_postal_code = sanitize_text_field($form_fields['field_f40e25d']);
        $non_private_residence = isset($form_fields['field_1975c55']) ? 'Yes' : 'No';
        $use_private_address = isset($form_fields['field_878cbc6']) ? 'Yes' : 'No';
        $shipping_address = sanitize_text_field($form_fields['shipping_address']);
        $agreement = isset($form_fields['field_9a627d8']) ? 'Yes' : 'No';
        $disclosure_consent = isset($form_fields['field_8aa6db5']) ? 'Yes' : 'No';
        $delivery_consent = isset($form_fields['field_f889f63']) ? 'Yes' : 'No';
        $third_party_consent = isset($form_fields['field_176dbec']) ? 'Yes' : 'No';
        $digital_signature = sanitize_text_field($form_fields['field_18ba580']);
        $email = sanitize_text_field($form_fields['email']);
        $phone_1 = sanitize_text_field($form_fields['phone_1']);
        $phone_2 = sanitize_text_field($form_fields['phone_2']);
        $phone_3 = sanitize_text_field($form_fields['phone_3']);
        
        // Validate form data (add your own validation as needed)

        // $reg_data = array(
        //     'title' => $title,
        //     'gender' => $gender,
        //     'telephone_1' => $billing_phone,
        //     'telephone_2' => $alternate_phone,
        //     'street_1' => $address_street,
        //     'street_2' => $address_pobox,
        //     'city' => $address_city,
        //     'province' => $address_province,
        //     'postal_code' => $address_postal_code,
        //     'mailing_street_1' => $mailing_street,
        //     'mailing_street_2' => $mailing_pobox,
        //     'mailing_city' => $mailing_city,
        //     'mailing_province' => $mailing_province,
        //     'mailing_postal_code' => $mailing_postal_code,
        // );

        //$update_response = update_registration_details_on_ample($client_id, $active_registration_id, $reg_data);

        $handler->add_response_data( 'redirect_url', home_url( '/my-account/?registration=success' ) );
        return;
    }
}
add_action( 'elementor_pro/forms/new_record', 'handle_elementor_form', 10, 2 );

?>