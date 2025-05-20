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

    // // Function to log in and get the admin token
    // function getAdminToken(callback) {
    //     $.ajax({
    //         type: 'GET',
    //         url: custom_order.ajax_url, // Use WordPress AJAX URL
    //         data: {
    //             action: 'get_admin_token'
    //         },
    //         success: function(response) {
    //             var data = JSON.parse(response);
    //             if (data && data.token) {
    //                 adminToken = data.token;
    //                 if (callback) callback();
    //             } else {
    //                 disablePlaceOrderButton();
    //             }
    //         },
    //         error: function() {
    //             disablePlaceOrderButton();
    //         }
    //     });
    // }

    // Function to check user status
    // function checkUserStatus() {
    //     if (!adminToken) {
    //         getAdminToken(checkUserStatus);
    //         return;
    //     }
    //     $.ajax({
    //         type: 'GET',
    //         url: `https://medbox.sandbox.onample.com/api/v2/clients/${client_id}`,
    //         data: {
    //             token: adminToken
    //         },
    //         success: function(clientResponse) {
    //             if (clientResponse && clientResponse.registration) {
    //                 var status = clientResponse.registration.status;
    //                 if (status == 'Approved') {
    //                     enablePlaceOrderButton();
    //                 } else {
    //                     disablePlaceOrderButton();
    //                 }
    //             } else {
    //                 disablePlaceOrderButton();
    //             }
    //         },
    //         error: function(clientError) {
    //             disablePlaceOrderButton();
    //         }
    //     });
    // }

    // // Call the checkUserStatus function on page load
    // if(client_id){
    //     checkUserStatus();
    // }
    

    // Re-apply the status check after any AJAX request completes
    // $(document).ajaxComplete(function() {
    //     if(client_id){
    //         checkUserStatus();
    //     }
    // });

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
            },
            error: function (error) {
                console.log("Error:", error);
            }
        });
    });

    $(document).ready(function($) {
        $('form.variations_form').on('found_variation', function(event, variation) {
            let $container = $('#cannabinoid-info');
            $container.empty(); // Clear previous
    
            if (variation.cannabinoids) {
                $.each(variation.cannabinoids, function(key, value) {
                    $container.append('<p><strong>' + key + ':</strong> ' + value + '</p>');
                });
            }
        });
    });
    
});

