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
	
if ( ! class_exists( 'WC_ODOO_Functions' ) ) :
	class WC_ODOO_Functions {
			
			
		public $odoo_sku_mapping = 'default_code';
		public $odoo_attr_values = '';
		public $odoo_settings    = array();
			
		public $export_products;
		public $import_products;
		public $import_customers;
		public $export_customers;
		public $export_orders;
		public $import_orders;
			
		public function __construct() {
			add_action( 'woocommerce_checkout_order_processed', array( $this, 'create_order_to_odoo' ), 10, 1 );
			// add_action( 'woocommerce_customer_save_address', array($this,'action_woocommerce_customer_save_address'), 10, 2 );
			// add_action('init', array($this,'sync_refund_order'));
			// $this->sync_refund_order();
			$ODOO_integrations   = get_option( 'woocommerce_woocommmerce_odoo_integration_settings' );
			$this->odoo_settings = $ODOO_integrations;
			if ( isset( $ODOO_integrations[ 'odooSkuMapping' ] ) && '' != $ODOO_integrations[ 'odooSkuMapping' ] ) {
				$this->odoo_sku_mapping = $ODOO_integrations[ 'odooSkuMapping' ];
			}
				
			require_once plugin_dir_path( __FILE__ ) . 'background-process/class-opmc-import-products.php';
			require_once plugin_dir_path( __FILE__ ) . 'background-process/class-opmc-export-products.php';
			require_once plugin_dir_path( __FILE__ ) . 'background-process/class-opmc-export-customers.php';
			require_once plugin_dir_path( __FILE__ ) . 'background-process/class-opmc-import-customers.php';
			require_once plugin_dir_path( __FILE__ ) . 'background-process/class-opmc-export-orders.php';
			require_once plugin_dir_path( __FILE__ ) . 'background-process/class-opmc-import-orders.php';
				
			$this->export_products  = new Opmc_Product_Export();
			$this->import_products  = new Opmc_Product_Import();
			$this->import_customers = new Opmc_Customer_Import();
			$this->export_customers = new Opmc_Customer_Export();
			$this->import_orders    = new Opmc_Order_Import();
			$this->export_orders    = new Opmc_Order_Export();
				
			// Hook into your custom event
			add_action( 'opmc_dispatch_orders_to_odoo_event', array( $this, 'opmc_dispatch_orders_to_odoo' ) );
				
			/***  Disable email to customer in the time of import refund from Odoo ****/
			add_filter( 'woocommerce_email_recipient_customer_refunded_order', array(
				$this,
				'disable_email_for_refunded_orders',
			), 10, 3 );
			add_filter( 'woocommerce_email_recipient_customer_partially_refunded_order', array(
				$this,
				'disable_email_for_partially_order',
			), 10, 3 );
		}
			
		public function disable_email_for_partially_order( $recipient, $order, $object ) {
				
			$odoo_refund_invoice_id = opmc_hpos_get_post_meta( $order->get_id(), '_odoo_refund_invoice_check_email', true );
				
			if ( ! empty( $odoo_refund_invoice_id ) ) {
				opmc_hpos_delete_post_meta( $order->get_id(), '_odoo_refund_invoice_check_email', true );
					
				return;
			} else {
				return $recipient;
			}
		}
			
		public function disable_email_for_refunded_orders( $recipient, $order, $object ) {
				
			$odoo_refund_invoice_id = opmc_hpos_get_post_meta( $order->get_id(), '_odoo_refund_invoice_check_email', true );
			if ( ! empty( $odoo_refund_invoice_id ) ) {
				opmc_hpos_delete_post_meta( $order->get_id(), '_odoo_refund_invoice_check_email', true );
					
				return;
			} else {
				return $recipient;
			}
		}
			
		/* PLUGINS-2244 */
		/**
		 * Create Logs file
		 *
		 * @param  [int] $order_id [woocommerce order id]
		 */
		public function odoo_view_debug_logs() {
			if ( ! check_ajax_referer( 'odoo_security', 'security', false ) ) {
				wp_send_json(
					array(
						'threads' => array(),
						'subject' => '',
						'error'   => 'There was security vulnerability issues in your request.',
					) );
				exit;
			}
			$selected_log      = ! empty( $_POST[ 'selected' ] ) ? sanitize_text_field( $_POST[ 'selected' ] ) : '';
			$selected_log_text = ! empty( $_POST[ 'selected2' ] ) ? sanitize_text_field( $_POST[ 'selected2' ] ) : '';
			if ( '' != $selected_log ) {
				update_option( 'selected_log_view', $selected_log );
				update_option( 'selected_log_view_text', $selected_log_text );
			}
			echo json_encode(
				array(
					'result' => 'success',
				) );
			exit;
		}
			
		/* PLUGINS-2244 End */
			
		/**
		 * Create new order in odoo On Woo Checkout
		 *
		 * @param  [int] $order_id [woocommerce order id]
		 */
		public function create_order_to_odoo( $order_id ) {
			if ( 'yes' == $this->odoo_settings[ 'odoo_export_order_on_checkout' ] ) {
				$this->export_orders->push_to_queue( $order_id );
				$this->export_orders->save();
				//              $this->order_create($order_id);
				wp_schedule_single_event( time(), 'opmc_dispatch_orders_to_odoo_event' );
			}
		}
			
		public function opmc_dispatch_orders_to_odoo() {
			$this->export_orders->dispatch();
		}
			
		/**
		 * Create Customer in Odoo
		 *
		 * @param  [array] $customer_data [customer data fields]
		 *
		 * @return [int] [customer odoo id]
		 */
		public function create_customer( $customer_data ) {
				
			$odooApi = new WC_ODOO_API();
			$common  = new WC_ODOO_Common_Functions();
				
			if ( ! $common->is_authenticate() ) {
				return;
			}
				
			$all_meta_for_user = get_user_meta( $customer_data->ID );

			$billing_states = isset($all_meta_for_user[ 'billing_state' ][ 0 ]) ? $all_meta_for_user[ 'billing_state' ][ 0 ] : '';
			$billing_countries = isset($all_meta_for_user[ 'billing_country' ][ 0 ]) ? $all_meta_for_user[ 'billing_country' ][ 0 ] : '';
			$state_county      = $this->getStateId( $billing_states, $billing_countries );

			$data              = array(
				'name'          => get_user_meta( $customer_data->ID, 'first_name', true ) . ' ' . get_user_meta( $customer_data->ID, 'last_name', true ),
				'display_name'  => get_user_meta( $customer_data->ID, 'first_name', true ) . ' ' . get_user_meta( $customer_data->ID, 'last_name', true ),
				'email'         => $customer_data->user_email,
				'customer_rank' => 1,
				'type'          => 'contact',
				'phone'         => $all_meta_for_user[ 'billing_phone' ][ 0 ],
				'street'        => $all_meta_for_user[ 'billing_address_1' ][ 0 ],
				'city'          => $all_meta_for_user[ 'billing_city' ][ 0 ],
				'state_id'      => isset( $state_county[ 'state' ] ) ? $state_county[ 'state' ] : '',
				'country_id'    => isset( $state_county[ 'country' ] ) ? $state_county[ 'country' ] : '',
				'zip'           => $all_meta_for_user[ 'billing_postcode' ][ 0 ],
			);
				
			// $odooApi->addLog( 'Customer Data : ' . print_r( $data, 1 ) );
			$response = $odooApi->create_record( 'res.partner', $data );
			if ( $response->success ) {
				return $response->data->odoo_id;
			} else {
				// $error_msg = 'Error for Create customer => ' . $customer_data->user_email . ' Msg : ' . print_r( $response->message, true );
				$error_msg = '[Customer Export] [Error] [There are some errors while exporting customers\'(' . $customer_data->user_email . ') delivery address. API reponse ' . print_r( $response->message, 1 );
				$odooApi->addLog( $error_msg );
					
				return false;
			}
		}
			
		public function update_customer_to_odoo( $customer_id, $customer_data ) {
			$odooApi = new WC_ODOO_API();
			$common  = new WC_ODOO_Common_Functions();
				
			if ( ! $common->is_authenticate() ) {
				return;
			}
				
			$all_meta_for_user = get_user_meta( $customer_data->ID );

			$billing_states = isset($all_meta_for_user[ 'billing_state' ][ 0 ]) ? $all_meta_for_user[ 'billing_state' ][ 0 ] : '';
			$billing_countries = isset($all_meta_for_user[ 'billing_country' ][ 0 ]) ? $all_meta_for_user[ 'billing_country' ][ 0 ] : '';
			$state_county      = $this->getStateId( $billing_states, $billing_countries );

			$data              = array(
				'name'          => get_user_meta( $customer_data->ID, 'first_name', true ) . ' ' . get_user_meta( $customer_data->ID, 'last_name', true ),
				'display_name'  => get_user_meta( $customer_data->ID, 'first_name', true ) . ' ' . get_user_meta( $customer_data->ID, 'last_name', true ),
				'email'         => $customer_data->user_email,
				'customer_rank' => 1,
				'type'          => 'contact',
				'phone'         => $all_meta_for_user[ 'billing_phone' ][ 0 ],
				'street'        => $all_meta_for_user[ 'billing_address_1' ][ 0 ],
				'city'          => $all_meta_for_user[ 'billing_city' ][ 0 ],
				'state_id'      => isset( $state_county[ 'state' ] ) ? $state_county[ 'state' ] : '',
				'country_id'    => isset( $state_county[ 'country' ] ) ? $state_county[ 'country' ] : '',
				'zip'           => $all_meta_for_user[ 'billing_postcode' ][ 0 ],
			);
			$response          = $odooApi->update_record( 'res.partner', $customer_id, $data );
			if ( $response->success ) {
				return $response;
			} else {
				// $error_msg = 'Error for Create customer => ' . $customer_data->user_email . ' Msg : ' . print_r( $response->message, true );
				$error_msg = '[Customer Export] [Error] [There are some errors while exporting customers\'(' . $customer_data->user_email . '). API response ' . print_r( $response->message, 1 );
				$odooApi->addLog( $error_msg );
					
				return false;
			}
		}
			
		/**
		 * Create new product in the Odoo
		 *
		 * @param  [array] [product data]
		 *
		 * @return [int] [product id]
		 */
		public function create_product( $product_data, $for_order = false ) {
			$odooApi = new WC_ODOO_API();
				
			if ( $product_data->get_sku() == '' ) {
				$error_msg = '[Product Export] [Error] [Product : ' . str_replace( ' - ', '-', $product_data->get_name() ) . ' have missing/invalid SKU. This product will not be exported. ]';
				$odooApi->addLog( $error_msg );
					
				return false;
			}
			$helper    = WC_ODOO_Helpers::getHelper();
			$attrs_res = $odooApi->readAll( 'product.attribute.value', array(
				'id',
				'name',
				'display_type',
				'attribute_id',
				'pav_attribute_line_ids',
			) );
			$attrs     = json_decode( json_encode( $attrs_res->data->items ), true );
			// $odooApi->addLog( 'Read product Attrs : ' . print_r( $attrs, true ) );
			$odoo_attrs = array();
			foreach ( $attrs as $akey => $attr ) {
				$odoo_attrs[ strtolower( $attr[ 'attribute_id' ][ 1 ] ) ][ strtolower( $attr[ 'name' ] ) ] = $attr;
			}
			$product = wc_get_product( $product_data->get_id() );
				
			$data = array(
				'name'                  => $product_data->get_name(),
				'sale_ok'               => true,
				'type'                  => 'product',
				$this->odoo_sku_mapping => $product_data->get_sku(),
				'description_sale'      => $product_data->get_description(),
				'attribute_line_ids'    => $this->get_attributes_line_ids( $odoo_attrs, $product_data->get_attributes() ),
				'weight'                => $product_data->get_weight(),
				'volume'                => (int) ( (int) $product_data->get_height() * (int) $product_data->get_length() * (int) $product_data->get_width() ),
			);
			if ( $for_order || 'yes' == $this->odoo_settings[ 'odoo_export_create_categories' ] ) {
				$data[ 'categ_id' ] = (int) $this->get_category_id( $product_data );
			}
			$tag_ids = $this->get_odoo_tag_ids( $product_data );
			if (!empty($tag_ids)) {
				$data['product_tag_ids'] = $tag_ids;
			}
			if ( $for_order || 'yes' == $this->odoo_settings[ 'odoo_export_update_price' ] ) {
				// $data['list_price'] = $product_data->get_regular_price();
				$data[ 'list_price' ] = number_format($product_data->get_sale_price() ? $product_data->get_sale_price() : $product_data->get_regular_price(), 2);
			}
			if ( $helper->can_upload_image( $product_data ) ) {
				$data[ 'image_1920' ] = $helper->upload_product_image( $product_data );
			}
				
//              $odooApi->addLog( 'product data : ' . print_r( $data, 1 ) );
			$product_res = $odooApi->create_record( 'product.product', $data );
//               $odooApi->addLog( 'product response : ' . print_r( $product_res, 1 ) );
			if ( $product_res->success ) {
				update_post_meta( $product_data->get_id(), '_synced_data_rec', 'synced' );
				update_post_meta( $product_data->get_id(), '_synced_last_date_rec', gmdate( 'Y-m-d' ) );
			}
				
			return $product_res;
		}
			
		/**
		 * [inventory_syncCron description]
		 *
		 * @return [type] [description]
		 */
		public function inventory_sync() {
			global $wpdb;
				
			// if (!empty($_GET['import_odoo']) && 1 == $_GET['import_odoo']) {
				
			$product_count = $wpdb->get_row( "SELECT COUNT(*) as total_products FROM {$wpdb->posts} WHERE (post_type='product' OR post_type='product_variation') AND post_status='publish'" );
			$total_count   = $product_count->total_products;
				
			$limit            = 3;
			$total_loop_pages = ceil( $total_count / $limit );
				
			for ( $i = 0 ; $i <= $total_loop_pages ; $i++ ) {
				$sku_lot = $this->get_product_sku_lot( $i );
				foreach ( $sku_lot as $product_id => $woo_product_sku ) {
					$this->search_item_and_update_inventory( $woo_product_sku, $product_id );
				}
			}
				
			// }
		}
			
		/**
		 * [get_product_sku_lot description]
		 *
		 * @param integer $page [page number for the pagination]
		 *
		 * @return [array]        [collection of skus]
		 */
		private function get_product_sku_lot( $page = 0 ) {
			global $wpdb;
				
			$limit = 3;
				
			if ( 0 == $page ) {
				$offset = 0;
			} else {
				$offset = $limit * $page;
			}
				
			$products = $wpdb->get_results( $wpdb->prepare( "SELECT ID FROM {$wpdb->posts}  WHERE (post_type='product' OR post_type='product_variation') AND post_status='publish' LIMIT %d , %d", $offset, $limit ) );
			$sku_lot  = array();
				
			foreach ( $products as $product ) {
				$sku = get_post_meta( $product->ID, '_sku', true ); // MANISH : CHANGE THIS TO CUSTOM FIELD
				if ( ! empty( $sku ) ) {
					$sku_lot[ $product->ID ] = $sku;
				}
			}
				
			return $sku_lot;
		}
			
			
		/**
		 * Search product in Odoo and update the woo inventory
		 *
		 * @param  [string] $item_sku   [woo product sku]
		 * @param  [int]    $product_id [woocommerce product id]
		 */
		public function search_item_and_update_inventory( $item_sku, $product_id ) {
				
			$quantity = false;
			try {
				// perofrm search request
				require_once WC_ODOO_INTEGRATION_PLUGINDIR . '/includes/class-wc-odoo-api.php';
				$odooApi    = new WC_ODOO_API();
				$conditions = array(
					array(
						'field_key'   => $this->odoo_sku_mapping,
						'field_value' => $item_sku,
					),
				);
				$search     = $odooApi->fetchProductInventory( $conditions );
				// var_dump($search);
				if ( empty( $search ) || isset( $search[ 'fail' ] ) ) {
					// $error_msg = 'Error for Searching  Product  for Sku => ' . $item_sku . ' Msg : ' . print_r($search['msg'], true);
					// $odooApi->addLog($error_msg);
					return false;
				} elseif ( isset( $search[ 'id' ] ) ) {
					$data = $search;
					
					// get items location id
					$item_odoo_id = $data[ 'id' ];
					
					update_post_meta( $product_id, '_odoo_id', $item_odoo_id );
					
					if ( ! empty( $data[ 'list_price' ] ) && isset( $data[ 'list_price' ] ) ) {
						$main_price = $data[ 'list_price' ];
						update_post_meta( $product_id, '_regular_price', $main_price );
						update_post_meta( $product_id, '_sale_price', $main_price );
						$product = wc_get_product( $product_id );
						$product->set_regular_price( $main_price );
						$product->set_sale_price( $main_price );
						$product->set_price( $main_price );
						$product->save();
					}
					
					$quantity = $data[ 'qty_available' ] ? $data[ 'qty_available' ] : false;
					
					if ( false !== $quantity ) {
						update_post_meta( $product_id, '_stock', $quantity );
						
						update_post_meta( $product_id, '_manage_stock', 'yes' );
						if ( $quantity > 0 ) {
							update_post_meta( $product_id, '_stock_status', 'instock' );
						} else {
							update_post_meta( $product_id, '_stock_status', 'outofstock' );
						}
					}
				}
			} catch ( Exception $e ) {
				$error_msg = '[Product Inventory sync] [Error] [Error for Searching  Product  for Sku => ' . $item_sku . ' Msg : ' . print_r( $e->getMessage(), true ) . ']';
				$odooApi->addLog( $error_msg );
					
				return false;
			}
				
			return $quantity;
		}
			
		/**
		 * Create data for the odoo customer address
		 *
		 * @param  [string]  $addressType [address type delivery/invoice]
		 * @param  [array]   $userdata    [user data]
		 * @param  [integer] $parent_id   [user_id ]
		 *
		 * @return [array]              [formated address data for the customer]
		 */
		public function create_address_data( $addressType, $userdata, $parent_id ) {
			$data    = array(
				'name'      => $userdata[ 'first_name' ] . ' ' . $userdata[ 'last_name' ],
				'email'     => isset( $userdata[ 'email' ] ) ? $userdata[ 'email' ] : '',
				'street'    => isset( $userdata[ 'address_1' ] ) ? $userdata[ 'address_1' ] : '',
				'street2'   => isset( $userdata[ 'address_2' ] ) ? $userdata[ 'address_2' ] : '',
				'zip'       => isset( $userdata[ 'postcode' ] ) ? $userdata[ 'postcode' ] : '',
				'city'      => isset( $userdata[ 'city' ] ) ? $userdata[ 'city' ] : '',
				'type'      => $addressType,
				'parent_id' => (int) $parent_id,
				'phone'     => isset( $userdata[ 'phone' ] ) ? $userdata[ 'phone' ] : false,
			);
			$odooApi = new WC_ODOO_API();
			if ( ! empty( $userdata[ 'state' ] ) || ! empty( $userdata[ 'country' ] ) ) {
				// $odooApi->addLog('Woo State : '.print_r($userdata['state'],1));
				$userstate = isset( $userdata[ 'state' ] ) ? $userdata[ 'state' ] : '';
				$userscountry = isset( $userdata[ 'country' ] ) ? $userdata[ 'country' ] : '';

				$state_county = $this->getStateId( $userstate, $userscountry );
				// $odooApi->addLog( 'Odoo State : ' . print_r( $state_county, 1 ) );
				if ( ! empty( $state_county ) ) {
					$data[ 'state_id' ]   = isset( $state_county[ 'state' ] ) ? $state_county[ 'state' ] : '';
					$data[ 'country_id' ] = isset( $state_county[ 'country' ] ) ? $state_county[ 'country' ] : '';
				}
			}
				
			// $odooApi->addLog('User Data for '.print_r($addressType, 1).' : '. print_r($data,1));
			return $data;
		}
			
		/**
		 * Create_invoice description
		 *
		 * @param  [array] $odoo_customer [customer ids array]
		 * @param  [int]   $odoo_order_id    [order id]
		 *
		 * @return [int]                   [invoice Id]
		 */
		public function create_invoice_data( $odoo_order_id ) {
			$odooApi = new WC_ODOO_API();
			$order   = $odooApi->fetch_record_by_id( 'sale.order', array( $odoo_order_id ), array(
				'id',
				'name',
				'date_order',
				'partner_id',
				'partner_shipping_id',
			) );
			$order   = $order[ 0 ];
			// $odooApi->addLog( 'order Name : ' . print_r( $order, true ) );
			$odoo_settings = $this->odoo_settings;
				
			$data = array(
				// 'name' => 'INV/' . gmdate('Y') . '/' . $order['name'],
				// 'display_name' => 'INV/' . gmdate('Y') . '/' . $order['name'],
				'partner_id'              => (int) $order[ 'partner_id' ][0],
				// 'journal_id'              => 1,
				'invoice_origin'          => $order[ 'name' ],
				'state'                   => 'draft',
				'type_name'               => 'Invoice',
				'invoice_payment_term_id' => 1,
				'partner_shipping_id'     => (int) $order[ 'partner_shipping_id' ][0],
				'invoice_date'            => gmdate( 'Y-m-d', strtotime( $order[ 'date_order' ] ) ),
				'invoice_date_due'        => gmdate( 'Y-m-d', strtotime( $order[ 'date_order' ] ) ),
			);
			if ( isset( $odoo_settings[ 'odooVersion' ] ) && 13 == $odoo_settings[ 'odooVersion' ] ) {
				$data[ 'type' ] = 'out_invoice';
			} else {
				$data[ 'move_type' ] = 'out_invoice';
			}
			if ( isset( $odoo_settings[ 'odooVersion' ] ) && ( 14 == $odoo_settings[ 'odooVersion' ] ) ) {
				if ( isset( $odoo_settings[ 'gst_treatment' ] ) ) {
					$data[ 'l10n_in_gst_treatment' ] = $odoo_settings[ 'gst_treatment' ];
				}
			}
				
			return $data;
		}
			
		/**
		 * Create line item fo the invoice
		 *
		 * @param  [int]   $odoo_invoice_id [invoice id]
		 * @param  [array] $products        [product array data]
		 *
		 * @return [int]  $invoice_line_item  [invoice line id]
		 */
		public function create_invoice_lines( $odoo_invoice_id, $products ) {
			$invoice_line_item = array();
			$odooApi           = new WC_ODOO_API();
			$invoice           = $odooApi->fetch_record_by_id( 'account.move', array( $odoo_invoice_id ) );
				
			foreach ( $products as $key => $product ) {
				$invoice_line_item[] = $odooApi->create_record( 'account.move.line', $product );
			}
				
			return $invoice_line_item;
		}
			
		/**
		 * Update records on Odoo
		 *
		 * @param  [string]  $type   [record type]
		 * @param  [integer] $ids    [ids of records]
		 * @param  [array]   $fields [records fields to update]
		 *
		 * @return [integer]         [id]
		 */
		public function update_record( $type, $ids, $fields ) {
			$odooApi = new WC_ODOO_API();
				
			return $odooApi->update_record( $type, array( $ids ), $fields );
		}
			
			
		/**
		 * Create download url for the invoice
		 *
		 * @param int $invoice_id odoo invoice id
		 *
		 * @return stingr             invoice downloadable url/''
		 */
		public function create_pdf_download_link( $invoice_id ) {
			$odooApi      = new WC_ODOO_API();
			$invoice      = $odooApi->fetch_record_by_id( 'account.move', $invoice_id, array(
				'id',
				'access_url',
				'access_token',
			) );
			$invoice      = $invoice[ 0 ];
			$download_url = '';
			if ( isset( $invoice[ 'id' ] ) && $invoice[ 'id' ] == $invoice_id && isset( $invoice[ 'access_token' ] ) ) {
				// $wc_setting = new WC_ODOO_Integration_Settings();
				$wc_setting = get_option( 'woocommerce_woocommmerce_odoo_integration_settings' );
					
				$host         = $wc_setting[ 'client_url' ];
				$access_token = $invoice[ 'access_token' ];
				$access_url   = $invoice[ 'access_url' ];
				$download     = true;
				$report_type  = 'pdf';
				$download_url = $host . $access_url . '?access_token=' . $access_token . '&report_type=' . $report_type . '&download=' . $download;
			}
				
			return $download_url;
		}
			
		public function create_refund_invoice_data( $odoo_invoice_id ) {
			$odooApi      = new WC_ODOO_API();
			$odoo_invoice = $odooApi->fetch_record_by_id( 'account.move', (int) $odoo_invoice_id, array(
				'id',
				'name',
				'partner_id',
				'invoice_origin',
			) );
			$odoo_invoice = $odoo_invoice[ 0 ];
			// $odooApi->addLog('refund Invoice : ' . print_r($odoo_invoice, 1));
			if ( isset( $odoo_invoice[ 'invoice_origin' ] ) ) {
				$data = array(
						
					// 'name' => 'RINV/' . gmdate('Y') . '/' . $odoo_invoice['invoice_origin'],
					// 'display_name' => 'RINV/' . gmdate('Y') . '/' . $odoo_invoice['invoice_origin'] . ' (Reversal of: ' . $odoo_invoice['name'] . ')',
					'reversed_entry_id' => (int) $odoo_invoice_id,
					'partner_id'        => $odoo_invoice[ 'partner_id' ][ 0 ],
					// 'journal_id'        => 1,
					'invoice_origin'    => $odoo_invoice[ 'invoice_origin' ],
					// 'invoice_sent' => 1,
					// 'type' => 'out_refund',
					'type_name'         => 'Credit Note',
					'invoice_date'      => gmdate( 'Y-m-d' ),
					'invoice_date_due'  => gmdate( 'Y-m-d' ),
					// 'invoice_payment_state' => 'not_paid',
				);
				if ( isset( $this->odoo_settings[ 'odooVersion' ] ) && 13 == $this->odoo_settings[ 'odooVersion' ] ) {
					$data[ 'type' ]                  = 'out_refund';
					$data[ 'invoice_payment_state' ] = 'not_paid';
				} else {
					$data[ 'move_type' ]     = 'out_refund';
					$data[ 'payment_state' ] = 'not_paid';
				}
				if ( isset( $this->odoo_settings[ 'odooVersion' ] ) && ( 14 == $this->odoo_settings[ 'odooVersion' ] ) ) {
					if ( isset( $this->odoo_settings[ 'gst_treatment' ] ) ) {
						$data[ 'l10n_in_gst_treatment' ] = $this->odoo_settings[ 'gst_treatment' ];
					}
				}
					
				return $data;
			}
				
			return false;
		}
			
			
		public function searchOrCreateGuestUser( $order ) {
			$customer  = array();
			$user_data = $order->get_address( 'billing' );
			$odooApi   = new WC_ODOO_API();
			// $odooApi->addLog( 'Guest user email ' . print_r( $user_data, 1 ) );
			$conditions  = array(
				array(
					'field_key'   => 'email',
					'field_value' => $user_data[ 'email' ],
				),
			);
			$customer_id = $odooApi->search_record( 'res.partner', $conditions );
				
			// $odooApi->addLog( print_r( $user_data['email'], 1 ) . ' cusomter found : ' . print_r( $customer_id, 1 ) );
				
			if ( $customer_id->success && count( $customer_id->data->items ) > 0 ) {
				$customer_id = $customer_id->data->items[ 0 ];
			} else {
				$state_county = $this->getStateId( $user_data[ 'state' ], $user_data[ 'country' ] );
				// $odooApi->addLog(print_r($user_data['state'], 1) . ' user state : ' . print_r($state_county, 1));
				$data     = array(
					'name'          => $user_data[ 'first_name' ] . ' ' . $user_data[ 'last_name' ],
					'email'         => ( $user_data[ 'email' ] ) ? $user_data[ 'email' ] : '',
					'street'    => isset( $user_data[ 'address_1' ] ) ? $user_data[ 'address_1' ] : '',
					'street2'   => isset( $user_data[ 'address_2' ] ) ? $user_data[ 'address_2' ] : '',
					'zip'       => isset( $user_data[ 'postcode' ] ) ? $user_data[ 'postcode' ] : '',
					'city'          => $user_data[ 'city' ],
					'state_id'      => isset( $state_county[ 'state' ] ) ? $state_county[ 'state' ] : '',
					'country_id'    => $state_county[ 'country' ],
					'type'          => 'contact',
					'phone'         => ( $user_data[ 'phone' ] ) ? $user_data[ 'phone' ] : false,
					'customer_rank' => 1,
				);
				$response = $odooApi->create_record( 'res.partner', $data );
					
				if ( $response->success ) {
					$customer_id = $response->data->odoo_id;
				} else {
					$error_msg = '[Customer Export] [Error] [Error for Create customer => ' . $user_data[ 'email' ] . ' Msg : ' . print_r( $response[ 'msg' ], true ) . ']';
					$odooApi->addLog( $error_msg );
						
					return false;
				}
			}
				
			return $customer_id;
		}
			
		public function getStateId( $stateCode, $country_code ) {
			$odooApi     = new WC_ODOO_API();
			$countries   = json_decode( file_get_contents( WC_ODOO_INTEGRATION_PLUGINDIR . '/includes/countries.json' ), true );
			$country_ID  = $country_code;
			$state_codes = array();
			foreach ( $countries as $key => $country ) {
				if ( $country[ 'code' ] == $country_code ) {
					$country_ID               = $key;
					$state_codes[ 'country' ] = $country_ID;
				}
			}
			if ( preg_match( '([a-zA-Z].*[0-9]|[0-9].*[a-zA-Z])', $stateCode ) ) {
				$stateCode = preg_replace( '/[^0-9]/', '', $stateCode );
			}
			// $odooApi->addLog('Country ID for '.print_r($stateCode, 1).' of '.print_r($country_code, 1).' : '. print_r($country_ID, 1));
				
			$states = json_decode( file_get_contents( WC_ODOO_INTEGRATION_PLUGINDIR . '/includes/states.json' ), true );
			if ( isset( $states[ $country_ID ][ $stateCode ] ) ) {
				$state_codes[ 'state' ] = $states[ $country_ID ][ $stateCode ][ 'id' ];
			}
				
			// $odooApi->addLog('State COde : '. print_r($state_codes, 1));
			return $state_codes;
		}
			
		/**
		 * [action_woocommerce_customer_save_address description]
		 *
		 * @param int   $user_id      [description]
		 * @param array $load_address [description]
		 *
		 * @return [type]               [description]
		 */
		public function action_woocommerce_customer_save_address( $user_id, $load_address ) {
			$odoo_user_exits = get_user_meta( $user_id, '_odoo_id', true );
			if ( isset( $odoo_user_exits ) ) {
				$user         = new WC_Customer( $user_id );
				$address_type = ( 'shipping' == $load_address ) ? 'delivery' : 'invoice';
				$user_address = ( 'shipping' == $load_address ) ? $user->get_shipping() : $user->get_billing();
					
				if ( ! $this->can_create_address( $user_id, $user_address, $address_type ) ) {
					return false;
				}
				$address             = $this->create_address_data( $address_type, $user_address, $odoo_user_exits );
				$customer_address_id = get_user_meta( $user_id, '_odoo_' . $load_address . '_id', true );
				if ( ! $customer_address_id ) {
					$conditions          = array(
						array(
							'field_key'   => 'parent_id',
							'field_value' => $odoo_user_exits,
						),
						array(
							'field_key'   => 'type',
							'field_value' => $address_type,
						),
					);
					$customer_address_id = $this->search_odoo_customer( $conditions, $user_id );
				}
				$odooApi = new WC_ODOO_API();
				// $odooApi->addLog('cusomter id  : ' . print_r($customer_address_id, true));
					
				if ( $customer_address_id ) {
					// $odooApi->addLog('cusomter id  : ' . print_r($customer_address_id, true));
					$updated = $this->update_record( 'res.partner', (int) $customer_address_id, $address );
					if ( ! $updated->success ) {
						$error_msg = '[Customer Export] [Error] [Unable To update customer Odoo Id ' . $customer_address_id . ' Msg : ' . print_r( $updated->message, true ) . ']';
						$odooApi->addLog( $error_msg );
							
						return false;
					}
				} else {
					$address_res = $odooApi->create_record( 'res.partner', $address );
					if ( $address_res->success ) {
						update_user_meta( $user_id, '_odoo_' . $load_address . '_id', $address_res->data->odoo_id );
					} else {
						$error_msg = '[Customer Export] [Error] [Unable To Create customer Id ' . $user_id . ' Msg : ' . print_r( $address_res->message, true ) . ']';
						$odooApi->addLog( $error_msg );
							
						return false;
					}
				}
			}
		}
			
		/**
		 * Create data for the invoice line items
		 *
		 * @param int    $invoice_id    [description]
		 * @param object $item          [description]
		 * @param int    $product_id    [description]
		 * @param array  $customer_data [description]
		 * @param array  $tax_data      [description]
		 *
		 * @return [array]                [description]
		 */
		public function create_invoice_line_base_on_tax( $invoice_id, $item, $product_id, $customer_data, $tax_data ) {
				
			$odooApi = new WC_ODOO_API();
			// $wc_setting = new WC_ODOO_Integration_Settings();
			$wc_setting = get_option( 'woocommerce_woocommmerce_odoo_integration_settings' );
				
			$product      = $item->get_product();
			$price        = (string) $product->get_price() . '.00';
			$total_amount = $price * $item->get_quantity();
			$tax_amount   = $this->create_tax_amount( $tax_data, $total_amount );
				
			if ( 1 == $tax_data[ 'price_include' ] ) {
				$subtotal_amount = $total_amount - $tax_amount;
			} else {
				$subtotal_amount = $total_amount;
			}
				
			$invoice_line_data[] = array(
				'product_id'     => (int) $product_id,
				'name'           => $product->get_name(),
				'price_unit'     => $price + 0,
				'quantity'       => $item->get_quantity(),
				'move_id'        => $invoice_id,
				'account_id'     => (int) $wc_setting[ 'odooAccount' ],
				'partner_id'     => (int) $customer_data[ 'invoice_id' ],
				'tax_ids'        => array( array( 6, 0, array( (int) $tax_data[ 'id' ] ) ) ),
				'tax_tag_ids'    => array( array( 6, 0, array( 5 ) ) ),
				'price_subtotal' => $subtotal_amount,
			);
				
			$tax_amounts[] = $tax_amount;
				
			$invoice_line_data[] = array(
				// 'tax_ids' => [[6, 0, [$tax_id]]],
				// 'tag_ids' => array(array(6, 0, array(9))),
				'product_id'               => (int) $product_id,
				'name'                     => $tax_data[ 'name' ],
				'price_unit'               => abs( $tax_amount ),
				'price_subtotal'           => abs( $tax_amount ),
				'quantity'                 => 1.00,
				'move_id'                  => $invoice_id,
				'account_id'               => (int) $wc_setting[ 'odooAccount' ],
				'ref'                      => '',
				'partner_id'               => (int) $customer_data[ 'invoice_id' ],
				'exclude_from_invoice_tab' => true,
				'tax_base_amount'          => abs( $tax_amount ),
				'tax_tag_ids'              => array( array( 6, 0, array( 5, 30 ) ) ),
				
			);
			if ( 1 == $tax_data[ 'price_include' ] ) {
				$debtors_amount = - ( $total_amount );
			} else {
				$debtors_amount = - ( array_sum( $tax_amounts ) + $total_amount );
			}
				
			$invoice_line_data[] = array(
				// 'tax_ids' => [[6, 0, [$tax_id]]],
				'product_id'               => (int) $product_id,
				'name'                     => 'INV' . gmdate( 'Y' ) . $invoice_id,
				'price_unit'               => $debtors_amount,
				'price_subtotal'           => $debtors_amount,
				'quantity'                 => 1.00,
				'move_id'                  => $invoice_id,
				'account_id'               => (int) $wc_setting[ 'odooDebtorAccount' ],
				'ref'                      => '',
				'partner_id'               => $customer_data[ 'id' ],
				'exclude_from_invoice_tab' => true,
			);
				
			return $invoice_line_data;
		}
			
		/**
		 * Create data for the invoice line items
		 *
		 * @param int    $invoice_id    [description]
		 * @param object $item          [description]
		 * @param int    $product_id    [description]
		 * @param array  $customer_data [description]
		 * @param array  $tax_data      [description]
		 *
		 * @return [array]                [description]
		 */
		public function create_return_invoice_line_base_on_tax( $invoice_id, $item, $product_id, $customer_data, $tax_data ) {
				
			$refunded_quantity      = $item->get_quantity();
			$refunded_line_subtotal = abs( $item->get_subtotal() );
			$refunded_item_id       = $item->get_meta( '_refunded_item_id' );
			$order_line_id          = wc_get_order_item_meta( $refunded_item_id, '_order_line_id', true );
			$odd_order_line_id      = wc_get_order_item_meta( $refunded_item_id, '_invoice_line_id', true );
			$odoo_product_id        = get_post_meta( $item->get_product_id(), '_odoo_id', true );
				
			$odooApi = new WC_ODOO_API();
			// $wc_setting = new WC_ODOO_Integration_Settings();
			$wc_setting = get_option( 'woocommerce_woocommmerce_odoo_integration_settings' );
				
			$product      = $item->get_product();
			$price        = round( $refunded_line_subtotal / $refunded_quantity, 2 );
			$invoice_id   = (int) $invoice_id;
			$total_amount = $refunded_line_subtotal;
			$gst_price    = round( ( $tax_data[ 'amount' ] / 100 ) * $total_amount, 2 );
			$tax_amount   = $this->create_tax_amount( $tax_data, $total_amount );
				
			if ( 1 == $tax_data[ 'price_include' ] ) {
				$subtotal_amount = $total_amount - $tax_amount;
			} else {
				$subtotal_amount = $total_amount;
			}
				
			$invoice_line_data[] = array(
				'product_id'     => (int) $odoo_product_id,
				'name'           => $product->get_name(),
				'price_unit'     => $price + 0,
				'quantity'       => $item->get_quantity(),
				'move_id'        => $invoice_id,
				'account_id'     => (int) $wc_setting[ 'odooAccount' ],
				'partner_id'     => (int) $customer_data[ 'invoice_id' ],
				'price_subtotal' => $subtotal_amount,
			);
				
			if ( 'no' == $this->odoo_settings[ 'odoo_fiscal_position' ] ) {
				$invoice_line_data[ 'tax_ids' ] = array( array( 6, 0, array( (int) $tax_data[ 'id' ] ) ) );
			}
				
			$tax_amounts[] = $tax_amount;
				
			$invoice_line_data[] = array(
				// 'tax_ids' => [[6, 0, [$tax_id]]],
				// 'tag_ids' => array(array(6, 0, array(9))),
				'product_id'               => (int) $product_id,
				'name'                     => $tax_data[ 'name' ],
				'price_unit'               => abs( $tax_amount ),
				'price_subtotal'           => abs( $tax_amount ),
				'quantity'                 => 1.00,
				'move_id'                  => $invoice_id,
				'account_id'               => (int) $wc_setting[ 'odooAccount' ],
				'ref'                      => '',
				'partner_id'               => (int) $customer_data[ 'invoice_id' ],
				'exclude_from_invoice_tab' => true,
				'tax_base_amount'          => abs( $tax_amount ),
				
			);
			if ( 1 == $tax_data[ 'price_include' ] ) {
				$debtors_amount = - ( $total_amount );
			} else {
				$debtors_amount = - ( array_sum( $tax_amounts ) + $total_amount );
			}
				
			$invoice_line_data[] = array(
				// 'tax_ids' => [[6, 0, [$tax_id]]],
				'product_id'               => (int) $product_id,
				'name'                     => 'INV' . gmdate( 'Y' ) . $invoice_id,
				'price_unit'               => $debtors_amount,
				'price_subtotal'           => $debtors_amount,
				'quantity'                 => 1.00,
				'move_id'                  => $invoice_id,
				'account_id'               => (int) $wc_setting[ 'odooDebtorAccount' ],
				'ref'                      => '',
				'partner_id'               => $customer_data[ 'id' ],
				'exclude_from_invoice_tab' => true,
			);
				
			return $invoice_line_data;
		}
			
		/**
		 * Manage Customer Data
		 *
		 * @param object/null $user  userdata
		 * @param object $order order objects data
		 *
		 * @return array   $customer_data  return customer data
		 */
		public function getCustomerData( $user, $order ) {
			$odooApi       = new WC_ODOO_API();
			$customer_data = array();
			$odooApi->addLog('Cusomer User : ' . print_r($user->ID, 1));
			if ('yes' == $this->odoo_settings['odoo_map_to_default_customers']) {
				$customer_id = $this->odoo_settings['odoo_default_customer_id'];
				if ($user && isset($user->ID)) {
					$shipping_id = $this->getCustomerShippingAddressID($customer_id, $user, $order);
					if ($shipping_id) {
						$customer_data['shipping_id'] = $shipping_id;
					} else {
						return false;
					}
				} else {
					$shipping_id = $this->guestCustomerShippingAddressID($customer_id, $order);
					if ($shipping_id) {
						$customer_data['shipping_id'] = $shipping_id;
					} else {
						return false;
					}
				}
				$customer_data['id']          = $customer_id;
				$customer_data['invoice_id']  = $customer_id;
			} elseif ($user && isset($user->user_email)) {
				$customer_id = get_user_meta($user->ID, '_odoo_id', true);
				if (!$customer_id) {
					$conditions  = array(
						array(
							'field_key'   => 'email',
							'field_value' => $user->user_email,
							'operator'    => '=',
						),
					);
					$customer_id = $odooApi->search_record('res.partner', $conditions);
	
					if ($customer_id->success) {
						$customer_id = $customer_id->data->items;
						if (count($customer_id) > 0) {
							$customer_id = $customer_id[0];
						} else {
							$customer_id = false;
						}
					} else {
						$error_msg = '[Customer Sync] [Error] [Error for Search customer => ' . $user->user_email . ' Msg : ' . print_r($customer_id->message, true) . ']';
						$odooApi->addLog($error_msg);
						return false;
					}
	
					// If user not exists in Odoo then Create New Customer in odoo
					if (! isset($customer_id) || false == $customer_id) {
						$customer_id = $this->create_customer($user);
						update_user_meta($user->ID, '_odoo_id', $customer_id);
					}
				}
				if ($customer_id) {
					$customer_data['id'] = $customer_id;
					$billing_id = $this->getCustomerBillingAddressID($customer_id, $user, $order);
					if ($billing_id) {
						$customer_data['invoice_id'] = $billing_id;
					} else {
						return false;
					}
					
					$shipping_id = $this->getCustomerShippingAddressID($customer_id, $user, $order);
					if ($shipping_id) {
						$customer_data['shipping_id'] = $shipping_id;
					} else {
						return false;
					}
				}
			} else {
				$customer = $this->searchOrCreateGuestUser($order);
				// $odooApi->addLog('customer data : '. print_r($customer, 1));
				
				if (! $customer) {
					$error_msg = '[Customer Sync] [Error] [Error for Search customer => ' . $user->user_email . ' Msg : ' . print_r($customer['msg'], true) . ']';
					$odooApi->addLog($error_msg);
					return false;
				}
				$customer_id                  = $customer;
				$customer_data['id']          = $customer_id;
				$customer_data['invoice_id']  = ( isset($billing_id) ) ? $billing_id : $customer_id;
				$customer_data['shipping_id'] = ( isset($shipping_id) ) ? $shipping_id : $customer_id;
			}
			$odooApi->addLog('Odoo Customers : ' . print_r($customer_data, 1));
			return $customer_data;
		}
		
		public function getCustomerBillingAddressID( $customer_id, $user, $order ) {
			$odooApi = new WC_ODOO_API();
			$is_new_billing_address = true;

			$woo_opmc_billing_addresses = get_user_meta($user->ID, '_opmc_odoo_billing_addresses', true);

			// $odooApi->addLog('billing address : ' . print_r($woo_opmc_billing_addresses, true));

			$billing_address = $this->create_address_data('invoice', $order->get_address('billing'), $customer_id);

			if (! empty($woo_opmc_billing_addresses)) {
				foreach ($woo_opmc_billing_addresses as $woo_opmc_billing_address) {
					if (trim(strtolower($woo_opmc_billing_address['street'])) == trim(strtolower($billing_address['street'])) && $woo_opmc_billing_address['zip'] == $billing_address['zip']) {
						$billing_id = $woo_opmc_billing_address['partner_invoice_id'];
						$is_new_billing_address      = false;
						break;
					}
				}
			} else {
				$woo_opmc_billing_addresses = array();
			}

			if ($is_new_billing_address) {
				$billing_id = $odooApi->create_record('res.partner', $billing_address);

				if ($billing_id->success) {
					update_user_meta($user->ID, '_odoo_billing_id', $billing_id->data->odoo_id);
					$billing_id                  = $billing_id->data->odoo_id;
				} else {
					// $error_msg = 'Error for Creating  Billing Address for customer=> ' . $customer_id . ' Msg : ' . print_r( $billing_id->message, true );
					$error_msg = '[Customer Export] [Error] [Error for Creating  Billing Address for customer : ' . $customer_id . ' API Response : ' . print_r($billing_id->message, true) . ']';
					$odooApi->addLog($error_msg);
					return false;
				}

				update_user_meta($user->ID, '_odoo_billing_id', $billing_id);

				$billing_address['partner_invoice_id'] = $billing_id;

				$woo_opmc_billing_addresses[] = $billing_address;

				update_user_meta($user->ID, '_opmc_odoo_billing_addresses', $woo_opmc_billing_addresses);
			}
			return $billing_id;
		}
		
		public function getCustomerShippingAddressID( $customer_id, $user, $order ) {
			$odooApi = new WC_ODOO_API();
			$is_new_shipping_address = true;
			
			$shipping_address_data = $order->get_address('shipping');
			$billing_address_data = $order->get_address('billing');
			if (empty($shipping_address_data['phone'])) {
				$shipping_address_data['phone'] = $billing_address_data['phone'];
			}
			if (empty($shipping_address_data['email'])) {
				$shipping_address_data['email'] = $billing_address_data['email'];
			}

			$shipping_address = $this->create_address_data('delivery', $shipping_address_data, $customer_id);
			
			$woo_opmc_shipping_addresses = get_user_meta($user->ID, '_opmc_odoo_shipping_addresses', true);

			if (! empty($woo_opmc_shipping_addresses)) {
				foreach ($woo_opmc_shipping_addresses as $woo_opmc_shipping_address) {
					if (trim(strtolower($woo_opmc_shipping_address['street'])) == trim(strtolower($shipping_address['street'])) && $woo_opmc_shipping_address['zip'] == $shipping_address['zip'] && $woo_opmc_shipping_address['parent_id'] == $shipping_address['parent_id']) {
						$shipping_id = $woo_opmc_shipping_address['partner_shipping_id'];
						$is_new_shipping_address      = false;
						break;
					}
				}
			} else {
				$woo_opmc_shipping_addresses = array();
			}

			if ($is_new_shipping_address) {
				$shipping_id = $odooApi->create_record('res.partner', $shipping_address);

				if ($shipping_id->success) {
					$shipping_id = $shipping_id->data->odoo_id;
				} else {
					// $error_msg = 'Error for Creating Shipping Address for customer=> ' . $customer_id . ' Msg : ' . print_r( $shipping_id->message, true );
					$error_msg = '[Customer Export] [Error] [Error for Creating  Shipping Address for customer :' . $customer_id . 'API Response : ' . print_r($shipping_id->message, true) . ']';
					$odooApi->addLog($error_msg);
					return false;
				}

				$shipping_address['partner_shipping_id'] = $shipping_id;

				$woo_opmc_shipping_addresses[] = $shipping_address;

				update_user_meta($user->ID, '_opmc_odoo_shipping_addresses', $woo_opmc_shipping_addresses);
			}
			return $shipping_id;
		}
		
		public function guestCustomerShippingAddressID( $customer_id, $order ) {
			$odooApi = new WC_ODOO_API();
			$shipping_address_data = $order->get_address('shipping');
			$billing_address_data = $order->get_address('billing');
			if (empty($shipping_address_data['phone'])) {
				$shipping_address_data['phone'] = $billing_address_data['phone'];
			}
			if (empty($shipping_address_data['email'])) {
				$shipping_address_data['email'] = $billing_address_data['email'];
			}
			$shipping_address = $this->create_address_data('delivery', $shipping_address_data, $customer_id);
			$shipping_id = '';
//                  $odooApi->addLog('order delivery address : '. print_r($shipping_address, 1));
			$conditions = array(
							array(
							  'field_key' => 'type',
							  'field_value' => 'delivery',
							  'operator' => '=',
							),
							array(
							  'field_key' => 'parent_id',
							  'field_value' => (int) $customer_id,
							  'operator' => '=',
							),
							array(
							  'field_key' => 'email',
							  'field_value' => $shipping_address['email'],
							  'operator' => '=',
							),
							array(
							  'field_key' => 'street',
							  'field_value' => $shipping_address['street'],
							  'operator' => '=',
							),
							array(
							  'field_key' => 'zip',
							  'field_value' => $shipping_address['zip'],
							  'operator' => '=',
							),
						  );
//          $odooApi->addLog('conditions : '. print_r($conditions, 1));
			$odoo_shipping_address = $odooApi->search( 'res.partner', $conditions );
//          $odooApi->addLog('address found : '. print_r($odoo_shipping_address, 1));
			if ($odoo_shipping_address->success) {
				if (!empty($odoo_shipping_address->data->items)) {
					$shipping_id = $odoo_shipping_address->data->items[0];
				} else {
					$shipping_id = '';
				}
//              $odooApi->addLog('address found  shpping id: '. print_r($shipping_id, 1));
			}
			
			if ('' == $shipping_id) {
				$shipping_id = $odooApi->create_record('res.partner', $shipping_address);
				if ($shipping_id->success) {
					$shipping_id = $shipping_id->data->odoo_id;
				} else {
					$error_msg = '[Customer Export] [Error] [Error for Creating  Shipping Address for customer :' . $customer_id . 'API Response : ' . print_r($shipping_id->message, true) . ']';
					$odooApi->addLog($error_msg);
					return false;
				}
			}
			
			return $shipping_id;
		}
			
		public function create_tax_amount( $tax, $amount ) {
			switch ( $tax[ 'amount_type' ] ) {
				case 'fixed':
					return round( $tax[ 'amount' ], 2 );
					break;
				case 'percent':
					// return round(( $tax['amount'] / 100 ) * $amount, 2);
					if ( 1 == $tax[ 'price_include' ] ) {
						$tax_included_price = round( ( $amount / ( 1 + $tax[ 'amount' ] / 100 ) ), 2 );
						$tax_amount         = $tax_included_price - $amount;
							
						return $tax_amount;
					} else {
						return round( ( $tax[ 'amount' ] / 100 ) * $amount, 2 );
					}
						
					break;
				case 'group':
					return round( ( $tax[ 'amount' ] / 100 ) * $amount, 2 );
					break;
				case 'division':
					$tax_included_price = round( ( $amount / ( 1 - $tax[ 'amount' ] / 100 ) ), 2 );
					$tax_amount         = $tax_included_price - $amount;
						
					return $tax_amount;
					break;
				default:
					return 0.00;
					break;
			}
		}
			
		public function get_delivery_product_id() {
			$shpping_id = get_option( 'odoo_shipping_product_id' );
			if ( false != $shpping_id ) {
				return $shpping_id;
			} else {
				return $this->create_shipping_product();
			}
		}
			
		/**
		 * Create new product in the Odoo
		 *
		 * @param  [array] [product data]
		 *
		 * @return [int] [product id]
		 */
		public function create_shipping_product() {
				
			$odooApi  = new WC_ODOO_API();
			$data     = array(
				'name'                  => __( 'WC Shipping Charge', 'wc-odoo-integration' ),
				'service_type'          => 'manual',
				'sale_ok'               => false, // 'categ_id' => 4,
				'type'                  => 'service',
				$this->odoo_sku_mapping => 'wc_odoo_delivery',
				'description_sale'      => __( 'delivery product created by WC Odoo Integration', 'wc-odoo-integration' ),
				'list_price'            => 0.00,
				
			);
			$response = $odooApi->create_record( 'product.product', $data );
				
			if ( $response->success ) {
				return $response->data->odoo_id;
			} else {
				$error_msg = '[Shipping Item Sync] [Error] [Error for create => Msg : ' . print_r( $response->message, true ) . ']';
				$this->addLog( $error_msg );
					
				return false;
			}
		}
			
		public function create_shipping_invoice_line( $invoice_id, $order, $customer_data, $tax_data ) {
				
			$odooApi = new WC_ODOO_API();
			// $wc_setting = new WC_ODOO_Integration_Settings();
			$wc_setting = get_option( 'woocommerce_woocommmerce_odoo_integration_settings' );
				
			$price        = $order->get_shipping_total();
			$total_amount = $price * 1;
			$tax_amount   = $this->create_tax_amount( $tax_data, $total_amount );
				
			if ( 1 == $tax_data[ 'price_include' ] ) {
				$subtotal_amount = $total_amount - $tax_amount;
			} else {
				$subtotal_amount = $total_amount;
			}
				
			$invoice_line_data[] = array(
				'product_id'     => (int) $this->get_delivery_product_id(),
				'name'           => __( 'Shipping Charge', 'wc-odoo-integration' ),
				'price_unit'     => $price + 0,
				'quantity'       => 1,
				'move_id'        => $invoice_id,
				'account_id'     => (int) $wc_setting[ 'odooAccount' ],
				'partner_id'     => (int) $customer_data[ 'invoice_id' ],
				'price_subtotal' => $subtotal_amount,
			);
				
			if ( 'no' == $this->odoo_settings[ 'odoo_fiscal_position' ] ) {
				$invoice_line_data[ 'tax_ids' ] = array( array( 6, 0, array( (int) $tax_data[ 'id' ] ) ) );
			}
				
			$tax_amounts[] = $tax_amount;
				
			$invoice_line_data[] = array(
				// 'tax_ids' => [[6, 0, [$tax_id]]],
				// 'tag_ids' => array(array(6, 0, array(9))),
				'product_id'               => (int) $this->get_delivery_product_id(),
				'name'                     => $tax_data[ 'name' ],
				'price_unit'               => abs( $tax_amount ),
				'price_subtotal'           => abs( $tax_amount ),
				'quantity'                 => 1.00,
				'move_id'                  => $invoice_id,
				'account_id'               => (int) $wc_setting[ 'odooAccount' ],
				'ref'                      => '',
				'partner_id'               => (int) $customer_data[ 'invoice_id' ],
				'exclude_from_invoice_tab' => true,
				'tax_base_amount'          => abs( $tax_amount ),
				
			);
			if ( 1 == $tax_data[ 'price_include' ] ) {
				$debtors_amount = - ( $total_amount );
			} else {
				$debtors_amount = - ( array_sum( $tax_amounts ) + $total_amount );
			}
				
			$invoice_line_data[] = array(
				// 'tax_ids' => [[6, 0, [$tax_id]]],
				'product_id'               => (int) $this->get_delivery_product_id(),
				'name'                     => 'INV' . gmdate( 'Y' ) . $invoice_id,
				'price_unit'               => $debtors_amount,
				'price_subtotal'           => $debtors_amount,
				'quantity'                 => 1.00,
				'move_id'                  => $invoice_id,
				'account_id'               => (int) $wc_setting[ 'odooDebtorAccount' ],
				'ref'                      => '',
				'partner_id'               => $customer_data[ 'id' ],
				'exclude_from_invoice_tab' => true,
			);
				
			return $invoice_line_data;
		}
			
		public function get_category_id( $product ) {
			$terms   = wp_get_post_terms( $product->get_id(), 'product_cat', array( 'fields' => 'ids' ) );
			$odooApi = new WC_ODOO_API();
			//          $odooApi->addLog('categories details : '. print_r($terms, 1));
				
			if ( ! is_wp_error( $terms ) && count( $terms ) > 0 ) {
				$cat_id       = (int) $terms[ 0 ];
				$odoo_term_id = get_term_meta( $cat_id, '_odoo_term_id', true );
					
				if ( $odoo_term_id ) {
					return $odoo_term_id;
				} else {
					$odooApi = new WC_ODOO_API();
					$term    = get_term( $cat_id );
					//                  $odooApi->addLog('category terms details : '. print_r($term, 1));
					$conditions         = array(
						array(
							'field_key'   => 'name',
							'field_value' => $term->name,
						),
					);
					$data               = array(
						'name' => $term->name,
					);
					$odoo_term_response = $odooApi->search_record( 'product.category', $conditions );
					if ( $odoo_term_response->success ) {
						// $odooApi->addLog('category id : '. var_export($odoo_term_response, 1));
						if ( ! empty( $odoo_term_response->data->items ) ) {
							// $odooApi->addLog('Categories found ');
							$odoo_term_id = $odoo_term_response->data->items[ 0 ];
						}
					} else {
						$error_msg = '[Category Sync] [Error] [Error for Search Category => ' . $cat_id . ' Msg : ' . print_r( $odoo_term_response->message, true ) . ']';
						$odooApi->addLog( $error_msg );
							
						return false;
					}
					if ( $odoo_term_id ) {
						return $odoo_term_id;
					} else {
						if ( $term->parent ) {
							$response            = $this->get_odoo_parent_categories( $term->parent );
							$data[ 'parent_id' ] = (int) $response;
						}
							
						$response = $odooApi->create_record( 'product.category', $data );
							
						// $odooApi->addLog( ' product categoy create response : ' . print_r( $response, true ) );
							
						if ( $response->success ) {
							update_term_meta( $cat_id, '_odoo_term_id', $response->data->odoo_id );
								
							return $response->data->odoo_id;
						} else {
							// $error_msg = 'Error for Creating category for Id  => ' . $cat_id . ' Msg : ' . print_r($response, true);
							$odooApi->addLog( '[Customer Sync] [Error] [Error for Creating category for Id  => ' . $cat_id . ' Msg : ' . print_r( $response->message, true ) ) . ']';
						}
					}
				}
			} else {
				return 1;
			}
		}
			
		public function get_odoo_parent_categories( $parent_category ) {
			$odooApi = new WC_ODOO_API();
			$term    = get_term( $parent_category );
			//          $odooApi->addLog('category terms details : '. print_r($term, 1));
			$odoo_term_id = get_term_meta( $parent_category, '_odoo_term_id', true );
			if ( $odoo_term_id ) {
				return $odoo_term_id;
			} else {
				$conditions         = array(
					array(
						'field_key'   => 'name',
						'field_value' => $term->name,
					),
				);
				$data               = array(
					'name' => $term->name,
				);
				$odoo_term_response = $odooApi->search_record( 'product.category', $conditions );
				if ( $odoo_term_response->success ) {
					//                   $odooApi->addLog('category id : '. var_export($odoo_term_response, 1));
					if ( ! empty( $odoo_term_response->data->items ) ) {
						$odoo_term_id = $odoo_term_response->data->items[ 0 ];
					}
				} else {
					$error_msg = '[Category Sync] [Error] [Error for Search Category => ' . $parent_category . ' Msg : ' . print_r( $odoo_term_response->message, true ) . ']';
					$odooApi->addLog( $error_msg );
						
					return false;
				}
				if ( $odoo_term_id ) {
					return $odoo_term_id;
				} else {
					if ( $term->parent ) {
						$response            = $this->get_odoo_parent_categories( $term->parent );
						$data[ 'parent_id' ] = (int) $response;
					}
						
					$response = $odooApi->create_record( 'product.category', $data );
						
					//                   $odooApi->addLog( ' product categoy create response : ' . print_r( $response, true ) );
						
					if ( $response->success ) {
						update_term_meta( $parent_category, '_odoo_term_id', $response->data->odoo_id );
							
						return $response->data->odoo_id;
					} else {
						// $error_msg = 'Error for Creating category for Id  => ' . $cat_id . ' Msg : ' . print_r($response, true);
						$odooApi->addLog( '[Customer Sync] [Error] [Error for Creating category for Id  => ' . $parent_category . ' Msg : ' . print_r( $response->message, true ) ) . ']';
					}
				}
			}
		}
			
		public function sync_refund_order() {
			global $wpdb;
			$order_origins = $wpdb->get_results( "SELECT meta_value FROM {$wpdb->postmeta}  WHERE meta_key='_odoo_order_origin'", 'OBJECT_K' );
				
			$refunded_invoices = $wpdb->get_results( "SELECT meta_value FROM {$wpdb->postmeta}  WHERE meta_key='_odoo_return_invoice_id'", 'OBJECT_K' );
				
			$origins              = array_keys( $order_origins );
			$refunded_invoice_ids = array_keys( $refunded_invoices );
				
			$odooApi       = new WC_ODOO_API();
			$odoo_settings = $this->odoo_settings;
				
			if ( isset( $odoo_settings[ 'odooVersion' ] ) && 13 == $odoo_settings[ 'odooVersion' ] ) {
				$conditions = array(
					array(
						'field_key'   => 'type',
						'field_value' => 'out_refund',
						'operator'    => '=',
					),
				);
			} else {
				$conditions = array(
					array(
						'field_key'   => 'move_type',
						'field_value' => 'out_refund',
						'operator'    => '=',
					),
				);
			}
			if ( count( $refunded_invoice_ids ) > 0 ) {
				$conditions[] = array(
					'field_key'   => 'id',
					'field_value' => $refunded_invoice_ids,
					'operator'    => 'not in',
				);
			}
			if ( count( $origins ) > 0 ) {
				$conditions[] = array(
					'field_key'   => 'invoice_origin',
					'field_value' => $origins,
					'operator'    => 'in',
				);
			}
			$conditions[]   = array(
				'field_key'   => 'state',
				'field_value' => 'posted',
				'operator'    => '=',
			);
			$invoice_fields = array( 'id', 'invoice_origin', 'invoice_line_ids' );
			// $odooApi->addlog('conditions : ' . print_r($conditions, true));
			$invioces = $odooApi->search_records( 'account.move', $conditions, $invoice_fields );
				
			if ( $invioces->success ) {
				$invioces = json_decode( json_encode( $invioces->data->items ), true );
				// $odooApi->addlog(' refund order : '. print_r($invioces, true));
				if ( 0 == count( $invioces ) ) {
					return;
				}
				foreach ( $invioces as $key => $invoice ) {
					$conditions          = array(
						array(
							'field_key'   => 'id',
							'field_value' => $invoice[ 'invoice_line_ids' ],
							'operator'    => 'in',
						),
					);
					$invioce_line_fields = array(
						'price_total',
						'price_subtotal',
						'quantity',
						'product_id',
						'tax_ids',
					);
					$invioce_lines       = $odooApi->search_records( 'account.move.line', $conditions, $invioce_line_fields );
					// $odooApi->addLog('invoie lines : '. print_r($invioce_lines, 1));
						
					if ( $invioce_lines->success ) {
						$invioce_lines = json_decode( json_encode( $invioce_lines->data->items ), true );
						if ( is_array( $invioce_lines ) ) {
							$inv_lines = array();
							foreach ( $invioce_lines as $ilkey => $invioce_line ) {
								if ( isset( $invioce_line[ 'product_id' ][ 0 ] ) ) {
									$product_id = $this->get_post_id_by_meta_key_and_value( '_odoo_id', $invioce_line[ 'product_id' ][ 0 ] );
									if ( $product_id ) {
										$inv_lines[ $product_id ]                    = $invioce_line;
										$inv_lines[ $product_id ][ 'wc_product_id' ] = $product_id;
									}
								}
							}
								
							if ( ! empty( $invoice[ 'invoice_origin' ] ) ) {
								$conditions = array(
									array(
										'field_key'   => 'name',
										'field_value' => $invoice[ 'invoice_origin' ],
									),
								);
								$order      = $odooApi->search_record( 'sale.order', $conditions );
								// $odooApi->addLog( 'sale ordder : ' . print_r( $order, 1 ) );
									
								if ( $order->success ) {
									$order    = json_decode( json_encode( $order->data->items ), true );
									$order_id = $this->get_post_id_by_meta_key_and_value( '_odoo_order_id', $order[ 0 ] );
									if ( $order_id ) {
										$return_id = opmc_hpos_get_post_meta( $order_id, '_odoo_return_invoice_id', true );
										if ( $return_id ) {
											$error_msg = '[Order Sync] [Info] [Refund Already Synced For Order  => ' . $order_id . ']';
											$odooApi->addLog( $error_msg );
												
											return false;
										}
										opmc_hpos_update_post_meta( $order_id, '_odoo_refund_invoice_check_email', $order_id );
										$this->wc_order_refund( $order_id, $inv_lines, $invoice[ 'id' ] );
									}
								}
							}
						}
					}
				}
			}
		}
			
			
		public function wc_order_refund( $order_id, $inv_lines, $inv_id ) {
			$order   = wc_get_order( $order_id );
			$odooApi = new WC_ODOO_API();
				
			// If it's something else such as a WC_Order_Refund, we don't want that.
			if ( ! is_a( $order, 'WC_Order' ) ) {
				$msg = '[Customer Sync] [Error] [Provided ID is not a WC Order : ' . $order_id . ']';
				$odooApi->addLog( $msg );
					
				return false;
			}
			if ( 'refunded' == $order->get_status() ) {
				$msg = '[Customer Sync] [Info] [Order has been already refunded : ' . $order_id . ']';
				$odooApi->addLog( $msg );
					
				return false;
			}
			if ( count( $order->get_refunds() ) > 0 ) {
				$msg = '[Customer Sync] [Info] [Order has been already refunded : ' . $order_id . ']';
				$odooApi->addLog( $msg );
					
				return false;
			}
			$refund_amount = 0;
			$line_items    = array();
			/* get tax id from the admin setting */
			$tax_id = (int) $this->odoo_settings[ 'odooTax' ];
				
			$tax_data_odoo = $odooApi->fetch_file_record_by_id( 'taxes', 'account.tax', $tax_id );
				
			$order_items = $order->get_items();
			if ( $order_items ) {
				foreach ( $order_items as $item_id => $item ) {
					if ( isset( $inv_lines[ $item->get_product_id() ] ) ) {
						$current_item = $inv_lines[ $item->get_product_id() ];
						$tax_data     = wc_get_order_item_meta( $item_id, '_line_tax_data', true );
						// $refund_tax = $current_item['amount_tax'];
						if ( 1 == $tax_data_odoo[ 'price_include' ] ) {
							$refund_tax = abs( $this->create_tax_amount( $tax_data_odoo, $current_item[ 'price_total' ] ) );
						} else {
							$refund_tax = $this->create_tax_amount( $tax_data_odoo, $current_item[ 'price_subtotal' ] );
						}
						$refund_amount = wc_format_decimal( $refund_amount + $current_item[ 'price_subtotal' ] + $refund_tax );
							
						$line_items[ $item_id ] = array(
							'qty'          => abs( $current_item[ 'quantity' ] ),
							'refund_total' => wc_format_decimal( $current_item[ 'price_subtotal' ] ),
							'refund_tax'   => array( 1 => wc_format_decimal( abs( $refund_tax ) ) ),
						);
					}
				}
			}
			if ( $refund_amount < 1 ) {
				$msg = '[Order Sync] [Error] [Refund Creating error for ' . $order_id . ' Msg Invalid Refund Amount ' . $refund_amount . ']';
				$odooApi->addLog( $msg );
					
				return false;
			}
			$refund_reason = 'Odoo Return';
			$refund_data   = array(
				'amount'         => $refund_amount,
				'reason'         => $refund_reason,
				'order_id'       => $order_id,
				'line_items'     => $line_items,
				'refund_payment' => false,
			);
				
			$refund = wc_create_refund( $refund_data );
				
			if ( ! is_wp_error( $refund ) ) {
				opmc_hpos_update_post_meta( $order_id, '_odoo_return_invoice_id', $inv_id );
				opmc_hpos_update_post_meta( $order_id, '_odoo_refund_invoice_check_email', $inv_id );
			} else {
				$msg = '[Order Sync] [Error] [Error In creating Refund for' . $order_id . ' msg ' . print_r( array(
																												 $refund_data,
																												 $refund,
																											 ), true ) . ']';
				$odooApi->addLog( $msg );
					
				return false;
			}
		}
			
		public function get_post_id_by_meta_key_and_value( $key, $value ) {
			global $wpdb;
			$meta = $wpdb->get_results( 'SELECT post_id FROM `' . $wpdb->postmeta . "` WHERE meta_key='" . esc_sql( $key ) . "' AND meta_value='" . esc_sql( $value ) . "'" );
			if ( is_array( $meta ) && ! empty( $meta ) && isset( $meta[ 0 ] ) ) {
				$meta = $meta[ 0 ];
			}
			if ( is_object( $meta ) ) {
				return $meta->post_id;
			} else {
				return false;
			}
		}
			
			
		public function get_user_id_by_meta_key_and_value( $key, $value ) {
			global $wpdb;
			$meta = $wpdb->get_results( 'SELECT user_id FROM `' . $wpdb->usermeta . "` WHERE meta_key='" . esc_sql( $key ) . "' AND meta_value='" . esc_sql( $value ) . "'" );
			if ( is_array( $meta ) && ! empty( $meta ) && isset( $meta[ 0 ] ) ) {
				$meta = $meta[ 0 ];
			}
			if ( is_object( $meta ) ) {
				return $meta->user_id;
			} else {
				return false;
			}
		}
			
		public function update_product_quantity( $product_id, $quantity, $template = 0 ) {
			//          $creds = get_option('woocommerce_woocommmerce_odoo_integration_settings');
			// if (isset($creds['createProductToOdoo']) && 'yes' == $creds['createProductToOdoo'] ) {
			$odooApi = new WC_ODOO_API();
			if ( 0 == $template ) {
				$tamplate    = $odooApi->fetch_record_by_ids( 'product.product', $product_id, array( 'product_tmpl_id' ) );
				$template_id = $tamplate->data->records[ 0 ]->product_tmpl_id[ 0 ];
			} else {
				$template_id = ( 0 == $template ) ? $product_id : $template;
			}
			$odooApi->addLog( 'template ID : ' . print_r( $template_id, 1 ) );
			$odooApi->addLog( 'product ID : ' . print_r( $product_id, 1 ) );
			$quantity_id = $odooApi->create_record(
				'stock.change.product.qty', array(
				'new_quantity'    => (int) $quantity,
				'product_tmpl_id' => (int) $template_id,
				'product_id'      => (int) $product_id,
			) );
			$odooApi->addLog( 'Qty ID : ' . print_r( $quantity_id, 1 ) );
			if ( $quantity_id->success ) {
				$quantity_id = $quantity_id->data->odoo_id;
				// $odooApi->addLog( 'quantity_id : ' . print_r( $quantity_id, 1 ) );
				$change_product_qty_res = $odooApi->custom_api_call( 'stock.change.product.qty', 'change_product_qty', array( (int) $quantity_id ) );
				if ( $change_product_qty_res->success ) {
					$odooApi->addLog( '[Product Sync] [Success] [Odoo Product Id ' . print_r( $product_id, 1 ) . ' successfully updated quantity]' );
				} else {
					$odooApi->addLog( '[Product Sync] [Error] [Odoo Product Id ' . print_r( $product_id, 1 ) . ' error in updating quantity]' );
					$odooApi->addLog( '[Product Export] [Error] [Error : ' . print_r( $change_product_qty_res->message, 1 ) . ']' );
				}
			} else {
				$odooApi->addLog( '[Product Export] [Error] [Odoo Product Id ' . print_r( $product_id, 1 ) . ' error in inserting quantity]' );
				$odooApi->addLog( '[Product Export] [Error] [Error : ' . print_r( $quantity_id->message, 1 ) . ']' );
			}
			// }
		}
			
		public function import_product_odoo() {
			if ( $this->import_products->is_process_running() ) {
				echo json_encode(
					array(
						'status'  => 0,
						'message' => __(
							'Product import is already running.', 'wc-odoo-integration' ),
					) );
				die;
			}
				
			$odooApi = new WC_ODOO_API();
				
			$moduleCondition = array(
				array(
					'field_key'   => 'application',
					'field_value' => true,
				),
				array(
					'field_key'   => 'state',
					'field_value' => 'installed',
				),
			);
				
			$installed_modules = $odooApi->readAll( 'ir.module.module', array(
				'name',
				'state',
			), $moduleCondition );
			$installed_modules = json_decode( json_encode( $installed_modules->data->items ), true );
			// $odooApi->addLog('installed Modules : '. print_r($installed_modules, true));
			$isPoSModuleInstalled = false;
			foreach ( $installed_modules as $key => $installed_module ) {
				if ( 'point_of_sale' == $installed_module[ 'name' ] ) {
					$isPoSModuleInstalled = true;
				}
			}
			$conditions = array(
				array(
					'field_key'   => 'sale_ok',
					'field_value' => true,
				),
				array(
					'field_key'   => 'product_variant_count',
					'field_value' => (int) 1,
				),
			);
				
			if ( 'yes' == $this->odoo_settings[ 'odoo_import_pos_product' ] && $isPoSModuleInstalled ) {
				$conditions[] = array(
					'field_key'   => 'available_in_pos',
					'field_value' => false,
				);
			}
				
			$exclude_products_cats = $this->odoo_settings[ 'odoo_import_exclude_product_category' ];
			if ( '' != $exclude_products_cats ) {
				$conditions[] = array(
					'field_key'   => 'categ_id',
					'field_value' => array_map( 'intval', $exclude_products_cats ),
					'operator'    => 'not in',
				);
			}
				
			// $odooApi->addLog( 'total Product count conditions : ' . var_export( $conditions, 1 ) );
			$total_products_count = $odooApi->search_count( 'product.template', $conditions );
			// $odooApi->addLog( 'total Product count : ' . print_r( $total_products_count, 1 ) );
			$batch_size  = 200;
			$batch_count = ceil( $total_products_count / $batch_size );
				
			if ( $total_products_count ) {
				for ( $i = 0 ; $i < $batch_count ; $i++ ) {
					$offset = $i * $batch_size;
						
					$products = $odooApi->search(
						'product.template', $conditions, array(
						'offset' => $offset,
						'limit'  => $batch_size,
					) );
						
					if ( $products->success ) {
						$products = json_decode( json_encode( $products->data->items ), true );
					} else {
						$error_msg = '[Product Sync] [Error] [Error for Search Products => Msg : ' . print_r( $products->message, true ) . ']';
						$odooApi->addLog( $error_msg );
					}
						
					if ( is_array( $products ) && count( $products ) > 0 ) {
						$batch_items = array();
						foreach ( $products as $tkey => $product_id ) {
							$this->import_products->push_to_queue( $product_id );
						}
						$this->import_products->save();
					}
				}
					
				update_option( 'opmc_odoo_product_import_count', $total_products_count );
				update_option( 'opmc_odoo_product_remaining_import_count', (int) $total_products_count );
				update_option( 'opmc_odoo_product_import_running', true );
				$odooApi->addLog( '[Products Import] [Start] [Products import has been started for ' . print_r( $total_products_count, 1 ) . ' Products. ]' );
				
				$this->import_products->dispatch();
			}
				
			echo json_encode(
				array(
					'status'  => 1,
					'message' => __(
									 'Import process has started for ', 'wc-odoo-integration' ) . $total_products_count . __(
									 ' products', 'wc-odoo-integration' ),
				) );
				
			exit;
		}
			
		// not
		public function do_import_products() {
			$odooApi = new WC_ODOO_API();
				
			$moduleCondition = array(
				array(
					'field_key'   => 'application',
					'field_value' => true,
				),
				array(
					'field_key'   => 'state',
					'field_value' => 'installed',
				),
			);
				
			$installed_modules = $odooApi->readAll( 'ir.module.module', array(
				'name',
				'state',
			), $moduleCondition );
			// $odooApi->addLog('installed Modules : '. print_r($installed_modules, true));
			$isPoSModuleInstalled = false;
			foreach ( $installed_modules as $key => $installed_module ) {
				if ( 'point_of_sale' == $installed_module[ 'name' ] ) {
					$isPoSModuleInstalled = true;
				}
			}
			$conditions = array(
				array( 'sale_ok', '=', '1' ),
				array( 'product_variant_count', '=', '1' ),
			);
				
			if ( 'yes' == $this->odoo_settings[ 'odoo_import_pos_product' ] && $isPoSModuleInstalled ) {
				$conditions[] = array( 'available_in_pos', '=', false );
			}
				
			// $odooApi->addLog('product Fileds found : '. print_r($conditions, true));
			$templates = $odooApi->readAll( 'product.template', array(), $conditions );
			$attr_v    = array();
				
			if ( ! isset( $templates[ 'fail' ] ) && is_array( $templates ) && count( $templates ) > 0 ) {
				foreach ( $templates as $tkey => $template ) {
					if ( $template[ 'product_variant_count' ] > 1 ) {
						continue;
						// if ($template['id'] != 32) {
						if ( count( $attr_v ) == 0 ) {
							$attr_values = $odooApi->readAll( 'product.template.attribute.value', array(
								'name',
								'id',
							) );
							foreach ( $attr_values as $key => $value ) {
								$attr_v[ $value[ 'id' ] ] = $value[ 'name' ];
							}
							$this->odoo_attr_values = $attr_v;
						}
							
						$products = $odooApi->fetch_record_by_ids( 'product.product', $template[ 'product_variant_ids' ], array( 'activity_ids', 'activity_state', 'activity_user_id', 'activity_type_id', 'activity_type_icon', 'activity_date_deadline', 'my_activity_date_deadline', 'activity_summary', 'activity_exception_decoration', 'activity_exception_icon', 'activity_calendar_event_id', 'message_is_follower', 'message_follower_ids', 'message_partner_ids', 'message_ids', 'has_message', 'message_needaction', 'message_needaction_counter', 'message_has_error', 'message_has_error_counter', 'message_attachment_count', 'rating_ids', 'website_message_ids', 'message_has_sms_error', 'price_extra', 'lst_price', 'default_code', 'code', 'partner_ref', 'active', 'product_tmpl_id', 'barcode', 'product_template_attribute_value_ids', 'product_template_variant_value_ids', 'combination_indices', 'is_product_variant', 'standard_price', 'volume', 'weight', 'pricelist_item_count', 'product_document_ids', 'product_document_count', 'packaging_ids', 'additional_product_tag_ids', 'all_product_tag_ids', 'can_image_variant_1024_be_zoomed', 'can_image_1024_be_zoomed', 'write_date', 'id', 'display_name', 'create_uid', 'create_date', 'write_uid', 'tax_string', 'stock_quant_ids', 'stock_move_ids', 'qty_available', 'virtual_available', 'free_qty', 'incoming_qty', 'outgoing_qty', 'orderpoint_ids', 'nbr_moves_in', 'nbr_moves_out', 'nbr_reordering_rules', 'reordering_min_qty', 'reordering_max_qty', 'putaway_rule_ids', 'storage_category_capacity_ids', 'show_on_hand_qty_status_button', 'show_forecasted_qty_status_button', 'valid_ean', 'lot_properties_definition', 'value_svl', 'quantity_svl', 'avg_cost', 'total_value', 'company_currency_id', 'stock_valuation_layer_ids', 'valuation', 'cost_method', 'sales_count', 'product_catalog_product_is_in_sale_order', 'name', 'sequence', 'description', 'description_purchase', 'description_sale', 'detailed_type', 'type', 'categ_id', 'currency_id', 'cost_currency_id', 'list_price', 'volume_uom_name', 'weight_uom_name', 'sale_ok', 'purchase_ok', 'uom_id', 'uom_name', 'uom_po_id', 'company_id', 'seller_ids', 'variant_seller_ids', 'color', 'attribute_line_ids', 'valid_product_template_attribute_line_ids', 'product_variant_ids', 'product_variant_id', 'product_variant_count', 'has_configurable_attributes', 'product_tooltip', 'priority', 'product_tag_ids', 'taxes_id', 'supplier_taxes_id', 'property_account_income_id', 'property_account_expense_id', 'account_tag_ids', 'fiscal_country_codes', 'responsible_id', 'property_stock_production', 'property_stock_inventory', 'sale_delay', 'tracking', 'description_picking', 'description_pickingout', 'description_pickingin', 'location_id', 'warehouse_id', 'has_available_route_ids', 'route_ids', 'route_from_categ_ids', 'service_type', 'sale_line_warn', 'sale_line_warn_msg', 'expense_policy', 'visible_expense_policy', 'invoice_policy', 'optional_product_ids', 'planning_enabled', 'planning_role_id', 'service_tracking', 'project_id', 'project_template_id', 'service_policy' ) );
						$attrs = $odooApi->fetch_record_by_ids( 'product.template.attribute.line', $template[ 'attribute_line_ids' ], array(
							'display_name',
							'id',
							'product_template_value_ids',
						) );
						foreach ( $products as $pkey => $product ) {
							$attr_and_value = array();
							foreach ( $product[ 'product_template_attribute_value_ids' ] as $attr => $attr_value ) {
								foreach ( $attrs as $key => $attr ) {
									foreach ( $attr[ 'product_template_value_ids' ] as $key => $value ) {
										if ( $value == $attr_value ) {
											$attr_and_value[ $attr[ 'display_name' ] ] = $attr_v[ $value ];
										}
									}
								}
								$products[ $pkey ][ 'attr_and_value' ]            = $attr_and_value;
								$products[ $pkey ][ 'attr_value' ][ $attr_value ] = $attr_v[ $attr_value ];
								// $this->create_variation_product($template,$product);
							}
						}
							
						$products[ 'attributes' ] = $attrs;
						$product_id               = $this->sync_product_from_odoo( $template, $products );
					} else {
						$product_id = $this->sync_product_from_odoo( $template );
					}
				}
			}
		}
			
		public function sync_product_from_odoo( $data, $for_order = false, $variations = array() ) {
				
			$odooApi = new WC_ODOO_API();
			// $data = $data[0];
			// $odooApi->addLog('SYnc products : '. print_r($data, true));
				
			$postData = array(
				'post_author'  => 1,
				'post_content' => isset( $data[ 'description_sale' ] ) ? $data[ 'description_sale' ] : '',
				'post_status'  => ( 1 == $data[ 'active' ] ) ? 'publish' : 'draft',
				'post_title'   => isset( $data[ 'name' ] ) ? $data[ 'name' ] : '',
				'post_parent'  => 0,
				'post_type'    => 'product',
				'post_excerpt' => isset( $data[ 'name' ] ) ? $data[ 'name' ] : '',
				
			);
				
			if ( isset( $data[ 'id' ] ) ) {
				// get Post id if record already exists in woocommerce
				$post = $this->get_post_id_by_meta_key_and_value( '_odoo_id', $data[ 'id' ] );
				// $odooApi->addLog( print_r( $data['id'], true ) . ' Product id from meta by ID : ' . print_r( $post, true ) );
				if ( ! $post ) {
					if ( '' != $data[ $this->odoo_sku_mapping ] ) {
						$post = $this->get_post_id_by_meta_key_and_value( '_sku', $data[ $this->odoo_sku_mapping ] );
						// $odooApi->addLog( print_r( $data['id'], true ) . ' Product id from meta by SKU : ' . print_r( $post, true ) );
					}
				}
					
				if ( $post ) {
					if ( ! $for_order && 'no' == $this->odoo_settings[ 'odoo_import_update_product' ] ) {
						return false;
					}
						
					$postData[ 'ID' ] = $post;
					$new_slug         = sanitize_title( $data[ 'name' ] );
					// use this line if you have multiple posts with the same title
					$new_slug                = wp_unique_post_slug( $new_slug, $postData[ 'ID' ], $postData[ 'post_status' ], $postData[ 'post_type' ], 0 );
					$postData[ 'post_name' ] = $data[ 'name' ];
					$post_id                 = wp_update_post( $postData );
				} else {
					$post_id = wp_insert_post( $postData );
				}
					
				// $odooApi->addLog( 'Woo product Id : ' . print_r( $post_id, true ) );
					
				wp_set_object_terms( $post_id, ( ( count( $variations ) > 0 ) ? 'variable' : 'simple' ), 'product_type' );
					
				update_post_meta( $post_id, '_visibility', 'visible' );
				update_post_meta( $post_id, '_description', $data[ 'description_sale' ] );
				update_post_meta( $post_id, '_product_attributes', array() );
				if ( count( $variations ) < 2 ) {
					update_post_meta( $post_id, '_sku', $data[ $this->odoo_sku_mapping ] );
					if ( $for_order || 'yes' == $this->odoo_settings[ 'odoo_import_update_price' ] ) {
						update_post_meta( $post_id, '_regular_price', $data[ 'list_price' ] );
						update_post_meta( $post_id, '_sale_price', $data[ 'list_price' ] );
						update_post_meta( $post_id, '_price', $data[ 'list_price' ] );
							
						// commented by Manish
						// $product = wc_get_product($post_id);
						// $product->set_regular_price( $data['list_price'] );
						// $product->set_sale_price( $data['list_price'] );
						// $product->set_price( $data['list_price'] );
						// $product->save();
							
						if ( isset( $data[ 'pricelist_item_count' ] ) && $data[ 'pricelist_item_count' ] > 0 ) {
							$this->get_and_set_sale_price( $post_id, $data );
						}
					}
				}
					
					
				if (  $for_order || 'yes' == $this->odoo_settings[ 'odoo_import_update_stocks' ] ) {
					$manage_stock_enabled = get_option( 'woocommerce_manage_stock' );
					// $odooApi->addLog( 'manage_stock_enabled : ' . var_export( $manage_stock_enabled, 1 ) );
					if ( 'yes' == $manage_stock_enabled ) {
						update_post_meta( $post_id, '_manage_stock', 'yes' );
						update_post_meta( $post_id, '_stock', $data[ 'qty_available' ] );
						$stock_status = ( $data[ 'qty_available' ] > 0 ) ? 'instock' : 'outofstock';
						update_post_meta( $post_id, '_stock_status', wc_clean( $stock_status ) );
						update_post_meta( $post_id, '_saleunit', $data[ 'uom_name' ] ? $data[ 'uom_name' ] : 'each' );
						update_post_meta( $post_id, '_stockunit', $data[ 'uom_name' ] ? $data[ 'uom_name' ] : 'each' );
						wp_set_post_terms( $post_id, $stock_status, 'product_visibility', true );
					} else {
						$stock_status = ( $data[ 'qty_available' ] > 0 ) ? 'instock' : 'outofstock';
						update_post_meta( $post_id, '_stock_status', wc_clean( $stock_status ) );
					}
				}
					
				// Stock Management Meta Fields
				update_post_meta( $post_id, '_sold_individually', '' );
				update_post_meta( $post_id, '_weight', $data[ 'weight' ] );
				update_post_meta( $post_id, '_cube', $data[ 'volume' ] );
				if ( count( $variations ) > 1 ) {
					update_post_meta( $post_id, '_odoo_id', $data[ 'id' ] );
				} else {
					update_post_meta( $post_id, '_odoo_id', $data[ 'product_variant_id' ][ 0 ] );
				}
				if ( isset( $data[ 'categ_id' ][ 1 ] ) ) {
					$term_id = $this->get_odoo_product_cats( $data[ 'categ_id' ][ 0 ] );
					wp_set_object_terms( $post_id, $term_id, 'product_cat' );
				}
				if (isset($data['product_tag_ids']) && !empty($data['product_tag_ids'])) {
//					$odooApi->addLog('Products tags : '. print_r($data['product_tag_ids'], 1));
					$tags_id = $this->get_odoo_product_tags($data['product_tag_ids']);
					wp_set_object_terms($post_id, $tags_id, 'product_tag');
				}
//                  $odooApi->addLog( 'term ID : ' . print_r( $term_id, 1 ) );
					
				if ( '' != $data[ 'image_1024' ] ) {
					$helper    = WC_ODOO_Helpers::getHelper();
					$attach_id = $helper->save_image( $data );
					set_post_thumbnail( $post_id, $attach_id );
				}
					
				if ( count( $variations ) > 1 ) {
					$this->createProductVariations( $post_id, $variations );
				}
				update_post_meta( $post_id, '_synced_data_rec', 'synced' );
				update_post_meta( $post_id, '_synced_last_date_rec', gmdate( 'Y-m-d' ) );
				
				return $post_id;
			}
		}
			
		/**
		 * Fetches a product category from Odoo by ID and creates it in WooCommerce.
		 * If the category has a parent, it will recursively create the parent categories first.
		 *
		 * @param int $cat_id The ID of the category to fetch from Odoo.
		 *
		 * @return object The created WooCommerce category.
		 */
		public function get_odoo_product_cats( $cat_id ) {
			$odooApi  = new WC_ODOO_API();
			$category = $odooApi->fetch_record_by_id( 'product.category', array( $cat_id ), array(
				'id',
				'name',
				'complete_name',
				'parent_id',
			) );
			if ( ! isset( $category[ 0 ][ 'parent_id' ][ 0 ] ) ) {
				// This category has no parent, create it and return
				return $this->create_wc_category( $category[ 0 ] );
			} else {
				// This category has a parent, let's get recursive
				$parent_cat_id = $category[ 0 ][ 'parent_id' ][ 0 ];
					
				// Create the parent category first
				$parent_wc_cat = $this->get_odoo_product_cats( $parent_cat_id );
					
				// Now create the child category with the parent category
				$child_wc_cat = $this->create_wc_category( $category[ 0 ], $parent_wc_cat );
					
				return $child_wc_cat;
			}
		}
		
		/**
		 * Get Odoo product tags and create them in WooCommerce if they don't exist.
		 *
		 * @param array $tag_ids An array of Odoo tag IDs.
		 * @return array An array of WooCommerce tag IDs.
		 */
		public function get_odoo_product_tags( $tag_ids) {
			// Initialize the Odoo API.
			$odooApi = new WC_ODOO_API();
			
			// Initialize an array for the WooCommerce tag IDs.
			$woo_tag_ids = array();
			
			// Fetch the Odoo tags by their IDs.
			$odoo_tags = $odooApi->fetch_record_by_id('product.tag', $tag_ids, array('name', 'display_name'));
			
			// If Odoo tags are found...
			if (!empty($odoo_tags)) {
				// Loop through each Odoo tag.
				foreach ($odoo_tags as $odoo_tag) {
					// Find or create the tag in WooCommerce.
					$woo_tag = $this->find_or_create_tag($odoo_tag['name']);
					
					// Add the WooCommerce tag ID to the array.
					$woo_tag_ids[] = $woo_tag;
					
					// Update the term meta with the Odoo tag ID.
					update_term_meta($woo_tag, '_opmc_odoo_tag_id', $odoo_tag['id']);
				}
			}
			
			// Return the array of WooCommerce tag IDs.
			return $woo_tag_ids;
		}
		
		/**
		 * Find a WooCommerce product tag by its name, or create it if it doesn't exist.
		 *
		 * @param string $tag_name The name of the tag.
		 * @return int The ID of the tag.
		 * @throws Exception If the tag cannot be created.
		 */
		public function find_or_create_tag( $tag_name) {
			// Try to get the term by its slug.
			$term = get_term_by('slug', str_replace(' ', '-', $tag_name), 'product_tag');
			
			// If the term doesn't exist...
			if (false === $term) {
				// Create a new term.
				$term_info = wp_insert_term($tag_name, 'product_tag');
				
				// If there was an error creating the term, throw an exception.
				if (is_wp_error($term_info)) {
					throw new Exception('Unable to create tag: ' . $term_info->get_error_message());
				}
				
				// Get the ID of the new term.
				$term_id = $term_info['term_id'];
			} else {
				// If the term exists, get its ID.
				$term_id = $term->term_id;
			}
			
			// Return the term ID.
			return $term_id;
		}
			
		/**
		 * Check if a WooCommerce attribute exists.
		 *
		 * This function checks if a given attribute name is already registered in WooCommerce.
		 * It loops through all registered attribute taxonomies and compares the attribute_name property to the given name.
		 * If it finds a match, it returns true. If it doesn't, it returns false.
		 *
		 * @param string $name The name of the attribute to check.
		 *
		 * @return bool True if the attribute exists, false otherwise.
		 */
		public function attribute_exists( $name ) {
			// Get all registered attribute taxonomies.
			$attribute_taxonomies = wc_get_attribute_taxonomies();
				
			// Loop through the taxonomies.
			foreach ( $attribute_taxonomies as $tax ) {
				// If the attribute_name property matches the given name, return true.
				if ( $tax->attribute_name == $name ) {
					return true;
				}
			}
				
			// If we've gone through all the taxonomies and haven't found a match, return false.
			return false;
		}
			
		/**
		 * Creating attributes and setting it touse for variation of products
		 *
		 * @param  [int]   $post_id [product id]
		 * @param  [array] $variations [array fo variations]
		 *
		 * @return  NULL
		 */
		public function createProductVariations( $post_id, $variations ) {
			$odooApi = new WC_ODOO_API();
				
			$attr = array();
			foreach ( $variations[ 'attributes' ] as $key => $attribute ) {
				$options = $this->setVariationAttributes( $attribute[ 'product_template_value_ids' ] );
				$name    = wc_attribute_taxonomy_name( $attribute[ 'display_name' ] );
					
				if ( ! $this->attribute_exists( $name ) ) {
					// Register the attribute with WooCommerce
					wc_create_attribute( array(
											 'name'         => ucfirst( $attribute[ 'display_name' ] ),
											 'slug'         => sanitize_title( $name ),
											 'type'         => 'select',
											 'order_by'     => 'menu_order',
											 'has_archives' => false,
										 ) );
				}
					
				// Convert options to term IDs
				$options_array = explode( WC_DELIMITER, $options );
				foreach ( $options_array as $term_name ) {
					if ( ! term_exists( $term_name, $name ) ) {
						// Register the term with the attribute taxonomy
						wp_insert_term( $term_name, $name );
					}
				}
					
				$attribute = new WC_Product_Attribute();
				$attribute->set_name( $name );
				$attribute->set_options( $options_array );
				$attribute->set_visible( true );
				$attribute->set_variation( true );
				$attribute->set_position( false );
				$attribute->set_id( false );
				$attr[ $name ] = $attribute;
			}
				
			$product = new WC_Product_Variable( $post_id );
			$product->set_attributes( $attr );
			$product->save();
				
			$variations_data = $this->createVariationData( $variations );
			// $odooApi->addLog('variation_data '. print_r($variations_data, true));
				
			foreach ( $variations_data as $variation_key => $variation_data ) {
				$variation    = '';
				$variation_id = $this->ifVariationExists( $variation_data, $post_id );
				if ( $this->ifVariationExists( $variation_data, $post_id ) !== false ) {
					$variation_id = $this->ifVariationExists( $variation_data, $post_id );
					if ( is_wp_error( $variation_id ) ) {
						return false;
					}
					$variation = new WC_Product_Variation( $variation_id );
				} else {
					$variation = new WC_Product_Variation();
					$variation->set_sku( $variation_data[ 'sku' ] );
				}
				$variation->set_parent_id( $post_id );
				$variation->set_status( ( 1 == $variation_data[ 'isonline' ] ) ? 'publish' : 'private' );
				if ( 'yes' == $this->odoo_settings[ 'odoo_import_update_stocks' ] ) {
					$variation->set_manage_stock( true );
					$variation->set_stock_quantity( $variation_data[ 'stock_qty' ] );
				}
					
				$variation->set_weight( $variation_data[ 'weight' ] );
				$variation->set_description( $variation_data[ 'desc' ] );
				$variation->save();
					
				if ( 'yes' == $this->odoo_settings[ 'odoo_import_update_price' ] ) {
					if ( $variation_data[ 'regular_price' ] > 1 ) {
						update_post_meta( $variation->get_id(), '_price', $variation_data[ 'regular_price' ] );
						update_post_meta( $variation->get_id(), '_regular_price', $variation_data[ 'regular_price' ] );
						if ( $variation_data[ 'pricelist_item_count' ] ) {
							$this->get_and_set_sale_price( $variation->get_id(), $variation_data, true );
						}
					} else {
						$this->get_and_set_variant_price( $variation->get_id(), $variation_data );
					}
				}
				// set variation thumbnail
				// if(isset($variation_data['image']) && !empty($variation_data['image'])){
				// $parts = parse_url($variation_data['image']);
				// parse_str($parts['query'], $query);
				// $attachment_id = get_post_id_by_meta_key_and_value('ns_image_id',$query['id']);
				// set_post_thumbnail( $variation->get_id(), $attachment_id );
				// }
					
				foreach ( $variation_data[ 'attr_and_value' ] as $key => $value ) {
					update_post_meta( $variation->get_id(), '_odoo_variation_id', $variation_data[ 'id' ] );
					update_post_meta( $variation->get_id(), 'attribute_' . wc_attribute_taxonomy_name( $key ), $value );
				}
			}
				
			return;
		}
			
		public function ifVariationExists( $variation_data, $parent_id ) {
			global $wpdb;
			if ( '' != $variation_data[ 'sku' ] ) {
				$result = $wpdb->get_results( $wpdb->prepare( "SELECT post_id FROM $wpdb->postmeta WHERE meta_key = '_sku' AND meta_value = %s LIMIT %d , %d", $variation_data[ 'sku' ], 0, 30 ) );
				if ( $result ) {
					return $result[ 0 ]->post_id;
				}
			}
				
			return false;
		}
			
		public function setVariationAttributes( $variations ) {
			$j      = 1;
			$option = '';
			foreach ( $variations as $key => $value ) {
				if ( $j < count( $variations ) ) {
					$option .= $this->odoo_attr_values[ $value ] . '|';
				} else {
					$option .= $this->odoo_attr_values[ $value ];
				}
				$j++;
			}
				
			return $option;
		}
			
		public function createVariationData( $variations ) {
			$odooApi = new WC_ODOO_API();
				
			$variation_data = array();
			$att            = array();
			foreach ( $variations[ 'attributes' ] as $key => $value ) {
				$att[] = $value[ 'display_name' ];
			}
			$attr_list = array();
			foreach ( $variations[ 'attributes' ] as $key => $attribute ) {
				foreach ( $attribute[ 'product_template_value_ids' ] as $key => $attr_value ) {
					$attr_list[ $attribute[ 'display_name' ] ][] = $this->odoo_attr_values[ $attr_value ];
				}
			}
			unset( $variations[ 'attributes' ] );
				
			foreach ( $variations as $key => $value ) {
				// $odooApi->addLog('varable value : '. print_r($value, 1));
					
				$variation_data[] = array(
					'id'                   => $value[ 'id' ],
					'sku'                  => $value[ $this->odoo_sku_mapping ],
					'regular_price'        => $value[ 'lst_price' ],
					'sale_price'           => '',
					'stock_qty'            => $value[ 'qty_available' ], // 'image'             => $value->image,
					'isonline'             => $value[ 'active' ],
					'weight'               => $value[ 'weight' ],
					'weightunit'           => $value[ 'weight_uom_name' ],
					'odoo_id'              => $value[ 'id' ],
					'desc'                 => $value[ 'description_sale' ],
					'attr_and_value'       => $value[ 'attr_and_value' ],
					'pricelist_item_count' => $value[ 'pricelist_item_count' ],
				);
			}
				
			return $variation_data;
		}
			
		public function create_and_set_category( $post_id, $category_str ) {
				
			$categories    = explode( '/', $category_str );
			$taxonomy      = 'product_cat';
			$previous_slug = '';
			$last_key      = count( $categories ) - 1;
			$first_key     = $categories[ 0 ];
				
			foreach ( $categories as $key => $category ) {
				if ( $last_key == $key ) {
					$current_slug = sanitize_title( $category );
					$slug         = ! empty( $previous_slug ) ? $previous_slug . '_' . $current_slug : $current_slug;
					$term         = get_term_by( 'slug', $slug, $taxonomy );
						
					if ( isset( $term ) && isset( $term->term_id ) ) {
						$termid = $term->term_id;
					} else {
						$term = wp_insert_term(
							$category, $taxonomy, array(
							'description' => 'Description for category',
							'parent'      => 0,
							'slug'        => $slug,
						) );
					}
				}
			}
		}
			
		public function do_import_categories() {
				
			$odooApi    = new WC_ODOO_API();
			$categories = $odooApi->readAll( 'product.category', array(
				'id',
				'name',
				'complete_name',
				'child_id',
				'parent_id',
			) );
			$new_cats   = array();
			if ( $categories->success ) {
				$categories = json_decode( json_encode( $categories->data->items ), true );
					
				// $odooApi->addLog('categories from odoo : '. print_r($categories, true));
					
				if ( count( $categories ) > 0 ) {
					foreach ( $categories as $key => $category ) {
						if ( count( $category[ 'child_id' ] ) > 0 ) {
							foreach ( $category[ 'child_id' ] as $child ) {
								$childkey                         = array_search( $child, array_column( $categories, 'id' ) );
								$categories[ $key ][ 'childs' ][] = $categories[ $childkey ];
							}
						}
						$new_cats[ $category[ 'id' ] ] = $categories[ $key ];
					}
					ksort( $new_cats );
					foreach ( $new_cats as $key => $cat ) {
						$this->create_wc_product_category( $cat );
					}
				}
			}
		}
			
		public function create_wc_product_category( $category ) {
			$cat_id = $this->create_wc_category( $category );
			if ( false != $cat_id && isset( $category[ 'childs' ] ) && count( $category[ 'childs' ] ) > 0 ) {
				foreach ( $category[ 'childs' ] as $key => $child_cat ) {
					$this->create_wc_category( $child_cat, $cat_id );
				}
			}
		}
			
		public function create_wc_category( $category, $parent_cat = 0 ) {
			$termid   = false;
			$taxonomy = 'product_cat';
			$slug     = sanitize_title( $category[ 'complete_name' ] );
			$term     = get_term_by( 'slug', $slug, $taxonomy );
			// $name = isset( $category['name'] ) ? $category['name'] : $category['complete_name'];
			$name = isset( $category[ 'name' ] ) ? $category[ 'name' ] : ( isset( $category[ 'complete_name' ] ) ? $category[ 'complete_name' ] : '' );
			if ( isset( $term ) && isset( $term->term_id ) ) {
				$termid = $term->term_id;
			} else {
				$term = wp_insert_term(
					$name, $taxonomy, array(
					'description' => $category[ 'complete_name' ],
					'parent'      => $parent_cat,
					'slug'        => $slug,
				) );
					
				if ( is_wp_error( $term ) ) {
					return false;
				}
				if ( isset( $term[ 'term_id' ] ) ) {
					$termid = $term[ 'term_id' ];
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
				
			$all_categories = get_categories( $args );
			$categories     = json_decode( json_encode( $all_categories ), true );
			// $odooApi = new WC_ODOO_API();
				
			foreach ( $categories as $key => $cat ) {
				if ( 0 != $cat[ 'parent' ] ) {
					$parent_key          = array_search( $cat[ 'parent' ], array_column( $categories, 'term_id' ) );
					$cat[ 'parent_cat' ] = $categories[ $parent_key ];
				}
				$cat_id   = $cat[ 'cat_ID' ];
				$response = $this->create_category_to_odoo( $cat );
			}
		}
			
		public function create_category_to_odoo( $category ) {
			$odooApi = new WC_ODOO_API();
			$odooApi->addLog( 'categories : ' . print_r( $category, 1 ) );
				
			$odoo_term_id = false;
			$cat_id       = $category[ 'cat_ID' ];
			$odoo_term_id = get_term_meta( $cat_id, '_odoo_term_id', true );
				
			if ( $odoo_term_id ) {
				return $odoo_term_id;
			} else {
				$odooApi = new WC_ODOO_API();
				// $odooApi->addLog('categories name : '. print_r($category,1));
				$conditions   = array(
					array(
						'field_key'   => 'name',
						'field_value' => $category[ 'name' ],
					),
				);
				$odoo_term_id = $odooApi->search_record( 'product.category', $conditions );
				if ( $odoo_term_id->success ) {
					if ( 0 != count( $odoo_term_id->data->items ) ) {
						$odoo_term_id = $odoo_term_id->data->items[ 0 ];
					} else {
						$odoo_term_id = false;
					}
				} else {
					$error_msg = '[Category Sync] [Error] [Error for Search Category => ' . $cat_id . ' Msg : ' . print_r( $odoo_term_id->message, true ) . ']';
					$odooApi->addLog( $error_msg );
						
					return false;
				}
					
				if ( $odoo_term_id ) {
					return $odoo_term_id;
				} else {
					$data = array(
						'name' => $category[ 'name' ],
					);
					if ( isset( $category[ 'parent_cat' ] ) ) {
						$response = $this->get_parent_category( $category[ 'parent_cat' ] );
						if ( $response ) {
							update_term_meta( $category[ 'parent_cat' ][ 'cat_ID' ], '_odoo_term_id', $response );
							// return $response;
							$data[ 'parent_id' ] = (int) $response;
						} else {
							$error_msg = '[Category Sync] [Error] [Error for Creating  Parent category for Id  => ' . $cat_id . ' Msg : ' . print_r( $response->message, true ) . ']';
							$odooApi->addLog( $error_msg );
								
							return $response;
						}
					}
					$response = $odooApi->create_record( 'product.category', $data );
					if ( $response->success ) {
						update_term_meta( $cat_id, '_odoo_term_id', $response->data->odoo_id );
							
						return $response->data->odoo_id;
					} else {
						$error_msg = '[Customer Sync] [Error] [Error for Creating category for Id  => ' . $cat_id . ' Msg : ' . print_r( $response->message, true ) . ']';
						$odooApi->addLog( $error_msg );
					}
				}
			}
		}
			
		public function get_parent_category( $category ) {
			$odooApi = new WC_ODOO_API();
			// $odooApi->addLog( 'parent category : ' . print_r( $category, 1 ) );
			$cat_id       = $category[ 'cat_ID' ];
			$odoo_term_id = get_term_meta( $cat_id, '_odoo_term_id', true );
				
			if ( $odoo_term_id ) {
				return $odoo_term_id;
			} else {
				$conditions   = array(
					array(
						'field_key'   => 'name',
						'field_value' => $category[ 'name' ],
					),
				);
				$odoo_term_id = $odooApi->search_record( 'product.category', $conditions );
				if ( $odoo_term_id->success ) {
					if ( 0 != count( $odoo_term_id->data->items ) ) {
						$odoo_term_id = $odoo_term_id->data->items[ 0 ];
					} else {
						$odoo_term_id = false;
					}
				} else {
					$error_msg = '[Category Sync] [Error] [Error for Search Category => ' . $cat_id . ' Msg : ' . print_r( $odoo_term_id->message, true ) . ']';
					$odooApi->addLog( $error_msg );
						
					return false;
				}
				if ( $odoo_term_id ) {
					return $odoo_term_id;
				} else {
					$data = array(
						'name' => $category[ 'name' ],
					);
					if ( isset( $category[ 'parent_cat' ] ) ) {
						$response = $this->get_parent_category( $category[ 'parent_cat' ] );
						if ( $response->success ) {
							update_term_meta( $cat_id, '_odoo_term_id', $response->data->odoo_id );
							// return $response;
							$data[ 'parent_id' ] = $response->data->odoo_id;
								
							return $response->data->odoo_id;
						} else {
							$error_msg = '[Customer Sync] [Error] [Error for Creating category for Id  => ' . $cat_id . ' Msg : ' . print_r( $response->message, true ) . ']';
							$odooApi->addLog( $error_msg );
								
							return $response;
						}
					}
					$response = $odooApi->create_record( 'product.category', $data );
					if ( $response->success ) {
						return $response->data->odoo_id;
					} else {
						return false;
					}
				}
			}
		}
			
		public function do_export_attributes() {
			$attribute_taxonomies = wc_get_attribute_taxonomies();
			$taxonomy_terms       = array();
			if ( $attribute_taxonomies ) {
				foreach ( $attribute_taxonomies as $tax ) {
					if ( taxonomy_exists( wc_attribute_taxonomy_name( $tax->attribute_name ) ) ) {
						$taxonomy_terms[ $tax->attribute_name ] = get_terms( array(
																				 'taxonomy'   => wc_attribute_taxonomy_name( $tax->attribute_name ),
																				 'orderby'    => 'name',
																				 'hide_empty' => false,
																			 ) );
					};
					$taxonomy_terms[ $tax->attribute_name ][ 'attr' ] = $tax;
				};
			};
				
			foreach ( $taxonomy_terms as $key => $taxonomy_term ) {
				$attr_id = $this->create_attributes_to_odoo( $taxonomy_term );
				unset( $taxonomy_term[ 'attr' ] );
				if ( false != $attr_id && $attr_id > 0 ) {
					foreach ( $taxonomy_term as $taxonomy_value ) {
						$attr_value = $this->create_attributes_value_to_odoo( $attr_id, $taxonomy_value );
					}
				}
			}
		}
			
		public function odoo_product_attributes_id( $term ) {
			$odooApi = new WC_ODOO_API();
			//          $odooApi->addLog('Term : '. print_r($term, 1));
			if ( is_string( $term ) ) {
				$attr_name = $term;
				$attr_type = 'select';
				$attr_id   = $term;
			} else {
				$attribute = $term[ 'attr' ];
				$attr_name = $attribute->attribute_name;
				$attr_type = $attribute->attribute_type;
				$attr_id   = $attribute->attribute_id;
				unset( $term );
			}
			//          $odooApi->addLog('Attr Name : '. print_r($attr_name, 1));
			$conditions   = array(
				array(
					'field_key'   => 'name',
					'operator'    => 'in',
					'field_value' => array(
						$attr_name,
						ucfirst( $attr_name ),
						strtolower( $attr_name ),
						strtoupper( $attr_name ),
					),
				),
			);
			$odoo_attr_id = $odooApi->search_record( 'product.attribute', $conditions );
			//          $odooApi->addLog('new Odoo Attr Id : '. print_r($odoo_attr_id, 1));
			if ( $odoo_attr_id->success ) {
				$odoo_attr_id = $odoo_attr_id->data->items[ 0 ];
			} else {
				$error_msg = '[Attributes Sync] [Error] [Error for Search attributes => ' . $attr_id . ' Msg : ' . preg_replace( "/\r|\n/", ' ', print_r( $odoo_attr_id->message, true ) ) . ']';
				$odooApi->addLog( $error_msg );
					
				return false;
			}
				
			if ( $odoo_attr_id ) {
				return $odoo_attr_id;
			} else {
				$data         = array(
					'name'           => $attr_name,
					'display_type'   => $attr_type,
					'create_variant' => 'always',
				);
				$odoo_attr_id = $odooApi->create_record( 'product.attribute', $data );
				if ( $odoo_attr_id->success ) {
					$odoo_attr_id = $odoo_attr_id->data->odoo_id;
				} else {
					$error_msg = '[Attributes Sync] [Error] [Error for Search attributes => ' . $attr_id . ' Msg : ' . print_r( $odoo_attr_id->message, true ) . ']';
					$odooApi->addLog( $error_msg );
						
					return false;
				}
					
				return $odoo_attr_id;
			}
		}
			
		public function create_attributes_to_odoo( $term ) {
				
			$odooApi = new WC_ODOO_API();
			if ( is_string( $term ) ) {
				$attr_name = $term;
				$attr_type = 'select';
				$attr_id   = $term;
			} else {
				$attribute = $term[ 'attr' ];
				$attr_name = $attribute->attribute_name;
				$attr_type = $attribute->attribute_type;
				$attr_id   = $attribute->attribute_id;
				unset( $term );
			}
			$odooApi->addLog( 'attr name : ' . print_r( $attr_name, 1 ) );
			$conditions   = array(
				array(
					'field_key'   => 'name',
					'operator'    => 'in',
					'field_value' => array(
						$attr_name,
						ucfirst( $attr_name ),
						strtolower( $attr_name ),
						strtoupper( $attr_name ),
					),
				),
			);
			$odoo_attr_id = $odooApi->search_record( 'product.attribute', $conditions );
			if ( $odoo_attr_id->success ) {
				$odoo_attr_id = $odoo_attr_id->data->items[ 0 ];
			} else {
				$error_msg = '[Attributes Sync] [Error] [Error for Search attributes => ' . $attr_id . ' Msg : ' . print_r( $odoo_attr_id->message, true ) . ']';
				$odooApi->addLog( $error_msg );
					
				return false;
			}
				
			if ( $odoo_attr_id ) {
				return $odoo_attr_id;
			} else {
				$data         = array(
					'name'           => $attr_name,
					'display_type'   => $attr_type,
					'create_variant' => 'always',
				);
				$odoo_attr_id = $odooApi->create_record( 'product.attribute', $data );
				if ( $odoo_attr_id->success ) {
					$odoo_attr_id = $odoo_attr_id->data->odoo_id;
				} else {
					$error_msg = '[Attributes Sync] [Error] [Error for Search attributes => ' . $attr_id . ' Msg : ' . print_r( $odoo_attr_id->message, true ) . ']';
					$odooApi->addLog( $error_msg );
						
					return false;
				}
					
				return $odoo_attr_id;
			}
		}
			
		public function odoo_attributes_value_id( $attr_id, $attr_value ) {
			$odooApi = new WC_ODOO_API();
			if ( is_string( $attr_value ) ) {
				$value_name = $attr_value;
			} else {
				$value_name = $attr_value->name;
			}
				
			$data               = array(
				'name'         => $value_name,
				'attribute_id' => $attr_id,
			);
			$odoo_attr_value_id = $odooApi->create_record( 'product.attribute.value', $data );
			if ( $odoo_attr_value_id->success ) {
				$odoo_attr_value_id = $odoo_attr_value_id->data->odoo_id;
			} else {
				$error_msg = '[Attributes Sync] [Error] [Error for Creating attributes value => ' . $value_name . ' Msg : ' . print_r( $odoo_attr_value_id->message, true ) . ']';
				$odooApi->addLog( $error_msg );
					
				return false;
			}
				
			return $odoo_attr_value_id;
		}
			
		public function create_attributes_value_to_odoo( $attr_id, $attr_value ) {
			$odooApi = new WC_ODOO_API();
			if ( is_string( $attr_value ) ) {
				$value_name = $attr_value;
			} else {
				$value_name = $attr_value->name;
			}
			$conditions         = array(
				array(
					'field_key'   => 'name',
					'operator'    => '=',
					'field_value' => $value_name,
				),
				array(
					'field_key'   => 'attribute_id',
					'operator'    => '=',
					'field_value' => $attr_id,
				),
			);
			$odoo_attr_value_id = $odooApi->search_record( 'product.attribute.value', $conditions );
			if ( $odoo_attr_value_id->success ) {
				if ( ! empty( $odoo_attr_value_id->data->items ) ) {
					$odoo_attr_value_id = $odoo_attr_value_id->data->items[ 0 ];
				} else {
					$odoo_attr_value_id = false;
				}
			} else {
				$error_msg = '[Attributes Sync] [Error] [Error for Search attributes value => ' . $value_name . ' Msg : ' . print_r( $odoo_attr_value_id->message, true ) . ']';
				$odooApi->addLog( $error_msg );
					
				return false;
			}
				
			if ( $odoo_attr_value_id ) {
				return $odoo_attr_value_id;
			} else {
				$data               = array(
					'name'         => $value_name,
					'attribute_id' => $attr_id,
				);
				$odoo_attr_value_id = $odooApi->create_record( 'product.attribute.value', $data );
				//               $odooApi->addLog( 'attr value1111 : ' . print_r( $odoo_attr_value_id, 1 ) );
				if ( $odoo_attr_value_id->success ) {
					$odoo_attr_value_id = $odoo_attr_value_id->data->odoo_id;
				} else {
					$error_msg = '[Attributes Sync] [Error] [Error for Creating attributes value => ' . $value_name . ' Msg : ' . print_r( $odoo_attr_value_id->message, true ) . ']';
					$odooApi->addLog( $error_msg );
						
					return false;
				}
					
				return $odoo_attr_value_id;
			}
		}
			
		public function do_import_attributes() {
				
			$odooApi     = new WC_ODOO_API();
			$attrs       = $odooApi->readAll( 'product.attribute', array(
				'id',
				'name',
				'value_ids',
				'display_type',
			) );
			$attr_values = $odooApi->readAll( 'product.attribute.value', array( 'id', 'name', 'display_type' ) );
			// $odooApi->addLog( 'products atributes : ' . print_r( $attrs, true ) );
			// $odooApi->addLog( 'products atributes Values : ' . print_r( $attr_values, true ) );
			if ( $attrs->success ) {
				$attrs = json_decode( json_encode( $attrs->data->items ), true );
				if ( count( $attrs ) > 0 && $attr_values->success ) {
					$attr_values = json_decode( json_encode( $attr_values->data->items ), true );
				}
			} else {
				return false;
			}
			foreach ( $attrs as $attr ) {
				$attr_id = $this->create_attribute_to_wc( $attr );
				update_term_meta( $attr_id, '_odoo_attr_id', $attr[ 'id' ] );
				if ( $attr_id ) {
					$attribute = wc_get_attribute( $attr_id );
					foreach ( $attr[ 'value_ids' ] as $attr_term ) {
						$term_key = array_search( $attr_term, array_column( $attr_values, 'id' ) );
						if ( isset( $attr_values[ $term_key ] ) ) {
							$attr_value_id = $this->create_attribute_value_to_wc( $attribute, $attr_values[ $term_key ] );
							if ( false != $attr_value_id ) {
								update_term_meta( $attr_value_id, '_odoo_attr_id', $attr_term );
							}
						}
					}
				}
			}
		}
			
		public function create_attribute_to_wc( $attr ) {
				
			global $wc_product_attributes;
			$raw_name = $attr[ 'name' ];
			// Make sure caches are clean.
			delete_transient( 'wc_attribute_taxonomies' );
			WC_Cache_Helper::incr_cache_prefix( 'woocommerce-attributes' );
				
			// These are exported as labels, so convert the label to a name if possible first.
			$attribute_labels = wp_list_pluck( wc_get_attribute_taxonomies(), 'attribute_label', 'attribute_name' );
			$attribute_name   = array_search( $raw_name, $attribute_labels, true );
				
			if ( ! $attribute_name ) {
				$attribute_name = wc_sanitize_taxonomy_name( $raw_name );
			}
				
			$attribute_id = wc_attribute_taxonomy_id_by_name( $attribute_name );
				
			if ( ! $attribute_id ) {
				$taxonomy_name = wc_attribute_taxonomy_name( $attribute_name );
					
				// Degister taxonomy which other tests may have created...
				unregister_taxonomy( $taxonomy_name );
					
				$attribute_id = wc_create_attribute(
					array(
						'name'         => $raw_name,
						'slug'         => $attribute_name,
						'type'         => $attr[ 'display_type' ],
						'order_by'     => 'menu_order',
						'has_archives' => 0,
					) );
					
				// Register as taxonomy.
				register_taxonomy(
					$taxonomy_name,
					/**
					 * Object type with which the taxonomy should be associated.
					 *
					 * @since  1.3.4
					 */
					apply_filters( 'woocommerce_taxonomy_objects_' . $taxonomy_name, array( 'product' ) ),
					/**
					 * Array of arguments for registering taxonomy
					 *
					 * @since  1.3.4
					 */
					apply_filters(
						'woocommerce_taxonomy_args_' . $taxonomy_name, array(
						'labels'       => array(
							'name' => $raw_name,
						),
						'hierarchical' => false,
						'show_ui'      => false,
						'query_var'    => true,
						'rewrite'      => false,
					) ) );
					
				// Set product attributes global.
				$wc_product_attributes = array();
					
				foreach ( wc_get_attribute_taxonomies() as $taxonomy ) {
					$wc_product_attributes[ wc_attribute_taxonomy_name( $taxonomy->attribute_name ) ] = $taxonomy;
				}
			}
				
			if ( $attribute_id ) {
				return $attribute_id;
			}
		}
			
		public function create_attribute_value_to_wc( $attribute, $term ) {
			$result = term_exists( $term[ 'name' ], $attribute->slug );
			if ( ! $result ) {
				$result = wp_insert_term( $term[ 'name' ], $attribute->slug );
				if ( is_wp_error( $result ) ) {
					return false;
				}
				$term_id = $result[ 'term_id' ];
			} else {
				$term_id = $result[ 'term_id' ];
			}
				
			return $term_id;
		}
			
		public function do_export_product_odoo() {
			if ( $this->export_products->is_process_running() ) {
				echo json_encode(
					array(
						'status'  => 0,
						'message' => __(
							'Product export is already running.', 'wc-odoo-integration' ),
					) );
				die;
			}
				
			global $wpdb;
			$products = array();
			// $exclude_cats = implode(',', $this->odoo_settings['odoo_exclude_product_category']);
			$odooApi = new WC_ODOO_API();
			// $odooApi->addLog( 'Exclude categories : ' . print_r( $this->odoo_settings['odoo_exclude_product_category'], true ) );
			
			$posttype = 'product';
			$post_status = 'publish';
			if ( 'yes' == $this->odoo_settings['odoo_export_create_product'] ) {
			$products = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT {$wpdb->posts}.`ID`,{$wpdb->posts}.`post_type` FROM {$wpdb->posts} RIGHT JOIN  $wpdb->term_relationships  as t
                        ON ID = t.object_id WHERE (post_type=%s) AND post_status=%s AND post_status=%s" , $posttype, $post_status, $post_status) );

			} elseif ( 'no' == $this->odoo_settings[ 'odoo_export_update_stocks' ] ) {
				$products = $wpdb->get_results(
					$wpdb->prepare(
						"SELECT {$wpdb->posts}.`ID`,{$wpdb->posts}.`post_type` FROM {$wpdb->posts} RIGHT JOIN  $wpdb->term_relationships  as t
                            ON ID = t.object_id WHERE (post_type=%s) AND post_status=%s AND NOT EXISTS (
              SELECT {$wpdb->postmeta}.`post_id` FROM {$wpdb->postmeta}
               WHERE {$wpdb->postmeta}.`meta_key` = '_odoo_id'
                AND {$wpdb->postmeta}.`post_id`={$wpdb->posts}.ID
            ) ", $posttype, $post_status ) );
	
			}

			$this->export_products->empty_data();
				
			// Remove duplicate products ids
			$products = array_values( array_unique( $products, SORT_REGULAR ) );
			// $odooApi->addLog('Products : '. print_r($products, 1));exit();
			$total_products_count = count( $products );
			$batch_size           = 200;
			$last_product_key     = array_key_last( $products );
			update_option( 'opmc_odoo_product_export_count', $total_products_count );
			update_option( 'opmc_odoo_product_export_remaining_count', $total_products_count );
				
			$i = 0;
			foreach ( $products as $key => $product_obj ) {
				$this->export_products->push_to_queue( $product_obj->ID );
				if ( $i >= $batch_size || $key == $last_product_key ) {
					$this->export_products->save();
					$i = 0;
				} else {
					$i++;
				}
			}
				
			$this->export_products->dispatch();
			update_option( 'opmc_odoo_product_export_running', true );
			$odooApi->addLog( '[Products Export] [Start] [Products export has been started for ' . print_r( $total_products_count, 1 ) . ' Products. ]' );
			// $common = new WC_ODOO_Common_Functions();
			// add_action( 'admin_notices', array( $common, 'opmc_update_exp_pro_notices' ) );
			echo json_encode(
				array(
					'status'  => 1,
					'message' => __(
									 'Export process has started for ', 'wc-odoo-integration' ) . $total_products_count . __(
									 ' products', 'wc-odoo-integration' ),
				) );
				
			exit;
		}
			
		public function sync_product_to_odoo( $item ) {
			$odooApi = new WC_ODOO_API();
			$helper  = WC_ODOO_Helpers::getHelper();
			$product = wc_get_product( $item );
			$excluded_item = false;
			
			$terms = get_the_terms( $product->ID, 'product_cat' );
			
			if ( '' != $terms && is_array( $this->odoo_settings[ 'odoo_exclude_product_category' ] ) ) {
				foreach ( $terms as $key => $term ) {
					if ( in_array( $term->term_id, $this->odoo_settings[ 'odoo_exclude_product_category' ] ) ) {
						$excluded_item = true;
					}
				}
			}
			
			$syncable_product = get_post_meta( $product->get_id(), '_exclude_product_to_sync', true );
			
			if ( $excluded_item || 'yes' == $syncable_product ) {
				$odooApi->addLog('[Products Export] [Excluded] [' . print_r($product->get_name(), 1) . ' set to exclude from export. ]');
				return false;
			}
			$manage_stock_enabled = get_option( 'woocommerce_manage_stock' );
				
			// Get additional data
			$product_data = array(
				'id'            => $product->get_id(),
				'name'          => $product->get_name(),
				'type'          => 'product',
				'description'   => $product->get_description(),
				'sku'           => array(
										'key' => $this->odoo_sku_mapping,
										'value' => $product->get_sku(),
									),
				'price'         => $product->get_price(),
				'regular_price' => $product->get_regular_price(),
				'sale_price'    => $product->get_sale_price(),
				'weight'        => $product->get_weight(),
				'volume'        => (int) ( (int) $product->get_height() * (int) $product->get_length() * (int) $product->get_width() ),
				'category'      => (int) $this->get_category_id( $product ),
				'attributes'    => $product->get_attributes(),
				'attribute_line_ids'    => $this->get_attributes_line_ids( array(), $product->get_attributes() ),
				'odoo_id' => get_post_meta($product->get_id(), '_odoo_id', true),
				'has_child' => false,
			);
			$tag_ids = $this->get_odoo_tag_ids( $product );
			if (!empty($tag_ids)) {
				$product_data['product_tags'] = $tag_ids;
			}
			if ( 'yes' == $this->odoo_settings[ 'odoo_export_update_stocks' ] ) {
				if ( 'yes' == $manage_stock_enabled ) {
					if ( $product->get_stock_quantity() > 0 ) {
						$product_data['stock'] = $product->get_stock_quantity();
					}
				}
			}
			if ( $helper->can_upload_image( $product ) ) {
				$product_data['image'] = $helper->upload_product_image($product);
			}
				
			if ( $product->has_child() ) {
				if ( ! $helper->is_product_exportable( $product->get_id() ) ) {
					return false;
				}
				$product_data['has_child'] = true;
				foreach ( $product->get_children() as $key => $child ) {
					$child_product                     = wc_get_product( ( $child ) );
					$product_data[ 'child' ][ $child ] = array(
						'id' => $child_product->get_id(),
						'attributes' => $child_product->get_attributes(),
						'sku'        => array(
											'key' => $this->odoo_sku_mapping,
											'value' => $child_product->get_sku(),
										),
						'price'      => $child_product->get_price(),
						'price_list' => $this->get_odoo_price_list(),
						'odoo_price_list_item' => get_post_meta( $child_product->get_id(), '_opmc_odoo_variant_price' ),
						'odoo_export_update_price' => $this->odoo_settings[ 'odoo_export_update_price' ],
					);
					if ( 'yes' == $this->odoo_settings[ 'odoo_export_update_stocks' ] ) {
						if ( 'yes' == $manage_stock_enabled ) {
							if ( $child_product->get_stock_quantity() > 0 ) {
								$product_data[ 'child' ][ $child ]['stock'] = $child_product->get_stock_quantity();
							}
						}
					}
						
						
					if ( $helper->can_upload_image( $child_product ) ) {
						$product_data[ 'child' ][ $child ][ 'image' ] = $helper->upload_product_image( $child_product );
					}
				}
			}
			$response = $odooApi->export_products( $product_data );
			$odooApi->addLog('response ' . print_r($response, 1));
			if ($response->success) {
				if ($product->has_child()) {
					$odoo_product_ids = $response->data->product_ids;
					$odoo_product_variants = $odoo_product_ids->variant_ids;
					update_post_meta( $odoo_product_ids->woo_id, '_odoo_id', $odoo_product_ids->odoo_id );
					update_post_meta( $odoo_product_ids->woo_id, '_synced_data_rec', 'synced' );
					update_post_meta( $odoo_product_ids->woo_id, '_synced_last_date_rec', gmdate( 'Y-m-d' ) );
					if (is_array($odoo_product_variants) && count($odoo_product_variants)) {
						foreach ( $odoo_product_variants as $odoo_product_variant ) {
							update_post_meta( $odoo_product_variant->Woo_var_id, '_odoo_id', $odoo_product_ids->odoo_id );
							update_post_meta( $odoo_product_variant->Woo_var_id, '_odoo_variation_id', $odoo_product_variant->odoo_var_id );
						}
					}
				} else {
					$odoo_product_ids = $response->data->product_ids;
					update_post_meta( $odoo_product_ids->woo_id, '_odoo_id', $odoo_product_ids->odoo_id );
					update_post_meta( $odoo_product_ids->woo_id, '_synced_data_rec', 'synced' );
					update_post_meta( $odoo_product_ids->woo_id, '_synced_last_date_rec', gmdate( 'Y-m-d' ) );
				}
			}
		}
		
		/**
		 * Get Odoo tag IDs from a WooCommerce product.
		 *
		 * @param WC_Product $product The WooCommerce product object.
		 * @return array|false An array of Odoo tag IDs, or false if no tags are found.
		 */
		public function get_odoo_tag_ids( $product) {
			// Initialize the Odoo API.
			$odooApi = new WC_ODOO_API();
			
			// Get the product ID.
			$product_id = $product->get_id();
			
			// Get the product tags.
			$tags = get_the_terms($product_id, 'product_tag');
			
			// Initialize arrays for the Odoo tag IDs and WooCommerce tags.
			$odoo_tag_ids = array();
			$woo_tags = array();
			
			// If tags are found...
			if (!empty($tags)) {
				// Loop through each tag.
				foreach ($tags as $tag) {
					// Get the Odoo tag ID from the term meta.
					$odoo_tag_id = get_term_meta($tag->term_id, '_opmc_odoo_tag_id', true);
					
					// Log the Odoo tag ID.
					$odooApi->addLog('Odoo tag id found : ' . print_r($odoo_tag_id, 1));
					
					// If an Odoo tag ID is found, add it to the array.
					// Otherwise, add the tag to the WooCommerce tags array.
					if ($odoo_tag_id) {
						$odoo_tag_ids[] = $odoo_tag_id;
					} else {
						$woo_tags[] = array(
							'id' => $tag->term_id,
							'name' => $tag->name
						);
					}
				}
				
				// If there are WooCommerce tags...
				if (!empty($woo_tags)) {
					// Make a request to the Odoo API.
					$response = $odooApi->odoo_product_tags($woo_tags);
					
					// Log the response.
					$odooApi->addLog('Tags Response : ' . print_r($response, 1));
					
					// If the request was successful...
					if ($response->success) {
						// If there are tags in the response...
						if (count($response->data->tags_ids)) {
							// Loop through each tag.
							foreach ($response->data->tags_ids as $odoo_tag) {
								// Add the Odoo tag ID to the array.
								$odoo_tag_ids[] = $odoo_tag->odoo_tag_id;
								
								// Update the term meta with the Odoo tag ID.
								update_term_meta($odoo_tag->woo_tag_id, '_opmc_odoo_tag_id', $odoo_tag->odoo_tag_id);
							}
						}
					}
				}
				
				// Return the array of Odoo tag IDs.
				return $odoo_tag_ids;
			} else {
				// If no tags are found, return false.
				return false;
			}
		}
			
		public function sync_to_odoo( $item ) {
			$product       = wc_get_product( $item );
			$excluded_item = false;
				
			$terms   = get_the_terms( $item, 'product_cat' );
			$odooApi = new WC_ODOO_API();
			$helper  = WC_ODOO_Helpers::getHelper();
				
			// $odooApi->addLog( print_r( $item, true ) . ' categories : ' . print_r( $terms, true ) );
			if ( '' != $terms && is_array( $this->odoo_settings[ 'odoo_exclude_product_category' ] ) ) {
				foreach ( $terms as $key => $term ) {
					if ( in_array( $term->term_id, $this->odoo_settings[ 'odoo_exclude_product_category' ] ) ) {
						$excluded_item = true;
					}
				}
			}
				
			if ( $excluded_item ) {
				return;
			}
				
			$syncable_product = get_post_meta( $product->get_id(), '_exclude_product_to_sync', true );
				
			if ( 'yes' == $syncable_product ) {
				return;
			}
				
			if ( $product->has_child() ) {
				if ( ! $helper->is_product_exportable( $product->get_id() ) ) {
					return false;
				}
				// $odooApi->addLog( 'Product have child product : ' . print_r( $product, 1 ) );
				// return;
				$odoo_template_id = get_post_meta( $product->get_id(), '_odoo_id', true );
				if ( ! $odoo_template_id ) {
					$child_SKUs = array();
					foreach ( $product->get_children() as $key => $child ) {
						$child_product = wc_get_product( $child );
						$child_SKUs[]  = $child_product->get_sku();
					}
					$conditions       = array(
						array(
							'field_key'   => $this->odoo_sku_mapping,
							'operator'    => 'in',
							'field_value' => $child_SKUs,
						),
					);
					$product_response = $odooApi->readAll( 'product.product', array(
						'id',
						'name',
						'partner_ref',
						'lst_price',
						'price_extra',
						'default_code',
						'code',
						'product_tmpl_id',
						'product_template_attribute_value_ids',
						'product_template_variant_value_ids',
						'is_product_variant',
						'attribute_line_ids',
						'valid_product_template_attribute_line_ids',
						'product_variant_ids',
						'product_variant_id',
						'product_variant_count',
					), $conditions );
					$odooApi->addLog( 'Serach Products : ' . print_r( $product_response, true ) );
					if ( $product_response->success ) {
						if ( $product_response->data->items ) {
							$odoo_template_id = $product_response->data->items[ 0 ]->product_tmpl_id;
						}
					}
				}
				if ( $odoo_template_id ) {
					$this->do_export_variable_product_update( (int) $odoo_template_id, $product );
				} else {
					$this->do_export_variable_product( $product );
				}
			} else {
				$odoo_product_id = get_post_meta( $product->get_id(), '_odoo_id', true );
					
				// Search Product on Odoo
				if ( ! $odoo_product_id ) {
					$conditions            = array(
						array(
							'field_key'   => $this->odoo_sku_mapping,
							'field_value' => $product->get_sku(),
						),
					);
					$odoo_product_response = $this->search_odoo_product( $conditions, $product->get_id() );
					// $odooApi->addLog( 'serach_products : ' . print_r( $odoo_product_response, true ) );
					if ( count( $odoo_product_response ) ) {
						$odoo_product_id = $odoo_product_response[ 0 ];
					} else {
						$odoo_product_id = false;
					}
				}
					
				if ( $odoo_product_id ) {
					$this->update_odoo_product( (int) $odoo_product_id, $product );
				} else {
					$odoo_product_response = $this->create_product( $product );
					if ( ! $odoo_product_response ) {
						return;
					} elseif ( $odoo_product_response->success ) {
						$odoo_product_id = $odoo_product_response->data->odoo_id;
						update_post_meta( $product->get_id(), '_synced_data_rec', 'synced' );
						update_post_meta( $product->get_id(), '_synced_last_date_rec', gmdate( 'Y-m-d' ) );
					} else {
						$odoo_product_id = false;
					}
				}
				// $odooApi->addLog( 'Odoo product Id : ' . print_r( $odoo_product_id, true ) );
				if ( false == $odoo_product_id ) {
					$error_msg = '[Product Export] [Error] [Products ID : ' . $product->get_id() . ' can\'t be exported due to some error. ]';
					$odooApi->addLog( $error_msg );
						
					return;
				}
				update_post_meta( $product->get_id(), '_odoo_id', $odoo_product_id );
				if ( 'yes' == $this->odoo_settings[ 'odoo_export_update_price' ] ) {
					if ( $product->is_on_sale() ) {
						$odoo_extra_product = get_post_meta( $product->get_id(), '_product_extra_price_id', true );
						if ( $odoo_extra_product ) {
							$this->update_extra_price( $odoo_extra_product, $product );
						} else {
							$this->create_extra_price( $odoo_product_id, $product );
						}
					}
				}
				if ( 'yes' == $this->odoo_settings[ 'odoo_export_update_stocks' ] ) {
					$manage_stock_enabled = get_option( 'woocommerce_manage_stock' );
					// $odooApi->addLog( 'manage_stock_enabled : ' . var_export( $manage_stock_enabled, 1 ) );
					if ( 'yes' == $manage_stock_enabled ) {
						if ( $product->get_stock_quantity() > 0 ) {
							$product_qty = number_format( (float) $product->get_stock_quantity(), 2, '.', '' );
							// $odooApi->addLog( 'New qty : ' . print_r( $product_qty, 1 ) );
							$res = $this->update_product_quantity( $odoo_product_id, $product_qty );
							// $odooApi->addLog( 'update qty response : ' . print_r( $res, 1 ) );
						}
					}
				}
				update_post_meta( $product->get_id(), '_odoo_image_id', $product->get_image_id() );
			}
		}
			
		public function do_export_product() {
			global $wpdb;
			$products = array();
			// $exclude_cats = implode(',', $this->odoo_settings['odoo_exclude_product_category']);
			$odooApi = new WC_ODOO_API();
			// $odooApi->addLog( 'Exclude categories : ' . print_r( $this->odoo_settings['odoo_exclude_product_category'], true ) );
			if ( 'yes' == $this->odoo_settings[ 'odoo_export_create_product' ] ) {
				$products = $wpdb->get_results(
					$wpdb->prepare(
						"SELECT {$wpdb->posts}.`ID`,{$wpdb->posts}.`post_type` FROM {$wpdb->posts} RIGHT JOIN  $wpdb->term_relationships  as t
                            ON ID = t.object_id WHERE (post_type='product') AND post_status='publish' AND post_status='publish'" ) );
			} elseif ( 'no' == $this->odoo_settings[ 'odoo_export_update_stocks' ] ) {
				$products = $wpdb->get_results(
					$wpdb->prepare(
						"SELECT {$wpdb->posts}.`ID`,{$wpdb->posts}.`post_type` FROM {$wpdb->posts} RIGHT JOIN  $wpdb->term_relationships  as t
                            ON ID = t.object_id WHERE (post_type='product') AND post_status='publish' AND NOT EXISTS (
              SELECT {$wpdb->postmeta}.`post_id` FROM {$wpdb->postmeta}
               WHERE {$wpdb->postmeta}.`meta_key` = '_odoo_id'
                AND {$wpdb->postmeta}.`post_id`={$wpdb->posts}.ID
            ) " ) );
			}
				
			// Remove duplicate products ids
			$products = array_unique( $products, SORT_REGULAR );
				
			foreach ( $products as $key => $product_obj ) {
				$product       = wc_get_product( $product_obj->ID );
				$excluded_item = false;
					
				$terms = get_the_terms( $product_obj->ID, 'product_cat' );
					
				// $odooApi->addLog( print_r( $product_obj->ID, true ) . ' categories : ' . print_r( $terms, true ) );
				if ( '' != $terms && is_array( $this->odoo_settings[ 'odoo_exclude_product_category' ] ) ) {
					foreach ( $terms as $key => $term ) {
						if ( in_array( $term->term_id, $this->odoo_settings[ 'odoo_exclude_product_category' ] ) ) {
							$excluded_item = true;
						}
					}
				}
					
				if ( $excluded_item ) {
					continue;
				}
					
				$syncable_product = get_post_meta( $product->get_id(), '_exclude_product_to_sync', true );
					
				if ( 'yes' == $syncable_product ) {
					continue;
				}
					
				if ( $product->has_child() ) {
					continue;
					$odoo_template_id = get_post_meta( $product->get_id(), '_odoo_id', true );
					if ( $odoo_template_id ) {
						$this->do_export_variable_product_update( (int) $odoo_template_id, $product );
					} else {
						$this->do_export_variable_product( $product );
					}
				} else {
					$odoo_product_id = get_post_meta( $product->get_id(), '_odoo_id', true );
						
					// Search Product on Odoo
					if ( ! $odoo_product_id ) {
						$conditions      = array(
							array(
								'field_key'   => $this->odoo_sku_mapping,
								'field_value' => $product->get_sku(),
							),
						);
						$odoo_product_id = $this->search_odoo_product( $conditions, $product->get_id() );
					}
						
					if ( $odoo_product_id ) {
						$this->update_odoo_product( (int) $odoo_product_id, $product );
					} else {
						$odoo_product_id = $this->create_product( $product );
					}
					if ( isset( $odoo_product_id[ 'fail' ] ) ) {
						$error_msg = '[Product Sync] [Error] [Error for Creating/Updating  Product Id  => ' . $product->get_id() . ' Msg : ' . print_r( $odoo_product_id[ 'msg' ], true ) . ']';
						$odooApi->addLog( $error_msg );
						continue;
					}
					if ( false == $odoo_product_id ) {
						$error_msg = '[Product Sync] [Error] [Error for Creating/Updating  Product Id  => ' . $product->get_id() . ' Msg : Error]';
						$odooApi->addLog( $error_msg );
						continue;
					}
					update_post_meta( $product->get_id(), '_odoo_id', $odoo_product_id );
					if ( 'yes' == $this->odoo_settings[ 'odoo_export_update_price' ] ) {
						if ( $product->is_on_sale() ) {
							$odoo_extra_product = get_post_meta( $product->get_id(), '_product_extra_price_id', true );
							if ( $odoo_extra_product ) {
								$this->update_extra_price( $odoo_extra_product, $product );
							} else {
								$this->create_extra_price( $odoo_product_id, $product );
							}
						}
					}
					if ( 'yes' == $this->odoo_settings[ 'odoo_export_update_stocks' ] ) {
						if ( $product->get_stock_quantity() > 0 ) {
							$product_qty = number_format( (float) $product->get_stock_quantity(), 2, '.', '' );
							$res         = $this->update_product_quantity( $odoo_product_id, $product_qty );
						}
					}
					update_post_meta( $product->get_id(), '_odoo_image_id', $product->get_image_id() );
				}
			}
		}
			
		public function do_export_variable_product_update( $odoo_template_id, $product ) {
			$odooApi       = new WC_ODOO_API();
			$helper        = WC_ODOO_Helpers::getHelper();
			$template_data = array(
				'name'                  => $product->get_name(),
				'sale_ok'               => true,
				'type'                  => 'product',
				$this->odoo_sku_mapping => $product->get_sku(),
				'description_sale'      => $product->get_description(),
				'attribute_line_ids'    => $this->update_attributes_line_ids( $odoo_template_id, $product ),
				
			);
			if ( 'yes' == $this->odoo_settings[ 'odoo_export_create_categories' ] ) {
				$template_data[ 'categ_id' ] = (int) $this->get_category_id( $product );
			}
			$tag_ids = $this->get_odoo_tag_ids( $product );
			if (!empty($tag_ids)) {
				$template_data['product_tag_ids'] = $tag_ids;
			}
			if ( 'yes' == $this->odoo_settings[ 'odoo_export_update_price' ] ) {
				$template_data[ 'list_price' ] = 1;
			}
			if ( $helper->can_upload_image( $product ) ) {
				$template_data[ 'image_1920' ] = $helper->upload_product_image( $product );
			}
				
			$template = $odooApi->update_record( 'product.template', array( $odoo_template_id ), $template_data );
				
			update_post_meta( $product->get_id(), '_odoo_id', $odoo_template_id );
			$conditions    = array(
				array(
					'field_key'   => 'product_tmpl_id',
					'field_value' => $odoo_template_id,
				),
			);
			$odoo_products = $odooApi->readAll( 'product.product', array(
				'id',
				'name',
				'partner_ref',
				'lst_price',
				'price_extra',
				'default_code',
				'code',
				'product_tmpl_id',
				'product_template_attribute_value_ids',
				'product_template_variant_value_ids',
				'is_product_variant',
				'attribute_line_ids',
				'valid_product_template_attribute_line_ids',
				'product_variant_ids',
				'product_variant_id',
				'product_variant_count',
			), $conditions );
			if ( $odoo_products->success ) {
				$odoo_products = json_decode( json_encode( $odoo_products->data->items ), true );
			}
				
			$pta_values_id = array_unique( call_user_func_array( 'array_merge', array_column( $odoo_products, 'product_template_attribute_value_ids' ) ) );
			sort( $pta_values_id );
			//          $odooApi->addLog('pta_value of ' . print_r($product->get_id(), 1) . '  : ' . print_r($pta_values_id, 1));
			$pta_values = $odooApi->fetch_record_by_ids( 'product.template.attribute.value', $pta_values_id, array(
				'id',
				'name',
				'product_attribute_value_id',
				'attribute_line_id',
				'attribute_id',
			) );
			if ( $pta_values->success ) {
				$pta_values = json_decode( json_encode( $pta_values->data->records ), true );
			}
				
			foreach ( $product->get_children() as $key => $child ) {
				$child_product = wc_get_product( $child );
				foreach ( $odoo_products as $opkey => $odoo_product ) {
					foreach ( $odoo_product[ 'product_template_attribute_value_ids' ] as $value_id ) {
						$vkey                          = array_search( $value_id, array_column( $pta_values, 'id' ) );
						$odoo_product[ 'pta_value' ][] = strtolower( $pta_values[ $vkey ][ 'name' ] );
					}
					$wcav = $child_product->get_attributes();
						
					$odoo_product[ 'pta_value' ] = array_map( 'strtolower', $odoo_product[ 'pta_value' ] );
					$wcav                        = array_map( 'strtolower', $wcav );
						
					sort( $odoo_product[ 'pta_value' ] );
					sort( $wcav );
						
					if ( $odoo_product[ 'pta_value' ] == $wcav ) {
						$child_data = array(
							$this->odoo_sku_mapping => $child_product->get_sku(),
						);
							
						if ( 'yes' == $this->odoo_settings[ 'odoo_export_update_price' ] ) {
							$price_list                 = $this->get_odoo_price_list();
							$child_data[ 'list_price' ] = 1;
							$variation_price            = $child_product->get_price();
							$odoo_price_list_item       = get_post_meta( $child_product->get_id(), '_opmc_odoo_variant_price' );
							if ( $odoo_price_list_item ) {
								$this->update_variant_price( $variation_price, $price_list, (int) $odoo_template_id, $odoo_product[ 'id' ], $odoo_price_list_item );
							} else {
								$odoo_pricelist_item = $this->update_variant_price( $variation_price, $price_list, (int) $odoo_template_id, $odoo_product[ 'id' ] );
								update_post_meta( $child_product->get_id(), '_opmc_odoo_variant_price', $odoo_pricelist_item );
							}
						}
						if ( $helper->can_upload_image( $child_product ) ) {
							$child_data[ 'image_1920' ] = $helper->upload_product_image( $child_product );
						}
						$res = $odooApi->update_record( 'product.product', $odoo_product[ 'id' ], $child_data );
							
						if ( 'yes' == $this->odoo_settings[ 'odoo_export_update_stocks' ] ) {
							if ( $child_product->get_stock_quantity() > 0 ) {
								$product_qty = number_format( (float) $child_product->get_stock_quantity(), 2, '.', '' );
								$res         = $this->update_product_quantity( $odoo_product[ 'id' ], $product_qty, $odoo_template_id );
							}
						}
						update_post_meta( $child_product->get_id(), '_odoo_id', $odoo_product[ 'id' ] );
						update_post_meta( $child_product->get_id(), '_odoo_image_id', $child_product->get_image_id() );
							
						update_post_meta( $product->get_id(), '_synced_data_rec', 'synced' );
						update_post_meta( $product->get_id(), '_synced_last_date_rec', gmdate( 'Y-m-d' ) );
					}
					unset( $odoo_product[ 'pta_value' ] );
					unset( $wcav );
				}
			}
		}
			
		public function update_odoo_product( $odoo_product_id, $product ) {
			$odooApi = new WC_ODOO_API();
			if ( $product->get_sku() == '' ) {
				$error_msg = '[Product Export] [Error] [Product : ' . str_replace( ' - ', '-', $product->get_name() ) . ' have missing/invalid SKU. This product will not be exported. ]';
				$odooApi->addLog( $error_msg );
					
				return false;
			}
			$helper = WC_ODOO_Helpers::getHelper();
			$data   = array(
				'name'                  => $product->get_name(),
				'sale_ok'               => true,
				'type'                  => 'product',
				$this->odoo_sku_mapping => $product->get_sku(),
				'description_sale'      => $product->get_description(),
				'weight'                => (int) $product->get_weight(),
				'volume'                => (int) ( (int) $product->get_height() * (int) $product->get_length() * (int) $product->get_width() ),
			);
			if ( 'yes' == $this->odoo_settings[ 'odoo_export_create_categories' ] ) {
				$data[ 'categ_id' ] = (int) $this->get_category_id( $product );
			}
			if ( 'yes' == $this->odoo_settings[ 'odoo_export_update_price' ] ) {
				// $data['list_price'] = $product->get_regular_price();
				$data[ 'list_price' ] = number_format( $product->get_sale_price() ? $product->get_sale_price() : $product->get_regular_price(), 2 );
					
				if ( $product->is_on_sale() ) {
					$odoo_extra_product = get_post_meta( $product->get_id(), '_product_extra_price_id', true );
					if ( $odoo_extra_product ) {
						$this->update_extra_price( $odoo_extra_product, $product );
					} else {
						$this->create_extra_price( $odoo_product_id, $product );
					}
				}
			}
			if ( $helper->can_upload_image( $product ) ) {
				$data[ 'image_1920' ] = $helper->upload_product_image( $product );
			}
			$update_product_res = $odooApi->update_record( 'product.product', array( $odoo_product_id ), $data );
			// $odooApi->addLog( 'Update product Response : ' . print_r( $update_product_res, 1 ) );
			if ( $update_product_res->success ) {
				// $odooApi->addLog( 'Odoo Product Id ' . print_r( $odoo_product_id, 1 ) . ' updated succesfully' );
				update_post_meta( $product->get_id(), '_synced_data_rec', 'synced' );
				update_post_meta( $product->get_id(), '_synced_last_date_rec', gmdate( 'Y-m-d' ) );
			} else {
				$odooApi->addLog( '[Product Sync] [Error] [Odoo Product Id ' . print_r( $odoo_product_id, 1 ) . ' error in updating ]' );
				$odooApi->addLog( '[Product Sync] [Error] [Error : ' . print_r( $update_product_res->message, 1 ) . ']' );
			}
				
			return true;
		}
			
			
		public function do_export_customer() {
				
			if ( $this->export_customers->is_process_running() ) {
				return;
			}
				
			$odooApi = new WC_ODOO_API();
				
			$total_customers = count( get_users( array( 'role' => 'customer' ) ) );
			$batch_size      = 100;
			$batch_count     = ceil( $total_customers / $batch_size );
				
			update_option( 'opmc_odoo_customer_export_count', $total_customers );
			update_option( 'opmc_odoo_customer_remaining_export_count', $total_customers );
				
			// $customer_export_process->empty_data();
				
			for ( $i = 0 ; $i < $batch_count ; $i++ ) {
				$offset = $i * $batch_size;
					
				$args          = array(
					'role'    => 'customer',
					'order'   => 'ASC',
					'orderby' => 'ID',
					'number'  => $batch_size,
					'offset'  => $offset,
					'fields'  => 'ID',
				);
				$wp_user_query = new WP_User_Query( $args );
				$customers     = $wp_user_query->get_results();
					
				// $odooApi->addLog( 'woo customers : ' . print_r( $customers, 1 ) );
					
				foreach ( $customers as $customer ) {
					$this->export_customers->push_to_queue( $customer );
				}
					
				$this->export_customers->save();
			}
			$this->export_customers->dispatch();
			update_option( 'opmc_odoo_customer_export_running', true );
			$odooApi->addLog( '[Customers Export] [Start] [Customer Export has been started for ' . print_r( $total_customers, 1 ) . ' Customers.]' );
		}
			
		public function do_export_order() {
			if ( $this->export_orders->is_process_running() ) {
				return;
			}
			$date_range = '';
				
			if ( ! empty( $this->odoo_settings[ 'odoo_export_order_from_date' ] ) && ! empty( $this->odoo_settings[ 'odoo_export_order_to_date' ] ) ) {
				$date_range = $this->odoo_settings[ 'odoo_export_order_from_date' ] . '...' . $this->odoo_settings[ 'odoo_export_order_to_date' ];
			}
				
			//          if ( !empty($this->odoo_settings['odoo_export_order_to_date']) ) {
			//              $date_range['before'] = $this->odoo_settings['odoo_export_order_to_date'];
			//          }
				
				
			$orders = wc_get_orders( array(
										 'limit'        => - 1,
										 'status'       => array(
											 'wc-pending',
											 'wc-processing',
											 'wc-on-hold',
											 'wc-completed',
										 ),
										 'type'         => 'shop_order',
										 'date_created' => ! empty( $date_range ) ? $date_range : '',
										 'return'       => 'ids',
									 ) );
				
			$orders            = array_unique( $orders, SORT_REGULAR );
			$exportable_orders = array();
				
			$odooApi = new WC_ODOO_API();
			$odooApi->addLog( 'orders id  : ' . print_r( $orders, 1 ) );
				
			foreach ( $orders as $key => $order_id ) {
				$odoo_order_id = opmc_hpos_get_post_meta( $order_id, '_odoo_order_id', true );
				if ( ! $odoo_order_id ) {
					$exportable_orders[] = $order_id;
				}
			}
			$odooApi->addLog( 'exportable order: ' . print_r( $exportable_orders, 1 ) );
			$total_orders = count( $exportable_orders );
			// $odooApi->addLog( 'total exportable orders count : ' . print_r( $total_orders, 1 ) );
			// $odooApi->addLog( 'total exportable orders : ' . print_r( $exportable_orders, 1 ) );
				
			if ( $total_orders ) {
				$batch_size     = 200;
				$last_order_key = array_key_last( $exportable_orders );
				$i              = 1;
				update_option( 'opmc_odoo_order_export_count', $total_orders );
				update_option( 'opmc_odoo_order_remaining_export_count', $total_orders );
					
				// $odooApi->addLog('order ids : '.print_r($orders, 1));
				foreach ( $exportable_orders as $key => $order_id ) {
					$this->export_orders->push_to_queue( $order_id );
					// $odooApi->addLog('save order id : '.print_r($key, 1));
					if ( $i >= $batch_size || $key == $last_order_key ) {
						$this->export_orders->save();
						$i = 1;
					} else {
						$i++;
					}
				}
				$this->export_orders->dispatch();
					
				update_option( 'opmc_odoo_order_export_running', true );
				$odooApi->addLog( '[Orders Export] [Start] [Order Export has been started for ' . print_r( $total_orders, 1 ) . ' Orders.]' );
			}
		}
			
		public function do_export_refund_order() {
			global $wpdb;
				
			$from_date = '';
			$to_date   = '';
				
			if ( isset( $this->odoo_settings[ 'odoo_export_order_from_date' ] ) && ! empty( $this->odoo_settings[ 'odoo_export_order_from_date' ] ) ) {
				$from_date = $wpdb->prepare( ' AND  p.post_date >= %s ', $this->odoo_settings[ 'odoo_export_order_from_date' ] );
			}
			if ( isset( $this->odoo_settings[ 'odoo_export_order_to_date' ] ) && ! empty( $this->odoo_settings[ 'odoo_export_order_to_date' ] ) ) {
				$to_date = $wpdb->prepare( ' AND  p.post_date <= %s ', $this->odoo_settings[ 'odoo_export_order_to_date' ] );
			}
				
			$orders = $wpdb->get_results(
				$wpdb->prepare(
					"
				SELECT pm.post_id AS order_id
				FROM {$wpdb->prefix}postmeta AS pm
				LEFT JOIN {$wpdb->prefix}posts AS p
				ON pm.post_id = p.ID
				WHERE p.post_type = 'shop_order'
				%s %s
				AND pm.meta_key = '_customer_user'
				ORDER BY pm.meta_value ASC, pm.post_id DESC
				", $from_date, $to_date ) );
			$orders = array_unique( $orders, SORT_REGULAR );
			foreach ( $orders as $key => $order ) {
				$order_id = opmc_hpos_get_post_meta( $order->order_id, '_odoo_order_id', true );
				if ( $order_id ) {
					$woo_order         = new WC_Order( $order->order_id );
					$woo_order_refunds = $woo_order->get_refunds();
						
					foreach ( $woo_order_refunds as $woo_order_refund ) {
						$odooApi = new WC_ODOO_API();
							
						// $odooApi->addLog( print_r( $order->order_id, 1 ) . ' order refund order : ' . print_r( $woo_order_refund->get_id(), 1 ) );
						$refund_id = $woo_order_refund->get_id();
						$this->create_odoo_refund( $order->order_id, $refund_id );
					}
				}
			}
		}
			
		public function do_export_variable_product( $product ) {
			$odooApi    = new WC_ODOO_API();
			$attrs      = $odooApi->readAll( 'product.attribute.value', array(
				'id',
				'name',
				'display_type',
				'attribute_id',
				'pav_attribute_line_ids',
			) );
			$odoo_attrs = array();
			if ( $attrs->success ) {
				$attrs = $attrs->data->items;
				foreach ( $attrs as $akey => $attr ) {
					$odoo_attrs[ strtolower( $attr->attribute_id[ 1 ] ) ][ strtolower( $attr->name ) ] = $attr;
				}
			}
				
			$helper        = WC_ODOO_Helpers::getHelper();
			$template_data = array(
				'name'                  => $product->get_name(),
				'sale_ok'               => true,
				'type'                  => 'product',
				$this->odoo_sku_mapping => $product->get_sku(),
				'description_sale'      => $product->get_description(),
				'attribute_line_ids'    => $this->get_attributes_line_ids( $odoo_attrs, $product->get_attributes() ),
			);
			if ( 'yes' == $this->odoo_settings[ 'odoo_export_create_categories' ] ) {
				$template_data[ 'categ_id' ] = (int) $this->get_category_id( $product );
			}
			$tag_ids = $this->get_odoo_tag_ids( $product );
			if (!empty($tag_ids)) {
				$template_data['product_tag_ids'] = $tag_ids;
			}
			if ( 'yes' == $this->odoo_settings[ 'odoo_export_update_price' ] ) {
				$template_data[ 'list_price' ] = 1;
			}
			if ( $helper->can_upload_image( $product ) ) {
				$template_data[ 'image_1920' ] = $helper->upload_product_image( $product );
			}
				
			$template = $odooApi->create_record( 'product.template', $template_data );
			if ( $template->success ) {
				$template = $template->data->odoo_id;
			} else {
				$template = false;
			}
			$conditions  = array(
				array(
					'field_key'   => 'product_tmpl_id',
					'field_value' => (int) $template,
				),
			);
			$attr_values = $odooApi->readAll( 'product.template.attribute.value', array(
				'id',
				'name',
				'attribute_line_id',
				'attribute_id',
			), $conditions );
			update_post_meta( $product->get_id(), '_odoo_id', $template );
			$odoo_products = $odooApi->readAll( 'product.product', array( 'id', 'name', 'partner_ref', 'lst_price', 'price_extra', 'default_code', 'code', 'product_tmpl_id', 'product_template_attribute_value_ids', 'product_template_variant_value_ids', 'is_product_variant', 'attribute_line_ids', 'valid_product_template_attribute_line_ids', 'product_variant_ids', 'product_variant_id', 'product_variant_count' ), $conditions );
			if ( $odoo_products->success ) {
				$odoo_products = json_decode( json_encode( $odoo_products->data->items ), true );
			}
				
			$pta_values_id = array_unique( call_user_func_array( 'array_merge', array_column( $odoo_products, 'product_template_attribute_value_ids' ) ) );
			sort( $pta_values_id );
				
			$pta_values = $odooApi->fetch_record_by_ids( 'product.template.attribute.value', $pta_values_id, array(
				'id',
				'name',
				'product_attribute_value_id',
				'attribute_line_id',
				'attribute_id',
			) );
				
			if ( $pta_values->success ) {
				$pta_values = json_decode( json_encode( $pta_values->data->records ), true );
			}
				
			foreach ( $product->get_children() as $key => $child ) {
				$child_product = wc_get_product( $child );
					
				foreach ( $odoo_products as $opkey => $odoo_product ) {
					foreach ( $odoo_product[ 'product_template_attribute_value_ids' ] as $value_id ) {
						$vkey                          = array_search( $value_id, array_column( $pta_values, 'id' ) );
						$odoo_product[ 'pta_value' ][] = strtolower( $pta_values[ $vkey ][ 'name' ] );
					}
					$wcav = $child_product->get_attributes();
						
					$odoo_product[ 'pta_value' ] = array_map( 'strtolower', $odoo_product[ 'pta_value' ] );
					$wcav                        = array_map( 'strtolower', $wcav );
						
					sort( $odoo_product[ 'pta_value' ] );
					sort( $wcav );
					$child_data = array();
					if ( $odoo_product[ 'pta_value' ] == $wcav ) {
						$child_data = array(
							$this->odoo_sku_mapping => $child_product->get_sku(),
						);
							
						if ( 'yes' == $this->odoo_settings[ 'odoo_export_update_price' ] ) {
							$price_list                 = $this->get_odoo_price_list();
							$child_data[ 'list_price' ] = 1;
							$variation_price            = $child_product->get_price();
							$odoo_pricelist_item        = $this->update_variant_price( $variation_price, $price_list, (int) $template, $odoo_product[ 'id' ] );
							update_post_meta( $child_product->get_id(), '_opmc_odoo_variant_price', $odoo_pricelist_item );
						}
						if ( $helper->can_upload_image( $child_product ) ) {
							$child_data[ 'image_1920' ] = $helper->upload_product_image( $child_product );
						}
						$res = $odooApi->update_record( 'product.product', array( (int) $odoo_product[ 'id' ] ), $child_data );
							
						if ( 'yes' == $this->odoo_settings[ 'odoo_export_update_stocks' ] ) {
							if ( $child_product->get_stock_quantity() > 0 ) {
								$product_qty = number_format( (float) $child_product->get_stock_quantity(), 2, '.', '' );
								$res         = $this->update_product_quantity( $odoo_product[ 'id' ], $product_qty, $template );
							}
						}
						update_post_meta( $child_product->get_id(), '_odoo_id', $template );
						update_post_meta( $child_product->get_id(), '_odoo_variation_id', $odoo_product[ 'id' ] );
						update_post_meta( $child_product->get_id(), '_odoo_image_id', $child_product->get_image_id() );
						update_post_meta( $product->get_id(), '_synced_data_rec', 'synced' );
						update_post_meta( $product->get_id(), '_synced_last_date_rec', gmdate( 'Y-m-d' ) );
					}
					unset( $odoo_product[ 'pta_value' ] );
					unset( $wcav );
				}
			}
		}
			
		public function get_odoo_price_list() {
			$odoo_pricelist_id = get_option( '_opmc_odoo_pricelist' );
			if ( $odoo_pricelist_id ) {
				return $odoo_pricelist_id;
			} else {
				$odooApi        = new WC_ODOO_API();
				$pricelist_name = get_bloginfo( 'name' );
				$odooApi->addLog( 'Price list : ' . print_r( $pricelist_name, 1 ) );
				$data = array(
					array(
						'name' => get_bloginfo( 'name' ) . ' pricelist',
					),
				);
				$res  = $odooApi->create_record( 'product.pricelist', $data );
				$odooApi->addLog( 'price list response : ' . print_r( $res, 1 ) );
				if ( $res->success ) {
					$odoo_pricelist_id = $res->data->odoo_id;
					update_option( '_opmc_odoo_pricelist', $odoo_pricelist_id );
						
					return $odoo_pricelist_id;
				}
			}
		}
			
		public function update_variant_price( $price, $price_list, $product_id, $variation_id, $price_list_item = 0 ) {
			$odooApi = new WC_ODOO_API();
			$data    = array(
				'applied_on'      => '0_product_variant',
				'fixed_price'     => $price,
				'pricelist_id'    => (int) $price_list,
				'product_tmpl_id' => (int) $product_id,
				'product_id'      => (int) $variation_id,
			);
			if ( $price_list_item ) {
				$res = $odooApi->update_record( 'product.pricelist.item', $price_list_item, array( 'fixed_price' => (int) $price ) );
			} else {
				$res = $odooApi->create_record( 'product.pricelist.item', $data );
				if ( $res->success ) {
					$odoo_pricelist_item = $res->data->odoo_id;
						
					return $odoo_pricelist_item;
				}
			}
		}
			
		public function update_attributes_line_ids( $odoo_template_id, $product ) {
			$odoo_attrs_lines = array();
			$odooApi          = new WC_ODOO_API();
				
			$odoo_attr_line_ids = get_post_meta( $product->get_id(), '_opmc_odoo_attr_line_ids', true );
			if ( ! $odoo_attr_line_ids ) {
				$odooProduct        = $odooApi->fetch_record_by_id( 'product.template', array( $odoo_template_id ), array(
					'id',
					'name',
					'sale_ok',
					'type',
					'description_sale',
					'attribute_line_ids',
					'list_price',
					$this->odoo_sku_mapping,
					'product_variant_ids',
					'product_variant_count',
					'pricelist_item_count',
				) );
				$odoo_attr_line_ids = $odooProduct[ 0 ][ 'attribute_line_ids' ];
				update_post_meta( $product->get_id(), '_opmc_odoo_attr_line_ids', $odoo_attr_line_ids );
			}
			$odooApi->addLog( 'odoo attr line ids : ' . print_r( $odoo_attr_line_ids, 1 ) );
				
			if ( $odoo_attr_line_ids ) {
				$odoo_attr_lines = $odooApi->fetch_record_by_id( 'product.template.attribute.line', $odoo_attr_line_ids );
				foreach ( $odoo_attr_lines as $odoo_attr_line ) {
					$odoo_attrs_lines[ strtolower( $odoo_attr_line[ 'attribute_id' ][ 1 ] ) ] = $odoo_attr_line;
				}
			}
				
			foreach ( $product->get_attributes() as $product_attribute ) {
				if ( $product_attribute->get_id() > 0 ) {
					$taxonomy     = wc_get_attribute_taxonomies();
					$product_attr = wc_get_attribute( $product_attribute->get_id() );
					foreach ( $taxonomy as $key => $tax ) {
						if ( $tax->attribute_id == $product_attr->id ) {
							$wc_attrs[ 'attr' ] = $tax;
							$attribute_name     = $tax->attribute_name;
						}
					}
					$attr_id = $this->odoo_product_attributes_id( $wc_attrs );
				} else {
					$attr_name      = $product_attribute->get_name();
					$attribute_name = $attr_name;
					$attr_id        = $this->odoo_product_attributes_id( $attr_name );
				}
					
				$new_odoo_attr_value_ids = $this->get_odoo_attr_term_ids( $attr_id, $attribute_name, $product_attribute );
					
				$lowercaseTermName = strtolower( $attribute_name );
				$attributes_values = array_key_exists( $lowercaseTermName, $odoo_attrs_lines ) ? $odoo_attrs_lines[ $lowercaseTermName ] : null;
				if ( $attributes_values ) {
					$odoo_attr_value = $attributes_values[ 'value_count' ];
					$wc_attr_value   = count( $product_attribute->get_options() );
					if ( $odoo_attr_value != $wc_attr_value ) {
						$attr_line_update = $odooApi->update_record( 'product.template.attribute.line', $attributes_values[ 'id' ], array( 'value_ids' => $new_odoo_attr_value_ids ) );
						if ( ! $attr_line_update->success ) {
							$odooApi->addLog( 'Error in updating Odoo attributes line ids' );
						}
					}
				} else {
					$data             = array(
						'product_tmpl_id' => (int) $odoo_template_id,
						'attribute_id'    => (int) $attr_id,
						'value_ids'       => $new_odoo_attr_value_ids,
					);
					$create_attr_line = $odooApi->create_record( 'product.template.attribute.line', $data );
					if ( $create_attr_line->success ) {
						$odoo_attr_line_ids[] = $create_attr_line->data->odoo_id;
					} else {
						$odooApi->addLog( 'Error in updating Odoo attributes line ids' );
					}
				}
			}
				
			return $odoo_attr_line_ids;
		}
			
		public function get_odoo_attr_term_ids( $attr_id, $attribute_name, $product_attribute ) {
			$odooApi       = new WC_ODOO_API();
			$conditions    = array(
				array(
					'field_key'   => 'attribute_id',
					'field_value' => array(
						$attribute_name,
						strtoupper( $attribute_name ),
						strtolower( $attribute_name ),
						ucfirst( $attribute_name ),
					),
					'operator'    => 'in',
				),
			);
			$odoo_attr_ids = $odooApi->search_records( 'product.attribute.value', $conditions, array(
				'id',
				'name',
			) );
			if ( $odoo_attr_ids->success ) {
				$odoo_attr_ids = $odoo_attr_ids->data->items;
			}
			$odoo_attr_value_ids = array();
			foreach ( $odoo_attr_ids as $odoo_attr_id ) {
				$odoo_attr_value_ids[ strtolower( $odoo_attr_id->name ) ] = $odoo_attr_id->id;
			}
			$attr_val_ids = array();
			foreach ( $product_attribute->get_options() as $okey => $option_id ) {
				if ( ! is_string( $option_id ) ) {
					$term      = get_term( $option_id );
					$term_name = $term->name;
				} else {
					$term      = $option_id;
					$term_name = $option_id;
				}
				$lowercaseTermName = strtolower( $term_name );
				$odoo_term_id      = array_key_exists( $lowercaseTermName, $odoo_attr_value_ids ) ? $odoo_attr_value_ids[ $lowercaseTermName ] : null;
				if ( $odoo_term_id ) {
					$attr_val_ids[] = $odoo_term_id;
				} else {
					$odoo_term_id   = $this->odoo_attributes_value_id( $attr_id, $term );
					$attr_val_ids[] = $odoo_term_id;
				}
			}
			$odooApi->addLog( 'attr_val_ids : ' . print_r( $attr_val_ids, 1 ) );
				
			return $attr_val_ids;
		}
			
		public function get_attributes_line_ids( $attr_values, $product_attributes ) {
			$odoo_attr_line = array();
			$odooApi        = new WC_ODOO_API();
				
			foreach ( $product_attributes as $key => $product_attribute ) {
				//              $odooApi->addLog('product attributes : '. print_r($product_attribute, 1));
				if ( $product_attribute->get_id() > 0 ) {
					$taxonomy     = wc_get_attribute_taxonomies();
					$product_attr = wc_get_attribute( $product_attribute->get_id() );
					foreach ( $taxonomy as $key => $tax ) {
						if ( $tax->attribute_id == $product_attr->id ) {
							$wc_attrs[ 'attr' ] = $tax;
							$attribute_name     = $tax->attribute_name;
						}
					}
					$attr_id = $this->odoo_product_attributes_id( $wc_attrs );
				} else {
					$attr_name      = $product_attribute->get_name();
					$attribute_name = $attr_name;
					$attr_id        = $this->odoo_product_attributes_id( $attr_name );
				}
					
				$attr_val_ids = $this->get_odoo_attr_term_ids( $attr_id, $attribute_name, $product_attribute );
					
				$odoo_attr_line[] = array(
					0,
					'virtual_' . implode( '', $attr_val_ids ),
					array(
						'attribute_id' => $attr_id,
						'value_ids'    => array(
							array(
								6,
								false,
								$attr_val_ids,
							),
							
						),
					),
				);
			}
				
			return $odoo_attr_line;
		}
			
		public function do_import_coupon() {
			$odooApi = new WC_ODOO_API();
			if ( $this->odoo_settings[ 'odooVersion' ] < 16 ) {
				$conditions = array(
					array(
						'field_key'   => 'program_type',
						'field_value' => 'coupon_program',
					),
				);
				$coupons    = $odooApi->search_records( 'loyalty.program', $conditions );
				// $odooApi->addLog( 'coupon for new version : ' . print_r( $coupons, 1 ) );
			} else {
				$conditions = array(
					array(
						'field_key'   => 'program_type',
						'field_value' => 'coupons',
					),
				);
				$coupons    = $odooApi->search_records( 'loyalty.program', $conditions );
				// $odooApi->addLog( 'coupon for old version : ' . print_r( $coupons, 1 ) );
			}
				
			if ( $coupons->success ) {
				$coupons = json_decode( json_encode( $coupons->data->items ), true );
				if ( is_array( $coupons ) && count( $coupons ) > 0 ) {
					foreach ( $coupons as $key => $coupon ) {
						if ( $coupon[ 'coupon_count' ] > 0 ) {
							$this->create_coupon_to_wc( $coupon );
						}
					}
					// exit;
				}
			} else {
				$error_msg = '[Coupon Sync] [Error] [Error for Search coupons => Msg : ' . print_r( $coupons->message, true ) . ']';
				$odooApi->addLog( $error_msg );
			}
		}
			
		public function create_coupon_to_wc( $odoo_coupon ) {
				
			$odooApi = new WC_ODOO_API();
			if ( $this->odoo_settings[ 'odooVersion' ] < 16 ) {
				$coupons = $odooApi->fetch_record_by_ids( 'coupon.coupon', $odoo_coupon[ 'coupon_ids' ] );
				// $odooApi->addLog( 'coupon coupon by id : ' . print_r( $coupons, 1 ) );
				if ( ! isset( $coupons[ 'fail' ] ) && is_array( $coupons ) && count( $coupons ) ) {
					foreach ( $coupons as $key => $coupon ) {
						$coupon_code = $coupon[ 'code' ];
						$amount      = $odoo_coupon[ 'discount_percentage' ];
						if ( 'percentage' == $odoo_coupon[ 'discount_type' ] ) {
							if ( 'on_order' == $odoo_coupon[ 'discount_apply_on' ] ) {
								$discount_type = 'percent';
							} elseif ( 'specific_products' == $odoo_coupon[ 'discount_apply_on' ] ) {
								$discount_type = 'percent_product';
							}
							$amount = $odoo_coupon[ 'discount_percentage' ];
						} elseif ( 'fixed_amount' == $odoo_coupon[ 'discount_type' ] ) {
							$discount_type = 'fixed_cart';
							$amount        = $odoo_coupon[ 'discount_fixed_amount' ];
						}
							
						/* Type: fixed_cart, percent, fixed_product, percent_product */
							
						$coupon_data = array(
							'post_title'   => $coupon_code,
							'post_content' => '',
							'post_status'  => 'publish',
							'post_author'  => 1,
							'post_type'    => 'shop_coupon',
						);
						$coupon_id   = $this->get_post_id_by_meta_key_and_value( '_odoo_coupon_code_id', $coupon[ 'id' ] );
							
						if ( $coupon_id ) {
							if ( 'no' == $this->odoo_settings[ 'odoo_import_coupon_update' ] ) {
								continue;
							}
							$coupon_data[ 'ID' ] = $coupon_id;
							$new_coupon_id       = wp_update_post( $coupon_data );
						} else {
							$new_coupon_id = wp_insert_post( $coupon_data );
						}
						update_post_meta( $new_coupon_id, 'discount_type', $discount_type );
						update_post_meta( $new_coupon_id, 'coupon_amount', $amount );
						update_post_meta( $new_coupon_id, '_odoo_coupon_code_id', $coupon[ 'id' ] );
						update_post_meta( $new_coupon_id, '_odoo_coupon_id', $odoo_coupon[ 'id' ] );
						update_post_meta( $new_coupon_id, '_odoo_coupon_name', $odoo_coupon[ 'name' ] );
						if ( 'specific_products' == $odoo_coupon[ 'discount_apply_on' ] ) {
							update_post_meta( $new_coupon_id, 'product_ids', $odoo_coupon[ 'discount_specific_product_ids' ] );
						}
						update_post_meta( $new_coupon_id, 'usage_limit', 1 );
						update_post_meta( $new_coupon_id, 'free_shipping', 'no' );
					}
				}
			} else {
				$coupons = $odooApi->fetch_record_by_ids( 'loyalty.card', $odoo_coupon[ 'coupon_ids' ] );
				$rewards = $odooApi->fetch_record_by_ids( 'loyalty.reward', $odoo_coupon[ 'reward_ids' ] );
				if ( $coupons->success ) {
					$coupons = json_decode( json_encode( $coupons->data->records ), true );
					// $odooApi->addLog('coupons by id old version : '. print_r($coupons, 1));
					if ( $rewards->success ) {
						$rewards = json_decode( json_encode( $rewards->data->records ), true );
						// $odooApi->addLog('coupons rewards by id old version : '. print_r($rewards, 1));
						$rewards = $rewards[ 0 ];
					}
					if ( is_array( $coupons ) && count( $coupons ) ) {
						foreach ( $coupons as $key => $coupon ) {
							// $odooApi->addLog('coupon found : '. print_r($coupon, 1));
							$coupon_code = $coupon[ 'code' ];
							$amount      = $rewards[ 'discount' ];
							if ( 'percent' == $rewards[ 'discount_mode' ] ) {
								if ( 'order' == $rewards[ 'discount_applicability' ] ) {
									$discount_type = 'percent';
								} elseif ( 'specific' == $rewards[ 'discount_applicability' ] ) {
									$discount_type = 'percent_product';
								}
								$amount = $rewards[ 'discount' ];
							} elseif ( 'per_order' == $rewards[ 'discount_mode' ] ) {
								$discount_type = 'fixed_cart';
								$amount        = $rewards[ 'discount_fixed_amount' ];
							}
								
							/* Type: fixed_cart, percent, fixed_product, percent_product */
								
							$coupon_data = array(
								'post_title'   => $coupon_code,
								'post_content' => '',
								'post_status'  => 'publish',
								'post_author'  => 1,
								'post_type'    => 'shop_coupon',
							);
							// $odooApi->addLog( 'coupons data : ' . print_r( $coupon_data, 1 ) );
							$coupon_id = $this->get_post_id_by_meta_key_and_value( '_odoo_coupon_code_id', $coupon[ 'id' ] );
								
							if ( $coupon_id ) {
								if ( 'no' == $this->odoo_settings[ 'odoo_import_coupon_update' ] ) {
									continue;
								}
								$coupon_data[ 'ID' ] = $coupon_id;
								$new_coupon_id       = wp_update_post( $coupon_data );
							} else {
								$new_coupon_id = wp_insert_post( $coupon_data );
							}
							update_post_meta( $new_coupon_id, 'discount_type', $discount_type );
							update_post_meta( $new_coupon_id, 'coupon_amount', $amount );
							update_post_meta( $new_coupon_id, '_odoo_coupon_code_id', $coupon[ 'id' ] );
							update_post_meta( $new_coupon_id, '_odoo_coupon_reward_id', $rewards[ 'id' ] );
							update_post_meta( $new_coupon_id, '_odoo_coupon_id', $odoo_coupon[ 'id' ] );
							update_post_meta( $new_coupon_id, '_odoo_coupon_name', $odoo_coupon[ 'name' ] );
							if ( 'specific' == $rewards[ 'discount_applicability' ] ) {
								update_post_meta( $new_coupon_id, 'product_ids', $rewards[ 'all_discount_product_ids' ] );
							}
							update_post_meta( $new_coupon_id, 'usage_limit', 1 );
							update_post_meta( $new_coupon_id, 'free_shipping', 'no' );
						}
					}
				}
			}
		}
			
		public function do_export_coupon() {
				
			$odooApi = new WC_ODOO_API();
			$common  = new WC_ODOO_Common_Functions();
				
			if ( ! $common->is_authenticate() ) {
				return;
			}
				
			$args = array(
				'posts_per_page' => - 1,
				'orderby'        => 'title',
				'order'          => 'asc',
				'post_type'      => 'shop_coupon',
				'post_status'    => 'publish',
				
			);
				
			$coupons = get_posts( $args );
			if ( $this->odoo_settings[ 'odooVersion' ] < 16 ) {
				foreach ( $coupons as $key => $coupon ) {
					$coupon_id   = get_post_meta( $coupon->ID, '_odoo_coupon_id', true );
					$coupon_data = $this->create_coupon_data( $coupon );
					// $odooApi->addLog('coupon data : '. print_r($coupon_data, 1));
					if ( $coupon_id ) {
						if ( 'no' == $this->odoo_settings[ 'odoo_export_coupon_update' ] ) {
							continue;
						}
						$res = $odooApi->update_record( 'coupon.program', array( (int) $coupon_id ), $coupon_data );
					} else {
						$coupon_id = $odooApi->create_record( 'coupon.program', $coupon_data );
						// $odooApi->addLog( 'coupan create result : ' . print_r( $coupon_id, 1 ) );
					}
					if ( $coupon_id->success ) {
						update_post_meta( $coupon->ID, '_odoo_coupon_id', $coupon_id );
						$coupon_code_id = get_post_meta( $coupon->ID, '_odoo_coupon_code_id', true );
							
						$code_data = $this->create_coupon_code_data( $coupon, $coupon_id );
						if ( $coupon_code_id ) {
							$res_code = $odooApi->update_record( 'coupon.coupon', array( (int) $coupon_code_id ), $code_data );
						} else {
							$coupon_code_id = $odooApi->create_record( 'coupon.coupon', $code_data );
						}
						if ( $coupon_code_id->success ) {
							$coupon_code_id = $coupon_code_id->data->odoo_id;
							update_post_meta( $coupon->ID, '_odoo_coupon_code_id', $coupon_code_id );
						} else {
							$error_msg = '[Coupon Sync] [Error] [Error for Creating/Updating Coupon Code Id  => ' . $coupon->ID . ' Msg : ' . print_r( $coupon_code_id->message, true ) . ']';
							$odooApi->addLog( $error_msg );
							continue;
						}
					} else {
						$error_msg = '[Coupon Sync] [Error] [Error for Creating/Updating Coupon Id  => ' . $coupon->ID . ' Msg : ' . print_r( $coupon_id->message, true ) . ']';
						$odooApi->addLog( $error_msg );
						continue;
					}
				}
			} else {
				foreach ( $coupons as $key => $coupon ) {
					$coupon_id = get_post_meta( $coupon->ID, '_odoo_coupon_id', true );
					// $odooApi->addLog('coupons : '. print_r($coupon, 1));
					if ( $coupon_id ) {
						if ( 'no' == $this->odoo_settings[ 'odoo_export_coupon_update' ] ) {
							continue;
						}
						// $this->update_loyalty_program($coupon, $coupon_id);
					} else {
						$loyalty_response = $this->create_loyalty_program( $coupon );
						// $odooApi->addLog( 'loyalty ' . print_r( $loyalty_response, 1 ) );
					}
				}
			}
		}
			
		public function create_loyalty_program( $coupon ) {
			$odooApi     = new WC_ODOO_API();
			$coupon_name = ( '' != $coupon->post_excerpt ) ? $coupon->post_excerpt : $coupon->name;
			$meta_data   = get_post_meta( $coupon->ID );
			if ( isset( $meta_data[ 'date_expires' ][ 0 ] ) ) {
				$expires = abs( time() - $meta_data[ 'date_expires' ][ 0 ] ) / 60 / 60 / 24;
				// $odooApi->addLog(print_r($meta_data['date_expires'][0], 1). ' expiers : '. print_r($expires, 1));
			}
			$data = array(
				array(
					'name'         => $coupon->post_title,
					'active'       => 1,
					'program_type' => 'coupons',
					'display_name' => $coupon->post_title,
				),
			);
			// if (isset($meta_data['discount_type'][0])) {
			// if (isset($meta_data['date_expires'][0])) {
			// $data[0]['date_to'] = $expires;
			// }
			// }
				
			// $odooApi->addLog( 'Coupon Data : ' . print_r( $data, 1 ) );
			$loyalty_response = $odooApi->create_record( 'loyalty.program', $data );
			// $odooApi->addLog( 'coupon program : ' . print_r( $loyalty_response, 1 ) );
			if ( $loyalty_response->success ) {
				$reward_data[]   = $this->create_reward_data( $coupon, $loyalty_response->data->odoo_id );
				$reward_response = $odooApi->create_record( 'loyalty.reward', $reward_data );
				// $odooApi->addLog( 'coupon reward : ' . print_r( $reward_response, 1 ) );
				if ( $reward_response->success ) {
					$coupon_data          = array(
						array(
							'code'   => $coupon->name, // "expiration_date" => $expires,
							'points' => 1,
						),
					);
					$reward_card_response = $odooApi->create_record( 'loyalty.card', $coupon_data );
					// $odooApi->addLog( 'coupon program : ' . print_r( $reward_card_response, 1 ) );
					if ( $reward_card_response->success ) {
						update_post_meta( $coupon->ID, '_odoo_coupon_code_id', $reward_card_response->data->odoo_id );
						update_post_meta( $coupon->ID, '_odoo_coupon_reward_id', $reward_response->data->odoo_id );
						update_post_meta( $coupon->ID, '_odoo_coupon_id', $loyalty_response->data->odoo_id );
						update_post_meta( $coupon->ID, '_odoo_coupon_name', $coupon_name );
					}
				}
			}
		}
			
		public function create_reward_data( $coupon, $program_id ) {
			$meta_data = get_post_meta( $coupon->ID );
				
			if ( isset( $meta_data[ 'discount_type' ][ 0 ] ) ) {
				$discount_type          = $meta_data[ 'discount_type' ][ 0 ];
				$data[ 'program_id' ]   = $program_id;
				$data[ 'program_type' ] = 'coupons';
				$data[ 'reward_type' ]  = 'discount';
				if ( 'fixed_cart' == $discount_type ) {
					$data[ 'discount_mode' ]          = 'per_order';
					$data[ 'discount_applicability' ] = 'order';
					$data[ 'discount' ]               = $meta_data[ 'coupon_amount' ][ 0 ];
				} elseif ( 'percent' == $discount_type ) {
					$data[ 'discount_mode' ]          = 'percent';
					$data[ 'discount' ]               = $meta_data[ 'coupon_amount' ][ 0 ];
					$data[ 'discount_applicability' ] = 'order';
				} else {
					$data[ 'discount_mode' ]          = 'percent';
					$data[ 'discount' ]               = $meta_data[ 'coupon_amount' ][ 0 ];
					$data[ 'discount_applicability' ] = 'order';
				}
			}
				
			if ( isset( $meta_data[ 'minimum_amount' ][ 0 ] ) ) {
				$data[ 'discount_max_amount' ] = $meta_data[ 'minimum_amount' ][ 0 ];
			}
				
			return $data;
		}
			
		public function update_loyalty_program( $coupon, $coupon_id ) {
			$data             = array(
				array(
					'name'         => $coupon->post_excerpt,
					'active'       => 1,
					'program_type' => 'coupons',
				),
			);
			$odooApi          = new WC_ODOO_API();
			$loyalty_response = $odooApi->update_record( 'loyalty.program', $coupon_id, $data );
			$odooApi->addLog( '[Coupon Sync] [Success] [Coupon updated : ' . print_r( $loyalty_response, 1 ) ) . ']';
		}
			
		public function create_coupon_data( $coupon ) {
			$odooApi = new WC_ODOO_API();
			// $odooApi->addLog('coupons : '. print_r($coupon, 1));
			$data = array(
				'name'              => $coupon->post_name,
				'active'            => 1,
				'program_type'      => 'coupon_program',
				'rule_min_quantity' => 1,
			);
			// $odooApi->addLog('coupon data : '. print_r($data, 1));
			$meta_data = get_post_meta( $coupon->ID );
				
			if ( isset( $meta_data[ 'discount_type' ][ 0 ] ) ) {
				$discount_type = $meta_data[ 'discount_type' ][ 0 ];
				if ( 'fixed_cart' == $discount_type ) {
					$data[ 'discount_type' ]         = 'fixed_amount';
					$data[ 'discount_apply_on' ]     = 'on_order';
					$data[ 'discount_fixed_amount' ] = $meta_data[ 'coupon_amount' ][ 0 ];
				} elseif ( 'percent' == $discount_type ) {
					$data[ 'discount_type' ]       = 'percentage';
					$data[ 'discount_percentage' ] = $meta_data[ 'coupon_amount' ][ 0 ];
					$data[ 'discount_apply_on' ]   = 'on_order';
				} else {
					$data[ 'discount_type' ]       = 'percentage';
					$data[ 'discount_percentage' ] = $meta_data[ 'coupon_amount' ][ 0 ];
					$data[ 'discount_apply_on' ]   = 'on_order';
				}
			}
			if ( isset( $meta_data[ 'date_expires' ][ 0 ] ) ) {
				$data[ 'validity_duration' ] = abs( time() - $meta_data[ 'date_expires' ][ 0 ] ) / 60 / 60 / 24;
			}
			if ( isset( $meta_data[ 'minimum_amount' ][ 0 ] ) ) {
				$data[ 'rule_minimum_amount' ] = $meta_data[ 'minimum_amount' ][ 0 ];
			}
			if ( isset( $meta_data[ '_odoo_coupon_name' ][ 0 ] ) ) {
				$data[ 'name' ] = $meta_data[ '_odoo_coupon_name' ][ 0 ];
			}
				
			return $data;
		}
			
		public function create_coupon_code_data( $coupon, $odoo_coupon_id ) {
			$data = array(
				'code'       => $coupon->post_name,
				'program_id' => $odoo_coupon_id,
			);
				
			return $data;
		}
			
		public function do_import_customer() {
				
			if ( $this->import_customers->is_process_running() ) {
				return;
			}
				
			$odooApi         = new WC_ODOO_API();
			$conditions      = array(
				array(
					'field_key'   => 'type',
					'field_value' => 'contact',
				),
				array(
					'field_key'   => 'customer_rank',
					'field_value' => 1,
				),
			);
			$total_customers = $odooApi->search_count( 'res.partner', $conditions );
			$batch_size      = 200;
			$batch_count     = ceil( $total_customers / $batch_size );
			update_option( 'opmc_odoo_customer_import_count', $total_customers );
			update_option( 'opmc_odoo_customer_remaining_import_count', $total_customers );
			for ( $i = 0 ; $i < $batch_count ; $i++ ) {
				$offset    = $i * $batch_size;
				$customers = $odooApi->search(
					'res.partner', $conditions, array(
					'offset' => $offset,
					'limit'  => $batch_size,
				) );
					
				if ( $customers->success ) {
					$customers = json_decode( json_encode( $customers->data->items ), true );
				} else {
					$error_msg = '[Customer import] [Error] [Error for Search customers => Msg : ' . print_r( $customers->message, true ) . ']';
					$odooApi->addLog( $error_msg );
				}
					
				// $odooApi->addLog('customer_ids : '. print_r($customers, 1));
				if ( is_array( $customers ) && count( $customers ) > 0 ) {
					foreach ( $customers as $key => $customer_id ) {
						$this->import_customers->push_to_queue( $customer_id );
					}
					$this->import_customers->save();
				}
			}
			$this->import_customers->dispatch();
			update_option( 'opmc_odoo_customer_import_running', true );
			$odooApi->addLog( '[Customers Import] [Start] [Customer Import has been started for ' . print_r( $total_customers, 1 ) . ' Customers.]' );
		}
			
		public function sync_customer_to_wc( $customer, $address_lists ) {
				
			$user = get_user_by( 'email', $customer[ 'email' ] );
				
			if ( ! $user ) {
				$userById = get_users(
					array(
						'meta_key'   => '_odoo_id',
						'meta_value' => $customer[ 'id' ],
					) );
				if ( 0 != count( $userById ) ) {
					$user = $userById[ 0 ];
				}
			}
				
			// $odooApi = new WC_ODOO_API();
			// $odooApi->addLog('user : '. print_r($user->email, true));
			// $odooApi->addLog('odoo user : '. print_r($customer['email'], true));
			// return false;
				
			if ( null != $user && is_array( $user->roles ) && in_array( 'customer', $user->roles ) ) {
				$user_id = $user->ID;
			}
			$customer_name = $this->split_name( $customer[ 'name' ] );
			$userdata      = array(
				'user_login'    => $customer[ 'email' ],
				'user_nicename' => $customer_name[ 'first_name' ],
				'user_email'    => $customer[ 'email' ],
				'display_name'  => $customer[ 'display_name' ],
				'nickname'      => $customer_name[ 'first_name' ],
				'first_name'    => $customer_name[ 'first_name' ],
				'last_name'     => $customer_name[ 'last_name' ],
				'role'          => 'customer',
				'locale'        => '',
				'website'       => $customer[ 'website' ],
			);
				
			if ( isset( $user_id ) ) {
				$userdata[ 'ID' ] = $user_id;
				wp_update_user( $userdata );
			} else {
				$userdata[ 'user_pass' ] = 'gsf3213#$rtyu';
				$user_id                 = wp_insert_user( $userdata );
			}
				
			update_user_meta( $user_id, '_odoo_id', $customer[ 'id' ] );
				
			$is_billing_updated = false;
			foreach ( $address_lists as $key => $address ) {
				if ( in_array( $address[ 'type' ], array( 'delivery', 'invoice' ) ) ) {
					if ( 'invoice' == $address[ 'type' ] ) {
						$is_billing_updated = true;
					}
					$this->create_user_addres_to_wc( $user_id, $address, $address[ 'type' ] );
				}
			}
			if ( ! $is_billing_updated ) {
				$this->create_user_addres_to_wc( $user_id, $customer, 'invioce' );
			}
				
			return $user_id;
		}
			
		public function create_user_addres_to_wc( $user_id, $address, $address_type = 'invioce' ) {
				
			$type          = ( 'delivery' == $address_type ) ? 'shipping' : 'billing';
			$customer_name = $this->split_name( $address[ 'name' ] );
				
			update_user_meta( $user_id, $type . '_first_name', $customer_name[ 'first_name' ] );
			update_user_meta( $user_id, $type . '_last_name', $customer_name[ 'last_name' ] );
			update_user_meta( $user_id, $type . '_address_1', $address[ 'street' ] );
			update_user_meta( $user_id, $type . '_address_2', $address[ 'street2' ] );
			update_user_meta( $user_id, $type . '_city', $address[ 'city' ] );
			if ( isset( $address[ 'state_id' ][ 1 ] ) ) {
				preg_match( '#\((.*?)\)#', $address[ 'state_id' ][ 1 ], $country );
				update_user_meta( $user_id, $type . '_country', $country[ 1 ] );
				$state = explode( ' (', $address[ 'state_id' ][ 1 ] );
				if ( '' != $country[ 1 ] && null != $country[ 1 ] && ! empty( $country[ 1 ] ) ) {
					$states_array = array_flip( WC()->countries->get_states( $country[ 1 ] ) );
					$state_name   = isset( $states_array[ $state[ 0 ] ] ) ? $states_array[ $state[ 0 ] ] : '';
				}
				update_user_meta( $user_id, $type . '_state', $state_name );
			}
				
			update_user_meta( $user_id, $type . '_postcode', $address[ 'zip' ] );
			// update_user_meta( $user_id, $type . '_country', $address['country_code']);
			update_user_meta( $user_id, $type . '_email', $address[ 'email' ] );
			update_user_meta( $user_id, $type . '_phone', $address[ 'phone' ] );
			update_user_meta( $user_id, '_odoo_' . $type . '_id', $address[ 'id' ] );
		}
			
		public function split_name( $name ) {
			$name       = trim( $name );
			$last_name  = ( strpos( $name, ' ' ) === false ) ? '' : preg_replace( '#.*\s([\w-]*)$#', '$1', $name );
			$first_name = trim( preg_replace( '#' . preg_quote( $last_name, '#' ) . '#', '', $name ) );
				
			return array(
				'first_name' => $first_name,
				'last_name'  => $last_name,
			);
		}
			
		public function do_import_order() {
				
			if ( $this->import_orders->is_process_running() ) {
				return;
			}
				
			$odooApi    = new WC_ODOO_API();
			$conditions = array();
				
			if ( isset( $this->odoo_settings[ 'odoo_import_order_from_date' ] ) && ! empty( $this->odoo_settings[ 'odoo_import_order_from_date' ] ) ) {
				$conditions[] = array(
					'field_key'   => 'date_order',
					'field_value' => $this->odoo_settings[ 'odoo_import_order_from_date' ],
					'operator'    => '>=',
				);
			}
			if ( isset( $this->odoo_settings[ 'odoo_import_order_to_date' ] ) && ! empty( $this->odoo_settings[ 'odoo_import_order_to_date' ] ) ) {
				$conditions[] = array(
					'field_key'   => 'date_order',
					'operator'    => '<=',
					'field_value' => $this->odoo_settings[ 'odoo_import_order_to_date' ],
				);
			}
				
			$conditions[] = array(
				'field_key'   => 'state',
				'operator'    => 'not in',
				'field_value' => array( 'draft', 'sent', 'cancel' ),
			);
				
			$total_orders = $odooApi->search_count( 'sale.order', $conditions );
			// $odooApi->addLog( 'Orders Cunt : ' . print_r( $total_orders, 1 ) );
				
			$batch_size  = 100;
			$batch_count = ceil( $total_orders / $batch_size );
			update_option( 'opmc_odoo_order_import_count', $total_orders );
			update_option( 'opmc_odoo_order_remaining_import_count', $total_orders );
				
			for ( $i = 0 ; $i < $batch_count ; $i++ ) {
				$offset = $i * $batch_size;
				// $odooApi->addLog( 'conditions : ' . print_r( $conditions, 1 ) );
				$orders = $odooApi->search(
					'sale.order', $conditions, array(
					'offset' => $offset,
					'limit'  => $batch_size,
				) );
					
				if ( $orders->success ) {
					$orders = json_decode( json_encode( $orders->data->items ), true );
				} else {
					$error_msg = '[Order Sync] [Error] [Error for Search orders => Msg : ' . print_r( $orders->message, true ) . ']';
					$odooApi->addLog( $error_msg );
				}
					
				if ( is_array( $orders ) && count( $orders ) > 0 ) {
					foreach ( $orders as $key => $order_id ) {
						$this->import_orders->push_to_queue( $order_id );
					}
					$this->import_orders->save();
				}
			}
			$this->import_orders->dispatch();
			update_option( 'opmc_odoo_order_import_running', true );
			$odooApi->addLog( '[Orders Import] [Start] [Order Import has been started for ' . print_r( $total_orders, 1 ) . ' Orders.]' );
		}
			
		public function odoo_import_order( $order ) {
			$odooApi = new WC_ODOO_API();
			// $odooApi->addLog('odoo orders : '. print_r($order, true));
			$order_id = $this->get_post_id_by_meta_key_and_value( '_odoo_order_id', $order[ 'id' ] );
			if ( $order_id ) {
				// $odooApi->addLog( 'Order already Synced for Odoo Order Id : ' . $order['id'] );
				return false;
			}
				
			$user_id     = $this->get_user_id_by_meta_key_and_value( '_odoo_id', $order[ 'partner_id' ][ 0 ] );
			$partner_ids = array( $order[ 'partner_invoice_id' ][ 0 ], $order[ 'partner_shipping_id' ][ 0 ] );
			if ( ! $user_id ) {
				$partner_ids[] = $order[ 'partner_id' ][ 0 ];
			}
			$partners = $odooApi->fetch_record_by_ids( 'res.partner', $partner_ids, array(
				'id',
				'name',
				'display_name',
				'website',
				'mobile',
				'email',
				'is_company',
				'phone',
				'image_medium',
				'street',
				'street2',
				'zip',
				'city',
				'state_id',
				'country_id',
				'type',
			) );
				
			// $odooApi->addLog('odoo partners : '. print_r($partner_ids, true));
			// $odooApi->addLog('odoo partners : '. print_r($partners, true));
			if ( $partners->success ) {
				$partners = json_decode( json_encode( $partners->data->records ), true );
			} else {
				$odooApi->addLog( '[Order Sync] [Error] [User not found for Order Id : ' . $order[ 'id' ] . ']' );
					
				return false;
			}
			$users = array();
				
			foreach ( $partners as $key => $partner ) {
				$billing  = $this->create_customer_address_data( $partner );
				$shipping = $this->create_customer_address_data( $partner );
				if ( 'invoice' == $partner[ 'type' ] ) {
					$users[ 'billing' ] = $this->create_customer_address_data( $partner );
				} elseif ( 'delivery' == $partner[ 'type' ] ) {
					$users[ 'shipping' ] = $this->create_customer_address_data( $partner );
				} else {
					$users[ 'user_id' ] = ( false != $user_id ) ? $user_id : $this->create_wc_customer( $partner );
				}
			}
			extract( $users );
			$order_lines = $odooApi->fetch_record_by_ids( 'sale.order.line', $order[ 'order_line' ], array(
				'id',
				'name',
				'invoice_status',
				'price_subtotal',
				'price_tax',
				'price_total',
				'product_id',
				'product_uom_qty',
				'price_unit',
			) );
				
			if ( $order_lines->success ) {
				$order_lines = json_decode( json_encode( $order_lines->data->records ), true );
			} else {
				$error_msg = '[Order Sync] [Error] [Order Line found for Order Id  => ' . $order[ 'id' ] . ' Msg : ' . print_r( $order_lines->message, true ) . ']';
				$odooApi->addLog( $error_msg );
					
				return false;
			}
			$wc_order = wc_create_order( array( 'customer_id' => $user_id ) );
			$wc_order->update_meta_data( '_new_order_email_sent', 'true' );
			$wc_order->update_meta_data( '_customer_user', $user_id );
			$wc_order->update_meta_data( '_odoo_order_id', $order[ 'id' ] );
			$wc_order->update_meta_data( '_odoo_invoice_id', end( $order[ 'invoice_ids' ] ) );
			$wc_order->update_meta_data( '_odoo_order_origin', $order[ 'name' ] );
			foreach ( $order_lines as $key => $order_line ) {
				if ( isset( $order_line[ 'product_id' ][ 0 ] ) ) {
					$product_id = $this->get_post_id_by_meta_key_and_value( '_odoo_id', $order_line[ 'product_id' ][ 0 ] );
					//                  $odooApi->addLog('odoo order Product id : '.print_r($order_line['product_id'][0], true));
					if ( ! $product_id ) {
						$odoo_product    = $odooApi->fetch_record_by_id( 'product.product', array( $order_line[ 'product_id' ][ 0 ] ), array( 'activity_ids', 'activity_state', 'activity_user_id', 'activity_type_id', 'activity_type_icon', 'activity_date_deadline', 'my_activity_date_deadline', 'activity_summary', 'activity_exception_decoration', 'activity_exception_icon', 'activity_calendar_event_id', 'message_is_follower', 'message_follower_ids', 'message_partner_ids', 'message_ids', 'has_message', 'message_needaction', 'message_needaction_counter', 'message_has_error', 'message_has_error_counter', 'message_attachment_count', 'rating_ids', 'website_message_ids', 'message_has_sms_error', 'price_extra', 'lst_price', 'default_code', 'code', 'partner_ref', 'active', 'product_tmpl_id', 'barcode', 'product_template_attribute_value_ids', 'product_template_variant_value_ids', 'combination_indices', 'is_product_variant', 'standard_price', 'volume', 'weight', 'pricelist_item_count', 'product_document_ids', 'product_document_count', 'packaging_ids', 'additional_product_tag_ids', 'all_product_tag_ids', 'can_image_variant_1024_be_zoomed', 'can_image_1024_be_zoomed', 'write_date', 'id', 'display_name', 'create_uid', 'create_date', 'write_uid', 'tax_string', 'stock_quant_ids', 'stock_move_ids', 'qty_available', 'virtual_available', 'free_qty', 'incoming_qty', 'outgoing_qty', 'orderpoint_ids', 'nbr_moves_in', 'nbr_moves_out', 'nbr_reordering_rules', 'reordering_min_qty', 'reordering_max_qty', 'putaway_rule_ids', 'storage_category_capacity_ids', 'show_on_hand_qty_status_button', 'show_forecasted_qty_status_button', 'valid_ean', 'lot_properties_definition', 'value_svl', 'quantity_svl', 'avg_cost', 'total_value', 'company_currency_id', 'stock_valuation_layer_ids', 'valuation', 'cost_method', 'sales_count', 'product_catalog_product_is_in_sale_order', 'name', 'sequence', 'description', 'description_purchase', 'description_sale', 'detailed_type', 'type', 'categ_id', 'currency_id', 'cost_currency_id', 'list_price', 'volume_uom_name', 'weight_uom_name', 'sale_ok', 'purchase_ok', 'uom_id', 'uom_name', 'uom_po_id', 'company_id', 'seller_ids', 'variant_seller_ids', 'color', 'attribute_line_ids', 'valid_product_template_attribute_line_ids', 'product_variant_ids', 'product_variant_id', 'product_variant_count', 'has_configurable_attributes', 'product_tooltip', 'priority', 'product_tag_ids', 'taxes_id', 'supplier_taxes_id', 'property_account_income_id', 'property_account_expense_id', 'account_tag_ids', 'fiscal_country_codes', 'responsible_id', 'property_stock_production', 'property_stock_inventory', 'sale_delay', 'tracking', 'description_picking', 'description_pickingout', 'description_pickingin', 'location_id', 'warehouse_id', 'has_available_route_ids', 'route_ids', 'route_from_categ_ids', 'service_type', 'sale_line_warn', 'sale_line_warn_msg', 'expense_policy', 'visible_expense_policy', 'invoice_policy', 'optional_product_ids', 'planning_enabled', 'planning_role_id', 'service_tracking', 'project_id', 'project_template_id', 'service_policy' ) );
						$variant_ids     = $odoo_product[ 0 ][ 'product_template_variant_value_ids' ];
						$product_tmpl_id = $odoo_product[ 0 ][ 'product_tmpl_id' ][ 0 ];
						if ( empty( $variant_ids ) ) {
							$templates = $odooApi->fetch_record_by_ids( 'product.template', array( $product_tmpl_id ) );
							if ( $templates->success ) {
								$template = json_decode( json_encode( $templates->data->records[ 0 ] ), true );
								$product_id = $this->sync_product_from_odoo( $template, true );
							}
						} else {
							$odooApi->addLog( 'order Product id : ' . print_r( $variant_ids, true ) );
							$templates = $odooApi->fetch_record_by_ids( 'product.template', array( $product_tmpl_id ) );
							//                          $odooApi->addLog('product template : '. print_r($templates, 1));
							if ( $templates->success ) {
								$template = json_decode( json_encode( $templates->data->records[ 0 ] ), true );
								$attr_v   = array();
//                                  $odooApi->addLog( 'product variant : ' . print_r( $template[ 'product_variant_count' ], 1 ) );
								if ( $template[ 'product_variant_count' ] > 1 ) {
									if ( count( $attr_v ) == 0 ) {
										$attr_response = $odooApi->readAll( 'product.template.attribute.value', array(
											'name',
											'id',
										) );
										$attr_values   = json_decode( json_encode( $attr_response->data->items ), true );
										// $odooApi->addLog('Prdcuts attributes : '. print_r($attr_values, true));
										foreach ( $attr_values as $key => $value ) {
											$attr_v[ $value[ 'id' ] ] = $value[ 'name' ];
										}
										$this->odoo_attr_values = $attr_v;
									}
									$products = $odooApi->fetch_record_by_ids( 'product.product', $template[ 'product_variant_ids' ], array( 'activity_ids', 'activity_state', 'activity_user_id', 'activity_type_id', 'activity_type_icon', 'activity_date_deadline', 'my_activity_date_deadline', 'activity_summary', 'activity_exception_decoration', 'activity_exception_icon', 'activity_calendar_event_id', 'message_is_follower', 'message_follower_ids', 'message_partner_ids', 'message_ids', 'has_message', 'message_needaction', 'message_needaction_counter', 'message_has_error', 'message_has_error_counter', 'message_attachment_count', 'rating_ids', 'website_message_ids', 'message_has_sms_error', 'price_extra', 'lst_price', 'default_code', 'code', 'partner_ref', 'active', 'product_tmpl_id', 'barcode', 'product_template_attribute_value_ids', 'product_template_variant_value_ids', 'combination_indices', 'is_product_variant', 'standard_price', 'volume', 'weight', 'pricelist_item_count', 'product_document_ids', 'product_document_count', 'packaging_ids', 'additional_product_tag_ids', 'all_product_tag_ids', 'can_image_variant_1024_be_zoomed', 'can_image_1024_be_zoomed', 'write_date', 'id', 'display_name', 'create_uid', 'create_date', 'write_uid', 'tax_string', 'stock_quant_ids', 'stock_move_ids', 'qty_available', 'virtual_available', 'free_qty', 'incoming_qty', 'outgoing_qty', 'orderpoint_ids', 'nbr_moves_in', 'nbr_moves_out', 'nbr_reordering_rules', 'reordering_min_qty', 'reordering_max_qty', 'putaway_rule_ids', 'storage_category_capacity_ids', 'show_on_hand_qty_status_button', 'show_forecasted_qty_status_button', 'valid_ean', 'lot_properties_definition', 'value_svl', 'quantity_svl', 'avg_cost', 'total_value', 'company_currency_id', 'stock_valuation_layer_ids', 'valuation', 'cost_method', 'sales_count', 'product_catalog_product_is_in_sale_order', 'name', 'sequence', 'description', 'description_purchase', 'description_sale', 'detailed_type', 'type', 'categ_id', 'currency_id', 'cost_currency_id', 'list_price', 'volume_uom_name', 'weight_uom_name', 'sale_ok', 'purchase_ok', 'uom_id', 'uom_name', 'uom_po_id', 'company_id', 'seller_ids', 'variant_seller_ids', 'color', 'attribute_line_ids', 'valid_product_template_attribute_line_ids', 'product_variant_ids', 'product_variant_id', 'product_variant_count', 'has_configurable_attributes', 'product_tooltip', 'priority', 'product_tag_ids', 'taxes_id', 'supplier_taxes_id', 'property_account_income_id', 'property_account_expense_id', 'account_tag_ids', 'fiscal_country_codes', 'responsible_id', 'property_stock_production', 'property_stock_inventory', 'sale_delay', 'tracking', 'description_picking', 'description_pickingout', 'description_pickingin', 'location_id', 'warehouse_id', 'has_available_route_ids', 'route_ids', 'route_from_categ_ids', 'service_type', 'sale_line_warn', 'sale_line_warn_msg', 'expense_policy', 'visible_expense_policy', 'invoice_policy', 'optional_product_ids', 'planning_enabled', 'planning_role_id', 'service_tracking', 'project_id', 'project_template_id', 'service_policy' ) );
//                                       $odooApi->addLog('variant Products : '. print_r($products, true));
									$products = json_decode( json_encode( $products->data->records ), true );
										
									$attrs = $odooApi->fetch_record_by_ids( 'product.template.attribute.line', $template[ 'attribute_line_ids' ], array(
										'display_name',
										'id',
										'product_template_value_ids',
									) );
									$attrs = json_decode( json_encode( $attrs->data->records ), true );
									// $odooApi->addLog('variant Products attribute line : '. print_r($attrs, true));
									foreach ( $products as $pkey => $product ) {
										$attr_and_value = array();
										foreach ( $product[ 'product_template_attribute_value_ids' ] as $attr => $attr_value ) {
											foreach ( $attrs as $key => $attr ) {
												foreach ( $attr[ 'product_template_value_ids' ] as $key => $value ) {
													if ( $value == $attr_value ) {
														$attr_and_value[ $attr[ 'display_name' ] ] = $attr_v[ $value ];
													}
												}
											}
											$products[ $pkey ][ 'attr_and_value' ]            = $attr_and_value;
											$products[ $pkey ][ 'attr_value' ][ $attr_value ] = $attr_v[ $attr_value ];
											// $this->create_variation_product($template,$product);
										}
									}
										
									$products[ 'attributes' ] = $attrs;
									$product_id               = $this->sync_product_from_odoo( $template, true, $products );
									$product_id               = $this->get_post_id_by_meta_key_and_value( '_odoo_variation_id', $order_line[ 'product_id' ][ 0 ] );
									$odooApi->addLog( print_r( $order_line[ 'product_id' ][ 0 ], 1 ) . 'Variant product ID : ' . print_r( $product_id, 1 ) );
								}
							}
						}
					}
					$product = wc_get_product( $product_id );
					// $odooApi->addLog('order Product : '.print_r($product, true));
						
					$product->set_price( $order_line[ 'price_unit' ] );
					$item_id = $wc_order->add_product( $product, $order_line[ 'product_uom_qty' ] );
					wc_update_order_item_meta( $item_id, '_order_line_id', $order_line[ 'id' ] );
				}
			}
			if ( isset( $billing ) ) {
				$wc_order->set_address( $billing, 'billing' );
			}
			if ( isset( $shipping ) ) {
				$wc_order->set_address( $shipping, 'shipping' );
			}
			$wc_order->calculate_totals();
			$wc_order->set_date_completed( $order[ 'date_order' ] );
			$wc_order->set_status( 'completed', __( 'Order Imported From Odoo', 'wc-odoo-integration' ) );
			$wc_order->save();
		}
			
		public function create_customer_address_data( $partner ) {
			$data = array(
				'first_name' => $this->split_name( $partner[ 'name' ] )[ 'first_name' ],
				'last_name'  => $this->split_name( $partner[ 'name' ] )[ 'last_name' ],
				'email'      => $partner[ 'email' ],
				'phone'      => $partner[ 'phone' ],
				'address_1'  => $partner[ 'street' ],
				'address_2'  => $partner[ 'street2' ],
				'city'       => $partner[ 'city' ],
				'state'      => isset( $partner[ 'state_id' ][ 1 ] ) ? $partner[ 'state_id' ][ 1 ] : '',
				'postcode'   => $partner[ 'zip' ],
				'country'    => isset( $partner[ 'country_id' ][ 1 ] ) ? $partner[ 'country_id' ][ 1 ] : '',
			);
				
			if ( 1 == $partner[ 'is_company' ] ) {
				$data[ 'company' ] = $partner[ 'display_name' ];
			}
				
			return $data;
		}
			
		public function create_wc_customer( $customer ) {
			$user = get_user_by( 'email', $customer[ 'email' ] );
				
			if ( ! $user ) {
				$userById = get_users(
					array(
						'meta_key'   => '_odoo_id',
						'meta_value' => $customer[ 'id' ],
					) );
				if ( 0 != count( $userById ) ) {
					$user = $userById[ 0 ];
				}
			}
				
			$odooApi = new WC_ODOO_API();
			// $odooApi->addLog( 'user : ' . print_r( $user->email, true ) );
			// $odooApi->addLog('odoo user : '. print_r($customer['email'], true));
			// return false;
				
			if ( null != $user && is_array( $user->roles ) && in_array( 'customer', $user->roles ) ) {
				$user_id = $user->ID;
			}
			$customer_name = $this->split_name( $customer[ 'name' ] );
			$userdata      = array(
				'user_nicename' => $customer_name[ 'first_name' ],
				'user_email'    => $customer[ 'email' ],
				'display_name'  => $customer[ 'display_name' ],
				'nickname'      => $customer_name[ 'first_name' ],
				'first_name'    => $customer_name[ 'first_name' ],
				'last_name'     => $customer_name[ 'last_name' ],
				'role'          => 'customer',
				'locale'        => '',
				'website'       => $customer[ 'website' ],
			);
				
			if ( isset( $user_id ) ) {
				$userdata[ 'ID' ] = $user_id;
				wp_update_user( $userdata );
			} else {
				$userdata[ 'user_pass' ]  = 'gsf3213#$rtyu';
				$userdata[ 'user_login' ] = $customer[ 'email' ];
				$user_id                  = wp_insert_user( $userdata );
			}
				
			update_user_meta( $user_id, '_odoo_id', $customer[ 'id' ] );
			if ( ! is_wp_error( $user_id ) ) {
				return $user_id;
			}
				
			return false;
		}
			
		public function product_id_for_order_line( $item ) {
			$odooApi = new WC_ODOO_API();
			$product = $item->get_product();
			//          $odooApi->addLog('Product Id :L '. print_r($product->get_id(), 1));
				
			if ( $product->is_type( 'variation' ) ) {
				$parent_product = wc_get_product( $product->get_parent_id() );
				//              $odooApi->addLog('Product Parent Id : '. print_r($parent_product->get_id(), 1));
				$odoo_product_variant_id = get_post_meta( $product->get_id(), '_odoo_variation_id', true );
				$odoo_product_id         = get_post_meta( $parent_product->get_id(), '_odoo_id', true );
				//              $odooApi->addLog('Parent of the varianttttttt: '. print_r($odoo_product_variant_id, 1));
				if ( ! $odoo_product_id && ! $odoo_product_variant_id ) {
					$conditions = array(
						array(
							'field_key'   => $this->odoo_sku_mapping,
							'field_value' => $product->get_sku(),
						),
					);
					$product_id = $odooApi->search_record( 'product.product', $conditions );
						
					if ( $product_id->success ) {
						if ( ! empty( $product_id->data->items ) ) {
							$product_id = $product_id->data->items[ 0 ];
							//                           $odooApi->addLog( 'Order Product Found : ' . print_r( $product_id, true ) );
							$odoo_product_variant_id = $product_id;
						} else {
							$product_id = '';
							$odooApi->addLog( 'Order Product not Synced.' );
						}
					} else {
						$error_msg = '[Product Sync] [Error] [Error for Search product => ' . str_replace( ' - ', '-', $product->get_name() ) . ' Msg : ' . print_r( $product_id->message, true ) . ']';
						$odooApi->addLog( $error_msg );
							
						return false;
					}
						
					update_post_meta( $product->get_id(), '_odoo_variation_id', $product_id );
					update_post_meta( $parent_product->get_id(), '_odoo_id', $product_id );
					if ( ! isset( $product_id ) || $product_id <= '' || false == $product_id ) {
						$this->do_export_variable_product( $parent_product );
						$odoo_product_variant_id = get_post_meta( $product->get_id(), '_odoo_variation_id', true );
						//                      $odooApi->addLog('product variant id : '. print_r($odoo_product_variant_id, 1));
						$odoo_product_id = get_post_meta( $parent_product->get_id(), '_odoo_id', true );
					}
				}
					
				//              $odooApi->addLog('Parent of the varianttttttt: '. print_r($odoo_product_variant_id, 1));
				return $odoo_product_variant_id;
			} else {
				$product_id = get_post_meta( $product->get_id(), '_odoo_id', true );
				if ( ! $product_id ) {
					$conditions = array(
						array(
							'field_key'   => $this->odoo_sku_mapping,
							'field_value' => $product->get_sku(),
						),
					);
						
					$product_id = $odooApi->search_record( 'product.product', $conditions );
						
					if ( $product_id->success ) {
						if ( ! empty( $product_id->data->items ) ) {
							$product_id = $product_id->data->items[ 0 ];
							// $odooApi->addLog( 'Order Product Found : ' . print_r( $product_id, true ) );
						} else {
							$product_id = '';
							// $odooApi->addLog( 'Order Product not Synced.' );
						}
					} else {
						$error_msg = '[Product Sync] [Error] [Error for Search product => ' . $product->get_id() . ' Msg : ' . print_r( $product_id->message, true ) . ']';
						$odooApi->addLog( $error_msg );
							
						return false;
					}
						
					update_post_meta( $product->get_id(), '_odoo_id', $product_id );
						
					if ( ! isset( $product_id ) || $product_id <= '' || false == $product_id ) {
						$product_id = $this->create_product( $product, true );
						if ( $product_id->success ) {
							$product_id = $product_id->data->odoo_id;
							update_post_meta( $product->get_id(), '_odoo_id', $product_id );
						} else {
							$error_msg = '[Product Sync] [Error] [Error for Creating  Product Id  =>' . $product->get_id() . ' Msg : ' . print_r( $product_id->message, true ) . ']';
							$odooApi->addLog( $error_msg );
								
							return false;
						}
							
						// if ('yes' == $this->odoo_settings['odoo_export_update_price']) {
						if ( $product->is_on_sale() ) {
							$odoo_extra_product = get_post_meta( $product->get_id(), '_product_extra_price_id', true );
							if ( $odoo_extra_product ) {
								$this->update_extra_price( $odoo_extra_product, $product );
							} else {
								$this->create_extra_price( $product_id, $product );
							}
						}
						// }
						$product_qty = number_format( (float) $product->get_stock_quantity(), 2, '.', '' );
							
						$this->update_product_quantity( $product_id, $product_qty );
						update_post_meta( $product->get_id(), '_odoo_id', $product_id );
						update_post_meta( $product->get_id(), '_odoo_image_id', $product->get_image_id() );
					}
				}
					
				return $product_id;
			}
		}
			
			
		public function order_create( $order_id ) {
				
			$odoo_settings = get_option( 'woocommerce_woocommmerce_odoo_integration_settings' );
			$odooApi       = new WC_ODOO_API();
			$order         = new WC_Order( $order_id );
			$common        = new WC_ODOO_Common_Functions();
			$helper        = WC_ODOO_Helpers::getHelper();
				
			$msg = '[Order Sync] [Start] [Order export process started for #' . $order_id . ']';
			$odooApi->addLog( $msg );
				
			if ( ! $common->is_authenticate() ) {
				return false;
			}
			if ( 'pending' == $order->get_status() ) {
				return false;
			}
			$verifyOrderItems = $helper->verifyOrderItems( $order_id );
				
			if ( ! $verifyOrderItems ) {
				//              $odooApi->addLog( '[Order Export] [Error] [{'.print_r($order->get_status(), 1).'}Order Export aborted due to invalid/incomplete product. Please review the order product details.]' );
					
				return false;
			}
				
			$woo_state = $helper->getState( $order->get_status() );
			$statuses  = $helper->odooStates( $woo_state );
				
			// $odooApi->addLog( print_r( $order_id, true ) . ' create order : ' . print_r( $statuses, true ) );
				
			if ( 'shop_order' != $order->get_type() ) {
				return false;
			}
			$is_order_syced = get_post_meta( $order_id, '_odoo_order_id', true );
			// $odooApi->addLog( 'order synced : ' . print_r( $is_order_syced, true ) );
			if ( $is_order_syced ) {
				$error_msg = '[Order Export] [info] [Order Already Synced For Id ' . $order_id . ' With Odoo Sale Order Id => ' . $is_order_syced . ']';
				$odooApi->addLog( $error_msg );
					
				return false;
			}
			// get user id assocaited with order
			$user          = $order->get_user();
			$customer_data = $this->getCustomerData( $user, $order );
				
			if ( ! isset( $odoo_settings[ 'odooTax' ] ) ) {
				$error_msg = '[Order Export] [Error] [Invalid Tax Setting For Order Id ' . $order_id . ']';
				$odooApi->addLog( $error_msg );
					
				return false;
			}
			/* get tax id from the admin setting */
			$tax_id = (int) $odoo_settings[ 'odooTax' ];
				
			$tax_data = $odooApi->fetch_file_record_by_id( 'taxes', 'account.tax', $tax_id );
				
			// $odooApi->addLog('tax data : ' . print_r($tax_data, 1));
				
			if ( isset( $tax_data[ 'fail' ] ) ) {
				$error_msg = '[Order Export] [Error] [Error For Fetching Tax data Msg : ' . print_r( $tax_data[ 'msg' ], true ) . ']';
				$odooApi->addLog( $error_msg );
					
				return false;
			}
			if ( empty( $customer_data[ 'invoice_id' ] ) ) {
				$customer_data[ 'invoice_id' ] = $customer_data[ 'id' ];
			}
				
			$order_data = array(
				'partner_id'         => (int) $customer_data[ 'id' ],
				'partner_invoice_id' => (int) $customer_data[ 'invoice_id' ],
				'state'              => $statuses[ 'order_state' ],
				'note'               => __( 'Woo Order Id : ', 'wc-odoo-integration' ) . $order_id,
				'payment_term_id'    => 1,
				'date_order'         => date_format( $order->get_date_created(), 'Y-m-d H:i:s' ),
			);
				
			if ( 'yes' == $odoo_settings[ 'odoo_fiscal_position' ] && ! empty( $odoo_settings[ 'odoo_fiscal_position_selected' ] ) ) {
				$order_data[ 'fiscal_position_id' ] = $odoo_settings[ 'odoo_fiscal_position_selected' ];
			}
				
			if ( isset( $odoo_settings[ 'odooVersion' ] ) && ( 14 == $odoo_settings[ 'odooVersion' ] ) ) {
				if ( isset( $odoo_settings[ 'gst_treatment' ] ) ) {
					$order_data[ 'l10n_in_gst_treatment' ] = $odoo_settings[ 'gst_treatment' ];
				}
			}
				
			/*
		 Create Sale Order in the Odoo */
			// $odooApi->addLog( 'order data: ' . print_r( $order_data, 1 ) );
			$order_odoo_id = $odooApi->create_record( 'sale.order', $order_data );
			if ( $order_odoo_id->success ) {
				$order_odoo_id = $order_odoo_id->data->odoo_id;
			} else {
				$error_msg = '[Order Export] [Error] [ Something is wrong with the Odoo API Request. API Response : ' . $order_id . ' Msg : ' . print_r( $order_odoo_id->message, true ) . ']';
				$odooApi->addLog( $error_msg );
					
				return false;
			}
				
			opmc_hpos_update_post_meta( $order_id, '_odoo_order_id', $order_odoo_id );
			$invoice_lines = array();
				
			foreach ( $order->get_items() as $item_id => $item ) {
				$product = $item->get_product();
				if ( ! $product || null == $product ) {
					$error_msg = '[Order Sync] [Error] [Invalid Product For Order ' . $order_id . ']';
					$odooApi->addLog( $error_msg );
						
					return false;
				}
				if ( ! $product || $product->get_sku() == '' ) {
					$error_msg = '[Product Export] [Error] [Product : ' . str_replace( ' - ', '-', $product->get_name() ) . ' have missing/invalid SKU. This product will not be exported. ]';
					$odooApi->addLog( $error_msg );
						
					return false;
				}
					
				$product_id = $this->product_id_for_order_line( $item );
				//              $odooApi->addLog('product for order line i: ' . print_r($product_id, 1));
					
				if ( 1 == $tax_data[ 'price_include' ] ) {
					$total_price = $item->get_total() + $item->get_total_tax();
				} else {
					$total_price = $item->get_total();
				}
				$unit_price = number_format( (float) ( $total_price / $item->get_quantity() ), 2, '.', '' );
					
				$order_line = array(
					'order_partner_id' => (int) $customer_data[ 'id' ],
					'order_id'         => $order_odoo_id,
					'product_uom_qty'  => $item->get_quantity(),
					'product_id'       => (int) $product_id,
					'price_unit'       => $unit_price,
				);
					
					
				if ( 'no' == $this->odoo_settings[ 'odoo_fiscal_position' ] ) {
					if ( $item->get_total_tax() > 0 ) {
						$order_line[ 'tax_id' ] = array( array( 6, 0, array( (int) $tax_id ) ) );
					} else {
						$order_line[ 'tax_id' ] = array( array( 6, 0, array() ) );
					}
				}
					
				//              $odooApi->addLog('product for order line i: ' . print_r($order_line, 1));
				$order_line_id = $odooApi->create_record( 'sale.order.line', $order_line );
					
				if ( $order_line_id->success ) {
					$order_line_id = $order_line_id->data->odoo_id;
				} else {
					$error_msg = '[Order Sync] [Error] [Error for Creating  Order line for Product Id  => ' . $product->get_id() . ' Msg : ' . print_r( $order_line_id->message, true ) . ']';
					$odooApi->addLog( $error_msg );
						
					return false;
				}
					
				wc_update_order_item_meta( $item_id, '_order_line_id', $order_line_id );
			}
			if ( $order->get_shipping_total() > 0 ) {
				$shipping_tax_id = (int) $odoo_settings[ 'shippingOdooTax' ];
					
				$shipping_tax_data = $odooApi->fetch_file_record_by_id( 'taxes', 'account.tax', $shipping_tax_id );
				$order_line        = array(
					'order_partner_id' => (int) $customer_data[ 'id' ],
					'order_id'         => $order_odoo_id,
					'product_uom_qty'  => 1,
					'product_id'       => (int) $this->get_delivery_product_id(),
					'tax_id'           => array( array( 6, 0, array( $shipping_tax_id ) ) ),
					'price_unit'       => $order->get_shipping_total(),
				);
					
				$order_line_id = $odooApi->create_record( 'sale.order.line', $order_line );
					
				if ( $order_line_id->success ) {
					$order_line_id = $order_line_id->data->odoo_id;
				} else {
					$error_msg = '[Order Sync] [Error] [Error for Creating  Order line for Product Id  => ' . $product->get_id() . ' Msg : ' . print_r( $order_line_id->message, true ) . ']';
					$odooApi->addLog( $error_msg );
						
					return false;
				}
					
				opmc_hpos_update_post_meta( $order_id, '_order_line_id', $order_line_id );
			}
			// calculate taxes if fiscal positions are enabled
			if ( 'yes' == $odoo_settings[ 'odoo_fiscal_position' ] ) {
				$order_tax_calculations = $odooApi->custom_api_call( 'sale.order', 'validate_taxes_on_sales_order', array( (int) $order_odoo_id ) );
			}
				
			if ( ! empty( $order->get_customer_note() ) ) {
				$order_line    = array(
					'order_partner_id' => (int) $customer_data[ 'id' ],
					'order_id'         => $order_odoo_id,
					'product_uom_qty'  => false,
					'product_id'       => false,
					'display_type'     => 'line_note',
					'name'             => $order->get_customer_note(),
				);
				$order_line_id = $odooApi->create_record( 'sale.order.line', $order_line );
					
				if ( $order_line_id->success ) {
					$order_line_id = $order_line_id->data->odoo_id;
					update_post_meta( $item_id, '_order_note_id', $order_line_id );
				} else {
					$error_msg = '[Order Sync] [Error] [Error for Creating  Order Note For Woo Order  => ' . $order_id . ' Msg : ' . print_r( $order_line_id->message, true ) . ']';
					$odooApi->addLog( $error_msg );
						
					return false;
				}
					
				// wc_update_order_item_meta($item_id, '_order_line_id', $order_line_id);
				// wc_update_order_item_meta($item_id, '_order_note_id', $order_line_id);
			}
				
			// $odooApi->addLog( 'Invoice state : ' . print_r( $statuses['invoice_state'], true ) );
			if ( '' != $statuses[ 'invoice_state' ] ) {
				if ( 'yes' == $this->odoo_settings[ 'odoo_export_invoice' ] ) {
					// $odooApi->addLog( 'Invoice state : ' . print_r( $statuses['invoice_state'], true ) );
					$invoice_id = $this->create_invoice( $order_id );
				}
				if ( '' == $invoice_id ) {
					$error_msg = '[Order Sync] [Error] [Error for Creating Order Invoice For Woo Order  => ' . $order_id . ' Msg : ' . print_r( $invoice_id[ 'msg' ], true ) . ']';
					$odooApi->addLog( $error_msg );
						
					return false;
				}
			}
			opmc_hpos_update_post_meta( $order_id, '_odoo_order_id', $order_odoo_id );
				
			$msg = '[Order Sync] [Complete] [Order #' . $order_id . ' successfully exported to Odoo]';
			$odooApi->addLog( $msg );
		}
			
			
		/**
		 * [create_odoo_invoice]
		 *
		 * @param int $order_id  refunded order id
		 * @param int $refund_id refund id
		 */
		public function create_invoice( $order_id ) {
			$odooApi = new WC_ODOO_API();
			$order   = new WC_Order( $order_id );
			$common  = new WC_ODOO_Common_Functions();
			$helper  = WC_ODOO_Helpers::getHelper();
				
			if ( ! $common->is_authenticate() ) {
				return;
			}
				
			$order_odoo_id = opmc_hpos_get_post_meta( $order_id, '_odoo_order_id', true );
				
			// $odooApi->addLog( 'order_odoo_id : ' . print_r( $order_odoo_id, true ) );
				
			$woo_state = $helper->getState( $order->get_status() );
			$statuses  = $helper->odooStates( $woo_state );
			$odoo_ver  = $helper->odoo_version();
				
			// get user id assocaited with order
			$user          = $order->get_user();
			$customer_data = $this->getCustomerData( $user, $order );	
			$invoice_data = $this->create_invoice_data( (int) $order_odoo_id );
			$invoice_id   = $odooApi->create_record( 'account.move', $invoice_data );
				
			if ( $invoice_id->success ) {
				$invoice_id = $invoice_id->data->odoo_id;
			} else {
				$error_msg = '[Order Sync] [Error] [Error for Creating  Invoice Id  => ' . $order_id . ' Msg : ' . print_r( $invoice_id->message, true ) . ']';
				$odooApi->addLog( $error_msg );
					
				return false;
			}
				
			if ( ! isset( $this->odoo_settings[ 'odooTax' ] ) ) {
				$error_msg = '[Order Sync] [Error] [Invalid Tax Setting For Order Id ' . $order_id . ']';
				$odooApi->addLog( $error_msg );
					
				return false;
			}
			/* get tax id from the admin setting */
			$tax_id = (int) $this->odoo_settings[ 'odooTax' ];
				
			$tax_data = $odooApi->fetch_file_record_by_id( 'taxes', 'account.tax', $tax_id );
				
			if ( isset( $tax_data[ 'fail' ] ) ) {
				$error_msg = '[Order Sync] [Error] [Error For Fetching Tax data Msg : ' . print_r( $tax_data[ 'msg' ], true ) . ']';
				$odooApi->addLog( $error_msg );
					
				return false;
			}
			$invoice_lines = array();
				
			foreach ( $order->get_items() as $item_id => $item ) {
				$product = $item->get_product();
					
				$order_line_id = wc_get_order_item_meta( $item_id, '_order_line_id' );
				// $odooApi->addLog( 'order_line_id : ' . print_r( $order_line_id, true ) );
					
				$conditions = array(
					array(
						'field_key'   => $this->odoo_sku_mapping,
						'field_value' => $product->get_sku(),
					),
				);
					
				$product_id = $odooApi->search_record( 'product.product', $conditions );
				if ( $product_id->success && ! empty( $product_id->data->items ) ) {
					$product_id = $product_id->data->items[ 0 ];
				} else {
					$odooApi->addLog( '[Order Sync] [Error] [Product not found for Invoice!!]' );
						
					return;
				}
					
				if ( 1 == $tax_data[ 'price_include' ] ) {
					$total_price = $item->get_total() + $item->get_total_tax();
				} else {
					$total_price = $item->get_total();
				}
				$unit_price = number_format( (float) ( $total_price / $item->get_quantity() ), 2, '.', '' );
					
				if ( 'yes' == $this->odoo_settings[ 'odoo_export_invoice' ] ) {
					$invoice_line_data = array(
						'partner_id'    => (int) $customer_data[ 'id' ],
						'move_id'       => $invoice_id,
						'price_unit'    => $unit_price,
						'quantity'      => $item->get_quantity(),
						'product_id'    => $product_id,
						'sale_line_ids' => array( array( 6, 0, array( (int) $order_line_id ) ) ),
					);
					if ( 'no' == $this->odoo_settings[ 'odoo_fiscal_position' ] ) {
						$invoice_line_data[ 'tax_ids' ] = array( array( 6, 0, array( (int) $tax_id ) ) );
							
						if ( $item->get_total_tax() > 0 ) {
							$invoice_line_data[ 'tax_ids' ] = array( array( 6, 0, array( (int) $tax_id ) ) );
						} else {
							$invoice_line_data[ 'tax_ids' ] = array( array( 6, 0, array() ) );
						}
					}
					$invoice_lines[] = $odooApi->create_record( 'account.move.line', $invoice_line_data );
					// $invoice_lines[] = $invoice_line_data;
				}
			}
				
			if ( $order->get_shipping_total() > 0 ) {
				$shipping_tax_id = (int) $this->odoo_settings[ 'shippingOdooTax' ];
					
				$shipping_tax_data = $odooApi->fetch_file_record_by_id( 'taxes', 'account.tax', $shipping_tax_id );
					
				$order_line_id = opmc_hpos_get_post_meta( $order_id, '_order_line_id', true );
					
				// $odooApi->addLog( 'Shipping line : ' . print_r( $order_line_id, true ) );
					
				if ( 'yes' == $this->odoo_settings[ 'odoo_export_invoice' ] ) {
					$invoice_line_data = array(
						'partner_id'    => (int) $customer_data[ 'id' ],
						'move_id'       => $invoice_id,
						'price_unit'    => $order->get_shipping_total(),
						'quantity'      => 1,
						'product_id'    => (int) $this->get_delivery_product_id(),
						'tax_ids'       => array( array( 6, 0, array( (int) $shipping_tax_id ) ) ),
						'sale_line_ids' => array( array( 6, 0, array( (int) $order_line_id ) ) ),
					);
					$invoice_lines[]   = $odooApi->create_record( 'account.move.line', $invoice_line_data );
				}
			}
				
			if ( ! empty( $order->get_customer_note() ) ) {
				if ( 'yes' == $this->odoo_settings[ 'odoo_export_invoice' ] ) {
					$order_line_id = opmc_hpos_get_post_meta( $order_id, '_order_note_id', true );
					// $odooApi->addLog( 'order note line : ' . print_r( $order_line_id, true ) );
					$invoice_line_data = array(
						'partner_id'    => (int) $customer_data[ 'id' ],
						'move_id'       => $invoice_id,
						'price_unit'    => false,
						'quantity'      => false,
						'product_id'    => false,
						'sale_line_ids' => array( array( 6, 0, array( (int) $order_line_id ) ) ),
						'display_type'  => 'line_note',
						'name'          => $order->get_customer_note(),
					);
					$invoice_lines[]   = $odooApi->create_record( 'account.move.line', $invoice_line_data );
				}
			}
				
			if ( count( $invoice_lines ) > 0 && ( 'yes' == $this->odoo_settings[ 'odoo_export_invoice' ] ) ) {
				// $invoice_data = $this->create_invoice_data($customer_data, (int) $order_odoo_id);
				// $invoice_data['invoice_line_ids'] = $invoice_lines;
				// $invoice_id = $odooApi->create_record('account.move', $invoice_data);
					
				// if (isset($invoice_id['fail'])) {
				// $error_msg = 'Error for Creating  Invoice Id  => ' . $order_id . ' Msg : ' . print_r($invoice_id['msg'], true);
				// $odooApi->addLog($error_msg);
				// return false;
				// }
				$odoo_order = $this->update_record( 'sale.order', (int) $order_odoo_id, array( 'state' => $statuses[ 'order_state' ] ) );
				// $odooApi->addLog( 'order update: ' . print_r( $odoo_order, true ) );
					
				if ( $helper->is_inv_mark_paid() ) {
					$invoice = $odooApi->update_record( 'account.move', (int) $invoice_id, array( 'state' => $statuses[ 'invoice_state' ] ) );
					if ( 13 === $odoo_ver ) {
						$invoice = $odooApi->update_record( 'account.move', (int) $invoice_id, array( 'invoice_payment_state' => $statuses[ 'payment_state' ] ) );
					} else {
						$invoice = $odooApi->update_record( 'account.move', (int) $invoice_id, array( 'payment_state' => $statuses[ 'payment_state' ] ) );
					}
				} else {
					$invoice = $odooApi->update_record( 'account.move', (int) $invoice_id, array( 'state' => 'draft' ) );
					if ( 13 === $odoo_ver ) {
						$invoice = $odooApi->update_record( 'account.move', (int) $invoice_id, array( 'invoice_payment_state' => 'not_paid' ) );
					} else {
						$invoice = $odooApi->update_record( 'account.move', (int) $invoice_id, array( 'payment_state' => 'not_paid' ) );
					}
				}
					
				if ( ! $invoice->success ) {
					$error_msg = '[Order Sync] [Error] [Error for Creating  Invoice  for Order Id  => ' . $order_id . ' Msg : ' . print_r( $invoice->message, true ) . ']';
					$odooApi->addLog( $error_msg );
						
					return false;
				}
					
				$invoice_url = $this->create_pdf_download_link( $invoice_id );
				if ( isset( $invoice_data[ 'invoice_origin' ] ) && ! empty( $invoice_data[ 'invoice_origin' ] ) ) {
					$order_origin = $invoice_data[ 'invoice_origin' ];
					opmc_hpos_update_post_meta( $order_id, '_odoo_order_origin', $order_origin );
				}
				opmc_hpos_update_post_meta( $order_id, '_odoo_invoice_id', $invoice_id );
				opmc_hpos_update_post_meta( $order_id, '_odoo_invoice_url', $invoice_url );
					
				return $invoice_id;
			}
		}
			
			
		/**
		 * [create_odoo_refund description]
		 *
		 * @param int $order_id  refunded order id
		 * @param int $refund_id refund id
		 */
		public function create_odoo_refund( $order_id, $refund_id ) {
			$odooApi = new WC_ODOO_API();
				
			$refund          = new WC_Order_Refund( $refund_id );
			$odoo_invoice_id = opmc_hpos_get_post_meta( $refund->get_parent_id(), '_odoo_invoice_id', true );
			$odoo_order_id   = opmc_hpos_get_post_meta( $refund->get_parent_id(), '_odoo_order_id', true );
				
			if ( ! $odoo_order_id || ! $odoo_invoice_id ) {
				$odooApi->addLog( '[Order Sync] [Error] [Order Not found on Odoo for refund!' );
					
				return;
			}
				
			$odoo_return_inv_id   = opmc_hpos_get_post_meta( $refund_id, '_odoo_return_invoice_id', true );
			$odoo_return_inv_url  = opmc_hpos_get_post_meta( $refund_id, '_odoo_return_invoice_url', true );
			$odoo_return_order_id = opmc_hpos_get_post_meta( $refund_id, '_odoo_return_order_id', true );
				
			if ( $odoo_return_inv_id ) {
				$odooApi->addLog( '[Order Sync] [Info] [Refund already exported]' );
					
				return;
			} else {
				$odoo_return_inv_id   = opmc_hpos_get_post_meta( $order_id, '_odoo_return_invoice_id', true );
				$odoo_return_inv_url  = opmc_hpos_get_post_meta( $order_id, '_odoo_return_invoice_url', true );
				$odoo_return_order_id = opmc_hpos_get_post_meta( $order_id, '_odoo_return_order_id', true );
				if ( $odoo_return_inv_id ) {
					opmc_hpos_update_post_meta( $refund_id, '_odoo_return_invoice_id', $odoo_return_inv_id );
					opmc_hpos_update_post_meta( $refund_id, '_odoo_return_invoice_url', $odoo_return_inv_url );
					opmc_hpos_update_post_meta( $refund_id, '_odoo_return_order_id', $odoo_return_order_id );
					opmc_hpos_delete_post_meta( $order_id, '_odoo_return_invoice_id' );
					opmc_hpos_delete_post_meta( $order_id, '_odoo_return_invoice_url' );
					opmc_hpos_delete_post_meta( $order_id, '_odoo_return_order_id' );
						
					return;
				}
			}
			// $odoo_refund_invoice_id = $this->create_refund_invoice($odoo_invoice_id);
			$odoo_refund_invoice_data = $this->create_refund_invoice_data( $odoo_invoice_id );
			// $odooApi->addLog( 'refund invoice data : ' . print_r( $odoo_refund_invoice_data, 1 ) );
				
			$refund_order  = new WC_Order( $refund->get_parent_id() );
			$user          = $refund_order->get_user();
//          $customer_data = $this->getCustomerData( $user, $refund_order );
				
			$refund_item_id = true;
			if ( ! $refund->get_items() ) {
				$refund         = $refund_order;
				$refund_item_id = false;
			}
				
			$odoo_refund_invoice_id = $odooApi->create_record( 'account.move', $odoo_refund_invoice_data );
			// $odooApi->addLog( 'refund invoice id : ' . print_r( $odoo_refund_invoice_id, 1 ) );
			if ( $odoo_refund_invoice_id->success ) {
				$odoo_refund_invoice_id = $odoo_refund_invoice_id->data->odoo_id;
			} else {
				$error_msg = '[Order Sync] [Error] [Error for Creating refund Invoice Id  => ' . $order_id . ' Msg : ' . print_r( $odoo_refund_invoice_id->message, true ) . ']';
				$odooApi->addLog( $error_msg );
					
				return false;
			}
				
			// $wc_setting = new WC_ODOO_Integration_Settings();
			$wc_setting = get_option( 'woocommerce_woocommmerce_odoo_integration_settings' );
				
			$tax_id   = (int) $wc_setting[ 'odooTax' ];
			$tax_data = $odooApi->fetch_file_record_by_id( 'taxes', 'account.tax', $tax_id );
			// $odooApi->addLog('tax response : '. print_r($tax_data,1));
				
			$refund_invoice_lines = array();
			foreach ( $refund->get_items() as $item_id => $item ) {
				if ( 0 == abs( $item->get_quantity() ) ) {
					$odooApi->addLog( '[Order Sync] [Error] [Refunded order Export canceled because the refund quantity is equal to 0. ]' );
					continue;
				}
					
				$refunded_quantity      = $item->get_quantity();
				$refunded_line_subtotal = abs( $item->get_subtotal() );
				$refunded_item_id       = ( $refund_item_id ) ? $item->get_meta( '_refunded_item_id' ) : $item_id;
				$order_line_id          = wc_get_order_item_meta( $refunded_item_id, '_order_line_id', true );
				$odd_order_line_id      = wc_get_order_item_meta( $refunded_item_id, '_invoice_line_id', true );
				$odoo_product_id        = get_post_meta( $item->get_product_id(), '_odoo_id', true );
					
				// $invoice_line_item = $this->create_return_invoice_line_base_on_tax($odoo_refund_invoice_id, $item, $odoo_product_id, $customer_data, $tax_data);
					
				if ( 1 == $tax_data[ 'price_include' ] ) {
					$total_price = abs( $item->get_total() ) + abs( $item->get_total_tax() );
				} else {
					$total_price = abs( $item->get_total() );
				}
				$unit_price = number_format( (float) ( $total_price / abs( $item->get_quantity() ) ), 2, '.', '' );
					
				$refund_invoice_line_data = array(
					'partner_id'    => $odoo_refund_invoice_data[ 'partner_id' ],
					'move_id'       => $odoo_refund_invoice_id,
					'price_unit'    => $unit_price,
					'quantity'      => absint( $item->get_quantity() ),
					'product_id'    => (int) $odoo_product_id,
					'sale_line_ids' => array( array( 6, 0, array( (int) $order_line_id ) ) ),
				);
					
				if ( 'no' == $this->odoo_settings[ 'odoo_fiscal_position' ] ) {
					if ( abs( $item->get_total_tax() ) > 0 ) {
						$refund_invoice_line_data[ 'tax_ids' ] = array( array( 6, 0, array( (int) $tax_id ) ) );
					} else {
						$refund_invoice_line_data[ 'tax_ids' ] = array( array( 6, 0, array() ) );
					}
				}
				$refund_invoice_line_id = $odooApi->create_record( 'account.move.line', $refund_invoice_line_data );
				if ( $refund_invoice_line_id->success ) {
					$refund_invoice_lines[] = $refund_invoice_line_id->data->odoo_id;
					wc_update_order_item_meta( $item_id, '_return_order_line_id', $refund_invoice_line_id->data->odoo_id );
				}
			}
				
			if ( isset( $this->odoo_settings[ 'odoo_mark_invoice_paid' ] ) && 'yes' == $this->odoo_settings[ 'odoo_mark_invoice_paid' ] ) {
				$odoo_refund_invoice = $this->update_record( 'account.move', $odoo_refund_invoice_id, array( 'state' => 'posted' ) );
			} else {
				$odoo_refund_invoice = $this->update_record( 'account.move', $odoo_refund_invoice_id, array( 'state' => 'draft' ) );
			}
			if ( ! $odoo_refund_invoice->success ) {
				$error_msg = '[Order Sync] [Error] [Error Update Refund Invoice For Invoice Id  => ' . $order_id . ' Msg : ' . print_r( $odoo_refund_invoice->message, true ) . ']';
				$odooApi->addLog( $error_msg );
					
				return false;
			}
				
			if ( isset( $this->odoo_settings[ 'odoo_mark_invoice_paid' ] ) && 'yes' == $this->odoo_settings[ 'odoo_mark_invoice_paid' ] ) {
				if ( isset( $odoo_settings[ 'odooVersion' ] ) && 13 == $odoo_settings[ 'odooVersion' ] ) {
					$odoo_refund_invoice = $this->update_record( 'account.move', $odoo_refund_invoice_id, array( 'invoice_payment_state' => 'paid' ) );
				} else {
					$odoo_refund_invoice = $this->update_record( 'account.move', $odoo_refund_invoice_id, array( 'payment_state' => 'in_payment' ) );
				}
			} elseif ( isset( $odoo_settings[ 'odooVersion' ] ) && 13 == $odoo_settings[ 'odooVersion' ] ) {
				$odoo_refund_invoice = $this->update_record( 'account.move', $odoo_refund_invoice_id, array( 'invoice_payment_state' => 'not_paid' ) );
			} else {
				$odoo_refund_invoice = $this->update_record( 'account.move', $odoo_refund_invoice_id, array( 'payment_state' => 'not_paid' ) );
			}
				
			if ( ! $odoo_refund_invoice->success ) {
				$error_msg = '[Order Sync] [Error] [Error for Creating  Invoice  for Order Id  => ' . $order_id . ' Msg : ' . print_r( $odoo_refund_invoice->message, true ) . ']';
				$odooApi->addLog( $error_msg );
					
				return false;
			}
			$invoice_url = $this->create_pdf_download_link( $odoo_refund_invoice_id );
			opmc_hpos_update_post_meta( $refund_id, '_odoo_return_invoice_id', $odoo_refund_invoice_id );
			opmc_hpos_update_post_meta( $refund_id, '_odoo_return_invoice_url', $invoice_url );
			opmc_hpos_update_post_meta( $refund_id, '_odoo_return_order_id', $odoo_order_id );
		}
			
		public function create_extra_price( $odoo_product_id, $product ) {
			$data           = array(
				'fixed_price'     => number_format($product->get_sale_price(), 2), // 'min_quantity' => 0,
				'pricelist_id'    => 1,
				'product_tmpl_id' => $odoo_product_id,
				'product_id'      => $odoo_product_id,
				'applied_on'      => '1_product',
			);
			$odooApi        = new WC_ODOO_API();
			$extra_price_id = $odooApi->create_record( 'product.pricelist.item', $data );
			if ( $extra_price_id->success ) {
				update_post_meta( $product->get_id(), '_product_extra_price_id', $extra_price_id->data->odoo_id );
			} else {
				$error_msg = '[Product Sync] [Error] [Error for Creating  Extra Price For Product Id  => ' . $product->get_id() . ' Msg :  ' . print_r( $extra_price_id->message, true ) . ']';
				$odooApi->addLog( $error_msg );
					
				return false;
			}
		}
			
			
		public function update_extra_price( $extra_price_id, $product ) {
			$odooApi = new WC_ODOO_API();
			// $odooApi->addLog( 'sale price : ' . print_r($extra_price_id, 1) );
			$data               = array(
				'fixed_price' => number_format($product->get_sale_price(), 2),
			);
			$extra_price_update = $odooApi->update_record( 'product.pricelist.item', (int) $extra_price_id, $data );
			if ( $extra_price_update->success ) {
				update_post_meta( $product->get_id(), '_product_extra_price_id', $extra_price_id );
			} else {
				$error_msg = '[Product Sync] [Error] [Error for Creating Extra Price For Product Id  => ' . $product->get_id() . ' Msg :  ' . print_r( $extra_price_update->message, true ) . ']';
				$odooApi->addLog( $error_msg );
					
				return false;
			}
		}
			
		public function get_and_set_variant_price( $post_id, $odoo_product ) {
			$odooApi     = new WC_ODOO_API();
			$conditions  = array(
				array(
					'field_key'   => 'product_id',
					'field_value' => (int) $odoo_product[ 'id' ],
				),
			);
			$price_lists = $odooApi->readAll( 'product.pricelist.item', array(), $conditions );
			//             $odooApi->addLog(print_r($post_id, 1).' price list items : '. print_r($price_lists, 1));
			if ( $price_lists->success ) {
				$price_lists = json_decode( json_encode( $price_lists->data->items ), true );
			} else {
				$error_msg = 'Unable to get Extra Price For Product Id  => ' . $post_id . ' Msg : ' . print_r( $price_lists->message, true );
				$odooApi->addLog( $error_msg );
					
				return false;
			}
			if ( isset( $price_lists[ 0 ][ 'fixed_price' ] ) ) {
				//              if ( $odoo_product['list_price'] > $price_lists[0]['fixed_price'] ) {
				update_post_meta( $post_id, '_regular_price', $price_lists[ 0 ][ 'fixed_price' ] );
				//                  update_post_meta($post_id, '_sale_price', $price_lists[0]['fixed_price']);
				update_post_meta( $post_id, '_price', $price_lists[ 0 ][ 'fixed_price' ] );
				update_post_meta( $post_id, '_product_extra_price_id', $price_lists[ 0 ][ 'id' ] );
				//              } else {
				//                  $error_msg = 'Extra Price Is Greater than Regular Price For Product Id  => ' . $post_id;
				//                  $odooApi->addLog( $error_msg );
				//                  return false;
				//              }
			}
		}
			
		public function get_and_set_sale_price( $post_id, $odoo_product, $variation = false ) {
			$odooApi    = new WC_ODOO_API();
			$field_key  = 'product_tmpl_id';
			$list_price = $odoo_product[ 'list_price' ];
			if ( $variation ) {
				$field_key  = 'product_id';
				$list_price = $odoo_product[ 'regular_price' ];
			}
			$conditions = array(
				array(
					'field_key'   => $field_key,
					'field_value' => (int) $odoo_product[ 'id' ],
				),
			);
				
			$price_lists = $odooApi->readAll( 'product.pricelist.item', array(), $conditions );
			if ( $price_lists->success ) {
				$price_lists = json_decode( json_encode( $price_lists->data->items ), true );
			} else {
				$error_msg = '[Product Sync] [Error] [Unable to get Extra Price For Product Id  => ' . $post_id . ' Msg : ' . print_r( $price_lists->message, true ) . ']';
				$odooApi->addLog( $error_msg );
					
				return false;
			}
			if ( isset( $price_lists[ 0 ][ 'fixed_price' ] ) ) {
				if ( $list_price > $price_lists[ 0 ][ 'fixed_price' ] ) {
					update_post_meta( $post_id, '_sale_price', $price_lists[ 0 ][ 'fixed_price' ] );
					update_post_meta( $post_id, '_price', $price_lists[ 0 ][ 'fixed_price' ] );
					update_post_meta( $post_id, '_product_extra_price_id', $price_lists[ 0 ][ 'id' ] );
				} else {
					$error_msg = '[Product Sync] [Error] [Extra Price Is Greater than Regular Price For Product Id  => ' . $post_id . ']';
					$odooApi->addLog( $error_msg );
						
					return false;
				}
			}
		}
			
		public function search_odoo_customer( $conditions, $customer_id ) {
			$odooApi  = new WC_ODOO_API();
			$customer = $odooApi->search_record( 'res.partner', $conditions );
			if ( $customer->success ) {
				return json_decode( json_encode( $customer->data->items ), true );
			} else {
				$error_msg = '[Customer Sync] [Error] [Error In Customer Search Customer Id  => ' . $customer_id . ' Msg : ' . print_r( $customer->message, true ) . ']';
				$odooApi->addLog( $error_msg );
					
				return false;
			}
		}
			
		public function search_odoo_product( $conditions, $product_id ) {
			$odooApi          = new WC_ODOO_API();
			$product_response = $odooApi->search_record( 'product.product', $conditions );
			// $odooApi->addLog( 'Serach Products : ' . print_r( $product_response, true ) );
			if ( $product_response->success ) {
				return json_decode( json_encode( $product_response->data->items ), true );
			} else {
				$error_msg = '[Product Sync] [Error] [Error In product Search product Id  => ' . $product_id . ' Msg : ' . print_r( $product_response->message, true ) . ']';
				$odooApi->addLog( $error_msg );
					
				return false;
			}
		}
			
		public function can_create_address( $user_id, $address, $type ) {
			if ( empty( $address[ 'address_1' ] ) || empty( $address[ 'postcode' ] ) || ( empty( $address[ 'first_name' ] ) && empty( $address[ 'last_name' ] ) ) ) {
				$odooApi   = new WC_ODOO_API();
				$error_msg = '[Customer Sync] [Error] [Unable to create customer ' . $type . ' address for customer Id  => ' . $user_id . ' Msg : Required Fields are missing]';
				$odooApi->addLog( $error_msg );
					
				return false;
			}
				
			return true;
		}
			
			
		public function opmc_odoo_order_status( $order_id, $from_status, $to_status ) {
			if ( is_admin() && defined( 'DOING_AJAX' ) && DOING_AJAX ) {
				$order = new WC_Order( $order_id );
				if ( 'refunded' === $from_status ) {
					$order->update_status( 'refunded', __( 'Order status can\'t be changed from refunded.', 'wc-odoo-integration' ) );
						
					return false;
				}
				$odooApi              = new WC_ODOO_API();
				$helper               = WC_ODOO_Helpers::getHelper();
				$woo_state            = $helper->getState( $to_status );
				$statuses             = $helper->odooStates( $woo_state );
				$export_inv_enable    = $helper->is_export_inv();
				$inv_mark_paid        = $helper->is_inv_mark_paid();
				$odoo_ver             = $helper->odoo_version();
				$opmc_odoo_order_id   = $order->get_meta( '_odoo_order_id' );
				$opmc_odoo_invoice_id = $order->get_meta( '_odoo_invoice_id' );
				// $odooApi->addLog( 'odoo order Id : ' . print_r( $opmc_odoo_order_id, 1 ) );
					
				if ( $opmc_odoo_order_id && $opmc_odoo_invoice_id ) {
					return false;
				}
					
				if ( 'shop_order' != $order->get_type() ) {
					return false;
				}
				if ( 'no' == $this->odoo_settings[ 'odoo_export_order_on_checkout' ] ) {
					return false;
				}
					
				// $odooApi->addLog( print_r( $statuses, true ) );
					
				$odoo_order_syced = opmc_hpos_get_post_meta( $order_id, '_odoo_order_id', true );
				$odoo_invoice_id  = opmc_hpos_get_post_meta( $order_id, '_odoo_invoice_id', true );
					
				// $odooApi->addLog( 'odoo Order ID : ' . print_r( $odoo_order_syced, true ) );
					
				if ( $export_inv_enable ) {
					if ( '' != $odoo_order_syced && '' == $odoo_invoice_id ) {
						$odoo_order = $odooApi->update_record( 'sale.order', (int) $odoo_order_syced, array( 'state' => $statuses[ 'order_state' ] ) );
						if ( '' != $statuses[ 'invoice_state' ] ) {
							$invoice = $this->create_invoice( $order_id );
							if ( isset( $invoice[ 'fail' ] ) ) {
								$error_msg = '[Order Sync] [Error] [Error Create Invoice For order ID  => ' . $order_id . ' Msg : ' . print_r( $invoice[ 'msg' ], true ) . ']';
								$odooApi->addLog( $error_msg );
									
								return false;
							}
						}
					} elseif ( '' != $odoo_order_syced && '' != $odoo_invoice_id ) {
						$odoo_order = $odooApi->update_record( 'sale.order', (int) $odoo_order_syced, array( 'state' => $statuses[ 'order_state' ] ) );
							
						if ( $inv_mark_paid ) {
							$invoice = $odooApi->update_record( 'account.move', (int) $odoo_invoice_id, array( 'state' => $statuses[ 'invoice_state' ] ) );
							if ( 13 === $odoo_ver ) {
								$invoice = $odooApi->update_record( 'account.move', (int) $odoo_invoice_id, array( 'invoice_payment_state' => $statuses[ 'payment_state' ] ) );
							} else {
								$invoice = $odooApi->update_record( 'account.move', (int) $odoo_invoice_id, array( 'payment_state' => $statuses[ 'payment_state' ] ) );
							}
						} else {
							$invoice = $odooApi->update_record( 'account.move', (int) $odoo_invoice_id, array( 'state' => 'draft' ) );
							if ( 13 === $odoo_ver ) {
								$invoice = $odooApi->update_record( 'account.move', (int) $odoo_invoice_id, array( 'invoice_payment_state' => 'not_paid' ) );
							} else {
								$invoice = $odooApi->update_record( 'account.move', (int) $odoo_invoice_id, array( 'payment_state' => 'not_paid' ) );
							}
						}
					} else {
						$this->order_create( $order_id );
					}
				}
			}
		}
			
			
		public function odoo_export_product_by_date() {
			if ( ! check_ajax_referer( 'odoo_security', 'security', false ) ) {
				wp_send_json(
					array(
						'threads' => array(),
						'subject' => '',
						'error'   => __( 'There was security vulnerability issues in your request.', 'wc-odoo-integration' ),
					) );
				exit;
			}
				
			global $wpdb;
			$date_from = ! empty( $_POST[ 'dateFrom' ] ) ? sanitize_text_field( $_POST[ 'dateFrom' ] ) : '';
			$date_to   = ! empty( $_POST[ 'dateTo' ] ) ? sanitize_text_field( $_POST[ 'dateTo' ] ) : '';
			if ( '' != $date_from ) {
				$date_from = gmdate( 'Y-m-d', strtotime( '-1 day', strtotime( $date_from ) ) );
			}
			if ( '' != $date_to ) {
				$date_to = gmdate( 'Y-m-d', strtotime( '1 day', strtotime( $date_to ) ) );
			}
				
			$exlucde_cats = $this->odoo_settings[ 'odoo_exclude_product_category' ];
			$query_string = array(
				'post_type'      => 'product',
				'date_query'     => array(
					'column' => 'post_date',
					'after'  => $date_from,
					'before' => $date_to,
				),
				'fields'         => 'ids',
				'post_status'    => 'publish',
				'order'          => 'ASC',
				'posts_per_page' => - 1,
				'tax_query'      => array(
					array(
						'taxonomy' => 'product_cat',
						'field'    => 'term_id',
						'terms'    => $exlucde_cats,
						'operator' => 'NOT IN',
					),
				),
			);
				
			$products_q = new WP_Query( $query_string );
			$products   = $products_q->posts;
				
			$odooApi        = new WC_ODOO_API();
			$product_added  = 0;
			$product_upated = 0;
			foreach ( $products as $key => $product_obj ) {
				$product = wc_get_product( $product_obj );
					
				$syncable_product = get_post_meta( $product->get_id(), '_exclude_product_to_sync', true );
					
				if ( 'yes' == $syncable_product ) {
					continue;
				}
					
				if ( $product->has_child() ) {
					// continue;
					$odoo_template_id = get_post_meta( $product->get_id(), '_odoo_id', true );
					if ( $odoo_template_id ) {
						$this->do_export_variable_product_update( (int) $odoo_template_id, $product );
					} else {
						$this->do_export_variable_product( $product );
					}
				} else {
					$odoo_product_id = get_post_meta( $product->get_id(), '_odoo_id', true );
					// Search Product on Odoo
					if ( ! $odoo_product_id ) {
						$conditions      = array(
							array(
								'field_key'   => $this->odoo_sku_mapping,
								'field_value' => $product->get_sku(),
							),
						);
						$odoo_product_id = $this->search_odoo_product( $conditions, $product->get_id() );
					}
						
					if ( $odoo_product_id ) {
						$this->update_odoo_product( (int) $odoo_product_id, $product );
						$product_upated++;
					} else {
						$odoo_product_id = $this->create_product( $product );
						$product_added++;
					}
					$odoo_product_id = json_decode(json_encode($odoo_product_id), true);
					if ( isset( $odoo_product_id[ 'fail' ] ) ) {
						$error_msg = '[Product Sync] [Error] [Error for Creating/Updating  Product Id  => ' . $product->get_id() . ' Msg : ' . print_r( $odoo_product_id[ 'msg' ], true ) . ']';
						$odooApi->addLog( $error_msg );
						continue;
					}
					if ( false == $odoo_product_id ) {
						continue;
					}
					update_post_meta( $product->get_id(), '_odoo_id', $odoo_product_id );
					if ( 'yes' == $this->odoo_settings[ 'odoo_export_update_price' ] ) {
						if ( $product->is_on_sale() ) {
							$odoo_extra_product = get_post_meta( $product->get_id(), '_product_extra_price_id', true );
							if ( $odoo_extra_product ) {
								$this->update_extra_price( $odoo_extra_product, $product );
							} else {
								$this->create_extra_price( $odoo_product_id, $product );
							}
						}
					}
					if ( 'yes' == $this->odoo_settings[ 'odoo_export_update_stocks' ] ) {
						if ( $product->get_stock_quantity() > 0 ) {
							$product_qty = number_format( (float) $product->get_stock_quantity(), 2, '.', '' );
							$res         = $this->update_product_quantity( $odoo_product_id, $product_qty );
						}
					}
					update_post_meta( $product->get_id(), '_odoo_image_id', $product->get_image_id() );
				}
			}
			echo json_encode(
				array(
					'result'         => 'success',
					'product_added'  => $product_added,
					'product_upated' => $product_upated,
					'total_product'  => count( $products ),
				) );
			exit;
		}
			
			
		public function odoo_export_customer_by_date() {
			if ( ! check_ajax_referer( 'odoo_security', 'security', false ) ) {
				wp_send_json(
					array(
						'threads' => array(),
						'subject' => '',
						'error'   => 'There was security vulnerability issues in your request.',
					) );
				exit;
			}
			global $wpdb;
				
			$date_from = ! empty( $_POST[ 'dateFrom' ] ) ? sanitize_text_field( $_POST[ 'dateFrom' ] ) : '';
			$date_to   = ! empty( $_POST[ 'dateTo' ] ) ? sanitize_text_field( $_POST[ 'dateTo' ] ) : '';
			if ( '' != $date_from ) {
				$date_from = gmdate( 'Y-m-d', strtotime( '-1 day', strtotime( $date_from ) ) );
			}
			if ( '' != $date_to ) {
				$date_to = gmdate( 'Y-m-d', strtotime( '1 day', strtotime( $date_to ) ) );
			}
				
			$args          = array(
				'role'           => 'customer',
				'date_query'     => array(
					'after'     => $date_from,
					'before'    => $date_to,
					'inclusive' => false,
				),
				'order'          => 'ASC',
				'orderby'        => 'ID',
				'posts_per_page' => - 1,
			);
			$wp_user_query = new WP_User_Query( $args );
			$customers     = $wp_user_query->get_results();
				
			$customer_added  = 0;
			$customer_upated = 0;
			$email           = array();
			foreach ( $customers as $key => $customer ) {
				if ( '' != $customer->user_email ) {
					$customer_id = get_user_meta( $customer->ID, '_odoo_id', true );
					array_push( $email, $customer->user_email );
						
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
						$customer_id = $this->search_odoo_customer( $conditions, $customer->ID );
					}
					if ( $customer_id ) {
						$this->update_customer_to_odoo( (int) $customer_id, $customer );
						$customer_upated++;
					} else {
						$customer_id = $this->create_customer( $customer );
						$customer_added++;
					}
					if ( false == $customer_id ) {
						continue;
					}
					update_user_meta( $customer->ID, '_odoo_id', $customer_id );
					$this->action_woocommerce_customer_save_address( $customer->ID, 'shipping' );
					$this->action_woocommerce_customer_save_address( $customer->ID, 'billing' );
				}
			}
				
			echo json_encode(
				array(
					'result'          => 'success',
					'customer_added'  => $customer_added,
					'customer_upated' => $customer_upated,
					'total_customer'  => count( $customers ),
				) );
			exit;
		}
			
			
		public function odoo_import_customer_by_date() {
			if ( ! check_ajax_referer( 'odoo_security', 'security', false ) ) {
				wp_send_json(
					array(
						'threads' => array(),
						'subject' => '',
						'error'   => 'There was security vulnerability issues in your request.',
					) );
				exit;
			}
			global $wpdb;
			$date_from = ! empty( $_POST[ 'dateFrom' ] ) ? sanitize_text_field( $_POST[ 'dateFrom' ] ) : '';
			$date_to   = ! empty( $_POST[ 'dateTo' ] ) ? sanitize_text_field( $_POST[ 'dateTo' ] ) : '';
			$odooApi   = new WC_ODOO_API();
			$customers = $odooApi->readAll( 'res.partner', array(
				'create_date',
				'write_date',
				'id',
				'name',
				'display_name',
				'website',
				'mobile',
				'email',
				'is_company',
				'phone',
				'image_medium',
				'street',
				'street2',
				'zip',
				'city',
				'state_id',
				'country_id',
				'child_ids',
				'type',
			), array(
												array( 'type', '=', 'contact' ),
												array( 'create_date', '>=', $date_from ),
												array( 'create_date', '<=', $date_to ),
											) );
			$email     = array();
			if ( ! isset( $customers[ 'fail' ] ) && is_array( $customers ) && count( $customers ) ) {
				foreach ( $customers as $key => $customer ) {
					if ( isset( $customer[ 'email' ] ) && ! empty( $customer[ 'email' ] ) ) {
						$address_lists = array();
						if ( count( $customer[ 'child_ids' ] ) > 0 ) {
							$address_lists = $odooApi->fetch_record_by_ids( 'res.partner', $customer[ 'child_ids' ], array(
								'id',
								'name',
								'display_name',
								'website',
								'mobile',
								'email',
								'is_company',
								'phone',
								'image_medium',
								'street',
								'street2',
								'zip',
								'city',
								'state_id',
								'country_id',
								'child_ids',
								'type',
							) );
							if ( isset( $address_lists[ 'fail' ] ) ) {
								continue;
							}
						}
						$this->sync_customer_to_wc( $customer, $address_lists );
						array_push( $email, $customer[ 'email' ] );
					}
				}
			}
				
			echo json_encode(
				array(
					'result'         => 'success',
					'error'          => $customers[ 'msg' ],
					'total_customer' => count( $customers ),
				) );
			exit;
		}
	}
		
	new WC_ODOO_Functions();
	endif;
