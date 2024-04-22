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
 * @since   3.0.0
 * @author  YITH <plugins@yithemes.com>
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) || ! defined( 'YITH_YWRAQ_VERSION' ) ) {
	exit;
}

extract( $field ); //phpcs:ignore
$customer_name  = isset( $content['ywraq_customer_name'] ) ? $content['ywraq_customer_name'] : '';
$customer_email = isset( $content['ywraq_customer_email'] ) ? $content['ywraq_customer_email'] : '';
?>
<div class="customer-request-field-wrapper">
	<div class="yith-pencil customer-request-editor"></div>
	<div class="customer-request-field editable">
		<label for="ywraq_customer_name"><?php esc_html_e( 'Name', 'yith-woocommerce-request-a-quote' ); ?></label>
		<span class="customer-field-value"><?php echo esc_html( $customer_name ); ?></span>
		<input type="text" name="yit_metaboxes[ywraq_customer_name]" id="ywraq_customer_name"
			value="<?php echo esc_attr( $customer_name ); ?>" readonly>
	</div>
	<div class="customer-request-field editable">
		<label for="ywraq_customer_email"><?php esc_html_e( 'Email', 'yith-woocommerce-request-a-quote' ); ?></label>
		<span class="customer-field-value"><?php echo esc_html( $customer_email ); ?></span>
		<input type="text" id="ywraq_customer_email" name="yit_metaboxes[ywraq_customer_email]"
			value="<?php echo esc_attr( $customer_email ); ?>" readonly>
	</div>
	<?php $message = empty( $content['ywraq_customer_message'] ) ? '-' : $content['ywraq_customer_message']; ?>
	<div class="customer-request-field editable">
		<label
			for="ywraq_customer_message"><?php esc_html_e( 'Message', 'yith-woocommerce-request-a-quote' ); ?></label>
		<span class="customer-field-value"><?php echo wp_kses_post( ( $message ) ); ?></span>
		<textarea type="text"
			name="yit_metaboxes[ywraq_customer_message]" readonly><?php echo wp_kses_post( $message ); ?></textarea>
	</div>
	<?php
	unset( $content['ywraq_customer_name'], $content['ywraq_customer_email'], $content['ywraq_customer_message'] );

	$content           = array_filter( $content );

	$field_key_to_hide = apply_filters( 'ywraq_customer_fields_to_hide', array() );
	if ( $content ) :
		foreach ( $content as $label => $value ) :
			if ( ! in_array( $label, $field_key_to_hide, true ) ) {
				$value = empty( $value ) ? '-' : $value;
				?>
			<div class="customer-request-field">
				<label><?php echo wp_kses_post( $label ); ?></label>
				<span class="customer-field-value"><?php echo wp_kses_post( $value ); ?></span>
			</div>
				<?php
			} endforeach;
		?>
	<?php endif; ?>
</div>
