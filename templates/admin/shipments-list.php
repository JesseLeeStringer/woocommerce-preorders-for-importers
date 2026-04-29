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
				<th><?php esc_html_e( 'Preorders Placed', 'wpi' ); ?></th>
				<th><?php esc_html_e( 'Actions', 'wpi' ); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php if ( ! $shipments ) : ?>
				<tr><td colspan="6"><?php esc_html_e( 'No shipments found. Add one to get started.', 'wpi' ); ?></td></tr>
			<?php endif; ?>
			<?php foreach ( $shipments as $shipment ) :
				$items     = WPI_Shipment_Item::for_shipment( $shipment->id );
				$total_pre = array_sum( array_map( fn( $i ) => $i->qty_preordered, $items ) );
				$fmt       = get_option( 'wpi_date_format', 'd/m/Y' );
				$edit_url  = admin_url( 'admin.php?page=wpi-shipment-edit&id=' . $shipment->id );

				// Build product names for the release confirmation dialog.
				$product_lines = [];
				foreach ( $items as $item ) {
					$p = wc_get_product( $item->product_id );
					$product_lines[] = ( $p ? $p->get_name() : 'Product #' . $item->product_id )
						. ' — ' . $item->qty_preordered . ' preorder(s), '
						. $item->qty_allocated . ' allocated';
				}
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
					<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:inline"
						  class="wpi-release-form">
						<?php wp_nonce_field( 'wpi_release_shipment' ); ?>
						<input type="hidden" name="action" value="wpi_release_shipment">
						<input type="hidden" name="shipment_id" value="<?php echo (int) $shipment->id; ?>">
						<button type="submit" class="button button-primary wpi-release-btn"
							data-ref="<?php echo esc_attr( $shipment->reference ); ?>"
							data-orders="<?php echo (int) $total_pre; ?>"
							data-products="<?php echo esc_attr( implode( "\n", $product_lines ) ); ?>">
							<?php esc_html_e( 'Release Shipment', 'wpi' ); ?>
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
	<div style="background:#fff; padding:28px 32px; border-radius:4px; max-width:540px; width:90%; box-shadow:0 4px 24px rgba(0,0,0,.2);">
		<h2 style="margin-top:0" id="wpi-modal-title"></h2>

		<div class="wpi-warning" style="margin-bottom:16px">
			<strong><?php esc_html_e( 'Before releasing, confirm:', 'wpi' ); ?></strong>
			<ul style="margin:.5em 0 0 1.2em; padding:0; list-style:disc">
				<li><?php esc_html_e( 'The physical goods have cleared customs and biosecurity.', 'wpi' ); ?></li>
				<li><?php esc_html_e( 'The delivered products match the manifest SKUs exactly — no substitutions (e.g. ABC supplied as ABC-2).', 'wpi' ); ?></li>
				<li><?php esc_html_e( 'Quantities received match or exceed preorders placed.', 'wpi' ); ?></li>
			</ul>
		</div>

		<p style="font-weight:600; margin-bottom:4px"><?php esc_html_e( 'Products in this shipment:', 'wpi' ); ?></p>
		<pre id="wpi-modal-products" style="background:#f6f7f7; padding:10px; font-size:12px; white-space:pre-wrap; border-radius:3px; margin-bottom:16px;"></pre>

		<p><?php esc_html_e( 'Releasing will move all preorders to Processing and notify customers by email. This cannot be undone.', 'wpi' ); ?></p>

		<p style="margin-bottom:0">
			<button id="wpi-modal-confirm" class="button button-primary"><?php esc_html_e( 'Yes — Release Shipment', 'wpi' ); ?></button>
			&nbsp;
			<button id="wpi-modal-cancel" class="button"><?php esc_html_e( 'Cancel', 'wpi' ); ?></button>
		</p>
	</div>
</div>

<script>
(function() {
	var modal        = document.getElementById('wpi-release-modal');
	var modalTitle   = document.getElementById('wpi-modal-title');
	var modalProducts = document.getElementById('wpi-modal-products');
	var confirmBtn   = document.getElementById('wpi-modal-confirm');
	var cancelBtn    = document.getElementById('wpi-modal-cancel');
	var pendingForm  = null;

	document.querySelectorAll('.wpi-release-btn').forEach(function(btn) {
		btn.addEventListener('click', function(e) {
			e.preventDefault();
			pendingForm = btn.closest('form');
			var ref     = btn.dataset.ref;
			var orders  = btn.dataset.orders;
			modalTitle.textContent  = 'Release Shipment ' + ref + '?';
			modalProducts.textContent = btn.dataset.products;
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
})();
</script>
