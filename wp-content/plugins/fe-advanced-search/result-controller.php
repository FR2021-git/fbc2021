<?php
//////////////////////////////////////////////
//  検索文字列を出力（テンプレート表示用）
//////////////////////////////////////////////
function search_result( $sterm = null , $num = 0 )
{
	$result = array( 0 => null, 1 => null );
	
	if( isset( $_GET['csp'] ) && ( $_GET['csp'] == "search_add" ) )
	{
	 	//  全ての検索条件（カンマ区切り）
	 	if( isset( $_POST['search_result_data']) && ( $_POST['search_result_data'] != null ) )
			$result[0] = esc_html( $_POST['search_result_data'] );
		// キーワード検索時、inputフィールドに戻すため（Ktai Style は$_GET/POSTの値をinputに返せない？ため） 	
		if( isset( $_POST['kwds_result_data_' . $num ] ) && ( $_POST['kwds_result_data_' . $num ] != null ) )
			$result[1] = esc_html( $_POST['kwds_result_data_' . $num] );
			
		if( $sterm == "keywords" )
			return $result[1];
		else
			print $result[0];
	}
	else 
		return;
}

//////////////////////////////////////////////
//  該当件数を取得
//////////////////////////////////////////////
function feas_count_posts( $form_id = 0, $print = true )
{
	global $wp_query, $feadvns_search_target, $feadvns_include_sticky, $feadvns_default_cat, $wpdb;
	
	$cnt_posts = 0;
	
	// 検索実行時
	if ( is_search() ) {
	
		$cnt_posts = $wp_query->found_posts;
	
	// 検索実行前
	} else {
		
		// 検索対象の投稿タイプを取得
		$target_pt = '';
		$search_target = db_op_get_value( $feadvns_search_target . $form_id );
		if ( $search_target ) {
			$search_target = explode( ',', $search_target );
			for ( $i = 0; $cnt = count( $search_target ), $i < $cnt; $i++ ) {
				$target_pt .= "'" . esc_sql( $search_target[$i] ) . "'";
				if ( $i + 1 < $cnt ) {
					$target_pt .= ',';
				}
			}
		} else {
			$target_pt = "'post'";
		}
		
		// 固定記事を検索対象から省く
		$exclude_sticky = '';
		$target_sp = db_op_get_value( $feadvns_include_sticky . $form_id );
		if ( $target_sp != 'yes' ) {
			$sticky = get_option( 'sticky_posts' );
			if ( !empty( $sticky ) ) {
				for ( $i = 0; $cnt = count( $sticky ), $i < $cnt; $i++ ) {
					$exclude_sticky .= $sticky[$i];
					if ( $i + 1 < $cnt )
						$exclude_sticky .= ', ';
				}
			}
		}
	
		// 固定タクソノミ/タームを取得
		$fixed_tax = db_op_get_value( $feadvns_default_cat . $form_id );
		
		$sql = "SELECT count( DISTINCT p.ID ) AS cnt 
		FROM {$wpdb->posts} AS p 
		LEFT JOIN {$wpdb->term_relationships} AS tr ON p.ID = tr.object_id 
		LEFT JOIN {$wpdb->term_taxonomy} AS tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
	    LEFT JOIN {$wpdb->terms} AS t ON tt.term_id = t.term_id
		WHERE p.post_type IN ( " . $target_pt . " ) ";
		if ( !empty( $exclude_sticky ) ) {
			$sql .= " AND p.ID NOT IN ( " . esc_sql( $exclude_sticky ) . " ) ";
		}
		if ( !empty( $fixed_tax ) ) {
			$sql .= " AND t.term_id = " . esc_sql( $fixed_tax );
		}
		$sql .= " AND p.post_status = 'publish' ";
		
		// カウント数取得
		$result = $wpdb->get_results( $sql );
		
		if( $result ) {
			$cnt_posts = (int) $result[0]->cnt;
		}
	}
	
	// 画面出力
	if ( $print ) {
		print $cnt_posts;
	} else {
		return $cnt_posts;
	}
}

//////////////////////////////////////////////
//  WP標準の検索クエリに流し込む
//////////////////////////////////////////////
function feas_merge_wp_search_query ( $search )
{
	if( isset( $_GET['csp'] ) && ( $_GET['csp'] == "search_add" ) )
	{
	 	// 全ての検索条件（カンマ区切り）
	 	if( isset( $_POST['search_result_data']) && ( $_POST['search_result_data'] != null ) )
	 	{
			$search = $_POST['search_result_data'];
		}
	}
	
	return $search;
}
add_filter( 'get_search_query', 'feas_merge_wp_search_query');

//////////////////////////////////////////////
//  検索文字列を出力（array） ハイライト表示などに
//////////////////////////////////////////////
function search_result_array( $sterm = null , $num = 0 )
{
	if( isset( $_GET['csp'] ) && ( $_GET['csp'] == "search_add" ) )
	{
	// 全ての検索条件（カンマ区切り）
		switch( $sterm )
		{
			case 'all':
				if( isset( $_POST['search_result_data'] ) && ( $_POST['search_result_data'] != null ) )
				{
					$result = $_POST['search_result_data'];
					$result_array = explode( ',', $result );
				}
				break;
			case 'keys': // 何かに使えるかもしれない・・・
				if( isset( $_POST['keys_result_data_' . $num] ) )
					$result_array = $_POST['keys_result_data_'. $num];
				else
					$result_array = null;
				break;
			// ハイライト表示のキーワードなどに
			default:
				if( isset( $_POST['kwds_result_data_all'] ) && ( $_POST['kwds_result_data_all'] != null ) )
					$result_array = $_POST['kwds_result_data_all'];
				break;
		}
		if( isset( $result_array ) )
			return esc_html( $result_array );
		else
			return false;
	}
	else 
		return false;
}

?>