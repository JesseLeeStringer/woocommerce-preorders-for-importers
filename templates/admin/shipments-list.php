<?php
/**
 * Admin: Shipments list
 * @var WPI_Shipment[] $shipments
 */
if ( ! defined( 'ABSPATH' ) ) exit;
?>
<div class="wrap">
	<h1 class="wp-heading-inline"><?php esc_html_e( 'Shipments', 'wpi' ); ?></h1>
	<a href="<?php echo esc_url( admin_url( 'admin.php?page=wpi-shipments&action=new' ) ); ?>" class="page-title-action"><?php esc_html_e( 'Add Shipment', 'wpi' ); ?></a>
	<hr class="wp-header-end">

	<?php
	if ( isset( $_GET['released'] ) ) {
		$result = get_transient( 'wpi_release_result_' . get_current_user_id() );
		delete_transient( 'wpi_release_result_' . get_current_user_id() );
		echo '<div class="notice notice-success is-dismissible"><p>';
		if ( is_array( $result ) && isset( $result['released'] ) ) {
			printf( esc_html__( 'Shipment released — %d order(s) moved to Processing.', 'wpi' ), (int) $result['released'] );
		} else {
			esc_html_e( 'Shipment released successfully.', 'wpi' );
		}
		echo '</p></div>';
		if ( is_array( $result ) && ! empty( $result['warnings'] ) ) {
			echo '<div class="notice notice-warning is-dismissible"><p><strong>' . esc_html__( 'Warnings:', 'wpi' ) . '</strong></p><ul>';
			foreach ( $result['warnings'] as $w ) {
				echo '<li>' . esc_html( $w ) . '</li>';
			}
			echo '</ul></div>';
		}
	}
	if ( isset( $_GET['deleted'] ) ) {
		echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Shipment deleted.', 'wpi' ) . '</p></div>';
	}
	?>

	<table class="wp-list-table widefat fixed striped wpi-shipment-table">
		<thead>
			<tr>
				<th><?php esc_html_e( 'Reference', 'wpi' ); ?></th>
				<th><?php esc_html_e( 'Due Date', 'wpi' ); ?></th>
				<th><?php esc_html_e( 'Status', 'wpi' ); ?></th>
				<th><?php esc_html_e( 'Products', 'wpi' ); ?></th>
				<th><?php esc_html_e( 'Preorders Placed', 'wpi' ); ?></th>
				<th><?php esc_html_e( 'Released', 'wpi' ); ?></th>
				<th><?php esc_html_e( 'Actions', 'wpi' ); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php if ( ! $shipments ) : ?>
				<tr><td colspan="7"><?php esc_html_e( 'No shipments found. Add one to get started.', 'wpi' ); ?></td></tr>
			<?php endif; ?>
			<?php foreach ( $shipments as $shipment ) :
				$items     = WPI_Shipment_Item::for_shipment( $shipment->id );
				$total_pre = array_sum( array_map( fn( $i ) => $i->qty_preordered, $items ) );
				$fmt       = get_option( 'wpi_date_format', 'd/m/Y' );
				$edit_url  = admin_url( 'admin.php?page=wpi-shipments&action=edit&id=' . $shipment->id );

				// Build a stock-impact summary so the modal can show what releasing will actually do.
				$preview = WPI_Release::preview_release( $shipment->id );
				$preview_lines = [];
				foreach ( $items as $item ) {
					$p     = wc_get_product( $item->product_id );
					$name  = $p ? $p->get_name() : 'Product #' . $item->product_id;
					$delta = $item->qty_allocated - $item->qty_preordered;
					if ( $delta >= 0 ) {
						$preview_lines[] = sprintf( '%s — +%d to stock (%d preordered, %d allocated)', $name, $delta, $item->qty_preordered, $item->qty_allocated );
					} else {
						$preview_lines[] = sprintf( '⚠ %s — OVERSOLD by %d (%d preordered, %d allocated)', $name, abs( $delta ), $item->qty_preordered, $item->qty_allocated );
					}
				}

				$released_label = '—';
				if ( $shipment->released_at ) {
					$user = $shipment->released_by ? get_userdata( $shipment->released_by ) : null;
					$released_label = sprintf(
						'%s<br><small>%s</small>',
						esc_html( date_i18n( $fmt . ' H:i', strtotime( $shipment->released_at ) ) ),
						esc_html( $user ? $user->display_name : __( 'Unknown user', 'wpi' ) )
					);
				}
			?>
			<tr>
				<td><a href="<?php echo esc_url( $edit_url ); ?>"><?php echo esc_html( $shipment->reference ); ?></a></td>
				<td><?php echo esc_html( date_i18n( $fmt, strtotime( $shipment->due_date ) ) ); ?></td>
				<td><?php echo esc_html( ucfirst( $shipment->status ) ); ?></td>
				<td><?php echo count( $items ); ?></td>
				<td><?php echo (int) $total_pre; ?></td>
				<td><?php echo $released_label; // already escaped above ?></td>
				<td>
					<a href="<?php echo esc_url( $edit_url ); ?>"><?php esc_html_e( 'Edit', 'wpi' ); ?></a>

					<?php if ( 'active' === $shipment->status ) : ?>
					&nbsp;|&nbsp;
					<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:inline" class="wpi-release-form">
						<?php wp_nonce_field( 'wpi_release_shipment' ); ?>
						<input type="hidden" name="action" value="wpi_release_shipment">
						<input type="hidden" name="shipment_id" value="<?php echo (int) $shipment->id; ?>">
						<button type="submit" class="button button-primary wpi-release-btn"
							data-ref="<?php echo esc_attr( $shipment->reference ); ?>"
							data-orders="<?php echo (int) $preview['order_count']; ?>"
							data-preview="<?php echo esc_attr( implode( "\n", $preview_lines ) ); ?>">
							<?php esc_html_e( 'Release Shipment', 'wpi' ); ?>
						</button>
					</form>
					<?php endif; ?>

					<?php if ( $total_pre === 0 && $shipment->status !== 'arrived' ) : ?>
					&nbsp;|&nbsp;
					<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:inline" class="wpi-delete-form">
						<?php wp_nonce_field( 'wpi_delete_shipment' ); ?>
						<input type="hidden" name="action" value="wpi_delete_shipment">
						<input type="hidden" name="shipment_id" value="<?php echo (int) $shipment->id; ?>">
						<button type="submit" class="button-link delete wpi-delete-btn"
							data-ref="<?php echo esc_attr( $shipment->reference ); ?>"
							style="color:#b32d2e">
							<?php esc_html_e( 'Delete', 'wpi' ); ?>
						</button>
					</form>
					<?php endif; ?>
				</td>
			</tr>
			<?php endforeach; ?>
		</tbody>
	</table>
</div>

<!-- Release confirmation modal -->
<div id="wpi-release-modal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,.5); z-index:9999; align-items:center; justify-content:center;">
	<div style="background:#fff; padding:28px 32px; border-radius:4px; max-width:600px; width:90%; max-height:85vh; overflow:auto; box-shadow:0 4px 24px rgba(0,0,0,.2);">
		<h2 style="margin-top:0" id="wpi-modal-title"></h2>

		<div class="wpi-warning" style="margin-bottom:16px">
			<strong><?php esc_html_e( 'Before releasing, confirm:', 'wpi' ); ?></strong>
			<ul style="margin:.5em 0 0 1.2em; padding:0; list-style:disc">
				<li><?php esc_html_e( 'The physical goods have cleared customs and biosecurity.', 'wpi' ); ?></li>
				<li><?php esc_html_e( 'The delivered products match the manifest SKUs exactly — no substitutions (e.g. ABC supplied as ABC-2).', 'wpi' ); ?></li>
				<li><?php esc_html_e( 'Quantities received match or exceed preorders placed.', 'wpi' ); ?></li>
			</ul>
		</div>

		<p style="font-weight:600; margin-bottom:4px"><?php esc_html_e( 'Stock impact preview:', 'wpi' ); ?></p>
		<pre id="wpi-modal-preview" style="background:#f6f7f7; padding:10px; font-size:12px; white-space:pre-wrap; border-radius:3px; margin-bottom:16px;"></pre>

		<p id="wpi-modal-orders" style="margin-bottom:8px"></p>
		<p style="margin-bottom:16px"><em><?php esc_html_e( 'Releasing will move these orders to Processing and notify customers by email. This cannot be undone.', 'wpi' ); ?></em></p>

		<p style="margin-bottom:0">
			<button id="wpi-modal-confirm" class="button button-primary"><?php esc_html_e( 'Yes — Release Shipment', 'wpi' ); ?></button>
			&nbsp;
			<button id="wpi-modal-cancel" class="button"><?php esc_html_e( 'Cancel', 'wpi' ); ?></button>
		</p>
	</div>
</div>

<script>
(function() {
	var modal       = document.getElementById('wpi-release-modal');
	var modalTitle  = document.getElementById('wpi-modal-title');
	var modalPreview = document.getElementById('wpi-modal-preview');
	var modalOrders = document.getElementById('wpi-modal-orders');
	var confirmBtn  = document.getElementById('wpi-modal-confirm');
	var cancelBtn   = document.getElementById('wpi-modal-cancel');
	var pendingForm = null;

	document.querySelectorAll('.wpi-release-btn').forEach(function(btn) {
		btn.addEventListener('click', function(e) {
			e.preventDefault();
			pendingForm = btn.closest('form');
			modalTitle.textContent   = <?php echo wp_json_encode( __( 'Release Shipment', 'wpi' ) ); ?> + ' ' + btn.dataset.ref + '?';
			modalPreview.textContent = btn.dataset.preview || <?php echo wp_json_encode( __( '(no items)', 'wpi' ) ); ?>;
			modalOrders.textContent  = btn.dataset.orders + ' ' + <?php echo wp_json_encode( __( 'preorder(s) will be moved to Processing.', 'wpi' ) ); ?>;
			modal.style.display = 'flex';
		});
	});

	confirmBtn.addEventListener('click', function() {
		modal.style.display = 'none';
		if (pendingForm) pendingForm.submit();
	});

	cancelBtn.addEventListener('click', function() {
		modal.style.display = 'none';
		pendingForm = null;
	});

	modal.addEventListener('click', function(e) {
		if (e.target === modal) {
			modal.style.display = 'none';
			pendingForm = null;
		}
	});

	// Plain confirm for destructive delete.
	document.querySelectorAll('.wpi-delete-btn').forEach(function(btn) {
		btn.addEventListener('click', function(e) {
			var msg = <?php echo wp_json_encode( __( 'Permanently delete shipment', 'wpi' ) ); ?> + ' ' + btn.dataset.ref + '? ' +
				<?php echo wp_json_encode( __( 'This cannot be undone.', 'wpi' ) ); ?>;
			if ( ! window.confirm( msg ) ) {
				e.preventDefault();
			}
		});
	});
})();
</script>
