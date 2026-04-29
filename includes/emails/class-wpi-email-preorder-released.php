<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WPI_Email_Preorder_Released extends WC_Email {

	public function __construct() {
		$this->id             = 'wpi_preorder_released';
		$this->title          = __( 'Preorder Released', 'wpi' );
		$this->description    = __( 'Sent to the customer when their preorder is released and moved to processing.', 'wpi' );
		$this->heading        = __( 'Your preorder is now processing', 'wpi' );
		$this->subject        = __( 'Great news — your preorder is on its way! #{order_number}', 'wpi' );
		$this->template_html  = 'emails/preorder-released.php';
		$this->template_plain = 'emails/plain/preorder-released.php';
		$this->template_base  = WPI_PLUGIN_DIR . 'templates/';
		$this->customer_email = true;
		$this->placeholders   = [
			'{site_title}'   => $this->get_blogname(),
			'{order_number}' => '',
		];

		parent::__construct();
	}

	public function init_form_fields(): void {
		$this->form_fields = [
			'enabled' => [
				'title'   => __( 'Enable/Disable', 'wpi' ),
				'type'    => 'checkbox',
				'label'   => __( 'Enable this email notification', 'wpi' ),
				'default' => 'yes',
			],
			'subject' => [
				'title'       => __( 'Subject', 'wpi' ),
				'type'        => 'text',
				'desc_tip'    => true,
				'description' => __( 'Available placeholders: {site_title}, {order_number}', 'wpi' ),
				'placeholder' => $this->get_default_subject(),
				'default'     => '',
			],
			'heading' => [
				'title'       => __( 'Email heading', 'wpi' ),
				'type'        => 'text',
				'desc_tip'    => true,
				'placeholder' => $this->get_default_heading(),
				'default'     => '',
			],
			'email_type' => [
				'title'       => __( 'Email type', 'wpi' ),
				'type'        => 'select',
				'description' => __( 'Choose which format of email to send.', 'wpi' ),
				'default'     => 'html',
				'class'       => 'email_type wc-enhanced-select',
				'options'     => $this->get_email_type_options(),
				'desc_tip'    => true,
			],
		];
	}

	public function get_default_subject(): string {
		return __( 'Great news — your preorder is on its way! #{order_number}', 'wpi' );
	}

	public function get_default_heading(): string {
		return __( 'Your preorder is now processing', 'wpi' );
	}

	public function trigger( int $order_id, ?WC_Order $order = null ): void {
		$this->setup_locale();
		$this->object    = $order ?? wc_get_order( $order_id );
		if ( ! $this->object ) {
			$this->restore_locale();
			return;
		}
		$this->recipient                      = $this->object->get_billing_email();
		$this->placeholders['{order_number}'] = $this->object->get_order_number();

		if ( $this->is_enabled() && $this->get_recipient() ) {
			$this->send( $this->get_recipient(), $this->get_subject(), $this->get_content(), $this->get_headers(), $this->get_attachments() );
		}

		$this->restore_locale();
	}

	public function get_content_html(): string {
		return wc_get_template_html(
			$this->template_html,
			[
				'order'         => $this->object,
				'email_heading' => $this->get_heading(),
				'sent_to_admin' => false,
				'plain_text'    => false,
				'email'         => $this,
			],
			'',
			$this->template_base
		);
	}

	public function get_content_plain(): string {
		return wc_get_template_html(
			$this->template_plain,
			[
				'order'         => $this->object,
				'email_heading' => $this->get_heading(),
				'sent_to_admin' => false,
				'plain_text'    => true,
				'email'         => $this,
			],
			'',
			$this->template_base
		);
	}
}
