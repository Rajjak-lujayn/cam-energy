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

$options = array(
	'page_settings' => array(
		'name' => __( '"Request quote" page options', 'yith-woocommerce-request-a-quote' ),
		'type' => 'title',
		'id'   => 'ywraq_page_settings',
	),
	'page_id'       => array(
		'name' => __( '"Request a quote" page', 'yith-woocommerce-request-a-quote' ),
		'desc' => sprintf(
			'%s<br/>%s<br/>%s',
			__( 'Choose from this list the page on which users will see the list of products added to the quote and send the request.', 'yith-woocommerce-request-a-quote' ),
			__( 'Please note: if you choose a page different from the default one (request quote) you need to insert', 'yith-woocommerce-request-a-quote' ),
			__( 'in the page the following shortcode: [yith_ywraq_request_quote] ', 'yith-woocommerce-request-a-quote' )
		),
		'id'       => 'ywraq_page_id',
		'type'     => 'single_select_page',
		'class'    => 'wc-enhanced-select',
		'css'      => 'min-width:300px',
		'desc_tip' => false,
	),
	'html_create_page' => array(
		'type'             => 'yith-field',
		'yith-type'        => 'html',
		'yith-display-row' => false,
		'html'             => sprintf(
			'<div class="ywraq-create-page">%s <a href="%s">%s</a></div>',
			esc_html_x( 'or', 'part of the string (or Create a page) inside admin panel', 'yith-woocommerce-request-a-quote' ),
			esc_url( admin_url( 'post-new.php?post_type=page' ) ),
			esc_html__( 'Create a page', 'yith-woocommerce-request-a-quote' )
		),
	),
	'page_list_layout_template' => array(
		'name'      => __( 'Page Layout', 'yith-woocommerce-request-a-quote' ),
		'desc'      => __( 'Choose the layout for "Request a quote" page.', 'yith-woocommerce-request-a-quote' ),
		'id'        => 'ywraq_page_list_layout_template',
		'type'      => 'yith-field',
		'yith-type' => 'radio',
		'options'   => array(
			'wide'     => __( 'Product list on left side, form on right side', 'yith-woocommerce-request-a-quote' ),
			'vertical' => __( 'Product list above, form below', 'yith-woocommerce-request-a-quote' ),
		),
		'default'   => 'vertical',
	),
	'page_settings_end' => array(
		'type' => 'sectionend',
		'id'   => 'ywraq_page_settings_end',
	),
	'form_settings' => array(
		'name' => __( 'Form options', 'yith-woocommerce-request-a-quote' ),
		'type' => 'title',
		'id'   => 'ywraq_form_settings',
	),
	'show_form_with_empty_list' => array(
		'name'      => __( 'Show form even with empty list', 'yith-woocommerce-request-a-quote' ),
		'desc'      => __( 'Enable to show the form in request quote page also with an empty list of products.', 'yith-woocommerce-request-a-quote' ),
		'id'        => 'ywraq_show_form_with_empty_list',
		'type'      => 'yith-field',
		'yith-type' => 'onoff',
		'default'   => 'no',
	),
	'title_before_form' => array(
		'name'      => __( 'Title before form', 'yith-woocommerce-request-a-quote' ),
		'desc'      => __( 'Enter an optional title to show above the form.', 'yith-woocommerce-request-a-quote' ),
		'id'        => 'ywraq_title_before_form',
		'type'      => 'yith-field',
		'yith-type' => 'text',
		'default'   => apply_filters( 'ywraq_form_title', __( 'Send the request', 'yith-woocommerce-request-a-quote' ) ),
	),
	'form_settings_end' => array(
		'type' => 'sectionend',
		'id'   => 'ywraq_form_settings_end',
	),
	'product_table_settings' => array(
		'name' => __( 'Product table', 'yith-woocommerce-request-a-quote' ),
		'type' => 'title',
		'id'   => 'ywraq_product_table_settings',
	),
	'product_table_show' => array(
		'name'      => __( 'In product table, show:', 'yith-woocommerce-request-a-quote' ),
		'desc'      => __( 'Choose which info to show in the product table.', 'yith-woocommerce-request-a-quote' ),
		'id'        => 'ywraq_product_table_show',
		'type'      => 'yith-field',
		'yith-type' => 'checkbox-array',
		'options'   => array(
			'images'       => __( 'Product images', 'yith-woocommerce-request-a-quote' ),
			'single_price' => __( 'Product prices', 'yith-woocommerce-request-a-quote' ),
			'sku'          => __( 'Product SKU', 'yith-woocommerce-request-a-quote' ),
			'quantity'     => __( 'Quantity', 'yith-woocommerce-request-a-quote' ),
			'line_total'   => __( 'Total amount of single products', 'yith-woocommerce-request-a-quote' ),
			'total'        => __( 'Total amount of all products', 'yith-woocommerce-request-a-quote' ),
			'tax'          => __( 'Taxes', 'yith-woocommerce-request-a-quote' ),
		),
		'default'   => array( 'images', 'line_total', 'quantity' ),
	),
	'product_table_settings_end' => array(
		'type' => 'sectionend',
		'id'   => 'ywraq_product_table_settings_end',
	),
	'return_shop_settings' => array(
		'name' => __( '"Return to shop" options', 'yith-woocommerce-request-a-quote' ),
		'type' => 'title',
		'id'   => 'ywraq_return_shop_settings',
	),
	'show_return_to_shop' => array(
		'name'      => __( 'Show "Return to Shop" button', 'yith-woocommerce-request-a-quote' ),
		'desc'      => __( 'Enable to show the "Return to shop" button.', 'yith-woocommerce-request-a-quote' ),
		'id'        => 'ywraq_show_return_to_shop',
		'type'      => 'yith-field',
		'yith-type' => 'onoff',
		'default'   => 'yes',
	),
	'return_to_shop_label' => array(
		'name'      => __( '"Return to Shop" label', 'yith-woocommerce-request-a-quote' ),
		'desc'      => __( 'Enter the button\'s label', 'yith-woocommerce-request-a-quote' ),
		'id'        => 'ywraq_return_to_shop_label',
		'type'      => 'yith-field',
		'yith-type' => 'text',
		'required'  => true,
		'deps'      => array(
			'id'    => 'ywraq_show_return_to_shop',
			'value' => 'yes',
		),
		'default'   => __( 'Return to Shop', 'yith-woocommerce-request-a-quote' ),
	),
	'return_to_shop_url_choice' => array(
		'name'      => __( '"Return to Shop" URL', 'yith-woocommerce-request-a-quote' ),
		'desc'      => __( 'Choose the URL to assign to the button.', 'yith-woocommerce-request-a-quote' ),
		'id'        => 'ywraq_return_to_shop_url_choice',
		'type'      => 'yith-field',
		'yith-type' => 'radio',
		'options'   => array(
			'wc-shop' => __( 'WooCommerce Shop page', 'yith-woocommerce-request-a-quote' ),
			'custom'  => __( 'Custom URL', 'yith-woocommerce-request-a-quote' ),
		),
		'deps'      => array(
			'id'    => 'ywraq_show_return_to_shop',
			'value' => 'yes',
		),
		'default'   => get_option( 'ywraq_return_to_shop_after_sent_the_request_url' ) ? 'custom' : 'wc-shop',
	),
	'return_to_shop_url' => array(
		'name'              => '',
		'desc'              => __( 'Enter the URL to assign to the button.', 'yith-woocommerce-request-a-quote' ),
		'id'                => 'ywraq_return_to_shop_url',
		'type'              => 'yith-field',
		'yith-type'         => 'text',
		'required'          => true,
		'custom_attributes' => array(
			'data-deps'       => 'ywraq_show_return_to_shop,ywraq_return_to_shop_url_choice',
			'data-deps_value' => 'yes,custom',
		),
		'default'           => get_permalink( wc_get_page_id( 'shop' ) ),
	),
	'return_shop_settings_end' => array(
		'type' => 'sectionend',
		'id'   => 'ywraq_return_shop_settings_end',
	),
	'update_list_settings' => array(
		'name' => __( '"Update &amp; clear list" options', 'yith-woocommerce-request-a-quote' ),
		'type' => 'title',
		'id'   => 'ywraq_update_list_settings',
	),
	'show_update_list' => array(
		'name'      => __( 'Show "Update List" button', 'yith-woocommerce-request-a-quote' ),
		'desc'      => __( 'Enable to show the "Update list" button.', 'yith-woocommerce-request-a-quote' ),
		'id'        => 'ywraq_show_update_list',
		'type'      => 'yith-field',
		'yith-type' => 'onoff',
		'default'   => 'yes',
	),
	'update_list_label' => array(
		'name'      => __( '"Update List" label', 'yith-woocommerce-request-a-quote' ),
		'desc'      => __( 'Enter the button\'s label.', 'yith-woocommerce-request-a-quote' ),
		'id'        => 'ywraq_update_list_label',
		'type'      => 'yith-field',
		'yith-type' => 'text',
		'deps'      => array(
			'id'    => 'ywraq_show_update_list',
			'value' => 'yes',
		),
		'default'   => __( 'Update List', 'yith-woocommerce-request-a-quote' ),
	),
	'clear_list_button' => array(
		'name'      => __( 'Show "Clear list" button', 'yith-woocommerce-request-a-quote' ),
		'desc'      => __( 'Enable to show the "Clear list" button.', 'yith-woocommerce-request-a-quote' ),
		'id'        => 'ywraq_show_clear_list_button',
		'type'      => 'yith-field',
		'yith-type' => 'onoff',
		'default'   => 'no',
	),
	'clear_list_label' => array(
		'name'      => __( '"Clear List" label', 'yith-woocommerce-request-a-quote' ),
		'desc'      => __( 'Enter the button\'s label.', 'yith-woocommerce-request-a-quote' ),
		'id'        => 'ywraq_clear_list_label',
		'type'      => 'yith-field',
		'yith-type' => 'text',
		'deps'      => array(
			'id'    => 'ywraq_show_clear_list_button',
			'value' => 'yes',
		),
		'default'   => __( 'Clear List', 'yith-woocommerce-request-a-quote' ),
	),
	'update_list_settings_end' => array(
		'type' => 'sectionend',
		'id'   => 'ywraq_update_list_settings_end',
	),
	'pdf_settings' => array(
		'name' => __( 'PDF options', 'yith-woocommerce-request-a-quote' ),
		'type' => 'title',
		'id'   => 'ywraq_pdf_settings',
	),
	'show_download_pdf_on_request' => array(
		'name'      => __( 'Show "View PDF" button', 'yith-woocommerce-request-a-quote' ),
		'desc'      => __( 'Enable to allow users to download the products list in a PDF file.', 'yith-woocommerce-request-a-quote' ),
		'id'        => 'ywraq_show_download_pdf_on_request',
		'type'      => 'yith-field',
		'yith-type' => 'onoff',
		'default'   => 'no',
	),
	'show_download_pdf_on_request_label' => array(
		'name'      => __( '"View PDF" label', 'yith-woocommerce-request-a-quote' ),
		'desc'      => __( 'Enter the button\'s label.', 'yith-woocommerce-request-a-quote' ),
		'id'        => 'ywraq_show_download_pdf_on_request_label',
		'type'      => 'yith-field',
		'yith-type' => 'text',
		'deps'      => array(
			'id'    => 'ywraq_show_download_pdf_on_request',
			'value' => 'yes',
		),
		'default'   => _x( 'View PDF', 'Admin option label for button to make a PDF on Request a quote page', 'yith-woocommerce-request-a-quote' ),
	),
	'download_pdf_on_request_logo'       => array(
		'name'      => __( 'Logo', 'yith-woocommerce-request-a-quote' ),
		'desc'      => __( 'Upload a logo to identify your shop in the PDF file.', 'yith-woocommerce-request-a-quote' ),
		'id'        => 'ywraq_download_pdf_on_request_logo',
		'type'      => 'yith-field',
		'yith-type' => 'media',
		'deps'      => array(
			'id'    => 'ywraq_show_download_pdf_on_request',
			'value' => 'yes',
		),
	),
	'pdf_settings_end' => array(
		'type' => 'sectionend',
		'id'   => 'ywraq_pdf_settings_end',
	),
	'send_request_settings' => array(
		'name' => __( 'Request sending', 'yith-woocommerce-request-a-quote' ),
		'type' => 'title',
		'id'   => 'ywraq_send_request_settings	',
	),
	'how_show_after_sent_the_request'    => array(
		'name'      => __( 'After request sending, show:', 'yith-woocommerce-request-a-quote' ),
		'desc'      => __( 'Choose what to show after a quote request has been sent.', 'yith-woocommerce-request-a-quote' ),
		'id'        => 'ywraq_how_show_after_sent_the_request',
		'type'      => 'yith-field',
		'yith-type' => 'radio',
		'options'   => array(
			'simple_message'  => __( 'A simple text message', 'yith-woocommerce-request-a-quote' ),
			'thank_you_quote' => __( 'A detail page of quote request', 'yith-woocommerce-request-a-quote' ),
			'thank_you_page'  => __( 'A specific "Thank you" page', 'yith-woocommerce-request-a-quote' ),
		),
		'default'   => 'simple_message',
	),
	'message_after_sent_the_request' => array(
		'name'      => __( 'Text to show after request sending', 'yith-woocommerce-request-a-quote' ),
		'desc'      => __( 'Choose what message to show to the user after the request is sent. It is possible to use %quote_number% to show the link to the quote details.', 'yith-woocommerce-request-a-quote' ),
		'id'        => 'ywraq_message_after_sent_the_request',
		'type'      => 'yith-field',
		'yith-type' => 'textarea',
		'default'   => __( 'Your request has been sent successfully. You can see details at: %quote_number%', 'yith-woocommerce-request-a-quote' ),
		'deps'      => array(
			'id'    => 'ywraq_how_show_after_sent_the_request',
			'value' => 'simple_message',
		),
	),
	'return_to_shop_after_sent_the_request' => array(
		'name'      => __( '"Return to shop" label after request sending', 'yith-woocommerce-request-a-quote' ),
		'desc'      => __( 'Enter the button\'s label', 'yith-woocommerce-request-a-quote' ),
		'id'        => 'ywraq_return_to_shop_after_sent_the_request',
		'type'      => 'yith-field',
		'yith-type' => 'text',
		'deps'      => array(
			'id'    => 'ywraq_how_show_after_sent_the_request',
			'value' => 'simple_message',
		),
		'default'   => __( 'Return to Shop', 'yith-woocommerce-request-a-quote' ),
	),
	'return_to_shop_after_sent_the_request_url_choice' => array(
		'name'      => __( '"Return to Shop" URL', 'yith-woocommerce-request-a-quote' ),
		'desc'      => __( 'Choose the URL to assign to the button.', 'yith-woocommerce-request-a-quote' ),
		'id'        => 'ywraq_return_to_shop_after_sent_the_request_url_choice',
		'type'      => 'yith-field',
		'yith-type' => 'radio',
		'options'   => array(
			'wc-shop' => __( 'WooCommerce Shop page', 'yith-woocommerce-request-a-quote' ),
			'custom'  => __( 'Custom URL', 'yith-woocommerce-request-a-quote' ),
		),
		'deps'      => array(
			'id'    => 'ywraq_how_show_after_sent_the_request',
			'value' => 'simple_message',
		),
		'default'   => get_option( 'ywraq_return_to_shop_after_sent_the_request_url' ) ? 'custom' : 'wc-shop'
	),
	'return_to_shop_after_sent_the_request_url' => array(
		'name'              => '',
		'desc'              => __( 'Enter the URL to assign to the button.', 'yith-woocommerce-request-a-quote' ),
		'id'                => 'ywraq_return_to_shop_after_sent_the_request_url',
		'type'              => 'yith-field',
		'yith-type'         => 'text',
		'required'          => true,
		'custom_attributes' => array(
			'data-deps'       => 'ywraq_how_show_after_sent_the_request,ywraq_return_to_shop_after_sent_the_request_url_choice',
			'data-deps_value' => 'simple_message,custom',
		),
		'default'           => get_permalink( wc_get_page_id( 'shop' ) ),
	),
	'thank_you_page' => array(
		'name'    => __( 'Choose the "Thank you" page', 'yith-woocommerce-request-a-quote' ),
		'desc'    => __( 'Choose the page to show to the user after the request is sent.', 'yith-woocommerce-request-a-quote' ),
		'id'      => 'ywraq_thank_you_page',
		'type'    => 'single_select_page',
		'default' => '',
		'class'   => 'wc-enhanced-select',
		'css'     => 'min-width:300px',
		'deps'    => array(
			'id'    => 'ywraq_how_show_after_sent_the_request',
			'value' => 'thank_you_page',
		),
	),
	'send_request_settings_end' => array(
		'type' => 'sectionend',
		'id'   => 'ywraq_send_request_settings_end',
	),
);

return array( 'request-page' => apply_filters( 'ywraq_request_page_settings_options', $options ) );
