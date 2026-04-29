<?php
/**
 * Preorder Released email — plain text.
 *
 * @var WC_Order $order
 * @var string   $email_heading
 * @var WPI_Email_Preorder_Released $email
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

echo "= " . esc_html( wp_strip_all_tags( $email_heading ) ) . " =\n\n";

echo esc_html__( 'Great news! Your preorder has arrived and is now being processed for dispatch.', 'wpi' ) . "\n\n";

foreach ( $order->get_items() as $item ) {
	if ( ! $item->get_meta( '_wpi_shipment_item_id' ) ) {
		continue;
	}
	$balance = (float) $item->get_meta( '_wpi_balance_due' );

	echo "----------------------------------------\n";
	echo esc_html__( 'Product', 'wpi' ) . ': ' . esc_html( $item->get_name() ) . "\n";
	echo esc_html__( 'Qty', 'wpi' )     . ': ' . (int) $item->get_quantity() . "\n";
	if ( $balance > 0 ) {
		echo esc_html__( 'Balance due', 'wpi' ) . ': ' . wp_strip_all_tags( wc_price( $balance ) ) . "\n";
	}
}
echo "----------------------------------------\n\n";

echo esc_html__( 'You will receive a separate invoice for any outstanding balance. Thank you for your patience.', 'wpi' ) . "\n\n";

echo "\n" . wp_strip_all_tags( wp_kses_post( apply_filters( 'woocommerce_email_footer_text', get_option( 'woocommerce_email_footer_text' ) ) ) ) . "\n";
