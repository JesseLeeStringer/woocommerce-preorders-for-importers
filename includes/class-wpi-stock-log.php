<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WPI_Stock_Log {

	const ACTION_PREORDER_PLACED    = 'preorder_placed';
	const ACTION_PREORDER_CANCELLED = 'preorder_cancelled';
	const ACTION_SHIPMENT_RELEASED  = 'shipment_released';
	const ACTION_MANUAL_ADJUSTMENT  = 'manual_adjustment';

	public static function write(
		int $product_id,
		string $action,
		int $qty_delta,
		?int $shipment_id = null,
		string $notes = ''
	): void {
		global $wpdb;

		$data    = [
			'product_id' => $product_id,
			'action'     => $action,
			'qty_delta'  => $qty_delta,
			'user_id'    => get_current_user_id(),
			'notes'      => sanitize_textarea_field( $notes ),
		];
		$formats = [ '%d', '%s', '%d', '%d', '%s' ];

		// Only include shipment_id when set — letting MySQL keep the column NULL otherwise.
		if ( $shipment_id !== null ) {
			$data['shipment_id'] = $shipment_id;
			$formats[]           = '%d';
		}

		$wpdb->insert( $wpdb->prefix . 'wpi_stock_log', $data, $formats );

		do_action( 'wpi_stock_log_entry', (int) $wpdb->insert_id, [
			'product_id'  => $product_id,
			'shipment_id' => $shipment_id,
			'action'      => $action,
			'qty_delta'   => $qty_delta,
		] );
	}

	/**
	 * Null out shipment_id on log entries when a shipment is deleted, preserving audit history.
	 */
	public static function nullify_shipment( int $shipment_id ): void {
		global $wpdb;
		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->query( $wpdb->prepare(
			"UPDATE {$wpdb->prefix}wpi_stock_log SET shipment_id = NULL WHERE shipment_id = %d",
			$shipment_id
		) );
		// phpcs:enable
	}

	public static function get_entries( array $filters = [], int $per_page = 50, int $paged = 1 ): array {
		global $wpdb;

		$where  = [];
		$values = [];

		if ( ! empty( $filters['product_id'] ) ) {
			$where[]  = 'l.product_id = %d';
			$values[] = (int) $filters['product_id'];
		}
		if ( ! empty( $filters['shipment_id'] ) ) {
			$where[]  = 'l.shipment_id = %d';
			$values[] = (int) $filters['shipment_id'];
		}
		if ( ! empty( $filters['action'] ) ) {
			$where[]  = 'l.action = %s';
			$values[] = $filters['action'];
		}
		if ( ! empty( $filters['date_from'] ) ) {
			$where[]  = 'l.created_at >= %s';
			$values[] = $filters['date_from'] . ' 00:00:00';
		}
		if ( ! empty( $filters['date_to'] ) ) {
			$where[]  = 'l.created_at <= %s';
			$values[] = $filters['date_to'] . ' 23:59:59';
		}

		$where_sql = $where ? 'WHERE ' . implode( ' AND ', $where ) : '';
		$offset    = ( $paged - 1 ) * $per_page;

		$sql = "SELECT l.*, p.post_title AS product_name, s.reference AS shipment_ref
				  FROM {$wpdb->prefix}wpi_stock_log l
			 LEFT JOIN {$wpdb->posts} p ON p.ID = l.product_id
			 LEFT JOIN {$wpdb->prefix}wpi_shipments s ON s.id = l.shipment_id
			  {$where_sql}
			  ORDER BY l.created_at DESC
			  LIMIT %d OFFSET %d";

		$values[] = $per_page;
		$values[] = $offset;

		return $wpdb->get_results( $wpdb->prepare( $sql, ...$values ) );
	}
}
