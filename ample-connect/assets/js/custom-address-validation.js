jQuery(document).ready(function($) {
    function validateForm(fields) {
        var isValid = true;
        fields.each(function() {
            if ($(this).val() === '') {
                isValid = false;
            }
        });

        $('button[name="save_address"]').prop('disabled', !isValid);
    }

    // Determine if we are on the billing or shipping page
    // var referer = $('input[name="_wp_http_referer"]').val();
    var refererInput = $('input[name="_wp_http_referer"]');
    var referer = refererInput.length ? refererInput.val() : '';

    var isBillingPage = referer.includes('edit-address/billing');
    var isShippingPage = referer.includes('edit-address/shipping');

    if (isBillingPage) {
        var billingFields = $('#billing_email, #billing_first_name, #billing_last_name, #billing_phone, #billing_country, #billing_address_1, #billing_city, #billing_state, #billing_postcode');
        // Initially disable the save button
        $('button[name="save_address"]').prop('disabled', true);

        // Initial validation check on page load
        validateForm(billingFields);

        // Attach the validation to input change events
        billingFields.on('input', function() {
            validateForm(billingFields);
        });
    }

    if (isShippingPage) {
        var shippingFields = $('#shipping_first_name, #shipping_last_name, #shipping_phone, #shipping_country, #shipping_address_1, #shipping_city, #shipping_state, #shipping_postcode');
        // Initially disable the save button
        $('button[name="save_address"]').prop('disabled', true);

        // Initial validation check on page load
        validateForm(shippingFields);
        
        // Attach the validation to input change events
        shippingFields.on('input', function() {
            validateForm(shippingFields);
        });
    }
});
