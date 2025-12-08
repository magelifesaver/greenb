/**
 * 
 * FilePath: plugins\aaa-order-workflow\assets\js\board-data-indexer.js
 */

(function($){
  "use strict";

  let scannedCount = 0;
  let manualCount  = 0;

  function incrementScanned(){ scannedCount++; console.log("[INDEXER] scannedCount:", scannedCount); }
  function incrementManual(){  manualCount++;  console.log("[INDEXER] manualCount:",  manualCount);  }

  function getScannedCount(){ return scannedCount; }
  function getManualCount(){  return manualCount; }
  function resetCounters(){   scannedCount = 0; manualCount = 0; }

  // (Optional) legacy function kept, but we now prefer posting from board-listener
  function recordFulfillmentCompletion(orderId) {
    $.post(AAA_OC_Vars.ajaxUrl, {
      action: "aaa_oc_record_fulfillment",
      security: AAA_OC_Vars.nonce,
      order_id: orderId,
      scanned_count: scannedCount,
      manual_count: manualCount
    }).always(function(){
      resetCounters();
    });
  }

  window.boardDataIndexer = {
    incrementScanned, incrementManual,
    getScannedCount, getManualCount, resetCounters,
    recordFulfillmentCompletion
  };
})(jQuery);
