<?php


use Automattic\WooCommerce\Blocks\Assets\Api;
use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;

/**
 * YWRAQ Quote payment method integration
 *
 * @since 4.16.0
 */
final class YwraqQuote extends AbstractPaymentMethodType {
	/**
	 * Payment method name/id/slug (matches id in WC_Gateway_BACS in core).
	 *
	 * @var string
	 */
	protected $name = 'yith-request-a-quote';

	/**
	 * An instance of the Asset Api
	 *
	 * @var Api
	 */
	private $asset_api;

	/**
	 * Constructor
	 *
	 * @param   Api  $asset_api  An instance of Api.
	 */
	public function __construct() {

	}

	/**
	 * Initializes the payment method type.
	 */
	public function initialize() {
		$this->settings = array(
			'title'       => apply_filters( 'ywraq_payment_method_label', esc_html__( 'YITH Request a Quote', 'yith-woocommerce-request-a-quote' ) ),
			'description' => esc_html__( 'Allows to request a quote at checkout.', 'yith-woocommerce-request-a-quote' )
		);
	}

	/**
	 * Returns if this payment method should be active. If false, the scripts will not be enqueued.
	 *
	 * @return boolean
	 */
	public function is_active() {
		return true; //filter_var( $this->get_setting( 'enabled', false ), FILTER_VALIDATE_BOOLEAN );
	}

	/**
	 * Returns an array of scripts/handles to be registered for this payment method.
	 *
	 * @return array
	 */
	public function get_payment_method_script_handles() {
		wp_register_script(
			'yith-request-a-quote-button-on-checkout',
			YITH_YWRAQ_URL . '/dist/wc-blocks/index.js',
			array( 'wc-settings', 'wc-blocks-registry' ),
			time(),
			true
		);
		wp_register_script(
			'wc-payment-method-yith-request-a-quote',
			YITH_YWRAQ_URL . '/dist/wc-blocks/wc-payment-method-yith-request-a-quote/index.js',
			array( 'wc-settings', 'wc-blocks-registry', 'yith-request-a-quote-button-on-checkout' ),
			time(),
			true
		);

		wp_localize_script(
			'wc-payment-method-yith-request-a-quote',
			'ywraq_gateway_settings',
			array(
				'title'       => $this->get_setting( 'title' ),
				'description' => $this->get_setting( 'description' ),
			)
		);

		return [ 'wc-payment-method-yith-request-a-quote' ];
	}

	/**
	 * Returns an array of key=>value pairs of data made available to the payment methods script.
	 *
	 * @return array
	 */
	public function get_payment_method_data() {
		return [
			'title'       => $this->get_setting( 'title' ),
			'description' => $this->get_setting( 'description' ),
			'supports'    => $this->get_supported_features(),
		];
	}
}
