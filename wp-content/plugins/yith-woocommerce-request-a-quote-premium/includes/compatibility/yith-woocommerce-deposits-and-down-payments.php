<?php //phpcs:ignore WordPress.Files.FileName.InvalidClassFileName
/**
 * Implements the YWRAQ_Deposits class.
 *
 * @class   YWRAQ_Deposits
 * @package YITH\RequestAQuote
 * @since   2.1.0
 * @author  YITH <plugins@yithemes.com>
 */

defined( 'ABSPATH' ) || exit;


if ( ! class_exists( 'YWRAQ_Deposits' ) ) {
	/**
	 * Class YWRAQ_Deposits
	 */
	class YWRAQ_Deposits {


		/**
		 * Single instance of the class
		 *
		 * @var \YWRAQ_Deposits
		 */
		protected static $instance;

		/**
		 * Returns single instance of the class
		 *
		 * @return YWRAQ_Deposits
		 * @since 2.1.0
		 */
		public static function get_instance() {
			return ! is_null( self::$instance ) ? self::$instance : self::$instance = new self();
		}

		/**
		 * Constructor
		 *
		 * Initialize class and registers actions and filters to be used
		 *
		 * @since  2.1.0
		 */
		public function __construct() {
			// admin order metabox.
			add_filter( 'ywraq_order_metabox', array( $this, 'metabox_deposit_options' ) );
			add_filter( 'woocommerce_add_cart_item_data', array( $this, 'update_cart_item_data' ), 40, 3 );
			add_filter( 'woocommerce_get_order_item_totals', array( $this, 'add_deposit_amount' ), 10, 4 );
			add_filter( 'yith_wcdp_full_amount_item', array( $this, 'change_full_amount_item' ), 20, 3 );
			add_filter( 'yith_wcdp_full_amount_order_item_html', array( $this, 'change_full_amount_order_item' ), 20, 4 );

			add_action( 'ywraq_after_order_accepted', array( $this, 'yith_remove_raq_coupon_for_deposit' ), 30, 1 );
			add_filter( 'woocommerce_get_cart_item_from_session', array( $this, 'set_product_price_raq_from_session' ), 999, 3 );

			add_action( 'yith_wcdp_after_add_to_support_cart', array( $this, 'yith_deposit_fix_support_cart_price' ), 30, 6 );
			add_filter( 'yith_wcdp_deposit_rate', array( $this, 'yith_wcdp_deposit_rate_from_session' ) );
		}

		/**
		 * Add deposit amount
		 *
		 * @param array    $total_rows Total rows.
		 * @param WC_Order $order .
		 * @param mixed    $tax_display Excl or incl tax display mode.
		 *
		 * @return mixed
		 */
		public function add_deposit_amount( $total_rows, $order, $tax_display ) {
			$is_quote = YITH_YWRAQ_Order_Request()->is_quote( $order->get_id() );

			$deposit_enabled = $order->get_meta( '_ywraq_deposit_enable' );
			$pay_now         = ywraq_get_payment_option( 'ywraq_pay_quote_now', $order );

			$has_deposit = $order->get_meta( '_has_deposit' );
			// check if the deposit is enabled on quote.
			if ( ! $is_quote || $has_deposit || ! ywraq_is_true( $deposit_enabled ) || ywraq_is_true( $pay_now ) ) {
				return $total_rows;
			}

			$deposit_rate  = (int) $order->get_meta( '_ywraq_deposit_rate' );
			$deposit_value = apply_filters( 'yith_ywraq_quote_deposit_amount', ( $order->get_total() - $order->get_total_fees() ) * (float) $deposit_rate / 100 + $order->get_total_fees(), $order, $deposit_rate );
			if ( $deposit_value > 0 ) {
				// translators: 1. deposit value 2. total amount.
				$deposit_amount        = sprintf( esc_html_x( '%1$s (of %2$s)', '1. deposit value 2. total amount.', 'yith-woocommerce-request-a-quote' ), wc_price( $deposit_value, array( 'currency' => $order->get_currency() ) ), $order->get_formatted_order_total( $tax_display ) );
				$total_rows['deposit'] = array(
					'label' => esc_html__( 'Pay now:', 'yith-woocommerce-request-a-quote' ),
					'value' => $deposit_amount,
				);
			}

			return $total_rows;
		}

		/**
		 * Update cart item data.
		 *
		 * @param array $cart_item_data .
		 * @param int   $product_id .
		 * @param int   $variation_id .
		 *
		 * @return mixed
		 */
		public function update_cart_item_data( $cart_item_data, $product_id, $variation_id ) {
			$order_id = WC()->session->get( 'order_awaiting_payment' );

			if ( $order_id ) {
				$order = wc_get_order( $order_id );
				if ( ! $order || ! YITH_YWRAQ_Order_Request()->is_quote( $order_id ) ) {
					return $cart_item_data;
				}
			} else {
				return $cart_item_data;
			}

			$deposit_enabled = $order->get_meta( '_ywraq_deposit_enable' );
			$pay_now         = ywraq_get_payment_option( 'ywraq_pay_quote_now', $order );

			// check if the deposit is enabled on quote.
			if ( ! ywraq_is_true( $deposit_enabled ) || ywraq_is_true( $pay_now ) ) {
				return $cart_item_data;
			}

			$tax_display    = $order->get_prices_include_tax( 'edit' );
			$order_discount = $order->get_total_discount( ! $tax_display );
			// retrieve specific item in the order.
			foreach ( $order->get_items() as $order_item ) {
				/**
				 * Order item product
				 *
				 * @var $order_item \WC_Order_Item_Product
				 */
				if ( $order_item->get_product_id() === $product_id && $order_item->get_variation_id() === $variation_id ) {
					$product_discount = wc_format_decimal( $order_item->get_subtotal() - $order_item->get_total(), '' );
					if ( $tax_display ) {
						$product_tax_discount = wc_format_decimal( $order_item->get_subtotal_tax() - $order_item->get_total_tax(), '' );
					} else {
						$product_tax_discount = 0;
					}
					$product_total_discount = ( $product_discount + $product_tax_discount ) / $order_item->get_quantity();

				}
			}

			$product_id = ! empty( $variation_id ) ? $variation_id : $product_id;
			$product    = wc_get_product( $product_id );

			$original_product_price = isset( $cart_item_data['ywraq_price'] ) ? $cart_item_data['ywraq_price'] : $product->get_price();

			$original_product_price -= $product_total_discount;

			$deposit_rate                          = (int) $order->get_meta( '_ywraq_deposit_rate' );
			$deposit_value                         = $original_product_price * (float) $deposit_rate / 100;
			$deposit_balance                       = $original_product_price - $deposit_value;
			$cart_item_data['deposit']             = true;
			$cart_item_data['deposit_type']        = 'amount';
			$cart_item_data['deposit_amount']      = 0;
			$cart_item_data['deposit_rate']        = $deposit_rate;
			$cart_item_data['deposit_value']       = $deposit_value + $product_total_discount;
			$cart_item_data['deposit_balance']     = $deposit_balance;
			$cart_item_data['ywraq_discount']      = $product_total_discount;
			$cart_item_data['ywraq_product_price'] = ( $deposit_value );

			return $cart_item_data;
		}

		/**
		 * Change full amount
		 *
		 * @param array $full_amount_item .
		 * @param array $cart_item .
		 * @param array $data .
		 *
		 * @return array
		 */
		public function change_full_amount_item( $full_amount_item, $cart_item, $data ) {

			if ( ! empty( $cart_item['ywraq_discount'] ) ) {

				$discount       = $cart_item['ywraq_discount'];
				$price          = $full_amount_item['value'];
				$new_price      = ( $price - $discount );
				$new_price_html = wc_get_price_to_display(
					$cart_item['data'],
					array(
						'qty'   => $cart_item['quantity'],
						'price' => $new_price,
					)
				);
				$price_html     = wc_format_sale_price( $price, $new_price_html );

				$full_amount_item['display'] = $price_html;
				$full_amount_item['value']   = $new_price;
				$cart_item['data']->set_price( $new_price * ( $cart_item['deposit_rate'] / 100 ) );

			}

			return $full_amount_item;
		}

		/**
		 * Change full amount order item
		 *
		 * @param string                $full_amount_html .
		 * @param float                 $full_amount .
		 * @param WC_Order_Item_Product $item .
		 * @param WC_Order              $order .
		 * @return mixed|string
		 */
		public function change_full_amount_order_item( $full_amount_html, $full_amount, $item, $order ) {

			if ( isset( $item['ywraq_discount'] ) ) {
				$new_amount      = $full_amount - $item['ywraq_discount'];
				$new_amount_html = wc_get_price_to_display(
					$item->get_product(),
					array(
						'qty'   => $item['qty'],
						'price' => $new_amount,
						'order' => $order,
					)
				);

				$full_amount_html = wc_format_sale_price( $full_amount_html, $new_amount_html );

			}

			return $full_amount_html;
		}

		/**
		 * Remove Raq coupon for deposit.
		 *
		 * @param int $order_id Order id.
		 */
		public function yith_remove_raq_coupon_for_deposit( $order_id ) {

			if ( YITH_YWRAQ_Order_Request()->is_quote( $order_id ) ) {
				$order           = wc_get_order( $order_id );
				$deposit_enabled = $order->get_meta( '_ywraq_deposit_enable' );
				$pay_now         = ywraq_get_payment_option( 'ywraq_pay_quote_now', $order );

				// check if the deposit is enabled on quote.
				if ( ywraq_is_true( $deposit_enabled ) && ! ywraq_is_true( $pay_now ) ) {

					$coupon_label = 'quotediscount_' . $order_id;

					if ( WC()->cart->has_discount( $coupon_label ) ) {

						WC()->cart->remove_coupon( $coupon_label );
					}
				}
			}

		}

		/**
		 * Set product price
		 *
		 * @param array  $session_data .
		 * @param array  $values .
		 * @param string $key .
		 *
		 * @return  array
		 */
		public function set_product_price_raq_from_session( $session_data, $values, $key ) {

			if ( isset( $session_data['ywraq_product_price'] ) ) {
				$new_price = $session_data['ywraq_product_price'];
				$session_data['data']->set_price( $new_price );

			}

			return $session_data;
		}

		/**
		 * Fix cart price
		 *
		 * @param int    $product_id .
		 * @param int    $quantity .
		 * @param int    $variation_id .
		 * @param array  $variation .
		 * @param array  $cart_item .
		 * @param Object $cart_item_data .
		 */
		public function yith_deposit_fix_support_cart_price( $product_id, $quantity, $variation_id, $variation, $cart_item, $cart_item_data ) {

			if ( isset( $cart_item['ywraq_discount'] ) && isset( $cart_item['ywraq_price'] ) && ! empty( $cart_item_data['data'] ) ) {
				$discount  = $cart_item['ywraq_discount'];
				$price     = $cart_item['ywraq_price'];
				$new_price = ( $price - $discount );
				$cart_item_data['data']->set_price( $new_price );
			}

		}

		/**
		 * Add deposit options to order metabox.
		 *
		 * @param array $options Options.
		 *
		 * @return array
		 */
		public function metabox_deposit_options( $options ) {
			$new_options = array();
			foreach ( $options as $key => $item ) {
				$new_options[ $key ] = $item;
				if ( 'ywraq_customer_sep2' === $key ) {
					$new_options['ywraq_deposit_enable'] = array(
						'label' => __( 'Enable Deposit', 'yith-woocommerce-request-a-quote' ),
						'type'  => 'onoff',
						'desc'  => '',
						'std'   => 'no',
					);

					$new_options['ywraq_deposit_rate'] = array(
						'label'   => __( 'Deposit Rate', 'yith-woocommerce-request-a-quote' ),
						'type'    => 'number',
						'desc'    => __( 'Percentage of product total price required as deposit', 'yith-woocommerce-request-a-quote' ),
						'css'     => 'min-width: 100px;',
						'std'     => apply_filters( 'ywraq_default_deposit_rate', 10 ),
						'step'    => 'any',
						'min'     => 0,
						'max'     => 100,
					);

					$new_options['ywraq_deposit_sep'] = array(
						'type' => 'sep',
					);
				}
			}

			return $new_options;
		}

		/**
		 * Deposit rate
		 *
		 * @param int $deposit_rate .
		 *
		 * @return array|string
		 */
		public function yith_wcdp_deposit_rate_from_session( $deposit_rate ) {

			if ( isset( WC()->session ) && WC()->session->get( 'order_awaiting_payment' ) ) {
				$order_id = WC()->session->get( 'order_awaiting_payment' );
				$order    = wc_get_order( $order_id );
				if ( $order ) {
					$deposit_rate = (int) $order->get_meta( '_ywraq_deposit_rate' );
				}
			}

			return $deposit_rate;
		}

	}

	/**
	 * Unique access to instance of YWRAQ_Deposits class
	 *
	 * @return \YWRAQ_Deposits
	 */
	function YWRAQ_Deposits() { //phpcs:ignore
		return YWRAQ_Deposits::get_instance();
	}

	YWRAQ_Deposits();

}
