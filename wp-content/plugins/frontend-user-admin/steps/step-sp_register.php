<?php
	global $current_user, $net_shop_admin;
	$options = $this->get_frontend_user_admin_data();
	
	if ( !empty($_REQUEST['key']) ) :
		$tmp = $this->fua_decrypt($_REQUEST['key']);
		list($time, $_POST['user_email']) = explode(':', $tmp);
		if ( $time<(date_i18n('U')-86400) || empty($_POST['user_email']) || !is_email($_POST['user_email']) || email_exists($_POST['user_email']) ) :
			$this->errors->add('invalidkey', __('Sorry, that key does not appear to be valid.', 'frontend-user-admin'));
			$invalidkey = 1;
		endif;
	endif;
		
	if ( !empty($options['global_settings']['email_confirmation_first']) ) :
		if ( empty($_REQUEST['key']) || !empty($invalidkey) ) :
			if ( !empty($_REQUEST['checkemail']) ) $this->errors->add('checkemail', __('Please check your e-mail and click the link.', 'frontend-user-admin'), 'message');
			if ( !empty($_REQUEST['invalidemail']) ) $this->errors->add('invalidemail', __('<strong>ERROR</strong>: The email address isn&#8217;t correct or already registered.', 'frontend-user-admin'));
			if ( !empty($options['message_options']['message_ecf']) ) echo $this->EvalBuffer($options['message_options']['message_ecf']);
?>
<div class="frontend-user-admin-login">
<?php
			$this->login_header('', $this->errors);
?>
<form name="ecf" id="ecf" action="<?php if ( !empty($options['global_settings']['login_url']) ) echo $this->return_frontend_user_admin_login_url(); ?>action=ecf" method="post">
<dl>
<dt><label><?php _e('E-mail', 'frontend-user-admin'); ?></label></dt>
<dd><input type="text" name="email" id="email" class="input" size="20" /></dd>
</dl>
<?php do_action('ecf_form', 'frontend-user-admin'); ?>
<p class="submit"><input type="submit" name="wp-submit" id="wp-submit" value="<?php _e('Send Confirmation Email &raquo;', 'frontend-user-admin'); ?>" tabindex="100" class="submit ecf_form" /></p>
</form>

<?php if ( empty($options['global_settings']['disable_links']) ) : ?>
<ul>
<li><a href="<?php if ( !empty($options['global_settings']['login_url']) ) echo $options['global_settings']['login_url']; ?>"><?php _e('Log In', 'frontend-user-admin') ?></a></li>
<?php if ( empty($options['global_settings']['disable_lostpassword']) ) : ?>
<li><a href="<?php if ( !empty($options['global_settings']['login_url']) ) echo $this->return_frontend_user_admin_login_url(); ?>action=lostpassword"><?php _e('Lost your password?', 'frontend-user-admin') ?></a></li>
<?php endif; ?>
</ul>
<?php endif; ?>
</div>
<?php
			return;
		endif;
	endif;

	if ( !empty($options['global_settings']['required_mark']) ) $required = $options['global_settings']['required_mark'];
	else $required = __('Required', 'frontend-user-admin');

	if ( !empty($options['message_options']['message_register']) ) echo $this->EvalBuffer($options['message_options']['message_register']);
?>
<div class="frontend-user-admin-login frontend-user-admin-login-sp">
<?php
	if( !empty($options['global_settings']['default_messages']) ) :
		if ( !empty($options['notice_options']['notice_register']) ) $notice_register = $options['notice_options']['notice_register'];
		else $notice_register = __('Register For This Site', 'frontend-user-admin');
		$this->login_header('<div class="message">' . $notice_register . '</div>', $this->errors);
	else :
		$this->login_header('', $this->errors);
	endif;
	
	$hidden = '';
?>
<form name="registerform" id="registerform" action="<?php if ( !empty($options['global_settings']['login_url']) ) echo $this->return_frontend_user_admin_login_url(); ?><?php if( !empty($options['global_settings']['confirmation_screen']) ) echo 'action=confirmation'; else echo 'action=register'; ?>" method="post" enctype="multipart/form-data">
<dl>
<?php
	if ( !empty($options['global_settings']['register_order']) && is_array($options['global_settings']['register_order']) ) :
	foreach( $options['global_settings']['register_order'] as $val ) :
		$continue = 0;
		switch ( $val ) :
			case "ms_domain": ?>
<?php if( is_multisite() && !empty($options['global_settings']['register_ms_domain']) ) : ?>
<dt><label for="ms_domain"><?php _e('Site Domain', 'frontend-user-admin') ?></label> <span class="required"><?php echo $required; ?></span></dt>
<dd><input type="text" name="ms_domain" id="ms_domain" class="input frontend-user-admin-ms_domain" value="<?php if ( !empty($_POST['ms_domain']) ) echo esc_attr($_POST['ms_domain']); ?>"  /> <?php _e('Half-width alphamerics', 'frontend-user-admin') ?></dd>
<?php endif; ?>
<?php		break;
			case "ms_title": ?>
<?php if( is_multisite() && !empty($options['global_settings']['register_ms_title']) ) : ?>
<dt><label for="ms_title"><?php _e('Site Title', 'frontend-user-admin') ?></label> <span class="required"><?php echo $required; ?></span></dt>
<dd><input type="text" name="ms_title" id="ms_title" class="input frontend-user-admin-ms_title" value="<?php if ( !empty($_POST['ms_title']) ) echo esc_attr($_POST['ms_title']); ?>" /></dd>
<?php endif; ?>
<?php		break;
			case "user_login": ?>
<?php if( empty($options['global_settings']['email_as_userlogin']) && !empty($options['global_settings']["register_user_login"]) ) : ?>
<dt><label for="user_login"><?php _e('Username', 'frontend-user-admin') ?></label> <span class="required"><?php echo $required; ?></span></dt>
<dd><input type="text" name="user_login" id="user_login" class="input frontend-user-admin-user_login" value="<?php if ( isset($_POST['user_login']) ) echo esc_attr($_POST['user_login']); ?>" size="20" /> <?php _e('Half-width alphamerics', 'frontend-user-admin') ?></dd>
<?php endif; ?>
<?php		break;
			case "last_name": ?>
<?php if( !empty($options['global_settings']["register_last_name"]) ) : ?>
<dt><label for="last_name"><?php _e('Last name', 'frontend-user-admin') ?></label><?php if ( !empty($options['global_settings']["register_last_name_required"]) ) echo ' <span class="required">'.$required.'</span>'; ?></dt>
<dd><input type="text" name="last_name" id="last_name" class="input last_name" value="<?php if ( isset($_POST['last_name']) ) echo esc_attr($_POST['last_name']); ?>" /></dd>
<?php endif; ?>
<?php		break;
			case "first_name": ?>
<?php if( !empty($options['global_settings']["register_first_name"]) ) : ?>
<dt><label for="first_name"><?php _e('First name', 'frontend-user-admin') ?></label><?php if ( !empty($options['global_settings']["register_first_name_required"]) ) echo ' <span class="required">'.$required.'</span>'; ?></dt>
<dd><input type="text" name="first_name" id="first_name" class="input first_name" value="<?php if ( isset($_POST['first_name']) ) echo esc_attr($_POST['first_name']); ?>" /></dd>
<?php endif; ?>
<?php		break;
			case "nickname": ?>
<?php if( !empty($options['global_settings']["register_nickname"]) ) : ?>
<dt><label for="nickname"><?php _e('Nickname', 'frontend-user-admin') ?></label><?php if ( !empty($options['global_settings']["register_nickname_required"]) ) echo ' <span class="required">'.$required.'</span>'; ?></dt>
<dd><input type="text" name="nickname" id="nickname" class="input nickname" value="<?php if ( isset($_POST['nickname']) ) echo esc_attr($_POST['nickname']); ?>" /></dd>
<?php endif; ?>
<?php		break;
			case "user_email": ?>
<?php if( !empty($options['global_settings']["register_user_email"]) ) : ?>
<dt><label for="user_email"><?php _e('E-mail', 'frontend-user-admin') ?></label> <span class="required"><?php echo $required; ?></span></dt>
<dd><input type="text" name="user_email" id="<?php if ( !empty($options['global_settings']['email_as_userlogin']) ) echo 'user_login'; else echo 'user_email'; ?>" class="input user_email" value="<?php if ( isset($_POST['user_email']) ) echo esc_attr($_POST['user_email']); ?>" size="25"<?php if ( !empty($options['global_settings']['email_confirmation_first']) ) echo ' readonly="readonly"'; ?> /></dd>
<?php endif; ?>
<?php		break;
			case "user_url": ?>
<?php if( !empty($options['global_settings']["register_user_url"]) ) : ?>
<dt><label for="user_url"><?php _e('Website', 'frontend-user-admin') ?></label><?php if ( !empty($options['global_settings']["register_user_url_required"]) ) echo ' <span class="required">'.$required.'</span>'; ?></dt>
<dd><input type="text" name="user_url" id="user_url" class="input user_url" value="<?php if ( isset($_POST['user_url']) ) echo esc_attr($_POST['user_url']); ?>" /></dd>
<?php endif; ?>
<?php		break;
			case "aim": ?>
<?php if( !empty($options['global_settings']["register_aim"]) ) : ?>
<dt><label for="aim"><?php _e('AIM', 'frontend-user-admin') ?></label><?php if ( !empty($options['global_settings']["register_aim_required"]) ) echo ' <span class="required">'.$required.'</span>'; ?></dt>
<dd><input type="text" name="aim" id="aim" class="input aim" value="<?php if ( isset($_POST['aim']) ) echo esc_attr($_POST['aim']); ?>" /></dd>
<?php endif; ?>
<?php		break;
			case "yim": ?>
<?php if( !empty($options['global_settings']["register_yim"]) ) : ?>
<dt><label for="yim"><?php _e('Yahoo IM', 'frontend-user-admin') ?></label><?php if ( !empty($options['global_settings']["register_yim_required"]) ) echo ' <span class="required">'.$required.'</span>'; ?></dt>
<dd><input type="text" name="yim" id="yim" class="input yim" value="<?php if ( isset($_POST['yim']) ) echo esc_attr($_POST['yim']); ?>" /></dd>
<?php endif; ?>
<?php		break;
			case "jabber": ?>
<?php if( !empty($options['global_settings']["register_jabber"]) ) : ?>
<dt><label for="jabber"><?php _e('Jabber / Google Talk', 'frontend-user-admin') ?></label><?php if ( !empty($options['global_settings']["register_jabber_required"]) ) echo ' <span class="required">'.$required.'</span>'; ?></dt>
<dd><input type="text" name="jabber" id="jabber" class="input jabber" value="<?php if ( isset($_POST['jabber']) ) echo esc_attr($_POST['jabber']); ?>" /></dd>
<?php endif; ?>
<?php		break;
			case "description": ?>
<?php if( !empty($options['global_settings']["register_description"]) ) : ?>
<dt><label for="description"><?php _e('Biographical Info', 'frontend-user-admin'); ?></label><?php if ( !empty($options['global_settings']["register_description_required"]) ) echo ' <span class="required">'.$required.'</span>'; ?></dt>
<dd><textarea name="description" id="description" class="textarea description" rows="5" cols="30"><?php if ( isset($_POST['description']) ) echo esc_attr($_POST['description']) ?></textarea><br />
<?php _e('Share a little biographical information to fill out your profile. This may be shown publicly.', 'frontend-user-admin'); ?></dd>
<?php endif; ?>
<?php		break;
			case "role":
			break;
			case "user_pass": ?>
<?php if( !empty($options['global_settings']["password_registration"]) && empty($options['global_settings']["use_common_password"])  ) : ?>
<?php
$show_password_fields = apply_filters('show_password_fields', true);
if ( $show_password_fields ) :
?>
<dt><label for="pass1"><?php _e('Password', 'frontend-user-admin'); ?></label> <span class="required"><?php echo $required; ?></span></dt>
<dd><input type="password" autocomplete="off" name="pass1" id="pass1" class="input" size="16" value="" /><br /><?php _e("Type your new password again.", 'frontend-user-admin'); ?><br />
<input type="password" autocomplete="off" name="pass2" id="pass2" class="input" size="16" value="">
</dd>
<?php if( !empty($options['global_settings']["use_password_strength"]) ) : if ( function_exists('is_ktai') && is_ktai() ) break; ?>
<dt><?php _e('Password Strength', 'frontend-user-admin'); ?></dt>
<dd><div id="pass-strength-result"><?php _e('Strength indicator', 'frontend-user-admin'); ?></div>
<?php _e('Hint: Use upper and lower case characters, numbers and symbols like !"?$%^&amp;( in your password.', 'frontend-user-admin'); ?></dd>
<?php endif; ?>
<?php endif; ?>
<?php endif; ?>
<?php		break;
			default:
				if ( !empty($options['user_attribute']['user_attribute']) ) $count_user_attribute = count($options['user_attribute']['user_attribute']);
				else $count_user_attribute = 0;
				for($i=0;$i<$count_user_attribute;$i++) :
					if($options['user_attribute']['user_attribute'][$i]['name'] == $val && $options['global_settings']["register_".$options['user_attribute']['user_attribute'][$i]['name']] && empty($options['user_attribute']['user_attribute'][$i]['admin']) ) :
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
						if ( $continue ) continue;
						if ( $options['user_attribute']['user_attribute'][$i]['type']=='breakpoint' ) :
							echo '</dl>'.$options['user_attribute']['user_attribute'][$i]['default'].'<dl>';
						elseif ( $options['user_attribute']['user_attribute'][$i]['type']=='hidden' ) :
							$hidden .= '<input type="hidden" name="'.esc_attr($options['user_attribute']['user_attribute'][$i]['name']).'" value="'.esc_attr($options['user_attribute']['user_attribute'][$i]['default']).'" />'."\n";
						else : ?>
<dt><label for="<?php echo $val; ?>"><?php echo $options['user_attribute']['user_attribute'][$i]['label']; ?></label><?php if($options['user_attribute']['user_attribute'][$i]['required']) echo ' <span class="required">'.$required.'</span>'; ?></dt>
<dd><?php					if ( !empty($before) ) echo $before; ?>
<?php					switch($options['user_attribute']['user_attribute'][$i]['type']) :
							case 'display': 
								echo $options['user_attribute']['user_attribute'][$i]['default'];
								break;
							case 'text':
								if (preg_match('/[^\|]*\|c$/', $options['user_attribute']['user_attribute'][$i]['default'])) $options['user_attribute']['user_attribute'][$i]['default'] = ""; ?>
<input type="text" name="<?php echo $val; ?>" id="<?php echo $val; ?>" value="<?php if( isset($_POST[$val]) ) : echo esc_attr($_POST[$val]); else : echo esc_attr($options['user_attribute']['user_attribute'][$i]['default']); endif; ?>" class="input <?php echo $val; ?>"<?php if ( $val=='zipcode' && $net_shop_admin ) : if ( !function_exists('is_ktai') || function_exists('is_ktai') && !is_ktai()) : ?> onKeyUp="AjaxZip2.zip2addr(this,'pref','address1');"<?php endif; endif; ?><?php if ( !empty($options['user_attribute']['user_attribute'][$i]['placeholder']) ) : ?> placeholder="<?php echo esc_attr($options['user_attribute']['user_attribute'][$i]['placeholder']); ?>"<?php endif; ?><?php echo $readonly; ?><?php echo $disabled; ?> /> 
<?php							break;
							case 'textarea': ?>
<textarea name="<?php echo esc_attr($val); ?>" id="<?php echo $val; ?>" class="textarea <?php echo $val; ?>"<?php if ( !empty($options['user_attribute']['user_attribute'][$i]['placeholder']) ) : ?> placeholder="<?php echo esc_attr($options['user_attribute']['user_attribute'][$i]['placeholder']); ?>"<?php endif; ?><?php echo $readonly; ?><?php echo $disabled; ?>><?php if( isset($_POST[$val]) ) : echo htmlspecialchars($_POST[$val]); else : echo htmlspecialchars($options['user_attribute']['user_attribute'][$i]['default']); endif; ?></textarea>
<?php						break;
							case 'select':
								preg_match_all('/"[^"]*"d?|\'[^\']*\'d?/', $options['user_attribute']['user_attribute'][$i]['default'], $matches);
										?>
<select name="<?php echo $val; ?>" id="<?php echo $val; ?>" class="select <?php echo $val; ?>">
<?php							foreach($matches[0] as $select_val) :
									unset($default);
									if(preg_match('/d$/', $select_val) && empty($_POST[$val]) ) {
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
<option value="<?php echo esc_attr($value); ?>"<?php if( (isset($_POST[$val]) && $value == $_POST[$val]) || !empty($default) ) echo ' selected="selected"'; ?><?php echo $disabled; ?>><?php echo $label; ?></option>
<?php							endforeach; ?>
</select>
<?php						break;      
							case 'checkbox':
								preg_match_all('/"[^"]*"d?|\'[^\']*\'d?/', $options['user_attribute']['user_attribute'][$i]['default'], $matches);
										?>
<?php
								if(isset($_POST[$val])) $_POST[$val] = maybe_unserialize($_POST[$val]);
								foreach($matches[0] as $select_val) :
									unset($default);
									if(preg_match('/d$/', $select_val) && !is_array($_POST[$val])) {
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
<label><input type="checkbox" name="<?php echo $val; ?>[]" class="checkbox <?php echo $val; ?>" value="<?php echo esc_attr($value); ?>"<?php if((!empty($_POST[$val]) && is_array($_POST[$val]) && in_array($value, $_POST[$val])) || !empty($default) ) echo ' checked="checked"';?><?php echo $disabled; ?> /> <?php echo $label; ?></label> 
<?php							endforeach; ?>
<?php						break;                                                                          
							case 'radio':
								preg_match_all('/"[^"]*"d?|\'[^\']*\'d?/', $options['user_attribute']['user_attribute'][$i]['default'], $matches);
										?>
<?php							foreach($matches[0] as $select_val) :
									unset($default);
									if(preg_match('/d$/', $select_val) && !$_POST[$val]) {
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
<label><input type="radio" name="<?php echo $val; ?>" class="radio <?php echo $val; ?>" value="<?php echo esc_attr($value); ?>"<?php if( (isset($_POST[$val]) && $value == $_POST[$val]) || isset($default)) echo ' checked="checked"';?><?php echo $disabled; ?> /> <?php echo $label; ?></label> 
<?php							endforeach; ?>
<?php						break;
							case 'datetime':
								$profile_year = $profile_month = $profile_day = $profile_hour = $profile_minute = '';
								$profile_datetime = array();
								if ( isset($_POST[$val]) ) $profile_datetime = preg_split('/-|\s|:/', $_POST[$val]);
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
							if (  !empty($options['global_settings']['confirmation_screen']) ) :
								_e('Upload a file in the next page.', 'frontend-user-admin');
							else :
?>
<input type="file" name="<?php echo $val; ?>" id="<?php echo $val; ?>" class="regular-text" />
<?php
							endif;
							break;
					endswitch;
					if ( !empty($after) ) echo $after;
					if ( isset($options['user_attribute']['user_attribute'][$i]['comment']) ) echo '<br /><span class="frontend-user-admin-user-attribute-comment">'.$options['user_attribute']['user_attribute'][$i]['comment'].'</span>';
?></dd><?php
					endif;
					break;
				endif;
			endfor;
		endswitch;
	endforeach;
	endif;
?>

<?php if( !empty($options['global_settings']["terms_of_use_check"]) ) : ?>
<dt><label for="terms_of_use"><?php _e('Terms of use', 'frontend-user-admin') ?></label><?php echo ' <span class="required">'.$required.'</span>'; ?></dt>
<dd><input type="checkbox" name="terms_of_use" id="terms_of_use" value="1"  <?php if ( !empty($_POST['terms_of_use']) ) checked(1); ?> /> 
<?php if ( !empty($options['global_settings']["terms_of_use_url"]) ) : ?>
<?php if ( !empty($options['global_settings']["name_of_terms"]) ) $terms = esc_html($options['global_settings']["name_of_terms"]);
else $terms = __('Terms of use', 'frontend-user-admin'); ?>
<?php printf(__('I agree to the <a href="%s" target="_blank">%s</a>.', 'frontend-user-admin'), $options['global_settings']["terms_of_use_url"], $terms ) ?></a>
<?php else: ?>
<?php _e('I agree to the terms of use.', 'frontend-user-admin') ?>
<?php endif; ?>
</dd>
<?php endif; ?>
</dl>

<?php do_action('register_form'); ?>
<?php if( !empty($options['global_settings']["password_registration"]) ) : ?>
<?php else: ?>
<p id="reg_passmail"><?php _e('A password will be e-mailed to you.', 'frontend-user-admin') ?></p>
<?php endif; ?>
<p class="submit"><?php if( !empty($options['global_settings']['confirmation_screen']) ) : ?>
<input type="submit" name="wp-submit-confirmation" id="wp-submit-confirmation" value="<?php _e('Confirm &raquo;', 'frontend-user-admin'); ?>" class="submit confirmation" />
<?php else: ?>
<input type="submit" name="wp-submit" id="wp-submit" value="<?php _e('Register &raquo;', 'frontend-user-admin'); ?>" class="submit register" />
<?php endif; ?>
</p>
<?php
	if ( !empty($hidden) ) echo $hidden;
	if ( !empty($options['global_settings']['email_confirmation_first']) && !empty($_REQUEST['key']) ) :
?>
<input type="hidden" name="key" value="<?php echo esc_attr($_REQUEST['key']); ?>" />
<?php
	endif;
?>
</form>

<?php if ( empty($options['global_settings']['disable_links']) ) : ?>
<ul>
<li><a href="<?php if ( !empty($options['global_settings']['login_url']) ) echo $options['global_settings']['login_url']; ?>"><?php _e('Log In', 'frontend-user-admin') ?></a></li>
<?php if ( empty($options['global_settings']['disable_lostpassword']) ) : ?>
<li><a href="<?php if ( !empty($options['global_settings']['login_url']) ) echo $this->return_frontend_user_admin_login_url(); ?>action=lostpassword"><?php _e('Lost your password?', 'frontend-user-admin') ?></a></li>
<?php endif; ?>
</ul>
<?php endif; ?>
</div>