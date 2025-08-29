(function () {

    function updateBar(policyGrams, presGrams) {
        const rxDed = document.getElementById('rx-dedu-info');
        // rxDed.innerText = `${remainingGrams} gr remaining`;
        // console.log("totalGrams: ", totalGrams);
        if (rxDed) {
            rxDed.innerText = `${presGrams} gr remaining`;
        }
        
        
        
        const pathSegments = window.location.pathname.split('/').filter(Boolean);
        const lastSegment = pathSegments[pathSegments.length - 1];

        if (lastSegment === 'cart') {
            const policyvalue = document.getElementById('policyvalue');
            const prescvalue = document.getElementById('prescvalue');

            prescvalue.innerHTML = `<div class="skillBar">
							You have <strong class="skillvalue" >${presGrams}g</strong> left in your prescription.
						</div>`;

            policyvalue.innerHTML = `<div class="skillBar">
							You have <strong class="skillvalue" >${policyGrams}g</strong> left in your policy.
						</div>`;
        }
        
    }

    function fetchQuota() {
        fetch(GramQuotaAjax.ajax_url + '?action=get_gram_quota_data', {
            method: 'GET',
            credentials: 'same-origin'
        })
        .then(res => res.json())
        .then(data => {
            console.log("data: ", data);
            if (data && typeof data.policy_grams === 'number' && typeof data.prescription_grams === 'number') {
                updateBar(data.policy_grams, data.prescription_grams);
            }
        })
        .catch(err => {
            console.error('Error fetching quota.');
        });
    }

    fetchQuota();

    // Run again whenever WooCommerce updates the cart DOM
    jQuery(document.body).on('updated_wc_div', function () {
        fetchQuota();
    });

    // setTimeout(function () {
    //     const checkbox = document.querySelector('input.apply-policy-discount[value="134"]');
    //     if (checkbox) {
    //         checkbox.checked = true;
    //         checkbox.dispatchEvent(new Event('change', { bubbles: true })); // Trigger change event
    //     }
    // }, 500);

})();

// Run only if the URL path includes "/product-filter/"
if (window.location.pathname.includes('/product-filter/')) {
    const path = window.location.pathname;

    // Find the last segment after the final slash
    const pathParts = path.split('/');
    let lastSegment = pathParts[pathParts.length - 1] || pathParts[pathParts.length - 2] || '';
    // Remove hash part if exists (after #)
    lastSegment = lastSegment.split('#')[0];
    // Split on "+" to get slugs
    const selectedSlugs = lastSegment.split('+').map(s => s.trim().toLowerCase());

    // Match checkboxes with these values
    document.querySelectorAll('#product-category-filter-form input[type="checkbox"]').forEach(input => {
        const val = input.value.trim().toLowerCase();
        if (selectedSlugs.includes(val)) {
            input.checked = true;
        }
    });
}

// Run only if the URL path includes "/product-filter/"
if (window.location.pathname.includes('/featured-filter/')) {
    const path = window.location.pathname;

    // Find the last segment after the final slash
    const pathParts = path.split('/');
    let lastSegment = pathParts[pathParts.length - 1] || pathParts[pathParts.length - 2] || '';
    // Remove hash part if exists (after #)
    lastSegment = lastSegment.split('#')[0];
    // Split on "+" to get slugs
    const selectedSlugs = lastSegment.split('+').map(s => s.trim().toLowerCase());

    // Match checkboxes with these values
    document.querySelectorAll('#featured-category-filter-form input[type="checkbox"]').forEach(input => {
        const val = input.value.trim().toLowerCase();
        if (selectedSlugs.includes(val)) {
            input.checked = true;
        }
    });
}

// Document downloading
jQuery(document).ready(function($) {

    function downloadDocument(orderId, docType, button) {
        // Save original text
        if (!button.data('original-text')) {
            button.data('original-text', button.text());
        }

        // Disable button and show loading state
        button.prop('disabled', true).text('Loading...');

        $.ajax({
            url: wc_add_to_cart_params.ajax_url,
            method: 'POST',
            data: {
                action: 'view_order_document',
                order_id: orderId,
                doc_type: docType,
                ajax_check: true // marker to force JSON response if error
            },
            xhrFields: {
                responseType: 'blob'
            },
            success: function (data, status, xhr) {
                const contentType = xhr.getResponseHeader('Content-Type');

                if (contentType && contentType.indexOf('application/json') !== -1) {
                    // JSON error
                    alert(data.message);
                } else if (contentType && contentType.indexOf('application/pdf') !== -1) {
                    const blob = new Blob([data], { type: 'application/pdf' });
                    const link = document.createElement('a');
                    link.href = URL.createObjectURL(blob);
                    if (docType == 'order-confirmation') {
                        link.download = 'order-confirm-' + orderId + '.pdf';
                    } else if (docType == 'shipped-receipt') {
                        link.download = 'order-shipping-' + orderId + '.pdf';
                    } else if (docType == 'registration_document') {
                        link.download = 'registration-document.pdf';
                    } else {
                        link.download = 'document.pdf';
                    }
                    
                    document.body.appendChild(link);
                    link.click();
                    document.body.removeChild(link);
                } else {
                    alert('Unexpected response type.');
                }
            },
            complete: function() {
                // Re-enable button and reset text
                button.prop('disabled', false).text(button.data('original-text'));
            }
        });
    }

    // Handle Order Confirmation button
    $(document).off('click', '#order-confirmation').on('click', '#order-confirmation', function() {
        let button = $(this);
        button.data('original-text', button.text());
        let orderId = button.data('order-id');
        downloadDocument(orderId, 'order-confirmation', button);
    });

    // Handle Shipped Receipt button
    $(document).off('click', '#shipped-receipt').on('click', '#shipped-receipt', function() {
        let button = $(this);
        button.data('original-text', button.text());
        let orderId = button.data('order-id');
        downloadDocument(orderId, 'shipped-receipt', button);
    });

    // Handle Registration Document button
    $(document).off('click', '#registrationDcoument').on('click', '#registrationDcoument', function() {
        let button = $(this);
        button.data('original-text', button.text());
        downloadDocument(0, 'registration_document', button);
    });

});
