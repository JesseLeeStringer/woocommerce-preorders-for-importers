<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WPI_Frontend {

	public function __construct() {
		add_filter( 'woocommerce_product_single_add_to_cart_text', [ $this, 'button_label' ], 10, 2 );
		add_filter( 'woocommerce_product_add_to_cart_text', [ $this, 'button_label' ], 10, 2 );
		add_action( 'woocommerce_after_add_to_cart_button', [ $this, 'product_page_eta' ] );
		add_action( 'woocommerce_after_shop_loop_item_title', [ $this, 'loop_badge' ], 11 );
		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue' ] );
	}

	public function button_label( string $label, WC_Product $product ): string {
		$item = WPI_Shipment_Item::earliest_available_for_product( $product->get_id() );
		if ( ! $item ) {
			return $label;
		}

		$deposit_formatted = wc_price( $item->deposit_amount() );
		$default = sprintf(
			/* translators: %s: formatted deposit amount */
			__( 'Pre-Order — Secure with %s deposit', 'wpi' ),
			$deposit_formatted
		);

		return apply_filters( 'wpi_preorder_button_label', $default, $product, $item );
	}

	public function product_page_eta(): void {
		global $product;
		if ( ! $product ) {
			return;
		}
		$item = WPI_Shipment_Item::earliest_available_for_product( $product->get_id() );
		if ( ! $item ) {
			return;
		}

		$fmt    = get_option( 'wpi_date_format', 'd/m/Y' );
		$date   = date_i18n( $fmt, strtotime( $item->release_date() ) );
		$label  = apply_filters(
			'wpi_preorder_eta_label',
			sprintf( __( 'Stock arrival expected %s', 'wpi' ), $date ),
			$item->release_date(),
			$item
		);

		echo '<p class="wpi-preorder-eta">' . esc_html( $label ) . '</p>';
	}

	public function loop_badge(): void {
		global $product;
		if ( ! $product ) {
			return;
		}
		$items = WPI_Shipment_Item::active_for_product( $product->get_id() );
		if ( ! $items ) {
			return;
		}
		$badge = apply_filters( 'wpi_preorder_badge_label', get_option( 'wpi_badge_text', __( 'Pre-Order', 'wpi' ) ), $product );
		echo '<span class="wpi-badge">' . esc_html( $badge ) . '</span>';
	}

	public function enqueue(): void {
		wp_enqueue_style(
			'wpi-frontend',
			WPI_PLUGIN_URL . 'assets/css/frontend.css',
			[],
			WPI_VERSION
		);
	}
}
