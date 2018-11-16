<?php
/*
Plugin Name: Frontend User Admin
Plugin URI: https://www.cmswp.jp/plugins/frontend_user_admin/
Description: This plugin makes it possible to manage users in the frontend side.
Author: Hiroaki Miyashita
Author URI: https://www.cmswp.jp/
Version: 3.2.1
Text Domain: frontend-user-admin
Domain Path: /
*/

/*  Copyright 2009 - 2018 Hiroaki Miyashita

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/

$frontend_user_admin_setting_version = 11;

include(WP_PLUGIN_DIR.'/frontend-user-admin/frontend-user-admin-adduser.php');
include(WP_PLUGIN_DIR.'/frontend-user-admin/frontend-user-admin-mail.php');
include(WP_PLUGIN_DIR.'/frontend-user-admin/frontend-user-admin-log.php');
include(WP_PLUGIN_DIR.'/frontend-user-admin/frontend-user-admin-importuser.php');
include(WP_PLUGIN_DIR.'/frontend-user-admin/frontend-user-admin-settings.php');

class frontend_user_admin {
	var $errors, $is_excerpt, $validkey;

	function __construct() {
		global $wp_version;

		add_action( 'init', array(&$this, 'frontend_user_admin_init') );
		add_action( 'admin_init', array(&$this, 'frontend_user_admin_admin_init') );
		add_action( 'admin_init', array(&$this, '_frontend_user_admin_delete_log') );
		add_action( 'widgets_init', array(&$this, 'frontend_user_admin_widgets_init') );
		add_filter( 'generate_rewrite_rules', array(&$this, 'frontend_user_admin_generate_rewrite_rules') ); 
		add_filter( 'query_vars', array(&$this, 'frontend_user_admin_query_vars') );
		add_action( 'template_redirect', array(&$this, 'template_redirect_intercept') );
		
		add_action( 'admin_menu', array(&$this, 'frontend_user_admin_admin_menu') );
		add_action( 'wp_head', array(&$this, 'frontend_user_admin_wp_head') );
		add_action( 'admin_head', array(&$this, 'frontend_user_admin_admin_head') );
		add_action( 'admin_print_scripts', array(&$this, 'frontend_user_admin_admin_print_scripts') );
		add_action( 'set_logged_in_cookie', array(&$this, 'frontend_user_admin_set_logged_in_cookie'), 10, 5 );
		add_action( 'wp_print_scripts', array(&$this, 'frontend_user_admin_wp_print_scripts') );
		add_action( 'add_meta_boxes', array(&$this, 'frontend_user_admin_add_meta_boxes') );
		add_action( 'save_post', array(&$this, 'frontend_user_admin_save_post'), 100 );
		add_action( 'edit_attachment', array(&$this, 'frontend_user_admin_save_post'), 100 );

		add_action( 'ktai_wp_head', array(&$this, 'frontend_user_admin_ktai_wp_head') );
		add_action( 'ktai_wp_footer', array(&$this, 'frontend_user_admin_ktai_wp_footer') );
		add_filter( 'comment_post_redirect', array(&$this, 'frontend_user_admin_comment_post_redirect') );

		add_filter( 'get_avatar', array(&$this, 'frontend_user_admin_get_avatar'), 10, 5 );
		add_filter( 'get_user_metadata', array(&$this, 'frontend_user_admin_get_user_metadata'), 10, 4 );

		$options = $this->get_frontend_user_admin_data();
		if( (!empty($options['global_settings']['login_url']) && $this->get_permalink() == $options['global_settings']['login_url']) || strstr($_SERVER['REQUEST_URI'], 'frontend-user-admin') ) :
			add_action( 'user_profile_update_errors', array(&$this, 'frontend_user_admin_edit_user'), 10, 3 );
		endif;
		if( !empty($options['global_settings']['login_url']) && strstr($this->get_permalink(), $options['global_settings']['login_url']) ) :
			add_filter( 'wp_title', array(&$this, 'return_wp_title'), 100 );
			add_filter( 'the_title', array(&$this, 'return_the_title'), 1 );
			add_filter( 'get_the_excerpt', array(&$this, 'frontend_user_admin_get_the_excerpt'), 1 );
			add_filter( 'the_content', array(&$this, 'frontend_user_admin_the_content') );
		endif;
		
		add_filter( 'auth_cookie_expiration', array(&$this, 'frontend_user_admin_auth_cookie_expiration'), 100, 3);
		add_action( 'wp_login', array(&$this, 'frontend_user_admin_wp_login') );
		add_action( 'check_passwords', array(&$this, 'frontend_user_admin_check_passwords'), 10, 3 );
		add_filter( 'sanitize_user', array(&$this, 'frontend_user_admin_sanitize_user'), 10, 3 );

		if(	!empty($options['global_settings']['transfer_all_to_login']) && $this->get_permalink() != $options['global_settings']['login_url'] && !$this->transfer_all_to_login_exception() ) :
			add_action( 'template_redirect', array(&$this, 'template_redirect_intercept2'));
		endif;
		if(	!empty($options['global_settings']['transfer_to_after_login_url']) && $this->get_permalink() == $options['global_settings']['login_url']) :
			add_action( 'template_redirect', array(&$this, 'template_redirect_intercept3'));
		endif;
		if(	!is_admin() && !empty($options['member_condition']) ) :
			add_action( 'template_redirect', array(&$this, 'frontend_user_admin_member_condition_template_redirect'));
			add_filter( 'the_title', array(&$this, 'frontend_user_admin_member_condition_the_title'), 10, 2);
			add_filter( 'the_title_feed', array(&$this, 'frontend_user_admin_member_condition_the_title'), 10, 2);
			add_filter( 'the_content', array(&$this, 'frontend_user_admin_member_condition_the_content'), 10);
			add_filter( 'the_content_feed', array(&$this, 'frontend_user_admin_member_condition_the_content'), 10);
			add_filter( 'the_posts', array(&$this, 'frontend_user_admin_the_posts') );
		endif;
		if(	!empty($options['global_settings']['disable_admin_bar']) && substr($wp_version, 0, 3) >= '3.1' ) :
			show_admin_bar(false);
		endif;
				
		if ( function_exists('add_shortcode') ) :
			add_shortcode( 'fua', array(&$this, 'frontend_user_admin_add_shortcode_fua') );
			add_shortcode( 'fuamc', array(&$this, 'frontend_user_admin_add_shortcode_fuamc') );
			add_shortcode( 'fuacu', array(&$this, 'frontend_user_admin_add_shortcode_fuacu') );
			add_shortcode( 'fualist', array(&$this, 'frontend_user_admin_add_shortcode_fualist') );
		endif;

		if( !empty($options['global_settings']['disable_password_change_email']) ) add_filter( 'send_password_change_email', '__return_false' );
		if( !empty($options['global_settings']['disable_email_change_email']) ) add_filter ( 'send_email_change_email', '__return_false' );
		
		add_action( 'wp_ajax_frontend_user_admin_get_user_meta', array(&$this, 'frontend_user_admin_get_user_meta') );
			
		add_filter('xmlrpc_methods', array(&$this, 'frontend_user_admin_xmlrpc_methods') );
		
		add_action('login_form', array(&$this, 'frontend_user_admin_login_form') );
		add_action('register_form', array(&$this, 'frontend_user_admin_register_form') );
		
		register_activation_hook( __FILE__, array(&$this, 'frontend_user_admin_register_activation_hook') );
	}

	function frontend_user_admin_ktai_wp_head() {
		ob_start();
	}

	function frontend_user_admin_ktai_wp_footer() {
		global $Ktai_Style;
		$Ktai_Style->admin->renew_session();

		$buffer = ob_get_contents();
		ob_end_clean();

		if ( !$Ktai_Style->ktai->get('cookie_available') ) :
			$buffer = $this->add_link_sid($buffer);
		endif;
		
		echo $buffer;
	}
	
	function frontend_user_admin_comment_post_redirect($location) {
		global $Ktai_Style;
		if ( function_exists('is_ktai') && is_ktai() ) :
			return $Ktai_Style->admin->add_sid($location);
		else :
			return $location;
		endif;
	}
	
	function add_link_sid($buffer) {
		global $Ktai_Style;
		
		if ( !$Ktai_Style->admin->get_sid()) {
			return $buffer;
		}
		for ($offset = 0, $replace = 'X' ; preg_match('!<a ([^>]*?)href=([\'"])(' . KtaiStyle::QUOTED_STRING_REGEX . ')\\2([^>]*?)>!s', $buffer, $l, PREG_OFFSET_CAPTURE, $offset) ; $offset += strlen($replace)) {
			$orig    = $l[0][0];
			$offset  = $l[0][1];
			$url     = $l[3][0];
			$url     = _ks_quoted_remove_query_arg(KtaiStyle_AdminTemplates::SESSION_NAME, $url);
			$attr1   = $l[1][0];
			$attr2   = $l[4][0];
			$replace = $orig;
			if ( preg_match('/#/', $url) ) :
				list($url, $sharp) = explode('#', $url);
				$sharp = '#'.$sharp;
			else :
				unset($sharp);
			endif;
			$replace = sprintf('<a %shref="%s"%s>', $attr1, $Ktai_Style->admin->add_sid($url, KTAI_DO_ECHO).$sharp, $attr2); 
			$buffer = substr_replace($buffer, $replace, $offset, strlen($orig)); // convert links
		}
		return $buffer;
	}

	function template_redirect_intercept2() {
		$options = $this->get_frontend_user_admin_data();

		if( !is_user_logged_in() ) {
			if ( !empty($options['global_settings']['transfer_all_to_alternative_url']) ) :
				wp_redirect($options['global_settings']['transfer_all_to_alternative_url']);
			else :
				wp_redirect($options['global_settings']['login_url']);
			endif;
		}
	}
	
	function template_redirect_intercept3() {
		global $current_user;
		$user = new WP_User($current_user->ID);
		$options = $this->get_frontend_user_admin_data();
		$url = @parse_url(preg_replace('/'.preg_quote($_SERVER['QUERY_STRING'],'/').'/',urlencode($_SERVER['QUERY_STRING']), $_SERVER['REQUEST_URI']));

		if( empty($url['query']) && empty($_POST) && (is_user_logged_in()) ) {
			$after_login_url = !empty($options['global_settings']['after_login_url']) ? $options['global_settings']['after_login_url'] : '';
			if ( !empty($user->redirect_to) ) $after_login_url = $user->redirect_to;
			wp_redirect($after_login_url);
		}
	}
	
	function frontend_user_admin_member_condition_template_redirect() {
		global $post, $wp_query;
		$options = $this->get_frontend_user_admin_data();
		
		if ( !is_singular() && !is_tax() && !is_category() ) return;

		$count = isset($options['member_condition']) ? count($options['member_condition'])+1 : 1;
		for($i=0;$i<$count;$i++) :
			$term_id = (isset($options['member_condition'][$i]['term_id']) && is_array($options['member_condition'][$i]['term_id'])) ? $options['member_condition'][$i]['term_id'] : '';
			if ( !empty($term_id) ) :
				if ( !empty($options['member_condition'][$i]['except_clawlers']) && $this->is_clawler() ) continue;
				if ( !empty($options['member_condition'][$i]['redirect_url']) ) :
					if ( (is_tax() || is_category() ) && $wp_query->is_main_query() && !in_array($wp_query->queried_object_id, $term_id) ) continue;
					if ( is_singular() ) :
						$taxonomies = get_object_taxonomies( $post, 'objects' );
						if ( is_array($taxonomies) ) :
							foreach ( $taxonomies as $taxname => $taxval ) :
								$taxnames[] = $taxname;
							endforeach;
							if ( isset($taxnames) && is_array($taxnames) ) :
								foreach( $taxnames as $taxname ) :
									$tax = get_the_terms(get_the_ID(), $taxname);
									if ( isset($tax) && is_array($tax) ) :
										foreach($tax as $tag) :
											if ( in_array($tag->term_id, $term_id) ) :
												$condition_flag = 1;
												break 2;
											endif;
										endforeach;
									endif;
								endforeach;
							endif;
							if ( empty($condition_flag) ) continue;
						endif;
					endif;
					if ( !is_user_logged_in() ) :
						$options['member_condition'][$i]['redirect_url'] = preg_replace('/%redirect_to%/', urlencode($this->get_permalink2()), $options['member_condition'][$i]['redirect_url']);
						wp_redirect($options['member_condition'][$i]['redirect_url']);
					else :
						if ( !empty($options['member_condition'][$i]['attribute']) && is_array($options['member_condition'][$i]['attribute']) ) :
							$count_attribute = count($options['member_condition'][$i]['attribute']);
							for($j=0;$j<$count_attribute;$j++) :
								if ( !$this->frontend_user_admin_is_member_condition($options['member_condition'][$i]['attribute'][$j]) ) :
									$condition_flag = 1;
									$fix_val = $i;
								else :
									if ( !empty($options['member_condition'][$i]['conjunction']) && $options['member_condition'][$i]['conjunction']=='OR' ) :
										$condition_flag = 0;
										if ( empty($options['member_condition'][$i]['attribute2']) ) break 2;
										else break;
									endif;							
								endif;
							endfor;
						endif;
						if ( !empty($options['member_condition'][$condition]['attribute2']) && is_array($options['member_condition'][$condition]['attribute2']) ) :
							if ( !empty($options['member_condition'][$condition]['uo_conjunction']) && $options['member_condition'][$condition]['uo_conjunction']=='AND' && $condition_flag == 1 ) :
								break;
							elseif ( !empty($options['member_condition'][$condition]['uo_conjunction']) && $options['member_condition'][$condition]['uo_conjunction']=='OR' && $condition_flag == 0 ) :
								break;
							endif;

							for($j=0;$j<count($options['member_condition'][$condition]['attribute2']);$j++) :
								if ( !$this->frontend_user_admin_is_member_condition2($options['member_condition'][$condition]['attribute2'][$j]) ) :
									$condition_flag = 1;
									$fix_val = $val;
								else :
									if ( !empty($options['member_condition'][$condition]['o_conjunction']) && $options['member_condition'][$condition]['o_conjunction']=='OR' ) :
										$condition_flag = 0;
										break 2;
									endif;							
								endif;
							endfor;
						endif;
					endif;
					if ( !empty($condition_flag) && isset($fix_val) ) :
						$options['member_condition'][$fix_val]['redirect_url'] = preg_replace('/%redirect_to%/', urlencode($this->get_permalink2()), $options['member_condition'][$fix_val]['redirect_url']);
						wp_redirect($options['member_condition'][$fix_val]['redirect_url']);
					elseif ( $this->is_clawler() ) :
						add_action( 'wp_head', array(&$this, 'frontend_user_admin_wp_head_clawler') );
					endif;
				endif;
			endif;
		endfor;
		
		if ( !is_singular() ) return;
		
		$fuamc = get_post_meta($post->ID, 'fuamc', true);
		$fuamc = explode(',', $fuamc);
		$fuamc = array_unique(array_map('trim', $fuamc));
		
		if ( $post->post_parent ) :
			$fuamc_parent = get_post_meta($post->post_parent, 'fuamc', true);
			$fuamc_parent = explode(',', $fuamc_parent);
			$fuamc_parent = array_unique(array_map('trim', $fuamc_parent));
			foreach ( $fuamc_parent as $val ) :
				if ( !empty($options['member_condition'][$val]['apply_to_subpages']) ) :
					$fuamc[] = $val;
				endif;
			endforeach;
			$fuamc = array_values(array_filter($fuamc, 'strlen'));
		endif;
		
		foreach ( $fuamc as $val ) :
			if ( !empty($options['member_condition'][$val]['except_clawlers']) && $this->is_clawler() ) continue;
			if ( !empty($options['member_condition'][$val]['redirect_url']) ) :
				if ( !is_user_logged_in() ) :
					$options['member_condition'][$val]['redirect_url'] = preg_replace('/%redirect_to%/', urlencode($this->get_permalink2()), $options['member_condition'][$val]['redirect_url']);
					wp_redirect($options['member_condition'][$val]['redirect_url']);
				else :
					if ( !empty($options['member_condition'][$val]['attribute']) && is_array($options['member_condition'][$val]['attribute']) ) :
						for($i=0;$i<count($options['member_condition'][$val]['attribute']);$i++) :
							if ( !$this->frontend_user_admin_is_member_condition($options['member_condition'][$val]['attribute'][$i]) ) :
								$condition_flag = 1;
								$fix_val = $val;
							else :
								if ( !empty($options['member_condition'][$val]['conjunction']) && $options['member_condition'][$val]['conjunction']=='OR' ) :
									$condition_flag = 0;
									if ( empty($options['member_condition'][$val]['attribute2']) ) break 2;
									else break;
								endif;							
							endif;
						endfor;
					endif;
				endif;
				if ( !empty($options['member_condition'][$condition]['attribute2']) && is_array($options['member_condition'][$condition]['attribute2']) ) :
					if ( !empty($options['member_condition'][$val]['attribute']) && !empty($options['member_condition'][$condition]['uo_conjunction']) && $options['member_condition'][$condition]['uo_conjunction']=='AND' && $condition_flag == 1 ) :
						break;
					elseif ( !empty($options['member_condition'][$val]['attribute']) && !empty($options['member_condition'][$condition]['uo_conjunction']) && $options['member_condition'][$condition]['uo_conjunction']=='OR' && $condition_flag == 0 ) :
						break;
					endif;

					for($j=0;$j<count($options['member_condition'][$condition]['attribute2']);$j++) :
						if ( !$this->frontend_user_admin_is_member_condition2($options['member_condition'][$condition]['attribute2'][$j]) ) :
							$condition_flag = 1;
							$fix_val = $val;
						else :
							if ( !empty($options['member_condition'][$condition]['o_conjunction']) && $options['member_condition'][$condition]['o_conjunction']=='OR' ) :
								$condition_flag = 0;
								break 2;
							endif;							
						endif;
					endfor;
				endif;
			else :
				unset($condition_flag);
				if ( !empty($options['member_condition'][$val]['attribute']) && is_array($options['member_condition'][$val]['attribute']) ) :
					for($i=0;$i<count($options['member_condition'][$val]['attribute']);$i++) :
						if ( !$this->frontend_user_admin_is_member_condition($options['member_condition'][$val]['attribute'][$i]) ) :
							$condition_flag = 1;
						else :
							if ( !empty($options['member_condition'][$val]['conjunction']) && $options['member_condition'][$val]['conjunction']=='OR' ) :
								$condition_flag = 0;
								if ( empty($options['member_condition'][$val]['attribute2']) ) break 2;
								else break;
							endif;							
						endif;
					endfor;
				endif;
				if ( !empty($options['member_condition'][$val]['attribute2']) && is_array($options['member_condition'][$val]['attribute2']) ) :
					if ( !empty($options['member_condition'][$val]['attribute']) && !empty($options['member_condition'][$val]['uo_conjunction']) && $options['member_condition'][$val]['uo_conjunction']=='AND' && $condition_flag == 1 ) :
						break;
					elseif ( !empty($options['member_condition'][$val]['attribute']) && !empty($options['member_condition'][$val]['uo_conjunction']) && $options['member_condition'][$val]['uo_conjunction']=='OR' && $condition_flag == 0 ) :
						break;
					endif;

					for($j=0;$j<count($options['member_condition'][$val]['attribute2']);$j++) :
						if ( !$this->frontend_user_admin_is_member_condition2($options['member_condition'][$val]['attribute2'][$j]) ) :
							$condition_flag = 1;
							$fix_val = $val;
						else :
							if ( !empty($options['member_condition'][$val]['o_conjunction']) && $options['member_condition'][$val]['o_conjunction']=='OR' ) :
								$condition_flag = 0;
								break 2;
							endif;							
						endif;
					endfor;
				endif;
			endif;
			if ( empty($condition_flag) ) return;
		endforeach;
		
		if ( !empty($condition_flag) && isset($fix_val) ) :
			$options['member_condition'][$fix_val]['redirect_url'] = preg_replace('/%redirect_to%/', urlencode($this->get_permalink2()), $options['member_condition'][$fix_val]['redirect_url']);
			wp_redirect($options['member_condition'][$fix_val]['redirect_url']);
		elseif ( $this->is_clawler() ) :
			add_action( 'wp_head', array(&$this, 'frontend_user_admin_wp_head_clawler') );
		endif;
	}
	
	function frontend_user_admin_is_member($condition) {
		$options = $this->get_frontend_user_admin_data();

		$condition_flag = 0;
		$true_or_false = false;
		
		if ( !empty($options['member_condition'][$condition]) && is_array($options['member_condition'][$condition]) ) :
			if ( !empty($options['member_condition'][$condition]['attribute']) && is_array($options['member_condition'][$condition]['attribute']) ) :
				for($i=0;$i<count($options['member_condition'][$condition]['attribute']);$i++) :
					if ( !$this->frontend_user_admin_is_member_condition($options['member_condition'][$condition]['attribute'][$i]) ) :
						$condition_flag = 1;
					else :
						if ( !empty($options['member_condition'][$condition]['conjunction']) && $options['member_condition'][$condition]['conjunction']=='OR' ) :
							$condition_flag = 0;
							if ( empty($options['member_condition'][$condition]['attribute2']) ) break;
							else break;
						endif;							
					endif;
				endfor;
				if ( $condition_flag == 0 ) :
					$true_or_false = true;
				endif;
			endif;
			if ( !empty($options['member_condition'][$condition]['attribute2']) && is_array($options['member_condition'][$condition]['attribute2']) ) :
				if ( !empty($options['member_condition'][$val]['attribute']) && !empty($options['member_condition'][$condition]['uo_conjunction']) && $options['member_condition'][$condition]['uo_conjunction']=='AND' && $condition_flag == 1 ) :
					return $true_or_false;
				elseif ( !empty($options['member_condition'][$val]['attribute']) && !empty($options['member_condition'][$condition]['uo_conjunction']) && $options['member_condition'][$condition]['uo_conjunction']=='OR' && $condition_flag == 0 ) :
					return $true_or_false;
				endif;

				for($i=0;$i<count($options['member_condition'][$condition]['attribute2']);$i++) :
					if ( !$this->frontend_user_admin_is_member_condition2($options['member_condition'][$condition]['attribute2'][$i]) ) :
						$condition_flag = 1;
					else :
						if ( !empty($options['member_condition'][$condition]['o_conjunction']) && $options['member_condition'][$condition]['o_conjunction']=='OR' ) :
							$condition_flag = 0;
							break;
						endif;							
					endif;
				endfor;
				if ( $condition_flag == 0 ) :
					$true_or_false = true;
				endif;
			endif;		
			if ( empty($options['member_condition'][$condition]['attribute']) && empty($options['member_condition'][$condition]['attribute2']) && is_user_logged_in() ) :
				$true_or_false = true;
			endif;
		endif;
		
		return $true_or_false;
	}
	
	function frontend_user_admin_is_member_condition($attribute) {
		global $current_user;

		if ( isset($attribute['attribute_key']) && isset($attribute['attribute_value']) ) :
			if ( !empty($attribute['code']) ) :
				$attribute['attribute_key'] = eval($attribute['attribute_key']);
				$attribute['attribute_value'] = eval($attribute['attribute_value']);
			else :
				$attribute['attribute_key'] = $current_user->{$attribute['attribute_key']};
			endif;

			$values = explode(" ", $attribute['attribute_value']);
			$values = array_filter( $values );
			$values = array_unique(array_filter(array_map('trim', $values)));
			foreach( $values as $value ) :
				if ( is_array($attribute['attribute_key']) ) :
					$attribute_keys = $attribute['attribute_key'];
				else :
					$attribute_keys[] = $attribute['attribute_key'];
				endif;
				foreach ( $attribute_keys as $attribute_key ) :
					switch ( $attribute['nm'] ) :
						case 'p' :
							if ( preg_match('/'.$value.'/', $attribute_key) ) return true;
							break;
						case 'f' :
							if ( $attribute_key == $value ) return true;
							break;
						case '!=' :
							if ( $attribute_key != $value ) return true;
							break;
						case '>=' :
							if ( $attribute_key >= $value ) return true;
							break;
						case '<=' :
							if ( $attribute_key <= $value ) return true;
							break;
						case '>' :
							if ( $attribute_key > $value ) return true;
							break;
						case '<' :
							if ( $attribute_key > $value ) return true;
							break;
						default :
							if ( $attribute_key == $value ) return true;
							break;
					endswitch;
				endforeach;
			endforeach;
			return false;
		else :
			return true;
		endif;
	}
	
	function frontend_user_admin_is_member_condition2($attribute) {
		if ( isset($attribute['order_key']) && isset($attribute['order_value']) ) :
			global $order_management, $current_user;
			$options = get_option('net_shop_admin');
			list($result, $supplement) = $order_management->select_order_management_data(array(
										'q' => trim($attribute['order_value']),
										't' => trim($attribute['order_key']),
										'm' => trim($attribute['nm']),
										'os' => $options['status_options']['last_status'],
										'user_id' => $current_user->ID,
										'posts_per_page' => 1));
			if ( !empty($result) && is_array($result) ) :
				return true;
			else :
				return false;
			endif;
		else :
			return true;
		endif;
	}
	
	function frontend_user_admin_member_condition_the_title($title, $post_id = null) {
		global $post;
		if ( !isset($post) || $post->ID != $post_id ) return $title;

		$options = $this->get_frontend_user_admin_data();

		$fuamc = get_post_meta($post->ID, 'fuamc', true);
		$fuamc = explode(',', $fuamc);
		$fuamc = array_unique(array_map('trim', $fuamc));
		
		if ( $post->post_parent ) :
			$fuamc_parent = get_post_meta($post->post_parent, 'fuamc', true);
			$fuamc_parent = explode(',', $fuamc_parent);
			$fuamc_parent = array_unique(array_map('trim', $fuamc_parent));
			foreach ( $fuamc_parent as $val ) :
				if ( !empty($options['member_condition'][$val]['apply_to_subpages']) ) :
					$fuamc[] = $val;
				endif;
			endforeach;
			$fuamc = array_values(array_filter($fuamc, 'strlen'));
		endif;

		foreach ( $fuamc as $val ) :
			$condition_flag = 0;
			if ( !empty($options['member_condition'][$val]['except_clawlers']) && $this->is_clawler() ) continue;
			if ( !empty($options['member_condition'][$val]['the_title']) ) :
				if ( !is_user_logged_in() ) :
					return $this->EvalBuffer($options['member_condition'][$val]['the_title']);
				endif;
				if ( !empty($options['member_condition'][$val]['attribute']) && is_array($options['member_condition'][$val]['attribute']) ) :
					for($i=0;$i<count($options['member_condition'][$val]['attribute']);$i++) :
						if ( !$this->frontend_user_admin_is_member_condition($options['member_condition'][$val]['attribute'][$i]) ) :
							$condition_flag = 1;
							$fix_val = $val;
						else :
							if ( !empty($options['member_condition'][$val]['conjunction']) && $options['member_condition'][$val]['conjunction']=='OR' ) :
								$condition_flag = 0;
								if ( empty($options['member_condition'][$val]['attribute2']) ) break 2;
								else break;
							endif;							
						endif;
					endfor;
				endif;
				if ( !empty($options['member_condition'][$val]['attribute2']) && is_array($options['member_condition'][$val]['attribute2']) ) :
					if ( !empty($options['member_condition'][$val]['attribute']) && !empty($options['member_condition'][$val]['uo_conjunction']) && $options['member_condition'][$val]['uo_conjunction']=='AND' && $condition_flag == 1 ) :
						break;
					elseif ( !empty($options['member_condition'][$val]['attribute']) && !empty($options['member_condition'][$val]['uo_conjunction']) && $options['member_condition'][$val]['uo_conjunction']=='OR' && $condition_flag == 0 ) :
						break;
					endif;

					for($i=0;$i<count($options['member_condition'][$val]['attribute2']);$i++) :
						if ( !$this->frontend_user_admin_is_member_condition2($options['member_condition'][$val]['attribute2'][$i]) ) :
							$condition_flag = 1;
							$fix_val = $val;
						else :
							if ( !empty($options['member_condition'][$val]['o_conjunction']) && $options['member_condition'][$val]['o_conjunction']=='OR' ) :
								$condition_flag = 0;
								break 2;
							endif;							
						endif;
					endfor;
				endif;
			endif;
		endforeach;
		if ( !empty($condition_flag) && isset($fix_val) ) return $this->EvalBuffer($options['member_condition'][$fix_val]['the_title']);
		
		return $title;
	}
	
	function frontend_user_admin_member_condition_the_content($content) {
		global $post;
		
		$options = $this->get_frontend_user_admin_data();
		
		$fuamc = get_post_meta($post->ID, 'fuamc', true);
		$fuamc = explode(',', $fuamc);
		$fuamc = array_unique(array_map('trim', $fuamc));
		
		if ( $post->post_parent ) :
			$fuamc_parent = get_post_meta($post->post_parent, 'fuamc', true);
			$fuamc_parent = explode(',', $fuamc_parent);
			$fuamc_parent = array_unique(array_map('trim', $fuamc_parent));
			foreach ( $fuamc_parent as $val ) :
				if ( !empty($options['member_condition'][$val]['apply_to_subpages']) ) :
					$fuamc[] = $val;
				endif;
			endforeach;
			$fuamc = array_values(array_filter($fuamc, 'strlen'));
		endif;
		
		foreach ( $fuamc as $val ) :
			$condition_flag = 0;
			$excerpt_content = '';
			if ( !empty($options['member_condition'][$val]['except_clawlers']) && $this->is_clawler() ) continue;
			if ( !empty($options['member_condition'][$val]['auto_excerpt']) ) $excerpt_content = $this->mb_strimwidth_with_elements($content, $options['member_condition'][$val]['auto_excerpt'], __('...', 'frontend-user-admin'), true, true);

			if ( !empty($options['member_condition'][$val]['the_content']) ) :
				if ( !is_user_logged_in() ) :
					if ( !empty($options['member_condition'][$val]['until_more']) ) :
						$return_content = preg_replace('/<span id="more-\d+"><\/span>.*$/s', $options['member_condition'][$val]['the_content'], $content);
						if ( !preg_match('/<span id="more-\d+"><\/span>.*$/s', $content) && !empty($excerpt_content) ) :
							$return_content = $excerpt_content . $options['member_condition'][$val]['the_content'];
						endif;
					else :
						$return_content = $excerpt_content . $options['member_condition'][$val]['the_content'];
					endif;
					if ( !empty($return_content) ) : return $this->EvalBuffer($return_content); endif;
				else :
					if ( !empty($options['member_condition'][$val]['until_more']) ) :
						$return_content = preg_replace('/<span id="more-\d+"><\/span>.*$/s', $options['member_condition'][$val]['the_content'], $content);
						if ( !preg_match('/<span id="more-\d+"><\/span>.*$/s', $content) && !empty($excerpt_content) ) :
							$return_content = $excerpt_content . $options['member_condition'][$val]['the_content'];
						endif;
					else :
						$return_content = $excerpt_content . $options['member_condition'][$val]['the_content'];
					endif;
					if ( !empty($options['member_condition'][$val]['attribute']) && is_array($options['member_condition'][$val]['attribute']) ) :
						for($i=0;$i<count($options['member_condition'][$val]['attribute']);$i++) :
							if ( !$this->frontend_user_admin_is_member_condition($options['member_condition'][$val]['attribute'][$i]) ) :
								$condition_flag = 1;
							else :
								if ( !empty($options['member_condition'][$val]['conjunction']) && $options['member_condition'][$val]['conjunction']=='OR' ) :
									$condition_flag = 0;
									if ( empty($options['member_condition'][$val]['attribute2']) ) :
										unset($return_content);
										break 2;
									else :
										break;
									endif;
								endif;
							endif;
						endfor;
					endif;
					if ( !empty($options['member_condition'][$val]['attribute2']) && is_array($options['member_condition'][$val]['attribute2']) ) :
						if ( !empty($options['member_condition'][$val]['attribute']) && !empty($options['member_condition'][$val]['uo_conjunction']) && $options['member_condition'][$val]['uo_conjunction']=='AND' && $condition_flag == 1 ) :
							break;
						elseif ( !empty($options['member_condition'][$val]['attribute']) && !empty($options['member_condition'][$val]['uo_conjunction']) && $options['member_condition'][$val]['uo_conjunction']=='OR' && $condition_flag == 0 ) :
							unset($return_content);
							break;
						endif;

						for($i=0;$i<count($options['member_condition'][$val]['attribute2']);$i++) :
							if ( !$this->frontend_user_admin_is_member_condition2($options['member_condition'][$val]['attribute2'][$i]) ) :
								$condition_flag = 1;
								$fix_val = $val;
							else :
								if ( !empty($options['member_condition'][$val]['o_conjunction']) && $options['member_condition'][$val]['o_conjunction']=='OR' ) :
									$condition_flag = 0;
									unset($return_content);
									break 2;
								endif;							
							endif;
						endfor;
					endif;
					if ( $condition_flag==0 ) :
						unset($return_content);
						break;		
					endif;
				endif;
			elseif ( !empty($options['member_condition'][$val]['until_more']) ) :
				$return_content = preg_replace('/<span id="more-\d+"><\/span>.*$/s', $options['member_condition'][$val]['the_content'], $content);
				if ( !empty($excerpt_content) && mb_strlen($return_content) > mb_strlen($excerpt_content . $options['member_condition'][$val]['the_content']) ) :
					$return_content = $excerpt_content . $options['member_condition'][$val]['the_content'];
				endif;
			endif;
		endforeach;
		if ( !empty($return_content) ) return $this->EvalBuffer($return_content);
		
		return $content;
	}
		
	function transfer_all_to_login_exception() {
		$options = $this->get_frontend_user_admin_data();
		if ( !empty($options['global_settings']['transfer_all_to_login_exception']) ) :
			$exception_url = explode("\n", $options['global_settings']['transfer_all_to_login_exception']);
			$exception_url = array_filter( $exception_url );
			$exception_url = array_unique(array_filter(array_map('trim', $exception_url)));
			foreach( $exception_url as $url ) :
				if ( user_trailingslashit($url) == get_option('home') ) :
					if ( $url == $this->get_permalink() ) :
						return true;
					endif;
				else :
					if ( preg_match('/^'.preg_quote($url,'/').'/', $this->get_permalink()) ) :
						return true;
					endif;
				endif;
			endforeach;
		endif;
		return false;
	}
	
	function get_permalink() {
		$page_id = '';
		if( is_ssl() || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO']=='https') ) 
			$scheme = "https://";
		else
			$scheme = "http://";
		$host = $_SERVER['HTTP_HOST'];
		$url = @parse_url(preg_replace('/'.preg_quote($_SERVER['QUERY_STRING'],'/').'/',urlencode($_SERVER['QUERY_STRING']), $_SERVER['REQUEST_URI']));
		if ( isset($url['query']) && preg_match('/page_id\%3D([0-9]+)/', $url['query'], $match) ) $page_id = '?page_id='.$match[1];
		if ( get_option('wordpress-https_sharedssl') == 1 && get_option('wordpress-https_sharedssl_host') != '' && !empty($_SERVER['HTTP_X_FORWARDED_FOR']) ) :
			return get_option('wordpress-https_sharedssl_host') . $url['path'] . $page_id;
		else :
			return $scheme . $host . $url['path'] . $page_id;
		endif;
	}
	
	function get_permalink2() {
		if( is_ssl() ) 
			$scheme = "https://";
		else
			$scheme = "http://";
		return $scheme . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
	}
	
	function get_user_to_edit($user_id = null) {
		global $current_user;
		if ( !$user_id ) $user_id = $current_user->ID;
		$user = new WP_User( $user_id );
		$user->user_id      = esc_attr($user->ID);
		$user->user_login   = esc_attr($user->user_login);
		$user->user_email   = esc_attr($user->user_email);
		$user->user_url     = esc_url($user->user_url);
		$user->first_name   = esc_attr($user->first_name);
		$user->last_name    = esc_attr($user->last_name);
		$user->display_name = esc_attr($user->display_name);
		$user->nickname     = esc_attr($user->nickname);
		$user->aim          = esc_attr($user->aim);
		$user->yim          = esc_attr($user->yim);
		$user->jabber       = esc_attr($user->jabber);
		$user->description  = esc_html($user->description);

		return $user;
	}
	
	function get_frontend_user_admin_data() {
		$options = get_option('frontend_user_admin');
		return $options;
	}
	
	function return_frontend_user_admin_login_url($suffix = '?') {
		$options = get_option('frontend_user_admin');
		if ( preg_match('/\?/', $options['global_settings']['login_url']) ) return $options['global_settings']['login_url'].'&';
		else return $options['global_settings']['login_url'].$suffix;
	}
	
	function frontend_user_admin_register_activation_hook() {
		$options = $this->get_frontend_user_admin_data();
		if ( empty($options) ) $this->install_frontend_user_admin_data();
	}
	
	function frontend_user_admin_init() {
		global $net_shop_admin, $current_user;
		$options = $this->get_frontend_user_admin_data();
		
		if ( function_exists('load_plugin_textdomain') ) :
			if ( !defined('WP_PLUGIN_DIR') ) 
				load_plugin_textdomain('frontend-user-admin', str_replace( ABSPATH, '', dirname(__FILE__) ) );
			else
				load_plugin_textdomain('frontend-user-admin', false, dirname( plugin_basename(__FILE__) ) );
		endif;

		if ( function_exists('is_ktai') && is_ktai() && !is_admin() ) :
			global $Ktai_Style;
			if ( !class_exists('KtaiStyle_Admin') )
				require(WP_PLUGIN_DIR . '/ktai-style/admin/class.php');
			require(WP_PLUGIN_DIR . '/ktai-style/admin/templates.php');
			$Ktai_Style->admin = new KtaiStyle_AdminTemplates;
			if( !$Ktai_Style->admin->check_session() ) :
				unset($_REQUEST['ksid'],$_POST['ksid'],$_GET['ksid']);
			endif;
			$Ktai_Style->admin->renew_session();
		endif;
		
		if ( !empty($options['global_settings']['register_order']) && is_array($options['global_settings']['register_order']) && !empty($options['global_settings']['profile_order']) && is_array($options['global_settings']['profile_order']) ) :
			if ( !in_array('role', $options['global_settings']['register_order']) && !in_array('role', $options['global_settings']['profile_order']) ) :
				$options['global_settings']['register_order'][] = 'role';
				$options['global_settings']['profile_order'][] = 'role';
				update_option('frontend_user_admin', $options);
			endif;
			if ( !in_array('user_status', $options['global_settings']['register_order']) && !in_array('user_status', $options['global_settings']['profile_order']) ) :
				$options['global_settings']['register_order'][] = 'user_status';
				$options['global_settings']['profile_order'][] = 'user_status';
				update_option('frontend_user_admin', $options);
			endif;
			if ( !in_array('no_log', $options['global_settings']['register_order']) && !in_array('no_log', $options['global_settings']['profile_order']) ) :
				$options['global_settings']['register_order'][] = 'no_log';
				$options['global_settings']['profile_order'][] = 'no_log';
				update_option('frontend_user_admin', $options);
			endif;
			if ( !in_array('duplicate_login', $options['global_settings']['register_order']) && !in_array('duplicate_login', $options['global_settings']['profile_order']) ) :
				$options['global_settings']['register_order'][] = 'duplicate_login';
				$options['global_settings']['profile_order'][] = 'duplicate_login';
				update_option('frontend_user_admin', $options);
			endif;			
		endif;
		
		if( !empty($options['global_settings']['login_url']) && $this->get_permalink() == $options['global_settings']['login_url'] ) :
			$this->frontend_user_admin_action();

			if ( !empty($options['global_settings']['use_password_strength']) ) :
				wp_localize_script('password-strength-meter', 'pwsL10n', array(
					'empty' => __('Strength indicator', 'frontend-user-admin'),
					'short' => __('Very weak', 'frontend-user-admin'),
					'bad' => __('Bad', 'frontend-user-admin'),
					'good' => __('Good', 'frontend-user-admin'),
					'strong' => __('Strong', 'frontend-user-admin'),
					'mismatch' => __('Mismatch', 'frontend-user-admin')
				) );
			endif;			
		endif;

		if ( is_user_logged_in() && !is_admin() && !empty($options['global_settings']['logout_time']) && (empty($options['global_settings']['logout_time_except_administrators']) || (!empty($options['global_settings']['logout_time_except_administrators']) && !current_user_can('administrator'))) ) :
			if ( ((date_i18n('U')-get_user_meta($current_user->ID, 'login_datetime', true))/60) > $options['global_settings']['logout_time'] ) :
				delete_user_meta( $current_user->ID, 'login_datetime');
				wp_logout();
				wp_redirect($options['global_settings']['login_url']);
				exit();
			endif;
		endif;
		
		if ( is_user_logged_in() && !is_admin() && !empty($options['global_settings']['record_login_datetime']) ) :
			update_user_meta( $current_user->ID, 'login_datetime', date_i18n('U'));
		endif;

		if ( is_user_logged_in() && !is_admin() && !empty($options['global_settings']['password_expiration_date']) && !($this->get_permalink() == $options['global_settings']['login_url'] && ($_REQUEST['action'] == 'profile' || $_REQUEST['action'] == 'update')) ) :
			if ( !empty($current_user->password_changed_time) && ($current_user->password_changed_time+$options['global_settings']['password_expiration_date']*86400)<date_i18n('U') ) :
				$redirect_to = $this->return_frontend_user_admin_login_url()."action=profile";
				if ( function_exists('is_ktai') && is_ktai() ) :
					if ( !$Ktai_Style->admin->base->ktai->get('cookie_available') ) :
						$redirect_to = $Ktai_Style->admin->add_sid($redirect_to);
							
						if ( !empty($net_shop_admin) ) :
							$redirect_to = $net_shop_admin->net_shop_admin_add_sid($redirect_to);
						endif;
					endif;
				endif;
				wp_redirect($redirect_to);
				exit();
			endif;
		endif;
			
		if ( is_user_logged_in() && !is_admin() && !empty($options['global_settings']['required_check']) && $this->frontend_user_admin_check_required($current_user) && !($this->get_permalink() == $options['global_settings']['login_url'] && ($_REQUEST['action'] == 'profile' || $_REQUEST['action'] == 'update')) ) :
			$redirect_to = $this->return_frontend_user_admin_login_url().'action=profile&required=true';
			if ( function_exists('is_ktai') && is_ktai() ) :
				$redirect_to = $Ktai_Style->admin->add_sid($redirect_to);
				if ( !empty($net_shop_admin) ) $redirect_to = $net_shop_admin->net_shop_admin_add_sid($redirect_to);
			endif;
			wp_redirect($redirect_to);
			exit();
		endif;
		
		if ( is_user_logged_in() && is_multisite() && !is_admin() && !empty($options['global_settings']['logout_other_sites']) ) :
			$blog_id = get_current_blog_id();
			if ( !current_user_can_for_blog($blog_id, 'subscriber') && !current_user_can_for_blog($blog_id, 'contributor') && !current_user_can_for_blog($blog_id, 'author') && !current_user_can_for_blog($blog_id, 'editor') && !current_user_can_for_blog($blog_id, 'administrator') ) :
				wp_logout();
			endif;
		endif;
		
		//if ( current_user_can('administrator') ) ini_set('display_errors', 1);
		$this->frontend_user_admin_user_log();
	}
	
	function frontend_user_admin_admin_init() {
		global $current_user;
		$options = $this->get_frontend_user_admin_data();

		$this->upgrade_frontend_user_admin();

		if ( !empty($_REQUEST['page']) && $_REQUEST['page'] == 'frontend-user-admin/frontend-user-admin-settings.php' && isset($_REQUEST['step']) ) :
			if ( in_array($_REQUEST['step'], array('widget', 'lostpassword', 'mobile_lostpassword', 'sp_lostpassword', 'register', 'mobile_register', 'sp_register', 'confirmation', 'mobile_confirmation', 'sp_confirmation', 'profile', 'mobile_profile', 'sp_profile', 'withdrawal', 'mobile_withdrawal', 'sp_withdrawal', 'login', 'mobile_login', 'sp_login')) ) :
				echo file_get_contents(WP_PLUGIN_DIR.'/frontend-user-admin/steps/step-'.$_REQUEST['step'].'.php');
			endif;
			exit();
		endif;
		
		if ( isset($options['global_settings']['admin_panel_user_level']) && $options['global_settings']['admin_panel_user_level'] > (int)$current_user->user_level && !strstr($_SERVER['REQUEST_URI'], 'async-upload.php') && !strstr($_SERVER['REQUEST_URI'], 'admin-ajax.php') )
			wp_redirect($options['global_settings']['login_url']);

		if ( strstr($_SERVER['REQUEST_URI'], 'frontend-user-admin') ) :
			add_thickbox();
			$this->frontend_user_admin_user_management_action();
		endif;
	}
	
	function frontend_user_admin_widgets_init() {
		register_widget('WP_Widget_Frontend_User_Admin');
	}
	
	function upgrade_frontend_user_admin() {
		global $wpdb, $charset_collate, $frontend_user_admin_setting_version;
		$options = $this->get_frontend_user_admin_data();
		if ( !empty($options['current_setting_version']) && $options['current_setting_version'] >= $frontend_user_admin_setting_version ) return; 

		if ( !empty($options['current_setting_version']) && $options['current_setting_version'] < 1 ) :
			$options = stripslashes_deep($options);
			$update_flag = 1;
		endif;

		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
		
		if ( !empty($options['current_setting_version']) && $options['current_setting_version'] < 2 ) :
			$table = "CREATE TABLE `".$wpdb->prefix."usermail` (
  `umail_id` INT(10) unsigned NOT NULL auto_increment,
  `user_id` INT(10) unsigned DEFAULT '0' NOT NULL,
  `umail_from` TEXT,
  `umail_to` TEXT,
  `umail_cc` TEXT,
  `umail_bcc` TEXT,
  `umail_template` TEXT,
  `umail_subject` TEXT,
  `umail_body` TEXT,
  `umail_regtime` DATETIME NOT NULL,
  `umail_del` TINYINT(1) unsigned DEFAULT '0' NOT NULL,
  PRIMARY KEY  (`umail_id`)
  ) $charset_collate;";
			maybe_create_table($wpdb->prefix."usermail", $table);
			$update_flag = 1;
		endif;
		
		if ( !empty($options['current_setting_version']) && $options['current_setting_version'] < 5 ) :
			$options['mail_options']['mail_from'] = str_replace('From: ', '', $options['mail_options']['mail_from']);
			$update_flag = 1;
		endif;

		if ( !empty($options['current_setting_version']) && $options['current_setting_version'] < 6 ) :
			add_clean_index($wpdb->prefix."userlog", 'user_id');
			add_clean_index($wpdb->prefix."userlog", 'ip');
			add_clean_index($wpdb->prefix."usermail", 'user_id');
			$update_flag = 1;
		endif;
		
		if ( !empty($options['current_setting_version']) && $options['current_setting_version'] < 8 ) :
			if ( !empty($options['global_settings']['register_order']) && is_array($options['global_settings']['register_order']) && !empty($options['global_settings']['profile_order']) && is_array($options['global_settings']['profile_order']) && !in_array('user_status', $options['global_settings']['register_order']) && !in_array('user_status', $options['global_settings']['profile_order']) ) :
				$options['global_settings']['register_order'][] = 'user_status';
				$options['global_settings']['register_order'][] = 'no_log';
				$options['global_settings']['register_order'][] = 'duplicate_login';
				$options['global_settings']['profile_order'][] = 'user_status';
				$options['global_settings']['profile_order'][] = 'no_log';
				$options['global_settings']['profile_order'][] = 'duplicate_login';
				$update_flag = 1;
			endif;
		endif;

		if ( !empty($options['current_setting_version']) && $options['current_setting_version'] < 9 ) :
			$options['member_condition_types'] = $options['global_settings']['member_condition_types'];
			$update_flag = 1;
		endif;

		if ( !empty($options['current_setting_version']) && $options['current_setting_version'] < 10 ) :
			$options['mail_options']['email_confirmation_first_user_subject'] = sprintf(__('[%s] Email Confirmation', 'frontend-user-admin'), get_option('blogname'));
			$options['mail_options']['email_confirmation_first_user_body'] = __('Please click the following address and register the site.', 'frontend-user-admin')."\r\n\r\n";
			$options['mail_options']['email_confirmation_first_user_body'] .= '%login_url%'."?action=register&key=%key%\r\n";
			$update_flag = 1;
		endif;

		if ( !empty($options['current_setting_version']) && $options['current_setting_version'] < 11 ) :
			$options['global_settings']['plugin_user_menu_user_list'] = 1;
			$options['global_settings']['plugin_user_menu_add_user'] = 1;
			$options['global_settings']['plugin_user_menu_user_mail'] = 1;
			$options['global_settings']['plugin_user_menu_user_log'] = 1;
			$options['global_settings']['plugin_user_menu_import_user'] = 1;
			$options['global_settings']['plugin_user_menu_options'] = 1;
			$update_flag = 1;
		endif;

		if ( !empty($update_flag) ) :
			$options['current_setting_version'] = $frontend_user_admin_setting_version;
			update_option('frontend_user_admin', $options);
		endif;
	}
	
	function frontend_user_admin_auth_cookie_expiration($seconds, $user_id, $remember) {
		$options = $this->get_frontend_user_admin_data();
		$expire_in = 0;

		if ( $remember ) :
			if ( !empty($options['global_settings']['remember_auth_time']) && $options['global_settings']['remember_auth_time']>0 ) :
				$expire_in = $options['global_settings']['remember_auth_time'];
			endif; 
		else :
			if ( !empty($options['global_settings']['normal_auth_time']) && $options['global_settings']['normal_auth_time']>0 ) :
				$expire_in = $options['global_settings']['normal_auth_time'];
			endif;
		endif;
		
		if ( $expire_in>0 ) :
			if ( (PHP_INT_MAX - time()) < $expire_in ) $seconds =  PHP_INT_MAX - time() - 5;
			else $seconds = $expire_in;
			if ( !empty($options['global_settings']['disable_duplicate_login']) ) $seconds -= rand(0, 60);
		endif;
		
		return $seconds;
	}
	
	function frontend_user_admin_add_shortcode_fua($attr) {
		extract(shortcode_atts(array(
			'before_widget' => '<div class="fua">',
			'after_widget'  => "</div>\n",
			'before_title'  => '<h4>',
			'after_title'   => "</h4>\n",
			'notitle'       => 0,
			'shortcode'     => 1,
			'redirect_to'   => ''
		), $attr));
		
		$args = compact('before_widget', 'after_widget', 'before_title', 'after_title', 'notitle', 'shortcode', 'redirect_to');

		$frontend_user_admin = new WP_Widget_Frontend_User_Admin();
		return $frontend_user_admin->widget($args, $attr);
	}
	
	function frontend_user_admin_add_shortcode_fuamc($attr, $content) {
		$options = $this->get_frontend_user_admin_data();

		extract(shortcode_atts(array(
			'condition' => 0,
			'alt_text' => '',
			'echo_content' => 0,
			'echo_title' => 0
		), $attr));

		if ( $this->frontend_user_admin_is_member($condition) ) :
			return do_shortcode($content);
		elseif ( !empty($alt_text) ) :
			return $alt_text;
		elseif ( !empty($echo_content) && !empty($options['member_condition'][$condition]['the_content']) ) :
			return do_shortcode($this->EvalBuffer($options['member_condition'][$condition]['the_content']));
		elseif ( !empty($echo_title) && !empty($options['member_condition'][$condition]['the_title']) ) :
			return do_shortcode($this->EvalBuffer($options['member_condition'][$condition]['the_title']));
		endif;
	}

	function frontend_user_admin_add_shortcode_fuacu($attr) {
		$options = $this->get_frontend_user_admin_data();

		extract(shortcode_atts(array(
			'key' => '',
			'before' => '',
			'after' => '',
			'alt' => ''
		), $attr));

		if ( empty($key) || !is_user_logged_in() ) return;
		global $current_user;
				
		if ( !empty($current_user->{$key}) ) :
			if ( is_array($current_user->{$key}) ) :
				if( !empty($options['global_settings']['array_delimiter']) ) $delimiter = $options['global_settings']['array_delimiter'];
				else $delimiter = ' ';
				$tmp = '';
				foreach( $current_user->{$key} as $val ) :
					$tmp .= $val.$delimiter;
				endforeach;
				$tmp = trim($tmp, $delimiter);
				$output = $tmp;
			else :
				$output = $current_user->{$key};
			endif;
		elseif ( !empty($alt) ) :
			$output = $alt;
		endif;

		if ( empty($output) ) return;

		return $before.$output.$after;
	}

	function frontend_user_admin_add_shortcode_fualist($attr) {
		$options = $this->get_frontend_user_admin_data();

		$attr = shortcode_atts(array(
			'role' => '',
			'meta_key' => '',
			'meta_value' => '',
			'meta_compare' => '',
			'meta_keys' => '',
			'meta_values' => '',
			'meta_compares' => '',
			'include' => '',
			'exclude' => '',
			'search' => '',
			'orderby' => '',
			'order' => 'ASC',
			'offset' => '',
			'number' => '',
			'format' => 0,
			'relation' => 'OR'
		), $attr);

		$format = $attr['format'];
		
		if ( !empty($attr['meta_keys']) ) :
			$meta_keys = explode(',', $attr['meta_keys']);
			$meta_keys = array_map('trim', $meta_keys);
			$meta_values = explode(',', $attr['meta_values']);
			$meta_values = array_map('trim', $meta_values);
			$meta_compares = explode(',', $attr['meta_compares']);
			$meta_compares = array_map('trim', $meta_compares);
			
			$count = count($meta_keys);
			
			for ( $i=0; $i<$count; $i++ ) :
				$arrays[$i] = array('key' => $meta_keys[$i], 'value' => $meta_values[$i], 'compare' => $meta_compares[$i]);
			endfor;

			$meta_query_args = array_merge(array('relation' => $attr['relation']), $arrays);
			$attr['meta_query'] = $meta_query_args;
		endif;
		$users = get_users($attr);
		$count = !empty($users) ? count($users) : 0;
		
		$content = '';
		
		$content .= isset($options['fualist_format'][$format]['prefix']) ? $options['fualist_format'][$format]['prefix'] : '';
		for ( $i=0; $i<$count; $i++ ) :
			$content .= isset($options['fualist_format'][$format]['main']) ? preg_replace_callback('/%([a-zA-Z0-9_]+)%/', function ($m) use ($i, $users) { return $users[$i]->{$m[1]}; }, $options['fualist_format'][$format]['main']) : '';
		endfor;
		$content .= isset($options['fualist_format'][$format]['suffix']) ? $options['fualist_format'][$format]['suffix'] : '';			

		return $content;		
	}

	function frontend_user_admin_user_log() {
		$options = $this->get_frontend_user_admin_data();

		if ( is_user_logged_in() && !empty($options['global_settings']['start_log']) && !is_admin() && !preg_match('/favicon\.ico$/', $_SERVER["REQUEST_URI"]) ) :
			global $current_user;
			if ( empty($current_user->no_log) ) {
				$this->frontend_user_admin_add_log($current_user->ID, $_SERVER['REMOTE_ADDR'], rawurldecode(home_url().$_SERVER["REQUEST_URI"]));
			}
		endif;
	}
	
	function frontend_user_admin_xmlrpc_methods($methods) {
		$methods['fua.newUser'] = array($this, 'frontend_user_admin_xmlrpc_newUser');
		$methods['fua.editUser'] = array($this, 'frontend_user_admin_xmlrpc_editUser');
		$methods['fua.deleteUser'] = array($this, 'frontend_user_admin_xmlrpc_deleteUser');
		$methods['fua.editUserStatus'] = array($this, 'frontend_user_admin_xmlrpc_editUserStatus');
		$methods['fua.getUserStatus'] = array($this, 'frontend_user_admin_xmlrpc_getUserStatus');
		return $methods;	
	}
	
	function frontend_user_admin_xmlrpc_login($username, $password) {
		$enabled = apply_filters( 'xmlrpc_enabled', true );
		
		if ( !get_option( 'enable_xmlrpc' ) && !$enabled  ) {
			$this->error = new IXR_Error( 405, sprintf( __( 'XML-RPC services are disabled on this site.', 'frontend-user-admin' ) ) );
			return false;
		}

		$user = wp_authenticate($username, $password);

		if (is_wp_error($user)) {
			$this->error = new IXR_Error( 403, __( 'Incorrect username or password.', 'frontend-user-admin' ) );
			$this->error = apply_filters( 'xmlrpc_login_error', $this->error, $user );
			return false;
		}

		wp_set_current_user( $user->ID );
		return $user;
	}
	
	function frontend_user_admin_xmlrpc_escape(&$array) {
		global $wpdb;

		if (!is_array($array)) {
			return(esc_sql($array));
		} else {
			foreach ( (array) $array as $k => $v ) {
				if ( is_array($v) ) {
					esc_sql($array[$k]);
				} else if ( is_object($v) ) {
					//skip
				} else {
					$array[$k] = esc_sql($v);
				}
			}
		}
	}
	
	function frontend_user_admin_xmlrpc_newUser($args) {
		global $wpdb;
		
		$username	= $this->frontend_user_admin_xmlrpc_escape($args[0]);
		$password	= $this->frontend_user_admin_xmlrpc_escape($args[1]);
		$data       = $args[2];

		if ( !$user = $this->frontend_user_admin_xmlrpc_login($username, $password) )
			return $this->error;

		do_action('xmlrpc_call', 'fua.newUser');
		
		require_once( ABSPATH . WPINC . '/pluggable.php');
		require_once( ABSPATH . '/wp-admin/includes/user.php');

		$user_login = trim($data['user_login']);
		$user_pass  = trim($data['user_pass']);
		$user_email = trim($data['user_email']);
		$user_nicename = isset($data['user_nicename']) ? $data['user_nicename'] : $user_login;
		$display_name = isset($data['display_name']) ? $data['display_name'] : $user_login;

		if ( !current_user_can('create_users', $page_id) )
			return new IXR_Error( 401, __('Sorry, you do not have the right to create a new user.', 'frontend-user-admin') );

		define('WP_IMPORTING', true);
		$errors = wp_create_user( $user_login, $user_pass, $user_email );
		if ( is_wp_error($errors) )
			return new IXR_Error( 401, __( 'Sorry, a new user could not be created. Something wrong happened.', 'frontend-user-admin' ) );

		$user_id = $errors;
		$data2 = compact( 'user_url', 'user_nicename', 'display_name', 'user_registered' );
		$data2 = stripslashes_deep( $data2 );
		$ID = $user_id;
		$wpdb->update( $wpdb->users, $data2, array( 'ID' => $ID ) );

		foreach ( $data as $key => $val ) :
			if ( $key != 'user_login' && $key != 'user_pass' && $key != 'user_email' && $key != 'user_url' && $key != 'user_nicename' && $key != 'display_name' && $key != 'user_registered' ) :
				update_user_meta( $user_id, $key, $val );
			endif;
		endforeach;

		return $user_id;
	}
	
	function frontend_user_admin_xmlrpc_editUser($args) {
		global $wpdb;
		
		$username	= $this->frontend_user_admin_xmlrpc_escape($args[0]);
		$password	= $this->frontend_user_admin_xmlrpc_escape($args[1]);
		$data       = $args[2];
		
		if ( !$user = $this->frontend_user_admin_xmlrpc_login($username, $password) )
			return $this->error;

		do_action('xmlrpc_call', 'fua.editUser');

		$ID = $data['ID'];
		$user_login  = $data['user_login'];

		if ( !current_user_can('edit_users', $user->ID) )
			return new IXR_Error( 401, __('Sorry, you do not have the right to edit the user status.', 'frontend-user-admin') );

		if ( empty($ID) && !empty($user_login) ) :
			$user = get_user_by('login', $user_login);
			$ID = $user->ID;
			$data['ID'] = $ID;
		endif;

		if ( empty($ID) ) return 0;
		
		$result = wp_update_user( $data );
		
		foreach ( $data as $key => $value ) :
			if ( $this->check_attribute_data($key) ) :
				update_user_meta( $ID, $key, $value);
			endif;
		endforeach;
				
		if ( is_wp_error( $result ) )
			return new IXR_Error( 500, $result->get_error_message() );

		if ( ! $result )
			return new IXR_Error( 500, __( 'Sorry, the user cannot be updated.', 'frontend-user-admin' ) );

		return $ID;
	}

	function frontend_user_admin_xmlrpc_deleteUser($args) {
		global $wpdb;
		
		$username	= $this->frontend_user_admin_xmlrpc_escape($args[0]);
		$password	= $this->frontend_user_admin_xmlrpc_escape($args[1]);
		$data       = $args[2];

		if ( !$user = $this->frontend_user_admin_xmlrpc_login($username, $password) )
			return $this->error;

		do_action('xmlrpc_call', 'fua.deleteUser');

		require_once( ABSPATH . WPINC . '/pluggable.php');
		require_once( ABSPATH . '/wp-admin/includes/user.php');
		
		$ID = $data['ID'];
		$user_login  = $data['user_login'];

		if ( !current_user_can('delete_users', $user->ID) )
			return new IXR_Error( 401, __('Sorry, you do not have the right to delete users.', 'frontend-user-admin') );
		
		if ( empty($ID) && !empty($user_login) ) :
			$user = get_user_by('login', $user_login);
			$ID = $user->ID;
		endif;
		
		if ( empty($ID) ) return 0;
		
		return wp_delete_user($ID);
	}

	function frontend_user_admin_xmlrpc_editUserStatus($args) {
		global $wpdb;
		
		$username	= $this->frontend_user_admin_xmlrpc_escape($args[0]);
		$password	= $this->frontend_user_admin_xmlrpc_escape($args[1]);
		$data       = $args[2];
		
		if ( !$user = $this->frontend_user_admin_xmlrpc_login($username, $password) )
			return $this->error;

		do_action('xmlrpc_call', 'fua.editUserStatus');

		$ID = $data['ID'];
		$user_login  = $data['user_login'];
		$user_status = $data['user_status'];

		if ( !current_user_can('edit_users', $user->ID) )
			return new IXR_Error( 401, __('Sorry, you do not have the right to edit the user status.', 'frontend-user-admin') );

		if ( empty($ID) && !empty($user_login) ) :
			$user = get_user_by('login', $user_login);
			$ID = $user->ID;
		endif;

		$data2 = compact( 'user_status' );
		$data2 = stripslashes_deep( $data2 );
		return $wpdb->update( $wpdb->users, $data2, array( 'ID' => $ID ) );
	}
	
	function frontend_user_admin_xmlrpc_getUserStatus($args) {
		global $wpdb;
		
		$username	= $this->frontend_user_admin_xmlrpc_escape($args[0]);
		$password	= $this->frontend_user_admin_xmlrpc_escape($args[1]);
		$data       = $args[2];

		if ( !$user = $this->frontend_user_admin_xmlrpc_login($username, $password) )
			return $this->error;

		do_action('xmlrpc_call', 'fua.getUserStatus');

		require_once( ABSPATH . WPINC . '/pluggable.php');
		require_once( ABSPATH . '/wp-admin/includes/user.php');

		$ID = $data['ID'];
		$user_login  = $data['user_login'];

		if ( !current_user_can('list_users', $user->ID) )
			return new IXR_Error( 401, __('Sorry, you do not have the right to get the user status.', 'frontend-user-admin') );

		if ( empty($ID) && !empty($user_login) ) :
			$user = get_user_by('login', $user_login);
			$ID = $user->ID;
		endif;

		$user = get_userdata($ID);
		
		return $user->user_status;
	}

	function install_frontend_user_admin_data() {
		global $frontend_user_admin_setting_version;
		
		delete_option('frontend_user_admin');

		$options['global_settings']['plugin_role'] = 'administrator';
		global $wp_roles;
		$wp_roles->add_cap('administrator', 'edit_frontend_user_admin');

		$options['global_settings']['login_url'] = get_option('home').'/login/';
		$options['global_settings']['after_login_url'] = '';
		$options['global_settings']['terms_of_use_url'] = get_option('home');
		$options['global_settings']['show_the_content_directly'] = 1;
		$options['global_settings']['menu_during_login'] = 1;
		$options['global_settings']['use_style_sheet'] = 1;
		$options['global_settings']['disable_password_change_email'] = 1;
		$options['global_settings']['disable_email_change_email'] = 1;
		$options['global_settings']['register_order'] = array("user_login", "last_name", "first_name", "nickname", "display_name", "user_email", "user_url", "aim", "yim", "jabber", "description", "user_pass", "role", "user_status", "no_log", "duplicate_login");
		$options['global_settings']['profile_order'] = array("user_login", "last_name", "first_name", "nickname", "display_name", "user_email", "user_url", "aim", "yim", "jabber", "description", "user_pass", "role", "user_status", "no_log", "duplicate_login");

		$options['global_settings']['register_user_login'] = 1;
		$options['global_settings']['register_user_email'] = 1;
		$options['global_settings']['register_user_pass'] = 1;
		$options['global_settings']['profile_user_login'] = 1;
		$options['global_settings']['profile_user_email'] = 1;
		$options['global_settings']['profile_user_pass'] = 1;
		
		$options['global_settings']['log_username'] = 'display_name';
		$options['global_settings']['name_of terms'] = __('Terms of use', 'frontend-user-admin');
		$options['global_settings']['required_mark'] = __('Required', 'frontend-user-admin');
		
		$options['global_settings']['plugin_user_menu_user_list'] = 1;
		$options['global_settings']['plugin_user_menu_add_user'] = 1;
		$options['global_settings']['plugin_user_menu_user_mail'] = 1;
		$options['global_settings']['plugin_user_menu_user_log'] = 1;
		$options['global_settings']['plugin_user_menu_import_user'] = 1;
		$options['global_settings']['plugin_user_menu_options'] = 1;

		$options['mail_options']['mail_from'] = 'wordpress <'.get_option('admin_email').'>';

		$options['mail_options']['retrieve_password_subject'] = sprintf(__('[%s] Password Reset', 'frontend-user-admin'), get_option('blogname'));
		$options['mail_options']['retrieve_password_body'] = __('Someone has asked to reset the password for the following site and username.', 'frontend-user-admin') . "\r\n\r\n";
		$options['mail_options']['retrieve_password_body'] .=  get_option('siteurl') . "\r\n\r\n";
		$options['mail_options']['retrieve_password_body'] .= __('Username: %user_login%', 'frontend-user-admin')."\r\n\r\n";
		$options['mail_options']['retrieve_password_body'] .= __('To reset your password visit the following address, otherwise just ignore this email and nothing will happen.', 'frontend-user-admin')."\r\n\r\n";
		$options['mail_options']['retrieve_password_body'] .= '%login_url%'."?key=%key%&action=rp\r\n";

		$options['mail_options']['reset_password_user_subject'] = sprintf(__('[%s] Your new password', 'frontend-user-admin'), get_option('blogname'));
		$options['mail_options']['reset_password_user_body'] = __('Username: %user_login%', 'frontend-user-admin')."\r\n";
		$options['mail_options']['reset_password_user_body'] .= __('Password: %password%', 'frontend-user-admin')."\r\n";
		$options['mail_options']['reset_password_user_body'] .= '%login_url%'."\r\n";

		$options['mail_options']['reset_password_admin_subject'] = sprintf(__('[%s] Password Lost/Changed', 'frontend-user-admin'), get_option('blogname'));
		$options['mail_options']['reset_password_admin_body'] = __('Password Lost and Changed for user: %user_login%', 'frontend-user-admin')."\r\n";

		$options['mail_options']['new_user_notification_user_subject'] = sprintf(__('[%s] Your username and password', 'frontend-user-admin'), get_option('blogname'));
		$options['mail_options']['new_user_notification_user_body'] = __('Username: %user_login%', 'frontend-user-admin')."\r\n";
		$options['mail_options']['new_user_notification_user_body'] .= __('Password: %password%', 'frontend-user-admin')."\r\n";
		$options['mail_options']['new_user_notification_user_body'] .= '%login_url%'."\r\n";

		$options['mail_options']['new_user_notification_admin_subject'] = sprintf(__('[%s] New User Registration', 'frontend-user-admin'), get_option('blogname'));
		$options['mail_options']['new_user_notification_admin_body'] = sprintf(__('New user registration on your blog %s:', 'frontend-user-admin'), get_option('blogname'))."\r\n\r\n";
		$options['mail_options']['new_user_notification_admin_body'] .= __('Username: %user_login%', 'frontend-user-admin')."\r\n";
		$options['mail_options']['new_user_notification_admin_body'] .= __('E-mail: %user_email%', 'frontend-user-admin')."\r\n";

		$options['mail_options']['email_confirmation_first_user_subject'] = sprintf(__('[%s] Email Confirmation', 'frontend-user-admin'), get_option('blogname'));
		$options['mail_options']['email_confirmation_first_user_body'] = __('Please click the following address and register the site.', 'frontend-user-admin')."\r\n\r\n";
		$options['mail_options']['email_confirmation_first_user_body'] .= '%login_url%'."?key=%key%&action=register\r\n";

		$options['mail_options']['email_confirmation_user_subject'] = sprintf(__('[%s] Email Confirmation', 'frontend-user-admin'), get_option('blogname'));
		$options['mail_options']['email_confirmation_user_body'] = __('Registration will be done after you click the following address.', 'frontend-user-admin')."\r\n\r\n";
		$options['mail_options']['email_confirmation_user_body'] .= __('Username: %user_login%', 'frontend-user-admin')."\r\n";
		$options['mail_options']['email_confirmation_user_body'] .= '%login_url%'."?key=%key%&action=ec\r\n";

		$options['mail_options']['approval_process_user_subject'] = sprintf(__('[%s] Under Approval Process', 'frontend-user-admin'), get_option('blogname'));
		$options['mail_options']['approval_process_user_body'] = __('Currently under approval process. Please wait for the email from the site owner.', 'frontend-user-admin')."\r\n\r\n";
		$options['mail_options']['approval_process_user_body'] .= __('Username: %user_login%', 'frontend-user-admin')."\r\n";
		$options['mail_options']['approval_process_user_body'] .= __('E-mail: %user_email%', 'frontend-user-admin')."\r\n";

		$options['mail_options']['approval_process_admin_subject'] = sprintf(__('[%s] Under Approval Process', 'frontend-user-admin'), get_option('blogname'));
		$options['mail_options']['approval_process_admin_body'] = __('Please approve the following user.', 'frontend-user-admin')."\r\n\r\n";
		$options['mail_options']['approval_process_admin_body'] .= __('Username: %user_login%', 'frontend-user-admin')."\r\n";
		$options['mail_options']['approval_process_admin_body'] .= __('E-mail: %user_email%', 'frontend-user-admin')."\r\n";

		$options['mail_options']['withdrawal_user_subject'] = sprintf(__('[%s] User Withdrawal', 'frontend-user-admin'), get_option('blogname'));
		$options['mail_options']['withdrawal_user_body'] = __('You were resigned from the site.', 'frontend-user-admin')."\r\n\r\n";
		$options['mail_options']['withdrawal_user_body'] .= __('Username: %user_login%', 'frontend-user-admin')."\r\n";
		$options['mail_options']['withdrawal_user_body'] .= __('E-mail: %user_email%', 'frontend-user-admin')."\r\n";

		$options['mail_options']['withdrawal_admin_subject'] = sprintf(__('[%s] User Withdrawal', 'frontend-user-admin'), get_option('blogname'));
		$options['mail_options']['withdrawal_admin_body'] = __('The following user resigned from the site.', 'frontend-user-admin')."\r\n\r\n";
		$options['mail_options']['withdrawal_admin_body'] .= __('Username: %user_login%', 'frontend-user-admin')."\r\n";
		$options['mail_options']['withdrawal_admin_body'] .= __('E-mail: %user_email%', 'frontend-user-admin')."\r\n";
		$options['current_setting_version'] = $frontend_user_admin_setting_version;

		global $wpdb, $charset_collate;
		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
		$table = "CREATE TABLE `".$wpdb->prefix."userlog` (
  `ulog_id` bigint(20) NOT NULL auto_increment,
  `user_id` bigint(20) NOT NULL DEFAULT '0', 
  `ip` INT(10) unsigned NOT NULL,
  `log` longtext NULL,
  `ulog_time` TIMESTAMP NOT NULL,
  PRIMARY KEY  (`ulog_id`),
  KEY user_id (user_id),
  KEY ip (ip)
  ) $charset_collate;";
		maybe_create_table($wpdb->prefix."userlog", $table);
		if ( !empty($_POST['truncate_table_userlog']) ) :
			$query = "TRUNCATE TABLE `".$wpdb->prefix."userlog`;";
			$wpdb->query($query);
		endif;
		
		$table = "CREATE TABLE `".$wpdb->prefix."usermail` (
  `umail_id` INT(10) unsigned NOT NULL auto_increment,
  `user_id` INT(10) unsigned DEFAULT '0' NOT NULL,
  `umail_from` TEXT,
  `umail_to` TEXT,
  `umail_cc` TEXT,
  `umail_bcc` TEXT,
  `umail_template` TEXT,
  `umail_subject` TEXT,
  `umail_body` TEXT,
  `umail_regtime` DATETIME NOT NULL,
	`umail_del` TINYINT(1) unsigned DEFAULT '0' NOT NULL,
  PRIMARY KEY  (`umail_id`),
  KEY user_id (user_id)
  ) $charset_collate;";
		maybe_create_table($wpdb->prefix."usermail", $table);
		if ( !empty($_POST['truncate_table_usermail']) ) :
			$query = "TRUNCATE TABLE `".$wpdb->prefix."usermail`;";
			$wpdb->query($query);
		endif;
		
		if ( empty($options['login_post_id']) ) :
			require_once( ABSPATH . WPINC . '/post.php');
			$post_id = wp_insert_post(array(
								'post_title'		=> __('Login', 'frontend-user-admin'),
								'post_status'		=> 'publish',
								'post_name'		    => 'login',
								'post_content'      => '',
								'post_excerpt'      => '',
								'post_type'         => 'page'
								));
			$options['login_post_id'] = $post_id;
		endif;

		update_option('frontend_user_admin', $options);
	}
	
	function frontend_user_admin_generate_rewrite_rules($wp_rewrite) {
		$options = $this->get_frontend_user_admin_data();
		if( !empty($options['global_settings']['after_login_url']) && preg_match('/%user_login%/', $options['global_settings']['after_login_url']) && !empty($options['global_settings']['after_login_template_file']) ) :
			$path = preg_replace('/(\.[^.]*)$/','',$options['global_settings']['after_login_template_file']);
			$newrules[$path.'/([a-zA-Z0-9]+)/?$'] = 'index.php?script='.$options['global_settings']['after_login_template_file'].'&userlogin={$matches[1]}';
			$wp_rewrite->rules = $newrules+$wp_rewrite->rules;
		elseif( !empty($options['global_settings']['after_login_template_file']) ) :
			$path = preg_replace('/(\.[^.]*)$/','',$options['global_settings']['after_login_template_file']);
			$newrules[$path.'/?$'] = 'index.php?script='.$options['global_settings']['after_login_template_file'];		
			$wp_rewrite->rules = $newrules+$wp_rewrite->rules;
		endif;
	}

	function frontend_user_admin_query_vars ( $qvars ){
		$qvars[] = 'script';
		$qvars[] = 'userlogin';
		return $qvars;
	}

	function template_redirect_intercept(){
		global $wp_query;
		
		$options = $this->get_frontend_user_admin_data();

		if ( !empty($options['global_settings']['disable_duplicate_login']) ) :
			$loginusers = get_option('frontend_user_admin_login_users');
			$ID = wp_validate_auth_cookie($_COOKIE[LOGGED_IN_COOKIE], 'logged_in');
			$user =  new WP_User($ID);
			if ( $loginusers[$ID] != $_COOKIE[LOGGED_IN_COOKIE] && !$user->duplicate_login ) :
				wp_logout();
				if(	$options['global_settings']['transfer_all_to_login'] && $this->get_permalink() != $options['global_settings']['login_url'] && !$this->transfer_all_to_login_exception() ) :
					$redirect_to = $this->return_frontend_user_admin_login_url().'duplicate=true';
					wp_redirect($redirect_to);
				endif;
			endif;
		endif;
		
		if ( !empty($options['global_settings']['approval_registration']) ) :
			if ( is_user_logged_in() ) :
				global $current_user;
				$status = $current_user->user_status;
				if ( $current_user->user_status != 0 ) :
					wp_logout();
					if(	$options['global_settings']['transfer_all_to_login'] && $this->get_permalink() != $options['global_settings']['login_url'] && !$this->transfer_all_to_login_exception() ) :
						$redirect_to = $this->return_frontend_user_admin_login_url().'checkemail=approval&status='.$status;
						wp_redirect($redirect_to);
					endif;					
				endif;
			endif;
		endif;

		if( !empty($options['global_settings']['after_login_url']) && !empty($options['global_settings']['after_login_template_file']) ) {

			if ( $wp_query->get('script') == $options['global_settings']['after_login_template_file']) {
				if (file_exists(TEMPLATEPATH . '/' . $options['global_settings']['after_login_template_file'] )) {
					include( TEMPLATEPATH . '/' . $options['global_settings']['after_login_template_file'] );
        		}
			}
		}
	}
	
	function frontend_user_admin_wp_login($user_login) {
		$options = $this->get_frontend_user_admin_data();
		/*if ( !empty($options['global_settings']['disable_duplicate_login']) ) :
			$loginusers = get_option('frontend_user_admin_login_users');
			$user_id = wp_cache_get($user_login, 'userlogins');
			wp_validate_auth_cookie();
			$loginusers[$user_id] = $_COOKIE[LOGGED_IN_COOKIE];
			update_option('frontend_user_admin_login_users', $loginusers);
		endif;*/
		
		if ( !empty($options['global_settings']['approval_registration']) || !empty($options['global_settings']['email_confirmation']) ) :
			$user = get_user_by('login', $user_login);
			if ( $user->user_activation_key && $user->user_status ) :
				wp_logout();
				$redirect_to = $this->return_frontend_user_admin_login_url()."checkemail=confirmation";
				wp_redirect($redirect_to);
				exit();
			elseif ( $user->user_status ) :
				$status = $user->user_status;
				wp_logout();
				$redirect_to = $this->return_frontend_user_admin_login_url()."checkemail=approval&status=".$status;
				wp_redirect($redirect_to);
				exit();
			endif;
		endif;
		
		if ( !empty($options['global_settings']['password_expiration_date']) ) :
			$user = get_user_by('login', $user_login);
			if ( empty($user->password_changed_time) ) :
				update_user_meta( $user->ID, 'password_changed_time', date_i18n('U'));
			endif;
		endif;
	}
	
	function frontend_user_admin_check_passwords($user_login, $pass1, $pass2) {
		$options = $this->get_frontend_user_admin_data();
		if ( !empty($options['global_settings']['password_expiration_date']) ) :
			$user = get_user_by('login', $user_login);
			if ( !empty($pass1) && !empty($pass2) && $pass1==$pass2 ) :
				update_user_meta( $user->ID, 'password_changed_time', date_i18n('U'));
			endif;
		endif;
	}
	
	function frontend_user_admin_sanitize_user($username, $raw_username, $strict) {
		$options = $this->get_frontend_user_admin_data();
		if ( !empty($options['global_settings']['email_as_userlogin']) || defined( 'WP_IMPORTING' ) ) :
			return $raw_username;
		else :
			return $username;
		endif;
	}
	
	function frontend_user_admin_set_logged_in_cookie($logged_in_cookie, $expire, $expiration, $user_id, $logged_in) {
		$options = $this->get_frontend_user_admin_data();
		if ( !empty($options['global_settings']['disable_duplicate_login']) ) :
			$loginusers = get_option('frontend_user_admin_login_users');
			$loginusers[$user_id] = $logged_in_cookie;
			update_option('frontend_user_admin_login_users', $loginusers);
		endif;
	}

	function frontend_user_admin_wp_head() {
		global $net_shop_admin;
		$options = $this->get_frontend_user_admin_data();
		if( !is_admin() && !empty($options['global_settings']['use_style_sheet']) ) {
			if( file_exists(TEMPLATEPATH.'/frontend-user-admin.css') ) {
				echo '<link rel="stylesheet" href="'.get_stylesheet_directory_uri().'/frontend-user-admin.css" type="text/css" media="screen" />'."\n";	
			} else {
				echo '<link rel="stylesheet" href="'.site_url().'/'.PLUGINDIR.'/frontend-user-admin/frontend-user-admin.css" type="text/css" media="screen" />'."\n";
			}
		}
		if( !empty($options['global_settings']['login_url']) && strstr($this->get_permalink(), $options['global_settings']['login_url']) && $net_shop_admin ) :
			$nsa_options = get_option('net_shop_admin');
			if( empty($nsa_options['global_settings']['disable_auto_zipcde_loading']) ) :
				$url = site_url();
				if ( preg_match('/^https/', $options['global_settings']['login_url']) ) $url = preg_replace('/http:/', 'https:', $url);
				echo '<script>AjaxZip2.JSONDATA = "'.$url.'/'.PLUGINDIR.'/net-shop-admin/ajaxzip2/data";</script>'."\n";
			endif;
		endif;
	}
	
	function frontend_user_admin_wp_head_clawler() {
		echo '<meta name="robots" content="noarchive">'."\n";
	}
	
	function frontend_user_admin_admin_head() {
		$options = $this->get_frontend_user_admin_data();
		
		if ( strstr($_SERVER['REQUEST_URI'], 'frontend-user-admin') && !empty($options['global_settings']['admin_javascript']) ) :
?>
<script type="text/javascript">
// <![CDATA[
<?php echo $options['global_settings']['admin_javascript']; ?>
//-->
</script>
<?php
		endif;
		
		if ( strstr($_SERVER['REQUEST_URI'], 'frontend-user-admin') && !empty($options['global_settings']['admin_css']) ) :
?>
<style type="text/css">
<!--
<?php echo $options['global_settings']['admin_css']; ?>
-->
</style>
<?php
		endif;
?>
<style type="text/css">
<!--
#wp-admin-bar-user-info .avatar { width:64px; }
<?php
		if ( strstr($_SERVER['REQUEST_URI'], 'frontend-user-admin') ) :
?>
.widefat td, .widefat th { border-bottom:1px solid #EEE; }
<?php
		endif;
?>
-->
</style>
<?php
	}
	
	function frontend_user_admin_admin_print_scripts() {
		global $wp_version;
		if (strpos($_SERVER['REQUEST_URI'], 'frontend-user-admin') !== false ) {
			$options = $this->get_frontend_user_admin_data();
			wp_deregister_script( 'jquery-form' );
			wp_enqueue_script( 'jquery' );
			wp_enqueue_script( 'jquery-ui-core' );
			wp_enqueue_script( 'jquery-ui-sortable' );
			wp_enqueue_script( 'jquery-form', '/'.PLUGINDIR.'/frontend-user-admin/js/jquery.form.js', array('jquery'));
			wp_enqueue_script( 'jquery-textarearesizer', '/'.PLUGINDIR.'/frontend-user-admin/js/jquery.textarearesizer.js', array('jquery'));

			if( !empty($options['global_settings']["use_password_strength"]) ) :
				if ( substr($wp_version, 0, 3) >= '4.3' ) :
					wp_enqueue_script( 'fua-user-profile', '/'.PLUGINDIR.'/frontend-user-admin/js/user-profile.min.js', array('jquery', 'password-strength-meter', 'wp-util') );
				elseif ( substr($wp_version, 0, 3) >= '3.0' ) :
					wp_enqueue_script( 'zxcvbn-async', "/wp-includes/js/zxcvbn-async.min.js", array(), '1.0' );
					wp_enqueue_script( 'user-profile' );
				else :
					wp_enqueue_script( 'password-strength-meter' );
					wp_enqueue_script( 'check_pass_strength', '/'.PLUGINDIR.'/frontend-user-admin/js/check_pass_strength.js', array('jquery' ) );
				endif;
			endif;
		}
	}
	
	function frontend_user_admin_wp_print_scripts() {
		global $wp_version, $net_shop_admin;
		$options = $this->get_frontend_user_admin_data();

		if( !empty($options['global_settings']['login_url']) && strstr($this->get_permalink(), $options['global_settings']['login_url']) ) {
			wp_enqueue_script( 'jquery' );
			if ( substr($wp_version, 0, 3) >= '4.3' ) :
				wp_enqueue_script( 'fua-user-profile', '/'.PLUGINDIR.'/frontend-user-admin/js/user-profile.min.js', array('jquery', 'password-strength-meter', 'wp-util') );
			elseif ( substr($wp_version, 0, 3) >= '3.0' ) :
				wp_enqueue_script( 'user-profile' );
			else :
				wp_enqueue_script( 'password-strength-meter' );
				wp_enqueue_script( 'check_pass_strength', '/'.PLUGINDIR.'/frontend-user-admin/js/check_pass_strength.js', array('jquery' ) );
			endif;
			if ( $net_shop_admin ) :
				wp_enqueue_script( 'ajaxzip2', '/'.PLUGINDIR.'/net-shop-admin/ajaxzip2/ajaxzip2.js', array('jquery' ) );
			endif;
		}
		if ( !empty($options['recaptcha_options']['site_key']) ) :
			wp_enqueue_script( 'recaptcha', 'https://www.google.com/recaptcha/api.js', array() );
		endif;
	}
	
	function frontend_user_admin_add_meta_boxes() {
		$options = $this->get_frontend_user_admin_data();

		if ( !empty($options['member_condition']) ) :
			if ( !empty($options['member_condition_types']) && is_array($options['member_condition_types']) ) :
				foreach ( $options['member_condition_types'] as $val ) :
					add_meta_box('fuamcdiv', __('Member Page Condition', 'frontend-user-admin'), array(&$this, 'frontend_user_admin_add_meta_box'), $val, 'side', 'core');
				endforeach;
			endif;			
		endif;
	}
	
	function frontend_user_admin_add_meta_box() {
		global $post;
		
		$options = $this->get_frontend_user_admin_data();
		$fuamc = get_post_meta($post->ID, 'fuamc', true);
		$fuamc = explode(',', $fuamc);
		$fuamc = array_unique(array_map('trim', $fuamc));

		if ( strstr($_SERVER['REQUEST_URI'], 'wp-admin/post-new.php') && isset($options['default_member_condition']) ) :
			$fuamc = explode(',', $options['default_member_condition']);
			$fuamc = array_unique(array_map('trim', $fuamc));
		endif;
?>
<p><?php
			for($i=0; $i<count($options['member_condition']); $i++ ) :
?>
<label><input name="fuamc[]" type="checkbox" value="<?php echo $i; ?>"<?php foreach ( $fuamc as $val ) checked($val, $i); ?> /> #<?php echo $i; ?> <?php echo esc_attr($options['member_condition'][$i]['name']); ?></label><br />
<?php
			endfor;
?>
</p>
<?php
	}
	
	function frontend_user_admin_save_post() {
		global $post_id;
		
		$fuamc = (isset($_POST['fuamc']) && is_array($_POST['fuamc'])) ? implode(',', $_POST['fuamc']) : null;

		if ( is_null($fuamc) ) :
			delete_post_meta( $post_id, 'fuamc' );
		elseif ( !add_post_meta( $post_id, 'fuamc', $fuamc, true ) ) :
			update_post_meta( $post_id, 'fuamc', $fuamc );
		endif;
	}
	
	function frontend_user_admin_the_posts($posts) {
		$options = $this->get_frontend_user_admin_data();

		$num_posts = count($posts);
		for ( $i = 0; $i < $num_posts; $i++ ) :
			$fuamc = get_post_meta($posts[$i]->ID, 'fuamc', true);
			$fuamc = explode(',', $fuamc);
			$fuamc = array_unique(array_map('trim', $fuamc));
		
			foreach ( $fuamc as $val ) :
				if ( !empty($options['member_condition'][$val]['no_output']) ) :
					if ( !is_user_logged_in() ) :
						$condition_flag = 1;
						break;
					else :
						if ( !empty($options['member_condition'][$val]['attribute']) && is_array($options['member_condition'][$val]['attribute']) ) :
							for($j=0;$j<count($options['member_condition'][$val]['attribute']);$j++) :
								if ( !$this->frontend_user_admin_is_member_condition($options['member_condition'][$val]['attribute'][$j]) ) :
									$condition_flag = 1;
								else :
									if ( !empty($options['member_condition'][$val]['conjunction']) && $options['member_condition'][$val]['conjunction']=='OR' ) :
										$condition_flag = 0;
										break 2;
									endif;
								endif;
							endfor;
						endif;
					endif;
				endif;
			endforeach;
			if ( !empty($condition_flag) ) :
				unset($posts[$i]);
			endif;
		endfor;
		$posts = array_values($posts);

		return $posts;
	}
	
	function frontend_user_admin_admin_menu() {
		$options = $this->get_frontend_user_admin_data();
		if ( function_exists('add_menu_page') ) :
	
			if ( isset($options['global_settings']['plugin_role']) ) : $plugin_role = 'edit_frontend_user_admin';
			else :
				if ( isset($options['global_settings']['plugin_user_level']) ) $plugin_role = $options['global_settings']['plugin_user_level'];
				else $plugin_role = 'manage_options';
			endif;
			if ( current_user_can('administrator') ) $plugin_role = 'manage_options';
			if ( !empty($options['global_settings']['admin_demo']) ) $plugin_role = 'read';

			if ( !current_user_can('administrator') && current_user_can('edit_frontend_user_admin') ) :
				if ( !empty($options['global_settings']['plugin_user_menu_user_list']) ) : $file = __FILE__; endif;
				if ( !empty($options['global_settings']['plugin_user_menu_add_user']) && empty($file) ) : $file = 'frontend-user-admin/frontend-user-admin-adduser.php'; endif;
				if ( !empty($options['global_settings']['plugin_user_menu_user_mail']) && empty($file) ) : $file = 'frontend-user-admin/frontend-user-admin-mail.php'; endif;
				if ( !empty($options['global_settings']['plugin_user_menu_user_log']) && empty($file) ) : $file = 'frontend-user-admin/frontend-user-admin-log.php'; endif;
				if ( !empty($options['global_settings']['plugin_user_menu_import_user']) && empty($file) ) : $file = 'frontend-user-admin/frontend-user-admin-importuser.php'; endif;
				if ( !empty($options['global_settings']['plugin_user_menu_options']) && empty($file) ) : $file = 'frontend-user-admin/frontend-user-admin-settings.php'; endif;
			else :
				$file = __FILE__;
			endif;

			if ( current_user_can('administrator') || (current_user_can('edit_frontend_user_admin') && (!empty($options['global_settings']['plugin_user_menu_user_list']) || !empty($options['global_settings']['plugin_user_menu_add_user']) || !empty($options['global_settings']['plugin_user_menu_user_mail']) || !empty($options['global_settings']['plugin_user_menu_user_log']) || !empty($options['global_settings']['plugin_user_menu_import_user']) || !empty($options['global_settings']['plugin_user_menu_options']))) || !empty($options['global_settings']['admin_demo']) )
				add_menu_page( __('Frontend User Admin', 'frontend-user-admin'), __('User Management', 'frontend-user-admin'), $plugin_role, $file, array(&$this, 'add_frontend_user_admin_admin'), 'dashicons-admin-users', 31 );

			if ( current_user_can('administrator') || (current_user_can('edit_frontend_user_admin') && !empty($options['global_settings']['plugin_user_menu_user_list'])) || !empty($options['global_settings']['admin_demo']) )
				add_submenu_page( $file, __('User Management', 'frontend-user-admin'), __('User List', 'frontend-user-admin'), $plugin_role, $file, array(&$this, 'add_frontend_user_admin_admin') );

			$frontend_user_admin_adduser = new frontend_user_admin_adduser();
			if ( current_user_can('administrator') || (current_user_can('edit_frontend_user_admin') && !empty($options['global_settings']['plugin_user_menu_add_user'])) || !empty($options['global_settings']['admin_demo']) )
				add_submenu_page( $file, __('Add User', 'frontend-user-admin'), __('Add User', 'frontend-user-admin'), $plugin_role, 'frontend-user-admin/frontend-user-admin-adduser.php', array(&$frontend_user_admin_adduser, 'frontend_user_admin_adduser_do') );

			$frontend_user_admin_mail = new frontend_user_admin_mail();
			if ( current_user_can('administrator') || (current_user_can('edit_frontend_user_admin') && !empty($options['global_settings']['plugin_user_menu_user_mail'])) || !empty($options['global_settings']['admin_demo']) )
				add_submenu_page( $file, __('User Mail', 'frontend-user-admin'), __('User Mail', 'frontend-user-admin'), $plugin_role, 'frontend-user-admin/frontend-user-admin-mail.php', array(&$frontend_user_admin_mail, 'frontend_user_admin_mail_do') );

			$frontend_user_admin_log = new frontend_user_admin_log();
			if ( current_user_can('administrator') || (current_user_can('edit_frontend_user_admin') && !empty($options['global_settings']['plugin_user_menu_user_log'])) || !empty($options['global_settings']['admin_demo']) )
				add_submenu_page( $file, __('User Log', 'frontend-user-admin'), __('User Log', 'frontend-user-admin'), $plugin_role, 'frontend-user-admin/frontend-user-admin-log.php', array(&$frontend_user_admin_log, 'frontend_user_admin_log_do') );

			$frontend_user_admin_importuser = new frontend_user_admin_importuser();
			if ( current_user_can('administrator') || (current_user_can('edit_frontend_user_admin') && !empty($options['global_settings']['plugin_user_menu_import_user'])) || !empty($options['global_settings']['admin_demo']) )
				add_submenu_page( $file, __('Import User', 'frontend-user-admin'), __('Import User', 'frontend-user-admin'), $plugin_role,'frontend-user-admin/frontend-user-admin-importuser.php', array(&$frontend_user_admin_importuser, 'frontend_user_admin_importuser_do') );
			
			$frontend_user_admin_settings = new frontend_user_admin_settings();
			if ( current_user_can('administrator') || (current_user_can('edit_frontend_user_admin') && !empty($options['global_settings']['plugin_user_menu_options'])) || !empty($options['global_settings']['admin_demo']) )
				add_submenu_page( $file, __('Options', 'frontend-user-admin'), __('Options', 'frontend-user-admin'), $plugin_role, 'frontend-user-admin/frontend-user-admin-settings.php', array(&$frontend_user_admin_settings, 'frontend_user_admin_settings_do') );
		endif;
	}
	
	function frontend_user_admin_get_avatar($avatar, $id_or_email, $size, $default, $alt) {
		if ( strstr($_SERVER['REQUEST_URI'], 'options-discussion.php') ) return $avatar;
		
		if ( is_numeric($id_or_email) ) :
			$id = (int) $id_or_email;
			$user = get_userdata($id);
		elseif ( is_object($id_or_email) ) :
			if ( !empty($id_or_email->user_id) ) :
				$id = (int) $id_or_email->user_id;
				$user = get_userdata($id);
			endif;
		else :
			$user = get_user_by('email', $id_or_email);
		endif;

		if ( !empty($user->avatar) && is_numeric($user->avatar) ) :
			if ( is_numeric ($size) ) $resize = array($size, $size);
			else $resize = $size;
			$image_data = wp_get_attachment_image_src((int)$user->avatar, $resize, false);
			if ( !empty($image_data[0]) ) :
				return '<img src="'.esc_attr($image_data[0]).'" width="'.$image_data[1].'" height="'.$image_data[2].'" alt="'.esc_attr($alt).'" class="avatar" />';
			else :	
				return $avatar;
			endif;
		else :	
			return $avatar;	
		endif;
	}
	
	function frontend_user_admin_get_user_metadata ($value, $object_id, $meta_key, $single) {
		if ( is_admin() || preg_match('/^_publicity_/', $meta_key) ) return $value;
		global $current_user;
		$options = $this->get_frontend_user_admin_data();

		if ( !empty($options['user_attribute']['user_attribute']) ) $count_user_attribute = count($options['user_attribute']['user_attribute']);
		else $count_user_attribute = 0;
		
		$publicity = get_user_meta($object_id, '_publicity_'.$meta_key, true);
		
		for ( $i = 0; $i < $count_user_attribute; $i++ ) :
			if ( !empty($options['user_attribute']['user_attribute'][$i]['publicity']) && !empty($publicity) && (!is_user_logged_in() || (is_user_logged_in() && $current_user->ID!=$object_id)) ) :
				$value = '';
			endif;	
		endfor;

		return $value;
	}
	
	function frontend_user_admin_user_management_action() {
		global $current_user, $wp_roles, $wp_version, $wp_rewrite;
		$options = $this->get_frontend_user_admin_data();

		if ( !empty($options['global_settings']['admin_demo']) && ((!empty($options['global_settings']['plugin_role']) && !current_user_can('edit_frontend_user_admin')) || (empty($options['global_settings']['plugin_role']) && $current_user->user_level<10)) )
			return;

		$_REQUEST = stripslashes_deep($_REQUEST);
		$_GET = stripslashes_deep($_GET);
		$_POST = stripslashes_deep($_POST);
		
		do_action( 'fua_action' );

		if ( !empty($_POST["global_settings_submit"]) ) :
			$profile_checkbox = $options['global_settings']['profile_checkbox'];
			unset($options['global_settings']);
			foreach($_POST as $key => $val) :
				if($key != "global_settings_submit") :
					if ( $key == 'login_url' ) :
						$val = user_trailingslashit($val);
					elseif ( $key == 'normal_auth_time' || $key == 'remember_auth_time' ) :
						if ( !empty($val) ) :
							$val = intval($val);
							if ( $val <= 0 ) $val = '';
						endif;
					endif;
					$options['global_settings'][$key] = $val;
				endif;
			endforeach;
			if ( !empty($options['global_settings']['plugin_role']) && current_user_can('administrator') ) :
				$all_roles = $wp_roles->roles;
				foreach ( $all_roles as $role => $details ) :
					$wp_roles->remove_cap($role, 'edit_frontend_user_admin');
				endforeach;

				$wp_roles->add_cap('administrator', 'edit_frontend_user_admin');
				switch($options['global_settings']['plugin_role']) :
					case 'editor' :
						$wp_roles->add_cap('editor', 'edit_frontend_user_admin');
						break;
					case 'author' :
						$wp_roles->add_cap('editor', 'edit_frontend_user_admin');
						$wp_roles->add_cap('author', 'edit_frontend_user_admin');
						break;
					case 'contributor' :
						$wp_roles->add_cap('editor', 'edit_frontend_user_admin');
						$wp_roles->add_cap('author', 'edit_frontend_user_admin');
						$wp_roles->add_cap('contributor', 'edit_frontend_user_admin');
						break;
					case 'subscriber' :
						$wp_roles->add_cap('editor', 'edit_frontend_user_admin');
						$wp_roles->add_cap('author', 'edit_frontend_user_admin');
						$wp_roles->add_cap('contributor', 'edit_frontend_user_admin');
						$wp_roles->add_cap('subscriber', 'edit_frontend_user_admin');
						break;
					default :
						$wp_roles->add_cap($options['global_settings']['plugin_role'], 'edit_frontend_user_admin');
						break;
				endswitch;
			endif;
			$options['global_settings']['profile_checkbox'] = $profile_checkbox;
			update_option('frontend_user_admin', $options);
			$wp_rewrite->flush_rules();
			wp_redirect(get_option('siteurl').'/wp-admin/admin.php?page='.$_REQUEST['page'].'&open=global_settings&message=updated');
		elseif ( !empty($_POST["add_user_attribute_submit"]) ) :
			unset($options['user_attribute']['user_attribute']);
			$j = 0;
			for ( $i=0; $i<count($_POST["name"]); $i++ ) :
				if( empty($_POST["delete"][$i]) && !empty($_POST["name"][$i]) ) :
					if ( is_array($options['global_settings']['profile_order']) && !in_array($_POST["name"][$i],$options['global_settings']['profile_order']) )
						array_push($options['global_settings']['profile_order'], $_POST["name"][$i]);
					if ( is_array($options['global_settings']['register_order']) && !in_array($_POST["name"][$i],$options['global_settings']['register_order']) )
						array_push($options['global_settings']['register_order'], $_POST["name"][$i]);
					$options['user_attribute']['user_attribute'][$j]['label']       = $_POST["label"][$i];
					$options['user_attribute']['user_attribute'][$j]['name']        = $_POST["name"][$i];
					$options['user_attribute']['user_attribute'][$j]['type']        = $_POST["type"][$i];
					$options['user_attribute']['user_attribute'][$j]['type2']       = $_POST["type2"][$i];
					$options['user_attribute']['user_attribute'][$j]['default']     = $_POST["default"][$i];
					$options['user_attribute']['user_attribute'][$j]['overwrite_php'] = $_POST["overwrite_php"][$i];
					$options['user_attribute']['user_attribute'][$j]['placeholder'] = $_POST["placeholder"][$i];
					$options['user_attribute']['user_attribute'][$j]['comment']     = $_POST["comment"][$i];
					$options['user_attribute']['user_attribute'][$j]['admin']       = $_POST["admin"][$i];
					$options['user_attribute']['user_attribute'][$j]['publicity']   = $_POST["publicity"][$i];
					$options['user_attribute']['user_attribute'][$j]['retrieve_password']   = $_POST["retrieve_password"][$i];
					$options['user_attribute']['user_attribute'][$j]['unique']      = $_POST["unique"][$i];
					$options['user_attribute']['user_attribute'][$j]['log']         = $_POST["log"][$i];
					$options['user_attribute']['user_attribute'][$j]['required']    = $_POST["required"][$i];
					$options['user_attribute']['user_attribute'][$j]['condition']   = $_POST["condition"][$i];
					$options['user_attribute']['user_attribute'][$j]['min_letters'] = (int)$_POST["min_letters"][$i];
					$options['user_attribute']['user_attribute'][$j]['max_letters'] = (int)$_POST["max_letters"][$i];
					$options['user_attribute']['user_attribute'][$j]['cast']        = $_POST["cast"][$i];
					$options['user_attribute']['user_attribute'][$j]['readonly']    = $_POST["readonly"][$i];
					$options['user_attribute']['user_attribute'][$j]['disabled']    = $_POST["disabled"][$i];
					$options['user_attribute']['user_attribute'][$j]['composite_unique'] = $_POST["composite_unique"][$i];
					$j++;
				endif;
			endfor;
			update_option('frontend_user_admin', $options);
			wp_redirect(get_option('siteurl').'/wp-admin/admin.php?page='.$_REQUEST['page'].'&open=user_attribute&message=updated');
		elseif ( !empty($_POST["add_widget_menu_submit"]) ) :
			unset($options['widget_menu']);
			$j = 0;
			for($i=0;$i<count($_POST["widget_menu_url"]);$i++) :
				if ( !empty($_POST["widget_menu_url"][$i]) ) :
					$options['widget_menu'][$j]['widget_menu_label']       = $_POST["widget_menu_label"][$i];
					$options['widget_menu'][$j]['widget_menu_url']         = $_POST["widget_menu_url"][$i];
					$options['widget_menu'][$j]['widget_menu_blank']       = $_POST["widget_menu_blank"][$i];
					$options['widget_menu'][$j]['widget_menu_user_level']  = $_POST["widget_menu_user_level"][$i];
					$options['widget_menu'][$j]['widget_menu_open']        = $_POST["widget_menu_open"][$i];
					$j++;
				endif;
			endfor;		
			update_option('frontend_user_admin', $options);
			wp_redirect(get_option('siteurl').'/wp-admin/admin.php?page='.$_REQUEST['page'].'&open=widget_menu&message=updated');
		elseif ( !empty($_POST["mail_options_submit"]) ) :
			foreach($_POST as $key => $val)
				if($key != "mail_options_submit") $options['mail_options'][$key] = $val;
			update_option('frontend_user_admin', $options);
			wp_redirect(get_option('siteurl').'/wp-admin/admin.php?page='.$_REQUEST['page'].'&open=mail_options&message=updated');
		elseif( !empty($_POST["mail_template_submit"]) ) :
			unset($options['mail_template']);
			$j = 0;
			for($i=0;$i<count($_POST['name']);$i++) {
				if($_POST['name'][$i]) {
					$options['mail_template'][$j]['name']     = $_POST['name'][$i];
					$options['mail_template'][$j]['subject']  = $_POST['subject'][$i];
					$options['mail_template'][$j]['body']     = $_POST['body'][$i];
					$j++;
				}
			}
			update_option('frontend_user_admin', $options);
			wp_redirect(get_option('siteurl').'/wp-admin/admin.php?page='.$_REQUEST['page'].'&open=mail_template&message=updated');
			exit();
		elseif ( !empty($_POST["title_options_submit"]) ) :
			foreach($_POST as $key => $val ) :
				if($key != "title_options_submit") :
					$options['title_options'][$key] = $val;
				endif;
			endforeach;
			update_option('frontend_user_admin', $options);
			wp_redirect(get_option('siteurl').'/wp-admin/admin.php?page='.$_REQUEST['page'].'&open=title_options&message=updated');
		elseif ( !empty($_POST["excerpt_options_submit"]) ) :
			foreach($_POST as $key => $val ) :
				if($key != "excerpt_options_submit") :
					$options['excerpt_options'][$key] = $val;
				endif;
			endforeach;
			update_option('frontend_user_admin', $options);
			wp_redirect(get_option('siteurl').'/wp-admin/admin.php?page='.$_REQUEST['page'].'&open=excerpt_options&message=updated');
		elseif ( !empty($_POST["message_options_submit"]) ) :
			foreach($_POST as $key => $val ) :
				if($key != "message_options_submit") :
					$options['message_options'][$key] = $val;
				endif;
			endforeach;
			update_option('frontend_user_admin', $options);
			wp_redirect(get_option('siteurl').'/wp-admin/admin.php?page='.$_REQUEST['page'].'&open=message_options&message=updated');
		elseif ( !empty($_POST["notice_options_submit"]) ) :
			foreach($_POST as $key => $val ) :
				if($key != "notice_options_submit") :
					$options['notice_options'][$key] = $val;
				endif;
			endforeach;
			update_option('frontend_user_admin', $options);
			wp_redirect(get_option('siteurl').'/wp-admin/admin.php?page='.$_REQUEST['page'].'&open=notice_options&message=updated');
		elseif ( !empty($_POST["output_options_submit"]) ) :
			foreach($_POST as $key => $val ) :
				if($key != "output_options_submit") :
					$options['output_options'][$key] = $val;
				endif;
			endforeach;
			update_option('frontend_user_admin', $options);
			wp_redirect(get_option('siteurl').'/wp-admin/admin.php?page='.$_REQUEST['page'].'&open=output_options&message=updated');
		elseif( !empty($_POST["member_condition_submit"]) ) :
			unset($options['member_condition']);
			$i = 0;
			$options['member_condition_types'] = isset($_POST['member_condition_types']) ? $_POST['member_condition_types'] : '';
			$options['default_member_condition'] = (isset($_POST['default_member_condition']) && is_array($_POST['default_member_condition'])) ? implode(',', $_POST['default_member_condition']) : null;
			foreach ( $_POST['name'] as $key => $val ) :
				if ( !empty($val) ) :
					$options['member_condition'][$i]['name']				= $_POST['name'][$key];
					$options['member_condition'][$i]['redirect_url']		= $_POST['redirect_url'][$key];
					$options['member_condition'][$i]['the_title']			= $_POST['the_title'][$key];
					$options['member_condition'][$i]['the_content']			= $_POST['the_content'][$key];
					$options['member_condition'][$i]['no_output']			= isset($_POST['no_output'][$key]) ? $_POST['no_output'][$key] : '';
					$options['member_condition'][$i]['except_clawlers'] 	= isset($_POST['except_clawlers'][$key]) ? $_POST['except_clawlers'][$key] : '';
					$options['member_condition'][$i]['until_more']			= isset($_POST['until_more'][$key]) ? $_POST['until_more'][$key] : '';
					$options['member_condition'][$i]['auto_excerpt']		= $_POST['auto_excerpt'][$key];
					$options['member_condition'][$i]['conjunction']			= $_POST['conjunction'][$key];
					$k = 0;
					for($j=0;$j<count($_POST['attribute_key'][$key]);$j++) :
						if( !empty($_POST['attribute_key'][$key][$j]) ) :
							$options['member_condition'][$i]['attribute'][$k]['attribute_key']   = $_POST['attribute_key'][$key][$j];
							$options['member_condition'][$i]['attribute'][$k]['attribute_value'] = $_POST['attribute_value'][$key][$j];
							$options['member_condition'][$i]['attribute'][$k]['code'] = $_POST['code'][$key][$j];
							$options['member_condition'][$i]['attribute'][$k]['nm'] = $_POST['nm'][$key][$j];
							$k++;
						endif;
					endfor;
					global $net_shop_admin;
					if ( !empty($net_shop_admin) ) :
						$options['member_condition'][$i]['uo_conjunction']		= $_POST['uo_conjunction'][$key];
						$options['member_condition'][$i]['o_conjunction']		= $_POST['o_conjunction'][$key];
						$k = 0;
						for($j=0;$j<count($_POST['order_key'][$key]);$j++) :
							if( !empty($_POST['order_key'][$key][$j]) ) :
								$options['member_condition'][$i]['attribute2'][$k]['order_key']   = $_POST['order_key'][$key][$j];
								$options['member_condition'][$i]['attribute2'][$k]['order_value'] = $_POST['order_value'][$key][$j];
								$options['member_condition'][$i]['attribute2'][$k]['nm'] = $_POST['nm2'][$key][$j];
								$k++;
							endif;
						endfor;
					endif;
					$options['member_condition'][$i]['term_id'] = array_unique(array_filter(array_map('trim', explode(',', $_POST['term_id'][$key]))));
					$options['member_condition'][$i]['apply_to_subpages']	= isset($_POST['apply_to_subpages'][$key]) ? $_POST['apply_to_subpages'][$key] : '';
					$i++;
				endif;
			endforeach;
			update_option('frontend_user_admin', $options);
			wp_redirect(get_option('siteurl').'/wp-admin/admin.php?page='.$_REQUEST['page'].'&open=member_condition&message=updated');
			exit();
		elseif( !empty($_POST["fualist_format_submit"]) ) :
			unset($options['fualist_format']);
			$i = 0;
			foreach ( $_POST['main'] as $key => $val ) :
				if ( !empty($val) ) :
					$options['fualist_format'][$i]['prefix']	= $_POST['prefix'][$key];
					$options['fualist_format'][$i]['main']		= $_POST['main'][$key];
					$options['fualist_format'][$i]['suffix']	= $_POST['suffix'][$key];
					$i++;
				endif;
			endforeach;
			update_option('frontend_user_admin', $options);
			wp_redirect(get_option('siteurl').'/wp-admin/admin.php?page='.$_REQUEST['page'].'&open=fualist_format&message=updated');
			exit();
		elseif( !empty($_POST["recaptcha_options_submit"]) ) :
			unset($options['recaptcha_options']);
			foreach($_POST as $key => $val ) :
				if( $key != "recaptcha_options_submit" ) :
					$options['recaptcha_options'][$key] = $val;
				endif;
			endforeach;
			update_option('frontend_user_admin', $options);
			wp_redirect(get_option('siteurl').'/wp-admin/admin.php?page='.$_REQUEST['page'].'&open=recaptcha_options&message=updated');
			exit();
		elseif ( !empty($_POST["phpcode_options_submit"]) ) :
			unset($options['phpcode_options']);
			$options['phpcode_options'] = $_POST['phpcode'];
			update_option('frontend_user_admin', $options);
			wp_redirect(get_option('siteurl').'/wp-admin/admin.php?page='.$_REQUEST['page'].'&open=phpcode_options&message=updated');
		elseif ( !empty($_POST["phpcode2_options_submit"]) ) :
			unset($options['phpcode2_options']);
			$options['phpcode2_options'] = $_POST['phpcode2'];
			update_option('frontend_user_admin', $options);
			wp_redirect(get_option('siteurl').'/wp-admin/admin.php?page='.$_REQUEST['page'].'&open=phpcode2_options&message=updated');
		elseif ( !empty($_POST["phpcode3_options_submit"]) ) :
			unset($options['phpcode3_options']);
			$options['phpcode3_options'] = $_POST['phpcode3'];
			update_option('frontend_user_admin', $options);
			wp_redirect(get_option('siteurl').'/wp-admin/admin.php?page='.$_REQUEST['page'].'&open=phpcode3_options&message=updated');
		elseif ( !empty($_POST['option_converter_submit']) ) :
			array_walk_recursive($options, function (&$val, $key) {
				$val = str_replace($_POST['from_text'], $_POST['to_text'], $val);
			});
			update_option('frontend_user_admin', $options);
			wp_redirect(get_option('siteurl').'/wp-admin/admin.php?page='.$_REQUEST['page'].'&message=updated');
		elseif ( !empty($_POST['export_options_submit']) ) :
			$filename = "fua".date_i18n('Ymd');
			header("Accept-Ranges: none");
			header("Content-Disposition: attachment; filename=$filename");
			header('Content-Type: application/octet-stream');
			echo maybe_serialize($options);
			exit();
		elseif ( !empty($_POST['import_options_submit']) ) :
			if ( is_uploaded_file($_FILES['fuafile']['tmp_name']) ) :
				ob_start();
				readfile ($_FILES['fuafile']['tmp_name']);
				$import = ob_get_contents();
				ob_end_clean();
				$import = maybe_unserialize($import);
				if ( is_array($import) ) :
					update_option('frontend_user_admin', $import);
					wp_redirect(get_option('siteurl').'/wp-admin/admin.php?page='.$_REQUEST['page'].'&message=imported');
				else :
					wp_redirect(get_option('siteurl').'/wp-admin/admin.php?page='.$_REQUEST['page'].'&message=failed');
				endif;
			endif;
		elseif ( !empty($_POST['reset_options_submit']) ) :
			if ( !empty($_POST['truncate_table_userlog']) ) :
				global $wpdb;
				$query = "TRUNCATE TABLE `".$wpdb->prefix."userlog`;";
				$wpdb->query($query);
			endif;
			if ( !empty($_POST['truncate_table_usermail']) ) :
				global $wpdb;
				$query = "TRUNCATE TABLE `".$wpdb->prefix."usermail`;";
				$wpdb->query($query);
			endif;
			if ( !empty($_POST['reset_options']) ) :
				$this->install_frontend_user_admin_data();
			endif;
			wp_redirect(get_option('siteurl').'/wp-admin/admin.php?page='.$_REQUEST['page'].'&message=reset');
		elseif ( !empty($_POST['delete_options_submit']) ) :
			if ( !empty($_POST['drop_table_userlog']) ) :
				global $wpdb;
				$query = "DROP TABLE IF EXISTS `".$wpdb->prefix."userlog`;";
				$wpdb->query($query);
			endif;
			if ( !empty($_POST['drop_table_usermail']) ) :
				global $wpdb;
				$query = "DROP TABLE IF EXISTS `".$wpdb->prefix."usermail`;";
				$wpdb->query($query);
			endif;
			if ( !empty($_POST['delete_options']) ) :
				delete_option('frontend_user_admin');
			endif;
			$all_roles = $wp_roles->roles;
			foreach ( $all_roles as $role => $details ) :
				$wp_roles->add_cap($role, 'edit_fronend_user_admin');
			endforeach;
			wp_redirect(get_option('siteurl').'/wp-admin/admin.php?page='.$_REQUEST['page'].'&message=deleted');
		elseif ( !empty($_REQUEST['action']) && $_REQUEST['action'] == 'register' ) :
			if ( version_compare( substr($wp_version, 0, 3), '3.1', '<' ) )
				require_once( ABSPATH . WPINC . '/registration.php');
			require_once( ABSPATH . WPINC . '/pluggable.php');
			require_once( ABSPATH . '/wp-admin/includes/user.php');
			$this->errors = new WP_Error();
			$user_login = empty($_POST['user_login']) ? '' : $_POST['user_login'];
			$user_email = empty($_POST['user_email']) ? '' : $_POST['user_email'];
			$_POST['email'] = empty($_POST['user_email']) ? '' : $_POST['user_email'];
			$_POST['url'] = empty($_POST['user_url']) ? '' : $_POST['user_url'];
					
			$this->errors = $this->register_new_user($user_login, $user_email);
		
			do_action( 'fua_before_register', $this->errors );
		
			if ( !is_wp_error($this->errors) ) :
				$user_id = $this->errors;
				$this->errors = $this->edit_user($this->errors);

				if ( isset($_POST['no_log']) ) update_user_meta( $user_id, 'no_log', $_POST['no_log']);
				if ( isset($_POST['duplicate_login']) ) update_user_meta( $user_id, 'duplicate_login', $_POST['duplicate_login']);
				if ( is_numeric($options['global_settings']['password_lock_miss_times']) ) update_user_meta( $user_id, 'password_lock_miss_times', $_POST['password_lock_miss_times']);
			
				if ( function_exists('is_multisite') && is_multisite() && !empty($options['global_settings']['all_site_registration']) ) :
					global $wpdb;
					$query = "SELECT blog_id FROM `" . $wpdb->blogs . "` ORDER BY `" . $wpdb->blogs . "`.blog_id ASC";
					$result = $wpdb->get_results($query, ARRAY_A);
					if ( !empty($result) && is_array($result) ) :
						for($i=0;$i<count($result);$i++) :
							$blog_prefix = $wpdb->get_blog_prefix( $result[$i]['blog_id'] );
							$user = $wpdb->get_var( "SELECT user_id FROM `" . $wpdb->usermeta . "` WHERE user_id='$user_id' AND meta_key='{$blog_prefix}capabilities'" );
							if ( $user == false ) :
								add_user_to_blog( $result[$i]['blog_id'], $user_id, get_option('default_role') );
							endif;
						endfor;
					endif;
				endif;
				
				do_action( 'fua_register', $user_id );
							
				wp_redirect(get_option('siteurl').'/wp-admin/admin.php?page='.$_REQUEST['page'].'&message=registered');
				exit();
			endif;
		elseif ( !empty($_REQUEST['action']) && $_REQUEST['action'] == 'update' ) :
			global $wpdb;
			$options = $this->get_frontend_user_admin_data();
			
			if ( version_compare( substr($wp_version, 0, 3), '3.1', '<' ) )
				require_once( ABSPATH . WPINC . '/registration.php');
			require_once( ABSPATH . WPINC . '/pluggable.php');
			require_once( ABSPATH . '/wp-admin/includes/user.php');
			$this->errors = new WP_Error();
			
			$profileuser = $this->get_user_to_edit( (int)$_REQUEST['user_id'] );
			
			if ( !current_user_can('edit_user', (int)$_REQUEST['user_id']) ) :
				$this->errors->add('no_role', __('<strong>ERROR</strong>: you do not have right to edit this user.', 'frontend-user-admin'));
			else :
				$_POST['email'] = empty($_POST['user_email']) ? $profileuser->user_email : $_POST['user_email'];
				$_POST['url'] = empty($_POST['user_url']) ? '' : $_POST['user_url'];

				$user_id = $_REQUEST['user_id'];
				do_action('personal_options_update');
			
				if ( !empty($options['global_settings']['email_as_userlogin']) && !empty($_POST['email']) ) :
					$_POST['user_login'] = $_POST['email'];
		
					if( !empty($options['global_settings']['unique_registration']) && isset($_POST['user_login']) ) :
						$blog_id = get_current_blog_id();
						$_POST['user_login'] .= ':'.$blog_id;
					endif;
	
					if ( $profileuser->user_login != $_POST['email'] && email_exists( $_POST['email'] ) ) :
						$this->errors->add('email_exists', __('<strong>ERROR</strong>: This email is already registered, please choose another one.', 'frontend-user-admin'));
					endif;
				endif;

				$this->errors = $this->edit_user($user_id, 'profile');

				if ( !is_wp_error($this->errors) && !empty($_POST['send_email']) && !empty($_POST['email']) ) :
					if ( !empty($_POST['pass1']) ) :
						$user_pass = $_POST['pass1'];
					else :
						if ( empty($options['global_settings']['password_registration']) && !empty($options['global_settings']['password_auto_regeneration']) ) :
							$user_pass = wp_generate_password();
							wp_set_password($user_pass, $user_id);
						else :
							$user_pass = '********';
						endif;
					endif;
					$this->wp_new_user_notification($user_id, $user_pass);
				endif;
			endif;

			if ( !is_wp_error($this->errors) ) :			

				if ( !empty($options['global_settings']['email_as_userlogin']) && preg_match('/@/', $profileuser->user_login) && empty($profileuser->fua_social_login) && !empty($_POST['user_login']) ) :
					$ID = $user_id;
					$wpdb->update( $wpdb->users, array('user_login' => $_POST['user_login']), compact( 'ID' ) );
				endif;
				
				if ( isset($_POST['user_status']) ) $wpdb->update($wpdb->users, array('user_status' => $_POST['user_status']), array('ID' => $user_id));
				if ( isset($_POST['no_log']) ) update_user_meta( $user_id, 'no_log', $_POST['no_log']);
				if ( isset($_POST['duplicate_login']) ) update_user_meta( $user_id, 'duplicate_login', $_POST['duplicate_login']);
				if ( is_numeric($options['global_settings']['password_lock_miss_times']) ) update_user_meta( $user_id, 'password_lock_miss_times', $_POST['password_lock_miss_times']);

				$redirect = get_option('siteurl').'/wp-admin/admin.php?page='.$_REQUEST['page'].'&option=edituser&user_id='.$user_id.'&message=edited';
				wp_redirect($redirect);
				exit();
			endif;
		elseif ( !empty($_REQUEST['action']) && $_REQUEST['action'] == 'instant_editor_update' ) :
			global $wpdb;
			$options = $this->get_frontend_user_admin_data();
			
			$user_id = $_REQUEST['user_id'];

			if ( !empty($_REQUEST['user_key']) && is_array($_REQUEST['user_key']) ) :
				foreach ( $_REQUEST['user_key'] as $key => $val ) :
					if ( !empty($val) ) :
						if ( !empty($_REQUEST['delete'][$key]) ) :
							delete_user_meta( $user_id, $val);
						else :
							if ( !empty($_REQUEST['user_array'][$key]) ) :
								update_user_meta( $user_id, $val, json_decode($_REQUEST['user_value'][$key], true) );
							else :
								update_user_meta( $user_id, $val, $_REQUEST['user_value'][$key] );
							endif;
						endif;
					endif;
				endforeach;
			endif;

			$redirect = get_option('siteurl').'/wp-admin/admin.php?page='.$_REQUEST['page'].'&option=edituser&user_id='.$user_id.'&message=edited';
			wp_redirect($redirect);
			exit();
		elseif ( !empty($_REQUEST['action']) && $_REQUEST['action'] == 'soft_user_deletion' ) :
			global $wpdb;
			$ID = $_REQUEST['user_id'];
			$user = $this->get_user_to_edit( (int)$ID );
			$user_login = $user->user_login;
			$user_email = $user->user_email;
			$wpdb->update( $wpdb->users, array('user_login' => 'deleted:'.$user_login, 'user_email' => 'deleted:'.$user_email), compact( 'ID' ) );
			$redirect = get_option('siteurl').'/wp-admin/admin.php?page='.$_REQUEST['page'].'&option=edituser&user_id='.$ID.'&message=edited';
			wp_redirect($redirect);
			exit();
		elseif ( !empty($_REQUEST['action']) && $_REQUEST['action'] == 'export_user_data' ) :
			@set_time_limit(0);
			global $wp_roles;
			$filename = "fuauser".date('Ymd').'.csv';
			header("Accept-Ranges: none");
			header("Content-Disposition: attachment; filename=$filename");
			header('Content-Type: application/octet-stream');

			$_REQUEST['user_id'] = !empty($_REQUEST['user_id']) ? $_REQUEST['user_id'] : '';
			$_REQUEST['order'] = !empty($_REQUEST['order']) ? $_REQUEST['order'] : '';
			$_REQUEST['q'] = !empty($_REQUEST['q']) ? $_REQUEST['q'] : '';
			$_REQUEST['t'] = !empty($_REQUEST['t']) ? $_REQUEST['t'] : '';
			$_REQUEST['m'] = !empty($_REQUEST['m']) ? $_REQUEST['m'] : '';
			if ( !isset($_REQUEST['user_status']) ) $_REQUEST['user_status'] = '';

			if ( !empty($_REQUEST['include_name']) ) :
				$attribute_name2label = $this->attribute_name2label();
				$additional_name2label = array('ID' => __('ID', 'frontend-user-admin'), 'login_datetime' => __('Login Datetime', 'frontend-user-admin'), 'update_datetime' => __('Update Datetime', 'frontend-user-admin'), 'user_registered' => __('Registered Datetime', 'frontend-user-admin'));
				$output = '';
				foreach( $_REQUEST['content'] as $val ) :
					if ( !empty($_REQUEST['with_field_name']) ) :
						$output .= $val.',';
						continue;
					endif;
					if ( $val == 'ID' || $val == 'login_datetime' || $val == 'update_datetime' || $val == 'user_registered' ) :
						switch( $_REQUEST['encode'] ) :
							case 'SJIS': $additional_name2label[$val] = mb_convert_encoding($additional_name2label[$val], 'SJIS', 'UTF-8'); break;
							case 'EUC-JP': $additional_name2label[$val] = mb_convert_encoding($additional_name2label[$val], 'EUC-JP', 'UTF-8'); break;
						endswitch; 
						$output .= $additional_name2label[$val].',';
						continue;
					endif;

					foreach ( $options['global_settings']['profile_order'] as $profile_val ) :
						if ( $val == $profile_val ) :
							switch( $_REQUEST['encode'] ) :
								case 'SJIS': $attribute_name2label[$val] = mb_convert_encoding($attribute_name2label[$val], 'SJIS', 'UTF-8'); break;
								case 'EUC-JP': $attribute_name2label[$val] = mb_convert_encoding($attribute_name2label[$val], 'EUC-JP', 'UTF-8'); break;
							endswitch; 
							$output .= $attribute_name2label[$val].',';
							break 1;
						endif;
					endforeach;
				endforeach;
				echo trim($output, ',')."\n";
			endif;
			
			$paged = 1;
			$posts_per_page = 100;
			while ( list($result, $supplement) = $this->select_user_management_data(array('user_id' => $_REQUEST['user_id'], 'order' => $_REQUEST['order'], 'q' => $_REQUEST['q'], 't' => $_REQUEST['t'], 'm' => $_REQUEST['m'], 'user_status' => $_REQUEST['user_status'], 'posts_per_page' => $posts_per_page, 'paged' => $paged)) ) :
			$count_result = count($result);
			if ( !empty($count_result) ) :
					for ( $i=0; $i<$count_result; $i++) :
						$output = '';
						foreach( $_REQUEST['content'] as $val ) :
							$result[$i][$val] = isset($result[$i][$val]) ? maybe_unserialize(maybe_unserialize($result[$i][$val])) : '';
							$item = '';
							if ( is_array($result[$i][$val]) ) :
								foreach ( $result[$i][$val] as $val2 ) :
									switch( $_REQUEST['encode'] ) :
										case 'SJIS': $val2 = mb_convert_encoding($val2, 'SJIS', 'UTF-8'); break;
										case 'EUC-JP': $val2 = mb_convert_encoding($val2, 'EUC-JP', 'UTF-8'); break;
									endswitch; 
									$item .= $val2.',';
								endforeach;
								$item = trim($item,',');
							else :
								if ( $val == 'role' ) :
									$user_object = new WP_User($result[$i]['ID']);
									$roles = $user_object->roles;
									$role = array_shift($roles);
									$result[$i][$val] = isset($wp_roles->role_names[$role]) ? translate_user_role($wp_roles->role_names[$role] ) : '';						
								endif;
								if ( !empty($result[$i][$val]) && ($val == 'login_datetime' || $val == 'update_datetime') ) $result[$i][$val] = date_i18n('Y-m-d H:i:s', $result[$i][$val]);
								switch( $_REQUEST['encode'] ) :
									case 'SJIS': $result[$i][$val] = mb_convert_encoding($result[$i][$val], 'SJIS', 'UTF-8'); break;
									case 'EUC-JP': $result[$i][$val] = mb_convert_encoding($result[$i][$val], 'EUC-JP', 'UTF-8'); break;
								endswitch; 
								$item .= $result[$i][$val];
							endif;
							$output .= '"'.str_replace('"','""',$item).'",';
						endforeach;
						$output = trim($output, ',')."\n";
						echo $output;
						@ob_flush();
						flush();
					endfor;
				else :
					break;
				endif;
				$paged++;
				wp_cache_flush();
			endwhile;			
			exit();
		elseif ( !empty($_REQUEST['action']) && $_REQUEST['action'] == 'bulk_user_update_code' ) :
			@set_time_limit(0);
			$qs = '';
			if ( !empty($_REQUEST['q']) ) $qs .= '&q='.rawurlencode($_REQUEST['q']);			
			if ( !empty($_REQUEST['t']) ) $qs .= '&t='.rawurlencode($_REQUEST['t']);			
			if ( !empty($_REQUEST['m']) ) $qs .= '&m='.rawurlencode($_REQUEST['m']);			
			if ( is_numeric($_REQUEST['posts_per_page']) ) $qs .= '&posts_per_page='.rawurlencode($_REQUEST['posts_per_page']);

			$_REQUEST['user_id'] = !empty($_REQUEST['user_id']) ? $_REQUEST['user_id'] : '';
			$_REQUEST['order'] = !empty($_REQUEST['order']) ? $_REQUEST['order'] : '';
			$_REQUEST['q'] = !empty($_REQUEST['q']) ? $_REQUEST['q'] : '';
			$_REQUEST['t'] = !empty($_REQUEST['t']) ? $_REQUEST['t'] : '';
			$_REQUEST['m'] = !empty($_REQUEST['m']) ? $_REQUEST['m'] : '';
			if ( !isset($_REQUEST['user_status']) ) $_REQUEST['user_status'] = '';
			
			if ( !empty($_REQUEST['code']) ) :
				$paged = 1;
				$posts_per_page = 100;
				while ( list($result, $supplement) = $this->select_user_management_data(array('user_id' => $_REQUEST['user_id'], 'order' => $_REQUEST['order'], 'q' => $_REQUEST['q'], 't' => $_REQUEST['t'], 'm' => $_REQUEST['m'], 'user_status' => $_REQUEST['user_status'], 'posts_per_page' => $posts_per_page, 'paged' => $paged)) ) :
					$count_result = count($result);
					if ( !empty($count_result) ) :
						for ( $i=0; $i<$count_result; $i++) :
							$user_id = $result[$i]['ID'];
							eval($_REQUEST['code']);
						endfor;
					else :
						break;
					endif;
					$paged++;
					wp_cache_flush();
				endwhile;
			endif;
			$redirect_to = "?page=frontend-user-admin/frontend-user-admin.php&execute_bulk=true" . $qs;
			wp_redirect($redirect_to);
			exit();
		elseif ( !empty($_REQUEST['action']) && $_REQUEST['action'] == 'export_user_log_data' ) :
			@set_time_limit(0);
			$filename = "fuauserlog".date('Ymd').'.csv';
			header("Accept-Ranges: none");
			header("Content-Disposition: attachment; filename=$filename");
			header('Content-Type: application/octet-stream');
			
			if ( !in_array($_REQUEST['encode'], array('SJIS','EUC-JP','UTF-8')) ) $_REQUEST['encode'] = 'UTF-8';

			$from_date = '';
			if ( !empty($_REQUEST['from_date_year']) && !empty($_REQUEST['from_date_month']) && $_REQUEST['from_date_day'] ) :
				$from_date_year  = (int)$_REQUEST['from_date_year'];
				$from_date_month = (int)$_REQUEST['from_date_month'];
				$from_date_day   = (int)$_REQUEST['from_date_day'];
				$from_date = sprintf('%04d',$from_date_year).'-'.sprintf('%02d',$from_date_month).'-'.sprintf('%02d',$from_date_day).' 00:00:00';
			endif;

			$to_date = '';
			if ( !empty($_REQUEST['to_date_year']) && !empty($_REQUEST['to_date_month']) && $_REQUEST['to_date_day'] ) :
				$to_date_year  = (int)$_REQUEST['to_date_year'];
				$to_date_month = (int)$_REQUEST['to_date_month'];
				$to_date_day   = (int)$_REQUEST['to_date_day'];
				$to_date = sprintf('%04d',$to_date_year).'-'.sprintf('%02d',$to_date_month).'-'.sprintf('%02d',$to_date_day).' 23:59:59';
			endif;

			$_REQUEST['order'] = !empty($_REQUEST['order']) ? $_REQUEST['order'] : '';
			$_REQUEST['q'] = !empty($_REQUEST['q']) ? $_REQUEST['q'] : '';
			$_REQUEST['t'] = !empty($_REQUEST['t']) ? $_REQUEST['t'] : '';
			$_REQUEST['m'] = !empty($_REQUEST['m']) ? $_REQUEST['m'] : '';
			if ( !empty($_REQUEST['user_id']) ) $user_id = (int)$_REQUEST['user_id'];

			if ( !empty($_REQUEST['include_name']) ) :
				$output = 'ID,'.mb_convert_encoding(__('User', 'frontend-user-admin'), $_REQUEST['encode'], 'UTF-8').','.mb_convert_encoding(__('IP','frontend-user-admin'), $_REQUEST['encode'], 'UTF-8').','.mb_convert_encoding(__('Log', 'frontend-user-admin'), $_REQUEST['encode'], 'UTF-8').','.mb_convert_encoding(__('Datetime', 'frontend-user-admin'), $_REQUEST['encode'], 'UTF-8')."\n";
				echo $output;
			endif;

			$paged = 1;
			$posts_per_page = 100;
			while ( list($result, $supplement) = $this->frontend_user_admin_select_log(array('order' => $_REQUEST['order'], 'user_id' => $user_id, 'q' => $_REQUEST['q'], 't' => $_REQUEST['t'], 'm' => $_REQUEST['m'], 'from_date' => $from_date, 'to_date' => $to_date, 'posts_per_page' => $posts_per_page, 'paged' => $paged)) ) :
				$count_result = count($result);
				if ( !empty($count_result) ) :				
					for ( $i=0; $i<$count_result; $i++) :
						unset($output);
						$output = $result[$i]['ulog_id'].','.$result[$i]['user_id'].','.$result[$i]['ip'].','.mb_convert_encoding(rawurldecode($result[$i]['log']), $_REQUEST['encode'], 'UTF-8').','.$result[$i]['ulog_time']."\n";
						echo $output;
						@ob_flush();
						flush();
					endfor;
				else :
					break;				
				endif;
				$paged++;
				wp_cache_flush();
			endwhile;
			exit();
		elseif ( !empty($_REQUEST['action']) && $_REQUEST['action'] == 'export_user_logsummary_data' ) :
			$filename = "fuauserlogsummary".date('Ymd').'.csv';
			header("Accept-Ranges: none");
			header("Content-Disposition: attachment; filename=$filename");
			header('Content-Type: application/octet-stream');

			if ( !in_array($_REQUEST['encode'], array('SJIS','EUC-JP','UTF-8')) ) $_REQUEST['encode'] = 'UTF-8';

			if ( !empty($_REQUEST['from_date_year']) && !empty($_REQUEST['from_date_month']) && $_REQUEST['from_date_day'] ) :
				$from_date_year  = (int)$_REQUEST['from_date_year'];
				$from_date_month = (int)$_REQUEST['from_date_month'];
				$from_date_day   = (int)$_REQUEST['from_date_day'];
				$from_date = sprintf('%04d',$from_date_year).'-'.sprintf('%02d',$from_date_month).'-'.sprintf('%02d',$from_date_day).' 00:00:00';
			else :
				$from_date_year  = date_i18n('Y');
				$from_date_month = date_i18n('m');
				$from_date_day   = 1;
				$from_date = sprintf('%04d',$from_date_year).'-'.sprintf('%02d',$from_date_month).'-'.sprintf('%02d',$from_date_day).' 00:00:00';
			endif;
		
			if ( !empty($_REQUEST['to_date_year']) && !empty($_REQUEST['to_date_month']) && $_REQUEST['to_date_day'] ) :
				$to_date_year  = (int)$_REQUEST['to_date_year'];
				$to_date_month = (int)$_REQUEST['to_date_month'];
				$to_date_day   = (int)$_REQUEST['to_date_day'];
				$to_date = sprintf('%04d',$to_date_year).'-'.sprintf('%02d',$to_date_month).'-'.sprintf('%02d',$to_date_day).' 23:59:59';
			else :
				$to_date = '';
			endif;
		
			if ( empty($_REQUEST['order']) ) $_REQUEST['order'] = '';
			if ( !empty($_REQUEST['condition']) ) $condition = $_REQUEST['condition'];
				
			$result = $this->frontend_user_admin_select_logsummary( array(
											'condition' => $condition,
											'order' => $_REQUEST['order'],
											'from_date' => $from_date,
											'to_date' => $to_date));
			$count_result = count($result);

			$options = get_option('frontend_user_admin');

			if ( !empty($options['user_attribute']['user_attribute']) ) $count_user_attribute = count($options['user_attribute']['user_attribute']);
			else $count_user_attribute = 0;

			if( !empty($count_result) ) :
				if ( !empty($_REQUEST['include_name']) ) :
					if ( empty($condition) || $condition == 'user_id' ) :
						$output = mb_convert_encoding(__('User', 'frontend-user-admin'), $_REQUEST['encode'], 'UTF-8').',';
						if ( !empty($options['user_attribute']['user_attribute']) && is_array($options['user_attribute']['user_attribute']) ) :
							for($i=0;$i<$count_user_attribute;$i++) :
								if ( !empty($options['user_attribute']['user_attribute'][$i]['log']) ) :
									$output .= mb_convert_encoding($options['user_attribute']['user_attribute'][$i]['label'], $_REQUEST['encode'], 'UTF-8').',';
								endif;
							endfor;
						endif;
					else :
						if ( !empty($options['user_attribute']['user_attribute']) && is_array($options['user_attribute']['user_attribute']) ) :
							for($i=0;$i<$count_user_attribute;$i++) :
								if ( $options['user_attribute']['user_attribute'][$i]['name'] == $_REQUEST['condition'] ) :
									$output .= mb_convert_encoding($options['user_attribute']['user_attribute'][$i]['label'], $_REQUEST['encode'], 'UTF-8').',';
								endif;
							endfor;
						endif;
						$output .= mb_convert_encoding(__('User Count', 'frontend-user-admin'), $_REQUEST['encode'], 'UTF-8').',';
					endif;
					$output .= mb_convert_encoding(__('Page Count', 'frontend-user-admin'), $_REQUEST['encode'], 'UTF-8').','.mb_convert_encoding(__('Unique Count', 'frontend-user-admin'), $_REQUEST['encode'], 'UTF-8')."\n";
					echo $output;
				endif;
					
				for ( $i=0; $i<$count_result; $i++) :
					unset($output);
					if ( empty($condition) || $condition == 'user_id' ) :
						$output = $result[$i]['user_id'].',';
						if ( !empty($options['user_attribute']['user_attribute']) && is_array($options['user_attribute']['user_attribute']) ) :
							for($j=0;$j<$count_user_attribute;$j++) :
								if ( !empty($options['user_attribute']['user_attribute'][$j]['log']) ) :
									$result[$i][$options['user_attribute']['user_attribute'][$j]['name']] = maybe_unserialize(maybe_unserialize($result[$i][$options['user_attribute']['user_attribute'][$j]['name']]));
									if ( is_array($result[$i][$options['user_attribute']['user_attribute'][$j]['name']]) ) :
										foreach ( $result[$i][$options['user_attribute']['user_attribute'][$j]['name']] as $val2 ) :
											$output .= mb_convert_encoding($val2, $_REQUEST['encode'], 'UTF-8').' ';
										endforeach;
										$output .= ',';
									else :
										$output .= mb_convert_encoding($result[$i][$options['user_attribute']['user_attribute'][$j]['name']], $_REQUEST['encode'], 'UTF-8').',';
									endif;
								endif;
							endfor;
						endif;
						$output .= $result[$i]['ct_id'].','.$result[$i]['ct_uq_id']."\n";
					else :
						$output = mb_convert_encoding($result[$i]['condition'], $_REQUEST['encode'], 'UTF-8').','.$result[$i]['ct_user'].','.$result[$i]['ct_id'].','.$result[$i]['ct_uq_id']."\n";
					endif;
					echo $output;
				endfor;
			endif;
			
			exit();
		elseif ( !empty($_REQUEST['action']) && $_REQUEST['action'] == 'login_hack' ) :
			wp_logout();
			$user =  new WP_User($_REQUEST['user_id']);
			wp_set_current_user( $user->ID );
			wp_set_auth_cookie($user->ID, false, false);
			if ( !is_wp_error($user) ) :
				wp_redirect(get_bloginfo('url'));
				exit();
			endif;
		elseif ( !empty($_POST['send_mail_submit']) ) :
			if ( !empty($_POST['from']) && !empty($_POST['to']) && !empty($_POST['subject']) && !empty($_POST['body']) ) :
				$args['user_id']  = $_POST['user_id'];
				$args['from']     = $_POST['from'];
				$args['to']       = $_POST['to'];
				$args['cc']       = $_POST['cc'];
				$args['bcc']      = $_POST['bcc'];
				$args['template'] = $_POST['template'];
				$args['subject']  = $_POST['subject'];
				$args['body']     = $_POST['body'];
								
				$this->frontend_user_admin_send_mail($args);
					
				if ( is_numeric($_REQUEST['user_id']) ) $str = '&user_id='.$_REQUEST['user_id'];

				$redirect_to = "?page=frontend-user-admin/frontend-user-admin-mail.php&option=sendmail" . $str;
				wp_redirect($redirect_to);
				exit();
			else :
				$_GET['message'] = 'mailerror';
			endif;
		elseif ( !empty($_REQUEST['doaction']) && !empty($_REQUEST['status']) && !empty($_REQUEST['user_ids']) ) :
			global $wpdb;
			$qs = '';
			if ( !empty($_REQUEST['q']) ) $qs .= '&q='.rawurlencode($_REQUEST['q']);			
			if ( !empty($_REQUEST['t']) ) $qs .= '&t='.rawurlencode($_REQUEST['t']);			
			if ( !empty($_REQUEST['m']) ) $qs .= '&m='.rawurlencode($_REQUEST['m']);			
			if ( is_numeric($_REQUEST['posts_per_page']) ) $qs .= '&posts_per_page='.rawurlencode($_REQUEST['posts_per_page']);
			switch ( $_REQUEST['status'] ) :
				case 'approval' :
					foreach ( $_REQUEST['user_ids'] as $user_id ) :
						$wpdb->update($wpdb->users, array('user_status' => 0), array('ID' => $user_id));
					endforeach;
					break;
				case 'pending' :
					foreach ( $_REQUEST['user_ids'] as $user_id ) :
						$wpdb->update($wpdb->users, array('user_status' => 1), array('ID' => $user_id));
					endforeach;
					break;
				case 'delete' :
					$wp_nonce = wp_nonce_field( 'bulk-users', '_wpnonce', false, false );
					$wp_http_referer = get_option('siteurl').'/wp-admin/admin.php?page=frontend-user-admin/frontend-user-admin.php';
					if ( function_exists('is_multisite') && is_multisite() ) :
						if ( !empty($options['global_settings']['user_complete_deletion']) ) :
							$action = 'delete';
						else :
							$action = 'remove';
						endif;
					else :
						$action = 'delete';
					endif;
					$users = '';
					foreach ( $_REQUEST['user_ids'] as $user_id ) $users .= '&users%5B%5D='.$user_id;
					$redirect_to = 'users.php?_wpnonce='.$_REQUEST['_wpnonce'].'&wp_http_referer='.urlencode($wp_http_referer).'&action='.$action.$users;
					wp_redirect($redirect_to);
					exit();
					break;
			endswitch;
			$redirect_to = "?page=frontend-user-admin/frontend-user-admin.php&change_status=true" . $qs;
			wp_redirect($redirect_to);
			exit();
		endif;
	}
	
	function return_wp_title($title) {
		global $wp_query, $wpdb, $wp_version;		
		$options = $this->get_frontend_user_admin_data();

		$action = isset($_REQUEST['action']) ? $_REQUEST['action'] : '';
		
		switch ($action) {
			case 'lostpassword' :
				$the_title = !empty($options['title_options']['lostpassword']) ? $options['title_options']['lostpassword'] : __('Password Lost and Found', 'frontend-user-admin');
				break;
			case 'register' :
				$the_title = !empty($options['title_options']['register']) ? $options['title_options']['register'] : __('Register', 'frontend-user-admin');
				break;					
			case 'confirmation' :
				$the_title = !empty($options['title_options']['confirmation']) ? $options['title_options']['confirmation'] : __('Confirmation', 'frontend-user-admin');
				break;					
			case 'profile' :
				$the_title = !empty($options['title_options']['profile']) ? $options['title_options']['profile'] : __('Profile', 'frontend-user-admin');
				break;					
			case 'history' :
				$the_title = !empty($options['title_options']['history']) ? $options['title_options']['history'] : __('Buying History', 'frontend-user-admin');
				break;					
			case 'affiliate' :
				$the_title = !empty($options['title_options']['affiliate']) ? $options['title_options']['affiliate'] : __('Affiliate', 'frontend-user-admin');
				break;					
			case 'wishlist' :
				$the_title = !empty($options['title_options']['wishlist']) ? $options['title_options']['wishlist'] : __('Wish List', 'frontend-user-admin');
				break;					
			case 'withdrawal' :
				if ( !empty($options['global_settings']['use_withdrawal']) ) :
					$the_title = !empty($options['title_options']['withdrawal']) ? $options['title_options']['withdrawal'] : __('Withdrawal', 'frontend-user-admin');
					break;					
				endif;
			case 'login' :
			default:
				if ( is_user_logged_in() ) :
					$the_title = !empty($options['title_options']['mypage']) ? $options['title_options']['mypage'] : __('My Page', 'frontend-user-admin');
				else :
					$the_title = !empty($options['title_options']['login']) ? $options['title_options']['login'] : __('Log In', 'frontend-user-admin');
				endif;
				break;			
		}

		return strip_tags($the_title);
	}
	
	function return_the_title($title) {
		global $wp_query, $wpdb, $wp_version;		
		$options = $this->get_frontend_user_admin_data();

		$action = isset($_REQUEST['action']) ? $_REQUEST['action'] : '';
		
		switch ($action) {
			case 'lostpassword' :
				$the_title = !empty($options['title_options']['lostpassword']) ? $options['title_options']['lostpassword'] : __('Password Lost and Found', 'frontend-user-admin');
				break;
			case 'register' :
				$the_title = !empty($options['title_options']['register']) ? $options['title_options']['register'] : __('Register', 'frontend-user-admin');
				break;					
			case 'confirmation' :
				$the_title = !empty($options['title_options']['confirmation']) ? $options['title_options']['confirmation'] : __('Confirmation', 'frontend-user-admin');
				break;					
			case 'profile' :
				$the_title = !empty($options['title_options']['profile']) ? $options['title_options']['profile'] : __('Profile', 'frontend-user-admin');
				break;					
			case 'history' :
				$the_title = !empty($options['title_options']['history']) ? $options['title_options']['history'] : __('Buying History', 'frontend-user-admin');
				break;					
			case 'affiliate' :
				$the_title = !empty($options['title_options']['affiliate']) ? $options['title_options']['affiliate'] : __('Affiliate', 'frontend-user-admin');
				break;					
			case 'wishlist' :
				$the_title = !empty($options['title_options']['wishlist']) ? $options['title_options']['wishlist'] : __('Wish List', 'frontend-user-admin');
				break;					
			case 'withdrawal' :
				if ( !empty($options['global_settings']['use_withdrawal']) ) :
					$the_title = !empty($options['title_options']['withdrawal']) ? $options['title_options']['withdrawal'] : __('Withdrawal', 'frontend-user-admin');
					break;					
				endif;
			case 'login' :
			default:
				if ( is_user_logged_in() ) :
					$the_title = !empty($options['title_options']['mypage']) ? $options['title_options']['mypage'] : __('My Page', 'frontend-user-admin');
				else :
					$the_title = !empty($options['title_options']['login']) ? $options['title_options']['login'] : __('Log In', 'frontend-user-admin');
				endif;
				break;			
		}

		if ( in_the_loop() && $title == $wp_query->queried_object->post_title ) return $the_title;
				
		if ( substr($wp_version, 0, 3) >= '3.0' ) :
			if(preg_match('/post_id IN \('.$wp_query->queried_object_id.'\)|ORDER BY p\.post_date ASC LIMIT 1/', $wpdb->last_query)) return $the_title;
			else return $title;
		elseif ( substr($wp_version, 0, 3) >= '2.9' ) :
			if(preg_match('/post_id IN \('.$wp_query->queried_object_id.'\)/', $wpdb->last_query)) return $the_title;
			else return $title;
		else :
			if(preg_match('/wp_posts.ID = wp_postmeta.Post_ID/', $wpdb->last_query)) return $the_title;
			else return $title;
		endif;
	}
	
	function login_header($message = '', $wp_error = '') {
		$options = $this->get_frontend_user_admin_data();

		if ( empty($wp_error) )
			$wp_error = new WP_Error();
			
		if ( !empty( $message ) ) echo apply_filters('login_message', $message) . "\n";

		if ( $wp_error->get_error_code() ) {
			$errors = '';
			$messages = '';
			foreach ( $wp_error->get_error_codes() as $code ) {
				$severity = $wp_error->get_error_data($code);
				$tmp_errors = array_unique($wp_error->get_error_messages($code));
				foreach ( $tmp_errors as $error ) {
					if ( 'message' == $severity ) :
						$messages .= '	' . $error . "<br />\n";
					else :
						if ( function_exists('is_multisite') && is_multisite() ) :
							$login_url = network_site_url('wp-login.php?action=lostpassword', 'login');
						else :
							$login_url = site_url('wp-login.php?action=lostpassword', 'login');
						endif;
						$error = preg_replace('/'.preg_quote($login_url,'/').'/', $this->return_frontend_user_admin_login_url().'action=lostpassword', $error );
						$errors .= '	' . $error . "<br />\n";
					endif;
				}
			}
			$messages = preg_replace('/<br \/>\n$/', '', $messages);
			$errors = preg_replace('/<br \/>\n$/', '', $errors);
			if ( !empty($errors) )
				echo '<div class="error"><p>' . apply_filters('login_errors', $errors) . "</p></div>\n";
			if ( !empty($messages) )
				echo '<div class="message"><p>' . apply_filters('login_messages', $messages) . "</p></div>\n";
		}
	}
	
	function retrieve_password() {
		global $wpdb;
		
		$options = $this->get_frontend_user_admin_data();

		$errors = new WP_Error();

		if ( empty( $_POST['user_login'] ) && empty( $_POST['user_email'] ) ) :
			if ( !empty($options['global_settings']['email_as_userlogin']) ) :
				$errors->add('empty_username', __('<strong>ERROR</strong>: Enter your e-mail address.', 'frontend-user-admin'));
			else :
				$errors->add('empty_username', __('<strong>ERROR</strong>: Enter your username or e-mail address.', 'frontend-user-admin'));
			endif;
		endif;

		if ( strstr($_POST['user_login'], '@') ) {
			$user = get_user_by_email(trim($_POST['user_login']));
			if ( empty($user) )
				$errors->add('invalid_email', __('<strong>ERROR</strong>: There is no user registered with that email address.', 'frontend-user-admin'));
		} else {
			$login = trim($_POST['user_login']);
			$user = get_user_by('login', $login);
		}

		do_action('lostpassword_post');

		if ( $errors->get_error_code() )
			return $errors;

		if ( !$user ) {
			$errors->add('invalidcombo', __('<strong>ERROR</strong>: Invalid username or e-mail.', 'frontend-user-admin'));
			return $errors;
		}

		if ( empty($user->user_email) ) :
			$errors->add('invalid_email', __('<strong>ERROR</strong>: E-mail address has not been set.', 'frontend-user-admin'));
			return $errors;
		endif;
		
		if ( !empty($options['user_attribute']['user_attribute']) ) $count_user_attribute = count($options['user_attribute']['user_attribute']);
		else $count_user_attribute = 0;
		
		for($i=0;$i<$count_user_attribute;$i++) :
			if( !empty($options['user_attribute']['user_attribute'][$i]['retrieve_password']) ) :
				if ( $options['user_attribute']['user_attribute'][$i]['type'] == 'datetime' ) :
					if( !empty($_POST[$options['user_attribute']['user_attribute'][$i]['name'].'_year']) ) $_POST[$options['user_attribute']['user_attribute'][$i]['name']] = sprintf('%04d', $_POST[$options['user_attribute']['user_attribute'][$i]['name'].'_year']);
					if( !empty($_POST[$options['user_attribute']['user_attribute'][$i]['name'].'_month']) ) $_POST[$options['user_attribute']['user_attribute'][$i]['name']] .= '-' . sprintf('%02d', $_POST[$options['user_attribute']['user_attribute'][$i]['name'].'_month']);
					if( !empty($_POST[$options['user_attribute']['user_attribute'][$i]['name'].'_day']) ) $_POST[$options['user_attribute']['user_attribute'][$i]['name']] .= '-' . sprintf('%02d', $_POST[$options['user_attribute']['user_attribute'][$i]['name'].'_day']);
					if( !empty($_POST[$options['user_attribute']['user_attribute'][$i]['name'].'_hour']) ) $_POST[$options['user_attribute']['user_attribute'][$i]['name']] .= ' ' . sprintf('%02d', $_POST[$options['user_attribute']['user_attribute'][$i]['name'].'_hour']);
					if( !empty($_POST[$options['user_attribute']['user_attribute'][$i]['name'].'_minute']) ) $_POST[$options['user_attribute']['user_attribute'][$i]['name']] .= ':' . sprintf('%02d', $_POST[$options['user_attribute']['user_attribute'][$i]['name'].'_minute']);
					$_POST[$options['user_attribute']['user_attribute'][$i]['name']] = trim($_POST[$options['user_attribute']['user_attribute'][$i]['name']]);
				endif;
				$meta_value = $wpdb->get_var($wpdb->prepare("SELECT meta_value FROM $wpdb->usermeta WHERE user_id = %s AND meta_key = %s", $user->ID, $options['user_attribute']['user_attribute'][$i]['name']));
				if ( $meta_value != $_POST[$options['user_attribute']['user_attribute'][$i]['name']] ) :
					$errors->add('invalid_email', sprintf(__('<strong>ERROR</strong>: The value you input does not match with %s.', 'frontend-user-admin'), $options['user_attribute']['user_attribute'][$i]['label']));
				endif;
			endif;		
		endfor;
		
		if ( $errors->get_error_code() )
			return $errors;

		// redefining user_login ensures we return the right case in the email
		$user_login = $user->user_login;
		$user_email = $user->user_email;

		do_action('retreive_password', $user_login);  // Misspelled and deprecated
		do_action('retrieve_password', $user_login);

		if ( is_multisite() && !empty($options['global_settings']['unique_registration']) ) :
			$blog_id = get_current_blog_id();
			$user->user_login = preg_replace('/:'.$blog_id.'$/', '', $user->user_login);
		endif;
		
		$key = $wpdb->get_var($wpdb->prepare("SELECT user_activation_key FROM $wpdb->users WHERE user_login = %s", $user_login));
		if ( empty($key) ) {
			// Generate something random for a key...
			$key = wp_generate_password();
			do_action('retrieve_password_key', $user_login, $key);
			// Now insert the new md5 key into the db
			$wpdb->query($wpdb->prepare("UPDATE $wpdb->users SET user_activation_key = %s WHERE user_login = %s", $key, $user_login));
		}
		
		$user->login_url = $options['global_settings']['login_url'];
		$user->signature_template = $options['mail_options']['signature_template'];
		
		$user_meta = get_user_meta($user->ID);
		if ( is_array($user_meta) ) :
			if( !empty($options['global_settings']['array_delimiter']) ) $delimiter = $options['global_settings']['array_delimiter'];
			else $delimiter = ' ';
			foreach( $user_meta as $meta_key => $meta_val ) :
				$array_val = maybe_unserialize(maybe_unserialize($meta_val[0]));
				if ( is_array($array_val) ) :
					$user->{$meta_key} = $this->implode_recursive($delimiter, $array_val);
				endif;
			endforeach;
		endif;
		
		$options['mail_options']['retrieve_password_subject'] = preg_replace_callback('/%([a-zA-Z0-9_]+)%/', function ($m) use ($user) { return $user->{$m[1]}; }, $options['mail_options']['retrieve_password_subject']);
		$options['mail_options']['retrieve_password_body'] = preg_replace('/%key%/', '###key###', $options['mail_options']['retrieve_password_body']);
		$options['mail_options']['retrieve_password_body'] = preg_replace_callback('/%([a-zA-Z0-9_]+)%/', function ($m) use ($user) { return $user->{$m[1]}; }, $options['mail_options']['retrieve_password_body']);
		$options['mail_options']['retrieve_password_body'] = preg_replace('/###key###/', rawurlencode($key), $options['mail_options']['retrieve_password_body']);
		
		$from = 'FROM: '.$options['mail_options']['mail_from']."\n";

		if ( !wp_mail($user_email, $options['mail_options']['retrieve_password_subject'], $options['mail_options']['retrieve_password_body'], $from) )
			die('<p>' . __('The e-mail could not be sent.', 'frontend-user-admin') . "<br />\n" . __('Possible reason: your host may have disabled the mail() function...', 'frontend-user-admin') . '</p>');

		return true;
	}
	
	function reset_password($key) {
		global $wpdb;
		
		$options = $this->get_frontend_user_admin_data();

		//$key = preg_replace('/[^a-z0-9]/i', '', $key);

		if ( empty( $key ) )
			return new WP_Error('invalid_key', __('Invalid key', 'frontend-user-admin'));
			
		$user = $wpdb->get_row($wpdb->prepare("SELECT * FROM $wpdb->users WHERE user_activation_key = %s", $key));
		if ( empty( $user ) )
			return new WP_Error('invalid_key', __('Invalid key', 'frontend-user-admin'));

		do_action('password_reset', $user);

		// Generate something random for a password...
		$new_pass = wp_generate_password();
		wp_set_password($new_pass, $user->ID);

		$user = new WP_User($user->ID);
		
		if ( is_multisite() && !empty($options['global_settings']['unique_registration']) ) :
			$blog_id = get_current_blog_id();
			$user->user_login = preg_replace('/:'.$blog_id.'$/', '', $user->user_login);
		endif;
		
		$user->login_url = $options['global_settings']['login_url'];
		$user->signature_template = $options['mail_options']['signature_template'];
		
		$user_meta = get_user_meta($user->ID);
		if ( is_array($user_meta) ) :
			if( !empty($options['global_settings']['array_delimiter']) ) $delimiter = $options['global_settings']['array_delimiter'];
			else $delimiter = ' ';
			foreach( $user_meta as $meta_key => $meta_val ) :
				$array_val = maybe_unserialize(maybe_unserialize($meta_val[0]));
				if ( is_array($array_val) ) :
					$user->{$meta_key} = $this->implode_recursive($delimiter, $array_val);
				endif;
			endforeach;
		endif;

		$options['mail_options']['reset_password_user_subject'] = preg_replace_callback('/%([a-zA-Z0-9_]+)%/', function ($m) use ($user) { return $user->{$m[1]}; }, $options['mail_options']['reset_password_user_subject']);
		$options['mail_options']['reset_password_user_body'] = preg_replace('/%password%/', '###password###', $options['mail_options']['reset_password_user_body']);
		$options['mail_options']['reset_password_user_body'] = preg_replace_callback('/%([a-zA-Z0-9_]+)%/', function ($m) use ($user) { return $user->{$m[1]}; }, $options['mail_options']['reset_password_user_body']);
		$options['mail_options']['reset_password_user_body'] = preg_replace('/###password###/', $new_pass, $options['mail_options']['reset_password_user_body']);

		$from = 'FROM: '.$options['mail_options']['mail_from']."\n";
				
		if ( !wp_mail($user->user_email, $options['mail_options']['reset_password_user_subject'], $options['mail_options']['reset_password_user_body'], $from) )
			die('<p>' . __('The e-mail could not be sent.', 'frontend-user-admin') . "<br />\n" . __('Possible reason: your host may have disabled the mail() function...', 'frontend-user-admin') . '</p>');

		$options['mail_options']['reset_password_admin_subject'] = preg_replace_callback('/%([a-zA-Z0-9_]+)%/', function ($m) use ($user) { return $user->{$m[1]}; }, $options['mail_options']['reset_password_admin_subject']);
		$options['mail_options']['reset_password_admin_body'] = preg_replace('/%password%/', '###password###', $options['mail_options']['reset_password_admin_body']);
		$options['mail_options']['reset_password_admin_body'] = preg_replace_callback('/%([a-zA-Z0-9_]+)%/', function ($m) use ($user) { return $user->{$m[1]}; }, $options['mail_options']['reset_password_admin_body']);
		$options['mail_options']['reset_password_admin_body'] = preg_replace('/###password###/', $new_pass, $options['mail_options']['reset_password_admin_body']);

		// send a copy of password change notification to the admin
		// but check to see if it's the admin whose password we're changing, and skip this
		if ( $user->user_email != get_option('admin_email') ) {
			wp_mail(get_option('admin_email'), $options['mail_options']['reset_password_admin_subject'], $options['mail_options']['reset_password_admin_body'], $from);
		}
		
		delete_user_meta( $user->ID, 'password_lock_miss_times');
		delete_user_meta( $user->ID, 'password_lock_time');

		return true;
	}
	
	function email_confirmation($key) {
		global $wpdb;
		
		$options = $this->get_frontend_user_admin_data();

		if ( empty( $key ) )
			return new WP_Error('invalid_key', __('Invalid key', 'frontend-user-admin'));
			
		$user = $wpdb->get_row($wpdb->prepare("SELECT * FROM $wpdb->users WHERE user_activation_key = %s", $key));
		if ( empty( $user ) )
			return new WP_Error('invalid_key', __('Invalid key', 'frontend-user-admin'));
			
		if ( !empty($options['global_settings']['approval_registration']) ) :
			$wpdb->update($wpdb->users, array('user_activation_key' => ''), array('user_login' => $user->user_login));
			$this->wp_approval_process_mail($user->ID, '********');
		else :
			$wpdb->update($wpdb->users, array('user_activation_key' => '', 'user_status' => 0), array('user_login' => $user->user_login));
			if ( empty($options['global_settings']['password_registration']) && !empty($options['global_settings']['password_auto_regeneration']) ) :
				$user_pass = wp_generate_password();
				wp_set_password($user_pass, $user->ID);
			else :
				$user_pass = '********';
			endif;
			$this->wp_new_user_notification($user->ID, $user_pass);
		endif;	

		return $user->ID;
	}

	function register_new_user($user_login, $user_email) {
		global $wpdb, $wp_version;
	
		$options = $this->get_frontend_user_admin_data();

		$errors = new WP_Error();

		$user_login = sanitize_user( $user_login );
		$user_email = apply_filters( 'user_registration_email', $user_email );

		$pass1 = $pass2 = '';
		if ( isset( $_POST['pass1'] ))
			$pass1 = $_POST['pass1'];
		if ( isset( $_POST['pass2'] ))
			$pass2 = $_POST['pass2'];

		$user_status = '';
		if ( !empty($options['global_settings']['email_confirmation']) && !empty($user_email) ) :
			$user = $wpdb->get_row($wpdb->prepare("SELECT * FROM $wpdb->users WHERE user_activation_key <> '' AND user_status=1 AND user_email=%s", $user_email));
			$user_status = isset($user->user_status) ? $user->user_status : '';
			if ( !empty( $user ) && isset($_REQUEST['action']) && $_REQUEST['action'] == 'register' && empty($_POST['wp-submit-confirmation']) ) :
				if ( function_exists('is_multisite') && is_multisite() ) :
					require_once(ABSPATH . 'wp-admin/includes/ms.php');
					wpmu_delete_user($user->ID);
				else :
					wp_delete_user($user->ID);
				endif;
			endif;
		endif;
		
		if ( is_multisite() && !empty($options['global_settings']['register_ms_domain']) ) :
			$ms_domain = isset($_POST['ms_domain']) ? $_POST['ms_domain'] : '';
			$ms_title = isset($_POST['ms_title']) ? $_POST['ms_title'] : '';
			$ms_result = wpmu_validate_blog_signup($ms_domain, $ms_title, '');
			$errors = $ms_result['errors'];
		endif;
		
		if ( !empty($options['global_settings']['userlogin_automatic_generation']) ) :
			$user_login = $wpdb->get_var("SELECT MAX(ID) FROM $wpdb->users")+1;
		else :
			if ( !empty($options['global_settings']['email_as_userlogin']) ) {
				$user_login = $user_email;
			} else {
				if ( $user_login == '' )
					$errors->add('user_login', __('<strong>ERROR</strong>: Please enter a username.', 'frontend-user-admin'));
				elseif ( !validate_username( $user_login ) ) {
					$errors->add('user_login', __('<strong>ERROR</strong>: This username is invalid.  Please enter a valid username.', 'frontend-user-admin'));
					$user_login = '';
				} elseif ( username_exists( $user_login ) ) {
					$errors->add('user_login', __('<strong>ERROR</strong>: This username is already registered, please choose another one.', 'frontend-user-admin'));
				} elseif ( $options['global_settings']['user_login_min_letters'] > 0 &&  strlen($user_login) < $options['global_settings']['user_login_min_letters']) {
					$errors->add( 'user_login', sprintf(__( '<strong>ERROR</strong>: Minimum number of letters of Username is %s.', 'frontend-user-admin'), $options['global_settings']['user_login_min_letters']), 'frontend-user-admin' );
				}  elseif ( $options['global_settings']['user_login_max_letters'] > 0 &&  strlen($user_login) > $options['global_settings']['user_login_max_letters']) {
					$errors->add( 'user_login', sprintf(__( '<strong>ERROR</strong>: Maximum number of letters of Username is %s.', 'frontend-user-admin'), $options['global_settings']['user_login_max_letters']), 'frontend-user-admin' );
				} elseif ( !empty($options['global_settings']['user_login_regexp']) && !preg_match('/'.$options['global_settings']['user_login_regexp'].'/', $user_login) ) {
					$errors->add('user_login', __('<strong>ERROR</strong>: This username is invalid.  Please enter a valid username.', 'frontend-user-admin'));
				}
			}
		endif;
					
		// Check the password
		if ( !empty($options['global_settings']["password_registration"]) ) {
			if ( !empty($options['global_settings']["use_common_password"]) ) :
				$pass1 = $options['global_settings']["common_password"];
				$pass2 = $options['global_settings']["common_password"];
			endif;
			
			if ( empty($pass1) )
				$errors->add( 'pass', __( '<strong>ERROR</strong>: Please enter your password.', 'frontend-user-admin' ) );
			elseif ( empty($pass2) )
				$errors->add( 'pass', __( '<strong>ERROR</strong>: Please enter your password twice.', 'frontend-user-admin' ) );
			/* Check for "\" in password */
			elseif( strpos( " ".$pass1, "\\" ) )
				$errors->add( 'pass', __( '<strong>ERROR</strong>: Passwords may not contain the character "\\".', 'frontend-user-admin' ) );
			/* checking the password has been typed twice the same */
			elseif ( $pass1 != $pass2 )
				$errors->add( 'pass', __( '<strong>ERROR</strong>: Please enter the same password in the two password fields.', 'frontend-user-admin' ) );
			elseif ( strlen($pass1) < 4 ) 
				$errors->add( 'pass', __('<strong>ERROR</strong>: Password require at least 4 characters.', 'frontend-user-admin') );
			elseif ( isset($options['global_settings']['user_pass_min_letters']) && $options['global_settings']['user_pass_min_letters'] > 0 &&  strlen($pass1) < $options['global_settings']['user_pass_min_letters'])
				$errors->add( 'pass', sprintf(__( '<strong>ERROR</strong>: Minimum number of letters of Password is %s.', 'frontend-user-admin'), $options['global_settings']['user_pass_min_letters']), 'frontend-user-admin' );
			elseif ( isset($options['global_settings']['user_pass_max_letters']) && $options['global_settings']['user_pass_max_letters'] > 0 &&  strlen($pass1) > $options['global_settings']['user_pass_max_letters'])
				$errors->add( 'pass', sprintf(__( '<strong>ERROR</strong>: Maximum number of letters of Password is %s.', 'frontend-user-admin'), $options['global_settings']['user_pass_max_letters']), 'frontend-user-admin' );
			//elseif ( preg_match('/[^A-Za-z0-9!#$%&\'*+\/=?^_`{|}~ -]/', $pass1 )) 
				//$errors->add( 'pass', __('<strong>ERROR</strong>: You enter the character that is unavailable for the password.', 'frontend-user-admin') );
			
			if (!empty ( $pass1 ))
				$user_pass = $pass1;
		}
		
		if ( !empty($options['global_settings']["register_user_email"]) ) :
			if ($user_email == '') {
				$errors->add('empty_email', __('<strong>ERROR</strong>: Please type your e-mail address.', 'frontend-user-admin'));
			} elseif ( !is_email( $user_email ) ) {
				$errors->add('invalid_email', __('<strong>ERROR</strong>: The email address isn&#8217;t correct.', 'frontend-user-admin'));
				$user_email = '';
			} elseif ( email_exists( $user_email ) && empty($options['global_settings']['email_duplication']) && $user_status != 1 )
				$errors->add('email_exists', __('<strong>ERROR</strong>: This email is already registered, please choose another one.', 'frontend-user-admin'));
		endif;
		
		if ( !empty($options['global_settings']['register_last_name']) && !empty($options['global_settings']['register_last_name_required']) && empty($_POST['last_name']) ) {
			$errors->add( 'last_name', __( '<strong>ERROR</strong>: Please enter your last name.', 'frontend-user-admin' ) );
		}
		
		if ( !empty($options['global_settings']['register_first_name']) && !empty($options['global_settings']['register_first_name_required']) && empty($_POST['first_name']) ) {
			$errors->add( 'first_name', __( '<strong>ERROR</strong>: Please enter your first name.', 'frontend-user-admin' ) );
		}
		
		if ( (!empty($options['global_settings']['nickname_as_display_name']) || !empty($options['global_settings']['register_nickname_required'])) && !empty($options['global_settings']['register_nickname'])) {
			if ( empty($_POST['nickname']) ) {
				$errors->add( 'nickname', __( '<strong>ERROR</strong>: Please enter your nickname.', 'frontend-user-admin' ) );
			} else {
				$_POST['display_name'] = $_POST['nickname'];
			}
		}

		if ( !empty($options['global_settings']['register_user_url']) && !empty($options['global_settings']['register_user_url_required']) && empty($_POST['user_url']) ) {
			$errors->add( 'user_url', __( '<strong>ERROR</strong>: Please enter your web site.', 'frontend-user-admin' ) );
		}

		if ( !empty($options['global_settings']['register_aim']) && !empty($options['global_settings']['register_aim_required']) && empty($_POST['aim']) ) {
			$errors->add( 'aim', __( '<strong>ERROR</strong>: Please enter your AIM.', 'frontend-user-admin' ) );
		}

		if ( !empty($options['global_settings']['register_yim']) && !empty($options['global_settings']['register_yim_required']) && empty($_POST['yim']) ) {
			$errors->add( 'yim', __( '<strong>ERROR</strong>: Please enter your Yahoo IM.', 'frontend-user-admin' ) );
		}

		if ( !empty($options['global_settings']['register_jabber']) && !empty($options['global_settings']['register_jabber_required']) && empty($_POST['jabber']) ) {
			$errors->add( 'jabber', __( '<strong>ERROR</strong>: Please enter your Jabber / Google Talk.', 'frontend-user-admin' ) );
		}

		if ( !empty($options['global_settings']['register_description']) && !empty($options['global_settings']['register_description_required']) && empty($_POST['description']) ) {
			$errors->add( 'description', __( '<strong>ERROR</strong>: Please enter your description.', 'frontend-user-admin' ) );
		}
		
		$errors = $this->check_additional_user('', $errors, 'register');

		if(	!empty($options['global_settings']['terms_of_use_check']) ) {
			if ( empty($_POST['terms_of_use']) )
				$errors->add( 'terms_of_use', __('<strong>ERROR</strong>: You need to agree to the terms of use.', 'frontend-user-admin') );
		}
		
		if ( empty($_POST['wp-submit-after-confirmation']) && empty($this->validkey) ) :
			do_action( 'register_post', $user_login, $user_email, $errors );
			$errors = apply_filters( 'registration_errors', $errors, $user_login, $user_email );
		endif;
		
		if ( $errors->get_error_code() )
			return $errors;
			
		if ( !empty($options['global_settings']['email_confirmation_parallel']) && empty($_POST['wp-submit-confirmation']) && empty($this->validkey) ) :
			set_transient( $user_email, $_POST, 60*60*24 );
			$this->wp_email_confirmation_first_mail($user_email);
			$redirect_to = $this->return_frontend_user_admin_login_url().'checkemail=confirmation';
				
			wp_redirect($redirect_to);
			exit();
		endif; 

		if( isset($_REQUEST['action']) && $_REQUEST['action'] == 'register' && empty($_POST['wp-submit-confirmation']) ) {
			
			if( empty($user_pass) ) {
				$user_pass = wp_generate_password();
			}
			
			if ( !empty($options['global_settings']['email_duplication']) ) define('WP_IMPORTING', true);
			$user_id = wp_create_user( $user_login, $user_pass, $user_email );
			if ( empty($user_id) ) {
				$errors->add('registerfail', sprintf(__('<strong>ERROR</strong>: Couldn&#8217;t register you... please contact the <a href="mailto:%s">webmaster</a> !', 'frontend-user-admin'), get_option('admin_email')));
				return $errors;
			}
			
			if ( is_multisite() ) :
				if ( !empty($ms_result) ) :	
					$domain = $ms_result['domain'];
					$path = $ms_result['path'];
					$blog_title = $ms_result['blog_title'];
	
					$blog_meta_defaults = array(
						'lang_id' => 1,
						'public'  => 1
					);
					$meta_defaults = apply_filters( 'signup_create_blog_meta', $blog_meta_defaults );
					$meta = apply_filters( 'add_signup_meta', $meta_defaults );

					$blog_id = wpmu_create_blog( $domain, $path, $blog_title, $user_id, $meta, 1 );
					add_user_to_blog( $blog_id, $user_id, get_option('default_role') );
				endif;
			
				if( !empty($options['global_settings']['unique_registration']) ) :
					if ( empty($blog_id) ) $blog_id = get_current_blog_id();
					$user_login = $user_login.':'.$blog_id;
					$wpdb->update( $wpdb->users, array('user_login' => $user_login), array('ID' => $user_id) );
				endif;				
			endif;
			
			if ( (!empty($options['global_settings']['email_confirmation']) || !empty($options['global_settings']['approval_registration'])) && !strstr($_SERVER['REQUEST_URI'], 'wp-admin/admin.php') ) :
				$wpdb->update($wpdb->users, array('user_status' => 1), array('user_login' => $user_login));
			endif;

			if ( !empty($_POST['user_status']) ) $wpdb->update($wpdb->users, array('user_status' => 1), array('ID' => $user_id));
			if ( !empty($_POST['no_log']) ) update_user_meta( $user_id, 'no_log', 1);
			if ( !empty($_POST['duplicate_login']) ) update_user_meta( $duplicate_login, 'duplicate_login', 1);

			if ( !empty($options['user_attribute']['user_attribute']) ) $count_user_attribute = count($options['user_attribute']['user_attribute']);
			else $count_user_attribute = 0;

			require_once(ABSPATH . 'wp-admin/includes/admin.php');
			
			if ( !empty($_POST['last_name']) ) update_user_meta( $user_id, 'last_name', $_POST['last_name']);
			if ( !empty($_POST['first_name']) ) update_user_meta( $user_id, 'first_name', $_POST['first_name']);
			if ( !empty($_POST['user_url']) ) update_user_meta( $user_id, 'user_url', $_POST['user_url']);
			if ( !empty($_POST['aim']) ) update_user_meta( $user_id, 'aim', $_POST['aim']);
			if ( !empty($_POST['yim']) ) update_user_meta( $user_id, 'yim', $_POST['yim']);
			if ( !empty($_POST['jabber']) ) update_user_meta( $user_id, 'jabber', $_POST['jabber']);
			if ( !empty($_POST['description']) ) update_user_meta( $user_id, 'description', $_POST['description']);

			for($i=0;$i<$count_user_attribute;$i++) :
				if ( $options['user_attribute']['user_attribute'][$i]['type']=='file' ) :
					if( isset($_FILES[$options['user_attribute']['user_attribute'][$i]['name']]['size']) && $_FILES[$options['user_attribute']['user_attribute'][$i]['name']]['size']>0 ) :
						add_filter('intermediate_image_sizes_advanced', array(&$this, 'frontend_user_admin_intermediate_image_sizes_advanced') );
						$_POST[$options['user_attribute']['user_attribute'][$i]['name']] = media_handle_upload($options['user_attribute']['user_attribute'][$i]['name'], '');
						if ( !is_numeric($_POST[$options['user_attribute']['user_attribute'][$i]['name']]) ) $_POST[$options['user_attribute']['user_attribute'][$i]['name']] = '';
					endif;
				endif;

				if( isset($_POST[$options['user_attribute']['user_attribute'][$i]['name']]) && $_POST[$options['user_attribute']['user_attribute'][$i]['name']]!='' ) :
					if ( version_compare( substr($wp_version, 0, 3), '3.0', '>=' ) ) :
						update_user_meta( $user_id, $options['user_attribute']['user_attribute'][$i]['name'], $_POST[$options['user_attribute']['user_attribute'][$i]['name']]);
					else :
						update_usermeta( $user_id, $options['user_attribute']['user_attribute'][$i]['name'], $_POST[$options['user_attribute']['user_attribute'][$i]['name']]);
					endif;
				endif;
			endfor;
			
			do_action('fua_register_before_sending_email', $user_id);

			if ( !empty($options['global_settings']['email_confirmation']) && !strstr($_SERVER['REQUEST_URI'], 'wp-admin/admin.php') )
				$this->wp_email_confirmation_mail($user_id);
			else if ( !empty($options['global_settings']['approval_registration']) && !strstr($_SERVER['REQUEST_URI'], 'wp-admin/admin.php') )
				$this->wp_approval_process_mail($user_id, $user_pass);
				
			if ( (empty($options['global_settings']['email_confirmation']) && empty($options['global_settings']['approval_registration']) && !strstr($_SERVER['REQUEST_URI'], 'wp-admin/admin.php')) || (strstr($_SERVER['REQUEST_URI'], 'wp-admin/admin.php') && !empty($_POST['send_email'])) )
				$this->wp_new_user_notification($user_id, $user_pass);

			return $user_id;
		} else {
			return null;
		}		
	}
	
	function edit_user( $user_id = 0, $stage = 'register' ) {
		global $wp_roles, $wpdb, $current_user;
		
		$options = $this->get_frontend_user_admin_data();

		$user = new stdClass;
		if ( $user_id ) {
			$update = true;
			$user->ID = (int) $user_id;
			$userdata = get_userdata( $user_id );
			$user->user_login = esc_sql( $userdata->user_login );
		} else {
			$update = false;
		}

		if ( !$update && isset( $_POST['user_login'] ) )
			$user->user_login = sanitize_user($_POST['user_login'], true);

		$pass1 = $pass2 = '';
		if ( isset( $_POST['pass1'] ))
			$pass1 = $_POST['pass1'];
		if ( isset( $_POST['pass2'] ))
			$pass2 = $_POST['pass2'];

		if ( isset( $_POST['role'] ) && current_user_can( 'edit_users' ) ) {
			$new_role = sanitize_text_field( $_POST['role'] );
			$potential_role = isset($wp_roles->role_objects[$new_role]) ? $wp_roles->role_objects[$new_role] : false;
			// Don't let anyone with 'edit_users' (admins) edit their own role to something without it.
			// Multisite super admins can freely edit their blog roles -- they possess all caps.
			if ( ( function_exists('is_multisite') && is_multisite() && current_user_can( 'manage_sites' ) ) || $user_id != $current_user->ID || ($potential_role && $potential_role->has_cap( 'edit_users' ) ) )
				$user->role = $new_role;

			// If the new role isn't editable by the logged-in user die with error
			$editable_roles = get_editable_roles();
			if ( ! empty( $new_role ) && empty( $editable_roles[$new_role] ) )
				wp_die(__('You can&#8217;t give users that role.'));
		}

		if ( isset( $_POST['email'] ))
			$user->user_email = sanitize_text_field( $_POST['email'] );
		if ( isset( $_POST['url'] ) ) {
			if ( empty ( $_POST['url'] ) || $_POST['url'] == 'http://' ) {
				$user->user_url = '';
			} else {
				$user->user_url = esc_url_raw( $_POST['url'] );
				$user->user_url = preg_match('/^(https?|ftps?|mailto|news|irc|gopher|nntp|feed|telnet):/is', $user->user_url) ? $user->user_url : 'http://'.$user->user_url;
			}
		}
		if ( isset( $_POST['first_name'] ) )
			$user->first_name = sanitize_text_field( $_POST['first_name'] );
		if ( isset( $_POST['last_name'] ) )
			$user->last_name = sanitize_text_field( $_POST['last_name'] );
		if ( isset( $_POST['nickname'] ) )
			$user->nickname = sanitize_text_field( $_POST['nickname'] );
		if ( !empty($options['global_settings']['nickname_as_display_name']) && !empty($_POST['nickname']) )
			$_POST['display_name'] = $_POST['nickname'];
		if ( isset( $_POST['display_name'] ) )
			$user->display_name = sanitize_text_field( $_POST['display_name'] );

		if ( isset( $_POST['description'] ) )
			$user->description = trim( $_POST['description'] );

		foreach ( _wp_get_user_contactmethods( $user ) as $method => $name ) {
			if ( isset( $_POST[$method] ))
				$user->{$method} = sanitize_text_field( $_POST[$method] );
		}
		
		$user->use_ssl = 0;
		if ( !empty($_POST['use_ssl']) )
			$user->use_ssl = 1;

		$errors = new WP_Error();

		/* checking that username has been typed */
		if ( $user->user_login == '' )
			$errors->add( 'user_login', __( '<strong>ERROR</strong>: Please enter a username.', 'frontend-user-admin' ));

		/* checking the password has been typed twice */
		do_action_ref_array( 'check_passwords', array ( $user->user_login, & $pass1, & $pass2 ));

		if ( $update ) {
			if ( empty($pass1) && !empty($pass2) )
				$errors->add( 'pass', __( '<strong>ERROR</strong>: You entered your new password only once.', 'frontend-user-admin' ), array( 'form-field' => 'pass1' ) );
			elseif ( !empty($pass1) && empty($pass2) )
				$errors->add( 'pass', __( '<strong>ERROR</strong>: You entered your new password only once.', 'frontend-user-admin' ), array( 'form-field' => 'pass2' ) );
			elseif ( !empty($pass1) && $options['global_settings']['user_pass_min_letters'] > 0 &&  strlen($pass1) < $options['global_settings']['user_pass_min_letters'])
				$errors->add( 'pass', sprintf(__( '<strong>ERROR</strong>: Minimum number of letters of Password is %s.', 'frontend-user-admin'), $options['global_settings']['user_pass_min_letters']), 'frontend-user-admin' );
			elseif ( !empty($pass1) && $options['global_settings']['user_pass_max_letters'] > 0 &&  strlen($pass1) > $options['global_settings']['user_pass_max_letters'])
				$errors->add( 'pass', sprintf(__( '<strong>ERROR</strong>: Maximum number of letters of Password is %s.', 'frontend-user-admin'), $options['global_settings']['user_pass_max_letters']), 'frontend-user-admin' );
		} else {
			if ( empty($pass1) )
				$errors->add( 'pass', __( '<strong>ERROR</strong>: Please enter your password.', 'frontend-user-admin' ), array( 'form-field' => 'pass1' ) );
			elseif ( empty($pass2) )
				$errors->add( 'pass', __( '<strong>ERROR</strong>: Please enter your password twice.', 'frontend-user-admin' ), array( 'form-field' => 'pass2' ) );
		}

		/* Check for "\" in password */
		if ( false !== strpos( stripslashes($pass1), "\\" ) )
			$errors->add( 'pass', __( '<strong>ERROR</strong>: Passwords may not contain the character "\\".', 'frontend-user-admin' ), array( 'form-field' => 'pass1' ) );

		/* checking the password has been typed twice the same */
		if ( $pass1 != $pass2 )
			$errors->add( 'pass', __( '<strong>ERROR</strong>: Please enter the same password in the two password fields.', 'frontend-user-admin' ), array( 'form-field' => 'pass1' ) );

		if ( !empty( $pass1 ) )
			$user->user_pass = $pass1;

		if ( !$update && isset( $_POST['user_login'] ) && !validate_username( $_POST['user_login'] ) )
			$errors->add( 'user_login', __( '<strong>ERROR</strong>: This username is invalid because it uses illegal characters. Please enter a valid username.', 'frontend-user-admin' ));

		if ( !$update && username_exists( $user->user_login ) )
			$errors->add( 'user_login', __( '<strong>ERROR</strong>: This username is already registered. Please choose another one.', 'frontend-user-admin' ));

		/* checking e-mail address */
		if ( !empty($options['global_settings']["profile_user_email"]) ) :
			if ( empty( $user->user_email ) ) {
				$errors->add( 'empty_email', __( '<strong>ERROR</strong>: Please enter an e-mail address.', 'frontend-user-admin' ), array( 'form-field' => 'email' ) );
			} elseif ( !is_email( $user->user_email ) ) {
				$errors->add( 'invalid_email', __( '<strong>ERROR</strong>: The e-mail address isn&#8217;t correct.', 'frontend-user-admin' ), array( 'form-field' => 'email' ) );
			} elseif ( ( $owner_id = email_exists($user->user_email) ) && ( !$update || ( $owner_id != $user->ID ) ) && empty($options['global_settings']['email_duplication']) ) {
				$errors->add( 'email_exists', __('<strong>ERROR</strong>: This email is already registered, please choose another one.', 'frontend-user-admin'), array( 'form-field' => 'email' ) );
			}
		endif;
		
		if ( $stage == 'profile' ) $errors = $this->check_additional_user($user->ID, $errors, 'profile');
		
		do_action_ref_array('user_profile_update_errors', array ( &$errors, $update, &$user ) );
		
		if ( $errors->get_error_codes() && (!is_admin() || empty($options['global_settings']['enforcement_update']) || $stage == 'register') )
			return $errors;
		
		if ( !empty($options['global_settings']['email_duplication']) ) :
			define('WP_IMPORTING', true);
		endif;

		if ( $update ) {
			$user_id = wp_update_user( get_object_vars( $user ) );
		} else {
			$user_id = wp_insert_user( get_object_vars( $user ) );
			$this->wp_new_user_notification( $user_id, isset($_POST['send_password']) ? $pass1 : '' );
		}

		if ( $stage == 'profile' ) $this->edit_additional_user($user_id);
	
		return $user_id;
	}
	
	function frontend_user_admin_edit_user($errors, $update, $user) {
		$options = $this->get_frontend_user_admin_data();

		if ( !empty($options['global_settings']['profile_last_name']) && ((is_admin() && !empty($options['global_settings']['profile_last_name_admin']) && !empty($options['global_settings']['profile_last_name_required'])) || (!is_admin() && empty($options['global_settings']['profile_last_name_admin']) && !empty($options['global_settings']['profile_last_name_required']))) && empty($_POST['last_name']) ) {
			$errors->add( 'last_name', __( '<strong>ERROR</strong>: Please enter your last name.', 'frontend-user-admin' ) );
		}

		if ( !empty($options['global_settings']['profile_first_name']) && ((is_admin() && !empty($options['global_settings']['profile_first_name_admin']) && !empty($options['global_settings']['profile_first_name_required'])) || (!is_admin() && empty($options['global_settings']['profile_first_name_admin']) && !empty($options['global_settings']['profile_first_name_required']))) && empty($_POST['first_name']) ) {
			$errors->add( 'first_name', __( '<strong>ERROR</strong>: Please enter your first name.', 'frontend-user-admin' ) );
		}

		if ( !empty($options['global_settings']['profile_nickname']) && ((is_admin() && !empty($options['global_settings']['profile_nickname_admin']) && !empty($options['global_settings']['profile_nickname_required'])) || (!is_admin() && empty($options['global_settings']['profile_nickname_admin']) && !empty($options['global_settings']['profile_nickname_required']))) && empty($_POST['nickname']) ) {
			$errors->add( 'nickname', __( '<strong>ERROR</strong>: Please enter your nickname.', 'frontend-user-admin' ) );
		}

		if ( !empty($options['global_settings']['profile_user_url']) && ((is_admin() && !empty($options['global_settings']['profile_user_url_admin']) && !empty($options['global_settings']['profile_user_url_required'])) || (!is_admin() && empty($options['global_settings']['profile_user_url_admin']) && !empty($options['global_settings']['profile_user_url_required']))) && empty($_POST['user_url']) ) {
			$errors->add( 'user_url', __( '<strong>ERROR</strong>: Please enter your web site.', 'frontend-user-admin' ) );
		}

		if ( !empty($options['global_settings']['profile_aim']) && ((is_admin() && !empty($options['global_settings']['profile_aim_admin']) && !empty($options['global_settings']['profile_aim_required'])) || (!is_admin() && empty($options['global_settings']['profile_aim_admin']) && !empty($options['global_settings']['profile_aim_required']))) && empty($_POST['aim']) ) {
			$errors->add( 'aim', __( '<strong>ERROR</strong>: Please enter your AIM.', 'frontend-user-admin' ) );
		}

		if ( !empty($options['global_settings']['profile_yim']) && ((is_admin() && !empty($options['global_settings']['profile_yim_admin']) && !empty($options['global_settings']['profile_yim_required'])) || (!is_admin() && empty($options['global_settings']['profile_yim_admin']) && !empty($options['global_settings']['profile_yim_required']))) && empty($_POST['yim']) ) {
			$errors->add( 'yim', __( '<strong>ERROR</strong>: Please enter your Yahoo IM.', 'frontend-user-admin' ) );
		}

		if ( !empty($options['global_settings']['profile_jabber']) && ((is_admin() && !empty($options['global_settings']['profile_jabber_admin']) && !empty($options['global_settings']['profile_jabber_required'])) || (!is_admin() && empty($options['global_settings']['profile_jabber_admin']) && !empty($options['global_settings']['profile_jabber_required']))) && empty($_POST['jabber']) ) {
			$errors->add( 'jabber', __( '<strong>ERROR</strong>: Please enter your Jabber / Google Talk.', 'frontend-user-admin' ) );
		}

		if ( !empty($options['global_settings']['profile_description']) && ((is_admin() && !empty($options['global_settings']['profile_description_admin']) && !empty($options['global_settings']['profile_description_required'])) || (!is_admin() && empty($options['global_settings']['profile_description_admin']) && !empty($options['global_settings']['profile_description_required']))) && empty($_POST['description']) ) {
			$errors->add( 'description', __( '<strong>ERROR</strong>: Please enter your description.', 'frontend-user-admin' ) );
		}
	}
	
	function frontend_user_admin_wp_mail($args) {
		extract($args, EXTR_SKIP);
		
		$mail_subject   = $subject;
		$mail_sender    = $from;
		$mail_body      = $body;
		$mail_recipient = $to;
		$mail_headers   = $from . "\n";
		$mail_headers   .= isset($headers) ? $headers : '';
		if( !empty($cc) ) $mail_headers .= "CC: " . $cc . "\n";
		if( !empty($bcc) ) $mail_headers .= "BCC: " . $bcc . "\n";
		$mail_headers .= "Content-Type: text/plain; charset=\"" . get_option('blog_charset') . "\"\n";
		$mail_attachments = isset($attachments) ? $attachments : '';
		if ( !empty($mail_recipient) ) @wp_mail($mail_recipient, $mail_subject, $mail_body, $mail_headers, $mail_attachments);
	}
	
	function wp_email_confirmation_first_mail($email) {
		global $wpdb;
		$options = $this->get_frontend_user_admin_data();

		$key = $this->fua_crypt(date_i18n('U').':'.$email);
		$login_url = $options['global_settings']['login_url'];
		$signature_template = isset($options['mail_options']['signature_template']) ? $options['mail_options']['signature_template'] : '';
		
		$options['mail_options']['email_confirmation_first_user_body'] = preg_replace('/%key%/', urlencode($key), $options['mail_options']['email_confirmation_first_user_body']);
		$options['mail_options']['email_confirmation_first_user_body'] = preg_replace('/%login_url%/', $login_url, $options['mail_options']['email_confirmation_first_user_body']);
		$options['mail_options']['email_confirmation_first_user_body'] = preg_replace('/%signature_template%/', $signature_template, $options['mail_options']['email_confirmation_first_user_body']);

		$from = 'FROM: '.$options['mail_options']['mail_from']."\n";

		if ( !empty($options['mail_options']['email_confirmation_first_user_subject']) && !empty($options['mail_options']['email_confirmation_first_user_body']) && !empty($email) ) 
			@wp_mail($email, $options['mail_options']['email_confirmation_first_user_subject'], $options['mail_options']['email_confirmation_first_user_body'], $from);
	}

	function wp_email_confirmation_mail($user_id) {
		global $wpdb;
		$options = $this->get_frontend_user_admin_data();

		$user = new WP_User($user_id);
		
		if ( is_multisite() && !empty($options['global_settings']['unique_registration']) ) :
			$blog_id = get_current_blog_id();
			$user->user_login = preg_replace('/:'.$blog_id.'$/', '', $user->user_login);
		endif;
		
		$user->login_url = $options['global_settings']['login_url'];
		$user->signature_template = isset($options['mail_options']['signature_template']) ? $options['mail_options']['signature_template'] : '';
		
		$user_meta = get_user_meta($user->ID);

		if ( is_array($user_meta) ) :
			if( !empty($options['global_settings']['array_delimiter']) ) $delimiter = $options['global_settings']['array_delimiter'];
			else $delimiter = ' ';
			foreach( $user_meta as $meta_key => $meta_val ) :
				$array_val = maybe_unserialize(maybe_unserialize($meta_val[0]));
				if ( is_array($array_val) ) :
					$user->{$meta_key} = $this->implode_recursive($delimiter, $array_val);
				else :
					$user->{$meta_key} = $array_val;
				endif;
			endforeach;
		endif;
	
		$key = wp_generate_password(20, false);
		$wpdb->update($wpdb->users, array('user_activation_key' => $key), array('user_login' => $user->user_login));

		$options['mail_options']['email_confirmation_user_subject'] = preg_replace_callback('/%([a-zA-Z0-9_]+)%/', function ($m) use ($user) { return $user->{$m[1]}; }, $options['mail_options']['email_confirmation_user_subject']);
		$options['mail_options']['email_confirmation_user_body'] = preg_replace('/%key%/', '###key###', $options['mail_options']['email_confirmation_user_body']);
		$options['mail_options']['email_confirmation_user_body'] = preg_replace_callback('/%([a-zA-Z0-9_]+)%/', function ($m) use ($user) { return $user->{$m[1]}; }, $options['mail_options']['email_confirmation_user_body']);
		$options['mail_options']['email_confirmation_user_body'] = preg_replace('/###key###/', rawurlencode($key), $options['mail_options']['email_confirmation_user_body']);

		$from = 'FROM: '.$options['mail_options']['mail_from']."\n";

		if ( !empty($options['mail_options']['email_confirmation_user_subject']) && !empty($options['mail_options']['email_confirmation_user_body']) && !empty($user->user_email) ) 
			@wp_mail($user->user_email, $options['mail_options']['email_confirmation_user_subject'], $options['mail_options']['email_confirmation_user_body'], $from);
	}

	function wp_approval_process_mail($user_id, $plaintext_pass = '') {
		$options = $this->get_frontend_user_admin_data();

		$user = new WP_User($user_id);

		if ( is_multisite() && !empty($options['global_settings']['unique_registration']) ) :
			$blog_id = get_current_blog_id();
			$user->user_login = preg_replace('/:'.$blog_id.'$/', '', $user->user_login);
		endif;
		
		$user->login_url = $options['global_settings']['login_url'];
		$user->password = $plaintext_pass;
		$user->signature_template = isset($options['mail_options']['signature_template']) ? $options['mail_options']['signature_template'] : '';
		
		$user_meta = get_user_meta($user->ID);
		if ( is_array($user_meta) ) :
			if( !empty($options['global_settings']['array_delimiter']) ) $delimiter = $options['global_settings']['array_delimiter'];
			else $delimiter = ' ';
			foreach( $user_meta as $meta_key => $meta_val ) :
				$array_val = maybe_unserialize(maybe_unserialize($meta_val[0]));
				if ( is_array($array_val) ) :
					$user->{$meta_key} = $this->implode_recursive($delimiter, $array_val);
				else :
					$user->{$meta_key} = $array_val;
				endif;
			endforeach;
		endif;
						
		$options['mail_options']['approval_process_admin_subject'] = preg_replace_callback('/%([a-zA-Z0-9_]+)%/', function ($m) use ($user) { return $user->{$m[1]}; }, $options['mail_options']['approval_process_admin_subject']);
		$options['mail_options']['approval_process_admin_body'] = preg_replace_callback('/%([a-zA-Z0-9_]+)%/', function ($m) use ($user) { return $user->{$m[1]}; }, $options['mail_options']['approval_process_admin_body']);
		
		$from = 'FROM: '.$options['mail_options']['mail_from']."\n";
		
		if ( !empty($options['mail_options']['approval_process_admin_subject']) && !empty($options['mail_options']['approval_process_admin_body']) ) 
			@wp_mail(get_option('admin_email'), $options['mail_options']['approval_process_admin_subject'], $options['mail_options']['approval_process_admin_body'], $from);

		$options['mail_options']['approval_process_user_subject'] = preg_replace_callback('/%([a-zA-Z0-9_]+)%/', function ($m) use ($user) { return $user->{$m[1]}; }, $options['mail_options']['approval_process_user_subject']);
		$options['mail_options']['approval_process_user_body'] = preg_replace_callback('/%([a-zA-Z0-9_]+)%/', function ($m) use ($user) { return $user->{$m[1]}; }, $options['mail_options']['approval_process_user_body']);

		if ( !empty($options['mail_options']['approval_process_user_subject']) && !empty($options['mail_options']['approval_process_user_body']) && !empty($user->user_email) ) 
			@wp_mail($user->user_email, $options['mail_options']['approval_process_user_subject'], $options['mail_options']['approval_process_user_body'], $from);
	}
	
	function wp_withdrawal_mail($user_id) {
		$options = $this->get_frontend_user_admin_data();

		$user = new WP_User($user_id);

		if ( is_multisite() && !empty($options['global_settings']['unique_registration']) ) :
			$blog_id = get_current_blog_id();
			$user->user_login = preg_replace('/:'.$blog_id.'$/', '', $user->user_login);
		endif;
		
		$user->login_url = $options['global_settings']['login_url'];
		$user->signature_template = isset($options['mail_options']['signature_template']) ? $options['mail_options']['signature_template'] : '';
		
		$user_meta = get_user_meta($user->ID);
		if ( is_array($user_meta) ) :
			if( !empty($options['global_settings']['array_delimiter']) ) $delimiter = $options['global_settings']['array_delimiter'];
			else $delimiter = ' ';
			foreach( $user_meta as $meta_key => $meta_val ) :
				$array_val = maybe_unserialize(maybe_unserialize($meta_val[0]));
				if ( is_array($array_val) ) :
					$user->{$meta_key} = $this->implode_recursive($delimiter, $array_val);
				endif;
			endforeach;
		endif;
				
		$options['mail_options']['withdrawal_admin_subject'] = preg_replace_callback('/%([a-zA-Z0-9_]+)%/', function ($m) use ($user) { return $user->{$m[1]}; }, $options['mail_options']['withdrawal_admin_subject']);
		$options['mail_options']['withdrawal_admin_body'] = preg_replace_callback('/%([a-zA-Z0-9_]+)%/', function ($m) use ($user) { return $user->{$m[1]}; }, $options['mail_options']['withdrawal_admin_body']);

		$from = 'FROM: '.$options['mail_options']['mail_from']."\n";
		
		if ( !empty($options['mail_options']['withdrawal_admin_subject']) && !empty($options['mail_options']['withdrawal_admin_body']) )
			@wp_mail(get_option('admin_email'), $options['mail_options']['withdrawal_admin_subject'], $options['mail_options']['withdrawal_admin_body'], $from);

		$options['mail_options']['withdrawal_user_subject'] = preg_replace_callback('/%([a-zA-Z0-9_]+)%/', function ($m) use ($user) { return $user->{$m[1]}; }, $options['mail_options']['withdrawal_user_subject']);
		$options['mail_options']['withdrawal_user_body'] = preg_replace_callback('/%([a-zA-Z0-9_]+)%/', function ($m) use ($user) { return $user->{$m[1]}; }, $options['mail_options']['withdrawal_user_body']);

		if ( !empty($options['mail_options']['withdrawal_user_subject']) && !empty($options['mail_options']['withdrawal_user_body']) && !empty($user->user_email) )
			@wp_mail($user->user_email, $options['mail_options']['withdrawal_user_subject'], $options['mail_options']['withdrawal_user_body'], $from);
	}
	
	function wp_new_user_notification($user_id, $plaintext_pass = '') {
		$options = $this->get_frontend_user_admin_data();

		$user = new WP_User($user_id);

		if ( is_multisite() && !empty($options['global_settings']['unique_registration']) ) :
			$blog_id = get_current_blog_id();
			$user->user_login = preg_replace('/:'.$blog_id.'$/', '', $user->user_login);
		endif;
		
		if ( is_multisite() ) :
			if ( !empty($_POST['ms_domain']) ) $user->ms_domain = $_POST['ms_domain'];	
			if ( !empty($_POST['ms_title']) ) $user->ms_title = $_POST['ms_title'];	
		endif;

		$user->login_url = $options['global_settings']['login_url'];
		$user->password = $plaintext_pass;
		$user->signature_template = isset($options['mail_options']['signature_template']) ? $options['mail_options']['signature_template'] : '';

		if ( is_array($_POST) ) :
			if( !empty($options['global_settings']['array_delimiter']) ) $delimiter = $options['global_settings']['array_delimiter'];
			else $delimiter = ' ';
			foreach( $_POST as $key => $val ) :
				if ( is_array($val) ) :
					$user->{$key} = $this->implode_recursive($delimiter, $val);
				else :
					$user->{$key} = $val;
				endif;
			endforeach;
		endif;
		
		$options['mail_options']['new_user_notification_admin_subject'] = preg_replace_callback('/%([a-zA-Z0-9_]+)%/', function ($m) use ($user) { return $user->{$m[1]}; }, $options['mail_options']['new_user_notification_admin_subject']);
		$options['mail_options']['new_user_notification_admin_body'] = preg_replace_callback('/%([a-zA-Z0-9_]+)%/', function ($m) use ($user) { return $user->{$m[1]}; }, $options['mail_options']['new_user_notification_admin_body']);

		$args['user_id'] = $user_id;
		$args['to'] = get_option('admin_email');
		$args['subject'] = $options['mail_options']['new_user_notification_admin_subject'];
		$args['body'] = $options['mail_options']['new_user_notification_admin_body'];
		$args['from'] = 'FROM: '.$options['mail_options']['mail_from']."\n";

		$args = apply_filters('fua_registration_admin_mail', $args);				

		if ( !empty($options['mail_options']['new_user_notification_admin_subject']) && !empty($options['mail_options']['new_user_notification_admin_body']) )
			$this->frontend_user_admin_wp_mail($args);
			//@wp_mail(get_option('admin_email'), $options['mail_options']['new_user_notification_admin_subject'], $options['mail_options']['new_user_notification_admin_body'], $from);

		if ( empty($plaintext_pass) )
			return;

		$options['mail_options']['new_user_notification_user_subject'] = preg_replace_callback('/%([a-zA-Z0-9_]+)%/', function ($m) use ($user) { return $user->{$m[1]}; }, $options['mail_options']['new_user_notification_user_subject']);
		$options['mail_options']['new_user_notification_user_body'] = preg_replace_callback('/%([a-zA-Z0-9_]+)%/', function ($m) use ($user) { return $user->{$m[1]}; }, $options['mail_options']['new_user_notification_user_body']);

		unset($args);

		$args['user_id'] = $user_id;
		$args['to'] = $user->user_email;
		$args['subject'] = $options['mail_options']['new_user_notification_user_subject'];
		$args['body'] = $options['mail_options']['new_user_notification_user_body'];
		$args['from'] = 'FROM: '.$options['mail_options']['mail_from']."\n";

		$args = apply_filters('fua_registration_user_mail', $args);				

		if ( !empty($options['mail_options']['new_user_notification_user_subject']) && !empty($options['mail_options']['new_user_notification_user_body']) && !empty($user->user_email) )
			$this->frontend_user_admin_wp_mail($args);
			//@wp_mail($user->user_email, $options['mail_options']['new_user_notification_user_subject'], $options['mail_options']['new_user_notification_user_body'], $from);
	}
	
	function wp_profile_update_mail($user_id) {
		$options = $this->get_frontend_user_admin_data();

		$user = new WP_User($user_id);
		
		if( is_multisite() && !empty($options['global_settings']['unique_registration']) ) :
			$blog_id = get_current_blog_id();
			$user->user_login = preg_replace('/:'.$blog_id.'$/', '', $user->user_login);
		endif;

		$user->login_url = $options['global_settings']['login_url'];
		$user->password = !empty($_POST['pass1']) ? $_POST['pass1'] : '********';
		$user->signature_template = isset($options['mail_options']['signature_template']) ? $options['mail_options']['signature_template'] : '';
		
		$user_meta = get_user_meta($user->ID);
		if ( is_array($user_meta) ) :
			if( !empty($options['global_settings']['array_delimiter']) ) $delimiter = $options['global_settings']['array_delimiter'];
			else $delimiter = ' ';
			foreach( $user_meta as $meta_key => $meta_val ) :
				$array_val = maybe_unserialize(maybe_unserialize($meta_val[0]));
				if ( is_array($array_val) ) :
					$user->{$meta_key} = $this->implode_recursive($delimiter, $array_val);
				endif;
			endforeach;
		endif;
				
		$options['mail_options']['profile_update_admin_subject'] = isset($options['mail_options']['profile_update_admin_subject']) ? preg_replace_callback('/%([a-zA-Z0-9_]+)%/', function ($m) use ($user) { return $user->{$m[1]}; }, $options['mail_options']['profile_update_admin_subject']) : '';
		$options['mail_options']['profile_update_admin_body'] = isset($options['mail_options']['profile_update_admin_body']) ? preg_replace_callback('/%([a-zA-Z0-9_]+)%/', function ($m) use ($user) { return $user->{$m[1]}; }, $options['mail_options']['profile_update_admin_body']) : '';

		$from = 'FROM: '.$options['mail_options']['mail_from']."\n";
		
		if ( !empty($options['mail_options']['profile_update_admin_subject']) && !empty($options['mail_options']['profile_update_admin_body']) )
			@wp_mail(get_option('admin_email'), $options['mail_options']['profile_update_admin_subject'], $options['mail_options']['profile_update_admin_body'], $from);

		$options['mail_options']['profile_update_user_subject'] = isset($options['mail_options']['profile_update_user_subject']) ? preg_replace_callback('/%([a-zA-Z0-9_]+)%/', function ($m) use ($user) { return $user->{$m[1]}; }, $options['mail_options']['profile_update_user_subject']) : '';
		$options['mail_options']['profile_update_user_body'] = isset($options['mail_options']['profile_update_user_body']) ? preg_replace_callback('/%([a-zA-Z0-9_]+)%/', function ($m) use ($user) { return $user->{$m[1]}; }, $options['mail_options']['profile_update_user_body']) : '';

		if ( !empty($options['mail_options']['profile_update_user_subject']) && !empty($options['mail_options']['profile_update_user_body']) && !empty($user->user_email) )
			@wp_mail($user->user_email, $options['mail_options']['profile_update_user_subject'], $options['mail_options']['profile_update_user_body'], $from);
	}
	
	function check_login_referer($action = -1, $query_arg = '_wpnonce') {
		$options = $this->get_frontend_user_admin_data();

		$adminurl = $options['global_settings']['login_url'];
		$referer = strtolower(wp_get_referer());
		$result = isset($_REQUEST[$query_arg]) ? wp_verify_nonce($_REQUEST[$query_arg], $action) : false;

		if ( ! $result && ! ( -1 == $action && strpos( $referer, $adminurl ) === 0 ) ) {
			wp_nonce_ays($action);
			die();
		}
		return $result;
	}
	
	function check_additional_user ($user_id, $errors, $stage = 'register') {
		global $wpdb;
		$options = $this->get_frontend_user_admin_data();

		$composite_unique = array();
		if ( !empty($options['global_settings']['register_last_name']) && !empty($options['global_settings']['register_last_name_composite_unique']) && !empty($_POST['last_name']) ) $composite_unique['last_name'] = $_POST['last_name'];
		if ( !empty($options['global_settings']['register_first_name']) && !empty($options['global_settings']['register_first_name_composite_unique']) && !empty($_POST['first_name']) ) $composite_unique['first_name'] = $_POST['first_name'];
		if ( !empty($options['global_settings']['register_nickname']) && !empty($options['global_settings']['register_nickname_composite_unique']) && !empty($_POST['nickname']) ) $composite_unique['nickname'] = $_POST['nickname'];

		if ( !empty($options['user_attribute']['user_attribute']) ) $count_user_attribute = count($options['user_attribute']['user_attribute']);
		else $count_user_attribute = 0;
		
		for($i=0;$i<$count_user_attribute;$i++) :
			$datetime_error = 0;
			if ( empty($options['global_settings'][$stage.'_'.$options['user_attribute']['user_attribute'][$i]['name']]) ) continue;
			if( isset($_POST[$options['user_attribute']['user_attribute'][$i]['name']]) ) {
				if(is_array($_POST[$options['user_attribute']['user_attribute'][$i]['name']])) {
					$_POST[$options['user_attribute']['user_attribute'][$i]['name']] = $_POST[$options['user_attribute']['user_attribute'][$i]['name']];
				} else {
					$_POST[$options['user_attribute']['user_attribute'][$i]['name']] = esc_html( trim( $_POST[$options['user_attribute']['user_attribute'][$i]['name']] ));
				}
			}

			if ( (!is_admin() && $options['user_attribute']['user_attribute'][$i]['type'] == 'datetime') || (is_admin() && $options['user_attribute']['user_attribute'][$i]['type2'] == 'datetime') ) :
				if( !empty($_POST[$options['user_attribute']['user_attribute'][$i]['name'].'_year']) ) $_POST[$options['user_attribute']['user_attribute'][$i]['name']] = sprintf('%04d', $_POST[$options['user_attribute']['user_attribute'][$i]['name'].'_year']);
				if( !empty($_POST[$options['user_attribute']['user_attribute'][$i]['name'].'_month']) ) $_POST[$options['user_attribute']['user_attribute'][$i]['name']] .= '-' . sprintf('%02d', $_POST[$options['user_attribute']['user_attribute'][$i]['name'].'_month']);
				if( !empty($_POST[$options['user_attribute']['user_attribute'][$i]['name'].'_day']) ) $_POST[$options['user_attribute']['user_attribute'][$i]['name']] .= '-' . sprintf('%02d', $_POST[$options['user_attribute']['user_attribute'][$i]['name'].'_day']);
				if( !empty($_POST[$options['user_attribute']['user_attribute'][$i]['name'].'_hour']) ) $_POST[$options['user_attribute']['user_attribute'][$i]['name']] .= ' ' . sprintf('%02d', $_POST[$options['user_attribute']['user_attribute'][$i]['name'].'_hour']);
				if( !empty($_POST[$options['user_attribute']['user_attribute'][$i]['name'].'_minute']) ) $_POST[$options['user_attribute']['user_attribute'][$i]['name']] .= ':' . sprintf('%02d', $_POST[$options['user_attribute']['user_attribute'][$i]['name'].'_minute']);
				$_POST[$options['user_attribute']['user_attribute'][$i]['name']] = isset($_POST[$options['user_attribute']['user_attribute'][$i]['name']]) ? trim($_POST[$options['user_attribute']['user_attribute'][$i]['name']]) : '';
				
				if( isset($_POST[$options['user_attribute']['user_attribute'][$i]['name'].'_year']) && $_POST[$options['user_attribute']['user_attribute'][$i]['name'].'_year']=='' ) $datetime_error = 1; 
				if( isset($_POST[$options['user_attribute']['user_attribute'][$i]['name'].'_month']) && $_POST[$options['user_attribute']['user_attribute'][$i]['name'].'_month']=='' ) $datetime_error = 1; 
				if( isset($_POST[$options['user_attribute']['user_attribute'][$i]['name'].'_day']) && $_POST[$options['user_attribute']['user_attribute'][$i]['name'].'_day']=='' ) $datetime_error = 1; 
				if( isset($_POST[$options['user_attribute']['user_attribute'][$i]['name'].'_hour']) && $_POST[$options['user_attribute']['user_attribute'][$i]['name'].'_hour']=='' ) $datetime_error = 1; 
				if( isset($_POST[$options['user_attribute']['user_attribute'][$i]['name'].'_minute']) && $_POST[$options['user_attribute']['user_attribute'][$i]['name'].'_minute']=='' ) $datetime_error = 1; 
			endif;
			
			if( !empty($options['user_attribute']['user_attribute'][$i]['required']) && ( is_admin() || ( !is_admin() && empty($options['user_attribute']['user_attribute'][$i]['admin']))) ) :
				if( (empty($_POST[$options['user_attribute']['user_attribute'][$i]['name']]) && empty($_FILES[$options['user_attribute']['user_attribute'][$i]['name']])) || $datetime_error==1 )
					$errors->add( $options['user_attribute']['user_attribute'][$i]['name'], sprintf(__( '<strong>ERROR</strong>: Please enter %s.', 'frontend-user-admin'), $options['user_attribute']['user_attribute'][$i]['label']), 'frontend-user-admin' );
			endif;

			if ( !empty($_POST[$options['user_attribute']['user_attribute'][$i]['name']]) && (!is_admin() && empty($options['user_attribute']['user_attribute'][$i]['admin'])) || (is_admin() && !empty($options['user_attribute']['user_attribute'][$i]['admin'])) ) :
				switch($options['user_attribute']['user_attribute'][$i]['condition']) :
					case 'numeric':
						if(preg_match('/[^0-9]+/', $_POST[$options['user_attribute']['user_attribute'][$i]['name']]))
							$errors->add( $options['user_attribute']['user_attribute'][$i]['name'], sprintf(__( '<strong>ERROR</strong>: Use only numerics in %s.', 'frontend-user-admin'), $options['user_attribute']['user_attribute'][$i]['label']), 'frontend-user-admin' );
						break;
					case 'alphabet':
						if(preg_match('/[^a-zA-Z]+/', $_POST[$options['user_attribute']['user_attribute'][$i]['name']]))
							$errors->add( $options['user_attribute']['user_attribute'][$i]['name'], sprintf(__( '<strong>ERROR</strong>: Use only alphabets in %s.', 'frontend-user-admin'), $options['user_attribute']['user_attribute'][$i]['label']), 'frontend-user-admin' );
						break;
					case 'alphanumeric':
						if(preg_match('/[^0-9a-zA-Z]+/', $_POST[$options['user_attribute']['user_attribute'][$i]['name']]))
							$errors->add( $options['user_attribute']['user_attribute'][$i]['name'], sprintf(__( '<strong>ERROR</strong>: Use only alphanumerics in %s.', 'frontend-user-admin'), $options['user_attribute']['user_attribute'][$i]['label']), 'frontend-user-admin' );
						break;
					case 'half-width':
						if(preg_match('/[^\x20-\x7E]+/', $_POST[$options['user_attribute']['user_attribute'][$i]['name']]))
							$errors->add( $options['user_attribute']['user_attribute'][$i]['name'], sprintf(__( '<strong>ERROR</strong>: Use only half-width letters in %s.', 'frontend-user-admin'), $options['user_attribute']['user_attribute'][$i]['label']), 'frontend-user-admin' );
						break;
					case 'hiragana':
						if(!preg_match('/\A(?:\xE3\x81[\x81-\xBF]|\xE3\x82[\x80-\x9F])+\z/', $_POST[$options['user_attribute']['user_attribute'][$i]['name']]))
							$errors->add( $options['user_attribute']['user_attribute'][$i]['name'], sprintf(__( '<strong>ERROR</strong>: Use only Hiragana in %s.', 'frontend-user-admin'), $options['user_attribute']['user_attribute'][$i]['label']), 'frontend-user-admin' );
						break;
					case 'katakana':
						if(!preg_match('/\A(?:\xE3\x82[\xA1-\xBF]|\xE3\x83[\x80-\xBE])+\z/', $_POST[$options['user_attribute']['user_attribute'][$i]['name']]))
							$errors->add( $options['user_attribute']['user_attribute'][$i]['name'], sprintf(__( '<strong>ERROR</strong>: Use only Katakana in %s.', 'frontend-user-admin'), $options['user_attribute']['user_attribute'][$i]['label']), 'frontend-user-admin' );
						break;
					case 'email':
						if( !is_email($_POST[$options['user_attribute']['user_attribute'][$i]['name']]) )
							$errors->add( $options['user_attribute']['user_attribute'][$i]['name'], sprintf(__( '<strong>ERROR</strong>: %s isn&#8217;t correct.', 'frontend-user-admin'), $options['user_attribute']['user_attribute'][$i]['label']), 'frontend-user-admin' );
						break;
				endswitch;
			
				if ( isset($options['user_attribute']['user_attribute'][$i]['min_letters']) && $options['user_attribute']['user_attribute'][$i]['min_letters'] > 0 && ((function_exists('mb_strlen') && mb_strlen($_POST[$options['user_attribute']['user_attribute'][$i]['name']]) < $options['user_attribute']['user_attribute'][$i]['min_letters']) || (!function_exists('mb_strlen') && strlen($_POST[$options['user_attribute']['user_attribute'][$i]['name']]) < $options['user_attribute']['user_attribute'][$i]['min_letters'])))
					$errors->add( $options['user_attribute']['user_attribute'][$i]['name'], sprintf(__( '<strong>ERROR</strong>: Minimum number of letters in %1$s is %2$s.', 'frontend-user-admin'), $options['user_attribute']['user_attribute'][$i]['label'], $options['user_attribute']['user_attribute'][$i]['min_letters']), 'frontend-user-admin' );

				if ( isset($options['user_attribute']['user_attribute'][$i]['max_letters']) && $options['user_attribute']['user_attribute'][$i]['max_letters'] > 0 && ((function_exists('mb_strlen') && mb_strlen($_POST[$options['user_attribute']['user_attribute'][$i]['name']]) > $options['user_attribute']['user_attribute'][$i]['max_letters']) || (!function_exists('mb_strlen') && strlen($_POST[$options['user_attribute']['user_attribute'][$i]['name']]) > $options['user_attribute']['user_attribute'][$i]['max_letters'])))
					$errors->add( $options['user_attribute']['user_attribute'][$i]['name'], sprintf(__( '<strong>ERROR</strong>: Maximum number of letters in %1$s is %2$s.', 'frontend-user-admin'), $options['user_attribute']['user_attribute'][$i]['label'], $options['user_attribute']['user_attribute'][$i]['max_letters']), 'frontend-user-admin' );
				
				if ( preg_match('/[^\|]*\|c$/', $options['user_attribute']['user_attribute'][$i]['default']) && $_POST[$options['user_attribute']['user_attribute'][$i]['name']] ) :
					if ( rtrim($options['user_attribute']['user_attribute'][$i]['default'], '|c') != $_POST[$options['user_attribute']['user_attribute'][$i]['name']] )
					$errors->add( $options['user_attribute']['user_attribute'][$i]['name'], sprintf(__( '<strong>ERROR</strong>: %s does not match.', 'frontend-user-admin'), $options['user_attribute']['user_attribute'][$i]['label']), 'frontend-user-admin' );
				endif;
			
				if ( !empty($options['user_attribute']['user_attribute'][$i]['unique']) ) :
					if ( $user_id ) $where = ' user_id<>'.$user_id.' AND ';
					else $where = '';
					$query = $wpdb->prepare("SELECT * FROM $wpdb->usermeta WHERE $where meta_key = %s AND meta_value = %s", $options['user_attribute']['user_attribute'][$i]['name'], $_POST[$options['user_attribute']['user_attribute'][$i]['name']]);
					$result = $wpdb->get_results( $query, ARRAY_A );
					if ( count($result)>0 ) :
						$errors->add( $options['user_attribute']['user_attribute'][$i]['name'], sprintf(__( '<strong>ERROR</strong>: %s is already registered, please input again.', 'frontend-user-admin'), $options['user_attribute']['user_attribute'][$i]['label']), 'frontend-user-admin' );
					endif;
				endif;
			
				if ( !empty($options['user_attribute']['user_attribute'][$i]['composite_unique']) && !empty($_POST[$options['user_attribute']['user_attribute'][$i]['name']]) ) :
					$composite_unique[$options['user_attribute']['user_attribute'][$i]['name']] = $_POST[$options['user_attribute']['user_attribute'][$i]['name']];
				endif;
			endif;			
		endfor;
		
		if ( !empty($composite_unique) && $stage=='register' ) :
			$query = "SELECT * FROM $wpdb->users WHERE 1=1";

			foreach( $composite_unique as $key => $val ) :
				$query .= $wpdb->prepare(" AND `".$wpdb->base_prefix."users`.ID IN (SELECT `".$wpdb->base_prefix."usermeta`.user_id FROM `".$wpdb->base_prefix."usermeta` WHERE `".$wpdb->base_prefix."usermeta`.meta_key='".$key."' AND `".$wpdb->base_prefix."usermeta`.meta_value = %s)", trim($val));
			endforeach;
			$result = $wpdb->get_results( $query, ARRAY_A );

			if ( count($result)>0 ) :
				$errors->add( 'composite_unique', __( '<strong>ERROR</strong>: a same user seems to be already registered, please input again.', 'frontend-user-admin'), 'frontend-user-admin' );
			endif;		
		endif;
		
		$errors = apply_filters( 'fua_check', $errors);

		return $errors;
	}
		
	function edit_additional_user ($user_id) {
		global $wp_version;
		require_once(ABSPATH . 'wp-admin/includes/admin.php');
		$options = $this->get_frontend_user_admin_data();
		
		if ( strstr($_SERVER['REQUEST_URI'], 'wp-admin/admin.php') ) $admin = true;
		else $admin = false;

		if ( !empty($options['user_attribute']['user_attribute']) ) $count = count($options['user_attribute']['user_attribute']);
		else $count = 0;

		for($i=0;$i<$count;$i++) :
			if ( !$admin && !empty($options['user_attribute']['user_attribute'][$i]['admin']) ) continue;
			if ( !isset($_POST[$options['user_attribute']['user_attribute'][$i]['name']]) && (($options['user_attribute']['user_attribute'][$i]['type']=='display' && !$admin) || ($options['user_attribute']['user_attribute'][$i]['type2']=='display' && $admin)) ) continue;
			if ( $options['user_attribute']['user_attribute'][$i]['type']=='file' ) :
				if ( !empty($_POST[$options['user_attribute']['user_attribute'][$i]['name'].'_delete']) ) :
					wp_delete_attachment($_POST[$options['user_attribute']['user_attribute'][$i]['name']]);
					unset($_POST[$options['user_attribute']['user_attribute'][$i]['name']]);
				endif;

				if( isset($_FILES[$options['user_attribute']['user_attribute'][$i]['name']]['size']) && $_FILES[$options['user_attribute']['user_attribute'][$i]['name']]['size']>0 ) :
					if ( isset($_POST[$options['user_attribute']['user_attribute'][$i]['name']]) ) wp_delete_attachment($_POST[$options['user_attribute']['user_attribute'][$i]['name']]);
					add_filter('intermediate_image_sizes_advanced', array(&$this, 'frontend_user_admin_intermediate_image_sizes_advanced') );
					$_POST[$options['user_attribute']['user_attribute'][$i]['name']] = media_handle_upload($options['user_attribute']['user_attribute'][$i]['name'], '');
					if ( !is_numeric($_POST[$options['user_attribute']['user_attribute'][$i]['name']]) ) $_POST[$options['user_attribute']['user_attribute'][$i]['name']] = '';
				endif;
			endif;
			
			if( isset($_POST[$options['user_attribute']['user_attribute'][$i]['name']]) && $_POST[$options['user_attribute']['user_attribute'][$i]['name']]!='' ) :
				if ( version_compare( substr($wp_version, 0, 3), '3.0', '>=' ) ) :
					update_user_meta( $user_id, $options['user_attribute']['user_attribute'][$i]['name'], $_POST[$options['user_attribute']['user_attribute'][$i]['name']]);
				else :
					update_usermeta( $user_id, $options['user_attribute']['user_attribute'][$i]['name'], $_POST[$options['user_attribute']['user_attribute'][$i]['name']]);
				endif;
			else :
				if ( !empty($options['global_settings']['profile_'.$options['user_attribute']['user_attribute'][$i]['name']]) ) :
					if( empty($options['user_attribute']['user_attribute'][$i]['disabled']) ) :
						if ( version_compare( substr($wp_version, 0, 3), '3.0', '>=' ) ) :
							delete_user_meta( $user_id, $options['user_attribute']['user_attribute'][$i]['name'], '');
						else :
							delete_usermeta( $user_id, $options['user_attribute']['user_attribute'][$i]['name'], '');
						endif;
					endif;
				endif;
			endif;
			
			if ( !empty($options['user_attribute']['user_attribute'][$i]['publicity']) ) :
				if ( !empty($_POST['_publicity_'.$options['user_attribute']['user_attribute'][$i]['name']]) ) :
					update_user_meta( $user_id, '_publicity_'.$options['user_attribute']['user_attribute'][$i]['name'], 1 );
				else :
					delete_user_meta( $user_id, '_publicity_'.$options['user_attribute']['user_attribute'][$i]['name'] );
				endif;				
			endif;
		endfor;

		return $user_id;
	}
	
	function frontend_user_admin_intermediate_image_sizes_advanced($sizes) {
		foreach ( $sizes as $key => $val ) {
			$sizes[$key]['crop'] = true;	
		}
		return $sizes;
	}
	
	function frontend_user_admin_check_required($user) {
		$options = $this->get_frontend_user_admin_data();

		if ( !empty($options['user_attribute']['user_attribute']) ) $count_user_attribute = count($options['user_attribute']['user_attribute']);
		else $count_user_attribute = 0;
		
		for($i=0;$i<$count_user_attribute;$i++) :
			if( !empty($options['user_attribute']['user_attribute'][$i]['required']) && ( (is_admin() && !empty($options['user_attribute']['user_attribute'][$i]['admin'])) || ( !is_admin() && empty($options['user_attribute']['user_attribute'][$i]['admin']))) && (!isset($user->{$options['user_attribute']['user_attribute'][$i]['name']}) || $user->{$options['user_attribute']['user_attribute'][$i]['name']}=='') ) :
				return true;
			endif;
		endfor;


		return false;
	}
	
	function frontend_user_admin_action() {
		global $Ktai_Style, $net_shop_admin, $wp_version;

		if ( version_compare( substr($wp_version, 0, 3), '3.1', '<' ) )
			require_once( ABSPATH . WPINC . '/registration.php');
		require_once( ABSPATH . WPINC . '/pluggable.php');
		require_once( ABSPATH . '/wp-admin/includes/user.php');
		
		global $current_user, $wpdb;
		
		$_REQUEST = stripslashes_deep($_REQUEST);
		$_GET = stripslashes_deep($_GET);
		$_POST = stripslashes_deep($_POST);
		
		$action = isset($_REQUEST['action']) ? $_REQUEST['action'] : '';
		$this->errors = new WP_Error();
		
		$options = $this->get_frontend_user_admin_data();
				
		$http_post = ('POST' == $_SERVER['REQUEST_METHOD']);
		switch ($action) {
			case 'logout' :
				if ( function_exists('is_ktai') && is_ktai() )
					$Ktai_Style->admin->base->admin->logout();
				else
					wp_logout();

				if ( !empty($net_shop_admin) ) :
					@session_id();
					@session_start();
					if ( !empty($_SESSION['net-shop-admin']) ) unset( $_SESSION['net-shop-admin'] );
				endif;
				if ( !empty($options['global_settings']['after_logout_url']) ) :
					$redirect_to = $options['global_settings']['after_logout_url'];
				else :
					$redirect_to = $this->return_frontend_user_admin_login_url().'loggedout=true';
				endif;
				if ( isset( $_REQUEST['redirect_to'] ) ) $redirect_to = $_REQUEST['redirect_to'];
				wp_redirect($redirect_to);
				exit();
				break;
			case 'lostpassword' :
			case 'retrievepassword' :
				if ( !empty($options['global_settings']['disable_lostpassword']) ) :
					$redirect_to = get_option('home');
					if ( !empty($net_shop_admin) ) :
						$redirect_to = $net_shop_admin->net_shop_admin_add_sid($redirect_to);
					endif;
							
					wp_redirect($redirect_to);
					exit();
				endif;
				if ( $http_post ) {
					$this->errors = $this->retrieve_password();
					if ( !is_wp_error($this->errors) ) {
						$redirect_to = $this->return_frontend_user_admin_login_url().'checkemail=confirm';
						if ( !empty($net_shop_admin) ) :
							$redirect_to = $net_shop_admin->net_shop_admin_add_sid($redirect_to);
						endif;
							
						wp_redirect($redirect_to);
						exit();
					}
				}
				break;
			case 'resetpass' :
			case 'rp' :
				$this->errors = $this->reset_password(rawurldecode($_REQUEST['key']));
				if ( ! is_wp_error($this->errors) ) :
					$redirect_to = $this->return_frontend_user_admin_login_url().'checkemail=newpass';
				else :
					$redirect_to = $this->return_frontend_user_admin_login_url().'action=lostpassword&error=invalidkey';
				endif;

				if ( $net_shop_admin ) :
					$redirect_to = $net_shop_admin->net_shop_admin_add_sid($redirect_to);
				endif;
							
				wp_redirect($redirect_to);
				exit();
				break;
			case 'ec' :
				$this->errors = $this->email_confirmation(rawurldecode($_REQUEST['key']));
				if ( ! is_wp_error($this->errors) ) :
					$user_id = $this->errors;
					
					do_action( 'fua_ec', $user_id );
				
					if ( !empty($options['global_settings']['approval_registration']) )
						$redirect_to = $this->return_frontend_user_admin_login_url().'checkemail=approval';
					else
						$redirect_to = $this->return_frontend_user_admin_login_url().'checkemail=registered';
				else :
					$redirect_to = $options['global_settings']['login_url'];
				endif;

				if ( $net_shop_admin ) :
					$redirect_to = $net_shop_admin->net_shop_admin_add_sid($redirect_to);
				endif;
							
				wp_redirect($redirect_to);
				exit();
				break;
			case 'ecf' :
				$email = empty($_POST['email']) ? '' : $_POST['email'];
				
				if ( !is_email($email) || email_exists($email) ) :
					$redirect_to = $this->return_frontend_user_admin_login_url().'action=register&invalidemail=1';
				else :
					$this->wp_email_confirmation_first_mail($email);
					$redirect_to = $this->return_frontend_user_admin_login_url().'action=register&checkemail=1';
				endif;
				
				wp_redirect($redirect_to);
				exit();
				break;				
			case 'register' :
			case 'confirmation' :
				if ( !get_option('users_can_register') && empty($options['global_settings']['users_can_register']) ) {
					wp_redirect($this->return_frontend_user_admin_login_url().'registration=disabled');
					exit();
				}
				
				if ( !empty($options['global_settings']['email_confirmation_parallel']) && !empty($_REQUEST['key']) ) :
					$tmp = $this->fua_decrypt($_REQUEST['key']);
					list($time, $user_email) = explode(':', $tmp);
					$_POST = get_transient( $user_email );
					if ( empty($_POST) ) :
						$redirect_to = $this->return_frontend_user_admin_login_url()."checkemail=invalidkey";
						wp_redirect($redirect_to);
						exit();
					else :
						delete_transient( $user_email );
						$this->validkey = 1;
						unset($_POST['wp-submit-confirmation'], $_POST['wp-submit-after-confirmation']);
						$http_post = true;
					endif;
				endif;

				$user_login = '';
				$user_email = '';
				if ( $http_post ) {
					if ( version_compare( substr($wp_version, 0, 3), '3.1', '<' ) )
						require_once( ABSPATH . WPINC . '/registration.php');
						
					if ( function_exists('is_ktai') && is_ktai() ) :
						$_POST = $this->decode_from_ktai_deep($_POST);
					endif;

					$user_login = empty($_POST['user_login']) ? '' : $_POST['user_login'];
					$user_email = empty($_POST['user_email']) ? '' : $_POST['user_email'];
					$_POST['email'] = empty($_POST['user_email']) ? '' : $_POST['user_email'];
					$_POST['url'] = empty($_POST['user_url']) ? '' : $_POST['user_url'];

					$this->errors = $this->register_new_user($user_login, $user_email);

					if ( !empty($options['recaptcha_options']['site_key']) && !empty($options['recaptcha_options']['registration']) && empty($this->validkey) && (empty($options['global_settings']['confirmation_screen']) || (!empty($options['global_settings']['confirmation_screen']) && !empty($_POST['wp-submit-confirmation']))) ) :
						if ( empty($_POST['g-recaptcha-response']) ) :
							if ( !is_wp_error($this->errors) ) $this->errors = new WP_Error();
							$this->errors->add('recaptcha', __('<strong>ERROR</strong>: reCAPTCHA failed. Please try again.', 'frontend-user-admin'));
						else :
							$response = file_get_contents('https://www.google.com/recaptcha/api/siteverify?secret='.$options['recaptcha_options']['secret_key'].'&response='.$_POST['g-recaptcha-response']);
							$response = json_encode($response);
							if( $response->success == 'false' ) :
								if ( !is_wp_error($this->errors) ) $this->errors = new WP_Error();
								$this->errors->add('recaptcha', __('<strong>ERROR</strong>: reCAPTCHA failed. Please try again.', 'frontend-user-admin'));
							endif;
						endif;
					endif;
					
					do_action( 'fua_before_register', $this->errors );
						
					if ( !is_wp_error($this->errors) ) {
						$user_id = $this->errors;
						if( $action == 'register' && empty($_POST['wp-submit-confirmation']) ) {
							$this->errors = $this->edit_user($this->errors);

							if ( function_exists('is_multisite') && is_multisite() && !empty($options['global_settings']['all_site_registration']) ) :
								$query = "SELECT blog_id FROM `" . $wpdb->blogs . "` ORDER BY `" . $wpdb->blogs . "`.blog_id ASC";
								$result = $wpdb->get_results($query, ARRAY_A);
								if ( !empty($result) && is_array($result) ) :
									for($i=0;$i<count($result);$i++) :
										$blog_prefix = $wpdb->get_blog_prefix( $result[$i]['blog_id'] );
										$user = $wpdb->get_var( "SELECT user_id FROM `" . $wpdb->usermeta . "` WHERE user_id='$user_id' AND meta_key='{$blog_prefix}capabilities'" );
										if ( $user == false ) :
											add_user_to_blog( $result[$i]['blog_id'], $user_id, get_option('default_role') );
										endif;
									endfor;
								endif;
							endif;
							
							if ( !empty($options['phpcode_options']) ) :
								eval($options['phpcode_options']);
							endif;
							
							do_action( 'fua_register', $user_id );
 
							if ( !empty($options['global_settings']['login_after_registration']) ) :
								if ( function_exists('is_ktai') && is_ktai() ) :
									if ( !empty($options['global_settings']['email_as_userlogin']) ) $user_login = $user_email;
									$_POST['log'] = $user_login;
									$_POST['pwd'] = $_POST['pass1'];
									$user = $Ktai_Style->admin->base->admin->signon();
									if ( !$Ktai_Style->admin->base->ktai->get('cookie_available') ) :
										$redirect_to = $Ktai_Style->admin->add_sid($options['global_settings']['login_url']);
									endif;
								else :
									$user = new WP_User($user_id);
									wp_set_auth_cookie($user->ID, false, false);
								endif;
								
								$after_login_url = !empty($options['global_settings']['after_login_url']) ? $options['global_settings']['after_login_url'] : '';
								if ( !empty($user->redirect_to) ) $after_login_url = $user->redirect_to;
								if ( !empty($after_login_url) ) :
									if ( !empty($options['global_settings']['after_login_url_exception']) && !empty($redirect_to) ) :
										$exception_url = explode("\n", $options['global_settings']['after_login_url_exception']);
										$exception_url = array_filter( $exception_url );
										$exception_url = array_unique(array_filter(array_map('trim', $exception_url)));
										foreach( $exception_url as $url ) :
											if ( preg_match('/^'.preg_quote($url,'/').'/', $redirect_to) ) :
												$exception_flag = true;
												break;
											endif;
										endforeach;
									endif;
									if ( empty($options['global_settings']['after_login_url_exception']) || empty($exception_flag) ) :
										$redirect_to = preg_replace('/%user_login%/', $user->user_login, $after_login_url );
									endif;
								endif;
					
								if ( !empty($net_shop_admin) ) :
									@session_id();
									@session_start();
									if ( !empty($_SESSION['net-shop-admin']['redirect_to']) ) :
										if ( function_exists('is_ktai') && is_ktai() ) :
											if ( !$Ktai_Style->admin->base->ktai->get('cookie_available') ) 
												$redirect_to = $Ktai_Style->admin->add_sid($_SESSION['net-shop-admin']['redirect_to']);
											$redirect_to = $net_shop_admin->net_shop_admin_add_sid($_SESSION['net-shop-admin']['redirect_to']);
										else :
											$redirect_to = $_SESSION['net-shop-admin']['redirect_to'];
										endif;
									endif;
								endif;
							endif;

							if ( empty($redirect_to) ) :
								if(	!empty($options['global_settings']['email_confirmation']) )
									$redirect_to = $this->return_frontend_user_admin_login_url().'checkemail=confirmation';
								else if( !empty($options['global_settings']['approval_registration']) )
									$redirect_to = $this->return_frontend_user_admin_login_url().'checkemail=approval';
								else if( !empty($options['global_settings']["password_registration"]) )
									$redirect_to = $this->return_frontend_user_admin_login_url().'checkemail=registered_pass';
								else
									$redirect_to = $this->return_frontend_user_admin_login_url().'checkemail=registered';
							endif;
							
							if ( !empty($net_shop_admin) ) :
								$redirect_to = $net_shop_admin->net_shop_admin_add_sid($redirect_to);
							endif;
						
							wp_redirect($redirect_to);
							exit();
						} else {
							do_action( 'fua_confirmation' );
						}
					} else {
						$_REQUEST['action'] = 'register';
					}
				}
				break;
			case 'profile':
				if ( !empty($options['global_settings']['disable_profile']) ) :
					$redirect_to = get_option('home');
					if ( !empty($net_shop_admin) ) :
						$redirect_to = $net_shop_admin->net_shop_admin_add_sid($redirect_to);
					endif;
					wp_redirect($redirect_to);
					exit();
				endif;
				if ( !is_user_logged_in() ) :
					$redirect_to = $this->return_frontend_user_admin_login_url().'redirect_to='.rawurlencode($this->return_frontend_user_admin_login_url().'action=profile');
					if ( !empty($net_shop_admin) ) :
						$redirect_to = $net_shop_admin->net_shop_admin_add_sid($redirect_to);
					endif;

					wp_redirect($redirect_to);
					exit();
				endif;
				break;
			case 'update':
				$profileuser = $this->get_user_to_edit();

				$_POST['email'] = empty($_POST['user_email']) ? $profileuser->user_email : $_POST['user_email'];
				$_POST['url'] = empty($_POST['user_url']) ? '' : $_POST['user_url'];
				$_POST['old_user_email'] = $profileuser->user_email;
			
				$user_id = $current_user->ID;
				if ( !function_exists('is_ktai') || !is_ktai() )
					$this->check_login_referer('update-user_' . $user_id);

				//if ( !current_user_can('edit_user', $user_id) )
					//wp_die(__('You do not have permission to edit this user.', 'frontend-user-admin'));

				do_action('personal_options_update');

				if ( !empty($options['global_settings']['email_as_userlogin']) && empty($profileuser->fua_social_login) ) :
					$_POST['user_login'] = $_POST['email'];
				
					if( !empty($options['global_settings']['unique_registration']) && isset($_POST['user_login']) ) :
						$blog_id = get_current_blog_id();
						$_POST['user_login'] .= ':'.$blog_id;
					endif;
	
					if ( $profileuser->user_login != $_POST['user_login'] && email_exists( $_POST['email'] ) ) :
						$this->errors->add('email_exists', __('<strong>ERROR</strong>: This email is already registered, please choose another one.', 'frontend-user-admin'));
						$already_error = 1;
					endif;
				endif;
				
				if ( !empty($options['global_settings']['different_password']) && !empty($_POST['pass1']) && !empty($_POST['pass2']) ) :
					if ( wp_check_password(trim($_POST['pass1']), $profileuser->user_pass, $profileuser->ID) ) :
						$this->errors->add('different_password', __('<strong>ERROR</strong>: A new password must be different from old one.', 'frontend-user-admin'));
						$already_error = 1;
					endif;
				endif;

				if ( empty($already_error) ) :
					$this->errors = $this->edit_user($user_id, 'profile');
				endif;
				
				if ( !is_wp_error( $this->errors ) ) :

					if ( !empty($options['global_settings']['email_as_userlogin']) && empty($profileuser->fua_social_login) ) :
						$ID = $user_id;
						$wpdb->update( $wpdb->users, array('user_login' => $_POST['user_login']), compact( 'ID' ) );
					endif;

					if ( !empty($options['global_settings']['record_update_datetime']) ) update_user_meta( $user_id, 'update_datetime', date_i18n('U'));

					$this->wp_profile_update_mail($current_user->ID);
					
					if ( !empty($options['phpcode2_options']) ) :
						eval($options['phpcode2_options']);
					endif;

					do_action( 'fua_update', $user_id );

					$redirect_to = $this->return_frontend_user_admin_login_url()."action=profile&updated=true";
					//$redirect_to = add_query_arg('wp_http_referer', rawurlencode($wp_http_referer), $redirect_to);
					if ( function_exists('is_ktai') && is_ktai() ) :
						if ( !$Ktai_Style->admin->base->ktai->get('cookie_available') ) :
							$redirect_to = $Ktai_Style->admin->add_sid($redirect_to);
							
							if ( !empty($net_shop_admin) ) :
								$redirect_to = $net_shop_admin->net_shop_admin_add_sid($redirect_to);
							endif;
						endif;
					endif;
					wp_redirect($redirect_to);
					exit();
				endif;
				break;
			case 'withdrawal' :
				if ( $http_post ) :
					if ( is_user_logged_in() && !empty($options['global_settings']['use_withdrawal']) ) :

						if ( !function_exists('is_ktai') || !is_ktai() )
							$this->check_login_referer('delete-user_' . $current_user->ID);

						$this->wp_withdrawal_mail($current_user->ID);
						if ( !empty($options['global_settings']['soft_user_deletion']) ) :
							$ID = $current_user->ID;
							$user_login = $current_user->user_login;
							$user_email = $current_user->user_email;
							$wpdb->update( $wpdb->users, array('user_login' => 'deleted:'.$user_login, 'user_email' => 'deleted:'.$user_email), compact( 'ID' ) );
						else :
							if ( function_exists('is_multisite') && is_multisite() ) :
								if ( !empty($options['global_settings']['user_complete_deletion']) ) :
									wp_delete_user($current_user->ID);
								else :
									require_once(ABSPATH . 'wp-admin/includes/ms.php');
									wpmu_delete_user($current_user->ID);
								endif;
							else :
								wp_delete_user($current_user->ID);
							endif;
						endif;
						
						do_action( 'fua_delete', $current_user->ID );
						
						if ( !empty( $_REQUEST['redirect_to'] ) ) $redirect_to = $_REQUEST['redirect_to'];
						else $redirect_to = $options['global_settings']['login_url'].'?withdrawal=completed';
					
						wp_redirect($redirect_to);
						exit();
					endif;
				endif;
				if ( !is_user_logged_in() ) :
					$redirect_to = $this->return_frontend_user_admin_login_url().'redirect_to='.rawurlencode($this->return_frontend_user_admin_login_url().'action=withdrawal');
					if ( !empty($net_shop_admin) ) :
						$redirect_to = $net_shop_admin->net_shop_admin_add_sid($redirect_to);
					endif;

					wp_redirect($redirect_to);
					exit();
				endif;
				break;
			case 'history' :
				if ( !is_user_logged_in() ) :
					$redirect_to = $this->return_frontend_user_admin_login_url().'redirect_to='.rawurlencode($this->return_frontend_user_admin_login_url().'action=history');
					if ( !empty($net_shop_admin) ) :
						$redirect_to = $net_shop_admin->net_shop_admin_add_sid($redirect_to);
					endif;

					wp_redirect($redirect_to);
					exit();
				endif;
				break;
			case 'affiliate' :
				if ( !is_user_logged_in() ) :
					$redirect_to = $this->return_frontend_user_admin_login_url().'&redirect_to='.rawurlencode($this->return_frontend_user_admin_login_url().'action=affiliate');
					if ( !empty($net_shop_admin) ) :
						$redirect_to = $net_shop_admin->net_shop_admin_add_sid($redirect_to);
					endif;

					wp_redirect($redirect_to);
					exit();
				endif;
				break;
			case 'wishlist' :
				if ( !is_user_logged_in() ) :
					$redirect_to = $this->return_frontend_user_admin_login_url().'&redirect_to='.rawurlencode($this->return_frontend_user_admin_login_url().'action=wishlist');
					if ( !empty($net_shop_admin) ) :
						$redirect_to = $net_shop_admin->net_shop_admin_add_sid($redirect_to);
					endif;

					wp_redirect($redirect_to);
					exit();
				endif;
				break;
			case 'login' :
			default:
				if ( !empty($_REQUEST['redirect_to']) )
					$redirect_to = $_REQUEST['redirect_to'];
			
				if ( !empty($_POST['log']) ) :
					wp_logout();
					wp_set_current_user(0);
				endif;
					
				if ( !empty($_POST['log']) && !empty($options['global_settings']['use_common_password']) && !empty($options['global_settings']['common_password']) ) :
					$_POST['pwd'] = $options['global_settings']['common_password'];
				endif;
				
				if( !empty($options['global_settings']['unique_registration']) && isset($_POST['log']) ) :
					$blog_id = get_current_blog_id();
					$_POST['log'] .= ':'.$blog_id;
				endif;
				do_action('fua_login_before');
					
				if ( function_exists('is_ktai') && is_ktai() ) :
					$user = $Ktai_Style->admin->base->admin->signon();
				else :
					$user = wp_signon();
				endif;
				$user = apply_filters( 'fua_login', $user );

				if ( isset($options['global_settings']['password_lock_miss_times']) && is_numeric($options['global_settings']['password_lock_miss_times']) && $options['global_settings']['password_lock_miss_times']>0 && !empty($_POST['log']) ) :
					$tmp_user = get_user_by('login', $_POST['log']);
					$password_lock_miss_times = (int)get_user_meta( $tmp_user->ID, 'password_lock_miss_times', true)+1;
					if ( is_wp_error($user) ) update_user_meta( $tmp_user->ID, 'password_lock_miss_times', $password_lock_miss_times);
					if ( is_numeric($options['global_settings']['password_lock_retrieval_time']) && $password_lock_miss_times == $options['global_settings']['password_lock_miss_times'] ) :
						update_user_meta( $tmp_user->ID, 'password_lock_time', date_i18n('U'));
					endif;
					if ( $password_lock_miss_times >= $options['global_settings']['password_lock_miss_times'] ) :
						if ( is_numeric($options['global_settings']['password_lock_retrieval_time']) && ((date_i18n('U')-get_user_meta($tmp_user->ID, 'password_lock_time', true))/60)>$options['global_settings']['password_lock_retrieval_time'] ) :
							delete_user_meta( $tmp_user->ID, 'password_lock_time');
							update_user_meta( $tmp_user->ID, 'password_lock_miss_times', 1);
						else :
							wp_logout();
							wp_set_current_user(0);
							$user = new WP_Error();
							if ( is_numeric($options['global_settings']['password_lock_retrieval_time']) ) :
								$user->add('password_lock', __('<strong>ERROR</strong>: The username is locked. You have to wait for a while.', 'frontend-user-admin'));
							elseif ( is_numeric($options['global_settings']['password_lock_miss_email']) ) :
								$_POST['user_login'] = $_POST['log'];
								$this->retrieve_password();
								$user->add('password_lock', __('<strong>ERROR</strong>: The username is locked. E-mail to reset password has been sent to your e-mail address.', 'frontend-user-admin'));				
							else :
								$user->add('password_lock', __('<strong>ERROR</strong>: The username is locked. Please contact the administrator.', 'frontend-user-admin'));
							endif;
						endif;
					endif;
				endif;
				
				if ( !empty($options['recaptcha_options']['site_key']) && !empty($options['recaptcha_options']['login']) && !empty($_POST['log'])  ) :
					if ( empty($_POST['g-recaptcha-response']) ) :
						wp_logout();
						wp_set_current_user(0);
						$user = new WP_Error();
						$user->add('recaptcha', __('<strong>ERROR</strong>: reCAPTCHA failed. Please try again.', 'frontend-user-admin'));
					else :
						$response = file_get_contents('https://www.google.com/recaptcha/api/siteverify?secret='.$options['recaptcha_options']['secret_key'].'&response='.$_POST['g-recaptcha-response']);
						$response = json_encode($response);
						if( $response->success == 'false' ) :
							wp_logout();
							wp_set_current_user(0);
							$user = new WP_Error();
							$user->add('recaptcha', __('<strong>ERROR</strong>: reCAPTCHA failed. Please try again.', 'frontend-user-admin'));
						endif;
					endif;
				endif;

				if ( !is_wp_error($user) && !empty($user->ID) ) :
					$user_id = $user->ID;
			
					if ( isset($options['global_settings']['password_lock_miss_times']) && is_numeric($options['global_settings']['password_lock_miss_times']) ) delete_user_meta( $user->ID, 'password_lock_miss_times');
					if ( !empty($options['global_settings']['logout_time']) ) update_user_meta( $user->ID, 'login_datetime', date_i18n('U'));

					if ( !empty($options['phpcode3_options']) ) :
						eval($options['phpcode3_options']);
					endif;
	
					$after_login_url = !empty($options['global_settings']['after_login_url']) ? $options['global_settings']['after_login_url'] : '';
					if ( !empty($user->redirect_to) ) $after_login_url = $user->redirect_to;
					if ( !empty($after_login_url) ) :
						if ( !empty($options['global_settings']['after_login_url_exception']) && !empty($redirect_to) ) :
							$exception_url = explode("\n", $options['global_settings']['after_login_url_exception']);
							$exception_url = array_filter( $exception_url );
							$exception_url = array_unique(array_filter(array_map('trim', $exception_url)));
							foreach( $exception_url as $url ) :
								if ( preg_match('/^'.preg_quote($url,'/').'/', $redirect_to) ) :
									$exception_flag = true;
									break;
								endif;
							endforeach;
						endif;
						if ( empty($options['global_settings']['after_login_url_exception']) || empty($exception_flag) ) :
							$redirect_to = preg_replace('/%user_login%/', $user->user_login, $after_login_url );
						endif;
					endif;

					if ( function_exists('is_ktai') && is_ktai() ) :
						if ( empty($redirect_to) ) :
							if ( !$Ktai_Style->admin->base->ktai->get('cookie_available') ) :
								$redirect_to = $Ktai_Style->admin->add_sid($options['global_settings']['login_url']);
							else :
								$redirect_to = $options['global_settings']['login_url'];
							endif;
						else :
							$redirect_to = $Ktai_Style->admin->add_sid($redirect_to);
						endif;
		
						if ( !empty($net_shop_admin) ) :
							$redirect_to = $net_shop_admin->net_shop_admin_add_sid($redirect_to);
						endif;

						do_action('wp_login', $user->user_login);
						wp_redirect($redirect_to);
						exit();
					endif;

					wp_set_current_user($user->ID);
					$this->frontend_user_admin_user_log();
				
					if( !empty($redirect_to) ) :
						//wp_safe_redirect($redirect_to);
						do_action('wp_login', $user->user_login);
						wp_redirect($redirect_to);
						exit();
					endif;
					break;
				endif;
			
				$this->errors = $user;

				if ( !empty($options['global_settings']['disable_lostpassword']) && !empty($this->errors->errors['invalid_username']) ) :
					$this->errors->errors['invalid_username'] = array(__('<strong>ERROR</strong>: Invalid username.', 'frontend-user-admin'));
				endif;
				
				// Clear errors if loggedout is set.
				if ( !empty($_REQUEST['loggedout']) )
					$this->errors = new WP_Error();

				break;
		}
	}
	
	function frontend_user_admin_get_the_excerpt($excerpt) {
		$options = $this->get_frontend_user_admin_data();
		
		if ( !empty($options['excerpt_options']) ) :
			$action = isset($_REQUEST['action']) ? $_REQUEST['action'] : '';
			if ( !$action ) $action = isset($_GET['action']) ? $_GET['action'] : '';

			switch ($action) :
				case 'lostpassword' :
					$excerpt = !empty($options['excerpt_options']['lostpassword']) ? $options['excerpt_options']['lostpassword'] : $excerpt;
					break;
				case 'register' :
					$excerpt = !empty($options['excerpt_options']['register']) ? $options['excerpt_options']['register'] : $excerpt;
					break;					
				case 'confirmation' :
					$excerpt = !empty($options['excerpt_options']['confirmation']) ? $options['excerpt_options']['confirmation'] : $excerpt;
					break;					
				case 'profile' :
					$excerpt = !empty($options['excerpt_options']['profile']) ? $options['excerpt_options']['profile'] : $excerpt;
					break;					
				case 'history' :
					$excerpt = !empty($options['excerpt_options']['history']) ? $options['excerpt_options']['history'] : $excerpt;
					break;					
				case 'affiliate' :
					$excerpt = !empty($options['excerpt_options']['affiliate']) ? $options['excerpt_options']['affiliate'] : $excerpt;
					break;					
				case 'wishlist' :
					$excerpt = !empty($options['excerpt_options']['wishlist']) ? $options['excerpt_options']['wishlist'] : $excerpt;
					break;					
				case 'withdrawal' :
					if ( !empty($options['global_settings']['use_withdrawal']) ) :
						$excerpt = !empty($options['excerpt_options']['withdrawal']) ? $options['excerpt_options']['withdrawal'] : $excerpt;
						break;					
					endif;
				case 'login' :
				default:
					if ( is_user_logged_in() ) :
						$excerpt = !empty($options['excerpt_options']['mypage']) ? $options['excerpt_options']['mypage'] : $excerpt;
					else :
						$excerpt = !empty($options['excerpt_options']['login']) ? $options['excerpt_options']['login'] : $excerpt;
					endif;
					break;			
			endswitch;
		endif;

		if ( empty($excerpt) ) 
			$this->is_excerpt = true;
			
		return $excerpt;
	}
	
	function frontend_user_admin_the_content($content) {
		global $current_user, $post, $net_shop_admin, $Ktai_Style, $is_iphone, $redirect_to;
		
		$output = '';
				
		if ( $this->is_excerpt ) :
			$this->is_excerpt = false;
			return $post->post_excerpt ? $post->post_excerpt : strip_shortcodes($content);
		endif;

		$action = isset($_REQUEST['action']) ? $_REQUEST['action'] : '';
		if ( !$action ) $action = isset($_GET['action']) ? $_GET['action'] : '';
		//Set a cookie now to see if they are supported by the browser.
		//setcookie(TEST_COOKIE, 'WP Cookie check', 0, COOKIEPATH, COOKIE_DOMAIN);
		//if ( SITECOOKIEPATH != COOKIEPATH )
			//setcookie(TEST_COOKIE, 'WP Cookie check', 0, SITECOOKIEPATH, COOKIE_DOMAIN);
			
		$options = $this->get_frontend_user_admin_data();

		if ( !empty($net_shop_admin) ) :
			if ( !session_id() ) @session_start();
			if ( !empty($_SESSION['net-shop-admin']['redirect_to']) )
				$redirect_to = $_SESSION['net-shop-admin']['redirect_to'];
		endif;

		if( is_user_logged_in() && !empty($options['global_settings']['menu_during_login']) ) :
			if ( !empty($options['global_settings']['howdy_message']) ) :
				$output .= '<p>'.sprintf(__('Howdy, %1$s!', 'frontend-user-admin'), $current_user->display_name).'</p>'."\n";
			endif;
			$output .= '<ul class="fua_menu_list">'."\n";
			if( !empty($options['widget_menu']) ):
				for ( $i=0; $i<count($options['widget_menu']); $i++ ) :
					if ( !isset($options['widget_menu'][$i]['widget_menu_user_level']) || (isset($options['widget_menu'][$i]['widget_menu_user_level']) && (int)$current_user->user_level >= (int)$options['widget_menu'][$i]['widget_menu_user_level']) ) :
						$output .= '<li class="widget_menu'.$i.'"><a href="'.preg_replace_callback('/%([a-zA-Z0-9_]+)%/', function ($m) use ($current_user) { return $current_user->{$m[1]}; }, $options['widget_menu'][$i]['widget_menu_url']).'"';
						if ( !empty($options['widget_menu'][$i]['widget_menu_blank']) ) $output .= ' target="_blank"';
						$output .= '>'.esc_attr($options['widget_menu'][$i]['widget_menu_label']).'</a></li>'."\n";
					endif;
				endfor;
			endif;
			if ( empty($options['global_settings']['disable_profile']) ) :
				$output .= '<li class="profile"><a href="';
				if ( !empty($options['global_settings']['login_url']) ) $output .= $this->return_frontend_user_admin_login_url();
				$output .= 'action=profile">'.__('Profile', 'frontend-user-admin').'</a></li>'."\n";
			endif;
			$output .= '<li class="logout"><a href="';
			if ( !empty($options['global_settings']['login_url']) ) $output .= $this->return_frontend_user_admin_login_url();
			$output .= 'action=logout">'.__('Log Out', 'frontend-user-admin').'</a></li>'."\n";
			$output .= '</ul>';
		endif;

		if ( !empty($options['global_settings']['show_the_content_directly']) && is_user_logged_in() && $action != 'profile' && $action != 'update' && $action != 'withdrawal' && $action != 'history' && $action != 'affiliate' && $action != 'wishlist' )
			return $output . $content;
		
		switch ($action) :
			case 'lostpassword' :
			case 'retrievepassword' :
				if ( $is_iphone && !empty($options['global_settings']['use_smartphone_code']) ) :
					if( !empty($options['output_options']['output_sp_lostpassword']) ) :
						$output .= $this->EvalBuffer($options['output_options']['output_sp_lostpassword']);
						return $output;
					endif;
				elseif ( function_exists('is_ktai') && is_ktai() ) :
					if( !empty($options['output_options']['output_mobile_lostpassword']) ) :
						$output .= $this->EvalBuffer($options['output_options']['output_mobile_lostpassword']);
						return $output;
					endif;
				else :
					if( !empty($options['output_options']['output_lostpassword']) ) :
						$output .= $this->EvalBuffer($options['output_options']['output_lostpassword']);
						return $output;
					endif;
				endif;
				break;
			case 'register' :
				if ( $is_iphone && !empty($options['global_settings']['use_smartphone_code']) ) :
					if( !empty($options['output_options']['output_sp_register']) ) :
						$output .= $this->EvalBuffer($options['output_options']['output_sp_register']);
						return $output;
					endif;
				elseif ( function_exists('is_ktai') && is_ktai() ) :
					if( !empty($options['output_options']['output_mobile_register']) ) :
						$output .= $this->EvalBuffer($options['output_options']['output_mobile_register']);
						return $output;
					endif;
				else :
					if( !empty($options['output_options']['output_register']) ) :
						$output .= $this->EvalBuffer($options['output_options']['output_register']);
						return $output;
					endif;
				endif;
				break;
			case 'confirmation' :
				if ( $is_iphone && !empty($options['global_settings']['use_smartphone_code']) ) :
					if( !empty($options['output_options']['output_sp_confirmation']) ) :
						$output .= $this->EvalBuffer($options['output_options']['output_sp_confirmation']);
						return $output;
					endif;
				elseif ( function_exists('is_ktai') && is_ktai() ) :
					if( !empty($options['output_options']['output_mobile_confirmation']) ) :
						$output .= $this->EvalBuffer($options['output_options']['output_mobile_confirmation']);
						return $output;
					endif;
				else :
					if( !empty($options['output_options']['output_confirmation']) ) :
						$output .= $this->EvalBuffer($options['output_options']['output_confirmation']);
						return $output;
					endif;
				endif;
				break;
			case 'update':
			case 'profile' :
				if ( $is_iphone && !empty($options['global_settings']['use_smartphone_code']) ) :
					if( !empty($options['output_options']['output_sp_profile']) ) :
						$output .= $this->EvalBuffer($options['output_options']['output_sp_profile']);
						return $output;
					endif;
				elseif ( function_exists('is_ktai') && is_ktai() ) :
					if( !empty($options['output_options']['output_mobile_profile']) ) :
						$output .= $this->EvalBuffer($options['output_options']['output_mobile_profile']);
						return $output;
					endif;
				else :
					if( !empty($options['output_options']['output_profile']) ) :
						$output .= $this->EvalBuffer($options['output_options']['output_profile']);
						return $output;
					endif;
				endif;
				break;
			case 'withdrawal':
				if ( $is_iphone && !empty($options['global_settings']['use_smartphone_code']) ) :
					if( !empty($options['output_options']['output_sp_withdrawal']) ) :
						$output .= $this->EvalBuffer($options['output_options']['output_sp_withdrawal']);
						return $output;
					endif;
				elseif ( function_exists('is_ktai') && is_ktai() ) :
					if( !empty($options['output_options']['output_mobile_withdrawal']) ) :
						$output .= $this->EvalBuffer($options['output_options']['output_mobile_withdrawal']);
						return $output;
					endif;
				else :
					if( !empty($options['output_options']['output_withdrawal']) ) :
						$output .= $this->EvalBuffer($options['output_options']['output_withdrawal']);
						return $output;
					endif;
				endif;
				break;
			case 'history' :
				global $net_shop_admin;
				if ( $net_shop_admin && is_user_logged_in() ) :
					ob_start();
					$net_shop_admin->net_shop_admin_buying_history();
					$output .= ob_get_contents();
					ob_end_clean();
					return $output;
				endif;
				break;
			case 'affiliate' :
				global $net_shop_admin;
				if ( $net_shop_admin && is_user_logged_in() ) :
					ob_start();
					$net_shop_admin->net_shop_admin_affiliate();
					$output .= ob_get_contents();
					ob_end_clean();
					return $output;
				endif;
				break;
			case 'wishlist' :
				global $net_shop_admin;
				if ( $net_shop_admin && is_user_logged_in() ) :
					ob_start();
					$net_shop_admin->net_shop_admin_wishlist();
					$output .= ob_get_contents();
					ob_end_clean();
					return $output;
				endif;
				break;
			case 'login' :
			default:
				if ( $is_iphone && !empty($options['global_settings']['use_smartphone_code']) ) :
					if( !empty($options['output_options']['output_sp_login']) ) :
						$output .= $this->EvalBuffer($options['output_options']['output_sp_login']);
						return $output;
					endif;
				elseif ( function_exists('is_ktai') && is_ktai() ) :
					if( !empty($options['output_options']['output_mobile_login']) ) :
						$output .= $this->EvalBuffer($options['output_options']['output_mobile_login']);
						return $output;
					endif;
				else :
					if( !empty($options['output_options']['output_login']) ) :
						$output .= $this->EvalBuffer($options['output_options']['output_login']);
						return $output;
					endif;
				endif;
				break;
		endswitch;

		ob_start();

		$http_post = ('POST' == $_SERVER['REQUEST_METHOD']);
		switch ($action) {
			case 'lostpassword' :
			case 'retrievepassword' :
				if ( $is_iphone && !empty($options['global_settings']['use_smartphone_code']) ) :
					include(WP_PLUGIN_DIR.'/frontend-user-admin/steps/step-sp_lostpassword.php');
				elseif ( function_exists('is_ktai') && is_ktai() ) :
					include(WP_PLUGIN_DIR.'/frontend-user-admin/steps/step-mobile_lostpassword.php');
				else :
					include(WP_PLUGIN_DIR.'/frontend-user-admin/steps/step-lostpassword.php');
				endif;
				break;
			case 'register' :
				if ( $is_iphone && !empty($options['global_settings']['use_smartphone_code']) ) :
					include(WP_PLUGIN_DIR.'/frontend-user-admin/steps/step-sp_register.php');
				elseif ( function_exists('is_ktai') && is_ktai() ) :
					include(WP_PLUGIN_DIR.'/frontend-user-admin/steps/step-mobile_register.php');
				else :
					include(WP_PLUGIN_DIR.'/frontend-user-admin/steps/step-register.php');
				endif;
				break;
			case 'confirmation' :
				if ( $is_iphone && !empty($options['global_settings']['use_smartphone_code']) ) :
					include(WP_PLUGIN_DIR.'/frontend-user-admin/steps/step-sp_confirmation.php');
				elseif ( function_exists('is_ktai') && is_ktai() ) :
					include(WP_PLUGIN_DIR.'/frontend-user-admin/steps/step-mobile_confirmation.php');
				else :
					include(WP_PLUGIN_DIR.'/frontend-user-admin/steps/step-confirmation.php');
				endif;
				break;
			case 'update':
			case 'profile' :
				if ( $is_iphone && !empty($options['global_settings']['use_smartphone_code']) ) :
					include(WP_PLUGIN_DIR.'/frontend-user-admin/steps/step-sp_profile.php');
				elseif ( function_exists('is_ktai') && is_ktai() ) :
					include(WP_PLUGIN_DIR.'/frontend-user-admin/steps/step-mobile_profile.php');
				else :
					include(WP_PLUGIN_DIR.'/frontend-user-admin/steps/step-profile.php');
				endif;
				break;
			case 'withdrawal' :
				if ( $is_iphone && !empty($options['global_settings']['use_smartphone_code']) ) :
					include(WP_PLUGIN_DIR.'/frontend-user-admin/steps/step-sp_withdrawal.php');
				elseif ( function_exists('is_ktai') && is_ktai() ) :
					include(WP_PLUGIN_DIR.'/frontend-user-admin/steps/step-mobile_withdrawal.php');
				else :
					include(WP_PLUGIN_DIR.'/frontend-user-admin/steps/step-withdrawal.php');
				endif;
				break;
			case 'history' :
				global $net_shop_admin;
				if ( $net_shop_admin && is_user_logged_in() ) :
					$net_shop_admin->net_shop_admin_buying_history();
				endif;
				break;
			case 'affiliate' :
				global $net_shop_admin;
				if ( $net_shop_admin && is_user_logged_in() ) :
					$net_shop_admin->net_shop_admin_affiliate();
				endif;
				break;
			case 'wishlist' :
				global $net_shop_admin;
				if ( $net_shop_admin && is_user_logged_in() ) :
					$net_shop_admin->net_shop_admin_wishlist();
				endif;
				break;
			case 'login' :
			default:
				if ( $is_iphone && !empty($options['global_settings']['use_smartphone_code']) ) :
					include(WP_PLUGIN_DIR.'/frontend-user-admin/steps/step-sp_login.php');
				elseif ( function_exists('is_ktai') && is_ktai() ) :
					include(WP_PLUGIN_DIR.'/frontend-user-admin/steps/step-mobile_login.php');
				else :
					include(WP_PLUGIN_DIR.'/frontend-user-admin/steps/step-login.php');
				endif;
				break;
		}

		$output .= ob_get_contents();
		ob_end_clean();
		
		return $output;
	}

	function add_frontend_user_admin_admin() {
		global $current_user;
		$options = $this->get_frontend_user_admin_data();

		if ( !defined('WP_PLUGIN_DIR') )
			$plugin_dir = str_replace( ABSPATH, '', dirname(__FILE__) );
		else
			$plugin_dir = dirname( plugin_basename(__FILE__) );

		if ( !empty($options['global_settings']['admin_demo']) && ((!empty($options['global_settings']['plugin_role']) && !current_user_can('edit_frontend_user_admin')) || (empty($options['global_settings']['plugin_role']) && $current_user->user_level<10)) )
			$message = __('Currently this is Admin Demo Mode. You can not change any settings.', 'frontend-user-admin');
		if ( !empty($_GET['message']) && $_GET['message'] == 'registered' )
			$message = __('User registered.', 'frontend-user-admin');
		if ( !empty($_GET['message']) && $_GET['message'] == 'edited' )
			$message = __('User edited.', 'frontend-user-admin');
		if ( !empty($_GET['message']) && $_GET['message'] == 'updated' )
			$message = __('Options updated.', 'frontend-user-admin');
		if ( !empty($_GET['message']) && $_GET['message'] == 'imported' )
			$message = __('Options imported.', 'frontend-user-admin');
		if ( !empty($_GET['message']) && $_GET['message'] == 'failed' )
			$message = __('Action failed.', 'frontend-user-admin');
		if ( !empty($_GET['message']) && $_GET['message'] == 'reset' )
			$message = __('Options reset.', 'frontend-user-admin');
		if ( !empty($_GET['message']) && $_GET['message'] == 'deleted' )
			$message = __('Options deleted.', 'frontend-user-admin');
		if ( !empty($_GET['message']) && $_GET['message'] == 'mailerror' )
			$message = __('Please enter FROM, TO, subject, and body.', 'frontend-user-admin');
?>
<?php if ( !empty($message) ) : ?>
<div id="message" class="updated"><p><?php echo $message; ?></p></div>
<?php endif; ?>
<div class="wrap">
<div id="icon-plugins" class="icon32"><br/></div>
<h2><?php _e('Frontend User Admin', 'frontend-user-admin'); ?> - <?php
		if ( empty($_REQUEST['option']) ) $_REQUEST['option'] = '';
		switch ( $_REQUEST['option'] ) :
			case 'adduser':
				_e('Add User', 'frontend-user-admin');
				break;
			case 'edituser':
				_e('Edit User', 'frontend-user-admin');
				break;
			case 'mail':
				_e('User Mail', 'frontend-user-admin');
				break;
			case 'sendmail':
				_e('Send Mail', 'frontend-user-admin');
				break;
			case 'log':
				_e('User Log', 'frontend-user-admin');
				break;
			case 'logsummary':
				_e('User Log Summary', 'frontend-user-admin');
				break;
			case 'importuser':
				_e('Import User', 'frontend-user-admin');
				break;
			case 'settings':
				_e('Settings', 'frontend-user-admin');
				break;
			default:
				_e('User List', 'frontend-user-admin');
				break;
		endswitch;
?></h2>

<style type="text/css">
.js .meta-box-sortables .postbox .handlediv:before { font: normal 20px/1 'dashicons'; display: inline-block; padding: 8px 10px; -webkit-font-smoothing: antialiased; -moz-osx-font-smoothing: grayscale; text-decoration: none !important; }
.js .meta-box-sortables .postbox .handlediv:before { content: '\f142'; }
.js .meta-box-sortables .postbox.closed .handlediv:before { content: '\f140'; }
#poststuff h3 { font-size: 14px; line-height: 1.4; margin: 0; padding: 8px 12px; }
div.grippie {
background:#EEEEEE url(<?php echo '../' . PLUGINDIR . '/' . $plugin_dir . '/js/'; ?>grippie.png) no-repeat scroll center 2px;
border-color:#DDDDDD;
border-style:solid;
border-width:0pt 1px 1px;
cursor:s-resize;
height:9px;
overflow:hidden;
}
.resizable-textarea textarea {
display:block;
margin-bottom:0pt;
}
#pass-strength-result { opacity:1; }
fieldset { border: 1px solid #DDD; }
</style>
<script type="text/javascript">
	jQuery(document).ready(function() {
		jQuery('textarea:not(.processed)').TextAreaResizer();
	});
</script>

<?php
		switch ( $_REQUEST['option'] ) :
			case 'adduser':
				$this->frontend_user_admin_adduser();
				break;
			case 'edituser':
				$this->frontend_user_admin_edituser();
				break;
			case 'maillist':
			case 'sendmail':
			case 'readmail':
				$this->frontend_user_admin_mail();
				break;
			case 'log':
				$this->frontend_user_admin_log();
				break;
			case 'logsummary':
				$this->frontend_user_admin_logsummary();
				break;
			case 'importuser':
				$this->frontend_user_admin_importuser();
				break;
			case 'settings':
				$this->frontend_user_admin_settings();
				break;
			default:
				$this->frontend_user_admin_user_list();
				break;
		endswitch;
	}
	
	function frontend_user_admin_settings() {	
		global $current_user, $wp_version;
		$options = $this->get_frontend_user_admin_data();
		
		if ( is_string($options) ) $options = array();
?>

<style type="text/css">
.form-table textarea { width:100%; }
.form-table th, .form-table td { padding:5px; }
</style>

<div id="poststuff" class="meta-box-sortables" style="position: relative; margin-top:10px;">
<div class="postbox<?php if ( !isset($_GET["open"]) || (isset($_GET["open"]) && $_GET["open"] != 'global_settings') ) echo ' closed'; ?>">
<div class="handlediv" title="<?php _e('Click to toggle', 'frontend-user-admin'); ?>"><br /></div>
<h3><?php _e('Global Settings', 'frontend-user-admin'); ?></h3>
<div class="inside">
<form method="post" action="?page=frontend-user-admin/frontend-user-admin-settings.php">
<table class="form-table">
<tbody>
<tr><td>
<p><label for="login_url"><?php _e('Log In URL', 'frontend-user-admin'); ?></label>:<br />
<input type="text" name="login_url" id="login_url" value="<?php if( !empty($options['global_settings']['login_url']) ) echo esc_attr($options['global_settings']['login_url']); ?>" size="60" class="regular-text imedisabled" /><br />
<?php _e('You need to make a page which has this permalink.', 'frontend-user-admin'); ?></p>
</td></tr>
<tr><td>
<p><label for="after_login_url"><?php _e('After Log In URL', 'frontend-user-admin'); ?></label>:<br />
<input type="text" name="after_login_url" id="after_login_url" value="<?php if( !empty($options['global_settings']['after_login_url']) ) echo esc_attr($options['global_settings']['after_login_url']); ?>" size="60" class="regular-text imedisabled" /><br />
<?php _e('You can add %user_login% in the upper url. %user_login% will be converted into the user name.', 'frontend-user-admin'); ?></p>
</td></tr>
<tr><td>
<p><label for="after_logout_url"><?php _e('After Log Out URL', 'frontend-user-admin'); ?></label>:<br />
<input type="text" name="after_logout_url" id="after_logout_url" value="<?php if( !empty($options['global_settings']['after_logout_url']) ) echo esc_attr($options['global_settings']['after_logout_url']); ?>" size="60" class="regular-text imedisabled" /></p>
</td></tr>
<tr><td>
<p><label for="after_login_url_exception"><?php _e('After Log In URL Exception URL', 'frontend-user-admin'); ?></label>:<br />
<textarea name="after_login_url_exception" cols="50" rows="3" id="after_login_url_exception"><?php if( !empty($options['global_settings']['after_login_url_exception']) ) echo htmlspecialchars($options['global_settings']['after_login_url_exception']); ?></textarea><br />
<?php _e('Please specify the exception url in each line.', 'frontend-user-admin'); ?></p>
</td></tr>
<tr><td>
<p><label for="after_login_template_file"><?php _e('After Log In Template File', 'frontend-user-admin'); ?></label>:<br />
<input type="text" name="after_login_template_file" id="after_login_template_file" value="<?php if( !empty($options['global_settings']['after_login_template_file']) ) echo esc_attr($options['global_settings']['after_login_template_file']); ?>" size="30" class="regular-text imedisabled" /><br />
<?php _e('If you would like to use the original template file, you need to set the file in the theme directory.', 'frontend-user-admin'); ?></p>
</td></tr>
<tr><td>
<p><label for="transfer_all_to_login"><?php _e('Transfer all to the Log In URL', 'frontend-user-admin'); ?>:<br />
<input type="checkbox" name="transfer_all_to_login" id="transfer_all_to_login" value="1" <?php if( !empty($options['global_settings']['transfer_all_to_login']) ) echo 'checked="checked"'; ?>/> <?php _e('If the user is not logged in, transfer all to the Log In URL.', 'frontend-user-admin'); ?></label></p>
</td></tr>
<tr><td>
<p><label for="transfer_all_to_alternative_url"><?php _e('Alternative URL for transfer all', 'frontend-user-admin'); ?></label>:<br />
<input type="text" name="transfer_all_to_alternative_url" id="transfer_all_to_alternative_url" value="<?php if( !empty($options['global_settings']['transfer_all_to_alternative_url']) ) echo esc_attr($options['global_settings']['transfer_all_to_alternative_url']); ?>" size="60" class="regular-text imedisabled" /></p>
</td></tr>
<tr><td>
<p><label for="transfer_all_to_login_exception"><?php _e('Transfer all to the Log In Exception URL', 'frontend-user-admin'); ?></label>:<br />
<textarea name="transfer_all_to_login_exception" cols="50" rows="3" id="transfer_all_to_login_exception"><?php if( !empty($options['global_settings']['transfer_all_to_login_exception']) ) echo $options['global_settings']['transfer_all_to_login_exception']; ?></textarea><br />
<?php _e('Please specify the exception url in each line.', 'frontend-user-admin'); ?></p>
</td></tr>
<tr><td>
<p><label for="users_can_register"><?php _e('User Registration', 'frontend-user-admin'); ?>:<br />
<input type="checkbox" name="users_can_register" id="users_can_register" value="1" <?php if( !empty($options['global_settings']['users_can_register']) ) echo 'checked="checked"'; ?>/> <?php _e('Anyone can register.', 'frontend-user-admin'); ?></label></p>
</td></tr>
<tr><td>
<p><label for="show_the_content_directly"><?php _e('Show the content directly when the user logged in', 'frontend-user-admin'); ?>:<br />
<input type="checkbox" name="show_the_content_directly" id="show_the_content_directly" value="1" <?php if( !empty($options['global_settings']['show_the_content_directly']) ) echo 'checked="checked"'; ?>/> <?php _e('If the user logged in, it shows the post content directly not the login box.', 'frontend-user-admin'); ?></label></p>
</td></tr>
<tr><td>
<p><label for="menu_during_login"><?php _e('Show the menu in the login page during login', 'frontend-user-admin'); ?>:<br />
<input type="checkbox" name="menu_during_login" id="menu_during_login" value="1" <?php if( !empty($options['global_settings']['menu_during_login']) ) echo 'checked="checked"'; ?>/> <?php _e('If the user logged in, it shows the menu at the head of content in the login page.', 'frontend-user-admin'); ?></label></p>
</td></tr>
<tr><td>
<p><label for="transfer_to_after_login_url"><?php _e('Transfer to the After Log In URL when the user logged in', 'frontend-user-admin'); ?>:<br />
<input type="checkbox" name="transfer_to_after_login_url" id="transfer_to_after_login_url" value="1" <?php if( !empty($options['global_settings']['transfer_to_after_login_url']) ) echo 'checked="checked"'; ?>/> <?php _e('If the user logged in, transfer to the After Log In URL.', 'frontend-user-admin'); ?></label></p>
</td></tr>
<tr><td>
<p><?php _e('Number of Username', 'frontend-user-admin'); ?>:<br />
<label for="user_login_min_letters"><?php _e('Min Letters', 'frontend-user-admin'); ?></label>:
<select name="user_login_min_letters">
<option value=""></option>
<?php
				for($j=1;$j<51;$j++) :
?>
<option value="<?php echo $j; ?>"<?php if( !empty($options['global_settings']['user_login_min_letters']) && $j == $options['global_settings']['user_login_min_letters'] ) : echo ' selected="selected"'; endif;?>><?php echo $j; ?></option>
<?php
				endfor;
?>
</select>
<label for="user_login_max_letters"><?php _e('Max Letters', 'frontend-user-admin'); ?></label>:
<select name="user_login_max_letters">
<option value=""></option>
<?php
				for($j=1;$j<51;$j++) :
?>
<option value="<?php echo $j; ?>"<?php if( !empty($options['global_settings']['user_login_max_letters']) && $j == $options['global_settings']['user_login_max_letters'] ) : echo ' selected="selected"'; endif;?>><?php echo $j; ?></option>
<?php
				endfor;
?>
</select></p>
</td></tr>
<tr><td>
<p><?php _e('Number of Password', 'frontend-user-admin'); ?>:<br />
<label for="user_pass_min_letters"><?php _e('Min Letters', 'frontend-user-admin'); ?></label>:
<select name="user_pass_min_letters">
<option value=""></option>
<?php
				for($j=1;$j<51;$j++) :
?>
<option value="<?php echo $j; ?>"<?php if( !empty($options['global_settings']['user_pass_min_letters']) && $j == $options['global_settings']['user_pass_min_letters'] ) : echo ' selected="selected"'; endif;?>><?php echo $j; ?></option>
<?php
				endfor;
?>
</select>
<label for="user_pass_max_letters"><?php _e('Max Letters', 'frontend-user-admin'); ?></label>:
<select name="user_pass_max_letters">
<option value=""></option>
<?php
				for($j=1;$j<51;$j++) :
?>
<option value="<?php echo $j; ?>"<?php if( !empty($options['global_settings']['user_pass_max_letters']) && $j == $options['global_settings']['user_pass_max_letters'] ) : echo ' selected="selected"'; endif;?>><?php echo $j; ?></option>
<?php
				endfor;
?>
</select></p>
</td></tr>
<tr><td>
<p><label for="user_login_regexp"><?php _e('Username Regulation Expressions', 'frontend-user-admin'); ?>:<br />
<input type="text" name="user_login_regexp" id="user_login_regexp" value="<?php if( !empty($options['global_settings']['user_login_regexp']) ) echo esc_attr($options['global_settings']['user_login_regexp']); ?>" class="regular-text" /></label></p>
</td></tr>
<tr><td>
<p><label for="email_as_userlogin"><?php _e('The email as Username', 'frontend-user-admin'); ?>:<br />
<input type="checkbox" name="email_as_userlogin" id="email_as_userlogin" value="1" <?php if( !empty($options['global_settings']['email_as_userlogin']) ) echo 'checked="checked"'; ?>/> <?php _e('Use the email as the username. ', 'frontend-user-admin'); ?></label></p>
</td></tr>
<tr><td>
<p><label for="userlogin_automatic_generation"><?php _e('Username Automatic Generation', 'frontend-user-admin'); ?>:<br />
<input type="checkbox" name="userlogin_automatic_generation" id="userlogin_automatic_generation" value="1" <?php if( !empty($options['global_settings']['userlogin_automatic_generation']) ) echo 'checked="checked"'; ?>/> <?php _e('Generate an userlogin automatically in an user registration.', 'frontend-user-admin'); ?></label></p>
</td></tr>
<tr><td>
<p><label for="password_registration"><?php _e('Password Registration', 'frontend-user-admin'); ?>:<br />
<input type="checkbox" name="password_registration" id="password_registration" value="1" <?php if( !empty($options['global_settings']['password_registration']) ) echo 'checked="checked"'; ?>/> <?php _e('Let new users decide a password on the registration.', 'frontend-user-admin'); ?></label></p>
</td></tr>
<tr><td>
<p><label for="email_confirmation_first"><?php _e('Email Confirmation (before registration)', 'frontend-user-admin'); ?>:<br />
<input type="checkbox" name="email_confirmation_first" id="email_confirmation_first" value="1" <?php if( !empty($options['global_settings']['email_confirmation_first']) ) echo 'checked="checked"'; ?>/> <?php _e('Need Email confirmation before user registration.', 'frontend-user-admin'); ?></label></p>
</td></tr>
<tr><td>
<p><label for="email_confirmation_parallel"><?php _e('Email Confirmation (parallel registration)', 'frontend-user-admin'); ?>:<br />
<input type="checkbox" name="email_confirmation_parallel" id="email_confirmation_parallel" value="1" <?php if( !empty($options['global_settings']['email_confirmation_parallel']) ) echo 'checked="checked"'; ?>/> <?php _e('Need Email confirmation in parallel with user registration.', 'frontend-user-admin'); ?></label></p>
</td></tr>
<tr><td>
<p><label for="email_confirmation"><?php _e('Email Confirmation (after registration)', 'frontend-user-admin'); ?>:<br />
<input type="checkbox" name="email_confirmation" id="email_confirmation" value="1" <?php if( !empty($options['global_settings']['email_confirmation']) ) echo 'checked="checked"'; ?>/> <?php _e('Need Email confirmation after user registration.', 'frontend-user-admin'); ?></label></p>
</td></tr>
<tr><td>
<p><label for="email_duplication"><?php _e('Email Duplication', 'frontend-user-admin'); ?>:<br />
<input type="checkbox" name="email_duplication" id="email_duplication" value="1" <?php if( !empty($options['global_settings']['email_duplication']) ) echo 'checked="checked"'; ?>/> <?php _e('Allow users to register by the email duplication.', 'frontend-user-admin'); ?></label></p>
</td></tr>
<tr><td>
<p><label for="approval_registration"><?php _e('Approval Registration', 'frontend-user-admin'); ?>:<br />
<input type="checkbox" name="approval_registration" id="approval_registration" value="1" <?php if( !empty($options['global_settings']['approval_registration']) ) echo 'checked="checked"'; ?>/> <?php _e('Need the site owner approval to register.', 'frontend-user-admin'); ?></label></p>
</td></tr>
<tr><td>
<p><label for="password_auto_regeneration"><?php _e('Password Auto Regeneration', 'frontend-user-admin'); ?>:<br />
<input type="checkbox" name="password_auto_regeneration" id="password_auto_regeneration" value="1" <?php if( !empty($options['global_settings']['password_auto_regeneration']) ) echo 'checked="checked"'; ?>/> <?php _e('Regenerate the password automatically in sending a registration email without the password registration by users.', 'frontend-user-admin'); ?></label></p>
</td></tr>
<?php
				if ( function_exists('is_multisite') && is_multisite() ) :
?>
<tr><td>
<p><label for="unique_registration"><?php _e('Unique Registration', 'frontend-user-admin'); ?> <?php _e('(Multi Site)', 'frontend-user-admin'); ?>:<br />
<input type="checkbox" name="unique_registration" id="unique_registration" value="1" <?php if( !empty($options['global_settings']['unique_registration']) ) echo 'checked="checked"'; ?>/> <?php _e('Treat usernames uniquely in each site. Site ID will be added into usernames automatically.', 'frontend-user-admin'); ?></label></p>
</td></tr>
<tr><td>
<p><label for="all_site_registration"><?php _e('All Site Registration', 'frontend-user-admin'); ?> <?php _e('(Multi Site)', 'frontend-user-admin'); ?>:<br />
<input type="checkbox" name="all_site_registration" id="all_site_registration" value="1" <?php if( !empty($options['global_settings']['all_site_registration']) ) echo 'checked="checked"'; ?>/> <?php _e('Register new users to all sites.', 'frontend-user-admin'); ?></label></p>
</td></tr>
<tr><td>
<p><label for="logout_other_sites"><?php _e('Logout Other Sites', 'frontend-user-admin'); ?> <?php _e('(Multi Site)', 'frontend-user-admin'); ?>:<br />
<input type="checkbox" name="logout_other_sites" id="logout_other_sites" value="1" <?php if( !empty($options['global_settings']['logout_other_sites']) ) echo 'checked="checked"'; ?>/> <?php _e('Logout in other sites which are not associated with users.', 'frontend-user-admin'); ?></label></p>
</td></tr>
	<tr><td>
<p><label for="exclude_admin_user"><?php _e('Exclude Admin Users', 'frontend-user-admin'); ?> <?php _e('(Multi Site)', 'frontend-user-admin'); ?>:<br />
<input type="checkbox" name="exclude_admin_user" id="exclude_admin_user" value="1" <?php if( !empty($options['global_settings']['exclude_admin_user']) ) echo 'checked="checked"'; ?>/> <?php _e('Exclude admin users from the user list.', 'frontend-user-admin'); ?></label></p>
</td></tr>
<!--
<tr><td>
<p><label for="user_complete_deletion"><?php _e('User Complete Deletion', 'frontend-user-admin'); ?> <?php _e('(Multi Site)', 'frontend-user-admin'); ?>:<br />
<input type="checkbox" name="user_complete_deletion" id="user_complete_deletion" value="1" <?php if( !empty($options['global_settings']['user_complete_deletion']) ) echo 'checked="checked"'; ?>/> <?php _e('Delete user data completely.', 'frontend-user-admin'); ?></label></p>
</td></tr>
-->
<?php
				endif;
?>
<tr><td>
<p><label for="use_password_strength"><?php _e('Use Password Strength', 'frontend-user-admin'); ?>:<br />
<input type="checkbox" name="use_password_strength" id="use_password_strength" value="1" <?php if( !empty($options['global_settings']['use_password_strength']) ) echo 'checked="checked"'; ?>/> <?php _e('Use the password strength on the registration.', 'frontend-user-admin'); ?></label></p>
</td></tr>
<tr><td>
<p><label for="use_common_password"><?php _e('Use Common Password', 'frontend-user-admin'); ?>:<br />
<input type="checkbox" name="use_common_password" id="use_common_password" value="1" <?php if( !empty($options['global_settings']['use_common_password']) ) echo 'checked="checked"'; ?>/> <?php _e('Use the common password to login.', 'frontend-user-admin'); ?></label></p>
</td></tr>
<tr><td>
<p><label for="common_password"><?php _e('Set the common password.', 'frontend-user-admin'); ?></label>:<br />
<input type="text" name="common_password" id="common_password" value="<?php if( !empty($options['global_settings']['common_password']) ) echo esc_attr($options['global_settings']['common_password']); ?>" size="30" class="regular-text imedisabled" /><br /><?php _e('You need to set this common password when you register users. In order to set up the common password automatically in registration, please check the Password Registration.', 'frontend-user-admin'); ?></p>
</td></tr>
<?php
				if ( substr($wp_version, 0, 3) >= '4.3' ) :
?>
<tr><td>
<p><label for="disable_password_change_email"><?php _e('Disable the password change email.', 'frontend-user-admin'); ?>:<br />
<input type="checkbox" name="disable_password_change_email" id="disable_password_change_email" value="1" <?php if( !empty($options['global_settings']['disable_password_change_email']) ) echo 'checked="checked"'; ?>/> <?php _e('Do not send the password change email', 'frontend-user-admin'); ?></label></p>
</td></tr>
<tr><td>
<p><label for="disable_email_change_email"><?php _e('Disable the email change email.', 'frontend-user-admin'); ?>:<br />
<input type="checkbox" name="disable_email_change_email" id="disable_email_change_email" value="1" <?php if( !empty($options['global_settings']['disable_email_change_email']) ) echo 'checked="checked"'; ?>/> <?php _e('Do not send the email change email', 'frontend-user-admin'); ?></label></p>
</td></tr>
<?php
				endif;
?>
<tr><td>
<p><label for="hide_rememberme"><?php _e('Hide `Remember me`', 'frontend-user-admin'); ?>:<br />
<input type="checkbox" name="hide_rememberme" id="hide_rememberme" value="1" <?php if( !empty($options['global_settings']['hide_rememberme']) ) echo 'checked="checked"'; ?>/> <?php _e('Hide `Remember me` checkbox in login.', 'frontend-user-admin'); ?></label></p>
</td></tr>
<tr><td>
<p><label for="login_after_registration"><?php _e('Login after registration', 'frontend-user-admin'); ?>:<br />
<input type="checkbox" name="login_after_registration" id="login_after_registration" value="1" <?php if( !empty($options['global_settings']['login_after_registration']) ) echo 'checked="checked"'; ?>/> <?php _e('Login after registration automatically.', 'frontend-user-admin'); ?></label></p>
</td></tr>
<tr><td>
<p><label for="confirmation_screen"><?php _e('Confirmation Screen', 'frontend-user-admin'); ?>:<br />
<input type="checkbox" name="confirmation_screen" id="confirmation_screen" value="1" <?php if( !empty($options['global_settings']['confirmation_screen']) ) echo 'checked="checked"'; ?>/> <?php _e('Show the confirmation screen on the registration.', 'frontend-user-admin'); ?></label></p>
</td></tr>
<tr><td>
<p><label for="enforcement_update"><?php _e('Enforcement Update', 'frontend-user-admin'); ?>:<br />
<input type="checkbox" name="enforcement_update" id="enforcement_update" value="1" <?php if( !empty($options['global_settings']['enforcement_update']) ) echo 'checked="checked"'; ?>/> <?php _e('Update user attributes regardless of required fields in the admin.', 'frontend-user-admin'); ?></label></p>
</td></tr>
<tr><td>
<p><label for="required_check"><?php _e('Required field check', 'frontend-user-admin'); ?>:<br />
<input type="checkbox" name="required_check" id="required_check" value="1" <?php if( !empty($options['global_settings']['required_check']) ) echo 'checked="checked"'; ?>/> <?php _e('Enforce users to input required fields in any time.', 'frontend-user-admin'); ?></label></p>
</td></tr>
<tr><td>
<p><label for="default_messages"><?php _e('Default Messages', 'frontend-user-admin'); ?>:<br />
<input type="checkbox" name="default_messages" id="default_messages" value="1" <?php if( !empty($options['global_settings']['default_messages']) ) echo 'checked="checked"'; ?>/> <?php _e('Display default messages on the top of each page.', 'frontend-user-admin'); ?></label></p>
</td></tr>
<tr><td>
<p><label for="set_default_userlogin"><?php _e('Set the default user_login.', 'frontend-user-admin'); ?></label>:<br />
<input type="text" name="set_default_userlogin" id="set_default_userlogin" value="<?php if( !empty($options['global_settings']['set_default_userlogin']) ) echo esc_attr($options['global_settings']['set_default_userlogin']); ?>" size="30" class="imedisabled" /><br /><?php _e('The input box of Log In will be the hidden attribute, if you set the default user_login.', 'frontend-user-admin'); ?></p>
</td></tr>
<tr><td>
<p><label for="use_smartphone_code"><?php _e('Smartphone Code', 'frontend-user-admin'); ?>:<br />
<input type="checkbox" name="use_smartphone_code" id="use_smartphone_code" value="1" <?php if( !empty($options['global_settings']['use_smartphone_code']) ) { echo 'checked="checked"';} ?>/> <?php _e('Use Smartphone output code', 'frontend-user-admin'); ?></label></p>
</td></tr>
<tr><td>
<p><label for="exclude_ipad"><?php _e('Smartphone Determination', 'frontend-user-admin'); ?>:<br />
<input type="checkbox" name="exclude_ipad" id="exclude_ipad" value="1" <?php if( !empty($options['global_settings']['exclude_ipad']) ) { echo 'checked="checked"';} ?>/> <?php _e('Exclude iPad from Smartphone', 'frontend-user-admin'); ?></label></p>
</td></tr>
<tr><td>
<p><label for="use_style_sheet"><?php _e('Use Style Sheet', 'frontend-user-admin'); ?>:<br />
<input type="checkbox" name="use_style_sheet" id="use_style_sheet" value="1" <?php if( !empty($options['global_settings']['use_style_sheet']) ) echo 'checked="checked"'; ?>/> <?php _e('Load the CSS file.', 'frontend-user-admin'); ?></label></p>
</td></tr>
<tr><td>
<p><label for="use_withdrawal"><?php _e('Use Withdrawal', 'frontend-user-admin'); ?>:<br />
<input type="checkbox" name="use_withdrawal" id="use_withdrawal" value="1" <?php if( !empty($options['global_settings']['use_withdrawal']) ) echo 'checked="checked"'; ?>/> <?php _e('Allow users to delete the account.', 'frontend-user-admin'); ?></label></p>
</td></tr>
<tr><td>
<p><label for="soft_user_deletion"><?php _e('Use the soft user deletion', 'frontend-user-admin'); ?>:<br />
<input type="checkbox" name="soft_user_deletion" id="soft_user_deletion" value="1" <?php if( !empty($options['global_settings']['soft_user_deletion']) ) echo 'checked="checked"'; ?>/> <?php _e('After the user withdrawal, user name and email will be only renamed.', 'frontend-user-admin'); ?></label></p>
</td></tr>
<tr><td>
<p><label for="disable_links"><?php _e('Disable Links', 'frontend-user-admin'); ?>:<br />
<input type="checkbox" name="disable_links" id="disable_links" value="1" <?php if( !empty($options['global_settings']['disable_links']) ) echo 'checked="checked"';?>/> <?php _e('Do not output trailing links.', 'frontend-user-admin'); ?></label></p>
</td></tr>
<tr><td>
<p><label for="disable_profile"><?php _e('Disable Profile', 'frontend-user-admin'); ?>:<br />
<input type="checkbox" name="disable_profile" id="disable_profile" value="1" <?php if( !empty($options['global_settings']['disable_profile']) ) echo 'checked="checked"'; ?>/> <?php _e('Do not allow users to update the profile.', 'frontend-user-admin'); ?></label></p>
</td></tr>
<tr><td>
<p><label for="disable_lostpassword"><?php _e('Disable Password Lost and Found', 'frontend-user-admin'); ?>:<br />
<input type="checkbox" name="disable_lostpassword" id="disable_lostpassword" value="1" <?php if( !empty($options['global_settings']['disable_lostpassword']) ) echo 'checked="checked"';?>/> <?php _e('Do not allow users to find the password.', 'frontend-user-admin'); ?></label></p>
</td></tr>
<tr><td>
<p><label for="disable_cookieerror"><?php _e('Disable the cookie error', 'frontend-user-admin'); ?>:<br />
<input type="checkbox" name="disable_cookieerror" id="disable_cookieerror" value="1" <?php if( !empty($options['global_settings']['disable_cookieerror']) ) echo 'checked="checked"'; ?>/> <?php _e('Do not allow to show the cookie error.', 'frontend-user-admin'); ?></label></p>
</td></tr>
<tr><td>
<p><label for="disable_duplicate_login"><?php _e('Disable the duplicate login', 'frontend-user-admin'); ?>:<br />
<input type="checkbox" name="disable_duplicate_login" id="disable_duplicate_login" value="1" <?php if( !empty($options['global_settings']['disable_duplicate_login']) ) echo 'checked="checked"'; ?>/> <?php _e('Do not allow multiple users in the same account to login.', 'frontend-user-admin'); ?></label></p>
</td></tr>
<tr><td>
<p><label for="disable_admin_bar"><?php _e('Disable the admin bar', 'frontend-user-admin'); ?>:<br />
<input type="checkbox" name="disable_admin_bar" id="disable_admin_bar" value="1" <?php if( !empty($options['global_settings']['disable_admin_bar']) ) echo 'checked="checked"'; ?>/> <?php _e('Do not allow users to use the admin bar.', 'frontend-user-admin'); ?></label></p>
</td></tr>
<tr><td>
<p><label for="normal_auth_time"><?php _e('Normal Authentication Timeout', 'frontend-user-admin'); ?>:<br />
<input type="text" name="normal_auth_time" id="normal_auth_time" value="<?php if( !empty($options['global_settings']['normal_auth_time']) ) echo esc_attr($options['global_settings']['normal_auth_time']); ?>" size="10" /> <?php _e('seconds', 'frontend-user-admin'); ?></label></p>
</td></tr>
<tr><td>
<p><label for="remember_auth_time"><?php _e('&quot;Remember Me&quot; Authentication Timeout', 'frontend-user-admin'); ?>:<br />
<input type="text" name="remember_auth_time" id="remember_auth_time" value="<?php if( !empty($options['global_settings']['remember_auth_time']) ) echo esc_attr($options['global_settings']['remember_auth_time']); ?>" size="10" /> <?php _e('seconds', 'frontend-user-admin'); ?></label></p>
</td></tr>
<tr><td>
<p><label for="record_login_datetime"><?php _e('Login Datetime', 'frontend-user-admin'); ?>:<br />
<input type="checkbox" name="record_login_datetime" id="record_login_datetime" value="1" <?php if( !empty($options['global_settings']['record_login_datetime']) ) echo 'checked="checked"'; ?>/> <?php _e('Record login datetime.', 'frontend-user-admin'); ?></label></p>
</td></tr>
<tr><td>
<p><label for="record_update_datetime"><?php _e('Update Datetime', 'frontend-user-admin'); ?>:<br />
<input type="checkbox" name="record_update_datetime" id="record_update_datetime" value="1" <?php if( !empty($options['global_settings']['record_update_datetime']) ) echo 'checked="checked"'; ?>/> <?php _e('Record update datetime.', 'frontend-user-admin'); ?></label></p>
</td></tr>
<tr><td>
<p><label for="logout_time"><?php _e('Logout Time', 'frontend-user-admin'); ?>:<br />
<input type="text" name="logout_time" id="logout_time" value="<?php if( !empty($options['global_settings']['logout_time']) ) echo esc_attr($options['global_settings']['logout_time']); ?>" size="10" /> <?php _e('minutes', 'frontend-user-admin'); ?></label></p>
</td></tr>
<tr><td>
<p><label for="logout_time_except_administrators"><?php _e('Logout Time except Administrators', 'frontend-user-admin'); ?>:<br />
<input type="checkbox" name="logout_time_except_administrators" id="logout_time_except_administrators" value="1" <?php if( !empty($options['global_settings']['logout_time_except_administrators']) ) echo 'checked="checked"'; ?>/> <?php _e('Do not apply Logout Time to Administrator users.', 'frontend-user-admin'); ?></label></p>
</td></tr>
<tr><td>
<p><label for="password_expiration_date"><?php _e('Password Expiration Date', 'frontend-user-admin'); ?>:<br />
<input type="text" name="password_expiration_date" id="password_expiration_date" value="<?php if( !empty($options['global_settings']['password_expiration_date']) ) echo esc_attr($options['global_settings']['password_expiration_date']); ?>" size="10" /> <?php _e('days', 'frontend-user-admin'); ?></label></p>
</td></tr>
<tr><td>
<p><label for="password_lock_miss_times"><?php _e('Password Lock Miss Times', 'frontend-user-admin'); ?>:<br />
<input type="text" name="password_lock_miss_times" id="password_lock_miss_times" value="<?php if( !empty($options['global_settings']['password_lock_miss_times']) ) echo esc_attr($options['global_settings']['password_lock_miss_times']); ?>" size="10" /> <?php _e('times', 'frontend-user-admin'); ?></label></p>
</td></tr>
<tr><td>
<p><label for="password_lock_miss_email"><?php _e('Send E-mail after Password Lock', 'frontend-user-admin'); ?>:<br />
<input type="checkbox" name="password_lock_miss_email" id="password_lock_miss_email" value="1" <?php if( !empty($options['global_settings']['password_lock_miss_email']) ) echo 'checked="checked"'; ?>/> <?php _e('Send E-mail to reset password after missing password and locking the account.', 'frontend-user-admin'); ?></label></p>
</td></tr>
<tr><td>
<p><label for="password_lock_retrieval_time"><?php _e('Password Lock Retrieval Time', 'frontend-user-admin'); ?>:<br />
<input type="text" name="password_lock_retrieval_time" id="password_lock_retrieval_time" value="<?php if( !empty($options['global_settings']['password_lock_retrieval_time']) ) echo esc_attr($options['global_settings']['password_lock_retrieval_time']); ?>" size="10" /> <?php _e('minutes', 'frontend-user-admin'); ?></label></p>
</td></tr>
<tr><td>
<p><label for="different_password"><?php _e('Different Password', 'frontend-user-admin'); ?>:<br />
<input type="checkbox" name="different_password" id="different_password" value="1" <?php if( !empty($options['global_settings']['different_password']) ) echo 'checked="checked"'; ?>/> <?php _e('A different password from old one must be input as a new password.', 'frontend-user-admin'); ?></label></p>
</td></tr>
<tr><td>
<p><label for="start_log"><?php _e('Start Log', 'frontend-user-admin'); ?>:<br />
<input type="checkbox" name="start_log" id="start_log" value="1" <?php if( !empty($options['global_settings']['start_log']) ) echo 'checked="checked"'; ?>/> <?php _e('Start logging the user actions.', 'frontend-user-admin'); ?></label></p>
</td></tr>
<tr><td>
<p><label for="log_username"><?php _e('Log User Name', 'frontend-user-admin'); ?>:<br />
<select name="log_username">
<?php
	$attribute = $this->attribute_name2label();
	foreach( $attribute as $key => $val ) :
?>
<option value="<?php echo $key; ?>"<?php if( isset($options['global_settings']['log_username']) && $key==$options['global_settings']['log_username'] ) : echo ' selected="selected"'; endif; ?>><?php echo $val; ?></option>
<?php
	endforeach;
?>
</select>
</label>
</p>
</td></tr>
<tr><td>
<p><label for="delete_log_days"><?php _e('Delete logs earlier than the following days automatically', 'frontend-user-admin'); ?></label>:<br />
<input type="text" name="delete_log_days" id="delete_log_days" value="<?php if ( !empty($options['global_settings']['delete_log_days']) ) echo esc_attr($options['global_settings']['delete_log_days']); ?>" size="10" class="imedisabled" /> <?php _e('days', 'frontend-user-admin'); ?></p>
<tr><td>
<p><label for="admin_panel_user_level"><?php _e('Admin Panel User Level', 'frontend-user-admin'); ?>:<br />
<select name="admin_panel_user_level" id="admin_panel_user_level">
<?php
	for ( $i=0; $i<11; $i++ ) :
?>
<option value="<?php echo $i; ?>"<?php if ( isset($options['global_settings']['admin_panel_user_level']) ) selected($options['global_settings']['admin_panel_user_level'], $i); ?>><?php echo $i; ?></option>
<?php
	endfor;
?>
</select>
</label></p>
</td></tr>
<tr><td>
<p><label for="disable_widget_while_login"><?php _e('Disable the widget while login', 'frontend-user-admin'); ?>:<br />
<input type="checkbox" name="disable_widget_while_login" id="disable_widget_while_login" value="1" <?php if( !empty($options['global_settings']['disable_widget_while_login']) ) echo 'checked="checked"'; ?>/> <?php _e('Do not show the widget while login.', 'frontend-user-admin'); ?></label></p>
</td></tr>
<tr><td>
<p><label for="disable_widget_while_nologin"><?php _e('Disable the widget while nologin', 'frontend-user-admin'); ?>:<br />
<input type="checkbox" name="disable_widget_while_nologin" id="disable_widget_while_nologin" value="1" <?php if( !empty($options['global_settings']['disable_widget_while_nologin']) ) echo 'checked="checked"'; ?>/> <?php _e('Do not show the widget while nologin.', 'frontend-user-admin'); ?></label></p>
</td></tr>
<tr><td>
<p><label for="widget_title"><?php _e('Widget Title', 'frontend-user-admin'); ?>:<br />
<textarea name="widget_title" cols="50" rows="3" id="widget_title"><?php if( !empty($options['global_settings']['widget_title']) ) echo $options['global_settings']['widget_title']; ?></textarea></label></p>
</td></tr>
<tr><td>
<p><label for="widget_content"><?php _e('Widget Content', 'frontend-user-admin'); ?>:<br />
<textarea name="widget_content" cols="50" rows="3" id="widget_content"><?php if( !empty($options['global_settings']['widget_content']) ) echo $options['global_settings']['widget_content']; ?></textarea></label></p>
</td></tr>
<tr><td>
<p><label for="array_delimiter"><?php _e('Array Delimiter', 'frontend-user-admin'); ?>:<br />
<input type="text" name="array_delimiter" id="array_delimiter" value="<?php if( !empty($options['global_settings']['array_delimiter']) ) echo esc_attr($options['global_settings']['array_delimiter']); ?>" size="30" /></label></p>
</td></tr>
<tr><td>
<p><label for="required_mark"><?php _e('Required Mark', 'frontend-user-admin'); ?>:<br />
<input type="text" name="required_mark" id="required_mark" value="<?php if( !empty($options['global_settings']['required_mark']) ) echo esc_attr($options['global_settings']['required_mark']); ?>" size="30" /></label></p>
</td></tr>
<?php
			if ( function_exists('is_multisite') && is_multisite() ) :
				if ( !in_array('ms_domain', $options['global_settings']['register_order']) )  $options['global_settings']['register_order'][] = 'ms_domain';
				if ( !in_array('ms_domain', $options['global_settings']['profile_order']) )  $options['global_settings']['profile_order'][] = 'ms_domain';
				if ( !in_array('ms_title', $options['global_settings']['register_order']) )  $options['global_settings']['register_order'][] = 'ms_title';
				if ( !in_array('ms_title', $options['global_settings']['profile_order']) )  $options['global_settings']['profile_order'][] = 'ms_title';
			endif;
?>
<tr><td>
<div style="width:45%; float:left;">
<p><?php _e('Register Item and Order', 'frontend-user-admin'); ?>:</p>
<ul id="sortable_register_usermeta">
<?php
			if( !empty($options['global_settings']['register_order']) && is_array($options['global_settings']['register_order']) ) :
				foreach( $options['global_settings']['register_order'] as $val ) :
					switch ( $val ) :
						case "user_login": ?>
<li><input type="checkbox" name="register_user_login" id="register_user_login" value="1" <?php if( !empty($options['global_settings']['register_user_login']) ) echo 'checked="checked"'; ?>/> <label for="register_user_login"><?php _e('Username', 'frontend-user-admin'); ?> <?php _e('(Default)', 'frontend-user-admin'); ?><input type="hidden" name="register_order[]" value="user_login" class="none" /></label></li>
<?php					break;
						case "first_name": ?>
<li><input type="checkbox" name="register_first_name" id="register_first_name" value="1" <?php if( !empty($options['global_settings']['register_first_name']) ) echo 'checked="checked"'; ?>/> <label for="register_first_name"><?php _e('First name', 'frontend-user-admin'); ?></label> <?php _e('(Default)', 'frontend-user-admin'); ?> <label><input type="checkbox" name="register_first_name_required" id="register_first_name_required" value="1" <?php if ( !empty($options['global_settings']['register_first_name_required']) ) echo 'checked="checked"'; ?> /> <?php _e('Required', 'frontend-user-admin'); ?></label> <label><input type="checkbox" name="register_first_name_composite_unique" id="register_first_name_composite_unique" value="1" <?php if ( !empty($options['global_settings']['register_first_name_composite_unique']) ) echo 'checked="checked"'; ?> /> <?php _e('Composite Unique', 'frontend-user-admin'); ?></label><input type="hidden" name="register_order[]" value="first_name" class="none" /></li>
<?php					break;
						case "last_name": ?>
<li><input type="checkbox" name="register_last_name" id="register_last_name" value="1" <?php if( !empty($options['global_settings']["register_last_name"]) ) echo 'checked="checked"'; ?>/> <label for="register_last_name"><?php _e('Last name', 'frontend-user-admin'); ?></label> <?php _e('(Default)', 'frontend-user-admin'); ?> <label><input type="checkbox" name="register_last_name_required" id="register_last_name_required" value="1" <?php if ( !empty($options['global_settings']['register_last_name_required']) ) echo 'checked="checked"'; ?> /> <?php _e('Required', 'frontend-user-admin'); ?></label> <label><input type="checkbox" name="register_last_name_composite_unique" id="register_last_name_composite_unique" value="1" <?php if ( !empty($options['global_settings']['register_last_name_composite_unique']) ) echo 'checked="checked"'; ?> /> <?php _e('Composite Unique', 'frontend-user-admin'); ?></label><input type="hidden" name="register_order[]" value="last_name" class="none" /></li>
<?php					break;
						case "nickname": ?>
<li><input type="checkbox" name="register_nickname" id="register_nickname" value="1" <?php if( !empty($options['global_settings']["register_nickname"]) ) echo 'checked="checked"'; ?>/> <label for="register_nickname"><?php _e('Nickname', 'frontend-user-admin'); ?></label> <?php _e('(Default)', 'frontend-user-admin'); ?> <label><input type="checkbox" name="register_nickname_required" id="register_nickname_required" value="1" <?php if ( !empty($options['global_settings']['register_nickname_required']) ) echo 'checked="checked"'; ?> /> <?php _e('Required', 'frontend-user-admin'); ?></label> <label><input type="checkbox" name="register_nickname_composite_unique" id="register_nickname_composite_unique" value="1" <?php if ( !empty($options['global_settings']['register_nickname_composite_unique']) ) echo 'checked="checked"'; ?> /> <?php _e('Composite Unique', 'frontend-user-admin'); ?></label><input type="hidden" name="register_order[]" value="nickname" class="none" /></li>
<?php					break;
						case "display_name": ?>
<li><input type="checkbox" name="register_display_name" id="register_display_name" value="1" <?php if( !empty($options['global_settings']["register_display_name"]) ) echo 'checked="checked"'; ?>/> <label for="register_display_name"><?php _e('Display name publicly&nbsp;as', 'frontend-user-admin'); ?></label> <?php _e('(Default)', 'frontend-user-admin'); ?><input type="hidden" name="register_order[]" value="display_name" class="none" /></li>
<?php					break;
						case "user_email": ?>
<li><input type="checkbox" name="register_user_email" id="register_user_email" value="1" <?php if( !empty($options['global_settings']["register_user_email"]) ) echo 'checked="checked"'; ?>/> <label for="register_user_email"><?php _e('E-mail', 'frontend-user-admin'); ?></label> <?php _e('(Default)', 'frontend-user-admin'); ?><input type="hidden" name="register_order[]" value="user_email" class="none" /></li>
<?php					break;
						case "user_url": ?>
<li><input type="checkbox" name="register_user_url" id="register_user_url" value="1" <?php if( !empty($options['global_settings']["register_user_url"]) ) echo 'checked="checked"'; ?>/> <label for="register_user_url"><?php _e('Website', 'frontend-user-admin'); ?></label> <?php _e('(Default)', 'frontend-user-admin'); ?> <label><input type="checkbox" name="register_user_url_required" id="register_user_url_required" value="1" <?php if ( !empty($options['global_settings']['register_user_url_required']) ) echo 'checked="checked"'; ?> /> <?php _e('Required', 'frontend-user-admin'); ?></label><input type="hidden" name="register_order[]" value="user_url" class="none" /></li>
<?php					break;
						case "aim": ?>
<li><input type="checkbox" name="register_aim" id="register_aim" value="1" <?php if( !empty($options['global_settings']["register_aim"]) ) echo 'checked="checked"'; ?>/> <label for="register_aim"><?php _e('AIM', 'frontend-user-adm class="none"in'); ?></label> <?php _e('(Default)', 'frontend-user-admin'); ?> <label><input type="checkbox" name="register_aim_required" id="register_aim_required" value="1" <?php if ( !empty($options['global_settings']['register_aim_required']) ) echo 'checked="checked"'; ?> /> <?php _e('Required', 'frontend-user-admin'); ?></label><input type="hidden" name="register_order[]" value="aim" class="none" /></li>
<?php					break;
						case "yim": ?>
<li><input type="checkbox" name="register_yim" id="register_yim" value="1" <?php if( !empty($options['global_settings']["register_yim"]) ) echo 'checked="checked"'; ?>/> <label for="register_yim"><?php _e('Yahoo IM', 'frontend-user-admin'); ?></label> <?php _e('(Default)', 'frontend-user-admin'); ?> <label><input type="checkbox" name="register_yim_required" id="register_yim_required" value="1" <?php if ( !empty($options['global_settings']['register_yim_required']) ) echo 'checked="checked"'; ?> /> <?php _e('Required', 'frontend-user-admin'); ?></label><input type="hidden" name="register_order[]" value="yim" class="none" /></li>
<?php					break;
						case "jabber": ?>
<li><input type="checkbox" name="register_jabber" id="register_jabber" value="1" <?php if( !empty($options['global_settings']["register_jabber"]) ) echo 'checked="checked"'; ?>/> <label for="register_jabber"><?php _e('Jabber / Google Talk', 'frontend-user-admin'); ?></label> <?php _e('(Default)', 'frontend-user-admin'); ?> <label><input type="checkbox" name="register_jabber_required" id="register_jabber_required" value="1" <?php if ( !empty($options['global_settings']['register_jabber_required']) ) echo 'checked="checked"'; ?> /> <?php _e('Required', 'frontend-user-admin'); ?></label><input type="hidden" name="register_order[]" value="jabber" class="none" /></li>
<?php					break;
						case "description": ?>
<li><input type="checkbox" name="register_description" id="register_description" value="1" <?php if( !empty($options['global_settings']["register_description"]) ) echo 'checked="checked"'; ?>/> <label for="register_description"><?php _e('Biographical Info', 'frontend-user-admin'); ?></label> <?php _e('(Default)', 'frontend-user-admin'); ?> <label><input type="checkbox" name="register_description_required" id="register_description_required" value="1" <?php if ( !empty($options['global_settings']['register_description_required']) ) echo 'checked="checked"'; ?> /> <?php _e('Required', 'frontend-user-admin'); ?></label><input type="hidden" name="register_order[]" value="description" class="none" /></li>
<?php					break;
						case "role": ?>
<li><input type="checkbox" name="register_role" id="register_role" value="1" <?php if( !empty($options['global_settings']["register_role"]) ) echo 'checked="checked"'; ?>/> <label for="register_role"><?php _e('Role', 'frontend-user-admin'); ?></label> <?php _e('(Default)', 'frontend-user-admin'); ?><input type="hidden" name="register_order[]" value="role" class="none" /></li>
<?php					break;
						case "user_status": ?>
<li><input type="checkbox" name="register_user_status" id="register_user_status" value="1" <?php if( !empty($options['global_settings']["register_user_status"]) ) echo 'checked="checked"'; ?>/> <label for="register_user_status"><?php _e('User status', 'frontend-user-admin'); ?></label> <?php _e('(Default)', 'frontend-user-admin'); ?><input type="hidden" name="register_order[]" value="user_status" class="none" /></li>
<?php					break;
						case "no_log": ?>
<li><input type="checkbox" name="register_no_log" id="register_no_log" value="1" <?php if( !empty($options['global_settings']["register_no_log"]) ) echo 'checked="checked"'; ?>/> <label for="register_user_status"><?php _e('No log', 'frontend-user-admin'); ?></label> <?php _e('(Default)', 'frontend-user-admin'); ?><input type="hidden" name="register_order[]" value="no_log" class="none" /></li>
<?php					break;
						case "duplicate_login": ?>
<li><input type="checkbox" name="register_duplicate_login" id="register_duplicate_login" value="1" <?php if( !empty($options['global_settings']["register_duplicate_login"]) ) echo 'checked="checked"'; ?>/> <label for="register_user_status"><?php _e('Duplicate login', 'frontend-user-admin'); ?></label> <?php _e('(Default)', 'frontend-user-admin'); ?><input type="hidden" name="register_order[]" value="duplicate_login" class="none" /></li>
<?php					break;
						case "user_pass": ?>
<li><input type="checkbox" name="register_user_pass" id="register_user_pass" value="1" <?php if( !empty($options['global_settings']["register_user_pass"]) ) echo 'checked="checked"'; ?>/> <label for="register_user_pass"><?php _e('New Password', 'frontend-user-admin'); ?></label> <?php _e('(Default)', 'frontend-user-admin'); ?> <input type="hidden" name="register_order[]" value="user_pass" class="none" /></li>
<?php					break;
						case "ms_domain": ?>
<li><input type="checkbox" name="register_ms_domain" id="register_ms_domain" value="1" <?php if( !empty($options['global_settings']["register_ms_domain"]) ) echo 'checked="checked"'; ?>/> <label for="register_ms_domain"><?php _e('Site Domain', 'frontend-user-admin'); ?></label> <?php _e('(Multi Site)', 'frontend-user-admin'); ?> <input type="hidden" name="register_order[]" value="ms_domain" class="none" /></li>
<?php					break;
						case "ms_title": ?>
<li><input type="checkbox" name="register_ms_title" id="register_ms_title" value="1" <?php if( !empty($options['global_settings']["register_ms_title"]) ) echo 'checked="checked"'; ?>/> <label for="register_ms_title"><?php _e('Site Title', 'frontend-user-admin'); ?></label> <?php _e('(Multi Site)', 'frontend-user-admin'); ?> <input type="hidden" name="register_order[]" value="ms_title" class="none" /></li>
<?php					break;
						default:
							if ( !empty($options['user_attribute']['user_attribute']) ) $count_user_attribute = count($options['user_attribute']['user_attribute']);
							else $count_user_attribute = 0;

							for($i=0;$i<$count_user_attribute;$i++) :
								if($options['user_attribute']['user_attribute'][$i]['name'] == $val) : ?>
<li><input type="checkbox" name="register_<?php echo $val; ?>" id="register_<?php echo $val; ?>" value="1" <?php if ( !empty($options['global_settings']["register_".$val]) ) echo 'checked="checked"'; ?>/> <label for="register_<?php echo $val; ?>"><?php echo $options['user_attribute']['user_attribute'][$i]['label']; ?></label> <input type="hidden" name="register_order[]" value="<?php echo $val; ?>" class="none" /></li>
<?php								break;
								endif;
							endfor;
					endswitch;
				endforeach;
			endif;
?>
</ul>
</div>
<div style="width:45%; float:left;">
<p><?php _e('Profile Item and Order', 'frontend-user-admin'); ?>:</p>
<ul id="sortable_profile_usermeta">
<?php
			if ( !empty($options['global_settings']["profile_order"]) && is_array($options['global_settings']["profile_order"]) ) :
				foreach( $options['global_settings']["profile_order"] as $val ) :
					switch ( $val ) :
						case "user_login": ?>
<li><input type="checkbox" name="profile_user_login" id="profile_user_login" value="1" <?php if ( !empty($options['global_settings']["profile_user_login"]) ) echo 'checked="checked"'; ?>/> <label for="profile_user_login"><?php _e('Username', 'frontend-user-admin'); ?></label> <?php _e('(Default)', 'frontend-user-admin'); ?><input type="hidden" name="profile_order[]" value="user_login" class="none" /></li>
<?php					break;
						case "first_name": ?>
<li><input type="checkbox" name="profile_first_name" id="profile_first_name" value="1" <?php if ( !empty($options['global_settings']["profile_first_name"]) ) echo 'checked="checked"'; ?>/> <label for="profile_first_name"><?php _e('First name', 'frontend-user-admin'); ?></label> <?php _e('(Default)', 'frontend-user-admin'); ?> <label><input type="checkbox" name="profile_first_name_admin" id="profile_first_name_admin" value="1" <?php if ( !empty($options['global_settings']['profile_first_name_admin']) ) echo 'checked="checked"'; ?> /> <?php _e('Admin', 'frontend-user-admin'); ?></label> <label><input type="checkbox" name="profile_first_name_required" id="profile_first_name_required" value="1" <?php if ( !empty($options['global_settings']['profile_first_name_required']) ) echo 'checked="checked"'; ?> /> <?php _e('Required', 'frontend-user-admin'); ?></label><input type="hidden" name="profile_order[]" value="first_name" class="none" /></li>
<?php					break;
						case "last_name": ?>
<li><input type="checkbox" name="profile_last_name" id="profile_last_name" value="1" <?php if ( !empty($options['global_settings']["profile_last_name"]) ) echo 'checked="checked"'; ?>/> <label for="profile_last_name"><?php _e('Last name', 'frontend-user-admin'); ?></label> <?php _e('(Default)', 'frontend-user-admin'); ?> <label><input type="checkbox" name="profile_last_name_admin" id="profile_last_name_admin" value="1" <?php if ( !empty($options['global_settings']['profile_last_name_admin']) ) echo 'checked="checked"'; ?> /> <?php _e('Admin', 'frontend-user-admin'); ?></label> <label><input type="checkbox" name="profile_last_name_required" id="profile_last_name_required" value="1" <?php if ( !empty($options['global_settings']['profile_last_name_required']) ) echo 'checked="checked"'; ?> /> <?php _e('Required', 'frontend-user-admin'); ?></label><input type="hidden" name="profile_order[]" value="last_name" class="none" /></li>
<?php					break;
						case "nickname": ?>
<li><input type="checkbox" name="profile_nickname" id="profile_nickname" value="1" <?php if ( !empty($options['global_settings']["profile_nickname"]) ) echo 'checked="checked"'; ?>/> <label for="profile_nickname"><?php _e('Nickname', 'frontend-user-admin'); ?></label> <?php _e('(Default)', 'frontend-user-admin'); ?> <label><input type="checkbox" name="profile_nickname_admin" id="profile_nickname_admin" value="1" <?php if ( !empty($options['global_settings']['profile_nickname_admin']) ) echo 'checked="checked"'; ?> /> <?php _e('Admin', 'frontend-user-admin'); ?></label> <label><input type="checkbox" name="profile_nickname_required" id="profile_nickname_required" value="1" <?php if ( !empty($options['global_settings']['profile_nickname_required']) ) echo 'checked="checked"'; ?> /> <?php _e('Required', 'frontend-user-admin'); ?></label><input type="hidden" name="profile_order[]" value="nickname" class="none" /></li>
<?php					break;
						case "display_name": ?>
<li><input type="checkbox" name="profile_display_name" id="profile_display_name" value="1" <?php if ( !empty($options['global_settings']["profile_display_name"]) ) echo 'checked="checked"'; ?>/> <label for="profile_display_name"><?php _e('Display name publicly&nbsp;as', 'frontend-user-admin'); ?></label> <?php _e('(Default)', 'frontend-user-admin'); ?> <label><input type="checkbox" name="profile_display_name_admin" id="profile_display_name_admin" value="1" <?php if ( !empty($options['global_settings']['profile_display_name_admin']) ) echo 'checked="checked"'; ?> /> <?php _e('Admin', 'frontend-user-admin'); ?></label> <label><input type="checkbox" name="profile_display_name_required" id="profile_display_name_required" value="1" <?php if ( !empty($options['global_settings']['profile_display_name_required']) ) echo 'checked="checked"'; ?> /> <?php _e('Required', 'frontend-user-admin'); ?></label><input type="hidden" name="profile_order[]" value="display_name" class="none" /></li>
<?php					break;
						case "user_email": ?>
<li><input type="checkbox" name="profile_user_email" id="profile_user_email" value="1" <?php if ( !empty($options['global_settings']["profile_user_email"]) ) echo 'checked="checked"'; ?>/> <label for="profile_user_email"><?php _e('E-mail', 'frontend-user-admin'); ?></label> <?php _e('(Default)', 'frontend-user-admin'); ?> <label><input type="checkbox" name="profile_user_email_admin" id="profile_user_email_admin" value="1" <?php if ( !empty($options['global_settings']['profile_user_email_admin']) ) echo 'checked="checked"'; ?> /> <?php _e('Admin', 'frontend-user-admin'); ?></label><input type="hidden" name="profile_order[]" value="user_email" class="none" /></li>
<?php					break;
						case "user_url": ?>
<li><input type="checkbox" name="profile_user_url" id="profile_user_url" value="1" <?php if ( !empty($options['global_settings']["profile_user_url"]) ) echo 'checked="checked"'; ?>/> <label for="profile_user_url"><?php _e('Website', 'frontend-user-admin'); ?></label> <?php _e('(Default)', 'frontend-user-admin'); ?> <label><input type="checkbox" name="profile_user_url_admin" id="profile_user_url_admin" value="1" <?php if ( !empty($options['global_settings']['profile_user_url_admin']) ) echo 'checked="checked"'; ?> /> <?php _e('Admin', 'frontend-user-admin'); ?></label> <label><input type="checkbox" name="profile_user_url_required" id="profile_user_url_required" value="1" <?php if ( !empty($options['global_settings']['profile_user_url_required']) ) echo 'checked="checked"'; ?> /> <?php _e('Required', 'frontend-user-admin'); ?></label><input type="hidden" name="profile_order[]" value="user_url" class="none" /></li>
<?php					break;
						case "aim": ?>
<li><input type="checkbox" name="profile_aim" id="profile_aim" value="1" <?php if ( !empty($options['global_settings']["profile_aim"]) ) echo 'checked="checked"'; ?>/> <label for="profile_aim"><?php _e('AIM', 'frontend-user-admin'); ?></label> <?php _e('(Default)', 'frontend-user-admin'); ?> <label><input type="checkbox" name="profile_aim_admin" id="profile_aim_admin" value="1" <?php if ( !empty($options['global_settings']['profile_aim_admin']) ) echo 'checked="checked"'; ?> /> <?php _e('Admin', 'frontend-user-admin'); ?></label> <label><input type="checkbox" name="profile_aim_required" id="profile_aim_required" value="1" <?php if ( !empty($options['global_settings']['profile_aim_required']) ) echo 'checked="checked"'; ?> /> <?php _e('Required', 'frontend-user-admin'); ?></label><input type="hidden" name="profile_order[]" value="aim" class="none" /></li>
<?php					break;
						case "yim": ?>
<li><input type="checkbox" name="profile_yim" id="profile_yim" value="1" <?php if ( !empty($options['global_settings']["profile_yim"]) ) echo 'checked="checked"'; ?>/> <label for="profile_yim"><?php _e('Yahoo IM', 'frontend-user-admin'); ?></label> <?php _e('(Default)', 'frontend-user-admin'); ?> <label><input type="checkbox" name="profile_yim_admin" id="profile_yim_admin" value="1" <?php if ( !empty($options['global_settings']['profile_yim_admin']) ) echo 'checked="checked"'; ?> /> <?php _e('Admin', 'frontend-user-admin'); ?></label> <label><input type="checkbox" name="profile_yim_required" id="profile_yim_required" value="1" <?php if ( !empty($options['global_settings']['profile_yim_required']) ) echo 'checked="checked"'; ?> /> <?php _e('Required', 'frontend-user-admin'); ?></label><input type="hidden" name="profile_order[]" value="yim" class="none" /></li>
<?php					break;
						case "jabber": ?>
<li><input type="checkbox" name="profile_jabber" id="profile_jabber" value="1" <?php if ( !empty($options['global_settings']["profile_jabber"]) ) echo 'checked="checked"'; ?>/> <label for="profile_jabber"><?php _e('Jabber / Google Talk', 'frontend-user-admin'); ?></label> <?php _e('(Default)', 'frontend-user-admin'); ?> <label><input type="checkbox" name="profile_jabber_admin" id="profile_jabber_admin" value="1" <?php if ( !empty($options['global_settings']['profile_jabber_admin']) ) echo 'checked="checked"'; ?> /> <?php _e('Admin', 'frontend-user-admin'); ?></label> <label><input type="checkbox" name="profile_jabber_required" id="profile_jabber_required" value="1" <?php if ( !empty($options['global_settings']['profile_jabber_required']) ) echo 'checked="checked"'; ?> /> <?php _e('Required', 'frontend-user-admin'); ?></label><input type="hidden" name="profile_order[]" value="jabber" class="none" /></li>
<?php					break;
						case "description": ?>
<li><input type="checkbox" name="profile_description" id="profile_description" value="1" <?php if ( !empty($options['global_settings']["profile_description"]) ) echo 'checked="checked"'; ?>/> <label for="profile_description"><?php _e('Biographical Info', 'frontend-user-admin'); ?></label> <?php _e('(Default)', 'frontend-user-admin'); ?> <label><input type="checkbox" name="profile_description_admin" id="profile_description_admin" value="1" <?php if ( !empty($options['global_settings']['profile_description_admin']) ) echo 'checked="checked"'; ?> /> <?php _e('Admin', 'frontend-user-admin'); ?></label> <label><input type="checkbox" name="profile_description_required" id="profile_description_required" value="1" <?php if ( !empty($options['global_settings']['profile_description_required']) ) echo 'checked="checked"'; ?> /> <?php _e('Required', 'frontend-user-admin'); ?></label><input type="hidden" name="profile_order[]" value="description" class="none" /></li>
<?php					break;
						case "role": ?>
<li><input type="checkbox" name="profile_role" id="profile_role" value="1" <?php if ( !empty($options['global_settings']["profile_role"]) ) echo 'checked="checked"'; ?>/> <label for="profile_role"><?php _e('Role', 'frontend-user-admin'); ?></label> <?php _e('(Default)', 'frontend-user-admin'); ?><input type="hidden" name="profile_order[]" value="role" class="none" /></li>
<?php					break;
						case "user_status": ?>
<li><input type="checkbox" name="profile_user_status" id="profile_user_status" value="1" <?php if( !empty($options['global_settings']["profile_user_status"]) ) echo 'checked="checked"'; ?>/> <label for="profile_user_status"><?php _e('User status', 'frontend-user-admin'); ?></label> <?php _e('(Default)', 'frontend-user-admin'); ?><input type="hidden" name="profile_order[]" value="user_status" class="none" /></li>
<?php					break;
						case "no_log": ?>
<li><input type="checkbox" name="profile_no_log" id="profile_no_log" value="1" <?php if( !empty($options['global_settings']["profile_no_log"]) ) echo 'checked="checked"'; ?>/> <label for="profile_no_log"><?php _e('No log', 'frontend-user-admin'); ?></label> <?php _e('(Default)', 'frontend-user-admin'); ?><input type="hidden" name="profile_order[]" value="no_log" class="none" /></li>
<?php					break;
						case "duplicate_login": ?>
<li><input type="checkbox" name="profile_duplicate_login" id="profile_duplicate_login" value="1" <?php if( !empty($options['global_settings']["profile_duplicate_login"]) ) echo 'checked="checked"'; ?>/> <label for="profile_duplicate_login"><?php _e('Duplicate login', 'frontend-user-admin'); ?></label> <?php _e('(Default)', 'frontend-user-admin'); ?><input type="hidden" name="profile_order[]" value="duplicate_login" class="none" /></li>
<?php					break;
						case "user_pass": ?>
<li><input type="checkbox" name="profile_user_pass" id="profile_user_pass" value="1" <?php if ( !empty($options['global_settings']["profile_user_pass"]) ) echo 'checked="checked"'; ?>/> <label for="profile_user_pass"><?php _e('New Password', 'frontend-user-admin'); ?></label> <?php _e('(Default)', 'frontend-user-admin'); ?> <input type="hidden" name="profile_order[]" value="user_pass" class="none" /></li>
<?php					break;
						case "ms_domain": ?>
<li><input type="checkbox" name="profile_ms_domain" id="profile_ms_domain" value="1" <?php if( !empty($options['global_settings']["profile_ms_domain"]) ) echo 'checked="checked"'; ?>/> <label for="profile_ms_domain"><?php _e('Site Domain', 'frontend-user-admin'); ?></label> <?php _e('(Multi Site)', 'frontend-user-admin'); ?> <input type="hidden" name="profile_order[]" value="ms_domain" class="none" /></li>
<?php					break;
						case "ms_title": ?>
<li><input type="checkbox" name="profile_ms_title" id="profile_ms_title" value="1" <?php if( !empty($options['global_settings']["profile_ms_title"]) ) echo 'checked="checked"'; ?>/> <label for="profile_ms_title"><?php _e('Site Title', 'frontend-user-admin'); ?></label> <?php _e('(Multi Site)', 'frontend-user-admin'); ?> <input type="hidden" name="profile_order[]" value="ms_title" class="none" /></li>
<?php					break;
						default:
							if ( !empty($options['user_attribute']['user_attribute']) ) $count_user_attribute = count($options['user_attribute']['user_attribute']);
							else $count_user_attribute = 0;

							for($i=0;$i<$count_user_attribute;$i++) :
								if($options['user_attribute']['user_attribute'][$i]['name'] == $val) : ?>
<li><input type="checkbox" name="profile_<?php echo $val; ?>" id="profile_<?php echo $val; ?>" value="1" <?php if( !empty($options['global_settings']["profile_".$val]) ) echo 'checked="checked"'; ?>/> <label for="profile_<?php echo $val; ?>"><?php echo $options['user_attribute']['user_attribute'][$i]['label']; ?></label> <input type="hidden" name="profile_order[]" value="<?php echo $val; ?>" class="none" /></li>
<?php								break;
								endif;
							endfor;
					endswitch;
				endforeach;
			endif;
?>
</ul>
</div>
</td></tr>
<tr><td>
<p><label for="nickname_as_display_name"><?php _e('Set Nickname as Display Name when registering.', 'frontend-user-admin'); ?></label>:<br />
<input type="checkbox" name="nickname_as_display_name" id="nickname_as_display_name" value="1" <?php if ( !empty($options['global_settings']["nickname_as_display_name"]) ) echo 'checked="checked"'; ?>/> <?php _e('Please check if you want users to set their nicknames as display name as default. Nickname will be a necessary attribute.', 'frontend-user-admin'); ?></label></p></td></tr>
<tr><td>
<p><label for="terms_of_use_check"><?php _e('Terms of use', 'frontend-user-admin'); ?>:<br />
<input type="checkbox" name="terms_of_use_check" id="terms_of_use_check" value="1" <?php if ( !empty($options['global_settings']["terms_of_use_check"]) ) echo 'checked="checked"'; ?>/> <?php _e('Let users approve the terms of use.', 'frontend-user-admin'); ?></label></p></td></tr>
<tr><td>
<p><label for="terms_of_use_url"><?php _e('Terms of use URL', 'frontend-user-admin'); ?></label>:<br />
<input type="text" name="terms_of_use_url" id="terms_of_use_url" value="<?php if ( !empty($options['global_settings']["terms_of_use_url"]) ) echo esc_attr($options['global_settings']["terms_of_use_url"]); ?>" size="60" class="regular-text imedisabled" /></p>
</td></tr>
<tr><td>
<p><label for="name_of_terms"><?php _e('The name of Terms', 'frontend-user-admin'); ?></label>:<br />
<input type="text" name="name_of_terms" id="name_of_terms" value="<?php if ( !empty($options['global_settings']["name_of_terms"]) ) echo esc_attr($options['global_settings']["name_of_terms"]); ?>" size="20" class="regular-text" /></p>
</td></tr>
<tr><td>
<p><label for="use_bulk_user_update_code"><?php _e('Bulk User Update Code', 'frontend-user-admin'); ?>:<br />
<input type="checkbox" name="use_bulk_user_update_code" id="use_bulk_user_update_code" value="1" <?php if( !empty($options['global_settings']['use_bulk_user_update_code']) ) { echo 'checked="checked"';} ?>/> <?php _e('Use the bulk user update code in the user list.', 'frontend-user-admin'); ?></label></p>
</td></tr>
<tr><td>
<p><label for="admin_css"><?php _e('Admin CSS', 'frontend-user-admin'); ?>:<br />
<textarea name="admin_css" id="admin_css" rows="3"><?php if ( !empty($options['global_settings']['admin_css']) ) echo htmlspecialchars($options['global_settings']['admin_css']); ?></textarea></label></p>
</td></tr>
<tr><td>
<p><label for="admin_javascript"><?php _e('Admin JavaScript', 'frontend-user-admin'); ?>:<br />
<textarea name="admin_javascript" id="admin_javascript" rows="3"><?php if ( !empty($options['global_settings']['admin_javascript']) ) echo htmlspecialchars($options['global_settings']['admin_javascript']); ?></textarea></label></p>
</td></tr>
<?php if ( current_user_can('administrator') || $current_user->user_level >= 8 ) :  ?>
<tr><td>
<p><label for="admin_demo"><?php _e('Admin Demo Mode', 'frontend-user-admin'); ?>:<br />
<input type="checkbox" name="admin_demo" value="1" <?php if ( !empty($options['global_settings']['admin_demo']) ) echo 'checked="checked"'; ?> /> <?php _e('Please check if you make the admin panel under the demo.', 'frontend-user-admin'); ?></label></p>
</td></tr>
<tr>
<td><p><label for="plugin_role"><?php _e('Role of this plugin', 'frontend-user-admin'); ?></label>
<select name="plugin_role" id="plugin_role">
<?php
if ( empty($new_user_role) )
	$new_user_role = !empty($options['global_settings']['plugin_role']) ? $options['global_settings']['plugin_role'] : 'administrator';
	wp_dropdown_roles($new_user_role);
?>
</select></p></td></tr>
<?php
		if ( empty($options['global_settings']['plugin_role']) ) :
			if ( is_array($options) && is_array($options['global_settings']) && !isset($options['global_settings']['plugin_user_level']) ) $options['global_settings']['plugin_user_level'] = 8;
?>
<tr>
<td>
<p><label for="plugin_user_level"><?php _e('User Level of this plugin', 'frontend-user-admin'); ?>:<br />
<select name="plugin_user_level" id="plugin_user_level">
<?php
			for ( $i=0; $i<11; $i++ ) :
?>
<option value="<?php echo $i; ?>"<?php if ( isset($options['global_settings']['plugin_user_level']) ) selected($options['global_settings']['plugin_user_level'], $i); ?>><?php echo $i; ?></option>
<?php
			endfor;
?>
</select> <?php _e('and above', 'frontend-user-admin'); ?></label></p>
</td></tr>
<?php
		endif;
?>
<tr><td>
<p><?php _e('Please select the ability which the user role of this plugin can have. The admin user can manage everything.', 'frontend-user-admin'); ?></p>
<p>
<label for="plugin_user_menu_user_list"><input type="checkbox" name="plugin_user_menu_user_list" value="1" id="plugin_user_menu_user_list"<?php if ( isset($options['global_settings']['plugin_user_menu_user_list']) ) checked('1', $options['global_settings']['plugin_user_menu_user_list']); ?> /> <?php _e('User List', 'frontend-user-admin'); ?></label>
<label for="plugin_user_menu_add_user"><input type="checkbox" name="plugin_user_menu_add_user" value="1" id="plugin_user_menu_add_user"<?php if ( isset($options['global_settings']['plugin_user_menu_add_user']) ) checked('1', $options['global_settings']['plugin_user_menu_add_user']); ?> /> <?php _e('Add User', 'frontend-user-admin'); ?></label>
<label for="plugin_user_menu_user_mail"><input type="checkbox" name="plugin_user_menu_user_mail" value="1" id="plugin_user_menu_user_mail"<?php if ( isset($options['global_settings']['plugin_user_menu_user_mail']) ) checked('1', $options['global_settings']['plugin_user_menu_user_mail']); ?> /> <?php _e('User Mail', 'frontend-user-admin'); ?></label>
<label for="plugin_user_menu_user_log"><input type="checkbox" name="plugin_user_menu_user_log" value="1" id="plugin_user_menu_user_log"<?php if ( isset($options['global_settings']['plugin_user_menu_user_log']) ) checked('1', $options['global_settings']['plugin_user_menu_user_log']); ?> /> <?php _e('User Log', 'frontend-user-admin'); ?></label>
<label for="plugin_user_menu_import_user"><input type="checkbox" name="plugin_user_menu_import_user" value="1" id="plugin_user_menu_import_user"<?php if ( isset($options['global_settings']['plugin_user_menu_import_user']) ) checked('1', $options['global_settings']['plugin_user_menu_import_user']); ?> /> <?php _e('Import User', 'frontend-user-admin'); ?></label>
<label for="plugin_user_menu_options"><input type="checkbox" name="plugin_user_menu_options" value="1" id="plugin_user_menu_options"<?php if ( isset($options['global_settings']['plugin_user_menu_options']) ) checked('1', $options['global_settings']['plugin_user_menu_options']); ?> /> <?php _e('Options', 'frontend-user-admin'); ?></label>
</p>
</td></tr>
<?php
	else :
?>
<input type="hidden" name="admin_demo" value="<?php echo $options['global_settings']['admin_demo']; ?>" />
<input type="hidden" name="plugin_role" value="<?php echo $options['global_settings']['plugin_role']; ?>" />
<?php
	endif;
?>
<tr><td>
<p><input type="submit" name="global_settings_submit" value="<?php _e('Update Options &raquo;', 'frontend-user-admin'); ?>" class="button-primary" /></p>
</td></tr>
</tbody>
</table>
</form>
<script type="text/javascript">
//<![CDATA[
	jQuery(document).ready(function () {
		jQuery("#sortable_register_usermeta").sortable({});
		jQuery("#sortable_profile_usermeta").sortable({});
	});
//]]>
</script>
</div>
</div>

<div class="postbox<?php if ( !isset($_GET["open"]) || (isset($_GET["open"]) && $_GET["open"] != 'user_attribute') ) echo ' closed'; ?>">
<div class="handlediv" title="<?php _e('Click to toggle', 'frontend-user-admin'); ?>"><br /></div>
<h3><?php _e('Add User Attribute', 'frontend-user-admin'); ?></h3>
<div class="inside">
<form method="post" action="?page=frontend-user-admin/frontend-user-admin-settings.php">
<table class="form-table" style="margin-bottom:5px;">
<thead>
<tr>
<th><?php _e('Delete', 'frontend-user-admin'); ?></th>
<th><?php _e('Field Label', 'frontend-user-admin'); ?></th>
<th><?php _e('Field Name', 'frontend-user-admin'); ?></th>
<th><?php _e('Field Type', 'frontend-user-admin'); ?></th>
<th><?php _e('Field Type Admin', 'frontend-user-admin'); ?></th>
<th><?php _e('Default Value', 'frontend-user-admin'); ?></th>
<th><?php _e('Comment', 'frontend-user-admin'); ?></th>
<th><?php _e('Admin', 'frontend-user-admin'); ?></th>
<th><?php _e('Required', 'frontend-user-admin'); ?></th>
<th><?php _e('Condition', 'frontend-user-admin'); ?></th>
<th><?php _e('Min Letters', 'frontend-user-admin'); ?></th>
<th><?php _e('Max Letters', 'frontend-user-admin'); ?></th>
<th></th>
</tr>
</thead>
<tbody id="sortable_user_attribute">
<?php
		$locale = get_locale();
		if ( empty($options['user_attribute']['user_attribute']) ) $count_user_attribute = 0;
		else $count_user_attribute = count($options['user_attribute']['user_attribute']);
		for($i=0;$i<$count_user_attribute+1;$i++) :
?>
<tr>
<td><input type="hidden" name="delete[]" value="0" /><input type="checkbox" name="delete[]" value="1" onclick="if (jQuery(this).attr('checked')==true || jQuery(this).attr('checked')=='checked') {jQuery(this).prev().attr('disabled', true);}else{jQuery(this).prev().attr('disabled', false);}" /></td>
<td><input type="text" name="label[]" class="admin_input" value="<?php if( !empty($options['user_attribute']['user_attribute'][$i]["label"]) ) echo esc_attr($options['user_attribute']['user_attribute'][$i]["label"]); ?>" size="8" /></td>
<td><input type="text" name="name[]" class="admin_input imedisabled" value="<?php if( !empty($options['user_attribute']['user_attribute'][$i]["name"]) ) echo esc_attr($options['user_attribute']['user_attribute'][$i]["name"]); ?>" size="8" /></td>
<td><select name="type[]">
<?php
				$type = array("display", "text", "textarea", "select", "checkbox", "radio", "datetime", "breakpoint", "hidden", "file");
				foreach($type as $val) :
?>
<option value="<?php echo $val; ?>"<?php if( isset($options['user_attribute']['user_attribute'][$i]["type"]) && $val==$options['user_attribute']['user_attribute'][$i]["type"] ) : echo ' selected="selected"'; elseif( empty($options['user_attribute']['user_attribute'][$i]["type"]) && $val=='text') : echo ' selected="selected"'; endif;?>><?php echo $val; ?></option>
<?php
				endforeach;
?>
</select></td>
<td><select name="type2[]">
<?php
				$type = array("display", "text", "textarea", "select", "checkbox", "radio", "datetime", "breakpoint", "hidden", "file");
				foreach($type as $val) :
?>
<option value="<?php echo $val; ?>"<?php if( isset($options['user_attribute']['user_attribute'][$i]["type2"]) && $val==$options['user_attribute']['user_attribute'][$i]["type2"] ) : echo ' selected="selected"'; endif;?>><?php echo $val; ?></option>
<?php
				endforeach;
?>
</select></td>
<td><input type="text" name="default[]" class="admin_input" value="<?php if( !empty($options['user_attribute']['user_attribute'][$i]["default"]) ) echo esc_attr($options['user_attribute']['user_attribute'][$i]["default"]); ?>" size="8" /></td>
<td><input type="text" name="comment[]" class="admin_input" value="<?php if( !empty($options['user_attribute']['user_attribute'][$i]["comment"]) ) echo esc_attr($options['user_attribute']['user_attribute'][$i]["comment"]); ?>" size="8" /></td>
<td><input type="hidden" name="admin[]" value="0" <?php if ( !empty($options['user_attribute']['user_attribute'][$i]["admin"]) ) echo 'disabled="disabled"'; ?> /><input type="checkbox" name="admin[]" value="1" <?php if( !empty($options['user_attribute']['user_attribute'][$i]["admin"]) ) echo ' checked="checked"'; ?> onclick="if (jQuery(this).attr('checked')==true || jQuery(this).attr('checked')=='checked') {jQuery(this).prev().attr('disabled', true);}else{jQuery(this).prev().attr('disabled', false);}" /></td>
<td><input type="hidden" name="required[]" value="0" <?php if ( !empty($options['user_attribute']['user_attribute'][$i]["required"]) ) echo 'disabled="disabled"'; ?> /><input type="checkbox" name="required[]" value="1" <?php if( !empty($options['user_attribute']['user_attribute'][$i]["required"]) ) echo ' checked="checked"'; ?> onclick="if (jQuery(this).attr('checked')==true || jQuery(this).attr('checked')=='checked') {jQuery(this).prev().attr('disabled', true);}else{jQuery(this).prev().attr('disabled', false);}" /></td>
<td><select name="condition[]">
<?php
				$type = array( "" => "", "numeric" => __('Numeric', 'frontend-user-admin'), "alphabet" => __('Alphabet', 'frontend-user-admin'), "alphanumeric" => __('Alphanumeric', 'frontend-user-admin'), "half-width" => __('Half width', 'frontend-user-admin'), "email" => __('Email', 'frontend-user-admin') );
				if ( $locale == 'ja' ) :
					$type['hiragana'] = __('Hiragana', 'frontend-user-admin');
					$type['katakana'] = __('Katakana', 'frontend-user-admin');
				endif;
				foreach($type as $key => $val) :
?>
<option value="<?php echo $key; ?>"<?php if( isset($options['user_attribute']['user_attribute'][$i]["condition"]) && $key==$options['user_attribute']['user_attribute'][$i]["condition"]) : echo ' selected="selected"'; endif;?>><?php echo $val; ?></option>
<?php
				endforeach;
?>
</select></td>
<td><input type="text" name="min_letters[]" value="<?php echo !empty($options['user_attribute']['user_attribute'][$i]["min_letters"]) ? (int)$options['user_attribute']['user_attribute'][$i]["min_letters"] : ''; ?>" class="small-text" /></td>
<td><input type="text" name="max_letters[]" value="<?php echo !empty($options['user_attribute']['user_attribute'][$i]["max_letters"]) ? (int)$options['user_attribute']['user_attribute'][$i]["max_letters"] : ''; ?>" class="small-text" /></td>
<td><a href="admin.php#TB_inline?inlineId=additional_settings<?php echo $i; ?>&amp;width=640&amp;height=540" class="thickbox"<?php if ( !empty($options['user_attribute']['user_attribute'][$i]["placeholder"]) || !empty($options['user_attribute']['user_attribute'][$i]["overwrite_php"]) || !empty($options['user_attribute']['user_attribute'][$i]["cast"]) || !empty($options['user_attribute']['user_attribute'][$i]["publicity"]) || !empty($options['user_attribute']['user_attribute'][$i]["retrieve_password"]) || !empty($options['user_attribute']['user_attribute'][$i]["unique"]) || !empty($options['user_attribute']['user_attribute'][$i]["log"]) || !empty($options['user_attribute']['user_attribute'][$i]["readonly"]) || !empty($options['user_attribute']['user_attribute'][$i]["disabled"]) ) echo '" style="color:#FF0000;"'; ?>>+</a><div id="additional_settings<?php echo $i; ?>" style="display:none;">
<p><?php _e('Please input the placeholder.', 'frontend-user-admin'); ?></p><p><textarea name="placeholder[]" rows="2" class="large-text"><?php if ( isset($options['user_attribute']['user_attribute'][$i]["placeholder"]) ) echo htmlspecialchars($options['user_attribute']['user_attribute'][$i]["placeholder"]); ?></textarea></p>
<p><?php _e('Please input php codes overwritting the output.', 'frontend-user-admin'); ?></p><p><textarea name="overwrite_php[]" rows="2" class="large-text"><?php if ( isset($options['user_attribute']['user_attribute'][$i]["overwrite_php"]) ) echo htmlspecialchars($options['user_attribute']['user_attribute'][$i]["overwrite_php"]); ?></textarea></p>
<p><?php _e('CAST', 'frontend-user-admin'); ?>: <select name="cast[]">
<?php
				$type = array('', 'binary', 'char', 'date', 'datetime', 'decimal', 'signed', 'time', 'unsigned');
				foreach($type as $val) :
?>
<option value="<?php echo $val; ?>"<?php if( isset($options['user_attribute']['user_attribute'][$i]["cast"]) && $val==$options['user_attribute']['user_attribute'][$i]["cast"]) : echo ' selected="selected"'; endif;?>><?php echo $val; ?></option>
<?php
				endforeach;
?>
</select></p>
<p><?php _e('Publicity Settings', 'frontend-user-admin'); ?>: <input type="hidden" name="publicity[]" value="0" <?php if ( !empty($options['user_attribute']['user_attribute'][$i]["publicity"]) ) echo 'disabled="disabled"'; ?> /><input type="checkbox" name="publicity[]" value="1" <?php if( !empty($options['user_attribute']['user_attribute'][$i]["publicity"]) ) echo ' checked="checked"'; ?> onclick="if (jQuery(this).attr('checked')==true || jQuery(this).attr('checked')=='checked') {jQuery(this).prev().attr('disabled', true);}else{jQuery(this).prev().attr('disabled', false);}" />
<?php _e('Pass', 'frontend-user-admin'); ?>: <input type="hidden" name="retrieve_password[]" value="0" <?php if ( !empty($options['user_attribute']['user_attribute'][$i]["retrieve_password"]) ) echo 'disabled="disabled"'; ?> /><input type="checkbox" name="retrieve_password[]" value="1" <?php if( !empty($options['user_attribute']['user_attribute'][$i]["retrieve_password"]) ) echo ' checked="checked"'; ?> onclick="if (jQuery(this).attr('checked')==true || jQuery(this).attr('checked')=='checked') {jQuery(this).prev().attr('disabled', true);}else{jQuery(this).prev().attr('disabled', false);}" />
<?php _e('Unique', 'frontend-user-admin'); ?>: <input type="hidden" name="unique[]" value="0" <?php if ( !empty($options['user_attribute']['user_attribute'][$i]["unique"]) ) echo 'disabled="disabled"'; ?> /><input type="checkbox" name="unique[]" value="1" <?php if( !empty($options['user_attribute']['user_attribute'][$i]["unique"]) ) echo ' checked="checked"'; ?> onclick="if (jQuery(this).attr('checked')==true || jQuery(this).attr('checked')=='checked') {jQuery(this).prev().attr('disabled', true);}else{jQuery(this).prev().attr('disabled', false);}" />
<?php _e('Log', 'frontend-user-admin'); ?>: <input type="hidden" name="log[]" value="0" <?php if ( !empty($options['user_attribute']['user_attribute'][$i]["log"]) ) echo 'disabled="disabled"'; ?> /><input type="checkbox" name="log[]" value="1" <?php if( !empty($options['user_attribute']['user_attribute'][$i]["log"]) ) echo ' checked="checked"'; ?> onclick="if (jQuery(this).attr('checked')==true || jQuery(this).attr('checked')=='checked') {jQuery(this).prev().attr('disabled', true);}else{jQuery(this).prev().attr('disabled', false);}" />
<?php _e('readonly', 'frontend-user-admin'); ?>: <input type="hidden" name="readonly[]" value="0" <?php if ( !empty($options['user_attribute']['user_attribute'][$i]["readonly"]) ) echo 'disabled="disabled"'; ?> /><input type="checkbox" name="readonly[]" value="1" <?php if( !empty($options['user_attribute']['user_attribute'][$i]["readonly"]) ) echo ' checked="checked"'; ?> onclick="if (jQuery(this).attr('checked')==true || jQuery(this).attr('checked')=='checked') {jQuery(this).prev().attr('disabled', true);}else{jQuery(this).prev().attr('disabled', false);}" />
<?php _e('disabled', 'frontend-user-admin'); ?>: <input type="hidden" name="disabled[]" value="0" <?php if ( !empty($options['user_attribute']['user_attribute'][$i]["disabled"]) ) echo 'disabled="disabled"'; ?> /><input type="checkbox" name="disabled[]" value="1" <?php if( !empty($options['user_attribute']['user_attribute'][$i]["disabled"]) ) echo ' checked="checked"'; ?> onclick="if (jQuery(this).attr('checked')==true || jQuery(this).attr('checked')=='checked') {jQuery(this).prev().attr('disabled', true);}else{jQuery(this).prev().attr('disabled', false);}" />
<?php _e('Composite Unique', 'frontend-user-admin'); ?>: <input type="hidden" name="composite_unique[]" value="0" <?php if ( !empty($options['user_attribute']['user_attribute'][$i]["composite_unique"]) ) echo 'disabled="disabled"'; ?> /><input type="checkbox" name="composite_unique[]" value="1" <?php if( !empty($options['user_attribute']['user_attribute'][$i]["composite_unique"]) ) echo ' checked="checked"'; ?> onclick="if (jQuery(this).attr('checked')==true || jQuery(this).attr('checked')=='checked') {jQuery(this).prev().attr('disabled', true);}else{jQuery(this).prev().attr('disabled', false);}" /></p>
</div></td>
</tr>
<?php
		endfor;
?>
</tbody>
<tfoot>
<tr><td colspan="13">
<p><input type="submit" name="add_user_attribute_submit" value="<?php _e('Update Options &raquo;', 'frontend-user-admin'); ?>" class="button-primary" /></p></td>
</tr>
</tfoot>
</table>
</form>
<script type="text/javascript">
//<![CDATA[
	jQuery(document).ready(function () {
		jQuery("#sortable_user_attribute").sortable({});
	});
//]]>
</script>
</div>
</div>

<div class="postbox<?php if ( !isset($_GET["open"]) || (isset($_GET["open"]) && $_GET["open"] != 'widget_menu') ) echo ' closed'; ?>">
<div class="handlediv" title="<?php _e('Click to toggle', 'frontend-user-admin'); ?>"><br /></div>
<h3><?php _e('Add Widget Menu', 'frontend-user-admin'); ?></h3>
<div class="inside">
<form method="post" action="?page=frontend-user-admin/frontend-user-admin-settings.php">
<table class="form-table" style="margin-bottom:5px;">
<tbody id="sortable_widget_menu">
<?php
		if ( empty($options['widget_menu']) ) $count_widget_menu = 0;
		else $count_widget_menu = count($options['widget_menu']);
		for($i=0;$i<$count_widget_menu+1;$i++) :
?>
<tr>
<td><p><label for="widget_menu_label_<?php echo $i; ?>"><?php _e('Menu Label', 'frontend-user-admin'); ?></label><br /><input type="text" name="widget_menu_label[]" id="widget_menu_label_<?php echo $i; ?>" value="<?php if ( !empty($options['widget_menu'][$i]['widget_menu_label']) ) echo esc_attr($options['widget_menu'][$i]['widget_menu_label']); ?>" size="60" /></p>
<p><label for="widget_menu_url_<?php echo $i; ?>"><?php _e('Menu URL', 'frontend-user-admin'); ?></label><br /><input type="text" name="widget_menu_url[]" id="widget_menu_url_<?php echo $i; ?>" value="<?php if ( !empty($options['widget_menu'][$i]['widget_menu_url']) ) echo esc_attr($options['widget_menu'][$i]['widget_menu_url']); ?>" size="60" class="imedisabled" /></p>
<p><label for="widget_menu_blank_<?php echo $i; ?>"><?php _e('Menu target="_blank"', 'frontend-user-admin'); ?></label><br /><input type="hidden" name="widget_menu_blank[]" value="0" <?php if ( !empty($options['widget_menu'][$i]['widget_menu_blank']) ) echo 'disabled="disabled"'; ?> /><input type="checkbox" name="widget_menu_blank[]" id="widget_menu_blank_<?php echo $i; ?>" value="1" <?php if ( !empty($options['widget_menu'][$i]['widget_menu_blank']) ) echo ' checked="checked"'; ?> onclick="if (jQuery(this).attr('checked')==true || jQuery(this).attr('checked')=='checked') {jQuery(this).prev().attr('disabled', true);}else{jQuery(this).prev().attr('disabled', false);}" /> <?php _e('Open a new window.', 'frontend-user-admin'); ?></p>
<p><label for="widget_menu_user_level_<?php echo $i; ?>"><?php _e('User Level', 'frontend-user-admin'); ?> 
<select name="widget_menu_user_level[]" id="widget_menu_user_level_<?php echo $i; ?>">
<option value=""></option>
<?php
			for ( $j=0; $j<11; $j++ ) :
?>
<option value="<?php echo $j; ?>"<?php if ( isset($options['widget_menu'][$i]['widget_menu_user_level']) ) selected($options['widget_menu'][$i]['widget_menu_user_level'], $j); ?>><?php echo $j; ?></option>
<?php
			endfor;
?>
</select></label></p>
<p><label for="widget_menu_open_<?php echo $i; ?>"><input type="hidden" name="widget_menu_open[]" value="0" <?php if ( !empty($options['widget_menu'][$i]['widget_menu_open']) ) echo 'disabled="disabled"'; ?> /><input type="checkbox" name="widget_menu_open[]" id="widget_menu_open_<?php echo $i; ?>" value="1" <?php if ( !empty($options['widget_menu'][$i]['widget_menu_open']) ) echo ' checked="checked"'; ?> onclick="if (jQuery(this).attr('checked')==true || jQuery(this).attr('checked')=='checked') {jQuery(this).prev().attr('disabled', true);}else{jQuery(this).prev().attr('disabled', false);}" /> <?php _e('Show the widget menu for non-login users', 'frontend-user-admin'); ?></label></p>
</td>
</tr>
<?php
		endfor;
?>
</tbody>
<tfoot>
<tr><td>
<p><input type="submit" name="add_widget_menu_submit" value="<?php _e('Update Options &raquo;', 'frontend-user-admin'); ?>" class="button-primary" /></p></td>
</tr>
</tfoot>
</table>
</form>
<script type="text/javascript">
//<![CDATA[
	jQuery(document).ready(function () {
		jQuery("#sortable_widget_menu").sortable({});
	});
//]]>
</script>
</div>
</div>

<div class="postbox<?php if ( !isset($_GET["open"]) || (isset($_GET["open"]) && $_GET["open"] != 'mail_options') ) echo ' closed'; ?>">
<div class="handlediv" title="<?php _e('Click to toggle', 'frontend-user-admin'); ?>"><br /></div>
<h3><?php _e('Mail Options', 'frontend-user-admin'); ?></h3>
<div class="inside">
<form method="post" action="?page=frontend-user-admin/frontend-user-admin-settings.php">
<table class="form-table" style="margin-bottom:5px;">
<tbody>
<tr><td>
<p><label for="mail_from"><?php _e('FROM', 'frontend-user-admin'); ?></label>:<br />
<input type="text" name="mail_from" id="mail_from" value="<?php if ( !empty($options['mail_options']['mail_from']) ) echo esc_attr($options['mail_options']['mail_from']); ?>" size="60" /></p>
</td></tr>
<tr><td style="border-bottom:1px solid #EEE;">
<p><label for="signature_template"><?php _e('Signature Template', 'frontend-user-admin'); ?></label>:<br />
<?php _e('`%signature_template%` will be replaced by the following.', 'frontend-user-admin'); ?><br />
<textarea name="signature_template" id="signature_template" class="admin_textarea" cols="60" rows="5"><?php if ( !empty($options['mail_options']['signature_template']) ) echo htmlspecialchars($options['mail_options']['signature_template']); ?></textarea></p>
</td></tr>
<tr><td>
<p><label for="new_user_notification_user_subject"><?php _e('New User Notification Subject (For User)', 'frontend-user-admin'); ?></label>:<br />
<input type="text" name="new_user_notification_user_subject" id="new_user_notification_user_subject" value="<?php if ( !empty($options['mail_options']['new_user_notification_user_subject']) ) echo esc_attr($options['mail_options']['new_user_notification_user_subject']); ?>" size="60" /></p>
</td></tr>
<tr><td>
<p><label for="new_user_notification_user_body"><?php _e('New User Notification Body (For User)', 'frontend-user-admin'); ?></label>:<br />
<textarea name="new_user_notification_user_body" id="new_user_notification_user_body" class="admin_textarea" cols="60" rows="5"><?php if ( !empty($options['mail_options']['new_user_notification_user_body']) ) echo htmlspecialchars($options['mail_options']['new_user_notification_user_body']); ?></textarea></p>
</td></tr>
<tr><td>
<p><label for="new_user_notification_admin_subject"><?php _e('New User Notification Subject (For Admin)', 'frontend-user-admin'); ?></label>:<br />
<input type="text" name="new_user_notification_admin_subject" id="new_user_notification_admin_subject" value="<?php if ( !empty($options['mail_options']['new_user_notification_admin_subject']) ) echo esc_attr($options['mail_options']['new_user_notification_admin_subject']); ?>" size="60" /></p>
</td></tr>
<tr><td>
<p><label for="new_user_notification_admin_body"><?php _e('New User Notification Body (For Admin)', 'frontend-user-admin'); ?></label>:<br />
<textarea name="new_user_notification_admin_body" id="new_user_notification_admin_body" class="admin_textarea" cols="60" rows="5"><?php if ( !empty($options['mail_options']['new_user_notification_admin_body']) ) echo htmlspecialchars($options['mail_options']['new_user_notification_admin_body']); ?></textarea></p>
</td></tr>
<tr><td>
<p><label for="profile_update_user_subject"><?php _e('Profile Update Subject (For User)', 'frontend-user-admin'); ?></label>:<br />
<input type="text" name="profile_update_user_subject" id="profile_update_user_subject" value="<?php if ( !empty($options['mail_options']['profile_update_user_subject']) ) echo esc_attr($options['mail_options']['profile_update_user_subject']); ?>" size="60" /></p>
</td></tr>
<tr><td>
<p><label for="profile_update_user_body"><?php _e('Profile Update Body (For User)', 'frontend-user-admin'); ?></label>:<br />
<textarea name="profile_update_user_body" id="profile_update_user_body" class="admin_textarea" cols="60" rows="5"><?php if ( !empty($options['mail_options']['profile_update_user_body']) ) echo htmlspecialchars($options['mail_options']['profile_update_user_body']); ?></textarea></p>
</td></tr>
<tr><td>
<p><label for="profile_update_admin_subject"><?php _e('Profile Update Subject (For Admin)', 'frontend-user-admin'); ?></label>:<br />
<input type="text" name="profile_update_admin_subject" id="profile_update_admin_subject" value="<?php if ( !empty($options['mail_options']['profile_update_admin_subject']) ) echo esc_attr($options['mail_options']['profile_update_admin_subject']); ?>" size="60" /></p>
</td></tr>
<tr><td>
<p><label for="profile_update_admin_body"><?php _e('Profile Update Body (For Admin)', 'frontend-user-admin'); ?></label>:<br />
<textarea name="profile_update_admin_body" id="profile_update_admin_body" class="admin_textarea" cols="60" rows="5"><?php if ( !empty($options['mail_options']['profile_update_admin_body']) ) echo htmlspecialchars($options['mail_options']['profile_update_admin_body']); ?></textarea></p>
</td></tr>
<tr><td>
<p><label for="retrieve_password_subject"><?php _e('Retrieve Password Subject', 'frontend-user-admin'); ?></label>:<br />
<input type="text" name="retrieve_password_subject" id="retrieve_password_subject" value="<?php if ( !empty($options['mail_options']['retrieve_password_subject']) ) echo esc_attr($options['mail_options']['retrieve_password_subject']); ?>" size="60" /></p>
</td></tr>
<tr><td>
<p><label for="retrieve_password_body"><?php _e('Retrieve Password Body', 'frontend-user-admin'); ?></label>:<br />
<textarea name="retrieve_password_body" id="retrieve_password_body" class="admin_textarea" cols="60" rows="5"><?php if ( !empty($options['mail_options']['retrieve_password_body']) ) echo esc_attr($options['mail_options']['retrieve_password_body']); ?></textarea></p>
</td></tr>
<tr><td>
<p><label for="reset_password_user_subject"><?php _e('Reset Password Subject (For User)', 'frontend-user-admin'); ?></label>:<br />
<input type="text" name="reset_password_user_subject" id="reset_password_user_subject" value="<?php if ( !empty($options['mail_options']['reset_password_user_subject']) ) echo esc_attr($options['mail_options']['reset_password_user_subject']); ?>" size="60" /></p>
</td></tr>
<tr><td>
<p><label for="reset_password_user_body"><?php _e('Reset Password Body (For User)', 'frontend-user-admin'); ?></label>:<br />
<textarea name="reset_password_user_body" id="reset_password_user_body" class="admin_textarea" cols="60" rows="5"><?php if ( !empty($options['mail_options']['reset_password_user_body']) ) echo htmlspecialchars($options['mail_options']['reset_password_user_body']); ?></textarea></p>
</td></tr>
<tr><td>
<p><label for="reset_password_admin_subject"><?php _e('Reset Password Subject (For Admin)', 'frontend-user-admin'); ?></label>:<br />
<input type="text" name="reset_password_admin_subject" id="reset_password_admin_subject" value="<?php if ( !empty($options['mail_options']['reset_password_admin_subject']) ) echo esc_attr($options['mail_options']['reset_password_admin_subject']); ?>" size="60" /></p>
</td></tr>
<tr><td>
<p><label for="reset_password_admin_body"><?php _e('Reset Password Body (For Admin)', 'frontend-user-admin'); ?></label>:<br />
<textarea name="reset_password_admin_body" id="reset_password_admin_body" class="admin_textarea" cols="60" rows="5"><?php if ( !empty($options['mail_options']['reset_password_admin_body']) ) echo htmlspecialchars($options['mail_options']['reset_password_admin_body']); ?></textarea></p>
</td></tr>
<tr><td>
<p><label for="email_confirmation_first_user_subject"><?php _e('Email Confirmation (before and parallel registration) Subject', 'frontend-user-admin'); ?></label>:<br />
<input type="text" name="email_confirmation_first_user_subject" id="email_confirmation_first_user_subject" value="<?php if ( !empty($options['mail_options']['email_confirmation_first_user_subject']) ) echo esc_attr($options['mail_options']['email_confirmation_first_user_subject']); ?>" size="60" /></p>
</td></tr>
<tr><td>
<p><label for="email_confirmation_first_user_body"><?php _e('Email Confirmation (before and parallel registration) Body', 'frontend-user-admin'); ?></label>:<br />
<textarea name="email_confirmation_first_user_body" id="email_confirmation_first_user_body" class="admin_textarea" cols="60" rows="5"><?php if ( !empty($options['mail_options']['email_confirmation_first_user_body']) ) echo htmlspecialchars($options['mail_options']['email_confirmation_first_user_body']); ?></textarea></p>
</td></tr>
<tr><td>
<p><label for="email_confirmation_user_subject"><?php _e('Email Confirmation (after registration) Subject', 'frontend-user-admin'); ?></label>:<br />
<input type="text" name="email_confirmation_user_subject" id="email_confirmation_user_subject" value="<?php if ( !empty($options['mail_options']['email_confirmation_user_subject']) ) echo esc_attr($options['mail_options']['email_confirmation_user_subject']); ?>" size="60" /></p>
</td></tr>
<tr><td>
<p><label for="email_confirmation_user_body"><?php _e('Email Confirmation (after registration) Body', 'frontend-user-admin'); ?></label>:<br />
<textarea name="email_confirmation_user_body" id="email_confirmation_user_body" class="admin_textarea" cols="60" rows="5"><?php if ( !empty($options['mail_options']['email_confirmation_user_body']) ) echo htmlspecialchars($options['mail_options']['email_confirmation_user_body']); ?></textarea></p>
</td></tr>
<tr><td>
<p><label for="approval_process_user_subject"><?php _e('Approval Process Subject (For User)', 'frontend-user-admin'); ?></label>:<br />
<input type="text" name="approval_process_user_subject" id="approval_process_user_subject" value="<?php if ( !empty($options['mail_options']['approval_process_user_subject']) ) echo esc_attr($options['mail_options']['approval_process_user_subject']); ?>" size="60" /></p>
</td></tr>
<tr><td>
<p><label for="approval_process_user_body"><?php _e('Approval Process Body (For User)', 'frontend-user-admin'); ?></label>:<br />
<textarea name="approval_process_user_body" id="approval_process_user_body" class="admin_textarea" cols="60" rows="5"><?php if ( !empty($options['mail_options']['approval_process_user_body']) ) echo htmlspecialchars($options['mail_options']['approval_process_user_body']); ?></textarea></p>
</td></tr>
<tr><td>
<p><label for="approval_process_admin_subject"><?php _e('Approval Process Subject (For Admin)', 'frontend-user-admin'); ?></label>:<br />
<input type="text" name="approval_process_admin_subject" id="approval_process_admin_subject" value="<?php if ( !empty($options['mail_options']['approval_process_admin_subject']) ) echo esc_attr($options['mail_options']['approval_process_admin_subject']); ?>" size="60" /></p>
</td></tr>
<tr><td>
<p><label for="approval_process_admin_body"><?php _e('Approval Process Body (For Admin)', 'frontend-user-admin'); ?></label>:<br />
<textarea name="approval_process_admin_body" id="approval_process_admin_body" class="admin_textarea" cols="60" rows="5"><?php if ( !empty($options['mail_options']['approval_process_admin_body']) ) echo htmlspecialchars($options['mail_options']['approval_process_admin_body']); ?></textarea></p>
</td></tr>
<tr><td>
<p><label for="withdrawal_user_subject"><?php _e('Withdrawal Subject (For User)', 'frontend-user-admin'); ?></label>:<br />
<input type="text" name="withdrawal_user_subject" id="withdrawal_user_subject" value="<?php if ( !empty($options['mail_options']['withdrawal_user_subject']) ) echo esc_attr($options['mail_options']['withdrawal_user_subject']); ?>" size="60" /></p>
</td></tr>
<tr><td>
<p><label for="withdrawal_user_body"><?php _e('Withdrawal Body (For User)', 'frontend-user-admin'); ?></label>:<br />
<textarea name="withdrawal_user_body" id="withdrawal_user_body" class="admin_textarea" cols="60" rows="5"><?php if ( !empty($options['mail_options']['withdrawal_user_body']) ) echo htmlspecialchars($options['mail_options']['withdrawal_user_body']); ?></textarea></p>
</td></tr>
<tr><td>
<p><label for="withdrawal_admin_subject"><?php _e('Withdrawal Subject (For Admin)', 'frontend-user-admin'); ?></label>:<br />
<input type="text" name="withdrawal_admin_subject" id="withdrawal_admin_subject" value="<?php if ( !empty($options['mail_options']['withdrawal_admin_subject']) ) echo esc_attr($options['mail_options']['withdrawal_admin_subject']); ?>" size="60" /></p>
</td></tr>
<tr><td>
<p><label for="withdrawal_admin_body"><?php _e('Withdrawal Body (For Admin)', 'frontend-user-admin'); ?></label>:<br />
<textarea name="withdrawal_admin_body" id="withdrawal_admin_body" class="admin_textarea" cols="60" rows="5"><?php if ( !empty($options['mail_options']['withdrawal_admin_body']) ) echo htmlspecialchars($options['mail_options']['withdrawal_admin_body']); ?></textarea></p>
</td></tr>
<tr><td>
<p><input type="submit" name="mail_options_submit" value="<?php _e('Update Options &raquo;', 'frontend-user-admin'); ?>" class="button-primary" /></p>
</td></tr>
</tbody>
</table>
</form>
</div>
</div>

<div class="postbox<?php if ( !isset($_GET['open']) || (isset($_GET['open']) && $_GET['open'] != 'mail_template') ) echo ' closed'; ?>">
<div class="handlediv" title="<?php _e('Click to toggle', 'frontend-user-admin'); ?>"><br /></div>
<h3><?php _e('Mail Template', 'frontend-user-admin'); ?></h3>
<div class="inside">
<form method="post" action="?page=frontend-user-admin/frontend-user-admin-settings.php">
<p><?php _e('If you delete the template name and save it, the mail template will be deleted.', 'frontend-user-admin'); ?></p>
<table class="form-table" style="margin-bottom:5px;">
<tbody id="sortable_mail_template_options">
<?php
	$count = isset($options['mail_template']) ? count($options['mail_template'])+1 : 1;
	for($i=0;$i<$count;$i++) :
?>
<tr><td>
<div class="postbox closed">
<div class="handlediv" title="<?php _e('Click to toggle', 'frontend-user-admin'); ?>"><br /></div>
<h3><?php _e('Template', 'frontend-user-admin'); ?> #<?php echo $i; ?> <?php echo isset($options['mail_template'][$i]['name']) ? esc_html($options['mail_template'][$i]['name']) : ''; ?></h3>
<div class="inside">
<p><label><?php _e('Template Name', 'frontend-user-admin'); ?></label>:<br />
<input type="text" name="name[]" value="<?php echo isset($options['mail_template'][$i]['name']) ? esc_attr($options['mail_template'][$i]['name']) : ''; ?>" class="regular-text" /></label></p>
<p><label><?php _e('Mail Subject', 'frontend-user-admin'); ?></label>:<br />
<input type="text" name="subject[]" value="<?php echo isset($options['mail_template'][$i]['subject']) ? esc_attr($options['mail_template'][$i]['subject']) : ''; ?>" class="regular-text" /></label></p>
<p><label><?php _e('Mail Body', 'frontend-user-admin'); ?></label>:<br />
<textarea name="body[]" rows="10"><?php echo isset($options['mail_template'][$i]['body']) ? htmlspecialchars($options['mail_template'][$i]['body']) : ''; ?></textarea></p>
</div>
</div>
</td></tr>
<?php
	endfor;
?>
</tbody>
</table>
<table class="form-table">
<tbody>
<tr><td>
<p><input type="submit" name="mail_template_submit" value="<?php _e('Update Options &raquo;', 'frontend-user-admin'); ?>" class="button-primary" /></p>
</td></tr>
</tbody>
</table>
</form>
<script type="text/javascript">
//<![CDATA[
	jQuery(document).ready(function () {
		jQuery("#sortable_mail_template_options").sortable({});
	});
//]]>
</script>
</div>
</div>

<div class="postbox<?php if ( !isset($_GET["open"]) || (isset($_GET["open"]) && $_GET["open"] != 'title_options') ) echo ' closed'; ?>">
<div class="handlediv" title="<?php _e('Click to toggle', 'frontend-user-admin'); ?>"><br /></div>
<h3><?php _e('Title Options', 'frontend-user-admin'); ?></h3>
<div class="inside">
<form method="post" action="?page=frontend-user-admin/frontend-user-admin-settings.php">
<table class="form-table" style="margin-bottom:5px;">
<tbody>
<tr><td>
<p><label for="title_login"><?php _e('Login Title', 'frontend-user-admin'); ?></label>:<br />
<input type="text" name="login" id="title_login" class="large-text" value="<?php if ( !empty($options['title_options']['login']) ) echo esc_attr($options['title_options']['login']); ?>" /></p>
</td></tr>
<tr><td>
<p><label for="title_mypage"><?php _e('My Page Title', 'frontend-user-admin'); ?></label>:<br />
<input type="text" name="mypage" id="title_mypage" class="large-text" value="<?php if ( !empty($options['title_options']['mypage']) ) echo esc_attr($options['title_options']['mypage']); ?>" /></p>
</td></tr>
<tr><td>
<p><label for="title_register"><?php _e('Register Title', 'frontend-user-admin'); ?></label>:<br />
<input type="text" name="register" id="title_register" class="large-text" value="<?php if ( !empty($options['title_options']['register']) ) echo esc_attr($options['title_options']['register']); ?>" /></p>
</td></tr>
<tr><td>
<p><label for="title_confirmation"><?php _e('Confirmation Title', 'frontend-user-admin'); ?></label>:<br />
<input type="text" name="confirmation" id="title_confirmation" class="large-text" value="<?php if ( !empty($options['title_options']['confirmation']) ) echo esc_attr($options['title_options']['confirmation']); ?>" /></p>
</td></tr>
<tr><td>
<p><label for="title_profile"><?php _e('Profile Title', 'frontend-user-admin'); ?></label>:<br />
<input type="text" name="profile" id="title_profile" class="large-text" value="<?php if ( !empty($options['title_options']['profile']) ) echo esc_attr($options['title_options']['profile']); ?>" /></p>
</td></tr>
<tr><td>
<p><label for="title_lostpassword"><?php _e('Lost Password Title', 'frontend-user-admin'); ?></label>:<br />
<input type="text" name="lostpassword" id="title_lostpassword" class="large-text" value="<?php if ( !empty($options['title_options']['lostpassword']) ) echo esc_attr($options['title_options']['lostpassword']); ?>" /></p>
</td></tr>
<tr><td>
<p><label for="title_withdrawal"><?php _e('Withdrawal Title', 'frontend-user-admin'); ?></label>:<br />
<input type="text" name="withdrawal" id="title_withdrawal" class="large-text" value="<?php if ( !empty($options['title_options']['withdrawal']) ) echo esc_attr($options['title_options']['withdrawal']); ?>" /></p>
</td></tr>
<tr><td>
<p><label for="title_history"><?php _e('History Title', 'frontend-user-admin'); ?></label>:<br />
<input type="text" name="history" id="title_history" class="large-text" value="<?php if ( !empty($options['title_options']['history']) ) echo esc_attr($options['title_options']['history']); ?>" /></p>
</td></tr>
<tr><td>
<p><label for="title_affiliate"><?php _e('Affiliate Title', 'frontend-user-admin'); ?></label>:<br />
<input type="text" name="affiliate" id="title_affiliate" class="large-text" value="<?php if ( !empty($options['title_options']['affiliate']) ) echo esc_attr($options['title_options']['affiliate']); ?>" /></p>
</td></tr>
<tr><td>
<p><label for="title_wishlist"><?php _e('Wish List Title', 'frontend-user-admin'); ?></label>:<br />
<input type="text" name="wishlist" id="title_wishlist" class="large-text" value="<?php if ( !empty($options['title_options']['wishlist']) ) echo esc_attr($options['title_options']['wishlist']); ?>" /></p>
</td></tr>
<tr><td>
<p><label for="title_default"><?php _e('Default Title', 'frontend-user-admin'); ?></label>:<br />
<input type="text" name="default" id="title_default" class="large-text" value="<?php if ( !empty($options['title_options']['default']) ) echo esc_attr($options['title_options']['default']); ?>" /></p>
</td></tr>
<tr><td>
<p><input type="submit" name="title_options_submit" value="<?php _e('Update Options &raquo;', 'frontend-user-admin'); ?>" class="button-primary" /></p>
</td></tr>
</tbody>
</table>
</form>
</div>
</div>

<div class="postbox<?php if ( !isset($_GET["open"]) || (isset($_GET["open"]) && $_GET["open"] != 'excerpt_options') ) echo ' closed'; ?>">
<div class="handlediv" title="<?php _e('Click to toggle', 'frontend-user-admin'); ?>"><br /></div>
<h3><?php _e('Excerpt Options', 'frontend-user-admin'); ?></h3>
<div class="inside">
<form method="post" action="?page=frontend-user-admin/frontend-user-admin-settings.php">
<table class="form-table" style="margin-bottom:5px;">
<tbody>
<tr><td>
<p><label for="excerpt_login"><?php _e('Login Excerpt', 'frontend-user-admin'); ?></label>:<br />
<input type="text" name="login" id="excerpt_login" class="large-text" value="<?php if ( !empty($options['excerpt_options']['login']) ) echo esc_attr($options['excerpt_options']['login']); ?>" /></p>
</td></tr>
<tr><td>
<p><label for="excerpt_mypage"><?php _e('My Page Excerpt', 'frontend-user-admin'); ?></label>:<br />
<input type="text" name="mypage" id="excerpt_mypage" class="large-text" value="<?php if ( !empty($options['excerpt_options']['mypage']) ) echo esc_attr($options['excerpt_options']['mypage']); ?>" /></p>
</td></tr>
<tr><td>
<p><label for="excerpt_register"><?php _e('Register Excerpt', 'frontend-user-admin'); ?></label>:<br />
<input type="text" name="register" id="excerpt_register" class="large-text" value="<?php if ( !empty($options['excerpt_options']['register']) ) echo esc_attr($options['excerpt_options']['register']); ?>" /></p>
</td></tr>
<tr><td>
<p><label for="excerpt_confirmation"><?php _e('Confirmation Excerpt', 'frontend-user-admin'); ?></label>:<br />
<input type="text" name="confirmation" id="excerpt_confirmation" class="large-text" value="<?php if ( !empty($options['excerpt_options']['confirmation']) ) echo esc_attr($options['excerpt_options']['confirmation']); ?>" /></p>
</td></tr>
<tr><td>
<p><label for="excerpt_profile"><?php _e('Profile Excerpt', 'frontend-user-admin'); ?></label>:<br />
<input type="text" name="profile" id="excerpt_profile" class="large-text" value="<?php if ( !empty($options['excerpt_options']['profile']) ) echo esc_attr($options['excerpt_options']['profile']); ?>" /></p>
</td></tr>
<tr><td>
<p><label for="excerpt_lostpassword"><?php _e('Lost Password Excerpt', 'frontend-user-admin'); ?></label>:<br />
<input type="text" name="lostpassword" id="excerpt_lostpassword" class="large-text" value="<?php if ( !empty($options['excerpt_options']['lostpassword']) ) echo esc_attr($options['excerpt_options']['lostpassword']); ?>" /></p>
</td></tr>
<tr><td>
<p><label for="excerpt_withdrawal"><?php _e('Withdrawal Excerpt', 'frontend-user-admin'); ?></label>:<br />
<input type="text" name="withdrawal" id="excerpt_withdrawal" class="large-text" value="<?php if ( !empty($options['excerpt_options']['withdrawal']) ) echo esc_attr($options['excerpt_options']['withdrawal']); ?>" /></p>
</td></tr>
<tr><td>
<p><label for="excerpt_history"><?php _e('History Excerpt', 'frontend-user-admin'); ?></label>:<br />
<input type="text" name="history" id="excerpt_history" class="large-text" value="<?php if ( !empty($options['excerpt_options']['history']) ) echo esc_attr($options['excerpt_options']['history']); ?>" /></p>
</td></tr>
<tr><td>
<p><label for="excerpt_affiliate"><?php _e('Affiliate Excerpt', 'frontend-user-admin'); ?></label>:<br />
<input type="text" name="affiliate" id="excerpt_affiliate" class="large-text" value="<?php if ( !empty($options['excerpt_options']['affiliate']) ) echo esc_attr($options['excerpt_options']['affiliate']); ?>" /></p>
</td></tr>
<tr><td>
<p><label for="excerpt_wishlist"><?php _e('Wish List Excerpt', 'frontend-user-admin'); ?></label>:<br />
<input type="text" name="wishlist" id="excerpt_wishlist" class="large-text" value="<?php if ( !empty($options['excerpt_options']['wishlist']) ) echo esc_attr($options['excerpt_options']['wishlist']); ?>" /></p>
</td></tr>
<tr><td>
<p><input type="submit" name="excerpt_options_submit" value="<?php _e('Update Options &raquo;', 'frontend-user-admin'); ?>" class="button-primary" /></p>
</td></tr>
</tbody>
</table>
</form>
</div>
</div>

<div class="postbox<?php if ( !isset($_GET["open"]) || (isset($_GET["open"]) && $_GET["open"] != 'message_options') ) echo ' closed'; ?>">
<div class="handlediv" title="<?php _e('Click to toggle', 'frontend-user-admin'); ?>"><br /></div>
<h3><?php _e('Message Options', 'frontend-user-admin'); ?></h3>
<div class="inside">
<form method="post" action="?page=frontend-user-admin/frontend-user-admin-settings.php">
<table class="form-table" style="margin-bottom:5px;">
<tbody>
<tr><td>
<p><label for="message_login"><?php _e('Login Message', 'frontend-user-admin'); ?></label>:<br />
<textarea name="message_login" id="message_login" class="admin_textarea" cols="80" rows="5"><?php if ( !empty($options['message_options']['message_login']) ) echo htmlspecialchars($options['message_options']['message_login']); ?></textarea></p>
</td></tr>
<tr><td>
<p><label for="message_registrer"><?php _e('Registration Message', 'frontend-user-admin'); ?></label>:<br />
<textarea name="message_register" id="message_register" class="admin_textarea" cols="80" rows="5"><?php if ( !empty($options['message_options']['message_register']) ) echo htmlspecialchars($options['message_options']['message_register']); ?></textarea></p>
</td></tr>
<tr><td>
<p><label for="message_confirmation"><?php _e('Confirmation Message', 'frontend-user-admin'); ?></label>:<br />
<textarea name="message_confirmation" id="message_confirmation" class="admin_textarea" cols="80" rows="5"><?php if ( !empty($options['message_options']['message_confirmation']) ) echo htmlspecialchars($options['message_options']['message_confirmation']); ?></textarea></p>
</td></tr>
<tr><td>
<p><label for="message_lostpassword"><?php _e('Lost Password Message', 'frontend-user-admin'); ?></label>:<br />
<textarea name="message_lostpassword" id="message_lostpassword" class="admin_textarea" cols="80" rows="5"><?php if ( !empty($options['message_options']['message_lostpassword']) ) echo htmlspecialchars($options['message_options']['message_lostpassword']); ?></textarea></p>
</td></tr>
<tr><td>
<p><label for="message_profile"><?php _e('Profile Message', 'frontend-user-admin'); ?></label>:<br />
<textarea name="message_profile" id="message_profile" class="admin_textarea" cols="80" rows="5"><?php if ( !empty($options['message_options']['message_profile']) ) echo htmlspecialchars($options['message_options']['message_profile']); ?></textarea></p>
</td></tr>
<tr><td>
<p><label for="message_ecf"><?php _e('Email Confirmation (before registration) Message', 'frontend-user-admin'); ?></label>:<br />
<textarea name="message_ecf" id="message_ecf" class="admin_textarea" cols="80" rows="5"><?php if ( !empty($options['message_options']['message_ecf']) ) echo htmlspecialchars($options['message_options']['message_ecf']); ?></textarea></p>
</td></tr>
<tr><td>
<p><label for="message_withdrawal"><?php _e('Withdrawal Message', 'frontend-user-admin'); ?></label>:<br />
<textarea name="message_withdrawal" id="message_withdrawal" class="admin_textarea" cols="80" rows="5"><?php if ( !empty($options['message_options']['message_withdrawal']) ) echo htmlspecialchars($options['message_options']['message_withdrawal']); ?></textarea></p>
</td></tr>
<tr><td>
<p><input type="submit" name="message_options_submit" value="<?php _e('Update Options &raquo;', 'frontend-user-admin'); ?>" class="button-primary" /></p>
</td></tr>
</tbody>
</table>
</form>
</div>
</div>

<div class="postbox<?php if ( !isset($_GET["open"]) || (isset($_GET["open"]) && $_GET["open"] != 'notice_options') ) echo ' closed'; ?>">
<div class="handlediv" title="<?php _e('Click to toggle', 'frontend-user-admin'); ?>"><br /></div>
<h3><?php _e('Notice Options', 'frontend-user-admin'); ?></h3>
<div class="inside">
<form method="post" action="?page=frontend-user-admin/frontend-user-admin-settings.php">
<table class="form-table" style="margin-bottom:5px;">
<tbody>
<tr><td>
<?php if ( empty($options['notice_options']['notice_register']) ) $options['notice_options']['notice_register'] = __('Register For This Site', 'frontend-user-admin'); ?>
<p><label for="notice_register"><?php _e('Register Message', 'frontend-user-admin'); ?></label>:<br />
<textarea name="notice_register" id="notice_register" class="admin_textarea" cols="80" rows="2"><?php echo htmlspecialchars($options['notice_options']['notice_register']); ?></textarea></p>
</td></tr>
<tr><td>
<?php if ( empty($options['notice_options']['notice_loggedout']) ) $options['notice_options']['notice_loggedout'] = __('You are now logged out.', 'frontend-user-admin'); ?>
<p><label for="notice_loggedout"><?php _e('Logged Out Message', 'frontend-user-admin'); ?></label>:<br />
<textarea name="notice_loggedout" id="notice_loggedout" class="admin_textarea" cols="80" rows="2"><?php echo htmlspecialchars($options['notice_options']['notice_loggedout']); ?></textarea></p>
</td></tr>
<tr><td>
<?php if ( empty($options['notice_options']['notice_registerdisabled']) ) $options['notice_options']['notice_registerdisabled'] = __('User registration is currently not allowed.', 'frontend-user-admin'); ?>
<p><label for="notice_registerdisabled"><?php _e('Register Disabled Message', 'frontend-user-admin'); ?></label>:<br />
<textarea name="notice_registerdisabled" id="notice_registerdisabled" class="admin_textarea" cols="80" rows="2"><?php echo htmlspecialchars($options['notice_options']['notice_registerdisabled']); ?></textarea></p>
</td></tr>
<tr><td>
<?php if ( empty($options['notice_options']['notice_confirm']) ) $options['notice_options']['notice_confirm'] = __('Check your e-mail for the confirmation link.', 'frontend-user-admin'); ?>
<p><label for="notice_confirm"><?php _e('Confirm Message', 'frontend-user-admin'); ?></label>:<br />
<textarea name="notice_confirm" id="notice_confirm" class="admin_textarea" cols="80" rows="2"><?php echo htmlspecialchars($options['notice_options']['notice_confirm']); ?></textarea></p>
</td></tr>
<tr><td>
<?php if ( empty($options['notice_options']['notice_passretrieve']) ) $options['notice_options']['notice_passretrieve'] = __('Please enter your username or e-mail address. You will receive a new password via e-mail.', 'frontend-user-admin'); ?>
<p><label for="notice_passretrieve"><?php _e('Password Lost and Found', 'frontend-user-admin'); ?></label>:<br />
<textarea name="notice_passretrieve" id="notice_passretrieve" class="admin_textarea" cols="80" rows="2"><?php echo htmlspecialchars($options['notice_options']['notice_passretrieve']); ?></textarea></p>
</td></tr>
<tr><td>
<?php if ( empty($options['notice_options']['notice_newpass']) ) $options['notice_options']['notice_newpass'] = __('Check your e-mail for your new password.', 'frontend-user-admin'); ?>
<p><label for="notice_newpass"><?php _e('New Pass Message', 'frontend-user-admin'); ?></label>:<br />
<textarea name="notice_newpass" id="notice_newpass" class="admin_textarea" cols="80" rows="2"><?php echo htmlspecialchars($options['notice_options']['notice_newpass']); ?></textarea></p>
</td></tr>
<tr><td>
<?php if ( empty($options['notice_options']['notice_registered']) ) $options['notice_options']['notice_registered'] = __('Registration complete. Please check your e-mail.', 'frontend-user-admin'); ?>
<p><label for="notice_registered"><?php _e('Registered Message', 'frontend-user-admin'); ?></label>:<br />
<textarea name="notice_registered" id="notice_registered" class="admin_textarea" cols="80" rows="2"><?php echo htmlspecialchars($options['notice_options']['notice_registered']); ?></textarea></p>
</td></tr>
<tr><td>
<?php if ( empty($options['notice_options']['notice_registeredpass']) ) $options['notice_options']['notice_registeredpass'] = __('Registration complete. Please log in.', 'frontend-user-admin'); ?>
<p><label for="notice_registeredpass"><?php _e('Registered Pass Message', 'frontend-user-admin'); ?></label>:<br />
<textarea name="notice_registeredpass" id="notice_registeredpass" class="admin_textarea" cols="80" rows="2"><?php echo htmlspecialchars($options['notice_options']['notice_registeredpass']); ?></textarea></p>
</td></tr>
<tr><td>
<?php if ( empty($options['notice_options']['notice_confirmation']) ) $options['notice_options']['notice_confirmation'] = __('Please check your e-mail and click the link.', 'frontend-user-admin'); ?>
<p><label for="notice_confirmation"><?php _e('Email Confirmation Message', 'frontend-user-admin'); ?></label>:<br />
<textarea name="notice_confirmation" id="notice_confirmation" class="admin_textarea" cols="80" rows="2"><?php echo htmlspecialchars($options['notice_options']['notice_confirmation']); ?></textarea></p>
</td></tr>
<tr><td>
<?php if ( empty($options['notice_options']['notice_approval']) ) $options['notice_options']['notice_approval'] = __('Currently under approval process. Please wait for the email from the site owner.', 'frontend-user-admin'); ?>
<p><label for="notice_approval"><?php _e('Approval Message', 'frontend-user-admin'); ?></label>:<br />
<textarea name="notice_approval" id="notice_approval" class="admin_textarea" cols="80" rows="2"><?php echo htmlspecialchars($options['notice_options']['notice_approval']); ?></textarea></p>
</td></tr>
<tr><td>
<?php if ( empty($options['notice_options']['notice_invalidkey']) ) $options['notice_options']['notice_invalidkey'] = __('Sorry, that key does not appear to be valid.', 'frontend-user-admin'); ?>
<p><label for="notice_invalidkey"><?php _e('Invalid Key Message', 'frontend-user-admin'); ?></label>:<br />
<textarea name="notice_invalidkey" id="notice_invalidkey" class="admin_textarea" cols="80" rows="2"><?php echo htmlspecialchars($options['notice_options']['notice_invalidkey']); ?></textarea></p>
</td></tr>
<tr><td>
<?php if ( empty($options['notice_options']['notice_redirect_to']) ) $options['notice_options']['notice_redirect_to'] = __('Please log in to use the member service.', 'frontend-user-admin'); ?>
<p><label for="notice_redirect_to"><?php _e('Redirect To Message', 'frontend-user-admin'); ?></label>:<br />
<textarea name="notice_redirect_to" id="notice_redirect_to" class="admin_textarea" cols="80" rows="2"><?php echo htmlspecialchars($options['notice_options']['notice_redirect_to']); ?></textarea></p>
</td></tr>
<tr><td>
<?php if ( empty($options['notice_options']['notice_updated']) ) $options['notice_options']['notice_updated'] = __('User updated.', 'frontend-user-admin'); ?>
<p><label for="notice_updated"><?php _e('Updated Message', 'frontend-user-admin'); ?></label>:<br />
<textarea name="notice_updated" id="notice_updated" class="admin_textarea" cols="80" rows="2"><?php echo htmlspecialchars($options['notice_options']['notice_updated']); ?></textarea></p>
</td></tr>
<tr><td>
<?php if ( empty($options['notice_options']['notice_required']) ) $options['notice_options']['notice_required'] = __('There are required fields you need to input.', 'frontend-user-admin'); ?>
<p><label for="notice_required"><?php _e('Required Message', 'frontend-user-admin'); ?></label>:<br />
<textarea name="notice_required" id="notice_required" class="admin_textarea" cols="80" rows="2"><?php echo htmlspecialchars($options['notice_options']['notice_required']); ?></textarea></p>
</td></tr>
<tr><td>
<?php if ( empty($options['notice_options']['notice_password_change']) ) $options['notice_options']['notice_password_change'] = __('Your password is expired. You need to change your password.', 'frontend-user-admin'); ?>
<p><label for="notice_password_change"><?php _e('Password Change Message', 'frontend-user-admin'); ?></label>:<br />
<textarea name="notice_password_change" id="notice_password_change" class="admin_textarea" cols="80" rows="2"><?php echo htmlspecialchars($options['notice_options']['notice_password_change']); ?></textarea></p>
</td></tr>
<tr><td>
<?php if ( empty($options['notice_options']['notice_withdrawal']) ) $options['notice_options']['notice_withdrawal'] = __('You were resigned from the site.', 'frontend-user-admin'); ?>
<p><label for="notice_withdrawal"><?php _e('Withdrawal Message', 'frontend-user-admin'); ?></label>:<br />
<textarea name="notice_withdrawal" id="notice_withdrawal" class="admin_textarea" cols="80" rows="2"><?php echo htmlspecialchars($options['notice_options']['notice_withdrawal']); ?></textarea></p>
</td></tr>
<tr><td>
<?php if ( empty($options['notice_options']['notice_duplicate']) ) $options['notice_options']['notice_duplicate'] = __('You are logged out because of the duplicate login.', 'frontend-user-admin'); ?>
<p><label for="notice_duplicate"><?php _e('Duplicate Login Message', 'frontend-user-admin'); ?></label>:<br />
<textarea name="notice_duplicate" id="notice_duplicate" class="admin_textarea" cols="80" rows="2"><?php echo htmlspecialchars($options['notice_options']['notice_duplicate']); ?></textarea></p>
</td></tr>
<tr><td>
<p><input type="submit" name="notice_options_submit" value="<?php _e('Update Options &raquo;', 'frontend-user-admin'); ?>" class="button-primary" /></p>
</td></tr>
</tbody>
</table>
</form>
</div>
</div>
<div class="postbox<?php if ( !isset($_GET["open"]) || (isset($_GET["open"]) && $_GET["open"] != 'output_options') ) echo ' closed'; ?>">
<div class="handlediv" title="<?php _e('Click to toggle', 'frontend-user-admin'); ?>"><br /></div>
<h3><?php _e('Output Options', 'frontend-user-admin'); ?></h3>
<div class="inside">
<form method="post" action="?page=frontend-user-admin/frontend-user-admin-settings.php">
<p><?php _e('This option is experimental. Please do not set anything nomally.', 'frontend-user-admin'); ?></p>
<table class="form-table" style="margin-bottom:5px;">
<tbody>
<tr><td>
<p><label for="output_widget"><?php _e('Widget Code', 'frontend-user-admin'); ?></label> <a href="javascript:void(0);" onclick="jQuery.ajax({type: 'GET', url: '?page=frontend-user-admin/frontend-user-admin-settings.php&step=widget&rand=<?php echo time(); ?>', success: function(html) {jQuery('#output_widget').val(html);}});"><?php _e('Load the initial code', 'frontend-user-admin'); ?></a>:<br />
<textarea name="output_widget" id="output_widget" class="admin_textarea" cols="80" rows="5"><?php if ( !empty($options['output_options']['output_widget']) ) echo htmlspecialchars($options['output_options']['output_widget']); ?></textarea></p>
</td></tr>
<tr><td>
<p><label for="output_lostpassword"><?php _e('Lost Password Code', 'frontend-user-admin'); ?></label> <a href="javascript:void(0);" onclick="jQuery.ajax({type: 'GET', url: '?page=frontend-user-admin/frontend-user-admin-settings.php&step=lostpassword&rand=<?php echo time(); ?>', success: function(html) {jQuery('#output_lostpassword').val(html);}});"><?php _e('Load the initial code', 'frontend-user-admin'); ?></a>:<br />
<textarea name="output_lostpassword" id="output_lostpassword" class="admin_textarea" cols="80" rows="5"><?php if ( !empty($options['output_options']['output_lostpassword']) ) echo htmlspecialchars($options['output_options']['output_lostpassword']); ?></textarea></p>
</td></tr>
<tr><td>
<p><label for="output_mobile_lostpassword"><?php _e('Lost Password Code', 'frontend-user-admin'); ?> [<?php _e('For Mobile', 'frontend-user-admin'); ?>]</label> <a href="javascript:void(0);" onclick="jQuery.ajax({type: 'GET', url: '?page=frontend-user-admin/frontend-user-admin-settings.php&step=mobile_lostpassword&rand=<?php echo time(); ?>', success: function(html) {jQuery('#output_mobile_lostpassword').val(html);}});"><?php _e('Load the initial code', 'frontend-user-admin'); ?></a>:<br />
<textarea name="output_mobile_lostpassword" id="output_mobile_lostpassword" class="admin_textarea" cols="80" rows="5"><?php if ( !empty($options['output_options']['output_mobile_lostpassword']) ) echo htmlspecialchars($options['output_options']['output_mobile_lostpassword']); ?></textarea></p>
</td></tr>
<tr><td>
<p><label for="output_sp_lostpassword"><?php _e('Lost Password Code', 'frontend-user-admin'); ?> [<?php _e('For Smartphone', 'frontend-user-admin'); ?>]</label> <a href="javascript:void(0);" onclick="jQuery.ajax({type: 'GET', url: '?page=frontend-user-admin/frontend-user-admin-settings.php&step=sp_lostpassword&rand=<?php echo time(); ?>', success: function(html) {jQuery('#output_sp_lostpassword').val(html);}});"><?php _e('Load the initial code', 'frontend-user-admin'); ?></a>:<br />
<textarea name="output_sp_lostpassword" id="output_sp_lostpassword" class="admin_textarea" cols="80" rows="5"><?php if ( !empty($options['output_options']['output_sp_lostpassword']) ) echo htmlspecialchars($options['output_options']['output_sp_lostpassword']); ?></textarea></p>
</td></tr>
<tr><td>
<p><label for="output_register"><?php _e('Register Code', 'frontend-user-admin'); ?></label> <a href="javascript:void(0);" onclick="jQuery.ajax({type: 'GET', url: '?page=frontend-user-admin/frontend-user-admin-settings.php&step=register&rand=<?php echo time(); ?>', success: function(html) {jQuery('#output_register').val(html);}});"><?php _e('Load the initial code', 'frontend-user-admin'); ?></a>:<br />
<textarea name="output_register" id="output_register" class="admin_textarea" cols="80" rows="5"><?php if ( !empty($options['output_options']['output_register']) ) echo htmlspecialchars($options['output_options']['output_register']); ?></textarea></p>
</td></tr>
<tr><td>
<p><label for="output_mobile_register"><?php _e('Register Code', 'frontend-user-admin'); ?> [<?php _e('For Mobile', 'frontend-user-admin'); ?>]</label> <a href="javascript:void(0);" onclick="jQuery.ajax({type: 'GET', url: '?page=frontend-user-admin/frontend-user-admin-settings.php&step=mobile_register&rand=<?php echo time(); ?>', success: function(html) {jQuery('#output_mobile_register').val(html);}});"><?php _e('Load the initial code', 'frontend-user-admin'); ?></a>:<br />
<textarea name="output_mobile_register" id="output_mobile_register" class="admin_textarea" cols="80" rows="5"><?php if ( !empty($options['output_options']['output_mobile_register']) ) echo htmlspecialchars($options['output_options']['output_mobile_register']); ?></textarea></p>
</td></tr>
<tr><td>
<p><label for="output_sp_register"><?php _e('Register Code', 'frontend-user-admin'); ?> [<?php _e('For Smartphone', 'frontend-user-admin'); ?>]</label> <a href="javascript:void(0);" onclick="jQuery.ajax({type: 'GET', url: '?page=frontend-user-admin/frontend-user-admin-settings.php&step=sp_register&rand=<?php echo time(); ?>', success: function(html) {jQuery('#output_sp_register').val(html);}});"><?php _e('Load the initial code', 'frontend-user-admin'); ?></a>:<br />
<textarea name="output_sp_register" id="output_sp_register" class="admin_textarea" cols="80" rows="5"><?php if ( !empty($options['output_options']['output_sp_register']) ) echo htmlspecialchars($options['output_options']['output_sp_register']); ?></textarea></p>
</td></tr>
<tr><td>
<p><label for="output_confirmation"><?php _e('Confirmation Code', 'frontend-user-admin'); ?></label> <a href="javascript:void(0);" onclick="jQuery.ajax({type: 'GET', url: '?page=frontend-user-admin/frontend-user-admin-settings.php&step=confirmation&rand=<?php echo time(); ?>', success: function(html) {jQuery('#output_confirmation').val(html);}});"><?php _e('Load the initial code', 'frontend-user-admin'); ?></a>:<br />
<textarea name="output_confirmation" id="output_confirmation" class="admin_textarea" cols="80" rows="5"><?php if ( !empty($options['output_options']['output_confirmation']) ) echo htmlspecialchars($options['output_options']['output_confirmation']); ?></textarea></p>
</td></tr>
<tr><td>
<p><label for="output_mobile_confirmation"><?php _e('Confirmation Code', 'frontend-user-admin'); ?> [<?php _e('For Mobile', 'frontend-user-admin'); ?>]</label> <a href="javascript:void(0);" onclick="jQuery.ajax({type: 'GET', url: '?page=frontend-user-admin/frontend-user-admin-settings.php&step=mobile_confirmation&rand=<?php echo time(); ?>', success: function(html) {jQuery('#output_mobile_confirmation').val(html);}});"><?php _e('Load the initial code', 'frontend-user-admin'); ?></a>:<br />
<textarea name="output_mobile_confirmation" id="output_mobile_confirmation" class="admin_textarea" cols="80" rows="5"><?php if ( !empty($options['output_options']['output_mobile_confirmation']) ) echo htmlspecialchars($options['output_options']['output_mobile_confirmation']); ?></textarea></p>
</td></tr>
<tr><td>
<p><label for="output_sp_confirmation"><?php _e('Confirmation Code', 'frontend-user-admin'); ?> [<?php _e('For Smartphone', 'frontend-user-admin'); ?>]</label> <a href="javascript:void(0);" onclick="jQuery.ajax({type: 'GET', url: '?page=frontend-user-admin/frontend-user-admin-settings.php&step=sp_confirmation&rand=<?php echo time(); ?>', success: function(html) {jQuery('#output_sp_confirmation').val(html);}});"><?php _e('Load the initial code', 'frontend-user-admin'); ?></a>:<br />
<textarea name="output_sp_confirmation" id="output_sp_confirmation" class="admin_textarea" cols="80" rows="5"><?php if ( !empty($options['output_options']['output_sp_confirmation']) ) echo htmlspecialchars($options['output_options']['output_sp_confirmation']); ?></textarea></p>
</td></tr>
<tr><td>
<p><label for="output_profile"><?php _e('Profile Code', 'frontend-user-admin'); ?></label> <a href="javascript:void(0);" onclick="jQuery.ajax({type: 'GET', url: '?page=frontend-user-admin/frontend-user-admin-settings.php&step=profile&rand=<?php echo time(); ?>', success: function(html) {jQuery('#output_profile').val(html);}});"><?php _e('Load the initial code', 'frontend-user-admin'); ?></a>:<br />
<textarea name="output_profile" id="output_profile" class="admin_textarea" cols="80" rows="5"><?php if ( !empty($options['output_options']['output_profile']) ) echo htmlspecialchars($options['output_options']['output_profile']); ?></textarea></p>
</td></tr>
<tr><td>
<p><label for="output_mobile_profile"><?php _e('Profile Code', 'frontend-user-admin'); ?> [<?php _e('For Mobile', 'frontend-user-admin'); ?>]</label> <a href="javascript:void(0);" onclick="jQuery.ajax({type: 'GET', url: '?page=frontend-user-admin/frontend-user-admin-settings.php&step=mobile_profile&rand=<?php echo time(); ?>', success: function(html) {jQuery('#output_mobile_profile').val(html);}});"><?php _e('Load the initial code', 'frontend-user-admin'); ?></a>:<br />
<textarea name="output_mobile_profile" id="output_mobile_profile" class="admin_textarea" cols="80" rows="5"><?php if ( !empty($options['output_options']['output_mobile_profile']) ) echo htmlspecialchars($options['output_options']['output_mobile_profile']); ?></textarea></p>
</td></tr>
<tr><td>
<p><label for="output_sp_profile"><?php _e('Profile Code', 'frontend-user-admin'); ?> [<?php _e('For Smartphone', 'frontend-user-admin'); ?>]</label> <a href="javascript:void(0);" onclick="jQuery.ajax({type: 'GET', url: '?page=frontend-user-admin/frontend-user-admin-settings.php&step=sp_profile&rand=<?php echo time(); ?>', success: function(html) {jQuery('#output_sp_profile').val(html);}});"><?php _e('Load the initial code', 'frontend-user-admin'); ?></a>:<br />
<textarea name="output_sp_profile" id="output_sp_profile" class="admin_textarea" cols="80" rows="5"><?php if ( !empty($options['output_options']['output_sp_profile']) ) echo htmlspecialchars($options['output_options']['output_sp_profile']); ?></textarea></p>
</td></tr>
<tr><td>
<p><label for="output_withdrawal"><?php _e('Withdrawal Code', 'frontend-user-admin'); ?></label> <a href="javascript:void(0);" onclick="jQuery.ajax({type: 'GET', url: '?page=frontend-user-admin/frontend-user-admin-settings.php&step=withdrawal&rand=<?php echo time(); ?>', success: function(html) {jQuery('#output_withdrawal').val(html);}});"><?php _e('Load the initial code', 'frontend-user-admin'); ?></a>:<br />
<textarea name="output_withdrawal" id="output_withdrawal" class="admin_textarea" cols="80" rows="5"><?php if ( !empty($options['output_options']['output_withdrawal']) ) echo htmlspecialchars($options['output_options']['output_withdrawal']); ?></textarea></p>
</td></tr>
<tr><td>
<p><label for="output_mobile_withdrawal"><?php _e('Withdrawal Code', 'frontend-user-admin'); ?> [<?php _e('For Mobile', 'frontend-user-admin'); ?>]</label> <a href="javascript:void(0);" onclick="jQuery.ajax({type: 'GET', url: '?page=frontend-user-admin/frontend-user-admin-settings.php&step=mobile_withdrawal&rand=<?php echo time(); ?>', success: function(html) {jQuery('#output_mobile_withdrawal').val(html);}});"><?php _e('Load the initial code', 'frontend-user-admin'); ?></a>:<br />
<textarea name="output_mobile_withdrawal" id="output_mobile_withdrawal" class="admin_textarea" cols="80" rows="5"><?php if ( !empty($options['output_options']['output_mobile_withdrawal']) ) echo htmlspecialchars($options['output_options']['output_mobile_withdrawal']); ?></textarea></p>
</td></tr>
<tr><td>
<p><label for="output_sp_withdrawal"><?php _e('Withdrawal Code', 'frontend-user-admin'); ?> [<?php _e('For Smartphone', 'frontend-user-admin'); ?>]</label> <a href="javascript:void(0);" onclick="jQuery.ajax({type: 'GET', url: '?page=frontend-user-admin/frontend-user-admin-settings.php&step=sp_withdrawal&rand=<?php echo time(); ?>', success: function(html) {jQuery('#output_sp_withdrawal').val(html);}});"><?php _e('Load the initial code', 'frontend-user-admin'); ?></a>:<br />
<textarea name="output_sp_withdrawal" id="output_sp_withdrawal" class="admin_textarea" cols="80" rows="5"><?php if ( !empty($options['output_options']['output_sp_withdrawal']) ) echo htmlspecialchars($options['output_options']['output_sp_withdrawal']); ?></textarea></p>
</td></tr>
<tr><td>
<p><label for="output_login"><?php _e('Login Code', 'frontend-user-admin'); ?></label> <a href="javascript:void(0);" onclick="jQuery.ajax({type: 'GET', url: '?page=frontend-user-admin/frontend-user-admin-settings.php&step=login&rand=<?php echo time(); ?>', success: function(html) {jQuery('#output_login').val(html);}});"><?php _e('Load the initial code', 'frontend-user-admin'); ?></a>:<br />
<textarea name="output_login" id="output_login" class="admin_textarea" cols="80" rows="5"><?php if ( !empty($options['output_options']['output_login']) ) echo htmlspecialchars($options['output_options']['output_login']); ?></textarea></p>
</td></tr>
<tr><td>
<p><label for="output_mobile_login"><?php _e('Login Code', 'frontend-user-admin'); ?> [<?php _e('For Mobile', 'frontend-user-admin'); ?>]</label> <a href="javascript:void(0);" onclick="jQuery.ajax({type: 'GET', url: '?page=frontend-user-admin/frontend-user-admin-settings.php&step=mobile_login&rand=<?php echo time(); ?>', success: function(html) {jQuery('#output_mobile_login').val(html);}});"><?php _e('Load the initial code', 'frontend-user-admin'); ?></a>:<br />
<textarea name="output_mobile_login" id="output_mobile_login" class="admin_textarea" cols="80" rows="5"><?php if ( !empty($options['output_options']['output_mobile_login']) ) echo htmlspecialchars($options['output_options']['output_mobile_login']); ?></textarea></p>
</td></tr>
<tr><td>
<p><label for="output_sp_login"><?php _e('Login Code', 'frontend-user-admin'); ?> [<?php _e('For Smartphone', 'frontend-user-admin'); ?>]</label> <a href="javascript:void(0);" onclick="jQuery.ajax({type: 'GET', url: '?page=frontend-user-admin/frontend-user-admin-settings.php&step=sp_login&rand=<?php echo time(); ?>', success: function(html) {jQuery('#output_sp_login').val(html);}});"><?php _e('Load the initial code', 'frontend-user-admin'); ?></a>:<br />
<textarea name="output_sp_login" id="output_sp_login" class="admin_textarea" cols="80" rows="5"><?php if ( !empty($options['output_options']['output_sp_login']) ) echo htmlspecialchars($options['output_options']['output_sp_login']); ?></textarea></p>
</td></tr>
<tr><td>
<p><input type="submit" name="output_options_submit" value="<?php _e('Update Options &raquo;', 'frontend-user-admin'); ?>" class="button-primary" /></p>
</td></tr>
</tbody>
</table>
</form>
</div>
</div>

<div class="postbox<?php if ( !isset($_GET["open"]) || (isset($_GET["open"]) && $_GET["open"] != 'member_condition') ) echo ' closed'; ?>">
<div class="handlediv" title="<?php _e('Click to toggle', 'frontend-user-admin'); ?>"><br /></div>
<h3><?php _e('Add Member Page Condition', 'frontend-user-admin'); ?></h3>
<div class="inside">
<form method="post" action="?page=frontend-user-admin/frontend-user-admin-settings.php">
<table class="form-table" style="margin-bottom:5px;">
<tbody>
<?php
		if ( substr($wp_version, 0, 3) >= '3.0' ) : 
?>
<tr><td>
<p><?php _e('Member Condition Types', 'frontend-user-admin'); ?>:<br />
<?php
			$post_types = get_post_types(array('public'=>true));
			foreach( $post_types as $key => $val ) :
?>
<label><input type="checkbox" name="member_condition_types[]" value="<?php echo $key; ?>"<?php if ( !empty($options['member_condition_types']) && is_array($options['member_condition_types']) && in_array($key, $options['member_condition_types'])) echo ' checked="checked"'; ?> /> <?php echo $key; ?></label> 
<?php
			endforeach;
?>
</p>
</td></tr>
<tr><td>
<p><label for="default_member_condition"><?php _e('Default Member Condition', 'frontend-user-admin'); ?></label>:<br />
<select name="default_member_condition[]" id="default_member_condition" multiple="multiple">
<?php
			$options['default_member_condition'] = isset($options['default_member_condition']) ? $options['default_member_condition'] : '';
			$fuamc = explode(',', $options['default_member_condition']);
			$fuamc = array_unique(array_map('trim', $fuamc));

			for($i=0; $i<count($options['member_condition']); $i++ ) :
?>
<option value="<?php echo $i; ?>"<?php foreach ( $fuamc as $val ) selected($val, $i); ?>>#<?php echo $i; ?> <?php echo esc_attr($options['member_condition'][$i]['name']); ?></option>
<?php
			endfor;
?>
</select>
</p>
<?php
		endif;
?>
<?php
		$count = isset($options['member_condition']) ? count($options['member_condition'])+1 : 1;
		for($i=0;$i<$count;$i++) :
?>
<tr><td>
<div class="postbox closed">
<div class="handlediv" title="<?php _e('Click to toggle', 'frontend-user-admin'); ?>"><br /></div>
<h3><?php _e('Condition', 'frontend-user-admin'); ?> #<?php echo $i; ?> <?php if ( isset($options['member_condition'][$i]['name']) ) echo $options['member_condition'][$i]['name']; ?></h3>
<div class="inside">
<table class="form-table" style="margin-bottom:5px;">
<tbody>
<tr>
<td><label for="name<?php echo $i; ?>"><?php _e('Condition Name', 'frontend-user-admin'); ?></label>: <br /><input type="text" name="name[]" id="name<?php echo $i; ?>" value="<?php echo isset($options['member_condition'][$i]['name']) ? esc_attr($options['member_condition'][$i]['name']) : ''; ?>" class="regular-text" /></td>
</tr>
<tr>
<td><label for="redirect_url<?php echo $i; ?>"><?php _e('Redirect URL', 'frontend-user-admin'); ?></label>: <br /><input type="text" name="redirect_url[]" id="redirect_url<?php echo $i; ?>" value="<?php echo isset($options['member_condition'][$i]['redirect_url']) ? esc_attr($options['member_condition'][$i]['redirect_url']) : ''; ?>" class="regular-text" /></td>
</tr>
<tr>
<td><label for="the_title<?php echo $i; ?>"><?php _e('Alternative Title in case of the condition mismatching', 'frontend-user-admin'); ?></label>: <br /><input type="text" name="the_title[]" id="the_title<?php echo $i; ?>" value="<?php echo isset($options['member_condition'][$i]['the_title']) ? esc_attr($options['member_condition'][$i]['the_title']) : ''; ?>" class="large-text" /></td>
</tr>
<tr>
<td><label for="the_content<?php echo $i; ?>"><?php _e('Alternative Content in case of the condition mismatching', 'frontend-user-admin'); ?></label>: <br /><textarea name="the_content[]" id="the_content<?php echo $i; ?>" rowspan="2" class="large_text"><?php echo isset($options['member_condition'][$i]['the_content']) ? esc_attr($options['member_condition'][$i]['the_content']) : ''; ?></textarea></td>
</tr>
<tr>
<t><label for="no_output<?php echo $i; ?>"><input type="checkbox" name="no_output[<?php echo $i; ?>]" id="no_output<?php echo $i; ?>" value="1" <?php if( !empty($options['member_condition'][$i]['no_output']) ) echo 'checked="checked"'; ?>/> <?php _e('No output (especially for pages)', 'frontend-user-admin'); ?></label></td>
</tr>
<tr>
<td><label for="except_clawlers<?php echo $i; ?>"><input type="checkbox" name="except_clawlers[<?php echo $i; ?>]" id="except_clawlers<?php echo $i; ?>" value="1" <?php if( !empty($options['member_condition'][$i]['except_clawlers']) ) echo 'checked="checked"'; ?>/> <?php _e('Except clawlers from the member condition.', 'frontend-user-admin'); ?></label></td>
</tr>
<tr>
<td><label for="until_more<?php echo $i; ?>"><input type="checkbox" name="until_more[<?php echo $i; ?>]" id="until_more<?php echo $i; ?>" value="1" <?php if( !empty($options['member_condition'][$i]['until_more']) ) echo 'checked="checked"'; ?>/> <?php _e('Output until the More separation', 'frontend-user-admin'); ?></label></td>
</tr>
<tr>
<td><label for="auto_excerpt<?php echo $i; ?>"><input type="text" name="auto_excerpt[<?php echo $i; ?>]" id="auto_excerpt<?php echo $i; ?>" value="<?php if( !empty($options['member_condition'][$i]['auto_excerpt']) ) echo esc_attr($options['member_condition'][$i]['auto_excerpt']); ?>" class="small-text" /> <?php _e('letters are automatically excerpted from the post content.', 'frontend-user-admin'); ?></label></td>
</tr>
<tr>
<td>
<fieldset>
<legend><?php _e('User Information', 'frontend-user-admin'); ?></legend>
<table>
<tr>
<td colspan="4"><label for="conjunction<?php echo $i; ?>"><?php _e('Conditional conjunction', 'frontend-user-admin'); ?></label>: <select name="conjunction[]" id="conjunction<?php echo $i; ?>" style="vertical-align:middle;">
<option value="AND" <?php if ( isset($options['member_condition'][$i]['conjunction']) ) selected('AND', $options['member_condition'][$i]['conjunction']); ?>>AND</option>
<option value="OR" <?php if ( isset($options['member_condition'][$i]['conjunction']) ) selected('OR', $options['member_condition'][$i]['conjunction']); ?>>OR</option>
</select></td>
</tr>
<?php
			$count2 = isset($options['member_condition'][$i]['attribute']) ? count($options['member_condition'][$i]['attribute'])+1 : 3;
			if ( $count2 < 3 ) $count2 = 3;
			for($j=0;$j<$count2;$j++) :
				$term_id = isset($options['member_condition'][$i]['term_id']) ? trim(implode(', ', $options['member_condition'][$i]['term_id'])) : '';
?>
<tr>
<td><label><?php _e('User Attribute Key', 'frontend-user-admin'); ?></label>: <input type="text" name="attribute_key[<?php echo $i; ?>][]" value="<?php if ( isset($options['member_condition'][$i]['attribute'][$j]['attribute_key']) ) echo esc_attr($options['member_condition'][$i]['attribute'][$j]['attribute_key']); ?>" class="regular-text imedisabled" /></td>
<td><label><?php _e('User Attribute Value', 'frontend-user-admin'); ?></label>: <input type="text" name="attribute_value[<?php echo $i; ?>][]" value="<?php if ( isset($options['member_condition'][$i]['attribute'][$j]['attribute_value']) ) echo esc_attr($options['member_condition'][$i]['attribute'][$j]['attribute_value']); ?>" class="regular-text" /></td>
<td><select name="code[<?php echo $i; ?>][]" style="vertical-align:middle;">
<option value=""></option>
<option value="1" <?php if ( isset($options['member_condition'][$i]['attribute'][$j]['code']) ) selected('1', $options['member_condition'][$i]['attribute'][$j]['code']); ?>><?php _e('CODE', 'frontend-user-admin'); ?></option>
</select></td>
<td><select name="nm[<?php echo $i; ?>][]" style="vertical-align:middle;">
<option value="p" <?php if ( isset($options['member_condition'][$i]['attribute'][$j]['nm']) ) selected('p', $options['member_condition'][$i]['attribute'][$j]['nm']); ?>><?php _e('Match Partial', 'frontend-user-admin'); ?></option>
<option value="f" <?php if ( isset($options['member_condition'][$i]['attribute'][$j]['nm']) ) selected('f', $options['member_condition'][$i]['attribute'][$j]['nm']); ?>><?php _e('Match Full', 'frontend-user-admin'); ?></option>
<option value="!=" <?php if ( isset($options['member_condition'][$i]['attribute'][$j]['nm']) ) selected('!=', $options['member_condition'][$i]['attribute'][$j]['nm']); ?>><?php _e('No Match', 'frontend-user-admin'); ?></option>
<option value=">=" <?php if ( isset($options['member_condition'][$i]['attribute'][$j]['nm']) ) selected('>=', $options['member_condition'][$i]['attribute'][$j]['nm']); ?>><?php _e('and above', 'frontend-user-admin'); ?></option>
<option value="<=" <?php if ( isset($options['member_condition'][$i]['attribute'][$j]['nm']) ) selected('<=', $options['member_condition'][$i]['attribute'][$j]['nm']); ?>><?php _e('and below', 'frontend-user-admin'); ?></option>
<option value=">" <?php if ( isset($options['member_condition'][$i]['attribute'][$j]['nm']) ) selected('>', $options['member_condition'][$i]['attribute'][$j]['nm']); ?>><?php _e('more than', 'frontend-user-admin'); ?></option>
<option value="<" <?php if ( isset($options['member_condition'][$i]['attribute'][$j]['nm']) ) selected('<', $options['member_condition'][$i]['attribute'][$j]['nm']); ?>><?php _e('less than', 'frontend-user-admin'); ?></option>
</select></td>
</tr>
<?php
			endfor;
?>
</table>
</fieldset>
</td>
</tr>
<?php
			global $net_shop_admin;
			if ( !empty($net_shop_admin) ) :
				$nsa_options = get_option('net_shop_admin');
				global $order_management;
				$nsa_fields = array_merge($order_management->return_order_value(), $order_management->return_address_value(), $order_management->return_product_value());
?>
<tr>
<td><label for="uo_conjunction<?php echo $i; ?>"><?php _e('Conditional conjunction between User Information and Order Information', 'frontend-user-admin'); ?></label>: <select name="uo_conjunction[]" id="uo_conjunction<?php echo $i; ?>" style="vertical-align:middle;">
<option value="AND" <?php if ( isset($options['member_condition'][$i]['uo_conjunction']) ) selected('AND', $options['member_condition'][$i]['uo_conjunction']); ?>>AND</option>
<option value="OR" <?php if ( isset($options['member_condition'][$i]['uo_conjunction']) ) selected('OR', $options['member_condition'][$i]['uo_conjunction']); ?>>OR</option>
</select></td>
</tr>
<tr>
<td>
<fieldset>
<legend><?php _e('Order Information', 'frontend-user-admin'); ?></legend>
<table>
<tr>
<td colspan="4"><label for="o_conjunction<?php echo $i; ?>"><?php _e('Conditional conjunction', 'frontend-user-admin'); ?></label>: <select name="o_conjunction[]" id="o_conjunction<?php echo $i; ?>" style="vertical-align:middle;">
<option value="AND" <?php if ( isset($options['member_condition'][$i]['o_conjunction']) ) selected('AND', $options['member_condition'][$i]['o_conjunction']); ?>>AND</option>
<option value="OR" <?php if ( isset($options['member_condition'][$i]['o_conjunction']) ) selected('OR', $options['member_condition'][$i]['o_conjunction']); ?>>OR</option>
</select></td>
</tr>
<?php
				$count2 = isset($options['member_condition'][$i]['attribute2']) ? count($options['member_condition'][$i]['attribute2'])+1 : 3;
				if ( $count2 < 3 ) $count2 = 3;
				for($j=0;$j<$count2;$j++) :
?>
<tr>
<td><label><?php _e('Order Key', 'frontend-user-admin'); ?></label>: <select name="order_key[<?php echo $i; ?>][]" style="vertical-align:middle;">
<option value=""></option>
<?php			
					foreach ( $nsa_fields as $key => $val ) :
?>
<option value="<?php echo $key; ?>"<?php if ( !empty($options['member_condition'][$i]['attribute2'][$j]['order_key']) ) selected($key, $options['member_condition'][$i]['attribute2'][$j]['order_key']); ?>><?php echo $val; ?></option>
<?php
					endforeach;
?>
</select></td>
<td><label><?php _e('Order Value', 'frontend-user-admin'); ?></label>: <input type="text" name="order_value[<?php echo $i; ?>][]" value="<?php if ( isset($options['member_condition'][$i]['attribute2'][$j]['order_value']) ) echo esc_attr($options['member_condition'][$i]['attribute2'][$j]['order_value']); ?>" class="regular-text" /></td>
<td><select name="nm2[<?php echo $i; ?>][]" style="vertical-align:middle;">
<option value="p" <?php if ( isset($options['member_condition'][$i]['attribute2'][$j]['nm']) ) selected('p', $options['member_condition'][$i]['attribute2'][$j]['nm']); ?>><?php _e('Match Partial', 'frontend-user-admin'); ?></option>
<option value="f" <?php if ( isset($options['member_condition'][$i]['attribute2'][$j]['nm']) ) selected('f', $options['member_condition'][$i]['attribute2'][$j]['nm']); ?>><?php _e('Match Full', 'frontend-user-admin'); ?></option>
<option value="!=" <?php if ( isset($options['member_condition'][$i]['attribute2'][$j]['nm']) ) selected('!=', $options['member_condition'][$i]['attribute2'][$j]['nm']); ?>><?php _e('No Match', 'frontend-user-admin'); ?></option>
<option value=">=" <?php if ( isset($options['member_condition'][$i]['attribute2'][$j]['nm']) ) selected('>=', $options['member_condition'][$i]['attribute2'][$j]['nm']); ?>><?php _e('and above', 'frontend-user-admin'); ?></option>
<option value="<=" <?php if ( isset($options['member_condition'][$i]['attribute2'][$j]['nm']) ) selected('<=', $options['member_condition'][$i]['attribute2'][$j]['nm']); ?>><?php _e('and below', 'frontend-user-admin'); ?></option>
<option value=">" <?php if ( isset($options['member_condition'][$i]['attribute2'][$j]['nm']) ) selected('>', $options['member_condition'][$i]['attribute2'][$j]['nm']); ?>><?php _e('more than', 'frontend-user-admin'); ?></option>
<option value="<" <?php if ( isset($options['member_condition'][$i]['attribute2'][$j]['nm']) ) selected('<', $options['member_condition'][$i]['attribute2'][$j]['nm']); ?>><?php _e('less than', 'frontend-user-admin'); ?></option>
</select></td>
</tr>
<?php
				endfor;
?>
</table>
</fieldset>
</td>
</tr>
<?php
			endif;
?>
<tr>
<td><label for="term_id<?php echo $i; ?>"><?php _e('Term ID (comma-deliminated) (valid only within Redirect URL)', 'frontend-user-admin'); ?></label>: <br /><input type="text" name="term_id[]" id="name<?php echo $i; ?>" value="<?php echo $term_id; ?>" class="large-text" /></td>
</tr>
<tr>
<td><label for="apply_to_subpages<?php echo $i; ?>"><input type="checkbox" name="apply_to_subpages[<?php echo $i; ?>]" id="apply_to_subpages<?php echo $i; ?>" value="1" <?php if( !empty($options['member_condition'][$i]['apply_to_subpages']) ) echo 'checked="checked"'; ?>/> <?php _e('Apply to subpages', 'frontend-user-admin'); ?></label></td>
</tr>
</tbody>
</table>
</div>
</div>
</td></tr>
<?php
		endfor;
?>
</tbody>
<tfoot>
<tr><td>
<p><input type="submit" name="member_condition_submit" value="<?php _e('Update Options &raquo;', 'frontend-user-admin'); ?>" class="button-primary" /></p></td>
</tr>
</tfoot>
</table>
</form>
</div>
</div>

<div class="postbox<?php if ( !isset($_GET["open"]) || (isset($_GET["open"]) && $_GET["open"] != 'fualist_format') ) echo ' closed'; ?>">
<div class="handlediv" title="<?php _e('Click to toggle', 'frontend-user-admin'); ?>"><br /></div>
<h3><?php _e('[fualist] Shortcode Format', 'frontend-user-admin'); ?></h3>
<div class="inside">
<form method="post" action="?page=frontend-user-admin/frontend-user-admin-settings.php">
<table class="form-table" style="margin-bottom:5px;">
<tbody>
<?php
		$count = isset($options['fualist_format']) ? count($options['fualist_format'])+1 : 1;
		for($i=0;$i<$count;$i++) :
?>
<tr><td>
<div class="postbox closed">
<div class="handlediv" title="<?php _e('Click to toggle', 'frontend-user-admin'); ?>"><br /></div>
<h3><?php _e('Format', 'frontend-user-admin'); ?> #<?php echo $i; ?></h3>
<div class="inside">
<table class="form-table" style="margin-bottom:5px;">
<tbody>
<tr>
<td><label for="prefix<?php echo $i; ?>"><?php _e('Prefix Text', 'frontend-user-admin'); ?></label>: <br /><textarea name="prefix[]" id="prefix<?php echo $i; ?>" class="large-text"><?php echo isset($options['fualist_format'][$i]['prefix']) ? esc_textarea($options['fualist_format'][$i]['prefix']) : ''; ?></textarea></td>
</tr>
<tr>
<td><label for="main<?php echo $i; ?>"><?php _e('Main Text', 'frontend-user-admin'); ?></label>: <br /><textarea name="main[]" id="main<?php echo $i; ?>" class="large-text"><?php echo isset($options['fualist_format'][$i]['main']) ? esc_textarea($options['fualist_format'][$i]['main']) : ''; ?></textarea></td>
</tr>
<tr>
<td><label for="suffix<?php echo $i; ?>"><?php _e('Suffix Text', 'frontend-user-admin'); ?></label>: <br /><textarea name="suffix[]" id="suffix<?php echo $i; ?>" class="large-text"><?php echo isset($options['fualist_format'][$i]['suffix']) ? esc_textarea($options['fualist_format'][$i]['suffix']) : ''; ?></textarea></td>
</tr>
</tbody>
</table>
</div>
</div>
</td></tr>
<?php
		endfor;
?>
</tbody>
<tfoot>
<tr><td>
<p><input type="submit" name="fualist_format_submit" value="<?php _e('Update Options &raquo;', 'frontend-user-admin'); ?>" class="button-primary" /></p></td>
</tr>
</tfoot>
</table>
</form>
</div>
</div>

<div class="postbox<?php if ( !isset($_GET["open"]) || (isset($_GET["open"]) && $_GET['open'] != 'recaptcha_options') ) echo ' closed'; ?>">
<div class="handlediv" title="<?php _e('Click to toggle', 'frontend-user-admin'); ?>"><br /></div>
<h3><?php _e('reCAPTCHA Options', 'frontend-user-admin'); ?></h3>
<div class="inside">
<form method="post" action="?page=frontend-user-admin/frontend-user-admin-settings.php">
<table class="form-table" style="margin-bottom:5px;">
<tbody>
<tr><td>
<p><label for="recaptcha_site_key"><?php _e('Site Key', 'frontend-user-admin'); ?>:<br />
<input type="text" name="site_key" id="recaptcha_site_key" value="<?php echo !empty($options['recaptcha_options']['site_key']) ? esc_attr($options['recaptcha_options']['site_key']) :  ''; ?>" class="large-text" /></label></p>
</td></tr>
<tr><td>
<p><label for="recaptcha_secret_key"><?php _e('Secret Key', 'frontend-user-admin'); ?>:<br />
<input type="text" name="secret_key" id="recaptcha_secret_key" value="<?php echo !empty($options['recaptcha_options']['secret_key']) ? esc_attr($options['recaptcha_options']['secret_key']) :  ''; ?>" class="large-text" /></label></p>
</td></tr>
<tr><td>
<p><label for="recaptcha_registration"><?php _e('Registration', 'frontend-user-admin'); ?>:<br />
<input type="checkbox" name="registration" id="recaptcha_registration" value="1" <?php if( !empty($options['recaptcha_options']['registration']) ) echo 'checked="checked"'; ?>/> <?php _e('Use', 'frontend-user-admin'); ?></label></p>
</td></tr>
<tr><td>
<p><label for="recaptcha_login"><?php _e('Login', 'frontend-user-admin'); ?>:<br />
<input type="checkbox" name="login" id="recaptcha_login" value="1" <?php if( !empty($options['recaptcha_options']['login']) ) echo 'checked="checked"'; ?>/> <?php _e('Use', 'frontend-user-admin'); ?></label></p>
</td></tr>
<tr><td>
<p><label for="recaptcha_theme"><?php _e('Theme', 'frontend-user-admin'); ?>:<br />
<select name="theme" id="recaptcha_theme">
<option value="light" <?php if ( !empty($options['recaptcha_options']['theme']) ) selected($options['recaptcha_options']['theme'], 'light'); ?>>light</option>
<option value="dark" <?php if ( !empty($options['recaptcha_options']['theme']) ) selected($options['recaptcha_options']['theme'], 'dark'); ?>>Dark</option>
</select></label></p>
</td></tr>
<tr><td>
<p><label for="recaptcha_type"><?php _e('Type', 'frontend-user-admin'); ?>:<br />
<select name="type" id="recaptcha_type">
<option value="image" <?php if ( !empty($options['recaptcha_options']['type']) ) selected($options['recaptcha_options']['type'], 'image'); ?>>image</option>
<option value="audio" <?php if ( !empty($options['recaptcha_options']['type']) ) selected($options['recaptcha_options']['type'], 'audio'); ?>>audio</option>
</select></label></p>
</td></tr>
<tr><td>
<p><label for="recaptcha_size"><?php _e('Size', 'frontend-user-admin'); ?>:<br />
<select name="size" id="recaptcha_size">
<option value="normal" <?php if ( !empty($options['recaptcha_options']['size']) ) selected($options['recaptcha_options']['size'], 'normal'); ?>>normal</option>
<option value="compact" <?php if ( !empty($options['recaptcha_options']['size']) ) selected($options['recaptcha_options']['size'], 'compact'); ?>>compact</option>
</select></label></p>
</td></tr>
<tr><td>
<p><input type="submit" name="recaptcha_options_submit" value="<?php _e('Update Options &raquo;', 'frontend-user-admin'); ?>" class="button-primary" /></p>
</td></tr>
</tbody>
</table>
</form>
</div>
</div>

<div class="postbox<?php if ( !isset($_GET["open"]) || (isset($_GET["open"]) && $_GET['open'] != 'phpcode_options') ) echo ' closed'; ?>">
<div class="handlediv" title="<?php _e('Click to toggle', 'frontend-user-admin'); ?>"><br /></div>
<h3><?php _e('PHP Code in the user registration', 'frontend-user-admin'); ?></h3>
<div class="inside">
<form method="post" action="?page=frontend-user-admin/frontend-user-admin-settings.php">
<table class="form-table" style="margin-bottom:5px;">
<tbody>
<tr><td>
<p><textarea name="phpcode" rows="5"><?php if ( !empty($options['phpcode_options']) ) echo htmlspecialchars($options['phpcode_options']); ?></textarea></p>
</td></tr>
<tr><td>
<p><input type="submit" name="phpcode_options_submit" value="<?php _e('Update Options &raquo;', 'frontend-user-admin'); ?>" class="button-primary" /></p>
</td></tr>
</tbody>
</table>
</form>
</div>
</div>

<div class="postbox<?php if ( !isset($_GET["open"]) || (isset($_GET["open"]) && $_GET['open'] != 'phpcode2_options') ) echo ' closed'; ?>">
<div class="handlediv" title="<?php _e('Click to toggle', 'frontend-user-admin'); ?>"><br /></div>
<h3><?php _e('PHP Code in the user update', 'frontend-user-admin'); ?></h3>
<div class="inside">
<form method="post" action="?page=frontend-user-admin/frontend-user-admin-settings.php">
<table class="form-table" style="margin-bottom:5px;">
<tbody>
<tr><td>
<p><textarea name="phpcode2" rows="5"><?php if ( !empty($options['phpcode2_options']) ) echo htmlspecialchars($options['phpcode2_options']); ?></textarea></p>
</td></tr>
<tr><td>
<p><input type="submit" name="phpcode2_options_submit" value="<?php _e('Update Options &raquo;', 'frontend-user-admin'); ?>" class="button-primary" /></p>
</td></tr>
</tbody>
</table>
</form>
</div>
</div>

<div class="postbox<?php if ( !isset($_GET["open"]) || (isset($_GET["open"]) && $_GET['open'] != 'phpcode3_options') ) echo ' closed'; ?>">
<div class="handlediv" title="<?php _e('Click to toggle', 'frontend-user-admin'); ?>"><br /></div>
<h3><?php _e('PHP Code in the login', 'frontend-user-admin'); ?></h3>
<div class="inside">
<form method="post" action="?page=frontend-user-admin/frontend-user-admin-settings.php">
<table class="form-table" style="margin-bottom:5px;">
<tbody>
<tr><td>
<p><textarea name="phpcode3" rows="5"><?php if ( !empty($options['phpcode3_options']) ) echo htmlspecialchars($options['phpcode3_options']); ?></textarea></p>
</td></tr>
<tr><td>
<p><input type="submit" name="phpcode3_options_submit" value="<?php _e('Update Options &raquo;', 'frontend-user-admin'); ?>" class="button-primary" /></p>
</td></tr>
</tbody>
</table>
</form>
</div>
</div>

<?php do_action( 'fua_additional_options' ); ?>

<div class="postbox closed">
<div class="handlediv" title="<?php _e('Click to toggle', 'frontend-user-admin'); ?>"><br /></div>
<h3><?php _e('Option Converter', 'frontend-user-admin'); ?></h3>
<div class="inside">
<form method="post" onsubmit="return confirm('<?php _e('Are you sure to execute? It is recommended to export options before execution.', 'frontend-user-admin'); ?>');">
<table class="form-table" style="margin-bottom:5px;">
<tbody>
<tr><td>
<p><?php _e('Text before conversion', 'frontend-user-admin'); ?>:<br />
<input type="text" name="from_text" class="regular-text" value="<?php echo esc_attr(get_option('siteurl')); ?>" /></p>
<p><?php _e('Text after conversion', 'frontend-user-admin'); ?>:<br />
<input type="text" name="to_text" class="regular-text" value="" /></p>
<p><input type="submit" name="option_converter_submit" value="<?php _e('Convert Options &raquo;', 'frontend-user-admin'); ?>" class="button-primary" /></p>
</td></tr>
</tbody>
</table>
</form>
</div>
</div>

<div class="postbox closed">
<div class="handlediv" title="<?php _e('Click to toggle', 'frontend-user-admin'); ?>"><br /></div>
<h3><?php _e('Export Options', 'frontend-user-admin'); ?></h3>
<div class="inside">
<form method="post">
<table class="form-table" style="margin-bottom:5px;">
<tbody>
<tr><td>
<p><input type="submit" name="export_options_submit" value="<?php _e('Export Options &raquo;', 'frontend-user-admin'); ?>" class="button-primary" /></p>
</td></tr>
</tbody>
</table>
</form>
</div>
</div>

<div class="postbox closed">
<div class="handlediv" title="<?php _e('Click to toggle', 'frontend-user-admin'); ?>"><br /></div>
<h3><?php _e('Import Options', 'frontend-user-admin'); ?></h3>
<div class="inside">
<form method="post" enctype="multipart/form-data" onsubmit="return confirm('<?php _e('Are you sure to import options? Options you set will be overwritten.', 'frontend-user-admin'); ?>');">
<table class="form-table" style="margin-bottom:5px;">
<tbody>
<tr><td>
<p><input type="file" name="fuafile" /> <input type="submit" name="import_options_submit" value="<?php _e('Import Options &raquo;', 'frontend-user-admin'); ?>" class="button-primary" /></p>
</td></tr>
</tbody>
</table>
</form>
</div>
</div>

<div class="postbox closed">
<div class="handlediv" title="<?php _e('Click to toggle', 'frontend-user-admin'); ?>"><br /></div>
<h3><?php _e('Reset Options', 'frontend-user-admin'); ?></h3>
<div class="inside">
<form method="post" action="?page=frontend-user-admin/frontend-user-admin-settings.php" onsubmit="return confirm('<?php _e('Are you sure to execute?', 'frontend-user-admin'); ?>');">
<table class="form-table">
<tbody>
<tr><td>
<p><label><input type="checkbox" name="truncate_table_userlog" value="1" /> <?php _e('Truncate the user log table.', 'frontend-user-admin'); ?></label></p>
<p><label><input type="checkbox" name="truncate_table_usermail" value="1" /> <?php _e('Truncate the user mail table.', 'frontend-user-admin'); ?></label></p>
<p><label><input type="checkbox" name="reset_options" value="1" /> <?php _e('Reset Options.', 'frontend-user-admin'); ?></label></p>
<p><input type="submit" name="reset_options_submit" value="<?php _e('Execute &raquo;', 'frontend-user-admin'); ?>" class="button-primary" /></p>
</td></tr>
</tbody>
</table>
</form>
</div>
</div>

<div class="postbox closed">
<div class="handlediv" title="<?php _e('Click to toggle', 'frontend-user-admin'); ?>"><br /></div>
<h3><?php _e('Delete Options', 'frontend-user-admin'); ?></h3>
<div class="inside">
<form method="post" action="?page=frontend-user-admin/frontend-user-admin-settings.php" onsubmit="return confirm('<?php _e('Are you sure to execute?', 'frontend-user-admin'); ?>');">
<table class="form-table">
<tbody>
<tr><td>
<p><label><input type="checkbox" name="drop_table_userlog" value="1" /> <?php _e('Drop the user log table.', 'frontend-user-admin'); ?></label></p>
<p><label><input type="checkbox" name="drop_table_usermail" value="1" /> <?php _e('Drop the user mail table.', 'frontend-user-admin'); ?></label></p>
<p><label><input type="checkbox" name="delete_options" value="1" /> <?php _e('Delete Options.', 'frontend-user-admin'); ?></label></p>
<p><input type="submit" name="delete_options_submit" value="<?php _e('Execute &raquo;', 'frontend-user-admin'); ?>" class="button-primary" /></p>
</td></tr>
</tbody>
</table>
</form>
</div>
</div>
</div>

<style type="text/css">
.form-table td
.form-table th	{ padding:4px 3px 12px; 3px; width:auto; }
</style>
<script type="text/javascript">
// <![CDATA[
<?php if ( version_compare( substr($wp_version, 0, 3), '2.7', '<' ) ) { ?>
jQuery('.postbox h3').prepend('<a class="togbox">+</a> ');
<?php } ?>
jQuery('.postbox div.handlediv').click( function() { jQuery(jQuery(this).parent().get(0)).toggleClass('closed'); } );
jQuery('.postbox h3').click( function() { jQuery(jQuery(this).parent().get(0)).toggleClass('closed'); } );
jQuery('.postbox.close-me').each(function(){
jQuery(this).addClass("closed");
});
//-->
</script>

</div>
<?php
	}
	
	function attribute_name2label() {
		$options = $this->get_frontend_user_admin_data();
	
		$attribute_name2label['ms_domain']  = __('Site Domain', 'frontend-user-admin');
		$attribute_name2label['ms_title']   = __('Site Title', 'frontend-user-admin');
		$attribute_name2label['user_login'] = __('Username', 'frontend-user-admin');
		$attribute_name2label['user_email'] = __('E-mail', 'frontend-user-admin');
		$attribute_name2label['user_pass']  = __('Password', 'frontend-user-admin');
		$attribute_name2label['user_url']   = __('Website', 'frontend-user-admin');
		$attribute_name2label['first_name'] = __('First name', 'frontend-user-admin');
		$attribute_name2label['last_name']  = __('Last name', 'frontend-user-admin');
		$attribute_name2label['nickname']   = __('Nickname', 'frontend-user-admin');
		$attribute_name2label['aim']        = __('AIM', 'frontend-user-admin');
		$attribute_name2label['yim']        = __('Yahoo IM', 'frontend-user-admin');
		$attribute_name2label['jabber']     = __('Jabber / Google Talk', 'frontend-user-admin');
		$attribute_name2label['display_name']  = __('Display name publicly&nbsp;as', 'frontend-user-admin');
		$attribute_name2label['description']   = __('Biographical Info', 'frontend-user-admin');
		$attribute_name2label['role']          = __('Role', 'frontend-user-admin');
		$attribute_name2label['user_status']   = __('User status', 'frontend-user-admin');
		$attribute_name2label['no_log']        = __('No log', 'frontend-user-admin');
		$attribute_name2label['duplicate_login'] = __('Duplicate login', 'frontend-user-admin');
		
		$init_checkbox = array('ID' => 1, 'user_login' => 1);
		if ( !empty($options['user_attribute']['user_attribute']) && is_array($options['user_attribute']['user_attribute']) ) :
			foreach( $options['user_attribute']['user_attribute'] as $key => $val ) :
				$attribute_name2label[$val['name']] = $val['label'];
				if( $val['required'] ) $init_checkbox[$val['name']] = 1; 
			endforeach;
		endif;

		// If frontend_user_admin_profile_checkbox does not exist
		if( empty($options['global_settings']['profile_checkbox']) ) :
			if ( is_array($options) && is_array($options['global_settings']) ) :
				$options['global_settings']['profile_checkbox'] = $init_checkbox;
				update_option('frontend_user_admin', $options);
			endif;
		endif;
		
		return $attribute_name2label;
	}
	
	function check_attribute_data($val) {
		$default_value = array('ID', 'user_login', 'user_pass', 'user_nicename', 'user_email', 'user_url', 'user_registered', 'user_status', 'display_name');
		if( in_array($val, $default_value) ) return 0;
		else return 1;
	}
	
	function select_user_management_data( $args ) {
		global $wpdb, $wp_roles;
		$options = $this->get_frontend_user_admin_data();

		if ( !empty($options['user_attribute']['user_attribute']) ) $count_user_attribute = count($options['user_attribute']['user_attribute']);
		else $count_user_attribute = 0;
		
		if ( !isset($wpdb->base_prefix) ) $wpdb->base_prefix = $wpdb->prefix;

		extract( $args, EXTR_SKIP );

		if( !empty($order) ) :
			list($order, $by) = explode(".", $order);
			if( $by == "desc" ) $by = "DESC";
			else $by = "ASC";
		else :
			$order = 'ID';
			$by = 'DESC';
		endif;
			
		$query  = "SELECT `".$wpdb->base_prefix."users`.* FROM `".$wpdb->base_prefix."users` LEFT JOIN `".$wpdb->base_prefix."usermeta` ON `".$wpdb->base_prefix."users`.ID=`".$wpdb->base_prefix."usermeta`.user_id ";

		if( !empty($order) ) :
			if( $this->check_attribute_data($order) ) $query .= " AND `".$wpdb->base_prefix."usermeta`.meta_key='$order'";
		endif;
				
		$query .= " WHERE 1=1 ";
		
		if( !empty($user_id) ) $query .= " AND `".$wpdb->base_prefix."users`.ID = '" . $user_id . "'";
		
		if( !empty($q) && !empty($t) ) :
			if ( $t == 'role' ) :
				foreach ( $wp_roles->get_names() as $key => $name ) :
					$name = translate_user_role( $name );
					$q = str_replace($name,$key,$q);
				endforeach;
			endif;
			if ( !($q = preg_split('/\s/',$q)) ) $q[] = $q;
			if ( $t == 'role' ) :
				$blog_id = get_current_blog_id();
				$blog_prefix = $wpdb->get_blog_prefix($blog_id);
				$query .= " AND (";
				for( $i=0; $i<count($q); $i++ ) :
					$query .= $wpdb->prepare("(`".$wpdb->base_prefix."usermeta`.meta_key='".$blog_prefix."capabilities' AND `".$wpdb->base_prefix."usermeta`.meta_value LIKE %s) OR ", '%'.trim($q[$i]).'%');
				endfor;
				$query = trim($query,' OR');
				$query .= ")";
			elseif( $this->check_attribute_data($t) ) :
				$query .= " AND (";

				for( $i=0; $i<count($q); $i++ ) :
					if ( !empty($m) && in_array($m, array('<=', '>=', '>', '<')) ) :
						$cast = '';
						for ($j=0; $j<$count_user_attribute; $j++) :
							if ( $options['user_attribute']['user_attribute'][$j]['name'] == $t ) :
								$cast = $options['user_attribute']['user_attribute'][$j]['cast'];
								break;
							endif;
						endfor;
						if ( !empty($cast) ) :
							$query .= $wpdb->prepare("(`".$wpdb->base_prefix."usermeta`.meta_key='".$t."' AND CAST(`".$wpdb->base_prefix."usermeta`.meta_value AS " . $cast . ") ".$m." %s) OR ", trim($q[$i]));
						else :
							$query .= $wpdb->prepare("(`".$wpdb->base_prefix."usermeta`.meta_key='".$t."' AND `".$wpdb->base_prefix."usermeta`.meta_value ".$m." %s) OR ", trim($q[$i]));
						endif;
					elseif ( !empty($m) && $m == 'f' ) :
						$query .= $wpdb->prepare("(`".$wpdb->base_prefix."usermeta`.meta_key='".$t."' AND `".$wpdb->base_prefix."usermeta`.meta_value = %s) OR ", trim($q[$i]));
					else :
						$query .= $wpdb->prepare("(`".$wpdb->base_prefix."usermeta`.meta_key='".$t."' AND `".$wpdb->base_prefix."usermeta`.meta_value LIKE %s) OR ", '%'.trim($q[$i]).'%');
					endif;
				endfor;
				$query = trim($query,' OR');
				$query .= ")";
			else :
				$query .= " AND (";
				for( $i=0; $i<count($q); $i++ ) :
					if ( !empty($m) && in_array($m, array('<=', '>=', '>', '<')) ) :
						$query .= $wpdb->prepare("(`".$wpdb->base_prefix."users`.".$t." ".$m." %s) OR ", trim($q[$i]));
					elseif ( !empty($m) && $m == 'f' ) :
						$query .= $wpdb->prepare("(`".$wpdb->base_prefix."users`.".$t." = %s) OR  ", trim($q[$i]));
					else :
						$query .= $wpdb->prepare("(`".$wpdb->base_prefix."users`.".$t." LIKE %s) OR ", '%'.trim($q[$i]).'%');
					endif;
				endfor;
				$query = trim($query,' OR ');
				$query .= ")";
			endif;
		elseif ( !empty($q) && empty($t) ) :
			if ( !($q = preg_split('/\s/',$q)) ) $q[] = $q;
			$query .= " AND (";
			for( $i=0; $i<count($q); $i++ ) :
				$query .= " (";
				foreach( $options['global_settings']["profile_order"] as $val ) :
					if( $this->check_attribute_data($val) ) :
						if ( !empty($m) && in_array($m, array('<=', '>=', '>', '<')) ) :
							$cast = '';
							for ($j=0; $j<$count_user_attribute; $j++) :
								if ( $options['user_attribute']['user_attribute'][$j]['name'] == $val ) :
									$cast = $options['user_attribute']['user_attribute'][$j]['cast'];
									break;
								endif;
							endfor;
							if ( !empty($cast) ) :
								$query .= $wpdb->prepare("(`".$wpdb->base_prefix."usermeta`.meta_key='".$val."' AND CAST(`".$wpdb->base_prefix."usermeta`.meta_value AS " . $cast . ") ".$m." %s) OR ", trim($q[$i]));
							else :
								$query .= $wpdb->prepare("(`".$wpdb->base_prefix."usermeta`.meta_key='".$val."' AND `".$wpdb->base_prefix."usermeta`.meta_value ".$m." %s) OR ", trim($q[$i]));
							endif;
						elseif ( !empty($m) && $m == 'f' ) :
							$query .= $wpdb->prepare("(`".$wpdb->base_prefix."usermeta`.meta_key='".$val."' AND `".$wpdb->base_prefix."usermeta`.meta_value = %s) OR ", trim($q[$i]));
						else :
							$query .= $wpdb->prepare("(`".$wpdb->base_prefix."usermeta`.meta_key='".$val."' AND `".$wpdb->base_prefix."usermeta`.meta_value LIKE %s) OR ", '%'.trim($q[$i]).'%');
						endif;
					else :
						if ( !empty($m) && in_array($m, array('<=', '>=', '>', '<')) ) :
							$query .= $wpdb->prepare("(CONVERT(`".$wpdb->base_prefix."users`.`".$val."` USING utf8) ".$m." %s) OR ", trim($q[$i]));
						elseif ( !empty($m) && $m == 'f' ) :
							$query .= $wpdb->prepare("(CONVERT(`".$wpdb->base_prefix."users`.`".$val."` USING utf8) = %s) OR  ", trim($q[$i]));
						else :
							$query .= $wpdb->prepare("(CONVERT(`".$wpdb->base_prefix."users`.`".$val."` USING utf8) LIKE %s) OR ", '%'.trim($q[$i]).'%');
						endif;
					endif;
				endforeach;
				$query = trim($query,' OR');
				$query .= ") AND ";
			endfor;
			$query = trim($query,' AND');
			$query .= ")";
		endif;
		
		if ( isset($user_status) && is_numeric($user_status) ) :
			if ( $user_status == 1 ) :
				$query .= " AND `".$wpdb->base_prefix."users`.user_status=1 ";
			else :
				$query .= " AND `".$wpdb->base_prefix."users`.user_status=0 ";
			endif;
		endif;
		
		if ( function_exists('is_multisite') && is_multisite() ) :
			$query .= " AND `".$wpdb->base_prefix."users`.ID IN (SELECT `".$wpdb->base_prefix."usermeta`.user_id FROM `".$wpdb->base_prefix."usermeta` WHERE `".$wpdb->base_prefix."usermeta`.meta_key='".$wpdb->prefix."capabilities')";
			if ( !empty($options['global_settings']['exclude_admin_user']) && !current_user_can('administrator') ) :
				$query .= " AND `".$wpdb->base_prefix."users`.ID NOT IN (SELECT `".$wpdb->base_prefix."usermeta`.user_id FROM `".$wpdb->base_prefix."usermeta` WHERE `".$wpdb->base_prefix."usermeta`.meta_key='".$wpdb->prefix."capabilities' AND `".$wpdb->base_prefix."usermeta`.meta_value LIKE '%administrator%')";
			endif;
		endif;
		
		$query .= " GROUP BY `".$wpdb->base_prefix."users`.ID";

		if( !empty($order) ) :
			if( $this->check_attribute_data($order) ) :
				$cast = '';
				for ($j=0; $j<$count_user_attribute; $j++) :
					if ( $options['user_attribute']['user_attribute'][$j]['name'] == $order ) :
						$cast = $options['user_attribute']['user_attribute'][$j]['cast'];
						break;
					endif;
				endfor;
				if ( !empty($cast) ) :
					$query .= " ORDER BY CAST(`".$wpdb->base_prefix."usermeta`.meta_value AS " . $cast . ") " . $by;
				else :
					$query .= " ORDER BY `".$wpdb->base_prefix."usermeta`.meta_value $by";
				endif;
			else :
				$query .= " ORDER BY `".$wpdb->base_prefix."users`.$order $by";
			endif;
		endif;
		if( !isset($posts_per_page) || !is_numeric($posts_per_page) || $posts_per_page>100 ) $posts_per_page = 20;
		if( !isset($paged) || !is_numeric($paged) || $paged<1 ) $paged = 1;
		if( is_numeric($paged) && is_numeric($posts_per_page) ) $from = (int)($paged-1)*(int)$posts_per_page;
		else $from = 0;
		if ( (!isset($action) || (isset($action) && $action != 'export_user_data')) && empty($export) ) 
			$query .= " LIMIT $from, ".$posts_per_page;
		$users_result = $wpdb->get_results($query, ARRAY_A);

		if( !empty($users_result) ) :
			$count_users_result = count($users_result);
			for ( $i=0; $i<$count_users_result; $i++ ) :
				$query2 =  $wpdb->prepare("SELECT * FROM `".$wpdb->base_prefix."usermeta` WHERE `".$wpdb->base_prefix."usermeta`.user_id=%d", $users_result[$i]['ID']);
				$usermeta_result = $wpdb->get_results($query2, ARRAY_A);
				if( !empty($usermeta_result) ) :
					$count_usermeta_result = count($usermeta_result);
					for ( $j=0; $j<$count_usermeta_result; $j++ ) :
						$users_result[$i][$usermeta_result[$j]['meta_key']] = $usermeta_result[$j]['meta_value'];
					endfor;
				endif;
			endfor;
		endif;

		$query = preg_replace("/^SELECT (.*?)FROM(.*?)WHERE/","SELECT COUNT(*) FROM (SELECT `".$wpdb->base_prefix."users`.ID FROM `".$wpdb->base_prefix."users` LEFT JOIN `".$wpdb->base_prefix."usermeta` ON `".$wpdb->base_prefix."users`.ID=`".$wpdb->base_prefix."usermeta`.user_id WHERE",$query);
		$query = preg_replace("/ ORDER.*/","",$query);
		$query = preg_replace("/ LIMIT.*/","",$query);
		$query .= ') AS USERCOUNT';
		$total = $wpdb->get_var($query);
				
		$supplement = array('paged' => (int)$paged, 'posts_per_page' => (int)$posts_per_page, 'found_posts' => $total, 'max_num_pages' => ceil($total/$posts_per_page));
	
		if( !empty($check) ) :
			foreach( $check as $key => $val ) :
				$init_checkbox[$key] = 1;
			endforeach;
			
			$options = get_option('frontend_user_admin');
			$options['global_settings']['profile_checkbox'] = $init_checkbox;
			update_option('frontend_user_admin', $options);
		endif;
		
		return array($users_result, $supplement);
	}
		
	function frontend_user_admin_adduser() {
		global $wp_version;
		
		if ( !empty($_REQUEST['action']) && $_REQUEST['action'] == 'register' )
			$this->login_header('', $this->errors);

		if ( !empty($options['global_settings']['required_mark']) ) $required = $options['global_settings']['required_mark'];
		else $required = __('Required', 'frontend-user-admin');

		$options = $this->get_frontend_user_admin_data();
		$hidden = '';
?>

<style type="text/css">
.form-table textarea { margin-bottom:6px; width:500px; }
</style>

<div id="poststuff" style="position: relative; margin-top:10px;">
<div class="postbox">
<h3><?php _e('Add User', 'frontend-user-admin'); ?></h3>
<div class="inside">
<form name="registerform" id="registerform" method="post">
<table class="form-table">
<tbody>
<?php
			if ( !empty($options['global_settings']['register_order']) && is_array($options['global_settings']['register_order']) ) :
				foreach( $options['global_settings']['register_order'] as $val ) :
					switch ( $val ) :
						case "ms_domain": ?>
<?php if( is_multisite() && !empty($options['global_settings']['register_ms_domain']) ) : ?>
<tr id="tr_ms_domain">
<th><label for="ms_domain"><?php _e('Site Domain', 'frontend-user-admin') ?></label></th>
<td><input type="text" name="ms_domain" id="ms_domain" class="regular-text" value="<?php if ( !empty($_POST['ms_domain']) ) echo esc_attr($_POST['ms_domain']); ?>" /> <?php _e('Half-width alphamerics', 'frontend-user-admin') ?></td>
</tr>
<?php endif; ?>
<?php					break;
						case "ms_title": ?>
<?php if( is_multisite() && !empty($options['global_settings']['register_ms_title']) ) : ?>
<tr id="tr_ms_title">
<th><label for="ms_title"><?php _e('Site Title', 'frontend-user-admin') ?></label></th>
<td><input type="text" name="ms_title" id="ms_title" class="regular-text" value="<?php if ( !empty($_POST['ms_title']) ) echo esc_attr($_POST['ms_title']); ?>" /></td>
</tr>
<?php endif; ?>
<?php					break;
						case "user_login": ?>
<?php if( empty($options['global_settings']['email_as_userlogin']) && !empty($options['global_settings']['register_user_login']) ) : ?>
<tr id="tr_user_login">
<th><label for="user_login"><?php _e('Username', 'frontend-user-admin') ?></label></th>
<td><input type="text" name="user_login" id="user_login" class="regular-text" value="<?php if ( !empty($_POST['user_login']) ) echo esc_attr($_POST['user_login']); ?>" /> <?php _e('Half-width alphamerics', 'frontend-user-admin') ?></td>
</tr>
<?php endif; ?>
<?php					break;
						case "last_name": ?>
<?php if( !empty($options['global_settings']['register_last_name']) ) : ?>
<tr id="tr_last_name">
<th><label for="last_name"><?php _e('Last name', 'frontend-user-admin') ?></label></th>
<td><input type="text" name="last_name" id="last_name" class="regular-text" value="<?php if ( !empty($_POST['last_name']) ) echo esc_attr($_POST['last_name']); ?>" /><?php if ( !empty($options['global_settings']['register_last_name_required']) ) echo ' <span class="required">'.$required.'</span>'; ?></td>
</tr>
<?php endif; ?>
<?php					break;
						case "first_name": ?>
<?php if( !empty($options['global_settings']['register_first_name']) ) : ?>
<tr id="tr_first_name">
<th><label for="first_name"><?php _e('First name', 'frontend-user-admin') ?></label></th>
<td><input type="text" name="first_name" id="first_name" class="regular-text" value="<?php if ( !empty($_POST['first_name']) ) echo esc_attr($_POST['first_name']); ?>" /><?php if ( !empty($options['global_settings']['register_first_name_required']) ) echo ' <span class="required">'.$required.'</span>'; ?></td>
</tr>
<?php endif; ?>
<?php					break;
						case "nickname": ?>
<?php if( !empty($options['global_settings']['register_nickname']) ) :				
 ?>
<tr id="tr_nickname">
<th><label for="nickname"><?php _e('Nickname', 'frontend-user-admin') ?></label></th>
<td><input type="text" name="nickname" id="nickname" class="regular-text" value="<?php if ( !empty($_POST['nickname']) ) echo esc_attr($_POST['nickname']); ?>" /><?php if ( !empty($options['global_settings']['register_nickname_required']) ) echo ' <span class="required">'.$required.'</span>'; ?></td>
</tr>
<?php endif; ?>
<?php					break;
						case "user_email": ?>
<?php if( !empty($options['global_settings']['register_user_email']) ) : ?>
<tr id="tr_user_email">
<th><label for="user_email"><?php _e('E-mail', 'frontend-user-admin') ?></label></th>
<td><input type="text" name="user_email" id="<?php if ( !empty($options['global_settings']['email_as_userlogin']) ) echo 'user_login'; else echo 'user_email'; ?>" class="regular-text" value="<?php if ( !empty($_POST['user_email']) ) echo esc_attr($_POST['user_email']); ?>" size="50" /><?php echo ' <span class="required">'.$required.'</span>'; ?></td>
</tr>
<?php endif; ?>
<?php					break;
						case "user_url": ?>
<?php if( !empty($options['global_settings']['register_user_url']) ) : ?>
<tr id="tr_user_url">
<th><label for="user_url"><?php _e('Website', 'frontend-user-admin') ?></label></th>
<td><input type="text" name="user_url" id="user_url" class="regular-text" value="<?php if ( !empty($_POST['user_url']) ) echo esc_attr($_POST['user_url']); ?>" /><?php if ( !empty($options['global_settings']['register_user_url_required']) ) echo ' <span class="required">'.$required.'</span>'; ?></td>
</tr>
<?php endif; ?>
<?php					break;
						case "aim": ?>
<?php if( !empty($options['global_settings']['register_aim']) ) : ?>
<tr id="tr_aim">
<th><label for="aim"><?php _e('AIM', 'frontend-user-admin') ?></label></th>
<td><input type="text" name="aim" id="aim" class="regular-text" value="<?php if ( !empty($_POST['aim']) ) echo esc_attr($_POST['aim']); ?>" /><?php if ( !empty($options['global_settings']['register_aim_required']) ) echo ' <span class="required">'.$required.'</span>'; ?></td>
</tr>
<?php endif; ?>
<?php					break;
						case "yim": ?>
<?php if( !empty($options['global_settings']['register_yim']) ) : ?>
<tr id="tr_yim">
<th><label for="yim"><?php _e('Yahoo IM', 'frontend-user-admin') ?></label></th>
<td><input type="text" name="yim" id="yim" class="regular-text" value="<?php if ( !empty($_POST['yim']) ) echo esc_attr($_POST['yim']); ?>" /><?php if ( !empty($options['global_settings']['register_yim_required']) ) echo ' <span class="required">'.$required.'</span>'; ?></td>
</tr>
<?php endif; ?>
<?php					break;
						case "jabber": ?>
<?php if( !empty($options['global_settings']['register_jabber']) ) : ?>
<tr id="tr_jabber">
<th><label for="jabber"><?php _e('Jabber / Google Talk', 'frontend-user-admin') ?></label></th>
<td><input type="text" name="jabber" id="jabber" class="regular-text" value="<?php if ( !empty($_POST['jabber']) ) echo esc_attr($_POST['jabber']); ?>" /><?php if ( !empty($options['global_settings']['register_jabber_required']) ) echo ' <span class="required">'.$required.'</span>'; ?></td>
</tr>
<?php endif; ?>
<?php					break;
						case "description": ?>
<?php if( !empty($options['global_settings']['register_description']) ) : ?>
<tr id="tr_description">
<th><label for="description"><?php _e('Biographical Info', 'frontend-user-admin'); ?></label></th>
<td><textarea name="description" id="description" class="textarea net-shop-admin-description" rows="5" cols="30"><?php if ( !empty($_POST['description']) ) echo htmlspecialchars($_POST['description']) ?></textarea><?php if ( !empty($options['global_settings']['register_description_required']) ) echo ' <span class="required">'.$required.'</span> '; ?><?php _e('Share a little biographical information to fill out your profile. This may be shown publicly.', 'frontend-user-admin'); ?></td></tr>
<?php endif; ?>
<?php					break;
						case "role": ?>
<?php if( !empty($options['global_settings']['register_role']) ) : ?>
<tr id="tr_role">
<th><label for="role"><?php _e('Role', 'frontend-user-admin'); ?></label></th>
<td><select name="role" id="role">
<?php
if ( empty($new_user_role) )
	$new_user_role = !empty($current_role) ? $current_role : get_option('default_role');
	wp_dropdown_roles($new_user_role);
?>
</select></td></tr>
<?php endif; ?>
<?php					break;
						case "user_status": ?>
<?php if( !empty($options['global_settings']['register_user_status']) ) : ?>
<tr id="tr_user_status">
<th><label for="user_status"><?php _e('Change the user status', 'frontend-user-admin'); ?></label></th>
<td><select name="user_status">
<option value="0" <?php if ( isset($_POST['user_status']) ) selected($_POST['user_status'], 0); ?>><?php _e('Active', 'frontend-user-admin'); ?></option>
<option value="1" <?php if ( isset($_POST['user_status']) ) selected($_POST['user_status'], 1); ?>><?php _e('Pending', 'frontend-user-admin'); ?></option>
</select></td>
</tr>               
<?php endif; ?>
<?php					break;
						case "no_log": ?>
<?php if( !empty($options['global_settings']['register_no_log']) ) : ?>
<tr id="tr_no_log">
<th><label for="no_log"><?php _e('No log', 'frontend-user-admin'); ?></label></th>
<td><?php _e('This is for the setting when the user log start.', 'frontend-user-admin'); ?><br />
<select name="no_log">
<option value="0" <?php if ( isset($_POST['no_log']) ) selected($_POST['no_log'], 0); ?>><?php _e('Do log', 'frontend-user-admin'); ?></option>
<option value="1" <?php if ( isset($_POST['no_log']) ) selected($_POST['no_log'], 1); ?>><?php _e('Do not log', 'frontend-user-admin'); ?></option>
</select></td>
</tr>
<?php endif; ?>
<?php					break;
						case "duplicate_login": ?>
<?php if( !empty($options['global_settings']['register_duplicate_login']) ) : ?>
<tr id="tr_duplicate_login">
<th><label for="duplicate_login"><?php _e('Duplicate Login', 'frontend-user-admin'); ?></label></th>
<td><?php _e('This is for the setting only applied when the duplicate login is disabled.', 'frontend-user-admin'); ?><br />
<select name="duplicate_login">
<option value="0" <?php if ( isset($_POST['duplicate_login']) ) selected($_POST['duplicate_login'], 0); ?>><?php _e('Apply duplicate login invalidity', 'frontend-user-admin'); ?></option>
<option value="1" <?php if ( isset($_POST['duplicate_login']) ) selected($_POST['duplicate_login'], 1); ?>><?php _e('Enable duplicate login', 'frontend-user-admin'); ?></option>
</select></td>
</tr>
<?php endif; ?>
<?php					break;
						case "user_pass": ?>
<?php if( !empty($options['global_settings']['password_registration']) ) : ?>
<?php
$show_password_fields = apply_filters('show_password_fields', true);
if ( $show_password_fields ) :
?>
<tr id="tr_password">
<th><label for="pass1"><?php _e('Password', 'frontend-user-admin'); ?></label></th>
<td>
<input type="password" autocomplete="off" name="pass1" id="pass1" class="input" size="16" value="" /><br /><?php _e("Type your new password again.", 'frontend-user-admin'); ?><br />
<input type="password" autocomplete="off" name="pass2" id="pass2" class="input" size="16" value="">
</td>
</tr>
<?php if( !empty($options['global_settings']["use_password_strength"]) ) : ?>
<tr id="tr_password_strength">
<th><?php _e('Password Strength:', 'frontend-user-admin'); ?></th>
<td>
<div id="pass-strength-result"><?php _e('Strength indicator', 'frontend-user-admin'); ?></div>
<p style="clear:both;"><?php _e('Hint: Use upper and lower case characters, numbers and symbols like !"?$%^&amp;( in your password.', 'frontend-user-admin'); ?></p></td>
</tr>
<?php endif; ?>
<?php endif; ?>
<?php endif; ?>
<?php					break;
						default:
							if ( !empty($options['user_attribute']['user_attribute']) ) :
							if ( !empty($options['user_attribute']['user_attribute']) ) $count_user_attribute = count($options['user_attribute']['user_attribute']);
							else $count_user_attribute = 0;

							for($i=0;$i<$count_user_attribute;$i++) :
								if($options['user_attribute']['user_attribute'][$i]['name'] == $val && !empty($options['global_settings']['register_'.$options['user_attribute']['user_attribute'][$i]['name']]) ) :
								$start_year = 1900;
								$end_year = date_i18n('Y')+10;
								$before = '';
								$after = '';
								if ( !empty($options['user_attribute']['user_attribute'][$i]['readonly']) ) $readonly = ' readonly="readonly"';
								else $readonly = '';								
								if ( !empty($options['user_attribute']['user_attribute'][$i]['disabled']) ) $disabled = ' disabled="disabled"';
								else $disabled = '';								
								if ( !empty($options['user_attribute']['user_attribute'][$i]['overwrite_php']) )
									eval($options['user_attribute']['user_attribute'][$i]['overwrite_php']);
								if ( $options['user_attribute']['user_attribute'][$i]['type2']=='breakpoint' ) :
									echo '</tbody></table>'.$options['user_attribute']['user_attribute'][$i]['default'].'<table class="form-table"><tbody>';
								elseif ( $options['user_attribute']['user_attribute'][$i]['type2']=='hidden' ) :
									$hidden .= '<input type="hidden" name="'.esc_attr($options['user_attribute']['user_attribute'][$i]['name']).'" value="'.esc_attr($options['user_attribute']['user_attribute'][$i]['default']).'" />'."\n";
								else : ?>
<tr id="tr_<?php echo $val; ?>">
<th><label for="<?php echo $val; ?>"><?php echo $options['user_attribute']['user_attribute'][$i]['label']; ?></label>
<?php
								if ( $options['user_attribute']['user_attribute'][$i]['type2'] == 'radio' ) :
?>
 <a href="#clear" onclick="jQuery(this).parent().parent().find('input').attr('checked', false); return false;"><?php _e('Clear', 'frontend-user-admin') ?></a>
<?php							
								endif;
?>
</th>
<td><?php						if ( !empty($before) ) echo $before; ?>
<?php							switch($options['user_attribute']['user_attribute'][$i]['type2']) :
										case 'display': 
											echo $options['user_attribute']['user_attribute'][$i]['default'];
											break;
										case 'text': ?>
<input type="text" name="<?php echo $val; ?>" id="<?php echo $val; ?>" value="<?php if( isset($_POST[$val]) ) : echo esc_attr($_POST[$val]); else : echo esc_attr($options['user_attribute']['user_attribute'][$i]['default']); endif; ?>" class="regular-text"<?php if ( !empty($options['user_attribute']['user_attribute'][$i]['placeholder']) ) : ?> placeholder="<?php echo esc_attr($options['user_attribute']['user_attribute'][$i]['placeholder']); ?>"<?php endif; ?><?php echo $readonly; ?><?php echo $disabled; ?> />
<?php									break;
										case 'textarea': ?>
<textarea name="<?php echo esc_attr($val); ?>" id="<?php echo $val; ?>" class="textarea net-shop-admin-<?php echo $val; ?>"<?php if ( !empty($options['user_attribute']['user_attribute'][$i]['placeholder']) ) : ?> placeholder="<?php echo esc_attr($options['user_attribute']['user_attribute'][$i]['placeholder']); ?>"<?php endif; ?><?php echo $readonly; ?><?php echo $disabled; ?>><?php if( isset($_POST[$val]) ) : echo htmlspecialchars($_POST[$val]); else : echo htmlspecialchars($options['user_attribute']['user_attribute'][$i]['default']); endif; ?></textarea>
<?php									break;
										case 'select':
											preg_match_all('/"[^"]*"d?|\'[^\']*\'d?/', $options['user_attribute']['user_attribute'][$i]['default'], $matches);
										?>
<select name="<?php echo $val; ?>" id="<?php echo $val; ?>" class="select net-shop-admin-<?php echo $val; ?>">
<?php										foreach($matches[0] as $select_val) :
												unset($default);
												if(preg_match('/d$/', $select_val) && !isset($_POST[$val])) {
													$default = true;
												} else {
													$default = false;	
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
<option value="<?php echo esc_attr($value); ?>"<?php if ( (isset($_POST[$val]) && $value == $_POST[$val]) || $default) echo ' selected="selected"'; ?><?php echo $disabled; ?>><?php echo $label; ?></option>
<?php										endforeach; ?>
</select>
<?php									break;      
										case 'checkbox':
											preg_match_all('/"[^"]*"d?|\'[^\']*\'d?/', $options['user_attribute']['user_attribute'][$i]['default'], $matches);
										?>
<?php										
											if(isset($_POST[$val])) $_POST[$val] = maybe_unserialize($_POST[$val]);
											foreach($matches[0] as $select_val) :
												unset($default);
												if(preg_match('/d$/', $select_val) && !is_array($_POST[$val])) {
													$default = true;
												} else {
													$default = false;	
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
<input type="checkbox" name="<?php echo $val; ?>[]" class="checkbox net-shop-admin-<?php echo $val; ?>" value="<?php echo esc_attr($value); ?>"<?php if((isset($_POST[$val]) && is_array($_POST[$val]) && in_array($value, $_POST[$val])) || !empty($default) ) echo ' checked="checked"';?><?php echo $disabled; ?> /> <?php echo $label; ?> 
<?php										endforeach; ?>
<?php									break;                                                                          
										case 'radio':
											preg_match_all('/"[^"]*"d?|\'[^\']*\'d?/', $options['user_attribute']['user_attribute'][$i]['default'], $matches);
										?>
<?php										foreach($matches[0] as $select_val) :
												unset($default);
												if(preg_match('/d$/', $select_val) && !isset($_POST[$val])) {
													$default = true;
												} else {
													$default = false;	
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
<input type="radio" name="<?php echo $val; ?>" class="radio net-shop-admin-<?php echo $val; ?>" value="<?php echo esc_attr($value); ?>"<?php if( (isset($_POST[$val]) && $value == $_POST[$val]) || !empty($default) ) echo ' checked="checked"';?><?php echo $disabled; ?> /> <?php echo $label; ?> 
<?php										endforeach; ?>
<?php									break;
										case 'datetime':
											$profile_year = $profile_month = $profile_day = $profile_hour = $profile_minute = '';
											$profile_datetime = array();
											if ( isset($_POST[$val]) ) $profile_datetime = preg_split('/-|\s|:/', $_POST[$val]);
											$profile_year = isset($profile_datetime[0]) ? $profile_datetime[0] : '';
											$profile_month = isset($profile_datetime[1]) ? $profile_datetime[1] : '';
											$profile_day = isset($profile_datetime[2]) ? $profile_datetime[2] : '';
											$profile_hour = isset($profile_datetime[3]) ? $profile_datetime[3] : '';
											$profile_minute = isset($profile_datetime[4]) ? $profile_datetime[4] : '';
											if ( !empty($options['user_attribute']['user_attribute'][$i]['default']) ) :
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
								endswitch;
								if ( !empty($after) ) echo $after;
								if($options['user_attribute']['user_attribute'][$i]['required']) echo ' <span class="required">'.$required.'</span>'; ?></td>
</tr><?php
								endif;
							break;
							endif;
						endfor;
						endif;
					endswitch;
				endforeach;
			endif;
?>

<?php if( !empty($options['global_settings']['terms_of_use_check']) ) : ?>
<tr id="tr_terms_of_use">
<th><label for="terms_of_use"><?php _e('Terms of use', 'frontend-user-admin') ?></label></th>
<td><input type="checkbox" name="terms_of_use" id="terms_of_use" value="1" <?php if ( !empty($_POST['terms_of_use']) ) checked(1); ?> /> 
<?php if($options['global_settings']['terms_of_use_url']) : ?>
<?php sprintf(__('I agree to the <a href="%s" target="_blank">terms of use</a>.', 'frontend-user-admin'), $options['global_settings']['terms_of_use_url'] ) ?></a>
<?php else: ?>
<?php _e('I agree to the terms of use.', 'frontend-user-admin') ?>
<?php endif; ?>
</td></tr>
<?php endif; ?>
<tr id="tr_send_email">
<th><label for="send_email"><?php _e('Send email to the new user?', 'frontend-user-admin'); ?></label></th>
<td><input type="checkbox" name="send_email" id="send_email" value="1" /></td>
</tr>
<tr>
<th colspan="2">
<input type="submit" name="wp-submit" id="wp-submit" value="<?php _e('Register &raquo;', 'frontend-user-admin'); ?>" class="button-primary" />
</th>
</tbody>
</table>
<input type="hidden" name="page" value="frontend-user-admin/frontend-user-admin-adduser.php" />
<input type="hidden" name="action" value="register" />
<?php do_action('register_form'); ?>
<?php if ( !empty($hidden) ) echo $hidden; ?>
</form>
</div>
</div>
</div>
<?php
	}
	
	function frontend_user_admin_edituser() {
		global $wp_version;

		if ( isset($_REQUEST['updated']) && !is_wp_error($this->errors) ) :
			$this->errors = new WP_Error();
			$this->errors->add('updated', __('User updated.', 'frontend-user-admin'), 'message');
		endif;
		$this->login_header('', $this->errors);
		
		if ( !empty($options['global_settings']['required_mark']) ) $required = $options['global_settings']['required_mark'];
		else $required = __('Required', 'frontend-user-admin');

		$options = $this->get_frontend_user_admin_data();
		$profileuser = $this->get_user_to_edit( (int)$_REQUEST['user_id'] );
		
		if ( !empty($_POST) && is_wp_error($this->errors) ) :
			foreach ( $_POST as $key => $val ) :
				$profileuser->{$key} = $val;
			endforeach;
		endif;
		
		$hidden = '';
?>
<style type="text/css">
.form-table textarea { margin-bottom:6px; width:500px; }
.thumbnail { vertical-align:middle; margin:5px 0; }
</style>

<div id="poststuff" class="meta-box-sortables" style="position: relative; margin-top:10px;">
<div class="stuffbox">
<h3><?php _e('Edit User', 'frontend-user-admin'); ?></h3>
<div class="inside">
<form name="profile" id="your-profile" action="" method="post" enctype="multipart/form-data">
<?php wp_nonce_field('update-user_' . $profileuser->user_id) ?>
<table class="form-table">
<tbody>
<?php
			if ( !empty($options['global_settings']['profile_order']) && is_array($options['global_settings']['profile_order']) ) :
				foreach( $options['global_settings']['profile_order'] as $val ) :
					switch ( $val ) :
						case "ms_domain": ?>
<?php if( is_multisite() && !empty($options['global_settings']['profile_ms_domain']) ) : ?>
<tr id="tr_ms_domain">
<th><label for="ms_domain"><?php _e('Site Domain', 'frontend-user-admin') ?></label></th>
<td><input type="text" name="ms_domain" id="ms_domain" class="regular-text" value="<?php if ( !empty($_POST['ms_domain']) ) echo esc_attr($_POST['ms_domain']); ?>" disabled="disabled" /> <?php _e('Half-width alphamerics', 'frontend-user-admin') ?></td>
</tr>
<?php endif; ?>
<?php					break;
						case "ms_title": ?>
<?php if( is_multisite() && !empty($options['global_settings']['profile_ms_title']) ) : ?>
<tr id="tr_ms_title">
<th><label for="ms_title"><?php _e('Site Title', 'frontend-user-admin') ?></label></th>
<td><input type="text" name="ms_title" id="ms_title" class="regular-text" value="<?php if ( !empty($_POST['ms_title']) ) echo esc_attr($_POST['ms_title']); ?>" /></td>
</tr>
<?php endif; ?>
<?php					break;
						case "user_login": ?>
<tr id="tr_user_login">
<th><label for="user_login"><?php _e('Username', 'frontend-user-admin'); ?></label></th>
<td><input type="text" name="user_login" id="user_login" class="regular-text" value="<?php if ( !empty($profileuser->user_login) ) echo $profileuser->user_login; ?>" disabled="disabled" /> <?php _e('Your username cannot be changed', 'frontend-user-admin'); ?></td>
</tr>
<?php					break;
						case "last_name": ?>
<?php if( !empty($options['global_settings']['profile_last_name']) ) : ?>
<tr id="tr_last_name">
<th><label for="last_name"><?php _e('Last name', 'frontend-user-admin') ?></label></th>
<td><input type="text" name="last_name" id="last_name" class="regular-text" value="<?php if ( !empty($profileuser->last_name) ) echo $profileuser->last_name ?>" /><?php if ( !empty($options['global_settings']['profile_last_name_required']) ) echo ' <span class="required">'.$required.'</span>'; ?></td>
</tr>
<?php endif; ?>
<?php					break;
						case "first_name": ?>
<?php if( !empty($options['global_settings']['profile_first_name']) ) : ?>
<tr id="tr_first_name">
<th><label for="first_name"><?php _e('First name', 'frontend-user-admin') ?></label></th>
<td><input type="text" name="first_name" id="first_name" class="regular-text" value="<?php if ( !empty($profileuser->first_name) ) echo $profileuser->first_name ?>" /><?php if ( !empty($options['global_settings']['profile_first_name_required']) ) echo ' <span class="required">'.$required.'</span>'; ?></td>
</tr>
<?php endif; ?>
<?php					break;
						case "nickname": ?>
<?php if( !empty($options['global_settings']['profile_nickname']) ) : ?>
<tr id="tr_nickname">
<th><label for="nickname"><?php _e('Nickname', 'frontend-user-admin') ?></label></th>
<td><input type="text" name="nickname" id="nickname" class="regular-text" value="<?php if ( !empty($profileuser->nickname) ) echo $profileuser->nickname ?>" /><?php if ( !empty($options['global_settings']['profile_nickname_required']) ) echo ' <span class="required">'.$required.'</span>'; ?></td>
</tr>
<?php endif; ?>
<?php					break;
						case "display_name": ?>
<?php if( !empty($options['global_settings']['profile_display_name']) ) : ?>
<tr id="tr_display_name">
<th><label for="display_name"><?php _e('Display name publicly&nbsp;as', 'frontend-user-admin') ?></label></th>
<td>
<select name="display_name" id="display_name" class="select net-shop-admin-display_name">
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
</td>
</tr>
<?php endif; ?>
<?php					break;
						case "user_email": ?>
<?php if( !empty($options['global_settings']['profile_user_email']) ) : ?>
<tr id="tr_user_email">
<th><label for="user_email"><?php _e('E-mail', 'frontend-user-admin') ?></label></th>
<td><input type="text" name="user_email" id="<?php if ( !empty($options['global_settings']['email_as_userlogin']) ) echo 'user_login'; else echo 'user_email'; ?>" class="regular-text" value="<?php if ( !empty($profileuser->user_email) ) echo $profileuser->user_email; ?>" /><?php echo ' <span class="required">'.$required.'</span>'; ?></td>
</tr>
<?php endif; ?>
<?php					break;
						case "user_url": ?>
<?php if( !empty($options['global_settings']['profile_user_url']) ) : ?>
<tr id="tr_user_url">
<th><label for="user_url"><?php _e('Website', 'frontend-user-admin') ?></label></th>
<td><input type="text" name="user_url" id="user_url" class="regular-text" value="<?php if ( !empty($profileuser->user_url) ) echo $profileuser->user_url; ?>" /><?php if ( !empty($options['global_settings']['profile_user_url_required']) ) echo ' <span class="required">'.$required.'</span>'; ?></td>
</tr>
<?php endif; ?>
<?php					break;
						case "aim": ?>
<?php if( !empty($options['global_settings']['profile_aim']) ) : ?>
<tr id="tr_aim">
<th><label for="aim"><?php _e('AIM', 'frontend-user-admin') ?></label></th>
<td><input type="text" name="aim" id="aim" class="regular-text" value="<?php if ( !empty($profileuser->aim) ) echo $profileuser->aim; ?>" /><?php if ( !empty($options['global_settings']['profile_aim_required']) ) echo ' <span class="required">'.$required.'</span>'; ?></td>
</tr>
<?php endif; ?>
<?php					break;
						case "yim": ?>
<?php if( !empty($options['global_settings']['profile_yim']) ) : ?>
<tr id="tr_yim">
<th><label for="yim"><?php _e('Yahoo IM', 'frontend-user-admin') ?></label></th>
<td><input type="text" name="yim" id="yim" class="regular-text" value="<?php if ( !empty($profileuser->yim) ) echo $profileuser->yim; ?>" /><?php if ( !empty($options['global_settings']['profile_yim_required']) ) echo ' <span class="required">'.$required.'</span>'; ?></td>
</tr>
<?php endif; ?>
<?php					break;
						case "jabber": ?>
<?php if( !empty($options['global_settings']['profile_jabber']) ) : ?>
<tr id="tr_jabber">
<th><label for="jabber"><?php _e('Jabber / Google Talk', 'frontend-user-admin') ?></label></th>
<td><input type="text" name="jabber" id="jabber" class="regular-text" value="<?php if ( !empty($profileuser->jabber) ) echo $profileuser->jabber; ?>" /><?php if ( !empty($options['global_settings']['profile_jabber_required']) ) echo ' <span class="required">'.$required.'</span>'; ?></td>
</tr>
<?php endif; ?>
<?php					break;
						case "description": ?>
<?php if( !empty($options['global_settings']['profile_description']) ) : ?>
<tr id="tr_description">
<th><label for="description"><?php _e('Biographical Info', 'frontend-user-admin'); ?></label></th>
<td><textarea name="description" id="description" class="textarea net-shop-admin-description" rows="5" cols="30"><?php if ( !empty($profileuser->description) ) echo $profileuser->description; ?></textarea><br /><?php if ( !empty($options['global_settings']['profile_description_required']) ) echo ' <span class="required">'.$required.'</span> '; ?><?php _e('Share a little biographical information to fill out your profile. This may be shown publicly.', 'frontend-user-admin'); ?></td>
</tr>
<?php endif; ?>
<?php					break;
						case "role": ?>
<?php if( !empty($options['global_settings']['profile_role']) ) : ?>
<tr id="tr_role">
<th><label for="role"><?php _e('Role', 'frontend-user-admin') ?></label></th>
<td><select name="role" id="role">
<?php
$user_roles = $profileuser->roles;
$user_role = array_shift($user_roles);
wp_dropdown_roles($user_role);
?>
</select></td></tr>
<?php endif; ?>
<?php					break;
						case "user_status": ?>
<?php if( !empty($options['global_settings']['profile_user_status']) ) : ?>
<tr id="tr_user_status">
<th><label for="user_status"><?php _e('Change the user status', 'frontend-user-admin'); ?></label></th>
<td><select name="user_status" onchange="if(jQuery(this).val()==0) { jQuery('#send_email').attr('disabled', false); } else { jQuery('#send_email').attr('disabled', true); }">
<option value="0" <?php selected($profileuser->user_status, 0); ?>><?php _e('Active', 'frontend-user-admin'); ?></option>
<option value="1" <?php selected($profileuser->user_status, 1); ?>><?php _e('Pending', 'frontend-user-admin'); ?></option>
</select></td>
</tr>               
<?php endif; ?>
<?php					break;
						case "no_log": ?>
<?php if( !empty($options['global_settings']['profile_no_log']) ) : ?>
<tr id="tr_no_log">
<th><label for="no_log"><?php _e('No log', 'frontend-user-admin'); ?></label></th>
<td><?php _e('This is for the setting when the user log start.', 'frontend-user-admin'); ?><br />
<select name="no_log">
<option value="0" <?php if ( isset($profileuser->no_log) ) selected($profileuser->no_log, 0); ?>><?php _e('Do log', 'frontend-user-admin'); ?></option>
<option value="1" <?php if ( isset($profileuser->no_log) ) selected($profileuser->no_log, 1); ?>><?php _e('Do not log', 'frontend-user-admin'); ?></option>
</select></td>
</tr>
<?php endif; ?>
<?php					break;
						case "duplicate_login": ?>
<?php if( !empty($options['global_settings']['profile_duplicate_login']) ) : ?>
<tr id="tr_duplicate_login">
<th><label for="duplicate_login"><?php _e('Duplicate Login', 'frontend-user-admin'); ?></label></th>
<td><?php _e('This is for the setting only applied when the duplicate login is disabled.', 'frontend-user-admin'); ?><br />
<select name="duplicate_login">
<option value="0" <?php if ( isset($profileuser->duplicate_login) ) selected($profileuser->duplicate_login, 0); ?>><?php _e('Apply duplicate login invalidity', 'frontend-user-admin'); ?></option>
<option value="1" <?php if ( isset($profileuser->duplicate_login) ) selected($profileuser->duplicate_login, 1); ?>><?php _e('Enable duplicate login', 'frontend-user-admin'); ?></option>
</select></td>
</tr>
<?php endif; ?>
<?php					break;
						case "user_pass": ?>
<?php if( !empty($options['global_settings']['profile_user_pass']) ) : ?>
<?php
$show_password_fields = apply_filters('show_password_fields', true);
if ( $show_password_fields ) :
?>
<tr id="tr_password">
<th><label for="pass1"><?php _e('New Password', 'frontend-user-admin'); ?></label></th>
<td><input type="password" autocomplete="off" name="pass1" id="pass1" class="input net-shop-admin-pass1" size="16" value="" /><br /><?php _e("If you would like to change the password type a new one. Otherwise leave this blank.", 'frontend-user-admin'); ?><br />
<input type="password" autocomplete="off" name="pass2" id="pass2" class="input net-shop-admin-pass2" size="16" value="" /><br /><?php _e("Type your new password again.", 'frontend-user-admin'); ?></td>
</tr>
<?php if( !empty($options['global_settings']["use_password_strength"]) ) : ?>
<tr id="tr_password_strength">
<th><?php _e('Password Strength', 'frontend-user-admin'); ?></th>
<td><div id="pass-strength-result"><?php _e('Strength indicator', 'frontend-user-admin'); ?></div>
<p style="clear:both;"><?php _e('Hint: Use upper and lower case characters, numbers and symbols like !"?$%^&amp;( in your password.', 'frontend-user-admin'); ?></p>
</td>
</tr>
<?php endif; ?>
<?php endif; ?>
<?php endif; ?>
<?php					break;
						default:
							if ( !empty($options['user_attribute']['user_attribute']) ) $count_user_attribute = count($options['user_attribute']['user_attribute']);
							else $count_user_attribute = 0;

							for($i=0;$i<$count_user_attribute;$i++) :
								if( !empty($options['user_attribute']['user_attribute'][$i]['name']) && $options['user_attribute']['user_attribute'][$i]['name'] == $val && !empty($options['global_settings']['profile_'.$options['user_attribute']['user_attribute'][$i]['name']]) ) :
								$start_year = 1900;
								$end_year = date_i18n('Y')+10;
								$before = '';
								$after = '';
								if ( !empty($options['user_attribute']['user_attribute'][$i]['readonly']) ) $readonly = ' readonly="readonly"';
								else $readonly = '';								
								if ( !empty($options['user_attribute']['user_attribute'][$i]['disabled']) ) $disabled = ' disabled="disabled"';
								else $disabled = '';								
								if ( !empty($options['user_attribute']['user_attribute'][$i]['overwrite_php']) )
									eval($options['user_attribute']['user_attribute'][$i]['overwrite_php']);
								if ( $options['user_attribute']['user_attribute'][$i]['type2']=='breakpoint' ) :
									echo '</tbody></table>'.$options['user_attribute']['user_attribute'][$i]['default'].'<table class="form-table"><tbody>';
								elseif ( $options['user_attribute']['user_attribute'][$i]['type2']=='hidden' ) :
									$hidden .= '<input type="hidden" name="'.esc_attr($options['user_attribute']['user_attribute'][$i]['name']).'" value="'.esc_attr($options['user_attribute']['user_attribute'][$i]['default']).'" />'."\n";
								else : ?>
<tr id="tr_<?php echo $val; ?>">
<th><label for="<?php echo $val; ?>"><?php echo $options['user_attribute']['user_attribute'][$i]['label']; ?></label>
<?php
								if ( $options['user_attribute']['user_attribute'][$i]['type2'] == 'radio' ) :
?>
 <a href="#clear" onclick="jQuery(this).parent().parent().find('input').attr('checked', false); return false;"><?php _e('Clear', 'frontend-user-admin') ?></a>
<?php							
								endif;
?>
</th>
<td><?php						if ( !empty($before) ) echo $before; ?>
<?php							switch($options['user_attribute']['user_attribute'][$i]['type2']) :
										case 'display': 
											if( isset($profileuser->{$val}) ) : echo $profileuser->{$val}; else : echo $options['user_attribute']['user_attribute'][$i]['default']; endif;
											break;
										case 'text': ?>
<input type="text" name="<?php echo $val; ?>" id="<?php echo $val; ?>" value="<?php if( isset($profileuser->{$val}) ) : echo esc_attr($profileuser->{$val}); else : echo esc_attr($options['user_attribute']['user_attribute'][$i]['default']); endif; ?>" class="regular-text frontend-user-admin-<?php echo $val; ?>"<?php if ( !empty($options['user_attribute']['user_attribute'][$i]['placeholder']) ) : ?> placeholder="<?php echo esc_attr($options['user_attribute']['user_attribute'][$i]['placeholder']); ?>"<?php endif; ?><?php echo $readonly; ?><?php echo $disabled; ?> />
<?php										break;
										case 'textarea': ?>
<textarea name="<?php echo esc_attr($val); ?>" id="<?php echo $val; ?>" class="textarea frontend-user-admin-<?php echo $val; ?>"<?php if ( !empty($options['user_attribute']['user_attribute'][$i]['placeholder']) ) : ?> placeholder="<?php echo esc_attr($options['user_attribute']['user_attribute'][$i]['placeholder']); ?>"<?php endif; ?><?php echo $readonly; ?><?php echo $disabled; ?>><?php if( isset($profileuser->{$val}) ) : echo htmlspecialchars($profileuser->{$val}); else : echo htmlspecialchars($options['user_attribute']['user_attribute'][$i]['default']); endif; ?></textarea>
<?php										break;
										case 'select':
											preg_match_all('/"[^"]*"d?|\'[^\']*\'d?/', $options['user_attribute']['user_attribute'][$i]['default'], $matches);
										?>
<select name="<?php echo $val; ?>" id="<?php echo $val; ?>" class="select net-shop-admin-<?php echo $val; ?>">
<?php										foreach($matches[0] as $select_val) :
												unset($default);
												$select_val = rtrim($select_val,'d');
												$select_val = rtrim(trim($select_val,'"|\''),'"|\'');
												if ( preg_match('/([^\|]*)\|([^\|]*)/', $select_val, $select_val2 ) ) :
													$label = $select_val2[1];
													$value = $select_val2[2];
												else:
													$label = $select_val;
													$value = $select_val;
												endif;?>
<option value="<?php echo esc_attr($value); ?>"<?php if( (isset($profileuser->{$val}) && $value == $profileuser->{$val}) || !empty($default) ) echo ' selected="selected"'; ?><?php echo $disabled; ?>><?php echo $label; ?></option>
<?php										endforeach; ?>
</select>
<?php										break;
										case 'checkbox':
											preg_match_all('/"[^"]*"d?|\'[^\']*\'d?/', $options['user_attribute']['user_attribute'][$i]['default'], $matches);
										?>
<?php										
											if( !empty($profileuser->{$val}) ) $profileuser->{$val} = maybe_unserialize($profileuser->{$val});
											foreach($matches[0] as $select_val) :
												unset($default);
												$select_val = rtrim($select_val,'d');
												$select_val = rtrim(trim($select_val,'"|\''),'"|\'');
												if ( preg_match('/([^\|]*)\|([^\|]*)/', $select_val, $select_val2 ) ) :
													$label = $select_val2[1];
													$value = $select_val2[2];
												else:
													$label = $select_val;
													$value = $select_val;
												endif;?>
<label><input type="checkbox" name="<?php echo $val; ?>[]" class="checkbox frontend-user-admin-<?php echo $val; ?>" value="<?php echo esc_attr($value); ?>"<?php if((isset($profileuser->{$val}) && is_array($profileuser->{$val}) && in_array($value, $profileuser->{$val})) || !empty($default) ) echo ' checked="checked"';?><?php echo $disabled; ?> /> <?php echo $label; ?></label> 
<?php										endforeach; ?>
<?php										break;                                                                          
										case 'radio':
											preg_match_all('/"[^"]*"d?|\'[^\']*\'d?/', $options['user_attribute']['user_attribute'][$i]['default'], $matches);
										?>
<?php										foreach($matches[0] as $select_val) :
												unset($default);
												$select_val = rtrim($select_val,'d');
												$select_val = rtrim(trim($select_val,'"|\''),'"|\'');
												if ( preg_match('/([^\|]*)\|([^\|]*)/', $select_val, $select_val2 ) ) :
													$label = $select_val2[1];
													$value = $select_val2[2];
												else:
													$label = $select_val;
													$value = $select_val;
												endif;
?>
<label><input type="radio" name="<?php echo $val; ?>" class="radio frontend-user-admin-<?php echo $val; ?>" value="<?php echo esc_attr($value); ?>"<?php if( (isset($profileuser->{$val}) && $value == $profileuser->{$val}) || !empty($default) ) echo ' checked="checked"';?><?php echo $disabled; ?> /> <?php echo $label; ?></label> 
<?php										endforeach; ?>
<?php										break;
										case 'datetime':
											$profile_year = $profile_month = $profile_day = $profile_hour = $profile_minute = '';
											$profile_datetime = array();
											if ( isset($profileuser->{$val}) ) $profile_datetime = preg_split('/-|\s|:/', $profileuser->{$val});
											$profile_year = isset($profile_datetime[0]) ? $profile_datetime[0] : '';
											$profile_month = isset($profile_datetime[1]) ? $profile_datetime[1] : '';
											$profile_day = isset($profile_datetime[2]) ? $profile_datetime[2] : '';
											$profile_hour = isset($profile_datetime[3]) ? $profile_datetime[3] : '';
											$profile_minute = isset($profile_datetime[4]) ? $profile_datetime[4] : '';
											if( !empty($options['user_attribute']['user_attribute'][$i]['default']) ) :
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
<input type="file" name="<?php echo $val; ?>" id="<?php echo $val; ?>" class="regular-text frontend-user-admin-<?php echo $val; ?>" />
<?php
										if( !empty($profileuser->{$val}) ) :
											$image_data = wp_get_attachment_image_src($profileuser->{$val}, 'thumbnail', false);
?>
<br /><a href="media.php?attachment_id=<?php echo $profileuser->{$val}; ?>&action=edit"><img src="<?php echo $image_data[0]; ?>" width="32" height="32" alt="<?php echo $options['user_attribute']['user_attribute'][$i]['label']; ?>" class="thumbnail" /></a> <input type="hidden" name="<?php echo $val; ?>" value="<?php echo $profileuser->{$val}; ?>" /> <input type="checkbox" name="<?php echo $val; ?>_delete" value="1" /> <?php _e('Delete', 'frontend-user-admin'); ?>
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
								if ( !empty($options['user_attribute']['user_attribute'][$i]['required']) ) echo ' <span class="required">'.$required.'</span>'; ?></td>
</tr>
<?php							endif;
							break;
							endif;
						endfor;
					endswitch;
				endforeach;
			endif;

			if ( isset($options['global_settings']['password_lock_miss_times']) && is_numeric($options['global_settings']['password_lock_miss_times']) ) :
?>
<tr id="tr_password_lock_miss_times">
<th><label for="password_lock_miss_times"><?php _e('Password Lock Miss Times', 'frontend-user-admin'); ?></label></th>
<td><input type="text" name="password_lock_miss_times" class="small-text" value="<?php echo $profileuser->password_lock_miss_times; ?>" /> <?php _e('times', 'frontend-user-admin'); ?></td>
</tr>
<?php
			endif;
?>
<tr id="tr_send_email">
<th><label for="send_email"><?php _e('Send email to the new user?', 'frontend-user-admin'); ?></label></th>
<td><input type="checkbox" name="send_email" id="send_email" value="1" <?php if( $profileuser->user_status==1 ) echo 'disabled="disabled"'; ?> /></td></tr>
<tr>
<?php
			if ( !empty($options['global_settings']['record_login_datetime']) ) :
?>
<tr id="tr_login_datetime">
<th><label for="login_datetime"><?php _e('Login Datetime', 'frontend-user-admin'); ?></label></th>
<td><?php if ( !empty($profileuser->login_datetime) ) echo date_i18n('Y-m-d H:i:s', $profileuser->login_datetime); ?></td>
</tr>
<?php
			endif;

			if ( !empty($options['global_settings']['record_update_datetime']) ) :
?>
<tr id="tr_update_datetime">
<th><label for="update_datetime"><?php _e('Update Datetime', 'frontend-user-admin'); ?></label></th>
<td><?php if ( !empty($profileuser->update_datetime) ) echo date_i18n('Y-m-d H:i:s', $profileuser->update_datetime); ?></td>
</tr><?php
			endif;
?>
<tr id="tr_user_registered">
<th><label for="user_registered"><?php _e('Registered Datetime', 'frontend-user-admin'); ?></label></th>
<td><?php if ( !empty($profileuser->user_registered) ) echo date_i18n('Y-m-d H:i:s', strtotime($profileuser->user_registered)+get_option('gmt_offset') * 3600); ?></td>
</tr>
<tr>
<td colspan="2">
<input type="button" value="<?php _e('&laquo; Back', 'frontend-user-admin') ?>" onclick="history.back();" class="button" /> <input type="submit" value="<?php _e('Update User &raquo;', 'frontend-user-admin') ?>" name="submit" class="button-primary" />
</td>
</tr>
</tbody>
</table>

<input type="hidden" name="action" value="update" />
<input type="hidden" name="user_id" id="user_id" value="<?php echo $profileuser->user_id; ?>" />
<input type="hidden" name="page" value="frontend-user-admin/frontend-user-admin.php" />
<input type="hidden" name="option" value="edituser" />
<?php if ( !empty($hidden) ) echo $hidden; ?>
</form>
</div>
</div>

<div class="postbox closed">
<div class="handlediv" title="<?php _e('Click to toggle', 'frontend-user-admin'); ?>"><br /></div>
<h3><?php _e('Instant Editor', 'frontend-user-admin'); ?></h3>
<div class="inside">
<form name="instant_editor" id="instant_editor" action="" method="post">
<table class="form-table" style="margin-bottom:5px;">
<tbody>
<tr><th style="text-align:center; width:210px;"><?php _e('Field Name', 'frontend-user-admin'); ?></th><th style="text-align:center; width:50px;"><?php _e('Load', 'frontend-user-admin'); ?></th><th style="text-align:center;"><?php _e('Value', 'frontend-user-admin'); ?></th><th style="text-align:center; width:50px;"><?php _e('Array', 'frontend-user-admin'); ?></th><th style="text-align:center; width:50px;"><?php _e('Delete', 'frontend-user-admin'); ?></th></tr>
<tr id="instant_editor_base"><td style="text-align:center;"><input type="text" name="user_key[]" style="width:200px;" onKeyPress="return submitStop(event);" /></td>
<td style="text-align:center;"><input type="button" value="<?php _e('Load', 'frontend-user-admin'); ?>" onclick="jQuery(this).parent().find('img').show(); jQuery(this).parent().parent().find('textarea').attr('disabled',true);
jQuery.ajax({ context: this, type: 'POST', url: '<?php echo admin_url( 'admin-ajax.php' ); ?>', data: { 'action': 'frontend_user_admin_get_user_meta', 'user_id': '<?php echo $profileuser->user_id; ?>', 'user_key':jQuery(this).parent().parent().find('input[type=text]').val() }, success: function(data){ var arr = data.slice(0,1); if ( arr==1 ) {jQuery(this).parent().parent().find('input[type=hidden]').attr('disabled',true); jQuery(this).parent().parent().find('input[type=checkbox]').attr('checked',true); } data=data.slice(1); jQuery(this).parent().find('img').hide(); jQuery(this).parent().parent().find('textarea').attr('disabled',false); jQuery(this).parent().parent().find('textarea').val(data); }});" /><br /><img src="../<?php echo PLUGINDIR; ?>/frontend-user-admin/images/loading.gif" width="16" height="16" alt="Loading..." id="loading" style="display:none;" /></td>
<td><textarea name="user_value[]" rows="2" style="width:100%;"></textarea></td>
<td style="text-align:center;"><input type="hidden" name="user_array[]" value="0" /><input type="checkbox" name="user_array[]" value="1" onclick="if ( jQuery(this).attr('checked')==true || jQuery(this).attr('checked')=='checked' ) {jQuery(this).prev().attr('disabled', true);}else{jQuery(this).prev().attr('disabled', false);}" /></td>
<td style="text-align:center;"><input type="hidden" name="delete[]" value="0" /><input type="checkbox" name="delete[]" value="1" onclick="if ( jQuery(this).attr('checked')==true || jQuery(this).attr('checked')=='checked' ) {jQuery(this).prev().attr('disabled', true);}else{jQuery(this).prev().attr('disabled', false);}" /></td>
</tr>
<tr><td colspan="4"><a href="javascript:void(0);" onclick="var source = jQuery('#instant_editor_base').clone(); jQuery(source).find('input[type=text],textarea').val('').attr('disabled',false); jQuery(source).find('input[type=checkbox]').attr('checked',false);jQuery(source).find('input[type=hidden]').attr('disabled',false);jQuery(source).find('img').hide(); jQuery(this).parent().parent().before(source);"><?php _e('Add', 'frontend-user-admin'); ?></a>
<tr><td colspan="4">
<p><input type="submit" value="<?php _e('Update User &raquo;', 'frontend-user-admin'); ?>" class="button-primary" /></p>
</td></tr>
</tbody>
</table>
<input type="hidden" name="action" value="instant_editor_update" />
<input type="hidden" name="user_id" id="user_id" value="<?php echo $profileuser->user_id; ?>" />
<input type="hidden" name="page" value="frontend-user-admin/frontend-user-admin.php" />
<input type="hidden" name="option" value="edituser" />
</form>
</div>
</div>

<div class="postbox closed">
<div class="handlediv" title="<?php _e('Click to toggle', 'frontend-user-admin'); ?>"><br /></div>
<h3><?php _e('Delete User', 'frontend-user-admin'); ?></h3>
<div class="inside">
<table class="form-table" style="margin-bottom:5px;">
<tbody>
<tr><td>
<?php
	if ( !empty($options['global_settings']['soft_user_deletion']) ) :
?>
<form method="post" action="" style="float:left; margin-right:10px;">
<input type="submit" value="<?php _e('Delete User (soft) &raquo;', 'frontend-user-admin'); ?>" class="button-primary" />
<input type="hidden" name="action" value="soft_user_deletion" />
<input type="hidden" name="user_id" id="user_id" value="<?php echo $profileuser->user_id; ?>" />
<input type="hidden" name="page" value="frontend-user-admin/frontend-user-admin.php" />
<input type="hidden" name="option" value="edituser" />
</form>
<?php
	endif;
?>
<form method="get" action="users.php">
<input type="submit" value="<?php _e('Delete User &raquo;', 'frontend-user-admin'); ?>" class="button-primary" />
<?php wp_nonce_field( 'bulk-users', '_wpnonce', false ); ?>
<input type="hidden" name="wp_http_referer" value="<?php echo get_option('siteurl'); ?>/wp-admin/admin.php?page=frontend-user-admin/frontend-user-admin.php" />
<?php
		if ( function_exists('is_multisite') && is_multisite() ) :
			if ( !empty($options['global_settings']['user_complete_deletion']) ) :
?>
<input type="hidden" name="action" value="delete" />
<?php
			else :
?>
<input type="hidden" name="action" value="remove" />
<?php 
			endif;
		else :
 ?>
<input type="hidden" name="action" value="delete" />
<?php endif; ?>
<input type="hidden" name="user" id="user" value="<?php echo $profileuser->user_id; ?>" />
</form>
</td></tr>
</tbody>
</table>
</div>
</div>
</div>

<script type="text/javascript">
// <![CDATA[
<?php if ( version_compare( substr($wp_version, 0, 3), '2.7', '<' ) ) { ?>
jQuery('.postbox h3').prepend('<a class="togbox">+</a> ');
<?php } ?>
jQuery('.postbox div.handlediv').click( function() { jQuery(jQuery(this).parent().get(0)).toggleClass('closed'); } );
jQuery('.postbox h3').click( function() { jQuery(jQuery(this).parent().get(0)).toggleClass('closed'); } );
jQuery('.postbox.close-me').each(function(){
jQuery(this).addClass("closed");
});
function thickbox(link) {
	var t = link.title || link.name || null;
	var a = link.href || link.alt;
	var g = link.rel || false;
	tb_show(t,a,g);
	link.blur();
	return false;
}
function submitStop(e){
    if (!e) var e = window.event;
    if(e.keyCode == 13) return false;
}
//-->
</script>

<?php
	}
	
	function umail_name2label() {
		$umail_name2label['umail_id']           = __('ID', 'frontend-user-admin');
		$umail_name2label['user_id']            = __('User ID', 'frontend-user-admin');
		$umail_name2label['umail_from']         = __('FROM', 'frontend-user-admin');
		$umail_name2label['umail_to']           = __('TO', 'frontend-user-admin');
		$umail_name2label['umail_cc']           = __('CC', 'frontend-user-admin');
		$umail_name2label['umail_bcc']          = __('BCC', 'frontend-user-admin');
		$umail_name2label['umail_template']     = __('Template Name', 'frontend-user-admin');
		$umail_name2label['umail_subject']      = __('Mail Subject', 'frontend-user-admin');
		$umail_name2label['umail_body']         = __('Mail Body', 'frontend-user-admin');
		$umail_name2label['umail_regtime']      = __('Mailed Datetime', 'frontend-user-admin');
		
		return $umail_name2label;
	}
	
	function frontend_user_admin_select_mail( $args ) {
		global $wpdb;

		if ( !isset($wpdb->base_prefix) ) $wpdb->base_prefix = $wpdb->prefix;
		
		extract( $args, EXTR_SKIP );

		if( empty($orderby) ) $orderby = 'umail_id';

		if( strtoupper($order) == "ASC" ) $order = "ASC";
		else $order = "DESC";
			
		$query = "SELECT * FROM `".$wpdb->prefix."usermail` LEFT JOIN `".$wpdb->base_prefix."users` ON `".$wpdb->prefix."usermail`.user_id=`".$wpdb->base_prefix."users`.ID";
		$query .= " WHERE 1=1 AND `".$wpdb->prefix."usermail`.umail_del=0";
		
		if( !empty($umail_id) ) $query .= $wpdb->prepare(" AND `".$wpdb->prefix."usermail`.umail_id = %d", $umail_id);
		if( !empty($user_id) ) $query .= $wpdb->prepare(" AND `".$wpdb->prefix."usermail`.user_id = %d", $user_id);
		
		if( !empty($q) && !empty($t) && array_key_exists($t, $this->umail_name2label()) ) :
			if ( $t == 'umail_id' || $t == 'user_id' )
				$query .= $wpdb->prepare(" AND `".$wpdb->prefix."usermail`.".$t." = %s", $q);
			else
				$query .= $wpdb->prepare(" AND `".$wpdb->prefix."usermail`.".$t." LIKE %s", '%'.$q.'%');
		endif;

		if ( array_key_exists($orderby, $this->umail_name2label()) )  :
			$query .= " ORDER BY `".$wpdb->prefix."usermail`.$orderby $order";
		endif;
		
		if( !is_numeric($posts_per_page) || $posts_per_page>100  ) $posts_per_page = 20;
		if( !is_numeric($paged) || $paged<1 ) $paged = 1;
		if( is_numeric($paged) && is_numeric($posts_per_page) ) $from = (int)($paged-1)*(int)$posts_per_page;
		else  $from = 0;
		$query .= $wpdb->prepare(" LIMIT %d, %d", $from, $posts_per_page);
		$umail_result = $wpdb->get_results($query, ARRAY_A);
		
		if( !empty($umail_result) ) :
			$count_umail_result = count($umail_result);
			for ( $i=0; $i<$count_umail_result; $i++ ) :
				$query2 =  $wpdb->prepare("SELECT * FROM `".$wpdb->base_prefix."usermeta` WHERE `".$wpdb->base_prefix."usermeta`.user_id=%d", $umail_result[$i]['user_id']);
				$usermeta_result = $wpdb->get_results($query2, ARRAY_A);
				if( !empty($usermeta_result) ) :
					$count_usermeta_result = count($usermeta_result);
					for ( $j=0; $j<$count_usermeta_result; $j++ ) :
						$umail_result[$i][$usermeta_result[$j]['meta_key']] = $usermeta_result[$j]['meta_value'];
					endfor;
				endif;
			endfor;
		endif;

		$query = preg_replace("/SELECT (.*?)FROM/","SELECT COUNT(`".$wpdb->prefix."usermail`.umail_id) FROM",$query);
		$query = preg_replace("/ ORDER.*/","",$query);
		$query = preg_replace("/ LIMIT.*/","",$query);
		$total = $wpdb->get_var($query);
		
		$supplement = array('paged' => (int)$paged, 'posts_per_page' => (int)$posts_per_page, 'found_posts' => $total, 'max_num_pages' => ceil($total/$posts_per_page));
		
		return array($umail_result, $supplement);
	}
	
	function frontend_user_admin_send_mail($args) {
		global $wpdb;
		
		extract($args, EXTR_SKIP);
		
		$mail_subject   = $subject;
		$mail_sender    = $from;
		$mail_body      = $body;
		$mail_recipient = $to;
		$mail_headers   = "FROM: " . $from . "\n";
		if($cc) $mail_headers .= "CC: " . $cc . "\n";
		if($bcc) $mail_headers .= "BCC: " . $bcc . "\n";
		$mail_headers .= "Content-Type: text/plain; charset=\"" . get_option('blog_charset') . "\"\n";
		@wp_mail($mail_recipient, $mail_subject, $mail_body, $mail_headers);
		
		$query = $wpdb->prepare("INSERT INTO `".$wpdb->prefix."usermail` (user_id, umail_from, umail_to, umail_cc, umail_bcc, umail_template, umail_subject, umail_body, umail_regtime) VALUES(%d,%s,%s,%s,%s,%s,%s,%s,NOW());", $user_id, $from, $to, $cc, $bcc, $template, $subject, $body);
		$wpdb->query($query);
	}
	
	function frontend_user_admin_delete_mail() {
		global $wpdb;

		$query = $wpdb->prepare("UPDATE `".$wpdb->prefix."usermail` SET umail_del='1' WHERE umail_id=%d;", $_POST['umail_id']);
		if ( !$wpdb->query($query) ) :
			$errors = new WP_Error();
			$errors->add('delete_mail', __('<strong>ERROR</strong>: Failed to delete the mail.', 'frontend-user-admin'));
			return $errors;
		endif;
	}
	
	function frontend_user_admin_mail() {
		$options = get_option('frontend_user_admin');

		$option = isset($_GET['option']) ? $_GET['option'] : '';

		if ( !empty($_REQUEST['umail_id']) && $option = 'readmail' )
			list($data, $data_supplement) = $this->frontend_user_admin_select_mail(array(
										'umail_id' => $_REQUEST['umail_id']
										));
		
		$_REQUEST['order'] = isset($_REQUEST['order']) ? $_REQUEST['order'] : '';
		$_REQUEST['orderby'] = isset($_REQUEST['orderby']) ? $_REQUEST['orderby'] : '';
		$_REQUEST['order_id'] = isset($_REQUEST['order_id']) ? $_REQUEST['order_id'] : '';
		$_REQUEST['user_id'] = isset($_REQUEST['user_id']) ? $_REQUEST['user_id'] : '';
		$_REQUEST['umail_id'] = isset($_REQUEST['umail_id']) ? $_REQUEST['umail_id'] : '';
		$_REQUEST['q'] = isset($_REQUEST['q']) ? $_REQUEST['q'] : '';
		$_REQUEST['t'] = isset($_REQUEST['t']) ? $_REQUEST['t'] : '';
		$_REQUEST['m'] = isset($_REQUEST['m']) ? $_REQUEST['m'] : '';
		$_REQUEST['paged'] = isset($_REQUEST['paged']) ? $_REQUEST['paged'] : '';
		$_REQUEST['check'] = isset($_REQUEST['check']) ? $_REQUEST['check'] : '';
		$_REQUEST['posts_per_page'] = isset($_REQUEST['posts_per_page']) ? $_REQUEST['posts_per_page'] : '';
				
		list($result, $supplement) = $this->frontend_user_admin_select_mail( array(
										'order' => $_REQUEST['order'],
										'q' => $_REQUEST['q'],
										't' => $_REQUEST['t'],
										'm' => $_REQUEST['m'],
										'umail_id' => $_REQUEST['umail_id'],
										'user_id' => $_REQUEST['user_id'],
										'posts_per_page' => $_REQUEST['posts_per_page'],
										'paged' => $_REQUEST['paged'] ));
		$count_result = count($result);
		
		$str = '';
		if ( !empty($_REQUEST['user_id']) ) $str .= '&user_id='.esc_attr($_REQUEST['user_id']);
?>
<style type="text/css">
table.tablesorter thead
tr .header					{ background:#DFDFDF url(../<?php echo PLUGINDIR; ?>/frontend-user-admin/images/bg.gif) no-repeat top right; cursor: pointer; padding-right: 20px; }
table.tablesorter thead
tr .headerSortUp 			{ background:#DFDFDF url(../<?php echo PLUGINDIR; ?>/frontend-user-admin/images/asc.gif) no-repeat top right; cursor: pointer; padding-right: 20px; }
table.tablesorter thead
tr .headerSortDown			{ background:#DFDFDF url(../<?php echo PLUGINDIR; ?>/frontend-user-admin/images/desc.gif) no-repeat top right; cursor: pointer; padding-right: 20px; }
table.tablesorter thead tr th	{ white-space:nowrap; background:#DFDFDF url(../<?php echo PLUGINDIR; ?>/frontend-user-admin/images/th.gif) no-repeat top right; }
table.tablesorter thead tr th.check-column { padding:0; vertical-align:middle; }
.notfound					{ text-align:center; }
</style>

<ul class="subsubsub">
<li><a href="?page=frontend-user-admin/frontend-user-admin-mail.php"><?php _e('User Mail', 'frontend-user-admin'); ?></a> |</li>
<li><a href="?page=frontend-user-admin/frontend-user-admin-mail.php&option=sendmail"><?php _e('Send Mail', 'frontend-user-admin'); ?></a></li>
</ul>

<div class="clear"></div>

<?php
		if( $option == 'sendmail' ) :
			if ( empty($_POST['send_mail_submit']) ) :
				$_POST['user_id'] = $_REQUEST['user_id'];
				$_POST['from'] = $options['mail_options']['mail_from'];
				
				if ( !empty($_REQUEST['user_id']) ) :
					$user = get_user_to_edit($_REQUEST['user_id']);
					$user->login_url = $options['global_settings']['login_url'];
					$user->signature_template = isset($options['mail_options']['signature_template']) ? $options['mail_options']['signature_template'] : 77;
					$_POST['to'] = $user->user_email;
				
					$user_meta = get_user_meta($user->ID);
					if ( is_array($user_meta) ) :
						if( !empty($options['global_settings']['array_delimiter']) ) $delimiter = $options['global_settings']['array_delimiter'];
						else $delimiter = ' ';
						foreach( $user_meta as $meta_key => $meta_val ) :
							$array_val = maybe_unserialize(maybe_unserialize($meta_val[0]));
							if ( is_array($array_val) ) :
								$user->{$meta_key} = $this->implode_recursive($delimiter, $array_val);
							endif;
						endforeach;
					endif;
				endif;

				if ( isset($_REQUEST['template']) ) :
					for($i=0;$i<count($options['mail_template']);$i++) :
						if ( $_REQUEST['template'] == $options['mail_template'][$i]['name'] ) :
							$options['mail_template'][$i]['subject'] = preg_replace_callback('/%([a-zA-Z0-9_]+)%/', function ($m) use ($user) { return $user->{$m[1]}; }, $options['mail_template'][$i]['subject']);
							$options['mail_template'][$i]['body'] = preg_replace_callback('/%([a-zA-Z0-9_]+)%/', function ($m) use ($user) { return $user->{$m[1]}; }, $options['mail_template'][$i]['body']);
							$_POST['subject']  = $options['mail_template'][$i]['subject'];
							$_POST['body']     = $options['mail_template'][$i]['body'];
							break;
						endif;
					endfor;
				endif;
			endif;
?>
<style type="text/css">
.form-table textarea { width:100%; }
.widefat td { vertical-align:middle; }
</style>

<div id="poststuff" style="position: relative; margin-top:10px;">
<div class="postbox">
<h3><?php _e('Send Mail', 'frontend-user-admin'); ?></h3>
<div class="inside">
<form action="" method="post">
<table class="form-table" style="margin-bottom:5px;">
<tbody>
<?php if ( !empty($_POST['user_id']) ) : ?>
<tr>
<th><?php _e('User ID', 'frontend-user-admin'); ?></th>
<td><?php echo $_POST['user_id']; ?><input type="hidden" name="user_id" value="<?php echo esc_attr($_POST['user_id']); ?>" /></td>
</tr>
<?php endif; ?>
<tr>
<th><?php _e('FROM', 'frontend-user-admin'); ?></th>
<td><input type="text" name="from" value="<?php echo isset($_POST['from']) ? esc_attr($_POST['from']) : ''; ?>" class="regular-text" /></td>
</tr>
<tr>
<th><?php _e('TO', 'frontend-user-admin'); ?></th>
<td><input type="text" name="to" value="<?php echo isset($_POST['to']) ? esc_attr($_POST['to']) : ''; ?>" class="regular-text" /></td>
</tr>
<tr>
<th><?php _e('CC', 'frontend-user-admin'); ?></th>
<td><input type="text" name="cc" value="<?php echo isset($_POST['cc']) ? esc_attr($_POST['cc']) : ''; ?>" class="regular-text" /></td>
</tr>
<tr>
<th><?php _e('BCC', 'frontend-user-admin'); ?></th>
<td><input type="text" name="bcc" value="<?php echo isset($_POST['bcc']) ? esc_attr($_POST['bcc']) : ''; ?>" class="regular-text" /></td>
</tr>
<tr>
<th><?php _e('Mail Template Name', 'frontend-user-admin'); ?></th>
<td>
<select name="template" id="template">
<option value=""></option>
<?php
	$count = isset($options['mail_template']) ? count($options['mail_template']) : 0;
	for($i=0;$i<$count;$i++) {
?>
<option value="<?php echo $options['mail_template'][$i]['name']; ?>"<?php if (($_GET['template'] == $options['mail_template'][$i]['name']) || ( !$_GET['template'] && $options['mail_default_template']==$i)) echo ' selected="selected"'; ?>><?php echo $options['mail_template'][$i]['name']; ?></option>
<?php		
	}
?>
</select>
<input type="button" value="<?php _e('Load &raquo;', 'frontend-user-admin'); ?>" onclick="location.href='?page=frontend-user-admin/frontend-user-admin-mail.php&option=sendmail<?php echo $str; ?>&template='+jQuery('#template').val();" /></td>
</tr>
<tr>
<th><?php _e('Mail Subject', 'frontend-user-admin'); ?></th>
<td><input type="text" name="subject" value="<?php echo isset($_POST['subject']) ? esc_attr($_POST['subject']) : ''; ?>" class="regular-text" /></td>
</td>
<tr>
<th><?php _e('Mail Body', 'frontend-user-admin'); ?></th>
<td><textarea name="body" rows="10" cols="60"><?php echo isset($_POST['body']) ? htmlspecialchars($_POST['body']) : ''; ?></textarea></td>
</tr>
<tr>
<th colspan="2">
<input type="submit" name="send_mail_submit" value="<?php _e('Send &raquo;', 'frontend-user-admin'); ?>" class="button-primary" />
</th>
</tr>
</tbody>
</table>
</form>
</div>
</div>
</div>
<?php
		elseif( $option == 'readmail' ) :
?>
<div id="poststuff" style="position: relative; margin-top:10px;">
<div class="postbox">
<h3><?php _e('Read Mail', 'frontend-user-admin'); ?></h3>
<div class="inside">
<table class="form-table" style="margin-bottom:5px;">
<tbody>
<tr>
<th><?php _e('ID', 'frontend-user-admin'); ?></th>
<td><?php echo $data[0]['umail_id']; ?></td>
</tr>
<tr>
<th><?php _e('User ID', 'frontend-user-admin'); ?></th>
<td><?php if ( !empty($data[0]['user_id']) ) : ?><?php echo $data[0]['user_id']; ?> <?php echo $data[0]['last_name']; ?><?php echo $data[0]['first_name']; ?><?php endif; ?></td>
</tr>
<tr>
<th><?php _e('FROM', 'frontend-user-admin'); ?></th>
<td><?php echo esc_html($data[0]['umail_from']); ?></td>
</tr>
<tr>
<th><?php _e('TO', 'frontend-user-admin'); ?></th>
<td><?php echo esc_html($data[0]['umail_to']); ?></td>
</tr>
<tr>
<th><?php _e('CC', 'frontend-user-admin'); ?></th>
<td><?php echo esc_html($data[0]['umail_cc']); ?></td>
</tr>
<tr>
<th><?php _e('BCC', 'frontend-user-admin'); ?></th>
<td><?php echo esc_html($data[0]['umail_bcc']); ?></td>
</tr>
<tr>
<th><?php _e('Mail Template Name', 'frontend-user-admin'); ?></th>
<td><?php echo $data[0]['umail_template']; ?></td>
</tr>
<tr>
<th><?php _e('Mail Subject', 'frontend-user-admin'); ?></th>
<td><?php echo $data[0]['umail_subject']; ?></td>
</tr>
<tr>
<th><?php _e('Mail Body', 'frontend-user-admin'); ?></th>
<td><?php echo nl2br($data[0]['umail_body']); ?></td>
</tr>
<tr>
<th><?php _e('Datetime', 'frontend-user-admin'); ?></th>
<td><?php echo $data[0]['umail_regtime']; ?></td>
</tr>
</tbody>
</table>
</div>
</div>
</div>
<?php
		endif;
?>

<form action="" method="get" id="select_data">
<div class="tablenav" style="height:auto;">
<?php
	$page_links = paginate_links( array(
		'base' => add_query_arg( 'paged', '%#%' ),
		'format' => '',
		'prev_text' => __('&laquo;'),
		'next_text' => __('&raquo;'),
		'total' => $supplement['max_num_pages'],
		'current' => $supplement['paged']
	) );
?>
<?php if ( $supplement['found_posts']>0 ) { ?>
<div class="tablenav-pages"><?php $page_links_text = sprintf( '<span class="displaying-num">' . __( 'Displaying %s&#8211;%s of %s' ) . '</span>%s',
	number_format_i18n( ( $supplement['paged'] - 1 ) * $supplement['posts_per_page'] + 1 ),
	number_format_i18n( min( $supplement['paged'] * $supplement['posts_per_page'], $supplement['found_posts'] ) ),
	number_format_i18n( $supplement['found_posts'] ),
	$page_links
); echo $page_links_text; ?></div>
<?php } ?>

<div class="alignleft">
<?php _e('Search', 'frontend-user-admin'); ?> 
<input type="text" name="q" value="<?php echo esc_attr($_REQUEST['q']); ?>" /> 

<select name="t" style="padding:3px; margin:2px;" class="middle">
<?php			
				foreach ( $this->umail_name2label() as $key => $val ) :
					if ( $_REQUEST['t'] == $key ) :
?>
<option value="<?php echo $key; ?>" selected="selected"><?php echo $val; ?></option>
<?php
					else :
?>
<option value="<?php echo $key; ?>"><?php echo $val; ?></option>
<?php
					endif;
				endforeach;
?>
</select> 

<select name="posts_per_page" style="padding:3px; margin:2px;">
<?php
				for($i=10;$i<110;$i+=10) :
					if( $supplement['posts_per_page'] == $i ) :
?>
<option vale="<?php echo $i; ?>" selected="selected"><?php echo $i; ?></option>
<?php
					else :
?>
<option vale="<?php echo $i; ?>"><?php echo $i; ?></option>
<?php
					endif;
				endfor;
?>
</select> 
<select name="m" style="vertical-align:middle;">
<option value="p" <?php selected('p', $_REQUEST['m']); ?>><?php _e('Match Partial', 'frontend-user-admin'); ?></option>
<option value="f" <?php selected('f', $_REQUEST['m']); ?>><?php _e('Match Full', 'frontend-user-admin'); ?></option>
</select> 
<input type="submit" class="button-secondary" value="<?php _e('Change and Display &raquo;', 'frontend-user-admin'); ?>" />
<input type="hidden" name="paged" value="<?php echo $supplement['paged'] ?>" />
<input type="hidden" name="page" value="frontend-user-admin/frontend-user-admin-mail.php" />
</div>

<br class="clear" />
</div>

<div class="clear"></div>

<table class="tablesorter widefat" style="margin:10px 0 5px 0;" cellspacing="0">
<thead>
<tr>
<th id="umail_id" class="<?php if( $_REQUEST['orderby'] == 'umail_id' && $_REQUEST['order'] == 'asc' ) echo 'headerSortDown'; elseif ( $_REQUEST['orderby'] == 'umail_id' && $_REQUEST['order'] == 'desc') echo 'headerSortUp'; else echo 'header'; ?>" style="width:50px;">ID</th>
<th id="user_id" class="<?php if( $_REQUEST['orderby'] == 'user_id' && $_REQUEST['order'] == 'asc' ) echo 'headerSortDown'; elseif ( $_REQUEST['orderby'] == 'user_id' && $_REQUEST['order'] == 'desc') echo 'headerSortUp'; else echo 'header'; ?>"><?php _e('User', 'frontend-user-admin'); ?></th>
<th id="umail_to" class="<?php if( $_REQUEST['orderby'] == 'umail_to' && $_REQUEST['order'] == 'asc' ) echo 'headerSortDown'; elseif ( $_REQUEST['orderby'] == 'umail_to' && $_REQUEST['order'] == 'desc') echo 'headerSortUp'; else echo 'header'; ?>"><?php _e('TO', 'frontend-user-admin'); ?></th>
<th id="umail_template" class="<?php if( $_REQUEST['orderby'] == 'umail_template' && $_REQUEST['order'] == 'asc' ) echo 'headerSortDown'; elseif ( $_REQUEST['orderby'] == 'umail_template' && $_REQUEST['order'] == 'desc') echo 'headerSortUp'; else echo 'header'; ?>"><?php _e('Template Name', 'frontend-user-admin'); ?></th>
<th id="umail_subject" class="<?php if( $_REQUEST['orderby'] == 'umail_subject' && $_REQUEST['order'] == 'asc' ) echo 'headerSortDown'; elseif ( $_REQUEST['orderby'] == 'umail_subject' && $_REQUEST['order'] == 'desc') echo 'headerSortUp'; else echo 'header'; ?>"><?php _e('Subject', 'frontend-user-admin'); ?></th>
<th id="umail_regtime" class="<?php if( $_REQUEST['orderby'] == 'umail_regtime' && $_REQUEST['order'] == 'asc' ) echo 'headerSortDown'; elseif ( $_REQUEST['orderby'] == 'umail_regtime' && $_REQUEST['order'] == 'desc') echo 'headerSortUp'; else echo 'header'; ?>"><?php _e('Datetime', 'frontend-user-admin'); ?></th>
<th class="center"><?php _e('Detail', 'frontend-user-admin'); ?></th>
</tr>
</thead>
<tbody>
<?php
				if( $count_result ) :
					for ( $i=0; $i<$count_result; $i++) :
?>
<tr>
<td><?php echo $result[$i]['umail_id']; ?></td>
<td><?php if ( !empty($result[$i]['user_id']) ) : ?><a href="?page=frontend-user-admin/frontend-user-admin.php&user_id=<?php echo $result[$i]['user_id']; ?>"><?php echo $result[$i]['user_id']; ?> <?php echo $result[$i][$options['global_settings']['log_username']]; ?></a><?php endif; ?></td>
<td><?php echo $result[$i]['umail_to']; ?></td>
<td><?php echo $result[$i]['umail_template']; ?></td>
<td><?php echo $result[$i]['umail_subject']; ?></td>
<td><?php echo $result[$i]['umail_regtime']; ?></td>
<td class="center"><input type="button" class="button" value="<?php _e('Detail', 'frontend-user-admin'); ?>" onclick="location.href='?page=frontend-user-admin/frontend-user-admin-mail.php&option=readmail&umail_id=<?php echo $result[$i]['umail_id']; ?><?php echo $str; ?>';" /></td>
</tr>
<?php
					endfor;
				else :
?>
<tr>
<td colspan="7" class="notfound"><?php _e('Not Found', 'frontend-user-admin'); ?></td>
</tr>
<?php
				endif;
?>
</tbody>
</table>
</form>

<div class="tablenav">
<?php if ( $supplement['found_posts']>0 ) { ?>
<div class="tablenav-pages"><?php $page_links_text = sprintf( '<span class="displaying-num">' . __( 'Displaying %s&#8211;%s of %s' ) . '</span>%s',
	number_format_i18n( ( $supplement['paged'] - 1 ) * $supplement['posts_per_page'] + 1 ),
	number_format_i18n( min( $supplement['paged'] * $supplement['posts_per_page'], $supplement['found_posts'] ) ),
	number_format_i18n( $supplement['found_posts'] ),
	$page_links
); echo $page_links_text; ?></div>
<?php } ?>
</div>

<script type="text/javascript">
//<![CDATA[
	jQuery(document).ready(function () {
		jQuery('.tablesorter thead tr th.headerSortUp').bind('click', 
			function(e) {location.href='?orderby='+jQuery(this).attr('id')+'&order=asc&'+jQuery("#select_data").formSerialize();});
		jQuery('.tablesorter thead tr th.headerSortDown').bind('click', 
			function(e) {location.href='?orderby='+jQuery(this).attr('id')+'&order=desc&'+jQuery("#select_data").formSerialize();});
		jQuery('.tablesorter thead tr th.header').bind('click', 
			function(e) {location.href='?orderby='+jQuery(this).attr('id')+'&order=asc&'+jQuery("#select_data").formSerialize();});
		jQuery(".tablesorter tbody tr:odd").addClass("odd"); 
		jQuery(".tablesorter tbody tr").hover(
			function() {jQuery(this).addClass("hover");},
			function() {jQuery(this).removeClass("hover");}
		);  	
	});
//]]>
</script>
</div>
<?php	
	}
	
	function frontend_user_admin_sendmail() {
		
	}
	
	function frontend_user_admin_select_log( $args ) {
		global $wpdb;
		$options = get_option('frontend_user_admin');

		if ( !isset($wpdb->base_prefix) ) $wpdb->base_prefix = $wpdb->prefix;

		extract( $args, EXTR_SKIP );

		if( $order ) :
			list($order, $by) = explode(".", $order);
			if( $by == "desc" ) $by = "DESC";
			else $by = "ASC";
		else :
			$order = 'ulog_id';
			$by = 'DESC';
		endif;

		$query = "SELECT `".$wpdb->prefix."userlog`.ulog_id, `".$wpdb->prefix."userlog`.user_id, INET_NTOA(`".$wpdb->prefix."userlog`.ip) AS ip, `".$wpdb->prefix."userlog`.log, `".$wpdb->prefix."userlog`.ulog_time, `".$wpdb->base_prefix."users`.*";

		if ( !empty($options['global_settings']['log_username']) && $options['global_settings']['log_username'] != 'ID' && $options['global_settings']['log_username'] != 'user_login' &&  $options['global_settings']['log_username'] != 'user_pass' &&  $options['global_settings']['log_username'] != 'user_nicename' &&  $options['global_settings']['log_username'] != 'user_email' &&  $options['global_settings']['log_username'] != 'user_url' && $options['global_settings']['log_username'] != 'display_name' ) 
			$query .= ", `".$wpdb->base_prefix."usermeta`.meta_value AS " . $options['global_settings']['log_username'];

		$query .= " FROM `".$wpdb->prefix."userlog` LEFT JOIN `".$wpdb->base_prefix."users` ON `".$wpdb->base_prefix."users`.ID=`".$wpdb->prefix."userlog`.user_id ";

		if( !empty($q) && !empty($t) ) :
			if ( !in_array($t, array('user_id', 'ip', 'log', 'ulog_time')) ) :
				$query .= " LEFT JOIN `".$wpdb->base_prefix."usermeta` ON `".$wpdb->base_prefix."users`.ID=`".$wpdb->base_prefix."usermeta`.user_id ";
			endif;
		endif;	

		if ( !empty($options['global_settings']['log_username']) && $options['global_settings']['log_username'] != 'ID' && $options['global_settings']['log_username'] != 'user_login' &&  $options['global_settings']['log_username'] != 'user_pass' &&  $options['global_settings']['log_username'] != 'user_nicename' &&  $options['global_settings']['log_username'] != 'user_email' &&  $options['global_settings']['log_username'] != 'user_url' && $options['global_settings']['log_username'] != 'display_name' ) 
			$query .= " LEFT JOIN `".$wpdb->base_prefix."usermeta` ON `".$wpdb->base_prefix."users`.ID=`".$wpdb->base_prefix."usermeta`.user_id AND `".$wpdb->base_prefix."usermeta`.meta_key='" . $options['global_settings']['log_username'] . "' ";

		$query .= " WHERE 1=1";

		if( !empty($user_id) ) $query .= " AND `".$wpdb->prefix."userlog`.user_id = '" . $user_id . "'";
				
		if( !empty($q) && !empty($t) ) :
			if ( in_array($t, array('user_id', 'ip', 'log', 'ulog_time')) ) :
				switch( $t ) :
					case 'ulog_id':
						$query .= " AND `".$wpdb->prefix."userlog`.".$t." = '".$q."'";
						break;
					case 'ip':
						$query .= " AND `".$wpdb->prefix."userlog`.".$t." = INET_ATON('".$q."')";
						break;
					default:
						if ( !empty($m) && $m == 'f' ) :
							$query .= $wpdb->prepare(" AND `".$wpdb->prefix."userlog`.".$t." = %s", $q);
						else :
							$query .= $wpdb->prepare(" AND `".$wpdb->prefix."userlog`.".$t." LIKE %s", '%'.$q.'%');
						endif;
						break;
				endswitch;
			else :
				if ( !empty($m) && $m == 'f' ) :
					$query .= $wpdb->prepare(" AND `".$wpdb->base_prefix."usermeta`.meta_key='".$t."' AND `".$wpdb->base_prefix."usermeta`.meta_value = %s", $q);
				else :
					$query .= $wpdb->prepare(" AND `".$wpdb->base_prefix."usermeta`.meta_key='".$t."' AND `".$wpdb->base_prefix."usermeta`.meta_value LIKE %s", '%'.$q.'%');
				endif;
			endif;
		endif;

		if ( !empty($from_date) ) $query .= " AND `".$wpdb->prefix."userlog`.ulog_time >= '" . $from_date . "'";
		if ( !empty($to_date) ) $query .= " AND `".$wpdb->prefix."userlog`.ulog_time <= '" . $to_date . "'";		
		if ( !empty($user_id) ) $query .= " AND `".$wpdb->prefix."userlog`.user_id = " . $user_id;		

		if( !empty($order) ):
			$query .= " ORDER BY `".$wpdb->prefix."userlog`.$order $by";
		endif;

		if( !isset($posts_per_page) || !is_numeric($posts_per_page) || $posts_per_page>100 ) $posts_per_page = 20;
		if( !isset($paged) || !is_numeric($paged) || $paged<1 ) $paged = 1;
		if( is_numeric($paged) && is_numeric($posts_per_page) ) $from = (int)($paged-1)*(int)$posts_per_page;
		else  $from = 0;
		if ( (!isset($action) || (isset($action) && $action != 'export_user_log_data')) && empty($export) ) 
			$query .= " LIMIT $from, ".$posts_per_page;
		$result = $wpdb->get_results($query, ARRAY_A);
				
		$query = preg_replace("/SELECT (.*?)FROM/","SELECT COUNT(`".$wpdb->prefix."userlog`.ulog_id) FROM",$query);
		$query = preg_replace("/ ORDER.*/","",$query);
		$query = preg_replace("/ LIMIT.*/","",$query);
		$total = $wpdb->get_var($query);
		
		$supplement = array('paged' => (int)$paged, 'posts_per_page' => (int)$posts_per_page, 'found_posts' => $total, 'max_num_pages' => ceil($total/$posts_per_page));
		
		return array($result, $supplement);
	}
	
	function frontend_user_admin_add_log($user_id, $ip, $log) {
		global $wpdb;
		
		$query = $wpdb->prepare("INSERT INTO `".$wpdb->prefix."userlog` (user_id, ip, log) VALUES(%d, INET_ATON(%s), %s);",$user_id, $ip, $log);
		$wpdb->query($query);
	}
	
	function frontend_user_admin_select_logsummary( $args ) {
		global $wpdb;
		$options = get_option('frontend_user_admin');

		if ( !isset($wpdb->base_prefix) ) $wpdb->base_prefix = $wpdb->prefix;

		extract( $args, EXTR_SKIP );

		if( $order ) :
			list($order, $by) = explode(".", $order);
			if( $by == "desc" ) $by = "DESC";
			else $by = "ASC";
		else :
			$order = 'ulog_id';
			$by = 'DESC';
		endif;
			
		$query = "SELECT `".$wpdb->prefix."userlog`.ulog_id, `".$wpdb->prefix."userlog`.user_id, `".$wpdb->base_prefix."users`.*, COUNT(`".$wpdb->prefix."userlog`.user_id) AS ct_id, unique_user2.ct_uq_id ";
		
		if ( !empty($options['global_settings']['log_username']) && $options['global_settings']['log_username'] != 'ID' && $options['global_settings']['log_username'] != 'user_login' &&  $options['global_settings']['log_username'] != 'user_pass' &&  $options['global_settings']['log_username'] != 'user_nicename' &&  $options['global_settings']['log_username'] != 'user_email' &&  $options['global_settings']['log_username'] != 'user_url' && $options['global_settings']['log_username'] != 'display_name' ) 
			$query .= ", `".$wpdb->base_prefix."usermeta`.meta_value AS " . $options['global_settings']['log_username'];

		$query .= " FROM (SELECT unique_user.user_id,COUNT(unique_user.user_id) AS ct_uq_id FROM (SELECT distinct user_id, DATE_FORMAT(ulog_time,'%Y-%m-%d') AS ulog_time_unique FROM `".$wpdb->prefix."userlog` WHERE 1 ";

		if ( $from_date ) $query .= " AND `".$wpdb->prefix."userlog`.ulog_time >= '" . $from_date . "'";
		if ( $to_date ) $query .= " AND `".$wpdb->prefix."userlog`.ulog_time <= '" . $to_date . "'";		

		$query .= " GROUP BY user_id, ulog_time_unique) AS unique_user WHERE 1 GROUP BY unique_user.user_id) AS unique_user2";

		$query .= ", `".$wpdb->prefix."userlog` LEFT JOIN `".$wpdb->base_prefix."users` ON `".$wpdb->base_prefix."users`.ID=`".$wpdb->prefix."userlog`.user_id ";

		if ( !empty($options['global_settings']['log_username']) && $options['global_settings']['log_username'] != 'ID' && $options['global_settings']['log_username'] != 'user_login' &&  $options['global_settings']['log_username'] != 'user_pass' &&  $options['global_settings']['log_username'] != 'user_nicename' &&  $options['global_settings']['log_username'] != 'user_email' &&  $options['global_settings']['log_username'] != 'user_url' && $options['global_settings']['log_username'] != 'display_name' ) 
			$query .= " LEFT JOIN `".$wpdb->base_prefix."usermeta` ON `".$wpdb->base_prefix."users`.ID=`".$wpdb->base_prefix."usermeta`.user_id AND `".$wpdb->base_prefix."usermeta`.meta_key='" . $options['global_settings']['log_username'] . "' ";
					
		$query .= " WHERE 1=1 AND `".$wpdb->prefix."userlog`.user_id=unique_user2.user_id";
		
		if ( $from_date ) $query .= " AND `".$wpdb->prefix."userlog`.ulog_time >= '" . $from_date . "'";
		if ( $to_date ) $query .= " AND `".$wpdb->prefix."userlog`.ulog_time <= '" . $to_date . "'";		
		
		$query .= " GROUP BY `".$wpdb->prefix."userlog`.user_id";
		
		if( !empty($order) ):
			$query .= " ORDER BY `".$wpdb->prefix."userlog`.$order $by";
		endif;

		$result = $wpdb->get_results($query, ARRAY_A);
		
		if( !empty($result) ) :
			$count_result = count($result);
			for ( $i=0; $i<$count_result; $i++ ) :
				$query2 =  $wpdb->prepare("SELECT * FROM `".$wpdb->base_prefix."usermeta` WHERE `".$wpdb->base_prefix."usermeta`.user_id=%d", $result[$i]['user_id']);
				$usermeta_result = $wpdb->get_results($query2, ARRAY_A);
				if( !empty($usermeta_result) ) :
					$count_usermeta_result = count($usermeta_result);
					for ( $j=0; $j<$count_usermeta_result; $j++ ) :
						$result[$i][$usermeta_result[$j]['meta_key']] = $usermeta_result[$j]['meta_value'];
					endfor;
				endif;
			endfor;

			if ( !empty($condition) && $condition != 'user_id' ) :
				for ( $i=0; $i<$count_result; $i++ ) :
					$new_result[$result[$i][$condition]]['ct_user']++;
					$new_result[$result[$i][$condition]]['ct_id'] += $result[$i]['ct_id'];
					$new_result[$result[$i][$condition]]['ct_uq_id'] += $result[$i]['ct_uq_id'];
				endfor;
				unset($result);
				$i = 0;
				foreach ( $new_result as $key => $val ) :
					$result[$i] = $val;
					$result[$i]['condition'] = $key;
					$i++;
				endforeach;
			endif;
		endif;

		return $result;
	}

	function frontend_user_admin_log() {
		global $wp_version;
		
		if ( !empty($_REQUEST['from_date_year']) && !empty($_REQUEST['from_date_month']) && $_REQUEST['from_date_day'] ) :
			$from_date_year  = (int)$_REQUEST['from_date_year'];
			$from_date_month = (int)$_REQUEST['from_date_month'];
			$from_date_day   = (int)$_REQUEST['from_date_day'];
			$from_date = sprintf('%04d',$from_date_year).'-'.sprintf('%02d',$from_date_month).'-'.sprintf('%02d',$from_date_day).' 00:00:00';
		else :
			$from_date_year = $from_date_month = $from_date_day = $from_date = '';
		endif;
		
		if ( !empty($_REQUEST['to_date_year']) && !empty($_REQUEST['to_date_month']) && $_REQUEST['to_date_day'] ) :
			$to_date_year  = (int)$_REQUEST['to_date_year'];
			$to_date_month = (int)$_REQUEST['to_date_month'];
			$to_date_day   = (int)$_REQUEST['to_date_day'];
			$to_date = sprintf('%04d',$to_date_year).'-'.sprintf('%02d',$to_date_month).'-'.sprintf('%02d',$to_date_day).' 23:59:59';
		else :
			$to_date_year = $to_date_month = $to_date_day = $to_date = '';
		endif;
		
		if ( empty($_REQUEST['order']) ) $_REQUEST['order'] = '';
		if ( empty($_REQUEST['q']) ) $_REQUEST['q'] = '';
		if ( empty($_REQUEST['t']) ) $_REQUEST['t'] = '';
		if ( empty($_REQUEST['m']) ) $_REQUEST['m'] = '';
		if ( !isset( $_REQUEST['paged'] ) || !empty($_REQUEST['search_button']) ) $_REQUEST['paged'] = 1;
		if ( empty($_REQUEST['posts_per_page']) ) $_REQUEST['posts_per_page'] = '';
		if ( !empty($_REQUEST['user_id']) ) $user_id = (int)$_REQUEST['user_id'];
		else $user_id = '';
				
		list($result, $supplement) = $this->frontend_user_admin_select_log( array(
										'order' => $_REQUEST['order'],
										'q' => $_REQUEST['q'],
										't' => $_REQUEST['t'],
										'm' => $_REQUEST['m'],
										'from_date' => $from_date,
										'to_date' => $to_date,
										'user_id' => $user_id,
										'posts_per_page' => $_REQUEST['posts_per_page'],
										'paged' => $_REQUEST['paged'] ));
		$count_result = count($result);

		$options = get_option('frontend_user_admin');

		if( !empty($options) ) :
?>
<style type="text/css">
table.tablesorter thead
tr .header					{ background:#DFDFDF url(../<?php echo PLUGINDIR; ?>/frontend-user-admin/images/bg.gif) no-repeat top right; cursor: pointer; padding-right: 20px; }
table.tablesorter thead
tr .headerSortUp 			{ background:#DFDFDF url(../<?php echo PLUGINDIR; ?>/frontend-user-admin/images/asc.gif) no-repeat top right; cursor: pointer; padding-right: 20px; }
table.tablesorter thead
tr .headerSortDown			{ background:#DFDFDF url(../<?php echo PLUGINDIR; ?>/frontend-user-admin/images/desc.gif) no-repeat top right; cursor: pointer; padding-right: 20px; }
table.tablesorter thead tr th	{ white-space:nowrap; background:#DFDFDF url(../<?php echo PLUGINDIR; ?>/frontend-user-admin/images/th.gif) no-repeat top right; }
table.tablesorter thead tr th.check-column { padding:0; vertical-align:middle; }
.notfound					{ text-align:center; }
.widefat td 				{ vertical-align:middle; }
.hover						{ background-color:#F3FFF0; }
</style>

<ul class="subsubsub">
<li><a href="?page=frontend-user-admin/frontend-user-admin-log.php"><?php _e('User Log', 'frontend-user-admin'); ?></a> |</li>
<li><a href="?page=frontend-user-admin/frontend-user-admin-log.php&option=logsummary"><?php _e('User Log Summary', 'frontend-user-admin'); ?></a></li>
</ul>

<div class="clear"></div>

<form action="" method="get" id="select_data">
<div class="tablenav" style="height:auto;">
<?php
	$_SERVER['REQUEST_URI'] = remove_query_arg( 'search_button', $_SERVER['REQUEST_URI']);
	$page_links = paginate_links( array(
		'base' => add_query_arg( 'paged', '%#%' ),
		'format' => '',
		'prev_text' => __('&laquo;'),
		'next_text' => __('&raquo;'),
		'total' => $supplement['max_num_pages'],
		'current' => $supplement['paged']
	) );
?>
<?php if ( $supplement['found_posts']>0 ) { ?>
<div class="tablenav-pages"><?php $page_links_text = sprintf( '<span class="displaying-num">' . __( 'Displaying %s&#8211;%s of %s' ) . '</span>%s',
	number_format_i18n( ( $supplement['paged'] - 1 ) * $supplement['posts_per_page'] + 1 ),
	number_format_i18n( min( $supplement['paged'] * $supplement['posts_per_page'], $supplement['found_posts'] ) ),
	number_format_i18n( $supplement['found_posts'] ),
	$page_links
); echo $page_links_text; ?></div>
<?php } ?>

<div class="alignleft">

<?php _e('From Date:', 'frontend-user-admin'); ?>
<select name="from_date_year" id="from_date_year">
<option value=""></option>
<?php
			for($j=2008;$j<(date('Y')+10);$j++) :
?><option value="<?php echo $j; ?>" <?php selected($from_date_year, $j); ?>><?php echo $j; ?></option><?php
			endfor;
?></select> - 
<select name="from_date_month" id="from_date_month"><option value=""></option>
<?php
			for($j=1;$j<13;$j++) :
?><option value="<?php echo sprintf('%02d',$j); ?>" <?php selected((int)$from_date_month, $j); ?>><?php echo $j; ?></option><?php
			endfor;
?></select> - 
<select name="from_date_day" id="from_date_day"><option value=""></option>
<?php
			for($j=1;$j<32;$j++) :
?><option value="<?php echo sprintf('%02d',$j); ?>" <?php selected((int)$from_date_day, $j); ?>><?php echo $j; ?></option><?php
			endfor;
?></select> 

<?php _e('-', 'frontend-user-admin'); ?> 

<?php _e('To Date:', 'frontend-user-admin'); ?>
<select name="to_date_year" id="to_date_year">
<option value=""></option>
<?php
			for($j=2008;$j<(date('Y')+10);$j++) :
?><option value="<?php echo $j; ?>" <?php selected($to_date_year, $j); ?>><?php echo $j; ?></option><?php
			endfor;
?></select> - 
<select name="to_date_month" id="to_date_month"><option value=""></option>
<?php
			for($j=1;$j<13;$j++) :
?><option value="<?php echo sprintf('%02d',$j); ?>" <?php selected((int)$to_date_month, $j); ?>><?php echo $j; ?></option><?php
			endfor;
?></select> - 
<select name="to_date_day" id="to_date_day"><option value=""></option>
<?php
			for($j=1;$j<32;$j++) :
?><option value="<?php echo sprintf('%02d',$j); ?>" <?php selected((int)$to_date_day, $j); ?>><?php echo $j; ?></option><?php
			endfor;
?></select><br />

<?php _e('Search Keywords', 'frontend-user-admin'); ?> 
<input type="text" name="q" class="search-input" value="<?php echo esc_attr($_REQUEST['q']); ?>" style="vertical-align:middle;" />
<select name="t" style="vertical-align:middle;">
<option value="user_id"<?php if ( $_REQUEST['t'] == "user_id" ) echo ' selected="selected"'; ?>>User ID</option>
<option value="ip"<?php if ( $_REQUEST['t'] == "ip" ) echo ' selected="selected"'; ?>>IP</option>
<option value="log"<?php if ( $_REQUEST['t'] == "log" ) echo ' selected="selected"'; ?>>Log</option>
<option value="ulog_time"<?php if ( $_REQUEST['t'] == "ulog_time" ) echo ' selected="selected"'; ?>>Datetime</option>
<?php
			if ( is_array($options['user_attribute']['user_attribute']) ) :
				foreach ( $options['user_attribute']['user_attribute'] as $key => $val ) :
					if ( $val['log'] ) :
?>
<option value="<?php echo $val['name']; ?>"<?php selected($_REQUEST['t'], $val['name']); ?>><?php echo $val['label']; ?></option>
<?php
					endif;
				endforeach;
			endif;
?>
</select>
<select name="posts_per_page" style="vertical-align:middle;">
<?php
				for($i=10;$i<110;$i+=10) :
					if( $supplement['posts_per_page'] == $i ) :
?>
<option vale="<?php echo $i; ?>" selected="selected"><?php echo $i; ?></option>
<?php
					else :
?>
<option vale="<?php echo $i; ?>"><?php echo $i; ?></option>
<?php
					endif;
				endfor;
?>
</select> 
<select name="m" style="vertical-align:middle;">
<option value="p" <?php selected('p', $_REQUEST['m']); ?>><?php _e('Match Partial', 'frontend-user-admin'); ?></option>
<option value="f" <?php selected('f', $_REQUEST['m']); ?>><?php _e('Match Full', 'frontend-user-admin'); ?></option>
</select> 
<input type="submit" class="button-secondary" value="<?php _e('Change and Display &raquo;', 'frontend-user-admin'); ?>" />
</div>
<input type="hidden" name="search_button" value="1" />
<input type="hidden" name="paged" value="<?php echo $supplement['paged'] ?>" />
<input type="hidden" name="page" value="frontend-user-admin/frontend-user-admin-log.php" />
</div>
</form>

<div class="clear"></div>

<table class="tablesorter widefat" style="margin:10px 0 5px 0;" cellspacing="0">
<thead>
<tr>
<th id="ulog_id" class="<?php if( $_REQUEST['order'] == 'ulog_id.asc') echo 'headerSortDown'; elseif ( $_REQUEST['order'] == 'ulog_id.desc') echo 'headerSortUp'; else echo 'header'; ?>" style="width:50px;">ID</th>
<th id="user_id" class="<?php if( $_REQUEST['order'] == 'user_id.asc') echo 'headerSortDown'; elseif ( $_REQUEST['order'] == 'user_id.desc') echo 'headerSortUp'; else echo 'header'; ?>"><?php _e('User', 'frontend-user-admin'); ?></th>
<th id="ip" class="<?php if( $_REQUEST['order'] == 'ip.asc') echo 'headerSortDown'; elseif ( $_REQUEST['order'] == 'ip.desc') echo 'headerSortUp'; else echo 'header'; ?>"><?php _e('IP', 'frontend-user-admin'); ?></th>
<th id="log" class="<?php if( $_REQUEST['order'] == 'log.asc') echo 'headerSortDown'; elseif ( $_REQUEST['order'] == 'log.desc') echo 'headerSortUp'; else echo 'header'; ?>"><?php _e('log', 'frontend-user-admin'); ?></th>
<th id="ulog_time" class="<?php if( $_REQUEST['order'] == 'ulog_time.asc') echo 'headerSortDown'; elseif ( $_REQUEST['order'] == 'ulog_time.desc') echo 'headerSortUp'; else echo 'header'; ?>"><?php _e('Datetime', 'frontend-user-admin'); ?></th>
</tr>
</thead>
<tbody>
<?php
				if( !empty($count_result) ) :
					for ( $i=0; $i<$count_result; $i++) :
?>
<tr>
<td><?php echo $result[$i]['ulog_id']; ?></td>
<td><a href="?page=frontend-user-admin/frontend-user-admin.php&user_id=<?php echo $result[$i]['user_id']; ?>"><?php echo $result[$i]['user_id']; ?> <?php echo $result[$i][$options['global_settings']['log_username']]; ?></a></td>
<td><?php echo $result[$i]['ip']; ?></td>
<td><?php echo mb_convert_encoding(rawurldecode($result[$i]['log']), 'UTF-8', 'auto'); ?></td>
<td><?php echo $result[$i]['ulog_time']; ?></td>
</tr>
<?php
					endfor;
				else :
?>
<tr>
<td colspan="5" class="notfound"><?php _e('Not Found', 'frontend-user-admin'); ?></td>
</tr>
<?php
				endif;
?>
</tbody>
</table>

<div class="tablenav">
<?php if ( $supplement['found_posts']>0 ) { ?>
<div class="tablenav-pages"><?php $page_links_text = sprintf( '<span class="displaying-num">' . __( 'Displaying %s&#8211;%s of %s' ) . '</span>%s',
	number_format_i18n( ( $supplement['paged'] - 1 ) * $supplement['posts_per_page'] + 1 ),
	number_format_i18n( min( $supplement['paged'] * $supplement['posts_per_page'], $supplement['found_posts'] ) ),
	number_format_i18n( $supplement['found_posts'] ),
	$page_links
); echo $page_links_text; ?></div>
<?php } ?>
</div>

<div id="poststuff" class="meta-box-sortables" style="position: relative; margin-top:10px;">
<div class="postbox closed">
<div class="handlediv" title="<?php _e('Click to toggle', 'frontend-user-admin'); ?>"><br /></div>
<h3><?php _e('Export User Log Data', 'frontend-user-admin'); ?></h3>
<div class="inside">
<form id="export_content">
<p><?php _e('The user log data will be exported on the current condition.', 'frontend-user-admin'); ?></p>
<p><?php _e('Encode', 'frontend-user-admin'); ?>: <select name="encode">
<option value="UTF-8">UTF-8</option>
<option value="SJIS">SJIS</option>
<option value="EUC-JP">EUC-JP</option>
</select></p>
<p><label for="include_name"><input type="checkbox" name="include_name" id="include_name" value="1" /> <?php _e('Include the item name', 'frontend-user-admin'); ?></label></p>
<p><input type="button" value="<?php _e('Export &raquo;', 'frontend-user-admin'); ?>" name="submit" class="button-primary" onclick="location.href='?action=export_user_log_data&'+jQuery('#export_content').formSerialize()+'&'+jQuery('#select_data').formSerialize();" /></p>
</form>
</div>
</div>
</div>

<?php
			endif;
?>
<script type="text/javascript">
//<![CDATA[
	jQuery(document).ready(function () {
		jQuery('.tablesorter thead tr th.headerSortUp').bind('click', 
			function(e) {location.href='?order='+jQuery(this).attr('id')+'.asc&'+jQuery("#select_data").formSerialize();});
		jQuery('.tablesorter thead tr th.headerSortDown').bind('click', 
			function(e) {location.href='?order='+jQuery(this).attr('id')+'.desc&'+jQuery("#select_data").formSerialize();});
		jQuery('.tablesorter thead tr th.header').bind('click', 
			function(e) {location.href='?order='+jQuery(this).attr('id')+'.asc&'+jQuery("#select_data").formSerialize();});
		jQuery(".tablesorter tbody tr:odd").addClass("odd"); 
		jQuery(".tablesorter tbody tr").hover(
			function() {jQuery(this).addClass("hover");},
			function() {jQuery(this).removeClass("hover");}
		);  	

	});
//]]>
</script>

<script type="text/javascript">
// <![CDATA[
<?php if ( version_compare( substr($wp_version, 0, 3), '2.7', '<' ) ) { ?>
jQuery('.postbox h3').prepend('<a class="togbox">+</a> ');
<?php } ?>
jQuery('.postbox div.handlediv').click( function() { jQuery(jQuery(this).parent().get(0)).toggleClass('closed'); } );
jQuery('.postbox h3').click( function() { jQuery(jQuery(this).parent().get(0)).toggleClass('closed'); } );
jQuery('.postbox.close-me').each(function(){
jQuery(this).addClass("closed");
});
//-->
</script>

</div>
<?php
	}
	
	function frontend_user_admin_logsummary() {
		global $wp_version;
		
		if ( !empty($_REQUEST['from_date_year']) && !empty($_REQUEST['from_date_month']) && $_REQUEST['from_date_day'] ) :
			$from_date_year  = (int)$_REQUEST['from_date_year'];
			$from_date_month = (int)$_REQUEST['from_date_month'];
			$from_date_day   = (int)$_REQUEST['from_date_day'];
			$from_date = sprintf('%04d',$from_date_year).'-'.sprintf('%02d',$from_date_month).'-'.sprintf('%02d',$from_date_day).' 00:00:00';
		else :
			$from_date_year  = date_i18n('Y');
			$from_date_month = date_i18n('m');
			$from_date_day   = 1;
			$from_date = sprintf('%04d',$from_date_year).'-'.sprintf('%02d',$from_date_month).'-'.sprintf('%02d',$from_date_day).' 00:00:00';
		endif;
		
		if ( !empty($_REQUEST['to_date_year']) && !empty($_REQUEST['to_date_month']) && $_REQUEST['to_date_day'] ) :
			$to_date_year  = (int)$_REQUEST['to_date_year'];
			$to_date_month = (int)$_REQUEST['to_date_month'];
			$to_date_day   = (int)$_REQUEST['to_date_day'];
			$to_date = sprintf('%04d',$to_date_year).'-'.sprintf('%02d',$to_date_month).'-'.sprintf('%02d',$to_date_day).' 23:59:59';
		else :
			$to_date_year = $to_date_month = $to_date_day = $to_date = '';
		endif;
		
		if ( empty($_REQUEST['order']) ) $_REQUEST['order'] = '';
		if ( !empty($_REQUEST['condition']) ) $condition = $_REQUEST['condition'];
		else $condition = '';
				
		$result = $this->frontend_user_admin_select_logsummary( array(
										'condition' => $condition,
										'order' => $_REQUEST['order'],
										'from_date' => $from_date,
										'to_date' => $to_date));
		$count_result = count($result);

		$options = get_option('frontend_user_admin');

		if( !empty($options) ) :
?>
<style type="text/css">
table.tablesorter thead
tr .header					{ background:#DFDFDF url(../<?php echo PLUGINDIR; ?>/frontend-user-admin/images/bg.gif) no-repeat top right; cursor: pointer; padding-right: 20px; }
table.tablesorter thead
tr .headerSortUp 			{ background:#DFDFDF url(../<?php echo PLUGINDIR; ?>/frontend-user-admin/images/asc.gif) no-repeat top right; cursor: pointer; padding-right: 20px; }
table.tablesorter thead
tr .headerSortDown			{ background:#DFDFDF url(../<?php echo PLUGINDIR; ?>/frontend-user-admin/images/desc.gif) no-repeat top right; cursor: pointer; padding-right: 20px; }
table.tablesorter thead tr th	{ white-space:nowrap; background:#DFDFDF url(../<?php echo PLUGINDIR; ?>/frontend-user-admin/images/th.gif) no-repeat top right; }
table.tablesorter thead tr th.check-column { padding:0; vertical-align:middle; }
.notfound					{ text-align:center; }
.widefat td 				{ vertical-align:middle; }
.hover						{ background-color:#F3FFF0; }
</style>

<ul class="subsubsub">
<li><a href="?page=frontend-user-admin/frontend-user-admin-log.php"><?php _e('User Log', 'frontend-user-admin'); ?></a> |</li>
<li><a href="?page=frontend-user-admin/frontend-user-admin-log.php&option=logsummary"><?php _e('User Log Summary', 'frontend-user-admin'); ?></a></li>
</ul>

<div class="clear"></div>

<form action="" method="get" id="select_data">
<div class="tablenav" style="height:auto;">
<?php
	$_SERVER['REQUEST_URI'] = remove_query_arg( 'search_button', $_SERVER['REQUEST_URI']);
	if ( isset($supplement['max_num_pages']) && isset($supplement['paged']) ) :
	$page_links = paginate_links( array(
		'base' => add_query_arg( 'paged', '%#%' ),
		'format' => '',
		'prev_text' => __('&laquo;'),
		'next_text' => __('&raquo;'),
		'total' => $supplement['max_num_pages'],
		'current' => $supplement['paged']
	) );
endif;
?>
<?php if ( !empty($page_links) ) { ?>
<div class="tablenav-pages"><?php $page_links_text = sprintf( '<span class="displaying-num">' . __( 'Displaying %s&#8211;%s of %s' ) . '</span>%s',
	number_format_i18n( ( $supplement['paged'] - 1 ) * $supplement['posts_per_page'] + 1 ),
	number_format_i18n( min( $supplement['paged'] * $supplement['posts_per_page'], $supplement['found_posts'] ) ),
	number_format_i18n( $supplement['found_posts'] ),
	$page_links
); echo $page_links_text; ?></div>
<?php } ?>

<div class="alignleft">

<?php _e('From Date:', 'frontend-user-admin'); ?>
<select name="from_date_year" id="from_date_year">
<option value=""></option>
<?php
			for($j=2008;$j<(date('Y')+10);$j++) :
?><option value="<?php echo $j; ?>" <?php selected($from_date_year, $j); ?>><?php echo $j; ?></option><?php
			endfor;
?></select> - 
<select name="from_date_month" id="from_date_month"><option value=""></option>
<?php
			for($j=1;$j<13;$j++) :
?><option value="<?php echo sprintf('%02d',$j); ?>" <?php selected((int)$from_date_month, $j); ?>><?php echo $j; ?></option><?php
			endfor;
?></select> - 
<select name="from_date_day" id="from_date_day"><option value=""></option>
<?php
			for($j=1;$j<32;$j++) :
?><option value="<?php echo sprintf('%02d',$j); ?>" <?php selected((int)$from_date_day, $j); ?>><?php echo $j; ?></option><?php
			endfor;
?></select> 

<?php _e('-', 'frontend-user-admin'); ?> 

<?php _e('To Date:', 'frontend-user-admin'); ?>
<select name="to_date_year" id="to_date_year">
<option value=""></option>
<?php
			for($j=2008;$j<(date('Y')+10);$j++) :
?><option value="<?php echo $j; ?>" <?php selected($to_date_year, $j); ?>><?php echo $j; ?></option><?php
			endfor;
?></select> - 
<select name="to_date_month" id="to_date_month"><option value=""></option>
<?php
			for($j=1;$j<13;$j++) :
?><option value="<?php echo sprintf('%02d',$j); ?>" <?php selected((int)$to_date_month, $j); ?>><?php echo $j; ?></option><?php
			endfor;
?></select> - 
<select name="to_date_day" id="to_date_day"><option value=""></option>
<?php
			for($j=1;$j<32;$j++) :
?><option value="<?php echo sprintf('%02d',$j); ?>" <?php selected((int)$to_date_day, $j); ?>><?php echo $j; ?></option><?php
			endfor;
?></select>

<?php _e('Summary Condition:', 'frontend-user-admin'); ?>
<select name="condition">
<option value="user_id"<?php selected('user_id', $condition); ?>><?php _e('User ID', 'frontend-user-admin'); ?></option>
<?php
			if ( is_array($options['user_attribute']['user_attribute']) ) :
				foreach ( $options['user_attribute']['user_attribute'] as $key => $val ) :
					if ( $val['log'] ) :
?>
<option value="<?php echo esc_attr($val['name']); ?>"<?php selected($val['name'], $condition); ?>><?php echo $val['label']; ?></option>
<?php
					endif;
				endforeach;
			endif;
?>
</select>

<input type="submit" class="button-secondary" value="<?php _e('Change and Display &raquo;', 'frontend-user-admin'); ?>" />
</div>
<input type="hidden" name="search_button" value="1" />
<input type="hidden" name="page" value="frontend-user-admin/frontend-user-admin-log.php" />
<input type="hidden" name="option" value="logsummary" />
</div>

<div class="clear"></div>

<table class="tablesorter widefat" style="margin:10px 0 5px 0;" cellspacing="0">
<thead>
<tr>
<?php
			if ( !empty($options['user_attribute']['user_attribute']) ) $count_user_attribute = count($options['user_attribute']['user_attribute']);
			else $count_user_attribute = 0;

			if ( empty($condition) || $condition == 'user_id' ) :
?>
<th id="user_id" class="<?php if( $_REQUEST['order'] == 'user_id.asc') echo 'headerSortDown'; elseif ( $_REQUEST['order'] == 'user_id.desc') echo 'headerSortUp'; else echo 'header'; ?>"><?php _e('User', 'frontend-user-admin'); ?></th>
<?php
				if ( !empty($options['user_attribute']['user_attribute']) && is_array($options['user_attribute']['user_attribute']) ) :
					for($i=0;$i<$count_user_attribute;$i++) :
						if ( !empty($options['user_attribute']['user_attribute'][$i]['log']) ) :
?>
<th><?php echo $options['user_attribute']['user_attribute'][$i]['label']; ?></th>
<?php
						endif;
					endfor;
				endif;
			else :
				if ( !empty($options['user_attribute']['user_attribute']) && is_array($options['user_attribute']['user_attribute']) ) :
					for($i=0;$i<$count_user_attribute;$i++) :
						if ( $options['user_attribute']['user_attribute'][$i]['name'] == $condition ) :
?>
<th><?php echo $options['user_attribute']['user_attribute'][$i]['label']; ?></th>
<?php
						endif;
					endfor;
				endif;
?>
<th><?php _e('User Count', 'frontend-user-admin'); ?></th>
<?php
			endif;
?>
<th><?php _e('Page Count', 'frontend-user-admin'); ?></th>
<th><?php _e('Unique Count', 'frontend-user-admin'); ?></th>
<th><?php _e('Detail', 'frontend-user-admin'); ?></th>
</tr>
</thead>
<tbody>
<?php
				if( !empty($count_result) ) :
					if ( !empty($options['user_attribute']['user_attribute']) ) $count_user_attribute = count($options['user_attribute']['user_attribute']);
					else $count_user_attribute = 0;

					for ( $i=0; $i<$count_result; $i++) :
?>
<tr>
<?php
						if ( empty($condition) || $condition == 'user_id' ) :
?>
<td><a href="?page=frontend-user-admin/frontend-user-admin.php&user_id=<?php echo $result[$i]['user_id']; ?>"><?php echo $result[$i]['user_id']; ?> <?php echo $result[$i][$options['global_settings']['log_username']]; ?></a></td>
<?php
							if ( !empty($options['user_attribute']['user_attribute']) && is_array($options['user_attribute']['user_attribute']) ) :
								for($j=0;$j<$count_user_attribute;$j++) :
									if ( !empty($options['user_attribute']['user_attribute'][$j]['log']) ) :
										if ( !empty($result[$i][$options['user_attribute']['user_attribute'][$j]['name']]) ) $result[$i][$options['user_attribute']['user_attribute'][$j]['name']] = maybe_unserialize(maybe_unserialize($result[$i][$options['user_attribute']['user_attribute'][$j]['name']]));
										if ( !empty($result[$i][$options['user_attribute']['user_attribute'][$j]['name']]) && is_array($result[$i][$options['user_attribute']['user_attribute'][$j]['name']]) ) :
?><td><?php
											foreach ( $result[$i][$options['user_attribute']['user_attribute'][$j]['name']] as $key2 => $val2 ) :
												echo esc_html($val2)." ";
											endforeach;
?></td><?php
										else :
?>
<td><?php echo isset($result[$i][$options['user_attribute']['user_attribute'][$j]['name']]) ? $result[$i][$options['user_attribute']['user_attribute'][$j]['name']] : ''; ?></td>
<?php
										endif;
									endif;
								endfor;
							endif;
						else :
							if ( !empty($result[$i]['condition']) ) $result[$i]['condition'] = maybe_unserialize(maybe_unserialize($result[$i]['condition']));
							if ( !empty($result[$i]['condition']) && is_array($result[$i]['condition']) ) :
?><td><?php
								foreach ( $result[$i]['condition'] as $key2 => $val2 ) :
									echo esc_html($val2)." ";
								endforeach;
?></td><?php
							else :
?>
<td><?php echo $result[$i]['condition']; ?></td>
<?php
							endif;
							
?>
<td><?php echo $result[$i]['ct_user']; ?></td>
<?php
						endif;
?>
<td><?php echo $result[$i]['ct_id']; ?></td>
<td><?php echo $result[$i]['ct_uq_id']; ?></td>
<?php
						if ( empty($condition) || $condition == 'user_id' ) :
?>
<td><a href="?page=frontend-user-admin/frontend-user-admin-log.php&user_id=<?php echo esc_attr($result[$i]['user_id']); ?>&from_date_year=<?php echo esc_attr($from_date_year); ?>&from_date_month=<?php echo esc_attr($from_date_month); ?>&from_date_day=<?php echo esc_attr($from_date_day); ?>&to_date_year=<?php echo esc_attr($to_date_year); ?>&to_date_month=<?php echo esc_attr($to_date_month); ?>&to_date_day=<?php echo esc_attr($to_date_day); ?>"><?php _e('Detail', 'frontend-user-admin'); ?></a></td>
<?php
						else :
?>
<td><a href="?page=frontend-user-admin/frontend-user-admin-log.php&q=<?php echo esc_attr($result[$i]['condition']); ?>&t=<?php echo esc_attr($condition); ?>&from_date_year=<?php echo esc_attr($from_date_year); ?>&from_date_month=<?php echo esc_attr($from_date_month); ?>&from_date_day=<?php echo esc_attr($from_date_day); ?>&to_date_year=<?php echo esc_attr($to_date_year); ?>&to_date_month=<?php echo esc_attr($to_date_month); ?>&to_date_day=<?php echo esc_attr($to_date_day); ?>"><?php _e('Detail', 'frontend-user-admin'); ?></a></td>
<?php
						endif;
?>
</tr>
<?php
					endfor;
				else :
?>
<tr>
<td colspan="4" class="notfound"><?php _e('Not Found', 'frontend-user-admin'); ?></td>
</tr>
<?php
				endif;
?>
</tbody>
</table>
</form>

<div class="tablenav">
<?php if ( !empty($page_links) ) { ?>
<div class="tablenav-pages"><?php $page_links_text = sprintf( '<span class="displaying-num">' . __( 'Displaying %s&#8211;%s of %s' ) . '</span>%s',
	number_format_i18n( ( $supplement['paged'] - 1 ) * $supplement['posts_per_page'] + 1 ),
	number_format_i18n( min( $supplement['paged'] * $supplement['posts_per_page'], $supplement['found_posts'] ) ),
	number_format_i18n( $supplement['found_posts'] ),
	$page_links
); echo $page_links_text; ?></div>
<?php } ?>
</div>

<div id="poststuff" class="meta-box-sortables" style="position: relative; margin-top:10px;">
<div class="postbox closed">
<div class="handlediv" title="<?php _e('Click to toggle', 'frontend-user-admin'); ?>"><br /></div>
<h3><?php _e('Export User Log Summary Data', 'frontend-user-admin'); ?></h3>
<div class="inside">
<form id="export_content">
<p><?php _e('The user log summary data will be exported on the current condition.', 'frontend-user-admin'); ?></p>
<p><?php _e('Encode', 'frontend-user-admin'); ?>: <select name="encode">
<option value="UTF-8">UTF-8</option>
<option value="SJIS">SJIS</option>
<option value="EUC-JP">EUC-JP</option>
</select></p>
<p><label for="include_name"><input type="checkbox" name="include_name" id="include_name" value="1" /> <?php _e('Include the item name', 'frontend-user-admin'); ?></label></p>
<p><input type="button" value="<?php _e('Export &raquo;', 'frontend-user-admin'); ?>" name="submit" class="button-primary" onclick="location.href='?action=export_user_logsummary_data&'+jQuery('#export_content').formSerialize()+'&'+jQuery('#select_data').formSerialize();" /></p>
</form>
</div>
</div>
</div>

<?php
			endif;
?>
<script type="text/javascript">
//<![CDATA[
	jQuery(document).ready(function () {
		jQuery('.tablesorter thead tr th.headerSortUp').bind('click', 
			function(e) {location.href='?order='+jQuery(this).attr('id')+'.asc&'+jQuery("#select_data").formSerialize();});
		jQuery('.tablesorter thead tr th.headerSortDown').bind('click', 
			function(e) {location.href='?order='+jQuery(this).attr('id')+'.desc&'+jQuery("#select_data").formSerialize();});
		jQuery('.tablesorter thead tr th.header').bind('click', 
			function(e) {location.href='?order='+jQuery(this).attr('id')+'.asc&'+jQuery("#select_data").formSerialize();});
		jQuery(".tablesorter tbody tr:odd").addClass("odd"); 
		jQuery(".tablesorter tbody tr").hover(
			function() {jQuery(this).addClass("hover");},
			function() {jQuery(this).removeClass("hover");}
		);  	

	});
//]]>
</script>

<script type="text/javascript">
// <![CDATA[
<?php if ( version_compare( substr($wp_version, 0, 3), '2.7', '<' ) ) { ?>
jQuery('.postbox h3').prepend('<a class="togbox">+</a> ');
<?php } ?>
jQuery('.postbox div.handlediv').click( function() { jQuery(jQuery(this).parent().get(0)).toggleClass('closed'); } );
jQuery('.postbox h3').click( function() { jQuery(jQuery(this).parent().get(0)).toggleClass('closed'); } );
jQuery('.postbox.close-me').each(function(){
jQuery(this).addClass("closed");
});
//-->
</script>

</div>
<?php
	}
	
	function frontend_user_admin_importuser() {
		global $wp_version, $wpdb;
		
		if ( version_compare( substr($wp_version, 0, 3), '3.1', '<' ) )
			require_once( ABSPATH . WPINC . '/registration.php');
		require_once( ABSPATH . WPINC . '/pluggable.php');
		require_once( ABSPATH . '/wp-admin/includes/user.php');
		global $wpdb, $current_user;

		$options = $this->get_frontend_user_admin_data();

		if ( !empty($options['user_attribute']['user_attribute']) ) $count_user_attribute = count($options['user_attribute']['user_attribute']);
		else $count_user_attribute = 0;

		$_enc_to='UTF-8';
		mb_detect_order('UTF-8,SJIS-win,eucJP-win,SJIS-mac,SJIS,EUC-JP');
		$_enc_from=mb_detect_order();
		if ( isset($_REQUEST['encode']) && in_array($_REQUEST['encode'], array('UTF-8', 'SJIS', 'EUC-JP')) ) $_enc_from = $_REQUEST['encode'];
		mb_regex_encoding("UTF-8");
		
		if ( empty($_REQUEST['action']) ) $_REQUEST['action'] = '';
?>
<div id="poststuff" class="meta-box-sortables" style="position: relative; margin-top:10px;">
<div class="stuffbox">
<h3><?php _e('Import User', 'frontend-user-admin'); ?></h3>
<div class="inside">
<?php
		switch ( $_REQUEST['action'] ) :
			case 'import':
				if ( file_exists($_POST['filename']) ) :
					$row = 0;
					$counter = 0;
					$handle = fopen($_POST['filename'], "r");
					if ( !empty($_POST['firstline']) || !empty($_POST['firstline_as_names']) ) $line = $this->fgetExcelCSV($handle, 4096);
					if ( !empty($_POST['firstline_as_names']) ) $attribute=array_flip($line);
					else $attribute = array_flip($_POST['content']);

					while (($data = $this->fgetExcelCSV($handle, null, ',', '"')) !== false) :
						$counter++;
						$errors = new WP_Error();
						if ( (isset($attribute['ID']) && !empty($data[$attribute['ID']])) || (isset($attribute['user_login']) && !empty($data[$attribute['user_login']]) && $data[$attribute['user_pass']] && !username_exists($data[$attribute['user_login']])) ) :
							mb_convert_variables($_enc_to,$_enc_from,$data);
							$row++;
							$user_id     = trim($data[$attribute['ID']]);
							$user_delete = trim($data[$attribute['user_delete']]);
							$user_login  = trim($data[$attribute['user_login']]);
							$user_pass   = trim($data[$attribute['user_pass']]);
							$user_email  = trim($data[$attribute['user_email']]);
							$user_nicename = !empty($data[$attribute['user_nicename']]) ? mb_convert_encoding(trim($data[$attribute['user_nicename']]), $_enc_to, $_enc_from) : '';
							$display_name  = !empty($data[$attribute['display_name']]) ? mb_convert_encoding(trim($data[$attribute['display_name']]), $_enc_to, $_enc_from) : $user_login;
							$user_status = trim($data[$attribute['user_status']]);
							define('WP_IMPORTING', true);
							if ( !empty($user_id) ) :
								if ( $user = get_userdata( $user_id ) ) :
									if ( !empty($user_delete) ) :
										$errors = wp_delete_user( $user_id );
										continue;
									else :
										if ( !empty($user_pass) ) :
											if ( $options['global_settings']['user_pass_min_letters'] > 0 &&  strlen($user_pass) < $options['global_settings']['user_pass_min_letters'] ) :
												$errors->add( 'pass', sprintf(__( 'ERROR: Minimum number of letters of Password is %s.', 'frontend-user-admin'), $options['global_settings']['user_pass_min_letters']), 'frontend-user-admin' );
											elseif ( $options['global_settings']['user_pass_max_letters'] > 0 &&  strlen($user_pass) > $options['global_settings']['user_pass_max_letters']) :
												$errors->add( 'pass', sprintf(__( 'ERROR: Maximum number of letters of Password is %s.', 'frontend-user-admin'), $options['global_settings']['user_pass_max_letters']), 'frontend-user-admin' );
											else :
												wp_update_user( array ('ID' => $user_id, 'user_pass' => $user_pass) );
											endif;
										endif;

										if ( !empty($user_email) ) wp_update_user( array ('ID' => $user_id, 'user_email' => $user_email) );
										if ( !empty($user_login) ) :
											$wpdb->update( $wpdb->users, array('user_login' => $user_login), array ('ID' => $user_id) );
										endif;
										$user_nicename = !empty($data[$attribute['user_nicename']]) ? mb_convert_encoding(trim($data[$attribute['user_nicename']]), $_enc_to, $_enc_from) : $user->user_nicename;
										$display_name  = !empty($data[$attribute['display_name']]) ? mb_convert_encoding(trim($data[$attribute['display_name']]), $_enc_to, $_enc_from) : $user->display_name;
									endif;
								else :
									$errors = new WP_Error();
									$errors->add( 'nouser', sprintf(__( 'ERROR: ID %d does not exist.', 'frontend-user-admin'), $user_id), 'frontend-user-admin' );
									unset($user_id);
								endif;
							else :
								$errors = wp_create_user( $user_login, $user_pass, $user_email );
							endif;
							if ( is_wp_error($errors) && $errors->get_error_code() ) :
								echo $counter . ". " . implode(",", $data) . "<br />\n";
								foreach ( $errors->get_error_codes() as $code ) :
									foreach ( $errors->get_error_messages($code) as $error ) :
										echo $error . "<br />\n";
									endforeach;
								endforeach;
								if ( empty($user_id) ) :
									$row--; continue;
								endif;
							else :
								if ( empty($user_id) ) :
									$user_id = $errors;
								endif;
							endif;
							$data2 = compact( 'user_url', 'user_nicename', 'display_name', 'user_registered', 'user_status' );
							$data2 = stripslashes_deep( $data2 );
							$ID = $user_id;
							$wpdb->update( $wpdb->users, $data2, array( 'ID' => $ID ) );

							for ( $i=0; $i<count($data); $i++ ) :
								if ( $_POST['content'][$i] != 'ID' && $_POST['content'][$i] != 'user_login' && $_POST['content'][$i] != 'user_pass' && $_POST['content'][$i] != 'user_email' && $_POST['content'][$i] != 'user_url' && $_POST['content'][$i] != 'display_name' && $_POST['content'][$i] != 'nicename' && $_POST['content'][$i] != 'user_registered' && $_POST['content'][$i] != 'user_status' ):
									$tmp_data = trim(trim($data[$i]),'"');
									for($j=0;$j<$count_user_attribute;$j++) :
										if( $options['user_attribute']['user_attribute'][$j]['name']==$_POST['content'][$i] && $options['user_attribute']['user_attribute'][$j]['type']=='checkbox' ) :
											$tmp_data = explode(',', $tmp_data);
											break;
										endif;
									endfor;
									update_user_meta( $user_id, $_POST['content'][$i], $tmp_data );
								endif;
							endfor;
							
							if ( !empty($_POST['send_email']) && is_email($user_email) ) :
								if ( empty($user_pass) ) $user_pass = '********';
								$this->wp_new_user_notification( $user_id, $user_pass);
							endif;

							unset ( $user_login, $user_pass, $user_email, $user_nicename, $display_name, $user_id, $data2 );
						endif;
					endwhile;
					fclose($handle);
?>
<p><?php echo sprintf(__('%d users were registered.', 'frontend-user-admin'), $row); ?></p>
<?php
					unlink($_POST['filename']);
				endif;
				break;
			case 'upload':
				if ( is_uploaded_file($_FILES['usercsv']['tmp_name']) ) :
					$row = 0;
					$handle = fopen($_FILES['usercsv']['tmp_name'], "r");
					if ( !empty($_POST['firstline']) || !empty($_POST['firstline_as_names']) ) $line = $this->fgetExcelCSV($handle, 4096);
					if ( !empty($_POST['firstline_as_names']) ) $_POST['content']=$line;
					if ( !in_array( 'ID', $_POST['content']) && (!in_array( 'user_login', $_POST['content'] ) || !in_array( 'user_pass', $_POST['content'] )) ) :
?>
<p><?php echo _e('You need to specify the user login and user password definitely in the user registration.', 'frontend-user-admin'); ?></p>
<?php
					else :
						echo "<table><thead>";
						$attribute = $this->attribute_name2label();
						$id_array['ID'] = 'ID';
						$id_array['user_delete'] = __('Delete', 'frontend-user-admin');
						$attribute = array_merge($id_array, $attribute);
						if ( !empty($_POST['content']) ) :
							echo "<th>No.</th>";
							foreach ( $_POST['content'] as $val ) :
								echo "<th>" . (isset($val) && isset($attribute[$val]) ? esc_attr($attribute[$val]) : '') . "</th>";
							endforeach;
						endif;
						echo "</thead><tbody>";
						while (($data = $this->fgetExcelCSV($handle, null, ',', '"')) !== false) :
							$row++;
							$num = count($data);
							echo  "<tr><td>" . $row . ". </td>";
							for ($c=0; $c < $num; $c++) :
								if ( !empty($_POST['content'][$c]) && !empty($data[$c]) )
									echo "<td>" . mb_convert_encoding($data[$c], $_enc_to, $_enc_from) . "</td>";
								else
									echo "<td></td>";
							endfor;
							echo "</tr>\n";
						endwhile;
						echo "</tbody>";
						echo "</table>";
						if ( get_option('upload_path') != '' ) $upload_path = get_option('upload_path');
						else $upload_path = 'wp-content/uploads'; 
						$filename = sha1(date_i18n('U')).'.tmp';
						if ( $row>0 )
							move_uploaded_file( $_FILES['usercsv']['tmp_name'], ABSPATH . $upload_path . '/'.$filename );					
						fclose($handle);
						if ( $row>0 ) :
?>
<p><?php echo sprintf(__('%d users will be imported.', 'frontend-user-admin'), $row); ?></p>
<form method="post">
<p><input type="submit" value="<?php _e('Import &raquo;', 'frontend-user-admin'); ?>" name="submit" class="button-primary" />
<input type="hidden" name="action" value="import" /></p>
<input type="hidden" name="page" value="frontend-user-admin/frontend-user-admin.php" />
<input type="hidden" name="option" value="importuser" />
<input type="hidden" name="filename" value="<?php echo ABSPATH . $upload_path . '/'.$filename; ?>" />
<input type="hidden" name="firstline" value="<?php echo isset($_POST['firstline']) ? esc_attr($_POST['firstline']) : ''; ?>" />
<input type="hidden" name="firstline_as_names" value="<?php echo isset($_POST['firstline_as_names']) ? esc_attr($_POST['firstline_as_names']) : ''; ?>" />
<input type="hidden" name="send_email" value="<?php echo isset($_POST['send_email']) ? esc_attr($_POST['send_email']) : ''; ?>" />
<input type="hidden" name="encode" value="<?php echo isset($_POST['encode']) ? esc_attr($_POST['encode']) : ''; ?>" />
<?php
							if ( !empty($_POST['content']) && is_array($_POST['content']) ) :
								foreach ( $_POST['content'] as $val ) :
?>
<input type="hidden" name="content[]" value="<?php echo esc_attr($val); ?>" />
<?php
								endforeach;
							endif;
?>
</form>
<?php
						else :
?>
<p><?php echo _e('There is no user to import.', 'frontend-user-admin'); ?></p>
<?php
						endif;
					endif;
				else :
?>
<p><?php echo _e('There is no user to import.', 'frontend-user-admin'); ?></p>
<?php
				endif;
				break;
			default:
?>
<p><?php _e('Please upload the csv file of user list.', 'frontend-user-admin'); ?></p>
<form method="post" enctype="multipart/form-data">
<p><input type="file" name="usercsv" /></p>
<p><label for="firstline_as_names"><input type="checkbox" name="firstline_as_names" id="firstline_as_names" value="1" onclick="if(jQuery(this).prop('checked')==true){jQuery('#cast_content select option').attr('disabled', true);jQuery('.firstline').attr('disabled', true);}else{jQuery('#cast_content select option').attr('disabled', false);jQuery('.firstline').attr('disabled', false);}" /> <?php _e('Treat the first line as the item names.', 'frontend-user-admin'); ?></label></p>
<p><?php _e('Please specify the user attribute of columns. You need to specify the user login and user password definitely in user registration. If you specify ID, user data will be updated.', 'frontend-user-admin'); ?></p>
<ol style="list-style:decimal inside;" id="cast_content">
<li><select name="content[]" class="content">
<option value=""></option>
<?php
	$attribute = $this->attribute_name2label();
	$id_array['ID'] = 'ID';
	$attribute = array_merge($id_array, $attribute);
	foreach( $attribute as $key => $val ) :
?>
<option value="<?php echo $key; ?>"><?php echo $val; ?></option>
<?php
	endforeach;
?>
</select> <span class="remove"><a href="javascript:void(0);" onclick="jQuery(this).parent().parent().remove();">-</a></span></li>
</ol>
<p><a href="javascript:void(0);" onclick="jQuery('#cast_content').append('<li>'+jQuery('#cast_content :first').html()+'</li>');">+</a></p>
<a href="javascript:void(0);" onclick="var remove = jQuery('#cast_content :first .remove').html(); var original = jQuery('#cast_content :first select option'); var original2 = jQuery('#cast_content :first select'); jQuery('#cast_content').empty(); jQuery.each(original, function() { if ( jQuery(this).val()!='' ) { jQuery('#cast_content').append('<li><select name=content[]>'+original2.html()+'</select> <span class=remove>'+remove+'</span></li>').find('select:last').val(jQuery(this).val());}});">++</a></p>
<p><label for="encode"><?php _e('Encode', 'frontend-user-admin'); ?>: <select name="encode" id="encode">
<option value="UTF-8">UTF-8</option>
<option value="SJIS">SJIS</option>
<option value="EUC-JP">EUC-JP</option>
</select></label></p>
<p><label for="firstline"><input type="checkbox" name="firstline" id="firstline" class="firstline" value="1" /> <?php _e('Omit the first line.', 'frontend-user-admin'); ?></label></p>
<p><label for="send_email"><input type="checkbox" name="send_email" id="send_email" value="1" /> <?php _e('Send email to the new user?', 'frontend-user-admin'); ?></label></p>
<p><input type="submit" value="<?php _e('Upload &raquo;', 'frontend-user-admin'); ?>" name="submit" class="button-primary" <?php if ( !empty($options['global_settings']['admin_demo']) && $current_user->user_level<10 ) echo ' disabled="disabled"'; ?> /><input type="hidden" name="action" value="upload" /></p>
<input type="hidden" name="page" value="frontend-user-admin/frontend-user-admin.php" />
<input type="hidden" name="option" value="importuser" />
</form>
<?php
			break;
		endswitch;
?>
</div>
</div>
</div>
<?php
	}
		
	function frontend_user_admin_user_list() {
		global $net_shop_admin, $wp_roles, $wp_version;

		if ( !is_wp_error($this->errors) ) :
			if ( isset($_GET['change_status']) ) :
				$this->errors = new WP_Error();
				$this->errors->add('change_status', __('Status Changed.', 'frontend-user-admin'), 'message');
				$this->login_header('', $this->errors);
			elseif ( isset($_GET['execute_bulk']) ) :
				$this->errors = new WP_Error();
				$this->errors->add('update_bulk', __('Bulk User Update Code Executed.', 'frontend-user-admin'), 'message');
				$this->login_header('', $this->errors);
			endif;
		endif;
	
		if ( empty($_REQUEST['user_id']) ) $_REQUEST['user_id'] = '';
		if ( empty($_REQUEST['order']) ) $_REQUEST['order'] = '';
		if ( empty($_REQUEST['q']) ) $_REQUEST['q'] = '';
		if ( empty($_REQUEST['t']) ) $_REQUEST['t'] = '';
		if ( empty($_REQUEST['m']) ) $_REQUEST['m'] = '';	
		if ( !isset($_REQUEST['user_status']) ) $_REQUEST['user_status'] = '';
		if ( !isset( $_REQUEST['paged'] ) || !empty($_REQUEST['search_button']) ) $_REQUEST['paged'] = 1;
		if ( empty($_REQUEST['posts_per_page']) ) $_REQUEST['posts_per_page'] = '';
		if ( empty($_REQUEST['check']) ) $_REQUEST['check'] = '';
	
		list($result, $supplement) = $this->select_user_management_data(array(
										'user_id' => $_REQUEST['user_id'],
										'order' => $_REQUEST['order'],
										'q' => $_REQUEST['q'],
										't' => $_REQUEST['t'],
										'm' => $_REQUEST['m'],
										'user_status' => $_REQUEST['user_status'],
										'paged' => $_REQUEST['paged'],
										'posts_per_page' => $_REQUEST['posts_per_page'],
										'check' => $_REQUEST['check']));
		$count_result = count($result);

		$options = get_option('frontend_user_admin');
		$nsa_options = get_option('net_shop_admin');

		if( $options ) :
			$attribute_name2label = $this->attribute_name2label();
			$options = get_option('frontend_user_admin');

			$count_column = 1;
			foreach ( $attribute_name2label as $key => $val ) :
				if ( !empty($options['global_settings']['profile_checkbox'][$key]) ) :
					$count_column++;
				endif;
			endforeach;
			$count_column += 4;
			if ( !empty($options['global_settings']['record_login_datetime']) ) $count_column++;
			if ( !empty($options['global_settings']['record_update_datetime']) ) $count_column++;
			if ( !empty($options['global_settings']['approval_registration']) ) $count_column++;
?>
<style type="text/css">
table.tablesorter thead
tr .header					{ background:#DFDFDF url(../<?php echo PLUGINDIR; ?>/frontend-user-admin/images/bg.gif) no-repeat top right; cursor: pointer; padding-right: 20px; }
table.tablesorter thead
tr .headerSortUp 			{ background:#DFDFDF url(../<?php echo PLUGINDIR; ?>/frontend-user-admin/images/asc.gif) no-repeat top right; cursor: pointer; padding-right: 20px; }
table.tablesorter thead
tr .headerSortDown			{ background:#DFDFDF url(../<?php echo PLUGINDIR; ?>/frontend-user-admin/images/desc.gif) no-repeat top right; cursor: pointer; padding-right: 20px; }
table.tablesorter thead tr th	{ white-space:nowrap; background:#DFDFDF url(../<?php echo PLUGINDIR; ?>/frontend-user-admin/images/th.gif) no-repeat top right; }
table.tablesorter thead tr th.check-column { padding:0; vertical-align:middle; }
table.tablesorter tbody th.check-column	{ padding:0; vertical-align:middle; }
.notfound					{ text-align:center; }
.widefat td					{ vertical-align:middle; }
.hover						{ background-color:#F3FFF0; }
</style>
<form action="" method="get" id="select_data">
<div class="tablenav" style="height:auto;">
<?php
	$_SERVER['REQUEST_URI'] = remove_query_arg( 'search_button', $_SERVER['REQUEST_URI']);
	$page_links = paginate_links( array(
		'base' => add_query_arg( 'paged', '%#%' ),
		'format' => '',
		'prev_text' => __('&laquo;'),
		'next_text' => __('&raquo;'),
		'total' => $supplement['max_num_pages'],
		'current' => $supplement['paged']
	) );
?>
<?php if ( $supplement['found_posts']>0 ) { ?>
<div class="tablenav-pages"><?php $page_links_text = sprintf( '<span class="displaying-num">' . __( 'Displaying %s&#8211;%s of %s' ) . '</span>%s',
	number_format_i18n( ( $supplement['paged'] - 1 ) * $supplement['posts_per_page'] + 1 ),
	number_format_i18n( min( $supplement['paged'] * $supplement['posts_per_page'], $supplement['found_posts'] ) ),
	number_format_i18n( $supplement['found_posts'] ),
	$page_links
); echo $page_links_text; ?></div>
<?php } ?>

<div class="alignleft">
<select name="status">
<option value=""><?php _e('Bulk Actions', 'frontend-user-admin'); ?></option>
<?php if ( !empty($options['global_settings']['approval_registration']) ) : ?>
<option value="approval"><?php _e('Approval', 'frontend-user-admin'); ?></option>
<option value="pending"><?php _e('Pending', 'frontend-user-admin'); ?></option>
<?php endif; ?>
<option value="delete"><?php _e('Delete', 'frontend-user-admin'); ?></option>
</select>
<input id="doaction" class="button-secondary action" type="submit" name="doaction" value="<?php _e('Apply', 'frontend-user-admin'); ?>" />
<?php wp_nonce_field( 'bulk-users', '_wpnonce', false ); ?>
<?php _e('Search Keywords', 'frontend-user-admin'); ?> 
<input type="text" name="q" class="search-input" value="<?php echo esc_attr($_REQUEST['q']); ?>" style="vertical-align:middle;" />
<select name="t" style="vertical-align:middle;">
<option value=""></option>
<option value="ID"<?php if ( $_REQUEST['t'] == "ID" ) echo ' selected="selected"'; ?>>ID</option>
<?php
				foreach ( $options['global_settings']['profile_order'] as $val ) :
					if ( !empty($options['global_settings']['profile_'.$val]) ) :
						if ( $_REQUEST['t'] == $val ) :
?>
<option value="<?php echo $val; ?>" selected="selected"><?php echo $attribute_name2label[$val]; ?></option>
<?php
						else :
?>
<option value="<?php echo $val; ?>"><?php echo $attribute_name2label[$val]; ?></option>
<?php
						endif;
					endif;
				endforeach;
?>
</select> 
<select name="posts_per_page" style="vertical-align:middle;">
<?php
				for($i=10;$i<110;$i+=10) :
					if( $supplement['posts_per_page'] == $i ) :
?>
<option vale="<?php echo $i; ?>" selected="selected"><?php echo $i; ?></option>
<?php
					else :
?>
<option vale="<?php echo $i; ?>"><?php echo $i; ?></option>
<?php
					endif;
				endfor;
?>
</select> 
<select name="m" style="vertical-align:middle;">
<option value="p" <?php selected('p', $_REQUEST['m']); ?>><?php _e('Match Partial', 'frontend-user-admin'); ?></option>
<option value="f" <?php selected('f', $_REQUEST['m']); ?>><?php _e('Match Full', 'frontend-user-admin'); ?></option>
<option value=">=" <?php selected('>=', $_REQUEST['m']); ?>><?php _e('and above', 'frontend-user-admin'); ?></option>
<option value="<=" <?php selected('<=', $_REQUEST['m']); ?>><?php _e('and below', 'frontend-user-admin'); ?></option>
<option value=">" <?php selected('>', $_REQUEST['m']); ?>><?php _e('more than', 'frontend-user-admin'); ?></option>
<option value="<" <?php selected('<', $_REQUEST['m']); ?>><?php _e('less than', 'frontend-user-admin'); ?></option>
</select> 
<select name="user_status" style="vertical-align:middle;">
<option value=""></option>
<option value="0" <?php selected(0, $_REQUEST['user_status']); ?>><?php _e('Active', 'frontend-user-admin'); ?></option>
<option value="1" <?php selected(1, $_REQUEST['user_status']); ?>><?php _e('Pending', 'frontend-user-admin'); ?></option>
</select>
<input type="submit" class="button-secondary" value="<?php _e('Change and Display &raquo;', 'frontend-user-admin'); ?>" />
<a href="javascript:void(0);" onclick="jQuery('#profileoptions').toggle('slow');"><?php _e('Display Options', 'frontend-user-admin'); ?></a>
<div id="profileoptions" style="display:none;">
<?php
				if ( !empty($options['global_settings']['profile_order']) && is_array($options['global_settings']['profile_order']) ) :
					foreach ( $options['global_settings']['profile_order'] as $val ) :
						if ( !empty($options['global_settings']['profile_'.$val]) ) :
?>
    <input type="checkbox" name="check[<?php echo $val; ?>]" id="<?php echo $val; ?>" value="1" <?php if ( !empty($options['global_settings']['profile_checkbox'][$val]) ) checked(1,$options['global_settings']['profile_checkbox'][$val]); ?> /> <label for="<?php echo $val; ?>"><?php echo $attribute_name2label[$val]; ?></label>  
<?php
						endif;
					endforeach;
				endif;
?>
</div>
</div>
<input type="hidden" name="search_button" value="1" />
<input type="hidden" name="paged" value="<?php echo $supplement['paged'] ?>" />
<input type="hidden" name="page" value="frontend-user-admin/frontend-user-admin.php" />

</div>

<div class="clear"></div>

<table class="tablesorter widefat" style="margin:10px 0 5px 0;" cellspacing="0">
<thead>
<tr>
<th id="cb" class="manage-column column-cb check-column" style="vertical-align:middle;" scope="col"><input type="checkbox" /></th>
<th id="ID" class="<?php if( !empty($_REQUEST['order']) && $_REQUEST['order'] == 'ID.asc') echo 'headerSortDown'; elseif ( $_REQUEST['order'] == 'ID.desc') echo 'headerSortUp'; else echo 'header'; ?>" style="width:50px;">ID</th>
<?php
				if ( !empty($options['global_settings']['profile_order']) && is_array($options['global_settings']['profile_order']) ) :
					foreach ( $options['global_settings']['profile_order'] as $val ) :
						if ( !empty($options['global_settings']['profile_'.$val]) && !empty($options['global_settings']['profile_checkbox'][$val]) ) :
							if ( $val == 'role' ) :
?>
<th id="<?php echo $val; ?>"><?php echo $attribute_name2label[$val]; ?></th>
<?php						
							else :
?>
<th id="<?php echo $val; ?>" class="<?php if( $_REQUEST['order'] == $val.'.asc') echo 'headerSortDown'; elseif ( $_REQUEST['order'] == $val.'.desc') echo 'headerSortUp'; else echo 'header'; ?>"><?php echo $attribute_name2label[$val]; ?></th>
<?php
							endif;
						endif;
					endforeach;
				endif;

				if ( !empty($options['global_settings']['record_login_datetime']) ) :
?>
<th id="login_datetime" class="<?php if( $_REQUEST['order'] == 'login_datetime.asc') echo 'headerSortDown'; elseif ( $_REQUEST['order'] == 'login_datetime.desc') echo 'headerSortUp'; else echo 'header'; ?>"><?php _e('Login Datetime', 'frontend-user-admin'); ?></th>
<?php
				endif;
				if ( !empty($options['global_settings']['record_update_datetime']) ) :
?>
<th id="update_datetime" class="<?php if( $_REQUEST['order'] == 'update_datetime.asc') echo 'headerSortDown'; elseif ( $_REQUEST['order'] == 'update_datetime.desc') echo 'headerSortUp'; else echo 'header'; ?>"><?php _e('Update Datetime', 'frontend-user-admin'); ?></th>
<?php
				endif;
				
				if ( $net_shop_admin ) :
					if ( current_user_can('administrator') || (current_user_can('edit_net_shop_admin') && !empty($nsa_options['global_settings']['plugin_user_menu_address'])) || !empty($options['global_settings']['admin_demo']) ) :
						$count_column++;
?>
<th style="text-align:center; width:50px;" class="nsa_address"><?php _e('Address', 'frontend-user-admin'); ?></th>
<?php
					endif;
					if ( current_user_can('administrator') || (current_user_can('edit_net_shop_admin') && !empty($nsa_options['global_settings']['plugin_user_menu_order'])) || !empty($options['global_settings']['admin_demo']) ) :
						$count_column++;
?>
<th style="text-align:center; width:50px;" class="nsa_order"><?php _e('Order', 'frontend-user-admin'); ?></th>
<?php
					endif;
				endif;
				if ( current_user_can('administrator') || (current_user_can('edit_frontend_user_admin') && !empty($options['global_settings']['plugin_user_menu_user_mail'])) || !empty($options['global_settings']['admin_demo']) ) :
					$count_column++;
?>
<th style="text-align:center; width:50px;" class="fua_mail"><?php _e('Mail', 'frontend-user-admin'); ?></th>
<?php
				endif;
				if ( current_user_can('administrator') || (current_user_can('edit_frontend_user_admin') && !empty($options['global_settings']['plugin_user_menu_user_log'])) || !empty($options['global_settings']['admin_demo']) ) :
					$count_column++;
?>
<th style="text-align:center; width:50px;" class="fua_log"><?php _e('Log', 'frontend-user-admin'); ?></th>
<?php
				endif;
?>
<th style="text-align:center; width:50px;" class="fua_edit"><?php _e('Edit', 'frontend-user-admin'); ?></th>
<?php
				if ( current_user_can('administrator') ) :
					$count_column++;
?>
<th style="text-align:center; width:50px;" class="fua_login"><?php _e('Log In', 'frontend-user-admin'); ?></th>
<?php
				endif;
?>
</tr>
</thead>
<tbody>
<?php
				if( $count_result ) :
					for ( $i=0; $i<$count_result; $i++) :
						$user_object = new WP_User($result[$i]['ID']);
						$roles = $user_object->roles;
						$role = array_shift($roles);
?>
<tr>
<th class="check-column" scope="row"><input type="checkbox" name="user_ids[]" value="<?php echo $result[$i]['ID']; ?>" /></th>
<td><?php echo $result[$i]['ID']; ?></td>
<?php
						if ( !empty($options['global_settings']['profile_order']) && is_array($options['global_settings']['profile_order']) ) :
							foreach ( $options['global_settings']['profile_order'] as $val ) :
								if ( !empty($options['global_settings']['profile_'.$val]) && !empty($options['global_settings']['profile_checkbox'][$val]) ) :
									if ( !empty($result[$i][$val]) ) $result[$i][$val] = maybe_unserialize(maybe_unserialize($result[$i][$val]));
									if ( !empty($result[$i][$val]) && is_array($result[$i][$val]) ) :
?><td><?php
										foreach ( $result[$i][$val] as $key2 => $val2 ) :
?>
<?php echo esc_html($val2); ?> 
<?php
										endforeach;
?></td><?php
									else :
										if ( $val == 'role' ) $result[$i][$val] = isset($wp_roles->role_names[$role]) ? translate_user_role($wp_roles->role_names[$role] ) : __('None', 'frontend-user-admin');
										if ( $val == 'user_status' && !empty($result[$i]['user_status']) ) :
											if ( !empty($result[$i]['user_activation_key']) ) $result[$i][$val] = __('E-mail Confirmation', 'frontend-user-admin');
											else $result[$i][$val] = __('Approval Wait', 'frontend-user-admin');
										endif;
										if ( $val == 'no_log' && !empty($result[$i]['no_log']) ) $result[$i][$val] = __('No log', 'frontend-user-admin');
										if ( $val == 'duplicate_login' && !empty($result[$i]['duplicate_login']) ) $result[$i][$val] = __('Valid', 'frontend-user-admin');
?>
<td><?php if ( !empty($result[$i][$val]) ) : echo $result[$i][$val]; else : echo '&nbsp;'; endif; ?></td>
<?php
									endif;
								endif;
							endforeach;
						endif;

						if ( !empty($options['global_settings']['record_login_datetime']) ) :
?>
<td><?php if ( !empty($result[$i]['login_datetime']) ) echo date_i18n('Y-m-d H:i:s', $result[$i]['login_datetime']); ?></td>
<?php
						endif;
						if ( !empty($options['global_settings']['record_update_datetime']) ) :
?>
<td><?php if ( !empty($result[$i]['update_datetime']) ) echo date_i18n('Y-m-d H:i:s', $result[$i]['update_datetime']); ?></td>
<?php
						endif;

						if ( $net_shop_admin ) :
							if ( current_user_can('administrator') || (current_user_can('edit_net_shop_admin') && !empty($nsa_options['global_settings']['plugin_user_menu_address'])) || !empty($options['global_settings']['admin_demo']) ) :
?>
<td style="text-align:center; width:50px;" class="nsa_address"><input type="button" class="button" value="<?php _e('Address', 'frontend-user-admin'); ?>" onclick="location.href='?page=net-shop-admin/address-management.php&t=user_id&q=<?php echo $result[$i]['ID']; ?>';" /></td>
<?php
							endif;
							if ( current_user_can('administrator') || (current_user_can('edit_net_shop_admin') && !empty($nsa_options['global_settings']['plugin_user_menu_order'])) || !empty($options['global_settings']['admin_demo']) ) :
?>
<td style="text-align:center; width:50px;" class="nsa_order"><input type="button" class="button" value="<?php _e('Order', 'frontend-user-admin'); ?>" onclick="location.href='?page=net-shop-admin/order-management.php&user_id=<?php echo $result[$i]['ID']; ?>';" /></td>
<?php
							endif;
						endif;
						if ( current_user_can('administrator') || (current_user_can('edit_frontend_user_admin') && !empty($options['global_settings']['plugin_user_menu_user_mail'])) || !empty($options['global_settings']['admin_demo']) ) :
?>
<td style="text-align:center; width:50px;" class="fua_mail"><input type="button" class="button" value="<?php _e('Mail', 'frontend-user-admin'); ?>" onclick="location.href='?page=frontend-user-admin/frontend-user-admin-mail.php&option=sendmail&user_id=<?php echo $result[$i]['ID']; ?>';" /></td>
<?php
						endif;
						if ( current_user_can('administrator') || (current_user_can('edit_frontend_user_admin') && !empty($options['global_settings']['plugin_user_menu_user_log'])) || !empty($options['global_settings']['admin_demo']) ) :
?>
<td style="text-align:center; width:50px;" class="fua_log"><input type="button" class="button" value="<?php _e('Log', 'frontend-user-admin'); ?>" onclick="location.href='?page=frontend-user-admin/frontend-user-admin-log.php&option=log&t=user_id&q=<?php echo $result[$i]['ID']; ?>';" /></td>
<?php
						endif;
?>
<td style="text-align:center; width:50px;" class="fua_edit"><input type="button" class="button" value="<?php _e('Edit', 'frontend-user-admin'); ?>" onclick="location.href='?page=frontend-user-admin/frontend-user-admin.php&option=edituser&user_id=<?php echo $result[$i]['ID']; ?>';" /></td>
<?php
						if ( current_user_can('administrator') ) :
?>
<td style="text-align:center; width:50px;" class="fua_login"><input type="button" class="button" value="<?php _e('Log In', 'frontend-user-admin'); ?>" onclick="if ( confirm('<?php _e('Are you sure to log in as this user? Current user will be logged out.', 'frontend-user-admin') ?>') ) location.href='?page=frontend-user-admin/frontend-user-admin.php&action=login_hack&user_id=<?php echo $result[$i]['ID']; ?>';" /></td>
<?php
						endif;
?>
</tr>
<?php
					endfor;
				else :
?>
<tr>
<td colspan="<?php echo $count_column; ?>" class="notfound"><?php _e('Not Found', 'frontend-user-admin'); ?></td>
</tr>
<?php
				endif;
?>
</tbody>
</table>
</form>

<div class="tablenav">
<?php if ( $supplement['found_posts']>0 ) { ?>
<div class="tablenav-pages"><?php $page_links_text = sprintf( '<span class="displaying-num">' . __( 'Displaying %s&#8211;%s of %s' ) . '</span>%s',
	number_format_i18n( ( $supplement['paged'] - 1 ) * $supplement['posts_per_page'] + 1 ),
	number_format_i18n( min( $supplement['paged'] * $supplement['posts_per_page'], $supplement['found_posts'] ) ),
	number_format_i18n( $supplement['found_posts'] ),
	$page_links
); echo $page_links_text; ?></div>
<?php } ?>
</div>

<?php
			endif;
?>

<div id="poststuff" class="meta-box-sortables" style="position: relative; margin-top:10px;">
<div class="postbox closed">
<div class="handlediv" title="<?php _e('Click to toggle', 'frontend-user-admin'); ?>"><br /></div>
<h3><?php _e('Export User Data', 'frontend-user-admin'); ?></h3>
<div class="inside">
<form id="export_content">
<p><?php _e('The user attribute list will be exported on the current condition.', 'frontend-user-admin'); ?><br />
<?php _e('Please specify the user attribute of columns you would like to output.', 'frontend-user-admin'); ?></p>
<ol style="list-style:decimal inside;" id="cast_content">
<li><select name="content[]">
<option value=""></option>
<option value="ID"><?php _e('ID', 'frontend-user-admin'); ?></option>
<?php
	foreach ( $options['global_settings']['profile_order'] as $val ) :
		if ( !empty($options['global_settings']['profile_'.$val]) ) :
?>
<option value="<?php echo $val; ?>"><?php echo $attribute_name2label[$val]; ?></option>
<?php
		endif;
	endforeach;
?>
<?php if ( !empty($options['global_settings']['record_login_datetime']) ) : ?><option value="login_datetime"><?php _e('Login Datetime', 'frontend-user-admin'); ?></option><?php endif; ?>
<?php if ( !empty($options['global_settings']['record_update_datetime']) ) : ?><option value="update_datetime"><?php _e('Update Datetime', 'frontend-user-admin'); ?></option><?php endif; ?>
<option value="user_registered"><?php _e('Registered Datetime', 'frontend-user-admin'); ?></option>
</select> <span class="remove"><a href="javascript:void(0);" onclick="jQuery(this).parent().parent().remove();">-</a></span></li>
</ol>
<p><a href="javascript:void(0);" onclick="jQuery('#cast_content').append('<li>'+jQuery('#cast_content :first').html()+'</li>');">+</a></p>
<a href="javascript:void(0);" onclick="var remove = jQuery('#cast_content :first .remove').html(); var original = jQuery('#cast_content :first select option'); var original2 = jQuery('#cast_content :first select'); jQuery('#cast_content').empty(); jQuery.each(original, function() { if ( jQuery(this).val()!='' ) { jQuery('#cast_content').append('<li><select name=content[]>'+original2.html()+'</select> <span class=remove>'+remove+'</span></li>').find('select:last').val(jQuery(this).val());}});">++</a></p>
<p><?php _e('Encode', 'frontend-user-admin'); ?>: <select name="encode">
<option value="UTF-8">UTF-8</option>
<option value="SJIS">SJIS</option>
<option value="EUC-JP">EUC-JP</option>
</select></p>
<p><label for="include_name"><input type="checkbox" name="include_name" id="include_name" value="1" /> <?php _e('Include the item name', 'frontend-user-admin'); ?></label></p>
<p><label for="with_field_name"><input type="checkbox" name="with_field_name" id="with_field_name" value="1" /> <?php _e('Output the item name with the field name', 'frontend-user-admin'); ?></label></p>
<p><input type="button" value="<?php _e('Export &raquo;', 'frontend-user-admin'); ?>" name="submit" class="button-primary" onclick="location.href='?action=export_user_data&'+jQuery('#export_content').formSerialize()+'&'+jQuery('#select_data').formSerialize();" /></p>
</form>
</div>
</div>

<?php
	if( !empty($options['global_settings']['use_bulk_user_update_code']) ) :
?>
<div class="postbox closed">
<div class="handlediv" title="<?php _e('Click to toggle', 'frontend-user-admin'); ?>"><br /></div>
<h3><?php _e('Bulk User Update Code', 'frontend-user-admin'); ?></h3>
<div class="inside">
<form id="bulk_content">
<p><?php _e('The following code will be executed on the current condition.', 'frontend-user-admin'); ?><br />
<p><textarea name="code" class="large-text"></textarea></p>
<p><input type="button" value="<?php _e('Execute &raquo;', 'frontend-user-admin'); ?>" name="submit" class="button-primary" onclick="location.href='?action=bulk_user_update_code&'+jQuery('#bulk_content').formSerialize()+'&'+jQuery('#select_data').formSerialize();" /></p>
</form>
</div>
</div>
<?php
	endif;
?>
</div>

<script type="text/javascript">
//<![CDATA[
	jQuery(document).ready(function () {
		jQuery('.tablesorter thead tr th.headerSortUp').bind('click', 
			function(e) {location.href='?order='+jQuery(this).attr('id')+'.asc&'+jQuery("#select_data").formSerialize();});
		jQuery('.tablesorter thead tr th.headerSortDown').bind('click', 
			function(e) {location.href='?order='+jQuery(this).attr('id')+'.desc&'+jQuery("#select_data").formSerialize();});
		jQuery('.tablesorter thead tr th.header').bind('click', 
			function(e) {location.href='?order='+jQuery(this).attr('id')+'.asc&'+jQuery("#select_data").formSerialize();});
		jQuery(".tablesorter tbody tr:odd").addClass("odd"); 
		jQuery(".tablesorter tbody tr").hover(
			function() {jQuery(this).addClass("hover");},
			function() {jQuery(this).removeClass("hover");}
		);  	

	});
//]]>
</script>

<script type="text/javascript">
// <![CDATA[
<?php if ( version_compare( substr($wp_version, 0, 3), '2.7', '<' ) ) { ?>
jQuery('.postbox h3').prepend('<a class="togbox">+</a> ');
<?php } ?>
jQuery('.postbox div.handlediv').click( function() { jQuery(jQuery(this).parent().get(0)).toggleClass('closed'); } );
jQuery('.postbox h3').click( function() { jQuery(jQuery(this).parent().get(0)).toggleClass('closed'); } );
jQuery('.postbox.close-me').each(function(){
jQuery(this).addClass("closed");
});
//-->
</script>
</div>
<?php
	}
	
	function frontend_user_admin_login_form() {
		global $pagenow;
		if ( $pagenow == 'wp-login.php' ) return;
		
		$options = $this->get_frontend_user_admin_data();

		if ( !empty($options['recaptcha_options']['site_key']) && !empty($options['recaptcha_options']['login']) ) :
			$addition = '';
			if ( !empty($options['recaptcha_options']['theme']) ) $addition .= ' data-theme="'.$options['recaptcha_options']['theme'].'"';
			if ( !empty($options['recaptcha_options']['type']) ) $addition .= ' data-type="'.$options['recaptcha_options']['type'].'"';
			if ( !empty($options['recaptcha_options']['size']) ) $addition .= ' data-size="'.$options['recaptcha_options']['size'].'"';
?>
<p class="g-recaptcha" data-sitekey="<?php echo esc_attr($options['recaptcha_options']['site_key']); ?>"<?php echo $addition; ?>></p>
<?php
		endif;
	}
	
	function frontend_user_admin_register_form() {
		$options = $this->get_frontend_user_admin_data();

		if ( !empty($options['recaptcha_options']['site_key']) && !empty($options['recaptcha_options']['registration']) ) :
?>
<p class="g-recaptcha" data-sitekey="<?php echo esc_attr($options['recaptcha_options']['site_key']); ?>"></p>
<?php
		endif;
	}
	
	function widget_login_form() {
		global $current_user, $net_shop_admin;
		$options = $this->get_frontend_user_admin_data();

		if( !empty($options['global_settings']['after_login_url']) ) $options['global_settings']['after_login_url'] = preg_replace('/%user_login%/', $current_user->user_login, $options['global_settings']['after_login_url']);
		
		if( !empty($options['output_options']['output_widget']) ) :
			$content = $this->EvalBuffer($options['output_options']['output_widget']);
			return $content;
		endif;

		ob_start();

		include(WP_PLUGIN_DIR.'/frontend-user-admin/steps/step-widget.php');

		$content = ob_get_contents();
		ob_end_clean();
		return $content;
	}
	
	function _frontend_user_admin_delete_log() {
		$options = $this->get_frontend_user_admin_data();
		if ( empty($options['global_settings']['start_log']) || empty($options['global_settings']['delete_log_days']) ) return;

		$current = get_transient( 'frontend_user_admin_delete_log' );
		if ( isset( $current ) && 43200 > ( time() - $current ) )
			return;
		$this->frontend_user_admin_delete_log();
	}
	
	function frontend_user_admin_delete_log() {
		global $wpdb;
		$options = $this->get_frontend_user_admin_data();
		
		if ( !empty($options['global_settings']['delete_log_days']) && is_numeric($options['global_settings']['delete_log_days']) && $options['global_settings']['delete_log_days']>=1 ) :
			$time = (int)$options['global_settings']['delete_log_days']*86400;

			$query = $wpdb->prepare("DELETE FROM `".$wpdb->prefix."userlog` WHERE UNIX_TIMESTAMP(`ulog_time`) < (UNIX_TIMESTAMP()-%s);", $time);
			$wpdb->query($query);
		endif;

		$current = time();
		set_transient( 'frontend_user_admin_delete_log', $current );
	}
	
	function frontend_user_admin_get_user_meta() {
		if ( (!current_user_can('edit_frontend_user_admin') && !current_user_can('administrator')) || !isset($_POST['user_id']) || empty($_POST['user_key']) ) die();
		$values = maybe_unserialize(get_user_meta( $_POST['user_id'], trim($_POST['user_key']), true));
		if ( is_array($values) ) :
			$values = '1'.$this->json_xencode($values);
		else :
			$values = '0'.$values;
		endif;
		echo $values;
		die();
	}
	
	function EvalBuffer($string) {
		ob_start();
		eval('?>'.$string);
		$ret = ob_get_contents();
		ob_end_clean();
		return $ret;
	}
	
	function toArray($data) {
		if (is_object($data)) $data = get_object_vars($data);
		return is_array($data) ? array_map(array(&$this, 'toArray'), $data) : $data;
	}
	
	function decode_from_ktai_deep($value) {
		global $Ktai_Style;

		$value = is_array($value) ? array_map(array(&$this, 'decode_from_ktai_deep'), $value) : $Ktai_Style->admin->base->decode_from_ktai($value, 'UTF-8');
		return $value;
	}
	
	function fgetExcelCSV(&$fp , $length = null, $delimiter = ',' , $enclosure = '"') {
		$line = fgets($fp);
		if($line === false) {
			return false;
		}
		$bytes = preg_split('//' , trim($line));
		array_shift($bytes);array_pop($bytes);
		$cols = array();
		$col = '';
		$isInQuote = false;
		while($bytes) {
			$byte = array_shift($bytes);
			if($isInQuote) {
				if($byte == $enclosure) {
					if( isset($bytes[0]) && $bytes[0] == $enclosure) {
						$col .= $byte;
						array_shift($bytes);
					} else {
						$isInQuote = false;
					}
				} else {
					$col .= $byte;
				}
			} else {
				if($byte == $delimiter) {
					$cols[] = $col;
					$col = '';
				} elseif($byte == $enclosure && $col == '') {
					$isInQuote = true;
				} else {
					$col .= $byte;
				}
			}
			while(!$bytes && $isInQuote) {
				$col .= "\n";
				$line = fgets($fp);
				if($line === false) {
					$isInQuote = false;
				} else {
					$bytes = preg_split('//' , trim($line));
					array_shift($bytes);array_pop($bytes);
				}
			}
		}
		$cols[] = $col;
		return $cols;
	}
	
	function json_xencode($value, $unescapee_unicode = true) {
		$v = json_encode($value);
		if ($unescapee_unicode) {
			$v = $this->unicode_encode($v);
			$v = preg_replace('/\\\\\//', '/', $v);
		}
		return $v;
	}

	function base64_url_encode($input) {
		return strtr(base64_encode($input), '+/=', '-._');
	}

	function base64_url_decode($input) {
		return base64_decode(strtr($input, '-._', '+/='));
	}
	
	function fua_crypt( $string ) {
		$iv = '12345678';
		$resource = mcrypt_module_open(MCRYPT_BLOWFISH, '',  MCRYPT_MODE_CBC, '');
		$salt = AUTH_SALT;
		$salt = substr($salt, 0, mcrypt_enc_get_key_size($resource)); 

		mcrypt_generic_init($resource, $salt, $iv);
		$encrypted_data = mcrypt_generic($resource, $this->base64_url_encode($string));
		mcrypt_generic_deinit($resource);
		$key = $this->base64_url_encode($encrypted_data);
		return $key;
	}

	function fua_decrypt ( $key ) {
		$iv = '12345678';
		$resource = mcrypt_module_open(MCRYPT_BLOWFISH, '',  MCRYPT_MODE_CBC, '');
		$salt = AUTH_SALT;
		$salt = substr($salt, 0, mcrypt_enc_get_key_size($resource)); 

		mcrypt_generic_init($resource, $salt, $iv);
		$base64_decrypted_data = mdecrypt_generic($resource, $this->base64_url_decode($key));
		mcrypt_generic_deinit($resource);
		
		$string = $this->base64_url_decode($base64_decrypted_data);
		return $string;
	}

	function unicode_encode($str) {
		return preg_replace_callback("/\\\\u([0-9a-zA-Z]{4})/", array(&$this, "encode_callback"), $str);
	}

	function encode_callback($matches) {
		return mb_convert_encoding(pack("H*", $matches[1]), "UTF-8", "UTF-16");
	}
	
	function is_clawler( $ua = null ) {
		if ( empty($ua) ) $ua = $_SERVER['HTTP_USER_AGENT'];
		
		$crawlers = array(
			"Googlebot",
			"bingbot",
		);
		
		foreach ($crawlers as $clawler) :
			if (stripos($ua, $clawler) !== false) return true;
		endforeach;

		return false;
	}
	
	function mb_strimwidth_with_elements($text, $length= 100, $ending= '...', $exact= true, $considerHtml= false){ 
		if ($considerHtml) {
			if (mb_strlen( strip_tags( $text ),"UTF-8" ) <= $length) { 
				return $text; 
			} 

			preg_match_all('/(<.+?>)?([^<>]*)/s', $text, $lines, PREG_SET_ORDER); 
			$total_length = mb_strlen($ending,"UTF-8"); 
			$open_tags  = array(); 
			$truncate   = ''; 

			foreach ($lines as $line_matchings) { 
				if ( isset($line_matchings[1]) and !empty( $line_matchings[1] ) ) { 
					if (preg_match('/^<(\s*.+?\/\s*|\s*(img|br|input|hr|area|base|basefont|col|frame|isindex|link|meta|param)(\s.+?)?)>$/is', $line_matchings[1])) { 
					} else if (preg_match('/^<\s*\/([^\s]+?)\s*>$/s', $line_matchings[1], $tag_matchings)) { 
						$pos= array_search($tag_matchings[1], $open_tags); 
						if ($pos !== false) { 
							unset($open_tags[$pos]); 
						} 
					} else if (preg_match('/^<\s*([^\s>!]+).*?>$/s', $line_matchings[1], $tag_matchings)) { 
						array_unshift($open_tags, strtolower($tag_matchings[1])); 
					} 
					$truncate .= $line_matchings[1]; 
				} 
	
				$content_length= mb_strlen(preg_replace('/&[0-9a-z]{2,8};|&#[0-9]{1,7};|&#x[0-9a-f]{1,6};/i', ' ', $line_matchings[2]),"UTF-8"); 
				if ($total_length+$content_length> $length) { 
					$left= $length - $total_length; 
					$entities_length= 0; 
					if (preg_match_all('/&[0-9a-z]{2,8};|&#[0-9]{1,7};|&#x[0-9a-f]{1,6};/i', $line_matchings[2], $entities, PREG_OFFSET_CAPTURE)) { 
						foreach ($entities[0] as $entity) { 
							if ($entity[1]+1-$entities_length <= $left) { 
								$left--; 
								$entities_length += mb_strlen($entity[0],"UTF-8"); 
							} else { 
								break; 
							} 
						}
					} 
					$truncate .= mb_substr($line_matchings[2], 0, $left+$entities_length,"UTF-8"); 
					break; 
				} else { 
					$truncate .= $line_matchings[2]; 
					$total_length += $content_length; 
				} 
				if($total_length>= $length) { 
					break; 
				} 
			} 
		} else { 
			if (mb_strlen($text,"UTF-8") <= $length) { 
				return $text; 
			} else { 
				$truncate= mb_substr($text, 0, $length - mb_strlen($ending,"UTF-8"),"UTF-8"); 
			} 
		} 
		if (!$exact) { 
			$spacepos= mb_strrpos($truncate, ' '); 
			if (isset($spacepos)) { 
				$truncate= mb_substr($truncate, 0, $spacepos,"UTF-8"); 
			} 
		}
	
		$truncate .= $ending; 
		if($considerHtml) { 
			foreach ($open_tags as $tag) { 
				$truncate .= '</' . $tag . '>'; 
			} 
		} 
		
		return $truncate; 
	}

	function implode_recursive($glue, array $pieces) {
		$f = function ($r, $p) use ($glue, &$f) { return (empty($r) ? '' : "{$r}{$glue}").(is_array($p) ? array_reduce($p, $f) : $p); };
		return array_reduce($pieces, $f, '');
	}
}

$frontend_user_admin = new frontend_user_admin();
global $frontend_user_admin, $is_iphone;
$frontend_user_admin_options = get_option('frontend_user_admin');
if ( stripos($_SERVER['HTTP_USER_AGENT'], 'android') !== false && stripos($_SERVER['HTTP_USER_AGENT'], 'mobile') !== false ) $is_iphone = true;
if ( !empty($frontend_user_admin_options['global_settings']['exclude_ipad']) && stripos($_SERVER['HTTP_USER_AGENT'], 'iPad') !== false ) $is_iphone = false;

class WP_Widget_Frontend_User_Admin extends WP_Widget {

	function __construct() {
		$widget_ops = array('classname' => 'frontend_user_admin', 'description' => __( 'Frontend User Admin', 'frontend-user-admin') );
		parent::__construct('frontend_user_admin', __('Frontend User Admin', 'frontend-user-admin'), $widget_ops);
	}

	function widget( $args, $instance ) {
		global $frontend_user_admin, $current_user;
		$options = get_option('frontend_user_admin');
		extract( $args );
		$pretext = isset($instance['pretext']) ? $instance['pretext'] : '';
		$posttext = isset($instance['posttext']) ? $instance['posttext'] : '';

		if ( !empty($options['global_settings']['disable_widget_while_login']) && is_user_logged_in() ) return;
		if ( !empty($options['global_settings']['disable_widget_while_nologin']) && !is_user_logged_in() ) return;

		if ( !empty($options['global_settings']['after_login_url']) ) $options['global_settings']['after_login_url'] = preg_replace('/%user_login%/', $current_user->user_login, $options['global_settings']['after_login_url']);

		if ( !empty($options['global_settings']['widget_title']) ) :
			$title = do_shortcode($frontend_user_admin->EvalBuffer($options['global_settings']['widget_title']));
		elseif ( is_user_logged_in() ) :
			if( !empty($options['global_settings']['after_login_url']) )
				$title = '<a href="' . $options['global_settings']['after_login_url'] . '">' . __('Logged In', 'frontend-user-admin') . '</a>';
			else
				$title = __('Logged In', 'frontend-user-admin');
		else :
			$title = '<a href="' . $options['global_settings']['login_url']. '">' . __('Log In', 'frontend-user-admin') . '</a>';
		endif;

		$output = $before_widget;
		if ( !empty($title) && empty($notitle) )
			$output .= $before_title . $title . $after_title;
		$output .= '<div class="frontend-user-admin-widget-login">';
		$output .= $pretext;
		if ( !empty($options['global_settings']['widget_content']) ) :
			$output .= do_shortcode($frontend_user_admin->EvalBuffer($options['global_settings']['widget_content']));
		endif;
		
		if ( !empty($args['redirect_to']) ) set_transient('fua_redirect_to', $args['redirect_to']);

		$output .= $frontend_user_admin->widget_login_form();
		$output .= $posttext;
		$output .= '</div>';
		$output .= $after_widget;
		
		if ( !empty($shortcode) ) :
			return $output;
		else :
			echo $output;
		endif;
	}

	function update( $new_instance, $old_instance ) {
		$options = get_option('frontend_user_admin');
		$instance = $old_instance;
		$instance['pretext'] = stripslashes($new_instance['pretext']);
		$instance['posttext'] = stripslashes($new_instance['posttext']);

		return $instance;
	}

	function form( $instance ) {
		$options = get_option('frontend_user_admin');
		$pretext = isset( $instance['pretext'] ) ? esc_attr($instance['pretext']) : '';
		$posttext = isset( $instance['posttext'] ) ? esc_attr($instance['posttext']) : '';
?>
<p><label for="<?php echo $this->get_field_id('pretext'); ?>"><?php _e( 'Pre-text', 'frontend-user-admin' ); ?></label> <textarea name="<?php echo $this->get_field_name('pretext'); ?>" id="<?php echo $this->get_field_id('pretext'); ?>" class="widefat"><?php echo $pretext; ?></textarea></p>
<p><label for="<?php echo $this->get_field_id('posttext'); ?>"><?php _e( 'Post-text', 'frontend-user-admin' ); ?></label> <textarea name="<?php echo $this->get_field_name('posttext'); ?>" id="<?php echo $this->get_field_id('posttext'); ?>" class="widefat"><?php echo $posttext; ?></textarea></p>
<?php
	}
}
?>