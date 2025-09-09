<?php
// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

// ==========================================================================
// AMPLE FILTER SYSTEM - INITIALIZATION
// Phase 1: THC/CBD Range Filters (ABBA Issue #8)
// ==========================================================================

// Load Ample Filter System
function ample_filter_system_init() {
    // Define feature flags
    if (!defined('AMPLE_FILTER_THC_CBD_ENABLED')) {
        define('AMPLE_FILTER_THC_CBD_ENABLED', true);
    }
    if (!defined('AMPLE_FILTER_BRANDS_ENABLED')) {
        define('AMPLE_FILTER_BRANDS_ENABLED', false); // Phase 2
    }
    if (!defined('AMPLE_FILTER_SORTING_ENABLED')) {
        define('AMPLE_FILTER_SORTING_ENABLED', false); // Phase 3
    }
    if (!defined('AMPLE_FILTER_LEGACY_MODE')) {
        define('AMPLE_FILTER_LEGACY_MODE', true); // Fallback support
    }
    
    // Load Ample Filter classes
    $ample_filter_path = get_stylesheet_directory() . '/ample-filters/';
    
    if (file_exists($ample_filter_path)) {
        require_once $ample_filter_path . 'class-ample-cache.php';
        require_once $ample_filter_path . 'class-ample-slider.php';
        require_once $ample_filter_path . 'class-ample-dual-range-slider.php';
        require_once $ample_filter_path . 'endpoints.php';
        require_once $ample_filter_path . 'class-ample-filter.php';
        
        // Initialize the system
        if (class_exists('Ample_Filter')) {
            Ample_Filter::init();
        }
    }
}

/**
 * Enqueue all Ample Filter System assets
 * 
 * This function loads all JavaScript and CSS files for the complete Ample product filtering system.
 * Originally created for dual-range THC/CBD sliders but has evolved to handle all filter types.
 * 
 * Filter Components Included:
 * - Dual-range sliders (THC/CBD with visual handles)
 * - Multi-select filters (Size, Dominance, Terpenes, Brands, Categories)
 * - Filter engine (product matching and display logic)  
 * - URL parameter management (browser history and bookmarkable URLs)
 * - Button indicators (visual feedback for active filters)
 * - Product display helpers (grid updates and animations)
 * 
 * Dependencies:
 * - jQuery (WordPress core)
 * - All scripts properly ordered with dependency chain
 * - REST API endpoints for filter data
 * 
 * @since ABBA Issue #8 (dual-range implementation)
 * @updated ABBA Issue #10 (size/dominance filters)  
 * @updated ABBA Issue #11 (terpenes filter)
 * @updated ABBA Issue #12 (brands/categories filters)
 * 
 * @return void
 */
add_action('wp_enqueue_scripts', 'ample_enqueue_filter_system_assets');

function ample_enqueue_filter_system_assets() {
    // Dual-range slider assets
    wp_enqueue_script(
        'ample-dual-range-slider-manual',
        get_stylesheet_directory_uri() . '/ample-filters/dual-range-slider.js',
        ['jquery'],
        '1.0.0',
        true
    );
    
    wp_enqueue_style(
        'ample-dual-range-slider-manual',
        get_stylesheet_directory_uri() . '/ample-filters/dual-range-slider.css',
        [],
        '1.0.0'
    );
    
    // Filter engine (already has product loading and filtering logic)
    wp_enqueue_script(
        'ample-filter-engine',
        get_stylesheet_directory_uri() . '/js/ample-filter-engine.js',
        ['jquery'],
        '1.0.0',
        true
    );
    
    // Slider-to-filter integration
    wp_enqueue_script(
        'ample-slider-integration',
        get_stylesheet_directory_uri() . '/js/ample-slider-integration.js',
        ['jquery', 'ample-dual-range-slider-manual', 'ample-filter-engine'],
        '1.0.0',
        true
    );
    
    // Product display helper
    wp_enqueue_script(
        'ample-product-display',
        get_stylesheet_directory_uri() . '/js/ample-product-display.js',
        ['jquery', 'ample-filter-engine'],
        '1.0.0',
        true
    );
    
    // Integration styles
    wp_enqueue_style(
        'ample-filter-integration',
        get_stylesheet_directory_uri() . '/css/ample-filter-integration.css',
        [],
        '1.0.0'
    );
    
    // My Account page typography styles
    if (is_account_page()) {
        wp_enqueue_style(
            'my-account-typography',
            get_stylesheet_directory_uri() . '/css/my-account-typography.css',
            [],
            '1.0.0'
        );
    }
    
    // Button indicators for visual feedback
    wp_enqueue_script(
        'ample-button-indicators',
        get_stylesheet_directory_uri() . '/js/ample-button-indicators.js',
        ['jquery', 'ample-slider-integration'],
        '1.0.0',
        true
    );
    
    // Size filter integration (ABBA Issue #10)
    wp_enqueue_script(
        'ample-size-filter',
        get_stylesheet_directory_uri() . '/js/ample-size-filter.js',
        ['jquery', 'ample-filter-engine'],
        '1.0.0',
        true
    );
    
    // Dominance filter integration (ABBA Issue #10 - Phase 2)
    wp_enqueue_script(
        'ample-dominance-filter',
        get_stylesheet_directory_uri() . '/js/ample-dominance-filter.js',
        ['jquery', 'ample-filter-engine'],
        '1.0.0',
        true
    );
    
    // Terpenes filter integration (ABBA Issue #11 - Phase 1)
    wp_enqueue_script(
        'ample-terpenes-filter',
        get_stylesheet_directory_uri() . '/js/ample-terpenes-filter.js',
        ['jquery', 'ample-filter-engine'],
        '1.0.0',
        true
    );
    
    // Brand filter integration (ABBA Issue #12 - Phase 2)
    wp_enqueue_script(
        'ample-brand-filter',
        get_stylesheet_directory_uri() . '/js/ample-brand-filter.js',
        ['jquery', 'ample-filter-engine'],
        '1.0.0',
        true
    );
    
    // Category filter integration (ABBA Issue #12 - Phase 3)
    wp_enqueue_script(
        'ample-category-filter',
        get_stylesheet_directory_uri() . '/js/ample-category-filter.js',
        ['jquery', 'ample-filter-engine'],
        '1.0.0',
        true
    );
    
    // Pass configuration to JavaScript
    wp_localize_script('ample-filter-engine', 'AmpleFilterConfig', [
        'rest_url' => home_url('/wp-json/ample/v1/'),
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('ample-filter'),
        'debug' => WP_DEBUG, // Keep debug mode as is
        'version' => '1.0.0',
        'liveFiltering' => false, // Set to true for live filtering without Apply button
        'performance' => [
            'debounce_ms' => 100,
            'cache_ttl' => 900 // 15 minutes
        ],
        'features' => [
            'legacy_mode' => true // Enable URL parameter updates
        ]
    ]);
}

add_action('init', 'ample_filter_system_init', 1);

// ==========================================================================
// END AMPLE FILTER SYSTEM
// ==========================================================================

// BEGIN ENQUEUE PARENT ACTION
// AUTO GENERATED - Do not modify or remove comment markers above or below:

if ( !function_exists( 'chld_thm_cfg_locale_css' ) ):
    function chld_thm_cfg_locale_css( $uri ){
        if ( empty( $uri ) && is_rtl() && file_exists( get_template_directory() . '/rtl.css' ) )
            $uri = get_template_directory_uri() . '/rtl.css';
        return $uri;
    }
endif;
add_filter( 'locale_stylesheet_uri', 'chld_thm_cfg_locale_css' );

if ( !function_exists( 'chld_thm_cfg_parent_css' ) ):
    function chld_thm_cfg_parent_css() {
        wp_enqueue_style( 'chld_thm_cfg_parent', trailingslashit( get_template_directory_uri() ) . 'style.css', array( 'inter','bootstrap' ) );
    }
endif;
add_action( 'wp_enqueue_scripts', 'chld_thm_cfg_parent_css', 10 );

// END ENQUEUE PARENT ACTION
// 
function custom_rename_and_change_woocommerce_admin_menu() {
    global $menu;
    foreach ($menu as $key => $value) {
        if ($value[2] == 'woocommerce') {
            $menu[$key][0] = 'eCommerce'; // Change 'Custom Name' to your desired name
            // Change the icon (use a dashicon class or a custom URL)
            // Example: 'dashicons-admin-generic' or 'dashicons-store'
            $menu[$key][6] = 'dashicons-welcome-view-site'; // Replace 'dashicons-admin-generic' with the desired icon class
            // To remove the icon, set it to an empty string
            // $menu[$key][6] = ''; 
        }
    }
}
add_action('admin_menu', 'custom_rename_and_change_woocommerce_admin_menu', 999);
//
//
//
function add_custom_admin_js() {
    ?>
    <script type="text/javascript">
        document.addEventListener('DOMContentLoaded', function() {
            var titleElement = document.getElementById('wcf_cart_abandonment_tracking_table');
            if (titleElement) {
                titleElement.textContent = titleElement.textContent.replace('WooCommerce ', '');
            }
        });
    </script>
    <?php
}
add_action('admin_head', 'add_custom_admin_js');
//
//// Remove WooCommerce Marketing Widget
add_action('admin_enqueue_scripts', 'remove_woocommerce_marketing_widgets');
function remove_woocommerce_marketing_widgets() {
    wp_add_inline_style('wp-admin', '
        .woocommerce-marketing-coupons,
        .woocommerce-marketing-knowledgebase-card {
            display: none !important;
        }
    ');
}
//
// Add the field to the WooCommerce user profile
add_action('show_user_profile', 'add_user_approved_field');
add_action('edit_user_profile', 'add_user_approved_field');
function add_user_approved_field($user) {
    $approved = get_user_meta($user->ID, 'approved', true);
    ?>
    <h3><?php _e('Approval Status', 'textdomain'); ?></h3>
    <table class="form-table">
        <tr>
            <th><label for="approved"><?php _e('Approved', 'textdomain'); ?></label></th>
            <td>
                <label for="approved">
                    <input type="checkbox" id="approved" name="approved" <?php checked($approved, 'yes'); ?> />
                    <?php _e('Yes', 'textdomain'); ?>
                </label>
            </td>
        </tr>
    </table>
    <?php
}

// Save the field value when user profile is updated
add_action('personal_options_update', 'save_user_approved_field');
add_action('edit_user_profile_update', 'save_user_approved_field');
function save_user_approved_field($user_id) {
    if (current_user_can('edit_user', $user_id)) {
        $approved = isset($_POST['approved']) && $_POST['approved'] ? 'yes' : 'no';
        update_user_meta($user_id, 'approved', $approved);
    }
}
// STOP plugin auto-update
add_filter( 'auto_update_plugin', '__return_false' );

// Ensure WooCommerce is active
if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {

    // Remove the original WooCommerce function
    remove_action( 'woocommerce_shop_loop_item_title', 'woocommerce_template_loop_product_title', 10 );
    
   // Remove the default price output inside the product link
    remove_action( 'woocommerce_after_shop_loop_item_title', 'woocommerce_template_loop_price', 10 );

    add_action( 'woocommerce_after_shop_loop_item', 'woocommerce_template_loop_price', 5 );

    // Add your custom function
    add_action( 'woocommerce_shop_loop_item_title', 'my_custom_template_loop_product_title', 10 );

    // Define your custom function
    function my_custom_template_loop_product_title() {
        
        // Get the product object
        global $product;

        echo '<div class="group">';
        // Output the product title              
        echo '<h2 class="' . esc_attr( apply_filters( 'woocommerce_product_loop_title_classes', 'woocommerce-loop-product__title' ) ) . '">       
                <span class="favouriteIcon">
                    <i class="bi bi-heart"></i>
                    <i class="bi bi-heart-fill"></i>
                </span>
            ' . get_the_title() . '
            </h2>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

		// Get dynamic brand from product attributes
        $brand = get_product_brand($product);
        echo '<div class="prodcard-brand"><label>' . esc_html($brand) . '</label></div>';  
        
        echo '<div class="childGroup">';
       
		echo '<div class="prodcard-attributes">';
        // Get the THC attribute value
        $thc_value = $product->get_attribute('thc');
        if ( ! empty( $thc_value ) ) {
            echo '<div class="product-list-attribute"><strong>THC:</strong> <span>' . esc_html( $thc_value ) . '</span></div>';
        }
		
        // Get the CBD attribute value
        $cbd_value = $product->get_attribute('cbd');
        if ( ! empty( $cbd_value ) ) {
            echo '<div class="product-list-attribute"><strong>CBD:</strong> <span>' . esc_html( $cbd_value ) . '</span></div>';
        }
		
		echo '</div>';
		
        echo '</div>';
        
        // Get dynamic terpenes for this product
        $product_terpenes = get_single_product_terpenes($product);
        $terpene_total = get_product_terpene_total($product);
        
        // Always show terpenes section for consistent layout
        echo '<div class="prodcard-tags">';
        if (!empty($terpene_total)) {
            // Products with real terpene data
            echo 'Terpenes &nbsp; ' . esc_html($terpene_total) . '%
                  <p>' . esc_html($product_terpenes) . '</p>';
        } else if (strpos($product_terpenes, 'Terpene data not available') === false) {
            // Products without terpenes but with categories - show without label
            echo '<p>' . esc_html($product_terpenes) . '</p>';
        } else {
            // Products with no useful data - maintain consistent height with invisible content
            echo '<p style="visibility: hidden; margin: 0; line-height: 20px;">&nbsp;</p>';
        }
        echo '</div>';
        echo '</div>';
    }

}

/**
 * Get product rating display for list view
 * Returns star icon + rating score for top-right corner
 */
function get_product_rating_display($product) {
    if (!$product) return '';
    
    $rating = (float) $product->get_average_rating();
    $review_count = $product->get_review_count();
    
    // Only show rating if there are reviews
    if ($rating === 0.0 || $review_count === 0) {
        return '';
    }
    
    // Format: ⭐ 4.6 (optional review count)
    $display = '<i class="bi bi-star-fill"></i> ' . number_format($rating, 1);
    
    // Add review count if significant (10+ reviews)
    if ($review_count >= 10) {
        $display .= ' (' . $review_count . ')';
    }
    
    return $display;
}

/**
 * Add rating icon overlay to product loop items
 */
function add_product_rating_overlay() {
    global $product;
    
    if (!$product) return;
    
    $rating_display = get_product_rating_display($product);
    if (!empty($rating_display)) {
        echo '<span class="ratingIcon">' . $rating_display . '</span>';
    }
}
add_action('woocommerce_before_shop_loop_item', 'add_product_rating_overlay', 15);

add_action( 'init', 'custom_remove_default_loop_price' );
function custom_remove_default_loop_price() {
    remove_action( 'woocommerce_after_shop_loop_item_title', 'woocommerce_template_loop_price', 10 );
}


// Add Continue Shopping Button
add_action('woocommerce_after_add_to_cart_button', 'add_continue_shopping_button');
function add_continue_shopping_button() {
    $shop_page_url = wc_get_page_permalink('shop');
    echo '<a href="' . esc_url($shop_page_url) . '" class="button continue-shopping-button">Continue Shopping</a>';
}

function custom_cart_collaterals_shortcode() {
    ob_start(); 
    ?>
    <div class="cart-collaterals"> 
        <?php
            do_action( 'woocommerce_cart_collaterals' ); 
        ?>
    </div>
    <?php
    return ob_get_clean(); 
}
add_shortcode('cart_collaterals', 'custom_cart_collaterals_shortcode');


// Add this in functions.php or a custom plugin
function my_custom_shortcode_function() {
    return "<p>This is my custom content from shortcode.</p>";
}
add_shortcode('terpene_details', 'my_custom_shortcode_function');
add_shortcode('potencies', 'my_custom_shortcode_function');

// variation price
add_filter( 'woocommerce_show_variation_price', '__return_true' );


// product category 
function product_category_filter_shortcode() {
  ob_start();
  echo '<form id="category-filter-form" class="categoryFilterForm" method="get">';

  $product_categories = get_terms([
    'taxonomy'   => 'product_cat',
    'orderby'    => 'name',
    'hide_empty' => true,
  ]);

  if (!empty($product_categories) && !is_wp_error($product_categories)) {
    // Group categories by parent
    $category_tree = [];

    foreach ($product_categories as $category) {
      $category_tree[$category->parent][] = $category;
    }
    echo '<div class="colGroup">';
    // Build the filter UI with the requested structure
    if (isset($category_tree[0])) {
      foreach ($category_tree[0] as $parent_cat) {
        // echo '<div class="colGroup">';
        echo '<div class="col">';
        echo '<h5>' . esc_html($parent_cat->name) . '</h5>';

        if (!empty($category_tree[$parent_cat->term_id])) {
          echo '<div class="labelGroup">';
          foreach ($category_tree[$parent_cat->term_id] as $child_cat) {
            echo '<label>';
            echo '<input type="checkbox" class="category-filter inputCheck" name="categories[]" value="' . esc_attr($child_cat->slug) . '"> ';
            echo esc_html($child_cat->name);
            echo '</label>';
          }
          echo '</div>';
        } else {
          echo '<div class="labelGroup"><em>No subcategories</em></div>';
        }

        echo '</div>'; // end .col
       
      }
    }
  } else {
    echo '<p>No product categories found.</p>';
  }

   echo '</div>'; // end .colGroup
   echo '<div class="menuFooter">
    <button type="button" id="selectAllProductMenu" class="selectAll">Select All</button>
    <button type="submit" id="sortByBtn" class="sortBtn">Sort By</button>
  </div>';
  echo '</form>';
  return ob_get_clean();
  
}
add_shortcode('product_category_filter', 'product_category_filter_shortcode');


// products menu
function render_dynamic_product_category_filter_form() {
    // Get all parent product categories
    $parent_terms = get_terms([
        'taxonomy'   => 'product_cat',
        'hide_empty' => false,
        'parent'     => 0,
    ]);

    if (empty($parent_terms) || is_wp_error($parent_terms)) {
        return '<p>No categories found.</p>';
    }

    ob_start();
    ?>
    <form id="product-category-filter-form" class="productCategoryFilterForm" method="get">
      <div class="colGroup">
        <?php foreach ($parent_terms as $parent_term): ?>
          <div class="col">
            <h5><?php echo esc_html($parent_term->name); ?></h5>
            <div class="labelGroup">
              <?php
              // Get sub-categories of this parent
              $sub_terms = get_terms([
                  'taxonomy'   => 'product_cat',
                  'hide_empty' => false,
                  'parent'     => $parent_term->term_id,
              ]);

              foreach ($sub_terms as $sub_term): 
                  $slug = esc_attr($sub_term->slug);
                  $id = 'filter_' . sanitize_html_class($slug);
                  ?>
                  <label for="<?php echo $id; ?>">
                      <input class="inputCheck" type="checkbox" id="<?php echo $id; ?>" value="<?php echo $slug; ?>">
                      <?php echo esc_html($sub_term->name); ?>
                  </label>
              <?php endforeach; ?>

              <!-- Optional: Add a "Shop All" checkbox -->
              <label for="shopAll_<?php echo esc_attr($parent_term->slug); ?>">
                <input class="inputCheck inputCheckAll" type="checkbox" id="shopAll_<?php echo esc_attr($parent_term->slug); ?>" value="<?php echo esc_attr($parent_term->slug); ?>">
                Shop All <?php echo esc_html($parent_term->name); ?>
              </label>
            </div>
          </div>
        <?php endforeach; ?>
      </div>

      <div class="menuFooter">
        <button type="button" id="selectAllProductMenu" class="selectAll">Select All</button>
        <button type="submit" id="sortByBtn" class="sortBtn">Sort By</button>
      </div>
    </form>
    <?php

    return ob_get_clean();
}
add_shortcode('product_category_filter_form', 'render_dynamic_product_category_filter_form');


// Custom products menu filter
// function custom_product_filter_results_shortcode() {

//     $search_term = get_query_var('filter_slugs');
//     $search_term = sanitize_text_field($search_term);
//     $search_termms = explode('+', $search_term);
//     $search_termms = array_filter(array_map('trim', $search_termms));
//     $search_terms = [];

//     foreach ($search_termms as $term) {
//         // Split on dash if present
//         $parts = preg_split('/[\s\-]+/', $term);
//         foreach ($parts as $part) {
//             $part = trim($part);
//             if (!empty($part)) {
//                 $search_terms[] = strtolower($part);
//             }
//         }
//     }
//     $paged = get_query_var('paged') ? intval(get_query_var('paged')) : 1;
//     $view_all = isset($_GET['view']) && $_GET['view'] === 'all';
//     $orderby = isset( $_GET['orderby'] ) ? wc_clean( wp_unslash( $_GET['orderby'] ) ) : 'menu_order';
//     $order_args = WC()->query->get_catalog_ordering_args( $orderby );

//     $max_terms = 5;
//     $total_terms = count($search_terms);

//     $display_terms = array_slice($search_terms, 0, $max_terms);
//     $formatted = implode(', ', array_map('ucwords', $display_terms));

//     if ($total_terms > $max_terms) {
//         $formatted .= '.....';
//     }

    

//     if (empty($search_term)) {
//         return '<div class="container text-white pt-5 pb-5" style="min-height:500px">Please select a category to view products.</div>';
//     }

//     $matched_ids = [];

//     // 1. Match by product title (using your custom posts_where filter)
//     $title_query = new WP_Query([
//         'post_type'           => 'product',
//         'posts_per_page'      => -1,
//         'post_status'         => 'publish',
//         'fields'              => 'ids',
//         'suppress_filters'    => false, // Needed for your custom title filter
//         'filter_title_terms' => $search_terms,
//     ]);
//     $matched_ids = $title_query->posts ?: [];
//     // echo '<pre>Matched by title: ' . implode(', ', $matched_ids) . '</pre>';

//     $matching_term_ids = [];
//     foreach ($search_terms as $term) {
//         $matching_terms = get_terms([
//             'taxonomy'   => 'product_cat',
//             'hide_empty' => false,
//             'name__like' => $term,
//         ]);

//         foreach ($matching_terms as $matched_term) {
//             $matching_term_ids[] = $matched_term->term_id;
//         }
//     }

//     $matching_term_ids = array_unique($matching_term_ids);

//     // Now do a proper tax_query using term IDs
//     $category_ids = [];
//     if (!empty($matching_term_ids)) {
//         $category_query = new WP_Query([
//             'post_type'      => 'product',
//             'posts_per_page' => -1,
//             'post_status'    => 'publish',
//             'fields'         => 'ids',
//             'tax_query'      => [
//                 [
//                     'taxonomy' => 'product_cat',
//                     'field'    => 'term_id',
//                     'terms'    => $matching_term_ids,
//                     'operator' => 'IN',
//                 ],
//             ],
//         ]);
//         $category_ids = $category_query->posts ?: [];
//     }
//     // echo '<pre>Matched by category: ' . implode(', ', $category_ids) . '</pre>';
//     $matched_ids = array_unique(array_merge($matched_ids, $category_ids));

//     // 3. Prevent match-all fallback
//     if (empty($matched_ids)) {
//         // echo '<pre>No matches found. Setting matched_ids = [0]</pre>';
//         $matched_ids = [0];
//     } else {
//         // echo '<pre>Final Matched IDs: ' . implode(', ', $matched_ids) . '</pre>';
//     }
    
//     // 3. Final filtered paginated query
//     $args = [
//         'post_type'      => 'product',
//         'posts_per_page' => $view_all ? -1 : 12,
//         'paged'          => $view_all ? 1 : $paged,
//         'post_status'    => 'publish',
//         'orderby'        => 'title',
//         'order'          => 'ASC',
//         'suppress_filters'    => false,
//         'post__in'       => $matched_ids,
//     ];

//     $args['orderby'] = $order_args['orderby'];
//     $args['order']   = $order_args['order'];

//     if ( ! empty( $order_args['meta_key'] ) ) {
//         $args['meta_key'] = $order_args['meta_key'];
//     }

//     $query = new WP_Query($args);
//     ob_start();

//     echo '<div class="container-fluid">';
//     if ($query->have_posts()) {		
        
//         echo '<div class="productTopbar">';
//         echo '<p class="product-count">' . $query->post_count . ' of ' . $query->found_posts . ' products</p>';

//         echo '<form class="woocommerce-ordering" method="get">
//             <select name="orderby" class="orderby" >';
                
//                 $orderby_options = apply_filters( 'woocommerce_catalog_orderby', [
//                     'menu_order' => __( 'Default sorting', 'woocommerce' ),
//                     'popularity' => __( 'Sort by popularity', 'woocommerce' ),
//                     'rating'     => __( 'Sort by average rating', 'woocommerce' ),
//                     'date'       => __( 'Sort by latest', 'woocommerce' ),
//                     'price'      => __( 'Sort by price: low to high', 'woocommerce' ),
//                     'price-desc' => __( 'Sort by price: high to low', 'woocommerce' ),
//                 ] );

//                 $current_orderby = isset( $_GET['orderby'] ) ? wc_clean( wp_unslash( $_GET['orderby'] ) ) : 'menu_order';

//                 foreach ( $orderby_options as $id => $label ) {
//                     echo '<option value="' . esc_attr( $id ) . '" ' . selected( $current_orderby, $id, false ) . '>' . esc_html( $label ) . '</option>';
//                 }
                
//             echo '</select>';

//             // Preserve all other GET parameters (filters, search, paged, etc)
//             foreach ( $_GET as $key => $value ) {
//                 if ( 'orderby' === $key ) {
//                     continue;
//                 }
//                 if ( is_array( $value ) ) {
//                     foreach ( $value as $val ) {
//                         echo '<input type="hidden" name="' . esc_attr( $key ) . '[]" value="' . esc_attr( $val ) . '">';
//                     }
//                 } else {
//                     echo '<input type="hidden" name="' . esc_attr( $key ) . '" value="' . esc_attr( $value ) . '">';
//                 }
//             }

//         echo '</form>';
        
//         echo '</div>';

//         echo '<div class="titleWrapper">
//                 <div class="container-fluid">
//                     <a id="goback" class="backBtn" style="display:none">
//                         Back
//                     </a>
//                 <h5>' . esc_html($formatted) . '</h5>
//                 </div>
//             </div>';

        

//         echo '<ul class="products elementor-grid columns-4">';
//         while ($query->have_posts()) {
//             $query->the_post();
//             wc_get_template_part('content', 'product');
//         }
//         echo '</ul>';
//         global $wp;
//         $current_url = home_url(add_query_arg([], $wp->request));
//         $view_all_url = esc_url(add_query_arg('view', 'all', $current_url));
//         $paginate_url = esc_url(remove_query_arg('view', $current_url));
//         if ($view_all) {
//             echo '<a href="' . $paginate_url . '" class="viewAllBtn">Show Paginated</a>';
//         } else {
//             echo '<a href="' . $view_all_url . '" class="viewAllBtn">View All</a>';
//         }

//         if (!$view_all && $query->max_num_pages > 1) {
//             echo '<nav class="woocommerce-pagination">';
//             echo '<ul class="page-numbers">';
//             echo paginate_links(array(
//                 'base'      => trailingslashit(get_pagenum_link(1)) . 'page/%#%/',
//                 'format'    => '',
//                 'current'   => $paged,
//                 'total'     => $query->max_num_pages,
//                 'prev_text' => __('←'),
//                 'next_text' => __('→'),
//                 'type'      => 'list',
//             ));

//             echo '</ul>';
//             echo '</nav>';
//         }
            
        

//     } else {
//         echo '<div class="noProductFound"><div class="alert alert-danger" role="alert">No products found of this type.</div></div>';				
//     }

//     echo '</div>';

//     wp_reset_postdata();
//     return ob_get_clean();
// }
function custom_product_filter_results_shortcode() {
    $request_uri = $_SERVER['REQUEST_URI'];
    preg_match('#product-filter/([^/]+)#', $request_uri, $matches);

    // Start output buffering
    ob_start();
    
    echo '<div class="container-fluid">';

    if (isset($matches[1])) {
        $filter_string = $matches[1];
        $sub_categories = explode('+', $filter_string);
        $total_products = 0;
        $term_results = [];


        // Capture the current orderby value
        $orderby = isset($_GET['orderby']) ? wc_clean(wp_unslash($_GET['orderby'])) : 'menu_order';

        // Default order settings
        $order_args = [
            'orderby' => 'menu_order',
            'order' => 'ASC',
            'meta_key' => '',
        ];

        switch ($orderby) {
            case 'price':
                $order_args['orderby'] = 'meta_value_num';
                $order_args['meta_key'] = '_price';
                $order_args['order'] = 'ASC';
                break;
            case 'price-desc':
                $order_args['orderby'] = 'meta_value_num';
                $order_args['meta_key'] = '_price';
                $order_args['order'] = 'DESC';
                break;
            case 'date':
                $order_args['orderby'] = 'date';
                $order_args['order'] = 'DESC';
                break;
            case 'popularity':
                $order_args['orderby'] = 'meta_value_num';
                $order_args['meta_key'] = 'total_sales';
                $order_args['order'] = 'DESC';
                break;
            case 'rating':
                $order_args['orderby'] = 'meta_value_num';
                $order_args['meta_key'] = '_wc_average_rating';
                $order_args['order'] = 'DESC';
                break;
            default:
                $order_args['orderby'] = 'menu_order';
                $order_args['order'] = 'ASC';
                break;
        }

        // Loop through sub-category slugs and collect product queries
        foreach ($sub_categories as $slug) {
            $term = get_term_by('slug', sanitize_title($slug), 'product_cat');
            if (!$term || is_wp_error($term)) {
                continue;
            }

            $args = [
                'post_type'      => 'product',
                'posts_per_page' => -1,
                'orderby'        => $order_args['orderby'],
                'order'          => $order_args['order'],
                'meta_key'       => isset($order_args['meta_key']) ? $order_args['meta_key'] : '',
                'orderby'        => $order_args['orderby'],
                'order'          => $order_args['order'],
                'tax_query'      => [[
                    'taxonomy' => 'product_cat',
                    'field'    => 'slug',
                    'terms'    => $term->slug,
                    'include_children' => false,
                ]],
            ];
            
            if (!empty($order_args['meta_key'])) {
                $args['meta_key'] = $order_args['meta_key'];
            }

            // Apply purchasable product filter for logged-in users
            if (is_user_logged_in()) {
                $purchasable_ids = ample_get_cached_purchasable_product_ids();
                if (!empty($purchasable_ids)) {
                    $args['post__in'] = $purchasable_ids;
                } else {
                    // No purchasable products - skip this category
                    continue;
                }
            }
            
            $query = new WP_Query($args);

            if ($query->have_posts()) {
                $term_results[] = [
                    'term'  => $term,
                    'query' => $query,
                ];
                $total_products += $query->found_posts;
            }
        }

        // Show Top Bar only if there are results
        if ($total_products > 0) {
            echo '<div class="productTopbar">';
            echo '<p class="product-count">Found ' . $total_products . ' products</p>';

            echo '<form class="woocommerce-ordering" method="get">';
            echo '<select name="orderby" class="orderby" >';

            $orderby_options = apply_filters('woocommerce_catalog_orderby', [
                'menu_order' => __('Default sorting', 'woocommerce'),
                'popularity' => __('Sort by popularity', 'woocommerce'),
                'rating'     => __('Sort by average rating', 'woocommerce'),
                'date'       => __('Sort by latest', 'woocommerce'),
                'price'      => __('Sort by price: low to high', 'woocommerce'),
                'price-desc' => __('Sort by price: high to low', 'woocommerce'),
            ]);

            foreach ($orderby_options as $id => $label) {
                echo '<option value="' . esc_attr($id) . '" ' . selected($orderby, $id, false) . '>' . esc_html($label) . '</option>';
            }

            echo '</select>';

            // Preserve other GET parameters
            foreach ($_GET as $key => $value) {
                if ($key === 'orderby') {
                    continue;
                }
                if (is_array($value)) {
                    foreach ($value as $val) {
                        echo '<input type="hidden" name="' . esc_attr($key) . '[]" value="' . esc_attr($val) . '">';
                    }
                } else {
                    echo '<input type="hidden" name="' . esc_attr($key) . '" value="' . esc_attr($value) . '">';
                }
            }

            echo '</form>';
            echo '</div>'; // End productTopbar
        }

        // Loop through each term's results and render product blocks
        foreach ($term_results as $entry) {
            $term = $entry['term'];
            $query = $entry['query'];
            $color_class = 'category-' . sanitize_html_class($term->slug);
            echo '<div class="titleWrapper '. $color_class .'">
                    <div class="container-fluid">
                        <a id="goback" class="backBtn" style="display:none">Back</a>
                        <h5>' . esc_html($term->name) . '</h5>
                    </div>
                </div>';

            echo '<ul class="products elementor-grid columns-4">';
            while ($query->have_posts()) {
                $query->the_post();
                wc_get_template_part('content', 'product');
            }
            echo '</ul>';
            wp_reset_postdata();
        }

        if ($total_products === 0) {
            echo '<div class="noProductFound"><div class="alert alert-danger" role="alert">No products found.</div></div>';
        }
    } else {
        echo '<div class="noProductFound"><div class="alert alert-danger" role="alert">No filter terms selected.</div></div>';
    }
    echo '</div>'; // End container
    return ob_get_clean();
}
add_shortcode('custom_product_filter_results', 'custom_product_filter_results_shortcode');



// Custom Featured menu filter 
function custom_featured_filter_results_shortcode() {
    $request_uri = $_SERVER['REQUEST_URI'];
    preg_match('#featured-filter/([^/]+)#', $request_uri, $matches);

    ob_start();
    echo '<div class="container-fluid">';

    if (isset($matches[1])) {

        $filter_string = $matches[1];
        $tagged_filters = explode('+', $filter_string);
        $total_products = 0;
        $term_results = [];

        // Capture the current orderby value
        $orderby = isset($_GET['orderby']) ? wc_clean(wp_unslash($_GET['orderby'])) : 'menu_order';

        // Setup order args
        $order_args = [
            'orderby' => 'menu_order',
            'order' => 'ASC',
            'meta_key' => '',
        ];

        switch ($orderby) {
            case 'price':
                $order_args['orderby'] = 'meta_value_num';
                $order_args['meta_key'] = '_price';
                $order_args['order'] = 'ASC';
                break;
            case 'price-desc':
                $order_args['orderby'] = 'meta_value_num';
                $order_args['meta_key'] = '_price';
                $order_args['order'] = 'DESC';
                break;
            case 'date':
                $order_args['orderby'] = 'date';
                $order_args['order'] = 'DESC';
                break;
            case 'popularity':
                $order_args['orderby'] = 'meta_value_num';
                $order_args['meta_key'] = 'total_sales';
                $order_args['order'] = 'DESC';
                break;
            case 'rating':
                $order_args['orderby'] = 'meta_value_num';
                $order_args['meta_key'] = '_wc_average_rating';
                $order_args['order'] = 'DESC';
                break;
            default:
                $order_args['orderby'] = 'menu_order';
                $order_args['order'] = 'ASC';
                break;
        }

        // Loop through tag_subcategory pairs
        foreach ($tagged_filters as $entry) {
            if (strpos($entry, '_') === false) continue;

            list($tag, $subcategory_slug) = explode('_', $entry, 2);

            $term = get_term_by('slug', sanitize_title($subcategory_slug), 'product_cat');
            if (!$term || is_wp_error($term)) continue;

            $args = [
                'post_type'      => 'product',
                'posts_per_page' => -1,
                'orderby'        => $order_args['orderby'],
                'order'          => $order_args['order'],
                'tax_query'      => [
                    'relation' => 'AND',
                    [
                        'taxonomy' => 'product_cat',
                        'field'    => 'slug',
                        'terms'    => $term->slug,
                        'include_children' => false,
                    ],
                    [
                        'taxonomy' => 'product_tag', // or custom taxonomy name
                        'field'    => 'slug',
                        'terms'    => sanitize_title($tag),
                    ]
                ],
            ];

            if (!empty($order_args['meta_key'])) {
                $args['meta_key'] = $order_args['meta_key'];
            }

            // Apply purchasable product filter for logged-in users
            if (is_user_logged_in()) {
                $purchasable_ids = ample_get_cached_purchasable_product_ids();
                if (!empty($purchasable_ids)) {
                    $args['post__in'] = $purchasable_ids;
                } else {
                    // No purchasable products - skip this entry
                    continue;
                }
            }

            $query = new WP_Query($args);

            if ($query->have_posts()) {
                $term_results[] = [
                    'term'  => $term,
                    'query' => $query,
                ];
                $total_products += $query->found_posts;
            }
        }

        // Render topbar if there are products
        if ($total_products > 0) {
            echo '<div class="productTopbar">';
            echo '<p class="product-count">Found ' . $total_products . ' products</p>';

            echo '<form class="woocommerce-ordering" method="get">';
            echo '<select name="orderby" class="orderby" >';

            $orderby_options = apply_filters('woocommerce_catalog_orderby', [
                'menu_order' => __('Default sorting', 'woocommerce'),
                'popularity' => __('Sort by popularity', 'woocommerce'),
                'rating'     => __('Sort by average rating', 'woocommerce'),
                'date'       => __('Sort by latest', 'woocommerce'),
                'price'      => __('Sort by price: low to high', 'woocommerce'),
                'price-desc' => __('Sort by price: high to low', 'woocommerce'),
            ]);

            foreach ($orderby_options as $id => $label) {
                echo '<option value="' . esc_attr($id) . '" ' . selected($orderby, $id, false) . '>' . esc_html($label) . '</option>';
            }

            echo '</select>';

            // Preserve other GET parameters
            foreach ($_GET as $key => $value) {
                if ($key === 'orderby') continue;

                if (is_array($value)) {
                    foreach ($value as $val) {
                        echo '<input type="hidden" name="' . esc_attr($key) . '[]" value="' . esc_attr($val) . '">';
                    }
                } else {
                    echo '<input type="hidden" name="' . esc_attr($key) . '" value="' . esc_attr($value) . '">';
                }
            }

            echo '</form>';
            echo '</div>'; // End productTopbar
        }

        // Render each result block
        foreach ($term_results as $entry) {
            $term = $entry['term'];
            $query = $entry['query'];
            $color_class = 'category-' . sanitize_html_class($term->slug);
            echo '<div class="titleWrapper '. $color_class .'">
                    <div class="container-fluid">
                        <a id="goback" class="backBtn" style="display:none">Back</a>
                        <h5>' . esc_html($term->name) . '</h5>
                    </div>
                </div>';

            echo '<ul class="products elementor-grid columns-4">';
            while ($query->have_posts()) {
                $query->the_post();
                wc_get_template_part('content', 'product');
            }
            echo '</ul>';
            wp_reset_postdata();
        }

        if ($total_products === 0) {
            echo '<div class="noProductFound"><div class="alert alert-danger" role="alert">No products found.</div></div>';
        }

    } else {
        echo '<div class="noProductFound"><div class="alert alert-danger" role="alert">No filter terms selected.</div></div>';
    }

    echo '</div>'; // End container
    return ob_get_clean();

}
add_shortcode('custom_featured_filter_results', 'custom_featured_filter_results_shortcode');


add_shortcode('custom_products_paginated', 'custom_product_filter_results');
function fix_shortcode_pagination($query) {
    if (!is_admin() && $query->is_main_query() && is_page()) {
        if (get_query_var('paged')) {
            $query->set('paged', get_query_var('paged'));
        }
    }
}
// OPTIMIZED: Only register pagination fix when needed
add_action('wp', 'healfio_conditional_pagination_hooks');

/**
 * Conditionally register pagination and filtering hooks only when needed
 */
function healfio_conditional_pagination_hooks() {
    // Register pagination fix on pages that need it
    if (is_home() || is_archive() || is_search() || is_shop() || is_product_category()) {
        add_action('pre_get_posts', 'fix_shortcode_pagination', 10);
    }
    
    // Register brand filtering only on shop pages
    if (is_shop() || is_product_category() || is_product_tag()) {
        add_action('pre_get_posts', 'filter_products_by_brand', 10);
        // Also register the brand filter checkboxes
        add_action('woocommerce_before_shop_loop', 'add_brand_filter_checkboxes', 15);
    }
}


function custom_brand_filter_results_shortcode() {
    $request_uri = $_SERVER['REQUEST_URI'];
    preg_match('#brand-filter/([^/]+)#', $request_uri, $matches);

    // Start output buffering
    ob_start();
    echo '<div class="container-fluid">';

    if (isset($matches[1])) {

        $filter_string = $matches[1];
        $brands = explode('+', $filter_string);
        $brand = $brands[0];
        $total_products = 0;
        $term_results = [];

        // Capture the current orderby value
        $orderby = isset($_GET['orderby']) ? wc_clean(wp_unslash($_GET['orderby'])) : 'menu_order';

        // Default order settings
        $order_args = [
            'orderby' => 'menu_order',
            'order' => 'ASC',
            'meta_key' => '',
        ];

        switch ($order_by) {
            case 'price':
                $order_args['orderby'] = 'meta_value_num';
                $order_args['meta_key'] = '_price';
                $order_args['order'] = 'ASC';
                break;
            case 'price-desc':
                $order_args['orderby'] = 'meta_value_num';
                $order_args['meta_key'] = '_price';
                $order_args['order'] = 'DESC';
                break;
            case 'date':
                $order_args['orderby'] = 'date';
                $order_args['order'] = 'DESC';
                break;
            case 'popularity':
                $order_args['orderby'] = 'meta_value_num';
                $order_args['meta_key'] = 'total_sales';
                $order_args['order'] = 'DESC';
                break;
            case 'rating':
                $order_args['orderby'] = 'meta_value_num';
                $order_args['meta_key'] = '_wc_average_rating';
                $order_args['order'] = 'DESC';
                break;
            default:
                $order_args['orderby'] = 'menu_order';
                $order_args['order'] = 'ASC';
                break;
        }

        $brand = urldecode( $brand ); 
        // $term = get_term_by( 'name', $brand, 'pa_brand' );

        // if ( $term && ! is_wp_error( $term ) ) {
        //     $brand_slug = $term->slug;
        // } else {
        //     $brand_slug = '';
        // }

        $args = [
            'post_type'  => 'product',
            'posts_per_page' => -1,
            'orderby'        => $orderby_args['orderby'],
            'order'          => $orderby_args['order'],
            'meta_key'       => isset($orderby_args['meta_key']) ? $orderby_args['meta_key'] : '',
            'tax_query' => [
                [
                    'taxonomy' => 'pa_brand',  // your attribute taxonomy
                    'field'    => 'name',      // or 'slug'
                    'terms'    => $brand,      // replace with brand name
                ],
            ],
        ];

        if (!empty($order_args['meta_key'])) {
            $args['meta_key'] = $order_args['meta_key'];
        }
        $query = new WP_Query($args);

        if ($query->have_posts()) {
            $term_results = [
                'brand'  => $brand,
                'query' => $query,
            ];
            $total_products += $query->found_posts;
        }

        // Show Top Bar only if there are results
        
            echo '<div class="productTopbar">';
            echo '<p class="product-count">Found ' . $total_products . ' products</p>';

            echo '<form class="woocommerce-ordering" method="get">';
            echo '<select name="orderby" class="orderby" onchange="this.form.submit()">';

            $orderby_options = apply_filters('woocommerce_catalog_orderby', [
                'menu_order' => __('Default sorting', 'woocommerce'),
                'popularity' => __('Sort by popularity', 'woocommerce'),
                'rating'     => __('Sort by average rating', 'woocommerce'),
                'date'       => __('Sort by latest', 'woocommerce'),
                'price'      => __('Sort by price: low to high', 'woocommerce'),
                'price-desc' => __('Sort by price: high to low', 'woocommerce'),
            ]);

            foreach ($orderby_options as $id => $label) {
                echo '<option value="' . esc_attr($id) . '" ' . selected($orderby, $id, false) . '>' . esc_html($label) . '</option>';
            }

            echo '</select>';

            // Preserve other GET parameters
            foreach ($_GET as $key => $value) {
                if ($key === 'orderby') {
                    continue;
                }
                if (is_array($value)) {
                    foreach ($value as $val) {
                        echo '<input type="hidden" name="' . esc_attr($key) . '[]" value="' . esc_attr($val) . '">';
                    }
                } else {
                    echo '<input type="hidden" name="' . esc_attr($key) . '" value="' . esc_attr($value) . '">';
                }
            }

            echo '</form>';
            echo '</div>'; // End productTopbar
        

            $query = $term_results['query'];
            $color_class = 'category-gummies';
            echo '<div class="titleWrapper '. $color_class .'">
                    <div class="container-fluid">
                        <a id="goback" class="backBtn" style="display:none">Back</a>
                        <h5>' . esc_html($brand) . '</h5>
                    </div>
                </div>';
        if ($total_products > 0) {
            echo '<ul class="products elementor-grid columns-4">';
            while ($query->have_posts()) {
                $query->the_post();
                wc_get_template_part('content', 'product');
            }
            echo '</ul>';
            wp_reset_postdata();
        } else {
            echo '<div class="noProductFound"><div class="alert alert-danger" role="alert">No products found.</div></div>';
        }
        
    } else {
        echo '<div class="noProductFound"><div class="alert alert-danger" role="alert">No filter terms selected.</div></div>';
    }
    echo '</div>'; // End container
    return ob_get_clean();
}
add_shortcode('custom_brand_filter_results', 'custom_brand_filter_results_shortcode');


// Add rewrite rule for pretty category filters like /product-filter/slug+slug/
function register_product_filter_rewrite_rule() {
  add_rewrite_rule(
    '^product-filter/([^/]+)/?$',
    'index.php?pagename=product-filter&filter_slugs=$matches[1]',
    'top'
  );

  // Match: /product-filter/Strawberry/page/2/
    add_rewrite_rule(
			'^product-filter/([^/]+)/page/([0-9]+)/?$',
			'index.php?pagename=product-filter&filter_slugs=$matches[1]&paged=$matches[2]',
			'top'
    );
}

add_action('init', 'register_product_filter_rewrite_rule');

// Add rewrite rule for pretty category filters like /product-filter/slug+slug/
function register_brand_filter_rewrite_rule() {
    add_rewrite_rule(
        '^brand-filter/([^/]+)/?$',
        'index.php?pagename=brand-filter&filter_slugs=$matches[1]',
        'top'
    );

    // Match: /product-filter/Strawberry/page/2/
    add_rewrite_rule(
			'^brand-filter/([^/]+)/page/([0-9]+)/?$',
			'index.php?pagename=brand-filter&filter_slugs=$matches[1]&paged=$matches[2]',
			'top'
    );
}

add_action('init', 'register_brand_filter_rewrite_rule');

// Add rewrite rule for pretty category filters like /featured-filter/slug+slug/
function register_featured_filter_rewrite_rule() {
  add_rewrite_rule(
    '^featured-filter/([^/]+)/?$',
    'index.php?pagename=featured-filter&filter_slugs=$matches[1]',
    'top'
  );

  // Match: /featured-filter/Strawberry/page/2/
    add_rewrite_rule(
			'^featured-filter/([^/]+)/page/([0-9]+)/?$',
			'index.php?pagename=featured-filter&filter_slugs=$matches[1]&paged=$matches[2]',
			'top'
    );
}
add_action('init', 'register_featured_filter_rewrite_rule');

// Add custom query var
function add_product_filter_query_var($vars) {
  $vars[] = 'filter_slugs';
  return $vars;
}
add_filter('query_vars', 'add_product_filter_query_var');

function filter_products_by_multiple_keywords($where, $query) {
    if (!is_admin() && $query->get('filter_title_terms')) {
        global $wpdb;
        $terms = $query->get('filter_title_terms');
        $title_conditions = [];

        foreach ($terms as $term) {
            $title_conditions[] = $wpdb->prepare("{$wpdb->posts}.post_title LIKE %s", '%' . $wpdb->esc_like($term) . '%');
        }

        if (!empty($title_conditions)) {
            $where .= ' AND (' . implode(' OR ', $title_conditions) . ')';
        }
    }

    return $where;
}
add_filter('posts_where', 'filter_products_by_multiple_keywords', 10, 2);


// Add brand filter checkboxes above product listings
// OPTIMIZED: Now registered conditionally in healfio_conditional_pagination_hooks()
// add_action('woocommerce_before_shop_loop', 'add_brand_filter_checkboxes', 15);
function add_brand_filter_checkboxes() {
    if (!is_shop() && !is_product_category()) return;

    $brands = get_terms([
        'taxonomy'   => 'product_brand',
        'hide_empty' => false,
    ]);

    if (!empty($brands) && !is_wp_error($brands)) {
        ?>
        <form method="GET" id="brand-filter-form">
            <strong>Filter by Brand:</strong><br>
            <?php foreach ($brands as $brand): 
                $checked = (isset($_GET['product_brand']) && in_array($brand->slug, (array) $_GET['product_brand'])) ? 'checked' : '';
                ?>
                <label>
                    <input type="checkbox" name="product_brand[]" value="<?php echo esc_attr($brand->slug); ?>" <?php echo $checked; ?>>
                    <?php echo esc_html($brand->name); ?>
                </label><br>
            <?php endforeach; ?>

            <!-- Preserve other query parameters like orderby, search, etc -->
            <?php
            foreach ($_GET as $key => $value) {
                if ($key === 'product_brand') continue;
                if (is_array($value)) {
                    foreach ($value as $v) {
                        echo '<input type="hidden" name="'.esc_attr($key).'[]" value="'.esc_attr($v).'">';
                    }
                } else {
                    echo '<input type="hidden" name="'.esc_attr($key).'" value="'.esc_attr($value).'">';
                }
            }
            ?>

            <button type="submit">Apply Filter</button>
        </form>
        <br>
        <?php
    }
}


// Modify WooCommerce product query based on selected brands
// OPTIMIZED: This will be registered conditionally
// add_action('pre_get_posts', 'filter_products_by_brand');
function filter_products_by_brand($query) {
    if (!is_admin() && $query->is_main_query() && (is_shop() || is_product_category())) {
        if (!empty($_GET['product_brand'])) {
            $brands = array_map('sanitize_text_field', $_GET['product_brand']);

            $tax_query = $query->get('tax_query');
            if (!is_array($tax_query)) {
                $tax_query = [];
            }

            $tax_query[] = [
                'taxonomy' => 'product_brand',
                'field'    => 'slug',
                'terms'    => $brands,
            ];

            $query->set('tax_query', $tax_query);
        }
    }
}


// product category
// Shortcode to display only parent product categories with checkboxes and apply button
function abbamedix_parent_product_category_checkbox_list() {
    // Get only parent product categories
    $parent_categories = get_terms([
        'taxonomy'   => 'product_cat',
        'hide_empty' => false,
        'parent'     => 0, // Only parent categories
    ]);

    ob_start();

    if (!empty($parent_categories) && !is_wp_error($parent_categories)) {
        ?>
        <form id="product-filter-category" class="productFilterCategory" method="GET" action="">
						<div class="categoryList">
							<?php foreach ($parent_categories as $category) : ?>
									<label>
											<input type="checkbox" name="product_cat[]" value="<?php echo esc_attr($category->slug); ?>"
													<?php if (isset($_GET['product_cat']) && in_array($category->slug, $_GET['product_cat'])) echo 'checked'; ?>>
											<?php echo esc_html($category->name); ?>
									</label><br>
							<?php endforeach; ?>
						</div>
            <button type="submit" class="btnApply">Apply</button>
        </form>
        <?php

        // Optional: Display selected category slugs
        if (isset($_GET['product_cat'])) {
            echo '<h4>Selected Categories:</h4>';
            echo '<ul>';
            foreach ($_GET['product_cat'] as $selected) {
                echo '<li>' . esc_html($selected) . '</li>';
            }
            echo '</ul>';
        }
    }

    return ob_get_clean();
}
add_shortcode('product-filter-category', 'abbamedix_parent_product_category_checkbox_list');



// custom product filter
function my_custom_product_filter() {
    ob_start();
    ?>
    <!-- Your Custom HTML Goes Here -->
		<div class="productFilter">
			<div class="filterDropdown">
					<button class="toggleBtn" id="thc">THC</button>
					<div class="dropdown">
					<?php echo Ample_Dual_Range_Slider::render_thc_slider(); ?>
					</div>
			</div>

			<div class="filterDropdown">
					<button class="toggleBtn" id="cbd">CBD</button>
					<div class="dropdown range-second">
					<?php echo Ample_Dual_Range_Slider::render_cbd_slider(); ?>
					</div>
			</div>

			<div class="filterDropdown">
					<button class="toggleBtn" id="size">size</button>
					<div class="dropdown">
					<?php echo do_shortcode('[product_sizes]'); ?>
					</div>
			</div>

			<div class="filterDropdown">
					<button class="toggleBtn" id="dominance">dominance</button>
					<div class="dropdown">
					<?php echo do_shortcode('[product_dominance]'); ?>
					</div>
			</div>

			<div class="filterDropdown">
					<button class="toggleBtn" id="terpenes">terpenes</button>
					<div class="dropdown">
						<?php echo do_shortcode('[terpene_checkboxes]'); ?>
					</div>
			</div>

			<div class="filterDropdown">
					<button class="toggleBtn" id="brands">BRANDS</button>
					<div class="dropdown">
					<?php echo do_shortcode('[brand_checkboxes]'); ?>					
					</div>
			</div>

			<div class="filterDropdown" style="display: none;" data-abba-issue="51">
					<!-- ABBA Issue #51: Category filter hidden due to URL base category conflicts
					     Customer may want to revert immediately - easy restoration by removing style="display: none;"
					     Original functionality preserved in [category_checkboxes] shortcode for future restoration -->
					<button class="toggleBtn" id="categories">CATEGORIES</button>
					<div class="dropdown">
					<?php echo do_shortcode('[category_checkboxes]'); ?>
					</div>
			</div>
		</div>
    <?php
    return ob_get_clean();
}
add_shortcode('my_custom_filter', 'my_custom_product_filter');


// Post category dropdown
function post_category_dropdown_shortcode() {
    $args = array(
        'show_option_all' => 'Category',
        'name'            => 'category',
        'class'           => 'post-category-dropdown',
        'id'              => 'category-filter',
        'taxonomy'        => 'category',
        'echo'            => 0
    );
    return wp_dropdown_categories($args);
}
add_shortcode('post_category_dropdown', 'post_category_dropdown_shortcode');


// Filter Posts by category 
function filter_posts_by_category() {
    $category_id = intval($_POST['category']);
    $offset = isset($_POST['offset']) ? intval($_POST['offset']) : 0;

    $args = array(
        'post_type'      => 'post',
        'posts_per_page' => 9,
        'offset'         => $offset,
    );

    if ($category_id > 0) {
        $args['cat'] = $category_id;
    }

    $query = new WP_Query($args);

    if ($query->have_posts()) :
        while ($query->have_posts()) : $query->the_post(); ?>
            <div class="filtered-post filteredpostItem">
                <?php if (has_post_thumbnail()) : ?>
                    <div class="post-thumbnail">
                        <a href="<?php the_permalink(); ?>">
                            <?php the_post_thumbnail('medium'); ?>
                        </a>
                    </div>
                <?php endif; ?>
								<p>
                    <span class="postAuthor"><?php the_author(); ?> |</span>
                    <span class="postDate"><?php the_time('F j, Y'); ?></span>
                </p>
                <h3><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
								<a href="<?php the_permalink(); ?>" class="readMoreBtn">Read More »</a>
                	
                <!-- <div><?php //the_excerpt(); ?></div> -->
            </div>
        <?php endwhile;
    else :
        echo ''; // Let JS decide whether to hide the button
    endif;

    wp_reset_postdata();
    wp_die();
}


add_action('wp_ajax_filter_posts_by_category', 'filter_posts_by_category');
add_action('wp_ajax_nopriv_filter_posts_by_category', 'filter_posts_by_category');

function enqueue_ajax_filter_script() {
    ?>
    <script type="text/javascript">
        window.ajaxurl = "<?php echo admin_url('admin-ajax.php'); ?>";
    </script>
    <?php
}
add_action('wp_head', 'enqueue_ajax_filter_script');


/**
 * Retrieves all unique terpenes from published product variations with counts.
 * 
 * Queries product variations for terpene metadata, filtering by category scope if provided.
 * Returns unique terpenes with counts based on distinct parent products to avoid duplicates.
 *
 * @global wpdb $wpdb WordPress database abstraction object
 * @return array Array of terpene objects with name, slug, and count properties
 * @since 1.0.0
 */
function get_product_terpenes() {
    global $wpdb;
    
    // Scope to base categories from URL parameters if provided
    $base_categories = abba_get_base_categories();
    $scoped_variation_ids = [];
    
    if (!empty($base_categories)) {
        // Get products in base categories, then their variations
        $query_args = [
            'post_type' => 'product',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'fields' => 'ids',
            'tax_query' => [
                [
                    'taxonomy' => 'product_cat',
                    'field' => 'slug',
                    'terms' => $base_categories,
                    'operator' => 'IN'
                ]
            ]
        ];
        
        // Apply purchasable product filter for logged-in users to match API endpoint
        if (is_user_logged_in() && function_exists('ample_get_cached_purchasable_product_ids')) {
            $purchasable_ids = ample_get_cached_purchasable_product_ids();
            if (!empty($purchasable_ids)) {
                $query_args['post__in'] = $purchasable_ids;
            } else {
                // No purchasable products - return empty array
                return [];
            }
        }
        
        $scoped_query = new WP_Query($query_args);
        
        if (empty($scoped_query->posts)) {
            return []; // No products in base categories
        }
        
        // Get all variations for scoped products
        $product_ids_list = implode(',', array_map('intval', $scoped_query->posts));
        $variation_ids = $wpdb->get_col("
            SELECT ID FROM {$wpdb->posts} 
            WHERE post_type = 'product_variation' 
            AND post_parent IN ({$product_ids_list})
            AND post_status = 'publish'
        ");
        
        if (empty($variation_ids)) {
            return []; // No variations in scoped products
        }
        
        $scoped_variation_ids = $variation_ids;
    }
    
    // Query for all terpene meta keys with counts
    $where_clause = "";
    if (!empty($scoped_variation_ids)) {
        $ids_list = implode(',', array_map('intval', $scoped_variation_ids));
        $where_clause = "AND pm.post_id IN ({$ids_list})";
    }
    
    // Query terpene metadata from variations, counting unique parent products to avoid duplicates
    $terpene_data = $wpdb->get_results("
        SELECT 
            REPLACE(pm.meta_key, 'terpene_', '') as terpene_name,
            COUNT(DISTINCT p.post_parent) as product_count 
        FROM {$wpdb->postmeta} pm
        JOIN {$wpdb->posts} p ON pm.post_id = p.ID
        WHERE pm.meta_key LIKE 'terpene_%' 
        AND pm.meta_key != 'terpene_total-terpenes'
        AND pm.meta_value != '0' 
        AND pm.meta_value != ''
        {$where_clause}
        GROUP BY pm.meta_key 
        ORDER BY terpene_name ASC
    ");
    
    
    $terpenes = [];
    foreach ($terpene_data as $terpene) {
        if (!empty($terpene->terpene_name)) {
            // Format terpene name for display (capitalize, clean up)
            $display_name = ucwords(str_replace(['-', '_'], ' ', $terpene->terpene_name));
            $terpenes[] = [
                'name' => $display_name,
                'slug' => $terpene->terpene_name,
                'count' => $terpene->product_count
            ];
        }
    }
    
    return $terpenes;
}

// Dynamic terpenes filter shortcode with real product data (ABBA Issue #11)
function dynamic_product_terpenes_multiselect_shortcode() {
    ob_start();
    
    // Get real terpenes from database
    $terpenes = get_product_terpenes();
    
    if (empty($terpenes)) {
        echo '<p>No terpenes found.</p>';
        return ob_get_clean();
    }
    
    echo '<div class="ample-terpenes-filter-container">';
    echo '<div class="ample-terpenes-filter-header">';
    echo '<label class="ample-terpenes-filter-label">TERPENE OPTIONS</label>';
    echo '</div>';
    echo '<div class="ample-terpenes-options">';
    
    foreach ($terpenes as $terpene) {
        $terpene_id = 'terpene-' . sanitize_title($terpene['slug']);
        $terpene_value = $terpene['name'];
        $display_text = $terpene['name'] . ' (' . $terpene['count'] . ')';
        
        echo '<div class="filter-option">';
        echo '<input type="checkbox" id="' . esc_attr($terpene_id) . '" value="' . esc_attr($terpene_value) . '" data-terpene="' . esc_attr($terpene['slug']) . '">';
        echo '<label for="' . esc_attr($terpene_id) . '">' . esc_html($display_text) . '</label>';
        echo '</div>';
    }
    
    echo '</div>'; // .ample-terpenes-options
    
    // Auto-apply on checkbox change - no Apply button needed
    
    echo '</div>'; // .ample-terpenes-filter-container
    
    return ob_get_clean();
}

// Legacy shortcode for backward compatibility - now uses real data
function render_terpene_checkboxes_shortcode() {
    return dynamic_product_terpenes_multiselect_shortcode();
}
add_shortcode('terpene_checkboxes', 'render_terpene_checkboxes_shortcode');
add_shortcode('product_terpenes_multiselect', 'dynamic_product_terpenes_multiselect_shortcode');


/**
 * Retrieves all unique product brands from WooCommerce product attributes with counts.
 * 
 * Searches the _product_attributes meta field for brand information, filtering by
 * category scope if provided. Only uses structured brand attribute data, not title parsing.
 *
 * @global wpdb $wpdb WordPress database abstraction object
 * @return array Array of brand objects with name, slug, and count properties
 * @since 1.0.0
 */
function get_product_brands() {
    global $wpdb;
    
    // Scope to base categories from URL parameters if provided
    $base_categories = abba_get_base_categories();
    $scoped_product_ids = [];
    
    if (!empty($base_categories)) {
        // Get products only in base categories
        $query_args = [
            'post_type' => 'product',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'fields' => 'ids',
            'tax_query' => [
                [
                    'taxonomy' => 'product_cat',
                    'field' => 'slug',
                    'terms' => $base_categories,
                    'operator' => 'IN'
                ]
            ],
        ];
        
        // Apply purchasable product filter for logged-in users to match API endpoint
        if (is_user_logged_in() && function_exists('ample_get_cached_purchasable_product_ids')) {
            $purchasable_ids = ample_get_cached_purchasable_product_ids();
            if (!empty($purchasable_ids)) {
                $query_args['post__in'] = $purchasable_ids;
            } else {
                // No purchasable products - return empty array
                return [];
            }
        }
        
        $scoped_query = new WP_Query($query_args);
        $scoped_product_ids = $scoped_query->posts;
        
        if (empty($scoped_product_ids)) {
            return []; // No products in base categories
        }
    }
    
    // Query for products with brand attribute in _product_attributes
    $where_clause = "";
    if (!empty($scoped_product_ids)) {
        $ids_list = implode(',', array_map('intval', $scoped_product_ids));
        $where_clause = "AND post_id IN ({$ids_list})";
    }
    
    // Query products with brand attributes in _product_attributes meta field
    $products_with_brands = $wpdb->get_results("
        SELECT post_id, meta_value 
        FROM {$wpdb->postmeta} 
        WHERE meta_key = '_product_attributes' 
        AND meta_value LIKE '%\"brand\"%'
        {$where_clause}
    ");
    
    $brand_counts = [];
    
    foreach ($products_with_brands as $product) {
        $attributes = maybe_unserialize($product->meta_value);
        
        if (is_array($attributes) && isset($attributes['brand'])) {
            $brand_data = $attributes['brand'];
            
            // Extract brand value from attribute structure
            $brand_value = '';
            if (isset($brand_data['value']) && !empty($brand_data['value'])) {
                $brand_value = trim($brand_data['value']);
            }
            
            if (!empty($brand_value)) {
                // Handle multiple brands separated by | or ,
                $brand_names = preg_split('/[|,]/', $brand_value);
                
                foreach ($brand_names as $brand_name) {
                    $brand_name = trim($brand_name);
                    if (!empty($brand_name)) {
                        if (!isset($brand_counts[$brand_name])) {
                            $brand_counts[$brand_name] = 0;
                        }
                        $brand_counts[$brand_name]++;
                    }
                }
            }
        }
    }
    
    // Only use structured brand attributes for consistency
    
    // Convert to format matching terpenes structure
    $brands = [];
    foreach ($brand_counts as $brand_name => $count) {
        $brands[] = [
            'name' => $brand_name,
            'slug' => sanitize_title($brand_name),
            'count' => $count
        ];
    }
    
    // Sort alphabetically by name for easier finding
    usort($brands, function($a, $b) {
        return strcasecmp($a['name'], $b['name']);
    });
    
    return $brands;
}

// Dynamic brands filter shortcode with real product data (ABBA Issue #12 Phase 2)  
function dynamic_product_brands_multiselect_shortcode() {
    ob_start();
    
    // Get real brands from database
    $brands = get_product_brands();
    
    if (empty($brands)) {
        echo '<p>No brands found.</p>';
        return ob_get_clean();
    }
    
    echo '<div class="ample-brand-filter-container">';
    echo '<div class="ample-brand-filter-header">';
    echo '<label class="ample-brand-filter-label">BRAND OPTIONS</label>';
    echo '</div>';
    echo '<div class="ample-brand-options">';
    
    foreach ($brands as $brand) {
        $brand_id = 'brand-' . sanitize_title($brand['slug']);
        $brand_value = $brand['name'];
        $display_text = $brand['name'] . ' (' . $brand['count'] . ')';
        
        echo '<div class="filter-option">';
        echo '<input type="checkbox" id="' . esc_attr($brand_id) . '" value="' . esc_attr($brand_value) . '" data-brand="' . esc_attr($brand['slug']) . '">';
        echo '<label for="' . esc_attr($brand_id) . '">' . esc_html($display_text) . '</label>';
        echo '</div>';
    }
    
    echo '</div>'; // .ample-brand-options
    
    // Auto-apply on checkbox change - no Apply button needed
    
    echo '</div>'; // .ample-brand-filter-container
    
    return ob_get_clean();
}

// Legacy shortcode for backward compatibility - now uses real data
function render_brand_checkboxes_shortcode() {
    return dynamic_product_brands_multiselect_shortcode();
}
add_shortcode('brand_checkboxes', 'render_brand_checkboxes_shortcode');
add_shortcode('product_brands_multiselect', 'dynamic_product_brands_multiselect_shortcode');


// Helper function to get all unique categories from WooCommerce taxonomy (ABBA Issue #12 Phase 3)
function get_product_categories() {
    // Get all WooCommerce product categories - keep hierarchy for proper querying
    $categories = get_terms([
        'taxonomy' => 'product_cat',
        'hide_empty' => true, // Only show categories with products
        'orderby' => 'name', // Use name first, we'll sort by count after
        'order' => 'ASC'
    ]);
    
    if (is_wp_error($categories) || empty($categories)) {
        return [];
    }
    
    $formatted_categories = [];
    
    foreach ($categories as $category) {
        // Skip "Uncategorized" if it exists
        if (strtolower($category->name) === 'uncategorized') {
            continue;
        }
        
        // Build hierarchical name (Parent > Child)
        $display_name = $category->name;
        if ($category->parent > 0) {
            $parent = get_term($category->parent, 'product_cat');
            if ($parent && !is_wp_error($parent)) {
                $display_name = $parent->name . ' > ' . $category->name;
            }
        }
        
        $formatted_categories[] = [
            'name' => $display_name,
            'original_name' => $category->name,
            'slug' => $category->slug,
            'count' => $category->count,
            'parent_id' => $category->parent
        ];
    }
    
    // Sort alphabetically by display name (keeps parents and children in logical order)
    usort($formatted_categories, function($a, $b) {
        return strcasecmp($a['name'], $b['name']);
    });
    
    return $formatted_categories;
}

// Dynamic categories filter shortcode with real product data (ABBA Issue #12 Phase 3)
function dynamic_product_categories_multiselect_shortcode() {
    ob_start();
    
    // Get real categories from database
    $categories = get_product_categories();
    
    
    if (empty($categories)) {
        echo '<p>No categories found.</p>';
        return ob_get_clean();
    }
    
    echo '<div class="ample-category-filter-container">';
    echo '<div class="ample-category-filter-header">';
    echo '<label class="ample-category-filter-label">CATEGORY OPTIONS</label>';
    echo '</div>';
    echo '<div class="ample-category-options">';
    
    foreach ($categories as $category) {
        $category_id = 'category-' . sanitize_title($category['slug']);
        $category_value = $category['name'];
        
        // Create styled display text for hierarchical categories
        if (strpos($category['name'], ' > ') !== false) {
            // This is a child category with Parent > Child format
            $parts = explode(' > ', $category['name']);
            $parent_part = $parts[0];
            $child_part = $parts[1];
            $styled_text = '<span class="category-parent">' . esc_html($parent_part) . '</span>' .
                          '<span class="category-separator">&gt;</span>' . 
                          '<span class="category-child">' . esc_html($child_part) . '</span>' .
                          ' (' . $category['count'] . ')';
        } else {
            // This is a parent category
            $styled_text = esc_html($category['name']) . ' (' . $category['count'] . ')';
        }
        
        echo '<div class="filter-option">';
        echo '<input type="checkbox" id="' . esc_attr($category_id) . '" value="' . esc_attr($category_value) . '" data-category="' . esc_attr($category['slug']) . '">';
        echo '<label for="' . esc_attr($category_id) . '">' . $styled_text . '</label>';
        echo '</div>';
    }
    
    echo '</div>'; // .ample-category-options
    
    // Auto-apply on checkbox change - no Apply button needed
    
    echo '</div>'; // .ample-category-filter-container
    
    return ob_get_clean();
}

// Legacy shortcode for backward compatibility - now uses real data
function render_category_checkboxes_shortcode() {
    return dynamic_product_categories_multiselect_shortcode();
}
add_shortcode('category_checkboxes', 'render_category_checkboxes_shortcode');
add_shortcode('product_categories_multiselect', 'dynamic_product_categories_multiselect_shortcode');



// Shortcode to dynamically get and display product sizes (multi-select)
// Helper function to group sizes by range
function group_sizes_by_range($sizes) {
    $groups = [
        'Small (< 1g)' => [],
        'Medium (1-5g)' => [],
        'Large (5-30g)' => [],
        'XL (> 30g)' => [],
        'Liquids (ml)' => []
    ];
    
    foreach ($sizes as $size) {
        if (stripos($size, 'ml') !== false) {
            $groups['Liquids (ml)'][] = $size;
        } elseif (preg_match('/(\d+(?:\.\d+)?)\s*g/i', $size, $matches)) {
            $value = floatval($matches[1]);
            if ($value < 1) {
                $groups['Small (< 1g)'][] = $size;
            } elseif ($value <= 5) {
                $groups['Medium (1-5g)'][] = $size;
            } elseif ($value <= 30) {
                $groups['Large (5-30g)'][] = $size;
            } else {
                $groups['XL (> 30g)'][] = $size;
            }
        }
    }
    
    // Remove empty groups and sort sizes within groups
    foreach ($groups as $group_name => $group_sizes) {
        if (empty($group_sizes)) {
            unset($groups[$group_name]);
        } else {
            sort($groups[$group_name]);
        }
    }
    
    return $groups;
}

// ABBA Issue #51: Enhanced size grouping with product counts
function group_sizes_by_range_with_counts($size_counts) {
    $groups = [
        'Small (< 1g)' => [],
        'Medium (1-5g)' => [],
        'Large (5-30g)' => [],
        'XL (> 30g)' => [],
        'Liquids (ml)' => []
    ];
    
    foreach ($size_counts as $size => $count) {
        if (stripos($size, 'ml') !== false) {
            $groups['Liquids (ml)'][] = ['size' => $size, 'count' => $count];
        } elseif (preg_match('/(\d+(?:\.\d+)?)\s*g/i', $size, $matches)) {
            $value = floatval($matches[1]);
            if ($value < 1) {
                $groups['Small (< 1g)'][] = ['size' => $size, 'count' => $count];
            } elseif ($value <= 5) {
                $groups['Medium (1-5g)'][] = ['size' => $size, 'count' => $count];
            } elseif ($value <= 30) {
                $groups['Large (5-30g)'][] = ['size' => $size, 'count' => $count];
            } else {
                $groups['XL (> 30g)'][] = ['size' => $size, 'count' => $count];
            }
        }
    }
    
    // Remove empty groups and sort by size within groups
    foreach ($groups as $group_name => $group_sizes) {
        if (empty($group_sizes)) {
            unset($groups[$group_name]);
        } else {
            usort($groups[$group_name], function($a, $b) {
                return strcmp($a['size'], $b['size']);
            });
        }
    }
    
    return $groups;
}

function dynamic_product_sizes_multiselect_shortcode() {
    // ABBA Issue #51: Scope to base categories from URL
    $base_categories = abba_get_base_categories();
    $query_args = [
        'limit' => -1,
        'status' => 'publish'
        // Include all products for NOTIFY ME functionality
    ];
    
    if (!empty($base_categories)) {
        $query_args['category'] = $base_categories;
    }
    
    // Apply purchasable product filter for logged-in users to match API endpoint
    if (is_user_logged_in() && function_exists('ample_get_cached_purchasable_product_ids')) {
        $purchasable_ids = ample_get_cached_purchasable_product_ids();
        if (!empty($purchasable_ids)) {
            $query_args['include'] = $purchasable_ids;
        } else {
            // No purchasable products - return empty array
            return '<p>No sizes available</p>';
        }
    }
    
    // Get sizes directly from WooCommerce database instead of API call
    $products = wc_get_products($query_args);
    
    // Count products per size for display with counts
    $size_counts = [];
    
    foreach ($products as $product) {
        // Get package-sizes attribute
        $package_sizes = $product->get_attribute('package-sizes');
        if ($package_sizes) {
            // Split by comma if multiple sizes in one attribute
            $product_sizes = array_map('trim', explode(',', $package_sizes));
            foreach ($product_sizes as $size) {
                if ($size) {
                    if (!isset($size_counts[$size])) {
                        $size_counts[$size] = 0;
                    }
                    $size_counts[$size]++;
                }
            }
        }
    }
    
    
    if (empty($size_counts)) {
        return '<p>No sizes found.</p>';
    }
    
    // Group sizes logically with counts
    $grouped_sizes = group_sizes_by_range_with_counts($size_counts);
    
    // Use distinct size filter container structure (styled like THC/CBD but with unique classes)
    $html = '<div class="ample-size-filter-container" id="ample-size-container">';
    
    // Filter header
    $html .= '<div class="ample-size-filter-header">';
    $html .= '<label class="ample-size-filter-label">SIZE</label>';
    $html .= '</div>';
    
    // Size options with counts
    $html .= '<div class="ample-size-options">';
    foreach ($grouped_sizes as $group_name => $group_sizes) {
        // Calculate total count for this group
        $total_count = 0;
        $size_names = [];
        foreach ($group_sizes as $size_data) {
            $total_count += $size_data['count'];
            $size_names[] = $size_data['size'];
        }
        
        $group_key = sanitize_title($group_name);
        $html .= '<label class="filter-option">';
        $html .= '<input type="checkbox" name="size_groups[]" value="' . esc_attr($group_key) . '" data-group="' . esc_attr($group_name) . '" data-sizes="' . esc_attr(implode(',', $size_names)) . '">';
        $html .= '<span>' . esc_html($group_name) . ' (' . $total_count . ')</span>';
        $html .= '</label>';
    }
    $html .= '</div>';
    
    // Auto-apply on checkbox change - no Apply button needed
    
    $html .= '</div>';
    
    return $html;
}
add_shortcode('product_sizes', 'dynamic_product_sizes_multiselect_shortcode');


// Shortcode to get and display product dominance filter (multi-select checkboxes)
function get_product_dominance_shortcode() {
    // This creates the dominance filter dropdown with multi-select checkboxes
    // Following same pattern as size filter but for strain types
    
    // Get all strain values with counts from scoped products
    $strain_counts = get_product_strains();
    
    if (empty($strain_counts)) {
        return '<p>No strain data available</p>';
    }
    
    // Create multi-select checkbox filter matching size filter structure
    $html = '<div class="ample-dominance-filter-container" id="ample-dominance-container">';
    
    // Filter header
    $html .= '<div class="ample-dominance-filter-header">';
    $html .= '<label class="ample-dominance-filter-label">DOMINANCE</label>';
    $html .= '</div>';
    
    $html .= '<div class="ample-dominance-options">';
    
    foreach ($strain_counts as $strain => $count) {
        $strain_clean = sanitize_title($strain);
        $strain_display = esc_html(ucfirst($strain));
        
        $html .= '<div class="filter-option">';
        $html .= '<input type="checkbox" id="dominance-' . $strain_clean . '" value="' . esc_attr($strain) . '" data-strain="' . esc_attr($strain) . '">';
        $html .= '<label for="dominance-' . $strain_clean . '">' . $strain_display . ' (' . $count . ')</label>';
        $html .= '</div>';
    }
    
    $html .= '</div>';
    
    // Auto-apply on checkbox change - no Apply button needed
    
    $html .= '</div>';
    
    return $html;
}

// Helper function to get all unique strain values from products
function get_product_strains() {
    // ABBA Issue #51: Scope to base categories from URL
    $base_categories = abba_get_base_categories();
    $query_args = array(
        'status' => 'publish',
        'limit' => -1
        // Include all products for NOTIFY ME functionality
    );
    
    if (!empty($base_categories)) {
        $query_args['category'] = $base_categories;
    }
    
    // Apply purchasable product filter for logged-in users to match API endpoint
    if (is_user_logged_in() && function_exists('ample_get_cached_purchasable_product_ids')) {
        $purchasable_ids = ample_get_cached_purchasable_product_ids();
        if (!empty($purchasable_ids)) {
            $query_args['include'] = $purchasable_ids;
        } else {
            // No purchasable products - return empty array
            return [];
        }
    }
    
    // Use direct WooCommerce database query (same approach as size filter)
    $products = wc_get_products($query_args);
    
    // Count products per strain for display with counts
    $strain_counts = array();
    
    foreach ($products as $product) {
        $strain = $product->get_attribute('strain');
        if (!empty($strain)) {
            if (!isset($strain_counts[$strain])) {
                $strain_counts[$strain] = 0;
            }
            $strain_counts[$strain]++;
        }
    }
    
    
    // Sort strains alphabetically by name
    ksort($strain_counts);
    
    return $strain_counts;
}
add_shortcode('product_dominance', 'get_product_dominance_shortcode');

// Order id to product detail page
add_action( 'init', function() {
    if ( isset( $_GET['order_id'] ) && is_numeric( $_GET['order_id'] ) ) {
        $order_id = absint( $_GET['order_id'] );
        $order = wc_get_order( $order_id );

        if ( $order ) {
            foreach ( $order->get_items() as $item ) {
                $product = $item->get_product();
                if ( $product ) {
                    // Change this if using a custom detail page
                    $redirect_url = home_url( '/product/?product_id=' . $product->get_id() );
                    wp_redirect( $redirect_url );
                    exit;
                }
            }
        }

        // fallback redirect if order not found or empty
        wp_redirect( home_url() );
        exit;
    }
});


// Add class to base on product main category
add_filter('woocommerce_post_class', 'add_main_category_to_product_class', 10, 2);

function add_main_category_to_product_class($classes, $product) {
    if (!is_a($product, 'WC_Product')) {
        $product = wc_get_product(get_the_ID());
    }

    if (!$product) return $classes;

    $terms = get_the_terms($product->get_id(), 'product_cat');

    if (!empty($terms) && !is_wp_error($terms)) {
        usort($terms, function ($a, $b) {
            return $a->term_order - $b->term_order;
        });

        $main_cat_slug = $terms[0]->slug;
        $classes[] = 'category-' . sanitize_html_class($main_cat_slug);
    }

    return $classes;
}

// Display variation swatches on shop page
add_action('woocommerce_after_shop_loop_item', 'display_variation_swatches', 15);
function display_variation_swatches() {
    global $product;
    
    if (!$product->is_type('variable')) {
        return;
    }
    
    $attributes = $product->get_variation_attributes();
    $available_variations = $product->get_available_variations();
    
    if (empty($attributes)) {
        return;
    }
    
    // Prepare minimal variation data for JavaScript (eliminating AJAX calls)
    $variation_data = [];
    foreach ($available_variations as $variation) {
        $variation_data[] = [
            'variation_id' => $variation['variation_id'] ?? 0,
            'display_price' => $variation['display_price'] ?? '',
            'price' => $variation['price'] ?? '', 
            'regular_price' => $variation['regular_price'] ?? '',
            'attributes' => $variation['attributes'] ?? [],
            'is_in_stock' => $variation['is_in_stock'] ?? false
        ];
    }
    
    echo '<div class="shop-variation-swatches" data-product-id="' . $product->get_id() . '" data-variations="' . esc_attr(wp_json_encode($variation_data)) . '">';
    $currency_symbol = get_woocommerce_currency_symbol();
    $price_html = $product->get_price_html();
    $price = '';

    if ($price_html) {
        libxml_use_internal_errors(true);
        $dom = new DOMDocument();
        $dom->loadHTML($price_html);
        libxml_clear_errors();

        $price_elements = $dom->getElementsByTagName('span');
        foreach ($price_elements as $element) {
            if ($element->getAttribute('class') === 'woocommerce-Price-currencySymbol') {
                $price_node = $element->nextSibling;
                $price = $price_node->nodeValue;
                break;
            }
        }
    }

    // Check if ALL variations are out of stock
    $all_oos = true;
    foreach ($available_variations as $variation) {
        if ($variation['is_in_stock']) {
            $all_oos = false;
            break;
        }
    }

    foreach ($attributes as $attribute_name => $options) {
        $attribute_label = wc_attribute_label($attribute_name);
        
        echo '<div class="swatch-group">';
        echo '<div class="swatches">';
        
        foreach ($options as $option) {
            $class = 'swatch-item';
            $attribute_slug = str_replace('pa_', '', $attribute_name);
            
            // Check stock for this option
            $in_stock_option = false;
            foreach ($available_variations as $variation) {
                if (isset($variation['attributes']["attribute_$attribute_name"]) && strtolower($variation['attributes']["attribute_$attribute_name"]) === strtolower($option)) {
                    if ($variation['is_in_stock']) {
                        $in_stock_option = true;
                    }
                    break;
                }
            }
            
            if (!$in_stock_option) {
                $class .= ' disabled';
            }
            
            $stock_status = $in_stock_option ? 'in' : 'out';
            
            // Render swatch
            if (strpos($attribute_name, 'color') !== false || strpos($attribute_name, 'colour') !== false) {
                $class .= ' color-swatch';
                $color_value = get_color_value($option);
                $style = 'background-color: ' . $color_value . ';';
                echo '<span class="' . $class . '" data-attribute="' . esc_attr($attribute_name) . '" data-value="' . esc_attr($option) . '" data-stock-status="' . $stock_status . '" style="' . $style . '" title="' . esc_attr($option) . '"></span>';
            } else {
                $class .= ' text-swatch';
                echo '<span class="' . $class . '" data-attribute="' . esc_attr($attribute_name) . '" data-value="' . esc_attr($option) . '" data-stock-status="' . $stock_status . '">' . esc_html($option) . '</span>';
            }
        }
        
        echo '</div>';
        echo '</div>';
    }

    echo '<input type="hidden" id="prod-cur" value="' . esc_attr($currency_symbol) . '">';
    echo '<input type="hidden" id="prod-price" value="' . esc_attr($price) . '">';

    echo '<div class="variation-info">';
    echo '<span class="variation-price"></span>';
    echo '<span class="variation-stock"></span>';
    echo '</div>';
    
    echo '<div class="variation-add-to-cart">';
    
    if ($all_oos) {
        // All variations out of stock → show Notify Me
        echo '<button type="button" class="single_add_to_cart_button button alt notify-me-button active" data-product-id="' . $product->get_id() . '">NOTIFY ME</button>';
    } else {
        // Normal form when some variations are in stock
        echo '<form class="cart" method="post" enctype="multipart/form-data">';
        echo '<input type="hidden" name="add-to-cart" value="' . $product->get_id() . '">';
        echo '<input type="hidden" name="product_id" value="' . $product->get_id() . '">';
        echo '<input type="hidden" name="variation_id" class="variation_id" value="">';    
        echo '<input type="hidden" name="quantity" value="1">';
        echo '<button type="submit" class="single_add_to_cart_button button alt">SELECT SIZE</button>';
        echo '</form>';
    }

    echo '</div>'; // .variation-add-to-cart
    echo '</div>'; // .shop-variation-swatches

}




// COMMENTED OUT: No longer needed - variations embedded in HTML data attributes
// add_action('wp_ajax_get_variations_for_product', 'get_variations_for_product');
// add_action('wp_ajax_nopriv_get_variations_for_product', 'get_variations_for_product');
function get_variations_for_product() {
    $product_id = intval($_POST['product_id']);
    $product = wc_get_product($product_id);

    if (!$product || !$product->is_type('variable')) {
        wp_send_json_error();
    }

    $available_variations = $product->get_available_variations();
    $variations = [];

    foreach ($available_variations as $variation_data) {
        $variation_id = $variation_data['variation_id'];
        $variation_obj = new WC_Product_Variation($variation_id);

        // Add price_html
        $variation_data['price_html'] = $variation_obj->get_price_html();

        $variations[] = $variation_data;
    }

    wp_send_json_success($variations);
}

// function get_variations_for_product() {
//     $product_id = intval($_POST['product_id']);
//     $product = wc_get_product($product_id);

//     if (!$product || !$product->is_type('variable')) {
//         wp_send_json_error();
//     }

//     $variations = $product->get_available_variations();
//     wp_send_json_success($variations);
// }

add_action('wp_enqueue_scripts', 'enqueue_custom_scripts');
function enqueue_custom_scripts() {
    wp_enqueue_script('jquery');
    wp_enqueue_script('wc-add-to-cart-variation'); // Needed for variation handling
    wp_localize_script('jquery', 'wc_add_to_cart_params', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce'    => wp_create_nonce('woocommerce-cart'),
    ));
    wp_localize_script('jquery', 'wc_notify_me_params', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce'    => wp_create_nonce('view_order_doc_nonce'),
        'is_logged_in' => is_user_logged_in(),
        'login_url' => wc_get_page_permalink('myaccount'),
    ));
}

// Strain type
function strain_brand_thc_cbd_shortcode() {
    if (!is_product()) return ''; // Only show on product pages

    global $product;

    // Define your attribute slugs here
    $attribute_slugs = array('pa_strain', 'pa_brand', 'pa_thc', 'pa_cbd');

    $output = '<div class="product-attributes">';

    foreach ($attribute_slugs as $slug) {
        $terms = wc_get_product_terms($product->get_id(), $slug, array('fields' => 'names'));
        if (!empty($terms)) {
            $label = wc_attribute_label($slug);
            $output .= '<p><strong>' . esc_html($label) . ':</strong> ' . implode(', ', $terms) . '</p>';
        }
    }

    $output .= '</div>';

    return $output;
}
add_shortcode('show_product_attributes', 'strain_brand_thc_cbd_shortcode');

// Related Product Count
function woocommerce_product_count_shortcode() {
    $count = wp_count_posts('product');
    $total = isset($count->publish) ? $count->publish : 0;

    // Customize your before and after labels
    $before = '';
    $after = ' Products';

    return $before . $total . $after;
}
add_shortcode('product_count', 'woocommerce_product_count_shortcode');


// Register shortcode to show Rx Deduction Amount
function rx_deduction_amount_shortcode($atts) {
    global $product;

    // Allow passing a product ID manually
    $atts = shortcode_atts([
        'id' => null,
    ], $atts, 'rx_deduction');

    // Get product ID: from attribute or current product
    $product_id = $atts['id'] ? intval($atts['id']) : ($product ? $product->get_id() : null);

    if (!$product_id) {
        return ''; // No product context
    }

    $rx_deduction = get_post_meta($product_id, '_rx_deduction', true);

    if (!empty($rx_deduction)) {
        return '<p class="rx-deduction" style="font-weight:bold; color:#007C00;">Rx Deduction Amount: ' . wc_price(floatval($rx_deduction)) . '</p>';
    }

    return '';
}
add_shortcode('rx_deduction', 'rx_deduction_amount_shortcode');

// Price per gram
function shortcode_price_per_gram() {
    global $product;

    if ( ! is_a( $product, 'WC_Product' ) ) return '';

    $price = floatval( $product->get_price() );
    $weight = floatval( $product->get_weight() ); // in kg

    if ( $weight <= 0 ) return '';

    $grams = $weight * 1000;
    $price_per_gram = $price / $grams;

    return '<p><strong>Price per gram:</strong> $' . number_format( $price_per_gram, 4 ) . '</p>';
}
add_shortcode( 'price_per_gram', 'shortcode_price_per_gram' );

// Update label pa_package-sizes to Package Size
add_filter( 'woocommerce_attribute_label', 'custom_attribute_label_change', 10, 2 );
function custom_attribute_label_change( $label, $name ) {
    if ( $name === 'pa_package-sizes' ) {
        $label = 'Package Size';
    }
    return $label;
}

// add_action('init', function () {
//     if (isset($_POST['login']) && isset($_POST['username']) && isset($_POST['password'])) {
//         $creds = array(
//             'user_login'    => sanitize_text_field($_POST['username']),
//             'user_password' => $_POST['password'],
//             'remember'      => true,
//         );
//         $user = wp_signon($creds, false);

//         if (is_wp_error($user)) {
//             wc_add_notice($user->get_error_message(), 'error');
//         } else {
//             wp_redirect(home_url('/my-account/'));
//             exit;
//         }
//     }
// });

function custom_user_order_history_shortcode() {
    if (!is_user_logged_in()) {
        return '<p>Please log in to view your order history.</p>';
    }

    $current_user_id = get_current_user_id();
    $customer_orders = wc_get_orders(array(
        'customer_id' => $current_user_id,
        'limit' => -1,
        'orderby' => 'date',
        'order' => 'DESC',
    ));

    if (empty($customer_orders)) {
        return '<p>No orders found.</p>';
    }

    ob_start();
    echo '<table class="woocommerce-orders-table">';
    echo '<thead><tr><th>Order</th><th>Date</th><th>Status</th><th>Total</th><th>Actions</th></tr></thead>';

    foreach ($customer_orders as $order) {
        echo '<tr>';
        echo '<td>#' . $order->get_id() . '</td>';
        echo '<td>' . $order->get_date_created()->date('Y-m-d') . '</td>';
        echo '<td>' . wc_get_order_status_name($order->get_status()) . '</td>';
        echo '<td>' . $order->get_formatted_order_total() . '</td>';
        echo '<td><a href="' . esc_url($order->get_view_order_url()) . '">View</a></td>';
        echo '</tr>';
    }

    echo '</table>';
    return ob_get_clean();
}
add_shortcode('user_order_history', 'custom_user_order_history_shortcode');


// Function to get array of category and related sub-categories
function get_woocommerce_categories_hierarchy_with_slugs() {
    /*
    [
        'Parent Category 1' => [
            ['name' => 'Sub Category 1', 'slug' => 'sub-category-1'],
            ['name' => 'Sub Category 2', 'slug' => 'sub-category-2'],
        ],
        ...
    ]
    */

    $categories_hierarchy = [];

    // Get all parent product categories
    $parent_categories = get_terms([
        'taxonomy'   => 'product_cat',
        'hide_empty' => false,
        'parent'     => 0
    ]);

    foreach ($parent_categories as $parent_cat) {
        // Get sub-categories for each parent
        $sub_categories = get_terms([
            'taxonomy'   => 'product_cat',
            'hide_empty' => false,
            'parent'     => $parent_cat->term_id
        ]);

        $sub_data = [];

        foreach ($sub_categories as $sub_cat) {
            $sub_data[] = [
                'name' => $sub_cat->name,
                'slug' => $sub_cat->slug
            ];
        }

        $categories_hierarchy[$parent_cat->name] = $sub_data;
    }

    return $categories_hierarchy;
}

// Function to get array of product-tags and related sub-categories
function get_tag_subcategory_structure() {
    /*
        [
            [
                'tag' => ['name' => 'Outdoor', 'slug' => 'outdoor'],
                'subcategories' => [
                ['name' => 'Sativa', 'slug' => 'sativa'],
                ['name' => 'Hybrid', 'slug' => 'hybrid'],
                ]
            ],
            ...
            ]
    */

    $structured_data = [];

    // Get all product tags
    $tags = get_terms([
        'taxonomy'   => 'product_tag',
        'hide_empty' => true,
    ]);

    foreach ($tags as $tag) {
        $tag_slug = $tag->slug;
        $tag_name = $tag->name;

        // Get all products associated with this tag
        $products = get_posts([
            'post_type'      => 'product',
            'posts_per_page' => -1,
            'fields'         => 'ids',
            'tax_query'      => [
                [
                    'taxonomy' => 'product_tag',
                    'field'    => 'slug',
                    'terms'    => $tag_slug,
                ],
            ],
        ]);

        $subcategory_map = [];

        foreach ($products as $product_id) {
            $categories = get_the_terms($product_id, 'product_cat');

            if (!empty($categories) && !is_wp_error($categories)) {
                foreach ($categories as $cat) {
                    if ($cat->parent != 0) {
                        $slug = $cat->slug;
                        // Prevent duplicates by slug
                        $subcategory_map[$slug] = [
                            'name' => $cat->name,
                            'slug' => $slug
                        ];
                    }
                }
            }
        }

        $structured_data[] = [
            'tag' => [
                'name' => $tag_name,
                'slug' => $tag_slug
            ],
            'subcategories' => array_values($subcategory_map)
        ];
    }

    return $structured_data;
}

// Shortcode for registration error messages
add_shortcode('reg_form_message', function () {
    if (isset($_GET['reg_msg'])){
        if ($_GET['reg_msg'] === 'email_exists') {
            return '<div class="alert alert-danger">An account with this email already exists.</div>';
        } else {
            return '<div class="alert alert-danger">Something went wrong. Please try again.</div>';
        } 
    } 
    return '';
});



// Used on Home page popular categories
// Universal shortcode for showing only subcategories of a parent category
// Usage: [show_subcats parent="dried-flower"]
add_shortcode('show_subcats', function ($atts) {
    $a = shortcode_atts([
        'taxonomy'     => 'product_cat', // WooCommerce categories
        'parent'       => '',            // parent slug, name, or ID
        'parent_by'    => 'slug',        // slug|id|name
        'hide_empty'   => 'true',
        'orderby'      => 'name',
        'order'        => 'ASC',
        'columns'      => '4',
        'show_image'   => 'true',
        'image_size'   => 'woocommerce_thumbnail',
        'show_count'   => 'false',
        'class'        => '',
    ], $atts, 'show_subcats');

    // Resolve parent term
    $taxonomy = sanitize_key($a['taxonomy']);
    $parent_term = null;

    if ($a['parent_by'] === 'id' && is_numeric($a['parent'])) {
        $parent_term = get_term((int)$a['parent'], $taxonomy);
    } elseif ($a['parent_by'] === 'name') {
        $parent_term = get_term_by('name', $a['parent'], $taxonomy);
    } else { // default slug
        $parent_term = get_term_by('slug', $a['parent'], $taxonomy);
    }

    if (!$parent_term || is_wp_error($parent_term)) {
        return '<div class="subcats error">Parent category not found.</div>';
    }

    // Query subcategories
    $args = [
        'taxonomy'   => $taxonomy,
        'hide_empty' => filter_var($a['hide_empty'], FILTER_VALIDATE_BOOLEAN),
        'parent'     => (int)$parent_term->term_id,
        'orderby'    => sanitize_text_field($a['orderby']),
        'order'      => sanitize_text_field($a['order']),
    ];
    $terms = get_terms($args);
    if (is_wp_error($terms) || empty($terms)) {
        return '<div class="subcats empty">No subcategories found.</div>';
    }

    $columns = max(1, (int)$a['columns']);
    $show_image = filter_var($a['show_image'], FILTER_VALIDATE_BOOLEAN);
    $show_count = filter_var($a['show_count'], FILTER_VALIDATE_BOOLEAN);
    $image_size = sanitize_key($a['image_size']);
    $extra_class = sanitize_html_class($a['class']);

    ob_start(); ?>
    <ul class="subcats grid cols-<?php echo esc_attr($columns); ?> <?php echo esc_attr($extra_class); ?>">
        <?php
        $all_slugs = "";
        foreach ($terms as $term): 
            if ($all_slugs == "") {
                $all_slugs .= $term->slug;
            } else {
                $all_slugs .= "+" . $term->slug;
            }
            
            $url = trailingslashit( site_url( '/product-filter/' . $term->slug ) );
            ?>
            <li class="subcat item">
                <a href="<?php echo esc_url( $url ); ?>" class="subcat-link">
                    <?php echo esc_html($term->name); ?>
                </a>
            </li>
        <?php endforeach; 
        $url = trailingslashit( site_url( '/product-filter/' . $all_slugs ) );
        
        ?>
        <li class="subcat item">
            <a href="<?php echo esc_url( $url ); ?>" class="subcat-link">
                SHOP ALL <?php echo $parent_term->name; ?>
            </a>
        </li>
    </ul>
    
    <?php
    return ob_get_clean();
});

/**
 * Retrieves the brand for a specific WooCommerce product.
 * 
 * Attempts to extract brand information from product attributes in order of priority:
 * 1. WooCommerce attribute 'brand' 
 * 2. _product_attributes meta field brand data
 * Returns fallback 'Brand' if no brand information found.
 *
 * @param WC_Product|null $product WooCommerce product object
 * @return string Brand name or 'Brand' if not found
 * @since 1.0.0
 */
function get_product_brand($product) {
    if (!$product) {
        return 'Brand';
    }
    
    // Try to get brand from WooCommerce attributes first
    $brand = $product->get_attribute('brand');
    
    if (!empty($brand)) {
        return trim($brand);
    }
    
    // If no brand attribute, check if we can extract from _product_attributes meta
    global $wpdb;
    $product_attributes = get_post_meta($product->get_id(), '_product_attributes', true);
    
    if (is_array($product_attributes) && isset($product_attributes['brand'])) {
        $brand_data = $product_attributes['brand'];
        if (isset($brand_data['value']) && !empty($brand_data['value'])) {
            return trim($brand_data['value']);
        }
    }
    
    return 'Brand'; // Default fallback - only use actual brand attributes
}

// Helper function to get terpene total percentage for a product (ABBA Issue #12)
function get_product_terpene_total($product) {
    if (!$product) {
        return '';
    }
    
    // Check parent product first
    $terpene_total = get_post_meta($product->get_id(), 'terpene_total-terpenes', true);
    if (!empty($terpene_total) && $terpene_total != '0') {
        return $terpene_total;
    }
    
    // If variable product, check variations
    if ($product->is_type('variable')) {
        $variations = $product->get_children();
        foreach ($variations as $variation_id) {
            $variation_total = get_post_meta($variation_id, 'terpene_total-terpenes', true);
            if (!empty($variation_total) && $variation_total != '0') {
                return $variation_total;
            }
        }
    }
    
    return '';
}

// Helper function to get terpenes for a specific product (ABBA Issue #12)
function get_single_product_terpenes($product) {
    if (!$product) {
        return 'Terpenes';
    }
    
    global $wpdb;
    
    // For variable products, check variations. For simple products, check the product itself.
    $post_ids = [$product->get_id()];
    
    if ($product->is_type('variable')) {
        $variations = $product->get_children();
        if (!empty($variations)) {
            $post_ids = $variations;
        }
    }
    
    // Get all terpene meta fields for this product or its variations
    $post_ids_string = implode(',', array_map('intval', $post_ids));
    $terpenes = $wpdb->get_results("
        SELECT meta_key, MAX(CAST(meta_value AS DECIMAL(5,2))) as max_value 
        FROM {$wpdb->postmeta} 
        WHERE post_id IN ({$post_ids_string})
        AND meta_key LIKE 'terpene_%' 
        AND meta_key != 'terpene_total-terpenes'
        AND meta_value != '' 
        AND CAST(meta_value AS DECIMAL(5,2)) > 0
        GROUP BY meta_key
        ORDER BY max_value DESC
        LIMIT 3
    ");
    
    if (empty($terpenes)) {
        // Better fallback - show product categories instead of hardcoded terpenes
        $product_categories = wp_get_post_terms($product->get_id(), 'product_cat', array('fields' => 'names'));
        if (!empty($product_categories)) {
            return implode(' - ', array_slice($product_categories, 0, 3));
        }
        return 'Terpene data not available'; // Last resort fallback
    }
    
    $terpene_names = [];
    foreach ($terpenes as $terpene) {
        // Clean up terpene name from meta_key
        $terpene_name = str_replace('terpene_', '', $terpene->meta_key);
        $terpene_name = ucwords(str_replace(['-', '_'], ' ', $terpene_name));
        $terpene_names[] = $terpene_name;
    }
    
    return implode(' - ', $terpene_names);
}

/**
 * Retrieves terpenes array for a specific WooCommerce product.
 * 
 * Queries terpene metadata from product variations (for variable products) or the product itself
 * (for simple products). Returns all terpenes with non-zero values, sorted by concentration.
 * Previously limited to top 3 terpenes, now returns all for accurate filtering.
 *
 * @param WC_Product|null $product WooCommerce product object
 * @global wpdb $wpdb WordPress database abstraction object
 * @return array Array of terpene names formatted for display
 * @since 1.0.0
 */
function get_single_product_terpenes_array($product) {
    if (!$product) {
        return [];
    }
    
    global $wpdb;
    
    // For variable products, check variations. For simple products, check the product itself.
    $post_ids = [$product->get_id()];
    
    if ($product->is_type('variable')) {
        $variations = $product->get_children();
        if (!empty($variations)) {
            $post_ids = $variations;
        }
    }
    
    // Get all terpene meta fields for this product or its variations
    $post_ids_string = implode(',', array_map('intval', $post_ids));
    $terpenes = $wpdb->get_results("
        SELECT meta_key, MAX(CAST(meta_value AS DECIMAL(5,2))) as max_value 
        FROM {$wpdb->postmeta} 
        WHERE post_id IN ({$post_ids_string})
        AND meta_key LIKE 'terpene_%' 
        AND meta_key != 'terpene_total-terpenes'
        AND meta_value != '' 
        AND CAST(meta_value AS DECIMAL(5,2)) > 0
        GROUP BY meta_key
        ORDER BY max_value DESC
    ");
    
    if (empty($terpenes)) {
        return [];
    }
    
    $terpene_names = [];
    foreach ($terpenes as $terpene) {
        // Clean up terpene name from meta_key
        $terpene_name = str_replace('terpene_', '', $terpene->meta_key);
        $terpene_name = ucwords(str_replace(['-', '_'], ' ', $terpene_name));
        $terpene_names[] = $terpene_name;
    }
    
    return $terpene_names;
}

// Customize attribute labels in Additional Information tab (ABBA Issue #49)
add_filter('woocommerce_display_product_attributes', 'customize_attribute_labels', 5, 2);
function customize_attribute_labels($product_attributes, $product) {
    if (!$product_attributes) {
        return $product_attributes;
    }
    
    // Change attribute labels
    foreach ($product_attributes as $key => &$attribute) {
        if (isset($attribute['label'])) {
            // Change 'STRAIN' to 'Dominance'
            if ($attribute['label'] === 'STRAIN') {
                $attribute['label'] = 'Dominance';
            }
            // Fix 'BRAND' capitalization to 'Brand' 
            elseif ($attribute['label'] === 'BRAND' || $attribute['label'] === 'Brands') {
                $attribute['label'] = 'Brand';
            }
        }
    }
    return $product_attributes;
}

// Add terpenes to WooCommerce Additional Information tab with percentages (ABBA Issue #49)
add_filter('woocommerce_display_product_attributes', 'add_terpenes_to_additional_info', 10, 2);
function add_terpenes_to_additional_info($product_attributes, $product) {
    if (!$product) {
        return $product_attributes;
    }
    
    // Get terpenes with percentages for this product
    $terpenes_with_percentages = get_single_product_terpenes_with_percentages($product);
    
    if (!empty($terpenes_with_percentages)) {
        $terpenes_string = implode(', ', $terpenes_with_percentages);
        
        // Add terpene total percentage if available
        $terpene_total = get_product_terpene_total($product);
        if (!empty($terpene_total)) {
            $terpenes_string = 'Total: ' . $terpene_total . '% (' . $terpenes_string . ')';
        }
        
        $product_attributes['terpenes'] = array(
            'label' => __('Terpenes', 'woocommerce'),
            'value' => $terpenes_string
        );
    }
    
    return $product_attributes;
}

// Helper function to get terpenes with percentages for Additional Information tab (ABBA Issue #49)
function get_single_product_terpenes_with_percentages($product) {
    if (!$product) {
        return [];
    }
    
    global $wpdb;
    
    // For variable products, check variations. For simple products, check the product itself.
    $post_ids = [$product->get_id()];
    
    if ($product->is_type('variable')) {
        $variations = $product->get_children();
        if (!empty($variations)) {
            $post_ids = $variations;
        }
    }
    
    // Get all terpene meta fields with percentages for this product or its variations
    $post_ids_string = implode(',', array_map('intval', $post_ids));
    $terpenes = $wpdb->get_results("
        SELECT meta_key, MAX(CAST(meta_value AS DECIMAL(5,2))) as max_value 
        FROM {$wpdb->postmeta} 
        WHERE post_id IN ({$post_ids_string})
        AND meta_key LIKE 'terpene_%' 
        AND meta_key != 'terpene_total-terpenes'
        AND meta_value != '' 
        AND CAST(meta_value AS DECIMAL(5,2)) > 0
        GROUP BY meta_key
        ORDER BY max_value DESC
        LIMIT 10
    ");
    
    if (empty($terpenes)) {
        return [];
    }
    
    $terpene_names_with_percentages = [];
    foreach ($terpenes as $terpene) {
        // Clean up terpene name from meta_key
        $terpene_name = str_replace('terpene_', '', $terpene->meta_key);
        $terpene_name = ucwords(str_replace(['-', '_'], ' ', $terpene_name));
        
        // Format with percentage
        $terpene_names_with_percentages[] = $terpene_name . ' (' . $terpene->max_value . '%)';
    }
    
    return $terpene_names_with_percentages;
}

// Helper function to get terpene icon data with fuzzy name matching (ABBA Issue #49)
function get_terpene_icon_data($terpene_name) {
    if (empty($terpene_name)) {
        return ['type' => 'fallback', 'value' => 'limonene'];
    }
    
    // Get WordPress uploads directory URL
    $uploads_dir = wp_upload_dir();
    $base_url = $uploads_dir['baseurl'] . '/terp-icons/';
    
    // Clean up the database terpene name for matching
    $clean_name = strtolower(trim($terpene_name));
    
    // Strip common terpene prefixes for fuzzy matching
    $prefixes_to_remove = ['terpene_', 'beta-', 'alpha-', 'd-', 'trans-', 'gamma-', 'cis-'];
    foreach ($prefixes_to_remove as $prefix) {
        if (strpos($clean_name, $prefix) === 0) {
            $clean_name = str_replace($prefix, '', $clean_name);
            break; // Only remove the first matching prefix
        }
    }
    
    // Handle special name variations and map to available Abba icon filenames
    $icon_mapping = [
        // Exact matches for available icons
        'bisabolol' => 'Abba_Terps_Bisabolol.png',
        'borneol' => 'Abba_Terps_Borneol.png',
        'camphene' => 'Abba_Terps_Camphene.png',
        'carene' => 'Abba_Terps_Carene.png',
        'caryophyllene' => 'Abba_Terps_Caryophyllene.png',
        'eucalyptol' => 'Abba_Terps_Eucalyptol.png',
        'farnesene' => 'Abba_Terps_Farnesene.png',
        'geraniol' => 'Abba_Terps_Geraniol.png',
        'humulene' => 'Abba_Terps_Humulene.png',
        'limonene' => 'Abba_Terps_Limonene.png',
        'linalool' => 'Abba_Terps_Linalool.png',
        'myrcene' => 'Abba_Terps_Myrcene.png',
        'nerolidol' => 'Abba_Terps_Nerolidol.png',
        'ocimene' => 'Abba_Terps_Ocimene.png',
        'pinene' => 'Abba_Terps_Pinene.png',
        'sabinene' => 'Abba_Terps_Sabinene.png',
        'terpineol' => 'Abba_Terps_Terpineol.png',
        'terpinolene' => 'Abba_Terps_Terpinolene.png',
        
        // Handle common name variations found in database
        '3-carene' => 'Abba_Terps_Carene.png',
        'delta-3-carene' => 'Abba_Terps_Carene.png',
        'alpha-bisabolol' => 'Abba_Terps_Bisabolol.png',
        'alpha-humulene' => 'Abba_Terps_Humulene.png',
        'alpha-pinene' => 'Abba_Terps_Pinene.png',
        'beta-caryophyllene' => 'Abba_Terps_Caryophyllene.png',
        'beta-myrcene' => 'Abba_Terps_Myrcene.png',
        'beta-pinene' => 'Abba_Terps_Pinene.png',
        'trans-beta-caryophyllene' => 'Abba_Terps_Caryophyllene.png',
        'trans-caryophyllene' => 'Abba_Terps_Caryophyllene.png',
        'alpha-terpineol' => 'Abba_Terps_Terpineol.png',
        'gamma-terpinene' => 'Abba_Terps_Terpinolene.png', // Close match
    ];
    
    // Check for exact match first
    if (isset($icon_mapping[$clean_name])) {
        return ['type' => 'url', 'value' => $base_url . $icon_mapping[$clean_name]];
    }
    
    // Try fuzzy matching - check if any key contains the clean name or vice versa
    foreach ($icon_mapping as $key => $filename) {
        if (strpos($key, $clean_name) !== false || strpos($clean_name, $key) !== false) {
            return ['type' => 'url', 'value' => $base_url . $filename];
        }
    }
    
    // Fallback to existing hardcoded CSS classes for unmatched terpenes
    $fallback_classes = ['limonene', 'linalool', 'myRecene'];
    
    // Use a simple hash-based selection for consistent fallback
    $fallback_index = abs(crc32($terpene_name)) % count($fallback_classes);
    return ['type' => 'fallback', 'value' => $fallback_classes[$fallback_index]];
}


// Logos Grid Shortcode with Clickable Links
function custom_logos_grid_shortcode() {

    $logos = array(
        'Abba Vets' => array(
            'img' => 'https://groiq.ca/ecommerce-test-api/wp-content/uploads/2025/06/av-logo.jpg'
        ),
        'bl' => array(
            'img' => 'https://groiq.ca/ecommerce-test-api/wp-content/uploads/2025/06/being-logo.jpg'
        ),
        'Box Hot' => array(
            'img' => 'https://groiq.ca/ecommerce-test-api/wp-content/uploads/2025/06/bf-logo.jpg'
        ),
        'BoxHot' => array(
            'img' => 'https://groiq.ca/ecommerce-test-api/wp-content/uploads/2025/06/cg-logo.jpg'
        ),
        'C3 Innovative Solutions Inc.' => array(
            'img' => 'https://groiq.ca/ecommerce-test-api/wp-content/uploads/2025/06/dcs-logo.jpg'
        ),
        'Carmel Cannabis' => array(
            'img' => 'https://groiq.ca/ecommerce-test-api/wp-content/uploads/2025/06/kp-logo.jpg'
        ),
        'DEBUNK' => array(
            'img' => 'https://groiq.ca/ecommerce-test-api/wp-content/uploads/2025/06/noon-logo.jpg'
        ),
        'Decibel' => array(
            'img' => 'https://groiq.ca/ecommerce-test-api/wp-content/uploads/2025/06/earthwolf-logo.jpg'
        ),
        'Dymond Concentrates' => array(
            'img' => 'https://groiq.ca/ecommerce-test-api/wp-content/uploads/2025/06/filed-trip-logo.jpg'
        ),
        'Emprise' => array(
            'img' => 'https://groiq.ca/ecommerce-test-api/wp-content/uploads/2025/06/emblem-logo.jpg'
        ),
        'Foray' => array(
            'img' => 'https://groiq.ca/ecommerce-test-api/wp-content/uploads/2025/06/foray-logo.jpg'
        ),
        "Farmer's Cut" => array(
            'img' => 'https://groiq.ca/ecommerce-test-api/wp-content/uploads/2025/06/fuga-logo.jpg'
        ),
        'General Admission' => array(
            'img' => 'https://groiq.ca/ecommerce-test-api/wp-content/uploads/2025/06/gron-logo.jpg'
        ),
        'Glacial Gold' => array(
            'img' => 'https://groiq.ca/ecommerce-test-api/wp-content/uploads/2025/06/olli-logo.jpg'
        ),
        'Homestead' => array(
            'img' => 'https://groiq.ca/ecommerce-test-api/wp-content/uploads/2025/06/mpl-logo.jpg'
        ),
        'Hot Box' => array(
            'img' => 'https://groiq.ca/ecommerce-test-api/wp-content/uploads/2025/06/opticann-logo.jpg'
        ),
        'MTL' => array(
            'img' => 'https://groiq.ca/ecommerce-test-api/wp-content/uploads/2025/06/mtl-logo.jpg'
        ),
        'MTL Cannabis' => array(
            'img' => 'https://groiq.ca/ecommerce-test-api/wp-content/uploads/2025/06/replay-logo.jpg'
        ),
        'Northbound' => array(
            'img' => 'https://groiq.ca/ecommerce-test-api/wp-content/uploads/2025/06/rho-logo.jpg'
        ),
        'Rubicon' => array(
            'img' => 'https://groiq.ca/ecommerce-test-api/wp-content/uploads/2025/06/polar-logo.jpg'
        ),
        'Token' => array(
            'img' => 'https://groiq.ca/ecommerce-test-api/wp-content/uploads/2025/06/proofly-logo.jpg'
        ),
        'Abba Medix' => array(
            'img' => 'https://groiq.ca/ecommerce-test-api/wp-content/uploads/2025/06/opticann-logo.jpg'
        ),
        'Joint Craft' => array(
            'img' => 'https://groiq.ca/ecommerce-test-api/wp-content/uploads/2025/06/proofly-logo.jpg'
        ),
        "Jungl' CakeJungl' Cake" => array(
            'img' => 'https://groiq.ca/ecommerce-test-api/wp-content/uploads/2025/06/mtl-logo.jpg'
        ),
        'Loosh Inc.' => array(
            'img' => 'https://groiq.ca/ecommerce-test-api/wp-content/uploads/2025/06/foray-logo.jpg'
        ),
        'Lowkey' => array(
            'img' => 'https://groiq.ca/ecommerce-test-api/wp-content/uploads/2025/06/emblem-logo.jpg'
        ),
        'Medipharm Labs' => array(
            'img' => 'https://groiq.ca/ecommerce-test-api/wp-content/uploads/2025/06/kp-logo.jpg'
        ),
        'ufeelu' => array(
            'img' => 'https://groiq.ca/ecommerce-test-api/wp-content/uploads/2025/06/being-logo.jpg'
        ),
        'Wayfarer' => array(
            'img' => 'https://groiq.ca/ecommerce-test-api/wp-content/uploads/2025/06/cg-logo.jpg'
        )
    );

    $brands = get_terms( array(
        'taxonomy'   => 'pa_brand',
        'hide_empty' => true,
    ) );

    $visible_brands = array();

    foreach ( $brands as $brand ) {
        $products = wc_get_products( array(
            'status'    => 'publish',
            'limit'     => 1,
            'tax_query' => array(
                array(
                    'taxonomy' => 'pa_brand',
                    'field'    => 'term_id',
                    'terms'    => $brand->term_id,
                ),
            ),
        ) );

        if ( ! empty( $products ) ) {
            $visible_brands[] = $brand;
        }
    }

    $brand_names = wp_list_pluck( $visible_brands, 'name' );


    $output = '<div class="custom-logos-grid">';
    foreach ($brand_names as $brand_name) {
        $logo = $logos[$brand_name];
        if (is_array($logo)) {
            $output .= '<div class="logo-item">
                        <a href="' . esc_url(home_url('brand-filter/'.$brand_name.'/')) . '" target="_blank" rel="noopener" alt="' . $brand_name . '">
                            <img src="' . esc_url($logo['img']) . '" alt="' . $brand_name . '">
                        </a>
                    </div>';
        }
    }
    $output .= '</div>';


    return $output;
}
add_shortcode('logos_grid', 'custom_logos_grid_shortcode');



/**
 * Make checkout fields read-only (not editable) but still visible and submitted.
 */
add_filter( 'woocommerce_form_field', function( $field, $key, $args, $value ) {
    // Apply only to billing, shipping and order notes (not payment/shipping methods)
    if ( strpos( $key, 'billing_' ) === 0 || strpos( $key, 'shipping_' ) === 0 || $key === 'order_comments' ) {

        // For input/text/textarea fields → set readonly
        if ( in_array( $args['type'], [ 'text', 'email', 'tel', 'textarea', 'number', 'postcode' ] ) ) {
            $field = str_replace( '<input', '<input readonly="readonly"', $field );
            $field = str_replace( '<textarea', '<textarea readonly="readonly"', $field );
        }

        // For selects (country, state) → replace with text + hidden input
        if ( $args['type'] === 'select' ) {
            $label = ! empty( $args['label'] ) ? '<label class="">' . esc_html( $args['label'] ) . '</label>' : '';
            $display_value = $args['options'][$value] ?? $value;

            $field  = '<p class="form-row ' . esc_attr( implode( ' ', $args['class'] ) ) . '" id="' . esc_attr( $key ) . '_field">';
            $field .= $label;
            $field .= '<span class="readonly-field">' . esc_html( $display_value ) . '</span>';
            $field .= '<input type="hidden" name="' . esc_attr( $key ) . '" value="' . esc_attr( $value ) . '" />';
            $field .= '</p>';
        }
    }
    return $field;
}, 10, 4 );

// Remove Additional Notes from checkout
add_filter( 'woocommerce_enable_order_notes_field', '__return_false' );


// Redirect default reset link to custom page
add_action( 'login_form_rp', 'custom_password_reset_redirect' );
add_action( 'login_form_resetpass', 'custom_password_reset_redirect' );

function custom_password_reset_redirect() {
    $redirect_url = home_url( '/reset-password/' ); // change slug if different

    if ( isset( $_REQUEST['key'] ) && isset( $_REQUEST['login'] ) ) {
        $redirect_url = add_query_arg( [
            'key'   => sanitize_text_field( $_REQUEST['key'] ),
            'login' => sanitize_text_field( $_REQUEST['login'] ),
        ], $redirect_url );
    }

    wp_safe_redirect( $redirect_url );
    exit;
}


// Shortcode for custom reset password form
add_shortcode( 'custom_reset_password', function() {

    if ( isset( $_GET['reset-error'] ) ) {
        if ( $_GET['reset-error'] === 'password_mismatch' ) {
            wc_print_notice( __( 'Passwords do not match.', 'woocommerce' ), 'error' );
        }
        if ( $_GET['reset-error'] === 'invalid_link' ) {
            wc_print_notice( __( 'Invalid or expired reset link.', 'woocommerce' ), 'error' );
        }
        if ( $_GET['reset-error'] === 'pending_reg' ) {
            wc_print_notice( __( 'Registration pending please wait for approval.', 'woocommerce' ), 'error' );
        }
        if ( $_GET['reset-error'] === 'no_user' ) {
            wc_print_notice( __( 'No such user exists.', 'woocommerce' ), 'error' );
        }
    }

    if ( ! isset( $_GET['key'], $_GET['login'] ) ) {
        return '<p class="error">Invalid reset link.</p>';
    }

    ob_start(); ?>
    <form method="post" class="custom-reset-form">
        <p>
            <label for="pass1">New password</label>
            <input type="password" name="pass1" id="pass1" required>
        </p>
        <p>
            <label for="pass2">Confirm new password</label>
            <input type="password" name="pass2" id="pass2" required>
        </p>
        <p>
            <button type="submit" name="custom_reset_submit">Reset Password</button>
        </p>
    </form>
    <?php
    return ob_get_clean();
});


/**
 * Extract base categories from product-filter URL
 * ABBA Issue #51: Reuse existing URL parsing logic for filter scoping
 */
function abba_get_base_categories() {
    $request_uri = $_SERVER['REQUEST_URI'];
    if (preg_match('#product-filter/([^/?]+)#', $request_uri, $matches)) {
        return array_filter(explode('+', $matches[1]));
    }
    return [];
}
