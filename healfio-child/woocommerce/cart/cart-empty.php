<?php
/**
 * Empty cart page
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/cart/cart-empty.php.
 *
 * @see     https://docs.woocommerce.com/document/template-structure/
 */

defined( 'ABSPATH' ) || exit;

wc_print_notices();
?>

<div class="woocommerce-cart-empty text-center">

    <h2 class="cart-empty-heading">
        <?php esc_html_e( 'Your cart is currently empty.', 'woocommerce' ); ?>
    </h2>

    <p class="cart-empty-text">
        <?php esc_html_e( 'Looks like you haven’t added anything yet.', 'woocommerce' ); ?>
    </p>

    <p class="return-to-shop">
        <a class="button wc-backward" href="<?php echo esc_url( wc_get_page_permalink( 'shop' ) ); ?>">
            <?php esc_html_e( 'Return to Shop', 'woocommerce' ); ?>
        </a>
    </p>
</div>

<style>
    .woocommerce-cart-empty {
        padding: 50px 20px;
        background: #fafafa;
        border: 1px solid #eee;
        border-radius: 12px;
        margin: 30px auto;
        max-width: 600px;
    }
    .cart-empty-heading {
        font-size: 1.8rem;
        margin-bottom: 15px;
        color: #333;
    }
    .cart-empty-text {
        font-size: 1rem;
        margin-bottom: 25px;
        color: #666;
    }
    .woocommerce-cart-empty .wc-backward {
        display: inline-block;
        padding: 12px 30px;
        background: #2e7d32;
        color: #fff;
        text-decoration: none;
        border-radius: 25px;
        font-weight: 600;
        transition: background 0.3s ease;
    }
    .woocommerce-cart-empty .wc-backward:hover {
        background: #1b5e20;
    }
</style>
