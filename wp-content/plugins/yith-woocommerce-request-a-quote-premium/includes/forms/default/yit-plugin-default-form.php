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

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Implements the YIT_Plugin_Default_Form class.
 *
 * @class   YIT_Plugin_Default_Form
 * @since   1.0.0
 * @author  YITH <plugins@yithemes.com>
 * @package YITH\RequestAQuote
 */
if ( ! class_exists( 'YIT_Plugin_Default_Form' ) ) {

	/**
	 * Class YIT_Plugin_Default_Form
	 */
	class YIT_Plugin_Default_Form {

		/**
		 * Single instance of the class
		 *
		 * @var \YIT_Plugin_Default_Form
		 */
		protected static $instance;


		/**
		 * Current option id
		 *
		 * @var string
		 */
		protected $option_id;


		/**
		 * Returns single instance of the class
		 *
		 * @return \YIT_Plugin_Default_Form
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
			add_action( 'init', array( $this, 'manage_default_field_form' ) );
			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_styles_scripts' ), 25 );
		}


		/**
		 * Enqueue styles and scripts
		 *
		 * @access public
		 * @return void
		 * @since  1.0.0
		 */
		public function enqueue_styles_scripts() {
			$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

			wp_register_script(
				'yith_default_form_field',
				YITH_YWRAQ_ASSETS_URL . '/js/yith-default-form-field' . $suffix . '.js',
				array(
					'jquery',
					'jquery-ui-dialog',
					'yith-plugin-fw-fields',
				),
				YITH_YWRAQ_VERSION,
				true
			);

			wp_register_style( 'yith_default_form_field', YITH_YWRAQ_ASSETS_URL . '/css/yith-default-form-field.css', '', YITH_YWRAQ_VERSION );

			wp_localize_script(
				'yith_default_form_field',
				'yith_default_form_field',
				array(
					'popup_title_add'  => __( 'Add field', 'yith-woocommerce-request-a-quote' ),
					'popup_title_edit' => __( 'Edit field', 'yith-woocommerce-request-a-quote' ),
					'add'             => __( 'Add field to form', 'yith-woocommerce-request-a-quote' ),
					'edit'             => __( 'Save form field', 'yith-woocommerce-request-a-quote' ),
					'confirmChoice'    => esc_html_x( 'Continue', 'Label button of a dialog popup', 'yith-woocommerce-request-a-quote' ),
					'cancel'           => esc_html_x( 'Cancel', 'Label button of a dialog popup', 'yith-woocommerce-request-a-quote' ),
				)
			);

		}

		/**
		 * Return the option saved on database
		 *
		 * @return array
		 */
		private function get_saved_option() {
			return get_option( $this->option_id, array() );
		}

		/**
		 * Save the option inside the database
		 *
		 * @param array $option Option array to save.
		 * @return void
		 */
		private function save_option( $option ) {
			update_option( $this->option_id, $option );
		}

		/**
		 * Save the plugin option.
		 */
		public function manage_default_field_form() {

			$posted = $_REQUEST; //phpcs:ignore

			if ( ! isset( $posted['yit_default_form'], $posted['request'] ) ) {
				return;
			}

			$this->option_id = wc_clean( $posted['yit_default_form'] );
			$request         = 'handle_form_' . wc_clean( $posted['request'] );

			// remove unnecessary elements.
			unset( $posted['yit_default_form'], $posted['request'] );

			$this->$request( $posted, $this->get_saved_option() );

		}

		/**
		 * Save the new field inside the option.
		 *
		 * @param array $posted Posted info.
		 * @param array $option Saved option.
		 *
		 * @return void;
		 */
		public function handle_form_save( $posted, $option ) {

			if ( isset( $posted['name'] ) ) {

				$name = strtolower( trim( $posted['name'] ) );
				$name = str_replace( ' ', '_', $name );
				unset( $posted['name'] );
				$posted['id']       = $name;
				$posted['enabled']  = isset( $posted['enabled'] ) ? $posted['enabled'] : 'yes';
				$posted['required'] = isset( $posted['required'] ) ? $posted['required'] : 'no';
				$posted['checked']  = isset( $posted['checked'] ) ? $posted['checked'] : 'no';

				$posted['class'] = isset( $posted['class'] ) ? explode( ',', $posted['class'] ) : array();
				$posted['class'] = array_filter( $posted['class'] );

				$posted['label_class'] = isset( $posted['label_class'] ) ? explode( ',', $posted['label_class'] ) : array();
				$posted['label_class'] = array_filter( $posted['label_class'] );
				$posted['options']     = isset( $posted['options'] ) && ! empty( $posted['options'] ) ? $this->create_options_array( $posted['options'], $posted['type'] ) : array();

				// Remove slashes to values with quotes.
				foreach ( $posted as $key => $value ) {
					if ( is_string( $value ) ) {
						$posted[ $key ] = stripslashes( $value );
					}
				}

				$option[ $name ] = $posted;
			}

			$this->save_option( $option );
		}


		/**
		 * Save the new field inside the option.
		 *
		 * @param array $posted Posted info.
		 * @param array $option Saved option.
		 *
		 * @return void;
		 */
		public function handle_form_activate( $posted, $option ) {

			if ( isset( $posted['row'], $posted['activated'], $option[ $posted['row'] ] ) ) {
				$posted['enabled']                   = isset( $posted['enabled'] ) ? $posted['enabled'] : 'yes';
				$option[ $posted['row'] ]['enabled'] = $posted['activated'];
			}

			$this->save_option( $option );
		}

		/**
		 * Duplicate a field.
		 *
		 * @param array $posted Posted info.
		 * @param array $option Saved option.
		 *
		 * @return void;
		 */
		public function handle_form_duplicate( $posted, $option ) {

			if ( isset( $posted['row'] ) ) {
				$row = $posted['row'];

				if ( isset( $option[ $row ] ) ) {
					$keys    = array_keys( $option );
					$new_row = $option[ $row ];
					if ( isset( $new_row['standard'] ) ) {
						unset( $new_row['standard'] );
					}
					$split     = explode( '_', $row );
					$end       = array_pop( $split );
					$increment = 1;
					if ( is_numeric( $end ) ) {
						$row       = implode( '_', $split );
						$increment = $end + 1;
					}

					while ( in_array( $row . '_' . $increment, $keys, true ) ) {
						$increment++;
					}

					$option[ $row . '_' . $increment ] = $new_row;
				}
			}

			$this->save_option( $option );
		}

		/**
		 * Cancel the field from the form.
		 *
		 * @param array $posted Posted info.
		 * @param array $option Saved option.
		 *
		 * @return void;
		 */
		public function handle_form_cancel( $posted, $option ) {

			if ( isset( $posted['row'] ) ) {
				$row = $posted['row'];
				if ( isset( $option[ $row ] ) ) {
					unset( $option[ $row ] );
				}
			}

			$this->save_option( $option );
		}

		/**
		 * Restore the default form fields.
		 *
		 * @param array $posted Posted info.
		 * @param array $option Saved option.
		 *
		 * @return void;
		 */
		public function handle_form_restore( $posted, $option ) {

			if ( isset( $posted['callback'] ) && function_exists( $posted['callback'] ) ) {
				$option = call_user_func_array( $posted['callback'], array() );
			}

			$this->save_option( $option );
		}

		/**
		 * Order the fields of the form.
		 *
		 * @param array $posted Posted info.
		 * @param array $option Saved option.
		 *
		 * @return void;
		 */
		public function handle_form_sort( $posted, $option ) {

			if ( isset( $posted['order'] ) ) {
				$new_option = array();
				foreach ( $posted['order'] as $key ) {
					if ( isset( $option[ $key ] ) ) {
						$new_option[ $key ] = $option[ $key ];
					}
				}

				$option = $new_option;
			}

			$this->save_option( $option );
		}


		/**
		 * Return the type of fields
		 *
		 * @return array
		 */
		public function get_field_types() {

			$types = array(
				'text'     => esc_html_x( 'Text', 'Text field', 'yith-woocommerce-request-a-quote' ),
				'email'    => esc_html_x( 'Email', 'Email field', 'yith-woocommerce-request-a-quote' ),
				'tel'      => esc_html_x( 'Phone', 'Phone number field', 'yith-woocommerce-request-a-quote' ),
				'textarea' => esc_html_x( 'Textarea', 'Textarea field', 'yith-woocommerce-request-a-quote' ),
				'radio'    => esc_html_x( 'Radio', 'Radio button field', 'yith-woocommerce-request-a-quote' ),
				'checkbox' => esc_html_x( 'Checkbox', 'Checkbox field', 'yith-woocommerce-request-a-quote' ),
				'select'   => esc_html_x( 'Select', 'Select field', 'yith-woocommerce-request-a-quote' ),
				'country'  => esc_html_x( 'Country', 'Select field for country', 'yith-woocommerce-request-a-quote' ),
				'state'    => esc_html_x( 'State', 'Field for State', 'yith-woocommerce-request-a-quote' ),
			);

			return apply_filters( 'yit_default_form_field_types', $types );
		}

		/**
		 * Create options array for field
		 *
		 * @access protected
		 *
		 * @param string $options .
		 * @param string $type .
		 *
		 * @return array
		 * @since  2.0.0
		 */
		protected function create_options_array( $options, $type = '' ) {

			$options_array = array();

			// first of all add empty options for placeholder if type is option.
			if ( 'select' === $type ) {
				$options_array[''] = '';
			}

			foreach ( $options as $option ) {
				// create key.
				if ( isset( $option['label'] ) ) {
					$key                   = 'radio' !== $type ? sanitize_title_with_dashes( $option['label'] ) : $option['label'];
					$options_array[ $key ] = stripslashes( $option['value'] );
				}
			}
			return $options_array;
		}

		/**
		 * Options
		 *
		 * @param array $options .
		 * @return mixed|string
		 */
		public function print_options_field( $options ) {
			$new_options = array();

			if ( $options ) {
				foreach ( $options as $key => $value ) {
					array_push( $new_options, $key . '::' . $value );
				}

				$options = implode( '|', $new_options );
			}

			return $options;
		}
	}
}

/**
 * Unique access to instance of YIT_Plugin_Default_Form class
 *
 * @return \YIT_Plugin_Default_Form
 */
function YIT_Plugin_Default_Form() { //phpcs:ignore
	return YIT_Plugin_Default_Form::get_instance();
}

YIT_Plugin_Default_Form();
