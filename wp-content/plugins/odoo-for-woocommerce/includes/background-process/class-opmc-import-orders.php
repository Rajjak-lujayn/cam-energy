<?php

/**
 * Import Orders from Odoo to Woo in background process.
 */
class Opmc_Order_Import extends WP_Background_Process {


	protected $action = 'cron_odoo_import_order_process';

	/**
	 * Import order to WooCommerce from Odoo
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

		// $odooApi->addLog('items Id : ' . print_r($item_id, 1));

		$item_data = $odooApi->fetch_record_by_id( 'sale.order', $item_id, array( 'id', 'name', 'origin', 'state', 'date_order', 'partner_id', 'partner_invoice_id', 'partner_shipping_id', 'order_line', 'invoice_ids', 'amount_total', 'amount_tax', 'type_name', 'display_name' ) );

		// $odooApi->addLog('items data : '. print_r($item_data, 1));

		if ( is_array( $item_data ) ) {
			$order = $item_data[0];
			$odoo_object->odoo_import_order( $order );

			$synced_order = get_option( 'opmc_odoo_order_remaining_import_count' );
			if ( $synced_order < 1 ) {
				update_option( 'opmc_odoo_order_remaining_import_count', 0 );
			} else {
				update_option( 'opmc_odoo_order_remaining_import_count', $synced_order - 1 );
			}
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
		// Show notice to user or perform some other arbitrary task...
		if ( $this->is_queue_empty() ) {
			update_option( 'opmc_odoo_order_import_running', false );
			$total_orders = get_option( 'opmc_odoo_order_import_count' );
			$odooApi->addLog( '[Orders Import] [Complete] [Order Import has been completed for ' . print_r( $total_orders, 1 ) . ' Orders.]' );
		}
	}

	public function is_queue_empty() {
		return parent::is_queue_empty();
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
