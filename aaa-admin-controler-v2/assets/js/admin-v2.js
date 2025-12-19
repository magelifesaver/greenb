/*
 * File: /wp-content/plugins/aaa-admin-controler-v2/assets/js/admin-v2.js
 *
 * This script powers the UI for the Sessions and Settings tabs. The
 * rewritten version introduces support for an “Only Online” filter on
 * the Sessions tab by appending a parameter to the AJAX request when
 * the corresponding checkbox is checked. No other behaviour has been
 * modified; when the checkbox is unchecked the full list of users for
 * the selected role will be returned as before.
 */
(function(){
  function qs(id){ return document.getElementById(id); }
  function on(el,ev,fn){ if(el) el.addEventListener(ev,fn); }

  document.addEventListener('DOMContentLoaded', function(){

    // ---- Sessions tab ----
    var roleSel = qs('aaa_ac_role');
    var loadBtn = qs('aaa_ac_load');
    var clearBtn= qs('aaa_ac_clear');
    var rows    = qs('aaa_ac_rows');
    var onlineOnly = qs('aaa_ac_online_only');

    if(loadBtn && rows){
      on(loadBtn, 'click', function(){
        if(!roleSel || !roleSel.value){
          rows.innerHTML = '<tr><td colspan="8" style="text-align:center;">'+
            'No role selected.'+'</td></tr>'; return;
        }
        rows.innerHTML = '<tr><td colspan="8" style="text-align:center;">Loading…</td></tr>';
        var url = AAA_AC.ajax + '?action=aaa_ac_load_sessions_v2&role=' + encodeURIComponent(roleSel.value) + '&nonce=' + encodeURIComponent(AAA_AC.nonce);
        // Add online_only flag when the checkbox is checked
        if(onlineOnly && onlineOnly.checked){
          url += '&online_only=1';
        }
        fetch(url, {credentials:'same-origin'}).then(function(r){ return r.json(); }).then(function(res){
          if(res && res.success){
            rows.innerHTML = res.data.rows || '<tr><td colspan="8" style="text-align:center;color:#777;">'+
              'No users.'+'</td></tr>';
          }
          else {
            rows.innerHTML = '<tr><td colspan="8">Failed to load.</td></tr>';
          }
        }).catch(function(){
          rows.innerHTML = '<tr><td colspan="8">Error.</td></tr>';
        });
      });
      on(clearBtn, 'click', function(){
        rows.innerHTML = '<tr><td colspan="8" style="text-align:center;color:#777;">No users loaded.</td></tr>';
      });
    }

    // ---- Settings tab ----
    var sRole  = qs('aaa_ac_settings_role');
    var sLoad  = qs('aaa_ac_settings_load');
    var sClear = qs('aaa_ac_settings_clear');
    var sSave  = qs('aaa_ac_settings_save');
    var sRows  = qs('aaa_ac_settings_rows');

    function enableSaveIfRows(){
      if(!sSave) return;
      sSave.disabled = !sRows || !sRows.querySelector('tr[data-uid]');
    }

    if(sLoad && sRows){
      on(sLoad, 'click', function(){
        if(!sRole || !sRole.value){
          sRows.innerHTML = '<tr><td colspan="6" style="text-align:center;">No role selected.</td></tr>'; enableSaveIfRows(); return;
        }
        sRows.innerHTML = '<tr><td colspan="6" style="text-align:center;">Loading…</td></tr>';
        var url = AAA_AC.ajax + '?action=aaa_ac_load_settings_users_v2&role=' + encodeURIComponent(sRole.value) + '&nonce=' + encodeURIComponent(AAA_AC.nonce);
        fetch(url, {credentials:'same-origin'}).then(function(r){ return r.json(); }).then(function(res){
          if(res && res.success){ sRows.innerHTML = res.data.rows || '<tr><td colspan="6" style="text-align:center;color:#777;">No users.</td></tr>'; }
          else { sRows.innerHTML = '<tr><td colspan="6">Failed to load.</td></tr>'; }
          enableSaveIfRows();
        }).catch(function(){ sRows.innerHTML = '<tr><td colspan="6">Error.</td></tr>'; enableSaveIfRows(); });
      });
    }

    if(sClear && sRows){
      on(sClear, 'click', function(){
        sRows.innerHTML = '<tr><td colspan="6" style="text-align:center;color:#777;">No users loaded.</td></tr>';
        enableSaveIfRows();
      });
    }

    if(sSave && sRows){
      on(sSave, 'click', function(){
        var payload = [];
        var trs = sRows.querySelectorAll('tr[data-uid]');
        trs.forEach(function(tr){
          var uid = parseInt(tr.getAttribute('data-uid'),10);
          if(!uid) return;
          var force = (tr.querySelector('input.aaa-ac-force')||{}).value || '';
          var popup = (tr.querySelector('input.aaa-ac-popup')||{}).value || '';
          var inc   = (tr.querySelector('input.aaa-ac-inc')||{}).checked || false;
          payload.push({ user_id: uid, force: force.trim(), popup: popup.trim(), include: inc });
        });

        sSave.disabled = true;
        var fd = new FormData();
        fd.append('action','aaa_ac_save_user_settings_v2');
        fd.append('nonce', AAA_AC.nonce);
        fd.append('users', JSON.stringify(payload));

        fetch(AAA_AC.ajax, {method:'POST', body:fd, credentials:'same-origin'})
          .then(function(r){ return r.json(); }).then(function(res){
            sSave.disabled = false;
            if(res && res.success){
              alert('Saved ' + (res.data && res.data.saved ? res.data.saved : 0) + ' users.');
            }else{
              alert('Save failed.');
            }
          }).catch(function(){
            sSave.disabled = false;
            alert('Error saving.');
          });
      });
    }

  });
})();