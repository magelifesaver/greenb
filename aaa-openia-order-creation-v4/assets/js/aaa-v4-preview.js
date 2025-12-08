/**
 * File: assets/js/aaa-v4-preview.js
 * Version: 1.0
 */

jQuery(document).ready(($) => {
    const placeholderImage = window.AAA_V4_PLACEHOLDER_IMAGE || 'https://via.placeholder.com/60x60?text=Image';

    // ========== Relookup Customer (email or phone button) ==========
    $('#relookup-customer').off('click').on('click', function () {
        const email = $('#customer_email').val();
        const phone = $('#customer_phone').val();

        if (!email && !phone) {
            alert('Please enter an email or phone number for lookup.');
            return;
        }

        $('#relookup-message').html('Looking up customer...');

        $.post(ajaxurl, {
            action: 'aaa_v4_relookup_customer',
            email,
            phone
        }, (response) => {
            if (response.success) {
                // --- Handle multiple matches if returned ---
                if (response.data.code === 'multiple_phone_matches') {
                    let html = '<p>Multiple accounts share this phone. Please pick the correct one:</p>' +
                               '<ul style="list-style: none; padding-left: 0;">';
                    response.data.matches.forEach((u) => {
                        const addressLine = [
                            u.billing_address_1 || '',
                            u.billing_city || '',
                            u.billing_state || '',
                            u.billing_postcode || ''
                        ].filter(Boolean).join(', ');
                        html += `
                            <li style="margin-bottom: 10px; border: 1px solid #ddd; padding: 8px; border-radius: 4px;">
                                <strong>ID:</strong>
                                <a href="#" class="select-duplicate-user" data-userid="${u.user_id}">${u.user_id}</a>
                                &nbsp;|&nbsp;
                                <strong>Name:</strong> ${u.first_name} ${u.last_name}<br>
                                <strong>Phone:</strong> ${u.phone || '—'}<br>
                                <strong>Email:</strong> ${u.email || '—'}<br>
                                <strong>Address:</strong> ${addressLine || '—'}<br>
                                <a href="${u.profile_url}" target="_blank" style="font-size: 0.9em;">Edit Profile</a>
                            </li>
                        `;
                    });
                    html += '</ul>';
                    $('#relookup-message').html(html);
                    return;
                }

                // --- Single‐user payload (no ambiguity) ---
                const d = response.data;

                // Always clear both email & phone, then repopulate:
                $('#customer_email').val('');
                $('#customer_phone').val('');

                $('#customer_email').val(d.email || '');
                $('#customer_phone').val(d.phone || '');
                $('#customer_first_name').val(d.first_name || '');
                $('#customer_last_name').val(d.last_name || '');
                $('#aaa_user_id').val(d.user_id || 0);

                // --- Populate billing address fields ---
                $('[name="billing_address_1"]').val(d.billing_address_1 || '');
                $('[name="billing_address_2"]').val(d.billing_address_2 || '');
                $('[name="billing_city"]').val(d.billing_city || '');
                $('[name="billing_state"]').val(d.billing_state || '');
                $('[name="billing_postcode"]').val(d.billing_postcode || '');
                $('[name="billing_country"]').val(d.billing_country || '');

                // --- Populate the 6 custom meta fields: ---

                // 1) DL number:
                $('#afreg_additional_4532').val(d.afreg_additional_4532 || '');

                // 2) DL expiration date:
                $('#afreg_additional_4623').val(d.afreg_additional_4623 || '');

                // 3) Birthday:
                $('#afreg_additional_4625').val(d.afreg_additional_4625 || '');

                // 4) ID image preview (afreg_additional_4626):
                if (d.afreg_additional_4626) {
                    $('#preview_id_img').attr(
                        'src',
                        window.AAA_V4_BASE_UPLOAD_URL +
                        '/addify_registration_uploads/' +
                        encodeURIComponent(d.afreg_additional_4626)
                    );
                } else {
                    $('#preview_id_img').attr('src', '');
                }

                // 5) Selfie image preview (afreg_additional_4627):
                if (d.afreg_additional_4627) {
                    $('#preview_selfie_img').attr(
                        'src',
                        window.AAA_V4_BASE_UPLOAD_URL +
                        '/addify_registration_uploads/' +
                        encodeURIComponent(d.afreg_additional_4627)
                    );
                } else {
                    $('#preview_selfie_img').attr('src', '');
                }

                // 6) Medical record image preview (afreg_additional_4630):
                if (d.afreg_additional_4630) {
                    $('#preview_medrec_img').attr(
                        'src',
                        window.AAA_V4_BASE_UPLOAD_URL +
                        '/addify_registration_uploads/' +
                        encodeURIComponent(d.afreg_additional_4630)
                    );
                } else {
                    $('#preview_medrec_img').attr('src', '');
                }

                // If this was flagged as an email mismatch, confirm override
                if (d.email_mismatch) {
                    if (!confirm(d.message || "The email and phone correspond to different accounts. Overwrite fields with the matched account?")) {
                        $('#relookup-message').html('<span style="color:orange;">Lookup canceled.</span>');
                        return;
                    }
                }

                // Finally update the status line (Matched by “email” or “phone”)
                updateCustomerStatus({
                    matched_by: d.matched_by,
                    profile_url: d.profile_url
                });

                $('#relookup-message').html('<span style="color:green;">Customer info updated.</span>');
            } else {
                $('#relookup-message').html(`<span style="color:red;">${response.data.message}</span>`);
            }
        });
    });

    // ========== Handle click on one of the “duplicate” links ==========
    $(document).on('click', '.select-duplicate-user', function(e) {
        e.preventDefault();
        const userId = $(this).data('userid');

        $('#relookup-message').html('Loading selected account...');

        $.post(ajaxurl, {
            action: 'aaa_v4_relookup_customer',
            user_id: userId
        }, (response) => {
            if (response.success) {
                const d = response.data;

                // Clear both email & phone
                $('#customer_email').val('');
                $('#customer_phone').val('');

                // Populate email, phone, name
                $('#customer_email').val(d.email || '');
                $('#customer_phone').val(d.phone || '');
                $('#customer_first_name').val(d.first_name || '');
                $('#customer_last_name').val(d.last_name || '');

                // Populate billing address fields
                $('[name="billing_address_1"]').val(d.billing_address_1 || '');
                $('[name="billing_address_2"]').val(d.billing_address_2 || '');
                $('[name="billing_city"]').val(d.billing_city || '');
                $('[name="billing_state"]').val(d.billing_state || '');
                $('[name="billing_postcode"]').val(d.billing_postcode || '');
                $('[name="billing_country"]').val(d.billing_country || '');

                // --- Populate the 6 custom meta fields: ---

                // 1) DL number:
                $('#afreg_additional_4532').val(d.afreg_additional_4532 || '');

                // 2) DL expiration date:
                $('#afreg_additional_4623').val(d.afreg_additional_4623 || '');

                // 3) Birthday:
                $('#afreg_additional_4625').val(d.afreg_additional_4625 || '');

                // 4) ID image preview (afreg_additional_4626):
                if (d.afreg_additional_4626) {
                    $('#preview_id_img').attr(
                        'src',
                        window.AAA_V4_BASE_UPLOAD_URL +
                        '/addify_registration_uploads/' +
                        encodeURIComponent(d.afreg_additional_4626)
                    );
                } else {
                    $('#preview_id_img').attr('src', '');
                }

                // 5) Selfie image preview (afreg_additional_4627):
                if (d.afreg_additional_4627) {
                    $('#preview_selfie_img').attr(
                        'src',
                        window.AAA_V4_BASE_UPLOAD_URL +
                        '/addify_registration_uploads/' +
                        encodeURIComponent(d.afreg_additional_4627)
                    );
                } else {
                    $('#preview_selfie_img').attr('src', '');
                }

                // 6) Medical record image preview (afreg_additional_4630):
                if (d.afreg_additional_4630) {
                    $('#preview_medrec_img').attr(
                        'src',
                        window.AAA_V4_BASE_UPLOAD_URL +
                        '/addify_registration_uploads/' +
                        encodeURIComponent(d.afreg_additional_4630)
                    );
                } else {
                    $('#preview_medrec_img').attr('src', '');
                }

                updateCustomerStatus({
                    matched_by: d.matched_by,
                    profile_url: d.profile_url
                });

                $('#relookup-message').html('<span style="color:green;">Customer info loaded.</span>');
            } else {
                $('#relookup-message').html(`<span style="color:red;">${response.data.message}</span>`);
            }
        });
    });

    const updateCustomerStatus = (data) => {
        let status_html = `<p><strong>Status:</strong> <span style="color:green;">Existing Customer (Matched by ${data.matched_by || ''})</span>`;
        if (data.profile_url) {
            status_html += ` | <a href="${data.profile_url}" target="_blank">View Profile</a>`;
        }
        status_html += '</p>';
        $('#customer-status-display').html(status_html);
    };

    // ========== Add Product Line ==========
    $('#add-product-line').off('click').on('click', () => {
        const index = $('#aaa-product-table tbody tr').length;
        const row = `
        <tr class="product-line">
            <td class="product-image">
                <img src="${placeholderImage}" style="max-width:60px; max-height:60px; cursor:pointer;">
            </td>
            <td><input type="text" class="product-name-input" name="products[${index}][product_name]" style="width:100%;"></td>
            <td><input type="number" class="product-qty-input" name="products[${index}][quantity]" value="1" style="width:60px;"></td>
            <td><input type="text" class="product-price-input" name="products[${index}][unit_price]" style="width:80px;" data-original=""></td>
            <td><input type="text" class="product-special-input" name="products[${index}][special_price]" style="width:80px;"></td>
            <td class="product-stock">-</td>
            <td class="line-total">-</td>
            <td><input type="text" name="products[${index}][product_id]" style="width:80px;"></td>
            <td class="product-note">Manual</td>
            <td>
                <button type="button" class="line-discount-percent button-modern">[%]</button>
                <button type="button" class="line-discount-fixed button-modern">[$]</button>
                <button type="button" class="remove-line-discount button-modern">X</button>
                <button type="button" class="remove-product button-modern">Remove</button>
            </td>
        </tr>`;
        $('#aaa-product-table tbody').append(row);
        if (typeof recalculateEverything === 'function') recalculateEverything();
    });

    // ========== Remove Product ==========
    $(document).on('click', '.remove-product', function () {
        $(this).closest('tr').remove();
        if (typeof recalculateEverything === 'function') recalculateEverything();
    });

    // ========== Autocomplete for Single Match ==========
    $(document).on('input', '.product-name-input', function () {
        const $input = $(this);
        const term = $input.val();
        const $row = $input.closest('tr');
        if (term.length < 3) return;
        $.post(ajaxurl, {
            action: 'aaa_v4_product_lookup',
            term
        }, (response) => {
            if (response.success && response.data.length === 1) {
                const product = response.data[0];
                $row.find('input[name$="[unit_price]"]').val(product.price).attr('data-original', product.price);
                $row.find('input[name$="[product_id]"]').val(product.product_id);
                $row.find('.product-stock').text(product.stock_quantity);
                $row.find('.product-note').text('Matched');
                $row.find('.product-image img').attr('src', product.image_url || placeholderImage);
                if (typeof recalculateEverything === 'function') recalculateEverything();
            }
        });
    });

    // ========== Live Input Recalc ==========
    $(document).on('input', '.product-qty-input, .product-price-input', () => {
        if (typeof recalculateEverything === 'function') recalculateEverything();
    });

    // ========== Use Address On File ==========
    $('#use-shipping-address-on-file').off('click').on('click', function () {
        const email = $('#customer_email').val();
        if (!email) {
            return alert('Enter an email first.');
        }
        $('#address-on-file-status').text('Loading...');
        $.post(ajaxurl, {
            action: 'aaa_v4_get_shipping_address',
            email
        }, (response) => {
            if (response.success) {
                const d = response.data;
                $('[name="billing_address_1"]').val(d.shipping_address_1 || '');
                $('[name="billing_address_2"]').val(d.shipping_address_2 || '');
                $('[name="billing_city"]').val(d.shipping_city || '');
                $('[name="billing_state"]').val(d.shipping_state || '');
                $('[name="billing_postcode"]').val(d.shipping_postcode || '');
                $('[name="billing_country"]').val(d.shipping_country || 'US');
                $('#address-on-file-status').html('<span style="color:green;">✓ Applied</span>');
            } else {
                $('#address-on-file-status').html(`<span style="color:red;">${response.data.message}</span>`);
            }
        });
    });

    // ========== Use Billing Address On File ==========
    $('#use-billing-address-on-file').off('click').on('click', function () {
        const email = $('#customer_email').val();
        if (!email) {
            return alert('Enter an email first.');
        }
        $('#billing-address-on-file-status').text('Loading...');
        $.post(ajaxurl, {
            action: 'aaa_v4_get_billing_address',
            email
        }, (response) => {
            if (response.success) {
                const d = response.data;
                $('[name="billing_address_1"]').val(d.billing_address_1 || '');
                $('[name="billing_address_2"]').val(d.billing_address_2 || '');
                $('[name="billing_city"]').val(d.billing_city || '');
                $('[name="billing_state"]').val(d.billing_state || '');
                $('[name="billing_postcode"]').val(d.billing_postcode || '');
                $('[name="billing_country"]').val(d.billing_country || 'US');
                $('#billing-address-on-file-status').html('<span style="color:green;">✓ Applied</span>');
            } else {
                $('#billing-address-on-file-status').html(`<span style="color:red;">${response.data.message}</span>`);
            }
        });
    });

    // ========== Use Address From Order ==========
    $('#use-address-from-order').off('click').on('click', function () {
        const parsedAddress1 = $('#parsed_billing_address_1').text().trim();
        const parsedAddress2 = $('#parsed_billing_address_2').text().trim();
        const parsedCity     = $('#parsed_billing_city').text().trim();
        const parsedState    = $('#parsed_billing_state').text().trim();
        const parsedPostcode = $('#parsed_billing_postcode').text().trim();
        const parsedCountry  = $('#parsed_billing_country').text().trim();

        $('[name="billing_address_1"]').val(parsedAddress1);
        $('[name="billing_address_2"]').val(parsedAddress2);
        $('[name="billing_city"]').val(parsedCity);
        $('[name="billing_state"]').val(parsedState);
        $('[name="billing_postcode"]').val(parsedPostcode);
        $('[name="billing_country"]').val(parsedCountry);

        $('#billing-address-from-order-status').html('<span style="color:green;">✓ Applied</span>');
    });

    // ========== Lookup by Phone (with console.log debugging) ==========
    $('#lookup-by-phone').off('click').on('click', function () {
        let rawPhone = $('#customer_phone').val();
        const phone = rawPhone.replace(/\D/g, '');

        console.log('[Lookup by Phone] Handler triggered. Raw phone:', rawPhone, 'Sanitized phone:', phone);

        if (phone.length < 10) {
            console.log('[Lookup by Phone] Invalid phone length:', phone.length);
            alert('Enter a valid 10-digit phone number');
            return;
        }

        $('#relookup-message').html('Looking up by phone...');
        console.log('[Lookup by Phone] Sending AJAX to aaa_v4_relookup_customer with phone:', phone);

        $.post(ajaxurl, {
            action: 'aaa_v4_relookup_customer',
            phone: phone
        }, (response) => {
            console.log('[Lookup by Phone] AJAX response received:', response);

            if (response.success) {
                // Check for multiple matches
                if (response.data.code === 'multiple_phone_matches') {
                    console.log('[Lookup by Phone] Detected multiple_phone_matches. Rendering pick-list.');
                    let html = '<p>Multiple accounts share this phone. Please pick the correct one:</p>' +
                               '<ul style="list-style: none; padding-left: 0;">';
                    response.data.matches.forEach((u) => {
                        const addressLine = [
                            u.billing_address_1 || '',
                            u.billing_city || '',
                            u.billing_state || '',
                            u.billing_postcode || ''
                        ].filter(Boolean).join(', ');
                        html += `
                          <li style="margin-bottom: 10px; border: 1px solid #ddd; padding: 8px; border-radius: 4px;">
                            <strong>ID:</strong>
                            <a href="#" class="select-duplicate-user" data-userid="${u.user_id}">${u.user_id}</a>
                            &nbsp;|&nbsp;
                            <strong>Name:</strong> ${u.first_name} ${u.last_name}<br>
                            <strong>Phone:</strong> ${u.phone || '—'}<br>
                            <strong>Email:</strong> ${u.email || '—'}<br>
                            <strong>Address:</strong> ${addressLine || '—'}<br>
                            <a href="${u.profile_url}" target="_blank" style="font-size: 0.9em;">Edit Profile</a>
                          </li>
                        `;
                    });
                    html += '</ul>';
                    $('#relookup-message').html(html);
                    return;
                }

                // Single‐user case
                const d = response.data;
                console.log('[Lookup by Phone] Single user match:', d);

                // Always clear both fields first
                $('#customer_email').val('');
                $('#customer_phone').val('');

                // Then repopulate email & phone (and names)
                $('#customer_email').val(d.email || '');
                $('#customer_phone').val(d.phone || '');
                $('#customer_first_name').val(d.first_name || '');
                $('#customer_last_name').val(d.last_name || '');

                // Populate billing address fields
                console.log('[Lookup by Phone] Populating address fields:', {
                    address_1: d.billing_address_1,
                    address_2: d.billing_address_2,
                    city: d.billing_city,
                    state: d.billing_state,
                    postcode: d.billing_postcode,
                    country: d.billing_country
                });
                $('[name="billing_address_1"]').val(d.billing_address_1 || '');
                $('[name="billing_address_2"]').val(d.billing_address_2 || '');
                $('[name="billing_city"]').val(d.billing_city || '');
                $('[name="billing_state"]').val(d.billing_state || '');
                $('[name="billing_postcode"]').val(d.billing_postcode || '');
                $('[name="billing_country"]').val(d.billing_country || '');

                // --- Populate the 6 custom meta fields: ---

                // 1) DL number:
                $('#afreg_additional_4532').val(d.afreg_additional_4532 || '');

                // 2) DL expiration date:
                $('#afreg_additional_4623').val(d.afreg_additional_4623 || '');

                // 3) Birthday:
                $('#afreg_additional_4625').val(d.afreg_additional_4625 || '');

                // 4) ID image preview (afreg_additional_4626):
                if (d.afreg_additional_4626) {
                    $('#preview_id_img').attr(
                        'src',
                        window.AAA_V4_BASE_UPLOAD_URL +
                        '/addify_registration_uploads/' +
                        encodeURIComponent(d.afreg_additional_4626)
                    );
                } else {
                    $('#preview_id_img').attr('src', '');
                }

                // 5) Selfie image preview (afreg_additional_4627):
                if (d.afreg_additional_4627) {
                    $('#preview_selfie_img').attr(
                        'src',
                        window.AAA_V4_BASE_UPLOAD_URL +
                        '/addify_registration_uploads/' +
                        encodeURIComponent(d.afreg_additional_4627)
                    );
                } else {
                    $('#preview_selfie_img').attr('src', '');
                }

                // 6) Medical record image preview (afreg_additional_4630):
                if (d.afreg_additional_4630) {
                    $('#preview_medrec_img').attr(
                        'src',
                        window.AAA_V4_BASE_UPLOAD_URL +
                        '/addify_registration_uploads/' +
                        encodeURIComponent(d.afreg_additional_4630)
                    );
                } else {
                    $('#preview_medrec_img').attr('src', '');
                }

                updateCustomerStatus({
                    matched_by: 'phone',
                    profile_url: `${ajaxurl.replace('admin-ajax.php', 'user-edit.php')}?user_id=${d.user_id}`
                });

                $('#relookup-message').html('<span style="color:green;">Customer info updated.</span>');
                console.log('[Lookup by Phone] Form fields updated for user ID:', d.user_id);
            } else {
                console.error('[Lookup by Phone] AJAX returned error:', response.data.message);
                $('#relookup-message').html(`<span style="color:red;">${response.data.message}</span>`);
            }
        }).fail((jqXHR, textStatus, errorThrown) => {
            console.error('[Lookup by Phone] AJAX request failed:', textStatus, errorThrown);
            $('#relookup-message').html(`<span style="color:red;">Lookup failed: ${errorThrown}</span>`);
        });
    });

    // ========== Initial Totals ==========
    if ($('#aaa-product-table').length > 0) {
        setTimeout(() => {
            if (typeof recalculateEverything === 'function') recalculateEverything();
        }, 150);
    }
});
