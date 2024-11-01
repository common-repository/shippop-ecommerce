/*global jQuery, Backbone, _, woocommerce_admin_api_keys, wcSetClipboard, wcClearClipboard */
(function( $ ) {
	jQuery( document ).ready(
		function ($) {
			$( "a.shippop_tracking_code" ).each(
				function() {
					$( this ).attr( "target", "_blank" );
				}
			);
		}
	);
})( jQuery );
