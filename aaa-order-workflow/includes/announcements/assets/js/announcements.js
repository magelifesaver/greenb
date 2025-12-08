(function ($) {
  'use strict';

  console.log('[ANN] announcements.js loaded');

  const api = {
    next(cb) {
      $.post(AAA_OC_ANN.ajax, {
        action: 'aaa_oc_annc_next',
        nonce: AAA_OC_ANN.nonce
      }).done((res) => {
        console.log('[ANN] next response:', res);
        cb && cb(res);
      }).fail((xhr) => {
        console.warn('[ANN] next failed:', xhr.status, xhr.responseText);
        cb && cb(null);
      });
    },
    accept(id, cb) {
      $.post(AAA_OC_ANN.ajax, {
        action: 'aaa_oc_annc_accept',
        nonce: AAA_OC_ANN.nonce,
        id: id,
        confirm: '1'
      }).done((res) => {
        console.log('[ANN] accept response:', res);
        cb && cb(res);
      }).fail((xhr) => {
        console.warn('[ANN] accept failed:', xhr.status, xhr.responseText);
        cb && cb(null);
      });
    }
  };

  function buildShell() {
    if ($('#aaa-oc-annc-overlay').length) return;

    const html = [
      '<div id="aaa-oc-annc-overlay" class="aaa-oc-annc-overlay" style="display:none;">',
        '<div class="aaa-oc-annc-modal" role="dialog" aria-modal="true" aria-labelledby="aaa-oc-annc-title">',
          '<div class="aaa-oc-annc-header"><h2 id="aaa-oc-annc-title"></h2></div>',
          '<div id="aaa-oc-annc-content" class="aaa-oc-annc-content"></div>',
          '<div class="aaa-oc-annc-footer">',
            '<label class="aaa-oc-annc-check">',
              '<input type="checkbox" id="aaa-oc-annc-confirm"> ',
              '<span id="aaa-oc-annc-confirm-text"></span>',
            '</label>',
            '<div class="aaa-oc-annc-actions">',
              '<button id="aaa-oc-annc-accept" class="button button-primary" disabled></button>',
            '</div>',
          '</div>',
        '</div>',
      '</div>'
    ].join('');
    $('body').append(html);
    console.log('[ANN] shell built');
  }

  function showOverlay() {
    console.log('[ANN] showOverlay');
    $('#aaa-oc-annc-overlay').fadeIn(120);
  }
  function hideOverlay() {
    console.log('[ANN] hideOverlay');
    $('#aaa-oc-annc-overlay').fadeOut(120, function () {
      $(this).remove();
    });
  }

  function wireEvents(currentId) {
    const $chk = $('#aaa-oc-annc-confirm');
    const $btn = $('#aaa-oc-annc-accept');

    $chk.off('change').on('change', function () {
      $btn.prop('disabled', !this.checked);
    });

    $btn.off('click').on('click', function () {
      $btn.prop('disabled', true);
      api.accept(currentId, function (res) {
        if (!res || !res.success) {
          $btn.prop('disabled', false);
          return;
        }
        step(); // fetch next or close
      });
    });
  }

  function fillModal(data) {
    $('#aaa-oc-annc-title').text(data.title || '');
    $('#aaa-oc-annc-content').html(data.content || '');
    $('#aaa-oc-annc-confirm').prop('checked', false);
    $('#aaa-oc-annc-confirm-text').text(AAA_OC_ANN.i18n.ack);
    $('#aaa-oc-annc-accept').text(AAA_OC_ANN.i18n.button).prop('disabled', true);
  }

  function step() {
    api.next(function (res) {
      if (!res || !res.success || !res.data) {
        hideOverlay();
        return;
      }
      if (!res.data.has) {
        hideOverlay();
        return;
      }
      fillModal(res.data);
      wireEvents(res.data.id);
      showOverlay();
    });
  }

  $(function () {
    console.log('[ANN] document ready');
    buildShell();
    step();
  });

})(jQuery);
