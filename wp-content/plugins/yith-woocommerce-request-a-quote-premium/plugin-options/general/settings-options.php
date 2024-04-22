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

$section1 = array(
	'general_options_settings'     => array(
		'name' => esc_html__( 'General Options', 'yith-woocommerce-request-a-quote' ),
		'type' => 'title',
		'id'   => 'ywraq_general_options_settings',
	),
	'user_type'                    => array(
		'name'      => esc_html__( 'Show "Add to quote" button to:', 'yith-woocommerce-request-a-quote' ),
		'desc'      => esc_html__( 'Choose to show the quote button to all users or only to logged or guest users.', 'yith-woocommerce-request-a-quote' ),
		'type'      => 'yith-field',
		'yith-type' => 'radio',
		'id'        => 'ywraq_user_type',
		'options'   => array(
			'all'       => esc_html__( 'All users', 'yith-woocommerce-request-a-quote' ),
			'roles'     => esc_html__( 'Only specific user roles', 'yith-woocommerce-request-a-quote' ),
			'customers' => esc_html__( 'Only logged users', 'yith-woocommerce-request-a-quote' ),
			'guests'    => esc_html__( 'Only guest users', 'yith-woocommerce-request-a-quote' ),
		),
		'default'   => 'all',
	),
	'user_role'                    => array(
		'name'              => esc_html__( 'Choose user roles', 'yith-woocommerce-request-a-quote' ),
		'desc'              => esc_html__( 'Choose the user roles that can see the "Add to quote" button.', 'yith-woocommerce-request-a-quote' ),
		'type'              => 'yith-field',
		'yith-type'         => 'select',
		'class'             => 'wc-enhanced-select',
		'css'               => 'min-width:300px',
		'multiple'          => true,
		'id'                => 'ywraq_user_role',
		'options'           => yith_ywraq_get_roles(),
		'default'           => array( 'customer' ),
		'placeholder'       => esc_html__( 'Choose a role', 'yith-woocommerce-request-a-quote' ),
		'required'          => true,
		'custom_attributes' => array(
			'data-deps'       => 'ywraq_user_type,ywraq_enabled_user_roles',
			'data-deps_value' => 'roles',
		),
	),
	'exclusion_list_setting'       => array(
		'name'      => esc_html__( 'Show "Add to quote" on:', 'yith-woocommerce-request-a-quote' ),
		'desc'      => esc_html__( 'Choose how to manage the Exclusion List: if you choose "All products" then all products will show the "Add to quote" button. If you choose "Products in the Exclusion List only" then only those added to the list will display "Add to quote".', 'yith-woocommerce-request-a-quote' ),
		'id'        => 'ywraq_exclusion_list_setting',
		'type'      => 'yith-field',
		'yith-type' => 'radio',
		'options'   => array(
			'hide' => esc_html__( 'All products (except the ones in the Exclusion List)', 'yith-woocommerce-request-a-quote' ),
			'show' => esc_html__( 'Products in the Exclusion List only.', 'yith-woocommerce-request-a-quote' ),
		),
		'default'   => 'hide',
	),
	'button_out_of_stock'          => array(
		'name'      => esc_html__( '"Add to quote" on out of stock products:', 'yith-woocommerce-request-a-quote' ),
		'desc'      => esc_html__( 'Choose how to manage the "Add to quote" button on out of stock products.', 'yith-woocommerce-request-a-quote' ),
		'id'        => 'ywraq_button_out_of_stock',
		'type'      => 'yith-field',
		'yith-type' => 'radio',
		'options'   => array(
			'show' => esc_html__( 'Show "Add to quote" in all products (also out of stock)', 'yith-woocommerce-request-a-quote' ),
			'only' => esc_html__( 'Show "Add to quote" only on out of stock products', 'yith-woocommerce-request-a-quote' ),
			'hide' => esc_html__( 'Hide "Add to quote" on out of stock products', 'yith-woocommerce-request-a-quote' ),
		),
		'default'   => 'hide',
	),
	'show_btn_single_page'         => array(
		'name'      => esc_html__( 'Show "Add to quote" on single product pages', 'yith-woocommerce-request-a-quote' ),
		'desc'      => esc_html__( 'Enable to show the "Add to quote" button on single product pages.', 'yith-woocommerce-request-a-quote' ),
		'type'      => 'yith-field',
		'yith-type' => 'onoff',
		'id'        => 'ywraq_show_btn_single_page',
		'default'   => 'yes',
	),
	'show_button_near_add_to_cart' => array(
		'name'      => esc_html__( '"Add to quote" position on single product page', 'yith-woocommerce-request-a-quote' ),
		'desc'      => esc_html__( 'Choose where to show the "Add to quote" button on single product pages.', 'yith-woocommerce-request-a-quote' ),
		'id'        => 'ywraq_show_button_near_add_to_cart',
		'type'      => 'yith-field',
		'yith-type' => 'radio',
		'options'   => array(
			'yes' => esc_html__( 'Inline with "Add to cart"', 'yith-woocommerce-request-a-quote' ),
			'no'  => esc_html__( 'Underneath "Add to cart" button', 'yith-woocommerce-request-a-quote' ),
		),
		'default'   => 'no',
		'deps'      => array(
			'id'    => 'ywraq_show_btn_single_page',
			'value' => 'yes',
		),
	),
	'hide_add_to_cart'             => array(
		'name'      => esc_html__( 'Hide "Add to cart" buttons', 'yith-woocommerce-request-a-quote' ),
		'desc'      => esc_html__( 'Enable to hide the "Add to cart" buttons on all products.', 'yith-woocommerce-request-a-quote' ),
		'id'        => 'ywraq_hide_add_to_cart',
		'type'      => 'yith-field',
		'yith-type' => 'onoff',
		'default'   => 'no',
	),
	'hide_price'                   => array(
		'name'      => esc_html__( 'Hide prices', 'yith-woocommerce-request-a-quote' ),
		'desc'      => esc_html__( 'Enable to hide prices on all products.', 'yith-woocommerce-request-a-quote' ),
		'id'        => 'ywraq_hide_price',
		'type'      => 'yith-field',
		'yith-type' => 'onoff',
		'default'   => 'no',
	),
	'show_btn_other_pages'         => array(
		'name'      => esc_html__( 'Show "Add to quote" in other WooCommerce pages', 'yith-woocommerce-request-a-quote' ),
		'desc'      => esc_html__( 'Enable to show the "Add to quote" button in category pages, shop pages, etc.', 'yith-woocommerce-request-a-quote' ),
		'id'        => 'ywraq_show_btn_other_pages',
		'type'      => 'yith-field',
		'yith-type' => 'onoff',
		'default'   => 'yes',
	),
	'show_btn_woocommerce_blocks'  => array(
		'name'      => esc_html__( 'Show "Add to quote" in WooCommerce Blocks', 'yith-woocommerce-request-a-quote' ),
		'desc'      => esc_html__( 'Enable to show the "Add to quote" button in WooCommerce Gutenberg Blocks.', 'yith-woocommerce-request-a-quote' ),
		'id'        => 'ywraq_show_btn_woocommerce_blocks',
		'type'      => 'yith-field',
		'yith-type' => 'onoff',
		'default'   => 'no',
	),
	'show_btn_on_cart_page'        => array(
		'name'      => esc_html__( 'Show "Ask quote" on the Cart page', 'yith-woocommerce-request-a-quote' ),
		'desc'      => esc_html__( 'Enable to show the "Ask quote" button on the Cart page. This option allows users to convert the cart content into a quote request.', 'yith-woocommerce-request-a-quote' ),
		'id'        => 'ywraq_show_button_on_cart_page',
		'type'      => 'yith-field',
		'yith-type' => 'onoff',
		'default'   => 'no',
	),
	'show_button_on_checkout_page' => array(
		'name'      => esc_html__( 'Show "Ask quote" on the Checkout page', 'yith-woocommerce-request-a-quote' ),
		'desc'      => esc_html__( 'Enable to show the "Ask quote" button on the Checkout page. We suggest enabling this option only if users are not automatically directed to the quote page.', 'yith-woocommerce-request-a-quote' ),
		'type'      => 'yith-field',
		'yith-type' => 'onoff',
		'id'        => 'ywraq_show_button_on_checkout_page',
		'default'   => 'no',
	),
	'checkout_quote_button_label'  => array(
		'name'      => esc_html__( '"Ask quote" button label', 'yith-woocommerce-request-a-quote' ),
		'desc'      => esc_html__( 'Enter the label for the "Ask quote" button on the Cart and Checkout pages.', 'yith-woocommerce-request-a-quote' ),
		'type'      => 'yith-field',
		'yith-type' => 'text',
		'id'        => 'ywraq_checkout_quote_button_label',
		'default'   => esc_html__( 'or ask for a quote', 'yith-woocommerce-request-a-quote' ),
		'required'  => true,
	),
	'after_click_action'           => array(
		'name'      => esc_html__( 'After clicking on "Add to quote" the user:', 'yith-woocommerce-request-a-quote' ),
		'desc'      => esc_html__( 'Choose what happens after the user clicks on the "Add to quote" button.', 'yith-woocommerce-request-a-quote' ),
		'type'      => 'yith-field',
		'yith-type' => 'radio',
		'id'        => 'ywraq_after_click_action',
		'options'   => array(
			'no'  => esc_html__( 'Sees a link to access the quote request list', 'yith-woocommerce-request-a-quote' ),
			'yes' => esc_html__( 'Is automatically redirected to the quote request list.', 'yith-woocommerce-request-a-quote' ),
		),
		'default'   => 'no',
	),
	'general_options_settings_end' => array(
		'type' => 'sectionend',
		'id'   => 'general_options_settings_end',
	),
);

if ( catalog_mode_plugin_enabled() ) {
	unset( $section1['hide_price'] );
	unset( $section1['hide_add_to_cart'] );
}

return array( 'general-settings' => apply_filters( 'ywraq_generals_settings_options', $section1 ) );
