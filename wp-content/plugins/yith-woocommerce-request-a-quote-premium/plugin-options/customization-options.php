<?php
/**
 * This file belongs to the YIT Framework.
 *
 * This source file is subject to the GNU GENERAL PUBLIC LICENSE (GPL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.gnu.org/licenses/gpl-3.0.txt
 *
 * @package YITH\RequestAQuote
 * @since   3.0.0
 * @author  YITH <plugins@yithemes.com>
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) || ! defined( 'YITH_YWRAQ_VERSION' ) ) {
	exit;
}

if ( defined( 'YITH_PROTEO_VERSION' ) && apply_filters( 'yith_proteo_theme_color', true ) ) {
	$ywraq_layout_button_bg_color           = 'transparent';
	$ywraq_layout_button_bg_color_hover     = get_theme_mod( 'yith_proteo_main_color_shade', '#448a85' );
	$ywraq_layout_button_border_color       = get_theme_mod( 'yith_proteo_main_color_shade', '#448a85' );
	$ywraq_layout_button_border_color_hover = get_theme_mod( 'yith_proteo_main_color_shade', '#448a85' );
	$ywraq_layout_button_color              = get_theme_mod( 'yith_proteo_main_color_shade', '#448a85' );
	$ywraq_layout_button_color_hover        = '#ffffff';

	$ywraq_checkout_button_bg_color           = 'transparent';
	$ywraq_checkout_button_bg_color_hover     = get_theme_mod( 'yith_proteo_main_color_shade', '#448a85' );
	$ywraq_checkout_button_border_color       = get_theme_mod( 'yith_proteo_main_color_shade', '#448a85' );
	$ywraq_checkout_button_border_color_hover = get_theme_mod( 'yith_proteo_main_color_shade', '#448a85' );
	$ywraq_checkout_button_color              = get_theme_mod( 'yith_proteo_main_color_shade', '#448a85' );
	$ywraq_checkout_button_color_hover        = '#ffffff';

	$ywraq_accept_button_bg_color           = get_theme_mod( 'yith_proteo_main_color_shade', '#448a85' );
	$ywraq_accept_button_bg_color_hover     = get_theme_mod( 'yith_proteo_general_link_hover_color', '#007b75' );
	$ywraq_accept_button_border_color       = get_theme_mod( 'yith_proteo_main_color_shade', '#448a85' );
	$ywraq_accept_button_border_color_hover = get_theme_mod( 'yith_proteo_general_link_hover_color', '#007b75' );
	$ywraq_accept_button_color              = get_theme_mod( 'yith_proteo_button_style_2_text_color', '#ffffff' );
	$ywraq_accept_button_color_hover        = get_theme_mod( 'yith_proteo_button_style_2_text_color', '#ffffff' );
} else {
	$ywraq_raq_color = get_option(
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

	$ywraq_layout_button_bg_color           = $ywraq_raq_color['bg_color'];
	$ywraq_layout_button_bg_color_hover     = $ywraq_raq_color['bg_color_hover'];
	$ywraq_layout_button_border_color       = isset( $ywraq_raq_color['border_color'] ) ? $ywraq_raq_color['border_color'] : '#0066b4';
	$ywraq_layout_button_border_color_hover = isset( $ywraq_raq_color['border_color_hover'] ) ? $ywraq_raq_color['border_color_hover'] : '#044a80';
	$ywraq_layout_button_color              = $ywraq_raq_color['color'];
	$ywraq_layout_button_color_hover        = $ywraq_raq_color['color_hover'];

	$ywraq_checkout_button_bg_color           = $ywraq_checkout_color['bg_color'];
	$ywraq_checkout_button_bg_color_hover     = $ywraq_checkout_color['bg_color_hover'];
	$ywraq_checkout_button_border_color       = $ywraq_checkout_color['border_color'];
	$ywraq_checkout_button_border_color_hover = $ywraq_checkout_color['border_color_hover'];
	$ywraq_checkout_button_color              = $ywraq_checkout_color['color'];
	$ywraq_checkout_button_color_hover        = $ywraq_checkout_color['color_hover'];

	$ywraq_accept_button_bg_color           = $ywraq_checkout_color['bg_color'];
	$ywraq_accept_button_bg_color_hover     = $ywraq_checkout_color['bg_color_hover'];
	$ywraq_accept_button_border_color       = $ywraq_checkout_color['border_color'];
	$ywraq_accept_button_border_color_hover = $ywraq_checkout_color['border_color_hover'];
	$ywraq_accept_button_color              = $ywraq_checkout_color['color'];
	$ywraq_accept_button_color_hover        = $ywraq_checkout_color['color_hover'];
}

$ywraq_reject_button_bg_color           = 'transparent';
$ywraq_reject_button_bg_color_hover     = '#CC2B2B';
$ywraq_reject_button_border_color       = '#CC2B2B';
$ywraq_reject_button_border_color_hover = '#CC2B2B';
$ywraq_reject_button_color              = '#CC2B2B';
$ywraq_reject_button_color_hover        = '#ffffff';

$options = array(
	'customization' => array(
		'add_to_quote_button_settings' => array(
			'name' => __( '"Add to quote" button', 'yith-woocommerce-request-a-quote' ),
			'type' => 'title',
			'id'   => 'ywraq_add_to_quote_button_settings',
		),
		'show_btn_link' => array(
			'name'      => __( '"Add to quote" style', 'yith-woocommerce-request-a-quote' ),
			'desc'      => __( 'Choose the style for the "Add to quote" button or link.', 'yith-woocommerce-request-a-quote' ),
			'type'      => 'yith-field',
			'yith-type' => 'radio',
			'id'        => 'ywraq_show_btn_link',
			'options'   => array(
				'button' => __( 'Button', 'yith-woocommerce-request-a-quote' ),
				'link'   => __( 'Text Link', 'yith-woocommerce-request-a-quote' ),
			),
			'default'   => 'button',
		),
		'add_to_quote_button_color' => array(
			'name'         => __( '"Add to quote" colors', 'yith-woocommerce-request-a-quote' ),
			'type'         => 'yith-field',
			'yith-type'    => 'multi-colorpicker',
			'id'           => 'ywraq_add_to_quote_button_color',
			'class'        => 'ywraq_quote_button_color',
			'colorpickers' => array(
				array(
					'name'    => __( 'Background', 'yith-woocommerce-request-a-quote' ),
					'id'      => 'bg_color',
					'default' => $ywraq_layout_button_bg_color,
				),
				array(
					'name'    => __( 'Background hover', 'yith-woocommerce-request-a-quote' ),
					'id'      => 'bg_color_hover',
					'default' => $ywraq_layout_button_bg_color_hover,
				),
				array(
					'name'    => __( 'Border', 'yith-woocommerce-request-a-quote' ),
					'id'      => 'border_color',
					'default' => $ywraq_layout_button_border_color,
				),
				array(
					'name'    => __( 'Border hover', 'yith-woocommerce-request-a-quote' ),
					'id'      => 'border_color_hover',
					'default' => $ywraq_layout_button_border_color_hover,
				),
				array(
					'name'    => __( 'Text', 'yith-woocommerce-request-a-quote' ),
					'id'      => 'color',
					'default' => $ywraq_layout_button_color,
				),
				array(
					'name'    => __( 'Text Hover', 'yith-woocommerce-request-a-quote' ),
					'id'      => 'color_hover',
					'default' => $ywraq_layout_button_color_hover,
				),
			),
			'deps'         => array(
				'id'    => 'ywraq_show_btn_link',
				'value' => 'button',
			),
		),
		'add_to_quote_button_settings_end' => array(
			'type' => 'sectionend',
			'id'   => 'ywraq_add_to_quote_button_settings_end',
		),
		'request_quote_button_settings' => array(
			'name' => __( '"Request a quote" button', 'yith-woocommerce-request-a-quote' ),
			'type' => 'title',
			'id'   => 'ywraq_request_quote_button_settings',
		),
		'raq_checkout_button_style' => array(
			'name'      => __( '"Request a quote" style', 'yith-woocommerce-request-a-quote' ),
			'desc'      => __( 'Choose the style for the "Request a quote" button or link.', 'yith-woocommerce-request-a-quote' ),
			'type'      => 'yith-field',
			'yith-type' => 'radio',
			'id'        => 'ywraq_raq_checkout_button_style',
			'options'   => array(
				'button' => __( 'Button', 'yith-woocommerce-request-a-quote' ),
				'link'   => __( 'Text Link', 'yith-woocommerce-request-a-quote' ),
			),
			'default'   => 'button',
		),
		'raq_color' => array(
			'name'         => __( '"Request a quote" colors', 'yith-woocommerce-request-a-quote' ),
			'type'         => 'yith-field',
			'yith-type'    => 'multi-colorpicker',
			'id'           => 'ywraq_raq_checkout_button_color',
			'class'        => 'ywraq_quote_button_color',
			'colorpickers' => array(
				array(
					'name'    => __( 'Background', 'yith-woocommerce-request-a-quote' ),
					'id'      => 'bg_color',
					'default' => $ywraq_checkout_button_bg_color,
				),
				array(
					'name'    => __( 'Background hover', 'yith-woocommerce-request-a-quote' ),
					'id'      => 'bg_color_hover',
					'default' => $ywraq_checkout_button_bg_color_hover,
				),
				array(
					'name'    => __( 'Border', 'yith-woocommerce-request-a-quote' ),
					'id'      => 'border_color',
					'default' => $ywraq_checkout_button_border_color,
				),
				array(
					'name'    => __( 'Border hover', 'yith-woocommerce-request-a-quote' ),
					'id'      => 'border_color_hover',
					'default' => $ywraq_checkout_button_border_color_hover,
				),
				array(
					'name'    => __( 'Text', 'yith-woocommerce-request-a-quote' ),
					'id'      => 'color',
					'default' => $ywraq_checkout_button_color,
				),
				array(
					'name'    => __( 'Text Hover', 'yith-woocommerce-request-a-quote' ),
					'id'      => 'color_hover',
					'default' => $ywraq_checkout_button_color_hover,
				),
			),
			'deps'         => array(
				'id'    => 'ywraq_raq_checkout_button_style',
				'value' => 'button',
			),
		),
		'request_quote_button_settings_end' => array(
			'type' => 'sectionend',
			'id'   => 'ywraq_request_quote_button_settings_end',
		),
		'accept_pay_reject_quote_settings' => array(
			'name' => __( 'Accept, pay &amp; reject options', 'yith-woocommerce-request-a-quote' ),
			'type' => 'title',
			'id'   => 'ywraq_accept_pay_reject_quote_settings',
		),
		'raq_accept_button_style' => array(
			'name'      => __( '"Accept and Pay" style', 'yith-woocommerce-request-a-quote' ),
			'desc'      => __( 'Choose the style for the "Accept and Pay" button or link.', 'yith-woocommerce-request-a-quote' ),
			'type'      => 'yith-field',
			'yith-type' => 'radio',
			'id'        => 'ywraq_raq_accept_button_style',
			'options'   => array(
				'button' => __( 'Button', 'yith-woocommerce-request-a-quote' ),
				'link'   => __( 'Text Link', 'yith-woocommerce-request-a-quote' ),
			),
			'default'   => 'button',
		),
		'raq_accept_button_color' => array(
			'name'         => __( '"Accept and Pay" colors', 'yith-woocommerce-request-a-quote' ),
			'type'         => 'yith-field',
			'yith-type'    => 'multi-colorpicker',
			'id'           => 'ywraq_raq_accept_button_color',
			'class'        => 'ywraq_quote_button_color',
			'colorpickers' => array(
				array(
					'name'    => __( 'Background', 'yith-woocommerce-request-a-quote' ),
					'id'      => 'bg_color',
					'default' => $ywraq_accept_button_bg_color,
				),
				array(
					'name'    => __( 'Background hover', 'yith-woocommerce-request-a-quote' ),
					'id'      => 'bg_color_hover',
					'default' => $ywraq_accept_button_bg_color_hover,
				),
				array(
					'name'    => __( 'Border', 'yith-woocommerce-request-a-quote' ),
					'id'      => 'border_color',
					'default' => $ywraq_accept_button_border_color,
				),
				array(
					'name'    => __( 'Border hover', 'yith-woocommerce-request-a-quote' ),
					'id'      => 'border_color_hover',
					'default' => $ywraq_accept_button_border_color_hover,
				),
				array(
					'name'    => __( 'Text', 'yith-woocommerce-request-a-quote' ),
					'id'      => 'color',
					'default' => $ywraq_accept_button_color,
				),
				array(
					'name'    => __( 'Text Hover', 'yith-woocommerce-request-a-quote' ),
					'id'      => 'color_hover',
					'default' => $ywraq_accept_button_color_hover,
				),
			),
			'deps'         => array(
				'id'    => 'ywraq_raq_accept_button_style',
				'value' => 'button',
			),
		),
		'raq_reject_button_style' => array(
			'name'      => __( '"Reject" style', 'yith-woocommerce-request-a-quote' ),
			'desc'      => __( 'Choose the style for the "Reject" button or link.', 'yith-woocommerce-request-a-quote' ),
			'type'      => 'yith-field',
			'yith-type' => 'radio',
			'id'        => 'ywraq_raq_reject_button_style',
			'options'   => array(
				'button' => __( 'Button', 'yith-woocommerce-request-a-quote' ),
				'link'   => __( 'Text Link', 'yith-woocommerce-request-a-quote' ),
			),
			'default'   => 'button',
		),
		'raq_reject_button_color' => array(
			'name'         => __( '"Reject" colors', 'yith-woocommerce-request-a-quote' ),
			'type'         => 'yith-field',
			'yith-type'    => 'multi-colorpicker',
			'id'           => 'ywraq_raq_reject_button_color',
			'class'        => 'ywraq_quote_button_color',
			'colorpickers' => array(
				array(
					'name'    => __( 'Background', 'yith-woocommerce-request-a-quote' ),
					'id'      => 'bg_color',
					'default' => $ywraq_reject_button_bg_color,
				),
				array(
					'name'    => __( 'Background hover', 'yith-woocommerce-request-a-quote' ),
					'id'      => 'bg_color_hover',
					'default' => $ywraq_reject_button_bg_color_hover,
				),
				array(
					'name'    => __( 'Border', 'yith-woocommerce-request-a-quote' ),
					'id'      => 'border_color',
					'default' => $ywraq_reject_button_border_color,
				),
				array(
					'name'    => __( 'Border hover', 'yith-woocommerce-request-a-quote' ),
					'id'      => 'border_color_hover',
					'default' => $ywraq_reject_button_border_color_hover,
				),
				array(
					'name'    => __( 'Text', 'yith-woocommerce-request-a-quote' ),
					'id'      => 'color',
					'default' => $ywraq_reject_button_color,
				),
				array(
					'name'    => __( 'Text Hover', 'yith-woocommerce-request-a-quote' ),
					'id'      => 'color_hover',
					'default' => $ywraq_reject_button_color_hover,
				),
			),
			'deps'         => array(
				'id'    => 'ywraq_raq_reject_button_style',
				'value' => 'button',
			),
		),
		'accept_pay_reject_quote_settings_end' => array(
			'type' => 'sectionend',
			'id'   => 'ywraq_accept_pay_reject_quote_settings_end',
		),
		'label_settings' => array(
			'name' => __( 'Labels', 'yith-woocommerce-request-a-quote' ),
			'type' => 'title',
			'id'   => 'ywraq_label_settings',
		),
		'show_btn_link_text' => array(
			'name'      => __( '"Add to Quote" label', 'yith-woocommerce-request-a-quote' ),
			'desc'      => __( 'Enter the label to show within the "Add to quote" button on a single product page.', 'yith-woocommerce-request-a-quote' ),
			'type'      => 'yith-field',
			'yith-type' => 'text',
			'id'        => 'ywraq_show_btn_link_text',
			'required'  => true,
			'default'   => __( 'Add to quote', 'yith-woocommerce-request-a-quote' ),
		),
		'show_product_added' => array(
			'name'      => __( '"Product added to the list" label', 'yith-woocommerce-request-a-quote' ),
			'desc'      => __( 'Enter the label to show when a product is added to a quote list.', 'yith-woocommerce-request-a-quote' ),
			'type'      => 'yith-field',
			'yith-type' => 'text',
			'required'  => true,
			'id'        => 'ywraq_show_product_added',
			'default'   => __( 'Product added to the list', 'yith-woocommerce-request-a-quote' ),
		),
		'show_already_in_quote' => array(
			'name'      => __( '"Product already in the list" label', 'yith-woocommerce-request-a-quote' ),
			'desc'      => __( 'Enter the label to show when a product is already in the quote request list.', 'yith-woocommerce-request-a-quote' ),
			'type'      => 'yith-field',
			'yith-type' => 'text',
			'required'  => true,
			'id'        => 'ywraq_show_already_in_quote',
			'default'   => __( 'This product is already in your quote request list.', 'yith-woocommerce-request-a-quote' ),
		),
		'show_browse_list' => array(
			'name'      => __( '"Browse the list" label', 'yith-woocommerce-request-a-quote' ),
			'desc'      => __( 'Enter the text to show in the link that redirects users to the quote request list.', 'yith-woocommerce-request-a-quote' ),
			'type'      => 'yith-field',
			'yith-type' => 'text',
			'required'  => true,
			'id'        => 'ywraq_show_browse_list',
			'default'   => __( 'Browse the list', 'yith-woocommerce-request-a-quote' ),
		),
		'label_settings_end' => array(
			'type' => 'sectionend',
			'id'   => 'ywraq_label_settings_end',
		),
		'other_options_settings' => array(
			'name' => __( 'Other options', 'yith-woocommerce-request-a-quote' ),
			'type' => 'title',
			'id'   => 'ywraq_other_options_settings',
		),
		'loader_style' => array(
			'name'      => __( 'Loader style', 'yith-woocommerce-request-a-quote' ),
			'desc'      => __( 'Choose to use the default loader or upload a custom one.', 'yith-woocommerce-request-a-quote' ),
			'type'      => 'yith-field',
			'yith-type' => 'radio',
			'id'        => 'ywraq_loader_style',
			'options'   => array(
				'default' => __( 'Default', 'yith-woocommerce-request-a-quote' ),
				'custom'  => __( 'Upload a custom loader', 'yith-woocommerce-request-a-quote' ),
			),
			'default'   => 'default',
		),
		'loader_image' => array(
			'name'      => __( 'Loader', 'yith-woocommerce-request-a-quote' ),
			'desc'      => __( 'Upload a custom loader.', 'yith-woocommerce-request-a-quote' ),
			'id'        => 'ywraq_loader_image',
			'type'      => 'yith-field',
			'yith-type' => 'media',
			'class'     => 'ywraq_loader_image',
			'deps'      => array(
				'id'    => 'ywraq_loader_style',
				'value' => 'custom',
			),
		),
		'widget_icon'  => array(
			'name'      => _x( 'Widget icon', 'option name to change the icon of mini quote widget', 'yith-woocommerce-request-a-quote' ),
			'desc'      => _x( 'Upload a custom icon for the mini widget.', 'option description to change the icon of mini quote widget', 'yith-woocommerce-request-a-quote' ),
			'id'        => 'ywraq_widget_icon',
			'type'      => 'yith-field',
			'yith-type' => 'radio',
			'options'   => array(
				'default' => __( 'Default', 'yith-woocommerce-request-a-quote' ),
				'custom'  => __( 'Upload a custom icon', 'yith-woocommerce-request-a-quote' ),
			),
			'default'   => 'default',
		),
		'widget_icon_upload' => array(
			'name'      => __('Upload icon', 'yith-woocommerce-request-a-quote'),
			'desc'      => __( 'Upload the custom icon you want to use for the mini widget.', 'yith-woocommerce-request-a-quote' ),
			'id'        => 'ywraq_widget_icon_upload',
			'type'      => 'yith-field',
			'yith-type' => 'media',
			'deps'      => array(
				'id'    => 'ywraq_widget_icon',
				'value' => 'custom',
			),
		),
		'enable_ajax_loading' => array(
			'name'      => __( 'Enable AJAX Loading', 'yith-woocommerce-request-a-quote' ),
			'desc'      => __( 'Load any cacheable quote item via AJAX', 'yith-woocommerce-request-a-quote' ),
			'id'        => 'ywraq_enable_ajax_loading',
			'default'   => 'no',
			'type'      => 'yith-field',
			'yith-type' => 'onoff',
		),
		'other_options_settings_end' => array(
			'type' => 'sectionend',
			'id'   => 'ywraq_other_options_settings_end',
		),
	),
);

return apply_filters( 'ywraq_customization_options', $options );
