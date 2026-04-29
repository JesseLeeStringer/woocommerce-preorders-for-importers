<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WPI_Email_Preorder_Confirmation extends WC_Email {

	public function __construct() {
		$this->id             = 'wpi_preorder_confirmation';
		$this->title          = __( 'Preorder Confirmation', 'wpi' );
		$this->description    = __( 'Sent to the customer when an order enters Preorder status.', 'wpi' );
		$this->heading        = __( 'Your preorder is confirmed', 'wpi' );
		$this->subject        = __( 'Preorder confirmed — {site_title} #{order_number}', 'wpi' );
		$this->template_html  = 'emails/preorder-confirmation.php';
		$this->template_plain = 'emails/plain/preorder-confirmation.php';
		$this->template_base  = WPI_PLUGIN_DIR . 'templates/';
		$this->customer_email = true;

		add_action( 'wpi_preorder_placed', [ $this, 'trigger' ] );

		parent::__construct();
	}

	public function trigger( int $order_id ): void {
		$this->setup_locale();
		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			return;
		}
		$this->object    = $order;
		$this->recipient = $order->get_billing_email();

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
