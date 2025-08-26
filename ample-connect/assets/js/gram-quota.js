(function () {

    function updateBar(policyGrams, presGrams) {

        console.log("I am called");
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

    // window.addEventListener('resize', () => {
    //     fetchQuota(); // Optional: re-fetch on resize
    // });

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

// document.querySelectorAll('.product-remove').forEach(function(quantityWrapper) {
//     const removeButton = quantityWrapper.querySelector('.remove1');

//     removeButton.addEventListener('click', function() {
        
//         setTimeout(() => {
//             location.reload();
//         }, 7500);
//     });
// });


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


// jQuery(document).ready(function($) {
//     $('.shop-variation-swatches').each(function() {
//         var container = $(this);
//         var variationsData = container.data('variations');

//         container.on('click', '.swatch-item', function() {
//             var attribute = $(this).data('attribute');
//             var value = $(this).data('value');

//             // Mark selected swatch
//             $(this).siblings().removeClass('selected');
//             $(this).addClass('selected');

//             // Find matching variation
//             var matched = variationsData.find(function(v) {
//                 return v.attributes[attribute] === value;
//             });

//             if (matched) {
//                 if (!matched.is_in_stock) {
//                     container.find('.single_add_to_cart_button')
//                         .replaceWith('<button type="button" class="button notify-me-button">NOTIFY ME</button>');
//                 } else {
//                     container.find('.notify-me-button')
//                         .replaceWith('<button type="submit" class="single_add_to_cart_button button alt">ADD TO CART</button>');
//                 }
//             }
//         });
//     });
// });



// jQuery(document.body).on('update_checkout', function(e){
//     console.log('update_checkout triggered', e);
// });