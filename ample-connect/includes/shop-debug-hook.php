<?php
/**
 * Shop Debug Hook - Logs all product data when shop/products page loads
 * For development debugging purposes
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Debug all products when shop page loads
 */
function debug_shop_products_on_load() {
    // Only run on product archive pages (shop, product category, etc.)
    if (!is_shop() && !is_product_category() && !is_product_tag() && !is_post_type_archive('product')) {
        return;
    }

    // Get all products
    $args = array(
        'post_type' => 'product',
        'posts_per_page' => -1, // Get all products
        'post_status' => 'publish'
    );

    $products = get_posts($args);
    
    ample_connect_log("=== SHOP PAGE LOADED - DEBUGGING ALL PRODUCTS ===");
    ample_connect_log("Total products found: " . count($products));
    ample_connect_log("Current page: " . $_SERVER['REQUEST_URI']);
    ample_connect_log("User agent: " . $_SERVER['HTTP_USER_AGENT']);
    
    foreach ($products as $product_post) {
        $product = wc_get_product($product_post->ID);
        
        if (!$product) {
            ample_connect_log("ERROR: Could not load WooCommerce product for post ID: " . $product_post->ID);
            continue;
        }

        $product_data = array(
            'basic_info' => array(
                'id' => $product->get_id(),
                'name' => $product->get_name(),
                'slug' => $product->get_slug(),
                'sku' => $product->get_sku(),
                'type' => $product->get_type(),
                'status' => $product->get_status(),
                'permalink' => get_permalink($product->get_id())
            ),
            'pricing' => array(
                'regular_price' => $product->get_regular_price(),
                'sale_price' => $product->get_sale_price(),
                'price' => $product->get_price(),
                'price_html' => $product->get_price_html(),
                'on_sale' => $product->is_on_sale()
            ),
            'inventory' => array(
                'manage_stock' => $product->get_manage_stock(),
                'stock_quantity' => $product->get_stock_quantity(),
                'in_stock' => $product->is_in_stock(),
                'backorders_allowed' => $product->backorders_allowed(),
                'purchasable' => $product->is_purchasable()
            ),
            'content' => array(
                'description' => $product->get_description(),
                'short_description' => $product->get_short_description(),
                'featured' => $product->is_featured(),
                'virtual' => $product->is_virtual(),
                'downloadable' => $product->is_downloadable()
            ),
            'meta' => array(
                'date_created' => $product->get_date_created() ? $product->get_date_created()->date('Y-m-d H:i:s') : null,
                'date_modified' => $product->get_date_modified() ? $product->get_date_modified()->date('Y-m-d H:i:s') : null,
                'total_sales' => $product->get_total_sales(),
                'rating_count' => $product->get_rating_count(),
                'average_rating' => $product->get_average_rating()
            )
        );

        // Get product categories
        $categories = wp_get_post_terms($product->get_id(), 'product_cat');
        $product_data['categories'] = array_map(function($cat) {
            return $cat->name;
        }, $categories);

        // Get product tags  
        $tags = wp_get_post_terms($product->get_id(), 'product_tag');
        $product_data['tags'] = array_map(function($tag) {
            return $tag->name;
        }, $tags);

        // Log the complete product data
        ample_connect_log("--- PRODUCT DEBUG: " . $product->get_name() . " (ID: " . $product->get_id() . ") ---");
        ample_connect_log(json_encode($product_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }
    
    ample_connect_log("=== END SHOP PRODUCTS DEBUG ===");
}

// Hook into template_redirect to run early in the page load
// add_action('template_redirect', 'debug_shop_products_on_load');

/**
 * Alternative: Debug products via AJAX for real-time debugging
 */
function debug_products_ajax_endpoint() {
    if (!current_user_can('manage_options')) {
        wp_die('Unauthorized');
    }
    
    debug_shop_products_on_load();
    
    wp_die('Product debug logged to ample-log.log');
}
// add_action('wp_ajax_debug_products', 'debug_products_ajax_endpoint');

/**
 * Add debug button to admin bar for easy triggering
 */
function add_debug_products_admin_bar($wp_admin_bar) {
    if (!current_user_can('manage_options') || !is_admin_bar_showing()) {
        return;
    }
    
    $wp_admin_bar->add_node(array(
        'id' => 'debug_products',
        'title' => 'Debug Products',
        'href' => wp_nonce_url(admin_url('admin-ajax.php?action=debug_products'), 'debug_products'),
        'meta' => array(
            'target' => '_blank'
        )
    ));
}
// add_action('admin_bar_menu', 'add_debug_products_admin_bar', 100);