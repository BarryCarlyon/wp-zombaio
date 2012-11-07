<?php

/*
 Plugin Name: Ask a Question | Courses
 Plugin URI: http://YourMembers.co.uk/
 Description: Creates the Course Content Post Type and Handle the asking and purchasing of questions on that content
 Author: CodingFutures
 Author URI: http://www.YourMembers.co.uk
 Version: 11.0.0
 */

class ym_ask_a_question {
	function __construct() {
		add_action('init', array($this, 'init'));
		add_filter('ym_user_api_expose', array($this, 'ym_user_api_expose'));

		if (is_admin()) {
			add_action('admin_menu', array($this, 'admin_menu'));
			add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_scripts'));
			add_action('ym_navigation_loaded', array($this, 'ym_navigation_loaded'));
			return;
		}

		add_filter('the_content', array($this, 'add_questions'));
		add_action('wp_enqueue_scripts', array($this, 'wp_enqueue_script'));

		add_filter('ym_purchase_unknown', array($this, 'ym_purchase_unknown'), 10, 5);
	}

	function init() {
		register_post_type(
			'course_content',
			array(
				'label'		=> 'Course Content',
				'labels'	=> array(
					'name' => __('Course Content'),
					'singular_name' => __('Course Content'),
					'add_new' => __('Add New'),
					'add_new_item' => __('Add New Course Content'),
					'edit_item' => __('Edit Course Content'),
					'new_item' => __('New Course Content'),
					'all_items' => __('All Course Content'),
					'view_item' => __('View Course Content'),
					'search_items' => __('Search Course Content'),
					'not_found' =>  __('No Course Content found'),
					'not_found_in_trash' => __('No Course Content found in Trash'), 
					'parent_item_colon' => '',
					'menu_name' => __('Course Content')
				),
				'public'	=> TRUE,
				'supports'	=> array(
					'title',
					'editor',
					'author',
					'comments'
				)
			)
		);
		register_post_type(
			'course_questions',
			array(
				'label'		=> 'Course Questions',
				'labels'	=> array(
				),
				'public'	=> FALSE,
			)
		);
		$this->options();
	}

	function options() {
		$options = get_option('ym_ask_a_question_options', FALSE);
		$force = FALSE;
		if (!$options) {
			$force = TRUE;
			$options = array();
		}

		if (ym_post('ym_saving_ask_a_question_options', FALSE) || $force) {
			$post_types = get_post_types();

			$ignores = array(
				'course_questions',
				'revision',
				'nav_menu_item',
				'attachment'
			);

			foreach ($post_types as $type) {
				if (!in_array($type, $ignores)) {
					$options['enable_ask_a_question_' . $type] = ym_post('enable_ask_a_question_' . $type, 0);
				}
			}
				
			$options['free_questions'] = ym_post('free_questions', 3);
			$options['question_cost'] = number_format(ym_post('question_cost', 10), 2, '.', '');
			$options['success_message'] = ym_post('success_message', 'Question was Asked Successfully');
			$options['purchase_required'] = ym_post('purchase_required', 'Purchase Required');
			$options['purchase_complete'] = ym_post('purchase_complete', 'Purchase Complete, your Question will publish when Payment Clears');
			$options['out_of_free_questions'] = ym_post('out_of_free_questions', 'You are out of Free Questions');
			$options['notify_email'] = ym_post('notify_email', '');

			update_option('ym_ask_a_question_options', $options);
		}

		$this->options = $options;
		return;
	}

	/**
	backend
	*/

	function ym_navigation_loaded() {
		ym_admin_add_menu_page(__('Advanced', 'ym'), __('Ask a Question','ym'), 'ym-hook-ask_a_question');
		add_action('ym-hook-ask_a_question', array($this, 'ask_a_question_options'));
	}
	function ask_a_question_options() {
		echo '<div class="wrap" id="poststuff">';
		global $ym_formgen;
		$post_types = get_post_types();

		$this->options();
		$options = $this->options;
		if ($_POST) {
			ym_display_message('Settings Updated');
		}

		echo ym_start_box(__('Ask A Question Options', 'ym'));
		echo '<form action="" method="post"><fieldset>
		<input type="hidden" name="ym_saving_ask_a_question_options" value="1" />
		<table>';

		$ym_formgen->render_form_table_divider('Enable Ask a Question, for which Post Type(s)');

		$ignores = array(
			'course_questions',
			'revision',
			'nav_menu_item',
			'attachment'
		);

		foreach ($post_types as $type) {
			if (!in_array($type, $ignores)) {
				$ym_formgen->render_form_table_checkbox_row($type, 'enable_ask_a_question_' . $type, $options['enable_ask_a_question_' . $type]);
			}
		}
		$ym_formgen->render_form_table_divider('Options');
		$ym_formgen->render_form_table_text_row('Number of Free Questions', 'free_questions', $options['free_questions'] ? $options['free_questions'] : 3);
		$question_cost = $options['question_cost'] ? $options['question_cost'] : 10;
		$ym_formgen->render_form_table_text_row('Cost for Additional Questions', 'question_cost', number_format($question_cost, 2, '.', ''));

		$ym_formgen->render_form_table_divider('Text Strings');

		$ym_formgen->render_form_table_text_row('Question Successfully Asked Message', 'success_message', $options['success_message']);
		$ym_formgen->render_form_table_text_row('Question Purchase Required', 'purchase_required', $options['purchase_required']);
		$ym_formgen->render_form_table_text_row('Question Purchase Complete', 'purchase_complete', $options['purchase_complete']);
		$ym_formgen->render_form_table_text_row('Out of Free Questions', 'out_of_free_questions', $options['out_of_free_questions']);

		$ym_formgen->render_form_table_divider('Notification');
		$ym_formgen->render_form_table_email_row('Email Address', 'notify_email', $options['notify_email'], 'A copy of the Author of the Content, Question Notification can be sent here (optional)');

		echo '</table>';
		echo '<p class="submit"><input type="submit" class="button-primary alignright" value="' . __('Save Options', 'ym') . '" />';
		echo '</fieldset></form>';
		echo ym_end_box();
		echo '</div>';
	}

	function admin_menu() {
		// how many questions need an answer
		$count = $this->how_many_questions_need_an_answer();
		$menu_title = 'Questions';
		$menu_title .= '<span class="update-plugins count-' . $count . '"><span class="plugin-count">' . $count . '</span></span>';
		add_menu_page('Questions', $menu_title, 'edit_posts', 'ym_ask_a_question_questions', array($this, 'ym_ask_a_question_questions'));
	}
	function admin_enqueue_scripts() {
		wp_enqueue_script('jquery-ui-dialog');
		wp_enqueue_style('jquery-ui-css', 'https://jquery-ui.googlecode.com/svn/tags/latest/themes/base/jquery.ui.all.css');
		wp_enqueue_style('ym_admin_css', YM_CSS_DIR_URL . 'ym_admin.css' , false, YM_PLUGIN_VERSION, 'all');
	}

	private function how_many_questions_need_an_answer() {
		//comment_count = 0
		global $wpdb;
		$query = 'SELECT ID FROM ' . $wpdb->posts . '
			WHERE comment_count = 0
			AND post_type = \'course_questions\'
			AND (
				post_status = \'publish\'
				OR
				post_status = \'private\'
			)';
		$wpdb->query($query);

		return $wpdb->num_rows;
	}

	function ym_ask_a_question_questions() {
		echo '
		<h2>Questions</h2>
		<div class="wrap" id="poststuff">
		';

		global $current_user;
		get_currentuserinfo();

		$answer = ym_get('answer', FALSE);
		if ($answer) {
			if ($_POST) {
				$data = array(
					'comment_post_ID'		=> $answer,
					'comment_author'		=> $current_user->display_name,
//					'comment_author_email'	=> $current_user->user_email,
//					'comment_author_url'	=> $current_user->user_url,
					'comment_content'		=> ym_post('response'),
					'comment_type'			=> '',
					'comment_parent'		=> 0,
					'user_id'				=> $current_user->ID,
					'comment_date'			=> current_time('mysql'),
					'comment_approved'		=> 1
				);
				$comment_id = wp_insert_comment($data);
				ym_display_message('Response Added');
			}

			$post = get_post($answer);

			echo '<p>This Question is: ';
			echo $post->post_status;
			$trans_id = get_post_meta($post->ID, 'ym_ask_a_question_transaction_id', TRUE);
			if ($trans_id) {
				echo ' Transaction ID: ' . $trans_id;
			}
			echo '</p>';

//			$question_on = get_post(get_post_meta($answer, 'ym_ask_a_question_about_id', TRUE));
//			echo ym_start_box('Review Post');
//			echo '<div class="ym_content_toggle" onclick="jQuery(this).find(\'.ym_content_toggle_content\').toggle();">Click To Show/Hide<div class="ym_content_toggle_content" style="display: none;">' . $question_on->post_content . '</div></div>';
//			echo ym_end_box();

			echo '<a href="admin.php?page=ym_ask_a_question_questions" class="button-secondary alignright">Back</a><div class="clear"></div>';

			echo ym_start_box($post->post_title);
			echo ym_apply_filter_the_content($post->post_content);
			echo '<p class="alignright">' . $post->post_date . '</p>';
			echo '<div class="clear"></div>';
			echo ym_end_box();

			$comments = get_comments(array(
				'post_id'	=> $post->ID,
				'orderby'	=> 'comment_date',
				'order'		=> 'ASC'
			));
			if (count($comments)) {
				foreach ($comments as $comment) {
					echo ym_start_box('Response by ' . $comment->comment_author);
					echo ym_apply_filter_the_content($comment->comment_content);
					echo '<p class="alignright">' . $comment->comment_date . '</p><div class="clear"></div>';
					echo ym_end_box();
				}
			}

			echo ym_start_box('Add Response');
			echo '<form action="" method="post"><table class="form-table">';
			global $ym_formgen;
			echo $ym_formgen->render_form_table_wp_editor_row('Add Response', 'response');
			echo '</table>';
			echo '<p class="submit"><input type="submit" value="Add Response" class="button-primary alignright" /></p>';
			echo '</form>';
			echo ym_end_box();

			echo '<a href="admin.php?page=ym_ask_a_question_questions" class="button-secondary alignleft">Back</a><div class="clear"></div>';
			echo '</div>';
			return;
		}

		if (ym_post('change_status_postid')) {
			$data = array(
				'ID'			=> ym_post('change_status_postid'),
				'post_status'	=> ym_post('change_status_newstatus')
			);
			wp_update_post($data);
			echo '<div id="message" class="updated"><p>Question Status Updated</p></div>';
		}

		$page_max = 20;
		$offset = ym_request('offset', 0);

		$args = array(
			'numberposts'	=> $page_max,
			'offset'		=> $offset,// page support
			'post_type'		=> 'course_questions',
			'post_status'	=> 'publish,private,pending,draft,trash',//is default but explicit state
		);
		$posts = get_posts($args);

		$args['numberposts'] = -1;
		$total = count(get_posts($args));

		echo '<table class="widefat fixed posts">';
		echo '<thead><tr><th style="width: 40px;">[ID]</th><th style="width: 40px;">Public</th><th>On Post</th><th>From User</th><th>At</th><th>Answers</th><th style="width: 80px;">Tasks</th><th style="width: 120px;">&nbsp;</th></tr></thead>';
		echo '<tfoot><tr><th>[ID]</th><th>Public</th><th>On Post</th><th>From User</th><th>At</th><th>Answers</th><th colspan="2">Tasks</th></tr></tfoot>';
		echo '<tbody>';
		foreach ($posts as $post) {
			echo '<tr>';
			echo '<td>' . $post->ID . '</td>';
			echo '<td>';
			switch ($post->post_status) {
				case 'publish';
					echo '<span class="ym_tick" title="Publish">&nbsp;</span>';
					break;
				case 'private':
					echo '<span class="ym_cross" title="Private">&nbsp;</span>';
					break;
				case 'pending':
					echo '<span class="ym_cart" title="Pending - Awaiting Payment">&nbsp;</span>';
					break;
				case 'draft':
					echo '<span class="ym_cart_error" title="Draft - Payment Failed">&nbsp;</span>';
					break;
				case 'trash':
					echo '<span class="ym_bin_recycle" title="Trash">&nbsp;</span>';
					break;
				default:
					echo $post->post_status;
			}
			$trans_id = get_post_meta($post->ID, 'ym_ask_a_question_transaction_id', TRUE);
			if ($trans_id) {
				echo '-[' . $trans_id . ']';
			}
			echo '</td>';
			$on_id = get_post_meta($post->ID, 'ym_ask_a_question_about_id', TRUE);
			echo '<td><a href="' . get_permalink($on_id) . '" target="postreview">' . get_the_title($on_id) . '</a></td>';
			$user = get_user_by('id', $post->post_author);
			echo '<td>' . $user->display_name . '</td>';
			echo '<td>' . $post->post_date . '</td>';
			echo '<td style="';
			if ($post->comment_count < 1 && ($post->post_status == 'publish' || $post->post_status == 'private')) {
				echo 'background: #EE9999; ';
			}
			echo 'text-align: center;">' . $post->comment_count . '</td>';
			echo '<td><p>
				<a href="admin.php?page=ym_ask_a_question_questions&answer=' . $post->ID . '" class="button-secondary">Answer</a>
			</p></td><td><p>
				<a href="admin.php?page=ym_ask_a_question_questions&answer=' . $post->ID . '" class="button-secondary change_status" data-currentstatus="' . $post->post_status . '" data-postid="' . $post->ID . '">Change Status</a>
			</p></td>';
			echo '</tr>';
		}
		echo '</tbody>';
		echo '</table>';

		if ($total > $page_max) {
			$next = $offset + $page_max;
			$back = $offset - $page_max;
			
			$page = ($offset / $page_max) + 1;//zero index
			$pages = ceil($total / $page_max);

			// show pagination
			echo '<table class="form-table"><tr>
			<td style="width: 33%;">';
			if ($back >= 0)
				echo '<a href="admin.php?page=ym_ask_a_question_questions&offset=' . $back . '" class="button-secondary">Previous Page</a>';
			echo '</td>
			<td style="text-align: center; width: 33%;">' . $page . '/' . $pages . '</td>
			<td style="width: 33%;">';
			if ($next < $total)
				echo '<a href="admin.php?page=ym_ask_a_question_questions&offset=' . $next . '" class="alignright button-secondary">Next Page</a>';
			echo '<td>
			</tr></table>';
		}

		echo '</div>';
?>
<form id="change_status_form" method="post" action="" style="display: none;">
	Change Post Status
	<input type="hidden" name="change_status_postid" id="change_status_form_postid" value="" />
	<select name="change_status_newstatus" id="change_status_form_newstatus">
		<option value="private">Private</option>
		<option value="publish">Publish</option>
		<option value="pending">Pending (Awaiting Payment)</option>
		<option value="draft">Draft (Payment Failed)</option>
		<option value="trash">Trash</option>
	</select>
</form>
<script type="text/javascript">
jQuery(document).ready(function() {
	jQuery('.change_status').click(function() {
		event.preventDefault();
		var currentstatus = jQuery(this).attr('data-currentstatus');
		var postid = jQuery(this).attr('data-postid');

		jQuery('#change_status_form_postid').val(postid);
		jQuery('#change_status_form_newstatus').val(currentstatus);

		jQuery('#change_status_form').dialog({
			width: 300,
			height: 200,
			modal: true
		});
	});

	jQuery('#change_status_form_newstatus').change(function() {
		jQuery('#change_status_form').submit();
	});
});
</script>
<?php
	}

	/**
	common
	*/

	function ym_user_api_expose($options) {
		$options[] = 'ask_a_question_questions';
		return $options;
	}

	/**
	front end
	*/
	function add_questions($content) {
		if (!ym_user_has_access()) {
			return $content;
		}
		$post_type = get_post_type();

		if ($this->options['enable_ask_a_question_' . $post_type] == 1) {
			global $ym_user;

			$html = '<div id="ym_ask_a_question">';

			// get public questions
			$args = array(
				'numberposts'	=> -1,
				'meta_key'		=> 'ym_ask_a_question_about_id',
				'meta_value'	=> get_the_ID(),
				'post_type'		=> 'course_questions',
				'post_status'	=> 'publish,private',
			);
			$questions = get_posts($args);

			if (count($questions)) {
				foreach ($questions as $question) {
					if (
						$question->post_status == 'publish'
						||
						$question->post_status == 'private' && (
							$question->post_author == $ym_user->ID
							||
							get_the_author() == $ym_user->ID
							||
							ym_superuser()
						)
					) {
						// hmm
						$html .= '<div id="ym_ask_a_question_a_asked_question_' . $question->ID . '" class="ym_ask_a_question_a_asked_question">';
						$author = get_user_by('id', $question->post_author);
						$html .= '<h4>' . 'Question From ' . $author->display_name . '</h4>';
						$html .= ym_apply_filter_the_content($question->post_content);
						$html .= '<p class="alignright">' . $question->post_date . '</p>';
						// get responses
						$comments = get_comments(array(
							'post_id'	=> $question->ID,
							'orderby'	=> 'comment_date',
							'order'		=> 'ASC'
						));
						if (count($comments)) {
							foreach ($comments as $comment) {
								$html .= '<div id="ym_ask_a_question_a_asked_question_response_' . $comment->comment_ID . '" class="ym_ask_a_question_a_asked_question_response">';
								$html .= '<p>' . $comment->comment_author . ' wrote:</p>';
								$html .= ym_apply_filter_the_content($comment->comment_content);
								$html .= '<p class="alignright">' . $comment->comment_date . '</p><div class="clear"></div>';
								$html .= '</div>';
							}
						}
						// end
						$html .= '</div>';
					}
				}
			}

			if ($_POST) {
				if (ym_get('purchase') == 'complete') {
					// purchase complete
					$html .= '<div id="message" class="success"><p>' . $this->options['purchase_complete'] . '</p></div>';
				} else {
					$dontpost = FALSE;
					$post_status = 'publish';
					if (ym_post('ym_ask_a_question_type', 'public') == 'private') {
						$post_status = 'private';
					}

					// statsh
					$post_purchase_post_status = $post_status;

					// check status
					if ($ym_user->ask_a_question_questions >= $this->options['free_questions']) {
						// purchase required
						$post_status = 'pending';
					}

					// posting
					$post = array(
						'post_author'		=> $ym_user->ID,
						'post_content'		=> ym_post('ym_ask_a_question_question', ''),
						'post_status'		=> $post_status,
						'post_title'		=> 'Question from ' . $ym_user->data->display_name . ' on ' . get_the_title(),
						'post_type'			=> 'course_questions',
					);

					$result = wp_insert_post($post, TRUE);
					if (is_wp_error($result)) {
						$html .= '<div id="message" class="error"><p>' . $result->get_error_message() . '</p></div>';
					} else {
	//					$post_id = $result;
						update_post_meta($result, 'ym_ask_a_question_about_id', get_the_ID());
						// store public/private mode
						update_post_meta($result, 'ym_ask_a_question_post_purchase_post_status', $post_purchase_post_status);
						// log
						$ym_user->ask_a_question_questions++;
						$ym_user->save();
						// purchase?
						if ($post_status == 'pending') {
							// go for purchase PayPal
							$html .= '<div id="message" class="success"><p>' . $this->options['purchase_required'] . '</p></div>';
							// payment form
							$ym_sku = 'question_purchase_' . $result . '_' . ym_get_user_id();
							$price = $this->options['question_cost'];
							$item_name = get_bloginfo() . ': ' . __('Question Purchase', 'ym');

							include('ym_ask_question_sku_form.php');
						} else {
							// message
							$html .= '<div id="message" class="success"><p>' . $this->options['success_message'] . '</p></div>';
							$this->notify($result);
						}
					}
				}
			}

			if (ym_superuser() || get_the_author() == $ym_user->ID) {
				// no
			} else if (!ym_get('purchase')) {
				$html .= '<form action="" method="post"><fieldset><table>';
				if ($ym_user->ask_a_question_questions < $this->options['free_questions']) {
					// all good
					$html .= '<tr><td></td><td>Free Question ' . ($ym_user->ask_a_question_questions + 1) . ' of ' . $this->options['free_questions'] . '</td></tr>';
				} else {
					$html .= '<tr><td></td><td>' . $this->options['out_of_free_questions'] . '</td></tr>';
				}

				// form
				$html .= '<tr><td>Your Question</td><td><textarea name="ym_ask_a_question_question" rows="5"></textarea></td></tr>';			
				$html .= '<tr><td>Question Type</td><td>
					<label for="ym_ask_a_question_type_public">Public: <input type="radio" name="ym_ask_a_question_type" id="ym_ask_a_question_type_public" class="ym_ask_a_question_type" value="public" checked="checked"></label>
					<label for="ym_ask_a_question_type_private">Private: <input type="radio" name="ym_ask_a_question_type" id="ym_ask_a_question_type_private" class="ym_ask_a_question_type" value="private"></label>
				</td></tr>';
				$html .= '</table>';

				if ($ym_user->ask_a_question_questions >= $this->options['free_questions']) {
					// all good
					// is purchase
					$html .= '<input type="submit" value="Purchase Question" class="alignright" /></p>';
				} else {
					$html .= '<p class="submit"><input type="submit" id="ym_ask_a_question_text_toggle_post" value="Post" class="alignright" />';
					$html .= '<input type="submit" id="ym_ask_a_question_text_toggle_send" value="Send" class="alignright" /></p>';
				}

				$html .= '</fieldset></form>';
			}
			$html .= '</div>';
			return $content . $html;
		}
		return $content;
	}

	function wp_enqueue_script() {
//		wp_enqueue_script('ym_ask_a_question', plugin_dir_url(__FILE__) . 'ym_ask_a_question.js');
		wp_enqueue_script('ym_ask_a_question', '/wp-content/plugins/ym_ask_question/ym_ask_a_question.js', array('jquery'));
	}

	/**
	Payment Processor
	*/
	function ym_purchase_unknown($failed, $item_field, $cost_field, $complete, $exit) {
		list($buy, $what, $id, $user_id) = explode('_', $item_field);
		if ($buy == 'question' && $what == 'purchase') {
			// $id = post id for question
			$failed = FALSE;

			$user = new YourMember_User($user_id);
			$data = array();
			if ($complete) {
				@ym_log_transaction(YM_PAYMENT, $cost_field, $user_id);
				@ym_log_transaction(YM_USER_STATUS_UPDATE, 'Question Purchase Cleared', $user_id);

				$data['status_str'] = 'Last payment (Questions) Cleared';

				// update post
				$target_status = get_post_meta($id, 'ym_ask_a_question_post_purchase_post_status', TRUE);

				$data = array(
					'ID'			=> $id,
					'post_status'	=> $target_status
				);
				wp_update_post($data);

				global $ym_this_transaction_id;
				update_post_meta($id, 'ym_ask_a_question_transaction_id', $ym_this_transaction_id);

				// notify
				$this->notify($id);
			} else {
				@ym_log_transaction(YM_USER_STATUS_UPDATE, 'Question Purchase ' . $_POST['payment_status'], $user_id);

				$data['status_str'] = 'Last payment (Questions) ' . $_POST['payment_status'];

				$failed = TRUE;
			}
			$user->update($data, TRUE);
		}

		if ($exit && $failed == FALSE) {
			header('HTTP/1.1 200 OK');
			exit;
		}
		return $failed;
	}

	private function notify($post_id) {
		$post = get_post($post_id);

		if ($post) {
			$author = get_user_by('id', $post->post_author);

			$subject = '[' . get_bloginfo() . '] New Question on Your Post ' . $post->post_title;

			$message = 'Hello ' . $author->display_name . ',' . "\n";
			$message .= 'A new question has been added to your Post ' . site_url('admin.php?page=ym_ask_a_question_questions&answer=' . $post_id) . "\n";
			$message .= 'It is a ' . ($post->post_status == 'publish' ? 'Public' : 'Private') . ' Question' . "\n";
			$message .= "\n" . 'The Ask a Question Bot';

			ym_email($author->user_email, $subject, $message);

			if ($this->options['notify_email']) {
				// admin
				ym_email($this->options['notify_email'], $subject, $message);
			}
		}
	}
}

new ym_ask_a_question();
