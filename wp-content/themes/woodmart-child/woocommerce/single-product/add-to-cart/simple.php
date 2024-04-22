<?php
/**
 * Simple product add to cart
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/single-product/add-to-cart/simple.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see https://docs.woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 7.0.1
 */

defined( 'ABSPATH' ) || exit;

global $product;

if ( ! $product->is_purchasable() ) {
	return;
}

echo wc_get_stock_html( $product ); // WPCS: XSS ok.

if ( $product->is_in_stock() ) : ?>

	<?php do_action( 'woocommerce_before_add_to_cart_form' ); ?>

	<form class="cart" action="<?php echo esc_url( apply_filters( 'woocommerce_add_to_cart_form_action', $product->get_permalink() ) ); ?>" method="post" enctype='multipart/form-data'>
		<?php do_action( 'woocommerce_before_add_to_cart_button' ); ?>

		<?php
		do_action( 'woocommerce_before_add_to_cart_quantity' );

		woocommerce_quantity_input(
			array(
				'min_value'   => apply_filters( 'woocommerce_quantity_input_min', $product->get_min_purchase_quantity(), $product ),
				'max_value'   => apply_filters( 'woocommerce_quantity_input_max', $product->get_max_purchase_quantity(), $product ),
				'input_value' => isset( $_POST['quantity'] ) ? wc_stock_amount( wp_unslash( $_POST['quantity'] ) ) : $product->get_min_purchase_quantity(), // WPCS: CSRF ok, input var ok.
			)
		);

		do_action( 'woocommerce_after_add_to_cart_quantity' );
		
		?>
	
		<!-- <button type="submit" name="add-to-cart" value="<?php echo esc_attr( $product->get_id() ); ?>" class="single_add_to_cart_button button alt<?php echo esc_attr( wc_wp_theme_get_element_class_name( 'button' ) ? ' ' . wc_wp_theme_get_element_class_name( 'button' ) : '' ); ?>"><?php echo esc_html( $product->single_add_to_cart_text() ); ?></button> -->
  <?php
    $raq_content = YITH_Request_Quote()->get_raq_return();
    $true = false;
    $style = 'block';
    $style_response = 'none';
    
    foreach($raq_content as $raqcontent){
        if($product->get_id() == $raqcontent['product_id'] ){
            $true = true;
            $style = 'none';
            $style_response = 'block';
        }
    }

   if (!is_product() ) { ?>
     <div class="wd-add-btn wd-add-btn-replace">
		<a href="?add-to-cart=<?php echo $product->get_id(); ?>" data-quantity="1" class="button product_type_simple add_to_cart_button ajax_add_to_cart add-to-cart-loop1" data-product_id="<?php echo $product->get_id(); ?>" data-product_sku="2ESTC-SS" aria-label="Add “2 Electrode Split Test Cell for R&amp;D Coin Cell Battery” to your basket" aria-describedby="" rel="nofollow"><span>Add to basket</span></a>		</div>
    <div class="yith-ywraq-add-to-quote add-to-quote-<?php echo esc_attr( $product->get_id() ); ?>">  
       
    <div class="yith-ywraq-add-to-quote add-to-quote-<?php echo esc_attr( $product->get_id() ); ?>">
    		<div class="yith-ywraq-add-button show" style="display:<?php echo $style; ?>" data-product_id="<?php echo esc_attr( $product->get_id() ); ?>">
    		
    <a href="#" class="add-request-quote-button button" data-product_id="<?php echo esc_attr( $product->get_id() ); ?>" data-wp_nonce="1e18f68b96">
    				Add to quote	</a>
    	</div>
    	<div class="yith_ywraq_add_item_product-response-<?php echo esc_attr( $product->get_id() ); ?> yith_ywraq_add_item_product_message hide hide-when-removed" style="display:<?php echo $style_response; ?>" data-product_id="<?php echo esc_attr( $product->get_id() ); ?>"></div>
    	<div class="yith_ywraq_add_item_response-<?php echo esc_attr( $product->get_id() ); ?> yith_ywraq_add_item_response_message hide hide-when-removed" data-product_id="<?php echo esc_attr( $product->get_id() ); ?>" style="display:<?php echo $style_response; ?>">This product is already in quote request</div>
    	<div class="yith_ywraq_add_item_browse-list-<?php echo esc_attr( $product->get_id() ); ?> yith_ywraq_add_item_browse_message  hide hide-when-removed" style="display:<?php echo $style_response; ?>" data-product_id="<?php echo esc_attr( $product->get_id() ); ?>"><a href="<?php echo site_url(); ?>/request-quote/">Browse the quote list</a></div>
    
    </div>			
 <?php  }
   ?>
		<?php do_action( 'woocommerce_after_add_to_cart_button' ); ?>
	</form>

	<?php do_action( 'woocommerce_after_add_to_cart_form' ); ?>

<?php endif; ?>