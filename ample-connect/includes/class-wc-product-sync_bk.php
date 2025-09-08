<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

require_once plugin_dir_path(__FILE__) . '../vendor/autoload.php';
use Automattic\WooCommerce\Client;
use Automattic\WooCommerce\HttpClient\HttpClientException;

class WC_Product_Sync {
    private static $instance = null;
    private $woocommerce;

    public static function init() {
        if (null == self::$instance) {
            self::$instance = new self();
        }

        add_action('admin_init', [self::$instance, 'initialize_plugin']);
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

    public function initialize_plugin() {
        global $ample_connect_settings;
        
        if (isset($ample_connect_settings['consumer_key']) && isset($ample_connect_settings['consumer_secret'])) {
            $consumer_key = $ample_connect_settings['consumer_key'];
            $consumer_secret = $ample_connect_settings['consumer_secret'];
            // Initialize WooCommerce Client with dynamic keys
            $this->woocommerce = new Client(
                site_url(),
                $consumer_key,
                $consumer_secret,
                [
                    'wp_api' => true,
                    'version' => 'wc/v3',
                    'timeout' => 30,
                    'verify_ssl' => false,
                ]
            );
        } else {
            echo 'Error: $ample_connect_settings not properly initialized.';
        }
    }

    public function admin_page() {
        ?>
        <div class="wrap">
            <h1>Ample Product Sync</h1>
            
            <?php if (isset($_GET['sync_success']) && $_GET['sync_success'] === 'true') : ?>
                <div class="notice notice-success is-dismissible">
                    <p>Products successfully synced!</p>
                </div>
            <?php endif; ?>

            <form action="<?php echo admin_url('admin-post.php'); ?>" method="post">
                <input type="hidden" name="action" value="sync_products">
                <?php submit_button('Sync Products'); ?>
            </form>
        </div>
        <?php
    }

    public function sync_products() {
        // Ensure the WooCommerce client is initialized
        // $this->initialize_plugin();

        $api_url = 'https://medbox.sandbox.onample.com/api/v3/products/public_listing?token=1z4qSJ7RVuO6Pxc9Mmlu_g';
        $response = wp_remote_get($api_url, ['timeout' => 300]);

        if (is_wp_error($response)) {
            error_log('Error fetching data from API: ' . $response->get_error_message());
            return;
        }

        $products = json_decode(wp_remote_retrieve_body($response), true);
        foreach ($products as $product) {
            //  if ($product['id'] == '4') {
            //      $this->create_or_update_product($product);
            //  }
            $this->create_or_update_product($product);
        }

        wp_redirect(add_query_arg('sync_success', 'true', admin_url('admin.php?page=ample-product-sync')));
        exit;
    }

    public function add_product($product_data) {
        $this->create_or_update_product($product_data);
        return true;
    }

    private function category_exists($category_name) {
        try {
            $categories = $this->woocommerce->get('products/categories', ['search' => $category_name]);
            if (!empty($categories)) {
                return $categories[0]->id;
            } else {
                return false;
            }
        } catch (HttpClientException $e) {
            echo "Error checking category: {$e->getMessage()}\n";
            return false;
        }
    }

    private function validate_image_url($url) {
        $headers = @get_headers($url);
        return $headers && strpos($headers[0], '200');
    }

    private function sanitize_category_name($category_name) {
        return strtolower(preg_replace('/[^a-zA-Z0-9]+/', '-', $category_name));
    }

    private function create_or_update_product($productData) {
        $category_name = $productData['product_type_name'];
        $category_id = $this->category_exists($category_name);
        
        // If category is new add a new category
        if (!$category_id) {
            try {
                $category = $this->woocommerce->post('products/categories', [
                    'name' => $category_name,
                    'slug' => $this->sanitize_category_name($category_name),
                    'parent' => 0,
                ]);
                $category_id = $category->id;
            } catch (HttpClientException $e) {
                echo "Error creating category '{$category_name}': {$e->getMessage()}\n";
                return;
            }
        }

        $attributeOptions = array_map(function ($sku) {
            return $sku['unit_grams'] . 'g';
        }, $productData['skus']);
        $attributeOptions = array_unique($attributeOptions);

        $image_url = $productData['product_image'];
        $images = [];
        if ($this->validate_image_url($image_url)) {
            $images[] = ['src' => $image_url];
        }

        $sku = 'sku-' . $productData['id'];

        try {
            $existing_products = $this->woocommerce->get('products', ['sku' => $sku]);

            if (!empty($existing_products)) {
                $product_id = $existing_products[0]->id;

                $attributes = $this->prepare_attributes($productData, $attributeOptions);

                $this->woocommerce->put("products/{$product_id}", [
                    'name' => $productData['name'],
                    'type' => 'variable',
                    'description' => $productData['description'],
                    'short_description' => $productData['description'],
                    'categories' => [['id' => $category_id]],
                    'images' => $images,
                    'attributes' => $attributes,
                ]);

                $this->create_or_update_variations($productData, $product_id);

                echo "Product '{$productData['name']}' updated.\n";
                return;
            }
        } catch (HttpClientException $e) {
            echo "Error checking/updating product '{$productData['name']}': {$e->getMessage()}\n";
            return;
        }

        try {
            $attributes = $this->prepare_attributes($productData, $attributeOptions);

            $product = $this->woocommerce->post('products', [
                'name' => $productData['name'],
                'type' => 'variable',
                'description' => $productData['description'],
                'short_description' => $productData['description'],
                'sku' => $sku,
                'categories' => [['id' => $category_id]],
                'images' => $images,
                'attributes' => $attributes,
            ]);

            $product_id = $product->id;

            $this->create_or_update_variations($productData, $product_id);
        } catch (HttpClientException $e) {
            echo "Error creating product '{$productData['name']}': {$e->getMessage()}\n";
        }
    }

    private function prepare_attributes($productData, $attributeOptions) {
        $attributes = [];
        if (!empty($productData['brand_name'])) {
            $attributes[] = [
                'name' => 'Brands',
                'options' => [$productData['brand_name']],
                'position' => 0,
                'visible' => true,
                'variation' => false,
            ];
        }
        if (!empty($productData['price_per_gram'])) {
            $attributes[] = [
                'name' => 'Price Per Gram',
                'options' => [$productData['price_per_gram']],
                'position' => 1,
                'visible' => true,
                'variation' => false,
            ];
        }
        if (!empty($productData['product_strain_name'])) {
            $attributes[] = [
                'name' => 'Strains',
                'options' => [$productData['product_strain_name']],
                'position' => 2,
                'visible' => true,
                'variation' => false,
            ];
        }
        $attributes[] = [
            'name' => 'PACKAGE SIZE',
            'options' => $attributeOptions,
            'position' => 3,
            'visible' => true,
            'variation' => true,
        ];
        return $attributes;
    }

    private function create_or_update_variations($productData, $product_id) {
        foreach ($productData['skus'] as $index => $sku) {
            $variation_sku = $productData['id'] . '-' . $sku['id'];
            $variation_id = null;

            $existing_variations = $this->woocommerce->get("products/{$product_id}/variations", ['sku' => $variation_sku]);
            if (!empty($existing_variations)) {
                $variation_id = $existing_variations[0]->id;
            }

            $variation_data = [
                'sku' => $variation_sku,
                'regular_price' => number_format($sku['unit_price'] / 100, 2),
                'attributes' => [
                    [ 
                        'name' => 'PACKAGE SIZE',
                        'option' => $sku['unit_grams'] . 'g',
                    ],
                ],
                'description' => $sku['description'],
                // 'manage_stock' => true,
                // 'stock_quantity' => $sku['in_stock'] ? 100 : 0,
                // 'in_stock' => $sku['in_stock'],
                'in_stock' => true,
                'backorders' => $sku['allow_backorder'] ? 'yes' : 'no',
            ];

            if ($variation_id) {
                $this->woocommerce->put("products/{$product_id}/variations/{$variation_id}", $variation_data);
            } else {
                $this->woocommerce->post("products/{$product_id}/variations", $variation_data);
            }
        }
    }
}

// WC_Product_Sync::init();
