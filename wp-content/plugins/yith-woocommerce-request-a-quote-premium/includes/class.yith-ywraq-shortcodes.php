<?php //phpcs:ignore WordPress.Files.FileName.InvalidClassFileName
/**
 * Implements the YITH_YWRAQ_Shortcodes class.
 *
 * @class    YITH_YWRAQ_Shortcodes
 * @package YITH\RequestAQuote
 * @since   1.0.0
 * @author  YITH <plugins@yithemes.com>
 */

defined( 'ABSPATH' ) || exit;


class YITH_YWRAQ_Shortcodes {

	/**
	 * Constructor for the shortcode class
	 */
	public function __construct() {

		add_shortcode( 'yith_ywraq_request_quote', array( $this, 'request_quote_page' ) );
		add_shortcode( 'yith_ywraq_myaccount_quote_list', array( $this, 'my_account_raq_shortcode' ) );
		add_shortcode( 'yith_ywraq_single_view_quote', array( $this, 'single_view_quote' ) );

		add_shortcode( 'yith_ywraq_myaccount_quote', array( $this, 'raq_shortcode_account' ) );
		add_shortcode( 'yith_ywraq_widget_quote', array( $this, 'widget_quote' ) );
		add_shortcode( 'yith_ywraq_mini_widget_quote', array( $this, 'mini_widget_quote' ) );
		add_shortcode( 'yith_ywraq_button_quote', array( $this, 'button_quote' ) );

		add_shortcode( 'yith_ywraq_request_quote_table', array( $this, 'quote_table' ) );
		add_shortcode( 'yith_ywraq_number_items', array( $this, 'ywraq_number_items' ) );
		add_shortcode( 'yith_ywraq_cross_sells', array( $this, 'ywraq_cross_sells_display_sc' ) );

		add_shortcode( 'yith_ywraq_quote_request_form', array( $this, 'quote_request_form' ) );
		add_shortcode( 'yith_ywraq_quote_sent', array( $this, 'quote_sent' ) );

	}

	/**
	 * Show the quote number after the quote is submitted.
	 */
	public function quote_sent() {
		$quote_number = '';
		if ( isset( WC()->session ) ) {
			$quote_id     = WC()->session->get( 'raq_new_order' );
			$quote_number = apply_filters( 'ywraq_quote_number', $quote_id );
		}

		return esc_html( $quote_number );
	}

	/**
	 * Show the quote list for Gutenberg and Elementor
	 *
	 * @param array $atts Attributes.
	 * @param null  $content .
	 * @return false|string
	 */
	public function quote_table( $atts, $content = null ) {
		$raq_content = YITH_Request_Quote()->get_raq_return();
		$show_prices = 'no' === get_option( 'ywraq_hide_price', 'no' );

		$atts = ywraq_parse_atts( $atts );

		$args = shortcode_atts(
			array(
				'raq_content'                     => $raq_content,
				'show_thumbnail'                  => apply_filters( 'ywraq_item_thumbnail', ywraq_show_element_on_list( 'images' ) ),
				'show_sku'                        => ywraq_show_element_on_list( 'sku' ),
				'show_single_price'               => $show_prices && ywraq_show_element_on_list( 'single_price' ),
				'show_line_total'                 => $show_prices && ywraq_show_element_on_list( 'line_total' ),
				'show_quantity'                   => ywraq_show_element_on_list( 'quantity' ),
				'show_totals'                     => $show_prices && ywraq_show_element_on_list( 'total' ),
				'show_back_to_shop'               => true,
				'show_update_button'              => get_option( 'ywraq_show_update_list', 'yes' ),
				'shop_url'                        => ywraq_get_return_to_shop_url(),
				'label_return_to_shop'            => apply_filters( 'ywraq_return_to_shop_label', get_option( 'ywraq_return_to_shop_label' ) ),

				'shop_url_after_send'             => ywraq_get_return_to_shop_after_sent_the_request_url(),
				'label_return_to_shop_after_send' => apply_filters( 'yith_ywraq_return_to_shop_after_sent_the_request_label', get_option( 'ywraq_return_to_shop_after_sent_the_request' ) ),
				'tax_display_list'                => apply_filters( 'ywraq_tax_display_list', get_option( 'woocommerce_tax_display_cart' ) ),
			),
			$atts
		);

		ob_start();

		wc_get_template( 'request-quote-table.php', $args, '', YITH_YWRAQ_TEMPLATE_PATH . '/' );

		return ob_get_clean();
	}

	/**
	 * View Quote Shortcode
	 *
	 * @return string
	 */
	public function raq_shortcode_account() {
		if ( ! is_user_logged_in() ) {
			return false;
		}

		global $wp;
		$view_quote = YITH_Request_Quote()->view_endpoint;

		if ( empty( $wp->query_vars[ $view_quote ] ) ) {
			return WC_Shortcodes::shortcode_wrapper( array( YITH_YWRAQ_Frontend()->my_account, 'view_quote_list' ) );
		} else {
			return WC_Shortcodes::shortcode_wrapper( array( YITH_YWRAQ_Frontend()->my_account, 'view_quote' ) );
		}
	}

	/**
	 * Request Quote Page Shortcode
	 *
	 * @param array $atts .
	 * @param null  $content .
	 *
	 * @return string
	 */
	public function request_quote_page( $atts, $content = null ) {

		$raq_content = YITH_Request_Quote()->get_raq_return();

		$args = shortcode_atts(
			array(
				'raq_content'   => $raq_content,
				'template_part' => 'view',
				'show_form'     => 'yes',
				'form_type'     => get_option( 'ywraq_inquiry_form_type', 'default' ),
				'form_title'    => get_option( 'ywraq_title_before_form', apply_filters( 'ywraq_form_title', __( 'Send the request', 'yith-woocommerce-request-a-quote' ) ) ),
			),
			$atts
		);

		$args['args'] = apply_filters( 'ywraq_request_quote_page_args', $args, $raq_content );

		ob_start();
		/**
		 * APPLY_FILTERS: ywraq_preview_slug
		 *
		 * change the url argument preview for a different text.
		 *
		 * @param string preview
		 */
		$preview_slug = apply_filters( 'ywraq_preview_slug', 'preview' );

		if ( isset( WC()->session, $_REQUEST[ $preview_slug ], $_REQUEST['quote'] ) && sanitize_text_field(wp_unslash($_REQUEST[ $preview_slug ])) ) { //phpcs:ignore

			$session_order = WC()->session->get( 'raq_new_order' );

			if ( sanitize_text_field(wp_unslash($_REQUEST['quote'])) == $session_order ) { //phpcs:ignore
				$order = wc_get_order( $session_order );
				if ( ! $order ) {
					esc_html_e( 'This Quote doesn\'t exist.', 'yith-woocommerce-request-a-quote' );
					return;
				}
				wc_get_template( 'quote-preview.php', array( 'order' => $order ), '', YITH_YWRAQ_TEMPLATE_PATH . '/' );
			} else {
				esc_html_e( 'You do not have permission to read the quote.', 'yith-woocommerce-request-a-quote' );
				return;
			}
		} else {
			wc_get_template( 'request-quote.php', $args, '', YITH_YWRAQ_TEMPLATE_PATH . '/' );
		}

		return ob_get_clean();
	}

	/**
	 *
	 * Add To Quote Button Shortcode
	 *
	 * @param array $atts .
	 * @param null  $content .
	 *
	 * @return string
	 */
	public function button_quote( $atts, $content = null ) {

		if ( ! wp_script_is( 'enqueued', 'yith_ywraq_frontend' ) ) {
			wp_enqueue_style( 'yith_ywraq_frontend' );
		}
		$args = shortcode_atts(
			array(
				'product' => false,
				'label'   => get_option( 'ywraq_show_btn_link_text', __( 'Add to quote', 'yith-woocommerce-request-a-quote' ) ),
				'style'   => ( get_option( 'ywraq_show_btn_link' ) === 'button' ) ? 'button' : 'ywraq-link',
				'colors'  => get_option(
					'ywraq_add_to_quote_button_color',
					array(
						'bg_color'       => '#0066b4',
						'bg_color_hover' => '#044a80',
						'color'          => '#ffffff',
						'color_hover'    => '#ffffff',
					)
				),
				'icon'    => 0,

			),
			$atts
		);

		if ( 'button' === $args['style'] ) {
			if ( isset( $atts['bg_color'] ) ) {
				$args['colors']['bg_color'] = $atts['bg_color'];
			}
			if ( isset( $atts['bg_color_hover'] ) ) {
				$args['colors']['bg_color_hover'] = $atts['bg_color_hover'];
			}
			if ( isset( $atts['color'] ) ) {
				$args['colors']['color'] = $atts['color'];
			}

			if ( isset( $atts['color_hover'] ) ) {
				$args['colors']['color_hover'] = $atts['color_hover'];
			}
		}

		ob_start();

		yith_ywraq_render_button( $args['product'], $args );

		return ob_get_clean();
	}

	/**
	 * Number Items Shortcode
	 *
	 * @param array $atts .
	 * @param null  $content .
	 *
	 * @return string
	 */
	public function ywraq_number_items( $atts, $content = null ) {

		$atts = shortcode_atts(
			array(
				'class'            => 'ywraq_number_items',
				'show_url'         => 'yes',
				'item_name'        => __( 'item', 'yith-woocommerce-request-a-quote' ),
				'item_plural_name' => __( 'items', 'yith-woocommerce-request-a-quote' ),
			),
			$atts
		);

		$num_items = YITH_Request_Quote()->get_raq_item_number();
		$raq_url   = esc_url( YITH_Request_Quote()->get_raq_page_url() );

		if ( 'yes' === $atts['show_url'] ) {
			$div = sprintf( '<div class="%s" data-show_url="%s" data-item_name="%s" data-item_plural_name="%s"><a href="%s">%d <span>%s</span></a></div>', $atts['class'], $atts['show_url'], $atts['item_name'], $atts['item_plural_name'], $raq_url, $num_items, _n( $atts['item_name'], $atts['item_plural_name'], $num_items, 'yith-woocommerce-request-a-quote' ) ); //phpcs:ignore
		} else {
			$div = sprintf( '<div class="%s" data-show_url="%s" data-item_name="%s" data-item_plural_name="%s">%d <span>%s</span></div>', $atts['class'], $atts['show_url'], $atts['item_name'], $atts['item_plural_name'], $num_items, _n( $atts['item_name'], $atts['item_plural_name'], $num_items, 'yith-woocommerce-request-a-quote' ) ); //phpcs:ignore
		}

		return $div;
	}

	/**
	 * Add Quotes section to my-account page
	 *
	 * @since   1.0.0
	 */
	public function my_account_raq_shortcode() {

		ob_start();
		wc_get_template( 'myaccount/my-quotes.php', null, '', YITH_YWRAQ_TEMPLATE_PATH . '/' );

		return ob_get_clean();
	}

	/**
	 * View Quote Shortcode
	 *
	 * @param array $atts .
	 * @param null  $content .
	 *
	 * @return string
	 */
	public function single_view_quote( $atts, $content = null ) {

		$args = shortcode_atts(
			array(
				'order_id' => 0,
				'preview'  => false,
			),
			$atts
		);

		ob_start();
		wc_get_template(
			'myaccount/view-quote.php',
			array(
				'order_id'     => $args['order_id'],
				'current_user' => get_user_by( 'id', get_current_user_id() ),
			),
			'',
			YITH_YWRAQ_TEMPLATE_PATH . '/'
		);

		return ob_get_clean();
	}

	/**
	 * Quote List Widget
	 *
	 * @param array $atts .
	 * @param null  $content .
	 *
	 * @return string
	 */
	public function widget_quote( $atts, $content = null ) {

		$args = shortcode_atts(
			array(
				'title'           => esc_html__( 'Quote List', 'yith-woocommerce-request-a-quote' ),
				'show_thumbnail'  => true,
				'show_price'      => true,
				'show_quantity'   => true,
				'show_variations' => true,
				'button_label'    => esc_html__( 'View list', 'yith-woocommerce-request-a-quote' ),
			),
			$atts
		);

		$args['args'] = $args;

		ob_start();

		the_widget( 'YITH_YWRAQ_List_Quote_Widget', $args );

		return ob_get_clean();
	}

	/**
	 * Quote List Mini Widget
	 *
	 * @param array $atts .
	 * @param null  $content .
	 *
	 * @return string
	 */
	public function mini_widget_quote( $atts, $content = null ) {

		$args = shortcode_atts(
			array(
				'title'             => esc_html__( 'Quote List', 'yith-woocommerce-request-a-quote' ),
				'item_name'         => esc_html__( 'item', 'yith-woocommerce-request-a-quote' ),
				'item_plural_name'  => esc_html__( 'items', 'yith-woocommerce-request-a-quote' ),
				'show_thumbnail'    => 1,
				'show_price'        => 1,
				'show_quantity'     => 1,
				'show_variations'   => 1,
				'show_title_inside' => 0,
				'button_label'      => esc_html__( 'View list', 'yith-woocommerce-request-a-quote' ),
				'open_quote_page'   => 0,
			),
			$atts
		);

		$args['args'] = $args;

		ob_start();

		the_widget( 'YITH_YWRAQ_Mini_List_Quote_Widget', $args );

		return ob_get_clean();
	}

	/**
	 * Quote Cross Sells Shortcode
	 *
	 * @param array $atts .
	 * @param null  $content .
	 *
	 * @return string
	 */
	public function ywraq_cross_sells_display_sc( $atts, $content = null ) {
		if ( get_the_ID() != get_option( 'ywraq_page_id' ) ) { //phpcs:ignore
			return;
		}

		$atts = shortcode_atts(
			array(
				'limit'   => 2,
				'columns' => 2,
				'orderby' => 'rand',
				'order'   => 'desc',
				'offset'  => 0,
			),
			$atts
		);

		ob_start();
		echo '<div class="woocommerce">';
		YITH_YWRAQ_Frontend()->ywraq_cross_sells_display( $atts['limit'], $atts['columns'], $atts['orderby'], $atts['order'], $atts['offset'] );
		echo '</div>';

		return ob_get_clean();
	}

	/**
	 * Shortcode to show the form
	 *
	 * @param array $atts .
	 * @param null  $content .
	 *
	 * @return string
	 */
	public function quote_request_form() {

		$form_type = get_option('ywraq_inquiry_form_type');

		switch ( $form_type ){
			case 'default':
				$form_id = '';
				break;
			case 'gravity-forms':
				$form_id = get_option( 'ywraq_inquiry_gravity_forms_id' );
				break;
			case 'contact-form-7':
				$form_id = ywraq_get_current_contact_form_7();
				break;
			case 'ninja-forms':
				$form_id = get_option('ywraq_inquiry_ninja_forms_id');
				break;
			case 'wpforms':
				$form_id = get_option('ywraq_inquiry_wpforms_id');
				break;
		}
		$atts = array(
				'form_type' => $form_type,
				'form_id'   => $form_id,
			);

		ob_start();

		if ( doing_action( 'wc_ajax_yith_plugin_fw_gutenberg_do_shortcode' ) && 'ninja-forms' === $atts['form_type'] ) {
			echo esc_html__( 'It is not possible to show the preview of the selected form.', 'yith-woocommerce-request-a-quote' );
		} else {
			YITH_Request_Quote_Premium()->get_inquiry_form_by_type( $atts['form_type'], $atts['form_id'] );
		}

		return ob_get_clean();
	}
}
