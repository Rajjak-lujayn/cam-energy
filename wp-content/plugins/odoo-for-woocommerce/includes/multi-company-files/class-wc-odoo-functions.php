<?php

/**
 * WooCommerce ODOO Integration.
 *
 * @package WooCommerce ODOO Integration
 */
// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);
require_once 'opmc-hpos-compatibility-helper.php';
if (!class_exists('WC_ODOO_Functions')) :
	class WC_ODOO_Functions {

		public $odoo_sku_mapping = 'default_code';
		public $odoo_attr_values = '';
		public $odoo_settings = array();
		public $odoo_company_id = 1;

		public $import_product_to_odoo_process;

		public function __construct() {
			add_action('woocommerce_checkout_order_processed', array( $this, 'create_order_to_odoo' ), 10, 1);
			// add_action( 'woocommerce_customer_save_address', array($this,'action_woocommerce_customer_save_address'), 10, 2 );
			// add_action('init', array($this,'sync_refund_order'));
			// $this->sync_refund_order();
			$ODOO_integrations = get_option('woocommerce_woocommmerce_odoo_integration_settings');
			$this->odoo_settings = $ODOO_integrations;
			if (isset($ODOO_integrations['companyFile']) && '' != $ODOO_integrations['companyFile']) {
				$this->odoo_company_id = $ODOO_integrations['companyFile'];
			}
			if (isset($ODOO_integrations['odooSkuMapping']) && '' != $ODOO_integrations['odooSkuMapping']) {
				$this->odoo_sku_mapping = $ODOO_integrations['odooSkuMapping'];
			}

			require_once plugin_dir_path( __FILE__ ) . '/../background-process/class-opmc-import-products.php';
			require_once plugin_dir_path( __FILE__ ) . '/../background-process/class-opmc-export-products.php';
			$this->export_products = new Opmc_Product_Export();
			$this->import_products = new Opmc_Product_Import();
		}


		/**
		 * Create new order in odoo On Woo Checkout
		 *
		 * @param  [int] $order_id [woocommerce order id]
		 */
		public function create_order_to_odoo( $order_id ) {
			$odooApi = new WC_ODOO_API();
			$odooApi->addLog('Create on import : ');
			if ('yes' == $this->odoo_settings['odoo_export_order_on_checkout']) {
				$this->order_create($order_id);
			}
		}

		/**
		 * Create Customer in Odoo
		 *
		 * @param [array] $customer_data [customer data fields]
		 * @return [int] [customer odoo id]
		 */
		public function create_customer( $customer_data ) {

			$odooApi = new WC_ODOO_API();
			$common = new WC_ODOO_Common_Functions();

			if (!$common->is_authenticate()) {
				return;
			}

			$all_meta_for_user = get_user_meta($customer_data->ID);
			$data = array(
				'name' => get_user_meta($customer_data->ID, 'first_name', true) . ' ' . get_user_meta($customer_data->ID, 'last_name', true),
				'display_name' => get_user_meta($customer_data->ID, 'first_name', true) . ' ' . get_user_meta($customer_data->ID, 'last_name', true),
				'email' => $customer_data->user_email,
				'customer_rank' => 1,
				'type' => 'contact',
				'phone' => $all_meta_for_user['billing_phone'][0],
				'street' => $all_meta_for_user['billing_address_1'][0],
				'city' => $all_meta_for_user['billing_city'][0],
				'state_id' => $this->getStateId($all_meta_for_user['billing_state'][0]),
				'country_id' => $this->getCountryId($all_meta_for_user['billing_state'][0]),
				'zip' => $all_meta_for_user['billing_postcode'][0],
			);
			$response = $odooApi->create_record('res.partner', $data);
			if (isset($response['fail'])) {
				$error_msg = 'Error for Create customer => ' . $customer_data->user_email . ' Msg : ' . print_r($response['msg'], true);
				$odooApi->addLog($error_msg);
				return false;
			}
			return $response;
		}

		public function update_customer_to_odoo( $customer_id, $customer_data ) {
			$odooApi = new WC_ODOO_API();
			$common = new WC_ODOO_Common_Functions();

			if (!$common->is_authenticate()) {
				return;
			}

			$all_meta_for_user = get_user_meta($customer_data->ID);

			$data = array(
				'name' => get_user_meta($customer_data->ID, 'first_name', true) . ' ' . get_user_meta($customer_data->ID, 'last_name', true),
				'display_name' => get_user_meta($customer_data->ID, 'first_name', true) . ' ' . get_user_meta($customer_data->ID, 'last_name', true),
				'email' => $customer_data->user_email,
				'customer_rank' => 1,
				'type' => 'contact',
				'phone' => $all_meta_for_user['billing_phone'][0],
				'street' => $all_meta_for_user['billing_address_1'][0],
				'city' => $all_meta_for_user['billing_city'][0],
				'state_id' => $this->getStateId($all_meta_for_user['billing_state'][0]),
				'country_id' => $this->getCountryId($all_meta_for_user['billing_state'][0]),
				'zip' => $all_meta_for_user['billing_postcode'][0],
			);
			$response = $odooApi->update_record('res.partner', array( $customer_id ), $data);
			if (isset($response['fail'])) {
				$error_msg = 'Error for Create customer => ' . $customer_data->user_email . ' Msg : ' . print_r($response['msg'], true);
				$odooApi->addLog($error_msg);
				return false;
			}
			return $response;
		}
		/**
		 * Create new product in the Odoo
		 *
		 * @param [array] [product data]
		 * @return [int] [product id]
		 */
		public function create_product( $product_data ) {
			$odooApi = new WC_ODOO_API();

			if ($product_data->get_sku() == '') {
				$error_msg = '[Product Export] [Error] [Products ID : ' . $product_data->get_id() . ' have missing/invalid SKU. This product will not be exported. ]';
				$odooApi->addLog($error_msg);
				return false;
			}
			$helper = WC_ODOO_Helpers::getHelper();
			$attrs = $odooApi->readAll('product.attribute.value', array( 'id', 'name', 'display_type', 'attribute_id', 'pav_attribute_line_ids' ));
			$odoo_attrs = array();
			foreach ($attrs as $akey => $attr) {
				$odoo_attrs[strtolower($attr['attribute_id'][1])][strtolower($attr['name'])] = $attr;
			}
			$product = wc_get_product($product_data->get_id());

			$data = array(
				'name' => $product_data->get_name(),
				'sale_ok' => true,
				'type' => 'product',
				$this->odoo_sku_mapping => $product_data->get_sku(),
				'description_sale' => $product_data->get_description(),
				'company_id'  => (int) $this->odoo_company_id,
				'attribute_line_ids' => $this->get_attributes_line_ids($odoo_attrs, $product_data->get_attributes()),
				'weight'        => $product_data->get_weight(),
				'volume'        => (int) ( (int) $product_data->get_height() * (int) $product_data->get_length() * (int) $product_data->get_width() ),
			);
			if ('yes' == $this->odoo_settings['odoo_export_create_categories']) {
				$data['categ_id'] = $this->get_category_id($product_data);
			}
			if ('yes' == $this->odoo_settings['odoo_export_update_price']) {
				//$data['list_price'] = $product_data->get_regular_price();
				$data['list_price'] = $product_data->get_sale_price() ? $product_data->get_sale_price() : $product_data->get_regular_price();
				$odooApi->addLog('product price : ' . print_r($data['list_price'], true));
			}
			if ($helper->can_upload_image($product_data)) {
				$data['image_1920'] = $helper->upload_product_image($product_data);
			}
//          $odooApi->addLog('Product\'s data : ' . print_r($data, true));

			return $odooApi->create_record('product.template', $data);
		}

		/**
		 * [inventory_syncCron description]
		 *
		 * @return [type] [description]
		 */
		public function inventory_sync() {
			global $wpdb;

			// if (!empty($_GET['import_odoo']) && 1 == $_GET['import_odoo']) {

			$product_count = $wpdb->get_row("SELECT COUNT(*) as total_products FROM {$wpdb->posts} WHERE (post_type='product' OR post_type='product_variation') AND post_status='publish'");
			$total_count = $product_count->total_products;

			$limit = 3;
			$total_loop_pages = ceil($total_count / $limit);

			for ($i = 0; $i <= $total_loop_pages; $i++) {
				$sku_lot = $this->get_product_sku_lot($i);
				foreach ($sku_lot as $product_id => $woo_product_sku) {
					$this->search_item_and_update_inventory($woo_product_sku, $product_id);
				}
			}

			// }
		}

		/**
		 * [get_product_sku_lot description]
		 *
		 * @param  integer $page [page number for the pagination]
		 * @return [array]        [collection of skus]
		 */
		private function get_product_sku_lot( $page = 0 ) {
			global $wpdb;

			$limit = 3;

			if (0 == $page) {
				$offset = 0;
			} else {
				$offset = $limit * $page;
			}

			$products = $wpdb->get_results($wpdb->prepare("SELECT ID FROM {$wpdb->posts}  WHERE (post_type='product' OR post_type='product_variation') AND post_status='publish' LIMIT %d , %d", $offset, $limit));
			$sku_lot = array();

			foreach ($products as $product) {
				$sku = get_post_meta($product->ID, '_sku', true); // MANISH : CHANGE THIS TO CUSTOM FIELD
				if (!empty($sku)) {
					$sku_lot[$product->ID] = $sku;
				}
			}

			return $sku_lot;
		}


		/**
		 * Search product in Odoo and update the woo inventory
		 *
		 * @param  [string] $item_sku   [woo product sku]
		 * @param  [int] $product_id [woocommerce product id]
		 */
		public function search_item_and_update_inventory( $item_sku, $product_id ) {


			$quantity = false;
			try {

				//perofrm search request
				require_once WC_ODOO_INTEGRATION_PLUGINDIR . '/includes/class-wc-odoo-api.php';
				$odooApi = new WC_ODOO_API();
				$conditions = array(
					array( 'company_id', '=', $this->odoo_company_id ),
					array( $this->odoo_sku_mapping, '=', $item_sku ),
				);
				$search = $odooApi->fetchProductInventory($conditions);
				// var_dump($search);
				if (empty($search) || isset($search['fail'])) {

					// $error_msg = 'Error for Searching  Product  for Sku => ' . $item_sku . ' Msg : ' . print_r($search['msg'], true);
					//  $odooApi->addLog($error_msg);
					return false;
				} elseif (isset($search['id'])) {
					$data = $search;

					//get items location id
					$item_odoo_id = $data['id'];


					update_post_meta($product_id, '_odoo_id', $item_odoo_id);

					if (!empty($data['list_price']) && isset($data['list_price'])) {
						$main_price = $data['list_price'];
						update_post_meta($product_id, '_regular_price', $main_price);
						update_post_meta($product_id, '_sale_price', $main_price);
						$product = wc_get_product($product_id);
						$product->set_regular_price($main_price);
						$product->set_sale_price($main_price);
						$product->set_price($main_price);
						$product->save();
					}

					$quantity = $data['qty_available'] ? $data['qty_available'] : false;

					if (false !== $quantity) {

						update_post_meta($product_id, '_stock', $quantity);

						update_post_meta($product_id, '_manage_stock', 'yes');
						if ($quantity > 0) {
							update_post_meta($product_id, '_stock_status', 'instock');
						} else {
							update_post_meta($product_id, '_stock_status', 'outofstock');
						}
					}
				}
			} catch (Exception $e) {
				$error_msg = 'Error for Searching  Product  for Sku => ' . $item_sku . ' Msg : ' . print_r($e->getMessage(), true);
				$odooApi->addLog($error_msg);
				return false;
			}
			return $quantity;
		}

		/**
		 * Create data for the odoo customer address
		 *
		 * @param  [string] $addressType [address type delivery/invoice]
		 * @param  [array] $userdata    [user data]
		 * @param  [integer] $parent_id   [user_id ]
		 * @return [array]              [formated address data for the customer]
		 */
		public function create_address_data( $addressType, $userdata, $parent_id ) {
			$data = array(
				'name' => $userdata['first_name'] . ' ' . $userdata['last_name'],
				'email' => isset($userdata['email']) ? $userdata['email'] : '',
				'street' => $userdata['address_1'],
				'street2' => $userdata['address_2'],
				'zip' => $userdata['postcode'],
				'city' => isset($userdata['city']) ? $userdata['city'] : '',
				'type' => $addressType,
				'parent_id' => (int) $parent_id,
				'phone' => isset($userdata['phone']) ? $userdata['phone'] : false,
			);
			if (!empty($userdata['state'])) {
				$data['state_id'] = $this->getStateId($userdata['state']);
			}
			if (!empty($userdata['country'])) {
				$data['country_id'] = $this->getCountryId($userdata['state']);
			}
			return $data;
		}

		/**
		 * Create_invoice description
		 *
		 * @param  [array] $odoo_customer [customer ids array]
		 * @param  [int] $odoo_order_id    [order id]
		 * @return [int]                   [invoice Id]
		 */
		public function create_invoice_data( $odoo_customer, $odoo_order_id ) {
			$odooApi = new WC_ODOO_API();
			$order = $odooApi->fetch_record_by_id('sale.order', array( $odoo_order_id ), array( 'id', 'name', 'date_order' ));
			$odoo_settings = $this->odoo_settings;
			$odooApi->addLog('customer data : ' . print_r($odoo_customer, true));

			$data = array(
				'name' => 'INV/' . gmdate('Y') . '/' . $order['name'],
				'display_name' => 'INV/' . gmdate('Y') . '/' . $order['name'],
				'company_id'  => (int) $this->odoo_company_id,
				'partner_id' => (int) $odoo_customer['id'],
				'journal_id' => (int) $odoo_settings['invoiceJournal'],
				'invoice_origin' => $order['name'],
				'state' => 'draft',
				'type_name' => 'Invoice',
				'invoice_payment_term_id' => 1,
				'partner_shipping_id' => (int) $odoo_customer['shipping_id'],
				'invoice_date' => gmdate('Y-m-d', strtotime($order['date_order'])),
				'invoice_date_due' => gmdate('Y-m-d', strtotime($order['date_order'])),
			);
			if (isset($odoo_settings['odooVersion']) && 13 == $odoo_settings['odooVersion']) {
				$data['type'] = 'out_invoice';
			} else {
				$data['move_type'] = 'out_invoice';
			}
			if (isset($odoo_settings['odooVersion']) && ( 14 == $odoo_settings['odooVersion'] || 15 == $odoo_settings['odooVersion'] )) {
				if (isset($odoo_settings['gst_treatment'])) {
					$data['l10n_in_gst_treatment'] = $odoo_settings['gst_treatment'];
				}
			}
			return $data;
		}

		/**
		 * Create line item fo the invoice
		 *
		 * @param  [int] $odoo_invoice_id [invoice id]
		 * @param  [array] $products        [product array data]
		 * @return [int]  $invoice_line_item  [invoice line id]
		 */
		public  function create_invoice_lines( $odoo_invoice_id, $products ) {
			$invoice_line_item = array();
			$odooApi = new WC_ODOO_API();
			$invoice = $odooApi->fetch_record_by_id('account.move', array( $odoo_invoice_id ));

			$odooApi->addLog('invoice id in line adding : ' . print_r($invoice, true));

			foreach ($products as $key => $product) {

				$invoice_line_item[] = $odooApi->create_record('account.move.line', $product);
			}

			return $invoice_line_item;
		}

		/**
		 * Update records on Odoo
		 *
		 * @param  [string] $type   [record type]
		 * @param  [integer] $ids    [ids of records]
		 * @param  [array] $fields [records fields to update]
		 * @return [integer]         [id]
		 */
		public function update_record( $type, $ids, $fields ) {
			$odooApi = new WC_ODOO_API();
			return $odooApi->update_record($type, array( $ids ), $fields);
		}


		/**
		 * Create download url for the invoice
		 *
		 * @param  int $invoice_id odoo invoice id
		 * @return stingr             invoice downloadable url/''
		 */
		public function create_pdf_download_link( $invoice_id ) {
			$odooApi = new WC_ODOO_API();
			$invoice = $odooApi->fetch_record_by_id('account.move', array( $invoice_id ), array( 'id', 'access_url', 'access_token' ));
			$download_url = '';
			if (isset($invoice['id']) && $invoice['id'] == $invoice_id && isset($invoice['access_token'])) {
				// $wc_setting = new WC_ODOO_Integration_Settings();
				$wc_setting = get_option('woocommerce_woocommmerce_odoo_integration_settings');


				$host = $wc_setting['client_url'];
				$access_token = $invoice['access_token'];
				$access_url = $invoice['access_url'];
				$download = true;
				$report_type = 'pdf';
				$download_url =  $host . $access_url . '?access_token=' . $access_token . '&report_type=' . $report_type . '&download=' . $download;
			}
			return $download_url;
		}

		public function create_refund_invoice_data( $odoo_invoice_id ) {
			$odooApi = new WC_ODOO_API();
			$odoo_invoice = $odooApi->fetch_record_by_id('account.move', (int) $odoo_invoice_id, array( 'id', 'name', 'partner_id', 'invoice_origin' ));
			if (isset($odoo_invoice['invoice_origin'])) {

				$data = array(

					'name' => 'RINV/' . gmdate('Y') . '/' . $odoo_invoice['invoice_origin'],
					'display_name' => 'RINV/' . gmdate('Y') . '/' . $odoo_invoice['invoice_origin'] . ' (Reversal of: ' . $odoo_invoice['name'] . ')',
					'reversed_entry_id' => (int) $odoo_invoice_id,
					'partner_id' => $odoo_invoice['partner_id'][0],
					'company_id' => $this->odoo_company_id,
					'journal_id' => 1,
					'invoice_origin' => $odoo_invoice['invoice_origin'],
					// 'invoice_sent' => 1,
					// 'type' => 'out_refund',
					'type_name' => 'Credit Note',
					'invoice_date' => gmdate('Y-m-d'),
					'invoice_date_due' => gmdate('Y-m-d'),
					// 'invoice_payment_state' => 'not_paid',
				);
				if (isset($this->odoo_settings['odooVersion']) && 13 == $this->odoo_settings['odooVersion']) {

					$data['type'] =  'out_refund';
					$data['invoice_payment_state'] =  'not_paid';
				} else {
					$data['move_type'] = 'out_refund';
					$data['payment_state'] =  'not_paid';
				}
				if (isset($this->odoo_settings['odooVersion']) && ( 14 == $this->odoo_settings['odooVersion'] || 15 == $odoo_settings['odooVersion'] )) {
					if (isset($this->odoo_settings['gst_treatment'])) {
						$data['l10n_in_gst_treatment'] = $this->odoo_settings['gst_treatment'];
					}
				}
				return $data;
			}
			return false;
		}


		public function searchOrCreateGuestUser( $order ) {
			$customer = array();
			$user_data = $order->get_address('billing');
			$odooApi = new WC_ODOO_API();
			$customer_id = $odooApi->search_record('res.partner', array( array( 'email', '=', $user_data['email'] ) ));

			if (!isset($customer_id) || false == $customer_id) {
				$data = array(
					'name' => $user_data['first_name'] . ' ' . $user_data['last_name'],
					'email' => ( $user_data['email'] ) ? $user_data['email'] : '',
					'street' => $user_data['address_1'],
					'street2' => $user_data['address_2'],
					'zip' => $user_data['postcode'],
					'city' => $user_data['city'],
					'state_id' => $this->getStateId($user_data['state']),
					'country_id' => $this->getCountryId($user_data['state']),
					'type' => 'contact',
					'phone' => ( $user_data['phone'] ) ? $user_data['phone'] : false,
					'customer_rank' => 1,
				);
				$response = $odooApi->create_record('res.partner', $data);
				if (isset($response['fail'])) {
					$error_msg = 'Error for Create customer => ' . $user_data['email'] . ' Msg : ' . print_r($response['msg'], true);
					$odooApi->addLog($error_msg);
					return false;
				}
				$customer_id = $response;
			}
			return $customer_id;
		}

		public function getStateId( $stateCode ) {
			$states =  json_decode(file_get_contents(WC_ODOO_INTEGRATION_PLUGINDIR . '/includes/states.json'), true);
			return ( isset($states[$stateCode]) ) ? (int) $states[$stateCode]['id'] : false;
		}

		public function getCountryId( $stateCode ) {
				$states =  json_decode(file_get_contents(WC_ODOO_INTEGRATION_PLUGINDIR . '/includes/states.json'), true);
				return ( isset($states[$stateCode]) ) ? (int) $states[$stateCode]['country_id'] : false;
		}

		/**
		 * [action_woocommerce_customer_save_address description]
		 *
		 * @param  int $user_id      [description]
		 * @param  array $load_address [description]
		 * @return [type]               [description]
		 */
		public function action_woocommerce_customer_save_address( $user_id, $load_address ) {
			$odoo_user_exits = get_user_meta($user_id, '_odoo_id', true);
			if (isset($odoo_user_exits)) {
				$user = new WC_Customer($user_id);
				$address_type = ( 'shipping' == $load_address ) ? 'delivery' : 'invoice';
				$user_address = ( 'shipping' == $load_address ) ? $user->get_shipping() : $user->get_billing();

				if (!$this->can_create_address($user_id, $user_address, $address_type)) {
					return false;
				}
				$address = $this->create_address_data($address_type, $user_address, $odoo_user_exits);
				$customer_address_id = get_user_meta($user_id, '_odoo_' . $load_address . '_id', true);
				if (!$customer_address_id) {
					$conditions = array(
						array( 'parent_id', '=', (int) $odoo_user_exits ),
						array( 'type', '=', $address_type ),
					);
					$customer_address_id = $this->search_odoo_customer($conditions, $user_id);
				}
				$odooApi = new WC_ODOO_API();

				if ($customer_address_id) {
					$updated = $this->update_record('res.partner', (int) $customer_address_id, $address);
					if (0 == $updated || isset($updated['fail'])) {
						if (isset($updated['msg'])) {
							$error_msg = 'Unable To update customer Odoo Id ' . $customer_address_id . ' Msg : ' . print_r($updated['msg'], true);
						} else {
							$error_msg = 'Unable To update customer Odoo Id ' . $customer_address_id;
						}
						$odooApi->addLog($error_msg);
						return false;
					}
				} else {
					$address_id = $odooApi->create_record('res.partner', $address);
					if (isset($address_id['fail'])) {
						$error_msg = 'Unable To Create customer Id ' . $user_id . ' Msg : ' . print_r($address_id['msg'], true);
						$odooApi->addLog($error_msg);
						return false;
					}
					update_user_meta($user_id, '_odoo_' . $load_address . '_id', $address_id);
				}
			}
		}

		/**
		 * Create data for the invoice line items
		 *
		 * @param  int $invoice_id    [description]
		 * @param  object $item          [description]
		 * @param  int $product_id    [description]
		 * @param  array $customer_data [description]
		 * @param  array $tax_data      [description]
		 * @return [array]                [description]
		 */
		public function create_invoice_line_base_on_tax( $invoice_id, $item, $product_id, $customer_data, $tax_data ) {

			$odooApi = new WC_ODOO_API();
			// $wc_setting = new WC_ODOO_Integration_Settings();
			$wc_setting = get_option('woocommerce_woocommmerce_odoo_integration_settings');

			$product = $item->get_product();
			$price = (string) $product->get_price() . '.00';
			$total_amount = $price * $item->get_quantity();
			$tax_amount = $this->create_tax_amount($tax_data, $total_amount);

			if (1 == $tax_data['price_include']) {
				$subtotal_amount = $total_amount - $tax_amount;
			} else {
				$subtotal_amount = $total_amount;
			}

			$invoice_line_data[] = array(
				'product_id' => (int) $product_id,
				'name' => $product->get_name(),
				'price_unit' => $price + 0,
				'quantity' => $item->get_quantity(),
				'move_id' => $invoice_id,
				'account_id' => (int) $wc_setting['odooAccount'],
				'partner_id' => (int) $customer_data['invoice_id'],
				'tax_ids' => array( array( 6, 0, array( (int) $tax_data['id'] ) ) ),
				'tax_tag_ids' => array( array( 6, 0, array( 5 ) ) ),
				'price_subtotal' => $subtotal_amount,
			);

			$tax_amounts[] = $tax_amount;


			$invoice_line_data[] = array(
				// 'tax_ids' => [[6, 0, [$tax_id]]],
				// 'tag_ids' => array(array(6, 0, array(9))),
				'product_id' => (int) $product_id,
				'name' => $tax_data['name'],
				'price_unit' => abs($tax_amount),
				'price_subtotal' => abs($tax_amount),
				'quantity' => 1.00,
				'move_id' => $invoice_id,
				'account_id' => (int) $wc_setting['odooAccount'],
				'ref' => '',
				'partner_id' => (int) $customer_data['invoice_id'],
				'exclude_from_invoice_tab' => true,
				'tax_base_amount' => abs($tax_amount),
				'tax_tag_ids' => array( array( 6, 0, array( 5, 30 ) ) ),


			);
			if (1 == $tax_data['price_include']) {
				$debtors_amount = - ( $total_amount );
			} else {
				$debtors_amount = - ( array_sum($tax_amounts) + $total_amount );
			}

			$invoice_line_data[] = array(
				// 'tax_ids' => [[6, 0, [$tax_id]]],
				'product_id' => (int) $product_id,
				'name' => 'INV' . gmdate('Y') . $invoice_id,
				'price_unit' => $debtors_amount,
				'price_subtotal' => $debtors_amount,
				'quantity' => 1.00,
				'move_id' => $invoice_id,
				'account_id' => (int) $wc_setting['odooDebtorAccount'],
				'ref' => '',
				'partner_id' => $customer_data['id'],
				'exclude_from_invoice_tab' => true,
			);
			return $invoice_line_data;
		}

		/**
		 * Create data for the invoice line items
		 *
		 * @param  int $invoice_id    [description]
		 * @param  object $item          [description]
		 * @param  int $product_id    [description]
		 * @param  array $customer_data [description]
		 * @param  array $tax_data      [description]
		 * @return [array]                [description]
		 */
		public function create_return_invoice_line_base_on_tax( $invoice_id, $item, $product_id, $customer_data, $tax_data ) {

			$refunded_quantity      = $item->get_quantity();
			$refunded_line_subtotal = abs($item->get_subtotal());
			$refunded_item_id       = $item->get_meta('_refunded_item_id');
			$order_line_id          = wc_get_order_item_meta($refunded_item_id, '_order_line_id', true);
			$odd_order_line_id      = wc_get_order_item_meta($refunded_item_id, '_invoice_line_id', true);
			$odoo_product_id        = get_post_meta($item->get_product_id(), '_odoo_id', true);

			$odooApi = new WC_ODOO_API();
			// $wc_setting = new WC_ODOO_Integration_Settings();
			$wc_setting = get_option('woocommerce_woocommmerce_odoo_integration_settings');

			$product = $item->get_product();
			$price = round($refunded_line_subtotal / $refunded_quantity, 2);
			$invoice_id = (int) $invoice_id;
			$total_amount = $refunded_line_subtotal;
			$gst_price = round(( $tax_data['amount'] / 100 ) * $total_amount, 2);
			$tax_amount = $this->create_tax_amount($tax_data, $total_amount);

			if (1 == $tax_data['price_include']) {
				$subtotal_amount = $total_amount - $tax_amount;
			} else {
				$subtotal_amount = $total_amount;
			}

			$invoice_line_data[] = array(
				'product_id' => (int) $odoo_product_id,
				'name' => $product->get_name(),
				'price_unit' => $price + 0,
				'quantity' => $item->get_quantity(),
				'move_id' => $invoice_id,
				'account_id' => (int) $wc_setting['odooAccount'],
				'partner_id' => (int) $customer_data['invoice_id'],
				'tax_ids' => array( array( 6, 0, array( (int) $tax_data['id'] ) ) ),
				'price_subtotal' => $subtotal_amount,
			);

			$tax_amounts[] = $tax_amount;


			$invoice_line_data[] = array(
				// 'tax_ids' => [[6, 0, [$tax_id]]],
				// 'tag_ids' => array(array(6, 0, array(9))),
				'product_id' => (int) $product_id,
				'name' => $tax_data['name'],
				'price_unit' => abs($tax_amount),
				'price_subtotal' => abs($tax_amount),
				'quantity' => 1.00,
				'move_id' => $invoice_id,
				'account_id' => (int) $wc_setting['odooAccount'],
				'ref' => '',
				'partner_id' => (int) $customer_data['invoice_id'],
				'exclude_from_invoice_tab' => true,
				'tax_base_amount' => abs($tax_amount),

			);
			if (1 == $tax_data['price_include']) {
				$debtors_amount = - ( $total_amount );
			} else {
				$debtors_amount = - ( array_sum($tax_amounts) + $total_amount );
			}

			$invoice_line_data[] = array(
				// 'tax_ids' => [[6, 0, [$tax_id]]],
				'product_id' => (int) $product_id,
				'name' => 'INV' . gmdate('Y') . $invoice_id,
				'price_unit' => $debtors_amount,
				'price_subtotal' => $debtors_amount,
				'quantity' => 1.00,
				'move_id' => $invoice_id,
				'account_id' => (int) $wc_setting['odooDebtorAccount'],
				'ref' => '',
				'partner_id' => $customer_data['id'],
				'exclude_from_invoice_tab' => true,
			);
			return $invoice_line_data;
		}

		/**
		 * Manage Customer Data
		 *
		 * @param  object/null $user  userdata
		 * @param  object $order order objects data
		 * @return array   $customer_data  return customer data
		 */
		public function getCustomerData( $user, $order ) {
			$odooApi = new WC_ODOO_API();
			$customer_data = array();
			if ($user && isset($user->user_email)) {

				$customer_id = get_user_meta($user->ID, '_odoo_id', true);

				// If user not exists in WooCommerce
				if (!$customer_id) {
					// Search record in the Odoo By email
					$customer_id = $odooApi->search_record('res.partner', array( array( 'email', '=', $user->user_email ) ));

					if (isset($customer_id['fail'])) {
						$error_msg = 'Error for Search customer =>' . $user->user_email . ' Msg : ' . print_r($customer_id['msg'], true);
						$odooApi->addLog($error_msg);
						return false;
					}

					//If user not exists in Odoo then Create New Customer in odoo
					if (!isset($customer_id) || false == $customer_id) {
						$customer_id = $this->create_customer($user);
						update_user_meta($user->ID, '_odoo_id', $customer_id);
					}
				}

				if ($customer_id) {

					$customer_data['id'] = $customer_id;

					$is_new_billing_address = true;

					$woo_opmc_billing_addresses = get_user_meta($user->ID, '_opmc_odoo_billing_addresses', true);

					$billing_address = $this->create_address_data('invoice', $order->get_address('billing'), $customer_id);

					if (!empty($woo_opmc_billing_addresses)) {
						foreach ($woo_opmc_billing_addresses as $woo_opmc_billing_address) {
							if (trim(strtolower($woo_opmc_billing_address['street'])) == trim(strtolower($billing_address['street'])) && $woo_opmc_billing_address['zip'] == $billing_address['zip']) {
								$customer_data['invoice_id'] = $woo_opmc_billing_address['partner_invoice_id'];
								$is_new_billing_address = false;
								break;
							}
						}
					} else {
						$woo_opmc_billing_addresses = array();
					}

					if ($is_new_billing_address) {
						$billing_id = $odooApi->create_record('res.partner', $billing_address);

						//Log Error Msg
						if (isset($billing_id['fail'])) {
							$error_msg = 'Error for Creating  Billing Address for customer=> ' . $customer_id . ' Msg : ' . print_r($billing_id['msg'], true);
							$odooApi->addLog($error_msg);
							return false;
						} else {
							update_user_meta($user->ID, '_odoo_billing_id', $billing_id);
							$customer_data['invoice_id'] = $billing_id;
						}

						update_user_meta($user->ID, '_odoo_billing_id', $billing_id);


						$billing_address['partner_invoice_id'] = $billing_id;

						$woo_opmc_billing_addresses[] = $billing_address;

						update_user_meta($user->ID, '_opmc_odoo_billing_addresses', $woo_opmc_billing_addresses);
					}

					$is_new_shipping_address = true;

					$woo_opmc_shipping_addresses = get_user_meta($user->ID, '_opmc_odoo_shipping_addresses', true);

					$shipping_address = $this->create_address_data('delivery', $order->get_address('shipping'), $customer_id);

					if (!empty($woo_opmc_shipping_addresses)) {
						foreach ($woo_opmc_shipping_addresses as $woo_opmc_shipping_address) {
							if (trim(strtolower($woo_opmc_shipping_address['street'])) == trim(strtolower($shipping_address['street'])) && $woo_opmc_shipping_address['zip'] == $shipping_address['zip']) {
								$customer_data['shipping_id'] = $woo_opmc_shipping_address['partner_shipping_id'];
								$is_new_shipping_address = false;
								break;
							}
						}
					} else {
						$woo_opmc_shipping_addresses = array();
					}

					if ($is_new_shipping_address) {
						$shipping_id = $odooApi->create_record('res.partner', $shipping_address);

						//Log Error Msg
						if (isset($shipping_id['fail'])) {
							$error_msg = 'Error for Creating  Shipping Address for customer=> ' . $customer_id . ' Msg : ' . print_r($shipping_id['msg'], true);
							$odooApi->addLog($error_msg);
							return false;
						} else {
							update_user_meta($user->ID, '_odoo_billing_id', $shipping_id);
							$customer_data['shipping_id'] = $shipping_id;
						}

						update_user_meta($user->ID, '_odoo_shipping_id', $shipping_id);

						$shipping_address['partner_shipping_id'] = $shipping_id;

						$woo_opmc_shipping_addresses[] = $shipping_address;

						update_user_meta($user->ID, '_opmc_odoo_shipping_addresses', $woo_opmc_shipping_addresses);
					}

				}

			}

			if (!$user || false == $user) {
				$customer = $this->searchOrCreateGuestUser($order);

				if (isset($customer['fail'])) {
					$error_msg = 'Error for Search customer => ' . $user->user_email . ' Msg : ' . print_r($customer['msg'], true);
					$odooApi->addLog($error_msg);
					return false;
				}
				$customer_id = $customer;
				$customer_data['id'] = $customer_id;
				$customer_data['invoice_id'] = ( isset($billing_id) ) ? $billing_id  : $customer_id;
				$customer_data['shipping_id'] = ( isset($shipping_id) ) ? $shipping_id : $customer_id;
			}
			return $customer_data;
		}

		public function create_tax_amount( $tax, $amount ) {
			switch ($tax['amount_type']) {
				case 'fixed':
					return round($tax['amount'], 2);
					break;
				case 'percent':
					// return round(( $tax['amount'] / 100 ) * $amount, 2);
					if (1 == $tax['price_include']) {
						$tax_included_price = round(( $amount / ( 1 + $tax['amount'] / 100 ) ), 2);
						$tax_amount = $tax_included_price - $amount;
						return $tax_amount;
					} else {
						return round(( $tax['amount'] / 100 ) * $amount, 2);
					}

					break;
				case 'group':
					return round(( $tax['amount'] / 100 ) * $amount, 2);
					break;
				case 'division':
					$tax_included_price = round(( $amount / ( 1 - $tax['amount'] / 100 ) ), 2);
					$tax_amount = $tax_included_price - $amount;
					return $tax_amount;
					break;
				default:
					return 0.00;
					break;
			}
		}

		public function get_delivery_product_id() {
			$shpping_id = get_option('odoo_shipping_product_id');
			if (false != $shpping_id) {
				return $shpping_id;
			} else {
				return $this->create_shipping_product();
			}
		}

		/**
		 * Create new product in the Odoo
		 *
		 * @param [array] [product data]
		 * @return [int] [product id]
		 */
		public function create_shipping_product() {

			$odooApi = new WC_ODOO_API();
			$data = array(
				'name' => 'WC Shipping Charge',
				'service_type' => 'manual',
				'sale_ok' => false,
				'company_id' => $this->odoo_company_id,
				// 'categ_id' => 4,
				'type' => 'service',
				$this->odoo_sku_mapping => 'wc_odoo_delivery',
				'description_sale' => 'delivery product created by WC Odoo Integration',
				'list_price' => 0.00,

			);
			$id = $odooApi->create_record('product.template', $data);

			if (isset($id)) {
				add_option('odoo_shipping_product_id', $id, '', 'yes');
				return $id;
			}
		}

		public function create_shipping_invoice_line( $invoice_id, $order, $customer_data, $tax_data ) {

			$odooApi = new WC_ODOO_API();
			// $wc_setting = new WC_ODOO_Integration_Settings();
			$wc_setting = get_option('woocommerce_woocommmerce_odoo_integration_settings');


			$price = $order->get_shipping_total();
			$total_amount = $price * 1;
			$tax_amount = $this->create_tax_amount($tax_data, $total_amount);

			if (1 == $tax_data['price_include']) {
				$subtotal_amount = $total_amount - $tax_amount;
			} else {
				$subtotal_amount = $total_amount;
			}

			$invoice_line_data[] = array(
				'product_id' => (int) $this->get_delivery_product_id(),
				'name' => 'Shipping Charge',
				'price_unit' => $price + 0,
				'quantity' => 1,
				'move_id' => $invoice_id,
				'account_id' => (int) $wc_setting['odooAccount'],
				'partner_id' => (int) $customer_data['invoice_id'],
				'tax_ids' => array( array( 6, 0, array( (int) $tax_data['id'] ) ) ),
				'price_subtotal' => $subtotal_amount,
			);

			$tax_amounts[] = $tax_amount;


			$invoice_line_data[] = array(
				// 'tax_ids' => [[6, 0, [$tax_id]]],
				// 'tag_ids' => array(array(6, 0, array(9))),
				'product_id' => (int) $this->get_delivery_product_id(),
				'name' => $tax_data['name'],
				'price_unit' => abs($tax_amount),
				'price_subtotal' => abs($tax_amount),
				'quantity' => 1.00,
				'move_id' => $invoice_id,
				'account_id' => (int) $wc_setting['odooAccount'],
				'ref' => '',
				'partner_id' => (int) $customer_data['invoice_id'],
				'exclude_from_invoice_tab' => true,
				'tax_base_amount' => abs($tax_amount),

			);
			if (1 == $tax_data['price_include']) {
				$debtors_amount = - ( $total_amount );
			} else {
				$debtors_amount = - ( array_sum($tax_amounts) + $total_amount );
			}

			$invoice_line_data[] = array(
				// 'tax_ids' => [[6, 0, [$tax_id]]],
				'product_id' => (int) $this->get_delivery_product_id(),
				'name' => 'INV' . gmdate('Y') . $invoice_id,
				'price_unit' => $debtors_amount,
				'price_subtotal' => $debtors_amount,
				'quantity' => 1.00,
				'move_id' => $invoice_id,
				'account_id' => (int) $wc_setting['odooDebtorAccount'],
				'ref' => '',
				'partner_id' => $customer_data['id'],
				'exclude_from_invoice_tab' => true,
			);
			return $invoice_line_data;
		}

		public function get_category_id( $product ) {
			$terms = wp_get_post_terms($product->get_id(), 'product_cat', array( 'fields' => 'ids' ));

			if (!is_wp_error($terms) && count($terms) > 0) {
				$cat_id = (int) $terms[0];
				$odoo_term_id = get_term_meta($cat_id, '_odoo_term_id', true);

				if ($odoo_term_id) {
					return $odoo_term_id;
				} else {
					$odooApi = new WC_ODOO_API();
					$term = get_term($cat_id);
					$data = array(
						'name' => $term->name,
					);
					$odoo_term_id = $odooApi->search_record('product.category', array( array( 'name', '=', $term->name ) ));
					if (isset($odoo_term_id['fail'])) {
						$error_msg = 'Error for Search Category => ' . $cat_id . ' Msg : ' . print_r($odoo_term_id['msg'], true);
						$odooApi->addLog($error_msg);
						return false;
					}
					if ($odoo_term_id) {
						return $odoo_term_id;
					} else {
						$response = $odooApi->create_record('product.category', $data);

						if (isset($response['fail'])) {
							$error_msg = 'Error for Creating category for Id  => ' . $cat_id . ' Msg : ' . print_r($response['msg'], true);
							$odooApi->addLog($error_msg);
						} else {
							update_term_meta($cat_id, '_odoo_term_id', $response);
							return $response;
						}
					}
				}
			} else {
				return 1;
			}
		}

		public function sync_refund_order() {
			global $wpdb;
			$order_origins = $wpdb->get_results("SELECT meta_value FROM {$wpdb->postmeta}  WHERE meta_key='_odoo_order_origin'", 'OBJECT_K');

			$refunded_invoices = $wpdb->get_results("SELECT meta_value FROM {$wpdb->postmeta}  WHERE meta_key='_odoo_return_invoice_id'", 'OBJECT_K');

			$origins = array_keys($order_origins);
			$refunded_invoice_ids = array_keys($refunded_invoices);

			$odooApi = new WC_ODOO_API();
			$odoo_settings = $this->odoo_settings;

			if (isset($odoo_settings['odooVersion']) && 13 == $odoo_settings['odooVersion']) {
				$conditions = array(
					array( 'type', '=', 'out_refund' ),
					array( 'state', '=', 'posted' ),
					array( 'id', 'not in', $refunded_invoice_ids ),
					array( 'invoice_origin', 'in', $origins ),
				);
			} else {
				$conditions = array(
					array( 'move_type', '=', 'out_refund' ),
					array( 'state', '=', 'posted' ),
					array( 'id', 'not in', $refunded_invoice_ids ),
					array( 'invoice_origin', 'in', $origins ),
				);
			}
			$invoice_fields = array();
			$invioces = $odooApi->search_records('account.move', $conditions, $invoice_fields);

			if (!isset($invioces['fail']) && is_array($invioces) && count($invioces) > 0) {
				foreach ($invioces as $key => $invoice) {

					$conditions = array( array( 'id', 'in', $invoice['invoice_line_ids'] ) );
					$invioce_line_fields = array( 'price_total', 'price_subtotal', 'quantity', 'product_id', 'tax_ids' );
					$invioce_lines = $odooApi->search_records('account.move.line', $conditions, $invioce_line_fields);

					if (is_array($invioce_lines)) {
						$inv_lines = array();
						foreach ($invioce_lines as $ilkey => $invioce_line) {
							if (isset($invioce_line['product_id'][0])) {
								$product_id = $this->get_post_id_by_meta_key_and_value('_odoo_id', $invioce_line['product_id'][0]);
								if ($product_id) {
									$inv_lines[$product_id] = $invioce_line;
									$inv_lines[$product_id]['wc_product_id'] = $product_id;
								}
							}
						}

						if (!empty($invoice['invoice_origin'])) {
							$conditions = array( array( 'name', '=', $invoice['invoice_origin'] ) );
							$order = $odooApi->search_record('sale.order', $conditions);

							if ($order) {
								$order_id = $this->get_post_id_by_meta_key_and_value('_odoo_order_id', $order);
								if ($order_id) {
									$return_id = opmc_hpos_get_post_meta($order_id, '_odoo_return_invoice_id', true);
									if ($return_id) {
										$error_msg = 'Refund Already Synced For Order  =>' . $order_id;
										$odooApi->addLog($error_msg);
										return false;
									}
									$this->wc_order_refund($order_id, $inv_lines, $invoice['id']);
								}
							}
						}
					}
				}
			}
		}


		public function wc_order_refund( $order_id, $inv_lines, $inv_id ) {
			$order  = wc_get_order($order_id);
			$odooApi = new WC_ODOO_API();

			// If it's something else such as a WC_Order_Refund, we don't want that.
			if (!is_a($order, 'WC_Order')) {

				$msg = 'Provided ID is not a WC Order : ' . $order_id;
				$odooApi->addLog($msg);
				return false;
			}
			if ('refunded' == $order->get_status()) {
				$msg = 'Order has been already refunded : ' . $order_id;
				$odooApi->addLog($msg);
				return false;
			}
			if (count($order->get_refunds()) > 0) {
				$msg = 'Order has been already refunded : ' . $order_id;
				$odooApi->addLog($msg);
				return false;
			}
			$refund_amount = 0;
			$line_items = array();
			/* get tax id from the admin setting */
			$tax_id = (int) $this->odoo_settings['odooTax'];

			$tax_data_odoo = $odooApi->fetch_file_record_by_id('taxes', 'account.tax', $tax_id);

			$order_items   = $order->get_items();
			if ($order_items) {
				foreach ($order_items as $item_id => $item) {
					if (isset($inv_lines[$item->get_product_id()])) {
						$current_item  = $inv_lines[$item->get_product_id()];
						$tax_data = wc_get_order_item_meta($item_id, '_line_tax_data', true);
						// $refund_tax = $current_item['amount_tax'];
						if (1 == $tax_data_odoo['price_include']) {
							$refund_tax = abs($this->create_tax_amount($tax_data_odoo, $current_item['price_total']));
						} else {
							$refund_tax = $this->create_tax_amount($tax_data_odoo, $current_item['price_subtotal']);
						}
						$refund_amount = wc_format_decimal($refund_amount + $current_item['price_subtotal'] + $refund_tax);

						$line_items[$item_id] = array(
							'qty' => abs($current_item['quantity']),
							'refund_total' => wc_format_decimal($current_item['price_subtotal']),
							'refund_tax' =>  array( 1 => wc_format_decimal(abs($refund_tax)) ),
						);
					}
				}
			}
			if ($refund_amount < 1) {
				$msg = 'Refund Created For for' . $order_id . ' Msg Invalid Refund Amount ' . $refund_amount;
				$odooApi->addLog($msg);
				return false;
			}
			$refund_reason = 'Odoo Return';
			$refund_data = array(
				'amount'         => $refund_amount,
				'reason'         => $refund_reason,
				'order_id'       => $order_id,
				'line_items'     => $line_items,
				'refund_payment' => false,
			);

			$refund = wc_create_refund($refund_data);

			if (!is_wp_error($refund)) {
				opmc_hpos_update_post_meta($order_id, '_odoo_return_invoice_id', $inv_id);
			} else {
				$msg = 'Error In creating Refund for' . $order_id . 'msg' . print_r(array( $refund_data, $refund ), true);
				$odooApi->addLog($msg);
				return false;
			}
		}

		public function get_post_id_by_meta_key_and_value( $key, $value ) {
			global $wpdb;
			$meta = $wpdb->get_results('SELECT post_id FROM `' . $wpdb->postmeta . "` WHERE meta_key='" . esc_sql($key) . "' AND meta_value='" . esc_sql($value) . "'");
			if (is_array($meta) && !empty($meta) && isset($meta[0])) {
				$meta = $meta[0];
			}
			if (is_object($meta)) {
				return $meta->post_id;
			} else {
				return false;
			}
		}


		public function get_user_id_by_meta_key_and_value( $key, $value ) {
			global $wpdb;
			$meta = $wpdb->get_results('SELECT user_id FROM `' . $wpdb->usermeta . "` WHERE meta_key='" . esc_sql($key) . "' AND meta_value='" . esc_sql($value) . "'");
			if (is_array($meta) && !empty($meta) && isset($meta[0])) {
				$meta = $meta[0];
			}
			if (is_object($meta)) {
				return $meta->user_id;
			} else {
				return false;
			}
		}

		public function update_product_quantity( $product_id, $quantity, $template = 0 ) {
			$creds = get_option('woocommerce_woocommmerce_odoo_integration_settings');
			// if (isset($creds['createProductToOdoo']) && 'yes' == $creds['createProductToOdoo'] ) {
			$odooApi = new WC_ODOO_API();
			$stockFields = $odooApi->read_fields('product.template', array() );
			if (0 == $template) {
				$template = $odooApi->fetch_record_by_id('product.product', array( (int) $product_id ), array( 'product_tmpl_id' ));
				$template_id = $template['product_tmpl_id'][0];
				$odooApi->addLog('template : ' . print_r($template_id, true));
			} else {
				$template_id = $template;
			}
//          $template_id = ( 0 == $template ) ? $product_id : $template;
			$odooApi->addLog('template Id : ' . print_r($template_id, true));
			$quantity_id = $odooApi->create_record('stock.change.product.qty', array(
				'new_quantity' => $quantity,
				'product_tmpl_id' => $template_id,
				'product_id' => $product_id,
			));
			$odooApi->addLog('quantity ID : ' . print_r($quantity_id, true));
			if ($quantity_id->success) {
				$quantity_id = $quantity_id->data->odoo_id;
				return $odooApi->custom_api_call('stock.change.product.qty', 'change_product_qty', array( $quantity_id ));
			}
			// }
		}

		public function import_product_odoo() {

			if ($this->import_products->is_process_running()) {
				echo json_encode(array( 'status'=> 0, 'message'=> __('Product import is already running.', 'wc-odoo-integration') ));
				die;
			}

			$odooApi = new WC_ODOO_API();

			$moduleCondition = array(
				array( 'application', '=', '1' ),
				'&',
				array( 'state', 'in', array( 'installed' ) ),
			);

			$installed_modules = $odooApi->readAll('ir.module.module', array( 'name', 'state' ), $moduleCondition);
//            $odooApi->addLog('installed Modules : '. print_r($installed_modules, true));
			$isPoSModuleInstalled = false;
			foreach ($installed_modules as $key => $installed_module) {
				if ('point_of_sale' ==$installed_module['name']) {
					$isPoSModuleInstalled = true;
				}
			}

			$conditions = array(
				array( 'company_id', '=', (int) $this->odoo_company_id ),
				array( 'sale_ok', '=', '1' ),
				array( 'product_variant_count', '=', '1' ),
			);

			if ('yes' == $this->odoo_settings['odoo_import_pos_product'] && $isPoSModuleInstalled) {
				$conditions[] = array( 'available_in_pos', '=', false );
			}


			$total_products_count = $odooApi->search_count('product.template', array(), $conditions);
			$total_products = $odooApi->search('product.template', $conditions, array( 'offset'=> 0, 'limit' => $total_products_count ));
			if (!isset($total_products['fail']) && is_array($total_products) && count($total_products) > 0 ) {
				foreach ($total_products as $tkey => $product_id) {
					$this->import_products->push_to_queue($product_id);
				}
			}

			$this->import_products->save()->dispatch();
			update_option('opmc_odoo_product_import_count', $total_products_count);
			update_option('opmc_odoo_product_remaining_import_count', $total_products_count);

			echo json_encode(array( 'status'=> 1, 'message'=> __('Import process has started for ', 'wc-odoo-integration') . $total_products_count . __(' products', 'wc-odoo-integration') ));

			exit;


			// $templates = $odooApi->readAll('product.template', array(), $conditions);
			// $attr_v = array();

			// if (!isset($templates['fail']) && is_array($templates) && count($templates) > 0) {
			//     foreach ($templates as $tkey => $template) {
			//         if ($template['product_variant_count'] > 1) {
			//             //continue;
			//             // if ($template['id'] != 32) {
			//             if (count($attr_v) == 0) {
			//                 $attr_values = $odooApi->readAll('product.template.attribute.value', array('name', 'id'));
			//                 foreach ($attr_values as $key => $value) {
			//                     $attr_v[$value['id']] = $value['name'];
			//                 }
			//                 $this->odoo_attr_values = $attr_v;
			//             }
			//             $products = $odooApi->fetch_record_by_ids('product.product', $template['product_variant_ids'], array());

			//             $attrs = $odooApi->fetch_record_by_ids('product.template.attribute.line', $template['attribute_line_ids'], array('display_name', 'id', 'product_template_value_ids'));
			//             foreach ($products as $pkey => $product) {
			//                 $attr_and_value = array();
			//                 foreach ($product['product_template_attribute_value_ids'] as $attr => $attr_value) {
			//                     foreach ($attrs as $key => $attr) {
			//                         foreach ($attr['product_template_value_ids'] as $key => $value) {
			//                             if ($value == $attr_value) {
			//                                 $attr_and_value[$attr['display_name']] = $attr_v[$value];
			//                             }
			//                         }
			//                     }
			//                     $products[$pkey]['attr_and_value'] = $attr_and_value;
			//                     $products[$pkey]['attr_value'][$attr_value] = $attr_v[$attr_value];
			//                     // $this->create_variation_product($template,$product);
			//                 }
			//             }

			//             $products['attributes'] = $attrs;
			//             $product_id = $this->sync_product_from_odoo($template, $products);
			//         } else {
			//             $product_id = $this->sync_product_from_odoo($template);
			//         }
			//     }
			// }
		}

		public function sync_product_from_odoo( $data, $variations = array() ) {

			$postData = array(
				'post_author' => 1,
				'post_content' => isset($data['description_sale']) ? $data['description_sale'] : '',
				'post_status' => ( 1 == $data['active'] ) ? 'publish' : 'draft',
				'post_title' => isset($data['name']) ? $data['name'] : '',
				'post_parent' => 0,
				'post_type' => 'product',
				'post_excerpt' => isset($data['name']) ? $data['name'] : '',

			);

			if (isset($data['id'])) {
				// get Post id if record already exists in woocommerce
				$post = $this->get_post_id_by_meta_key_and_value('_odoo_id', $data['id']);
				if (!$post) {
					$post = $this->get_post_id_by_meta_key_and_value('_sku', $data[$this->odoo_sku_mapping]);
				}

				if ($post) {
					if ('no' == $this->odoo_settings['odoo_import_update_product']) {
						return false;
					}

					$postData['ID'] = $post;
					$new_slug = sanitize_title($data['name']);
					// use this line if you have multiple posts with the same title
					$new_slug = wp_unique_post_slug($new_slug, $postData['ID'], $postData['post_status'], $postData['post_type'], 0);
					$postData['post_name'] = $data['name'];
					$post_id  = wp_update_post($postData);
				} else {
					$post_id = wp_insert_post($postData);
				}

				wp_set_object_terms($post_id, ( ( count($variations) > 0 ) ? 'variable' : 'simple' ), 'product_type');

				update_post_meta($post_id, '_visibility', 'visible');
				update_post_meta($post_id, '_description', $data['description_sale']);
				update_post_meta($post_id, '_sku', $data[$this->odoo_sku_mapping]);
				update_post_meta($post_id, '_product_attributes', array());

				if ('yes' == $this->odoo_settings['odoo_import_update_price']) {
					update_post_meta($post_id, '_regular_price', $data['list_price']);
					update_post_meta($post_id, '_sale_price', $data['list_price']);
					update_post_meta($post_id, '_price', $data['list_price']);

					//commented by Manish
					// $product = wc_get_product($post_id);
					// $product->set_regular_price($data['list_price']);
					// $product->set_sale_price($data['list_price']);
					// $product->set_price($data['list_price']);
					// $product->save();

					if (isset($data['pricelist_item_count']) && $data['pricelist_item_count'] > 0) {
						$this->get_and_set_sale_price($post_id, $data);
					}
				}

				if ('yes' == $this->odoo_settings['odoo_import_update_stocks']) {
					update_post_meta($post_id, '_manage_stock', 'yes');
					update_post_meta($post_id, '_stock', $data['qty_available']);
					$stock_status = ( $data['qty_available'] > 0 ) ? 'instock' : 'outofstock';
					update_post_meta($post_id, '_stock_status', wc_clean($stock_status));
					update_post_meta($post_id, '_saleunit', $data['uom_name'] ? $data['uom_name'] : 'each');
					update_post_meta($post_id, '_stockunit', $data['uom_name'] ? $data['uom_name'] : 'each');
					wp_set_post_terms($post_id, $stock_status, 'product_visibility', true);
				}

				// Stock Management Meta Fields
				update_post_meta($post_id, '_sold_individually', '');
				update_post_meta($post_id, '_weight', $data['weight']);
				update_post_meta($post_id, '_cube', $data['volume']);
				update_post_meta($post_id, '_odoo_id', $data['id']);
				if (isset($data['categ_id'][1])) {
					$category['complete_name'] = $data['categ_id'][1];
					$term_id = $this->create_wc_category($category);
					wp_set_object_terms($post_id, $term_id, 'product_cat');
				}

				if ('' != $data['image_1024']) {
					$helper = WC_ODOO_Helpers::getHelper();
					$attach_id = $helper->save_image( $data );
					set_post_thumbnail( $post_id, $attach_id );
				}

				if (count($variations) > 0) {
					$this->createProductVariations($post_id, $variations);
				}
				return $post_id;
			}
		}

		/**
		 * Creating attributes and setting it touse for variation of products
		 *
		 * @param  [int] $post_id [product id]
		 * @param  [array] $variations [array fo variations]
		 * @return  NULL
		 */
		public function createProductVariations( $post_id, $variations ) {
			$attr = array();
			foreach ($variations['attributes'] as $key => $attribute) {
				$options = $this->setVariationAttributes($attribute['product_template_value_ids']);
				$name = $attribute['display_name'];
				$attribute = new WC_Product_Attribute();
				// $attribute->set_id(0);
				$attribute->set_name($name);
				$attribute->set_options(explode(WC_DELIMITER, $options));
				$attribute->set_visible(true);
				$attribute->set_variation(true);
				$attr[] = $attribute;
			}

			$product = new WC_Product_Variable($post_id);
			$product->set_attributes($attr);
			$product->save();

			$variations_data = $this->createVariationData($variations);

			foreach ($variations_data as $variation_key => $variation_data) {

				$variation = '';
				$variation_id = $this->ifVariationExists($variation_data, $post_id);
				if ($this->ifVariationExists($variation_data, $post_id) !== false) {
					$variation_id = $this->ifVariationExists($variation_data, $post_id);
					if (is_wp_error($variation_id)) {
						return false;
					}
					$variation = new WC_Product_Variation($variation_id);
				} else {
					$variation = new WC_Product_Variation();
					$variation->set_sku($variation_data['sku']);
				}
				$variation->set_parent_id($post_id);
				$variation->set_status(( 1 == $variation_data['isonline'] ) ? 'publish' : 'private');
				if ('yes' == $this->odoo_settings['odoo_import_update_stocks']) {
					$variation->set_manage_stock(true);
					$variation->set_stock_quantity($variation_data['stock_qty']);
				}

				if ('yes' == $this->odoo_settings['odoo_import_update_price']) {

					$variation->set_price($variation_data['regular_price']);
					$variation->set_regular_price($variation_data['regular_price']);
				}
				$variation->set_weight($variation_data['weight']);
				$variation->set_description($variation_data['desc']);
				$variation->save();

				//set variation thumbnail
				// if(isset($variation_data['image']) && !empty($variation_data['image'])){
				//  $parts = parse_url($variation_data['image']);
				//  parse_str($parts['query'], $query);
				//  $attachment_id = get_post_id_by_meta_key_and_value('ns_image_id',$query['id']);
				//  set_post_thumbnail( $variation->get_id(), $attachment_id );
				// }

				foreach ($variation_data['attr_and_value'] as $key => $value) {
					update_post_meta($variation->get_id(), '_odoo_variation_id', $variation_data['id']);
					update_post_meta($variation->get_id(), 'attribute_' . strtolower($key), $value);
				}
			}
			return;
		}

		public function ifVariationExists( $variation_data, $parent_id ) {
			global $wpdb;
			if ('' != $variation_data['sku']) {
				$result =  $wpdb->get_results($wpdb->prepare("SELECT post_id FROM $wpdb->postmeta WHERE meta_key = '_sku' AND meta_value = %s LIMIT 0 , 30", $variation_data['sku']));
				if ($result) {
					return $result[0]->post_id;
				}
			}
			return false;
		}

		public function setVariationAttributes( $variations ) {
			$j = 1;
			$option = '';
			foreach ($variations as $key => $value) {
				if ($j < count($variations)) {
					$option .= $this->odoo_attr_values[$value] . '|';
				} else {
					$option .= $this->odoo_attr_values[$value];
				}
				$j++;
			}
			return $option;
		}

		public function createVariationData( $variations ) {
			$variation_data =  array();
			$att = array();
			foreach ($variations['attributes'] as $key => $value) {
				$att[] = $value['display_name'];
			}
			$attr_list = array();
			foreach ($variations['attributes'] as $key => $attribute) {
				foreach ($attribute['product_template_value_ids'] as $key => $attr_value) {
					$attr_list[$attribute['display_name']][] = $this->odoo_attr_values[$attr_value];
				}
			}
			unset($variations['attributes']);

			foreach ($variations as $key => $value) {
				$variation_data[] = array(
					'id' => $value['id'],
					'sku'           => $value[$this->odoo_sku_mapping],
					'regular_price' => $value['list_price'],
					'sale_price'    => '',
					'stock_qty'     => $value['qty_available'],
					// 'image'       => $value->image,
					'isonline' => $value['active'],
					'weight' => $value['weight'],
					'weightunit' => $value['weight_uom_name'],
					'odoo_id' => $value['id'],
					'desc' => $value['description_sale'],
					'attr_and_value' => $value['attr_and_value'],
				);
			}
			return $variation_data;
		}

		public function create_and_set_category( $post_id, $category_str ) {

			$categories = explode('/', $category_str);
			$taxonomy = 'product_cat';
			$previous_slug = '';
			$last_key = count($categories) - 1;
			$first_key = $categories[0];

			foreach ($categories as $key => $category) {
				if ($last_key == $key) {
					$current_slug = sanitize_title($category);
					$slug = !empty($previous_slug) ? $previous_slug . '_' . $current_slug : $current_slug;
					$term = get_term_by('slug', $slug, $taxonomy);

					if (isset($term) && isset($term->term_id)) {
						$termid = $term->term_id;
					} else {
						$term = wp_insert_term($category, $taxonomy, array(
							'description' => 'Description for category',
							'parent' => 0,
							'slug' => $slug,
						));
					}
				}
			}
		}

		public function do_import_categories() {

			$odooApi = new WC_ODOO_API();
			$categories = $odooApi->readAll('product.category', array( 'id', 'name', 'complete_name', 'child_id', 'parent_id', '__last_update' ));
			$new_cats = array();
			if (!isset($categories['fail']) && is_array($categories) && count($categories) > 0) {

				foreach ($categories as $key => $category) {
					if (count($category['child_id']) > 0) {
						foreach ($category['child_id'] as $child) {
							$childkey = array_search($child, array_column($categories, 'id'));
							$categories[$key]['childs'][] = $categories[$childkey];
						}
					}
					$new_cats[$category['id']] = $categories[$key];
				}
				ksort($new_cats);
				foreach ($new_cats as $key => $cat) {
					$this->create_wc_product_category($cat);
				}
			}
		}

		public function create_wc_product_category( $category ) {
			$cat_id = $this->create_wc_category($category);
			if (false != $cat_id && isset($category['childs']) && count($category['childs']) > 0) {
				foreach ($category['childs'] as $key => $child_cat) {
					$this->create_wc_category($child_cat, $cat_id);
				}
			}
		}

		public function create_wc_category( $category, $parent_cat = 0 ) {
			$termid = false;
			$taxonomy = 'product_cat';
			$slug = sanitize_title($category['complete_name']);
			$term = get_term_by('slug', $slug, $taxonomy);
			// $name = isset( $category['name'] ) ? $category['name'] : $category['complete_name'];
			$name = isset($category['name']) ? $category['name'] : ( isset($category['complete_name'])  ? $category['complete_name'] : '' );
			if (isset($term) && isset($term->term_id)) {
				$termid = $term->term_id;
			} else {

				$term =  wp_insert_term(
					$name,
					$taxonomy,
					array(
						'description'   => $category['complete_name'],
						'parent'          => $parent_cat,
						'slug' => $slug,
					)
				);

				if (is_wp_error($term)) {
					return false;
				}
				if (isset($term['term_id'])) {
					$termid = $term['term_id'];
				}
			}
			return $termid;
		}

		public function do_export_categories() {
			$taxonomy     = 'product_cat';
			$orderby      = 'term_id';
			$show_count   = 0;
			$pad_counts   = 0;
			$hierarchical = 1;
			$title        = '';
			$empty        = 0;

			$args = array(
				'taxonomy'     => $taxonomy,
				'orderby'      => $orderby,
				'show_count'   => $show_count,
				'pad_counts'   => $pad_counts,
				'hierarchical' => $hierarchical,
				'title_li'     => $title,
				'hide_empty'   => $empty,
			);

			$all_categories = get_categories($args);
			$categories =  json_decode(json_encode($all_categories), true);
			// $odooApi = new WC_ODOO_API();

			foreach ($categories as $key => $cat) {
				if (0 != $cat['parent']) {
					$parent_key = array_search($cat['parent'], array_column($categories, 'term_id'));
					$cat['parent_cat'] = $categories[$parent_key];
				}
				$cat_id = $cat['cat_ID'];
				$response = $this->create_category_to_odoo($cat);
			}
		}

		public function create_category_to_odoo( $category ) {

			$odoo_term_id = false;
			$cat_id = $category['cat_ID'];
			$odoo_term_id = get_term_meta($cat_id, '_odoo_term_id', true);

			if ($odoo_term_id) {
				return $odoo_term_id;
			} else {
				$odooApi = new WC_ODOO_API();
				$odoo_term_id = $odooApi->search_record('product.category', array( array( 'name', '=', $category['name'] ) ));
				if (isset($odoo_term_id['fail'])) {
					$error_msg = 'Error for Search Category => ' . $cat_id . ' Msg : ' . print_r($odoo_term_id['msg'], true);
					$odooApi->addLog($error_msg);
					return false;
				}
				if ($odoo_term_id) {
					return $odoo_term_id;
				} else {
					$data = array(
						'name' => $category['name'],
					);
					if (isset($category['parent_cat'])) {
						$response = $this->get_parent_category($category['parent_cat']);
						if (isset($response['fail'])) {
							$error_msg = 'Error for Creating  Parent category for Id  => ' . $cat_id . ' Msg : ' . print_r($response['msg'], true);
							$odooApi->addLog($error_msg);
							return $response;
						} else {
							update_term_meta($category['parent_cat']['cat_ID'], '_odoo_term_id', $response);
							// return $response;
							$data['parent_id'] = (int) $response;
						}
					}
					$response = $odooApi->create_record('product.category', $data);
					if (isset($response['fail'])) {
						$error_msg = 'Error for Creating category for Id  => ' . $cat_id . ' Msg : ' . print_r($response['msg'], true);
						$odooApi->addLog($error_msg);
					} else {
						update_term_meta($cat_id, '_odoo_term_id', $response);
						return $response;
					}
				}
			}
		}

		public function get_parent_category( $category ) {
			$cat_id = $category['cat_ID'];
			$odoo_term_id = get_term_meta($cat_id, '_odoo_term_id', true);

			if ($odoo_term_id) {
				return $odoo_term_id;
			} else {
				$odooApi = new WC_ODOO_API();
				$odoo_term_id = $odooApi->search_record('product.category', array( array( 'name', '=', $category['name'] ) ));
				if (isset($odoo_term_id['fail'])) {
					$error_msg = 'Error for Search Category => ' . $cat_id . ' Msg : ' . print_r($odoo_term_id['msg'], true);
					$odooApi->addLog($error_msg);
					return false;
				}
				if ($odoo_term_id) {
					return $odoo_term_id;
				} else {
					$data = array(
						'name' => $category['name'],
					);
					if (isset($category['parent_cat'])) {
						$response = $this->get_parent_category($category['parent_cat']);
						if (isset($response['fail'])) {
							$error_msg = 'Error for Creating category for Id  => ' . $cat_id . ' Msg : ' . print_r($response['msg'], true);
							$odooApi->addLog($error_msg);
							return $response;
						} else {
							update_term_meta($cat_id, '_odoo_term_id', $response);
							// return $response;
							$data['parent_id'] = $response;
							return $response;
						}
					}
					return $odooApi->create_record('product.category', $data);
				}
			}
		}

		public function do_export_attributes() {
			$attribute_taxonomies = wc_get_attribute_taxonomies();
			$taxonomy_terms = array();
			if ($attribute_taxonomies) {
				foreach ($attribute_taxonomies as $tax) {
					if (taxonomy_exists(wc_attribute_taxonomy_name($tax->attribute_name))) {
						$taxonomy_terms[ $tax->attribute_name ] = get_terms( array(
							'taxonomy' => wc_attribute_taxonomy_name($tax->attribute_name),
							'orderby' => 'name',
							'hide_empty' => false,
						) );
					};
					$taxonomy_terms[$tax->attribute_name]['attr'] = $tax;
				};
			};


			foreach ($taxonomy_terms as $key => $taxonomy_term) {
				$attr_id = $this->create_attributes_to_odoo($taxonomy_term);
				unset($taxonomy_term['attr']);
				if (false != $attr_id && $attr_id > 0) {
					foreach ($taxonomy_term as $taxonomy_value) {
						$attr_value = $this->create_attributes_value_to_odoo($attr_id, $taxonomy_value);
					}
				}
			}
		}

		public function create_attributes_to_odoo( $term ) {

			$odooApi = new WC_ODOO_API();
			if (is_string($term)) {
				$attr_name = $term;
				$attr_type = 'select';
				$attr_id = $term;
			} else {
				$attribute = $term['attr'];
				$attr_name = $attribute->attribute_name;
				$attr_type = $attribute->attribute_type;
				$attr_id = $attribute->attribute_id;
				unset($term);
			}
			$odoo_attr_id = $odooApi->search_record('product.attribute', array( '|', array( 'name', '=', $attr_name ), array( 'name', '=', ucfirst($attr_name) ) ));
			if (isset($odoo_attr_id['fail'])) {
				$error_msg = 'Error for Search attributes => ' . $attr_id . ' Msg : ' . print_r($odoo_attr_id['msg'], true);
				$odooApi->addLog($error_msg);
				return false;
			}

			if ($odoo_attr_id) {
				return $odoo_attr_id;
			} else {

				$data = array(
					'name' => $attr_name,
					'display_type' => $attr_type,
					'create_variant' => 'always',
				);
				$odoo_attr_id = $odooApi->create_record('product.attribute', $data);
				if (isset($odoo_attr_id['fail'])) {
					$error_msg = 'Error for Search attributes => ' . $attr_id . ' Msg : ' . print_r($odoo_attr_id['msg'], true);
					$odooApi->addLog($error_msg);
					return false;
				}
				return $odoo_attr_id;
			}
		}

		public function create_attributes_value_to_odoo( $attr_id, $attr_value ) {
			$odooApi = new WC_ODOO_API();
			if (is_string($attr_value)) {
				$value_name = $attr_value;
			} else {
				$value_name = $attr_value->name;
			}
			$odoo_attr_value_id = $odooApi->search_record('product.attribute.value', array( array( 'name', '=', $value_name ), array( 'attribute_id', '=', $attr_id ) ));
			if (isset($odoo_attr_value_id['fail'])) {
				$error_msg = 'Error for Search attributes value => ' . $value_name . ' Msg : ' . print_r($odoo_attr_value_id['msg'], true);
				$odooApi->addLog($error_msg);
				return false;
			}

			if ($odoo_attr_value_id) {
				return $odoo_attr_value_id;
			} else {
				$data = array(
					'name' => $value_name,
					'attribute_id' => $attr_id,
				);
				$odoo_attr_value_id = $odooApi->create_record('product.attribute.value', $data);
				if (isset($odoo_attr_value_id['fail'])) {
					$error_msg = 'Error for Creating attributes value => ' . $value_name . ' Msg : ' . print_r($odoo_attr_value_id['msg'], true);
					$odooApi->addLog($error_msg);
					return false;
				}
				return $odoo_attr_value_id;
			}
		}

		public function do_import_attributes() {

			$odooApi = new WC_ODOO_API();
			$attrs = $odooApi->readAll('product.attribute', array( 'id', 'name', 'value_ids', 'display_type' ));
			$attr_values = $odooApi->readAll('product.attribute.value', array( 'id', 'name', 'display_type' ));
			if (isset($attrs['fail']) || isset($attr_values['fail'])) {
				return false;
			}
			foreach ($attrs as $attr) {
				$attr_id = $this->create_attribute_to_wc($attr);
				update_term_meta($attr_id, '_odoo_attr_id', $attr['id']);
				if ($attr_id) {
					$attribute = wc_get_attribute($attr_id);
					foreach ($attr['value_ids'] as $attr_term) {
						$term_key = array_search($attr_term, array_column($attr_values, 'id'));
						if (isset($attr_values[$term_key])) {
							$attr_value_id = $this->create_attribute_value_to_wc($attribute, $attr_values[$term_key]);
							if (false != $attr_value_id) {
								update_term_meta($attr_value_id, '_odoo_attr_id', $attr_term);
							}
						}
					}
				}
			}
		}

		public function create_attribute_to_wc( $attr ) {


			global $wc_product_attributes;
			$raw_name = $attr['name'];
			// Make sure caches are clean.
			delete_transient('wc_attribute_taxonomies');
			WC_Cache_Helper::incr_cache_prefix('woocommerce-attributes');

			// These are exported as labels, so convert the label to a name if possible first.
			$attribute_labels = wp_list_pluck(wc_get_attribute_taxonomies(), 'attribute_label', 'attribute_name');
			$attribute_name   = array_search($raw_name, $attribute_labels, true);

			if (!$attribute_name) {
				$attribute_name = wc_sanitize_taxonomy_name($raw_name);
			}

			$attribute_id = wc_attribute_taxonomy_id_by_name($attribute_name);

			if (!$attribute_id) {
				$taxonomy_name = wc_attribute_taxonomy_name($attribute_name);

				// Degister taxonomy which other tests may have created...
				unregister_taxonomy($taxonomy_name);

				$attribute_id = wc_create_attribute(
					array(
						'name'         => $raw_name,
						'slug'         => $attribute_name,
						'type'         => $attr['display_type'],
						'order_by'     => 'menu_order',
						'has_archives' => 0,
					)
				);

				// Register as taxonomy.
				register_taxonomy(
					$taxonomy_name,
					/**
					 * Object type with which the taxonomy should be associated.
					 *
					 * @since  1.3.4
					 */
					apply_filters('woocommerce_taxonomy_objects_' . $taxonomy_name, array( 'product' )),

					/**
					 * Array of arguments for registering taxonomy
					 *
					 * @since  1.3.4
					 */
					apply_filters(
						'woocommerce_taxonomy_args_' . $taxonomy_name,
						array(
							'labels'       => array(
								'name' => $raw_name,
							),
							'hierarchical' => false,
							'show_ui'      => false,
							'query_var'    => true,
							'rewrite'      => false,
						)
					)
				);

				// Set product attributes global.
				$wc_product_attributes = array();

				foreach (wc_get_attribute_taxonomies() as $taxonomy) {
					$wc_product_attributes[wc_attribute_taxonomy_name($taxonomy->attribute_name)] = $taxonomy;
				}
			}

			if ($attribute_id) {
				return $attribute_id;
			}
		}

		public function create_attribute_value_to_wc( $attribute, $term ) {
			$result = term_exists($term['name'], $attribute->slug);
			if (!$result) {
				$result = wp_insert_term($term['name'], $attribute->slug);
				if (is_wp_error($result)) {
					return false;
				}
				$term_id = $result['term_id'];
			} else {
				$term_id = $result['term_id'];
			}
			return $term_id;
		}

		public  function do_export_product_odoo() {

			if ($this->export_products->is_process_running()) {
				echo json_encode(array( 'status'=> 0, 'message'=> __('Product export is already running.', 'wc-odoo-integration') ));
				die;
			}
			
			global $wpdb;
			$products = array();
			$exclude_cats = implode(',', $this->odoo_settings['odoo_exclude_product_category']);
			if ('yes' == $this->odoo_settings['odoo_export_create_product']) {
				$products = $wpdb->get_results(
					$wpdb->prepare(
						"SELECT ID ,{$wpdb->posts}.`post_type` FROM {$wpdb->posts} LEFT JOIN  $wpdb->term_relationships  as t
                            ON ID = t.object_id WHERE (post_type='product') AND post_status='publish' AND t.term_taxonomy_id NOT IN (%s)",
						array( $exclude_cats )
					));
			} else if ('no' == $this->odoo_settings['odoo_export_update_stocks']) {
				$products = $wpdb->get_results(
					$wpdb->prepare(
						"SELECT {$wpdb->posts}.`ID`,{$wpdb->posts}.`post_type` FROM {$wpdb->posts} LEFT JOIN  $wpdb->term_relationships  as t
                            ON ID = t.object_id  WHERE (post_type='product') AND post_status='publish' AND NOT EXISTS (
              SELECT {$wpdb->postmeta}.`post_id` FROM {$wpdb->postmeta}
               WHERE {$wpdb->postmeta}.`meta_key` = '_odoo_id'
                AND {$wpdb->postmeta}.`post_id`={$wpdb->posts}.ID
            ) AND t.term_taxonomy_id NOT IN (%s) ",
						array( $exclude_cats )
					));
			}
			$this->export_products->empty_data();
			update_option('woo_product_synced_to_odoo_count', 0 );

			$products = array_unique($products, SORT_REGULAR);

			foreach ($products as $key => $product_obj) {
				$this->export_products->push_to_queue($product_obj->ID);
			}
			$total_products_count = count($products);
			update_option('opmc_odoo_product_export_count', $total_products_count);
			update_option('opmc_odoo_product_export_remaining_count', $total_products_count);

			$this->export_products->save()->dispatch();
			echo json_encode(array( 'status'=> 1, 'message'=> __('Export process has started for ', 'wc-odoo-integration') . $total_products_count . __(' products', 'wc-odoo-integration') ));

			exit;
		}

		public function sync_to_odoo( $item ) {
			$product = wc_get_product($item);

			$odooApi = new WC_ODOO_API();

			$odooApi->addLog('Product ID : ' . print_r($item, true));

			$syncable_product = get_post_meta($product->get_id(), '_exclude_product_to_sync', true);

			if ('yes' == $syncable_product) {
				return;
			}

			if ($product->has_child()) {
				return;
				$odoo_template_id = get_post_meta($product->get_id(), '_odoo_id', true);
				if ($odoo_template_id) {
					$this->do_export_variable_product_update((int) $odoo_template_id, $product);
				} else {
					$this->do_export_variable_product($product);
				}
			} else {
				$odoo_product_id = get_post_meta($product->get_id(), '_odoo_id', true);

				// Search Product on Odoo
				if (!$odoo_product_id) {
					$conditions = array(
						array(
							$this->odoo_sku_mapping, '=', $product->get_sku(),
						),
					);
					$odoo_product_id = $this->search_odoo_product($conditions, $product->get_id());
				}

				if ($odoo_product_id) {
					$this->update_odoo_product((int) $odoo_product_id, $product);
				} else {
					$odoo_product_id = $this->create_product($product);
				}
				if (isset($odoo_product_id['fail'])) {
					$error_msg = 'Error for Creating/Updating  Product Id  => ' . $product->get_id() . ' Msg : ' . print_r($odoo_product_id['msg'], true);
					$odooApi->addLog($error_msg);
					return;
				}
				if (false == $odoo_product_id) {
					return;
				}
				update_post_meta($product->get_id(), '_odoo_id', $odoo_product_id);
				if ('yes' == $this->odoo_settings['odoo_export_update_price']) {
					if ($product->is_on_sale()) {
						$odoo_extra_product = get_post_meta($product->get_id(), '_product_extra_price_id', true);
						if ($odoo_extra_product) {
							$this->update_extra_price($odoo_extra_product, $product);
						} else {
							$this->create_extra_price($odoo_product_id, $product);
						}
					}
				}
				if ('yes' == $this->odoo_settings['odoo_export_update_stocks']) {
					if ($product->get_stock_quantity() > 0) {

						$product_qty = number_format((float) $product->get_stock_quantity(), 2, '.', '');
						$res = $this->update_product_quantity($odoo_product_id, $product_qty);
						$odooApi->addLog('Product quantity update Res : ' . print_r($res, true));
					}
				}
				update_post_meta($product->get_id(), '_odoo_image_id', $product->get_image_id());
			}
		}


		public function do_export_product() {
			global $wpdb;
			$products = array();
			$exclude_cats = implode(',', $this->odoo_settings['odoo_exclude_product_category']);
			if ('yes' == $this->odoo_settings['odoo_export_create_product']) {
				$products = $wpdb->get_results(
					$wpdb->prepare(
						"SELECT ID ,{$wpdb->posts}.`post_type` FROM {$wpdb->posts} LEFT JOIN  $wpdb->term_relationships  as t
                            ON ID = t.object_id WHERE (post_type='product') AND post_status='publish' AND t.term_taxonomy_id NOT IN (%s)",
						array( $exclude_cats )
					));
			} else if ('no' == $this->odoo_settings['odoo_export_update_stocks']) {
				$products = $wpdb->get_results(
					$wpdb->prepare(
						"SELECT {$wpdb->posts}.`ID`,{$wpdb->posts}.`post_type` FROM {$wpdb->posts} LEFT JOIN  $wpdb->term_relationships  as t
                            ON ID = t.object_id  WHERE (post_type='product') AND post_status='publish' AND NOT EXISTS (
              SELECT {$wpdb->postmeta}.`post_id` FROM {$wpdb->postmeta}
               WHERE {$wpdb->postmeta}.`meta_key` = '_odoo_id'
                AND {$wpdb->postmeta}.`post_id`={$wpdb->posts}.ID
            ) AND t.term_taxonomy_id NOT IN (%s) ",
						array( $exclude_cats )
					));
			}
			$odooApi = new WC_ODOO_API();

//            $odooApi->addLog('Product ID : '. print_r($products, true));
//            $odooApi->addLog('Product unique ID : '. print_r(array_unique($products, SORT_REGULAR), true));
//            return;

			// Remove duplicate products ids
			$products = array_unique($products, SORT_REGULAR);

			foreach ($products as $key => $product_obj) {
				$product = wc_get_product($product_obj->ID);

				$odooApi->addLog('Product ID : ' . print_r($product_obj->ID, true));

				$syncable_product = get_post_meta($product->get_id(), '_exclude_product_to_sync', true);

				if ('yes' == $syncable_product) {
					continue;
				}

				if ($product->has_child()) {
					continue;
					$odoo_template_id = get_post_meta($product->get_id(), '_odoo_id', true);
					if ($odoo_template_id) {
						$this->do_export_variable_product_update((int) $odoo_template_id, $product);
					} else {
						$this->do_export_variable_product($product);
					}
				} else {
					$odoo_product_id = get_post_meta($product->get_id(), '_odoo_id', true);

					// Search Product on Odoo
					if (!$odoo_product_id) {
						$conditions = array(
							array(
								$this->odoo_sku_mapping, '=', $product->get_sku(),
							),
						);
						$odoo_product_id = $this->search_odoo_product($conditions, $product->get_id());
					}

					if ($odoo_product_id) {
						$this->update_odoo_product((int) $odoo_product_id, $product);
					} else {
						$odoo_product_id = $this->create_product($product);
					}
					if (isset($odoo_product_id['fail'])) {
						$error_msg = 'Error for Creating/Updating  Product Id  => ' . $product->get_id() . ' Msg : ' . print_r($odoo_product_id['msg'], true);
						$odooApi->addLog($error_msg);
						continue;
					}
					if (false == $odoo_product_id) {
						continue;
					}
					update_post_meta($product->get_id(), '_odoo_id', $odoo_product_id);
					if ('yes' == $this->odoo_settings['odoo_export_update_price']) {
						if ($product->is_on_sale()) {
							$odoo_extra_product = get_post_meta($product->get_id(), '_product_extra_price_id', true);
							if ($odoo_extra_product) {
								$this->update_extra_price($odoo_extra_product, $product);
							} else {
								$this->create_extra_price($odoo_product_id, $product);
							}
						}
					}
					if ('yes' == $this->odoo_settings['odoo_export_update_stocks']) {
						if ($product->get_stock_quantity() > 0) {

							$product_qty = number_format((float) $product->get_stock_quantity(), 2, '.', '');
							$res = $this->update_product_quantity($odoo_product_id, $product_qty);
							$odooApi->addLog('Product quantity update Res : ' . print_r($res, true));
						}
					}
					update_post_meta($product->get_id(), '_odoo_image_id', $product->get_image_id());
				}
			}
		}

		public function do_export_variable_product_update( $odoo_template_id, $product ) {
			$odooApi = new WC_ODOO_API();
			$attrs = $odooApi->readAll('product.attribute.value', array( 'id', 'name', 'display_type', 'attribute_id', 'pav_attribute_line_ids' ));
			$odoo_attrs = array();
			foreach ($attrs as $akey => $attr) {
				$odoo_attrs[strtolower($attr['attribute_id'][1])][strtolower($attr['name'])] = $attr;
			}
			$attr_values = $odooApi->readAll('product.template.attribute.value', array( 'id', 'name', 'attribute_line_id', 'attribute_id' ));
			$aaa = array();
			foreach ($attr_values as $avkey => $attr_value) {
				$aaa[strtolower($attr_value['attribute_id'][1])][] = $attr_value;
			}
			$helper = WC_ODOO_Helpers::getHelper();
			$template_data = array(
				'name' => $product->get_name(),
				'sale_ok' => true,
				'type' => 'product',
				$this->odoo_sku_mapping => $product->get_sku(),
				'description_sale' => $product->get_description(),
				// 'categ_id'   => $this->get_category_id( $product->get_id() ),
				'attribute_line_ids' => $this->get_attributes_line_ids($odoo_attrs, $product->get_attributes()),
			);
			if ('yes' == $this->odoo_settings['odoo_export_create_categories']) {
				$template_data['categ_id'] = $this->get_category_id($product);
			}
			if ('yes' == $this->odoo_settings['odoo_export_update_price']) {
				$template_data['list_price'] = $product->get_price();
			}
			if ($helper->can_upload_image($product)) {
				$template_data['image_1920'] = $helper->upload_product_image($product);
			}

			$template = $odooApi->update_record('product.template', array( $odoo_template_id ), $template_data);

			update_post_meta($product->get_id(), '_odoo_id', $odoo_template_id);
			$odoo_products = $odooApi->readAll('product.product', array(), array( array( 'product_tmpl_id', '=', $odoo_template_id ) ));

			$pta_values_id = array_unique(call_user_func_array('array_merge', array_column($odoo_products, 'product_template_attribute_value_ids')));
			sort($pta_values_id);
			$pta_values = $odooApi->fetch_record_by_ids('product.template.attribute.value', $pta_values_id, array( 'id', 'name', 'product_attribute_value_id', 'attribute_line_id', 'attribute_id' ));

			foreach ($product->get_children() as $key => $child) {
				$child_product = wc_get_product($child);
				//$child_product = new WC_Product($child);
				foreach ($odoo_products as $opkey => $odoo_product) {
					foreach ($odoo_product['product_template_attribute_value_ids']  as $value_id) {
						$vkey = array_search($value_id, array_column($pta_values, 'id'));
						$odoo_product['pta_value'][] = strtolower($pta_values[$vkey]['name']);
					}
					$wcav = $child_product->get_attributes();

					sort($odoo_product['pta_value']);
					sort($wcav);

					if ($odoo_product['pta_value'] == $wcav) {

						$child_data = array(
							$this->odoo_sku_mapping => $child_product->get_sku(),
						);

						if ('yes' == $this->odoo_settings['odoo_export_update_price']) {
							$child_data['list_price'] = $child_product->get_price();
						}
						if ($helper->can_upload_image($child_product)) {
							$child_data['image_1920'] = $helper->upload_product_image($child_product);
						}
						$res = $odooApi->update_record('product.product', $odoo_product['id'], $child_data);

						if ('yes' == $this->odoo_settings['odoo_export_update_stocks']) {
							if ($child_product->get_stock_quantity() > 2) {

								$product_qty = number_format((float) $child_product->get_stock_quantity(), 2, '.', '');
								$res = $this->update_product_quantity($odoo_product['id'], $product_qty, $template);
							}
						}
						update_post_meta($child_product->get_id(), '_odoo_id', $odoo_product['id']);
						update_post_meta($child_product->get_id(), '_odoo_image_id', $child_product->get_image_id());
					}
					unset($odoo_product['pta_value']);
					unset($wcav);
				}
			}
		}

		public function update_odoo_product( $odoo_product_id, $product ) {
			$odooApi = new WC_ODOO_API();
			if ($product->get_sku() == '') {
				$error_msg = '[Product Export] [Error] [Products ID : ' . $product_data->get_id() . ' have missing/invalid SKU. This product will not be exported. ]';
				$odooApi->addLog($error_msg);
				return false;
			}
			$helper = WC_ODOO_Helpers::getHelper();
			$data = array(
				'name' => $product->get_name(),
				'sale_ok' => true,
				'type' => 'product',
				$this->odoo_sku_mapping => $product->get_sku(),
				'description_sale' => $product->get_description(),
				'weight'        => $product->get_weight(),
				'volume'        => (int) ( (int) $product->get_height() * (int) $product->get_length() * (int) $product->get_width() ),
			);
			if ('yes' == $this->odoo_settings['odoo_export_create_categories']) {
				$data['categ_id'] = $this->get_category_id($product);
			}
			if ('yes' == $this->odoo_settings['odoo_export_update_price']) {
				//$data['list_price'] = $product->get_regular_price();
				$data['list_price'] = $product->get_sale_price() ? $product->get_sale_price() : $product->get_regular_price();

				if ($product->is_on_sale()) {
					$odoo_extra_product = get_post_meta($product->get_id(), '_product_extra_price_id', true);
					if ($odoo_extra_product) {
						$this->update_extra_price($odoo_extra_product, $product);
					} else {
						$this->create_extra_price($odoo_product_id, $product);
					}
				}
			}
			if ($helper->can_upload_image($product)) {
				$data['image_1920'] = $helper->upload_product_image($product);
			}
			return $odooApi->update_record('product.product', array( $odoo_product_id ), $data);
		}

		public function do_export_customer() {
			$args = array(
				'role' => 'customer',
				'order' => 'ASC',
				'orderby' => 'ID',
				'number' => -1,
			);
			$wp_user_query = new WP_User_Query($args);
			$customers = $wp_user_query->get_results();

			foreach ($customers as $key => $customer) {
				$customer_id = get_user_meta($customer->ID, '_odoo_id', true);
				if (!$customer_id) {
					$conditions = array(
						array( 'type', '=', 'contact' ),
						array( 'email', '=', $customer->user_email ),
					);
					$customer_id = $this->search_odoo_customer($conditions, $customer->ID);
				}

				if ($customer_id) {
					$this->update_customer_to_odoo((int) $customer_id, $customer);
				} else {
					$customer_id = $this->create_customer($customer);
				}
				if (false == $customer_id) {
					continue;
				}
				update_user_meta($customer->ID, '_odoo_id', $customer_id);
				$this->action_woocommerce_customer_save_address($customer->ID, 'shipping');
				$this->action_woocommerce_customer_save_address($customer->ID, 'billing');
			}
		}

		public function do_export_order() {
			global $wpdb;

			$from_date = '';
			$to_date = '';

			if (isset($this->odoo_settings['odoo_export_order_from_date']) && !empty($this->odoo_settings['odoo_export_order_from_date'])) {
				$from_date =  $wpdb->prepare(' AND  p.post_date >= %s ', $this->odoo_settings['odoo_export_order_from_date']);
			}
			if (isset($this->odoo_settings['odoo_export_order_to_date']) && !empty($this->odoo_settings['odoo_export_order_to_date'])) {
				$to_date = $wpdb->prepare(' AND  p.post_date <= %s ', $this->odoo_settings['odoo_export_order_to_date']);
			}

			/*$orders = $wpdb->get_results("
				SELECT pm.post_id AS order_id
				FROM {$wpdb->prefix}postmeta AS pm
				LEFT JOIN {$wpdb->prefix}posts AS p
				ON pm.post_id = p.ID
				WHERE p.post_type = 'shop_order'
				AND ( p.post_status = 'wc-completed' OR p.post_status = 'wc-processing' )
				$from_date $to_date
				AND pm.meta_key = '_customer_user'
				ORDER BY pm.meta_value ASC, pm.post_id DESC
				");*/

			$orders = $wpdb->get_results(
				$wpdb->prepare("
				SELECT pm.post_id AS order_id
				FROM {$wpdb->prefix}postmeta AS pm
				LEFT JOIN {$wpdb->prefix}posts AS p
				ON pm.post_id = p.ID
				WHERE p.post_type = 'shop_order'
				%s %s
				AND pm.meta_key = '_customer_user'
				ORDER BY pm.meta_value ASC, pm.post_id DESC
				", $from_date, $to_date)
			);

			foreach ($orders as $key => $order) {
				$order_id = get_post_meta($order->order_id, '_odoo_order_id', true);
				if (!$order_id) {
					$this->order_create($order->order_id);
				}
			}
		}

		public function do_export_variable_product( $product ) {
			$odooApi = new WC_ODOO_API();
			$attrs = $odooApi->readAll('product.attribute.value', array( 'id', 'name', 'display_type', 'attribute_id', 'pav_attribute_line_ids' ));
			$odoo_attrs = array();
			foreach ($attrs as $akey => $attr) {
				$odoo_attrs[strtolower($attr['attribute_id'][1])][strtolower($attr['name'])] = $attr;
			}

			$helper = WC_ODOO_Helpers::getHelper();
			$template_data = array(
				'name' => $product->get_name(),
				'sale_ok' => true,
				'type' => 'product',
				$this->odoo_sku_mapping => $product->get_sku(),
				'description_sale' => $product->get_description(),
				// 'categ_id'   => $this->get_category_id( $product->get_id() ),
				'attribute_line_ids' => $this->get_attributes_line_ids($odoo_attrs, $product->get_attributes()),
			);
			if ('yes' == $this->odoo_settings['odoo_export_create_categories']) {
				$template_data['categ_id'] = $this->get_category_id($product);
			}
			if ('yes' == $this->odoo_settings['odoo_export_update_price']) {
				$template_data['list_price'] = $product->get_price();
			}
			if ($helper->can_upload_image($product)) {
				$template_data['image_1920'] = $helper->upload_product_image($product);
			}

			$template = $odooApi->create_record('product.template', $template_data);
			$attr_values = $odooApi->readAll('product.template.attribute.value', array( 'id', 'name', 'attribute_line_id', 'attribute_id' ), array( array( 'product_tmpl_id', '=', $template ) ));
			update_post_meta($product->get_id(), '_odoo_id', $template);
			$odoo_products = $odooApi->readAll('product.product', array(), array( array( 'product_tmpl_id', '=', $template ) ));

			$pta_values_id = array_unique(call_user_func_array('array_merge', array_column($odoo_products, 'product_template_attribute_value_ids')));
			sort($pta_values_id);

			$pta_values = $odooApi->fetch_record_by_ids('product.template.attribute.value', $pta_values_id, array( 'id', 'name', 'product_attribute_value_id', 'attribute_line_id', 'attribute_id' ));

			foreach ($product->get_children() as $key => $child) {
				$child_product = wc_get_product($child);
				//$child_product = new WC_Product($child);

				foreach ($odoo_products as $opkey => $odoo_product) {
					foreach ($odoo_product['product_template_attribute_value_ids']  as $value_id) {
						$vkey = array_search($value_id, array_column($pta_values, 'id'));
						$odoo_product['pta_value'][] = strtolower($pta_values[$vkey]['name']);
					}
					$wcav = $child_product->get_attributes();


					sort($odoo_product['pta_value']);
					sort($wcav);
					$child_data = array();
					if ($odoo_product['pta_value'] == $wcav) {

						$child_data = array(
							$this->odoo_sku_mapping => $child_product->get_sku(),
						);

						// if ('yes' == $this->odoo_settings['odoo_export_update_price']) {
						//  $child_data['list_price'] = $child_product->get_price();
						// }
						if ($helper->can_upload_image($child_product)) {
							$child_data['image_1920'] = $helper->upload_product_image($child_product);
						}
						$res = $odooApi->update_record('product.product', array( (int) $odoo_product['id'] ), $child_data);

						if ('yes' == $this->odoo_settings['odoo_export_update_stocks']) {
							$product_qty = number_format((float) $child_product->get_stock_quantity(), 2, '.', '');
							$res = $this->update_product_quantity($odoo_product['id'], $product_qty);
						}
						update_post_meta($child_product->get_id(), '_odoo_id', $odoo_product['id']);
						update_post_meta($child_product->get_id(), '_odoo_varitaion_id', $odoo_product['id']);
						update_post_meta($child_product->get_id(), '_odoo_image_id', $child_product->get_image_id());
					}
					unset($odoo_product['pta_value']);
					unset($wcav);
				}
			}
		}

		public function get_attributes_line_ids( $attr_values, $product_attributes ) {
			$odoo_attr_line = array();

			foreach ($product_attributes as $key => $product_attribute) {
				if (is_array($product_attribute)) {
					if ($product_attribute->get_id() > 0) {
						$attr_name = strtolower(wc_get_attribute($product_attribute->get_id())->name);
					} else {
						$attr_name = $product_attribute->get_name();
					}

					$attr_val_ids = array();
					if (isset($attr_values[$attr_name])) {
						$attr_id = reset($attr_values[$attr_name])['attribute_id'][0];
						foreach ($product_attribute->get_options() as $okey => $option_id) {
							$term = get_term($option_id);
							if (isset($attr_values[$attr_name][$term->name])) {
								$attr_val_ids[] = $attr_values[$attr_name][$term->name]['id'];
							} else {
								$attr_val_ids[] = $this->create_attributes_value_to_odoo($attr_id, $term);
							}
						}
					} else {
						if (null == wc_get_attribute($product_attribute->get_id())) {
							$attr_id = $this->create_attributes_to_odoo($attr_name);
						} else {
							$attr_id = $this->create_attributes_to_odoo(wc_get_attribute($product_attribute->get_id()));
						}

						foreach ($product_attribute->get_options() as $okey => $option_id) {
							if (null == wc_get_attribute($product_attribute->get_id())) {
								$term = $option_id;
							} else {
								$term = get_term($option_id);
							}
							$attr_val_ids[] = $this->create_attributes_value_to_odoo($attr_id, $term);
						}
					}

					$odoo_attr_line[] = array(
						0, 'virtual_' . implode('', $attr_val_ids),
						array(
							'attribute_id' => $attr_id, 'value_ids' => array(
							array(
								6,
								false,
								$attr_val_ids,
							),

						),
						),
					);
				}
			}
			return $odoo_attr_line;
		}

		public function do_import_coupon() {
			$odooApi = new WC_ODOO_API();
			$coupons = $odooApi->search_records('coupon.program', array( array( 'program_type', '=', 'coupon_program' ) ));

			if (!isset($coupons['fail']) && is_array($coupons) && count($coupons) > 0) {
				foreach ($coupons as $key => $coupon) {
					if ($coupon['coupon_count'] > 0) {
						$this->create_coupon_to_wc($coupon);
					}
				}
				//exit;
			}
		}

		public function create_coupon_to_wc( $odoo_coupon ) {

			$odooApi = new WC_ODOO_API();
			$coupons = $odooApi->fetch_record_by_ids('coupon.coupon', $odoo_coupon['coupon_ids']);
			if (!isset($coupons['fail']) && is_array($coupons) && count($coupons)) {

				foreach ($coupons as $key => $coupon) {
					$coupon_code = $coupon['code'];
					$amount = $odoo_coupon['discount_percentage'];
					if ('percentage' == $odoo_coupon['discount_type']) {
						if ('on_order' == $odoo_coupon['discount_apply_on']) {
							$discount_type = 'percent';
						} else if ('specific_products' == $odoo_coupon['discount_apply_on']) {
							$discount_type = 'percent_product';
						}
						$amount = $odoo_coupon['discount_percentage'];
					} elseif ('fixed_amount' == $odoo_coupon['discount_type']) {
						$discount_type = 'fixed_cart';
						$amount = $odoo_coupon['discount_fixed_amount'];
					}

					/* Type: fixed_cart, percent, fixed_product, percent_product */

					$coupon_data = array(
						'post_title' => $coupon_code,
						'post_content' => '',
						'post_status' => 'publish',
						'post_author' => 1,
						'post_type' => 'shop_coupon',
					);
					$coupon_id = $this->get_post_id_by_meta_key_and_value('_odoo_coupon_code_id', $coupon['id']);

					if ($coupon_id) {
						if ('no' == $this->odoo_settings['odoo_import_coupon_update']) {
							continue;
						}
						$coupon_data['ID'] = $coupon_id;
						$new_coupon_id = wp_update_post($coupon_data);
					} else {
						$new_coupon_id = wp_insert_post($coupon_data);
					}
					update_post_meta($new_coupon_id, 'discount_type', $discount_type);
					update_post_meta($new_coupon_id, 'coupon_amount', $amount);
					update_post_meta($new_coupon_id, '_odoo_coupon_code_id', $coupon['id']);
					update_post_meta($new_coupon_id, '_odoo_coupon_id', $odoo_coupon['id']);
					update_post_meta($new_coupon_id, '_odoo_coupon_name', $odoo_coupon['name']);
					if ('specific_products' == $odoo_coupon['discount_apply_on']) {
						update_post_meta($new_coupon_id, 'product_ids', $odoo_coupon['discount_specific_product_ids']);
					}
					update_post_meta($new_coupon_id, 'usage_limit', 1);
					update_post_meta($new_coupon_id, 'free_shipping', 'no');
				}
			}
		}

		public function do_export_coupon() {

			$odooApi = new WC_ODOO_API();
			$common = new WC_ODOO_Common_Functions();

			if (!$common->is_authenticate()) {
				return;
			}

			$args = array(
				'posts_per_page'   => -1,
				'orderby'          => 'title',
				'order'            => 'asc',
				'post_type'        => 'shop_coupon',
				'post_status'      => 'publish',

			);

			$coupons = get_posts($args);
			foreach ($coupons as $key => $coupon) {
				$coupon_id = get_post_meta($coupon->ID, '_odoo_coupon_id', true);
				$coupon_data = $this->create_coupon_data($coupon);
				if ($coupon_id) {
					if ('no' == $this->odoo_settings['odoo_export_coupon_update']) {
						continue;
					}
					$res = $odooApi->update_record('coupon.program', array( (int) $coupon_id ), $coupon_data);
				} else {
					$coupon_id = $odooApi->create_record('coupon.program', $coupon_data);
				}
				if (isset($coupon_id['fail'])) {
					$error_msg = 'Error for Creating/Updating Coupon Id  => ' . $coupon->ID . ' Msg : ' . print_r($coupon_id['msg'], true);
					$odooApi->addLog($error_msg);
					continue;
				} else {
					update_post_meta($coupon->ID, '_odoo_coupon_id', $coupon_id);
					$coupon_code_id = get_post_meta($coupon->ID, '_odoo_coupon_code_id', true);

					$code_data = $this->create_coupon_code_data($coupon, $coupon_id);
					if ($coupon_code_id) {
						$res_code = $odooApi->update_record('coupon.coupon', array( (int) $coupon_code_id ), $code_data);
					} else {
						$coupon_code_id = $odooApi->create_record('coupon.coupon', $code_data);
					}
					if (isset($coupon_code_id['fail'])) {
						$error_msg = 'Error for Creating/Updating Coupon Code Id  => ' . $coupon->ID . ' Msg : ' . print_r($coupon_code_id['msg'], true);
						$odooApi->addLog($error_msg);
						continue;
					} else {
						update_post_meta($coupon->ID, '_odoo_coupon_code_id', $coupon_code_id);
					}
				}
			}
		}

		public function create_coupon_data( $coupon ) {
			$data = array(
				'name' => $coupon->post_name,
				'active' => 1,
				'program_type' => 'coupon_program',
				'rule_min_quantity' => 1,
			);
			$meta_data = get_post_meta($coupon->ID);

			if (isset($meta_data['discount_type'][0])) {
				$discount_type = $meta_data['discount_type'][0];
				if ('fixed_cart' == $discount_type) {
					$data['discount_type'] = 'fixed_amount';
					$data['discount_apply_on'] = 'on_order';
					$data['discount_fixed_amount'] = $meta_data['coupon_amount'][0];
				} else if ('percent' == $discount_type) {
					$data['discount_type'] = 'percentage';
					$data['discount_percentage'] = $meta_data['coupon_amount'][0];
					$data['discount_apply_on'] = 'on_order';
				} else {
					$data['discount_type'] = 'percentage';
					$data['discount_percentage'] = $meta_data['coupon_amount'][0];
					$data['discount_apply_on'] = 'on_order';
				}
			}
			if (isset($meta_data['date_expires'][0])) {

				$data['validity_duration'] = abs(time() - $meta_data['date_expires'][0]) / 60 / 60 / 24;
			}
			if (isset($meta_data['minimum_amount'][0])) {
				$data['rule_minimum_amount'] = $meta_data['minimum_amount'][0];
			}
			if (isset($meta_data['_odoo_coupon_name'][0])) {
				$data['name'] = $meta_data['_odoo_coupon_name'][0];
			}
			return $data;
		}

		public function create_coupon_code_data( $coupon, $odoo_coupon_id ) {
			$data = array(
				'code' => $coupon->post_name,
				'program_id' => $odoo_coupon_id,
			);
			return $data;
		}

		public function do_import_customer() {
			$odooApi = new WC_ODOO_API();
			$customers = $odooApi->readAll('res.partner', array( 'id', 'name', 'display_name', 'website', 'mobile', 'email', 'is_company', 'phone', 'image_medium', 'street', 'street2', 'zip', 'city', 'state_id', 'country_id', 'child_ids', 'type' ), array( array( 'type', '=', 'contact' ) ));

			if (!isset($customers['fail']) && is_array($customers) && count($customers)) {
				foreach ($customers as $key => $customer) {
					if (isset($customer['email']) && !empty($customer['email'])) {
						$address_lists = array();
						if (count($customer['child_ids']) > 0) {
							$address_lists = $odooApi->fetch_record_by_ids('res.partner', $customer['child_ids'], array( 'id', 'name', 'display_name', 'website', 'mobile', 'email', 'is_company', 'phone', 'image_medium', 'street', 'street2', 'zip', 'city', 'state_id', 'country_id', 'child_ids', 'type' ));
							if (isset($address_lists['fail'])) {
								continue;
							}
						}
						$this->sync_customer_to_wc($customer, $address_lists);
					}
				}
			}
		}

		public function sync_customer_to_wc( $customer, $address_lists ) {

			$user = get_user_by('email', $customer['email']);
			if (null != $user && is_array($user->roles) && in_array('customer', $user->roles)) {
				$user_id = $user->ID;
			}
			$customer_name = $this->split_name($customer['name']);
			$userdata = array(
				'user_login'            => $customer['email'],
				'user_nicename'         => $customer_name['first_name'],
				'user_email'            => $customer['email'],
				'display_name'          => $customer['display_name'],
				'nickname'              => $customer_name['first_name'],
				'first_name'            => $customer_name['first_name'],
				'last_name'             => $customer_name['last_name'],
				'role'                  => 'customer',
				'locale'                => '',
				'website'               => $customer['website'],
			);

			if (isset($user_id)) {
				$userdata['ID'] = $user_id;
				wp_update_user($userdata);
			} else {
				$userdata['user_pass'] = 'gsf3213#$rtyu';
				$user_id = wp_insert_user($userdata);
			}


			update_user_meta($user_id, '_odoo_id', $customer['id']);

			$is_billing_updated = false;
			foreach ($address_lists as $key => $address) {
				if (in_array($address['type'], array( 'delivery', 'invoice' ))) {
					if ('invoice' == $address['type']) {
						$is_billing_updated = true;
					}
					$this->create_user_addres_to_wc($user_id, $address, $address['type']);
				}
			}
			if (!$is_billing_updated) {
				$this->create_user_addres_to_wc($user_id, $customer, 'invioce');
			}
			return $user_id;
		}

		public function create_user_addres_to_wc( $user_id, $address, $address_type = 'invioce' ) {

			$type = ( 'delivery' == $address_type ) ? 'shipping' : 'billing';
			$customer_name = $this->split_name($address['name']);

			update_user_meta($user_id, $type . '_first_name', $customer_name['first_name']);
			update_user_meta($user_id, $type . '_last_name', $customer_name['last_name']);
			update_user_meta($user_id, $type . '_address_1', $address['street']);
			update_user_meta($user_id, $type . '_address_2', $address['street2']);
			update_user_meta($user_id, $type . '_city', $address['city']);
			if (isset($address['state_id'][1])) {
				preg_match('#\((.*?)\)#', $address['state_id'][1], $country);
				update_user_meta($user_id, $type . '_country', $country[1]);
				$state = explode(' (', $address['state_id'][1]);
				if ('' != $country[1] && null != $country[1] && !empty($country[1])) {
					$states_array =  array_flip(WC()->countries->get_states($country[1]));
					$state_name = isset($states_array[$state[0]]) ? $states_array[$state[0]] : '' ;
				}
				update_user_meta($user_id, $type . '_state', $state_name);
			}

			update_user_meta($user_id, $type . '_postcode', $address['zip']);
			// update_user_meta( $user_id, $type . '_country', $address['country_code']);
			update_user_meta($user_id, $type . '_email', $address['email']);
			update_user_meta($user_id, $type . '_phone', $address['phone']);
			update_user_meta($user_id, '_odoo_' . $type . '_id', $address['id']);
		}

		public function split_name( $name ) {
			$name       = trim($name);
			$last_name  = ( strpos($name, ' ') === false ) ? '' : preg_replace('#.*\s([\w-]*)$#', '$1', $name);
			$first_name = trim(preg_replace('#' . preg_quote($last_name, '#') . '#', '', $name));
			return array( 'first_name' => $first_name, 'last_name' => $last_name );
		}

		public function do_import_order() {

			$odooApi = new WC_ODOO_API();
			$conditions = array();

			if (isset($this->odoo_settings['odoo_import_order_from_date']) && !empty($this->odoo_settings['odoo_import_order_from_date'])) {
				$conditions[] = array( 'date_order', '>=', $this->odoo_settings['odoo_import_order_from_date'] );
			}
			if (isset($this->odoo_settings['odoo_import_order_to_date']) && !empty($this->odoo_settings['odoo_import_order_to_date'])) {
				$conditions[] = array( 'date_order', '<=', $this->odoo_settings['odoo_import_order_to_date'] );
			}

			$orders = $odooApi->readAll('sale.order', array( 'id', 'name', 'origin', 'state', 'date_order', 'partner_id', 'partner_invoice_id', 'partner_shipping_id', 'order_line', 'invoice_ids', 'amount_total', 'amount_tax', 'type_name', 'display_name' ), $conditions);

			if (!isset($orders['fail']) && is_array($orders) &&  count($orders) > 0) {

				foreach ($orders as $key => $order) {
					$order_id = $this->get_post_id_by_meta_key_and_value('_odoo_order_id', $order['id']);
					if ($order_id) {
						$odooApi->addLog('Order already Synced for Odoo Order Id : ' . $order['id']);
						continue;
					}

					$user_id = $this->get_user_id_by_meta_key_and_value('_odoo_id', $order['partner_id'][0]);
					$partner_ids = array( $order['partner_invoice_id'][0], $order['partner_shipping_id'][0] );
					if (!$user_id) {
						$partner_ids[] = $order['partner_id'][0];
					}
					$partners = $odooApi->fetch_record_by_ids('res.partner', $partner_ids, array( 'id', 'name', 'display_name', 'website', 'mobile', 'email', 'is_company', 'phone', 'image_medium', 'street', 'street2', 'zip', 'city', 'state_id', 'country_id', 'type' ));

					if (isset($partners['fail'])) {
						$odooApi->addLog('User not found for Order Id : ' . $order['id']);
						continue;
					}
					$users = array();

					foreach ($partners as $key => $partner) {
						$billing = $this->create_customer_address_data($partner);
						$shipping = $this->create_customer_address_data($partner);
						if ('invoice' == $partner['type']) {
							$users['billing'] = $this->create_customer_address_data($partner);
						} else if ('delivery' == $partner['type']) {
							$users['shipping'] = $this->create_customer_address_data($partner);
						} else {
							$users['user_id'] = ( false != $user_id ) ? $user_id : $this->create_wc_customer($partner);
						}
					}
					extract($users);
					$order_lines = $odooApi->fetch_record_by_ids('sale.order.line', $order['order_line'], array( 'id', 'name', 'invoice_status', 'price_subtotal', 'price_tax', 'price_total', 'product_id', 'product_uom_qty', 'price_unit' ));

					if (isset($order_lines['fail'])) {
						$error_msg = 'Order Line found for Order Id  => ' . $order['id'] . ' Msg : ' . print_r($order_lines['msg'], true);
						$odooApi->addLog($error_msg);
						continue;
					}
					$wc_order = wc_create_order(array( 'customer_id' => $user_id ));
					$wc_order->update_meta_data( '_new_order_email_sent', 'true' );
					$wc_order->update_meta_data( '_customer_user', $user_id);
					$wc_order->update_meta_data( '_odoo_order_id', $order['id']);
					$wc_order->update_meta_data( '_odoo_invoice_id', end($order['invoice_ids']));
					$wc_order->update_meta_data( '_odoo_order_origin', $order['name']);
					foreach ($order_lines as $key => $order_line) {
						$product_id = $this->get_post_id_by_meta_key_and_value('_odoo_id', $order_line['product_id'][0]);
						if (!$product_id) {
							$odoo_product = $odooApi->fetch_record_by_id('product.product', array( $order_line['product_id'][0] ));
							$product_id = $this->sync_product_from_odoo($odoo_product);
						}
						$product = wc_get_product($product_id);
						$product->set_price($order_line['price_unit']);
						$item_id = $wc_order->add_product($product, $order_line['product_uom_qty']);
						wc_update_order_item_meta($item_id, '_order_line_id', $order_line['id']);
					}
					if (isset($billing)) {
						$wc_order->set_address($billing, 'billing');
					}
					if (isset($shipping)) {
						$wc_order->set_address($shipping, 'shipping');
					}
					$wc_order->calculate_totals();
					$wc_order->set_date_completed($order['date_order']);
					$wc_order->set_status('completed', __('Order Imported From Odoo', 'wc-odoo-integration'));
					$wc_order->save();

//                  update_post_meta($wc_order->get_ID(), '_customer_user', $user_id);
//                  update_post_meta($wc_order->get_ID(), '_odoo_invoice_id', end($order['invoice_ids']));
//                  update_post_meta($wc_order->get_ID(), '_odoo_order_id', $order['id']);
//                  update_post_meta($wc_order->get_ID(), '_odoo_order_origin', $order['name']);
				}
			}
		}

		public function create_customer_address_data( $partner ) {
			$data = array(
				'first_name' => $this->split_name($partner['name'])['first_name'],
				'last_name'  => $this->split_name($partner['name'])['last_name'],
				'email'      => $partner['email'],
				'phone'      => $partner['phone'],
				'address_1'  => $partner['street'],
				'address_2'  => $partner['street2'],
				'city'       => $partner['city'],
				'state'      => isset($partner['state_id'][1]) ? $partner['state_id'][1] : '',
				'postcode'   => $partner['zip'],
				'country'    => isset($partner['country_id'][1]) ? $partner['country_id'][1] : '',
			);

			if (1 == $partner['is_company']) {
				$data['company'] = $partner['display_name'];
			}
			return $data;
		}

		public function create_wc_customer( $customer ) {
			$user = get_user_by('email', $customer['email']);

			if (null != $user && is_array($user->roles) && in_array('customer', $user->roles)) {
				$user_id = $user->ID;
			}
			$customer_name = $this->split_name($customer['name']);
			$userdata = array(
				'user_nicename'         => $customer_name['first_name'],
				'user_email'            => $customer['email'],
				'display_name'          => $customer['display_name'],
				'nickname'              => $customer_name['first_name'],
				'first_name'            => $customer_name['first_name'],
				'last_name'             => $customer_name['last_name'],
				'role'                  => 'customer',
				'locale'                => '',
				'website'               => $customer['website'],
			);

			if (isset($user_id)) {
				$userdata['ID'] = $user_id;
				wp_update_user($userdata);
			} else {
				$userdata['user_pass'] = 'gsf3213#$rtyu';
				$userdata['user_login'] = $customer['email'];
				$user_id = wp_insert_user($userdata);
			}

			update_user_meta($user_id, '_odoo_id', $customer['id']);
			if (!is_wp_error($user_id)) {
				return $user_id;
			}
			return false;
		}


		public function order_create( $order_id ) {

			$odoo_settings = get_option('woocommerce_woocommmerce_odoo_integration_settings');
			$odooApi = new WC_ODOO_API();
			$order = new WC_Order($order_id);
			$common = new WC_ODOO_Common_Functions();
			$helper = WC_ODOO_Helpers::getHelper();

			if (!$common->is_authenticate()) {
				return;
			}

			$woo_state = $helper->getState($order->get_status());
			$statuses = $helper->odooStates($woo_state);

			$odooApi->addLog('create order : ' . print_r($statuses, true));

			if ('shop_order' != $order->get_type()) {
				return false;
			}
			$is_order_syced = get_post_meta($order_id, '_odoo_order_id', true);
			if ($is_order_syced) {
				$error_msg = 'Order Already Synced For Id ' . $order_id . ' With Odoo Sale Order Id => ' . $is_order_syced;
				$odooApi->addLog($error_msg);
				return false;
			}
			//get user id assocaited with order
			$user = $order->get_user();
			$customer_data = $this->getCustomerData($user, $order);

			if (!isset($odoo_settings['odooTax'])) {

				$error_msg = 'Invalid Tax Setting For Order Id ' . $order_id;
				$odooApi->addLog($error_msg);
				return false;
			}
			/* get tax id from the admin setting */
			$tax_id = (int) $odoo_settings['odooTax'];

			$tax_data = $odooApi->fetch_file_record_by_id('taxes', 'account.tax', $tax_id);

			if (isset($tax_data['fail'])) {
				$error_msg = 'Error For Fetching Tax data Msg : ' . print_r($tax_data['msg'], true);
				$odooApi->addLog($error_msg);
				return false;
			}
			if (empty($customer_data['invoice_id'])) {
				$customer_data['invoice_id'] = $customer_data['id'];
			}

			$order_data = array(
				'partner_id'           => (int) $customer_data['id'],
				'company_id'            => (int) $this->odoo_company_id,
				'partner_invoice_id'   => (int) $customer_data['invoice_id'],
				'state'                => $statuses['order_state'],
				'note'                 => __('Woo Order Id : ', 'wc-odoo-integration') . $order_id,
				'payment_term_id'      => 1,
				'date_order'           => date_format($order->get_date_created(), 'Y-m-d H:i:s'),
			);

			if ('yes' == $this->odoo_settings->odoo_fiscal_position && !empty($this->odoo_settings->odoo_fiscal_position_selected)) {
				$order_data['fiscal_position_id'] = $this->odoo_settings->odoo_fiscal_position_selected;

			}

			if (isset($odoo_settings['odooVersion']) && ( 14 == $odoo_settings['odooVersion'] || 15 == $odoo_settings['odooVersion'] )) {
				if (isset($odoo_settings['gst_treatment'])) {
					$order_data['l10n_in_gst_treatment'] = $odoo_settings['gst_treatment'];
				}
			}
			// $odooApi->addLog('Order Data : ' . print_r($order_data, true));
			/* Create Sale Order in the Odoo */
			$order_odoo_id = $odooApi->create_record('sale.order', $order_data);
			if (isset($order_odoo_id['fail'])) {
				$error_msg = 'Error for Creating  Order Id  => ' . $order_id . ' Msg : ' . print_r($order_odoo_id['msg'], true);
				$odooApi->addLog($error_msg);
				return false;
			}
			opmc_hpos_update_post_meta($order_id, '_odoo_order_id', $order_odoo_id);
			$invoice_lines = array();

			foreach ($order->get_items() as $item_id => $item) {

				$product = $item->get_product();

				if (!$product ||  null == $product) {
					$error_msg = 'Invalid Product For Order ' . $order_id;
					$odooApi->addLog($error_msg);
					return false;
				}
				if (!$product || $product->get_sku() == '') {
					$error_msg = '[Product Export] [Error] [Products ID : ' . $product_data->get_id() . ' have missing/invalid SKU. This product will not be exported. ]';
					$odooApi->addLog($error_msg);
					return false;
				}

				$product_id = $odooApi->search_record( 'product.product', array( array( $this->odoo_sku_mapping, '=', $product->get_sku() ), array( 'company_id', '=', (int) $this->odoo_company_id ) ) );

				$odooApi->addLog('ODOO product id : ' . print_r($product_id, true));

				if (isset($product_id['fail'])) {
					$error_msg = 'Error for Search product => ' . $product->get_id() . ' Msg : ' . print_r($product_id['msg'], true);
					$odooApi->addLog($error_msg);
					return false;
				}
				update_post_meta($product->get_id(), '_odoo_id', $product_id);

				if (!isset($product_id) || $product_id <= ''  || false == $product_id) {
					$product_id = $this->create_product($product);

					$product_tmpl_id = $odooApi->fetch_record_by_ids('product.product', array( $product_id ), array( 'product_tmpl_id' ));
					$odooApi->addLog('New ODOO product_tmpl_id : ' . print_r($product_tmpl_id[0]['product_tmpl_id'][0], true));

					if (isset($product_id['fail'])) {
						$error_msg = 'Error for Creating  Product Id  => ' . $product->get_id() . ' Msg : ' . print_r($product_id['msg'], true);
						$odooApi->addLog($error_msg);
						return false;
					}
					update_post_meta($product->get_id(), '_odoo_id', $product_id);
					update_post_meta($product->get_id(), '_odooproduct_tmpl_id', $product_tmpl_id);
					if ('yes' == $this->odoo_settings['odoo_export_update_price']) {
						if ($product->is_on_sale()) {
							$odoo_extra_product = get_post_meta($product->get_id(), '_product_extra_price_id', true);
							if ($odoo_extra_product) {
								$this->update_extra_price($odoo_extra_product, $product);
							} else {
								$this->create_extra_price($product_id, $product, $product_tmpl_id[0]['product_tmpl_id'][0]);
							}
						}
					}
					$product_qty = number_format((float) $product->get_stock_quantity(), 2, '.', '');

					$this->update_product_quantity($product_id, $product_qty, $product_tmpl_id[0]['product_tmpl_id'][0]);
					update_post_meta($product->get_id(), '_odoo_id', $product_id);
					update_post_meta($product->get_id(), '_odoo_image_id', $product->get_image_id());
				}
				if (1 == $tax_data['price_include']) {
					$total_price = $item->get_total() + $item->get_total_tax();
				} else {
					$total_price = $item->get_total();
				}
				$unit_price = number_format((float) ( $total_price / $item->get_quantity() ), 2, '.', '');

				$order_line = array(
					'order_partner_id' => (int) $customer_data['id'],
					'order_id' => $order_odoo_id,
					'product_uom_qty' => $item->get_quantity(),
					'product_id' =>  $product_id,
					'price_unit' => $unit_price,
				);

				if ('no' == $this->odoo_settings->odoo_fiscal_position) {
					if ($item->get_total_tax() > 0) {
						$order_line['tax_id'] = array( array( 6, 0, array( (int) $tax_id ) ) );
					} else {
						$order_line['tax_id'] = array( array( 6, 0, array() ) );
					}
				}

				$order_line_id = $odooApi->create_record('sale.order.line', $order_line);

				if (isset($order_line_id['fail'])) {
					$error_msg = 'Error for Creating  Order line for Product Id  => ' . $product->get_id() . ' Msg : ' . print_r($order_line_id['msg'], true);
					$odooApi->addLog($error_msg);
					return false;
				}

				wc_update_order_item_meta($item_id, '_order_line_id', $order_line_id);
			}
			if ($order->get_shipping_total() > 0) {
				$shipping_tax_id = (int) $odoo_settings['shippingOdooTax'];

				$shipping_tax_data = $odooApi->fetch_file_record_by_id('taxes', 'account.tax', $shipping_tax_id);
				$order_line = array(
					'order_partner_id' => (int) $customer_data['id'],
					'order_id' => $order_odoo_id,
					'product_uom_qty' => 1,
					'product_id' =>  (int) $this->get_delivery_product_id(),
					'price_unit' => $order->get_shipping_total(),
				);
				if ('no' != $this->odoo_settings->odoo_fiscal_position) {
					$order_line['tax_id'] = array( array( 6, 0, array( $shipping_tax_id ) ) );
				}

				$order_line_id = $odooApi->create_record('sale.order.line', $order_line);

				if (isset($order_line_id['fail'])) {
					$error_msg = 'Error for Creating  Order line for Product Id  => ' . $product->get_id() . ' Msg : ' . print_r($order_line_id['msg'], true);
					$odooApi->addLog($error_msg);
					return false;
				}

				opmc_hpos_update_post_meta($order_id, '_order_line_id', $order_line_id);
			}
			//calculate taxes if fiscal positions are enabled
			if ('yes' == $odoo_settings['odoo_fiscal_position']) {
				$order_tax_calculations = $odooApi->custom_api_call('sale.order', 'validate_taxes_on_sales_order', array( (int) $order_odoo_id ));
			}

			if (!empty($order->get_customer_note())) {
				$order_line = array(
					'order_partner_id' => (int) $customer_data['id'],
					'order_id' => $order_odoo_id,
					'product_uom_qty' => false,
					'product_id' =>  false,
					'display_type' => 'line_note',
					'name' => $order->get_customer_note(),
				);
				$order_line_id = $odooApi->create_record('sale.order.line', $order_line);

				if (isset($order_line_id['fail'])) {
					$error_msg = 'Error for Creating  Order Note For Woo Order  => ' . $order_id . ' Msg : ' . print_r($order_line_id['msg'], true);
					$odooApi->addLog($error_msg);
					return false;
				}

				update_post_meta($item_id, '_order_note_id', $order_line_id);

				// wc_update_order_item_meta($item_id, '_order_line_id', $order_line_id);
				// wc_update_order_item_meta($item_id, '_order_note_id', $order_line_id);
			}

			if ('' != $statuses['invoice_state']) {
				if ('yes' == $this->odoo_settings['odoo_export_invoice']) {
					$invoice_id = $this->create_invoice($order_id);
				}
				if (isset($invoice_id['fail'])) {
					$error_msg = 'Error for Creating  Order Note For Woo Order  => ' . $order_id . ' Msg : ' . print_r($invoice_id['msg'], true);
					$odooApi->addLog($error_msg);
					return false;
				}
			}
			opmc_hpos_update_post_meta($order_id, '_odoo_order_id', $order_odoo_id);
		}


		/**
		 * [create_odoo_invoice]
		 *
		 * @param  int $order_id  refunded order id
		 * @param  int $refund_id refund id
		 */
		public function create_invoice( $order_id ) {
			$odooApi = new WC_ODOO_API();
			$order = new WC_Order($order_id);
			$common = new WC_ODOO_Common_Functions();
			$helper = WC_ODOO_Helpers::getHelper();

			if (!$common->is_authenticate()) {
				return;
			}

			$order_odoo_id = opmc_hpos_get_post_meta($order_id, '_odoo_order_id', true);

			$odooApi->addLog('order_odoo_id : ' . print_r($order_odoo_id, true));

			$woo_state = $helper->getState($order->get_status());
			$statuses = $helper->odooStates($woo_state);
			$odoo_ver = $helper->odoo_version();

			//get user id assocaited with order
			$user = $order->get_user();
			$customer_data = $this->getCustomerData($user, $order);

			$invoice_data = $this->create_invoice_data($customer_data, (int) $order_odoo_id);
			$invoice_id = $odooApi->create_record('account.move', $invoice_data);

			if (isset($invoice_id['fail'])) {
				$error_msg = 'Error for Creating  Invoice Id  => ' . $order_id . ' Msg : ' . print_r($invoice_id['msg'], true);
				$odooApi->addLog($error_msg);
				return false;
			}

			if (!isset($this->odoo_settings['odooTax'])) {

				$error_msg = 'Invalid Tax Setting For Order Id ' . $order_id;
				$odooApi->addLog($error_msg);
				return false;
			}
			/* get tax id from the admin setting */
			$tax_id = (int) $this->odoo_settings['odooTax'];

			$tax_data = $odooApi->fetch_file_record_by_id('taxes', 'account.tax', $tax_id);

			if (isset($tax_data['fail'])) {
				$error_msg = 'Error For Fetching Tax data Msg : ' . print_r($tax_data['msg'], true);
				$odooApi->addLog($error_msg);
				return false;
			}
			$invoice_lines = array();

			foreach ($order->get_items() as $item_id => $item) {

				$product = $item->get_product();

				$order_line_id =  wc_get_order_item_meta($item_id, '_order_line_id');
				$odooApi->addLog('order_line_id : ' . print_r($order_line_id, true));

				$product_id = $odooApi->search_record( 'product.product', array( array( $this->odoo_sku_mapping, '=', $product->get_sku() ), array( 'company_id', '=', (int) $this->odoo_company_id ) ) );

				$odooApi->addLog('products id  : ' . print_r($product_id, true));

				if (1 == $tax_data['price_include']) {
					$total_price = $item->get_total() + $item->get_total_tax();
				} else {
					$total_price = $item->get_total();
				}
				$unit_price = round(number_format((float) ( $total_price / $item->get_quantity() ), 2, '.', ''));

				if ('yes' == $this->odoo_settings['odoo_export_invoice']) {
					$invoice_line_data = array(
						'price_unit' =>  $unit_price,
						'quantity' =>  $item->get_quantity(),
						'product_id' =>  $product_id,
						'sale_line_ids' =>  array( array( 6, 0, array( (int) $order_line_id ) ) ),
					);
					if ('no' == $this->odoo_settings->odoo_fiscal_position) {
						$invoice_line_data['tax_ids'] =  array( array( 6, 0, array( (int) $tax_id ) ) );

						if ($item->get_total_tax() > 0) {
							$invoice_line_data['tax_ids'] = array( array( 6, 0, array( (int) $tax_id ) ) );
						} else {
							$invoice_line_data['tax_ids'] = array( array( 6, 0, array() ) );
						}
					}
					$invoice_lines[] = $odooApi->create_record('account.move.line', $invoice_line_data);
//                  $invoice_lines[] = $invoice_line_data;
				}
			}

			if ($order->get_shipping_total() > 0) {
				$shipping_tax_id = (int) $this->odoo_settings['shippingOdooTax'];

				$shipping_tax_data = $odooApi->fetch_file_record_by_id('taxes', 'account.tax', $shipping_tax_id);

				$order_line_id = opmc_hpos_get_post_meta($order_id, '_order_line_id', true);

				$odooApi->addLog('Shipping line : ' . print_r($order_line_id, true));

				if ('yes' == $this->odoo_settings['odoo_export_invoice']) {
					$invoice_lines[] = array(
						'price_unit' =>  $order->get_shipping_total(),
						'quantity' =>  1,
						'product_id' =>  (int) $this->get_delivery_product_id(),
						'sale_line_ids' =>  array( array( 6, 0, array( (int) $order_line_id ) ) ),
					);
					if ('no' == $this->odoo_settings->odoo_fiscal_position) {
						$invoice_lines['tax_ids'] =  array( array( 6, 0, array( (int) $shipping_tax_id ) ) );
					}
					$invoice_lines[] = $odooApi->create_record('account.move.line', $invoice_line_data);
				}
			}

			if (!empty($order->get_customer_note())) {
				if ('yes' == $this->odoo_settings['odoo_export_invoice']) {
					$order_line_id = opmc_hpos_get_post_meta($order_id, '_order_note_id', true);
					$odooApi->addLog('order note line : ' . print_r($order_line_id, true));
					$invoice_lines[] = array(
						'price_unit' =>  false,
						'quantity' =>  false,
						'product_id' =>  false,
						'sale_line_ids' =>  array( array( 6, 0, array( (int) $order_line_id ) ) ),
						'display_type' => 'line_note',
						'name' => $order->get_customer_note(),
					);
					$invoice_lines[] = $odooApi->create_record('account.move.line', $invoice_line_data);
				}
			}

			if (count($invoice_lines) > 0 && ( 'yes' == $this->odoo_settings['odoo_export_invoice'] )) {
//              $invoice_data = $this->create_invoice_data($customer_data, (int) $order_odoo_id);
//              $invoice_data['invoice_line_ids'] = $invoice_lines;
				// $odooApi->addLog('invoice data : '. print_r($invoice_data, true) );
//              $invoice_id = $odooApi->create_record('account.move', $invoice_data);
//              $odooApi->addLog('Invoice ID : ' . print_r($invoice_id, true));

//              if (isset($invoice_id['fail'])) {
//                  $error_msg = 'Error for Creating  Invoice Id  => ' . $order_id . ' Msg : ' . print_r($invoice_id['msg'], true);
//                  $odooApi->addLog($error_msg);
//                  return false;
//              }
				$odoo_order = $this->update_record('sale.order', (int) $order_odoo_id, array( 'state' => $statuses['order_state'] ));
				$odooApi->addLog('order update: ' . print_r($odoo_order, true));

				if ($helper->is_inv_mark_paid()) {
					$invoice = $odooApi->update_record('account.move', (int) $invoice_id, array( 'state' => $statuses['invoice_state'] ));
					if (13 === $odoo_ver) {
						$invoice = $odooApi->update_record('account.move', (int) $invoice_id, array( 'invoice_payment_state' => $statuses['payment_state'] ));
					} else {
						$invoice = $odooApi->update_record('account.move', (int) $invoice_id, array( 'payment_state' => $statuses['payment_state'] ));
					}
				} else {
					$invoice = $odooApi->update_record('account.move', (int) $invoice_id, array( 'state' => 'draft' ));
					if (13 === $odoo_ver) {
						$invoice = $odooApi->update_record('account.move', (int) $invoice_id, array( 'invoice_payment_state' => 'not_paid' ));
					} else {
						$invoice = $odooApi->update_record('account.move', (int) $invoice_id, array( 'payment_state' => 'not_paid' ));
					}
				}

				if (isset($invoice['fail'])) {
					$error_msg = 'Error for Creating  Invoice  for Order Id  => ' . $order_id . ' Msg : ' . print_r($invoice['msg'], true);
					$odooApi->addLog($error_msg);
					return false;
				}

				$invoice_url = $this->create_pdf_download_link($invoice_id);
				if (isset($invoice_data['invoice_origin']) && !empty($invoice_data['invoice_origin'])) {
					$order_origin = $invoice_data['invoice_origin'];
					opmc_hpos_update_post_meta($order_id, '_odoo_order_origin', $order_origin);
				}
				opmc_hpos_update_post_meta($order_id, '_odoo_invoice_id', $invoice_id);
				opmc_hpos_update_post_meta($order_id, '_odoo_invoice_url', $invoice_url);
			}
		}


		/**
		 * [create_odoo_refund description]
		 *
		 * @param  int $order_id  refunded order id
		 * @param  int $refund_id refund id
		 */
		public function create_odoo_refund( $order_id, $refund_id ) {
			$odooApi = new WC_ODOO_API();

			$refund = new WC_Order_Refund($refund_id);
			$odoo_invoice_id = opmc_hpos_get_post_meta($refund->get_parent_id(), '_odoo_invoice_id', true);
			$odoo_order_id = opmc_hpos_get_post_meta($refund->get_parent_id(), '_odoo_order_id', true);

			// $odoo_refund_invoice_id = $this->create_refund_invoice($odoo_invoice_id);
			$odoo_refund_invoice_data = $this->create_refund_invoice_data($odoo_invoice_id);

			$refund_order = new WC_Order($refund->get_parent_id());
			$user = $refund_order->get_user();
			$customer_data = $this->getCustomerData($user, $refund_order);

			$refund_item_id = true;
			if (!$refund->get_items()) {
				$refund = $refund_order;
				$refund_item_id = false;
			}

			// $wc_setting = new WC_ODOO_Integration_Settings();
			$wc_setting = get_option('woocommerce_woocommmerce_odoo_integration_settings');


			$tax_id = (int) $wc_setting['odooTax'];
			$tax_data = $odooApi->fetch_file_record_by_id('taxes', 'account.tax', $tax_id);

			$refund_invoice_lines = array();
			foreach ($refund->get_items() as $item_id => $item) {

				$refunded_quantity      = $item->get_quantity();
				$refunded_line_subtotal = abs($item->get_subtotal());
				$refunded_item_id       = ( $refund_item_id ) ? $item->get_meta('_refunded_item_id') : $item_id;
				$order_line_id          = wc_get_order_item_meta($refunded_item_id, '_order_line_id', true);
				$odd_order_line_id      = wc_get_order_item_meta($refunded_item_id, '_invoice_line_id', true);
				$odoo_product_id        = get_post_meta($item->get_product_id(), '_odoo_id', true);

				// $invoice_line_item = $this->create_return_invoice_line_base_on_tax($odoo_refund_invoice_id, $item, $odoo_product_id, $customer_data, $tax_data);

				if (1 == $tax_data['price_include']) {
					$total_price = abs($item->get_total()) + abs($item->get_total_tax());
				} else {
					$total_price = abs($item->get_total());
				}
				$unit_price = round(number_format((float) ( $total_price / abs($item->get_quantity()) ), 2, '.', ''));

				$refund_invoice_line_data = array(
					'price_unit' =>  $unit_price,
					'quantity' =>  absint($item->get_quantity()),
					'product_id' =>  (int) $odoo_product_id,
					'sale_line_ids' =>  array( array( 6, 0, array( (int) $order_line_id ) ) ),
				);

				if ('no' == $this->odoo_settings->odoo_fiscal_position) {
					if (abs($item->get_total_tax()) > 0) {
						$refund_invoice_line_data['tax_ids'] = array( array( 6, 0, array( (int) $tax_id ) ) );
					} else {
						$refund_invoice_line_data['tax_ids'] = array( array( 6, 0, array() ) );
					}
				}
				$refund_invoice_lines[] = $refund_invoice_line_data;
				wc_update_order_item_meta($item_id, '_return_order_line_id', $order_line_id);
			}
			$odoo_refund_invoice_data['invoice_line_ids'] = $refund_invoice_lines;
			$odoo_refund_invoice_id = $odooApi->create_record('account.move', $odoo_refund_invoice_data);

			if (isset($odoo_refund_invoice_id['fail'])) {
				$error_msg = 'Error for Creating  Invoice Id  => ' . $order_id . ' Msg : ' . print_r($odoo_refund_invoice_id['msg'], true);
				$odooApi->addLog($error_msg);
				return false;
			}
			if (isset($this->odoo_settings['odoo_mark_invoice_paid']) && 'yes' == $this->odoo_settings['odoo_mark_invoice_paid']) {
				$odoo_refund_invoice = $this->update_record('account.move', $odoo_refund_invoice_id, array( 'state' => 'posted' ));
			} else {
				$odoo_refund_invoice = $this->update_record('account.move', $odoo_refund_invoice_id, array( 'state' => 'draft' ));
			}
			if (isset($odoo_refund_invoice['fail'])) {
				$error_msg = 'Error Update Refund Invoice For Invoice Id  => ' . $order_id . ' Msg : ' . print_r($odoo_refund_invoice['msg'], true);
				$odooApi->addLog($error_msg);
				return false;
			}

			if (isset($this->odoo_settings['odoo_mark_invoice_paid']) && 'yes' == $this->odoo_settings['odoo_mark_invoice_paid']) {
				if (isset($odoo_settings['odooVersion']) && 13 == $odoo_settings['odooVersion']) {

					$odoo_refund_invoice = $this->update_record('account.move', $odoo_refund_invoice_id, array( 'invoice_payment_state' => 'paid' ));
				} else {

					$odoo_refund_invoice = $this->update_record('account.move', $odoo_refund_invoice_id, array( 'payment_state' => 'in_payment' ));
				}
			} elseif (isset($odoo_settings['odooVersion']) && 13 == $odoo_settings['odooVersion']) {

					$odoo_refund_invoice = $this->update_record('account.move', $odoo_refund_invoice_id, array( 'invoice_payment_state' => 'not_paid' ));
			} else {

				$odoo_refund_invoice = $this->update_record('account.move', $odoo_refund_invoice_id, array( 'payment_state' => 'not_paid' ));
			}

			if (isset($odoo_refund_invoice['fail'])) {
				$error_msg = 'Error for Creating  Invoice  for Order Id  => ' . $order_id . ' Msg : ' . print_r($odoo_refund_invoice['msg'], true);
				$odooApi->addLog($error_msg);
				return false;
			}
			$invoice_url = $this->create_pdf_download_link($odoo_refund_invoice_id);
			opmc_hpos_update_post_meta($order_id, '_odoo_return_invoice_id', $odoo_refund_invoice_id);
			opmc_hpos_update_post_meta($order_id, '_odoo_return_invoice_url', $invoice_url);
			opmc_hpos_update_post_meta($order_id, '_odoo_return_order_id', $odoo_order_id);
		}

		public function create_extra_price( $odoo_product_id, $product, $template = 0 ) {
			$template_id = ( 0 == $template ) ? $odoo_product_id : $template;
			$priceListConditions = array(
				array( 'currency_id', '=', get_option('woocommerce_currency') ),
			);
			$odooApi = new WC_ODOO_API();
			$priceList = $odooApi->readAll('product.pricelist', array(), $priceListConditions);
//            $odooApi->addLog('product.price-list : '. print_r($priceList, true));
			$data = array(
				'fixed_price' => $product->get_sale_price(),
				// 'min_quantity' => 0,git
				'pricelist_id' => 1,
				'product_tmpl_id' => $template_id,
				'product_id' => $odoo_product_id,
				'applied_on' => '1_product',
			);
			$priceList = $odooApi->readAll('product.pricelist');
//            $odooApi->addLog('product.price-list : '. print_r($priceList, true));
			$extra_price_id = $odooApi->create_record('product.pricelist.item', $data);
			if (isset($extra_price_id['fail'])) {
				$error_msg = 'Error for Creating  Extra Price For Product Id  => ' . $product->get_id() . ' Msg : ' . print_r($extra_price_id['msg'], true);
				$odooApi->addLog($error_msg);
				return false;
			}
			update_post_meta($product->get_id(), '_product_extra_price_id', $extra_price_id);
		}


		public function update_extra_price( $extra_price_id, $product ) {
			$data = array(
				'fixed_price' => $product->get_sale_price(),
			);
			$odooApi = new WC_ODOO_API();
			$priceList = $odooApi->readAll('product.pricelist');
//            $odooApi->addLog('product.price-list : '. print_r($priceList, true));
			$extra_price_update = $odooApi->update_record('product.pricelist.item', (int) $extra_price_id, $data);
			if (isset($extra_price_update['fail'])) {
				$error_msg = 'Error for Updating  Extra Price For Product Id  => ' . $product->get_id() . ' Msg : ' . print_r($extra_price_update['msg'], true);
				$odooApi->addLog($error_msg);
				return false;
			}
			update_post_meta($product->get_id(), '_product_extra_price_id', $extra_price_id);
		}

		public function get_and_set_sale_price( $post_id, $odoo_product ) {
			$odooApi = new WC_ODOO_API();
			$price_lists = $odooApi->readAll('product.pricelist.item', array(), array( array( 'product_tmpl_id', '=', (int) $odoo_product['id'] ) ));
			if (isset($price_lists['fail'])) {
				$error_msg = 'Unable to get Extra Price For Product Id  => ' . $post_id . ' Msg : ' . print_r($price_lists['msg'], true);
				$odooApi->addLog($error_msg);
				return false;
			}
			if (isset($price_lists[0]['fixed_price'])) {

				if ($odoo_product['list_price'] > $price_lists[0]['fixed_price']) {
					update_post_meta($post_id, '_sale_price', $price_lists[0]['fixed_price']);
					update_post_meta($post_id, '_price', $price_lists[0]['fixed_price']);
					update_post_meta($post_id, '_product_extra_price_id', $price_lists[0]['id']);
				} else {
					$error_msg = 'Extra Price Is Greater than Regular Price For Product Id  => ' . $post_id;
					$odooApi->addLog($error_msg);
					return false;
				}
			}
		}

		public function search_odoo_customer( $conditions, $customer_id ) {
			$odooApi = new WC_ODOO_API();
			$customer = $odooApi->search_record('res.partner', $conditions);
			if (isset($customer['fail'])) {
				$error_msg = 'Error In Customer Search Customer Id  => ' . $customer_id . ' Msg : ' . print_r($customer['msg'], true);
				$odooApi->addLog($error_msg);
				return false;
			}
			return $customer;
		}

		public function search_odoo_product( $conditions, $product_id ) {
			$odooApi = new WC_ODOO_API();
			$product = $odooApi->search_record('product.product', $conditions);
			if (isset($product['fail'])) {
				$error_msg = 'Error In product Search product Id  => ' . $product_id . ' Msg : ' . print_r($product['msg'], true);
				$odooApi->addLog($error_msg);
				return false;
			}
			return $product;
		}

		public function can_create_address( $user_id, $address, $type ) {
			if (empty($address['address_1']) || empty($address['postcode']) || ( empty($address['first_name']) && empty($address['last_name']) )) {
				$odooApi = new WC_ODOO_API();
				$error_msg = 'Unable to create customer ' . $type . ' address for customer Id  => ' . $user_id . ' Msg : Required Fields are missing';
				$odooApi->addLog($error_msg);
				return false;
			}
			return true;
		}


		public function opmc_odoo_order_status( $order_id, $from_status, $to_status ) {
			$order = new WC_Order($order_id);
			if ('refunded' === $from_status) {
				$order->update_status('refunded', __('Order status can\'t be changed from refunded.', 'wc-odoo-integration'));
				return false;
			}
			$odooApi = new WC_ODOO_API();
			$helper = WC_ODOO_Helpers::getHelper();
			$woo_state = $helper->getState($to_status);
			$statuses = $helper->odooStates($woo_state);
			$odooApi->addLog('Statuses : ' . print_r($statuses, true));
			$export_inv_enable = $helper->is_export_inv();
			$inv_mark_paid = $helper->is_inv_mark_paid();
			$odoo_ver = $helper->odoo_version();
			$opmc_odoo_order_id = $order->get_meta('_odoo_order_id');
			$odooApi->addLog('odoo order Id : ' . print_r($opmc_odoo_order_id, 1));

			if ($opmc_odoo_order_id) {
				return false;
			}

			if ('shop_order' != $order->get_type()) {
				return false;
			}

			if ('no' == $this->odoo_settings['odoo_export_order_on_checkout']) {
				return false;
			}

			$odoo_order_syced = opmc_hpos_get_post_meta($order_id, '_odoo_order_id', true);
			$odoo_invoice_id = opmc_hpos_get_post_meta($order_id, '_odoo_invoice_id', true);

			if ($export_inv_enable) {

				if ('' != $odoo_order_syced && '' == $odoo_invoice_id) {
					$odoo_order = $odooApi->update_record('sale.order', (int) $odoo_order_syced, array( 'state' => $statuses['order_state'] ));
					if ('' != $statuses['invoice_state']) {
						$invoice = $this->create_invoice($order_id);

						if (isset($invoice['fail'])) {
							$error_msg = 'Error Create Invoice For order ID  => ' . $order_id . ' Msg : ' . print_r($invoice['msg'], true);
							$odooApi->addLog($error_msg);
							return false;
						}
					}
				} elseif ('' != $odoo_order_syced && '' != $odoo_invoice_id) {
					$odoo_order = $odooApi->update_record('sale.order', (int) $odoo_order_syced, array( 'state' => $statuses['order_state'] ));

					if ($inv_mark_paid) {
						$invoice = $odooApi->update_record('account.move', (int) $odoo_invoice_id, array( 'state' => $statuses['invoice_state'] ));
						if (13 === $odoo_ver) {
							$invoice = $odooApi->update_record('account.move', (int) $odoo_invoice_id, array( 'invoice_payment_state' => $statuses['payment_state'] ));
						} else {
							$invoice = $odooApi->update_record('account.move', (int) $odoo_invoice_id, array( 'payment_state' => $statuses['payment_state'] ));
						}
					} else {
						$invoice = $odooApi->update_record('account.move', (int) $odoo_invoice_id, array( 'state' => 'draft' ));
						if (13 === $odoo_ver) {
							$invoice = $odooApi->update_record('account.move', (int) $odoo_invoice_id, array( 'invoice_payment_state' => 'not_paid' ));
						} else {
							$invoice = $odooApi->update_record('account.move', (int) $odoo_invoice_id, array( 'payment_state' => 'not_paid' ));
						}
					}
				} else {
					$this->order_create($order_id);
				}
			}
		}


		public function odoo_export_product_by_date() {
			if (!check_ajax_referer('odoo_security', 'security', false)) {
				wp_send_json(
					array(
						'threads' => array(),
						'subject' => '',
						'error' => 'There was security vulnerability issues in your request.',
					)
				);
				exit;
			}

			global $wpdb;
			$date_from = !empty($_POST['dateFrom']) ? sanitize_text_field($_POST['dateFrom']) : '';
			$date_to = !empty($_POST['dateTo']) ? sanitize_text_field($_POST['dateTo']) : '';
			if ('' != $date_from) {
				$date_from = gmdate('Y-m-d', strtotime('-1 day', strtotime($date_from)));
			}
			if ('' != $date_to) {
				$date_to = gmdate('Y-m-d', strtotime('1 day', strtotime($date_to)));
			}

			$query_string = array(
				'post_type' => 'product',
				'date_query' => array(
					'column' => 'post_date',
					'after' => $date_from,
					'before' =>  $date_to,
				),
				'fields' => 'ids',
				'post_status' => 'publish',
				'order' => 'ASC',
				'posts_per_page' => -1,
				'tax_query'  => array(
					array(
						'taxonomy' => 'product_cat',
						'field'    => 'term_id',
						'terms'    => array(),
						'operator' => 'NOT IN',
					),
				),
			);

			$products_q = new WP_Query($query_string);
			$products = $products_q->posts;

			$odooApi = new WC_ODOO_API();
			$product_added = 0;
			$product_upated = 0;
			foreach ($products as $key => $product_obj) {
				$product = wc_get_product($product_obj);

				$syncable_product = get_post_meta($product->get_id(), '_exclude_product_to_sync', true);

				if ('yes' == $syncable_product) {
					continue;
				}

				if ($product->has_child()) {
					//continue;
					$odoo_template_id = get_post_meta($product->get_id(), '_odoo_id', true);
					if ($odoo_template_id) {
						$this->do_export_variable_product_update((int) $odoo_template_id, $product);
					} else {
						$this->do_export_variable_product($product);
					}
				} else {

					$odoo_product_id = get_post_meta($product->get_id(), '_odoo_id', true);
					// Search Product on Odoo
					if (!$odoo_product_id) {
						$conditions = array(
							array(
								$this->odoo_sku_mapping, '=', $product->get_sku(),
							),
						);
						$odoo_product_id = $this->search_odoo_product($conditions, $product->get_id());
					}

					if ($odoo_product_id) {
						$this->update_odoo_product((int) $odoo_product_id, $product);
						$product_upated++;
					} else {
						$odoo_product_id = $this->create_product($product);
						$product_added++;
					}
					if (isset($odoo_product_id['fail'])) {
						$error_msg = 'Error for Creating/Updating  Product Id  => ' . $product->get_id() . ' Msg : ' . print_r($odoo_product_id['msg'], true);
						$odooApi->addLog($error_msg);
						continue;
					}
					if (false == $odoo_product_id) {
						continue;
					}
					update_post_meta($product->get_id(), '_odoo_id', $odoo_product_id);
					if ('yes' == $this->odoo_settings['odoo_export_update_price']) {
						if ($product->is_on_sale()) {
							$odoo_extra_product = get_post_meta($product->get_id(), '_product_extra_price_id', true);
							if ($odoo_extra_product) {
								$this->update_extra_price($odoo_extra_product, $product);
							} else {
								$this->create_extra_price($odoo_product_id, $product);
							}
						}
					}
					if ('yes' == $this->odoo_settings['odoo_export_update_stocks']) {
						if ($product->get_stock_quantity() > 0) {

							$product_qty = number_format((float) $product->get_stock_quantity(), 2, '.', '');
							$res = $this->update_product_quantity($odoo_product_id, $product_qty);
						}
					}
					update_post_meta($product->get_id(), '_odoo_image_id', $product->get_image_id());
				}
			}
			echo json_encode(array( 'result' => 'success', 'product_added' => $product_added, 'product_upated' => $product_upated, 'total_product' => count($products) ));
			exit;
		}


		public function odoo_export_customer_by_date() {
			if (!check_ajax_referer('odoo_security', 'security', false)) {
				wp_send_json(
					array(
						'threads' => array(),
						'subject' => '',
						'error' => 'There was security vulnerability issues in your request.',
					)
				);
				exit;
			}
			global $wpdb;

			$date_from = !empty($_POST['dateFrom']) ? sanitize_text_field($_POST['dateFrom']) : '';
			$date_to = !empty($_POST['dateTo']) ? sanitize_text_field($_POST['dateTo']) : '';
			if ('' != $date_from) {
				$date_from = gmdate('Y-m-d', strtotime('-1 day', strtotime($date_from)));
			}
			if ('' != $date_to) {
				$date_to = gmdate('Y-m-d', strtotime('1 day', strtotime($date_to)));
			}

			$args = array(
				'role' => 'customer',
				'date_query' => array(
					'after' => $date_from,
					'before' =>  $date_to,
					'inclusive' => false,
				),
				'order' => 'ASC',
				'orderby' => 'ID',
				'posts_per_page' => -1,
			);
			$wp_user_query = new WP_User_Query($args);
			$customers = $wp_user_query->get_results();

			$customer_added = 0;
			$customer_upated = 0;
			$email = array();
			foreach ($customers as $key => $customer) {
				if ('' != $customer->user_email) {
					$customer_id = get_user_meta($customer->ID, '_odoo_id', true);
					array_push($email, $customer->user_email);

					if (!$customer_id) {
						$conditions = array( array( 'type', '=', 'contact' ), array( 'email', '=', $customer->user_email ) );
						$customer_id = $this->search_odoo_customer($conditions, $customer->ID);
					}
					if ($customer_id) {
						$this->update_customer_to_odoo((int) $customer_id, $customer);
						$customer_upated++;
					} else {
						$customer_id = $this->create_customer($customer);
						$customer_added++;
					}
					if (false == $customer_id) {
						continue;
					}
					update_user_meta($customer->ID, '_odoo_id', $customer_id);
					$this->action_woocommerce_customer_save_address($customer->ID, 'shipping');
					$this->action_woocommerce_customer_save_address($customer->ID, 'billing');
				}
			}

			echo json_encode(array( 'result' => 'success', 'customer_added' => $customer_added, 'customer_upated' => $customer_upated, 'total_customer' => count($customers) ));
			exit;
		}


		public  function odoo_import_customer_by_date() {
			if (!check_ajax_referer('odoo_security', 'security', false)) {
				wp_send_json(
					array(
						'threads' => array(),
						'subject' => '',
						'error' => 'There was security vulnerability issues in your request.',
					)
				);
				exit;
			}
			global $wpdb;
			$date_from = !empty($_POST['dateFrom']) ? sanitize_text_field($_POST['dateFrom']) : '';
			$date_to = !empty($_POST['dateTo']) ? sanitize_text_field($_POST['dateTo']) : '';
			$odooApi = new WC_ODOO_API();
			$customers = $odooApi->readAll('res.partner', array( 'create_date', 'write_date', 'id', 'name', 'display_name', 'website', 'mobile', 'email', 'is_company', 'phone', 'image_medium', 'street', 'street2', 'zip', 'city', 'state_id', 'country_id', 'child_ids', 'type' ), array( array( 'type', '=', 'contact' ), array( 'create_date', '>=', $date_from ), array( 'create_date', '<=', $date_to ) ));
			$email = array();
			if (!isset($customers['fail']) && is_array($customers) && count($customers)) {
				foreach ($customers as $key => $customer) {
					if (isset($customer['email']) && !empty($customer['email'])) {
						$address_lists = array();
						if (count($customer['child_ids']) > 0) {
							$address_lists = $odooApi->fetch_record_by_ids('res.partner', $customer['child_ids'], array( 'id', 'name', 'display_name', 'website', 'mobile', 'email', 'is_company', 'phone', 'image_medium', 'street', 'street2', 'zip', 'city', 'state_id', 'country_id', 'child_ids', 'type' ));
							if (isset($address_lists['fail'])) {
								continue;
							}
						}
						$this->sync_customer_to_wc($customer, $address_lists);
						array_push($email, $customer['email']);
					}
				}
			}

			echo json_encode(array( 'result' => 'success', 'error' => $customers['msg'], 'total_customer' => count($customers) ));
			exit;
		}
	}

	new WC_ODOO_Functions();

endif;
