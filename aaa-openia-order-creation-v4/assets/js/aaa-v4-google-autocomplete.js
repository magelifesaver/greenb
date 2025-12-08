/**
 * File: assets/js/aaa-v4-google-autocomplete.js
 * Version: 1.0
 */

jQuery(document).ready(function($){
    const apiKey = window.AAA_V4_GOOGLE_API_KEY;
    if (!apiKey) return;

    // Load Google Places library dynamically
    const script = document.createElement('script');
    script.src = `https://maps.googleapis.com/maps/api/js?key=${apiKey}&libraries=places`;
    script.async = true;
    document.head.appendChild(script);

    script.onload = function(){
        const inputs = [
            'input[name="billing_address_1"]',
            'input[name="billing_city"]',
            'input[name="billing_postcode"]'
        ];
        inputs.forEach(sel => {
            const el = document.querySelector(sel);
            if (!el) return;
            const autocomplete = new google.maps.places.Autocomplete(el, {
                types: ['address'],
                componentRestrictions: { country: 'us' }
            });

            autocomplete.addListener('place_changed', function(){
                const place = autocomplete.getPlace();
                if (!place.address_components) return;

                let street = '', city = '', state = '', zip = '';
                place.address_components.forEach(c => {
                    if (c.types.includes('street_number')) street = c.long_name + ' ' + street;
                    if (c.types.includes('route')) street += c.long_name;
                    if (c.types.includes('locality')) city = c.long_name;
                    if (c.types.includes('administrative_area_level_1')) state = c.short_name;
                    if (c.types.includes('postal_code')) zip = c.long_name;
                });

                $('[name="billing_address_1"]').val(street);
                $('[name="billing_city"]').val(city);
                $('[name="billing_state"]').val(state);
                $('[name="billing_postcode"]').val(zip);
            });
        });
    };
});
