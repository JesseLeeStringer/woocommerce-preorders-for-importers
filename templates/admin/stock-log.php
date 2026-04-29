<?php
/**
 * Admin: Stock log view
 * @var array $entries
 * @var array $filters
 */
if ( ! defined( 'ABSPATH' ) ) exit;

$action_labels = [
	WPI_Stock_Log::ACTION_PREORDER_PLACED    => __( 'Preorder Placed', 'wpi' ),
	WPI_Stock_Log::ACTION_PREORDER_CANCELLED => __( 'Preorder Cancelled', 'wpi' ),
	WPI_Stock_Log::ACTION_SHIPMENT_RELEASED  => __( 'Shipment Released', 'wpi' ),
	WPI_Stock_Log::ACTION_MANUAL_ADJUSTMENT  => __( 'Manual Adjustment', 'wpi' ),
];
?>
<div class="wrap">
	<h1 class="wp-heading-inline"><?php esc_html_e( 'Stock Log', 'wpi' ); ?></h1>
	<a href="<?php echo esc_url( add_query_arg( 'export', 'csv' ) ); ?>" class="page-title-action"><?php esc_html_e( 'Export CSV', 'wpi' ); ?></a>
	<hr class="wp-header-end">

	<form method="get">
		<input type="hidden" name="page" value="wpi-stock-log">
		<input type="number" name="product_id" placeholder="<?php esc_attr_e( 'Product ID', 'wpi' ); ?>" value="<?php echo esc_attr( $filters['product_id'] ); ?>">
		<input type="number" name="shipment_id" placeholder="<?php esc_attr_e( 'Shipment ID', 'wpi' ); ?>" value="<?php echo esc_attr( $filters['shipment_id'] ); ?>">
		<select name="action_filter">
			<option value=""><?php esc_html_e( 'All actions', 'wpi' ); ?></option>
			<?php foreach ( $action_labels as $val => $label ) : ?>
				<option value="<?php echo esc_attr( $val ); ?>" <?php selected( $filters['action'], $val ); ?>><?php echo esc_html( $label ); ?></option>
			<?php endforeach; ?>
		</select>
		<input type="date" name="date_from" value="<?php echo esc_attr( $filters['date_from'] ); ?>">
		<input type="date" name="date_to" value="<?php echo esc_attr( $filters['date_to'] ); ?>">
		<?php submit_button( __( 'Filter', 'wpi' ), 'secondary', '', false ); ?>
	</form>

	<table class="wp-list-table widefat fixed striped wpi-shipment-table" style="margin-top:16px">
		<thead>
			<tr>
				<th><?php esc_html_e( 'Date', 'wpi' ); ?></th>
				<th><?php esc_html_e( 'Product', 'wpi' ); ?></th>
				<th><?php esc_html_e( 'Shipment', 'wpi' ); ?></th>
				<th><?php esc_html_e( 'Action', 'wpi' ); ?></th>
				<th><?php esc_html_e( 'Qty', 'wpi' ); ?></th>
				<th><?php esc_html_e( 'User', 'wpi' ); ?></th>
				<th><?php esc_html_e( 'Notes', 'wpi' ); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php if ( ! $entries ) : ?>
				<tr><td colspan="7"><?php esc_html_e( 'No log entries found.', 'wpi' ); ?></td></tr>
			<?php endif; ?>
			<?php foreach ( $entries as $entry ) :
				$user   = get_userdata( (int) $entry->user_id );
				$qty_class = $entry->qty_delta < 0 ? 'negative' : '';
			?>
			<tr>
				<td><?php echo esc_html( date_i18n( get_option( 'date_format' ) . ' H:i', strtotime( $entry->created_at ) ) ); ?></td>
				<td><?php echo esc_html( $entry->product_name ?? '#' . $entry->product_id ); ?></td>
				<td><?php echo esc_html( $entry->shipment_ref ?? '—' ); ?></td>
				<td><?php echo esc_html( $action_labels[ $entry->action ] ?? $entry->action ); ?></td>
				<td class="wpi-qty-remaining <?php echo esc_attr( $qty_class ); ?>">
					<?php echo ( $entry->qty_delta > 0 ? '+' : '' ) . (int) $entry->qty_delta; ?>
				</td>
				<td><?php echo esc_html( $user ? $user->display_name : __( 'System', 'wpi' ) ); ?></td>
				<td><?php echo esc_html( $entry->notes ?? '' ); ?></td>
			</tr>
			<?php endforeach; ?>
		</tbody>
	</table>
</div>
