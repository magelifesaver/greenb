(function($){
  'use strict';

  $('#dt-search-btn').on('click', function(e){
    e.preventDefault();
    var plugin = $('#dt-search-plugin').val();
    var term   = $('#dt-search-term').val();
    var mode   = $('input[name="dt-mode"]:checked').val();

    if (!plugin || !term) {
      $('#dt-search-results').html('<p style="color:red">Please select a plugin and enter a search term.</p>');
      return;
    }

    $('#dt-search-results').html('<p>Searching…</p>');

    $.post(DTPro_Ajax.ajax_url, {
      action : 'dt_search',
      nonce  : DTPro_Ajax.nonce_search,
      plugin : plugin,
      term   : term,
      mode   : mode
    })
    .done(function(res){
      if (res.success) {
        var html = '<h2>Search Results</h2>';
        if ($.isEmptyObject(res.data)) {
          html += '<p>No matches found.</p>';
        } else {
          html += '<ul>';
          $.each(res.data, function(file, matches){
            html += '<li><strong>' + file + '</strong><ul>';
            if (Array.isArray(matches)) {
              $.each(matches, function(_, line){
                html += '<li>' + line + '</li>';
              });
            } else {
              html += '<li>' + matches + '</li>';
            }
            html += '</ul></li>';
          });
          html += '</ul>';
        }
        $('#dt-search-results').html(html);
      } else {
        $('#dt-search-results').html('<p style="color:red">Error: '+ res.data +'</p>');
      }
    })
    .fail(function(jqXHR, textStatus, errorThrown){
      console.error('AJAX failed:', textStatus, errorThrown, jqXHR);
      $('#dt-search-results').html('<p style="color:red">AJAX error: '+ textStatus +'</p>');
    });
  });

  // Flush cache handler
  $('#dt-flush-cache').on('click', function(e){
    e.preventDefault();
    var btn = $(this);
    btn.text('Flushing…').prop('disabled', true);
    $.post(DTPro_Ajax.ajax_url, {
      action : 'dt_flush_cache',
      nonce  : DTPro_Ajax.nonce_flush
    }, function(res){
      if (res.success) {
        btn.text('Cache Flushed');
        setTimeout(function(){ btn.text('Flush WP Cache').prop('disabled', false); }, 2000);
      } else {
        btn.text('Error');
      }
    });
  });

  // Flush rewrite rules handler
  $('#dt-flush-rewrite').on('click', function(e){
    e.preventDefault();
    var btn = $(this);
    btn.text('Flushing…').prop('disabled', true);
    // reuse same AJAX endpoint as cache flush
    $.post(DTPro_Ajax.ajax_url, {
      action : 'dt_flush_cache',
      nonce  : DTPro_Ajax.nonce_flush
    }, function(res){
      if (res.success) {
        btn.text('Rewrite Rules Flushed');
        setTimeout(function(){ btn.text('Flush Rewrite Rules').prop('disabled', false); }, 2000);
      } else {
        btn.text('Error');
      }
    });
  });

})(jQuery);
