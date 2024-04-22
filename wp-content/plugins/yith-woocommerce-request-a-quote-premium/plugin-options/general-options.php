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

$options =  array(
	'general' => array(
		'general_options_settings'     => array(
			'name' => __( '"Add to quote" options', 'yith-woocommerce-request-a-quote' ),
			'type' => 'title',
			'id'   => 'ywraq_general_options_settings',
		),
		'user_type'                    => array(
			'name'      => __( 'Show "Add to quote" button to:', 'yith-woocommerce-request-a-quote' ),
			'desc'      => __( 'Choose to show the quote button to all users or only to logged or guest users.', 'yith-woocommerce-request-a-quote' ),
			'type'      => 'yith-field',
			'yith-type' => 'radio',
			'id'        => 'ywraq_user_type',
			'options'   => array(
				'all'       => __( 'All users', 'yith-woocommerce-request-a-quote' ),
				'roles'     => __( 'Only specific user roles', 'yith-woocommerce-request-a-quote' ),
				'customers' => __( 'Only logged users', 'yith-woocommerce-request-a-quote' ),
				'guests'    => __( 'Only guest users', 'yith-woocommerce-request-a-quote' ),
			),
			'default'   => 'all',
		),
		'user_role'                    => array(
			'name'              => __('Choose user roles', 'yith-woocommerce-request-a-quote' ),
			'desc'              => __( 'Choose the user roles that can see the "Add to quote" button.', 'yith-woocommerce-request-a-quote' ),
			'type'              => 'yith-field',
			'yith-type'         => 'select',
			'class'             => 'wc-enhanced-select',
			'css'               => 'min-width:300px',
			'multiple'          => true,
			'id'                => 'ywraq_user_role',
			'options'           => yith_ywraq_get_roles(),
			'default'           => array( 'customer' ),
			'placeholder'       => __( 'Choose a role', 'yith-woocommerce-request-a-quote' ),
			'required'          => true,
			'custom_attributes' => array(
				'data-deps'       => 'ywraq_user_type,ywraq_enabled_user_roles',
				'data-deps_value' => 'roles',
			),
		),
		'exclusion_list_setting'       => array(
			'name'      => __( 'Show "Add to quote" on:', 'yith-woocommerce-request-a-quote' ),
			'desc'      => __( 'Choose how to manage the Exclusion List: if you choose "All products" then all products will show the "Add to quote" button. If you choose "Products in the Exclusion List only" then only those added to the list will display "Add to quote".', 'yith-woocommerce-request-a-quote' ),
			'id'        => 'ywraq_exclusion_list_setting',
			'type'      => 'yith-field',
			'yith-type' => 'radio',
			'options'   => array(
				'hide' => __( 'All products (except the ones in the Exclusion List)', 'yith-woocommerce-request-a-quote' ),
				'show' => __( 'Products in the Exclusion List only.', 'yith-woocommerce-request-a-quote' ),
			),
			'default'   => 'hide',
		),
		'button_out_of_stock'          => array(
			'name'      => __( '"Add to quote" on out of stock products:', 'yith-woocommerce-request-a-quote' ),
			'desc'      => __( 'Choose how to manage the "Add to quote" button on out of stock products.', 'yith-woocommerce-request-a-quote' ),
			'id'        => 'ywraq_button_out_of_stock',
			'type'      => 'yith-field',
			'yith-type' => 'radio',
			'options'   => array(
				'show' => __( 'Show "Add to quote" in all products (also out of stock)', 'yith-woocommerce-request-a-quote' ),
				'only' => __( 'Show "Add to quote" only on out of stock products', 'yith-woocommerce-request-a-quote' ),
				'hide' => __( 'Hide "Add to quote" on out of stock products', 'yith-woocommerce-request-a-quote' ),
			),
			'default'   => 'hide',
		),
		'show_btn_single_page'         => array(
			'name'      => __( 'Show "Add to quote" on single product pages', 'yith-woocommerce-request-a-quote' ),
			'desc'      => __( 'Enable to show the "Add to quote" button on single product pages.', 'yith-woocommerce-request-a-quote' ),
			'type'      => 'yith-field',
			'yith-type' => 'onoff',
			'id'        => 'ywraq_show_btn_single_page',
			'default'   => 'yes',
		),
		'show_button_near_add_to_cart' => array(
			'name'      => __( '"Add to quote" position on single product page', 'yith-woocommerce-request-a-quote' ),
			'desc'      => __( 'Choose where to show the "Add to quote" button on single product pages.', 'yith-woocommerce-request-a-quote' ),
			'id'        => 'ywraq_show_button_near_add_to_cart',
			'type'      => 'yith-field',
			'yith-type' => 'radio',
			'options'   => array(
				'yes' => __( 'Inline with "Add to cart"', 'yith-woocommerce-request-a-quote' ),
				'no'  => __( 'Underneath "Add to cart" button', 'yith-woocommerce-request-a-quote' ),
			),
			'default'   => 'no',
			'deps'      => array(
				'id'    => 'ywraq_show_btn_single_page',
				'value' => 'yes',
			),
		),
		'show_btn_other_pages'         => array(
			'name'      => __( 'Show "Add to quote" in other WooCommerce pages', 'yith-woocommerce-request-a-quote' ),
			'desc'      => __( 'Enable to show the "Add to quote" button in category pages, shop pages, etc.', 'yith-woocommerce-request-a-quote' ),
			'id'        => 'ywraq_show_btn_other_pages',
			'type'      => 'yith-field',
			'yith-type' => 'onoff',
			'default'   => 'yes',
		),
		'show_btn_woocommerce_blocks'  => array(
			'name'      => __( 'Show "Add to quote" in WooCommerce Blocks', 'yith-woocommerce-request-a-quote' ),
			'desc'      => __( 'Enable to show the "Add to quote" button in WooCommerce Gutenberg Blocks.', 'yith-woocommerce-request-a-quote' ),
			'id'        => 'ywraq_show_btn_woocommerce_blocks',
			'type'      => 'yith-field',
			'yith-type' => 'onoff',
			'default'   => 'no',
		),
		'general_options_settings_end' => array(
			'type' => 'sectionend',
			'id'   => 'general_options_settings_end',
		),
		'other_options_settings'     => array(
			'name' => __( 'Other options', 'yith-woocommerce-request-a-quote' ),
			'type' => 'title',
			'id'   => 'ywraq_other_options_settings',
		),
		'hide_add_to_cart'             => array(
			'name'      => __( 'Hide "Add to cart" buttons', 'yith-woocommerce-request-a-quote' ),
			'desc'      => __( 'Enable to hide the "Add to cart" buttons on all products.', 'yith-woocommerce-request-a-quote' ),
			'id'        => 'ywraq_hide_add_to_cart',
			'type'      => 'yith-field',
			'yith-type' => 'onoff',
			'default'   => 'no',
		),
		'hide_price'                   => array(
			'name'      => __( 'Hide prices', 'yith-woocommerce-request-a-quote' ),
			'desc'      => __( 'Enable to hide prices on all products.', 'yith-woocommerce-request-a-quote' ),
			'id'        => 'ywraq_hide_price',
			'type'      => 'yith-field',
			'yith-type' => 'onoff',
			'default'   => 'no',
		),
		'show_btn_on_cart_page'  => array(
			'name'      => __( 'Show "Ask quote" on the Cart page', 'yith-woocommerce-request-a-quote' ),
			'desc'      => __( 'Enable to show the "Ask quote" button on the Cart page. This option allows users to convert the cart content into a quote request.', 'yith-woocommerce-request-a-quote' ),
			'id'        => 'ywraq_show_button_on_cart_page',
			'type'      => 'yith-field',
			'yith-type' => 'onoff',
			'default'   => 'no',
		),
		'show_button_on_checkout_page' => array(
			'name'      => __( 'Show "Ask quote" on the Checkout page', 'yith-woocommerce-request-a-quote' ),
			'desc'      => __( 'Enable to show the "Ask quote" button on the Checkout page. We suggest enabling this option only if users are not automatically directed to the quote page.', 'yith-woocommerce-request-a-quote' ),
			'type'      => 'yith-field',
			'yith-type' => 'onoff',
			'id'        => 'ywraq_show_button_on_checkout_page',
			'default'   => 'no',
		),
		'checkout_quote_button_label' => array(
			'name'      => __( '"Ask quote" button label', 'yith-woocommerce-request-a-quote' ),
			'desc'      => __( 'Enter the label for the "Ask quote" button on the Cart and Checkout pages.', 'yith-woocommerce-request-a-quote' ),
			'type'      => 'yith-field',
			'yith-type' => 'text',
			'id'        => 'ywraq_checkout_quote_button_label',
			'default'   => __( 'or ask for a quote', 'yith-woocommerce-request-a-quote' ),
			'required'  => true,
		),
		'after_click_action'           => array(
			'name'      => __( 'After clicking on "Add to quote" the user:', 'yith-woocommerce-request-a-quote' ),
			'desc'      => __( 'Choose what happens after the user clicks on the "Add to quote" button.', 'yith-woocommerce-request-a-quote' ),
			'type'      => 'yith-field',
			'yith-type' => 'radio',
			'id'        => 'ywraq_after_click_action',
			'options'   => array(
				'no'  => __( 'Sees a link to access the quote request list', 'yith-woocommerce-request-a-quote' ),
				'yes' => __( 'Is automatically redirected to the quote request list.', 'yith-woocommerce-request-a-quote' ),
			),
			'default'   => 'no',
		),
		'other_options_settings_end' => array(
			'type' => 'sectionend',
			'id'   => 'other_options_settings_end',
		),
	),
);

if ( catalog_mode_plugin_enabled() ) {
	unset( $options['general']['hide_price'] );
	unset( $options['general']['hide_add_to_cart'] );
}

return apply_filters( 'ywraq_general_settings_options', $options );
