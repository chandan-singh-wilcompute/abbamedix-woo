<?php 
if (!defined('ABSPATH')) {
    exit;
}

/* 

    This part of code is controlling customization and restriction 
    For restricted User Role
    Hiding product editing options

*/

if (current_user_can('user-admin-limited')) 
{
    add_filter('woocommerce_product_data_tabs', 'remove_unwanted_product_data_tabs', 99);
}

function remove_unwanted_product_data_tabs($tabs)
{
    // Remove general (contains pricing), linked products, attributes, and advanced tabs
    unset($tabs['general']);
    unset($tabs['linked_product']);
    unset($tabs['attribute']);
    unset($tabs['advanced']);
    // Remove the Description tab
    // unset($tabs['description']);

    return $tabs;
}

add_action('admin_init', 'hide_product_title_and_description_fields');
function hide_product_title_and_description_fields()
{
    // Check if the current user can edit products but not manage WooCommerce
    if (current_user_can('user-admin-limited')) {
        add_action('admin_head', 'custom_admin_css');
    }
}

function custom_admin_css()
{
    echo '<style>
    #titlediv, #_sku, #product-type, #elementor-switch-mode-button, .woocommerce_variation h3 { pointer-events:none; !important; }  /* Hide product title */
    #postdivrich, #postexcerpt, #product_catdiv { pointer-events: none !important; } /* Hide product description */ 
    .post-type-product .page-title-action { display: none; } /* Hide Add New button from product Edit page */
    </style>';
}

function restrict_woocommerce_fuctionalities_js() {
    if (current_user_can('user-admin-limited')) {
        // Add inline JavaScript to disable certain fields
        add_action('admin_footer', 'custom_js_for_custom_woocommerce');
    }
}
add_action('admin_init', 'restrict_woocommerce_fuctionalities_js');

function custom_js_for_custom_woocommerce() 
{
    ?>
    <script type="text/javascript">
        jQuery(document).ready(function($) {
            $("#product_catdiv").removeClass('closed');
            $("#product_catdiv").addClass('opened');
        });
    </script>
    <?php
}


// Remove Add New Product Option
function hide_add_new_product_menu_for_limited_role() {
    // Check if the current user has the "user-admin-limited" role
    if (current_user_can('user-admin-limited')) {
        // Remove the "Add New" submenu under Products
        remove_submenu_page('edit.php?post_type=product', 'post-new.php?post_type=product');
        // Remove "Categories" submenu under Products
        remove_submenu_page('edit.php?post_type=product', 'edit-tags.php?taxonomy=product_cat&amp;post_type=product');
        // Remove "Attributes" submenu under Products
        remove_submenu_page('edit.php?post_type=product', 'product_attributes');
    }
}
add_action('admin_menu', 'hide_add_new_product_menu_for_limited_role', 999);

function restrict_add_new_product_access() {
    // Check if the current user is attempting to access the "Add New Product" page
    if (is_admin() && isset($_GET['post_type']) && $_GET['post_type'] === 'product' && strpos($_SERVER['REQUEST_URI'], 'post-new.php') !== false) {
        // Check if the user has the "user-admin-limited" role
        if (current_user_can('user-admin-limited')) {
            // Deny access and redirect to a different admin page or show an error
            wp_die(__('This Action is not supported at this moment. <br/> <a href="'.admin_url().'">Go Back</a>'));
        }
    }
}
add_action('admin_init', 'restrict_add_new_product_access');

/* 
    Restriction and Diabling Woocommerce features Ends here.

*/