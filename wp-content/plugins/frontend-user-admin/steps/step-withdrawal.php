<?php
	global $current_user, $net_shop_admin;
	$options = $this->get_frontend_user_admin_data();
		
	if ( !empty($options['message_options']['message_withdrawal']) ) echo $this->EvalBuffer($options['message_options']['message_withdrawal']);
?>
<div class="frontend-user-admin-login">
<form name="withdrawalform" id="withdrawalform" action="<?php if ( !empty($options['global_settings']['login_url']) ) echo $this->return_frontend_user_admin_login_url(); ?>action=withdrawal" method="post" onsubmit="return confirm('<?php _e('Are you sure to resign from the site? All your data will be deleted.', 'frontend-user-admin'); ?>');">
<?php wp_nonce_field('delete-user_' . $current_user->ID) ?>
<p class="submit"><input type="submit" name="wp-submit" id="wp-submit" value="<?php _e('Delete my account &raquo;', 'frontend-user-admin'); ?>" tabindex="100" class="submit withdrawal_form" /></p>
<?php
if ( function_exists('is_ktai') && is_ktai() ) :
	$Ktai_Style->admin->sid_field();
endif;
?>
</form>
</div>
