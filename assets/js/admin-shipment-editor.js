/* global jQuery */
(function ($) {
	'use strict';

	// Add product row.
	$(document).on('click', '.wpi-add-row', function () {
		var template = $('#wpi-item-row-template').html();
		var index    = $('.wpi-item-row').length;
		template     = template.replace(/\[__INDEX__\]/g, '[' + index + ']');
		$('#wpi-items-body').append('<tr class="wpi-item-row">' + template + '</tr>');
	});

	// Remove product row.
	$(document).on('click', '.wpi-remove-row', function () {
		$(this).closest('tr').remove();
		recalculate_all();
	});

	// Recalculate deposit amount when price/type/value changes.
	$(document).on('input change', '.wpi-price, .wpi-deposit-type, .wpi-deposit-value', function () {
		recalculate($(this).closest('tr'));
	});

	function recalculate($row) {
		var price   = parseFloat($row.find('.wpi-price').val()) || 0;
		var type    = $row.find('.wpi-deposit-type').val();
		var value   = parseFloat($row.find('.wpi-deposit-value').val()) || 0;
		var deposit = type === 'percent' ? price * (value / 100) : value;
		var balance = price - deposit;

		$row.find('.wpi-derived-deposit').text(wpi_format_price(deposit));
		$row.find('.wpi-derived-balance').text(wpi_format_price(balance));
	}

	function recalculate_all() {
		$('.wpi-item-row').each(function () {
			recalculate($(this));
		});
	}

	// Release confirmation modal.
	$(document).on('click', '.wpi-release-btn', function (e) {
		var ref   = $(this).data('ref');
		var count = $(this).data('orders');
		if ( ! window.confirm('Release shipment ' + ref + '? This will move ' + count + ' preorder(s) to Processing and notify customers.') ) {
			e.preventDefault();
		}
	});

	recalculate_all();

}(jQuery));
