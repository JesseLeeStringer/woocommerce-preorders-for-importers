<?php
/**
 * Preorder Confirmation email template.
 * Override by copying to yourtheme/woocommerce-preorders-for-importers/emails/preorder-confirmation.php
 *
 * @var WC_Order $order
 * @var WPI_Email_Preorder_Confirmation $email
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

do_action( 'woocommerce_email_header', $email->get_heading(), $email );
?>

<p><?php esc_html_e( 'Thank you for your preorder. Your deposit payment has been received and your spot is secured.', 'wpi' ); ?></p>

<?php foreach ( $order->get_items() as $item ) :
	$shipment_item_id = (int) $item->get_meta( '_wpi_shipment_item_id' );
	if ( ! $shipment_item_id ) continue;
	$fmt          = get_option( 'wpi_date_format', 'd/m/Y' );
	$deposit      = (float) $item->get_meta( '_wpi_deposit_amount' );
	$full         = (float) $item->get_meta( '_wpi_full_price' );
	$balance      = (float) $item->get_meta( '_wpi_balance_due' );
	$release_date = $item->get_meta( '_wpi_release_date' );
?>
<table>
	<tr><th><?php esc_html_e( 'Product', 'wpi' ); ?></th><td><?php echo esc_html( $item->get_name() ); ?></td></tr>
	<tr><th><?php esc_html_e( 'Qty', 'wpi' ); ?></th><td><?php echo (int) $item->get_quantity(); ?></td></tr>
	<tr><th><?php esc_html_e( 'Full price', 'wpi' ); ?></th><td><?php echo wc_price( $full ); ?></td></tr>
	<tr><th><?php esc_html_e( 'Deposit paid', 'wpi' ); ?></th><td><?php echo wc_price( $deposit ); ?></td></tr>
	<tr><th><?php esc_html_e( 'Balance due on arrival', 'wpi' ); ?></th><td><?php echo wc_price( $balance ); ?></td></tr>
	<tr><th><?php esc_html_e( 'Estimated release', 'wpi' ); ?></th><td><?php echo esc_html( date_i18n( $fmt, strtotime( $release_date ) ) ); ?></td></tr>
</table>
<?php endforeach; ?>

<p><?php esc_html_e( "We'll be in touch as soon as your order is ready to dispatch. The balance amount shown above will be invoiced separately.", 'wpi' ); ?></p>

<?php do_action( 'woocommerce_email_footer', $email ); ?>
