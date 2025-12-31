;(function($){
  'use strict';
  const DEBUG_THIS_FILE = true;
  const log = (...a)=>{ if(DEBUG_THIS_FILE) console.log('[AAA-OC][Actions]', ...a); };

  function actionsHtml(){
    return `
      <div class="aaa-oc-actions-wrap" style="display:flex;flex-direction:column;gap:12px">
        <section>
          <h3 style="margin:.2em 0 .4em;font-size:14px;">Quick Navigation</h3>
          <div class="aaa-oc-actions-row" style="display:flex;flex-wrap:wrap;gap:8px">
            <button class="button button-secondary" data-href="index.php">Exit (Dashboard)</button>
            <button class="button button-secondary" data-href="admin.php?page=aaa-oc-core-settings&tab=aaa-oc-workflow-settings">Workflow Settings</button>
            <button class="button button-secondary" data-href="admin.php?page=aaa-oc-indexing-settings">Reindexing</button>
            <button class="button button-secondary" data-href="edit.php?post_type=shop_order">Orders</button>
            <button class="button button-secondary" data-href="admin.php?page=aaa-openia-order-creation-v4">New Order</button>
            <button class="button button-secondary" data-href="edit.php?post_type=product&post_status=publish&product_type=simple">Products (Published + Simple)</button>
            <button class="button button-secondary" data-href="users.php?role=customer&order=desc">Customers</button>
          </div>
        </section>

      </div>
    `;
  }

  function mount(){
    if (!window.aaaOcPanels) {
      log('Toolbar shell (aaaOcPanels) not found; aborting actions mount.');
      return;
    }
    window.aaaOcPanels.add('actions', $('<div></div>').html(actionsHtml())[0]);
    bind();
    log('actions mounted');
  }

  function bind(){
    const $root = $('#aaa-oc-actions-body');

    $root.on('click', 'button[data-href]', function(){
      const href = $(this).data('href');
      if (!href) return;
      try { window.location.href = href; } catch(e) {}
    });

    $root.on('click', 'button[data-open]', function(){
      const area = $(this).data('open');
      if (window.aaaOcPanels && typeof aaaOcPanels.open === 'function') {
        aaaOcPanels.open(area);
      }
    });

    $root.on('click', '.aaa-oc-act-refresh', function(){
      if (typeof window.aaaOcRefreshBoard === 'function') {
        window.aaaOcRefreshBoard();
      }
    });
  }

  $(function(){ mount(); });
})(jQuery);
