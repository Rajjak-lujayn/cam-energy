<?php //phpcs:ignore WordPress.Files.FileName.InvalidClassFileName
/**
 * Implements the YITH_YWRAQ_Frontend class.
 *
 * @class   YITH_YWRAQ_Frontend
 * @since   1.0.0
 * @package YITH\RequestAQuote
 * @author  YITH <plugins@yithemes.com>
 */

defined( 'ABSPATH' ) || exit;


if ( ! class_exists( 'YITH_YWRAQ_Frontend' ) ) {

	/**
	 * Class YITH_YWRAQ_Frontend
	 */
	class YITH_YWRAQ_Frontend {

		/**
		 * Single instance of the class
		 *
		 * @var YITH_YWRAQ_Frontend
		 */
		protected static $instance;

		/**
		 * Shortcodes
		 *
		 * @var array
		 */
		public $shortcodes;

		/**
		 * My Account
		 *
		 * @var array
		 */
		public $my_account;

		/**
		 * Returns single instance of the class
		 *
		 * @return YITH_YWRAQ_Frontend
		 * @since 1.0.0
		 */
		public static function get_instance() {
			return ! is_null( self::$instance ) ? self::$instance : self::$instance = new self();
		}

		/**
		 * Constructor
		 *
		 * Initialize plugin and registers actions and filters to be used
		 *
		 * @since  1.0
		 */
		public function __construct() {

			add_action( 'wp_loaded', array( $this, 'update_raq_list' ), 35 );

			// custom styles and javascript.
			add_filter( 'body_class', array( $this, 'custom_body_class_in_quote_page' ) );

			// show button in single page.
			add_action( 'woocommerce_before_single_product', array( $this, 'show_button_single_page' ), 0 );

			// show request a quote button.
			add_filter( 'yith_ywraq-show_btn_single_page', 'yith_ywraq_show_button_in_single_page' );
			add_filter( 'yith_ywraq-btn_other_pages', 'yith_ywraq_show_button_in_other_pages', 10 );

			add_filter( 'woocommerce_loop_add_to_cart_link', array( $this, 'hide_add_to_cart_loop' ), 99, 2 );

			if ( ! class_exists( 'YITH_YWRAQ_Shortcodes' ) ) {
				require_once YITH_YWRAQ_INC . 'class.yith-ywraq-shortcodes.php';
			}

			$this->shortcodes = new YITH_YWRAQ_Shortcodes();

			// My account section.
			if ( is_user_logged_in() && ! is_admin() ) {
				if ( ! class_exists( 'YITH_Request_Quote_My_Account' ) ) {
					require_once YITH_YWRAQ_INC . 'class.yith-request-quote-my-account.php';
				}
				$this->my_account = YITH_Request_Quote_My_Account();
			}

			// quote button to checkout.
			add_action( 'woocommerce_review_order_before_submit', array( $this, 'show_button_on_checkout' ) );


			// add button to reorder.
			if ( 'yes' === get_option( 'ywraq_enable_order_again' ) ) {
				add_action( 'wp_loaded', array( $this, 'raq_order_again' ), 100 );
			}

		}

		/**
		 * Add button to Request a Quote again from frontend order view.
		 *
		 * @param WC_Order $order woocommerce order id.
		 *
		 * @return void
		 */
		public function add_request_quote_again_button( $order ) {
			$is_raq_order = ! empty( $order ) && $order->get_id() ? $order->get_meta( 'ywraq_raq' ) : '';
			// APPLY_FILTER: ywraq_valid_order_statuses_for_order_again : set the valid order status for which to show the Request Quote Again button.
			if ( $is_raq_order && $order->has_status( apply_filters( 'ywraq_valid_order_statuses_for_order_again',
			                                                         array( 'completed', 'pending', 'processing' ) ) ) ) {
				$button_label = get_option( 'ywraq_order_again_button_label',
				                            _x( 'Request the quote again', 'default label to ask the same quote on My Account page',
				                                'yith-woocommerce-request-a-quote' ) );
				$reorder_url  = wp_nonce_url( add_query_arg( 'raq_again', $order->get_id(),
				                                             YITH_Request_Quote_Premium()->get_raq_url( '' ) ), 'ywraq-order_again' );
				// APPLY_FILTER: ywraq_quote_again_button_label : change the label of the Request Quote Again button.
				echo '<p class="raq order-again"><a class="button" href="' . esc_url( $reorder_url ) . '">' . wp_kses_post( apply_filters( 'ywraq_quote_again_button_label',
				                                                                                                                           $button_label ) ) . '</a></p>';
			}
		}

		/**
		 * Manage Request a Quote Again process.
		 *
		 * @return void
		 */
		public function raq_order_again() {
			if ( isset( $_GET['raq_again'] ) && '' !== $_GET['raq_again'] && isset( $_GET['_wpnonce'] ) && wp_verify_nonce( sanitize_key( $_GET['_wpnonce'] ),
			                                                                                                                'ywraq-order_again' ) ) {

				// clean previous list.
				YITH_Request_Quote_Premium()->clear_raq_list();
				// get raq_content.
				$order       = wc_get_order( intval( $_GET['raq_again'] ) );
				$raq_request = $order->get_meta( '_raq_request' );

				if ( ! isset( $raq_request['raq_content'] ) ) {
					add_filter( 'woocommerce_is_attribute_in_product_name', '__return_false' );
					$items       = $order->get_items();
					$raq_content = array();
					foreach ( $items as $item ) {
						$raq_item['product_id'] = $item->get_product_id();
						$raq_item['quantity']   = $item->get_quantity();
						$raq_item['variations'] = array();
						$variation_id           = $item->get_variation_id();
						$id                     = md5( $raq_item['product_id'] );
						if ( $variation_id ) {
							$raq_item['variation_id'] = $variation_id;
							$formatted                = $item->get_formatted_meta_data();
							$id                       = md5( $variation_id );

							foreach ( $formatted as $meta ) {
								$raq_item['variations'][ 'attribute_' . $meta->key ] = $meta->value;
							}
						}

						$raq_content[ $id ] = $raq_item;
					}
				} else {
					$raq_content = $raq_request['raq_content'];
				}


				// start Raq Session if needed.
				if ( ! YITH_Request_Quote_Premium()->session_class ) {
					YITH_Request_Quote_Premium()->session_class = new YITH_YWRAQ_Session();
					YITH_Request_Quote_Premium()->set_session();
					YITH_Request_Quote_Premium()->session_class->set_customer_session_cookie( true );
				}


				// add each raq item to new raq list.
				foreach ( $raq_content as $key => $raq_item ) {

					if ( empty( $raq_item['yith_wapo_options'] ) ) {
						unset( $raq_item['yith_wapo_options'] );
					}

					if ( isset( $raq_item['variations'] ) ) {
						foreach ( $raq_item['variations'] as $k => $v ) {
							$raq_item[ $k ] = $v;
						}
						unset( $raq_item['variations'] );
					}

					// APPLY_FILTER: ywraq_order_again_raq_item_data: manage raq item data : arguments( $raq_item, $raq_content, $order).
					$raq_item = apply_filters( 'ywraq_order_again_raq_item_data', $raq_item, $raq_content, $order );
					YITH_Request_Quote_Premium()->add_item( $raq_item );

				}


				// reload the page after the re-order so to remove the query strings.
				wp_safe_redirect( YITH_Request_Quote_Premium()->get_raq_url( '' ) );
				exit;
			}
		}

		/**
		 * Add button to clean Request a quote list.
		 *
		 */
		public function clean_request_list_button() {
			echo '<button class="button ywraq_clean_list">' . apply_filters( 'ywraq_clear_list_label',
			                                                                 get_option( 'ywraq_clear_list_label',
			                                                                             esc_html__( 'Clear List', 'yith-woocommerce-request-a-quote' ) ) ) . '</button>'; //phpcs:ignore
		}

		/**
		 * Show the Request a quote button on checkout page.
		 */
		public function show_button_on_checkout() {

			if ( ! $this->check_if_show_button_on_checkout() ) {
				return;
			}

			$button_style = get_option( 'ywraq_raq_checkout_button_style', 'button' );
			echo '<input type="hidden" id="ywraq_checkout_quote" name="ywraq_checkout_quote" value="" />';

			$label_button = get_option( 'ywraq_checkout_quote_button_label',
			                            __( 'Request a Quote', 'yith-woocommerce-request-a-quote' ) );

			if ( 'button' === $button_style ) {
				echo wp_kses_post( apply_filters( 'ywraq_quote_button_checkout_html',
				                                  '<button type="submit" class="button alt" id="ywraq_checkout_quote_button" value="' . esc_attr( $label_button ) . '" data-value="' . esc_attr( $label_button ) . '">' . esc_html( $label_button ) . '</button>' ) );
			} else {
				echo wp_kses_post( apply_filters( 'ywraq_quote_button_checkout_html',
				                                  '<a href="#" class="quote-button alt" id="ywraq_checkout_quote_button" data-value="' . esc_attr( $label_button ) . '">' . esc_html( $label_button ) . '</a>' ) );
			}

		}

		/**
		 * Check if show the ask for a quote button on checkout.
		 *
		 * @return bool;
		 * @since 1.46.0
		 */
		public function check_if_show_button_on_checkout() {
			$show = 'yes' === get_option( 'ywraq_show_button_on_checkout_page', 'no' );

			if ( $show ) {
				$order_payment = WC()->session->get( 'order_awaiting_payment' );

				if ( $order_payment || ! YITH_Request_Quote_Premium()->check_user_type() ) {
					$show = false;
				}
			}

			return apply_filters( 'ywraq_check_if_show_button_on_checkout', $show );
		}


		/**
		 * Show Button on Single Product Page
		 *
		 */
		public function show_button_single_page() {

			global $product;

			if ( ywraq_check_blockified_single_templates() ) {
				return;
			}

			if ( $product ) {
				if ( is_string( $product ) ) {
					$product = wc_get_product_id_by_sku( $product );
				}

				if ( is_numeric( $product ) ) {
					$product = wc_get_product( $product );
				}

				if ( ! $product instanceof WC_Product ) {
					return;
				}
			} else {
				return;
			}

			$show_button_near_add_to_cart = get_option( 'ywraq_show_button_near_add_to_cart', 'no' );

			if ( yith_plugin_fw_is_true( $show_button_near_add_to_cart ) && $product->is_in_stock() && $product->get_price() !== '' ) {
				if ( $product->is_type( 'variable' ) ) {
					add_action( 'woocommerce_after_single_variation', array( $this, 'add_button_single_page' ), 15 );
				} else {
					add_action( 'woocommerce_after_add_to_cart_button', array( $this, 'add_button_single_page' ), 15 );
				}
			} else {
				add_action( 'woocommerce_single_product_summary', array( $this, 'add_button_single_page' ), 35 );
				add_action( 'yith_wcqv_product_summary', array( $this, 'add_button_single_page' ), 27 );
			}
		}

		/**
		 * Hide add to cart in single page
		 *
		 * Hide the button add to cart in the single product page
		 *
		 * @since  1.0
		 */
		public function hide_add_to_cart_single() {

			if ( catalog_mode_plugin_enabled() ) {
				return;
			}

			global $post;

			if ( ! $post || ! is_object( $post ) || ! is_singular() ) {
				return;
			}

			$product = wc_get_product( $post->ID );
			if ( ! $product || apply_filters( 'ywraq_hide_add_to_cart_single', false, $product ) ) {
				return;
			}
			if ( 'yes' === get_option( 'ywraq_hide_add_to_cart' ) || ( '' === $product->get_price() && 'external' !== $product->get_type() ) ) {
				$css = '';

				if ( isset( $product ) && $product && $product->is_type( 'variable' ) ) {
					$css = '.single_variation_wrap .variations_button button.button{
	                 display:none!important;
	                }';
				} elseif ( ! $product->is_type( 'gift-card' ) && ! $product->is_type( 'grouped' ) ) {
					$css = '.cart button.single_add_to_cart_button, .cart a.single_add_to_cart_button{
	                 display:none!important;
	                }';
				}
				wp_add_inline_style( 'yith_ywraq_frontend', apply_filters( 'yith_ywrad_hide_cart_single_css', $css ) );
			}

		}

		/**
		 * Hide add to cart in loop
		 *
		 * Hide the button add to cart in the shop page
		 *
		 * @param string     $link    .
		 * @param WC_Product $product .
		 *
		 * @return string
		 *
		 * @since  1.0
		 */
		public function hide_add_to_cart_loop( $link, $product = false ) {

			if ( $product instanceof WC_Product && ! catalog_mode_plugin_enabled() && 'yes' === get_option( 'ywraq_hide_add_to_cart' ) ) {

				if ( ! $product->is_type( array( 'external', 'grouped', 'variable' ) ) ) {
					if ( apply_filters( 'hide_add_to_cart_loop', true, $link, $product ) ) {
						$link = '';
					}
				}
			}

			return $link;
		}

		/**
		 * Enqueue Scripts and Styles
		 *
		 * @return void
		 * @since  1.0.0
		 * @deprecated
		 */
		public function enqueue_styles_scripts() {
			_deprecated_function( 'YITH_YWRAQ_Frontend::enqueue_styles_scripts', '3.0.0',
			                      'YITH_Request_Quote_Assets::enqueue_frontend_scripts' );
		}

		/**
		 * Check if the button can be showed in single page
		 *
		 * @return void
		 *
		 * @since  1.0.0
		 */
		public function add_button_single_page() {
			$show_button = apply_filters( 'yith_ywraq-show_btn_single_page', true ); //phpcs:ignore
			if ( yith_plugin_fw_is_true( $show_button ) ) {
				yith_ywraq_render_button();
			}
		}

		/**
		 * Print Add to Quote Button
		 *
		 * @param bool $product_id .
		 *
		 * @internal param bool $product
		 */
		public function print_button( $product_id = false ) {
			yith_ywraq_render_button( $product_id );
		}

		/**
		 * Update the Request Quote List
		 *
		 * @return void
		 * @since  1.0.0
		 */
		public function update_raq_list() {

			$posted = $_POST; //phpcs:ignore WordPress.Security.NonceVerification.Missing

			if ( isset( $posted['update_raq_wpnonce'] ) && isset( $posted['raq'] ) && wp_verify_nonce( $posted['update_raq_wpnonce'],
			                                                                                           'update-request-quote-quantity' ) ) {

				foreach ( $posted['raq'] as $key => $value ) {

					if ( 0 !== $value['qty'] ) {

						YITH_Request_Quote()->update_item( $key, 'quantity', $value['qty'] );
					} else {
						YITH_Request_Quote()->remove_item( $key );
					}
				}
			}
		}

		/**
		 * Add the gateway to WC Available Gateways
		 *
		 * @param array $gateways all available WC gateways .
		 *
		 * @return array $gateways all WC gateways + offline gateway
		 * @since 2.1.9
		 * @deprecated
		 */
		public function add_ywraq_gateway( $gateways ) {
			_deprecated_function( 'YITH_YWRAQ_Frontend::add_ywraq_gateway', '3.1.3',
			                      'YITH_Request_Quote_Premium::add_ywraq_gateway' );

			return YITH_Request_Quote_Premium()->add_ywraq_gateway( $gateways );
		}

		/**
		 * Add a custom body class on quote page.
		 *
		 * @param array $classes Array of body class.
		 *
		 * @return array
		 * @since 2.3.0
		 */
		public function custom_body_class_in_quote_page( $classes ) {
			if ( is_page( YITH_Request_Quote()->get_raq_page_id() ) ) {
				$classes[] = 'yith-request-a-quote-page';
			}

			return $classes;
		}

		/**
		 * Get Quote Cross-Sells.
		 *
		 * @param array $raq_content content of the quote.
		 *
		 * @return array
		 */
		public function ywraq_get_cross_sells( $raq_content = '' ) {
			$cross_sells = array();

			if ( empty( $raq_content ) || ! is_array( $raq_content ) ) {
				$raq_content = YITH_Request_Quote()->get_raq_return();
			}

			foreach ( $raq_content as $key => $values ) {
				$p  = wc_get_product( $values['product_id'] );
				$cs = $p->get_cross_sell_ids();
				if ( is_array( $cs ) && count( $cs ) > 0 ) {
					foreach ( $cs as $i => $id ) {
						array_push( $cross_sells, wc_get_product( $id ) );
					}
				}
			}

			return apply_filters( 'ywraq_cross_sells', $cross_sells, $raq_content );
		}

		/**
		 * Show Quote Cross-Sells.
		 *
		 * @param int    $limit   limit of returned.
		 * @param int    $columns columns to shows.
		 * @param string $orderby order by of products (default = rand).
		 * @param string $order   order of products (default = desc).
		 * @param int    $offset  Start from product.
		 *
		 * @return void
		 */
		public function ywraq_cross_sells_display( $limit = 2, $columns = 2, $orderby = 'rand', $order = 'desc', $offset = 0 ) {
			$raq_content = YITH_Request_Quote()->get_raq_return();
			if ( is_array( $raq_content ) && count( $raq_content ) > 0 ) {
				$cross_sells = $this->ywraq_get_cross_sells( $raq_content );
				if ( is_array( $cross_sells ) && count( $cross_sells ) > 0 ) {
					// Handle orderby and limit results.
					$orderby     = apply_filters( 'ywraq_cross_sells_orderby', $orderby );
					$order       = apply_filters( 'ywraq_cross_sells_order', $order );
					$cross_sells = wc_products_array_orderby( $cross_sells, $orderby, $order );
					$limit       = apply_filters( 'ywraq_cross_sells_total', $limit );
					$cross_sells = $limit > 0 ? array_slice( $cross_sells, $offset, $limit ) : $cross_sells;

					wc_set_loop_prop( 'name', 'cross-sells' );
					wc_set_loop_prop( 'columns', apply_filters( 'ywraq_cross_sells_columns', $columns ) );

					wc_get_template(
						'quote-cross-sells.php',
						array(
							'cross_sells' => $cross_sells,
						),
						'',
						YITH_YWRAQ_TEMPLATE_PATH . '/'
					);
				}
			}
		}

	}

	/**
	 * Unique access to instance of YITH_YWRAQ_Frontend class
	 *
	 * @return YITH_YWRAQ_Frontend
	 */
	function YITH_YWRAQ_Frontend() { //phpcs:ignore
		return YITH_YWRAQ_Frontend::get_instance();
	}
}
