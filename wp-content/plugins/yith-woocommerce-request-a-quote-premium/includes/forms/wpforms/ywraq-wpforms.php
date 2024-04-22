<?php //phpcs:ignore WordPress.Files.FileName.InvalidClassFileName
/**
 * This file belongs to the YIT Plugin Framework.
 *
 * This source file is subject to the GNU GENERAL PUBLIC LICENSE (GPL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.gnu.org/licenses/gpl-3.0.txt
 */

if ( ! defined( 'ABSPATH' ) || ! defined( 'YITH_YWRAQ_VERSION' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Implements the YWRAQ_WPForms class.
 *
 * @class   YWRAQ_WPForms
 * @package YITH\RequestAQuote
 * @since   3.1.0
 * @author  YITH <plugins@yithemes.com>
 */
if ( ! class_exists( 'YWRAQ_WPForms' ) ) {

	/**
	 * Class YWRAQ_WPForms
	 */
	class YWRAQ_WPForms {

		/**
		 * Single instance of the class
		 *
		 * @var \YWRAQ_WPForms
		 */
		protected static $instance;


		/**
		 * WPFoms option name
		 *
		 * @var string
		 */
		protected $option_name = 'ywraq_inquiry_wpforms_id';


		/**
		 * WPFoms tag name
		 *
		 * @var string
		 */
		protected $tag_name = 'ywraq_list';


		/**
		 * WPFoms form data
		 *
		 * @var array
		 */
		protected $formdata = array();

		/**
		 * WPFoms data entry by customer
		 *
		 * @var array
		 */
		protected $entry = array();

		/**
		 * Returns single instance of the class
		 *
		 * @return YWRAQ_WPForms
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
		 * @since  3.1
		 */
		public function __construct() {

			if ( isset( $_GET['action'] ) && 'wpforms_approve' === $_GET['action'] ) {
				return;
			}

			require_once YITH_YWRAQ_INC . 'forms/wpforms/ywraq-wpforms-template.php';
			if ( is_admin() ) {
				add_filter( 'ywraq_additional_form_options', array( $this, 'add_option' ), 10, 3 );
				add_filter( 'ywraq_get_wpforms', array( $this, 'get_forms' ) );
				add_filter( 'ywraq_form_type_list', array( $this, 'add_to_option_list' ) );
			}

			// Admin section.
			add_action( 'wpforms_field_options_bottom_advanced-options', array( $this, 'custom_options' ), 10, 2 );
			add_filter( 'wpforms_smart_tags', array( $this, 'add_list_tag' ) );
			add_filter( 'wpforms_process_smart_tags', array( $this, 'process_smart_tag' ), 20, 4 );
			if ( get_option( 'ywraq_inquiry_form_type' ) === 'wpforms' ) {

				// Add custom tag to wpforms.
				add_filter( 'yith_ywraq_frontend_localize', array( $this, 'frontend_localize' ) );
				add_filter( 'ywraq_order_meta_list', array( $this, 'add_order_metas' ), 10, 3 );
				add_filter( 'ywraq_ajax_create_order_args', array( $this, 'create_order_args' ), 10, 2 );

				// Triggered bu WPForms when the form selected is submitted.
				add_action( 'wpforms_process_' . $this->get_selected_form_id(), array( $this, 'create_order_before_send_email' ), 10, 3 );
			}
		}

		/**
		 * Add WPForms inside the list of forms type
		 *
		 * @param array $list List of forms supported.
		 *
		 * @return mixed
		 */
		public function add_to_option_list( $list ) {
			$list['wpforms'] = __( 'WPForms', 'yith-woocommerce-request-a-quote' );

			return $list;
		}


		/**
		 * Return the list of forms
		 *
		 * @return array
		 */
		public function get_forms() {
			$args  = array(
				'post_type'      => 'wpforms',
				'post_status'    => 'publish',
				'posts_per_page' => -1,
			);
			$posts = wpforms()->form->get();
			$forms = array();
			if ( $posts ) {
				foreach ( $posts as $post ) {
					$forms[ $post->ID ] = $post->post_title;

				}
			}
			if ( empty( $forms ) ) {
				return array( '' => __( 'No contact form found', 'yith-woocommerce-request-a-quote' ) );
			}
			return $forms;
		}

		/**
		 * Add the option with the list of the forms
		 *
		 * @param array $options Array of options to manage.
		 *
		 * @return mixed
		 */
		public function add_option( $options ) {
			$forms = apply_filters( 'ywraq_get_wpforms', array() );
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
								'value' => 'wpforms',
								'type'  => 'hide',
							),
							'class'     => 'wpforms',
						);
					}
				}
			} else {
				$wp_forms = array(
					'name'      => esc_html__( 'Form to display', 'yith-woocommerce-request-a-quote' ),
					'type'      => 'yith-field',
					'yith-type' => 'select',
					'desc'      => sprintf( '%s. <a href="%s" class="ywraq_form_link" data-type="wpforms">%s</a>', __( 'Choose the form to display', 'yith-woocommerce-request-a-quote' ), esc_url( add_query_arg( array( 'page' => 'wpforms-builder' ), admin_url( 'admin.php' ) ) ), $form_link ),
					'options'   => $forms,
					'id'        => $this->option_name,
					'deps'      => array(
						'id'    => 'ywraq_inquiry_form_type',
						'value' => 'wpforms',
						'type'  => 'hide',
					),
					'class'     => 'wpforms',
				);

			}

			if ( ! empty( $wp_forms ) ) {
				foreach ( $wp_forms as $k => $wpf ) {
					if ( ! is_array( $wpf ) ) {
						$options['wpforms'] = $wp_forms;
						break;
					}
					$options[ $k ] = $wpf;
				}
			}

			return $options;
		}

		/**
		 * Add additional field inside the tab of each field type.
		 *
		 * @param array  $field Field.
		 * @param object $field_class Field Object.
		 */
		public function custom_options( $field, $field_class ) {

			if ( isset( $field['type'] ) && 'name' === $field['type'] ) {
				$label = esc_html__( 'First Name', 'yith-woocommerce-request-a-quote' );
			} else {
				$label = isset( $field['label'] ) ? $field['label'] : '';
			}

			// translators: placeholder is the name of the field.
			$label   = sprintf( esc_html_x( 'Connect %s to a WooCommerce field', 'placeholder is the name of the field', 'yith-woocommerce-request-a-quote' ), $label );
			$tooltip = esc_html_x( 'Connect a WooCommerce field: the information entered by customers on the Request a Quote page will automatically fill the billing and shipping fields in the quote order.', 'description of field in wpforms integration', 'yith-woocommerce-request-a-quote' );

			$filter_type_label = $field_class->field_element(
				'label',
				$field,
				array(
					'slug'    => 'connect_to_label',
					'value'   => $label,
					'tooltip' => $tooltip,
				),
				false
			);

			$filter_type_field = $field_class->field_element(
				'select',
				$field,
				array(
					'slug'    => 'connect_to',
					'value'   => ! empty( $field['connect_to'] ) ? esc_attr( $field['connect_to'] ) : '',
					'options' => ywraq_get_connect_fields(),
				),
				false
			);
			$field_class->field_element(
				'row',
				$field,
				array(
					'slug'    => 'connect_to_option',
					'content' => $filter_type_label . $filter_type_field,
				)
			);

			if ( isset( $field['type'] ) && 'name' === $field['type'] ) {
				$filter_type_label = $field_class->field_element(
					'label',
					$field,
					array(
						'slug'    => 'connect_to_label2',
						'value'   => esc_html__( 'Connect Last Name to WooCommerce field', 'yith-woocommerce-request-a-quote' ),
						'tooltip' => $tooltip,
					),
					false
				);
				$filter_type_field = $field_class->field_element(
					'select',
					$field,
					array(
						'slug'    => 'connect_to2',
						'value'   => ! empty( $field['connect_to2'] ) ? esc_attr( $field['connect_to2'] ) : '',
						'options' => ywraq_get_connect_fields(),
					),
					false
				);
				$field_class->field_element(
					'row',
					$field,
					array(
						'slug'    => 'connect_to_option2',
						'content' => $filter_type_label . $filter_type_field,
					)
				);
			}

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
		 * @return string
		 */
		public function get_shortcode_form_by_id( $form_id ) {
			if ( empty( $form_id ) ) {
				$form_id = key( $this->get_forms() );
			}
			return apply_filters( 'ywraq_wpforms_shortcode', '[wpforms id="' . $form_id . '"]', $form_id );
		}


		/**
		 * Add the request a quote list as smart tag
		 *
		 * @param array $tags List of smart tags.
		 */
		public function add_list_tag( $tags ) {
			$tags[ $this->tag_name ] = esc_html_x( 'YITH Request a Quote for WooCommerce: List of products', 'add a new smart tag to wpform email editor', 'yith-woocommerce-request-a-quote' );

			return $tags;
		}

		/**
		 * Process and parse smart tags.
		 *
		 * @param string       $content The string to preprocess.
		 * @param array        $form_data Form data and settings.
		 * @param string|array $fields Form fields.
		 * @param int|string   $entry_id Entry ID.
		 *
		 * @return string
		 * @since 3.1.0
		 */
		public function process_smart_tag( $content, $form_data, $fields = '', $entry_id = '' ) {

			if ( isset( $form_data['id'] ) && $this->get_selected_form_id() === $form_data['id'] ) {

				if ( ! empty( $content ) && strpos( '{' . $this->tag_name . '}', $content ) !== -1 ) {
					/**
					* APPLY_FILTERS: ywraq_wp_forms_template_html
					*
					* Select to use html (true) or plain (false) template for WP Forms email.
					* 
					* @param bool $value default true.
					* 
					*/
					$list    = yith_ywraq_get_email_template( apply_filters( 'ywraq_wp_forms_template_html', true ) );
					$content = str_replace( '{' . $this->tag_name . '}', $list, $content );
					// avoid the nl2br function to the message by wpforms.
					$content = str_replace( "\n", '', $content );

				}
			}

			return $content;
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

		/**
		 * Add current wpforms to javascript frontend localization.
		 *
		 * @param array $localize Localize array.
		 *
		 * @return mixed
		 */
		public function frontend_localize( $localize ) {
			$localize['wpforms'] = apply_filters( 'ywraq_inquiry_wpforms_id', $this->get_selected_form_id() );

			return $localize;
		}


		/**
		 * Trigger the order creation when the selected wpform is submitted.
		 *
		 * @param array $fields List of fields.
		 * @param array $entry Content submitted.
		 * @param array $formdata Form data.
		 *
		 * @return void
		 * @throws Exception Throws an Exception.
		 */
		public function create_order_before_send_email( $fields, $entry, $formdata ) {
			$this->formdata = $formdata;
			$this->entry    = $entry;
			YITH_YWRAQ_Order_Request()->ajax_create_order( false );
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

			if ( ! empty( $this->formdata ) && isset( $_REQUEST['wpforms'] ) ) { //phpcs:ignore
				$wpform = $this->entry['fields'];
				foreach ( $this->formdata['fields'] as $index => $defined_field ) {
					$value  = isset( $wpform[ $index ] ) ? $wpform[ $index ] : '';
					$wc_key = empty( $defined_field['connect_to'] ) ? false : $defined_field['connect_to'];
					if ( 'name' === $defined_field['type'] ) {
						$wc_key2 = empty( $defined_field['connect_to2'] ) ? false : $defined_field['connect_to2'];

						if ( $wc_key ) {
							$args[ $wc_key ] = $value['first'];
						}

						if ( $wc_key2 ) {
							$args[ $defined_field['connect_to2'] ] = $value['last'];
						}

						$value = trim( $value['first'] . ' ' . $value['last'] );
						$name  = empty( $name ) ? $value : $name;

					} else {
						if ( 'email' === $defined_field['type'] ) {
							$email = empty( $email ) ? $value : $email;
						}

						$args[ $wc_key ] = $value;
					}

					$value                = is_array( $value ) ? implode( ', ', $value ) : $value;
					$label = isset( $defined_field['label'] ) ? $defined_field['label'] : '';
					$other_email_content .= sprintf( '<strong>%s</strong>: %s<br>', $label , $value );
					$key                  = apply_filters( 'ywraq_other_email_content_key', $label, $value );
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
			$args['user_email']   = isset( $args['billing_email'] ) ? $args['billing_email'] : $email;
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


	}


	/**
	 * Unique access to instance of YWRAQ_WPForms class
	 *
	 * @return \YWRAQ_WPForms
	 */
	function YWRAQ_WPForms() { //phpcs:ignore
		return YWRAQ_WPForms::get_instance();
	}
}
