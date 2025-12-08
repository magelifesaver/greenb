/**
 * File: assets/js/aaa-v4-order-creator-autocomplete.js
 * Purpose: Google Places → Order Creator (Admin).
 * Binds to shipping address fields, populates city/state/zip, and sets coords.
 * Version: 1.1
 */

jQuery(function ($) {
  const apiKey = window.AAA_V4_GOOGLE_API_KEY || '';
  if (!apiKey) {
    console.warn('[AAA-OC][Autocomplete] No API key configured.');
    return;
  }

  // Load Google Maps script once
  function loadGoogle(cb) {
    if (window.google && window.google.maps && window.google.maps.places) {
      return cb();
    }
    const s = document.createElement('script');
    s.src =
      'https://maps.googleapis.com/maps/api/js?key=' +
      encodeURIComponent(apiKey) +
      '&libraries=places';
    s.async = true;
    s.defer = true;
    s.onload = cb;
    document.head.appendChild(s);
  }

  loadGoogle(function () {
    const addr1 = document.querySelector('input[name="shipping_address_1"]');
    if (!addr1) {
      console.warn('[AAA-OC][Autocomplete] shipping_address_1 not found.');
      return;
    }

    const autocomplete = new google.maps.places.Autocomplete(addr1, {
      types: ['address'],
      fields: ['address_components', 'geometry'],
    });

    autocomplete.addListener('place_changed', function () {
      const place = autocomplete.getPlace();
      if (!place || !place.geometry) return;

      let street = '',
        city = '',
        state = '',
        zip = '';

      (place.address_components || []).forEach((c) => {
        if (c.types.includes('street_number'))
          street = c.long_name + ' ' + street;
        if (c.types.includes('route')) street += c.long_name;
        if (c.types.includes('locality')) city = c.long_name;
        if (c.types.includes('administrative_area_level_1'))
          state = c.short_name;
        if (c.types.includes('postal_code')) zip = c.long_name;
      });

      // Fill shipping fields
      $('[name="shipping_address_1"]').val(street);
      $('[name="shipping_city"]').val(city);
      $('[name="shipping_state"]').val(state);
      $('[name="shipping_postcode"]').val(zip);

      const lat = place.geometry.location.lat();
      const lng = place.geometry.location.lng();

      $('#aaa_oc_latitude').val(lat);
      $('#aaa_oc_longitude').val(lng);
      $('#aaa_oc_coords_verified').val('yes');
      $('#coords-status').text('Yes');

      console.log('[AAA-OC][Autocomplete]', {
        street,
        city,
        state,
        zip,
        lat,
        lng,
      });
    });

    console.log('[AAA-OC][Autocomplete] bound to shipping_address_1');
  });
});
