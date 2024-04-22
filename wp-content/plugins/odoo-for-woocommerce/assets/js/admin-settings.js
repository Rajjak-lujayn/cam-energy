/**
 * My JavaScript file
 *
 * @package odoo-for-woocommerce
 * @subpackage js
 * @since 1.0.0
 *
 * @textdomain wc-odoo-integration
 */

jQuery(function() {
	const params = new URL(window.location.href).searchParams;
	if (params.get('page') == 'wc-settings' && params.get('tab') == 'integration' && params.get('section') == 'woocommmerce_odoo_integration') {
		jQuery('#mainform').on('submit', function (e){
			if (jQuery('#tab3').is(':checked')) {
				if(jQuery("#woocommerce_woocommmerce_odoo_integration_odoo_map_to_default_customers").prop("checked")){
					if(jQuery("#woocommerce_woocommmerce_odoo_integration_odoo_default_customer_id").val() == ''){
						e.preventDefault();
						jQuery("#woocommerce_woocommmerce_odoo_integration_odoo_default_customer_id").focus();
						alert('If you want to use "Map All Orders to Default Customer" feature, you must enter the default Odoo customer ID.');
					} else {
						return true;
					}
				} else {
					return true;
				}
			} else {
				return true;
			}
		});
		var activeIndex = jQuery('.tabset > input[type="radio"]:checked').index();
		console.log(activeIndex);
		if (jQuery('.tabset > #tab4').is(':checked')) {
			jQuery('.submit').hide();
		} else {
			jQuery('.submit').show();
		}
		jQuery('.tabset > input[type="radio"]').on('change', function() {
			if (jQuery('#tab4').is(':checked')) {
				jQuery('.submit').hide();
			} else {
				jQuery('.submit').show();
			}
		});
	}
})

jQuery(function()
{
	console.log(odoo_admin.is_creds_defined);
	if ((odoo_admin.is_creds_defined == 1 && jQuery('#woocommerce_woocommmerce_odoo_integration_odooTax').length == 0) 
		|| (odoo_admin.is_creds_defined == 0 && jQuery('#woocommerce_woocommmerce_odoo_integration_odooTax').length > 0)) {
		location.reload();
	}
	jQuery(document).on('change','.select_odoo_version', function(){
		change_password_input(jQuery(this).val());
	});
	change_password_input(jQuery('.select_odoo_version').val());

	if(!jQuery("#woocommerce_woocommmerce_odoo_integration_odoo_map_to_default_customers").prop("checked")){
		jQuery(".opmc-odoo-default-customer").hide();
	} else {
		jQuery(".opmc-odoo-default-customer").show();
	}

	jQuery("#woocommerce_woocommmerce_odoo_integration_odoo_map_to_default_customers").on('change', function(){
		if (!jQuery(this).prop("checked")) {
			jQuery(".opmc-odoo-default-customer").hide();
		} else {
			jQuery(".opmc-odoo-default-customer").show();
		}
	});

	if (!jQuery("#woocommerce_woocommmerce_odoo_integration_odoo_import_refund_order").prop("checked")) {
		jQuery(".opmc_import_refund_order_frequency").hide();
	} else {
		jQuery(".opmc_import_refund_order_frequency").show();
	}
	
	jQuery("#woocommerce_woocommmerce_odoo_integration_odoo_import_refund_order").on('change', function() {
		if (!jQuery(this).prop("checked")) {
			jQuery(".opmc_import_refund_order_frequency").hide();
		} else {
			jQuery(".opmc_import_refund_order_frequency").show();
		}
	});
	
	if (!jQuery("#woocommerce_woocommmerce_odoo_integration_odoo_import_customer").prop("checked")) {
		jQuery("#woocommerce_woocommmerce_odoo_integration_odoo_import_customer_frequency").closest('tr').hide();
	} else {
		jQuery("#woocommerce_woocommmerce_odoo_integration_odoo_import_customer_frequency").closest('tr').show();
	}
	
	jQuery("#woocommerce_woocommmerce_odoo_integration_odoo_import_customer").on('change', function() {
		if (!jQuery(this).prop("checked")) {
			jQuery("#woocommerce_woocommmerce_odoo_integration_odoo_import_customer_frequency").closest('tr').hide();
		} else {
			jQuery("#woocommerce_woocommmerce_odoo_integration_odoo_import_customer_frequency").closest('tr').show();
		}
	});

	if (!jQuery("#woocommerce_woocommmerce_odoo_integration_odoo_export_customer").prop("checked")) {
		jQuery("#woocommerce_woocommmerce_odoo_integration_odoo_export_customer_frequency").closest('tr').hide();
	} else {
		jQuery("#woocommerce_woocommmerce_odoo_integration_odoo_export_customer_frequency").closest('tr').show();
	}
	
	jQuery("#woocommerce_woocommmerce_odoo_integration_odoo_export_customer").on('change', function() {
		if (!jQuery(this).prop("checked")) {
			jQuery("#woocommerce_woocommmerce_odoo_integration_odoo_export_customer_frequency").closest('tr').hide();
		} else {
			jQuery("#woocommerce_woocommmerce_odoo_integration_odoo_export_customer_frequency").closest('tr').show();
		}
	});

	if (!jQuery("#woocommerce_woocommmerce_odoo_integration_odoo_import_create_product").prop("checked")) {
		jQuery("#woocommerce_woocommmerce_odoo_integration_odoo_import_create_product_frequency").closest('tr').hide();
	} else {
		jQuery("#woocommerce_woocommmerce_odoo_integration_odoo_import_create_product_frequency").closest('tr').show();
	}
	
	jQuery("#woocommerce_woocommmerce_odoo_integration_odoo_import_create_product").on('change', function() {
		if (!jQuery(this).prop("checked")) {
			jQuery("#woocommerce_woocommmerce_odoo_integration_odoo_import_create_product_frequency").closest('tr').hide();
		} else {
			jQuery("#woocommerce_woocommmerce_odoo_integration_odoo_import_create_product_frequency").closest('tr').show();
		}
	});

	if (!jQuery("#woocommerce_woocommmerce_odoo_integration_odoo_export_create_product").prop("checked")) {
		jQuery("#woocommerce_woocommmerce_odoo_integration_odoo_export_create_product_frequency").closest('tr').hide();
	} else {
		jQuery("#woocommerce_woocommmerce_odoo_integration_odoo_export_create_product_frequency").closest('tr').show();
	}
	
	jQuery("#woocommerce_woocommmerce_odoo_integration_odoo_export_create_product").on('change', function() {
		if (!jQuery(this).prop("checked")) {
			jQuery("#woocommerce_woocommmerce_odoo_integration_odoo_export_create_product_frequency").closest('tr').hide();
		} else {
			jQuery("#woocommerce_woocommmerce_odoo_integration_odoo_export_create_product_frequency").closest('tr').show();
		}
	});

	if (!jQuery("#woocommerce_woocommmerce_odoo_integration_odoo_import_create_categories").prop("checked")) {
		jQuery("#woocommerce_woocommmerce_odoo_integration_odoo_import_create_categories_frequency").closest('tr').hide();
	} else {
		jQuery("#woocommerce_woocommmerce_odoo_integration_odoo_import_create_categories_frequency").closest('tr').show();
	}
	
	jQuery("#woocommerce_woocommmerce_odoo_integration_odoo_import_create_categories").on('change', function() {
		if (!jQuery(this).prop("checked")) {
			jQuery("#woocommerce_woocommmerce_odoo_integration_odoo_import_create_categories_frequency").closest('tr').hide();
		} else {
			jQuery("#woocommerce_woocommmerce_odoo_integration_odoo_import_create_categories_frequency").closest('tr').show();
		}
	});
	if (!jQuery("#woocommerce_woocommmerce_odoo_integration_odoo_export_create_categories").prop("checked")) {
		jQuery("#woocommerce_woocommmerce_odoo_integration_odoo_export_create_categories_frequency").closest('tr').hide();
	} else {
		jQuery("#woocommerce_woocommmerce_odoo_integration_odoo_export_create_categories_frequency").closest('tr').show();
	}
	
	jQuery("#woocommerce_woocommmerce_odoo_integration_odoo_export_create_categories").on('change', function() {
		if (!jQuery(this).prop("checked")) {
			jQuery("#woocommerce_woocommmerce_odoo_integration_odoo_export_create_categories_frequency").closest('tr').hide();
		} else {
			jQuery("#woocommerce_woocommmerce_odoo_integration_odoo_export_create_categories_frequency").closest('tr').show();
		}
	});

	if (!jQuery("#woocommerce_woocommmerce_odoo_integration_odoo_import_create_attributes").prop("checked")) {
		jQuery("#woocommerce_woocommmerce_odoo_integration_odoo_import_create_attributes_frequency").closest('tr').hide();
	} else {
		jQuery("#woocommerce_woocommmerce_odoo_integration_odoo_import_create_attributes_frequency").closest('tr').show();
	}
	
	jQuery("#woocommerce_woocommmerce_odoo_integration_odoo_import_create_attributes").on('change', function() {
		if (!jQuery(this).prop("checked")) {
			jQuery("#woocommerce_woocommmerce_odoo_integration_odoo_import_create_attributes_frequency").closest('tr').hide();
		} else {
			jQuery("#woocommerce_woocommmerce_odoo_integration_odoo_import_create_attributes_frequency").closest('tr').show();
		}
	});

	if (!jQuery("#woocommerce_woocommmerce_odoo_integration_odoo_export_create_attributes").prop("checked")) {
		jQuery("#woocommerce_woocommmerce_odoo_integration_odoo_export_create_attributes_frequency").closest('tr').hide();
	} else {
		jQuery("#woocommerce_woocommmerce_odoo_integration_odoo_export_create_attributes_frequency").closest('tr').show();
	}
	
	jQuery("#woocommerce_woocommmerce_odoo_integration_odoo_export_create_attributes").on('change', function() {
		if (!jQuery(this).prop("checked")) {
			jQuery("#woocommerce_woocommmerce_odoo_integration_odoo_export_create_attributes_frequency").closest('tr').hide();
		} else {
			jQuery("#woocommerce_woocommmerce_odoo_integration_odoo_export_create_attributes_frequency").closest('tr').show();
		}
	});

	if (!jQuery("#woocommerce_woocommmerce_odoo_integration_odoo_import_order").prop("checked")) {
		jQuery("#woocommerce_woocommmerce_odoo_integration_odoo_import_order_frequency").closest('tr').hide();
	} else {
		jQuery("#woocommerce_woocommmerce_odoo_integration_odoo_import_order_frequency").closest('tr').show();
	}
	
	jQuery("#woocommerce_woocommmerce_odoo_integration_odoo_import_order").on('change', function() {
		if (!jQuery(this).prop("checked")) {
			jQuery("#woocommerce_woocommmerce_odoo_integration_odoo_import_order_frequency").closest('tr').hide();
		} else {
			jQuery("#woocommerce_woocommmerce_odoo_integration_odoo_import_order_frequency").closest('tr').show();
		}
	});

	if (!jQuery("#woocommerce_woocommmerce_odoo_integration_odoo_export_order").prop("checked")) {
		jQuery("#woocommerce_woocommmerce_odoo_integration_odoo_export_order_frequency").closest('tr').hide();
	} else {
		jQuery("#woocommerce_woocommmerce_odoo_integration_odoo_export_order_frequency").closest('tr').show();
	}
	
	jQuery("#woocommerce_woocommmerce_odoo_integration_odoo_export_order").on('change', function() {
		if (!jQuery(this).prop("checked")) {
			jQuery("#woocommerce_woocommmerce_odoo_integration_odoo_export_order_frequency").closest('tr').hide();
		} else {
			jQuery("#woocommerce_woocommmerce_odoo_integration_odoo_export_order_frequency").closest('tr').show();
		}
	});
	if(!jQuery("#woocommerce_woocommmerce_odoo_integration_odoo_fiscal_position").prop("checked")){
		jQuery("#woocommerce_woocommmerce_odoo_integration_odoo_fiscal_position_selected").prop('disabled', true);
	}

	if (!jQuery("#woocommerce_woocommmerce_odoo_integration_odoo_export_invoice").prop("checked")) {
		jQuery("#woocommerce_woocommmerce_odoo_integration_odoo_export_refund_order").prop("checked", false);
		jQuery("#woocommerce_woocommmerce_odoo_integration_odoo_export_refund_order").prop('disabled', true);
		jQuery("#woocommerce_woocommmerce_odoo_integration_odoo_mark_invoice_paid").prop("checked", false);
		jQuery("#woocommerce_woocommmerce_odoo_integration_odoo_mark_invoice_paid").prop('disabled', true);
	} else {
		jQuery("#woocommerce_woocommmerce_odoo_integration_odoo_export_refund_order").prop('disabled', false);
		jQuery("#woocommerce_woocommmerce_odoo_integration_odoo_mark_invoice_paid").prop('disabled', false);
	}
	jQuery("#woocommerce_woocommmerce_odoo_integration_odoo_export_invoice").on('change', function(){
		if (jQuery(this).prop('checked')) {
			// jQuery("#woocommerce_woocommmerce_odoo_integration_odoo_export_refund_order").prop("checked", true);
			jQuery("#woocommerce_woocommmerce_odoo_integration_odoo_export_refund_order").prop('disabled', false);
			jQuery("#woocommerce_woocommmerce_odoo_integration_odoo_mark_invoice_paid").prop('disabled', false);
		} else {
			jQuery("#woocommerce_woocommmerce_odoo_integration_odoo_export_refund_order").prop("checked", false);
			jQuery("#woocommerce_woocommmerce_odoo_integration_odoo_export_refund_order").prop('disabled', true);
			jQuery("#woocommerce_woocommmerce_odoo_integration_odoo_mark_invoice_paid").prop("checked", false);
			jQuery("#woocommerce_woocommmerce_odoo_integration_odoo_mark_invoice_paid").prop('disabled', true);
		}
	})

	if (!jQuery('#woocommerce_woocommmerce_odoo_integration_odoo_export_create_product').prop("checked")) {
		jQuery("#woocommerce_woocommmerce_odoo_integration_odoo_export_update_product").prop("checked", false);
		jQuery("#woocommerce_woocommmerce_odoo_integration_odoo_export_update_stocks").prop("checked", false);
		jQuery("#woocommerce_woocommmerce_odoo_integration_odoo_export_update_price").prop("checked", false);
		jQuery("#woocommerce_woocommmerce_odoo_integration_odoo_export_update_product").prop("disabled", true);
		jQuery("#woocommerce_woocommmerce_odoo_integration_odoo_export_update_stocks").prop("disabled", true);
		jQuery("#woocommerce_woocommmerce_odoo_integration_odoo_export_update_price").prop("disabled", true);
	} else {
		jQuery("#woocommerce_woocommmerce_odoo_integration_odoo_export_update_product").prop("disabled", false);
		jQuery("#woocommerce_woocommmerce_odoo_integration_odoo_export_update_stocks").prop("disabled", false);
		jQuery("#woocommerce_woocommmerce_odoo_integration_odoo_export_update_price").prop("disabled", false);
	}

	jQuery("#woocommerce_woocommmerce_odoo_integration_odoo_export_create_product").on('change', function(){
		if (jQuery(this).prop('checked')) {
			jQuery("#woocommerce_woocommmerce_odoo_integration_odoo_export_update_product").prop("disabled", false);
			jQuery("#woocommerce_woocommmerce_odoo_integration_odoo_export_update_stocks").prop("disabled", false);
			jQuery("#woocommerce_woocommmerce_odoo_integration_odoo_export_update_price").prop("disabled", false); 
		} else {
			jQuery("#woocommerce_woocommmerce_odoo_integration_odoo_export_update_product").prop("checked", false);
			jQuery("#woocommerce_woocommmerce_odoo_integration_odoo_export_update_stocks").prop("checked", false);
			jQuery("#woocommerce_woocommmerce_odoo_integration_odoo_export_update_price").prop("checked", false);
			jQuery("#woocommerce_woocommmerce_odoo_integration_odoo_export_update_product").prop("disabled", true);
			jQuery("#woocommerce_woocommmerce_odoo_integration_odoo_export_update_stocks").prop("disabled", true);
			jQuery("#woocommerce_woocommmerce_odoo_integration_odoo_export_update_price").prop("disabled", true);
		}
	})

	if (!jQuery('#woocommerce_woocommmerce_odoo_integration_odoo_import_create_product').prop("checked")) {
		jQuery("#woocommerce_woocommmerce_odoo_integration_odoo_import_update_product").prop("checked", false);
		jQuery("#woocommerce_woocommmerce_odoo_integration_odoo_import_update_stocks").prop("checked", false);
		jQuery("#woocommerce_woocommmerce_odoo_integration_odoo_import_update_price").prop("checked", false);
		jQuery("#woocommerce_woocommmerce_odoo_integration_odoo_import_pos_product").prop("disabled", true);
		jQuery("#woocommerce_woocommmerce_odoo_integration_odoo_import_update_product").prop("disabled", true);
		jQuery("#woocommerce_woocommmerce_odoo_integration_odoo_import_update_stocks").prop("disabled", true);
		jQuery("#woocommerce_woocommmerce_odoo_integration_odoo_import_update_price").prop("disabled", true);
	} else {
		jQuery("#woocommerce_woocommmerce_odoo_integration_odoo_import_pos_product").prop("disabled", false);
		jQuery("#woocommerce_woocommmerce_odoo_integration_odoo_import_update_product").prop("disabled", false);
		jQuery("#woocommerce_woocommmerce_odoo_integration_odoo_import_update_stocks").prop("disabled", false);
		jQuery("#woocommerce_woocommmerce_odoo_integration_odoo_import_update_price").prop("disabled", false);
	}

	jQuery("#woocommerce_woocommmerce_odoo_integration_odoo_import_create_product").on('change', function(){
		if (jQuery(this).prop('checked')) {
			jQuery("#woocommerce_woocommmerce_odoo_integration_odoo_import_pos_product").prop("disabled", false);
			jQuery("#woocommerce_woocommmerce_odoo_integration_odoo_import_update_product").prop("disabled", false);
			jQuery("#woocommerce_woocommmerce_odoo_integration_odoo_import_update_stocks").prop("disabled", false);
			jQuery("#woocommerce_woocommmerce_odoo_integration_odoo_import_update_price").prop("disabled", false); 
		} else {
			jQuery("#woocommerce_woocommmerce_odoo_integration_odoo_import_pos_product").prop("checked", false);
			jQuery("#woocommerce_woocommmerce_odoo_integration_odoo_import_update_product").prop("checked", false);
			jQuery("#woocommerce_woocommmerce_odoo_integration_odoo_import_update_stocks").prop("checked", false);
			jQuery("#woocommerce_woocommmerce_odoo_integration_odoo_import_update_price").prop("checked", false);
			jQuery("#woocommerce_woocommmerce_odoo_integration_odoo_import_pos_product").prop("disabled", true);
			jQuery("#woocommerce_woocommmerce_odoo_integration_odoo_import_update_product").prop("disabled", true);
			jQuery("#woocommerce_woocommmerce_odoo_integration_odoo_import_update_stocks").prop("disabled", true);
			jQuery("#woocommerce_woocommmerce_odoo_integration_odoo_import_update_price").prop("disabled", true);
		}
	})

	if (!jQuery("#woocommerce_woocommmerce_odoo_integration_odoo_status_mapping").prop("checked")) {
		jQuery(".order_mapping_block").hide();
		jQuery(".mappingBlock").hide();
	} else {
		jQuery(".order_mapping_block").show();
		jQuery(".mappingBlock").show();
	}

	jQuery("#woocommerce_woocommmerce_odoo_integration_odoo_status_mapping").on('change', function(){
		if (!jQuery(this).prop("checked")) {
			jQuery(".order_mapping_block").hide();
			jQuery(".mappingBlock").hide();
		} else {
			jQuery(".order_mapping_block").show();
			jQuery(".mappingBlock").show();
		}
	});

	if (!jQuery("#woocommerce_woocommmerce_odoo_integration_odoo_export_coupon").prop("checked")) {
		jQuery("#woocommerce_woocommmerce_odoo_integration_odoo_export_coupon_frequency").closest('tr').hide();
		jQuery("#woocommerce_woocommmerce_odoo_integration_odoo_export_coupon_update").closest('tr').hide();
	} else {
		jQuery("#woocommerce_woocommmerce_odoo_integration_odoo_export_coupon_frequency").closest('tr').show();
		jQuery("#woocommerce_woocommmerce_odoo_integration_odoo_export_coupon_update").closest('tr').show();
	}
jQuery("#woocommerce_woocommmerce_odoo_integration_odoo_export_coupon").on('change', function(){

    if (!jQuery(this).prop("checked")) {
        jQuery("#woocommerce_woocommmerce_odoo_integration_odoo_export_coupon_frequency").closest('tr').hide();
		jQuery("#woocommerce_woocommmerce_odoo_integration_odoo_export_coupon_update").prop("checked", false).closest('tr').hide();

    } else {
        jQuery("#woocommerce_woocommmerce_odoo_integration_odoo_export_coupon_frequency").closest('tr').show();
        jQuery("#woocommerce_woocommmerce_odoo_integration_odoo_export_coupon_update").closest('tr').show();
    }
});

if (!jQuery("#woocommerce_woocommmerce_odoo_integration_odoo_import_coupon").prop("checked")) {
	jQuery("#woocommerce_woocommmerce_odoo_integration_odoo_import_coupon_frequency").closest('tr').hide();
	jQuery("#woocommerce_woocommmerce_odoo_integration_odoo_import_coupon_update").closest('tr').hide();
} else {
	jQuery("#woocommerce_woocommmerce_odoo_integration_odoo_import_coupon_frequency").closest('tr').show();
	jQuery("#woocommerce_woocommmerce_odoo_integration_odoo_import_coupon_update").closest('tr').show();
}

jQuery("#woocommerce_woocommmerce_odoo_integration_odoo_import_coupon").on('change', function(){

    if (!jQuery(this).prop("checked")) {
        jQuery("#woocommerce_woocommmerce_odoo_integration_odoo_import_coupon_frequency").closest('tr').hide();
        jQuery("#woocommerce_woocommmerce_odoo_integration_odoo_import_coupon_update").prop("checked", false).closest('tr').hide();
    } else {
        jQuery("#woocommerce_woocommmerce_odoo_integration_odoo_import_coupon_frequency").closest('tr').show();
        jQuery("#woocommerce_woocommmerce_odoo_integration_odoo_import_coupon_update").closest('tr').show();
    }
});



});

function change_password_input(val) {
	var password_input_label = jQuery('label[for=woocommerce_woocommmerce_odoo_integration_client_password]');
	var tool_tip = wp.i18n.__('Insert password for your API access user', 'wc-odoo-integration');
	if(val == 14) {
		password_input_label.text(wp.i18n.__('Password/Api Key', 'wc-odoo-integration'));
		var tool_tip = wp.i18n.__('Insert Password or API Key for your API access user', 'wc-odoo-integration');
		jQuery('#woocommerce_woocommmerce_odoo_integration_client_password').siblings('.description').text(wp.i18n.__('Insert password/API Key for your API access user','wc-odoo-integration'));
	}else{
		password_input_label.text(wp.i18n.__('Password','wc-odoo-integration'));
		var tool_tip = wp.i18n.__('Insert password for your API access user', 'wc-odoo-integration');
		jQuery('#woocommerce_woocommmerce_odoo_integration_client_password').siblings('.description').text(wp.i18n.__('Insert password for your API access user','wc-odoo-integration'));
	}

	password_input_label.html(function(index, oldHtml) {
		return oldHtml + ' <span class="woocommerce-help-tip" data-tip="'+tool_tip+'" aria-label="'+tool_tip+'"></span>';
	  });

	jQuery( '.woocommerce-help-tip' ).tipTip( {
		'attribute': 'data-tip',
		'fadeIn':    50,
		'fadeOut':   50,
		'delay':     200
	} );
}

jQuery(document).on('click', "#addMoreMappingRows", function () {

    var clone_this = jQuery(this).parents('tr').prev('tr'); //get prev ele to clone
    var max_rows = clone_this[0].dataset.max_rows;

    var current_index = clone_this[0].dataset.index; //get current index
    var next = parseInt(current_index) + 1; //increment ele index

    var selected = [];
    
    jQuery('.odoo_woo_order_status').each(function() {
    	selected.push(this.value);
    });
    

    if (current_index < max_rows) {
        clone_this[0].dataset.index = next; //increment ele index
        clone_this.find('fieldset .select.odoo_woo_order_status').attr('name', 'woocommerce_woocommmerce_odoo_integration_odoo_woo_order_status[' + next + ']');
        clone_this.find('fieldset .select.odoo_woo_order_status').attr('id', 'woocommerce_woocommmerce_odoo_integration_odoo_woo_order_status_' + next );
        clone_this.find('fieldset .select.odoo_payment_status').attr('name', 'woocommerce_woocommmerce_odoo_integration_odoo_payment_status[' + next + ']');
        clone_this.find('fieldset .select.odoo_payment_status').attr('id', 'woocommerce_woocommmerce_odoo_integration_odoo_payment_status_' + next );

        jQuery(this).parents('tr').before(clone_this.clone()); // add new mapping row by cloing prev table

        clone_this[0].dataset.index = current_index; //fix for prev table
        clone_this.find('fieldset .select.odoo_woo_order_status').attr('name', 'woocommerce_woocommmerce_odoo_integration_odoo_woo_order_status[' + current_index + ']');
        clone_this.find('fieldset .select.odoo_woo_order_status').attr('id', 'woocommerce_woocommmerce_odoo_integration_odoo_woo_order_status_' + current_index );
        clone_this.find('fieldset .select.odoo_payment_status').attr('name', 'woocommerce_woocommmerce_odoo_integration_odoo_payment_status[' + current_index + ']');
        clone_this.find('fieldset .select.odoo_payment_status').attr('id', 'woocommerce_woocommmerce_odoo_integration_odoo_payment_status_' + current_index );

        //removing already selected status
        var cloned = jQuery(this).parents('tr').prev('tr');
        var options = cloned.find('fieldset .select.odoo_woo_order_status')[0].options;
        for (var i = options.length - 1; i >= 0; i--) {
        	var value = cloned.find('fieldset .select.odoo_woo_order_status')[0].options[i].value;
        	if(selected.includes(value)){
        		cloned.find('fieldset .select.odoo_woo_order_status')[0].remove(i);
        	}
        }

        jQuery('.mappingBlock:last').find('select').each(function () {
        	jQuery(this).val('');
        	if (jQuery(this).hasClass('odoo_payment_status')) {
        		jQuery(this).siblings('.description').html(wp.i18n.__('Selected state\'s description.', 'wc-odoo-integration'));
        	}
        });
    } else {
    	jQuery('#addMoreMappingRows').hide();
    }
    if (next == max_rows) {
    	jQuery('#addMoreMappingRows').hide();
    }

});


jQuery(document).on('change', '.odoo_woo_order_status', function () {
	var current_ele = jQuery(this).parents('tr');
	console.log(current_ele);
	var selected = [];
	jQuery('.odoo_woo_order_status').each(function() {
		if (jQuery(this).attr('name') != current_ele.find('.odoo_woo_order_status').attr('name')) {
			if (this.value == current_ele.find('.odoo_woo_order_status')[0].value) {
				confirm(wp.i18n.__('State already mapped!!','wc-odoo-integration'));
				current_ele.find('.odoo_woo_order_status')[0].value = '';
			}
		}
    	selected.push(this.value);
    });
});

jQuery(document).on('change', '.odoo_payment_status', function () {
	var selectedIndex = jQuery(this)[0].options.selectedIndex;
	var desc = jQuery(this)[0].options[selectedIndex].dataset.desc;
	jQuery(this).siblings('.description').html(desc);
});

jQuery("#woocommerce_woocommmerce_odoo_integration_odoo_fiscal_position").on('change', function(){
	if(jQuery(this).prop('checked')){
		jQuery('#woocommerce_woocommmerce_odoo_integration_odoo_fiscal_position_selected').html('<option value=""> ' + wp.i18n.__('Fetching options...','wc-odoo-integration') + '</option>');
		jQuery('#woocommerce_woocommmerce_odoo_integration_odoo_fiscal_position_selected').prop('disabled', true);
		var company_id = jQuery('#woocommerce_woocommmerce_odoo_integration_companyFile').val();
		jQuery.ajax({
			type: "post",
			dataType: "JSON",
			url: odoo_admin.ajax_url,
			data: { action: 'load_fiscal_positions', id: company_id, security: odoo_admin.ajax_nonce },
			success: function (response){
				var fiscal_positions = response.fiscal_position;
				var fiscal_position_options = '<option vlue=""> ' + wp.i18n.__('-- Select Fiscal Position --','wc-odoo-integration') + '</option>';
				jQuery.each(fiscal_positions, function (key, value){
					if (key != '') {
						fiscal_position_options += '<option value="'+ key +'">'+ value +'</option>';
					}
				});
				jQuery('#woocommerce_woocommmerce_odoo_integration_odoo_fiscal_position_selected').html(fiscal_position_options);
				jQuery('#woocommerce_woocommmerce_odoo_integration_odoo_fiscal_position_selected').prop('disabled', false);
			}
		})
	} else {
		jQuery('#woocommerce_woocommmerce_odoo_integration_odoo_fiscal_position_selected').html('<option value="">' + wp.i18n.__( '-- Select Fiscal Position --','wc-odoo-integration') + '</option>');
		jQuery("#woocommerce_woocommmerce_odoo_integration_odoo_fiscal_position_selected").prop('disabled', true);
	}
})

//Load extra option on the basises of company with ajax
jQuery(document).on('change', '#woocommerce_woocommmerce_odoo_integration_companyFile', function () {
	var company_file = jQuery(this).val();
	jQuery('#woocommerce_woocommmerce_odoo_integration_odooTax').html('<option value="">' + wp.i18n.__( 'Fetching options...','wc-odoo-integration') + ' </option>');
	jQuery('#woocommerce_woocommmerce_odoo_integration_shippingOdooTax').html('<option value="">' + wp.i18n.__( 'Fetching options...','wc-odoo-integration') + ' </option>');
	jQuery('#woocommerce_woocommmerce_odoo_integration_invoiceJournal').html('<option value="">' + wp.i18n.__( 'Fetching options...','wc-odoo-integration') + ' </option>');
	jQuery('#woocommerce_woocommmerce_odoo_integration_odooTax').prop('disabled', true);
	jQuery('#woocommerce_woocommmerce_odoo_integration_shippingOdooTax').prop('disabled', true);
	jQuery('#woocommerce_woocommmerce_odoo_integration_invoiceJournal').prop('disabled', true);
	jQuery.ajax({
		type: "post",
		dataType: "JSON",
		url: odoo_admin.ajax_url,
		data: { action: 'load_odoo_extra_fields', id: company_file, security: odoo_admin.ajax_nonce },
		success: function (response) {
			var taxes = response.taxes;
			var journal = response.journal;
			var taxes_options = '<option value="">'+ wp.i18n.__('-- Select Tax Type --','wc-odoo-integration') + '</option>';
			var journal_options = '<option value="">'+ wp.i18n.__('-- Select Invoice Journal --','wc-odoo-integration') +'</option>';
			jQuery.each(taxes, function (key, value) {
				if (key != '') {
					taxes_options += '<option value="' + key + '">' + value + '</option>';
				}
			});
			jQuery.each(journal, function (key, value) {
				if (key != '') {
					journal_options += '<option value="' + key + '">' + value + '</option>';
				}
			});
			jQuery('#woocommerce_woocommmerce_odoo_integration_odooTax').html(taxes_options);
			jQuery('#woocommerce_woocommmerce_odoo_integration_shippingOdooTax').html(taxes_options);
			jQuery('#woocommerce_woocommmerce_odoo_integration_invoiceJournal').html(journal_options);
			jQuery('#woocommerce_woocommmerce_odoo_integration_odooTax').prop('disabled', false);
			jQuery('#woocommerce_woocommmerce_odoo_integration_shippingOdooTax').prop('disabled', false);
			jQuery('#woocommerce_woocommmerce_odoo_integration_invoiceJournal').prop('disabled', false);
		}
	});
});


jQuery(".opmc_odoo_product_import").on("click", function(){
	jQuery(this).attr("disabled", true);

	jQuery.ajax({
		type: "post",
		dataType: "JSON",
		url: odoo_admin.ajax_url,
		data: { action: 'opmc_odoo_product_import', security: odoo_admin.ajax_nonce },
		success: function (response) {
			if (confirm(response.message)) {
				location.reload();
			} else {
				location.reload();
			}
		}
	});


});

jQuery(".opmc_odoo_product_export").on("click", function(){
	jQuery(this).attr("disabled", true);

	jQuery.ajax({
		type: "post",
		dataType: "JSON",
		url: odoo_admin.ajax_url,
		data: { action: 'opmc_odoo_product_export', security: odoo_admin.ajax_nonce },
		success: function (response) {
			if (confirm(response.message)) {
				location.reload();
			} else {
				location.reload();
			}
		}
	});


});

jQuery(document).ready(function($) {
	// Check if DataTable is already initialized
	if (!$.fn.DataTable.isDataTable('#opmc-odoo-logs-table')) {
		var table = $('#opmc-odoo-logs-table').DataTable({
			searching: false, // Disable search
			paging: true,     // Disable pagination
			info: false,       // Disable information display
			pageLength: 50, // Set default page length to 50
			columnDefs: [
				{ orderable: false, targets: [3] },
				{ orderable: true, targets: [0, 1, 2] }
			],
			order: []
		});
		$('.dataTables_length select').css('width', '50px').addClass('form-control');
		$('.dataTables_length select').css('margin', '4px').addClass('form-control');


	}
});