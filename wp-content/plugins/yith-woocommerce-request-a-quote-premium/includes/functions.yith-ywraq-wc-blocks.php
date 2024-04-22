<?php

require YITH_YWRAQ_INC . '/builders/wc-blocks/src/Payments/Integrations/YwraqQuote.php';

use Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry;
use Automattic\WooCommerce\Blocks\Package;

if ( 'yes' === get_option( 'ywraq_show_button_on_checkout_page', 'no' ) ) {
	if ( ! function_exists( 'ywraq_add_extension_woocommerce_blocks_support' ) ) {
		/**
		 * Extend payment method to WC Checkout Blocks
		 *
		 * @return void
		 * @throws Exception
		 */
		function ywraq_add_extension_woocommerce_blocks_support() {

			if ( class_exists( 'Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType' ) ) {

				add_action(
					'woocommerce_blocks_payment_method_type_registration',
					function ( PaymentMethodRegistry $payment_method_registry ) {
						$container = Package::container();
						// registers as shared instance.
						$container->register(
							YwraqQuote::class,
							function () {
								return new YwraqQuote();
							}
						);
						$payment_method_registry->register(
							$container->get( YwraqQuote::class )
						);
					}
				);
			}
		}
	}

	add_action( 'woocommerce_blocks_loaded', 'ywraq_add_extension_woocommerce_blocks_support' );
}


if ( ! function_exists( 'ywraq_check_blockified_single_templates' ) ) {
	/**
	 * Check if the new version of plugin framework that supports wc blocks is set
	 *
	 * @return bool
	 */
	function ywraq_check_blockified_single_templates() {
		$blockified = false;
		if ( version_compare( WC()->version, '7.9', '>=' ) && function_exists( 'yith_plugin_fw_wc_is_using_block_template_in_single_product' ) ) {
			$blockified = yith_plugin_fw_wc_is_using_block_template_in_single_product();
		}

		return $blockified;
	}
}

