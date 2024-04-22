<?php
/**
 * HTML Product Total Table Body
 *
 * @package YITH\RequestAQuote
 * @since   4.0.0
 * @version 4.0.0
 * @author  YITH <plugins@yithemes.com>
 *
 * @var $quote    WC_Order
 */

foreach ( $quote->get_order_item_totals() as $key => $total ) {
	if ( 'order_total' !== $key && ( ! apply_filters( 'ywraq_hide_payment_method_pdf', false ) || 'payment_method' !== $key ) ) : ?>
		<tr class="subtotal-row">
			<td class="subtotal-label"><?php echo esc_html( $total['label'] ); ?></td>
			<td class="subtotal number"><?php echo wp_kses_post( $total['value'] ); ?></td>
		</tr>
		<tr>
	<?php elseif ( 'order_total' === $key ) : ?>
		<tr class="total-row">
			<td class="total-label"><?php echo esc_html( $total['label'] ); ?></td>
			<td style="text-align: right"><?php echo wp_kses_post( str_replace( '<small', '<br/><small', $total['value'] ) ); ?></td>
		</tr>
		<tr>
	<?php endif; ?>
	<?php
}
