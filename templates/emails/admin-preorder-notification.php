<?php
/**
 * Admin: new preorder placed — HTML.
 *
 * @var WC_Order $order
 * @var string   $email_heading
 * @var WPI_Email_Admin_Preorder $email
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

do_action( 'woocommerce_email_header', $email_heading, $email );
?>

<p><?php
	printf(
		/* translators: %s: order number */
		esc_html__( 'A new preorder has been placed (Order #%s). Deposit payment has been received and the customer is awaiting fulfilment when the relevant shipment arrives.', 'wpi' ),
		esc_html( $order->get_order_number() )
	);
?></p>

<table style="width:100%; border-collapse:collapse">
	<tr>
		<th align="left" style="border-bottom:1px solid #eee; padding:6px 0"><?php esc_html_e( 'Customer', 'wpi' ); ?></th>
		<td style="border-bottom:1px solid #eee; padding:6px 0"><?php echo esc_html( $order->get_formatted_billing_full_name() . ' <' . $order->get_billing_email() . '>' ); ?></td>
	</tr>
	<tr>
		<th align="left" style="border-bottom:1px solid #eee; padding:6px 0"><?php esc_html_e( 'Order total', 'wpi' ); ?></th>
		<td style="border-bottom:1px solid #eee; padding:6px 0"><?php echo wp_kses_post( $order->get_formatted_order_total() ); ?></td>
	</tr>
	<tr>
		<th align="left" style="border-bottom:1px solid #eee; padding:6px 0"><?php esc_html_e( 'Shipping', 'wpi' ); ?></th>
		<td style="border-bottom:1px solid #eee; padding:6px 0"><?php echo wp_kses_post( wc_price( $order->get_shipping_total() ) ); ?></td>
	</tr>
</table>

<h3><?php esc_html_e( 'Preorder line items', 'wpi' ); ?></h3>
<table style="width:100%; border-collapse:collapse">
	<thead>
		<tr>
			<th align="left" style="border-bottom:2px solid #ddd; padding:6px 4px"><?php esc_html_e( 'Product', 'wpi' ); ?></th>
			<th align="right" style="border-bottom:2px solid #ddd; padding:6px 4px"><?php esc_html_e( 'Qty', 'wpi' ); ?></th>
			<th align="right" style="border-bottom:2px solid #ddd; padding:6px 4px"><?php esc_html_e( 'Deposit', 'wpi' ); ?></th>
			<th align="right" style="border-bottom:2px solid #ddd; padding:6px 4px"><?php esc_html_e( 'Balance due', 'wpi' ); ?></th>
			<th align="left"  style="border-bottom:2px solid #ddd; padding:6px 4px"><?php esc_html_e( 'Shipment', 'wpi' ); ?></th>
		</tr>
	</thead>
	<tbody>
	<?php
	$fmt = get_option( 'wpi_date_format', 'd/m/Y' );
	foreach ( $order->get_items() as $item ) :
		if ( ! $item->get_meta( '_wpi_shipment_item_id' ) ) {
			continue;
		}
		$deposit      = (float) $item->get_meta( '_wpi_deposit_amount' );
		$balance      = (float) $item->get_meta( '_wpi_balance_due' );
		$shipment_id  = (int) $item->get_meta( '_wpi_shipment_id' );
		$shipment     = $shipment_id ? WPI_Shipment::get( $shipment_id ) : null;
		$release_date = $item->get_meta( '_wpi_release_date' );
	?>
		<tr>
			<td style="border-bottom:1px solid #eee; padding:6px 4px"><?php echo esc_html( $item->get_name() ); ?></td>
			<td style="border-bottom:1px solid #eee; padding:6px 4px" align="right"><?php echo (int) $item->get_quantity(); ?></td>
			<td style="border-bottom:1px solid #eee; padding:6px 4px" align="right"><?php echo wp_kses_post( wc_price( $deposit * $item->get_quantity() ) ); ?></td>
			<td style="border-bottom:1px solid #eee; padding:6px 4px" align="right"><?php echo wp_kses_post( wc_price( $balance * $item->get_quantity() ) ); ?></td>
			<td style="border-bottom:1px solid #eee; padding:6px 4px">
				<?php
				echo $shipment ? esc_html( $shipment->reference ) : '—';
				if ( $release_date ) {
					echo '<br><small>' . esc_html( date_i18n( $fmt, strtotime( $release_date ) ) ) . '</small>';
				}
				?>
			</td>
		</tr>
	<?php endforeach; ?>
	</tbody>
</table>

<p style="margin-top:16px">
	<a href="<?php echo esc_url( $order->get_edit_order_url() ); ?>" class="button"><?php esc_html_e( 'View order in admin →', 'wpi' ); ?></a>
</p>

<p><strong><?php esc_html_e( 'Action required:', 'wpi' ); ?></strong> <?php esc_html_e( 'Confirm whether freight should be invoiced separately after goods arrive — see the order note added automatically.', 'wpi' ); ?></p>

<?php
do_action( 'woocommerce_email_footer', $email );
