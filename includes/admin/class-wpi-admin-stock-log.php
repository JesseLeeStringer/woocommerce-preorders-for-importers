<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WPI_Admin_Stock_Log {

	public function __construct() {
		add_action( 'admin_menu', [ $this, 'register_menu' ] );
	}

	public function register_menu(): void {
		add_submenu_page(
			'woocommerce',
			__( 'Stock Log', 'wpi' ),
			__( 'Stock Log', 'wpi' ),
			'manage_woocommerce',
			'wpi-stock-log',
			[ $this, 'render' ]
		);
	}

	public function render(): void {
		// CSV export.
		if ( isset( $_GET['export'] ) && 'csv' === $_GET['export'] ) {
			$this->export_csv();
			exit;
		}

		$filters = [
			'product_id'  => isset( $_GET['product_id'] ) ? (int) $_GET['product_id'] : 0,
			'shipment_id' => isset( $_GET['shipment_id'] ) ? (int) $_GET['shipment_id'] : 0,
			'action'      => sanitize_key( $_GET['action_filter'] ?? '' ),
			'date_from'   => sanitize_text_field( $_GET['date_from'] ?? '' ),
			'date_to'     => sanitize_text_field( $_GET['date_to'] ?? '' ),
		];

		$paged   = isset( $_GET['paged'] ) ? (int) $_GET['paged'] : 1;
		$entries = WPI_Stock_Log::get_entries( array_filter( $filters ), 50, $paged );

		require WPI_PLUGIN_DIR . 'templates/admin/stock-log.php';
	}

	private function export_csv(): void {
		$entries = WPI_Stock_Log::get_entries( [], 10000 );

		header( 'Content-Type: text/csv' );
		header( 'Content-Disposition: attachment; filename="wpi-stock-log-' . date( 'Y-m-d' ) . '.csv"' );

		$out = fopen( 'php://output', 'w' );
		fputcsv( $out, [ 'Date', 'Product ID', 'Product', 'Shipment', 'Action', 'Qty Delta', 'User', 'Notes' ] );

		foreach ( $entries as $row ) {
			$user = get_userdata( (int) $row->user_id );
			fputcsv( $out, [
				$row->created_at,
				$row->product_id,
				$row->product_name ?? '',
				$row->shipment_ref ?? '',
				$row->action,
				$row->qty_delta,
				$user ? $user->display_name : 'System',
				$row->notes ?? '',
			] );
		}

		fclose( $out );
	}
}
