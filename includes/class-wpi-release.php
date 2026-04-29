<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WPI_Release {

	/**
	 * Release a shipment: update stock, move orders to processing, send emails.
	 *
	 * @return array{released: int, stock_updates: array, warnings: array}
	 */
	public static function release_shipment( int $shipment_id ): array {
		$shipment = WPI_Shipment::get( $shipment_id );
		if ( ! $shipment ) {
			return [ 'released' => 0, 'stock_updates' => [], 'warnings' => [] ];
		}

		$items        = WPI_Shipment_Item::for_shipment( $shipment_id );
		$released     = 0;
		$stock_updates = [];
		$warnings     = [];

		// 1. Update WooCommerce stock per product.
		foreach ( $items as $item ) {
			$units_to_add = $item->qty_allocated - $item->qty_preordered;

			if ( $units_to_add < 0 ) {
				$warnings[] = sprintf(
					__( 'Product #%d has %d preorders but only %d units allocated. Manually resolve before finalising.', 'wpi' ),
					$item->product_id,
					$item->qty_preordered,
					$item->qty_allocated
				);
				$units_to_add = 0;
			}

			if ( $units_to_add > 0 ) {
				wc_update_product_stock( $item->product_id, $units_to_add, 'increase' );
			}

			WPI_Stock_Log::write(
				$item->product_id,
				WPI_Stock_Log::ACTION_SHIPMENT_RELEASED,
				$units_to_add,
				$shipment_id,
				sprintf( 'Shipment %s released. Allocated: %d, Preordered: %d, Added to stock: %d', $shipment->reference, $item->qty_allocated, $item->qty_preordered, $units_to_add )
			);

			$stock_updates[] = [
				'product_id'  => $item->product_id,
				'units_added' => $units_to_add,
			];
		}

		// 2. Release orders tied to this shipment.
		$orders = self::get_orders_for_shipment( $shipment_id );
		foreach ( $orders as $order ) {
			do_action( 'wpi_release_excluded_orders', false, $order->get_id(), $shipment_id );

			$order->update_status( 'processing', __( 'Preorder released — order moved to processing.', 'wpi' ) );
			$order->update_meta_data( '_wpi_preorder_released', '1' );
			$order->save();

			self::send_released_email( $order );
			$released++;
		}

		// 3. Mark shipment arrived.
		WPI_Shipment::set_status( $shipment_id, 'arrived' );

		do_action( 'wpi_shipment_released', $shipment_id, array_map( fn( $o ) => $o->get_id(), $orders ), $stock_updates );

		return compact( 'released', 'stock_updates', 'warnings' );
	}

	private static function get_orders_for_shipment( int $shipment_id ): array {
		// Find orders containing items tied to this shipment.
		global $wpdb;

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

		return wc_get_orders( [
			'post__in' => array_map( 'intval', $order_ids ),
			'status'   => [ 'preorder' ],
			'limit'    => -1,
			'return'   => 'objects',
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
