<?php
/**
 * Implements helper functions for YITH WooCommerce Reques a Quote
 *
 * @package YITH\RequestAQuote
 * @since   3.0.0
 * @author  YITH <plugins@yithemes.com>
 */

defined( 'ABSPATH' ) || exit;

$ywraq_option_version = get_option( 'ywraq_update_3_0', '2.0.0' );
if ( $ywraq_option_version && version_compare( $ywraq_option_version, '3.0.0', '<' ) ) {
	add_action( 'admin_init', 'ywraq_update_3_0' );
}
if ( $ywraq_option_version && version_compare( $ywraq_option_version, '3.1.0', '<' ) ) {
	add_action( 'admin_init', 'ywraq_update_3_1' );
}
if ( $ywraq_option_version && version_compare( $ywraq_option_version, '3.3.0', '<' ) ) {
	add_action( 'admin_init', 'ywraq_update_3_3' );
}
if ( $ywraq_option_version && version_compare( $ywraq_option_version, '4.0.0', '<' ) ) {
	add_action( 'admin_init', 'ywraq_update_4_0' );
}


/**
 * Update script.
 */
function ywraq_update_3_0() {
	// User roles main options.
	$enabled = get_option( 'ywraq_enabled_user_roles' );
	if ( ! $enabled ) {
		$roles = (array) get_option( 'ywraq_user_role' );
		if ( ! empty( $roles ) && in_array( 'all', $roles, true ) ) {
			update_option( 'ywraq_enabled_user_roles', 'all' );
		} else {
			update_option( 'ywraq_enabled_user_roles', 'roles' );
		}
	}

	// Out of stock  main options.
	$button_out_of_stock = get_option( 'ywraq_button_out_of_stock' );
	if ( ! $button_out_of_stock ) {
		$ywraq_allow_raq_out_of_stock     = get_option( 'ywraq_allow_raq_out_of_stock', 'no' );
		$ywraq_show_btn_only_out_of_stock = get_option( 'ywraq_show_btn_only_out_of_stock', 'no' );
		if ( 'yes' === $ywraq_allow_raq_out_of_stock ) {
			$button_out_of_stock = ( 'yes' === $ywraq_show_btn_only_out_of_stock ) ? 'only' : 'show';
		} else {
			$button_out_of_stock = 'hide';
		}

		update_option( 'ywraq_button_out_of_stock', $button_out_of_stock );
		delete_option( 'ywraq_allow_raq_out_of_stock' );
		delete_option( 'ywraq_show_btn_only_out_of_stock' );
	}

	// Button colors.
	$ywraq_raq_color = array(
		'bg_color'       => get_option( 'ywraq_layout_button_bg_color', '#0066b4' ),
		'bg_color_hover' => get_option( 'ywraq_layout_button_bg_color_hover', '#044a80' ),
		'color'          => get_option( 'ywraq_layout_button_color', '#ffffff' ),
		'color_hover'    => get_option( 'ywraq_layout_button_color_hover', '#ffffff' ),
	);

	update_option( 'ywraq_add_to_quote_button_color', $ywraq_raq_color );
	delete_option( 'ywraq_layout_button_bg_color' );
	delete_option( 'ywraq_layout_button_bg_color_hover' );
	delete_option( 'ywraq_layout_button_color' );
	delete_option( 'ywraq_layout_button_color_hover' );

	// Message to show the quote detail.
	$type = get_option( 'ywraq_how_show_after_sent_the_request' );

	if ( ! $type && 'simple_message' === $type ) {
		$message_after_request = get_option( 'ywraq_message_after_sent_the_request' );
		$show_detail           = get_option( 'ywraq_enable_link_details' );
		$enabled_order         = get_option( 'ywraq_enable_order_creation', 'yes' );
		if ( $show_detail && 'yes' === $show_detail ) {
			$message_after_request .= ' ' . get_option( 'ywraq_message_to_view_details' );
			if ( 'yes' === $enabled_order ) {
				$message_after_request .= ' %quote_number%';
			}

			update_option( 'ywraq_message_after_sent_the_request', $message_after_request );

			delete_option( 'ywraq_enable_link_details' );
			delete_option( 'ywraq_message_to_view_details' );
		}
	}

	// Expiry time.
	$ywraq_expired_time = get_option( 'ywraq_expired_time' );
	if ( false !== $ywraq_expired_time ) {
		update_option( 'ywraq_enable_expired_time', 0 == $ywraq_expired_time ? 'no' : 'yes' ); //phpcs:ignore
		$ywraq_expired_time = array( 'days' => $ywraq_expired_time );
		update_option( 'ywraq_expired_time', $ywraq_expired_time );
	}

	// Gateway selection.
	$choose_gateways = get_option( 'ywraq_select_gateway', array() );
	if ( ! $choose_gateways ) {
		update_option( 'ywraq_enable_specific_gateways', 'specific' );
	}

	// cron time.
	$cron_time = get_option( 'ywraq_cron_time' );
	if ( ! $cron_time ) {
		update_option( 'ywraq_automate_send_quote', 'yes' );
		update_option(
			'ywraq_cron_time',
			array(
				'time' => $cron_time,
				'type' => get_option(
					'ywraq_cron_time_type',
					'hours'
				),
			)
		);
	}

	$product_table_show = get_option( 'ywraq_product_table_show' );
	if ( ! $product_table_show ) {
		$product_table_show = array( 'images', 'quantity' );
		if ( 'yes' === get_option( 'ywraq_show_sku', 'no' ) ) {
			array_push( $product_table_show, 'sku' );
		}

		if ( 'yes' === get_option( 'ywraq_hide_total_column', 'no' ) ) {
			array_push( $product_table_show, 'line_total' );
		}

		if ( 'yes' === get_option( 'ywraq_show_total_in_list', 'no' ) ) {
			array_push( $product_table_show, 'total' );
		}

		if ( 'yes' === get_option( 'ywraq_show_preview', 'no' ) ) {
			array_push( $product_table_show, 'images' );
		}

		update_option( 'ywraq_product_table_show', $product_table_show );
		delete_option( 'ywraq_show_sku' );
		delete_option( 'ywraq_hide_total_column' );
		delete_option( 'ywraq_show_total_in_list' );
	}

	$old_form = get_option( 'ywraq_fields_form_options' );

	if ( $old_form ) {
		$default_form = ywraq_get_default_form_fields();
		$updated_form = array();

		foreach ( $old_form as $key => $value ) {
			$updated_form[ $key ]       = $value;
			$updated_form[ $key ]['id'] = $key;

			if ( isset( $value['enabled'] ) ) {
				$updated_form[ $key ]['enabled'] = $value['enabled'] ? 'yes' : 'no';
			}

			if ( isset( $value['required'] ) ) {
				$updated_form[ $key ]['required'] = $value['required'] ? 'yes' : 'no';
			}

			$updated_form[ $key ]['standard'] = isset( $default_form[ $key ], $default_form[ $key ]['standard'] ) ? $default_form[ $key ]['standard'] : false;
		}

		update_option( 'ywraq_default_table_form', $updated_form );
	}

	// customer registration.
	$force        = get_option( 'ywraq_force_user_to_register' );
	$registration = get_option( 'ywraq_add_user_registration_check' );

	if ( false !== $registration ) {
		$registration = 'yes' === $registration ? 'enable' : 'none';
	}

	if ( false !== $force ) {
		if ( 'yes' === $force ) {
			$registration = 'force';
		}
	}

	update_option( 'ywraq_user_registration', $registration );
	delete_option( 'ywraq_force_user_to_register' );
	delete_option( 'ywraq_add_user_registration_check' );

	// time and date_format.
	$date_format = get_option( 'ywraq-date-format-datepicker', 'dd/mm/yy' );
	$time_format = get_option( 'ywraq-time-format-datepicker', 'dd/mm/yy' );

	update_option( 'ywraq_time_format_datepicker', $time_format );
	update_option( 'ywraq_date_format_datepicker', $date_format );

	delete_option( 'ywraq-date-format-datepicker' );
	delete_option( 'ywraq-time-format-datepicker' );

	update_option( 'ywraq_update_3_0', '3.0.0' );
}


/**
 * Update script.
 */
function ywraq_update_3_3() {

	$user_type = get_option( 'ywraq_user_type', 'default' );
	if ( 'all' === $user_type || 'customers' === $user_type ) {
		$enabled_role = get_option( 'ywraq_enabled_user_roles' );
		if ( 'all' === $enabled_role ) {
			update_option( 'ywraq_user_type', 'all' );
		} else {
			update_option( 'ywraq_user_type', 'roles' );
		}
	}

	update_option( 'ywraq_update_3_0', '3.3.0' );
}


/**
 * Update script.
 */
function ywraq_update_3_1() {
	$ywraq_option_version = get_option( 'ywraq_update_3_0', '3.0.0' );
	if ( $ywraq_option_version && version_compare( $ywraq_option_version, '3.1.0', '<' ) ) {
		$form_type = get_option( 'ywraq_inquiry_form_type', 'default' );
		if ( 'default' !== $form_type ) {
			update_option( 'ywraq_title_before_form', '' );
		}
	}

	$allow_add_to_cart = get_option( 'ywraq_allow_add_to_cart' );
	if ( $allow_add_to_cart ) {
		update_option( 'ywraq_block_cart', ( 'yes' === $allow_add_to_cart ? 'no' : 'yes' ) );
		delete_option( 'ywraq_allow_add_to_cart' );
	}
	update_option( 'ywraq_update_3_0', '3.1.0' );
}


/**
 * Update script.
 */
function ywraq_update_4_0(){
	// Mantains the old pdf and disable the builder.
	update_option('ywraq_pdf_template_to_use', 'default' );
	update_option( 'ywraq_update_3_0', '4.0.0' );


	$url = get_option( 'ywraq_return_to_shop_after_sent_the_request_url' );
	update_option( 'ywraq_return_to_shop_after_sent_the_request_url_choice', ($url ? 'custom' : 'wc-page') );

	$url = get_option( 'ywraq_return_to_shop_url' );
	update_option( 'ywraq_return_to_shop_url_choice', ($url ? 'custom' : 'wc-page') );
}
