<?php
/**
 * "Order received" message.
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/checkout/order-received.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 8.8.0
 *
 * @var WC_Order|false $order
 */

defined( 'ABSPATH' ) || exit;
?>

<div class="woocommerce-notice woocommerce-notice--success woocommerce-thankyou-order-received">
	<div class="container">
			<?php
			/**
			 * Filter the message shown after a checkout is complete.
			 *
			 * @since 2.2.0
			 *
			 * @param string         $message The message.
			 * @param WC_Order|false $order   The order created during checkout, or false if order data is not available.
			 */
			$message = apply_filters(
				'woocommerce_thankyou_order_received_text',
				esc_html( __( 'We have sent an order confirmation email. Once your order ships, we will send you a shipping confirmation email.', 'woocommerce' ) ),
				$order
			);

			$page_title = '<h1>ORDER CONFIRMED</h1>';

			$button_html = '<a href="' . esc_url( home_url( '/my-account/view-order/'.$order->id ) ) . '" class="button check-status thankyou-button">' . esc_html__( 'Check Status', 'woocommerce' ) . '</a>';
			$message = '<p class="woocommerce-notice woocommerce-notice--success woocommerce-thankyou-order-received">' .$page_title .'<br><span style="font-size:33px;">'. $message . ' </span><br>' . $button_html . '</p>';

			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			echo $message;
			?>
	</div>	
</div>
