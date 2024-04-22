<?php
/**
 * The Header template for our theme
 */
?><!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<link rel="profile" href="https://gmpg.org/xfn/11">
	<link rel="pingback" href="<?php bloginfo( 'pingback_url' ); ?>">

	<?php wp_head(); ?>
	<!--<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>-->
	<!--<script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>-->
	<script src="https://kit.fontawesome.com/dd0190f9d8.js"></script>

	<!--<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js" integrity="sha512-v2CJ7UaYy4JwqLDIrZUI/4hqeoQieOmAZNXBeQyjo21dadnwR+8ZaIJVT8EE2iyI61OV8e6M8PP2/4hpQINQ/g==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>-->
	<script>
        jQuery(document).ready(function($) {
    
        // Set placeholder for #tnp-1
            jQuery('#tnp-1').attr('placeholder', 'Enter your email');
        
            // Check URL condition
            if (window.location.href.indexOf('nm=confirmed') > -1) {
                console.log('URL contains "nm=confirmed"'); // Debugging statement
        
                // Scroll down to .wpb_wrapper
                jQuery('html, body').animate({
                    scrollTop: jQuery('.footer-wrapper').offset().top
                }, 'fast'); // Adjust animation speed if necessary
            }
        });
    </script>
    <?php if ( is_checkout()) { ?>

	<script>
        jQuery(document).ready(function () {
    
			jQuery('.block_billing_address').hide();

			jQuery('#same_as_shipping').click(function () {
				if (jQuery(this).prop('checked')) {
					jQuery('.block_billing_address').hide();
				} else {
					jQuery('.block_billing_address').show();
				}
			});


		});
		
		
        document.addEventListener("DOMContentLoaded", function() {
          const sameAsShippingCheckbox = document.getElementById("same_as_shipping");
          const shippingFields = [
            "first_name",
            "last_name",
            "company_name_or_institutional",
            "address",
            "address2",
            "country",
            "shipping_phone",
            "state",
            "shipping_town_city",
            "postal_code"
          ];
          const billingFields = [
            "first_name_1",
            "last_name_1",
            "billing_company_name",
            "address_1",
            "address2_1",
            "country_1",
            "country",
            "state",
            "billing_phone",
            "shipping_town_city",
            "postal_code"
          ];
        
          sameAsShippingCheckbox.addEventListener("change", function() {
            if (sameAsShippingCheckbox.checked) {
              for (let i = 0; i < shippingFields.length; i++) {
                const shippingField = document.getElementById(shippingFields[i]);
                const billingField = document.getElementById(billingFields[i]);
                billingField.value = shippingField.value;
              }
            } else {
              // You can clear or set default values for billing address fields if needed.
            }
          });
        });


    </script>
    
    
    <?php  } ?>
    
	
</head>

<body <?php body_class(); ?>>
	<?php if ( function_exists( 'wp_body_open' ) ) : ?>
		<?php wp_body_open(); ?>
	<?php endif; ?>

	<?php do_action( 'woodmart_after_body_open' ); ?>

	<div class="website-wrapper">
		<?php if ( woodmart_needs_header() ) : ?>
			<?php if ( ! function_exists( 'elementor_theme_do_location' ) || ! elementor_theme_do_location( 'header' ) ) : ?>
				<header <?php woodmart_get_header_classes(); // phpcs:ignore ?>>
					<?php whb_generate_header(); ?>
				</header>
			<?php endif ?>

			<?php woodmart_page_top_part(); ?>
		<?php endif ?>
		
		
		
		



