<?php
/**
 * The template for displaying product content in the single-product.php template
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/content-single-product.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see     https://docs.woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 999999
 */

defined('ABSPATH') || exit;

global $product;

/**
 * Hook: woocommerce_before_single_product.
 *
 * @hooked woocommerce_output_all_notices - 10
 */
do_action('woocommerce_before_single_product');

if (post_password_required()) {
    echo get_the_password_form(); // WPCS: XSS ok.
    return;
}
?>
<div id="product-<?php the_ID(); ?>" <?php wc_product_class('singleProductContainer', $product); ?>>

        <div class="productTitleWrapper">
            <a id="goback" class="backBtn">
                Back
            </a>
            <div class="titleGroup">
                <?php
                if ( $product ) {
                    echo '<h1 class="product-title">' . esc_html( $product->get_name() ) . '</h1>';
                    $brand_name = $product->get_attribute('Brand');
                    
                    if ($brand_name) {
                        echo '<h6 class="subTitle">' . esc_html($brand_name) . '</h6>';
                    }
                }
                ?>
            </div>
        </div>
        
        <div class="productInfoDetails">
            <?php //echo do_shortcode('[terpene_details]'); ?>
            <?php //echo do_shortcode('[potencies]'); ?>
            <?php
            /**
             * Hook: woocommerce_before_single_product_summary.
             *
             * @hooked woocommerce_show_product_sale_flash - 10
             * @hooked woocommerce_show_product_images - 20
             */
            do_action('woocommerce_before_single_product_summary');
            ?>

            <div class="summary entry-summary productDetails">

                <div class="progressWrapper">
                    <div class="thcProgress">
                        <label>THC</label>
                        <div class="progressContainer">
                        <span class="active"></span>
                        <span class="active"></span>
                        <span class="active"></span>
                        <span></span>
                        </div>
                        28-32%
                    </div>

                    <div class="cbdProgress">
                        <label>CBD</label>
                        <div class="progressContainer">
                        <span class="active"></span>
                        <span></span>
                        <span></span>
                        <span></span>
                        </div>
                        0-1%
                    </div>
                </div>

                <div class="iconWrapper">
                    <div class="icon">
                        <span class="sativa">Sativa</span>
                        <p>25% <br>Sativa</p>
                    </div>

                    <div class="icon">
                        <span class="indica">Indica</span>
                        <p>75% <br>Indica</p>
                    </div>

                    <div class="icon">
                        <span class="myRecene"></span>
                        <p>Myrecene</p>
                    </div>

                    <div class="icon">
                        <span class="limonene"></span>
                        <p>Limonene</p>
                    </div>

                    <div class="icon">
                        <span class="linalool"></span>
                        <p>Linalool</p>
                    </div>
                </div>

                <!-- <?php //echo do_shortcode('[show_product_attributes]') ?> -->

                <div class="devider"></div>

                <?php
                /**
                 * Hook: woocommerce_single_product_summary.
                 *
                 * @hooked woocommerce_template_single_title - 5
                 * @hooked woocommerce_template_single_rating - 10
                 * @hooked woocommerce_template_single_price - 10
                 * @hooked woocommerce_template_single_excerpt - 20
                 * @hooked woocommerce_template_single_add_to_cart - 30
                 * @hooked woocommerce_template_single_meta - 40
                 * @hooked woocommerce_template_single_sharing - 50
                 * @hooked WC_Structured_Data::generate_product_data() - 60
                 */
                do_action('woocommerce_single_product_summary');
                ?>
                <div class="blog-tile-wave product-sum-btm-wave"></div>
            </div>
            
        </div>
        <?php
        /**
         * Hook: woocommerce_after_single_product_summary.
         *
         * @hooked woocommerce_output_product_data_tabs - 10
         * @hooked woocommerce_upsell_display - 15
         * @hooked woocommerce_output_related_products - 20
         */
        do_action('woocommerce_after_single_product_summary');
        ?>
     
</div>

<?php do_action('woocommerce_after_single_product'); ?>

