// @version 2.1.0
jQuery(function ($) {
  const D = window.DDD_DT_DebugLog || {};
  const $out = $('#ddd-dt-debug-log-output');
  const $status = $('#ddd-dt-debug-log-status');
  let offset = 0;
  let timer = null;

  function append(text) {
    if (!text) return;
    const prev = $out.val();
    $out.val(prev + text);
    $out.scrollTop($out[0].scrollHeight);
  }

  function tail() {
    $.post(D.ajax_url, { action: D.tail_action, nonce: D.nonce_tail, offset: offset }, function (res) {
      if (!res || !res.success) {
        $status.text('Tail failed.');
        stop();
        return;
      }
      offset = res.data.offset || 0;
      append(res.data.content || '');
    });
  }

  function start() {
    if (timer) return;
    $status.text('Tailing...');
    timer = setInterval(tail, 1500);
    tail();
  }

  function stop() {
    if (!timer) return;
    clearInterval(timer);
    timer = null;
    $status.text('Stopped.');
  }

  $('#ddd-dt-debug-log-start').on('click', start);
  $('#ddd-dt-debug-log-stop').on('click', stop);
  $('#ddd-dt-debug-log-clear').on('click', function () { $out.val(''); offset = 0; });

  $('#ddd-dt-debug-log-snapshot').on('click', function () {
    $status.text('Creating snapshot...');
    $.post(D.ajax_url, { action: D.snapshot_action, nonce: D.nonce_snapshot }, function (res) {
      if (!res || !res.success) return $status.text('Snapshot failed.');
      $status.text('Snapshot created.');
    });
  });

  $('#ddd-dt-debug-log-clear-snapshot').on('click', function () {
    $status.text('Clearing snapshot...');
    $.post(D.ajax_url, { action: D.clear_snap_action, nonce: D.nonce_clear_snap }, function (res) {
      $status.text(res && res.success ? 'Snapshot cleared.' : 'Clear failed.');
    });
  });
});
