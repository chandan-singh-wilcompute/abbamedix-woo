<?php
/**
 * Related Products
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/single-product/related.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see         https://woocommerce.com/document/template-structure/
 * @package     WooCommerce\Templates
 * @version     9.6.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

global $product;

// Force WooCommerce to get 8 related products
$related_products = wc_get_related_products( $product->get_id(), 8 );

if ( $related_products ) : ?>

    <section class="related products">
        <h2>
            <?php esc_html_e( 'Related products', 'woocommerce' ); ?>
            <p class="productCount"><?php echo do_shortcode('[product_count]') ?></p>
        </h2>
        
        <?php
        // Set up WooCommerce loop columns
        global $woocommerce_loop;
        $woocommerce_loop['columns'] = 4;

        woocommerce_product_loop_start();

        foreach ( $related_products as $related_product ) :
            $post_object = get_post( $related_product );
            setup_postdata( $GLOBALS['post'] = $post_object );
            wc_get_template_part( 'content', 'product' );
        endforeach;

        woocommerce_product_loop_end();
        ?>

    </section>

<?php endif;

wp_reset_postdata();
