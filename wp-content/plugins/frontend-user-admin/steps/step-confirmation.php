<?php
	global $current_user, $net_shop_admin;
	$options = $this->get_frontend_user_admin_data();
		
	if ( !empty($options['message_options']['message_confirmation']) ) echo $this->EvalBuffer($options['message_options']['message_confirmation']);
?>
<div class="frontend-user-admin-login">
<?php
	$hidden = '';
?>
<form name="registerform" id="registerform" action="<?php if ( !empty($options['global_settings']['login_url']) ) echo $this->return_frontend_user_admin_login_url(); ?>action=register" method="post" enctype="multipart/form-data">
<table class="form-table">
<tbody>
<?php
	foreach( $options['global_settings']['register_order'] as $val ) :
		switch ( $val ) :
			case "ms_domain": ?>
<?php if( is_multisite() && !empty($options['global_settings']['register_ms_domain']) ) : ?>
<tr>
<th><label for="ms_domain"><?php _e('Site Domain', 'frontend-user-admin') ?></label></th>
<td><input type="hidden" name="ms_domain" value="<?php echo esc_attr($_POST['ms_domain']); ?>" /><?php echo esc_attr($_POST['ms_domain']); ?></td>
</tr>
<?php endif; ?>
<?php		break;
			case "ms_title": ?>
<?php if( is_multisite() && !empty($options['global_settings']['register_ms_title']) ) : ?>
<tr>
<th><label for="ms_title"><?php _e('Site Title', 'frontend-user-admin') ?></label></th>
<td><input type="hidden" name="ms_title" value="<?php echo esc_attr($_POST['ms_title']); ?>" /><?php echo esc_attr($_POST['ms_title']); ?></td>
</tr>
<?php endif; ?>
<?php		break;
			case "user_login": ?>
<?php if( empty($options['global_settings']['email_as_userlogin']) && !empty($options['global_settings']["register_user_login"]) ) : ?>
<tr>
<th><label for="user_login"><?php _e('Username', 'frontend-user-admin') ?></label></th>
<td><input type="hidden" name="user_login" value="<?php echo esc_attr($_POST['user_login']); ?>" /><?php echo esc_attr($_POST['user_login']); ?></td>
</tr>
<?php endif; ?>
<?php		break;
			case "last_name": ?>
<?php if( !empty($options['global_settings']["register_last_name"]) ) : ?>
<tr>
<th><label for="last_name"><?php _e('Last name', 'frontend-user-admin') ?></label></th>
<td><input type="hidden" name="last_name" value="<?php echo esc_attr($_POST['last_name']); ?>" /><?php echo esc_attr($_POST['last_name']); ?></td>
</tr>
<?php endif; ?>
<?php		break;
			case "first_name": ?>
<?php if( !empty($options['global_settings']["register_first_name"]) ) : ?>
<tr>
<th><label for="first_name"><?php _e('First name', 'frontend-user-admin') ?></label></th>
<td><input type="hidden" name="first_name" value="<?php echo esc_attr($_POST['first_name']); ?>" /><?php echo esc_attr($_POST['first_name']); ?></td>
</tr>
<?php endif; ?>
<?php		break;
			case "nickname": ?>
<?php if( !empty($options['global_settings']["register_nickname"]) ) : ?>
<tr>
<th><label for="nickname"><?php _e('Nickname', 'frontend-user-admin') ?></label></th>
<td><input type="hidden" name="nickname" value="<?php echo esc_attr($_POST['nickname']); ?>" /><?php echo esc_attr($_POST['nickname']); ?></td>
</tr>
<?php endif; ?>
<?php		break;
			case "user_email": ?>
<?php if( !empty($options['global_settings']["register_user_email"]) ) : ?>
<tr>
<th><label for="user_email"><?php _e('E-mail', 'frontend-user-admin') ?></label></th>
<td><input type="hidden" name="user_email" value="<?php echo esc_attr($_POST['user_email']); ?>" /><?php echo esc_attr($_POST['user_email']); ?></td>
</tr>
<?php endif; ?>
<?php		break;
			case "user_url": ?>
<?php if( !empty($options['global_settings']["register_user_url"]) ) : ?>
<tr>
<th><label for="user_url"><?php _e('Website', 'frontend-user-admin') ?></label></th>
<td><input type="hidden" name="user_url" value="<?php echo esc_attr($_POST['user_url']); ?>" /><?php echo esc_attr($_POST['user_url']); ?></td>
</tr>
<?php endif; ?>
<?php		break;
			case "aim": ?>
<?php if( !empty($options['global_settings']["register_aim"]) ) : ?>
<tr>
<th><label for="aim"><?php _e('AIM', 'frontend-user-admin') ?></label></th>
<td><input type="hidden" name="aim" value="<?php echo esc_attr($_POST['aim']); ?>" /><?php echo esc_attr($_POST['aim']); ?></td>
</tr>
<?php endif; ?>
<?php		break;
			case "yim": ?>
<?php if( !empty($options['global_settings']["register_yim"]) ) : ?>
<tr>
<th><label for="yim"><?php _e('Yahoo IM', 'frontend-user-admin') ?></label></th>
<td><input type="hidden" name="yim" value="<?php echo esc_attr($_POST['yim']); ?>" /><?php echo esc_attr($_POST['yim']); ?></td>
</tr>
<?php endif; ?>
<?php		break;
			case "jabber": ?>
<?php if( !empty($options['global_settings']["register_jabber"]) ) : ?>
<tr>
<th><label for="jabber"><?php _e('Jabber / Google Talk', 'frontend-user-admin') ?></label></th>
<td><input type="hidden" name="jabber" value="<?php echo esc_attr($_POST['jabber']); ?>" /><?php echo esc_attr($_POST['jabber']); ?></td>
</tr>
<?php endif; ?>
<?php		break;
			case "description": ?>
<?php if( !empty($options['global_settings']["register_description"]) ) : ?>
<tr>
<th><label for="description"><?php _e('Biographical Info', 'frontend-user-admin'); ?></label></th>
<td><input type="hidden" name="description" value="<?php echo esc_attr($_POST['description']) ?>" /><?php echo esc_attr($_POST['description']) ?></td></tr>
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
<tr>
<th><label for="pass1"><?php _e('Password', 'frontend-user-admin'); ?></label></th>
<td>
<input type="hidden" name="pass1" value="<?php echo esc_attr($_POST['pass1']) ?>" />
<input type="hidden" name="pass2" value="<?php echo esc_attr($_POST['pass2']) ?>" /><?php echo preg_replace('/./','*',$_POST['pass1']); ?></td>
</tr>
<?php endif; ?>
<?php endif; ?>
<?php		break;
			default:
				if ( !empty($options['user_attribute']['user_attribute']) ) $count_user_attribute = count($options['user_attribute']['user_attribute']);
				else $count_user_attribute = 0;
				for($i=0;$i<$count_user_attribute;$i++) :
					if($options['user_attribute']['user_attribute'][$i]['name'] == $val && !empty($options['global_settings']["register_".$options['user_attribute']['user_attribute'][$i]['name']]) && empty($options['user_attribute']['user_attribute'][$i]['admin']) ) :
						if ( $options['user_attribute']['user_attribute'][$i]['type']=='breakpoint' ) :
							echo '</tbody></table>'.$options['user_attribute']['user_attribute'][$i]['default'].'<table class="form-table"><tbody>';
						elseif ( $options['user_attribute']['user_attribute'][$i]['type']=='hidden' ) :
							$hidden .= '<input type="hidden" name="'.esc_attr($options['user_attribute']['user_attribute'][$i]['name']).'" value="'.esc_attr($_POST[$val]).'" />'."\n";
						else : ?>
<tr>
<th><label for="<?php echo $val; ?>"><?php echo $options['user_attribute']['user_attribute'][$i]['label']; ?></label></th>
<td>
<?php
							switch($options['user_attribute']['user_attribute'][$i]['type']) :
								case 'display':
									if ( $options['user_attribute']['user_attribute'][$i]['type'] == $options['user_attribute']['user_attribute'][$i]['type2'] ) :
										echo $options['user_attribute']['user_attribute'][$i]['default'];
									endif;
									break;
								case 'text':
								case 'textarea':
?>
<input type="hidden" name="<?php echo $val; ?>" value="<?php echo esc_attr($_POST[$val]); ?>" /><?php echo esc_attr($_POST[$val]); ?>
<?php
									break;
								case 'select':
								case 'checkbox':
								case 'radio':
									preg_match_all('/"[^"]*"d?|\'[^\']*\'d?/', $options['user_attribute']['user_attribute'][$i]['default'], $matches);
									if ( isset($_POST[$val]) ) $_POST[$val] = maybe_unserialize($_POST[$val]);
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
										endif;
										if ( isset($_POST[$val]) && is_array($_POST[$val]) ) :
											foreach ( $_POST[$val] as $key2 => $val2 ) :
												if ( $value==$val2 ) :
							?>
<input type="hidden" name="<?php echo $val; ?>[]" value="<?php echo esc_attr($val2); ?>" /><?php echo $label; ?> 
							<?php
												endif;
											endforeach;
										else :
							?>
<?php							
											if ( isset($_POST[$val]) && $value==$_POST[$val] ) :
							?>
<input type="hidden" name="<?php echo $val; ?>" value="<?php echo esc_attr($_POST[$val]); ?>" /><?php echo $label; ?>
							<?php
											endif;
										endif;
									endforeach;
									break;
								case 'datetime':
									$profile_year = $profile_month = $profile_day = '';
									$profile_datetime = array();
									if ( isset($_POST[$val]) ) $profile_datetime = preg_split('/-|\s|:/', $_POST[$val]);
									$profile_year = isset($profile_datetime[0]) ? $profile_datetime[0] : '';
									$profile_month = isset($profile_datetime[1]) ? $profile_datetime[1] : '';
									$profile_day = isset($profile_datetime[2]) ? $profile_datetime[2] : '';
									$year = date('Y');
									$month = date('m');
									$day = date('d');
									if( !empty($options['user_attribute']['user_attribute'][$i]['default']) ) :
										$options['user_attribute']['user_attribute'][$i]['default'] = preg_replace('/yyyy/', $profile_year, $options['user_attribute']['user_attribute'][$i]['default']);
										$options['user_attribute']['user_attribute'][$i]['default'] = preg_replace('/mm/', $profile_month, $options['user_attribute']['user_attribute'][$i]['default']);
										$options['user_attribute']['user_attribute'][$i]['default'] = preg_replace('/dd/', $profile_day, $options['user_attribute']['user_attribute'][$i]['default']);
																					
										echo $options['user_attribute']['user_attribute'][$i]['default'];
?>
<input type="hidden" name="<?php echo $val; ?>" value="<?php echo esc_attr($_POST[$val]); ?>" />
<?php								endif;
									break;
								case 'file':
?>
<input type="file" name="<?php echo $val; ?>" id="<?php echo $val; ?>" class="regular-text" />
<?php
									break;
							endswitch;
?></td></tr><?php
						endif;
					break;
				endif;
				endfor;
		endswitch;
	endforeach;
?>
</tbody>
</table>
<?php if( !empty($options['global_settings']["terms_of_use_check"]) ) : ?>
<input type="hidden" name="terms_of_use" value="1" /> 
<?php endif; ?>

<?php if( !empty($options['global_settings']["password_registration"]) ) : ?>
<?php else: ?>
<p id="reg_passmail"><?php _e('A password will be e-mailed to you.', 'frontend-user-admin') ?></p>
<?php endif; ?>
<p class="submit"><?php if( !empty($options['global_settings']['confirmation_screen']) ) : ?> <input type="submit" name="wp-submit-confirmation" id="wp-submit-back" value="<?php _e('Back', 'frontend-user-admin'); ?>" class="submit confirmation back" /><?php endif; ?> <input type="submit" name="wp-submit-after-confirmation" id="wp-submit" value="<?php _e('Register &raquo;', 'frontend-user-admin'); ?>" class="submit register" /></p>
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