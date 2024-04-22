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

/**
 * Quote Detail
 *
 * Shows recent orders on the account page
 *
 * @since   1.0.0
 * @author  YITH <plugins@yithemes.com>
 *
 * @version 3.0.0
 * @package YITH\RequestAQuote
 * @var $order_id int
 * @var $current_user
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
YITH_YWRAQ_Order_Request()->is_expired( $order_id );

$order = wc_get_order( $order_id ); //phpcs:ignore

do_action( 'ywraq_before_view_quote', $order );

add_filter( 'woocommerce_is_attribute_in_product_name', '__return_false' );

if ( ! $order ) {
	esc_html_e( 'This Quote doesn\'t exist.', 'yith-woocommerce-request-a-quote' );

	return;
}

$user_email       = $order->get_meta( 'ywraq_customer_email' ); //phpcs:ignore
$customer_message = $order->get_meta( 'ywraq_customer_message' );
$af4              = $order->get_meta( 'ywraq_other_email_fields' );
$billing_phone    = $order->get_billing_phone();

$is_new = $order->get_status() === 'ywraq-new';

$raq_version = $order->get_meta( '_ywraq_version' );

if ( $is_new ) {
	$admin_message = get_option( 'ywraq_quote_admin_text_new_quotes_status', '' );
	$show_price    = ! ( 'yes' === get_option( 'ywraq_quote_my_account_hide_price_new_quote', 'yes' ) || 'yes' === get_option( 'ywraq_hide_price' ) );
	if ( catalog_mode_plugin_enabled() ) {
		foreach ( $order->get_items() as $item_id => $item ) {
			/**
			 * Define variable.
			 *
			 * @var WC_Product            $_product
			 * @var WC_Order_Item_Product $item
			 */
			$_product   = $item->get_product();
			$hide_price = apply_filters( 'yith_ywraq_hide_price_template', WC()->cart->get_product_subtotal( $_product, $item['qty'] ), $_product->get_id(), $item );
			if ( '' === $hide_price ) {
				$show_price = false;
			}
		}
	}
} else {
	$admin_message = $order->get_meta( '_ywraq_request_my_account_admin_message' );
	if ( empty( $admin_message ) && version_compare( $raq_version, '3.0.0', '<' ) ) {
		$admin_message = $order->get_meta( '_ywcm_request_response' );
	}
	$show_price = true;
}

$show_total_column = $show_price && ( ywraq_show_element_on_list( 'line_total' ) || in_array( $order->get_status(), array( 'pending', 'completed' ) ) );

$exdata     = $order->get_meta( '_ywcm_request_expire' );
$order_date = strtotime( $order->get_date_created() );

$raq_nonce = ywraq_get_token( 'reject-request-quote', $order_id, $order->get_meta( 'ywraq_customer_email' ) );

if ( $order->get_user_id() !== $current_user->ID ) {
	esc_html_e( 'You do not have permission to read the quote.', 'yith-woocommerce-request-a-quote' );

	return;
}

if ( 'trash' === $order->get_status() ) {
	esc_html_e( 'This Quote was deleted by administrator.', 'yith-woocommerce-request-a-quote' );

	return;
}


$colspan = $show_total_column ? 1 : 2;

$pdf_file = false;

if ( file_exists( YITH_Request_Quote_Premium()->get_pdf_file_path( $order_id ) ) ) {
	$pdf_file = YITH_Request_Quote_Premium()->get_pdf_file_url( $order_id );
}
$print_button_pdf = get_option( 'ywraq_pdf_in_myaccount' ) === 'yes' && $pdf_file;

$status_label = wc_get_order_status_name( $order->get_status() );
if ( $is_new ) {
	$label_to_add  = get_option( 'ywraq_quote_label_new_quotes_status', esc_html_x( 'You will get a quote soon!', 'Endpoint label on My account', 'yith-woocommerce-request-a-quote' ) );
	$status_label .= empty( $label_to_add ) ? '' : ' - ' . $label_to_add;
}

$accept_button_text = ( YITH_Request_Quote()->enabled_checkout() && $order->get_status() !== 'ywraq-pending' ) ? esc_html__( 'Pay now', 'yith-woocommerce-request-a-quote' ) : ywraq_get_label( 'accept' );

?>
<div class="ywraq-view-quote-wrapper ywraq-status-<?php echo esc_attr( $order->get_status() ); ?>">

	<p>
		<a href="<?php echo esc_url( YITH_YWRAQ_Frontend()->my_account->get_quotes_url() ); ?>"><?php esc_html_e( '< Back to quote list', 'yith-woocommerce-request-a-quote' ); ?></a>
	</p>

	<header>
		<h2>
			<?php
			// translators: Number of quote.
			printf( esc_html__( 'Quote #%s details', 'yith-woocommerce-request-a-quote' ), esc_html( $order->get_order_number() ) );
			?>
		</h2>
		<?php
		if ( $print_button_pdf ) {
			?>
			<a class="ywraq-big-button ywraq-pdf-file button" href="<?php echo esc_url( $pdf_file ); ?>"target="_blank"><?php esc_html_e( 'PDF', 'yith-woocommerce-request-a-quote' ); ?></a>
		<?php } ?>
	</header>

	<!-- ORDER DATE -->
	<p>
		<strong><?php esc_html_e( 'Request date:', 'yith-woocommerce-request-a-quote' ); ?></strong> <?php echo esc_html( date_i18n( wc_date_format(), $order_date ) ); ?>
	</p>
	<!-- END ORDER DATE -->

	<!-- ORDER STATUS BLOCK -->
	<p class="ywraq-view-quote__order-status">
		<strong><?php echo esc_html__( 'Status:', 'yith-woocommerce-request-a-quote' ); ?></strong>
		<span class="ywraq-status <?php echo esc_attr( $order->get_status() ); ?>"><?php echo esc_html( $status_label ); ?></span>
	</p>
	<!-- END ORDER STATUS BLOCK -->

	<!-- EXPIRATION BLOCK -->
	<?php if ( ! $is_new && '' !== $exdata ) : ?>
		<p>
			<strong><?php esc_html_e( 'Quote expires on:', 'yith-woocommerce-request-a-quote' ); ?></strong> <?php echo esc_html( date_i18n( wc_date_format(), strtotime( $exdata ) ) ); ?>
		</p>
	<?php endif ?>
	<!-- END EXPIRATION BLOCK -->

	<!-- IF REJECTED SHOW CUSTOMER NOTES -->
	<?php if ( $order->has_status( 'ywraq-rejected' ) && $order->get_customer_note() ) : ?>
		<p>
			<strong><?php echo esc_html( __( 'Customer reason:', 'yith-woocommerce-request-a-quote' ) ); ?></strong> <?php echo esc_html( $order->get_customer_note() ); ?>
		</p>
	<?php endif; ?>
	<!-- END REJECTED BLOCK -->

	<!-- QUOTE DETAILS -->
	<table class="shop_table order_details">

		<thead>
		<tr>
			<th class="product-name"
				colspan="<?php echo esc_attr( $colspan ); ?>"><?php echo esc_html( _n( 'Product in your request', 'Products in your request', count( $order->get_items() ), 'yith-woocommerce-request-a-quote' ) ); ?></th>
			<?php if ( $show_total_column ) : ?>
				<th class="product-total"><?php esc_html_e( 'Quote Total', 'yith-woocommerce-request-a-quote' ); ?></th>
			<?php endif ?>
		</tr>
		</thead>

		<tbody>
		<?php
		if ( count( $order->get_items() ) > 0 ) {

			foreach ( $order->get_items() as $item_id => $item ) {
				/**
				 * Current product.
				 *
				 * @var $_product WC_Product
				 */
				$_product = $item->get_product();

				// retro compatibility.
				$item_meta = false;
				$title     = $_product ? $_product->get_title() : $item->get_name(); //phpcs:ignore


				if ( apply_filters( 'woocommerce_order_item_visible', true, $item ) ) :
					?>
					<tr class="<?php echo esc_attr( apply_filters( 'woocommerce_order_item_class', 'order_item', $item, $order ) ); ?>">
						<td class="product-name">
							<?php if ( apply_filters( 'ywraq_item_thumbnail', true ) ) : ?>
								<span class="product-thumbnail">
									<?php
									if ( $_product ) {
										/**
										 * APPLY_FILTERS:ywraq_quote_item_thumbnail
										 *
										 * Filter  the product thumbnail for the product on quote.
										 *
										 * @param   string  $product_thumbnail  Product thumbnail.
										 * @param   int  $item_id  Quote item id.
										* @param   WC_Order_Item_Product  $item  Quote item product.
										 *
										 * @return array
										 */
										$thumbnail = apply_filters( 'ywraq_quote_item_thumbnail', $_product->get_image(), $item_id, $item );

										if ( ! $_product->is_visible() || ! apply_filters( 'ywraq_list_show_product_permalinks', true, 'quote-view' ) ) {
											echo $thumbnail; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
										} else {
											printf( '<a href="%s">%s</a>', esc_url( $_product->get_permalink() ), $thumbnail ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
										}
									}
									?>
								</span>
							<?php endif; ?>
							<span class="product-name-item">
							<?php
							if ( ! $_product || ( $_product && ! $_product->is_visible() ) || ! apply_filters( 'ywraq_list_show_product_permalinks', true, 'view_quote' ) ) {
								echo wp_kses_post( apply_filters( 'woocommerce_order_item_name', $title, $item, false ) );
							} else {
								echo apply_filters( 'woocommerce_order_item_name', sprintf( '<a href="%s">%s</a>', esc_url( get_permalink( $item['product_id'] ) ), esc_html( $title ) ), $item, true ); //phpcs:ignore
							}

							echo wp_kses_post( apply_filters( 'woocommerce_order_item_quantity_html', ' <strong class="product-quantity">' . sprintf( '&times; %s', esc_html( $item['qty'] ) ) . '</strong>', $item ) );

							if ( $_product && $_product->get_sku() !== '' && ywraq_show_element_on_list( 'sku' ) ) {
								$sku_label = apply_filters( 'ywraq_sku_label', __( ' SKU:', 'yith-woocommerce-request-a-quote' ) );
								$sku       = sprintf( '<br><strong>%s</strong> %s', $sku_label, $_product->get_sku() );
								echo  wp_kses_post( apply_filters( 'ywraq_sku_label_html', $sku, $_product ) ); //phpcs:ignore
							}

							// Allow other plugins to add additional product information here.
							do_action( 'woocommerce_order_item_meta_start', $item_id, $item, $order, false );

							if ( $item_meta ) {
								$item_meta->display();
							} else {
								wc_display_item_meta( $item );
							}

							// Allow other plugins to add additional product information here.
							do_action( 'woocommerce_order_item_meta_end', $item_id, $item, $order, false );
							?>
							</span>
						</td>
						<?php if ( $show_price ) : ?>
							<td class="product-total">
								<?php

								echo wp_kses_post( $order->get_formatted_line_subtotal( $item ) );

								?>
							</td>
						<?php endif ?>
					</tr>
					<?php

				endif;

				if ( $order->has_status( array( 'completed', 'processing' ) ) && $_product->get_purchase_note() ) :
					?>
					<tr class="product-purchase-note">
						<td colspan="3">
							<?php
							echo wpautop( is_callable( 'apply_shortcodes' ) ? apply_shortcodes( wp_kses_post( $_product->get_purchase_note() ) ) : do_shortcode( wp_kses_post( $_product->get_purchase_note() ) ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
							?>
						</td>
					</tr>
					<?php
				endif;
			}
		}

		do_action( 'woocommerce_order_items_table', $order );
		?>
		</tbody>
		<tfoot>
		<?php
		$has_refund = false;

		if ( $order->get_total_refunded() ) {
			$has_refund = true;
		}

		$totals = $order->get_order_item_totals(); //phpcs:ignore

		if ( $show_total_column && $totals ) {
			foreach ( $totals as $key => $total ) {
				$value = $total['value'];

				?>
				<?php if ( $show_price ) : ?>
					<tr>
						<th scope="row"><?php echo esc_html( $total['label'] ); ?></th>
						<td><?php echo wp_kses_post( $value ); ?></td>
					</tr>
				<?php endif ?>
				<?php
			}
		}
		?>

		<?php
		if ( in_array( $order->get_status(), array( 'ywraq-pending', 'ywraq-accepted', 'pending' ), true ) ) :
			?>
		<tr>

			<td colspan="2">
				<p class="ywraq-buttons">
					<?php
					if ( in_array( $order->get_status(), array( 'ywraq-pending' ), true ) ) :
						if ( get_option( 'ywraq_show_accept_link' ) !== 'no' ) :
							?>
							<a class="ywraq-button ywraq-accept button" href="<?php echo esc_url( ywraq_get_accepted_quote_page( $order ) ); ?>">
								<?php echo esc_html( $accept_button_text ); ?>
							</a>
						<?php endif ?>
						<?php
						if ( get_option( 'ywraq_show_reject_link' ) !== 'no' ) :
							?>
							<a class="ywraq-button ywraq-reject button" href="#"><?php esc_html( ywraq_get_label( 'reject', true ) ); ?></a>
						<?php endif ?>
					<?php endif ?>
					<?php if ( in_array( $order->get_status(), array( 'ywraq-accepted', 'pending' ) ) ) : ?>
						<?php if ( get_option( 'ywraq_show_accept_link' ) !== 'no' && YITH_Request_Quote()->enabled_checkout() ) : ?>
							<a class="ywraq-button ywraq-accept button" href="
							<?php
							echo esc_url(
								add_query_arg(
									array(
										'request_quote' => $order_id,
										'status'        => 'accepted',
										'raq_nonce'     => ywraq_get_token( 'accept-request-quote', $order_id, $user_email ),
										'lang'          => get_post_meta( $order_id, 'wpml_language', true ),
									),
									YITH_Request_Quote()->get_raq_page_url()
								)
							)
							?>
			"><?php echo esc_html( $accept_button_text ); ?>
							</a>
						<?php endif ?>
					<?php endif ?>
				</p>
			</td>
		<tr>
			<?php endif ?>
		</tfoot>
	</table>
	<!-- END QUOTE DETAILS -->


	<div id="ywraq-reject-confirm" title="<?php esc_html_e( 'Reject Quote.', 'yith-woocommerce-request-a-quote' ); ?>" style="display:none;">
		<div class="ywraq-reject-confirm-wrapper">
			<p>
				<?php
				// translators: Quote number.
				printf( esc_html_x( 'You are about to reject the quote #%s.', 'The placeholder is the quote number', 'yith-woocommerce-request-a-quote' ), esc_html( $order->get_order_number() ) );
				?>
				<br>
				<?php esc_html_e( 'Please feel free to enter here your reason or provide us your feedback:', 'yith-woocommerce-request-a-quote' ); ?>
			</p>
			<form id="reject-form">
				<input type="hidden" name="status" value="rejected" />
				<input type="hidden" name="raq_nonce" value="<?php echo esc_attr( $raq_nonce ); ?>" />
				<input type="hidden" name="request_quote" value="<?php echo esc_attr( $order_id ); ?>" />
				<input type="hidden" name="ywraq_action" value="<?php echo esc_attr( 'reject_quote' ); ?>" />
				<input type="hidden" name="confirm" value="yes" />
				<textarea name="reason" id="reason"></textarea>
				<button
					class="button"><?php esc_html_e( 'Reject the quote', 'yith-woocommerce-request-a-quote' ); ?></button>
			</form>
		</div>
	</div>


	<?php do_action( 'woocommerce_order_details_after_order_table', $order ); ?>

	<div class="ywraq-additional-information">
		<div class="ywraq-customer-information">
			<header>
				<h3><?php esc_html_e( 'Customer\'s details', 'yith-woocommerce-request-a-quote' ); ?></h3>
			</header>


			<?php
			$user_name = $order->get_meta( 'ywraq_customer_name', true );


			$billing['billing_company']   = $order->get_billing_company();
			$billing['billing_address_1'] = $order->get_billing_address_1();
			$billing['billing_address_2'] = $order->get_billing_address_2();
			$billing['billing_name']      = $order->get_billing_first_name();
			$billing['billing_surname']   = $order->get_billing_last_name();
			$billing['billing_city']      = $order->get_billing_city();
			$billing['billing_postcode']  = $order->get_billing_postcode();
			$billing['billing_state']     = $order->get_billing_state();
			$billing['billing_country']   = $order->get_billing_country();
			$billing['billing_email']     = $order->get_billing_email();
			$billing['billing_email']     = empty( $billing_email ) ? $user_email : $billing_email;
			$billing['billing_phone']     = $order->get_billing_phone();
			$billing['billing_phone']     = empty( $billing_phone ) ? $order->get_meta( 'ywraq_billing_phone' ) : $billing_phone;
			$billing['billing_vat']       = $order->get_meta( 'ywraq_billing_vat' );
			$billing['billing_vat']       = empty( $billing_vat ) ? $order->get_meta( '_billing_vat' ) : $billing_vat;


			// Removed field duplicated.
			if ( ! empty( $af4 ) && is_array( $af4 ) ) {
				foreach ( $billing as $field ) {
					$find = array_search( $field, $af4, true );
					if ( isset( $af4[ $find ] ) ) {
						unset( $af4[ $find ] );
					}
				}
			}

			$content_printed = '';
			$content         = ( empty( $billing['billing_name'] ) && empty( $billing['billing_surname'] ) ) ? $user_name : $billing['billing_name'] . ' ' . $billing['billing_surname'];
			printf( '<p class="ywraq-view-quote__customer-info"><strong>%s</strong> %s</p>', esc_html__( 'Name:', 'yith-woocommerce-request-a-quote' ), esc_html( $content ) );

			if ( $billing['billing_company'] ) {
				printf( '<p class="ywraq-view-quote__customer-info"><strong>%s</strong> %s</p>', esc_html__( 'Company:', 'yith-woocommerce-request-a-quote' ), esc_html( $billing['billing_company'] ) );
			}

			if ( $billing['billing_address_1'] || $billing['billing_address_2'] ) {

				$content = $billing['billing_address_1'] . ( $billing['billing_address_1'] ? '<br />' : '' ) . $billing['billing_address_2'];
				printf( '<p class="ywraq-view-quote__customer-info"><strong>%s</strong> %s</p>', esc_html__( 'Address:', 'yith-woocommerce-request-a-quote' ), wp_kses_post( ( $content ) ) );
			}

			if ( $billing['billing_city'] ) {
				printf( '<p class="ywraq-view-quote__customer-info"><strong>%s</strong> %s</p>', esc_html__( 'City:', 'yith-woocommerce-request-a-quote' ), esc_html( $billing['billing_city'] ) );
			}

			if ( $billing['billing_postcode'] ) {
				printf( '<p class="ywraq-view-quote__customer-info"><strong>%s</strong> %s</p>', esc_html__( 'Postcode:', 'yith-woocommerce-request-a-quote' ), esc_html( $billing['billing_postcode'] ) );
			}

			if ( $billing['billing_state'] ) {
				$states  = WC()->countries->get_states( $billing['billing_country'] );
				$state   = is_array( $states ) ? $states[ $billing['billing_state'] ] : '';
				$content = ( '' === $state ) ? $billing['billing_state'] : $state;
				printf( '<p class="ywraq-view-quote__customer-info"><strong>%s</strong> %s</p>', esc_html__( 'State/Province:', 'yith-woocommerce-request-a-quote' ), esc_html( $content ) );
			}

			if ( $billing['billing_country'] ) {
				$countries = WC()->countries->get_countries();
				$country   = isset( $countries[ $billing['billing_country'] ] ) ? $countries[ $billing['billing_country'] ] : $billing['billing_country'];
				printf( '<p class="ywraq-view-quote__customer-info"><strong>%s</strong> %s</p>', esc_html__( 'Country:', 'yith-woocommerce-request-a-quote' ), esc_html( $country ) );
			}

			if ( $billing['billing_email'] ) {
				printf( '<p class="ywraq-view-quote__customer-info"><strong>%s</strong> %s</p>', esc_html__( 'Email:', 'yith-woocommerce-request-a-quote' ), esc_html( $billing['billing_email'] ) );
			}

			if ( $billing['billing_phone'] ) {

				printf( '<p class="ywraq-view-quote__customer-info"><strong>%s</strong> %s</p>', esc_html__( 'Telephone:', 'yith-woocommerce-request-a-quote' ), esc_html( $billing['billing_phone'] ) );
			}

			if ( $billing['billing_vat'] ) {
				printf( '<p class="ywraq-view-quote__customer-info"><strong>%s</strong> %s</p>', esc_html__( 'VAT:', 'yith-woocommerce-request-a-quote' ), esc_html( $billing['billing_vat'] ) );
			}

			// Additional customer details hook.
			do_action( 'woocommerce_order_details_after_customer_details', $order );
			?>


			<?php

			if ( '' !== $customer_message || ! empty( $af4 ) || '' !== $admin_message ) :

				if ( ! empty( $af4 ) ) :
					/**
					 * APPLY_FILTERS: ywraq_customer_info_account_fields
					 * 
					 * fields to show in customer's informations
					 * 
					 * @param array $fields fields to show.
					 */
					$af4 = apply_filters( 'ywraq_customer_info_account_fields', $af4 );
					foreach ( $af4 as $key => $value ) :
						if ( ! empty( $value ) ) :
							?>
							<p class="ywraq-view-quote__customer-info">
								<strong><?php echo wp_kses_post( ucwords( str_replace( '-', ' ', $key ) ) ); ?>
									:</strong>
								<?php echo wp_kses_post( $value ); ?>
							</p>
							<?php
						endif;
					endforeach;
				endif;

				// Check for customer note.
				if ( '' !== $customer_message ) :
					?>
					<p class="ywraq-view-quote__customer-info">
						<strong><?php esc_html_e( 'Message:', 'yith-woocommerce-request-a-quote' ); ?></strong>
					<p><?php echo wp_kses_post( wptexturize( stripslashes( $customer_message ) ) ); ?></p>
					</p>
				<?php endif; ?>
			<?php endif ?>
		</div>
		<?php if ( '' !== $admin_message ) : ?>
			<div class="ywraq-admin-message">
				<header>
					<h3><?php esc_html_e( 'Admin reply:', 'yith-woocommerce-request-a-quote' ); ?></h3>
				</header>
				<div class="message-content">
					<p><?php echo nl2br( wp_kses_post( wptexturize( stripslashes( $admin_message ) ) ) ); ?></p>
				</div>

			</div>
		<?php endif; ?>
	</div>

</div>
