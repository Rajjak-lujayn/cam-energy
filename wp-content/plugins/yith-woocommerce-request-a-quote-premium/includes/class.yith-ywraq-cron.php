<?php //phpcs:ignore WordPress.Files.FileName.InvalidClassFileName
/**
 * Implements the YITH_YWRAQ_Cron class.
 *
 * @class    YITH_YWRAQ_Cron
 * @package YITH\RequestAQuote
 * @since    1.4.9
 * @author   YITH <plugins@yithemes.com>
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class YITH_YWRAQ_Cron
 */
class YITH_YWRAQ_Cron {

	/**
	 * Single instance of the class
	 *
	 * @var YITH_YWRAQ_Cron
	 */
	protected static $instance;

	/**
	 * Automatic quote
	 *
	 * @var string
	 */
	private $automatic_quote;


	/**
	 * Cron time
	 *
	 * @var int
	 */
	private $cron_time;

	/**
	 * Cron time type
	 *
	 * @var string
	 */
	private $cron_time_type;


	/**
	 * Returns single instance of the class
	 *
	 * @return YITH_YWRAQ_Cron
	 * @since 1.0.0
	 */
	public static function get_instance() {
		return ! is_null( self::$instance ) ? self::$instance : self::$instance = new self();
	}

	/**
	 * Constructor
	 *
	 * Initialize plugin and registers actions and filters to be used
	 *
	 * @since  1.0.0
	 */
	public function __construct() {

		add_action( 'wp_loaded', array( $this, 'ywraq_set_cron' ) );

		$this->automatic_quote = get_option( 'ywraq_automate_send_quote', 'no' );
		if ( 'yes' === $this->automatic_quote ) {
			$cron_option          = get_option( 'ywraq_cron_time' );
			$this->cron_time      = isset( $cron_option['time'] ) && ! empty( $cron_option['time'] ) ? (int) $cron_option['time'] : 0;
			$this->cron_time_type = isset( $cron_option['type'] ) ? $cron_option['type'] : 'hours';

			if ( 0 !== $this->cron_time ) {
				add_filter( 'cron_schedules', array( $this, 'cron_schedule' ), 50 ); //phpcs:ignore
				add_action( 'ywraq_automatic_quote', array( $this, 'send_automatic_quote' ) );
			}
		}

		add_action( 'ywraq_clean_cron', array( $this, 'clean_session' ) );
		add_action( 'ywraq_time_validation', array( $this, 'time_validation' ) );
		add_action( 'ywraq_time_validation', array( $this, 'send_email_reminder_to_accept_the_quote' ) );

	}



	/**
	 * Set Cron
	 */
	public function ywraq_set_cron() {

		if ( ! wp_next_scheduled( 'ywraq_time_validation' ) ) {
			$ve = get_option( 'gmt_offset' ) > 0 ? '+' : '-';
			wp_schedule_event( strtotime( '00:00 tomorrow ' . $ve . get_option( 'gmt_offset' ) . ' HOURS' ), 'daily', 'ywraq_time_validation' );
		}

		if ( ! wp_next_scheduled( 'ywraq_clean_cron' ) ) {
			wp_schedule_event( time(), 'daily', 'ywraq_clean_cron' );
		}

		if ( ! wp_next_scheduled( 'ywraq_automatic_quote' ) && 'yes' === $this->automatic_quote && 0 !== $this->cron_time ) {
			wp_schedule_event( time(), 'ywraq_gap', 'ywraq_automatic_quote' );
		}
	}

	/**
	 * Cron Schedule
	 *
	 * Add new schedules to WordPress.
	 *
	 * @param array $schedules Schedules.
	 *
	 * @return mixed
	 * @since  1.0.0
	 */
	public function cron_schedule( $schedules ) {

		$interval = 0;

		if ( 'hours' === $this->cron_time_type ) {
			$interval = 60 * 60 * $this->cron_time;
		} elseif ( 'days' === $this->cron_time_type ) {
			$interval = 24 * 60 * 60 * $this->cron_time;
		} elseif ( 'minutes' === $this->cron_time_type ) {
			$interval = 60 * $this->cron_time;
		}

		$schedules['ywraq_gap'] = array(
			'interval' => $interval,
			'display'  => esc_html__( 'YITH Request a Quote for WooCommerce Cron', 'yith-woocommerce-request-a-quote' ),
		);

		return $schedules;
	}

	/**
	 * Clean the session on database
	 */
	public function clean_session() {
		global $wpdb;

		$cookie_name = '_' . ywraq_get_cookie_name() . '_%';
		$wpdb->query( $wpdb->prepare( 'DELETE FROM ' . $wpdb->prefix . 'options  WHERE option_name LIKE %s', $cookie_name ) ); //phpcs:ignore

	}

	/**
	 * Function called from Cron to swich in
	 * ywraq-expired order status the request expired
	 *
	 * @return  void
	 * @since   1.4.9
	 */
	public function time_validation() {
		// todo:replace get_posts with wc_get_orders.
		$orders = get_posts(
			array(
				'numberposts' => -1,
				'meta_query'  => array( //phpcs:ignore
					array(
						'key'     => '_ywcm_request_expire',
						'value'   => '',
						'compare' => '!=',
					),
				),
				'post_type'   => 'shop_order',
				'post_status' => array( 'wc-ywraq-pending' ),
			)
		);

		foreach ( $orders as $order ) {
			$status = $order->post_status;
			$order  = wc_get_order( $order->ID );

			$expired_data  = strtotime( get_post_meta( $order->get_id(), '_ywcm_request_expire', true ) );
			$expired_data += ( 24 * 60 * 60 ) - 1;
			/**
			 * DO_ACTION:send_reminder_quote_mail
			 *
			 * This action is triggered to send the reminder quote to customer
			 * @param int $order_id Quote id.
			 * @param int $expired_data Expiring date of quote.
			 *
			 */
			do_action( 'send_reminder_quote_mail', $order->get_id(), $expired_data );

			if ( $expired_data < time() && 'wc-ywraq-pending' === $status ) {
				$order->update_status( 'ywraq-expired', __( 'Quote expired.', 'yith-woocommerce-request-a-quote' ) );
			}
		}
	}

	/**
	 * Send the email reminder after x days from quote sent date
	 *
	 * @since 4.0
	 */
	public function send_email_reminder_to_accept_the_quote() {

		$emails = wc()->mailer()->get_emails();
		if ( isset( $emails['YITH_YWRAQ_Send_Quote_Reminder_Accept'] ) ) {
			$email_class = $emails['YITH_YWRAQ_Send_Quote_Reminder_Accept'];
		}

		if ( ! $email_class || ! $email_class->is_enabled() ) {
			return;
		}

		$day_after = $email_class->get_option( 'days_after_sent' );
		if ( $day_after > 0 ) {

			$date_to_compare = current_time( 'timestamp' ) - $day_after * DAY_IN_SECONDS; //phpcs:ignore
			$pending_quotes  = get_posts(
				array(
					'post_type'   => 'shop_order',
					'post_status' => 'wc-ywraq-pending',
					'numberposts' => -1,
					'meta_key'    => 'ywraq_pending_status_date',//phpcs:ignore
					'meta_value'  => gmdate( 'Y-m-d', $date_to_compare ),//phpcs:ignore
					'fields'      => 'ids',
				)
			);

			global $wpdb;

			if ( $pending_quotes ) {
				foreach ( $pending_quotes as $quote ) {
					/**
					 * DO_ACTION:send_reminder_quote_accept_mail
					 *
					 * This action is triggered to send the reminder the customer to pay the quote.
					 * @param WP_Post $quote Quote post object.
					 *
					 */
					do_action( 'send_reminder_quote_accept_mail', $quote );
				}
			}
		}

	}

	/**
	 * Send automatic quote
	 *
	 * @return  void
	 * @since   1.4.9
	 */
	public function send_automatic_quote() {

		if ( 'yes' !== $this->automatic_quote ) {
			return;
		}

		$orders = wc_get_orders(
			array(
				'numberposts' => -1,
				'status'      => array( 'wc-ywraq-new' ),
			)
		);

		if ( $orders ) {
			foreach ( $orders as $order ) {
				$order_id = $order->get_id();
				/**
				 * DO_ACTION:create_pdf
				 *
				 * This action is triggered to create the pdf template
				 *
				 * @param int $order_id Order id.
				 * @param bool $preview Set if the pdf is a preview or not.
				 */
				do_action( 'create_pdf', $order_id, false );
				/**
				 * DO_ACTION:send_quote_mail
				 *
				 * This action is triggered to send the quote email to the customer
				 *
				 * @param int $order_id Order id.
				 */
				do_action( 'send_quote_mail', $order_id );
				$order->update_meta_data( 'ywraq_pending_status_date', gmdate( 'Y-m-d' ) );
				$order->update_status( 'ywraq-pending' );
			}
		}

	}

}


/**
 * Unique access to instance of YITH_YWRAQ_Cron class
 *
 * @return YITH_YWRAQ_Cron
 */
function YITH_YWRAQ_Cron() { //phpcs:ignore
	return YITH_YWRAQ_Cron::get_instance();
}

YITH_YWRAQ_Cron();
