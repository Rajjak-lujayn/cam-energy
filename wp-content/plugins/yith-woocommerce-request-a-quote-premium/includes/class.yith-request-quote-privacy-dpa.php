<?php //phpcs:ignore WordPress.Files.FileName.InvalidClassFileName
/**
 *  Implements the YITH_YWRAQ_Privacy_DPA Class
 *
 * @package YITH\RequestAQuote
 * @since   2.0.4
 * @author  YITH <plugins@yithemes.com>
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'YITH_YWRAQ_Privacy_DPA' ) ) {
	/**
	 * Class YITH_YWRAQ_Privacy_DPA
	 * Privacy Class
	 *
	 */
	class YITH_YWRAQ_Privacy_DPA extends YITH_Privacy_Plugin_Abstract {

		/**
		 * YITH_YWRAQ_Privacy constructor.
		 */
		public function __construct() {
			parent::__construct( YITH_YWRAQ_PLUGIN_NAME );
		}

		/**
		 * Return the privacy message.
		 *
		 * @param string $section .
		 *
		 * @return string
		 */
		public function get_privacy_message( $section ) {
			$message = '';

			switch ( $section ) {
				case 'collect_and_store':
					$message = '<p>' . esc_html__( "While you visit our site, we'll track:", 'yith-woocommerce-request-a-quote' ) . '</p>' .
								'<ul>' .
								'<li>' . esc_html__( 'Products added to the quote list: these products will be used to offer you our best price.', 'yith-woocommerce-request-a-quote' ) . '</li>' .
								'<li>' . esc_html__( 'All fields in the quote request form: name, email and message content.', 'yith-woocommerce-request-a-quote' ) . '</li>' .
								'</ul>' .
								'<p class="privacy-policy-tutorial">' . esc_html__( 'Note: you should add here all the fields that appear in the quote request form.', 'yith-woocommerce-request-a-quote' ) . '</p>' .
								'<p>' . esc_html__( "We'll also use cookies to keep track of request a quote list while you're browsing our site.", 'yith-woocommerce-request-a-quote' ) . '</p>' .
								'<p class="privacy-policy-tutorial">' . esc_html__( 'Note: you may want to further detail your cookie policy, and link to that section from here.', 'yith-woocommerce-request-a-quote' ) . '</p>' .
								'<p>' . esc_html__( 'Send you information about your request:', 'yith-woocommerce-request-a-quote' ) . '</p>' .
								'<p>' . esc_html__( 'The answer to your quote request.', 'yith-woocommerce-request-a-quote' ) . '</p>' .
								'<p class="privacy-policy-tutorial">' . esc_html__( 'Note: all the fields in the quote request that have been filled by the user will be saved in the order details, so, they will be subject to WooCommerce plugin Privacy Policy.', 'yith-woocommerce-request-a-quote' ) . '</p>';
					break;
				case 'has_access':
					$message = '<p>' . __( 'Members of our team have access to the information you provide us. For example, both Administrators and Shop Managers can access:', 'yith-woocommerce-request-a-quote' ) . '</p>' .
								'<ul>' .
								'<li>' . __( "- the quote request you've submitted through our store.", 'yith-woocommerce-request-a-quote' ) . '</li>' .
								'<li>' . __( '- data entered in the quote request form.', 'yith-woocommerce-request-a-quote' ) . '</li>' .
								'</ul>' .
								'<p>' . __( 'Our team members have access to this information to send you the best offer.', 'yith-woocommerce-request-a-quote' ) . '</p>';
					break;
				default:
			}

			return $message;
		}
	}
}

new YITH_YWRAQ_Privacy_DPA();
