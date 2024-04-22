<?php
	
	/**
	 * Import Products from Odoo to Woo in background process.
	 */
class Opmc_Product_Export extends WP_Background_Process {
		
		
		
	protected $action = 'cron_odoo_export_product_process';
		
	/**
	 * Export Products to Odoo
	 *
	 * @param  [type] $item [description]
	 *
	 * @return [type]       [description]
	 */
	protected function task( $item ) {
		$odooApi = new WC_ODOO_API();
			
		if ($odooApi->is_multi_company()) {
			$multi_company_func = '/multi-company-files';
		} else {
			$multi_company_func = '';
		}
			
		require_once WC_ODOO_INTEGRATION_PLUGINDIR . 'includes' . $multi_company_func . '/class-wc-odoo-functions.php';
			
		$odoo_object = new WC_ODOO_Functions();
		$helper  = WC_ODOO_Helpers::getHelper();
		$product = wc_get_product($item);
			
		if (!$product) {
			return false;
		}
			
		$is_product_exportable = $helper->is_product_exportable( $product->get_id() );
			
		if ($is_product_exportable) {
			$odoo_object->sync_product_to_odoo($item);
				
			$products_without_sku = get_option('_opmc_odoo_export_products_without_sku', array());
				
			foreach ($products_without_sku as $key => $product_info) {
				if ($product_info['id'] == $product->get_id()) {
					unset($products_without_sku[$key]);
					update_option('_opmc_odoo_export_products_without_sku', $products_without_sku);
					break;
				}
			}
		} else {
			$odooApi->addLog('[Products Export] [Error] [Product export for ' . $product->get_name() . ' is unsuccessful due to missing SKU. ] ');
				
			$products_without_sku = get_option('_opmc_odoo_export_products_without_sku', array());
			$product_exists_in_array = false;
				
			foreach ($products_without_sku as $key => $product_info) {
				if ($product_info['id'] == $product->get_id()) {
					$products_without_sku[$key]['name'] = $product->get_name();
					$product_exists_in_array = true;
					break;
				}
			}
				
			if (!$product_exists_in_array) {
				$products_without_sku[] = array(
					'id'   => $product->get_id(),
					'name' => $product->get_name(),
				);
			}
				
			update_option('_opmc_odoo_export_products_without_sku', $products_without_sku);
		}
			
		$synced_product = get_option('opmc_odoo_product_export_remaining_count');
		$total_products = get_option('opmc_odoo_product_export_count');
			
		if ($synced_product < 1) {
			update_option('opmc_odoo_product_export_remaining_count', 0);
		} else {
			update_option('opmc_odoo_product_export_remaining_count', $synced_product - 1);
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
			
		$total_products = get_option('opmc_odoo_product_export_count');
			
		$export_product__without_sku = get_option('_opmc_odoo_export_products_without_sku', array());
		$export_product_without_sku_count = count($export_product__without_sku);
			
		$successfull_export_product_count = $total_products - $export_product_without_sku_count;
			
		// Show notice to user or perform some other arbitrary task...
		if ($this->is_queue_empty()) {
			update_option('opmc_odoo_product_export_running', 0);
			// $total_products = get_option('opmc_odoo_product_export_count');
			$odooApi->addLog( '[Products Export] [Completed] [Successfully exported ' . print_r( $successfull_export_product_count, 1 ) . ' out of ' . print_r( $total_products, 1) . ' products ! ]' );
			if ($export_product_without_sku_count) {
				$odooApi->addLog( '[Products Export] [Error] [' . print_r( $export_product_without_sku_count, 1 ) . ' products could not be exported due to missing SKUs. Please review and update the SKU information for these products. ]' );
			}
		}
	}
		
	/**
	 * Checks if the queue is empty.
	 * Delegates the heavy lifting to the parent's is_queue_empty() method.
	 *
	 * @return bool True if empty, false otherwise.
	 */
	public function is_queue_empty() {
		return parent::is_queue_empty();
	}
		
	/**
	 * Checks if the process is running.
	 * It simply calls the parent's is_process_running() method.
	 *
	 * @return bool True if running, false otherwise.
	 */
	public function is_process_running() {
		return parent::is_process_running();
	}
		
	/**
	 * Empties the data.
	 * It's like a forgetful person, wipes the slate clean, leaving nothing behind.
	 *
	 * @return void
	 */
	public function empty_data() {
		$this->data = array();
	}
		
	/**
	 * Saves the data and then empties the data array.
	 * Returns itself for potential method chaining.
	 *
	 * @return $this The current instance.
	 */
	public function save() {
		parent::save();
		$this->data = array();
			
		return $this;
	}
}
