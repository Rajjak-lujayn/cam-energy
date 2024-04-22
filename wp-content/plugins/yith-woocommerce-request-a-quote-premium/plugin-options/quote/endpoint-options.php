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
	'quote_endpoint'                        => array(
		'name' => __( 'Quote options in "My Account"', 'yith-woocommerce-request-a-quote' ),
		'type' => 'title',
		'id'   => 'ywraq_quote_endpoint',
	),
	'quote_endpoint_label'                  => array(
		'name'      => __( 'Quotes endpoint label', 'yith-woocommerce-request-a-quote' ),
		'desc'      => __( 'Enter the text to identify the "Quotes" endpoint in the user\'s "My Account" page.', 'yith-woocommerce-request-a-quote' ),
		'id'        => 'ywraq_quote_endpoint_label',
		'required'  => true,
		'type'      => 'yith-field',
		'yith-type' => 'text',
		'default'   => _x( 'Quotes', 'Endpoint label on My account', 'yith-woocommerce-request-a-quote' ),
	),
	'quote_label_new_quotes_status'         => array(
		'name'      => __( 'Additional label for the "New quote requests" status', 'yith-woocommerce-request-a-quote' ),
		'desc'      => __( 'Enter an optional text to show near the "New quote request" status.', 'yith-woocommerce-request-a-quote' ),
		'id'        => 'ywraq_quote_label_new_quotes_status',
		'type'      => 'yith-field',
		'yith-type' => 'text',
		'default'   => _x( 'You will get a quote soon!', 'Endpoint label on My account', 'yith-woocommerce-request-a-quote' ),
	),
	'quote_admin_text_new_quotes_status'    => array(
		'name'      => __( 'Default text to show in all new quotes requests', 'yith-woocommerce-request-a-quote' ),
		'desc'      => __( 'Enter an additional text to show in My Account, in all new quotes.', 'yith-woocommerce-request-a-quote' ),
		'id'        => 'ywraq_quote_admin_text_new_quotes_status',
		'type'      => 'yith-field',
		'yith-type' => 'textarea',
		'rows'      => 8,
		'default'   => __(
			'Hi, and thank you for your request.
We usually reply to all quote requests in 3 working days.
Feel free to contact our Customer Service if you need additional info or help.

Regards,
Site name staff',
			'yith-woocommerce-request-a-quote'
		),
	),
	'quote_my_account_hide_price_new_quote' => array(
		'name'      => __( 'Hide product prices in all new quote details', 'yith-woocommerce-request-a-quote' ),
		'desc'      => __( 'Enable to hide the product prices in the new quote requests in "My Account".', 'yith-woocommerce-request-a-quote' ),
		'id'        => 'ywraq_quote_my_account_hide_price_new_quote',
		'type'      => 'yith-field',
		'yith-type' => 'onoff',
		'default'   => 'yes',
	),
	'enable_order_again'                    => array(
		'name'      => __( 'Show "Order again" button', 'yith-woocommerce-request-a-quote' ),
		'desc'      => __( 'Enable to show the "Order again" button in "My Account".', 'yith-woocommerce-request-a-quote' ),
		'id'        => 'ywraq_enable_order_again',
		'type'      => 'yith-field',
		'yith-type' => 'onoff',
		'default'   => 'no',
	),
	'order_again_button_label'              => array(
		'name'      => __( '"Order again" label', 'yith-woocommerce-request-a-quote' ),
		'desc'      => __( 'Enter the button\'s label.', 'yith-woocommerce-request-a-quote' ),
		'id'        => 'ywraq_order_again_button_label',
		'type'      => 'yith-field',
		'yith-type' => 'text',
		'required'  => true,
		'deps'      => array(
			'id'    => 'ywraq_enable_order_again',
			'value' => 'yes',
		),
		'default'   => __( 'Order again', 'yith-woocommerce-request-a-quote' ),
	),
	'enable_quote_again'                    => array(
		'name'      => __( 'Show "Ask new quote"', 'yith-woocommerce-request-a-quote' ),
		'desc'      => __( 'Enable to allow users to "Ask new quote" for the same list of products from their quotes list.', 'yith-woocommerce-request-a-quote' ),
		'id'        => 'ywraq_enable_quote_again',
		'type'      => 'yith-field',
		'yith-type' => 'onoff',
		'default'   => 'no',
	),
	'quote_again_button_label'              => array(
		'name'      => __( '"Ask new quote" label', 'yith-woocommerce-request-a-quote' ),
		'desc'      => __( 'Enter the text to show.', 'yith-woocommerce-request-a-quote' ),
		'id'        => 'ywraq_quote_again_button_label',
		'type'      => 'yith-field',
		'required'  => true,
		'yith-type' => 'text',
		'deps'      => array(
			'id'    => 'ywraq_enable_quote_again',
			'value' => 'yes',
		),
		'default'   => _x( 'Ask a new quote', 'label of button to add ask a new quote for the same products', 'yith-woocommerce-request-a-quote' ),
	),
	'quote_endpoint_end'                    => array(
		'type' => 'sectionend',
		'id'   => 'quote_endpoint_end',
	),
);

return array( 'quote-endpoint' => apply_filters( 'ywraq_quote_endpoint_options', $options ) );
