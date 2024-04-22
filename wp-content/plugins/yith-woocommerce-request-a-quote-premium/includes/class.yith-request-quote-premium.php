<?php //phpcs:ignore WordPress.Files.FileName.InvalidClassFileName
/**
 * Implements the YITH_Request_Quote_Premium class.
 *
 * @class   YITH_Request_Quote_Premium
 * @since   1.0.0
 * @package YITH\RequestAQuote
 * @author  YITH <plugins@yithemes.com>
 */

defined( 'ABSPATH' ) || exit;

use Mpdf\Mpdf;


if ( ! class_exists( 'YITH_Request_Quote_Premium' ) ) {

	/**
	 * Class YITH_Request_Quote_Premium
	 */
	class YITH_Request_Quote_Premium extends YITH_Request_Quote {

		/**
		 * Locale
		 *
		 * @var bool
		 */
		private $locale = false;

		/**
		 * Returns single instance of the class
		 *
		 * @return YITH_Request_Quote_Premium
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

			parent::__construct();

			$this->_run();

			// register widget.
			add_action( 'widgets_init', array( $this, 'register_widgets' ) );
			add_filter( 'woocommerce_email_classes', array( $this, 'add_woocommerce_emails' ) );
			$cron_time = get_option( 'ywraq_cron_time' );
			$cron_time = isset( $cron_time['time'] ) ? $cron_time['time'] : '0';
			if ( 'yes' === get_option( 'ywraq_enable_pdf', 'yes' ) && ( '0' == $cron_time || defined( 'DOING_CRON' ) || ywraq_is_admin() || ( isset( $_REQUEST['ywraq_checkout_quote'] ) && 'true' === $_REQUEST['ywraq_checkout_quote'] ) ) ) {//phpcs:ignore
				add_action( 'create_pdf', array( $this, 'generate_pdf' ), 99, 2 );
				add_action( 'yith_ywraq_quote_template_header', array( $this, 'pdf_header' ), 10, 1 );
				add_action( 'yith_ywraq_quote_template_footer', array( $this, 'pdf_footer' ), 10, 1 );
				add_action( 'yith_ywraq_quote_template_content', array( $this, 'pdf_content' ), 10, 1 );
				add_filter( 'plugin_locale', array( $this, 'set_locale_for_pdf' ), 10, 2 );
			}

			if ( ywraq_is_admin() ) {
				add_action( 'init', array( $this, 'set_plugin_requirements' ), 20 );
			} else {

				// show button in shop page.
				add_action( 'woocommerce_after_shop_loop_item', array( $this, 'add_button_shop' ), 15 );
				add_filter( 'yith_ywraq_hide_price_template', array( $this, 'show_product_price' ), 0, 2 );

				if ( ! catalog_mode_plugin_enabled() ) {
					add_filter( 'woocommerce_get_price_html', array( $this, 'show_product_price' ), 0 );
					add_filter( 'woocommerce_get_variation_price_html', array( $this, 'show_product_price' ), 0 );
				}

				// check user type.
				add_filter( 'yith_ywraq_before_print_button', array( $this, 'must_be_showed' ), 10, 2 );
				add_filter( 'yith_ywraq_before_print_widget', array( $this, 'raq_page_check_user' ) );
				add_filter( 'yith_ywraq_before_print_my_account_my_quotes', array( $this, 'raq_page_check_user' ) );
				add_filter( 'yith_ywraq_before_print_raq_page', array( $this, 'raq_page_check_user' ) );
				add_filter( 'yith_ywraq_raq_page_deniend_access', array( $this, 'raq_page_denied_access' ) );
			}

			$cron_time = get_option( 'ywraq_cron_time' );
			$cron_time = isset( $cron_time['time'] ) ? $cron_time['time'] : '0';
			if ( 'yes' === get_option( 'ywraq_automate_send_quote' ) && '0' === $cron_time ) {
				add_action(
					'ywraq_after_create_order_from_checkout',
					array(
						$this,
						'send_the_quote_automatically',
					),
					10,
					2
				);
				add_action( 'ywraq_after_create_order', array( $this, 'send_the_quote_automatically' ), 10, 2 );
			}

			add_filter( 'pre_option_ywraq_show_preview', array( $this, 'override_ywraq_show_preview_option' ), 10, 1 );
			add_filter( 'option_ywraq_expired_time', array( $this, 'override_ywraq_expired_time' ), 10, 1 );

			// quote button to checkout.
			if ( 'yes' === get_option( 'ywraq_show_button_on_checkout_page', 'no' ) ) {
				// add the gateway.
				require_once YITH_YWRAQ_INC . 'class.yith-request-quote-gateway.php';
				add_filter( 'woocommerce_payment_gateways', array( $this, 'add_ywraq_gateway' ) );
				add_filter( 'woocommerce_email_enabled_new_order', array( $this, 'disable_new_order_email' ), 20, 2 );

			}

			if ( 'yes' === get_option( 'ywraq_show_button_on_cart_page', 'no' ) ) {
				require_once YITH_YWRAQ_INC . 'class-yith-request-quote-cart.php';
				yith_wraq_cart();
			}

			if ( isset( $_GET['ywraq_debug_pdf'] ) ) { //phpcs:ignore
				add_action( 'init', array( $this, 'debug_pdf' ), 30 );
			}

			add_action( 'template_redirect', array( $this, 'wc_blocks_hooks' ), 10 );

		}


		/**
		 * Check if WC is using the block to show the add to quote button
		 *
		 * @since 4.16.0
		 */
		public function wc_blocks_hooks() {


			if ( ( is_shop() || is_product_taxonomy() ) && ( has_block( 'post-template' ) && has_block( 'woocommerce/product-button' ) ) )  {
				add_filter( 'render_block_woocommerce/product-button', array( $this, 'add_button_shop_block' ), 10, 2 );
				remove_action( 'woocommerce_after_shop_loop_item', array( $this, 'add_button_shop' ), 15 );
			}



			if ( is_single() && ywraq_is_using_block_template_in_single_product() ) {
				global $post;

				$product                      = wc_get_product( $post->ID );
				$show_button_near_add_to_cart = get_option( 'ywraq_show_button_near_add_to_cart', 'no' );

				if ( yith_plugin_fw_is_true( $show_button_near_add_to_cart ) && $product && $product->is_in_stock() && $product->get_price() !== '' ) {
					add_action( 'woocommerce_after_add_to_cart_button', array( YITH_YWRAQ_Frontend(), 'add_button_single_page' ), 10 );
				} else {
					add_filter( 'render_block_woocommerce/add-to-cart-form', array( $this, 'add_button_single_block' ), 10, 2 );
				}

                // to add the button on related product.
				add_filter( 'render_block_woocommerce/product-button', array( $this, 'add_button_shop_block' ), 10, 2 );
			}
		}

		/**
		 * Concat the quote button to the product button
		 *
		 * @since 4.16.0
		 */
		public function add_button_shop_block( $content, $b ) {
			ob_start();
			$this->add_button_shop();
			$new_content = ob_get_clean();

			return $content . $new_content;

		}

		/**
		 * Concat the quote button to the product button
		 *
		 * @since 4.16.0
		 */
		public function add_button_single_block( $content, $b ) {
			ob_start();
			YITH_YWRAQ_Frontend()->add_button_single_page();
			$new_content = ob_get_clean();

			return $content . $new_content;

		}

		/**
		 * Useful to debug the pdf template.
		 * Inside the query string add ?ywraq_debug_pdf=xxx replacing xxx with the number of the order.
		 *
		 * @since 3.1.4
		 */
		public function debug_pdf() {
			$order = sanitize_text_field( wp_unslash( $_GET['ywraq_debug_pdf'] ) ); //phpcs:ignore
			$this->generate_pdf( $order );
		}

		/**
		 * Include files and classes for the premium version.
		 *
		 * @since  2.0
		 */
		private function _run() { //phpcs:ignore

			include_once YITH_YWRAQ_INC . 'functions.yith-ywraq-updates.php';
			// widget.
			require_once YITH_YWRAQ_INC . 'widgets/class.yith-ywraq-list-quote-widget.php';
			require_once YITH_YWRAQ_INC . 'widgets/class.yith-ywraq-mini-list-quote-widget.php';

			// Load class and functions of default form.
			require_once YITH_YWRAQ_INC . 'forms/default/class.yith-ywraq-default-form.php';
			require_once YITH_YWRAQ_INC . 'forms/default/functions.yith-ywraq-default-form.php';

			// privacy.
			require_once YITH_YWRAQ_INC . 'class.yith-request-quote-privacy.php';
			require_once YITH_YWRAQ_INC . 'class.yith-ywraq-order-request.php';

			// Gutenberg.
			include_once YITH_YWRAQ_INC . 'builders/class-ywraq-pdf-template-builder.php';
			include_once YITH_YWRAQ_INC . 'builders/gutenberg/class-ywraq-gutenberg.php';

			if ( ywraq_is_admin() ) {
				require_once YITH_YWRAQ_INC . 'class.yith-ywraq-exclusions-handler.php';
				YITH_YWRAQ_Exclusions_Handler();
			}

			$this->_plugin_integrations();
			$this->_form_integrations();

		}

		/**
		 * Include the files and the classes if necessary.
		 *
		 * @since  2.0
		 */
		private function _plugin_integrations() { //phpcs:ignore

			if ( class_exists( 'YITH_Vendors' ) ) {
				require_once YITH_YWRAQ_INC . 'compatibility/yith-woocommerce-product-vendors.php';
			}

			if ( class_exists( 'YITH_WAPO' ) ) {
				require_once YITH_YWRAQ_INC . 'compatibility/yith-woocommerce-advanced-product-options.php';
			}

			if ( class_exists( 'YITH_WCP' ) ) {
				require_once YITH_YWRAQ_INC . 'compatibility/yith-woocommerce-composite-products.php';
			}

			if ( class_exists( 'YITH_WCDP' ) ) {
				require_once YITH_YWRAQ_INC . 'compatibility/yith-woocommerce-deposits-and-down-payments.php';
			}

			if ( defined( 'YITH_WCMCS_VERSION' ) ) {
				require_once YITH_YWRAQ_INC . 'compatibility/yith-multi-currency-switcher.php';
			}
		}

		/**
		 * Include the files and the classes if necessary.
		 *
		 * @since  2.0
		 */
		private function _form_integrations() { //phpcs:ignore

			$form_type = get_option( 'ywraq_inquiry_form_type', 'default' );

			if ( ywraq_cf7_form_installed() && ( is_admin() || 'contact-form-7' === $form_type ) ) {
				require_once YITH_YWRAQ_INC . 'forms/contact-form-7/class.yith-ywraq-contact-form-7.php';
				require_once YITH_YWRAQ_INC . 'forms/contact-form-7/functions.yith-ywraq-contact-form-7.php';
				YITH_YWRAQ_Contact_Form_7();
			}

			if ( ywraq_yit_contact_form_installed() && ( is_admin() || 'yit-contact-form' === $form_type ) ) {
				require_once YITH_YWRAQ_INC . 'forms/yit-contact-form/class.yith-ywraq-yit-contact-form.php';
				YITH_YWRAQ_YIT_Contact_Form();
			}

			if ( ywraq_gravity_form_installed() && ( is_admin() || 'gravity-forms' === $form_type ) ) {
				require_once YITH_YWRAQ_INC . 'forms/gravity-forms/ywraq-gravity-form-addons.php';
				YWRAQ_Gravity_Forms_Add_On();
			}

			if ( ywraq_ninja_forms_installed() && ( is_admin() || 'ninja-forms' === $form_type ) ) {
				require_once YITH_YWRAQ_INC . 'forms/ninja-forms/ywraq-ninja-forms.php';
				YWRAQ_Ninja_Forms();
			}

			if ( ywraq_wpforms_installed() && ( is_admin() || 'wpforms' === $form_type ) ) {
				require_once YITH_YWRAQ_INC . 'forms/wpforms/ywraq-wpforms.php';
				YWRAQ_WPForms();
			}

			if ( is_admin() || 'default' === $form_type ) {
				YITH_YWRAQ_Default_Form();
			}
			/**
			 * DO_ACTION:ywraq_form_integration
			 *
			 * This action is triggered after the Request a Quote form integration
			 */
			do_action( 'ywraq_form_integration' );
		}

		/**
		 * Add the quote button in other pages is the product is simple
		 *
		 * @return  boolean|void
		 * @since   1.0.0
		 */
		public function add_button_shop() {

			$show_button = apply_filters( 'yith_ywraq-btn_other_pages', true ); //phpcs:ignore


			global $product;

			if ( ! $product ) {
				return false;
			}

			$type_in_loop = apply_filters(
				'yith_ywraq_show_button_in_loop_product_type',
				array(
					'simple',
					'subscription',
					'external',
					'yith-composite',
				)
			);

			if ( ! yith_plugin_fw_is_true( $show_button ) || ! $product->is_type( $type_in_loop ) ) {
				return false;
			}

			if ( ! function_exists( 'YITH_YWRAQ_Frontend' ) ) {
				require_once YITH_YWRAQ_INC . 'class.yith-request-quote-frontend.php';
				YITH_YWRAQ_Frontend();
			}

			YITH_YWRAQ_Frontend()->print_button( $product );
		}

		/**
		 * Check for which users will not see the price
		 *
		 * @param float $price      .
		 * @param bool  $product_id .
		 *
		 * @return string
		 *
		 * @since   1.0.0
		 */
		public function show_product_price( $price, $product_id = false ) {

			$hide_price = get_option( 'ywraq_hide_price' ) === 'yes';

			if ( catalog_mode_plugin_enabled() ) {
				global $YITH_WC_Catalog_Mode; //phpcs:ignore
				$hide_price = $YITH_WC_Catalog_Mode->check_product_price_single( true, $product_id ); //phpcs:ignore

				if ( $hide_price && '' !== get_option( 'ywctm_exclude_price_alternative_text' ) ) {
					$hide_price = false;
					$price      = get_option( 'ywctm_exclude_price_alternative_text' );
				}
			} elseif ( $hide_price && current_filter() === 'woocommerce_get_price_html' ) {
				$price = '';
			} elseif ( $hide_price && ! catalog_mode_plugin_enabled() && ! ( defined( 'DOING_AJAX' ) && DOING_AJAX ) && current_filter() !== 'woocommerce_get_price_html' ) {
				ob_start();

				$args = array(
					'.single_variation_wrap .single_variation',
				);

				$classes = implode( ', ', apply_filters( 'ywcraq_catalog_price_classes', $args ) );

				?>
				<style>
					<?php
						echo esc_attr( $classes );
					?>
					{
						display: none !important
					}

				</style>
				<?php
				echo ob_get_clean(); //phpcs:ignore
			}

			return ( $hide_price ) ? '' : $price;

		}

		/**
		 * Add metabox in the product editor
		 *
		 * @return  void
		 * @since   1.0.0
		 * @deprecated
		 */
		public function add_metabox() {

		}

		/**
		 * Check if the product is in the exclusion list
		 *
		 * @param string $value    .
		 * @param int    $post_id  .
		 * @param string $meta_key .
		 * @param bool   $single   .
		 *
		 * @return mixed
		 * @deprecated
		 */
		public function get_exclusion_value( $value, $post_id, $meta_key, $single ) {
			return $value;
		}

		/**
		 * Add or Remove the products in the exclusion list
		 *
		 * @param int     $post_id .
		 * @param WP_Post $post    .
		 *
		 * @depracated
		 */
		public function save_metabox_info( $post_id, $post ) {

		}

		/**
		 * Register the widgets
		 *
		 * @return  void
		 * @since   1.0.0
		 */
		public function register_widgets() {
			register_widget( 'YITH_YWRAQ_List_Quote_Widget' );
			register_widget( 'YITH_YWRAQ_Mini_List_Quote_Widget' );
		}

		/**
		 * Refresh the quote list in the widget when a product is added or removed from the list
		 *
		 * @return  void
		 * @since   1.0.0
		 */
		public function ajax_refresh_quote_list() {
			$raq_content  = YITH_Request_Quote()->get_raq_return();
			$posted       = $_POST; //phpcs:ignore WordPress.Security.NonceVerification.Missing
			$args         = array(
				'raq_content'       => $raq_content,
				'template_part'     => 'view',
				'title'             => isset( $posted['title'] ) ? $posted['title'] : '',
				'item_plural_name'  => isset( $posted['item_plural_name'] ) ? $posted['item_plural_name'] : '',
				'item_name'         => isset( $posted['item_name'] ) ? $posted['item_name'] : '',
				'button_label'      => isset( $posted['button_label'] ) ? $posted['button_label'] : '',
				'show_title_inside' => isset( $posted['show_title_inside'] ) ? $posted['show_title_inside'] : 1,
				'show_thumbnail'    => isset( $posted['show_thumbnail'] ) ? $posted['show_thumbnail'] : 1,
				'show_price'        => isset( $posted['show_price'] ) ? $posted['show_price'] : 1,
				'show_quantity'     => isset( $posted['show_quantity'] ) ? $posted['show_quantity'] : 1,
				'show_variations'   => isset( $posted['show_variations'] ) ? $posted['show_variations'] : 1,
				'open_quote_page'   => isset( $posted['open_quote_page'] ) ? $posted['open_quote_page'] : 1,
				'widget_type'       => isset( $posted['widget_type'] ) ? $posted['widget_type'] : '',
			);
			$args['args'] = $args;

			wp_send_json(
				array(
					'large' => wc_get_template_html( 'widgets/quote-list.php', $args, '', YITH_YWRAQ_TEMPLATE_PATH . '/' ),
					'mini'  => wc_get_template_html( 'widgets/mini-quote-list.php', $args, '', YITH_YWRAQ_TEMPLATE_PATH . '/' ),
				)
			);

			die();
		}

		/**
		 * Refresh the number of items for the shortcode [yith_ywraq_number_items]
		 *
		 * @return  void
		 * @since   1.7.8
		 */
		public function ajax_refresh_number_items() {

			$posted = $_POST; //phpcs:ignore WordPress.Security.NonceVerification.Missing
			$atts   = array(
				'show_url'         => $posted['show_url'],
				'item_name'        => $posted['item_name'],
				'item_plural_name' => $posted['item_plural_name'],
			);

			echo YITH_YWRAQ_Frontend()->shortcodes->ywraq_number_items( $atts ); //phpcs:ignore
		}

		/**
		 * Update the request a quote button to prevent caches
		 */
		public function ajax_update_ywraq_fragments() {

			$response = array(
				'error' => __( 'Error: Invalid request. Try again!', 'yith-woocommerce-request-a-quote' ),
			);

			if ( ! empty( $_POST['fragments'] ) ) { //phpcs:ignore
				$updated_fragments = array();

				$fragments = wp_unslash( $_POST['fragments'] ); //phpcs:ignore
				if ( $fragments ) {
					foreach ( $fragments as $fragment ) {
						$updated_fragments[ $fragment ] = do_shortcode( '[yith_ywraq_button_quote product=' . $fragment . ']' );
					}

					$response = array(
						'success'   => true,
						'fragments' => $updated_fragments,
					);
				}
			}

			wp_send_json( apply_filters( 'yith_ywraq_ajax_update_fragments_json', $response ) );
		}

		/**
		 * Loads the inquiry form
		 *
		 * @param array $args .
		 *
		 * @since 1.0
		 */
		public function get_inquiry_form( $args ) {

			$shortcode = '';

			switch ( get_option( 'ywraq_inquiry_form_type', 'default' ) ) {
				case 'yit-contact-form':
					$shortcode = '[contact_form name="' . get_option( 'ywraq_inquiry_yit_contact_form_id' ) . '"]';
					break;
				case 'contact-form-7':
					$shortcode = ywraq_cf7_form_installed() ? YITH_YWRAQ_Contact_Form_7()->get_shortcode_form() : '';
					break;
				case 'gravity-forms':
					$shortcode = ywraq_gravity_form_installed() ? YWRAQ_Gravity_Forms_Add_On()->get_shortcode_form() : '';
					break;
				case 'wpforms':
					$shortcode = ywraq_wpforms_installed() ? YWRAQ_WPForms()->get_shortcode_form() : '';
					break;
				case 'ninja-forms':
					$shortcode = ywraq_ninja_forms_installed() ? YWRAQ_Ninja_Forms()->get_shortcode_form() : '';
					break;
				case 'default':
					YITH_YWRAQ_Default_Form()->get_form_template( $args );
					break;
			}

			echo is_callable( 'apply_shortcodes' ) ? apply_shortcodes( $shortcode ) : do_shortcode( $shortcode ); //phpcs:ignore

		}

		/**
		 * Return the form by type and id.
		 *
		 * @param string $type    .
		 * @param string $form_id .
		 */
		public function get_inquiry_form_by_type( $type, $form_id = '' ) {
			$shortcode = '';

			switch ( $type ) {
				case 'contact-form-7':
					$shortcode = ywraq_cf7_form_installed() ? YITH_YWRAQ_Contact_Form_7()->get_shortcode_form_by_id( $form_id ) : '';
					break;
				case 'gravity-forms':
					$shortcode = ywraq_gravity_form_installed() ? YWRAQ_Gravity_Forms_Add_On()->get_shortcode_form_by_id( $form_id ) : '';
					break;
				case 'wpforms':
					$shortcode = ywraq_wpforms_installed() ? YWRAQ_WPForms()->get_shortcode_form_by_id( $form_id ) : '';
					break;
				case 'ninja-forms':
					$shortcode = ywraq_ninja_forms_installed() ? YWRAQ_Ninja_Forms()->get_shortcode_form_by_id( $form_id ) : '';
					break;
				case 'default':
					YITH_YWRAQ_Default_Form()->get_form_template( array() );
					break;
			}

			echo is_callable( 'apply_shortcodes' ) ? apply_shortcodes( $shortcode ) : do_shortcode( $shortcode ); //phpcs:ignore
		}

		/**
		 * Filters woocommerce available mails, to add wishlist related ones
		 *
		 * @param array $emails .
		 *
		 * @return array
		 * @since 1.0
		 */
		public function add_woocommerce_emails( $emails ) {
			if ( ! isset( $emails['YITH_YWRAQ_Send_Email_Request_Quote'] ) ) {
				$emails['YITH_YWRAQ_Send_Email_Request_Quote'] = include YITH_YWRAQ_INC . 'emails/class.yith-ywraq-send-email-request-quote.php';
			}
			$emails['YITH_YWRAQ_Send_Email_Request_Quote_Customer'] = include YITH_YWRAQ_INC . 'emails/class.yith-ywraq-send-email-request-quote-customer.php';
			$emails['YITH_YWRAQ_Quote_Status']                      = include YITH_YWRAQ_INC . 'emails/class.yith-ywraq-quote-status.php';
			$emails['YITH_YWRAQ_Send_Quote']                        = include YITH_YWRAQ_INC . 'emails/class.yith-ywraq-send-quote.php';
			$emails['YITH_YWRAQ_Send_Quote_Reminder']               = include YITH_YWRAQ_INC . 'emails/class.yith-ywraq-send-quote-reminder.php';
			$emails['YITH_YWRAQ_Send_Quote_Reminder_Accept']        = include YITH_YWRAQ_INC . 'emails/class.yith-ywraq-send-quote-reminder-accept.php';

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
			add_action( 'send_raq_customer_mail', array( 'WC_Emails', 'send_transactional_email' ), 10 );
			add_action( 'send_quote_mail', array( 'WC_Emails', 'send_transactional_email' ), 10 );
			add_action( 'change_status_mail', array( 'WC_Emails', 'send_transactional_email' ), 10 );
			add_action( 'send_reminder_quote_mail', array( 'WC_Emails', 'send_transactional_email' ), 10 );
			add_action( 'send_reminder_quote_accept_mail', array( 'WC_Emails', 'send_transactional_email' ), 10 );
		}

		/**
		 * Build wishlist page URL.
		 *
		 * @param string $action .
		 *
		 * @return string
		 * @since 1.0.0
		 */
		public function get_raq_url( $action = 'view' ) {
			$base_url    = '';
			$raq_page_id = get_option( 'ywraq_page_id' );

			if ( get_option( 'permalink_structure' ) ) {
				$raq_page          = get_post( $raq_page_id );
				$raq_page_slug     = $raq_page->post_name;
				$raq_page_relative = '/' . $raq_page_slug . '/' . $action . '/';

				$base_url = trailingslashit( home_url( $raq_page_relative ) );
			}

			return $base_url;

		}

		/**
		 * Check if the raq button can be showed
		 *
		 * @param boolean    $value   Current filter value.
		 * @param WC_Product $product The WC Product object.
		 *
		 * @return bool
		 * @since 1.0.0
		 */
		public function must_be_showed( $value = true, $product = null ) {

			if ( is_null( $product ) ) {
				// if is null get global.
				global $product;
			}
			// if is null get post.
			if ( ! $product ) {
				global $post;
				if ( ! $post || ! is_object( $post ) || ! is_singular() ) {
					return false;
				}
				$product = wc_get_product( $post->ID );
			}

			if ( ! is_object( $product ) || ! $this->check_user_type() || ( ! ywraq_allow_raq_out_of_stock() && $product && ! $product->is_in_stock() ) || ( ywraq_show_btn_only_out_of_stock() && $product && $product->is_type( 'simple' ) && $product->is_in_stock() ) ) {
				return false;
			}

			if ( ywraq_is_in_exclusion( $product->get_id() ) ) {
				return false;
			}

			return $value;
		}

		/**
		 * Check user
		 *
		 * @return bool
		 * @since 1.0.0
		 */
		public function check_user() {

			global $product;

			if ( ! $product ) {
				global $post;
				if ( ! $post || ! is_object( $post ) || ! is_singular() ) {
					return false;
				}
				$product = wc_get_product( $post->ID );
			}

			if ( ! is_object( $product ) || ! $this->check_user_type() || ( ! ywraq_allow_raq_out_of_stock() && $product && ! $product->is_in_stock() ) || ( ywraq_show_btn_only_out_of_stock() && $product && $product->is_in_stock() ) ) {
				return false;
			}

			return true;
		}

		/**
		 * Check if the raq button can be showed
		 *
		 * @return bool
		 * @since 1.0.0
		 */
		public function raq_page_check_user() {

			if ( ! $this->check_user_type() ) {
				return false;
			}

			return true;
		}

		/**
		 * Check if the current user is available to send requests
		 *
		 * @return bool
		 * @since 1.0.0
		 */
		public function check_user_type() {
			$user_type = get_option( 'ywraq_user_type' );
			$return    = false;

			if ( is_user_logged_in() && 'guests' !== $user_type ) {

				if ( 'all' === $user_type || 'customers' === $user_type ) {
					return true;
				}

				$rules = (array) get_option( 'ywraq_user_role' );

				if ( empty( $rules ) || ! is_array( $rules ) ) {
					return false;
				}

				if ( in_array( 'all', $rules, true ) ) {
					return true;
				}

				$current_user = function_exists( 'wp_get_current_user' ) ? wp_get_current_user() : false;
				$intersect    = array();
				if ( $current_user instanceof WP_User ) {
					$intersect = array_intersect( $current_user->roles, $rules );
				}

				if ( ! empty( $intersect ) ) {
					return true;
				}
			} elseif ( ! is_user_logged_in() && ( 'guests' === $user_type || 'all' === $user_type ) ) {
				return true;
			}

			return $return;
		}

		/**
		 * Raq page denied access
		 *
		 * @param string $message .
		 *
		 * @return string
		 */
		public function raq_page_denied_access( $message ) {
			$user_type = get_option( 'ywraq_user_type' );

			if ( 'customers' === $user_type || 'roles' === $user_type ) {
				return __( 'You must be logged in to access this page', 'yith-woocommerce-request-a-quote' );
			}

			return $message;
		}

		/**
		 * Generate the template
		 *
		 * @param int  $order_id .
		 * @param bool $preview  Set true if show the pdf.
		 *
		 * @return mixed
		 */
		public function generate_pdf( $order_id, $preview = false ) {
			if ( get_option( 'ywraq_pdf_template_to_use' ) === 'builder' ) {
				$template_id = get_option( 'ywraq_pdf_custom_templates', 0 );
				$order       = wc_get_order( $order_id );
				$language    = $order->get_meta( 'wpml_language' );
				$template    = ywraq_get_pdf_template( $template_id, $language );

				return $template->generate_pdf( $order_id );
			} else {
				return $this->generate_pdf_from_old_template( $order_id, $preview = false );
			}
		}

		/**
		 * Return the mpdf object
		 *
		 * @return Mpdf
		 * @throws \Mpdf\MpdfException
		 */
		public function get_mpdf() {
			require_once YITH_YWRAQ_DIR . 'lib/mpdf/autoload.php';
			$mpdf_args = apply_filters(
				'ywraq_mpdf_args',
				array(
					'autoScriptToLang'  => true,
					'autoLangToFont'    => true,
					'default_font'      => 'dejavusans',
					'default_font_size' => 12,
				)
			);

			if ( is_array( $mpdf_args ) ) {
				$mpdf = new Mpdf( $mpdf_args );
			} else {
				$mpdf = new Mpdf();
			}

			$direction                  = is_rtl() ? 'rtl' : 'ltr';
			$mpdf->directionality       = apply_filters( 'ywraq_mpdf_directionality', $direction );
			$mpdf->shrink_tables_to_fit = 1;

			return $mpdf;
		}

		/**
		 * Generate pdf from php template
		 *
		 * @param int  $order_id .
		 * @param bool $preview  Set true if show the pdf.
		 *
		 * @return mixed
		 * @since 4.0
		 *
		 */
		public function generate_pdf_from_old_template( $order_id, $preview = false ) {
			ob_start();

			wc_get_template( 'pdf/quote.php', array( 'order_id' => $order_id ), '', YITH_YWRAQ_TEMPLATE_PATH . '/' );

			$html = ob_get_contents();
			ob_end_clean();

			$mpdf = $this->get_mpdf();

			$mpdf->WriteHTML( $html );

			$pdf       = $mpdf->Output( 'document', 'S' );
			$file_path = $this->get_pdf_file_path( $order_id, true );

			if ( ! file_exists( $file_path ) ) {
				$file_path = $this->get_pdf_file_path( $order_id, false );
			} else {
				unlink( $file_path );
			}

			$file = fopen( $file_path, "a" ); //phpcs:ignore
			fwrite( $file, $pdf ); //phpcs:ignore
			fclose( $file ); //phpcs:ignore

			return $file;
		}

		/**
		 * Generate the template
		 *
		 * @return mixed
		 */
		public function generate_preview_list_pdf() {

			ob_start();

			wc_get_template( 'pdf/preview-list/preview-list.php', array(), '', YITH_YWRAQ_TEMPLATE_PATH . '/' );

			$html = ob_get_contents();
			ob_end_clean();

			$mpdf = $this->get_mpdf();

			$mpdf->WriteHTML( $html );

			$pdf = $mpdf->Output( 'document', 'S' );

			$file_path = $this->get_pdf_file_path( 0, true );

			if ( ! file_exists( $file_path ) ) {
				$file_path = $this->get_pdf_file_path( 0, false );
			} else {
				unlink( $file_path );
			}

			$file = fopen( $file_path, "a" ); //phpcs:ignore
			fwrite( $file, $pdf ); //phpcs:ignore
			fclose( $file ); //phpcs:ignore

			return $this->get_pdf_file_url();
		}

		/**
		 * Get Pdf File Url
		 *
		 * @param int $order_id .
		 *
		 * @return string
		 */
		public function get_pdf_file_url( $order_id = 0 ) {
			$path = $this->create_storing_folder( $order_id );
			$url  = YITH_YWRAQ_SAVE_QUOTE_URL . $path . $this->get_pdf_file_name( $order_id );

			return apply_filters( 'ywraq_pdf_file_url', $url );
		}

		/**
		 * Create the pdf from raq list.
		 */
		public function ajax_get_pdf_from_list() {
			check_ajax_referer( 'ywraq-list-to-pdf' );
			$pdf_url = $this->generate_preview_list_pdf();

			wp_send_json(
				array( 'pdf' => $pdf_url )
			);
		}

		/**
		 * Return the file of pdf
		 *
		 * @param int $order_id .
		 *
		 * @return string
		 */
		public function get_pdf_file_name( $order_id ) {
			$format = get_option( 'ywraq_pdf_file_name', 'quote_%rand%' );
			$order  = wc_get_order( $order_id );
			if ( $order ) {
				$ywraq_customer_email = $order->get_meta( 'ywraq_customer_email' );
				$quote_number         = $order->get_order_number();
				$pdf_file_name        = str_replace( '%rand%', md5( $order_id . $ywraq_customer_email ), $format );
				$pdf_file_name        = str_replace( '%quote_number%', $quote_number, $pdf_file_name );
			} else {
				$hash_name = ywraq_get_cookie_name( 'hash' );
				if ( isset( $_COOKIE[ $hash_name ] ) ) { //phpcs:ignore
					$hash = $_COOKIE[ $hash_name ]; //phpcs:ignore
				} elseif ( isset( $_COOKIE['woocommerce_cart_hash'] ) ) {
					$hash = $_COOKIE['woocommerce_cart_hash']; //phpcs:ignore
				} elseif ( is_user_logged_in() ) {
					$hash = get_current_user_id();
				} else {
					$hash = uniqid();
				}

				$pdf_file_name = str_replace( '%rand%', md5( $hash ), $format );
				$pdf_file_name = str_replace( '%quote_number%', '', $pdf_file_name );

			}

			$pdf_file_name = $pdf_file_name . '.pdf';

			return apply_filters( 'ywraq_pdf_file_name', $pdf_file_name, $order_id );
		}

		/**
		 * Get Pdf File Path
		 *
		 * @param int  $order_id    .
		 * @param bool $delete_file .
		 *
		 * @return string
		 */
		public function get_pdf_file_path( $order_id = 0, $delete_file = false ) {
			$path = apply_filters( 'ywraq_pdf_file_path', $this->create_storing_folder( $order_id ), $order_id );
			$file = YITH_YWRAQ_DOCUMENT_SAVE_DIR . $path . $this->get_pdf_file_name( $order_id );
			// delete the document if exists.
			if ( file_exists( $file ) && $delete_file ) {
				@unlink( $file ); //phpcs:ignore
			}

			return $file;
		}

		/**
		 * Send the quote automatically after that the customer does the request.
		 *
		 * @param array    $raq   .
		 * @param WC_Order $order .
		 */
		public function send_the_quote_automatically( $raq, $order ) {

			if ( current_action() === 'ywraq_after_create_order' ) {
				$order = wc_get_order( $raq );
			}

			if ( $order instanceof WC_Order ) {
				/**
				 * DO_ACTION:create_pdf
				 *
				 * This action is triggered to create the pdf template
				 *
				 * @param int  $order_id Order id.
				 * @param bool $preview  Set if the pdf is a preview or not.
				 */
				do_action( 'create_pdf', $order->get_id(), false );
				/**
				 * DO_ACTION:send_quote_mail
				 *
				 * This action is triggered to send the quote email to the customer
				 *
				 * @param int $order_id Order id.
				 */
				do_action( 'send_quote_mail', $order->get_id() );
				$order->update_meta_data( 'ywraq_pending_status_date', gmdate( 'Y-m-d' ) );
				$order->update_status( 'ywraq-pending' );
			}
		}

		/**
		 * Create Storing Folder
		 *
		 * @param int $order_id .
		 *
		 * @return mixed|string
		 */
		public static function create_storing_folder( $order_id = 0 ) {

			/* Create folders for storing documents */
			$folder_pattern = '[year]/[month]/';

			if ( $order_id ) {
				$order      = wc_get_order( $order_id );
				$order_date = is_callable(
					array(
						$order,
						'get_date_created',
					)
				) ? $order->get_date_created() : $order->order_date;
			} else {
				$order_date = gmdate( 'Y-m-d H:i:s' );
			}

			$date = getdate( strtotime( $order_date ) );

			$folder_pattern = str_replace(
				array(
					'[year]',
					'[month]',
				),
				array(
					$date['year'],
					sprintf( '%02d', $date['mon'] ),
				),
				$folder_pattern
			);

			if ( ! file_exists( YITH_YWRAQ_DOCUMENT_SAVE_DIR . $folder_pattern ) ) {
				wp_mkdir_p( YITH_YWRAQ_DOCUMENT_SAVE_DIR . $folder_pattern );
			}

			return $folder_pattern;
		}

		/**
		 * PDF Content
		 *
		 * @param int $order_id .
		 */
		public function pdf_content( $order_id ) {
			$order    = wc_get_order( $order_id );
			$template = get_option( 'ywraq_pdf_template', 'table' );

			if ( 'table' === $template ) {
				wc_get_template( 'pdf/quote-table.php', array( 'order' => $order ), '', YITH_YWRAQ_TEMPLATE_PATH . '/' );
			} else {
				wc_get_template( 'pdf/quote-table-div.php', array( 'order' => $order ), '', YITH_YWRAQ_TEMPLATE_PATH . '/' );
			}

		}

		/**
		 * PDF Header
		 *
		 * @param int $order_id .
		 */
		public function pdf_header( $order_id ) {
			$order = wc_get_order( $order_id );
			wc_get_template( 'pdf/quote-header.php', array( 'order' => $order ), '', YITH_YWRAQ_TEMPLATE_PATH . '/' );
		}

		/**
		 * PDF Footer
		 *
		 * @param int $order_id .
		 */
		public function pdf_footer( $order_id ) {
			$footer_content  = get_option( 'ywraq_pdf_footer_content' );
			$show_pagination = get_option( 'ywraq_pdf_pagination' );
			wc_get_template(
				'pdf/quote-footer.php',
				array(
					'footer'     => $footer_content,
					'pagination' => $show_pagination,
					'order_id'   => $order_id,
				),
				'',
				YITH_YWRAQ_TEMPLATE_PATH . '/'
			);
		}

		/**
		 * Change PDF Language
		 *
		 * @param string $lang .
		 */
		public function change_pdf_language( $lang ) {
			global $sitepress, $woocommerce;
			if ( is_object( $sitepress ) ) {
				$sitepress->switch_lang( $lang, true );
				$this->locale = $sitepress->get_locale( $lang );
				unload_textdomain( 'yith-woocommerce-request-a-quote' );
				unload_textdomain( 'woocommerce' );
				unload_textdomain( 'default' );

				load_plugin_textdomain( 'yith-woocommerce-request-a-quote', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
				$woocommerce->load_plugin_textdomain();
				load_default_textdomain();
			}
		}

		/**
		 * Set correct locale code for emails
		 *
		 * @param string $locale .
		 * @param string $domain .
		 *
		 * @return bool
		 */
		public function set_locale_for_pdf( $locale, $domain ) {

			if ( 'woocommerce' === $domain && $this->locale ) {
				$locale = $this->locale;
			}

			return $locale;
		}

		/**
		 * Set plugin requirement on System Info Panel
		 */
		public function set_plugin_requirements() {

			$plugin_name  = YITH_YWRAQ_PLUGIN_NAME;
			$requirements = array(
				'wp_cron_enabled'  => true,
				'mbstring_enabled' => true,
				'gd_enabled'       => true,
				'iconv_enabled'    => true,
				'imagick_version'  => '6.4.0',
			);
			yith_plugin_fw_add_requirements( $plugin_name, $requirements );
		}

		/**
		 * Override ywraq_show_preview option based on the new option
		 * to avoid issue with old template.
		 *
		 * @param mixed $value Current value option.
		 *
		 * @return mixed|string
		 */
		public function override_ywraq_show_preview_option( $value ) {

			$new_options = get_option( 'ywraq_product_table_show' );
			if ( $new_options ) {
				$value = in_array( 'images', $new_options, true ) ? 'yes' : 'no';
			}

			return $value;
		}


		/**
		 * Override ywraq_expired_time option based on the new option
		 * to avoid issue with old settings.
		 *
		 * @param mixed $value Current value option.
		 *
		 * @return mixed|string
		 */
		public function override_ywraq_expired_time( $value ) {

			if ( ! is_array( $value ) ) {
				update_option( 'ywraq_enable_expired_time', wc_bool_to_string( $value ) );
				$value = 0 === $value ? 10 : $value;
				$value = array( 'days' => $value );
			}

			return $value;
		}


		/**
		 * Add the gateway to WC Available Gateways
		 *
		 * @param array $gateways all available WC gateways .
		 *
		 * @return array $gateways all WC gateways + offline gateway
		 * @since 3.1.3
		 */
		public function add_ywraq_gateway( $gateways ) {
			$gateways[] = 'YITH_YWRAQ_Gateway';

			return $gateways;
		}

		/**
		 * If the quote is created from checkout disavble the email new order
		 *
		 * @param bool     $enabled Status to filter.
		 * @param WC_Order $order   Order.
		 *
		 * @since 3.1.4
		 */
		public function disable_new_order_email( $enabled, $order ) {

			$request = $_REQUEST; //phpcs:ignore

			if ( isset( $request['wc_order_action'] ) && 'send_order_details_admin' === $request['wc_order_action'] ) {
				return $enabled;
			}

			if ( $order instanceof WC_Order && $order->get_meta( 'ywraq_raq' ) === 'yes' ) {
				$enabled = false;
			}

			return $enabled;
		}
	}

}

/**
 * Unique access to instance of YITH_Request_Quote_Premium class
 *
 * @return YITH_Request_Quote_Premium
 */
function YITH_Request_Quote_Premium() { //phpcs:ignore
	return YITH_Request_Quote_Premium::get_instance();
}

