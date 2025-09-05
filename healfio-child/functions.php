<?php
// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

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

        // Get the brand attribute value dynamically
		$brand_name = $product->get_attribute('Brand');
		// $brand_name = $brand_name ? $brand_name : 'Brand NA';
		
        if ($brand_name) {
            echo '<div class="prodcard-brand"><label>' . esc_html($brand_name) . '</label></div>';
        } else {
            echo '<div class="prodcard-brand" style="opacity:0;"><label>&nbsp;</label></div>';
        }
		
		//echo '<div class="prodcard-brand-name"><label>MTL Canabis</label></div>';  
        
        echo '<div class="childGroup">';
       
		echo '<div class="prodcard-attributes">';
        // Get the THC attribute value
        $thc_value = $product->get_attribute('thc');
        if ( ! empty( $thc_value ) ) {
            // echo '<div class="product-list-attribute"><strong>THC :</strong> <span>' . esc_html( $thc_value ) . '</span></div>';
            echo '<div class="product-list-attribute"><strong>THC:</strong> <span>32%</span></div>';
        }
		
        // Get the CBD attribute value
        $cbd_value = $product->get_attribute('cbd');
        if ( ! empty( $cbd_value ) ) {
            // echo '<div class="product-list-attribute"><strong>CBD :</strong> <span>' . esc_html( $cbd_value ) . '</span></div>';
            echo '<div class="product-list-attribute"><strong>CBD:</strong> <span>0.03%</span></div>';
        }
		
		echo '</div>';
		
        echo '</div>';
        echo'<div class="prodcard-tags">
                Terpenes &nbsp; 4.95%
                <p>Myrcele - Limonede - Linalool </p>
             </div>';
        echo '</div>';
    }

}

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
//             <select name="orderby" class="orderby" onchange="this.form.submit()">';
                
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

        switch ($orderby) { // Fix: use $orderby instead of undefined $order_by
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
					<button class="toggleBtn" id="tch">THC</button>
					<div class="dropdown">
					<label>THC Range</label>
					<div class="slider">
							<div class="progress"></div>
					</div>
					<div class="range-input">
							<input type="range" class="range-min" min="0.00" max="45.00" step="0.01">
							<input type="range" class="range-max" min="0.00" max="45.00" step="0.01">
					</div>
					<div class="price-input">
							<div class="field">
							<input type="number" class="input-min" value="0.00">
							</div>
							<div class="separator">To</div>
							<div class="field">
							<input type="number" class="input-max" value="45.00">
							</div>
					</div>
					
					<button type="submit" class="btnApply">Apply</button>
					</div>
			</div>

			<div class="filterDropdown">
					<button class="toggleBtn" id="cbd">CBD</button>
					<div class="dropdown range-second">
					<label>CBD Range</label>
					<div class="slider">
							<div class="progress"></div>
					</div>
					<div class="range-input">
							<input type="range" class="range-min range-input-second range-min" step="0.001" min="0.00" max="0.03">
							<input type="range" class="range-max range-input-second range-max" step="0.001" min="0.00" max="0.03">
					</div>
					<div class="price-input">
							<div class="field">
							<input type="number" class="input-min price-input-second input-min" step="0.001" min="0.00" max="0.03" value="0.00">
							</div>
							<div class="separator">To</div>
							<div class="field">
							<input type="number" class="input-max price-input-second input-max" step="0.001" min="0.00" max="0.03" value="0.03">
							</div>
					</div>
					
					<button type="submit" class="btnApply">Apply</button>
					</div>
			</div>

			<div class="filterDropdown">
					<button class="toggleBtn" id="size">size</button>
					<div class="dropdown">
					<label>size</label>
					<?php echo do_shortcode('[product_sizes]'); ?>
					</div>
			</div>

			<div class="filterDropdown">
					<button class="toggleBtn" id="dominance">dominance</button>
					<div class="dropdown">
					<label>dominance</label>					
					<?php echo do_shortcode('[product_dominance]'); ?>
					</div>
			</div>

			<div class="filterDropdown">
					<button class="toggleBtn" id="terpenes">terpenes</button>
					<div class="dropdown">
						<label>terpenes</label>
						<?php echo do_shortcode('[terpene_checkboxes]'); ?>
					</div>
			</div>

			<div class="filterDropdown">
					<button class="toggleBtn" id="brand">brand</button>
					<div class="dropdown">
					<label>brand</label>
					<?php
						// Get all product brands
						$brands = get_terms([
								'taxonomy'   => 'product_brand',
								'hide_empty' => false, // Set to true if you only want brands with products
						]);

						if (!empty($brands) && !is_wp_error($brands)) {
								echo '<form id="brand-filter">';
								foreach ($brands as $brand) {
										echo '<label>';
										echo '<input type="checkbox" name="product_brand[]" value="' . esc_attr($brand->slug) . '">';
										echo esc_html($brand->name);
										echo '</label>';
								}
								echo '<button type="submit" class="btnApply">Apply</button>';
								echo '</form>';
						}
						?>					
					</div>
			</div>

			<div class="filterDropdown">
					<button class="toggleBtn" id="categories">categories</button>
					<div class="dropdown">
					<label>categories</label>
					<?php echo do_shortcode('[product-filter-category]'); ?>
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


// Register shortcode to display terpene checkboxes
function render_terpene_checkboxes_shortcode() {
    $terpenes = ['Myrcene', 'Limonene', 'Pinene', 'Linalool', 'Caryophyllene', 'Humulene', 'Terpinolene', 'Ocimene'];

    $html = '<form method="post" class="terpene-form">';
    $html .= '<div class="terpene-checkboxes">';

    foreach ($terpenes as $terpene) {
        $id = 'terpene_' . strtolower($terpene);
        $html .= '<label style="display:block;margin-bottom:5px;">';
        $html .= '<input type="checkbox" name="terpenes[]" value="' . esc_attr($terpene) . '"> ' . esc_html($terpene);
        $html .= '</label>';
    }

    $html .= '</div>';
    $html .= '<button type="submit" class="btnApply">Apply</button>'; // Optional
    $html .= '</form>';

    return $html;
}
add_shortcode('terpene_checkboxes', 'render_terpene_checkboxes_shortcode');


// Shortcode to dynamically get and display product sizes (multi-select)
function dynamic_product_sizes_multiselect_shortcode() {
    // Replace 'pa_size' with your actual attribute slug
    $attribute_name = 'pa_size';
    $taxonomy = wc_sanitize_taxonomy_name($attribute_name);

    // Get all terms (sizes) from the attribute
    $terms = get_terms([
        'taxonomy'   => $taxonomy,
        'hide_empty' => false,
    ]);

    if (empty($terms) || is_wp_error($terms)) {
        return '<p>No sizes found.</p>';
    }

    $html = '<form class="product-sizes-form">';

    foreach ($terms as $term) {
        $html .= '<label style="display:block; margin:4px 0;">';
        $html .= '<input type="checkbox" name="product_sizes[]" value="' . esc_attr($term->name) . '"> ' . esc_html($term->name);
        $html .= '</label>';
    }
		$html .= '<button type="submit" class="btnApply">Apply</button>'; // Optional
    $html .= '</form>';
    return $html;
}
add_shortcode('product_sizes', 'dynamic_product_sizes_multiselect_shortcode');


// Shortcode to get and display product dominance from attribute
function get_product_dominance_shortcode() {
    global $product;

    if (!$product || !is_product()) {
        return '';
    }

    $taxonomy = 'pa_dominance';
    $terms = wp_get_post_terms($product->get_id(), $taxonomy);

    if (!empty($terms) && !is_wp_error($terms)) {
        $names = wp_list_pluck($terms, 'name');
        return '<p>' . esc_html(implode(', ', $names)) . '</p>';
    }

    return '<p><strong>Dominance:</strong> Not specified</p>';
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
    
    echo '<div class="shop-variation-swatches" data-product-id="' . $product->get_id() . '">';
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




add_action('wp_ajax_get_variations_for_product', 'get_variations_for_product');
add_action('wp_ajax_nopriv_get_variations_for_product', 'get_variations_for_product');
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
        'nonce'    => wp_create_nonce('view_order_doc_nonce'),
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
        } else {
            $logo = $logos['Abba Vets'];
            $output .= '<div class="logo-item">
                        <a href="' . esc_url(home_url('brand-filter/'.$brand_name.'/')) . '" target="_blank" rel="noopener" alt="' . $brand_name . '">
                            <img src="' . esc_url($logo['img']) . '" alt="' . $brand_name . '">
                        </a>
                    </div>';
        }
    }
    $output .= '</div>';

    // echo '<pre>';
    // print_r( $brand_names );
    // echo '</pre>';

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
