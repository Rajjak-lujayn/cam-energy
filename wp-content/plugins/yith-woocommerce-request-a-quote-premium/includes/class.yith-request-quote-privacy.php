<?php //phpcs:ignore WordPress.Files.FileName.InvalidClassFileName
/**
 * Implements the YITH_Request_Quote_Privacy class.
 *
 * @class   YITH_Request_Quote_Privacy
 * @package YITH\RequestAQuote
 * @since   2.0.4
 * @author  YITH <plugins@yithemes.com>
 */

defined( 'ABSPATH' ) || exit;


if ( ! class_exists( 'YITH_Request_Quote_Privacy' ) ) {

	/**
	 * Class YITH_Request_Quote_Privacy
	 */
	class YITH_Request_Quote_Privacy {

		/**
		 * Single instance of the class
		 *
		 * @var YITH_Request_Quote_Privacy
		 */
		protected static $instance;

		/**
		 * Array with the list of meta to export
		 *
		 * @var array
		 */
		public $raq_exporter_data;

		/**
		 * Array with the list of meta to erase
		 *
		 * @var array
		 */
		public $raq_eraser_data;


		/**
		 * Returns single instance of the class
		 *
		 * @return YITH_Request_Quote_Privacy
		 * @since 2.0.4
		 */
		public static function get_instance() {
			return ! is_null( self::$instance ) ? self::$instance : self::$instance = new self();
		}

		/**
		 * Constructor
		 *
		 * Initialize plugin and registers actions and filters to be used
		 *
		 * @since  2.0.4
		 */
		public function __construct() {
			$this->raq_eraser_data   = $this->get_privacy_eraser_personal_data_props();
			$this->raq_exporter_data = $this->get_privacy_export_personal_data_props();

			// erase.
			add_action( 'woocommerce_privacy_before_remove_order_personal_data', array( $this, 'remove_order_personal_data' ), 10, 1 );

			// export.
			add_filter( 'woocommerce_privacy_export_order_personal_data_props', array( $this, 'add_privacy_export_order_personal_data_fields' ), 10, 2 );
			add_filter( 'woocommerce_privacy_export_order_personal_data_prop', array( $this, 'export_order_personal_data_prop' ), 10, 3 );

		}

		/**
		 * Return the value of personal data.
		 *
		 * @param mixed    $value .
		 * @param string   $prop .
		 * @param WC_Order $order .
		 *
		 * @return mixed
		 *
		 * @since 2.0.4
		 */
		public function export_order_personal_data_prop( $value, $prop, $order ) {
			if ( isset( $this->raq_exporter_data[ $prop ] ) ) {
				$value = $order->get_meta( $prop );
			}

			return $value;
		}

		/**
		 * Export personal data
		 *
		 * @param string   $props_to_export .
		 * @param WC_Order $order .
		 *
		 * @return array
		 * @since 2.0.4
		 */
		public function add_privacy_export_order_personal_data_fields( $props_to_export, $order ) {
			$status = $order->get_meta( 'ywraq_raq_status' );

			if ( ! empty( $status ) ) {
				$props_to_export = array_merge( $props_to_export, $this->raq_exporter_data );
			}

			return $props_to_export;
		}

		/**
		 * Remove personal data.
		 *
		 * @param WC_Order $order .
		 *
		 * @return bool|void
		 * @since 2.0.4
		 */
		public function remove_order_personal_data( $order ) {
			$status      = $order->get_meta( 'ywraq_raq_status' );
			$meta_values = array();

			if ( empty( $status ) ) {
				return false;
			}

			$props_to_remove = $this->raq_eraser_data;
			if ( $props_to_remove ) {
				foreach ( $props_to_remove as $prop => $data_type ) {
					// Get the current value in edit context.
					$value = $order->get_meta_data( $prop );
					// If the value is empty, it does not need to be anonymized.
					if ( empty( $value ) || empty( $data_type ) ) {
						continue;
					}

					if ( function_exists( 'wp_privacy_anonymize_data' ) ) {
						$anon_value = wp_privacy_anonymize_data( $data_type, $value );
					} else {
						$anon_value = '';
					}

					$meta_values[ $prop ] = apply_filters( 'woocommerce_privacy_remove_order_personal_data_prop_value', $anon_value, $prop, $value, $data_type, $order );
				}

				if ( is_array( $meta_values ) ) {
					foreach ( $meta_values as $key => $value ) {
						$order->update_meta_data( $key, $value );
					}
				}
			}
		}


		/**
		 * Return the personal data to export.
		 *
		 * @return array
		 * @since 2.0.4
		 */
		public function get_privacy_export_personal_data_props() {
			/**
			 * APPLY_FILTERS:ywraq_privacy_export_personal_data_props
			 *
			 * Filter the personal data properties
			 *
			 * @param   array  $data_props  List of personal data properties
			 *
			 * @return array
			 */
			return apply_filters(
				'ywraq_privacy_export_personal_data_props',
				array(
					'ywraq_customer_name'       => esc_html__( 'Request a quote - Customer Name', 'yith-woocommerce-request-a-quote' ),
					'ywraq_customer_email'      => esc_html__( 'Request a quote - Customer Email', 'yith-woocommerce-request-a-quote' ),
					'ywraq_customer_message'    => esc_html__( 'Request a quote - Customer Message', 'yith-woocommerce-request-a-quote' ),
					'ywraq_other_email_content' => esc_html__( 'Request a quote - Email Content', 'yith-woocommerce-request-a-quote' ),
				)
			);
		}

		/**
		 * Return the list of personal data to remove.
		 *
		 * @return array
		 * @since 2.0.4
		 */
		public function get_privacy_eraser_personal_data_props() {
			/**
			 * APPLY_FILTERS:ywraq_privacy_eraser_personal_data_props
			 *
			 * Filter the personal data properties to erase
			 *
			 * @param   array  $data_props  List of personal data properties.
			 *
			 * @return array
			 */
			return apply_filters(
				'ywraq_privacy_eraser_personal_data_props',
				array(
					'ywraq_customer_name'       => 'text',
					'ywraq_customer_email'      => 'email',
					'ywraq_customer_message'    => 'longtext',
					'_raq_request'              => 'longtext',
					'ywraq_other_email_fields'  => 'longtext',
					'ywraq_other_email_content' => 'longtext',
				)
			);
		}

	}
}

/**
 * Unique access to instance of YITH_Request_Quote_Privacy class
 *
 * @return YITH_Request_Quote_Privacy
 */
function YITH_Request_Quote_Privacy() { //phpcs:ignore
	return YITH_Request_Quote_Privacy::get_instance(); //phpcs:ignore
}

YITH_Request_Quote_Privacy();
