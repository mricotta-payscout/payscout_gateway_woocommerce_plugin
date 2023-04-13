=== Payscout Paywire Gateway ===
Contributors: payscoutdevelopment, arcanedevops
Donate link: https://www.payscout.com/
Plugin Name: Payscout Paywire Gateway
Plugin URI: https://www.payscout.com/
Author URI: https://www.payscout.com/
Author: Payscout LLC
Tags: stripe, payscout, paywire, woocommerce, sripe terminal, point of sale, in person payments, pos, bbpos, ingenico, moby, e-commerce, ecommerce, store, sales, sell, shop, checkout, wcpos, woo, storefront, payments
Version: 1.0.0
Requires at least: 5.5.0
Tested up to: 6.2
Requires PHP: 7.3
Requires WooCommerce: 6.3.1
WooCommerce tested up to: 7.2.1
Stable: 1.0.0
License: GPLv3
License URI: https://www.gnu.org/licenses/gpl-3.0.html

Payscout Paywire Gateway connects your WooCommerce shop to your Payscout account via the Paywire API.  This plugin embeds a PCI-friendly card form on your checkout page.  As a third-party hosted library, Payscout's EPF library off-loads PCI responsibilities to Payscout in order to make it a turnkey payment option.

== Description ==

The Payscout Paywire Gateway (aka EPF) embeds a payment method form onto your checkout from Payscout's Paywire servers.  As a third-party hosted environment, your PCI responsibilities are offloaded to Payscout, making this a turnkey solution.

Wuthin your plugin settings, you'll also be able to write custom frontend gateway labels, select supportable shipping methods, and customize your field's styling using Sass-similar CSS objects in JSON format which can be assigned to any of our default classes ("base", "invalid", etc).  You can learn more information about our components styling on our website at [https://project.payscout.com/dbtranz/docs/EPF/EPFGuide.html#Components](https://project.payscout.com/dbtranz/docs/EPF/EPFGuide.html#Components).

== Features ==

* Real-time PCI-friendly embedding, card validation, and transaction capture
* Reconcile lagging pending and processing orders nightly
* Customizable CSS
* Order headers contain transaction ID's that can be used for investigation with Payscout support and your VPOS.

= Minimum Requirements =

Our plugin is tested up to the versions listed within the file headers and documentation seen in this package.  We practice graceful degradation, so we will not be performing backward compatibility testing.  Similarly, we receive WP and WC updates the same as you do, so as is best practice, auto-updates should be disabled as any forward compatibility updates we will perform will be following WP and WC releases.

Please be sure to read documentation and follow best practices when installing or updating.  As is standard practice, all updates and installation should be performed only on a staging environment and then released to production following testing.  We are not responsible for issues arising from outdated supporting software and will not be able to assist in resolution.

* PHP 7.0 or greater is recommended
* MySQL 5.6 or greater is recommended
* WordPress 6.1 or greater is recommended
* Woocommerce 7.1 or greater is recommended

== Installation ==

1. Download the plugin package to your plugins directory
2. Log into your WP Admin panel and visit the plugins directory
3. Click the "activate" link
4. On the plugin's settings page, each field contains a tooltip to instruct you on how to fill out the field.  Proceed as follows:
  1. When your account was verified you'll have received 2 sets of 2 keys from Payscout support containing (a) a public key and (b) a secret key.  Copy/paste those into the corresopnding fields.  The "Test" fields should contain only staging keys.  Staging keys will contain the word "test" in them.
  2. In "title", "instructions", and "description", enter the text you want your customer to see on the checkout page
  3. For "Enable shipping methods" unless you have alternative payment methods for certain shipping options (ie. if you are not approved for out-of-country purchases), click the dropdown and ctrl+select all of your shipping methods, otherwise select the ones you can support.
  4. For "Accept virtual orders", leave this enabled.  Only disable this if virtual orders must be paid with a different method such as a card-on-file or digital currencies.
  5. The style object cna be changed to match your preferred CSS styles as seen in our documentation [https://project.payscout.com/dbtranz/docs/EPF/EPFGuide.html#Components_Styling](https://project.payscout.com/dbtranz/docs/EPF/EPFGuide.html#Components_Styling).  Be sure to only configure this on staging prior to launch as this will interact directly with our JS library, so it can break the integration if not properly formated.
  6. Scroll up to the top of the settings.  To use your production endpoints (live) select the Live/Test checkbox to "Enable live mode".  This checkbox should always be unchecked on staging and development sites while enabled/checked on production/live sites.
  7. Finally, enable the "Enable Gateway" checkbox to turn the gateway on and cick "save".

== Frequently Asked Questions ==

= Can I use this plugin with Payscout if I'm using a different gateway with Payscout as the processor? =

No, this is a Paywire gateway.

= Do you work with any partners that can help us with complex integrations? =

Payscout works with several developers including [Payscout software vendor Arcane Strategies](https://www.arcanestrategies.com).  Your partnership or account manager can provide you with recommendations.

= Is this the only gateway you offer? =

No, our gateway is extremely robust with both frontend and backend endpoints that will look a lot like others you may be familiar with (Stripe, NMI, etc).  We can support various payment methods including card and ACH, various components including our "payment" component, "cad" component, and a host of several themes, customizable processing workflows, and customizable styling.

We even have a robust codepen library for you to use for building integrations of your own.

You can find direct documentation for our frontend and backend libraries on [https://project.payscout.com/dbtranz/docs/EPF/EPFGuide.html#ObjectWorkflow](https://project.payscout.com/dbtranz/docs/EPF/EPFGuide.html#ObjectWorkflow)

== Screenshots ==

1. This screen shot description corresponds to screenshot-1.(png|jpg|jpeg|gif). Screenshots are stored in the /assets directory.
2. This is the second screen shot

== Changelog ==

= 1.0 =
* Initial release.

== Upgrade Notice ==

= 1.0 =
1.0 is the initial release
[review update best practices](https://docs.woocommerce.com/document/how-to-update-your-site) before upgrading.