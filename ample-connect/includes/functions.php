<?php
if (!defined('ABSPATH')) {
    exit;
}
require_once plugin_dir_path(__FILE__) . '/customer-functions.php';

// loading custom css
wp_enqueue_style('ample-connect-styles2', plugin_dir_url(__FILE__) . '../assets/css/ample-connect.css');

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
    $update_url = "https://medbox.sandbox.onample.com/api/v2/clients/$client_id/registrations/$active_registration_id";
    $update_data = ample_request($update_url, 'PUT', $data);

    if ($update_data['status'] != 'success') {
        error_log('Update failed: ' . $update_data['message']);
    }

}
add_action('admin_post_sync_products', 'handle_manual_product_sync');

function handle_manual_product_sync()
{
    // Ensure the WooCommerce client is initialized
    WC_Product_Sync::init();
    $instance = WC_Product_Sync::get_instance();
    $instance->sync_products();
}

function load_ample_connect_settings()
{
    global $ample_connect_settings;
    $ample_connect_settings = get_option('ample_connect_settings');
}
add_action('plugins_loaded', 'load_ample_connect_settings');



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
    
    $credit_cards = Client_Information::get_credit_cards();
    $user_id = get_current_user_id();

    // Get the client id of the customer
    $client_id = get_user_meta($user_id, "client_id", true);
    echo '<h3>Your Saved Cards</h3>';
    if (empty($credit_cards)) {
        echo '<p>No saved cards found.</p>';
        return;
    }

    echo '<table>';
    echo '<thead><tr><th>Card Type</th><th>Card Number</th><th>Expiry</th><th>Action</th></tr></thead>';
    echo '<tbody>';

    foreach ($credit_cards as $credit_card) {
        echo '<tr>';
        echo '<td>' . esc_html($credit_card['brand']) . '</td>';
        echo '<td>' . esc_html($credit_card['protected_card_number']) . '</td>';
        echo '<td>' . esc_html($credit_card['expiry']) . '</td>';
        echo '<td>';
        echo '<form method="POST" action="">';
        echo '<input type="hidden" name="delete_token" value="' . esc_attr($credit_card['id']) . '">';
        echo '<button type="submit">Delete</button>';
        echo '</form>';
        echo '</td>';
        echo '</tr>';
    }

    echo '</tbody>';
    echo '</table>';
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
    my_debug_log("shipping method response = ");
    my_debug_log($shipping_method);
    $user_id = get_current_user_id();
    // Get the client id of the customer
    $client_id = get_user_meta($user_id, 'client_id', true);

    $order = get_order_id_from_api();
    $order_id = $order['id'];

    $body = array(
        'shipping_rate_id' => $shipping_method,
        'client_id' => $client_id
    );

    $api_url = AMPLE_CONNECT_PORTAL_URL . "/orders/{$order_id}/set_shipping_rate"; 
    $body_data = ample_request($api_url, 'PUT', $body);
    if ($body_data) {
        wp_send_json_success($body_data);
        
    } else {
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

    // Do something with the data
    $presc_data = get_prescription_details($client_id);

    // Send the response back
    wp_send_json_success($presc_data);
}
// Hook for logged-in users
add_action('wp_ajax_get_prescription_data', 'get_prescription_data');
// Hook for non-logged-in users
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
        $external_order_number = get_post_meta($wc_order_id, '_external_order_number', true);
        if ($external_order_number) {
            $order_map[$external_order_number] = $wc_order_id;
        }
    }

    // Display the orders in WooCommerce's format
    echo '<table class="shop_table shop_table_responsive my_account_orders">';
    echo '<thead><tr>';
    echo '<th>' . __('Order ID', 'woocommerce') . '</th>';
    echo '<th>' . __('Date', 'woocommerce') . '</th>';
    echo '<th>' . __('Status', 'woocommerce') . '</th>';
    echo '<th>' . __('Total', 'woocommerce') . '</th>';
    echo '<th>' . __('Actions', 'woocommerce') . '</th>';
    echo '</tr></thead>';
    echo '<tbody>';

    foreach ($external_orders as $ext_order) {
        $wc_order_id = $order_map[$ext_order['id']] ?? 'N/A';
        $order_url = $wc_order_id !== 'N/A' ? wc_get_endpoint_url('view-order', $wc_order_id) : '#';

        echo '<tr>';
        echo '<td><a class="order_number" href="' . esc_url($order_url) . '">#' . esc_html($wc_order_id) . '</a></td>';
        echo '<td>' . esc_html(date('F j, Y', strtotime($ext_order['created_at']))) . '</td>';
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

add_action( 'wp_ajax_fetch_and_store_product_data', 'save_api_products_to_temp_file' );
    function save_api_products_to_temp_file() {
        // $api_url = 'https://abbamedix.onample.com/api/v3/products/public_listing';
        $api_url = AMPLE_CONNECT_API_BASE_URL . '/v3/products/public_listing';
        $products = ample_request($api_url);

        $upload_dir = wp_upload_dir();
        $file_path  = trailingslashit( $upload_dir['basedir'] ) . 'temp_products.json';

        if ( file_put_contents( $file_path, json_encode( $products ) ) === false ) {
            wp_send_json_error( 'Failed to write file' );
        }

        wp_send_json_success( 'Product data saved to file' );

        // $woo_client = new WC_Products();
        // $woo_client->clean_product_categories();
        // wp_send_json_success( 'Products and categories cleared!' );
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
            return 'Done. File deleted.';
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

        return 'Processed batch of ' . count( $batch ) . ' and ' .
        
        
        count($all_products) . ' products remaining!';
    }

    add_action( 'wp_ajax_run_product_batch_processing', 'handle_ajax_product_batch' );
    function handle_ajax_product_batch() {
        $result = process_product_batch_from_file( 50 ); // or whatever batch size
        wp_send_json_success( $result );
    }