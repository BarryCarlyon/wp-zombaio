# WP Zombaio #
**Contributors:** BarryCarlyon  
**Donate link:** http://barrycarlyon.co.uk/wordpress/wordpress-plugins/wp-zombaio/  
**Tags:** zombaio, membership, adult  
**Requires at least:** 3.4.2  
**Tested up to:** 3.4.2  
**Stable tag:** 1.0.4  
**License:** GPLv2 or later  
**License URI:** http://www.gnu.org/licenses/gpl-2.0.html  

Catches PostBack Information from the Adult Payment Gateway Zombaio and acts accordingly

## Description ##

This Plugin allows Site Admins to easily configure and use the Adult Payment Gateway [Zombaio](https://www.zombaio.com/) for use with WordPress. In a more Secure and WordPress-y way than the existing Zombaio recommended script.

ShortCodes help to make joining the site and displaying the Zombaio Site seal as easy as possible.
As well as logging transmitted data from Zombaio as a Custom Post Type.

### Membership End/Cancellation ###

When a user delete/cancel occurs, the admin can choose whether the User is deleted from WordPress or just suspended from being able to login. Users can be manually blocked from Site Access without deleting the account.

### Protection ###

This plugin works with "Anyone can register" disabled, stopping users signing up without using a Zombaio based Join Form.

Built in Splash Page/redirection allows you to redirect logged out users to a WordPress Page of your Choosing, thus protecting your content from non members, as well as giving you the ability to warn users about the site content, or create a suitable "Join my Site" page.

### Selling ###

You can allow users to Purchase Zombaio Credits.
Users can then use these Credits to Purchase content on your site.
That content can then be timed access, or access forever.

### Multi Site ###

This plugin is not currently Multi Site aware.
Its not been tested on Multi Site, so no guarentee it works or not.....

### Further Usage ###

See [Other Notes](http://wordpress.org/extend/plugins/wp-zombaio/other_notes/) for Usage/Instructions

For Extra help/support or otherwise, either use the [WordPress Support Forum](http://wordpress.org/support/plugin/wp-zombaio), or [Drop Me a Line](http://barrycarlyon.co.uk/wordpress/contact/)

### Whats Coming Soon ###

* Allow Pages, when you have the login block on, you can set a flag to allow pages/posts thru
* Flexible Protection

## Installation ##

1. Download the Plugin from Extend
1. Unzip the Zip File
1. Upload `wp-zombaio` folder to the `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress
1. Use the Wizard to quicky run thru the setup and get everything going
1. Add the SideBar widget, or use the ShortCode to add a Join Site form.

## Screenshots ##

###1. The ShortCodes and Widgets in Action###
![The ShortCodes and Widgets in Action](http://s-plugins.wordpress.org/wp-zombaio/assets/screenshot-1.png)

###2. Activating the Plugin###
![Activating the Plugin](http://s-plugins.wordpress.org/wp-zombaio/assets/screenshot-2.png)

###3. Wizard Step 1###
![Wizard Step 1](http://s-plugins.wordpress.org/wp-zombaio/assets/screenshot-3.png)

###4. Wizard Step 2###
![Wizard Step 2](http://s-plugins.wordpress.org/wp-zombaio/assets/screenshot-4.png)

###5. Wizard Step 3###
![Wizard Step 3](http://s-plugins.wordpress.org/wp-zombaio/assets/screenshot-5.png)

###6. The Settings Page in Full###
![The Settings Page in Full](http://s-plugins.wordpress.org/wp-zombaio/assets/screenshot-6.png)

###7. WP Widgets Page###
![WP Widgets Page](http://s-plugins.wordpress.org/wp-zombaio/assets/screenshot-7.png)

###8. Logs Interface###
![Logs Interface](http://s-plugins.wordpress.org/wp-zombaio/assets/screenshot-8.png)

###9. Menu Control###
![Menu Control](http://s-plugins.wordpress.org/wp-zombaio/assets/screenshot-9.png)

###10. Updating Users - Table###
![Updating Users - Table](http://s-plugins.wordpress.org/wp-zombaio/assets/screenshot-10.png)

###11. Updating Users - A User###
![Updating Users - A User](http://s-plugins.wordpress.org/wp-zombaio/assets/screenshot-11.png)

###12. Creating a Credit Purchasble Post###
![Creating a Credit Purchasble Post](http://s-plugins.wordpress.org/wp-zombaio/assets/screenshot-12.png)

###13. Graphs!###
![Graphs!](http://s-plugins.wordpress.org/wp-zombaio/assets/screenshot-13.png)


## Changelog ##

### 1.1.0 ###
* If Logged in don't show login form, both Widet and ShortCode
* Added ability to control WordPress Menus (Logged In/Logged Out), this can be enabled/disabled in case your Theme Provides this option or you don't need it
* Added Settings Upgrader
* Show the User their Subscription ID and current Credits balance on the Profile Edit Page
* Support Credit Purchase via Zombaio and crediting users with Credits, as a shortcode and sidebar widget
* You can change a users current Credit Balance
* Support Post/Page purchase via Credits, Items purchased can be set to be one off purchase, or timed access
* Manually Suspend a user
* Graphs!
* Ability to enable/disable email copies and change the target email address for them
* Added Approval and Decline URL's as arguments to both the shortcode and widget for Join Form

### 1.0.4 ###
* Tweaks to the Validate ZScript Code
* Actually fixed IPN IP Verfication so it actually works
* Accept and log chargebacks and declined messages
* New/improved logging interface
* Added the Guide
* Added notes on Caching Plugins and CloudFlare
* Improved the interface and layout (somewhat looks a little like Zombaios ZOA interface)
* Internationaslised the plugin/added translation support
* Added a Login Widget
* Added Login block, and the abilty to redirect users to a "Landing Page", default is the WP Login Page.
* [zombaio_join] shortcode supports additional arguments:
*** align - form placement - choices:** left center right  
*** buttonalign - button placement - choices:** left center right  
* width - form width
* [zombaio_seal] shortcode supports additiona arguments:
*** align - placement - choices:** left center right  
* Added Shortcode [zombaio_login] renders a basic Login Form, its also a Widget

### 1.0.2 ###
* Prettification (logo/icon)
* Added a Notice/warning when Anyone can Register is enabled.
* Fix a UI glitch when the sidebar and/or one or more forms are on a screen
* Stop Passwords being double hashed (and confusing users at login) as the selected Password won't work

### 1.0.0 ###
* Initial Release

## Usage ##

### "There's a Wizard Harry!" ###

Upon first activating the Plugin you can run the Quick Start wizard to get you Up and running Quickly!

With the block enabled, only the landing page is accessable, and users are forced to login or register.

You can run with Anyone can Register turned off, which means users can only use your Landing page, or the Zombaio Join Form/Widget to Register.

You can refer to the in plugin guide, which contains lots of setup information, settings notes and advice.

The Plugin provides four Shortcodes which are also available as Sidebar Widgets.

### Zombao Seal ###

The first being the Zombaio Site Seal, which you can enter in the settings and then quickly add it where needed.

[zombaio_seal]

ShortCode Arguments

* align - save you having to wrap it in a div, you can use left, center or right to control page placement

### Join Form ###

The second being, the Join Form.

[zombaio_join]content[/zombaio_join]

ShortCode Arguments

* join_url - the Zombaio Join Form URL for the relevant subscription
* submit - the text to use on the Submit button
* align - save you having to wrap it in a div, you can use left, center or right to control page placement
* buttonalign - save you having to wrap it in a div, you can use left, center or right to button placement
* width - the Form Width, just a number
* approve_url - redirect user to this url on Purchase Complete (default is the Zombaio Login Details Page)
* decline_url - redirect user to this url on Purchase Delcine (default is the Zombaio Fail Page)

ShortCode Content

The shortcode content is added to the form beneath the password field and the join button.

### Login ###

The third and final item is, a Login Form.

[zombaio_login]

Renders a login form, also available as a Widget

### Credits Purchase ###

[zombaio_add_credits]

Renders a Credits Purchase Form

Shortcode Arguments

* join_url - the Zombaio Join Form URL for the Credit Pricing Structure
* approve_url - redirect user to this url on Purchase Complete (default is the Current Page)
* decline_url - redirect user to this url on Purchase Delcine (default is the Current Page)
* submit - the text to use on the Submit button

## Upgrade Notice ##

There is nothing Special to do for all upgrades.

All new settings are spawned to default values automagically

You should however take a Database and File Backup before Hand. Just in case.

## Frequently Asked Questions ##

### Do I need the ZScript script? ###

No, this is a replacement for the downloadable ZScript and the "Standard" Zombaio .htaccess/.htpasswd Protection. Existing ZScript protection should be removed.

### What Details do I need for this Plugin? ###

Just the ZombaioGWPass (aka Digest Key) and your SiteID.
SiteID's are specific to each Site/URL you add to the ZOA Website Manager.

We do not need your Account User name/login or Password.

### What settings do I need in Zombaio ZOA ###

If you follow the Wizard inside the plugin it will guide you thru the setup and settings to use.

### What about the Zombaio Seal Code? ###

We provide a shortcode and widget to display the Seal on your website, and instructions on where to obtain your code.

### I need help! ###

Either use the [WordPress Support Forum](http://wordpress.org/support/plugin/wp-zombaio), or [Drop Me a Line](http://barrycarlyon.co.uk/wordpress/contact/)
