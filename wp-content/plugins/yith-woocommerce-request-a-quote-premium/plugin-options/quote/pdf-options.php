<?php
/**
 * This file belongs to the YIT Framework.
 *
 * This source file is subject to the GNU GENERAL PUBLIC LICENSE (GPL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.gnu.org/licenses/gpl-3.0.txt
 *
 * @package YITH\RequestAQuote
 * @since   3.0.0
 * @author  YITH <plugins@yithemes.com>
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) || ! defined( 'YITH_YWRAQ_VERSION' ) ) {
	exit;
}

$builder_notice = ywraq_is_gutenberg_active() ? '' : sprintf( '. <span class="ywraq-notice">%s</span>', _x( 'In order to use the PDF builder you need to install Gutenberg and update WordPress to the last version.', 'Admin notice', 'yith-woocommerce-request-a-quote' ) );

$options = array(
	'quote_pdf' => array(
		'name' => __( 'Quote PDF', 'yith-woocommerce-request-a-quote' ),
		'type' => 'title',
		'id'   => 'ywraq_quote_pdf',
	),
	'pdf_in_myaccount' => array(
		'name'      => __( 'Allow quotes to be downloaded as PDF', 'yith-woocommerce-request-a-quote' ),
		'desc'      => __( 'If enabled, users can download the quote in a PDF version from "My Account".', 'yith-woocommerce-request-a-quote' ),
		'id'        => 'ywraq_pdf_in_myaccount',
		'type'      => 'yith-field',
		'yith-type' => 'onoff',
		'default'   => 'no',
	),
	'pdf_file_name' => array(
		'name'      => __( 'PDF file name', 'yith-woocommerce-request-a-quote' ),
		'desc'      => sprintf( '%s <br> %s ',
			__( 'Enter a name to identify the PDF quote. Customers will see this name when they download or open the file. It is possible to use %quote_number% to use the number of the quote and %rand% to add a random number.', 'yith-woocommerce-request-a-quote' ),
			sprintf( __( 'All pdf documents are stored in %s.', 'placeholder is the folder of the quotes', 'yith-woocommerce-request-a-quote' ), '<code>' . YITH_YWRAQ_DOCUMENT_SAVE_DIR . '</code>' ) ),
		'id'        => 'ywraq_pdf_file_name',
		'type'      => 'yith-field',
		'required'  => true,
		'yith-type' => 'text',
		'default'   => 'quote_%rand%',
		'deps'      => array(
			'id'    => 'ywraq_pdf_in_myaccount',
			'value' => 'yes',
		),
	),
	'hide_table_is_pdf_attachment' => array(
		'name'      => __( 'Hide product list in the email content when a PDF quote is attached', 'yith-woocommerce-request-a-quote' ),
		'desc'      => __( 'Hide product list in the content if the PDF version is attached to the email.', 'yith-woocommerce-request-a-quote' ),
		'id'        => 'ywraq_hide_table_is_pdf_attachment',
		'type'      => 'yith-field',
		'yith-type' => 'onoff',
		'default'   => 'no',
		'deps'      => array(
			'id'    => 'ywraq_pdf_in_myaccount',
			'value' => 'yes',
		),
	),
	'pdf_attachment' => array(
		'name'      => __( 'Attach a PDF version to the quote email', 'yith-woocommerce-request-a-quote' ),
		'desc'      => __( 'If enabled, users can download a PDF version of the quotes.', 'yith-woocommerce-request-a-quote' ),
		'id'        => 'ywraq_pdf_attachment',
		'type'      => 'yith-field',
		'yith-type' => 'onoff',
		'default'   => 'no',
		'deps'      => array(
			'id'    => 'ywraq_pdf_in_myaccount',
			'value' => 'yes',
		),
	),
	'quote_pdf_end' => array(
		'type' => 'sectionend',
		'id'   => 'ywraq_quote_pdf_end',
	),
	'pdf_layout' => array(
		'name' => __( 'PDF quote templates', 'yith-woocommerce-request-a-quote' ),
		'type' => 'title',
		'id'   => 'ywraq_pdf_layout',
	),
	'pdf_template_to_use' => array(
		'name'      => __( 'PDF template to use', 'yith-woocommerce-request-a-quote' ),
		'desc'      => sprintf(
			'%s <br> <strong>%s</strong> %s ',
			__(
				'Choose if you want to use the default template included in the plugin, or if you want to enable the templates builder to create a custom template for your quotes.', 'yith-woocommerce-request-a-quote'
			),
			_x( 'Note:', 'part of a sentence in the option "PDF template to use" ', 'yith-woocommerce-request-a-quote' ),
			_x( 'to enable the builder you need to enable Gutenberg.', 'part of a sentence in the option "PDF template to use" ', 'yith-woocommerce-request-a-quote' )
		),
		'id'        => 'ywraq_pdf_template_to_use',
		'type'      => 'yith-field',
		'yith-type' => 'radio',
		'options'   => array(
			'default' => __( 'Use the default template', 'yith-woocommerce-request-a-quote' ),
			'builder' => __( 'Create and choose a custom template', 'yith-woocommerce-request-a-quote' ) . wp_kses_post( $builder_notice ),
		),
		'default'   => ywraq_is_gutenberg_active() ? 'builder' : 'default',
		'deps'      => array(
			'id'    => 'ywraq_pdf_in_myaccount',
			'value' => 'yes',
		),
	),
	'pdf_custom_templates' => array(
		'name'              => _x( 'Choose template', 'Admin option label', 'yith-woocommerce-request-a-quote' ),
		'desc'              => __( 'Choose which template to use by default for your PDF Quotes. You can create unlimited templates in the Quote Template tab.', 'yith-woocommerce-request-a-quote' ),
		'id'                => 'ywraq_pdf_custom_templates',
		'type'              => 'yith-field',
		'yith-type'         => 'select',
		'options'           => YITH_YWRAQ_Post_Types::get_pdf_template_list(),
		'custom_attributes' => array(
			'data-deps'       => 'ywraq_pdf_in_myaccount,ywraq_pdf_template_to_use',
			'data-deps_value' => 'yes,builder',
		),
	),
	'pdf_template' => array(
		'name'              => __( 'PDF layout based on a', 'yith-woocommerce-request-a-quote' ),
		'desc'              => sprintf( '%s <br> %s',
			__( 'Table allows adding content to the HTML table.', 'yith-woocommerce-request-a-quote' ),
			__( 'DIV replaces the HTML table with DIVs (use this to avoid some issues with pagination).', 'yith-woocommerce-request-a-quote' ) ),
		'id'                => 'ywraq_pdf_template',
		'type'              => 'yith-field',
		'yith-type'         => 'radio',
		'options'           => array(
			'table' => __( 'Table', 'yith-woocommerce-request-a-quote' ),
			'div'   => __( 'DIV', 'yith-woocommerce-request-a-quote' ),
		),
		'default'           => 'table',
		'custom_attributes' => array(
			'data-deps'       => 'ywraq_pdf_in_myaccount,ywraq_pdf_template_to_use',
			'data-deps_value' => 'yes,default',
		),
	),
	'pdf_logo' => array(
		'name'              => __( 'Logo', 'yith-woocommerce-request-a-quote' ),
		'desc'              => __( 'Upload a logo to identify your shop in the PDF file.', 'yith-woocommerce-request-a-quote' ),
		'id'                => 'ywraq_pdf_logo',
		'type'              => 'yith-field',
		'yith-type'         => 'media',
		'custom_attributes' => array(
			'data-deps'       => 'ywraq_pdf_in_myaccount,ywraq_pdf_template_to_use',
			'data-deps_value' => 'yes,default',
		),
	),
	'pdf_info' => array(
		'name'              => __( 'Sender info text in PDF quote', 'yith-woocommerce-request-a-quote' ),
		'desc'              => __( 'Enter the sender information that will be shown in the PDF quote.', 'yith-woocommerce-request-a-quote' ),
		'id'                => 'ywraq_pdf_info',
		'type'              => 'yith-field',
		'yith-type'         => 'textarea',
		'default'           => get_bloginfo( 'name' ),
		'custom_attributes' => array(
			'data-deps'       => 'ywraq_pdf_in_myaccount,ywraq_pdf_template_to_use',
			'data-deps_value' => 'yes,default',
		),
	),
	'show_author_quote' => array(
		'name'              => __( 'Show quote author', 'yith-woocommerce-request-a-quote' ),
		'desc'              => __( 'Enable to show information about the user that sent the quote.', 'yith-woocommerce-request-a-quote' ),
		'id'                => 'ywraq_show_author_quote',
		'type'              => 'yith-field',
		'yith-type'         => 'onoff',
		'default'           => 'no',
		'custom_attributes' => array(
			'data-deps'       => 'ywraq_pdf_in_myaccount,ywraq_pdf_template_to_use',
			'data-deps_value' => 'yes,default',
		),
	),
	'pdf_columns' => array(
		'name'              => __( 'In product table show this info:', 'yith-woocommerce-request-a-quote' ),
		'desc'              => __( 'Choose the information to show in the product list.', 'yith-woocommerce-request-a-quote' ),
		'id'                => 'ywraq_pdf_columns',
		'type'              => 'yith-field',
		'yith-type'         => 'select',
		'class'             => 'wc-enhanced-select',
		'multiple'          => true,
		'required'          => true,
		'options'           => array_merge(
			array(
				'all' => _x( 'All', 'show all fields', 'yith-woocommerce-request-a-quote' ),
			),
			apply_filters(
				'ywpar_pdf_columns',
				array(
					'thumbnail'        => 'Product Thumbnail',
					'product_name'     => 'Product Name',
					'unit_price'       => 'Unit Price',
					'quantity'         => 'Quantity',
					'product_subtotal' => 'Product Subtotal',
				)
			)
		),
		'default'           => array( 'all' ),
		'custom_attributes' => array(
			'data-deps'       => 'ywraq_pdf_in_myaccount,ywraq_pdf_template_to_use',
			'data-deps_value' => 'yes,default',
		),
	),
	'pdf_hide_total_row' => array(
		'name'              => __( 'Hide "Total Price" row', 'yith-woocommerce-request-a-quote' ),
		'desc'              => __( 'Enable to hide the "Total Price" row in the product list.', 'yith-woocommerce-request-a-quote' ),
		'id'                => 'ywraq_pdf_hide_total_row',
		'type'              => 'yith-field',
		'yith-type'         => 'onoff',
		'default'           => 'no',
		'custom_attributes' => array(
			'data-deps'       => 'ywraq_pdf_in_myaccount,ywraq_pdf_template_to_use',
			'data-deps_value' => 'yes,default',
		),
	),
	'pdf_link' => array(
		'name'              => __( 'Show "Accept | Reject" links', 'yith-woocommerce-request-a-quote' ),
		'desc'              => sprintf( '%s <br> %s',
			__( 'Enable to add the link to accept or reject the quote into the PDF.', 'yith-woocommerce-request-a-quote' ),
			__( 'To show both links be sure to enable also the option in "Quote option" tab.', 'yith-woocommerce-request-a-quote' ) ),
		'id'                => 'ywraq_pdf_link',
		'type'              => 'yith-field',
		'yith-type'         => 'onoff',
		'default'           => 'no',
		'custom_attributes' => array(
			'data-deps'       => 'ywraq_pdf_in_myaccount,ywraq_pdf_template_to_use',
			'data-deps_value' => 'yes,default',
		),
	),
	'pdf_footer_content' => array(
		'name'              => __( 'Optional text in PDF quote footer', 'yith-woocommerce-request-a-quote' ),
		'desc'              => __( 'Enter an additional text content to show in the footer area of the PDF Quote.', 'yith-woocommerce-request-a-quote' ),
		'id'                => 'ywraq_pdf_footer_content',
		'type'              => 'yith-field',
		'yith-type'         => 'textarea',
		'default'           => '',
		'custom_attributes' => array(
			'data-deps'       => 'ywraq_pdf_in_myaccount,ywraq_pdf_template_to_use',
			'data-deps_value' => 'yes,default',
		),
	),
	'pdf_pagination'     => array(
		'name'              => __( 'Enable pagination in PDF', 'yith-woocommerce-request-a-quote' ),
		'desc'              => __( 'Enable to add pagination numbers at the end of the PDF quote, if the quote has more pages.', 'yith-woocommerce-request-a-quote' ),
		'type'              => 'yith-field',
		'id'                => 'ywraq_pdf_pagination',
		'yith-type'         => 'onoff',
		'default'           => 'yes',
		'custom_attributes' => array(
			'data-deps'       => 'ywraq_pdf_in_myaccount,ywraq_pdf_template_to_use',
			'data-deps_value' => 'yes,default',
		),
	),
	'pdf_layout_end' => array(
		'type' => 'sectionend',
		'id'   => 'ywraq_pdf_layout_end',
	),
);

return array( 'quote-pdf' => apply_filters( 'ywraq_quote_pdf_settings_options', $options ) );
