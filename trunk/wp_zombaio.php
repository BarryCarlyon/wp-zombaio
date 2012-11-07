<?php

/*
 * Plugin Name: WP Zombaio
 * Plugin URI: http://barrycarlyon.co.uk/wordpress/wordpress-plugins/wp-zombaio/
 * Description: Catches Information from the Adult Payment Gateway Zombaio and acts accordingly
 * Author: Barry Carlyon
 * Version: 1.0.5
 * Author URI: http://barrycarlyon.co.uk/wordpress/
 */

/*
* $Id: wp_zombaio.php 615736 2012-10-22 19:14:33Z BarryCarlyon $
* $Revision: 615736 $
* $Date: 2012-10-22 20:14:33 +0100 (Mon, 22 Oct 2012) $
*/

define('WP_ZOMBAIO_VERSION', '1.0.5');

class wp_zombaio {
	function __construct($proc = FALSE) {
		require_once(plugin_dir_path(__FILE__) . 'wp_zombaio_widgets.php');

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
		$this->form_index = 0;
		$this->frontend();
		return;
	}
	
	private function setup() {
		/**
		Data Codes
		*/
		// user.delete codes
		$this->delete_codes = array(
			'',
			__('Satisfied Customer (just moving on)', 'wp-zombaio'),
			__('Income Issues', 'wp-zombaio'),
			__('Spouse called in about charge', 'wp-zombaio'),
			__('Minor user card', 'wp-zombaio'),
			__('Only interested in trial subscription', 'wp-zombaio'),
			__('Did not read terms and conditions', 'wp-zombaio'),
			__('Not satisfied with content', 'wp-zombaio'),
			__('Not receiving replies from Webmaster', 'wp-zombaio'),
			__('Password problems', 'wp-zombaio'),
			__('Unable to load content fast enough', 'wp-zombaio'),
			__('Other', 'wp-zombaio'),
			__('Rebill Failed', 'wp-zombaio'),
		);
		// rebill success
		$this->rebill_codes = array(
			__('Declined, no more retries', 'wp-zombaio'),//user.delete will be sent
			__('Approved', 'wp-zombaio'),
			__('Declined, retrying in 5 days', 'wp-zombaio'),
		);
		// appendix a
		$this->chargeback_codes = array(
			'30'	=> __('CB - Services/Merchandise Not Received', 'wp-zombaio'),
			'41'	=> __('Cancelled Recurring Transaction', 'wp-zombaio'),
			'53'	=> __('Not as Described or Defective', 'wp-zombaio'),
			'57'	=> __('Fraudulent Multiple Drafts', 'wp-zombaio'),
			'73'	=> __('Expired Card', 'wp-zombaio'),
			'74'	=> __('Late Presentment', 'wp-zombaio'),
			'75'	=> __('Cardholder Does Not Recognize', 'wp-zombaio'),
			'83'	=> __('Fraudulent Transaction - Card Absent Environment', 'wp-zombaio'),
			'85'	=> __('Card Not Processed', 'wp-zombaio'),
			'86'	=> __('Altered Amount/Paid by Other Means', 'wp-zombaio'),
			'93'	=> __('Risk Identification Service', 'wp-zombaio'),
			'101'	=> __('Zombaio - Not as Described or Defective', 'wp-zombaio'),
			'102'	=> __('Zombaio - No access to website (Script problem or Site down)', 'wp-zombaio'),
		);

		$this->chargeback_liability_code = array(
			'',
			__('Merchange is liable for the chargeback', 'wp-zombaio'),
			__('Card Issuer is liable for the chargeback (3D Secure)', 'wp-zombaio'),
			__('Zombaio is liable for the chargeback (Fraud Insurance)', 'wp-zombaio'),
		);
		// decline codes
		// appendix b
		$this->decline_codes = array(
			'B01'	=> __('Declined by Issuing Bank', 'wp-zombaio'),
			'B02'	=> __('Card Expired', 'wp-zombaio'),
			'B03'	=> __('Card Lost of Stolen', 'wp-zombaio'),
			'B04'	=> __('Card on Negative List', 'wp-zombaio'),//international blacklist

			'F01'	=> __('Blocked by Anti Fraud System Level1 - Velocity', 'wp-zombaio'),
			'F02'	=> __('Blocked by Anti Fraud System Level2 - Geo Technology', 'wp-zombaio'),
			'F03'	=> __('Blocked by Anti Fraud System Level3 - Blacklist', 'wp-zombaio'),
			'F04'	=> __('Blocked by Anti Fraud System Level4 - Bayesian probability', 'wp-zombaio'),
			'F05'	=> __('Blocked by Anti Fraud System Level5 - Other', 'wp-zombaio'),

			'H01'	=> __('3D Secure - Failed to Authenticate', 'wp-zombaio'),

			'E01'	=> __('Merchange Account Closed or Suspended', 'wp-zombaio'),
			'E02'	=> __('Routing Error', 'wp-zombaio'),
			'E03'	=> __('General Error', 'wp-zombaio'),
		);
		/**
		End Data
		*/

		$this->init();
		add_action('init', array($this, 'post_types'));
		add_action('plugins_loaded', array($this, 'detect'));
		add_action('widgets_init', array($this, 'widgets_init'));

		add_filter('wp_authenticate_user', array($this, 'wp_authenticate_user'), 10, 2);// check at login
		add_action('init', array($this, 'wp_authenticate_user_check'));// check all the time

		load_plugin_textdomain('wp-zombaio', false, basename(dirname(__FILE__)), '/languages');

		/**
		other common
		*/
		if ($this->options->menus) {
			add_action('after_setup_theme', array($this, 'menus'));
			add_filter('wp_nav_menu_args', array($this, 'menu_select'));
		}

		return;
	}
	public function init($sanity = FALSE) {
		$options = get_option('wp_zombaio', FALSE);
		$save = FALSE;
		if (!$options) {
			$options = $this->sanity_check();
			$this->options = $options;
		} else {
			$test = $this->sanity_check();
			foreach ($test as $item => $value) {
				if (!isset($options->$item)) {
					$save = TRUE;
					$options->$item = $value;
				}
			}
		}
		$this->options = $options;
		if ($save) {
			$this->saveoptions();
		}
		return;
	}
	/**
	sanity check
	*/
	private function sanity_check() {
		$options = new stdClass();

		// base options
		$options->site_id = '';
		$options->gw_pass = '';

		$options->delete = TRUE;// what to do on user.delete true delete false suspend

		$options->wizard = FALSE;// flag for wizard competion

		// logged out redirect
		$options->redirect_target_enable = FALSE;
		$options->redirect_target = '';

		// seal
		$options->seal_code = '';

		// misc
		$options->bypass_ipn_ip_verification = FALSE;
		$options->raw_logs = FALSE;

		// enable/disable plugin powered menu
		$options->menus = FALSE;

		// notifications
		$options->notify_enable = TRUE;
		$options->notify_target = '';

		return $options;
	}
	private function saveoptions() {
		return update_option('wp_zombaio', $this->options);
	}

	/**
	Log post type
	*/
	public function post_types() {
		register_post_type(
			'wp_zombaio',
			array(
				'label'					=> __('Zombaio Log', 'wp-zombaio'),
				'labels'				=> array(
					'name' => __('Zombaio Log', 'wp-zombaio'),
					'singular_name' => __('Zombaio Log', 'wp-zombaio'),
					'add_new' => __('Add New', 'wp-zombaio'),
					'add_new_item' => __('Add New Zombaio Log', 'wp-zombaio'),
					'edit_item' => __('Edit Zombaio Log', 'wp-zombaio'),
					'new_item' => __('New Zombaio Log', 'wp-zombaio'),
					'all_items' => __('All Zombaio Logs', 'wp-zombaio'),
					'view_item' => __('View Zombaio Log', 'wp-zombaio'),
					'search_items' => __('Search Zombaio Logs', 'wp-zombaio'),
					'not_found' =>  __('No Zombaio Logs found', 'wp-zombaio'),
					'not_found_in_trash' => __('No Zombaio Logs found in Trash', 'wp-zombaio'), 
					'parent_item_colon' => '',
					'menu_name' => __('Zombaio Log', 'wp-zombaio')
				),
				'public'				=> ($this->options->raw_logs ? TRUE : FALSE),
				'supports'				=> array(
					'title',
					'editor',
					'custom-fields',
				),
				'has_archive'			=> false,
				'publicly_queryable'	=> false,
				'exclude_from_search'	=> true,
				'can_export'			=> false,
				'menu_icon'				=> plugin_dir_url(__FILE__) . 'img/zombaio_icon.png',
			)
		);

		register_post_status('user_add', array(
			'label' => __('User Add', 'wp-zombaio'),
			'public' => TRUE,
			'exclude_from_search' => TRUE,
			'show_in_admin_all_list' => TRUE,
			'show_in_admin_status_list' => TRUE,
			'label_count' => _n_noop( 'User Add <span class="count">(%s)</span>', 'User Add <span class="count">(%s)</span>', 'wp-zombaio'),
		));
		register_post_status('user_delete', array(
			'label' => __('User Delete', 'wp-zombaio'),
			'public' => FALSE,
			'exclude_from_search' => TRUE,
			'show_in_admin_all_list' => TRUE,
			'show_in_admin_status_list' => TRUE,
			'label_count' => _n_noop( 'User Delete <span class="count">(%s)</span>', 'User Delete <span class="count">(%s)</span>', 'wp-zombaio'),
		));
		register_post_status('user_addcredits', array(
			'label' => __('User Add Credits', 'wp-zombaio'),
			'public' => FALSE,
			'exclude_from_search' => TRUE,
			'show_in_admin_all_list' => TRUE,
			'show_in_admin_status_list' => TRUE,
			'label_count' => _n_noop( 'User Add Credits <span class="count">(%s)</span>', 'User Add Credits <span class="count">(%s)</span>', 'wp-zombaio'),
		));
		register_post_status('rebill', array(
			'label' => __('User Rebill', 'wp-zombaio'),
			'public' => FALSE,
			'exclude_from_search' => TRUE,
			'show_in_admin_all_list' => TRUE,
			'show_in_admin_status_list' => TRUE,
			'label_count' => _n_noop( 'User Rebill <span class="count">(%s)</span>', 'User Rebill <span class="count">(%s)</span>', 'wp-zombaio'),
		));
		register_post_status('chargeback', array(
			'label' => __('Chargeback Report', 'wp-zombaio'),
			'public' => FALSE,
			'exclude_from_search' => TRUE,
			'show_in_admin_all_list' => FALSE,
			'show_in_admin_status_list' => TRUE,
			'label_count' => _n_noop( 'Charge Back Report <span class="count">(%s)</span>', 'Charge Back Report <span class="count">(%s)</span>', 'wp-zombaio'),
		));
		register_post_status('declined', array(
			'label' => __('Card Declined Report', 'wp-zombaio'),
			'public' => FALSE,
			'exclude_from_search' => TRUE,
			'show_in_admin_all_list' => FALSE,
			'show_in_admin_status_list' => TRUE,
			'label_count' => _n_noop( 'Card Declined Report <span class="count">(%s)</span>', 'Card Declined Report <span class="count">(%s)</span>', 'wp-zombaio'),
		));
		register_post_status('credit_spend', array(
			'label' => __('Credits Spent', 'wp-zombaio'),
			'public' => FALSE,
			'exclude_from_search' => TRUE,
			'show_in_admin_all_list' => FALSE,
			'show_in_admin_status_list' => TRUE,
			'label_count' => _n_noop( 'Credits Spent <span class="count">(%s)</span>', 'Credits Spent <span class="count">(%s)</span>', 'wp-zombaio'),
		));
		
		return;
	}

	/**

	Admin Setup

	*/
	private function admin() {
		add_action('admin_notices', array($this, 'admin_notices'));
		add_action('admin_menu', array($this, 'admin_menu'));
		add_action('admin_head', array($this, 'admin_head'));
		add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_scripts'));
		add_action('wp_ajax_wp_zombaio_post_lookup', array($this, 'wp_zombaio_post_lookup'));
		add_action('wp_dashboard_setup', array($this, 'wp_dashboard_setup'));

		//admin profile controller
		add_action('profile_update', array($this, 'profile_update'));
		add_action('show_user_profile', array($this, 'edit_user_profile'));
		add_action('edit_user_profile', array($this, 'edit_user_profile'));

		// users table
		add_filter('manage_users_columns', array($this, 'manage_users_columns'));
		add_filter('manage_users_custom_column', array($this, 'manage_users_custom_column'), 10, 3);

		// posts table
		add_filter('manage_posts_columns', array($this, 'manage_posts_columns'));
		add_action('manage_posts_custom_column', array($this, 'manage_posts_custom_column'), 10, 2);
		add_filter('manage_pages_columns', array($this, 'manage_posts_columns'));
		add_action('manage_pages_custom_column', array($this, 'manage_posts_custom_column'), 10, 2);

		// meta box
		add_action('add_meta_boxes', array($this, 'add_meta_boxes'));
		add_action('save_post', array($this, 'save_post'));
	}
	public function admin_notices() {
		$page = isset($_GET['page']) ? $_GET['page'] : '';
		$do = isset($_REQUEST['do']) ? $_REQUEST['do'] : '';
		if (!$this->options->wizard && $page != 'wp_zombaio' && $do != 'wizard') {
			if (isset($_REQUEST['wp_zombaio']) && $_REQUEST['wp_zombaio'] == 'dismisswizard') {
				$this->options->wizard = TRUE;
				$this->saveoptions();
				return;
			}
			// offer wizard
			$wizard_prompt = '<div id="wp_zombaio_wizard" style="display: block; clear: both; background: #000000; color: #9F9F9F; margin-top: 10px; margin-right: 15px; padding: 12px 10px; font-size: 12px;">';
			$wizard_prompt .= sprintf(__('Run the <a href="%s">WP Zombaio Install Wizard?</a>', 'wp-zombaio'), admin_url('admin.php?page=wp_zombaio&do=wizard'));
			$wizard_prompt .= '<a href="' . admin_url('admin.php?page=wp_zombaio&do=wizarddismiss') . '" style="float: right;">' . __('Dismiss Wizard', 'wp-zombaio') . '</a>';
			$wizard_prompt .= '</div>';
			echo $wizard_prompt;
		}
		if (get_option('users_can_register')) {
			$alert = '<div id="message" class="error">';
			$alert .= __('<p>You have <i>Anyone can register</i> enabled, this means people can join your site without Paying!</p>', 'wp-zombaio');
			$alert .= '</div>';
			echo $alert;
		}
	}

	/**
	User User admin Interface
	profileuser = object
	*/
	function profile_update($user_id) {
		if (current_user_can('edit_users')) {
			if (isset($_POST['wp_zombaio_delete'])) {
				update_user_meta($user_id, 'wp_zombaio_delete', $_POST['wp_zombaio_delete']);
			}
			if (isset($_POST['wp_zombaio_change_credits'])) {
				update_user_meta($user_id, 'wp_zombaio_credits', $_POST['wp_zombaio_change_credits']);
			}
		}
	}
	function edit_user_profile($profileuser) {
		echo '<table class="form-table" style="width: 100%;">';
		echo '<col style="width:125px;"/>';

		if (strlen($sub_id = get_user_meta($profileuser->ID, 'wp_zombaio_subscription_id', TRUE))) {
			echo '<tr>';
			echo '<th style="text-align: left; vertical-align: top;">' . __('Zombaio Subscription ID:', 'wp-zombaio') . '</th>';
			echo '<td style="text-align: left; vertical-align: top;">' . $sub_id . '</td>';
			echo '</tr>';
		}

		if (strlen($credits = get_user_meta($profileuser->ID, 'wp_zombaio_credits', TRUE))) {
			echo '<tr>';
			echo '<th style="text-align: left; vertical-align: top;">' . __('Zombaio Credits Balance:', 'wp-zombaio') . '</th>';
			echo '<td style="text-align: left; vertical-align: top;">' . $credits . '</td>';
			echo '</tr>';
		}

		if (current_user_can('edit_users')) {
			echo '<tr>';
			echo '<th style="text-align: left; vertical-align: top;">' . __('Update Zombaio Credits Balance:', 'wp-zombaio') . '</th>';
			echo '<td style="text-align: left; vertical-align: top;"><input type="text" name="wp_zombaio_change_credits" value="' . $credits . '" /></td>';
			echo '</tr>';

			$suspend = get_user_meta($profileuser->ID, 'wp_zombaio_delete', TRUE);

			echo '<tr>';
			echo '<th style="text-align: left; vertical-align: top;">' . __('Zombaio Suspend Access:', 'wp-zombaio') . '</th>';
			echo '<td style="text-align: left; vertical-align: top;">'
				. '<select name="wp_zombaio_delete">'
				. '<option value="0" ' . ($suspend ? '' : 'selected="selected"') . '>' . __('No', 'wp-zombaio') . '</option>'
				. '<option value="1" ' . ($suspend ? 'selected="selected"' : '') . '>' . __('Yes', 'wp-zombaio') . '</option>'
				. '</select>'
				. '</td>';
			echo '</tr>';
		}

		echo '</table>';
	}
	// Users table
	function manage_users_columns($column) {
		if (current_user_can('edit_users')) {
			$column['wp_zombaio_subscription_id'] = __('Subscription ID', 'wp-zombaio');
			$column['wp_zombaio_credits'] = __('Credits Balance', 'wp-zombaio');
			$column['wp_zombaio_delete'] = __('Blocked', 'wp-zombaio');
		}
		return $column;
	}
	function manage_users_custom_column($value, $column_name, $user_id) {
		if (current_user_can('edit_users')) {
			switch ($column_name) {
				case 'wp_zombaio_subscription_id':
					return get_user_meta($user_id, 'wp_zombaio_subscription_id', TRUE);
				case 'wp_zombaio_credits':
					return get_user_meta($user_id, 'wp_zombaio_credits', TRUE);
				case 'wp_zombaio_delete':
					return get_user_meta($user_id, 'wp_zombaio_delete', TRUE) ? 'Blocked' : '';
			}
		}
		return $value;
	}

	/**
	Post table
	*/
	public function manage_posts_columns($column) {
		$column['purchaseble'] = __('Purchasable', 'wp-zombaio');
		$column['sales'] = __('Sales', 'wp-zombaio');
		return $column;
	}
	public function manage_posts_custom_column($column_name, $post_id) {
		switch ($column_name) {
			case 'purchaseble':
				echo (get_post_meta($post_id, 'wp_zombaio_credit_cost', TRUE) ? __('Yes', 'wp-zombaio') : __('No', 'wp-zombaio'));
				break;
			case 'sales':
				echo 0;
				break;
		}
	}

	/**
	Post meta Boxes
	*/
	public function add_meta_boxes() {
		add_meta_box(
			'wp_zombaio_credit_cost',
			__('WP Zomabio Credit Cost', 'wp-zombaio'),
			array($this, 'wp_zombaio_credit_cost'),
			'',
			'side',
			'high'
		);
	}
	public function save_post($post_id) {
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )
			return;
		if (!$_POST) {
			return;
		}
		if ( !wp_verify_nonce( $_POST['wp_zombaio_postmeta'], plugin_basename( __FILE__ ) ) )
			return;
		if ('page' == $_POST['post_type']) {
			if ( !current_user_can( 'edit_page', $post_id ) )
				return;
		} else {
			if ( !current_user_can( 'edit_post', $post_id ) )
				return;
		}
		// auth ok

		update_post_meta($post_id, 'wp_zombaio_credit_cost', $_POST['wp_zombaio_credit_cost']);
		update_post_meta($post_id, 'wp_zombaio_credit_cost_type', $_POST['wp_zombaio_credit_cost_type']);
		update_post_meta($post_id, 'wp_zombaio_credit_cost_time_qty', $_POST['wp_zombaio_credit_cost_time_qty']);
		update_post_meta($post_id, 'wp_zombaio_credit_cost_time_units', $_POST['wp_zombaio_credit_cost_time_units']);
	}
	public function wp_zombaio_credit_cost($post) {
		wp_nonce_field( plugin_basename( __FILE__ ), 'wp_zombaio_postmeta' );

		if (!strlen($cost = get_post_meta($post->ID, 'wp_zombaio_credit_cost', TRUE))) {
			$cost = 0;
		}
		if (!strlen($type = get_post_meta($post->ID, 'wp_zombaio_credit_cost_type', TRUE))) {
			$type = 'normal';
		}
		if (!strlen($qty = get_post_meta($post->ID, 'wp_zombaio_credit_cost_time_qty', TRUE))) {
			$qty = 1;
		}
		if (!strlen($units = get_post_meta($post->ID, 'wp_zombaio_credit_cost_time_units', TRUE))) {
			$units = 'hours';
		}

		_e('Leave Credit Cost blank for not Purchasable', 'wp-zombaio');

		echo '<table style="display: block;">';

		echo '<tr>'
			. '<th valign="top"><label for="wp_zombaio_credit_cost">' . __('Credit Cost', 'wp_zombaio') . '</label></th>'
			. '<td style="width: 150px; text-align: center;"><input type="text" name="wp_zombaio_credit_cost" id="wp_zombaio_credit_cost" size="4" value="' . $cost . '" /></td>'
			. '</tr>';

		echo '<tr><th colspan="2">'
			. __('Purchase Access Type', 'wp_zombaio')
			. '</th></tr><tr><td style="text-align: center;">'
			. '<label for="wp_zombaio_credit_cost_type_normal">' . __('Normal', 'wp_zombaio') . '</label>'
			. '</td><td style="text-align: center;">'
			. '<input type="radio" name="wp_zombaio_credit_cost_type" id="wp_zombaio_credit_cost_type_normal" class="wp_zombaio_credit_cost_type" value="normal" ' . ($type == 'normal' ? 'checked="checked"' : '') . ' />'
			. '</td></tr><tr><td style="text-align: center;">'
			. '<label for="wp_zombaio_credit_cost_type_timed">' . __('Timed', 'wp_zombaio') . '</label>'
			. '</td><td style="text-align: center;">'
			. '<input type="radio" name="wp_zombaio_credit_cost_type" id="wp_zombaio_credit_cost_type_timed" class="wp_zombaio_credit_cost_type" value="timed" ' . ($type == 'timed' ? 'checked="checked"' : '') . ' />'
			. '</td></tr>';

		echo '<tr id="wp_zombaio_credit_cost_type_timed_select" ' . ($type == 'timed' ? '' : 'style="display: none;"') . '><th>'
			. __('Allow Access for', 'wp_zombaio_credits')
			. '</th><td style="text-align: center;">'
			. '<input type="text" name="wp_zombaio_credit_cost_time_qty" size="2" value="' . $qty . '" />'
			. '<br />'
			. '<select name="wp_zombaio_credit_cost_time_units">'
			. '<option value="hours" ' . ($units == 'hours' ? 'selected="selected"' : '') . ' >' . __('Hours', 'wp-zombaio') . '</option>'
			. '<option value="days" ' . ($units == 'days' ? 'selected="selected"' : '') . ' >' . __('Days', 'wp-zombaio') . '</option>'
			. '<option value="weeks" ' . ($units == 'weeks' ? 'selected="selected"' : '') . ' >' . __('Weeks', 'wp-zombaio') . '</option>'
			. '</select>'
			. '</td></tr>';

		echo '</table>';

		echo '
<script type="text/javascript">
jQuery(document).ready(function() {
	jQuery(\'.wp_zombaio_credit_cost_type\').change(function() {
		jQuery(\'#wp_zombaio_credit_cost_type_timed_select\').hide();
		if (jQuery(this).val() == \'timed\') {
			jQuery(\'#wp_zombaio_credit_cost_type_timed_select\').show();
		}
	});
});
</script>
';
	}

	/**
	User admin interface
	*/
	public function admin_menu() {
		if (!$this->options->wizard) {
			add_menu_page('WP Zombaio', 'WP Zombaio', 'activate_plugins', 'wp_zombaio', array($this, 'admin_page'), plugin_dir_url(__FILE__) . 'img/zombaio_icon.png');
			add_submenu_page('wp_zombaio', __('Guide', 'wp-zombaio'), __('Guide', 'wp-zombaio'), 'activate_plugins', 'wp_zombaio_guide', array($this, 'admin_page_guide'));
			add_submenu_page('wp_zombaio', __('Logs', 'wp-zombaio'), __('Logs', 'wp-zombaio'), 'activate_plugins', 'wp_zombaio_logs', array($this, 'admin_page_logs'));
		} else {
			add_menu_page('WP Zombaio', 'WP Zombaio', 'activate_plugins', 'wp_zombaio_logs', array($this, 'admin_page_logs'), plugin_dir_url(__FILE__) . 'img/zombaio_icon.png');
			add_submenu_page('wp_zombaio_logs', __('Guide', 'wp-zombaio'), __('Guide', 'wp-zombaio'), 'activate_plugins', 'wp_zombaio_guide', array($this, 'admin_page_guide'));
			add_submenu_page('wp_zombaio_logs', __('Settings', 'wp-zombaio'), __('Settings', 'wp-zombaio'), 'activate_plugins', 'wp_zombaio', array($this, 'admin_page'));
		}
	}

	// Seems silly to generate a whole file just for a single line of js....
	public function admin_head() {
		if (isset($_GET['page']) && substr($_GET['page'], 0, 10) == 'wp_zombaio') {
			echo '
<script type="text/javascript">
jQuery(document).ready(function() {
	jQuery(\'#redirect_target\').suggest(ajaxurl + \'?action=wp_zombaio_post_lookup\')
});
</script>
';
		}
	}
	public function admin_enqueue_scripts() {
		wp_enqueue_script('google-chart-api', 'https://www.google.com/jsapi');
		if (isset($_GET['page']) && substr($_GET['page'], 0, 10) == 'wp_zombaio') {
			wp_enqueue_script('jquery-ui-dialog');
			wp_enqueue_script('jquery-ui-tabs');
			wp_enqueue_style('jquery-ui-css', 'http://jquery-ui.googlecode.com/svn/tags/latest/themes/base/jquery.ui.all.css');
			wp_enqueue_script('suggest');
			wp_enqueue_style('wp-zombaio-admin', plugin_dir_url(__FILE__) . 'wp_zombaio_admin.css', array(), WP_ZOMBAIO_VERSION);
		}
	}

	/**
	admin with priv ajax post lookup
	*/
	public function wp_zombaio_post_lookup() {
		global $wpdb;

		$search = like_escape($_REQUEST['q']);

		$query = 'SELECT ID,post_title FROM ' . $wpdb->posts . '
			WHERE post_title LIKE \'' . $search . '%\'
			AND post_type = \'page\'
			AND post_status = \'publish\'
			ORDER BY post_title ASC';
		foreach ($wpdb->get_results($query) as $row) {
			$post_title = $row->post_title;
			$id = $row->ID;

			echo $id . '--' . $post_title . "\n";
		}
		die();
	}

	/**
	Utilty admin
	*/
	protected function admin_page_top($title = 'WP Zombaio', $logo = TRUE) {
		echo '<div class="wrap" id="wp_zombaio">';

		echo '<div class="wp_zombaio_admin">';
		if ($logo) {
			echo '<img src="' . plugin_dir_url(__FILE__) . 'img/zombaio-logo.png" alt="' . __('Zomabio Logo', 'wp-zombaio') . '" id="wp_zombaio_logo" />';
		}

		echo '<h2>' . $title . '</h2>';
		echo '<div class="wp_zombaio_inner">';
	}
	protected function admin_page_bottom() {
		echo '</div></div></div>';
	}
	protected function admin_page_spacer($title = '') {
		echo '</div></div>';
		echo '<br />';
		echo '<div class="wp_zombaio_admin">';
		echo '<h2>';
		if ($title) {
			echo $title;
		}
		echo '&nbsp;</h2>';
		echo '<div class="wp_zombaio_inner">';
	}

	/**

	Main Settings Page

	*/
	public function admin_page() {
		$this->admin_page_top();

		$do = isset($_REQUEST['do']) ? $_REQUEST['do'] : FALSE;
		$step = $nextstep = FALSE;

		if ($do == 'wizard') {
			$this->options->wizard = false;
			$this->saveoptions();
		}

		if ($this->options->wizard) {
			echo '<form action="' . admin_url('admin.php?page=wp_zombaio&do=wizard') . '" method="post" style="float: right; clear: right;">
				<p class="submit"><input type="submit" value="' . __('Run Wizard Again', 'wp-zombaio') . '" class="button-secondary" /></p>
			</form>';
		}

		echo '<form method="post" action="' . admin_url('admin.php?page=wp_zombaio') . '">';

		if ($do == 'wizard') {
			$step = isset($_REQUEST['step']) ? $_REQUEST['step'] : 0;
			$nextstep = $step;
			switch ($step) {
				case '3':
					$this->options->wizard = TRUE;
					$this->saveoptions();
					echo '<div id="message" class="updated"><p>' . __('All Done, you are ready to go', 'wp-zombaio') . '</p></div>';
					echo __('<p>You can now review the current options and change advanced options</p>', 'wp-zombaio');
					$do = FALSE;
					break;
				case '2':
					$gw_pass = isset($_REQUEST['gw_pass']) ? $_REQUEST['gw_pass'] : FALSE;
					if (!$gw_pass) {
						echo '<div id="message" class="error"><p>' . __('You Need to enter your Zombaio GW Pass', 'wp-zombaio') . '</p></div>';
					} else {
						$this->options->gw_pass = $gw_pass;
						$this->saveoptions();

						echo __('<p>Now the final step</p><p>Update the <strong>Postback URL (ZScript)</strong> to the following:</p>', 'wp-zombaio');
						echo '<input type="text" name="postbackurl" value="' . site_url() . '" />';
						echo sprintf(__('<p>Then Press Validate</p>'
							. '<p>Zombaio will then Validate the Settings, and if everything is correct, should say Successful and save the URL</p>'
							. '<p>If not, please <a href="%s">Click Here</a> and we will restart the Wizard to confirm your settings</p>'
							. '<p>If everything worked, just hit Submit below</p>', 'wp-zombaio'), admin_url('admin.php?page=wp_zombaio&do=wizard'));

						$nextstep = 3;
						break;
					}
				case '1':
					if ($step == 1) {
						$site_id = isset($_REQUEST['site_id']) ? $_REQUEST['site_id'] : FALSE;
						if (!$site_id) {
							echo '<div id="message" class="error"><p>' . __('You Need to enter your Site ID', 'wp-zombaio') . '</p></div>';
						} else {
							$this->options->site_id = $site_id;
							$this->saveoptions();

							echo __('<p>Next we need to setup the Zombaio -&gt; Communications</p>'
								. '<p>In Website Management, select Settings</p>'
								. '<p>Copy and Enter the <strong>Zombaio GW Pass</strong> below</p>', 'wp-zombaio');
							echo '<label for="gw_pass">' . __('Zombaio GW Pass:', 'wp-zombaio') . ' <input type="text" name="gw_pass" id="gw_pass" value="' . $this->options->gw_pass . '" /></label>';
							$nextstep = 2;
							break;
						}
					}
				case '0':
				default:
					echo __('<p>This Wizard will Guide you thru the Zombaio Setup</p>'
						. '<p>First your will need a Zombaio Account</p>'
						. '<p>And to have added your Website under Website Management</p>'
						. '<p>This will give you a <strong>Site ID</strong>, enter that now:</p>', 'wp-zombaio');
					echo '<label for="site_id">' . __('Site ID:', 'wp-zombaio') . ' <input type="text" name="site_id" id="site_id" value="' . $this->options->site_id . '" /></label>';
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
				echo '<div id="message" class="updated"><p>' . __('Settings Updated', 'wp-zombaio') , '</p></div>';
			}

			echo '<p>' . __('For Reference, your Zombaio Postback URL (ZScript) should be set to', 'wp-zombaio') . ' <input type="text" name="postbackurl" value="' . site_url() . '" /></p>';
			echo '<table>';

			echo '<tr><td style="width: 200px;"></td><td><h3>' . __('Standard Settings', 'wp-zombaio') . '</h3></td></tr>';
			echo '<tr><th valign="top"><label for="site_id">' . __('Site ID:', 'wp-zombaio') . '</label></th><td><input type="text" name="site_id" id="site_id" value="' . $this->options->site_id . '" /></label></td></tr>';
			echo '<tr><th valign="top"><label for="gw_pass">' . __('Zombaio GW Pass', 'wp-zombaio') . '</label></th><td><input type="text" name="gw_pass" id="gw_pass" value="' . $this->options->gw_pass . '" /></label></td></tr>';

			/**
			Advanced
			*/

			echo '<tr><td></td><td><h3>' . __('Advanced Settings', 'wp-zombaio') . '</h3></td></tr>';
			echo '<tr><th valign="top">' . __('Delete Action', 'wp-zombaio') . '</th><td valign="top">';
				echo '<select name="delete">
					<option value="1" ' . ($this->options->delete ? 'selected="selected"' : '') . '>' . __('Delete User Account', 'wp-zombaio') . '</option>
					<option value="0" ' . ($this->options->delete ? '' : 'selected="selected"') . '>' . __('Block User Access', 'wp-zombaio') . '</option>
				</select>
				<h4>' . __('What to do when Zombaio Calls User Delete', 'wp-zombaio') . '</h4>
				</td></tr>';
			echo '<tr><th valign="top">' . __('Bypass IPN Verfication', 'wp-zombaio') . '</th><td valign="top">';
				echo '<select name="bypass_ipn_ip_verification">
					<option value="0" ' . ($this->options->bypass_ipn_ip_verification ? '' : 'selected="selected"') . '>' . __('No', 'wp-zombaio') . '</option>
					<option value="1" ' . ($this->options->bypass_ipn_ip_verification ? 'selected="selected"' : '') . '>' . __('Yes', 'wp-zombaio') . '</option>
				</select>
				<h4>' . __('We Validate the Zombaio IP against a Known list, You can bypass this if needed', 'wp-zombaio') . '</h4>
				</td></tr>';

			/**
			Notification
			*/
			echo '<tr><td></td><td><h3>' . __('Notification Preferences', 'wp-zombaio') . '</h3></tr></tr>';
			echo '<tr><th valign="top">' . __('Enabled', 'wp-zombaio') . '</th><td valign="top">';
				echo '<select name="notify_enable">
					<option value="1" ' . ($this->options->notify_enable ? 'selected="selected"' : '') . '>' . __('Enabled', 'wp-zombaio') . '</option>
					<option value="0" ' . ($this->options->notify_enable ? '' : 'selected="selected"') . '>' . __('Disabled', 'wp-zombaio') . '</option>
				</select></td></tr>';
			echo '<tr><th valign="top"><label for="notify_target">' . __('Email Address:', 'wp-zombaio') . '</label></th><td><input type="text" name="notify_target" id="notify_target" value="' . $this->options->notify_target . '" /></label>
				<h4>' . sprintf(__('By default we Notify the Admin Email <i>%s</i> you can change that above or leave blank to send to the default', 'wp-zombaio'), get_option('admin_email')) . '</h4>
			</td></tr>';

			/**
			Login Block
			*/
			echo '<tr><td></td><td><h3>' . __('Login Control', 'wp-zombaio') . '</h3></td></tr>';
			echo '<tr><td></td>
				<td>' . __('In order to Protect your Membership Site, we need to Force Users to Login, (or register)', 'wp-zombaio') . '</td></tr>
			<tr><th valign="top"><label for="redirect_target_enable">' . __('Enable Login Required', 'wp-zombaio') . '</label></th>
				<td><select name="redirect_target_enable">
					<option value="1" ' . ($this->options->redirect_target_enable ? 'selected="selected"' : '') . '>' . __('On', 'wp-zombaio') . '</option>
					<option value="0" ' . ($this->options->redirect_target_enable ? '' : 'selected="selected"') . '>' . __('Off', 'wp-zombaio') . '</option>
				</select></td></tr>
			<tr><th valign="top"><label for="redirect_target">' . __('Redirect Target - Page Title', 'wp-zombaio') . '</label></th>
				<td>' . __('Where shall we send them? (Leave blank for the default WP Login)', 'wp-zombaio') , '<br />
					<input type="text" name="redirect_target" id="redirect_target" value="' . $this->options->redirect_target . '" /><br />'
					. __('Start Typing the Page Title and we will find it, we will add the Post ID to the start so ignore the number', 'wp-zombaio')
					. sprintf(__('<p>See the <a href="%s">Guide</a> on usage</p>', 'wp-zombaio'), '?page=wp_zombaio_guide')
					. '</td></tr>
			';

			/**
			Extras
			*/

			echo '<tr><td></td><td><h3>' . __('Extras', 'wp-zombaio') . '</h3></td></tr>';
			echo '<tr><th valign="top"><label for="seal_code">' . __('Seal Code:', 'wp-zombaio') . '</label></th>
				<td><textarea name="seal_code" id="seal_code" style="width: 500px;" rows="10">' . $this->options->seal_code . '</textarea>
				<h4>' . sprintf(__('This field&#39;s contents are shown when using the [zombaio_seal] shortcode, or its widget</h4><p>See the <a href="%s">Guide</a> on where to get your Seal', 'wp-zombaio'), '?page=wp_zombaio_guide') . '</td></tr>';

			echo '<tr><th valign="top"><label for="raw_logs">' . __('Raw Logs/Editor', 'wp-zombaio') . '</label></th>
				<td><select name="raw_logs">
					<option value="1" ' . ($this->options->raw_logs ? 'selected="selected"' : '') . '>' . __('On', 'wp-zombaio') . '</option>
					<option value="0" ' . ($this->options->raw_logs ? '' : 'selected="selected"') . '>' . __('Off', 'wp-zombaio') . '</option>
				</select></td></tr>';

			echo '<tr><th valign="top"><label for="menus">' . __('Use Plugin Logged In/Out Menus', 'wp-zombaio') . '</label></th>
				<td><select name="menus">
					<option value="1" ' . ($this->options->menus ? 'selected="selected"' : '') . '>' . __('On', 'wp-zombaio') . '</option>
					<option value="0" ' . ($this->options->menus ? '' : 'selected="selected"') . '>' . __('Off', 'wp-zombaio') . '</option>
				</select>';

			if ($this->options->menus) {
				echo '<p>' . sprintf(__('You can configure Menus under Appearance or <a href="%s">here</a>', 'wp-zombaio'), admin_url('nav-menus.php')) . '</p>';
			}

			echo '</td></tr>';

			echo '</table>';
		}

		echo '<p class="submit"><input type="submit" class="button-primary" value="' . __('Submit', 'wp-zombaio') . '" /></p>';

		echo '</form>';

		$this->admin_page_bottom();
	}

	/**

	Logs Page

	*/
	public function admin_page_logs() {
		$this->admin_page_top(__('Transaction Logs', 'wp-zombaio'), FALSE);

		$states = array(
			'user_add'			=> __('User Add', 'wp-zombaio'),
			'user_delete'		=> __('User Delete', 'wp-zombaio'),
			'user_addcredits'	=> __('User Add Credits', 'wp-zombaio'),
			'rebill'			=> __('Rebill', 'wp-zombaio'),
			'chargeback'		=> __('Chargeback', 'wp-zombaio'),
			'declined'			=> __('Declined', 'wp-zombaio'),
			'credit_spend'		=> __('Credits Spent', 'wp-zombaio'),
		);

		echo '<div id="wp_zombaio_tabs">';
		echo '<ul>';
		$totals = array();
		foreach ($states as $state => $translation) {
			$count = count(get_posts(array('post_type' => 'wp_zombaio', 'post_status' => $state, 'numberposts' => -1)));
			$totals[$state] = $count;
			echo '<li><a href="#wp_zombaio_' . $state . '">' . $translation . ' (' . $count . ')</a></li>';
		}
		echo '</ul>';

		$limit = 20;

		foreach ($states as $state => $translation) {
			$offset = (isset($_REQUEST['wp_zombaio_' . $state . '_offset']) ? $_REQUEST['wp_zombaio_' . $state . '_offset'] : 0);

			echo '<div id="wp_zombaio_' . $state . '" style="height: 500px; overflow: auto;">';
			$posts = get_posts(array(
				'post_type' => 'wp_zombaio',
				'post_status' => $state,
				'numberposts'	=> $limit,
				'offset'		=> $offset,
			));
			echo '<p style="text-align: center; display: block;">' . __('Click a row to view the Full Log', 'wp-zombaio') , '</p>';
			echo '<table id="wp_zombaio_transaction_logs">';
			echo '
			<thead>
			<tr>
				<th>' . __('Log ID', 'wp-zombaio') , '</th>
				';

				switch ($state) {
					case 'user_add':
						echo '<th>' . __('User', 'wp-zombaio') , '</th>';
						echo '<th>' . __('Amount', 'wp-zombaio') , '</th>';
						echo '<th>' . __('Transaction ID', 'wp-zombaio') , '</th>';
						echo '<th>' . __('Subscription ID', 'wp-zombaio') , '</th>';
						echo '<th>' . __('Pricing ID', 'wp-zombaio') , '</th>';
						break;
					case 'user_delete':
						echo '<th>' . __('User', 'wp-zombaio') , '</th>';
						echo '<th>' . __('Delete Reason', 'wp-zombaio') . '</th>';
						break;
					case 'user_addcredits':
						echo '<th>' . __('User', 'wp-zombaio') , '</th>';
						echo '<th>' . __('Credits', 'wp-zombaio') . '</th>';
						echo '<th>' . __('Amount', 'wp-zombaio') , '</th>';
						echo '<th>' . __('Transaction ID', 'wp-zombaio') , '</th>';
						echo '<th>' . __('Pricing ID', 'wp-zombaio') , '</th>';
						break;
					case 'rebill':
						echo '<th>' . __('User', 'wp-zombaio') , '</th>';
						echo '<th>' . __('Amount', 'wp-zombaio') , '</th>';
						echo '<th>' . __('Transaction ID', 'wp-zombaio') , '</th>';
						echo '<th>' . __('Subscription ID', 'wp-zombaio') , '</th>';
						echo '<th>' . __('Status', 'wp-zombaio') , '</th>';
						echo '<th>' . __('Retry', 'wp-zombaio') , '</th>';
						break;
					case 'chargeback':
						echo '<th>' . __('User', 'wp-zombaio') . '</th>';
						echo '<th>' . __('Amount', 'wp-zombaio') . '</th>';
						echo '<th>' . __('Reason', 'wp-zombaio') . '</th>';
						echo '<th>' . __('Liability', 'wp-zombaio') . '</th>';						
					case 'declined':
						echo '<th>' . __('Amount', 'wp-zombaio') , '</th>';
						echo '<th>' . __('Transaction ID', 'wp-zombaio') , '</th>';
						echo '<th>' . __('Reason', 'wp-zombaio') , '</th>';
						break;
					case 'credit_spend':
						echo '<th>' . __('Post', 'wp-zombaio') . '</th>';
						echo '<th>' . __('Credits Spent', 'wp-zombaio') . '</th>';
						break;
				}

				echo '<th>' . __('Log Time', 'wp-zombaio') , '</th>';

				echo '
			</tr>
			</thead>
			<tbody>';
			foreach ($posts as $post) {
				echo '<tr class="renderRawLog">';
				echo '<td>' . $post->ID . '</td>';

				$json = get_post_meta($post->ID, 'wp_zombaio_json_packet', TRUE);
				$json = json_decode($json);

				echo '<td class="talignleft">';
				switch ($state) {
					case 'user_add':
						if ($user_id = get_post_meta($post->ID, 'wp_zombaio_user_id', TRUE)) {
							$user = get_user_by('id', $user_id);
							if (!$user) {
								echo __('Unreg: ', 'wp-zombaio') . ' ' . $json->username;
							} else {
								echo '(' . $user_id . ') ' . $user->display_name . '<br />' . $user->user_email;
							}
						} else {
							echo __('Unreg: ', 'wp-zombaio') . ' ' . $json->username;
						}
						echo '</td>';
						echo '<td>';
						if ($amount = get_post_meta($post->ID, 'wp_zombaio_amount', TRUE)) {
							echo $json->Amount_Currency . ' ' . $amount;
						} else {
							echo $json->Amount_Currency . ' ' . $json->Amount;
							echo ' - ' . __('Failed', 'wp-zombaio');
						}
						echo '</td>';
						echo '<td>' . $json->TRANSACTION_ID . '</td>';
						echo '<td>' . $json->SUBSCRIPTION_ID . '</td>';
						echo '<td>' . $json->PRICING_ID . '</td>';
						break;
					case 'user_delete':
						if ($user_id = get_post_meta($post->ID, 'wp_zombaio_user_id', TRUE)) {
							$user = get_user_by('id', $user_id);
							if (!$user) {
								echo __('Unreg: ', 'wp-zombaio') . ' ' . $json->username;
							} else {
								echo '(' . $user_id . ') ' . $user->display_name . '<br />' . $user->user_email;
							}
						} else {
							echo __('Unreg: ', 'wp-zombaio') . ' ' . $json->username;
						}
						echo '</td>';
						echo '<td>' . (isset($this->delete_codes[$json->ReasonCode]) ? $json->ReasonCode . ' - ' . $this->delete_codes[$json->ReasonCode] : 'Unknown') . '</td>';
						break;
					case 'user_addcredits':
						if ($user_id = get_post_meta($post->ID, 'wp_zombaio_user_id', TRUE)) {
							$user = get_user_by('id', $user_id);
							if (!$user) {
								echo __('Unreg: ', 'wp-zombaio') . ' ' . $user->user_login;
							} else {
								echo '(' . $user_id . ') ' . $user->display_name . '<br />' . $user->user_email;
							}
						} else {
							echo __('Unreg: ', 'wp-zombaio') . ' ' . $json->EMAIL;
						}
						echo '</td>';
						echo '<td>' . $json->Credits . '</td>';
						echo '<td>';
						if ($amount = get_post_meta($post->ID, 'wp_zombaio_amount', TRUE)) {
							echo $json->Amount_Currency . ' ' . $amount;
						} else {
							echo $json->Amount_Currency . ' ' . $json->Amount;
							echo ' - ' . __('Failed', 'wp-zombaio');
						}
						echo '</td>';
						echo '<td>' . $json->TransactionID . '</td>';
						echo '<td>' . $json->PRICING_ID . '</td>';
						break;
					case 'rebill':
						if ($user_id = get_post_meta($post->ID, 'wp_zombaio_user_id', TRUE)) {
							$user = get_user_by('id', $user_id);
							if (!$user) {
								echo __('Unreg: ', 'wp-zombaio') . ' ' . $json->username;
							} else {
								echo '(' . $user_id . ') ' . $user->display_name . '<br />' . $user->user_email;
							}
						} else {
							echo __('Unreg: ', 'wp-zombaio') . ' ' . $json->username;
						}
						echo '</td>';
						echo '<td>';
						if ($amount = get_post_meta($post->ID, 'wp_zombaio_amount', TRUE)) {
							echo $json->Amount_Currency . ' ' . $amount;
						} else {
							echo $json->Amount_Currency . ' ' . $json->Amount;
							echo ' - ' . __('Failed', 'wp-zombaio');
						}
						echo '</td>';
						echo '<td>' . $json->TRANSACTION_ID . '</td>';
						echo '<td>' . $json->SUBSCRIPTION_ID . '</td>';
						echo '<td>' . $json->Success . '</td>';
						echo '<td>' . $json->Retries . '</td>';
						break;
					case 'chargeback':
						if ($user_id = get_post_meta($post->ID, 'wp_zombaio_user_id', TRUE)) {
							$user = get_user_by('id', $user_id);
							if (!$user) {
								echo __('Unreg: ', 'wp-zombaio') . ' ' . $json->username;
							} else {
								echo '(' . $user_id . ') ' . $user->display_name . '<br />' . $user->user_email;
							}
						} else {
							echo __('Unreg: ', 'wp-zombaio') . ' ' . $json->username;
						}
						echo '</td>';
						echo '<td>';
						if ($amount = get_post_meta($post->ID, 'wp_zombaio_amount', TRUE)) {
							echo $json->Amount_Currency . ' ' . $amount;
						} else {
							echo $json->Amount_Currency . ' ' . $json->Amount;
							echo ' - ' . __('Failed', 'wp-zombaio');
						}
						echo '</td>';
						echo '<td>' . (isset($this->chargeback_codes[$json->ReasonCode]) ? $json->ReasonCode . ' - ' . $this->chargeback_codes[$json->ReasonCode] : 'Unknown') . '</td>';
						echo '<td>' . (isset($this->chargeback_liability_code[$json->LiabilityCode]) ? $json->LiabilityCode . ' - ' . $this->chargeback_liability_code[$json->LiabilityCode] : 'Unknown') . '</td>';
						break;
					case 'declined':
						if ($amount = get_post_meta($post->ID, 'wp_zombaio_amount', TRUE)) {
							echo $json->Amount_Currency . ' ' . $amount;
						} else {
							echo $json->Amount_Currency . ' ' . $json->Amount;
							echo ' - ' . __('Failed', 'wp-zombaio');
						}
						echo '</td>';
						echo '<td>' . $json->TRANSACTION_ID . '</td>';
						echo '<td>' . (isset($this->decline_codes[$json->ReasonCode]) ? $json->ReasonCode . ' - ' . $this->decline_codes[$json->ReasonCode] : 'Unknown') . '</td>';
						break;
					case 'credit_spend':
						$postdata = get_post($json->post_id);
						echo '(' . $json->post_id . ') ' . $postdata->post_title . '</td>';
						echo '<td>' . $json->credits . '</td>';
						break;
				}

				echo '<td>' . $post->post_date . '</td>';

				echo '<td class="renderRawLogData" style="display: none;">' . get_post_meta($post->ID, 'wp_zombaio_logmessage', TRUE) . '<br /><br /><textarea style="width: 400px;" rows="30" readonly="readonly">' . $post->post_content . '</textarea></td>';

				echo '</tr>';
			}
			echo '</tbody><tfoot>';

			// pagination
			echo '<tr><td colspan="20" style="text-align: center;">';
			if ($offset >= $limit) {
				echo '<a href="?page=wp_zombaio_logs&wp_zombaio_' . $state . '_offset=' . ($offset - $limit) . '#wp_zombaio_' . $state . '" class="alignleft">' . __('Back', 'wp-zombaio') , '</a>';
			}
			echo (($offset / $limit) + 1) . '/' . ceil($totals[$state] / $limit);
			if (count($posts) == $limit) {
				echo '<a href="?page=wp_zombaio_logs&wp_zombaio_' . $state . '_offset=' . ($offset + $limit) . '#wp_zombaio_' . $state . '" class="alignright">' . __('Forward', 'wp-zombaio') , '</a>';
			}
			echo '</td></tr>';
			// end
			echo '</tfoot></table>';

			echo '</div>';
		}
		echo '</div>';

		echo '
<script type="text/javascript">
jQuery(document).ready(function() {
	jQuery(\'#wp_zombaio_tabs\').tabs();
	jQuery(\'.renderRawLog\').click(function() {
		jQuery(this).find(\'.renderRawLogData\').clone().dialog({width: 440, modal: true});
	});
});
</script>
';

		$this->admin_page_bottom();
	}

	/**
	Guide Page
	*/
	function admin_page_guide() {
		require_once(plugin_dir_path(__FILE__) . 'wp_zombaio_guide.php');
		new wp_zombaio_guide();
	}

	/**
	Payment Processor
	*/
	public function detect() {
		if (isset($_GET['wp_zombaio_ips']) && $_GET['wp_zombaio_ips'] == 1) {
			$ips = $this->load_ipn_ips();
			if (isset($_GET['csv']) && $_GET['csv'] == 1) {
				echo '<textarea style="width: 270px;" rows="10" readonly="readonly">' . implode(',', $ips) . '</textarea>';
				exit;
			}
			echo '<ul>';
			foreach ($ips as $ip) {
				echo '<li><input type="text" readonly="readonly" value="' . $ip . '" size="15" /></li>';
			}
			echo '</ul>';
			exit;
		}
		$wp_zombaio = new wp_zombaio(TRUE);
		$wp_zombaio->process();
	}

	private function process() {
		$this->init();

		$gw_pass = isset($_GET['ZombaioGWPass']) ? $_GET['ZombaioGWPass'] : FALSE;
		if (!$gw_pass) {
			return;
		}
		if ($gw_pass != $this->options->gw_pass) {
			header('HTTP/1.0 401 Unauthorized');
			echo '<h1>Zombaio Gateway 1.1</h1><h3>Authentication failed. GW Pass</h3>';
			exit;
		}

		if (!$this->verify_ipn_ip()) {
			header('HTTP/1.0 403 Forbidden');
			echo '<h1>Zombaio Gateway 1.1</h1><h3>Authentication failed, you are not Zombaio.</h3>';
			exit;
		}

		$username = isset($_GET['username']) ? $_GET['username'] : FALSE;

		// verify site ID
		$site_id = isset($_GET['SITE_ID']) ? $_GET['SITE_ID'] : (isset($_GET['SiteID']) ? $_GET['SiteID'] : FALSE);
		if (!$site_id || $site_id != $this->options->site_id) {
			if (substr($username, 0, 4) == 'Test') {
				// test mode
				header('HTTP/1.1 200 OK');
				echo 'OK';
				exit;
			}
			header('HTTP/1.0 401 Unauthorized');
			echo '<h1>Zombaio Gateway 1.1</h1><h3>Authentication failed. Site ID MisMatch</h3>';
			exit;
		}
		
		$action = isset($_GET['Action']) ? $_GET['Action'] : FALSE;
		if (!$action) {
			header('HTTP/1.0 401 Unauthorized');
			echo '<h1>Zombaio Gateway 1.1</h1><h3>Authentication failed. No Action</h3>';
			exit;
		}
		
		$logid = $this->log();
		$logmsg = '';
		
		$action = strtolower($action);
		switch ($action) {
			case 'user.add': {
				$subscription_id = isset($_GET['SUBSCRIPTION_ID']) ? $_GET['SUBSCRIPTION_ID'] : FALSE;
				if (!$subscription_id) {
					header('HTTP/1.0 401 Unauthorized');
					echo '<h1>Zombaio Gateway 1.1</h1><h3>Authentication failed. No Sub</h3>';
					exit;
				}
				
				$email = $_GET['EMAIL'];
				$fname = $_GET['FIRSTNAME'];
				$lname = $_GET['LASTNAME'];
				$password = $_GET['password'];
				
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
					echo 'ERROR';
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
					// could test for deleted and return ERROR if needed
					$logmsg = 'User was deleted';
				} else {
					update_user_meta($user->ID, 'wp_zombaio_delete', TRUE);
					$logmsg ='User was suspended';
				}
				break;
			}
			case 'rebill': {
				$subscription_id = isset($_GET['SUBSCRIPTION_ID']) ? $_GET['SUBSCRIPTION_ID'] : FALSE;
				if (!$subscription_id) {
					header('HTTP/1.0 401 Unauthorized');
					echo '<h1>Zombaio Gateway 1.1</h1><h3>Authentication failed. Rebill No SUB ID</h3>';
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
					$success = ym_GET('Success', 0);
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
			case 'chargeback': {
				$logmsg = 'A Chargeback Occured';
				break;
			}
			case 'declined': {
				$subscription_id = isset($_GET['SUBSCRIPTION_ID']) ? $_GET['SUBSCRIPTION_ID'] : FALSE;
				if ($subscription_id) {
					//get user ID by subscription ID
					global $wpdb;
					$query = 'SELECT user_id FROM ' . $wpdb->usermeta . ' WHERE meta_key = \'wp_zombaio_subscription_id\' AND meta_value = \'' . $subscription_id . '\'';

					$user_id = $wpdb->get_var($query);
					// should fire a user.delete after true fail
					$logmsg = 'User Card Rebill was Declined';
				} else {
					$user_id = '';
					$logmsg = 'User Card was Declined';
				}
				break;
			}
			case 'user.addcredits': {
				// dont match on email Identifier is more useful
				// sicne a differnet persons details could be used to buy credits for the user in the Identifier
				$id = isset($_GET['Identifier']) ? $_GET['Identifier'] : FALSE;
				$credits_purchased = isset($_GET['Credits']) ? $_GET['Credits'] : FALSE;
				if ($id && $credits_purchased) {
					$user = get_user_by('id', $id);
					if ($user) {
						// validate hash
						$myhash = md5($id . $this->options->gw_pass . $credits_purchased . $this->options->site_id);
						$theirhash = isset($_GET['Hash']) ? $_GET['Hash'] : FALSE;
						if ($myhash == $theirhash) {
							$user_id = $id;

							// get current add add away
							$credits = get_user_meta($user_id, 'wp_zombaio_credits', TRUE);
							if (!$credits) {
								$credits = 0;
							}
							$credits += $credits_purchased;

							// update
							update_user_meta($user_id, 'wp_zombaio_credits', $credits);
							break;
						} else {
							header('HTTP/1.0 401 Unauthorized');
							echo 'ERROR';
							exit;
						}
					} else {
						header('HTTP/1.0 401 Unauthorized');
						echo 'ERROR';
						exit;
					}
				} else {
					header('HTTP/1.0 401 Unauthorized');
					echo 'ERROR';
					exit;
				}
			}
			default: {
				header('HTTP/1.0 401 Unauthorized');
				echo '<h1>Zombaio Gateway 1.1</h1><h3>Authentication failed. No Idea: ' . $action . '</h3>';
				exit;
			}
		}
		
		// log result
		$this->logresult($logid, $logmsg, $user_id);
		$this->notifyadmin($logid, $logmsg);
		
		echo 'OK';
		exit;
	}
	
	/**
	Payment Processor utility
	*/
	private function log() {
		$username = isset($_GET['username']) ? ' - ' . $_GET['username'] : '';
		$post = array(
			'post_title'		=> 'Zombaio ' . $_GET['Action'] . $username,
			'post_type'			=> 'wp_zombaio',
			'post_status'		=> (isset($_GET['Action']) ? str_replace('.', '_', strtolower($_GET['Action'])) : 'unknown'),
			'post_content'		=> print_r($_GET, TRUE),
		);
		$r = @wp_insert_post($post);

		update_post_meta($r, 'wp_zombaio_json_packet', json_encode($_GET));

		return $r;
	}
	private function logresult($logid, $logmsg, $user_id) {
		update_post_meta($logid, 'wp_zombaio_logmessage' , $logmsg);
		update_post_meta($logid, 'wp_zombaio_user_id', $user_id);
		if (isset($_GET['Amount'])) {
			update_post_meta($logid, 'wp_zombaio_amount', $_GET['Amount']);
		}
		if (isset($_GET['Credits'])) {
			update_post_meta($logid, 'wp_zombaio_credits', $_GET['Credits']);
		}
		if (isset($_GET['credits'])) {
			update_post_meta($logid, 'wp_zombaio_credits', $_GET['credits']);
		}
		return;
	}
	
	private function notifyadmin($logid, $logmsg) {
		if (!$this->options->notify_enable) {
			return;
		}
		// notify admin
		$subject = 'WP Zombaio: Payment Result';
		$message = 'A Payment has been processed' . "\n"
			. 'The Result was: ' . $logmsg . "\n"
			. 'Full Log: ' . print_r($_GET, TRUE) . "\n"
			. 'Love WP Zombaio';
		$target = !empty($this->options->notify_target) ? $this->options->notify_target : get_option('admin_email');
		@wp_mail($target, $subject, $message);
		return;
	}

	private function verify_ipn_ip() {
		if ($this->options->bypass_ipn_ip_verification) {
			return TRUE;
		}
		$ip = $_SERVER['REMOTE_ADDR'];

		$ips = $this->load_ipn_ips();		
		if ($ips) {
			if (in_array($ip, $ips)) {
				return TRUE;
			}
		}
		return FALSE;
	}
	/**
	utility
	*/
	private function load_ipn_ips() {
		$request = new WP_Http;
		$data = $request->request('http://www.zombaio.com/ip_list.txt');
		$data = explode('|', $data['body']);
		return $data;
	}

	/**
	widgets
	*/
	public function widgets_init() {
		register_widget('wp_zombaio_widget');
		register_widget('wp_zombaio_seal');
		register_widget('wp_zombaio_login');
//		register_widget('wp_zombaio_registerlogin');
		register_widget('wp_zombaio_credits');
	}

	/**
	Dashboard
	*/
	public function wp_dashboard_setup() {
		require_once(plugin_dir_path(__FILE__) . 'wp_zombaio_dashboard.php');
		new wp_zombaio_dashboard();
	}

	/**




	FrontEnd




	*/
	public function frontend() {
		add_shortcode('zombaio_seal', array($this, 'shortcode_zombaio_seal'));
		add_shortcode('zombaio_join', array($this, 'zombaio_join'));
		add_shortcode('zombaio_login', array($this, 'zombaio_login'));

		add_shortcode('zombaio_add_credits', array($this, 'zombaio_add_credits'));

		add_action('wp_enqueue_scripts', array($this, 'wp_enqueue_scripts'));

		add_action('template_redirect', array($this, 'template_redirect'));

		add_filter('the_content', array($this, 'post_purchasable'));
	}

	/**
	login block
	*/
	public function template_redirect() {
		if (!is_user_logged_in() && $this->options->redirect_target_enable) {
			$redirect = FALSE;

			if ($this->options->redirect_target) {
				$target = substr($this->options->redirect_target, 0, strpos($this->options->redirect_target, '--'));
				$target_url = get_permalink($target);
			} else {
				$target = TRUE;
				$target_url = home_url('wp-login.php');
			}

			if ($target) {
				if (is_singular() || is_single() || is_page()) {
					if (get_the_ID() != $target) {
						$redirect = TRUE;
					}
				} else {
					$redirect = TRUE;
				}

				if ($redirect) {
					header('Location: ' . $target_url);
					exit;
				}
			}
		}
	}
	public function wp_authenticate_user($user) {
		if (is_wp_error($user)) {
			return $user;
		}
		if (get_user_meta($user->ID, 'wp_zombaio_delete', TRUE)) {
			$err = new WP_Error();
			$err->add('wp_zombaio_error', __('Access Blocked: Zombaio Failed Rebill or Access Suspended', 'wp-zombaio'));
			return $err;
		}
		return $user;
	}
	public function wp_authenticate_user_check() {
		global $pagenow, $current_user;
		$array = array('wp-login.php', 'wp-register.php');
		if (!in_array($pagenow, $array) && is_user_logged_in()) {
			if (get_user_meta($current_user->ID, 'wp_zombaio_delete', TRUE)) {
				// is blocked during login occurance
				wp_clear_auth_cookie();
				wp_redirect(get_option('siteurl') . '/wp-login.php');
				exit;
			}
		}
	}

	/**
	Menus
	*/
	function menus() {
		register_nav_menus(array(
			'logged_out_navigation' => __('Zombaio Logged Out Navigation', 'wp-zombaio'),
			'logged_in_navigation' => __('Zombaio Logged In Navigation', 'wp-zombaio'),
		));
	}
	function menu_select($args) {
		if (is_user_logged_in()) {
			$args['theme_location'] = 'logged_in_navigation';
		} else {
			$args['theme_location'] = 'logged_out_navigation';
		}
		return $args;
	}

	/**
	script/style
	*/
	public function wp_enqueue_scripts() {
		wp_enqueue_style('wp_zombaio_css', plugin_dir_url(__FILE__) . 'wp_zombaio.css');
	}
	
	/**
	ShortCodes
	*/
	public function shortcode_zombaio_seal($args = array()) {
		$align = isset($args['align']) ? 'align' . $args['align'] : 'aligncenter';
		return '<div class="' . $align . '" style="width: 130px;">' . $this->options->seal_code . '</div>';
	}
	public function zombaio_join($args = array(), $content = '') {
		if (is_user_logged_in()) {
			return '';
		}

		$this->form_index++;

		$html = '';

		$join_url = isset($args['join_url']) ? $args['join_url'] : FALSE;
		$approve_url = isset($args['approve_url']) ? $args['approve_url'] : false;
		$decline_url = isset($args['decline_url']) ? $args['decline_url'] : false;
		$submit = isset($args['submit']) && $args['submit'] ? $args['submit'] : __('Join', 'wp-zombaio');

		if ($join_url) {
			if (FALSE !== strpos($join_url, 'zombaio.com')) {
				list($crap, $zombaio, $com_crap, $id, $zom) = explode('.', $args['join_url']);
			} else {
				$id = $args['join_url'];
			}

			if ($id) {
				$align = isset($args['align']) ? 'align' . $args['align'] : 'aligncenter';
				$style = isset($args['width']) ? 'width: ' . $args['width'] . 'px;' : '';
				$buttonalign = isset($args['buttonalign']) ? 'align' . $args['buttonalign'] : 'alignright';

				$html .= '<form action="https://secure.zombaio.com/?' . $this->options->site_id . '.' . $id . '.ZOM" method="post" class="wp_zombaio_form ' . $align . '" style="' . $style . '">';

				$html .= '<label for="email' . $this->form_index . '">Email: <input type="email" id="email' . $this->form_index . '" name="email" /></label>';
				$html .= '<label for="username' . $this->form_index . '">Username: <input type="text" id="username' . $this->form_index . '" name="username" /></label>';
				$html .= '<label for="password' . $this->form_index . '">Password: <input type="password" id="password' . $this->form_index . '" name="password" /></label>';

				$html .= '<p class="jointext">' . $content . '</p>';

				if ($approve_url) {
					$html .= '<input type="hidden" name="return_url_approve" value="' . $approve_url . '" />';
				}
				if ($decline_url) {
					$html .= '<input type="hidden" name="return_url_decline" value="' . $decline_url . '" />';
				}

				$html .= '<div class="' . $buttonalign . '" style="width: 50px;">';
				$html .= '<input type="submit" name="zomPay" value="' . $submit . '" />';
				$html .= '</div>';
				$html .= '</form>';
			}
		}

		return $html;
	}

	public function zombaio_login() {
		if (is_user_logged_in()) {
			return '';
		} else {
			return wp_login_form(array('echo' => FALSE));
		}
	}

	public function zombaio_add_credits($args = array(), $content = '') {
		if (!is_user_logged_in()) {
			return '';
		}
		$html = '';

		$join_url = isset($args['join_url']) ? $args['join_url'] : false;
		$approve_url = isset($args['approve_url']) ? $args['approve_url'] : get_permalink();
		$decline_url = isset($args['decline_url']) ? $args['decline_url'] : get_permalink();
		$submit = isset($args['submit']) ? $args['submit'] : __('Click to buy credits', 'wp_zombaio');
		$selector = isset($args['selector']) ? $args['selector'] : false;

		if ($join_url) {
			if (FALSE !== strpos($join_url, 'zombaio.com')) {
				list($crap, $zombaio, $com_crap, $id, $zom) = explode('.', $args['join_url']);
			} else {
				$id = $args['join_url'];
			}

			if ($id) {
				$align = isset($args['align']) ? 'align' . $args['align'] : 'aligncenter';
				$style = isset($args['width']) ? 'width: ' . $args['width'] . 'px;' : '';
				$buttonalign = isset($args['buttonalign']) ? 'align' . $args['buttonalign'] : 'alignright';

				$current_user = wp_get_current_user();

				$html .= '<form action="https://secure.zombaio.com/?' . $this->options->site_id . '.' . $id . '.ZOM" method="post" class="wp_zombaio_form ' . $align . '" style="' . $style . '">';
				$html .= '<fieldset>';
				$html .= $content;
				if ($selector) {
//				$html .
				}
				$html .= '<input type="hidden" name="identifier" value="' . $current_user->ID . '" />';
				$html .= '<input type="hidden" name="Email" value="' . $current_user->user_email . '" />';
				$html .= '<input type="hidden" name="approve_url" value="' . $approve_url . '" />';
				$html .= '<input type="hidden" name="decline_url" value="' . $decline_url . '" />';
				$html .= '<div class="' . $buttonalign . '">';
				$html .= '<input type="submit" value="' . $submit . '" />';
				$html .= '</div>';
				$html .= '</fieldset>';
				$html .= '</form>';
			}
		}

		return $html;
	}

	/**
	Spending Credits
	*/
	public function post_purchasable($content) {
		$user = wp_get_current_user();
		if (strlen($post_cost_credits = get_post_meta(get_the_ID(), 'wp_zombaio_credit_cost', TRUE))) {
			$user_credits = get_user_meta($user->ID, 'wp_zombaio_credits', TRUE);
			// make sure we have a value
			if (!strlen($user_credits)) {
				$user_credits = 0;
			}

			$message = '';

			if ($_POST && isset($_POST['wp_zombaio_process_purchase'])) {
				if (wp_verify_nonce($_POST['wp_zombaio_credits_spend'], plugin_basename(__FILE__))) {
					// check for already have access
					if (!$this->hasPurchased()) {
						// go for purchase
						if ($user_credits >= $post_cost_credits) {
							// subtract
							$user_credits -= $post_cost_credits;
							// update credits
							update_user_meta($user->ID, 'wp_zombaio_credits', $user_credits);
							// log transaction
							$data = array(
								'Action'	=> 'credit_spend',
								'username'	=> $user->user_login,
								'post_id'	=> get_the_ID(),
								'credits'	=> $post_cost_credits,
								'amount'	=> $post_cost_credits,
								'type'		=> get_post_meta(get_the_ID(), 'wp_zombaio_credit_cost_type', TRUE),
								'user_id'	=> $user->ID
							);
							if (get_post_meta(get_the_ID(), 'wp_zombaio_credit_cost_type', TRUE) == 'timed') {
								$limit = get_post_meta(get_the_ID(), 'wp_zombaio_credit_cost_time_qty', TRUE);
								$units = get_post_meta(get_the_ID(), 'wp_zombaio_credit_cost_time_units', TRUE);

								$multiples = array(
									'hours'		=>	3600,
									'days'		=>	86400,
									'weeks'		=>	604800,
								);

								$expire = time() + ($limit * $multiples[$units]);
								$expire = date(get_option('time_format') . ' ' . get_option('date_format'), $expire);
								$data = array_merge($data, array('expire' => $expire));
							}
							$logmsg = 'Credit Spending';
							// build $_GET
							$_GET = array_merge($_GET, $data);
							$logid = $this->log();
							$this->logresult($logid, $logmsg, $user->ID);
							$this->notifyadmin($logid, $logmsg);

							update_post_meta($logid, 'wp_zombaio_post_id', get_the_ID());
							update_post_meta($logid, 'wp_zombaio_credit_cost_type', get_post_meta(get_the_ID(), 'wp_zombaio_credit_cost_type', TRUE));
						} else {
							$message = '<p>' . __('You don&#39;t have enough Credits', 'wp-zombaio') . '</p>';
						}
					}
				}
			}

			// post is purchasble
			if ($expires = $this->hasPurchased()) {
				// timer?
				if (get_post_meta(get_the_ID(), 'wp_zombaio_credit_cost_type', TRUE) == 'timed') {
					$multiples = array(
						'hours'		=>	3600,
						'days'		=>	86400,
						'weeks'		=>	604800,
					);

					if ($expires < 3600) {
						$time_left = date('i:s', $expires);
					} else if ($expires < 86400) {
						// hours left
						$time_left = ceil($expires / 3600) . ':' . date('i:s', $expires);
					} else {
						// days left
						$time_left = ceil($expires / 86400);
					}

					// timed access
					$time_left = '<div class="wp_zombaio_time_left"><p>' . sprintf(__('Your access expires in %s', 'wp-zombaio'), $time_left) . '</p></div>';
					$content = $time_left . $content;
				}
				return $content;
			} else {
				// not purchased
				$content = '<form action="" method="post">';
				$content .= wp_nonce_field(plugin_basename(__FILE__), 'wp_zombaio_credits_spend', TRUE, FALSE);
				$content .= '<input type="hidden" name="wp_zombaio_process_purchase" value="' . get_the_ID() . '" />';
				$content .= '<p>' . __('This Post is Purchasble', 'wp-zombaio') . '</p>';
				$content .= $message;
				$content .= '<p>' . sprintf(__('You have %s Credits', 'wp-zombaio'), $user_credits) . '</p>';
				$content .= '<input type="submit" value="' . sprintf(__('Buy Now %s Credits', 'wp-zombaio'), $post_cost_credits) . '" ' . ($user_credits >= $post_cost_credits ? '' : 'disabled="disabled"') . ' />';
				$content .= '</form>';
			}
		}
		return $content;
	}
	private function hasPurchased() {
		// purchase check
		global $wpdb, $current_user;
		$query = 'SELECT p1.post_id AS post_id
			FROM ' . $wpdb->postmeta . ' p1
			LEFT JOIN ' . $wpdb->postmeta . ' p2 ON p2.post_id = p1.post_id
			WHERE p1.meta_key = \'wp_zombaio_post_id\'
			AND p1.meta_value = \'' . get_the_ID() . '\'
			AND p2.meta_key = \'wp_zombaio_user_id\'
			AND p2.meta_value = \'' . $current_user->ID . '\'
			ORDER BY p1.meta_id DESC
			LIMIT 1
			';
		// order by and limit to only get the last purchase (in the case of timed)
		$log_id = $wpdb->get_var($query);
		if ($log_id) {
			$log = get_post($log_id);
			// entry found
			// timer check
			if (get_post_meta(get_the_ID(), 'wp_zombaio_credit_cost_type', TRUE) == 'timed') {
				$limit = get_post_meta(get_the_ID(), 'wp_zombaio_credit_cost_time_qty', TRUE);
				$units = get_post_meta(get_the_ID(), 'wp_zombaio_credit_cost_time_units', TRUE);

				$multiples = array(
					'hours'		=>	3600,
					'days'		=>	86400,
					'weeks'		=>	604800,
				);

				$limit = $limit * $multiples[$units];

				$log_time = strtotime($log->post_date);
				$end = $log_time + $limit;
				if ($end > time()) {
					return $end - time();
					return $end;
				} else {
					// access expired
					return FALSE;
				}
			}
			return true;
		}
		return false;
	}
}

// FIRE IN THE HOLE
new wp_zombaio();
