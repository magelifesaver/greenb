/* File: /wp-content/plugins/aaa-admin-controler-v2/assets/js/popup.js */
(function(){
  if (!window.AAA_AC_POPUP) return;

  function modal(html){
    var wrap=document.createElement('div');
    wrap.id='aaa-ac-popup';
    wrap.innerHTML = ''
      + '<div style="position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:99998;"></div>'
      + '<div style="position:fixed;inset:0;display:flex;align-items:center;justify-content:center;z-index:99999;">'
      + '  <div style="background:#fff;min-width:360px;max-width:520px;padding:18px 18px 14px;border-radius:10px;box-shadow:0 10px 30px rgba(0,0,0,.2);font:14px/1.5 system-ui,Segoe UI,Roboto,Helvetica,Arial;">'
      + html
      + '  </div>'
      + '</div>';
    document.body.appendChild(wrap); return wrap;
  }
  function closeModal(){ var m=document.getElementById('aaa-ac-popup'); if(m) m.remove(); }

  function showPopup(payload){
    var name = AAA_AC_POPUP.shortName || AAA_AC_POPUP.name || 'me';
    var html = ''
      + '<h2 style="margin:0 0 10px;">' + (payload.msg || ('You are currently logged in as ' + name)) + '</h2>'
      + '<div style="color:#444;margin-bottom:12px;">Please confirm or switch accounts.</div>'
      + '<div style="display:flex;gap:8px;justify-content:flex-end;">'
      +   '<button id="aaa-ac-confirm" class="button button-primary">Confirm I\'m ' + name + '</button>'
      +   '<button id="aaa-ac-switch"  class="button">Switch Accounts</button>'
      + '</div>';
    var m = modal(html);
    m.querySelector('#aaa-ac-confirm').addEventListener('click', function(){
      var fd = new FormData();
      fd.append('action','aaa_ac_popup_confirm');
      fd.append('nonce', AAA_AC_POPUP.nonce);
      fd.append('popup_id', String(payload.popup_id||0));
      fetch(AAA_AC_POPUP.ajax, {method:'POST', body:fd, credentials:'same-origin'})
        .then(r=>r.json()).then(function(){ closeModal(); }).catch(function(){ closeModal(); });
    });
    m.querySelector('#aaa-ac-switch').addEventListener('click', function(){
      var fd = new FormData();
      fd.append('action','aaa_ac_popup_switch');
      fd.append('nonce', AAA_AC_POPUP.nonce);
      fd.append('popup_id', String(payload.popup_id||0));
      fetch(AAA_AC_POPUP.ajax, {method:'POST', body:fd, credentials:'same-origin'})
        .then(r=>r.json()).then(function(res){
          if(res && res.success && res.data && res.data.redirect){ window.location.href = res.data.redirect; }
          else { window.location.reload(); }
        }).catch(function(){ window.location.reload(); });
    });
  }

  function poll(){
    var url = AAA_AC_POPUP.ajax + '?action=aaa_ac_popup_check&nonce=' + encodeURIComponent(AAA_AC_POPUP.nonce);
    fetch(url, {credentials:'same-origin'}).then(r=>r.json()).then(function(res){
      if(res && res.success && res.data && res.data.show){ showPopup(res.data); }
    }).catch(function(){});
  }

  document.addEventListener('DOMContentLoaded', function(){
    poll();
    setInterval(poll, AAA_AC_POPUP.interval || 60000);
  });
})();
