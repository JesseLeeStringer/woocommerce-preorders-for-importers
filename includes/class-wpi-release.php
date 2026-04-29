<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WPI_Release {

	/**
	 * Compute what a release would do without actually doing it.
	 * Used by the admin confirmation modal and by the WP-CLI dry-run.
	 *
	 * @return array{
	 *   stock_updates: array<int, array{product_id:int, units_added:int, oversold:int}>,
	 *   order_count: int,
	 *   warnings: string[]
	 * }
	 */
	public static function preview_release( int $shipment_id ): array {
		$shipment = WPI_Shipment::get( $shipment_id );
		if ( ! $shipment ) {
			return [ 'stock_updates' => [], 'order_count' => 0, 'warnings' => [] ];
		}

		$items         = WPI_Shipment_Item::for_shipment( $shipment_id );
		$stock_updates = [];
		$warnings      = [];

		foreach ( $items as $item ) {
			$units_to_add = $item->qty_allocated - $item->qty_preordered;
			$oversold     = 0;

			if ( $units_to_add < 0 ) {
				$oversold = abs( $units_to_add );
				$warnings[] = sprintf(
					/* translators: 1: product id, 2: preorder qty, 3: allocated qty */
					__( 'Product #%1$d has %2$d preorders but only %3$d units allocated. The shortfall (%4$d) will need manual resolution.', 'wpi' ),
					$item->product_id,
					$item->qty_preordered,
					$item->qty_allocated,
					$oversold
				);
				$units_to_add = 0;
			}

			$stock_updates[] = [
				'product_id'  => $item->product_id,
				'units_added' => max( 0, $units_to_add ),
				'oversold'    => $oversold,
			];
		}

		$orders      = self::get_orders_for_shipment( $shipment_id );
		$order_count = count( $orders );

		return compact( 'stock_updates', 'order_count', 'warnings' );
	}

	/**
	 * Release a shipment: update stock, move orders to processing, send emails.
	 *
	 * @return array{released: int, stock_updates: array, warnings: array}
	 */
	public static function release_shipment( int $shipment_id, bool $dry_run = false ): array {
		$shipment = WPI_Shipment::get( $shipment_id );
		if ( ! $shipment ) {
			return [ 'released' => 0, 'stock_updates' => [], 'warnings' => [] ];
		}

		$preview = self::preview_release( $shipment_id );
		if ( $dry_run ) {
			return [
				'released'      => $preview['order_count'],
				'stock_updates' => $preview['stock_updates'],
				'warnings'      => $preview['warnings'],
				'dry_run'       => true,
			];
		}

		$items    = WPI_Shipment_Item::for_shipment( $shipment_id );
		$released = 0;

		// 1. Update WooCommerce stock per product.
		foreach ( $items as $item ) {
			$units_to_add = max( 0, $item->qty_allocated - $item->qty_preordered );

			if ( $units_to_add > 0 ) {
				wc_update_product_stock( $item->product_id, $units_to_add, 'increase' );
			}

			WPI_Stock_Log::write(
				$item->product_id,
				WPI_Stock_Log::ACTION_SHIPMENT_RELEASED,
				$units_to_add,
				$shipment_id,
				sprintf(
					/* translators: 1: shipment ref, 2: allocated, 3: preordered, 4: added */
					__( 'Shipment %1$s released. Allocated: %2$d, Preordered: %3$d, Added to stock: %4$d', 'wpi' ),
					$shipment->reference,
					$item->qty_allocated,
					$item->qty_preordered,
					$units_to_add
				)
			);
		}

		// 2. Release orders tied to this shipment.
		$orders = self::get_orders_for_shipment( $shipment_id );
		foreach ( $orders as $order ) {
			$order->update_status( 'processing', __( 'Preorder released — order moved to processing.', 'wpi' ) );
			$order->update_meta_data( '_wpi_preorder_released', '1' );
			$order->save();

			self::send_released_email( $order );
			$released++;
		}

		// 3. Mark shipment arrived and stamp the audit columns.
		WPI_Shipment::record_release( $shipment_id, get_current_user_id() );

		do_action(
			'wpi_shipment_released',
			$shipment_id,
			array_map( fn( $o ) => $o->get_id(), $orders ),
			$preview['stock_updates']
		);

		return [
			'released'      => $released,
			'stock_updates' => $preview['stock_updates'],
			'warnings'      => $preview['warnings'],
		];
	}

	private static function get_orders_for_shipment( int $shipment_id ): array {
		global $wpdb;

		// Order-item meta still lives in wp_woocommerce_order_itemmeta under HPOS — that table is unaffected.
		$order_ids = $wpdb->get_col( $wpdb->prepare(
			"SELECT DISTINCT oi.order_id
			   FROM {$wpdb->prefix}woocommerce_order_items oi
			   JOIN {$wpdb->prefix}woocommerce_order_itemmeta oim ON oim.order_item_id = oi.order_item_id
			  WHERE oim.meta_key = '_wpi_shipment_id'
			    AND oim.meta_value = %d",
			$shipment_id
		) );

		if ( ! $order_ids ) {
			return [];
		}

		// Use HPOS-friendly `include` rather than `post__in` so this works under custom_order_tables.
		return wc_get_orders( [
			'include' => array_map( 'intval', $order_ids ),
			'status'  => [ 'preorder' ],
			'limit'   => -1,
			'return'  => 'objects',
		] );
	}

	private static function send_released_email( WC_Order $order ): void {
		$mailer = WC()->mailer();
		$emails = $mailer->get_emails();
		if ( isset( $emails['WPI_Email_Preorder_Released'] ) ) {
			$emails['WPI_Email_Preorder_Released']->trigger( $order->get_id(), $order );
		}
	}
}
