<?php //phpcs:ignore WordPress.Files.FileName.InvalidClassFileName
if ( class_exists( 'WPForms_Template', false ) ) :
	/**
	 * YITH Request a Quote
	 * Template for WPForms.
	 */
	class YWRAQ_WPForms_Template extends WPForms_Template {

		/**
		 * Primary class constructor.
		 *
		 * @since 1.0.0
		 */
		public function init() {

			// Template name.
			$this->name = 'YITH Request a Quote Form';

			// Template slug.
			$this->slug = 'yith_request_a_quote';

			// Template description.
			$this->description = '';

			// Template field and settings.
			$this->data = array(
				'field_id' => 9,
				'fields'   => array(
					0 => array(
						'id'          => '0',
						'type'        => 'name',
						'label'       => 'Name',
						'format'      => 'first-last',
						'required'    => '1',
						'size'        => 'medium',
						'connect_to'  => 'billing_first_name',
						'connect_to2' => 'billing_last_name',
					),
					1 => array(
						'id'         => '1',
						'type'       => 'email',
						'label'      => 'Email',
						'required'   => '1',
						'size'       => 'medium',
						'connect_to' => 'billing_email',
					),
					2 => array(
						'id'          => '2',
						'type'        => 'textarea',
						'label'       => 'Message',
						'required'    => '1',
						'size'        => 'medium',
						'limit_count' => '1',
						'limit_mode'  => 'characters',
						'connect_to'  => 'order_comments',
					),
				),
				'settings' => array(
					'form_title'             => 'YITH Request a Quote',
					'submit_text'            => 'Submit you request',
					'submit_text_processing' => 'Sending...',
					'antispam'               => '1',
					'ajax_submit'            => '1',
					'notification_enable'    => '1',
					'notifications'          => array(
						1 => array(
							'email'          => '{admin_email}',
							'subject'        => 'New Entry: Quote',
							'sender_name'    => 'raq',
							'sender_address' => '{admin_email}',
							'replyto'        => '{field_id="1"}',
							'message'        => '{all_fields}

{ywraq_list}',
						),
					),
					'confirmations'          => array(
						1 => array(
							'type'           => 'message',
							'message'        => '<p>Thanks for contacting us! We will be in touch with you shortly.</p>',
							'message_scroll' => '1',
							'page'           => '157',
						),
					),
				),
				'meta'     => array(
					'template' => 'yith_request_a_quote',
				),
			);
		}
	}
	new YWRAQ_WPForms_Template();
endif;
