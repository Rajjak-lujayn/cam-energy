<?php
/**
 * Show widget
 *
 * This template can be overridden by copying it to yourtheme/woo-currency/woo-currency_widget.php.
 *
 * @author        Cuong Nguyen
 * @package       Woo-currency/Templates
 * @version       1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
$currencies       = $settings->get_list_currencies();
$current_currency = $settings->get_current_currency();
$links            = $settings->get_links();
$currency_name    = get_woocommerce_currencies();
$class_action     = $onchange = '';
if ( $settings->enable_switch_currency_by_js() ) {
	$class_action = 'wmc-select-currency-js';
} else {
	$onchange = "this.options[this.selectedIndex].value && (window.location = this.options[this.selectedIndex].value)";
}
?>
<div class="woocommerce-multi-currency shortcode">
    <div class="wmc-currency">
        <select class="wmc-nav <?php echo esc_html( $class_action ); ?>" onchange="<?php echo $onchange ?>">
			<?php
			foreach ( $links as $code => $link ) {
				$value = $settings->enable_switch_currency_by_js() ? esc_html( $code ) : esc_url( $link );
				$name  = $shortcode == 'default' ? $currency_name[ $code ] : ( $shortcode == 'listbox_code' ? $code : '' );
				?>
                <option data-currency="<?php echo esc_attr( $code ) ?>" <?php selected( $current_currency, $code ) ?> value="<?php echo $value ?>">
					<?php echo esc_html( $name ) ?>
                </option>
			<?php } ?>
        </select>
    </div>
</div>
