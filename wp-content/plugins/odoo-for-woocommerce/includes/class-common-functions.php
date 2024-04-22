<?php

require_once 'opmc-hpos-compatibility-helper.php';

class WC_ODOO_Common_Functions {

	public $odoo_url;
	public $odoo_db;
	public $odoo_username;
	public $odoo_password;
	public $creds;
	public $odoo_tax;
	public $odoo_shipping_tax;
	public $odoo_invoice_journal;
	public $opmc_odoo_access_token;
	public $settings_changed;
	public $plugin_updated;

	public function __construct() {

		$this->_setCredentials();
//		$this->opmc_odoo_access_token = get_option( 'opmc_odoo_access_token', false );
		// General.
		add_filter( 'woocommerce_email_recipient_customer_completed_order', array( $this, 'opmc_odoo_disable_import_order_email' ), 10, 2 );
	}


	private function _setCredentials() {
		$this->creds = get_option( 'woocommerce_woocommmerce_odoo_integration_settings' );
		// pr($this->creds);
		$this->odoo_url             = isset( $this->creds['client_url'] ) ? rtrim( $this->creds['client_url'], '/' ) : '';
		$this->odoo_db              = isset( $this->creds['client_db'] ) ? $this->creds['client_db'] : '';
		$this->odoo_username        = isset( $this->creds['client_username'] ) ? $this->creds['client_username'] : '';
		$this->odoo_password        = isset( $this->creds['client_password'] ) ? $this->creds['client_password'] : '';
		$this->odoo_tax             = isset( $this->creds['odooTax'] ) ? $this->creds['odooTax'] : '';
		$this->odoo_shipping_tax    = isset( $this->creds['shippingOdooTax'] ) ? $this->creds['shippingOdooTax'] : '';
		$this->odoo_invoice_journal = isset( $this->creds['invoiceJournal'] ) ? $this->creds['invoiceJournal'] : '';
		$this->settings_changed     = ( get_option( 'is_opmc_odoo_settings_changed' ) ) ? get_option( 'is_opmc_odoo_settings_changed' ) : 0;
		$this->plugin_updated       = ( get_option( 'wc_opmc_odoo_update_state' ) ) ? get_option( 'wc_opmc_odoo_update_state' ) : 0;
		$this->opmc_odoo_access_token = get_option( 'opmc_odoo_access_token', false );
	}

	public function gettingStarted() {
		$this->_setCredentials();
		$url = $this->get_settings_url();
		if ( '' == $this->odoo_url || '' == $this->odoo_db || '' == $this->odoo_username || '' == $this->odoo_password ) {
			/* translators: 1: Strong Tag start, 2: Strong Tag end, 3: link start 4: link end. */
			echo '<div class="notice notice-error"><p>' . sprintf( esc_html__( '%1$s WooCommerce Odoo Integration is almost ready. %2$s To get started, %3$s go to Odoo Integration >> Settings tab %4$s and Set Odoo details and click save button.', 'wc-odoo-integration' ), '<strong>', '</strong>', '<a href="' . esc_url( $url ) . '">', '</a>' ) . '</p></div>' . "\n";
		} elseif ( false != $this->opmc_odoo_access_token && ( '' == $this->odoo_tax || '' == $this->odoo_shipping_tax || '' == $this->odoo_invoice_journal ) ) {
			/* translators: 1: Strong Tag start, 2: Strong Tag end, 3: link start 4: link end. */
			echo '<div class="notice notice-warning"><p>' . sprintf( esc_html__( '%1$s WooCommerce Odoo Integration is almost ready. %2$s To get started, %3$s go to Odoo Integration >> Configurations tab %4$s and %1$s Set Odoo Tax, Shipping Tax details and Sale Invoice Journal %2$s and click save button.', 'wc-odoo-integration' ), '<strong>', '</strong>', '<a href="' . esc_url( $url ) . '">', '</a>' ) . '</p></div>' . "\n";
		}
	}

	public function isCredsDefined() {
		if ( ! empty( $this->odoo_url ) && ! empty( $this->odoo_db ) && ! empty( $this->odoo_username ) && ! empty( $this->odoo_password ) ) {
			return true;
		} else {
			return false;
		}
	}

	public function opmc_odoo_disable_import_order_email( $recipient, $order ) {
		$opmc_odoo_ordder_id = opmc_hpos_get_post_meta( $order->get_id(), '_odoo_order_id', true );
		if ( $opmc_odoo_ordder_id ) {
			$recipient = '';
		}
		return $recipient;
	}

	/**
	 * Admin Notice callback for import status
	 */
	public function product_import_status() {
		$import_running = get_option( 'opmc_odoo_product_import_running' );
		$total          = get_option( 'opmc_odoo_product_import_count' );
		$remaining      = get_option( 'opmc_odoo_product_remaining_import_count', 0 );
		$synced_product = $total - $remaining;

		// Generate the dynamic notice content based on the data
		$notice_content = '<div class="notice notice-info is-dismissible">';
		$notice_content .= '<p id="opmc_odoo_product_import" class="opmc-odoo-sync-notices">' . esc_html__('Odoo product import is in process. ', 'wc-odoo-integration');
		if (1 == $synced_product) {
			/* translators: 1: imported value, 2: total value */
			$notice_content .= '<span class="opmc-odoo-product-import-status">' . sprintf(__('%1$d of %2$d product imported.', 'wc-odoo-integration'), esc_html($synced_product), esc_html($total )) . '</span>';
		} else {
			/* translators: 1: imported value, 2: total value */
			$notice_content .= '<span class="opmc-odoo-product-import-status">' . sprintf(__('%1$d of %2$d products imported.', 'wc-odoo-integration'), esc_html($synced_product), esc_html($total)) . '</span>';
		}
		$notice_content .= '</p></div>';

		if ( $import_running ) {
			echo wp_kses_post( $notice_content );
		}
	}

	/**
	 * Admin Notice callback for import status
	 */
	public function customer_import_status() {
		$import_running = get_option( 'opmc_odoo_customer_import_running' );
		$total          = get_option( 'opmc_odoo_customer_import_count' );
		$remaining      = get_option( 'opmc_odoo_customer_remaining_import_count', 0 );
		$synced_items   = $total - $remaining;

		// Generate the dynamic notice content based on the data
		$notice_content = '<div class="notice notice-info is-dismissible">';
		$notice_content .= '<p id="opmc_odoo_customer_import" class="opmc-odoo-sync-notices">' . esc_html__('Odoo customer import is in process. ', 'wc-odoo-integration');
		if (1 == $synced_items) {
			/* translators: 1: imported value, 2: total value */
			$notice_content .= '<span class=""opmc-odoo-customer-import-status">' . sprintf(__('%1$d of %2$d customer imported.', 'wc-odoo-integration'), esc_html($synced_items), esc_html($total )) . '</span>';
		} else {
			/* translators: 1: imported value, 2: total value */
			$notice_content .= '<span class=""opmc-odoo-customer-import-status">' . sprintf(__('%1$d of %2$d customers imported.', 'wc-odoo-integration'), esc_html($synced_items), esc_html($total)) . '</span>';
		}
		$notice_content .= '</p></div>';

		if ( $import_running ) {
			echo wp_kses_post( $notice_content );
		}
	}

	/**
	 * Admin Notice callback for import status
	 */
	public function order_import_status() {
		$import_running = get_option( 'opmc_odoo_order_import_running' );
		$total          = get_option( 'opmc_odoo_order_import_count' );
		$remaining      = get_option( 'opmc_odoo_order_remaining_import_count', 0 );
		$synced_items   = $total - $remaining;

		// Generate the dynamic notice content based on the data
		$notice_content = '<div class="notice notice-info is-dismissible">';
		$notice_content .= '<p id="opmc_odoo_order_import" class="opmc-odoo-sync-notices">' . esc_html__('Odoo order import is in process. ', 'wc-odoo-integration');
		if (1 == $synced_items) {
			/* translators: 1: imported value, 2: total value */
			$notice_content .= '<span class="opmc-odoo-order-import-status">' . sprintf(__('%1$d of %2$d order imported.', 'wc-odoo-integration'), esc_html($synced_items), esc_html($total )) . '</span>';
		} else {
			/* translators: 1: imported value, 2: total value */
			$notice_content .= '<span class="opmc-odoo-order-import-status">' . sprintf(__('%1$d of %2$d orders imported.', 'wc-odoo-integration'), esc_html($synced_items), esc_html($total)) . '</span>';
		}
		$notice_content .= '</p></div>';

		if ( $import_running ) {
			echo wp_kses_post( $notice_content );
		}
	}


	/**
	 * Admin Notice callback for export status
	 */
	public function customer_export_status() {
		$export_running = get_option( 'opmc_odoo_customer_export_running' );
		$total          = get_option( 'opmc_odoo_customer_export_count' );
		$remaining      = get_option( 'opmc_odoo_customer_remaining_export_count' );
		$synced_items   = $total - $remaining;
		// pr($remaining); die();

		// Generate the dynamic notice content based on the data
		$notice_content = '<div class="notice notice-info is-dismissible">';
		$notice_content .= '<p id="opmc_odoo_customer_export" class="opmc-odoo-sync-notices">' . esc_html__('Odoo customer export is in process. ', 'wc-odoo-integration');
		if (1 == $synced_items) {
			/* translators: 1: exported value, 2: total value */
			$notice_content .= '<span class="opmc-odoo-customer-export-status">' . sprintf(__('%1$d of %2$d customer exported.', 'wc-odoo-integration'), esc_html($synced_items), esc_html($total)) . '</span>';
		} else {
			/* translators: 1: exported value, 2: total value */
			$notice_content .= '<span class="opmc-odoo-customer-export-status">' . sprintf(__('%1$d of %2$d customers exported.', 'wc-odoo-integration'), esc_html($synced_items), esc_html($total)) . '</span>';
		}
		$notice_content .= '</p></div>';

		if ( $export_running ) {
			echo wp_kses_post( $notice_content );
		}
	}

	/**
	 * Admin Notice callback for export status
	 */
	public function order_export_status() {
		$export_running = get_option( 'opmc_odoo_order_export_running' );
		$total          = get_option( 'opmc_odoo_order_export_count' );
		$remaining      = get_option( 'opmc_odoo_order_remaining_export_count' );
		$synced_items   = $total - $remaining;
		// pr($remaining); die();

		// Generate the dynamic notice content based on the data
		$notice_content = '<div class="notice notice-info is-dismissible">';
		$notice_content .= '<p id="opmc_odoo_order_export" class="opmc-odoo-sync-notices">' . esc_html__('Odoo order export is in process. ', 'wc-odoo-integration');
		if (1 == $synced_items) {
			/* translators: 1: exported value, 2: total value */
			$notice_content .= '<span class="opmc-odoo-order-export-status">' . sprintf(__('%1$d of %2$d order exported.', 'wc-odoo-integration'), esc_html($synced_items), esc_html($total)) . '</span>';
		} else {
			/* translators: 1: exported value, 2: total value */
			$notice_content .= '<span class="opmc-odoo-order-export-status">' . sprintf(__('%1$d of %2$d orders exported.', 'wc-odoo-integration'), esc_html($synced_items), esc_html($total)) . '</span>';
		}
		$notice_content .= '</p></div>';

		if ( $export_running ) {
			echo wp_kses_post( $notice_content );
		}
	}


	/**
	 * Admin Notice callback for export status
	 */
	public function product_export_status() {
		$export_running = get_option( 'opmc_odoo_product_export_running' );
		$total          = get_option( 'opmc_odoo_product_export_count' );
		$remaining      = get_option( 'opmc_odoo_product_export_remaining_count' );
		$synced_product = $total - $remaining;
		// pr($remaining); die();

		// Generate the dynamic notice content based on the data
		$notice_content = '<div class="notice notice-info is-dismissible">';
		$notice_content .= '<p id="opmc_odoo_product_export" class="opmc-odoo-sync-notices">' . esc_html__('Odoo product export is in process. ', 'wc-odoo-integration');
		if (1 == $synced_product) {
			/* translators: 1: exported value, 2: total value */
			$notice_content .= '<span class="opmc-odoo-product-export-status">' . sprintf(__('%1$d of %2$d product exported.', 'wc-odoo-integration'), esc_html($synced_product), esc_html($total)) . '</span>';
		} else {
			/* translators: 1: exported value, 2: total value */
			$notice_content .= '<span class="opmc-odoo-product-export-status">' . sprintf(__('%1$d of %2$d products exported.', 'wc-odoo-integration'), esc_html($synced_product), esc_html($total)) . '</span>';
		}
		$notice_content .= '</p></div>';

		if ( $export_running ) {
			echo wp_kses_post( $notice_content );
		}
	}


	public function opmc_update_imp_pro_notices() {
		$odooApi = new WC_ODOO_API();
		$import_running = get_option('opmc_odoo_product_import_running');
		$total = get_option('opmc_odoo_product_import_count');
		$remaining = get_option('opmc_odoo_product_remaining_import_count');
		$synced_product = $total - $remaining;
	
		if ($import_running) {
			if (1 == $synced_product) {
				/* translators: 1: imported value, 2: total value */
				$status = sprintf(__('%1$d of %2$d product imported.', 'wc-odoo-integration'), esc_html($synced_product), esc_html($total));
			} else {
				/* translators: 1: imported value, 2: total value */
				$status = sprintf(__('%1$d of %2$d products imported.', 'wc-odoo-integration'), esc_html($synced_product), esc_html($total));
			}
		} else {
			// Include information about failed products due to missing SKUs

			$import_product_without_sku = get_option('_opmc_odoo_import_product_without_sku', array());
			$import_product_without_sku_count = count($import_product_without_sku);
			$successfully_imported_product = $total - $import_product_without_sku_count;
			
			if ($import_product_without_sku_count) {
				/* translators: 1: successfully imported product  value, 2: total value, 3: product count without sku */
				$status = sprintf(__(' Odoo product import is completed. Successfully imported %1$d of %2$d products. %3$d products could not be imported due to missing SKUs.', 'wc-odoo-integration'), esc_html($successfully_imported_product), esc_html($total), esc_html($import_product_without_sku_count) );
			} else {
				/* translators: 1: successfully imported value, 2: total value */
				$status = sprintf(__(' Odoo product import is completed. Successfully imported %1$d of %2$d products.', 'wc-odoo-integration'), esc_html($successfully_imported_product), esc_html($total) );
			}
		}
	
		$data = array(
			'success' => true,
			'message' => $status,
			'remaining_items' => ( 0 != (int) $remaining ) ? true : false,
		);
	
		wp_send_json($data);
	}
	

	public function opmc_update_imp_order_notices() {
		$import_running = get_option('opmc_odoo_order_import_running');
		$total = get_option('opmc_odoo_order_import_count');
		$remaining = get_option('opmc_odoo_order_remaining_import_count', 0);
		$synced_items = $total - $remaining;

		if ($import_running) {
			if (1 == $synced_items) {
				/* translators: 1: imported value, 2: total value */
				$status = sprintf(__('%1$d of %2$d order imported.', 'wc-odoo-integration'), esc_html($synced_items), esc_html($total));
			} else {
				/* translators: 1: imported value, 2: total value */
				$status = sprintf(__('%1$d of %2$d orders imported.', 'wc-odoo-integration'), esc_html($synced_items), esc_html($total));
			}
		} else {
			/* translators: 1: imported value, 2: total value */
			$status = sprintf(__('Odoo order import is completed. Successfully imported %1$d of %2$d orders.', 'wc-odoo-integration'), esc_html($synced_items), esc_html($total));
		}
		$data = array(
			'success' => true,
			'message' => $status,
			'remaining_items' => ( 0 != (int) $remaining ) ? true : false,
		);
		wp_send_json($data);
	}

	public function opmc_update_imp_customer_notices() {
		$import_running = get_option('opmc_odoo_customer_import_running');
		$total = get_option('opmc_odoo_customer_import_count');
		$remaining = get_option('opmc_odoo_customer_remaining_import_count', 0);
		$synced_items = $total - $remaining;

		if ($import_running) {
			if (1 == $synced_items) {
				/* translators: 1: imported value, 2: total value */
				$status = sprintf(__('%1$d of %2$d customer imported.', 'wc-odoo-integration'), esc_html($synced_items), esc_html($total));
			} else {
				/* translators: 1: imported value, 2: total value */
				$status = sprintf(__('%1$d of %2$d customers imported.', 'wc-odoo-integration'), esc_html($synced_items), esc_html($total));
			}
		} else {
			/* translators: 1: imported value, 2: total value */
			$status = sprintf(__('Odoo customer import is completed. Successfully imported %1$d of %2$d customers.', 'wc-odoo-integration'), esc_html($synced_items), esc_html($total));
		}
		$data = array(
			'success' => true,
			'message' => $status,
			'remaining_items' => ( 0 != (int) $remaining ) ? true : false,
		);
		wp_send_json($data);
	}

	public function opmc_update_exp_pro_notices() {
		$odooApi = new WC_ODOO_API();
		$export_running = get_option('opmc_odoo_product_export_running');
		$total = get_option('opmc_odoo_product_export_count');
		$remaining = get_option('opmc_odoo_product_export_remaining_count');
		$synced_product = $total - $remaining;

//		$odooApi->addLog('remianing items in process : ' . print_r($remaining, true));
		if ( $export_running ) {
			if ( 1 == $synced_product ) {
				/* translators: 1: exported value, 2: total value */
				$status = sprintf(__('%1$d of %2$d product exported.', 'wc-odoo-integration'), esc_html($synced_product), esc_html($total));
			} else {
				/* translators: 1: exported value, 2: total value */
				$status = sprintf(__('%1$d of %2$d products exported.', 'wc-odoo-integration'), esc_html($synced_product), esc_html($total));
			}
		} else {

			$export_product_without_sku = get_option('_opmc_odoo_export_products_without_sku', array());
			$export_product_without_sku_count = count($export_product_without_sku);
			$successfully_exported_product = $total - $export_product_without_sku_count;

			if ($export_product_without_sku_count) {
				/* translators: 1: successfully exported product value, 2: total value, 3: product without sku */
				$status = sprintf(__(' Odoo product export is completed. Successfully exported %1$d of %2$d products. %3$d products could not be exported due to missing SKUs.', 'wc-odoo-integration'), esc_html($successfully_exported_product), esc_html($total), esc_html($export_product_without_sku_count) );
			} else {
				/* translators: 1: successfully exported product value, 2: total value */
				$status = sprintf(__(' Odoo product export is completed. Successfully exported %1$d of %2$d products.', 'wc-odoo-integration'), esc_html($successfully_exported_product), esc_html($total) );
	
			}
		
		}
		$data = array(
			'success' => true,
			'message' => $status,
			'remaining_items' => ( 0 != (int) $remaining ) ? true : false,
		);
		wp_send_json($data);
	}

	public function opmc_update_exp_order_notices() {
		$odooApi = new WC_ODOO_API();
		$export_running = get_option('opmc_odoo_order_export_running');
		$total = get_option('opmc_odoo_order_export_count');
		$remaining = get_option('opmc_odoo_order_remaining_export_count');
		$synced_items = $total - $remaining;

		if ($export_running) {
			if (1 == $synced_items) {
				/* translators: 1: exported value, 2: total value */
				$status = sprintf(__('%1$d of %2$d order exported.', 'wc-odoo-integration'), esc_html($synced_items), esc_html($total));
			} else {
				/* translators: 1: exported value, 2: total value */
				$status = sprintf(__('%1$d of %2$d orders exported.', 'wc-odoo-integration'), esc_html($synced_items), esc_html($total));
			}
		} else {
			/* translators: 1: imported value, 2: total value */
			$status = sprintf(__('Odoo order export is completed. Successfully exported %1$d of %2$d orders.', 'wc-odoo-integration'), esc_html($synced_items), esc_html($total));
		}
		$data = array(
			'success' => true,
			'message' => $status,
			'remaining_items' => ( 0 != (int) $remaining ) ? true : false,
		);
		wp_send_json($data);
	}

	public function opmc_update_exp_customer_notices() {
		$odooApi = new WC_ODOO_API();
		$export_running = get_option('opmc_odoo_customer_export_running');
		$total = get_option('opmc_odoo_customer_export_count');
		$remaining = get_option('opmc_odoo_customer_remaining_export_count');
		$synced_items = $total - $remaining;

		if ($export_running) {
			if (1 == $synced_items) {
				/* translators: 1: exported value, 2: total value */
				$status = sprintf(__('%1$d of %2$d customer exported.', 'wc-odoo-integration'), esc_html($synced_items), esc_html($total));
			} else {
				/* translators: 1: exported value, 2: total value */
				$status = sprintf(__('%1$d of %2$d customers exported.', 'wc-odoo-integration'), esc_html($synced_items), esc_html($total));
			}
		} else {
			/* translators: 1: imported value, 2: total value */
			$status = sprintf(__('Odoo customer export is completed. Successfully exported %1$d of %2$d customers.', 'wc-odoo-integration'), esc_html($synced_items), esc_html($total));
		}
		$data = array(
			'success' => true,
			'message' => $status,
			'remaining_items' => ( 0 != (int) $remaining ) ? true : false,
		);
		wp_send_json($data);
	}


	public function is_authenticate() {
		$odooApi = new WC_ODOO_API();
		$this->_setCredentials();
		if ($this->isCredsDefined()) {
			if (!$this->opmc_odoo_access_token) {
				if ($this->settings_changed) {
					delete_option('opmc_odoo_access_token');
					delete_option('opmc_odoo_authenticated_uid');
					delete_option('_opmc_odoo_access_error');
					$response = $odooApi->generateToken();
					update_option( 'is_opmc_odoo_settings_changed', 0 );
					update_option( '_opmc_odoo_update_configs', 1 );
					$this->settings_changed = 0;
					if ($response->success) {
						update_option('opmc_odoo_access_token', $response->data->token);
						update_option('opmc_odoo_authenticated_uid', $response->data->odoo_uid);
						$odooApi->addLog('[Odoo Connection] [Success] [Odoo Database is connected with the provided details successfully]');
						return true;
					} else {
						update_option( '_opmc_odoo_access_error', $response->data->error_code );
						if ('INVALID_CREDS' == $response->data->error_code) {
							$odooApi->addLog('[Odoo Connection] [Error] [Your provided odoo login details are not valid.]');
						} elseif ('INVALID_HOST' == $response->data->error_code) {
							$odooApi->addLog('[Odoo Connection] [Error] [Your provided odoo login details are not valid.]');
						}
						return false;
					}
				} elseif ( $this->plugin_updated ) {
					update_option( 'is_opmc_odoo_settings_changed', 1 );
					$response = $odooApi->generateToken();
					update_option( 'is_opmc_odoo_settings_changed', 0 );
					update_option( '_opmc_odoo_update_configs', 1 );
					delete_option( 'wc_opmc_odoo_update_state' );
					$this->settings_changed = 0;
					if ($response->success) {
						update_option('opmc_odoo_access_token', $response->data->token);
						update_option('opmc_odoo_authenticated_uid', $response->data->odoo_uid);
						$odooApi->addLog('[Odoo Connection] [Success] [Odoo Database is connected with the provided details successfully]');
						return true;
					} else {
						update_option( '_opmc_odoo_access_error', $response->data->error_code );
						if ('INVALID_CREDS' == $response->data->error_code) {
							$odooApi->addLog('[Odoo Connection] [Error] [Your provided odoo login details are not valid.]');
						} elseif ('INVALID_HOST' == $response->data->error_code) {
							$odooApi->addLog('[Odoo Connection] [Error] [Your provided odoo login details are not valid.]');
						}
						return false;
					}
				} else {
					$opmc_odoo_access_error = get_option( '_opmc_odoo_access_error', false );
					if ($opmc_odoo_access_error) {
						return false;
					} else {
						return true;
					}
				}
			} else {
				$opmc_odoo_access_error = get_option( '_opmc_odoo_access_error', false );
				if ( $opmc_odoo_access_error ) {
					return false;
				} else {
					return true;
				}
			}
		} else {
			return false;
		}
	}

	public function opmc_admin_notice() {
		$error_code = get_option( '_opmc_odoo_access_error', false );
		$url        = $this->get_settings_url();
		if ( 'INVALID_HOST' == $error_code ) {
			/* translators: 1: Strong Tag start, 2: Strong Tag end, 3: link start 4: link end. */
			echo '<div class="notice notice-error"><p>' . sprintf( esc_html__( '%1$s WooCommerce Odoo Integration Server URL is Invalid. %2$s Please verify Odoo Credentials & %3$s go to Odoo Integration >> Settings tab %4$s and Set Odoo details and click save button.', 'wc-odoo-integration' ), '<strong>', '</strong>', '<a href="' . esc_url( $url ) . '">', '</a>' ) . '</p></div>' . "\n";
		}
		if ( 'INVALID_CREDS' == $error_code ) {
			/* translators: 1: Strong Tag start, 2: Strong Tag end, 3: link start 4: link end. */
			echo '<div class="notice notice-error"><p>' . sprintf( esc_html__( '%1$s WooCommerce Odoo Integration credentials are Invalid. %2$s Please verify Odoo credentials & %3$s go to Odoo Integration >> Settings tab %4$s and Set Odoo details and click save button.', 'wc-odoo-integration' ), '<strong>', '</strong>', '<a href="' . esc_url( $url ) . '">', '</a>' ) . '</p></div>' . "\n";
		}
	}


	public function get_settings_url() {
		return add_query_arg(
			array(
				'page'    => 'wc-settings',
				'tab'     => 'integration',
				'section' => 'woocommmerce_odoo_integration',
			),
			admin_url( 'admin.php' )
		);
	}
}
