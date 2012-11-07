<?php

/*
 * Plugin Name: WP Zombaio
 * Plugin URI: http://barrycarlyon.co.uk/wordpress/
 * Description: Catches Information from the Adult Payment Gateway Zombaio and acts accordingly
 * Author: Barry Carlyon
 * Version: 1.0.0
 * Author URI: http://barrycarlyon.co.uk/wordpress/
 */
 
class wp_zombaio {
	function __construct($proc = FALSE) {
		if (isset($_GET['bcreset'])) {
			delete_option('wp_zombaio');
		}

		$this->setup();
		if ($proc) {
			return;
		}

		if (is_admin()) {
			// admin options interface
			$this->admin();
			return;
		}
		$this->frontend();
		return;
	}
	
	private function setup() {
		$this->init();
		add_action('init', array($this, 'post_types'));
		add_action('plugins_loaded', array($this, 'detect'));
		add_action('widgets_init', array($this, 'widgets_init'));
		add_filter('wp_authenticate_user', array($this, 'wp_authenticate_user'), 10, 2);
		return;
	}
	public function init() {
		$options = get_option('wp_zombaio', FALSE);
		if (!$options) {
			$options = new stdClass();
			$options->site_id = '';
			$options->gw_pass = '';
			$options->bypass_ipn_ip_verification = FALSE;
			$options->delete = TRUE;
			$options->wizard = FALSE;


			$options->seal_code = '';

			$this->options = $options;
			$this->saveoptions();
		}
		$this->options = $options;
		return;
	}
	private function saveoptions() {
		return update_option('wp_zombaio', $this->options);
	}

	public function post_types() {
		register_post_type(
			'wp_zombaio',
			array(
				'label'		=> 'Zombaio Log',
				'labels'	=> array(
					'name' => __('Zombaio Log'),
					'singular_name' => __('Zombaio Log'),
					'add_new' => __('Add New'),
					'add_new_item' => __('Add New Zombaio Log'),
					'edit_item' => __('Edit Zombaio Log'),
					'new_item' => __('New Zombaio Log'),
					'all_items' => __('All Zombaio Logs'),
					'view_item' => __('View Zombaio Log'),
					'search_items' => __('Search Zombaio Logs'),
					'not_found' =>  __('No Zombaio Logs found'),
					'not_found_in_trash' => __('No Zombaio Logs found in Trash'), 
					'parent_item_colon' => '',
					'menu_name' => __('Zombaio Log')
				),
				'public'	=> TRUE,
				'supports'	=> array(
					'title',
					'editor',
					'custom-fields',
				),
				'has_archive'			=> false,
				'publicly_queryable'	=> false,
				'exclude_from_search'	=> true,
			)
		);
		
		register_post_status('user_add', array(
			'label' => 'User Add',
			'public' => TRUE,
			'exclude_from_search' => TRUE,
			'show_in_admin_all_list' => TRUE,
			'show_in_admin_status_list' => TRUE,
			'label_count' => _n_noop( 'User Add <span class="count">(%s)</span>', 'User Add <span class="count">(%s)</span>' ),
		));
		register_post_status('user_delete', array(
			'label' => 'User Delete',
			'public' => FALSE,
			'exclude_from_search' => TRUE,
			'show_in_admin_all_list' => TRUE,
			'show_in_admin_status_list' => TRUE,
			'label_count' => _n_noop( 'User Delete <span class="count">(%s)</span>', 'User Delete <span class="count">(%s)</span>' ),
		));
		register_post_status('user_addcredits', array(
			'label' => 'User Add Credits',
			'public' => FALSE,
			'exclude_from_search' => TRUE,
			'show_in_admin_all_list' => TRUE,
			'show_in_admin_status_list' => TRUE,
			'label_count' => _n_noop( 'User Add Credits <span class="count">(%s)</span>', 'User Add Credits <span class="count">(%s)</span>' ),
		));
		register_post_status('rebill', array(
			'label' => 'User Rebill',
			'public' => FALSE,
			'exclude_from_search' => TRUE,
			'show_in_admin_all_list' => TRUE,
			'show_in_admin_status_list' => TRUE,
			'label_count' => _n_noop( 'User Rebill <span class="count">(%s)</span>', 'User Rebill <span class="count">(%s)</span>' ),
		));
		
		return;
	}

	private function admin() {
		add_action('admin_notices', array($this, 'admin_notices'));
		add_action('admin_menu', array($this, 'admin_menu'));
	}
	public function admin_notices() {
		if (!$this->options->wizard) {
			if (isset($_REQUEST['wp_zombaio']) && $_REQUEST['wp_zombaio'] == 'dismisswizard') {
				$this->options->wizard = TRUE;
				$this->saveoptions();
				return;
			}
			// offer wizard
			$wizard_prompt = '<div id="wp_zombaio_wizard" style="display: block; clear: both; background: #000000; color: #9F9F9F; margin-top: 10px; margin-right: 15px; padding: 12px 10px; font-size: 12px;">';
			$wizard_prompt .= 'Run the <a href="' . admin_url('admin.php?page=wp_zombaio&do=wizard') . '">WP Zombaio Install Wizard?</a>';
			$wizard_prompt .= '<a href="' . admin_url('admin.php?page=wp_zombaio&do=wizarddismiss') . '" style="float: right;">Dismiss Wizard</a>';
			$wizard_prompt .= '</div>';
			echo $wizard_prompt;
		}
	}

	/**
	User admin interface
	*/
	public function admin_menu() {
		add_menu_page('WP Zombaio', 'WP Zombaio', 'activate_plugins', 'wp_zombaio', array($this, 'admin_page'));
	}
	public function admin_page() {
		echo '<div class="wrap">';
		echo '<div id="icon-options-general" class="icon32"><br></div>';
		echo '<h2>WP Zombaio</h2>';

		$do = isset($_REQUEST['do']) ? $_REQUEST['do'] : FALSE;
		$step = $nextstep = FALSE;

		if ($this->options->wizard) {
			echo '<form action="' . admin_url('admin.php?page=wp_zombaio&do=wizard') . '" method="post" style="float: right;"><p class="submit"><input type="submit" value="Run Wizard Again" class="button-secondary" /></p></form>';
		}

		echo '<form method="post" action="' . admin_url('admin.php?page=wp_zombaio') . '">';

		if ($do == 'wizard') {
			$step = isset($_REQUEST['step']) ? $_REQUEST['step'] : 0;
			$nextstep = $step;
			switch ($step) {
				case '3':
					$this->options->wizard = TRUE;
					$this->saveoptions();
					echo '<div id="message" class="updated"><p>All Done, you are ready to go</p></div>';
					echo '<p>You can now review the current options and change advanced options</p>';
					$do = FALSE;
					break;
				case '2':
					$gw_pass = isset($_REQUEST['gw_pass']) ? $_REQUEST['gw_pass'] : FALSE;
					if (!$gw_pass) {
						echo '<div id="message" class="error"><p>You Need to enter your Zombaio GW Pass</p></div>';
					} else {
						$this->options->gw_pass = $gw_pass;
						$this->saveoptions();

						echo '<p>Now the final step</p>';
						echo '<p>Update the <strong>Postback URL (ZScript)</strong> to the following:</p>';
						echo '<input type="text" name="postbackurl" value="' . site_url() . '" />';
						echo '<p>Then Press Validate</p>';
						echo '<p>Zombaio will then Validate the Settings, and if everything is correct, should say Successful and save the URL</p>';
						echo '<p>If not, please <a href="' . admin_url('admin.php?page=wp_zombaio&do=wizard') . '">Click Here</a> and we will restart the Wizard to confirm your settings</p>';
						echo '<p>If everything worked, just hit Submit below</p>';

						$nextstep = 3;
						break;
					}
				case '1':
					if ($step == 1) {
						$site_id = isset($_REQUEST['site_id']) ? $_REQUEST['site_id'] : FALSE;
						if (!$site_id) {
							echo '<div id="message" class="error"><p>You Need to enter your Site ID</p></div>';
						} else {
							$this->options->site_id = $site_id;
							$this->saveoptions();

							echo '<p>Next we need to setup the Zombaio -&gt; Communications</p>';
							echo '<p>In Website Management, select Settings</p>';
							echo '<p>Copy and Enter the <strong>Zombaio GW Pass</strong> below</p>';
							echo '<label for="gw_pass">Zombaio GW Pass <input type="text" name="gw_pass" id="gw_pass" value="' . $this->options->gw_pass . '" /></label>';
							$nextstep = 2;
							break;
						}
					}
				case '0':
				default:
					echo '<p>This Wizard will Guide you thru the Zombaio Setup</p>';
					echo '<p>First your will need a Zombaio Account</p>';
					echo '<p>And to have added your Website under Website Managment</p>';
					echo '<p>This will give you a <strong>Site ID</strong>, enter that now:</p>';
					echo '<label for="site_id">Site ID: <input type="text" name="site_id" id="site_id" value="' . $this->options->site_id . '" /></label>';
					$nextstep = 1;
			}
			echo '<input type="hidden" name="step" value="' . $nextstep . '" />';
			echo '<input type="hidden" name="do" value="' . $do . '" />';
		}

		if (!$do) {
			if ($_POST && !$nextstep) {
				foreach ($_POST as $item => $value) {
					$value = isset($this->options->$item) ? $this->options->$item : '';
					$value = isset($_POST[$item]) ? $_POST[$item] : $value;
					$this->options->$item = stripslashes($value);
				}
				$this->saveoptions();
				echo '<div id="message" class="updated"><p>Settings Updated</p></div>';
			}

			echo '<p>For Reference, your Zombaio Postback URL (ZScript) should be set to <input type="text" name="postbackurl" value="' . site_url() . '" /></p>';
			echo '<table>';

			echo '<tr><td></td><td><h3>Standard Settings</h3></td></tr>';
			echo '<tr><td><label for="site_id">Site ID:</label></td><td><input type="text" name="site_id" id="site_id" value="' . $this->options->site_id . '" /></label></td></tr>';
			echo '<tr><td><label for="gw_pass">Zombaio GW Pass</label></td><td><input type="text" name="gw_pass" id="gw_pass" value="' . $this->options->gw_pass . '" /></label></td></tr>';

			echo '<tr><td></td><td><h3>Advanced Settings</h3></td></tr>';
			echo '<tr><td>Delete Action<br /><h4>What to do when Zombaio Calls User Delete</h4></td><td valign="top">';
				echo '<select name="delete">
					<option value="1" ' . ($this->options->delete ? 'selected="selected"' : '') . '>Delete User Account</option>
					<option value="0" ' . ($this->options->delete ? '' : 'selected="selected"') . '>Block User Access</option>
				</select></td></tr>';
			echo '<tr><td>Bypass IPN Verfication<br /><h4>We Validate the Zombaio IP against a Known list<br />You can bypass this if needed</h4></td><td valign="top">';
				echo '<select name="bypass_ipn_ip_verification">
					<option value="0" ' . ($this->options->bypass_ipn_ip_verification ? '' : 'selected="selected"') . '>No</option>
					<option value="1" ' . ($this->options->bypass_ipn_ip_verification ? 'selected="selected"' : '') . '>Yes</option>
				</select></td></tr>';

			echo '<tr><td></td><td><h3>Extras</h3></td></tr>';
			echo '<tr><td><label for="seal_code">Seal Code<br /><h4>Use the ShortCode [zombaio_seal]</h4></label></td><td><textarea name="seal_code" id="seal_code" style="width: 500px;" rows="5">' . $this->options->seal_code . '</textarea></td></tr>';

			echo '</table>';
		}

		echo '<p class="submit"><input type="submit" class="button-primary" value="Submit" /></p>';

		echo '</form>';
		echo '</div>';
	}

	/**
	Payment Processor
	*/
	public function detect() {
		$wp_zombaio = new wp_zombaio(TRUE);
		$wp_zombaio->process();
	}
	
	private function process() {
		$this->init();

		$gw_pass = isset($_REQUEST['ZombaioGWPass']) ? $_REQUEST['ZombaioGWPass'] : FALSE;
		if (!$gw_pass) {
			return;
		}
		if ($gw_pass != $this->options->gw_pass) {
			header('HTTP/1.0 401 Unauthorized');
			echo '<h1>Zombaio Gateway 1.1</h1><h3>Authentication failed.</h3>';
			exit;
		}
		
		$username = isset($_REQUEST['username']) ? $_REQUEST['username'] : FALSE;
		$test = substr($username, 0, 4);
		if ($test == 'Test') {
			// test mode
			echo 'OK';
			exit;
		}

		if (!$this->verify_ipn_ip()) {
			header('HTTP/1.0 401 Unauthorized');
			echo '<h1>Zombaio Gateway 1.1</h1><h3>Authentication failed, you are not Zombaio.</h3>';
			exit;
		}

		// verify site ID
		$site_id = isset($_REQUEST['SITE_ID']) ? $_REQUEST['SITE_ID'] : FALSE;
		if (!$site_id || $site_id != $this->options->site_id) {
			header('HTTP/1.0 401 Unauthorized');
			echo '<h1>Zombaio Gateway 1.1</h1><h3>Authentication failed.</h3>';
			exit;
		}
		
		$action = isset($_REQUEST['Action']) ? $_REQUEST['Action'] : FALSE;
		if (!$action) {
			header('HTTP/1.0 401 Unauthorized');
			echo '<h1>Zombaio Gateway 1.1</h1><h3>Authentication failed.</h3>';
			exit;
		}
		
		$logid = $this->log();
		$logmsg = '';
		
		$action = strtolower($action);
		switch ($action) {
			case 'user.add': {
				$subscription_id = isset($_REQUEST['SUBSCRIPTION_ID']) ? $_REQUEST['SUBSCRIPTION_ID'] : FALSE;
				if (!$subscription_id) {
					header('HTTP/1.0 401 Unauthorized');
					echo '<h1>Zombaio Gateway 1.1</h1><h3>Authentication failed.</h3>';
					exit;
				}
				
				$email = $_REQUEST['EMAIL'];
				$fname = $_REQUEST['FIRSTNAME'];
				$lname = $_REQUEST['LASTNAME'];
				$password = wp_hash_password($_REQUEST['password']);
				
				$user_id = username_exists($username);
				if (!$user_id) {
					$email_test = is_email($email);
					if ($email_test == $email) {
						$user_id = email_exists($email);
						if (!$user_id) {
							$user_id = wp_create_user( $username, $password, $email );
							if (!is_wp_error($user_id)) {
								$logmsg = 'User Created OK';
							} else {
								// error
								$logmsg = 'User Create: Fail ' . $user_id->get_error_message();
							}
						} else {
							// email exists
							$logmsg = 'User Create: Email Exists, Activating User';
						}
					} else {
						// invalid/empty email
						$logmsg = 'User Create: Failed ' . $email_test;
					}
				} else {
					// username exists
					$logmsg = 'User Create: UserName Exists, Activating User';
				}
				
				if ($user_id) {
					update_user_meta($user_id, 'wp_zombaio_delete', FALSE);
					update_user_meta($user_id, 'wp_zombaio_subscription_id', $subscription_id);
					update_user_meta($user_id, 'first_name', $fname);
					update_user_meta($user_id, 'last_name', $lname);
				} else {
					// epic fail
					echo 'USER_DOES_NOT_EXIST';
					exit;
				}
				break;
			}
			case 'user.delete': {
				$user = get_user_by('username', $username);
				if (!$user) {
					echo 'USER_DOES_NOT_EXIST';
					exit;
				}
				// delete of suspend?
				if ($this->options->delete == TRUE) {
					include('./wp-admin/includes/user.php');
					wp_delete_user($user->ID);
					$logmsg = 'User was deleted';
				} else {
					update_user_meta($user->ID, 'wp_zombaio_delete', TRUE);
					$logmsg ='User was suspended';
				}
				break;
			}
			case 'rebill': {
				$subscription_id = isset($_REQUEST['SUBSCRIPTION_ID']) ? $_REQUEST['SUBSCRIPTION_ID'] : FALSE;
				if (!$subscription_id) {
					header('HTTP/1.0 401 Unauthorized');
					echo '<h1>Zombaio Gateway 1.1</h1><h3>Authentication failed.</h3>';
					exit;
				}
				
				//get user ID by subscription ID
				global $wpdb;
				$query = 'SELECT user_id FROM ' . $wpdb->usermeta . ' WHERE meta_key = \'wp_zombaio_subscription_id\' AND meta_value = \'' . $subscription_id . '\'';

				$user_id = $wpdb->get_var($query);
				if (!$user_id) {
					echo 'USER_DOES_NOT_EXIST';
					exit;
				} else {
					$success = ym_request('Success', 0);
					// 0 FAIL 2 FAIL retry in 5 days
					if ($success == 1) {
						// all good
						update_user_meta($user_id, 'wp_zombaio_delete', FALSE);
					} else {
						if ($success) {
							$logmsg = 'Rebill Charge Failed: Retry in 5 Days';
						} else {
							$logmsg = 'Rebill Charge Failed: REASON CODE';
						}
					}
				}
				$logmsg = 'User rebilled cleared';
				break;
			}
			case 'declined': {
				$subscription_id = isset($_REQUEST['SUBSCRIPTION_ID']) ? $_REQUEST['SUBSCRIPTION_ID'] : FALSE;
				if (!$subscription_id) {
					header('HTTP/1.0 401 Unauthorized');
					echo '<h1>Zombaio Gateway 1.1</h1><h3>Authentication failed.</h3>';
					exit;
				}
				
				//get user ID by subscription ID
				global $wpdb;
				$query = 'SELECT user_id FROM ' . $wpdb->usermeta . ' WHERE meta_key = \'wp_zombaio_subscription_id\' AND meta_value = \'' . $subscription_id . '\'';

				$user_id = $wpdb->get_var($query);
				if (!$user_id) {
					echo 'USER_DOES_NOT_EXIST';
					exit;
				}
				$logmsg = 'User Card was Declined';
				break;
			}
			case 'user.addcredits': {
			}
			default: {
				header('HTTP/1.0 401 Unauthorized');
				echo '<h1>Zombaio Gateway 1.1</h1><h3>Authentication failed.</h3>';
				exit;
			}
		}
		
		// log result
		$this->logresult($logid, $logmsg, $user_id);
		$this->notifyadmin($logid, $logmsg);
		
		echo 'Ok';
		exit;
	}
	
	private function abort() {
	}
	
	private function log() {
		$post = array(
			'post_title'		=> 'Zombaio ' . $_REQUEST['Action'] . ' - ' . $_REQUEST['username'],
			'post_type'			=> 'wp_zombaio',
			'post_status'		=> (isset($_REQUEST['Action']) ? str_replace('.', '_', strtolower($_REQUEST['Action'])) : 'unknown'),
			'post_content'		=> print_r($_REQUEST, TRUE),
		);
		$r = @wp_insert_post($post);
		return $r;
	}
	private function logresult($logid, $logmsg, $user_id) {
		update_post_meta($logid, 'logmessage' , $logmsg);
		update_post_meta($logid, 'user_id', $user_id);
		update_post_meta($logid, 'json_packet', json_encode($_REQUEST));
		if (isset($_REQUEST['Amount'])) {
			update_post_meta($logid, 'amount', $_REQUEST['Amount']);
		}
		return;
	}
	
	private function notifyadmin($logid, $logmsg) {
		// notify admin
		$subject = 'WP Zombaio: Payment Result';
		$message = 'A Payment has been processed' . "\n"
			. 'The Result was: ' . $logmsg . "\n"
			. 'Full Log: ' . print_r($_REQUEST, TRUE) . "\n"
			. 'Love WP Zombaio';
		@wp_mail(get_option('admin_email'), $subject, $message);
		return;
	}

	private function verify_ipn_ip() {
		if ($this->options->bypass_ipn_ip_verification) {
			return true;
		}
		$ip = $_SERVER['REMOTE_ADDR'];

		$request = new WP_Http;
		$data = $request->request('http://www.zombaio.com/ip_list.txt');
		if ($data) {
			$ips = explode('|', $data);

			if (in_array($ip, $ips)) {
				return TRUE;
			}
		}
		return TRUE;
	}

	/**
	widgets
	*/
	public function widgets_init() {
		register_widget('wp_zombaio_widget');
		register_widget('wp_zombaio_seal');
	}


	/**
	FrontEnd
	*/
	public function frontend() {
		add_shortcode('zombaio_seal', array($this, 'shortcode_zombaio_seal'));
		add_shortcode('zombaio_join', array($this, 'zombaio_join'));

		add_action('wp_enqueue_scripts', array($this, 'wp_enqueue_scripts'));
	}
	public function wp_enqueue_scripts() {
		wp_enqueue_style('wp_zombaio_css', plugin_dir_url(__FILE__) . basename(__FILE__) . '?do=css');
	}
	public function wp_authenticate_user($user) {
		if (get_user_meta($user->ID, 'wp_zombaio_delete', TRUE)) {
			$err = new WP_Error();
			$err->add('wp_zombaio_error', 'Zombaio Failed Rebill');
			return $err;
		}
		return $user;
	}
	
	/**
	ShortCodes
	*/
	public function shortcode_zombaio_seal() {
		return $this->options->seal_code;
	}
	public function zombaio_join($args, $content = '') {
		if (is_user_logged_in()) {
			return '';
		}

		$html = '';

		$join_url = isset($args['join_url']) ? $args['join_url'] : FALSE;

		if ($join_url) {
			if (FALSE !== strpos($join_url, 'zombaio.com')) {
				list($crap, $zombaio, $com_crap, $id, $zom) = explode('.', $args['join_url']);
			} else {
				$id = $args['join_url'];
			}

			if ($id) {
				$html .= '<form action="https://secure.zombaio.com/?' . $this->options->site_id . '.' . $id . '.ZOM" method="post" class="wp_zombaio_form">';

				$html .= '<label for="email">Email: <input type="email" id="email" name="email" /></label>';
				$html .= '<label for="username">Username: <input type="text" id="username" name="username" /></label>';
				$html .= '<label for="password">Password: <input type="password" id="password" name="password" /></label>';

				$html .= '<p>' . $content . '</p>';

				$submit = isset($args['submit']) && $args['submit'] ? $args['submit'] : 'Join';

				$html .= '<input type="submit" name="zomPay" value="' . $submit . '" />';
				$html .= '</form>';
			}
		}

		return $html;
	}
}

$do = isset($_GET['do']) ? $_GET['do'] : FALSE;
if ($do == 'css') {
	header('Content-Type: text/css');
	echo '
.wp_zombaio_form, .wp_zombaio_form label, .wp_zombaio_form p { display: block; clear: both; }
.wp_zombaio_form input { float: right; }
';
	exit;
}

new wp_zombaio();

/**
widget
*/
class wp_zombaio_widget extends wp_widget {
	function wp_zombaio_widget() {
		$this->widgetclassname = 'widget_wp_zombaio_widget';

		$widget_ops = array('classname' => $this->widgetclassname, 'description' => 'Use this widget to add a Join Form to your SideBar');
		$this->WP_Widget($this->widgetclassname, 'WP Zombaio Join Widget', $widget_ops);
		$this->alt_option_name = $this->widgetclassname;

		add_action('save_post', array(&$this, 'flush_widget_cache'));
		add_action('deleted_post', array(&$this, 'flush_widget_cache'));
		add_action('switch_theme', array(&$this, 'flush_widget_cache'));

		parent::__construct(false, 'WP Zombaio Join Widget');
	}

	function widget($args, $instance) {
		if (is_user_logged_in()) {
			return '';
		}
		$cache = wp_cache_get($this->widgetclassname, 'widget');
		if (!is_array($cache)) {
			$cache = array();
		}
		if (!isset($args['widget_id'])) {
			$args['widget_id'] = null;
		}
		if (isset($cache[$args['widget_id']])) {
			echo $cache[$args['widget_id']];
			return;
		}

		ob_start();
		extract($args);
		extract($instance);

		echo $before_widget;
		echo $before_title . $title . $after_title;
		echo do_shortcode('[zombaio_join join_url="' . $join_url . '" submit="' . $submit . '"]' . $message . '[/zombaio_join]');
		echo $after_widget;

		$cache[$args['widget_id']] = ob_get_flush();
		wp_cache_set($this->widgetclassname, $cache, 'widget');
	}

	function update($new_instance, $old_instance) {
		$instance = $old_instance;
		$instance['title'] = $new_instance['title'];
		$instance['join_url'] = $new_instance['join_url'];
		$instance['submit'] = $new_instance['submit'];
		$instance['message'] = $new_instance['message'];
		return $instance;
	}
	function form($instance) {
		$defaults = array(
			'title'		=> 'Join the Site',
			'join_url'	=> '',
			'submit'	=> 'Join',
			'message'	=> '',
		);
		$instance = wp_parse_args((array)$instance, $defaults);

		echo '<label for="' . $this->get_field_id('title') . '">Title: <input type="text" name="' . $this->get_field_name('title') . '" id="' . $this->get_field_id('title') . '" value="' . $instance['title'] . '" /></label>';
		echo '<br />';
		echo '<label for="' . $this->get_field_id('join_url') . '">Join Form URL: <input type="text" name="' . $this->get_field_name('join_url') . '" id="' . $this->get_field_id('join_url') . '" value="' . $instance['join_url'] . '" /></label>';
		echo '<br />';
		echo '<label for="' . $this->get_field_id('message') . '">Intro Text: <input type="text" name="' . $this->get_field_name('message') . '" id="' . $this->get_field_id('message') . '" value="' . $instance['message'] . '" /></label>';
		echo '<br />';
		echo '<label for="' . $this->get_field_id('submit') . '">Submit Button: <input type="text" name="' . $this->get_field_name('submit') . '" id="' . $this->get_field_id('submit') . '" value="' . $instance['submit'] . '" /></label>';
		echo '<br />';
	}

	function flush_widget_cache() {
		wp_cache_delete($this->widgetclassname, 'widget');
	}
}

class wp_zombaio_seal extends wp_widget {
	function wp_zombaio_seal() {
		$this->widgetclassname = 'widget_wp_zombaio_seal';

		$widget_ops = array('classname' => $this->widgetclassname, 'description' => 'Use this widget to add a Zombaio Site Seal to your SideBar');
		$this->WP_Widget($this->widgetclassname, 'WP Zombaio Seal Widget', $widget_ops);
		$this->alt_option_name = $this->widgetclassname;

		add_action('save_post', array(&$this, 'flush_widget_cache'));
		add_action('deleted_post', array(&$this, 'flush_widget_cache'));
		add_action('switch_theme', array(&$this, 'flush_widget_cache'));

		parent::__construct(false, 'WP Zombaio Seal Widget');
	}

	function widget($args, $instance) {
		$cache = wp_cache_get($this->widgetclassname, 'widget');
		if (!is_array($cache)) {
			$cache = array();
		}
		if (!isset($args['widget_id'])) {
			$args['widget_id'] = null;
		}
		if (isset($cache[$args['widget_id']])) {
			echo $cache[$args['widget_id']];
			return;
		}

		ob_start();
		extract($args);
		extract($instance);

		echo $before_widget;
		echo do_shortcode('<br /><center>[zombaio_seal]</center>');
		echo $after_widget;

		$cache[$args['widget_id']] = ob_get_flush();
		wp_cache_set($this->widgetclassname, $cache, 'widget');
	}

	function flush_widget_cache() {
		wp_cache_delete($this->widgetclassname, 'widget');
	}	
}
