<?php
/**
 * Plugin Name:       WooCommerce Preorders for Importers
 * Plugin URI:        https://github.com/JesseLeeStringer/woocommerce-preorders-for-importers
 * Description:       Shipment-based preorder management for WooCommerce importers. Define inbound shipments, set per-product deposit rules and release dates, enforce quantity caps, and release stock with one click.
 * Author:            Jesse Lee Stringer
 * Author URI:        https://github.com/JesseLeeStringer
 * Version:           0.1.0
 * Requires at least: 6.4
 * Requires PHP:      8.1
 * WC requires at least: 9.0
 * WC tested up to:   10.6
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       wpi
 * Domain Path:       /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'WPI_VERSION', '0.1.0' );
define( 'WPI_PLUGIN_FILE', __FILE__ );
define( 'WPI_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'WPI_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

register_activation_hook( __FILE__, 'wpi_activate' );
register_deactivation_hook( __FILE__, 'wpi_deactivate' );

function wpi_activate() {
	require_once WPI_PLUGIN_DIR . 'includes/class-wpi-activator.php';
	WPI_Activator::activate();
}

function wpi_deactivate() {
	wp_clear_scheduled_hook( 'wpi_daily_check' );
}

add_action( 'plugins_loaded', 'wpi_init' );

function wpi_init() {
	if ( ! class_exists( 'WooCommerce' ) ) {
		add_action( 'admin_notices', function () {
			echo '<div class="error"><p><strong>WooCommerce Preorders for Importers</strong> requires WooCommerce to be active.</p></div>';
		} );
		return;
	}

	load_plugin_textdomain( 'wpi', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );

	require_once WPI_PLUGIN_DIR . 'includes/class-wpi-shipment.php';
	require_once WPI_PLUGIN_DIR . 'includes/class-wpi-shipment-item.php';
	require_once WPI_PLUGIN_DIR . 'includes/class-wpi-stock-log.php';
	require_once WPI_PLUGIN_DIR . 'includes/class-wpi-preorder-order.php';
	require_once WPI_PLUGIN_DIR . 'includes/class-wpi-preorder-cart.php';
	require_once WPI_PLUGIN_DIR . 'includes/class-wpi-release.php';
	require_once WPI_PLUGIN_DIR . 'includes/class-wpi-shortcodes.php';
	require_once WPI_PLUGIN_DIR . 'includes/class-wpi-emails.php';
	require_once WPI_PLUGIN_DIR . 'includes/frontend/class-wpi-frontend.php';

	if ( is_admin() ) {
		require_once WPI_PLUGIN_DIR . 'includes/admin/class-wpi-admin-shipments.php';
		require_once WPI_PLUGIN_DIR . 'includes/admin/class-wpi-admin-stock-log.php';
		require_once WPI_PLUGIN_DIR . 'includes/admin/class-wpi-admin-settings.php';
	}

	// Declare HPOS compatibility.
	add_action( 'before_woocommerce_init', function () {
		if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', WPI_PLUGIN_FILE, true );
		}
	} );

	// Schedule daily availability cascade check if not already scheduled.
	if ( ! wp_next_scheduled( 'wpi_daily_check' ) ) {
		wp_schedule_event( time(), 'daily', 'wpi_daily_check' );
	}

	new WPI_Preorder_Order();
	new WPI_Preorder_Cart();
	new WPI_Frontend();
	new WPI_Shortcodes();
	new WPI_Emails();

	if ( is_admin() ) {
		new WPI_Admin_Shipments();
		new WPI_Admin_Stock_Log();
		new WPI_Admin_Settings();
	}
}
