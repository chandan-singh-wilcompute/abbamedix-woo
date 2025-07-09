<?php
/**
 * Order Customer Details
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/order/order-details-customer.php.
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

$show_shipping = ! wc_ship_to_billing_address_only() && $order->needs_shipping_address();
?>
<section class="woocommerce-customer-details mb-0">
    <div class="container">

        <?php if ( $show_shipping ) : ?>

        <section class="woocommerce-columns woocommerce-columns--2 woocommerce-columns--addresses col2-set addresses" style="font-size: 25px;">
            

        <?php endif; ?>

        <h4 class="woocommerce-column__title"><?php esc_html_e( 'Contact details', 'healfio' ); ?></h4>
        <p><strong>Contact :</strong> <?php echo wp_kses(( $order->get_billing_first_name( esc_html__( 'N/A', 'healfio' ) )), 'regular' ); ?> <?php echo wp_kses(( $order->get_billing_last_name( esc_html__( 'N/A', 'healfio' ) )), 'regular' ); ?>, <?php echo wp_kses(( $order->get_billing_email( esc_html__( 'N/A', 'healfio' ) )), 'regular' ); ?>, <?php echo wp_kses(( $order->get_billing_phone( esc_html__( 'N/A', 'healfio' ) )), 'regular' ); ?></p>

        <?php if ( $show_shipping ) : ?>
        <?php
            $shipping_first_name = $order->get_shipping_first_name();
            $shipping_last_name  = $order->get_shipping_last_name();
            $shipping_company    = $order->get_shipping_company();
            $shipping_address_1  = $order->get_shipping_address_1();
            $shipping_address_2  = $order->get_shipping_address_2();
            $shipping_city       = $order->get_shipping_city();
            $shipping_state      = $order->get_shipping_state();
            $shipping_postcode   = $order->get_shipping_postcode();
            $shipping_country    = WC()->countries->countries[ $order->get_shipping_country() ];

            $shipping_address_components = array();
            if ( $shipping_first_name ) {
                $shipping_address_components[] = $shipping_first_name .' '.$shipping_last_name;
            }
            if ( $shipping_company ) {
                $shipping_address_components[] = $shipping_company;
            }
            if ( $shipping_address_1 ) {
                $shipping_address_components[] = $shipping_address_1;
            }
            if ( $shipping_address_2 ) {
                $shipping_address_components[] = $shipping_address_2;
            }
            if ( $shipping_city ) {
                $shipping_address_components[] = $shipping_city;
            }
            if ( $shipping_state ) {
                $shipping_address_components[] = $shipping_state.' '.$shipping_postcode;
            }
            if ( $shipping_country ) {
                $shipping_address_components[] = $shipping_country;
            }

            // Join the shipping address components with a comma and space
            $shipping_address = implode( ', ', $shipping_address_components );

            // Output the shipping address if any components exist
            if ( ! empty( $shipping_address ) ) {
                echo '<p><strong>Shipping address:</strong> ' . wp_kses_post( $shipping_address ) . '</p>';
            }
        ?>
        <?php endif; ?>
            <p style="word-break: break-word;"><strong>Delivery Option :</strong> <?php echo wp_kses_post( $order->get_payment_method_title() ); ?> - <?php echo $order->get_formatted_order_total(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></p>
        </section>

        <?php do_action( 'woocommerce_order_details_after_customer_details', $order ); ?>
    </div>

</section>
