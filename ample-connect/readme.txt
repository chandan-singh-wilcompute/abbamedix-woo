=== Ample Connect ===
Contributors: Chandan Singh
Donate link: https://www.groweriq.ca/
Tags: sync, medical portal, woocommerce, jwt, api
Requires at least: 5.0
Tested up to: 5.9
Requires PHP: 7.2
Stable tag: 1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Ample Connect is a plugin that syncs with the Ample medical portal data and updates user information via a custom API endpoint.

== Description ==

Ample Connect is a WordPress plugin that integrates your WooCommerce store with the Ample medical portal. It allows for seamless synchronization of user data between your WooCommerce store and the Ample portal, ensuring that user profiles and billing addresses are always up-to-date. The plugin also updates user information via a custom API endpoint and requires the "JWT Authentication for WP-API" plugin for secure communication.

= Features =

* Syncs user profile data with the Ample medical portal.
* Updates user billing address information in the Ample medical portal.
* Ensures only approved users can place orders.
* Custom API endpoint for updating user information.
* Requires "JWT Authentication for WP-API" plugin for secure communication.

== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/ample-connect` directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the 'Plugins' screen in WordPress.
3. Install and activate the "JWT Authentication for WP-API" plugin.
4. Use the 'Settings' -> 'Ample Connect' screen to configure the plugin.

== Frequently Asked Questions ==

= How do I configure the plugin? =

Navigate to 'Settings' -> 'Ample Connect' in the WordPress admin dashboard to configure the plugin settings, including API credentials and sync options.

= What happens if the sync fails? =

If the sync fails, the plugin will log an error message that can be viewed in the WordPress debug log. Ensure that your API credentials are correct and that the Ample API is accessible.

= Why do I need the "JWT Authentication for WP-API" plugin? =

The "JWT Authentication for WP-API" plugin provides secure token-based authentication for API requests, ensuring that data exchange between your site and the Ample medical portal is secure.

== Changelog ==

= 1.1 =
* Added custom API endpoint for updating user information.
* Added requirement for "JWT Authentication for WP-API" plugin.

= 1.0 =
* Initial release.

== Upgrade Notice ==

= 1.1 =
Added custom API endpoint and JWT Authentication requirement.

= 1.0 =
Initial release.

== Screenshots ==

1. Settings page for configuring the Ample Connect plugin.
2. Example of synchronized user profile data.
3. Example of synchronized billing address data.

== License ==

This plugin is free software; you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation; either version 2 of the License, or any later version.

This plugin is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.

You should have received a copy of the GNU General Public License along with this plugin; if not, see https://www.gnu.org/licenses/gpl-2.0.html.

*Test Deploy Script2*
