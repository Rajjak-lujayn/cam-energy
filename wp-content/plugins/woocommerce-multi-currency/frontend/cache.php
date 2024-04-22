<?php

/**
 * Class WOOMULTI_CURRENCY_Frontend_Update
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WOOMULTI_CURRENCY_Frontend_Cache {
	protected $settings;

	public function __construct() {

//		$this->settings = new WOOMULTI_CURRENCY_Data();
		$this->settings = WOOMULTI_CURRENCY_Data::get_ins();
		if ( $this->settings->get_enable() ) {
//			add_action( 'wp_loaded', array( $this, 'wp_loaded' ) );
//			add_action( 'init', array( $this, 'clear_browser_cache' ) );
			add_action( 'wp_ajax_wmc_get_products_price', array( $this, 'get_products_price' ) );
			add_action( 'wp_ajax_nopriv_wmc_get_products_price', array( $this, 'get_products_price' ) );
		}
	}

	/**
	 * Clear cache browser
	 */
	public function clear_browser_cache() {
		if ( isset( $_GET['wmc-currency'] ) ) {
			header( "Cache-Control: no-cache, must-revalidate" );
			header( "Expires: Mon, 26 Jul 1997 05:00:00 GMT" );
			header( "Content-Type: application/xml; charset=utf-8" );
		}
	}

	/**
	 * Clear cache
	 *
	 * @param $cart
	 */
	public function wp_loaded() {
		/*Clear cache with W3 total cache*/
		if ( is_plugin_active( 'w3-total-cache/w3-total-cache.php' ) && isset( $_GET['wmc-currency'] ) ) {
			w3tc_flush_all();
		}

	}

	public function get_products_price() {

		$pids      = ! empty( $_POST['pids'] ) ? wc_clean( $_POST['pids'] ) : [];
		$shortcode = ! empty( $_POST['shortcode'] ) ? sanitize_text_field( $_POST['shortcode'] ) : '';
		$flag_size = ! empty( $_POST['flag_size'] ) ? sanitize_text_field( $_POST['flag_size'] ) : '';
		$result    = array();

		if ( ! empty( $pids ) ) {
			foreach ( $pids as $pid ) {
				$product                  = wc_get_product( $pid );
				$result['prices'][ $pid ] = $product->get_price_html();
			}
		}

		$current_currency           = $this->settings->get_current_currency();
		$result['current_currency'] = $current_currency;
		$result['current_country']  = strtolower( $this->settings->get_country_data( $current_currency )['code'] );

		if ( $shortcode ) {
			$result['shortcode'] = do_shortcode( "[woo_multi_currency_{$shortcode} flag_size={$flag_size}]" );
		}

		if ( ! empty( $_POST['exchange'] ) ) {
			$exchange_sc  = [];
			$exchange_arr = wc_clean( $_POST['exchange'] );
			foreach ( $exchange_arr as $ex ) {
				$exchange_sc[] = array_merge( $ex, [ 'shortcode' => do_shortcode( "[woo_multi_currency_exchange price={$ex['value']} currency={$ex['currency']}]" ) ] );
			}
			$result['exchange'] = $exchange_sc;
		}

		wp_send_json_success( $result );
		wp_die();
	}


}