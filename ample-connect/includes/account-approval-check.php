<?php
/**
 * Account Approval Check
 * 
 * This file handles cart restrictions for non-approved accounts
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Check if user is approved
 */
function ample_connect_is_user_approved() {
    if (!is_user_logged_in()) {
        return true; // Non-logged in users handled elsewhere
    }
    
    $status = Ample_Session_Cache::get('status');
    return empty($status) || strtolower($status) === 'approved';
}

/**
 * Display a persistent notice for non-approved users
 */
function ample_connect_display_approval_notice() {
    // Only run for logged-in users
    if (!is_user_logged_in()) {
        return;
    }
    
    // Don't run on admin pages
    if (is_admin()) {
        return;
    }
    
    // Check if user is approved
    if (ample_connect_is_user_approved()) {
        return;
    }
    
    // Get the custom message from settings or use default
    global $ample_connect_settings;
    $message = !empty($ample_connect_settings['account_not_approved_message']) 
        ? $ample_connect_settings['account_not_approved_message'] 
        : 'Your account is pending approval. You can view your profile and orders, but cannot make new purchases until approved.';
    
    // Add a persistent notice
    ?>
    <div class="woocommerce-info ample-approval-notice" style="background: #fff3cd; border-left-color: #f0ad4e; margin-bottom: 20px; position: relative; padding-right: 50px;">
        <button type="button" class="ample-approval-close" style="position: absolute; top: 8px; right: 8px; background: none; border: none; font-size: 24px; font-weight: bold; color: #856404; cursor: pointer; padding: 4px; width: 32px; height: 32px; line-height: 1; border-radius: 4px; display: flex; align-items: center; justify-content: center;" title="Close">&times;</button>
        <strong>Account Status:</strong> <?php echo esc_html($message); ?>
    </div>
    <style>
    .ample-approval-notice::before {
        display: none !important;
    }
    .ample-approval-close:hover {
        background: rgba(133, 100, 4, 0.1) !important;
    }
    </style>
    <?php
}

/**
 * Prevent non-approved users from adding products to cart
 */
function ample_connect_prevent_add_to_cart($passed, $product_id, $quantity, $variation_id = 0, $variations = array()) {
    // Check if user is approved
    if (!ample_connect_is_user_approved()) {
        // Get the custom message
        global $ample_connect_settings;
        $message = !empty($ample_connect_settings['account_not_approved_message']) 
            ? $ample_connect_settings['account_not_approved_message'] 
            : 'Your account must be approved before you can add products to cart. Please wait for approval or contact support.';
        
        wc_add_notice($message, 'error');
        return false;
    }
    
    return $passed;
}

/**
 * Hide add to cart buttons for non-approved users
 */
function ample_connect_hide_add_to_cart_button() {
    if (!ample_connect_is_user_approved()) {
        remove_action('woocommerce_after_shop_loop_item', 'woocommerce_template_loop_add_to_cart', 10);
        remove_action('woocommerce_single_product_summary', 'woocommerce_template_single_add_to_cart', 30);
        
        // Add a message in place of add to cart button on single product page
        add_action('woocommerce_single_product_summary', 'ample_connect_approval_required_button', 30);
    }
}

/**
 * Show approval required message instead of add to cart button
 */
function ample_connect_approval_required_button() {
    echo '<div class="approval-required-message" style="padding: 10px; background: #f8f9fa; border: 1px solid #dee2e6; border-radius: 5px; text-align: center; margin-top: 20px;">';
    echo '<p style="margin: 0; color: #6c757d;"><strong>Account approval required to purchase</strong></p>';
    echo '<p style="margin: 5px 0 0 0; font-size: 14px;">You can browse products and view your existing orders while your account is being reviewed.</p>';
    echo '</div>';
}

/**
 * Modify add to cart button text for non-approved users (for places we can't remove)
 */
function ample_connect_modify_add_to_cart_text($text, $product = null) {
    if (!ample_connect_is_user_approved()) {
        return 'Approval Required';
    }
    return $text;
}

/**
 * Disable add to cart button via CSS for non-approved users
 */
function ample_connect_add_approval_styles() {
    if (!ample_connect_is_user_approved()) {
        ?>
        <style>
            .add_to_cart_button,
            .single_add_to_cart_button,
            .ajax_add_to_cart {
                pointer-events: none !important;
                opacity: 0.5 !important;
                cursor: not-allowed !important;
            }
            .approval-required-message {
                animation: fadeIn 0.3s ease-in;
            }
            @keyframes fadeIn {
                from { opacity: 0; }
                to { opacity: 1; }
            }
            .ample-approval-notice {
                position: sticky;
                top: 0;
                z-index: 999;
                box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            }
        </style>
        <?php
    }
}

/**
 * Prevent checkout access for non-approved users
 */
function ample_connect_prevent_checkout_access() {
    if (is_checkout() && !is_wc_endpoint_url('order-received')) {
        if (!ample_connect_is_user_approved()) {
            // Redirect to my account page with a message
            wc_add_notice('Your account must be approved before you can checkout. You can view your profile and existing orders.', 'error');
            wp_redirect(wc_get_page_permalink('myaccount'));
            exit;
        }
    }
}

/**
 * Add notice on cart page for non-approved users
 */
function ample_connect_cart_page_notice() {
    if (is_cart() && !ample_connect_is_user_approved()) {
        wc_print_notice('Your account is pending approval. You cannot proceed to checkout until your account is approved.', 'notice');
    }
}

// Hook into WordPress/WooCommerce
add_action('wp', 'ample_connect_display_approval_notice');
add_action('woocommerce_before_main_content', 'ample_connect_display_approval_notice', 5);
add_filter('woocommerce_add_to_cart_validation', 'ample_connect_prevent_add_to_cart', 10, 5);
add_action('init', 'ample_connect_hide_add_to_cart_button');
add_filter('woocommerce_product_add_to_cart_text', 'ample_connect_modify_add_to_cart_text', 10, 2);
add_filter('woocommerce_product_single_add_to_cart_text', 'ample_connect_modify_add_to_cart_text', 10, 2);
add_action('wp_head', 'ample_connect_add_approval_styles');
add_action('template_redirect', 'ample_connect_prevent_checkout_access');
add_action('woocommerce_before_cart', 'ample_connect_cart_page_notice');