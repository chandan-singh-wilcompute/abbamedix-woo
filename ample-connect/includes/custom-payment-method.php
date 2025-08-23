<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Gateway class
 */
if ( ! class_exists( 'WC_Gateway_Token_Payment' ) ) {

    class WC_Gateway_Token_Payment extends WC_Payment_Gateway {

        public function __construct() {
            $this->id                 = 'token_payment';
            $this->method_title       = 'Credit Card';
            $this->method_description = 'Pay using saved credit cards managed on an external system.';
            $this->has_fields         = true;

            $this->init_form_fields();
            $this->init_settings();

            $this->enabled     = $this->get_option( 'enabled' );
            $this->title       = $this->get_option( 'title' );
            $this->description = $this->get_option( 'description' );

            // Save settings
            add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, [ $this, 'process_admin_options' ] );
        }

        public function init_form_fields() {
            $this->form_fields = [
                'enabled' => [
                    'title'   => 'Enable/Disable',
                    'type'    => 'checkbox',
                    'label'   => 'Enable Credit Card Payment',
                    'default' => 'yes',
                ],
                'title' => [
                    'title'       => 'Title',
                    'type'        => 'text',
                    'description' => 'Title displayed to the user at checkout.',
                    'default'     => 'Credit Card',
                ],
                'description' => [
                    'title'       => 'Description',
                    'type'        => 'textarea',
                    'description' => 'Description displayed to the user at checkout.',
                    'default'     => 'Pay securely using your saved credit cards.',
                ],
            ];
        }

        public function payment_fields() {
            $tokens = Ample_Session_Cache::get( 'credit_cards' );

            if ( ! empty( $tokens ) ) {
                echo '<label for="token_selection">Select a Credit Card:</label>';
                echo '<select name="token_id" id="token_selection" style="margin-left:10px;width:150px">';
                foreach ( $tokens as $token ) {
                    echo '<option value="' . esc_attr( $token['id'] ) . '">' . esc_html( $token['protected_card_number'] ) . '</option>';
                }
                echo '</select>';
            } else {
                echo '<p>No credit cards available. Please add a credit card to your account.</p>';
            }
        }

        public function process_payment( $orderid ) {
            $order    = wc_get_order( $orderid );
            $token_id = isset( $_POST['token_id'] ) ? sanitize_text_field( $_POST['token_id'] ) : '';

            $response = Order_Process::purchase_order( $token_id );

            if ( isset( $response['purchased'] ) && $response['purchased'] === true ) {
                $order->payment_complete();

                $ample_order_id = $response['ample_order_id'];
                $order_note     = 'Payment completed successfully via credit card. External order id: ' . $ample_order_id . ' and API id: ' . $response['id'];
                $order->add_order_note( $order_note );

                update_post_meta( $orderid, '_external_order_number', $response['id'] );

                return [
                    'result'   => 'success',
                    'redirect' => $this->get_return_url( $order ),
                ];
            } else {
                wc_add_notice( 'Payment failed: ' . print_r( $response, true ), 'error' );

                return [
                    'result'   => 'fail',
                    'redirect' => '',
                ];
            }
        }
    }
}