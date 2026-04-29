<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Admin notification: a new preorder has been placed.
 * Standard WC_Email subclass — appears under WooCommerce → Settings → Emails so
 * the recipient, subject, heading, and template can all be customised in the UI.
 */
class WPI_Email_Admin_Preorder extends WC_Email {

	public function __construct() {
		$this->id             = 'wpi_admin_preorder';
		$this->title          = __( 'New Preorder (Admin)', 'wpi' );
		$this->description    = __( 'Sent to administrators when a new preorder is placed.', 'wpi' );
		$this->heading        = __( 'New preorder placed', 'wpi' );
		$this->subject        = __( '[{site_title}] New preorder #{order_number}', 'wpi' );
		$this->template_html  = 'emails/admin-preorder-notification.php';
		$this->template_plain = 'emails/plain/admin-preorder-notification.php';
		$this->template_base  = WPI_PLUGIN_DIR . 'templates/';
		$this->customer_email = false;
		$this->placeholders   = [
			'{site_title}'   => $this->get_blogname(),
			'{order_number}' => '',
		];

		add_action( 'wpi_preorder_placed', [ $this, 'trigger' ] );

		parent::__construct();

		// Default recipient: legacy `wpi_admin_email` setting, then site admin email.
		$this->recipient = $this->get_option( 'recipient', get_option( 'wpi_admin_email', get_option( 'admin_email' ) ) );
	}

	public function init_form_fields(): void {
		$this->form_fields = [
			'enabled' => [
				'title'   => __( 'Enable/Disable', 'wpi' ),
				'type'    => 'checkbox',
				'label'   => __( 'Enable this email notification', 'wpi' ),
				'default' => 'yes',
			],
			'recipient' => [
				'title'       => __( 'Recipient(s)', 'wpi' ),
				'type'        => 'text',
				'description' => sprintf(
					/* translators: %s: default admin email */
					__( 'Comma-separated list of admin recipients. Defaults to %s.', 'wpi' ),
					'<code>' . esc_html( get_option( 'admin_email' ) ) . '</code>'
				),
				'placeholder' => get_option( 'wpi_admin_email', get_option( 'admin_email' ) ),
				'default'     => '',
				'desc_tip'    => true,
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
		return __( '[{site_title}] New preorder #{order_number}', 'wpi' );
	}

	public function get_default_heading(): string {
		return __( 'New preorder placed', 'wpi' );
	}

	public function trigger( int $order_id ): void {
		$this->setup_locale();
		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			$this->restore_locale();
			return;
		}
		$this->object                         = $order;
		$this->placeholders['{order_number}'] = $order->get_order_number();

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
				'sent_to_admin' => true,
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
				'sent_to_admin' => true,
				'plain_text'    => true,
				'email'         => $this,
			],
			'',
			$this->template_base
		);
	}
}
