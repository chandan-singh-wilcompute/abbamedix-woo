<?php
/**
 * Cart totals
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/cart/cart-totals.php.
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

defined( 'ABSPATH' ) || exit;

?>
<div class="row cart-totals-wrapper" style="display: flex; align-items: center;">
	<div class="col-md-12">
		<div class="cart_totals <?php echo ( WC()->customer->has_calculated_shipping() ) ? 'calculated_shipping' : ''; ?>" style="width: 100%;">
			<?php do_action( 'woocommerce_before_cart_totals' ); ?>

			<h3 class="mb-0" style="font-size: 40px;
    font-weight: 400;"><?php esc_html_e( 'SHOPPING CART', 'healfio' ); ?></h3>
			<h6 style="padding-left:0px;font-size:22px" class="mb-4 mt-1">Tax, Discounts and Shipping charges will be applied on the next page</h6>

			<table cellspacing="0" class="shop_table shop_table_responsive summary_shop_table">

				<!-- <tr class="cart-subtotal">
					<th><?php esc_html_e( 'Subtotal:', 'healfio' ); ?></th>
					<td data-title="<?php esc_attr_e( 'Subtotal:', 'healfio' ); ?>"><?php wc_cart_totals_subtotal_html(); ?></td>
				</tr>

				<?php foreach ( WC()->cart->get_coupons() as $code => $coupon ) : ?>
					<tr class="cart-discount coupon-<?php echo esc_attr( sanitize_title( $code ) ); ?>">
						<th><?php wc_cart_totals_coupon_label( $coupon ); ?></th>
						<td data-title="<?php echo esc_attr( wc_cart_totals_coupon_label( $coupon, false ) ); ?>"><?php wc_cart_totals_coupon_html( $coupon ); ?></td>
					</tr>
				<?php endforeach; ?>

				<tr class="tax-total">
					<th><?php esc_html_e( 'Taxes:', 'healfio' ); ?></th>
					<td data-title="<?php echo esc_attr( WC()->countries->tax_or_vat() ); ?>">Apply on the next step</td>
				</tr>

				<tr class="tax-total">
					<th><?php esc_html_e( 'Discounts:', 'healfio' ); ?></th>
					<td>Apply on the next step</td>
				</tr>

				<tr class="shipping">
					<th><?php esc_html_e( 'Shipping:', 'healfio' ); ?></th>
					<td data-title="<?php esc_attr_e( 'Shipping', 'healfio' ); ?>">Apply on the next step</td>
				</tr> -->

				

				

				<?php do_action( 'woocommerce_cart_totals_before_order_total' ); ?>

				<tr class="order-total">
					<th><?php esc_html_e( 'Total:', 'healfio' ); ?></th>
					<td data-title="<?php esc_attr_e( 'Total', 'healfio' ); ?>">
						<?php 
							wc_cart_totals_subtotal_html(); 
							// wc_cart_totals_order_total_html(); 
						?>
					</td>
				</tr>

				<?php do_action( 'woocommerce_cart_totals_after_order_total' ); ?>

			</table>

			

			<?php do_action( 'woocommerce_after_cart_totals' ); ?>

		</div>
	</div>
	<div class="col-md-12">
		<div class="wc-proceed-to-checkout">
			<?php do_action( 'woocommerce_proceed_to_checkout' ); ?>
		</div>
	</div>
</div>

