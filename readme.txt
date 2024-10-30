=== MJ Freeway Orders ===
Contributors: mjfreeway, skylarq
Requires at least: 4.7
Tested up to: 4.9.2
Requires PHP: 5.3
Stable Tag: 1.0.2

This plugin creates a shopping cart experience for MJ Freeway customers using MJ Freeway's API.

== Description ==
This plugin creates a shopping cart experience for MJ Freeway customers using MJ Freeway's API. You must be an existing MJ Freeway customer to use this plugin.

== Installation ==
1. Upload the plugin files to the `/wp-content/plugins/mjfreeway` directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the Plugins screen in WordPress.
3. Use the Settings->MJ Freeway screen to configure the plugin.
4. Once activated, website users must be logged in and have an MJ Freeway customer ID associated with their user account in order to add items to the cart and reserve a product.

== Frequently Asked Questions ==
= How do I associate a Wordpress user with an MJ Freeway customer/patient account? =

Add the MJ Freeway Customer ID in the Customer ID field under MJ Freeway within the user's profile. Note: only website administrators have access to update this field.

= Where can I put additional instructions for successful orders? =

Under Settings->MJ Freeway, place additional text in the `Confirmation Message` textarea. This text appears on the confirmation screen after a successful order is placed.

= Where can I put additional instructions for successful orders? =

Under Settings->MJ Freeway, place additional text in the `Unverified User Message` textarea. This text appears on the product detail page when a user is signed in but the user profile does not have an MJ Freeway Customer ID.

= Where can I put a link to directions to my business? =

Under Settings->MJ Freeway, insert a URL in the `Directions Link` input box. This will cause the link to appear on the confirmation screen after a successful order is placed.

= How do I remove the registration link for anonymous users? =

Under Settings->MJ Freeway, uncheck the box labeled `Display Registration Link on Product Detail page`.

== Screenshots ==
1. Admin menu
2. User profile input for customer ID

== Changelog ==
= 1.02 =
* Added in-stock filter
= 1.01 =
* Unverified user message content managed
* Improved admin labels
= 1.0 =
* Initial rollout
