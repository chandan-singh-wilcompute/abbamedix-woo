<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

require_once plugin_dir_path(__FILE__) . '../vendor/autoload.php';
use Automattic\WooCommerce\Client;
use Automattic\WooCommerce\HttpClient\HttpClientException;


class WC_Products {

    private $woocommerce;

    public function __construct() {

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
            $this->woocommerce = null;
        }
    }


    public function add_product($productData) {
        $product_id = $this->create_or_update_product($productData);
        $this->create_or_update_variations($productData, $product_id);
        return true;
    }

    public function update_product($productData) {
        $this->create_or_update_product($productData);
        return true;
    } 

    public function update_sku($skuData, $productId) {
        $product_id = $this->product_exists($productId);

        if ($product_id) {
            $this->create_or_update_variation($skuData, $product_id);
        }
        
        return true;
    }


    private function product_exists($product_id) {
        $sku = 'sku-' . $product_id;
        $product_id_woo = null;
        try {
            $existing_products = $this->woocommerce->get('products', ['sku' => $sku]);
            if (!empty($existing_products)) {
                $product_id_woo = $existing_products[0]->id;
            }
        } catch (HttpClientException $e) {
            echo "Error finding product";
        }
        
        return $product_id_woo;
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
            $attributes = $this->prepare_attributes($productData, $attributeOptions);

            if (!empty($existing_products)) {
                $product_id = $existing_products[0]->id;

                $this->woocommerce->put("products/{$product_id}", [
                    'name' => $productData['name'],
                    'type' => 'variable',
                    'description' => $productData['description'],
                    'short_description' => $productData['description'],
                    'categories' => [['id' => $category_id]],
                    'attributes' => $attributes,
                ]);

                // echo "Product '{$productData['name']}' updated.\n";
                return $product_id;
            } else {
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

                // echo "Product '{$productData['name']}' created.\n";
                return $product_id;
            }

        } catch (HttpClientException $e) {
            echo "Error checking/updating product '{$productData['name']}': {$e->getMessage()}\n";
            return false;
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

    private function create_or_update_variation($sku, $product_id) {
        $variation_sku = $sku['product_id'] . '-' . $sku['id'];
        $variation_id = null;

        $existing_variations = $this->woocommerce->get("products/{$product_id}/variations", ['sku' => $variation_sku]);
        if (!empty($existing_variations)) {
            $variation_id = $existing_variations[0]->id;
        }
        $sku_name = "";
        if (is_null($sku['description'])) {
            $sku_name = $sku['name'];
        } else {
            $sku_name = $sku['description'];
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
            'description' => $sku_name,
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

    private function create_or_update_variations($productData, $product_id) {
        $productId = $this->product_exists($product_id);
        if ($productId) {
            foreach ($productData['skus'] as $index => $sku) {
                $this->create_or_update_variation($sku, $productId);
            }
        }
    }

}