<?php
/**
 * HTML Template Quote table
 *
 * @package YITH\RequestAQuote
 * @since   1.0.0
 * @version 2.2.7
 * @author  YITH <plugins@yithemes.com>
 *
 * @var WC_Order $order
 */

$border   = true;
$order_id = $order->get_id();

if ( function_exists( 'icl_get_languages' ) ) {
	global $sitepress;
	$lang = $order->get_meta( 'wpml_language' );
	YITH_Request_Quote_Premium()->change_pdf_language( $lang );
}
add_filter( 'woocommerce_is_attribute_in_product_name', '__return_false' );

?>

<?php
$after_list = $order->get_meta( '_ywcm_request_response' );
if ( '' !== $after_list ) :
	?>
	<div class="after-list">
		<p><?php echo wp_kses_post( apply_filters( 'ywraq_quote_before_list', nl2br( $after_list ), $order_id ) ); ?></p>
	</div>
<?php endif; ?>

<?php do_action( 'yith_ywraq_email_before_raq_table', $order ); ?>

<?php
$columns = get_option( 'ywraq_pdf_columns', 'all' );
/* be sure it is an array */
if ( ! is_array( $columns ) ) {
	$columns = array( $columns );
}
$colspan = 0;

?>
<div class="table-wrapper">
	<div class="mark"></div>
	<table class="quote-table" cellspacing="0" cellpadding="6" style="width: 100%;" border="0">
		<thead>
		<tr>
			<?php
			if ( in_array( 'all', $columns, true ) || in_array( 'thumbnail', $columns, true ) || get_option( 'ywraq_show_preview' ) === 'yes' ) {
				++$colspan;
			}
			?>
			<?php if ( in_array( 'all', $columns, true ) || in_array( 'product_name', $columns, true ) ) : ?>
				<?php $colspan_title = ( in_array( 'all', $columns, true ) || in_array( 'thumbnail', $columns, true ) ) ? '2' : '1'; ?>
				<th scope="col" colspan="<?php echo esc_attr( $colspan_title ); ?>" style="text-align:left; border-bottom: 1px solid #eee;">
					<?php esc_html_e( 'Product', 'yith-woocommerce-request-a-quote' ); ?>
					<?php ++$colspan; ?>
				</th>
			<?php endif ?>
			<?php if ( in_array( 'all', $columns, true ) || in_array( 'unit_price', $columns, true ) ) : ?>
				<th scope="col" style="text-align:right; border-bottom: 1px solid #eee;">
					<?php esc_html_e( 'Unit Price', 'yith-woocommerce-request-a-quote' ); ?>
					<?php ++$colspan; ?>
				</th>
			<?php endif ?>
			<?php if ( in_array( 'all', $columns, true ) || in_array( 'quantity', $columns, true ) ) : ?>
				<th scope="col" style="text-align:right; border-bottom: 1px solid #eee;">
					<?php esc_html_e( 'Quantity', 'yith-woocommerce-request-a-quote' ); ?>
					<?php ++$colspan; ?>
				</th>
			<?php endif ?>
			<?php if ( in_array( 'all', $columns, true ) || in_array( 'product_subtotal', $columns, true ) ) : ?>
				<th scope="col" class="last-col" style="text-align:right; border-bottom: 1px solid #eee;">
					<?php esc_html_e( 'Subtotal', 'yith-woocommerce-request-a-quote' ); ?>
					<?php ++$colspan; ?>
				</th>
			<?php endif ?>
		</tr>
		</thead>
		<tbody>
		<?php
		$items    = $order->get_items();
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
					$sku_label = apply_filters( 'ywraq_sku_label', __( ' SKU:', 'yith-woocommerce-request-a-quote' ) );
					$sku       = sprintf( '<br><small><strong>%s</strong> %s</small>', $sku_label, $_product->get_sku() );
					$title .=  apply_filters( 'ywraq_sku_label_html', $sku, $_product ); //phpcs:ignore
				}

				$subtotal   = wc_price( $item['line_total'], array( 'currency' => $currency ) );
				$unit_price = wc_price( $item['line_total'] / $item['qty'], array( 'currency' => $currency ) );

				if ( get_option( 'ywraq_show_old_price' ) === 'yes' ) {
					$subtotal   = ( $item['line_subtotal'] !== $item['line_total'] ) ? '<small><del>' . wc_price( $item['line_subtotal'], array( 'currency' => $currency ) ) . '</del></small> ' . wc_price( $item['line_total'], array( 'currency' => $currency ) ) : wc_price( $item['line_subtotal'], array( 'currency' => $currency ) );
					$unit_price = ( $item['line_subtotal'] !== $item['line_total'] ) ? '<small><del>' . wc_price( $item['line_subtotal'] / $item['qty'], array( 'currency' => $currency ) ) . '</del></small> ' . wc_price( $item['line_total'] / $item['qty'] ) : wc_price( $item['line_subtotal'] / $item['qty'], array( 'currency' => $currency ) );
				}

				?>
				<tr>
					<?php if ( in_array( 'all', $columns, true ) || in_array( 'thumbnail', $columns, true ) || get_option( 'ywraq_show_preview' ) === 'yes' ) : ?>
						<td scope="col" style="text-align:center;">
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
								printf( '<a href="%s">%s</a>', esc_url( $_product->get_permalink() ), $thumbnail ); //phpcs:ignore
							}
							?>
						</td>
					<?php endif ?>

					<?php if ( in_array( 'all', $columns, true ) || in_array( 'product_name', $columns, true ) ) : ?>
						<td scope="col" style="text-align:left;">
							<?php echo wp_kses_post( $title );  ?>
							<small>
								<?php

									$display = wc_display_item_meta(
										$item,
										array(
											'before'       => '<div class="wc-item-meta"><span>',
											'after'        => '</span></div>',
											'separator'    => '<br>',
											'echo'         => false,
											'autop'        => false,
											'label_before' => '<strong class="wc-item-meta-label">',
											'label_after'  => ':</strong> ',
										)
									);
									$display = str_replace( '<p', '<span', $display );
									$display = str_replace( '</p>', '</span>', $display );
									echo wp_kses_post( $display );

								?>
							</small>
						</td>
					<?php endif ?>
					<?php if ( in_array( 'all', $columns, true ) || in_array( 'unit_price', $columns, true ) ) : ?>
						<td scope="col" style="text-align:right;"><?php echo wp_kses_post( $unit_price ); ?></td>
					<?php endif ?>
					<?php if ( in_array( 'all', $columns, true ) || in_array( 'quantity', $columns, true ) ) : ?>
						<td scope="col" style="text-align:right;"><?php echo esc_html( $item['qty'] ); ?></td>
					<?php endif ?>
					<?php if ( in_array( 'all', $columns, true ) || in_array( 'product_subtotal', $columns, true ) ) : ?>
						<td scope="col" class="last-col" style="text-align:right;">
							<?php echo wp_kses_post( apply_filters( 'ywraq_quote_subtotal_item', ywraq_formatted_line_total( $order, $item ), $item['line_total'], $_product, $item ) ); ?>
						</td>
					<?php endif ?>
				</tr>

				<?php
			endforeach;
			?>

			<?php
			if ( 'no' === get_option( 'ywraq_pdf_hide_total_row', 'no' ) ) {
				foreach ( $order->get_order_item_totals() as $key => $total ) {
					if ( ! apply_filters( 'ywraq_hide_payment_method_pdf', false ) || 'payment_method' !== $key ) :
						?>
						<tr>
							<th scope="col" colspan="<?php echo esc_attr( ( $colspan > 0 ) ? $colspan - 1 : 0 ); ?>"
								style="text-align:right;"><?php echo esc_html( $total['label'] ); ?></th>
							<td scope="col" class="last-col"
								style="text-align:right;"><?php echo wp_kses_post( $total['value'] ); ?></td>
						</tr>

					<?php endif; ?>
					<?php
				}
			}
			?>
		<?php endif; ?>


		</tbody>
	</table>
</div>
<?php if ( get_option( 'ywraq_pdf_link' ) === 'yes' ) : ?>
	<div>
		<table class="ywraq-buttons">
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

<?php $after_list = apply_filters( 'ywraq_quote_after_list', $order->get_meta( '_ywraq_request_response_after' ), $order_id ); ?>

<?php if ( '' !== $after_list ) : ?>
	<div class="after-list">
		<p><?php echo wp_kses_post( nl2br( $after_list ) ); ?></p>
	</div>
<?php endif; ?>
