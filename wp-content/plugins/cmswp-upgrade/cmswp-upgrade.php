<?php
/*
Plugin Name: CMSWP Upgrade
Plugin URI: http://www.cmswp.jp/plugins/cmswp_upgrade/
Description: This plugin adds the functionality to upgrade the plugins cmswp provides.
Author: Hiroaki Miyashita
Version: 1.1.7
Author URI: http://www.cmswp.jp/
*/

/*  Copyright 2009 -2016 Hiroaki Miyashita

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

class cmswp_upgrade {
	public $plugin_files;

	function __construct() {
		add_action( 'init', array(&$this, 'cmswp_upgrade_init') );
		add_action( 'admin_init', array(&$this, 'cmswp_upgrade_admin_init') );
		add_action( 'admin_menu', array(&$this, 'cmswp_upgrade_admin_menu') );

		remove_action( 'load-plugins.php', 'wp_update_plugins' );
		remove_action( 'load-update.php', 'wp_update_plugins' );
		remove_action( 'admin_init', '_maybe_update_plugins' );
		remove_action( 'wp_update_plugins', 'wp_update_plugins' );
		
		add_action( 'load-plugins.php', array(&$this, 'wp_update_plugins') );
		add_action( 'load-update.php', array(&$this, 'wp_update_plugins') );
		add_action( 'admin_init', array(&$this, '_maybe_update_plugins') );
		add_action( 'wp_update_plugins', array(&$this, 'wp_update_plugins') );
	}
	
	function cmswp_upgrade_init() {
		if ( function_exists('load_plugin_textdomain') ) :
			if ( !defined('WP_PLUGIN_DIR') ) 
				load_plugin_textdomain('cmswp-upgrade', str_replace( ABSPATH, '', dirname(__FILE__) ) );
			else
				load_plugin_textdomain('cmswp-upgrade', false, dirname( plugin_basename(__FILE__) ) );
		endif;
	}
	
	function cmswp_upgrade_admin_init() {
		$pluginlist = $this->cmswp_upgrade_plugin_list();
		
		foreach ( $pluginlist as $key => $val ) :
			$this->plugin_files[$key] = $val[0];
		endforeach;

		$this->plugin_files['cmswp-upgrade'] = 'cmswp-upgrade/cmswp-upgrade.php';

		foreach ( $this->plugin_files as $plugin_file ) :
			remove_action( 'after_plugin_row_'.$plugin_file, 'wp_plugin_update_row');
			add_action( 'after_plugin_row_'.$plugin_file, array(&$this, 'wp_plugin_update_row'), 10, 2 );
		endforeach;
	}
	
	function cmswp_upgrade_plugin_list() {
		$pluginlist['business-calendar']          = array('business-calendar/business-calendar.php', __('Business Calendar', 'cmswp-upgrade'));
		$pluginlist['category-widget-ext']        = array('category-widget-ext/category-widget-ext.php', __('Category Widget Ext', 'cmswp-upgrade'));
		$pluginlist['page-widget-ext']            = array('page-widget-ext/page-widget-ext.php', __('Page Widget Ext', 'cmswp-upgrade'));
		$pluginlist['cms-admin-controller']       = array('cms-admin-controller/cms-admin-controller.php', __('CMS Admin Controller', 'cmswp-upgrade'));
		$pluginlist['whats-new-maker']            = array('whats-new-maker/whats-new-maker.php', __("What's New Maker", 'cmswp-upgrade'));
		$pluginlist['custom-php-widget-manager']  = array('custom-php-widget-manager/custom-php-widget-manager.php', __('Custom PHP Widget Manager', 'cmswp-upgrade'));
		$pluginlist['custom-column-maker']        = array('custom-column-maker/custom-column-maker.php', __('Custom Column Maker', 'cmswp-upgrade'));
		$pluginlist['linkify-keywords']           = array('linkify-keywords/linkify-keywords.php', __('linkify Keywords', 'cmswp-upgrade'));
		$pluginlist['translation-overwriter']     = array('translation-overwriter/translation-overwriter.php', __('Translation Overwriter', 'cmswp-upgrade'));
		$pluginlist['csv-post-manager']           = array('csv-post-manager/csv-post-manager.php', __('CSV Post Manager', 'cmswp-upgrade'));
		$pluginlist['twitter-joint-manager']      = array('twitter-joint-manager/twitter-joint-manager.php', __('Twitter Joint Manager', 'cmswp-upgrade'));
		$pluginlist['event-attendance-manager']   = array('event-attendance-manager/event-attendance-manager.php', __('Event Attendance Manager', 'cmswp-upgrade'));
		$pluginlist['attendance-management-timecard']   = array('attendance-management-timecard/attendance-management-timecard.php', __('Attendance Management Timecard', 'cmswp-upgrade'));
		$pluginlist['mail-magazine-newsletter']   = array('mail-magazine-newsletter/mail-magazine-newsletter.php', __('Mail Magazine Newsletter', 'cmswp-upgrade'));
		$pluginlist['frontend-user-admin']        = array('frontend-user-admin/frontend-user-admin.php', __('Frontend User Admin', 'cmswp-upgrade'));
		$pluginlist['net-shop-admin']             = array('net-shop-admin/net-shop-admin.php', __('Net Shop Admin', 'cmswp-upgrade'));
		
		return $pluginlist;
	}
	
	function cmswp_upgrade_admin_menu() {
		add_options_page(__('CMSWP Upgrade', 'cmswp-upgrade'), __('CMSWP Upgrade', 'cmswp-upgrade'), 'manage_options', basename(__FILE__), array(&$this, 'cmswp_upgrade_admin'));
	}

	function cmswp_upgrade_admin() {
		$options = get_option('cmswp_upgrade_data');
		
		if( !empty($_POST["cmswp_upgrade_global_settings_submit"]) ) :
			$options['global_settings']['cmswp_email'] = $_POST['cmswp_email'];
			$options['global_settings']['premium_code'] = $_POST['premium_code'];
			update_option('cmswp_upgrade_data', $options);
			$message = __('Options updated.', 'cmswp-upgrade');
		elseif ( !empty($_POST['cmswp_upgrade_upgrade_settings_submit']) ) :
			$pluginlist = $this->cmswp_upgrade_plugin_list();

			foreach ( $pluginlist as $key => $val ) :
				$options['global_settings'][$key] = $_POST[$key];
				$options['global_settings'][$key.'-fail'] = 0;
				$options['global_settings'][$key.'-expiration'] = 0;
			endforeach;
			update_option('cmswp_upgrade_data', $options);
			$message = __('Options updated.', 'cmswp-upgrade');
		elseif ( !empty($_POST['cmswp_upgrade_delete_options_submit']) ) :
			delete_option('cmswp_upgrade_data');
			$options = get_option('cmswp_upgrade_data');
			$message = __('Options deleted.', 'cmswp-upgrade');
		endif;
		if( empty($options) ) $options = array();
		$plugins = get_site_transient( 'update_plugins' );
?>
<style type="text/css">
<!--
#poststuff h3			{ font-size: 14px; line-height: 1.4; margin: 0; padding: 8px 12px; }
-->
</style>

<?php if ( !empty($message) ) : ?>
<div id="message" class="updated"><p><?php echo $message; ?></p></div>
<?php endif; ?>
<div class="wrap">
<div id="icon-plugins" class="icon32"><br/></div>
<h2><?php _e('CMSWP Upgrade', 'cmswp-upgrade'); ?></h2>

<br class="clear"/>

<div id="poststuff" class="meta-box-sortables" style="position: relative; margin-top:10px;">
<div class="stuffbox">
<h3><?php _e('Global Settings', 'cmswp-upgrade'); ?></h3>
<div class="inside">
<form method="post">
<table class="form-table" style="margin-bottom:5px;">
<tbody>
<tr>
<th><label for="cmswp_email"><?php _e('CMSWP Email', 'cmswp-upgrade'); ?></label></th>
<td><input type="text" name="cmswp_email" id="cmswp_email" class="regular-text" value="<?php if ( isset($options['global_settings']['cmswp_email']) ) echo esc_attr($options['global_settings']['cmswp_email']); ?>" /></td>
</tr>
<tr>
<th><label for="premium_code"><?php _e('Premium Code', 'cmswp-upgrade'); ?></label></th>
<td><input type="text" name="premium_code" id="premium_code" class="regular-text" value="<?php if ( isset($options['global_settings']['premium_code']) ) echo esc_attr($options['global_settings']['premium_code']); ?>" /><br />
<p><?php echo sprintf(__('We recommend the <a href="%s" target="_blank">Premium Support</a> in order to use the automatic upgrade.', 'cmswp-upgrade'), 'http://www.cmswp.jp/plugins/premium_support/'); ?></p></td>
</tr>
<tr><td colspan="2">
<p><input type="submit" name="cmswp_upgrade_global_settings_submit" value="<?php _e('Update Options &raquo;', 'cmswp-upgrade'); ?>" class="button-primary" /></p>
</td></tr>
</tbody>
</table>
</form>
</div>
</div>

<div class="stuffbox">
<h3><?php _e('Upgrade Options', 'cmswp-upgrade'); ?></h3>
<div class="inside">
<form method="post">
<table class="form-table" style="margin-bottom:5px;">
<tbody>
<?php
		$pluginlist = $this->cmswp_upgrade_plugin_list();

		foreach ( $pluginlist as $key => $val ) :
?>
<tr>
<th><label for="<?php echo $key; ?>"><?php echo sprintf(__('%s Upgrade Code', 'cmswp-upgrade'), $val[1]); ?></label></th>
<td><input type="text" name="<?php echo $key; ?>" id="<?php echo $key; ?>" class="regular-text" value="<?php if ( isset($options['global_settings'][$key]) ) echo esc_attr($options['global_settings'][$key]); ?>" /> <?php if ( !empty($options['global_settings'][$key.'-expiration']) ) : echo '[ '.$options['global_settings'][$key.'-expiration'].' ]'; endif; ?> <?php if ( !empty($options['global_settings']['business-calendar-fail']) ) : _e('Upgrade Code was not approved.', 'cmswp-upgrade'); endif; ?></td>
</tr>
<?php
		endforeach;
?>
<tr><td>
<p><input type="submit" name="cmswp_upgrade_upgrade_settings_submit" value="<?php _e('Update Options &raquo;', 'cmswp-upgrade'); ?>" class="button-primary" /></p>
</td></tr>
</tbody>
</table>
</form>
</div>
</div>

<div class="postbox closed">
<div class="handlediv" title="<?php _e('Click to toggle', 'cmswp-upgrade'); ?>"><br /></div>
<h3><?php _e('Delete Options', 'cmswp-upgrade'); ?></h3>
<div class="inside">
<form method="post" onsubmit="return confirm('<?php _e('Are you sure to delete options? Options you set will be deleted.', 'cmswp-upgrade'); ?>');">
<table class="form-table" style="margin-bottom:5px;">
<tbody>
<tr><td>
<p><input type="submit" name="cmswp_upgrade_delete_options_submit" value="<?php _e('Delete Options &raquo;', 'cmswp-upgrade'); ?>" class="button-primary" /></p>
</td></tr>
</tbody>
</table>
</form>
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
//-->
</script>

</div>
<?php
	}

	function _maybe_update_plugins() {
		$current = get_transient( 'update_plugins' );
		if ( isset( $current->last_checked ) && 43200 > ( time() - $current->last_checked ) )
			return;
		$this->wp_update_plugins();
	}
	
	function wp_update_plugins() {
		global $wp_version;
		$cmswp_options = get_option('cmswp_upgrade_data');
		
		if ( defined('WP_INSTALLING') )
			return false;
	
		if ( !function_exists( 'get_plugins' ) )
			require_once( ABSPATH . 'wp-admin/includes/plugin.php' );

		$plugins = get_plugins();
		$active  = get_option( 'active_plugins' );
		if ( substr($wp_version, 0, 3) >= '3.0' ) 
			$current = get_site_transient( 'update_plugins' );
		else
			$current = get_transient( 'update_plugins' );
		if ( ! is_object($current) )
			$current = new stdClass;

		$new_option = new stdClass;
		$new_option->last_checked = time();
		$timeout = 'load-plugins.php' == current_filter() ? 3600 : 43200;
		$time_not_changed = isset( $current->last_checked ) && $timeout > ( time() - $current->last_checked );

		$plugin_changed = false;
		foreach ( $plugins as $file => $p ) {
			$new_option->checked[ $file ] = $p['Version'];

			if ( !isset( $current->checked[ $file ] ) || strval($current->checked[ $file ]) !== strval($p['Version']) )
				$plugin_changed = true;
		}

		if ( isset ( $current->response ) && is_array( $current->response ) ) {
			foreach ( $current->response as $plugin_file => $update_details ) {
				if ( ! isset($plugins[ $plugin_file ]) ) {
					$plugin_changed = true;
					break;
				}
			}
		}

		if ( $time_not_changed && !$plugin_changed )
			return false;

		$current->last_checked = time();
		if ( substr($wp_version, 0, 3) >= '3.0' ) 
			set_site_transient( 'update_plugins', $current );
		else
			set_transient( 'update_plugins', $current );

		$to_send = (object)compact('plugins', 'active');
		foreach ( $this->plugin_files as $plugin_name => $plugin_file ) :
			if ( $plugin_name != 'cmswp-upgrade' ) :
				if ( file_exists(WP_PLUGIN_DIR.'/'.$plugin_file) ) :
					$to_send->plugins[$plugin_file]['UpgradeCode'] = isset($cmswp_options['global_settings'][$plugin_name]) ? $cmswp_options['global_settings'][$plugin_name] : '';
				endif;
			endif;
		endforeach;
		
		$options = array(
			'timeout' => ( ( defined('DOING_CRON') && DOING_CRON ) ? 30 : 3),
			'body' => array( 'plugins' => serialize( $to_send ) ),
			'user-agent' => 'WordPress/' . $wp_version . '; ' . get_bloginfo( 'url' )
		);
		
		$raw_response = wp_remote_post( 'http://api.wordpress.org/plugins/update-check/1.0/', $options );
		
		$options = array(
			'timeout' => ( ( defined('DOING_CRON') && DOING_CRON ) ? 30 : 3),
			'body' => array( 'plugins' => serialize( $to_send ), 'email' => $cmswp_options['global_settings']['cmswp_email'], 'premium_code' => $cmswp_options['global_settings']['premium_code'] ),
			'user-agent' => 'WordPress/' . $wp_version . '; ' . get_bloginfo( 'url' )
		);
		$raw_response2 = wp_remote_post( 'http://www.cmswp.jp/update-check/', $options );
				
		if ( is_wp_error( $raw_response ) || is_wp_error( $raw_response2 ) )
			return false;
						
		if ( 200 != $raw_response['response']['code'] && 200 != $raw_response2['response']['code'] )
			return false;

		$response = unserialize( $raw_response['body'] );
		$response2 = unserialize( $raw_response2['body'] );

		foreach ( $this->plugin_files as $plugin_name => $plugin_file ) :
			if ( $plugin_name != 'cmswp-upgrade' ) :
				if ( file_exists(WP_PLUGIN_DIR.'/'.$plugin_file) ) :
					if ( isset($response2[$plugin_file]->upgrade_check) ) :
						if ( $response2[$plugin_file]->upgrade_check === false ) :
							$cmswp_options['global_settings'][$plugin_name.'-fail'] = 1;
						endif;
						$cmswp_options['global_settings'][$plugin_name.'-expiration'] = $response2[$plugin_file]->expiration_date;
					endif;
				endif;
			endif;
		endforeach;
		update_option('cmswp_upgrade_data', $cmswp_options);
		
		$cmswp_options = get_option('cmswp_upgrade_data');
		
		if ( is_array($response) && is_array($response2) )
			$response = array_merge($response, $response2);
		elseif ( is_array($response2) )
			$response = response2;
		
		if ( false !== $response )
			$new_option->response = $response;
		else
			$new_option->response = array();

		if ( substr($wp_version, 0, 3) >= '3.0' ) 
			set_site_transient( 'update_plugins', $new_option );
		else
			set_transient( 'update_plugins', $new_option );
	}
	
	function wp_plugin_update_row($file, $plugin_data ) {
		global $wp_version;
		if ( substr($wp_version, 0, 3) >= '3.0' ) 
			$current = get_site_transient( 'update_plugins' );
		else
			$current = get_transient( 'update_plugins' );
		if ( !isset( $current->response[ $file ] ) )
			return false;
			
		$r = $current->response[ $file ];
		
		$plugins_allowedtags = array('a' => array('href' => array(),'title' => array()),'abbr' => array('title' => array()),'acronym' => array('title' => array()),'code' => array(),'em' => array(),'strong' => array());
		$plugin_name = wp_kses( $plugin_data['Name'], $plugins_allowedtags );

		if ( strstr($r->url, 'cmswp.jp') ) :
			echo '<tr class="plugin-update-tr"><td colspan="3" class="plugin-update"><div class="update-message">';
			printf( __('There is a new version of %1$s available. <a href="%2$s" target="_blank">View version %3$s Details</a> or <a href="%4$s">Upgrade automatically</a>.','cmswp-upgrade'), $plugin_name, $r->url, $r->new_version, wp_nonce_url('update.php?action=upgrade-plugin&plugin=' . $file, 'upgrade-plugin_' . $file) );
			do_action( "in_plugin_update_message-$file", $plugin_data, $r );
			echo '</div></td></tr>';
		endif;
		return;
	}
}

$cmswp_upgrade = new cmswp_upgrade();
global $cmswp_upgrade;
?>