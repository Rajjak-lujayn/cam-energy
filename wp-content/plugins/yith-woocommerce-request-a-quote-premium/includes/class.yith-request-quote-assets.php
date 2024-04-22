<?php //phpcs:ignore WordPress.Files.FileName.InvalidClassFileName
/**
 * Assets class. This is used to load script and styles.
 *
 * @package YITH\RequestAQuote
 * @since   3.0.0
 * @author  YITH <plugins@yithemes.com>
 */

defined( 'ABSPATH' ) || exit;


if ( ! class_exists( 'YITH_Request_Quote_Assets' ) ) {

	/**
	 * Class that handles the assets
	 *
	 * @class  YITH_Request_Quote_Assets
	 */
	class YITH_Request_Quote_Assets {

		/**
		 * Single instance of the class
		 *
		 * @var YITH_Request_Quote_Assets
		 */
		private static $instance;

		/**
		 * Singleton implementation
		 *
		 * @return YITH_Request_Quote_Assets
		 */
		public static function get_instance() {
			return ! is_null( self::$instance ) ? self::$instance : self::$instance = new self();
		}

		/**
		 * YITH_Request_Quote_Assets constructor.
		 */
		private function __construct() {

			if ( ywraq_is_admin() ) {
				add_action( 'admin_enqueue_scripts', array( $this, 'register_admin_scripts' ), 10 );
				add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ), 20 );
			} else {
				add_action( 'wp_enqueue_scripts', array( $this, 'register_frontend_scripts' ), 11 );
				add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_frontend_scripts' ), 11 );
			}

		}

		/**
		 * Return the suffix of script.
		 *
		 * @return string
		 */
		private function get_suffix() {

			$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

			return $suffix;
		}

		/**
		 * Register admin scripts
		 */
		public function register_admin_scripts() {
			$suffix = $this->get_suffix();
			$screen = get_current_screen();

			wp_register_script(
				'yith_ywraq_admin',
				YITH_YWRAQ_ASSETS_URL . '/js/yith-ywraq-admin' . $suffix . '.js',
				array(
					'jquery',
					'jquery-ui-dialog',
					'yith-plugin-fw-fields',
				),
				YITH_YWRAQ_VERSION,
				true
			);
			wp_localize_script(
				'yith_ywraq_admin',
				'yith_ywraq_admin',
				array(
					'ajaxurl'                     => admin_url( 'admin-ajax.php' ),
					'required_label'              => _x( 'The field is required', 'Error displayed when the field is empty', 'yith-woocommerce-request-a-quote' ),
					'is_raq_panel'                => isset( $_GET['page'] ) && 'yith_woocommerce_request_a_quote' === $_GET['page'], //phpcs:ignore
					'bulk_delete_confirm_title'   => __( 'Confirm delete', 'yith-woocommerce-request-a-quote' ),
					'bulk_delete_confirm_message' => __( 'Are you sure you want to delete the selected items?', 'yith-woocommerce-request-a-quote' ) . '<br /><br />' . __( 'This action cannot be undone and you will not be able to recover this data.', 'yith-woocommerce-request-a-quote' ),
					'bulk_delete_confirm_button'  => _x( 'Yes, delete', 'Delete confirmation action', 'yith-woocommerce-request-a-quote' ),
					'bulk_delete_cancel_button'   => __( 'No', 'yith-woocommerce-request-a-quote' ),
				)
			);
			wp_register_style( 'yith_ywraq_backend', YITH_YWRAQ_ASSETS_URL . '/css/ywraq-backend.css', '', YITH_YWRAQ_VERSION );

			wp_register_script( 'yith_ywraq_pdf_panel', YITH_YWRAQ_ASSETS_URL . '/js/ywraq-pdf-template-panel' . $suffix . '.js', array( 'yith_ywraq_admin', 'jquery-blockui' ), YITH_YWRAQ_VERSION, true );
			wp_register_script( 'yith_ywraq_pdf_templates', YITH_YWRAQ_URL . 'dist/templates/index.js', false, YITH_YWRAQ_VERSION, true );

			wp_register_script( 'ywraq_exclusion_list', YITH_YWRAQ_ASSETS_URL . '/js/ywraq-exclusion-list' . $suffix . '.js', array( 'jquery', 'jquery-ui-dialog', 'yith-plugin-fw-fields', ), YITH_YWRAQ_VERSION, true );

			wp_localize_script(
				'ywraq_exclusion_list',
				'ywraq_exclusion_list',
				array(
					'ajaxurl'         => admin_url( 'admin-ajax.php' ),
					'delete_nonce'    => wp_create_nonce( 'yith_ywraq_delete_exclusions' ),
					'popup_add_title' => __( 'Add exclusion in list', 'yith-woocommerce-request-a-quote' ),
					'save'            => __( 'Add exclusion to list', 'yith-woocommerce-request-a-quote' ),
					'confirmChoice'   => esc_html_x( 'Continue', 'Label button of a dialog popup', 'yith-woocommerce-request-a-quote' ),
					'cancel'          => esc_html_x( 'Cancel', 'Label button of a dialog popup', 'yith-woocommerce-request-a-quote' ),
				)
			);

			wp_register_style( 'ywraq_exclusion_list', YITH_YWRAQ_ASSETS_URL . '/css/ywraq-exclusion-list.css', '', YITH_YWRAQ_VERSION );

			wp_register_style( 'yith_ywraq_frontend', YITH_YWRAQ_ASSETS_URL . '/css/ywraq-frontend.css', array(), YITH_YWRAQ_VERSION );

			if ( function_exists( 'wp_set_script_translations' ) ) {
				wp_set_script_translations( 'yith_ywraq_pdf_templates', 'yith-woocommerce-request-a-quote', YITH_YWRAQ_DIR . 'languages' );
			}

		}

		/**
		 * Register frontend scripts
		 */
		public function register_frontend_scripts() {
			$suffix = $this->get_suffix();
			wp_register_script( 'ywraq-password-strength', YITH_YWRAQ_ASSETS_URL . '/js/frontend-password' . $suffix . '.js', array( 'jquery', 'password-strength-meter' ), YITH_YWRAQ_VERSION, true );
			wp_register_script( 'yith_ywraq_frontend', YITH_YWRAQ_ASSETS_URL . '/js/frontend' . $suffix . '.js', array( 'jquery', 'jquery-ui-dialog' ), YITH_YWRAQ_VERSION, true );
			wp_register_script( 'yith_ywraq_cart', YITH_YWRAQ_ASSETS_URL . '/js/ywraq-cart-checkout' . $suffix . '.js', array( 'jquery', 'jquery-ui-dialog' ), YITH_YWRAQ_VERSION, true );
			wp_register_script( 'yith_ywraq_my-account', YITH_YWRAQ_ASSETS_URL . '/js/ywraq-my-account' . $suffix . '.js', array( 'jquery', 'jquery-ui-dialog' ), YITH_YWRAQ_VERSION, true );

			wp_register_style( 'yith_ywraq_frontend', YITH_YWRAQ_ASSETS_URL . '/css/ywraq-frontend.css', array(), YITH_YWRAQ_VERSION );
			wp_register_style( 'yith_ywraq_my-account', YITH_YWRAQ_ASSETS_URL . '/css/ywraq-my-account.css', array( 'yith_ywraq_frontend' ), YITH_YWRAQ_VERSION );

			// Localize password strength.
			wp_localize_script(
				'ywraq-password-strength',
				'ywraq_pwd',
				array(
					'min_password_strength' => apply_filters( 'woocommerce_min_password_strength', 3 ),
					'i18n_password_error'   => esc_attr__( 'Please enter a stronger password.', 'woocommerce' ),
					'i18n_password_hint'    => esc_attr( wp_get_password_hint() ),
				)
			);

			// Localize frontend script.
			$default_loader       = ywraq_get_ajax_default_loader();
			$loader               = 'default' === get_option( 'ywraq_loader_style', 'default' ) ? $default_loader : get_option( 'ywraq_loader_image', $default_loader );
			$localize_script_args = array(
				'ajaxurl'                             => WC_AJAX::get_endpoint( '%%endpoint%%' ),
				'current_lang'                        => ywraq_get_current_language(),
				'no_product_in_list'                  => ywraq_get_list_empty_message(),
				'block_loader'                        => $loader,
				'go_to_the_list'                      => ( get_option( 'ywraq_after_click_action' ) === 'yes' ) ? 'yes' : 'no',
				'rqa_url'                             => YITH_Request_Quote()->get_redirect_page_url(),
				'current_user_id'                     => is_user_logged_in() ? get_current_user_id() : '',
				'hide_price'                          => get_option( 'ywraq_hide_price' ) === 'yes' ? 1 : 0,
				'allow_out_of_stock'                  => ywraq_allow_raq_out_of_stock(),
				'allow_only_on_out_of_stock'          => 'only' === get_option( 'ywraq_button_out_of_stock', 'hide' ),
				'select_quantity'                     => apply_filters( 'yith_ywraq_select_quantity_grouped_label', __( 'Set at least the quantity for a product', 'yith-woocommerce-request-a-quote' ) ),
				'i18n_choose_a_variation'             => apply_filters( 'yith_ywraq_select_variations_label', esc_attr__( 'Please select some product options before adding this product to your quote list.', 'yith-woocommerce-request-a-quote' ) ),
				'i18n_out_of_stock'                   => apply_filters( 'yith_ywraq_variation_outofstock_label', esc_attr__( 'This Variation is Out of Stock, please select another one.', 'yith-woocommerce-request-a-quote' ) ),
				'raq_table_refresh_check'             => apply_filters( 'yith_ywraq_table_refresh_check', true ),
				'auto_update_cart_on_quantity_change' => apply_filters( 'yith_ywraq_auto_update_cart_on_quantity_change', true ),
				'enable_ajax_loading'                 => get_option( 'ywraq_enable_ajax_loading', 'no' ) === 'yes' ? 1 : 0,
				'widget_classes'                      => apply_filters( 'yith_ywraq_widget_classes', '.widget_ywraq_list_quote, .widget_ywraq_mini_list_quote' ),
				'show_form_with_empty_list'           => get_option( 'ywraq_show_form_with_empty_list', 'no' ) === 'yes' ? 1 : 0,
				'mini_list_widget_popup'              => apply_filters( 'ywraq_mini_list_widget_popup', true ),
				'isCheckout'                          =>  is_checkout(),
				'showButtonOnCheckout'                => YITH_YWRAQ_Frontend()->check_if_show_button_on_checkout(),
				'buttonOnCheckoutStyle'               => get_option( 'ywraq_raq_checkout_button_style', 'button' ),  // button or link
				'buttonOnCheckoutLabel'               => get_option( 'ywraq_checkout_quote_button_label', __( 'or ask for a quote', 'yith-woocommerce-request-a-quote' ) )
			);

			wp_localize_script( 'yith_ywraq_frontend', 'ywraq_frontend', apply_filters( 'yith_ywraq_frontend_localize', $localize_script_args ) );

		}

		/**
		 * Enqueue admin scripts
		 */
		public function enqueue_admin_scripts() {

			// load the script in selected pages.
			global $current_screen, $pagenow;
			$request             = $_REQUEST;//phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$current_screen_base = $current_screen->base ?? false;

			$post_id = $request['post'] ?? $request['post_ID'] ?? 0;;
			$post = get_post( $post_id );

			if ( ywraq_check_valid_admin_page( YITH_YWRAQ_Post_Types::$pdf_template ) || ( 'admin.php' === $pagenow && isset( $request['page'] ) && 'yith_woocommerce_request_a_quote' === $request['page'] ) || ( $post && 'shop_order' === $post->post_type ) || ( 'post-new.php' === $pagenow && isset( $request['post_type'] ) && 'shop_order' === $request['post_type'] ) || 'woocommerce_page_wc-orders' === $current_screen_base ) {

				if ( ! wp_script_is( 'selectWoo' ) ) {
					wp_enqueue_script( 'selectWoo' );
					wp_enqueue_style( 'select2' );
				}

				wp_enqueue_script( 'yith_ywraq_admin' );
				wp_enqueue_style( 'yith_ywraq_backend' );
				wp_enqueue_style( 'yith-ywraq-gutenberg' );
				wp_localize_script(
					'yith_ywraq_admin',
					'ywraq_admin',
					array(
						'default_form_submit_label' => __( 'Add field to form', 'yith-woocommerce-request-a-quote' ),
						'enabled'                   => '<span class="status-enabled tips" data-tip="' . __( 'Yes', 'yith-woocommerce-request-a-quote' ) . '"></span>',
						'ajax_url'                  => admin_url( 'admin-ajax.php' ),
					)
				);
			}

			if ( 'woocommerce_page_wc-orders' === $current_screen_base || $post && 'shop_order' === $post->post_type ) {
				wp_enqueue_media();

				wp_enqueue_style( 'woocommerce_admin_styles' );

				wp_enqueue_style( 'yith-plugin-fw-fields' );
				wp_enqueue_style( 'wp-color-picker' );
				wp_enqueue_style( 'yit-plugin-metaboxes' );
				wp_enqueue_style( 'jquery-ui-style' );

				wp_enqueue_script( 'yit-metabox' );
				wp_enqueue_script( 'yith-plugin-fw-fields' );
			}

			if ( 'admin.php' === $pagenow && isset( $request['section'] )
			     && in_array( $request['section'], array( 'yith_ywraq_send_quote_reminder', 'yith_ywraq_send_quote_reminder_accept' ), true ) ) {
				wp_enqueue_script( 'yith_ywraq_admin' );
			}
			if ( ywraq_check_valid_admin_page( YITH_YWRAQ_Post_Types::$pdf_template ) ) {

				wp_enqueue_script( 'yith_ywraq_pdf_panel' );
				if ( 'edit.php' !== $pagenow ) {
					wp_enqueue_script( 'yith_ywraq_pdf_templates' );
				}
			}

			if ( ywraq_is_elementor_editor() ) {
				wp_enqueue_style( 'yith_ywraq_frontend' );
			}
		}

		/**
		 * Enqueue frontend scripts
		 */
		public function enqueue_frontend_scripts() {

			if ( ! apply_filters( 'ywraq_load_assets', true ) ) {
				return;
			}

			global $post;

			$raq_page_id = (int) YITH_Request_Quote()->get_raq_page_id();

			// Styles and scripts in request a quote page.
			if ( $post && $post->ID === $raq_page_id ) {
				// if the registration user is requested.
				if ( 'none' !== get_option( 'ywraq_user_registration', 'none' ) && 'yes' !== get_option( 'woocommerce_registration_generate_password' ) ) {
					wp_enqueue_script( 'ywraq-password-strength' );

				}
			}

			if ( is_cart() || is_checkout() ) {
				wp_enqueue_script( 'yith_ywraq_cart' );
			}

			if ( is_account_page() ) {
				wp_enqueue_script( 'jquery-ui-dialog' );
				wp_enqueue_style( 'yith_ywraq_my-account' );
				wp_enqueue_script( 'yith_ywraq_my-account' );

			}
			wp_enqueue_script( 'jquery-ui-dialog' );
			wp_enqueue_style( 'yith_ywraq_frontend' );
			wp_enqueue_script( 'yith_ywraq_frontend' );

			$custom_css = require_once YITH_YWRAQ_TEMPLATE_PATH . '/layout/css.php';
			wp_add_inline_style( 'yith_ywraq_frontend', $custom_css );

			if ( function_exists( 'Woo_Bulk_Discount_Plugin_t4m' ) ) {
				remove_filter( 'woocommerce_cart_product_subtotal', array( Woo_Bulk_Discount_Plugin_t4m(), 'filter_cart_product_subtotal' ), 10 );
			}

			if ( isset( $_REQUEST['hidem'] ) ) {
				wp_enqueue_script( 'yith_ywraq_my-account' );
				wp_enqueue_style( 'yith_ywraq_my-account' );
			}

			YITH_YWRAQ_Frontend()->hide_add_to_cart_single();

			wp_dequeue_style( 'yith-ywraq-gutenberg' );

		}
	}
}


/**
 * Unique access to instance of YITH_Request_Quote_Assets class
 *
 * @return YITH_Request_Quote_Assets
 */
function YITH_Request_Quote_Assets() { //phpcs:ignore
	return YITH_Request_Quote_Assets::get_instance();
}

YITH_Request_Quote_Assets();
