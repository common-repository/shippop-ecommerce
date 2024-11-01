/*global jQuery, Backbone, _, woocommerce_admin_api_keys, wcSetClipboard, wcClearClipboard */
(function ($) {
  jQuery(document).ready(function ($) {

    if ($("form#wp-list-table-parcel-shipping-form ul.subsubsub").length > 0) {
      var data = {
        action: "parcel_shipping_count_data_status",
        nonce: ps_js_object.nonce,
      };

      jQuery
        .post(ajaxurl, data, function (response) {})
        .done(function (response) {
          if (response.status) {
            $.each(response.data_count, function (k, v) {
              $(
                "form#wp-list-table-parcel-shipping-form ul.subsubsub li.sp-" +
                  k +
                  " a span.count"
              ).text("(" + v + ")");
            });
          }
        })
        .fail(function () {
          specm_alert("f", specm_translate("error"), false, []);
        });
    }

    $("form#wp-list-table-parcel-shipping-form table input[type='checkbox']").on("click", function() {
      var frm_elm = $(this).closest("form");
      var printlabel_size = $("select#printlabel_size");
      var button_tracking = $("button#manual_tracking");
      var label_warning = $(printlabel_size).closest("div").find("label");

      setTimeout(() => {
        var input_bulk = frm_elm
        .find("input[name='bulk_id[]']:checked")
        .serializeArray();
        if (input_bulk.length > 0) {
            $(printlabel_size).removeAttr("disabled");
            $(printlabel_size).css({"cursor": "pointer"});
            $(label_warning).hide();

            $(button_tracking).removeAttr("disabled");
            $(button_tracking).css({"cursor": "pointer"});
          } else {
            $(printlabel_size).attr("disabled", "disabled");
            $(printlabel_size).css({"cursor": "not-allowed"});
            $(label_warning).show();

            $(button_tracking).attr("disabled", "disabled");
            $(button_tracking).css({"cursor": "not-allowed"});
          }
      }, 1);
    });

    $("form#wp-list-table-parcel-shipping-form a.check-tracking-code").on(
      "click",
      function (e) {
        var modal_elm = $("#shippop-ecommerce-modal");
        var tracking_code = $(this).data("tracking-code");
        var order_id = $(this).data("order-id");
        var body = $("body");

        if (tracking_code != "" && order_id != "") {
          modal_elm.find("div.modal-content").html("");
          e.preventDefault();

          $("#specm_overlay").show();
          body.LoadingOverlay("show");
          
          var data = {
            action: "parcel_shipping_tracking",
            order_id: order_id,
            tracking_code: tracking_code,
            nonce: ps_js_object.nonce,
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
                  width: 670,
                  position: {
                    my: "center",
                    at: "center",
                    of: window,
                  },
                  dialogClass: "hideTitleDialog",
                  draggable: false,
                  resizable: false,
                  closeText: "",
                  open: function (event, ui) {
                    $(".ui-dialog-titlebar-close", ui).hide();
                  },
                  close: function (event, ui) {
                    $("#specm_overlay").hide();
                  }
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
      }
    );

    $("form#wp-list-table-parcel-shipping-form #printlabel_size").on(
      "change",
      function (e) {
        e.preventDefault();
        var printlabel_size = $(this).val();
        var frm_elm = $(this).closest("form");
        var body = $("body");
        var input_bulk = frm_elm
          .find("input[name='bulk_id[]']:checked")
          .serializeArray();

        var bulk_ids = [];
        if (input_bulk.length > 0) {
          e.preventDefault();

          $("#specm_overlay").show();
          body.LoadingOverlay("show");
          
          $(frm_elm).find("input[name='bulk_id[]']:checked").each(function (index, item) {
            var val = $(item).val();
            bulk_ids.push(val);
          })
          .promise()
          .done(function () {
            var data = {
              action: "parcel_shipping_print_label",
              bulk_id: bulk_ids,
              nonce: ps_js_object.nonce,
              printlabel_size: printlabel_size
            };
  
            jQuery
              .post(ajaxurl, data, function (response) {
                body.LoadingOverlay("hide", true);
                $("#specm_overlay").hide();
              })
              .done(function (response) {
                body.LoadingOverlay("hide", true);
                if (response.status) {
                  var dtn = Date.now();
                  var html = '<a href="'+ response.file_url +'" target="_blank">';
                  html += '<button style="display: none;" id="' + dtn + '"  type="button">x</button>';
                  html += '</a>';
                  $(body).append(html);
                  $("#" + dtn).click();
                } else {
                  specm_alert("f", response.message, false, []);
                }
              })
              .fail(function () {
                specm_alert("f", specm_translate("error"), false, []);
              });  
          });
        } else {
          specm_alert("f", specm_translate("please_select_order"), false, []);
        }

        setTimeout(() => {
          $(this).val("");
        }, 1500);
      }
    );

    $("form#wp-list-table-parcel-shipping-form #export_to_csv").on(
      "click",
      function (e) {
        e.preventDefault();
        var frm_elm = $(this).closest("form");
        frm_elm.find("input[name='action']").val("export_to_csv");
        frm_elm.submit();
      }
    );

    $("form#wp-list-table-parcel-shipping-form button#manual_tracking").on(
      "click",
      function (e) {
        e.preventDefault();
        var frm_elm = $(this).closest("form");
        var body = $("body");
        var input_bulk = frm_elm
          .find("input[name='bulk_id[]']:checked")
          .serializeArray();
        
        var bulk_ids = {};
        if (input_bulk.length > 0) {
          e.preventDefault();

          $("#specm_overlay").show();
          body.LoadingOverlay("show");
          
          $(frm_elm).find("input[name='bulk_id[]']:checked").each(function (index, item) {
            var order_id = $(item).val();
            bulk_ids[ order_id ] = $(item).closest("tr").find("td.tracking_code a").text().trim();
          })
          .promise()
          .done(function () {
            console.log( bulk_ids );
            var data = {
              action: "parcel_shipping_tracking_multiple",
              bulk_ids: bulk_ids,
              nonce: ps_js_object.nonce
            };
  
            jQuery
              .post(ajaxurl, data, function (response) {
                body.LoadingOverlay("hide", true);
                $("#specm_overlay").hide();
              })
              .done(function (response) {
                body.LoadingOverlay("hide", true);
                // console.log(response);
                if (response.status) {
                  specm_alert( "s", response.message, true, [] );
                } else {
                  specm_alert( "f", response.message, false, [] );
                }
              })
              .fail(function () {
                specm_alert("f", specm_translate("error"), false, []);
              });
          });
        } else {
          specm_alert("f", specm_translate("please_select_order"), false, []);
        }

        setTimeout(() => {
          $(this).val("");
        }, 1500);
      }
    );

    $("form#wp-list-table-parcel-shipping-form button.purchase-cancel").on(
      "click",
      function (e) {
        e.preventDefault();
        var modal_elm = $("#specm-dialog-confirm");
        var frm_elm = $(this).closest("form");
        var tracking_code = $(this).data("tracking-code");
        var order_id = $(this).data("order-id");
        var body = $("body");

        specm_alert_confirm(
          specm_translate("confirm"),
          ps_js_object.confirm_cancel,
          function () {
            frm_elm.find("input[name='action']").val("bulk_purchase_cancel");

            $("#specm_overlay").show();
            body.LoadingOverlay("show");

            var data = {
              action: "parcel_shipping_purchase_cancel",
              nonce: ps_js_object.nonce,
              tracking_code: tracking_code,
              order_id: order_id,
            };

            jQuery
              .post(ajaxurl, data, function (response) {
                $(modal_elm).dialog("destroy");
                body.LoadingOverlay("hide", true);
              })
              .done(function (response) {
                body.LoadingOverlay("hide", true);
                if (response.status) {
                  specm_alert("s", response.message, true, []);
                } else {
                  specm_alert("f", response.message, false, []);
                }
              })
              .fail(function (xhr) {
                specm_alert("f", xhr.responseText, false, []);
              });
          });
      }
    );

  });
})(jQuery);
