<?php
/**
 * Table view to Request A Quote in the widget
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/widgets/quote-list.php.
 *
 * HOWEVER, on occasion YITH will need to update template files and you
 * will need to copy the new files to your theme to maintain compatibility.
 *
 * @since   1.0.0
 * @author  YITH <plugins@yithemes.com>
 * @package YITH\RequestAQuote
 * @version 4.0.0
 *
 * @var array  $raq_content
 * @var string $title
 * @var bool   $show_thumbnail
 * @var bool   $show_price
 * @var bool   $show_quantity
 * @var bool   $show_variations
 * @var bool   $show_title_inside
 * @var bool   $open_quote_page
 * @var string $item_name
 * @var string $item_plural_name
 * @var string $button_label
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

$num_items         = apply_filters( 'ywraq_miniquote_item_number', YITH_Request_Quote()->get_raq_item_number() );
$tax_display_list  = apply_filters( 'ywraq_tax_display_list', get_option( 'woocommerce_tax_display_cart' ) );
$show_thumbnail    = ( 'true' == $show_thumbnail || 1 == $show_thumbnail ) ? 1 : 0; //phpcs:ignore
$show_price        = ( 'true' == $show_price || 1 == $show_price ) ? 1 : 0; //phpcs:ignore
$show_quantity     = ( 'true' == $show_quantity || 1 == $show_quantity ) ? 1 : 0; //phpcs:ignore
$show_variations   = ( 'true' == $show_variations || 1 == $show_variations ) ? 1 : 0; //phpcs:ignore
$show_title_inside = ( 'true' == $show_title_inside || 1 == $show_title_inside ) ? 1 : 0; //phpcs:ignore
$open_quote_page   = ( 'true' == $open_quote_page || 1 == $open_quote_page ) ? 1 : 0; //phpcs:ignore

$data_open_quote_page = $open_quote_page ? 'data-open="yes"' : '';

if ( get_option( 'ywraq_widget_icon', 'default' ) === 'custom' ) {
	$icon = sprintf( '<img class="ywraq-custom-icon" alt="Quote Icon" src="%s">', get_option( 'ywraq_widget_icon_upload' ) );
} else {
	$icon = '<span class="ywraq-quote-icon-icon_quote"></span>';
}
?>

<?php do_action( 'ywraq_before_raq_list_widget' ); ?>
<div class="raq-info <?php echo 0 === $num_items ? 'empty-raq' : ''; ?>">
	<a class="raq_label" <?php echo wp_kses_post( $data_open_quote_page ); ?>
		href="<?php echo esc_url( YITH_Request_Quote()->get_raq_page_url() ); ?>">
		<?php if ( $show_title_inside ) : ?>
			<span class="raq-tip-counter">
					<?php echo wp_kses_post( $icon ); ?><span
						class="raq-items-number"><?php echo esc_html( $num_items ); ?></span>
				</span>
		<?php else : ?>
			<span class="raq-tip-counter">
					<span class="raq-items-number"><?php echo esc_html( $num_items ); ?></span>
															<?php
																echo ' ' . esc_html(
																	_n(
																		$item_name,
																		  $item_plural_name, $num_items, 'yith-woocommerce-request-a-quote' ) ); //phpcs:ignore?>
				</span>
			<span class="handler-label"><?php echo esc_html( $title ); ?></span>
		<?php endif; ?>
	</a>
</div>
<div class="yith-ywraq-list-wrapper">
	<div class="close">X</div>
	<div class="yith-ywraq-list-content">
		<?php if ( $show_title_inside && ! empty( $raq_content ) ) : ?>
			<p class="items-count">
				<?php echo esc_html( $num_items ); ?>
						<?php
							echo esc_html(
								_n(
									$item_name,
									$item_plural_name,
									$num_items,
						'yith-woocommerce-request-a-quote' ) ); //phpcs:ignore ?>
				<?php echo esc_html( $title ); ?>
			</p>
		<?php endif; ?>
		<ul class="yith-ywraq-list">
			<?php if ( ! $num_items ) : ?>
				<li class="no-product">
					<?php
					echo wp_kses_post(
						apply_filters(
							'yith_ywraq_quote_list_empty_message',
							__( 'No products in the list', 'yith-woocommerce-request-a-quote' )
						)
					);
					?>
				</li>
			<?php else : ?>
				<?php
				foreach ( $raq_content as $key => $raq ) :
					$_product = wc_get_product( isset( $raq['variation_id'] ) ? $raq['variation_id'] : $raq['product_id'] );

					if ( ! $_product ) {
						continue;
					}
					/**
					 * APPLY_FILTERS:ywraq_product_image
					 *
					 * Filter the image of product.
					 *
					 * @param string     $product_image Product image.
					 * @param array      $raq           Quote request content.
					 * @param WC_Product $product       Product Object.
					 *
					 * @return array
					 */
					$thumbnail    = ( $show_thumbnail ) ? apply_filters( 'ywraq_product_image', $_product->get_image(), $raq, $_product ) : '';
					$product_name = $_product->get_title();
					?>

					<li class="yith-ywraq-list-item">
						<?php
						echo apply_filters(
							'yith_ywraq_item_remove_link',
							sprintf(
								'<a href="#"  data-remove-item="%s" data-wp_nonce="%s"  data-product_id="%d" class="yith-ywraq-item-remove remove" title="%s">&times;</a>',
								esc_attr( $key ),
								esc_attr( wp_create_nonce( 'remove-request-quote-' . $_product->get_id() ) ),
								esc_attr( $_product->get_id() ),
								esc_attr( __( 'Remove this item', 'yith-woocommerce-request-a-quote' ) )
							),
							$key
						); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
						?>

						<?php
						if ( ! $_product->is_visible() || ! apply_filters(
							'ywraq_list_show_product_permalinks',
							true,
							'widget_quote'
						) ) :
							?>
							<?php echo str_replace( array( 'http:', 'https:' ), '', $thumbnail ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						<?php else : ?>
							<a class="yith-ywraq-list-item-info"
								href="<?php echo esc_url( $_product->get_permalink() ); ?>">
								<?php echo str_replace( array( 'http:', 'https:' ), '', $thumbnail ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
							</a>
						<?php endif; ?>
						<a class="yith-ywraq-list-item-product-info" href="<?php echo esc_url( $_product->get_permalink() ); ?>">
							<?php echo wp_kses_post( $product_name ); ?>

							<?php if ( isset( $raq['variations'] ) && $show_variations ) : ?>
								<small><?php yith_ywraq_get_product_meta( $raq ); ?></small>
							<?php endif ?>

							<?php if ( $show_quantity || $show_price ) : ?>
								<span class="quantity">
								<?php
								echo esc_html( ( $show_quantity ) ? $raq['quantity'] : '' );
								if ( $show_price ) {
									do_action( 'ywraq_quote_adjust_price', $raq, $_product );
									$price = ( 'incl' === $tax_display_list ) ? wc_get_price_including_tax(
										$_product,
										array( 'qty' => $raq['quantity'] )
									) : wc_get_price_excluding_tax(
										$_product,
										array( 'qty' => $raq['quantity'] )
									);
									if ( $price ) {
										$price = isset( WC()->cart ) ? WC()->cart->get_product_price( $_product ) : $_product->get_price_html();
										$price = apply_filters(
											'yith_ywraq_product_price_html',
											$price,
											$_product,
											$raq
										);
									} else {
										$price = wc_price( 0 );
									}

									$x = ( $show_quantity ) ? ' x ' : '';
									echo wp_kses_post(
										apply_filters(
											'yith_ywraq_hide_price_template',
											$x . $price,
											$_product->get_id(),
											$raq
										)
									);
								}
								?>
							</span>
								<?php do_action( 'ywraq_mini_widget_view_item', $raq_content, $key ); ?>
							<?php endif; ?>
						</a>
					</li>
				<?php endforeach ?>

			<?php endif ?>
		</ul>
		<?php if ( ! empty( $raq_content ) ) : ?>
			<a href="<?php echo esc_url( YITH_Request_Quote()->get_raq_page_url() ); ?>" class="button">
				<?php
				echo esc_html(
					apply_filters(
						'yith_ywraq_quote_list_button_label',
						$button_label
					)
				);
				?>
			</a>
		<?php endif; ?>
	</div>
</div>

<?php do_action( 'ywraq_after_raq_list_widget' ); ?>
