<?php
/**
 * Single variation cart button
 *
 * @see https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 7.0.1
 */

defined( 'ABSPATH' ) || exit;

global $product;
?>
<div class="woocommerce-variation-add-to-cart variations_button productQuantity">
	<?php do_action( 'woocommerce_before_add_to_cart_button' ); ?>
	<div class="group">
		<button type="button" class="btn minusQuntity decrease" onclick="decreaseQty(this)">−</button>
		<?php
		do_action( 'woocommerce_before_add_to_cart_quantity' );

		woocommerce_quantity_input(
		array(			
			'min_value'   => apply_filters( 'woocommerce_quantity_input_min', $product->get_min_purchase_quantity(), $product ),
			'max_value'   => apply_filters( 'woocommerce_quantity_input_max', $product->get_max_purchase_quantity(), $product ),
			'input_value' => isset( $_POST['quantity'] ) ? wc_stock_amount( wp_unslash( $_POST['quantity'] ) ) : $product->get_min_purchase_quantity(), // WPCS: CSRF ok, input var ok.
			'input_attrs' => array(
				'onchange' => 'updateTotal(this)',
			),
		)
	);


		do_action( 'woocommerce_after_add_to_cart_quantity' );
		?>
		<button type="button" class="btn addQuntity increase" onclick="increaseQty(this)">+</button>
	</div>

	<div class="group addToCartGroup">
		<span class="rxDeductionAmount">
			<?php //echo do_shortcode('[rx_deduction]') ?>			
			RX Deduction Amount 10g
		</span>
		<button type="submit" class="single_add_to_cart_button button alt<?php echo esc_attr( wc_wp_theme_get_element_class_name( 'button' ) ? ' ' . wc_wp_theme_get_element_class_name( 'button' ) : '' ); ?>"><?php echo esc_html( $product->single_add_to_cart_text() ); ?></button>

		<?php do_action( 'woocommerce_after_add_to_cart_button' ); ?>
	
		<input type="hidden" name="add-to-cart" value="<?php echo absint( $product->get_id() ); ?>" />
		<input type="hidden" name="product_id" value="<?php echo absint( $product->get_id() ); ?>" />
		<input type="hidden" name="variation_id" class="variation_id" value="0" />
	</div>
</div>

<script>

		// Triggerd product size on window load
    window.addEventListener('load', function() {
        setTimeout(function() {
            const items = document.querySelectorAll('.variable-items-wrapper li');
            const lastLi = items[items.length - 1];
            if (lastLi) {
                lastLi.click();
                console.log('Last <li> clicked');
            } else {
                console.log('No <li> found');
            }
        }, 500); // 0.5 second delay
    });

	
		jQuery(document).ready(function($) {

		// Save original unit price (for total calculation)
		let priceContainer = $('.woocommerce-Price-amount').first();
		let originalPrice = parseFloat(priceContainer.text().replace(/[^0-9.]/g, ''));

		// On quantity change
		$('form.cart').on('input change', 'input[name="quantity"]', function() {
			let qty = parseInt($(this).val()) || 1;
			let total = (originalPrice * qty).toFixed(2);
			priceContainer.html('<bdi><span class="woocommerce-Price-currencySymbol">$</span>' + total + '</bdi>');
		});

		// Plus
		$('.increase').on('click', function() {
			let input = $(this).siblings('input[name="quantity"]');
			let val = parseInt(input.val()) || 1;
			let max = parseInt(input.attr('max')) || 999;
			if (val < max) {
				input.val(val + 1).trigger('change');
			}
		});

		// Minus
		$('.decrease').on('click', function() {
			let input = $(this).siblings('input[name="quantity"]');
			let val = parseInt(input.val()) || 1;
			let min = parseInt(input.attr('min')) || 1;
			if (val > min) {
				input.val(val - 1).trigger('change');
			}
		});
	});
</script>