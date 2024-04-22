<?php

require_once WC_ODOO_INTEGRATION_PLUGINDIR . 'includes/opmc-hpos-compatibility-helper.php';
/**
 * Export Orders from Odoo to Woo in background process.
 */
class Opmc_Order_Export extends WP_Background_Process {


	protected $action = 'cron_odoo_export_order_process';

	/**
	 * Export order to WooCommerce from Odoo
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

		// $odooApi->addLog( 'order ID : ' . print_r( $item_id, 1 ) );

		$order_id = opmc_hpos_get_post_meta( $item_id, '_odoo_order_id', true );
		if ( ! $order_id ) {
			$odoo_object->order_create( $item_id );
			$synced_order = get_option( 'opmc_odoo_order_remaining_export_count' );
			if ( $synced_order < 1 ) {
				update_option( 'opmc_odoo_order_remaining_export_count', 0 );
			} else {
				update_option( 'opmc_odoo_order_remaining_export_count', $synced_order - 1 );
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
			$odooApi = new WC_ODOO_API();
			update_option( 'opmc_odoo_order_export_running', false );
			$total_orders = get_option( 'opmc_odoo_order_export_count' );
//          $odooApi->addLog( '[Orders Export] [Complete] [Order Export has been completed for ' . print_r( $total_orders, 1 ) . ' Orders.]' );
			$odooApi->addLog( '[Orders Export] [Complete] [Order Export process queue has been completed]' );
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
