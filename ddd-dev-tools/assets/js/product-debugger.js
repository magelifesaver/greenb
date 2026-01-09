// @version 2.1.0
jQuery(function ($) {
  $('#ddd-dt-pdbg-copy').on('click', function () {
    const $pre = $('#ddd-dt-pdbg-output');
    if (!$pre.length) return;
    const text = $pre.text();
    if (navigator.clipboard && navigator.clipboard.writeText) {
      navigator.clipboard.writeText(text).then(() => alert('Copied.'));
      return;
    }
    const $ta = $('<textarea>').val(text).appendTo('body').select();
    document.execCommand('copy');
    $ta.remove();
    alert('Copied.');
  });
});
