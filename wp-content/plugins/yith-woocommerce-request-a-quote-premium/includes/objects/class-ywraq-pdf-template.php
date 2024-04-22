<?php // phpcs:ignore WordPress.Files.FileName.InvalidClassFileName
/**
 * Class to the pdf templates
 *
 * @class   YITH_YWRAQ_PDF_Template
 * @since   4.0.0
 * @author  YITH <plugins@yithemes.com>
 * @package YITH\RequestAQuote
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'YITH_YWRAQ_Cpt_Object', false ) ) {
	include_once YITH_YWRAQ_INC . 'objects/abstract-class-cpt-object.php';
}

if ( ! class_exists( 'YITH_YWRAQ_PDF_Template' ) ) {

	/**
	 * Class YITH_YWRAQ_PDF_Template
	 */
	class YITH_YWRAQ_PDF_Template extends YITH_YWRAQ_Cpt_Object {

		/**
		 * Array of data
		 *
		 * @var array
		 */
		protected $data = array(
			'name' => '',
			'default' => 0,
			'template_parent' => 'default',
			'footer_content' => '',
		);

		/**
		 * Post type name
		 *
		 * @var string
		 */
		protected $post_type = '';

		/**
		 * Main constructor function
		 *
		 * @param   mixed  $obj  Object.
		 */
		public function __construct( $obj ) {
			$this->post_type = YITH_YWRAQ_Post_Types::$pdf_template;
			parent::__construct( $obj );
		}

		/**
		 * Set name of the template
		 *
		 * @param   string  $value  The value to set.
		 */
		public function set_name( $value ) {
			$this->set_prop( 'name', $value );
		}

		/**
		 * Set if the template is the default template
		 *
		 * @param   int  $value  The value to set.
		 */
		public function set_default( $value ) {
			$this->set_prop( 'default', $value );
		}

		/**
		 * Set the template parent id
		 *
		 * @param   string  $value  The value to set.
		 */
		public function set_template_parent( $value ) {
			$this->set_prop( 'template_parent', $value );
		}

		/**
		 * Return if the template is the default template.
		 *
		 * @return bool
		 */
		public function is_default() {
			return (bool) $this->get_default();
		}

		/**
		 * Return the name of template
		 *
		 * @param   string  $context  What the value is for. Valid values are view and edit.
		 *
		 * @return string
		 */
		public function get_name( $context = 'view' ) {
			return $this->get_prop( 'name', $context );
		}


		/**
		 * Return yes the if the template is the default template
		 *
		 * @param   string  $context  What the value is for. Valid values are view and edit.
		 *
		 * @return string
		 */
		public function get_default( $context = 'view' ) {
			return $this->get_prop( 'default', $context );
		}


		/**
		 * Return the template parent id
		 *
		 * @param   string  $context  What the value is for. Valid values are view and edit.
		 *
		 * @return string
		 */
		public function get_template_parent( $context = 'view' ) {
			return $this->get_prop( 'template_parent', $context );
		}


		/**
		 * Return the content of the template
		 *
		 * @param   int    $order_id          Order id.
		 * @param   array  $preview_products  Preview products.
		 *                                    .
		 * @return string
		 */
		public function get_content( $order_id, $preview_products = array() ) {
			$content  = get_the_content( null, false, $this->get_id() );
			$template = $this->get_template_parent();
			$output   = ywraq_pdf_template_builder()->render_template( $content, $order_id, $template,
				$preview_products );

			return $output;
		}

		/**
		 * Return the footer content
		 *
		 * @param   string  $context  What the value is for. Valid values are view and edit.
		 *
		 * @return mixed|null
		 */
		public function get_footer_content( $context = 'view' ) {
			return $this->get_prop( 'footer_content', $context );
		}

		/**
		 * Generate pdf
		 *
		 * @param $order_id
		 *
		 * @return false|resource
		 * @throws \Mpdf\MpdfException
		 */
		public function generate_pdf( $order_id ) {

			$mpdf = YITH_Request_Quote_Premium()->get_mpdf();

			$mpdf->shrink_tables_to_fit = 1;

			ob_start();
			$content = $this->get_content( $order_id );
			$footer  = $this->get_footer_content();
			wc_get_template(
				'pdf/builder/quote.php',
				array(
					'content' => $content,
					'footer' => $footer,
				),
				'',
				YITH_YWRAQ_TEMPLATE_PATH . '/'
			);
			$html = ob_get_contents();
			ob_end_clean();

			$mpdf->WriteHTML( $html );

			$pdf       = $mpdf->Output( 'document', 'S' );
			$file_path = YITH_Request_Quote_Premium()->get_pdf_file_path( $order_id, true );

			if ( ! file_exists( $file_path ) ) {
				$file_path = YITH_Request_Quote_Premium()->get_pdf_file_path( $order_id, false );
			} else {
				unlink( $file_path );
			}

			$file = fopen( $file_path, "a" ); //phpcs:ignore
			fwrite( $file, $pdf ); //phpcs:ignore
			fclose( $file ); //phpcs:ignore

			return $file;
		}

		/**
		 * Return the pdf preview
		 *
		 * @param   array  $preview_products  Preview products.
		 */
		public function get_preview( $preview_products = array() ) {

			$mpdf = YITH_Request_Quote_Premium()->get_mpdf();

			ob_start();
			$content = $this->get_content( 0, $preview_products );
			$footer  = $this->get_footer_content();
			wc_get_template(
				'pdf/builder/quote.php',
				array(
					'content' => $content,
					'footer' => $footer,
				),
				'',
				YITH_YWRAQ_TEMPLATE_PATH . '/'
			);

			$html = ob_get_contents();
			ob_end_clean();

			$mpdf->WriteHTML( $html );

			$pdf       = $mpdf->Output( 'document', 'S' );
			$file_path = YITH_Request_Quote_Premium()->get_pdf_file_path( 0, true );

			if ( ! file_exists( $file_path ) ) {
				$file_path = YITH_Request_Quote_Premium()->get_pdf_file_path( 0, false );
			} else {
				unlink( $file_path );
			}

			$file = fopen( $file_path, "a" ); //phpcs:ignore
			fwrite( $file, $pdf ); //phpcs:ignore
			fclose( $file ); //phpcs:ignore

			$pdf_url = YITH_Request_Quote_Premium()->get_pdf_file_url();
			wp_send_json(
				array( 'pdf' => $pdf_url )
			);

		}

		/**
		 * Set as default
		 *
		 * @param   bool  $skip_synchronization  Skip the update of the option.
		 */
		public function set_as_default( $skip_synchronization = false ) {
			$posts = get_posts(
				array(
					'post_type' => YITH_YWRAQ_Post_Types::$pdf_template,
					'meta_key' => '_default',
					'meta_value' => 1,
				)
			);

			if ( $posts ) {
				foreach ( $posts as $post ) {
					update_post_meta( $post->ID, '_default', 0 );
				}
			}

			$this->set_default( 1 );
			$this->save();
			if ( ! $skip_synchronization ) {
				update_option( 'ywraq_pdf_custom_templates', $this->get_id() );
			}

		}
	}
}

if ( ! function_exists( 'ywraq_get_pdf_template' ) ) {
	/**
	 * Return the pdf template object
	 *
	 * @param   mixed  $pdf_template  PDF Template.
	 *
	 * @return YITH_YWRAQ_PDF_Template
	 */
	function ywraq_get_pdf_template( $pdf_template, $lang = '' ) {

        global $sitepress;
        $pdf_template = $sitepress && $lang ? yit_wpml_object_id( $pdf_template, 'product', true, $lang ) : $pdf_template;

		return new YITH_YWRAQ_PDF_Template( $pdf_template );
	}
}
