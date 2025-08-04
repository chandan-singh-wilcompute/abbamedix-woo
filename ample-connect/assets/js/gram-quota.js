(function () {
    const rxDed = document.getElementById('rx-dedu-info');

    function updateBar(usedGrams, totalGrams) {
        const remainingGrams = Math.max(0, totalGrams - usedGrams);
        
        rxDed.innerText = `${remainingGrams} gr remaining`;

        const pathSegments = window.location.pathname.split('/').filter(Boolean);
        const lastSegment = pathSegments[pathSegments.length - 1];

        if (lastSegment === 'cart' || lastSegment === 'checkout') {
            var percentage = (usedGrams / totalGrams) * 100;
            // Update the skill bar
            var skillPerElement = document.querySelector('.skill-per');
            skillPerElement.style.width = percentage + '%';
            skillPerElement.setAttribute('data-per', remainingGrams);
            skillPerElement.setAttribute('data-max', totalGrams);
            document.querySelector(".gr.max").innerText = totalGrams + " gr";
        }
        
    }


    function fetchQuota() {
        fetch(GramQuotaAjax.ajax_url + '?action=get_gram_quota_data', {
        method: 'GET',
        credentials: 'same-origin'
        })
        .then(res => res.json())
        .then(data => {
            // console.log("data: ", data);
            if (data && typeof data.used === 'number' && typeof data.total === 'number') {
            updateBar(data.used, data.total);
            }
        })
        .catch(err => {
            console.error('Error fetching quota:', err);
        });
    }

    window.addEventListener('resize', () => {
        fetchQuota(); // Optional: re-fetch on resize
    });

    fetchQuota();

})();

document.querySelectorAll('.product-remove').forEach(function(quantityWrapper) {
    const removeButton = quantityWrapper.querySelector('.remove1');

    removeButton.addEventListener('click', function() {
        
        setTimeout(() => {
            location.reload();
        }, 7500);
    });
});


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