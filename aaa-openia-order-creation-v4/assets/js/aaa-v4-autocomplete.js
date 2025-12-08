jQuery(document).ready(($) => {
    const placeholderImage = window.AAA_V4_PLACEHOLDER_IMAGE || 'https://via.placeholder.com/60x60?text=Image';

    $(document).on('input', '.product-name-input', function () {
        const $input = $(this);
        const term = $input.val();
        const $row = $input.closest('tr');

        if (term.length < 3) return;

        $.post(ajaxurl, {
            action: 'aaa_v4_product_lookup',
            term
        }, (response) => {
            $('.autocomplete-dropdown').remove();

            if (response.success && Array.isArray(response.data)) {
                const dropdown = $('<div class="autocomplete-dropdown"></div>').css({
                    position: 'absolute',
                    background: '#fff',
                    border: '1px solid #ccc',
                    zIndex: 1000,
                    width: $input.outerWidth(),
                    maxHeight: '200px',
                    overflowY: 'auto'
                });

                response.data.forEach((product) => {
                    const item = $('<div class="autocomplete-item"></div>')
                        .text(`${product.product_name} (${product.sku}) - $${product.price}`)
                        .css({ padding: '5px', cursor: 'pointer' })
                        .on('click', () => {
                            $input.val(product.product_name);
                            $row.find('input[name$="[unit_price]"]').val(product.price).attr('data-original', product.price);
                            $row.find('input[name$="[product_id]"]').val(product.product_id);
                            $row.find('.product-stock').text(product.stock_quantity);
                            $row.find('.product-image img').attr('src', product.image_url || placeholderImage);
                            appendNote($row, 'Autocomplete');
                            $('.autocomplete-dropdown').remove();
                            if (typeof recalculateEverything === 'function') recalculateEverything();
                        });

                    dropdown.append(item);
                });

                $('body').append(dropdown);
                const offset = $input.offset();
                dropdown.css({ top: offset.top + $input.outerHeight(), left: offset.left });
            }
        });
    });

    $(document).on('click', function (e) {
        if (!$(e.target).closest('.autocomplete-dropdown, .product-name-input').length) {
            $('.autocomplete-dropdown').remove();
        }
    });

    const appendNote = ($row, text) => {
        const $note = $row.find('.product-note');
        const current = $note.text().trim();
        const parts = current.split(',').map(p => p.trim()).filter(Boolean);
        if (!parts.includes(text)) parts.push(text);
        $note.text(parts.join(', '));
    };
});