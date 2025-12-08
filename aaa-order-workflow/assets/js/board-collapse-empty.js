/**
 * File: /wp-content/plugins/aaa-order-workflow/assets/js/board-collapse-empty.js
 * Purpose: Admin preference to Collapse/Hide columns that have zero orders on the Workflow Board.
 * Version: 1.0.0
 */
(function ($) {
	'use strict';
	const DEBUG_THIS_FILE = true;
	const log = (...a) => { if (DEBUG_THIS_FILE) console.log('[AAA-OC][CollapseEmpty]', ...a); };
	const LS_KEY = 'aaa_oc_board_collapse_empty'; // '1' = enabled, '0' = disabled
	const COL_SELECTOR = '.aaa-oc-column, .aaa-oc-board-col, .board-status, .wf-col'; // broad fallback list
	const CARD_SELECTOR = '.aaa-oc-card, .aaa-oc-order-card, .order-card'; // broad fallback list
	const COLLAPSE_CLASS = 'aaa-oc-col-collapsed';

	function injectCssOnce() {
		if (document.getElementById('aaa-oc-collapse-empty-style')) return;
		const css = `
			/* Collapse style is deliberately light to avoid layout thrash */
			${COL_SELECTOR}.${COLLAPSE_CLASS} {
				width: 32px !important;
				min-width: 32px !important;
				max-width: 36px !important;
				overflow: hidden !important;
				opacity: .55 !important;
				transition: width .2s ease, opacity .2s ease;
				position: relative;
			}
			${COL_SELECTOR}.${COLLAPSE_CLASS} * { pointer-events: none !important; }
			${COL_SELECTOR}.${COLLAPSE_CLASS} .aaa-oc-col-title,
			${COL_SELECTOR}.${COLLAPSE_CLASS} h2,
			${COL_SELECTOR}.${COLLAPSE_CLASS} h3 { 
				writing-mode: vertical-rl; 
				transform: rotate(180deg); 
				white-space: nowrap; 
				margin: 8px auto; 
			}
		`.trim();
		const tag = document.createElement('style');
		tag.id = 'aaa-oc-collapse-empty-style';
		tag.type = 'text/css';
		tag.appendChild(document.createTextNode(css));
		document.head.appendChild(tag);
		log('Injected CSS');
	}

	function getPref() {
		return window.localStorage.getItem(LS_KEY) === '1';
	}
	function setPref(enabled) {
		window.localStorage.setItem(LS_KEY, enabled ? '1' : '0');
	}

	function isColumnEmpty($col) {
		// Primary: no cards present
		if ($col.find(CARD_SELECTOR).length === 0) {
			// Fallback: UI text that we render when empty
			const text = ($col.text() || '').toLowerCase();
			if (text.indexOf('no orders found in this status') !== -1 || text.indexOf('(0)') !== -1) {
				return true;
			}
			// If truly no cards, still treat as empty
			return true;
		}
		return false;
	}

	function applyCollapse() {
		const enabled = getPref();
		$(COL_SELECTOR).each(function () {
			const $col = $(this);
			if (!enabled) {
				$col.removeClass(COLLAPSE_CLASS);
				return;
			}
			if (isColumnEmpty($col)) {
				$col.addClass(COLLAPSE_CLASS);
			} else {
				$col.removeClass(COLLAPSE_CLASS);
			}
		});
		log('Applied collapse state=', enabled);
	}

	function addToggleButton() {
		// Attach to the existing board toolbar area (same row that has Refresh / filters / “webmaster”)
		const $bar = $('.aaa-oc-board-toolbar, .board-toolbar, .aaa-oc-header-actions').first();
		if ($bar.length === 0 || $bar.find('.aaa-oc-btn-collapse-empty').length) return;

		const $btn = $('<button/>', {
			class: 'button button-secondary aaa-oc-btn-collapse-empty',
			text: getPref() ? 'Show Empty' : 'Hide Empty',
			title: 'Toggle collapsing columns that have zero orders',
			css: { marginLeft: '6px' }
		}).on('click', function () {
			const next = !getPref();
			setPref(next);
			$(this).text(next ? 'Show Empty' : 'Hide Empty');
			applyCollapse();
		});

		$bar.append($btn);
		log('Toggle button mounted');
	}

	function watchForBoardUpdates() {
		// Board refreshes via AJAX; watch for DOM changes
		const root = document.querySelector('#wpbody-content') || document.body;
		if (!root) return;
		const mo = new MutationObserver((mut) => {
			// On any child change, re-apply
			applyCollapse();
		});
		mo.observe(root, { childList: true, subtree: true });
		log('MutationObserver armed');
	}

	$(document).ready(function () {
		injectCssOnce();
		addToggleButton();
		applyCollapse();
		watchForBoardUpdates();
	});
})(jQuery);
