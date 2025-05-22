<?php
/**
 * Order details
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/order/order-details.php.
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

$order = wc_get_order( $order_id ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited

if ( ! $order ) {
	return;
}

$order_items           = $order->get_items( apply_filters( 'woocommerce_purchase_order_item_types', 'line_item' ) );
$show_purchase_note    = $order->has_status( apply_filters( 'woocommerce_purchase_note_order_statuses', array( 'completed', 'processing' ) ) );
$show_customer_details = is_user_logged_in() && $order->get_user_id() === get_current_user_id();
$downloads             = $order->get_downloadable_items();
$show_downloads        = $order->has_downloadable_item() && $order->is_download_permitted();

if ( $show_downloads ) {
	wc_get_template(
		'order/order-downloads.php',
		array(
			'downloads'  => $downloads,
			'show_title' => true,
		)
	);
}
?>
<section class="woocommerce-order-details">
	<?php do_action( 'woocommerce_order_details_before_order_table', $order ); ?>

	<h4 class="woocommerce-order-details__title"><?php esc_html_e( 'Order details', 'healfio' ); ?></h4>
	<div class="row" style="margin-right: 0px; margin-left: 0px;">
		<div class="col-md-7" style="border: 3px solid #007866;">
			<table class="woocommerce-table woocommerce-table--order-details shop_table order_details">

				<tbody>
					<?php
					do_action( 'woocommerce_order_details_before_order_table_items', $order );

					foreach ( $order_items as $item_id => $item ) {
						$product = $item->get_product();

						wc_get_template(
							'order/order-details-item.php',
							array(
								'order'              => $order,
								'item_id'            => $item_id,
								'item'               => $item,
								'show_purchase_note' => $show_purchase_note,
								'purchase_note'      => $product ? $product->get_purchase_note() : '',
								'product'            => $product,
							)
						);
					}

					do_action( 'woocommerce_order_details_after_order_table_items', $order );
					?>
				</tbody>

				
			</table>
		</div>
		<div class="col-md-4 offset-1" style="border: 3px solid #007866;">
			<h4>Order Summary</h4> 
			<table class="woocommerce-table woocommerce-table--order-details shop_table order_details">
				<thead>
				</thead>
				<tbody>
					<?php
						$order_items = $order->get_order_item_totals();
						$last_item_key = array_key_last($order_items);
						foreach ( $order_items as $key => $total ) {
							$last_class = ($key === $last_item_key) ? ' class="order-summary-last-item"' : '';
							?>
								<tr <?php echo $last_class; ?>>
									<th scope="row"><?php echo esc_html( $total['label'] ); ?></th>
									<td><?php echo ( 'payment_method' === $key ) ? esc_html( $total['value'] ) : wp_kses_post( $total['value'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></td>
								</tr>
								<?php
						}
						?>
						<?php if ( $order->get_customer_note() ) : ?>
							<tr>
								<th><?php esc_html_e( 'Note:', 'healfio' ); ?></th>
								<td><?php echo wp_kses_post( nl2br( wptexturize( $order->get_customer_note() ) ) ); ?></td>
							</tr>
					<?php endif; ?>
				</tbody>
			</table>
			<tfoot>

			</tfoot>
		</div>
	</div>
	

	<?php do_action( 'woocommerce_order_details_after_order_table', $order ); ?>
</section>

<?php
/**
 * Action hook fired after the order details.
 *
 * @since 4.4.0
 * @param WC_Order $order Order data.
 */
do_action( 'woocommerce_after_order_details', $order );

if ( $show_customer_details ) {
	wc_get_template( 'order/order-details-customer.php', array( 'order' => $order ) );
}
