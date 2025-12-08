(function($){
  'use strict';

  $('#ddd-js-plugin-select').on('change', function(){
    var plugin       = $(this).val();
    var excludes     = $('#ddd-js-exclude').val();
    var includeEmpty = $('#ddd-js-include-empty').is(':checked');

    if (!plugin) {
      $('#ddd-js-results').empty();
      return;
    }

    $('#ddd-js-results').html('<p>Scanning for enqueues…</p>');

    $.post(DDD_JS_Ajax.ajax_url, {
      action          : 'ddd_scan_js',
      plugin_file     : plugin,
      exclude_folders : excludes,
      include_empty   : includeEmpty ? 1 : 0,
      nonce           : DDD_JS_Ajax.nonce
    }, function(res){
      if (res.success) {
        var html = '<h2>Enqueue Map</h2><ul>';
        $.each(res.data, function(file, lines){
          html += '<li><strong>'+ file +'</strong><ul>';
          $.each(lines, function(_, line){
            html += '<li>'+ line +'</li>';
          });
          html += '</ul></li>';
        });
        html += '</ul>';
        $('#ddd-js-results').html(html);
      } else {
        $('#ddd-js-results').html('<p style="color:red">Error: '+ res.data +'</p>');
      }
    });
  });
})(jQuery);
