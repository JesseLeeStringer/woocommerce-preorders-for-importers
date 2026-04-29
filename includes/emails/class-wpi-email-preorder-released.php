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

		parent::__construct();
	}

	public function trigger( int $order_id, ?WC_Order $order = null ): void {
		$this->setup_locale();
		$this->object    = $order ?? wc_get_order( $order_id );
		$this->recipient = $this->object ? $this->object->get_billing_email() : '';

		if ( $this->is_enabled() && $this->get_recipient() ) {
			$this->send( $this->get_recipient(), $this->get_subject(), $this->get_content(), $this->get_headers(), $this->get_attachments() );
		}

		$this->restore_locale();
	}

	public function get_content_html(): string {
		return wc_get_template_html(
			$this->template_html,
			[ 'order' => $this->object, 'email' => $this ],
			'',
			$this->template_base
		);
	}

	public function get_content_plain(): string {
		return wc_get_template_html(
			$this->template_plain,
			[ 'order' => $this->object, 'email' => $this ],
			'',
			$this->template_base
		);
	}
}
