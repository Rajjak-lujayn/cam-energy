<?php //phpcs:ignore WordPress.Files.FileName.InvalidClassFileName

/**
 * Implements the YITH_YWRAQ_Send_Quote_Reminder_Accept class.
 *
 * @class   YITH_YWRAQ_Send_Quote_Reminder_Accept
 * @package YITH\RequestAQuote
 * @since   4.0.0
 * @author  YITH <plugins@yithemes.com>
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'WC_Email', false ) ) {
	require_once WC()->plugin_path() . '/includes/emails/class-wc-email.php';
}


if ( ! class_exists( 'YITH_YWRAQ_Send_Quote_Reminder_Accept' ) ) {

	/**
	 * YITH_YWRAQ_Send_Quote_Reminder_Accept
	 *
	 * @since   4.0.0
	 */
	class YITH_YWRAQ_Send_Quote_Reminder_Accept extends WC_Email {


		/**
		 * Constructor method, used to return object of the class to WC
		 *
		 * @return mixed
		 * @since   4.0.0
		 */
		public function __construct() {
			$this->id          = 'ywraq_send_quote_reminder_accept';
			$this->title       = __( '[YITH Request a Quote] Reminder: pending quote', 'yith-woocommerce-request-a-quote' );
			$this->description = __( 'This email is sent when the customer does not respond to the quote within x days.', 'yith-woocommerce-request-a-quote' );

			$this->heading = __( 'Reminder: you have a pending quote', 'yith-woocommerce-request-a-quote' );
			$this->subject = __( 'Reminder: you have a pending quote', 'yith-woocommerce-request-a-quote' );

			$this->template_base  = YITH_YWRAQ_TEMPLATE_PATH . '/';
			$this->template_html  = 'emails/quote-reminder-accept.php';
			$this->template_plain = 'emails/plain/quote-reminder-accept.php';

			global $woocommerce_wpml;

			$is_wpml_configured = apply_filters( 'wpml_setting', false, 'setup_complete' );
			if ( $is_wpml_configured && defined( 'WCML_VERSION' ) && $woocommerce_wpml ) {
				add_action( 'send_quote_mail_notification', array( $this, 'refresh_email_lang' ), 10, 1 );
			}

			// Triggers for this email.
			add_action( 'send_reminder_quote_accept_mail_notification', array( $this, 'trigger' ), 15, 2 );

			$this->customer_email = true;
			// Call parent constructor.
			parent::__construct();

			if ( 'no' === $this->enabled ) {
				return;
			}

			$this->enable_bcc = $this->get_option( 'enable_bcc' );
			$this->enable_bcc = 'yes' === $this->enable_bcc;
		}

		/**
		 * Refresh email language
		 *
		 * @param int $order_id Order id.
		 */
		public function refresh_email_lang( $order_id ) {
			global $sitepress;
			if ( is_array( $order_id ) ) {
				if ( isset( $order_id['order_id'] ) ) {
					$order_id = $order_id['order_id'];
				} else {
					return;
				}
			}

			$order = wc_get_order( $order_id );
			$lang  = $order->get_meta( 'wpml_language' );
			if ( ! empty( $lang ) ) {
				$sitepress->switch_lang( $lang, true );
			}

		}

		/**
		 * Method triggered to send email
		 *
		 * @param int $order_id Order id.
		 *
		 * @internal param int $args
		 *
		 * @since    4.0.0
		 */
		public function trigger( $order_id ) {

			if ( 'no' === $this->enabled ) {
				return;
			}
			$order = wc_get_order( $order_id );
			if ( $order ) {

				// the cron is scheduled daily so the email is send around a day.
				if ( 'ywraq-pending' === $order->get_status() ) {

					$order_date                    = $order->get_date_created();
					$on                            = $order->get_order_number();
					$this->order                   = $order;
					$this->raq['customer_message'] = $order->get_meta( 'ywraq_customer_message' );
					$this->raq['admin_message']    = nl2br( $order->get_meta( '_ywcm_request_response' ) );
					$this->raq['user_email']       = $order->get_meta( 'ywraq_customer_email' );
					$this->raq['user_name']        = $order->get_meta( 'ywraq_customer_name' );
					$this->raq['expiration_data']  = ! empty( $exdata ) ? date_i18n( wc_date_format(), strtotime( $exdata ) ) : '';
					$this->raq['order-date']       = date_i18n( wc_date_format(), strtotime( $order_date ) );
					$this->raq['order-id']         = $order_id;
					$this->raq['order-number']     = ! empty( $on ) ? $on : $order_id;
					$this->raq['lang']             = $order->get_meta( 'wpml_language' );

					$this->object                          = $order;
					$this->recipient                       = $this->raq['user_email'];
					$this->placeholders['{quote_number}']  = apply_filters( 'ywraq_quote_number', $this->raq['order-id'] );
					$this->placeholders['{customer_name}'] = $this->raq['user_name'];

					$this->heading = apply_filters( 'wpml_translate_single_string', $this->heading, 'admin_texts_woocommerce_ywraq_send_quote_reminder_settings', '[woocommerce_ywraq_send_quote_reminder_settings]heading', $this->raq['lang'] );
					$this->subject = apply_filters( 'wpml_translate_single_string', $this->subject, 'admin_texts_woocommerce_ywraq_send_quote_reminder_settings', '[woocommerce_ywraq_send_quote_reminder_settings]subject', $this->raq['lang'] );

					$this->send( $this->recipient, $this->get_subject(), $this->get_content(), $this->get_headers(), $this->get_attachments() );
				}
			}

		}

		/**
		 * Get attachment
		 *
		 * @return array
		 */
		public function get_attachments() {
			$order_id    = $this->order->get_id();
			$attachments = array();
			if ( get_option( 'ywraq_pdf_attachment' ) === 'yes' ) {
				$attachments[] = YITH_Request_Quote_Premium()->get_pdf_file_path( $order_id );
			}
			$optional_upload = $this->order->get_meta( '_ywraq_optional_attachment' );
			if ( '' !== $optional_upload ) {
				$attachment_id = ywraq_get_attachment_id_by_url( $optional_upload );
				$path          = ( $attachment_id ) ? get_attached_file( $attachment_id ) : null;
				if ( file_exists( $path ) ) {
					$attachments[] = $path;
				}
			}

			return apply_filters( 'woocommerce_email_attachments', $attachments, $this->id, $this->object, $this );
		}

		/**
		 * Get headers function.
		 *
		 * @access public
		 * @return string
		 */
		public function get_headers() {

            $cc = (isset($this->settings['recipient']) && '' != $this->settings['recipient']) ? $this->settings['recipient'] : get_option('admin_email'); //phpcs:ignore

			$headers = array();

            if (get_option('woocommerce_email_from_address') != '') { //phpcs:ignore
				$headers[] = 'Reply-To: ' . $this->get_from_address();
			}

			if ( $this->enable_bcc ) {
				$headers[] = 'Bcc: ' . $cc . "\r\n";
			}

			$headers[] = 'Content-Type: ' . $this->get_content_type();
			$headers   = implode( "\r\n", $headers );
			return apply_filters( 'woocommerce_email_headers', $headers, $this->id, $this->object, $this );
		}

		/**
		 * Get HTML content for the mail
		 *
		 * @return string HTML content of the mail
		 * @since  1.0
		 */
		public function get_content_html() {
			ob_start();
			wc_get_template(
				$this->template_html,
				array(
					'order'             => $this->order,
					'email_heading'     => $this->get_heading(),
					'raq_data'          => $this->raq,
					'email_title'       => $this->format_string( $this->get_option( 'email-title' ) ),
					'email_description' => $this->format_string( $this->get_option( 'email-description' ) ),
					'sent_to_admin'     => true,
					'plain_text'        => false,
					'email'             => $this,
				),
				false,
				$this->template_base
			);

			return ob_get_clean();
		}

		/**
		 * Get Plain content for the mail
		 *
		 * @access public
		 * @return string
		 */
		public function get_content_plain() {
			ob_start();
			wc_get_template(
				$this->template_plain,
				array(
					'order'             => $this->order,
					'raq_data'          => $this->raq,
					'email_title'       => $this->format_string( $this->get_option( 'email-title' ) ),
					'email_description' => $this->format_string( $this->get_option( 'email-description' ) ),
					'sent_to_admin'     => true,
					'plain_text'        => false,
					'email'             => $this,
				),
				false,
				$this->template_base
			);

			$content = ob_get_clean();
			return wordwrap( preg_replace( $this->plain_search, $this->plain_replace, wp_strip_all_tags( $content ) ), 70 );
		}

		/**
		 * Get from name for email.
		 *
		 * @param string $from_name From name.
		 * @return string
		 */
		public function get_from_name( $from_name = '' ) {
            $email_from_name = (isset($this->settings['email_from_name']) && '' != $this->settings['email_from_name']) ? $this->settings['email_from_name'] : $from_name;//phpcs:ignore

			return wp_specialchars_decode( esc_html( $email_from_name ), ENT_QUOTES );
		}

		/**
		 * Get from email address.
		 *
		 * @param string $from_email From email.
		 * @return string
		 */
		public function get_from_address( $from_email = '' ) {
            $email_from_email = (isset($this->settings['email_from_email']) && '' != $this->settings['email_from_email']) ? $this->settings['email_from_email'] : $from_email;//phpcs:ignore

			return sanitize_email( $email_from_email );
		}

		/**
		 * Init form fields to display in WC admin pages
		 *
		 * @return void
		 * @since   4.0.0
		 */
		public function init_form_fields() {
			$this->form_fields = array(
				'enabled'           => array(
					'title'   => __( 'Enable/Disable', 'yith-woocommerce-request-a-quote' ),
					'type'    => 'checkbox',
					'label'   => __( 'Enable this email notification', 'yith-woocommerce-request-a-quote' ),
					'default' => 'yes',
				),
				'days_after_sent'   => array(
					'title'             => __( 'Days (to pass) after the quote has been sent: ', 'yith-woocommerce-request-a-quote' ),
					'type'              => 'number',
					'description'       => __( 'Choose when to send this follow up e-mail.', 'yith-woocommerce-request-a-quote' ),
					'placeholder'       => '',
					'css'               => 'width:50px;',
					'default'           => 2,
					'custom_attributes' => array(
						'data-description' => _x( 'days after the quote creation', 'additional info inside the email option', 'yith-woocommerce-request-a-quote' ),
					),
				),
				'email_from_name'   => array(
					'title'       => __( '"From" Name', 'yith-woocommerce-request-a-quote' ),
					'type'        => 'text',
					'description' => '',
					'placeholder' => '',
					'default'     => get_option( 'woocommerce_email_from_name' ),
				),
				'email_from_email'  => array(
					'title'       => __( '"From" Email Address', 'yith-woocommerce-request-a-quote' ),
					'type'        => 'text',
					'description' => '',
					'placeholder' => '',
					'default'     => get_option( 'woocommerce_email_from_address' ),
				),
				'subject'           => array(
					'title'       => __( 'Subject', 'yith-woocommerce-request-a-quote' ),
					'type'        => 'text',
					// translators:placeholder is default subject.
					'description' => sprintf( __( 'This field lets you modify the email subject line. Leave it blank to use the default subject text: <code>%s</code>. You can use {quote_number} as a placeholder that will show the quote number in the quote.', 'yith-woocommerce-request-a-quote' ), $this->subject ),
					'placeholder' => '',
					'default'     => $this->subject,
				),
				'recipient'         => array(
					'title'       => __( 'Bcc Recipient(s)', 'yith-woocommerce-request-a-quote' ),
					'type'        => 'text',
					'description' => __( 'Enter further recipients (separated by commas) for this email. By default the email is sent to the customer.', 'yith-woocommerce-request-a-quote' ),
					'placeholder' => '',
					'default'     => '',
				),
				'enable_bcc'        => array(
					'title'       => __( 'Send BCC copy', 'yith-woocommerce-request-a-quote' ),
					'type'        => 'checkbox',
					'description' => __( 'Send a blind carbon copy to the administrator', 'yith-woocommerce-request-a-quote' ),
					'default'     => 'no',
				),
				'heading'           => array(
					'title'       => __( 'Email Heading', 'yith-woocommerce-request-a-quote' ),
					'type'        => 'text',
					// translators:placeholder is default heading.
					'description' => sprintf( __( 'This field lets you change the main heading in email notification. Leave it blank to use default heading type: <code>%s</code>.', 'yith-woocommerce-request-a-quote' ), $this->heading ),
					'placeholder' => '',
					'default'     => $this->heading,
				),
				'email-title'       => array(
					'title'       => __( 'Email Title', 'yith-woocommerce-request-a-quote' ),
					'type'        => 'text',
					'placeholder' => '',
					'default'     => '',
				),
				'email-description' => array(
					'title'       => __( 'Email Description', 'yith-woocommerce-request-a-quote' ),
					'type'        => 'textarea',
					'placeholder' => '',
					'default'     => __(
						'Hi {customer_name},
					We are contacting you because you have a pending quote on our shop. Don\'t forget it! Our best proposal is the following:',
						'yith-woocommerce-request-a-quote'
					),
				),
				'email_type'        => array(
					'title'       => __( 'Email type', 'yith-woocommerce-request-a-quote' ),
					'type'        => 'select',
					'description' => __( 'Choose email format.', 'yith-woocommerce-request-a-quote' ),
					'default'     => 'html',
					'class'       => 'email_type wc-enhanced-select',
					'options'     => $this->get_email_type_options(),
				),
			);
		}
	}
}

// returns instance of the mail on file include.
return new YITH_YWRAQ_Send_Quote_Reminder_Accept();
