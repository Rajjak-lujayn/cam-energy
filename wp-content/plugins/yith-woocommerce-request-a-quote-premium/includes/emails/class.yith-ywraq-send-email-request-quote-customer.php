<?php //phpcs:ignore WordPress.Files.FileName.InvalidClassFileName
/**
 * Implements the YITH_YWRAQ_Send_Email_Request_Quote_Customer class.
 *
 * @class   YITH_YWRAQ_Send_Email_Request_Quote_Customer
 * @package YITH\RequestAQuote
 * @since   1.0.0
 * @author  YITH <plugins@yithemes.com>
 */

defined( 'ABSPATH' ) || exit;


if ( ! class_exists( 'YITH_YWRAQ_Send_Email_Request_Quote_Customer' ) ) {

	/**
	 * YITH_YWRAQ_Send_Email_Request_Quote_Customer
	 *
	 * @since 1.0.0
	 */
	class YITH_YWRAQ_Send_Email_Request_Quote_Customer extends WC_Email {

		/**
		 * Constructor method, used to return object of the class to WC
		 *
		 * @since 1.0.0
		 */
		public function __construct() {
			$this->id          = 'ywraq_email_customer';
			$this->title       = __( '[YITH Request a Quote] Confirmation email for the customer', 'yith-woocommerce-request-a-quote' );
			$this->description = __( 'This email is sent as confirmation when a user clicks on "Request a quote" button', 'yith-woocommerce-request-a-quote' );

			$this->heading = __( 'Quote request', 'yith-woocommerce-request-a-quote' );
			$this->subject = __( '[Quote request]', 'yith-woocommerce-request-a-quote' );

			$this->template_base  = YITH_YWRAQ_TEMPLATE_PATH . '/';
			$this->template_html  = 'emails/request-quote-customer.php';
			$this->template_plain = 'emails/plain/request-quote-customer.php';
			$this->customer_email = true;
			$this->email_type     = 'html';
			// Call parent constructor.
			parent::__construct();

			if ( 'no' === $this->enabled ) {
				return;
			}

			global $woocommerce_wpml;

			$is_wpml_configured = apply_filters( 'wpml_setting', false, 'setup_complete' );
			if ( $is_wpml_configured && defined( 'WCML_VERSION' ) && $woocommerce_wpml ) {
				add_action( 'send_quote_mail_notification', array( $this, 'refresh_email_lang' ), 10, 1 );
			}

			// Triggers for this email.
			add_action( 'send_raq_customer_mail_notification', array( $this, 'trigger' ), 15, 1 );
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
		 * @param array $args Arguments.
		 *
		 * @return void
		 * @since  1.0
		 */
		public function trigger( $args ) {
			if ( ! isset( $args['user_email'] ) ) {
				return;
			}

			$new_order             = WC()->session->raq_new_order;
			$this->raq             = $args;
			$this->raq['order_id'] = is_null( $new_order ) ? 0 : $new_order;
			$this->recipient       = $this->raq['user_email'];
			// quote checkout.
			$this->raq['content_type'] = isset( $args['from_checkout'] ) ? 'order_items' : 'raq_content';

			$order = wc_get_order( $this->raq['order_id'] );
			if ( 'order_items' === $this->raq['content_type'] && $order ) {
				$this->raq['raq_content'] = $order->get_items();
			}

			$this->placeholders['{quote_number}']  = apply_filters( 'ywraq_quote_number', $this->raq['order_id'] );
			$this->placeholders['{quote_user}']    = $this->raq['user_name'];
			$this->placeholders['{quote_email}']   = $this->raq['user_email'];
			$this->placeholders['{customer_name}'] = $this->raq['user_name'];

			if ( ! is_null( $new_order ) ) {
				$this->object = wc_get_order( $new_order );
			}

			$recipients = (array) $this->get_recipient();
			$recipients = array_merge( $recipients, explode( ',', $this->get_option( 'recipient' ) ) );
			$recipients = implode( ',', $recipients );
			// remove spaces for avoiding problems on multi-recipients emails.
			$recipients = str_replace( ' ', '', $recipients );

			$this->heading = $this->get_heading();
			$this->subject = $this->get_subject();

			if ( isset( $this->raq['lang'] ) ) {
				$this->heading = apply_filters( 'wpml_translate_single_string', $this->heading, 'admin_texts_woocommerce_ywraq_email_customer_settings', '[woocommerce_ywraq_email_customer_settings]heading', $this->raq['lang'] );
				$this->subject = apply_filters( 'wpml_translate_single_string', $this->subject, 'admin_texts_woocommerce_ywraq_email_customer_settings', '[woocommerce_ywraq_email_customer_settings]subject', $this->raq['lang'] );
			}

			$return = $this->send( $recipients, $this->subject, $this->get_content(), $this->get_headers(), $this->get_attachments() );

		}

		/**
		 * Get headers function.
		 *
		 * @access public
		 * @return string
		 */
		public function get_headers() {
			$cc = ( isset( $this->settings['recipient'] ) && '' != $this->settings['recipient'] ) ? $this->settings['recipient'] : get_option( 'admin_email' ); //phpcs:ignore

			$headers = array();

			if ( get_option( 'woocommerce_email_from_address' ) != '' ) { //phpcs:ignore
				$headers[] = 'Reply-To: ' . $this->get_from_address();
			}

			if ( $this->enable_bcc ) {
				$headers[] = 'Bcc: ' . $cc . "\r\n";
			}

			$headers[] = 'Content-Type: ' . $this->get_content_type();
			$obj       = isset( $this->object ) ? $this->object : false;
			return apply_filters( 'woocommerce_email_headers', $headers, $this->id, $obj, $this );
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
					'raq_data'          => $this->raq,
					'email_heading'     => $this->get_heading(),
					'email_description' => $this->format_string( $this->get_option( 'email-description' ) ),
					'sent_to_admin'     => true,
					'plain_text'        => false,
					'email'             => $this,
				),
				'',
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
					'raq_data'          => $this->raq,
					'email_heading'     => $this->get_heading(),
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
		 * Return the array with the attachments' file paths.
		 *
		 * @return array
		 */
		public function get_attachments() {
			$attachments = ywraq_get_default_form_attachment( $this->raq );
			$obj         = isset( $this->object ) ? $this->object : false;
			return apply_filters( 'woocommerce_email_attachments', $attachments, $this->id, $obj, $this );
		}

		/**
		 * Get from name for email.
		 *
		 * @param string $from_name From name.
		 * @return string
		 */
		public function get_from_name( $from_name = '' ) {
			$email_from_name = ( isset( $this->settings['email_from_name'] ) && '' != $this->settings['email_from_name'] ) ? $this->settings['email_from_name'] : $from_name; //phpcs:ignore
			$email_from_name = apply_filters( 'ywraq_request_a_quote_send_email_from_name', $email_from_name, $this );

			return wp_specialchars_decode( esc_html( $email_from_name ), ENT_QUOTES );
		}

		/**
		 * Get from email address.
		 *
		 * @param string $from_email From email.
		 * @return string
		 */
		public function get_from_address( $from_email = '' ) {
			$email_from_email = ( isset( $this->settings['email_from_email'] ) && '' != $this->settings['email_from_email'] ) ? $this->settings['email_from_email'] : $from_email; //phpcs:ignore
			$email_from_email = apply_filters( 'ywraq_request_a_quote_send_email_from_address', $email_from_email, $this );

			return sanitize_email( $email_from_email );
		}


		/**
		 * Init form fields to display in WC admin pages
		 *
		 * @return void
		 * @since  1.0
		 */
		public function init_form_fields() {
			$this->form_fields = array(
				'enabled'           => array(
					'title'   => __( 'Enable/Disable', 'yith-woocommerce-request-a-quote' ),
					'type'    => 'checkbox',
					'label'   => __( 'Enable this email notification', 'yith-woocommerce-request-a-quote' ),
					'default' => 'no',
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
					'description' => sprintf( __( 'This field lets you edit email subject line. Leave it blank to use default subject text: <code>%s</code>. You can use {quote_number} as a placeholder that will show the quote number in the quote,<br>{quote_user} to show the customer\'s name, {quote_email} to show the customer\'s email', 'yith-woocommerce-request-a-quote' ), $this->subject ),
					'placeholder' => '',
					'default'     => '',
				),
				'recipient'         => array(
					'title'       => __( 'Bcc Recipient(s)', 'yith-woocommerce-request-a-quote' ),
					'type'        => 'text',
					'description' => __( 'Enter further recipients (separated by commas) for this email. By default the email is sent to the customer.', 'yith-woocommerce-request-a-quote' ),
					'placeholder' => '',
					'default'     => '',
				),
				'enable_bcc'        => array(
					'title'       => __( 'Enter additional recipients', 'yith-woocommerce-request-a-quote' ),
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
				'email-description' => array(
					'title'       => __( 'Email Description', 'yith-woocommerce-request-a-quote' ),
					'type'        => 'textarea',
					'placeholder' => '',
					'default'     => __(
						'Hi {customer_name}, 
					You have sent a quote request for the following products:',
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
return new YITH_YWRAQ_Send_Email_Request_Quote_Customer();
