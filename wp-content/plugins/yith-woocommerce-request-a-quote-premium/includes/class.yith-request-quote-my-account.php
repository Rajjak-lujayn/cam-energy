<?php //phpcs:ignore WordPress.Files.FileName.InvalidClassFileName
/**
 * Implements the YITH_Request_Quote_My_Account class.
 *
 * @class   YITH_Request_Quote_My_Account
 * @since   1.0.0
 * @author  YITH <plugins@yithemes.com>
 * @package YITH\RequestAQuote
 */

defined( 'ABSPATH' ) || exit;


if ( ! class_exists( 'YITH_Request_Quote_My_Account' ) ) {

	/**
	 * Class YITH_Request_Quote_My_Account
	 */
	class YITH_Request_Quote_My_Account {

		/**
		 * Single instance of the class
		 *
		 * @var YITH_Request_Quote_My_Account
		 */
		protected static $instance;


		/**
		 * Returns single instance of the class
		 *
		 * @return YITH_Request_Quote_My_Account
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
		 * @since  3.0.0
		 */
		public function __construct() {
			add_filter( 'woocommerce_account_menu_items', array( $this, 'add_quote_menu_item' ), 20 );
			add_filter( 'woocommerce_account_menu_item_classes', array( $this, 'set_quote_menu_active_on_view_quote' ), 10, 2 );

			add_action( 'woocommerce_account_view-quote_endpoint', array( $this, 'view_quote' ) );
			add_filter( 'woocommerce_endpoint_view-quote_title', array( $this, 'load_view_quote_page_title' ) );

			// my account list quotes.
			add_filter( 'woocommerce_my_account_my_orders_query', array( $this, 'my_account_my_orders_query' ) );
			add_action( 'woocommerce_account_quotes_endpoint', array( $this, 'view_quote_list' ), 1 );
			add_filter( 'woocommerce_endpoint_quotes_title', array( $this, 'load_quotes_page_title' ) );

			add_filter( 'woocommerce_order_data_store_cpt_get_orders_query', array( $this, 'select_only_quote' ), 10, 2 );
			add_action( 'init', array( $this, 'init' ), 30 );

		}

		/**
		 * Init method to set proteo icon.
		 */
		public function init() {
			if ( defined( 'YITH_PROTEO_VERSION' ) ) {
				add_filter( 'yith_proteo_myaccount_custom_icon', array( $this, 'customize_my_account_proteo_icon' ), 10, 2 );
			}
		}


		/**
		 * Change the icon inside my account on Proteo Theme.
		 *
		 * @param string $icon     Icon.
		 * @param string $endpoint Endpoint.
		 *
		 * @return string
		 */
		public function customize_my_account_proteo_icon( $icon, $endpoint ) {

			if ( YITH_Request_Quote()->endpoint === $endpoint ) {
				$icon = '<span class="yith-proteo-myaccount-icons ywraq-quote-icon-icon_quote lnr"></span>';
			}

			return $icon;
		}


		/**
		 * Add the menu item on WooCommerce My account Menu
		 * before the Logout item menu.
		 *
		 * @param array $wc_menu WooCommerce menu list.
		 *
		 * @return mixed
		 */
		public function add_quote_menu_item( $wc_menu ) {

			$user_type = get_option( 'ywraq_user_type' );

			if ( 'guest' === $user_type ) {
				return $wc_menu;
			}

			$user  = wp_get_current_user();
			$rules = (array) get_option( 'ywraq_user_role' );

			if ( ! empty( $rules ) && is_array( $rules ) && 'all' !== $user_type ) {
				$intersect = array_intersect( $user->roles, $rules );

				if ( empty( $intersect ) ) {
					return $wc_menu;
				}
			}

			$new_menu = array();
			foreach ( $wc_menu as $key => $endpoint ) {
				if ( apply_filters( 'ywraq_quote_menu_before_item', 'orders' ) === $key ) {
					$new_menu['quotes'] = get_option( 'ywraq_quote_endpoint_label', esc_html__( 'Quotes', 'yith-woocommerce-request-a-quote' ) );
				}
				$new_menu[ $key ] = $endpoint;
			}

			return $new_menu;
		}


		/**
		 * Active the quote menu inside the view quote page.
		 *
		 * @param array  $classes  Class list.
		 * @param string $endpoint Current item menu.
		 *
		 * @return array
		 */
		public function set_quote_menu_active_on_view_quote( $classes, $endpoint ) {
			global $wp;

			if ( YITH_Request_Quote()->endpoint === $endpoint && isset( $wp->query_vars['view-quote'] ) ) {
				array_push( $classes, 'is-active' );
			}

			return $classes;
		}

		/**
		 * Show the quote detail
		 *
		 * @since 1.0.0
		 */
		public function view_quote() {
			global $wp;
			if ( ! is_user_logged_in() ) {
				wc_get_template( 'myaccount/form-login.php' );
			} else {
				$view_quote = YITH_Request_Quote()->view_endpoint;
				$order_id   = $wp->query_vars[ $view_quote ];

				wc_get_template(
					'myaccount/view-quote.php',
					array(
						'order_id'     => $order_id,
						'current_user' => get_user_by( 'id', get_current_user_id() ),
					),
					false,
					YITH_YWRAQ_TEMPLATE_PATH . '/'
				);
			}
		}

		/**
		 * Show the quote list
		 *
		 * @since   3.0.0
		 */
		public function view_quote_list() {
			wc_get_template( 'myaccount/my-quotes.php', null, '', YITH_YWRAQ_TEMPLATE_PATH . '/' );
		}


		/**
		 * Change the title of the endpoint.
		 *
		 * @param string $title Title of the page.
		 *
		 * @return string
		 * @since 3.0.0
		 */
		public function load_view_quote_page_title( $title ) {
			global $wp;

			$view_quote = YITH_Request_Quote()->view_endpoint;
			$order_id   = ! empty( $wp->query_vars[ $view_quote ] ) ? $wp->query_vars[ $view_quote ] : 0;

			if ( ! $order_id ) {
				return $title;
			}

			/* translators: $%s quote number */

			return wp_kses_post( sprintf( esc_html__( 'Quote #%s', 'yith-woocommerce-request-a-quote' ), apply_filters( 'ywraq_quote_number', $order_id ) ) );
		}

		/**
		 * Change the title of the quotes list endpoint.
		 *
		 * @param string $title Title of the page.
		 *
		 * @return string
		 * @since 3.0.0
		 */
		public function load_quotes_page_title( $title ) {
			return get_option( 'ywraq_quote_endpoint_label', esc_html__( 'Quotes', 'yith-woocommerce-request-a-quote' ) );
		}

		/**
		 * Remove Quotes from Order query
		 *
		 * @param array $args .
		 *
		 * @return array
		 * @since  1.0.0
		 */
		public function my_account_my_orders_query( $args ) {
			$args['status'] = array_keys( array_diff( wc_get_order_statuses(), YITH_YWRAQ_Order_Request()->get_quote_order_status() ) );

			return $args;
		}


		/**
		 * Return the URL of quote list.
		 *
		 * @return string
		 */
		public function get_quotes_url() {
			$quotes = YITH_Request_Quote()->endpoint;

			return wc_get_endpoint_url( $quotes, '', wc_get_page_permalink( 'myaccount' ) );
		}

		/**
		 * Select only the quote
		 *
		 * @param array $query      .
		 * @param array $query_vars .
		 *
		 * @return array
		 */
		public function select_only_quote( $query, $query_vars ) {
			if ( ! empty( $query_vars['ywraq_raq'] ) ) {
				$query['meta_query'][] = array(
					'key'   => 'ywraq_raq',
					'value' => esc_attr( $query_vars['ywraq_raq'] ),
				);
			}

			return $query;
		}

	}
}

/**
 * Unique access to instance of YITH_Request_Quote_My_Account class
 *
 * @return YITH_Request_Quote_My_Account
 */
function YITH_Request_Quote_My_Account() { //phpcs:ignore
	return YITH_Request_Quote_My_Account::get_instance();
}

