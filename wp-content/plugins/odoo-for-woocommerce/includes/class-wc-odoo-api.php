<?php
	
	
	
class WC_ODOO_API {

		
		
	protected $odoo_url;
	protected $odoo_db;
	protected $odoo_username;
	protected $odoo_password;
	protected $odoo_creds_available;
	protected $settings_changed;
	protected $opmc_odoo_api_url;
		
	public function __construct() {
			
		$this->odoo_creds_available = false;
		$this->opmc_odoo_api_url    = 'https://odooconnector.nicer10.com/api/';
		$creds                      = get_option('woocommerce_woocommmerce_odoo_integration_settings');
		$this->odoo_url             = isset($creds[ 'client_url' ]) ? rtrim($creds[ 'client_url' ], '/') : '';
		$this->odoo_db              = isset($creds[ 'client_db' ]) ? $creds[ 'client_db' ] : '';
		$this->odoo_company         = isset($creds[ 'companyFile' ]) ? $creds[ 'companyFile' ] : '';
		$this->odoo_username        = isset($creds[ 'client_username' ]) ? $creds[ 'client_username' ] : '';
		$this->odoo_password        = isset($creds[ 'client_password' ]) ? $creds[ 'client_password' ] : '';
		$this->debug_mode           = isset($creds[ 'debug' ]) ? $creds[ 'debug' ] : 'no';
		$this->settings_changed     = ( get_option('is_opmc_odoo_settings_changed') ) ? get_option('is_opmc_odoo_settings_changed') : 0;
			
		if (! empty($this->odoo_url) && ! empty($this->odoo_db) && ! empty($this->odoo_username) && ! empty($this->odoo_password)) {
			$this->odoo_creds_available = true;
		}
	}
		
	public function generateToken() {
		// if ($this->settings_changed) {
		$data     = array(
			'domain'              => get_site_url(),
			'odoo_host'           => $this->odoo_url,
			'odoo_db_name'        => $this->odoo_db,
			'odoo_username'       => $this->odoo_username,
			'odoo_password'       => $this->odoo_password,
			'settings_updated'    => $this->settings_changed,
			'admin_email'         => get_option('admin_email'),
			'odoo_plugin_version' => WC_ODOO_INTEGRATION_INIT_VERSION,
		);
		$endpoint = $this->opmc_odoo_api_url . 'register';
			
		$response = wp_remote_post(
			$endpoint,
			array(
						 'timeout'   => 70,
						 'sslverify' => 0,
						 'body'      => $data,
					 )
		);
			
		// $this->addLog( 'register response : ' . print_r( $response['body'], true ) );
		return json_decode($response[ 'body' ]);
		// } else {
		// $this->addLog('settigns not changed |||');
		// }
	}
		
	public function getToken() {
		$token          = get_option('opmc_odoo_access_token');
		$plugin_updated = get_option('wc_opmc_odoo_update_state');
		// $this->addLog('existing Token : '. print_r($token, true));
		if (! $token || $this->settings_changed) {
			$response = $this->generateToken();
			if ($response->success) {
				update_option('opmc_odoo_access_token', $response->data->token);
				update_option('opmc_odoo_authenticated_uid', $response->data->odoo_uid);
				$token = $response->data->token;
			} else {
				$token = false;
			}
		} elseif ($plugin_updated) {
			$this->settings_changed = 1;
			$response               = $this->generateToken();
			delete_option('wc_opmc_odoo_update_state');
			if ($response->success) {
				// $this->addLog( 'response success : ' . print_r( $response, 1 ) );
				update_option('opmc_odoo_access_token', $response->data->token);
				update_option('opmc_odoo_authenticated_uid', $response->data->odoo_uid);
				// delete_option('wc_opmc_odoo_update_state');
				$token = $response->data->token;
			} else {
				$token = false;
			}
		}
			
		return $token;
	}
		
		
	public function opmc_odoo_get_request( $endpoint, $request = '' ) {
		try {
			$tokenValue = $this->getToken();
			$endpoint   = $this->opmc_odoo_api_url . $endpoint;
			// $this->addLog('get request : ' . print_r($request, 1));
			if (false != $tokenValue) {
				$tokenType   = 'Bearer';
				$tokenHeader = $tokenValue;
				$response    = wp_remote_get(
					$endpoint,
					array(
								 'timeout'   => 70,
								 'sslverify' => 0,
								 'headers'   => array(
									 'Authorization' => $tokenType . ' ' . $tokenHeader,
									 'Content-Type'  => 'application/json',
								 ),
								 'body'      => $request,
							 )
				);
					
				// $this->addLog(print_r($endpoint, true).' Response : '. print_r($response['body'], true));
				return json_decode($response[ 'body' ]);
			} else {
				$response = array(
					'success' => false,
					'error'   => 'Unauthorized user!!',
				);
					
				return $response;
			}
		} catch (Exception $e) {
			$response = array(
				'success' => false,
				'error'   => $e,
			);
				
			return $response;
		}
	}
		
	public function opmc_odoo_post_request( $endpoint, $request = '' ) {
		try {
			$tokenValue = $this->getToken();
			$endpoint   = $this->opmc_odoo_api_url . $endpoint;
			if (false != $tokenValue) {
				$tokenType   = 'Bearer';
				$tokenHeader = $tokenValue;
				$response    = wp_remote_post(
					$endpoint,
					array(
								 'timeout'   => 70,
								 'sslverify' => 0,
								 'headers'   => array(
									 'Authorization' => $tokenType . ' ' . $tokenHeader,
									 'Content-Type'  => 'application/json',
								 ),
								 'body'      => json_encode($request),
							 )
				);
					
				return json_decode($response[ 'body' ]);
			} else {
				$response = array(
					'success' => false,
					'error'   => 'Unauthorized user!!',
				);
					
				return $response;
			}
		} catch (Exception $e) {
			$response = array(
				'success' => false,
				'error'   => $e,
			);
				
			return $response;
		}
	}
		
	public function varifyOdooConnection() {
		$data = array(
			'domain'              => get_site_url(),
			'odoo_host'           => $this->odoo_url,
			'odoo_db_name'        => $this->odoo_db,
			'odoo_username'       => $this->odoo_username,
			'odoo_password'       => $this->odoo_password,
			'settings_updated'    => $this->settings_changed,
			'admin_email'         => get_option('admin_email'),
			'odoo_plugin_version' => WC_ODOO_INTEGRATION_INIT_VERSION,
		);
			
		$response = $this->opmc_odoo_post_request('odoo/verifyOdooCreds', $data);
			
		// $this->addLog( 'response for verify : ' . print_r( $response, 1 ) );
		return $response;
	}
		
	/**
	 * Search record in Odoo crm
	 *
	 * @param  [string] $type       [search record type]
	 * @param  [array]  $conditions [search condition in the form of array]
	 *
	 * @return [int/boolean]             [record id or boolean value]
	 */
	public function search_record( $type, $conditions = array() ) {
		$request = array(
			'type'       => $type,
			'conditions' => $conditions,
		);
		$record  = $this->opmc_odoo_post_request('odoo/search', $request);
			
		// $this->addLog(print_r($type, true) . ' search record : ' . print_r($record, true));
			
		return $record;
	}
		
	/**
	 * Search record in Odoo crm
	 *
	 * @param  [string] $type       [search record type]
	 * @param  [array]  $conditions [search condition in the form of array]
	 *
	 * @return [int/boolean]             [record id or boolean value]
	 */
	public function search( $type, $conditions = array(), $pagination = array() ) {
			
		$request = array(
			'type'       => $type,
			'conditions' => $conditions,
			'limit'      => $pagination[ 'limit' ],
			'offset'     => $pagination[ 'offset' ],
		);
		$record  = $this->opmc_odoo_post_request('odoo/search', $request);
			
		// $this->addLog('RECPOSNE : '. print_r($record, true));
			
		return $record;
	}
		
	/**
	 * Search records in Odoo crm
	 *
	 * @param  [string] $type       [search record type]
	 * @param  [array]  $conditions [search condition in the form of array]
	 *
	 * @return [int/boolean]             [record id or boolean value]
	 */
	public function search_records( $type, $conditions = null, $fields = array() ) {
		$request = array(
			'type'       => $type,
			'fields'     => $fields,
			'conditions' => $conditions,
		);
		$record  = $this->opmc_odoo_post_request('odoo/search_read', $request);
		// $this->addLog('RECPOSNE : '. print_r($record, true));
			
		if ($record->success) {
			return $record;
		} else {
			$error_msg = '[Search API] [Error] [Error for search record => Msg : ' . print_r($record->message, true) . ']';
			$this->addLog($error_msg);
				
			return false;
		}
	}
		
	/**
	 * Search records in Odoo crm
	 *
	 * @param  [string] $type       [search record type]
	 * @param  [array]  $conditions [search condition in the form of array]
	 *
	 * @return [int/boolean]             [record id or boolean value]
	 */
	public function search_count( $type, $conditions = null ) {
		$request = array(
			'type'       => $type,
			'conditions' => $conditions,
		);
		$record  = $this->opmc_odoo_post_request('odoo/search_count', $request);
			
		// $this->addLog( 'Search Count : ' . print_r( $record, true ) );
			
		if ($record->success) {
			return $record->data->items;
		} else {
			$error_msg = '[Count API] [Error] [Error for Search count => Msg : ' . print_r($record->message, true) . ']';
			$this->addLog($error_msg);
				
			return false;
		}
	}
		
		
	/**
	 * Search record in Odoo crm
	 *
	 * @param  [string] $type       [search record type]
	 * @param  [array]  $conditions [search condition in the form of array]
	 *
	 * @return [int/boolean]             [record id or boolean value]
	 */
	public function fetch_record_by_id( $type, $ids, $fields = array() ) {
		$request = array(
			'type'   => $type,
			'id'     => $ids,
			'fields' => $fields,
		);
		// $this->addLog('record by id  request : ' . print_r($request, true));
		$record = $this->opmc_odoo_get_request('odoo/read', $request);
		// $this->addLog('Record by id : ' . print_r($record, true));
			
		if ($record->success) {
			return json_decode(json_encode($record->data->records), true);
		} else {
			$error_msg = '[Read by id API] [Error] [Error for fetch record by id ' . print_r($type, 1) . ' => Msg : ' . print_r($record->message, true) . ']';
			$this->addLog($error_msg);
				
			return false;
		}
	}
		
	/**
	 * Search record in Odoo crm
	 *
	 * @param  [string] $type       [search record type]
	 * @param  [array]  $conditions [search condition in the form of array]
	 *
	 * @return [int/boolean]             [record id or boolean value]
	 */
	public function fetch_record_by_ids( $type, $ids, $fields = array() ) {
		$request = array(
			'type'   => $type,
			'id'     => $ids,
			'fields' => $fields,
		);
		$record  = $this->opmc_odoo_get_request('odoo/read', $request);
			
		if ($record->success) {
			return $record;
		} else {
			$error_msg = '[Read By Ids API] [Error] [Error for fetch record by ids for ' . print_r($type, 1) . ' => Msg : ' . print_r($record->message, true) . ']';
			$this->addLog($error_msg);
				
			return false;
		}
	}
		
	/**
	 * [create_record description]
	 *
	 * @param  [string] $type       [search record type]
	 * @param  [array]  $data [record to create the data]
	 *
	 * @return [array] $record [record id or boolean value]
	 */
	public function create_record( $type, $data ) {
		$request = array(
			'type' => $type,
			'data' => $data,
		);
		// $this->addLog('create request data : ' . print_r($request, true));
		$record = $this->opmc_odoo_post_request('odoo/create', $request);
		// $this->addLog('create response : ' . print_r($record, true));
		if ($record->success) {
			return $record;
		} else {
			$error_msg = '[Create API] [Error] [There is some error in Odoo API for ' . print_r($type, 1) . '. API Response : ' . print_r($record->message, true) . ']';
			$this->addLog($error_msg);
				
			return $record;
		}
	}
		
	/**
	 * Fetch all fields of records from odoo
	 *
	 * @param string  $type      records type
	 * @param array   $fields    fields need to fetch
	 * @param array   $condition conditions for the record fetch
	 * @param integer $limit     limit of records
	 *
	 * @return array             array of records
	 */
	public function readAll( $type, $fields = array(), $condition = null, $limit = 1000 ) {
		$request = array(
			'type'       => $type,
			'fields'     => $fields,
			'conditions' => $condition,
			'limit'      => $limit,
		);
		// pr($request);die('aa');
		// $this->addLog( print_r( $type, true ) . ' Request : ' . print_r( $request, true ) );
		$record = $this->opmc_odoo_post_request('odoo/search_read', $request);
		// $this->addLog( print_r( $type, true ) . ' Records : ' . print_r( $record, true ) );
		if ($record->success) {
			return $record;
		} else {
			$error_msg = '[Read API] [Error] [Error for read all => Msg : ' . print_r($record->message, true) . ']';
			$this->addLog($error_msg);
				
			return false;
		}
	}
		
		
	/**
	 * Fetch Single record from Odoo
	 *
	 * @param array $condition [description]
	 *
	 * @return [type]            [description]
	 */
	public function fetchProductInventory( $criteria = null ) {
		$fields  = array( 'name', 'qty_available', 'barcode', 'list_price', 'default_code', 'barcode' );
		$request = array(
			'type'       => 'product.product',
			'fields'     => $fields,
			'conditions' => $criteria,
			'limit'      => 1,
		);
		$record  = $this->opmc_odoo_post_request('odoo/search_read', $request);
		// $record = $client->search_read('product.product', $criteria, $fields, 1);
		if (isset($record[ 'faultCode' ])) {
			$msg = '[Read API] [Error] [Unable To Fetch Inventory product.product Msg : ' . print_r($record, true) . ']';
			$this->addLog($msg);
				
			return array(
				'fail' => true,
				'msg'  => $record[ 'faultString' ],
			);
		} elseif (count($record) > 0) {
			return $record[ 0 ];
		} else {
			return false;
		}
	}
		
	/**
	 * Update record at Odoo
	 *
	 * @param  [string] $type   [record type]
	 * @param  [array]  $ids    [records ids]
	 * @param  [array]  $fields [records fields to update]
	 *
	 * @return [array]         [ids]
	 */
	public function update_record( $type, $ids, $fields ) {
		// $record = $client->write($type, $ids, $fields);
			
		$request = array(
			'type' => $type,
			'id'   => $ids,
			'data' => $fields,
		);
		// $this->addLog( print_r( $type, true ) . ' request of ' . print_r( $ids, true ) . ' : ' . print_r( $fields, true ) );
		$record = $this->opmc_odoo_post_request('odoo/write', $request);
		// $this->addLog(print_r($type, true) . ' update response for ' . print_r($ids, true) . ' : ' . print_r($record, true));
			
		if ($record->success) {
			return $record;
		} else {
			$error_msg = '[Update API] [Error] [Error for update record for ' . print_r($type, 1) . ' => Msg : ' . print_r($record->message, true);
			$this->addLog($error_msg);
				
			return false;
		}
	}
		
		
	public function read_fields( $model, $fields = array(), $attrs = array() ) {
		// $record = $client->read_fields($model , $fields, array());
		$request = array(
			'type'   => $model,
			'fields' => $fields,
		);
		$record  = $this->opmc_odoo_get_request('odoo/read_fields', $request);
		if ($record->success) {
			return json_decode(json_encode($record->data->fields), true);
		} else {
			$error_msg = '[Read Fields API] [Error] [Error to read fields for ' . print_r($model, 1) . ' => Msg : ' . print_r($record->message, true) . ']';
			$this->addLog($error_msg);
				
			return false;
		}
	}
		
	public function search_read() {
	}
		
	public function addLog( $message ) {
		if ('yes' == $this->debug_mode) {
			$wcLogger = WC_ODOO_Integration::get_logger();
			$wcLogger->add('odoo-integration', $message);
		}
	}
		
	/**
	 * Search record in Odoo crm by id
	 *
	 * @param  [string] $type       [search record type]
	 * @param  [array]  $conditions [search condition in the form of array]
	 *
	 * @return [int/boolean]             [record id or boolean value]
	 */
	public function fetch_file_record_by_id( $filename, $type, $ids = array(), $fields = array() ) {
		$data = file_get_contents(WC_ODOO_INTEGRATION_PLUGINDIR . '/includes/' . $filename . '.json');
		if (! empty($data)) {
			$array_data = json_decode($data, 1);
				
			if (json_last_error() === JSON_ERROR_NONE) {
				$response = array_filter(
					$array_data,
					function ( $item ) use ( $ids ) {
						if ($item[ 'id' ] == $ids) {
							return true;
						}
						
						return false;
					}
				);
					
				return count($response) > 0 ? reset($response) : $response;
			} else {
				return array(
					'fail' => true,
					'msg'  => 'Invalid Json File',
				);
			}
		} else {
			return $this->fetch_record_by_id($type, $ids, $fields);
		}
	}
		
	/**
	 * Search record in Odoo crm by ids
	 *
	 * @param  [string] $type       [search record type]
	 * @param  [array]  $conditions [search condition in the form of array]
	 *
	 * @return [int/boolean]             [record id or boolean value]
	 */
	public function fetch_file_record_by_ids( $filename, $type, $ids = array(), $fields = array() ) {
		$data = file_get_contents(WC_ODOO_INTEGRATION_PLUGINDIR . '/includes/' . $filename . '.json');
		if (! empty($data)) {
			$array_data = json_decode($data, 1);
			if (json_last_error() === JSON_ERROR_NONE) {
				$response = array_filter(
					$array_data,
					function ( $item ) use ( $ids ) {
						if (in_array($item[ 'id' ], $ids)) {
							return true;
						}
						
						return false;
					}
				);
					
				return $response;
			} else {
				return array(
					'fail' => true,
					'msg'  => 'Invalid Json File',
				);
			}
		} else {
			return $this->fetch_record_by_ids($type, $ids, $fields);
		}
	}
		
	public function version() {
		try {
			$record = $this->opmc_odoo_get_request('odoo/version', array());
			if ($record->success) {
				return $record;
			} else {
				// $this->addLog( 'invalid creds' );
				return null;
			}
		} catch (Exception $e) {
			// $this->addLog( 'invalid creds' );
			return null;
		}
	}
		
	public function is_authenticate() {
			
		$is_authenticated = get_option('is_opmc_odoo_authenticated', null);
		if (null === $is_authenticated) {
			return false;
		} else {
			return $is_authenticated;
		}
	}
		
	public function is_multi_company() {
		$is_multi_company = get_option('is_opmc_odoo_multi_company', null);
		if (null === $is_multi_company) {
			return false;
		} else {
			return $is_multi_company;
		}
	}
		
		
	public function custom_api_call( $model, $action, $data ) {
		$request = array(
			'type'       => $model,
			'method'     => $action,
			'conditions' => $data,
		);
		// $this->addLog('custom api call request : ' . print_r($request, 1));
		$record = $this->opmc_odoo_post_request('odoo/custom_api_call', $request);
		// $this->addLog('custom api call : ' . print_r($record, 1));
		if ($record->success) {
			return $record;
		} else {
			return false;
		}
	}
	
	/**
	 * Make a request to the Odoo API to get product tags.
	 *
	 * @param array $woo_tags An array of WooCommerce tags.
	 * @return object The response from the Odoo API.
	 */
	public function odoo_product_tags( $woo_tags) {
		// Prepare the request.
		$request = array(
			'woo_tags' => $woo_tags,
		);
		
		// Make a POST request to the Odoo API.
		$response = $this->opmc_odoo_post_request('odoo/product-tags', $request);
		
		// Return the response.
		return $response;
	}
	
	/**
	 * Export a product to Odoo.
	 *
	 * @param WC_Product $product The WooCommerce product object to export.
	 * @return object The response from the Odoo API.
	 */
	public function export_products( $product) {
		// Prepare the request.
		$request = array(
			'product' => $product,
		);
		
		// Make a POST request to the Odoo API.
		$response = $this->opmc_odoo_post_request('odoo/product-export', $request);
		
		// Return the response.
		return $response;
	}
}
