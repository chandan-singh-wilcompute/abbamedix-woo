<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Filter products based on purchasable_products session data
 * OPTIMIZED: Uses pre-calculated product IDs from session
 */
function ample_filter_products_by_purchasable($query) {
    // Only filter on frontend shop/archive pages
    if (is_admin() || !$query->is_main_query()) {
        return;
    }

    // Only apply to product queries
    if (!is_shop() && !is_product_category() && !is_product_tag()) {
        return;
    }

    // Check if user is logged in
    if (!is_user_logged_in()) {
        return;
    }

    // OPTIMIZED: Use pre-calculated product IDs instead of recalculating
    $allowed_product_ids = ample_get_cached_purchasable_product_ids();
    
    if (!empty($allowed_product_ids)) {
        $query->set('post__in', $allowed_product_ids);
    } else {
        // No products available - hide all
        $query->set('post__in', [0]);
    }
}
// OPTIMIZED: Only register hook when on shop-related pages
add_action('wp', 'ample_conditional_product_filtering_hooks');

/**
 * Conditionally register product filtering hooks only when needed
 */
function ample_conditional_product_filtering_hooks() {
    // Only register product filtering hooks on shop-related pages
    if (is_shop() || is_product_category() || is_product_tag() || is_product_taxonomy()) {
        add_action('pre_get_posts', 'ample_filter_products_by_purchasable', 10);
    }
}

/**
 * Hide products from catalog that are not in purchasable list
 */
function ample_product_is_visible($visible, $product_id) {
    // Only filter for logged-in users
    if (!is_user_logged_in()) {
        return $visible;
    }
    
    // Don't filter on cart page - allow products in cart to be clickable
    if (is_cart()) {
        return $visible;
    }

    // Get purchasable products from session
    $purchasable_products = Ample_Session_Cache::get('purchasable_products');
    
    if (empty($purchasable_products) || !is_array($purchasable_products)) {
        return false;
    }

    // Get product SKU
    $product = wc_get_product($product_id);
    if (!$product) {
        return false;
    }

    $sku = $product->get_sku();
    
    // Check if this product is in purchasable list
    foreach ($purchasable_products as $purchasable) {
        if (isset($purchasable['id'])) {
            $purchasable_sku = 'sku-' . $purchasable['id'];
            if ($sku === $purchasable_sku) {
                return true;
            }
        }
    }

    return false;
}
add_filter('woocommerce_product_is_visible', 'ample_product_is_visible', 10, 2);

/**
 * Filter individual product variation based on purchasable SKUs
 */
function ample_filter_available_variation($variation_data, $product, $variation) {
    // Only filter for logged-in users
    if (!is_user_logged_in()) {
        return $variation_data;
    }

    // Get purchasable products from session
    $purchasable_products = Ample_Session_Cache::get('purchasable_products');
    
    if (empty($purchasable_products) || !is_array($purchasable_products)) {
        return $variation_data;
    }

    // Get parent product SKU
    $parent_sku = $product->get_sku();
    $parent_id = null;

    // Find the parent product in purchasable list
    foreach ($purchasable_products as $purchasable) {
        if (isset($purchasable['id'])) {
            $purchasable_sku = 'sku-' . $purchasable['id'];
            if ($parent_sku === $purchasable_sku) {
                $parent_id = $purchasable['id'];
                break;
            }
        }
    }

    if (!$parent_id) {
        $variation_data['is_purchasable'] = false;
        $variation_data['is_in_stock'] = false;
        return $variation_data;
    }

    // Get variation SKU
    $variation_sku = $variation->get_sku();
    
    // Check if this variation is in the purchasable SKUs list
    foreach ($purchasable_products as $purchasable) {
        if ($purchasable['id'] == $parent_id && isset($purchasable['skus'])) {
            foreach ($purchasable['skus'] as $sku_data) {
                // Variation SKU format: product_id-sku_id
                $expected_sku = $parent_id . '-' . $sku_data['id'];
                if ($variation_sku === $expected_sku) {
                    // Check if hidden
                    if (!empty($sku_data['hidden']) && $sku_data['hidden']) {
                        $variation_data['is_purchasable'] = false;
                        $variation_data['is_in_stock'] = false;
                        return $variation_data;
                    }
                    
                    // Override stock and inventory data
                    if (isset($sku_data['inventory_available'])) {
                        $variation_data['max_qty'] = min(
                            $sku_data['max_per_order'] ?? PHP_INT_MAX,
                            $sku_data['inventory_available']
                        );
                        $variation_data['is_in_stock'] = $sku_data['in_stock'] ?? true;
                        
                        // Let WooCommerce handle the display, just set the availability
                        if ($sku_data['in_stock']) {
                            $variation_data['availability'] = array(
                                'availability' => sprintf(_n('%s in stock', '%s in stock', $sku_data['inventory_available'], 'woocommerce'), $sku_data['inventory_available']),
                                'class' => 'in-stock'
                            );
                        } else {
                            $variation_data['availability'] = array(
                                'availability' => __('Out of stock', 'woocommerce'),
                                'class' => 'out-of-stock'
                            );
                        }
                    }
                    
                    // Add custom data for later use
                    $variation_data['ample_sku_data'] = $sku_data;
                    
                    return $variation_data;
                }
            }
        }
    }

    // If we get here, variation not found in purchasable list
    $variation_data['is_purchasable'] = false;
    $variation_data['is_in_stock'] = false;
    
    return $variation_data;
}
add_filter('woocommerce_available_variation', 'ample_filter_available_variation', 10, 3);

/**
 * Override product stock status based on purchasable data
 */
function ample_override_stock_status($status, $product) {
    // Don't override stock status in admin area
    if (is_admin()) {
        return $status;
    }
    
    // Only filter for logged-in users
    if (!is_user_logged_in()) {
        return $status;
    }

    // Get purchasable products from session
    $purchasable_products = Ample_Session_Cache::get('purchasable_products');
    
    if (empty($purchasable_products) || !is_array($purchasable_products)) {
        return 'outofstock';
    }

    $sku = $product->get_sku();
    
    // For simple products
    if ($product->is_type('simple')) {
        foreach ($purchasable_products as $purchasable) {
            if (isset($purchasable['id'])) {
                $purchasable_sku = 'sku-' . $purchasable['id'];
                if ($sku === $purchasable_sku && isset($purchasable['skus'][0])) {
                    return $purchasable['skus'][0]['in_stock'] ? 'instock' : 'outofstock';
                }
            }
        }
    }
    
    // For variations
    if ($product->is_type('variation')) {
        $parent = wc_get_product($product->get_parent_id());
        $parent_sku = $parent->get_sku();
        
        foreach ($purchasable_products as $purchasable) {
            if (isset($purchasable['id'])) {
                $purchasable_sku = 'sku-' . $purchasable['id'];
                if ($parent_sku === $purchasable_sku && isset($purchasable['skus'])) {
                    foreach ($purchasable['skus'] as $sku_data) {
                        $expected_sku = $purchasable['id'] . '-' . $sku_data['id'];
                        if ($sku === $expected_sku) {
                            return $sku_data['in_stock'] ? 'instock' : 'outofstock';
                        }
                    }
                }
            }
        }
    }

    return $status;
}
add_filter('woocommerce_product_get_stock_status', 'ample_override_stock_status', 10, 2);
add_filter('woocommerce_product_variation_get_stock_status', 'ample_override_stock_status', 10, 2);

/**
 * Override product stock quantity based on purchasable data
 */
function ample_override_stock_quantity($quantity, $product) {
    // Don't override stock quantity in admin area
    if (is_admin()) {
        return $quantity;
    }
    
    // Only filter for logged-in users
    if (!is_user_logged_in()) {
        return $quantity;
    }

    // Get purchasable products from session
    $purchasable_products = Ample_Session_Cache::get('purchasable_products');
    
    if (empty($purchasable_products) || !is_array($purchasable_products)) {
        return 0;
    }

    $sku = $product->get_sku();
    
    // For simple products
    if ($product->is_type('simple')) {
        foreach ($purchasable_products as $purchasable) {
            if (isset($purchasable['id'])) {
                $purchasable_sku = 'sku-' . $purchasable['id'];
                if ($sku === $purchasable_sku && isset($purchasable['skus'][0])) {
                    return intval($purchasable['skus'][0]['inventory_available'] ?? 0);
                }
            }
        }
    }
    
    // For variations
    if ($product->is_type('variation')) {
        $parent = wc_get_product($product->get_parent_id());
        $parent_sku = $parent->get_sku();
        
        foreach ($purchasable_products as $purchasable) {
            if (isset($purchasable['id'])) {
                $purchasable_sku = 'sku-' . $purchasable['id'];
                if ($parent_sku === $purchasable_sku && isset($purchasable['skus'])) {
                    foreach ($purchasable['skus'] as $sku_data) {
                        $expected_sku = $purchasable['id'] . '-' . $sku_data['id'];
                        if ($sku === $expected_sku) {
                            return intval($sku_data['inventory_available'] ?? 0);
                        }
                    }
                }
            }
        }
    }

    return $quantity;
}
add_filter('woocommerce_product_get_stock_quantity', 'ample_override_stock_quantity', 10, 2);
add_filter('woocommerce_product_variation_get_stock_quantity', 'ample_override_stock_quantity', 10, 2);

/**
 * Ensure product manages stock when we have API inventory data
 */
function ample_manage_stock($manages_stock, $product) {
    // Don't override stock management in admin area
    if (is_admin()) {
        return $manages_stock;
    }
    
    // Only filter for logged-in users
    if (!is_user_logged_in()) {
        return $manages_stock;
    }

    // Get purchasable products from session
    $purchasable_products = Ample_Session_Cache::get('purchasable_products');
    
    if (empty($purchasable_products) || !is_array($purchasable_products)) {
        return $manages_stock;
    }

    $sku = $product->get_sku();
    
    // For simple products
    if ($product->is_type('simple')) {
        foreach ($purchasable_products as $purchasable) {
            if (isset($purchasable['id'])) {
                $purchasable_sku = 'sku-' . $purchasable['id'];
                if ($sku === $purchasable_sku && isset($purchasable['skus'][0]['inventory_available'])) {
                    return true; // Force stock management for API products
                }
            }
        }
    }
    
    // For variations
    if ($product->is_type('variation')) {
        $parent = wc_get_product($product->get_parent_id());
        $parent_sku = $parent->get_sku();
        
        foreach ($purchasable_products as $purchasable) {
            if (isset($purchasable['id'])) {
                $purchasable_sku = 'sku-' . $purchasable['id'];
                if ($parent_sku === $purchasable_sku && isset($purchasable['skus'])) {
                    foreach ($purchasable['skus'] as $sku_data) {
                        $expected_sku = $purchasable['id'] . '-' . $sku_data['id'];
                        if ($sku === $expected_sku && isset($sku_data['inventory_available'])) {
                            return true; // Force stock management for API products
                        }
                    }
                }
            }
        }
    }

    return $manages_stock;
}
add_filter('woocommerce_product_get_manage_stock', 'ample_manage_stock', 10, 2);
add_filter('woocommerce_product_variation_get_manage_stock', 'ample_manage_stock', 10, 2);

/**
 * Set maximum quantity per order based on purchasable data
 */
function ample_set_max_purchase_quantity($args, $product) {
    // Only filter for logged-in users
    if (!is_user_logged_in()) {
        return $args;
    }

    // Get purchasable products from session
    $purchasable_products = Ample_Session_Cache::get('purchasable_products');
    
    if (empty($purchasable_products) || !is_array($purchasable_products)) {
        $args['max_value'] = 0;
        return $args;
    }

    $sku = $product->get_sku();
    
    // For simple products
    if ($product->is_type('simple')) {
        foreach ($purchasable_products as $purchasable) {
            if (isset($purchasable['id'])) {
                $purchasable_sku = 'sku-' . $purchasable['id'];
                if ($sku === $purchasable_sku && isset($purchasable['skus'][0])) {
                    $max_qty = min(
                        $purchasable['skus'][0]['max_per_order'] ?? PHP_INT_MAX,
                        $purchasable['skus'][0]['inventory_available'] ?? PHP_INT_MAX
                    );
                    $args['max_value'] = $max_qty;
                    return $args;
                }
            }
        }
    }
    
    // For variations
    if ($product->is_type('variation')) {
        $parent = wc_get_product($product->get_parent_id());
        $parent_sku = $parent->get_sku();
        
        foreach ($purchasable_products as $purchasable) {
            if (isset($purchasable['id'])) {
                $purchasable_sku = 'sku-' . $purchasable['id'];
                if ($parent_sku === $purchasable_sku && isset($purchasable['skus'])) {
                    foreach ($purchasable['skus'] as $sku_data) {
                        $expected_sku = $purchasable['id'] . '-' . $sku_data['id'];
                        if ($sku === $expected_sku) {
                            $max_qty = min(
                                $sku_data['max_per_order'] ?? PHP_INT_MAX,
                                $sku_data['inventory_available'] ?? PHP_INT_MAX
                            );
                            $args['max_value'] = $max_qty;
                            return $args;
                        }
                    }
                }
            }
        }
    }

    return $args;
}
add_filter('woocommerce_quantity_input_args', 'ample_set_max_purchase_quantity', 10, 2);

/**
 * Validate cart quantities against purchasable limits
 */
function ample_validate_cart_quantities() {
    // Only validate for logged-in users
    if (!is_user_logged_in()) {
        return;
    }

    // Get purchasable products from session
    $purchasable_products = Ample_Session_Cache::get('purchasable_products');
    
    if (empty($purchasable_products) || !is_array($purchasable_products)) {
        return;
    }

    // Create a lookup array for quick access
    $sku_limits = [];
    foreach ($purchasable_products as $purchasable) {
        if (isset($purchasable['id']) && isset($purchasable['skus'])) {
            $parent_sku = 'sku-' . $purchasable['id'];
            foreach ($purchasable['skus'] as $sku_data) {
                $variation_sku = $purchasable['id'] . '-' . $sku_data['id'];
                $sku_limits[$parent_sku] = [
                    'inventory' => $sku_data['inventory_available'] ?? 0,
                    'max_per_order' => $sku_data['max_per_order'] ?? PHP_INT_MAX,
                    'in_stock' => $sku_data['in_stock'] ?? false
                ];
                $sku_limits[$variation_sku] = [
                    'inventory' => $sku_data['inventory_available'] ?? 0,
                    'max_per_order' => $sku_data['max_per_order'] ?? PHP_INT_MAX,
                    'in_stock' => $sku_data['in_stock'] ?? false
                ];
            }
        }
    }

    // Check each cart item
    foreach (WC()->cart->get_cart() as $cart_item_key => $cart_item) {
        $product = $cart_item['data'];
        $quantity = $cart_item['quantity'];
        $sku = $product->get_sku();
        
        if (isset($sku_limits[$sku])) {
            $limits = $sku_limits[$sku];
            
            // Check if product is in stock
            if (!$limits['in_stock']) {
                wc_add_notice(
                    sprintf(__('%s is out of stock and has been removed from your cart.', 'woocommerce'), $product->get_name()),
                    'error'
                );
                WC()->cart->remove_cart_item($cart_item_key);
                continue;
            }
            
            // Check max per order limit
            if ($quantity > $limits['max_per_order']) {
                WC()->cart->set_quantity($cart_item_key, $limits['max_per_order']);
                wc_add_notice(
                    sprintf(
                        __('The quantity of %s has been adjusted to the maximum allowed (%d) per order.', 'woocommerce'),
                        $product->get_name(),
                        $limits['max_per_order']
                    ),
                    'notice'
                );
            }
            
            // Check inventory limit
            if ($quantity > $limits['inventory']) {
                WC()->cart->set_quantity($cart_item_key, $limits['inventory']);
                wc_add_notice(
                    sprintf(
                        __('The quantity of %s has been adjusted to available stock (%d).', 'woocommerce'),
                        $product->get_name(),
                        $limits['inventory']
                    ),
                    'notice'
                );
            }
        }
    }
}
add_action('woocommerce_check_cart_items', 'ample_validate_cart_quantities');

/**
 * Helper function to get pre-calculated purchasable product IDs from session
 * This is much faster than calculating them each time
 */
function ample_get_cached_purchasable_product_ids() {
    // Only for logged-in users
    if (!is_user_logged_in()) {
        return [];
    }

    // Get pre-calculated product IDs from session
    $product_ids = Ample_Session_Cache::get('purchasable_product_ids');
    
    if (empty($product_ids) || !is_array($product_ids)) {
        // Try to calculate from purchasable_products if available
        $product_ids = ample_calculate_and_cache_product_ids();
        if (empty($product_ids)) {
            return [0]; // Return [0] to show no products
        }
    }

    return $product_ids;
}

/**
 * Calculate and cache product IDs from purchasable products session data
 * OPTIMIZED: Uses wc_get_product_id_by_sku for faster lookups
 */
function ample_calculate_and_cache_product_ids() {
    $purchasable_products = Ample_Session_Cache::get('purchasable_products');
    
    if (empty($purchasable_products) || !is_array($purchasable_products)) {
        return [];
    }

    $product_ids = [];
    
    foreach ($purchasable_products as $purchasable) {
        if (isset($purchasable['id'])) {
            $sku = 'sku-' . $purchasable['id'];
            
            // OPTIMIZED: Use WooCommerce's built-in function
            $product_id = wc_get_product_id_by_sku($sku);
            if ($product_id) {
                $product_ids[] = $product_id;
            }
        }
    }
    
    // Cache the calculated IDs
    if (!empty($product_ids)) {
        Ample_Session_Cache::set('purchasable_product_ids', $product_ids);
    }
    
    return $product_ids;
}

/**
 * Enhanced query args helper that includes purchasable filter upfront
 * Uses pre-calculated product IDs for maximum performance
 */
function ample_get_filtered_product_query_args($base_args = []) {
    // Get pre-calculated purchasable product IDs
    $purchasable_ids = ample_get_cached_purchasable_product_ids();
    
    // Merge with base args
    $args = array_merge([
        'post_type' => 'product',
        'post_status' => 'publish',
    ], $base_args);
    
    // Add purchasable filter
    if (!empty($purchasable_ids)) {
        $args['post__in'] = $purchasable_ids;
    }
    
    return $args;
}

