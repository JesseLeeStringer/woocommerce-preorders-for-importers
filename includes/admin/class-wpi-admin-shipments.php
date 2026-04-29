<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Admin shipment list and edit screens.
 * Placeholder — full UI in next development pass.
 */
class WPI_Admin_Shipments {

	public function __construct() {
		add_action( 'admin_menu', [ $this, 'register_menu' ] );
		add_action( 'admin_post_wpi_release_shipment', [ $this, 'handle_release' ] );
		add_action( 'admin_post_wpi_save_shipment', [ $this, 'handle_save' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue' ] );
	}

	public function register_menu(): void {
		add_submenu_page(
			'woocommerce',
			__( 'Shipments', 'wpi' ),
			__( 'Shipments', 'wpi' ),
			'manage_woocommerce',
			'wpi-shipments',
			[ $this, 'render_list' ]
		);
		add_submenu_page(
			null, // Hidden from nav; accessed via link.
			__( 'Edit Shipment', 'wpi' ),
			__( 'Edit Shipment', 'wpi' ),
			'manage_woocommerce',
			'wpi-shipment-edit',
			[ $this, 'render_edit' ]
		);
	}

	public function render_list(): void {
		$shipments = WPI_Shipment::get_all();
		require WPI_PLUGIN_DIR . 'templates/admin/shipments-list.php';
	}

	public function render_edit(): void {
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

		$id   = isset( $_POST['shipment_id'] ) ? (int) $_POST['shipment_id'] : 0;
		$data = [
			'reference' => sanitize_text_field( $_POST['reference'] ?? '' ),
			'due_date'  => sanitize_text_field( $_POST['due_date'] ?? '' ),
			'status'    => sanitize_text_field( $_POST['status'] ?? 'draft' ),
			'notes'     => sanitize_textarea_field( $_POST['notes'] ?? '' ),
		];

		if ( $id ) {
			WPI_Shipment::update( $id, $data );
		} else {
			$id = WPI_Shipment::insert( $data );
		}

		// Save line items.
		WPI_Shipment_Item::delete_for_shipment( $id );
		$product_ids   = $_POST['item_product_id']   ?? [];
		$prices        = $_POST['item_price']         ?? [];
		$qtys          = $_POST['item_qty']           ?? [];
		$deposit_types = $_POST['item_deposit_type']  ?? [];
		$deposit_vals  = $_POST['item_deposit_value'] ?? [];
		$offsets       = $_POST['item_offset']        ?? [];
		$choices       = $_POST['item_customer_choice'] ?? [];

		foreach ( $product_ids as $i => $product_id ) {
			if ( ! $product_id ) {
				continue;
			}
			WPI_Shipment_Item::insert( $id, [
				'product_id'           => (int) $product_id,
				'shipment_price'       => (float) ( $prices[ $i ] ?? 0 ),
				'qty_allocated'        => (int) ( $qtys[ $i ] ?? 0 ),
				'deposit_type'         => sanitize_text_field( $deposit_types[ $i ] ?? 'percent' ),
				'deposit_value'        => (float) ( $deposit_vals[ $i ] ?? 0 ),
				'release_offset_days'  => (int) ( $offsets[ $i ] ?? 0 ),
				'allow_customer_choice' => isset( $choices[ $i ] ) ? 1 : 0,
			] );
		}

		wp_safe_redirect( admin_url( 'admin.php?page=wpi-shipment-edit&id=' . $id . '&saved=1' ) );
		exit;
	}

	public function enqueue( string $hook ): void {
		if ( ! in_array( $hook, [ 'woocommerce_page_wpi-shipments', 'admin_page_wpi-shipment-edit' ], true ) ) {
			return;
		}
		wp_enqueue_style( 'wpi-admin', WPI_PLUGIN_URL . 'assets/css/admin.css', [], WPI_VERSION );
		wp_enqueue_script( 'wpi-admin', WPI_PLUGIN_URL . 'assets/js/admin-shipment-editor.js', [ 'jquery' ], WPI_VERSION, true );
	}
}
