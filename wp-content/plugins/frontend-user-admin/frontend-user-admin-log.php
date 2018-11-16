<?php
class frontend_user_admin_log {
	function __construct() {}
	function frontend_user_admin_log_do() {
		global $frontend_user_admin;
		if ( !isset($_REQUEST['option']) ) $_REQUEST['option'] = 'log';
		$frontend_user_admin->add_frontend_user_admin_admin();
	}
}
?>