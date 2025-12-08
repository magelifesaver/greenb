(function($){
  'use strict';

  $('#dt-plugin-select').on('change', function(){
    var plugin       = $(this).val();
    var excludes     = $('#dt-exclude-folders').val();
    var includeEmpty = $('#dt-include-empty').is(':checked');

    if (!plugin) {
      $('#dt-results').empty();
      return;
    }

    $('#dt-results').html('<p>Scanning for dependencies…</p>');

    $.post(DT_Ajax.ajax_url, {
      action          : 'dt_scan_plugin',
      plugin_file     : plugin,
      exclude_folders : excludes,
      include_empty   : includeEmpty ? 1 : 0,
      nonce           : DT_Ajax.nonce
    }, function(res){
      if (res.success) {
        var html = '<h2>Dependency Map</h2><ul>';
        $.each(res.data, function(file, lines){
          html += '<li><strong>'+ file +'</strong><ul>';
          $.each(lines, function(_, line){
            html += '<li>'+ line +'</li>';
          });
          html += '</ul></li>';
        });
        html += '</ul>';
        $('#dt-results').html(html);
      } else {
        $('#dt-results').html('<p style="color:red">Error: '+ res.data +'</p>');
      }
    });
  });
})(jQuery);
