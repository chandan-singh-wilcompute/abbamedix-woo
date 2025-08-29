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
            <button id="product_fetch_and_sync" class="button-primary">Sync Products</button>

            <br/> <br/>

            <button id="delete-all-products" class="button button-danger">Delete All Products & Categories</button>
            <div id="delete-result" style="margin-top:10px;"></div>
        </div>
        <?php
    }


    public function sync_products() {
        // Step 1: Fetch products and save to file
        $this->fetch_and_store_products_for_cron();

        // Step 2: Process in batches until done
        $batch_size = 50;
        while (true) {
            $result = process_product_batch_from_file($batch_size);
            if (strpos($result, 'remaining!') === false) {
                break; // no more products
            }
        }
    }

    private function fetch_and_store_products_for_cron() {
        $api_url = AMPLE_CONNECT_API_BASE_URL . '/v3/products/public_listing';
        $products = ample_request($api_url);

        $upload_dir = wp_upload_dir();
        $file_path  = trailingslashit($upload_dir['basedir']) . 'temp_products.json';

        file_put_contents($file_path, json_encode($products));
    }

}

// WC_Product_Sync::init();
