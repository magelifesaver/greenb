(function($){
    "use strict";

    let scanBuffer = "";
    let fulfillmentStarted = false;

    let originalCloseModal = window.aaaOcCloseModal;

    window.aaaOcCloseModal = function(autoClose = false) {
        if (!autoClose && fulfillmentStarted) {
            let confirmClose = confirm("Warning: Closing now will reset picking progress. Continue?");
            if (!confirmClose) {
                return;
            }
            resetFulfillment();
            fulfillmentStarted = false;
        }
        originalCloseModal();
    };

document.addEventListener("keydown", function(e) {
    if (e.key === "Enter") {
        let code = scanBuffer.trim();
        scanBuffer = "";
        if (code) {
            handleScannedCode(code);
        }
    } else if (e.key.length === 1) {
        scanBuffer += e.key;
    } else {
        console.log("[SCANNER] Ignored non-character key:", e.key);
    }
});

    function handleScannedCode(code) {
        console.log("[SCANNER] Final scanned code:", code);
        let isModalOpen = $("#aaa-oc-modal").is(":visible");
        if (!isModalOpen) {
            expandOrder(code);
        } else {
            let $expanded = $(".aaa-oc-order-card.expanded");
            if ($expanded.length) {
                // Read both statuses from the expanded card.
                let orderStatus = $expanded.attr("data-order-status");
                orderStatus = orderStatus ? orderStatus.toLowerCase().trim() : '';
                let fulfillmentStatus = $expanded.attr("data-fulfillment-status");
                fulfillmentStatus = fulfillmentStatus ? fulfillmentStatus.toLowerCase().trim() : '';
                
                if (orderStatus !== "processing" || fulfillmentStatus !== "not_picked") {
                    console.warn("[SCANNER] Ignoring product scan because conditions are not met. Order status:", orderStatus, "Fulfillment status:", fulfillmentStatus);
                    return;
                }
                fulfillmentStarted = true;
                markItemAsPicked(code);
                checkIfAllPickedAndComplete();
            }
        }
    }

function expandOrder(code) {
    let parts   = code.split("|");
    let orderId = parts[0];
    let action  = parts[1] ? parts[1].toLowerCase() : null;

    let $card = $(".aaa-oc-order-card[data-order-id='" + orderId + "']");
    if (!$card.length) {
        console.warn("[ORDER] No matching card for order:", orderId);
        return;
    }

    let $expandBtn = $card.find(".aaa-oc-view-edit");
    if ($expandBtn.length) {
        $expandBtn.trigger("click");

        if (action === "payment") {
            setTimeout(function() {
                let $payBtn = $(".open-payment-modal[data-order-id='" + orderId + "']");
                if ($payBtn.length) {
                    $payBtn.trigger("click");
                    console.log("[ORDER] Opened payment modal for order:", orderId);
                } else {
                    console.warn("[ORDER] Payment button not found for order:", orderId);
                }
            }, 400); // delay to allow expansion to render
        }

    } else {
        console.warn("[ORDER] Found card, but no expand button:", orderId);
    }
}

    function markItemAsPicked(sku) {
        console.log("[FULFILLMENT] Looking for SKU:", sku);
        
        let $row = $(".aaa-oc-order-card.expanded .picked-status[data-original-sku='" + sku + "']");
        
        if (!$row.length) {
            console.log("[FULFILLMENT] Original SKU not found, trying to find by new SKU:", sku);
            $row = $(".aaa-oc-order-card.expanded .picked-status[data-new-sku='" + sku + "']");
            if ($row.length) {
                console.log("[FULFILLMENT] Found product with new SKU:", sku);
                sku = $row.attr('data-original-sku');
                console.log("[FULFILLMENT] Using original SKU for processing:", sku);
            } else {
                console.warn("[FULFILLMENT] No product found with original SKU or new SKU:", sku);
                return;
            }
        }
        
        let $card = $row.closest('.aaa-oc-order-card.expanded');
        let orderStatus = $card.attr('data-order-status');
        orderStatus = orderStatus ? orderStatus.toLowerCase().trim() : '';
        let fulfillmentStatus = $card.attr('data-fulfillment-status');
        fulfillmentStatus = fulfillmentStatus ? fulfillmentStatus.toLowerCase().trim() : '';
        
        console.log("markItemAsPicked: orderStatus =", orderStatus, ", fulfillmentStatus =", fulfillmentStatus);
        if (orderStatus !== 'processing' || fulfillmentStatus !== 'not_picked') {
            console.warn("[FULFILLMENT] Picking is locked because conditions are not met. Order status:", orderStatus, "Fulfillment status:", fulfillmentStatus);
            return;
        }
        
        let $countEl = $row.find(".picked-count");
        let $pickedText = $countEl.next("span.picked-text");
        let maxQty = parseInt($row.attr("data-max"), 10) || 1;
        let currentQty = parseInt($countEl.text().trim(), 10) || 0;
        
        if (currentQty < maxQty) {
            currentQty++;
            $countEl.text(currentQty);
            let rowColor = (currentQty === maxQty) ? "#a9fca9" : "#ffffc7";
            $row.css("background-color", rowColor);
            console.log("[FULFILLMENT] Picked 1 item of SKU:", sku, " => now", currentQty, "/", maxQty);
            
            checkIfAllPickedAndComplete();
        } else {
            console.warn("[FULFILLMENT] Already fully picked. SKU:", sku);
        }
    }

    function unpickItem(sku) {
        let $row = $(".aaa-oc-order-card.expanded .picked-status[data-sku='" + sku + "']");
        if (!$row.length) {
            setTimeout(() => {
                const found = $('.aaa-owf-card.is-expanded')
                              .find(`tr[data-original-sku='${code}'], tr[data-new-sku='${code}']`).length;
                if (!found) { toast(`Scanned SKU not found: ${code}`, 'warning'); }
            }, 100);
            return;
        }
        fulfillmentStarted = true;
        let $countEl = $row.find(".picked-count");
        let maxQty = parseInt($row.attr("data-max"), 10) || 1;
        let currentText = $countEl.text().trim();
        let parts = currentText.split(" ");
        let currentQty = parseInt(parts[0], 10) || 0;
        if (currentQty > 0) {
            currentQty--;
            $countEl.text(currentQty + " of " + maxQty + " Picked");
            let rowColor = (currentQty === 0) ? "transparent" : "yellow";
            $row.css("background-color", rowColor);
            console.log("[FULFILLMENT] Unpicked 1 item of SKU:", sku, " => now", currentQty, "/", maxQty);
        }
    }

    function checkIfAllPickedAndComplete() {
        let $expanded = $(".aaa-oc-order-card.expanded");
        if (!$expanded.length) return;
        let orderId = $expanded.attr("data-order-id");
        if (!orderId) return;
        let allDone = true;
        let pickedData = [];
        $expanded.find(".picked-status").each(function(){
            let $row = $(this);
            let sku = $row.attr("data-sku") || "";
            let max = parseInt($row.attr("data-max"), 10) || 1;
            let text = $row.find(".picked-count").text().trim();
            let parts = text.split(" ");
            let qtyPicked = parseInt(parts[0], 10) || 0;
            pickedData.push({
                sku: sku,
                picked: qtyPicked,
                max: max
            });
            if (qtyPicked < max) {
                allDone = false;
            }
        });
        if (allDone) {
            console.log("[FULFILLMENT] All items fully picked => finalize");
            recordFulfillmentCompletion(orderId, pickedData, function(){
                window.aaaOcCloseModal(true);
                window.aaaOcChangeOrderStatus(orderId, "lkd-packed-ready");
            });
        }
    }

function recordFulfillmentCompletion(orderId, pickedArray, doneCallback) {
    const picked_json = JSON.stringify(pickedArray);

    const scanned = (window.boardDataIndexer && typeof window.boardDataIndexer.getScannedCount === 'function')
        ? window.boardDataIndexer.getScannedCount() : 0;
    const manual  = (window.boardDataIndexer && typeof window.boardDataIndexer.getManualCount === 'function')
        ? window.boardDataIndexer.getManualCount() : 0;

    $.ajax({
        url: AAA_OC_Vars.ajaxUrl,
        method: "POST",
        dataType: "json",
        data: {
            action: "aaa_oc_record_fulfillment",
            security: AAA_OC_Vars.nonce,
            order_id: orderId,
            picked_json: picked_json,
            scanned_count: scanned,
            manual_count: manual
        },
        success: function(resp){
            if (!resp.success) {
                console.warn("[FULFILLMENT] save error:", resp.data);
            } else {
                console.log("[FULFILLMENT] saved; status:", resp.data?.fulfillment_status);
            }
            if (window.boardDataIndexer && typeof window.boardDataIndexer.resetCounters === 'function') {
                window.boardDataIndexer.resetCounters();
            }
            if (doneCallback) doneCallback();
        },
        error: function(err){
            console.error("[FULFILLMENT] AJAX error:", err);
            if (doneCallback) doneCallback();
        }
    });
}

    function resetFulfillment() {
        $(".aaa-oc-order-card.expanded .picked-status").each(function(){
            let $row = $(this);
            let max = parseInt($row.attr("data-max"), 10) || 1;
            $row.css("background-color", "transparent");
            $row.find(".picked-count").text("0 of " + max + " Picked");
        });
        console.log("[FULFILLMENT] Reset picking progress to 0");
    }
    $(document).on("click", ".increment-picked", function(e){
        e.preventDefault();
        let sku = $(this).attr("data-sku");
        markItemAsPicked(sku);
        checkIfAllPickedAndComplete();
    });

    $(document).on("click", ".decrement-picked", function(e){
        e.preventDefault();
        let sku = $(this).attr("data-sku");
        unpickItem(sku);
    });

})(jQuery);
