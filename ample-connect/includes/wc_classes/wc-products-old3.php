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

    }

    public function add_custom_variable_product($productData) {

        // Process cannabinoid profiles and create attributes
        $processed = $this->prepare_product_attributes_and_variations($productData);
        $attributes = $processed['attributes'];
        $variations = $processed['variations'];
        
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
        $product->save();

        // Optional: Set description if available
        if (!empty($productData['description'])) {
            $product->set_description($productData['description']);
        }
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
        // Add product-level attributes
        $wc_attributes = [];
        foreach ($attributes as $name => $attr_data) {
            $attribute = new WC_Product_Attribute();
            $attribute->set_name($name);
            $attribute->set_options($attr_data['options']);
            $attribute->set_visible($attr_data['visible']);
            $attribute->set_variation($attr_data['variation']);
            $wc_attributes[] = $attribute;
        }

        $product->set_attributes($wc_attributes);
        $product_id = $product->save();


        // Step 3: Create variations
        foreach ($variations as $variation_info) {
            $variation = new WC_Product_Variation();
            $variation->set_parent_id($product_id);
            $variation->set_regular_price($variation_info['price']);
            $variation->set_sku($variation_info['sku']);

            // Add variation attribute via meta (e.g., 'attribute_pa_package-size')
            foreach ($variation_info['attributes'] as $attr_name => $attr_value) {
                $taxonomy = wc_sanitize_taxonomy_name($attr_name); // e.g., package-size
                $meta_key = 'attribute_' . $taxonomy;
                $variation->update_meta_data($meta_key, $attr_value);
            }

            // Custom meta fields (cannabinoid data)
            foreach ($variation_info['custom_meta'] as $meta_key => $meta_value) {
                $variation->update_meta_data($meta_key, $meta_value);
            }

            if (!empty($variation_info['unit_grams'])) {
                $variation->update_meta_data('unit_grams', $variation_info['unit_grams']);
            }

            $variation->save();
        }
        return $product_id;
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
    

    private function prepare_product_attributes_and_variations($productData) {
        $attributes = [];
        $variation_data = [];
    
        $attribute_map = []; // For global cannabinoid attributes
        $unit_map = [
            'mg'    => 'mg',
            'mg_g'  => 'mg/g',
            'mg_ml' => 'mg/ml',
        ];
    
        // Add product-level attributes first (not for variation)
        if (!empty($productData['product_strain_name'])) {
            $attributes['Strain'] = [
                'name' => 'Strain',
                'options' => [ $productData['product_strain_name'] ],
                'variation' => false,
                'visible' => true,
            ];
        }
    
        if (!empty($productData['brand_name'])) {
            $attributes['Brand'] = [
                'name' => 'Brand',
                'options' => [ $productData['brand_name'] ],
                'variation' => false,
                'visible' => true,
            ];
        }
    
        if (!empty($productData['price_per_gram'])) {
            $price_per_gram = floatval($productData['price_per_gram']);
            $attributes['Price Per Gram'] = [
                'name' => 'Price Per Gram',
                'options' => [ rtrim(rtrim(number_format($price_per_gram, 2, '.', ''), '0'), '.') ],
                'variation' => false,
                'visible' => true,
            ];
        }
    
        // Loop through SKUs to extract cannabinoid profiles and variations
        foreach ($productData['skus'] as $sku) {
            $variation = [];
            $variation['price'] = isset($sku['unit_price']) ? floatval($sku['unit_price']) / 100 : 0;
            $variation['unit_grams'] = $sku['unit_grams'] ?? '';
            $variation['name'] = $sku['name'];
            $variation['sku'] = $sku['product_id'] . '-' . $sku['id'];
    
            // Variation attribute for PACKAGE SIZE
            $variation['attributes'] = [
                'PACKAGE SIZE' => $sku['name']
            ];
    
            // Extract cannabinoid data
            $cannabinoids = $sku['cannabinoid_profile'] ?? [];
            $custom_meta = [];
    
            foreach ($cannabinoids as $key => $value) {
                if (isset($value['high']) && $value['high'] !== null) {
                    // Parse prefix and cannabinoid name
                    $parts = explode('_', $key);
                    $unit_prefix = 'percent';
                    $compound_parts = [];
    
                    foreach ($parts as $part) {
                        if (in_array($part, ['mg', 'mg_g', 'mg_ml'])) {
                            $unit_prefix = $part;
                        } else {
                            $compound_parts[] = $part;
                        }
                    }
    
                    $compound_name = strtoupper(end($compound_parts));
                    $unit = $unit_map[$unit_prefix] ?? '%';
                    $attribute_key = "{$compound_name} ({$unit})";
    
                    // Track for product-level global display
                    $attribute_map[$attribute_key][] = $value['high'];
    
                    // Add to variation custom meta
                    $custom_meta[$attribute_key] = $value['high'];
                }
            }
    
            $variation['custom_meta'] = $custom_meta;
            $variation_data[] = $variation;
        }
    
        // Add global cannabinoid attributes to product (not used for variation)
        foreach ($attribute_map as $attr_name => $values) {
            $unique_values = array_unique(array_map(function ($v) {
                return is_numeric($v) ? rtrim(rtrim(number_format((float)$v, 2, '.', ''), '0'), '.') : $v;
            }, $values));
    
            $attributes[$attr_name] = [
                'name' => $attr_name,
                'options' => $unique_values,
                'variation' => false,
                'visible' => true,
            ];
        }
    
        // Add PACKAGE SIZE as the only variation attribute
        $package_sizes = array_column($variation_data, 'name');
        $attributes['PACKAGE SIZE'] = [
            'name' => 'PACKAGE SIZE',
            'options' => array_unique($package_sizes),
            'variation' => true,
            'visible' => true,
        ];
    
        return [
            'attributes' => $attributes,
            'variations' => $variation_data,
        ];
    }
    
    
}