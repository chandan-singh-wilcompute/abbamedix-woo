<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

require_once plugin_dir_path(__FILE__) . '../../vendor/autoload.php';
// require_once plugin_dir_path(__FILE__) . '../utility.php';
use Automattic\WooCommerce\Client;
use Automattic\WooCommerce\HttpClient\HttpClientException;


class WC_Customers {

    private $woocommerce;

    public function __construct() {

        global $ample_connect_settings;
        
        if (isset($ample_connect_settings['consumer_key']) && isset($ample_connect_settings['consumer_secret'])) {
            $consumer_key = $ample_connect_settings['consumer_key'];
            $consumer_secret = $ample_connect_settings['consumer_secret'];
            // Initialize WooCommerce Client with dynamic keys
            $this->woocommerce = new Client(
                site_url(),
                $consumer_key,
                $consumer_secret,
                [
                    'wp_api' => true,
                    'version' => 'wc/v3',
                    'timeout' => 30,
                    'verify_ssl' => false,
                ]
            );
        } else {
            echo 'Error: $ample_connect_settings not properly initialized.';
            $this->woocommerce = null;
        }
    }

    public function get_customers() {
        try {
            // Retrieve customers
            $customers = $this->woocommerce->get('customers', [
                'per_page' => 50, // Number of customers per page (default: 10, max: 100)
                'page' => 1      // Page number
            ]);
        
            return $customers;
        } catch (Exception $e) {
            // Handle errors
            return json_encode(array("message" => "Some error was there"));
        }
    }

    public function get_customer($data) {
        // Fetches a customer based on filter mentioned in $data

        $response = $this->woocommerce->get('customers', $data);
        $customer_id = $response[0]->id;
        if (empty($response)) {
            return false;
        }
        
        return $response[0];
    }

    public function update_customer($customerData) {

        $data = [
            'meta_key' => 'client_login_id',
            'meta_value' => $customerData['client_id']
        ];
        $customer = $this->get_customer($data);

        if (!$customer) {
            return false;
        }

        $customer_id = $customer->id;

        try {
            // Data to update
            list($year, $month, $day) = explode('-', $customerData['registration']['date_of_birth']);

            $data = [
                'first_name' => $customerData['registration']['first_name'],
                'last_name' => $customerData['registration']['first_name'],
                'email' => $customerData['registration']['email'],
                'billing' => [
                    'address_1' => $customerData['shipping_address']['street1'],
                    'address_2' => $customerData['shipping_address']['street2'],
                    'city' => $customerData['shipping_address']['city'],
                    'state' => $customerData['shipping_address']['state'],
                    'postcode' => $customerData['shipping_address']['zip'],
                    'country' => $customerData['shipping_address']['country'],
                    'phone' => $customerData['shipping_address']['phone']
                ],
                'shipping' => [
                    'address_1' => $customerData['shipping_address']['street1'],
                    'address_2' => $customerData['shipping_address']['street2'],
                    'city' => $customerData['shipping_address']['city'],
                    'state' => $customerData['shipping_address']['state'],
                    'postcode' => $customerData['shipping_address']['zip'],
                    'country' => $customerData['shipping_address']['country'],
                    'phone' => $customerData['shipping_address']['phone']
                ],
                'meta_data' => [
                    [
                        'key' => 'billing_first_name',
                        'value' => $customerData['registration']['first_name']
                    ],
                    [
                        'key' => 'billing_middle_name',
                        'value' => $customerData['registration']['middle_name']
                    ],
                    [
                        'key' => 'billing_last_name',
                        'value' => $customerData['registration']['last_name']
                    ],
                    [
                        'key' => 'first_name',
                        'value' => $customerData['registration']['first_name']
                    ],
                    [
                        'key' => 'middle_name',
                        'value' => $customerData['registration']['middle_name']
                    ],
                    [
                        'key' => 'last_name',
                        'value' => $customerData['registration']['last_name']
                    ],
                    [
                        'key' => 'dob_year',
                        'value' => $year
                    ],
                    [
                        'key' => 'dob_month',
                        'value' => $month
                    ],
                    [
                        'key' => 'dob_day',
                        'value' => $day
                    ],
                    [
                        'key' => 'gender',
                        'value' => $customerData['registration']['gender']
                    ],
                    [
                        'key' => 'mailing_street',
                        'value' => $customerData['registration']['mailing_street_1'] . $customerData['registration']['mailing_street_2']
                    ],
                    [
                        'key' => 'mailing_city',
                        'value' => $customerData['registration']['mailing_city']
                    ],
                    [
                        'key' => 'mailing_province',
                        'value' => $customerData['registration']['mailing_province']
                    ],
                    [
                        'key' => 'mailing_postal_code',
                        'value' => $customerData['registration']['mailing_postal_code']
                    ],
                    [
                        'key' => 'status',
                        'value' => $customerData['registration']['status']
                    ]
                ]
            ];
        
            // Update the customer
            $customer = $this->woocommerce->put("customers/$customer_id", $data);
        
            // echo "Customer updated successfully.\n";
            // echo "Customer ID: " . $customer['id'] . "\n";

            return true;

        } catch (Exception $e) {
            echo 'Error: ' . $e->getMessage();
            return false;
        }
    }

    public function create_customer($customerData) {

        try {
            // Data to update
            list($year, $month, $day) = explode('-', $customerData['registration']['date_of_birth']);
            $username = sanitize_user($customerData['registration']['first_name'] . $customerData['registration']['last_name']);
            $username = custom_registration_generate_unique_username($username);

            $data = [
                'first_name' => $customerData['registration']['first_name'],
                'last_name' => $customerData['registration']['first_name'],
                'email' => $customerData['registration']['email'],
                'username' => $username,
                'password' => "password@1234",
                'billing' => [
                    'address_1' => $customerData['shipping_address']['street1'],
                    'address_2' => $customerData['shipping_address']['street2'],
                    'city' => $customerData['shipping_address']['city'],
                    'state' => $customerData['shipping_address']['state'],
                    'postcode' => $customerData['shipping_address']['zip'],
                    'country' => $customerData['shipping_address']['country'],
                    'phone' => $customerData['shipping_address']['phone']
                ],
                'shipping' => [
                    'address_1' => $customerData['shipping_address']['street1'],
                    'address_2' => $customerData['shipping_address']['street2'],
                    'city' => $customerData['shipping_address']['city'],
                    'state' => $customerData['shipping_address']['state'],
                    'postcode' => $customerData['shipping_address']['zip'],
                    'country' => $customerData['shipping_address']['country'],
                    'phone' => $customerData['shipping_address']['phone']
                ],
                'meta_data' => [
                    [
                        'key' => 'billing_first_name',
                        'value' => $customerData['registration']['first_name']
                    ],
                    [
                        'key' => 'billing_middle_name',
                        'value' => $customerData['registration']['middle_name']
                    ],
                    [
                        'key' => 'billing_last_name',
                        'value' => $customerData['registration']['last_name']
                    ],
                    [
                        'key' => 'first_name',
                        'value' => $customerData['registration']['first_name']
                    ],
                    [
                        'key' => 'middle_name',
                        'value' => $customerData['registration']['middle_name']
                    ],
                    [
                        'key' => 'last_name',
                        'value' => $customerData['registration']['last_name']
                    ],
                    [
                        'key' => 'dob_year',
                        'value' => $year
                    ],
                    [
                        'key' => 'dob_month',
                        'value' => $month
                    ],
                    [
                        'key' => 'dob_day',
                        'value' => $day
                    ],
                    [
                        'key' => 'gender',
                        'value' => $customerData['registration']['gender']
                    ],
                    [
                        'key' => 'mailing_street',
                        'value' => $customerData['registration']['mailing_street_1'] . $customerData['registration']['mailing_street_2']
                    ],
                    [
                        'key' => 'mailing_city',
                        'value' => $customerData['registration']['mailing_city']
                    ],
                    [
                        'key' => 'mailing_province',
                        'value' => $customerData['registration']['mailing_province']
                    ],
                    [
                        'key' => 'mailing_postal_code',
                        'value' => $customerData['registration']['mailing_postal_code']
                    ],
                    [
                        'key' => 'client_login_id',
                        'value' => $customerData['client_id']
                    ],
                    [
                        'key' => 'client_id',
                        'value' => $customerData['registration']['client_id']
                    ],
                    [
                        'key' => 'active_registration_id',
                        'value' => $customerData['registration']['id']
                    ]
                ]
            ];
        
            // Update the customer
            $customer = $this->woocommerce->post("customers", $data);
        
            // echo "Customer updated successfully.\n";
            // echo "Customer ID: " . $customer['id'] . "\n";

            return true;

        } catch (Exception $e) {
            echo 'Error: ' . $e->getMessage();
            return false;
        }
    }

    public function reset_password($client_id) {

        $data = [
            'meta_key' => 'client_id',
            'meta_value' => $client_id
        ];
        $customer = $this->get_customer($data);

        if (!$customer) {
            return false;
        }

        $user_email = $customer->email;

        if (empty($user_email)) {
            return new WP_Error('missing_email', __('User email is required.', 'woocommerce'));
        }
    
        $user = get_user_by('email', $user_email);
        if (!$user) {
            return new WP_Error('invalid_email', __('No user found with this email address.', 'woocommerce'));
        }
    
        // Trigger the WordPress password reset process
        $reset_email_sent = retrieve_password($user_email);
    
        if (is_wp_error($reset_email_sent)) {
            return $reset_email_sent;
        }
    
        return __('Password reset email sent successfully.', 'woocommerce');
    }
}