<?php
/**
 * Class to build the pdf template from Gutenberg
 *
 * @class   YWRAQ_PDF_Template_Builder
 * @since   4.0.0
 * @package YITH\RequestAQuote
 */

defined( 'ABSPATH' ) || exit;


if ( ! class_exists( 'YWRAQ_PDF_Template_Builder' ) ) {

	/**
	 * Class YWRAQ_PDF_Template_Builder
	 */
	class YWRAQ_PDF_Template_Builder {


		/**
		 * Single instance of the class
		 *
		 * @var YWRAQ_PDF_Template_Builder
		 */
		protected static $instance;

		/**
		 * Preview Products
		 *
		 * @var array
		 */
		public $preview_products = array();

		/**
		 * List of internal method to call Gutenberg Blocks related the quote
		 *
		 * @var array
		 */
		protected $render_functions = array(
			'core/column'                => 'render_columns_block',
			'core/columns'               => 'render_columns_block',
			'yith/ywraq-products-table'  => 'render_product_table',
			'yith/ywraq-products-totals' => 'render_product_totals',
			'yith/ywraq-quote-number'    => 'render_quote_number',
			'yith/ywraq-customer-info'   => 'render_customer_info',
			'yith/ywraq-quote-date'      => 'render_quote_date',
			'yith/ywraq-quote-buttons'   => 'render_quote_buttons',
		);

		/**
		 * List of internal method to render graphic Gutenberg Blocks
		 *
		 * @var array
		 */
		protected $render_graphic_blocks = array(
			'core/image'     => 'render_image_block',
			'core/separator' => 'render_separator_block',
		);

		/**
		 * Returns single instance of the class
		 *
		 * @return YWRAQ_PDF_Template_Builder
		 * @since  1.0.0
		 */
		public static function get_instance() {
			return ! is_null( self::$instance ) ? self::$instance : self::$instance = new self();
		}//end get_instance()


		/**
		 * Constructor
		 *
		 * Initialize class and registers actions and filters to be used
		 *
		 * @since 1.0.0
		 */
		public function __construct() {

		}//end __construct()

		/**
		 * Render template
		 *
		 * @param   string $content           Content of template.
		 * @param   int    $quote_id          Quote id.
		 * @param   string $template          Graphic template.
		 * @param   array  $preview_products  Preview products for the preview.
		 *
		 * @return string
		 */
		public function render_template( $content, $quote_id, $template = 'default', $preview_products = array() ) {

			$this->preview_products = $preview_products;

			$blocks  = parse_blocks( $content );
			$output  = $this->get_main_style( $template );
			$output .= $this->render_blocks( $blocks, $quote_id );

			return $output;
		}//end render_template()


		/**
		 * Return the css rules included inside the pdf template assets
		 *
		 * @param   string $template  Graphic template.
		 *
		 * @return string
		 */
		public function get_main_style( $template ) {
			$style                  = '<style>';
			$large_margin_templates = array(
				'leaf',
				'stripes',
				'sexy',
				'elegant-blue',
			);

			$lateral_margin = apply_filters(
				'ywraq_template_pdf_lateral_margin',
				in_array( $template, $large_margin_templates, true ) ? 5 : 3,
				$template
			);

			$footer_margin_bottom = apply_filters(
				'ywraq_template_pdf_footer_margin_bottom',
				in_array( $template, $large_margin_templates, true ) ? 13 : 6,
				$template
			);

			if ( 'default' !== $template ) {
				$background = apply_filters(
					'ywraq_template_pdf_background_image',
					YITH_YWRAQ_ASSETS_URL . '/preview/images/bg/' . $template . '.svg',
					$template
				);
				$style     .= '
			@page{ 
			 background: url(' . $background . ') no-repeat 0 0;
			  background-image-resize: 6;
			  margin:10mm ' . $lateral_margin . 'mm;
			  margin-bottom: 14mm;
			  margin-footer: ' . $footer_margin_bottom . 'mm;
			  }';
			} else {
				$style .= '
			@page{ 
			  margin:10mm ' . $lateral_margin . 'mm;
			  margin-bottom: 14mm;
			  margin-footer: ' . $footer_margin_bottom . 'mm;
			  }';
			}

			ob_start();
			include_once YITH_YWRAQ_DIR . 'assets/css/ywraq-template-pdf.css';
			$style .= apply_filters( 'ywraq_pdf_template_style', ob_get_contents(), $template, $this );
			ob_end_clean();
			$style .= '</style>';

			return $style;

		}//end get_main_style()

		/**
		 * Render the blocks inside the template
		 *
		 * @param   array $blocks    List of blocks.
		 * @param   int   $quote_id  Id of the quote.
		 *
		 * @return string
		 */
		public function render_blocks( $blocks, $quote_id ) {
			$output = '';
			$quote  = wc_get_order( $quote_id );
			foreach ( $blocks as $block ) {
				if ( isset( $this->render_functions[ $block['blockName'] ] ) && method_exists(
					$this,
					$this->render_functions[ $block['blockName'] ]
				) ) {
					$callback = $this->render_functions[ $block['blockName'] ];
					$output  .= $this->$callback( $block, $quote );
				} elseif ( isset( $this->render_graphic_blocks[ $block['blockName'] ] ) && method_exists(
					$this,
					$this->render_graphic_blocks[ $block['blockName'] ]
				) ) {
					$callback = $this->render_graphic_blocks[ $block['blockName'] ];
					$output  .= $this->$callback( $block );
				} else {
					$output .= render_block( $block );
				}
			}

			return $output;

		}//end render_blocks()

		/**
		 * Render the columns and column blocks
		 *
		 * @param   array         $block  Block.
		 * @param   bool|WC_Order $quote  Quote.
		 *
		 * @return string
		 */
		public function render_columns_block( array $block, $quote = false ) {
			$inner_blocks = $block['innerBlocks'];
			$is_single    = 'core/column' === $block['blockName'];
			$first_tag    = $block['innerContent'][0];
			if ( $inner_blocks && ! $is_single ) {
				$inner_blocks_class = sprintf( 'columns-%d', count( $inner_blocks ) );
				$output             = str_replace(
					'wp-block-columns',
					'wp-block-columns ' . $inner_blocks_class,
					$first_tag
				);
				$output            .= '<sethtmlpagefooter name="footer" value="on" page="ALL" />';
			} else {
				if ( strpos( $first_tag, 'style="' ) !== false ) {
					$width  = isset( $block['attrs']['width'] ) ? 'style="width:' . $block['attrs']['width'] . ';' : '';
					$output = ( '' !== $width ) ? str_replace( 'style="', $width, $first_tag ) : $first_tag;
				} else {
					$width  = isset( $block['attrs']['width'] ) ? 'style="width:' . $block['attrs']['width'] . '"' : '';
					$output = ( '' !== $width ) ? str_replace( '>', $width . '>', $first_tag ) : $first_tag;
				}
			}

			$output .= $this->render_blocks( $inner_blocks, $quote );
			$output .= end( $block['innerContent'] );

			return $output;

		}//end render_columns_block()


		/**
		 * Render the block with the customer info
		 *
		 * @param   array         $block  Block.
		 * @param   bool|WC_Order $quote  Quote.
		 *
		 * @return string
		 */
		public function render_customer_info( $block, $quote = false ) {
			$rendered_block = render_block( $block );
			$customer       = $this->get_customer( $quote );

			foreach ( $this->get_customer_info_placeholders() as $placeholder ) {
				$value          = isset( $customer[ $placeholder ] ) ? $customer[ $placeholder ] : '';
				$rendered_block = str_replace( '{{' . $placeholder . '}}', $value, $rendered_block );
				$rendered_block = str_replace( '<br><br>', '<br>', $rendered_block );
			}

			return $rendered_block;
		}

		/**
		 * Render the image block
		 *
		 * @param   array $block  Block.
		 *
		 * @return string
		 */
		public function render_image_block( $block ) {
			$rendered_block = render_block( $block );
			if ( apply_filters( 'ywraq_get_images_via_path', false ) ) {
				$path           = get_attached_file( $block['attrs']['id'] );
				$rendered_block = preg_replace( '/src=[\'\"](.*?)[\'\"]/', 'src="' . $path . '"', $rendered_block );
			}

			return $rendered_block;

		}//end render_image_block()


		/**
		 * Return the product table rendered
		 *
		 * @param   array          $block  Block.
		 * @param   false|WC_Order $quote  Quote.
		 *
		 * @return string
		 */
		public function render_product_table( $block, $quote = false ) {
			$rendered_block = render_block( $block );

			$attr        = $block['attrs'];
			$output      = '';
			$total_items = 0;
			$total_tax   = 0;
			if ( ! $quote ) {
				$products = $this->get_preview_products();
				$size     = count( $products );
				$i        = 0;
				foreach ( $products as $product ) {
					$total_items += $product['line_subtotal'];
					$total_tax   += $product['line_subtotal_tax'];
					$tr_class     = ++ $i === $size ? 'class="last"' : '';
					$output      .= "<tr {$tr_class}>";

					if ( ! isset( $attr['thumbnails'] ) || isset( $attr['thumbnails'] ) && $attr['thumbnails'] ) {

						if ( empty( $product['thumbnail_path'] ) ) {
							$placeholder_image = get_option( 'woocommerce_placeholder_image', 0 );
							$thumbnail         = wp_get_original_image_path( $placeholder_image );
						} else {
							$thumbnail = $product['thumbnail_path'];
						}

						$output .= sprintf(
							'<td class="thumbnail"><img src="%s" class="thumbnail-img"/></td>',
							$thumbnail
						);
					}

					if ( ! isset( $attr['productName'] ) || isset( $attr['productName'] ) && $attr['productName'] ) {
						$product_name = $product['name'];
						if ( ! empty( $product['sku'] ) && ( ! isset( $attr['productSku'] ) || isset( $attr['productSku'] ) && $attr['productSku'] ) ) {
							$product_name = $product['name'] . ' <br/><small>' . apply_filters(
								'ywraq_sku_label',
								__( ' SKU:', 'yith-woocommerce-request-a-quote' )
							) . $product['sku'] . '</small>';
						}
						$output .= sprintf( '<td class="product-name">%s</td>', $product_name );
					}

					if ( ! isset( $attr['quantity'] ) || isset( $attr['quantity'] ) && $attr['quantity'] ) {
						$output .= sprintf( '<td class="quantity number">%s</td>', $product['quantity'] );
					}

					if ( ! isset( $attr['unitPrice'] ) || isset( $attr['unitPrice'] ) && $attr['unitPrice'] ) {
						$output .= sprintf( '<td class="product-price number">%s</td>', wc_price( $product['price'] ) );
					}

					if ( ! isset( $attr['productSubtotal'] ) || isset( $attr['productSubtotal'] ) && $attr['productSubtotal'] ) {
						$subtotal = 'yes' === get_option( 'woocommerce_prices_include_tax' ) ? ( $product['line_subtotal'] + $product['line_subtotal_tax'] ) : $product['line_subtotal'];
						$output  .= sprintf( '<td class="subtotal number">%s</td>', wc_price( $subtotal ) );
					}

					$output .= '</tr>';

				}//end foreach
			} else {
				ob_start();
				wc_get_template(
					'pdf/builder/quote-table.php',
					array(
						'quote' => $quote,
						'attr'  => $attr,
					),
					'',
					YITH_YWRAQ_TEMPLATE_PATH . '/'
				);
				$output .= ob_get_contents();

				ob_end_clean();
			}

			$rendered_block = str_replace( '##table_content', $output, $rendered_block );

			return apply_filters( 'ywraq_pdf_builder_render_product_table', $rendered_block, $block, $quote, $this );

		}//end render_product_table()

		/**
		 * Render product totals
		 *
		 * @param   array         $block  Gutenberg block.
		 * @param   bool|WC_Order $quote  Order.
		 *
		 * @return string
		 */
		public function render_product_totals( $block, $quote = false ) {
			$rendered_block = render_block( $block );
			if ( ! $quote ) {
				$output      = '';
				$total_items = 0;
				$total_tax   = 0;
				$products    = $this->get_preview_products();
				foreach ( $products as $product ) {
					$total_items += $product['line_subtotal'];
					$total_tax   += $product['line_subtotal_tax'];
				}

				$output .= '<tr class="subtotal-row"><td class="subtotal-label" >' . __(
					'Subtotal',
					'yith-woocommerce-request-a-quote'
				) . '</td><td class="subtotal number">' . wc_price( $total_items ) . '</td></tr>';
				$output .= '<tr class="subtotal-row"><td class="subtotal-label">' . __(
					'Taxes',
					'yith-woocommerce-request-a-quote'
				) . '</td><td class="subtotal number">' . wc_price( $total_tax ) . '</td></tr>';
				$output .= '<tr class="total-row"><td class="total-label">' . __(
					'Total',
					'yith-woocommerce-request-a-quote'
				) . '</td><td style="text-align: right">' . wc_price( $total_items + $total_tax ) . '</td></tr>';

			} else {
				ob_start();
				wc_get_template(
					'pdf/builder/quote-totals.php',
					array(
						'quote' => $quote,
					),
					'',
					YITH_YWRAQ_TEMPLATE_PATH . '/'
				);
				$output = ob_get_contents();
				ob_end_clean();

			}

			$rendered_block = str_replace( '##table_totals', $output, $rendered_block );

			return apply_filters( 'ywraq_pdf_builder_render_product_totals', $rendered_block, $block, $quote, $this );
		}


		/**
		 * Render the quote number
		 *
		 * @param   array         $block  Block.
		 * @param   WP_Order|bool $order  Quote.
		 *
		 * @return string
		 */
		public function render_quote_number( $block, $order = false ) {
			$output = render_block( $block );
			if ( ! $order ) {
				$output = str_replace( '{{quote_number}}', '543', $output );
			} else {
				$output = str_replace(
					'{{quote_number}}',
					apply_filters( 'ywraq_quote_number', $order->get_id() ),
					$output
				);
			}

			return $output;

		}//end render_quote_number()

		/**
		 * Render the quote date
		 *
		 * @param   array         $block  Block.
		 * @param   WP_Order|bool $order  Quote.
		 *
		 * @return string
		 */
		public function render_quote_date( $block, $order = false ) {
			$output = render_block( $block );

			if ( ! $order ) {
				$output = str_replace( '{{current_date}}', date_i18n( wc_date_format(), time() ), $output );
				$output = str_replace(
					'{{created_date}}',
					date_i18n( wc_date_format(), time() - DAY_IN_SECONDS ),
					$output
				);
				$output = str_replace(
					'{{expired_date}}',
					date_i18n( wc_date_format(), time() + DAY_IN_SECONDS ),
					$output
				);
			} else {
				$attr      = $block['attrs'];
				$date_type = isset( $attr['dateType'] ) ? $attr['dateType'] : 'current';

				switch ( $date_type ) {
					case 'expiring':
						$exdata = $order->get_meta( '_ywcm_request_expire' );
						if ( ! empty( $exdata ) ) {
							try {
								$exdata = new WC_DateTime( $exdata );
							} catch ( Exception $e ) {
								$exdata = '';
							}
							$expiration_data = wc_format_datetime( $exdata );
							$output          = str_replace( '{{expired_date}}', $expiration_data, $output );
						} else {
							$output = '';
						}
						break;
					case 'created':
						$order_date = wc_format_datetime( $order->get_date_created() );
						if ( ! empty( $order_date ) ) {
							$output = str_replace( '{{created_date}}', $order_date, $output );
						} else {
							$output = '';
						}

						break;
					default:
						$output = str_replace( '{{current_date}}', date_i18n( wc_date_format(), time() ), $output );

				}
			}

			return $output;

		}

		/**
		 * Render the quote buttons
		 *
		 * @param   array         $block  Block.
		 * @param   WP_Order|bool $order  Quote.
		 *
		 * @return string
		 */
		public function render_quote_buttons( $block, $order = false ) {
			$output = render_block( $block );
			if ( ! $order ) {
				$output = str_replace( '{{ywraq_accept_quote_url}}', '#', $output );
				$output = str_replace( '{{ywraq_reject_quote_url}}', '#', $output );
			} else {
				$output = str_replace( '{{ywraq_accept_quote_url}}', ywraq_get_accepted_quote_page( $order ), $output );
				$output = str_replace( '{{ywraq_reject_quote_url}}', ywraq_get_rejected_quote_page( $order ), $output );
			}

			return $output;
		}

		/**
		 * Render separator block
		 *
		 * @param   array $block  Block.
		 *
		 * @return string
		 */
		public function render_separator_block( $block ) {
			$output  = render_block( $block );
			$output  = str_replace( 'background-color', 'border-color', $output );
			$output  = str_replace( 'hr', 'div', $output );
			$output  = str_replace( '/>', '>', $output );
			$output .= '</div>';

			return $output;
		}

		/**
		 * Return an array with some preview products
		 *
		 * @return array
		 */
		public function get_preview_products() {

			if ( ! empty( $this->preview_products ) ) {
				return $this->preview_products;
			}

			$products = get_posts(
				array(
					'posts_per_page' => 2,
					'orderby'        => 'rand',
					'post_type'      => 'product',
					'status'         => 'published',
					'fields'         => 'ids',
				)
			);

			$preview_products = array();

			if ( $products ) {
				foreach ( $products as $product_id ) {
					$product       = wc_get_product( $product_id );
					$product_image = $product->get_image_id() ? $product->get_image_id() : get_option(
						'woocommerce_placeholder_image',
						0
					);

					$price_with_tax    = (float) wc_get_price_including_tax( $product, array( 'qty' => 1 ) );
					$price_without_tax = (float) wc_get_price_excluding_tax( $product, array( 'qty' => 1 ) );

					$preview_products[] = array(
						'id'                => $product->get_id(),
						'quantity'          => 1,
						'name'              => $product->get_name(),
						'sku'               => $product->get_sku(),
						'permalink'         => $product->get_permalink(),
						'thumbnail'         => wp_get_attachment_image_url( $product_image ),
						'thumbnail_path'    => wp_get_original_image_path( $product_image ),
						'price'             => $product->get_price(),
						'line_subtotal'     => $price_without_tax,
						'line_subtotal_tax' => $price_with_tax - $price_without_tax,
						'line_total'        => $price_without_tax,
						'line_total_tax'    => $price_with_tax - $price_without_tax,
						'currency_code'     => get_woocommerce_currency(),
						'currency_symbol'   => get_woocommerce_currency_symbol(),
					);
				}
			} else {
				$preview_products = array(
					array(
						'id'                => 1,
						'quantity'          => 1,
						'name'              => 'Beanie',
						'sku'               => 'woo-beanie',
						'permalink'         => 'https://example.org',
						'thumbnail'         => YITH_YWRAQ_URL . 'assets/preview/images/beanie.jpg',
						'thumbnail_path'    => YITH_YWRAQ_DIR . 'assets/preview/images/beanie.jpg',
						'price'             => 'yes' === get_option( 'woocommerce_prices_include_tax' ) ? 24 : 20,
						'line_subtotal'     => '20',
						'line_subtotal_tax' => '4',
						'line_total'        => '20',
						'line_total_tax'    => '4',
					),
					array(
						'id'                => 2,
						'quantity'          => 1,
						'name'              => 'Cap',
						'sku'               => 'woo-cap',
						'permalink'         => 'https://example.org',
						'thumbnail'         => YITH_YWRAQ_URL . 'assets/preview/images/cap.jpg',
						'thumbnail_path'    => YITH_YWRAQ_DIR . 'assets/preview/images/cap.jpg',
						'price'             => 'yes' === get_option( 'woocommerce_prices_include_tax' ) ? 36 : 30,
						'line_subtotal'     => 30,
						'line_subtotal_tax' => 6,
						'line_total'        => 30,
						'line_total_tax'    => 6,
					),
				);
			}

			return $preview_products;

		}//end get_preview_products()

		/**
		 * Return a fake customer to show the preview of template
		 *
		 * @param   bool|WC_Order $order  Order.
		 *
		 * @return array
		 */
		protected function get_customer( $order ) {
			if ( ! $order ) {
				$customer = array(
					'billing_first_name' => 'John',
					'billing_last_name'  => 'Doe',
					'billing_address_1'  => '705 West Trout Ave',
					'billing_address_2'  => '',
					'billing_city'       => 'New York',
					'billing_state'      => 'NY',
					'billing_country'    => 'USA',
					'billing_postcode'   => '10002',
					'billing_email'      => 'email@email.com',
					'billing_phone'      => '555-5555',
					'billing_company'    => 'Store House',
				);
			} else {
				$customer = array(
					'billing_first_name' => $order->get_billing_first_name(),
					'billing_last_name'  => $order->get_billing_last_name(),
					'billing_address_1'  => $order->get_billing_address_1(),
					'billing_address_2'  => $order->get_billing_address_2(),
					'billing_city'       => $order->get_billing_city(),
					'billing_state'      => $order->get_billing_state(),
					'billing_country'    => $order->get_billing_country(),
					'billing_postcode'   => $order->get_billing_postcode(),
					'billing_email'      => $order->get_billing_email(),
					'billing_phone'      => $order->get_billing_phone(),
					'billing_company'    => $order->get_billing_company(),
				);
			}

			return apply_filters( 'ywraq_pdf_template_customer_info', $customer, $order, $this );

		}//end get_customer()


		/**
		 * Return the placeholders for customer info
		 *
		 * @return string
		 */
		public function get_customer_info_placeholders() {
			$placeholders = array(
				'billing_first_name',
				'billing_last_name',
				'billing_company',
				'billing_address_1',
				'billing_address_2',
				'billing_city',
				'billing_state',
				'billing_country',
				'billing_email',
				'billing_phone',
				'billing_postcode',
			);

			return apply_filters( 'ywraq_pdf_template_customer_info_placeholders', $placeholders );
		}

	}//end class

}//end if


/**
 * Unique access to instance of YWRAQ_PDF_Template_Builder class
 *
 * @return YWRAQ_PDF_Template_Builder
 */
function ywraq_pdf_template_builder() { //phpcs:ignore
	return YWRAQ_PDF_Template_Builder::get_instance();

}//end ywraq_pdf_template_builder()
