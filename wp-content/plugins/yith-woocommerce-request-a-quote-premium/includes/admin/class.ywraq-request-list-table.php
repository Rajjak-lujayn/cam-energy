<?php //phpcs:ignore WordPress.Files.FileName.InvalidClassFileName
/**
 * Implements the YWRAQ_Request_List_Table class.
 *
 * @class   YWRAQ_Request_List_Table
 * @package YITH\RequestAQuote
 * @since   3.1.0
 * @author  YITH <plugins@yithemes.com>
 * @extends WP_List_Table
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

/**
 * Class YWRAQ_Request_List_Table
 */
class YWRAQ_Request_List_Table extends WP_List_Table {

	/**
	 * Class constructor method.
	 *
	 * @since 3.1.0
	 */
	public function __construct() {

		// Set parent defaults.
		parent::__construct(
			array(
				'singular' => 'quote',     // singular name of the listed records.
				'plural'   => 'quotes',    // plural name of the listed records.
				'ajax'     => false,          // does this table support ajax?
				'screen'   => 'ywraq_quote',
			)
		);

		$this->handle_bulk_action();

	}

	/**
	 * Returns columns available in table
	 *
	 * @return array Array of columns of the table.
	 * @since 3.1.0
	 */
	public function get_columns() {
		$columns = array(
			'cb'           => '<input type="checkbox" />',
			'quote_number' => __( 'Order', 'yith-woocommerce-request-a-quote' ),
			'date'         => __( 'Date', 'yith-woocommerce-request-a-quote' ),
			'status'       => __( 'Status', 'yith-woocommerce-request-a-quote' ),
			'total'        => __( 'Total', 'yith-woocommerce-request-a-quote' ),
			'actions'      => '',
		);

		return $columns;
	}

	/**
	 * Returns column to be sortable in table
	 *
	 * @return array Array of sortable columns.
	 * @since 3.1.0
	 */
	public function get_sortable_columns() {
		$sortable_columns = array(
			'quote_number' => array( 'id', false ),
			'date'         => array( 'date', true ),
			'total'        => array( 'order_total', true ),
		);

		return $sortable_columns;
	}

	/**
	 * Column cb.
	 *
	 * @param WC_Order $quote Quote instance.
	 * @return string
	 */
	public function column_cb( $quote ) {
		return sprintf( '<input type="checkbox" name="%1$s[]" value="%2$s" />', $this->_args['singular'], $quote->get_id() );
	}

	/**
	 * Render columm: order_number.
	 *
	 * @param WC_Order $quote Current Quote.
	 *
	 * @return string
	 */
	protected function render_order_number_column( $quote ) {
		$buyer = '';

		if ( $quote->get_billing_first_name() || $quote->get_billing_last_name() ) {
			/* translators: 1: first name 2: last name */
			$buyer = trim( sprintf( '%1$s %2$s', $quote->get_billing_first_name(), $quote->get_billing_last_name() ) );
		} elseif ( $quote->get_billing_company() ) {
			$buyer = trim( $quote->get_billing_company() );
		} elseif ( $quote->get_customer_id() ) {
			$user  = get_user_by( 'id', $quote->get_customer_id() );
			$buyer = ucwords( $user->display_name );
		}

		/**
		 * Filter buyer name in list table orders.
		 *
		 * @param string   $buyer Buyer name.
		 * @param WC_Order $order Order data.
		 * @since 3.1.0
		 */
		$buyer   = apply_filters( 'woocommerce_admin_order_buyer_name', $buyer, $quote );
		$content = '';
		if ( $quote->get_status() === 'trash' ) {
			$content = '<strong>#' . esc_attr( $quote->get_order_number() ) . ' ' . esc_html( $buyer ) . '</strong>';
		} else {
			$content = '<a href="' . esc_url( admin_url( 'post.php?post=' . absint( $quote->get_id() ) ) . '&action=edit' ) . '" class="order-view"><strong>#' . esc_attr( $quote->get_order_number() ) . ' ' . esc_html( $buyer ) . '</strong></a>';
		}

		return $content;
	}

	/**
	 * Fill the columns.
	 *
	 * @param WC_Order $item Current Object.
	 * @param string   $column_name Current Column.
	 *
	 * @return string
	 */
	public function column_default( $item, $column_name ) {

		switch ( $column_name ) {
			case 'quote_number':
				return $this->render_order_number_column( $item );
			case 'date':
				return $this->render_date_column( $item->get_date_created() );
			case 'status':
				$post_edit = add_query_arg(
					array(
						'post'   => $item->get_id(),
						'action' => 'edit',
					),
					admin_url() . 'post.php'
				);
				return '<a href="' . esc_url( $post_edit ) . '"><mark class="order-status status-' . $item->get_status() . '"><span>' . wc_get_order_status_name( $item->get_status() ) . '</span></mark></a>';
			case 'total':
				/**
				 * APPLY_FILTERS:ywraq_request_list_item_total
				 *
				 * Filter the quote total on request a quote list table.
				 *
				 * @param   string  $item_total  Item total.
				 * @param   WC_Order  $item  Order.
				 *
				 * @return string
				 */
				return apply_filters( 'ywraq_request_list_item_total', wc_price( $item->get_total(), array( 'currency' => $item->get_currency() ) ), $item );
			case 'actions':
				$components = array(
					array(
						'type'   => 'action-button',
						'action' => 'edit',
						'title'  => __( 'Edit', 'yith-woocommerce-request-a-quote' ),
						'icon'   => 'edit',
						'url'    => esc_url( admin_url( 'post.php?post=' . absint( $item->get_id() ) ) . '&action=edit' ),
					),
					array(
						'type'   => 'action-button',
						'action' => 'show-more',
						'icon'   => 'more',
						'menu'   => array(
							array(
								'name' => __( 'Change status to New', 'yith-woocommerce-request-a-quote' ),
								'url'  => add_query_arg(
									array(
										'action'              => 'yith_ywraq_change_quote_status',
										'quote_id'            => $item->get_id(),
										'status'              => 'new',
										'change_status_nonce' => wp_create_nonce( 'yith_ywraq_change_quote_status' )
									),
									admin_url(),
								),
							),
							array(
								'name' => __( 'Change status to Pending', 'yith-woocommerce-request-a-quote' ),
								'url'  => add_query_arg(
									array(
										'action'              => 'yith_ywraq_change_quote_status',
										'quote_id'            => $item->get_id(),
										'status'              => 'pending',
										'change_status_nonce' => wp_create_nonce( 'yith_ywraq_change_quote_status' )
									),
									admin_url(),
								),
							),
							array(
								'name' => __( 'Change status to Expired', 'yith-woocommerce-request-a-quote' ),
								'url'  => add_query_arg(
									array(
										'action'              => 'yith_ywraq_change_quote_status',
										'quote_id'            => $item->get_id(),
										'status'              => 'expired',
										'change_status_nonce' => wp_create_nonce( 'yith_ywraq_change_quote_status' )
									),
									admin_url(),
								),
							),
							array(
								'name'         => __( 'Delete', 'yith-woocommerce-request-a-quote' ),
								'url'          => get_delete_post_link( $item->get_id(), '', true ),
								'confirm_data' => array(
									'title'               => __( 'Confirm delete', 'yith-woocommerce-request-a-quote' ),
									'message'             => __( 'Are you sure you want to delete this item?', 'yith-woocommerce-request-a-quote' ) . '<br /><br />' . __( 'This action cannot be undone and you will not be able to recover this data.', 'yith-woocommerce-request-a-quote' ),
									'confirm-button'      => _x( 'Yes, delete', 'Delete confirmation action', 'yith-woocommerce-request-a-quote' ),
									'cancel-button'       => __( 'No', 'yith-woocommerce-request-a-quote' ),
									'confirm-button-type' => 'delete',
								),
							),
						),
					),
				);

				foreach ( $components as $component ) {
					yith_plugin_fw_get_component( $component );
				}

				break;
			default:
				return $item->$column_name; // Show the whole array for troubleshooting purposes.
		}
	}

	/**
	 * Get bulk actions
	 *
	 * @return array|false|string
	 * @since  3.1.0
	 */
	public function get_bulk_actions() {
		return array(
			'mark_new'     => __( 'Change status to New Quote', 'yith-woocommerce-request-a-quote' ),
			'mark_pending' => __( 'Change status to Pending Quote', 'yith-woocommerce-request-a-quote' ),
			'mark_expired' => __( 'Change status to Expired Quote', 'yith-woocommerce-request-a-quote' ),
			'delete'       => __( 'Delete', 'yith-woocommerce-request-a-quote' ),
		);
	}

	/**
	 * Return the quote total number
	 *
	 * @param string $status Status.
	 *
	 * @return array|object|null
	 */
	protected function get_quote_total_number( $status = 'all' ) {
		global $wpdb;

		if ( 'all' === $status ) {
			$query = $wpdb->prepare(
				"SELECT count(*) as counter FROM {$wpdb->posts} as quotes INNER JOIN {$wpdb->postmeta} as pm ON ( quotes.ID = pm.post_id)  WHERE quotes.post_type = %s AND pm.meta_key='ywraq_raq' and pm.meta_value='yes' ",
				'shop_order'
			);
		} else {
			$query = $wpdb->prepare(
				"SELECT count(*) as counter FROM {$wpdb->posts} as quotes INNER JOIN {$wpdb->postmeta} as pm ON ( quotes.ID = pm.post_id)  WHERE quotes.post_type = %s AND pm.meta_key='ywraq_raq' and pm.meta_value='yes' AND quotes.status = %s ",
				'shop_order',
				$status
			);
		}
		/**
		 * APPLY_FILTERS:ywraq_status_counter_query
		 *
		 * Filter the query to get the number of quote requests.
		 *
		 * @param   string  $query  SQL query.
		 *
		 * @return string
		 */
		$query = apply_filters( 'ywraq_status_counter_query', $query );

		return $wpdb->get_results( $query, 'ARRAY_A' ); //phpcs:ignore
	}

	/**
	 * Return the subscription status
	 *
	 * @return array|object|null
	 */
	protected function get_status_counter() {
		global $wpdb;

		$query = $wpdb->prepare(
			"SELECT count(*) as counter, post_status as status  FROM {$wpdb->posts} as quotes WHERE quotes.post_type = %s  GROUP BY quotes.post_status",
			'shop_order'
		);
		$query = apply_filters( 'ywraq_status_counter_query', $query );

		return $wpdb->get_results( $query, 'ARRAY_A' ); //phpcs:ignore
	}

	/**
	 * Show the filter for status.
	 */
	protected function render_status_filter() {

		$current_status = isset( $_REQUEST['status'] ) && ! empty( $_REQUEST['status'] ) ? $_REQUEST['status'] : '';  // phpcs:ignore
		$counters       = $this->get_status_counter();

		$quote_status = ywraq_get_quote_status_list();
		?>
		<div class="alignleft actions">
			<select name="status" id="status">
				<option value=""><?php esc_html_e( 'All statuses', 'yith-woocommerce-request-a-quote' ); ?></option>
				<?php
				foreach ( $quote_status as $status ) :
					$wc_status = 'wc-' . $status;
					$key       = array_search( $wc_status, array_column( $counters, 'status' ), true );
					if ( false !== $key ) :
						$status_name = wc_get_order_status_name( $status );
						?>
						<option value="<?php echo esc_attr( $status ); ?>" <?php selected( $status, $current_status, true ); ?> >
							<?php echo esc_html( $status_name ); ?>
						</option>
					<?php endif; ?>
				<?php endforeach; ?>
			</select>
		</div>
		<?php
	}

	/**
	 * Render the customer filter.
	 */
	public function render_customer_filter() {

		echo '<div class="alignleft actions">';
		$user_string = '';
		$customer_id = '';

		if ( ! empty( $_REQUEST['customer_user'] ) ) { // phpcs:ignore
			$customer_id = absint( $_REQUEST['customer_user'] ); // phpcs:ignore
			$user        = get_user_by( 'id', $customer_id );
			$user_string = $user ? esc_html( $user->display_name ) . ' (#' . absint( $user->ID ) . ' &ndash; ' . esc_html( $user->user_email ) : '';
		}

		$args = array(
			'type'             => 'hidden',
			'class'            => 'wc-customer-search',
			'id'               => 'customer_user',
			'name'             => 'customer_user',
			'data-placeholder' => esc_html__( 'Filter by registered customers', 'yith-woocommerce-request-a-quote' ),
			'data-allow_clear' => true,
			'data-selected'    => array( $customer_id => esc_attr( $user_string ) ),
			'data-multiple'    => false,
			'value'            => $customer_id,
			'style'            => 'width:200px',
		);

		yit_add_select2_fields( $args );

		echo '</div>';

	}

	/**
	 * Extra controls to be displayed between bulk actions and pagination, which
	 * includes our Filters: Customers, Products, Availability Dates
	 *
	 * @param string $which the placement, one of 'top' or 'bottom'.
	 * @since 3.1.0
	 * @see WP_List_Table::extra_tablenav();
	 */
	public function extra_tablenav( $which ) {
		if ( 'top' === $which ) {
			// Customers, products.
			echo '<div class="alignleft actions">';
			$this->months_dropdown( 'shop_order' );
			echo '</div>';
			$this->render_status_filter();
			$this->render_customer_filter();

			submit_button(
				__( 'Filter', 'yith-woocommerce-request-a-quote' ),
				'button',
				false,
				false,
				array(
					'id'    => 'post-query-submit',
					'class' => 'ywraq_filter_button',
				)
			);
		}
	}

	/**
	 * Process Bulk Actions
	 *
	 */
	public function handle_bulk_action() {

		$action = $this->current_action();
		$quote  = isset( $_REQUEST['quote'] ) ?  $_REQUEST['quote'] : array(); //phpcs:ignored

		if ( ! empty( $action ) && -1 !== $action && ! empty( $quote ) ) {
			// Handle the bulk action.
			if ( in_array( $action, array( 'mark_new', 'mark_pending', 'mark_expired' ), true ) ) {
				$new_status = str_replace( 'mark_', 'ywraq-', $action );
				foreach ( $quote as $q ) {
					$order = wc_get_order( $q );
					if ( ! $order ) {
						continue;
					}

					$order->update_status( $new_status );
				}
			}

			if ( 'delete' === $action ) {
				foreach ( $quote as $q ) {
					wp_delete_post( $q );
				}
			}
		}

	}


	/**
	 * Render any custom filters and search inputs for the list table.
	 */
	protected function render_filters() {
		$user_string = '';
		$user_id     = '';

		if ( ! empty( $_GET['_customer_user'] ) ) { // phpcs:disable WordPress.Security.NonceVerification.Recommended
			$user_id = absint( $_GET['_customer_user'] ); // phpcs:ignore
			$user    = get_user_by( 'id', $user_id );

			$user_string = sprintf(
			/* translators: 1: user display name 2: user ID 3: user email */
				esc_html__( '%1$s (#%2$s &ndash; %3$s)', 'woocommerce' ),
				$user->display_name,
				absint( $user->ID ),
				$user->user_email
			);
		}
		?>
		<select class="wc-customer-search" name="_customer_user"
			data-placeholder="<?php esc_attr_e( 'Filter by registered customer', 'woocommerce' ); ?>"
			data-allow_clear="true">
			<option value="<?php echo esc_attr( $user_id ); ?>"
				selected="selected"><?php echo htmlspecialchars( wp_kses_post( $user_string ) ); // phpcs:ignore. ?>
			<option>
		</select>
		<?php
	}


	/**
	 * Render columm: order_date.
	 *
	 * @param DateTime $order_date Date of Quote.
	 * @return string
	 */
	protected function render_date_column( $order_date ) {
		if ( ! $order_date ) {
			echo '&ndash;';
			return;
		}

		// Check if the order was created within the last 24 hours, and not in the future.
		if ( $order_date->getTimestamp() > strtotime( '-1 day', time() ) && $order_date->getTimestamp() <= time() ) {
			// translators: placeholder time human-readable time difference.
			$show_date = sprintf( _x( '%s ago', 'human-readable time difference', 'yith-woocommerce-request-a-quote' ), human_time_diff( $order_date->getTimestamp(), time() ) );
		} else {
			$show_date = $order_date->date_i18n( apply_filters( 'woocommerce_admin_order_date_format', __( 'M j, Y', 'yith-woocommerce-request-a-quote' ) ) );
		}
		$date_string = sprintf(
			'<time datetime="%1$s" title="%2$s">%3$s</time>',
			esc_attr( $order_date->date( 'c' ) ),
			esc_html( $order_date->date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ) ) ),
			esc_html( $show_date )
		);

		return $date_string;
	}

	/**
	 * Prepare items for table
	 *
	 * @return void
	 * @since 3.1.0
	 */
	public function prepare_items() {

		$per_page = 20;
		$status   = ywraq_get_quote_status_list();

		$get_from_query = true;
		$default_args   = array(
			'limit'     => -1,
			'ywraq_raq' => 'yes',
			'status'    => $status,
		);

		if ( isset( $_REQUEST['action'] ) && -1 == sanitize_text_field( wp_unslash( $_REQUEST['action'] ) ) ) { //phpcs:ignore
			// filter the results.
			if ( isset( $_REQUEST['status'] ) && '' !== sanitize_text_field( wp_unslash( $_REQUEST['status'] ) ) ) {
				$default_args['status'] = sanitize_text_field( wp_unslash( $_REQUEST['status'] ) );
			}

			if ( ! empty( $_REQUEST['m'] ) ) {
				$m                            = sanitize_text_field( wp_unslash( $_REQUEST['m'] ) );
				$date                         = substr( $m, 0, 4 ) . '-' . substr( $m, -2 );
				$default_args['date_created'] = $date . '-01...' . $date . '-31';
				$get_from_query               = false;
			}

			if ( isset( $_REQUEST['customer_user'] ) && '' !== sanitize_text_field( wp_unslash( $_REQUEST['customer_user'] ) ) ) {
				$default_args['customer_id'] = sanitize_text_field( wp_unslash( $_REQUEST['customer_user'] ) );
				$get_from_query              = false;
			}
		}
		if ( $get_from_query ) {
			$status      = is_array( $default_args['status'] ) ? 'all' : $default_args['status'];
			$quotes      = $this->get_quote_total_number( $status );
			$total_items = $quotes[0]['counter'];
		} else {
			$quotes      = wc_get_orders( $default_args );
			$total_items = count( $quotes );
		}

		$current_page = $this->get_pagenum();

		$page_args = array(
			'limit' => $per_page,
			'page'  => $current_page,
		);

		$args = wp_parse_args( $page_args, $default_args );

		if ( isset( $_GET['orderby'] ) ) {
			$orderby = sanitize_text_field( wp_unslash( $_GET['orderby'] ) );
			$order   = isset( $_GET['order'] ) ? sanitize_text_field( wp_unslash( $_GET['order'] ) ) : 'DESC';

			if ( isset( $orderby ) ) {
				if ( 'order_total' === $orderby ) {
					$args['orderby']  = 'meta_value';
					$args['meta_key'] = '_order_total'; //phpcs:ignore
				} else {
					$args['orderby'] = $orderby;
				}

				if ( isset( $order ) ) {
					$args['order'] = $order;
				}
			}
		}

		if ( isset( $_REQUEST['action'] ) && -1 === sanitize_text_field( wp_unslash( $_REQUEST['action'] ) ) ) {
			// filter the results.
			if ( isset( $_REQUEST['status'] ) && '' !== sanitize_text_field( wp_unslash( $_REQUEST['status'] ) ) ) {
				$args['status'] = sanitize_text_field( wp_unslash( $_REQUEST['status'] ) );
			}
		}

		$query  = new WC_Order_Query( $args );
		$quotes = $query->get_orders();
		// retrieve data for table.
		$this->items = $quotes;

		// sets pagination args.
		$this->set_pagination_args(
			array(
				'total_items' => $total_items,
				'per_page'    => $per_page,
				'total_pages' => ceil( $total_items / $per_page ),
			)
		);

	}



}
