<?php
/**
 * Request Quote PDF Header
 *
 * @package YITH\RequestAQuote
 * @since   1.0.0
 * @version 3.0.0
 * @author  YITH <plugins@yithemes.com>
 *
 * @var $order    WC_Order
 * @var $raq_data array
 */

if ( function_exists( 'icl_get_languages' ) ) {
	global $sitepress;
	$lang = $order->get_meta( 'wpml_language' );
	if ( function_exists( 'wc_switch_to_site_locale' ) ) {
		wc_switch_to_site_locale();
	}
	YITH_Request_Quote_Premium()->change_pdf_language( $lang );
}

$order_id = $order->get_id();
$logo_url = get_option( 'ywraq_pdf_logo' );

$logo_attachment_id = apply_filters( 'yith_pdf_logo_id', get_option( 'ywraq_pdf_logo-yith-attachment-id' ) );


if ( ! $logo_attachment_id && $logo_url ) {
	$logo_attachment_id = attachment_url_to_postid( $logo_url );
}

$logo = $logo_attachment_id ? get_attached_file( $logo_attachment_id ) : $logo_url;


$image_type        = wp_check_filetype( $logo );
$mime_type         = array( 'image/jpeg', 'image/png' );
$logo              = apply_filters( 'ywraq_pdf_logo', ( isset( $image_type['type'] ) && in_array( $image_type['type'], $mime_type, true ) ) ? $logo : '' );
$user_name         = $order->get_meta( 'ywraq_customer_name' );
$user_email        = $order->get_meta( 'ywraq_customer_email' ); //phpcs:ignore
$formatted_address = $order->get_formatted_billing_address();

$billing_phone   = $order->get_meta( 'ywraq_billing_phone' );
$billing_name    = $order->get_billing_first_name();
$billing_surname = $order->get_billing_last_name();
$billing_phone   = $order->get_meta( 'ywraq_billing_phone' );
$billing_phone   = empty( $billing_phone ) ? $order->get_billing_phone() : $billing_phone;
$billing_vat     = $order->get_meta( 'ywraq_billing_vat' );


$exdata = $order->get_meta( '_ywcm_request_expire' );

$expiration_data = '';

if ( function_exists( 'wc_format_datetime' ) ) {
	$order_date = wc_format_datetime( $order->get_date_created() );
	if ( ! empty( $exdata ) ) {
		try {
			$exdata = new WC_DateTime( $exdata );
		} catch ( Exception $e ) {
			$exdata = '';
		}
		$expiration_data = wc_format_datetime( $exdata );
	}
} else {
	$date_format     = isset( $raq_data['lang'] ) ? ywraq_get_date_format( $raq_data['lang'] ) : wc_date_format();
	$order_date      = date_i18n( $date_format, strtotime( $order->get_date_created() ) );
	$expiration_data = empty( $exdata ) ? '' : date_i18n( $date_format, strtotime( $exdata ) );
}

?>
<div class="logo">
	<img src="<?php echo apply_filters( 'ywraq_pdf_log_src', $logo ); //phpcs:ignore ?>" style="max-width: 300px;">
</div>
<div class="admin_info right">
	<table cellspacing="15">
		<tr>
			<td valign="top"
				class="small-title"><?php echo esc_html( __( 'From', 'yith-woocommerce-request-a-quote' ) ); ?></td>
			<td valign="top" class="small-info">
				<p>
					<?php
					if ( 'yes' === get_option( 'ywraq_show_author_quote' ) ) :
						/**
						 * Current customer.
						 *
						 * @var $user WC_Customer
						 */
						$user = new WC_Customer( $order->get_meta( '_ywraq_author' ) );
						if ( $user ) :
							$name  = trim( $user->get_billing_first_name() . ' ' . $user->get_billing_last_name() );
							$email = trim( $user->get_billing_email() );
							$phone = trim( $user->get_billing_phone() );

							$from  = ! empty( $name ) ? $name . '<br>' : '';
							$from .= ! empty( $email ) ? $email . '<br>' : '';
							$from .= ! empty( $phone ) ? $phone . '<br>' : '';

							?>
							<?php echo ( trim( $from ) !== '' ) ? wp_kses_post( $from ) . '<br>' : ''; ?>
						<?php endif ?>
					<?php endif ?>

					<?php echo wp_kses_post( apply_filters( 'ywraq_pdf_info', nl2br( get_option( 'ywraq_pdf_info' ) ), $order ) ); ?>
				</p>
			</td>
		</tr>
		<tr>
			<td valign="top"
				class="small-title"><?php echo esc_html( __( 'Customer', 'yith-woocommerce-request-a-quote' ) ); ?></td>
			<td valign="top" class="small-info" style="word-wrap: break-word; word-break: break-all;">
				<p>
					<?php if ( empty( $billing_name ) && empty( $billing_surname ) ) : ?>
						<strong><?php echo esc_html( $user_name ); ?></strong>
						<br>
						<?php
					endif;

					echo wp_kses_post( $formatted_address ) . '<br>';
					echo esc_html( $user_email ) . '<br>';

					if ( '' !== $billing_phone ) {
						echo esc_html( $billing_phone ) . '<br>';
					}

					if ( '' !== $billing_vat ) {
						echo esc_html( $billing_vat ) . '<br>';
					}
					?>
				</p>
			</td>
		</tr>

		<?php if ( '' !== $order_date ) : ?>
			<tr>
				<td valign="top"
					class="small-title"><?php echo esc_html( __( 'Date created', 'yith-woocommerce-request-a-quote' ) ); ?></td>
				<td valign="top" class="small-info">
					<p><?php echo esc_html( $order_date ); ?></p>
				</td>
			</tr>
		<?php endif ?>

		<?php if ( '' !== $expiration_data ) : ?>
			<tr>
				<td valign="top"
					class="small-title"><?php echo esc_html( __( 'Expiration date', 'yith-woocommerce-request-a-quote' ) ); ?></td>
				<td valign="top" class="small-info">
					<p><strong><?php echo esc_html( $expiration_data ); ?></strong></p>
				</td>
			</tr>
		<?php endif ?>
	</table>
</div>
<div class="clear"></div>
<div class="quote-title">
	<h2>
	<?php
		// translators: Quote number.
		printf( esc_html( __( 'Quote #%s', 'yith-woocommerce-request-a-quote' ) ), esc_html( apply_filters( 'ywraq_quote_number', $order_id ) ) );
	?>
		</h2>
</div>
