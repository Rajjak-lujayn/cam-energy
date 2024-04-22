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

return array(
	'quote' => array(
		'quote-options' => array(
			'type'     => 'multi_tab',
			'sub-tabs' => array(
				'quote-settings'     => array(
					'title'       => _x( 'Quote Settings', 'Admin title of tab', 'yith-woocommerce-request-a-quote' ),
					'description' => __( 'Configure the quote options settings.', 'yith-woocommerce-request-a-quote' ),
				),
				'quote-pdf'          => array(
					'title'       => _x( 'Quote PDF', 'Admin title of tab', 'yith-woocommerce-request-a-quote' ),
					'description' => __( 'Configure the quote PDF settings.', 'yith-woocommerce-request-a-quote' ),
				),
				'quote-pdf-template' => array(
					'title'       => _x( 'Quote PDF Templates', 'Admin title of tab', 'yith-woocommerce-request-a-quote' ),
					'description' => __( 'Create and manage quote templates that your customers can download as PDFs.', 'yith-woocommerce-request-a-quote' ),
				),
				'quote-endpoint'     => array(
					'title'       => _x( 'Quotes in My Account', 'Admin title of tab', 'yith-woocommerce-request-a-quote' ),
					'description' => __( 'Configure the quote endpoint settings.', 'yith-woocommerce-request-a-quote' ),
				),
				'quote-payment'      => array(
					'title'       => _x( 'Quote Payment', 'Admin title of tab', 'yith-woocommerce-request-a-quote' ),
					'description' => __( 'Configure the quote payment settings.', 'yith-woocommerce-request-a-quote' ),
				),
			),
		),
	),
);
