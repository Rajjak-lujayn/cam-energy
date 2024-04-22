<?php
/**
 * Quote footer
 *
 * @package YITH\RequestAQuote
 * @since   1.0.0
 * @version 4.0.0
 * @author  YITH <plugins@yithemes.com>
 *
 * @var $footer string
 * @var $pagination string
 * @var $order_id int
 */

if ( function_exists( 'icl_get_languages' ) ) {
	global $sitepress;
	$lang = get_post_meta( $order_id, 'wpml_language', true );
	YITH_Request_Quote_Premium()->change_pdf_language( $lang );
}
?>

<htmlpagefooter name="footer">
	<div id="document-footer" style="background-color:transparent;font-size:8px;text-align: center">
		<div class="footer-content"><?php echo wp_kses_post( $footer ); ?></div>
		<?php  if ( 'yes' === $pagination ) : ?>
		<div class="page"><?php echo esc_html( __( 'Page', 'yith-woocommerce-request-a-quote' ) ); ?><span class="pagenum">{PAGENO}</span>
		</div>
		<?php endif ?>
	</div>
</htmlpagefooter>
<sethtmlpagefooter name="footer" value="on" page="ALL" />
<?php
if ( function_exists( 'wc_restore_locale' ) ) {
	wc_restore_locale();
}
?>
