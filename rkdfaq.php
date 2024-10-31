<?php
	/*
	Plugin Name: RKD FAQ
	Plugin URI: http://blog.rkd-graphics.nl/rkd-faq
	Description: RKD FAQ: a jQuery FAQ Accordion
	Version: 1.0.13
	Author: Wietse Warendorff
	Author URI: http://www.rkd-graphics.nl
	License: GPL
	*/
	
	add_action('admin_menu', 'rkdfaq_admin_menu');
	add_action('admin_init', 'rkdfaq_settings');
	add_action('wp_enqueue_scripts', 'rkdfaq_enqueue_scripts');
	add_shortcode('rkdfaq', 'rkdfaq_get_questions');
	register_activation_hook(__FILE__,'rkdfaq_install');
	register_deactivation_hook( __FILE__, 'rkdfaq_uninstall' );
	
	function rkdfaq_install() {
		global $wpdb;
		$table_name = $wpdb->prefix . "rkdfaq";
	
		$sql = "CREATE TABLE " . $table_name . " (
			id INT(10) NOT NULL AUTO_INCREMENT PRIMARY KEY,
			answer TEXT NOT NULL,
			ordering VARCHAR(10) NOT NULL,
			question TEXT NOT NULL
		);";
		
		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
		dbDelta($sql);
		
		add_option("rkdfaq_version", "1.0.13");
	}
	
	function rkdfaq_uninstall() {
		global $wpdb;
		$table_name = $wpdb->prefix . "rkdfaq";
	
		delete_option("rkdfaq_version");
		$wpdb->query("DROP TABLE IF EXISTS " . $table_name);
	}
	
	function rkdfaq_admin_menu() {
		add_menu_page('RKD FAQ', 'RKD FAQ', 'administrator', 'rkdfaq', 'rkdfaq_info', plugin_dir_url( __FILE__ ) . '/icon.png');
		add_submenu_page('rkdfaq', 'Questions', 'Questions', 'administrator', 'rkdfaq_questions', 'rkdfaq_questions');
	}
	
	function rkdfaq_enqueue_scripts() {
		wp_enqueue_script('rkdfaq_jquery_widget', WP_PLUGIN_URL . '/rkd-faq/jquery.ui.min.js', array('jquery', 'jquery-ui-core'), '1.8.16');
	}
	
	function rkdfaq_settings() {
		register_setting('rkdfaq_settings', 'rkdfaq');
	}
	
	
	function rkdfaq_questions() {
		if(get_magic_quotes_gpc()) {
			$_POST = array_map('stripslashes_deep', $_POST);
			$_GET = array_map('stripslashes_deep', $_GET);
			$_COOKIE = array_map('stripslashes_deep', $_COOKIE);
			$_REQUEST = array_map('stripslashes_deep', $_REQUEST);
		}
		
		$id = mysql_escape_string($_POST['id']);
		$answer = mysql_escape_string($_POST['answer']);
		$ordering = mysql_escape_string($_POST['ordering']);
		$question = mysql_escape_string($_POST['question']);
		
		if($_POST['delete']) {
			rkdfaq_question_delete($id);
			rkdfaq_overview();
		} elseif($_POST['edit']) {
			rkdfaq_question_edit($id);
		} elseif($_POST['save']) {
			rkdfaq_question_save($id, $ordering, $answer, $question);
			rkdfaq_overview();
		} else {
			rkdfaq_overview();
		}
	}
	
	function rkdfaq_info() {
		global $funcs;
		$options = get_option('rkdfaq');
		?>
			<div class="wrap">
				<div id="icon-options-general" class="icon32"></div>
				<h2>Settings - RKD FAQ</h2>
				<p>Set up your RKD FAQ plugin below.</p>
				<form method="post" action="options.php">
					<?php settings_fields( 'rkdfaq_settings' ); ?>
					<table class="form-table">
						<tr>
							<th width="200">Name</th>
							<th>Value</th>
						</tr>
						<tr valign="top">
							<td>
								Active
							</td>
							<td>
								On&nbsp;<input type="radio" name="rkdfaq[active]" value="1" <?php if($options['active']) echo 'checked'; ?> />
								Off&nbsp;<input type="radio" name="rkdfaq[active]" value="0" <?php if(!$options['active']) echo 'checked'; ?> />
							</td>
						</tr>
						<tr valign="top">
							<td>
								Auto Height
							</td>
							<td>
								On&nbsp;<input type="radio" name="rkdfaq[autoheight]" value="1" <?php if($options['autoheight']) echo 'checked'; ?> />
								Off&nbsp;<input type="radio" name="rkdfaq[autoheight]" value="0" <?php if(!$options['autoheight']) echo 'checked'; ?> />
							</td>
						</tr>
						<tr valign="top">
							<td>
								Collapsible
							</td>
							<td>
								On&nbsp;<input type="radio" name="rkdfaq[collapsible]" value="1" <?php if($options['collapsible']) echo 'checked'; ?> />
								Off&nbsp;<input type="radio" name="rkdfaq[collapsible]" value="0" <?php if(!$options['collapsible']) echo 'checked'; ?> />
							</td>
						</tr>
					</table>
					<p class="submit">
					<input type="submit" class="button-primary" value="<?php _e('Save Settings') ?>" />
					</p>
				</form>
		<?php
	}
	
	function rkdfaq_overview() {
		global $wpdb;
		?>
			<div class="wrap">
				<div id="icon-edit" class="icon32"></div>
				<h2>RKD FAQ Questions</h2>
				<form action="" method="post">

					<input name="id" type="hidden" value="0" />
					<input name="edit" type="submit" value="Add question" />
				</form>
				<table id="archives-month" class="widefat">
					<thead>
						<tr>
							<th>Ordering</th>
							<th>Question</th>
							<th>Edit</th>
							<th>Delete</th>
						</tr>
					</thead>
					<tfoot>
						<tr>
							<th>Ordering</th>
							<th>Question</th>
							<th>Edit</th>
							<th>Delete</th>
						</tr>
					</tfoot>
					<tbody>
					<?php 
						$table_name = $wpdb->prefix . 'rkdfaq';
						$query = $wpdb->get_results("SELECT * FROM " . $table_name . " ORDER BY ordering,id", ARRAY_A);
						$questions = array();
	
						foreach($query as $result) {
							array_push($questions, array('id' => $result['id'], 'answer' => $result['answer'], 'ordering' => $result['ordering'], 'question' => $result['question']));
						}
	
						foreach($questions as $question) {
						?>
							<form action="" method="post">
							<input name="id" type="hidden" value="<?php echo $question['id']; ?>" />
							<tr>
								<td>
									<?php echo $question['ordering']; ?>
								</td>
								<td>
									<?php echo $question['question']; ?>
								</td>
								<td>
									<input name="edit" type="submit" value="Edit" />
								</td>
								<td>
									<input name="delete" type="submit" value="Delete" />
								</td>
							</tr>
							</form>
						<?php
						}
					?>
					</tbody>
				</table>
			</div>
		<?php
	}
	
	function rkdfaq_admin_questionform($id=0, $ordering=0, $question='', $answer='') {
		wp_enqueue_script(array('jquery', 'editor', 'thickbox', 'media-upload'));
		wp_enqueue_style('thickbox');
		?>
			<div class="wrap">
				<div id="icon-edit-pages" class="icon32">
					<br />
				</div>
				<h2>Form</h2>
				<div id="poststuff">
					<form action="" method="post">
					<input name="id" type="hidden" value="<?php echo $id; ?>">
						<div id="post-body">
							<div id="post-body-content">
								<div id="titlediv">
									<div id="titlewrap">
										Ordering:<br /><input id="title" name="ordering" type="text" value="<?php echo $ordering; ?>" /><br />
										Title:<br /><input id="title" name="question" type="text" value="<?php echo $question; ?>" /><br />
										<?php the_editor($answer, 'answer'); ?>
									</div>
								</div>
							</div>
						</div>
					</div>
					<input type="submit" name="save" class="button-primary" value="<?php _e('Save Changes') ?>" />
					</form>
				</div>
			</div>
		<?php
	}
	
	function rkdfaq_question_delete($id=0) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'rkdfaq';
		$id = absint($id);
	
		$wpdb->query("DELETE FROM " . $table_name . " WHERE id = '$id'");
	}
	
	function rkdfaq_question_edit($id=0) {
		if($id=='') $id=0;
		global $wpdb;
		$table_name = $wpdb->prefix . 'rkdfaq';
		$question = $wpdb->get_results("SELECT * FROM " . $table_name . " WHERE id = " . $id, ARRAY_A);
		
		rkdfaq_admin_questionform($id, $question[0]['ordering'], $question[0]['question'], $question[0]['answer']);
	}
	
	function rkdfaq_question_save($id=0, $ordering=0, $answer='', $question='') {
		global $wpdb;
		$table_name = $wpdb->prefix . 'rkdfaq';
		
		if($id==0) {
			$query = "INSERT INTO " . $table_name . " (answer, ordering, question) VALUES ('" . $answer . "', '" . $ordering . "', '" . $question . "')";
		} else {
			$query = "UPDATE " . $table_name . " SET answer='" . $answer . "', ordering='" . $ordering . "', question='" . $question . "' WHERE id=" . $id;
		}
		$wpdb->query($query);
	}
	
	function rkdfaq_get_questions() {
		global $funcs;
		global $wpdb;
		$options = get_option('rkdfaq');
		$table_name = $wpdb->prefix . 'rkdfaq';
		
		$results = $wpdb->get_results( "SELECT * FROM " . $table_name . " ORDER BY ordering, id", ARRAY_A);
		
		$js_options = array();
		if(!$options['autoheight']) {
			$js_options[] = 'autoHeight: false';
		}
		if(!$options['active']) {
			$js_options[] = 'active: false';
		}
		if($options['collapsible']) {
			$js_options[] = 'collapsible: true';
		}
		if($js_options) {
			$js_options = implode(',', $js_options);
		}
		
		if($results) {
			$output = '<script type="text/javascript" charset="utf-8">jQuery(document).ready(function($){$("#rkdfaq").accordion({' . $js_options . '});});</script>';
			$output .= '<div id="rkdfaq">';
			foreach($results as $question) {
				$output .= '<div class="rkdfaq_question">' . $question['question'] . '</div>';
				$output .= '<div class="rkdfaq_answer">'.$question['answer'].'</div>';
			}
			$output .= '</div>';
		} else {
			$output = '&nbsp;';
		}
		
		return $output;
	}
?>