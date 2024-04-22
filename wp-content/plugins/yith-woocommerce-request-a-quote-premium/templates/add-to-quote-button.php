<?php
/**
 * Add to Quote button template
 *
 * @package YITH\RequestAQuote
 * @since   1.0.0
 * @version 2.2.7
 * @author  YITH <plugins@yithemes.com>
 *
 * @var int    $product_id
 * @var string $wpnonce
 * @var string $label
 * @var $class
 */

$id = ''; //phpcs:ignore
$product_id = apply_filters( 'wpml_object_id', $product_id, 'product', true );
if ( isset( $colors ) ) {

	$css_class = str_replace( ' ', '.', $class );

	$style = "<style>#p-{$product_id}.{$css_class}{background-color: {$colors['bg_color']}!important;
    color: {$colors['color']}!important;}

     #p-{$product_id}.{$css_class}:hover{ background-color: {$colors['bg_color_hover']}!important;
    color: {$colors['color_hover']}!important; }</style>";

	echo $style; // phpcs:ignore

	$id = 'id=p-' . $product_id; //phpcs:ignore
}
?>
<a href="#" class="<?php echo esc_attr( $class ); ?>" data-product_id="<?php echo esc_attr( $product_id ); ?>" data-wp_nonce="<?php echo esc_attr( $wpnonce ); ?>" <?php echo esc_attr( $id ); ?>><?php if ( $icon ) : ?><i class="ywraq-quote-icon-icon_quote"></i><?php else : ?><?php echo esc_html( $label ); ?><?php endif; ?></a>