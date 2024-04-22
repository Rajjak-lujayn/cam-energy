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
 */

/**
 * HTML Template Email Request a Quote
 *
 * @since   1.0.0
 * @author  YITH <plugins@yithemes.com>
 *
 * @package YITH\RequestAQuote
 * @version 2.2.7
 * @var $raq_data          array
 * @var $email_heading     array
 * @var $email             string
 * @var $email_description string
 * @var $sent_to_admin     bool
 * @var $plain_text        string
 */
$mail_options      = get_option( 'woocommerce_ywraq_email_settings' );
$order_id          = $raq_data['order_id'];
$order = wc_get_order($order_id); //phpcs:ignore
$customer          = $order instanceof WC_Order ? $order->get_customer_id() : 0;
$page_detail_admin = false;
$quote_number      = apply_filters( 'ywraq_quote_number', $raq_data['order_id'] );

do_action( 'woocommerce_email_header', $email_heading, $email );

?>
<style>
	.customer-info div{
		line-height: 2em;
	}
	h2{
		margin-bottom: 10px;
	}
	h2.quote-title{
		margin-bottom: 25px;
	}
	.td{
		border: 1px solid #eee;
	}
	.column-number{
		text-align: right;
	}
	.column-quantity{
		text-align: center;
	}
	.thumb-wrapper{
		display: table-cell;
		margin-right:15px;
	}
	.product-name-wrapper{
		display: table-cell;
		vertical-align: middle;
	}
	.table-wrapper {
		margin: 30px 0;
	}
</style>

<p><?php echo wp_kses_post( nl2br( $email_description ) ); ?></p>

<div class="table-wrapper">
	<?php
	wc_get_template(
		'emails/request-quote-table.php',
		array(
			'raq_data'   => $raq_data,
			'email_type' => $email->id,
		),
		'',
		YITH_YWRAQ_TEMPLATE_PATH . '/'
	);
	?>
</div>

<?php if ( ( 0 !== (int) $customer && ( get_option( 'ywraq_enable_order_creation', 'yes' ) === 'yes' ) ) ) : ?>
	<p style="margin-bottom:30px">
		<?php
		$show_details = sprintf( '<a href="%s">#%s</a>', esc_url( YITH_YWRAQ_Order_Request()->get_view_order_url( $order_id ) ), esc_html( $quote_number ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo esc_html( __( 'You can see details here:', 'yith-woocommerce-request-a-quote' ) ) . ' ' . wp_kses_post( apply_filters( 'ywraq_request_quote_email_show_detail', $show_details, $order_id ) )
		?>
	</p>
<?php endif ?>


<?php if ( ! empty( $raq_data['user_message'] ) ) : ?>
	<h2><?php esc_html_e( 'Your message', 'yith-woocommerce-request-a-quote' ); ?></h2>
	<p style="margin-bottom:30px"><?php echo wp_kses_post( stripslashes( $raq_data['user_message'] ) ); ?></p>
<?php endif ?>

<h2><?php esc_html_e( 'Your details', 'yith-woocommerce-request-a-quote' ); ?></h2>
<div class="customer-info">
	<?php
	if ( ! isset( $raq_data['from_checkout'] ) ) {
		$country_code = isset( $raq_data['user_country'] ) ? $raq_data['user_country'] : '';

		foreach ( $raq_data as $key => $field ) {

			if ( ! isset( $field['id'] ) ) {
				continue;
			};

			$avoid_key = array(
				'customer_id',
				'raq_content',
				'user_country',
				'user_message',
				'user_email',
				'user_name',
				'order_id',
				'lang',
				'message',
				'user_additional_field',
				'user_additional_field_2',
				'user_additional_field_3',
			);

			if ( in_array( $key, $avoid_key, true ) ) {
				continue;
			}

			$field_type = strtolower( $field['type'] );

			switch ( $field_type ) {

				case 'ywraq_heading':
					?>
					<h3><?php echo esc_html( $field['label'] ); ?></h3>
					<?php
					break;

				case 'email':
					?>
					<div><strong><?php echo esc_html( $field['label'] ); ?></strong>: <a
								href="mailto:<?php echo esc_attr( ( $field['value'] ) ); ?>"><?php echo esc_html( $field['value'] ); ?></a>
					</div>
					<?php
					break;

				case 'country':
					$countries = WC()->countries->get_countries();
					?>
					<div>
						<strong><?php echo esc_html( $field['label'] ); ?></strong>: <?php echo esc_html( $countries[ $field['value'] ] ); ?>
					</div>
					<?php
					break;

				case 'state':
					$states = WC()->countries->get_states( $country_code );
					$state  = '';
					if ( '' !== $field['value'] ) {
						if ( empty( $states ) ) {
							$state = $field['value'];
						} else {
							$state = isset( $states[ $field['value'] ] ) ? $states[ $field['value'] ] : '';
						}
					}

					if ( '' !== $state ) {
						?>
						<div><strong><?php echo wp_kses_post( $field['label'] ); ?></strong>
							: <?php echo wp_kses_post( ( '' === $state ? $field['value'] : $state ) ); ?></div>
						<?php
					}
					break;

				case 'ywraq_multiselect':
					?>
					<div>
						<strong><?php echo wp_kses_post( $field['label'] ); ?></strong>: <?php echo is_array( $field['value'] ) && ! empty( $field['value'] ) ? wp_kses_post( implode( ', ', $field['value'] ) ) : ''; ?>
					</div>
					<?php
					break;

				case 'checkbox':
					$value = ( 1 === intval( $field['value'] ) ) ? apply_filters( 'yith_wraq_checkbox_yes_text', 'Yes' ) : apply_filters( 'yith_wraq_checkbox_no_text', 'No' );
					?>
					<div><strong><?php echo esc_html( $field['label'] ); ?></strong>: <?php echo wp_kses_post( $value ); ?>
					</div>
					<?php
					break;


				default:
					if ( 'ywraq_upload' !== $field_type ) {
						?>
						<div><strong><?php echo esc_html( $field['label'] ); ?></strong>
							: <?php echo wp_kses_post( $field['value'] ); ?></div>
						<?php
					}
			}
		}
	} else {
		do_action( 'woocommerce_email_customer_details', $order, $sent_to_admin, $plain_text, $email );
	}
	?>

	<?php if ( ! empty( $raq_data['user_additional_field'] ) || ! empty( $raq_data['user_additional_field_2'] ) || ! empty( $raq_data['user_additional_field_3'] ) ) : ?>
		<h2><?php esc_html_e( 'Your additional fields', 'yith-woocommerce-request-a-quote' ); ?></h2>

		<?php if ( ! empty( $raq_data['user_additional_field'] ) ) : ?>
			<div><?php printf( '<strong>%s</strong>: %s', esc_html( get_option( 'ywraq_additional_text_field_label' ) ), esc_html( $raq_data['user_additional_field'] ) ); ?></div>
		<?php endif ?>

		<?php if ( ! empty( $raq_data['user_additional_field_2'] ) ) : ?>
			<div><?php printf( '<strong>%s</strong>: %s', esc_html( get_option( 'ywraq_additional_text_field_label_2' ) ), esc_html( $raq_data['user_additional_field_2'] ) ); ?></div>
		<?php endif ?>

		<?php if ( ! empty( $raq_data['user_additional_field_3'] ) ) : ?>
			<div><?php printf( '<strong>%s</strong>: %s', esc_html( get_option( 'ywraq_additional_text_field_label_3' ) ), esc_html( $raq_data['user_additional_field_3'] ) ); ?></div>
		<?php endif ?>

	<?php endif ?>
</div>
<?php

do_action( 'woocommerce_email_footer', $email );

?>
