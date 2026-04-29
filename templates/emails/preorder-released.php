<?php
/**
 * Preorder Released email template.
 * Override by copying to yourtheme/woocommerce-preorders-for-importers/emails/preorder-released.php
 *
 * @var WC_Order $order
 * @var WPI_Email_Preorder_Released $email
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

do_action( 'woocommerce_email_header', $email->get_heading(), $email );
?>

<p><?php esc_html_e( 'Great news! Your preorder has arrived and is now being processed for dispatch.', 'wpi' ); ?></p>

<?php foreach ( $order->get_items() as $item ) :
	$balance = (float) $item->get_meta( '_wpi_balance_due' );
	if ( ! $item->get_meta( '_wpi_shipment_item_id' ) ) continue;
?>
<table>
	<tr><th><?php esc_html_e( 'Product', 'wpi' ); ?></th><td><?php echo esc_html( $item->get_name() ); ?></td></tr>
	<tr><th><?php esc_html_e( 'Qty', 'wpi' ); ?></th><td><?php echo (int) $item->get_quantity(); ?></td></tr>
	<?php if ( $balance > 0 ) : ?>
	<tr><th><?php esc_html_e( 'Balance due', 'wpi' ); ?></th><td><?php echo wc_price( $balance ); ?></td></tr>
	<?php endif; ?>
</table>
<?php endforeach; ?>

<p><?php esc_html_e( 'You will receive a separate invoice for any outstanding balance. Thank you for your patience.', 'wpi' ); ?></p>

<?php do_action( 'woocommerce_email_footer', $email ); ?>
