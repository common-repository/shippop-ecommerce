/*global jQuery, Backbone, _, woocommerce_admin_api_keys, wcSetClipboard, wcClearClipboard */
(function( $ ) {
	jQuery( document ).ready(
		function ($) {
			$( "form#wp-list-table-report-cod-form #export_to_csv" ).on(
				'click',
				function (e) {
					e.preventDefault();
					var frm_elm = $( this ).closest( "form" );
					frm_elm.find( "input[name='action']" ).val( "export_to_csv" );
					frm_elm.submit();
				}
			);
		}
	);
})( jQuery );
