<?php
/**
 *  WooCommerce ODOO Integration.
 *
 * @package   WooCommerce ODOO Integration
 */
// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);
if ( ! class_exists( 'WC_ODOO_Integration_Settings' ) ) :

	class WC_ODOO_Integration_Settings extends WC_Integration {
		/**
		 * Init and hook in the integration.
		 */
		private $client_id       = '';
		private $alert_msg       = 0;
		public $odoo_sku_mapping = 'default_code';
		public $companyFile      = 1;
		public function __construct() {

			global $woocommerce;
			$this->id                 = 'woocommmerce_odoo_integration';
			$this->method_title       = __( 'Odoo Integration', 'wc-odoo-integration' );
			$this->method_description = '<div>' . __( 'Sync WooCommerce Customer,Product and Order to Odoo ERP.', 'wc-odoo-integration' ) . '</br><strong>' . __( 'Important Note', 'wc-odoo-integration' ) . ':</strong><p>' . __( 'Fill The Address Details for company. (Settings -> General Settings -> Manage Companies)', 'wc-odoo-integration' ) . '</p><p>' . __( 'Change Invoice Quantity Setting. (Settings -> Sales -> Invoicing -> Select Invoice what is ordered )', 'wc-odoo-integration' ) . '</p></div>';

			if ( $this->is_current_url() ) {
				$this->process_admin_options();
			}
			// $this->init_settings();
			// Define user set variables.
			$this->odoo_version         = $this->get_option( 'odooVersion' );
			$this->client_url           = rtrim( $this->get_option( 'client_url' ), '/' );
			$this->client_db            = $this->get_option( 'client_db' );
			$this->client_username      = $this->get_option( 'client_username' );
			$this->client_password      = $this->get_option( 'client_password' );
			$this->companyFile          = $this->get_option( 'companyFile' );
			$this->odooAccount          = $this->get_option( 'odooAccount' );
			$this->odooTax              = $this->get_option( 'odooTax' );
			$this->shippingOdooTax      = $this->get_option( 'shippingOdooTax' );
			$this->invoiceJournal       = $this->get_option( 'invoiceJournal' );
			$this->odooInventorySync    = $this->get_option( 'odooInventorySync' );
			$this->debug                = $this->get_option( 'debug' );
			$this->odooDebtorAccount    = $this->get_option( 'odooDebtorAccount' );
			$this->createProductToOdoo  = $this->get_option( 'createProductToOdoo' );
			$this->odoo_fiscal_position = $this->get_option( 'odoo_fiscal_position' );
			if ( $this->get_option( 'odooSkuMapping' ) != '' ) {
				$this->odoo_sku_mapping = $this->get_option( 'odooSkuMapping' );
			}

			// Actions.
			add_action( 'woocommerce_update_options_integration_' . $this->id, array( $this, 'process_admin_options' ) );

			// Filters.
			add_filter( 'woocommerce_settings_api_sanitized_fields_' . $this->id, array( $this, 'sanitize_settings' ) );

			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_script' ) );

			add_action( 'wp_ajax_load_odoo_extra_fields', array( $this, 'load_odoo_taxes_fields' ) );
			add_action( 'wp_ajax_load_fiscal_positions', array( $this, 'load_fiscal_positions' ) );
			// add_action( 'save_post', array( $this,'do_insert_product_in_odoo' ), 10, 2);
			// add_action('create_product_cat', array($this, 'sync_category_to_odoo'), 10, 2);
			// add_action('edit_product_cat', array($this, 'sync_category_to_odoo'), 10, 2);
			add_action( 'woocommerce_order_refunded', array( $this, 'create_odoo_refund' ), 10, 2 );
			add_action( 'update_option_woocommerce_woocommmerce_odoo_integration_settings', array( $this, 'creds_updated' ), 10, 3 );
		}

		public function generate_custom_settings_html( $form_fields, $echo = true ) {
			// echo "<pre>";
			// print_r($form_fields);
			if ( empty( $form_fields ) ) {
				$form_fields = $this->get_form_fields();
			}

			$fields_tabs       = array();
			$second_tab_fields = array();
			foreach ( $form_fields as $key => $form_field ) {
				$fields_tabs[ $form_field['tab'] ][ $key ] = $form_field;
			}
			extract( $fields_tabs );
			$opmc_odoo_access_token      = get_option( 'opmc_odoo_access_token' );
			$opmc_odoo_authenticated_uid = get_option( 'opmc_odoo_authenticated_uid' );
			$opmc_odoo_access_error      = get_option( '_opmc_odoo_access_error' );

			// var_dump($opmc_odoo_access_token);
			// var_dump($opmc_odoo_authenticated_uid);die();

			if ( ! $opmc_odoo_access_token || ! $opmc_odoo_authenticated_uid ) {
				if ( 'INVALID_CREDS' == $opmc_odoo_access_error ) {
					$opmc_odoo_indicator = array(
						'value' => __( 'Odoo credentials are not valid.', 'wc-odoo-integration' ),
						'class' => 'opmc-error',
						'icon'  => 'dashicons-no-alt',
					);
				} elseif ( 'INVALID_HOST' == $opmc_odoo_access_error ) {
					$opmc_odoo_indicator = array(
						'value' => __( 'Odoo Host url is not valid.', 'wc-odoo-integration' ),
						'class' => 'opmc-error',
						'icon'  => 'dashicons-no-alt',
					);
				} else {
					$opmc_odoo_indicator = array(
						'value' => __( 'Please provide valid Odoo credentials to connect.', 'wc-odoo-integration' ),
						'class' => 'opmc-warning',
						'icon'  => 'dashicons-info-outline',
					);
				}
			} else {
					$opmc_odoo_indicator = array(
						'value' => __( 'Odoo account is connected.', 'wc-odoo-integration' ),
						'class' => 'opmc-success',
						'icon'  => 'dashicons-yes',
					);
			}
			// ob_start();
			include_once WC_ODOO_INTEGRATION_PLUGINDIR . '/includes/tamplate-admin-setting-page.php';
			// $output = ob_get_contents();
			// ob_end_clean();
			// return $output;
		}

		public function creds_updated( $old_values, $new_values, $option_name ) {
			global $wpdb;
			if ( empty( $old_values ) ) {
				return;
			}
			if ( ( $old_values['client_url'] != $new_values['client_url'] ) || ( $old_values['client_db'] != $new_values['client_db'] ) || ( $old_values['client_username'] != $new_values['client_username'] ) ) {
				$common_functions = new WC_ODOO_Common_Functions();
				if ( $common_functions->is_authenticate() ) {

					$wpdb->query( "DELETE FROM `{$wpdb->postmeta}` WHERE `meta_key` LIKE '%odoo%'" );
					$wpdb->query( "DELETE FROM `{$wpdb->usermeta}` WHERE `meta_key` LIKE '%odoo%'" );
					$wpdb->query( "DELETE FROM `{$wpdb->termmeta}` WHERE `meta_key` LIKE '%odoo%'" );
					$wpdb->query( "DELETE FROM `{$wpdb->order_itemmeta}` WHERE `meta_key` LIKE '%_order_line_id%'" );
					$wpdb->query( "DELETE FROM `{$wpdb->options}` WHERE `option_name` LIKE '%odoo_shipping_product_id%'" );
				}
			}
		}

		public function admin_options() {
			$this->generate_custom_settings_html( $this->get_form_fields(), false ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}
		public function process_admin_options() {

			$saved = parent::process_admin_options();
			$this->init_form_fields();
			return $saved;
		}
		/**
		 * Initialize integration settings form fields.
		 *
		 * @return void
		 */
		public function init_form_fields() {
			// $this->fetch_file_record_by_id('taxes','account.tax');
			$debug_label       = __( 'Enable Logging', 'wc-odoo-integration' );
			$debug_description = __( 'Log Odoo events, such as API requests.', 'wc-odoo-integration' );

			if ( defined( 'WC_VERSION' ) && version_compare( WC_VERSION, '2.2', '>=' ) ) {
				$debug_label = sprintf( $debug_label, ' | <a href="' . esc_url( admin_url( 'admin.php?page=wc-status&tab=logs&log_file=' . esc_attr( $this->id ) . '-' . sanitize_file_name( wp_hash( $this->id ) ) . '.log' ) ) . '">' . __( 'View Log', 'wc-odoo-integration' ) . '</a>' );
			} else {
				$debug_label = sprintf( $debug_label, ' | ' . __( 'View Log', 'wc-odoo-integration' ) . ': <code>woocommerce/logs/' . $this->id . '-' . sanitize_file_name( wp_hash( $this->id ) ) . '.txt</code>' );
			}

			$common_functions  = new WC_ODOO_Common_Functions();
			$this->form_fields = array(
				'odoo_plugin_nonce'                       => array(
					'type'    => 'hidden',
					'default' => wp_create_nonce( 'odoo_plugin_nonce_action' ),
					'tab'     => 'tab1',
				),
				'odooVersion'                             => array(
					'title'       => __( 'Select Odoo Version', 'wc-odoo-integration' ),
					'type'        => 'select',
					'label'       => __( 'Odoo Version', 'wc-odoo-integration' ),
					'default'     => '',
					'options'     => array(
						'13' => 'Odoo 13.0',
						'14' => 'Odoo 14.0',
						'15' => 'Odoo 15.0',
						'16' => 'Odoo 16.0',
						'17' => 'Odoo 17.0',
					),
					'description' => __( 'Select Odoo version for your CRM.', 'wc-odoo-integration' ),
					'desc_tip'    => __( 'Select the Odoo database version for your CRM.', 'wc-odoo-integration' ),
					'class'       => 'select_odoo_version',
					'tab'         => 'tab1',
				),
				'client_url'                              => array(
					'title'       => __( 'Server URL', 'wc-odoo-integration' ),
					'type'        => 'url',
					'description' => __( 'Insert database server URL. You can find it in your Odoo account', 'wc-odoo-integration' ),
					'desc'        => true,
					'desc_tip'    => __( 'Insert database server URL. You can find it in your Odoo account URL', 'wc-odoo-integration' ),
					'default'     => '',
					'tab'         => 'tab1',

				),
				'client_db'                               => array(
					'title'       => __( 'Database Name', 'wc-odoo-integration' ),
					'type'        => 'text',
					'description' => __( 'Insert database name. You can find it in your Odoo account.', 'wc-odoo-integration' ),
					'desc'        => true,
					'desc_tip'    => __( 'Insert database name. it’s simply the name in URL (without www. And .com)', 'wc-odoo-integration' ),
					'default'     => '',
					'tab'         => 'tab1',
				),
				'client_username'                         => array(
					'title'       => __( 'Username', 'wc-odoo-integration' ),
					'type'        => 'text',
					'description' => __( 'Insert username. You can find it in your Odoo account.', 'wc-odoo-integration' ),
					'desc'        => true,
					'desc_tip'    => __( 'Insert username. It is the email used while creating the Odoo database.  You can find it in your Odoo account.', 'wc-odoo-integration' ),
					'default'     => '',
					'tab'         => 'tab1',
				),
				'client_password'                         => array(
					'title'       => __( 'Password', 'wc-odoo-integration' ),
					'type'        => 'password',
					'description' => __( 'Insert password for your API access user.', 'wc-odoo-integration' ),
					'desc'        => true,
					'desc_tip'    => __( 'Insert password for your API access user.', 'wc-odoo-integration' ),
					'default'     => '',
					'tab'         => 'tab1',
				),
				'debug'                                   => array(
					'title'       => __( 'Debug Log', 'wc-odoo-integration' ),
					'type'        => 'checkbox',
					'label'       => $debug_label,
					'default'     => 'no',
					'description' => $debug_description,
					'desc_tip'    => __( 'Recommended to keep on, so as to check the status messages under WooCommerce >> Status >> Logs', 'wc-odoo-integration' ),
					'tab'         => 'tab1',
				),
				'odooSkuMapping'                          => array(
					'title'       => __( 'Odoo SKU Mapping', 'wc-odoo-integration' ),
					'type'        => 'select',
					'label'       => __( 'Odoo SKU Mapping', 'wc-odoo-integration' ),
					'default'     => 'default_code',
					'options'     => array(
						'default_code' => __( 'Internal Reference', 'wc-odoo-integration' ),
						'barcode'      => __( 'Barcode', 'wc-odoo-integration' ),
						// 'l10n_in_hsn_code'       => 'HSN/SAC Code'
					),
					'description' => __( 'Odoo SKU mapping for your CRM.', 'wc-odoo-integration' ),
					'desc_tip'    => __( 'Odoo SKU mapping for your CRM.', 'wc-odoo-integration' ),
					'class'       => 'select_odoo_version',
					'tab'         => 'tab_config',
				),
				'odoo_import_exclude_product_category'    => array(
					'title'       => __( 'Exclude Product by Categories', 'wc-odoo-integration' ),
					'type'        => 'multiselect',
					'label'       => __( 'Exclude Products by Categories', 'wc-odoo-integration' ),
					'default'     => '',
					'options'     => $this->get_odoo_categories(),
					'description' => __( 'Exclude products by categories not to import.', 'wc-odoo-integration' ),
					'desc_tip'    => __( 'Choose the categories that you wish to exclude from the product import process. Products belonging to the selected categories will be omitted during the import from Odoo to WooCommerce.', 'wc-odoo-integration' ),
					'tab'         => 'tab2',
				),
				'odoo_import_create_product'              => array(
					'title'             => __( 'Import Products', 'wc-odoo-integration' ),
					'type'              => 'checkbox',
					'label'             => __( 'Import Products', 'wc-odoo-integration' ),
					'default'           => 'no',
					'description'       => __( 'Import Products', 'wc-odoo-integration' ),
					'desc_tip'          => __( 'Enable this option to automate the product Import from Odoo to WooCommerce. If you need to run the product import manually, you can do so by clicking the "Manual Product Import" button.', 'wc-odoo-integration' ),
					'custom_attributes' => array(
						'cron_link'  => true,
						'link_title' => __( 'Manual Import Products', 'wc-odoo-integration' ),
						'link_url'   => '#;',
						'class'      => 'trigger_cron opmc_odoo_product_import',
					),
					'tab'               => 'tab2',
				),
				'odoo_import_create_product_frequency'    => array(
					'title'       => __( 'Import Products Frequency', 'wc-odoo-integration' ),
					'type'        => 'select',
					'label'       => __( 'Import Products Frequency', 'wc-odoo-integration' ),
					'default'     => '',
					'options'     => array(
						'hourly'     => __( 'Every Hour', 'wc-odoo-integration' ),
						'twicedaily' => __( 'Twice A Day', 'wc-odoo-integration' ),
						'daily'      => __( 'Once A Day', 'wc-odoo-integration' ),
					),
					'description' => __( 'Import products data frequency.', 'wc-odoo-integration' ),
					'desc_tip'    => __( 'Choose a time frequency for the product import from Odoo to WooCommerce. The product import process will run based on the selected frequency.', 'wc-odoo-integration' ),
					'tab'         => 'tab2',
				),
				'odoo_import_pos_product'                 => array(
					'title'       => __( 'Exclude PoS Products', 'wc-odoo-integration' ),
					'type'        => 'checkbox',
					'label'       => __( 'Exclude PoS Products', 'wc-odoo-integration' ),
					'default'     => 'no',
					'description' => __( 'Exclude PoS Products', 'wc-odoo-integration' ),
					'desc_tip'    => __( 'Enable this option to exclude the import of "Point of Sale products" from Odoo to WooCommerce. These products will not be included in the automated import process.', 'wc-odoo-integration' ),
					'tab'         => 'tab2',
				),
				'odoo_import_update_product'              => array(
					'title'       => __( 'Update Products', 'wc-odoo-integration' ),
					'type'        => 'checkbox',
					'label'       => __( 'Update Products', 'wc-odoo-integration' ),
					'default'     => 'no',
					'description' => __( 'Update Products', 'wc-odoo-integration' ),
					'desc_tip'    => __( 'Activate this option to update products from Odoo to WooCommerce. This will enable the automated process of updating product information from Odoo to WooCommerce whenever changes occur.', 'wc-odoo-integration' ),
					'tab'         => 'tab2',
				),
				'odoo_import_update_stocks'               => array(
					'title'       => __( 'Synchronize Stocks', 'wc-odoo-integration' ),
					'type'        => 'checkbox',
					'label'       => __( 'Synchronize Stocks', 'wc-odoo-integration' ),
					'default'     => 'no',
					'description' => __( 'Synchronize Stocks', 'wc-odoo-integration' ),
					'desc_tip'    => __( 'Enable this setting to synchronize stock from Odoo to WooCommerce. This will ensure that stock quantities are regularly updated and consistent between the two platforms at the specified intervals.', 'wc-odoo-integration' ),
					'tab'         => 'tab2',
				),
				'odoo_import_update_price'                => array(
					'title'       => __( 'Synchronize Price', 'wc-odoo-integration' ),
					'type'        => 'checkbox',
					'label'       => __( 'Synchronize Price', 'wc-odoo-integration' ),
					'default'     => 'no',
					'description' => __( 'Synchronize Price', 'wc-odoo-integration' ),
					'desc_tip'    => __( 'Enable this option to synchronize prices from Odoo to WooCommerce. This will ensure that product prices are regularly updated and aligned between the two platforms, providing consistent pricing for your products.', 'wc-odoo-integration' ),
					'tab'         => 'tab2',
				),
				'odoo_import_create_categories'           => array(
					'title'       => __( 'Import Categories', 'wc-odoo-integration' ),
					'type'        => 'checkbox',
					'label'       => __( 'Import Categories', 'wc-odoo-integration' ),
					'default'     => 'no',
					'description' => __( 'Import Categories', 'wc-odoo-integration' ),
					'desc_tip'    => __( 'Enable this option to import product categories from Odoo to WooCommerce. This will ensure that the product categories are accurately replicated and available in your WooCommerce store, based on the data from Odoo.', 'wc-odoo-integration' ),
					'tab'         => 'tab2',
				),
				'odoo_import_create_categories_frequency' => array(
					'title'       => __( 'Import Categories Frequency', 'wc-odoo-integration' ),
					'type'        => 'select',
					'label'       => __( 'Import Categories Frequency', 'wc-odoo-integration' ),
					'default'     => '',
					'options'     => array(
						'hourly'     => __( 'Every Hour', 'wc-odoo-integration' ),
						'twicedaily' => __( 'Twice A Day', 'wc-odoo-integration' ),
						'daily'      => __( 'Once A Day', 'wc-odoo-integration' ),
					),
					'description' => __( 'Category sync frequency.', 'wc-odoo-integration' ),
					'desc_tip'    => __( 'Choose the frequency for category synchronization from Odoo to WooCommerce. This will determine how often the categories information is updated and kept consistent between the two platforms.', 'wc-odoo-integration' ),
					'tab'         => 'tab2',
				),
				'odoo_import_create_attributes'           => array(
					'title'       => __( 'Import Attribute', 'wc-odoo-integration' ),
					'type'        => 'checkbox',
					'label'       => __( 'Import Attribute', 'wc-odoo-integration' ),
					'default'     => 'no',
					'description' => __( 'Import Attribute', 'wc-odoo-integration' ),
					'desc_tip'    => __( 'Enable this setting to synchronize attributes from Odoo to WooCommerce. This will ensure that attribute information is regularly updated and kept consistent between the two platforms at the specified intervals.', 'wc-odoo-integration' ),
					'tab'         => 'tab2',
				),
				'odoo_import_create_attributes_frequency' => array(
					'title'       => __( 'Import Attribute Frequency', 'wc-odoo-integration' ),
					'type'        => 'select',
					'label'       => __( 'Import Attribute Frequency', 'wc-odoo-integration' ),
					'default'     => '',
					'options'     => array(
						'hourly'     => __( 'Every Hour', 'wc-odoo-integration' ),
						'twicedaily' => __( 'Twice A Day', 'wc-odoo-integration' ),
						'daily'      => __( 'Once A Day', 'wc-odoo-integration' ),
					),
					'description' => __( 'Select the attribute cron frequency to sync attribute.', 'wc-odoo-integration' ),
					'desc_tip'    => __( 'Select the attribute frequency to synchronize attributes from Odoo to WooCommerce. This setting will determine how often attributes are updated and kept consistent between the two platforms, ensuring that product attributes remain accurate and up-to-date.', 'wc-odoo-integration' ),
					'tab'         => 'tab2',
				),				
				'odoo_import_update_order_status'         => array(
					'title'   => __( 'Update Order Status', 'wc-odoo-integration' ),
					'type'    => 'checkbox',
					'label'   => __( 'Update Order Status', 'wc-odoo-integration' ),
					'default' => 'no',
					'tab'     => 'tab2_2',
				),
				'odoo_import_update_order_status_frequency' => array(
					'title'   => __( 'Update Order Status Frequency', 'wc-odoo-integration' ),
					'type'    => 'select',
					'label'   => __( 'Update Order Status Frequency', 'wc-odoo-integration' ),
					'default' => '',
					'options' => array(
						'hourly'     => __( 'Every Hour', 'wc-odoo-integration' ),
						'twicedaily' => __( 'Twice A Day', 'wc-odoo-integration' ),
						'daily'      => __( 'Once A Day', 'wc-odoo-integration' ),
					),
					'tab'     => 'tab2_2',
				),
				'odoo_import_customer'                    => array(
					'title'       => __( 'Import/Update Customer', 'wc-odoo-integration' ),
					'type'        => 'checkbox',
					'label'       => __( 'Import/Update Customer', 'wc-odoo-integration' ),
					'default'     => 'no',
					'description' => __( 'Import/Update Customer', 'wc-odoo-integration' ),
					'desc_tip'    => __( 'Enable this option to import and update customers from Odoo to WooCommerce. With this setting activated, customer data, including new customers and any changes to existing customer information, will be synchronized between Odoo and WooCommerce, ensuring accurate and up-to-date customer records on both platforms.', 'wc-odoo-integration' ),
					'tab'         => 'tab2_2',
				),
				'odoo_import_customer_frequency'          => array(
					'title'    => __( 'Import/Update Customer Frequency', 'wc-odoo-integration' ),
					'type'     => 'select',
					'label'    => __( 'Import/Update Customer Frequency', 'wc-odoo-integration' ),
					'default'  => '',
					'options'  => array(
						'hourly'     => __( 'Every Hour', 'wc-odoo-integration' ),
						'twicedaily' => __( 'Twice A Day', 'wc-odoo-integration' ),
						'daily'      => __( 'Once A Day', 'wc-odoo-integration' ),
					),
					'desc_tip' => __( 'Choose the frequency to sync customer attributes from Odoo to WooCommerce. This setting determines how often customer attribute information, such as custom fields or special preferences, is updated and kept consistent between the two platforms.', 'wc-odoo-integration' ),
					'tab'      => 'tab2_2',
				),
				'odoo_import_order'                       => array(
					'title'    => __( 'Import Order', 'wc-odoo-integration' ),
					'type'     => 'checkbox',
					'label'    => __( 'Import Order', 'wc-odoo-integration' ),
					'default'  => '',
					'desc_tip' => __( 'Configure the start and end dates to import orders from Odoo to WooCommerce within the chosen time period', 'wc-odoo-integration' ),
					'tab'      => 'tab2_2',
				),
				'odoo_import_order_frequency'             => array(
					'title'    => __( 'Import Order Frequency', 'wc-odoo-integration' ),
					'type'     => 'select',
					'label'    => __( 'Import Order Frequency', 'wc-odoo-integration' ),
					'default'  => '',
					'desc_tip' => __( 'Select frequency to sync order from Odoo to WooCommerce', 'wc-odoo-integration' ),
					'options'  => array(
						'hourly'     => __( 'Every Hour', 'wc-odoo-integration' ),
						'twicedaily' => __( 'Twice A Day', 'wc-odoo-integration' ),
						'daily'      => __( 'Once A Day', 'wc-odoo-integration' ),
					),
					'tab'      => 'tab2_2',
				),
				'odoo_import_order_from_date'             => array(
					'type'        => 'text',
					'placeholder' => __( 'From', 'wc-odoo-integration' ),
					'default'     => '',
					'tab'         => 'tab2_2',
				),
				'odoo_import_order_to_date'               => array(
					'type'        => 'text',
					'placeholder' => __( 'To', 'wc-odoo-integration' ),
					'default'     => '',
					'tab'         => 'tab2_2',
				),
				'odoo_import_refund_order'                => array(
					'title'    => __( 'Import Refund Order', 'wc-odoo-integration' ),
					'type'     => 'checkbox',
					'label'    => __( 'Import Refund Order', 'wc-odoo-integration' ),
					'default'  => '',
					'desc_tip' => __( 'Enable the option to import Refunded Orders from Odoo to WooCommerce.', 'wc-odoo-integration' ),
					'tab'      => 'tab2_2',
				),
				'odoo_import_refund_order_frequency'      => array(
					'title'    => __( 'Import Refund Order Frequency', 'wc-odoo-integration' ),
					'type'     => 'select',
					'label'    => __( 'Import Refund Order Frequency', 'wc-odoo-integration' ),
					'default'  => '',
					'desc_tip' => __( 'Choose the refund order frequency to synchronize refund orders.', 'wc-odoo-integration' ),
					'options'  => array(
						'hourly'     => __( 'Every Hour', 'wc-odoo-integration' ),
						'twicedaily' => __( 'Twice A Day', 'wc-odoo-integration' ),
						'daily'      => __( 'Once A Day', 'wc-odoo-integration' ),
					),
					'tab'      => 'tab2_2',
				),
				'odoo_import_coupon'                      => array(
					'title'       => __( 'Import Coupon', 'wc-odoo-integration' ),
					'type'        => 'checkbox',
					'label'       => __( 'Import Coupon', 'wc-odoo-integration' ),
					'default'     => '',
					'description' => __( 'Select Coupon Cron to sync Coupon', 'wc-odoo-integration' ),
					'desc_tip'    => __( 'Enable this option to import coupons from Odoo to WooCommerce.', 'wc-odoo-integration' ),
					'tab'         => 'tab2_3',
				),
				'odoo_import_coupon_frequency'            => array(
					'title'       => __( 'Import Coupon Frequency', 'wc-odoo-integration' ),
					'type'        => 'select',
					'label'       => __( 'Import Coupon Frequency', 'wc-odoo-integration' ),
					'default'     => '',
					'options'     => array(
						'hourly'     => __( 'Every Hour', 'wc-odoo-integration' ),
						'twicedaily' => __( 'Twice A Day', 'wc-odoo-integration' ),
						'daily'      => __( 'Once A Day', 'wc-odoo-integration' ),
					),
					'description' => __( 'Select coupon cron frequency to sync coupon.', 'wc-odoo-integration' ),
					'desc_tip'    => __( 'Choose the coupon cron frequency to synchronize coupons.', 'wc-odoo-integration' ),
					'tab'         => 'tab2_3',
				),
				'odoo_import_coupon_update'               => array(
					'title'       => __( 'Update Coupon', 'wc-odoo-integration' ),
					'type'        => 'checkbox',
					'label'       => __( 'Update Coupon', 'wc-odoo-integration' ),
					'default'     => '',
					'description' => __( 'Select Coupon Cron to sync Coupon', 'wc-odoo-integration' ),
					'desc_tip'    => __( 'Enable this option to update existing coupons from Odoo to WooCommerce.', 'wc-odoo-integration' ),
					'tab'         => 'tab2_3',
				),
				'odoo_exclude_product_category'           => array(
					'title'       => __( 'Exclude Product by Categories', 'wc-odoo-integration' ),
					'type'        => 'multiselect',
					'label'       => __( 'Exclude Products by Categories', 'wc-odoo-integration' ),
					'default'     => '',
					'options'     => $this->get_categories(),
					'description' => __( 'Exclude products by categories not to export.', 'wc-odoo-integration' ),
					'desc_tip'    => __( 'Select the categories to exclude when exporting WooCommerce products to Odoo.', 'wc-odoo-integration' ),
					'tab'         => 'tab3',
				),
				'odoo_export_create_product'              => array(
					'title'             => __( 'Export Products', 'wc-odoo-integration' ),
					'type'              => 'checkbox',
					'label'             => __( 'Export Products', 'wc-odoo-integration' ),
					'default'           => 'no',
					'description'       => __( 'Export Products', 'wc-odoo-integration' ),
					'desc_tip'          => __( 'Enable this option to export products from WooCommerce to Odoo. Clicking the “Manual Product Export” button to complete it manually.', 'wc-odoo-integration' ),
					'custom_attributes' => array(
						'cron_link'  => true,
						'link_title' => __( 'Manual Export Products', 'wc-odoo-integration' ),
						'link_url'   => '#;',
						'class'      => 'trigger_cron opmc_odoo_product_export',
					),
					'tab'               => 'tab3',
				),
				'odoo_export_create_product_frequency'    => array(
					'title'       => __( 'Export Products Frequency', 'wc-odoo-integration' ),
					'type'        => 'select',
					'label'       => __( 'Export Products Frequency', 'wc-odoo-integration' ),
					'default'     => '',
					'options'     => array(
						'hourly'     => __( 'Every Hour', 'wc-odoo-integration' ),
						'twicedaily' => __( 'Twice A Day', 'wc-odoo-integration' ),
						'daily'      => __( 'Once A Day', 'wc-odoo-integration' ),
					),
					'description' => __( 'Export Products Frequency', 'wc-odoo-integration' ),
					'desc_tip'    => __( 'Choose the frequency for exporting product data from WooCommerce to Odoo. This setting determines how often product information is sent from WooCommerce to Odoo, ensuring that product data is kept up-to-date and synchronized between the two platforms at the specified intervals.', 'wc-odoo-integration' ),
					'tab'         => 'tab3',
				),
				'odoo_export_update_product'              => array(
					'title'       => __( 'Update Products', 'wc-odoo-integration' ),
					'type'        => 'checkbox',
					'label'       => __( 'Update Products', 'wc-odoo-integration' ),
					'default'     => 'no',
					'description' => __( 'Update Products', 'wc-odoo-integration' ),
					'desc_tip'    => __( 'Enable this option to update products from WooCommerce to Odoo automatically. This ensures that product information is regularly synced and kept consistent between the two platforms at the specified intervals.', 'wc-odoo-integration' ),
					'tab'         => 'tab3',
				),
				'odoo_export_update_stocks'               => array(
					'title'       => __( 'Synchronize Stocks', 'wc-odoo-integration' ),
					'type'        => 'checkbox',
					'label'       => __( 'Synchronize Stocks', 'wc-odoo-integration' ),
					'default'     => 'no',
					'description' => __( 'Synchronize Stocks', 'wc-odoo-integration' ),
					'desc_tip'    => __( 'Enable this option to synchronize the stock from WooCommerce to Odoo. This ensures that stock quantities are regularly updated and consistent between the two platforms at the specified intervals.', 'wc-odoo-integration' ),
					'tab'         => 'tab3',
				),
				'odoo_export_update_price'                => array(
					'title'       => __( 'Synchronize Price', 'wc-odoo-integration' ),
					'type'        => 'checkbox',
					'label'       => __( 'Synchronize Price', 'wc-odoo-integration' ),
					'default'     => 'no',
					'description' => __( 'Synchronize Price', 'wc-odoo-integration' ),
					'desc_tip'    => __( 'Enable this option to synchronize the price from WooCommerce to Odoo.', 'wc-odoo-integration' ),
					'tab'         => 'tab3',
				),
				'odoo_export_create_categories'           => array(
					'title'       => __( 'Export Categories', 'wc-odoo-integration' ),
					'type'        => 'checkbox',
					'label'       => __( 'Export Categories', 'wc-odoo-integration' ),
					'default'     => 'no',
					'description' => __( 'Export Categories', 'wc-odoo-integration' ),
					'desc_tip'    => __( 'Enable this option to export product categories from WooCommerce to Odoo. This ensures that product category information is regularly updated and synced between the two platforms at the specified intervals.', 'wc-odoo-integration' ),
					'tab'         => 'tab3',
				),
				'odoo_export_create_categories_frequency' => array(
					'title'       => __( 'Export Categories Frequency', 'wc-odoo-integration' ),
					'type'        => 'select',
					'label'       => __( 'Export Categories Frequency', 'wc-odoo-integration' ),
					'default'     => '',
					'options'     => array(
						'hourly'     => __( 'Every Hour', 'wc-odoo-integration' ),
						'twicedaily' => __( 'Twice A Day', 'wc-odoo-integration' ),
						'daily'      => __( 'Once A Day', 'wc-odoo-integration' ),
					),
					'description' => __( 'Select category cron frequency to sync category.', 'wc-odoo-integration' ),
					'desc_tip'    => __( 'Choose the category cron frequency to synchronize categories from WooCommerce to Odoo. This setting will determine how often category information is updated and kept consistent between the two platforms, ensuring that categories remain accurate and aligned.', 'wc-odoo-integration' ),
					'tab'         => 'tab3',
				),
				'odoo_export_create_attributes'           => array(
					'title'       => __( 'Export Attribute', 'wc-odoo-integration' ),
					'type'        => 'checkbox',
					'label'       => __( 'Export Attribute', 'wc-odoo-integration' ),
					'default'     => 'no',
					'description' => __( 'Export Attribute', 'wc-odoo-integration' ),
					'desc_tip'    => __( 'Enable this option to synchronize the attributes from WooCommerce to Odoo.', 'wc-odoo-integration' ),
					'tab'         => 'tab3',
				),
				'odoo_export_create_attributes_frequency' => array(
					'title'       => __( 'Export Attribute Frequency', 'wc-odoo-integration' ),
					'type'        => 'select',
					'label'       => __( 'Export Attribute Frequency', 'wc-odoo-integration' ),
					'default'     => '',
					'options'     => array(
						'hourly'     => __( 'Every Hour', 'wc-odoo-integration' ),
						'twicedaily' => __( 'Twice A Day', 'wc-odoo-integration' ),
						'daily'      => __( 'Once A Day', 'wc-odoo-integration' ),
					),
					'description' => __( 'Select attribute cron frequency to sync attribute.', 'wc-odoo-integration' ),
					'desc_tip'    => __( 'Choose the attribute frequency to synchronize attributes from WooCommerce to Odoo. This setting will determine how often attribute information is updated and kept consistent between the two platforms, ensuring that product attributes remain accurate and up-to-date.', 'wc-odoo-integration' ),
					'tab'         => 'tab3',
				),
				'odoo_export_update_order_status'         => array(
					'title'   => __( 'Update Order Status', 'wc-odoo-integration' ),
					'type'    => 'checkbox',
					'label'   => __( 'Update Order Status', 'wc-odoo-integration' ),
					'default' => 'no',
					'tab'     => 'tab3_2',
				),
				'odoo_export_update_order_status_frequency' => array(
					'title'   => __( 'Update Order Status Frequency', 'wc-odoo-integration' ),
					'type'    => 'select',
					'label'   => __( 'Update Order Status Frequency', 'wc-odoo-integration' ),
					'default' => '',
					'options' => array(
						'hourly'     => __( 'Every Hour', 'wc-odoo-integration' ),
						'twicedaily' => __( 'Twice A Day', 'wc-odoo-integration' ),
						'daily'      => __( 'Once A Day', 'wc-odoo-integration' ),
					),
					'tab'     => 'tab3_2',
				),
				'odoo_export_order_on_checkout'           => array(
					'title'    => __( 'Export order On Checkout', 'wc-odoo-integration' ),
					'type'     => 'checkbox',
					'label'    => __( 'Export order On Checkout', 'wc-odoo-integration' ),
					'default'  => 'yes',
					'desc_tip' => __( 'Enable this option to automatically synchronize new orders upon creation/checkout from WooCommerce to Odoo.', 'wc-odoo-integration' ),
					'tab'      => 'tab3_2',
				),
				'odoo_export_invoice'                     => array(
					'title'    => __( 'Export Invoice', 'wc-odoo-integration' ),
					'type'     => 'checkbox',
					'label'    => __( 'Export Invoice', 'wc-odoo-integration' ),
					'default'  => 'yes',
					'desc_tip' => __( 'Enable this option to seamlessly export all invoices from WooCommerce to Odoo, streamlining the management and tracking of invoice information in your Odoo system.', 'wc-odoo-integration' ),
					'tab'      => 'tab3_2',
				),
				'odoo_mark_invoice_paid'                  => array(
					'title'       => __( 'Mark Invoice Paid', 'wc-odoo-integration' ),
					'type'        => 'checkbox',
					'label'       => __( 'Mark Invoice Paid', 'wc-odoo-integration' ),
					'default'     => 'yes',
					'description' => __( 'Export Invoice option should be enabled for this.', 'wc-odoo-integration' ),
					'desc_tip'    => __( 'When this setting is enabled, the invoice will be automatically marked as completed, regardless of the order status. The status mapping process, which normally associates the order status with the invoice status, will be skipped in this case. This ensures that invoices are considered completed and fully processed within Odoo, regardless of the current status of the corresponding orders in WooCommerce.', 'wc-odoo-integration' ),
					'tab'         => 'tab3_2',
				),
				'odoo_export_refund_order'                => array(
					'title'       => __( 'Export Refund Order', 'wc-odoo-integration' ),
					'type'        => 'checkbox',
					'label'       => __( 'Export Refund Order', 'wc-odoo-integration' ),
					'default'     => 'yes',
					'description' => __( 'Export Invoice option should be enabled for this.', 'wc-odoo-integration' ),
					'desc_tip'    => __( 'Enable this option for exporting refunded orders from WooCommerce to Odoo, ensure that the "Export Invoice" setting is enabled. This setting allows the plugin to efficiently process and synchronize refunded order information, including invoices, from WooCommerce to Odoo at scheduled intervals. By enabling this option, you can seamlessly manage and track refunded orders in your Odoo system.', 'wc-odoo-integration' ),
					'tab'         => 'tab3_2',
				),
				'odoo_map_to_default_customers'           => array(
					'title'       => __( 'Map Orders to Default Customer', 'wc-odoo-integration' ),
					'type'        => 'checkbox',
					'label'       => __( 'Map All Orders to Default Customer', 'wc-odoo-integration' ),
					'default'     => 'no',
					'desc_tip'    => __( 'Enable this option to allow the plugin to synchronize all orders to default Odoo customer. By enabling this option, you can map orders to provided odoo customer.', 'wc-odoo-integration' ),
					'tab'         => 'tab3_2',
				),
				'odoo_default_customer_id'           => array(
					'title'       => __( 'Default Odoo Customer ID', 'wc-odoo-integration' ),
					'type'        => 'text',
					'label'       => __( 'Default Odoo Customer ID', 'wc-odoo-integration' ),
					'desc_tip'    => __( 'Input the ID of your default Odoo customer. All orders will be synced to this individual. To find the ID, see the URL address bar when editing the customer in Odoo. Look for the parameter named "id".', 'wc-odoo-integration' ),
					'tab'         => 'tab3_2',
				),

				'odoo_export_customer'                    => array(
					'title'    => __( 'Export/Update Customer', 'wc-odoo-integration' ),
					'type'     => 'checkbox',
					'label'    => __( 'Export/Update Customer', 'wc-odoo-integration' ),
					'desc_tip' => __( 'Enable this option to Export/Update customers from WooCommerce to Odoo.', 'wc-odoo-integration' ),
					'default'  => 'no',
					'tab'      => 'tab3_2',
				),
				'odoo_export_customer_frequency'          => array(
					'title'    => __( 'Export/Update Customer Frequency', 'wc-odoo-integration' ),
					'type'     => 'select',
					'label'    => __( 'Export/Update Customer Frequency', 'wc-odoo-integration' ),
					'default'  => '',
					'options'  => array(
						'hourly'     => __( 'Every Hour', 'wc-odoo-integration' ),
						'twicedaily' => __( 'Twice A Day', 'wc-odoo-integration' ),
						'daily'      => __( 'Once A Day', 'wc-odoo-integration' ),
					),
					'desc_tip' => __( 'Select the frequency to synchronize customers. This setting determines how often customer information is updated and kept consistent between the two platforms, ensuring that customer data remains accurate and up-to-date.', 'wc-odoo-integration' ),
					'tab'      => 'tab3_2',
				),
				'odoo_export_order'                       => array(
					'title'    => __( 'Export Order', 'wc-odoo-integration' ),
					'type'     => 'checkbox',
					'label'    => __( 'Export Order', 'wc-odoo-integration' ),
					'default'  => '',
					'desc_tip' => __( 'Configure the start and end dates to export orders from wooCommerce to Odoo within the chosen time period.', 'wc-odoo-integration' ),
					'tab'      => 'tab3_2',
				),

				'odoo_status_mapping'                     => array(
					'title'    => __( 'Status Mapping', 'wc-odoo-integration' ),
					'type'     => 'checkbox',
					'label'    => __( 'Status Mapping', 'wc-odoo-integration' ),
					'default'  => 'no',
					'desc_tip' => __( 'Enable this option to create a custom order status mapping, synchronizing WooCommerce order statuses with corresponding Odoo order states. This setting ensures that the order statuses in both platforms are synchronized, allowing for seamless order tracking and management between WooCommerce and Odoo.', 'wc-odoo-integration' ),
					'tab'      => 'tab3_2',
				),
				'odoo_woo_order_status'                   => array(
					'title'   => __( 'Order Status Mapping', 'wc-odoo-integration' ),
					'type'    => 'multiselect',
					'label'   => __( 'Order Status Mapping', 'wc-odoo-integration' ),
					'default' => '',
					'options' => wc_get_order_statuses(),
					'tab'     => 'tab3_2',
				),
				'odoo_payment_status'                     => array(
					'title'   => __( 'Order Status Mapping', 'wc-odoo-integration' ),
					'type'    => 'multiselect',
					'label'   => __( 'Order Status Mapping', 'wc-odoo-integration' ),
					'default' => '',
					'options' => array(
						'quote_only'  => __( 'Quote Only', 'wc-odoo-integration' ),
						'quote_order' => __( 'Quote and Sales Order', 'wc-odoo-integration' ),
						'in_payment'  => __( 'In Payment Invoice', 'wc-odoo-integration' ),
						'paid'        => __( 'Paid Invoice', 'wc-odoo-integration' ),
						'cancelled'   => __( 'Cancelled', 'wc-odoo-integration' ),
					),
					'tab'     => 'tab3_2',
				),
				'odoo_export_order_frequency'             => array(
					'title'    => __( 'Export Order Frequency', 'wc-odoo-integration' ),
					'type'     => 'select',
					'label'    => __( 'Export Order Frequency', 'wc-odoo-integration' ),
					'default'  => '',
					'options'  => array(
						'hourly'     => __( 'Every Hour', 'wc-odoo-integration' ),
						'twicedaily' => __( 'Twice A Day', 'wc-odoo-integration' ),
						'daily'      => __( 'Once A Day', 'wc-odoo-integration' ),
					),
					'desc_tip' => __( 'Select order cron frequency to sync order.', 'wc-odoo-integration' ),
					'tab'      => 'tab3_2',
				),
				'odoo_export_order_from_date'             => array(
					'type'        => 'text',
					'placeholder' => __( 'From', 'wc-odoo-integration' ),
					'default'     => '',
					'tab'         => 'tab3_2',
				),
				'odoo_export_order_to_date'               => array(
					'type'        => 'text',
					'placeholder' => __( 'To', 'wc-odoo-integration' ),
					'default'     => '',
					'tab'         => 'tab3_2',
				),
				'odoo_export_coupon'                      => array(
					'title'       => __( 'Export Coupon', 'wc-odoo-integration' ),
					'type'        => 'checkbox',
					'label'       => __( 'Export Coupon', 'wc-odoo-integration' ),
					'default'     => '',
					'description' => __( 'Select coupon to sync coupon.', 'wc-odoo-integration' ),
					'desc_tip'    => __( 'Enable this option to export coupons from WooCommerce to Odoo.', 'wc-odoo-integration' ),
					'tab'         => 'tab3_3',
				),
				'odoo_export_coupon_frequency'            => array(
					'title'       => __( 'Export Coupon Frequency', 'wc-odoo-integration' ),
					'type'        => 'select',
					'label'       => __( 'Export Coupon Frequency', 'wc-odoo-integration' ),
					'default'     => '',
					'options'     => array(
						'hourly'     => __( 'Every Hour', 'wc-odoo-integration' ),
						'twicedaily' => __( 'Twice A Day', 'wc-odoo-integration' ),
						'daily'      => __( 'Once A Day', 'wc-odoo-integration' ),
					),
					'description' => __( 'Select coupon cron frequency to sync coupon.', 'wc-odoo-integration' ),
					'desc_tip'    => __( 'Select frequency to sync coupons.', 'wc-odoo-integration' ),
					'tab'         => 'tab3_3',
				),
				'odoo_export_coupon_update'               => array(
					'title'       => __( 'Update Coupon', 'wc-odoo-integration' ),
					'type'        => 'checkbox',
					'label'       => __( 'Update Coupon', 'wc-odoo-integration' ),
					'default'     => '',
					'description' => __( 'Update coupon to Odoo.', 'wc-odoo-integration' ),
					'desc_tip'    => __( 'Enable this option to update coupons from WooCommerce to Odoo.', 'wc-odoo-integration' ),
					'tab'         => 'tab3_3',
				),
			);
//			if ( $common_functions->is_authenticate() ) {

				$company_id = ( '' != $this->get_option( 'companyFile' ) ) ? $this->get_option( 'companyFile' ) : 1;

				$companyList                  = $this->getcompany_files();
				$invoiceJouranlList           = $this->get_all_live_sale_journal( $company_id );
				$taxList                      = $this->get_all_live_taxes( $company_id );
				$fiscalPositions              = $this->get_fiscal_positions( $company_id );
				$gst_fields                   = $this->check_for_gst_treatment();
				$is__opmc_odoo_update_configs = get_option( '_opmc_odoo_update_configs' );
			if ( $is__opmc_odoo_update_configs ) {
				$this->companyFile          = '';
				$this->odooAccount          = '';
				$this->odooTax              = '';
				$this->shippingOdooTax      = '';
				$this->invoiceJournal       = '';
				$this->odoo_fiscal_position = '';
				update_option( '_opmc_odoo_update_configs', 0 );
			}
				// pr($companyList);die();
				$extraFields = array(
					'companyFile'                   => array(
						'title'       => __( 'Select Company', 'wc-odoo-integration' ),
						'type'        => 'select',
						'lable'       => __( 'Select Company', 'wc-odoo-integration' ),
						'default'     => '',
						'class'       => 'companyFiles',
						'options'     => $companyList,
						'description' => __( 'Select company for your CRM.', 'wc-odoo-integration' ),
						'desc_tip'    => __( 'Select company for your CRM listed in Odoo.', 'wc-odoo-integration' ),
						'tab'         => 'tab_config',
					),
					'invoiceJournal'                => array(
						'title'       => __( 'Select Sale Invoice Journal', 'wc-odoo-integration' ),
						'type'        => 'select',
						'label'       => __( 'Select Sale Invoice Journal', 'wc-odoo-integration' ),
						'default'     => '',
						'class'       => 'dependentOptions',
						'options'     => $invoiceJouranlList,
						'description' => __( 'Select sale journal for Odoo invoices.', 'wc-odoo-integration' ),
						'desc_tip'    => __( 'Select sale journal for Odoo invoices.', 'wc-odoo-integration' ),
						'tab'         => 'tab_config',
					),
					'odooTax'                       => array(
						'title'       => __( 'Select Tax Type', 'wc-odoo-integration' ),
						'type'        => 'select',
						'label'       => __( 'Select Tax Type', 'wc-odoo-integration' ),
						'default'     => '',
						'class'       => 'dependentOptions',
						'options'     => $taxList,
						'description' => __( 'Select tax term for Odoo invoices.', 'wc-odoo-integration' ),
						'desc_tip'    => __( 'Select tax rate for Odoo invoices.', 'wc-odoo-integration' ),
						'tab'         => 'tab_config',
					),
					'shippingOdooTax'               => array(
						'title'       => __( 'Select Shipping Tax Type', 'wc-odoo-integration' ),
						'type'        => 'select',
						'label'       => __( 'Select Shipping Tax Type', 'wc-odoo-integration' ),
						'default'     => '',
						'class'       => 'dependentOptions',
						'options'     => $taxList,
						'description' => __( 'Select shipping tax term for Odoo invoices.', 'wc-odoo-integration' ),
						'desc_tip'    => __( 'Select shipping tax rate for Odoo invoices.', 'wc-odoo-integration' ),
						'tab'         => 'tab_config',
					),
					'odoo_fiscal_position'          => array(
						'title'    => __( 'Use Fiscal Positions', 'wc-odoo-integration' ),
						'type'     => 'checkbox',
						'label'    => __( 'Use fiscal positions', 'wc-odoo-integration' ),
						'default'  => 'no',
						'desc_tip' => __( 'If enabled, the plugin will use the fiscal positions defined in your Odoo ERP system to calculate taxes. Fiscal positions allow you to adapt the taxes applied to a sale or purchase, based on the customer/supplier\'s location. If you are not using fiscal positions in Odoo, leave this option disabled.', 'wc-odoo-integration' ),
						'tab'      => 'tab_config',
					),
					'odoo_fiscal_position_selected' => array(
						'title'       => __( 'Select Fiscal Position', 'wc-odoo-integration' ),
						'type'        => 'select',
						'label'       => __( 'Select Fiscal Position', 'wc-odoo-integration' ),
						'default'     => '',
						'class'       => 'dependentOptions',
						'options'     => $fiscalPositions,
						'description' => __( 'Select fiscal position.', 'wc-odoo-integration' ),
						'desc_tip'    => __( 'You can select the fiscal position tax according to location from dropdown.', 'wc-odoo-integration' ),
						'tab'         => 'tab_config',
					),
				);

				foreach ( $extraFields as $key => $extraField ) {
					$this->form_fields[ $key ] = $extraField;
				}
				if ( count( $gst_fields ) > 0 ) {
					$this->form_fields['gst_treatment'] = array(
						'title'       => __( 'Select GST Treatment', 'wc-odoo-integration' ),
						'type'        => 'select',
						'label'       => __( 'Select GST Treatment', 'wc-odoo-integration' ),
						'default'     => '',
						'options'     => $gst_fields,
						'description' => __( 'Select GST treatment for Odoo invoices.', 'wc-odoo-integration' ),
						'desc_tip'    => __( 'Select how the GST will be treated for Odoo invoices.', 'wc-odoo-integration' ),
						'tab'         => 'tab_config',
					);
				}
//			}
		}

		/**
		 * Sanitize our settings
		 *
		 * @see process_admin_options()
		 */
		public function sanitize_settings( $settings ) {

			if ( $settings['odooVersion'] != $this->odoo_version ||
				rtrim( $settings['client_url'], '/' ) != $this->client_url ||
				$settings['client_db'] != $this->client_db ||
				$settings['client_username'] != $this->client_username ||
				$settings['client_password'] != $this->client_password ) {
					delete_option( 'opmc_odoo_access_token' );
				update_option( 'is_opmc_odoo_settings_changed', 1 );
			} else {
				update_option( 'is_opmc_odoo_settings_changed', 0 );
			}

			if ( isset( $settings['woocommerce_woocommmerce_odoo_integration_tax_account_mapping'] ) ) {

				$settings = $settings;
			}

			return $settings;
		}

		/**
		 * Generate a URL to our Odoo settings screen.
		 *
		 * @since  1.3.4
		 * @return string Generated URL.
		 */
		public function get_settings_url() {
			return add_query_arg(
				array(
					'page'    => 'wc-settings',
					'tab'     => 'integration',
					'section' => $this->id,
				),
				admin_url( 'admin.php' )
			);
		}

		/**
		 * Enqueue admin scripts.
		 */
		public function enqueue_admin_script() {
			if ( ( isset( $_GET['tab'] ) && 'integration' == $_GET['tab'] ) && ( isset( $_GET['section'] ) && 'woocommmerce_odoo_integration' == $_GET['section'] ) ) {
				wp_enqueue_style('admin-datatables-css', 'https://cdn.datatables.net/1.11.5/css/jquery.dataTables.css');
				wp_enqueue_style('opmc_ofw-admin-style', WC_ODOO_INTEGRATION_PLUGINURL . 'assets/css/odoo.css', array());
				wp_enqueue_script( 'admin-datatables-jquery', 'https://cdn.datatables.net/1.11.5/js/jquery.dataTables.js', array(), WC_ODOO_INTEGRATION_INIT_VERSION, true );
				wp_enqueue_script( 'admin-settings', WC_ODOO_INTEGRATION_PLUGINURL . 'assets/js/admin-settings.js', array(), WC_ODOO_INTEGRATION_INIT_VERSION, true );
				wp_set_script_translations( 'admin-settings', 'wc-odoo-integration', WC_ODOO_INTEGRATION_PLUGINDIR . 'languages' );

				$creds            = get_option( 'woocommerce_woocommmerce_odoo_integration_settings' );
				$common_functions = new WC_ODOO_Common_Functions();
				wp_localize_script(
					'admin-settings',
					'odoo_admin',
					array(
						'ajax_url'         => admin_url( 'admin-ajax.php' ),
						'odoo_url'         => isset( $creds['client_url'] ) ? rtrim( $creds['client_url'], '/' ) : '',
						'odoo_db'          => isset( $creds['client_db'] ) ? $creds['client_db'] : '',
						'odoo_username'    => isset( $creds['client_username'] ) ? $creds['client_username'] : '',
						'odoo_password'    => isset( $creds['client_password'] ) ? $creds['client_password'] : '',
						'ajax_nonce'       => wp_create_nonce( 'odoo_security' ),
						'is_creds_defined' => ( $common_functions->is_authenticate() ) ? 1 : 0,

					)
				);
			}

			wp_enqueue_script( 'admin-notices', WC_ODOO_INTEGRATION_PLUGINURL . 'assets/js/admin-notices.js', array(), WC_ODOO_INTEGRATION_INIT_VERSION, true );
			wp_localize_script(
				'admin-notices',
				'odoo_admin_notices',
				array(
					'ajax_url'   => admin_url( 'admin-ajax.php' ),
					'ajax_nonce' => wp_create_nonce( 'odoo_security' ),
				)
			);
		}
		
		/**
		 * Read data from the localfile to save the request time.
		 *
		 * @param  string $file filename
		 * @return array/false       return data may be array or boolean
		 */
		public function read_local_file( $file ) {
			$data = file_get_contents( WC_ODOO_INTEGRATION_PLUGINDIR . '/includes/' . $file . '.json' );
			if ( ! empty( $data ) ) {
				$array_data = json_decode( $data, 1 );
				if ( json_last_error() === JSON_ERROR_NONE ) {
					return $array_data;
				}
				return false;
			}
			return false;
		}

		/**
		 * Create file and save data to the local file
		 *
		 * @param  string $file filename
		 * @return arrray  $data response
		 */
		public function create_and_read_local_file( $file, $odoo_model_name, $fields = array(), $conditions = array() ) {
			$data    = array();
			$odooApi = new WC_ODOO_API();

			$data = $odooApi->readAll( $odoo_model_name, $fields, $conditions );
			// $odooApi->addLog( 'response : ' . print_r( $data->data->items, true ) );
			if ( $data->success ) {
				$fp = fopen( WC_ODOO_INTEGRATION_PLUGINDIR . '/includes/' . $file . '.json', 'w' );
				fwrite( $fp, json_encode( $data->data->items ) );
				fclose( $fp );
			}

			return json_decode( json_encode( $data->data->items ), true );
		}

		public function load_odoo_taxes_fields() {
			$ajaxxNonce;
			if ( isset( $_POST['security'] ) ) {
				$ajaxxNonce = sanitize_text_field( $_POST['security'] );
			}

			if ( ! wp_verify_nonce( $ajaxxNonce, 'odoo_security' ) ) {
				echo 'Nonce verification failed!';
				wp_die();
			}

			$company_id         = isset( $_POST['id'] ) ? sanitize_text_field( $_POST['id'] ) : 1;
			$options            = array();
			$options['journal'] = $this->get_all_live_sale_journal( $company_id );
			$options['taxes']   = $this->get_all_live_taxes( $company_id );
			echo json_encode( $options );
			die();
		}

		public function load_fiscal_positions() {
			$ajaxxNonce;
			if ( isset( $_POST['security'] ) ) {
				$ajaxxNonce = sanitize_text_field( $_POST['security'] );
			}

			if ( ! wp_verify_nonce( $ajaxxNonce, 'odoo_security' ) ) {
				echo 'Nonce verification failed!';
				wp_die();
			}
			$company_id                 = isset( $_POST['id'] ) ? sanitize_text_field( $_POST['id'] ) : 1;
			$options                    = array();
			$options['fiscal_position'] = $this->get_fiscal_positions( $company_id, true );
			echo json_encode( $options );
			die();
		}

		public function get_all_live_sale_journal( $company_id ) {
			$company_id                   = ( '' == $company_id ) ? 1 : $company_id;
			$odooApi                      = new WC_ODOO_API();
			$opmc_odoo_accounts           = get_option( '_opmc_odoo_journals' );
			$is__opmc_odoo_update_configs = get_option( '_opmc_odoo_update_configs' );
			if ( '' != $opmc_odoo_accounts && ! $is__opmc_odoo_update_configs ) {
				return $opmc_odoo_accounts;
			}
			$newaccounts = array( '' => __( '-- Select Invoice Journal --', 'wc-odoo-integration' ) );
			$conditions  = array(
				array(
					'field_key'   => 'company_id',
					'field_value' => (int) $company_id,
				),
				array(
					'field_key'   => 'type',
					'field_value' => 'sale',
				),
			);
			$accounts    = $this->create_and_read_local_file( 'accounts', 'account.journal', array(), $conditions );
			// $odooApi->addLog('Accounts :' . print_r($accounts, true));
			if ( is_array( $accounts ) ) {
				foreach ( (array) $accounts as $key => $account ) {
					$newaccounts[ $account['id'] ] = $account['name'];
				}
				update_option( '_opmc_odoo_journals', $newaccounts );
			}
			return $newaccounts;
		}

		public function get_all_live_taxes( $company_id ) {
			$company_id                   = ( '' == $company_id ) ? 1 : $company_id;
			$odooApi                      = new WC_ODOO_API();
			$opmc_odoo_taxes              = get_option( '_opmc_odoo_taxes' );
			$is__opmc_odoo_update_configs = get_option( '_opmc_odoo_update_configs' );
			if ( '' != $opmc_odoo_taxes && ! $is__opmc_odoo_update_configs ) {
				return $opmc_odoo_taxes;
			}
			$newtaxes   = array( '' => __( '-- Select Tax Type --', 'wc-odoo-integration' ) );
			$conditions = array(
				array(
					'field_key'   => 'company_id',
					'field_value' => (int) $company_id,
				),
			);
			$taxes      = $this->create_and_read_local_file( 'taxes', 'account.tax', array(), $conditions );
			// $odooApi->addLog('Taxes :' . print_r($taxes, true));
			if ( is_array( $taxes ) ) {
				foreach ( $taxes as $key => $tax ) {
					if ( 'sale' == $tax['type_tax_use'] ) {
						$newtaxes[ $tax['id'] ] = $tax['name'];
					}
				}
				update_option( '_opmc_odoo_taxes', $newtaxes );
			}
			return $newtaxes;
		}

		public function get_fiscal_positions( $company_id, $ajax_call = false ) {
			$company_id      = ( '' == $company_id ) ? 1 : $company_id;
			$newtaxes        = array( '' => __( '-- Select Fiscal Position --', 'wc-odoo-integration' ) );
			$odooApi         = new WC_ODOO_API();
			$odoo_fp_enabled = get_option( 'odoo_fiscal_position', 'no' );
			// $odooApi->addLog( 'fiscal postions enabled : ' . print_r( $odoo_fp_enabled, 1 ) );
			if ( 'yes' == $odoo_fp_enabled || $ajax_call ) {
				$opmc_odoo_taxes              = get_option( '_opmc_odoo_fiscal_positions' );
				$is__opmc_odoo_update_configs = get_option( '_opmc_odoo_update_configs' );
				if ( '' != $opmc_odoo_taxes && ! $is__opmc_odoo_update_configs ) {
					return $opmc_odoo_taxes;
				}

				$conditions = array(
					array(
						'field_key'   => 'company_id',
						'field_value' => (int) $company_id,
					),
				);
				$taxes      = $this->create_and_read_local_file( 'fiscal', 'account.fiscal.position', array(), $conditions );

				if ( is_array( $taxes ) ) {
					foreach ( $taxes as $key => $tax ) {
						if ( 1 == $tax['active'] ) {
							$newtaxes[ $tax['id'] ] = $tax['name'];
						}
					}
					update_option( '_opmc_odoo_fiscal_positions', $newtaxes );
				}
			}

			return $newtaxes;
		}

		public function check_for_gst_treatment() {

			$gst_fields = array();
			$odooApi = new WC_ODOO_API();
			$fields = $odooApi->read_fields('sale.order', array( 'l10n_in_gst_treatment' ));
			if (( null != $fields ) && ( 0 < count($fields) )) {
				if (isset($fields['l10n_in_gst_treatment']['selection']) && is_array($fields['l10n_in_gst_treatment'])) {
					foreach ($fields['l10n_in_gst_treatment']['selection'] as $key => $selection) {
						$gst_fields[$selection[0]] = $selection[1];
					}
				}
			}
			return $gst_fields;
		}

		public function getcompany_files() {
			$odooApi                      = new WC_ODOO_API();
			$companies_files              = get_option( '_opmc_odoo_company' );
			$is__opmc_odoo_update_configs = get_option( '_opmc_odoo_update_configs' );

			if ( '' != $companies_files && ! $is__opmc_odoo_update_configs ) {
				// $odooApi->addLog('Existing companies : ' . print_r($companies_files, true));
				return $companies_files;
			}

			$company_files = array( '' => __( '-- Select Company --', 'wc-odoo-integration' ) );

			$fields = $odooApi->search_records('res.company', array(), array( 'id', 'name' ));
			// $odooApi->addLog('companies response : ' . print_r($fields, true));
			if ($fields->success) {
				foreach ($fields->data->items as $key => $field) {
					$company_files[$field->id] = $field->name;
					// pr($company_files);
				}
				update_option('_opmc_odoo_company', $company_files);
				$odooApi->addLog('[Fetch Company files] [Success] [Companies List from Odoo Database fetched successfully.]');
			} else {
				$odooApi->addLog('[Fetch Company files] [Error] [There are some error during fetching Companies List from Odoo Database.]');
			}
			// $odooApi->addLog('new companies : ' . print_r($company_files, true));
			return $company_files;
		}

		public function get_odoo_categories() {
			 $odooApi     = new WC_ODOO_API();
			$product_cats = array();

			$common_functions = new WC_ODOO_Common_Functions();
			if ( $common_functions->is_authenticate() ) {
				$odoo_categories = $odooApi->search_records( 'product.category', array(), array( 'id', 'name' ) );
				// $odooApi->addLog('Odoo Categories response : '. print_r($odoo_categories, 1));
				if ( ! empty( $odoo_categories ) ) {
					if ( $odoo_categories->success ) {
						foreach ( $odoo_categories->data->items as $key => $all_odoo_cat ) {
							$product_cats[ $all_odoo_cat->id ] = $all_odoo_cat->name;
						}
						ksort( $product_cats );
						update_option( '_opmc_odoo_categories', $product_cats );
					}
				}
				// $odooApi->addLog( 'Odoo Categories to return : ' . print_r( $product_cats, 1 ) );
			}

			return $product_cats;
		}

		public function get_categories() {
			global $wpdb;

			$product_cats = array( '' => __( '-- Select Categories --', 'wc-odoo-integration' ) );

			$all_categories = $wpdb->get_results(
				"SELECT *
				FROM
				$wpdb->terms
				LEFT JOIN
				$wpdb->term_taxonomy ON
				$wpdb->terms.term_id = $wpdb->term_taxonomy.term_id
				WHERE
				$wpdb->term_taxonomy.taxonomy = 'product_cat' order by $wpdb->terms.name ASC"
			);
			// pr($all_categories);
			foreach ( $all_categories as $cat ) {
				if ( 0 == $cat->parent ) {
					$product_cats[ $cat->term_id ] = $cat->name;
				}
			}
			return $product_cats;
		}

		public function do_insert_product_in_odoo( $post_id ) {
			$post_status = get_post_status( $post_id );

			if ( 'auto-draft' == $post_status ) {
				return;
			}
			/* Autosave, do nothing */
			if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
				return;
			}
			/* Check user permissions */
			if ( ! current_user_can( 'edit_post', $post_id ) ) {
				return;
			}
			if ( 'product' == get_post_type( $post_id ) ) {
				$update = get_post_meta( $post_id, '_odoo_id', true );
				if ( $update ) {
					$this->sync_to_odoo( $post_id, (int) $update );
				} else {
					$this->sync_to_odoo( $post_id );
				}
			}
			return;
		}

		public function sync_to_odoo( $post_id, $odoo_product_id = 0 ) {

			$helper  = WC_ODOO_Helpers::getHelper();
			$odooApi = new WC_ODOO_API();
			$creds   = get_option( 'woocommerce_woocommmerce_odoo_integration_settings' );

			$product = wc_get_product( $post_id );
			if ( $product->get_sku() == '' ) {
					$error_msg = 'Error for Search product =>' . $product->get_id() . ' Msg : Invalid SKU';
					$odooApi->addLog( $error_msg );
					return false;
			}
			$data = array(
				'name'                  => $product->get_name(),
				'sale_ok'               => true,
				'type'                  => 'product',
				'company_id'            => ( ( isset( $creds['companyFile'] ) && '' != $creds['companyFile'] ) ? $creds['companyFile'] : 1 ),
				$this->odoo_sku_mapping => $product->get_sku(),
				'description_sale'      => $product->get_description(),
				'list_price'            => $product->get_price(),
				'categ_id'              => $this->get_category_id( $product ),
			);
			// 'description' => $product->get_description(),
			$product_qty = number_format( (float) $product->get_stock_quantity(), 2, '.', '' );

			if ( $helper->can_upload_image( $product ) ) {
				$data['image_1920'] = $helper->upload_product_image( $product->get_id() );
			}

			if ( $odoo_product_id > 0 ) {
				$data['id'] = $odoo_product_id;
				$response   = $odooApi->update_record( 'product.template', array( $odoo_product_id ), $data );
				$this->update_product_quantity( $odoo_product_id, $product_qty );
				if ( isset( $response['fail'] ) ) {
					$error_msg = 'Error for Updating Product for Id  => ' . $product->get_id() . ' Msg : ' . print_r( $response['msg'], true );
					$odooApi->addLog( $error_msg );
				} else {
					update_post_meta( $product->get_id(), '_odoo_id', $odoo_product_id );
					if ( $product->get_image_id() ) {
						update_post_meta( $product->get_id(), '_odoo_image_id', $product->get_image_id() );
					}
				}
			} else {
				$response = $odooApi->create_record( 'product.template', $data );

				if ( isset( $response['fail'] ) ) {
					$error_msg = 'Error for Creating Product for Id  => ' . $product->get_id() . ' Msg : ' . print_r( $response['msg'], true );
					$odooApi->addLog( $error_msg );
				} else {

					// $response = $odooApi->create_record('product.product', $data);
					$this->update_product_quantity( $response, $product_qty );

					update_post_meta( $product->get_id(), '_odoo_id', $response );
					if ( $product->get_image_id() ) {
						update_post_meta( $product->get_id(), '_odoo_image_id', $product->get_image_id() );
					}
				}
			}
		}

		public function sync_category_to_odoo( $term_id, $taxonomy_term_id ) {
			$odooApi = new WC_ODOO_API();
			$term    = get_term( $term_id );
			$data    = array(
				'name' => $term->taxonomy,
			);

			$odoo_term_id = get_term_meta( $term_id, '_odoo_term_id', true );
			if ( $odoo_term_id ) {

				$data['id'] = $odoo_term_id;
				$response   = $odooApi->update_record( 'product.category', array( $odoo_term_id ), $data );
				if ( isset( $response['fail'] ) ) {
					$error_msg = 'Error for Updating category for Id  => ' . $term_id . ' Msg : ' . print_r( $response['msg'], true );
					$odooApi->addLog( $error_msg );
				} else {
					update_post_meta( $term_id, '_odoo_id', $odoo_term_id );
				}
			} else {
				$response = $odooApi->create_record( 'product.category', $data );

				if ( isset( $response['fail'] ) ) {
					$error_msg = 'Error for Creating category for Id  => ' . $term_id . ' Msg : ' . print_r( $response['msg'], true );
					$odooApi->addLog( $error_msg );
				} else {
					update_term_meta( $term_id, '_odoo_term_id', $response );
				}
			}
		}

		public function get_category_id( $product ) {
			// $product   = wc_get_product( $product_id );
			$terms = wp_get_post_terms( $product->id, 'product_cat', array( 'fields' => 'ids' ) );
			if ( count( $terms ) > 0 ) {
				$cat_id = (int) $terms[0];

				$odoo_term_id = get_term_meta( $cat_id, '_odoo_term_id', true );

				if ( $odoo_term_id ) {
					return $odoo_term_id;
				} else {
					$odooApi      = new WC_ODOO_API();
					$term         = get_term( $cat_id );
					$data         = array(
						'name' => $term->name,
					);
					$odoo_term_id = $odooApi->search_record( 'product.category', array( array( 'name', '=', $term->name ) ) );
					if ( isset( $odoo_term_id['fail'] ) ) {
						$error_msg = 'Error for Search Category => ' . $cat_id . ' Msg : ' . print_r( $odoo_term_id['msg'], true );
						$odooApi->addLog( $error_msg );
						return false;
					}
					if ( $odoo_term_id ) {
						return $odoo_term_id;
					} else {
						$response = $odooApi->create_record( 'product.category', $data );

						if ( isset( $response['fail'] ) ) {
							$error_msg = 'Error for Creating category for Id  => ' . $cat_id . ' Msg : ' . print_r( $response['msg'], true );
							$odooApi->addLog( $error_msg );
						} else {
							update_term_meta( $cat_id, '_odoo_term_id', $response );
							return $response;
						}
					}
				}
			} else {
				return 1;
			}
		}

		public function update_product_quantity( $product_id, $quantity ) {
			$creds = get_option( 'woocommerce_woocommmerce_odoo_integration_settings' );
			if ( isset( $creds['createProductToOdoo'] ) && 'yes' == $creds['createProductToOdoo'] ) {
				$odooApi = new WC_ODOO_API();

				$quantity_id = $odooApi->create_record(
					'stock.change.product.qty',
					array(
						'new_quantity'    => $quantity,
						'product_tmpl_id' => $product_id,
						'product_id'      => $product_id,
					)
				);
				// $odooApi->addLog('qty Id : ' . print_r($quantity_id, true));
				if ( $quantity_id->success ) {
					$quantity_id = $quantity_id->data->odoo_id;
					$odooApi->custom_api_call( 'stock.change.product.qty', 'change_product_qty', array( $quantity_id ) );
				}
			}
		}

		public function generate_checkbox_html( $key, $data ) {

			$field    = $this->plugin_id . $this->id . '_' . $key;
			$value    = $this->get_option( $key );
			$defaults = array(
				'class'             => 'button-secondary',
				'css'               => '',
				'custom_attributes' => array(),
				'desc_tip'          => true,
				'description'       => '',
				'title'             => '',
				'disable'           => true,
			);

			$allowed_html = array(
				'a'      => array(
					'href'  => array(),
					'title' => array(),
				),
				'br'     => array(),
				'em'     => array(),
				'strong' => array(),
			);
			$data         = wp_parse_args( $data, $defaults );

			ob_start();
			?>
			<tr valign="top">
				<th scope="row" class="titledesc">
					<label for="<?php echo esc_attr( $field ); ?>"><?php echo esc_html( wp_kses_post( $data['title'] ) ); ?></label>
					<?php echo wp_kses_post( $this->get_tooltip_html( $data ) ); ?>
				</th>
				<td class="forminp">
					<fieldset>
						<legend class="screen-reader-text"><span><?php echo esc_html( wp_kses_post( $data['title'] ) ); ?></span></legend>
						<label class="switch">
					<input type="checkbox" name="<?php echo esc_attr( $field ); ?>" id="<?php echo esc_attr( $field ); ?>" value="<?php echo esc_html( $value ); ?>" <?php echo ( 'yes' == $value ? 'checked' : '' ); ?>>
					<span class="slider round"></span>
				  </label>
					<?php if ( ! empty( $data['custom_attributes'] ) && $data['custom_attributes']['cron_link'] ) : ?>
						<a href="<?php echo esc_attr( $data['custom_attributes']['link_url'] ); ?>" class="button button-secondary cron-button <?php echo esc_attr( $data['custom_attributes']['class'] ); ?>"><?php echo esc_html( $data['custom_attributes']['link_title'] ); ?></a>
					<?php endif; ?>
					</fieldset>
				</td>
			</tr>
			<?php
			return ob_get_clean();
		}

		public function is_current_url() {

			// $current_url = null;
			// if ( isset( $_SERVER['HTTP_HOST'] ) && isset($_SERVER['REQUEST_URI']) ) {
			// $current_url = is_ssl() ? 'https://' : 'http://';
			// $current_url .= esc_url_raw(wp_unslash( $_SERVER['HTTP_HOST'] )); // WPCS: sanitization okay
			// $current_url .= esc_url_raw(wp_unslash( $_SERVER['REQUEST_URI'] )); // WPCS: sanitization okay
			// }
			// return $current_url;
			if ( isset( $_GET['tab'] ) && 'integration' == $_GET['tab'] && isset( $_GET['section'] ) && $this->id == $_GET['section'] ) {
				return true;
			}
			return false;
		}

		public function allowed_html() {
			$fields             = wp_kses_allowed_html( 'post' );
			$allowed_atts       = array(
				'align'      => array(),
				'class'      => array(),
				'type'       => array(),
				'id'         => array(),
				'dir'        => array(),
				'lang'       => array(),
				'style'      => array(),
				'xml:lang'   => array(),
				'src'        => array(),
				'alt'        => array(),
				'href'       => array(),
				'rel'        => array(),
				'rev'        => array(),
				'target'     => array(),
				'novalidate' => array(),
				'value'      => array(),
				'name'       => array(),
				'tabindex'   => array(),
				'action'     => array(),
				'method'     => array(),
				'for'        => array(),
				'width'      => array(),
				'height'     => array(),
				'data'       => array(),
				'title'      => array(),
				'label'      => array(),
				'checked'    => true,
				'select'     => array(),
				'option'     => array(),
				'selected'   => array(),
				'multiple'   => array(),
			);
			$fields['form']     = array(
				'action'         => true,
				'accept'         => true,
				'accept-charset' => true,
				'enctype'        => true,
				'method'         => array(),
				'name'           => true,
				'target'         => true,
				'class'          => true,
			);
			$fields['input']    = $allowed_atts;
			$fields['select']   = $allowed_atts;
			$fields['option']   = $allowed_atts;
			$fields['optgroup'] = $allowed_atts;

			$fields['script'] = $allowed_atts;
			$fields['style']  = $allowed_atts;

			return $fields;
		}
		
		/**
		 * Splits a string by the first level of square brackets, if nested brackets exist.
		 *
		 * @param string $str The string to be split.
		 * @return mixed An array of substrings, or the original string if no nested brackets are present.
		 */
		public function explodeLogMessage( $str ) {
			// Check if there are no brackets present
			if ('[' !== $str[0] || !preg_match('/\[[^\[\]]*\]/', $str)) {
				// If no brackets present, return the string as is
				return $str;
			}
			$depth = 0;
			$result = array();
			$substr = '';
			$length = strlen($str); // Calculate the length of the string once
			
			for ($i = 0; $i < $length; $i++) { // Use the pre-calculated length here
				if ( '[' === $str[$i] ) {
					$depth++;
					if ($depth > 1) {
						$substr .= $str[$i];
					}
				} else if ( ']' === $str[$i] ) {
					$depth--;
					if ($depth >= 1) {
						$substr .= $str[$i];
					}
				} else {
					$substr .= $str[$i];
				}
				
				if ( ']' === $str[$i] && 0 === $depth && '' != $substr) {
					$result[] = $substr;
					$substr = '';
				}
			}
			
			return $result;
		}

		public function create_odoo_refund( $order_id, $refund_id ) {
			
			$nonce = isset($_POST['_wpnonce']) ? sanitize_text_field(wp_unslash($_POST['_wpnonce'])) : '';
			
			if ( empty($nonce) || ! wp_verify_nonce( $nonce, 'woocommerce-settings' ) ) {
				echo 'Nonce verification failed!';
				wp_die();
			}

			if ( 'yes' == $this->get_option( 'odoo_export_refund_order' ) && 'yes' == $this->get_option( 'odoo_export_invoice' ) ) {
				if (isset($_POST['action']) && 'woocommerce_refund_line_items' == $_POST['action'] ) { // phpcs:ignore
					$odooApi = new WC_ODOO_API();
					if ( $odooApi->is_multi_company() ) {
						$multi_company_func = '/multi-company-files';
					} else {
						$multi_company_func = '';
					}
					include WC_ODOO_INTEGRATION_PLUGINDIR . '/includes' . $multi_company_func . '/class-wc-odoo-functions.php';
					$function = new WC_ODOO_Functions();
					$function->create_odoo_refund( $order_id, $refund_id );
				}
			}
		}
	}

endif;

