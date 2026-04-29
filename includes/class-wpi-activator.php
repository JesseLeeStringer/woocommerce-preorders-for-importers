<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WPI_Activator {

	const DB_VERSION_OPTION = 'wpi_db_version';
	const DB_VERSION        = '1';

	public static function activate() {
		self::create_tables();
		update_option( self::DB_VERSION_OPTION, self::DB_VERSION );
	}

	private static function create_tables() {
		global $wpdb;

		$charset_collate = $wpdb->get_charset_collate();
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$sql = "
		CREATE TABLE {$wpdb->prefix}wpi_shipments (
			id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
			reference   VARCHAR(64)  NOT NULL DEFAULT '',
			due_date    DATE         NOT NULL,
			status      ENUM('draft','active','arrived','closed') NOT NULL DEFAULT 'draft',
			notes       TEXT,
			created_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY status (status),
			KEY due_date (due_date)
		) $charset_collate;

		CREATE TABLE {$wpdb->prefix}wpi_shipment_items (
			id                    INT UNSIGNED    NOT NULL AUTO_INCREMENT,
			shipment_id           INT UNSIGNED    NOT NULL,
			product_id            BIGINT UNSIGNED NOT NULL,
			shipment_price        DECIMAL(10,2)   NOT NULL DEFAULT '0.00',
			qty_allocated         INT             NOT NULL DEFAULT 0,
			qty_preordered        INT             NOT NULL DEFAULT 0,
			deposit_type          ENUM('percent','fixed') NOT NULL DEFAULT 'percent',
			deposit_value         DECIMAL(10,2)   NOT NULL DEFAULT '0.00',
			release_offset_days   TINYINT         NOT NULL DEFAULT 0,
			allow_customer_choice TINYINT(1)      NOT NULL DEFAULT 0,
			created_at            DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY shipment_id (shipment_id),
			KEY product_id (product_id)
		) $charset_collate;

		CREATE TABLE {$wpdb->prefix}wpi_stock_log (
			id          INT UNSIGNED    NOT NULL AUTO_INCREMENT,
			product_id  BIGINT UNSIGNED NOT NULL,
			shipment_id INT UNSIGNED,
			action      VARCHAR(64)     NOT NULL DEFAULT '',
			qty_delta   INT             NOT NULL DEFAULT 0,
			user_id     BIGINT UNSIGNED NOT NULL DEFAULT 0,
			notes       TEXT,
			created_at  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY product_id (product_id),
			KEY shipment_id (shipment_id),
			KEY created_at (created_at)
		) $charset_collate;
		";

		dbDelta( $sql );
	}
}
