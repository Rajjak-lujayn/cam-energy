<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class WOOMULTI_CURRENCY_Frontend_Price
 */
class WOOMULTI_CURRENCY_Frontend_Price {
	protected $settings;
	protected $price;
	protected $change;
	protected $decimal_data = '';
	public $prepare_param;
	public $show_approximate_price;

	public function __construct() {
		//		$this->settings = new WOOMULTI_CURRENCY_Data();
		$this->settings = WOOMULTI_CURRENCY_Data::get_ins();
		$this->change   = false;

		//Fix for FB WC feed data
		if ( isset( $_GET['wc-api'] ) && $_GET['wc-api'] == 'wc_facebook_get_feed_data' ) {
			return;
		}

		if ( $this->settings->get_enable() ) {
			/*Simple product*/
			add_filter( 'woocommerce_product_get_regular_price', array( $this, 'woocommerce_product_get_regular_price' ), 99, 2 );
			add_filter( 'woocommerce_product_get_sale_price', array( $this, 'woocommerce_product_get_sale_price' ), 99, 2 );
			add_filter( 'woocommerce_product_get_price', array( $this, 'woocommerce_product_get_price' ), 99, 2 );

			/*Variable price*/
			add_filter( 'woocommerce_product_variation_get_price', array( $this, 'woocommerce_product_variation_get_price' ), 99, 2 );
			add_filter( 'woocommerce_product_variation_get_regular_price', array( $this, 'woocommerce_product_variation_get_regular_price' ), 99, 2 );
			add_filter( 'woocommerce_product_variation_get_sale_price', array( $this, 'woocommerce_product_variation_get_sale_price' ), 99, 2 );

			/*Variable Parent min max price*/
			add_filter( 'woocommerce_variation_prices', array( $this, 'get_woocommerce_variation_prices' ), 99, 3 );

			/*Pay with Multi Currencies*/
			add_action( 'init', array( $this, 'init' ), 99 );


			/*Approximately*/
			add_action( 'init', array( $this, 'prepare_params' ), 999 );
			add_filter( 'woocommerce_get_price_html', array( $this, 'add_approximately_price' ), 20, 2 );
			add_filter( 'woocommerce_cart_product_price', array( $this, 'cart_product_approximately_price' ), 20, 2 );
			add_filter( 'woocommerce_cart_totals_order_total_html', array( $this, 'cart_total_approximately_price' ), 20 );
			add_filter( 'woocommerce_cart_subtotal', array( $this, 'cart_subtotal_approximately_price' ), 20, 3 );
			add_filter( 'woocommerce_cart_product_subtotal', array( $this, 'cart_product_subtotal_approximately_price' ), 20, 4 );
			add_filter( 'woocommerce_cart_shipping_method_full_label', array( $this, 'shipping_approximately_price' ), 20, 2 );

			if ( $this->settings->get_price_switcher() ) {
				add_action( 'woocommerce_single_product_summary', array( $this, 'add_price_switcher' ), 20, 2 );
			}

			add_filter( 'wmc_get_price', array( $this, 'format_beauty_price' ), 99, 2 );

			add_filter( 'wmc_set_decimals', array( $this, 'override_decimal' ), 999 );

			if ( $this->settings->get_param( 'cache_compatible' ) ) {
				add_filter( 'woocommerce_get_price_html', array( $this, 'compatible_cache_plugin' ), 20, 2 );
			}

		}
	}

	/**
	 *
	 */
	public function add_price_switcher() {

		if ( is_admin() || ! is_single() ) {
			return;
		}

		global $post;
		if ( is_object( $post ) && $post->ID && $post->post_type == 'product' && $post->post_status == 'publish' ) {
			$product          = wc_get_product( $post->ID );
			$links            = $this->settings->get_links();
			$current_currency = $this->settings->get_current_currency();
			$country          = $this->settings->get_country_data( $current_currency );
			$list_currencies  = $this->settings->get_list_currencies();
			$class            = array( 'wmc-price-switcher' );
			wp_enqueue_style( 'wmc-flags', WOOMULTI_CURRENCY_CSS . 'flags-64.min.css' );

			?>
            <div class="woocommerce-multi-currency <?php echo implode( ' ', $class ) ?>"
                 title="<?php esc_attr_e( 'Please select your currency', 'woocommerce-multi-currency' ) ?>">
                <div class="wmc-currency-wrapper">
                        <span class="wmc-current-currency">
                          <i style="transform: scale(0.8);" alt="<?php echo esc_attr( $country['name'] ) ?>"
                             class="vi-flag-64 flag-<?php echo strtolower( $country['code'] ) ?> "></i>
                        </span>
                    <div class="wmc-sub-currency">
						<?php
						foreach ( $links as $k => $link ) {

							/*End override*/
							$country = $this->settings->get_country_data( $k );

							?>
                            <div class="wmc-currency" data-currency="<?php echo $k ?>">
                                <a rel="nofollow" title="<?php echo esc_attr( $country['name'] ) ?>"
                                   href="<?php echo esc_url( $link ) ?>"
                                   class="wmc-currency-redirect" data-currency="<?php echo $k ?>">
                                    <i style="transform: scale(0.8);" alt="<?php echo esc_attr( $country['name'] ) ?>"
                                       class="vi-flag-64 flag-<?php echo strtolower( $country['code'] ) ?> "></i>
									<?php switch ( $this->settings->get_price_switcher() ) {
										case 2:
											echo '<span class="wmc-price-switcher-code">' . $k . '</span>';
											break;
										case 3:
											$decimals           = (int) $list_currencies[ $k ]['decimals'];
											$decimal_separator  = wc_get_price_decimal_separator();
											$thousand_separator = wc_get_price_thousand_separator();
											$pos                = $list_currencies[ $k ]['pos'];
											$symbol             = $list_currencies[ $k ]['custom'];
											$symbol             = $symbol ? $symbol : get_woocommerce_currency_symbol( $k );
											switch ( $pos ) {
												case 'left' :
													$format = '%1$s%2$s';
													break;
												case 'right' :
													$format = '%2$s%1$s';
													break;
												case 'left_space' :
													$format = '%1$s&nbsp;%2$s';
													break;
												case 'right_space' :
													$format = '%2$s&nbsp;%1$s';
													break;
											}

											$price = 0;
											if ( $this->settings->check_fixed_price() ) {

												$product_id    = $product->get_id();
												$product_price = wmc_adjust_fixed_price( json_decode( get_post_meta( $product_id, '_regular_price_wmcp', true ), true ) );
												$sale_price    = wmc_adjust_fixed_price( json_decode( get_post_meta( $product_id, '_sale_price_wmcp', true ), true ) );
												if ( isset( $product_price[ $k ] ) && ! $product->is_on_sale( 'edit' ) && $product_price[ $k ] > 0 ) {
													$price = $product_price[ $k ];
												} elseif ( isset( $sale_price[ $k ] ) && $sale_price[ $k ] > 0 ) {
													$price = $sale_price[ $k ];
												}
											}
											if ( ! $price && $product->get_price( 'edit' ) ) {
												$price = $product->get_price( 'edit' );
												$price = number_format( wmc_get_price( wc_get_price_to_display( $product, array(
													'qty'   => 1,
													'price' => $price
												) ), $k ), $decimals, $decimal_separator, $thousand_separator );
											} else {
												$price = number_format( wc_get_price_to_display( $product, array(
													'qty'   => 1,
													'price' => $price
												) ), $decimals, $decimal_separator, $thousand_separator );
											}

											$pos = strpos( $symbol, '#PRICE#' );
											if ( $pos === false ) {
												$formatted_price = sprintf( $format, $symbol, $price );
											} else {
												$formatted_price = str_replace( '#PRICE#', $price, $symbol );
											}
											$max_price = '';
											if ( $product->get_type() == 'variable' ) {
												$price_max = $this->get_variation_max_price( $product, $k );
												if ( $price_max != wmc_get_price( $product->get_price( 'edit' ), $k ) ) {
													$price_max = number_format( wc_get_price_to_display( $product, array(
														'qty'   => 1,
														'price' => $price_max
													) ), $decimals, $decimal_separator, $thousand_separator );
													if ( $pos === false ) {
														$max_price = ' - ' . sprintf( $format, $symbol, $price_max );
													} else {
														$max_price = ' - ' . str_replace( '#PRICE#', $price_max, $symbol );
													}
												}
											}
											echo '<span class="wmc-price-switcher-price">' . $formatted_price . $max_price . '</span>';
									} ?>
                                </a>
                            </div>
						<?php } ?>
                    </div>
                </div>
            </div>
			<?php
		}
	}

	/**
	 * Get Product variation price
	 *
	 * @param $product
	 *
	 * @return int|string
	 */
	protected function get_variation_max_price( $product, $currency_code = false, $raw = false ) {
		$variation_ids = $product->get_visible_children();
		$price_max     = 0;
		foreach ( $variation_ids as $variation_id ) {
			$variation = wc_get_product( $variation_id );

			if ( $variation ) {
				$price = 0;
				if ( ! $currency_code ) {
					$currenct_currency = $this->settings->get_current_currency();
				} elseif ( ! $raw ) {
					$currenct_currency = $currency_code;
				}
				if ( $this->settings->check_fixed_price() && ! $raw ) {
					$product_id    = $variation_id;
					$product_price = wmc_adjust_fixed_price( json_decode( get_post_meta( $product_id, '_regular_price_wmcp', true ), true ) );
					$sale_price    = wmc_adjust_fixed_price( json_decode( get_post_meta( $product_id, '_sale_price_wmcp', true ), true ) );
					if ( isset( $product_price[ $currenct_currency ] ) && ! $product->is_on_sale( 'edit' ) && $product_price[ $currenct_currency ] > 0 ) {
						$price = $product_price[ $currenct_currency ];
					} elseif ( isset( $sale_price[ $currenct_currency ] ) && $sale_price[ $currenct_currency ] > 0 ) {
						$price = $sale_price[ $currenct_currency ];
					}
				}

				if ( ! $price ) {
					$price = $variation->get_price( 'edit' );
					if ( ! $raw ) {
						$price = wmc_get_price( $price, $currency_code );
					}
				}
				if ( $price > $price_max ) {
					$price_max = $price;
				}
			}
		}


		return $price_max;
	}

	public function prepare_params() {
		if ( $this->settings->get_auto_detect() != 2 ) {
			return;
		}
		$this->show_approximate_price = true;

		if ( ! $this->settings->getcookie( 'wmc_currency_rate' ) || ! $this->settings->getcookie( 'wmc_currency_symbol' ) || ! $this->settings->getcookie( 'wmc_ip_info' ) ) {
			return;
		}
		$geoplugin_arg        = json_decode( base64_decode( $this->settings->getcookie( 'wmc_ip_info' ) ), true );
		$detect_currency_code = isset( $geoplugin_arg['currency_code'] ) ? $geoplugin_arg['currency_code'] : '';
		if ( $detect_currency_code == $this->settings->get_current_currency() ) {
			return;
		}
		$list_currencies  = $this->settings->get_list_currencies();
		$default_currency = $this->settings->get_default_currency();

		if ( $detect_currency_code && isset( $list_currencies[ $detect_currency_code ] ) ) {
			$decimals    = (int) $list_currencies[ $detect_currency_code ]['decimals'];
			$current_pos = $list_currencies[ $detect_currency_code ]['pos'];
		} else {
			$decimals    = (int) $list_currencies[ $default_currency ]['decimals'];
			$current_pos = $list_currencies[ $default_currency ]['pos'];
		}
		$format = '';
		switch ( $current_pos ) {
			case 'left' :
				$format = '%1$s%2$s';
				break;
			case 'right' :
				$format = '%2$s%1$s';
				break;
			case 'left_space' :
				$format = '%1$s&nbsp;%2$s';
				break;
			case 'right_space' :
				$format = '%2$s&nbsp;%1$s';
				break;
		}

		$this->prepare_param = array(
			'rate'               => $this->settings->getcookie( 'wmc_currency_rate' ),
			'symbol'             => $this->settings->getcookie( 'wmc_currency_symbol' ),
			'format'             => $format,
			'decimals'           => $decimals,
			'decimal_separator'  => wc_get_price_decimal_separator(),
			'thousand_separator' => wc_get_price_thousand_separator(),
			'priority'           => $this->settings->get_param( 'approximately_priority' )
		);
	}

	/**
	 * @param $html_price
	 * @param $product WC_Product
	 *
	 * @return string
	 */
	public function add_approximately_price( $html_price, $product ) {
		if ( is_admin() ) {
			return $html_price;
		}
		if ( $this->settings->get_auto_detect() == 2 ) {

			if ( '' === $product->get_price() || ! $product->is_in_stock() || empty( $this->prepare_param ) ) {
				return $html_price;
			}

			$params          = $this->prepare_param;
			$raw_price       = wc_get_price_to_display( $product, array( 'qty' => 1, 'price' => $product->get_price( 'edit' ) ) );
			$price           = number_format( $raw_price * $params['rate'], (int) $params['decimals'], $params['decimal_separator'], $params['thousand_separator'] );
			$pos             = strpos( $params['symbol'], '#PRICE#' );
			$formatted_price = $pos ? str_replace( '#PRICE#', $price, $params['symbol'] ) : sprintf( $params['format'], $params['symbol'], $price );

			$max_price = '';
			if ( $product->get_type() == 'variable' ) {
				$price_max = $this->get_variation_max_price( $product, false, true );
				if ( $price_max != $product->get_price( 'edit' ) ) {
					$raw_price = wc_get_price_to_display( $product, array( 'qty' => 1, 'price' => $price_max ) );
					$price_max = number_format( $raw_price * $params['rate'], (int) $params['decimals'], $params['decimal_separator'], $params['thousand_separator'] );
					$max_price = $pos ? str_replace( '#PRICE#', $price_max, $params['symbol'] ) : sprintf( $params['format'], $params['symbol'], $price_max );
					$max_price = ' - ' . $max_price;
				}
			}

			$appro_label = $this->settings->get_param( 'approximately_label' );
			$pos         = strpos( $appro_label, '{price}' );
			$appro_price = $pos ? str_replace( '{price}', $formatted_price . $max_price, $appro_label ) : $appro_label . $formatted_price . $max_price;

			$html_price = $params['priority'] ? '<span class="wmc-approximately">' . $appro_price . '</span>' . $html_price : $html_price . '<span class="wmc-approximately">' . $appro_price . '</span>';

		}

		return $html_price;
	}

	public function cart_total_approximately_price( $html ) {
		if ( ! $this->show_approximate_price || ! in_array( 'total', (array) $this->settings->get_param( 'approximate_position' ) ) ) {
			return $html;
		}

		$total_price = wc()->cart->get_total( 'edit' );
		$appro_price = $this->get_approximately_price( $total_price );

		return $this->prepare_param['priority'] ? $appro_price . $html : $html . $appro_price;
	}


	public function cart_subtotal_approximately_price( $cart_subtotal, $compound, $cart ) {
		if ( ! $this->show_approximate_price || ! in_array( 'subtotal', (array) $this->settings->get_param( 'approximate_position' ) ) ) {
			return $cart_subtotal;
		}

		if ( $compound ) {
			$subtotal_price = $cart->get_cart_contents_total() + $cart->get_shipping_total() + $cart->get_taxes_total( false, false );
		} elseif ( $cart->display_prices_including_tax() ) {
			$subtotal_price = $cart->get_subtotal() + $cart->get_subtotal_tax();
		} else {
			$subtotal_price = $cart->get_subtotal();
		}

		$appro_price = $this->get_approximately_price( $subtotal_price );

		return $this->prepare_param['priority'] ? $appro_price . $cart_subtotal : $cart_subtotal . $appro_price;
	}

	public function cart_product_approximately_price( $product_price, $product ) {
		if ( ! $this->show_approximate_price || ! in_array( 'product', (array) $this->settings->get_param( 'approximate_position' ) ) ) {
			return $product_price;
		}
		if ( wc()->cart->display_prices_including_tax() ) {
			$item_price = wc_get_price_including_tax( $product );
		} else {
			$item_price = wc_get_price_excluding_tax( $product );
		}
		$appro_price = $this->get_approximately_price( $item_price );

		return $this->prepare_param['priority'] ? $appro_price . $product_price : $product_price . $appro_price;
	}

	public function cart_product_subtotal_approximately_price( $product_subtotal, $product, $quantity, $cart ) {
		if ( ! $this->show_approximate_price || ! in_array( 'product_subtotal', (array) $this->settings->get_param( 'approximate_position' ) ) ) {
			return $product_subtotal;
		}
		$price = $product->get_price();

		if ( $product->is_taxable() ) {
			if ( $cart->display_prices_including_tax() ) {
				$row_price = wc_get_price_including_tax( $product, array( 'qty' => $quantity ) );
			} else {
				$row_price = wc_get_price_excluding_tax( $product, array( 'qty' => $quantity ) );
			}
		} else {
			$row_price = $price * $quantity;
		}

		$appro_price = $this->get_approximately_price( $row_price );

		return $this->prepare_param['priority'] ? $appro_price . $product_subtotal : $product_subtotal . $appro_price;
	}

	public function shipping_approximately_price( $label, $method ) {
		if ( ! $this->show_approximate_price || ! in_array( 'shipping', (array) $this->settings->get_param( 'approximate_position' ) ) ) {
			return $label;
		}
		$has_cost  = 0 < $method->cost;
		$hide_cost = ! $has_cost && in_array( $method->get_method_id(), array( 'free_shipping', 'local_pickup' ), true );
		$raw_price = '';
		if ( $has_cost && ! $hide_cost ) {
			if ( WC()->cart->display_prices_including_tax() ) {
				$raw_price = $method->cost + $method->get_shipping_tax();
			} else {
				$raw_price = $method->cost;
			}
		}
		$appro_price = $this->get_approximately_price( $raw_price );

		return $this->prepare_param['priority'] ? $appro_price . $label : $label . $appro_price;
//		return $label . $appro_price;
	}

	public function get_approximately_price( $raw_price ) {
		if ( empty( $this->prepare_param ) || ! $raw_price ) {
			return '';
		}

		$params = $this->prepare_param;
		$price  = number_format( $raw_price * $params['rate'], (int) $params['decimals'], $params['decimal_separator'], $params['thousand_separator'] );

		$pos             = strpos( $params['symbol'], '#PRICE#' );
		$formatted_price = $pos ? str_replace( '#PRICE#', $price, $params['symbol'] ) : sprintf( $params['format'], $params['symbol'], $price );

		$appro_label = $this->settings->get_param( 'approximately_label' );
		$pos         = strpos( $appro_label, '{price}' );
		$appro_price = $pos ? str_replace( '{price}', $formatted_price, $appro_label ) : $appro_label . $formatted_price;

		$html_price = '<span class="wmc-approximately">' . $appro_price . '</span>';

		return $html_price;
	}

	/**
	 * Check on checkout page
	 */

	public function init() {

		if ( is_admin() && ! is_ajax() ) {
			return;
		}
		/*Fix UX Builder of Flatsome*/
		if ( isset( $_GET['uxb_iframe'] ) ) {
			return;
		}

		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			return;
		}

		$page        = $this->get_post_from_url();
		$is_cart     = $this->is_cart( $page );
		$is_checkout = $this->is_checkout( $page );

		if ( ! ( is_ajax() && isset( $_POST['action'] ) && $_POST['action'] == 'wmc_get_products_price' || ! is_ajax() ) ) {
			return;
		}

		$settings    = WOOMULTI_CURRENCY_Data::get_ins();
		$allow_multi = $settings->get_enable_multi_payment();

		$current_currency       = $settings->get_current_currency();
		$checkout_currency      = $settings->get_checkout_currency();
		$checkout_currency_args = $settings->get_checkout_currency_args();
		$old_currency           = $settings->getcookie( 'wmc_current_currency_old' );

		if ( in_array( $current_currency, $checkout_currency_args ) ) {
			$checkout_currency = $current_currency;
		}

		/*Checkout && Cartpage*/
		if ( ! $allow_multi ) {
			if ( $is_checkout ) {
				$settings->set_current_currency( $settings->get_default_currency(), false );
			} elseif ( $settings->enable_cart_page() && $is_cart ) {
				$settings->set_current_currency( $checkout_currency, false );
			} elseif ( $old_currency && $old_currency != $current_currency ) {
				$settings->set_current_currency( $old_currency, false );
			}
		} else {
			if ( ! isset( $_REQUEST['custom-page'] ) ) { //check ! isset( $_REQUEST['custom-page'] ) to fix looping checkout error with theme Handmade
				if ( $allow_multi && $is_checkout ) { // is checkout page
					$settings->set_current_currency( $checkout_currency, false );
				} elseif ( $settings->enable_cart_page() && $is_cart ) {
					$settings->set_current_currency( $checkout_currency, false );
				} elseif ( $old_currency && $old_currency != $current_currency ) {
					$settings->set_current_currency( $old_currency, false );
				}
			}
		}
	}

	public function get_post_from_url() {
		global $wp;
		$current_url = site_url( add_query_arg( array(), $wp->request ) );

		$redirect_url = isset( $_SERVER['REDIRECT_URI'] ) ? $_SERVER['REDIRECT_URI'] : '';
		$redirect_url = ! empty( $_SERVER['REDIRECT_URL'] ) ? $_SERVER['REDIRECT_URL'] : $redirect_url;
		$redirect_url = ! empty( $_SERVER['REQUEST_URI'] ) ? $_SERVER['REQUEST_URI'] : $redirect_url;
		$current_url  = $current_url . $redirect_url;

		$id = url_to_postid( $current_url );

		$post = get_post( $id );

		return $post;
	}

	public function has_shortcode( $post, $tag ) {
		return is_a( $post, 'WP_Post' ) && has_shortcode( $post->post_content, $tag );
	}

	public function is_cart( $post ) {
		if ( ! $post ) {
			return false;
		}

		return $this->has_shortcode( $post, 'woocommerce_cart' );
	}

	public function is_checkout( $post ) {
		if ( ! $post ) {
			return false;
		}

		return $this->has_shortcode( $post, 'woocommerce_checkout' );
	}

	/**
	 * Variable Parent min max price
	 *
	 * @param $price_arr
	 *
	 * @return array
	 */
	public function get_woocommerce_variation_prices( $price_arr, $product, $for_display ) {
		$temp_arr = $price_arr;
		if ( is_array( $price_arr ) && ! empty( $price_arr ) ) {
			$fixed_price = $this->settings->check_fixed_price();
			foreach ( $price_arr as $price_type => $values ) {
				foreach ( $values as $key => $value ) {
					if ( $fixed_price ) {
						$current_currency = $this->settings->get_current_currency();
						if ( $temp_arr['regular_price'][ $key ] != $temp_arr['price'][ $key ] ) {
							if ( $price_type == 'regular_price' ) {
								$regular_price_wmcp = wmc_adjust_fixed_price( json_decode( get_post_meta( $key, '_regular_price_wmcp', true ), true ) );

								if ( isset( $regular_price_wmcp[ $current_currency ] ) && $regular_price_wmcp[ $current_currency ] > 0 ) {
									$price_arr[ $price_type ][ $key ] = $for_display ? $this->tax_handle( $regular_price_wmcp[ $current_currency ], $product ) : $regular_price_wmcp[ $current_currency ];
								} else {
									$price_arr[ $price_type ][ $key ] = wmc_get_price( $value );
								}
							}

							if ( $price_type == 'price' || $price_type == 'sale_price' ) {
								$sale_price_wmcp = wmc_adjust_fixed_price( json_decode( get_post_meta( $key, '_sale_price_wmcp', true ), true ) );

								if ( isset( $sale_price_wmcp[ $current_currency ] ) && $sale_price_wmcp[ $current_currency ] > 0 ) {
									$price_arr[ $price_type ][ $key ] = $for_display ? $this->tax_handle( $sale_price_wmcp[ $current_currency ], $product ) : $sale_price_wmcp[ $current_currency ];
								} elseif ( $temp_arr['regular_price'][ $key ] != $temp_arr['price'][ $key ] ) {
									$price_arr[ $price_type ][ $key ] = wmc_get_price( $value );
								} else {
									$price_arr[ $price_type ][ $key ] = wmc_get_price( $value );
								}
							}
						} else {
							$regular_price_wmcp = wmc_adjust_fixed_price( json_decode( get_post_meta( $key, '_regular_price_wmcp', true ), true ) );
							if ( isset( $regular_price_wmcp[ $current_currency ] ) && $regular_price_wmcp[ $current_currency ] > 0 ) {
								$price_arr[ $price_type ][ $key ] = $for_display ? $this->tax_handle( $regular_price_wmcp[ $current_currency ], $product ) : $regular_price_wmcp[ $current_currency ];
							} else {
								$price_arr[ $price_type ][ $key ] = wmc_get_price( $value );
							}
						}

					} else {
						$price_arr[ $price_type ][ $key ] = wmc_get_price( $value );
					}
				}
			}
		}

		return $price_arr;
	}

	public function tax_handle( $price, $product ) {
		if ( ! $price ) {
			return $price;
		}

		$data = array( 'qty' => 1, 'price' => $price, );

		return 'incl' === get_option( 'woocommerce_tax_display_shop' ) ? wc_get_price_including_tax( $product, $data ) : wc_get_price_excluding_tax( $product, $data );
	}

	/**
	 * @param $price
	 * @param $product WC_Product
	 *
	 * @return mixed
	 */
	public function woocommerce_product_variation_get_sale_price( $price, $product ) {
		if ( ! $price ) {
			return $price;
		}
		$product_id = $product->get_id();
		if ( ! is_ajax() && isset( $this->price[ $product_id ][ $price ] ) ) {
			return $this->price[ $product_id ][ $price ];
		}
		$changes = $product->get_changes();

		if ( $this->settings->check_fixed_price() && ( is_array( $changes ) ) && count( $changes ) < 1 ) {

			$currenct_currency = $this->settings->get_current_currency();
			$product_id        = $product->get_id();
			$product_price     = wmc_adjust_fixed_price( json_decode( get_post_meta( $product_id, '_sale_price_wmcp', true ), true ) );
			if ( isset( $product_price[ $currenct_currency ] ) ) {
				if ( $product_price[ $currenct_currency ] > 0 ) {
					return $this->set_cache( $product_price[ $currenct_currency ], $product_id, $price );
				}
			}
		}

		return $this->set_cache( wmc_get_price( $price ), $product_id, $price );
		//Do nothing to remove prices hash to alway get live price.
	}

	/**
	 * Set price to global. It will help more speedy.
	 *
	 * @param $price
	 * @param $id
	 *
	 * @return mixed
	 */
	protected function set_cache( $price, $id, $key ) {
		if ( $price && $id && $key ) {
			/*Default decimal is "."*/
			$this->price[ $id ][ $key ] = str_replace( ',', '.', $price );

			return $this->price[ $id ][ $key ];
		} else {
			return $price;
		}
	}

	/**
	 * @param $price
	 * @param $product WC_Product
	 *
	 * @return mixed
	 */
	public function woocommerce_product_variation_get_regular_price( $price, $product ) {
		if ( ! $price ) {
			return $price;
		}
		$product_id = $product->get_id();
		if ( ! is_ajax() && isset( $this->price[ $product_id ][ $price ] ) ) {
			return $this->price[ $product_id ][ $price ];
		}
		$changes = $product->get_changes();

		if ( $this->settings->check_fixed_price() && ( is_array( $changes ) ) && count( $changes ) < 1 ) {

			$currenct_currency = $this->settings->get_current_currency();
			$product_id        = $product->get_id();
			$product_price     = wmc_adjust_fixed_price( json_decode( get_post_meta( $product_id, '_regular_price_wmcp', true ), true ) );
			if ( isset( $product_price[ $currenct_currency ] ) ) {
				if ( $product_price[ $currenct_currency ] > 0 ) {
					return $this->set_cache( $product_price[ $currenct_currency ], $product_id, $price );

				}
			}
		}

		return $this->set_cache( wmc_get_price( $price ), $product_id, $price );
		//Do nothing to remove prices hash to alway get live price.
	}

	/**
	 * @param $price
	 * @param $product WC_Product
	 *
	 * @return mixed
	 */
	public function woocommerce_product_variation_get_price( $price, $product ) {
		if ( ! $price ) {
			return $price;
		}
		$product_id = $product->get_id();

		if ( ! is_ajax() && isset( $this->price[ $product_id ][ $price ] ) ) {
			return $this->price[ $product_id ][ $price ];
		}

		$changes = $product->get_changes();

		if ( $this->settings->check_fixed_price() && ( is_array( $changes ) ) && count( $changes ) < 1 ) {

			$currenct_currency = $this->settings->get_current_currency();
			$product_id        = $product->get_id();
			$product_price     = wmc_adjust_fixed_price( json_decode( get_post_meta( $product_id, '_regular_price_wmcp', true ), true ) );
			$sale_price        = wmc_adjust_fixed_price( json_decode( get_post_meta( $product_id, '_sale_price_wmcp', true ), true ) );

			if ( isset( $product_price[ $currenct_currency ] ) && ! $product->is_on_sale( 'edit' ) ) {
				if ( $product_price[ $currenct_currency ] > 0 ) {
					return $this->set_cache( $product_price[ $currenct_currency ], $product_id, $price );
				}
			} elseif ( isset( $sale_price[ $currenct_currency ] ) ) {
				if ( $sale_price[ $currenct_currency ] > 0 ) {
					return $this->set_cache( $sale_price[ $currenct_currency ], $product_id, $price );

				}
			}
		}

		return $this->set_cache( wmc_get_price( $price ), $product_id, $price );
	}

	/**
	 * @param $price
	 * @param $product WC_Product
	 *
	 * @return mixed
	 */
	public function woocommerce_product_get_price( $price, $product ) {
		$condition = apply_filters( 'wmc_product_get_price_condition', true, $price, $product );
		if ( ! $price || ! $condition ) {
			return $price;
		}

		$product_id = $product->get_id();

		if ( ! is_ajax() && isset( $this->price[ $product_id ][ $price ] ) ) {
			return $this->price[ $product_id ][ $price ];
		}

		$changes = $product->get_changes();
		if ( $this->settings->check_fixed_price() && ( is_array( $changes ) ) && count( $changes ) < 1 ) {
			$currenct_currency = $this->settings->get_current_currency();
			$product_id        = $product->get_id();
			$product_price     = wmc_adjust_fixed_price( json_decode( get_post_meta( $product_id, '_regular_price_wmcp', true ), true ) );
			$sale_price        = wmc_adjust_fixed_price( json_decode( get_post_meta( $product_id, '_sale_price_wmcp', true ), true ) );

			if ( isset( $product_price[ $currenct_currency ] ) && ! $product->is_on_sale( 'edit' ) ) {
				if ( $product_price[ $currenct_currency ] > 0 ) {
					return $this->set_cache( $product_price[ $currenct_currency ], $product_id, $price );
				}
			} elseif ( isset( $sale_price[ $currenct_currency ] ) ) {
				if ( $sale_price[ $currenct_currency ] > 0 ) {
					return $this->set_cache( $sale_price[ $currenct_currency ], $product_id, $price );

				}
			}
		}

		$new_price = apply_filters( 'wmc_wc_product_get_price', wmc_get_price( $price ) );

		return $this->set_cache( $new_price, $product_id, $price );
	}

	/**
	 * @param $price
	 * @param $product WC_Product
	 *
	 * @return mixed
	 */
	public function woocommerce_product_get_sale_price( $price, $product ) {
		if ( ! $price ) {
			return $price;
		}
		$product_id = $product->get_id();
		if ( ! is_ajax() && isset( $this->price[ $product_id ][ $price ] ) ) {
			return $this->price[ $product_id ][ $price ];
		}
		$changes = $product->get_changes();

		if ( $this->settings->check_fixed_price() && ( is_array( $changes ) ) && count( $changes ) < 1 ) {

			$currenct_currency = $this->settings->get_current_currency();
			$product_id        = $product->get_id();
			$product_price     = wmc_adjust_fixed_price( json_decode( get_post_meta( $product_id, '_sale_price_wmcp', true ), true ) );
			if ( isset( $product_price[ $currenct_currency ] ) ) {
				if ( $product_price[ $currenct_currency ] > 0 ) {
					return $this->set_cache( $product_price[ $currenct_currency ], $product_id, $price );

				}
			}
		}

		return $this->set_cache( wmc_get_price( $price ), $product_id, $price );
	}

	/**
	 * @param $price
	 * @param $product WC_Product
	 *
	 * @return mixed
	 */
	public function woocommerce_product_get_regular_price( $price, $product ) {
		if ( ! $price ) {
			return $price;
		}
		$product_id = $product->get_id();
		if ( ! is_ajax() && isset( $this->price[ $product_id ][ $price ] ) ) {
			return $this->price[ $product_id ][ $price ];
		}
		$changes = $product->get_changes();

		if ( $this->settings->check_fixed_price() && ( is_array( $changes ) ) && count( $changes ) < 1 ) {

			$currenct_currency = $this->settings->get_current_currency();
			$product_id        = $product->get_id();
			$product_price     = wmc_adjust_fixed_price( json_decode( get_post_meta( $product_id, '_regular_price_wmcp', true ), true ) );
			if ( isset( $product_price[ $currenct_currency ] ) ) {
				if ( $product_price[ $currenct_currency ] > 0 ) {
					return $this->set_cache( $product_price[ $currenct_currency ], $product_id, $price );
				}
			}
		}

		return $this->set_cache( wmc_get_price( $price ), $product_id, $price );
	}


	public function format_beauty_price( $price, $current_currency ) {

		$enable                = $this->settings->get_param( 'beauty_price_enable' );
		$input_price           = wc_prices_include_tax();
		$shop_display_incl_tax = 'incl' === get_option( 'woocommerce_tax_display_shop' ) ? true : false;
		$cart_display_incl_tax = 'incl' === get_option( 'woocommerce_tax_display_cart' ) ? true : false;

		$cond_1 = $input_price && $shop_display_incl_tax && $cart_display_incl_tax;
		$cond_2 = ! $input_price && ! $shop_display_incl_tax && ! $cart_display_incl_tax;

		if ( $enable && ( $cond_1 || $cond_2 || ! wc_tax_enabled() ) ) {

			$int         = $new_int = (int) $price;
			$fraction    = $new_fraction = $price - $int;
			$lower_bound = $this->settings->get_param( 'price_lower_bound' );

			$from       = $this->settings->get_param( 'beauty_price_from' );
			$to         = $this->settings->get_param( 'beauty_price_to' );
			$value      = $this->settings->get_param( 'beauty_price_value' );
			$currencies = $this->settings->get_param( 'beauty_price_currencies' );
			$int_frac   = $this->settings->get_param( 'beauty_price_part' );

			$count_from       = is_array( $from ) ? count( $from ) : '';
			$count_to         = is_array( $to ) ? count( $to ) : '';
			$count_value      = is_array( $value ) ? count( $value ) : '';
			$count_currencies = is_array( $currencies ) ? count( $currencies ) : '';
			$count_int_frac   = is_array( $int_frac ) ? count( $int_frac ) : '';

			if ( $count_from && $count_to && $count_value && $count_currencies && $count_int_frac ) {
				$count = min( $count_from, $count_to, $count_value, $count_currencies, $count_int_frac );

				for ( $i = 0; $i < $count; $i ++ ) {

					if ( empty( $currencies[ $i ] ) || ! in_array( $current_currency, $currencies[ $i ] ) ) {
						continue;
					}

					switch ( $int_frac[ $i ] ) {

						case 'integer':
							$offset = strlen( $value[ $i ] );
							if ( $offset == 0 ) {
								break;
							}
							$price_length = strlen( $int );
							if ( $price_length > $offset ) {
								$first_part_price  = substr( $int, 0, - $offset );
								$second_part_price = substr( $int, - $offset );

								if ( $lower_bound ) {
									if ( (int) $second_part_price >= (int) $from[ $i ] && (int) $second_part_price < (int) $to[ $i ] ) {
										$new_int = $first_part_price . $value[ $i ];
									}
								} else {
									if ( (int) $second_part_price > (int) $from[ $i ] && (int) $second_part_price < (int) $to[ $i ] ) {
										$new_int = $first_part_price . $value[ $i ];
									}
								}
							}
							break;

						case 'fraction':
							$f_from  = (float) $from[ $i ];
							$f_to    = (float) $to[ $i ];
							$f_value = $value[ $i ];
							$offset  = 0;

							if ( $f_from > 1 || $f_to > 1 ) {
								break;
							}

							switch ( true ) {
								case $f_value == '':
									$offset = 0;
									break;
								case $f_value >= 1:
									$offset = strlen( $value[ $i ] );
									break;
								case $f_value < 1:
									$string = str_replace( ',', '.', $f_value );
									$string = substr( $string, strpos( $string, '.' ) + 1 );
									$offset = strlen( $string );
									break;
							}

							$this->decimal_data = $offset;

							$f_value = (float) $f_value;

							if ( $lower_bound ) {
								if ( (float) $fraction >= $f_from && (float) $fraction < $f_to ) {
									$new_fraction = $f_value;
								}
							} else {
								if ( (float) $fraction > $f_from && (float) $fraction < $f_to ) {
									$new_fraction = $f_value;
								}
							}

							break;
					}
				}
				$price = (int) $new_int + (float) $new_fraction;
			}
		}

		return (float) $price;
	}


	public function override_decimal( $decimal ) {

		if ( $this->decimal_data !== '' ) {
			return $this->decimal_data;
		}

		return $decimal;
	}

	public function compatible_cache_plugin( $price, $product ) {
		return "<span class='wmc-cache-pid' data-wmc_product_id='{$product->get_id()}'>" . $price . '</span>';
	}

}