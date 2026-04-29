<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WPI_Shipment {

	/** @var int */
	public int $id;
	public string $reference;
	public string $due_date;
	public string $status;
	public string $notes;

	public function __construct( object $row ) {
		$this->id        = (int) $row->id;
		$this->reference = $row->reference;
		$this->due_date  = $row->due_date;
		$this->status    = $row->status;
		$this->notes     = (string) $row->notes;
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
		$fields = [];
		$formats = [];

		if ( isset( $data['reference'] ) ) {
			$fields['reference'] = sanitize_text_field( $data['reference'] );
			$formats[] = '%s';
		}
		if ( isset( $data['due_date'] ) ) {
			$fields['due_date'] = $data['due_date'];
			$formats[] = '%s';
		}
		if ( isset( $data['status'] ) ) {
			$fields['status'] = $data['status'];
			$formats[] = '%s';
		}
		if ( isset( $data['notes'] ) ) {
			$fields['notes'] = sanitize_textarea_field( $data['notes'] );
			$formats[] = '%s';
		}

		if ( $fields ) {
			$wpdb->update( $wpdb->prefix . 'wpi_shipments', $fields, [ 'id' => $id ], $formats, [ '%d' ] );
		}
	}

	public static function set_status( int $id, string $status ): void {
		self::update( $id, [ 'status' => $status ] );
	}
}
