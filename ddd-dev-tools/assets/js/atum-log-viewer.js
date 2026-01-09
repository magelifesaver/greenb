// @version 2.1.0
jQuery(function ($) {
  if (!$.fn || !$.fn.DataTable) return;
  const $t = $('#ddd-dt-atum-logs-table');
  if (!$t.length) return;
  $t.DataTable({ pageLength: 25, order: [[0, 'desc']] });
});
