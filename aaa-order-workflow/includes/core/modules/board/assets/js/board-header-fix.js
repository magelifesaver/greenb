(function($){
  'use strict';
  const DEBUG_THIS_FILE = true;
  const log = (...a)=>{ if(DEBUG_THIS_FILE) console.log('[AAA-OC][HeaderFix]', ...a); };

  function injectCss(){
    if (document.getElementById('aaa-oc-header-fix-style')) return;
    const css = `
      .aaa-oc-header-row{display:flex;justify-content:space-between;align-items:center;gap:.75rem;margin:.25rem 0 .5rem}
      .aaa-oc-header-row .aaa-oc-header-tools{margin:0} /* remove extra bump from older CSS */
    `;
    const s = document.createElement('style');
    s.id = 'aaa-oc-header-fix-style';
    s.textContent = css;
    document.head.appendChild(s);
  }

  function moveOutOfH1(){
    const $h1 = $('.wrap h1').first();
    if (!$h1.length) return;

    const $bar   = $h1.find('.aaa-oc-bar').first().detach();
    const $tools = $h1.find('.aaa-oc-header-tools').first().detach();

    if (!$bar.length && !$tools.length) return;

    $bar.find('.aaa-oc-title').remove();

    let $row = $h1.next('.aaa-oc-header-row');
    if (!$row.length) {
      $row = $('<div class="aaa-oc-header-row"></div>');
      $row.insertAfter($h1);
    }

    if ($bar.length)   $row.append($bar);
    if ($tools.length) $row.append($tools);

    log('moved toolbar & tools out of <h1>');
  }

  function init(){
    injectCss();
    moveOutOfH1();

    $(document).ajaxSuccess((_, __, settings)=>{
      try{
        if (typeof settings.data === 'string' && settings.data.indexOf('action=aaa_oc_get_latest_orders') !== -1){
          moveOutOfH1();
        }
      }catch(e){}
    });
  }

  jQuery(init);
})(jQuery);
