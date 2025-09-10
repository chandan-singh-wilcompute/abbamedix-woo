<?php
if (!defined('ABSPATH')) {
    exit;
}
require_once plugin_dir_path(__FILE__) . '/customer-functions.php';

// loading custom css
// wp_enqueue_style('ample-connect-styles2', plugin_dir_url(__FILE__) . '../assets/css/ample-connect.css');
add_action('admin_enqueue_scripts', function () {
    wp_enqueue_style('ample-connect-styles2', plugin_dir_url(__FILE__) . 'assets/css/admin.css');
});

add_action('wp_enqueue_scripts', function() {
    wp_enqueue_script('wc-cart');
    wp_enqueue_script('wc-cart-fragments');
    wp_enqueue_script('gram-quota-script', dirname(plugin_dir_url(__FILE__)) . '/assets/js/gram-quota.js', ['jquery'], null, true);
    wp_localize_script('gram-quota-script', 'GramQuotaAjax', [
        'ajax_url' => admin_url('admin-ajax.php'),
    ]);
});


function is_login_page() {
    return in_array($GLOBALS['pagenow'], ['wp-login.php', 'wp-register.php']);
}

function setup_session_for_user_once() {

    if (!is_user_logged_in()) {
        return;
    }

    // Avoid admin, login pages, or AJAX calls
    if (current_user_can( 'manage_options' ) || is_login_page() || wp_doing_ajax()) {
        return;
    }

    // More robust prevention using session cache instead of just static variable
    static $already_run = false;
    if ($already_run) {
        ample_connect_log("Setup session blocked by static variable");
        return;
    }
    
    // Also check if initialization is currently in progress (cross-request protection)
    $initialization_in_progress = Ample_Session_Cache::get('initialization_in_progress');
    if ($initialization_in_progress && (time() - $initialization_in_progress) < 30) {
        ample_connect_log("Session initialization in progress (started " . (time() - $initialization_in_progress) . " seconds ago), skipping");
        return;
    }
    
    // Set lock before proceeding
    Ample_Session_Cache::set('initialization_in_progress', time());
    $already_run = true;

    setup_session_for_user();
    
    // Remove lock after completion
    Ample_Session_Cache::set('initialization_in_progress', null);
}
// add_action('template_redirect', 'setup_session_for_user_once');
add_action('wp', 'setup_session_for_user_once');

function setup_session_for_user() {
    ample_connect_log("Setup session for user called");
    
    $user = wp_get_current_user();

    // Global lock to prevent multiple simultaneous API calls
    static $api_initialization_in_progress = false;
    if ($api_initialization_in_progress) {
        ample_connect_log("Session initialization already in progress, skipping");
        return;
    }

    // Check if session is initialized and has required data
    $session_initialized = Ample_Session_Cache::get('session_initialized');
    $purchasable_products = Ample_Session_Cache::get('purchasable_products');
    $initialization_timestamp = Ample_Session_Cache::get('initialization_timestamp');

    // Prevent re-initialization if it was done recently (within last 5 minutes)
    if ($session_initialized && $initialization_timestamp && (time() - $initialization_timestamp) < 300) {
        ample_connect_log("Session recently initialized, skipping re-initialization");
        
        // Still check for order if user is approved but has no order
        $order_id = Ample_Session_Cache::get('order_id', false);
        $status = Ample_Session_Cache::get('status', 'Lead');
        if ($status == "Approved" && !$order_id) {
            $api_initialization_in_progress = true;
            get_order_from_api_and_update_session($user->ID);
            $api_initialization_in_progress = false;
        }
        return;
    }

    // Re-initialize if session is missing or incomplete
    if (!$session_initialized || !$purchasable_products) {
        $api_initialization_in_progress = true;
        ample_connect_log("Re-initializing session data for user: " . $user->ID);
        
        Client_Information::fetch_information();
        
        get_purchasable_products_and_store_in_session($user->ID);
        // get_order_from_api_and_update_session($user->ID);
        // get_shipping_rates_and_store_in_session($user->ID);
        
        Ample_Session_Cache::set('session_initialized', true);
        Ample_Session_Cache::set('initialization_timestamp', time());
        $api_initialization_in_progress = false;
    }

    $order_id = Ample_Session_Cache::get('order_id', false);
    $status = Ample_Session_Cache::get('status', 'Lead');
    if ($status == "Approved" && !$order_id) {
        $api_initialization_in_progress = true;
        get_order_from_api_and_update_session($user->ID);
        $api_initialization_in_progress = false;
    }
}

// Custom Fields add to User Profile Page
function wk_custom_user_profile_fields($user)
{
?>
    <table class="form-table">
        <tr>
            <th><label for="client_id">Client Id</label></th>
            <td>
                <input type="number" name="client_id" id="client_id" value="<?php echo esc_attr(get_the_author_meta('client_id', $user->ID)); ?>" class="regular-text" readonly>
            </td>
        </tr>
        <tr>
            <th><label for="client_login_id">Client Login Id</label></th>
            <td>
                <input type="text" name="client_login_id" id="client_login_id" value="<?php echo esc_attr(get_the_author_meta('client_login_id', $user->ID)); ?>" class="regular-text" readonly>
            </td>
        </tr>
        <tr>
            <th><label for="active_registration_id">Active Registration Id</label></th>
            <td>
                <input type="number" name="active_registration_id" id="active_registration_id" value="<?php echo esc_attr(get_the_author_meta('active_registration_id', $user->ID)); ?>" class="regular-text" readonly>
            </td>
        </tr>
    </table>
<?php
}
add_action('show_user_profile', 'wk_custom_user_profile_fields');
add_action('edit_user_profile', 'wk_custom_user_profile_fields');

// End Custom Fields add to User Profile Page


// Hook into the profile update
add_action('profile_update', 'custom_update_user_profile', 10, 1);

function custom_update_user_profile($user_id)
{
    global $ample_connect_settings;
    if (!$ample_connect_settings['client_profile_update_enabled']) {
        return;
    }
    $client_id = get_user_meta($user_id, 'client_id', true);
    $active_registration_id = get_user_meta($user_id, 'active_registration_id', true);

    $user_info = get_userdata($user_id);
    $first_name = $user_info->first_name;
    $last_name = $user_info->last_name;
    $email = $user_info->user_email;

    $data = [
        'first_name' => $first_name,
        'last_name' => $last_name,
        'email' => $email
    ];

    // Call the function to update the external system
    custom_update_external_system($client_id, $active_registration_id, $data);
}

// Hook into WooCommerce billing address update
add_action('woocommerce_customer_save_address', 'custom_update_user_billing_address', 10, 2);

function custom_update_user_billing_address($user_id, $load_address)
{

    global $ample_connect_settings;
    if (!$ample_connect_settings['client_profile_update_enabled']) {
        return;
    }

    // Don't update during checkout/order placement to avoid unnecessary API calls
    // Check if we're in checkout context or processing an order
    if (is_admin() || wp_doing_ajax() || 
        (function_exists('is_checkout') && is_checkout()) ||
        (isset($_POST['woocommerce_checkout_place_order']) || isset($_POST['_wpnonce']))) {
        return;
    }

    // Check if the updated address is billing address
    if ($load_address !== 'billing') {
        return;
    }

    $billing_phone = get_user_meta($user_id, 'billing_phone', true);
    $billing_address_1 = get_user_meta($user_id, 'billing_address_1', true);
    $billing_address_2 = get_user_meta($user_id, 'billing_address_2', true);
    $billing_city = get_user_meta($user_id, 'billing_city', true);
    $billing_postcode = get_user_meta($user_id, 'billing_postcode', true);
    $client_id = get_user_meta($user_id, 'client_id', true);
    $active_registration_id = get_user_meta($user_id, 'active_registration_id', true);

    $data = [
        'telephone_1' => $billing_phone,
        'street_1' => $billing_address_1,
        'street_2' => $billing_address_2,
        'city' => $billing_city,
        'postal_code' => $billing_postcode
    ];

    // Call the function to update the external system
    custom_update_external_system($client_id, $active_registration_id, $data);
}


function custom_update_user_shipping_address($user_id, $load_address)
{

    global $ample_connect_settings;
    if (!$ample_connect_settings['client_profile_update_enabled']) {
        return;
    }

    // Don't update during checkout/order placement to avoid unnecessary API calls
    // Check if we're in checkout context or processing an order
    if (is_admin() || wp_doing_ajax() || 
        (function_exists('is_checkout') && is_checkout()) ||
        (isset($_POST['woocommerce_checkout_place_order']) || isset($_POST['_wpnonce']))) {
        return;
    }

    // Check if the updated address is shipping address
    if ($load_address !== 'shipping') {
        return;
    }

    $shipping_phone = get_user_meta($user_id, 'shipping_phone', true);
    $shipping_address_1 = get_user_meta($user_id, 'shipping_address_1', true);
    $shipping_address_2 = get_user_meta($user_id, 'shipping_address_2', true);
    $shipping_city = get_user_meta($user_id, 'shipping_city', true);
    $shipping_postcode = get_user_meta($user_id, 'shipping_postcode', true);
    $client_id = get_user_meta($user_id, 'client_id', true);
    $active_registration_id = get_user_meta($user_id, 'active_registration_id', true);

    $data = [
        'telephone_2' => $shipping_phone,
        'mailing_street_1' => $shipping_address_1,
        'mailing_street_2' => $shipping_address_2,
        'mailing_city' => $shipping_city,
        'mailing_postal_code' => $shipping_postcode
    ];

    custom_update_external_system($client_id, $active_registration_id, $data);
}
add_action('woocommerce_customer_save_address', 'custom_update_user_shipping_address', 10, 2);


function custom_update_external_system($client_id, $active_registration_id, $data)
{
    $update_url = AMPLE_CONNECT_CLIENTS_URL . "/{$client_id}/registrations/{$active_registration_id}";
    //$update_url = "https://medbox.sandbox.onample.com/api/v2/clients/$client_id/registrations/$active_registration_id";
    $update_data = ample_request($update_url, 'PUT', $data);

    if ($update_data['status'] != 'success') {
        error_log('Update failed: ' . $update_data['message']);
    }

}

add_action( 'wp_ajax_fetch_and_store_product_data', 'save_api_products_to_temp_file' );
function save_api_products_to_temp_file() {
    // ✅ Check if user has admin rights
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_send_json_error( 'You are not allowed to perform this action.' );
    }

    $api_url = AMPLE_CONNECT_API_BASE_URL . '/v3/products/public_listing';
    $products = ample_request($api_url);

    $upload_dir = wp_upload_dir();
    $file_path  = trailingslashit( $upload_dir['basedir'] ) . 'temp_products.json';

    if ( file_put_contents( $file_path, json_encode( $products ) ) === false ) {
        wp_send_json_error( 'Failed to write file' );
    }

    wp_send_json_success( array(
        'message' => 'Product data saved to file',
        'total_products' => count( $products )
    ) );

}

function process_product_batch_from_file( $batch_size = 50 ) {
    $upload_dir = wp_upload_dir();
    $file_path  = trailingslashit( $upload_dir['basedir'] ) . 'temp_products.json';

    if ( ! file_exists( $file_path ) ) {
        return 'No file to process';
    }

    $all_products = json_decode( file_get_contents( $file_path ), true );

    if ( empty( $all_products ) ) {
        unlink( $file_path );
        return array(
            'processed' => 0,
            'remaining' => 0,
            'message' => 'Done. File deleted.',
            'completed' => true
        );
    }

    // Get the first N products
    $batch = array_splice( $all_products, 0, $batch_size );

    $woo_client = new WC_Products();
    foreach ( $batch as $product_data ) {
        // Your existing function to add/update product
        $result = $woo_client->add_custom_variable_product($product_data);
    }

    // Save the remaining data back to file
    file_put_contents( $file_path, json_encode( $all_products ) );

    return array(
        'processed' => count( $batch ),
        'remaining' => count( $all_products ),
        'message' => 'Processed batch of ' . count( $batch ) . ' and ' . count( $all_products ) . ' products remaining!'
    );
}

add_action( 'wp_ajax_run_product_batch_processing', 'handle_ajax_product_batch' );
function handle_ajax_product_batch() {
    $result = process_product_batch_from_file( 50 ); // or whatever batch size
    wp_send_json_success( $result );
}

function load_ample_connect_settings()
{
    global $ample_connect_settings;
    $ample_connect_settings = get_option('ample_connect_settings');
}
add_action('plugins_loaded', 'load_ample_connect_settings');


add_action('wp_ajax_delete_all_products', function() {
    // check_ajax_referer('delete_products_nonce', 'nonce');

    if (!current_user_can('manage_woocommerce')) {
        wp_send_json_error('You do not have permission to do this.');
    }

    // ✅ Delete products
    $woo_client = new WC_Products();
    $woo_client->hard_reset_catalog();
    // $woo_client->clean_product_categories();
    // $woo_client->hard_reset_catalog();

    wp_send_json_success('✅ All products and categories have been deleted.');
});


// Add to My Account menu
function add_medical_info_endpoint()
{
    add_rewrite_endpoint('medical-information', EP_ROOT | EP_PAGES);
}
add_action('init', 'add_medical_info_endpoint');


function add_medical_info_link_my_account($items)
{
    $new_items = [];

    foreach ($items as $key => $value) {
        if ($key === 'customer-logout') {
            // Add Medical Information before Logout
            $new_items['medical-information'] = __('Medical Information', 'textdomain');
        }
        $new_items[$key] = $value;
    }

    return $new_items;
}
add_filter('woocommerce_account_menu_items', 'add_medical_info_link_my_account');

// Display and handle Medical Information form
function medical_info_content()
{
    $user_id = get_current_user_id();

    // Retrieve existing user meta data
    $medicare_option = get_user_meta($user_id, 'medicare_option', true);
    $medical_condition = get_user_meta($user_id, 'medical_condition', true);
    $other_treatment = get_user_meta($user_id, 'other_treatment', true);
    $side_effects = get_user_meta($user_id, 'side_effects', true);
    $pregnancy_status = get_user_meta($user_id, 'pregnancy_status', true);
    $medical_conditions = get_user_meta($user_id, 'medical_conditions', true);
    $seeking_treatment_for = get_user_meta($user_id, 'seeking_treatment_for', true);
    $medications = get_user_meta($user_id, 'medications', true);
    $allergies = get_user_meta($user_id, 'allergies', true);
    $allergies_list = get_user_meta($user_id, 'allergies_list', true);

    // Handle form submission
    if (isset($_POST['submit_medical_info'])) {
        // Sanitize and update user meta
        $medicare_option = sanitize_text_field($_POST['medicare_option']);
        update_user_meta($user_id, 'medicare_option', $medicare_option);

        $medical_condition = sanitize_text_field($_POST['medical_condition']);
        update_user_meta($user_id, 'medical_condition', $medical_condition);

        $other_treatment = isset($_POST['other_treatment']) ? sanitize_text_field($_POST['other_treatment']) : '';
        update_user_meta($user_id, 'other_treatment', $other_treatment);

        $side_effects = isset($_POST['side_effects']) ? sanitize_text_field($_POST['side_effects']) : '';
        update_user_meta($user_id, 'side_effects', $side_effects);

        $pregnancy_status = isset($_POST['pregnancy_status']) ? sanitize_text_field($_POST['pregnancy_status']) : '';
        update_user_meta($user_id, 'pregnancy_status', $pregnancy_status);

        $medical_conditions = isset($_POST['medical_conditions']) ? array_map('sanitize_text_field', $_POST['medical_conditions']) : [];
        update_user_meta($user_id, 'medical_conditions', $medical_conditions);

        $seeking_treatment_for = isset($_POST['seeking_treatment_for']) ? array_map('sanitize_text_field', $_POST['seeking_treatment_for']) : [];
        update_user_meta($user_id, 'seeking_treatment_for', $seeking_treatment_for);

        $seeking_treatment_for_other_list = sanitize_textarea_field($_POST['seeking_treatment_for_other_list']);
        update_user_meta($user_id, 'seeking_treatment_for_other_list', $seeking_treatment_for_other_list);

        $medications = sanitize_textarea_field($_POST['medications']);
        update_user_meta($user_id, 'medications', $medications);

        $allergies = isset($_POST['allergies']) ? sanitize_text_field($_POST['allergies']) : '';
        update_user_meta($user_id, 'allergies', $allergies);

        $allergies_list = sanitize_textarea_field($_POST['allergies_list']);
        update_user_meta($user_id, 'allergies_list', $allergies_list);

        // Display a success message
        wc_add_notice(__('Medical Information updated successfully.', 'textdomain'), 'success');
    }


    // Display the form
?>
    <h3><?php _e('Medical Information', 'textdomain'); ?></h4>
        <form method="post">
            <p>Please select one of the following options:</p>
            <p>
                <input type="radio" name="medicare_option" value="medicare_details" <?php checked($medicare_option, 'medicare_details'); ?> /> Medicare Details - Number, Reference Number, and Expiry<br />
                <input type="radio" name="medicare_option" value="ihi" <?php checked($medicare_option, 'ihi'); ?> /> Individual health identifier (IHI)<br />
                <input type="radio" name="medicare_option" value="no_medicare_ihi" <?php checked($medicare_option, 'no_medicare_ihi'); ?> /> I do not have either a Medicare or IHI Number
            </p>

            <p>1. Have you had a medical condition that has lasted more than 3 months and have seen a doctor previously about it?</p>
            <p>
                <input type="radio" name="medical_condition" value="yes" <?php checked($medical_condition, 'yes'); ?> /> Yes<br />
                <input type="radio" name="medical_condition" value="no" <?php checked($medical_condition, 'no'); ?> /> No
            </p>

            <p>2. Have you tried any other treatment/conventional medications for your condition?</p>
            <p>
                <input type="radio" name="other_treatment" value="yes" <?php checked($other_treatment, 'yes'); ?> /> Yes<br />
                <input type="radio" name="other_treatment" value="no" <?php checked($other_treatment, 'no'); ?> /> No
            </p>

            <p>3. Did you get unwanted side effects or did not get complete relief from your other treatments?</p>
            <p>
                <input type="radio" name="side_effects" value="yes" <?php checked($side_effects, 'yes'); ?> /> Yes<br />
                <input type="radio" name="side_effects" value="no" <?php checked($side_effects, 'no'); ?> /> No
            </p>

            <p>4. Are you currently pregnant, breastfeeding or trying to conceive?</p>
            <p>
                <input type="radio" name="pregnancy_status" value="yes" <?php checked($pregnancy_status, 'yes'); ?> /> Yes<br />
                <input type="radio" name="pregnancy_status" value="no" <?php checked($pregnancy_status, 'no'); ?> /> No
            </p>

            <p>5. Do you suffer from any of the following conditions? Please select all that apply</p>
            <p>
                <input type="checkbox" name="medical_conditions[]" value="Psychosis" <?php if (in_array('Psychosis', (array) $medical_conditions)) echo 'checked'; ?> /> Psychosis<br />
                <input type="checkbox" name="medical_conditions[]" value="Bipolar Disorder" <?php if (in_array('Bipolar Disorder', (array) $medical_conditions)) echo 'checked'; ?> /> Bipolar Disorder<br />
                <input type="checkbox" name="medical_conditions[]" value="Mood Disorder" <?php if (in_array('Mood Disorder', (array) $medical_conditions)) echo 'checked'; ?> /> Mood Disorder<br />
                <input type="checkbox" name="medical_conditions[]" value="Cardiopulmonary Disorder" <?php if (in_array('Cardiopulmonary Disorder', (array) $medical_conditions)) echo 'checked'; ?> /> Cardiopulmonary Disorder<br />
                <input type="checkbox" name="medical_conditions[]" value="ADHD" <?php if (in_array('ADHD', (array) $medical_conditions)) echo 'checked'; ?> /> ADHD<br />
                <input type="checkbox" name="medical_conditions[]" value="History of drug dependence or opioid replacement therapy" <?php if (in_array('History of drug dependence or opioid replacement therapy', (array) $medical_conditions)) echo 'checked'; ?> /> History of drug dependence or opioid replacement therapy<br />
                <input type="checkbox" name="medical_conditions[]" value="Chronic Liver Disease" <?php if (in_array('Chronic Liver Disease', (array) $medical_conditions)) echo 'checked'; ?> /> Chronic Liver Disease<br />
                <input type="checkbox" name="medical_conditions[]" value="Respiratory Disease" <?php if (in_array('Respiratory Disease', (array) $medical_conditions)) echo 'checked'; ?> /> Respiratory Disease
            </p>

            <p>6. Please select a condition/s you are suffering from which you are seeking treatment for:</p>
            <p>
                <input type="checkbox" name="seeking_treatment_for[]" value="Pain" <?php if (in_array('Pain', (array) $seeking_treatment_for)) echo 'checked'; ?> /> Pain<br />
                <input type="checkbox" name="seeking_treatment_for[]" value="Anxiety" <?php if (in_array('Anxiety', (array) $seeking_treatment_for)) echo 'checked'; ?> /> Anxiety<br />
                <input type="checkbox" name="seeking_treatment_for[]" value="Depression" <?php if (in_array('Depression', (array) $seeking_treatment_for)) echo 'checked'; ?> /> Depression<br />
                <input type="checkbox" name="seeking_treatment_for[]" value="Injury" <?php if (in_array('Injury', (array) $seeking_treatment_for)) echo 'checked'; ?> /> Injury<br />
                <input type="checkbox" name="seeking_treatment_for[]" value="Eating Disorder" <?php if (in_array('Eating Disorder', (array) $seeking_treatment_for)) echo 'checked'; ?> /> Eating Disorder<br />
                <input type="checkbox" name="seeking_treatment_for[]" value="Nausea and vomiting (chemo-induced)" <?php if (in_array('Nausea and vomiting (chemo-induced)', (array) $seeking_treatment_for)) echo 'checked'; ?> /> Nausea and vomiting (chemo-induced)<br />
                <input type="checkbox" name="seeking_treatment_for[]" value="Nausea and vomiting (non-chemo-induced)" <?php if (in_array('Nausea and vomiting (non-chemo-induced)', (array) $seeking_treatment_for)) echo 'checked'; ?> /> Nausea and vomiting (non-chemo-induced)<br />
                <input type="checkbox" name="seeking_treatment_for[]" value="Loss of appetite" <?php if (in_array('Loss of appetite', (array) $seeking_treatment_for)) echo 'checked'; ?> /> Loss of appetite<br />
                <input type="checkbox" name="seeking_treatment_for[]" value="ADHD" <?php if (in_array('ADHD', (array) $seeking_treatment_for)) echo 'checked'; ?> /> ADHD<br />
                <input type="checkbox" name="seeking_treatment_for[]" value="PTSD" <?php if (in_array('PTSD', (array) $seeking_treatment_for)) echo 'checked'; ?> /> PTSD<br />
                <input type="checkbox" name="seeking_treatment_for[]" value="Inflammation" <?php if (in_array('Inflammation', (array) $seeking_treatment_for)) echo 'checked'; ?> /> Inflammation<br />
                <input type="checkbox" name="seeking_treatment_for[]" value="Parkinsons" <?php if (in_array('Parkinsons', (array) $seeking_treatment_for)) echo 'checked'; ?> /> Parkinsons<br />
                <input type="checkbox" name="seeking_treatment_for[]" value="Cerebral Palsy" <?php if (in_array('Cerebral Palsy', (array) $seeking_treatment_for)) echo 'checked'; ?> /> Cerebral Palsy<br />
                <input type="checkbox" name="seeking_treatment_for[]" value="Epilepsy" <?php if (in_array('Epilepsy', (array) $seeking_treatment_for)) echo 'checked'; ?> /> Epilepsy<br />
                <input type="checkbox" name="seeking_treatment_for[]" value="Cancer" <?php if (in_array('Cancer', (array) $seeking_treatment_for)) echo 'checked'; ?> /> Cancer<br />
                <input type="checkbox" name="seeking_treatment_for[]" value="Chrons/Ulcerative Colitis/IBS" <?php if (in_array('Chrons/Ulcerative Colitis/IBS', (array) $seeking_treatment_for)) echo 'checked'; ?> /> Chrons/Ulcerative Colitis/IBS<br />
                <input type="checkbox" name="seeking_treatment_for[]" value="Headaches/Migraines" <?php if (in_array('Headaches/Migraines', (array) $seeking_treatment_for)) echo 'checked'; ?> /> Headaches/Migraines<br />
                <input type="checkbox" name="seeking_treatment_for[]" value="Other" <?php if (in_array('Other', (array) $seeking_treatment_for)) echo 'checked'; ?> /> Other
            </p>

            <p>If Other, please list</p>
            <p>
                <textarea name="seeking_treatment_for_other_list" rows="4" cols="50" style="width: 100%;"><?php echo esc_textarea($seeking_treatment_for_other_list); ?></textarea>
            </p>

            <p>7. What medication/s have you been prescribed in the past or are currently taking to help manage your condition?</p>
            <p>
                <textarea name="medications" rows="4" cols="50" style="width: 100%;"><?php echo esc_textarea($medications); ?></textarea>
            </p>

            <p>8. Do you have any allergies?</p>
            <p>
                <input type="radio" name="allergies" value="yes" <?php checked($allergies, 'yes'); ?> /> Yes<br />
                <input type="radio" name="allergies" value="no" <?php checked($allergies, 'no'); ?> /> No
            </p>

            <p>If yes, please list</p>
            <p>
                <textarea name="allergies_list" rows="4" cols="50" style="width: 100%;"><?php echo esc_textarea($allergies_list); ?></textarea>
            </p>

            <p>
                <input type="submit" name="submit_medical_info" value="<?php _e('Save Medical Information', 'textdomain'); ?>" />
            </p>
        </form>
    <?php
}

add_action('woocommerce_account_medical-information_endpoint', 'medical_info_content');


// Display all meta-data of a variation in the admin panel
add_action('woocommerce_product_after_variable_attributes', 'show_all_variation_meta_in_admin', 10, 3);

function show_all_variation_meta_in_admin($loop, $variation_data, $variation) {
    // Get all meta-data for the current variation
    $meta_data = get_post_meta($variation->ID);

    if (!empty($meta_data)) {
        // Loop through and display each meta key and value
        $counter = 0;
        foreach ($meta_data as $key => $value) {
            if (str_starts_with($key, '_'))
                continue;
            if ($key == "total_sales")
                continue;

            // Display the meta data
            if ($counter % 2 == 0) {
                // Start a new row for every two fields
                echo '<div style="width:100%; display: flex;">';
            }
            echo '<div class="form-group form-group-half" style="width: 48%; margin-right: 2%;">';
            // echo '<div class="form-row form-row-full">';
            echo '<label for="' . $key .'_' . $variation->ID . '">' . $key . ':</label>';
            echo '<input type="text" id="' . $key .'_' . $variation->ID . '" value="' . esc_html($value[0]) . '" readonly style="width: 100%;">';
            echo '</div>';

            $counter++;

            if ($counter % 2 == 0) {
                // Close the row after two fields
                echo '</div>';
            }
        }

        // Close any unclosed row
        if ($counter % 2 != 0) {
            echo '</div>';
        }

    } else {
        echo '<div class="form-row form-row-full">';
        echo '<p>No meta-data found for this variation.</p>';
        echo '</div>';
    }
}


// Extendend Woocomerce customer query to support meta fields to filter
add_filter('woocommerce_rest_customer_query', 'add_meta_query_to_customer_search', 10, 2);

function add_meta_query_to_customer_search($args, $request) {
    if (!empty($request['meta_key']) && !empty($request['meta_value'])) {
        $args['meta_query'] = [
            [
                'key' => sanitize_text_field($request['meta_key']),
                'value' => sanitize_text_field($request['meta_value']),
                'compare' => '='
            ]
        ];
    }
    return $args;
}


// // Add "Manage Cards" to WooCommerce My Account menu
// add_filter('woocommerce_account_menu_items', 'add_manage_cards_menu_item');
// function add_manage_cards_menu_item($menu_items) {
//     $menu_items['manage-cards'] = 'Manage Cards';
//     return $menu_items;
// }

// add_filter('template_include', function($template) {
//     if ( is_account_page() && get_query_var('manage-cards') !== false ) {
//         return get_stylesheet_directory() . '/manage-cards-template.php';
//     }
//     return $template;
// });

// // Add endpoint for "Manage Cards"
// add_action('init', 'add_manage_cards_endpoint');
// function add_manage_cards_endpoint() {
//     add_rewrite_endpoint('manage-cards', EP_ROOT | EP_PAGES);
// }

// Render content for "Manage Cards"
// add_action('woocommerce_account_manage-cards_endpoint', 'manage_cards_content');
// function manage_cards_content() {
//     //if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_card'])) {
//     //     process_card_submission();
//     // } else
//     if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_token'])) {
//         delete_saved_card();
//     }

//     display_saved_cards();
//     display_add_card_form();
// }

// Display saved cards
add_shortcode('my_manage_cards_ui', 'display_saved_cards');
function display_saved_cards() {
    
    // $credit_cards = Client_Information::get_credit_cards();
    $credit_cards = Ample_Session_Cache::get('credit_cards');
    // $user_id = get_current_user_id();

    // // Get the client id of the customer
    // $client_id = get_user_meta($user_id, "client_id", true);
    echo '<h3>Your Saved Cards</h3>';
    if (empty($credit_cards)) {
        echo '<p class="alert alert-info">No saved cards found.</p>';
        return;
    }

    echo '<div class="savedCard">';
    echo '<table class="table table-hover table-striped">';
    echo '<thead class="thead-light"><tr><th>Card Type</th><th>Card Number</th><th>Expiry</th><th>Action</th></tr></thead>';
    echo '<tbody>';

    foreach ($credit_cards as $credit_card) {
        echo '<tr>';
        echo '<td>' . esc_html($credit_card['brand']) . '</td>';
        echo '<td>' . esc_html($credit_card['protected_card_number']) . '</td>';
        echo '<td>' . esc_html($credit_card['expiry']) . '</td>';
        echo '<td>';
        echo '<form method="POST" action="">';
        echo '<input type="hidden" name="delete_token" value="' . esc_attr($credit_card['id']) . '">';
        echo '<button type="submit" class="btn btn-danger"><i class="bi bi-trash3"></i>&nbsp;Delete</button>';
        echo '</form>';
        echo '</td>';
        echo '</tr>';
    }

    echo '</tbody>';
    echo '</table>';
    echo '</div>';
}

// Display add card form
function display_add_card_form() {
    ?>
    <h3>Manage Cards</h3>
    <button id="toggleAddCardForm" style="margin-bottom: 10px;">Add New Card</button>
    <div id="addCardFormContainer" style="display: none; border: 1px solid #ccc; padding: 15px; margin-top: 10px;">
    
        <h3>Add New Card</h3>
        <form method="POST" id="addCardForm" action="">
            <?php wp_nonce_field("moneris_save_card", "moneris_card_nonce"); ?>
            
            <div>
                <label for="first_name">First Name</label>
                <input type="text" name="first_name" id="first_name" required>
            </div>
            <div>
                <label for="last_name">Last Name</label>
                <input type="text" name="last_name" id="last_name" required>
            </div>
            <div>
                <label for="street_name">Street Name</label>
                <input type="text" name="street_name" id="street_name" required>
            </div>
            <div>
                <label for="street_number">Street Number</label>
                <input type="text" name="street_number" id="street_number" required>
            </div>
            <div>
                <label for="postal_code">Postal Code</label>
                <input type="text" name="postal_code" id="postal_code" required>
            </div>

            <div style="margin-top:15px;">
                <label>Credit Card Details</label>
                <iframe id="monerisFrame" src="https://esqa.moneris.com/HPPtoken/index.php?id=ht37S4JVAQ3VT7S&pmmsg=true&css_body=background:white;&css_textbox=border-width:2px;margin-top:5px;border-radius:5px;&display_labels=1&css_label_pan=float:left;width:25%;font-size:1.15em;&css_label_exp=float:left;width:25%;font-size:1.15em;&css_label_cvd=float:left;width:25%;font-size:1.15em;&css_textbox_pan=width:140px;&enable_exp=1&css_textbox_exp=width:40px;&enable_cvd=1&css_textbox_cvd=width:40px&enable_exp_formatting=1&enable_cc_formatting=1" frameborder="0" width="300px" height="200px"></iframe>
            </div>
            <div>
                <input type="hidden" id="moneris_data_key" name="moneris_data_key">
                <button type="button" onclick="doMonerisSubmit();" name="submit_card">Save Card</button>
                
                <!-- <button type="button" onclick="doMonerisSubmit();"><?php esc_html_e("Submit Card Details", "woocommerce"); ?></button> -->

            </div>
        </form>
    </div>

    <script>
        // Trigger Moneris tokenization
        function doMonerisSubmit() {
            var monFrameRef = document.getElementById("monerisFrame").contentWindow;
            monFrameRef.postMessage("tokenize", "https://esqa.moneris.com/HPPtoken/index.php");
            return false;
        }

        // Handle the tokenization response from Moneris
        var respMsg = function (e) {
            var respData = JSON.parse(e.data); // Moneris returns a JSON response
            console.log("Resp Data: ", respData);
            if (respData.responseCode[0] === "001" && respData.dataKey) {
                document.getElementById("moneris_data_key").value = respData.dataKey;
                document.getElementById("addCardForm").submit();
            } else {
                alert("Failed to tokenize credit card. Please try again.");
            }
        };

        window.onload = function () {
            if (window.addEventListener) {
                window.addEventListener("message", respMsg, false);
            } else if (window.attachEvent) {
                window.attachEvent("onmessage", respMsg);
            }
        };

        document.addEventListener("DOMContentLoaded", function () {
            const toggleButton = document.getElementById("toggleAddCardForm");
            const addCardFormContainer = document.getElementById("addCardFormContainer");

            toggleButton.addEventListener("click", function () {
                if (addCardFormContainer.style.display === "none") {
                    addCardFormContainer.style.display = "block";
                } else {
                    addCardFormContainer.style.display = "none";
                }
            });
        });
    </script>
    <?php
}


/**
 * Handle form submission to save a card.
 */
add_action("template_redirect", "mcm_process_add_card_form");
function mcm_process_add_card_form() {
    if (!isset($_POST["moneris_card_nonce"]) || !wp_verify_nonce($_POST["moneris_card_nonce"], "moneris_save_card")) {
        // wc_add_notice("Invalid submission.", "error");
        return;
    }

    $first_name = sanitize_text_field($_POST['first_name']);
    $last_name = sanitize_text_field($_POST['last_name']);
    $street_name = sanitize_text_field($_POST['street_name']);
    $street_number = sanitize_text_field($_POST['street_number']);
    $postal_code = sanitize_text_field($_POST['postal_code']);
    $data_key = sanitize_text_field($_POST['moneris_data_key']);

    if (add_credit_card_token($data_key, $street_name, $street_number, $postal_code)) {
        wc_add_notice('Card saved successfully!', 'success');
    } else {
        wc_add_notice('Failed to save card. Please try again.', 'error');
    }

    unset($_SESSION['postdata']);
}

// Delete a saved card
function delete_saved_card() {
    if (isset($_POST['delete_token'])) {
        $token_id = intval($_POST['delete_token']);

        if (remove_credit_card_token($token_id)) {
            wc_add_notice('Card deleted successfully.', 'success');
        } else {
            wc_add_notice('Failed to delete card.', 'error');
        }
        unset($_SESSION['postdata']);
    }
}


function handle_shipping_method_selected() {
    if (!isset($_POST['shipping_method'])) {
        wp_send_json_error("No shipping method received");
        return;
    }

    $shipping_method = sanitize_text_field($_POST['shipping_method']);
    // ample_connect_log("shipping method selected = ");
    // ample_connect_log($shipping_method);
    $user_id = get_current_user_id();
    // Get the client id of the customer
    $client_id = get_user_meta($user_id, 'client_id', true);

    // $order = get_order_id_from_api();
    $order_id = Ample_Session_Cache::get('order_id');

    $api_url = AMPLE_CONNECT_PORTAL_URL . "/orders/{$order_id}/set_shipping_rate";
    $body = array(
        'shipping_rate_id' => $shipping_method,
        'client_id' => $client_id
    );

    $body_data = ample_request($api_url, 'PUT', $body);

    if ($body_data) {
        if (is_array($body_data) && array_key_exists('id', $body_data)) {
            store_current_order_to_session($body_data, $user_id);
            WC()->cart->calculate_totals();
        }
        wp_send_json_success($body_data);
    } else {
        // get_order_from_api_and_update_session();
        wp_send_json_error("API request failed: " . $response->get_error_message());
    }
}
add_action('wp_ajax_shipping_method_selected', 'handle_shipping_method_selected');
add_action('wp_ajax_nopriv_shipping_method_selected', 'handle_shipping_method_selected');


function get_prescription_data() {
    // Verify nonce for security
    check_ajax_referer('ample_nonce_data', 'security');

    // Get the data from the request
    $client_id = isset($_POST['client_id']) ? sanitize_text_field($_POST['client_id']) : '';

    // Retrieve from session if exists and not expired
    $cached = WC()->session->get('prescription_data');

    if (
        !empty($cached) &&
        isset($cached['client_id'], $cached['timestamp'], $cached['data']) &&
        $cached['client_id'] === $client_id &&
        (time() - $cached['timestamp']) < 1800 // 30 minutes = 1800 seconds
    ) {
        wp_send_json_success($cached['data']);
    }

    // Do something with the data
    $presc_data = get_prescription_details($client_id);

    // Save to session with timestamp
    WC()->session->set('prescription_data', [
        'client_id' => $client_id,
        'data'      => $presc_data,
        'timestamp' => time(),
    ]);

    // Send the response back
    wp_send_json_success($presc_data);
}
// Hook for logged-in users
add_action('wp_ajax_get_prescription_data', 'get_prescription_data');
add_action('wp_ajax_nopriv_get_prescription_data', 'get_prescription_data');


function get_registration_data() {
    // Verify nonce for security
    check_ajax_referer('ample_nonce_data', 'security');

    // Get the data from the request
    $client_id = isset($_POST['client_id']) ? sanitize_text_field($_POST['client_id']) : '';
    $reg_id = isset($_POST['reg_id']) ? sanitize_text_field($_POST['reg_id']) : '';

    if ($reg_id == '') 
        wp_send_json_success([]);

    // Do something with the data
    $reg_data = get_registration_details($client_id, $reg_id);
    // Send the response back
    wp_send_json_success($reg_data);
}
// Hook for logged-in users
add_action('wp_ajax_get_registration_data', 'get_registration_data');
// Hook for non-logged-in users
add_action('wp_ajax_nopriv_get_registration_data', 'get_registration_data');


// Function to update registration data
function update_registration_data() {
    // Verify nonce for security
    check_ajax_referer('ample_nonce_data', 'security');

    // Get the data from the request
    $client_id = isset($_POST['client_id']) ? sanitize_text_field($_POST['client_id']) : '';
    $reg_id = isset($_POST['reg_id']) ? sanitize_text_field($_POST['reg_id']) : '';
    $form_data = isset($_POST['form_data']) ? sanitize_text_field($_POST['form_data']) : '';

    if ($reg_id == '') 
        wp_send_json_success([]);

    parse_str($form_data, $reg_data);

    // Do something with the data
    $reg_data = update_registration_details($client_id, $reg_id, $reg_data);
    // Send the response back
    wp_send_json_success($reg_data);
}
// Hook for logged-in users
add_action('wp_ajax_update_registration_data', 'update_registration_data');
// Hook for non-logged-in users
add_action('wp_ajax_nopriv_update_registration_data', 'update_registration_data');


// Order History Page
add_filter('woocommerce_account_orders_columns', 'custom_order_list_columns_with_external');
function custom_order_list_columns_with_external($columns) {
    return [
        'order-number' => __('Order ID', 'woocommerce'),
        'order-date' => __('Date', 'woocommerce'),
        'order-status' => __('Status', 'woocommerce'),
        'order-total' => __('Total', 'woocommerce'),
        'order-actions' => __('Actions', 'woocommerce'),
    ];
}

// Plugin function to provide data
function get_ample_orders() {
    $user_id = get_current_user_id();
    // Get the client id of the customer
    $client_id = get_user_meta($user_id, 'client_id', true);

    $order_url = AMPLE_CONNECT_PORTAL_URL . '/orders';
    $api_url = add_query_arg (
        array( 'per_page' => 50,'client_id' => $client_id, 'order' => 'desc'  ),
        $order_url
    );

    $external_orders = ample_request($api_url);

    return $external_orders;
}
// Register filter
add_filter( 'get_ample_orders', 'get_ample_orders' );

add_shortcode( 'woo_ample_order_history', 'replace_woocommerce_orders_with_api_data' );
// Add custom API-based order history list page
function replace_woocommerce_orders_with_api_data() {
    $user_id = get_current_user_id();
    // Get the client id of the customer
    $client_id = get_user_meta($user_id, 'client_id', true);

    $order_url = AMPLE_CONNECT_PORTAL_URL . '/orders';
    $api_url = add_query_arg (
        array( 'per_page' => 50,'client_id' => $client_id, 'order' => 'desc'  ),
        $order_url
    );

    $external_orders = ample_request($api_url);

    if (is_wp_error($external_orders)) {
        echo "<p style='color:red;'>Error fetching orders from external system.</p>";
        return;
    }

    if ($external_orders[0]['total_entries'] == 0) {
        echo "<p>No orders found.</p>";
        return;
    }

    $external_orders = $external_orders[1];

    // Get all WooCommerce order IDs mapped to external order numbers
    $woocommerce_orders = wc_get_orders(['customer_id' => $user_id]);
    $order_map = [];

    foreach ($woocommerce_orders as $wc_order) {
        $wc_order_id = $wc_order->get_id();
	    $item_count = $wc_order->get_item_count();
        $external_order_number = get_post_meta($wc_order_id, '_external_order_number', true);
        if ($external_order_number) {
            $order_map[$external_order_number] = array(
                'woo_order_id' => $wc_order_id,
                'item_count' => $item_count
            );
        }
    }

    

    // Display the orders in WooCommerce's format
    echo '<table class="shop_table shop_table_responsive my_account_orders">';
    echo '<thead><tr>';
    echo '<th>' . __('Order ID', 'woocommerce') . '</th>';
    echo '<th>' . __('Placed On', 'woocommerce') . '</th>';
    echo '<th>' . __('Items', 'woocommerce') . '</th>';
    echo '<th>' . __('Gram Deduction', 'woocommerce') . '</th>';
    echo '<th>' . __('Status', 'woocommerce') . '</th>';
    echo '<th>' . __('Total', 'woocommerce') . '</th>';
    echo '</tr></thead>';
    echo '<tbody>';

    foreach ($external_orders as $ext_order) {
        $wc_order_details = $order_map[$ext_order['id']] ?? 'N/A';
        $order_url = $wc_order_details['woo_order_id'] !== 'N/A' ? wc_get_endpoint_url('view-order', $wc_order_details['woo_order_id']) : '#';

        echo '<tr>';
        echo '<td><a class="order_number" href="' . esc_url($order_url) . '">#' . esc_html($wc_order_id) . '</a></td>';
        echo '<td>' . esc_html(date('F j, Y', strtotime($ext_order['created_at']))) . '</td>';
        echo '<td>' . $wc_order_details['woo_order_id'] . '</td>';
        echo '<td>' . $wc_order_details['woo_order_id'] . '</td>';
        echo '<td>' . esc_html($ext_order['status']) . '</td>';
        echo '<td>' . wc_price($ext_order['final_cost']) . '</td>';
        echo '<td><a href="' . esc_url($order_url) . '" class="button">View</a>';
        if ($ext_order['status'] == 'Order Placed')
            echo '&nbsp;<a href="' . esc_url($order_url) . '" class="button">Cancel</a>';

        echo '</td></tr>';
    }

    echo '</tbody></table>';
}


// Order Details Page
function fetch_external_order_details() {
    global $wp;
    $order_id = !empty($wp->query_vars['view-order']) ? intval($wp->query_vars['view-order']) : 0;

    if (!$order_id) {
        echo "<p style='color:red;'>Invalid order.</p>";
        return;
    }

    $external_order_number = get_post_meta($order_id, '_external_order_number', true);

    // Fetching order-detail from the ample.
    $order_url = AMPLE_CONNECT_WOO_ORDER_URL . $external_order_number;
    
    $order_data = ample_request($order_url);

    if (is_wp_error($order_data)) {
        echo "<p>Unable to fetch order's details.</p>";
        return;
    }

    if (!empty($order_data)) {

        $items = $order_data['order_items'];

        echo '<p class="order_details">Order Details</p>';
        echo "<p><strong>Ample Order ID:</strong> " . esc_html($order_data['id']) . "</p>";
        echo "<p><strong>WooCommerce Order ID:</strong> " . esc_html($order_id) . "</p>";
        echo "<p><strong>Order Status:</strong> " . esc_html($order_data['status']) . "</p>";

        // Order items table
        echo "<h3>Ordered Items</h3>";
        echo "<table style='width:100%; border-collapse: collapse;'>";
        echo "<thead>
                <tr>
                    <th style='border-bottom: 1px solid #ddd; text-align: left; padding: 8px;'>S. No</th>
                    <th style='border-bottom: 1px solid #ddd; text-align: left; padding: 8px;'>Product</th>
                    <th style='border-bottom: 1px solid #ddd; text-align: left; padding: 8px;'>Quantity</th>
                    <th style='border-bottom: 1px solid #ddd; text-align: left; padding: 8px;'>Price</th>
                    <th style='border-bottom: 1px solid #ddd; text-align: left; padding: 8px;'>Total</th>
                </tr>
            </thead>";
        echo "<tbody>";

        $count = 1;
        foreach ($items as $item) {
            echo "<tr>
                    <td style='border-bottom: 1px solid #ddd; padding: 8px;'>" . esc_html($count) . "</td>
                    <td style='border-bottom: 1px solid #ddd; padding: 8px;'>" . esc_html($item['product_name'] . ' ('. $item['sku_name']) . ")</td>
                    <td style='border-bottom: 1px solid #ddd; padding: 8px;'>" . esc_html($item['quantity']) . "</td>
                    <td style='border-bottom: 1px solid #ddd; padding: 8px;'>" . wc_price($item['unit_price']/100) . "</td>
                    <td style='border-bottom: 1px solid #ddd; padding: 8px;'>" . wc_price($item['total_cost']/100) . "</td>
                </tr>";

            $count += 1;
        }

        echo "</tbody></table>";

        // Order summary
        echo "<h3>Order Summary</h3>";
        echo "<p><strong>Subtotal:</strong> " . wc_price($order_data['subtotal']/100) . "</p>";
        echo "<p><strong>Shipping:</strong> " . wc_price($order_data['total_shipping']/100) . "</p>";
        echo "<p><strong>Tax:</strong> " . wc_price($order_data['total_tax']/100) . "</p>";
        echo "<p><strong>Order Total:</strong> " . wc_price($order_data['final_cost']/100) . "</p>";

        // Shipping details
        echo "<h3>Shipping Details</h3>";
        echo "<p><strong>Recipient:</strong> " . esc_html($order_data['shipping_carrier']) . "</p>";
        echo "<p><strong>Tracking ID</strong> " . esc_html($order_data['shipping_tracking_code']) . "</p>";

        echo '<br/><p><a href="' . esc_url(wc_get_account_endpoint_url('orders')) . '" class="button wc-backward">← Back to Orders</a></p>';


    } else {
        echo "<p>Order details are not available at the moment.</p>";
    }
}




// Terpens Details
// Add custom meta box for terpenes in WooCommerce product edit page
function add_terpene_meta_box() {
    add_meta_box(
        'terpene_meta_box',
        'Terpene Details',
        'render_terpene_meta_box',
        'product',
        'normal',
        'high'
    );
}
add_action('add_meta_boxes', 'add_terpene_meta_box');

function render_terpene_meta_box($post) {
    $terpene_data = get_post_meta($post->ID, '_terpene_data', true);
    $terpenes = [
        'Myrcene', 'Limonene', 'Pinene', 'Linalool', 'Caryophyllene', 'Humulene'
    ];
    ?>
    <div id="terpene-container">
        <?php if (!empty($terpene_data)) : ?>
            <?php foreach ($terpene_data as $key => $data) : ?>
                <div class="terpene-row">
                    <select name="terpene_property[]">
                        <?php foreach ($terpenes as $terpene) : ?>
                            <option value="<?php echo esc_attr($terpene); ?>" <?php selected($data['property'], $terpene); ?>>
                                <?php echo esc_html($terpene); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <input type="text" name="terpene_value[]" value="<?php echo esc_attr($data['value']); ?>" placeholder="Enter value">
                    <button type="button" class="remove-terpene">Remove</button>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
    <button type="button" id="add-terpene">Add Terpene</button>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            document.getElementById('add-terpene').addEventListener('click', function () {
                var container = document.getElementById('terpene-container');
                var row = document.createElement('div');
                row.classList.add('terpene-row');
                row.innerHTML = `
                    <select name="terpene_property[]">
                        <?php foreach ($terpenes as $terpene) : ?>
                            <option value="<?php echo esc_attr($terpene); ?>"><?php echo esc_html($terpene); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <input type="text" name="terpene_value[]" placeholder="Enter value">
                    <button type="button" class="remove-terpene">Remove</button>
                `;
                container.appendChild(row);
            });

            document.addEventListener('click', function (e) {
                if (e.target.classList.contains('remove-terpene')) {
                    e.target.parentElement.remove();
                }
            });
        });
    </script>
    <style>
        .terpene-row {
            display: flex;
            align-items: center;
            margin-bottom: 5px;
        }
        .terpene-row select, .terpene-row input {
            margin-right: 5px;
        }
    </style>
    <?php
}

// Save Terpene Data
function save_terpene_meta_box($post_id) {
    if (isset($_POST['terpene_property']) && isset($_POST['terpene_value'])) {
        $terpene_data = [];
        $properties = $_POST['terpene_property'];
        $values = $_POST['terpene_value'];
        
        foreach ($properties as $index => $property) {
            if (!empty($property) && !empty($values[$index])) {
                $terpene_data[] = [
                    'property' => sanitize_text_field($property),
                    'value' => sanitize_text_field($values[$index]),
                ];
            }
        }

        update_post_meta($post_id, '_terpene_data', $terpene_data);
    }
}
add_action('save_post', 'save_terpene_meta_box');


function get_terpene_details() {
    global $post;
    $terpene_data = get_post_meta($post->ID, '_terpene_data', true);

    if (!empty($terpene_data)) {
        $output = '<div class="terpene-profile elementor-widget-container">';
        
        // Heading section (Ensures it's on a new line)
        $output .= '<div class="elementor-widget elementor-widget-heading">';
        $output .= '<h6 class="elementor-heading-title elementor-size-default" style="margin-bottom: 10px;">Terpene Profile</h6>';
        $output .= '</div>';

        // List wrapper (Without <ul> to remove bullet points)
        $output .= '<div class="elementor-widget elementor-widget-text-editor">';
        
        foreach ($terpene_data as $terpene) {
            $output .= '<div class="terpene-item" style="margin-bottom: 5px;">';
            $output .= '<strong>' . esc_html($terpene['property']) . ':</strong> ' . esc_html($terpene['value']);
            $output .= '</div>';
        }

        $output .= '</div>'; // Close List Wrapper
        $output .= '</div>'; // Close Main Wrapper

        // protencies part


        $output .= '<div id="cannabinoid-profile-variation">Hello</div>';

        $output .= '<script>';
        $output .= 'jQuery(document).ready(function($) {';
        $output .= 'function updateProfileDisplay(variation) {';
        $output .= 'var container = $("#cannabinoid-profile-variation");';
        $output .= 'container.empty();';
        $output .= 'if (variation && variation.meta_data) {';
        $output .= 'const relevantKeys = ["THC", "CBD", "CBG", "CBC", "CBN"];';
        $output .= 'let profileHTML = "<ul class=\"cannabinoid-profile\">";';
        $output .= 'variation.meta_data.forEach(function(meta) {';
        $output .= 'if (relevantKeys.includes(meta.key.toUpperCase())) {';
        $output .= 'profileHTML += `<li><strong>${meta.key}</strong>: ${meta.value}</li>`;';
        $output .= '}';
        $output .= '});';
        $output .= 'profileHTML += "</ul>";';
        $output .= 'container.html(profileHTML);';
        $output .= '}';
        $output .= '}';

        $output .= '$("form.variations_form").on("woocommerce_variation_has_changed", function () {';
        $output .= 'const variationData = $(this).data("product_variations");';
        $output .= 'const selectedAttributes = $(this).find("select");';

        $output .= 'let currentVariation = variationData.find(function(variation) {';
        $output .= 'return selectedAttributes.toArray().every(function(select) {';
        $output .= 'const attr = $(select).data("attribute_name") || $(select).attr("name");';
        $output .= 'return variation.attributes[attr] === $(select).val();';
        $output .= '});';
        $output .= '});';

        $output .= 'updateProfileDisplay(currentVariation);';
        $output .= '});';

        $output .= '$("form.variations_form").trigger("woocommerce_variation_has_changed");';
        $output .= '});';
        $output .= '</script>';

        return $output;
    } else {
        // protencies part

        $output = '<p class="elementor-text-editor text-white">No Terpene Data Available.</p>';
        
        return $output;

    }
}
add_shortcode('terpene_details', 'get_terpene_details');


function show_cannabinoid_profiles() {
    ob_start();
    ?>
    <div id="cannabinoid-profile-display">
        <!-- <strong>Cannabinoid Profile:</strong> -->
        <div id="cannabinoid-profile-content" class="progressWrapper">Select a variation to see cannabinoid profile.</div>
    </div>

    <div id="rx-value-display" class="rxValue">
        <strong>RX Reduction Value:</strong>
        <span id="rx-value-content">Select a variation to see RX reduction value.</span>
    </div>

    <script>
    jQuery(function($) {
    function extractValueCategory(input) {
            const parts = input.split('|').map(part => part.trim());
            let value = null;

            // Try to extract % value
            for (const part of parts) {
                if (part.includes('%')) {
                    const match = part.match(/\d+(\.\d+)?/);
                    if (match) {
                        value = parseFloat(match[0]);
                        break;
                    }
                }
            }

            // If no % found, check for mg/g and divide by 10
            if (value === null) {
                for (const part of parts) {
                    if (part.toLowerCase().includes('mg/g')) {
                        const match = part.match(/\d+(\.\d+)?/);
                        if (match) {
                            value = parseFloat(match[0]) / 10;
                            break;
                        }
                    }
                }
            }

            // Categorize value
            if (value === null) {
                return 0;
            } else if (value >= 1 && value <= 25) {
                return 1;
            } else if (value >= 26 && value <= 50) {
                return 2;
            } else if (value >= 51 && value <= 75) {
                return 3;
            } else if (value >= 76 && value <= 100) {
                return 4;
            } else {
                return 0;
            }
        }    

        function updateCannabinoidProfile(variation) {
            var container = $("#cannabinoid-profile-content");
            container.empty();

            if (variation) {
                var keys = ["THC", "CBD"];
                // var keys = ["THC", "CBD", "CBG", "CBC", "CBN", "TOTAL THC", "TOTAL CBD", "TOTAL CBG", "TOTAL CBC", "TOTAL CBN"];
                var hasData = false;

                keys.forEach(function(key) {
                    if (variation[key]) {
                        hasData = true;
                        let progressVal = extractValueCategory(variation[key]);
                        let divHtml = "";

                        if (progressVal == 0) 
                            divHtml += "<div class='tchProgress'><label>" + key + ":</label><div class='progressContainer'><span></span><span></span><span></span><span></span></div>" + variation[key] + "</div>";

                        else {
                            divHtml = "<div class='tchProgress'><label>" + key + ":</label><div class='progressContainer'>";
                            for (let i = 1; i <= 4; i++) {
                                if (i <= progressVal) 
                                    divHtml += "<span class='active'></span>";
                                else 
                                    divHtml += "<span></span>";
                            }
                            divHtml += "</div>" + variation[key] + "</div>";
                        }

                        container.append(divHtml);
                        
                        //container.append("<div class='tchProgress'><label>" + key + ":</label><div class='progressContainer'><span class='active'></span><span class='active'></span><span></span><span></span></div>" + variation[key] + "</div>");                                   
                    }
                });

                if (!hasData) {
                    container.text("No cannabinoid profile data found for this variation.");
                }
            } else {
                container.text("Select a variation to see cannabinoid profile.");
            }
        }

        $("form.variations_form").on("found_variation", function(event, variation) {
            updateCannabinoidProfile(variation);

            if (variation.rx_reduction) {

                $("#rx-value-content").text(variation.rx_reduction + ' g');
                $("#rx-value-display").addClass('active');
            } else {
                $("#rx-value-content").text("No RX reduction value found for this variation.");
                $("#rx-value-display").removeClass('active');
            }
        }).on("reset_data", function() {
            $("#cannabinoid-profile-content").text("Select a variation to see cannabinoid profile.");
            $("#rx-value-content").text("Select a variation to see RX reduction value.");
        });
    });
    </script>
    <?php
    return ob_get_clean();
}
add_shortcode('potencies', 'show_cannabinoid_profiles');


add_filter('woocommerce_available_variation', 'add_cannabinoid_profile_to_variation');
function add_cannabinoid_profile_to_variation($variation_data) {
    $variation_id = $variation_data['variation_id'];
    $cannabinoids = ['THC', 'CBD'];
    // $cannabinoids = ['THC', 'CBD', 'CBG', 'CBC', 'CBN', "TOTAL THC", "TOTAL CBD", "TOTAL CBG", "TOTAL CBC", "TOTAL CBN"];

    foreach ($cannabinoids as $cannabinoid) {
        $value = get_post_meta($variation_id, $cannabinoid, true);
        if (!empty($value)) {
            $variation_data[$cannabinoid] = $value;
        }
    }

    $rx_val = get_post_meta($variation_id, 'RX Reduction', true);

    if ($rx_val) {
        $variation_data['rx_reduction'] = $rx_val;
    }

    return $variation_data;
}

// Function to process cannabinoid profile values to show as progress bar
function extractValueCategory($input) {
    // Split and trim the values
    $parts = array_map('trim', explode('|', $input));

    $value = null;

    // Try to find and return % value directly
    foreach ($parts as $part) {
        if (strpos($part, '%') !== false) {
            preg_match('/\d+(\.\d+)?/', $part, $match);
            $value = isset($match[0]) ? (float)$match[0] : null;
            break;
        }
    }

    // If no % found, look for mg/g and divide by 10
    if ($value === null) {
        foreach ($parts as $part) {
            if (stripos($part, 'mg/g') !== false) {
                preg_match('/\d+(\.\d+)?/', $part, $match);
                $value = isset($match[0]) ? ((float)$match[0] / 10) : null;
                break;
            }
        }
    }

    // Categorize value
    if ($value === null) {
        return 0;
    } elseif ($value >= 1 && $value <= 25) {
        return 1;
    } elseif ($value >= 26 && $value <= 50) {
        return 2;
    } elseif ($value >= 51 && $value <= 75) {
        return 3;
    } elseif ($value >= 76 && $value <= 100) {
        return 4;
    } else {
        return 0; // Out of expected range
    }
}


add_action('woocommerce_checkout_process', 'validate_selected_shipping_method');
function validate_selected_shipping_method() {
    $chosen_methods = WC()->session->get('chosen_shipping_methods');

    if (!empty($chosen_methods) && in_array('select_shipping_placeholder', $chosen_methods)) {
        wc_add_notice(__('Please select a valid shipping method.'), 'error');
    }
}

add_action('woocommerce_before_checkout_form', function() {
    if (WC()->session) {
        WC()->session->set('shipping_for_package_0', null);
    }
    if (wc()->shipping) {
        wc()->shipping->reset_shipping();
    }
    if (is_checkout() && WC()->cart) {
        ample_connect_log("shipping being recalculated"); 
        WC()->cart->calculate_shipping(); // forces recalc
    }
});


// Customer discount and policies
add_action('woocommerce_review_order_before_payment', 'show_selectable_discounts_on_checkout');
function show_selectable_discounts_on_checkout() {
    $discount_codes = Ample_Session_Cache::get('applicable_discounts', []);
    $applicable_policies = Ample_Session_Cache::get('applicable_policies', []);

    if (empty($discount_codes) && empty($policy_data)) return;

    echo '<div class="woocommerce-checkout-discounts">';
    echo '<h4>Available Discounts</h4>';
    echo '<ul style="list-style: none; padding-left: 0;">';

    // Discount codes with checkboxes
    foreach ($discount_codes as $discount) {
        $desc   = esc_html($discount['description'] ?? $discount['code']);
        $code   = esc_attr($discount['code']);
        $id     = $discount['id'];
        $amount = number_format($discount['amount'] / 100, 2);
        echo "<li><label><input type='checkbox' class='apply-discount discount_checkbox' value='{$id}' data-amount='{$discount['amount']}' data-desc='{$desc}'> {$desc}</label></li>";
    }

    // Policy discount checkbox
    foreach ($applicable_policies as $policy) {
        $label   = esc_html($policy['name']);
        $covers_shipping   = esc_attr($policy['covers_shipping']);
        $id     = $policy['id'];
        $percent = esc_html($policy['percent']);
        echo "<li><label><input type='checkbox' class='apply-policy-discount discount_checkbox' data-shipping='{$covers_shipping}' value='{$id}' data-percentage='{$percent}' data-desc='{$label}'> {$label}</label></li>";
    }

    echo '</ul>';
    echo '</div>';

    // Add hidden fields for AJAX
    // echo '<input type="hidden" name="applied_custom_discount" id="applied_custom_discount" value="">';
    // echo '<input type="hidden" name="applied_policy_discount" id="applied_policy_discount" value="">';
    // echo '<input type="hidden" name="applied_policy_id" id="applied_policy_id" value="">';
}


add_action('wp_footer', 'custom_discount_ajax_script');
function custom_discount_ajax_script() {
    if (!is_checkout()) return;
    ?>
    <script>
    
    jQuery(function($){
        // Apply discounts/policies via AJAX
        function applySelectedDiscounts() {
            let discounts = [];

            // Collect all currently checked checkboxes
            $('.apply-discount:checked, .apply-policy-discount:checked').each(function(){
                discounts.push({
                    id: $(this).val(),
                    amount: $(this).data('amount') || 0,
                    percentage: $(this).data('percentage') || 0,
                    type: $(this).hasClass('apply-policy-discount') ? 'policy' : 'discount',
                    desc: $(this).data('desc') || ''
                });
            });

            $.ajax({
                type: 'POST',
                url: wc_checkout_params.ajax_url,
                data: {
                    action: 'update_discounts',
                    discounts: discounts
                },
                success: function(response) {
                    console.log("policy apply response:");
                    console.log(response);
                    if(response.success){
                        // location.reload();
                        $(document.body).trigger('update_checkout'); // refresh totals
                    } else {
                        console.log('Failed to update discounts:', response);
                    }
                }
            });
        }

        // Debounce for multiple quick changes
        let discountTimer;
        $(document).on('change', '.apply-discount, .apply-policy-discount', function(){
            clearTimeout(discountTimer);
            discountTimer = setTimeout(applySelectedDiscounts, 300); // 300ms delay
        });


        // Run after checkout updates (includes shipping change, payment change, coupon apply etc.)
        $(document.body).on('updated_checkout', function(){
            refreshAppliedDiscounts();
        });

        function refreshAppliedDiscounts(){
            $.ajax({
                type: 'POST',
                url: wc_checkout_params.ajax_url,
                data: { action: 'get_applied_discounts' },
                success: function(response){
                    if(response.success){
                        console.log("Applied discounts = ");
                        console.log(response.data);
                        const applied = response.data.applied || [];
                        // Reset all discount checkboxes
                        $('.discount_checkbox').prop('checked', false);
                        applied.forEach(d => {
                            $('input[value="'+d.id+'"]').prop('checked', true);
                        });
                    }
                }
            });
        }
    });
    </script>
    <?php
}


add_action('wp_ajax_update_discounts', 'handle_update_discounts');
add_action('wp_ajax_nopriv_update_discounts', 'handle_update_discounts');
function handle_update_discounts() {
    if(!WC()->cart) wp_send_json_error('Cart not available');

    $discounts = isset($_POST['discounts']) ? (array) $_POST['discounts'] : [];

    // Previous selections
    $prev_discounts = Ample_Session_Cache::get('applied_discounts', []);
    $prev_policies  = Ample_Session_Cache::get('applied_policies', []);

    $new_discounts = [];
    $new_policies  = [];

    foreach ($discounts as $d) {
        if ($d['type'] === 'policy') {
            $new_policies[$d['id']] = $d;
        } else {
            $new_discounts[$d['id']] = $d;
        }
    }

    // Determine genuine changes
    $to_apply_discounts = array_diff_key($new_discounts, $prev_discounts);
    $to_remove_discounts = array_diff_key($prev_discounts, $new_discounts);

    $to_apply_policies = array_diff_key($new_policies, $prev_policies);
    $to_remove_policies = array_diff_key($prev_policies, $new_policies);
    
    $return_data = [];

    // 🔹 API calls for discounts
    if (!empty($to_apply_discounts)) {
        foreach ($to_apply_discounts as $discount_id => $discount) {
            $return_data[] = add_discount_to_order($discount_id);
        }
    }
    if (!empty($to_remove_discounts)) {
        $app_dis_codes = Ample_Session_Cache::get('applied_discount_codes');
        foreach ($to_remove_discounts as $discount_id => $discount) {
            $return_data[] = remove_discount_from_order($app_dis_codes[$discount_id]);
        }
    }

    // 🔹 API calls for policies
    if (!empty($to_apply_policies)) {
        foreach ($to_apply_policies as $policy_id => $policy) {
            $return_data[] = add_policy_to_order($policy_id);
        }
        
    }
    if (!empty($to_remove_policies)) {
        foreach ($to_remove_policies as $policy_id => $policy) {
            $return_data[] = remove_policy_from_order($policy_id);
        }
    }

    // Update session
    Ample_Session_Cache::set('applied_discounts', $new_discounts);
    Ample_Session_Cache::set('applied_policies', $new_policies);

    wp_send_json_success($return_data);
}

// Return applied discounts for UI restore
add_action('wp_ajax_get_applied_discounts', 'get_applied_discounts');
add_action('wp_ajax_nopriv_get_applied_discounts', 'get_applied_discounts');

function get_applied_discounts() {
    $applied = array_merge(
        Ample_Session_Cache::get('applied_discounts', []),
        Ample_Session_Cache::get('applied_policies', [])
    );
    wp_send_json_success(['applied' => $applied]);
}


add_filter( 'woocommerce_add_to_cart_fragments', 'healfio_update_cart_total_fragment' );
function healfio_update_cart_total_fragment( $fragments ) {
    ob_start();
    ?>
    <tr class="order-total">
        <th><?php esc_html_e( 'Total:', 'healfio' ); ?></th>
        <td data-title="<?php esc_attr_e( 'Total', 'healfio' ); ?>">
            <?php
            if ( WC()->cart->is_empty() ) {
                echo wc_price( 0 );
            } else {
                wc_cart_totals_subtotal_html();
            }
            ?>
        </td>
    </tr>
    <?php
    $fragments['.order-total'] = ob_get_clean();
    return $fragments;
}

// OPTIMIZED: This functionality moved to ample_optimized_cart_totals_calculation
add_action('woocommerce_cart_calculate_fees', 'apply_selected_custom_discount', 20, 1);
function apply_selected_custom_discount($cart) {
    // ample_connect_log("custon discount is being applied");
    if (is_admin() && !defined('DOING_AJAX')) return;

    if ($cart->is_empty()) {
        $cart->fees_api()->remove_all_fees();
        return;
    }

    $custom_taxes = Ample_Session_Cache::get('custom_tax_data');
    if (!empty($custom_taxes) && is_array($custom_taxes)) {
        // ample_connect_log("Custom taxes - ");
        // ample_connect_log($custom_taxes);
        foreach ($custom_taxes as $tax_label => $amount_cents) {
            $amount_dollars = floatval($amount_cents) / 100;

            // Add a fee labeled as tax
            $cart->add_fee(ucfirst($tax_label), $amount_dollars, false); // true => taxable
        }
    }

    $discounts = Ample_Session_Cache::get('applied_discounts', []);
    $policies  = Ample_Session_Cache::get('applied_policies', []);
    // ample_connect_log("Discounts ");
    // ample_connect_log($discounts);
    // ample_connect_log("Policies ");
    // ample_connect_log($policies);
    // Apply fixed amount discounts
    if ($discounts) {
        foreach($discounts as $d) {
            $cart->add_fee($d['desc'], -(floatval($d['amount'])/100), false);
        }
    }
    
    if ($policies) {
        // Apply percentage-based policies
        foreach($policies as $p){
            // $cart_total = $cart->get_subtotal() + $cart->get_shipping_total();
            $cart_total = 0;
            foreach ( $cart->get_cart() as $item ) {
                $cart_total += $item['line_total'] + $item['line_tax'];
            }
            $cart_total += $cart->get_shipping_total() + $cart->get_shipping_tax();
            $fee_total = 0;
            foreach ( $cart->get_fees() as $fee ) {
                $fee_total += $fee->amount;
            }
            $cart_total += $fee_total;
            $discount_amount = $cart_total * (floatval($p['percentage']) / 100);
            $cart->add_fee($p['desc'], -$discount_amount, false);
        }
    }
}

function clear_custom_discount_session() {
    // WC()->session->__unset('applied_custom_discount');
    // WC()->session->__unset('applied_policy_discount');
    Ample_Session_Cache::delete('applied_discounts');
    Ample_Session_Cache::delete('applied_policies');
}
add_action('woocommerce_cart_emptied', 'clear_custom_discount_session');

// WC remove non displayable function
if ( ! function_exists( 'wc_remove_non_displayable_chars' ) ) {
    /**
     * Remove non-displayable (non-printable) characters from a string.
     *
     * @param string $string Input string.
     * @return string Cleaned string.
     */
    function wc_remove_non_displayable_chars( $string ) {
        return preg_replace( '/[^\PC\s]/u', '', $string ); // removes control characters
    }
}

// Order API call before order creation in Woocommerce
add_action('woocommerce_after_checkout_validation', 'validate_checkout_with_external_api', 20, 2);
function validate_checkout_with_external_api($posted_data, $errors) {
    if (!empty($errors->get_error_messages())) {
        return; // Other validation errors exist
    }

    $cart_total = floatval(WC()->cart->get_total('edit')); // float, e.g. 0.00

    if ($cart_total > 0.0) {
        return;
    }

    $response = purchase_order_on_ample();

    // Check response
    if ($response && isset($response["purchased"]) && $response["purchased"] == TRUE) {
    
        $ample_order_id = $response["ample_order_id"];
        $order_note = 'Order placed with Ample Order Id: ' . $ample_order_id; 

        Ample_Session_Cache::set('order_note', $order_note);
        Ample_Session_Cache::set('external_order_number', $response['id']);

    } else {
        $errors->add('api_error', __('There was a problem while placing order on Ample. Please try again.', 'ample-connect-plugin'));
        return;
    }
}

// Setting order details after order processing
add_action('woocommerce_checkout_create_order', 'attach_api_data_to_order', 20, 2);
function attach_api_data_to_order($order, $data) {

    if (floatval($order->get_total()) !== 0.0) {
        return;
    }

    $order_note = Ample_Session_Cache::get('order_note');
    $external_order_number = Ample_Session_Cache::get('external_order_number');

    if ($external_order_number) {
        $order->add_order_note($order_note);
        $order->update_meta_data('_external_order_number', $external_order_number);
    }
}


// 2. Ajax Endpoint to return current grams
add_action('wp_ajax_get_gram_quota_data', 'get_gram_quota_data');
add_action('wp_ajax_nopriv_get_gram_quota_data', 'get_gram_quota_data');
function get_gram_quota_data() {
    // Check if session is available for AJAX requests
    if (!Ample_Session_Cache::is_session_available()) {
        ample_connect_log("Session not available during AJAX request for get_gram_quota_data");
        
        // Try to initialize session for logged-in users
        if (is_user_logged_in()) {
            // Ensure session is initialized
            if (function_exists('WC') && WC() && !WC()->session) {
                WC()->session = new WC_Session_Handler();
                WC()->session->init();
            }
            
            // If still no session, try to setup session for user
            if (!Ample_Session_Cache::is_session_available()) {
                wp_send_json_error([
                    'message' => 'Session not available. Please refresh the page.',
                    'session_error' => true
                ]);
                return;
            }
        } else {
            wp_send_json_error([
                'message' => 'Please log in to view quota information.',
                'login_required' => true
            ]);
            return;
        }
    }

    $details = Ample_Session_Cache::get('policy_details');
    $availble_to_order = Ample_Session_Cache::get('available_to_order', 0);

    if ($details) {
        ample_connect_log("Policy details found in session.");
        $policy_available_grams = floatval($details['policy_remaining']);
        $current_gram_used = floatval($details['current_order_coverage']);
        $prescription_available_grams = $availble_to_order - $current_gram_used;
        Ample_Session_Cache::set('current_on_cart', $current_gram_used);
    } else if ($availble_to_order > 0) {
        ample_connect_log("Policy details not found in session for get_gram_quota_data");
        $total = 0;
        if ( WC()->cart ) {
            foreach ( WC()->cart->get_cart() as $cart_item ) {
                $product = $cart_item['data']; // WC_Product object
                $quantity = $cart_item['quantity'];

                // Make sure 'rx_reduction' meta exists
                $rx_reduction = floatval( $product->get_meta('RX Reduction') );

                $total += $rx_reduction * $quantity;
            }
        }
        
        $prescription_available_grams = $availble_to_order - $total;
        Ample_Session_Cache::set('current_on_cart', $total);
        $policy_available_grams = 0;

    } else {
        $policy_available_grams = 0;
        $prescription_available_grams = 0;
    }

    $prescription_available_grams = max(0, $prescription_available_grams);

    wp_send_json([
        'policy_grams' => $policy_available_grams,
        'prescription_grams' => $prescription_available_grams
        // 'used' => $used_grams,
    ]);
}






// Debugging
add_action('init', function() {
    add_rewrite_rule('^debug-endpoint/?$', 'index.php?debug_custom=1', 'top');
});

add_filter('query_vars', function($vars) {
    $vars[] = 'debug_custom';
    return $vars;
});


add_action('template_redirect', function() {
    if (get_query_var('debug_custom') == 1) {
        // Load WordPress environment
        get_header(); // Output theme header

        echo '<div style="background: #f0f0f0; padding: 10px; margin: 10px; border: 1px solid #ccc;">';
        echo '<h3>Ample Connect Session Debug</h3>';
        echo '<p><strong>Session Available:</strong> ' . (Ample_Session_Cache::is_session_available() ? 'Yes' : 'No') . '</p>';
        echo '<p><strong>User Logged In:</strong> ' . (is_user_logged_in() ? 'Yes' : 'No') . '</p>';
        
        if (is_user_logged_in()) {
            $user = wp_get_current_user();
            echo '<p><strong>User ID:</strong> ' . $user->ID . '</p>';
            echo '<p><strong>Client ID:</strong> ' . get_user_meta($user->ID, 'client_id', true) . '</p>';
        }
        
        $session_keys = [
            'session_initialized',
            'order_id',
            'purchasable_products',
            'purchasable_product_ids',
            'custom_shipping_rates',
            'custom_tax_data',
            'applicable_discounts',
            'policy_details',
            'order_items',
            'available_to_order',
            'credit_cards',
            'status',
            'applicable_policies',
            'applied_policy_id'
        ];
        
        echo '<h4>Session Data:</h4>';
        foreach ($session_keys as $key) {
            $value = Ample_Session_Cache::get($key);
            echo '<p><strong>' . $key . ':</strong> ';
            print_r($value);
            echo '</p>';
        }
        
        echo '</div>';

        get_footer(); // Output theme footer
        exit;
    }
});

// Clear session data on logout
add_action('wp_logout', 'ample_connect_clear_session_on_logout');
function ample_connect_clear_session_on_logout() {
    Ample_Session_Cache::clear_all();
}

// Clear session data after order completion
add_action('woocommerce_thankyou', 'ample_connect_clear_session_after_order');
function ample_connect_clear_session_after_order($order_id) {
    // Clear session data but keep order_id for potential reorder
    $order_id_backup = Ample_Session_Cache::get('order_id');
    Ample_Session_Cache::clear_all();
    if ($order_id_backup) {
        Ample_Session_Cache::set('last_completed_order_id', $order_id_backup);
    }
}

// Debug session data (only for administrators)
add_action('wp_footer', 'ample_connect_debug_session_data');
function ample_connect_debug_session_data() {
    if (!current_user_can('administrator')) {
        return;
    }
    
    if (isset($_GET['debug_session']) && $_GET['debug_session'] === '1') {
        echo '<div style="background: #f0f0f0; padding: 10px; margin: 10px; border: 1px solid #ccc;">';
        echo '<h3>Ample Connect Session Debug</h3>';
        echo '<p><strong>Session Available:</strong> ' . (Ample_Session_Cache::is_session_available() ? 'Yes' : 'No') . '</p>';
        echo '<p><strong>User Logged In:</strong> ' . (is_user_logged_in() ? 'Yes' : 'No') . '</p>';
        
        if (is_user_logged_in()) {
            $user = wp_get_current_user();
            echo '<p><strong>User ID:</strong> ' . $user->ID . '</p>';
            echo '<p><strong>Client ID:</strong> ' . get_user_meta($user->ID, 'client_id', true) . '</p>';
        }
        
        $session_keys = [
            'session_initialized',
            'order_id',
            'purchasable_products',
            'custom_shipping_rates',
            'custom_tax_data',
            'applicable_discounts',
            'policy_details',
            'order_items',
            'available_to_order',
            'credit_cards',
            'status'
        ];
        
        echo '<h4>Session Data:</h4>';
        foreach ($session_keys as $key) {
            $value = Ample_Session_Cache::get($key);
            echo '<p><strong>' . $key . ':</strong> ' . (is_array($value) ? 'Array(' . count($value) . ')' : var_export($value, true)) . '</p>';
        }
        
        echo '</div>';
    }
}


// Confirmation Receipt 
add_action('wp_ajax_view_order_document', 'view_order_document');
add_action('wp_ajax_nopriv_view_order_document', 'view_order_document');
function view_order_document() {
    if (!is_user_logged_in()) {
        wp_die('Not allowed', 'Error', ['response' => 403]);
    }

    // Require nonce from client side for CSRF protection
    // check_ajax_referer('view_order_doc_nonce', 'nonce');

    $woo_order_id = intval($_POST['order_id']);
    $doc_type = sanitize_text_field($_POST['doc_type']); 

    $user_id = get_current_user_id();
    $client_id = get_user_meta($user_id, 'client_id', true);

    if ($doc_type === 'registration_document') {
        $url = AMPLE_CONNECT_WOO_CLIENT_URL . $client_id . '/registration_document';
    } else {
        // Verify the logged-in user owns the WooCommerce order (or has capability)
        $order = wc_get_order($woo_order_id);
        if (!$order || ($order->get_user_id() !== get_current_user_id() && !current_user_can('manage_woocommerce'))) {
            wp_die('Not allowed', 'Error', ['response' => 403]);
        }

        // Map to external Ample order ID stored on the Woo order
        $external_order_number = get_post_meta($woo_order_id, '_external_order_number', true);
        if (empty($external_order_number)) {
            wp_die('External order not found', 'Error', ['response' => 400]);
        }

        // Decide API URL based on document type
        if ($doc_type === 'order-confirmation') {
            $url = AMPLE_CONNECT_PORTAL_URL . '/orders/' . $external_order_number . '/confirmation_receipt';
        } elseif ($doc_type === 'shipped-receipt') {
            $url = AMPLE_CONNECT_PORTAL_URL . '/orders/' . $external_order_number . '/shipping_receipt';
        } else {
            wp_die('Invalid document type', 'Error', ['response' => 400]);
        }
    }

    $api_url = add_query_arg(array('client_id' => $client_id), $url);

    $body = ample_request($api_url);

    if (is_array($body)) {
        // API returned an error or no document
        wp_send_json_success([
            'message' => 'No document available',
            'has_pdf' => false,
        ]);
    }

    // Clean output buffer
    if (ob_get_length()) {
        ob_end_clean();
    }

    nocache_headers();
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="order-' . intval($_POST['order_id']) . '.pdf"');
    header('Content-Length: ' . strlen($body));
    echo $body;

    // ✅ Use exit, not wp_die
    exit;
}


// Notify user about product
add_action('wp_ajax_add_to_notify_list', 'handle_add_to_notify_list');
add_action('wp_ajax_nopriv_add_to_notify_list', 'handle_add_to_notify_list');
function handle_add_to_notify_list() {

    // Check login
    if ( ! is_user_logged_in() ) {
        // Get WooCommerce My Account login page
        $login_url = wc_get_page_permalink( 'myaccount' );
        
        wp_send_json_error( [
            'message'    => 'You must be logged in to use this feature.',
            'redirect'   => $login_url,
        ] );
    }

    // $email = sanitize_email($_POST['email']);
    $product_id = intval($_POST['product_id']);

    $user_id = get_current_user_id();
    $client_id = get_user_meta($user_id, 'client_id', true);

    $url = AMPLE_CONNECT_CLIENTS_URL . '/notify_when_back_in_stock';
    $arg = array (
        "sku_id" => $product_id,
        "client_id" => $client_id
    );
    
    $response = ample_request($url, 'PUT', $arg);
    wp_send_json_success('You will be notified!');

    // if ( $was_successful ) {
    //     wp_send_json_success( 'You will be notified when the product is back in stock.' );
    // } else {
    //     wp_send_json_error( 'Unable to process your request. Please try again later.' );
    // }
}

// Simple AJAX handler that doesn't conflict with WooCommerce
add_action('wp_ajax_woocommerce_ajax_add_to_cart', 'handle_ajax_add_to_cart');
add_action('wp_ajax_nopriv_woocommerce_ajax_add_to_cart', 'handle_ajax_add_to_cart');

function handle_ajax_add_to_cart() {
    // Check if user is logged in
    if (!is_user_logged_in()) {
        wp_send_json_error(array(
            'message' => 'Please log in to add products to cart.',
            'login_required' => true,
            'login_url' => wc_get_page_permalink('myaccount')
        ));
        return;
    }
    
    $product_id = absint($_POST['product_id']);
    $quantity = absint($_POST['quantity']) ?: 1;
    $variation_id = absint($_POST['variation_id']) ?: 0;
    
    if (!$product_id) {
        wp_send_json_error('Invalid product');
        return;
    }
    
    ample_connect_log("AJAX Add to Cart - product_id: $product_id, quantity: $quantity, variation_id: $variation_id");
    
    $passed_validation = apply_filters('woocommerce_add_to_cart_validation', true, $product_id, $quantity, $variation_id, array());
    
    // Check for validation errors BEFORE attempting to add to cart
    $error_notices = wc_get_notices('error');
    if (!$passed_validation || !empty($error_notices)) {
        // Get the error message from notices
        $error_message = !empty($error_notices) ? strip_tags($error_notices[0]['notice']) : 'Failed to add product to cart';
        ample_connect_log("AJAX Add to Cart validation failed: " . $error_message);
        wc_clear_notices(); // Clear notices
        
        wp_send_json_error($error_message);
        return;
    }
    
    // Only proceed if validation passed and no error notices
    $cart_item_key = WC()->cart->add_to_cart($product_id, $quantity, $variation_id);
    if ($cart_item_key) {
        do_action('woocommerce_ajax_added_to_cart', $product_id);
        
        // Clear any WooCommerce notices to prevent them showing on reload
        wc_clear_notices();
        ample_connect_log("AJAX Add to Cart success - cleared all notices");
        
        // Get cart fragments like WooCommerce does
        $data = array(
            'message' => 'Product added to cart',
            'fragments' => apply_filters('woocommerce_add_to_cart_fragments', array()),
            'cart_hash' => WC()->cart->get_cart_hash()
        );
        
        wp_send_json_success($data);
    } else {
        // Check for any new error notices that might have been added during add_to_cart
        $error_notices = wc_get_notices('error');
        $error_message = !empty($error_notices) ? strip_tags($error_notices[0]['notice']) : 'Failed to add product to cart';
        wc_clear_notices(); // Clear notices
        
        wp_send_json_error($error_message);
    }
}

add_filter('woocommerce_get_item_data', 'show_manual_discount_note', 10, 2);
function show_manual_discount_note($item_data, $cart_item) {
    if (isset($cart_item['custom_discount_note'])) {
        $item_data[] = array(
            'name'  => __('Discount'),
            'value' => wc_clean($cart_item['custom_discount_note']),
        );
    }
    return $item_data;
}

// Handle reset-password
add_action( 'template_redirect', function() {
    if ( isset( $_POST['custom_reset_submit'], $_GET['key'], $_GET['login'] ) ) {
        $user = check_password_reset_key( sanitize_text_field( $_GET['key'] ), sanitize_text_field( $_GET['login'] ) );

        if ( is_wp_error( $user ) ) {
            wp_safe_redirect( add_query_arg( 'reset-error', 'invalid_link', wp_get_referer() ) );
            exit;
        }

        $client_id = get_user_meta($user->ID, "client_id", true);

        $status = Client_Information::fetch_patient_status($client_id);

        if (!$status) {
            wp_safe_redirect( add_query_arg( 'reset-error', 'no_user', wp_get_referer() ) );
            exit;
        }

        if ($status == "Pending Registration") {
            wp_safe_redirect( add_query_arg( 'reset-error', 'pending_reg', wp_get_referer() ) );
            exit;
        }

        $pass1 = sanitize_text_field( $_POST['pass1'] );
        $pass2 = sanitize_text_field( $_POST['pass2'] );

        if ( empty( $pass1 ) || $pass1 !== $pass2 ) {
            wp_safe_redirect( add_query_arg( 'reset-error', 'password_mismatch', wp_get_referer() ) );
            exit;
        }

        reset_password( $user, $_POST['pass1'] );

        // Redirect after success
        wp_safe_redirect( wc_get_page_permalink( 'myaccount' ) . '?password-reset=success' );
        exit;
    }
});


// checkout related code
// ---- 1) Server-side: copy billing -> shipping in user meta if empty (runs before checkout render)
add_action( 'woocommerce_before_checkout_form', function() {
    if ( ! is_user_logged_in() ) return;

    $user_id = get_current_user_id();
    $map = [
        'shipping_first_name' => 'billing_first_name',
        'shipping_last_name'  => 'billing_last_name',
        'shipping_phone'      => 'billing_phone',
        'shipping_address_1'  => 'billing_address_1',
    ];

    foreach ( $map as $ship_key => $bill_key ) {
        $ship_val = get_user_meta( $user_id, $ship_key, true );
        if ( empty( $ship_val ) ) {
            $bill_val = get_user_meta( $user_id, $bill_key, true );
            if ( ! empty( $bill_val ) ) {
                update_user_meta( $user_id, $ship_key, $bill_val );
            }
        }
    }
}, 5 );

// ---- 2) Fallback: ensure checkout field values come from user meta if present
add_filter( 'woocommerce_checkout_get_value', function( $value, $input ) {
    if ( is_user_logged_in() ) {
        $user_id = get_current_user_id();
        $saved = get_user_meta( $user_id, $input, true );
        if ( ! empty( $saved ) ) {
            return $saved;
        }
    }
    return $value;
}, 10, 2 );

// ---- 3) Client-side: enqueue inline JS that copies billing inputs to shipping inputs and
// re-runs after checkout updates (robust for Fluid Checkout)
add_action( 'wp_enqueue_scripts', function() {
    if ( ! is_checkout() || is_order_received_page() ) return;

    wp_register_script( 'fc-copy-billing', false, ['jquery'], '1.0', true );
    wp_enqueue_script( 'fc-copy-billing' );

    $js = <<<'JS'
(function($){
  function copyBillingToShipping() {
    // mappings: billing -> shipping
    var maps = [
      {b: '[name="billing_first_name"]', s: '[name="shipping_first_name"]'},
      {b: '[name="billing_last_name"]',  s: '[name="shipping_last_name"]'},
      {b: '[name="billing_phone"]',      s: '[name="shipping_phone"]'},
      {b: '[name="billing_first_name"]',  s: '[name="shipping_address_1"]'}
    ];

    maps.forEach(function(m){
      var billingVal = $(m.b).val();
      var $s = $(m.s);
      if ( billingVal && $s.length && !$s.val() ) {
        $s.val(billingVal).trigger('change');
      }
    });
  }

  $(function(){
    // Run once on load
    copyBillingToShipping();

    // Re-run when WooCommerce triggers an updated_checkout event
    $(document.body).on('updated_checkout', copyBillingToShipping);

    // Capture delegated changes on billing fields (works with Fluid Checkout DOM replacement)
    $(document.body).on('change input', '[name^="billing_"], [name="account_first_name"], [name^="form-field-"]', function(){
      // small delay to allow other scripts to run first (autocomplete etc.)
      setTimeout(copyBillingToShipping, 30);
    });

    // MutationObserver fallback: when the checkout form area changes, try again
    var target = document.querySelector('form.checkout') || document.querySelector('#checkout-app') || document.body;
    if ( target && window.MutationObserver ) {
      var obs = new MutationObserver(function() { copyBillingToShipping(); });
      obs.observe(target, { childList: true, subtree: true });
    }
  });
})(jQuery);
JS;

    wp_add_inline_script( 'fc-copy-billing', $js );
});

// Auto-fill checkout fields with user data
function autofill_checkout_fields($fields) {
    if (!is_user_logged_in()) {
        return $fields;
    }
    
    $current_user = wp_get_current_user();
    $user_id = $current_user->ID;
    
    // Get user meta data
    $phone_number = get_user_meta($user_id, 'billing_phone', true);
    $date_of_birth = get_user_meta($user_id, 'date_of_birth', true);
    
    // Get most recent order address
    $recent_address = get_most_recent_order_address($user_id);
    
    // Auto-fill billing fields
    if (empty($fields['billing']['billing_first_name']['default'])) {
        $fields['billing']['billing_first_name']['default'] = $current_user->first_name;
    }
    if (empty($fields['billing']['billing_last_name']['default'])) {
        $fields['billing']['billing_last_name']['default'] = $current_user->last_name;
    }
    if (empty($fields['billing']['billing_email']['default'])) {
        $fields['billing']['billing_email']['default'] = $current_user->user_email;
    }
    if (empty($fields['billing']['billing_phone']['default']) && $phone_number) {
        $fields['billing']['billing_phone']['default'] = $phone_number;
    }
    
    // Auto-fill shipping fields with recent order data
    if ($recent_address) {
        if (empty($fields['shipping']['shipping_first_name']['default'])) {
            $fields['shipping']['shipping_first_name']['default'] = $recent_address['first_name'] ?: $current_user->first_name;
        }
        if (empty($fields['shipping']['shipping_last_name']['default'])) {
            $fields['shipping']['shipping_last_name']['default'] = $recent_address['last_name'] ?: $current_user->last_name;
        }
        if (empty($fields['shipping']['shipping_address_1']['default'])) {
            $fields['shipping']['shipping_address_1']['default'] = $recent_address['address_1'];
        }
        if (empty($fields['shipping']['shipping_address_2']['default'])) {
            $fields['shipping']['shipping_address_2']['default'] = $recent_address['address_2'];
        }
        if (empty($fields['shipping']['shipping_city']['default'])) {
            $fields['shipping']['shipping_city']['default'] = $recent_address['city'];
        }
        if (empty($fields['shipping']['shipping_postcode']['default'])) {
            $fields['shipping']['shipping_postcode']['default'] = $recent_address['postcode'];
        }
        if (empty($fields['shipping']['shipping_state']['default'])) {
            $fields['shipping']['shipping_state']['default'] = $recent_address['state'];
        }
        if (empty($fields['shipping']['shipping_country']['default'])) {
            $fields['shipping']['shipping_country']['default'] = $recent_address['country'];
        }
    }
    
    return $fields;
}
add_filter('woocommerce_checkout_fields', 'autofill_checkout_fields');

// Get user address data for AJAX requests
function get_user_address_data_for_checkout() {
    if (!is_user_logged_in()) {
        wp_send_json_error('User not logged in');
        return;
    }
    
    $current_user = wp_get_current_user();
    $user_id = $current_user->ID;
    
    // Get user meta data
    $phone_number = get_user_meta($user_id, 'billing_phone', true);
    $recent_address = get_most_recent_order_address($user_id);
    
    $data = array(
        'billing_first_name' => $current_user->first_name,
        'billing_last_name' => $current_user->last_name,
        'billing_email' => $current_user->user_email,
        'billing_phone' => $phone_number,
    );
    
    // Add shipping data from recent order
    if ($recent_address) {
        $data = array_merge($data, array(
            'shipping_first_name' => $recent_address['first_name'] ?: $current_user->first_name,
            'shipping_last_name' => $recent_address['last_name'] ?: $current_user->last_name,
            'shipping_address_1' => $recent_address['address_1'],
            'shipping_address_2' => $recent_address['address_2'],
            'shipping_city' => $recent_address['city'],
            'shipping_postcode' => $recent_address['postcode'],
            'shipping_state' => $recent_address['state'],
            'shipping_country' => $recent_address['country'],
        ));
    }
    
    wp_send_json_success($data);
}
add_action('wp_ajax_get_user_address_data_for_checkout', 'get_user_address_data_for_checkout');
add_action('wp_ajax_nopriv_get_user_address_data_for_checkout', 'get_user_address_data_for_checkout');

// Helper function to get most recent order address
function get_most_recent_order_address($user_id) {
    $orders = wc_get_orders(array(
        'customer_id' => $user_id,
        'limit' => 1,
        'status' => array('wc-completed', 'wc-processing', 'wc-on-hold'),
        'orderby' => 'date',
        'order' => 'DESC'
    ));
    
    if (empty($orders)) {
        return null;
    }
    
    $order = $orders[0];
    
    return array(
        'first_name' => $order->get_shipping_first_name(),
        'last_name' => $order->get_shipping_last_name(),
        'address_1' => $order->get_shipping_address_1(),
        'address_2' => $order->get_shipping_address_2(),
        'city' => $order->get_shipping_city(),
        'postcode' => $order->get_shipping_postcode(),
        'state' => $order->get_shipping_state(),
        'country' => $order->get_shipping_country(),
    );
}
