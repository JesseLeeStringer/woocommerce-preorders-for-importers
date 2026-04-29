<?php
/**
 * Preorder Confirmation email — plain text.
 *
 * @var WC_Order $order
 * @var string   $email_heading
 * @var WPI_Email_Preorder_Confirmation $email
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

echo "= " . esc_html( wp_strip_all_tags( $email_heading ) ) . " =\n\n";

echo esc_html__( 'Thank you for your preorder. Your deposit payment has been received and your spot is secured.', 'wpi' ) . "\n\n";

$fmt = get_option( 'wpi_date_format', 'd/m/Y' );

foreach ( $order->get_items() as $item ) {
	$shipment_item_id = (int) $item->get_meta( '_wpi_shipment_item_id' );
	if ( ! $shipment_item_id ) {
		continue;
	}
	$deposit      = (float) $item->get_meta( '_wpi_deposit_amount' );
	$full         = (float) $item->get_meta( '_wpi_full_price' );
	$balance      = (float) $item->get_meta( '_wpi_balance_due' );
	$release_date = $item->get_meta( '_wpi_release_date' );

	echo "----------------------------------------\n";
	echo esc_html__( 'Product', 'wpi' )                . ': ' . esc_html( $item->get_name() ) . "\n";
	echo esc_html__( 'Qty', 'wpi' )                    . ': ' . (int) $item->get_quantity() . "\n";
	echo esc_html__( 'Full price', 'wpi' )             . ': ' . wp_strip_all_tags( wc_price( $full ) ) . "\n";
	echo esc_html__( 'Deposit paid', 'wpi' )           . ': ' . wp_strip_all_tags( wc_price( $deposit ) ) . "\n";
	echo esc_html__( 'Balance due on arrival', 'wpi' ) . ': ' . wp_strip_all_tags( wc_price( $balance ) ) . "\n";
	echo esc_html__( 'Estimated release', 'wpi' )      . ': ' . esc_html( date_i18n( $fmt, strtotime( $release_date ) ) ) . "\n";
}
echo "----------------------------------------\n\n";

echo esc_html__( "We'll be in touch as soon as your order is ready to dispatch. The balance amount shown above will be invoiced separately.", 'wpi' ) . "\n\n";

echo "\n" . wp_strip_all_tags( wp_kses_post( apply_filters( 'woocommerce_email_footer_text', get_option( 'woocommerce_email_footer_text' ) ) ) ) . "\n";
