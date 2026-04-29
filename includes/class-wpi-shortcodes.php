<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WPI_Shortcodes {

	public function __construct() {
		// Original spec shortcodes.
		add_shortcode( 'wpi_preorder_availability', [ $this, 'availability' ] );
		add_shortcode( 'wpi_preorder_eta',           [ $this, 'eta' ] );

		// Elementor / page-builder friendly atomic shortcodes.
		add_shortcode( 'preorder_status', [ $this, 'status' ] );
		add_shortcode( 'preorder_stock',  [ $this, 'stock' ] );
		add_shortcode( 'preorder_date',   [ $this, 'date' ] );
	}

	// -------------------------------------------------------------------------
	// Helpers
	// -------------------------------------------------------------------------

	private function resolve_product_id( array $atts ): int {
		$product_id = (int) ( $atts['product_id'] ?? 0 );
		if ( $product_id ) {
			return $product_id;
		}
		global $product;
		if ( $product instanceof WC_Product ) {
			return $product->get_id();
		}
		return 0;
	}

	// -------------------------------------------------------------------------
	// Atomic shortcodes (designed to drop into Elementor templates / Text widgets)
	// -------------------------------------------------------------------------

	/**
	 * [preorder_status] — short status label.
	 * Output: "Pre-orders open", "Pre-order back-ordered", "Out of stock", or empty.
	 */
	public function status( array $atts ): string {
		$atts       = shortcode_atts( [ 'product_id' => 0, 'wrap' => 'yes' ], $atts, 'preorder_status' );
		$product_id = $this->resolve_product_id( $atts );
		if ( ! $product_id ) {
			return '';
		}

		$items = WPI_Shipment_Item::active_for_product( $product_id );
		if ( ! $items ) {
			return '';
		}

		$any_remaining = false;
		foreach ( $items as $item ) {
			if ( $item->qty_remaining() > 0 ) {
				$any_remaining = true;
				break;
			}
		}

		$label = $any_remaining
			? __( 'Pre-orders open', 'wpi' )
			: __( 'Pre-order back-ordered', 'wpi' );

		$label = apply_filters( 'wpi_preorder_status_label', $label, $product_id, $items );

		if ( 'no' === $atts['wrap'] ) {
			return esc_html( $label );
		}
		$class = $any_remaining ? 'wpi-status wpi-status--open' : 'wpi-status wpi-status--backorder';
		return sprintf( '<span class="%s">%s</span>', esc_attr( $class ), esc_html( $label ) );
	}

	/**
	 * [preorder_stock] — combined stock breakdown.
	 * Always shows real-time WC stock alongside preorder allocation, so customers see the
	 * full picture and aren't discouraged by a "no stock" message when goods are inbound.
	 *
	 * Default output (text):
	 *   "5 in stock now · 12 inbound 22/06 (3 already pre-ordered)"
	 *
	 * Use format="numbers" to get just the figures, or format="long" for a multi-line breakdown.
	 */
	public function stock( array $atts ): string {
		$atts = shortcode_atts( [
			'product_id' => 0,
			'format'     => 'text', // text | numbers | long
		], $atts, 'preorder_stock' );

		$product_id = $this->resolve_product_id( $atts );
		if ( ! $product_id ) {
			return '';
		}

		$product = wc_get_product( $product_id );
		if ( ! $product ) {
			return '';
		}

		$wc_stock = $product->managing_stock() ? max( 0, (int) $product->get_stock_quantity() ) : null;

		$items   = WPI_Shipment_Item::active_for_product( $product_id );
		$primary = null;
		foreach ( $items as $item ) {
			if ( $item->qty_allocated > 0 ) {
				$primary = $item;
				break;
			}
		}

		// No preorder activity at all — fall through to default WC display.
		if ( ! $primary && $wc_stock === null ) {
			return '';
		}

		$fmt           = get_option( 'wpi_date_format', 'd/m/Y' );
		$arrival       = $primary ? date_i18n( $fmt, strtotime( $primary->release_date() ) ) : '';
		$preorder_left = $primary ? max( 0, $primary->qty_remaining() ) : 0;
		$preordered    = $primary ? $primary->qty_preordered : 0;
		$allocated     = $primary ? $primary->qty_allocated : 0;

		if ( 'numbers' === $atts['format'] ) {
			return sprintf(
				'<span class="wpi-stock"><span class="wpi-stock__current">%s</span> · <span class="wpi-stock__inbound">%d</span></span>',
				$wc_stock !== null ? (int) $wc_stock : '–',
				(int) $preorder_left
			);
		}

		if ( 'long' === $atts['format'] ) {
			$lines = [];
			if ( $wc_stock !== null ) {
				$lines[] = sprintf(
					/* translators: %d: in-stock units */
					_n( '%d unit currently in stock.', '%d units currently in stock.', $wc_stock, 'wpi' ),
					$wc_stock
				);
			}
			if ( $primary ) {
				$lines[] = sprintf(
					/* translators: 1: allocated, 2: arrival date */
					_n(
						'%1$d unit in next shipment, arriving %2$s.',
						'%1$d units in next shipment, arriving %2$s.',
						$allocated,
						'wpi'
					),
					$allocated,
					$arrival
				);
				if ( $preordered > 0 ) {
					$lines[] = sprintf(
						/* translators: %d: preordered count */
						_n( '%d already reserved by other customers.', '%d already reserved by other customers.', $preordered, 'wpi' ),
						$preordered
					);
				}
			}
			return '<div class="wpi-stock wpi-stock--long">' . esc_html( implode( ' ', $lines ) ) . '</div>';
		}

		// Default: single-line text format.
		$bits = [];
		if ( $wc_stock !== null ) {
			$bits[] = sprintf(
				/* translators: %d: in-stock count */
				_n( '%d in stock now', '%d in stock now', $wc_stock, 'wpi' ),
				$wc_stock
			);
		}
		if ( $primary ) {
			$bits[] = sprintf(
				/* translators: 1: remaining preorder qty, 2: arrival date */
				_n(
					'%1$d available for pre-order (arrives %2$s)',
					'%1$d available for pre-order (arrives %2$s)',
					$preorder_left,
					'wpi'
				),
				$preorder_left,
				$arrival
			);
			if ( $preordered > 0 ) {
				$bits[] = sprintf(
					/* translators: %d: preordered count */
					_n( '%d already pre-ordered', '%d already pre-ordered', $preordered, 'wpi' ),
					$preordered
				);
			}
		}

		return '<span class="wpi-stock">' . esc_html( implode( ' · ', $bits ) ) . '</span>';
	}

	/**
	 * [preorder_date] — earliest expected arrival date for the next active shipment.
	 * Empty output when no preorder is active (lets template builders hide the row entirely).
	 */
	public function date( array $atts ): string {
		$atts = shortcode_atts( [
			'product_id' => 0,
			'format'     => '',
			'prefix'     => '',
		], $atts, 'preorder_date' );

		$product_id = $this->resolve_product_id( $atts );
		if ( ! $product_id ) {
			return '';
		}

		$item = WPI_Shipment_Item::earliest_available_for_product( $product_id );
		if ( ! $item ) {
			return '';
		}

		$format = $atts['format'] ?: get_option( 'wpi_date_format', 'd/m/Y' );
		$date   = date_i18n( $format, strtotime( $item->release_date() ) );
		$prefix = $atts['prefix'] ? esc_html( $atts['prefix'] ) . ' ' : '';

		return '<span class="wpi-date">' . $prefix . esc_html( $date ) . '</span>';
	}

	// -------------------------------------------------------------------------
	// Original spec shortcodes (kept for backwards compatibility)
	// -------------------------------------------------------------------------

	public function availability( array $atts ): string {
		$atts       = shortcode_atts( [ 'product_id' => 0 ], $atts, 'wpi_preorder_availability' );
		$product_id = $this->resolve_product_id( $atts );
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
		$atts       = shortcode_atts( [ 'product_id' => 0 ], $atts, 'wpi_preorder_eta' );
		$product_id = $this->resolve_product_id( $atts );
		if ( ! $product_id ) {
			return '';
		}

		$item = WPI_Shipment_Item::earliest_available_for_product( $product_id );
		if ( ! $item ) {
			return '';
		}

		$fmt   = get_option( 'wpi_date_format', 'd/m/Y' );
		$date  = date_i18n( $fmt, strtotime( $item->release_date() ) );
		$label = apply_filters(
			'wpi_preorder_eta_label',
			/* translators: %s: formatted release date */
			sprintf( __( 'Stock arrival expected %s', 'wpi' ), $date ),
			$item->release_date(),
			$item
		);
		return '<span class="wpi-eta">' . esc_html( $label ) . '</span>';
	}

	/** @param WPI_Shipment_Item[] $items */
	private function build_availability_text( array $items ): string {
		$fmt     = get_option( 'wpi_date_format', 'd/m/Y' );
		$primary = null;
		$next    = null;

		foreach ( $items as $item ) {
			if ( $item->qty_remaining() > 0 ) {
				if ( ! $primary ) {
					$primary = $item;
				} elseif ( ! $next ) {
					$next = $item;
					break;
				}
			} else {
				if ( ! $next && $primary ) {
					$next = $item;
				}
			}
		}

		if ( ! $primary && ! $next ) {
			return __( 'Pre-orders not currently available.', 'wpi' );
		}

		if ( $primary ) {
			$remaining = $primary->qty_remaining();
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
					/* translators: 1: qty, 2: arrival date */
					_n(
						'%1$d additional unit arriving %2$s — unless sold out earlier.',
						'%1$d additional units arriving %2$s — unless sold out earlier.',
						$next->qty_allocated,
						'wpi'
					),
					$next->qty_allocated,
					date_i18n( $fmt, strtotime( $next->release_date() ) )
				);
			}
			return $line;
		}

		// Primary sold out, next exists.
		return sprintf(
			/* translators: 1: arrival date, 2: qty expected */
			_n(
				'Sold out — next shipment arriving %1$s, %2$d unit expected.',
				'Sold out — next shipment arriving %1$s, %2$d units expected.',
				$next->qty_allocated,
				'wpi'
			),
			date_i18n( $fmt, strtotime( $next->release_date() ) ),
			$next->qty_allocated
		);
	}
}
