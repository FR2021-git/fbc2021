<?php
	global $current_user, $net_shop_admin;
	$options = $this->get_frontend_user_admin_data();
		
	if ( !empty($options['message_options']['message_lostpassword']) ) echo $this->EvalBuffer($options['message_options']['message_lostpassword']);
	if ( !empty($_REQUEST['error']) && 'invalidkey' == $_REQUEST['error'] ) $this->errors->add('invalidkey', __('Sorry, that key does not appear to be valid.', 'frontend-user-admin'));
	do_action('lost_password');
?>
<div class="frontend-user-admin-login">
<?php
	if( !empty($options['global_settings']['default_messages']) ) :
		if ( !empty($options['notice_options']['notice_passretrieve']) ) $notice_passretrieve = $options['notice_options']['notice_passretrieve'];
		else $notice_passretrieve = __('Please enter your username or e-mail address. You will receive a new password via e-mail.', 'frontend-user-admin');

		$this->login_header('<div class="message">' . $notice_passretrieve . '</div>', $this->errors);
	else :
		$this->login_header('', $this->errors);
	endif;

?>
<form name="lostpasswordform" id="lostpasswordform" action="<?php if ( !empty($options['global_settings']['login_url']) ) echo $this->return_frontend_user_admin_login_url(); ?>action=lostpassword" method="post">
<dl>
<dt><label><?php if ( !empty($options['global_settings']['email_as_userlogin']) ) : _e('E-mail', 'frontend-user-admin'); else: _e('Username or E-mail', 'frontend-user-admin'); endif; ?></label></dt>
<dd><input type="text" name="user_login" id="user_login" class="input" value="<?php echo isset($_POST['user_login']) ? esc_attr($_POST['user_login']) : ''; ?>" size="20" /></dd>
<?php
		$user_attribute_count = (isset($options['user_attribute']['user_attribute']) && is_array($options['user_attribute']['user_attribute'])) ? count($options['user_attribute']['user_attribute']) : 0;
		for($i=0;$i<$user_attribute_count;$i++) :
			if ( empty($options['user_attribute']['user_attribute'][$i]['retrieve_password']) ) continue; 
?>
<dt><label for="<?php echo $options['user_attribute']['user_attribute'][$i]['name']; ?>"><?php echo $options['user_attribute']['user_attribute'][$i]['label']; ?></label></dt>
<dd>
<?php		switch($options['user_attribute']['user_attribute'][$i]['type']) :
				case 'text':
?>
<input type="text" name="<?php echo $options['user_attribute']['user_attribute'][$i]['name']; ?>" id="<?php echo $options['user_attribute']['user_attribute'][$i]['name']; ?>" value="<?php if($_POST[$options['user_attribute']['user_attribute'][$i]['name']]) : echo esc_attr($_POST[$options['user_attribute']['user_attribute'][$i]['name']]); else : echo esc_attr($options['user_attribute']['user_attribute'][$i]['default']); endif; ?>" class="input <?php echo $options['user_attribute']['user_attribute'][$i]['name']; ?>" /> 
 <?php if( !empty($options['user_attribute']['user_attribute'][$i]['comment']) ) echo '<br /><span class="frontend-user-admin-user-attribute-comment">'.esc_attr($options['user_attribute']['user_attribute'][$i]['comment']).'</span>';
 				break;
				case 'textarea': ?>
<textarea name="<?php echo esc_attr($options['user_attribute']['user_attribute'][$i]['name']); ?>" id="<?php echo $options['user_attribute']['user_attribute'][$i]['name']; ?>" class="textarea <?php echo $options['user_attribute']['user_attribute'][$i]['name']; ?>"><?php if($_POST[$options['user_attribute']['user_attribute'][$i]['name']]) : echo $_POST[$options['user_attribute']['user_attribute'][$i]['name']]; else : echo esc_attr($options['user_attribute']['user_attribute'][$i]['default']); endif; ?></textarea>
 <?php if( !empty($options['user_attribute']['user_attribute'][$i]['comment']) ) echo '<br /><span class="frontend-user-admin-user-attribute-comment">'.esc_attr($options['user_attribute']['user_attribute'][$i]['comment']).'</span>'; ?>
<?php			break;
				case 'select':
					preg_match_all('/"[^"]*"d?|\'[^\']*\'d?/', $options['user_attribute']['user_attribute'][$i]['default'], $matches);
?>
<select name="<?php echo $options['user_attribute']['user_attribute'][$i]['name']; ?>" id="<?php echo $options['user_attribute']['user_attribute'][$i]['name']; ?>" class="select <?php echo $options['user_attribute']['user_attribute'][$i]['name']; ?>">
<?php				foreach($matches[0] as $select_val) :
						unset($default);
						if(preg_match('/d$/', $select_val) && !$_POST[$options['user_attribute']['user_attribute'][$i]['name']]) {
							$default = true;
						}
						$select_val = rtrim($select_val,'d');
						$select_val = rtrim(trim($select_val,'"|\''),'"|\'');
						if ( preg_match('/([^\|]*)\|([^\|]*)/', $select_val, $select_val2 ) ) :
							$label = $select_val2[1];
							$value = $select_val2[2];
						else:
							$label = $select_val;
							$value = $select_val;
						endif;?>
<option value="<?php echo esc_attr($value); ?>"<?php if( (isset($_POST[$options['user_attribute']['user_attribute'][$i]['name']]) && $value == $_POST[$options['user_attribute']['user_attribute'][$i]['name']]) || !empty($default) ) echo ' selected="selected"'; ?>><?php echo $label; ?></option>
<?php				endforeach; ?>
</select>
 <?php if($options['user_attribute']['user_attribute'][$i]['comment']) echo '<br /><span class="frontend-user-admin-user-attribute-comment">'.esc_attr($options['user_attribute']['user_attribute'][$i]['comment']).'</span>'; ?>
<?php			break;      
				case 'checkbox':
					preg_match_all('/"[^"]*"d?|\'[^\']*\'d?/', $options['user_attribute']['user_attribute'][$i]['default'], $matches);									
					if(isset($_POST[$options['user_attribute']['user_attribute'][$i]['name']])) $_POST[$options['user_attribute']['user_attribute'][$i]['name']] = maybe_unserialize($_POST[$options['user_attribute']['user_attribute'][$i]['name']]);
					foreach($matches[0] as $select_val) :
						unset($default);
						if(preg_match('/d$/', $select_val) && !is_array($_POST[$options['user_attribute']['user_attribute'][$i]['name']])) {
							$default = true;
						}
						$select_val = rtrim($select_val,'d');
						$select_val = rtrim(trim($select_val,'"|\''),'"|\'');
						if ( preg_match('/([^\|]*)\|([^\|]*)/', $select_val, $select_val2 ) ) :
							$label = $select_val2[1];
							$value = $select_val2[2];
						else:
							$label = $select_val;
							$value = $select_val;
						endif;?>
<input type="checkbox" name="<?php echo $options['user_attribute']['user_attribute'][$i]['name']; ?>[]" class="checkbox <?php echo $options['user_attribute']['user_attribute'][$i]['name']; ?>" value="<?php echo esc_attr($value); ?>"<?php if((is_array($_POST[$options['user_attribute']['user_attribute'][$i]['name']]) && in_array($value, $_POST[$options['user_attribute']['user_attribute'][$i]['name']])) || !empty($default) ) echo ' checked="checked"';?> /> <?php echo $label; ?> 
<?php				endforeach; ?>
 <?php if($options['user_attribute']['user_attribute'][$i]['comment']) echo '<br /><span class="frontend-user-admin-user-attribute-comment">'.esc_attr($options['user_attribute']['user_attribute'][$i]['comment']).'</span>'; ?>
<?php			break;                                                                          
				case 'radio':
					preg_match_all('/"[^"]*"d?|\'[^\']*\'d?/', $options['user_attribute']['user_attribute'][$i]['default'], $matches);
										?>
<?php				foreach($matches[0] as $select_val) :
						unset($default);
						if(preg_match('/d$/', $select_val) && !$_POST[$options['user_attribute']['user_attribute'][$i]['name']]) {
							$default = true;
						}
						$select_val = rtrim($select_val,'d');
						$select_val = rtrim(trim($select_val,'"|\''),'"|\'');
						if ( preg_match('/([^\|]*)\|([^\|]*)/', $select_val, $select_val2 ) ) :
							$label = $select_val2[1];
							$value = $select_val2[2];
						else:
							$label = $select_val;
							$value = $select_val;
						endif;?>
<input type="radio" name="<?php echo $options['user_attribute']['user_attribute'][$i]['name']; ?>" class="radio <?php echo $options['user_attribute']['user_attribute'][$i]['name']; ?>" value="<?php echo esc_attr($value); ?>"<?php if($value == $_POST[$options['user_attribute']['user_attribute'][$i]['name']] || !empty($default) ) echo ' checked="checked"';?> /> <?php echo $label; ?> 
<?php				endforeach; ?>
 <?php if( !empty($options['user_attribute']['user_attribute'][$i]['comment']) ) echo '<br /><span class="frontend-user-admin-user-attribute-comment">'.esc_attr($options['user_attribute']['user_attribute'][$i]['comment']).'</span>'; ?>
<?php			break;
				case 'datetime':
					list($profile_year, $profile_month, $profile_day) = split('-', $_POST[$options['user_attribute']['user_attribute'][$i]['name']]);
					$year = date('Y');
					$month = date('m');
					$day = date('d');
					if($options['user_attribute']['user_attribute'][$i]['default']) :
						$tmp_year = '<select name="'.$options['user_attribute']['user_attribute'][$i]['name'].'_year" id="'.$options['user_attribute']['user_attribute'][$i]['name'].'_year_edit"><option value=""></option>';
						for($j=1900;$j<($year+10);$j++) {
							if($j == $profile_year) {
								$tmp_year .= '<option value="'.$j.'" selected="selected">'.$j.'</option>';
							} else {
								$tmp_year .= '<option value="'.$j.'">'.$j.'</option>';
							}
						}
						$tmp_year .= '</select>';

						$tmp_month = '<select name="'.$options['user_attribute']['user_attribute'][$i]['name'].'_month" id="'.$options['user_attribute']['user_attribute'][$i]['name'].'_month_edit"><option value=""></option>';
						for($j=1;$j<13;$j++) {
							if($j == $profile_month) {
								$tmp_month .= '<option value="'.$j.'" selected="selected">'.$j.'</option>';
							} else {
								$tmp_month .= '<option value="'.$j.'">'.$j.'</option>';
							}
						}
						$tmp_month .= '</select>';

						$tmp_day = '<select name="'.$options['user_attribute']['user_attribute'][$i]['name'].'_day" id="'.$options['user_attribute']['user_attribute'][$i]['name'].'_day_edit"><option value=""></option>';
						for($j=1;$j<32;$j++) {
							if($j == $profile_day) {
								$tmp_day .= '<option value="'.$j.'" selected="selected">'.$j.'</option>';
							} else {
								$tmp_day .= '<option value="'.$j.'">'.$j.'</option>';
							}
						}
						$tmp_day .= '</select>';

						$tmp_hour = '<select name="'.$options['user_attribute']['user_attribute'][$i]['name'].'_hour" id="'.$options['user_attribute']['user_attribute'][$i]['name'].'_hour_edit"><option value=""></option>';
						for($j=0;$j<24;$j++) {
							if($profile_hour != '' && $j == trim($profile_hour)) {
								$tmp_hour .= '<option value="'.$j.'" selected="selected">'.$j.'</option>';
							} else {
								$tmp_hour .= '<option value="'.$j.'">'.$j.'</option>';
							}
						}
						$tmp_hour .= '</select>';

						$tmp_minute = '<select name="'.$options['user_attribute']['user_attribute'][$i]['name'].'_minute" id="'.$options['user_attribute']['user_attribute'][$i]['name'].'_minute_edit"><option value=""></option>';
						for($j=0;$j<60;$j++) {
							if($profile_minute != '' &&$j == trim($profile_minute)) {
								$tmp_minute .= '<option value="'.$j.'" selected="selected">'.$j.'</option>';
							} else {
								$tmp_minute .= '<option value="'.$j.'">'.$j.'</option>';
							}
						}
						$tmp_minute .= '</select>';
	
						$replacements = array('yyyy'=>$tmp_year,'mm'=>$tmp_month,'dd'=>$tmp_day,'hh'=>$tmp_hour,'ii'=>$tmp_minute);
						$options['user_attribute']['user_attribute'][$i]['default'] = strtr($options['user_attribute']['user_attribute'][$i]['default'], $replacements);
																				
						echo $options['user_attribute']['user_attribute'][$i]['default'];
						if($options['user_attribute']['user_attribute'][$i]['comment']) echo '<br /><span class="frontend-user-admin-user-attribute-comment">'.esc_attr($options['user_attribute']['user_attribute'][$i]['comment']).'</span>';
					endif;
				break;                                                                        
			endswitch;
?></dd><?php
		endfor;
?>
</dl>
<?php do_action('lostpassword_form', 'frontend-user-admin'); ?>
<p class="submit"><input type="submit" name="wp-submit" id="wp-submit" value="<?php _e('Get New Password &raquo;', 'frontend-user-admin'); ?>" tabindex="100" class="submit lostpassword_form" /></p>
<?php
	if ( $net_shop_admin ) :
		$net_shop_admin->net_shop_admin_sid_field();
	endif;
?>
</form>

<?php if ( empty($options['global_settings']['disable_links']) ) : ?>
<?php if ( get_option('users_can_register') || !empty($options['global_settings']['users_can_register']) ) : ?>
<ul>
<li><a href="<?php if ( !empty($options['global_settings']['login_url']) ) echo $options['global_settings']['login_url']; ?>"><?php _e('Log In', 'frontend-user-admin') ?></a></li>
<li><a href="<?php if ( !empty($options['global_settings']['login_url']) ) echo $this->return_frontend_user_admin_login_url(); ?>action=register"><?php _e('Register', 'frontend-user-admin') ?></a></li>
</ul>
<?php else : ?>
<ul>
<li><a href="<?php if ( !empty($options['global_settings']['login_url']) ) echo $options['global_settings']['login_url']; ?>"><?php _e('Log In', 'frontend-user-admin') ?></a></li>
</ul>
<?php endif; ?>
<?php endif; ?>
</div>