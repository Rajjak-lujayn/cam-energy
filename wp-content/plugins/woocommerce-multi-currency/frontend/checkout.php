<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class WOOMULTI_CURRENCY_Frontend_Checkout
 */
class WOOMULTI_CURRENCY_Frontend_Checkout {

	public $settings;
	public $old_currency;
	public $rate;

	function __construct() {
		$this->settings = WOOMULTI_CURRENCY_Data::get_ins();
		if ( $this->settings->get_enable() ) {
			add_action( 'woocommerce_checkout_process', array( $this, 'woocommerce_checkout_process' ) );
			add_action( 'woocommerce_checkout_update_order_review', array( $this, 'woocommerce_checkout_update_order_review' ), 99 );

			add_filter( 'woocommerce_available_payment_gateways', array( $this, 'control_payment_methods' ), 12 );
			add_action( 'woocommerce_before_checkout_process', array( $this, 'change_currency_to_checkout' ) );

			add_action( 'woocommerce_checkout_init', array( $this, 'checkout_init' ) );
			add_filter( 'woocommerce_cart_totals_order_total_html', array( $this, 'previous_currency_order_total' ) );
			add_filter( 'woocommerce_cart_totals_taxes_total_html', array( $this, 'previous_currency_taxes_total' ) );
			add_filter( 'woocommerce_cart_totals_fee_html', array( $this, 'previous_currency_fee_html' ), 10, 2 );
			add_filter( 'woocommerce_cart_subtotal', array( $this, 'previous_currency_cart_subtotal' ), 10, 3 );
			add_filter( 'woocommerce_cart_product_subtotal', array( $this, 'previous_currency_item_subtotal' ), 10, 4 );
			add_filter( 'woocommerce_cart_totals_coupon_html', array( $this, 'previous_currency_coupon_html' ), 10, 3 );
			add_filter( 'woocommerce_cart_shipping_method_full_label', array( $this, 'previous_currency_shipping_label' ), 10, 2 );

			add_filter( 'woocommerce_checkout_get_value', array( $this, 'save_shipping_country' ), 10, 2 );
			add_filter( 'woocommerce_ship_to_different_address_checked', array( $this, 'save_shipping_to_different_address' ) );

			//Payment
			add_filter( 'woocommerce_paypal_args', array( $this, 'payment_args' ), 10, 2 );
		}
	}

	/**
	 * @param $methods
	 */
	public function control_payment_methods( $payment_methods ) {

		if ( is_admin() ) {
			return $payment_methods;
		}
		$current_currency = $this->settings->get_current_currency();
		if ( $this->settings->get_enable_multi_payment() ) {
			$payments = $this->settings->get_payments_by_currency( $current_currency );
			if ( is_array( $payments ) && count( $payments ) ) {
				foreach ( $payment_methods as $key => $payment_method ) {
					if ( ! in_array( $key, $payments ) ) {
						unset( $payment_methods[ $key ] );
					}
				}
			}
		}

		return $payment_methods;
	}

	/**
	 * Update checkout page with one currency
	 */
	public function woocommerce_checkout_update_order_review( $data ) {

		$allow_multi = $this->settings->get_enable_multi_payment();
		if ( $allow_multi ) {
			$change_currency_option = $this->settings->get_param( 'billing_shipping_currency' );;

			if ( ! $change_currency_option ) {
				return;
			}

			$checkout_currency_args = $this->settings->get_checkout_currency_args();//array('USD')
			$current_currency       = $this->settings->get_current_currency(); //vnd
			$checkout_currency      = $this->settings->get_checkout_currency();//gbp

			if ( is_string( $data ) ) {
				parse_str( $data, $data );
			}

			if ( isset( $data['ship_to_different_address'] ) ) {
				wc()->session->set( 'wmc_ship_to_different_address', true );
			} else {
				wc()->session->__unset( 'wmc_ship_to_different_address' );
			}

			WC()->customer->set_props( array( 'billing_country' => isset( $_POST['country'] ) ? wc_clean( wp_unslash( $_POST['country'] ) ) : null ) );

			if ( wc_ship_to_billing_address_only() ) {
				WC()->customer->set_props( array( 'shipping_country' => isset( $_POST['country'] ) ? wc_clean( wp_unslash( $_POST['country'] ) ) : null ) );
			} else {
				WC()->customer->set_props( array( 'shipping_country' => isset( $_POST['s_country'] ) ? wc_clean( wp_unslash( $_POST['s_country'] ) ) : null ) );
			}

			WC()->customer->save();
			$country = '';
			switch ( $change_currency_option ) {
				case 1:
					$country = wc()->customer->get_billing_country();
					break;
				case 2:
					$country = wc()->customer->get_shipping_country();
					break;
			}

			if ( ! $country ) {
				return;
			}

			$auto_detect = $this->settings->get_auto_detect();
			$currency    = '';
			if ( $auto_detect == 1 ) {
				$currency = $this->settings->get_currency_by_detect_country( $country );
			}

			if ( ! $currency ) {
				$currency = $this->settings->get_currency_code( $country );
			}

			$currencies_list = $this->settings->get_currencies();

			if ( ! $currency || $currency == $current_currency ) {
				return;
			}

			if ( ! in_array( $currency, $checkout_currency_args ) || ! in_array( $currency, $currencies_list ) ) {
				return;
			}

			$this->settings->set_current_currency( $currency, false );
			$this->reload_after_update_order_review();
		}

//			if ( ! empty( $checkout_currency_args ) && $checkout_currency && ! in_array( $current_currency, $checkout_currency_args ) ) {
//				$this->settings->set_current_currency( $checkout_currency, false );
//				$this->reload_after_update_order_review();
//			}
//		}
	}

	public function reload_after_update_order_review() {
		$messages = '';
		// Get order review fragment
		ob_start();
		woocommerce_order_review();
		$woocommerce_order_review = ob_get_clean();

		// Get checkout payment fragment
		ob_start();
		woocommerce_checkout_payment();
		$woocommerce_checkout_payment = ob_get_clean();
		wp_send_json(
			array(
				'result'    => 'failure',
				'messages'  => $messages,
				'reload'    => true,
				'fragments' => apply_filters(
					'woocommerce_update_order_review_fragments', array(
						'.woocommerce-checkout-review-order-table' => $woocommerce_order_review,
						'.woocommerce-checkout-payment'            => $woocommerce_checkout_payment,
					)
				),
			)
		);
	}

	public function save_shipping_country( $value, $input ) {
		if ( $input == 'shipping_country' ) {
			$value = wc()->customer->get_shipping_country();
		}

		return $value;
	}

	public function save_shipping_to_different_address() {
		return wc()->session->get( 'wmc_ship_to_different_address' );
	}

	/**
	 * Compare currency on checkout page
	 */
	public function woocommerce_checkout_process() {
		$allow_multi = $this->settings->get_enable_multi_payment();
		if ( $allow_multi ) {
			$checkout_currency_args = $this->settings->get_checkout_currency_args();
			$current_currency       = $this->settings->get_current_currency();
			$checkout_currency      = $this->settings->get_checkout_currency();
			if ( $checkout_currency && ! in_array( $current_currency, $checkout_currency_args ) ) {
				$this->settings->set_current_currency( $checkout_currency, false );
				$this->send_ajax_failure_response();
			}
		}
	}

	/**
	 * If checkout failed during an AJAX call, send failure response.
	 */
	protected function send_ajax_failure_response() {
		if ( is_ajax() ) {
			// only print notices if not reloading the checkout, otherwise they're lost in the page reload
			if ( ! isset( WC()->session->reload_checkout ) ) {
				ob_start();
				wc_print_notices();
				$messages = ob_get_clean();
			}

			$response = array(
				'result'   => 'failure',
				'messages' => isset( $messages ) ? $messages : '',
				'refresh'  => isset( WC()->session->refresh_totals ),
				'reload'   => isset( WC()->session->reload_checkout ),
			);

			unset( WC()->session->refresh_totals, WC()->session->reload_checkout );

			wp_send_json( $response );
		}
	}

	public function change_currency_to_checkout() {
		$data           = new WOOMULTI_CURRENCY_Data();
		$payment_method = isset( $_POST['payment_method'] ) ? wc_clean( wp_unslash( $_POST['payment_method'] ) ) : '';
		$currency       = $data->get_param( 'currency_by_payment_method_' . $payment_method );
		if ( $currency ) {
			$data->set_current_currency( $currency, false );
			WC()->cart->calculate_totals();
		}
	}


	public function checkout_init() {
		if ( ! $this->settings->get_param( 'equivalent_currency' ) ) {
			return;
		}
		$current_currency   = $this->settings->get_current_currency();
		$this->old_currency = $this->settings->getcookie( 'wmc_current_currency_old' );

		if ( ! $this->old_currency || $this->old_currency == $current_currency ) {
			return;
		}

		$rate1 = $current_currency !== $this->old_currency ? wmc_get_price( 1, $current_currency ) : '';

		if ( $rate1 ) {
			$rate2 = wmc_get_price( 1, $this->old_currency );
			if ( $rate2 / $rate1 != 1 ) {
				$this->rate = $rate2 / $rate1;
			}
		}
	}


	public function previous_value_format( $value ) {
		return wc_price( $this->rate * $value, array( 'currency' => $this->old_currency ) );
	}

	public function previous_currency_item_subtotal( $html, $product, $quantity, $this_cart ) {
		if ( ! $this->old_currency || ! $this->rate ) {
			return $html;
		}

//		$fixed_price = $this->settings->check_fixed_price();
//
//		if ( $fixed_price ) {
//			echo '<pre>' . print_r( $product->get_price( 'edit' ), true ) . '</pre>';
//		}

		$price = $product->get_price();
		if ( $product->is_taxable() ) {
			if ( $this_cart->display_prices_including_tax() ) {
				$row_price = wc_get_price_including_tax( $product, array( 'qty' => $quantity ) );
			} else {
				$row_price = wc_get_price_excluding_tax( $product, array( 'qty' => $quantity ) );
			}
		} else {
			$row_price = $price * $quantity;
		}
		$prev_value = $this->previous_value_format( $row_price );
		$html       = sprintf( "<div class='wmc-custom-checkout-left'>%s</div><div class='wmc-custom-checkout-right'>(%s)</div>", $prev_value, $html );

		return $html;
	}

	public function previous_currency_order_total( $value ) {
		if ( $this->rate && $this->old_currency ) {
			$order_total = wc()->cart->get_total( 'edit' );
			$prev_value  = $this->previous_value_format( $order_total );
			$message     = esc_html__( 'You will be billed:', 'woocommerce-multi-currency' );
			$value       = sprintf( "<div class='wmc-custom-checkout-left'>%s</div><div class='wmc-custom-checkout-right'>(%s %s)</div>", $prev_value, $message, $value );
		}

		return $value;
	}

	public function previous_currency_taxes_total( $value ) {
		if ( $this->rate && $this->old_currency ) {
			$taxes_total = WC()->cart->get_taxes_total();
			$prev_value  = $this->previous_value_format( $taxes_total );
			$value       = sprintf( "<div class='wmc-custom-checkout-left'>%s</div><div class='wmc-custom-checkout-right'>(%s)</div>", $prev_value, $value );
		}

		return $value;
	}

	public function previous_currency_fee_html( $value, $fee ) {
		if ( $this->rate && $this->old_currency ) {
			$fee        = WC()->cart->display_prices_including_tax() ? $fee->total + $fee->tax : $fee->total;
			$prev_value = $this->previous_value_format( $fee );
			$value      = sprintf( "<div class='wmc-custom-checkout-left'>%s</div><div class='wmc-custom-checkout-right'>(%s)</div>", $prev_value, $value );
		}

		return $value;
	}

	public function previous_currency_cart_subtotal( $html, $compound, $this_cart ) {
		if ( $this->rate && $this->old_currency ) {
			if ( $compound ) {
				$prev_value = $this_cart->get_cart_contents_total() + $this_cart->get_shipping_total() + $this_cart->get_taxes_total( false, false );
			} elseif ( $this_cart->display_prices_including_tax() ) {
				$prev_value = $this_cart->get_subtotal() + $this_cart->get_subtotal_tax();
			} else {
				$prev_value = $this_cart->get_subtotal();
			}
			$prev_value = $this->previous_value_format( $prev_value );
			$html       = sprintf( "<div class='wmc-custom-checkout-left'>%s</div><div class='wmc-custom-checkout-right'>(%s)</div>", $prev_value, $html );
		}

		return $html;
	}

	public function previous_currency_coupon_html( $html, $coupon, $discount_amount_html ) {

		if ( $this->rate && $this->old_currency ) {
			if ( is_string( $coupon ) ) {
				$coupon = new WC_Coupon( $coupon );
			}

			$amount     = WC()->cart->get_coupon_discount_amount( $coupon->get_code(), WC()->cart->display_cart_ex_tax );
			$prev_value = $this->previous_value_format( $amount );
			$old_html   = str_replace( $discount_amount_html, '', $html );
			$html       = sprintf( "<div class='wmc-custom-checkout-left'>-%s %s</div><div class='wmc-custom-checkout-right'>(%s)</div>",
				$prev_value, $old_html, $discount_amount_html );
		}

		return $html;
	}

	public function previous_currency_shipping_label( $label, $method ) {
		if ( $this->rate && $this->old_currency ) {
			$new_label = $method->get_label();
			$has_cost  = 0 < $method->cost;
			$hide_cost = ! $has_cost && in_array( $method->get_method_id(), array( 'free_shipping', 'local_pickup' ), true );
			$old_label = '';
			if ( $has_cost && ! $hide_cost ) {
				$old_label = str_replace( $new_label, '', $label );
				$old_label = trim( str_replace( ':', '', $old_label ) );
				if ( WC()->cart->display_prices_including_tax() ) {
					$new_label .= ': ' . $this->previous_value_format( $method->cost + $method->get_shipping_tax() );
				} else {
					$new_label .= ': ' . $this->previous_value_format( $method->cost );
				}
			}
			$old_label = $old_label ? "({$old_label})" : '';

			$label = sprintf( "<div class='wmc-custom-checkout-left'>%s</div><div class='wmc-custom-checkout-right'>%s</div>",
				$new_label, $old_label );
		}

		return $label;
	}

	public function payment_args( $payment_args, $order ) {
		if ( isset( $_GET['pay_for_order'] ) && $_GET['pay_for_order'] ) {
			$payment_args['currency_code'] = $order->get_currency();
		}

		return $payment_args;
	}
}
