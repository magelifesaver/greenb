/**
 * File: assets/js/aaa-v4-address-loader.js
 * Purpose: Load billing/shipping addresses from static blocks into editable fields.
 * Also applies coords (lat/lng/verified) into hidden fields + status span.
 * Version: 1.1
 */

jQuery(function($){

  /**
   * Load address + coords from static block into editable fields
   */
  function loadAddressFromBlock(blockId){
    const data = $('#' + blockId).data('address');
    if (!data || $.isEmptyObject(data)) {
      alert('No address data available.');
      return;
    }

    // Fill editable shipping fields
    $('[name="shipping_address_1"]').val(data.address_1 || '');
    $('[name="shipping_address_2"]').val(data.address_2 || '');
    $('[name="shipping_city"]').val(data.city || '');
    $('[name="shipping_state"]').val(data.state || '');
    $('[name="shipping_postcode"]').val(data.postcode || '');
    $('[name="shipping_country"]').val(data.country || 'US');

    // Fill coords hidden fields
    $('#aaa_oc_latitude').val(data.lat || '');
    $('#aaa_oc_longitude').val(data.lng || '');
    $('#aaa_oc_coords_verified').val(data.verified || 'no');

    // Update the on-screen status
    $('#coords-status').text(data.verified && data.verified.toLowerCase() === 'yes' ? 'Yes' : 'No');

    console.log('[AAA-V4-AddressLoader]', blockId, data);
  }

  // Button bindings
  $('#load-billing-address').off('click').on('click', function(e){
    e.preventDefault();
    loadAddressFromBlock('billing-address-block');
  });

  $('#load-shipping-address').off('click').on('click', function(e){
    e.preventDefault();
    loadAddressFromBlock('shipping-address-block');
  });

});
