/*
 * Handles UI interactions and AJAX requests for the AAA Account Portal.
 * Toggling between login and lost password views, showing messages and
 * disabling buttons on submit. Relies on jQuery (loaded by WordPress).
 */
(function ($) {
  // Helper for debug logging when WP_DEBUG is true.
  const debug = !!AAA_ACP.debug;
  function log() {
    if (debug && console && console.log) {
      console.log('[AAA_ACP]', ...arguments);
    }
  }

  // Display message in message box. isError toggles styling.
  function showMessage($wrap, text, isError) {
    const $msg = $wrap.find('.aaa-acp-msg');
    $msg.text(text).removeClass('is-error');
    if (isError) {
      $msg.addClass('is-error');
    }
    $msg.show();
  }

  // Switch between login and lost password tabs.
  function switchTab($wrap, tab) {
    $wrap.find('.aaa-acp-tab').removeClass('is-active');
    $wrap.find('.aaa-acp-tab[data-tab="' + tab + '"]').addClass('is-active');
    $wrap.find('.aaa-acp-form').hide();
    $wrap.find('.aaa-acp-form[data-form="' + tab + '"]').show();
    $wrap.find('.aaa-acp-msg').hide();
  }

  // Handle tab clicks.
  $(document).on('click', '.aaa-acp-tab', function () {
    const $wrap = $(this).closest('.aaa-acp');
    const tab = $(this).data('tab');
    switchTab($wrap, tab);
  });

  // Handle forgot password link click.
  $(document).on('click', '.aaa-acp-forgot', function (e) {
    e.preventDefault();
    const $wrap = $(this).closest('.aaa-acp');
    switchTab($wrap, 'lost');
  });

  // Handle back link in lost password form.
  $(document).on('click', '.aaa-acp-back', function (e) {
    e.preventDefault();
    const $wrap = $(this).closest('.aaa-acp');
    switchTab($wrap, 'login');
  });

  // Submit handler for both forms.
  $(document).on('submit', '.aaa-acp-form', function (e) {
    e.preventDefault();
    const $form = $(this);
    const $wrap = $form.closest('.aaa-acp');
    const type = $form.data('form');
    const $button = $form.find('.aaa-acp-submit');

    if ($button.prop('disabled')) {
      return;
    }
    $button.prop('disabled', true);

    const data = {
      action: type === 'login' ? 'aaa_acp_login' : 'aaa_acp_lost_password',
      nonce: AAA_ACP.nonce,
      login: $form.find('input[name="login"]').val().trim(),
      password: $form.find('input[name="password"]').val() || '',
      remember: $form.find('input[name="remember"]').is(':checked') ? 1 : 0,
    };

    log('Submitting', type, data);

    $.post(AAA_ACP.ajax_url, data)
      .done(function (resp) {
        if (!resp || !resp.success) {
          const msg = (resp && resp.data && resp.data.message) ? resp.data.message : 'Request failed.';
          showMessage($wrap, msg, true);
          return;
        }
        if (type === 'login') {
          showMessage($wrap, 'Logging in…', false);
          window.location.reload();
        } else {
          // Lost password
          const msg = (resp.data && resp.data.message) ? resp.data.message : 'Check your email for the reset link.';
          showMessage($wrap, msg, false);
          // Switch back to login form for clarity.
          switchTab($wrap, 'login');
          $form[0].reset();
        }
      })
      .fail(function () {
        showMessage($wrap, 'An error occurred. Please try again.', true);
      })
      .always(function () {
        $button.prop('disabled', false);
      });
  });
})(jQuery);