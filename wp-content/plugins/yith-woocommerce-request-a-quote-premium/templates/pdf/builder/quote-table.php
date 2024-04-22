<?php
/**
 * HTML Product Table Body
 *
 * @package YITH\RequestAQuote
 * @since   4.0.0
 * @version 4.0.0
 * @author  YITH <plugins@yithemes.com>
 *
 * @var $quote    WC_Order
 * @var $attr     array
 */

$quote_id = $quote->get_id();

if ( function_exists( 'icl_get_languages' ) ) {
	global $sitepress;
	$lang = $quote->get_meta( 'wpml_language' );
	YITH_Request_Quote_Premium()->change_pdf_language( $lang );
}
add_filter( 'woocommerce_is_attribute_in_product_name', '__return_false' );
?>

<?php do_action( 'yith_ywraq_email_before_raq_table', $quote ); ?>



<?php
$items    = $quote->get_items();
$currency = $quote->get_currency();


if ( ! empty( $items ) ) :
	$size = count( $items );
	$i    = 0;
	foreach ( $items as $item ) :
		if ( isset( $item['variation_id'] ) && $item['variation_id'] ) {
			$_product = wc_get_product( $item['variation_id'] );
		} else {
			$_product = wc_get_product( $item['product_id'] );
		}

		if ( ! $_product ) {
			$size --;
			continue;
		}

		$tr_class = ( ++ $i === $size ) ? 'class="last"' : '';
		$title    = $_product->get_title(); //phpcs:ignore
		if ( ! isset( $attr['productSku'] ) || isset( $attr['productSku'] ) && $attr['productSku'] && $_product->get_sku() !== '' ) {
			$sku_label = apply_filters( 'ywraq_sku_label', __( ' SKU:', 'yith-woocommerce-request-a-quote' ) );
			$sku       = sprintf( '<br><small><strong>%s</strong> %s</small>', $sku_label, $_product->get_sku() );
		   $title .=  apply_filters( 'ywraq_sku_label_html', $sku, $_product ); //phpcs:ignore
		}

		$subtotal   = wc_price( $item['line_total'], array( 'currency' => $currency ) );
		$unit_price = wc_price( $item['line_total'] / $item['qty'], array( 'currency' => $currency ) );

		if ( get_option( 'ywraq_show_old_price' ) === 'yes' ) {
			$subtotal   = ( $item['line_subtotal'] !== $item['line_total'] ) ? '<small><del>' . wc_price(
				$item['line_subtotal'],
				array( 'currency' => $currency )
			) . '</del></small> ' . wc_price(
				$item['line_total'],
				array( 'currency' => $currency )
			) : wc_price(
				$item['line_subtotal'],
				array( 'currency' => $currency )
			);
			$unit_price = ( $item['line_subtotal'] !== $item['line_total'] ) ? '<small><del>' . wc_price(
				$item['line_subtotal'] / $item['qty'],
				array( 'currency' => $currency )
			) . '</del></small> ' . wc_price( $item['line_total'] / $item['qty'] ) : wc_price(
				$item['line_subtotal'] / $item['qty'],
				array( 'currency' => $currency )
			);
		}

		$im = false;

		?>
		<tr <?php echo $tr_class; ?>>
			<?php
			if ( ! isset( $attr['thumbnails'] ) || isset( $attr['thumbnails'] ) && $attr['thumbnails'] ) :
				?>
				<td class="thumbnail">
					<?php
					$image_id = $_product->get_image_id();
					if ( $image_id ) {
						$thumbnail_id  = $image_id;
						$thumbnail_url = apply_filters(
							'ywraq_pdf_product_thumbnail',
							get_attached_file( $thumbnail_id ),
							$thumbnail_id
						);
					} else {
						$thumbnail_url = function_exists( 'wc_placeholder_img_src' ) ? wc_placeholder_img_src() : '';
					}
					$thumbnail_url = apply_filters( 'ywraq_product_image', $thumbnail_url, false, $_product );
					$thumbnail = sprintf( '<img src="%s" class="thumbnail-img"/>', $thumbnail_url );

					if ( ! $_product->is_visible() ) {
						echo $thumbnail; //phpcs:ignore
					} else {
						printf(
							'<a href="%s">%s</a>',
							esc_url( $_product->get_permalink() ),
							$thumbnail ); //phpcs:ignore
					}
					?>
				</td>
			<?php endif ?>
			<?php if ( ! isset( $attr['productName'] ) || isset( $attr['productName'] ) && $attr['productName'] ) : ?>
				<td class="product-name">
					<?php
					echo wp_kses_post( $title );
					?>
					<small>
						<?php
						if ( $im ) {
							$im->display();
						} else {
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
						}
						?>
					</small>
				</td>
			<?php endif; ?>

			<?php
			if ( ! isset( $attr['quantity'] ) || isset( $attr['quantity'] ) && $attr['quantity'] ) :
				?>
				<td class="quantity number"><?php echo esc_html( $item['qty'] ); ?></td>
			<?php endif; ?>

			<?php
			if ( ! isset( $attr['unitPrice'] ) || isset( $attr['unitPrice'] ) && $attr['unitPrice'] ) :
				?>
				<td class="product-price number"><?php echo wp_kses_post( $unit_price ); ?></td>
			<?php endif; ?>
			<?php
			if ( ! isset( $attr['productSubtotal'] ) || isset( $attr['productSubtotal'] ) && $attr['productSubtotal'] ) :
				?>
				<td class="subtotal number">
					<?php
					echo wp_kses_post(
						apply_filters(
							'ywraq_quote_subtotal_item',
							ywraq_formatted_line_total( $quote, $item ),
							$item['line_total'],
							$_product,
							$item
						)
					);
					?>
				</td>
			<?php endif; ?>
		</tr>

		<?php
	endforeach;
	?>
<?php endif; ?>

<?php do_action( 'yith_ywraq_email_after_raq_table', $quote ); ?>
