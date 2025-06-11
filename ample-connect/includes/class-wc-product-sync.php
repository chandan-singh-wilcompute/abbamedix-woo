<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

require_once plugin_dir_path(__FILE__) . '/wc_classes/class-wc-products.php';

class WC_Product_Sync {
    private static $instance = null;

    public static function init() {
        if (null == self::$instance) {
            self::$instance = new self();
        }
        // add_action('init', [self::$instance, 'register_custom_schedule']);
        add_action('wp_loaded', [self::$instance, 'register_custom_schedule']);
    }

    public static function get_instance() {
        return self::$instance;
    }

    public function __construct() {
        // Schedule the event
        add_action('ample_connect_product_sync_event', [$this, 'sync_products']);
    }

    public function register_custom_schedule() {
        global $ample_connect_settings;

        // Check if product sync is enabled
        if (isset($ample_connect_settings['product_sync_enabled']) && !$ample_connect_settings['product_sync_enabled']) {
            $timestamp = wp_next_scheduled('ample_connect_product_sync_event');
            if ($timestamp) {
                wp_unschedule_event($timestamp, 'ample_connect_product_sync_event');
            }
            return;
        }

        $sync_time = isset($ample_connect_settings['product_sync_time']) ? (int)$ample_connect_settings['product_sync_time'] : 60;
        // Add custom cron schedule
        add_filter('cron_schedules', function($schedules) use ($sync_time) {
            $schedules['ample_connect_sync_interval'] = [
                'interval' => $sync_time * 60,
                'display' => __('Every ' . $sync_time . ' Minutes')
            ];
            return $schedules;
        });

        // Schedule the event if not already scheduled
        if (!wp_next_scheduled('ample_connect_product_sync_event')) {
            wp_schedule_event(time(), 'ample_connect_sync_interval', 'ample_connect_product_sync_event');
        }
    }

    public function admin_page() {
        ?>
        <div class="wrap">
            <h1>Ample Product Sync</h1>
            
            <!-- <?php if (isset($_GET['sync_success']) && $_GET['sync_success'] === 'true') : ?>
                <div class="notice notice-success is-dismissible">
                    <p>Products successfully synced!</p>
                </div>
            <?php endif; ?>

            <form action="<?php echo admin_url('admin-post.php'); ?>" method="post">
                <input type="hidden" name="action" value="sync_products">
                <?php submit_button('Sync Products'); ?>
            </form> -->
            <button id="product_fetch_and_sync" class="button-
            
            
            primary">Sync Products</button>
        </div>
        <?php
    }


    public function sync_products() {
        // Ensure the WooCommerce client is initialized
        // $api_url = AMPLE_CONNECT_API_BASE_URL . '/v3/products/public_listing';
        $api_url = 'https://abbamedix.onample.com/api/v3/products/public_listing';
        // $this->save_api_products_to_temp_file($api_url);


        $woo_client = new WC_Products();
        $woo_client->clean_product_categories();
        foreach ($products as $product) {   
            $result = $woo_client->add_custom_variable_product($product); 
        }

        wp_redirect(add_query_arg('sync_success', 'true', admin_url('admin.php?page=ample-product-sync')));
        exit;
    }
}

// WC_Product_Sync::init();
