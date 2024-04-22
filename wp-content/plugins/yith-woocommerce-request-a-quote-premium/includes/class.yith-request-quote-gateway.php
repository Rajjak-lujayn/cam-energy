<?php //phpcs:ignore WordPress.Files.FileName.InvalidClassFileName
/**
 * Implements the YITH_YWRAQ_Gateway class.
 *
 * @class   YITH_YWRAQ_Gateway
 * @since   1.0.0
 * @author  YITH <plugins@yithemes.com>
 * @package YITH\RequestAQuote
 */


defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'YITH_YWRAQ_Gateway' ) ) {
	/**
	 * Class YITH_YWRAQ_Gateway
	 */
	class YITH_YWRAQ_Gateway extends WC_Payment_Gateway {
		/**
		 * Constructor for the gateway.
		 */
		public function __construct() {
			$this->setup_properties();
		}

		/**
		 * Init settings for gateways.
		 */
		public function init_settings() {
			parent::init_settings();
			$this->enabled = ! empty( $this->settings['enabled'] ) && 'yes' === $this->settings['enabled'] ? 'yes' : 'no';
		}

		/**
		 * Setup general properties for the gateway.
		 */
		protected function setup_properties() {
			$this->id                 = 'yith-request-a-quote';
			$this->has_fields         = false;
			$this->title              = apply_filters( 'ywraq_payment_method_label', esc_html__( 'YITH Request a Quote', 'yith-woocommerce-request-a-quote' ) );
			$this->method_title       = apply_filters( 'ywraq_payment_method_label', esc_html__( 'YITH Request a Quote', 'yith-woocommerce-request-a-quote' ) );
			$this->method_description = esc_html__( 'Allows to request a quote at checkout.', 'yith-woocommerce-request-a-quote' );
			$this->description        = '';
			$this->enabled            = 'yes';
			$this->supports           = array();
		}

		/**
		 * Process the payment and return the result.
		 *
		 * @param int $order_id Order ID.
		 * @return array
		 */
		public function process_payment( $order_id ) {

			$order = wc_get_order( $order_id );

			$raq = array(
				'user_name'     => trim( $order->get_billing_first_name() . ' ' . $order->get_billing_last_name() ),
				'user_email'    => $order->get_billing_email(),
				'user_message'  => $order->get_customer_note(),
				'from_checkout' => 'yes',
			);

			$order->update_meta_data( 'ywraq_customer_name', $raq['user_name'] );
			$order->update_meta_data( 'ywraq_customer_email', $raq['user_email'] );
			$order->update_meta_data( 'ywraq_customer_message', $raq['user_message'] );
			$order->update_meta_data( '_ywraq_from_checkout', 1 );
			$order->update_meta_data( '_ywraq_pay_quote_now', apply_filters( 'ywraq_pay_quote_now_value_on_save_order', 1, $order->get_id() ) );

			$order->set_status( 'ywraq-new' );
			YITH_YWRAQ_Order_Request()->add_order_meta( $order, array() );

			WC()->session->set( 'raq_new_order', $order->get_id() );

			$order->save();
			/**
			 * DO_ACTION:send_raq_mail
			 *
			 * This action triggers to send the quote email
			 *
			 * @param   array  $raq  List of arguments useful to send the email with quote.
			 */
			do_action( 'send_raq_mail', $raq );
			/**
			 * DO_ACTION:send_raq_customer_mail
			 *
			 * This action triggers to send the quote to customer
			 *
			 * @param   array  $raq  List of arguments useful to send the email with quote.
			 */
			do_action( 'send_raq_customer_mail', $raq );

			WC()->cart->empty_cart( true );
			WC()->cart->persistent_cart_destroy();
			$order->add_order_note( esc_html__( 'This quote has been submitted from the checkout page.', 'yith-woocommerce-request-a-quote' ) );
			/**
			 * DO_ACTION:ywraq_after_create_order_from_checkout
			 *
			 * This action triggers after the creation of the order on checkout page
			 *
			 * @param   array     $raq    Quote information.
			 * @param   WC_Order  $order  Order with quote.
			 */
			do_action( 'ywraq_after_create_order_from_checkout', $raq, $order );

			// Return thankyou redirect.
			return array(
				'result'   => 'success',
				'redirect' => apply_filters(
					'woocommerce_checkout_no_payment_needed_redirect',
					YITH_Request_Quote()->get_redirect_page_url(
						array(
							'hidem' => 1,
							'order' => $order->get_id(),
						)
					),
					$order
				),
			);

		}
	}


}
