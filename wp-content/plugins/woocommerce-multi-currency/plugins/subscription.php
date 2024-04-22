<?php

/**
 * Class WOOMULTI_CURRENCY_Plugin_Subscription
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WOOMULTI_CURRENCY_Plugin_Subscription {
	protected $settings;
	public $cart_item_key = 'subscription_renewal';

	public function __construct() {

		$this->settings = WOOMULTI_CURRENCY_Data::get_ins();
		if ( $this->settings->get_enable() ) {
			add_filter( 'woocommerce_subscriptions_product_price', array( $this, 'get_price' ) );
			add_filter( 'woocommerce_subscriptions_product_sale_price', array( $this, 'revert_sale_price' ),10,2 );
//			add_filter( 'wc_epo_price', array( $this, 'get_price' ) );
//			add_filter( 'woocommerce_tm_epo_price_on_cart', array( $this, 'get_price' ) );

			// Make sure renewal meta data persists between sessions
			if ( is_plugin_active( 'woocommerce-subscriptions/woocommerce-subscriptions.php' ) ) {
				add_filter( 'woocommerce_get_cart_item_from_session', array( $this, 'get_cart_item_from_session' ), 11, 3 );
			}
		}
	}

	/**
	 * Checks the cart to see if it contains a subscription renewal item.
	 *
	 * @return bool | Array The cart item containing the renewal, else false.
	 * @see wcs_cart_contains_renewal()
	 * @since  2.0.10
	 */
	protected function cart_contains() {
		return wcs_cart_contains_renewal();
	}

	/**
	 * Get the order object used to construct the renewal cart.
	 *
	 * @param Array The renewal cart item.
	 *
	 * @return WC_Order | The order object
	 * @since  2.0.13
	 */
	protected function get_order( $cart_item = '' ) {
		$order = false;

		if ( empty( $cart_item ) ) {
			$cart_item = $this->cart_contains();
		}

		if ( false !== $cart_item && isset( $cart_item[ $this->cart_item_key ] ) ) {
			$order = wc_get_order( $cart_item[ $this->cart_item_key ]['renewal_order_id'] );
		}

		return $order;
	}

	/**
	 * Restore renewal flag when cart is reset and modify Product object with renewal order related info
	 *
	 * @since 2.0
	 */
	public function get_cart_item_from_session( $cart_item_session_data, $cart_item, $key ) {
		if ( isset( $cart_item[ $this->cart_item_key ]['subscription_id'] ) ) {
			$rate             = 1;
			$related_order_id = wp_get_post_parent_id( $cart_item[ $this->cart_item_key ]['subscription_id'] );
			$order_currency   = get_post_meta( $related_order_id, '_order_currency', true );
			$wmc_order_info   = get_post_meta( $related_order_id, 'wmc_order_info', true );
			if ( is_array( $wmc_order_info ) && count( $wmc_order_info ) ) {
				foreach ( $wmc_order_info as $wmc_order_info_k => $wmc_order_info_v ) {
					if ( isset( $wmc_order_info_v['is_main'] ) && $wmc_order_info_v['is_main'] == 1 ) {
						$base_currency = $wmc_order_info_k;
						if ( $order_currency != $base_currency ) {
							if ( isset( $wmc_order_info[ $order_currency ] ) && is_array( $wmc_order_info[ $order_currency ] ) ) {
								if ( isset( $wmc_order_info[ $order_currency ]['rate'] ) && $wmc_order_info[ $order_currency ]['rate'] ) {
									$rate = $wmc_order_info[ $order_currency ]['rate'];
								}
							}
						}
						break;
					}
				}
			}
			$cart_item_session_data[ $this->cart_item_key ] = $cart_item[ $this->cart_item_key ];

			$_product = $cart_item_session_data['data'];

			// Need to get the original subscription or order price, not the current price
			$subscription = $this->get_order( $cart_item );

			if ( $subscription ) {

				$subscription_items = $subscription->get_items();
				$item_to_renew      = $subscription_items[ $cart_item_session_data[ $this->cart_item_key ]['line_item_id'] ];

				$price = $item_to_renew['line_subtotal'] / $rate;

				if ( $_product->is_taxable() && wc_prices_include_tax() ) {

					if ( apply_filters( 'woocommerce_adjust_non_base_location_prices', true ) ) {
						$base_tax_rates = WC_Tax::get_base_tax_rates( wcs_get_objects_property( $_product, 'tax_class' ) );
					} else {
						$base_tax_rates = WC_Tax::get_rates( wcs_get_objects_property( $_product, 'tax_class' ) );
					}

					$base_taxes_on_item = WC_Tax::calc_tax( $price, $base_tax_rates, false, false );
					$price              += array_sum( $base_taxes_on_item );
				}

				$_product->set_price( $price / $item_to_renew['qty'] );

				// Don't carry over any sign up fee
				wcs_set_objects_property( $_product, 'subscription_sign_up_fee', 0, 'set_prop_only' );

				// Allow plugins to add additional strings to the product name for renewals
				$line_item_name = is_callable( $item_to_renew, 'get_name' ) ? $item_to_renew->get_name() : $item_to_renew['name'];
				wcs_set_objects_property( $_product, 'name', apply_filters( 'woocommerce_subscriptions_renewal_product_title', $line_item_name, $_product ), 'set_prop_only' );

				// Make sure the same quantity is renewed
				$cart_item_session_data['quantity'] = $item_to_renew['qty'];
			}
		}

		return $cart_item_session_data;
	}

	/**
	 * WooCommerce Subscription
	 *
	 * @param $data
	 *
	 * @return mixed
	 */
	public function get_price( $price ) {

		return wmc_get_price( $price );
	}

	public function revert_sale_price( $sale_price, $product ) {
		$sale_price = $product->get_sale_price( 'edit' );

		return $sale_price;
	}
}