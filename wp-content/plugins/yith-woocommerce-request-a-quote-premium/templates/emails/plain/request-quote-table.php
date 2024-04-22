<?php
/**
 * Plain Template Email for Request A Quote List table
 *
 * @package YITH\RequestAQuote
 * @version 1.4.4
 * @since   1.0.0
 * @author  YITH <plugins@yithemes.com>
 */

do_action( 'yith_ywraq_email_before_raq_table', $raq_data );
$show_total_column = ywraq_show_element_on_list( 'line_total' );

echo "****************************************************\n\n";

echo "\n";

if ( ! empty( $raq_data['raq_content'] ) ) :
	foreach ( $raq_data['raq_content'] as $item ) :

		if ( ! empty( $item['variation_id'] ) ) {
			$product = wc_get_product( $item['variation_id'] );
		} else {
			$product = wc_get_product( $item['product_id'] );
		}

		$title = $product->get_title(); //phpcs:ignore

		if ( $product->get_sku() !== '' && ywraq_show_element_on_list( 'sku' ) ) {
			$sku    = apply_filters( 'ywraq_sku_label', __( ' SKU:', 'yith-woocommerce-request-a-quote' ) ) . $product->get_sku();
			$title .= ' ' . esc_html( apply_filters( 'ywraq_sku_label_html', $sku, $product ) ); //phpcs:ignore
		}

		echo esc_html( $title . ' ' . yith_ywraq_get_product_meta( $item, false ) ) . ' | ';
		echo esc_html( $item['quantity'] );
		if ( $show_total_column ) {
			echo ' ' . esc_html( apply_filters( 'yith_ywraq_hide_price_template', WC()->cart->get_product_subtotal( $product, $item['quantity'] ), $product->get_id(), $item ) );
		}

		echo "\n";
	endforeach;
endif;

echo "\n****************************************************\n\n";

do_action( 'yith_ywraq_email_after_raq_table', $raq_data );
