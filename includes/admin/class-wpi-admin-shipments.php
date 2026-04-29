<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Admin shipment list and edit screens.
 * Single menu page; sub-views dispatched via ?action=…
 */
class WPI_Admin_Shipments {

	public function __construct() {
		add_action( 'admin_menu', [ $this, 'register_menu' ] );
		add_action( 'admin_post_wpi_release_shipment', [ $this, 'handle_release' ] );
		add_action( 'admin_post_wpi_save_shipment', [ $this, 'handle_save' ] );
		add_action( 'admin_post_wpi_delete_shipment', [ $this, 'handle_delete' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue' ] );
	}

	public function register_menu(): void {
		add_submenu_page(
			'woocommerce',
			__( 'Shipments', 'wpi' ),
			__( 'Shipments', 'wpi' ),
			'manage_woocommerce',
			'wpi-shipments',
			[ $this, 'dispatch' ]
		);
	}

	public function dispatch(): void {
		$action = isset( $_GET['action'] ) ? sanitize_key( wp_unslash( $_GET['action'] ) ) : '';
		if ( in_array( $action, [ 'edit', 'new' ], true ) ) {
			$this->render_edit();
			return;
		}
		$this->render_list();
	}

	private function render_list(): void {
		$shipments = WPI_Shipment::get_all();
		require WPI_PLUGIN_DIR . 'templates/admin/shipments-list.php';
	}

	private function render_edit(): void {
		$id       = isset( $_GET['id'] ) ? (int) $_GET['id'] : 0;
		$shipment = $id ? WPI_Shipment::get( $id ) : null;
		$items    = $id ? WPI_Shipment_Item::for_shipment( $id ) : [];
		require WPI_PLUGIN_DIR . 'templates/admin/shipment-edit.php';
	}

	public function handle_release(): void {
		check_admin_referer( 'wpi_release_shipment' );
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'Permission denied.', 'wpi' ) );
		}

		$shipment_id = isset( $_POST['shipment_id'] ) ? (int) $_POST['shipment_id'] : 0;
		if ( ! $shipment_id ) {
			wp_die( esc_html__( 'Invalid shipment.', 'wpi' ) );
		}

		$result = WPI_Release::release_shipment( $shipment_id );
		set_transient( 'wpi_release_result_' . get_current_user_id(), $result, 60 );
		wp_safe_redirect( admin_url( 'admin.php?page=wpi-shipments&released=1' ) );
		exit;
	}

	public function handle_save(): void {
		check_admin_referer( 'wpi_save_shipment' );
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'Permission denied.', 'wpi' ) );
		}

		$id     = isset( $_POST['shipment_id'] ) ? (int) $_POST['shipment_id'] : 0;
		$status = sanitize_text_field( wp_unslash( $_POST['status'] ?? 'draft' ) );
		$data   = [
			'reference' => sanitize_text_field( wp_unslash( $_POST['reference'] ?? '' ) ),
			'due_date'  => sanitize_text_field( wp_unslash( $_POST['due_date'] ?? '' ) ),
			'status'    => $status,
			'notes'     => sanitize_textarea_field( wp_unslash( $_POST['notes'] ?? '' ) ),
		];

		if ( $id ) {
			WPI_Shipment::update( $id, $data );
		} else {
			$id = WPI_Shipment::insert( $data );
		}

		// Save line items: UPDATE existing rows by id, INSERT new ones, DELETE removed ones.
		// This preserves qty_preordered (which is read-only in the form and not POSTed).
		$item_ids       = $_POST['item_id']               ?? [];
		$product_ids    = $_POST['item_product_id']        ?? [];
		$prices         = $_POST['item_price']             ?? [];
		$qtys           = $_POST['item_qty']               ?? [];
		$deposit_types  = $_POST['item_deposit_type']      ?? [];
		$deposit_vals   = $_POST['item_deposit_value']     ?? [];
		$offsets        = $_POST['item_offset']            ?? [];
		$choices        = $_POST['item_customer_choice']   ?? [];

		$existing_ids   = array_map( fn( $i ) => $i->id, WPI_Shipment_Item::for_shipment( $id ) );
		$kept_ids       = [];
		$invalid        = [];

		foreach ( $product_ids as $i => $product_id ) {
			$product_id = (int) $product_id;
			if ( ! $product_id ) {
				continue;
			}
			if ( ! WPI_Shipment_Item::is_eligible_product( $product_id ) ) {
				$invalid[] = $product_id;
				continue;
			}

			$row = [
				'product_id'            => $product_id,
				'shipment_price'        => (float) ( $prices[ $i ] ?? 0 ),
				'qty_allocated'         => (int) ( $qtys[ $i ] ?? 0 ),
				'deposit_type'          => sanitize_text_field( $deposit_types[ $i ] ?? 'percent' ),
				'deposit_value'         => (float) ( $deposit_vals[ $i ] ?? 0 ),
				'release_offset_days'   => (int) ( $offsets[ $i ] ?? 0 ),
				'allow_customer_choice' => isset( $choices[ $i ] ) ? 1 : 0,
			];

			$item_id = isset( $item_ids[ $i ] ) ? (int) $item_ids[ $i ] : 0;
			if ( $item_id && in_array( $item_id, $existing_ids, true ) ) {
				WPI_Shipment_Item::update( $item_id, $row );
				$kept_ids[] = $item_id;
			} else {
				WPI_Shipment_Item::insert( $id, $row );
			}
		}

		// Delete rows the user removed from the form.
		foreach ( array_diff( $existing_ids, $kept_ids ) as $orphan_id ) {
			WPI_Shipment_Item::delete( (int) $orphan_id );
		}

		// Validate: a shipment can't go to 'active' with no usable line items.
		if ( $status === 'active' ) {
			$items = WPI_Shipment_Item::for_shipment( $id );
			$has_usable = false;
			foreach ( $items as $item ) {
				if ( $item->qty_allocated > 0 ) {
					$has_usable = true;
					break;
				}
			}
			if ( ! $has_usable ) {
				WPI_Shipment::set_status( $id, 'draft' );
				set_transient( 'wpi_save_notice_' . get_current_user_id(), [
					'type' => 'warning',
					'msg'  => __( 'Shipment reverted to Draft — at least one product with allocated qty is required to activate.', 'wpi' ),
				], 60 );
			}
		}

		if ( $invalid ) {
			set_transient( 'wpi_invalid_products_' . get_current_user_id(), $invalid, 60 );
		}

		wp_safe_redirect( admin_url( 'admin.php?page=wpi-shipments&action=edit&id=' . $id . '&saved=1' ) );
		exit;
	}

	public function handle_delete(): void {
		check_admin_referer( 'wpi_delete_shipment' );
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'Permission denied.', 'wpi' ) );
		}

		$shipment_id = isset( $_POST['shipment_id'] ) ? (int) $_POST['shipment_id'] : 0;
		$shipment    = $shipment_id ? WPI_Shipment::get( $shipment_id ) : null;
		if ( ! $shipment ) {
			wp_die( esc_html__( 'Invalid shipment.', 'wpi' ) );
		}

		// Refuse to delete a shipment with placed preorders unless the admin explicitly forces it.
		$items = WPI_Shipment_Item::for_shipment( $shipment_id );
		foreach ( $items as $item ) {
			if ( $item->qty_preordered > 0 ) {
				wp_die( esc_html__( 'This shipment has placed preorders. Cancel or release those first.', 'wpi' ) );
			}
		}

		WPI_Shipment::delete( $shipment_id );
		wp_safe_redirect( admin_url( 'admin.php?page=wpi-shipments&deleted=1' ) );
		exit;
	}

	public function enqueue( string $hook ): void {
		$wpi_pages = [
			'woocommerce_page_wpi-shipments',
			'woocommerce_page_wpi-stock-log',
		];
		if ( ! in_array( $hook, $wpi_pages, true ) ) {
			return;
		}

		wp_enqueue_style( 'wpi-admin', WPI_PLUGIN_URL . 'assets/css/admin.css', [], WPI_VERSION );

		// Editor-only assets (product search + JS).
		if ( $hook === 'woocommerce_page_wpi-shipments' ) {
			wp_enqueue_script( 'wc-enhanced-select' );
			wp_enqueue_style( 'woocommerce_admin_styles' );
			wp_enqueue_script( 'wpi-admin', WPI_PLUGIN_URL . 'assets/js/admin-shipment-editor.js', [ 'jquery', 'wc-enhanced-select' ], WPI_VERSION, true );
		}
	}
}
