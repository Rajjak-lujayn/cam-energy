<?php
/**
 * HTML Template Email Request a Quote
 *
 * @package YITH\RequestAQuote
 * @since   1.1.6
 * @version 4.0.0
 * @author  YITH <plugins@yithemes.com>
 *
 * @var $email_heading array
 * @var $email_description string
 * @var $email string
 * @var $order WC_Order
 * @var $email_title string
 * @var $reason string
 * @var $status string
 */

$order_id     = $order->get_id();
$quote_number = apply_filters( 'ywraq_quote_number', $order_id );

do_action( 'woocommerce_email_header', $email_heading, $email );

echo wp_kses_post( nl2br( $email_description ) );
?>


<?php if ( 'accepted' === $status ) : ?>
	<p>
	<?php
		// translators: number of quote.
		printf( esc_html__( 'The Proposal # %s has been accepted', 'yith-woocommerce-request-a-quote' ), esc_html( $quote_number ) );
	?>
		</p>
<?php else : ?>
	<p>
	<?php
		// translators: number of quote.
		printf( esc_html__( 'The Proposal # %s has been rejected.', 'yith-woocommerce-request-a-quote' ), esc_html( $quote_number ) );
	?>
		</p>
	<?php echo '"' . wp_kses_post( stripcslashes( $reason ) ) . '"'; ?>
<?php endif ?>
	<p></p>
	<p><?php printf( '%1$s <a href="%2$s">#%3$s</a>', esc_html( __( 'You can see details here:', 'yith-woocommerce-request-a-quote' ) ), esc_url( admin_url( 'post.php?post=' . $order_id . '&action=edit' ) ), esc_html( $quote_number ) ); ?></p>
<?php
do_action( 'woocommerce_email_footer', $email );
?>
