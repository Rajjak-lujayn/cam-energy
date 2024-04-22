<?php
/**
 * HTML Template Email
 *
 * @package YITH\RequestAQuote
 * @since   1.0.0
 * @version 2.0.8
 * @author  YITH <plugins@yithemes.com>
 *
 * @var $order WC_Order.
 */

$border   = true;
$order_id = $order->get_id();

$enable_custom_text = $order->get_meta( '_ywraq_enable_custom_text' );
$message_before     = $order->get_meta( '_ywcm_request_response' );
$message_after      = $order->get_meta( '_ywraq_request_response_after' );
$enable_custom_text = empty( $enable_custom_text ) ? ! ( empty( $message_before ) || empty( $message_after ) ) ? 'yes' : 'no' : $enable_custom_text;

$columns   = get_option( 'ywraq_pdf_columns', 'all' );
$columns_n = 0;
/* be sure it is an array */
if ( ! is_array( $columns ) ) {
	$columns = array( $columns );
}
if ( in_array( 'all', $columns, true ) ) {
	$columns_n = 5;
} else {
	$columns_n = count( $columns );
}

if ( function_exists( 'icl_get_languages' ) ) {
	global $sitepress;
	$lang = $order->get_meta( 'wpml_language' );
	YITH_Request_Quote_Premium()->change_pdf_language( $lang );
}
add_filter( 'woocommerce_is_attribute_in_product_name', '__return_false' );


if ( $enable_custom_text && ! empty( $message_before ) ) :
	?>
	<div class="after-list">
		<p><?php echo wp_kses_post( apply_filters( 'ywraq_quote_before_list', nl2br( $message_before ), $order_id ) ); ?></p>
	</div>
<?php endif; ?>

<?php do_action( 'yith_ywraq_email_before_raq_table', $order ); ?>

	<div class="table-wrapper">
		<div class="mark"></div>

		<ul class="quote-table raq-header fields-<?php echo $columns_n; //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>">
			<?php if ( get_option( 'ywraq_show_preview' ) === 'yes' || in_array( 'all', $columns, true ) || in_array( 'thumbnail', $columns, true ) ) : ?>

			<?php endif ?>
			<?php if ( in_array( 'all', $columns, true ) || in_array( 'product_name', $columns, true ) ) : ?>
				<li class="product-title"><?php esc_html_e( 'Product', 'yith-woocommerce-request-a-quote' ); ?></li>
				<li class="product-preview"></li>
			<?php endif ?>
			<?php if ( in_array( 'all', $columns, true ) || in_array( 'unit_price', $columns, true ) ) : ?>
				<li class="product-price"><?php esc_html_e( 'Unit Price', 'yith-woocommerce-request-a-quote' ); ?></li>
			<?php endif ?>
			<?php if ( in_array( 'all', $columns, true ) || in_array( 'quantity', $columns, true ) ) : ?>
				<li class="product-quantity"><?php esc_html_e( 'Quantity', 'yith-woocommerce-request-a-quote' ); ?></li>
			<?php endif ?>
			<?php if ( in_array( 'all', $columns, true ) || in_array( 'product_subtotal', $columns, true ) ) : ?>
				<li class="product-subtotal"><?php esc_html_e( 'Subtotal', 'yith-woocommerce-request-a-quote' ); ?></li>
			<?php endif ?>
			<div style="clear: both;"></div>
		</ul>
		<ul class="quote-table raq-items">
			<?php
			$items = $order->get_items();

			$currency = $order->get_currency();
			if ( ! empty( $items ) ) :
				foreach ( $items as $item ) :

					if ( isset( $item['variation_id'] ) && $item['variation_id'] ) {
						$_product = wc_get_product( $item['variation_id'] );
					} else {
						$_product = wc_get_product( $item['product_id'] );
					}

					if ( ! $_product ) {
						continue;
					}


					$title = $_product->get_title(); //phpcs:ignore

					if ( $_product->get_sku() !== '' && ywraq_show_element_on_list( 'sku' ) ) {
						$sku    = wp_kses_post( apply_filters( 'ywraq_sku_label', esc_html__( ' SKU:', 'yith-woocommerce-request-a-quote' ) ) ) . $_product->get_sku();
						$title .= '<br />' . apply_filters( 'ywraq_sku_label_html', $sku, $_product ); //phpcs:ignore
					}

					$subtotal   = wc_price( $item['line_total'], array( 'currency' => $currency ) );
					$unit_price = wc_price( $item['line_total'] / $item['qty'], array( 'currency' => $currency ) );

					if ( get_option( 'ywraq_show_old_price' ) === 'yes' ) {
						$subtotal   = ( $item['line_subtotal'] !== $item['line_total'] ) ? '<small><del>' . wc_price( $item['line_subtotal'], array( 'currency' => $currency ) ) . '</del></small> ' . wc_price( $item['line_total'], array( 'currency' => $currency ) ) : wc_price( $item['line_subtotal'], array( 'currency' => $currency ) );
						$unit_price = ( $item['line_subtotal'] !== $item['line_total'] ) ? '<small><del>' . wc_price( $item['line_subtotal'] / $item['qty'], array( 'currency' => $currency ) ) . '</del></small> ' . wc_price( $item['line_total'] / $item['qty'] ) : wc_price( $item['line_subtotal'] / $item['qty'], array( 'currency' => $currency ) );
					}


					?>

					<li class="raq_item fields-<?php echo $columns_n; //phpcs:ignore ?> <?php echo ( $item->get_meta_data() ) ? 'with-metas' : ''; //phpcs:ignore ?>">
						<ul>
							<?php
							if ( get_option( 'ywraq_show_preview' ) === 'yes' || in_array( 'all', $columns, true ) || in_array( 'thumbnail', $columns, true ) ) :
								?>
								<li class="item-preview">
									<?php
									$image_id = $_product->get_image_id();
									if ( $image_id ) {
										$thumbnail_id  = $image_id;
										$thumbnail_url = apply_filters( 'ywraq_pdf_product_thumbnail', get_attached_file( $thumbnail_id ), $thumbnail_id );
									} else {
										$thumbnail_url = function_exists( 'wc_placeholder_img_src' ) ? wc_placeholder_img_src() : '';
									}
									$thumbnail = sprintf( '<img src="%s" style="max-width:100px;"/>', $thumbnail_url );

									if ( ! $_product->is_visible() ) {
										echo $thumbnail; //phpcs:ignore
									} else {
										printf( '<a href="%s">%s</a>', $_product->get_permalink(), $thumbnail ); //phpcs:ignore
									}
									?>
								</li>
							<?php endif ?>
							<?php if ( in_array( 'all', $columns, true ) || in_array( 'product_name', $columns, true ) ) : ?>
								<li class="item-name"><?php echo wp_kses_post( $title ); ?></li>
							<?php endif ?>
							<?php if ( in_array( 'all', $columns, true ) || in_array( 'unit_price', $columns, true ) ) : ?>
								<li class="item-price"><?php echo wp_kses_post( $unit_price ); ?></li>
							<?php endif ?>
							<?php if ( in_array( 'all', $columns, true ) || in_array( 'quantity', $columns, true ) ) : ?>
								<li class="item-quantity"><?php echo wp_kses_post( $item['qty'] ); ?></li>
							<?php endif ?>
							<?php if ( in_array( 'all', $columns, true ) || in_array( 'product_subtotal', $columns, true ) ) : ?>
								<li class="item-subtotal"><?php echo wp_kses_post( apply_filters( 'ywraq_quote_subtotal_item', ywraq_formatted_line_total( $order, $item ), $item['line_total'], $_product, $item ) ); ?></li>
							<?php endif ?>
							<div style="clear: both;"></div>
						</ul>
					</li>
					<?php if ( $item->get_meta_data() ) : ?>
					<li class="raq_item metas fields-<?php echo $columns_n; //phpcs:ignore ?>" style="clear:both;">
						<ul>
							<?php if ( get_option( 'ywraq_show_preview' ) === 'yes' ) : ?>
								<li>&nbsp;</li>
							<?php endif; ?>
							<li style="width:100%">
								<small>
									<?php
									$item_meta = wc_display_item_meta( $item, array( 'echo' => false ) );
									$item_meta = str_replace( '<p', '<span', $item_meta );
									$item_meta = str_replace( '</p>', '</span>', $item_meta );

									echo wp_kses_post( $item_meta );
									?>
								</small>
							</li>
							<div style="clear: both"></div>
						</ul>
					</li>
				<?php endif; ?>

				<?php endforeach; ?>
			<?php endif ?>
		</ul>
		<?php if ( 'no' === get_option( 'ywraq_pdf_hide_total_row', 'no' ) ) { ?>
			<ul class="quote-table raq-totals">
				<li>
				<?php
				foreach ( $order->get_order_item_totals() as $key => $total ) :
					if ( ! apply_filters( 'ywraq_hide_payment_method_pdf', false ) || 'payment_method' !== $key ) :
						?>
						<ul>
							<li class="totals_label colspan<?php echo $columns_n - 1; //phpcs:ignore  ?>"><?php echo $total['label']; ?></li>
							<li class="totals_value"><?php echo $total['value']; //phpcs:ignore  ?></li>
						</ul>
						<div style="clear:both">
						</div>
					<?php endif; ?>
				<?php endforeach; ?>
				</li>
			</ul>
		<?php } ?>
	</div>


<?php if ( get_option( 'ywraq_pdf_link' ) === 'yes' ) : ?>
	<div>
		<table>
			<tr>
				<?php if ( get_option( 'ywraq_show_accept_link' ) !== 'no' ) : ?>
					<td><a href="<?php echo esc_url( ywraq_get_accepted_quote_page( $order ) ); ?>"
							class="pdf-button"><?php ywraq_get_label( 'accept', true ); ?></a></td>
					<?php
				endif;
				echo ( get_option( 'ywraq_show_accept_link' ) !== 'no' && get_option( 'ywraq_show_reject_link' ) !== 'no' ) ? '<td><span style="color: #666666">|</span></td>' : '';
				if ( get_option( 'ywraq_show_reject_link' ) !== 'no' ) :
					?>
					<td><a href="<?php echo esc_url( ywraq_get_rejected_quote_page( $order ) ); ?>"
							class="pdf-button"><?php ywraq_get_label( 'reject', true ); ?></a></td>
				<?php endif ?>
			</tr>
		</table>
	</div>
<?php endif ?>

<?php do_action( 'yith_ywraq_email_after_raq_table', $order ); ?>

<?php if ( $enable_custom_text && ! empty( $message_after ) ) : ?>
	<div class="after-list">
		<p><?php echo wp_kses_post( apply_filters( 'ywraq_quote_after_list', nl2br( $message_after ), $order_id ) ); ?></p>
	</div>
<?php endif; ?>
