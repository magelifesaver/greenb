/* @version 2.0.0 */
jQuery(function($){
  $(document).on('click','form input.delete',function(){
    return confirm('Delete all log files?');
  });
});
