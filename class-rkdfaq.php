<?php
	if ( !class_exists( 'RKDFaq' ) ) {
		class RKDFaq {
			private $db_table = 'rkdfaq';
			private $version = '1.0.9';
			
			function __construct() {
				global $wpdb;
				$this->db_table = $wpdb->prefix . $this->db_table;
			}
			
			function rkdfaq_install() {
				require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
				dbDelta( "CREATE TABLE " . $this->db_table . " (id INT(10) NOT NULL AUTO_INCREMENT PRIMARY KEY, answer TEXT NOT NULL, question TEXT NOT NULL);" );
				
				add_option( 'rkdfaq_version', $this->version );
			}
			
			function rkdfaq_uninstall() {
				delete_option( 'rkdfaq_version' );
				$wpdb->query( "DROP TABLE IF EXISTS " . $this->db_table );
			}
			
			function rkdfaq_admin_menu() {
				add_menu_page( 'RKD FAQ', 'RKD FAQ', 'administrator', 'rkdfaq', array('RKDFAQ', 'rkdfaq_info'), plugin_dir_url( __FILE__ ) . '/icon.png' );
				add_submenu_page( 'rkdfaq', 'Questions', 'Questions', 'administrator', 'rkdfaq_questions', 'rkdfaq_questions' );
			}
			function rkdfaq_enqueue_scripts() {
				wp_deregister_script( 'jquery-ui' );
				wp_register_script( 'jquery-ui', 'http://ajax.googleapis.com/ajax/libs/jqueryui/1.8.16/jquery-ui.min.js', array( 'jquery' ) );
				wp_enqueue_script( 'jquery-ui' );
			}
			
			function rkdfaq_enqueue_styles() {
				global $funcs;
				$options = get_option( 'rkdfaq' );
				
				if( $options['themeroller'] ) {
					wp_register_style( 'jquery-ui' , 'http://ajax.googleapis.com/ajax/libs/jqueryui/1.8.16/themes/base/jquery-ui.css' );
					wp_enqueue_style( 'jquery-ui');	
				}
			}
			
			function rkdfaq_settings() {
				register_setting( 'rkdfaq_settings', 'rkdfaq' );
			}
			
			
			function rkdfaq_questions() {
				$id = htmlspecialchars( addslashes( $_POST['id'] ) );
				$answer = htmlspecialchars( addslashes( $_POST['answer'] ) );
				$question = htmlspecialchars( addslashes( $_POST['question'] ) );
				
				if( $_POST['delete'] ) {
					rkdfaq_question_delete( $id );
					rkdfaq_overview();
				} elseif( $_POST['edit'] ) {
					rkdfaq_question_edit( $id );
				} elseif($_POST['save']) {
					rkdfaq_question_save( $id, $answer, $question );
					rkdfaq_overview();
				} else {
					rkdfaq_overview();
				}
			}
			
			function rkdfaq_info() {
				global $funcs;
				$options = get_option( 'rkdfaq' );
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
										On&nbsp;<input type="radio" name="rkdfaq[active]" value="1" <?php if( $options['active'] ) echo 'checked'; ?> />
										Off&nbsp;<input type="radio" name="rkdfaq[active]" value="0" <?php if( !$options['active'] ) echo 'checked'; ?> />
									</td>
								</tr>
								<tr valign="top">
									<td>
										Auto Height
									</td>
									<td>
										On&nbsp;<input type="radio" name="rkdfaq[autoheight]" value="1" <?php if( $options['autoheight'] ) echo 'checked'; ?> />
										Off&nbsp;<input type="radio" name="rkdfaq[autoheight]" value="0" <?php if( !$options['autoheight'] ) echo 'checked'; ?> />
									</td>
								</tr>
								<tr valign="top">
									<td>
										Collapsible
									</td>
									<td>
										On&nbsp;<input type="radio" name="rkdfaq[collapsible]" value="1" <?php if( $options['collapsible'] ) echo 'checked'; ?> />
										Off&nbsp;<input type="radio" name="rkdfaq[collapsible]" value="0" <?php if( !$options['collapsible'] ) echo 'checked'; ?> />
									</td>
								</tr>
								<tr valign="top">
									<td>
										ThemeRoller
									</td>
									<td>
										On&nbsp;<input type="radio" name="rkdfaq[themeroller]" value="1" <?php if( $options['themeroller'] ) echo 'checked'; ?> />
										Off&nbsp;<input type="radio" name="rkdfaq[themeroller]" value="0" <?php if( !$options['themeroller'] ) echo 'checked'; ?> />
									</td>
								</tr>
							</table>
							<p class="submit">
							<input type="submit" class="button-primary" value="<?php _e( 'Save Settings' ) ?>" />
							</p>
						</form>
				<?php
			}
			
			function rkdfaq_overview() {
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
									<th>Id</th>
									<th>Question</th>
									<th>Edit</th>
									<th>Delete</th>
								</tr>
							</thead>
							<tfoot>
								<tr>
									<th>Id</th>
									<th>Question</th>
									<th>Edit</th>
									<th>Delete</th>
								</tr>
							</tfoot>
							<tbody>
							<?php 
								$questions = $wpdb->get_results( "SELECT * FROM " . $this->db_table . " ORDER BY id", ARRAY_A );
								if( $questions ) {
									foreach( $questions as $question ) {
									?>
										<form action="" method="post">
										<input name="id" type="hidden" value="<?php echo $question['id']; ?>" />
										<tr>
											<td>
												<?php echo $question['id']; ?>
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
								}
							?>
							</tbody>
						</table>
					</div>
				<?php
			}
			
			function rkdfaq_admin_questionform( $id=0, $question='', $answer='' ) {
				wp_enqueue_script( array( 'jquery', 'editor', 'thickbox', 'media-upload' ) );
				wp_enqueue_style( 'thickbox' );
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
												<input id="title" name="question" type="text" value="<?php echo $question; ?>" />
												<textarea id="content" name="answer" style="height: 300px; width: 100%"><?php echo $answer; ?></textarea>
											</div>
										</div>
									</div>
								</div>
							</div>
							<input type="submit" name="save" class="button-primary" value="<?php _e( 'Save Changes' ) ?>" />
							</form>
						</div>
					</div>
				<?php
			}
			
			function rkdfaq_question_delete( $id=0 ) {
				$wpdb->query( "DELETE FROM " . $this->db_table . " WHERE id=" . absint( $id ) );
			}
			
			function rkdfaq_question_edit( $id=0 ) {
				if( $id=='' ) $id=0;
				$question = $wpdb->get_results( "SELECT * FROM " . $this->db_table . " WHERE id = " . $id, ARRAY_A );
				
				rkdfaq_admin_questionform( $id, $question[0]['question'], $question[0]['answer'] );
			}
			
			function rkdfaq_question_save( $id=0, $answer='', $question='' ) {
				if( $id==0 ) {
					$wpdb->query( "INSERT INTO " . $table_name . " (answer, question) VALUES ('" . $answer . "', '" . $question . "')" );
				} else {
					$wpdb->query( "UPDATE " . $table_name . " SET answer='" . $answer . "', question='" . $question . "' WHERE id=" . absint( $id ) );
				}
			}
			
			function rkdfaq_get_questions() {
				global $funcs;
				global $wpdb;
				$options = get_option( 'rkdfaq' );
				
				$results = $wpdb->get_results( "SELECT * FROM " . $this->db_table . " ORDER BY id", ARRAY_A );
				
				$js_options = array();
				if( !$options['active'] ) {
					$js_options[] = 'active: false';
				}
				if( !$options['autoheight'] ) {
					$js_options[] = 'autoHeight: false';
				}
				if( $options['collapsible'] ) {
					$js_options[] = 'collapsible: true';
				}
				if( $js_options ) {
					$js_options = implode( ',', $js_options );
				} else {
					$js_options = '';
				}
				
				if( $results ) {
					$output = '<script type="text/javascript" charset="utf-8">jQuery(document).ready(function($){$("#rkdfaq").accordion({' . $js_options . '});});</script>';
					$output .= '<div id="rkdfaq">';
					foreach( $results as $question ) {
						$output .= '<div class="rkdfaq_question"><a href="#">' . $question['question'] . '</a></div>';
						$output .= '<div class="rkdfaq_answer">' . nl2br($question['answer']) . '</div>';
					}
					$output .= '</div>';
				} else {
					$output = '&nbsp;';
				}
				
				return $output;
			}
		}
	}
?>