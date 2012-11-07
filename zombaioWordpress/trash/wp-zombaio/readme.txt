=== WP Zombaio ===
Contributors: BarryCarlyon
Donate link: http://barrycarlyon.co.uk/
Tags: zombaio, membership, adult
Requires at least: 3.4.2
Tested up to: 3.4.2
Stable tag: 1.0.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Catches Information from the Adult Payment Gateway Zombaio and acts accordingly

== Description ==

This Plugin allows Site Admins to easily configure and use the Adult Payment Gateway [Zombaio](https://www.zombaio.com/) for use with WordPress. In a more Secure and WordPress-y way that the existing recommended script.

Two ShortCodes help to make joining the site and displaying the Zombaio Site seal as easy as possible.
As well as logging transmitted data from Zombaio as a Custom Post Type.

Currently only User Add, User Delete and User Rebill are Processed.
But all data is logged.

In the future, Credit Purchase will be supported.

When a user delete/cancel occurs, the admin can choose whether the User is deleted from WordPress or just suspended from being able to login.

== Installation ==

1. Download the Plugin from Extend
1. Unzip the Zip File
1. Upload `wp_zombaio` folder to the `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress
1. Use the Wizard to quicky run thru the setup and get everything going
1. Add the SideBar widget, or use the ShortCode to add a Join Site form.

== Screenshots ==

1. The ShortCodes and Widgets in Action
1. Activating the Plugin
1. Wizard Step 1
1. Wizard Step 2
1. Wizard Step 3
1. The Settings Page in Full
1. WP Widgets Page

== Changelog ==

= 1.0 =
* Initial Release

== Usage ==

The Plugin provides two Shortcodes which are also available as Sidebar Widgets.

The first being the Zombaio Site Seal, which you can enter in the settings and then quickly add it where needed.

[zombaio_seal] - No Options

The second being, the Join Form.

[zombaio_join]content[/zombaio_join]

ShortCode Arguments

1. join_url - the Zombaio Join URL for the relevant subscription

2. submit - the text to use on the Submit button

ShortCode Content

The shortcode content is added to the form beneath the password field and the join button.

