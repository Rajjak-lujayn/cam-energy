<?php //phpcs:ignore WordPress.Files.FileName.InvalidClassFileName

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

use Automattic\WooCommerce\Utilities\OrderUtil;

defined( 'ABSPATH' ) || exit;


/**
 * Implements the YITH_Request_Quote_Order_Meta_Box class.
 *
 * @class    YITH_YWRAQ_Order_Request
 * @since    1.0.0
 * @author   YITH <plugins@yithemes.com>
 * @package  YITH
 */
class YITH_Request_Quote_Order_Meta_Box {
	/**
	 * Output the metabox.
	 *
	 * @param   WP_Post|WC_Order  $post  Post or order object.
	 */
	public static function output( $post ) {
		global $post, $thepostid, $theorder;
		if ( ! $theorder && $post ) {
			$theorder = OrderUtil::init_theorder_object( $post );
		}

		if ( ! is_int( $thepostid ) && ( $post instanceof WP_Post ) ) {
			$thepostid = $post->ID;
		}
		$request      = $_REQUEST; //phpcs:ignore.
		$order        = $theorder;
		$is_quote     = $order->get_meta( 'ywraq_raq' );
		$is_new_quote = isset( $request['new_quote'] ) && $request['new_quote'];
		$data         = ( $post instanceof WP_Post ) ? get_post_meta( $post->ID ) : array();
		$meta_box_id  = 'yith-ywraq-metabox-order';

		if ( $is_new_quote || 'yes' === $is_quote ) {
			$args = include YITH_YWRAQ_DIR . 'plugin-options/metabox/ywraq-metabox-order.php';
			extract( $args );

			include YITH_YWRAQ_DIR . 'includes/admin/meta-boxes/view/tab.php';
		}
		
	}

	/**
	 * Save meta box data.
	 *
	 * @param   int  $order_id  Order ID.
	 *
	 * @throws Exception Required request data is missing.
	 */
	public static function save( $order ) {

		// Get order object.
		$post  = $_REQUEST;
		$order->update_meta_data( 'ywraq_raq', 'yes' );
		if ( isset( $post['yit_metaboxes']['ywraq_customer_name'] ) ) {
			$order->update_meta_data( 'ywraq_customer_name', sanitize_text_field( wp_unslash( $post['yit_metaboxes']['ywraq_customer_name'] ) ) );
		}
		if ( isset( $post['yit_metaboxes']['ywraq_customer_name'] ) ) {
			$order->update_meta_data( 'ywraq_customer_email', sanitize_text_field( wp_unslash( $post['yit_metaboxes']['ywraq_customer_email'] ) ) );
		}
		if ( isset( $post['yit_metaboxes']['ywraq_customer_name'] ) ) {
			$order->update_meta_data( 'ywraq_customer_message', sanitize_text_field( wp_unslash( $post['yit_metaboxes']['ywraq_customer_message'] ) ) );
		}

		$args = include YITH_YWRAQ_DIR . 'plugin-options/metabox/ywraq-metabox-order.php';
		foreach ( $args['tabs'] as $tab ) {
			foreach ( $tab['fields'] as $key => $field ) {
				$field_name    = isset( $field['private'] ) && ! $field['private'] ? $key : '_' . $key;
				$field['id']   = $field_name;
				$field['name'] = 'yit_metaboxes[' . $field_name . ']';
				self::sanitize_and_save_field( $field, $order );
			}
		}

		$order->save();

	}

	/**
	 * Sanitize and save a single field
	 *
	 * @param   array     $field  The field.
	 * @param   WC_Order  $order  The order.
	 *
	 * @since 4.12.0
	 */
	public static function sanitize_and_save_field( $field, $order ) {

		if ( ! $order && in_array( $field['type'], array( 'title' ), true ) ) {
			return;
		}
		$post          = $_POST;
		$meta_box_data = isset( $post['yit_metaboxes'] ) ? wp_unslash( $post['yit_metaboxes'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		if ( isset( $meta_box_data[ $field['id'] ] ) ) {
			if ( in_array( $field['type'], array( 'onoff', 'checkbox' ), true ) ) {
				$order->update_meta_data( $field['id'], 1 );
			} elseif ( in_array( $field['type'], array( 'toggle-element' ), true ) ) {
				if ( isset( $field['elements'] ) && $field['elements'] ) {
					$elements_value = $meta_box_data[ $field['id'] ];
					if ( $elements_value ) {
						if ( isset( $elements_value['box_id'] ) ) {
							unset( $elements_value['box_id'] );
						}

						foreach ( $field['elements'] as $element ) {
							foreach ( $elements_value as $key => $element_value ) {
								if ( isset( $field['onoff_field'] ) ) {
									$onoff_id                            = $field['onoff_field']['id'];
									$elements_value[ $key ][ $onoff_id ] = $element_value[ $onoff_id ] ?? 0;
								}
								if ( in_array( $element['type'], array( 'onoff', 'checkbox' ), true ) ) {
									$elements_value[ $key ][ $element['id'] ] = ! isset( $element_value[ $element['id'] ] ) ? 0 : 1;
								}

								if ( ! empty( $element['yith-sanitize-callback'] ) && is_callable( $element['yith-sanitize-callback'] ) ) {
									$elements_value[ $key ][ $element['id'] ] = call_user_func( $element['yith-sanitize-callback'], $elements_value[ $key ][ $element['id'] ] );
								}
							}
						}
					}
					$order->update_meta_data( $field['id'], maybe_serialize( $elements_value ) );
				}
			} else {
				$value = $meta_box_data[ $field['id'] ];
				if ( ! empty( $field['yith-sanitize-callback'] ) && is_callable( $field['yith-sanitize-callback'] ) ) {
					$value = call_user_func( $field['yith-sanitize-callback'], $value );
				}
				$order->update_meta_data( $field['id'], $value );

			}
		} elseif ( in_array( $field['type'], array( 'onoff', 'checkbox' ), true ) ) {
			$order->update_meta_data( $field['id'], 'no' );
		} elseif ( in_array( $field['type'], array( 'checkbox-array' ), true ) ) {
			$order->update_meta_data( $field['id'], array() );
		} else {
			$order->delete_meta_data( $field['id'] );
		}
		// phpcs:enable
	}
}
