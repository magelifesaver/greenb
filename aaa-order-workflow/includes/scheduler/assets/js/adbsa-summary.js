/**
 * File: assets/js/adbsa-summary.js
 * Purpose: Inject and update Delivery Summary display in checkout.
 * Version: 1.4.0 (unified with delivery_time_range format)
 */
document.addEventListener('DOMContentLoaded', () => {
    const DEBUG_THIS_FILE = true;
    const log = (...args) => { if (DEBUG_THIS_FILE) console.debug('[ADBSA-Summary]', ...args); };

    function fmtDate(dateStr) {
        if (!dateStr) return '';
        const [y, m, d] = dateStr.split('-').map(Number);
        const date = new Date(y, m - 1, d); // local midnight
        return date.toLocaleDateString(undefined, {
            weekday: 'long',
            year: 'numeric',
            month: 'long',
            day: 'numeric'
        });
    }

    function todayYmdLocal() {
        const d = new Date();
        const pad = (n) => String(n).padStart(2, '0');
        return `${d.getFullYear()}-${pad(d.getMonth()+1)}-${pad(d.getDate())}`;
    }

    function updateSummary() {
        const dateEl = document.querySelector('#order-adbsa-delivery-date');
        const timeEl = document.querySelector('#order-adbsa-delivery-time');
        const summaryEl = document.querySelector('#adbsa-delivery-summary');

        if (!summaryEl) return;

        let dateVal = dateEl?.value || '';
        const timeOpt = timeEl?.options[timeEl.selectedIndex];
        const timeRange = timeOpt ? timeOpt.text : '';

        if (!dateVal) {
            dateVal = todayYmdLocal(); // fallback
            log('Date empty → using today fallback:', dateVal);
        }

        const dateTxt = fmtDate(dateVal);
        log('Raw date:', dateVal, 'Range text:', timeRange);

        let text = 'No delivery details selected yet.';
        if (dateVal && timeRange) {
            text = `Delivery scheduled for Today\n${dateTxt} at ${timeRange}`;
        } else if (dateVal) {
            text = `Delivery date: ${dateTxt}`;
        }

        summaryEl.innerText = text;
        log('Final summary:', text);
    }

    // Inject summary container after Time field
    const timeField = document.querySelector('.wc-block-components-select-input-adbsa-delivery-time');
    if (timeField && !document.querySelector('#adbsa-delivery-summary')) {
        const div = document.createElement('div');
        div.id = 'adbsa-delivery-summary';
        div.style.marginTop = '1rem';
        div.style.fontWeight = '500';
        div.style.color = '#333';
        div.style.whiteSpace = 'pre-line'; // respect line breaks
        div.innerText = 'No delivery details selected yet.';
        timeField.insertAdjacentElement('afterend', div);
        log('Summary container injected.');
    }

    document.addEventListener('change', (e) => {
        if (e.target.id === 'order-adbsa-delivery-date' || e.target.id === 'order-adbsa-delivery-time') {
            updateSummary();
        }
    });

    updateSummary(); // initial
});
