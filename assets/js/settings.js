/*global jQuery, Backbone, _, woocommerce_admin_api_keys, wcSetClipboard, wcClearClipboard */
(function ($) {

	$( "button[type='button'].specm-addr-corrector-auto" ).on(
		"click",
		function (e) {
			e.preventDefault();
			var frm_elm = $( this ).closest( "form" );
			$( frm_elm ).find( "input[type='submit']" ).click();
		}
	);

	function specm_check_input_ready() {
		$( ".specm-settings-input" )
		.each(
			function (index, item) {
				var val = $( item ).val();
				if (
				! $.trim( item.value ).length ||
				val.trim() === "" ||
				val.trim === ""
				) {
					// console.log( $(item).attr("id") );
					$( item ).focus();
					return false;
				}
			}
		);

		return true;
	}

	$( "button[type='button'].specm-addr-corrector" ).on(
		"click",
		function (e) {
			e.preventDefault();
			var frm_elm          = $( this ).closest( "form" );
			var address_name     = $( frm_elm ).find( "#address_name" );
			var address_tel      = $( frm_elm ).find( "#address_tel" );
			var address          = $( frm_elm ).find( "textarea#address_address" );
			var address_state    = $( frm_elm ).find( "#address_state" );
			var address_district = $( frm_elm ).find( "#address_district" );
			var address_province = $( frm_elm ).find( "#address_province" );
			var address_postcode = $( frm_elm ).find( "#address_postcode" );

			if ( $( address_name ).val() == "" ) {
				  $( address_name ).focus();
				  return false;
			}

			if ( $( address_tel ).val() == "" ) {
				$( address_tel ).focus();
				return false;
			}

			if ( $( address ).val() == "" ) {
				$( address ).focus();
				return false;
			}

			if ( $( address_state ).length && $( address_state ).val() == "" ) {
				$( address_state ).focus();
				return false;
			}

			if ( $( address_district ).length && $( address_district ).val() == "" ) {
				$( address_district ).focus();
				return false;
			}

			if ( $( address_province ).length && $( address_province ).val() == "" ) {
				$( address_province ).focus();
				return false;
			}

			if ( $( address_postcode ).length && $( address_postcode ).val() == "" ) {
				$( address_postcode ).focus();
				return false;
			}

			var billing_name    = $( frm_elm ).find( "#billing_name" );
			var billing_tax_id  = $( frm_elm ).find( "#billing_tax_id" );
			var billing_tel     = $( frm_elm ).find( "#billing_tel" );
			var billing_address = $( frm_elm ).find( "textarea#billing_address" );

			var body      = $( "body" );
			var modal_elm = $( "#shippop-settings-address-modal" );

			// if (address != "" && address != undefined) {
			modal_elm.find( "div.modal-content" ).html( "" );
			body.LoadingOverlay( "show" );
			var data = {
				action: "shippop_address_corrector",
				address: $( address ).val(),
				billing_address: $( billing_address ).val(),
				nonce: shippop_setting_js.nonce,
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

						var check_address_first = check_address_second = false;
						if (response.address_address_suggestion.status === false) {
							check_address_first = true
						}

						if (response.billing_address_suggestion.status === false) {
							check_address_second = true;
						}

						if ( check_address_first || check_address_second ) {
							if (check_address_first) {
								$( address ).css( {"border-color": "red"} );
								$( address ).closest( "td" ).find( "p" ).text( response.address_address_suggestion.message );
								$( address ).focus();
							}

							if (check_address_second) {
								$( billing_address ).css( {"border-color": "red"} );
								$( billing_address ).closest( "td" ).find( "p" ).text( response.billing_address_suggestion.message );
								$( billing_address ).focus();
							}

							return false;
						}

						if (response.address_address_suggestion.type == "1" && response.billing_address_suggestion.type == "1" ) {

							if ( response.billing_address_suggestion.suggestion ) {
								$( "#billing_address" ).val( response.billing_address_suggestion.suggestion[0].full );
							}

							specm_correct_address_append(
								$,
								frm_elm,
								{
									state: response.address_address_suggestion.suggestion[0].state,
									district: response.address_address_suggestion.suggestion[0].district,
									province: response.address_address_suggestion.suggestion[0].province,
									postcode: response.address_address_suggestion.suggestion[0].postcode,
									full: response.address_address_suggestion.suggestion[0].full
								},
								true
							);
							return true;
						} else {
							modal_elm.find( "div.modal-content" ).html( response.html );
							modal_elm.dialog(
								{
									show: { effect: "blind", duration: 100 },
									hide: { effect: "blind", duration: 300 },
									width: $( window ).width() * 0.6,
									// height: 300,
									dialogClass: "hideTitleDialog",
									draggable: false,
									resizable: false,
									closeText: "",
									open: function (event, ui) {
										$( "#specm_overlay" ).show();
										$( ".ui-dialog-titlebar-close", ui ).hide();
									},
									close: function (event, ui) {
										$( "#specm_overlay" ).hide();
									},
								}
							);
						}
					} else {
						console.log( response );
						specm_alert( "f", response.message, false, [] );
						return false;
					}
				}
			)
			.fail(
				function () {
					specm_alert( "f", specm_translate( "error" ), false, [] );
				}
			);
		}
	);

	$( "body" ).on(
		"click",
		"div#address-corrector-form button[type='button']",
		function (e) {
			var body                                  = $( "body" );
			var frm                                   = $( this ).data( "form-name" );
			var frm_elm                               = $( "form[name='" + frm + "']" );
			var radio_correct_address_checked         = $( "#address-corrector-form" ).find(
				"input[name='correct_address']:checked"
			);
			var radio_correct_address_billing_checked = $( "#address-corrector-form" ).find(
				"input[name='correct_address_billing']:checked"
			);

			var addr_obj = {
				state: $( radio_correct_address_checked ).data( "state" ),
				district: $( radio_correct_address_checked ).data( "district" ),
				province: $( radio_correct_address_checked ).data( "province" ),
				postcode: $( radio_correct_address_checked ).data( "postcode" ),
				full: $( radio_correct_address_checked ).data( "full" ),
			};

			body.LoadingOverlay( "show" );
			specm_correct_address_append( $, frm_elm, addr_obj, false );

			$( "#billing_address" ).val( $( radio_correct_address_billing_checked ).data( "full" ) );
			$( frm_elm ).find( "input[type='submit']" ).click();
		}
	);

	$( "body" ).on(
		"click",
		"button.specm-logout-btn",
		function (e) {
			var link = $( this ).closest( "div" ).find( "input[type='hidden']" ).val();
			specm_confirm_logout( link );
		}
	);

	if ($( "form[name='settings']" ).length > 0) {
		$( 'input[required], textarea[required]' ).each(
			function(index) {
				var txt = $( this ).closest( "tr" ).find( "th" ).text();
				$( this ).closest( "tr" ).find( "th" ).html( txt + "<span class='red-star-setting'>*</span>" );
			}
		);
	}

	$( "body" ).on(
		"keyup",
		".specm-settings-input",
		function(e) {
			e.preventDefault();
			var td = $( this ).closest( "td" );
			$( td ).find( "p" ).text( "" );
			$( this ).css( {"border-color": "black"} );
		}
	);

	$( "body" ).on(
		"click",
		"#address_billing_clone",
		function(e) {
			var frm_elm = $( this ).closest( "form" );
			if ( $( this ).is( ':checked' ) ) {
				var address_name = $( frm_elm ).find( "#address_name" ).val();
				var address_tel  = $( frm_elm ).find( "#address_tel" ).val();
				var address      = $( frm_elm ).find( "textarea#address_address" ).val();
			} else {
				var address_name = "";
				var address_tel  = "";
				var address      = "";
			}

			$( frm_elm ).find( "#billing_name" ).val( address_name );
			$( frm_elm ).find( "#billing_tel" ).val( address_tel );
			$( frm_elm ).find( "textarea#billing_address" ).val( address );
		}
	);

	$( "body" ).on(
		"click",
		"#advance_setting",
		function(e) {
			if ( $( this ).is( ':checked' ) ) {
				$(".hide-advance-setting").fadeIn();
			} else {
				$(".hide-advance-setting").fadeOut();
			}
		}
	);

})( jQuery );

/**
 * @return void
 */
function specm_correct_address_append($, frm_elm, data, submit) {
	$( frm_elm ).append(
		"<input type='hidden' value='" +
		data.state +
		"' name='specm_settings[address][state]'>" +
		"<input type='hidden' value='" +
		data.district +
		"' name='specm_settings[address][district]'>" +
		"<input type='hidden' value='" +
		data.province +
		"' name='specm_settings[address][province]'>" +
		"<input type='hidden' value='" +
		data.postcode +
		"' name='specm_settings[address][postcode]'>"
	);
	$( "#address_address" ).val( data.full );

	if (submit) {
		jQuery( frm_elm ).find( "input[type='submit']" ).click();
	}
}

function specm_confirm_logout(link) {
	jQuery( "#specm_overlay" ).show();
	jQuery( "#specm-dialog-confirm-logout div.modal-content div.modal-content-body" ).empty();

	jQuery( "#specm-dialog-confirm-logout div.modal-content div.modal-content-body" ).html( specm_translate( "confirm_logout" ) );
	jQuery( "#specm-dialog-confirm-logout div.modal-content div.modal-content-body" ).append(
		'<div class="clearfix"></div>'
	);

	jQuery( "#specm-dialog-confirm-logout" ).dialog(
		{
			draggable: false,
			title: specm_translate( "logout" ),
			resizable: false,
			height: "auto",
			dialogClass: "hideTitleDialog",
			width: 400,
			modal: false,
			buttons: [
			{
				text: specm_translate( "close" ),
				// class: "bg-shippop-close",
				click: function() {
					jQuery( this ).dialog( "close" );
					jQuery( "#specm_overlay" ).hide();
				}
			},
			{
				text: specm_translate( "logout" ),
				class: "bg-shippop-main-color",
				click: function() {
					window.location.href = link;
				}
			}
			]
		}
	);
}
