jQuery(document).ready(function($) {
    $('.prescription-details-link').on('click', function(e) {
        e.preventDefault();
        var clientId = $(this).data('client-id');
        // var token = $(this).data('token');
        var client_name = $(this).data('client_name');
        
        // console.log(clientId);
        $.ajax({
            url: ampleConnectConfig.ajax_url,
            type: 'POST',
            data: {
                action: 'get_prescription_data',
                security: ampleConnectConfig.nonce,
                client_id: clientId
            },
            success: function(response) {
                var content = '<h2>Prescription Details</h2>';
                content += '<h3>'+client_name+'</h3>';
                let prescriptions = response["data"];
                if (prescriptions.length > 0) {
                    prescriptions.forEach(function(prescription) {
                        content += '<div class="prescription-item">';
                        // content += '<p><strong>Diagnosis:</strong> ' + (prescription.diagnosis || 'N/A') + '</p>';
                        content += '<p><strong>PRESCRIPTION ALLOWANCE :</strong> ' + prescription.number_of_grams + ' g/day</p>';
                        content += '<p><strong>PRESCRIPTION ENDS :</strong> ' + prescription.script_end + '</p>';
                        // content += '<p><strong>Physician:</strong> ' + prescription.initial_physician_first_name + ' ' + prescription.initial_physician_last_name + '</p>';
                        content += '</div>';
                    });
                } else {
                    content += '<p>No prescriptions found.</p>';
                }
                $('#ampleModal .ample-modal-content .ample-modal-details').html(content);
                $('#ampleModal').fadeIn();
            },
            error: function() {
                alert('Failed to fetch prescription details.');
            }
        });
    });

    // Close the modal
    $('.ample-modal-close').on('click', function() {
        $('#ampleModal').fadeOut();
    });

    // Close the modal when clicking outside of the modal content
    $(window).on('click', function(event) {
        if ($(event.target).is('#ampleModal')) {
            $('#ampleModal').fadeOut();
        }
    });
});
