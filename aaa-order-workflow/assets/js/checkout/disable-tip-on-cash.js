document.addEventListener('DOMContentLoaded', function () {
    const DEBUG_THIS_FILE = true;

    function log(...args) {
        if (DEBUG_THIS_FILE) {
            console.log('[TIP BLOCK]', ...args);
        }
    }

    const tipInput = document.querySelector('.wpslash-tip-input');
    const removeBtn = document.querySelector('.wpslash_tip_remove_btn');
    const paymentRadios = document.querySelectorAll('input[name="payment_method"]');
    const placeOrderBtn = document.querySelector('#place_order');

    if (!tipInput || !placeOrderBtn) {
        log('Missing elements: tip input or place order button.');
        return;
    }

    let hasIntercepted = false;

    function getSelectedPayment() {
        const selected = document.querySelector('input[name="payment_method"]:checked');
        return selected ? selected.value : null;
    }

    function isCOD() {
        return getSelectedPayment() === 'cod';
    }

    function tipValue() {
        return parseFloat(tipInput.value || '0') || 0;
    }

    function clearTipAndRecalculate(callback) {
        log('Clearing tip and fee...');

        tipInput.value = '';
        if (removeBtn) {
            removeBtn.click();
            log('Clicked remove fee button');
        } else {
            log('No remove button found');
        }

        jQuery('body').trigger('update_checkout');

        // Wait briefly for recalculation
        setTimeout(() => {
            log('WooCommerce recalculated. Resubmitting...');
            hasIntercepted = false;
            callback();
        }, 500);
    }

    placeOrderBtn.addEventListener('click', function (e) {
        if (hasIntercepted) return; // avoid infinite loop

        const tip = tipValue();
        const cod = isCOD();

        log('Place Order clicked. Method:', cod ? 'cod' : 'non-cod', 'Tip:', tip);

        if (cod && tip > 0) {
            log('Intercepting order submission due to tip with COD');

            e.preventDefault(); // block submission
            hasIntercepted = true;

            clearTipAndRecalculate(() => {
                placeOrderBtn.click(); // re-trigger after cleanup
            });
        }
    });

    log('✅ Tip-on-COD blocker script initialized');
});
