<?php
/**
 * HTML Template Email Customer Details section
 *
 * @package YITH\RequestAQuote
 * @since   4.0.0
 * @version 4.0.0
 * @author  YITH <plugins@yithemes.com>
 *
 * @var $order WC_Order
 */

$user_name         = $order->get_meta( 'ywraq_customer_name' );
$user_email = $order->get_meta('ywraq_customer_email'); //phpcs:ignore
$formatted_address = $order->get_formatted_billing_address();
$billing_name      = $order->get_billing_first_name();
$billing_surname   = $order->get_billing_last_name();
$billing_phone     = $order->get_meta( 'ywraq_billing_phone' );
$billing_phone     = empty( $billing_phone ) ? $order->get_billing_phone() : $billing_phone;
$billing_vat       = $order->get_meta( 'ywraq_billing_vat' );
$billing_vat       = empty( $billing_vat ) ? $order->get_meta( '_billing_vat' ) : $billing_vat;

?>

<h2 style="margin-top: 40px"><?php esc_html_e( 'Customer\'s details', 'yith-woocommerce-request-a-quote' ); ?></h2>
<div class="customer-info">
	<?php if ( empty( $billing_name ) && empty( $billing_surname ) ) : ?>
		<div>
			<strong><?php esc_html_e( 'Name:', 'yith-woocommerce-request-a-quote' ); ?></strong> <?php echo esc_html( $user_name ); ?>
		</div>
	<?php endif; ?>

	<div><?php echo wp_kses_post( $formatted_address ); ?></div>

	<div><strong><?php esc_html_e( 'Email:', 'yith-woocommerce-request-a-quote' ); ?></strong>
		<a href="mailto:<?php echo esc_attr( $user_email ); ?>"><?php echo esc_html( $user_email ); ?></a>
	</div>

	<?php if ( '' !== $billing_vat ) : ?>
		<div>
			<strong><?php esc_html_e( 'Billing VAT:', 'yith-woocommerce-request-a-quote' ); ?></strong> <?php echo esc_html( $billing_vat ); ?>
		</div>
	<?php endif; ?>

	<?php if ( '' !== $billing_phone ) : ?>
		<div>
			<strong><?php esc_html_e( 'Billing Phone:', 'yith-woocommerce-request-a-quote' ); ?></strong> <?php echo esc_html( $billing_phone ); ?>
		</div>
		<?php
	endif;

	// Retro compatibility.
	$af1 = $order->get_meta( 'ywraq_customer_additional_field' );
	if ( ! empty( $af1 ) ) {
		printf( ' <div><strong>%s</strong>: %s</div>', esc_html( get_option( 'ywraq_additional_text_field_label' ) ), esc_html( $af1 ) );
	}

	$af2 = $order->get_meta( 'ywraq_customer_additional_field_2' );
	if ( ! empty( $af2 ) ) {
		printf( ' <div><strong>%s</strong>: %s</div>', esc_html( get_option( 'ywraq_additional_text_field_label_2' ) ), esc_html( $af2 ) );
	}

	$af3 = $order->get_meta( 'ywraq_customer_additional_field_3' );
	if ( ! empty( $af3 ) ) {
		printf( ' <div><strong>%s</strong>: %s</div>', esc_html( get_option( 'ywraq_additional_text_field_label_3' ) ), esc_html( $af3 ) );
	}

	$af4 = $order->get_meta( 'ywraq_customer_other_email_content' );
	if ( ! empty( $af4 ) ) {
		printf( '<div>%s</div>', esc_html( $af4 ) );
	}
	?>
