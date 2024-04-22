<?php //phpcs:ignore WordPress.Files.FileName.InvalidClassFileName
/**
 * This file belongs to the YIT Plugin Framework.
 *
 * This source file is subject to the GNU GENERAL PUBLIC LICENSE (GPL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.gnu.org/licenses/gpl-3.0.txt
 *
 * @package YITH\RequestAQuote
 */

if ( ! defined( 'ABSPATH' ) || ! defined( 'YITH_YWRAQ_VERSION' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Implements the YWRAQ_Ninja_Forms class.
 *
 * @class   YWRAQ_Ninja_Forms
 * @package YITH\RequestAQuote
 * @since   3.1.0
 * @author  YITH <plugins@yithemes.com>
 */
if ( ! class_exists( 'YWRAQ_Ninja_Forms_Tag' ) ) {
	/**
	 * Class YWRAQ_Ninja_Forms_Tag
	 */
	class YWRAQ_Ninja_Forms_Tag extends NF_Abstracts_MergeTags {

		/**
		 * Id of the request a quote list tag
		 *
		 * @var string
		 */
		protected $id = 'ywraq_merge_tags';

		/**
		 * YWRAQ_Ninja_Forms_Tag constructor.
		 */
		public function __construct() {
			parent::__construct();
			$this->title      = 'YITH Request a Quote';
			$this->merge_tags = array(
				'ywraq_table' => array(
					'id'       => 'ywraq_table_content',
					'tag'      => '{ywraq:list}',
					'label'    => esc_html_x( 'List Table', 'Ninja form tag to add the request quote list inside the email', 'yith-woocommerce-request-a-quote' ),
					'callback' => 'ywraq_table_content',
				),
			);
		}

		/**
		 * Return the content that should be replaced to the tag
		 *
		 * @return false|string
		 */
		protected function ywraq_table_content() {
			return yith_ywraq_get_email_template( true );
		}
	}
}
