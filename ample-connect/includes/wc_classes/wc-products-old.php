<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

require_once plugin_dir_path(__FILE__) . '../../vendor/autoload.php';
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

        $allProfiles = $sku["cannabinoid_profile"];
        $allCannaProfiles = array();
        foreach($allProfiles as $key => $value) {
            if (is_null($value['high'])) 
                continue;

            $allCannaProfiles[] = array('key' => $key, 'value' => $value['high']);
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

        
        try {
            if ($variation_id) {
                $this->woocommerce->put("products/{$product_id}/variations/{$variation_id}", $variation_data);
            } else {
                $this->woocommerce->post("products/{$product_id}/variations", $variation_data);
            }

            update_post_meta($product_id, '_cannabinoid_profiles', $allCannaProfiles);

        } catch (HttpClientException $e) {
            echo "Error creating/updating variation '{$sku['name']}': {$e->getMessage()}\n";
            return false;
        }
        
    }

    private function create_or_update_variations($productData, $product_id) {
        if ($product_id) {
            foreach ($productData['skus'] as $index => $sku) {
                $this->create_or_update_variation($sku, $product_id);
            }
        }
    }    


    public function add_custom_variable_product($productData) {

        // Process cannabinoid profiles and create attributes
        $processed = $this->prepare_product_attributes_and_variations($productData);

        // === 1. Define Category and Subcategory ===
        $parent_category = $productData['product_type_name'];
        $child_category = $productData['product_type_subclass'];

        // Create or get parent category
        $parent_term = term_exists($parent_category, 'product_cat');
        if (!$parent_term) {
            $parent_term = wp_insert_term($parent_category, 'product_cat');
        }

        // Create or get child category
        $child_term = term_exists($child_category, 'product_cat');
        if (!$child_term) {
            $child_term = wp_insert_term($child_category, 'product_cat', [
                'parent' => is_array($parent_term) ? $parent_term['term_id'] : $parent_term
            ]);
        }

        $category_ids = [
            is_array($parent_term) ? $parent_term['term_id'] : $parent_term,
            is_array($child_term) ? $child_term['term_id'] : $child_term,
        ];

        // === 2. Create Variable Product ===
        $sku = 'sku-' . $productData['id'];

        $product = new WC_Product_Variable();
        $product->set_name($productData['name']);
        $product->set_status('publish');
        $product->set_catalog_visibility('visible');
        $product->set_description($productData['description']);
        $product->set_sku($sku);
        $product->set_manage_stock(true);
        $product->set_stock_status('instock');
        $product->set_category_ids($category_ids);
        $product->save();

        $product_id = $product->get_id();

        // Add product image if available
        $image_url = $productData['product_image'];
        if (!empty($image_url)) {
            $attachment_id = $this->download_image_to_media_library($image_url);
            if ($attachment_id) {
                $this->attach_image_to_product($attachment_id, $product_id, true);
            }
        }

        // 3. Add Attributes
        $attribute_objects = [];

        foreach ($processed['attributes'] as $attr) {
            $taxonomy = wc_sanitize_taxonomy_name(sanitize_title($attr['name']));
            $label = $attr['name'];
            $options = $attr['options'];

            // Register the attribute if not exists (for global)
            if (!taxonomy_exists($taxonomy)) {
                register_taxonomy(
                    $taxonomy,
                    'product',
                    [
                        'label' => $label,
                        'hierarchical' => false,
                        'show_ui' => false,
                        'query_var' => true,
                        'rewrite' => false,
                    ]
                );
            }

            // Add terms (for variation and non-variation attributes)
            foreach ($options as $option) {
                if (!term_exists($option, $taxonomy)) {
                    wp_insert_term($option, $taxonomy);
                }
            }

            $attribute = new WC_Product_Attribute();
            $attribute->set_name($taxonomy);
            $attribute->set_options($options);
            $attribute->set_visible(true);
            $attribute->set_variation($attr['variation']);

            $attribute_objects[] = $attribute;
        }

        $product->set_attributes($attribute_objects);
        $product->save();

        // 4. Create Variations
        foreach ($processed['variations'] as $variation) {
            $variation_post = [
                'post_title'  => $product->get_name() . ' Variation',
                'post_name'   => 'product-' . $product_id . '-variation',
                'post_status' => 'publish',
                'post_parent' => $product_id,
                'post_type'   => 'product_variation',
                'guid'        => home_url() . '/?product_variation=product-' . $product_id . '-variation',
            ];
    
            $variation_id = wp_insert_post($variation_post);
    
            if (is_wp_error($variation_id)) {
                continue;
            }

            if (is_wp_error($variation_id)) {
                continue;
            }
    
            // Set variation meta: attributes
            foreach ($variation['attributes'] as $taxonomy => $value) {
                $attribute_slug = wc_sanitize_taxonomy_name($taxonomy);
                update_post_meta($variation_id, 'attribute_' . $attribute_slug, $value);
            }
    
            // Set variation SKU and price
            update_post_meta($variation_id, '_sku', $variation['sku']);
            update_post_meta($variation_id, '_regular_price', $variation_data['price'] ?? '0'); // Placeholder
            update_post_meta($variation_id, '_price', $variation_data['price'] ?? '0');
        }
    }


    private function download_image_to_media_library($image_url) {
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/media.php');
        require_once(ABSPATH . 'wp-admin/includes/image.php');
    
        // 1. Check if image already exists
        $existing = new WP_Query([
            'post_type'      => 'attachment',
            'post_status'    => 'inherit',
            'meta_key'       => '_original_image_url',
            'meta_value'     => $image_url,
            'posts_per_page' => 1,
            'fields'         => 'ids',
        ]);
    
        if (!empty($existing->posts)) {
            return $existing->posts[0]; // Return existing attachment ID
        }
    
        // 2. Download image
        $tmp_file = download_url($image_url);
        if (is_wp_error($tmp_file)) {
            error_log('Image download failed: ' . $tmp_file->get_error_message());
            return false;
        }
    
        // 3. Upload to media library
        $file_array = [
            'name'     => basename($image_url),
            'tmp_name' => $tmp_file,
        ];
    
        $attachment_id = media_handle_sideload($file_array, 0); // 0 = no post association
        if (is_wp_error($attachment_id)) {
            @unlink($tmp_file);
            error_log('Image upload failed: ' . $attachment_id->get_error_message());
            return false;
        }
    
        // 4. Save original URL as meta
        update_post_meta($attachment_id, '_original_image_url', esc_url_raw($image_url));
    
        return $attachment_id;
    }

    private function attach_image_to_product($attachment_id, $product_id, $set_as_featured = true) {
        if (!$attachment_id || !$product_id) return;
    
        if ($set_as_featured) {
            set_post_thumbnail($product_id, $attachment_id);
        } else {
            $gallery = get_post_meta($product_id, '_product_image_gallery', true);
            $gallery_ids = array_filter(explode(',', $gallery));
            if (!in_array($attachment_id, $gallery_ids)) {
                $gallery_ids[] = $attachment_id;
                update_post_meta($product_id, '_product_image_gallery', implode(',', $gallery_ids));
            }
        }
    }
    

    private function prepare_product_attributes_and_variations($product_data) {
        $attributes = [];
        $attribute_terms = [];
        $variations = [];
    
        $cannabinoid_keys = ['thc', 'cbd', 'cbg', 'cbc', 'cbn'];
    
        // Product-level attributes (non-variation)
        $product_level_attributes = [
            'Strain' => $product_data['product_strain_name'] ?? null,
            'Brand' => $product_data['brand_name'] ?? null,
            'Price Per Gram' => $product_data['price_per_gram'] ?? null,
        ];
    
        foreach ($product_level_attributes as $label => $value) {
            if (!empty($value)) {
                $attributes[$label] = [
                    'name' => $label,
                    'value' => $value,
                    'visible' => true,
                    'variation' => false,
                ];
            }
        }
    
        // Loop through SKUs as variations
        foreach ($product_data['skus'] as $sku) {
            $variation_attributes = [];
    
            if (!empty($sku['cannabinoid_profile'])) {
                foreach ($sku['cannabinoid_profile'] as $key => $entry) {
                    if (empty($entry['high'])) continue;
    
                    if (preg_match('/_unit(_|$)/', $key)) continue;
    
                    $parts = explode('_', $key);
                    $prefix = $parts[0];
                    $cannabinoid = $parts[count($parts) - 2] ?? end($parts);
    
                    if (!in_array($cannabinoid, $cannabinoid_keys)) continue;
    
                    $unit_map = [
                        'mg' => 'mg',
                        'mg/g' => 'mg/g',
                        'mg/ml' => 'mg/ml',
                    ];
                    $unit = isset($unit_map[$prefix]) ? $unit_map[$prefix] : ($prefix === 'mg' ? 'mg' : null);
                    if (!$unit) continue;
    
                    $label = strtoupper($cannabinoid);
                    $value = $entry['high'] . $unit;
    
                    $variation_attributes[$label] = $value;
                    $attribute_terms[$label][] = $value;
                }
            }
    
            // Add unit_grams as PACKAGE SIZE
            if (!empty($sku['unit_grams'])) {
                $label = 'PACKAGE SIZE';
                $value = $sku['unit_grams'] . 'g';
                $variation_attributes[$label] = $value;
                $attribute_terms[$label][] = $value;
            }
    
            // Add variation
            $variations[] = [
                'attributes' => $variation_attributes,
                'sku' => (string) $sku['product_id'] . '-' . $sku['id'], // Use `id` as SKU
                'price' => $sku['unit_price'] / 100,
            ];
        }
    
        // Now construct attributes with options and correct keys
        foreach ($attribute_terms as $label => $terms) {
            $terms = array_unique($terms);
            $attributes[$label] = [
                'name' => $label,
                'options' => $terms,
                'visible' => true,
                'variation' => true, // ✅ Restored this key
            ];
        }
    
        return [
            'attributes' => array_values($attributes), // Ensure indexed array
            'variations' => $variations,
        ];
    }
    
    
}