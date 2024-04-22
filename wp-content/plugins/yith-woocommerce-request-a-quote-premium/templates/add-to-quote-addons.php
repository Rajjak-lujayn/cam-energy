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
 * @var string $variations
 * @var string $label
 * @var bool   $exists
 */

$data_variations = ( isset( $variations ) && ! empty( $variations ) ) ? ' data-variation="' . $variations . '" ' : '';

?>

<div
	class="yith-ywraq-add-to-quote add-to-quote-addons-<?php echo esc_attr( $product_id ); ?>" <?php echo esc_attr( $data_variations ); ?>>
	<a class="add-request-quote-button-addons button" style="display:<?php echo ( $exists ) ? 'none' : 'block'; ?>" href="<?php echo esc_url( get_the_permalink( $product_id ) ); ?>">
		<?php echo esc_html( $label ); ?>
	</a>
</div>

<div class="clear"></div>
