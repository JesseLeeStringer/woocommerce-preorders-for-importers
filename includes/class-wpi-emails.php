<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WPI_Emails {

	public function __construct() {
		add_filter( 'woocommerce_email_classes', [ $this, 'register' ] );
	}

	public function register( array $emails ): array {
		require_once WPI_PLUGIN_DIR . 'includes/emails/class-wpi-email-preorder-confirmation.php';
		require_once WPI_PLUGIN_DIR . 'includes/emails/class-wpi-email-preorder-released.php';

		$emails['WPI_Email_Preorder_Confirmation'] = new WPI_Email_Preorder_Confirmation();
		$emails['WPI_Email_Preorder_Released']     = new WPI_Email_Preorder_Released();

		return $emails;
	}
}
