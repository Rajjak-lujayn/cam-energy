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

$section  = array(
	'form_settings' => array(
		'name' => __( '"Request a quote" form', 'yith-woocommerce-request-a-quote' ),
		'type' => 'title',
		'id'   => 'ywraq_form_settings',
	),
	'inquiry_form' => array(
		'name'      => __( 'Choose the form to show in "Request a quote" page', 'yith-woocommerce-request-a-quote' ),
		'desc'      => __( 'Choose the form to show to request quote details. You can also add Contact Form 7, Gravity Form, Ninja forms or WPForms, which must be installed and activated.', 'yith-woocommerce-request-a-quote' ),
		'type'      => 'yith-field',
		'yith-type' => 'select',
		'class'     => 'wc-enhanced-select',
		'default'   => 'default',
		'options'   => apply_filters(
			'ywraq_form_type_list',
			array(
				'default' => __( 'Default', 'yith-woocommerce-request-a-quote' ),
			)
		),
		'id'        => 'ywraq_inquiry_form_type',
	),
);

$section = apply_filters( 'ywraq_additional_form_options', $section );

$section = array_merge(
	$section,
	array(
		'form_settings_end' => array(
			'type' => 'sectionend',
			'id'   => 'ywraq_form_settings_end',
		),
	)
);

$section2 = array(
	'form_fields_settings' => array(
		'name' => _x( 'Default form fields', 'Admin options title', 'yith-woocommerce-request-a-quote' ),
		'type' => 'title',
		'id'   => 'ywraq_form_fields_settings',
	),
	'default_table_form'         => array(
		'id'                    => 'ywraq_default_table_form',
		'type'                  => 'yith-field',
		'yith-type'             => 'default-form',
		'yith-display-row'      => false,
		'callback_default_form' => 'ywraq_get_default_form_fields',
	),
	'form_fields_settings_ends' => array(
		'type' => 'sectionend',
		'id'   => 'ywraq_form_fields_settings_end',
	),
	'form_options_settings' => array(
		'name' => _x( 'Default form options', 'Admin options title', 'yith-woocommerce-request-a-quote' ),
		'type' => 'title',
		'id'   => 'ywraq_form_options_settings',
	),
	'user_registration'          => array(
		'name'      => __( 'User registration', 'yith-woocommerce-request-a-quote' ),
		'desc'      => __( 'Choose whether to register the user or make this optional.', 'yith-woocommerce-request-a-quote' ),
		'id'        => 'ywraq_user_registration',
		'type'      => 'yith-field',
		'yith-type' => 'radio',
		'options'   => array(
			'none'   => __( 'Don\'t show a registration option in this form', 'yith-woocommerce-request-a-quote' ),
			'enable' => __( 'Show an optional checkbox to allow registration', 'yith-woocommerce-request-a-quote' ),
			'force'  => __( 'Force user registration', 'yith-woocommerce-request-a-quote' ),
		),
		'deps'      => array(
			'id'    => 'ywraq_inquiry_form_type',
			'value' => 'default',
			'type'  => 'hide',
		),
		'default'   => 'none',
	),
	'reCAPTCHA' => array(
		'name'      => __( 'Add a reCAPTCHA to the default form', 'yith-woocommerce-request-a-quote' ),
		// translators: html tags.
		'desc'      => sprintf( _x( 'Enable to add reCAPTCHA option in default form. %1$s To start using reCAPTCHA, you need to %2$s sign up for an API key %3$s pair for your site.', 'string with placeholder do not translate or remove it', 'yith-woocommerce-request-a-quote' ), '<br>', '<a href="https://www.google.com/recaptcha/admin">', '</a>' ),
		'id'        => 'ywraq_reCAPTCHA',
		'class'     => 'field_with_deps',
		'type'      => 'yith-field',
		'yith-type' => 'onoff',
		'deps'      => array(
			'id'    => 'ywraq_inquiry_form_type',
			'value' => 'default',
			'type'  => 'hide',
		),
		'default'   => 'no',
	),
	'reCAPTCHA_version'   => array(
		'name'              => __( 'Choose the reCAPTCHA version', 'yith-woocommerce-request-a-quote' ),
		'desc'              => __( 'Select the reCAPTCHA version.', 'yith-woocommerce-request-a-quote' ),
		'id'                => 'ywraq_reCAPTCHA_version',
		'type'              => 'yith-field',
		'yith-type'         => 'radio',
		'options'           => array(
			'v2' => _x( 'v2', 'reCAPTCHA version in admin options', 'yith-woocommerce-request-a-quote' ),
			'v3' => _x( 'v3 - Invisible', 'reCAPTCHA version in admin options', 'yith-woocommerce-request-a-quote' ),
		),
		'custom_attributes' => array(
			'data-deps'       => 'ywraq_inquiry_form_type,ywraq_reCAPTCHA',
			'data-deps_value' => 'default,yes',
		),
		'default'           => 'v2',
	),
	// @since 1.9.0
	'reCAPTCHA_sitekey'   => array(
		'name'              => __( 'Site key', 'yith-woocommerce-request-a-quote' ),
		'desc'              => __( 'Enter the reCAPTCHA site key', 'yith-woocommerce-request-a-quote' ),
		'id'                => 'ywraq_reCAPTCHA_sitekey',
		'type'              => 'yith-field',
		'yith-type'         => 'text',
		'required'          => true,
		'default'           => '',
		'custom_attributes' => array(
			'data-deps'       => 'ywraq_inquiry_form_type,ywraq_reCAPTCHA',
			'data-deps_value' => 'default,yes',
		),
	),
	// @since 1.9.0
	'reCAPTCHA_secretkey' => array(
		'name'              => __( 'Secret key', 'yith-woocommerce-request-a-quote' ),
		'desc'              => __( 'Enter reCAPTCHA secret key', 'yith-woocommerce-request-a-quote' ),
		'id'                => 'ywraq_reCAPTCHA_secretkey',
		'class'             => 'regular-input',
		'type'              => 'yith-field',
		'yith-type'         => 'text',
		'required'          => true,
		'default'           => '',
		'custom_attributes' => array(
			'data-deps'       => 'ywraq_inquiry_form_type,ywraq_reCAPTCHA',
			'data-deps_value' => 'default,yes',
		),
	),
	'autocomplete_default_form' => array(
		'name'      => __( 'Autocomplete Form', 'yith-woocommerce-request-a-quote' ),
		'desc'      => __( 'If enabled, the fields connected to WooCommerce will be filled automatically.', 'yith-woocommerce-request-a-quote' ),
		'id'        => 'ywraq_autocomplete_default_form',
		'type'      => 'yith-field',
		'yith-type' => 'onoff',
		'deps'      => array(
			'id'    => 'ywraq_inquiry_form_type',
			'value' => 'default',
			'type'  => 'hide',
		),
		'default'   => 'no',
	),
	'data_format_datepicker' => array(
		'name'      => __( 'Date picker format', 'yith-woocommerce-request-a-quote' ),
		'desc'      => __( 'Choose the format for the date picker in the default form.', 'yith-woocommerce-request-a-quote' ),
		'id'        => 'ywraq_date_format_datepicker',
		'type'      => 'yith-field',
		'yith-type' => 'date-format',
		'js'        => true,
		'default'   => 'dd/mm/yy',
		'deps'      => array(
			'id'    => 'ywraq_inquiry_form_type',
			'value' => 'default',
			'type'  => 'hide',
		),
	),
	'time-format-datepicker' => array(
		'name'      => __( 'Time picker format', 'yith-woocommerce-request-a-quote' ),
		'desc'      => __( 'Choose the format for the time picker in default form.', 'yith-woocommerce-request-a-quote' ),
		'id'        => 'ywraq_time_format_datepicker',
		'type'      => 'yith-field',
		'yith-type' => 'radio',
		'options'   => array(
			'12' => date( 'h:i A', current_time( 'timestamp', 0 ) ), //phpcs:ignore
			'24' => date( 'H:i', current_time( 'timestamp', 0 ) ),  //phpcs:ignore
		),
		'deps'      => array(
			'id'    => 'ywraq_inquiry_form_type',
			'value' => 'default',
			'type'  => 'hide',
		),
		'default'   => '24',
	),
	'form_options_settings_end' => array(
		'type' => 'sectionend',
		'id'   => 'ywraq_form_options_settings_end',
	),
);

return array( 'request-form' => apply_filters( 'ywraq_request_form_settings_options', array_merge( $section, $section2 ) ) );
