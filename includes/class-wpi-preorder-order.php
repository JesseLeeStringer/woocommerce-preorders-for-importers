<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WPI_Preorder_Order {

	const STATUS = 'wc-preorder';

	public function __construct() {
		add_action( 'init', [ $this, 'register_status' ] );
		add_filter( 'wc_order_statuses', [ $this, 'add_to_order_statuses' ] );

		// Set order to preorder status once payment is confirmed.
		add_action( 'woocommerce_payment_complete', [ $this, 'maybe_set_preorder_status' ] );
		add_action( 'woocommerce_order_status_processing', [ $this, 'maybe_set_preorder_status' ] );

		// Record preorder qty and log when a preorder order is created.
		add_action( 'wpi_order_set_preorder', [ $this, 'on_preorder_created' ] );

		// Return preorder qty on cancellation.
		add_action( 'woocommerce_order_status_cancelled', [ $this, 'on_order_cancelled' ] );

		// Add freight note and block WC from auto-completing preorder orders.
		add_filter( 'woocommerce_payment_complete_order_status', [ $this, 'keep_preorder_status_on_payment' ], 10, 3 );

		// Prevent WC from auto-cancelling pending preorder orders.
		add_filter( 'woocommerce_cancel_unpaid_order', [ $this, 'prevent_pending_cancel' ], 10, 2 );
	}

	public function register_status(): void {
		register_post_status(
			self::STATUS,
			[
				'label'                     => _x( 'Preorder', 'Order status', 'wpi' ),
				'public'                    => true,
				'exclude_from_search'       => false,
				'show_in_admin_all_list'    => true,
				'show_in_admin_status_list' => true,
				'label_count'               => _n_noop(
					'Preorder <span class="count">(%s)</span>',
					'Preorders <span class="count">(%s)</span>',
					'wpi'
				),
			]
		);
	}

	public function add_to_order_statuses( array $statuses ): array {
		$statuses[ self::STATUS ] = _x( 'Preorder', 'Order status', 'wpi' );
		return $statuses;
	}

	public function keep_preorder_status_on_payment( string $status, int $order_id, WC_Order $order ): string {
		if ( $order->get_meta( '_wpi_has_preorder' ) ) {
			return 'preorder';
		}
		return $status;
	}

	public function maybe_set_preorder_status( int $order_id ): void {
		$order = wc_get_order( $order_id );
		if ( ! $order || $order->get_status() === 'preorder' ) {
			return;
		}

		$has_preorder = false;
		foreach ( $order->get_items() as $item ) {
			if ( $item->get_meta( '_wpi_shipment_item_id' ) ) {
				$has_preorder = true;
				break;
			}
		}

		if ( ! $has_preorder ) {
			return;
		}

		$order->update_meta_data( '_wpi_has_preorder', '1' );
		$order->save();
		$order->update_status( 'preorder' );

		do_action( 'wpi_order_set_preorder', $order );
	}

	public function on_preorder_created( WC_Order $order ): void {
		foreach ( $order->get_items() as $item ) {
			$shipment_item_id = (int) $item->get_meta( '_wpi_shipment_item_id' );
			if ( ! $shipment_item_id ) {
				continue;
			}

			$shipment_item = WPI_Shipment_Item::get( $shipment_item_id );
			if ( ! $shipment_item ) {
				continue;
			}

			$qty = (int) $item->get_quantity();
			WPI_Shipment_Item::increment_preordered( $shipment_item_id, $qty );

			WPI_Stock_Log::write(
				$shipment_item->product_id,
				WPI_Stock_Log::ACTION_PREORDER_PLACED,
				-$qty,
				$shipment_item->shipment_id,
				sprintf( 'Order #%d', $order->get_id() )
			);

			// Add freight admin note.
			$shipping_total = (float) $order->get_shipping_total();
			if ( $shipping_total > 0 ) {
				$order->add_order_note( sprintf(
					/* translators: %s: formatted shipping amount */
					__( '⚠️ PREORDER: Shipping of %s charged at checkout. Confirm whether freight should be invoiced separately after goods arrive.', 'wpi' ),
					wc_price( $shipping_total )
				) );
			} else {
				$order->add_order_note(
					__( '⚠️ PREORDER: No shipping charged at checkout. Confirm freight arrangements with customer.', 'wpi' )
				);
			}
		}

		do_action( 'wpi_preorder_placed', $order->get_id() );
	}

	public function on_order_cancelled( int $order_id ): void {
		$order = wc_get_order( $order_id );
		if ( ! $order || ! $order->get_meta( '_wpi_has_preorder' ) ) {
			return;
		}
		if ( $order->get_meta( '_wpi_preorder_released' ) ) {
			return; // Already released — do not decrement.
		}

		foreach ( $order->get_items() as $item ) {
			$shipment_item_id = (int) $item->get_meta( '_wpi_shipment_item_id' );
			if ( ! $shipment_item_id ) {
				continue;
			}
			$shipment_item = WPI_Shipment_Item::get( $shipment_item_id );
			if ( ! $shipment_item ) {
				continue;
			}

			$qty = (int) $item->get_quantity();
			WPI_Shipment_Item::decrement_preordered( $shipment_item_id, $qty );

			WPI_Stock_Log::write(
				$shipment_item->product_id,
				WPI_Stock_Log::ACTION_PREORDER_CANCELLED,
				$qty,
				$shipment_item->shipment_id,
				sprintf( 'Order #%d cancelled', $order_id )
			);
		}

		do_action( 'wpi_preorder_cancelled', $order_id );
	}

	public function prevent_pending_cancel( bool $cancel, WC_Order $order ): bool {
		if ( $order->get_meta( '_wpi_has_preorder' ) ) {
			return false;
		}
		return $cancel;
	}

	// -------------------------------------------------------------------------
	// Helpers
	// -------------------------------------------------------------------------

	public static function get_preorder_orders_for_shipment( int $shipment_id ): array {
		return wc_get_orders( [
			'meta_key'     => '_wpi_has_preorder',
			'meta_value'   => '1',
			'meta_compare' => '=',
			'status'       => [ 'preorder' ],
			'limit'        => -1,
			'return'       => 'objects',
		] );
	}
}
