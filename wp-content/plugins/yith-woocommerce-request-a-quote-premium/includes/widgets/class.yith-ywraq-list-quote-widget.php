<?php //phpcs:ignore WordPress.Files.FileName.InvalidClassFileName
/**
 * YITH_YWRAQ_List_Quote_Widget add widget to show the request quote list.
 *
 * @class    YITH_YWRAQ_List_Quote_Widget
 * @package YITH\RequestAQuote
 * @since    1.0.0
 * @author   YITH <plugins@yithemes.com>
 */

defined( 'ABSPATH' ) || exit;


if ( ! class_exists( 'YITH_YWRAQ_List_Quote_Widget' ) ) {
	/**
	 * YITH WooCommerce Ajax Navigation Widget
	 *
	 * @since 1.0.0
	 */
	class YITH_YWRAQ_List_Quote_Widget extends WP_Widget {

		/**
		 * Constructor
		 *
		 * @access public
		 */
		public function __construct() {

			/* Widget variable settings. */
			$widget_cssclass    = 'woocommerce widget_ywraq_list_quote';
			$widget_description = __( 'Show products added to your list', 'yith-woocommerce-request-a-quote' );
			$widget_idbase      = 'yith_ywraq_request_quote_list';
			$widget_name        = __( 'YITH Request a Quote for WooCommerce List', 'yith-woocommerce-request-a-quote' );

			/* Widget settings. */
			$widget_ops = array(
				'classname'   => $widget_cssclass,
				'description' => $widget_description,
			);

			/* Create the widget. */
			parent::__construct( $widget_idbase, $widget_name, $widget_ops );

		}

		/**
		 * Widget function
		 *
		 * @param array $args .
		 * @param array $instance .
		 *
		 * @return void
		 * @see    WP_Widget
		 * @access public
		 */
		public function widget( $args, $instance ) {
			extract( $args ); //phpcs:ignore

			$this->istance = $instance;
			$title         = isset( $instance['title'] ) ? $instance['title'] : '';
			$title         = apply_filters( 'widget_title', $title, $instance, $this->id_base );
			$button_label  = isset( $instance['button_label'] ) ? $instance['button_label'] : __( 'View list', 'yith-woocommerce-request-a-quote' );

			if ( ! apply_filters( 'yith_ywraq_before_print_widget', true ) ) {
				return;
			}
			$instance['widget_type'] = 'large';

			echo wp_kses_post( $before_widget );

			if ( $title ) {
				echo wp_kses_post( $before_title . $title . $after_title );
			}

			$raq_content = YITH_Request_Quote()->get_raq_return();

			$args = array(
				'raq_content'     => $raq_content,
				'template_part'   => 'view',
				'button_label'    => $button_label,
				'show_thumbnail'  => isset( $instance['show_thumbnail'] ) && 'true' == $instance['show_thumbnail'], //phpcs:ignore
				'show_price'      => isset( $instance['show_price'] ) && 'true' == $instance['show_price'], //phpcs:ignore
				'show_quantity'   => isset( $instance['show_quantity'] ) && 'true' == $instance['show_quantity'], //phpcs:ignore
				'show_variations' => isset( $instance['show_variations'] ) && 'true' == $instance['show_variations'], //phpcs:ignore
				'widget_type'     => $instance['widget_type'],
			);

			echo '<div class="yith-ywraq-list-widget-wrapper" data-instance="' . wp_kses_post( http_build_query( $args ) ) . '">';
			wc_get_template( 'widgets/quote-list.php', $args, '', YITH_YWRAQ_TEMPLATE_PATH . '/' );
			echo '</div>';
			echo wp_kses_post( $after_widget );
		}

		/**
		 * Update function.
		 *
		 * @param array $new_instance .
		 * @param array $old_instance .
		 *
		 * @return array
		 * @see    WP_Widget->update
		 * @access public
		 */
		public function update( $new_instance, $old_instance ) {
			$check_value                 = array(
				'true',
				true,
			);
			$instance['title']           = wp_strip_all_tags( stripslashes( $new_instance['title'] ) );
			$instance['button_label']    = wp_strip_all_tags( stripslashes( $new_instance['button_label'] ) );
			$instance['show_thumbnail']  = isset( $new_instance['show_thumbnail'] ) && in_array( $new_instance['show_thumbnail'], $check_value ); //phpcs:ignore
			$instance['show_price']      = isset( $new_instance['show_price'] ) && in_array( $new_instance['show_price'], $check_value ); //phpcs:ignore
			$instance['show_quantity']   = isset( $new_instance['show_quantity'] ) && in_array( $new_instance['show_quantity'], $check_value ); //phpcs:ignore
			$instance['show_variations'] = isset( $new_instance['show_variations'] ) && in_array( $new_instance['show_variations'], $check_value ); //phpcs:ignore

			$this->instance = $instance;

			return $instance;
		}

		/**
		 * Form function.
		 *
		 * @param array $instance .
		 *
		 * @return void
		 * @see    WP_Widget->form
		 * @access public
		 */
		public function form( $instance ) {
			$defaults = array(
				'title'           => __( 'Quote List', 'yith-woocommerce-request-a-quote' ),
				'button_label'    => __( 'View quote list', 'yith-woocommerce-request-a-quote' ),
				'show_thumbnail'  => 1,
				'show_price'      => 1,
				'show_quantity'   => 1,
				'show_variations' => 1,
			);

			$instance     = wp_parse_args( (array) $instance, $defaults );
			$title        = isset( $instance['title'] ) ? $instance['title'] : '';
			$button_label = isset( $instance['button_label'] ) ? $instance['button_label'] : '';
			?>
			<!--suppress ALL -->
			<p>
				<label
					for="<?php echo esc_html( $this->get_field_id( 'title' ) ); ?>"><?php esc_html_e( 'Title:', 'yith-woocommerce-request-a-quote' ); ?></label>
				<input type="text" class="widefat" id="<?php echo esc_attr( trim( $this->get_field_id( 'title' ) ) ); ?>"
					name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>"
					value="<?php echo esc_attr( $title ); ?>"/>
			</p>

			<p>
				<label
					for="<?php echo esc_html( $this->get_field_id( 'button_label' ) ); ?>"><?php esc_html_e( 'Button Label:', 'yith-woocommerce-request-a-quote' ); ?></label>
				<input type="text" class="widefat"
					id="<?php echo esc_attr( $this->get_field_id( 'button_label' ) ); ?>"
					name="<?php echo esc_attr( $this->get_field_name( 'button_label' ) ); ?>"
					value="<?php echo esc_attr( $button_label ); ?>"/>
			</p>

			<p>
				<label>
					<input type="checkbox" id="<?php echo esc_attr( $this->get_field_id( 'show_thumbnail' ) ); ?>"
						name="<?php echo esc_attr( $this->get_field_name( 'show_thumbnail' ) ); ?>"
						value="1" <?php checked( $instance['show_thumbnail'], 1 ); ?> />
					<?php esc_html_e( 'Show Thumbnail', 'yith-woocommerce-request-a-quote' ); ?>
				</label>
			</p>

			<p>
				<label>
					<input type="checkbox" id="<?php echo esc_attr( $this->get_field_id( 'show_price' ) ); ?>"
						name="<?php echo esc_attr( $this->get_field_name( 'show_price' ) ); ?>"
						value="1" <?php checked( $instance['show_price'], 1 ); ?> />
					<?php esc_html_e( 'Show Price', 'yith-woocommerce-request-a-quote' ); ?>
				</label>
			</p>

			<p>
				<label>
					<input type="checkbox" id="<?php echo esc_attr( $this->get_field_id( 'show_quantity' ) ); ?>"
						name="<?php echo esc_attr( $this->get_field_name( 'show_quantity' ) ); ?>"
						value="1" <?php checked( $instance['show_quantity'], 1 ); ?> />
					<?php esc_html_e( 'Show Quantity', 'yith-woocommerce-request-a-quote' ); ?>
				</label>
			</p>

			<p>
				<label>
					<input type="checkbox" id="<?php echo esc_attr( $this->get_field_id( 'show_variations' ) ); ?>"
						name="<?php echo esc_attr( $this->get_field_name( 'show_variations' ) ); ?>"
						value="1" <?php checked( $instance['show_variations'], 1 ); ?> />
					<?php esc_html_e( 'Show Variations', 'yith-woocommerce-request-a-quote' ); ?>
				</label>
			</p>

			<?php
		}
	}
}
