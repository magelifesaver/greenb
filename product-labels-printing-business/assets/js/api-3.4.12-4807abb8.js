window.BarcodePrintAppStorage = new (class BarcodeStorage {
  async zipStorage(barcodeList, storageKey) {
    const pluginSettings = window.a4barcodesGS || {};

    if (pluginSettings.jszipCompression == 1) {
      if (barcodeList && (barcodeList.length == 0 || (barcodeList.length == 1) && barcodeList[0].image == undefined)) {
        window.localStorage.setItem(storageKey, "");
      } else {
        try {
          var zip = new USJSZip();
          zip.file("labels.txt", JSON.stringify(barcodeList));

          return zip.generateAsync({ type: "binarystring", compression: "DEFLATE", compressionOptions: { level: 5 } }).then((content) => {
            window.localStorage.setItem(storageKey, content);
            return true;
          });
        } catch (error) {
          window.a4barcodesGS.jszipCompression = 0;
          jQuery.ajax({ type: "POST", url: window.a4bjs.ajaxUrl, data: { action: "a4_barcode_disable_jszip" }, dataType: "JSON" });
          return await zipStorage(barcodeList, storageKey);
        }
      }
    }
    else {
      if (barcodeList && (barcodeList.length == 0 || (barcodeList.length == 1) && barcodeList[0].image == undefined)) {
        window.localStorage.setItem(storageKey, "");
      } else {
        window.localStorage.setItem(storageKey, JSON.stringify(barcodeList));
      }
    }
  }

  async unzipStorage(storageKey) {
    const binarystring = window.localStorage.getItem(storageKey);

    const pluginSettings = window.a4barcodesGS || {};

    try {
      if (pluginSettings.jszipCompression == 1) {
        if (!binarystring) return [];
        try {
          var unZip = new USJSZip();

          return unZip.loadAsync(binarystring).then((zip) => {
            return zip
              .file("labels.txt")
              .async("string")
              .then((content) => {
                return JSON.parse(content);
              });
          });
        } catch (error) {
          window.a4barcodesGS.jszipCompression = 0;
          jQuery.ajax({ type: "POST", url: window.a4bjs.ajaxUrl, data: { action: "a4_barcode_disable_jszip" }, dataType: "JSON" });
          return await unzipStorage(storageKey);
        }

      }
      else {
        return JSON.parse(binarystring);
      }

    } catch (error) {
      return [];
    }

    return [];
  }

  async addLabel(image, format, replacements) {
    let label = {
      format,
      image,
      line1: typeof replacements === "object" && replacements["[line1]"] ? replacements["[line1]"] : "",
      line2: typeof replacements === "object" && replacements["[line2]"] ? replacements["[line2]"] : "",
      line3: typeof replacements === "object" && replacements["[line3]"] ? replacements["[line3]"] : "",
      line4: typeof replacements === "object" && replacements["[line4]"] ? replacements["[line4]"] : "",
      post_image: "",
      replacements: typeof replacements === "object" ? replacements : [],
    };

    let storageKey = "barcodes-list-images";

    return this.unzipStorage(storageKey).then((list) => {
      if (!list) list = [];

      let index = list.findIndex((item) => !item.image);

      if (index >= 0) list[index] = label;
      else list.push(label);

      return this.zipStorage(list, storageKey).then(() => {
        if (window.ProductLabelPrintingAppStatus) window.ProductLabelPrintingApp.reloadBarcodesList();
    });
  });
}

  async addLabelsBatch(labels) {
  labels.forEach(label => {
    label.post_image = "";
    label.line1 = typeof label.replacements === "object" && label.replacements["[line1]"] ? label.replacements["[line1]"] : "";
    label.line2 = typeof label.replacements === "object" && label.replacements["[line2]"] ? label.replacements["[line2]"] : "";
    label.line3 = typeof label.replacements === "object" && label.replacements["[line3]"] ? label.replacements["[line3]"] : "";
    label.line4 = typeof label.replacements === "object" && label.replacements["[line4]"] ? label.replacements["[line4]"] : "";
    label.replacements = typeof label.replacements === "object" ? label.replacements : [];
  });

  let storageKey = "barcodes-list-images";

  return this.unzipStorage(storageKey).then((list) => {
    if (!list) list = [];

    if (Array.isArray(labels)) {
      labels.forEach(label => {
        let index = list.findIndex((item) => !item.image);

        if (index >= 0) list[index] = label;
        else list.push(label);
      });
    }

    return this.zipStorage(list, storageKey).then(() => {
      if (window.ProductLabelPrintingAppStatus) window.ProductLabelPrintingApp.reloadBarcodesList();
  });
});
  }

  async clearLabels() {
  let storageKey = "barcodes-list-images";

  return this.zipStorage([], storageKey).then(() => {
    if (window.ProductLabelPrintingAppStatus) window.ProductLabelPrintingApp.reloadBarcodesList();
});
  }

}) ();

var ProductLabelsPrinting = new (class ProductLabelsPrinting {

  loaderPromise = null;

  async addLabel(labelData = { barcodeImageData: "", format: "C128", replacements: [] }) {
    const pluginData = window.a4bjs || {};

    if (!window.ProductLabelPrintingAppStatus) {
      const res = BarcodeLoader.a4bLoadScript(jQuery("<a></a>"), a4bjs, false);
      if (res) this.loaderPromise = res;
    }

    return jQuery
      .ajax({ url: `${pluginData.websiteUrl}/barcodes-print/${labelData.barcodeImageData}/${labelData.format}/f4af4fa850983140f87a5ee1dbcd6dbf00b59a4c0.svg3`, type: "get", dataType: "text" }, null, "JSON")
      .then((image) => {
        return window.BarcodePrintAppStorage.addLabel(image, labelData.format, labelData.replacements);
      });
  }

  async addLabelsBatch(batch = [], format = "C128") {
    const pluginData = window.a4bjs || {};

    if (!window.ProductLabelPrintingAppStatus) {
      const res = BarcodeLoader.a4bLoadScript(jQuery("<a></a>"), a4bjs, false);
      if (res) this.loaderPromise = res;
    }

    let requestData = [];

    if (Array.isArray(batch)) {
      batch.forEach(labelData => {
        if (labelData.barcodeImageData && !requestData.includes(labelData.barcodeImageData)) requestData.push(labelData.barcodeImageData);
      });
    }

    return jQuery.post(window.ajaxurl, { action: 'label_printing_generate_barcodes_by_codes', nonce: pluginData.nonce, batch: requestData, format }, null, "JSON").then((response) => {
      const barcodes = response ? response : {};
      let labels = [];

      if (Array.isArray(batch)) {
        batch.forEach(labelData => {
          if (labelData.barcodeImageData) {
            const image = barcodes[labelData.barcodeImageData] ? barcodes[labelData.barcodeImageData] : "";
            const batchLabel = batch.find(b => b.barcodeImageData == labelData.barcodeImageData);
            const replacements = batchLabel && batchLabel.replacements ? batchLabel.replacements : [];
            labels.push({ image, format, replacements });
          }
        });
      }

      return window.BarcodePrintAppStorage.addLabelsBatch(labels);
    });
  }

  async clearLabels() {
    const pluginData = window.a4bjs || {};

    if (!window.ProductLabelPrintingAppStatus) {
      const res = BarcodeLoader.a4bLoadScript(jQuery("<a></a>"), a4bjs, false);
      if (res) this.loaderPromise = res;
    }

    return window.BarcodePrintAppStorage.clearLabels();
  }

  async createLabelsByActiveTemplate(params) {
    const pluginData = window.a4bjs || {};

    BarcodeLoaderPreloader(true);

    try {
      window.BarcodePrintAppStorage.clearLabels();

      return jQuery.post(window.ajaxurl, { action: 'a4barcode_get_barcodes', nonce: pluginData.nonce, ...params }, null, "JSON").then((response) => {
        const barcodes = response && response.listItems ? response.listItems : {};
        const templateId = response && response.templateId ? response.templateId : null;
        let labels = [];

        if (Array.isArray(barcodes)) {
          barcodes.forEach(value => {
            if (!value.replacements["[line1]"]) value.replacements["[line1]"] = value.fieldLine1;
            if (!value.replacements["[line2]"]) value.replacements["[line2]"] = value.fieldLine2;
            if (!value.replacements["[line3]"]) value.replacements["[line3]"] = value.fieldLine3;
            if (!value.replacements["[line4]"]) value.replacements["[line4]"] = value.fieldLine4;

            labels.push({
              image: value.image,
              format: value.format,
              replacements: value.replacements,
              tplId: templateId,
              post_image: value.post_image,
              postId: value.ID,
              parentId: value.parentId,
              lineBarcode: value.lineBarcode,
            });
          });
        }

        BarcodeLoaderPreloader(false);

        return window.BarcodePrintAppStorage.addLabelsBatch(labels);
      });
    } catch (error) {
      BarcodeLoaderPreloader(false);
    }
  }

  show() {
    if (window.ProductLabelPrintingAppStatus) window.ProductLabelPrintingApp.showManually({ preview: true });
    else {
  BarcodeLoaderPreloader(true);

  if (this.loaderPromise) this.loaderPromise.then(() => {
    window.ProductLabelPrintingApp.showManually({ preview: true });
  });
  else BarcodeLoader.a4bLoadScript(jQuery("<a></a>"), a4bjs).then(() => {
    window.ProductLabelPrintingApp.showManually({ preview: true });
  });
}
  }
}) ();
