(function($){
  'use strict';

  function imToggleGroups($row) {
    var src = $row.find('.im-source').val();
    var $keyGroup   = $row.find('.im-key-group');
    var $tableGroup = $row.find('.im-table-group');

    if (!src) {
      src = 'meta';
    }

    if (src === 'table') {
      $tableGroup.show();
      $keyGroup.hide();
    } else {
      $tableGroup.hide();
      $keyGroup.show();
    }
  }

  $(function(){

    $('#im-add-col').on('click', function(e){
      e.preventDefault();

      var tpl = ''
        + '<div class="im-row">'
          + '<span class="dashicons dashicons-move im-handle"></span>'
          + '<input type="text" name="col[col][]" placeholder="column_name"/>'
          + '<select name="col[source][]" class="im-source">'
            + '<option value="core">core</option>'
            + '<option value="meta">meta</option>'
            + '<option value="computed">computed</option>'
            + '<option value="table">table</option>'
          + '</select>'
          + '<span class="im-key-group">'
            + '<input type="text" name="col[key][]" placeholder="key or computed token"/>'
          + '</span>'
          + '<span class="im-table-group" style="display:none;">'
            + '<input type="text" name="col[ext_table][]"  placeholder="table (suffix)"/>'
            + '<input type="text" name="col[ext_fk_col][]" placeholder="ID column"/>'
            + '<input type="text" name="col[ext_val_col][]" placeholder="value column"/>'
          + '</span>'
          + '<select name="col[type][]">'
            + '<option>VARCHAR(190)</option>'
            + '<option>VARCHAR(200)</option>'
            + '<option>TEXT</option>'
            + '<option>INT(11)</option>'
            + '<option>BIGINT(20) UNSIGNED</option>'
            + '<option>DECIMAL(12,6)</option>'
            + '<option>DECIMAL(18,6)</option>'
            + '<option>DATETIME</option>'
            + '<option>TINYINT(1)</option>'
          + '</select>'
          + '<label><input type="checkbox" name="col[primary][]" value="1"/> Primary</label>'
          + '<label><input type="checkbox" name="col[index][]" value="1"/> Index</label>'
          + '<label><input type="checkbox" name="col[unique][]" value="1"/> Unique</label>'
          + '<a href="#" class="im-remove">×</a>'
        + '</div>';

      var $row = $(tpl).appendTo('#im-cols');
      imToggleGroups($row);
    });

    $(document).on('click', '.im-remove', function(e){
      e.preventDefault();
      $(this).closest('.im-row').remove();
    });

    $(document).on('change', '.im-source', function(){
      imToggleGroups($(this).closest('.im-row'));
    });

    $('.im-sort').sortable({ handle: '.im-handle', axis: 'y' });

    $('#im-cols .im-row').each(function(){
      imToggleGroups($(this));
    });
  });
})(jQuery);
