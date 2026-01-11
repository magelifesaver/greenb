const cl_ajaxurl=clg_vars.ajaxurl;
const cl_data_nonce= clg_vars.data_nonce;

document.addEventListener('DOMContentLoaded', function () {
    const searchInput = document.getElementById('product-search');
    const selectedProductsDiv = document.getElementById('selected-products');
    const fixedSubtotalInput = document.getElementById('fixed-subtotal');
    let timeout = null;

    // Autocomplete functionality with AJAX
    searchInput.addEventListener('keyup', function () {
        // Clear any existing timeout
        clearTimeout(timeout);

        jQuery(".gvncl_notfound").remove();
        // Set a new timeout to delay the AJAX request
        timeout = setTimeout(() => {
            const searchTerm = searchInput.value.trim().toLowerCase();

            if (searchTerm.length < 2) {
                // Clear suggestions if input is too short
                const dropdown = document.getElementById('autocomplete-dropdown');
                if (dropdown) dropdown.innerHTML = '';
                return;
            }

            // Perform the AJAX request
            jQuery.post(cl_ajaxurl, {
                action: 'fetch_product_suggestions',
                _wpnonce: cl_data_nonce,
                search: searchTerm
            }, function (response) {
                const suggestions = response.data;

               
                // Create the dropdown
                let dropdown = document.getElementById('autocomplete-dropdown');
                if (!dropdown) {
                    dropdown = document.createElement('div');
                    dropdown.id = 'autocomplete-dropdown';
                    document.querySelector('.wrapautogen').appendChild(dropdown);
                }

                if(suggestions==''){
                    jQuery("#autocomplete-dropdown").html('<p class="gvncl_notfound">No products found</p>');
                    return;
                }

                dropdown.innerHTML = suggestions.map(s => `
                    <div class="autocomplete-item" data-price="${s.price}" data-id="${s.id}">
                        ${s.name}
                    </div>
                `).join('');

                // Add click listeners to the suggestions
                dropdown.querySelectorAll('.autocomplete-item').forEach(item => {
                    item.addEventListener('click', function () {
                        const productId = this.getAttribute('data-id');
                        const productPrice = this.getAttribute('data-price');
                        const productName = this.textContent;

                        const row = document.createElement('div');
                        row.classList.add('product-row');
                        row.innerHTML = `
                            <span>${productName}</span>
                            <div class="product-row-inner"><input type="number" class="quantity" value="1" min="1"></div>
                            <div class="product-row-inner"><input type="number" placeholder="price" class="price" value="${productPrice}" min="1"></div>
                            <div class="product-row-inner"><button class="remove-product">X</button></div>
                        `;
                        row.dataset.id = productId;
                        row.dataset.price = productPrice;
                        selectedProductsDiv.appendChild(row);

                        // Clear search input and dropdown
                        searchInput.value = '';
                        dropdown.innerHTML = '';
                        jQuery(".pregenerate").parent().show();
                        jQuery("#selected-products").show();
                    });
                });
            });
        }, 300); // 300ms delay
    });

    // Remove product logic
    selectedProductsDiv.addEventListener('click', function (e) {
        if (e.target.classList.contains('remove-product')) {
            e.target.parentElement.parentElement.remove();
            if (jQuery("#selected-products").html() === '') {
                jQuery("#selected-products .pregenerate").parent().hide();
            }
        }
    });

    // Generate link logic
    document.getElementById('generate-link').addEventListener('click', function() {
        const selectedProducts = Array.from(selectedProductsDiv.querySelectorAll('.product-row')).map(row => {
            return {
                id: row.dataset.id,
                quantity: row.querySelector('.quantity').value,
                price: row.querySelector('.price').value
            };
        });

        const fixedSubtotal = parseFloat(fixedSubtotalInput.value) || null;
        const expireDays = parseInt(document.getElementById('expire-days').value) || 1;

        if (selectedProducts.length === 0) {
            alert('No products selected!');
            return;
        }

        // Make AJAX call to store transient and generate link
        jQuery.post(cl_ajaxurl, {
            action: 'generate_cartlink',
            _wpnonce: cl_data_nonce, // Add nonce
            products: selectedProducts,
            fixed_subtotal: fixedSubtotal,
            expire_days: expireDays
        }, function(response) {
            if (response.success) {
                jQuery(".autogenresponse").html(
                    '<p style="padding:20px;background:#38b6ff;color:white;border-radius:4px">' +
                    'Link: <a style="color:white " href="' + response.data.link + '" target="_blank">' + response.data.link + '</a>'+
                    '<a style="color:white;margin-left:30px" href="#" onclick="navigator.clipboard.writeText(\'' + response.data.link+ '\');jQuery(this).html(\'Copied\');">Copy</a>'+
                    '</p>'
                );
            } else {
                jQuery(".autogenresponse").html(
                    '<p style="padding:20px;background:red;color:white;">' +
                    'Error: ' + response.data.message + '</p>'
                );
            }
        });

    });

});