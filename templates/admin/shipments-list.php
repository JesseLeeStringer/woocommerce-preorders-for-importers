<?php
/**
 * Admin: Shipments list
 * @var WPI_Shipment[] $shipments
 */
if ( ! defined( 'ABSPATH' ) ) exit;
?>
<div class="wrap">
	<h1 class="wp-heading-inline"><?php esc_html_e( 'Shipments', 'wpi' ); ?></h1>
	<a href="<?php echo esc_url( admin_url( 'admin.php?page=wpi-shipment-edit' ) ); ?>" class="page-title-action"><?php esc_html_e( 'Add Shipment', 'wpi' ); ?></a>
	<hr class="wp-header-end">

	<?php if ( isset( $_GET['released'] ) ) : ?>
		<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Shipment released successfully.', 'wpi' ); ?></p></div>
	<?php endif; ?>

	<table class="wp-list-table widefat fixed striped wpi-shipment-table">
		<thead>
			<tr>
				<th><?php esc_html_e( 'Reference', 'wpi' ); ?></th>
				<th><?php esc_html_e( 'Due Date', 'wpi' ); ?></th>
				<th><?php esc_html_e( 'Status', 'wpi' ); ?></th>
				<th><?php esc_html_e( 'Products', 'wpi' ); ?></th>
				<th><?php esc_html_e( 'Preorders', 'wpi' ); ?></th>
				<th><?php esc_html_e( 'Actions', 'wpi' ); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php if ( ! $shipments ) : ?>
				<tr><td colspan="6"><?php esc_html_e( 'No shipments found. Add one to get started.', 'wpi' ); ?></td></tr>
			<?php endif; ?>
			<?php foreach ( $shipments as $shipment ) :
				$items       = WPI_Shipment_Item::for_shipment( $shipment->id );
				$total_pre   = array_sum( array_map( fn( $i ) => $i->qty_preordered, $items ) );
				$fmt         = get_option( 'wpi_date_format', 'd/m/Y' );
				$edit_url    = admin_url( 'admin.php?page=wpi-shipment-edit&id=' . $shipment->id );
				$release_url = wp_nonce_url( admin_url( 'admin-post.php?action=wpi_release_shipment' ), 'wpi_release_shipment' );
			?>
			<tr>
				<td><a href="<?php echo esc_url( $edit_url ); ?>"><?php echo esc_html( $shipment->reference ); ?></a></td>
				<td><?php echo esc_html( date_i18n( $fmt, strtotime( $shipment->due_date ) ) ); ?></td>
				<td><?php echo esc_html( ucfirst( $shipment->status ) ); ?></td>
				<td><?php echo count( $items ); ?></td>
				<td><?php echo (int) $total_pre; ?></td>
				<td>
					<a href="<?php echo esc_url( $edit_url ); ?>"><?php esc_html_e( 'Edit', 'wpi' ); ?></a>
					<?php if ( 'active' === $shipment->status ) : ?>
					&nbsp;|&nbsp;
					<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:inline">
						<?php wp_nonce_field( 'wpi_release_shipment' ); ?>
						<input type="hidden" name="action" value="wpi_release_shipment">
						<input type="hidden" name="shipment_id" value="<?php echo (int) $shipment->id; ?>">
						<button type="submit" class="button-link wpi-release-btn"
							data-ref="<?php echo esc_attr( $shipment->reference ); ?>"
							data-orders="<?php echo (int) $total_pre; ?>">
							<?php esc_html_e( 'Release', 'wpi' ); ?>
						</button>
					</form>
					<?php endif; ?>
				</td>
			</tr>
			<?php endforeach; ?>
		</tbody>
	</table>
</div>
