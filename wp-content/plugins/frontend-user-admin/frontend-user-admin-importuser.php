<?php
class frontend_user_admin_importuser {
	function __construct() {}
	function frontend_user_admin_importuser_do() {
		global $frontend_user_admin;
		$_REQUEST['option'] = 'importuser';
		$frontend_user_admin->add_frontend_user_admin_admin();
	}
}
?>