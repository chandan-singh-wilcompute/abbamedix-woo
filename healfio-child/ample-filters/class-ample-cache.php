<?php
/**
 * Ample Filter System - Caching Class
 * 
 * High-performance caching for product data with smart invalidation
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

class Ample_Filter_Cache {
    
    /**
     * Cache key prefixes for organization
     */
    const PRODUCTS_KEY = 'ample_products_v1_';
    const TERMS_KEY = 'ample_filter_terms_v1';
    const ATTRIBUTES_KEY = 'ample_product_attributes_v1';
    
    /**
     * Cache TTL (15 minutes)
     */
    const CACHE_TTL = 900;
    
    /**
     * Initialize caching system
     */
    public static function init() {
        // Hook into product updates to invalidate cache
        add_action('save_post_product', [self::class, 'invalidate_product_cache']);
        add_action('woocommerce_product_set_stock_status', [self::class, 'invalidate_product_cache']);
        add_action('woocommerce_variation_set_stock_status', [self::class, 'invalidate_product_cache']);
        add_action('created_product_cat', [self::class, 'invalidate_terms_cache']);
        add_action('edited_product_cat', [self::class, 'invalidate_terms_cache']);
        add_action('created_product_brand', [self::class, 'invalidate_terms_cache']);
        add_action('edited_product_brand', [self::class, 'invalidate_terms_cache']);
        
        // Hook into comment/review changes to invalidate cache (CRITICAL FOR RATING UPDATES)
        add_action('comment_post', [self::class, 'invalidate_product_cache_on_comment']);
        add_action('wp_set_comment_status', [self::class, 'invalidate_product_cache_on_comment']);
        add_action('edit_comment', [self::class, 'invalidate_product_cache_on_comment']);
        add_action('delete_comment', [self::class, 'invalidate_product_cache_on_comment']);
        
        // Admin cache management
        if (is_admin()) {
            add_action('wp_ajax_ample_clear_cache', [self::class, 'ajax_clear_cache']);
        }
        
        // Performance monitoring
        if (defined('WP_DEBUG') && WP_DEBUG) {
            add_action('shutdown', [self::class, 'log_cache_stats']);
        }
    }
    
    /**
     * Get cached product data for current user
     */
    public static function get_products($user_id = null) {
        if ($user_id === null) {
            $user_id = get_current_user_id();
        }
        
        $cache_key = self::PRODUCTS_KEY . md5($user_id . '_' . get_locale());
        $start_time = microtime(true);
        
        $cached_data = get_transient($cache_key);
        
        // Log cache performance
        if (defined('WP_DEBUG') && WP_DEBUG) {
            $hit = ($cached_data !== false);
            self::log_cache_access('products', $hit, microtime(true) - $start_time);
        }
        
        return $cached_data;
    }
    
    /**
     * Set cached product data for current user
     */
    public static function set_products($products, $user_id = null) {
        if ($user_id === null) {
            $user_id = get_current_user_id();
        }
        
        $cache_key = self::PRODUCTS_KEY . md5($user_id . '_' . get_locale());
        
        // Add metadata to cached data
        $cache_data = [
            'products' => $products,
            'meta' => [
                'total' => count($products),
                'cache_time' => current_time('c'),
                'user_id' => $user_id,
                'version' => Ample_Filter::VERSION,
                'ttl' => self::CACHE_TTL
            ]
        ];
        
        $result = set_transient($cache_key, $cache_data, self::CACHE_TTL);
        
        // Log cache set
        if (defined('WP_DEBUG') && WP_DEBUG) {
            self::log_cache_set('products', count($products), $result);
        }
        
        return $result;
    }
    
    /**
     * Get cached filter terms (categories, brands, etc.)
     */
    public static function get_filter_terms() {
        $start_time = microtime(true);
        $cached_data = get_transient(self::TERMS_KEY);
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            $hit = ($cached_data !== false);
            self::log_cache_access('terms', $hit, microtime(true) - $start_time);
        }
        
        return $cached_data;
    }
    
    /**
     * Set cached filter terms
     */
    public static function set_filter_terms($terms) {
        $cache_data = [
            'terms' => $terms,
            'meta' => [
                'cache_time' => current_time('c'),
                'version' => Ample_Filter::VERSION
            ]
        ];
        
        $result = set_transient(self::TERMS_KEY, $cache_data, self::CACHE_TTL);
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            self::log_cache_set('terms', count($terms), $result);
        }
        
        return $result;
    }
    
    /**
     * Get cached product attributes (THC/CBD ranges, terpenes, etc.)
     */
    public static function get_product_attributes() {
        $start_time = microtime(true);
        $cached_data = get_transient(self::ATTRIBUTES_KEY);
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            $hit = ($cached_data !== false);
            self::log_cache_access('attributes', $hit, microtime(true) - $start_time);
        }
        
        return $cached_data;
    }
    
    /**
     * Set cached product attributes
     */
    public static function set_product_attributes($attributes) {
        $cache_data = [
            'attributes' => $attributes,
            'meta' => [
                'cache_time' => current_time('c'),
                'version' => Ample_Filter::VERSION
            ]
        ];
        
        $result = set_transient(self::ATTRIBUTES_KEY, $cache_data, self::CACHE_TTL);
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            self::log_cache_set('attributes', count($attributes), $result);
        }
        
        return $result;
    }
    
    /**
     * Invalidate product cache when products are updated
     */
    public static function invalidate_product_cache($post_id = null) {
        global $wpdb;
        
        // Clear all product caches (for all users)
        $wpdb->query(
            "DELETE FROM {$wpdb->options} 
             WHERE option_name LIKE '_transient_" . self::PRODUCTS_KEY . "%'"
        );
        
        // Also clear the timeout entries
        $wpdb->query(
            "DELETE FROM {$wpdb->options} 
             WHERE option_name LIKE '_transient_timeout_" . self::PRODUCTS_KEY . "%'"
        );
        
        // Clear attributes cache as THC/CBD values might have changed
        delete_transient(self::ATTRIBUTES_KEY);
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("AMPLE: Invalidated product cache for post_id: " . $post_id);
        }
    }
    
    /**
     * Invalidate terms cache when categories/brands are updated
     */
    public static function invalidate_terms_cache($term_id = null) {
        delete_transient(self::TERMS_KEY);
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("AMPLE: Invalidated terms cache for term_id: " . $term_id);
        }
    }
    
    /**
     * Clear all Ample filter caches
     */
    public static function clear_all_caches() {
        global $wpdb;
        
        $count = $wpdb->query(
            "DELETE FROM {$wpdb->options} 
             WHERE option_name LIKE '_transient_ample_%' 
             OR option_name LIKE '_transient_timeout_ample_%'"
        );
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("AMPLE: Cleared {$count} cache entries");
        }
        
        return $count;
    }
    
    /**
     * Get cache status and statistics
     */
    public static function get_status() {
        global $wpdb;
        
        $cache_count = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->options} 
             WHERE option_name LIKE '_transient_ample_%'"
        );
        
        return [
            'total_entries' => (int) $cache_count,
            'ttl_seconds' => self::CACHE_TTL,
            'products_cached' => (get_transient(self::PRODUCTS_KEY . md5(get_current_user_id() . '_' . get_locale())) !== false),
            'terms_cached' => (get_transient(self::TERMS_KEY) !== false),
            'attributes_cached' => (get_transient(self::ATTRIBUTES_KEY) !== false),
            'last_clear' => get_option('ample_cache_last_clear', 'never')
        ];
    }
    
    /**
     * AJAX handler to clear cache from admin
     */
    public static function ajax_clear_cache() {
        // Verify nonce and permissions
        if (!check_ajax_referer('ample_filter_nonce', 'nonce', false)) {
            wp_die('Security check failed');
        }
        
        if (!current_user_can('manage_options')) {
            wp_die('Insufficient permissions');
        }
        
        $cleared = self::clear_all_caches();
        update_option('ample_cache_last_clear', current_time('c'));
        
        wp_send_json_success([
            'message' => "Cleared {$cleared} cache entries",
            'timestamp' => current_time('c')
        ]);
    }
    
    /**
     * Log cache access for debugging
     */
    private static function log_cache_access($type, $hit, $time_ms) {
        $status = $hit ? 'HIT' : 'MISS';
        error_log("AMPLE Cache {$type}: {$status} in " . round($time_ms * 1000, 2) . "ms");
    }
    
    /**
     * Log cache set operation
     */
    private static function log_cache_set($type, $count, $success) {
        $status = $success ? 'SUCCESS' : 'FAILED';
        error_log("AMPLE Cache SET {$type}: {$status} ({$count} items)");
    }
    
    /**
     * Log cache statistics on page shutdown
     */
    public static function log_cache_stats() {
        if (!defined('WP_DEBUG') || !WP_DEBUG) {
            return;
        }
        
        $status = self::get_status();
        error_log("AMPLE Cache Stats: {$status['total_entries']} total entries");
    }
    
    /**
     * Invalidate product cache when comments/reviews change
     * This ensures rating data stays fresh when customers add reviews
     */
    public static function invalidate_product_cache_on_comment($comment_id) {
        // Get comment details to check if it's a product review
        $comment = get_comment($comment_id);
        
        if (!$comment) {
            return;
        }
        
        // Check if comment is on a product post type
        $post = get_post($comment->comment_post_ID);
        if (!$post || $post->post_type !== 'product') {
            return;
        }
        
        // This is a product review - invalidate all product caches
        self::invalidate_product_cache();
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("AMPLE Cache: Invalidated product cache due to review change on product #{$comment->comment_post_ID}");
        }
    }
}