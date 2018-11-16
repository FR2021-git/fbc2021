<?php
	global $current_user, $net_shop_admin, $Ktai_Style, $redirect_to;
	$options = $this->get_frontend_user_admin_data();
	
	if ( !empty($options['message_options']['message_login']) ) echo $this->EvalBuffer($options['message_options']['message_login']);

	if ( isset( $_REQUEST['redirect_to'] ) )
		$redirect_to = $_REQUEST['redirect_to'];

	// If cookies are disabled we can't log in even with a valid user+pass
	if ( isset($_POST['testcookie']) && empty($_COOKIE[TEST_COOKIE]) && empty($options['global_settings']['disable_cookieerror']) )
		$this->errors->add('test_cookie', __("<strong>ERROR</strong>: Cookies are blocked or not supported by your browser. You must <a href='http://www.google.com/cookies.html'>enable cookies</a> to use WordPress.", 'frontend-user-admin'));

	if ( !empty($options['notice_options']['notice_loggedout']) ) $notice_loggedout = $options['notice_options']['notice_loggedout'];
	else $notice_loggedout = __('You are now logged out.', 'frontend-user-admin');
	if ( !empty($options['notice_options']['notice_registerdisabled']) ) $notice_registerdisabled = $options['notice_options']['notice_registerdisabled'];
	else $notice_registerdisabled = __('User registration is currently not allowed.', 'frontend-user-admin');
	if ( !empty($options['notice_options']['notice_confirm']) ) $notice_confirm = $options['notice_options']['notice_confirm'];
	else $notice_confirm = __('Check your e-mail for the confirmation link.', 'frontend-user-admin');
	if ( !empty($options['notice_options']['notice_newpass']) ) $notice_newpass = $options['notice_options']['notice_newpass'];
	else $notice_newpass = __('Check your e-mail for your new password.', 'frontend-user-admin');
	if ( !empty($options['notice_options']['notice_registered']) ) $notice_registered = $options['notice_options']['notice_registered'];
	else $notice_registered = __('Registration complete. Please check your e-mail.', 'frontend-user-admin');
	if ( !empty($options['notice_options']['notice_registeredpass']) ) $notice_registeredpass = $options['notice_options']['notice_registeredpass'];
	else $notice_registeredpass = __('Registration complete. Please log in.', 'frontend-user-admin');
	if ( !empty($options['notice_options']['notice_confirmation']) ) $notice_confirmation = $options['notice_options']['notice_confirmation'];
	else $notice_confirmation = __('Please check your e-mail and click the link.', 'frontend-user-admin');
	if ( !empty($options['notice_options']['notice_approval']) ) $notice_approval = $options['notice_options']['notice_approval'];
	else $notice_approval = __('Currently under approval process. Please wait for the email from the site owner.', 'frontend-user-admin');
	if ( !empty($options['notice_options']['notice_invalidkey']) ) $notice_invalidkey = $options['notice_options']['notice_invalidkey'];
	else $notice_invalidkey = __('Sorry, that key does not appear to be valid.', 'frontend-user-admin');
	if ( !empty($options['notice_options']['notice_redirect_to']) ) $notice_redirect_to = $options['notice_options']['notice_redirect_to'];
	else $notice_redirect_to = __('Please log in to use the member service.', 'frontend-user-admin');
	if ( !empty($options['notice_options']['notice_withdrawal']) ) $notice_withdrawal = $options['notice_options']['notice_withdrawal'];
	else $notice_withdrawal = __('You were resigned from the site.', 'frontend-user-admin');
	if ( !empty($options['notice_options']['notice_duplicate']) ) $notice_duplicate = $options['notice_options']['notice_duplicate'];
	else $notice_duplicate = __('You are logged out because of the duplicate login.', 'frontend-user-admin');

	// Some parts of this script use the main login form to display a message
	if		( isset($_REQUEST['loggedout']) && TRUE == $_REQUEST['loggedout'] )$this->errors->add('loggedout', $notice_loggedout, 'message');
	elseif	( isset($_REQUEST['registration']) && 'disabled' == $_REQUEST['registration'] )	$this->errors->add('registerdisabled', $notice_registerdisabled, 'error');
	elseif	( isset($_REQUEST['checkemail']) && 'confirm' == $_REQUEST['checkemail'] )	$this->errors->add('confirm', $notice_confirm, 'message');
	elseif	( isset($_REQUEST['checkemail']) && 'newpass' == $_REQUEST['checkemail'] )	$this->errors->add('newpass', $notice_newpass, 'message');
	elseif	( isset($_REQUEST['checkemail']) && 'registered' == $_REQUEST['checkemail'] )	$this->errors->add('registered', $notice_registered, 'message');
	elseif	( isset($_REQUEST['checkemail']) && 'registered_pass' == $_REQUEST['checkemail'] )	$this->errors->add('registered', $notice_registeredpass, 'message');
	elseif	( isset($_REQUEST['checkemail']) && 'confirmation' == $_REQUEST['checkemail'] )	$this->errors->add('confirmation', $notice_confirmation, 'message');
	elseif	( isset($_REQUEST['checkemail']) && 'approval' == $_REQUEST['checkemail'] )	$this->errors->add('approval', $notice_approval, 'message');
	elseif	( isset($_REQUEST['checkemail']) && 'invalidkey' == $_REQUEST['checkemail'] ) $this->errors->add('invalidkey', $notice_invalidkey, 'error');
	elseif  ( isset($_REQUEST['redirect_to']) ) $this->errors->add('redirect_to', $notice_redirect_to, 'message');
	elseif  ( isset($_REQUEST['withdrawal']) ) $this->errors->add('withdrawal', $notice_withdrawal, 'message');
	elseif  ( isset($_REQUEST['duplicate']) ) $this->errors->add('duplicate', $notice_duplicate, 'message');
?>
<div class="frontend-user-admin-login">
<?php
	$this->login_header('', $this->errors);
?>
<form id="loginform" action="<?php if ( !empty($options['global_settings']['login_url']) ) echo $options['global_settings']['login_url']; ?>" method="post">
<?php if ( !isset($_REQUEST['checkemail']) || !in_array( $_REQUEST['checkemail'], array('confirm', 'newpass') ) ) : ?>
<?php if ( !empty($options['global_settings']['set_default_userlogin']) ) : ?>
<input type="hidden" name="log" id="user_login" class="user_login" value="<?php echo $options['global_settings']['set_default_userlogin']; ?>" />
<?php else : ?>
<p><label><?php if ( !empty($options['global_settings']['email_as_userlogin']) ) : _e('E-mail', 'frontend-user-admin'); else: _e('Username', 'frontend-user-admin'); endif; ?><br />
<input type="text" name="log" id="user_login" class="input imedisabled user_login" value="<?php if ( !empty($user_login) ) echo esc_attr($user_login); ?>" size="20" tabindex="10" /></label></p>
<?php endif; ?>
<?php if ( empty($options['global_settings']['use_common_password']) ) : ?>
<p><label><?php _e('Password', 'frontend-user-admin') ?><br />
<input type="password" name="pwd" id="user_pass" class="input user_pass" value="" size="20" tabindex="20" /></label></p>
<?php endif; ?>
<?php do_action('login_form'); ?>
<?php if ( empty($options['global_settings']['hide_rememberme']) ) : ?>
<?php if ( !function_exists('is_ktai') || (function_exists('is_ktai') && !is_ktai()) || (function_exists('is_ktai') && is_ktai() && $Ktai_Style->admin->base->ktai->get('cookie_available')) ) : ?>
<p class="forgetmenot"><label><input name="rememberme" type="checkbox" id="rememberme" value="forever" tabindex="90" checked="checked" /> <?php _e('Remember Me', 'frontend-user-admin'); ?></label></p>
<?php endif; ?>
<?php endif; ?>
<p class="submit">
<input type="submit" name="wp-submit" id="wp-submit" class="submit login" value="<?php _e('Log In &raquo;', 'frontend-user-admin'); ?>" tabindex="100" />
<input type="hidden" name="redirect_to" value="<?php if ( isset($redirect_to) ) echo esc_attr($redirect_to); ?>" />
<input type="hidden" name="testcookie" value="1" />
</p>
<?php else : ?>
<p>&nbsp;</p>
<?php endif; ?>
<?php
	if ( $net_shop_admin ) :
		$net_shop_admin->net_shop_admin_sid_field();
	endif;
?>
</form>

<?php if ( empty($options['global_settings']['disable_links']) ) : ?>
<?php if ( isset($_REQUEST['checkemail']) && in_array( $_REQUEST['checkemail'], array('confirm', 'newpass') ) ) : ?>
<?php elseif ( get_option('users_can_register') || !empty($options['global_settings']['users_can_register'])) : ?>
<ul>
<li><a href="<?php if ( !empty($options['global_settings']['login_url']) ) echo $this->return_frontend_user_admin_login_url(); ?>action=register"><?php _e('Register', 'frontend-user-admin') ?></a></li>
<?php if ( empty($options['global_settings']['disable_lostpassword']) ) : ?>
<li><a href="<?php if ( !empty($options['global_settings']['login_url']) ) echo $this->return_frontend_user_admin_login_url(); ?>action=lostpassword" title="<?php _e('Password Lost and Found', 'frontend-user-admin') ?>"><?php _e('Lost your password?', 'frontend-user-admin') ?></a></li>
<?php endif; ?>
</ul>
<?php elseif ( !empty($options['global_settings']['set_default_userlogin']) ) : ?>
<?php else : ?>
<?php if ( empty($options['global_settings']['disable_lostpassword']) ) : ?>
<ul>
<li><a href="<?php if ( !empty($options['global_settings']['login_url']) ) echo $this->return_frontend_user_admin_login_url(); ?>action=lostpassword" title="<?php _e('Password Lost and Found', 'frontend-user-admin') ?>"><?php _e('Lost your password?', 'frontend-user-admin') ?></a></li>
</ul>
<?php endif; ?>
<?php endif; ?>
<?php endif; ?>
</div>