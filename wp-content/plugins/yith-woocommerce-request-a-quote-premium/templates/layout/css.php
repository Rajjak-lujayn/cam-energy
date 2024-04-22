<?php
/**
 * This file belongs to the YIT Plugin Framework.
 *
 * This source file is subject to the GNU GENERAL PUBLIC LICENSE (GPL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.gnu.org/licenses/gpl-3.0.txt
 *
 * @package YITH\RequestAQuote
 * @since   1.0.0
 * @author  YITH <plugins@yithemes.com>
 */

if ( ! defined( 'ABSPATH' ) || ! defined( 'YITH_YWRAQ_VERSION' ) ) {
	exit; // Exit if accessed directly .
}

$ywraq_raq_color      = get_option(
	'ywraq_add_to_quote_button_color',
	array(
		'bg_color'           => '#0066b4',
		'bg_color_hover'     => '#044a80',
		'border_color'       => '#0066b4',
		'border_color_hover' => '#044a80',
		'color'              => '#ffffff',
		'color_hover'        => '#ffffff',
	)
);
$ywraq_checkout_color = get_option(
	'ywraq_raq_checkout_button_color',
	array(
		'bg_color'           => '#0066b4',
		'bg_color_hover'     => '#044a80',
		'border_color'       => '#0066b4',
		'border_color_hover' => '#044a80',
		'color'              => '#ffffff',
		'color_hover'        => '#ffffff',
	)
);

$ywraq_accept_color = get_option(
	'ywraq_raq_accept_button_color',
	array(
		'bg_color'           => '#0066b4',
		'bg_color_hover'     => '#044a80',
		'border_color'       => '#0066b4',
		'border_color_hover' => '#044a80',
		'color'              => '#ffffff',
		'color_hover'        => '#ffffff',
	)
);

$ywraq_reject_color = get_option(
	'ywraq_raq_reject_button_color',
	array(
		'bg_color'           => 'transparent',
		'bg_color_hover'     => '#CC2B2B',
		'border_color'       => '#CC2B2B',
		'border_color_hover' => '#CC2B2B',
		'color'              => '#CC2B2B',
		'color_hover'        => '#ffffff',
	)
);

// retro compatibility with previous version 4.0.
$ywraq_raq_color['border_color']            = isset( $ywraq_raq_color['border_color'] ) ? $ywraq_raq_color['border_color'] : $ywraq_raq_color['bg_color'];
$ywraq_raq_color['border_color_hover']      = isset( $ywraq_raq_color['border_color_hover'] ) ? $ywraq_raq_color['border_color_hover'] : $ywraq_raq_color['bg_color_hover'];
$ywraq_checkout_color['border_color']       = isset( $ywraq_checkout_color['border_color'] ) ? $ywraq_checkout_color['border_color'] : $ywraq_checkout_color['bg_color'];
$ywraq_checkout_color['border_color_hover'] = isset( $ywraq_checkout_color['border_color_hover'] ) ? $ywraq_checkout_color['border_color_hover'] : $ywraq_checkout_color['bg_color_hover'];

$css = ":root {
		--ywraq_layout_button_bg_color: {$ywraq_raq_color['bg_color']};
		--ywraq_layout_button_bg_color_hover: {$ywraq_raq_color['bg_color_hover']};
		--ywraq_layout_button_border_color: {$ywraq_raq_color['border_color']};
		--ywraq_layout_button_border_color_hover: {$ywraq_raq_color['border_color_hover']};
		--ywraq_layout_button_color: {$ywraq_raq_color['color']};
		--ywraq_layout_button_color_hover: {$ywraq_raq_color['color_hover']};
		
		--ywraq_checkout_button_bg_color: {$ywraq_checkout_color['bg_color']};
		--ywraq_checkout_button_bg_color_hover: {$ywraq_checkout_color['bg_color_hover']};
		--ywraq_checkout_button_border_color: {$ywraq_checkout_color['border_color']};
		--ywraq_checkout_button_border_color_hover: {$ywraq_checkout_color['border_color_hover']};
		--ywraq_checkout_button_color: {$ywraq_checkout_color['color']};
		--ywraq_checkout_button_color_hover: {$ywraq_checkout_color['color_hover']};
		
		--ywraq_accept_button_bg_color: {$ywraq_accept_color['bg_color']};
		--ywraq_accept_button_bg_color_hover: {$ywraq_accept_color['bg_color_hover']};
		--ywraq_accept_button_border_color: {$ywraq_accept_color['border_color']};
		--ywraq_accept_button_border_color_hover: {$ywraq_accept_color['border_color_hover']};
		--ywraq_accept_button_color: {$ywraq_accept_color['color']};
		--ywraq_accept_button_color_hover: {$ywraq_accept_color['color_hover']};
		
		--ywraq_reject_button_bg_color: {$ywraq_reject_color['bg_color']};
		--ywraq_reject_button_bg_color_hover: {$ywraq_reject_color['bg_color_hover']};
		--ywraq_reject_button_border_color: {$ywraq_reject_color['border_color']};
		--ywraq_reject_button_border_color_hover: {$ywraq_reject_color['border_color_hover']};
		--ywraq_reject_button_color: {$ywraq_reject_color['color']};
		--ywraq_reject_button_color_hover: {$ywraq_reject_color['color_hover']};
		}		
";


$show_button_near_add_to_cart = get_option( 'ywraq_show_button_near_add_to_cart', 'no' );
if ( yith_plugin_fw_is_true( $show_button_near_add_to_cart ) ) {
	$css .= '.woocommerce.single-product button.single_add_to_cart_button.button {margin-right: 5px;}
	.woocommerce.single-product .product .yith-ywraq-add-to-quote {display: inline-block; vertical-align: middle;margin-top: 5px;}
	';
	if ( defined( 'YITH_PROTEO_VERSION' ) ) {
		$css .= '.theme-yith-proteo .product .yith-ywraq-add-to-quote{ margin-bottom:0}';
		$css .= '
.theme-yith-proteo .ywraq-form-table-wrapper .yith-ywraq-mail-form-wrapper {
    background: #f5f5f5;
}

.theme-yith-proteo a.add-request-quote-button.button {
    color: #fff;
}

.theme-yith-proteo #yith-ywraq-mail-form input[type=text],
.theme-yith-proteo #yith-ywraq-mail-form input[type=email],
.theme-yith-proteo #yith-ywraq-mail-form input[type=password],
.theme-yith-proteo #yith-ywraq-mail-form select,
.theme-yith-proteo #yith-ywraq-mail-form textarea {
    background-color: #fff;
    border: 1px solid #ccc;
}

.theme-yith-proteo a.add-request-quote-button.button {
    font-size: var(--proteo-single_product_add_to_cart_button_font_size, 1.25rem);
    font-weight: bold;
    margin-bottom: 15px;
    margin-right: 15px;
    margin-top: -5px;
    padding: 0.9375rem 2.8125rem;
    text-align: center;
    text-transform: uppercase;
    vertical-align: top;
}
';
	}
}

return apply_filters( 'ywraq_custom_css', $css );
