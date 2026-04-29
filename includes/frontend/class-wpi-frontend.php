<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WPI_Frontend {

	public function __construct() {
		// Single product page: replace add-to-cart label with "Pre-Order — secure with $X deposit".
		add_filter( 'woocommerce_product_single_add_to_cart_text', [ $this, 'button_label' ], 10, 2 );

		// Archive (shop / category / loop): hard-replace the quick-add HTML with a permalink button.
		// Customers must land on the product page to see shipment terms before ordering — Shotgun's
		// average order value is high enough that a one-click archive add doesn't make sense.
		add_filter( 'woocommerce_loop_add_to_cart_link', [ $this, 'archive_preorder_link' ], 10, 2 );

		// Single product page extras.
		add_action( 'woocommerce_after_add_to_cart_button', [ $this, 'product_page_eta' ] );
		add_action( 'woocommerce_after_add_to_cart_button', [ $this, 'product_page_availability' ], 11 );

		// Loop badge alongside the archive button.
		add_action( 'woocommerce_after_shop_loop_item_title', [ $this, 'loop_badge' ], 11 );

		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue' ] );
	}

	/**
	 * Quick check using the cached active-product set so we don't hit the DB once per loop iteration.
	 */
	public function product_has_preorder( int $product_id ): bool {
		return in_array( $product_id, WPI_Shipment_Item::products_with_active_shipments(), true );
	}

	public function button_label( string $label, WC_Product $product ): string {
		if ( ! $this->product_has_preorder( $product->get_id() ) ) {
			return $label;
		}
		$item = WPI_Shipment_Item::earliest_available_for_product( $product->get_id() );
		if ( ! $item ) {
			return $label;
		}

		$deposit_formatted = wc_price( $item->deposit_amount() );
		$default = sprintf(
			/* translators: %s: formatted deposit amount */
			__( 'Pre-Order — Secure with %s deposit', 'wpi' ),
			wp_strip_all_tags( $deposit_formatted )
		);

		return apply_filters( 'wpi_preorder_button_label', $default, $product, $item );
	}

	/**
	 * Replace the loop add-to-cart anchor for preorder products with a permalink button.
	 */
	public function archive_preorder_link( string $html, WC_Product $product ): string {
		if ( ! $this->product_has_preorder( $product->get_id() ) ) {
			return $html;
		}
		$label = get_option( 'wpi_archive_button_text', __( 'Pre-Orders Available', 'wpi' ) );
		$label = apply_filters( 'wpi_archive_button_text', $label, $product );

		return sprintf(
			'<a href="%s" class="button wpi-archive-button" rel="nofollow">%s</a>',
			esc_url( $product->get_permalink() ),
			esc_html( $label )
		);
	}

	public function product_page_eta(): void {
		global $product;
		if ( ! $product || ! $this->product_has_preorder( $product->get_id() ) ) {
			return;
		}
		$item = WPI_Shipment_Item::earliest_available_for_product( $product->get_id() );
		if ( ! $item ) {
			return;
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

		echo '<p class="wpi-preorder-eta">' . esc_html( $label ) . '</p>';
	}

	/**
	 * Show how many units the customer can preorder vs. how many are already on the way.
	 * No hard cap — orders may exceed allocation; this is purely informational.
	 */
	public function product_page_availability(): void {
		global $product;
		if ( ! $product || ! $this->product_has_preorder( $product->get_id() ) ) {
			return;
		}
		echo do_shortcode( '[preorder_stock product_id="' . (int) $product->get_id() . '"]' );
	}

	public function loop_badge(): void {
		global $product;
		if ( ! $product || ! $this->product_has_preorder( $product->get_id() ) ) {
			return;
		}
		$badge = apply_filters( 'wpi_preorder_badge_label', get_option( 'wpi_badge_text', __( 'Pre-Order', 'wpi' ) ), $product );
		echo '<span class="wpi-badge">' . esc_html( $badge ) . '</span>';
	}

	public function enqueue(): void {
		// Only load on WC pages — keeps front-of-house bloat down.
		if ( ! ( function_exists( 'is_woocommerce' ) && ( is_woocommerce() || is_cart() || is_checkout() || is_account_page() ) ) ) {
			return;
		}
		wp_enqueue_style(
			'wpi-frontend',
			WPI_PLUGIN_URL . 'assets/css/frontend.css',
			[],
			WPI_VERSION
		);
	}
}
