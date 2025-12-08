var barcodeActionsInitted = false;

jQuery(document).ready(function () {
  const barcodeActionsInit = function () {
    if (barcodeActionsInitted === true) return;

    let els = "#barcodes-import-products";
    els += ",#barcodes-import-categories";
    els += ",#barcodes-import-orders";
    els += ",#barcodes-import-orders-products";
    els += ",#barcodes-import-cf-messages";
    els += ",#barcodes-import-orders-items";

    jQuery(els).mouseover(function (e) {
      jQuery(this).parent().find("#barcodes-tooltip").addClass("show");
    });
    jQuery(els).mouseout(function (e) {
      jQuery(this).parent().find("#barcodes-tooltip").removeClass("show");
    });

    barcodeActionsInitted = true;
  };

  const barcodeCheckHash = function (hash) {
    if (hash === "#b=products") {
      const el = jQuery("#barcodes-import-products");
      el.parent().find("#barcodes-tooltip").addClass("show");
    }
    if (hash === "#b=categories") {
      const el = jQuery("#barcodes-import-categories");
      el.parent().find("#barcodes-tooltip").addClass("show");
    }
    if (hash === "#b=orders") {
      const el = jQuery("#barcodes-import-orders");
      el.parent().find("#barcodes-tooltip").addClass("show");
    }
    if (hash === "#b=orders-products") {
      const el = jQuery("#barcodes-import-orders-products");
      el.parent().find("#barcodes-tooltip").addClass("show");
    }
    if (hash === "#b=cf-messages") {
      const el = jQuery("#barcodes-import-cf-messages");
      el.parent().find("#barcodes-tooltip").addClass("show");
    }
    if (hash === "#b=orders-items") {
      const el = jQuery("#barcodes-import-orders-items");
      el.parent().find("#barcodes-tooltip").addClass("show");
    }
  };

  jQuery(window).bind("hashchange", function () {
    barcodeCheckHash(location.hash);
  });

  barcodeActionsInit();
  barcodeCheckHash(location.hash);

  jQuery(".button.barcodes-show-popup").on("click", (e) => {
    e.stopPropagation();
  });
});

function barcodeShowPopup(barcodeUrl) {
}
