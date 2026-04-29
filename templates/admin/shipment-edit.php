<?php
/**
 * Admin: Shipment edit screen
 * @var WPI_Shipment|null $shipment
 * @var WPI_Shipment_Item[] $items
 */
if ( ! defined( 'ABSPATH' ) ) exit;

$fmt = get_option( 'wpi_date_format', 'd/m/Y' );
?>
<div class="wrap">
	<h1><?php echo $shipment ? esc_html( $shipment->reference ) : esc_html__( 'New Shipment', 'wpi' ); ?></h1>

	<?php if ( isset( $_GET['saved'] ) ) : ?>
		<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Shipment saved.', 'wpi' ); ?></p></div>
	<?php endif; ?>

	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
		<?php wp_nonce_field( 'wpi_save_shipment' ); ?>
		<input type="hidden" name="action" value="wpi_save_shipment">
		<input type="hidden" name="shipment_id" value="<?php echo $shipment ? (int) $shipment->id : 0; ?>">

		<table class="form-table">
			<tr>
				<th><?php esc_html_e( 'Reference', 'wpi' ); ?></th>
				<td><input type="text" name="reference" class="regular-text" value="<?php echo esc_attr( $shipment->reference ?? '' ); ?>" required></td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'Due Date', 'wpi' ); ?></th>
				<td><input type="date" name="due_date" value="<?php echo esc_attr( $shipment->due_date ?? '' ); ?>" required></td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'Status', 'wpi' ); ?></th>
				<td>
					<select name="status">
						<?php foreach ( [ 'draft', 'active', 'arrived', 'closed' ] as $s ) : ?>
							<option value="<?php echo esc_attr( $s ); ?>" <?php selected( $shipment->status ?? 'draft', $s ); ?>><?php echo esc_html( ucfirst( $s ) ); ?></option>
						<?php endforeach; ?>
					</select>
				</td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'Notes', 'wpi' ); ?></th>
				<td><textarea name="notes" rows="3" class="large-text"><?php echo esc_textarea( $shipment->notes ?? '' ); ?></textarea></td>
			</tr>
		</table>

		<h2><?php esc_html_e( 'Products in this Shipment', 'wpi' ); ?></h2>

		<table class="wp-list-table widefat fixed wpi-shipment-table" id="wpi-items-table">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Product ID', 'wpi' ); ?></th>
					<th><?php esc_html_e( 'Shipment Price ($)', 'wpi' ); ?></th>
					<th><?php esc_html_e( 'Qty Allocated', 'wpi' ); ?></th>
					<th><?php esc_html_e( 'Qty Preordered', 'wpi' ); ?></th>
					<th><?php esc_html_e( 'Deposit Type', 'wpi' ); ?></th>
					<th><?php esc_html_e( 'Deposit Value', 'wpi' ); ?></th>
					<th><?php esc_html_e( 'Deposit Amount', 'wpi' ); ?></th>
					<th><?php esc_html_e( 'Balance Due', 'wpi' ); ?></th>
					<th><?php esc_html_e( 'Release Offset (days)', 'wpi' ); ?></th>
					<th><?php esc_html_e( 'Customer Can Choose', 'wpi' ); ?></th>
					<th></th>
				</tr>
			</thead>
			<tbody id="wpi-items-body">
				<?php foreach ( $items as $i => $item ) :
					$product = wc_get_product( $item->product_id );
				?>
				<tr class="wpi-item-row">
					<td>
						<input type="number" name="item_product_id[<?php echo $i; ?>]" class="small-text" value="<?php echo (int) $item->product_id; ?>">
						<?php if ( $product ) echo '<br><small>' . esc_html( $product->get_name() ) . '</small>'; ?>
					</td>
					<td><input type="number" step="0.01" name="item_price[<?php echo $i; ?>]" class="small-text wpi-price" value="<?php echo esc_attr( $item->shipment_price ); ?>"></td>
					<td><input type="number" name="item_qty[<?php echo $i; ?>]" class="small-text" value="<?php echo (int) $item->qty_allocated; ?>"></td>
					<td><?php echo (int) $item->qty_preordered; ?></td>
					<td>
						<select name="item_deposit_type[<?php echo $i; ?>]" class="wpi-deposit-type">
							<option value="percent" <?php selected( $item->deposit_type, 'percent' ); ?>>%</option>
							<option value="fixed" <?php selected( $item->deposit_type, 'fixed' ); ?>>$ Fixed</option>
						</select>
					</td>
					<td><input type="number" step="0.01" name="item_deposit_value[<?php echo $i; ?>]" class="small-text wpi-deposit-value" value="<?php echo esc_attr( $item->deposit_value ); ?>"></td>
					<td class="wpi-derived-deposit"><?php echo wc_price( $item->deposit_amount() ); ?></td>
					<td class="wpi-derived-balance"><?php echo wc_price( $item->balance_due() ); ?></td>
					<td><input type="number" name="item_offset[<?php echo $i; ?>]" class="small-text" value="<?php echo (int) $item->release_offset_days; ?>"></td>
					<td><input type="checkbox" name="item_customer_choice[<?php echo $i; ?>]" value="1" <?php checked( $item->allow_customer_choice ); ?>></td>
					<td><button type="button" class="button-link wpi-remove-row"><?php esc_html_e( 'Remove', 'wpi' ); ?></button></td>
				</tr>
				<?php endforeach; ?>
			</tbody>
		</table>

		<p><button type="button" class="button wpi-add-row"><?php esc_html_e( '+ Add Product', 'wpi' ); ?></button></p>

		<?php submit_button( __( 'Save Shipment', 'wpi' ) ); ?>
	</form>
</div>

<script type="text/html" id="wpi-item-row-template">
	<td><input type="number" name="item_product_id[__INDEX__]" class="small-text" placeholder="Product ID"></td>
	<td><input type="number" step="0.01" name="item_price[__INDEX__]" class="small-text wpi-price" value="0"></td>
	<td><input type="number" name="item_qty[__INDEX__]" class="small-text" value="0"></td>
	<td>—</td>
	<td>
		<select name="item_deposit_type[__INDEX__]" class="wpi-deposit-type">
			<option value="percent">%</option>
			<option value="fixed">$ Fixed</option>
		</select>
	</td>
	<td><input type="number" step="0.01" name="item_deposit_value[__INDEX__]" class="small-text wpi-deposit-value" value="0"></td>
	<td class="wpi-derived-deposit">$0.00</td>
	<td class="wpi-derived-balance">$0.00</td>
	<td><input type="number" name="item_offset[__INDEX__]" class="small-text" value="0"></td>
	<td><input type="checkbox" name="item_customer_choice[__INDEX__]" value="1"></td>
	<td><button type="button" class="button-link wpi-remove-row"><?php esc_html_e( 'Remove', 'wpi' ); ?></button></td>
</script>
