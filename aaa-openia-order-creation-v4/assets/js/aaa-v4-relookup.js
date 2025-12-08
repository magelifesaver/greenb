/**
 * File: assets/js/aaa-v4-relookup.js
 * Version: 1.2
 *
 * Handles customer re-lookup and populates:
 *   - Customer profile fields (email, phone, name, etc.)
 *   - Read-only address blocks (#billing-address-block, #shipping-address-block)
 *   - Data-address JSON for loader buttons, including coords + verified
 */

jQuery(function ($) {
    function fillCustomerFields(d) {
        // Basic customer info
        $('#customer_email').val(d.email || '');
        $('#customer_phone').val(d.phone || '');
        $('#customer_first_name').val(d.first_name || '');
        $('#customer_last_name').val(d.last_name || '');
        $('#aaa_user_id').val(d.user_id || 0); 

        $('#afreg_additional_4532').val(d.afreg_additional_4532 || '');
        $('#afreg_additional_4623').val(d.afreg_additional_4623 || '');
        $('#afreg_additional_4625').val(d.afreg_additional_4625 || '');

        const base = window.AAA_V4_BASE_UPLOAD_URL + '/addify_registration_uploads/';
        $('#preview_id_img').attr('src',d.afreg_additional_4626?base+encodeURIComponent(d.afreg_additional_4626):'').toggle(!!d.afreg_additional_4626);
        $('#preview_selfie_img').attr('src',d.afreg_additional_4627?base+encodeURIComponent(d.afreg_additional_4627):'').toggle(!!d.afreg_additional_4627);
        $('#preview_medrec_img').attr('src',d.afreg_additional_4630?base+encodeURIComponent(d.afreg_additional_4630):'').toggle(!!d.afreg_additional_4630);
        // Update address blocks (billing + shipping + coords)
        updateAddressBlocks(d);
    }

    function updateAddressBlocks(d) {
        // ---------- Billing ----------
        const billingData = {
            address_1: d.billing_address_1 || '',
            address_2: d.billing_address_2 || '',
            city:      d.billing_city || '',
            state:     d.billing_state || '',
            postcode:  d.billing_postcode || '',
            country:   d.billing_country || 'US',
            lat:       d.billing_lat || '',
            lng:       d.billing_lng || '',
            verified:  d.billing_verified || 'no'
        };
        const billingText = billingData.address_1
            ? `${billingData.address_1}${billingData.address_2 ? ', ' + billingData.address_2 : ''}<br>${billingData.city}, ${billingData.state} ${billingData.postcode}`
            : '<em>No billing address on file</em>';
        $('#billing-address-block')
            .data('address', billingData)
            .html(
                billingText +
                `<div style="font-size:0.9em; margin-top:4px; color:#555;">
                   Lat: ${billingData.lat || '-'} | Lng: ${billingData.lng || '-'}<br>
                   Verified: ${billingData.verified}
                 </div>`
            );

        // ---------- Shipping ----------
        const shippingData = {
            address_1: d.shipping_address_1 || '',
            address_2: d.shipping_address_2 || '',
            city:      d.shipping_city || '',
            state:     d.shipping_state || '',
            postcode:  d.shipping_postcode || '',
            country:   d.shipping_country || 'US',
            lat:       d.shipping_lat || '',
            lng:       d.shipping_lng || '',
            verified:  d.shipping_verified || 'no'
        };
        const shippingText = shippingData.address_1
            ? `${shippingData.address_1}${shippingData.address_2 ? ', ' + shippingData.address_2 : ''}<br>${shippingData.city}, ${shippingData.state} ${shippingData.postcode}`
            : '<em>No shipping address on file</em>';
        $('#shipping-address-block')
            .data('address', shippingData)
            .html(
                shippingText +
                `<div style="font-size:0.9em; margin-top:4px; color:#555;">
                   Lat: ${shippingData.lat || '-'} | Lng: ${shippingData.lng || '-'}<br>
                   Verified: ${shippingData.verified}
                 </div>`
            );
    }

    function updateStatus(d) {
        if (typeof window.AAA_V4_updateCustomerStatus === 'function') {
            window.AAA_V4_updateCustomerStatus({
                matched_by: d.matched_by,
                profile_url: d.profile_url
            });
        }
    }

    function buildPickList(matches) {
        let html = '<p>Multiple accounts share this phone. Please pick the correct one:</p><ul style="list-style: none; padding-left: 0;">';
        matches.forEach(u => {
            const addressLine = [
                u.billing_address_1 || '',
                u.billing_city || '',
                u.billing_state || '',
                u.billing_postcode || ''
            ].filter(Boolean).join(', ');

            html += `
              <li style="margin-bottom:10px; border:1px solid #ddd; padding:8px; border-radius:4px;">
                <strong>ID:</strong> <a href="#" class="select-duplicate-user" data-userid="${u.user_id}">${u.user_id}</a> |
                <strong>Name:</strong> ${u.first_name} ${u.last_name}<br>
                <strong>Phone:</strong> ${u.phone || '—'}<br>
                <strong>Email:</strong> ${u.email || '—'}<br>
                <strong>Address:</strong> ${addressLine || '—'}<br>
                <a href="${u.profile_url}" target="_blank" style="font-size:0.9em;">Edit Profile</a>
              </li>`;
        });
        html += '</ul>';
        return html;
    }

    // Lookup handler
    $('#relookup-customer').off('click').on('click', function () {
        const email = $('#customer_email').val();
        const phone = $('#customer_phone').val();

        if (!email && !phone) {
            alert('Please enter an email or phone number for lookup.');
            return;
        }

        $('#relookup-message').html('Looking up customer…');

        $.post(ajaxurl, {
            action: 'aaa_v4_relookup_customer',
            email,
            phone
        }, function (response) {
            if (response.success) {
                const d = response.data;

                if (d.code === 'multiple_phone_matches') {
                    $('#relookup-message').html(buildPickList(d.matches));
                    return;
                }

                fillCustomerFields(d);

                if (d.email_mismatch) {
                    if (!confirm(d.message || "The email and phone correspond to different accounts. Overwrite fields with the matched account?")) {
                        $('#relookup-message').html('<span style="color:orange;">Lookup canceled.</span>');
                        return;
                    }
                }

                updateStatus(d);
                $('#relookup-message').html('<span style="color:green;">Customer info updated.</span>');
            } else {
                $('#relookup-message').html(`<span style="color:red;">${response.data.message}</span>`);
            }
        });
    });

    // Handle multiple phone matches
    $(document).on('click', '.select-duplicate-user', function (e) {
        e.preventDefault();
        const userId = $(this).data('userid');
        $('#relookup-message').html('Loading selected account…');

        $.post(ajaxurl, {
            action: 'aaa_v4_relookup_customer',
            user_id: userId
        }, function (response) {
            if (response.success) {
                fillCustomerFields(response.data);
                updateStatus(response.data);
                $('#relookup-message').html('<span style="color:green;">Customer info loaded.</span>');
            } else {
                $('#relookup-message').html(`<span style="color:red;">${response.data.message}</span>`);
            }
        });
    });

    // Lookup by phone shortcut
    $('#lookup-by-phone').off('click').on('click', function () {
        let rawPhone = $('#customer_phone').val();
        const phone = rawPhone.replace(/\D/g, '');

        if (phone.length < 10) {
            alert('Enter a valid 10-digit phone number');
            return;
        }

        $('#relookup-message').html('Looking up by phone…');

        $.post(ajaxurl, {
            action: 'aaa_v4_relookup_customer',
            phone
        }, function (response) {
            if (response.success) {
                const d = response.data;

                if (d.code === 'multiple_phone_matches') {
                    $('#relookup-message').html(buildPickList(d.matches));
                    return;
                }

                fillCustomerFields(d);
                updateStatus(d);
                $('#relookup-message').html('<span style="color:green;">Customer info updated.</span>');
            } else {
                $('#relookup-message').html(`<span style="color:red;">${response.data.message}</span>`);
            }
        });
    });
});
