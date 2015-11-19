=== Conditional Woo Checkout Field ===
Contributors: scott.deluzio
Tags: plugin, woocommerce, checkout, custom, customer, input, input field
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=N2TYAV69U9CP4
Requires at least: 3.1.0
Tested up to: 4.1
Stable tag: 1.0.10
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Display a custom field at checkout in your WooCommerce store if a certain product is in the customer's cart.

== Description ==
If you sell a product through your WooCommerce store that requires you to collect some additional information from your customers before they complete their purchase, this plugin is exactly what you need.

This plugin lets you configure an additional field to be displayed at checkout for your customer to fill out provided a certain product is in the customer's cart. The plugin allows you to customize this additional field's

* Title
* Input type (text box, text area, or select/drop down menu)
* Label
* Placeholder text
* CSS Class
* Whether or not the field is required (and the error message to display if it is required but not filled out)
* Whether or not to add the customer's input to the order email or invoice

This plugin requires your site to have WooCommerce installed in order to work. It is not compatible with other e-commerce plugins.

**Pro Version Now Available**
[Update to Pro](https://surpriseazwebservices.com/plugins/conditional-woo-checkout-field-pro/) to get even more features.

* Display an unlimited number of custom conditional fields at checkout (free version only allows one).
* Display each conditional field on an unlimited number of products (free version only allows the field to display for one product).
* Increased variety of available input fields including: Text box, multi-line text area, drop-down/select menu, country list, state list, check boxes, password, radio buttons, and date picker.
* Bonus: Easily edit any of the default WooCommerce checkout fields without having to edit any code.

Get the pro version by [clicking here](https://surpriseazwebservices.com/plugins/conditional-woo-checkout-field-pro/).

== Installation ==
1. Download archive and unzip in wp-content/plugins or install via Plugins – Add New.
2. Activate the plugin through the Plugins menu in WordPress.
3. In your WordPress dashboard, click on WooCommerce > Conditional Field.
4. Enter the information for your custom field, and click Save Changes.

== Frequently Asked Questions ==
= Where do I get the Product ID? =

Product IDs are found in your WordPress admin dashboard, by clicking Products > Products in the left menu. Hover your mouse over a product, and the ID number will be displayed directly below the product name next to the Edit links.

= How do I add options to the select menu =

In the Input Type column, choose Select from the dropdown menu. Then add the options as you want your customers to see them at checkout in the Select Options box directly below the menu. Enter one option per line. Do NOT use commas to separate your options.

= Why would I want to have an extra field at checkout? =

There are a number of reasons why you might want to have an extra field at checkout. Sometimes the default fields provided are not enough and there is extra information you need to collect from your customers.

A few examples include:

* Gift wrap options for a birthday, holiday, anniversary, etc.
* Allow customers to include a note with a gift.
* When customers need to provide a guest's name if purchasing tickets to an event.
* To indicate food preferences or allergies when food is included in the item being purchased, such as when booking a trip, or event.
* Find out how your customers heard about your store.


== Screenshots ==
1. Easily create a custom field at checkout for your customers to fill out.
2. Your custom checkout field will show up right after the other checkout fields for billing, shipping, and order notes.
3. Custom checkout field with Title, Field Label, and Text Box options. Field is set to be required as indicated by the asterisk.
4. Custom checkout field with Title, Field Label, and Text Area options. Field is set to be required as indicated by the asterisk.
5. Custom checkout field with Title, Field Label, and Select Menu options. Field is set to be required as indicated by the asterisk.
6. Custom error message displayed after the customer attempts to checkout without completing your required field.

== Changelog ==
= 1.0.10 =
* Minor Fix

= 1.0.9 =
* Bug fix: Some fields were incorrectly set to be required, when the required setting was set to "No"

= 1.0.8 =
* Minor Fix

= 1.0.7 =
* Minor Fix

= 1.0.6 =
* Bug fix: Some orders were being processed when the conditional field was set to be required, but the customer did not complete the field.

= 1.0.5 =
* Bug fix: When the conditional field was set to be required, it was requiring it on all orders regardless of whether or not the conditional product was in the cart. This update correctly checks to see if the conditional product is in the cart, and if the conditional field is required. If both of those conditions are met it will check to see if the conditional field has a value before processing the order, otherwise it will display an error message.

= 1.0.4 =
* Bug fix: Custom field not appending to order emails.

= 1.0.3 =
* Minor Fix

= 1.0.2 =
* Bug fix to allow the field to not be required.

= 1.0.1 =
* Included POT file for translations.

= 1.0 =
* Initial release.

== Upgrade Notice ==
= 1.0.10 =
* Minor Fix