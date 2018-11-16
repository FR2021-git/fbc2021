<?php
		global $current_user, $net_shop_admin;
		$options = $this->get_frontend_user_admin_data();

		if( is_user_logged_in() ) {
			if ( !empty($options['global_settings']['howdy_message']) ) :
?>
<p><?php echo sprintf(__('Howdy, %1$s!', 'frontend-user-admin'), $current_user->display_name) ?></p>
<?php
			endif;
?>
<ul>
<?php
			if( !empty($options['widget_menu']) ):
				for ( $i=0; $i<count($options['widget_menu']); $i++ ) :
					if ( !isset($options['widget_menu'][$i]['widget_menu_user_level']) || (isset($options['widget_menu'][$i]['widget_menu_user_level']) && (int)$current_user->user_level >= (int)$options['widget_menu'][$i]['widget_menu_user_level']) ) :
?>
<li class="widget_menu<?php echo $i ?>"><a href="<?php echo preg_replace_callback('/%([a-zA-Z0-9_]+)%/', function ($m) use ($current_user) { return $current_user->{$m[1]}; }, $options['widget_menu'][$i]['widget_menu_url']); ?>"<?php if ( !empty($options['widget_menu'][$i]['widget_menu_blank']) ) echo ' target="_blank"'; ?>><?php echo esc_attr($options['widget_menu'][$i]['widget_menu_label']) ?></a></li>
<?php
					endif;
				endfor;
			endif;
			if ( empty($options['global_settings']['disable_profile']) ) :
?>
<li class="profile"><a href="<?php if ( !empty($options['global_settings']['login_url']) ) echo $this->return_frontend_user_admin_login_url(); ?>action=profile"><?php _e('Profile', 'frontend-user-admin'); ?></a></li>
<?php
			endif;
?>
<li class="logout"><a href="<?php if ( !empty($options['global_settings']['login_url']) ) echo $this->return_frontend_user_admin_login_url(); ?>action=logout"><?php _e('Log Out', 'frontend-user-admin'); ?></a></li>
</ul>
<?php
		} else {
			if ( $redirect_to = get_transient('fua_redirect_to') ) :
				delete_transient('fua_redirect_to');
			elseif ( !empty($options['global_settings']['transfer_all_to_login']) && !empty($options['global_settings']['after_login_url']) ) :
				$redirect_to = $options['global_settings']['after_login_url'];
			else :
				$redirect_to = $this->get_permalink();
			endif;
?>
<form id="loginform" action="<?php echo $options['global_settings']['login_url']; ?>" method="post">
<p><label><?php if ( !empty($options['global_settings']['email_as_userlogin']) ) : _e('E-mail', 'frontend-user-admin'); else: _e('Username', 'frontend-user-admin'); endif; ?><br />
<input type="text" name="log" id="widget_user_login" class="input" value="<?php if ( !empty($user_login) ) echo esc_attr($user_login); ?>" size="20" /></label></p>
<?php if ( empty($options['global_settings']['use_common_password']) ) : ?>
<p><label><?php _e('Password', 'frontend-user-admin') ?><br />
<input type="password" name="pwd" id="widget_user_pass" class="input" value="" size="20" /></label></p>
<?php endif; ?>
<?php do_action('login_form'); ?>
<?php if ( empty($options['global_settings']['hide_rememberme']) ) : ?>
<p class="forgetmenot"><label><input name="rememberme" type="checkbox" id="widget_rememberme" value="forever" checked="checked" /> <?php _e('Remember Me', 'frontend-user-admin'); ?></label></p>
<?php endif; ?>
<p class="submit">
<input type="submit" name="wp-submit" id="wp-submit" class="submit login" value="<?php _e('Log In &raquo;', 'frontend-user-admin'); ?>" />
<input type="hidden" name="redirect_to" value="<?php echo $redirect_to; ?>" />
<input type="hidden" name="testcookie" value="1" />
</p>
<?php
			if ( $net_shop_admin ) :
				$net_shop_admin->net_shop_admin_sid_field();
			endif;
?>
</form>

<?php
			if ( !empty($options['widget_menu']) || get_option('users_can_register') || !empty($options['global_settings']['users_can_register']) || empty($options['global_settings']['disable_lostpassword']) ) :
?>
<ul>
<?php
				if( !empty($options['widget_menu']) ):
					for ( $i=0; $i<count($options['widget_menu']); $i++ ) :
						if ( !empty($options['widget_menu'][$i]['widget_menu_open']) ) :
?>
<li class="widget_menu<?php echo $i ?>"><a href="<?php echo preg_replace_callback('/%([a-zA-Z0-9_]+)%/', function ($m) use ($current_user) { return $current_user->{$m[1]}; }, $options['widget_menu'][$i]['widget_menu_url']); ?>"<?php if ($options['widget_menu'][$i]['widget_menu_blank']) echo ' target="_blank"'; ?>><?php echo esc_attr($options['widget_menu'][$i]['widget_menu_label']) ?></a></li>
<?php
						endif;
					endfor;
				endif;

				if ( get_option('users_can_register') || !empty($options['global_settings']['users_can_register']) ) :
?>
<li class="register"><a href="<?php if ( !empty($options['global_settings']['login_url']) ) echo $this->return_frontend_user_admin_login_url(); ?>action=register"><?php _e('Register', 'frontend-user-admin') ?></a></li>
<?php
				endif;

				if ( empty($options['global_settings']['disable_lostpassword']) ) :
?>
<li class="lostpassword"><a href="<?php if ( !empty($options['global_settings']['login_url']) ) echo $this->return_frontend_user_admin_login_url(); ?>action=lostpassword" title="<?php _e('Password Lost and Found', 'frontend-user-admin') ?>"><?php _e('Lost your password?', 'frontend-user-admin') ?></a></li>
<?php
				endif;
?>
</ul>
<?php
			endif;
		}
?>