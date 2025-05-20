# Ample Connect

Ample Connect is a WordPress plugin that integrates WooCommerce with the Ample medical portal system. It provides a seamless connection between your WooCommerce store and Ample's API, allowing for synchronization of products, customers, and orders.

## Features

### Product Synchronization
- Automatically sync products from Ample to WooCommerce
- Update product information when changes occur in Ample
- Handle product variations and SKUs

### Customer Management
- Sync customer information between WooCommerce and Ample
- Manage customer registrations and approvals
- Display customer prescription details

### Order Processing
- Process orders through Ample's system
- Sync order status between WooCommerce and Ample
- Handle shipping and tracking information
- Support for custom shipping methods

### Payment Processing
- Secure payment processing using tokens
- Integration with Ample's payment system
- Support for saved payment methods

### Webhooks
- Receive real-time updates from Ample via webhooks
- Handle product, customer, and order updates
- Process shipping and tracking information updates

## Requirements

- WordPress 5.0 or higher
- WooCommerce 4.0 or higher
- PHP 7.2 or higher
- Valid Ample API credentials

## Installation

1. Upload the `ample-connect` folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Go to the Ample Connect settings page and enter your API credentials
4. Configure the plugin settings according to your needs

## Configuration

### API Credentials
- Ample Admin Username: Your Ample admin username
- Ample Admin Password: Your Ample admin password
- Webhook Secret: A secret key used to verify webhook requests

### WooCommerce Integration
- Consumer Key: WooCommerce API consumer key
- Consumer Secret: WooCommerce API consumer secret

### Product Sync
- Enable/disable product synchronization
- Set product sync interval

### Client Profile Updates
- Enable/disable client profile updates to Ample

## Usage

### Admin Interface
The plugin adds an "Ample Connect" menu to the WordPress admin dashboard with the following options:

- **Dashboard**: Overview of the plugin
- **Clients**: View and manage client information
- **Product Sync**: Manually trigger product synchronization
- **Settings**: Configure plugin settings

### Order Management
- Orders placed through WooCommerce are synchronized with Ample
- Shipping and tracking information is displayed on order details pages
- Admins can update tracking information from the order edit screen

### Customer Experience
- Customers can view their prescription details
- Product restrictions based on customer prescriptions
- Tracking information displayed on order details page

## Webhooks

The plugin registers the following webhook endpoints:

- `/webhooks/v1/products`: Handle product updates from Ample
- `/webhooks/v1/clients`: Handle client updates from Ample
- `/webhooks/v1/orders`: Handle order updates from Ample

## Support

For support, please contact the plugin author at [support@groweriq.ca](mailto:support@groweriq.ca).

## License

This plugin is licensed under the GPL v2 or later.
