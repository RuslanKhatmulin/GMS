<?php
/**
* @package wpWasiAdmin
*/
namespace Inc\Base;
class Activate{
	public static function activate(){
		flush_rewrite_rules();
		Model::wpWasiAdmin_install();
	}
}