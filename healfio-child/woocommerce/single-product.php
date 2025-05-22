<?php
/**
 * The Template for displaying all single products
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/single-product.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see         https://docs.woocommerce.com/document/template-structure/
 * @package     WooCommerce\Templates
 * @version     999999
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

get_header('shop'); ?>
    <main>
        <div class="container-xl container-product">
            <div class="row">
                
                <div class="content-single-product">

                    <?php

                    do_action('woocommerce_before_main_content');

                    the_post();

                    wc_get_template_part('content', 'single-product');

                    ?>

                </div>
            </div>

            <?php if (is_active_sidebar('sidebar-single-product')) {
                echo '<div class="col-lg-4 pl-lg-4 sidebar-single-product">';
                dynamic_sidebar('sidebar-single-product');
                echo '</div>';
            } ?>
        </div>
        </div>
    </main>
<?php
do_action('woocommerce_after_main_content');
?>


<?php
get_footer('shop');

/* Omit closing PHP tag at the end of PHP files to avoid "headers already sent" issues. */
