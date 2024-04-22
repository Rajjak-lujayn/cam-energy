<?php
/**
 * Plugin Name: YITH Request a Quote for WooCommerce Premium
 * Plugin URI: https://yithemes.com/themes/plugins/yith-woocommerce-request-a-quote
 * Description: <code><strong>YITH Request a Quote for WooCommerce</strong></code> lets your customers ask for an estimate of a list of products they are interested in. It allows hiding prices and/or the "Add to cart" button so that your customers can request a quote on every product page. <a href="https://yithemes.com/" target="_blank">Get more plugins for your e-commerce shop on <strong>YITH</strong></a>.
 * Version: 4.22.0
 * Author: YITH
 * Author URI: https://yithemes.com/
 * Text Domain: yith-woocommerce-request-a-quote
 * Domain Path: /languages/
 * WC requires at least: 8.4.0
 * WC tested up to: 8.6.0
 *
 * @package YITH\RequestAQuote
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly.

if ( ! function_exists( 'is_plugin_active' ) ) {
	require_once ABSPATH . 'wp-admin/includes/plugin.php';
}

$wp_upload_dir = wp_upload_dir();

// Define constants ________________________________________.

! defined( 'YITH_YWRAQ_DIR' ) && define( 'YITH_YWRAQ_DIR', plugin_dir_path( __FILE__ ) );
! defined( 'YITH_YWRAQ_VERSION' ) && define( 'YITH_YWRAQ_VERSION', '4.22.0' );
! defined( 'YITH_YWRAQ_PREMIUM' ) && define( 'YITH_YWRAQ_PREMIUM', plugin_basename( __FILE__ ) );
! defined( 'YITH_YWRAQ_FILE' ) && define( 'YITH_YWRAQ_FILE', __FILE__ );
! defined( 'YITH_YWRAQ_URL' ) && define( 'YITH_YWRAQ_URL', plugins_url( '/', __FILE__ ) );
! defined( 'YITH_YWRAQ_ASSETS_URL' ) && define( 'YITH_YWRAQ_ASSETS_URL', YITH_YWRAQ_URL . 'assets' );
! defined( 'YITH_YWRAQ_TEMPLATE_PATH' ) && define( 'YITH_YWRAQ_TEMPLATE_PATH', YITH_YWRAQ_DIR . 'templates' );
! defined( 'YITH_YWRAQ_VIEW_PATH' ) && define( 'YITH_YWRAQ_VIEW_PATH', YITH_YWRAQ_DIR . 'views' );
! defined( 'YITH_YWRAQ_INIT' ) && define( 'YITH_YWRAQ_INIT', plugin_basename( __FILE__ ) );
! defined( 'YITH_YWRAQ_INC' ) && define( 'YITH_YWRAQ_INC', YITH_YWRAQ_DIR . 'includes/' );
! defined( 'YITH_YWRAQ_DOMPDF_DIR' ) && define( 'YITH_YWRAQ_DOMPDF_DIR', YITH_YWRAQ_DIR . 'lib/dompdf/' );
! defined( 'YITH_YWRAQ_SLUG' ) && define( 'YITH_YWRAQ_SLUG', 'yith-woocommerce-request-a-quote' );
! defined( 'YITH_YWRAQ_SECRET_KEY' ) && define( 'YITH_YWRAQ_SECRET_KEY', 'vT6zK6QAp0DD2H2d9NoE' );
! defined( 'YITH_YWRAQ_PLUGIN_NAME' ) && define( 'YITH_YWRAQ_PLUGIN_NAME', 'YITH Request a Quote for WooCommerce' );
! defined( 'YITH_YWRAQ_DOCUMENT_SAVE_DIR' ) && define( 'YITH_YWRAQ_DOCUMENT_SAVE_DIR', $wp_upload_dir['basedir'] . '/yith_ywraq/' );
! defined( 'YITH_YWRAQ_SAVE_QUOTE_URL' ) && define( 'YITH_YWRAQ_SAVE_QUOTE_URL', $wp_upload_dir['baseurl'] . '/yith_ywraq/' );


// Free version deactivation if installed __________________.
if ( ! function_exists( 'yith_deactivate_plugins' ) ) {
	require_once 'plugin-fw/yit-deactive-plugin.php';
}
yith_deactivate_plugins( 'YITH_YWRAQ_FREE_INIT', plugin_basename( __FILE__ ) );

// Yith jetpack deactivation if installed __________________.
if ( function_exists( 'yith_deactive_jetpack_module' ) ) {
	global $yith_jetpack_1;
	yith_deactive_jetpack_module( $yith_jetpack_1, 'YITH_YWRAQ_PREMIUM', plugin_basename( __FILE__ ) );
}

/* Plugin Framework Version Check */
if ( ! function_exists( 'yit_maybe_plugin_fw_loader' ) && file_exists( YITH_YWRAQ_DIR . 'plugin-fw/init.php' ) ) {
	require_once YITH_YWRAQ_DIR . 'plugin-fw/init.php';
}
yit_maybe_plugin_fw_loader( YITH_YWRAQ_DIR );

if ( ! function_exists( 'yith_ywraq_install_woocommerce_admin_notice' ) ) {
	/**
	 * Administrator Notice that will display if WooCommerce plugin is deactivated.
	 */
	function yith_ywraq_install_woocommerce_admin_notice() {
		?>
		<div class="error">
			<p>
				<?php
				// translators: %s is the plugin name.
				echo esc_html( sprintf( __( '%s is enabled but not effective. It requires WooCommerce in order to work.', 'yith-woocommerce-request-a-quote' ), YITH_YWRAQ_PLUGIN_NAME ) );
				?>
			</p>
		</div>
		<?php
	}
}

if ( ! function_exists( 'yith_ywraq_premium_install' ) ) {
	/**
	 * Install the premium version.
	 */
	function yith_ywraq_premium_install() {
		if ( ! function_exists( 'WC' ) ) {
			add_action( 'admin_notices', 'yith_ywraq_install_woocommerce_admin_notice' );
		} else {
			require_once YITH_YWRAQ_INC . 'functions.yith-request-quote.php';
			require_once YITH_YWRAQ_INC . 'functions.yith-ywraq-wc-blocks.php';
			if ( ywraq_ninja_forms_installed() && is_admin() ) {
				require_once YITH_YWRAQ_INC . 'forms/ninja-forms/ywraq-ninja-forms.php';
				require_once YITH_YWRAQ_INC . 'forms/ninja-forms/ywraq-ninja-forms-tag.php';
				YWRAQ_Ninja_Forms();
			}
			/**
			 * DO_ACTION:yith_ywraq_init
			 *
			 * This action is triggered to install the plugin
			 */
			do_action( 'yith_ywraq_init' );
			add_action( 'plugins_loaded', 'yith_ywraq_premium_constructor', 12 );
		}
	}

	add_action( 'plugins_loaded', 'yith_ywraq_premium_install', 9 );
}

register_activation_hook( __FILE__, 'ywraq_protect_folder' );
register_activation_hook( __FILE__, 'yith_ywraq_reset_option_version' );
register_deactivation_hook( __FILE__, 'ywraq_rewrite_rules' );

if ( ! function_exists( 'ywraq_rewrite_rules' ) ) {
	/**
	 * Delete option
	 */
	function ywraq_rewrite_rules() {
		delete_option( 'yith-ywraq-flush-rewrite-rules' );
	}
}

if ( ! function_exists( 'ywraq_protect_folder' ) ) {
	/**
	 * Create files/directories to protect upload folders
	 */
	function ywraq_protect_folder() {

		$files = array(
			array(
				'base'    => YITH_YWRAQ_DOCUMENT_SAVE_DIR,
				'file'    => 'index.html',
				'content' => '',
			),
		);

		foreach ( $files as $file ) {
			if ( wp_mkdir_p( $file['base'] ) && ! file_exists( trailingslashit( $file['base'] ) . $file['file'] ) ) {
				$file_handle = @fopen( trailingslashit( $file['base'] ) . $file['file'], 'w' ); //phpcs:ignore
				if ( $file_handle ) {
					fwrite( $file_handle, $file['content'] ); //phpcs:ignore
					fclose( $file_handle ); //phpcs:ignore
				}
			}
		}
	}
}

if ( ! function_exists( 'yith_ywraq_reset_option_version' ) ) {
	/**
	 * Save the previous version on database
	 *
	 * @since 2.0.0
	 */
	function yith_ywraq_reset_option_version() {
		$old = get_option( 'yith_ywraq_option_version' );
		if ( $old ) {
			add_option( 'yith_ywraq_previous_version', $old );
		}

		delete_option( 'yith_ywraq_option_version' );
	}
}

if ( ! function_exists( 'yith_plugin_onboarding_registration_hook' ) ) {
	include_once 'plugin-upgrade/functions-yith-licence.php';
}
register_activation_hook( __FILE__, 'yith_plugin_onboarding_registration_hook' );

if ( ! function_exists( 'yith_ywraq_premium_constructor' ) ) {

	/**
	 * Load the plugin
	 */
	function yith_ywraq_premium_constructor() {
		// Load required classes and functions.

		// Woocommerce installation check _________________________.
		if ( ! function_exists( 'WC' ) ) {
			add_action( 'admin_notices', 'yith_ywraq_install_woocommerce_admin_notice' );

			return;
		}

		// Load ywraq text domain ___________________________________.
		load_plugin_textdomain( 'yith-woocommerce-request-a-quote', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );

		require_once YITH_YWRAQ_INC . 'functions.yith-request-quote.php';
		require_once YITH_YWRAQ_INC . 'class.yith-request-quote.php';
		require_once YITH_YWRAQ_INC . 'class.yith-request-quote-premium.php';

		YITH_Request_Quote_Premium();
	}
}
