<?php //phpcs:ignore WordPress.Files.FileName.InvalidClassFileName
/**
 * This file belongs to the YIT Plugin Framework.
 *
 * This source file is subject to the GNU GENERAL PUBLIC LICENSE (GPL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.gnu.org/licenses/gpl-3.0.txt
 *
 * @package YITH\RequestAQuote
 */

if ( ! defined( 'ABSPATH' ) || ! defined( 'YITH_YWRAQ_VERSION' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Implements the YWRAQ_Ninja_Forms class.
 *
 * @class   YWRAQ_Ninja_Forms
 * @package YITH\RequestAQuote
 * @since   3.1.0
 * @author  YITH <plugins@yithemes.com>
 */
if ( ! class_exists( 'YWRAQ_Ninja_Forms' ) ) {

	/**
	 * Class YWRAQ_Ninja_Forms
	 */
	class YWRAQ_Ninja_Forms {

		/**
		 * Single instance of the class
		 *
		 * @var \YWRAQ_Ninja_Forms
		 */
		protected static $instance;

		/**
		 * Ninja option name
		 *
		 * @var string
		 */
		protected $option_name = 'ywraq_inquiry_ninja_forms_id';


		/**
		 * Ninja form data processed
		 *
		 * @var array
		 */
		protected $formdata = array();


		/**
		 * Returns single instance of the class
		 *
		 * @return YWRAQ_Ninja_Forms
		 * @since  3.1.0
		 */
		public static function get_instance() {
			return ! is_null( self::$instance ) ? self::$instance : self::$instance = new self();
		}


		/**
		 * Constructor
		 *
		 * Initialize form and registers actions and filters to be used
		 *
		 * @since  2.0
		 */
		public function __construct() {
			if ( is_admin() ) {
				add_filter( 'ywraq_form_type_list', array( $this, 'add_to_option_list' ) );
				add_filter( 'ywraq_additional_form_options', array( $this, 'add_option' ), 10, 3 );
				add_filter( 'ywraq_get_ninja_forms', array( $this, 'get_forms' ) );
				add_filter( 'ninja_forms_field_load_settings', array( $this, 'load_custom_settings' ), 10, 3 );
				add_filter( 'ywraq_additional_form_options', array( $this, 'add_option' ), 10, 3 );
			}

			add_action( 'ninja_forms_loaded', array( $this, 'register_mergetags' ) );
			if ( get_option( 'ywraq_inquiry_form_type' ) === 'ninja-forms' ) {
				add_action( 'ninja_forms_after_submission', array( $this, 'create_order_before_send_email' ) );
				add_filter( 'ywraq_order_meta_list', array( $this, 'add_order_metas' ), 10, 3 );
				add_filter( 'ywraq_ajax_create_order_args', array( $this, 'create_order_args' ), 10, 2 );
				add_filter( 'yith_ywraq_frontend_localize', array( $this, 'frontend_localize' ) );
			}
		}

		/**
		 * Add current wpforms to javascript frontend localization.
		 *
		 * @param array $localize Localize array.
		 *
		 * @return mixed
		 */
		public function frontend_localize( $localize ) {
			$localize['ninja_forms'] = apply_filters( 'ywraq_inquiry_ninja_forms_id', $this->get_selected_form_id() );

			return $localize;
		}


		/**
		 * Trigger the order creation when the selected wpform is submitted.
		 *
		 * @param array $data Form data.
		 *
		 * @return void
		 * @throws Exception Throws an Exception.
		 */
		public function create_order_before_send_email( $data ) {

			if ( ! isset( $data['form_id'] ) || $this->get_selected_form_id() !== $data['form_id'] ) {
				return;
			}

			$this->formdata = $data;
			YITH_YWRAQ_Order_Request()->ajax_create_order( false );
		}


		/**
		 * Add an additional setting inside each field of Ninja form.
		 *
		 * @param array  $settings List of settings.
		 * @param string $name Name of field.
		 * @param string $parent_type Parent.
		 *
		 * @return array
		 */
		public function load_custom_settings( $settings, $name, $parent_type ) {
			$new_settings = array();
			foreach ( $settings as $key => $setting ) {
				$new_settings[ $key ] = $setting;
				if ( 'label_pos' === $key ) {
					$new_settings['connect_to'] = array(
						'name'    => 'connect_to',
						'type'    => 'select',
						'label'   => esc_html_x( 'Connect to', 'Connect fields to WooCommerce fields', 'yith-woocommerce-request-a-quote' ),
						'options' => $this->get_connection_fields(),
						'width'   => 'full',
						'group'   => 'advanced',
						'value'   => 'default',
						'help'    => '',
					);
				}
			}

			return $new_settings;
		}


		/**
		 * Return the connection fields changed ad hoc for the ninja form field settings
		 *
		 * @return array
		 */
		public function get_connection_fields() {
			$fields            = ywraq_get_connect_fields();
			$connection_fields = array();
			foreach ( $fields as $key => $field ) {
				$connection_fields[] = array(
					'label' => $field,
					'value' => $key,
				);
			}

			return $connection_fields;
		}


		/**
		 * Register Merge Tags Call Back
		 */
		public function register_mergetags() {
			Ninja_Forms()->merge_tags['ywraq_merge_tags'] = new YWRAQ_Ninja_Forms_Tag();
		}

		/**
		 * Add the option with the list of the forms
		 *
		 * @param array $options Array of options to manage.
		 *
		 * @return mixed
		 */
		public function add_option( $options ) {
			$forms = apply_filters( 'ywraq_get_ninja_forms', array() );
			reset( $forms );
			$first_key = key( $forms );
			$form_link = empty( $first_key ) ? __( 'Create form', 'yith-woocommerce-request-a-quote' ) : __( 'Edit form', 'yith-woocommerce-request-a-quote' );

			if ( function_exists( 'wpml_get_active_languages_filter' ) ) {
				$langs = wpml_get_active_languages_filter( '', 'skip_missing=N&orderby=KEY&order=DIR&link_empty_to=str' );

				if ( is_array( $langs ) ) {
					foreach ( $langs as $key => $lang ) {
						$wp_forms[ 'wpforms_' . $key ] = array(
							'name'      => esc_html__( 'Form to display', 'yith-woocommerce-request-a-quote' ) . sprintf( ' %s:', $lang['native_name'] ),
							'type'      => 'yith-field',
							'yith-type' => 'select',
							'desc'      => __( 'Choose the form to display', 'yith-woocommerce-request-a-quote' ),
							'options'   => $forms,
							'id'        => $this->option_name . '_' . $key,
							'deps'      => array(
								'id'    => 'ywraq_inquiry_form_type',
								'value' => 'ninja-forms',
								'type'  => 'hide',
							),
							'class'     => 'ninja-forms',
						);
					}
				}
			} else {
				$wp_forms = array(
					'name'      => esc_html__( 'Form to display', 'yith-woocommerce-request-a-quote' ),
					'type'      => 'yith-field',
					'yith-type' => 'select',
					'desc'      => sprintf( '%s. <a href="%s" class="ywraq_form_link" data-type="ninja-forms">%s</a>', __( 'Choose the form to display', 'yith-woocommerce-request-a-quote' ), esc_url( add_query_arg( array( 'page' => 'ninja-forms' ), admin_url( 'admin.php' ) ) ), $form_link ),
					'options'   => $forms,
					'id'        => $this->option_name,
					'deps'      => array(
						'id'    => 'ywraq_inquiry_form_type',
						'value' => 'ninja-forms',
						'type'  => 'hide',
					),
					'class'     => 'ninja-forms',
				);

			}

			if ( ! empty( $wp_forms ) ) {
				foreach ( $wp_forms as $k => $nf ) {
					if ( ! is_array( $nf ) ) {
						$options['ninja-forms'] = $wp_forms;
						break;
					}
					$options[ $k ] = $nf;
				}
			}

			return $options;
		}


		/**
		 * Return the list of forms
		 *
		 * @return array
		 */
		public function get_forms() {
			$forms = Ninja_Forms()->form()->get_forms();

			$ninja_forms = array();
			foreach ( $forms as $form ) {
				$ninja_forms[ $form->get_id() ] = $form->get_setting( 'title' );
			}

			if ( empty( $ninja_forms ) ) {
				return array( '' => __( 'No contact form found', 'yith-woocommerce-request-a-quote' ) );
			}

			return $ninja_forms;
		}


		/**
		 * Add Ninja Forms inside the list of forms type
		 *
		 * @param array $list List of forms supported.
		 *
		 * @return mixed
		 */
		public function add_to_option_list( $list ) {
			$list['ninja-forms'] = __( 'Ninja Forms', 'yith-woocommerce-request-a-quote' );

			return $list;
		}

		/**
		 * Return the id of the selected form.
		 *
		 * @return int
		 */
		public function get_selected_form_id() {
			global $sitepress;
			if ( function_exists( 'icl_get_languages' ) && ! is_null( $sitepress ) ) {
				$current_language = $sitepress->get_current_language();
				$form_id          = get_option( $this->option_name . '_' . $current_language );
			} else {
				$form_id = get_option( $this->option_name );
			}

			return $form_id;
		}

		/**
		 * Get form shortcode
		 *
		 * @return string
		 */
		public function get_shortcode_form() {
			$form_id = $this->get_selected_form_id();

			return $this->get_shortcode_form_by_id( $form_id );
		}

		/**
		 * Get form shortcode by id
		 *
		 * @param int $form_id Form id.
		 *
		 * @return string
		 */
		public function get_shortcode_form_by_id( $form_id ) {
			if ( empty( $form_id ) ) {
				$form_id = key( $this->get_forms() );
			}

			return apply_filters( 'ywraq_ninja_forms_shortcode', '[ninja_form id="' . $form_id . '"]', $form_id );
		}

		/**
		 * Add argument with fields sent from wpforms form before create the order.
		 *
		 * @param array $args Array of argument necessary to create the order quote.
		 * @param array $posted Array in posted.
		 *
		 * @return array
		 */
		public function create_order_args( $args, $posted ) {
			$name                = '';
			$email               = '';
			$message             = '';
			$other_email_content = '';
			$other_fields        = array();

			if ( ! empty( $this->formdata ) ) {
				$extra = $this->formdata['extra'];
				$sent_from_cart = ( !empty( $extra) && isset( $extra['sentFromCart']) && 'yes' === $extra['sentFromCart'] );
				if( $sent_from_cart && WC()->cart ){
					$args['raq_content'] = WC()->cart->get_cart_contents();
				}
				foreach ( $this->formdata['fields'] as $index => $defined_field ) {
					$value       = isset( $defined_field['value'] ) ? $defined_field['value'] : '';
					$field_key   = $defined_field['key'];
					$field_label = $defined_field['label'] ? $defined_field['label'] : $field_key;
					$wc_key      = empty( $defined_field['settings']['connect_to'] ) ? false : $defined_field['settings']['connect_to'];

					if ( $wc_key ) {
						$args[ $wc_key ] = $value;
					}

					if ( 'email' === $defined_field['settings']['type'] ) {
						$email = sanitize_email( $value );
					}
					$value               = is_array( $value ) ? implode( ', ', $value ) : $value;
					$other_email_content .= sprintf( '<strong>%s</strong>: %s<br>', $field_label, $value );

					$key                  = apply_filters( 'ywraq_other_email_content_key', $field_label, $value );
					$other_fields[ $key ] = $value;
				}
			}

			// check the name and email if connected with the billing fields.
			if ( isset( $args['billing_first_name'] ) || isset( $args['billing_last_name'] ) ) {
				$first_name = isset( $args['billing_first_name'] ) ? $args['billing_first_name'] : '';
				$last_name  = isset( $args['billing_last_name'] ) ? $args['billing_last_name'] : '';
				$name       = $first_name . ' ' . $last_name;
			}

			$args['user_name']    = empty( $name ) ? esc_html__( 'Guest', 'yith-woocommerce-request-a-quote' ) : sanitize_text_field( $name );
			$args['user_email']   = isset( $args['billing_email'] ) ? sanitize_email( $args['billing_email'] ) : $email;
			$args['user_message'] = isset( $args['order_comments'] ) ? $args['order_comments'] : $message;

			foreach ( $other_fields as $key => $value ) {
				if ( $value === $args['user_name'] || $value === $args['user_email'] || $value === $args['user_message'] ) {
					unset( $other_fields[ $key ] );
				}
			}

			$args['other_email_fields']  = $other_fields;
			$args['other_email_content'] = $other_email_content;

			return $args;
		}


		/**
		 * Add order meta from request.
		 *
		 * @param array $attr Attributes to manage.
		 * @param int   $order_id Order id.
		 * @param array $raq Request content.
		 *
		 * @return mixed
		 */
		public function add_order_metas( $attr, $order_id, $raq ) {
			$attr['ywraq_customer_name']      = $raq['user_name'];
			$attr['ywraq_customer_message']   = $raq['user_message'];
			$attr['ywraq_customer_email']     = $raq['user_email'];
			$attr['ywraq_other_email_fields'] = $raq['other_email_fields'];

			$attr['_raq_request'] = $raq;

			$ov_field = apply_filters( 'ywraq_override_order_billing_fields', true );
			if ( $ov_field ) {
				$supported_fields = ywraq_get_connect_fields();

				foreach ( $supported_fields as $key => $field ) {
					if ( isset( $raq[ $key ] ) && ! empty( $raq[ $key ] ) ) {
						$name          = '_' . $key;
						$attr[ $name ] = $raq[ $key ];
					}
				}
			}

			return $attr;
		}
	}

	/**
	 * Unique access to instance of YWRAQ_Ninja_Forms class
	 *
	 * @return \YWRAQ_Ninja_Forms
	 */
	function YWRAQ_Ninja_Forms() { //phpcs:ignore
		return YWRAQ_Ninja_Forms::get_instance();
	}
}

