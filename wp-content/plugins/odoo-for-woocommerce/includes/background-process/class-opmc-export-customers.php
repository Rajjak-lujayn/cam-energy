<?php


/**
 * Export Customers from Odoo to Woo in background process.
 */
class Opmc_Customer_Export extends WP_Background_Process {


	// Define the action hook for exporting customers.
	protected $action = 'cron_odoo_export_customer_process';

	// Define the maximum number of items to process per batch.
	public $batch_size = 4;

	// Define the timeout for each batch process.
	// protected $timeout = 30;


	/**
	 * Export customers to WooCommerce from Odoo
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
		// $odooApi->addLog('ITem ID: ' . print_r($item_id, 1));

		$args = array(
			'role'         => 'customer',
			'include'      => array( $item_id ),
			'number'       => 1,
		);
		
		$user_query = new WP_User_Query( $args );
		if ( ! empty( $user_query->results ) ) {
			$customer = $user_query->results[0];
		} else {
			$customer = null;
		}

		$customer_id = get_user_meta( $item_id, '_odoo_id', true );
		if ( ! $customer_id ) {
			$conditions  = array(
			array(
			'field_key'   => 'type',
			'field_value' => 'contact',
			),
			array(
			'field_key'   => 'email',
			'field_value' => $customer->user_email,
			),
			);
			$customer_id = $odoo_object->search_odoo_customer( $conditions, $item_id );
		}

		if ( $customer_id ) {
			$odoo_object->update_customer_to_odoo( (int) $customer_id, $customer );
		} else {
			$customer_id = $odoo_object->create_customer( $customer );
		}
		if ( false == $customer_id ) {
			return false;
		}
			update_user_meta( $customer->ID, '_odoo_id', $customer_id );
			$odoo_object->action_woocommerce_customer_save_address( $customer->ID, 'shipping' );
			$odoo_object->action_woocommerce_customer_save_address( $customer->ID, 'billing' );

		if ( $customer_id ) {
			$synced_customers = get_option( 'opmc_odoo_customer_remaining_export_count' );
			if ( $synced_customers < 1 ) {
				update_option( 'opmc_odoo_customer_remaining_export_count', 0 );
			} else {
				update_option( 'opmc_odoo_customer_remaining_export_count', $synced_customers - 1 );
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
			update_option( 'opmc_odoo_customer_export_running', false );
			$total_customers = get_option( 'opmc_odoo_customer_export_count' );
			$odooApi->addLog( '[Customer Export] [Complete] [Customer Export has been completed for ' . print_r( $total_customers, 1 ) . ' Customers.]' );
		}
	}

	public function is_queue_empty() {
		return parent::is_queue_empty();
	}

	public function is_process_running() {
		return parent::is_process_running();
	}

	public function empty_data() {
		$this->data = array();
	}
	
	public function get_batch() {
		return parent::get_batch();
	}

	public function save() {
		parent::save();
		$this->data = array();
		return $this;
	}
}
