<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**Must modify woo-function.php of Woo Discount rules
 * Class FlycartWoocommerceVersion, method get_price_html, get_price
 * $product->get_price() =>$product->get_price('edit')
 * $product->get_regular_price() =>$product->get_regular_price('edit')
 * $product->get_sale_price() =>$product->get_sale_price('edit')
 * Class WOOMULTI_CURRENCY_Plugin_Woo_Discount_Rules
 */
class WOOMULTI_CURRENCY_Plugin_Woo_Discount_Rules {
	protected $settings;
	protected $convert;

	public function __construct() {
		$this->settings = WOOMULTI_CURRENCY_Data::get_ins();
		$this->convert  = false;
		if ( $this->settings->get_enable() ) {
			add_filter( 'woo_discount_rules_on_display_discount_priced_in_cart_item_subtotal', array(
				$this,
				'woo_discount_rules_on_display_discount_priced_in_cart_item_subtotal'
			), 10, 4 );
		}
	}

	protected function getYouSavedContent( $total_discounted_price ) {
		$subtotal_additional_text = '<span class="wdr_you_saved_con">';
		$config                   = new FlycartWooDiscountBase();
		$display_you_saved_string = $config->getConfigData( 'display_you_saved_text_value', " You saved: {{total_discount_price}}" );
		$display_you_saved_string = str_replace( '{{total_discount_price}}', '%s', $display_you_saved_string );
		$subtotal_additional_text .= sprintf( esc_html__( $display_you_saved_string, 'woo-discount-rules' ), $total_discounted_price );
		$subtotal_additional_text .= '</span>';

		return $subtotal_additional_text;
	}

	public function woo_discount_rules_on_display_discount_priced_in_cart_item_subtotal( $subtotal_additional_text, $woo_discount, $cart_item, $subtotal ) {
		if ( ! empty( $woo_discount['discount_amount_total'] ) ) {
			$total_discounted_price   = FlycartWoocommerceProduct::wc_price( wmc_get_price( $woo_discount['discount_amount_total'] ) );
			$subtotal_additional_text = $this->getYouSavedContent( $total_discounted_price );
			$subtotal                 = $subtotal_additional_text;
		}
		return $subtotal;
	}
}