<?php //phpcs:ignore WordPress.Files.FileName.InvalidClassFileName
/**
 * Implements the YWRAQ_Multi_Currency_Switcher class.
 *
 * @class   YWRAQ_Multi_Currency_Switcher
 * @package YITH\RequestAQuote
 * @since   3.7.0
 * @author  YITH <plugins@yithemes.com>
 */

defined( 'ABSPATH' ) || exit;


if ( ! class_exists( 'YWRAQ_Multi_Currency_Switcher' ) ) {
	/**
	 * Class YWRAQ_Multi_Currency_Switcher
	 */
	class YWRAQ_Multi_Currency_Switcher {


		/**
		 * Single instance of the class
		 *
		 * @var YWRAQ_Multi_Currency_Switcher
		 */
		protected static $instance;

		/**
		 * Returns single instance of the class
		 *
		 * @return YWRAQ_Multi_Currency_Switcher
		 * @since 3.7.0
		 */
		public static function get_instance() {
			return ! is_null( self::$instance ) ? self::$instance : self::$instance = new self();
		}


		/**
		 * Constructor
		 *
		 * Initialize class and registers actions and filters to be used
		 *
		 * @since  3.7.0
		 */
		public function __construct() {
			add_filter( 'ywraq_filter_cart_item_before_add_to_cart', array( $this, 'filter_cart_item_price' ), 10, 2 );
			add_filter( 'ywraq_order_discount_on_quote_accepted', array( $this, 'filter_coupon_amount' ), 10, 2 );
			add_filter( 'ywraq_woocommerce_shipping_method_add_rate', array( $this, 'convert_shipping_method_add_rate' ), 10, 1 );
			add_filter( 'ywraq_add_cart_fee', array( $this, 'add_cart_fee_convert_prices' ), 10, 1 );
			add_filter( 'ywraq_before_view_quote', array( $this, 'remove_currency_filters' ), 10 );
			add_filter( 'yith_ywraq_before_my_quotes', array( $this, 'remove_currency_filters' ), 10 );
		}


		/**
		 * Remove the currency symbol filter
		 */
		public function remove_currency_filters() {
			remove_filter( 'woocommerce_currency_symbol', array( YITH_WCMCS_Products::get_instance(), 'filter_currency_symbol' ), 20 );
		}

		/**
		 * Reconvert order prices into default currency before add the product to quote
		 *
		 * @param array    $cart_item_data Cart item data.
		 * @param WC_Order $order Order.
		 * @return array
		 * @since 3.7.0
		 */
		public function filter_cart_item_price( $cart_item_data, $order ) {

			if ( $order->get_currency() !== yith_wcmcs_get_wc_currency_options( 'currency' ) ) {
				$cart_item_data['ywraq_price'] = yith_wcmcs_convert_price(
					$cart_item_data['ywraq_price'],
					array(
						'from' => $order->get_currency(),
						'to'   => yith_wcmcs_get_wc_currency_options( 'currency' ),
					)
				);
			}

			return $cart_item_data;

		}

		/**
		 * Convert coupon amount into default currency before add the product to quote
		 *
		 * @param float    $coupon_amount Coupon amount.
		 * @param WC_Order $order Order.
		 * @return array
		 * @since 3.7.0
		 */
		public function filter_coupon_amount( $coupon_amount, $order ) {

			if ( $order->get_currency() !== yith_wcmcs_get_wc_currency_options( 'currency' ) ) {
				$coupon_amount = yith_wcmcs_convert_price(
					$coupon_amount,
					array(
						'from' => $order->get_currency(),
						'to'   => yith_wcmcs_get_wc_currency_options( 'currency' ),
					)
				);
			}

			return $coupon_amount;

		}

		/**
		 * Convert the shipping rate cost
		 *
		 * @param WC_Shipping_Rate $shipping_rate Shipping rate.
		 *
		 * @return WC_Shipping_Rate
		 */
		public function convert_shipping_method_add_rate( $shipping_rate ) {
			if ( $shipping_rate instanceof WC_Shipping_Rate ) {
				$cost = $shipping_rate->get_cost();
				if ( ! empty( $cost ) ) {
					$cost = yith_wcmcs_convert_price( $cost );
					$shipping_rate->set_cost( $cost );
				}

				$taxes = $shipping_rate->get_taxes();
				if ( ! empty( $taxes ) ) {
					foreach ( $taxes as $key => $tax ) {
						$taxes[ $key ] = yith_wcmcs_convert_price( $tax );
					}
					$shipping_rate->set_taxes( $taxes );
				}
			}

			return $shipping_rate;
		}

		/**
		 * Convert the shipping rate cost
		 *
		 * @param WC_Order_Item_Fee $fee Shipping rate.
		 *
		 * @return WC_Order_Item_Fee
		 */
		public function add_cart_fee_convert_prices( $fee ) {
			if ( $fee instanceof WC_Order_Item_Fee ) {
				$total = $fee->get_total();
				if ( ! empty( $total ) ) {
					$total = yith_wcmcs_convert_price( $total );
					$fee->set_total( $total );
				}

				$taxes = $fee->get_taxes();
				if ( ! empty( $taxes['total'] ) ) {
					foreach ( $taxes['total'] as $key => $tax ) {
						$taxes['total'][ $key ] = yith_wcmcs_convert_price( $tax );
					}
					$fee->set_taxes( $taxes );
				}
			}

			return $fee;
		}
	}

	/**
	 * Unique access to instance of YWRAQ_Multi_Currency_Switcher class
	 *
	 * @return YWRAQ_Multi_Currency_Switcher
	 */
	function ywraq_multi_currency_switcher() { //phpcs:ignore
		return YWRAQ_Multi_Currency_Switcher::get_instance();
	}

	ywraq_multi_currency_switcher();

}
