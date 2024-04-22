<?php

/**
 * Plugin Name: Odoo for Woocommerce
 * Plugin URI: https://woocommerce.com/products/odoo-for-woocommerce/
 * Description: Create customer In ODOO and create invoice when order is placed.
 * Version: 3.7.8
 * Author: OPMC
 * Author URI: https://woocommerce.com
 * WP tested up to: 6.4
 * WC tested up to: 8.6
 * WC requires at least: 3.0
 * Text Domain: wc-odoo-integration
 * Domain Path: /languages
 *
 * Woo: 6307045:8fe2a8885f7f879d1ad5c5c077fa9612
 *
 * Copyright: 2009-2020 WooCommerce.
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 *
 * @package WC_ODOO_Integration
 */

// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);

if (! defined('ABSPATH')) {
	exit; // Exit if accessed directly
}


/**
 * Required functions.
 */
if (! function_exists('woothemes_queue_update') || ! function_exists('is_woocommerce_active')) {
	require_once 'woo-includes/woo-functions.php';
}

add_action('plugins_loaded', 'opmc_odoo_load_textdomain');

/**
 * Load textdomain for plugin
 *
 * @return void
 */
function opmc_odoo_load_textdomain() {
	load_plugin_textdomain('wc-odoo-integration', false, dirname(plugin_basename(__FILE__)) . '/languages');
}

/**
 * Plugin updates
 */
// woothemes_queue_update(plugin_basename(__FILE__), '8fe2a8885f7f879d1ad5c5c077fa9612', '6307045');

/* run the install scripts upon plugin activation */
register_activation_hook(__FILE__, 'install_odoo_integration_plugin');

/**
 * Function for creating log table "in8sync_log"
 */
function install_odoo_integration_plugin() {
	/**
	 * Check if WooCommerce is active
	 *
	 * @since  1.3.4
	 */
	if (! in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {
		die(esc_html_e('WooCommerce plugin is missing. Odoo for WooCommerce plugin requires WooCommerce.', 'wc-odoo-integration'));
	}
}

/**
 * This function runs when WordPress completes its upgrade process
 * It iterates through each plugin updated to see if ours is included
 *
 * @param $upgrader_object Array
 * @param $options Array
 */
function wp_opmc_odoo_upgrade_completed( $upgrader_object, $options ) {
	// The path to our plugin's main file
	$our_plugin = plugin_basename(__FILE__);
	// If an update has taken place and the updated type is plugins and the plugins element exists
	if ('update' == $options['action'] && 'plugin' == $options['type'] && isset($options['plugins'])) {
		// Iterate through the plugins being updated and check if ours is there
		foreach ($options['plugins'] as $plugin) {
			if ($plugin == $our_plugin) {
				$plugin_data = get_file_data(__FILE__, array( 'Version' => 'Version' ), false);
				if (isset($plugin_data['Version']) && '3.4.1' == $plugin_data['Version']) {
					// Set a transient to record that our plugin has just been updated
					set_transient('wp_opmc_odoo_updated', 1);
					update_option('wc_opmc_odoo_update_state', 1);
				}
			}
		}
	}
}
add_action('upgrader_process_complete', 'wp_opmc_odoo_upgrade_completed', 10, 2);

if (! class_exists('WC_ODOO_Integration')) :
	/* Constants */
	$plugin_data    = get_file_data(__FILE__, array( 'Version' => 'Version' ), false);
	$plugin_version = isset($plugin_data['Version']) ? $plugin_data['Version'] : '1.5.1';
	define('WC_ODOO_INTEGRATION_INIT_VERSION', $plugin_version);
	define('WC_ODOO_INTEGRATION_PLUGINURL', plugin_dir_url(__FILE__));
	define('WC_ODOO_INTEGRATION_PLUGINDIR', plugin_dir_path(__FILE__));

	/**
	 * WC Odoo Integration
	 */
	class WC_ODOO_Integration {
	

		protected static $instance = null;

		private $id = 'woocommmerce_odoo_integration';

		private $odoo_object = '';

		public static function get_instance() {
			// If the single instance hasn't been set, set it now.
			if (null == self::$instance) {
				self::$instance = new self();
			}

			return self::$instance;
		}



		public function __construct() {

			if (isset($_GET['clear_odoo_db']) && 1 == $_GET['clear_odoo_db']) {
				$this->clear_odoo_db();
			}
			// Do the WC active check
			if (! WC_Dependencies::woocommerce_active_check()) {
				//add_action( 'admin_init', array($this, 'opmc_odoo_deactivate' ));
				add_action('admin_notices', array( $this, 'opmc_odoo_admin_notice_activate_wc' ));
			}
			/* Checks if WooCommerce is installed.*/
			if (class_exists('WC_Integration')) {
				require_once WC_ODOO_INTEGRATION_PLUGINDIR . '/includes/class-wc-odoo-api.php';
				require_once WC_ODOO_INTEGRATION_PLUGINDIR . '/includes/class-helpers-functions.php';
				require_once WC_ODOO_INTEGRATION_PLUGINDIR . '/includes/class-common-functions.php';
				require_once WC_ODOO_INTEGRATION_PLUGINDIR . '/includes/class-wc-odoo-cron.php';
				add_filter('plugin_action_links', array( $this, 'add_plugin_links' ), 10, 5);

				add_action('admin_menu', array( $this, 'odoo_metabox' ));
				add_action('save_post', array( $this, 'odoo_opmc_save_meta' ), 10, 2);
				add_filter('manage_edit-product_columns', array( $this, 'odoo_status_visibility_product_columns' ), 10);
				add_action('manage_product_posts_custom_column', array( $this, 'add_odoo_status_product_column_contents' ), 10, 2);
				add_action('admin_head', array( $this, 'customize_admin_column_width' ), 10, 2);
				
				$odooApi = new WC_ODOO_API();
				$helper  = WC_ODOO_Helpers::getHelper();
				if (is_admin()) {
					require_once WC_ODOO_INTEGRATION_PLUGINDIR . '/includes/class-wc-odoo-admin-settings.php';
					/* Register the integration. */
					add_filter('woocommerce_integrations', array( $this, 'add_integration' ));
					// $settings = new WC_ODOO_Integration_Settings();
					add_action('wp_ajax_odoo_test_Cron', array( $this, 'odoo_test_Cron' ));
					add_action('wp_ajax_odoo_export_product_by_date', array( $this, 'odoo_export_product_by_date' ));
					add_action('wp_ajax_odoo_export_customer_by_date', array( $this, 'odoo_export_customer_by_date' ));
					add_action('wp_ajax_odoo_import_customer_by_date', array( $this, 'odoo_import_customer_by_date' ));

					$c = new WC_ODOO_Common_Functions();
					add_action('admin_notices', array( $c, 'gettingStarted' ));
					add_action('admin_notices', array( $c, 'opmc_admin_notice' ));
					add_action('admin_notices', array( $c, 'product_import_status' ));
					add_action('admin_notices', array( $c, 'product_export_status' ));
					add_action('admin_notices', array( $c, 'customer_export_status' ));
					add_action('admin_notices', array( $c, 'customer_import_status' ));
					add_action('admin_notices', array( $c, 'order_export_status' ));
					add_action('admin_notices', array( $c, 'order_import_status' ));

					add_action('wp_ajax_opmc_odoo_product_import_notices', array( $c, 'opmc_update_imp_pro_notices' ));
					add_action('wp_ajax_opmc_odoo_product_export_notices', array( $c, 'opmc_update_exp_pro_notices' ));
					add_action('wp_ajax_opmc_odoo_order_export_notices', array( $c, 'opmc_update_exp_order_notices' ));
					add_action('wp_ajax_opmc_odoo_order_import_notices', array( $c, 'opmc_update_imp_order_notices' ));
					add_action('wp_ajax_opmc_odoo_customer_export_notices', array( $c, 'opmc_update_exp_customer_notices' ));
					add_action('wp_ajax_opmc_odoo_customer_import_notices', array( $c, 'opmc_update_imp_customer_notices' ));

					/* PLUGINS-2244 */
					add_action('wp_ajax_odoo_view_debug_logs', array( $this, 'odoo_view_debug_logs' ));
					/* PLUGINS-2244 End */
				} elseif (function_exists('woocommerce') && is_checkout()) {
						/* Include our integration class.*/
					if ( $odooApi->is_authenticate() ) {
						if ( $odooApi->is_multi_company() ) {
							$multi_company_func = '/multi-company-files';
						} else {
							$multi_company_func = '';
						}
						require_once WC_ODOO_INTEGRATION_PLUGINDIR . 'includes' . $multi_company_func . '/class-wc-odoo-functions.php';
						// require_once(WC_ODOO_INTEGRATION_PLUGINDIR . '/includes/class-wc-odoo-api.php');
						// require_once(WC_ODOO_INTEGRATION_PLUGINDIR . '/includes/class-wc-odoo-cron.php');
					}
				}
			}

			if ('' == get_option('is_opmc_odoo_installed') || null == get_option('is_opmc_odoo_installed')) {
				global $wpdb;
				$wpdb->query("DELETE FROM `$wpdb->postmeta` WHERE `meta_key` LIKE '%odoo%'");
				$wpdb->query("DELETE FROM `$wpdb->usermeta` WHERE `meta_key` LIKE '%odoo%'");
				$wpdb->query("DELETE FROM `{$wpdb->termmeta}` WHERE `meta_key` LIKE '%odoo%'");
				$wpdb->query("DELETE FROM `{$wpdb->order_itemmeta}` WHERE `meta_key` LIKE '%_order_line_id%'");
				$wpdb->query("DELETE FROM `{$wpdb->options}` WHERE `option_name` LIKE '%opmc_odoo%'");
				update_option('is_opmc_odoo_installed', 'yes');
			}

			/* End here */
		}
		/*
		public function opmc_odoo_deactivate() {
			deactivate_plugins( plugin_basename( __FILE__ ) );
		} */
		
		/* PLUGINS-2244 */
		public function odoo_view_debug_logs() {
			$odooApi = new WC_ODOO_API();
			if ($odooApi->is_multi_company()) {
				$multi_company_func = '/multi-company-files';
			} else {
				$multi_company_func = '';
			}
			require_once WC_ODOO_INTEGRATION_PLUGINDIR . 'includes' . $multi_company_func . '/class-wc-odoo-functions.php';
			$odoo_function = new WC_ODOO_Functions();
			$odoo_function->odoo_view_debug_logs();
		}
		/* PLUGINS-2244 End */
		/**
		 * Display the notice
		 *
		 * @since  3.5.2
		 *
		 */
		public function opmc_odoo_admin_notice_activate_wc() {
			?>
			<div class="error">
				<p>
				<?php
				/* translators: 1: href link for the extension 2: closing href tag */
				printf(esc_html(__('%1$sOdoo For WooCommerce%2$s%3$s
					Odoo For WooCommerce requires %4$sWooCommerce%5$s to be installed and active. %6$sActivate WooCommerce%7$s', 'woocommerce-taxamo')), '<b>', '</b>', '<br><br>', '<a href="' . esc_url(admin_url('plugin-install.php?tab=search&s=WooCommerce&plugin-search-input=Search+Plugins')) . '">', '</a>', '<a href="' . esc_url(wp_nonce_url(admin_url('plugins.php?action=activate&plugin=woocommerce/woocommerce.php'), 'activate-plugin_woocommerce/woocommerce.php')) . '">', '</a>');
				?>
				</p>
			</div>
			<?php
		}

		public static function get_logger() {
			global $woocommerce;

			if (class_exists('WC_Logger')) {
				return new WC_Logger();
			} else {
				return $woocommerce->logger();
			}
		}

		public function odoo_test_Cron() {
			$odooApi = new WC_ODOO_API();
			if ($odooApi->is_multi_company()) {
				$multi_company_func = '/multi-company-files';
			} else {
				$multi_company_func = '';
			}
			require_once WC_ODOO_INTEGRATION_PLUGINDIR . 'includes' . $multi_company_func . '/class-wc-odoo-functions.php';
			$odoo_object = new WC_ODOO_Functions();
			$odoo_object->do_import_products();
			// $odoo_function->odoo_import_customer_by_date();
		}

		public function clear_odoo_db() {
			global $wpdb;
			// var_dump($wpdb->query("DELETE FROM `{$wpdb->options}` WHERE `option_name` LIKE '%odoo_creds_validated%'"));die('test');

			$wpdb->query("DELETE FROM `{$wpdb->postmeta}` WHERE `meta_key` LIKE '%odoo%'");
			$wpdb->query("DELETE FROM `{$wpdb->usermeta}` WHERE `meta_key` LIKE '%odoo%'");
			$wpdb->query("DELETE FROM `{$wpdb->termmeta}` WHERE `meta_key` LIKE '%odoo%'");
			$wpdb->query("DELETE FROM `{$wpdb->options}` WHERE `option_name` LIKE '%opmc_odoo_%'");
			$wpdb->query("DELETE FROM `{$wpdb->options}` WHERE `option_name` LIKE '%odoo_shipping_product_id%'");
			$wpdb->query("DELETE FROM `{$wpdb->options}` WHERE `option_name` LIKE '%is_opmc_odoo_creds_validated%'");
			$wpdb->query("DELETE FROM `{$wpdb->options}` WHERE `option_name` LIKE '%is_opmc_odoo_authenticated%'");
			$wpdb->query("DELETE FROM `{$wpdb->options}` WHERE `option_name` LIKE '%woocommerce_woocommmerce_odoo_integration_settings%'");
			$url = add_query_arg(
				array(
					'page'    => 'wc-settings',
					'tab'     => 'integration',
					'section' => 'woocommmerce_odoo_integration',
				),
				admin_url('admin.php')
			);
			
			wp_safe_redirect($url);
			exit;
		}

		public function odoo_export_product_by_date() {
			$odooApi = new WC_ODOO_API();
			if ($odooApi->is_multi_company()) {
				$multi_company_func = '/multi-company-files';
			} else {
				$multi_company_func = '';
			}
			require_once WC_ODOO_INTEGRATION_PLUGINDIR . 'includes' . $multi_company_func . '/class-wc-odoo-functions.php';
			$odoo_function = new WC_ODOO_Functions();
			$odoo_function->odoo_export_product_by_date();
		}

		public function odoo_export_customer_by_date() {
			$odooApi = new WC_ODOO_API();
			if ($odooApi->is_multi_company()) {
				$multi_company_func = '/multi-company-files';
			} else {
				$multi_company_func = '';
			}
			require_once WC_ODOO_INTEGRATION_PLUGINDIR . 'includes' . $multi_company_func . '/class-wc-odoo-functions.php';
			$odoo_function = new WC_ODOO_Functions();
			$odoo_function->odoo_export_customer_by_date();
		}

		public function odoo_import_customer_by_date() {
			$odooApi = new WC_ODOO_API();
			if ($odooApi->is_multi_company()) {
				$multi_company_func = '/multi-company-files';
			} else {
				$multi_company_func = '';
			}
			require_once WC_ODOO_INTEGRATION_PLUGINDIR . 'includes' . $multi_company_func . '/class-wc-odoo-functions.php';
			$odoo_function = new WC_ODOO_Functions();
			$odoo_function->odoo_import_customer_by_date();
		}

		/**
		 * Add a new  integration to WooCommerce.
		 */
		public function add_integration( $integrations ) {
			$integrations[] = 'WC_ODOO_Integration_Settings';
			return $integrations;
		}

		public function add_plugin_links( $actions, $plugin_file ) {
			static $plugin;

			if (! isset($plugin)) {
				$plugin = plugin_basename(__FILE__);
			}
			if ($plugin == $plugin_file) {
				$settings  = array( 'settings' => '<a href=" ' . $this->get_settings_url() . '">' . __('Settings', 'wc-odoo-integration') . '</a>' );
				$site_link = array( 'support' => '<a href="https://docs.woocommerce.com/document/odoo-for-woocommerce/" target="_blank">' . __('Support', 'wc-odoo-integration') . '</a>' );

				$actions = array_merge($settings, $actions);
				$actions = array_merge($site_link, $actions);
			}
			return $actions;
		}

		/**
		 * Add Odoo Meta box to product
		 */
		public function odoo_metabox() {
			add_meta_box(
				'odoo_metabox',
				__('Odoo Sync', 'wc-odoo-integration'),
				array( $this, 'odoo_metabox_callback' ),
				'product',
				'side',
				'high'
			);
		}

		public function odoo_metabox_callback( $post ) {
			// pr($post);die();
			$odoo_product_id      = get_post_meta( $post->ID, '_odoo_id', true );
			$odoo_exclude_product = get_post_meta( $post->ID, '_exclude_product_to_sync', true );
			if ( isset( $odoo_exclude_product ) && 'yes' == $odoo_exclude_product ) {
				$odoo_product_id = __( 'Product Not to be synced', 'wc-odoo-integration' );
			} elseif ( empty( $odoo_product_id ) ) {
					$odoo_product_id = __( 'Not synced', 'wc-odoo-integration' );
			}
			wp_nonce_field('odoometa', '_odoononce');
			?>
			<p>
				<label for="_exclude_product_to_sync">
				<input type="checkbox" name="_exclude_product_to_sync" id="_exclude_product_to_sync" value="yes" 
				<?php
				if (! empty($odoo_exclude_product)) {
					checked($odoo_exclude_product, 'yes');
				}
				?>
				 />
				<?php echo esc_html_e('Exclude from Oddo', 'wc-odoo-integration'); ?>
				</label>
			</p>
			<?php
		}

		public function odoo_opmc_save_meta( $post_id, $post ) {
			// nonce check
			if (! isset($_POST['_odoononce']) || ! wp_verify_nonce(sanitize_text_field($_POST['_odoononce']), 'odoometa')) {
				return $post_id;
			}

			// check current user permissions
			$post_type = get_post_type_object($post->post_type);

			if (! current_user_can($post_type->cap->edit_post, $post_id)) {
				return $post_id;
			}

			// Do not save the data if autosave
			if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
				return $post_id;
			}

			// define your own post type here
			if ('product' !== $post->post_type) {
				return $post_id;
			}

			if (isset($_POST['_exclude_product_to_sync'])) {
				update_post_meta($post_id, '_exclude_product_to_sync', sanitize_text_field($_POST['_exclude_product_to_sync']));
			} else {
				delete_post_meta($post_id, '_exclude_product_to_sync');
			}

			return $post_id;
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
				admin_url('admin.php')
			);
		}

		public function odoo_status_visibility_product_columns( $columns ) {
			$columns['odoo_status_field'] = __('Odoo Last Sync', 'wc-odoo-integration');
			return $columns;
		}
		// Add content to new column row in Admin products list
		public function add_odoo_status_product_column_contents( $column, $postid ) {
			if ('odoo_status_field' == $column ) {
				global $items;
				$synced = get_post_meta($postid, '_synced_data_rec');
				$last_date_rec = get_post_meta($postid, '_synced_last_date_rec');
				//print_r($blocked);
				//echo $blocked;
				if (!empty($synced) && !empty($last_date_rec) && 'synced' === $synced[0]) {
					echo '<b><span style="color:green;"> Synced - ' . esc_html($last_date_rec[0]) .
					'</span></b>';
				} else {
					echo '<b><span>
					Not Synced
					</span></b>';
				}
			}
		} /*End New Custom Column*/
		public function customize_admin_column_width() {
			echo '<style type="text/css">';
			echo 'table.wp-list-table .column-odoo_status_field { width: 11%;}';
			echo '</style>';
		}
	}
	
	if (is_woocommerce_active()) {
		add_action('plugins_loaded', array( 'WC_ODOO_Integration', 'get_instance' ));
		
		add_action('before_woocommerce_init', function () {
			if (class_exists('\Automattic\WooCommerce\Utilities\FeaturesUtil')) {
				\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('custom_order_tables', __FILE__, true);
			}
		});
	}
endif;
