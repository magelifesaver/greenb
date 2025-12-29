// Accessibility helpers for Fast Cart. See accompanying PHP for details.
(function(){
    'use strict';
    const sel = '.fc-container';
    let prev = null;
    const getC = () => document.querySelector(sel);
    const getPage = () => Array.from(document.body.children).filter(el => !el.classList.contains('fc-container'));
    const disable = () => {
        const c = getC();
        if (!c) return;
        getPage().forEach(el => {
            el.setAttribute('inert', '');
            el.setAttribute('aria-hidden', 'true');
        });
        c.removeAttribute('inert');
        c.setAttribute('aria-hidden', 'false');
    };
    const enable = () => {
        const c = getC();
        if (!c) return;
        getPage().forEach(el => {
            el.removeAttribute('inert');
            el.removeAttribute('aria-hidden');
        });
        c.setAttribute('inert', '');
        c.setAttribute('aria-hidden', 'true');
    };
    const trap = c => {
        const sel = 'a[href],area[href],input:not([disabled]),select:not([disabled]),textarea:not([disabled]),button:not([disabled]),[tabindex]:not([tabindex="-1"])';
        const nodes = c.querySelectorAll(sel);
        if (!nodes.length) return;
        const first = nodes[0], last = nodes[nodes.length - 1];
        c.addEventListener('keydown', e => {
            if (e.key !== 'Tab') return;
            if (e.shiftKey) {
                if (document.activeElement === first) {
                    e.preventDefault();
                    last.focus();
                }
            } else {
                if (document.activeElement === last) {
                    e.preventDefault();
                    first.focus();
                }
            }
        });
    };
    const open = () => {
        const c = getC();
        if (!c) return;
        prev = document.activeElement;
        disable();
        const first = c.querySelector('button,a,input,[tabindex]:not([tabindex="-1"])');
        if (first) first.focus();
        trap(c);
    };
    const close = () => {
        enable();
        if (prev && typeof prev.focus === 'function') {
            try { prev.focus(); } catch (e) {}
        }
        prev = null;
    };
    const watch = () => {
        const c = getC();
        if (!c) return;
        if (c.classList.contains('loaded')) open(); else enable();
        const observer = new MutationObserver(ms => {
            ms.forEach(m => {
                if (m.type === 'attributes' && m.attributeName === 'class') {
                    if (c.classList.contains('loaded')) open(); else close();
                }
            });
        });
        observer.observe(c, {attributes: true, attributeFilter: ['class']});
        document.addEventListener('keydown', e => {
            if (e.key === 'Escape') {
                const ci = getC();
                if (ci && ci.classList.contains('loaded')) {
                    if (window.jQuery) {
                        window.jQuery(ci).trigger('click');
                    } else {
                        ci.classList.remove('loaded');
                    }
                }
            }
        });
    };
    document.addEventListener('DOMContentLoaded', watch);
})();