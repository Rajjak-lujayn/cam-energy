<?php
/**
 * HTML Template Email
 *
 * @since   1.0.0
 * @author  YITH <plugins@yithemes.com>
 *
 * @version 3.10.0
 * @package YITH\RequestAQuote
 * @var $raq_data array
 */

$show_price        = true;
$show_total_column = ywraq_show_element_on_list( 'line_total' );
$email_type        = isset( $email_type ) ? $email_type : 'ywraq_email';
$total             = 0;
$total_tax         = 0;

$quote_number     = apply_filters( 'ywraq_quote_number', $raq_data['order_id'] );
$tax_display_list = apply_filters( 'ywraq_tax_display_list', get_option( 'woocommerce_tax_display_cart' ) );
$text_align       = is_rtl() ? 'right' : 'left';
$in_email         = true;

$show_image = ( get_option( 'ywraq_show_preview' ) === 'yes' ) || in_array( 'images', get_option( 'ywraq_product_table_show' ), true );

$show_permalinks = apply_filters( 'ywraq_list_show_product_permalinks', true, 'email_request_quote_table' );
$colspan         = 1;

if ( 'ywraq_email_customer' === $email_type ) {
	$show_price        = ! ( 'yes' === get_option(
		'ywraq_quote_my_account_hide_price_new_quote',
		'yes'
	) || 'yes' === get_option( 'ywraq_hide_price' ) );
	$show_total_column = ! $show_price ? false : $show_total_column;
}

if ( ! empty( $raq_data['raq_content'] ) ) : ?>

	<h2 class="quote-title">
		<?php
		if ( get_option( 'ywraq_enable_order_creation', 'yes' ) === 'yes' && ! empty( $quote_number ) ) {
			printf(
				'%s #%s',
				esc_html( __( 'Request', 'yith-woocommerce-request-a-quote' ) ),
				esc_html( $quote_number )
			);
		} else {
			esc_html_e( 'Quote request', 'yith-woocommerce-request-a-quote' );
		}
		?>
	</h2>

	<?php do_action( 'yith_ywraq_email_before_raq_table', $raq_data, $in_email ); ?>

	<table cellspacing="0" cellpadding="6" style="width: 100%; border: 1px solid #eee;border-collapse: collapse;margin-bottom:25px">
		<thead>
		<tr>
			<th scope="col" class="td"
				style="text-align:<?php echo esc_attr( $text_align ); ?>;">
				<?php
				esc_html_e(
					'Product',
					'yith-woocommerce-request-a-quote'
				);
				?>
			</th>
			<th scope="col" class="column-quantity td">
				<?php
				esc_html_e(
					'Qty',
					'yith-woocommerce-request-a-quote'
				);
				?>
			</th>
			<?php if ( $show_total_column ) : ?>
				<th scope="col" class="column-number td">
					<?php
					esc_html_e(
						'Subtotal',
						'yith-woocommerce-request-a-quote'
					);
					?>
				</th>
			<?php endif ?>
		</tr>
		</thead>
		<tbody>
		<?php

		foreach ( $raq_data['raq_content'] as $key => $item ) :

			$_product_id = isset( $item['variation_id'] ) && $item['variation_id'] ? $item['variation_id'] : $item['product_id'];
			$_product    = wc_get_product( $_product_id );

			if ( ! $_product ) {
				continue;
			}

            $title = $_product->get_title(); //phpcs:ignore


			do_action( 'ywraq_before_request_quote_view_item', $raq_data, $key );
			$product_name_style = 'text-align:' . $text_align . ';';
			?>

			<tr>
				<td scope="col" class="td" style="<?php echo esc_attr( $product_name_style ); ?>">
					<?php
					if ( $show_image ) :

						$dimensions = apply_filters( 'ywraq_email_image_product_size', array( 80, 80 ), $_product );
						/**
						 * APPLY_FILTERS:ywraq_product_image
						 *
						 * Filter the image of product.
						 *
						 * @param   string     $product_image  Product image.
						 * @param   array      $raq            Quote request content.
						 * @param   WC_Product $_product       WC_Product object.
						 *
						 * @return array
						 */
						$src = apply_filters( 'ywraq_product_image', ( $_product->get_image_id() ) ? current(
							wp_get_attachment_image_src(
								$_product->get_image_id(),
								$dimensions
							)
						) : wc_placeholder_img_src(), false, $_product );
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

							<?php
							if ( $show_permalinks ) :
								?>
					</a>
								<?php
					else :
						?>
				<?php endif ?>
				<?php endif; ?>

				<div class="product-name-wrapper">
					<?php if ( $show_permalinks ) : ?>
						<a href="<?php echo esc_url( $_product->get_permalink() ); ?>"><?php echo wp_kses_post( $title ); ?></a>
					<?php else : ?>
						<?php echo wp_kses_post( $title ); ?>
					<?php endif ?>

					<?php
					if ( $_product->get_sku() !== '' && ywraq_show_element_on_list( 'sku' ) ) {
						$sku = '<br/><small>' . apply_filters( 'ywraq_sku_label', __( ' SKU:', 'yith-woocommerce-request-a-quote' ) ) . $_product->get_sku() . '</small>';
                        echo wp_kses_post(apply_filters('ywraq_sku_label_html', $sku, $_product)); //phpcs:ignore
					}
					?>


					<?php
					do_action( 'ywraq_request_quote_email_view_item_after_title', $item, $raq_data, $key );
					?>
					<?php if ( isset( $item['variations'] ) || ( ! is_array( $item ) && ( is_array( $item->get_meta( '_ywraq_wc_ywapo' ) ) || ( isset( $item->get_data()['variation_id'] ) && (int) $item->get_data()['variation_id'] > 0 ) ) ) || isset( $item['addons'] ) || isset( $item['yith_wapo_options'] ) ) {  ?>
						<small style="line-height: 1em">
							<?php
							echo yith_ywraq_get_product_meta( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
								$item,
								true,
								$show_price
							);
							?>
						</small>
					<?php } elseif(  !is_array( $item ) && is_array( $item->get_meta( '_ywapo_meta_data' ) ) ) {
						wc_display_item_meta( $item );
					} ?>
				</div>
				</td>
				<td scope="col" class="column-quantity td"><?php echo esc_html( $item['quantity'] ); ?></td>
				<?php if ( $show_total_column ) : ?>
					<td scope="col" class="td column-number">
						<?php
						if ( $show_price ) {
							do_action( 'ywraq_quote_adjust_price', $item, $_product );

							if ( $item instanceof WC_Order_Item_Product ) {
								$price      = wc_price( $item->get_total() );
								$total     += floatval( $item->get_total() );
								$total_tax += floatval( $item->get_total_tax() );
							} else {
								$price = ( 'incl' === $tax_display_list ) ? wc_get_price_including_tax(
									$_product,
									array( 'qty' => $item['quantity'] )
								) : wc_get_price_excluding_tax(
									$_product,
									array( 'qty' => $item['quantity'] )
								);

								if ( $price ) {
									if ( $_product->is_type( 'yith-composite' ) && isset( $item['yith_wcp_component_data'] ) ) {
										$component_data                   = $item['yith_wcp_component_data'];
										$composite_stored_data            = $_product->getComponentsData();
										$composite_data_price             = 0;
										$composite_data_price_with_tax    = 0;
										$composite_data_price_without_tax = 0;
										foreach ( $composite_stored_data as $wcp_key => $component_item ) {
											if ( isset( $component_data['selection_data'][ $wcp_key ] ) && $component_data['selection_data'][ $wcp_key ] > 0 ) {
												// variation selected.
												if ( isset( $component_data['selection_variation_data'][ $wcp_key ] ) && $component_data['selection_variation_data'][ $wcp_key ] > 0 ) {
													$child_product = wc_get_product( $component_data['selection_variation_data'][ $wcp_key ] );
												} else {
													$child_product = wc_get_product( $component_data['selection_data'][ $wcp_key ] );
												}
												if ( ! $child_product ) {
													continue;
												}
												$composite_data_price             += wc_get_price_to_display(
													$child_product,
													array( 'qty' => $item['quantity'] )
												);
												$composite_data_price_with_tax    += wc_get_price_including_tax(
													$child_product,
													array( 'qty' => $item['quantity'] )
												);
												$composite_data_price_without_tax += wc_get_price_excluding_tax(
													$child_product,
													array( 'qty' => $item['quantity'] )
												);
											}
										}
										$total     += floatval( $price + $composite_data_price );
										$total_tax += floatval( $composite_data_price_with_tax - $composite_data_price_without_tax );
									} else {
										$price_with_tax    = wc_get_price_including_tax(
											$_product,
											array( 'qty' => $item['quantity'] )
										);
										$price_without_tax = wc_get_price_excluding_tax(
											$_product,
											array( 'qty' => $item['quantity'] )
										);
										$total            += floatval( $price );
										$total_tax        += floatval( $price_with_tax - $price_without_tax );
									}
									$price = apply_filters(
										'yith_ywraq_product_price_html',
										WC()->cart->get_product_subtotal( $_product, $item['quantity'] ),
										$_product,
										$item
									);

								} else {
									$price = wc_price( 0 );
								}
							}

							echo wp_kses_post(
								apply_filters(
									'yith_ywraq_hide_price_template',
									$price,
									$_product->get_id(),
									$item
								)
							);
						}
						?>
					</td>
				<?php endif ?>
			</tr>
			<?php
			do_action( 'ywraq_after_request_quote_view_item_on_email', $raq_data['raq_content'], $key );
		endforeach;
		?>

		<?php if ( $show_total_column ) : ?>
			<?php
			if ( $total_tax > 0 && 'incl' !== $tax_display_list && ywraq_show_taxes_on_quote_list() ) :
				$total += $total_tax;
				?>
				<tr class="taxt-total">
					<td colspan="<?php echo esc_attr( $colspan ); ?>" style="text-align:right; border: 1px solid #eee;">
					</td>
					<th><?php echo esc_html( WC()->countries->tax_or_vat() ); ?></th>
					<td class="raq-totals" scope="col"
						style="text-align:<?php echo esc_attr( $text_align ); ?>;border: 1px solid #eee;">
						<?php
						echo wp_kses_post( wc_price( $total_tax ) );
						?>
					</td>
				</tr>
			<?php endif; ?>
			<tr>
				<td colspan="<?php echo esc_attr( $colspan ); ?>"
					style="text-align:right; border: 1px solid #eee;"></td>
				<th style="text-align:<?php echo esc_attr( $text_align ); ?>; border: 1px solid #eee;">
					<?php esc_html_e( 'Total:', 'yith-woocommerce-request-a-quote' ); ?>
				</th>
				<td class="raq-totals" scope="col"
					style="text-align:<?php echo esc_attr( $text_align ); ?>;border: 1px solid #eee;">
					<?php
					echo wp_kses_post( wc_price( $total ) );
					if ( $total_tax > 0 && 'incl' === $tax_display_list && ywraq_show_taxes_on_quote_list() ) {
						echo '<br><small class="includes_tax">' . sprintf(
							'(%s %s %s)',
							esc_html( __( 'includes', 'yith-woocommerce-request-a-quote' ) ),
							wp_kses_post( wc_price( $total_tax ) ),
							wp_kses_post( WC()->countries->tax_or_vat() )
						) . '</small>';
					}
					?>

				</td>
			</tr>
		<?php endif; ?>
		</tbody>
	</table>
<?php endif; ?>

<?php do_action( 'yith_ywraq_email_after_raq_table', $raq_data ); ?>
