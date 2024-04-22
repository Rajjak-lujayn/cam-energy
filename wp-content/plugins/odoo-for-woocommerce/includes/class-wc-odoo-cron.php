<?php
class WC_ODOO_CRON {
	
	public  $timestamp;
	public function __construct() {
		add_action( 'add_option_woocommerce_woocommmerce_odoo_integration_settings', array( $this, 'add_option_cron' ), 10, 2 );
		add_action( 'update_option_woocommerce_woocommmerce_odoo_integration_settings', array( $this, 'update_product_cron' ), 10, 3 );
		add_action( 'odoo_process_inventory', array( $this, 'updateWooInventory' ) );
		add_action( 'odoo_process_order_refund', array( $this, 'updateWooRefundedOrder' ) );
		add_action( 'odoo_process_import_product_create', array( $this, 'do_odoo_import_product_create' ) );
		add_action( 'odoo_process_import_product_update', array( $this, 'do_odoo_import_product_update' ) );
		add_action( 'odoo_process_import_create_categories', array( $this, 'do_odoo_import_create_categories' ) );
		add_action( 'odoo_process_import_create_attributes', array( $this, 'do_odoo_import_create_attributes' ) );
		add_action( 'odoo_process_import_update_stocks', array( $this, 'do_odoo_import_update_stocks' ) );
		add_action( 'odoo_process_import_order', array( $this, 'do_odoo_import_order' ) );
		add_action( 'odoo_process_import_refund_order', array( $this, 'do_odoo_import_refund_order' ) );
		add_action( 'odoo_process_import_coupon', array( $this, 'do_odoo_import_coupon' ) );
		add_action( 'odoo_process_import_customer', array( $this, 'do_odoo_import_customer' ) );
		add_action( 'odoo_process_export_customer', array( $this, 'do_odoo_export_customer' ) );
		add_action( 'odoo_process_export_product_create', array( $this, 'do_odoo_export_product_create' ) );
		add_action( 'odoo_process_export_create_categories', array( $this, 'do_odoo_export_create_categories' ) );
		add_action( 'odoo_process_export_create_attributes', array( $this, 'do_odoo_export_create_attributes' ) );
		add_action( 'odoo_process_export_order', array( $this, 'do_odoo_export_order' ) );
		add_action( 'odoo_process_export_refund_order', array( $this, 'do_odoo_export_refund_order' ) );
		add_action( 'odoo_process_export_coupon', array( $this, 'do_odoo_export_coupon' ) );

		add_action( 'odoo_process_verify_odoo_creds', array( $this, 'verifyOdooCreds' ) );

		add_action( 'wp_ajax_opmc_odoo_product_import', array( $this, 'do_odoo_import_product_create' ) );
		add_action( 'wp_ajax_opmc_odoo_product_export', array( $this, 'do_odoo_export_product_create' ) );

		// add_action('do_odoo_import_create_categories', array($this, 'do_odoo_import_create_categories'));
		$ODOO_integrations   = get_option( 'woocommerce_woocommmerce_odoo_integration_settings' );
		$this->odoo_settings = $ODOO_integrations;
		$this->timestamp = array(
			'hourly' => strtotime('+1 hour'),
			'twicedaily' => strtotime('+12 hour'),
			'daily' => strtotime('+24 hour')
		);
	}

	public function add_option_cron( $v, $data ) {

		if ( ! wp_next_scheduled( 'odoo_process_verify_odoo_creds' ) ) {
			wp_schedule_event( $this->timestamp['twicedaily'], 'twicedaily', 'odoo_process_verify_odoo_creds' );
		}

		// if ( isset($data['odooInventorySync'] ) && ( 'yes' == $data['odooInventorySync'] && !wp_next_scheduled( 'odoo_process_inventory' ) ) ) {
		// wp_schedule_event( time(), $data['odooCronFrequency'], 'odoo_process_inventory' );

		// }

		// if ( isset($data['odooOrderRefundSync'] ) && ( 'yes' == $data['odooOrderRefundSync'] && !wp_next_scheduled( 'odoo_process_order_refund' ) ) ) {
		// wp_schedule_event( time(), $data['odooOrderRefundCronFrequency'], 'odoo_process_order_refund' );

		// }
		$import_product_list = array(
			isset( $data['odoo_import_create_product'] ) ? $data['odoo_import_create_product'] : '',
			isset( $data['odoo_import_update_product'] ) ? $data['odoo_import_update_product'] : '',
			isset( $data['odoo_import_update_stocks'] ) ? $data['odoo_import_update_stocks'] : '',
			isset( $data['odoo_import_update_price'] ) ? $data['odoo_import_update_price'] : '',
		);
		if ( in_array( 'yes', $import_product_list ) ) {
			wp_schedule_event( $this->timestamp[$data['odoo_import_create_product_frequency']], $data['odoo_import_create_product_frequency'], 'odoo_process_import_product_create' );
		}

		if ( isset( $data['odoo_import_create_categories'] ) && ( 'yes' == $data['odoo_import_create_categories'] && ! wp_next_scheduled( 'odoo_process_import_create_categories' ) ) ) {
			wp_schedule_event( $this->timestamp[$data['odoo_import_create_categories_frequency']], $data['odoo_import_create_categories_frequency'], 'odoo_process_import_create_categories' );

		}

		if ( isset( $data['odoo_import_create_attributes'] ) && ( 'yes' == $data['odoo_import_create_attributes'] && ! wp_next_scheduled( 'odoo_process_import_create_attributes' ) ) ) {
			wp_schedule_event( $this->timestamp[$data['odoo_import_create_attributes_frequency']], $data['odoo_import_create_attributes_frequency'], 'odoo_process_import_create_attributes' );
		}

		if ( isset( $data['odoo_import_customer'] ) && ( 'yes' == $data['odoo_import_customer'] && ! wp_next_scheduled( 'odoo_process_import_customer' ) ) ) {
			wp_schedule_event( $this->timestamp[$data['odoo_import_customer_frequency']], $data['odoo_import_customer_frequency'], 'odoo_process_import_customer' );

		}

		$import_order_list = array(
			isset( $data['odoo_import_update_order_status'] ) ? $data['odoo_import_update_order_status'] : '',
			isset( $data['odoo_import_order'] ) ? $data['odoo_import_order'] : '',
		);
		if ( in_array( 'yes', $import_order_list ) ) {
			wp_schedule_event( $this->timestamp[$data['odoo_import_order_frequency']], $data['odoo_import_order_frequency'], 'odoo_process_import_order' );
		}

		$import_coupon_list = array(
			isset( $data['odoo_import_coupon'] ) ? $data['odoo_import_coupon'] : '',
			isset( $data['odoo_import_coupon_update'] ) ? $data['odoo_import_coupon_update'] : '',
		);
		if ( in_array( 'yes', $import_coupon_list ) ) {
			wp_schedule_event( $this->timestamp[$data['odoo_import_coupon_frequency']], $data['odoo_import_coupon_frequency'], 'odoo_process_import_coupon' );
		}

		$export_product_list = array(
			isset( $data['odoo_export_create_product'] ) ? $data['odoo_export_create_product'] : '',
			isset( $data['odoo_export_update_product'] ) ? $data['odoo_export_update_product'] : '',
			isset( $data['odoo_export_update_stocks'] ) ? $data['odoo_export_update_stocks'] : '',
			isset( $data['odoo_export_update_price'] ) ? $data['odoo_export_update_price'] : '',
		);
		if ( in_array( 'yes', $export_product_list ) ) {
			wp_schedule_event( $this->timestamp[$data['odoo_export_create_product_frequency']], $data['odoo_export_create_product_frequency'], 'odoo_process_export_product_create' );
		}

		if ( isset( $data['odoo_export_create_categories'] ) && ( 'yes' == $data['odoo_export_create_categories'] && ! wp_next_scheduled( 'odoo_process_export_create_categories' ) ) ) {
			wp_schedule_event( $this->timestamp[$data['odoo_export_create_categories_frequency']], $data['odoo_export_create_categories_frequency'], 'odoo_process_export_create_categories' );

		}

		if ( isset( $data['odoo_export_create_attributes'] ) && ( 'yes' == $data['odoo_export_create_attributes'] && ! wp_next_scheduled( 'odoo_process_export_create_attributes' ) ) ) {
			wp_schedule_event( $this->timestamp[$data['odoo_export_create_attributes_frequency']], $data['odoo_export_create_attributes_frequency'], 'odoo_process_export_create_attributes' );
		}

		if ( isset( $data['odoo_export_customer'] ) && ( 'yes' == $data['odoo_export_customer'] && ! wp_next_scheduled( 'odoo_process_export_customer' ) ) ) {
			wp_schedule_event( $this->timestamp[$data['odoo_export_customer_frequency']], $data['odoo_export_customer_frequency'], 'odoo_process_export_customer' );

		}

		$export_order_list = array(
			isset( $data['odoo_export_update_order_status'] ) ? $data['odoo_export_update_order_status'] : '',
			isset( $data['odoo_export_order'] ) ? $data['odoo_export_order'] : '',
		);
		if ( in_array( 'yes', $export_order_list ) ) {
			wp_schedule_event( $this->timestamp[$data['odoo_export_order_frequency']], $data['odoo_export_order_frequency'], 'odoo_process_export_order' );
		}

		$export_coupon_list = array(
			isset( $data['odoo_export_coupon'] ) ? $data['odoo_export_coupon'] : '',
			isset( $data['odoo_export_coupon_update'] ) ? $data['odoo_export_coupon_update'] : '',
		);
		if ( in_array( 'yes', $export_coupon_list ) ) {
			wp_schedule_event( $this->timestamp[$data['odoo_export_coupon_frequency']], $data['odoo_export_coupon_frequency'], 'odoo_process_export_coupon' );
		}

		if ( isset( $data['odoo_import_refund_order'] ) && 'yes' == $data['odoo_import_refund_order'] ) {
			wp_schedule_event( $this->timestamp[$data['odoo_import_refund_order_frequency']], $data['odoo_import_refund_order_frequency'], 'odoo_process_import_refund_order' );
		}

		if ( isset( $data['odoo_export_refund_order'] ) && 'yes' == $data['odoo_export_refund_order'] ) {
			wp_schedule_event( $this->timestamp[$data['odoo_export_order_frequency']], $data['odoo_export_order_frequency'], 'odoo_process_export_refund_order' );
		}
	}
	/**
	 * [register_product_cron register a cron job to enable the product sync]
	 */
	public function update_product_cron( $old_values, $new_values, $option_name ) {

		if ( ! wp_next_scheduled( 'odoo_process_verify_odoo_creds' ) ) {
			wp_clear_scheduled_hook( 'odoo_process_verify_odoo_creds' );
			wp_schedule_event( $this->timestamp['twicedaily'], 'twicedaily', 'odoo_process_verify_odoo_creds' );
		}

		// if (( !wp_next_scheduled( 'odoo_process_inventory' ) ) || ( $old_values['odooCronFrequency'] != $new_values['odooCronFrequency'] )) {
		// wp_clear_scheduled_hook('odoo_process_inventory');
		// wp_schedule_event( time(), $new_values['odooCronFrequency'], 'odoo_process_inventory' );
		// }

		// if (( !wp_next_scheduled( 'odoo_process_order_refund' ) ) || ( $old_values['odooOrderRefundCronFrequency'] != $new_values['odooOrderRefundCronFrequency'] )) {
		// wp_clear_scheduled_hook('odoo_process_order_refund');
		// wp_schedule_event( time(), $new_values['odooOrderRefundCronFrequency'], 'odoo_process_order_refund' );
		// }
		$import_product_list = array( $new_values['odoo_import_create_product'], $new_values['odoo_import_update_product'], $new_values['odoo_import_update_stocks'], $new_values['odoo_import_update_price'] );

		if ( in_array( 'yes', $import_product_list ) ) {
			wp_clear_scheduled_hook( 'odoo_process_import_product_create' );
			wp_schedule_event( $this->timestamp[$new_values['odoo_import_create_product_frequency']], $new_values['odoo_import_create_product_frequency'], 'odoo_process_import_product_create' );
		} else {
			wp_clear_scheduled_hook( 'odoo_process_import_product_create' );
		}

		$export_product_list = array( $new_values['odoo_export_create_product'], $new_values['odoo_export_update_product'], $new_values['odoo_export_update_stocks'], $new_values['odoo_export_update_price'] );

		if ( in_array( 'yes', $export_product_list ) ) {
			wp_clear_scheduled_hook( 'odoo_process_export_product_create' );
			wp_schedule_event( $this->timestamp[$new_values['odoo_export_create_product_frequency']], $new_values['odoo_export_create_product_frequency'], 'odoo_process_export_product_create' );
		} else {
			wp_clear_scheduled_hook( 'odoo_process_export_product_create' );
		}

		// if ($new_values['odoo_import_update_product'] == 'yes' && (( !wp_next_scheduled( 'odoo_process_import_product_update' ) ) || ( $old_values['odoo_import_update_product'] != $new_values['odoo_import_update_product'] ))) {
		// wp_clear_scheduled_hook('odoo_process_import_product_update');
		// wp_schedule_event( time(), $new_values['odoo_import_update_product_frequency'], 'odoo_process_import_product_update' );
		// } else {
		// wp_clear_scheduled_hook('odoo_process_import_product_update');
		// }

		if ( 'yes' == $new_values['odoo_import_create_categories'] && ( ( ! wp_next_scheduled( 'odoo_process_import_create_categories' ) ) || ( $old_values['odoo_import_create_categories'] != $new_values['odoo_import_create_categories'] ) ) ) {
			wp_clear_scheduled_hook( 'odoo_process_import_create_categories' );
			wp_schedule_event( $this->timestamp[$new_values['odoo_import_create_categories_frequency']], $new_values['odoo_import_create_categories_frequency'], 'odoo_process_import_create_categories' );
		} else {
			wp_clear_scheduled_hook( 'odoo_process_import_create_categories' );
		}

		if ( 'yes' == $new_values['odoo_export_create_categories'] && ( ( ! wp_next_scheduled( 'odoo_process_export_create_categories' ) ) || ( $old_values['odoo_export_create_categories'] != $new_values['odoo_export_create_categories'] ) ) ) {
			wp_clear_scheduled_hook( 'odoo_process_export_create_categories' );
			wp_schedule_event( $this->timestamp[$new_values['odoo_export_create_categories_frequency']], $new_values['odoo_export_create_categories_frequency'], 'odoo_process_export_create_categories' );
		} else {
			wp_clear_scheduled_hook( 'odoo_process_export_create_categories' );
		}

		if ( 'yes' == $new_values['odoo_import_create_attributes'] ) {
			wp_clear_scheduled_hook( 'odoo_process_import_create_attributes' );
			wp_schedule_event( $this->timestamp[$new_values['odoo_import_create_attributes_frequency']], $new_values['odoo_import_create_attributes_frequency'], 'odoo_process_import_create_attributes' );
		} else {
			wp_clear_scheduled_hook( 'odoo_process_import_create_attributes' );
		}

		if ( 'yes' == $new_values['odoo_export_create_attributes'] ) {
			wp_clear_scheduled_hook( 'odoo_process_export_create_attributes' );
			wp_schedule_event( $this->timestamp[$new_values['odoo_export_create_attributes_frequency']], $new_values['odoo_export_create_attributes_frequency'], 'odoo_process_export_create_attributes' );
		} else {
			wp_clear_scheduled_hook( 'odoo_process_export_create_attributes' );
		}

		// if ($new_values['odoo_import_update_stocks'] == 'yes' && (( !wp_next_scheduled( 'do_odoo_import_update_stocks' ) ) || ( $old_values['odoo_import_update_stocks'] != $new_values['odoo_import_update_stocks'] ))) {
		// wp_clear_scheduled_hook('do_odoo_import_update_stocks');
		// wp_schedule_event( time(), $new_values['odoo_import_update_stocks_frequency'], 'do_odoo_import_update_stocks' );
		// } else {
		// wp_clear_scheduled_hook('do_odoo_import_update_stocks');
		// }

		// if ($new_values['odoo_import_update_price'] == 'yes' && (( !wp_next_scheduled( 'do_odoo_import_update_price' ) ) || ( $old_values['odoo_import_update_price'] != $new_values['odoo_import_update_price'] ))) {
		// wp_clear_scheduled_hook('do_odoo_import_update_price');
		// wp_schedule_event( time(), $new_values['odoo_import_update_price_frequency'], 'do_odoo_import_update_price' );
		// } else {
		// wp_clear_scheduled_hook('do_odoo_import_update_price');
		// }

		if ( 'yes' == $new_values['odoo_import_customer'] ) {
			wp_clear_scheduled_hook( 'odoo_process_import_customer' );
			wp_schedule_event( $this->timestamp[$new_values['odoo_import_customer_frequency']], $new_values['odoo_import_customer_frequency'], 'odoo_process_import_customer' );
		} else {
			wp_clear_scheduled_hook( 'odoo_process_import_customer' );
		}

		if ( 'yes' == $new_values['odoo_export_customer'] ) {
			wp_clear_scheduled_hook( 'odoo_process_export_customer' );
			wp_schedule_event( $this->timestamp[$new_values['odoo_export_customer_frequency']], $new_values['odoo_export_customer_frequency'], 'odoo_process_export_customer' );
		} else {
			wp_clear_scheduled_hook( 'odoo_process_export_customer' );
		}

		$import_coupon_list = array( $new_values['odoo_import_coupon'], $new_values['odoo_import_coupon_update'] );
		if ( in_array( 'yes', $import_coupon_list ) ) {
			wp_clear_scheduled_hook( 'odoo_process_import_coupon' );
			wp_schedule_event( $this->timestamp[$new_values['odoo_import_coupon_frequency']], $new_values['odoo_import_coupon_frequency'], 'odoo_process_import_coupon' );
		} else {
			wp_clear_scheduled_hook( 'odoo_process_import_coupon' );
		}

		$export_coupon_list = array( $new_values['odoo_export_coupon'], $new_values['odoo_export_coupon_update'] );
		if ( in_array( 'yes', $export_coupon_list ) ) {
			wp_clear_scheduled_hook( 'odoo_process_export_coupon' );
			wp_schedule_event( $this->timestamp[$new_values['odoo_export_coupon_frequency']], $new_values['odoo_export_coupon_frequency'], 'odoo_process_export_coupon' );
		} else {
			wp_clear_scheduled_hook( 'odoo_process_export_coupon' );
		}

		$new_values['odoo_import_update_order_status'] = 'no';
		$import_order_list                             = array( $new_values['odoo_import_update_order_status'], $new_values['odoo_import_order'] );
		if ( in_array( 'yes', $import_order_list ) ) {
			wp_clear_scheduled_hook( 'odoo_process_import_order' );
			wp_schedule_event( $this->timestamp[$new_values['odoo_import_order_frequency']], $new_values['odoo_import_order_frequency'], 'odoo_process_import_order' );
		} else {
			wp_clear_scheduled_hook( 'odoo_process_import_order' );
		}
		$new_values['odoo_export_update_order_status'] = 'no';
		$export_order_list                             = array( $new_values['odoo_export_update_order_status'], $new_values['odoo_export_order'] );
		if ( in_array( 'yes', $export_order_list ) ) {
			wp_clear_scheduled_hook( 'odoo_process_export_order' );
			wp_schedule_event( $this->timestamp[$new_values['odoo_export_order_frequency']], $new_values['odoo_export_order_frequency'], 'odoo_process_export_order' );
		} else {
			wp_clear_scheduled_hook( 'odoo_process_export_order' );
		}

		if ( isset( $new_values['odoo_import_refund_order'] ) && 'yes' == $new_values['odoo_import_refund_order'] ) {
			wp_clear_scheduled_hook( 'odoo_process_import_refund_order' );
			wp_schedule_event( $this->timestamp[$new_values['odoo_import_refund_order_frequency']], $new_values['odoo_import_refund_order_frequency'], 'odoo_process_import_refund_order' );
		} else {
			wp_clear_scheduled_hook( 'odoo_process_import_refund_order' );

		}

		if ( isset( $new_values['odoo_export_refund_order'] ) && 'yes' == $new_values['odoo_export_refund_order'] ) {
			wp_clear_scheduled_hook( 'odoo_process_export_refund_order' );
			wp_schedule_event( $this->timestamp[$new_values['odoo_export_order_frequency']], $new_values['odoo_export_order_frequency'], 'odoo_process_export_refund_order' );
		} else {
			wp_clear_scheduled_hook( 'odoo_process_export_refund_order' );

		}
	}

	public function verifyOdooCreds() {
		$odooApi              = new WC_ODOO_API();
		$odoo_imp_exp_running = array(
			'order_import'    => get_option( 'opmc_odoo_order_import_running' ),
			'order_export'    => get_option( 'opmc_odoo_order_export_running' ),
			'product_import'  => get_option( 'opmc_odoo_product_import_running' ),
			'product_export'  => get_option( 'opmc_odoo_product_export_running' ),
			'customer_import' => get_option( 'opmc_odoo_customer_import_running' ),
			'customer_export' => get_option( 'opmc_odoo_customer_export_running' ),
		);
		if ( $odoo_imp_exp_running['order_import'] ||
			 $odoo_imp_exp_running['order_export'] ||
			 $odoo_imp_exp_running['product_import'] ||
			 $odoo_imp_exp_running['product_export'] ||
			 $odoo_imp_exp_running['customer_import'] ||
			 $odoo_imp_exp_running['customer_export'] ) {
			$odooApi->addLog( 'Odoo Improt/Export cron is running. Odoo Creds verify aborted.' );

			return false;
		} else {
			$response = $odooApi->varifyOdooConnection();
			if ( $response->success ) {
				update_option( 'opmc_odoo_access_token', $response->data->token );
				update_option( 'opmc_odoo_authenticated_uid', $response->data->odoo_uid );
				delete_option( '_opmc_odoo_access_error' );
				return true;
			} else {
				update_option( '_opmc_odoo_access_error', $response->data->error_code );
				update_option( 'opmc_odoo_authenticated_uid', false );
				// $this->opmc_admin_notice($response->data->error_code);
				return false;
			}
		}
	}

	/**
	 * [updateWooInventory Update and create the product on the woocommerce from the Odoo data]
	 */
	public function updateWooInventory() {
		$common_functions = new WC_ODOO_Common_Functions();
		if ( $common_functions->is_authenticate() ) {
			$odooApi = new WC_ODOO_API();
			if ( $odooApi->is_multi_company() ) {
				$multi_company_func = '/multi-company-files';
			} else {
				$multi_company_func = '';
			}
			require_once WC_ODOO_INTEGRATION_PLUGINDIR . '/includes' . $multi_company_func . '/class-wc-odoo-functions.php';
			$odoo_object = new WC_ODOO_Functions();
			$odoo_object->inventory_sync();
		}
	}

	public function updateWooRefundedOrder() {
		$common_functions = new WC_ODOO_Common_Functions();
		if ( $common_functions->is_authenticate() ) {
			$odooApi = new WC_ODOO_API();
			if ( $odooApi->is_multi_company() ) {
				$multi_company_func = '/multi-company-files';
			} else {
				$multi_company_func = '';
			}
			require_once WC_ODOO_INTEGRATION_PLUGINDIR . '/includes' . $multi_company_func . '/class-wc-odoo-functions.php';
			$odoo_object = new WC_ODOO_Functions();
			$odoo_object->sync_refund_order();
		}
	}

	public function do_odoo_import_product_create() {
		$common_functions = new WC_ODOO_Common_Functions();
		if ( $common_functions->is_authenticate() ) {

			$odooApi = new WC_ODOO_API();
			if ( $odooApi->is_multi_company() ) {
				$multi_company_func = '/multi-company-files';
				// $odooApi->addLog('multi-company');
			} else {
				// $odooApi->addLog('single-company');
				$multi_company_func = '';
			}
			require_once WC_ODOO_INTEGRATION_PLUGINDIR . '/includes' . $multi_company_func . '/class-wc-odoo-functions.php';
			$odoo_object = new WC_ODOO_Functions();
			$odoo_object->import_product_odoo();
		}
	}

	public function do_odoo_import_create_categories() {
		$common_functions = new WC_ODOO_Common_Functions();
		if ( $common_functions->is_authenticate() && 'yes' == $this->odoo_settings['odoo_import_create_categories'] ) {
			$odooApi = new WC_ODOO_API();
			if ( $odooApi->is_multi_company() ) {
				$multi_company_func = '/multi-company-files';
			} else {
				$multi_company_func = '';
			}
			require_once WC_ODOO_INTEGRATION_PLUGINDIR . '/includes' . $multi_company_func . '/class-wc-odoo-functions.php';
			$odoo_object = new WC_ODOO_Functions();
			$odoo_object->do_import_categories();
		}
	}

	public function do_odoo_import_create_attributes() {
		$common_functions = new WC_ODOO_Common_Functions();
		if ( $common_functions->is_authenticate() ) {
			$odooApi = new WC_ODOO_API();
			if ( $odooApi->is_multi_company() ) {
				$multi_company_func = '/multi-company-files';
			} else {
				$multi_company_func = '';
			}
			require_once WC_ODOO_INTEGRATION_PLUGINDIR . '/includes' . $multi_company_func . '/class-wc-odoo-functions.php';
			$odoo_object = new WC_ODOO_Functions();
			$odoo_object->do_import_attributes();
		}
	}

	public function do_odoo_import_order() {
		$common_functions = new WC_ODOO_Common_Functions();
		if ( $common_functions->is_authenticate() ) {
			$odooApi = new WC_ODOO_API();
			if ( $odooApi->is_multi_company() ) {
				$multi_company_func = '/multi-company-files';
			} else {
				$multi_company_func = '';
			}
			require_once WC_ODOO_INTEGRATION_PLUGINDIR . '/includes' . $multi_company_func . '/class-wc-odoo-functions.php';
			$odoo_object = new WC_ODOO_Functions();
			$odoo_object->do_import_order();
		}
	}

	public function do_odoo_import_refund_order() {

		$common_functions = new WC_ODOO_Common_Functions();
		if ( $common_functions->is_authenticate() ) {

			$odooApi = new WC_ODOO_API();
			if ( $odooApi->is_multi_company() ) {
				$multi_company_func = '/multi-company-files';
			} else {
				$multi_company_func = '';
			}
			require_once WC_ODOO_INTEGRATION_PLUGINDIR . '/includes' . $multi_company_func . '/class-wc-odoo-functions.php';
			$odoo_object = new WC_ODOO_Functions();
			$odoo_object->sync_refund_order();
		}
	}

	public function do_odoo_import_coupon() {
		$common_functions = new WC_ODOO_Common_Functions();

		if ( $common_functions->is_authenticate() ) {
			$odooApi = new WC_ODOO_API();
			if ( $odooApi->is_multi_company() ) {
				$multi_company_func = '/multi-company-files';
			} else {
				$multi_company_func = '';
			}
			require_once WC_ODOO_INTEGRATION_PLUGINDIR . '/includes' . $multi_company_func . '/class-wc-odoo-functions.php';
			$odoo_object = new WC_ODOO_Functions();
			$odoo_object->do_import_coupon();
		}
	}


	public function do_odoo_import_customer() {

		$common_functions = new WC_ODOO_Common_Functions();
		if ( $common_functions->is_authenticate() ) {
			$odooApi = new WC_ODOO_API();
			if ( $odooApi->is_multi_company() ) {
				$multi_company_func = '/multi-company-files';
			} else {
				$multi_company_func = '';
			}
			require_once WC_ODOO_INTEGRATION_PLUGINDIR . '/includes' . $multi_company_func . '/class-wc-odoo-functions.php';
			$odoo_object = new WC_ODOO_Functions();
			$odoo_object->do_import_customer();
		}
	}

	public function do_odoo_export_product_create() {
		$common_functions = new WC_ODOO_Common_Functions();
		if ( $common_functions->is_authenticate() ) {
			$odooApi = new WC_ODOO_API();
			if ( $odooApi->is_multi_company() ) {
				$multi_company_func = '/multi-company-files';
			} else {
				$multi_company_func = '';
			}
			require_once WC_ODOO_INTEGRATION_PLUGINDIR . '/includes' . $multi_company_func . '/class-wc-odoo-functions.php';
			$odoo_object = new WC_ODOO_Functions();
			$odoo_object->do_export_product_odoo();
		}
	}

	public function do_odoo_export_create_categories() {
		$common_functions = new WC_ODOO_Common_Functions();
		if ( $common_functions->is_authenticate() ) {
			$odooApi = new WC_ODOO_API();
			if ( $odooApi->is_multi_company() ) {
				$multi_company_func = '/multi-company-files';
			} else {
				$multi_company_func = '';
			}
			require_once WC_ODOO_INTEGRATION_PLUGINDIR . '/includes' . $multi_company_func . '/class-wc-odoo-functions.php';
			$odoo_object = new WC_ODOO_Functions();
			$odoo_object->do_export_categories();
		}
	}

	public function do_odoo_export_create_attributes() {
		$common_functions = new WC_ODOO_Common_Functions();
		if ( $common_functions->is_authenticate() ) {
			$odooApi = new WC_ODOO_API();
			if ( $odooApi->is_multi_company() ) {
				$multi_company_func = '/multi-company-files';
			} else {
				$multi_company_func = '';
			}
			require_once WC_ODOO_INTEGRATION_PLUGINDIR . '/includes' . $multi_company_func . '/class-wc-odoo-functions.php';
			$odoo_object = new WC_ODOO_Functions();
			$odoo_object->do_export_attributes();
		}
	}

	public function do_odoo_export_order() {
		$common_functions = new WC_ODOO_Common_Functions();
		if ( $common_functions->is_authenticate() ) {
			$odooApi = new WC_ODOO_API();
			if ( $odooApi->is_multi_company() ) {
				$multi_company_func = '/multi-company-files';
			} else {
				$multi_company_func = '';
			}
			require_once WC_ODOO_INTEGRATION_PLUGINDIR . '/includes' . $multi_company_func . '/class-wc-odoo-functions.php';
			$odoo_object = new WC_ODOO_Functions();
			$odoo_object->do_export_order();
		}
	}

	public function do_odoo_export_refund_order() {
		$common_functions = new WC_ODOO_Common_Functions();
		if ( $common_functions->is_authenticate() ) {
			$odooApi = new WC_ODOO_API();
			if ( $odooApi->is_multi_company() ) {
				$multi_company_func = '/multi-company-files';
			} else {
				$multi_company_func = '';
			}
			require_once WC_ODOO_INTEGRATION_PLUGINDIR . '/includes' . $multi_company_func . '/class-wc-odoo-functions.php';
			$odoo_object = new WC_ODOO_Functions();
			$odoo_object->do_export_refund_order();
		}
	}

	public function do_odoo_export_coupon() {
		$common_functions = new WC_ODOO_Common_Functions();
		if ( $common_functions->is_authenticate() ) {
			$odooApi = new WC_ODOO_API();
			if ( $odooApi->is_multi_company() ) {
				$multi_company_func = '/multi-company-files';
			} else {
				$multi_company_func = '';
			}
			require_once WC_ODOO_INTEGRATION_PLUGINDIR . '/includes' . $multi_company_func . '/class-wc-odoo-functions.php';
			$odoo_object = new WC_ODOO_Functions();
			$odoo_object->do_export_coupon();
		}
	}

	public function do_odoo_export_customer() {
		$common_functions = new WC_ODOO_Common_Functions();
		if ( $common_functions->is_authenticate() ) {
			$odooApi = new WC_ODOO_API();
			if ( $odooApi->is_multi_company() ) {
				$multi_company_func = '/multi-company-files';
			} else {
				$multi_company_func = '';
			}
			require_once WC_ODOO_INTEGRATION_PLUGINDIR . '/includes' . $multi_company_func . '/class-wc-odoo-functions.php';
			$odoo_object = new WC_ODOO_Functions();
			$odoo_object->do_export_customer();
		}
	}
}
new WC_ODOO_CRON();
