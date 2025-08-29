jQuery(document).ready(function($) {
    // var client_id = custom_order.client_id; 
    // console.log(client_id);
    //var adminToken = null;

    var status = custom_order.status;
    // Create and insert the message element
    var $statusMessage = $('<div id="status-message" style="color: red; display: none;padding-bottom: 12px;font-weight: 600;">'+custom_address_validation.status_message+'</div>');
    $('#place_order').before($statusMessage);

    $('#place_order').prop('disabled', true);
    function disablePlaceOrderButton() {
        $('#place_order').before($statusMessage);
        $('#status-message').show();
        $('#place_order').prop('disabled', true);
    }

    function enablePlaceOrderButton() {
        $('#place_order').prop('disabled', false);
        $('#status-message').hide();
    }

    function controlPlaceOrderButton() {
        
        if (status == 'Approved') {
            enablePlaceOrderButton();
        } else {
            disablePlaceOrderButton();
        }
                
    }

    controlPlaceOrderButton();


    $(document).on('change', 'input[name="shipping_method[0]"]', function () {
        let shipping_method = $(this).val();
        console.log("Shipping Method: ", shipping_method);

        $.ajax({
            type: "POST",
            url: custom_order.ajax_url,
            data: {
                action: "shipping_method_selected",
                shipping_method: shipping_method
            },
            success: function (response) {
                console.log("API Response:", response);
                $('body').trigger('update_checkout');
            },
            error: function (error) {
                console.log("Error:", error);
            }
        });
    });

    // $(document).ready(function($) {
    //     $('form.variations_form').on('found_variation', function(event, variation) {
    //         console.log("I got called from custom place order");
    //         let $container = $('#cannabinoid-info');
    //         $container.empty(); // Clear previous
    
    //         if (variation.cannabinoids) {
    //             $.each(variation.cannabinoids, function(key, value) {
    //                 $container.append('<p><strong>' + key + ':</strong> ' + value + '</p>');
    //             });
    //         }
    //     });
    // });
    
});

