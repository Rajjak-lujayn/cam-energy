<?php

/**
 * Import Customers from Odoo to Woo in background process.
 */
class Opmc_Customer_Import extends WP_Background_Process {


	protected $action = 'cron_odoo_import_customer_process';

	/**
	 * Import customer to WooCommerce from Odoo
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


		$customer_data  = $odooApi->fetch_record_by_id( 'res.partner', $item_id, array() );

		if (is_array($customer_data)) {
			$customer = $customer_data[0];
			if ( isset( $customer['email'] ) && ! empty( $customer['email'] ) ) {
				$address_lists = array();
				if ( count( $customer['child_ids'] ) > 0 ) {
					foreach ( $customer['child_ids'] as $key => $child_ids ) {
						$address_res = $odooApi->fetch_record_by_ids( 'res.partner', $child_ids, array( 'id', 'name', 'display_name', 'website', 'mobile', 'email', 'is_company', 'phone', 'image_medium', 'street', 'street2', 'zip', 'city', 'state_id', 'country_id', 'child_ids', 'type' ) );

						$address_res     = json_decode( json_encode( $address_res->data->records ), true );
						$address_lists[] = $address_res[0];
					}
					if ( 0 > count( $address_lists ) ) {
						return false;
					}
					// $odooApi->addLog('Customer addresses : '. print_r($address_lists, true));
				}
				$cusomter_id = $odoo_object->sync_customer_to_wc( $customer, $address_lists );
				if ( $cusomter_id ) {
					$synced_customer = get_option( 'opmc_odoo_customer_remaining_import_count' );
					if ( $synced_customer < 1 ) {
						update_option( 'opmc_odoo_customer_remaining_import_count', 0 );
					} else {
						update_option( 'opmc_odoo_customer_remaining_import_count', $synced_customer - 1 );
					}
				}
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
		// Show notice to user or perform some other arbitrary task...
		if ( $this->is_queue_empty() ) {
			$odooApi = new WC_ODOO_API();
			update_option( 'opmc_odoo_customer_import_running', false );
			$total_customers = get_option( 'opmc_odoo_customer_import_count' );
			$odooApi->addLog( '[Customer Import] [Complete] [Customer Import has been completed for ' . print_r( $total_customers, 1 ) . ' Customers.]' );
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
