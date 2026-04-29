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
	<h1>
		<?php echo $shipment ? esc_html( $shipment->reference ) : esc_html__( 'New Shipment', 'wpi' ); ?>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=wpi-shipments' ) ); ?>" class="page-title-action"><?php esc_html_e( '← Back to list', 'wpi' ); ?></a>
	</h1>

	<?php
	if ( isset( $_GET['saved'] ) ) {
		echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Shipment saved.', 'wpi' ) . '</p></div>';
	}

	$user_id      = get_current_user_id();
	$save_notice  = get_transient( 'wpi_save_notice_' . $user_id );
	$invalid_pids = get_transient( 'wpi_invalid_products_' . $user_id );
	if ( $save_notice ) {
		delete_transient( 'wpi_save_notice_' . $user_id );
		printf(
			'<div class="notice notice-%s is-dismissible"><p>%s</p></div>',
			esc_attr( $save_notice['type'] ),
			esc_html( $save_notice['msg'] )
		);
	}
	if ( $invalid_pids ) {
		delete_transient( 'wpi_invalid_products_' . $user_id );
		echo '<div class="notice notice-warning is-dismissible"><p>'
			. esc_html__( 'These products were skipped (virtual or downloadable products cannot be preordered): ', 'wpi' )
			. esc_html( implode( ', ', $invalid_pids ) )
			. '</p></div>';
	}
	?>

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
					<p class="description"><?php esc_html_e( 'Active shipments are visible to customers as preorder. Promotion to Active requires at least one product with allocated qty.', 'wpi' ); ?></p>
				</td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'Notes', 'wpi' ); ?></th>
				<td><textarea name="notes" rows="3" class="large-text"><?php echo esc_textarea( $shipment->notes ?? '' ); ?></textarea></td>
			</tr>
			<?php if ( $shipment && $shipment->released_at ) : ?>
			<tr>
				<th><?php esc_html_e( 'Released', 'wpi' ); ?></th>
				<td>
					<?php
					$user = $shipment->released_by ? get_userdata( $shipment->released_by ) : null;
					echo esc_html( date_i18n( $fmt . ' H:i', strtotime( $shipment->released_at ) ) );
					echo ' — ';
					echo esc_html( $user ? $user->display_name : __( 'Unknown user', 'wpi' ) );
					?>
				</td>
			</tr>
			<?php endif; ?>
		</table>

		<h2><?php esc_html_e( 'Products in this Shipment', 'wpi' ); ?></h2>

		<table class="wp-list-table widefat fixed wpi-shipment-table" id="wpi-items-table">
			<thead>
				<tr>
					<th style="width:30%"><?php esc_html_e( 'Product', 'wpi' ); ?></th>
					<th><?php esc_html_e( 'Shipment Price', 'wpi' ); ?></th>
					<th><?php esc_html_e( 'Qty Allocated', 'wpi' ); ?></th>
					<th><?php esc_html_e( 'Qty Preordered', 'wpi' ); ?></th>
					<th><?php esc_html_e( 'Deposit Type', 'wpi' ); ?></th>
					<th><?php esc_html_e( 'Deposit Value', 'wpi' ); ?></th>
					<th><?php esc_html_e( 'Deposit', 'wpi' ); ?></th>
					<th><?php esc_html_e( 'Balance', 'wpi' ); ?></th>
					<th><?php esc_html_e( 'Offset (days)', 'wpi' ); ?></th>
					<th><?php esc_html_e( 'Customer Choice', 'wpi' ); ?></th>
					<th></th>
				</tr>
			</thead>
			<tbody id="wpi-items-body">
				<?php foreach ( $items as $i => $item ) :
					$product = wc_get_product( $item->product_id );
				?>
				<tr class="wpi-item-row">
					<td>
						<input type="hidden" name="item_id[<?php echo $i; ?>]" value="<?php echo (int) $item->id; ?>">
						<select class="wc-product-search wpi-product-search" style="width:100%"
								name="item_product_id[<?php echo $i; ?>]"
								data-placeholder="<?php esc_attr_e( 'Search for a product…', 'wpi' ); ?>"
								data-action="woocommerce_json_search_products"
								data-allow_clear="false">
							<?php if ( $product ) : ?>
								<option value="<?php echo (int) $item->product_id; ?>" selected><?php echo esc_html( $product->get_formatted_name() ); ?></option>
							<?php endif; ?>
						</select>
					</td>
					<td><input type="number" step="0.01" name="item_price[<?php echo $i; ?>]" class="small-text wpi-price" value="<?php echo esc_attr( $item->shipment_price ); ?>"></td>
					<td><input type="number" name="item_qty[<?php echo $i; ?>]" class="small-text" value="<?php echo (int) $item->qty_allocated; ?>"></td>
					<td><?php echo (int) $item->qty_preordered; ?></td>
					<td>
						<select name="item_deposit_type[<?php echo $i; ?>]" class="wpi-deposit-type">
							<option value="percent" <?php selected( $item->deposit_type, 'percent' ); ?>>%</option>
							<option value="fixed" <?php selected( $item->deposit_type, 'fixed' ); ?>><?php esc_html_e( 'Fixed', 'wpi' ); ?></option>
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
	<td>
		<input type="hidden" name="item_id[__INDEX__]" value="0">
		<select class="wc-product-search wpi-product-search" style="width:100%"
				name="item_product_id[__INDEX__]"
				data-placeholder="<?php esc_attr_e( 'Search for a product…', 'wpi' ); ?>"
				data-action="woocommerce_json_search_products"
				data-allow_clear="false"></select>
	</td>
	<td><input type="number" step="0.01" name="item_price[__INDEX__]" class="small-text wpi-price" value="0"></td>
	<td><input type="number" name="item_qty[__INDEX__]" class="small-text" value="0"></td>
	<td>—</td>
	<td>
		<select name="item_deposit_type[__INDEX__]" class="wpi-deposit-type">
			<option value="percent">%</option>
			<option value="fixed"><?php esc_html_e( 'Fixed', 'wpi' ); ?></option>
		</select>
	</td>
	<td><input type="number" step="0.01" name="item_deposit_value[__INDEX__]" class="small-text wpi-deposit-value" value="0"></td>
	<td class="wpi-derived-deposit"><?php echo wp_kses_post( wc_price( 0 ) ); ?></td>
	<td class="wpi-derived-balance"><?php echo wp_kses_post( wc_price( 0 ) ); ?></td>
	<td><input type="number" name="item_offset[__INDEX__]" class="small-text" value="0"></td>
	<td><input type="checkbox" name="item_customer_choice[__INDEX__]" value="1"></td>
	<td><button type="button" class="button-link wpi-remove-row"><?php esc_html_e( 'Remove', 'wpi' ); ?></button></td>
</script>
