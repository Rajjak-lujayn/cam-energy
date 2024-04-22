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
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

$customer_name            = '';
$customer_message         = '';
$customer_email           = '';
$additional_field         = '';
$additional_field_2       = '';
$additional_field_3       = '';
$additional_email_content = '';
$customer_attachments     = '';
$status                   = ''; //phpcs:ignore
$button_disabled          = '';
$pdf_file                 = '';
$attachment_text          = '';

$billing_address = '';
$billing_phone   = '';
$billing_vat     = '';

$enable_expired                 = 'no';
$enable_custom_text             = 'no';
$additional_email_content_array = array();
$prev_of_3_1_0                  = false;

$order = $order ? $order : ywraq_get_order();

if ( $order ) {
	$customer_name          = $order->get_meta( 'ywraq_customer_name' );
	$customer_message       = $order->get_meta( 'ywraq_customer_message' );
	$request_response       = $order->get_meta( 'ywraq_request_response' );
	$request_response_after = $order->get_meta( 'ywraq_request_response_after' );
	$customer_email         = $order->get_meta( 'ywraq_customer_email' );
	$additional_field       = $order->get_meta( 'ywraq_customer_additional_field' );
	$additional_field_2     = $order->get_meta( 'ywraq_customer_additional_field_2' );
	$additional_field_3     = $order->get_meta( 'ywraq_customer_additional_field_3' );
	$customer_attachments   = $order->get_meta( 'ywraq_customer_attachment' );
	$other_fields           = $order->get_meta( 'ywraq_other_email_fields' );
	$billing_address        = $order->get_meta( 'ywraq_billing_address' );
	$billing_phone          = $order->get_meta( 'ywraq_billing_phone' );
	$billing_vat            = $order->get_meta( 'ywraq_billing_vat' );

	// phpcs:enable
	$additional_email_content_array['ywraq_customer_name']    = $customer_name;
	$additional_email_content_array['ywraq_customer_email']   = $customer_email;
	$additional_email_content_array['ywraq_customer_message'] = $customer_message;

	if ( '' !== $billing_address ) {
		$additional_email_content_array[ esc_html__( 'Billing Address', 'yith-woocommerce-request-a-quote' ) ] = $billing_address;
	}

	if ( '' !== $billing_phone ) {
		$additional_email_content_array[ esc_html__( 'Billing Phone', 'yith-woocommerce-request-a-quote' ) ] = $billing_phone;
	}

	if ( '' !== $billing_vat ) {
		$additional_email_content_array[ esc_html__( 'Billing Vat', 'yith-woocommerce-request-a-quote' ) ] = $billing_vat;
	}

	if ( '' !== $additional_field ) {
		$additional_email_content_array[ esc_html( get_option( 'ywraq_additional_text_field_label' ) ) ] = $additional_field;
	}

	if ( '' !== $additional_field_2 ) {
		$additional_email_content_array[ esc_html( get_option( 'ywraq_additional_text_field_label_2' ) ) ] = $additional_field_2;
	}

	if ( '' !== $additional_field_3 ) {
		$additional_email_content_array[ esc_html( get_option( 'ywraq_additional_text_field_label_3' ) ) ] = $additional_field_3;
	}

	if ( ! empty( $customer_attachments ) ) {
		if ( isset( $customer_attachments['url'] ) ) {
			$additional_email_content_array[ esc_html( get_option( 'ywraq_additional_text_field_label_3' ) ) ] = '<a href="' . $customer_attachments['url'] . '" target="_blank">' . $customer_attachments['url'] . '</a>';
		} else {
			foreach ( $customer_attachments as $key => $item ) {
				$attachment_text .= '<div><strong>' . $key . '</strong>:  <a href="' . $item . '" target="_blank">' . $item . '</a></div>';
			}
			$additional_email_content_array[ esc_html( get_option( 'ywraq_additional_text_field_label_3' ) ) ] = $attachment_text;
		}
	}

	$additional_email_content_array = array_merge( (array) $other_fields, $additional_email_content_array );

	$raq_request = $order->get_meta( '_raq_request' );
	if ( $raq_request ) {
		foreach ( $raq_request as $key_field => $field ) {
			if ( in_array( $key_field, array( 'first_name', 'last_name', 'email', 'message' ) ) && isset( $field['label'] ) && isset( $additional_email_content_array[ $field['label'] ] ) ) {
				unset( $additional_email_content_array[ $field['label'] ] );
			}
		}
	}

	$additional_email_content_array = apply_filters( 'ywraq_additional_email_content_array', $additional_email_content_array, $raq_request );
	$accepted_statuses              = apply_filters( 'ywraq_quote_accepted_statuses_send', array( 'ywraq-new', 'ywraq-rejected' ) );

	$_status = $order->get_status();
	if ( ! ( defined( 'WP_DEBUG' ) && WP_DEBUG ) && ! $order->has_status( $accepted_statuses ) ) {
		$button_disabled = 'disabled="disabled"';
	}
	if ( file_exists( YITH_Request_Quote_Premium()->get_pdf_file_path( $order->get_id() ) ) ) {
		$pdf_file = YITH_Request_Quote_Premium()->get_pdf_file_url( $order->get_id() );
	}


	// retro compatibility.
	$enable_expired = $order->get_meta( '_ywraq_enable_expiry_date' );
	$expired        = $order->get_meta( '_ywcm_request_expire' );
	$enable_expired = empty( $enable_expired ) ? ! empty( $expired ) ? 'yes' : 'no' : $enable_expired;

	$raq_version   = $order->get_meta( '_ywraq_version' );
	$raq_version   = $raq_version ? $raq_version : YITH_YWRAQ_VERSION;
	$prev_of_3_1_0 = version_compare( $raq_version, '3.1.0', '<' );

	$enable_custom_text = $order->get_meta( '_ywraq_enable_custom_text' );
	$message_before     = $order->get_meta( '_ywcm_request_response' );
	$message_after      = $order->get_meta( '_ywraq_request_response_after' );
	$enable_expired     = empty( $enable_custom_text ) ? ! ( empty( $message_before ) || empty( $message_after ) ) ? 'yes' : 'no' : $enable_custom_text;
}

$order_meta = array(
	'label'    => esc_html__( 'Quote options', 'yith-woocommerce-request-a-quote' ),
	'pages'    => array( 'shop_order', 'woocommerce_page_wc-orders' ),
	'context'  => 'normal',
	'priority' => 'high',
	'tabs'     => array(
		'settings' => array(
			'label' => esc_html__( 'Settings', 'yith-woocommerce-request-a-quote' ),
		),
	),
	'class'    => 'yith-plugin-ui'
);

$fields = array(

	'ywraq_customer_request_title' => array(
		'type'          => 'html',
		'html'          => sprintf( '<h3>%s</h3>', esc_html__( 'Customer\'s request:', 'yith-woocommerce-request-a-quote' ) ),
		'std'           => '',
		'extra-classes' => 'yith-metabox-inner-title',
	),

	'ywraq_customer_request' => array(
		'label'            => esc_html__( 'Name', 'yith-woocommerce-request-a-quote' ),
		'desc'             => '',
		'private'          => false,
		'yith-display-row' => false,
		'content'          => $additional_email_content_array,
		'type'             => 'customer-request',
	),
);


if ( ! empty( $additional_email_content ) ) {


	$fields['ywraq_customer_additional_email_content'] = array(
		'label'            => esc_html__( 'Additional email content', 'yith-woocommerce-request-a-quote' ),
		'desc'             => $additional_email_content,
		'type'             => 'simple-text',
		'yith-display-row' => false,
	);
}

if ( ! empty( $additional_field ) ) {
	$fields['ywraq_customer_additional_field'] = array(
		'label' => esc_html__( 'Customer\'s additional field', 'yith-woocommerce-request-a-quote' ),
		'desc'  => $additional_field,
		'type'  => 'simple-text',
	);
}

if ( ! empty( $additional_field ) ) {
	$fields['ywraq_customer_additional_field_2'] = array(
		'label' => esc_html__( 'Customer\'s additional field', 'yith-woocommerce-request-a-quote' ),
		'desc'  => $additional_field_2,
		'type'  => 'simple-text',
	);
}

if ( ! empty( $additional_field_3 ) ) {
	$fields['ywraq_customer_additional_field_3'] = array(
		'label' => esc_html__( 'Customer\'s additional field', 'yith-woocommerce-request-a-quote' ),
		'desc'  => $additional_field_3,
		'type'  => 'simple-text',
	);
}


$group_2 = array(
	'ywcm_request_response_title' => array(
		'type' => 'html',
		'html' => sprintf( '<h3>%s</h3>', esc_html__( 'Quote general options', 'yith-woocommerce-request-a-quote' ) ),
		'std'  => '',
	),

	'ywraq_enable_custom_text'     => array(
		'label' => esc_html__( 'Show custom text in email content', 'yith-woocommerce-request-a-quote' ),
		'type'  => 'onoff',
		'desc'  => esc_html__( 'Enable to add custom messages to the default email template.', 'yith-woocommerce-request-a-quote' ),
		'std'   => 'no',
	),

	// @since 1.3.0
	'ywcm_request_response'        => array(
		'label' => esc_html__( 'Custom text before product list:', 'yith-woocommerce-request-a-quote' ),
		'type'  => 'textarea',
		'desc'  => esc_html__( 'Enter a custom message to show before the product list in the quote email.', 'yith-woocommerce-request-a-quote' ),
		'std'   => '',
		'deps'  => array(
			'id'    => '_ywraq_enable_custom_text',
			'value' => 'yes',
		),
	),

	// @since 1.3.0
	'ywraq_request_response_after' => array(
		'label' => esc_html__( 'Custom text after product list:', 'yith-woocommerce-request-a-quote' ),
		'type'  => 'textarea',
		'desc'  => esc_html__( 'Enter a custom message to show after the product list in the quote email.', 'yith-woocommerce-request-a-quote' ),
		'std'   => '',
		'deps'  => array(
			'id'    => '_ywraq_enable_custom_text',
			'value' => 'yes',
		),
	),

	// @since 1.3.0
	'ywraq_optional_attachment'    => array(
		'label' => esc_html__( 'Upload an optional Attachment', 'yith-woocommerce-request-a-quote' ),
		'type'  => 'media',
		'desc'  => esc_html__( 'Upload a file to attach to the quote email.', 'yith-woocommerce-request-a-quote' ),
		'std'   => '',
	),


	// @since 3.0.0
	'ywraq_enable_expiry_date'     => array(
		'label' => esc_html__( 'Set a specific expiry date for this quote', 'yith-woocommerce-request-a-quote' ),
		'type'  => 'onoff',
		'desc'  => sprintf(
			'%s<br>%s',
			esc_html__( 'Enable if you want to set a specific expiry date for this quote.', 'yith-woocommerce-request-a-quote' ),
			esc_html__( 'This will override the "Quote options" settings.', 'yith-woocommerce-request-a-quote' )
		),
		'std'   => $enable_expired,

	),

	'ywcm_request_expire'                    => array(
		'label'             => esc_html__( 'This quote will expire on', 'yith-woocommerce-request-a-quote' ),
		'desc'              => esc_html__( 'Choose an expiry date for this quote.', 'yith-woocommerce-request-a-quote' ),
		'type'              => 'datepicker',
		'std'               => apply_filters( 'ywraq_set_default_expire_date', '' ),
		'custom_attributes' => array(
			'data-deps'       => '_ywraq_enable_expiry_date',
			'data-deps_value' => 'yes',
		),
	),

	// @since 1.3.0
	'ywraq_request_my_account_admin_message' => array(
		'label' => esc_html__( 'Message to show in "My account > Quote detail"', 'yith-woocommerce-request-a-quote' ),
		'type'  => 'textarea',
		'desc'  => esc_html__( 'Enter an optional message to show in the customer\'s  "My account > Quote detail" page.', 'yith-woocommerce-request-a-quote' ),
		'std'   => '',
	),

	'ywcm_request_response_title2' => array(
		'type' => 'html',
		'html' => sprintf( '<h3>%s</h3>', esc_html__( 'Quote payment options', 'yith-woocommerce-request-a-quote' ) ),
		'std'  => '',
	),

	'ywraq_override_quote_payment_options' => array(
		'label' => esc_html__( 'Override quote payment options', 'yith-woocommerce-request-a-quote' ),
		'type'  => 'onoff',
		'desc'  => esc_html__( 'Enable to set specific quote payment options for this quote. It will override the quote payment options in the general settings.', 'yith-woocommerce-request-a-quote' ),
		'std'   => $prev_of_3_1_0 ? 'yes' : 'no',
	),

	// @since 1.6.3
	'ywraq_pay_quote_now'                  => array(
		'label'             => esc_html__( 'Redirect the user to "Pay for Quote" page', 'yith-woocommerce-request-a-quote' ),
		'type'              => 'onoff',
		'desc'              => sprintf(
			'%s<br>%s<br>%s',
			esc_html__( 'If billing and shipping fields are filled, you can send the customer to "Pay for Quote" Page.', 'yith-woocommerce-request-a-quote' ),
			esc_html__( 'In this page, neither billing nor shipping information will be requested.', 'yith-woocommerce-request-a-quote' ),
			esc_html__( 'If billing and shipping are empty, the user will be redirect to the default Checkout page.', 'yith-woocommerce-request-a-quote' )
		),
		'std'               => apply_filters( 'ywraq_set_default_pay_quote_now', get_option( 'ywraq_pay_quote_now', 'no' ) ),
		'custom_attributes' => array(
			'data-deps'       => '_ywraq_override_quote_payment_options',
			'data-deps_value' => 'yes',
		),
	),

	// @since 1.6.3
	'ywraq_checkout_info'                  => array(
		'label'             => esc_html__( 'Override checkout fields with the billing and shipping info of this order', 'yith-woocommerce-request-a-quote' ),
		'type'              => 'select',
		'class'             => 'wc-enhanced-select',
		'desc'              => esc_html__( 'Choose whether to override the billing and shipping checkout fields of this order.', 'yith-woocommerce-request-a-quote' ),
		'std'               => get_option( 'ywraq_checkout_info', '-' ),
		'options'           => array(
			'-'        => esc_html__( 'Do not override billing and shipping info', 'yith-woocommerce-request-a-quote' ),
			'both'     => esc_html__( 'Override billing and shipping info', 'yith-woocommerce-request-a-quote' ),
			'billing'  => esc_html__( 'Override billing info', 'yith-woocommerce-request-a-quote' ),
			'shipping' => esc_html__( 'Override shipping info', 'yith-woocommerce-request-a-quote' ),
		),
		'custom_attributes' => array(
			'data-deps'       => '_ywraq_override_quote_payment_options,_ywraq_pay_quote_now',
			'data-deps_value' => 'yes,no',
		),
	),

	// @since 1.6.3
	'ywraq_disable_shipping_method'        => array(
		'label'             => esc_html__( 'Override shipping costs', 'yith-woocommerce-request-a-quote' ),
		'type'              => 'onoff',
		'desc'              => esc_html__( 'Enable if you want to apply the shipping costs applied in this quote and not the default shipping.', 'yith-woocommerce-request-a-quote' ),
		'std'               => apply_filters( 'override_shipping_option_default_value', get_option( 'ywraq_disable_shipping_method', 'yes' ) ),
		'custom_attributes' => array(
			'data-deps'       => '_ywraq_override_quote_payment_options,_ywraq_pay_quote_now',
			'data-deps_value' => 'yes,no',
		),
	),

	// @since 1.6.3
	'ywraq_lock_editing'                   => array(
		'label'             => esc_html__( 'Lock the editing of checkout fields', 'yith-woocommerce-request-a-quote' ),
		'type'              => 'onoff',
		'desc'              => esc_html__( 'If enabled, the customer will be not able to edit the checkout fields.', 'yith-woocommerce-request-a-quote' ),
		'std'               => get_option( 'ywraq_lock_editing', 'no' ),
		'custom_attributes' => array(
			'data-deps'       => '_ywraq_override_quote_payment_options,_ywraq_pay_quote_now',
			'data-deps_value' => 'yes,no',
		),
	),

	'ywraq_customer_sep2' => array(
		'type' => 'sep',
	),

	'ywraq_safe_submit_field' => array(
		'desc' => esc_html__( 'Set an expiration date for this quote', 'yith-woocommerce-request-a-quote' ),
		'type' => 'hidden',
		'std'  => '',
		'val'  => '',
	),

	'ywraq_raq' => array(
		'desc'    => '',
		'type'    => 'hidden',
		'private' => false,
		'std'     => 'yes',
		'value'     => 'yes',
		'val'     => 'yes',
		'default'     => 'yes',
	),

	'ywraq_quote_check' => array(
		'type' => 'html',
		'html' => sprintf( '<h3>%s</h3>', esc_html__( 'Check quote before sending', 'yith-woocommerce-request-a-quote' ) ),
		'std'  => '',
	),

	'ywraq_email_preview' => array(
		'type'   => 'inline-fields',
		'label'  => esc_html__( 'Send a test email to:', 'yith-woocommerce-request-a-quote' ),
		'desc'   => sprintf( '<div class="ywraq-email-success">%s</div><div class="ywraq-email-error">%s</div>%s', esc_html__( 'The email has been sent.', 'yith-woocommerce-request-a-quote' ), esc_html__( 'Please add a valid email address.', 'yith-woocommerce-request-a-quote' ),
			esc_html__( 'Send a test email to check the content of the quote before sending it to the customer.', 'yith-woocommerce-request-a-quote' ) ),
		'fields' => array(
			'email' => array(
				'type'              => 'text',
				'custom_attributes' => 'placeholder="youremail@gmail.com"',
				'std'               => '',
				'class'             => 'text-short',
			),
			'html'  => array(
				'type' => 'html',
				'html' => '<a href="#" class="button button-secondary yith-button-ghost" id="ywraq_check_email">' . esc_html__( 'Send email', 'yith-woocommerce-request-a-quote' ) . '</a>',
				'std'  => '',
			),
		),
	),
);


$fields = apply_filters( 'yith_ywraq_metabox_fields', array_merge( $fields, $group_2 ), $fields, $group_2 );

if ( 'yes' === get_option( 'ywraq_enable_pdf', 'yes' ) ) {
	$pdf_url                     = '';
	$pdf_url                     = YITH_Request_Quote_Premium()->get_pdf_file_url( $order->get_id() );
	$button_create               = '<a href="#" target="_blank" data-pdf="' . $pdf_url . '" class="button button-secondary yith-button-ghost" id="ywraq_pdf_button">' . esc_html__( 'See a PDF preview', 'yith-woocommerce-request-a-quote' ) . '</a>';
	$button_create               .= '<span class="description">' . esc_html__( 'Click to see a PDF preview of this quote.', 'yith-woocommerce-request-a-quote' ) . '</span>';
	$fields['ywraq_pdf_preview'] = array(
		'type'   => 'inline-fields',
		'label'  => esc_html__( 'PDF preview', 'yith-woocommerce-request-a-quote' ),
		'fields' => array(
			'html' => array(
				'type' => 'html',
				'html' => $button_create,
				'std'  => '',

			),
		),
	);
}

if ( ! empty( $customer_email ) && ! empty( $customer_name ) ) {

	$button_label  = apply_filters( 'ywraq_admin_send_quote_label', esc_html__( 'Send Quote', 'yith-woocommerce-request-a-quote' ) );
	$button_submit = '<input type="submit" class="button button-primary" id="ywraq_submit_button" value="' . $button_label . '" ' . $button_disabled . '>';

	$fields['ywraq_submit_button'] = array(
		'html' => $button_submit,
		'type' => 'html',
	);
}

$order_meta['tabs']['settings']['fields'] = apply_filters( 'ywraq_order_metabox', $fields );

return $order_meta;
