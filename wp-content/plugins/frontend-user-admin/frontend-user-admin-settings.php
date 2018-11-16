<?php
class frontend_user_admin_settings {
	function __construct() {}
	function frontend_user_admin_settings_do() {
		global $frontend_user_admin;
		$_REQUEST['option'] = 'settings';
		$frontend_user_admin->add_frontend_user_admin_admin();
	}
}
?>