<?php

/*
* $Id: wp_zombaio_guide.php 615256 2012-10-21 14:59:34Z BarryCarlyon $
* $Revision: 615256 $
* $Date: 2012-10-21 15:59:34 +0100 (Sun, 21 Oct 2012) $
*/

class wp_zombaio_guide extends wp_zombaio {
	function __construct() {
		$this->admin_page_top();

		echo '<h3>' . __('Zombaio Join Form', 'wp-zombaio') . '</h3>';
		echo __('
<p>Zombaio supports two methods of joining your website</p>
<p>Under Website Management, when viewing the Settings for your Site, the <strong>Zombaio Join Form Template</strong> has two options</p>
<h4>Option 1 - Simple Credit Card Template</h4>
<p>This method means on your site, for each pricing structure, you&#39;ll need multiple Join Form Widgets/Shortcodes, on your WordPress blog, to direct the user to Pay</p>
<p>This is good for when you want to make a designed entry/lading page on your blog/site</p>
', 'wp-zombaio');

		echo '<h4>' . __('Option 2 - e-Ticket Selection Template', 'wp-zombaio') . '</h4>';
		echo __('
<p>With this method, any Join Form URL, results in the same form</p>
<p>When arriving at Zombaio, users can then choose which Subscription/Package to join, so in this case you only need a single Join Form Widget/Shortcode on your WordPress Blog</p>
<p>The choice is up to you</p>
', 'wp-zombaio');

		echo '<h3>' . __('Google Analytics', 'wp-zombaio') . '</h3>';
		echo __('
<p>If you add your GA Property ID, you can track if users are navigating thru your site, and leaving before paying</p>
<p>This will be handy when considering your pricing structure</p>
', 'wp-zombaio');

		echo '<h3>' . __('Join Form Image', 'wp-zombaio') . '</h3>';
		echo __('
<p>It is a good idea to upload your Site Logo to Zombaio for use on Join Forms, helps remind users that whilst on the Zombaio Website paying, they are joining your Website!</p>
', 'wp-zombaio');

/*
<h3>Credits</h3>
<p>Purchasing Credits works much the same way as Joining the site.</p>
<p>Just create a Pricing Structure for your Credits and use the Join Form URL in the Shortcode</p>
*/

$this->admin_page_spacer(__('Login Control', 'wp-zombaio'));

		echo __('
<p>With Login Control enabled, only Logged In Users (and thus people who have paid), can access your content</p>
<p>You can choose where users are directed to, the default is the WP-Login.php page, but since WP Zomabio does not hook/work on the standard registration page, users cannot sign up from here</p>
<p>So normally you shoule have <i>Anyone can Register</i> disabled</p>
<p>You can pick a page to send users to, this page should containt a Login Form and a Register Form (or at least a link to your Zombaio Join Form)</p>
<p>Zombaio Rules (or at least advice) that you should put a Zombaio Seal on your site somewhere, this is a good page to place it</p>
<p>You can do something like:</p>', 'wp-zombaio');
		echo '<textarea onclick="jQuery(this).select();" readonly="readonly" style="width: 600px;" rows="2">[zombaio_join align="left" width="300" join_url="JOINURL" buttonalign="center"]PRICING[/zombaio_join]
[zombaio_seal align="right"]</textarea>';
		echo __('
<p>Swap JOINURL for your Zombaio Pricing Structure, Join Form URL, and PRICING for some Introdutory Text, such as the Price and Membership Terms</p>
<p>That makes a nice Registration Landing Page</p>
', 'wp-zombaio');
		echo sprintf(__('You can see an <a href="%s" target="_blank">example here</a>', 'wp-zombaio'), 'http://dev.barrycarlyon.co.uk/?page_id=69');

$this->admin_page_spacer(__('Credits', 'wp-zombaio'));

		echo __('<p>Credit Purchase and Spending thereof comes in two parts</p>', 'wp-zombaio');
		echo '
<ul>
	<li>' . __('First a user needs to Purchase Credits', 'wp-zombaio') . '</li>
	<li>' . __('Second the user can then spend those Credits on your site', 'wp-zombaio') . '</li>
</ul>';

		echo '<h3>' . __('Zombaio Join Form', 'wp-zombaio') . '</h3>';
		echo __('
<p>First you need to choose your Credit Pricing Structure, and there are two options here</p>

<p>You can use the quick and easy Zombaio Method, where you create a Credits Pricing Structure on the Zombaio side, and then link to that, much the same as a standard subscription</p>
<p>Or we can dynamically set the Price to charge for a number of Credits inside WordPress and just let Zombaio handle taking the money</p>
<p>Both require creating a Credits Pricing Structure inside ZOA, this gives you a Join Form URL, to use in the Widget or ShortCode</p>
<p>If you are using the Basic Credit Pricing Structure a defined in ZOA, there is nothing else to do</p>
<p>If you want dynamic, Zombaio Just ignores the Set Structure, this means you can turn on/off your dynamic WordPress structure, to temporarily offer discounts/different pricing over the ZOA Set Pricing</p>
', 'wp-zombaio');

		echo __('<p>Zombaio&#39;s Credit Pricing Structure, limits you to 5 levels and charging 5 amounts, you just choose how many credits this buys</p>', 'wp-zombaio');
		echo __('<p>The Default Pre Selected Structure is:</p>', 'wp-zombaio');
		echo '
<ul class="disc">
	<li>' . sprintf(__('%s for %s credits', 'wp-zombaio'), '10.00', 10) . '</li>
	<li>' . sprintf(__('%s for %s credits', 'wp-zombaio'), '25.00', 25) . '</li>
	<li>' . sprintf(__('%s for %s credits', 'wp-zombaio'), '50.00', 55) . '</li>
	<li>' . sprintf(__('%s for %s credits', 'wp-zombaio'), '75.00', 90) . '</li>
	<li>' . sprintf(__('%s for %s credits', 'wp-zombaio'), '100.00', 130) . '</li>
</ul>';

$this->admin_page_spacer(__('Post/Page/Item Purchase', 'wp-zombaio'));
		
		echo __('
<p>WordPress treats Posts/Pages/Custom Post Types, the same</p>
<p>You should be able to set any (that support Post Meta Boxes and standard templates), to cost a number of Zombaio Credits to Purchase.</p>
<p>And then Access to that Post should require the spending of the stated number of credits.</p>
<p>In addition you can choose whether that Purchase is forever (normal) or Timed access.</p>
<p>The timed access can be set to be a number of Hours, Days or Weeks, this of course does not stop you setting it to 52 weeks to represent a year.....</p>
', 'wp-zombaio');

$this->admin_page_spacer(__('Site Seal', 'wp-zombaio'));

		echo sprintf(__('
<p>You can get your Seal Code as follows:
	<ul class="disc">
		<li>Login to <a href="%s">ZOA</a></li>
		<li>Navigate to:
			<ul class="disc">
				<li>Tools</li>
				<li>Pricing Structure</li>
			</ul>
		</li>
		<li>Then Manage/Edit any Pricing Structure for your site</li>
		<li>Then in the HTML Button Field, copy everything from and including &lt;!-- START ZOMBAIO SEAL CODE --&gt; to &lt;!-- END ZOMBAIO SEAL CODE --&gt;</li>
		<li>Paste that into this field</li>
		<li><a href="#nowhere" onclick="%s">View a ScreenShot/Example</a>, the Seal Code is Hightlighted</li>
	</ul>
</p>', 'wp-zombaio'), 'https://secure.zombaio.com/ZOA/', 'jQuery(\'#wp_zombaio_sealshot\').dialog({width: 720});');

echo '
<div id="wp_zombaio_sealshot" style="display: none;">
	<img src="' . plugin_dir_url(__FILE__) . 'img/seal_code.png" alt="' . __('Seal Screenshot', 'wp-zombaio') . '" style="border: 1px solid #000000;" />
</div>
';

$this->admin_page_spacer(__('Menus', 'wp-zombaio'));

echo '<p>' . __('You can optionally enable Zombaio Menu Control</p>', 'wp-zombaio') . '</p>';
echo '<p>' . __('Essentially this means if your theme does not support showing a different Menu, based on if the user is Logged In or not, you can use the Plugin to power this instead', 'wp-zombaio') . '</p>';
echo '<p>' . __('It is optional, as this functionality should really be provided by your Theme, rather than a plugin', 'wp-zombaio') . '</p>';

$this->admin_page_spacer(__('Running a Membership Site', 'wp-zombaio'));

echo sprintf(__('<p>For more thoughts and advice on Running a membership site, check out the <a href="%s" target="_blank">Your Members Blog</a></p>', 'wp-zombaio'), 'http://blog.yourmembers.co.uk/');

$this->admin_page_spacer(__('Caching, (Plugins or Otherwise) and CloudFlare', 'wp-zombaio'));

echo sprintf(__('<p>If you are running anything Caching related, (which you probably shouldn&#39;t on a <a href="%s" target="_blank">membership site</a>), you may need to Whitelist Zombaios Notifications IP Addresses, so they bypass the potential block</p>', 'wp-zombaio'), 'http://blog.yourmembers.co.uk/2012/your-members-and-caching/');
echo sprintf(__('<h4>Known Caching Plugins and How to Bypass</h4>

<ul class="disc">
<li><strong>CloudFlare Whitelisting</strong>, visit <a href="%s" target="_blank">Threat Control</a> add Custom Rule, and Trust all the Zombaio IP&#39;s</li>
<li><strong>Misc</strong>, generally you should allow all Query Strings containing <i>ZombaioGWPass</i> to Bypass any and all Caching</li>
</ul>

<h5>Want to add a Caching Bypass Solution to this list, <a href="%s">Drop me a Line</a></h5>

<h4>Zombaio IPs</h4>
<p>You can load/fetch the Current Zombaio Known IP Addresses</p>
', 'wp-zombaio'), 'https://www.cloudflare.com/threat-control', 'http://barrycarlyon.co.uk/wordpress/contact/');

echo '<a href="#load" id="loadips" class="button-secondary">' . __('Load IP&#39;s', 'wp-zombaio') . '</a> ' . __('or', 'wp-zombaio') . ' <a href="#load" id="loadcsvips" class="button-secondary">' . __('Load CSV List of IP&#39;s', 'wp-zombaio') . '</a>';

echo '
<div id="loadipsoutput"></div>

<script type="text/javascript">
jQuery(document).ready(function() {
	jQuery(\'#loadips\').click(function() {
		jQuery.get(\'' . home_url('?wp_zombaio_ips=1') . '\', function(data) {
			jQuery(\'#loadipsoutput\').html(data).dialog({height: 400});
		})
	});
	jQuery(\'#loadcsvips\').click(function() {
		jQuery.get(\'' . home_url('?wp_zombaio_ips=1&csv=1') . '\', function(data) {
			jQuery(\'#loadipsoutput\').html(data).dialog({height: 240});
		})
	});
});
</script>
';

		$this->admin_page_bottom();
	}
}
