<?php
/**
*
* Trigger this file on Plugin uninstall
*
* @package wpWashiAdmin
*/

if(! defined('WP_UNINSTALL_PLUGIN')){
	die;
}
global $wpdb;
$wpWashiAdmin_table = $wpdb->prefix . "wpWashiAdmin";
$wpWashiAdmin_comments_table = $wpdb->prefix . "wpWashiAdmin_comments";
$wpWashiAdmin_email_table = $wpdb->prefix . "wpWashiAdmin_email";
$tables = array($wpWashiAdmin_table,$wpWashiAdmin_comments_table, $wpWashiAdmin_email_table);
	foreach ($tables as $table) {
		$wpdb->query("DROP TABLE IF EXISTS `$table`");
	}