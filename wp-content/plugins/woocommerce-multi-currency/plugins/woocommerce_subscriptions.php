<?php

/**
 * Class WOOMULTI_CURRENCY_Plugin_Yith_Product_Bundles
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WOOMULTI_CURRENCY_Plugin_WooCommerce_Subscriptions {
	protected $settings;

	public function __construct() {
//		$this->settings = new WOOMULTI_CURRENCY_Data();
		$this->settings = WOOMULTI_CURRENCY_Data::get_ins();
		if ( $this->settings->get_enable() ) {
			add_filter( 'woocommerce_subscriptions_product_sign_up_fee', array( $this, 'woocommerce_subscriptions_product_sign_up_fee' ) );
		}
	}

	/**
	 * Simple subscription
	 *
	 * @param $price
	 *
	 * @return mixed
	 */
	public function woocommerce_subscriptions_product_sign_up_fee( $price ) {
		return wmc_get_price( $price );
	}
}