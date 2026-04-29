<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WPI_Settings_Page extends WC_Settings_Page {

	public function __construct() {
		$this->id    = 'wpi_preorders';
		$this->label = __( 'Preorders', 'wpi' );
		parent::__construct();
	}

	public function get_settings(): array {
		return apply_filters( 'wpi_settings', [
			[
				'title' => __( 'Preorder Display', 'wpi' ),
				'type'  => 'title',
				'id'    => 'wpi_display_options',
			],
			[
				'title'   => __( 'Badge text', 'wpi' ),
				'type'    => 'text',
				'id'      => 'wpi_badge_text',
				'default' => __( 'Pre-Order', 'wpi' ),
			],
			[
				'title'   => __( 'Date display format', 'wpi' ),
				'type'    => 'text',
				'id'      => 'wpi_date_format',
				'default' => 'd/m/Y',
				'desc'    => __( 'PHP date format string, e.g. d/m/Y', 'wpi' ),
			],
			[
				'type' => 'sectionend',
				'id'   => 'wpi_display_options',
			],
			[
				'title' => __( 'Checkout', 'wpi' ),
				'type'  => 'title',
				'id'    => 'wpi_checkout_options',
			],
			[
				'title'   => __( 'Checkout notice', 'wpi' ),
				'type'    => 'textarea',
				'id'      => 'wpi_checkout_notice',
				'default' => __( 'Your order includes preorder items. You are paying a deposit only. The balance will be invoiced separately when your order is ready to dispatch.', 'wpi' ),
			],
			[
				'type' => 'sectionend',
				'id'   => 'wpi_checkout_options',
			],
			[
				'title' => __( 'Notifications', 'wpi' ),
				'type'  => 'title',
				'id'    => 'wpi_notification_options',
			],
			[
				'title'   => __( 'Admin notification email', 'wpi' ),
				'type'    => 'email',
				'id'      => 'wpi_admin_email',
				'default' => get_option( 'admin_email' ),
			],
			[
				'type' => 'sectionend',
				'id'   => 'wpi_notification_options',
			],
		] );
	}
}
