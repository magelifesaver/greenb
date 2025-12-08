
var BarcodeAdminMenuList = function () {
  try {
    let wpVersion = window.a4bjs.wp_version;
    jQuery("#adminmenu span.a4barcode_support")
      .closest("a")
      .attr("target", "_blank")
      .attr("href", "https://www.ukrsolution.com/ExtensionsSupport/Support?extension=15&version=3.4.12&pversion=" + wpVersion + "&d=" + btoa(window.a4barcodesGS.lk));
    jQuery("#adminmenu span.a4barcode_faq")
      .closest("a")
      .attr("target", "_blank")
      .attr("href", "https://www.ukrsolution.com/Joomla/A4-BarCode-Generator-For-Wordpress#faq");
  } catch (error) {
    console.error(error.message);
  }
};

var BarcodeLoaderPreloader = function (status) {
  jQuery("#a4b-preloader-scripts").remove();

  if (status) {
    let css = `
    #a4b-preloader-scripts {position: fixed;top: 0px;left: 0px;width: 100vw;height: 100vh;z-index: 9000;font-size: 14px;background: rgba(0, 0, 0, 0.3);transition: opacity 0.3s ease 0s;transform: translate3d(0px, 0px, 0px);    }
    #a4b-preloader-scripts .a4b-preloader-icon {position: relative;top: 50%;left: 50%;color: #fff;border-radius: 50%;opacity: 1;width: 30px;height: 30px;border: 2px solid #f3f3f3;border-top: 3px solid #3498db;display: inline-block;animation: a4b-spin 1s linear infinite;    }
    @keyframes a4b-spin { 100% { -webkit-transform: rotate(360deg); transform:rotate(360deg); } }
    `;
    let preloader = jQuery(`<div id="a4b-preloader-scripts"><span class="a4b-preloader-icon"></span></div>`);

    jQuery("#wpbody-content").append(`<style>${css}</style>`);
    jQuery("#wpbody-content").append(preloader);
  }
};

var BarcodeLoaderMethods = function (params) {
  let prefix = params && params.prefix ? params.prefix : "";

  let a4bGetScriptByPath = function (path) {
    return jQuery.ajax({ type: "GET",url: path,success: function () { },dataType: "script",cache: false });
  };

  let a4bLoadScript = function (el, pluginData, preloader = true) {
    if(window.ProductLabelPrintingAppProgressStatus == true) return;

    window.ProductLabelPrintingAppProgressStatus = true;

    if(preloader) BarcodeLoaderPreloader(true);

    const loadedDone = function () {
      BarcodeLoaderPreloader(false);
      if(preloader) el.click();
      window.ProductLabelPrintingAppStatus = true;
      window.ProductLabelPrintingAppProgressStatus = false;
      window.ProductLabelPrintingAppProgressJszipStatus = false;
      return true;
    }

    if (pluginData.vendorJsPath !== "") {
      var a = a4bGetScriptByPath(pluginData.appJsPath);
      var v = a4bGetScriptByPath(pluginData.vendorJsPath);

      if ([undefined, 'undefined'].includes(typeof USJSZip)) {
        window.ProductLabelPrintingAppProgressJszipStatus = true;
        var jszip = a4bGetScriptByPath(pluginData.jszip);
        return jQuery.when(jszip, a, v).done(loadedDone);
      } 
      else {
        return jQuery.when(a, v).done(loadedDone);
      }
    } else {
      window.ProductLabelPrintingAppProgressJszipStatus = true;
      var jszip = a4bGetScriptByPath(pluginData.jszip);
      jQuery.getCachedScript(jszip);
      return jQuery.getCachedScript(pluginData.appJsPath).done(loadedDone);
    }
  };

  return { a4bLoadScript };
};

var BarcodeLoader = new BarcodeLoaderMethods();

jQuery(document).ready(function($) {
  jQuery.getCachedScript = function (url, options) {
    options = jQuery.extend(options || {}, {
      dataType: "script",
      cache: true,
      crossDomain: true,
      url: url,
    });

    return jQuery.ajax(options);
  };
});

jQuery(document).ready(function () {
  let prefix = "";

  BarcodeAdminMenuList();

  let customButtonSelector = window.a4barcodesGS ? window.a4barcodesGS.customButtonSelector : "a012345";
  let s = 'a[href*="page=wpbcu-barcode-generator"]';
  s += ',a[href*="page=wpbcu-barcode-generator&m=1"]';
  s += ',a[href*="page=wpbcu-import-list"]';
  s += ',a[id="barcode' + prefix + '-shortcodes-section"]';
  s += ',a[id="barcode' + prefix + '-settings-section"]';
  s += ',a[id="barcode' + prefix + '-custom-templates"]';
  s += ",button#barcodes-import-orders";
  s += ",button#barcodes-import-orders-products";
  s += ",button#barcodes-import-cf-messages";
  s += ",button#barcodes-import-products";
  s += ",button.barcodes-import-single-product";
  s += ",button.barcodes-import-single-product-label";
  s += ",a.barcodes-import-single-product-label";
  s += ",.barcodes-external-import";
  s += ",button.barcodes-import-variation-item";
  s += ",button#barcodes-import-categories";
  s += ",button#barcodes-import-orders-items";
  s += ",button#barcodes-import-wc-coupons";
  s += ",button#barcodes-import-wp-users";
  s += "," + customButtonSelector;

  let menu = jQuery(s);
  menu.off("click");

  let startLoading = function (e) {
    let isExcluded = jQuery(this).attr("data-is-excluded");
    let id = jQuery(this).attr("data-post-id");
    let status = jQuery(this).attr("data-post-status");
    if (isExcluded == 1) {
      window.BarcodePrintPostIsExcluded(id, status);
      return;
    }

    e.preventDefault();
    e.stopPropagation();

    let action = jQuery(this).attr("data-action-type");
    jQuery("body").attr("data-barcodes-action", action);

    menu.off("click");    
    menu.click(function (e) {
      e.preventDefault();
      e.stopPropagation();

      let itemId = jQuery(this).attr("data-item-id");
      if (itemId) window.barcodeSingleItemId = itemId;
      else window.barcodeSingleItemId = undefined;
    });

    jQuery(document).off("click", "button.barcodes-import-variation-item");
    jQuery(document).off("click", ".barcodes-external-import");
    jQuery(document).off("click", "button#barcodes-import-orders-items");

    BarcodeLoader.a4bLoadScript(jQuery(this), a4bjs);

    return false;
  };

  menu.click(startLoading);

  jQuery(document).on("click", "button.barcodes-import-variation-item", startLoading);
  jQuery(document).on("click", ".barcodes-external-import", startLoading);
  jQuery(document).on("click", "button#barcodes-import-orders-items", startLoading);

  let shortcodes = jQuery('a[id="barcode' + prefix + '-shortcodes-section"]');
  if (shortcodes.length > 0) {
    shortcodes.click();
  }

  let settings = jQuery('a[id="barcode' + prefix + '-settings-section"]');
  if (settings.length > 0) {
    settings.click();
  }

  let templates = jQuery('a[id="barcode' + prefix + '-custom-templates"]');
  if (templates.length > 0) {
    templates.click();
  }
});

window.BarcodePrintPostIsExcluded = function (id, status) {
  try {
    let lang = window.a4barcodesL10n ? window.a4barcodesL10n : {};
    let msg = lang.disabled_status_msg.replace("%status%", status);

    jQuery("#barcodePrintPostIsExcludedMsg").remove();
    let css = `
    #barcodePrintPostIsExcludedMsg {position: fixed;top: 0px;left: 0px;width: 100vw;height: 100vh;z-index: 9000;font-size: 14px;background: rgba(0, 0, 0, 0.3);transition: opacity 0.3s ease 0s;transform: translate3d(0px, 0px, 0px); }
    #barcodePrintPostIsExcludedMsg > div {position: relative; top: calc(50% - 150px); left: calc(50% - 150px); color: #000; background: #fff; padding: 25px; border-radius: 5px; ;width: 300px; height: 80px; }
    `;
    let modal = jQuery(`<div id="barcodePrintPostIsExcludedMsg"></div>`);
    let body = jQuery(`<div></div>`);
    body.append(`<div>${msg}</div>`)
    body.append(`<div style="text-align: center;"><button class="barcodePrintPostIsExcludedClose" style="margin-top: 20px;">${lang.close}</button></div>`)
    modal.append(body);
    jQuery("#wpbody-content").append(`<style>${css}</style>`);
    jQuery("#wpbody-content").append(modal);

    jQuery(".barcodePrintPostIsExcludedClose").click(function () {
      jQuery("#barcodePrintPostIsExcludedMsg").remove();
    });
  } catch (error) {
    console.warn("BarcodePrintPostIsExcluded", e.message);
  }
}
