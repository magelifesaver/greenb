window.__AAA_OC_CARD_CTX = window.__AAA_OC_CARD_CTX || {};
document.querySelectorAll('script.aaa-oc-card-ctx[type="application/json"]').forEach(s => {
  const id = s.getAttribute('data-order-id');
  if (!id) return;
  try { window.__AAA_OC_CARD_CTX[id] = JSON.parse(s.textContent); } catch (e) {}
});
// Snapshot indexer: (re)builds the global registry from JSON script tags
function indexCardSnapshots(scope = document) {
  window.__AAA_OC_CARD_CTX = window.__AAA_OC_CARD_CTX || {};
  const scripts = scope.querySelectorAll('script.aaa-oc-card-ctx[type="application/json"]');
  scripts.forEach(s => {
    const id = s.getAttribute('data-order-id');
    if (!id) return;
    try { window.__AAA_OC_CARD_CTX[id] = JSON.parse(s.textContent); } catch (e) {}
  });
  if (scripts.length) {
    try { console.log('[BOARD] Indexed card snapshots:', Object.keys(window.__AAA_OC_CARD_CTX).length); } catch(e){}
  }
}

(function($){
    "use strict";

    let currentOrderEditing = null;
    let sortMode = 'published';
    const pollInterval = AAA_OC_Vars.pollInterval || 300;
    const showCountdown = parseInt(AAA_OC_Vars.showCountdown, 10) === 1;
    const disablePolling = parseInt(AAA_OC_Vars.disablePolling, 10) === 1;

    // NEW: keep track of the polling timer
    let pollTimer = null;

    $(document).ready(function(){
        console.log("[BOARD] Document ready. Initializing board.");
        refreshBoardColumns();
        if (showCountdown) {
            initCountdownBar();
        }
        if (!disablePolling) {
            startPolling();
        }

        // NEW: pause polling when tab not visible; resume with a catch-up fetch
        document.addEventListener('visibilitychange', function(){
            if (disablePolling) return;
            if (document.hidden) {
                stopPolling();
                console.log('[BOARD] Page hidden → polling paused.');
            } else {
                console.log('[BOARD] Page visible → catch-up fetch + polling resumed.');
                refreshBoardColumns(); // catch-up fetch immediately
                startPolling();
            }
        });
    });

    // Toggle sort between "published" and "status"
    $(document).on('click', '.aaa-oc-sort-toggle', function(){
        sortMode = (sortMode === 'published') ? 'status' : 'published';
        refreshBoardColumns();
    });

    // NEW: explicit start/stop so we can pause/resume cleanly
    function startPolling(){
        stopPolling(); // ensure only one timer
        pollTimer = setInterval(function(){
            if (currentOrderEditing === null) {
                refreshBoardColumns();
            }
        }, pollInterval * 1000);
    }

    function stopPolling(){
        if (pollTimer) {
            clearInterval(pollTimer);
            pollTimer = null;
        }
    }

    function refreshBoardColumns(){
        $.ajax({
            url: AAA_OC_Vars.ajaxUrl,
            method: 'POST',
            data: {
                action: 'aaa_oc_get_latest_orders',
                _ajax_nonce: AAA_OC_Vars.nonce,
                sortMode: sortMode
            },
            success: function(response){
                if (response.success){
                    $('#aaa-oc-board-columns').html(response.data.columns_html);
                    indexCardSnapshots(document); // NEW: index fresh cards after render
                } else {
                    console.error("Error: ", response.data);
                }
            },
            error: function(err){
                console.error("AJAX error: ", err.statusText);
            }
        });
    }
    window.aaaOcRefreshBoard = refreshBoardColumns;

    // Expand order card to modal view when "Expand" button is clicked
    $(document).on('click', '.aaa-oc-view-edit', function(e){
        e.preventDefault();
        const $originalCard = $(this).closest('.aaa-oc-order-card');
        const orderId = $originalCard.data('order-id');

        // Mark order as currently being edited
        currentOrderEditing = orderId;

        // Clone the card and reveal its expanded content
        const $cardClone = $originalCard.clone(true, true);
        $cardClone.addClass('expanded'); // Mark the clone as expanded
        $cardClone.find('.row-2-two-col').show();

        // After inserting the clone into the modal container:
        $('#aaa-oc-modal-content').empty().append($cardClone);
            indexCardSnapshots($cardClone[0]); // NEW: index the clone’s embedded JSON
        // Display the modal overlay
        $('#aaa-oc-modal').fadeIn();
    });

    // Global function to close the modal view
    window.aaaOcCloseModal = function(){
        // Don't allow card to collapse if a modal is open
        if ($('.aaa-payment-modal:visible').length > 0) {
            console.warn('[MODAL] Cannot close card while modal is open.');
            return;
        }

        $('#aaa-oc-modal-content').empty();
        $('#aaa-oc-modal').fadeOut();
        if (currentOrderEditing) {
            $('.aaa-oc-order-card[data-order-id="'+ currentOrderEditing +'"]').removeClass('expanded');
        }
        currentOrderEditing = null;
    };

    $('#aaa-oc-modal').on('click', function(e){
        // Prevent closing card if any payment modal is open
        if ($('.aaa-payment-modal:visible').length > 0) {
            console.warn('[MODAL] Preventing backdrop card close: payment modal is open.');
            return;
        }
        if ($(e.target).is('#aaa-oc-modal')){
            aaaOcCloseModal();
        }
    });

    // Global function to change order status via AJAX
    window.aaaOcChangeOrderStatus = function(orderId, newStatus){
        // Remove the card from the DOM immediately
        $('.aaa-oc-order-card[data-order-id="'+ orderId +'"]').remove();
        $.ajax({
            url: AAA_OC_Vars.ajaxUrl,
            method: 'POST',
            data: {
                action: 'aaa_oc_update_order_status',
                order_id: orderId,
                new_status: newStatus,
                _ajax_nonce: AAA_OC_Vars.nonce
            },
            success: function(response){
                if (!response.success){
                    console.error("Error updating status: ", response.data);
                } else {
                    refreshBoardColumns();
                }
            },
            error: function(err){
                console.error("AJAX error: ", err.statusText);
            }
        });
    };

    // Optional: Initialize a countdown bar to show next poll interval
    function initCountdownBar(){
        const $heading = $('.wrap h1:first');
        if (!$heading.length) return;
        let countdown = pollInterval;
        const $bar = $('<div id="aaa-oc-countdown-bar" style="margin:1em 0; padding:5px; background:#ffdf00; text-align:center;"></div>');
        $bar.text('Next poll in ' + countdown + 's');
        $heading.after($bar);
        setInterval(function(){
            // Only tick when not editing, polling timer is active, and page is visible
            if (currentOrderEditing === null && pollTimer && !document.hidden){
                countdown--;
                if (countdown <= 0) {
                    countdown = pollInterval;
                }
                $bar.text('Next poll in ' + countdown + 's');
            }
        }, 1000);
    }

})(jQuery);
