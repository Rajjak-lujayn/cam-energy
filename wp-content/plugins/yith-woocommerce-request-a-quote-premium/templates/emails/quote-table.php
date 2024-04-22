<?php
/**
 * HTML Template Email
 *
 * @package YITH\RequestAQuote
 * @since   1.0.0
 * @version 3.10.0
 * @author  YITH <plugins@yithemes.com>
 *
 * @var $order WC_Order
 */

add_filter( 'woocommerce_is_attribute_in_product_name', '__return_false' );

$order_id        = $order->get_id();
$currency        = $order->get_currency();
$text_align      = is_rtl() ? 'right' : 'left';
$colspan         = 2;
$before_list     = $order->get_meta( '_ywraq_request_response_before' );
$show_permalinks = apply_filters( 'ywraq_list_show_product_permalinks', true, 'pdf_quote_table' );
?>
<?php if ( ( ! empty( $before_list ) ) ) : ?>
	<p><?php echo esc_html( apply_filters( 'ywraq_quote_before_list', $before_list, $order_id ) ); ?></p>
<?php endif; ?>

<?php do_action( 'yith_ywraq_email_before_raq_table', $order ); ?>

<table cellspacing="0" cellpadding="6" style="width: 100%; border: 1px solid #eee;border-collapse: collapse;">
	<thead>
	<tr>
		<th scope="col"
			style="text-align:<?php echo esc_attr( $text_align ); ?>; border: 1px solid #eee;">
										<?php
											esc_html_e(
												'Product',
												'yith-woocommerce-request-a-quote'
											);
											?>
				</th>
		<th scope="col" style="text-align:center; border: 1px solid #eee;">
										<?php
											esc_html_e(
												'Qty',
												'yith-woocommerce-request-a-quote'
											);
											?>
				</th>
		<th scope="col"
			style="text-align:right; border: 1px solid #eee;">
										<?php
											esc_html_e(
												'Subtotal',
												'yith-woocommerce-request-a-quote'
											);
											?>
				</th>
	</tr>
	</thead>
	<tbody>
	<?php
	$items = $order->get_items();

	if ( ! empty( $items ) ) :
		$bundles_items_to_hide = array(); /* for compatibility with hide product option in YITH Bundle */
		foreach ( $items as $item ) :
			if ( isset( $item['variation_id'] ) && $item['variation_id'] ) {
				$_product = wc_get_product( $item['variation_id'] );
			} else {
				$_product = wc_get_product( $item['product_id'] );
			}

			$component = $item->get_meta( '_yith_wcp_child_component_data' );

			if ( ! $_product ) {
				continue;
			}

			/* compatibility with hide product option in YITH Bundle starts */
			if ( 'yith_bundle' === $_product->get_type() ) {
				$bundle_meta = $_product->get_meta( '_yith_wcpb_bundle_data' );
				if ( $bundle_meta ) {
					foreach ( $bundle_meta as $bundle_info ) {
						if ( isset( $bundle_info['bp_hide_item'] ) && 'yes' === $bundle_info['bp_hide_item'] ) {
							$bundles_items_to_hide[] = $bundle_info['product_id'];
						}
					}
				}
			}

			if ( in_array( $_product->get_id(), $bundles_items_to_hide ) ) { //phpcs:ignore
				continue;
			}

			/* compatibility with hide product option in YITH Bundle ends */
			$subtotal = wc_price( $item['line_total'], array( 'currency', $currency ) );

			if ( 'yes' === get_option( 'ywraq_show_old_price' ) ) {
				$subtotal = ( $item['line_subtotal'] !== $item['line_total'] ) ? '<small><del>' . wc_price(
					$item['line_subtotal'],
					array( 'currency', $currency )
				) . '</del></small> ' . wc_price(
					$item['line_total'],
					array( 'currency', $currency )
				) : wc_price(
					$item['line_subtotal'],
					array( 'currency', $currency )
				);
			}

			$title = $_product->get_title(); //phpcs:ignore



			// retro compatibility .
			$im = false;

			?>
			<tr>
				<td scope="col" class="td" style="text-align:<?php echo esc_attr( $text_align ); ?>;border: 1px solid #eee;">
					<?php
					if ( get_option( 'ywraq_show_preview' ) === 'yes' ) :
						$dimensions = apply_filters( 'ywraq_email_image_product_size', array( 80, 80 ), $_product );
						$src = apply_filters(
							'ywraq_product_image',
							( $_product->get_image_id() ) ? current(
								wp_get_attachment_image_src(
									$_product->get_image_id(),
									$dimensions
								)
							) : wc_placeholder_img_src(),
							$item,
							true
						);
						?>
						<?php if ( $show_permalinks ) : ?>
					<a href="<?php echo esc_url( $_product->get_permalink() ); ?>" class="thumb-wrapper">
						<?php else : ?>
						<div class="thumb-wrapper">
							<?php endif; ?>
							<img
									src="<?php echo esc_url( $src ); ?>"
									height="<?php echo esc_attr( $dimensions[1] ); ?>"
									width="<?php echo esc_attr( $dimensions[0] ); ?>"/>

							<?php if ( $show_permalinks ) : ?>
					</a> 
								<?php
					else :
						?>
								</td><?php endif ?>
				<?php endif; ?>
				<div class="product-name-wrapper">
				<?php if ( $component ) : ?>
					<?php $component_data = $component['yith_wcp_component_parent_object']->getComponentItemByKey( $component['yith_wcp_component_key'] ); ?>
					<div><strong><?php echo wp_kses_post( $component_data['name'] ); ?></strong></div>
					<div><?php echo wp_kses_post( $_product->get_title() ); ?></div>
				<?php elseif ( $show_permalinks ) : ?>
					<a href="<?php echo esc_url( $_product->get_permalink() ); ?>"><?php echo wp_kses_post( $title ); ?></a>
				<?php else : ?>
					<?php echo wp_kses_post( $title ); ?>
				<?php endif ?>
					<?php
					if ( $_product->get_sku() !== '' && ywraq_show_element_on_list( 'sku' ) ) {
						$sku = '<br/><small>' . apply_filters( 'ywraq_sku_label', __( ' SKU:', 'yith-woocommerce-request-a-quote' ) ) . $_product->get_sku() . '</small>';
                        echo  wp_kses_post( apply_filters( 'ywraq_sku_label_html', $sku, $_product ) ); //phpcs:ignore
					}
					?>

				<small>
					<?php
					if ( $im ) {
						$im->display();
					} else {
						wc_display_item_meta( $item );
					}
					?>
				</small>
				</div></td>
				<td scope="col" class="td" width="15%" style="text-align:center;border: 1px solid #eee;"><?php echo esc_html( $item['qty'] ); ?></td>
				<td scope="col" class="td" width="25%"
					style="text-align:right;border: 1px solid #eee;">
					<?php
					/**
					 * APPLY_FILTERS:ywraq_quote_subtotal_item
					 *
					 * Filter the subtotal of an item
					 *
					 * @param   string  $subtotal  Current item subtotal.
					 * @param   float  $line_total  Item line total.
					 * @param   WC_Product  $_product  Product.
					 * @param   WC_Order_Item_Product  $item  Order item.
					 *
					 * @return string
					 */
					echo apply_filters(
						'ywraq_quote_subtotal_item',
						wp_kses_post( ywraq_formatted_line_total( $order, $item ) ),
						$item['line_total'],
						$_product,
                        $item); // phpcs:ignore

					?>
				</td>

			</tr>

			<?php
		endforeach;
		?>

		<?php
		foreach ( $order->get_order_item_totals() as $key => $total ) {
			if ( ! apply_filters( 'ywraq_hide_payment_method_pdf', false ) || 'payment_method' !== $key ) :
				?>
				<tr>
					<th scope="col" colspan="<?php echo esc_attr( $colspan ); ?>"
						style="text-align:right;border: 1px solid #eee;"><?php echo wp_kses_post( $total['label'] ); ?></th>
					<td scope="col"
						style="text-align:right;border: 1px solid #eee;"><?php echo wp_kses_post( $total['value'] ); ?></td>
				</tr>
			<?php endif; ?>
			<?php
		}
		?>

	<?php endif; ?>
	</tbody>
</table>

<?php

do_action( 'yith_ywraq_email_after_raq_table', $order );
?>
