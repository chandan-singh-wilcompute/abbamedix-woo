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

    // Add your custom function
    add_action( 'woocommerce_shop_loop_item_title', 'my_custom_template_loop_product_title', 10 );

    // Define your custom function
    function my_custom_template_loop_product_title() {
        // Get the product object
        global $product;

        echo '<div class="group">';

        // Output the product title
        echo '<h2 class="' . esc_attr( apply_filters( 'woocommerce_product_loop_title_classes', 'woocommerce-loop-product__title' ) ) . '">' . get_the_title() . '</h2>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

		//echo '<div class="prodcard-brand-name"><label>MTL Canabis</label></div>';  
		echo '<div class="prodcard-brand"><label>MTL Canabis</label></div>';  
		echo '<div class="prodcard-attributes">';
        // Get the THC attribute value
        $thc_value = $product->get_attribute('thc');
        if ( ! empty( $thc_value ) ) {
            echo '<span class="product-list-attribute">THC : ' . esc_html( $thc_value ) . '</span>';
        }
		
        // Get the CBD attribute value
        $cbd_value = $product->get_attribute('cbd');
        if ( ! empty( $cbd_value ) ) {
            echo '<span class="product-list-attribute">CBD : ' . esc_html( $cbd_value ) . '</span>';
        }
		
		echo '</div>';
		echo'<div class="prodcard-tags">Terpenes<br>Myrcele - Limonede - Linalool<br>Total: 4.5%</div>';
        echo '</div>';
    }
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
    <button type="button" id="selectAllFeaturedMenu" class="selectAll">Select All</button>
    <button type="submit" id="sortByBtn" class="sortBtn">Sort By</button>
  </div>';
  echo '</form>';
  return ob_get_clean();
  
}
add_shortcode('product_category_filter', 'product_category_filter_shortcode');

function custom_product_filter_results_shortcode() {
  $slug_string = get_query_var('filter_slugs', '');
  $slugs = explode('+', sanitize_text_field($slug_string));

  // Debug:
//   echo '<pre>Filter Slugs: ' . esc_html($slug_string) . '</pre>';
  echo '<div class="titleWrapper">
            <div class="container">
                <a id="goback" class="backBtn">
                Back
            </a>
            <h5>' . esc_html($slug_string) . '</h5>
            </div>
        </div>';

  if (empty($slug_string)) {
    return '<div class="container text-white pt-5 pb-5" style="min-height:500px">Please select a category to view products.</div>';
  }

  $query = new WP_Query([
    'post_type'      => 'product',
    'posts_per_page' => -1,
    'tax_query'      => [
      [
        'taxonomy' => 'product_cat',
        'field'    => 'slug',
        'terms'    => $slugs,
        'operator' => 'IN',
      ]
    ]
  ]);

  ob_start();

  if ($query->have_posts()) {
    echo '<div class="container">';
    echo '<ul class="products elementor-grid columns-4">';
    while ($query->have_posts()) {
      $query->the_post();
      wc_get_template_part('content', 'product');
    }
    echo '</ul>';
    echo '</div>';

  } else {
    echo '<p>No products found for selected categories.</p>';
  }

  wp_reset_postdata();
  return ob_get_clean();
}
add_shortcode('custom_product_filter_results', 'custom_product_filter_results_shortcode');

// Add rewrite rule for pretty category filters like /product-filter/slug+slug/
function register_product_filter_rewrite_rule() {
  add_rewrite_rule(
    '^product-filter/([^/]+)/?$',
    'index.php?pagename=product-filter&filter_slugs=$matches[1]',
    'top'
  );
}
add_action('init', 'register_product_filter_rewrite_rule');

// Add custom query var
function add_product_filter_query_var($vars) {
  $vars[] = 'filter_slugs';
  return $vars;
}
add_filter('query_vars', 'add_product_filter_query_var');


