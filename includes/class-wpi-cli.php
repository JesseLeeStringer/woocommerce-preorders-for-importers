<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	return;
}

/**
 * Manage WooCommerce Preorders for Importers from the command line.
 */
class WPI_CLI {

	/**
	 * List all shipments.
	 *
	 * ## EXAMPLES
	 *
	 *     wp wpi list
	 */
	public function list( $args, $assoc ): void {
		$shipments = WPI_Shipment::get_all();
		$rows = [];
		foreach ( $shipments as $s ) {
			$items = WPI_Shipment_Item::for_shipment( $s->id );
			$rows[] = [
				'id'         => $s->id,
				'reference'  => $s->reference,
				'due_date'   => $s->due_date,
				'status'     => $s->status,
				'products'   => count( $items ),
				'preordered' => array_sum( array_map( fn( $i ) => $i->qty_preordered, $items ) ),
			];
		}
		WP_CLI\Utils\format_items( 'table', $rows, [ 'id', 'reference', 'due_date', 'status', 'products', 'preordered' ] );
	}

	/**
	 * Show what releasing a shipment will do, without releasing it.
	 *
	 * ## OPTIONS
	 *
	 * <id>
	 * : The shipment ID.
	 *
	 * ## EXAMPLES
	 *
	 *     wp wpi preview 7
	 */
	public function preview( $args, $assoc ): void {
		$id      = (int) ( $args[0] ?? 0 );
		$preview = WPI_Release::preview_release( $id );

		WP_CLI::log( sprintf( 'Orders to be moved to Processing: %d', $preview['order_count'] ) );
		WP_CLI::log( '' );

		$rows = [];
		foreach ( $preview['stock_updates'] as $u ) {
			$rows[] = [
				'product_id'  => $u['product_id'],
				'units_added' => $u['units_added'],
				'oversold'    => $u['oversold'],
			];
		}
		WP_CLI\Utils\format_items( 'table', $rows, [ 'product_id', 'units_added', 'oversold' ] );

		if ( $preview['warnings'] ) {
			WP_CLI::log( '' );
			foreach ( $preview['warnings'] as $w ) {
				WP_CLI::warning( $w );
			}
		}
	}

	/**
	 * Release a shipment.
	 *
	 * ## OPTIONS
	 *
	 * <id>
	 * : The shipment ID.
	 *
	 * [--dry-run]
	 * : Compute the result but don't actually release.
	 *
	 * [--yes]
	 * : Skip the confirmation prompt.
	 *
	 * ## EXAMPLES
	 *
	 *     wp wpi release 7 --dry-run
	 *     wp wpi release 7 --yes
	 */
	public function release( $args, $assoc ): void {
		$id      = (int) ( $args[0] ?? 0 );
		$dry_run = (bool) ( $assoc['dry-run'] ?? false );

		$shipment = WPI_Shipment::get( $id );
		if ( ! $shipment ) {
			WP_CLI::error( sprintf( 'Shipment #%d not found.', $id ) );
			return;
		}

		if ( ! $dry_run ) {
			WP_CLI::confirm( sprintf( 'Release shipment %s (#%d)?', $shipment->reference, $id ), $assoc );
		}

		$result = WPI_Release::release_shipment( $id, $dry_run );

		WP_CLI::log( sprintf( '%s: %d order(s).', $dry_run ? 'Would release' : 'Released', (int) $result['released'] ) );
		foreach ( $result['stock_updates'] as $u ) {
			WP_CLI::log( sprintf( '  Product #%d: +%d units' . ( $u['oversold'] ? " (OVERSOLD by {$u['oversold']})" : '' ), $u['product_id'], $u['units_added'] ) );
		}
		foreach ( $result['warnings'] as $w ) {
			WP_CLI::warning( $w );
		}

		if ( $dry_run ) {
			WP_CLI::success( 'Dry run complete — no changes written.' );
		} else {
			WP_CLI::success( sprintf( 'Shipment %s released.', $shipment->reference ) );
		}
	}
}

WP_CLI::add_command( 'wpi', 'WPI_CLI' );
