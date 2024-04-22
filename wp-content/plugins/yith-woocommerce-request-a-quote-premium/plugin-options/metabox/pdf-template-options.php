<?php
/**
 * PDF Template Options
 *
 * @package YITH\RequestAQuote
 * @since   2.2.0
 * @author  YITH <plugins@yithemes.com>
 */

$options = array(
	'ywraq_pdf_template_templates' => array(
		'id'      => 'ywraq_pdf_template_templates',
		'name'    => 'ywraq_pdf_template_templates',
		'type'    => 'html',
		'html'   =>'<div id="ywraq_pdf_templates"></div>'
	),
	'ywraq_pdf_template_title' => array(
		'label' => esc_html__( 'Template title', 'yith-woocommerce-request-a-quote' ),
		'type'  => 'text',
		'name'    => 'name',
		'desc'  => esc_html__( 'Enter a title for this template.', 'yith-woocommerce-request-a-quote' ),
		'std'   => '',
	),
);

return apply_filters( 'ywraq_pdf_template_options', $options );
