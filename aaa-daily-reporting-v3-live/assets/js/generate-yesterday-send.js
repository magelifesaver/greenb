/**
 * File: assets/js/generate-yesterday-send.js
 * Description: AJAX trigger for “Generate & Send Yesterday’s Report”
 */
jQuery(function($){
  $('#aaa-generate-yesterday-send-btn').on('click', function(){
    var $status = $('#aaa-generate-yesterday-send-status');
    $status.text('Sending…').removeClass('error success');

    $.post(
      aaaGenerateSendAjax.ajax_url,
      {
        action: 'aaa_send_yesterday_report',
        nonce:  aaaGenerateSendAjax.nonce
      }
    ).done(function(res){
      if(res.success){
        $status.text('✅ ' + res.data).addClass('success');
      } else {
        $status.text('❌ ' + res.data).addClass('error');
      }
    }).fail(function(_, textStatus){
      $status.text('❌ Request failed: ' + textStatus).addClass('error');
    });
  });
});
