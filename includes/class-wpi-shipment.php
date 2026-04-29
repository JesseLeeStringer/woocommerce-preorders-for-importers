<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WPI_Shipment {

	public int $id;
	public string $reference;
	public string $due_date;
	public string $status;
	public string $notes;
	public ?int $released_by;
	public ?string $released_at;

	public function __construct( object $row ) {
		$this->id          = (int) $row->id;
		$this->reference   = $row->reference;
		$this->due_date    = $row->due_date;
		$this->status      = $row->status;
		$this->notes       = (string) $row->notes;
		$this->released_by = isset( $row->released_by ) && $row->released_by !== null ? (int) $row->released_by : null;
		$this->released_at = isset( $row->released_at ) ? $row->released_at : null;
	}

	// -------------------------------------------------------------------------
	// Static queries
	// -------------------------------------------------------------------------

	public static function get( int $id ): ?self {
		global $wpdb;
		$row = $wpdb->get_row( $wpdb->prepare(
			"SELECT * FROM {$wpdb->prefix}wpi_shipments WHERE id = %d",
			$id
		) );
		return $row ? new self( $row ) : null;
	}

	/** @return self[] */
	public static function get_all( string $status = '' ): array {
		global $wpdb;
		if ( $status ) {
			$rows = $wpdb->get_results( $wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}wpi_shipments WHERE status = %s ORDER BY due_date ASC",
				$status
			) );
		} else {
			$rows = $wpdb->get_results(
				"SELECT * FROM {$wpdb->prefix}wpi_shipments ORDER BY due_date ASC"
			);
		}
		return array_map( fn( $r ) => new self( $r ), $rows );
	}

	public static function insert( array $data ): int {
		global $wpdb;
		$wpdb->insert(
			$wpdb->prefix . 'wpi_shipments',
			[
				'reference' => sanitize_text_field( $data['reference'] ),
				'due_date'  => $data['due_date'],
				'status'    => $data['status'] ?? 'draft',
				'notes'     => sanitize_textarea_field( $data['notes'] ?? '' ),
			],
			[ '%s', '%s', '%s', '%s' ]
		);
		return (int) $wpdb->insert_id;
	}

	public static function update( int $id, array $data ): void {
		global $wpdb;
		$fields  = [];
		$formats = [];

		$map = [
			'reference'   => '%s',
			'due_date'    => '%s',
			'status'      => '%s',
			'notes'       => '%s',
			'released_by' => '%d',
			'released_at' => '%s',
		];

		foreach ( $map as $key => $format ) {
			if ( ! array_key_exists( $key, $data ) ) {
				continue;
			}
			$value = $data[ $key ];
			if ( in_array( $key, [ 'reference', 'notes' ], true ) ) {
				$value = $key === 'notes' ? sanitize_textarea_field( $value ) : sanitize_text_field( $value );
			}
			$fields[ $key ] = $value;
			$formats[]      = $format;
		}

		if ( $fields ) {
			$wpdb->update( $wpdb->prefix . 'wpi_shipments', $fields, [ 'id' => $id ], $formats, [ '%d' ] );
			if ( array_key_exists( 'status', $fields ) ) {
				WPI_Shipment_Item::flush_active_cache();
			}
		}
	}

	public static function set_status( int $id, string $status ): void {
		self::update( $id, [ 'status' => $status ] );
	}

	/**
	 * Mark a shipment as released, recording who and when.
	 */
	public static function record_release( int $id, int $user_id ): void {
		self::update( $id, [
			'status'      => 'arrived',
			'released_by' => $user_id,
			'released_at' => current_time( 'mysql' ),
		] );
	}

	/**
	 * Delete a shipment and cascade-clean dependent rows.
	 * Stock log entries are preserved but their shipment_id is nulled so audit history survives.
	 */
	public static function delete( int $id ): void {
		global $wpdb;
		WPI_Shipment_Item::delete_for_shipment( $id );
		WPI_Stock_Log::nullify_shipment( $id );
		$wpdb->delete( $wpdb->prefix . 'wpi_shipments', [ 'id' => $id ], [ '%d' ] );
	}
}
