<?php
/**
 * Admin: new preorder placed — plain text.
 *
 * @var WC_Order $order
 * @var string   $email_heading
 * @var WPI_Email_Admin_Preorder $email
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

echo "= " . esc_html( wp_strip_all_tags( $email_heading ) ) . " =\n\n";

printf(
	/* translators: %s: order number */
	esc_html__( 'A new preorder has been placed (Order #%s).', 'wpi' ) . "\n\n",
	esc_html( $order->get_order_number() )
);

echo esc_html__( 'Customer', 'wpi' )    . ': ' . esc_html( $order->get_formatted_billing_full_name() ) . " <" . esc_html( $order->get_billing_email() ) . ">\n";
echo esc_html__( 'Order total', 'wpi' ) . ': ' . wp_strip_all_tags( $order->get_formatted_order_total() ) . "\n";
echo esc_html__( 'Shipping', 'wpi' )    . ': ' . wp_strip_all_tags( wc_price( $order->get_shipping_total() ) ) . "\n\n";

echo esc_html__( 'Preorder line items', 'wpi' ) . "\n";
echo "----------------------------------------\n";

$fmt = get_option( 'wpi_date_format', 'd/m/Y' );
foreach ( $order->get_items() as $item ) {
	if ( ! $item->get_meta( '_wpi_shipment_item_id' ) ) {
		continue;
	}
	$deposit      = (float) $item->get_meta( '_wpi_deposit_amount' );
	$balance      = (float) $item->get_meta( '_wpi_balance_due' );
	$shipment_id  = (int) $item->get_meta( '_wpi_shipment_id' );
	$shipment     = $shipment_id ? WPI_Shipment::get( $shipment_id ) : null;
	$release_date = $item->get_meta( '_wpi_release_date' );

	echo esc_html( $item->get_name() ) . ' x ' . (int) $item->get_quantity() . "\n";
	echo '  ' . esc_html__( 'Deposit', 'wpi' )     . ': ' . wp_strip_all_tags( wc_price( $deposit * $item->get_quantity() ) ) . "\n";
	echo '  ' . esc_html__( 'Balance due', 'wpi' ) . ': ' . wp_strip_all_tags( wc_price( $balance * $item->get_quantity() ) ) . "\n";
	if ( $shipment ) {
		echo '  ' . esc_html__( 'Shipment', 'wpi' ) . ': ' . esc_html( $shipment->reference );
		if ( $release_date ) {
			echo ' (' . esc_html( date_i18n( $fmt, strtotime( $release_date ) ) ) . ')';
		}
		echo "\n";
	}
}

echo "----------------------------------------\n\n";
echo esc_html__( 'View order:', 'wpi' ) . ' ' . esc_url_raw( $order->get_edit_order_url() ) . "\n\n";
echo esc_html__( 'Action required: confirm whether freight should be invoiced separately after goods arrive.', 'wpi' ) . "\n";
