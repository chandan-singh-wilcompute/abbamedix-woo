<?php
if (!defined('ABSPATH')) {
    exit;
}

require_once plugin_dir_path(__FILE__) . 'class-wc-product-sync.php';
// require_once plugin_dir_path(__FILE__) . 'class-ample-connect-api.php';
class Ample_Connect_Admin {

    private static $settings_cache = null;

    public static function get_settings() {
        if (self::$settings_cache === null) {
            self::$settings_cache = array(
                'product_sync_enabled'       => get_option('ample_connect_product_sync_enabled', true),
                'client_profile_update_enabled' => get_option('ample_connect_client_profile_update_enabled', true),
                'consumer_key'               => get_option('ample_connect_consumer_key', ''),
                'consumer_secret'            => get_option('ample_connect_consumer_secret', ''),
                'account_not_approved_message' => get_option('account_not_approved_message', ''),
                'product_sync_time'          => get_option('product_sync_time', ''),
                'ample_base_url'             => get_option('ample_base_url', ''),
                'ample_admin_username'       => get_option('ample_admin_username', ''),
                'ample_admin_password'       => get_option('ample_admin_password', ''),
                'webhook_secret'             => get_option('ample_connect_webhook_secret', ''),
            );
        }
        return self::$settings_cache;
    }

    public function __construct() {
        // Hook into the admin_init action to register settings
        add_action('admin_init', array($this, 'register_settings'));

        // Initialize global settings
        add_action('init', array($this, 'initialize_global_settings'));
    }
    
    public function enqueue_styles() {
        wp_enqueue_style('ample-connect-styles', plugin_dir_url(__FILE__) . '../assets/css/ample-connect.css');
    }

    public function enqueue_scripts() {
        wp_enqueue_script('jquery');
        wp_enqueue_script('prescription-details-script', plugin_dir_url(__FILE__) . '../assets/js/prescription-details.js', array('jquery'), null, true);
        wp_enqueue_script('client-edit-script', plugin_dir_url(__FILE__) . '../assets/js/client-edit.js', array('jquery'), null, true);

        wp_localize_script('prescription-details-script', 'ampleConnectConfig', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce'    => wp_create_nonce('ample_nonce_data')
        ));
        wp_localize_script('client-edit-script', 'ampleConnectConfig', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce'    => wp_create_nonce('ample_nonce_data')
        ));

        wp_enqueue_script('ample-connect-settings-script', plugin_dir_url(__FILE__) . '../assets/js/ample-connect-settings.js', array('jquery'), null, true);
    }

    public function add_admin_menu() {
        $icon_url = plugin_dir_url(__FILE__) . '../images/icon.png';
        add_menu_page(
            'Ample Connect',
            'Ample Connect',
            'manage_options',
            'ample-connect-plugin',
            array($this, 'display_main_page'),
            $icon_url
        );
        add_submenu_page('ample-connect-plugin', 'Clients', 'Clients', 'manage_options', 'ample-connect-clients', array($this, 'display_clients_page'));
        add_submenu_page('ample-connect-plugin', 'Settings', 'Settings', 'manage_options', 'ample-connect-settings', array($this, 'display_settings_page'));

        global $ample_connect_settings;
        if ($ample_connect_settings['product_sync_enabled']) {
            $wc_product_sync = new WC_Product_Sync();
            add_submenu_page('ample-connect-plugin', 'Product Sync', 'Product Sync', 'manage_options', 'ample-product-sync', array($wc_product_sync, 'admin_page'));
        }
    }


    public function register_settings() {

        register_setting('ample_connect_settings', 'ample_connect_product_sync_enabled');
        register_setting('ample_connect_settings', 'ample_connect_client_profile_update_enabled');
        register_setting('ample_connect_settings', 'ample_connect_consumer_key');
        register_setting('ample_connect_settings', 'ample_connect_consumer_secret');

        register_setting('ample_connect_settings', 'account_not_approved_message');
        register_setting('ample_connect_settings', 'product_sync_time');
        register_setting('ample_connect_settings', 'ample_base_url');
        register_setting('ample_connect_settings', 'ample_admin_username');
        register_setting('ample_connect_settings', 'ample_admin_password');

        register_setting('ample_connect_settings', 'ample_connect_webhook_secret');

        add_settings_section('ample_connect_main_section', 'Main Settings', null, 'ample-connect-settings');

        add_settings_field(
            'ample_connect_product_sync_enabled',
            'Enable Product Sync', 
            [$this, 'product_sync_field_callback'],
            'ample-connect-settings',
            'ample_connect_main_section'
        );

        add_settings_field(
            'product_sync_time',
            'Product Sync Time (Minutes)',
            [$this, 'product_sync_time_field_callback'],
            'ample-connect-settings',
            'ample_connect_main_section'
        );

        add_settings_field(
            'ample_connect_client_profile_update_enabled',
            'Enable Client Profile Update to Ample',
            [$this, 'client_profile_update_field_callback'],
            'ample-connect-settings',
            'ample_connect_main_section'
        );

        add_settings_field(
            'ample_connect_consumer_key',
            'Consumer Key (WooCommerce)',
            [$this, 'consumer_key_field_callback'],
            'ample-connect-settings',
            'ample_connect_main_section'
        );

        add_settings_field(
            'ample_connect_consumer_secret',
            'Consumer Secret (WooCommerce)',
            [$this, 'consumer_secret_field_callback'],
            'ample-connect-settings',
            'ample_connect_main_section'
        );
        add_settings_field(
            'account_not_approved_message',
            'Account not approved message',
            [$this, 'account_not_approved_message_field_callback'],
            'ample-connect-settings',
            'ample_connect_main_section'
        );

        add_settings_field(
            'ample_base_url',
            'Ample Base URL',
            [$this, 'ample_base_url_field_callback'],
            'ample-connect-settings',
            'ample_connect_main_section'
        );

        add_settings_field(
            'ample_admin_username',
            'Ample Admin Username',
            [$this, 'ample_admin_username_field_callback'],
            'ample-connect-settings',
            'ample_connect_main_section'
        );
        add_settings_field(
            'ample_admin_password',
            'Ample Admin Password',
            [$this, 'ample_admin_password_field_callback'],
            'ample-connect-settings',
            'ample_connect_main_section'
        );

        add_settings_field(
            'ample_connect_webhook_secret',
            'Ample Care Webhook Secret',
            [$this, 'webhook_secret_field_callback'],
            'ample-connect-settings',
            'ample_connect_main_section'
        );
       
    }

    public function product_sync_field_callback() {
        global $ample_connect_settings;
        $option = $ample_connect_settings['product_sync_enabled'];
        echo '<input type="checkbox" name="ample_connect_product_sync_enabled" id="ample_connect_product_sync_enabled" value="1" ' . checked(1, $option, false) . '>';
    }

    public function product_sync_time_field_callback() {
        global $ample_connect_settings;
        $option = $ample_connect_settings['product_sync_time'];
        echo '<div id="product_sync_time_field">';
        echo '<input type="number" min="1" step="1" name="product_sync_time" value="' . esc_attr($option) . '" class="regular-text">';
        echo '</div>';
    }

    public function client_profile_update_field_callback() {
        global $ample_connect_settings;
        $option = $ample_connect_settings['client_profile_update_enabled'];
        echo '<input type="checkbox" name="ample_connect_client_profile_update_enabled" value="1" ' . checked(1, $option, false) . '>';
    }

    public function consumer_key_field_callback() {
        global $ample_connect_settings;
        $option = $ample_connect_settings['consumer_key'];
        echo '<input type="text" name="ample_connect_consumer_key" value="' . esc_attr($option) . '" class="regular-text">';
    }

    public function consumer_secret_field_callback() {
        global $ample_connect_settings;
        $option = $ample_connect_settings['consumer_secret'];
        echo '<input type="text" name="ample_connect_consumer_secret" value="' . esc_attr($option) . '" class="regular-text">';
    }

    public function account_not_approved_message_field_callback() {
        global $ample_connect_settings;
        $option = $ample_connect_settings['account_not_approved_message'];
        echo '<input type="text" name="account_not_approved_message" value="' . esc_attr($option) . '" class="regular-text">';
    }

    public function ample_base_url_field_callback() {
        global $ample_connect_settings;
        $option = $ample_connect_settings['ample_base_url'];
        echo '<input type="text" name="ample_base_url" value="' . esc_attr($option) . '" class="regular-text">';
    }

    public function ample_admin_username_field_callback() {
        global $ample_connect_settings;
        $option = $ample_connect_settings['ample_admin_username'];
        echo '<input type="text" name="ample_admin_username" value="' . esc_attr($option) . '" class="regular-text">';
    }
    public function ample_admin_password_field_callback() {
        global $ample_connect_settings;
        $option = $ample_connect_settings['ample_admin_password'];
        echo '<input type="password" name="ample_admin_password" value="' . esc_attr($option) . '" class="regular-text">';
    }
 
    public function webhook_secret_field_callback() {
        global $ample_connect_settings;
        $option = $ample_connect_settings['webhook_secret'];
        echo '<input type="text" name="ample_connect_webhook_secret" value="' . esc_attr($option) . '" class="regular-text">';
    }

    public function display_main_page() {
        $client_page_url = admin_url('admin.php?page=ample-connect-clients');
        $product_sync_page_url = admin_url('admin.php?page=ample-product-sync');
        $settings_page_url = admin_url('admin.php?page=ample-connect-settings');
        $product_sync_enabled = get_option('ample_connect_product_sync_enabled', true);

        echo '<div class="wrap">
                <h1>Ample Connect</h1>
                <h2><center>Welcome to Ample Connect...</center></h2>
                <center>';

                echo '<a href="' . esc_url($settings_page_url) . '" class="button button-primary">Go to Settings</a>';
        if ($product_sync_enabled) {
            echo '<a href="' . esc_url($product_sync_page_url) . '" class="button button-primary" style="margin-left: 10px;">Go to Product Sync</a>'; 
        }

        echo '<a href="' . esc_url($client_page_url) . '" class="button button-primary" style="margin-left: 10px;">Go to Client Listings</a>';
        echo '</center>
              </div>';
    }

    public function display_settings_page() {
        ?>
        <div class="wrap">
            <h1>Ample Connect Settings</h1>
            <form method="post" action="options.php">
                <?php settings_fields('ample_connect_settings'); ?>
                <?php do_settings_sections('ample-connect-settings'); ?>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }
    
    public function display_clients_page() {
        global $ample_connect_settings;
        $client_profile_update_enabled = $ample_connect_settings['client_profile_update_enabled'];
        $page = isset($_GET['apage']) ? intval($_GET['apage']) : 1;

        $result = $this->fetch_client_listings($page);

        if (is_wp_error($result)) {
            echo '<div class="error"><p>' . esc_html($result->get_error_message()) . '</p></div>';
            return;
        }

        $clients = isset($result['clients_data']) ? $result['clients_data'] : [];

        // Display the client listings
        echo '<div class="wrap">';
        echo '<h1>Client Listings</h1>';
        echo '<table class="widefat fixed striped" cellspacing="0">
                <thead>
                    <tr>
                        <th class="manage-column column-columnname" scope="col">Client ID</th>
                        <th class="manage-column column-columnname" scope="col">First Name</th>
                        <th class="manage-column column-columnname" scope="col">Last Name</th>
                        <th class="manage-column column-columnname" scope="col">Email</th>
                        <th class="manage-column column-columnname" scope="col">Status</th>
                        <th class="manage-column column-columnname" scope="col">DOB</th>
                        <th class="manage-column column-columnname" scope="col">Days in Status</th>
                        <th class="manage-column column-columnname" scope="col">Phone Number</th>
                        <th class="manage-column column-columnname" scope="col">Actions</th>
                    </tr>
                </thead>
                <tbody>';

        foreach ($clients[1] as $client) {
            if (isset($client['registration'])) {
                $registration = $client['registration'];
                echo '<tr>
                    <td>' . esc_html($client['client_id']) . '</td>
                    <td>' . esc_html($registration['first_name']) . '</td>
                    <td>' . esc_html($registration['last_name']) . '</td>
                    <td>' . esc_html($registration['email']) . '</td>
                    <td>' . esc_html($registration['status']) . '</td>
                    <td>' . esc_html($registration['date_of_birth']) . '</td>
                    <td>' . esc_html($registration['days_in_status']) . ' ' . ($registration['days_in_status'] == 1 ? 'Day' : 'Days') . '</td>
                    <td>' . esc_html($registration['telephone_1']) . '</td>
                    <td>';
                    echo '<a title="Prescription Details" href="#" class="prescription-details-link" data-client-id="' . esc_attr($client['id']) . '" data-client_name="' . esc_attr($registration['first_name'] . ' ' . $registration['last_name']) . '"><i class="eicon-preview-medium" style="font-size:24px"></i></a>';

                    // Check if ample_connect_client_profile_update_enabled is true for Client Edit link
                    if (isset($client_profile_update_enabled) && $client_profile_update_enabled) {
                        echo '&nbsp;&nbsp;<a title="Client Edit" href="#" class="edit-client-link" data-client-id="' . esc_attr($client['id']) . '" data-registration_id="' . esc_attr($client['active_registration_id']) . '"><i class="eicon-edit" style="font-size:22px"></i></a>';
                    }

                echo '</td></tr>';
            }
        }

        echo '</tbody></table>';

        // Pagination
        $this->display_pagination($clients[0]['total_entries'], $page);

        echo '</div>';

        // Add modals for prescription details and client edit
        $this->add_modals();
    }

    private function fetch_client_listings($page = 1) {

        $clients_url = add_query_arg(array(
            'page' => $page,
            'per_page' => 10,
            'sort_by' => 'first_name',
            'order_by' => 'asc',
            'archived' => 'false'
        ), AMPLE_CONNECT_CLIENTS_URL);

        $clients_data = ample_request($clients_url);
        
        if (!isset($clients_data)) {
            return new WP_Error('client_listing_failed', 'Sorry, Something went wrong please try again later');
        }

        return array(
            'clients_data' => $clients_data
        );
    }

    private function display_pagination($total_entries, $current_page) {
        $total_pages = ceil($total_entries / 10);
        if ($total_pages > 1) {
            echo '<div class="tablenav"><div class="tablenav-pages">';
            $current_url = admin_url('admin.php?page=ample-connect-clients');
            echo '<span class="displaying-num">' . sprintf(_n('1 item', '%s items', $total_entries), number_format_i18n($total_entries)) . '</span>';
            echo '<span class="pagination-links">';
            if ($current_page > 1) {
                printf('<a class="prev-page" href="%s">%s</a>', esc_url(add_query_arg('apage', $current_page - 1, $current_url)), '&laquo; Previous');
            }
            for ($i = 1; $i <= $total_pages; $i++) {
                if ($i === $current_page) {
                    echo '<span class="page-numbers current">' . $i . '</span>';
                } else {
                    printf('<a class="page-numbers" href="%s">%s</a>', esc_url(add_query_arg('apage', $i, $current_url)), $i);
                }
            }
            if ($current_page < $total_pages) {
                printf('<a class="next-page" href="%s">%s</a>', esc_url(add_query_arg('apage', $current_page + 1, $current_url)), 'Next &raquo;');
            }
            echo '</span>';
            echo '</div></div>';
        }
    }

    private function add_modals() {
        // Add modal for prescription details
        echo '<div id="ampleModal" class="ample-modal">
                <div class="ample-modal-content width-30-p">
                    <span class="ample-modal-close">&times;</span>
                    <div class="ample-modal-details">
                        <p>Loading...</p>
                    </div>
                </div>
            </div>';

        // Add modal for client edit
        echo '<div id="clientEditModal" class="ample-modal">
            <div class="ample-modal-content width-30-p">
                <span class="ample-modal-close">&times;</span>
                <div class="client-edit-modal-form">
                    <p>Loading...</p>
                </div>
            </div>
        </div>';
    }

    public function initialize_global_settings() {
        global $ample_connect_settings;
        $ample_connect_settings = array(
            'product_sync_enabled' => get_option('ample_connect_product_sync_enabled', true),
            'client_profile_update_enabled' => get_option('ample_connect_client_profile_update_enabled', true),
            'consumer_key' => get_option('ample_connect_consumer_key', ''),
            'consumer_secret' => get_option('ample_connect_consumer_secret', ''),
            'account_not_approved_message' => get_option('account_not_approved_message', ''),
            'product_sync_time' => get_option('product_sync_time', ''),
            'ample_base_url' => get_option('ample_base_url', ''),
            'ample_admin_username' => get_option('ample_admin_username', ''),
            'ample_admin_password' => get_option('ample_admin_password', ''),
            'webhook_secret' => get_option('ample_connect_webhook_secret', ''),
        );
    }
}