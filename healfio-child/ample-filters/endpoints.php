<?php
/**
 * Ample Filter System - REST API Endpoints
 * 
 * High-performance product data API with caching
 * Part of Phase 1 implementation for ABBA Issue #8
 * 
 * @package AmpleFilter
 * @version 1.0.0
 * @since 2025-08-29
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class Ample_Filter_Endpoints {
    
    /**
     * API namespace
     */
    const NAMESPACE = 'ample/v1';
    
    /**
     * Initialize REST API endpoints
     */
    public static function init() {
        add_action('rest_api_init', [self::class, 'register_endpoints']);
    }
    
    /**
     * Register all REST API endpoints
     */
    public static function register_endpoints() {
        // Main products endpoint
        register_rest_route(self::NAMESPACE, '/products', [
            'methods' => 'GET',
            'callback' => [self::class, 'get_products'],
            'permission_callback' => '__return_true',
            'args' => [
                'refresh' => [
                    'description' => 'Force refresh cache',
                    'type' => 'boolean',
                    'default' => false
                ],
                'user_id' => [
                    'description' => 'User ID for personalized data',
                    'type' => 'integer',
                    'default' => 0
                ]
            ]
        ]);
        
        // Filter terms endpoint (categories, brands)
        register_rest_route(self::NAMESPACE, '/filter-terms', [
            'methods' => 'GET',
            'callback' => [self::class, 'get_filter_terms'],
            'permission_callback' => '__return_true'
        ]);
        
        // Product attributes endpoint (THC/CBD ranges, terpenes)
        register_rest_route(self::NAMESPACE, '/product-attributes', [
            'methods' => 'GET',
            'callback' => [self::class, 'get_product_attributes'],
            'permission_callback' => '__return_true'
        ]);
        
        // Cache management endpoint (admin only)
        register_rest_route(self::NAMESPACE, '/cache', [
            'methods' => 'DELETE',
            'callback' => [self::class, 'clear_cache'],
            'permission_callback' => [self::class, 'check_admin_permissions']
        ]);
        
        // System status endpoint (admin only)
        register_rest_route(self::NAMESPACE, '/status', [
            'methods' => 'GET',
            'callback' => [self::class, 'get_system_status'],
            'permission_callback' => [self::class, 'check_admin_permissions']
        ]);
    }
    
    /**
     * Get all products with THC/CBD data
     */
    public static function get_products($request) {
        $start_time = microtime(true);
        $user_id = $request->get_param('user_id') ?: get_current_user_id();
        $force_refresh = $request->get_param('refresh');
        
        // Try to get from cache first
        if (!$force_refresh) {
            $cached_data = Ample_Filter_Cache::get_products($user_id);
            if ($cached_data !== false) {
                $cached_data['meta']['source'] = 'cache';
                $cached_data['meta']['response_time'] = round((microtime(true) - $start_time) * 1000, 2);
                return new WP_REST_Response($cached_data, 200);
            }
        }
        
        // Build fresh product data
        $products = self::build_product_data($user_id);
        
        // Cache the result
        Ample_Filter_Cache::set_products($products, $user_id);
        
        $response_data = [
            'products' => $products,
            'meta' => [
                'total' => count($products),
                'cache_time' => current_time('c'),
                'user_id' => $user_id,
                'version' => Ample_Filter::VERSION,
                'source' => 'fresh',
                'response_time' => round((microtime(true) - $start_time) * 1000, 2)
            ]
        ];
        
        return new WP_REST_Response($response_data, 200);
    }
    
    /**
     * Build product data array optimized for filtering
     */
    private static function build_product_data($user_id = 0) {
        // Optimized query - get IDs first, then load data
        $query = new WP_Query([
            'post_type' => 'product',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'fields' => 'ids',
            'no_found_rows' => true,
            'update_post_meta_cache' => false,
            'update_post_term_cache' => false,
            'meta_query' => [
                [
                    'key' => '_stock_status',
                    'value' => 'instock',
                    'compare' => '='
                ]
            ]
        ]);
        
        $products = [];
        
        foreach ($query->posts as $product_id) {
            $product = wc_get_product($product_id);
            if (!$product || !$product->is_purchasable()) {
                continue;
            }
            
            // Extract THC/CBD values
            $thc_raw = $product->get_attribute('thc');
            $cbd_raw = $product->get_attribute('cbd');
            
            // Parse numeric values from attribute strings
            $thc_value = self::parse_numeric_attribute($thc_raw);
            $cbd_value = self::parse_numeric_attribute($cbd_raw);
            
            // Debug logging for first few products
            static $debug_count = 0;
            if ($debug_count < 10 && defined('WP_DEBUG') && WP_DEBUG) {
                error_log("AMPLE DEBUG Product #{$product_id}: '{$product->get_name()}' - THC raw: '{$thc_raw}' -> {$thc_value}, CBD raw: '{$cbd_raw}' -> {$cbd_value}");
                $debug_count++;
            }
            
            // Get product categories
            $categories = wp_get_post_terms($product_id, 'product_cat', ['fields' => 'slugs']);
            if (is_wp_error($categories)) {
                $categories = [];
            }
            
            // Get product brands using our custom function that reads from _product_attributes
            $brands = [];
            if (function_exists('get_product_brand')) {
                $brand = get_product_brand($product);
                if (!empty($brand) && $brand !== 'Brand') {
                    // Convert to slug using same logic as the filter dropdown
                    $brand_slug = sanitize_title($brand);
                    $brands = [$brand_slug];
                }
            }
            
            // Get product terpenes using the functions from healfio-child
            // This function properly checks product variations where terpene data is stored
            $terpenes = [];
            if (function_exists('get_single_product_terpenes_array')) {
                $terpenes_array = get_single_product_terpenes_array($product);
                if (!empty($terpenes_array)) {
                    // Convert to slug format for filter matching (e.g., "Beta Myrcene" -> "beta-myrcene")
                    $terpenes = array_map(function($terpene) {
                        return strtolower(str_replace(' ', '-', trim($terpene)));
                    }, $terpenes_array);
                }
            }
            
            // Get other attributes
            $attributes = [];
            $product_attributes = $product->get_attributes();
            foreach ($product_attributes as $attr_name => $attr_data) {
                if (in_array($attr_name, ['thc', 'cbd'])) {
                    continue; // Already processed
                }
                
                $attr_values = [];
                if ($attr_data->is_taxonomy()) {
                    $terms = wp_get_post_terms($product_id, $attr_data->get_name(), ['fields' => 'names']);
                    if (!is_wp_error($terms)) {
                        $attr_values = $terms;
                    }
                } else {
                    $attr_values = array_map('trim', explode('|', $attr_data->get_options()[0] ?? ''));
                }
                
                if (!empty($attr_values)) {
                    $attributes[str_replace('pa_', '', $attr_name)] = $attr_values;
                }
            }
            
            // Build product data optimized for client-side filtering
            $products[] = [
                'id' => $product_id,
                'title' => $product->get_name(),
                'slug' => $product->get_slug(),
                'price_html' => $product->get_price_html(),
                'regular_price' => (float) ($product->get_price() ?: 0),
                'sale_price' => (float) ($product->is_on_sale() ? ($product->get_sale_price() ?: $product->get_price()) : 0),
                'on_sale' => $product->is_on_sale(),
                'thc' => $thc_value,
                'cbd' => $cbd_value,
                'categories' => $categories,
                'brands' => $brands,
                'terpenes' => $terpenes,
                'attributes' => $attributes,
                'image' => [
                    'thumbnail' => get_the_post_thumbnail_url($product_id, 'thumbnail'),
                    'medium' => get_the_post_thumbnail_url($product_id, 'medium'),
                    'full' => get_the_post_thumbnail_url($product_id, 'full')
                ],
                'permalink' => get_permalink($product_id),
                'excerpt' => get_the_excerpt($product_id),
                'meta' => [
                    'featured' => $product->is_featured(),
                    'rating' => (float) $product->get_average_rating(),
                    'review_count' => $product->get_review_count(),
                    'stock_status' => $product->get_stock_status(),
                    'stock_quantity' => $product->get_stock_quantity(),
                    'sku' => $product->get_sku(),
                    'weight' => $product->get_weight(),
                    'dimensions' => [
                        'length' => $product->get_length(),
                        'width' => $product->get_width(),
                        'height' => $product->get_height()
                    ],
                    'total_sales' => (int) get_post_meta($product_id, 'total_sales', true)
                ]
            ];
        }
        
        return $products;
    }
    
    /**
     * Parse numeric value from product attribute string
     */
    private static function parse_numeric_attribute($attr_value) {
        if (empty($attr_value)) {
            return 0;
        }
        
        // Convert to string and lowercase for processing
        $value = strtolower(trim($attr_value));
        
        // Skip obvious non-numeric content
        if (strlen($value) > 20) return 0; // Extremely long strings
        if (strpos($value, '$') !== false) return 0; // Prices
        
        // Extract first number found (keep it simple)
        if (preg_match('/(\d+\.?\d*)/', $value, $matches)) {
            $numeric = (float) $matches[1];
            
            // Only filter out clearly impossible values
            if ($numeric < 0) return 0; // Negative values
            
            return $numeric;
        }
        
        return 0;
    }
    
    /**
     * Get filter terms (categories, brands, etc.)
     */
    public static function get_filter_terms($request) {
        $start_time = microtime(true);
        
        // Try cache first
        $cached_data = Ample_Filter_Cache::get_filter_terms();
        if ($cached_data !== false) {
            $cached_data['meta']['source'] = 'cache';
            $cached_data['meta']['response_time'] = round((microtime(true) - $start_time) * 1000, 2);
            return new WP_REST_Response($cached_data, 200);
        }
        
        // Build fresh terms data
        $terms = [
            'categories' => self::get_taxonomy_terms('product_cat'),
            'brands' => self::get_taxonomy_terms('product_brand'),
            'tags' => self::get_taxonomy_terms('product_tag')
        ];
        
        // Cache the result
        Ample_Filter_Cache::set_filter_terms($terms);
        
        $response_data = [
            'terms' => $terms,
            'meta' => [
                'cache_time' => current_time('c'),
                'version' => Ample_Filter::VERSION,
                'source' => 'fresh',
                'response_time' => round((microtime(true) - $start_time) * 1000, 2)
            ]
        ];
        
        return new WP_REST_Response($response_data, 200);
    }
    
    /**
     * Get terms for a specific taxonomy
     */
    private static function get_taxonomy_terms($taxonomy) {
        $terms = get_terms([
            'taxonomy' => $taxonomy,
            'hide_empty' => true,
            'orderby' => 'name',
            'order' => 'ASC'
        ]);
        
        if (is_wp_error($terms)) {
            return [];
        }
        
        $result = [];
        foreach ($terms as $term) {
            $result[] = [
                'id' => $term->term_id,
                'slug' => $term->slug,
                'name' => $term->name,
                'count' => $term->count,
                'parent' => $term->parent
            ];
        }
        
        return $result;
    }
    
    /**
     * Get product attributes summary (ranges, options)
     */
    public static function get_product_attributes($request) {
        $start_time = microtime(true);
        
        // Try cache first
        $cached_data = Ample_Filter_Cache::get_product_attributes();
        if ($cached_data !== false) {
            $cached_data['meta']['source'] = 'cache';
            $cached_data['meta']['response_time'] = round((microtime(true) - $start_time) * 1000, 2);
            return new WP_REST_Response($cached_data, 200);
        }
        
        // Build fresh attributes data
        $attributes = self::build_product_attributes();
        
        // Cache the result
        Ample_Filter_Cache::set_product_attributes($attributes);
        
        $response_data = [
            'attributes' => $attributes,
            'meta' => [
                'cache_time' => current_time('c'),
                'version' => Ample_Filter::VERSION,
                'source' => 'fresh',
                'response_time' => round((microtime(true) - $start_time) * 1000, 2)
            ]
        ];
        
        return new WP_REST_Response($response_data, 200);
    }
    
    /**
     * Build product attributes summary
     */
    private static function build_product_attributes() {
        // Get THC/CBD data from our product builder (more reliable)
        $products_data = self::build_product_data();
        
        $thc_values = [];
        $cbd_values = [];
        
        foreach ($products_data as $product) {
            if (isset($product['thc']) && $product['thc'] > 0) {
                $thc_values[] = (float) $product['thc'];
            }
            
            if (isset($product['cbd']) && $product['cbd'] > 0) {
                $cbd_values[] = (float) $product['cbd'];
            }
        }
        
        // Calculate ranges
        $attributes = [
            'thc' => [
                'min' => !empty($thc_values) ? min($thc_values) : 0,
                'max' => !empty($thc_values) ? max($thc_values) : 45,
                'average' => !empty($thc_values) ? array_sum($thc_values) / count($thc_values) : 0,
                'count' => count($thc_values)
            ],
            'cbd' => [
                'min' => !empty($cbd_values) ? min($cbd_values) : 0,
                'max' => !empty($cbd_values) ? max($cbd_values) : 30,
                'average' => !empty($cbd_values) ? array_sum($cbd_values) / count($cbd_values) : 0,
                'count' => count($cbd_values)
            ]
        ];
        
        // Get other attribute options
        $other_attributes = wc_get_attribute_taxonomies();
        foreach ($other_attributes as $attribute) {
            if (in_array($attribute->attribute_name, ['thc', 'cbd'])) {
                continue;
            }
            
            $taxonomy = wc_attribute_taxonomy_name($attribute->attribute_name);
            $terms = get_terms(['taxonomy' => $taxonomy, 'hide_empty' => true]);
            
            if (!is_wp_error($terms) && !empty($terms)) {
                $attributes[$attribute->attribute_name] = [
                    'type' => 'taxonomy',
                    'options' => array_map(function($term) {
                        return [
                            'slug' => $term->slug,
                            'name' => $term->name,
                            'count' => $term->count
                        ];
                    }, $terms)
                ];
            }
        }
        
        return $attributes;
    }
    
    /**
     * Clear all caches (admin only)
     */
    public static function clear_cache($request) {
        $cleared = Ample_Filter_Cache::clear_all_caches();
        
        return new WP_REST_Response([
            'success' => true,
            'message' => "Cleared {$cleared} cache entries",
            'timestamp' => current_time('c')
        ], 200);
    }
    
    /**
     * Get system status (admin only)
     */
    public static function get_system_status($request) {
        $status = Ample_Filter::get_system_status();
        return new WP_REST_Response($status, 200);
    }
    
    /**
     * Check admin permissions for protected endpoints
     */
    public static function check_admin_permissions() {
        return current_user_can('manage_options');
    }
}