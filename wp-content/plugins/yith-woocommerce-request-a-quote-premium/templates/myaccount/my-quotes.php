<?php
/**
 * This file belongs to the YIT Plugin Framework.
 *
 * This source file is subject to the GNU GENERAL PUBLIC LICENSE (GPL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.gnu.org/licenses/gpl-3.0.txt
 *
 * My Quotes
 *
 * Shows recent orders on the account page
 *
 * @package YITH\RequestAQuote
 * @since   1.0.0
 * @version 2.2.7
 * @author  YITH <plugins@yithemes.com>
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! apply_filters( 'yith_ywraq_before_print_my_account_my_quotes', true ) ) {
	return;
}

do_action( 'yith_ywraq_before_my_quotes' );

$text_align = is_rtl() ? 'right' : 'left';

$customer_quotes = wc_get_orders(
	apply_filters(
		'ywraq_my_account_my_quotes_query',
		yith_plugin_fw_is_wc_custom_orders_table_usage_enabled() ?
			array(
				'limit'     => 15,
				'meta_query' => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
					array(
						'key'   => 'ywraq_raq',
						'value' => 'yes',
					),
				),
				'customer'  => get_current_user_id(),
				'status'    => array_merge( YITH_YWRAQ_Order_Request()->raq_order_status, array_keys( wc_get_order_statuses() ) ),
			) :
			array(
				'limit'     => 15,
				'ywraq_raq' => 'yes',
				'customer'  => get_current_user_id(),
				'status'    => array_merge( YITH_YWRAQ_Order_Request()->raq_order_status, array_keys( wc_get_order_statuses() ) ),
			)
	)
);

$hide_price = get_option( 'ywraq_hide_price', 'no' );


?>

<h2 class="ywraq_my_account_quotes_title"><?php echo wp_kses_post( apply_filters( 'ywraq_my_account_my_quotes_title', __( 'My Quotes', 'yith-woocommerce-request-a-quote' ) ) ); ?></h2>

<?php if ( $customer_quotes ) : ?>

	<table class="shop_table shop_table_responsive my_account_quotes my_account_orders">

		<thead>
		<tr>
			<th class="order-number">
				<span class="nobr"><?php esc_html_e( 'Quote number', 'yith-woocommerce-request-a-quote' ); ?></span>
			</th>
			<th class="order-date"><span
					class="nobr"><?php esc_html_e( 'Date', 'yith-woocommerce-request-a-quote' ); ?></span>
			<th class="total"><span
					class="nobr"><?php esc_html_e( 'Total', 'yith-woocommerce-request-a-quote' ); ?></span>
			<th class="order-status"></th>
			<th class="quote-actions"></th>

		</tr>
		</thead>

		<tbody>
		<?php

		foreach ( $customer_quotes as $customer_order ) {

			$order_id   = $customer_order->get_id();
			$order      = $customer_order; //phpcs:ignore
			$item_count = $order->get_item_count();

			$is_a_quote = true;
			if ( ! $is_a_quote || 0 === $item_count ) {
				continue;
			}


			$order_date = $customer_order->get_date_created();
			$order_lang = $order->get_meta( 'wpml_language' );

			$show_price = 'ywraq-new' !== $order->get_status();
			$action_menu = ywraq_get_actions_menu( $order );
			?>
			<tr class="quotes" data-url="<?php echo esc_url( YITH_YWRAQ_Order_Request()->get_view_order_url( $order_id ) ); ?>">
				<td class="quotes-number"
					data-title="<?php esc_attr_e( 'Order Number', 'yith-woocommerce-request-a-quote' ); ?>">
					<a href="<?php echo esc_url( YITH_YWRAQ_Order_Request()->get_view_order_url( $order_id ) ); ?>">
						#<?php echo esc_html( $order->get_order_number() ); ?>
					</a>
				</td>
				<td class="quotes-date" data-title="<?php esc_attr_e( 'Date', 'yith-woocommerce-request-a-quote' ); ?>">
					<time datetime="<?php echo esc_attr( gmdate( 'Y-m-d', strtotime( $order_date ) ) ); ?>"
						title="<?php echo esc_attr( strtotime( $order_date ) ); ?>"><?php echo wp_kses_post( date_i18n( get_option( 'date_format' ), strtotime( $order_date ) ) ); ?></time>
				</td>
				<td class="quotes-total" data-title="<?php esc_html_e( 'Total', 'yith-woocommerce-request-a-quote' ); ?>">
					<?php
					if ( $show_price ) {
						$totals = $order->get_total(); //phpcs:ignore
						echo wp_kses_post( wc_price( $totals, array( 'currency' => $order->get_currency() ) ) );
					} else {
						echo '-';
					}
					?>
				</td>
				<td class="quotes-status" style="text-align:<?php echo esc_attr( $text_align ); ?>; white-space:nowrap;" data-title="<?php esc_html_e( 'Status', 'yith-woocommerce-request-a-quote' ); ?>">
					<?php ywraq_get_order_status_tag( $order->get_status() ); ?>
				</td>
				<td class="quotes-actions"
					style="text-align:<?php echo esc_attr( $text_align ); ?>; white-space:nowrap;">
					<?php if( ! empty( $action_menu) ): ?>
					<span class="quote-actions__more">
						<span id="" class="quote-actions__action-button">
							<a class="quote-actions-button__link quote-actions__tips" href="#">
										<i class="yith-icon-more ywraq-quote-icon-more_horizontal"></i>
							</a>
					<span class="quote-actions-button__menu">
							<?php foreach ( $action_menu as $menu_item ): ?>
								<a class="quote-actions-button__menu__item" href="<?php echo esc_url( $menu_item['url'] ); ?>"><?php echo esc_html( $menu_item['label'] ); ?></a>
							<?php endforeach; ?>
					</span>
						</span>
					</span>
				<?php endif; ?>
				</td>
			</tr>
			<?php
		}
		?>
		</tbody>
	</table>


<?php else : ?>
	<p class="ywraq-no-quote-in-list"><?php esc_html_e( 'No quote request available.', 'yith-woocommerce-request-a-quote' ); ?></p>
<?php endif; ?>
