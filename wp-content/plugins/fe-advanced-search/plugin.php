<?php
/*
Plugin Name: FE Advanced Search
Plugin URI: http://www.firstelement.jp/fe-advanced-search/
Description: 複合的な条件（カテゴリ/カスタムタクソノミ/タグ/年月/カスタムフィールド/キーワード）による絞り込み検索機能を提供します。複数の検索フォームの作成・管理ができます。ショートコードで任意の投稿・ページに検索フォームを挿入できます。検索結果のソート（並べ替え）ができます。
Author: FirstElement
Author URI: http://www.firstelement.jp/
Version: 1.6.6
*/

/*************************************************
 *	設定・共通関数読み込み
 *************************************************/

/* フック */

add_action( 'admin_menu', 'custom_search_plugin' );
// WPがwhereを呼び出す時に呼び出される
add_filter( 'posts_where', 'search_where_add' );
// WPがorder byを呼び出す時に呼び出される
add_filter( 'posts_orderby', 'custom_sort' );
// ソート用にjoinする
add_filter( 'posts_join', 'join_datas' );
// ソート用にGROUP BYする
add_filter( 'posts_groupby', 'groupby_datas' );
// 出力するする前にソートをする
add_filter( 'posts_request', 'sort_add_field' );

// ショートコード
add_shortcode( 'create-searchform', 'feas_shortcode_fanc' );
// ショートコード
add_shortcode( 'feas-sort-menu', 'feas_shortcode_sort_menu' );
// ショートコード
add_shortcode( 'feas-count-posts', 'feas_shortcode_count_posts' );

add_action( 'wp_head', 'feas_header_style' );

add_action( 'admin_head', 'feas_set_manag_no', 5 );

add_filter( 'init', 'feas_retrun_child' );

add_filter( 'init', 'feas_print_preview' );

// 設定カラムのプリフィクス - 検索フォーム
$cols = array(
	 0 => 'feadvns_disp_number_', 
	 1 => 'feadvns_disp_',
	 2 => 'feadvns_par_cat_',
	 3 => 'feadvns_label_',
	 4 => 'feadvns_kind_',
	 5 => 'feadvns_disp_op_order_',
	 6 => 'feadvns_search_and_',
	 7 => 'feadvns_before_',
	 8 => 'feadvns_after_',
	 9 => 'feadvns_del_',
	10 => 'feadvns_dchi_',
	11 => 'feadvns_exclude_cat_',
	12 => 'feadvns_order_',
	13 => 'feadvns_kwds_target_',
	14 => 'feadvns_nocnt_emptycat_',
	15 => 'feadvns_kwds_yuragi_',
	16 => 'feadvns_cf_range_',
	17 => 'feadvns_cf_unit_',
	18 => 'feadvns_cf_kugiri_',
	19 => 'feadvns_kwds_ajax_',
	20 => 'feadvns_cf_specify_key_',
	21 => 'feadvns_cf_specify_key_switch',
	22 => 'feadvns_cf_range_free_input_',
	23 => 'feadvns_cache_number_'
);

// 設定カラムのプリフィクス - ソートボタン
$cols_order = array(
	 0 => 'feadvns_order_target_',
	 1 => 'feadvns_order_display_',
	 2 => 'feadvns_order_sort_',
	 3 => 'feadvns_order_del_',
	 4 => 'feadvns_order_before_',
	 5 => 'feadvns_order_after_',
	 6 => 'feadvns_order_label_',
	 7 => 'feadvns_order_asc_',
	 8 => 'feadvns_order_desc_',
	 9 => 'feadvns_sort_target_cfkey_',
	10 => 'feadvns_sort_target_cfkey_as_'
);

$cols_transient = array(
	 0 => 'archive',
	 1 => 'taxonomy',
	 2 => 'post_meta',
	 3 => 'tag'
);

// linecountの付属語
$feadvns_max_line = "feadvns_max_line_";

// linecountの付属語（ソート用）
$feadvns_max_line_order = "feadvns_max_line_order";

// search_button_labelの付属語
$feadvns_search_b_label = "search_button_label_";

// フォーム複数個管理する
$manag_no = 0;

// ソート複数個管理する
$manag_order_no = 0;

// 何ページあるかを格納するkey
$feadvns_max_page = "feadvns_max_page";

// 取得するstyleを使うかのkeyを設定
$use_style_key = "feadvns_style_use_";
// 取得するstyleのkeyを設定
$style_body_key = "feadvns_style_body_";

// 検索結果を関連の設定を保存するkey
$feadvns_sort_target = "feadvns_sort_target_";
$feadvns_sort_order = "feadvns_sort_order_";
$feadvns_sort_target_cfkey = "feadvns_sort_target_cfkey_";
$feadvns_sort_target_cfkey_as = "feadvns_sort_target_cfkey_as_";

// ソート時にカスタムフィールドを判別する付属語
$meta_sort_key = "meta_";

// プレビューで「フォーム外観」CSSを反映するかどうかの設定付
$pv_css = 'feadvns_pv_css_';

// 各フォームのタイトルの付属語
$feadvns_search_form_name = 'feadvns_search_form_name_';

// 検索対象のpost_type指定の付属語
$feadvns_search_target = 'feadvns_search_target_';

// 初期設定カテゴリの付属語
$feadvns_default_cat = 'feadvns_default_cat_';

// 検索条件未指定時オプションの付属語
$feadvns_empty_request = 'feadvns_empty_request_';

// ドロップダウン内に件数表示の付属語
$feadvns_show_count = 'feadvns_show_count_';

// Sticky Postsを検索対象に含む設定の付属語
$feadvns_include_sticky = 'feadvns_include_sticky_';

// フリーワード検索のターゲット指定の付属語
$feadvns_kwds_targets = 'feadvns_kwds_targets_';

// フリーワード検索のゆらぎ検索指定の付属語
$feadvns_kwds_yuragi = 'feadvns_kwds_yuragi_';

// キャッシュ
$feas_cache_enable = 'feas_cache_enable';
$feas_cache_time = 'feas_cache_time';

/*************************************************
 *	設定変数(global)
 *************************************************/
function custom_search_plugin()
{
	add_menu_page( '検索', '検索', 'administrator', __FILE__, 'management', 'dashicons-feas', null );
	add_submenu_page( __FILE__, 'ソート設定 &laquo; 検索', 'ソート設定', 'administrator', 'sort_management', 'sort_management' );
	add_submenu_page( __FILE__, 'フォーム外観 &laquo; 検索', 'フォーム外観', 'administrator', 'style_management', 'style_management' );
	add_submenu_page( __FILE__, 'キャッシュ &laquo; 検索', 'キャッシュ', 'administrator', 'cache_management', 'cache_management' );
	add_submenu_page( __FILE__, 'インポート/エクスポート &laquo; 検索', 'インポート/エクスポート', 'administrator', 'backup_management', 'backup_management' );
}

/*************************************************
 *	ファイル読み込み
 *************************************************/
require_once( dirname( __FILE__ ) . '/functions.php' );
require_once( dirname( __FILE__ ) . '/form-controller.php' );
require_once( dirname( __FILE__ ) . '/search-controller.php' );
require_once( dirname( __FILE__ ) . '/result-controller.php' );
require_once( dirname( __FILE__ ) . '/sort-controller.php' );
require_once( dirname( __FILE__ ) . '/backup_management-controller.php' );

/*************************************************
 *	管理トップページ
 *************************************************/
function management()
{
	func_management( 'management' );
}

/*************************************************
 *	ショートコード関数
 *	検索フォーム
 *************************************************/
function feas_shortcode_fanc( $id = 0 )
{
	if( isset( $id['id'] ) == true )
		$id = $id['id'];
	else
		$id = 0;

	return create_searchform( $id, 'shortcode' );
}

/*************************************************
 *	ショートコード関数
 *	該当記事数
 *************************************************/
function feas_shortcode_count_posts( $form_id = 0 )
{
	if ( isset( $form_id['id'] ) ) {
		$form_id = $form_id['id'];
	} else {
		$form_id = 0;
	}

	return feas_count_posts( $form_id, false );
}

/*************************************************
 *	フォームのstyleの設定画面
 *	ソートを表示
 *************************************************/
function feas_shortcode_sort_menu( $id = 0 )
{
	if( isset( $id['id'] ) == true )
		$id = $id['id'];
	else
		$id = 0;
			
	return feas_sort_menu( $id, 'shortcode_f' );
}

/*************************************************
 *	フォームのstyleの設定画面
 *************************************************/
function style_management()
{
	func_management( 'style_management' );
}

/*************************************************
 *	ソート設定画面
 *************************************************/
function sort_management()
{
	func_management( 'sort_management' );
}

/*************************************************
 *	キャッシュ設定画面
 *************************************************/
function cache_management()
{
	func_management( 'cache_management' );
}

/*************************************************
 *	インポート/エクスポート
 *************************************************/
function backup_management()
{
	func_management( 'backup_management' );
}

/*************************************************
 *	apply_css_to_preview()をheaderで実行するためにmanag_noを設定
 *************************************************/
function feas_set_manag_no()
{
	global $feadvns_max_page, $manag_no, $manag_order_no;
	
	$count_form = db_op_get_value( $feadvns_max_page );
	
	if( isset( $_POST['c_form_number'] ) == true )
	{
		if( $_POST['c_form_number'] == 'new' )
			$manag_no = ( $count_form + 1 );
		else
			$manag_no = $_POST['c_form_number'];
	
	}
	else
		$manag_no = 0;
	
	if( isset( $_POST['c_order_number'] ) && $_POST['c_order_number'] != null )
		$manag_order_no = $_POST['c_order_number'];
	else
		$manag_order_no = 0;
}

/*************************************************
 *	style表示用関数
 *************************************************/
function feas_header_style()
{
	global $use_style_key, $style_body_key,$feadvns_max_page;
	
	$get_form_max = intval( db_op_get_value( $feadvns_max_page ) );
	$use_style    = null;
	$get_db_body  = null;
	
	// styleを適用するかのcheck
	for( $i = 0; $i <= $get_form_max; $i++ )
	{
		if( db_op_get_value( $use_style_key . $i ) == 1 )
			$get_db_body .= db_op_get_value( $style_body_key . $i);
	}
	if( $get_db_body !== null )
	{
		$use_style  = '<style type="text/css">' . "\n";
		$use_style .= stripslashes( $get_db_body ) .  "\n";
		$use_style .= '</style>' . "\n";
	}
	echo $use_style;
}
/*************************************************
 *「フォーム外観」CSSをプレビューに適用
 *************************************************/
function feas_apply_css_to_preview( $manag_no = 0 )
{
	global $pv_css, $style_body_key;
	
	$applycss = null;
	
	if( db_op_get_value( $pv_css . $manag_no ) == 'yes' )
	{
		$applycss = db_op_get_value( 'feadvns_style_body' );
		$applycss = db_op_get_value( $style_body_key . $manag_no );
		print '<style type="text/css">' . "\n";
		print stripslashes( $applycss ) . "\n";
		print '</style>' . "\n";
	}
	else
		return null;
}

/*************************************************
 *	ORDER BY時に呼ばれる関数
 *************************************************/
function custom_order_by( $order_by )
{
//	global $wpdb, $feadvns_search_b_label, $manag_no;

	return $ret_order;
}

/********************************************
 *	ajax_filteringで結果を返す
 ********************************************/
function feas_retrun_child( $parent_id = 0, $get_manag_no = 0, $form_no = 0 )
{
	global $wpdb, $manag_no, $feadvns_search_target, $feadvns_include_sticky, $feadvns_search_b_label, $cols;
	
	$exids = null;
	$taxonomy = false;
	$retrun_list = array();
	$target_sp = $excat = $sp = '';
	
	if( isset( $_GET['parent'] ) && ( $_GET['parent'] != null ) )
	{
		$parent_id = intval( $_GET['parent'] );
		if( isset( $_GET['manag_no'] ) )
			$manag_no = intval( $_GET['manag_no'] );
		if( isset( $_GET['form_no'] ) )
			$form_no = intval( $_GET['form_no'] );
		
		// 保存データ取得
		$get_data = get_db_save_data();
		$get_data = $get_data[$form_no];
		
		// 除外カテゴリIDが設定されている場合
		if( isset( $get_data[$cols[11]] ) )
			$excat = $get_data[$cols[11]];	
		
		if( !empty( $excat ) )
		{
			$excat = str_replace( '，', ',', trim( $excat ) );
			$excat = str_replace( '、', ',', $excat );
			$excat_array = explode( ',', $excat );
			
			for( $i = 0, $cnt = count( $excat_array ); $i < $cnt ; $i++ )
			{
				if( $exids != null )
					$exids .= ',';
				$exids .= esc_sql( $excat_array[$i] );
			}
		}
		
		// 固定記事(Sticky Posts)を検索対象から省くor省かない
		$get_cond = $stickey = '';
		$get_cond = db_op_get_value( $feadvns_include_sticky . $manag_no );
		// Sticky Postsを含まない場合、SQL文に挿入するためのArray→カンマ区切りの書式準備
		if( $get_cond != 'yes' )
		{
			$sp = get_option( 'sticky_posts' );
			if( $sp != array() )
			{
				for( $i = 0; $cnt = count( $sp ), $i < $cnt; $i++ )
				{
					$stickey .= esc_sql( $sp[$i] );
					if( $i + 1 < $cnt )
						$stickey .= ',';
				}
			}
		}
		
		// 検索対象のpost_typeを取得
		$get_cond = $target_pt = '';
		$get_cond = db_op_get_value( $feadvns_search_target . $manag_no );
		if( $get_cond )
		{
			$get_cond = explode( ',', $get_cond );
			for( $i = 0; $cnt = count( $get_cond ), $i < $cnt; $i++ )
			{
				$target_pt .= "'" . esc_sql( $get_cond[$i] ) . "'";
				if( $i + 1 < $cnt )
					$target_pt .= ',';
			}
		}
		
		if( isset( $get_data[$cols[5]] ) )
		{
			switch( $get_data[$cols[5]] )
			{
				case 0:
					$option_order = ' ORDER BY t.term_id ASC ';
					break;
				case 1:
					$option_order = ' ORDER BY t.term_id DESC ';
					break;
				case 2:
					$option_order = ' ORDER BY t.name ASC ';
					break;
				case 3:
					$option_order = ' ORDER BY t.name DESC ';
					break;
				case 4:
					$option_order = ' ORDER BY t.slug ASC ';
					break;
				case 5:
					$option_order = ' ORDER BY t.slug DESC ';
					break;
				case 6:
					$option_order = ' ORDER BY t.term_order ASC ';
					break;
				default:
					$option_order = ' ORDER BY t.term_id ASC ';
					break;
			}
		}

		$sql2  = " SELECT t.term_id, t.name, count( DISTINCT object_id ) AS cnt FROM $wpdb->terms AS t";
		$sql2 .= " LEFT JOIN $wpdb->term_taxonomy AS tt ON t.term_id = tt.term_id";
		$sql2 .= " LEFT JOIN $wpdb->term_relationships AS tr ON tt.term_taxonomy_id = tr.term_taxonomy_id";
		$sql2 .= " LEFT JOIN $wpdb->posts ON $wpdb->posts.ID = tr.object_id";
		$sql2 .= " WHERE $wpdb->posts.post_type IN( $target_pt )";
		$sql2 .= " AND tt.parent = $parent_id AND tt.count != 0";
		if( $exids )
			$sql2 .= " AND t.term_id NOT IN ( $exids )";
		if( $taxonomy )
			$sql2 .= " AND tt.taxonomy = '$taxonomy'";
		$sql2 .= " AND $wpdb->posts.post_status = 'publish'";
		if( $stickey != '' )
			$sql2 .= " AND tr.object_id NOT IN ( $stickey )";
		$sql2 .= " GROUP BY t.term_id";
		$sql2 .= $option_order;
		$get_cats2 = $wpdb->get_results( $sql2 );
				
		foreach( $get_cats2 as $key => $val )
		{
			$retrun_list[] = array( 'name' => $val->name , 'id' => $val->term_id , 'count' => $val->cnt );
		}
		if( 1 > count( $retrun_list ) )
			$retrun_list = false;

		@header( 'Content-Type: application/json; charset=' . get_bloginfo( 'charset' ) );
		echo json_encode( $retrun_list );
		exit;
	}
}

/********************************************
 *	自動アップデート
 ********************************************/
global $wp_version;

if( $wp_version >= '3' )
{
	define( 'FEAS_ALT_API', 'http://downloads.firstelement.jp/plugin-api/' );
	
	// Hook into the plugin update check
	add_filter( 'pre_set_site_transient_update_plugins', 'feas_altapi_check' );
	// Hook into the plugin details screen
	add_filter( 'plugins_api', 'feas_altapi_information', 10, 3 );
	
	// For testing purpose, the site transient will be reset on each page load
	//add_action( 'init', 'feas_altapi_delete_transient' ); //デバッグ用
}

function feas_altapi_delete_transient()
{
	delete_site_transient( 'update_plugins' );
}

function feas_altapi_check( $transient )
{
	// Check if the transient contains the 'checked' information
	// If no, just return its value without hacking it
	if( empty( $transient->checked ) ){
		return $transient;
	}
	
	// The transient contains the 'checked' information
	// Now append to it information form your own API
	$plugin_slug = plugin_basename( __FILE__ );
	
	// POST data to send to your API
	$args = array(
		'action'      => 'update-check',
		'plugin_name' => $plugin_slug,
		'version'     => $transient->checked[$plugin_slug],
	);
	
	// Send request checking for an update
	$response = feas_altapi_request( $args );
	
	// If response is false, don't alter the transient
	if( false !== $response )
		$transient->response[$plugin_slug] = $response;
	
	return $transient;
}

// Send a request to the alternative API, return an object
function feas_altapi_request( $args )
{
	// Send request
	$request = wp_remote_post( FEAS_ALT_API, array( 'body' => $args ) );
	
	// Make sure the request was successful
	if( is_wp_error( $request ) or wp_remote_retrieve_response_code( $request ) != 200 )
		// Request failed
		return false;
	
	// Read server response, which should be an object
	$response = unserialize( wp_remote_retrieve_body( $request ) );
	if( is_object( $response ) )
	{
		$new_varsion = $response->new_version;
		$naw_varsion = feas_plugin_get_version();
		
		if( $naw_varsion < $new_varsion )
			return $response;
		else
			return false;
			
	} else {
		
		// Unexpected response
		return false;
	}
}

function feas_altapi_information( $false, $action, $args )
{
	$plugin_slug = plugin_basename( __FILE__ );
	
	// Check if this plugins API is about this plugin
	if( ( isset( $args->slug ) ) && ( $args->slug != $plugin_slug ) )
		return false;
	
	// POST data to send to your API
	$args = array(
		'action'      => 'plugin_information',
		'plugin_name' => $plugin_slug,
		//'version' => $transient->checked[$plugin_slug],
	);
	
	// Send request for detailed information
	$response = feas_altapi_request( $args );
	
	// Send request checking for information
	$request = wp_remote_post( FEAS_ALT_API, array( 'body' => $args ) );
	
	return $response;
}

/**
 * Returns current plugin version.
 * プラグインのバージョンを返します
 *
 * @return string Plugin version
 */
function feas_plugin_get_version()
{
	if ( ! function_exists( 'get_plugins' ) )
		require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
	$plugin_folder = get_plugins( '/' . plugin_basename( dirname( __FILE__ ) ) );
	$plugin_file   = basename( ( __FILE__ ) );
	
	return $plugin_folder[$plugin_file]['Version'];
}

/**
 * make preview of iframe.
 * iframeでのプレビューを作る
 *
 * @echo html
 */
function feas_print_preview()
{
	if( !isset( $_GET['feas_pv'] ) ){ return; }
	
	$pv_mng_no = intval( $_GET['feas_mng_no'] );
	$pv_type   = $_GET['feas_pv_type'];
	
	header( "Content-Type: text/html; charset=UTF-8" );
	header( "Expires: Thu, 01 Dec 1994 16:00:00 GMT" );
	header( "Last-Modified: ". gmdate("D, d M Y H:i:s" ). " GMT" );
	header( "Cache-Control: no-cache, must-revalidate" );
	header( "Cache-Control: post-check=0, pre-check=0", false );
	header( "Pragma: no-cache" );
	?>
	<html>
	<head>
		<meta content="text/html; charset=UTF-8" http-equiv="Content-Type">
		<script type="text/javascript" src="<?php bloginfo( 'wpurl' ); ?>/wp-admin/load-scripts.php?c=1&amp;load%5B%5D=jquery-core,jquery-migrate,utils"></script>
		<script type="text/javascript" src="<?php echo WP_PLUGIN_URL . '/' . str_replace( basename(__FILE__), "", plugin_basename(__FILE__) ) . 'ajax_filtering.js'; ?>"></script>
		<style>
			body{margin:0;padding:0;}
			html{overflow-y: scroll;}
		</style>
		<?php feas_apply_css_to_preview( $pv_mng_no ); ?>
	</head>
	<body><div id="feas-<?php echo $pv_mng_no; ?>">
			<div id="feas-form-<?php echo $pv_mng_no; ?>">
				<div id="feas-searchform-<?php echo $pv_mng_no;?>">
					<?php
						if( 'search' == $pv_type )
							create_searchform( $pv_mng_no );
						else if( 'sort' == $pv_type )
							feas_sort_menu( $pv_mng_no );
					?>
				</div>
			</div>
		</div>
	</body></html>
<?php
	exit;
}

function feas_admin_style(){
    wp_enqueue_style( 'feas_admin_style', plugin_dir_url( __FILE__ ) . '/admin_style.css' );
}
add_action( 'admin_enqueue_scripts', 'feas_admin_style' );
?>