<?php //phpcs:ignore WordPress.Files.FileName.InvalidClassFileName.
/**
 * Class to manage pdf templates
 *
 * @class   YITH_YWRAQ_Editor_PDF_Template
 * @since   4.0.0
 * @author  YITH <plugins@yithemes.com>
 * @package YITH\RequestAQuote
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'YWRAQ_Editor', false ) ) {
	include_once YITH_YWRAQ_INC . '/admin/cpt/abstract-class-ywraq-editor.php';
}

if ( ! class_exists( 'YITH_YWRAQ_Editor_PDF_Template' ) ) {

	/**
	 * Class YITH_YWRAQ_Editor_PDF_Template
	 */
	class YITH_YWRAQ_Editor_PDF_Template extends YWRAQ_Editor {

		/**
		 * Single instance of the class
		 *
		 * @var YITH_YWRAQ_Editor_PDF_Template
		 */
		protected static $instance;

		/**
		 *  Post type name
		 *
		 * @var string
		 */
		public $post_type = '';

		/**
		 * Returns single instance of the class
		 *
		 * @return YITH_YWRAQ_Editor_PDF_Template
		 * @since  4.0.0
		 */
		public static function get_instance() {
			return ! is_null( self::$instance ) ? self::$instance : self::$instance = new self();
		}

		/**
		 * Constructor
		 *
		 * Initialize plugin and registers actions and filters to be used
		 *
		 * @since 4.0.0
		 */
		public function __construct() {

			$this->post_type = YITH_YWRAQ_Post_Types::$pdf_template;

			$this->options = include_once YITH_YWRAQ_DIR . 'plugin-options/metabox/pdf-template-options.php';

			parent::__construct();

			add_action( 'add_meta_boxes', array( $this, 'add_metabox' ) );
			add_action( 'save_post', array( $this, 'save_post' ), 1, 2 );
			add_action( 'admin_enqueue_scripts', array( $this, 'gutenberg_editor_init' ), 11 );
			if ( class_exists( 'Jetpack' ) ) {
				add_action( 'admin_enqueue_scripts', array( $this, 'remove_jetpack_scripts' ), 1000 );
			}

			if ( defined( 'ELEMENTOR_VERSION' ) ) {
				add_action( 'admin_enqueue_scripts', array( $this, 'remove_elementor_scripts' ), 1000 );
			}

			add_action( 'admin_init', array( $this, 'set_default_template' ), 10 );

			add_action( 'admin_action_yith_ywraq_duplicate_template_pdf', array( $this, 'duplicate_pdf_template' ) );
		}

		/**
		 * Remove jetpack script
		 *
		 * @param   string $hook  Page.
		 *
		 * @return void
		 */
		public function remove_jetpack_scripts( $hook ) {
			if ( ! in_array( $hook, array( 'post-new.php', 'post.php' ), true ) || ! class_exists( 'WP_Block_Editor_Context' ) ) {
				return;
			}

			global $post_type;

			if ( $post_type !== $this->post_type ) {
				return;
			}

			wp_dequeue_script( 'jetpack-blocks-editor' );

		}

		/**
		 * Remove elementor script
		 *
		 * @param   string $hook  Page.
		 *
		 * @return void
		 */
		public function remove_elementor_scripts( $hook ) {
			if ( ! in_array( $hook, array( 'post-new.php', 'post.php' ), true ) || ! class_exists( 'WP_Block_Editor_Context' ) ) {
				return;
			}

			global $post_type;

			if ( $post_type !== $this->post_type ) {
				return;
			}

			wp_dequeue_style( 'elementor-admin-top-bar' );
			wp_dequeue_script( 'elementor-admin-top-bar' );
		}

		/**
		 * Duplicate PDF template
		 */
		public function duplicate_pdf_template() {
			if ( isset( $_REQUEST['action'], $_GET['duplicate_nonce'], $_GET['template_id'] ) && 'yith_ywraq_duplicate_template_pdf' === $_REQUEST['action'] && wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['duplicate_nonce'] ) ), 'yith_ywraq_duplicate_template_pdf' ) && current_user_can( 'edit_' . YITH_YWRAQ_Post_Types::$pdf_template . 's', absint( wp_unslash( $_GET['template_id'] ) ) ) ) {
				$post_id = absint( wp_unslash( $_GET['template_id'] ) );
				$post    = get_post( $post_id );

				if ( ! $post || YITH_YWRAQ_Post_Types::$pdf_template !== $post->post_type ) {
					return;
				}

				YITH_YWRAQ_Post_Types::duplicate_post( $post, YITH_YWRAQ_Post_Types::$pdf_template );
				/**
				 * APPLY_FILTERS: yith_ywraq_duplicate_template_pdf_redirect_url
				 *
				 * Filter the redirect url when a PDF template is duplicated
				 *
				 * @param   string  $pdf_redirect_url Redirect url.
				 *
				 * @return string
				 */
				$redirect_url = apply_filters(
					'yith_ywraq_duplicate_template_pdf_redirect_url',
					add_query_arg(
						array(
							'post_type' => YITH_YWRAQ_Post_Types::$pdf_template,
						),
						admin_url( 'edit.php' )
					)
				);

				wp_safe_redirect( $redirect_url );

			}
		}

		/**
		 * Set default template.
		 *
		 * @return void
		 */
		public function set_default_template() {
			if ( isset( $_REQUEST['request'], $_REQUEST['post_id'], $_REQUEST['security'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_REQUEST['security'] ) ), 'ywraq-set-pdf-template-default' ) ) {
				$post_id  = sanitize_text_field( wp_unslash( $_REQUEST['post_id'] ) );
				$template = ywraq_get_pdf_template( $post_id );
				$template->set_as_default();
			}
		}


		/**
		 * Initialize the Gutenberg editor page.
		 *
		 * @param   string $hook  Page.
		 */
		public function gutenberg_editor_init( $hook ) {

			if ( ! in_array( $hook, array( 'post-new.php', 'post.php' ), true ) || ! class_exists( 'WP_Block_Editor_Context' ) ) {
				return;
			}

			global $post_type, $post_type_object, $post, $wp_meta_boxes;

			if ( $post_type !== $this->post_type ) {
				return;
			}
			$block_editor_context = new WP_Block_Editor_Context( array( 'post' => $post ) );

			// Flag that we're loading the block editor.
			$current_screen = get_current_screen();
			remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );

			add_filter( 'screen_options_show_screen', '__return_false' );

			wp_enqueue_script( 'heartbeat' );
			wp_enqueue_script( 'wp-edit-post' );

			$rest_base = ! empty( $post_type_object->rest_base ) ? $post_type_object->rest_base : $post_type_object->name;

			// Preload common data.
			$preload_paths = array(
				'/',
				'/wp/v2/types?context=edit',
				'/wp/v2/taxonomies?per_page=-1&context=edit',
				'/wp/v2/themes?status=active',
				sprintf( '/wp/v2/%s/%s?context=edit', $rest_base, $post->ID ),
				sprintf( '/wp/v2/types/%s?context=edit', $post_type ),
				sprintf( '/wp/v2/users/me?post_type=%s&context=edit', $post_type ),
				array( '/wp/v2/media', 'OPTIONS' ),
				array( '/wp/v2/blocks', 'OPTIONS' ),
				sprintf( '/wp/v2/%s/%d/autosaves?context=edit', $rest_base, $post->ID ),
			);

			block_editor_rest_api_preload( $preload_paths, $block_editor_context );

			wp_add_inline_script(
				'wp-blocks',
				sprintf( 'wp.blocks.setCategories( %s );', wp_json_encode( get_block_categories( $post ) ) ),
				'after'
			);

			/*
			 * Assign initial edits, if applicable. These are not initially assigned to the persisted post,
			 * but should be included in its save payload.
			 */
			$initial_edits = null;
			$is_new_post   = false;
			if ( 'auto-draft' === $post->post_status ) {
				$is_new_post = true;
				// Override "(Auto Draft)" new post default title with empty string, or filtered value.
				$initial_edits = array(
					'title'   => $post->post_title,
					'content' => $post->post_content,
					'excerpt' => $post->post_excerpt,
				);
			}

			// Preload server-registered block schemas.
			wp_add_inline_script(
				'wp-blocks',
				'wp.blocks.unstable__bootstrapServerSideBlockDefinitions(' . wp_json_encode( get_block_editor_server_block_settings() ) . ');'
			);

			// Get admin url for handling meta boxes.
			$meta_box_url = admin_url( 'post.php' );
			$meta_box_url = add_query_arg(
				array(
					'post'                  => $post->ID,
					'action'                => 'edit',
					'meta-box-loader'       => true,
					'meta-box-loader-nonce' => wp_create_nonce( 'meta-box-loader' ),
				),
				$meta_box_url
			);
			wp_add_inline_script(
				'wp-editor',
				sprintf( 'var _wpMetaBoxUrl = %s;', wp_json_encode( $meta_box_url ) ),
				'before'
			);

			/*
			 * Get all available templates for the post/page attributes meta-box.
			 * The "Default template" array element should only be added if the array is
			 * not empty so we do not trigger the template select element without any options
			 * besides the default value.
			 */
			$available_templates = wp_get_theme()->get_page_templates( get_post( $post->ID ) );
			$available_templates = ! empty( $available_templates ) ? array_replace(
				array(
					/** This filter is documented in wp-admin/includes/meta-boxes.php */
					'' => apply_filters( 'default_page_template_title', __( 'Default template' ), 'rest-api' ),
				),
				$available_templates
			) : $available_templates;

			// Lock settings.
			$user_id = wp_check_post_lock( $post->ID );
			if ( $user_id ) {
				$locked = false;

				/** This filter is documented in wp-admin/includes/post.php */
				if ( apply_filters( 'show_post_locked_dialog', true, $post, $user_id ) ) {
					$locked = true;
				}

				$user_details = null;
				if ( $locked ) {
					$user         = get_userdata( $user_id );
					$user_details = array(
						'name' => $user->display_name,
					);
					$avatar       = get_avatar_url( $user_id, array( 'size' => 64 ) );
				}

				$lock_details = array(
					'isLocked' => $locked,
					'user'     => $user_details,
				);
			} else {
				// Lock the post.
				$active_post_lock = wp_set_post_lock( $post->ID );
				if ( $active_post_lock ) {
					$active_post_lock = esc_attr( implode( ':', $active_post_lock ) );
				}

				$lock_details = array(
					'isLocked'       => false,
					'activePostLock' => $active_post_lock,
				);
			}

			/**
			 * Filters the body placeholder text.
			 *
			 * @param   string   $text  Placeholder text. Default 'Type / to choose a block'.
			 * @param   WP_Post  $post  Post object.
			 *
			 * @since 5.0.0
			 * @since 5.8.0 Changed the default placeholder text.
			 */
			$body_placeholder = apply_filters( 'write_your_story', __( 'Type / to choose a block' ), $post );

			$editor_settings = array(
				'availableTemplates'                   => $available_templates,
				'allowedBlockTypes'                    => YWRAQ_Gutenberg()->get_allowed_block_types(),
				'disablePostFormats'                   => true,
				/** This filter is documented in wp-admin/edit-form-advanced.php */
				'titlePlaceholder'                     => apply_filters( 'enter_title_here', __( 'Add title' ), $post ),
				'bodyPlaceholder'                      => $body_placeholder,
				'autosaveInterval'                     => AUTOSAVE_INTERVAL,
				'styles'                               => false,
				'richEditingEnabled'                   => user_can_richedit(),
				'postLock'                             => $lock_details,
				'postLockUtils'                        => array(
					'nonce'       => wp_create_nonce( 'lock-post_' . $post->ID ),
					'unlockNonce' => wp_create_nonce( 'update-post_' . $post->ID ),
					'ajaxUrl'     => admin_url( 'admin-ajax.php' ),
				),
				'supportsLayout'                       => function_exists( 'wp_theme_has_theme_json' ) ? wp_theme_has_theme_json() : WP_Theme_JSON_Resolver::theme_has_support(),
				'__experimentalBlockPatterns'          => WP_Block_Patterns_Registry::get_instance()->get_all_registered(),
				'__experimentalBlockPatternCategories' => WP_Block_Pattern_Categories_Registry::get_instance()->get_all_registered(),
				'supportsTemplateMode'                 => current_theme_supports( 'block-templates' ),

				// Whether or not to load the 'postcustom' meta box is stored as a user meta
				// field so that we're not always loading its assets.
				'enableCustomFields'                   => (bool) get_user_meta( get_current_user_id(), 'enable_custom_fields', true ),
			);

			$autosave = wp_get_post_autosave( $post->ID );
			if ( $autosave ) {
				if ( mysql2date( 'U', $autosave->post_modified_gmt, false ) > mysql2date( 'U', $post->post_modified_gmt, false ) ) {
					$editor_settings['autosave'] = array(
						'editLink' => get_edit_post_link( $autosave->ID ),
					);
				} else {
					wp_delete_post_revision( $autosave->ID );
				}
			}

			if ( ! empty( $post_type_object->template ) ) {
				$editor_settings['template']     = $post_type_object->template;
				$editor_settings['templateLock'] = ! empty( $post_type_object->template_lock ) ? $post_type_object->template_lock : false;
			}

			// If there's no template set on a new post, use the post format, instead.
			if ( $is_new_post && ! isset( $editor_settings['template'] ) && 'post' === $post->post_type ) {
				$post_format = get_post_format( $post );
				if ( in_array( $post_format, array( 'audio', 'gallery', 'image', 'quote', 'video' ), true ) ) {
					$editor_settings['template'] = array( array( "core/$post_format" ) );
				}
			}

			/**
			 * Scripts
			 */
			wp_enqueue_media(
				array(
					'post' => $post->ID,
				)
			);
			wp_tinymce_inline_scripts();
			wp_enqueue_editor();

			/**
			 * Styles
			 */
			wp_enqueue_style( 'wp-edit-post' );

			/**
			 * Fires after block assets have been enqueued for the editing interface.
			 *
			 * Call `add_action` on any hook before 'admin_enqueue_scripts'.
			 *
			 * In the function call you supply, simply use `wp_enqueue_script` and
			 * `wp_enqueue_style` to add your functionality to the block editor.
			 *
			 * @since 5.0.0
			 */
			do_action( 'enqueue_block_editor_assets' );

			if ( isset( $wp_meta_boxes[ $current_screen->id ]['normal']['core'] ) ) {
				// Check if the Custom Fields meta box has been removed at some point.
				$core_meta_boxes = $wp_meta_boxes[ $current_screen->id ]['normal']['core'];
				if ( ! isset( $core_meta_boxes['postcustom'] ) || ! $core_meta_boxes['postcustom'] ) {
					unset( $editor_settings['enableCustomFields'] );
				}
			}

			$editor_settings = get_block_editor_settings( $editor_settings, $block_editor_context );

			$init_script = <<<JS
( function() {
	window._wpLoadBlockEditor = new Promise( function( resolve ) {
			resolve( wp.editPost.initializeEditor( 'editor', "%s", %d, %s, %s ) )
		} );
	} )();
JS;

			$script = sprintf(
				$init_script,
				$post->post_type,
				$post->ID,
				wp_json_encode( $editor_settings ),
				wp_json_encode( $initial_edits )
			);
			wp_add_inline_script( 'wp-edit-post', $script );

			if ( (int) get_option( 'page_for_posts' ) === $post->ID ) {
				add_action( 'admin_enqueue_scripts', '_wp_block_editor_posts_page_notice' );
			}

		}

		/**
		 * Change the post message
		 *
		 * @param   array $messages  List of messages.
		 *
		 * @return array
		 */
		public function change_post_update_message( $messages ) {
			global $post;

			if ( $post && $this->post_type === $post->post_type ) {
				$messages['post'][1] = _x( 'Template updated.', 'Message that appears when a post is updated', 'yith-woocommerce-request-a-quote' );
				$messages['post'][6] = _x( 'Template published.', 'Message that appears when a post is published', 'yith-woocommerce-request-a-quote' );
			}

			return $messages;
		}

		/**
		 * Return custom sortable columns.
		 *
		 * @param array $sortables_columns Sortable columns.
		 *
		 * @return array
		 */
		public function sortable_custom_columns( $sortables_columns ) {
			return array();
		}

		/**
		 * Change the bulk post message
		 *
		 * @param   array $messages     List of messages.
		 * @param   array $bulk_counts  List of bulk counts.
		 *
		 * @return array
		 */
		public function change_bulk_post_updated_messages( $messages, $bulk_counts ) {
			global $post;

			if ( $post && $this->post_type === $post->post_type ) {
				// translators: number of templates deleted.
				$messages['post']['deleted'] = _n( '%s template permanently deleted.', '%s templates permanently deleted.', $bulk_counts['deleted'], 'yith-woocommerce-request-a-quote' );
			}

			return $messages;
		}

		/**
		 * Set custom columns
		 *
		 * @param   array $columns  Existing columns.
		 *
		 * @return  array
		 * @since   4.0.0
		 */
		public function set_custom_columns( $columns ) {
			$columns = array(
				'title'  => '',
				'action' => '',
			);

			return $columns;
		}

		/**
		 * Return the all action row
		 *
		 * @param   int                     $post_id   Post id.
		 * @param   YITH_YWRAQ_PDF_Template $template  Current template.
		 *
		 * @return array
		 */
		public function get_all_actions( $post_id, $template ) {
			$duplicate_link  = add_query_arg(
				array(
					'action'          => 'yith_ywraq_duplicate_template_pdf',
					'template_id'     => $post_id,
					'duplicate_nonce' => wp_create_nonce( 'yith_ywraq_duplicate_template_pdf' ),
				),
				admin_url()
			);
			$actions         = array();
			$default_actions = yith_plugin_fw_get_default_post_actions( $post_id );

			foreach ( $default_actions as $key => $action ) {
				$actions[ $key ] = $action;
				if ( 'edit' === $key ) {
					$actions['clone'] = array(
						'type'   => 'action-button',
						'action' => 'duplicate',
						'title'  => esc_html__( 'Duplicate', 'yith-woocommerce-request-a-quote' ),
						'icon'   => 'clone',
						'url'    => $duplicate_link,
					);
				}
			}
			unset( $actions['trash'] );
			if ( ! $template->is_default() && current_user_can( 'delete_post', $post_id ) ) {
				$title             = _draft_or_post_title( $post_id );
				$actions['delete'] = array(
					'type'         => 'action-button',
					'title'        => _x( 'Delete', 'Post action', 'yith-woocommerce-request-a-quote' ),
					'action'       => 'delete',
					'icon'         => 'trash',
					'url'          => get_delete_post_link( $post_id, '', true ),
					'confirm_data' => array(
						'title'               => __( 'Confirm delete', 'yith-woocommerce-request-a-quote' ),
						// translators: %s is the title of the post object.
						'message'             => sprintf( _x( 'Are you sure you want to delete "%s"?', 'translators: %s is the title of the post object', 'yith-woocommerce-request-a-quote' ), '<strong>' . $title . '</strong>' ) . '<br /><br />' . __( 'This action cannot be undone and you will not be able to recover this data.', 'yith-woocommerce-request-a-quote' ),
						'cancel-button'       => __( 'No', 'yith-woocommerce-request-a-quote' ),
						'confirm-button'      => _x( 'Yes, delete', 'Delete confirmation action', 'yith-woocommerce-request-a-quote' ),
						'confirm-button-type' => 'delete',
					),
				);
			}

			return $actions;
		}


		/**
		 * Manage custom columns
		 *
		 * @param   string $column   Current column.
		 * @param   int    $post_id  Post ID.
		 *
		 * @since   4.0.0
		 */
		public function render_custom_columns( $column, $post_id ) {
			$template = ywraq_get_pdf_template( $post_id );
			if ( 'action' === $column ) {
				echo yith_plugin_fw_get_action_buttons( $this->get_all_actions( $post_id, $template ), true ); //phpcs:ignore
				if ( $template->is_default() ) {
					return printf( '<div class="default-badge">%s</div>', esc_html_x( 'In use', 'label of the default pdf template', 'yith-woocommerce-request-a-quote' ) );
				} else {
					return printf( '<a class="set-default-badge" data-nonce="%s" data-post="%d" href="#">%s</a>', esc_html( wp_create_nonce( 'ywraq-set-pdf-template-default' ) ), esc_html( $post_id ), esc_html_x( 'Use it', 'label of the button to set the pdf template as default', 'yith-woocommerce-request-a-quote' ) );
				}
			}
		}

		/**
		 * The function to be called to output the meta box in PDF Template editor page
		 *
		 * @param   WP_Post $post  The Post object.
		 *
		 * @return  void
		 */
		public function option_metabox( $post ) {

			wp_nonce_field( 'ywraq_pdf_template', 'ywraq_pdf_template_nonce' );

			$values = array();
			/* getting previous saved settings */
			if ( 'auto-draft' !== $post->post_status ) {
				$obj      = ywraq_get_pdf_template( $post->ID );
				$obj_data = $obj->get_data();
			}

			?>
			<div class="ywraq-metabox-wrapper">
				<div class="yith-plugin-ui yith-plugin-fw">
					<table class="form-table">
						<?php
						foreach ( $this->options as $id => $field ) :
							$field['id']    = $id;
							$std            = isset( $field['std'] ) ? $field['std'] : '';
							$field['value'] = isset( $obj_data[ $field['name'] ] ) ? $obj_data[ $field['name'] ] : $std;
							$colspan        = 'html' !== $field['type'] ? '' : 'colspan=2';
							?>
							<tr class="yith-plugin-fw-panel-wc-row <?php echo esc_attr( $field['type'] ); ?> <?php echo esc_attr( $id ); ?>">
								<?php if ( 'html' !== $field['type'] ) : ?>
									<th scope="row" class="titledesc">
										<label
												for="<?php echo esc_attr( $field['name'] ); ?>"><?php echo esc_attr( $field['label'] ); ?></label>
									</th>
								<?php endif; ?>
								<td class="forminp forminp-<?php echo esc_attr( $field['type'] ); ?>" <?php echo esc_attr( $colspan ); ?>>
									<?php yith_plugin_fw_get_field( $field, true ); ?>
									<?php if ( isset( $field['desc'] ) ) : ?>
										<span class="description"><?php echo wp_kses_post( $field['desc'] ); ?></span>
									<?php endif; ?>

								</td>
							</tr>
						<?php endforeach; ?>
					</table>
					<div id="editor" class="block-editor__container hide-if-no-js"></div>

				</div>
			</div>
			<?php
		}


		/**
		 * Return the back to list button label.
		 *
		 * @return string
		 */
		protected function get_back_button_list_label() {
			return esc_html__( 'Back to templates list', 'yith-woocommerce-request-a-quote' );
		}


		/**
		 * Save meta box process
		 *
		 * @param   integer $post_id  The Post ID.
		 * @param   WP_Post $post     The Post object.
		 *
		 * @return  void
		 * @since   4.0.0
		 */
		public function save_post( $post_id, $post ) {
			// $post_id and $post are required.
			if ( empty( $post_id ) || empty( $post ) || $this->saved_meta_box ) {
				return;
			}

			// Check the nonce.
			if ( empty( $_POST['ywraq_pdf_template_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['ywraq_pdf_template_nonce'] ) ), 'ywraq_pdf_template' ) ) {
				return;
			}

			$posted = $_POST;

			// Don't save meta boxes for revisions or autosaves.
			if ( defined( 'DOING_AUTOSAVE' ) || is_int( wp_is_post_revision( $post ) ) || is_int( wp_is_post_autosave( $post ) ) ) {
				return;
			}

			// Check the post being saved == the $post_id to prevent triggering this call for other save_post events.
			if ( empty( $posted['post_ID'] ) || (int) $posted['post_ID'] !== $post_id ) {
				return;
			}

			// Check user has permission to edit.
			if ( ! current_user_can( 'edit_post', $post_id ) ) {
				return;
			}

			$this->saved_meta_box = true;
			$obj                  = ywraq_get_pdf_template( $post_id );

			foreach ( $obj->get_data() as $key => $value ) {

				if ( 'name' === $key && isset( $posted[ $key ] ) ) {
					wp_update_post(
						array(
							'ID'         => $post_id,
							'post_title' => $posted[ $key ],
						)
					);
				}

				if ( isset( $posted[ $key ] ) ) {
					$changes[ $key ] = $posted[ $key ];
				} elseif ( 'id' !== $key ) {
					$changes[ $key ] = 'no';
				}
			}

			$obj->set_props( $changes );
			$obj->save();

		}


	}

}
