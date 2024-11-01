/*global jQuery, Backbone, _, woocommerce_admin_api_keys, wcSetClipboard, wcClearClipboard */
(function ($) {
	$.LoadingOverlaySetup(
		{
			image: "",
			fontawesome: "fa fa-circle-o-notch fa-spin",
			size: 40,                                // Float/String/Boolean
			minSize : 20,                                // Integer/String
			maxSize: 40
		}
	);

	// MAIN FUNCTION TO WP TABLE LIST

	$( "body" ).on(
		"click",
		"div.modal-shippop div div.modal-close, button.alert-modal-button-close",
		function (e) {
			if ( $( this ).data( "reload" ) != undefined && $( this ).data( "reload" ) != "" ) {
				window.location.reload();
			}
			var momdal_elm = $( this ).closest( "div.modal-shippop " ).attr( "id" );
			$( "#specm_overlay" ).hide();
			$( "#" + momdal_elm ).dialog( "destroy" );
			$( ".ui-dialog-content" ).dialog( "destroy" );
		}
	);

	var from = $( 'input[name="daterange_start"]' )
	.datepicker(
		{
			dateFormat: "dd/mm/yy",
			// defaultDate: "+1w",
			changeMonth: true,
			numberOfMonths: 2,
			showButtonPanel: true,
			beforeShow: function (input) {
				setTimeout(
					function () {
						var buttonPane = $( input )
						.datepicker( "widget" )
						.find( ".ui-datepicker-buttonpane" );

						$(
							"<button>",
							{
								text: "Clear",
								click: function () {
									// from._clearDate(input);
									// to._clearDate(input);
									$( from ).val( "" );
									$( to ).val( "" );
									$( ".specm_on_change_submit" ).change();
								},
							}
						)
						.appendTo( buttonPane )
						.addClass(
							"ui-datepicker-clear ui-state-default ui-priority-primary ui-corner-all"
						);
					},
					1
				);
			},
			onSelect: function () {
				to.val( "" );
			},
		}
	)
	.on(
		"change",
		function () {
			to.datepicker( "option", "minDate", specm_getDate( this ) );
		}
	);
	var to = $( 'input[name="daterange_end"]' )
	.datepicker(
		{
			dateFormat: "dd/mm/yy",
			// defaultDate: "+1w",
			changeMonth: true,
			numberOfMonths: 2,
			showButtonPanel: true,
			beforeShow: function (input) {
				setTimeout(
					function () {
						var buttonPane = $( input )
						.datepicker( "widget" )
						.find( ".ui-datepicker-buttonpane" );

						$(
							"<button>",
							{
								text: "Clear",
								click: function () {
									$( from ).val( "" );
									$( to ).val( "" );
									$( ".specm_on_change_submit" ).change();
								},
							}
						)
						.appendTo( buttonPane )
						.addClass(
							"ui-datepicker-clear ui-state-default ui-priority-primary ui-corner-all"
						);
					},
					1
				);
			},
			onSelect: function () {
				if (from.val() != undefined && from.val() != "" && to.val() != undefined && to.val() != "") {
					$( this ).closest( "form" ).submit();
				}
			},
		}
	)
	.on(
		"change",
		function () {
			from.datepicker( "option", "maxDate", specm_getDate( this ) );
		}
	);

	$( "select.update_post_per_page" ).on(
		"change",
		function (e) {
			var value_option = $( this ).val();
			var id           = $( this ).data( "option-name" );

			if (value_option != undefined && id != undefined) {
				$( "#" + id ).val( value_option );
				$( "#" + id )
				  .closest( "form" )
				  .submit();
			}
		}
	);

	$( ".specm_on_change_submit" ).on(
		"change",
		function (e) {
			e.preventDefault();
            var _this = $(this);
            var form = _this.closest("form");
            var form_data = form.serializeArray();
            var exclude = ['_wpnonce', '_wp_http_referer', 'action', 'paged', 'action2'];
            form_data.forEach(element => {
                if (exclude.includes(element.name) == false) {
                    var newurl = window.location.href + '&' + element.name + '=' + element.value;
                    window.history.pushState({ path: newurl }, '', newurl);
                }

            });

			$( this ).closest( "form" ).submit();
		}
	);

	if (
	$( "form.shippop-wp-list-table div.tablenav div.bulkactions select" )
	  .length == 0
	) {
		$( "form.shippop-wp-list-table div.tablenav div.bulkactions" ).remove();
	}

	// MAIN FUNCTION TO WP TABLE LIST
})( jQuery );

var getUrlParameter = function getUrlParameter(sParam) {
    var sPageURL = window.location.search.substring(1),
        sURLVariables = sPageURL.split('&'),
        sParameterName,
        i;

    for (i = 0; i < sURLVariables.length; i++) {
        sParameterName = sURLVariables[i].split('=');

        if (sParameterName[0] === sParam) {
            return typeof sParameterName[1] === undefined ? true : decodeURIComponent(sParameterName[1]);
        }
    }
    return false;
};

function specm_translate(key) {
	var translate = shippop_main_js.translate;
	return translate[key] == undefined ? "" : translate[key];
}

function specm_getDate(element) {
	var dateFormat = "dd/mm/yy";
	var date;
	try {
		date = jQuery.datepicker.parseDate( dateFormat, element.value );
	} catch (error) {
		date = null;
	}

	return date;
}

function specm_alert(type, msg, reload, buttons) {
	jQuery( "#specm_overlay" ).show();
	jQuery( "#specm-dialog-message div.modal-content div.modal-content-body" ).empty();
	jQuery( "#specm-dialog-message div.modal-content div.md-success" ).hide();
	jQuery( "#specm-dialog-message div.modal-content div.md-fail" ).hide();
	if (type == "s") {
		jQuery( "#specm-dialog-message div.modal-content div.md-success" ).show();
	} else {
		jQuery( "#specm-dialog-message div.modal-content div.md-fail" ).show();
	}

	jQuery( "#specm-dialog-message div.modal-content div.modal-content-body" ).html( msg );
	jQuery( "#specm-dialog-message div.modal-content div.modal-content-body" ).append(
		'<div class="clearfix"></div>'
	);

	var tt = '';
	if (reload) {
		tt = 'data-reload="true"';
		jQuery( "#specm-dialog-message div div.modal-close" ).attr( "data-reload", "true" );
	}
	jQuery( "#specm-dialog-message div.modal-content div.modal-content-body" ).append(
		'<button class="button alert-modal-button-close" ' + tt + '>' + specm_translate( "close" ) + '</button>'
	);
	jQuery.each(
		buttons,
		function(k, v) {
			jQuery( "#specm-dialog-message div.modal-content div.modal-content-body" ).append(
				'<a href="' + v.href + '" target="' + v.target + '"><button class="button alert-modal-button-other">' + v.title + '</button></a>'
			);
		}
	);

	jQuery( "#specm-dialog-message" ).dialog(
		{
			draggable: false,
			width: jQuery( window ).width() * 0.3,
			close: function (event, ui) {
				jQuery( "#specm_overlay" ).hide();
				jQuery( ".ui-dialog-content" ).dialog( "close" );
				if (reload) {
					window.location.reload();
				}
			},
			dialogClass: "hideTitleDialog"
		}
	);
}

function specm_alert_confirm(title, msg, yes_callback) {
	jQuery( "#specm_overlay" ).show();
	jQuery( "#specm-dialog-confirm div.modal-content div.modal-content-body" ).empty();

	jQuery( "#specm-dialog-confirm div.modal-content div.modal-content-body" ).html( msg );
	jQuery( "#specm-dialog-confirm div.modal-content div.modal-content-body" ).append(
		'<div class="clearfix"></div>'
	);

	jQuery( "#specm-dialog-confirm" ).dialog(
		{
			draggable: false,
			title: title,
			resizable: false,
			height: "auto",
			dialogClass: "hideTitleDialog",
			width: 400,
			modal: false,
			buttons: [
			{
				text: specm_translate( "cancel" ),
				click: function() {
					jQuery( this ).dialog( "close" );
					jQuery( "#specm_overlay" ).hide();
				}
			},
			{
				text: specm_translate( "ok" ),
				class: "bg-shippop-main-color",
				click: yes_callback
			}
			]
		}
	);
}

function specm_removeParam(key, sourceURL) {
    var rtn = sourceURL.split("?")[0],
        param,
        params_arr = [],
        queryString = (sourceURL.indexOf("?") !== -1) ? sourceURL.split("?")[1] : "";
    if (queryString !== "") {
        params_arr = queryString.split("&");
        for (var i = params_arr.length - 1; i >= 0; i -= 1) {
            param = params_arr[i].split("=")[0];
            if (param === key) {
                params_arr.splice(i, 1);
            }
        }
        if (params_arr.length) rtn = rtn + "?" + params_arr.join("&");
    }
    return rtn;
}