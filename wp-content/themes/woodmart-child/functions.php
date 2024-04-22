<?php
/**
 * Enqueue script and styles for child theme
 */
function woodmart_child_enqueue_styles() {
	wp_enqueue_style( 'child-style', get_stylesheet_directory_uri() . '/style.css', array( 'woodmart-style' ), woodmart_get_theme_info( 'Version' ) );
}
add_action( 'wp_enqueue_scripts', 'woodmart_child_enqueue_styles', 10010 );


function add_custom_class_to_single_product_page($classes) {
    if (is_product()) {
        global $post; // Define $post as a global variable

        // Get the product ID.
        $product_id = $post->ID;

        // Check if the product has attributes.
        $product = wc_get_product($product_id);
        if (!$product->has_attributes()) {
            // Add your custom class here
            $classes[] = 'check_attribute_product';
        } else {
             $classes[] = 'check_attribute_product_spec';
        }
    }
    return $classes;
}
add_filter('body_class', 'add_custom_class_to_single_product_page');


/**restrict for login user***/
add_action('template_redirect','check_if_logged_in');
function check_if_logged_in()
{
	$pageid =5935;
	if(!is_user_logged_in() && is_page($pageid))
	{
		$url = add_query_arg(
			'redirect_to',
			get_permalink($pagid),
			site_url('/my-account/') // your my acount url
		);
		wp_redirect($url);
		exit;
	}
}



/**
 * Customizing styles of the email notification
 *
 * @link https://wpforms.com/developers/how-to-customize-the-styles-on-the-email-template/
 */
  
function wpf_dev_email_message_custom_styles( $message ) {
     
    $custom_styles =
        '
        #templateBody {
            background-color: red;border:1px solid red;
        }
 
        #templateBody .mcnTextContent a { 
            color:#32a852;  
            font-weight:normal;     
            text-decoration:underline;border:1px solid red; 
        }
        ';
 
        $message = preg_replace('/<style type="text\/css">(.*?)<\/style>/s', '<style type="text/css">$1' . $custom_styles . '</style>', $message);
 
        return $message;
     
}
   
add_filter( 'wpforms_email_message', 'wpf_dev_email_message_custom_styles', 10, 1);




// add_filter( 'wp_nav_menu_items', 'add_raq_widget_to_menu', 10, 2 );
function custom_raq_widget_shortcode() {
    ob_start();
    $instance = array(
        // 'title'           => 'test',
        'item_name'       => '',
        'item_plural_name' => '',
        'show_thumbnail'  => 1,
        'show_price'      => 1,
        'show_quantity'   => 1,
        'show_sku' =>1,
        'show_variations' => 1,
        'show_title_inside' => true,
        
        'button_label'    => 'Quotation Cart',
    );

    the_widget('YITH_YWRAQ_Mini_List_Quote_Widget', $instance);

    $widget_output = ob_get_clean();

    return '<div class="site-raq">' . $widget_output . '</div>';
}

add_shortcode('raq_widget', 'custom_raq_widget_shortcode');




/*add custom product tab with meta**/

add_action( 'woocommerce_product_write_panel_tabs', 'add_product_specification_panel_tab' );
function add_product_specification_panel_tab() {
    ?>
    <li class="specification_options specification_tab">
        <a href="#specification_product_data"><span><?php echo esc_html__( 'Specifications', 'electro' ); ?></span></a>
    </li>
    <?php
}
add_action( 'woocommerce_product_data_panels', 'add_product_specification_panel_data' );

 function add_product_specification_panel_data() {
    global $post;
    ?>
    <div id="specification_product_data" class="panel woocommerce_options_panel">
        <div class="options_group">
            <?php
                $display_attributes = get_post_meta( $post->ID, '_specifications_display_attributes', true );
                woocommerce_wp_checkbox( array(
                    'id' => '_specifications_display_attributes',
                    'label' => esc_html__( 'Display Attributes', 'electro' ),
                    'desc_tip' => 'true',
                    'description' => esc_html__( 'Display Attributes for products in specifications tab.', 'electro' ),
                    'value' => $display_attributes ? $display_attributes : 'yes'
                ) );

                woocommerce_wp_text_input(  array( 
                    'id' => '_specifications_attributes_title',
                    'label' => esc_html__( 'Attributes Title', 'electro' ),
                    'desc_tip' => 'true',
                    'description' => esc_html__( 'Attributes Title for products in specifications tab.', 'electro' ),
                    'type' => 'text'
                ) );
            ?>
        </div>
        <div class="options_group">
            <?php
                $specifications = get_post_meta( $post->ID, '_specifications', true );
                wp_editor( htmlspecialchars_decode( $specifications ), '_specifications', array() );
            ?>
        </div>
    </div>
    <?php
}

foreach ( wc_get_product_types() as $value => $label ) {
    add_action( 'woocommerce_process_product_meta_' . $value,'save_product_specification_panel_data' );
}
 function save_product_specification_panel_data( $post_id ) {
    $display_attributes = isset( $_POST['_specifications_display_attributes'] ) ? 'yes' : 'no';
    update_post_meta( $post_id, '_specifications_display_attributes', $display_attributes );

    $attributes_title = isset( $_POST['_specifications_attributes_title'] ) ? $_POST['_specifications_attributes_title'] : '';
    update_post_meta( $post_id, '_specifications_attributes_title', $attributes_title );

    $specifications = isset( $_POST['_specifications'] ) ? $_POST['_specifications'] : '';
    update_post_meta( $post_id, '_specifications', $specifications );
}

// for hide stock/sku.

function custom_hide_woodmart_stock_status() {
    echo '<style>.wd-product-stock { display: none !important; }</style>';
}

add_action('wp_head', 'custom_hide_woodmart_stock_status');


add_action( 'wp_footer', 'requast_cost_shipping_address' );
function requast_cost_shipping_address(){
    ?>
    <style>
        .block_billing_address{display:none;}
    </style>
    <script>
        jQuery( document ).ready(function() {
           
            if(jQuery('#same_as_shipping').prop('checked')==true){
                 var shipping_company=jQuery('#company_name_or_institutional').val();
                 var shipping_first_name=jQuery('#first_name').val();
                 var shipping_last_name=jQuery('#last_name').val();
                 var shipping_address_1=jQuery('#address').val();
                 var shipping_address_2=jQuery('#address2').val();
                 var shipping_country=jQuery('#country').val();
                 var shipping_state=jQuery('#state').val();
                  var shipping_town_city=jQuery('#shipping_town_city').val();
                 var shipping_post_code=jQuery('#postal_code').val();
                   var shipping_value_phone = jQuery('#shipping_phone').val();
                   
                // append value in billing address  
                jQuery('#billing_company_name').val(shipping_company);
                jQuery('#first_name_1').val(shipping_first_name);
                jQuery('#last_name_1').val(shipping_last_name);
                jQuery('#address_1').val(shipping_address_1);
                jQuery('#address2_1').val(shipping_address_2);
                jQuery('#country_1').val(shipping_country);
                jQuery('#state_1').val(shipping_state);
                 jQuery('#shipping_town_city_1').val(shipping_state);
                jQuery('#postal_code_1').val(shipping_post_code);
                 jQuery('#billing_phone').val(shipping_value_phone);

            }
            
            // on chnage datils 
            // 
            jQuery('#company_name_or_institutional').keyup(function() {
                  var Company_Name_or_Institutional = jQuery(this).val();
                  jQuery('#billing_company_name').val(Company_Name_or_Institutional);
            });
            jQuery('#first_name').keyup(function() {
              var first_name = jQuery(this).val();
              jQuery('#first_name_1').val(first_name);
            });
            jQuery('#last_name').keyup(function() {
              var last_name = jQuery(this).val();
              jQuery('#last_name_1').val(last_name);
            });
            jQuery('#address').keyup(function() {
              var address = jQuery(this).val();
              jQuery('#address_1').val(address);
            });
            jQuery('#address2').keyup(function() {
              var address2 = jQuery(this).val();
              jQuery('#address2_1').val(address2);
            });
             jQuery('#address2').keyup(function() {
              var address2 = jQuery(this).val();
              jQuery('#address2_1').val(address2);
            });
            jQuery('#shipping_phone').keyup(function() {
              var shipping_phones = jQuery(this).val();
              jQuery('#billing_phone').val(shipping_phones);
            });
       
            //   jQuery(document).on('change','#country',function(e) {
            //     alert("test");
            // });

   

			
             jQuery('#state').keyup(function() {
              var State = jQuery(this).val();
                var shipping_country=jQuery('#country').val();
               console.log(shipping_country);
              jQuery('#state_1').val(State);
               jQuery('#country_1').val(shipping_country);
              // Change the value or make some change to the internal state
            jQuery('#country_1').trigger('change.select2'); 
            });
              jQuery('#shipping_town_city').keyup(function() {
              var shipping_town_city = jQuery(this).val();
              jQuery('#shipping_town_city_1').val(shipping_town_city);
            });
            
            jQuery('#postal_code').keyup(function() {
              var Postal_Code = jQuery(this).val();
              jQuery('#postal_code_1').val(Postal_Code);
            });
          
           jQuery(document).on('change','#same_as_shipping',function(e) {
                if (!jQuery(this).is(":checked")) {
                    jQuery(".block_billing_address").css("display", "block");
                    // jQuery( ".yith-ywraq-mail-form-wrapper" ).removeClass( "myClass yourClass" )
                    jQuery('#billing_company_name').val('');
                    jQuery('#first_name_1').val('');
                    jQuery('#last_name_1').val('');
                    jQuery('#address_1').val('');
                    jQuery('#address2_1').val('');
                    jQuery('#country_1').val('');
                    jQuery('#state_1').val('');
                     jQuery('#shipping_town_city_1').val('');
                    jQuery('#postal_code_1').val('');
                    jQuery('#shipping_phone').val('');
                }else{
                 jQuery(".block_billing_address").css("display", "none");
                 var shipping_company=jQuery('#company_name_or_institutional').val();
                 var shipping_first_name=jQuery('#first_name').val();
                 var shipping_last_name=jQuery('#last_name').val();
                 var shipping_address_1=jQuery('#address').val();
                 var shipping_address_2=jQuery('#address2').val();
                 var shipping_country=jQuery('#country').val();
                 var shipping_state=jQuery('#state').val();
                 var shipping_town_city=jQuery('#shipping_town_city').val();
                 var shipping_post_code=jQuery('#postal_code').val();
                 var shipping_value_phone = jQuery('#shipping_phone').val();
             
                jQuery('#billing_phone').val(shipping_value_phone);
                jQuery('#billing_company_name').val(shipping_company);
                jQuery('#first_name_1').val(shipping_first_name);
                jQuery('#last_name_1').val(shipping_last_name);
                jQuery('#address_1').val(shipping_address_1);
                jQuery('#address2_1').val(shipping_address_2);
                jQuery('#country_1').val(shipping_country);
                jQuery('#state_1').val(shipping_state);
                 jQuery('#shipping_town_city_1').val(shipping_town_city);
                jQuery('#postal_code_1').val(shipping_post_code);

                
                }
            });
             

        });
    </script>
<?php
}

// Assuming your custom function is named 'update_post_meta_on_quote_submission'
add_action( 'ywraq_process', 'update_post_meta_on_quote_submission', 10, 1 );

function update_post_meta_on_quote_submission( $filled_form_fields ) {

$order_id = WC()->session->get( 'raq_new_order' );
 $shipping_phone_ma =  $filled_form_fields['shipping_phone']['value'];
  if ( isset( $shipping_phone_ma ) ) {
        // Get the shipping phone number
        $shipping_phone = sanitize_text_field( $filled_form_fields['shipping_phone'] );

        // Now you can use $shipping_phone as needed
        // For example, update post meta with the shipping phone number
        update_post_meta( $order_id, '_shipping_phone', $shipping_phone_ma);
    }
}