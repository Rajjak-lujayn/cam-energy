<?php
/**
 * Quote Detail for guest
 *
 * Shows quote resume for guest
 *
 * @package YITH\RequestAQuote
 * @since   3.0.0
 * @version 3.0.0
 * @author  YITH <plugins@yithemes.com>
 *
 * @var $order WC_Order.
 */

defined( 'ABSPATH' ) || exit;

$order_id = $order->get_id();
wp_enqueue_style( 'yith_ywraq_my-account' );

add_filter( 'woocommerce_is_attribute_in_product_name', '__return_false' );

$user_email       = $order->get_meta( 'ywraq_customer_email' ); //phpcs:ignore
$customer_message = $order->get_meta( 'ywraq_customer_message' );
$af4              = $order->get_meta( 'ywraq_other_email_fields' );
$exdata           = $order->get_meta( '_ywcm_request_expire' );
$order_date       = strtotime( $order->get_date_created() );

if ( $order->get_status() === 'trash' ) {
	esc_html_e( 'This Quote was deleted by administrator.', 'yith-woocommerce-request-a-quote' );
	return;
}

$admin_message = get_option( 'ywraq_quote_admin_text_new_quotes_status', '' );
$show_price    = ! ( 'yes' === get_option( 'ywraq_quote_my_account_hide_price_new_quote', 'yes' ) || 'yes' === get_option( 'ywraq_hide_price' ) );

$show_total_column = $show_price && ywraq_show_element_on_list( 'line_total' );
$colspan           = $show_total_column ? 1 : 2;
$print_button_pdf  = get_option( 'ywraq_pdf_in_myaccount' ) === 'yes';

if ( $order->get_status() === 'ywraq-new' ) {

	if ( catalog_mode_plugin_enabled() ) {
		foreach ( $order->get_items() as $item_id => $item ) {
			/**
			 * Defining type of variable
			 *
			 * @var $_product WC_Product
			 */
			$_product   = $item->get_product();
			$hide_price = apply_filters( 'yith_ywraq_hide_price_template', WC()->cart->get_product_subtotal( $_product, $item['qty'] ), $_product->get_id(), $item );
			if ( '' === $hide_price ) {
				$show_price = false;
			}
		}
	}
}

$status_label  = wc_get_order_status_name( $order->get_status() );
$label_to_add  = get_option( 'ywraq_quote_label_new_quotes_status', esc_html_x( 'You will get a quote soon!', 'Endpoint label on My account', 'yith-woocommerce-request-a-quote' ) );
$status_label .= empty( $label_to_add ) ? '' : ' - ' . $label_to_add;
?>
<div class="ywraq-view-quote-wrapper ywraq-status-<?php echo esc_attr( $order->get_status() ); ?>">

	<header>
		<h2>
		<?php
			// translators: number of quote.
			printf( esc_html__( 'Quote #%d details', 'yith-woocommerce-request-a-quote' ), esc_html( $order->get_order_number() ) );
		?>
			</h2>
		<?php

		if ( $print_button_pdf && file_exists( YITH_Request_Quote_Premium()->get_pdf_file_path( $order->get_id() ) ) ) {
			$pdf_file = YITH_Request_Quote_Premium()->get_pdf_file_url( $order->get_id() );
			?>
			<a class="ywraq-big-button ywraq-pdf-file"
				href="<?php echo esc_url( $pdf_file ); ?>"
				target="_blank"><?php esc_html_e( 'PDF', 'yith-woocommerce-request-a-quote' ); ?></a>

		<?php } ?>
	</header>
	<p>
		<strong><?php esc_html_e( 'Request date:', 'yith-woocommerce-request-a-quote' ); ?></strong> <?php echo esc_html( date_i18n( wc_date_format(), $order_date ) ); ?>
	</p>
	<p class="ywraq-view-quote__order-status">
		<strong><?php echo esc_html__( 'Status:', 'yith-woocommerce-request-a-quote' ); ?></strong> <span
			class="ywraq-status <?php echo esc_attr( $order->get_status() ); ?>"><?php echo esc_html( $status_label ); ?></span>
	</p>
	<?php if ( '' !== $exdata ) : ?>
		<p>
			<strong><?php esc_html_e( 'Quote expires on:', 'yith-woocommerce-request-a-quote' ); ?></strong> <?php echo esc_html( date_i18n( wc_date_format(), strtotime( $exdata ) ) ); ?>
		</p>
	<?php endif ?>

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
				 * Current product
				 *
				 * @var $_product WC_Product
				 */
				$_product = $item->get_product();

				$title     = $_product ? $_product->get_title() : $item->get_name(); //phpcs:ignore

				if ( $_product && $_product->get_sku() !== '' && ywraq_show_element_on_list( 'sku' ) ) {
					$sku    = apply_filters( 'ywraq_sku_label', __( ' SKU:', 'yith-woocommerce-request-a-quote' ) ) . $_product->get_sku();
					$title .= ' ' . apply_filters( 'ywraq_sku_label_html', $sku, $_product ); //phpcs:ignore
				}

				if ( apply_filters( 'woocommerce_order_item_visible', true, $item ) ) :
					?>
					<tr class="<?php echo esc_attr( apply_filters( 'woocommerce_order_item_class', 'order_item', $item, $order ) ); ?>">
						<td class="product-name">
							<?php if ( apply_filters( 'ywraq_item_thumbnail', true ) ) : ?>
								<span class="product-thumbnail">
									<?php
									if ( $_product ) {
										$thumbnail = $_product->get_image();

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
							if ( ! $_product || $_product->is_visible() || ! apply_filters( 'ywraq_list_show_product_permalinks', true, 'view_quote' ) ) {
								echo wp_kses_post( apply_filters( 'woocommerce_order_item_name', $title, $item, false ) );
							} else {
								echo apply_filters( 'woocommerce_order_item_name', sprintf( '<a href="%s">%s</a>', esc_url( get_permalink( $item['product_id'] ) ), esc_html( $title ) ), $item, true ); //phpcs:ignore
							}

							echo wp_kses_post( apply_filters( 'woocommerce_order_item_quantity_html', ' <strong class="product-quantity">' . sprintf( '&times; %s', esc_html( $item['qty'] ) ) . '</strong>', $item ) );

							// Allow other plugins to add additional product information here.
							do_action( 'woocommerce_order_item_meta_start', $item_id, $item, $order );

							wc_display_item_meta( $item );

							// Allow other plugins to add additional product information here.
							do_action( 'woocommerce_order_item_meta_end', $item_id, $item, $order );
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

		if ( $order->get_total_refunded() ) { //phpcs:ignore
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
		</tfoot>
	</table>

	<?php do_action( 'woocommerce_order_details_after_order_table', $order ); ?>
	<div class="ywraq-additional-information">
		<div class="ywraq-customer-information">
			<header>
				<h3><?php esc_html_e( 'Customer\'s details', 'yith-woocommerce-request-a-quote' ); ?></h3>
			</header>


			<?php
			$user_name = $order->get_meta( 'ywraq_customer_name', true );


			$billing_company   = $order->get_billing_company();
			$billing_address_1 = $order->get_billing_address_1();
			$billing_address_2 = $order->get_billing_address_2();
			$billing_name      = $order->get_billing_first_name();
			$billing_surname   = $order->get_billing_last_name();
			$billing_city      = $order->get_billing_city();
			$billing_postcode  = $order->get_billing_postcode();
			$billing_state     = $order->get_billing_state();
			$billing_country   = $order->get_billing_country();
			$billing_email     = $order->get_billing_email();
			$billing_email     = empty( $billing_email ) ? $user_email : $billing_email;
			$billing_phone     = $order->get_billing_phone();
			$billing_phone     = empty( $billing_phone ) ? $order->get_meta( 'ywraq_billing_phone' ) : $billing_phone;
			$billing_vat       = $order->get_meta( 'ywraq_billing_vat' );
			$billing_vat       = empty( $billing_vat ) ? $order->get_meta( '_billing_vat' ) : $billing_vat;


			$content = ( empty( $billing_name ) && empty( $billing_surname ) ) ? $user_name : $billing_name . ' ' . $billing_surname;
			printf( '<p class="ywraq-view-quote__customer-info"><strong>%s</strong> %s</p>', esc_html__( 'Name:', 'yith-woocommerce-request-a-quote' ), esc_html( $content ) );

			if ( $billing_company ) {
				printf( '<p class="ywraq-view-quote__customer-info"><strong>%s</strong> %s</p>', esc_html__( 'Company:', 'yith-woocommerce-request-a-quote' ), esc_html( $billing_company ) );
			}

			if ( $billing_address_1 || $billing_address_2 ) {
				$content = $billing_address_1 . ( $billing_address_1 ? '<br />' : '' ) . $billing_address_2;
				printf( '<p class="ywraq-view-quote__customer-info"><strong>%s</strong> %s</p>', esc_html__( 'Address:', 'yith-woocommerce-request-a-quote' ), wp_kses_post( ( $content ) ) );
			}

			if ( $billing_city ) {
				printf( '<p class="ywraq-view-quote__customer-info"><strong>%s</strong> %s</p>', esc_html__( 'City:', 'yith-woocommerce-request-a-quote' ), esc_html( $billing_city ) );
			}

			if ( $billing_postcode ) {
				printf( '<p class="ywraq-view-quote__customer-info"><strong>%s</strong> %s</p>', esc_html__( 'Postcode:', 'yith-woocommerce-request-a-quote' ), esc_html( $billing_postcode ) );
			}

			if ( $billing_state ) {
				$states  = WC()->countries->get_states( $billing_country );
				$state   = $states[ $billing_state ];
				$content = ( '' === $state ) ? $billing_state : $state;
				printf( '<p class="ywraq-view-quote__customer-info"><strong>%s</strong> %s</p>', esc_html__( 'State/Province:', 'yith-woocommerce-request-a-quote' ), esc_html( $content ) );
			}

			if ( $billing_country ) {
				$countries = WC()->countries->get_countries();
				printf( '<p class="ywraq-view-quote__customer-info"><strong>%s</strong> %s</p>', esc_html__( 'Country:', 'yith-woocommerce-request-a-quote' ), esc_html( isset( $countries[ $billing_country ] ) ? $countries[ $billing_country ] : $billing_country ) );
			}

			if ( $billing_email ) {
				printf( '<p class="ywraq-view-quote__customer-info"><strong>%s</strong> %s</p>', esc_html__( 'Email:', 'yith-woocommerce-request-a-quote' ), esc_html( $billing_email ) );
			}

			if ( $billing_phone ) {
				printf( '<p class="ywraq-view-quote__customer-info"><strong>%s</strong> %s</p>', esc_html__( 'Telephone:', 'yith-woocommerce-request-a-quote' ), esc_html( $billing_phone ) );
			}

			if ( $billing_vat ) {
				printf( '<p class="ywraq-view-quote__customer-info"><strong>%s</strong> %s</p>', esc_html__( 'VAT:', 'yith-woocommerce-request-a-quote' ), esc_html( $billing_vat ) );
			}

			// Additional customer details hook.
			do_action( 'woocommerce_order_details_after_customer_details', $order );
			?>


<?php

if ( '' !== $customer_message || ! empty( $af4 ) || '' !== $admin_message ) :

	if ( ! empty( $af4 ) ) :
		foreach ( $af4 as $key => $value ) :
			if ( ! empty( $value ) ) :
				?>
			<p class="ywraq-view-quote__customer-info">
				<strong><?php echo wp_kses_post( ucwords( str_replace( '-', ' ', $key ) ) ); ?>:</strong>
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
		<p><?php echo wp_kses_post( wptexturize( $customer_message ) ); ?></p>
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
		<p><?php echo nl2br( wp_kses_post( wptexturize( $admin_message ) ) ); ?></p>
	</div>

</div>
<?php endif; ?>
		</div>

	</div>

</div>
