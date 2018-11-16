<?php
class frontend_user_admin_mail {
	function __construct() {}
	function frontend_user_admin_mail_do() {
		global $frontend_user_admin;
		$_REQUEST['option'] = 'maillist';
		$frontend_user_admin->add_frontend_user_admin_admin();
	}
}
?>