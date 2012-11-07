<?php

/*
* $Id: wp_zombaio_widgets.php 615389 2012-10-21 21:56:38Z BarryCarlyon $
* $Revision: 615389 $
* $Date: 2012-10-21 22:56:38 +0100 (Sun, 21 Oct 2012) $
*/

/**
join
*/
class wp_zombaio_widget extends wp_widget {
	function wp_zombaio_widget() {
		$this->widgetclassname = 'widget_wp_zombaio_widget';

		$widget_ops = array('classname' => $this->widgetclassname, 'description' => __('Use this widget to add a Join Form to your SideBar', 'wp-zombaio'));
		$this->WP_Widget($this->widgetclassname, __('WP Zombaio Join Widget', 'wp-zombaio'), $widget_ops);
		$this->alt_option_name = $this->widgetclassname;

		add_action('save_post', array(&$this, 'flush_widget_cache'));
		add_action('deleted_post', array(&$this, 'flush_widget_cache'));
		add_action('switch_theme', array(&$this, 'flush_widget_cache'));

		parent::__construct(false, __('WP Zombaio Join Widget', 'wp-zombaio'));
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
		if (!isset($args['join_url'])) {
			return;
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
		echo do_shortcode('[zombaio_join join_url="' . $join_url . '" approve_url="' . $approve_url . '" decline_url="' . $decline_url . '" submit="' . $submit . '"]' . $message . '[/zombaio_join]');
		echo $after_widget;

		$cache[$args['widget_id']] = ob_get_flush();
		wp_cache_set($this->widgetclassname, $cache, 'widget');
	}

	function update($new_instance, $old_instance) {
		$instance = $old_instance;
		$instance['title'] = $new_instance['title'];
		$instance['join_url'] = $new_instance['join_url'];
		$instance['approve_url'] = $new_instance['approve_url'];
		$instance['decline_url'] = $new_instance['decline_url'];
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

		echo '<label for="' . $this->get_field_id('title') . '">' . __('Title:', 'wp-zombaio') . ' <input type="text" name="' . $this->get_field_name('title') . '" id="' . $this->get_field_id('title') . '" value="' . $instance['title'] . '" /></label>';
		echo '<br />';
		echo '<label for="' . $this->get_field_id('join_url') . '">' . __('Join Form URL:', 'wp-zombaio') . ' <input type="text" name="' . $this->get_field_name('join_url') . '" id="' . $this->get_field_id('join_url') . '" value="' . $instance['join_url'] . '" /></label>';
		echo '<br />';
		echo '<label for="' . $this->get_field_id('message') . '">' . __('Intro Text:', 'wp-zombaio') . ' <input type="text" name="' . $this->get_field_name('message') . '" id="' . $this->get_field_id('message') . '" value="' . $instance['message'] . '" /></label>';
		echo '<br />';
		echo '<label for="' . $this->get_field_id('approve_url') . '">' . __('Thank You/Payment Complete URL:', 'wp-zombaio') . ' <input type="text" name="' . $this->get_field_name('approve_url') . '" id="' . $this->get_field_id('approve_url') . '" value="' . $instance['approve_url'] . '" /></label>';
		echo '<br />';
		echo '<label for="' . $this->get_field_id('decline_url') . '">' . __('Payment Declined URL:', 'wp-zombaio') . ' <input type="text" name="' . $this->get_field_name('decline_url') . '" id="' . $this->get_field_id('decline_url') . '" value="' . $instance['decline_url'] . '" /></label>';
		echo '<br />';
		echo '<label for="' . $this->get_field_id('submit') . '">' . __('Submit Button:', 'wp-zombaio') . ' <input type="text" name="' . $this->get_field_name('submit') . '" id="' . $this->get_field_id('submit') . '" value="' . $instance['submit'] . '" /></label>';
		echo '<br />';
	}

	function flush_widget_cache() {
		wp_cache_delete($this->widgetclassname, 'widget');
	}
}

/**
seal
*/
class wp_zombaio_seal extends wp_widget {
	function wp_zombaio_seal() {
		$this->widgetclassname = 'widget_wp_zombaio_seal';

		$widget_ops = array('classname' => $this->widgetclassname, 'description' => __('Use this widget to add a Zombaio Site Seal to your SideBar', 'wp-zombaio'));
		$this->WP_Widget($this->widgetclassname, __('WP Zombaio Seal Widget', 'wp-zombaio'), $widget_ops);
		$this->alt_option_name = $this->widgetclassname;

		add_action('save_post', array(&$this, 'flush_widget_cache'));
		add_action('deleted_post', array(&$this, 'flush_widget_cache'));
		add_action('switch_theme', array(&$this, 'flush_widget_cache'));

		parent::__construct(false, __('WP Zombaio Seal Widget', 'wp-zombaio'));
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

/**
generic login
*/
class wp_zombaio_login extends wp_widget {
	function wp_zombaio_login() {
		$this->widgetclassname = 'widget_wp_zombaio_login';

		$widget_ops = array('classname' => $this->widgetclassname, 'description' => __('Use this widget to add a Login Form to your SideBar', 'wp-zombaio'));
		$this->WP_Widget($this->widgetclassname, __('WP Zombaio Login Widget', 'wp-zombaio'), $widget_ops);
		$this->alt_option_name = $this->widgetclassname;

		add_action('save_post', array(&$this, 'flush_widget_cache'));
		add_action('deleted_post', array(&$this, 'flush_widget_cache'));
		add_action('switch_theme', array(&$this, 'flush_widget_cache'));

		parent::__construct(false, __('WP Zombaio Login Widget', 'wp-zombaio'));
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
		wp_login_form();
		echo $after_widget;

		$cache[$args['widget_id']] = ob_get_flush();
		wp_cache_set($this->widgetclassname, $cache, 'widget');
	}

	function update($new_instance, $old_instance) {
		$instance = $old_instance;
		$instance['title'] = $new_instance['title'];
		return $instance;
	}
	function form($instance) {
		$defaults = array(
			'title'		=> 'Login',
		);
		$instance = wp_parse_args((array)$instance, $defaults);

		echo '<label for="' . $this->get_field_id('title') . '">' . __('Title:', 'wp-zombaio') . ' <input type="text" name="' . $this->get_field_name('title') . '" id="' . $this->get_field_id('title') . '" value="' . $instance['title'] . '" /></label>';
		echo '<br />';
	}

	function flush_widget_cache() {
		wp_cache_delete($this->widgetclassname, 'widget');
	}	
}

/**
Credits
*/
class wp_zombaio_credits extends wp_widget {
	function wp_zombaio_credits() {
		$this->widgetclassname = 'widget_wp_zombaio_credits';

		$widget_ops = array('classname' => $this->widgetclassname, 'description' => __('Use this widget to add a Credit Purchase Form to your SideBar', 'wp-zombaio'));
		$this->WP_Widget($this->widgetclassname, __('WP Zombaio Credit Purchase Widget', 'wp-zombaio'), $widget_ops);
		$this->alt_option_name = $this->widgetclassname;

		add_action('save_post', array(&$this, 'flush_widget_cache'));
		add_action('deleted_post', array(&$this, 'flush_widget_cache'));
		add_action('switch_theme', array(&$this, 'flush_widget_cache'));

		parent::__construct(false, __('WP Zombaio Credits Purchase Widget', 'wp-zombaio'));
	}

	function widget($args, $instance) {
		if (!is_user_logged_in()) {
			return '';
		}
		$cache = wp_cache_get($this->widgetclassname, 'widget');
		if (!is_array($cache)) {
			$cache = array();
		}
		if (!isset($args['widget_id'])) {
			$args['widget_id'] = null;
		}
		if (!isset($args['join_url'])) {
			return;
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
		echo do_shortcode('[zombaio_add_credits join_url="' . $join_url . '" approve_url="' . $approve_url . '" decline_url="' . $decline_url . '" submit="' . $submit . '"]');
		echo $after_widget;

		$cache[$args['widget_id']] = ob_get_flush();
		wp_cache_set($this->widgetclassname, $cache, 'widget');
	}

	function update($new_instance, $old_instance) {
		$instance = $old_instance;
		$instance['title'] = $new_instance['title'];
		$instance['join_url'] = $new_instance['join_url'];
		$instance['approve_url'] = $new_instance['approve_url'];
		$instance['decline_url'] = $new_instance['decline_url'];
		$instance['submit'] = $new_instance['submit'];
		return $instance;
	}
	function form($instance) {
		$defaults = array(
			'title'		=> __('Purchase Credits', 'wp-zombaio'),
			'join_url'	=> '',
			'submit'	=> __('Click to buy Credits', 'wp-zombaio'),
		);
		$instance = wp_parse_args((array)$instance, $defaults);

		echo '<label for="' . $this->get_field_id('title') . '">' . __('Title:', 'wp-zombaio') . ' <input type="text" name="' . $this->get_field_name('title') . '" id="' . $this->get_field_id('title') . '" value="' . $instance['title'] . '" /></label>';
		echo '<br />';
		echo '<label for="' . $this->get_field_id('join_url') . '">' . __('Join Form URL:', 'wp-zombaio') . ' <input type="text" name="' . $this->get_field_name('join_url') . '" id="' . $this->get_field_id('join_url') . '" value="' . $instance['join_url'] . '" /></label>';
		echo '<br />';
		echo '<label for="' . $this->get_field_id('approve_url') . '">' . __('Thank You/Payment Complete URL:', 'wp-zombaio') . ' <input type="text" name="' . $this->get_field_name('approve_url') . '" id="' . $this->get_field_id('approve_url') . '" value="' . $instance['approve_url'] . '" /></label>';
		echo '<br />';
		echo '<label for="' . $this->get_field_id('decline_url') . '">' . __('Payment Declined URL:', 'wp-zombaio') . ' <input type="text" name="' . $this->get_field_name('decline_url') . '" id="' . $this->get_field_id('decline_url') . '" value="' . $instance['decline_url'] . '" /></label>';
		echo '<br />';
		echo '<label for="' . $this->get_field_id('submit') . '">' . __('Submit Button:', 'wp-zombaio') . ' <input type="text" name="' . $this->get_field_name('submit') . '" id="' . $this->get_field_id('submit') . '" value="' . $instance['submit'] . '" /></label>';
		echo '<br />';
	}

	function flush_widget_cache() {
		wp_cache_delete($this->widgetclassname, 'widget');
	}
}
