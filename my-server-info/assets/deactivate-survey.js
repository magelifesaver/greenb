(function ($) {
  'use strict';

  function findDeactivateLinks(pluginBasename) {
    var selector =
      'a[href*="plugins.php?action=deactivate"][href*="plugin=' +
      encodeURIComponent(pluginBasename) +
      '"]';
    return $(selector);
  }

  function resetForm() {
    $('#msi-error').hide().text('');
    $('input[name="msi_reason"]').prop('checked', false);
    $('.msi-survey-option').removeClass('active');
    $('.msi-survey-details').val('');
  }

  function bindReasonRadios() {
    $('input[name="msi_reason"]').on('change', function () {
      var $opt = $(this).closest('.msi-survey-option');

    
      $('.msi-survey-option').removeClass('active');
      $('.msi-survey-details').val('');

      
      if (this.checked) {
        $opt.addClass('active');
      }
    });
  }

  function openModal() {
    $('#msi-submit').text(MyServerInfoSurvey.i18n.submit);
    $('#msi-skip').text(MyServerInfoSurvey.i18n.skip);
    $('#msi-cancel').text(MyServerInfoSurvey.i18n.cancel);
    resetForm();
    $('#myserverinfo-survey-modal').fadeIn(120);
  }

  function closeModal() {
    $('#myserverinfo-survey-modal').fadeOut(100);
  }

  $(document).ready(function () {
    var originalHref = null;

    
    findDeactivateLinks(MyServerInfoSurvey.pluginBasename).each(function () {
      $(this).on('click', function (e) {
        e.preventDefault();
        originalHref = $(this).attr('href');
        openModal();
      });
    });

    bindReasonRadios();

    $('#msi-cancel').on('click', function (e) {
      e.preventDefault();
      closeModal();
    });

    $('#msi-skip').on('click', function (e) {
      e.preventDefault();
      if (originalHref) {
        window.location.href = originalHref;
      }
    });

    $('#msi-submit').on('click', function (e) {
      e.preventDefault();

      var $selected = $('input[name="msi_reason"]:checked');
      if (!$selected.length) {
        $('#msi-error')
          .text(MyServerInfoSurvey.i18n.errorNoReason || 'Please select a reason.')
          .show();
        return;
      }

      var reason = $selected.val();
      var $opt = $selected.closest('.msi-survey-option');
      var details = ($opt.find('.msi-survey-details').val() || '').trim();

      
      if (details) {
        details = reason + ': ' + details;
      }

      $.ajax({
        method: 'POST',
        url: MyServerInfoSurvey.ajaxUrl,
        data: {
          action: 'myserverinfo_submit_survey',
          nonce: MyServerInfoSurvey.nonce,
          reason: reason,
          details: details
        },
        complete: function () {
          if (originalHref) {
            window.location.href = originalHref;
          }
        }
      });
    });
  });
})(jQuery);