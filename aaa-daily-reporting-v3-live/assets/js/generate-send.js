jQuery(function($){
  $('#aaa-send-report-btn').on('click', function() {
    const email   = $('#aaa_email_field').val().trim();
    const date    = $('#aaa_report_date').val().trim();
    const $status = $('#aaa-send-report-status').removeClass('error success').text('Sending…');

    if (!email || !date) {
      $status.text('❌ Please enter both email(s) and a date.').addClass('error');
      return;
    }

    $.post(AAAgen.ajax_url, {
      action: 'aaa_send_report_by_date',
      nonce: AAAgen.custom_date_nonce,
      email,
      date
    }).done(function(res){
      if (res.success) {
        $status.text('✅ ' + res.data).addClass('success');
      } else {
        $status.text('❌ ' + res.data).addClass('error');
      }
    }).fail(function(_, textStatus){
      $status.text('❌ Request failed: ' + textStatus).addClass('error');
    });
  });
});
