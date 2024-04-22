<?php
/**
 * Table view to Request A Quote
 *
 * @package YITH\RequestAQuote
 * @since   1.0.0
 * @version 3.1.0
 * @author  YITH <plugins@yithemes.com>
 *
 * This source file is subject to the GNU GENERAL PUBLIC LICENSE (GPL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.gnu.org/licenses/gpl-3.0.txt
 */

$shortcode_args = apply_filters( 'ywraq_main_table_shortcode_arguments', '[yith_ywraq_request_quote_table show_back_to_shop=false]' );
echo do_shortcode( $shortcode_args );
