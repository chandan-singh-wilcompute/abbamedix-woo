// jQuery(document).ready(function($) {
//     $('form[name="New Form"]').on('submit', function(e) {
//         e.preventDefault();

//         var formData = $(this).serialize();
//         var formObject = serializeToObject(formData);
//         $.ajax({
//             type: 'POST',
//             url: custom_registration.ajax_url,
//             data: formData + '&action=custom_registration_action',
//             success: function(response) {
//                 if(response.success) {
//                     $.ajax({
//                         type: 'GET',
//                         url: custom_registration.ajax_url,
//                         data: {
//                             action: 'get_admin_token'
//                         },
//                         success: function(loginResponse) {
//                             var data = JSON.parse(loginResponse);
//                             var token = data.token;
//                             $.ajax({
//                                 type: 'POST',
//                                 url: 'https://medbox.sandbox.onample.com/api/v2/clients',
//                                 data: {
//                                     "language_id": "EN",
//                                     "registration_attributes[first_name]": formObject['form_fields[name]'],
//                                     "registration_attributes[middle_name]": formObject['form_fields[email]'],
//                                     "registration_attributes[last_name]": formObject['form_fields[message]'],
//                                     "registration_attributes[date_of_birth]": formObject['form_fields[field_871115a]']+'-'+formObject['form_fields[field_434268a]']+'-'+formObject['form_fields[field_701927b]'],
//                                     "registration_attributes[email]": formObject['form_fields[field_728ed5d1]'],
//                                     "password": formObject['form_fields[field_f4cc2f5]'],
//                                     "password_confirmation": formObject['form_fields[field_f4cc2f5]'],

//                                     token: token
//                                 },
//                                 success: function(registrationResponse) {
//                                     var client_id = registrationResponse.id;
//                                     var user_id = response.data.user_id;
//                                     var active_registration_id = registrationResponse.active_registration_id;
//                                     var client_login_id = registrationResponse.client_id;
//                                     console.log('client_login_id '+client_login_id);
//                                     $.ajax({
//                                         type: 'PUT',
//                                         url: `https://medbox.sandbox.onample.com/api/v2/clients/${client_id}/registrations/${active_registration_id}`,
//                                         data: {
//                                             "gender": formObject['form_fields[field_2a0ac5c]'],
//                                             "telephone_1": formObject['form_fields[field_583befa]'],
//                                             "street_1": formObject['form_fields[field_84f70c1]'],
//                                             "street_2": formObject['form_fields[field_e48311f]'],
//                                             "city": formObject['form_fields[field_cea79fa]'],
//                                             "province": formObject['form_fields[field_46e27da]'],
//                                             "postal_code": formObject['form_fields[field_81d3861]'],
//                                             "mailing_street_1": formObject['form_fields[field_5dcb0ca]'],
//                                             "mailing_street_2": formObject['form_fields[field_3b67ce9]'],
//                                             "mailing_city": formObject['form_fields[field_b03cb1a]'],
//                                             "mailing_province": formObject['form_fields[field_cbac568]'],
//                                             "mailing_postal_code": formObject['form_fields[field_c97e896]'],
//                                             token: token
//                                         },
//                                         success: function(clientUpdateResponse) {
//                                             console.log('Client update success');
//                                         },
//                                         error: function(clientUpdateError) {
//                                             console.log('Client update failed: ' + clientUpdateError.responseText);
//                                         }
//                                     });
                                    
//                                     // Update WordPress user meta with client_id
//                                     $.ajax({
//                                         type: 'POST',
//                                         url: custom_registration.ajax_url,
//                                         data: {
//                                             action: 'update_user_client_id_registration_id',
//                                             user_id: response.data.user_id,
//                                             client_id: client_id,
//                                             active_registration_id: active_registration_id,
//                                             client_login_id: client_login_id
//                                         },
//                                         success: function(updateResponse) {
//                                             // alert('Registration successful!');
//                                             window.location.href = response.data.redirect_url;
//                                         },
//                                         error: function(updateError) {
//                                             console.log('User meta update failed: ' + updateError.responseText);
//                                         }
//                                     });
                                    
//                                 },
//                                 error: function(registrationError) {
//                                     console.log('Registration failed: ' + registrationError.responseText);
//                                 }
//                             });
//                         },
//                         error: function(loginError) {
//                             console.log('Login failed: ' + loginError.responseText);
//                         }
//                     });
//                 }
//             }
//         });
//     });

//     function serializeToObject(serializedData) {
//         var formArray = serializedData.split('&');
//         var formObject = {};

//         formArray.forEach(function(item) {
//             var pair = item.split('=');
//             var key = decodeURIComponent(pair[0]);
//             var value = decodeURIComponent(pair[1]);
//             formObject[key] = value;
//         });

//         return formObject;
//     }
// });


jQuery(document.body).on('updated_cart_totals', function () {
    location.reload();
});