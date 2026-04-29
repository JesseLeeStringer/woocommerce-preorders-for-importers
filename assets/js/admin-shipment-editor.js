/* global jQuery */
(function ($) {
	'use strict';

	function initProductSearch($scope) {
		// WC's enhanced-select self-initializes on .wc-product-search at script load,
		// but dynamically-added rows need a manual nudge.
		if ( typeof $.fn.selectWoo === 'function' ) {
			$scope.find('.wc-product-search').filter(function () {
				return ! $(this).hasClass('enhanced');
			}).each(function () {
				var $select = $(this);
				$select.selectWoo({
					ajax: {
						url:         (window.wc_enhanced_select_params && wc_enhanced_select_params.ajax_url) || ajaxurl,
						dataType:    'json',
						delay:       250,
						data: function (params) {
							return {
								term:     params.term,
								action:   $select.data('action') || 'woocommerce_json_search_products',
								security: (window.wc_enhanced_select_params && wc_enhanced_select_params.search_products_nonce) || ''
							};
						},
						processResults: function (data) {
							var terms = [];
							if ( data ) {
								$.each(data, function (id, text) {
									terms.push({ id: id, text: text });
								});
							}
							return { results: terms };
						},
						cache: true
					},
					placeholder:        $select.data('placeholder') || '',
					minimumInputLength: 2,
					escapeMarkup:       function (m) { return m; },
					allowClear:         $select.data('allow_clear') !== false
				}).addClass('enhanced');
			});
		}
	}

	// Add product row.
	$(document).on('click', '.wpi-add-row', function () {
		var template = $('#wpi-item-row-template').html();
		var index    = $('.wpi-item-row').length;
		template     = template.replace(/__INDEX__/g, index);
		var $row     = $('<tr class="wpi-item-row">' + template + '</tr>');
		$('#wpi-items-body').append($row);
		initProductSearch($row);
	});

	// Remove product row.
	$(document).on('click', '.wpi-remove-row', function () {
		$(this).closest('tr').remove();
	});

	// Recalculate deposit + balance when price/type/value changes.
	$(document).on('input change', '.wpi-price, .wpi-deposit-type, .wpi-deposit-value', function () {
		recalculate($(this).closest('tr'));
	});

	function recalculate($row) {
		var price   = parseFloat($row.find('.wpi-price').val()) || 0;
		var type    = $row.find('.wpi-deposit-type').val();
		var value   = parseFloat($row.find('.wpi-deposit-value').val()) || 0;
		var deposit = type === 'percent' ? price * (value / 100) : value;
		var balance = price - deposit;

		// Use the browser's locale for the live preview — server-side values still drive the saved record.
		var fmt = function (n) {
			return n.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
		};
		$row.find('.wpi-derived-deposit').text(fmt(deposit));
		$row.find('.wpi-derived-balance').text(fmt(balance));
	}

	// Initial setup for any pre-rendered rows that haven't been enhanced yet.
	$(function () {
		initProductSearch($(document));
	});

}(jQuery));
