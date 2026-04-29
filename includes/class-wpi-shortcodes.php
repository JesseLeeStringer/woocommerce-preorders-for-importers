<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WPI_Shortcodes {

	public function __construct() {
		add_shortcode( 'wpi_preorder_availability', [ $this, 'availability' ] );
		add_shortcode( 'wpi_preorder_eta', [ $this, 'eta' ] );
	}

	public function availability( array $atts ): string {
		$atts = shortcode_atts( [ 'product_id' => 0 ], $atts, 'wpi_preorder_availability' );
		$product_id = (int) $atts['product_id'];
		if ( ! $product_id ) {
			return '';
		}

		$items = WPI_Shipment_Item::active_for_product( $product_id );
		if ( ! $items ) {
			return '<span class="wpi-availability wpi-availability--none">' . esc_html__( 'Pre-orders not currently available.', 'wpi' ) . '</span>';
		}

		$text = apply_filters( 'wpi_availability_text', $this->build_availability_text( $items ), $product_id, $items );
		return '<span class="wpi-availability">' . esc_html( $text ) . '</span>';
	}

	public function eta( array $atts ): string {
		$atts = shortcode_atts( [ 'product_id' => 0 ], $atts, 'wpi_preorder_eta' );
		$product_id = (int) $atts['product_id'];
		if ( ! $product_id ) {
			return '';
		}

		$item = WPI_Shipment_Item::earliest_available_for_product( $product_id );
		if ( ! $item ) {
			return '';
		}

		$fmt  = get_option( 'wpi_date_format', 'd/m/Y' );
		$date = date_i18n( $fmt, strtotime( $item->release_date() ) );
		$label = apply_filters(
			'wpi_preorder_eta_label',
			sprintf( __( 'Stock arrival expected %s', 'wpi' ), $date ),
			$item->release_date(),
			$item
		);
		return '<span class="wpi-eta">' . esc_html( $label ) . '</span>';
	}

	/** @param WPI_Shipment_Item[] $items */
	private function build_availability_text( array $items ): string {
		$fmt          = get_option( 'wpi_date_format', 'd/m/Y' );
		$primary      = null;
		$next         = null;

		foreach ( $items as $item ) {
			if ( $item->qty_preordered < $item->qty_allocated ) {
				if ( ! $primary ) {
					$primary = $item;
				} elseif ( ! $next ) {
					$next = $item;
					break;
				}
			} else {
				// Sold out — candidate for "next shipment" messaging.
				if ( ! $next && $primary ) {
					$next = $item;
				}
			}
		}

		if ( ! $primary && ! $next ) {
			return __( 'Pre-orders not currently available.', 'wpi' );
		}

		if ( $primary ) {
			$remaining = $primary->qty_allocated - $primary->qty_preordered;
			$sold      = $primary->qty_preordered;
			$line      = sprintf(
				/* translators: 1: remaining qty, 2: sold qty */
				_n(
					'%1$d unit available for pre-order — %2$d already sold.',
					'%1$d units available for pre-order — %2$d already sold.',
					$remaining,
					'wpi'
				),
				$remaining,
				$sold
			);
			if ( $next ) {
				$line .= ' ' . sprintf(
					__( '%d additional units arriving %s — unless sold out earlier.', 'wpi' ),
					$next->qty_allocated,
					date_i18n( $fmt, strtotime( $next->release_date() ) )
				);
			}
			return $line;
		}

		// Primary sold out, next exists.
		return sprintf(
			__( 'Sold out — next shipment arriving %s, %d units expected.', 'wpi' ),
			date_i18n( $fmt, strtotime( $next->release_date() ) ),
			$next->qty_allocated
		);
	}
}
