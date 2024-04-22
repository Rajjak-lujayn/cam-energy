<?php
/**
 * Class to manage the plugins post types.
 *
 * @class   YITH_YWRAQ_Post_Types
 * @since   4.0.0
 * @author  YITH <plugins@yithemes.com>
 * @package YITH\RequestAQuote
 */

defined( 'ABSPATH' ) || exit;


if ( ! class_exists( 'YITH_YWRAQ_Post_Types' ) ) {

	/**
	 * Class YITH_YWRAQ_Post_Types
	 */
	class YITH_YWRAQ_Post_Types {

		/**
		 * Pdf Template Post Type
		 *
		 * @var string
		 * @static
		 */
		public static $pdf_template = 'ywraq-pdf-template';


		/**
		 * Hook in methods.
		 */
		public static function init() {
			add_action( 'init', array( __CLASS__, 'register_post_types' ), 5 );
			add_action( 'init', array( __CLASS__, 'create_default_template' ), 30 );
			add_action( 'admin_init', array( __CLASS__, 'add_capabilities' ) );
			add_filter( 'rest_prepare_' . self::$pdf_template, array( __CLASS__, 'filter_post_json' ), 10, 2 );

			add_filter(
				'use_block_editor_for_post_type',
				array(
					__CLASS__,
					'filter_use_block_editor_for_post_type',
				),
				10,
				2
			);
			add_action( 'rest_after_insert_' . self::$pdf_template, array( __CLASS__, 'save_meta' ), 10, 2 );
		}

		/**
		 * Save the template parent meta inside the pdf template.
		 *
		 * @param   WP_Post $post     Post.
		 * @param   array   $request  Request.
		 *
		 * @return void
		 */
		public static function save_meta( $post, $request ) {

			if ( isset( $request['template_parent'] ) ) {
				update_post_meta( $post->ID, '_template_parent', $request['template_parent'] );
			}
			if ( isset( $request['meta']['_template_parent'] ) ) {
				update_post_meta( $post->ID, '_template_parent', $request['meta']['_template_parent'] );
			}
			if ( isset( $request['meta']['_footer_content'] ) ) {
				update_post_meta( $post->ID, '_footer_content', $request['meta']['_footer_content'] );
			}
			update_post_meta( $post->ID, '_name', $post->post_title );
		}

		/**
		 * Register core post types.
		 */
		public static function register_post_types() {

			if ( post_type_exists( self::$pdf_template ) ) {
				return;
			}
			/**
			 * DO_ACTION:ywraq_before_register_post_type
			 *
			 * This action is triggered before the PDF Template post type registration
			 */
			do_action( 'ywraq_before_register_post_type' );

			/* PDF TEMPLATES  */

			$labels = array(
				'name'               => esc_html_x( 'PDF Templates', 'Post Type General Name', 'yith-woocommerce-request-a-quote' ),
				'singular_name'      => esc_html_x( 'PDF Template', 'Post Type Singular Name', 'yith-woocommerce-request-a-quote' ),
				'add_new_item'       => esc_html__( 'PDF Template', 'yith-woocommerce-request-a-quote' ),
				'add_new'            => esc_html__( '+ Add new template', 'yith-woocommerce-request-a-quote' ),
				'new_item'           => esc_html__( 'New template', 'yith-woocommerce-request-a-quote' ),
				'edit_item'          => esc_html__( 'Edit template', 'yith-woocommerce-request-a-quote' ),
				'view_item'          => esc_html__( 'View template', 'yith-woocommerce-request-a-quote' ),
				'search_items'       => esc_html__( 'Search template', 'yith-woocommerce-request-a-quote' ),
				'not_found'          => esc_html__( 'Not found', 'yith-woocommerce-request-a-quote' ),
				'not_found_in_trash' => esc_html__( 'Not found in Trash', 'yith-woocommerce-request-a-quote' ),
			);

			$post_type_args = array(
				'labels'                => $labels,
				'supports'              => array( 'editor', 'title' ),
				'hierarchical'          => false,
				'public'                => false,
				'show_ui'               => true,
				'show_in_menu'          => false,
				'menu_position'         => 10,
				'capability_type'       => self::$pdf_template,
				'capabilities'          => self::get_capabilities( self::$pdf_template ),
				'show_in_nav_menus'     => false,
				'has_archive'           => true,
				'exclude_from_search'   => true,
				'rewrite'               => false,
				'publicly_queryable'    => false,
				'query_var'             => false,
				'show_in_rest'          => true,
				'rest_base'             => 'pdf_template',
				'rest_controller_class' => 'WP_REST_Posts_Controller',
			);
			/**
			 * APPLY_FILTERS:ywraq_register_post_type_ywraq-pdf-template
			 *
			 * Filter the attributes for the registration of ywraq-pdf-template custom post type
			 *
			 * @param   array  $post_type_args  Attributes to filter.
			 *
			 * @return array
			 */
			register_post_type( self::$pdf_template, apply_filters( 'ywraq_register_post_type_' . self::$pdf_template, $post_type_args ) );
			/**
			 * DO_ACTION:ywraq_after_register_post_type
			 *
			 * This action is triggered after the PDF Template post type registration
			 */
			do_action( 'ywraq_after_register_post_type' );

			register_post_meta(
				self::$pdf_template,
				'_footer_content',
				array(
					'show_in_rest' => true,
					'single'       => true,
					'type'         => 'string',
				)
			);

			register_post_meta(
				self::$pdf_template,
				'_template_parent',
				array(
					'show_in_rest' => true,
					'single'       => true,
					'type'         => 'string',
				)
			);
		}

		/**
		 * Get capabilities for custom post type
		 *
		 * @param   string $capability_type  Capability name.
		 *
		 * @return  array
		 *
		 * @since 4.0.0
		 */
		public static function get_capabilities( $capability_type ) {
			return array(
				'edit_post'              => "edit_{$capability_type}",
				'read_post'              => "read_{$capability_type}",
				'delete_post'            => "delete_{$capability_type}",
				'edit_posts'             => "edit_{$capability_type}s",
				'edit_others_posts'      => "edit_others_{$capability_type}s",
				'publish_posts'          => "publish_{$capability_type}s",
				'read_private_posts'     => "read_private_{$capability_type}s",
				'delete_posts'           => "delete_{$capability_type}s",
				'delete_private_posts'   => "delete_private_{$capability_type}s",
				'delete_published_posts' => "delete_published_{$capability_type}s",
				'delete_others_posts'    => "delete_others_{$capability_type}s",
				'edit_private_posts'     => "edit_private_{$capability_type}s",
				'edit_published_posts'   => "edit_published_{$capability_type}s",
				'create_posts'           => "edit_{$capability_type}s",
				'manage_posts'           => "manage_{$capability_type}s",
			);
		}

		/**
		 * Add the capability
		 */
		public static function add_capabilities() {
			self::add_admin_capabilities( self::$pdf_template );
		}

		/**
		 * Add management capabilities to Admin and Shop Manager
		 *
		 * @param   string $ctp  Custom post type.
		 *
		 * @return  void
		 * @since   4.0.0
		 */
		public static function add_admin_capabilities( $ctp ) {
			$caps = self::get_capabilities( $ctp );

			$roles = array(
				'administrator',
				'shop_manager',
			);

			foreach ( $roles as $role_slug ) {

				$role = get_role( $role_slug );

				if ( ! $role ) {
					continue;
				}

				foreach ( $caps as $key => $cap ) {
					$role->add_cap( $cap );
				}
			}
		}

		/**
		 * Duplicate post type
		 *
		 * @param   WP_Post $original_post  Original post.
		 * @param   string  $post_type      Post type.
		 *
		 * @return int
		 */
		public static function duplicate_post( $original_post, $post_type ) {
			$new_title = ywraq_get_unique_post_title( $original_post->post_title, $original_post->ID, self::$pdf_template );
			$new_post  = array(
				'post_status'  => 'publish',
				'post_type'    => $post_type,
				'post_title'   => $new_title,
				'post_content' => wp_slash( $original_post->post_content ),
			);

			$new_post_id = wp_insert_post( $new_post );
			$metas       = get_post_meta( $original_post->ID );

			if ( ! empty( $metas ) ) {
				foreach ( $metas as $meta_key => $meta_value ) {
					if ( in_array( $meta_key, array( '_default', '_edit_lock', '_edit_last' ), true ) ) {
						continue;
					}

					update_post_meta( $new_post_id, $meta_key, maybe_unserialize( $meta_value[0] ) );
				}
			}

			update_post_meta( $new_post_id, '_name', $new_title );

			return $new_post_id;
		}

		/**
		 * Check if the block editor can be used
		 *
		 * @param   bool   $return     Current value.
		 * @param   string $post_type  Post type.
		 */
		public static function filter_use_block_editor_for_post_type( $return, $post_type ) {

			if ( self::$pdf_template === $post_type ) {
				$return = false;
			}

			return $return;
		}

		/**
		 * Add meta to the post via REST
		 *
		 * @param   WP_REST_Response $data  The response object.
		 * @param   WP_Post          $post  Post requested.
		 *
		 * @return WP_REST_Response
		 */
		public static function filter_post_json( $data, $post ) {

			if ( $post->post_type !== self::$pdf_template ) {
				return $data;
			}

			$data->data['template_parent']         = get_post_meta( $post->ID, '_template_parent', true );
			$data->data['template_parent']         = empty( $data->data['template_parent'] ) ? 'default' : $data->data['template_parent'];
			$data->data['default']                 = (bool) get_post_meta( $post->ID, '_default', true );
			$data->data['meta']['_footer_content'] = get_post_meta( $post->ID, '_footer_content', true );

			return $data;
		}

		/**
		 * Create the first pdf template if not exist
		 */
		public static function create_default_template() {

			$query_args = array(
				'post_type'   => self::$pdf_template,
				'numberposts' => 1,
				'fields'      => 'ids',
			);

			$posts = get_posts( $query_args );

			if ( ! $posts ) {
				$template_data = array(
					'post_status'    => 'publish',
					'post_type'      => self::$pdf_template,
					'post_author'    => 1,
					'post_name'      => 'default-pdf-template',
					'post_title'     => _x( 'Simple template', 'name of the pdf default template', 'yith-woocommerce-request-a-quote' ),
					'post_content'   => wp_slash( ywraq_get_default_pdf_content() ),
					'post_parent'    => 0,
					'comment_status' => 'closed',
				);

				$post_id = wp_insert_post( $template_data );

				if ( $post_id ) {
					// sync the main option.
					update_option( 'ywraq_pdf_custom_templates', $post_id );

					update_post_meta( $post_id, '_template_parent', 'default' );
					update_post_meta( $post_id, '_default', 1 );
					update_post_meta( $post_id, '_name', __( 'Simple template', 'yith-woocommerce-request-a-quote' ) );
				}
			}
		}

		/**
		 * Return the list of pdf templates
		 *
		 * @return array
		 */
		public static function get_pdf_template_list() {
			$template_list = array();

			$posts = get_posts(
				array(
					'post_type'   => self::$pdf_template,
					'numberposts' => - 1,
					'order_by'    => 'post_title',
					'order'       => 'ASC',
				)
			);

			if ( $posts ) {
				foreach ( $posts as $post ) {
					$template_list[ $post->ID ] = $post->post_title;
				}
			}

			return $template_list;
		}
	}

	YITH_YWRAQ_Post_Types::init();
}
