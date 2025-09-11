/**
 * Ample Connect Checkout Auto-fill
 * Automatically fills checkout fields with customer address data on first visit
 */

(function($) {
    'use strict';

    // Initialize when DOM is ready
    $(document).ready(function() {
        initCheckoutAutofill();
    });

    function initCheckoutAutofill() {
        // Check if we have address data
        if (typeof AmpleCheckoutData === 'undefined' || !AmpleCheckoutData.address) {
            if (AmpleCheckoutData && AmpleCheckoutData.debug) {
                console.log('Ample Checkout: No address data available');
            }
            return;
        }

        // Check if this is the first visit to checkout (no previously saved data)
        if (isCheckoutFieldsAlreadyFilled()) {
            if (AmpleCheckoutData.debug) {
                console.log('Ample Checkout: Fields already filled, skipping auto-fill');
            }
            return;
        }

        // Wait for WooCommerce checkout to be initialized
        setTimeout(function() {
            fillCheckoutFields();
        }, 500);

        // Also handle dynamic field updates (for ajax checkout updates)
        $(document.body).on('updated_checkout', function() {
            if (!isCheckoutFieldsAlreadyFilled()) {
                setTimeout(function() {
                    fillCheckoutFields();
                }, 100);
            }
        });
    }

    function isCheckoutFieldsAlreadyFilled() {
        // Check if key shipping fields already have values
        var keyFields = ['#shipping_address_1', '#shipping_city', '#shipping_postcode'];
        var filledFields = 0;
        
        keyFields.forEach(function(field) {
            if ($(field).val() && $(field).val().trim() !== '') {
                filledFields++;
            }
        });

        // If 2 or more key fields are filled, consider form already populated
        return filledFields >= 2;
    }

    function fillCheckoutFields() {
        var address = AmpleCheckoutData.address;
        var fieldsPopulated = 0;

        if (AmpleCheckoutData.debug) {
            console.log('Ample Checkout: Auto-filling fields with data:', address);
        }

        // Fill each field if it exists and is empty
        Object.keys(address).forEach(function(fieldName) {
            var fieldSelector = '#' + fieldName;
            var $field = $(fieldSelector);
            
            if ($field.length && address[fieldName]) {
                var currentValue = $field.val();
                
                // Only fill if field is empty or has placeholder text
                if (!currentValue || currentValue.trim() === '' || currentValue === $field.attr('placeholder')) {
                    $field.val(address[fieldName]).trigger('change');
                    fieldsPopulated++;
                    
                    if (AmpleCheckoutData.debug) {
                        console.log('Filled field:', fieldName, 'with:', address[fieldName]);
                    }
                }
            }
        });

        // Handle special cases for select fields (country, state)
        fillSelectField('#shipping_country', address.shipping_country);
        fillSelectField('#billing_country', address.billing_country);
        fillSelectField('#shipping_state', address.shipping_state);
        fillSelectField('#billing_state', address.billing_state);

        // Copy billing to shipping if "Ship to different address" is not checked
        if (!$('#ship-to-different-address-checkbox').is(':checked')) {
            copyBillingToShipping();
        }

        // Trigger checkout update to recalculate shipping if fields were populated
        if (fieldsPopulated > 0) {
            setTimeout(function() {
                $('body').trigger('update_checkout');
                
                if (AmpleCheckoutData.debug) {
                    console.log('Ample Checkout: Auto-filled', fieldsPopulated, 'fields and triggered checkout update');
                }
            }, 200);
        }
    }

    function fillSelectField(selector, value) {
        if (!value) return;
        
        var $field = $(selector);
        if ($field.length && $field.is('select')) {
            // Check if the option exists in the select
            if ($field.find('option[value="' + value + '"]').length) {
                if (!$field.val() || $field.val() === '') {
                    $field.val(value).trigger('change');
                    
                    if (AmpleCheckoutData.debug) {
                        console.log('Filled select field:', selector, 'with:', value);
                    }
                }
            }
        }
    }

    function copyBillingToShipping() {
        var billingFields = [
            'first_name', 'last_name', 'company', 'address_1', 'address_2', 
            'city', 'postcode', 'country', 'state', 'phone'
        ];

        billingFields.forEach(function(field) {
            var billingValue = $('#billing_' + field).val();
            var $shippingField = $('#shipping_' + field);
            
            if (billingValue && $shippingField.length && (!$shippingField.val() || $shippingField.val() === '')) {
                $shippingField.val(billingValue).trigger('change');
            }
        });
    }

    // Handle "Ship to different address" checkbox
    $(document).on('change', '#ship-to-different-address-checkbox', function() {
        if (!$(this).is(':checked')) {
            // Copy billing to shipping when unchecking
            setTimeout(copyBillingToShipping, 100);
        }
    });

    // Add visual feedback for auto-filled fields (optional)
    function addVisualFeedback() {
        if (!AmpleCheckoutData.debug) return;
        
        $('.woocommerce-checkout input, .woocommerce-checkout select').each(function() {
            if ($(this).val() && $(this).val() !== '') {
                $(this).css('border-left', '3px solid #28a745');
            }
        });
        
        // Remove visual feedback after 3 seconds
        setTimeout(function() {
            $('.woocommerce-checkout input, .woocommerce-checkout select').css('border-left', '');
        }, 3000);
    }

})(jQuery);