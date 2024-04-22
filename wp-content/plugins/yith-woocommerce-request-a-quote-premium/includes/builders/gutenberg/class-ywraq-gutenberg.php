<?php //phpcs:ignore WordPress.Files.FileName.InvalidClassFileName
/**
 * YWRAQ_Gutenberg class to add custom blocks
 *
 * @class   YWRAQ_Gutenberg
 * @package YITH\RequestAQuote
 * @since   4.0.0
 * @author  YITH <plugins@yithemes.com>
 */

defined( 'ABSPATH' ) || exit;


if ( ! class_exists( 'YWRAQ_Gutenberg' ) ) {
	/**
	 * Class YWRAQ_Gutenberg
	 */
	class YWRAQ_Gutenberg {

		/**
		 * Custom blocks
		 *
		 * @var array
		 */
		public $blocks = array(
			'yith/ywraq-products-table',
			'yith/ywraq-products-totals',
			'yith/ywraq-quote-number',
			'yith/ywraq-customer-info',
			'yith/ywraq-quote-date',
			'yith/ywraq-quote-buttons',
		);

		/**
		 * Single instance of the class.
		 *
		 * @var YWRAQ_Gutenberg
		 */
		protected static $instance;

		/**
		 * Returns single instance of the class
		 *
		 * @return YWRAQ_Gutenberg
		 * @since  1.0.0
		 */
		public static function get_instance() {
			return ! is_null( self::$instance ) ? self::$instance : self::$instance = new self();
		}

		/**
		 * Constructor
		 *
		 * Initialize class and registers actions and filters to be used
		 *
		 * @since  1.0.0
		 */
		public function __construct() {

			add_action( 'init', array( $this, 'gutenberg_integration' ) );
			add_filter( 'allowed_block_types_all', array( $this, 'allowed_block_types' ), 10, 2 );
			add_filter( 'block_categories_all', array( $this, 'block_category' ), 100, 2 );
			add_action( 'after_setup_theme', array( $this, 'disable_theme_palette' ), 100 );

			add_action( 'wp_ajax_ywraq_get_template_pdf_content', array( $this, 'get_template_pdf_content' ) );
			add_action( 'wp_ajax_ywraq_get_pdf_templates', array( $this, 'get_templates' ) );
		}

		/**
		 * Return the response to get the remote templates
		 *
		 * @param string $url URL.
		 * @param bool   $casper
		 *
		 * @return array|WP_Error
		 */
		private function get_response( $url, $casper = false ) {

			if ( $casper ) {
				$api_url = 'https://casper.yithemes.com/resources/yith-woocommerce-request-a-quote/pdf-templates/';
			} else {
				$api_url = 'https://plugins.yithemes.com/resources/yith-woocommerce-request-a-quote/pdf-templates/';
			}

			$api_call_args = array(
				'timeout'    => apply_filters( 'ywraq_get_templates_timeout', 15 ),
				'user-agent' => 'YITH Request a Quote for WooCommerce Premium/' . YITH_YWRAQ_VERSION . '; ' . get_site_url(),
			);

			return wp_remote_get( $api_url . $url, $api_call_args );

		}

		/**
		 * Get the templates
		 *
		 * @return false
		 */
		public function get_templates() {

			check_ajax_referer( 'get_templates', 'security' );

			$transient = 'ywraq_pdf_templates_' . YITH_YWRAQ_VERSION;
			$templates = get_transient( $transient );

			if ( false !== $templates ) {
				wp_send_json(
					array( 'templates' => $templates )
				);
			} else {

				$response = $this->get_response( 'list.json' );
				if ( 200 === wp_remote_retrieve_response_code( $response ) ) {
					$template_list = json_decode( wp_remote_retrieve_body( $response ), true );
					set_transient( $transient, $template_list, DAY_IN_SECONDS );

					wp_send_json(
						array( 'templates' => $template_list )
					);
				} elseif ( 403 === wp_remote_retrieve_response_code( $response ) ) {
					$response      = $this->get_response( 'list.json', true );
					$template_list = json_decode( wp_remote_retrieve_body( $response ), true );
					set_transient( $transient, $template_list, DAY_IN_SECONDS );

					wp_send_json(
						array( 'templates' => $template_list )
					);
				} else {
					wp_send_json_error( array( 'error' => 'Call to remote template failed' ) );
				}
			}

		}


		/**
		 * Return the content of template
		 *
		 * @return false|void
		 */
		public function get_template_pdf_content() {
			check_ajax_referer( 'get_template_content', 'security' );

			$template = sanitize_text_field( wp_unslash( $_POST['template_id'] ) );

			$transient        = 'ywraq_pdf_templates_content_' . YITH_YWRAQ_VERSION;
			$template_content = get_transient( $transient );

			if ( false !== $template_content && isset( $template_content[ $template ] ) ) {
				wp_send_json(
					array( 'content' => $template_content )
				);
			} else {
				$response = $this->get_response( 'content/' . $template . '.txt' );
				if ( is_wp_error( $response ) ) {
					wp_send_json_error( array( 'error' => 'Call to remote template failed' ) );
				}

				if ( 200 === wp_remote_retrieve_response_code( $response ) ) {
					$template_content               = wp_remote_retrieve_body( $response );
					$template_contents[ $template ] = $template_content;
					set_transient( $transient, $template_contents, DAY_IN_SECONDS );
					wp_send_json(
						array( 'content' => $template_content )
					);
				} elseif ( 403 === wp_remote_retrieve_response_code( $response ) ) {
					$response                       = $this->get_response( 'content/' . $template . '.txt', true );
					$template_content               = wp_remote_retrieve_body( $response );
					$template_contents[ $template ] = $template_content;
					set_transient( $transient, $template_contents, DAY_IN_SECONDS );
					wp_send_json(
						array( 'content' => $template_content )
					);
				}
			}

		}

		/**
		 * Removing the theme palette because the colors on pdf are not available
		 *
		 * @return void
		 */
		public function disable_theme_palette() {
			if ( ywraq_check_valid_admin_page( YITH_YWRAQ_Post_Types::$pdf_template ) ) {
				add_theme_support( 'editor-color-palette', array() );
			}
		}
		

		/**
		 * Gutenberg Integration
		 */
		public function gutenberg_integration() {

			// Register blocks for PDF template.
			if ( ywraq_check_valid_admin_page( YITH_YWRAQ_Post_Types::$pdf_template ) ) {

				$version = YITH_YWRAQ_VERSION;
				wp_register_style( 'ywraq-pdf-template-builder', YITH_YWRAQ_ASSETS_URL . '/css/ywraq-pdf-template-builder.css', array( 'wp-edit-blocks' ), $version, false );
				wp_register_script( 'ywraq-pdf-template-builder-script', YITH_YWRAQ_URL . 'dist/blocks/index.js', array( 'wp-blocks', 'wp-element', 'wp-editor', 'wp-components', 'wp-plugins', 'wp-i18n', 'wc-components', 'wp-mediaelement' ), $version, false );
				wp_localize_script(
					'ywraq-pdf-template-builder-script',
					'ywraq_pdf_template',
					array(
						'ajaxurl' => admin_url( 'admin-ajax.php' ),
						'preview_image_url' => YITH_YWRAQ_ASSETS_URL . '/preview/images',
						'customer_info_placeholders' => ywraq_pdf_template_builder()->get_customer_info_placeholders(),
						'licence_key' => ywraq_get_license(),
						'licence_url' => ywraq_get_license_activation_url(),
						'slug' => YITH_YWRAQ_SLUG,
						'today' => date_i18n( wc_date_format(), time() ),
						'tomorrow' => date_i18n( wc_date_format(), time() + DAY_IN_SECONDS ),
						'get_template_content_security' => wp_create_nonce( 'get_template_content' ),
						'get_templates_security' => wp_create_nonce( 'get_templates' ),
						'preview_products' => YWRAQ_PDF_Template_Builder::get_instance()->get_preview_products()
					)
				);

				$assets = array(
					'script'       => 'ywraq-pdf-template-builder-script',
					'editor_style' => 'ywraq-pdf-template-builder',
					'style'        => 'ywraq-pdf-template-builder',
				);

				foreach ( $this->blocks as $block ) {
					register_block_type( $block, $assets );
				}

				if ( function_exists( 'wp_set_script_translations' ) ) {
					wp_set_script_translations( 'ywraq-pdf-template-builder-script', 'yith-woocommerce-request-a-quote', YITH_YWRAQ_DIR . 'languages' );
				}
			}

		}

		/**
		 * Add block category
		 *
		 * @param array   $categories Array block categories array.
		 * @param WP_Post $post Current post.
		 *
		 * @return array block categories
		 */
		public function block_category( $categories, $post ) {

			$found_key = array_search( 'yith-blocks', array_column( $categories, 'slug' ), true );

			if ( ! $found_key ) {
				$categories[] = array(
					'slug'  => 'yith-blocks',
					'title' => _x( 'YITH', '[gutenberg]: Category Name', 'yith-plugin-fw' ),
				);
			}

			return $categories;
		}


		/**
		 * Select specific block from Gutenberg
		 *
		 * @param array                   $allowed_blocks Current blocks.
		 * @param WP_Block_Editor_Context $block_editor_context The current block editor.
		 *
		 * return array
		 */
		public function allowed_block_types( $allowed_blocks, $block_editor_context ) {
			$post = $block_editor_context->post;
			if ( $post && YITH_YWRAQ_Post_Types::$pdf_template === $post->post_type ) {
				$allowed_blocks = $this->get_allowed_block_types();
			}

			return $allowed_blocks;
		}

		/**
		 * Get the allowed blocks types
		 *
		 * @return array
		 */
		public function get_allowed_block_types(  ) {
			$allowed_blocks = array(
				'core/image',
				'core/paragraph',
				'core/heading',
				'core/list',
				'core/columns',
				'core/buttons',
				'core/separator',
				'core/spacer',
			);
			return array_merge( $allowed_blocks, $this->blocks );
		}
	}
}

/**
 * Unique access to instance of YWRAQ_Gutenberg class
 *
 * @return YWRAQ_Gutenberg
 */
function YWRAQ_Gutenberg() { //phpcs:ignore
	return YWRAQ_Gutenberg::get_instance();
}

YWRAQ_Gutenberg();
