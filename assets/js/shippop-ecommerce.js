/*global jQuery, Backbone, _, woocommerce_admin_api_keys, wcSetClipboard, wcClearClipboard */
(function ($) {

  jQuery(document).ready(function ($) {

    // get confirm purchase
    if ( getUrlParameter('success') == 1 && getUrlParameter('hash') !== false ) {
      var hash = getUrlParameter('hash');
      var body = $("body");
      // var modal_elm = $("#shippop-ecommerce-modal");

      body.LoadingOverlay("show");
      var data = {
        action: "shippop_ecommerce_tracking_purchase",
        hash: hash,
        nonce: js_object.nonce,
      };

      jQuery
        .post(ajaxurl, data, function (response) {
          // $(modal_elm).dialog("destroy");
          body.LoadingOverlay("hide", true);
        })
        .done(function (response) {
          body.LoadingOverlay("hide", true);
          if (response.status) {
            var msg = '';
            msg = '<h4>'+ response.message +'</h4>';
            msg += '<p class="txt-shippop-main-color">'+ response.message2 +'</p>';
            var reset = specm_removeParam( 'success' , window.location.href );
            reset = specm_removeParam( 'hash' , reset );
            window.history.replaceState(null, null, reset);
            specm_alert("s", msg, true, [{
              'title': specm_translate("print_label"),
              'href': shippop_main_js.list_parcel_url,
              'target': '_self'
            }]);
          } else {
              specm_alert("f", response.message, false, []);
              return false;
          }
        })
        .fail(function () {
          specm_alert("f", specm_translate("error"), false, []);
        });

    }

    $("form#wp-list-table-shippop-ecommerce-form table input[type='checkbox']").on("click", function() {
      var frm_elm = $(this).closest("form");
      var btn_get_list = $("button.specm-get-list-choose-courier");
      var label_warning = $(btn_get_list).closest("div").find("label");

      setTimeout(() => {
        var input_bulk = frm_elm
        .find("input[name='bulk_id[]']:checked")
        .serializeArray();
        if (input_bulk.length > 0) {
            $(btn_get_list).removeAttr("disabled");
            $(btn_get_list).css({"cursor": "pointer"});
            $(label_warning).hide();
        } else {
          $(btn_get_list).attr("disabled", "disabled");
          $(btn_get_list).css({"cursor": "not-allowed"});
          $(label_warning).show();
        }
      }, 1);
    });

    $("body").on("click", "button.button-select-courier", function (e) {
      var btn_elm = $(this);
      var txt = $(btn_elm).text();

      var active = $(this).hasClass("active-select-courier");
      if (active) {
        $(btn_elm).closest("table").find(".active-select-courier").html(txt);
        $(btn_elm).removeClass("active-select-courier");
        $(btn_elm).closest("div#shippop-ecommerce-modal").find("input#select_courier").val("");
        $(btn_elm).closest("div.modal-content").find("div.specm-modal-footer").hide();
      } else {
        // clear other button
        $(btn_elm).closest("table").find(".active-select-courier").html(txt);
        $(btn_elm).closest("table").find(".active-select-courier").removeClass("active-select-courier");
        $(btn_elm).closest("div#shippop-ecommerce-modal").find("input#select_courier").val("");

        // set
        $(btn_elm).addClass("active-select-courier");
        $(btn_elm).closest("div#shippop-ecommerce-modal").find("input#select_courier").val($(btn_elm).data("courier-code"));
        $(btn_elm).html('<i class="fa fa-check" aria-hidden="true"></i>' + " " + txt);
        $(btn_elm).closest("div.modal-content").find("div.specm-modal-footer").show();
      }
    });

    $("body").on("click", "button.button-shippop-cancel", function (e) {
      $("div.modal-shippop div div.modal-close").click();
    });

    $("body").on("click", "button.specm-get-list-choose-courier", function(e) {
      var frm_elm = $(this).closest("form");
      var modal_elm = $("#shippop-ecommerce-modal");
      var body = $("body");
      var cc = ( $(this).data("courier-code") ) ? $(this).data("courier-code") : null;

      var input_bulk = frm_elm
        .find("input[name='bulk_id[]']:checked")
        .serializeArray();
      if (input_bulk.length > 0) {

        var order_ids = "";
        $.each(input_bulk, function (k, v) {
          if (k != 0) {
            order_ids += ",";
          }
          order_ids += v.value;
        });

        modal_elm.find("div.modal-content").html("");
        e.preventDefault();
        body.LoadingOverlay("show");
        var data = {
          action: "shippop_ecommerce_choose_courier",
          order_ids: order_ids,
          cc: cc,
          nonce: js_object.nonce,
        };

        jQuery
          .post(ajaxurl, data, function (response) {
            body.LoadingOverlay("hide", true);
          })
          .done(function (response) {
            body.LoadingOverlay("hide", true);
            if (response.status) {
              modal_elm.find("div.modal-content").html(response.html);
              modal_elm.dialog({
                show: { effect: "blind", duration: 100 },
                hide: { effect: "blind", duration: 300 },
                width: $(window).width() * 0.7,
                height: 520,
                dialogClass: "hideTitleDialog",
                draggable: false,
                resizable: false,
                closeText: "",
                open: function (event, ui) {
                  $("#specm_overlay").show();
                  $(".ui-dialog-titlebar-close", ui).hide();
                },
                close: function (event, ui) {
                  $("#specm_overlay").hide();
                },
              });
            } else {
              specm_alert("f", response.message, false, []);
            }
          })
          .fail(function () {
            specm_alert("f", specm_translate("error"), false, []);
          });
      } else {
        return false;
      }
    });

    $("body").on(
      "click",
      "form#wp-shippop-ecommerce-booking button[type='submit']",
      function (e) {
        var frm_elm = $(this).closest("form");
        var modal_elm = $("#shippop-ecommerce-modal");
        var select_courier = frm_elm.find("#select_courier").val();
        var order_ids = frm_elm.find("#order_ids").val();
        var body = $("body");

        if (select_courier == "" || order_ids == "") {
          return false;
        } else {
          e.preventDefault();
          body.LoadingOverlay("show");
          var data = {
            action: "shippop_ecommerce_booking_courier",
            order_ids: order_ids,
            select_courier: select_courier,
            nonce: js_object.nonce,
          };

          jQuery
            .post(ajaxurl, data, function (response) {
              $(modal_elm).dialog("destroy");
              body.LoadingOverlay("hide", true);
            })
            .done(function (response) {
              body.LoadingOverlay("hide", true);
              if (response.status) {
                if (response.redirect != undefined && response.redirect != "") {
                  window.location.href = response.redirect;
                  return;
                } else {
                  var msg = '';
                  msg = '<h4>'+ response.message +'</h4>';
                  msg += '<p class="txt-shippop-main-color">'+ response.message2 +'</p>';
                  specm_alert("s", msg, true, [{
                    'title': specm_translate("print_label"),
                    'href': shippop_main_js.list_parcel_url,
                    'target': '_self'
                  }]);
                }
              } else {
                  specm_alert("f", response.message, false, []);
                  return false;
              }
            })
            .fail(function () {
              specm_alert("f", specm_translate("error"), false, []);
            });
        }
      });

  });
})(jQuery);
