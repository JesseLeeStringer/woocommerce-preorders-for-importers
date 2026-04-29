<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WPI_Shipment_Item {

	public int $id;
	public int $shipment_id;
	public int $product_id;
	public float $shipment_price;
	public int $qty_allocated;
	public int $qty_preordered;
	public string $deposit_type;
	public float $deposit_value;
	public int $release_offset_days;
	public bool $allow_customer_choice;

	public function __construct( object $row ) {
		$this->id                   = (int) $row->id;
		$this->shipment_id          = (int) $row->shipment_id;
		$this->product_id           = (int) $row->product_id;
		$this->shipment_price       = (float) $row->shipment_price;
		$this->qty_allocated        = (int) $row->qty_allocated;
		$this->qty_preordered       = (int) $row->qty_preordered;
		$this->deposit_type         = $row->deposit_type;
		$this->deposit_value        = (float) $row->deposit_value;
		$this->release_offset_days  = (int) $row->release_offset_days;
		$this->allow_customer_choice = (bool) $row->allow_customer_choice;
	}

	// -------------------------------------------------------------------------
	// Derived values
	// -------------------------------------------------------------------------

	public function deposit_amount(): float {
		if ( 'percent' === $this->deposit_type ) {
			return round( $this->shipment_price * ( $this->deposit_value / 100 ), 2 );
		}
		return round( $this->deposit_value, 2 );
	}

	public function balance_due(): float {
		return round( $this->shipment_price - $this->deposit_amount(), 2 );
	}

	public function release_date(): string {
		$shipment = WPI_Shipment::get( $this->shipment_id );
		if ( ! $shipment ) {
			return '';
		}
		$date = new DateTime( $shipment->due_date );
		if ( $this->release_offset_days > 0 ) {
			$date->modify( "+{$this->release_offset_days} days" );
		}
		return $date->format( 'Y-m-d' );
	}

	public function qty_remaining(): int {
		return $this->qty_allocated - $this->qty_preordered;
	}

	// -------------------------------------------------------------------------
	// Static queries
	// -------------------------------------------------------------------------

	public static function get( int $id ): ?self {
		global $wpdb;
		$row = $wpdb->get_row( $wpdb->prepare(
			"SELECT * FROM {$wpdb->prefix}wpi_shipment_items WHERE id = %d",
			$id
		) );
		return $row ? new self( $row ) : null;
	}

	/** @return self[] */
	public static function for_shipment( int $shipment_id ): array {
		global $wpdb;
		$rows = $wpdb->get_results( $wpdb->prepare(
			"SELECT * FROM {$wpdb->prefix}wpi_shipment_items WHERE shipment_id = %d",
			$shipment_id
		) );
		return array_map( fn( $r ) => new self( $r ), $rows );
	}

	/**
	 * Returns active shipment items for a product, ordered by shipment due date ascending.
	 * Used to find the correct item to debit when a preorder is placed.
	 *
	 * @return self[]
	 */
	public static function active_for_product( int $product_id ): array {
		global $wpdb;
		$rows = $wpdb->get_results( $wpdb->prepare(
			"SELECT si.*
			   FROM {$wpdb->prefix}wpi_shipment_items si
			   JOIN {$wpdb->prefix}wpi_shipments s ON s.id = si.shipment_id
			  WHERE si.product_id = %d
			    AND s.status = 'active'
			  ORDER BY s.due_date ASC, si.release_offset_days ASC",
			$product_id
		) );
		return array_map( fn( $r ) => new self( $r ), $rows );
	}

	/**
	 * Returns the item the customer should preorder from by default:
	 * the earliest active shipment. Once sold out, cascades to the next.
	 */
	public static function earliest_available_for_product( int $product_id ): ?self {
		$items = self::active_for_product( $product_id );
		foreach ( $items as $item ) {
			// Negative qty_remaining is allowed (oversell) — any active item qualifies.
			// Return the first (earliest) unless the shipment has no allocated qty at all.
			if ( $item->qty_allocated > 0 && $item->qty_preordered < $item->qty_allocated ) {
				return $item;
			}
		}
		// All sold out — return next available for display purposes.
		foreach ( $items as $item ) {
			if ( $item->qty_allocated > 0 && $item->qty_preordered >= $item->qty_allocated ) {
				return $item; // sold out but next in line
			}
		}
		return null;
	}

	public static function insert( int $shipment_id, array $data ): int {
		global $wpdb;
		$wpdb->insert(
			$wpdb->prefix . 'wpi_shipment_items',
			[
				'shipment_id'          => $shipment_id,
				'product_id'           => (int) $data['product_id'],
				'shipment_price'       => (float) $data['shipment_price'],
				'qty_allocated'        => (int) $data['qty_allocated'],
				'deposit_type'         => in_array( $data['deposit_type'], [ 'percent', 'fixed' ], true ) ? $data['deposit_type'] : 'percent',
				'deposit_value'        => (float) $data['deposit_value'],
				'release_offset_days'  => (int) ( $data['release_offset_days'] ?? 0 ),
				'allow_customer_choice' => (int) ( $data['allow_customer_choice'] ?? 0 ),
			],
			[ '%d', '%d', '%f', '%d', '%s', '%f', '%d', '%d' ]
		);
		return (int) $wpdb->insert_id;
	}

	public static function increment_preordered( int $item_id, int $qty = 1 ): void {
		global $wpdb;
		$wpdb->query( $wpdb->prepare(
			"UPDATE {$wpdb->prefix}wpi_shipment_items SET qty_preordered = qty_preordered + %d WHERE id = %d",
			$qty,
			$item_id
		) );
	}

	public static function decrement_preordered( int $item_id, int $qty = 1 ): void {
		global $wpdb;
		$wpdb->query( $wpdb->prepare(
			"UPDATE {$wpdb->prefix}wpi_shipment_items SET qty_preordered = GREATEST(0, qty_preordered - %d) WHERE id = %d",
			$qty,
			$item_id
		) );
	}

	public static function delete_for_shipment( int $shipment_id ): void {
		global $wpdb;
		$wpdb->delete( $wpdb->prefix . 'wpi_shipment_items', [ 'shipment_id' => $shipment_id ], [ '%d' ] );
	}
}
