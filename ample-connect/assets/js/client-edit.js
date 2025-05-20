jQuery(document).ready(function($) {
    // Handle edit client link click
    $('.edit-client-link').on('click', function(e) {
        e.preventDefault();

        var clientId = $(this).data('client-id');
        var token = $(this).data('token');
        var registrationId = $(this).data('registration_id');

        // Fetch client registration details
        $.ajax({
            url:  ampleConnectConfig.ajax_url,
            method: 'POST',
            data: {
                action: 'get_registration_data',
                security: ampleConnectConfig.nonce,
                client_id: clientId,
                reg_id: registrationId
            },
            success: function(response) {
                response = response["data"];
                
                var content = '<h2>Client Information</h2>';
                content += '<form id="edit-client-form">';
                content += '<input type="hidden" name="client_id" value="' + clientId + '">';
                content += '<input type="hidden" name="registration_id" value="' + registrationId + '">';

                content += '<div class="form-group"><label>First Name</label>';
                content += '<input type="text" class="form-control" name="first_name" value="' + response.first_name + '"></div>';

                content += '<div class="form-group"><label>Last Name</label>';
                content += '<input type="text" class="form-control" name="last_name" value="' + response.last_name + '"></div>';

                content += '<div class="form-group"><label>Date of Birth</label>';
                content += '<input type="date" class="form-control" name="date_of_birth" value="' + response.date_of_birth + '"></div>';

                content += '<div class="form-group"><label>Gender</label>';
                content += '<select name="gender" class="form-control" style="max-width: 100%;">';
                content += '<option value="Undisclosed"' + (response.gender === 'Undisclosed' ? ' selected' : '') + '>Undisclosed</option>';
                content += '<option value="Male"' + (response.gender === 'Male' ? ' selected' : '') + '>Male</option>';
                content += '<option value="Female"' + (response.gender === 'Female' ? ' selected' : '') + '>Female</option>';
                content += '<option value="Other"' + (response.gender === 'Other' ? ' selected' : '') + '>Other</option>';
                content += '<option value="X"' + (response.gender === 'X' ? ' selected' : '') + '>X</option>';
                content += '</select></div>';
                
                
                content += '<button type="submit" class="button button-primary">Save Changes</button>';
                content += '</form>';

                $('#clientEditModal .client-edit-modal-form').html(content);
                $('#clientEditModal').fadeIn();
            },
            error: function() {
                alert('Failed to fetch client details.');
            }
        });
    });

    // Handle modal close
    $(document).on('click', '.ample-modal-close', function() {
        $('#clientEditModal').fadeOut();
    });

    $(window).on('click', function(event) {
        if ($(event.target).is('#clientEditModal')) {
            $('#clientEditModal').fadeOut();
        }
    });

    // Handle form submission
    $(document).on('submit', '#edit-client-form', function(e) {
        e.preventDefault();

        var formData = $(this).serialize();

        $.ajax({
            url: ampleConnectConfig.ajax_url,
            method: 'POST',
            data: {
                action: 'update_registration_data',
                security: ampleConnectConfig.nonce,
                reg_id: $('input[name="registration_id"]').val(),
                client_id: $('input[name="client_id"]').val(),
                form_data: formData
            },
            success: function(response) {
                alert('Client details updated successfully.');
                $('#clientEditModal').fadeOut();
                location.reload();
            },
            error: function() {
                alert('Failed to update client details.');
            }
        });
    });
});
