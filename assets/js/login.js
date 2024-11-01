/*global jQuery, Backbone, _, woocommerce_admin_api_keys, wcSetClipboard, wcClearClipboard */
(function ($) {
	$( "body" ).on(
		"click",
		"button.specm-register-btn",
		function (e) {
			$( "div.specm-wrapper-register" ).show();
			$( "div.specm-wrapper-login" ).hide();
		}
	);

	$( "body" ).on(
		"click",
		"span.back-to-login",
		function (e) {
			$( "div.specm-wrapper-login" ).show();
			$( "div.specm-wrapper-register" ).hide();
		}
	);

	$( "form.specm-form-register" ).on(
		"submit",
		function (e) {
			e.preventDefault();
			var frm_elm = $( this );
			var body    = $( "body" );
			// console.log ( frm_elm.serializeArray() );

			body.LoadingOverlay( "show" );

			var data = {
				action: "specm_shippop_register",
				nonce: shippop_login_js.nonce,
				shippop_company: $( frm_elm ).find( "input[name='shippop_company']" ).val(),
				shippop_name: $( frm_elm ).find( "input[name='shippop_name']" ).val(),
				shippop_tel: $( frm_elm ).find( "input[name='shippop_tel']" ).val(),
				shippop_email: $( frm_elm ).find( "input[name='shippop_email']" ).val(),
				shippop_courier: $( frm_elm ).find( "input[name='shippop_courier']" ).val(),
				shippop_server: $( frm_elm ).find( "select[name='shippop_server']" ).val()
			};

			jQuery
			.post(
				ajaxurl,
				data,
				function (response) {
					body.LoadingOverlay( "hide", true );
				}
			)
			.done(
				function (response) {
					body.LoadingOverlay( "hide", true );
					if (response.status) {
						var msg = '';
						msg     = '<h4>' + response.message + '</h4>';
						msg    += '<p class="txt-shippop-main-color">' + response.message2 + '</p>';
						specm_alert( "s", msg, true, [] );
					} else {
						specm_alert( "f", response.message, false, [] );
					}
				}
			)
			.fail(
				function (xhr) {
					specm_alert( "f", xhr.responseText, false, [] );
				}
			);
		}
	);
})( jQuery );
