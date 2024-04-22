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

use Elementor\Plugin;
use Automattic\WooCommerce\Utilities\OrderUtil;
defined( 'ABSPATH' ) || exit;

/**
 * Implements helper functions for YITH Request a Quote for WooCommerce
 *
 * @since   1.0.0
 * @author  YITH <plugins@yithemes.com>
 * @package YITH\RequestAQuote
 */

/* Admin Functions */
if ( ! function_exists( 'ywraq_get_pages' ) ) {

	/**
	 * Return the list of site's pages
	 *
	 * @return array
	 * @since  2.0
	 */
	function ywraq_get_pages() {

		$args    = array(
			'sort_order'   => 'asc',
			'hierarchical' => 1,
			'exclude'      => '',
			'include'      => '',
			'meta_key'     => '', //phpcs:ignore
			'meta_value'   => '', //phpcs:ignore
			'authors'      => '',
			'child_of'     => 0,
			'parent'       => -1,
			'exclude_tree' => '',
			'number'       => '',
			'offset'       => 0,
			'post_type'    => 'page',
			'post_status'  => 'publish',
		);
		$pages   = get_pages( $args );
		$options = array();
		if ( $pages ) {
			foreach ( $pages as $page ) {
				$options[ $page->ID ] = $page->post_title;
			}
		}

		return $options;

	}
}

if ( ! function_exists( 'yith_ywraq_render_button' ) ) {
	/**
	 * Render the Request a quote button.
	 *
	 * @param mixed $product_id .
	 * @param array $args       .
	 */
	function yith_ywraq_render_button( $product_id = false, $args = array() ) {


		if ( ! $product_id ) {
			global $product, $post;
			if( !$product instanceof WC_Product && $post instanceof WP_Post){
				$product = wc_get_product( $post->ID);
			}
		} else {
			$product = wc_get_product( $product_id );
		}

		/**
		 * APPLY_FILTERS:yith_ywraq_before_print_button
		 *
		 * Show or not the quote button
		 *
		 * @param bool $hide_button If true hide the quote button.
		 *
		 * @return bool
		 */
		if ( ! apply_filters( 'yith_ywraq_before_print_button', $product, $product ) ) {
			return;
		}
		global $woocommerce_loop;
		$style_button = get_option( 'ywraq_show_btn_link', 'button' ) === 'button' ? 'button' : 'ywraq-link';
		$style_button = $args['style'] ?? $style_button;

		$product_id = $product->get_id();

		$general_color = get_option(
			'ywraq_add_to_quote_button_color',
			array(
				'bg_color'       => '#0066b4',
				'bg_color_hover' => '#044a80',
				'color'          => '#ffffff',
				'color_hover'    => '#ffffff',
			)
		);

		$default_args = array(
			'class'         => 'add-request-quote-button ' . $style_button,
			'wpnonce'       => wp_create_nonce( 'add-request-quote-' . $product_id ),
			'product_id'    => $product_id,
			'label'         => ywraq_get_label( 'btn_link_text' ),
			'label_browse'  => ywraq_get_label( 'browse_list' ),
			'template_part' => 'button',
			'rqa_url'       => YITH_Request_Quote()->get_raq_page_url(),
			'exists'        => $product->is_type( 'variable' ) ? false : YITH_Request_Quote()->exists( $product_id ),
			'colors'        => $general_color,
			'icon'          => 0,
		);

		$args = shortcode_atts( $default_args, $args );

		// Remove the array colors if the style is general.
		$array_color = array_filter( array_diff( $args['colors'], $general_color ) );

		if ( empty( $array_color ) ) {
			unset( $args['colors'] );
		}

		if ( $product->is_type( 'variable' ) ) {
			$args['variations'] = implode( ',', YITH_Request_Quote()->raq_variations );
		}

		/**
		 * APPLY_FILTERS: ywraq_add_to_quote_args
		 *
		 * Filter arguments to pass to the template.
		 *
		 * @param array $args List of arguments to pass to the template
		 *
		 * @return array
		 */
		$args['args']    = apply_filters( 'ywraq_add_to_quote_args', $args );
		$template_button = 'add-to-quote.php';

		$wcloop_name = ! is_null( $woocommerce_loop ) && ! is_null( $woocommerce_loop['name'] ) ? $woocommerce_loop['name'] : '';


		/**
		 * APPLY_FILTERS: yith_ywraq_render_button_check_loop
		 *
		 * Filter the possibility to show or hide the button on loop.
		 *
		 * @param bool $render_button If true show buttons on loop.
		 * @param int  $product_id    Product id.
		 *
		 * @return bool
		 */
		$show_in_loop = apply_filters(
			'yith_ywraq_render_button_check_loop',
			in_array(
				$wcloop_name,
				array(
					'up-sells',
					'related',
				),
				true
			),
			$product_id
		);

		if ( ( function_exists( 'yith_wapo_product_has_blocks' ) || class_exists( 'YITH_WAPO_Type' ) ) && ( ! is_product() || $show_in_loop ) ) {
			$has_addons = function_exists( 'yith_wapo_product_has_blocks' ) ? yith_wapo_product_has_blocks( $product_id ) : '';
			$has_addons = class_exists( 'YITH_WAPO_Type' ) ? YITH_WAPO_Type::getAllowedGroupTypes( $product_id ) : $has_addons;

			if ( ! empty( $has_addons ) ) {
				$template_button = 'add-to-quote-addons.php';
			}
		}

		if ( $product->is_type( 'yith-composite' ) && ( ! is_product() || $show_in_loop ) ) {
			$template_button = 'add-to-quote-addons.php';
		}

		wc_get_template( $template_button, $args, '', YITH_YWRAQ_TEMPLATE_PATH . '/' );
	}
}

if ( ! function_exists( 'yith_ywraq_get_roles' ) ) {
	/**
	 * Return the roles of users
	 *
	 * @return array
	 * @since 1.3.0
	 */
	function yith_ywraq_get_roles() {
		global $wp_roles;
		$roles = array();

		foreach ( $wp_roles->get_names() as $key => $role ) {
			$roles[ $key ] = translate_user_role( $role );
		}

		return $roles;
	}
}

/* Frontend Functions */
if ( ! function_exists( 'yith_ywraq_show_button_in_other_pages' ) ) {
	/**
	 * Check if the button can be showed on page.
	 *
	 * @return bool
	 */
	function yith_ywraq_show_button_in_other_pages() {

		$general_show_btn = get_option( 'ywraq_show_btn_other_pages' );
		if ( 'yes' !== $general_show_btn ) {
			return false;
		}

		global $product, $sitepress;

		if ( ! $product instanceof WC_Product ) {
			global $post;
			$product_id = $post instanceof WP_Post ? $post->ID : '';
		} else {
			$product_id = $product->get_id();
		}

		if ( empty( $product_id ) ) {
			return false;
		}

		// WPML Integration.
		$product_id = apply_filters( 'wpml_object_id', $product_id, 'product', true );

		if ( ! $product->is_type( 'variable' ) && 'only' === get_option( 'ywraq_button_out_of_stock', 'hide' ) && $product->is_in_stock() ) {
			return false;
		}

		return ! ywraq_is_in_exclusion( $product_id );

	}
}

if ( ! function_exists( 'yith_ywraq_check_notices' ) ) {
	/**
	 * Check notices.
	 */
	function yith_ywraq_check_notices() {
		$all_notices = array();
		$session     = YITH_Request_Quote()->session_class;
		if ( ! is_null( $session ) ) {
			$all_notices = $session->get( 'yith_ywraq_notices', array() );
		}

		return count( $all_notices );
	}
}

if ( ! function_exists( 'ywraq_get_label' ) ) {
	/**
	 * Return or print a label from a specific $key
	 *
	 * @param string $key  .
	 * @param bool   $echo .
	 *
	 * @return string|void
	 */
	function ywraq_get_label( $key, $echo = false ) {

		$option_name = 'ywraq_show_' . $key;
		$option      = get_option( $option_name );

		switch ( $key ) {
			case 'product_added':
				/**
				 * APPLY_FILTERS:yith_ywraq_product_added_to_list_message
				 *
				 * Filter the message displayed when the product is added to the list.
				 *
				 * @param string $message Message.
				 *
				 * @return string
				 */
				$label = $option ?? apply_filters( 'yith_ywraq_product_added_to_list_message', esc_html__( 'Product added to the list!', 'yith-woocommerce-request-a-quote' ) );
				break;
			case 'browse_list':
				/**
				 * APPLY_FILTERS:ywraq_product_added_view_browse_list
				 *
				 * Filter the message "Browse the list"
				 *
				 * @param string $message Message.
				 *
				 * @return string
				 */
				$label = $option ?? apply_filters( 'ywraq_product_added_view_browse_list', esc_html__( 'Browse the list', 'yith-woocommerce-request-a-quote' ) );
				break;
			case 'btn_link_text':
				/**
				 * APPLY_FILTERS:ywraq_product_add_to_quote
				 *
				 * Filter the label of button.
				 *
				 * @param string $label Label of button.
				 *
				 * @return string
				 */
				$label = $option ?? apply_filters( 'ywraq_product_add_to_quote', esc_html__( 'Add to Quote', 'yith-woocommerce-request-a-quote' ) );
				break;
			case 'already_in_quote':
				/**
				 * APPLY_FILTERS:yith_ywraq_product_already_in_list_message
				 *
				 * Filter the message which says that the product is already on list.
				 *
				 * @param string $message Message.
				 *
				 * @return string
				 */
				$label = $option ?? apply_filters( 'yith_ywraq_product_already_in_list_message', esc_html__( 'Product already in the list.', 'yith-woocommerce-request-a-quote' ) );
				break;
			case 'accept':
				/**
				 * APPLY_FILTERS:ywraq_accept_link_label
				 *
				 * Filter the label of button to accept the quote
				 *
				 * @param string $label Label.
				 *
				 * @return string
				 */
				$label = get_option( 'ywraq_accept_link_label', esc_html__( 'Accept', 'yith-woocommerce-request-a-quote' ) );
				break;
			case 'reject':
				/**
				 * APPLY_FILTERS:ywraq_reject_link_label
				 *
				 * Filter the label of button to reject the quote.
				 *
				 * @param string $label Label.
				 *
				 * @return string
				 */
				$label = get_option( 'ywraq_reject_link_label', esc_html__( 'Reject', 'yith-woocommerce-request-a-quote' ) );
				break;
			default:
				$label = '';
		}
		/**
		 * APPLY_FILTERS:ywraq_get_label
		 *
		 * Filter the label requested.
		 *
		 * @param string $label Label.
		 * @param string $key   Type of label requested.
		 *
		 * @return string
		 */
		$label = apply_filters( 'ywraq_get_label', $label, $key );

		if ( $echo ) {
			echo esc_html( $label );
		} else {
			return $label;
		}
	}
}

if ( ! function_exists( 'ywraq_get_token' ) ) {
	/**
	 * Add a token to the mask quote number.
	 *
	 * @param string $action   .
	 * @param int    $order_id .
	 * @param string $email    .
	 *
	 * @return string
	 */
	function ywraq_get_token( $action, $order_id, $email ) {
		return wp_hash( $action . '|' . $order_id . '|' . $email, 'yith-woocommerce-request-a-quote' );
	}
}

if ( ! function_exists( 'ywraq_verify_token' ) ) {
	/**
	 * Check the token.
	 *
	 * @param string $token    Token.
	 * @param string $action   .
	 * @param int    $order_id .
	 * @param string $email    .
	 *
	 * @return int
	 */
	function ywraq_verify_token( $token, $action, $order_id, $email ) {
		$expected = wp_hash( $action . '|' . $order_id . '|' . $email, 'yith-woocommerce-request-a-quote' );
		if ( hash_equals( $expected, $token ) ) {
			return 1;
		}

		return 0;
	}
}

if ( ! function_exists( 'ywraq_is_in_exclusion' ) ) {
	/**
	 * Check if product is in exclusion list
	 *
	 * @param int $product_id Product id.
	 *
	 * @return boolean
	 * @since  2.0.0
	 *
	 */
	function ywraq_is_in_exclusion( $product_id ) {

		$is_excluded    = false;
		$exclusion_prod = array_filter( explode( ',', get_option( 'yith-ywraq-exclusions-prod-list', '' ) ) );
		$exclusion_cat  = array_filter( explode( ',', get_option( 'yith-ywraq-exclusions-cat-list', '' ) ) );
		$exclusion_tag  = array_filter( explode( ',', get_option( 'yith-ywraq-exclusions-tag-list', '' ) ) );

		$product_cat        = array();
		$product_tag        = array();
		$product_categories = get_the_terms( $product_id, 'product_cat' );
		$product_tags       = get_the_terms( $product_id, 'product_tag' );

		if ( ! empty( $product_categories ) ) {
			foreach ( $product_categories as $cat ) {
				$product_cat[] = $cat->term_id;
			}
		}

		$intersect_cat = array_intersect( $product_cat, $exclusion_cat );

		if ( ! empty( $product_tags ) ) {
			foreach ( $product_tags as $tag ) {
				$product_tag[] = $tag->term_id;
			}
		}

		$intersect_tag = array_intersect( $product_tag, $exclusion_tag ); //phpcs:ignore

		if ( in_array(
				$product_id,
			     $exclusion_prod ) || ! empty( $intersect_cat ) || ! empty( $intersect_tag ) ) { //phpcs:ignore
			$is_excluded = true;
		}

		// can be hide or show if it 'show' the list is reversed.
		$is_excluded = ( 'hide' === get_option(
				'ywraq_exclusion_list_setting',
				'hide'
			) ) ? $is_excluded : ! $is_excluded;

		return $is_excluded;
	}
}

if ( ! function_exists( 'ywraq_get_quote_status_list' ) ) {
	/**
	 * Return the list of status.
	 *
	 * @return array
	 */
	function ywraq_get_quote_status_list() {
		/**
		 * APPLY_FILTERS:ywraq_request_list_status_filter
		 *
		 * Filter status that are shown inside the list.
		 *
		 * @param array $status_list List of status.
		 *
		 * @return array
		 */
		return apply_filters( 'ywraq_request_list_status_filter', array(
			'ywraq-new',
			'ywraq-pending',
			'ywraq-expired',
			'ywraq-rejected',
			'ywraq-accepted',
		) );
	}
}

if ( ! function_exists( 'ywraq_get_order_status_tag' ) ) {
	/**
	 * Print the order status tag relative to a status.
	 *
	 * @param string $status Status of quote.
	 *
	 */
	function ywraq_get_order_status_tag( $status ) {

		switch ( $status ) {
			case 'ywraq-new':
				$status = '<span class="raq_status new">' . esc_html__(
						'new',
						'yith-woocommerce-request-a-quote'
					) . '</span>';
				break;
			case 'ywraq-pending':
				$status = '<span class="raq_status pending">' . esc_html__(
						'pending',
						'yith-woocommerce-request-a-quote'
					) . '</span>';
				break;
			case 'ywraq-expired':
				$status = '<span class="raq_status expired">' . esc_html__(
						'expired',
						'yith-woocommerce-request-a-quote'
					) . '</span>';
				break;
			case 'ywraq-rejected':
				$status = '<span class="raq_status rejected">' . esc_html__(
						'rejected',
						'yith-woocommerce-request-a-quote'
					) . '</span>';
				break;
			case 'ywraq-accepted':
				$status = '<span class="raq_status accepted">' . esc_html__(
						'accepted',
						'yith-woocommerce-request-a-quote'
					) . '</span>';
				break;

			default:
				$status = '<span class="raq_status ' . $status . '">' . wc_get_order_status_name( $status ) . '</span>';
		}

		echo wp_kses_post( $status );
	}
}

if ( ! function_exists( 'yith_ywraq_get_product_meta' ) ) {
	/**
	 * Return the product meta
	 *
	 * @param array $raq        .
	 * @param bool  $echo       .
	 * @param bool  $show_price .
	 *
	 * @return string
	 */
	function yith_ywraq_get_product_meta( $raq, $echo = true, $show_price = true ) {

		$item_data = array();

		// Variation data.
		if ( ! empty( $raq['variation_id'] ) && ( isset( $raq['variations'] ) && is_array( $raq['variations'] ) ) ) {

			foreach ( $raq['variations'] as $name => $value ) {

				if ( '' === $value ) {
					continue;
				}

				$taxonomy = wc_attribute_taxonomy_name( str_replace( 'attribute_pa_', '', urldecode( $name ) ) );

				// If this is a term slug, get the term's nice name.
				if ( taxonomy_exists( $taxonomy ) ) {
					$term = get_term_by( 'slug', $value, $taxonomy );
					if ( ! is_wp_error( $term ) && $term && $term->name ) {
						$value = $term->name;
					}
					$label = wc_attribute_label( $taxonomy );

				} else {
					if ( strpos( $name, 'attribute_' ) !== false ) {
						$custom_att = str_replace( 'attribute_', '', $name );
						if ( ! empty( $custom_att ) ) {
							$label = wc_attribute_label( $custom_att, wc_get_product( $raq['variation_id'] ) );
						} else {
							$label = apply_filters( 'woocommerce_attribute_label', wc_attribute_label( $name ), $name );
						}
					}
				}

				$item_data[] = array(
					'key'   => $label,
					'value' => $value,
				);

			}
		} elseif ( is_object( $raq ) && ( ( isset( $raq->get_data()['variation_id'] ) && (int) $raq->get_data()['variation_id'] > 0 ) || is_array( $raq->get_meta( '_ywraq_wc_ywapo' ) ) ) ) {

			$product = wc_get_product( $raq->get_data()['variation_id'] );

			foreach ( $raq->get_meta_data() as $meta ) {
				if ( empty( $meta->id ) || '' === $meta->value || ! is_scalar( $meta->value ) ) {
					continue;
				}

				$meta->key     = rawurldecode( (string) $meta->key );
				$meta->value   = rawurldecode( (string) $meta->value );
				$attribute_key = str_replace( 'attribute_', '', $meta->key );
				$display_key   = wc_attribute_label( $attribute_key, $product );
				$display_value = wp_kses_post( $meta->value );

				if ( taxonomy_exists( $attribute_key ) ) {
					$term = get_term_by( 'slug', $meta->value, $attribute_key );
					if ( ! is_wp_error( $term ) && is_object( $term ) && $term->name ) {
						$display_value = $term->name;
					}
				}

				$item_data[] = array(
					'key'   => $display_key,
					'value' => $display_value,
				);
			}
		}
		$item_data = apply_filters( 'ywraq_item_data', $item_data, $raq, $show_price );
		/**
		 * APPLY_FILTERS:ywraq_meta_data_carret
		 *
		 * Filter the char to use to move the text in a new line.
		 *
		 * @param string $char Char.
		 *
		 * @return string
		 */
		$carrets = apply_filters( 'ywraq_meta_data_carret', "\n" );

		$out = $echo ? $carrets : '';

		// Output flat or in list format.
		if ( ! empty( $item_data ) ) {
			foreach ( $item_data as $data ) {
				if ( $echo ) {
					if ( apply_filters( 'ywraq_meta_data_do_escape', true ) ) {
						$out .= '<br /><span class="meta_key">' . esc_html( $data['key'] ) . ':</span> ' . wp_kses_post( $data['value'] ) . $carrets;
					} else {
						$out .= '<br /><span class="meta_key">' . wp_kses_post( $data['key'] ) . ':</span> ' . wp_kses_post( $data['value'] ) . $carrets;
					}
				} else {
					$out .= ' - <span class="meta_key">' . wp_strip_all_tags( $data['key'] ) . ':</span> ' . wp_kses_post( $data['value'] ) . ' ';
				}
			}

			if ( $echo ) {
				echo wp_kses_post( $out );
			} else {
				return $out;
			}

			return '';
		}

	}
}

if ( ! function_exists( 'yith_ywraq_get_product_meta_from_order_item' ) ) {
	/**
	 * Get product meta from order item
	 *
	 * @param array $item_meta Array of item meta.
	 * @param bool  $echo      .
	 *
	 * @return string
	 */
	function yith_ywraq_get_product_meta_from_order_item( $item_meta, $echo = true ) {
		/**
		 * Return the product meta in a variation product
		 *
		 * @param array $raq
		 * @param bool  $echo
		 *
		 * @return string
		 * @since 1.0.0
		 */
		$item_data = array();

		// Variation data.
		if ( ! empty( $item_meta ) ) {
			$order_item_hidden = apply_filters( 'woocommerce_hidden_order_itemmeta',
				array(
					'_qty',
					'_tax_class',
					'_product_id',
					'_variation_id',
					'_line_subtotal',
					'_line_subtotal_tax',
					'_line_total',
					'_line_tax',
					'_parent_line_item_id',
					'_commission_id',
					'_woocs_order_rate',
					'_woocs_order_base_currency',
					'_woocs_order_currency_changed_mannualy',
				)
			);
			foreach ( $item_meta as $name => $val ) {

				if ( empty( $val ) || in_array( $name, $order_item_hidden, true ) || is_serialized( $val[0] ) ) {
					continue;
				}

				$taxonomy = $name;

				// If this is a term slug, get the term's nice name.
				if ( taxonomy_exists( $taxonomy ) ) {
					$term = get_term_by( 'slug', $val[0], $taxonomy );
					if ( ! is_wp_error( $term ) && $term && $term->name ) {
						$value = $term->name;
					} else {
						$value = $val[0];
					}
					$label = wc_attribute_label( $taxonomy );
				} else {
					$label = apply_filters( 'woocommerce_attribute_label', wc_attribute_label( $name ), $name );
					$value = $val[0];
				}

				if ( ! empty( $label ) && ! empty( $val[0] ) ) {
					$item_data[] = array(
						'key'   => $label,
						'value' => $value,
					);
				}
			}
		}

		/**
		 * APPLY_FILTERS:ywraq_item_data
		 *
		 * Filter the item data.
		 *
		 * @param array $item_data Item data.
		 *
		 * @return array
		 */
		$item_data = apply_filters( 'ywraq_item_data', $item_data );
		$out       = '';
		// Output flat or in list format.
		if ( ! empty( $item_data ) ) {
			foreach ( $item_data as $data ) {
				if ( $echo ) {
					echo esc_html( $data['key'] ) . ': ' . wp_kses_post( $data['value'] ) . "\n";
				} else {
					$out .= ' - ' . esc_html( $data['key'] ) . ': ' . wp_kses_post( $data['value'] ) . ' ';
				}
			}
		}

		return $out;
	}
}

if ( ! function_exists( 'yith_ywraq_add_notice' ) ) {
	/**
	 * Add and store a notice
	 *
	 * @param string $message     The text to display in the notice.
	 * @param string $notice_type The singular name of the notice type - either error, success or notice. [optional].
	 *
	 * @since 2.1
	 */
	function yith_ywraq_add_notice( $message, $notice_type = 'success' ) {

		$session = YITH_Request_Quote()->session_class;
		if ( ! $session ) {
			return;
		}

		$notices = $session->get( 'yith_ywraq_notices', array() );

		// Backward compatibility.
		if ( 'success' === $notice_type ) {
			/**
			 * APPLY_FILTERS:yith_ywraq_add_message
			 *
			 * Filter the content of the notice message.
			 *
			 * @param string $message Message.
			 *
			 * @return string
			 */
			$message = apply_filters( 'yith_ywraq_add_message', $message );
		}

		$notices[ $notice_type ][] = array(
			/**
			 * APPLY_FILTERS:yith_ywraq_add_{$notice_type}
			 *
			 * Filter the content of the $notice_type message.
			 *
			 * @param string $message Message.
			 *
			 * @return string
			 */
			'notice' => apply_filters( 'yith_ywraq_add_' . $notice_type, $message ),
		);
		$session->set( 'yith_ywraq_notices', $notices );
	}
}

if ( ! function_exists( 'yith_ywraq_notice_count' ) ) {
	/**
	 * Get the count of notices added, either for all notices (default) or for one
	 * particular notice type specified by $notice_type.
	 *
	 * @param string $notice_type The name of the notice type - either error, success or notice. [optional].
	 * @param array  $all_notices Array of notices.
	 *
	 * @return int
	 */
	function yith_ywraq_notice_count( $notice_type = '', $all_notices = array() ) {
		$notice_count = 0;

		if ( isset( $all_notices[ $notice_type ] ) ) {
			$notice_count = absint( count( $all_notices[ $notice_type ] ) );
		} elseif ( empty( $notice_type ) ) {
			$notice_count += absint( count( $all_notices ) );
		}

		return $notice_count;
	}
}

if ( ! function_exists( 'yith_ywraq_print_notices' ) ) {
	/**
	 * Prints messages and errors which are stored in the session, then clears them.
	 */
	function yith_ywraq_print_notices() {
		$all_notices = array();
		$session     = YITH_Request_Quote()->session_class;

		if ( ! $session ) {
			if ( isset( $_GET['order'] ) ) {                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                   //phpcs:ignore
				$order                    = sanitize_text_field( wp_unslash( $_GET['order'] ) );                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                    //phpcs:ignore
				$all_notices['success'][] = array( 'notice' => ywraq_get_message_after_request_quote_sending( $order ) );
			} else {
				return;
			}
		} else {
			$all_notices = $session->get( 'yith_ywraq_notices', array() );
		}
		/**
		 * APPLY_FILTERS:ywraq_notices
		 *
		 * Filter the content of the notice messages.
		 *
		 * @param array $all_notices All notices list.
		 *
		 * @return array
		 */
		$all_notices = apply_filters( 'ywraq_notices', $all_notices );
		/**
		 * APPLY_FILTERS:yith_ywraq_notice_types
		 *
		 * Filter the list of notice types.
		 *
		 * @param array $notice_types All notice types list.
		 *
		 * @return array
		 */
		$notice_types = apply_filters( 'yith_ywraq_notice_types', array( 'error', 'success', 'notice' ) );

		foreach ( $notice_types as $notice_type ) {
			if ( yith_ywraq_notice_count( $notice_type, $all_notices ) > 0 && ! empty( $all_notices ) && $all_notices[ $notice_type ] ) {

				$messages = array();

				foreach ( $all_notices[ $notice_type ] as $notice ) {
					$messages[] = $notice['notice'] ?? $notice;
				}

				wc_get_template(
					"notices/{$notice_type}.php",
					array(
						'messages' => array_filter( $messages ),
						'notices'  => array_filter( $all_notices[ $notice_type ] ),
					)
				);
			}
		}

		yith_ywraq_clear_notices();
	}
}

if ( ! function_exists( 'yith_ywraq_clear_notices' ) ) {
	/**
	 * Unset all notices
	 */
	function yith_ywraq_clear_notices() {
		$session = YITH_Request_Quote()->session_class;
		$session && $session->set( 'yith_ywraq_notices', null );
	}
}

if ( ! function_exists( 'ywraq_get_message_after_request_quote_sending' ) ) {
	/**
	 * Return the message after that the request quote sending
	 *
	 * @param WC_Order $new_order Order.
	 *
	 * @return string
	 */
	function ywraq_get_message_after_request_quote_sending( $new_order = '' ) {

		if ( get_option( 'ywraq_how_show_after_sent_the_request' ) !== 'simple_message' ) {
			return '';
		}

		$ywraq_message_after_sent_the_request = esc_html( get_option( 'ywraq_message_after_sent_the_request' ) );
		/**
		 * APPLY_FILTERS:ywraq_quote_number
		 *
		 * Filter the quote number.
		 *
		 * @param string $quote_number Quote number.
		 *
		 * @return string
		 */
		$quote_number = get_option( 'ywraq_enable_order_creation', 'yes' ) === 'yes' ? apply_filters( 'ywraq_quote_number', $new_order ) : '';
		if ( is_user_logged_in() ) {
			$quote_url = empty( $quote_number ) ? '#' : YITH_YWRAQ_Order_Request()->get_view_order_url( $new_order );
		} else {
			$quote_url = empty( $quote_number ) ? '#' : add_query_arg(
				array(
					'quote'   => $new_order,
					'preview' => 1,
				),
				YITH_Request_Quote()->get_raq_page_url()
			);
		}

		$quote_number_link = empty( $quote_number ) ? '' : sprintf( '<a href="%1$s">#%2$s</a>', $quote_url, $quote_number );
		/**
		 * APPLY_FILTERS:ywraq_quote_number_after_request_quote_sending
		 *
		 * Filter url of quote
		 *
		 * @param string   $quote_number_link Url of quote.
		 * @param WC_Order $quote             Quote object.
		 *
		 * @return string
		 */
		$quote_number_link = apply_filters( 'ywraq_quote_number_after_request_quote_sending', $quote_number_link, $new_order );
		$message           = str_replace( '%quote_number%', $quote_number_link, $ywraq_message_after_sent_the_request );

		/**
		 * APPLY_FILTERS:ywraq_simple_thank_you_message
		 *
		 * Filter the thank-you message.
		 *
		 * @param string   $message           Message of thank you page.
		 * @param WC_Order $quote             Quote object.
		 * @param string   $quote_number_link Url of quote.
		 *
		 * @return string
		 */
		return apply_filters( 'ywraq_simple_thank_you_message', $message, $new_order, $quote_number_link );
	}
}

if ( ! function_exists( 'ywraq_get_list_empty_message' ) ) {
	/**
	 * Return the empty list message
	 *
	 * @return mixed|void
	 */
	function ywraq_get_list_empty_message() {
		/**
		 * APPLY_FILTERS:ywraq_get_list_empty_message_text
		 *
		 * Filter the message when the list is empty.
		 *
		 * @param string $message Message of empty list.
		 *
		 * @return string
		 */
		$empty_list_message_text = apply_filters( 'ywraq_get_list_empty_message_text', esc_html__( 'Your list is empty, add products to the list to send a request', 'yith-woocommerce-request-a-quote' ) );
		$empty_list_message      = sprintf( '<p class="ywraq_list_empty_message">%s<p>', $empty_list_message_text );
		$raq_id                  = false;
		if ( isset( WC()->session ) ) {
			$raq_id = WC()->session->get( 'raq_new_order', false );
		}
		if ( $raq_id ) {
			$shop_url = ywraq_get_return_to_shop_after_sent_the_request_url();
			/**
			 * APPLY_FILTERS:yith_ywraq_return_to_shop_after_sent_the_request_label
			 *
			 * Filter the label of button to return to the shop page after that the quote is sent.
			 *
			 * @param string $label Label.
			 *
			 * @return string
			 */
			$label_return_to_shop = apply_filters( 'yith_ywraq_return_to_shop_after_sent_the_request_label', get_option( 'ywraq_return_to_shop_after_sent_the_request' ) );
		} else {
			$shop_url = ywraq_get_return_to_shop_url();
			/**
			 * APPLY_FILTERS:yith_ywraq_return_to_shop_label
			 *
			 * Filter the label of button to return to the shop page.
			 *
			 * @param string $label Label.
			 *
			 * @return string
			 */
			$label_return_to_shop = apply_filters( 'yith_ywraq_return_to_shop_label', get_option( 'ywraq_return_to_shop_label' ) );
		}

		$empty_list_message .= sprintf(
			'<p class="return-to-shop"><a class="button wc-backward" href="%s">%s</a></p>',
			$shop_url,
			$label_return_to_shop
		);

		/**
		 * APPLY_FILTERS:ywraq_get_list_empty_message
		 *
		 * Filter the message when the list is empty.
		 *
		 * @param string $empty_list_message Empty list message.
		 *
		 * @return string
		 */
		return apply_filters( 'ywraq_get_list_empty_message', $empty_list_message );
	}
}

/* Hooks */
if ( ! function_exists( 'yith_ywraq_show_button_in_single_page' ) ) {
	/**
	 * Check if the button can be showed in single product page.
	 *
	 * @return bool
	 */
	function yith_ywraq_show_button_in_single_page() {
		global $product, $sitepress;
		$general_show_btn = get_option( 'ywraq_show_btn_single_page' );

		if ( 'yes' !== $general_show_btn || ! $product instanceof WC_Product ) {
			return false;
		}

		$product_id = $product->get_id();

		// WPML Integration.
		$product_id = apply_filters( 'wpml_object_id', $product_id, 'product', true );

		if ( ! $product->is_type( 'variable' ) && 'only' === get_option( 'ywraq_button_out_of_stock', 'hide' ) && $product->is_in_stock() ) {
			return false;
		}

		return ! ywraq_is_in_exclusion( $product_id );
	}
}

/* Common Functions */
if ( ! function_exists( 'ywraq_get_date_format' ) ) {
	/**
	 * Return the date format based on locale.
	 *
	 * @param string $language Language.
	 *
	 * @return string
	 */
	function ywraq_get_date_format( $language ) {
		$date_format = wc_date_format();

		if ( isset( $language ) ) {
			global $sitepress;
			if ( $sitepress ) {
				$lang = $sitepress->get_locale( $language );
				setlocale( LC_ALL, $lang . '.UTF-8' );

				/**
				 * APPLY_FILTERS:ywraq_date_local_formats
				 *
				 * Filter the local date format
				 *
				 * @param array $local_formats Local format.
				 *
				 * @return array
				 */
				$local_formats = apply_filters( 'ywraq_date_local_formats', array() );
				if ( ! empty( $local_formats ) && isset( $local_formats[ $lang ] ) ) {
					$date_format = $local_formats[ $lang ];
				}
			}
		}

		return $date_format;
	}
}

if ( ! function_exists( 'ywraq_get_current_language' ) ) {
	/**
	 * Return the current language when a multilingual plugin is installed.
	 *
	 * @return string $current_language
	 * @since  2.0
	 */
	function ywraq_get_current_language() {
		// WPML Compatibility.
		global $sitepress, $polylang;
		$current_language = '';
		if ( function_exists( 'icl_get_languages' ) && class_exists( 'YITH_YWRAQ_Multilingual_Email' ) ) {
			$current_language = $sitepress->get_current_language();
		} elseif ( $polylang && isset( $polylang->curlang->slug ) ) {
			$current_language = $polylang->curlang->slug;
		}

		return $current_language;
	}
}

if ( ! function_exists( 'ywraq_check_recaptcha_options' ) ) {

	/**
	 * Check if recaptcha is enabled and it can be added to the form.
	 *
	 * @return mixed|void
	 */
	function ywraq_check_recaptcha_options() {
		$recaptcha = get_option( 'ywraq_reCAPTCHA' );
		$sitekey   = get_option( 'ywraq_reCAPTCHA_sitekey' );
		$secretkey = get_option( 'ywraq_reCAPTCHA_secretkey' );

		$is_captcha = 'yes' === $recaptcha && ! empty( $sitekey ) && $secretkey;

		/**
		 * APPLY_FILTERS:ywraq_check_recaptcha
		 *
		 * Filter if is possible to use the recaptcha.
		 *
		 * @param bool $is_captcha If true the recaptcha can be used.
		 *
		 * @return bool
		 */
		return apply_filters( 'ywraq_check_recaptcha', $is_captcha );
	}
}

if ( ! function_exists( 'get_array_column' ) ) {
	/**
	 * Get column of last names from a recordset
	 *
	 * @param array $array        .
	 * @param array $array_column .
	 *
	 * @return array
	 * @since  2.0.0
	 */
	function get_array_column( $array, $array_column ) {
		if ( function_exists( 'array_column' ) ) {
			return array_column( $array, $array_column );
		}

		$return = array();
		foreach ( $array as $row ) {
			if ( isset( $row[ $array_column ] ) ) {
				$return[] = $row[ $array_column ];
			}
		}

		return $return;
	}
}

if ( ! function_exists( 'wc_get_template_html' ) && function_exists( 'wc_get_template' ) ) {
	/**
	 * Add the function wc_get_template_html if woocommerce version is < 2.5
	 *
	 * @param string $template_name Template name.
	 * @param array  $args          Array of arguments.
	 * @param string $template_path Template path.
	 * @param string $default_path  Default path.
	 *
	 * @return string
	 */
	function wc_get_template_html( $template_name, $args = array(), $template_path = '', $default_path = '' ) {
		ob_start();
		wc_get_template( $template_name, $args, $template_path, $default_path );

		return ob_get_clean();
	}
}

if ( ! function_exists( 'ywraq_get_attachment_id_by_url' ) ) {
	/**
	 * Return an ID of an attachment by searching the database with the file URL.
	 *
	 * First checks to see if the $url is pointing to a file that exists in
	 * the wp-content directory. If so, then we search the database for a
	 * partial match consisting of the remaining path AFTER the wp-content
	 * directory. Finally, if a match is found the attachment ID will be
	 * returned.
	 *
	 * @param string $url The URL of the image (ex: http://mysite.com/wp-content/uploads/2013/05/test-image.jpg).
	 *
	 * @return mixed $attachment Returns an attachment ID, or null if no attachment is found
	 */
	function ywraq_get_attachment_id_by_url( $url ) {
		// Split the $url into two parts with the wp-content directory as the separator.
		$parsed_url = explode( wp_parse_url( WP_CONTENT_URL, PHP_URL_PATH ), $url );
		$host_url = wp_parse_url( $url, PHP_URL_HOST );
		// Get the host of the current site and the host of the $url, ignoring www.
		$this_host = str_ireplace( 'www.', '', wp_parse_url( home_url(), PHP_URL_HOST ) );
		$file_host = str_ireplace( 'www.', '', is_null($host_url) ? '' :$host_url );

		// Return nothing if there aren't any $url parts or if the current host and $url host do not match.
		if ( ! isset( $parsed_url[1] ) || empty( $parsed_url[1] ) || ( $this_host !== $file_host ) ) {
			return null;
		}

		// Now we're going to quickly search the DB for any attachment GUID with a partial path match.
		// Example: /uploads/2013/05/test-image.jpg.
		global $wpdb;

		$attachment = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT ID FROM {$wpdb->prefix}posts WHERE guid RLIKE %s;",
				$parsed_url[1] ) ); //phpcs:ignore

		// Returns null if no attachment is found.
		return $attachment[0] ?? null;
	}
}

if ( ! function_exists( 'yith_ywraq_get_email_template' ) ) {
	/**
	 * Return email template
	 *
	 * @param bool $html Html format?.
	 *
	 * @return string
	 */
	function yith_ywraq_get_email_template( $html ) {
		if ( ! isset( WC()->session ) ) {
			return '';
		}

		$raq_data['order_id']       = WC()->session->raq_new_order;
		$raq_data['sent_from_cart'] = WC()->session->get( 'sent_from_cart' );
		$raq_data['raq_content']    = '';

		if ( $raq_data['sent_from_cart'] && ! WC()->cart->is_empty() ) {
			$raq_data['raq_content'] = WC()->cart->get_cart_contents();
		}
		if ( empty( $raq_data['raq_content'] ) && $raq_data['order_id'] ) {
			$order                   = wc_get_order( $raq_data['order_id'] );
			$raq_data['raq_content'] = $order->get_items();
		}

		if ( empty( $raq_data['raq_content'] ) ) {
			$raq_data['raq_content'] = YITH_Request_Quote()->get_raq_return();
		}

		ob_start();
		if ( $html ) {
			wc_get_template(
				'emails/request-quote-table.php',
				array(
					'raq_data' => $raq_data,
				),
				'',
				YITH_YWRAQ_TEMPLATE_PATH . '/'
			);
		} else {
			wc_get_template(
				'emails/plain/request-quote-table.php',
				array(
					'raq_data' => $raq_data,
				),
				'',
				YITH_YWRAQ_TEMPLATE_PATH . '/'
			);
		}

		return ob_get_clean();
	}
}

if ( ! function_exists( 'ywraq_formatted_line_total' ) ) {

	/**
	 * Gets line subtotal - formatted for display.
	 *
	 * @param WC_Order $order       Order.
	 * @param array    $item        Item.
	 * @param string   $tax_display Tax display.
	 *
	 * @return string
	 */
	function ywraq_formatted_line_total( $order, $item, $tax_display = '' ) {

		$tax_display = empty( $tax_display ) ? get_option( 'woocommerce_tax_display_cart' ) : $tax_display;

		if ( ! isset( $item['line_total'] ) || ! isset( $item['line_subtotal'] ) ) {
			return '';
		}

		$show_old_price = get_option( 'ywraq_show_old_price', false );

		$show_discount = apply_filters(
			'ywraq_show_discount_in_line_total',
			'yes' === $show_old_price && $item['line_subtotal'] > $item['line_total'],
			$item
		);

		$currency = $order->get_currency();

		if ( 'excl' === $tax_display ) {
			$ex_tax_label = $order->get_prices_include_tax() ? 1 : 0;

			$line_total    = wc_price(
				$order->get_line_total( $item ),
				array(
					'ex_tax_label' => $ex_tax_label,
					'currency'     => $currency,
				)
			);
			$line_subtotal = wc_price(
				$order->get_line_subtotal( $item ),
				array(
					'ex_tax_label' => $ex_tax_label,
					'currency'     => $currency,
				)
			);

		} else {
			$line_total    = wc_price( $order->get_line_total( $item, true ), array( 'currency' => $currency ) );
			$line_subtotal = wc_price( $order->get_line_subtotal( $item, true ), array( 'currency' => $currency ) );
		}

		if ( $show_discount ) {
			$show_discount = '<small><del>' . $line_subtotal . '</del></small>';
			$show_discount = apply_filters( 'ywraq_formatted_discount_line_total', $show_discount );
			$line_total    = $show_discount . ' ' . $line_total;
		}

		return apply_filters( 'ywraq_formatted_line_total', $line_total, $item, $order );
	}
}

if ( ! function_exists( 'ywraq_allow_raq_out_of_stock' ) ) {
	/**
	 * Check if the request of quote is allowed also to out of stock products.
	 *
	 * @return bool
	 */
	function ywraq_allow_raq_out_of_stock() {
		return get_option( 'ywraq_button_out_of_stock', 'hide' ) !== 'hide';
	}
}

if ( ! function_exists( 'ywraq_show_btn_only_out_of_stock' ) ) {
	/**
	 * Check if the request a quote button must be showed only for out of stock products.
	 *
	 * @return bool
	 */
	function ywraq_show_btn_only_out_of_stock() {
		$option = get_option( 'ywraq_show_btn_only_out_of_stock' );

		return ( true === $option || 1 === $option || '1' === $option || 'yes' === $option );
	}
}

if ( ! function_exists( 'ywraq_get_connect_fields' ) ) {
	/**
	 * Get Order connect meta fields.
	 *
	 * A list of
	 *
	 * @return array
	 * @since  2.0.0
	 */
	function ywraq_get_connect_fields() {

		if ( ! isset( WC()->countries ) ) {
			include_once WC_ABSPATH . 'includes/class-wc-countries.php';
			WC()->countries = new WC_Countries();
		}

		$fields_billing = WC()->countries->get_address_fields( '', 'billing_' );

		$connect_fields = array( '' => '-' );
		if ( $fields_billing ) {
			foreach ( $fields_billing as $key => $value ) {
				$connect_fields[ $key ] = esc_html__( 'Billing', 'yith-woocommerce-request-a-quote' ) . ' ' . ( isset( $value['label'] ) ? $value['label'] : str_replace( '_', ' ', $key ) );
			}
		}

		$fields_shipping = WC()->countries->get_address_fields( '', 'shipping_' );
		if ( $fields_shipping ) {
			foreach ( $fields_shipping as $key => $value ) {
				$connect_fields[ $key ] = esc_html__( 'Shipping', 'yith-woocommerce-request-a-quote' ) . ' ' . ( isset( $value['label'] ) ? $value['label'] : str_replace( '_', ' ', $key ) );
			}
		}

		$connect_fields['order_comments'] = esc_html__( 'Order Comments', 'yith-woocommerce-request-a-quote' );

		return apply_filters( 'ywraq_form_connect_fields', $connect_fields );
	}
}

if ( ! function_exists( 'ywraq_get_accepted_quote_page' ) ) {
	/**
	 * Return the url of the accepted page for a quote
	 *
	 * @param WC_Order $order           Order.
	 * @param bool     $disable_pay_now Disable pay now boolean.
	 *
	 * @return string
	 * @since  2.0.8
	 */
	function ywraq_get_accepted_quote_page( $order, $disable_pay_now = false ) {
		$args = array(
			'request_quote' => $order->get_id(),
			'status'        => 'accepted',
			'raq_nonce'     => ywraq_get_token(
				'accept-request-quote',
				$order->get_id(),
				$order->get_meta( 'ywraq_customer_email' )
			),
			'lang'          => $order->get_meta( 'wpml_language' ),
		);

		$pay_now = ywraq_get_payment_option( 'ywraq_pay_quote_now', $order );

		$base_url = ( ! $disable_pay_now && ywraq_is_true( $pay_now ) ) ? $order->get_checkout_payment_url( false ) : YITH_Request_Quote()->get_raq_page_url();

		// APPLY_FILTER: ywraq_accepted_quote_page : Filtering the page url to accept a quote: order, args and redirect page url are passed as arguments.
		return apply_filters(
			'ywraq_accepted_quote_page',
			add_query_arg( $args, $base_url ),
			$order,
			$args,
			$base_url
		);
	}
}

if ( ! function_exists( 'ywraq_get_rejected_quote_page' ) ) {
	/**
	 * Return the url of the rejected page for a quote
	 *
	 * @param WC_Order $order Order.
	 *
	 * @return string
	 * @since  2.0.8
	 */
	function ywraq_get_rejected_quote_page( $order ) {
		$args = array(
			'request_quote' => $order->get_id(),
			'status'        => 'rejected',
			'raq_nonce'     => ywraq_get_token(
				'reject-request-quote',
				$order->get_id(),
				$order->get_meta( 'ywraq_customer_email' )
			),
			'lang'          => $order->get_meta( 'wpml_language' ),
		);

		// APPLY_FILTER: ywraq_rejected_quote_page : Filtering the page url to reject a quote: order, args and request a quote page url are passed as arguments.
		return apply_filters(
			'ywraq_rejected_quote_page',
			add_query_arg( $args, YITH_Request_Quote()->get_raq_page_url() ),
			$order,
			$args,
			YITH_Request_Quote()->get_raq_page_url()
		);
	}
}

/* YITH Contact Form */
if ( ! function_exists( 'ywraq_yit_contact_form_installed' ) ) {
	/**
	 * Check if YIT Contact Form is installed
	 *
	 * @return bool
	 */
	function ywraq_yit_contact_form_installed() {
		return apply_filters( 'ywraq_yit_contact_form_installation', defined( 'YIT_CONTACT_FORM' ) );
	}
}

if ( ! function_exists( 'ywraq_get_license' ) ) {
	/**
	 * Check if there is an active license
	 *
	 * @return bool|string
	 */
	function ywraq_get_license() {

		if ( ! function_exists( 'YITH_Plugin_Licence' ) ) {
			// Try to load YITH_Plugin_Licence class.
			yith_plugin_fw_load_update_and_licence_files();
		}

		if ( function_exists( 'YITH_Plugin_Licence' ) ) {
			$license = YITH_Plugin_Licence();
			if ( is_callable( array( $license, 'get_licence' ) ) ) {
				$licenses = $license->get_licence();

				return isset( $licenses[ YITH_YWRAQ_SLUG ] ) ?? $licenses[ YITH_YWRAQ_SLUG ];
			}
		}

		return false;
	}
}

if ( ! function_exists( 'ywraq_get_license_activation_url' ) ) {
	/**
	 * Get license activation url
	 *
	 * @return string
	 */
	function ywraq_get_license_activation_url() {

		if ( ! function_exists( 'YITH_Plugin_Licence' ) && function_exists( 'yith_plugin_fw_load_update_and_licence_files' ) ) {
			// Try to load YITH_Plugin_Licence class.
			yith_plugin_fw_load_update_and_licence_files();
		}

		if ( function_exists( 'YIT_Plugin_Licence' ) ) {
			$license = YIT_Plugin_Licence();

			if ( is_callable( array( $license, 'get_license_activation_url' ) ) ) {
				return $license->get_license_activation_url( YITH_YWRAQ_SLUG );

			}
		}

		return '';
	}
}

/* Contact Form 7 */
if ( ! function_exists( 'ywraq_cf7_form_installed' ) ) {
	/**
	 * Check if Contact Form 7 is installed
	 *
	 * @return bool
	 */
	function ywraq_cf7_form_installed() {
		return apply_filters( 'ywraq_cf7_form_installation', class_exists( 'WPCF7_ContactForm' ) );
	}
}

/* GRAVITY FORMS */
if ( ! function_exists( 'ywraq_gravity_form_installed' ) ) {
	/**
	 * Check if Gravity Form is installed.
	 *
	 * @return bool
	 */
	function ywraq_gravity_form_installed() {
		return apply_filters( 'ywraq_gravity_form_installation', class_exists( 'GFForms' ) );
	}
}

/* WPForms Forms */
if ( ! function_exists( 'ywraq_wpforms_installed' ) ) {
	/**
	 * Check if WPForms is installed.
	 *
	 * @return bool
	 */
	function ywraq_wpforms_installed() {
		$is_installed = ( defined( 'WPFORMS_VERSION' ) && version_compare( WPFORMS_VERSION, '1.6.4.1', '>=' ) );

		return apply_filters( 'ywraq_wpforms_installation', $is_installed );
	}
}

/* Ninja Forms */
if ( ! function_exists( 'ywraq_ninja_forms_installed' ) ) {
	/**
	 * Check if Ninja Forms is installed.
	 *
	 * @return bool
	 */
	function ywraq_ninja_forms_installed() {
		$is_installed = ( class_exists( 'Ninja_Forms' ) && defined( 'Ninja_Forms::VERSION' ) && version_compare(
				Ninja_Forms::VERSION,
				'3.4.33',
				'>='
			) );

		return apply_filters( 'ywraq_ninjas_form_installation', $is_installed );
	}
}

/* YITH WooCommerce Catalog Mode */
if ( ! function_exists( 'catalog_mode_plugin_enabled' ) ) {
	/**
	 * Check if is installed YITH WooCommerce Catalog Mode
	 *
	 * @return bool
	 */
	function catalog_mode_plugin_enabled() {
		return defined( 'YWCTM_PREMIUM' );
	}
}

/* POLYLANG */
if ( defined( 'POLYLANG_VERSION' ) ) {

	if ( ! function_exists( 'ywraq_pll_getLanguageEntity' ) ) {
		/**
		 * Polilang support function
		 *
		 * @param string $slug Slug.
		 *
		 * @return bool
		 */
		function ywraq_pll_getLanguageEntity( $slug ) { //phpcs:ignore
			global $polylang;

			$langs = $polylang->model->get_languages_list();

			foreach ( $langs as $lang ) {
				if ( $lang->slug === $slug ) {
					return $lang;
				}
			}

			return false;
		}
	}

	if ( ! function_exists( 'ywraq_pll_refresh_email_lang' ) ) {
		/**
		 * Polilang email
		 *
		 * @param int $order_id Order id.
		 *
		 * @return mixed
		 */
		function ywraq_pll_refresh_email_lang( $order_id ) {

			global $polylang;
			$order  = wc_get_order( $order_id );
			$lang   = $order->get_meta( 'wpml_language' );
			$entity = ywraq_pll_getLanguageEntity( $lang );

			if ( $entity ) {
				$polylang->curlang = $polylang->model->get_language( $entity->locale );

				$GLOBALS['text_direction'] = $entity->is_rtl ? 'rtl' : 'ltr'; //phpcs:ignore
				if ( class_exists( 'WP_Locale' ) ) {
					$GLOBALS['wp_locale'] = new WP_Locale(); //phpcs:ignore
				}

				return $entity->locale;
			}

		}

		add_action( 'send_quote_mail_notification', 'ywraq_pll_refresh_email_lang', 10 );
	}
}

/* FLATSOME */
if ( ! function_exists( 'show_wraq_product_lightbox' ) ) {
	/**
	 * Show request a quote button on Flatsome Lightbox.
	 */
	function show_wraq_product_lightbox() {

		if ( ! function_exists( 'YITH_YWRAQ_Frontend' ) ) {
			return;
		}

		global $product;

		if ( ! $product ) {
			global $post;
			if ( ! $post || ! is_object( $post ) || ! is_singular() ) {
				return;
			}
			$product = wc_get_product( $post->ID );
		}
		$show_button_near_add_to_cart = get_option( 'ywraq_show_button_near_add_to_cart', 'no' );
		if ( yith_plugin_fw_is_true( $show_button_near_add_to_cart ) && $product->is_in_stock() ) {
			add_action(
				'woocommerce_after_add_to_cart_button',
				array(
					YITH_YWRAQ_Frontend(),
					'add_button_single_page',
				)
			);
		} else {
			add_action(
				'woocommerce_single_product_lightbox_summary',
				array(
					YITH_YWRAQ_Frontend(),
					'add_button_single_page',
				),
				35
			);
		}
	}

	add_action( 'wc_quick_view_before_single_product', 'show_wraq_product_lightbox' );
}

if ( ! function_exists( 'ywraq_is_true' ) ) {
	/**
	 * Check if a variable is true
	 *
	 * @param mixed $value Value tu check.
	 *
	 * @return bool
	 */
	function ywraq_is_true( $value ) {
		return true === $value || 1 === $value || '1' === $value || 'yes' === $value;
	}
}

if ( ! function_exists( 'ywraq_get_available_gateways' ) ) {
	/**
	 * Return available gateways
	 *
	 * @return array
	 */
	function ywraq_get_available_gateways() {
		$payment  = WC()->payment_gateways()->payment_gateways();
		$gateways = array();
		foreach ( $payment as $gateway ) {
			if ( 'yes' === $gateway->enabled && 'YITH Request a Quote' !== $gateway->title ) {
				$gateways[ $gateway->id ] = $gateway->title;
			}
		}

		return $gateways;
	}
}

if ( ! function_exists( 'ywraq_get_cookie_name' ) ) {
	/**
	 * Return the cookie name
	 *
	 * @param string $name Cookie name.
	 *
	 * @return string
	 */
	function ywraq_get_cookie_name( $name = 'session' ) {

		$cookie_names = array(
			'session' => 'session',
			'items'   => 'items_in_raq',
			'hash'    => 'hash',
		);

		$current_name  = isset( $cookie_names[ $name ] ) ? $cookie_names[ $name ] : 'session';
		$cookie_prefix = apply_filters( 'ywraq_cookie_prefix', 'yith_ywraq_' );

		return $cookie_prefix . '' . $current_name;
	}
}

if ( ! function_exists( 'ywraq_get_ajax_default_loader' ) ) {
	/**
	 * Return the default loader.
	 *
	 * @return mixed|void
	 */
	function ywraq_get_ajax_default_loader() {

		$ajax_loader_default = YITH_YWRAQ_ASSETS_URL . '/images/ajax-loader.gif';
		if ( defined( 'YITH_PROTEO_VERSION' ) ) {
			$ajax_loader_default = YITH_YWRAQ_ASSETS_URL . '/images/proteo-loader.gif';
		}

		return apply_filters( 'ywraq_ajax_loader', $ajax_loader_default );
	}
}

if ( ! function_exists( 'ywraq_show_element_on_list' ) ) {
	/**
	 * Return the default loader.
	 *
	 * @param string $key String.
	 *
	 * @return bool
	 */
	function ywraq_show_element_on_list( $key ) {
		$product_table_show = get_option( 'ywraq_product_table_show', array() );
		return in_array( $key, $product_table_show, true );
	}
}

if ( ! function_exists( 'ywraq_export_get_products' ) ) {
	/**
	 * Return products inside the quote as string.
	 *
	 * @param _WC_Order $quote Quote.
	 *
	 * @return string
	 */
	function ywraq_export_get_products( $quote ) {

		if ( ! $quote ) {
			return '';
		}

		$items = $quote->get_items();
		if ( ! $items ) {
			return '';
		}
		$product_names = array();
		foreach ( $items as $item ) {
			$product_name     = $item->get_name();
			$product_quantity = $item->get_quantity();
			$product_names[]  = $product_quantity . ' x ' . $product_name;
		}
		$product_names = array_unique( $product_names );

		return apply_filters(
			'ywraq_export_quote_purchased_products_names',
			implode( ',', $product_names ),
			$product_names,
			$items
		);

	}
}

if ( ! function_exists( 'ywraq_is_elementor_editor' ) ) {
	/**
	 * Check if is an elementor editor
	 *
	 * @return bool
	 */
	function ywraq_is_elementor_editor() {
		if ( did_action( 'admin_action_elementor' ) ) {
			return Plugin::$instance->editor->is_edit_mode();
		}

		return is_admin() && isset( $_REQUEST['action'] ) && in_array(
				sanitize_text_field( wp_unslash( $_REQUEST['action'] ) ),
				array(
					'elementor',
					'elementor_ajax',
				) ); //phpcs:ignore
	}
}

if ( ! function_exists( 'ywraq_is_admin' ) ) {
	/**
	 * Check if current request is admin
	 *
	 * @return boolean
	 * @since 3.0.0
	 */
	function ywraq_is_admin() {
		$is_frontend_call   = isset( $_REQUEST['context'] ) && 'frontend' === $_REQUEST['context']; //phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$is_frontend_action = isset( $_REQUEST['action'] ) && in_array( //phpcs:ignore WordPress.Security.NonceVerification.Recommended
				$_REQUEST['action'], //phpcs:ignore WordPress.Security.NonceVerification.Recommended
				apply_filters(
					'yith_ywraq_frontend_action_list',
					array(
						'flatsome_quickview',
						'ux_quickview',
						'product_popup_content',
						'jeet_shop_loadmore',
						'Yith_wc_qof_Ajax_Front_change_variation',
						'furnicom_quickviewproduct',
						'wcpt_get_product_form_modal',
						'piko_quickview',
						'wpb_wl_quickview',
						'yith_wceop_get_quick_view_modal_ajax',
						'filtersFrontend',
						'prdctfltr_respond_550',
						'jet_smart_filters',
						'woostify_product_filter',
					)
				),
				true
			);

		$is_admin = is_admin() && ! ( defined( 'DOING_AJAX' ) && DOING_AJAX && ( $is_frontend_call || $is_frontend_action ) );

		if ( ! $is_admin && 'yes' === get_option(
				'ywraq_automate_send_quote',
				'no'
			) && '0' === get_option( 'ywraq_cron_time' ) ) {
			$is_admin = ( isset( $_REQUEST['context'] ) && 'frontend' === $_REQUEST['context'] ) && ( isset( $_REQUEST['wc-ajax'] ) && 'ywraq_submit_default_form' === $_REQUEST['wc-ajax'] ); //phpcs:ignore WordPress.Security.NonceVerification.Recommended
		}

		return apply_filters( 'ywraq_is_admin', $is_admin );
	}
}

if ( ! function_exists( 'ywraq_parse_atts' ) ) {
	/**
	 * Change no/yes to false/true
	 *
	 * @param array $atts Attributes.
	 *
	 * @return array
	 */
	function ywraq_parse_atts( $atts ) {
		if ( $atts ) {
			foreach ( $atts as $key => $att ) {
				if ( in_array( $att, array( 'yes', 'no', 'true', 'false' ) ) ) { //phpcs:ignore
					$atts[ $key ] = wc_string_to_bool( $att );
				}
			}
		}

		return $atts;
	}
}

if ( ! function_exists( 'ywraq_get_payment_option' ) ) {
	/**
	 * Get the payment option of a quote
	 *
	 * @param string   $option  Option key.
	 * @param WC_Order $quote   Quote.
	 * @param string   $default Default value.
	 *
	 * @return string
	 */
	function ywraq_get_payment_option( $option, $quote, $default = '' ) {

		$override_payment_options = $quote->get_meta( '_ywraq_override_quote_payment_options' );

		if ( ywraq_is_true( $override_payment_options ) ) {
			$option_value = $quote->get_meta( '_' . $option );
		} else {
			$option_value = get_option( $option, $default );
		}

		return $option_value;
	}
}

if ( ! function_exists( 'ywraq_get_actions_menu' ) ) {
	/**
	 * Return the action menu on My Account page
	 *
	 * @param WC_Order $quote Quote.
	 *
	 * @return array
	 * @since 4.0
	 */
	function ywraq_get_actions_menu( $quote ) {
		$action_menu = array();

		// Pay now
		if ( YITH_Request_Quote()->enabled_checkout() && in_array(
				$quote->get_status(),
				apply_filters(
					'ywraq_valid_order_statuses_for_payment',
					array(
						'pending',
						'ywraq-accepted',
					),
					$quote
				),
				true
			) ) {
			$action_menu['pay_now'] = array(
				'label' => __( 'Pay Now', 'yith-woocommerce-request-a-quote' ),
				'url'   => ywraq_get_accepted_quote_page( $quote ),
			);
		}

		// Order again
		if ( 'yes' === get_option( 'ywraq_enable_order_again', 'no' ) && $quote->has_status(
				apply_filters(
					'ywraq_valid_order_statuses_for_order_again',
					array(
						'completed',
						'processing',
					)
				)
			) ) {
			$reorder_url  = wp_nonce_url(
				add_query_arg( 'order_again', $quote->get_id(), wc_get_cart_url() ),
				'woocommerce-order_again'
			);
			$button_label = apply_filters(
				'ywraq_order_again_button_label',
				get_option(
					'ywraq_order_again_button_label',
					_x(
						'Order again',
						'default label to order the same products on My Account page',
						'yith-woocommerce-request-a-quote'
					)
				)
			);

			$action_menu['order_again'] = array(
				'label' => $button_label,
				'url'   => $reorder_url,
			);
		}

		// Ask new quote.
		if ( 'yes' === get_option( 'ywraq_enable_order_again', 'no' ) ) {
			/**
			 * APPLY_FILTERS: ywraq_valid_order_statuses_for_order_again
			 *
			 * Set the valid order status for which to show the Request Quote Again button.
			 *
			 * @param array $quote_status List of status.
			 *
			 * @return array
			 */
			$quote_status = apply_filters(
				'ywraq_valid_order_statuses_for_quote_again',
				array(
					'pending',
					'ywraq-new',
					'ywraq-pending',
					'ywraq-accepted',
				)
			);
			if ( ! $quote->has_status( $quote_status ) ) {
				/**
				 * APPLY_FILTERS: ywraq_quote_again_button_label
				 *
				 * Change the label of the Request Quote Again button.
				 *
				 * @param string $quote_status List of status.
				 *
				 * @return string
				 */
				$button_label    = apply_filters( 'ywraq_quote_again_button_label', get_option( 'ywraq_quote_again_button_label', _x( 'Ask again a quote', 'default label to ask the same quote on My Account page', 'yith-woocommerce-request-a-quote' ) ) );
				$quote_again_url = wp_nonce_url( add_query_arg( 'raq_again', $quote->get_id(), YITH_Request_Quote_Premium()->get_raq_url( '' ) ), 'ywraq-order_again' );

				$action_menu['ask_new_quote'] = array(
					'label' => $button_label,
					'url'   => $quote_again_url,
				);
			}
		}

		return apply_filters( 'ywraq_actions_menu', $action_menu, $quote );
	}
}

if ( ! function_exists( 'ywraq_check_valid_admin_page' ) ) {
	/**
	 * Return if the current pagenow is valid for a post_type, useful if you want add metabox, scripts inside the editor of a particular post type.
	 *
	 * @param string $post_type_name Post type.
	 *
	 * @return bool
	 * @since 3.0.0
	 */
	function ywraq_check_valid_admin_page( $post_type_name ) {
		global $pagenow;
		$screen    = function_exists( 'get_current_screen' ) ? get_current_screen() : false;
		$screen_id = $screen ? $screen->id : '';

		$post = $_REQUEST['post'] ?? ( $_REQUEST['post_ID'] ?? 0 ); // phpcs:ignore
		$post = get_post( $post );

		return 'edit-' . $post_type_name === $screen_id || ( $post && $post->post_type === $post_type_name ) || ( 'post-new.php' === $pagenow && isset( $_REQUEST['post_type'] ) && $post_type_name === $_REQUEST['post_type'] ); //phpcs:ignore WordPress.Security.NonceVerification.Recommended
	}
}

if ( ! function_exists( 'ywraq_get_default_pdf_content' ) ) {
	/**
	 * Return the content of the default pdf template.
	 */
	function ywraq_get_default_pdf_content() {
		$content  = '';
		$filename = YITH_YWRAQ_VIEW_PATH . '/pdf-templates/content.txt';
		if ( file_exists( $filename ) ) {
			$content = file_get_contents( $filename );
		}

		return $content;
	}
}

if ( ! function_exists( 'ywraq_show_taxes_on_quote_list' ) ) {
	/**
	 * Return if is necessary show the taxes on quote request
	 *
	 * @return mixed|void
	 */
	function ywraq_show_taxes_on_quote_list() {
		$show_taxes = in_array( 'tax', get_option( 'ywraq_product_table_show', array( 'images', 'line_total', 'quantity' ) ), true );

		return apply_filters( 'ywraq_show_taxes_quote_list', $show_taxes );
	}
}

if ( ! function_exists( 'ywraq_get_return_to_shop_after_sent_the_request_url' ) ) {
	/**
	 * Return the return to shop url after the quote is sent
	 *
	 * @return string
	 */
	function ywraq_get_return_to_shop_after_sent_the_request_url() {
		$choice = get_option( 'ywraq_return_to_shop_after_sent_the_request_url_choice' );
		$url    = get_permalink( wc_get_page_id( 'shop' ) );
		if ( 'custom' === $choice ) {
			$url = get_option( 'ywraq_return_to_shop_after_sent_the_request_url', $url );
		}

		return apply_filters( 'yith_ywraq_return_to_shop_after_sent_the_request_url', $url );
	}
}

if ( ! function_exists( 'ywraq_get_return_to_shop_url' ) ) {
	/**
	 * Return the return to shop url for the button in quote page
	 *
	 * @return string
	 */
	function ywraq_get_return_to_shop_url() {
		$choice = get_option( 'ywraq_return_to_shop_url_choice' );
		$url    = get_permalink( wc_get_page_id( 'shop' ) );
		if ( 'custom' === $choice ) {
			$url = get_option( 'ywraq_return_to_shop_url', $url );
		}

		return apply_filters( 'ywraq_return_to_shop_url', $url );
	}
}

if ( ! function_exists( 'ywraq_is_gutenberg_active' ) ) {
	/**
	 * Check if Gutenberg is active
	 * Must be used not earlier than plugins_loaded action fired.
	 */
	function ywraq_is_gutenberg_active() {
		$block_editor = false;

		if ( version_compare( $GLOBALS['wp_version'], '5.6', '>' ) ) {
			// Block editor.
			$block_editor = true;
		}

		return $block_editor;
	}
}

/* Deprecated */

if ( ! function_exists( 'yith_ywraq_locate_template' ) ) {
	/**
	 * Locate the templates and return the path of the file found
	 *
	 * @param string $path .
	 *
	 * @return string
	 * @since 1.0.0
	 * @deprecated
	 */
	function yith_ywraq_locate_template( $path ) {

		if ( function_exists( 'WC' ) ) {
			$woocommerce_base = WC()->template_path();
		} elseif ( defined( 'WC_TEMPLATE_PATH' ) ) {
			$woocommerce_base = WC_TEMPLATE_PATH;
		} else {
			$woocommerce_base = WC()->plugin_path() . '/templates/';
		}

		$template_woocommerce_path = $woocommerce_base . $path;
		$template_path             = '/' . $path;
		$plugin_path               = YITH_YWRAQ_DIR . 'templates/' . $path;

		$located = locate_template(
			array(
				$template_woocommerce_path, // Search in <theme>/woocommerce/.
				$template_path,             // Search in <theme>/.
				$plugin_path,                // Search in <plugin>/templates/.
			)
		);

		if ( ! $located && file_exists( $plugin_path ) ) {
			return apply_filters( 'yith_ywraq_locate_template', $plugin_path, $path );
		}

		return apply_filters( 'yith_ywraq_locate_template', $located, $path );
	}
}

if ( ! function_exists( 'ywraq_get_quote_line_total' ) ) {

	/**
	 * Deprecated function
	 *
	 * @param string $key .
	 * @param array  $raq .
	 *
	 * @return int|mixed|string|void
	 * @deprecated
	 */
	function ywraq_get_quote_line_total( $key, $raq ) {
		$total = 0;

		if ( ! isset( $raq[ $key ] ) ) {
			return $total;
		}

		$raq_item = $raq[ $key ];
		$_product = wc_get_product( ( isset( $raq_item['variation_id'] ) && '' != $raq_item['variation_id'] ) ? $raq_item['variation_id'] : $raq_item['product_id'] ); //phpcs:ignore

		if ( ! $_product ) {
			return $total;
		}

		$price = yit_get_display_price( $_product, $price = '', $raq_item['quantity'] );
		$total = apply_filters( 'yith_ywraq_hide_price_template', $price, $_product->get_id(), $raq );

		if ( is_numeric( $total ) ) {
			$total = apply_filters( 'yith_ywraq_product_price', $price, $_product, $raq_item );
		}

		return wc_price( $total );
	}
}

if ( ! function_exists( 'ywraq_get_quote_total' ) ) {
	/**
	 * Deprecated function
	 *
	 * @param array $raq Array.
	 *
	 * @return string
	 * @deprecated
	 */
	function ywraq_get_quote_total( $raq ) {
		$total = 0;

		foreach ( $raq as $raq_item ) {
			$_product = wc_get_product( ( isset( $raq_item['variation_id'] ) && '' != $raq_item['variation_id'] ) ? $raq_item['variation_id'] : $raq_item['product_id'] ); //phpcs:ignore

			if ( ! $_product ) {
				continue;
			}
			$price = yit_get_display_price( $_product, $price = '', $raq_item['quantity'] );
			$total += apply_filters( 'yith_ywraq_product_price', $price, $_product, $raq_item );
		}

		return wc_price( $total );
	}
}

if ( ! function_exists( 'ywraq_get_browse_list_message' ) ) {
	/**
	 * Deprecated function
	 *
	 * @return mixed|void
	 * @deprecated
	 */
	function ywraq_get_browse_list_message() {
		return ywraq_get_label( 'browse_list' );
	}
}

if ( ! function_exists( 'ywraq_convert_date_format' ) ) {

	/**
	 * Deprecated function
	 *
	 * @param string $format .
	 *
	 * @return mixed
	 * @deprecated
	 */
	function ywraq_convert_date_format( $format ) {

		$keys = array(
			'd' => '%d', // Day of the month, 2 digits with leading zeros (01 to 31).
			'D' => '%a', // A textual representation of a day, three letters (Mon through Sun).
			'j' => '%e', // Day of the month without leading zeros (1 to 31).
			'l' => '%A', // A full textual representation of the day of the week (Sunday through Saturday).
			'F' => '%B', // A full textual representation of a month, such as January or March.
			'm' => '%m', // Numeric representation of a month, with leading zeros (01 through 12).
			'M' => '%b', // A short textual representation of a month, three letters (Jan through Dec).
			'n' => '%m', // Numeric representation of a month, without leading zeros (01 through 12).
			'Y' => '%Y', // A full numeric representation of a year, 4 digits (Examples: 1999 or 2003).
			'y' => '%y', // A two digit representation of a year (Examples: 99 or 03).
		);

		return str_replace( array_keys( $keys ), $keys, $format );

	}
}

if ( ! function_exists( 'ywraq_adjust_type' ) ) {
	/**
	 * Deprecated function
	 *
	 * @param string $attr  .
	 * @param string $value .
	 *
	 * @return false|int
	 * @deprecated
	 */
	function ywraq_adjust_type( $attr, $value ) {
		if ( 'date_created' === $attr ) {
			$value = strtotime( $value );
		}

		return $value;
	}
}

if ( ! function_exists( 'ywraq_get_unique_post_title' ) ) {
	/**
	 * Get unique post title
	 *
	 * @param string $title     The post title.
	 * @param int    $post_id   The post ID.
	 * @param string $post_type The post type.
	 *
	 * @return string
	 */
	function ywraq_get_unique_post_title( $title, $post_id, $post_type = null ) {
		$count       = 1;
		$start_title = $title;
		$post_type   = is_null( $post_type ) ? get_post_type( $post_id ) : $post_type;
		while ( get_page_by_title( $title, OBJECT, $post_type ) ) {
			$title = sprintf( '%s (%d)', $start_title, $count++ );
		}

		return $title;
	}
}


if ( ! function_exists( 'ywraq_force_start_session_on_quote_page' ) ) {
	/**
	 * Force start of session in quote page
	 *
	 * @return boolean
	 */
	function ywraq_force_start_session_on_quote_page() {

		global $post;
		if ( $post && is_user_logged_in() ) {
			return (int) get_option( 'ywraq_page_id' ) === $post->ID;
		}

		return false;
	}
}


if( !function_exists('ywraq_get_order')){
	/**
	 * Return the order if we are inside the order detail page.
	 *
	 * Created to support HPOS.
	 *
	 * @return bool|WC_Order|WC_Order_Refund
	 */
	function ywraq_get_order(){
		global $post, $thepostid, $theorder;
		if( !$theorder && $post ){
			$theorder = OrderUtil::init_theorder_object( $post );
		}

		$request = $_REQUEST; //phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$order        = $theorder;
		if( !$order ){
			if ( isset( $request['page'], $request['id'] ) && 'wc-orders' === $request['page'] ) {
				$order_id = $request['id'];
			} else {
				$order_id = $request['post'] ?? $request['post_ID'] ?? 0;
			}
			$order        = wc_get_order( $order_id ) ;
		}

		return $order;
	}
}

if ( ! function_exists( 'ywraq_is_using_block_template_in_single_product' ) ) {
	/**
	 * Check if the WC is using the block template on single product
	 *
	 * @since 4.16.0
	 */
	function ywraq_is_using_block_template_in_single_product() {
		static $use_blocks = null;
		if ( is_null( $use_blocks ) ) {
			// The blockified templates for Single Products are available since WooCommerce 7.9.
			$use_blocks = version_compare( WC()->version, '7.9.0', '>=' ) && \Automattic\WooCommerce\Blocks\Utils\BlockTemplateUtils::supports_block_templates();

			if ( $use_blocks ) {
				try {
					$container  = \Automattic\WooCommerce\Blocks\Package::container();
					$controller = $container->get( \Automattic\WooCommerce\Blocks\BlockTemplatesController::class );
					$use_blocks = $controller->block_template_is_available( 'single-product' );
					if ( $use_blocks ) {
						$templates = get_block_templates( array( 'slug__in' => array( 'single-product' ) ) );

						$use_blocks = ! ( isset( $templates[0] ) && Automattic\WooCommerce\Blocks\Utils\BlockTemplateUtils::template_has_legacy_template_block( $templates[0] ) );
					}
				} catch ( Exception $e ) {
					return false;
				}
			}
		}

		return $use_blocks;
	}
}
