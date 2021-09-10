<?php
/**
* @package wpWasiAdmin
*/
namespace Inc\Base;

use \Inc\Base\BaseController;
use \Inc\Pages\Admin;

class Model extends BaseController
{
	private static $wpdb;

	public function __construct(){
		global $wpdb;
		self::$wpdb = $wpdb;
	}
/**
 * Creating database tables
 */
	public static function wpWasiAdmin_install() {
		// where and what we will store - db structure
		$wpWasiAdmin_table = self::$wpdb->prefix . "wpWasiAdmin";
		$wpWasiAdmin_comments_table = self::$wpdb->prefix . "wpWasiAdmin_comments";
		$wpWasiAdmin_email_table = self::$wpdb->prefix . "wpWasiAdmin_email";
		$wpWasiAdmin_structure = "
		CREATE TABLE IF NOT EXISTS `$wpWasiAdmin_table` (
			`id` BIGINT( 20 ) UNSIGNED NOT NULL AUTO_INCREMENT ,
			`date` DATE NOT NULL ,
			`title` TEXT NOT NULL ,
			`desc` TEXT NOT NULL ,
			`from` BIGINT( 20 ) UNSIGNED NOT NULL ,
			`for` BIGINT( 20 ) UNSIGNED NOT NULL DEFAULT '0',
			`until` DATE NOT NULL ,
			`status` TINYINT( 1 ) NOT NULL DEFAULT '0',
			`priority` TINYINT( 1 ) NOT NULL DEFAULT '0',
			`notify` BINARY NOT NULL DEFAULT '0',
			PRIMARY KEY ( `id` )
		) ENGINE = MYISAM CHARACTER SET utf8 COLLATE utf8_general_ci";
		
		$wpWasiAdmin_comments_structure = "
		CREATE TABLE IF NOT EXISTS `$wpWasiAdmin_comments_table` (
			`id` BIGINT( 20 ) UNSIGNED NOT NULL AUTO_INCREMENT ,
			`date` DATE NOT NULL ,
			`task` BIGINT( 20 ) UNSIGNED NOT NULL ,
			`body` TEXT NOT NULL ,
			`from` BIGINT( 20 ) UNSIGNED NOT NULL ,
			PRIMARY KEY ( `id` )
		) ENGINE = MYISAM CHARACTER SET utf8 COLLATE utf8_general_ci";

		$wpWasiAdmin_email_structure = "
		CREATE TABLE IF NOT EXISTS `$wpWasiAdmin_email_table` (
			`id` BIGINT( 20 ) UNSIGNED NOT NULL AUTO_INCREMENT ,
			`subject` TEXT NOT NULL,
			`body` TEXT NOT NULL ,
			PRIMARY KEY ( `id` )
		) ENGINE = MYISAM CHARACTER SET utf8 COLLATE utf8_general_ci";
		
		// Sending all this to mysql queries
		self::$wpdb->query($wpWasiAdmin_structure);
		self::$wpdb->query($wpWasiAdmin_comments_structure);
		self::$wpdb->query($wpWasiAdmin_email_structure);
		$today_date = gmdate('Y-m-d');

		//sample email template
		self::$wpdb->query("INSERT INTO `$wpWasiAdmin_email_table` (`subject`, `body`)
		VALUES('Notification','Hi, One task has been updated on your website!')");
	}
	/**
	 * Users id -> nicename
	 */
	public static function wpWasiAdmin_from($raw_from) {
		if(is_int($raw_from) && ($raw_from != '0')) {
			$from = get_userdata($raw_from);
			return $from->display_name;
		}
		else if (is_string($raw_from)) {
			$from = get_userdata($raw_from);
			return $from->ID;
		}
		else return "Nobody";
	}

	/**
	 * Users email
	 */
	public static function wpWasiAdmin_to(int $id) {
		if(is_int($id) && ($id != '0')) {
			$to = get_userdata($id);
			return $to->user_email;
		}
	}
	/**
	 * saving email messages into database
	 */
	public static function wpWasiAdmin_message_retrieve(){
		$wpWasiAdmin_email_table = self::$wpdb->prefix . "wpWasiAdmin_email";
		$wpWasiAdmin_query = "SELECT `subject`, `body` FROM $wpWasiAdmin_email_table";
		$results = self::$wpdb->get_results($wpWasiAdmin_query);
		if($results){
			return $results; 
		}
	}
	/**
	 * saving email messages into database
	 */
	public static function wpWasiAdmin_message(array $email){
			$wpWasiAdmin_email_table = self::$wpdb->prefix . "wpWasiAdmin_email";
			$wpWasiAdmin_query = "UPDATE `".$wpWasiAdmin_email_table."` SET `subject` = '".$email['subject']."', `body` = '".$email['body']."'";
			self::$wpdb->query($wpWasiAdmin_query);
	}
	/**
	 * send email
	 */
	public static function wpWasiAdmin_email(int $from = null, int $for = null){
		//get subject and body
		$results = self::wpWasiAdmin_message_retrieve();
		foreach($results as $result){
			$subject = __($result->subject,'wpWasiAdmin');
			$message = __($result->body, 'wpWasiAdmin' );
		}
		//recepient array
		$admin = get_option('admin_email');
		if(isset($_POST['wpWasiAdmin_notify'])){
			$editor = self::wpWasiAdmin_to($_POST['wpWasiAdmin_for']);
			$tos = array($admin,$editor);
		}else if(isset($for) && isset($from)){
			$assigned_to = self::wpWasiAdmin_to($for);
			$created_by = self::wpWasiAdmin_to($from);
			$tos = array($admin,$assigned_to, $created_by);
		}else{
			$tos = array($admin);
		}
		$headers = array('Content-Type: text/html; charset=UTF-8');
		require_once(parent::$plugin_path . 'templates/email.php');
		//send notificaiton to each of the recepient
		foreach($tos as $to){
			wp_mail( $to, $subject, $html, $headers );
		}
	}
	/**
	 * Displaying a nicer date
	 */
	public static function wpWasiAdmin_date($raw_date) {
		if($raw_date != "0000-00-00") {
			return mysql2date(get_option('date_format'), $raw_date); //Let's use wordpress prefered date settings
		}
		else return "Not set";
	}

	/**
	 * Displaying a nicer status
	 */
	public static function wpWasiAdmin_status($raw_status) {
		switch ($raw_status) {
		default: return "New";
		//case 1: return "New";
		case 2: return "Open";
		case 3: return "Buggy";
		case 4: return "Solved";
		case 5: return "Closed";
		}
	}

	/**
	 * Displaying a nicer priority
	 */
	public static function wpWasiAdmin_priority($raw_priority) {
		switch ($raw_priority) {
		default: return "Low";
		//case 1: return "Low";
		case 2: return "Normal";
		case 3: return "High";
		case 4: return "Important";
		}
	}

	/**
	 * Displaying a nicer notice
	 */
	public static function wpWasiAdmin_notice($raw_notice) {
		switch ($raw_notice) {
		default: return "No";
		case 1: return "Yes";
		}
	}

	/**
	 * Add a task to db
	 */
	public static function wpWasiAdmin_addtask(array $newdata) {
		$wpWasiAdmin_table = self::$wpdb->prefix . "wpWasiAdmin";
		$today_date = gmdate('Y-m-d');
		$wpWasiAdmin_query = "INSERT INTO `".$wpWasiAdmin_table."` (`id`, `date`, `title`, `desc`, `from`, `for`, `until`,`status`,`priority`,`notify`)VALUES (NULL , '$today_date', '".$newdata['wpWasiAdmin_title']."','".$newdata['wpWasiAdmin_description']."','".$newdata['wpWasiAdmin_from']."','".$newdata['wpWasiAdmin_for']."','".$newdata['wpWasiAdmin_deadline']."','".$newdata['wpWasiAdmin_status']."','".$newdata['wpWasiAdmin_priority']."','".!empty($newdata['wpWasiAdmin_notify'])."')";
		self::$wpdb->query($wpWasiAdmin_query);
		self::wpWasiAdmin_email();
	}

	/**
	 * Update a task
	 */
	public static function wpWasiAdmin_updatetask(array $newdata) {
		$wpWasiAdmin_table = self::$wpdb->prefix . "wpWasiAdmin";
		$wpWasiAdmin_query = "UPDATE `".$wpWasiAdmin_table."` SET `title`='".$newdata['wpWasiAdmin_title']."', `desc`='".$newdata['wpWasiAdmin_description']."', `for`='".$newdata['wpWasiAdmin_for']."', `until`='".$newdata['wpWasiAdmin_deadline']."', `status`='".$newdata['wpWasiAdmin_status']."', `priority`='".$newdata['wpWasiAdmin_priority']."', `notify`='".!empty($newdata['wpWasiAdmin_notify'])."' WHERE `id`='".$newdata['wpWasiAdmin_taskid']."'";
		self::$wpdb->query($wpWasiAdmin_query);
		self::wpWasiAdmin_email();

		//echo '<script>window.location.href="?page=wp-todo"</script>';
	}
	/**
	 * Delete a task
	 */
	public static function wpWasiAdmin_deletetask(int $id) {
		if(isset($id)){
			$wpWasiAdmin_table = self::$wpdb->prefix . "wpWasiAdmin";
			$wpWasiAdmin_comments_table = self::$wpdb->prefix . "wpWasiAdmin_comments";
			$q = self::$wpdb->query("DELETE FROM `".$wpWasiAdmin_table."` WHERE `id`=$id");
			self::$wpdb->query("DELETE FROM `".$wpWasiAdmin_comments_table."` WHERE `task`=$id");
			echo '<script>window.location.href="?page=wp-todo"</script>';
		}
	}
	/**
	 * Add a comment
	 */
	public static function wpWasiAdmin_addcomment(array $newdata) {
		$wpWasiAdmin_comments_table = self::$wpdb->prefix . "wpWasiAdmin_comments";
		$today_date = gmdate('Y-m-d');
		self::$wpdb->query("INSERT INTO $wpWasiAdmin_comments_table(`id`, `date`, `task`, `body`, `from`)
		VALUES(NULL, '$today_date', '".$newdata['wpWasiAdmin_comment_task']."', '".$newdata['wpWasiAdmin_comment_body']."', '".$newdata['wpWasiAdmin_comment_author']."')");

	}
	/**
	 * Edit a task
	 */
	public static function wpWasiAdmin_edit(int $id) {
		if(isset($id) && !empty($id)){
			$wpWasiAdmin_table = self::$wpdb->prefix . "wpWasiAdmin";
			$wpWasiAdmin_edit_item = self::$wpdb->get_results("SELECT * FROM `$wpWasiAdmin_table` WHERE `id`=$id");
			if(!$wpWasiAdmin_edit_item) {
				echo'<div class="wrap"><h2>There is no such task to edit. Please add one first.</h2></div>';
			}
			else {
				require_once(parent::$plugin_path . 'templates/edit_task.php');
		 	}
		}
	}
	/**
	 * View a task
	 */
	public static function wpWasiAdmin_view(int $id) {
		if(isset($id) && !empty($id)){
			$wpWasiAdmin_table = self::$wpdb->prefix . "wpWasiAdmin";
			$wpWasiAdmin_comments_table = self::$wpdb->prefix . "wpWasiAdmin_comments";
			$wpWasiAdmin_view_item = self::$wpdb->get_results("SELECT * FROM `$wpWasiAdmin_table` WHERE `id`=$id");
			$wpWasiAdmin_view_item_comments = self::$wpdb->get_results("SELECT * FROM `$wpWasiAdmin_comments_table` WHERE `task`=$id");
			if(!$wpWasiAdmin_view_item) {
				echo'<div class="wrap"><h2>There is no such task to view. Please add one first.</h2></div>';
			}else{
				require_once(parent::$plugin_path . 'templates/view_task.php');
			}
		}
	}
	/**
	 * Main admin page
	 */
	public static function wpWasiAdmin_manage_main(/*$wpWasiAdmin_filter_status*/) {
		$wpWasiAdmin_table = self::$wpdb->prefix . "wpWasiAdmin";
		require_once(parent::$plugin_path . 'templates/admin.php');
	}
	/**
	 * Admin CP manage page
	 */
	public static function wpWasiAdmin_manage() {
		$wpWasiAdmin_table = self::$wpdb->prefix . "wpWasiAdmin";
		if(isset($_POST['wpWasiAdmin_addtask']) && isset($_POST['wpWasiAdmin_title'])) self::wpWasiAdmin_addtask($_POST); //If we have a new task let's add it
		if(isset($_POST['wpWasiAdmin_updatetask'])) self::wpWasiAdmin_updatetask($_POST); //Update my task
		if(isset($_POST['wpWasiAdmin_comment_task'])) self::wpWasiAdmin_addcomment($_POST); //Add comments to tasks
		//if(isset($_POST['wpWasiAdmin_filter_status']) != NULL) self::wpWasiAdmin_manage_main($_POST['wpWasiAdmin_filter_status']); 
		if(isset($_POST['wpWasiAdmin_deletetask'])) self::wpWasiAdmin_deletetask($_POST['wpWasiAdmin_taskid']); //Update my task
		if(isset($_GET['view'])) self::wpWasiAdmin_view($_GET['view']);
		else if(isset($_GET['edit'])) self::wpWasiAdmin_edit($_GET['edit']);
		else self::wpWasiAdmin_manage_main();
	}
	public static function wpWasiAdmin_settings(){
		require_once(parent::$plugin_path . 'templates/settings.php');
	}
	// redirect to tasks
	public static function wpWasiAdmin_edit_task(int $id){
		$edit = '';
		//$role = Admin::get_role();
		//if($role == 'administrator' || $role == 'editor'){
			$edit = '<a href="?page=wp-todo&edit='.$id.'" >Edit</a>';
		//}
		return $edit;
	}

	public static function wpWasiAdmin_tasks(){
		$wpWasiAdmin_table = self::$wpdb->prefix . "wpWasiAdmin";
		$wpWasiAdmin_manage_items = self::$wpdb->get_results("SELECT * FROM $wpWasiAdmin_table ORDER BY `priority` DESC");
		$wpWasiAdmin_counted = count($wpWasiAdmin_manage_items);
			$num = 0;
				while($num != $wpWasiAdmin_counted) {
					switch ($wpWasiAdmin_manage_items[$num]->status) {
						case 4:
								echo "<tr class='success'>";
							  	echo "<td>".$wpWasiAdmin_manage_items[$num]->id."</td>";
							  	echo "<td><span style=\"float:right; display: inline;\">".self::wpWasiAdmin_edit_task($wpWasiAdmin_manage_items[$num]->id)."</span><a href=\"?page=wp-todo&view=".$wpWasiAdmin_manage_items[$num]->id."\">".$wpWasiAdmin_manage_items[$num]->title."</a></td>";
							break;
						case 5:
								echo "<tr class= 'info'>";
							  	echo "<td>".$wpWasiAdmin_manage_items[$num]->id."</td>";
							  	echo "<td><span style=\"float:right; display: inline;\">".self::wpWasiAdmin_edit_task($wpWasiAdmin_manage_items[$num]->id). "</span><a href=\"?page=wp-todo&view=".$wpWasiAdmin_manage_items[$num]->id."\">".$wpWasiAdmin_manage_items[$num]->title."</a></td>";
							break;
						default:
							echo "<tr>";
						  	echo "<td>".$wpWasiAdmin_manage_items[$num]->id."</td>";
						  	echo "<td><span  style=\"float:right; display: inline;\">".self::wpWasiAdmin_edit_task($wpWasiAdmin_manage_items[$num]->id). "</span><a href=\"?page=wp-todo&view=".$wpWasiAdmin_manage_items[$num]->id."\">".$wpWasiAdmin_manage_items[$num]->title."</a></td>";
							break;
					}

				  	echo "<td>".self::wpWasiAdmin_from((int)$wpWasiAdmin_manage_items[$num]->from)."</td>"; //we have to send int not strings
				  	echo "<td>".self::wpWasiAdmin_from((int)$wpWasiAdmin_manage_items[$num]->for)."</td>";
					echo "<td>".self::wpWasiAdmin_date($wpWasiAdmin_manage_items[$num]->date)."</td>";
				  	echo "<td>".self::wpWasiAdmin_date($wpWasiAdmin_manage_items[$num]->until)."</td>";
				  	echo "<td>".self::wpWasiAdmin_status($wpWasiAdmin_manage_items[$num]->status)."</td>";
				  	echo "<td>".self::wpWasiAdmin_priority($wpWasiAdmin_manage_items[$num]->priority)."</td>";
				  	echo "<td>".self::wpWasiAdmin_notice($wpWasiAdmin_manage_items[$num]->notify)."</td>";
				  	echo "</tr>";
				  	echo "";
				  	$num++;
				}
	}	
}