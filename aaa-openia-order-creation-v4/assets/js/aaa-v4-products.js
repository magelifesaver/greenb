/**
 * File: products.js
 *
 * Handles:
 *   - Add/Remove product lines
 *   - Autocomplete product name → fill in price, stock, image, etc.
 *   - Recalculate totals on quantity/price change
 *
 * Depends on:
 *   - jQuery
 *   - recalculateEverything() (defined elsewhere)
 */

jQuery(function($){

  const placeholderImage = window.AAA_V4_PLACEHOLDER_IMAGE
    || 'https://via.placeholder.com/60x60?text=Image';

  // ──────────────────────────────────────────────────
  // Add a new product‐line row to the table
  // ──────────────────────────────────────────────────
  $('#add-product-line').off('click').on('click', function(){
    const index = $('#aaa-product-table tbody tr').length;

    const row = `
      <tr class="product-line">
        <td class="product-image">
          <img src="${placeholderImage}" style="max-width:60px; max-height:60px; cursor:pointer;">
        </td>
        <td>
          <input
            type="text"
            class="product-name-input"
            name="products[${index}][product_name]"
            style="width:100%;">
        </td>
        <td>
          <input
            type="number"
            class="product-qty-input"
            name="products[${index}][quantity]"
            value="1"
            style="width:60px;">
        </td>
        <td>
          <input
            type="text"
            class="product-price-input"
            name="products[${index}][unit_price]"
            style="width:80px;"
            data-original="">
        </td>
        <td>
          <input
            type="text"
            class="product-special-input"
            name="products[${index}][special_price]"
            style="width:80px;">
        </td>
        <td class="product-stock">-</td>
        <td class="line-total">-</td>
        <td>
          <input
            type="text"
            name="products[${index}][product_id]"
            style="width:80px;">
        </td>
        <td class="product-note">Manual</td>
        <td>
          <button type="button" class="line-discount-percent button-modern">[%]</button>
          <button type="button" class="line-discount-fixed button-modern">[$]</button>
          <button type="button" class="remove-line-discount button-modern">X</button>
          <button type="button" class="remove-product button-modern">Remove</button>
        </td>
      </tr>
    `;

    $('#aaa-product-table tbody').append(row);
    if ( typeof recalculateEverything === 'function' ) {
      recalculateEverything();
    }
  });


  // ──────────────────────────────────────────────────
  // Remove a product row
  // ──────────────────────────────────────────────────
  $(document).on('click', '.remove-product', function(){
    $(this).closest('tr').remove();
    if ( typeof recalculateEverything === 'function' ) {
      recalculateEverything();
    }
  });


  // ──────────────────────────────────────────────────
  // Autocomplete: when user types at least 3 chars, lookup product
  // ──────────────────────────────────────────────────
  $(document).on('input', '.product-name-input', function(){
    const $input = $(this);
    const term   = $input.val();
    const $row   = $input.closest('tr');

    if ( term.length < 3 ) return;

    $.post(ajaxurl, {
      action: 'aaa_v4_product_lookup',
      term: term
    }, function(response){
      if ( response.success && Array.isArray(response.data) && response.data.length === 1 ) {
        const product = response.data[0];

        $row.find('input[name$="[unit_price]"]')
            .val(product.price)
            .attr('data-original', product.price);

        $row.find('input[name$="[product_id]"]').val(product.product_id);
        $row.find('.product-stock').text(product.stock_quantity);
        $row.find('.product-note').text('Matched');

        if ( product.image_url ) {
          $row.find('.product-image img').attr('src', product.image_url);
        } else {
          $row.find('.product-image img').attr('src', placeholderImage);
        }

        if ( typeof recalculateEverything === 'function' ) {
          recalculateEverything();
        }
      }
    });
  });


  // ──────────────────────────────────────────────────
  // Live recalc when quantity or unit_price changes
  // ──────────────────────────────────────────────────
  $(document).on('input', '.product-qty-input, .product-price-input', function(){
    if ( typeof recalculateEverything === 'function' ) {
      recalculateEverything();
    }
  });

});
