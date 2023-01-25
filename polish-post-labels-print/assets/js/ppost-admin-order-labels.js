'use strict';
jQuery(document).ready(function ($) {
    $(document).on('click', '.woo-ppost_envelope_print', function () {
        let $button = $(this);		
		
		$.ajax({
			url: ppost_admin_order_labels.ajax_url,
			type: 'POST',
			data: {
				action: 'ppost_envelope_print_pdf',
				order_id: $button.data('order_id'),
			},
			success: function (response) {
				var json = $.parseJSON(response);
				window.open(json.path, '_blank');
			},
			error: function (response) {
                console.log(response);
			}
		});
    });
	
    $(document).on('click', '.woo-ppost_post_confirmation_print', function () {
        let $button = $(this);		
		
		$.ajax({
			url: ppost_admin_order_labels.ajax_url,
			type: 'POST',
			data: {
				action: 'ppost_post_confirmation_print_pdf',
				order_id: $button.data('order_id'),
			},
			success: function (response) {
				var json = $.parseJSON(response);
				window.open(json.path, '_blank');
			},
			error: function (response) {
                console.log(response);
			}
		});
    });
});