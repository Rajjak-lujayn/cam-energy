<?php //phpcs:ignore WordPress.Files.FileName.InvalidClassFileName
/**
 * Implements the YITH_Request_Quote class.
 *
 * @class   YITH_Request_Quote
 * @since   1.0.0
 * @author  YITH <plugins@yithemes.com>
 * @package YITH\RequestAQuote
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'YITH_Request_Quote' ) ) {

	/**
	 * Class YITH_Request_Quote
	 */
	class YITH_Request_Quote {


		/**
		 * Single instance of the class
		 *
		 * @var YITH_Request_Quote
		 */
		protected static $instance;

		/**
		 * Session object
		 *
		 * @var string
		 */
		public $session_class;

		/**
		 * Content of session
		 *
		 * @var array
		 */
		public $raq_content = array();

		/**
		 * List of variations
		 *
		 * @var array
		 */
		public $raq_variations = array();

		/**
		 * Quote endpoint
		 *
		 * @var string
		 */
		public $endpoint = '';

		/**
		 * View Quote endpoint
		 *
		 * @var string
		 */
		public $view_endpoint = '';

		/**
		 * Returns single instance of the class
		 *
		 * @return YITH_Request_Quote
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
		 * @since  1.0.0
		 */
		public function __construct() {

			$this->_run();
			add_action( 'before_woocommerce_init', array( $this, 'declare_wc_features_support' ) );

			add_action( 'wp_loaded', array( $this, 'start_session' ) );
			add_action( 'template_redirect', array( $this, 'start_session' ) );

			/* plugin */
			if ( ! isset( $_REQUEST['action'] ) || 'yith_ywraq_action' !== $_REQUEST['action'] || ywraq_yit_contact_form_installed() ) { //phpcs:ignore WordPress.Security.NonceVerification.Recommended
				// register plugin to licence/update system.
				add_action( 'wp_loaded', array( $this, 'register_plugin_for_activation' ), 99 );
				add_action( 'admin_init', array( $this, 'register_plugin_for_updates' ) );
				add_action( 'plugins_loaded', array( $this, 'plugin_fw_loader' ), 15 );
			} else {
				remove_action( 'plugins_loaded', array( $this, 'plugin_fw_loader' ), 15 );
				include_once YITH_YWRAQ_DIR . 'plugin-fw/yit-woocommerce-compatibility.php';
			}

			/* ajax action */
			add_action( 'wc_ajax_yith_ywraq_action', array( $this, 'ajax' ) );

			// add quote from query string.
			add_action( 'wp_loaded', array( $this, 'add_to_quote_action' ), 30 );

			/* session settings */
			add_action( 'wp_loaded', array( $this, 'init' ), 30 );                // Get raq after WP and plugins are loaded.
			add_action( 'wp', array( $this, 'maybe_set_raq_cookies' ), 99 );      // Set cookies.

			/* email actions and filter */
			add_filter( 'woocommerce_email_classes', array( $this, 'add_woocommerce_emails' ) );
			add_action( 'woocommerce_init', array( $this, 'load_wc_mailer' ) );
			add_action( 'wp_loaded', array( $this, 'send_message' ), 30 );

			add_action( 'woocommerce_created_customer', array( $this, 'add_quote_to_new_customer' ), 10, 2 );

			// gutemberg blocks.
			add_filter( 'woocommerce_blocks_product_grid_item_html', array( $this, 'add_raq_button_to_block' ), 10, 3 );

			add_action( 'init', array( $this, 'add_my_account_endpoints' ), 15 );

			// Script Translations.
			add_filter( 'pre_load_script_translations', array( $this, 'script_translations' ), 10, 4 );

			// Set cookies before shutdown and ob flushing.
			add_action( 'shutdown', array( $this, 'maybe_set_raq_cookies' ), 0 ); // Set cookies before shutdown and ob flushing.


		}


		/**
		 * Add the request a quote buttons inside the product grid block of Gutenberg.
		 *
		 * @param string     $render  The <li> output.
		 * @param Object     $data    Product data.
		 * @param WC_Product $product The WC_Product object.
		 *
		 * @return string
		 */
		public function add_raq_button_to_block( $render, $data, $product ) {
			if ( 'variable' === $product->get_type() ) {
				return $render;
			}
			if ( 'yes' === get_option( 'ywraq_show_btn_woocommerce_blocks' ) ) {
				$shortcode = '[yith_ywraq_button_quote product="' . $product->get_id() . '"]';
				$data->raq = is_callable( 'apply_shortcodes' ) ? apply_shortcodes( $shortcode ) : do_shortcode( $shortcode );
				$render    = str_replace( '</li>', $data->raq . '</li>', $render );
			}

			return $render;
		}

		/**
		 * Include files and classes for the premium version.
		 *
		 * @since  2.0
		 */
		private function _run() { //phpcs:ignore

			if ( ! class_exists( 'WC_Session' ) ) {
				require_once WC()->plugin_path() . '/includes/abstracts/abstract-wc-session.php';
			}
			require_once YITH_YWRAQ_INC . 'class.yith-ywraq-session.php';
			require_once YITH_YWRAQ_INC . 'class.yith-request-quote-assets.php';
			require_once YITH_YWRAQ_INC . 'class-yith-ywraq-post-types.php';

			require_once YITH_YWRAQ_INC . 'objects/class-ywraq-pdf-template.php';
			require_once YITH_YWRAQ_INC . 'admin/cpt/class-ywraq-editor-pdf-template.php';
			YITH_YWRAQ_Editor_PDF_Template::get_instance();
			if ( ywraq_is_admin() ) {
				require_once YITH_YWRAQ_INC . 'class.yith-request-quote-admin.php';
				require_once YITH_YWRAQ_INC . 'admin/meta-boxes/class.yith-request-quote-order-meta-box.php';
				YITH_YWRAQ_Admin();

				if ( ywraq_is_elementor_editor() ) {
					require_once YITH_YWRAQ_INC . 'class.yith-ywraq-shortcodes.php';
					require_once YITH_YWRAQ_INC . 'class.yith-request-quote-frontend.php';
					YITH_YWRAQ_Frontend();
				}
			} else {
				require_once YITH_YWRAQ_INC . 'class.yith-ywraq-shortcodes.php';
				require_once YITH_YWRAQ_INC . 'class.yith-request-quote-frontend.php';
				YITH_YWRAQ_Frontend();
			}
			require_once YITH_YWRAQ_INC . 'class.yith-ywraq-cron.php';

			$this->_plugin_integrations();
		}

		/**
		 * Include the files and the classes if necessary.
		 *
		 * @since  2.0
		 */
		private function _plugin_integrations() { //phpcs:ignore

			/* compatibility with email template WC_Subscriptions */
			if ( class_exists( 'WC_Subscriptions' ) ) {
				add_filter( 'ywraq_quote_subtotal_item', array( $this, 'update_subtotal_item_price' ), 10, 3 );
				add_filter( 'ywraq_quote_subtotal_item_plain', array( $this, 'update_subtotal_item_price_plain' ), 10, 3 );
			}

			/* compatibility with WooCommerce Min/Max Quantities */
			if ( function_exists( 'YITH_WMMQ' ) && get_option( 'ywmmq_enable_rules_on_quotes' ) === 'yes' ) {
				add_filter( 'ywraq_quantity_input_value', array( $this, 'ywraq_quantity_input_value' ), 10 );
				add_filter( 'ywraq_quantity_max_value', array( YITH_WMMQ(), 'max_quantity_block' ), 10, 2 );
				add_filter( 'ywraq_quantity_min_value', array( YITH_WMMQ(), 'min_quantity_block' ), 10, 2 );
				add_filter( 'ywraq_quantity_step_value', array( YITH_WMMQ(), 'step_quantity_block' ), 10, 2 );
			}

			if ( class_exists( 'Woo_Advanced_QTY_Public' ) ) {
				add_filter( 'ywraq_quantity_input_value', array( $this, 'ywraq_quantity_input_value' ), 10 );
				add_filter( 'woocommerce_quantity_input_args', array( $this, 'woocommerce_quantity_input_args' ), 200 );
			}

			if ( class_exists( 'Woo_Advanced_QTY_Public' ) ) {
				add_filter( 'ywraq_quantity_input_value', array( $this, 'ywraq_quantity_input_value' ), 10 );
				add_filter( 'woocommerce_quantity_input_args', array( $this, 'woocommerce_quantity_input_args' ), 200 );
			}

			add_action( 'init', array( $this, 'gutenberg_integration' ) );
		}

		/**
		 * Gutenberg Integration
		 */
		public function gutenberg_integration() {
			if ( function_exists( 'yith_plugin_fw_gutenberg_add_blocks' ) ) {
				$blocks = include_once YITH_YWRAQ_DIR . 'plugin-options/gutenberg/blocks.php';
				yith_plugin_fw_gutenberg_add_blocks( $blocks );
				if ( defined( 'ELEMENTOR_VERSION' ) && function_exists( 'yith_plugin_fw_register_elementor_widgets' ) ) {
					yith_plugin_fw_register_elementor_widgets( $blocks, true );
				}
				wp_register_style( 'yith-ywraq-gutenberg', YITH_YWRAQ_ASSETS_URL . '/css/ywraq-gutenberg.css', '', YITH_YWRAQ_VERSION );
			}
		}

		/**
		 * Check if the plugin is working on administrator panel.
		 *
		 * @return bool
		 * @deprecated
		 * @since  2.0
		 */
		public function is_admin() {
			_deprecated_function( 'YITH_Request_Quote::is_admin', '3.0.0', 'ywraq_is_admin()' );

			return ywraq_is_admin();
		}

		/**
		 * Initialize session and cookies
		 *
		 * @since  1.0.0
		 */
		public function start_session() {

			if ( ! isset( $_COOKIE['woocommerce_items_in_cart'] ) && ( isset( $_POST['ywraq_action'] ) && 'add_item' === $_POST['ywraq_action'] ) || isset( $_REQUEST['add-to-quote'] ) ) { //phpcs:ignore
				do_action( 'woocommerce_set_cart_cookies', true );
			}

			$force_session = false;
			if ( 'template_redirect' === current_action() ) {
				$force_session = ywraq_force_start_session_on_quote_page();
			}

			$cookie = ywraq_get_cookie_name() . '_' . COOKIEHASH;
			/**
			 * APPLY_FILTERS: ywraq_force_start_session
			 *
			 * This filter allows forcing the start of the session.
			 *
			 * @param   boolean  $force  It true the session will be started.
			 *
			 * @return boolean
			 */
			if ( ( isset( $_POST['ywraq_action'] ) && 'add_item' === $_POST['ywraq_action'] ) || isset( $_COOKIE[ $cookie ] ) || isset( $_REQUEST['add-to-quote'] ) || apply_filters( 'ywraq_force_start_session', $force_session ) ) { //phpcs:ignore
				$this->session_class = new YITH_YWRAQ_Session();
				$this->set_session();
			}
		}

		/**
		 * Initialize functions
		 *
		 * @since  1.0.0
		 */
		public function init() {
			$this->get_raq_for_session();
			$this->session_class && $this->session_class->set_customer_session_cookie( true );
			$this->raq_variations = $this->get_variations_list();
		}

		/**
		 * Load YIT Plugin Framework
		 *
		 * @return void
		 * @since  1.0.0
		 */
		public function plugin_fw_loader() {
			if ( ! defined( 'YIT_CORE_PLUGIN' ) ) {
				global $plugin_fw_data;
				if ( ! empty( $plugin_fw_data ) ) {
					$plugin_fw_file = array_shift( $plugin_fw_data );
					require_once $plugin_fw_file;
				}
			}
		}

		/**
		 * Get request quote list
		 *
		 * @return array
		 * @since  1.0.0
		 */
		public function get_raq_return() {
			return $this->raq_content;
		}

		/**
		 * Get request quote list
		 *
		 * @return array
		 * @since  1.0.0
		 */
		public function get_variations_list() {
			$variations = array();
			if ( ! empty( $this->raq_content ) ) {
				foreach ( $this->raq_content as $item ) {
					if ( isset( $item['variation_id'] ) && 0 !== $item['variation_id'] ) {
						$variations[] = $item['variation_id'];
					}
				}
			}

			return $variations;
		}

		/**
		 * Get all errors in HTML mode or simple string.
		 *
		 * @param   array  $errors  .
		 * @param   bool   $html    .
		 *
		 * @return string
		 * @since 1.0.0
		 */
		public function get_errors( $errors, $html = true ) {
			return implode( ( $html ? '<br />' : ', ' ), $errors );
		}

		/**
		 * Check if Empty
		 *
		 * @return bool true if empty.
		 * @since  1.0.0
		 */
		public function is_empty() {
			return empty( $this->raq_content );
		}

		/**
		 * Get the items number of the current quote
		 *
		 * @return int
		 * @since  1.0.0
		 */
		public function get_raq_item_number() {
			$item_number = 0;
			if ( $this->raq_content ) {
				$item_number = array_sum( array_column( $this->raq_content, 'quantity' ) );
			}

			/**
			 * APPLY_FILTERS: ywraq_number_items_count
			 *
			 * Filter the number of items in the list.
			 *
			 * @param   int    $item_number    Number of item.
			 * @param   array  $quote_content  Content of quote list.
			 *
			 * @return int
			 */
			return apply_filters( 'ywraq_number_items_count', $item_number, $this->raq_content );
		}

		/**
		 * Get the products number of the current quote
		 *
		 * @return int
		 * @since  3.0.0
		 */
		public function get_raq_product_number() {
			/**
			 * APPLY_FILTERS: ywraq_number_product_count
			 *
			 * Filter the number of items in the list.
			 *
			 * @param   int    $item_number    Number of item.
			 * @param   array  $quote_content  Content of quote list.
			 *
			 * @return int
			 */
			return apply_filters( 'ywraq_number_product_count', count( $this->raq_content ), $this->raq_content );
		}

		/**
		 * Get request quote list from session
		 *
		 * @return array
		 * @since  1.0.0
		 */
		public function get_raq_for_session() {
			if ( $this->session_class ) {
				$this->raq_content = $this->session_class->get( 'raq', array() );
			}

			return $this->raq_content;
		}

		/**
		 * Sets the php session data for the request a quote
		 *
		 * @param   array  $raq_session   .
		 * @param   bool   $can_be_empty  .
		 *
		 * @since  1.0.0
		 *
		 */
		public function set_session( $raq_session = array(), $can_be_empty = false ) {
			if ( empty( $raq_session ) && ! $can_be_empty ) {
				$raq_session = $this->get_raq_for_session();
			}

			// Set raq  session data.
			if ( $this->session_class ) {
				$this->session_class->set( 'raq', $raq_session );
			}
			/**
			 * DO_ACTION:yith_raq_updated
			 *
			 * This action is triggered after set the session
			 */
			do_action( 'yith_raq_updated' );
		}

		/**
		 * Unset the session
		 *
		 * @return void
		 * @since  1.0.0
		 */
		public function unset_session() {
			// Set raq and coupon session data.
			$this->session_class->__unset( 'raq' );
		}

		/**
		 * Set Request a quote cookie
		 *
		 * @return void
		 * @since  1.0.0
		 */
		public function maybe_set_raq_cookies() {
			$set = true;

			if ( ! headers_sent() ) {
				if ( count( $this->raq_content ) > 0 ) {
					$this->set_rqa_cookies( true );
					$set = true;
				} elseif ( isset( $_COOKIE[ ywraq_get_cookie_name( 'items' ) ] ) ) {
					$this->set_rqa_cookies( false );
					$set = false;
				}
			}
			/**
			 * DO_ACTION:yith_ywraq_set_rqa_cookies
			 *
			 * This action is triggered after set the request a quote cookies
			 *
			 * @param   bool  $set  If true the cookie must be set.
			 */
			do_action( 'yith_ywraq_set_raq_cookies', $set );
		}

		/**
		 * Set hash cookie and items in raq.
		 *
		 * @param   bool  $set  .
		 *
		 * @since  1.0.0
		 * @access private
		 *
		 */
		private function set_rqa_cookies( $set = true ) {
			$items_name = ywraq_get_cookie_name( 'items' );
			$hash_name  = ywraq_get_cookie_name( 'hash' );
			if ( $set ) {
				/**
				 * APPLY_FILTERS:ywraq_items_cookie_expiration_time
				 *
				 * Filter to set the expiration time for the cookies
				 *
				 * @param   int  $time  Expiration time in seconds
				 *
				 * @return int
				 */
				wc_setcookie( $items_name, 1, apply_filters( 'ywraq_items_cookie_expiration_time', 0 ) );
				wc_setcookie( $hash_name, md5( wp_json_encode( $this->raq_content ) ), apply_filters( 'ywraq_hash_cookie_expiration_time', 0 ) );
			} elseif ( isset( $_COOKIE[ $items_name ] ) ) {
				wc_setcookie( $items_name, 0, time() - HOUR_IN_SECONDS );
				wc_setcookie( $hash_name, '', time() - HOUR_IN_SECONDS );
			}
			/**
			 * DO_ACTION:yith_ywraq_set_rqa_cookies
			 *
			 * This action is triggered after set the request a quote cookies
			 *
			 * @param   bool  $set  If true the cookie must be set.
			 */
			do_action( 'yith_ywraq_set_rqa_cookies', $set );
		}

		/**
		 * Check if the product is in the list
		 *
		 * @param   int   $product_id    .
		 * @param   bool  $variation_id  .
		 * @param   bool  $postadata     .
		 *
		 * @return mixed|void
		 */
		public function exists( $product_id, $variation_id = false, $postadata = false ) {

			$return = false;

			if ( $variation_id ) {
				// variation product.
				$key_to_find = md5( $product_id . $variation_id );
			} else {
				$key_to_find = md5( $product_id );
			}

			if ( array_key_exists( $key_to_find, $this->raq_content ) ) {
				$this->errors[] = __( 'Product already in the list.', 'yith-woocommerce-request-a-quote' );
				$return         = true;
			}

			/**
			 * APPLY_FILTER: ywraq_exists_in_list
			 *
			 * Filter if a product exists inside the quote list.
			 *
			 * @param   bool   $exists        If true the items is on the list
			 * @param   int    $product_id    Product to check.
			 * @param   int    $variation_id  Product variation to check.
			 * @param   array  $postadata     Postdata related to the product.
			 * @param   array  $quote_conten  List of items in quote.
			 *
			 * @return bool
			 */
			return apply_filters( 'ywraq_exists_in_list', $return, $product_id, $variation_id, $postadata, $this->raq_content );
		}

		/**
		 * Add an item to request quote list
		 *
		 * @param   array  $product_raq  .
		 *
		 * @return string
		 */
		public function add_item( $product_raq ) {

			$return = '';

			if ( ! ( isset( $product_raq['variation_id'] ) && '' !== $product_raq['variation_id'] ) ) {
				$product = wc_get_product( $product_raq['product_id'] );

				// grouped.
				if ( $product->is_type( 'grouped' ) ) {
					if ( is_array( $product_raq['quantity'] ) ) {
						foreach ( $product_raq['quantity'] as $item_id => $quantity ) {
							if ( ! $this->exists( $item_id ) && ! empty( $quantity ) ) {
								$raq = array(
									'product_id' => $item_id,
									'quantity'   => $quantity,
								);
								/**
								 * APPLY_FILTERS: ywraq_add_item
								 *
								 * Filter the quote item
								 *
								 * @param   array  $raq          Quote item to add to the list.
								 * @param   array  $product_raq  Product to add.
								 *
								 * @return array
								 */
								$raq = apply_filters( 'ywraq_add_item', $raq, $product_raq );
								/**
								 * APPLY_FILTERS: ywraq_quote_item_id
								 *
								 * Filter the id of quote item
								 *
								 * @param   string  $quote_item_id  Quote item id.
								 * @param   array   $product_raq    Product to add.
								 * @param   int     $item_id        Item id.
								 *
								 * @return string
								 */
								$this->raq_content[ apply_filters( 'ywraq_quote_item_id', md5( $item_id ), $product_raq, $item_id ) ] = $raq;
							}
						}
					}
				} else {
					// single product.
					if ( ! $this->exists( $product_raq['product_id'] ) ) {
						$product_raq['quantity'] = ( isset( $product_raq['quantity'] ) ) ? $product_raq['quantity'] : 1;

						$raq = array(
							'product_id' => $product_raq['product_id'],
							'quantity'   => $product_raq['quantity'],
						);

						$raq = apply_filters( 'ywraq_add_item', $raq, $product_raq );

						$this->raq_content[ apply_filters( 'ywraq_quote_item_id', md5( $product_raq['product_id'] ), $product_raq, $product_raq['product_id'] ) ] = $raq;
					} else {
						$return = 'exists';
					}
				}
			} else {
				// variable product.
				if ( ! $this->exists( $product_raq['product_id'], $product_raq['variation_id'] ) ) {
					$product_raq['quantity'] = ( isset( $product_raq['quantity'] ) ) ? $product_raq['quantity'] : 1;

					$raq = array(
						'product_id'   => $product_raq['product_id'],
						'variation_id' => $product_raq['variation_id'],
						'quantity'     => $product_raq['quantity'],
					);

					$raq = apply_filters( 'ywraq_add_item', $raq, $product_raq );

					$variations = array();

					foreach ( $product_raq as $key => $value ) {
						if ( stripos( $key, 'attribute' ) !== false ) {
							$variations[ $key ] = urldecode( $value );
						}
					}

					$raq ['variations'] = $variations;

					$this->raq_content[ apply_filters( 'ywraq_quote_item_id', md5( $product_raq['product_id'] . $product_raq['variation_id'] ), $product_raq, $product_raq['product_id'] ) ] = $raq;
				} else {
					$return = 'exists';
				}
			}

			if ( 'exists' !== $return ) {
				/**
				 * APPLY_FILTERS:ywraq_raq_content_before_add_item
				 *
				 * Filter the quote list before add a new item on quote
				 *
				 * @param   array  $quote_content  Content of quote.
				 * @param   array  $product_raq    Product to add to the quote
				 * @param   array  $raq            Quote request.
				 *
				 * @return array
				 */
				$this->raq_content = apply_filters( 'ywraq_raq_content_before_add_item', $this->raq_content, $product_raq, $raq );
				$this->set_session( $this->raq_content );
				$return = 'true';
				$this->set_rqa_cookies( count( $this->raq_content ) > 0 );
			}

			return $return;
		}

		/**
		 * Remove an item form the request list
		 *
		 * @param   string  $key  .
		 *
		 * @return bool
		 */
		public function remove_item( $key ) {
			if ( isset( $this->raq_content[ $key ] ) ) {
				/* SOLD INDIVIDUALLY SUPPORT */
				if ( isset( $this->raq_content[ $key ]['yith_wapo_parent'] ) ) {
					$product_id = $this->raq_content[ $key ]['product_id'];
					foreach ( $this->raq_content as $_key => $item ) {
						if ( $item['product_id'] === $product_id ) {
							unset( $this->raq_content[ $_key ] );
						}
					}
				} else {
					unset( $this->raq_content[ $key ] );
				}
				$this->set_session( $this->raq_content, true );
				$this->raq_variations = $this->get_variations_list();

				return true;
			} else {
				return false;
			}
		}

		/**
		 * Clear the list
		 */
		public function clear_raq_list() {
			$this->raq_content = array();
			$this->set_session( $this->raq_content, true );
			// remove list files.
			$this->get_pdf_file_path( 0, true );
		}


		/**
		 * Update an item in the raq list
		 *
		 * @param   string  $key    .
		 * @param   bool    $field  .
		 * @param   string  $value  .
		 *
		 * @return bool
		 */
		public function update_item( $key, $field = false, $value = '' ) {

			if ( $field && isset( $this->raq_content[ $key ][ $field ] ) ) {
				$this->raq_content[ $key ][ $field ] = $value;
				$this->set_session( $this->raq_content );
			} elseif ( isset( $this->raq_content[ $key ] ) ) {
				$this->raq_content[ $key ] = $value;
				$this->set_session( $this->raq_content );
			} else {
				return false;
			}

			$this->set_session( $this->raq_content );

			return true;
		}

		/**
		 * Switch a ajax call
		 */
		public function ajax() {
			if ( isset( $_POST['ywraq_action'] ) && method_exists( $this, 'ajax_' . sanitize_text_field( wp_unslash( $_POST['ywraq_action'] ) ) ) ) {//phpcs:ignore WordPress.Security.NonceVerification.Missing
				$s = 'ajax_' . sanitize_text_field( wp_unslash( $_POST['ywraq_action'] ) ); //phpcs:ignore WordPress.Security.NonceVerification.Missing
				$this->$s();
			}
		}

		/**
		 * Refresh quote totals on Ajax
		 */
		public function ajax_refresh_quote_totals() {
			wp_send_json(
				array( 'response' => do_shortcode( '[yith_ywraq_request_quote_table]' ) )
			);
		}

		/**
		 * Clean quote on Ajax
		 */
		public function ajax_clean_quote() {
			YITH_Request_Quote()->clear_raq_list();
			wp_send_json(
				array( 'response' => do_shortcode( '[yith_ywraq_request_quote]' ) )
			);
		}

		/**
		 * Add an item to request quote list in ajax mode
		 *
		 * @return void
		 * @since  1.0.0
		 */
		public function ajax_add_item() {

			$return             = 'false';
			$message            = '';
			$errors             = array();
			$product_id         = ( isset( $_POST['product_id'] ) && is_numeric( $_POST['product_id'] ) ) ? (int) $_POST['product_id'] : false;          //phpcs:ignore WordPress.Security.NonceVerification.Missing
			$is_valid_variation = ( isset( $_POST['variation_id'] ) && ! empty( $_POST['variation_id'] ) ) ? is_numeric( $_POST['variation_id'] ) : true;//phpcs:ignore WordPress.Security.NonceVerification.Missing
			/**
			 * APPLY_FILTERS: ywraq_ajax_validate_uploaded_files
			 *
			 * Filter the uploaded file with Product add-ons.
			 *
			 * @param   array  $valid  Valid files.
			 *
			 * @return array
			 */
			$wapo_valid = apply_filters( 'ywraq_ajax_validate_uploaded_files', array() );

			if ( ! empty( $wapo_valid ) ) {
				wp_send_json(
				/**
				 * APPLY_FILTERS: yith_ywraq_ajax_add_item_json
				 *
				 * Filter the  add item json response.
				 *
				 * @param   array  $response  Json response.
				 *
				 * @return array
				 */
					apply_filters(
						'yith_ywraq_ajax_add_item_json',
						array(
							'result'  => 'false',
							'message' => implode( '<br>', $wapo_valid ),
						)
					)
				);
				exit;
			}
			/**
			 * APPLY_FILTERS: ywraq_ajax_add_item_is_valid
			 *
			 * Filter if the item to add is valid.
			 *
			 * @param   boolean  $is_valid    Check if the item to add is valid.
			 * @param   int      $product_id  Product id.
			 *
			 * @return boolean
			 */
			$is_valid = apply_filters( 'ywraq_ajax_add_item_is_valid', $product_id && $is_valid_variation, $product_id );

			$postdata = $_POST;//phpcs:ignore WordPress.Security.NonceVerification.Missing
			/**
			 * APPLY_FILTERS: ywraq_ajax_add_item_prepare
			 *
			 * Filter the post data of quote item.
			 *
			 * @param   array  $postdata    Post data
			 * @param   int    $product_id  Product id.
			 *
			 * @return boolean
			 */
			$postdata = apply_filters( 'ywraq_ajax_add_item_prepare', $postdata, $product_id );

			if ( ! $is_valid ) {
				$errors[] = __( 'Error occurred while adding product to Request a Quote list.', 'yith-woocommerce-request-a-quote' );
			} else {
				$return = $this->add_item( $postdata );
			}

			if ( 'true' === $return ) {
				$message = ywraq_get_label( 'product_added' );
			} elseif ( 'exists' === $return ) {
				$message = ywraq_get_label( 'already_in_quote' );
			} elseif ( ! empty( $errors ) ) {
				/**
				 * APPLY_FILTERS: yith_ywraq_error_adding_to_list_message
				 *
				 * Filter the error message triggered after that a product is added to the quote.
				 *
				 * @param   string  $message  Error message.
				 *
				 * @return string
				 */
				$message = apply_filters( 'yith_ywraq_error_adding_to_list_message', $this->get_errors( $errors ) );
			}

			wp_send_json(
				apply_filters(
					'yith_ywraq_ajax_add_item_json',
					array(
						'result'     => $return,
						'message'    => $message,
						'rqa_url'    => $this->get_raq_page_url(),
						'variations' => implode( ',', $this->get_variations_list() ),
					)
				)
			);
		}

		/**
		 * Add an item in the list from query string
		 * for example ?add-to-quote=%product_id%&quantity=%quantity%
		 */
		public function add_to_quote_action() {

			if ( empty( $_REQUEST['add-to-quote'] ) ) {//phpcs:ignore WordPress.Security.NonceVerification.Recommended
				return;
			}

			$product_id      = apply_filters( 'woocommerce_add_to_quote_product_id', absint( $_REQUEST['add-to-quote'] ) );//phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$variation_id    = empty( $_REQUEST['variation_id'] ) ? '' : absint( wp_unslash( $_REQUEST['variation_id'] ) );//phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$adding_to_quote = wc_get_product( $product_id );

			if ( ! $adding_to_quote ) {
				return;
			}

			$quantity = empty( intval( wp_unslash( $_REQUEST['quantity'] ) ) ) ? 1 : wc_stock_amount( intval( wp_unslash( $_REQUEST['quantity'] ) ) );//phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$raq_data = array();

			if ( $adding_to_quote->is_type( 'variable' ) && $variation_id ) {
				$variation  = wc_get_product( $variation_id );
				$attributes = $variation->get_attributes();

				if ( ! empty( $attributes ) ) {
					foreach ( $attributes as $name => $value ) {
						$raq_data[ 'attribute_' . $name ] = $value;
					}
				}
			}

			$raq_data = array_merge(
				array(
					'product_id'   => $product_id,
					'variation_id' => $variation_id,
					'quantity'     => $quantity,
				),
				$raq_data
			);
			$return   = $this->add_item( $raq_data );

			if ( 'true' === $return ) {
				$message = ywraq_get_label( 'product_added' );
				wc_add_notice( $message, 'success' );
				/**
				 * DO_ACTION:ywraq_added_to_quote_by_url
				 *
				 * This action is triggered after that an item is added to the quote by url
				 */
				do_action( 'ywraq_added_to_quote_by_url' );
			} elseif ( 'exists' === $return ) {
				$message = ywraq_get_label( 'already_in_quote' );
				wc_add_notice( $message, 'notice' );
			}
		}

		/**
		 * Remove an item from the list in ajax mode
		 *
		 * @since  1.0.0
		 */
		public function ajax_remove_item() {
			$product_id = ( isset( $_POST['product_id'] ) && is_numeric( $_POST['product_id'] ) ) ? (int) $_POST['product_id'] : false;//phpcs:ignore WordPress.Security.NonceVerification.Missing
			$is_valid   = $product_id && isset( $_POST['key'] );                                                                       //phpcs:ignore WordPress.Security.NonceVerification.Missing
			if ( $is_valid ) {
				echo esc_attr( $this->remove_item( sanitize_key( wp_unslash( $_POST['key'] ) ) ) ); //phpcs:ignore WordPress.Security.NonceVerification.Missing
			} else {
				echo false;
			}
			die();
		}

		/**
		 * Remove an item from the list in ajax mode
		 *
		 * @since  1.0.0
		 */
		public function ajax_update_item_quantity() {
			$is_valid = isset( $_POST['key'] ) && isset( $_POST['quantity'] ); //phpcs:ignore WordPress.Security.NonceVerification.Missing
			if ( $is_valid ) {
				$quantity = preg_replace( '/[^0-9\.]/', '', $_POST['quantity'] ); //phpcs:ignore
				$updates  = $this->update_item_quantity( sanitize_key( $_POST['key'] ), $quantity ); //phpcs:ignore WordPress.Security.NonceVerification.Missing
			}

			wp_send_json( $updates );
		}

		/**
		 * Remove an item from the list in ajax mode
		 *
		 * @param   string  $key       .
		 * @param   int     $quantity  .
		 *
		 * @return boolean
		 * @since  1.0.0
		 */
		public function update_item_quantity( $key, $quantity ) {
			$updated = false;
			$min     = $quantity;
			$max     = $quantity;

			if ( isset( $this->raq_content[ $key ] ) ) {

				$product_id = ( ! empty( $this->raq_content[ $key ]['variation_id'] ) && $this->raq_content[ $key ]['variation_id'] > 0 ) ? $this->raq_content[ $key ]['variation_id'] : $this->raq_content[ $key ]['product_id'];
				$_product   = wc_get_product( $product_id );

				if ( function_exists( 'YITH_WMMQ' ) && get_option( 'ywmmq_enable_rules_on_quotes' ) === 'yes' ) {
					/**
					 * APPLY_FILTERS: ywraq_quantity_max_value
					 *
					 * Filter the max quantity of an item on quote list
					 *
					 * @param   int         $max_value  Max value.
					 * @param   WC_Product  $_product   Current product.
					 *
					 * @return int
					 */
					$max = apply_filters( 'ywraq_quantity_max_value', $_product->backorders_allowed() ? '' : $_product->get_stock_quantity(), $_product );
					/**
					 * APPLY_FILTERS: ywraq_quantity_min_value
					 *
					 * Filter the minimum quantity of an item on quote list
					 *
					 * @param   int         $min_value  Max value.
					 * @param   WC_Product  $_product   Current product.
					 *
					 * @return int
					 */
					$min = apply_filters( 'ywraq_quantity_min_value', 0, $_product );
				}

				$quantity = ( $quantity <= $min ) ? $min : $quantity;
				$quantity = ( '' !== (string) $max && $quantity >= $max ) ? $max : $quantity;

				/**
				 * APPLY_FILTERS: ywraq_validate_ajax_quantity
				 *
				 * Filter the quantity of an item on quote list for validation purposes
				 *
				 * @param   int         $quantity  The current quantity.
				 * @param   WC_Product  $_product  Current product.
				 *
				 * @return int
				 */
				$quantity = apply_filters( 'ywraq_validate_ajax_quantity', $quantity, $_product );

				$this->raq_content[ $key ]['quantity'] = $quantity;

				$this->set_session( $this->raq_content, true );
				$updated = true;
			}

			return $updated;
		}

		/**
		 * Check if an element exist the list in ajax mode
		 *
		 * @return void
		 * @since  1.0.0
		 */
		public function ajax_variation_exist() {
			if ( isset( $_POST['product_id'] ) && isset( $_POST['variation_id'] ) ) { //phpcs:ignore WordPress.Security.NonceVerification.Missing
				$message       = '';
				$label_browser = '';
				$product_id    = ( '' !== $_POST['variation_id'] ) ? intval( wp_unslash( $_POST['variation_id'] ) ) : intval( wp_unslash( $_POST['product_id'] ) ); //phpcs:ignore WordPress.Security.NonceVerification.Missing
				$product       = wc_get_product( $product_id );

				$posted = $_POST; //phpcs:ignore WordPress.Security.NonceVerification.Missing

				if ( ( ! YITH_Request_Quote_Premium()->check_user_type() || ( ywraq_show_btn_only_out_of_stock() && $product->is_in_stock() ) ) ) {
					/**
					 * APPLY_FILTERS: yith_ywraq_product_not_quoted
					 *
					 * Filter the message that says that a product is not quotable.
					 *
					 * @param   string  $product_not_quotable  Message that says that a product is not quotable.
					 *
					 * @return string
					 */
					$message = apply_filters( 'yith_ywraq_product_not_quoted', __( 'This product is not quotable.', 'yith-woocommerce-request-a-quote' ) );
				} elseif ( $this->exists( $posted['product_id'], $posted['variation_id'], $posted ) === 'true' ) {
					$message       = ywraq_get_label( 'already_in_quote' );
					$label_browser = ywraq_get_label( 'browse_list' );
				}

				$return = ( '' !== $message );

				wp_send_json(
					array(
						'result'       => $return,
						'message'      => $message,
						'label_browse' => $label_browser,
						'rqa_url'      => $this->get_raq_page_url(),
					)
				);
			}
		}

		/**
		 * Reject the quote
		 *
		 * @since 3.0.0
		 */
		public function ajax_reject_quote() {
			$result = YITH_YWRAQ_Order_Request()->change_order_status();

			wp_send_json(
				array(
					'result'  => isset( $result['rejected'] ) ? $result['rejected'] : false,
					'message' => isset( $result['message'] ) ? $result['message'] : '',
				)
			);
		}


		/**
		 * Return the url of request quote page
		 *
		 * @return string
		 * @since 1.0.0
		 */
		public function get_raq_page_url() {
			$option_value = get_option( 'ywraq_page_id' );
			$option_value = apply_filters( 'wpml_object_id', $option_value, 'post', true );
			$base_url     = get_the_permalink( $option_value );

			/**
			 * APPLY_FILTERS:ywraq_request_page_url
			 *
			 * Filter the request a quote page url
			 *
			 * @param   string  $base_url  Base url.
			 *
			 * @return string
			 */
			return apply_filters( 'ywraq_request_page_url', $base_url );
		}

		/**
		 * Return the id of request quote page
		 *
		 * @return int
		 * @since 1.9.0
		 */
		public function get_raq_page_id() {
			$page_id = get_option( 'ywraq_page_id' );
			$page_id = apply_filters( 'wpml_object_id', $page_id, 'post', true );

			/**
			 * APPLY_FILTERS:ywraq_request_page_id
			 *
			 * Filter the id of the request a quote page.
			 *
			 * @param   int  $page_id  Request a quote page id.
			 *
			 * @return int
			 */
			return apply_filters( 'ywraq_request_page_id', $page_id );
		}

		/**
		 * Get all errors in HTML mode or simple string.
		 *
		 * @return void
		 * @since 1.0.0
		 */
		public function send_message() {

			if ( ! isset( $_REQUEST['raq_mail_wpnonce'] ) ) { //phpcs:ignore WordPress.Security.NonceVerification.Recommended
				return;
			}

			$errors                  = array();
			$user_additional_field   = '';
			$user_additional_field_2 = '';
			$user_additional_field_3 = '';
			$attachment              = array();
			if ( isset( $_POST['raq_mail_wpnonce'] ) ) { //phpcs:ignore WordPress.Security.NonceVerification.Missing
				$posted_raq = wp_unslash( $_POST ); //phpcs:ignore WordPress.Security.NonceVerification.Missing

				if ( empty( $posted_raq['rqa_name'] ) ) {//phpcs:ignore WordPress.Security.NonceVerification.Missing
					$errors[] = '<p>' . esc_html__( 'Please enter a name', 'yith-woocommerce-request-a-quote' ) . '</p>';
				}

				if ( ! isset( $posted_raq['rqa_email'] ) || empty( $posted_raq['rqa_email'] ) || ! is_email( $posted_raq['rqa_email'] ) ) {
					$errors[] = '<p>' . esc_html__( 'Please enter a valid email', 'yith-woocommerce-request-a-quote' ) . '</p>';
				}

				if ( ! empty( $posted_raq['rqa_name'] ) && ! empty( $posted_raq['rqa_email'] ) && isset( $posted_raq['createaccount'] ) && email_exists( $posted_raq['rqa_email'] ) ) {//phpcs:ignore WordPress.Security.NonceVerification.Missing
					$errors[] = '<p>' . esc_html__( 'An account is already registered with your email address. Please login.', 'yith-woocommerce-request-a-quote' ) . '</p>';
				}

				if ( 'yes' === get_option( 'ywraq_additional_text_field' ) && 'yes' === get_option( 'ywraq_additional_text_field_required' ) && isset( $posted_raq['rqa_text_field'] ) && empty( $posted_raq['rqa_text_field'] ) ) {//phpcs:ignore WordPress.Security.NonceVerification.Missing
					// translators: placeholder label of field.
					$errors[] = '<p>' . sprintf( esc_html__( 'Please enter a value for %s', 'yith-woocommerce-request-a-quote' ), get_option( 'ywraq_additional_text_field_label' ) ) . '</p>';
				} else {
					$user_additional_field = isset( $posted_raq['rqa_text_field'] ) ? $posted_raq['rqa_text_field'] : '';
				}

				if ( 'yes' === get_option( 'ywraq_additional_text_field_2' ) && 'yes' === get_option( 'ywraq_additional_text_field_required_2' ) && isset( $posted_raq['rqa_text_field_2'] ) && empty( $posted_raq['rqa_text_field_2'] ) ) {//phpcs:ignore WordPress.Security.NonceVerification.Missing
					// translators: placeholder label of field.
					$errors[] = '<p>' . sprintf( esc_html__( 'Please enter a value for %s', 'yith-woocommerce-request-a-quote' ), get_option( 'ywraq_additional_text_field_label_2' ) ) . '</p>';
				} else {
					$user_additional_field_2 = isset( $posted_raq['rqa_text_field_2'] ) ? $posted_raq['rqa_text_field_2'] : '';
				}

				if ( 'yes' === get_option( 'ywraq_additional_text_field_3' ) && 'yes' === get_option( 'ywraq_additional_text_field_required_3' ) && isset( $posted_raq['rqa_text_field_3'] ) && empty( $posted_raq['rqa_text_field_3'] ) ) {//phpcs:ignore WordPress.Security.NonceVerification.Missing
					// translators: placeholder label of field.
					$errors[] = '<p>' . sprintf( esc_html__( 'Please enter a value for %s', 'yith-woocommerce-request-a-quote' ), get_option( 'ywraq_additional_text_field_label_3' ) ) . '</p>';
				} else {
					$user_additional_field_3 = isset( $posted_raq['rqa_text_field_3'] ) ? $posted_raq['rqa_text_field_3'] : '';
				}

				if ( ywraq_check_recaptcha_options() ) {
					$captcha_error_string = sprintf( '<p>%s</p>', __( 'Please check the captcha form.', 'yith-woocommerce-request-a-quote' ) );
					if ( isset( $posted_raq['g-recaptcha-response'] ) ) {//phpcs:ignore WordPress.Security.NonceVerification.Missing
						$captcha = $posted_raq['g-recaptcha-response'];  //phpcs:ignore WordPress.Security.NonceVerification.Missing
					}
					if ( ! $captcha ) {
						$errors[] = $captcha_error_string;
					} else {
						$secret_key = get_option( 'ywraq_reCAPTCHA_secretkey' );
						$response   = wp_remote_get( 'https://www.google.com/recaptcha/api/siteverify?secret=' . $secret_key . '&response=' . $captcha );
						if ( is_wp_error( $response ) || ! isset( $response['body'] ) ) {
							$errors[] = $captcha_error_string;
						} else {
							$response_keys = json_decode( $response['body'], true );
							if ( intval( $response_keys['success'] ) !== 1 ) {
								$errors[] = $captcha_error_string;
							}
						}
					}
				}

				if ( 'yes' === get_option( 'ywraq_additional_upload_field' ) && ! empty( $_FILES['rqa_upload_field']['name'] ) ) {
					if ( ! function_exists( 'wp_handle_upload' ) ) {
						require_once ABSPATH . 'wp-admin/includes/file.php';
					}

					$uploadedfile     = $_FILES['rqa_upload_field'];//phpcs:ignore
					$upload_overrides = array( 'test_form' => false );
					$movefile         = wp_handle_upload( $uploadedfile, $upload_overrides );

					if ( $movefile && ! isset( $movefile['error'] ) ) {
						$attachment = $movefile;
					} else {
						$errors[] = '<p>' . $movefile['error'] . '</p>';
					}
				}

				if ( YITH_Request_Quote()->is_empty() ) {
					$errors[] = ywraq_get_list_empty_message();
				}
				/**
				 * APPLY_FILTERS: ywraq_request_validate_fields
				 *
				 * Filter the errors after that the customer sends the request.
				 *
				 * @param   array  $errors  List of errors
				 * @param   array  $post    List of posted arguments.
				 *
				 * @return array
				 */
				$errors = apply_filters( 'ywraq_request_validate_fields', $errors, $_POST ); //phpcs:ignore WordPress.Security.NonceVerification.Missing

				if ( empty( $errors ) ) {
					$args = array(
						'user_name'               => $posted_raq['rqa_name'],
						'user_email'              => $posted_raq['rqa_email'],
						'user_password'           => isset( $posted_raq['account_password'] ) ? $posted_raq['account_password'] : '',
						'user_message'            => nl2br( $posted_raq['rqa_message'] ),
						'user_additional_field'   => $user_additional_field,
						'user_additional_field_2' => $user_additional_field_2,
						'user_additional_field_3' => $user_additional_field_3,
						'attachment'              => $attachment,
						'raq_content'             => YITH_Request_Quote()->get_raq_return(),
					);

					$current_customer_id = 0;
					$current_customer    = get_user_by( 'email', $posted_raq['rqa_email'] );
					/**
					 * APPLY_FILTERS:ywraq_force_create_account
					 *
					 * Filter if is necessary to force the creation of an account.
					 *
					 * @param   boolean  $create_account  If true is necessary to create an account.
					 *
					 * @return boolean
					 */
					$force_to_create_account = apply_filters( 'ywraq_force_create_account', false );
					if ( is_user_logged_in() ) {
						$current_customer_id = get_current_user_id();
					} elseif ( $current_customer ) {
						$current_customer_id = $current_customer->ID;
					} elseif ( isset( $_POST['createaccount'] ) || $force_to_create_account ) { //phpcs:ignore WordPress.Security.NonceVerification.Missing
						if ( username_exists( $args['user_name'] ) ) {
							$user_login = $this->get_username( $args['user_name'], $args['user_email'] );
						} else {
							$user_login = $args['user_name'];
						}
						$current_customer_id = $this->add_user( $user_login, $args['user_email'], $args['user_password'] );
						wp_set_auth_cookie( $current_customer_id, true );
					}

					$args['customer_id'] = $current_customer_id;

					if ( isset( $_REQUEST['lang'] ) ) {                                 //phpcs:ignore WordPress.Security.NonceVerification.Recommended
						$args['lang'] = sanitize_key( wp_unslash( $_REQUEST['lang'] ) );//phpcs:ignore WordPress.Security.NonceVerification.Recommended
					}

					if ( 'yes' === get_option( 'ywraq_enable_order_creation', 'yes' ) ) {
						/**
						 * DO_ACTION:ywraq_process
						 *
						 * This action triggers to create the order with quote
						 *
						 * @param   array  $arguments  List of arguments useful to process the quote.
						 */
						do_action( 'ywraq_process', $args );
					}
					/**
					 * DO_ACTION:send_raq_customer_mail
					 *
					 * This action triggers to send the quote to customer
					 *
					 * @param   array  $arguments  List of arguments useful to send the email with quote.
					 */
					do_action( 'send_raq_customer_mail', $args );

					wp_safe_redirect( $this->get_redirect_page_url(), 301 );

					exit();
				}
			} else {
				$errors[] = '<p>' . __( 'There was a problem sending your request. Please try again.', 'yith-woocommerce-request-a-quote' ) . '</p>';
			}

			yith_ywraq_add_notice( $this->get_errors( $errors ), 'error' );
		}

		/**
		 *
		 * Check if set a Thank you page
		 *
		 * @return bool|string
		 */
		public function has_thank_you_page() {

			if ( get_option( 'ywraq_how_show_after_sent_the_request', 'simple_message' ) !== 'thank_you_page' ) {
				return false;
			}

			return ( get_option( 'ywraq_thank_you_page' ) ) ? get_permalink( get_option( 'ywraq_thank_you_page' ) ) : false;
		}

		/**
		 * Return the username of user
		 *
		 * @param   string  $hyb_user_login  .
		 * @param   string  $hyb_user_email  .
		 *
		 * @return string
		 * @since  1.0.0
		 */
		public function get_username( $hyb_user_login, $hyb_user_email ) {
			$yith_user_login = $hyb_user_login;
			if ( ! empty( $hyb_user_login ) && 'yes' === get_option( 'woocommerce_registration_generate_username' ) && ! empty( $hyb_user_email ) ) {
				$yith_user_login = sanitize_user( current( explode( '@', $hyb_user_email ) ) );
				if ( username_exists( $hyb_user_login ) ) {
					$append     = 1;
					$o_username = $yith_user_login;

					while ( username_exists( $yith_user_login ) ) {
						$yith_user_login = $o_username . $append;
						$append ++;
					}
				}
			}

			return $yith_user_login;
		}

		/**
		 * Filters woocommerce available mails, to add wishlist related ones
		 *
		 * @param   array  $emails  array .
		 *
		 * @return array
		 * @since 1.0
		 */
		public function add_woocommerce_emails( $emails ) {
			if ( ! isset( $emails['YITH_YWRAQ_Send_Email_Request_Quote'] ) ) {
				$emails['YITH_YWRAQ_Send_Email_Request_Quote'] = include YITH_YWRAQ_INC . 'emails/class.yith-ywraq-send-email-request-quote.php';
			}

			return $emails;
		}

		/**
		 * Loads WC Mailer when needed
		 *
		 * @return void
		 * @since 1.0
		 */
		public function load_wc_mailer() {
			add_action( 'send_raq_mail', array( 'WC_Emails', 'send_transactional_email' ), 10 );
		}

		/**
		 * Add a new user
		 *
		 * @param   string  $username       .
		 * @param   string  $user_email     .
		 * @param   string  $user_password  .
		 *
		 * @return string
		 * @since  1.0.0
		 *
		 */
		public function add_user( $username, $user_email, $user_password ) {

			$password = ( 'yes' === get_option( 'woocommerce_registration_generate_password' ) ) ? wp_generate_password() : $user_password;
			/**
			 * APPLY_FILTERS:ywraq_new_user_role
			 *
			 * Filter the role assigned when a user is added.
			 *
			 * @param   string  $role  Role.
			 *
			 * @return string
			 */
			$user_role = apply_filters( 'ywraq_new_user_role', 'customer' );

			$args = array(
				'user_login' => $username,
				'user_pass'  => $password,
				'user_email' => $user_email,
				'remember'   => false,
				'role'       => $user_role,
			);

			$customer_id = wp_insert_user( $args );

			wp_signon( $args, false );

			do_action( 'woocommerce_created_customer', $customer_id, $args, $password );

			return $customer_id;
		}

		/**
		 * Update subtotal price for compatibility with WC_Subscriptions
		 *
		 * @param   string      $subtotal   .
		 * @param   float       $line_item  .
		 * @param   WC_Product  $product    .
		 *
		 * @return mixed
		 * @since  1.3.0
		 */
		public function update_subtotal_item_price( $subtotal, $line_item, $product ) {
			if ( ! WC_Subscriptions_Product::is_subscription( $product->get_id() ) ) {
				return $subtotal;
			}
			$this->subtotal = $line_item;
			add_filter( 'woocommerce_subscriptions_product_price_string_inclusions', array( $this, 'update_price' ), 10, 1 );

			return $product->get_price_html();
		}

		/**
		 * Update price in plain email for compatibility with WC_Subscriptions
		 *
		 * @param   float       $subtotal   .
		 * @param   float       $line_item  .
		 * @param   WC_Product  $product    .
		 *
		 * @return string
		 * @since  1.3.0
		 */
		public function update_subtotal_item_price_plain( $subtotal, $line_item, $product ) {
			if ( ! WC_Subscriptions_Product::is_subscription( $product->get_id() ) ) {
				return $subtotal;
			}
			$this->subtotal = $line_item;

			add_filter( 'woocommerce_subscriptions_product_price_string_inclusions', array( $this, 'update_price' ), 10, 1 );

			return wc_price( $this->subtotal );
		}

		/**
		 * Update price for compatibility with WC_Subscriptions
		 *
		 * @param   array  $include  .
		 *
		 * @return array
		 * @since  1.3.0
		 */
		public function update_price( $include ) {
			$include['price'] = '<ins><span class="amount">' . wc_price( $this->subtotal ) . '</span></ins>';

			return $include;
		}

		/**
		 * Change the value of quantity input for compatibility with WooCommerce Min/Max Quantities
		 *
		 * @param   array  $args  .
		 *
		 * @return mixed
		 * @since  1.3.0
		 */
		public function woocommerce_quantity_input_args( $args ) {

			if ( isset( $this->quantity ) ) {
				$args['input_value'] = $this->quantity;
			}

			return $args;
		}

		/**
		 * Save the temp quantity in a param for compatibility with WooCommerce Min/Max Quantities
		 *
		 * @param   int  $quantity  .
		 *
		 * @return mixed
		 * @since  1.3.0
		 */
		public function ywraq_quantity_input_value( $quantity ) {
			$this->quantity = $quantity;

			return $quantity;
		}

		/**
		 * Check if the checkout is enabled after the acceptance of quote
		 *
		 * @return bool
		 */
		public function enabled_checkout() {

			if ( 'no' === get_option( 'ywraq_show_accept_link', 'yes' ) ) {
				return false;
			}

			$accepted_page_id = apply_filters( 'wpml_object_id', $this->get_accepted_page(), 'page', true );
			$checkout_page_id = apply_filters( 'wpml_object_id', get_option( 'woocommerce_checkout_page_id' ), 'page', true );
			$cart_page_id     = apply_filters( 'wpml_object_id', get_option( 'woocommerce_cart_page_id' ), 'page', true );

			return ( $accepted_page_id === $checkout_page_id || $accepted_page_id === $cart_page_id );
		}

		/**
		 *
		 * Get id of the page for Accepted link
		 *
		 * @return int|mixed|void
		 */
		public function get_accepted_page() {
			return apply_filters( 'wpml_object_id', get_option( 'ywraq_page_accepted' ), 'page', true );
		}

		/**
		 * Get Redirect page URL
		 *
		 * @param   array  $attr  .
		 *
		 * @return bool|false|string
		 */
		public function get_redirect_page_url( $attr = array() ) {
			$thank_you_page  = $this->has_thank_you_page();
			$thank_you_quote = get_option( 'ywraq_how_show_after_sent_the_request', 'simple_message' );
			if ( $thank_you_page ) {
				/**
				 * APPLY_FILTERS: ywraq_thankyou_page_url
				 *
				 * This filter allow change the thank you page url
				 *
				 * @param   string  $thank_you_page  Redirect page
				 *
				 * @return string
				 */
				$redirect = apply_filters( 'ywraq_thankyou_page_url', $thank_you_page );
			} elseif ( 'thank_you_quote' === $thank_you_quote && WC()->session ) {
				$raq_id = WC()->session->get( 'raq_new_order', false );
				/**
				 * APPLY_FILTERS: ywraq_preview_slug
				 *
				 * change the url argument preview for a different text.
				 *
				 * @param   string preview
				 */
				$preview_slug = apply_filters( 'ywraq_preview_slug', 'preview' );
				if ( is_user_logged_in() ) {
					$redirect = YITH_YWRAQ_Order_Request()->get_view_order_url( $raq_id );
				} else {
					$url_args = array(
						'quote'       => $raq_id,
						$preview_slug => 1,
					);
					$attr     = wp_parse_args( $attr, $url_args );
					$redirect = $this->get_raq_page_url();
				}
			} else {
				$redirect = $this->get_raq_page_url();
			}

			if ( $attr ) {
				$redirect = add_query_arg( $attr, $redirect );
			}

			return $redirect;
		}

		/**
		 * Add quote to customer after registration
		 *
		 * @access public
		 *
		 * @param   int    $customer_id        .
		 * @param   mixed  $new_customer_data  .
		 *
		 * @since  1.0.0
		 */
		public function add_quote_to_new_customer( $customer_id, $new_customer_data ) {
			if ( empty( $new_customer_data['user_email'] ) ) {
				return;
			}

			global $wpdb;
			// get ids.
			$query = $wpdb->prepare( "SELECT post_id from {$wpdb->postmeta} WHERE meta_key = 'ywraq_customer_email' AND meta_value LIKE %s ", '%' . $new_customer_data['user_email'] . '%' );
			$ids   = $wpdb->get_col( $query ); //phpcs:ignore

			if ( empty( $ids ) ) {
				return;
			}

			foreach ( $ids as $id ) {
				update_post_meta( $id, '_customer_user', $customer_id );
			}
		}

		/**
		 * Add Endpoint to My Account
		 *
		 * @since  1.0.0
		 */
		public function add_my_account_endpoints() {

			// set my account endpoints.
			/**
			 * APPLY_FILTERS: ywraq_endpoint
			 *
			 * This filter allow change the request a quote endpoint
			 *
			 * @param   string  $endpoint  Endpoint
			 *
			 * @return string
			 */
			$this->endpoint      = apply_filters( 'ywraq_endpoint', 'quotes' );
			$this->view_endpoint = get_option( 'woocommerce_myaccount_view_quote_endpoint', 'view-quote' );

			if ( ! is_null( WC()->query ) ) {
				WC()->query->query_vars['quotes']     = $this->endpoint;
				WC()->query->query_vars['view-quote'] = $this->view_endpoint;

				add_rewrite_endpoint( $this->endpoint, EP_ROOT | EP_PAGES );
				add_rewrite_endpoint( $this->view_endpoint, EP_ROOT | EP_PAGES );
			}

			global $sitepress;

			if ( ! $sitepress ) {
				add_filter( 'option_rewrite_rules', array( $this, 'rewrite_rules' ), 1 );
				function_exists( 'get_home_path' ) && flush_rewrite_rules();
			}
		}

		/**
		 * Check if the permalink should be flushed.
		 *
		 * @param   array  $rules  Rewrite Rules.
		 *
		 * @return array|bool
		 */
		public function rewrite_rules( $rules ) {
			$ep = $this->endpoint;
			$vp = $this->view_endpoint;

			return isset( $rules["(.?.+?)/{$ep}(/(.*))?/?$"] ) && isset( $rules["(.?.+?)/{$vp}(/(.*))?/?$"] ) ? $rules : false;
		}


		/**
		 * Register plugins for activation tab
		 *
		 * @return void
		 * @since    1.0.0
		 */
		public function register_plugin_for_activation() {
			if ( ! class_exists( 'YIT_Plugin_Licence' ) ) {
				require_once YITH_YWRAQ_DIR . 'plugin-fw/lib/yit-plugin-licence.php';
			}
			YIT_Plugin_Licence()->register( YITH_YWRAQ_INIT, YITH_YWRAQ_SECRET_KEY, YITH_YWRAQ_SLUG );
		}

		/**
		 * Register plugins for update tab
		 *
		 * @return void
		 * @since    1.0.0
		 */
		public function register_plugin_for_updates() {
			if ( ! class_exists( 'YIT_Upgrade' ) ) {
				require_once YITH_YWRAQ_DIR . 'plugin-fw/lib/yit-upgrade.php';
			}
			YIT_Upgrade()->register( YITH_YWRAQ_SLUG, YITH_YWRAQ_INIT );
		}

		/**
		 * Create the json translation through the PHP file
		 * so it's possible using normal translations (with PO files) also for JS translations
		 *
		 * @param   string|null  $json_translations  Json translation.
		 * @param   string       $file               File.
		 * @param   string       $handle             Handle.
		 * @param   string       $domain             Domain.
		 *
		 * @return string|null
		 * @since 4.0
		 */
		public function script_translations( $json_translations, $file, $handle, $domain ) {
			$plugin_domain = 'yith-woocommerce-request-a-quote';
			$handles       = array( 'ywraq-pdf-template-builder-script', 'yith_ywraq_pdf_templates' );

			if ( $plugin_domain === $domain && in_array( $handle, $handles, true ) ) {
				$path = YITH_YWRAQ_DIR . 'languages/' . $domain . '.php';
				if ( file_exists( $path ) ) {
					$translations = include $path;

					$json_translations = wp_json_encode(
						array(
							'domain'      => $handles,
							'locale_data' => array(
								'messages' =>
									array(
										'' => array(
											'domain'       => $handles,
											'lang'         => get_locale(),
											'plural-forms' => 'nplurals=2; plural=(n != 1);',
										),
									)
									+
									$translations,
							),
						)
					);
				}
			}

			return $json_translations;
		}

		/**
		 * Declare support for WooCommerce features.
		 */
		public function declare_wc_features_support() {
			if ( class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
				\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', YITH_YWRAQ_INIT, true );
				\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'cart_checkout_blocks', YITH_YWRAQ_INIT, true );
			}
		}
	}
}

/**
 * Unique access to instance of YITH_Request_Quote class
 *
 * @return YITH_Request_Quote
 */
function YITH_Request_Quote() { //phpcs:ignore
	return YITH_Request_Quote::get_instance();
}
