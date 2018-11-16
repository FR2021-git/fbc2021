<?php
class frontend_user_admin_adduser {
	function __construct() {}
	function frontend_user_admin_adduser_do() {
		global $frontend_user_admin;
		$_REQUEST['option'] = 'adduser';
		$frontend_user_admin->add_frontend_user_admin_admin();
	}
}
?>