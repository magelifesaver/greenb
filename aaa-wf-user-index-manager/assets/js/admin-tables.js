(function($){
  'use strict';
  $(function(){
    $('#wfuim-add-col').on('click', function(e){
      e.preventDefault();
      var $holder = $('#wfuim-cols');
      var tpl = '<div class="wfuim-row wfuim-col">'
        +'<span class="dashicons dashicons-move wfuim-handle"></span>'
        +'<input type="text" name="col[col][]" placeholder="column_name" />'
        +'<select name="col[source][]"><option value="core">core</option><option value="meta">meta</option><option value="computed">computed</option></select>'
        +'<input type="text" name="col[key][]" placeholder="key or computed token" />'
        +'<select name="col[type][]">'
        +'<option>VARCHAR(190)</option><option>VARCHAR(200)</option><option>TEXT</option><option>INT(11)</option><option>BIGINT(20) UNSIGNED</option><option>DECIMAL(12,6)</option><option>DECIMAL(18,6)</option><option>DATETIME</option><option>TINYINT(1)</option><option>BOOLEAN</option>'
        +'</select>'
        +'<label><input type="checkbox" name="col[primary][]" value="1"/> Primary</label>'
        +'<label><input type="checkbox" name="col[index][]" value="1"/> Index</label>'
        +'<label><input type="checkbox" name="col[unique][]" value="1"/> Unique</label>'
        +'<a href="#" class="wfuim-remove">×</a>'
        +'</div>';
      $holder.append(tpl);
    });

    $('#wfuim-add-hook').on('click', function(e){
      e.preventDefault();
      var $box = $('#wfuim-custom-hooks .wfuim-clone-holder');
      var proto = $box.data('proto');
      $box.append(proto);
    });

    $(document).on('click','.wfuim-remove', function(e){
      e.preventDefault();
      $(this).closest('.wfuim-row').remove();
    });

    $('.wfuim-sort').sortable({ handle: '.wfuim-handle', axis: 'y' });
  });
})(jQuery);
