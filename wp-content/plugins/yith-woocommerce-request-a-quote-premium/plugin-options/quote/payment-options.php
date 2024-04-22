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
	'quote_payment'           => array(
		'name' => __( 'Quote payment', 'yith-woocommerce-request-a-quote' ),
		'desc' => __( 'These options apply to all new quote requests, but can be overridden on the quote detail page.', 'yith-woocommerce-request-a-quote' ),
		'type' => 'title',
		'id'   => 'ywraq_quote_payment',
	),
	'pay_quote_now'           => array(
		'name'      => __( 'Redirect the user to "Pay for Quote" page', 'yith-woocommerce-request-a-quote' ),
		'desc'      => sprintf(
			'%s<br>%s<br>%s',
			__( 'If billing and shipping fields are filled, you can send the customer to the "Pay for Quote" Page.', 'yith-woocommerce-request-a-quote' ),
			__( 'In this page, neither billing nor shipping information will be requested.', 'yith-woocommerce-request-a-quote' ),
			__( 'If billing and shipping are empty, the user will be redirected to the default Checkout page.', 'yith-woocommerce-request-a-quote' )
		),
		'id'        => 'ywraq_pay_quote_now',
		'type'      => 'yith-field',
		'yith-type' => 'onoff',
		'default'   => apply_filters( 'ywraq_set_default_pay_quote_now', 'no' ),
	),
	'checkout_info'           => array(
		'name'      => __( 'Override checkout fields with the billing and shipping info of all orders', 'yith-woocommerce-request-a-quote' ),
		'type'      => 'yith-field',
		'yith-type' => 'select',
		'id'        => 'ywraq_checkout_info',
		'class'     => 'wc-enhanced-select',
		'desc'      => __( 'Choose whether to override the billing and shipping checkout fields of all orders.', 'yith-woocommerce-request-a-quote' ),
		'default'   => '-',
		'options'   => array(
			'-'        => __( 'Do not override billing and shipping info', 'yith-woocommerce-request-a-quote' ),
			'both'     => __( 'Override billing and shipping info', 'yith-woocommerce-request-a-quote' ),
			'billing'  => __( 'Override billing info', 'yith-woocommerce-request-a-quote' ),
			'shipping' => __( 'Override shipping info', 'yith-woocommerce-request-a-quote' ),
		),
	),
	'disable_shipping_method' => array(
		'name'      => __( 'Override shipping costs', 'yith-woocommerce-request-a-quote' ),
		'type'      => 'yith-field',
		'yith-type' => 'onoff',
		'id'        => 'ywraq_disable_shipping_method',
		'desc'      => __( 'Enable if you want to apply the shipping costs applied in the quote, and not the default shipping costs.', 'yith-woocommerce-request-a-quote' ),
		'default'   => apply_filters( 'override_shipping_option_default_value', 'yes' ),
	),
	// @since 1.6.3
	'lock_editing'            => array(
		'name'      => __( 'Lock the editing of checkout fields', 'yith-woocommerce-request-a-quote' ),
		'type'      => 'yith-field',
		'id'        => 'ywraq_lock_editing',
		'yith-type' => 'onoff',
		'desc'      => __( 'If enabled, the customer can not edit the checkout fields.', 'yith-woocommerce-request-a-quote' ),
		'default'   => 'no',
	),
	'quote_payment_end'       => array(
		'type' => 'sectionend',
		'id'   => 'ywraq_quote_payment_end',
	),
);

return array( 'quote-payment' => apply_filters( 'ywraq_quote_payment_options', $options ) );
