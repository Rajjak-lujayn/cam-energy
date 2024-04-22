<?php
/**
 * Request A Quote pages template; load template parts
 *
 * @package YITH\RequestAQuote
 * @since   1.0.0
 * @version 2.2.7
 * @author  YITH <plugins@yithemes.com>
 *
 * @var $template_part string
 * @var $raq_content
 * @var $args
 */

global $wpdb, $woocommerce;

function_exists( 'wc_nocache_headers' ) && wc_nocache_headers();

$quote_wrapper_class = get_option( 'ywraq_page_list_layout_template', '' );
$show_form           = ( 'yes' === $args['show_form'] ) && ( 'yes' === get_option( 'ywraq_show_form_with_empty_list', 'no' ) || count( $raq_content ) > 0 );
$main_wrapper_class  = count( $raq_content ) === 0 ? 'ywraq-empty' : '';
$main_wrapper_class  .= $show_form ? ' ywraq-with-form' : '';

$shop_url             = ywraq_get_return_to_shop_url();
$label_return_to_shop = apply_filters( 'yith_ywraq_return_to_shop_label', get_option( 'ywraq_return_to_shop_label' ) );

?>

<div class="woocommerce ywraq-wrapper <?php echo esc_attr( $main_wrapper_class ); ?>">
	<?php

	// Denied Access.
	if ( ! apply_filters( 'yith_ywraq_before_print_raq_page', true ) ) :
		?>
		<div
			id="yith-ywraq-message"><?php echo wp_kses_post( apply_filters( 'yith_ywraq_raq_page_deniend_access', __( 'You do not have access to this page', 'yith-woocommerce-request-a-quote' ) ) ); ?></div>
		<?php
		return;
	endif;

	// show notices.
	if ( function_exists( 'wc_print_notices' ) ) {
		if ( defined( 'YWMMQ_PREMIUM' ) && YWMMQ_PREMIUM ) {
			wc_print_notices();
		}

		$args['notices'] = yith_ywraq_check_notices();
		yith_ywraq_print_notices();
	}

	// from checkout.
	$quote_id = isset( $_GET['order'] ) ? wp_unslash( $_GET['order'] ) : false; //phpcs:ignore
	if (  isset( $_REQUEST['hidem'] ) && $quote_id && wc_get_order( $quote_id )) { //phpcs:ignore
		$shortcode = '[yith_ywraq_single_view_quote order_id="' . $quote_id . '"]';
		echo wp_kses_post( is_callable( 'apply_shortcodes' ) ? apply_shortcodes( $shortcode ) : do_shortcode( $shortcode ) );
	}


	if ( ! isset( $_REQUEST['hidem'] ) ) : //phpcs:ignore
		?>
		<div id="yith-ywraq-message"><?php do_action( 'ywraq_raq_message' ); ?></div>
		<?php
		if ( isset( $_GET['raq_nonce'] ) ) { //phpcs:ignore
			return;
		}
		?>

		<?php
		if ( get_option( 'ywraq_show_return_to_shop' ) === 'yes' && count( $raq_content ) !== 0 ) :
			?>
			<div class="yith-ywraq-before-table"><a class="button wc-backward yith-ywraq-before-table-wc-backward"
					href="<?php echo esc_url( apply_filters( 'yith_ywraq_return_to_shop_url', $shop_url ) ); ?>"><?php echo esc_html( $label_return_to_shop ); ?></a>
			</div>
		<?php endif ?>
		<div class="ywraq-form-table-wrapper <?php echo esc_attr( $quote_wrapper_class ); ?>">
			<?php wc_get_template( 'request-quote-' . $template_part . '.php', $args, '', YITH_YWRAQ_TEMPLATE_PATH . '/' ); ?>

			<?php if ( $show_form ) : ?>
				<?php if ( ! defined( 'YITH_YWRAQ_PREMIUM' ) ) : ?>
					<?php wc_get_template( 'request-quote-form.php', $args, '', YITH_YWRAQ_TEMPLATE_PATH . '/' ); ?>
					<?php
				else :
					if ( 'default' !== $args['form_type'] && ! empty( $args['form_title'] ) ) {
						echo '<div id="ywraq-other-form">';
						echo '<h3 class="ywraq-form-title">' . wp_kses_post( $args['form_title'] ) . '</h3>';
					}
					?>
					<?php YITH_Request_Quote_Premium()->get_inquiry_form( $args ); ?>
					<?php
					if ( 'default' !== $args['form_type'] && ! empty( $args['form_title'] ) ) {
						echo '</div>';
					}
					?>
				<?php endif ?>
			<?php endif ?>
		</div>
	<?php endif ?>
</div>
