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
 * @var $table
 * @var $get
 * @var $is_blank
 */

/**
 * Admin View: Quote Request List Table
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

$add_quote_url = add_query_arg(
	array(
		'post_type' => 'shop_order',
		'new_quote' => 1,
	),
	admin_url( 'post-new.php' )
);

$export_url = add_query_arg(
	array(
		'action' => 'ywraq_export_quotes'
	),
	admin_url( 'admin.php' )
);

?>
<div class="ywraq-admin-wrap-content yith-plugin-ui--classic-wp-list-style">
	<a class="button-primary ywraq-add-new-quote" href="<?php echo esc_url( $add_quote_url ); ?>"><?php esc_html_e( '+ Add quote', 'yith-woocommerce-request-a-quote' ); ?></a>
	<?php if ( ! $is_blank ) : ?>
		<a class="button-secondary ywraq-export yith-button-ghost" href="<?php echo esc_url( $export_url ); ?>"><?php esc_html_e( 'Export CSV', 'yith-woocommerce-request-a-quote' ); ?></a>
	<?php endif; ?>

	<?php if ( $is_blank ) : ?>
		<div class="ywraq-admin-no-posts">
			<div class="ywraq-admin-no-posts-container">
				<div class="ywraq-admin-no-posts-logo"><img width="80" src="<?php echo esc_url( YITH_YWRAQ_ASSETS_URL . '/images/mini-quote.svg' ); ?>"></div>
				<div class="ywraq-admin-no-posts-text">
					<span>
						<strong><?php echo esc_html_x( 'You don\'t have any request yet.', 'Text showed when the list of quotes is empty.', 'yith-woocommerce-request-a-quote' ); ?></strong>
					</span>
					<p><?php echo esc_html_x( 'But don\'t worry, your request will appear here soon!', 'Text showed when the list of quotes is empty.', 'yith-woocommerce-request-a-quote' ); ?></p>
				</div>
			</div>
		</div>
	<?php else : ?>
		<form method="get" id="ywraq-exclusions">
			<input type="hidden" name="page" value="<?php echo esc_attr( $get['page'] ); ?>">
			<?php
			$table->views();
			$table->prepare_items();
			$table->display();
			?>
		</form>
	<?php endif; ?>
</div>
