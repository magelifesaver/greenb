/**
 * File Path: /wp-content/plugins/aaa-age-gate/assets/js/aaa-age-gate.js
 * Purpose: Age gate modal logic (cookie set + focus trap)
 */
(function () {
  'use strict';

  var CFG = (window.AAA_AGE_GATE || {});
  var COOKIE = CFG.cookieName || 'aaa_age_gate_ok';
  var DAYS = parseInt(CFG.cookieDays || 365, 10);
  var declineUrl = CFG.declineUrl || 'https://www.google.com';

  function log(msg){ if (window.console && console.debug) console.debug('[AAA_Age_Gate]', msg); }

  function getCookie(name){
    var m = document.cookie.match(new RegExp('(^| )' + name + '=([^;]+)'));
    return m ? decodeURIComponent(m[2]) : null;
  }
  function setCookie(name, value, days){
    var d = new Date();
    d.setTime(d.getTime() + (days*24*60*60*1000));
    document.cookie = name + '=' + encodeURIComponent(value) + ';expires=' + d.toUTCString() + ';path=/;SameSite=Lax';
  }

  function openGate(){
    var overlay = document.getElementById('aaa-age-gate-overlay');
    if (!overlay) return;

    // Inject strings
    var t = document.getElementById('aaa-age-gate-title');
    var m = document.getElementById('aaa-age-gate-message');
    var a = document.getElementById('aaa-age-gate-accept');
    var d = document.getElementById('aaa-age-gate-decline');
    if (t) t.textContent = CFG.heading || 'Adult Content Ahead';
    if (m) m.textContent = CFG.message || 'This website contains age-restricted content.';
    if (a) a.textContent = CFG.acceptLabel || 'I am 18+ — Enter';
    if (d) d.textContent = CFG.declineLabel || 'Exit';

    d.setAttribute('href', declineUrl);

    overlay.setAttribute('aria-hidden', 'false');
    overlay.classList.add('open');
    document.documentElement.classList.add('aaa-age-gate-locked');

    // Focus trap
    var focusables = overlay.querySelectorAll('button, [href], [tabindex]:not([tabindex="-1"])');
    var first = focusables[0];
    var last  = focusables[focusables.length - 1];
    if (first) first.focus();

    overlay.addEventListener('keydown', function(e){
      if (e.key === 'Escape'){
        // No escape without accepting; route to decline
        window.location.href = declineUrl;
      } else if (e.key === 'Tab'){
        if (e.shiftKey && document.activeElement === first){
          e.preventDefault(); last.focus();
        } else if (!e.shiftKey && document.activeElement === last){
          e.preventDefault(); first.focus();
        }
      }
    });

    // Accept
    a.addEventListener('click', function(){
      setCookie(COOKIE, '1', DAYS);
      overlay.classList.remove('open');
      overlay.setAttribute('aria-hidden', 'true');
      document.documentElement.classList.remove('aaa-age-gate-locked');
      log('Accepted; cookie set.');
    });

    // Decline handled by link href
  }

  // Run only if cookie not set
  if (!getCookie(COOKIE)) {
    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', openGate);
    } else {
      openGate();
    }
  }
})();
