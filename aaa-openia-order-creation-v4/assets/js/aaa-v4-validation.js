/**
 * File: assets/js/aaa-v4-validation.js
 *
 * Handles client-side validation for Order Creator V4.
 * Fix: Respect Settings toggles for ID / DL Expiration / Birthday.
 * If a field is NOT required by settings, do not hard-block submission for it.
 */

jQuery(function ($) {
  // Normalize / helper
  function titleCaseName(s) {
    s = (s || '').trim();
    if (!s) return s;
    return s
      .toLowerCase()
      .replace(/\b([a-z])/g, function (m) { return m.toUpperCase(); })
      .replace(/\s+/g, ' ');
  }
  function capitalizeName(str) {
    str = (str || '').trim();
    return str ? str.charAt(0).toUpperCase() + str.slice(1).toLowerCase() : str;
  }

  // Settings flags from PHP (guard with safe defaults)
  const REQ = window.AAA_V4_REQUIRED_SETTINGS || {
    require_id_number: false,
    require_dl_expiration: false,
    require_birthday: false
  };

  $('#aaa-v4-order-form').on('submit', function (e) {
    const errors = [];

    // ---- Names: required + capitalization ----
    const $first = $('#customer_first_name');
    const $last  = $('#customer_last_name');
    const firstVal = ($first.val() || '').trim();
    const lastVal  = ($last.val()  || '').trim();

    if (!firstVal) { errors.push('First name is required.'); }
    else { const fixed = titleCaseName(firstVal); if (fixed !== firstVal) $first.val(fixed); }

    if (!lastVal) { errors.push('Last name is required.'); }
    else { const fixed = titleCaseName(lastVal); if (fixed !== lastVal) $last.val(fixed); }

    // ---- Phone: exactly 10 digits ----
    const $phone = $('#customer_phone');
    const digits = ($phone.val() || '').replace(/\D/g, '');
    if (digits.length !== 10) { errors.push('Phone number must be exactly 10 digits.'); }
    else { $phone.val(digits); }

    // ---- Email: basic format ----
    const email = ($('#customer_email').val() || '').trim();
    const emailOk = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
    if (!emailOk) errors.push('Enter a valid email address.');

    // ---- Conditional: DL Number ----
    const dln = ($('#afreg_additional_4532').val() || '').trim();
    if (REQ.require_id_number && !dln) {
      errors.push('Driver’s License number is required.');
    }

    // ---- Conditional: DL Expiration (and must be future) ----
    const dlExpStr = ($('#afreg_additional_4623').val() || '').trim();
    if (REQ.require_dl_expiration) {
      if (!dlExpStr) {
        errors.push('DL expiration date is required.');
      } else {
        const exp = new Date(dlExpStr);
        if (isNaN(exp.getTime())) {
          errors.push('DL expiration date is invalid.');
        } else {
          const today = new Date(); today.setHours(0,0,0,0);
          if (exp < today) errors.push('DL is expired.');
        }
      }
    } else if (dlExpStr) {
      // Not required, but if provided validate sensibly
      const exp = new Date(dlExpStr);
      if (isNaN(exp.getTime())) errors.push('DL expiration date is invalid.');
    }

    // ---- Conditional: Birthday (+ age/med-rec if available) ----
    const dobStr = ($('#afreg_additional_4625').val() || '').trim();
    if (REQ.require_birthday) {
      if (!dobStr) {
        errors.push('Birthday is required.');
      } else {
        const dob = new Date(dobStr);
        if (isNaN(dob.getTime())) {
          errors.push('Birthday is invalid.');
        } else {
          // If DOB exists (required), enforce age + med-rec rule
          const now = new Date();
          let age = now.getFullYear() - dob.getFullYear();
          const m = now.getMonth() - dob.getMonth();
          if (m < 0 || (m === 0 && now.getDate() < dob.getDate())) age--;
          if (age < 18) errors.push('Customer must be at least 18 years old.');
          else if (age < 21) {
            // Require Medical Recommendation upload for ages 18–20
            const hasPreview = !!($('#preview_medrec_img').attr('src') || '').trim();
            const $medFile   = $('#afreg_additional_4630');
            const hasFile    = $medFile.length && $medFile[0].files && $medFile[0].files.length > 0;
            if (!(hasPreview || hasFile)) {
              errors.push('Medical recommendation upload is required for ages 18–20.');
            }
          }
        }
      }
    } else if (dobStr) {
      // Not required, but if provided: validate + (optional) med-rec rule
      const dob = new Date(dobStr);
      if (isNaN(dob.getTime())) {
        errors.push('Birthday is invalid.');
      } else {
        const now = new Date();
        let age = now.getFullYear() - dob.getFullYear();
        const m = now.getMonth() - dob.getMonth();
        if (m < 0 || (m === 0 && now.getDate() < dob.getDate())) age--;
        if (age < 18) errors.push('Customer must be at least 18 years old.');
        else if (age < 21) {
          const hasPreview = !!($('#preview_medrec_img').attr('src') || '').trim();
          const $medFile   = $('#afreg_additional_4630');
          const hasFile    = $medFile.length && $medFile[0].files && $medFile[0].files.length > 0;
          if (!(hasPreview || hasFile)) {
            errors.push('Medical recommendation upload is required for ages 18–20.');
          }
        }
      }
    }
    // If Birthday is not required and not provided, we skip age checks entirely.

    // ---- Verified Address (Google Autocomplete) ----
    const coordsVerified = ($('input[name="aaa_oc_coords_verified"]').val() || '').toLowerCase();
    const lat = ($('input[name="aaa_oc_latitude"]').val() || '').trim();
    const lng = ($('input[name="aaa_oc_longitude"]').val() || '').trim();
    if (coordsVerified !== 'yes' || !lat || !lng) {
      errors.push('Delivery address must be verified (Google autocomplete).');
    }
    // ---- Order Source: required ----
    const orderSource = ($('#order_source_type').val() || '').trim();
    if (!orderSource) {
      errors.push('Order source is required.');
    }
    // ---- At least one product ----
    const productCount = $('#aaa-product-table tbody tr').length;
    if (productCount === 0) {
      errors.push('At least one product must be added to the order.');
    }

    if (errors.length) {
      e.preventDefault();
      alert('Please correct the following:\n\n' + errors.join('\n'));
      return false;
    }

    // ---- Phone uniqueness AJAX check (sync) ----
    let valid = true;
    $.ajax({
      url: ajaxurl,
      type: 'POST',
      data: { action: 'aaa_v4_validate_phone', phone: $phone.val(), user_id: $('#aaa_user_id').val() || 0 },
      async: false,
      success: function (res) {
        if (!res.success) {
          alert('Please correct the following:\n\n' + res.data.message);
          valid = false;
        }
      },
    });
    if (!valid) { e.preventDefault(); return false; }

    return true;
  });

  // Cap helpers
  $('#customer_first_name,#customer_last_name').on('blur', function () {
    $(this).val(capitalizeName($(this).val()));
  });
});
