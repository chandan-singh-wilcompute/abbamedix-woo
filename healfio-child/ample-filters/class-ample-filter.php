<?php
/**
 * Ample Filter System - Main Filter Class
 * 
 * Handles THC/CBD range filtering with client-side performance
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

class Ample_Filter {
    
    /**
     * Version for cache busting and compatibility
     */
    const VERSION = '1.0.0';
    
    /**
     * Feature flags for gradual rollout
     */
    const FEATURE_THC_CBD_ENABLED = true;
    const FEATURE_BRANDS_ENABLED = false;
    const FEATURE_SORTING_ENABLED = false;
    const FEATURE_LEGACY_MODE = true;
    
    /**
     * Cache configuration
     */
    const CACHE_TTL = 900; // 15 minutes
    const CACHE_PREFIX = 'ample_filter_';
    
    /**
     * Initialize the filter system
     */
    public static function init() {
        add_action('wp_enqueue_scripts', [self::class, 'enqueue_assets']);
        add_action('wp_footer', [self::class, 'add_filter_config']);
        
        // Initialize REST API endpoints
        Ample_Filter_Endpoints::init();
        
        // Initialize caching system
        Ample_Filter_Cache::init();
        
        // Add shortcodes
        add_shortcode('ample_thc_cbd_filter', [self::class, 'render_thc_cbd_filter']);
        
        // Development helpers
        if (defined('WP_DEBUG') && WP_DEBUG) {
            add_action('wp_footer', [self::class, 'debug_info']);
        }
    }
    
    /**
     * Enqueue CSS and JavaScript assets
     */
    public static function enqueue_assets() {
        // Only load on product filter page
        if (!is_page('product-filter') && !self::should_load_assets()) {
            return;
        }
        
        $child_theme_uri = get_stylesheet_directory_uri();
        $version = self::VERSION . '.' . filemtime(get_stylesheet_directory() . '/css/ample-filters.css');
        
        // CSS
        wp_enqueue_style(
            'ample-filters',
            $child_theme_uri . '/css/ample-filters.css',
            [],
            $version
        );
        
        // JavaScript - load in footer for performance
        wp_enqueue_script(
            'ample-filter-engine',
            $child_theme_uri . '/js/ample-filter-engine.js',
            ['jquery'],
            $version,
            true
        );
        
        wp_enqueue_script(
            'ample-sliders',
            $child_theme_uri . '/js/ample-sliders.js',
            ['ample-filter-engine'],
            $version,
            true
        );
        
        wp_enqueue_script(
            'ample-url-manager',
            $child_theme_uri . '/js/ample-url-manager.js',
            ['ample-filter-engine'],
            $version,
            true
        );
    }
    
    /**
     * Add JavaScript configuration to footer
     */
    public static function add_filter_config() {
        if (!is_page('product-filter') && !self::should_load_assets()) {
            return;
        }
        
        $config = [
            'ajax_url' => admin_url('admin-ajax.php'),
            'rest_url' => rest_url('ample/v1/'),
            'nonce' => wp_create_nonce('ample_filter_nonce'),
            'version' => self::VERSION,
            'features' => [
                'thc_cbd' => self::FEATURE_THC_CBD_ENABLED,
                'brands' => self::FEATURE_BRANDS_ENABLED,
                'sorting' => self::FEATURE_SORTING_ENABLED,
                'legacy_mode' => self::FEATURE_LEGACY_MODE
            ],
            'performance' => [
                'debounce_ms' => 100,
                'cache_ttl' => self::CACHE_TTL
            ],
            'debug' => defined('WP_DEBUG') && WP_DEBUG
        ];
        
        echo "<script>window.AmpleFilterConfig = " . wp_json_encode($config) . ";</script>\n";
    }
    
    /**
     * Render THC/CBD filter shortcode
     */
    public static function render_thc_cbd_filter($atts = []) {
        $atts = shortcode_atts([
            'show_thc' => true,
            'show_cbd' => true,
            'thc_min' => 0,
            'thc_max' => 45,
            'cbd_min' => 0,
            'cbd_max' => 30,
            'class' => ''
        ], $atts);
        
        ob_start();
        include get_stylesheet_directory() . '/ample-filters/templates/thc-cbd-filter.php';
        return ob_get_clean();
    }
    
    /**
     * Check if we should load filter assets on this page
     */
    private static function should_load_assets() {
        // Load on product-filter page, shop page, product category pages
        return is_page('product-filter') || is_shop() || is_product_category() || is_product_tag();
    }
    
    /**
     * Debug information for development
     */
    public static function debug_info() {
        if (!current_user_can('manage_options')) {
            return;
        }
        
        echo "<!-- Ample Filter Debug Info -->\n";
        echo "<!-- Version: " . self::VERSION . " -->\n";
        echo "<!-- Cache TTL: " . self::CACHE_TTL . "s -->\n";
        echo "<!-- Features: THC/CBD=" . (self::FEATURE_THC_CBD_ENABLED ? 'ON' : 'OFF') . " -->\n";
        echo "<!-- Page Type: " . (is_page('product-filter') ? 'filter-page' : 'other') . " -->\n";
        echo "<!-- Assets Loaded: " . (self::should_load_assets() ? 'YES' : 'NO') . " -->\n";
    }
    
    /**
     * Get filter system status for troubleshooting
     */
    public static function get_system_status() {
        return [
            'version' => self::VERSION,
            'features_enabled' => [
                'thc_cbd' => self::FEATURE_THC_CBD_ENABLED,
                'brands' => self::FEATURE_BRANDS_ENABLED,
                'sorting' => self::FEATURE_SORTING_ENABLED,
                'legacy_mode' => self::FEATURE_LEGACY_MODE
            ],
            'cache_status' => Ample_Filter_Cache::get_status(),
            'assets_loaded' => self::should_load_assets(),
            'php_version' => PHP_VERSION,
            'wp_version' => get_bloginfo('version')
        ];
    }
}