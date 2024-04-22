<?php
	
	/**
	 * Import Products from Odoo to Woo in background process.
	 */
class Opmc_Product_Import extends WP_Background_Process {
		
		
	protected $action = 'cron_odoo_import_product_process';
		
	/**
	 * Import product to WooCommerce from Odoo
	 *
	 * @param  [type] $item [description]
	 * @return [type]       [description]
	 */
	protected function task( $item_id ) {
		$odooApi = new WC_ODOO_API();
		if ( $odooApi->is_multi_company() ) {
			$multi_company_func = '/multi-company-files';
		} else {
			$multi_company_func = '';
		}
		require_once WC_ODOO_INTEGRATION_PLUGINDIR . 'includes' . $multi_company_func . '/class-wc-odoo-functions.php';
		$odoo_object = new WC_ODOO_Functions();
		$helper  = WC_ODOO_Helpers::getHelper();
			
		$item_data = $odooApi->fetch_record_by_id( 'product.template', $item_id, array() );
		// $odooApi->addLog(print_r($item_id, true) . ' products data id : ' . print_r($item_data, true));

		if ( is_array( $item_data ) ) {
			$template = $item_data[0];
			$attr_v   = array();
			
			if (!$helper->is_product_importable($template)) {
//				$odooApi->addLog('[Products Import] [Error] [Products import for ' . $template['display_name'] . ' is unsuccessful due to missing SKU. ] ');
				
				$missing_skus = get_option('_opmc_odoo_import_product_without_sku', array());
				
				$item_exists = false;
				
				foreach ($missing_skus as $key => $sku) {
					if ($sku['id'] == $item_id) {
						$missing_skus[$key]['name'] = $template['display_name'];
						$item_exists = true;
						break;
					}
				}
				
				if (!$item_exists) {
					$missing_skus[] = array(
						'id'   => $item_id,
						'name' => $template['display_name'],
					);
					
					update_option('_opmc_odoo_import_product_without_sku', $missing_skus);
				}
			} else {
				// $odooApi->addLog(print_r($template['id'], true) . ' Local Product : '. print_r($template['product_variant_count'], true));
				
				if ( $template['product_variant_count'] > 1 ) {
					// continue;
					// if ($template['id'] != 32) {
					if ( count( $attr_v ) == 0 ) {
						$attr_response = $odooApi->readAll( 'product.template.attribute.value', array( 'name', 'id' ) );
						$attr_values   = json_decode( json_encode( $attr_response->data->items ), true );
						// $odooApi->addLog('Prdcuts attributes : '. print_r($attr_values, true));
						foreach ( $attr_values as $key => $value ) {
							$attr_v[ $value['id'] ] = $value['name'];
						}
						$odoo_object->odoo_attr_values = $attr_v;
					}
					$products = $odooApi->fetch_record_by_ids( 'product.product', $template['product_variant_ids'], array( 'activity_ids', 'activity_state', 'activity_user_id', 'activity_type_id', 'activity_type_icon', 'activity_date_deadline', 'my_activity_date_deadline', 'activity_summary', 'activity_exception_decoration', 'activity_exception_icon', 'activity_calendar_event_id', 'message_is_follower', 'message_follower_ids', 'message_partner_ids', 'message_ids', 'has_message', 'message_needaction', 'message_needaction_counter', 'message_has_error', 'message_has_error_counter', 'message_attachment_count', 'rating_ids', 'website_message_ids', 'message_has_sms_error', 'price_extra', 'lst_price', 'default_code', 'code', 'partner_ref', 'active', 'product_tmpl_id', 'barcode', 'product_template_attribute_value_ids', 'product_template_variant_value_ids', 'combination_indices', 'is_product_variant', 'standard_price', 'volume', 'weight', 'pricelist_item_count', 'product_document_ids', 'product_document_count', 'packaging_ids', 'additional_product_tag_ids', 'all_product_tag_ids', 'can_image_variant_1024_be_zoomed', 'can_image_1024_be_zoomed', 'write_date', 'id', 'display_name', 'create_uid', 'create_date', 'write_uid', 'tax_string', 'stock_quant_ids', 'stock_move_ids', 'qty_available', 'virtual_available', 'free_qty', 'incoming_qty', 'outgoing_qty', 'orderpoint_ids', 'nbr_moves_in', 'nbr_moves_out', 'nbr_reordering_rules', 'reordering_min_qty', 'reordering_max_qty', 'putaway_rule_ids', 'storage_category_capacity_ids', 'show_on_hand_qty_status_button', 'show_forecasted_qty_status_button', 'valid_ean', 'lot_properties_definition', 'value_svl', 'quantity_svl', 'avg_cost', 'total_value', 'company_currency_id', 'stock_valuation_layer_ids', 'valuation', 'cost_method', 'sales_count', 'product_catalog_product_is_in_sale_order', 'name', 'sequence', 'description', 'description_purchase', 'description_sale', 'detailed_type', 'type', 'categ_id', 'currency_id', 'cost_currency_id', 'list_price', 'volume_uom_name', 'weight_uom_name', 'sale_ok', 'purchase_ok', 'uom_id', 'uom_name', 'uom_po_id', 'company_id', 'seller_ids', 'variant_seller_ids', 'color', 'attribute_line_ids', 'valid_product_template_attribute_line_ids', 'product_variant_ids', 'product_variant_id', 'product_variant_count', 'has_configurable_attributes', 'product_tooltip', 'priority', 'product_tag_ids', 'taxes_id', 'supplier_taxes_id', 'property_account_income_id', 'property_account_expense_id', 'account_tag_ids', 'fiscal_country_codes', 'responsible_id', 'property_stock_production', 'property_stock_inventory', 'sale_delay', 'tracking', 'description_picking', 'description_pickingout', 'description_pickingin', 'location_id', 'warehouse_id', 'has_available_route_ids', 'route_ids', 'route_from_categ_ids', 'service_type', 'sale_line_warn', 'sale_line_warn_msg', 'expense_policy', 'visible_expense_policy', 'invoice_policy', 'optional_product_ids', 'planning_enabled', 'planning_role_id', 'service_tracking', 'project_id', 'project_template_id', 'service_policy' ) );
					// $odooApi->addLog('variant Products : '. print_r($products, true));
					$products = json_decode( json_encode( $products->data->records ), true );
					
					$attrs = $odooApi->fetch_record_by_ids( 'product.template.attribute.line', $template['attribute_line_ids'], array( 'display_name', 'id', 'product_template_value_ids' ) );
					$attrs = json_decode( json_encode( $attrs->data->records ), true );
					// $odooApi->addLog('variant Products attribute line : '. print_r($attrs, true));
					foreach ( $products as $pkey => $product ) {
						$attr_and_value = array();
						foreach ( $product['product_template_attribute_value_ids'] as $attr => $attr_value ) {
							foreach ( $attrs as $key => $attr ) {
								foreach ( $attr['product_template_value_ids'] as $key => $value ) {
									if ( $value == $attr_value ) {
										$attr_and_value[ $attr['display_name'] ] = $attr_v[ $value ];
										
									}
								}
							}
							$products[ $pkey ]['attr_and_value']            = $attr_and_value;
							$products[ $pkey ]['attr_value'][ $attr_value ] = $attr_v[ $attr_value ];
							// $this->create_variation_product($template,$product);
						}
					}
					
					$products['attributes'] = $attrs;
					
					// $odooApi->addLog('variations ' . print_r($products, true));
					
					$product_id = $odoo_object->sync_product_from_odoo( $template, false, $products );
					
				} else {
					$product_id = $odoo_object->sync_product_from_odoo($template);
				}
				$missing_skus = get_option('_opmc_odoo_import_product_without_sku', array());
				
				foreach ($missing_skus as $key => $sku) {
					if ($sku['id'] == $item_id) {
						unset($missing_skus[$key]);
						
						update_option('_opmc_odoo_import_product_without_sku', $missing_skus);
						break;
					}
				}
			}
		}
			
		$synced_product = (int) get_option( 'opmc_odoo_product_remaining_import_count' );
			
		if ($synced_product < 1) {
			update_option('opmc_odoo_product_remaining_import_count', 0);
		} else {
			update_option('opmc_odoo_product_remaining_import_count', $synced_product - 1);
		}
		return false;
	}
		
		
	/**
	 * Complete
	 *
	 * Override if applicable, but ensure that the below actions are
	 * performed, or, call parent::complete().
	 */
	protected function complete() {
		parent::complete();
		$odooApi = new WC_ODOO_API();
			
		$import_product__without_sku = get_option('_opmc_odoo_import_product_without_sku', array());
		$import_product_without_sku_count = count($import_product__without_sku);
			
		$total_products_count = get_option( 'opmc_odoo_product_import_count' );
			
		$successfull_import_product_count = $total_products_count - $import_product_without_sku_count;
			
		// Show notice to user or perform some other arbitrary task...
		if ( $this->is_queue_empty() ) {
			update_option( 'opmc_odoo_product_import_running', false );
			// $total_products_count = get_option( 'opmc_odoo_product_import_count' );
				
			$odooApi->addLog( '[Products Import] [Completed] [Successfully imported  ' . print_r( $successfull_import_product_count, 1 ) . ' out of ' . print_r( $total_products_count, 1) . ' products ! ]' );
			if ($import_product_without_sku_count) {
				$odooApi->addLog( '[Products Import] [Error] [' . print_r( $import_product_without_sku_count, 1 ) . ' products could not be imported due to missing SKUs. Please review and update the SKU information for these products. ]' );
			}
				
		}
	}
		
	public function is_process_running() {
		return parent::is_process_running();
	}
		
	public function save() {
		parent::save();
		$this->data = array();
		return $this;
	}
}
