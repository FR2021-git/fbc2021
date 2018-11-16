<?php
	global $current_user, $net_shop_admin, $required;
	$options = $this->get_frontend_user_admin_data();

	if ( !empty($options['global_settings']['required_mark']) ) $required = $options['global_settings']['required_mark'];
	else $required = __('Required', 'frontend-user-admin');

	if ( !empty($options['message_options']['message_profile']) ) echo $this->EvalBuffer($options['message_options']['message_profile']);
	$profileuser = $this->get_user_to_edit();

	if ( isset($_REQUEST['updated']) ) :
		if ( !empty($options['notice_options']['notice_updated']) ) $notice_updated = $options['notice_options']['notice_updated'];
		else $notice_updated = __('User updated.', 'frontend-user-admin');
		$this->errors->add('updated', $notice_updated, 'message');
	endif;

	if ( isset($_REQUEST['required']) ) :
		if ( !empty($options['notice_options']['notice_required']) ) $notice_required = $options['notice_options']['notice_required'];
		else $notice_required = __('There are required fields you need to input.', 'frontend-user-admin');
		$this->errors->add('required', $notice_required, 'error');
	endif;
	
	if ( !empty($options['global_settings']['password_expiration_date']) && !empty($current_user->password_changed_time) && ($current_user->password_changed_time+$options['global_settings']['password_expiration_date']*86400)<date_i18n('U') ) :
		if ( !empty($options['notice_options']['notice_password_change']) ) $password_change = $options['notice_options']['notice_password_change'];
		else $password_change = __('Your password is expired. You need to change your password.', 'frontend-user-admin');
		$this->errors->add('password_change', $password_change, 'error');
	endif;

	if ( !empty($_POST) && is_wp_error($this->errors) ) :
		foreach ( $_POST as $key => $val ) :
			$profileuser->{$key} = $val;
		endforeach;
	endif;

	$hidden = '';
?>
<div class="frontend-user-admin-login">
<?php
	$this->login_header('', $this->errors);
?>
<form name="profile" id="your-profile" action="<?php if ( !empty($options['global_settings']['login_url']) ) echo $options['global_settings']['login_url']; ?>" method="post" enctype="multipart/form-data">
<dl>
<?php wp_nonce_field('update-user_' . $profileuser->user_id) ?>
<?php
	if ( !empty($options['global_settings']['profile_order']) && is_array($options['global_settings']['profile_order']) ) :
	foreach( $options['global_settings']["profile_order"] as $val ) :
		switch ( $val ) :
			case "user_login": ?>
<?php if( empty($options['global_settings']['mail_as_userlogin']) && !empty($options['global_settings']["profile_user_login"]) ) : ?>
<dt><label for="user_login"><?php _e('Username', 'frontend-user-admin'); ?></label> <span class="required"><?php echo $required; ?></span></dt>
<dd><input type="text" name="user_login" id="user_login" class="input frontend-user-admin-user_login" value="<?php echo $profileuser->user_login; ?>" disabled="disabled" /><br /><?php _e('Your username cannot be changed', 'frontend-user-admin'); ?></dd>
<?php endif; ?>
<?php		break;
			case "last_name": ?>
<?php if( !empty($options['global_settings']["profile_last_name"]) && empty($options['global_settings']["profile_last_name_admin"]) ) : ?>
<dt><label for="last_name"><?php _e('Last name', 'frontend-user-admin') ?></label><?php if ( !empty($options['global_settings']["profile_last_name_required"]) ) echo ' <span class="required">'.$required.'</span>'; ?></dt>
<dd><input type="text" name="last_name" id="last_name" class="input frontend-user-admin-last_name" value="<?php echo $profileuser->last_name ?>" /></dd>
<?php endif; ?>
<?php		break;
			case "first_name": ?>
<?php if( !empty($options['global_settings']["profile_first_name"]) && empty($options['global_settings']["profile_first_name_admin"]) ) : ?>
<dt><label for="first_name"><?php _e('First name', 'frontend-user-admin') ?></label><?php if ( !empty($options['global_settings']["profile_first_name_required"]) ) echo ' <span class="required">'.$required.'</span>'; ?></dt>
<dd><input type="text" name="first_name" id="first_name" class="input frontend-user-admin-first_name" value="<?php echo $profileuser->first_name ?>" /></dd>
<?php endif; ?>
<?php		break;
			case "nickname": ?>
<?php if( !empty($options['global_settings']["profile_nickname"]) && empty($options['global_settings']["profile_nickname_admin"]) ) : ?>
<dt><label for="nickname"><?php _e('Nickname', 'frontend-user-admin') ?></label><?php if ( !empty($options['global_settings']["profile_nickname_required"]) ) echo ' <span class="required">'.$required.'</span>'; ?></dt>
<dd><input type="text" name="nickname" id="nickname" class="input frontend-user-admin-nickname" value="<?php echo $profileuser->nickname ?>" /></dd>
<?php endif; ?>
<?php		break;
			case "display_name": ?>
<?php if( !empty($options['global_settings']["profile_display_name"]) && empty($options['global_settings']["profile_display_name_admin"]) ) : ?>
<dt><label for="display_name"><?php _e('Display name publicly&nbsp;as', 'frontend-user-admin') ?></label><?php if ( !empty($options['global_settings']["profile_display_name_required"]) ) echo ' <span class="required">'.$required.'</span>'; ?></dt>
<dd><select name="display_name" id="display_name" class="select frontend-user-admin-display_name">
<?php
				$public_display = array();
				$public_display[] = $profileuser->display_name;
				$public_display[] = $profileuser->nickname;
				$public_display[] = $profileuser->user_login;
				$public_display[] = $profileuser->first_name;
				$public_display[] = $profileuser->first_name.' '.$profileuser->last_name;
				$public_display[] = $profileuser->last_name.' '.$profileuser->first_name;
				$public_display = array_unique(array_filter(array_map('trim', $public_display)));
				foreach($public_display as $item) {
?>
<option value="<?php echo $item; ?>"><?php echo $item; ?></option>
<?php
				}
?>
</select>
</dd>
<?php endif; ?>
<?php		break;
			case "user_email": ?>
<?php if( !empty($options['global_settings']["profile_user_email"]) && empty($options['global_settings']["profile_user_email_admin"]) ) : ?>
<dt><label for="user_email"><?php _e('E-mail', 'frontend-user-admin') ?></label> <span class="required"><?php echo $required; ?></span></dt>
<dd><input type="text" name="user_email" id="<?php if ( !empty($options['global_settings']['email_as_userlogin']) ) echo 'user_login'; else echo 'user_email'; ?>" class="input user_email frontend-user-admin-user_email" value="<?php echo $profileuser->user_email ?>" />
<?php if( ( !empty($options['global_settings']['email_as_userlogin']) && !function_exists('is_ktai')) || ( !empty($options['global_settings']['email_as_userlogin']) && function_exists('is_ktai') && !is_ktai()) ) : ?><br /><?php _e('If you change the email, you will be automatically logged off.', 'frontend-user-admin'); ?>
<?php endif; ?>
</dd>
<?php endif; ?>
<?php		break;
			case "user_url": ?>
<?php if( !empty($options['global_settings']["profile_user_url"]) && empty($options['global_settings']["profile_user_url_admin"]) ) : ?>
<dt><label for="user_url"><?php _e('Website', 'frontend-user-admin') ?></label><?php if ( !empty($options['global_settings']["profile_user_url_required"]) ) echo ' <span class="required">'.$required.'</span>'; ?></dt>
<dd><input type="text" name="user_url" id="user_url" class="input user-url frontend-user-admin-user_url" value="<?php echo $profileuser->user_url ?>" /></dd>
<?php endif; ?>
<?php		break;
			case "aim": ?>
<?php if( !empty($options['global_settings']["profile_aim"]) && empty($options['global_settings']["profile_aim_admin"]) ) : ?>
<dt><label for="aim"><?php _e('AIM', 'frontend-user-admin') ?></label><?php if ( !empty($options['global_settings']["profile_aim_required"]) ) echo ' <span class="required">'.$required.'</span>'; ?></dt>
<dd><input type="text" name="aim" id="aim" class="input frontend-user-admin-aim" value="<?php echo $profileuser->aim ?>" /></dd>
<?php endif; ?>
<?php		break;
			case "yim": ?>
<?php if( !empty($options['global_settings']["profile_yim"]) && empty($options['global_settings']["profile_yim_admin"]) ) : ?>
<dt><label for="yim"><?php _e('Yahoo IM', 'frontend-user-admin') ?></label><?php if ( !empty($options['global_settings']["profile_yim_required"]) ) echo ' <span class="required">'.$required.'</span>'; ?></dt>
<dd><input type="text" name="yim" id="yim" class="input frontend-user-admin-yim" value="<?php echo $profileuser->yim ?>" /></dd>
<?php endif; ?>
<?php		break;
			case "jabber": ?>
<?php if( !empty($options['global_settings']["profile_jabber"]) && empty($options['global_settings']["profile_jabber_admin"]) ) : ?>
<dt><label for="jabber"><?php _e('Jabber / Google Talk', 'frontend-user-admin') ?></label><?php if ( !empty($options['global_settings']["profile_jabber_required"]) ) echo ' <span class="required">'.$required.'</span>'; ?></dt>
<dd><input type="text" name="jabber" id="jabber" class="input frontend-user-admin-jabber" value="<?php echo $profileuser->jabber ?>" /></dd>
<?php endif; ?>
<?php		break;
			case "description": ?>
<?php if( !empty($options['global_settings']["profile_description"]) && empty($options['global_settings']["profile_description_admin"]) ) : ?>
<dt><label for="description"><?php _e('Biographical Info', 'frontend-user-admin'); ?></label><?php if ( !empty($options['global_settings']["profile_description_required"]) ) echo ' <span class="required">'.$required.'</span>'; ?></dt>
<dd><textarea name="description" id="description" class="textarea frontend-user-admin-description" rows="5" cols="30"><?php echo $profileuser->description ?></textarea><br /><?php _e('Share a little biographical information to fill out your profile. This may be shown publicly.', 'frontend-user-admin'); ?></dd>
<?php endif; ?>
<?php		break;
			case "role":
			break;
			case "user_pass": ?>
<?php if( !empty($options['global_settings']["profile_user_pass"]) && (empty($profileuser->fua_social_login) || (!empty($profileuser->fua_social_login) && empty($options['social_options']['password_prohibition']))) ) : ?>
<?php
$show_password_fields = apply_filters('show_password_fields', true);
if ( !empty($show_password_fields) ) :
?>
<dt><label for="pass1"><?php _e('New Password', 'frontend-user-admin'); ?></label></dt>
<dd><input type="password" autocomplete="off" name="pass1" id="pass1" class="input frontend-user-admin-pass1" size="16" value="" /><br /><?php _e("If you would like to change the password type a new one. Otherwise leave this blank.", 'frontend-user-admin'); ?><br />
<input type="password" autocomplete="off" name="pass2" id="pass2" class="input frontend-user-admin-pass2" size="16" value="" /><br /><?php _e("Type your new password again.", 'frontend-user-admin'); ?></dd>
<?php if( !empty($options['global_settings']["use_password_strength"]) ) : if ( function_exists('is_ktai') && is_ktai() ) break; ?>
<dt><?php _e('Password Strength', 'frontend-user-admin'); ?></dt>
<dd><div id="pass-strength-result"><?php _e('Strength indicator', 'frontend-user-admin'); ?></div>
<?php _e('Hint: Use upper and lower case characters, numbers and symbols like !"?$%^&amp;( in your password.', 'frontend-user-admin'); ?>
</dd>
<?php endif; ?>
<?php endif; ?>
<?php endif; ?>
<?php		break;
			default:
				$user_attribute_count = (isset($options['user_attribute']['user_attribute']) && is_array($options['user_attribute']['user_attribute'])) ? count($options['user_attribute']['user_attribute']) : 0;
				for($i=0;$i<$user_attribute_count;$i++) :
					if($options['user_attribute']['user_attribute'][$i]['name'] == $val && !empty($options['global_settings']["profile_".$options['user_attribute']['user_attribute'][$i]['name']]) && empty($options['user_attribute']['user_attribute'][$i]['admin']) ) :
						$start_year = 1900;
						$end_year = date('Y')+10;
						$before = '';
						$after = '';
						if ( !empty($options['user_attribute']['user_attribute'][$i]['readonly']) ) $readonly = ' readonly="readonly"';
						else $readonly = '';								
						if ( !empty($options['user_attribute']['user_attribute'][$i]['disabled']) ) $disabled = ' disabled="disabled"';
						else $disabled = '';	
						if ( !empty($options['user_attribute']['user_attribute'][$i]['overwrite_php']) )
							eval($options['user_attribute']['user_attribute'][$i]['overwrite_php']);
						if ( $options['user_attribute']['user_attribute'][$i]['type']=='breakpoint' ) :
							echo '</dl>'.$options['user_attribute']['user_attribute'][$i]['default'].'<dl>';
						elseif ( $options['user_attribute']['user_attribute'][$i]['type']=='hidden' ) :
							$hidden .= '<input type="hidden" name="'.esc_attr($options['user_attribute']['user_attribute'][$i]['name']).'" value="'.esc_attr($options['user_attribute']['user_attribute'][$i]['default']).'" />'."\n";
						else : ?>
<dt><label for="<?php echo $val; ?>"><?php echo $options['user_attribute']['user_attribute'][$i]['label']; ?></label><?php if($options['user_attribute']['user_attribute'][$i]['required']) echo ' <span class="required">'.$required.'</span>'; ?></dt>
<dd>
<?php					if ( !empty($before) ) echo $before; ?>
<?php					switch($options['user_attribute']['user_attribute'][$i]['type']) :
						case 'display':
							if ( !empty($profileuser->{$val}) ) $profileuser->{$val} = maybe_unserialize(maybe_unserialize($profileuser->{$val}));
							if ( !empty($profileuser->{$val}) && is_array($profileuser->{$val}) ) :
								foreach ( $profileuser->{$val} as $key2 => $val2 ) :
									echo esc_html($val2)." ";
								endforeach;
								break;
							endif; ?>
<?php if( isset($profileuser->{$val}) ) : echo $profileuser->{$val}; else : echo $options['user_attribute']['user_attribute'][$i]['default']; endif; ?>
<?php					break;
						case 'text': ?>
<input type="text" name="<?php echo $val; ?>" id="<?php echo $val; ?>" value="<?php if( isset($profileuser->{$val}) ) : echo esc_attr($profileuser->{$val}); else : echo esc_attr($options['user_attribute']['user_attribute'][$i]['default']); endif; ?>" class="input <?php echo $val; ?>"<?php if ( $val=='zipcode' && $net_shop_admin ) : if ( !function_exists('is_ktai') || function_exists('is_ktai') && !is_ktai()) : ?> onKeyUp="AjaxZip2.zip2addr(this,'pref','address1');"<?php endif; endif; ?><?php if ( !empty($options['user_attribute']['user_attribute'][$i]['placeholder']) ) : ?> placeholder="<?php echo esc_attr($options['user_attribute']['user_attribute'][$i]['placeholder']); ?>"<?php endif; ?><?php echo $readonly; ?><?php echo $disabled; ?> />
<?php					break;
						case 'textarea': ?>
<textarea name="<?php echo esc_attr($val); ?>" id="<?php echo $val; ?>" class="textarea <?php echo $val; ?>"<?php if ( !empty($options['user_attribute']['user_attribute'][$i]['placeholder']) ) : ?> placeholder="<?php echo esc_attr($options['user_attribute']['user_attribute'][$i]['placeholder']); ?>"<?php endif; ?><?php echo $readonly; ?><?php echo $disabled; ?>><?php if( isset($profileuser->{$val}) ) : echo htmlspecialchars($profileuser->{$val}); else : echo htmlspecialchars($options['user_attribute']['user_attribute'][$i]['default']); endif; ?></textarea>
<?php					break;
						case 'select':
							preg_match_all('/"[^"]*"d?|\'[^\']*\'d?/', $options['user_attribute']['user_attribute'][$i]['default'], $matches);
										?>
<select name="<?php echo $val; ?>" id="<?php echo $val; ?>" class="select <?php echo $val; ?>">
<?php						foreach($matches[0] as $select_val) :
								$select_val = rtrim($select_val,'d');
								$select_val = rtrim(trim($select_val,'"|\''),'"|\'');
								if ( preg_match('/([^\|]*)\|([^\|]*)/', $select_val, $select_val2 ) ) :
									$label = $select_val2[1];
									$value = $select_val2[2];
								else:
									$label = $select_val;
									$value = $select_val;
								endif;?>
<option value="<?php echo esc_attr($value); ?>"<?php if($value == $profileuser->{$val} || !empty($default) ) echo ' selected="selected"'; ?><?php echo $disabled; ?>><?php echo $label; ?></option>
<?php						endforeach; ?>
</select> 
<?php					break;      
						case 'checkbox':
							preg_match_all('/"[^"]*"d?|\'[^\']*\'d?/', $options['user_attribute']['user_attribute'][$i]['default'], $matches);
										?>
<?php
							if(isset($profileuser->{$val})) $profileuser->{$val} = maybe_unserialize($profileuser->{$val});
							foreach($matches[0] as $select_val) :
								$select_val = rtrim($select_val,'d');
								$select_val = rtrim(trim($select_val,'"|\''),'"|\'');
								if ( preg_match('/([^\|]*)\|([^\|]*)/', $select_val, $select_val2 ) ) :
									$label = $select_val2[1];
									$value = $select_val2[2];
								else:
									$label = $select_val;
									$value = $select_val;
								endif;?>
<label><input type="checkbox" name="<?php echo $val; ?>[]" class="checkbox <?php echo $val; ?>" value="<?php echo esc_attr($value); ?>"<?php if((!empty($profileuser->{$val}) && is_array($profileuser->{$val}) && in_array($value, $profileuser->{$val})) || !empty($default) ) echo ' checked="checked"';?><?php echo $disabled; ?> /> <?php echo $label; ?></label> 
<?php						endforeach; ?>
<?php					break;                                                                          
						case 'radio':
							preg_match_all('/"[^"]*"d?|\'[^\']*\'d?/', $options['user_attribute']['user_attribute'][$i]['default'], $matches);
						?>
<?php						foreach($matches[0] as $select_val) :
								$select_val = rtrim($select_val,'d');
								$select_val = rtrim(trim($select_val,'"|\''),'"|\'');
								if ( preg_match('/([^\|]*)\|([^\|]*)/', $select_val, $select_val2 ) ) :
									$label = $select_val2[1];
									$value = $select_val2[2];
								else:
									$label = $select_val;
									$value = $select_val;
								endif;?>
<label><input type="radio" name="<?php echo $val; ?>" class="radio <?php echo $val; ?>" value="<?php echo esc_attr($value); ?>"<?php if( (isset($profileuser->{$val}) && $value == $profileuser->{$val}) || isset($default) ) echo ' checked="checked"';?><?php echo $disabled; ?> /> <?php echo $label; ?></label> 
<?php						endforeach; ?>
<?php					break;
						case 'datetime':
							$profile_year = $profile_month = $profile_day = $profile_hour = $profile_minute = '';
							$profile_datetime = array();
							if ( isset($profileuser->{$val}) ) $profile_datetime = preg_split('/-|\s|:/', $profileuser->{$val});
							$profile_year = isset($profile_datetime[0]) ? $profile_datetime[0] : '';
							$profile_month = isset($profile_datetime[1]) ? $profile_datetime[1] : '';
							$profile_day = isset($profile_datetime[2]) ? $profile_datetime[2] : '';
							$profile_hour = isset($profile_datetime[3]) ? $profile_datetime[3] : '';
							$profile_minute = isset($profile_datetime[4]) ? $profile_datetime[4] : '';
								if($options['user_attribute']['user_attribute'][$i]['default']) :
									$tmp_year = '<select name="'.$val.'_year" id="'.$val.'_year_edit"><option value=""></option>';
									for($j=$start_year;$j<=$end_year;$j++) {
										if( isset($profile_year) && $j == $profile_year ) {
											$tmp_year .= '<option value="'.$j.'" selected="selected"'.$disabled.'>'.$j.'</option>';
										} else {
											$tmp_year .= '<option value="'.$j.'"'.$disabled.'>'.$j.'</option>';
										}
									}
									$tmp_year .= '</select>';

									$tmp_month = '<select name="'.$val.'_month" id="'.$val.'_month_edit"><option value=""></option>';
									for($j=1;$j<13;$j++) {
										if( isset($profile_month) && $j == $profile_month ) {
											$tmp_month .= '<option value="'.$j.'" selected="selected"'.$disabled.'>'.$j.'</option>';
										} else {
											$tmp_month .= '<option value="'.$j.'"'.$disabled.'>'.$j.'</option>';
										}
									}
									$tmp_month .= '</select>';

									$tmp_day = '<select name="'.$val.'_day" id="'.$val.'_day_edit"><option value=""></option>';
									for($j=1;$j<32;$j++) {
										if( isset($profile_day) && $j == $profile_day ) {
											$tmp_day .= '<option value="'.$j.'" selected="selected"'.$disabled.'>'.$j.'</option>';
										} else {
											$tmp_day .= '<option value="'.$j.'"'.$disabled.'>'.$j.'</option>';
										}
									}
									$tmp_day .= '</select>';
							
									$tmp_hour = '<select name="'.$val.'_hour" id="'.$val.'_hour_edit"><option value=""></option>';
									for($j=0;$j<24;$j++) {
										if($profile_hour != '' && $j == trim($profile_hour)) {
											$tmp_hour .= '<option value="'.$j.'" selected="selected"'.$disabled.'>'.$j.'</option>';
										} else {
											$tmp_hour .= '<option value="'.$j.'"'.$disabled.'>'.$j.'</option>';
										}
									}
									$tmp_hour .= '</select>';

									$tmp_minute = '<select name="'.$val.'_minute" id="'.$val.'_minute_edit"><option value=""></option>';
									for($j=0;$j<60;$j++) {
										if($profile_minute != '' &&$j == trim($profile_minute)) {
											$tmp_minute .= '<option value="'.$j.'" selected="selected"'.$disabled.'>'.$j.'</option>';
										} else {
											$tmp_minute .= '<option value="'.$j.'"'.$disabled.'>'.$j.'</option>';
										}
									}
									$tmp_minute .= '</select>';
								
									$replacements = array('yyyy'=>$tmp_year,'mm'=>$tmp_month,'dd'=>$tmp_day,'hh'=>$tmp_hour,'ii'=>$tmp_minute);
									$options['user_attribute']['user_attribute'][$i]['default'] = strtr($options['user_attribute']['user_attribute'][$i]['default'], $replacements);
																											
									echo $options['user_attribute']['user_attribute'][$i]['default'];
								endif;
							break;                                                                        
						case "file" :
?>
<input type="file" name="<?php echo $val; ?>" id="<?php echo $val; ?>" class="regular-text" />
<?php
							if( !empty($profileuser->{$val}) ) :
								$image_data = wp_get_attachment_image_src($profileuser->{$val}, 'thumbnail', false);
?>
<br /><img src="<?php echo $image_data[0]; ?>" width="32" height="32" alt="<?php echo $options['user_attribute']['user_attribute'][$i]['label']; ?>" class="thumbnail" /> <input type="hidden" name="<?php echo $val; ?>" value="<?php echo $profileuser->{$val}; ?>" /> <input type="checkbox" name="<?php echo $val; ?>_delete" value="1" /> <?php _e('Delete', 'frontend-user-admin'); ?>
<?php	
							endif;
							break;                                         
					endswitch;
					if ( !empty($options['user_attribute']['user_attribute'][$i]['publicity']) ) :
?>
<select name="_publicity_<?php echo $val; ?>" id="_publicity_<?php echo $val; ?>">
<option value=""><?php _e('Public', 'frontend-user-admin'); ?></option>
<option value="1"<?php if ( !empty($profileuser->{'_publicity_'.$val}) ) selected(1); ?>><?php _e('Private', 'frontend-user-admin'); ?></option>
</select>
<?php
					endif;
					if ( !empty($after) ) echo $after;
					if($options['user_attribute']['user_attribute'][$i]['comment']) echo '<br /><span class="frontend-user-admin-user-attribute-comment">'.$options['user_attribute']['user_attribute'][$i]['comment'].'</span>';
?></dd>
<?php
					endif;
					break;
				endif;
			endfor;
		endswitch;
	endforeach;
	endif;
	
	if ( !empty($options['global_settings']['record_update_datetime']) ) :
?>
<dt><label for="update_datetime"><?php _e('Update Datetime', 'frontend-user-admin'); ?></label></dt>
<dd><?php if ( !empty($profileuser->update_datetime) ) echo date_i18n('Y-m-d H:i:s', $profileuser->update_datetime); ?></dd>
<?php
	endif;
?>
</dl>

<p class="submit">
<input type="hidden" name="action" value="update" />
<input type="submit" value="<?php _e('Update Profile &raquo;', 'frontend-user-admin') ?>" name="submit" class="submit profile" />
</p>
<?php
if ( function_exists('is_ktai') && is_ktai() ) :
	$Ktai_Style->admin->sid_field();
	if ( $net_shop_admin ) :
		$net_shop_admin->net_shop_admin_sid_field();
	else :
		ks_fix_encoding_form();
	endif;
endif;
if ( !empty($hidden) ) echo $hidden;
?>
</form>
</div>