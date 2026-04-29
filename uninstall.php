<?php

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

global $wpdb;

$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}wpi_stock_log" );
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}wpi_shipment_items" );
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}wpi_shipments" );

delete_option( 'wpi_db_version' );
delete_option( 'wpi_badge_text' );
delete_option( 'wpi_archive_button_text' );
delete_option( 'wpi_date_format' );
delete_option( 'wpi_checkout_notice' );
delete_option( 'wpi_admin_email' );
delete_option( 'wpi_excluded_categories' );

// No scheduled hooks to clear — release is always manual.
