<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WPI_Admin_Settings {

	public function __construct() {
		add_filter( 'woocommerce_get_settings_pages', [ $this, 'register_settings_page' ] );
	}

	public function register_settings_page( array $pages ): array {
		require_once WPI_PLUGIN_DIR . 'includes/admin/class-wpi-settings-page.php';
		$pages[] = new WPI_Settings_Page();
		return $pages;
	}
}
