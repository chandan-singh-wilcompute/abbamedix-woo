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

    public function clean_product_categories() {
        if (!current_user_can('manage_woocommerce')) {
            return;
        }

        // 1. Delete all products (including variations)
        $product_args = [
            'post_type'      => ['product', 'product_variation'],
            'post_status'    => ['any'],
            'posts_per_page' => -1,
            'fields'         => 'ids',
        ];

        $product_ids = get_posts($product_args);

        foreach ($product_ids as $product_id) {
            wp_delete_post($product_id, true); // true = force delete
        }

        // 2. Delete all product categories and subcategories
        $terms = get_terms([
            'taxonomy'   => 'product_cat',
            'hide_empty' => false,
        ]);

        foreach ($terms as $term) {
            wp_delete_term($term->term_id, 'product_cat');
        }

        $category_dictionary = [
            'Dried'                 => 'Dried Flower',
            'Edibles - Solids'      => 'Edibles',
            'Extracts - Ingested'   => 'Extracts',
            'Edibles - Non-solids'  => 'Beverages',
            'Topicals'              => 'Topicals',
            'Extracts - Inhaled'    => 'Vapes',
            'Extracts - Other'      => 'Concentrates',
            'Accessories'           => 'Accessories'
        ];

        foreach ($category_dictionary as $key => $value) {
            // Check if a category with this name already exists
            if (!term_exists($value, 'product_cat')) {
                // Create product category
                wp_insert_term($value, 'product_cat');
            } 
        }

        return 1;
    }

    public function add_custom_variable_product($productData) {
        if (empty($productData['skus'])) return;

        $category_dictionary = [
            'Dried'                 => 'Dried Flower',
            'Edibles - Solids'      => 'Edibles',
            'Extracts - Ingested'   => 'Extracts',
            'Edibles - Non-solids'  => 'Beverages',
            'Topicals'              => 'Topicals',
            'Extracts - Inhaled'    => 'Vapes',
            'Extracts - Other'      => 'Concentrates',
            'Accessories'           => 'Accessories'
        ];

        // === 1. Define Category and Subcategory ===
        $parent_category = $productData['product_type_subclass'];
        $child_category = $productData['product_type_name'];

        
        // if (stripos($parent_category, '(discrete units)') !== false) {
        //     $parent_category = preg_replace('/\s*\(discrete units\)/i', '', $parent_category);
        // }
        if (stripos($child_category, '(discrete units)') !== false) {
            $child_category = preg_replace('/\s*\(discrete units\)/i', '', $child_category);
        }

        $parent_category = trim($parent_category);
        $child_category = trim($child_category);

        $parent_category = isset($category_dictionary[$parent_category]) ? $category_dictionary[$parent_category] : $parent_category;

        // // Create or get parent category
        // $parent_term = term_exists($parent_category, 'product_cat');
        // if (!$parent_term) {
        //     $parent_term = wp_insert_term($parent_category, 'product_cat');
        // }

        // // Create or get child category
        // $child_term = term_exists($child_category, 'product_cat');
        // if (!$child_term) {
        //     $child_term = wp_insert_term($child_category, 'product_cat', [
        //         'parent' => is_array($parent_term) ? $parent_term['term_id'] : $parent_term
        //     ]);
        // }

        // $category_ids = [
        //     is_array($parent_term) ? $parent_term['term_id'] : $parent_term,
        //     is_array($child_term) ? $child_term['term_id'] : $child_term,
        // ];

        // === 2. Create Variable Product ===
        $sku = 'sku-' . $productData['id'];

        $existing_id = get_product_id_by_sku( $sku );

        if ( $existing_id ) {
            // Update existing product
            $product = wc_get_product( $existing_id );
        } else {
            // Create new product
            $product = new WC_Product_Variable();
        }

        
        $product->set_name($productData['name']);
        $product->set_status('publish');
        $product->set_catalog_visibility('visible');
        $product->save();

        // Optional: Set description if available
        
        $product->set_description($productData['description'] ?? '');
        $product->set_sku($sku);
        $product->set_manage_stock(true);
        $product->set_stock_status('instock');
        // $product->set_category_ids($category_ids);
        $product->set_attributes($attributes);

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

        // Add attributes
        // Product-level attributes
        $package_sizes = [];
        $cannabinoid_values = [];

        // Cannabinoid keys to track
        $cannabinoids = ['thc', 'cbd', 'cbg', 'cbn', 'cbc'];

        foreach ($productData['skus'] as $sku_data) {
            $package_size = is_null($sku_data['net_weight']) ? 0 : floatval($sku_data['net_weight']);
            $package_sizes[] = "{$package_size}g"; // Include "g" suffix

            $cannabinoid_profile = $sku_data['cannabinoid_profile'] ?? [];
            foreach ($cannabinoid_profile as $key => $value_data) {
                $lower_key = strtolower($key);
                foreach ($cannabinoids as $cannabinoid) {
                    if (strpos($lower_key, $cannabinoid) !== false && isset($value_data['high'])) {
                        // Extract unit
                        if (strpos($lower_key, 'mg_g_') !== false) {
                            $unit = 'mg/g';
                        } elseif (strpos($lower_key, 'mg_ml_') !== false) {
                            $unit = 'mg/ml';
                        } elseif (strpos($lower_key, 'mg_') !== false) {
                            $unit = 'mg';
                        } else {
                            $unit = '%';
                        }

                        $value = floatval($value_data['high']);
                        $cannabinoid_values[$cannabinoid][$unit][] = $value;
                    }
                }
            }
        }

        // Build PACKAGE SIZE attribute
        $package_sizes = array_unique($package_sizes);
        $attributes = [];

        $package_attr = new WC_Product_Attribute();
        $package_attr->set_name('PACKAGE SIZE');
        $package_attr->set_options($package_sizes);
        $package_attr->set_visible(true);
        $package_attr->set_variation(true);
        $attributes[] = $package_attr;

        // Add cannabinoid attributes (as range values)
        foreach ($cannabinoid_values as $cannabinoid => $units) {
            $parts = [];
            $count = 1;
            foreach ($units as $unit => $values) {
                if ($count > 1) 
                    break;

                $min = min($values);
                $max = max($values);
                if ($min == $max) {
                    $parts[] = "{$min} {$unit}";
                } else {
                    $parts[] = "{$min} - {$max} {$unit}";
                }
                $count += 1;
            }

            $attribute = new WC_Product_Attribute();
            $attribute->set_name(strtoupper($cannabinoid));
            $attribute->set_options([$value = implode(' | ', $parts)]);
            $attribute->set_visible(true);
            $attribute->set_variation(false);
            $attributes[] = $attribute;
        }

        $product->set_attributes($attributes);
        $product->save();

        // Create variations
        foreach ($productData['skus'] as $sku_data) {

            $var_sku = $sku_data['product_id'] . '-' . $sku_data['id'];
            $existing_var_id = get_product_id_by_sku( $var_sku );

            if ( $existing_var_id ) {
                $variation = new WC_Product_Variation( $existing_var_id );
            } else {
                $variation = new WC_Product_Variation();
                $variation->set_parent_id( $product_id );
            }

            $net_weight = is_null($sku_data['net_weight']) ? 0 : floatval($sku_data['net_weight']);
            $variation->set_attributes(['package-size' => "{$net_weight}g"]);
            $variation->set_weight($net_weight);

            $variation->set_regular_price($sku_data['unit_price'] ? $sku_data['unit_price'] / 100 : '0');
            // $variation->set_sku($sku_data['product_id'] . '-' . $sku_data['id']);
            $variation->set_manage_stock(true);

            // Add cannabinoid meta fields to variation
            $cannabinoid_profile = $sku_data['cannabinoid_profile'] ?? [];
            foreach ($cannabinoids as $cannabinoid) {
                $values_by_unit = [];
                foreach ($cannabinoid_profile as $key => $value_data) {
                    $lower_key = strtolower($key);
                    if (strpos($lower_key, $cannabinoid) !== false && isset($value_data['high'])) {
                        if (strpos($lower_key, 'mg_g_') !== false) {
                            $unit = 'mg/g';
                        } elseif (strpos($lower_key, 'mg_ml_') !== false) {
                            $unit = 'mg/ml';
                        } elseif (strpos($lower_key, 'mg_') !== false) {
                            $unit = 'mg';
                        } else {
                            $unit = '%';
                        }

                        $value = floatval($value_data['high']);
                        $values_by_unit[$unit][] = $value;
                    }
                }

                if (!empty($values_by_unit)) {
                    $parts = [];
                    $total = '';
                    // foreach ($values_by_unit as $unit => $values) {
                    //     $parts[] = implode(', ', $values) . " {$unit}";
                    // }
                    foreach ($values_by_unit as $unit => $values) {
                        $unique_values = array_unique($values);
                        $parts[] = implode(', ', $unique_values) . " {$unit}";
                        if ($unit != '%') {
                            $total = array_sum($unique_values) . " {$unit}";
                        }
                    }
                    // update_post_meta($variation->get_id(), strtoupper($cannabinoid), implode(' | ', $parts));
                    $variation->update_meta_data(strtoupper($cannabinoid), implode(' | ', $parts));

                    if ($total != '') {
                        $variation->update_meta_data('TOTAL ' . strtoupper($cannabinoid), $total);
                    }  
                }
            }

            if ($productData['is_cannabis']) {
                $variation->update_meta_data('RX Reduction', (float)$sku_data['unit_grams']);
            }

            $variation->save();

        }
    
        $this->assign_product_to_dynamic_category($product_id, $parent_category, $child_category);
        return $product_id;
    }

    private function assign_product_to_dynamic_category($product_id, $product_type, $sub_class) {
        // Step 1: Get or create the parent category (Product Type)
        $parent_term = get_term_by('name', $product_type, 'product_cat');
        if (!$parent_term) {
            $parent_term = wp_insert_term($product_type, 'product_cat');
            if (is_wp_error($parent_term)) return; // Bail if insert failed
            $parent_id = $parent_term['term_id'];
        } else {
            $parent_id = $parent_term->term_id;
        }

        // Step 2: Check if the Sub Class already exists under *this* parent
        $existing_sub_terms = get_terms([
            'taxonomy'   => 'product_cat',
            'name'       => $sub_class,
            'hide_empty' => false,
            'parent'     => $parent_id,
        ]);

        if (!empty($existing_sub_terms) && !is_wp_error($existing_sub_terms)) {
            // Use the existing one under the correct parent
            $sub_term_id = $existing_sub_terms[0]->term_id;
        } else {
            // Create a new subcategory under the current parent
            $new_sub_term = wp_insert_term($sub_class, 'product_cat', ['parent' => $parent_id]);
            if (is_wp_error($new_sub_term)) return; // Bail if insert failed
            $sub_term_id = $new_sub_term['term_id'];
        }

        // Step 3: Assign product to the subcategory (which implicitly relates it to the parent)
        wp_set_object_terms($product_id, [$sub_term_id], 'product_cat');
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
    

    private function extract_product_attributes_and_variations($product_data) {
        $cannabinoids = ['thc', 'cbd', 'cbg', 'cbc', 'cbn'];
        $product_attributes = [];
        $variation_attributes = [];
        $variations = [];
        $combined_cannabinoids = [];
    
        // 1. Add product-level attributes
        $product_level_keys = [
            'product_strain_name' => 'Strain',
            'brand_name' => 'Brand',
            'price_per_gram' => 'Price Per Gram',
        ];
    
        foreach ($product_level_keys as $key => $label) {
            if (!empty($product_data[$key])) {
                $product_attributes[$label] = [
                    'name' => $label,
                    'value' => $product_data[$key],
                    'is_visible' => true,
                    'is_taxonomy' => false,
                    'is_variation' => false,
                ];
            }
        }
    
        // 2. Loop through SKUs (variations)
        foreach ($product_data['skus'] as $index => $sku) {
            $variation = [
                'attributes' => [],
                'meta_data' => [],
                'regular_price' => isset($sku['unit_price']) ? number_format($sku['unit_price'] / 100, 2, '.', '') : '',
                'sku' => $sku['product_id'] . '-' . $sku['id'],
            ];
    
            // 2a. Add PACKAGE SIZE attribute
            $net_weight = is_null($sku_data['net_weight']) ? '0' : $sku_data['net_weight'];
            $package_size = $net_weight . 'g';

            $variation['attributes']['PACKAGE SIZE'] = $package_size;
            $variation_attributes['PACKAGE SIZE'][] = $package_size;
            
    
            // 2b. Process cannabinoid profile
            $cannabinoid_profile = $sku['cannabinoid_profile'] ?? [];
            $cannabinoid_summary = [];
    
            foreach ($cannabinoid_profile as $key => $values) {
                foreach ($cannabinoids as $cannabinoid) {
                    if (stripos($key, $cannabinoid) !== false && !preg_match('/acid|potential|actual|unit/i', $key)) {
                        $unit = '%';
                        if (strpos($key, 'mg_g') !== false) {
                            $unit = 'mg/g';
                        } elseif (strpos($key, 'mg_ml') !== false) {
                            $unit = 'mg/ml';
                        }
    
                        $clean_name = strtoupper($cannabinoid);
                        $value = $values['high'] ?? null;
    
                        if ($value !== null) {
                            $formatted_value = "{$value}{$unit}";
                            $cannabinoid_summary[] = "{$clean_name}: {$formatted_value}";
    
                            // Add to variation meta
                            $variation['meta_data'][] = [
                                'key' => $clean_name,
                                'value' => $formatted_value
                            ];
    
                            // Add to product-level attribute list for combined display
                            $combined_cannabinoids[$clean_name][] = $formatted_value;
                        }
                    }
                }
            }
    
            $variations[] = $variation;
        }
    
        // 3. Finalize PACKAGE SIZE as a product-level attribute
        foreach ($variation_attributes as $name => $values) {
            $product_attributes[$name] = [
                'name' => $name,
                'value' => implode(' | ', array_unique($values)),
                'is_visible' => true,
                'is_taxonomy' => false,
                'is_variation' => true,
            ];
        }
    
        // 4. Add cannabinoid attributes to product-level for display
        foreach ($combined_cannabinoids as $name => $values) {
            $sequence = [];
            foreach ($values as $idx => $val) {
                $sequence[] = ($idx + 1) . ") " . $val;
            }
    
            $product_attributes[$name] = [
                'name' => $name,
                'value' => implode(' | ', $sequence),
                'is_visible' => true,
                'is_taxonomy' => false,
                'is_variation' => false,
            ];
        }
    
        return [
            'attributes' => array_values($product_attributes),
            'variations' => $variations
        ];
    }   
    
}

function get_product_id_by_sku( $sku ) {
    global $wpdb;

    $product_id = wc_get_product_id_by_sku( $sku );

    return $product_id ? intval( $product_id ) : false;
}