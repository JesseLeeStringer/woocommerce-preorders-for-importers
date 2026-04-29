<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WPI_Preorder_Cart {

	public function __construct() {
		// Render shipment-choice picker on the product page when admin has enabled it.
		add_action( 'woocommerce_before_add_to_cart_button', [ $this, 'render_shipment_choice' ], 9 );

		// Attach shipment item ID to cart item when a preorder product is added.
		add_filter( 'woocommerce_add_cart_item_data', [ $this, 'attach_shipment_item' ], 10, 3 );

		// Replace product price with deposit amount in cart.
		add_action( 'woocommerce_before_calculate_totals', [ $this, 'set_deposit_price' ], 20 );

		// Show deposit breakdown in cart item rows.
		add_filter( 'woocommerce_get_item_data', [ $this, 'display_item_meta' ], 10, 2 );

		// Persist the shipment item ID through to the order item, and flag the order as containing a preorder.
		add_action( 'woocommerce_checkout_create_order_line_item', [ $this, 'save_item_meta_to_order' ], 10, 4 );

		// Show checkout notice when cart contains preorder item(s).
		add_action( 'woocommerce_before_checkout_form', [ $this, 'checkout_notice' ] );
	}

	/**
	 * Render a shipment selector on the product page when at least one item allows customer choice.
	 */
	public function render_shipment_choice(): void {
		global $product;
		if ( ! $product ) {
			return;
		}
		if ( ! WPI_Shipment_Item::product_allows_customer_choice( $product->get_id() ) ) {
			return;
		}

		$items = WPI_Shipment_Item::active_for_product( $product->get_id() );
		$fmt   = get_option( 'wpi_date_format', 'd/m/Y' );
		echo '<div class="wpi-shipment-choice"><label for="wpi_chosen_shipment_item_id"><strong>' . esc_html__( 'Choose your shipment', 'wpi' ) . '</strong></label><br>';
		echo '<select name="wpi_chosen_shipment_item_id" id="wpi_chosen_shipment_item_id">';
		foreach ( $items as $item ) {
			$label = sprintf(
				/* translators: 1: release date, 2: deposit amount, 3: remaining qty */
				__( 'Arrives %1$s — deposit %2$s — %3$d available', 'wpi' ),
				date_i18n( $fmt, strtotime( $item->release_date() ) ),
				wp_strip_all_tags( wc_price( $item->deposit_amount() ) ),
				max( 0, $item->qty_remaining() )
			);
			printf(
				'<option value="%d">%s</option>',
				(int) $item->id,
				esc_html( $label )
			);
		}
		echo '</select></div>';
	}

	public function attach_shipment_item( array $cart_item_data, int $product_id, int $variation_id = 0 ): array {
		$chosen_id = isset( $_REQUEST['wpi_chosen_shipment_item_id'] ) ? (int) $_REQUEST['wpi_chosen_shipment_item_id'] : 0;

		if ( $chosen_id ) {
			$chosen = WPI_Shipment_Item::get( $chosen_id );
			// Validate the chosen item belongs to this product and lives under an active shipment.
			if ( $chosen && $chosen->product_id === $product_id ) {
				$shipment = WPI_Shipment::get( $chosen->shipment_id );
				if ( $shipment && $shipment->status === 'active' ) {
					$cart_item_data['wpi_shipment_item_id'] = $chosen->id;
					return $cart_item_data;
				}
			}
		}

		$item = WPI_Shipment_Item::earliest_available_for_product( $product_id );
		if ( ! $item ) {
			return $cart_item_data;
		}
		$cart_item_data['wpi_shipment_item_id'] = $item->id;
		return $cart_item_data;
	}

	public function set_deposit_price( WC_Cart $cart ): void {
		if ( is_admin() && ! defined( 'DOING_AJAX' ) ) {
			return;
		}
		foreach ( $cart->get_cart() as $cart_item ) {
			$item_id = $cart_item['wpi_shipment_item_id'] ?? 0;
			if ( ! $item_id ) {
				continue;
			}
			$shipment_item = WPI_Shipment_Item::get( (int) $item_id );
			if ( ! $shipment_item ) {
				continue;
			}
			$cart_item['data']->set_price( $shipment_item->deposit_amount() );
		}
	}

	public function display_item_meta( array $item_data, array $cart_item ): array {
		$item_id = $cart_item['wpi_shipment_item_id'] ?? 0;
		if ( ! $item_id ) {
			return $item_data;
		}
		$shipment_item = WPI_Shipment_Item::get( (int) $item_id );
		if ( ! $shipment_item ) {
			return $item_data;
		}

		$fmt = get_option( 'wpi_date_format', 'd/m/Y' );

		$item_data[] = [
			'name'    => __( 'Full price', 'wpi' ),
			'display' => wc_price( $shipment_item->shipment_price ),
		];
		$item_data[] = [
			'name'    => __( 'Deposit paid', 'wpi' ),
			'display' => wc_price( $shipment_item->deposit_amount() ),
		];
		$item_data[] = [
			'name'    => __( 'Balance due on arrival', 'wpi' ),
			'display' => wc_price( $shipment_item->balance_due() ),
		];
		$item_data[] = [
			'name'    => __( 'Estimated release', 'wpi' ),
			'display' => date_i18n( $fmt, strtotime( $shipment_item->release_date() ) ),
		];

		return $item_data;
	}

	public function save_item_meta_to_order(
		WC_Order_Item_Product $order_item,
		string $cart_item_key,
		array $values,
		WC_Order $order
	): void {
		$item_id = $values['wpi_shipment_item_id'] ?? 0;
		if ( ! $item_id ) {
			return;
		}
		$shipment_item = WPI_Shipment_Item::get( (int) $item_id );
		if ( ! $shipment_item ) {
			return;
		}

		$order_item->update_meta_data( '_wpi_shipment_item_id', $shipment_item->id );
		$order_item->update_meta_data( '_wpi_shipment_id', $shipment_item->shipment_id );
		$order_item->update_meta_data( '_wpi_deposit_amount', $shipment_item->deposit_amount() );
		$order_item->update_meta_data( '_wpi_full_price', $shipment_item->shipment_price );
		$order_item->update_meta_data( '_wpi_balance_due', $shipment_item->balance_due() );
		$order_item->update_meta_data( '_wpi_release_date', $shipment_item->release_date() );

		// Flag the order so downstream filters (e.g. payment_complete_order_status) can see it
		// before the order is saved.
		$order->update_meta_data( '_wpi_has_preorder', '1' );
	}

	public function checkout_notice(): void {
		foreach ( WC()->cart->get_cart() as $cart_item ) {
			if ( ! empty( $cart_item['wpi_shipment_item_id'] ) ) {
				$notice = get_option(
					'wpi_checkout_notice',
					__( 'Your order includes preorder items. You are paying a deposit only. The balance will be invoiced separately when your order is ready to dispatch.', 'wpi' )
				);
				wc_print_notice( esc_html( $notice ), 'notice' );
				return;
			}
		}
	}
}
