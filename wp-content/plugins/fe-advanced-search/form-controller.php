<?php
/***********************************************/
/*フォーム作成関係
/***********************************************/

/////////////////////////////////////////////////
//フォームを作成
/////////////////////////////////////////////////
function create_searchform( $id = null, $shortcode_f = null )
{
	global $wpdb, $cols, $feadvns_max_line, $manag_no, $feadvns_search_b_label, $use_style_key, $style_body_key, $feadvns_search_target;
	
	// ajax_filtering用スクリプト
	wp_enqueue_script( 'ajax_filtering', plugins_url() . '/' . str_replace( basename( __FILE__ ), "", plugin_basename( __FILE__ ) ) . 'ajax_filtering.js', array( 'jquery' ), '0.3' );

	if( $id != null && is_numeric( $id ) )
		$manag_no = $id;
	else
		$manag_no = 0;

	if ( is_ssl() ) {
		$action_url = home_url( "/", "https" );
	} else {
		$action_url = home_url( "/" );
	}
	$output_form = "<form id='feas-searchform-" . $manag_no . "' action='" . $action_url . "' method='get' >\n";

	// 保存データ取得
	$get_data = get_db_save_data();
	// 取得データを並び替え
	$get_data = sort_db_save_data( $get_data );
	// 表示した場合チェックを入れる
	$ele_disp = null;
	// 対象投稿タイプをセットしていないとフォームを作らない
	$target_pt = db_op_get_value( $feadvns_search_target . $manag_no );
	if( isset( $target_pt ) )
	{
		for( $i_gd = 0, $cnt_gd = count( $get_data ); $i_gd < $cnt_gd; $i_gd++ )
		{
			// 表示するかしないか取得
			if( isset( $get_data[$i_gd][$cols[1]] ) && $get_data[$i_gd][$cols[1]] != 1 )
			{
				// 前に挿入を取得
				if( isset( $get_data[$i_gd][$cols[7]] ) && $get_data[$i_gd][$cols[7]] != null )
					$output_form .= str_replace( '\\', '', $get_data[$i_gd][$cols[7]] ) . "\n";
	
				// ラベル取得
				if( isset( $get_data[$i_gd][$cols[3]]) && $get_data[$i_gd][$cols[3]] != null )
					$output_form .= str_replace( '\\', '', $get_data[$i_gd][$cols[3]] ) ."\n";
	
				// エレメント取得
				$output_form .= create_element( $get_data[$i_gd], $i_gd );
	
				// 後に挿入を取得
				if( isset( $get_data[$i_gd][$cols[8]] ) && $get_data[$i_gd][$cols[8]] != null )
					$output_form .= str_replace( '\\', '', $get_data[$i_gd][$cols[8]] ) . "\n";
	
				// 表示した場合は
				$ele_disp = "disp";
			}
		}
	}

	if( $ele_disp != null )
	{
		// 検索ボタンのラベル取得
		$s_b_label = "検　索";
		$get_data = db_op_get_value( $feadvns_search_b_label . $manag_no );
		if( isset( $get_data ) && $get_data != null )
			$s_b_label = $get_data;

		// 前に挿入を取得
		$before_btn   = db_op_get_value( $feadvns_search_b_label . $manag_no . "_before" );
		$output_form .= str_replace( '\\', '', $before_btn ) . "\n";
		$output_form .= "<input type='submit' name='searchbutton' id='feas-submit-button-" . esc_attr( $manag_no ) . "' class='feas-submit-button' value='" . esc_attr( $s_b_label ) . "' />\n";
		// 後に挿入を取得
		$after_btn    = db_op_get_value( $feadvns_search_b_label . $manag_no . "_after" );
		$output_form .= str_replace( '\\', '', $after_btn ) . "\n";
	}

	$output_form .= "<input type='hidden' name='csp' value='search_add' />\n";
	$output_form .= "<input type='hidden' name='" . esc_attr( $feadvns_max_line . $manag_no ) . "' value='" . esc_attr( db_op_get_value( $feadvns_max_line . $manag_no ) ) . "' />\n";

	if( isset( $chi_manag_no ) && ( $chi_manag_no != 0 ) )
		$output_form .= "<input type='hidden' name='fe_form_no' value='" . esc_attr( $chi_manag_no ) . "' />\n";
	else
		$output_form .= "<input type='hidden' name='fe_form_no' value='" . esc_attr( $manag_no ) . "' />\n";

	$output_form .= "</form>\n";
	
	if( $shortcode_f == null )
		echo $output_form;
	else
		return $output_form;
}
/////////////////////////////////////////////////
//キャッシュ判定
/////////////////////////////////////////////////
function feas_cache_judgment( $location = false,$number )
{
	global $feas_cache_enable, $cols, $manag_no, $cols_transient;
	if( !in_array( $location, $cols_transient ) ){ return false; }
	if ( db_op_get_value( $feas_cache_enable ) == 'enable' )
	{
		if( false === ( $output_form = get_transient( $cols[23] . $manag_no . '_' . $location . '_' . $number ) ) )
			return false;
			
		return $output_form;
	}
	else
		return false;
}
/////////////////////////////////////////////////
//キャッシュ作成
/////////////////////////////////////////////////
function feas_cache_create( $location = false, $number, $output_form )
{
	global $feas_cache_enable, $cols, $manag_no, $cols_transient, $feas_cache_time;
	if( db_op_get_value( $feas_cache_enable ) != 'enable' ){ return ; }
	if( isset( $output_form ) )
		set_transient( $cols[23] . $manag_no . '_' . $location . '_' . $number, $output_form, intval( db_op_get_value( $feas_cache_time ) ) );
	return;
}
/////////////////////////////////////////////////
//DBから保存データを取得
/////////////////////////////////////////////////
function get_db_save_data()
{
	global $wpdb, $cols, $feadvns_max_line, $manag_no;

	$line_cnt = db_op_get_value( $feadvns_max_line . $manag_no );

	if( $line_cnt == 0 )
		$line_cnt = $line_cnt + 1;

	$cnt_cols = count( $cols );

	$get_data = array();
	for( $i_line = 0; $i_line < $line_cnt; $i_line++ )
	{
		for( $i_col = 0; $i_col < $cnt_cols; $i_col++ )
		{
			$s_key = $cols[$i_col] . $manag_no . "_" . $i_line;
			$get_data[$i_line][$cols[$i_col]] = db_op_get_value( $s_key );
		}
	}

	return $get_data;
}

/////////////////////////////////////////////////
//保存データを並び替え
/////////////////////////////////////////////////
function sort_db_save_data( $get_data = array() )
{

	//ソート
	/*$order_date = array();
	foreach ($get_data as $v){
		$order_date[] = $v[$cols[$i_col]];
	}
	array_multisort($order_date, SORT_ASC, $get_data);*/

	return $get_data;
}

/////////////////////////////////////////////////
//検索フォームを作成
/////////////////////////////////////////////////
function create_element( $get_data = array(), $number = 0 )
{
	global $wpdb, $cols;

	// 表示しないの場合は処理しない
	if( $get_data[$cols[1]] == 1 )
		return null;

	// 形式 - なし の場合も処理しない
	if( $get_data[$cols[4]] == 0 )
		return null;

	// 並び順を取得する
	$option_order = null;

	//エレメント作成
	if( $get_data[$cols[2]] == "archive" )
		$ret_ele = create_archive_element( $get_data, $number );
	else 
	{
		if( mb_substr( $get_data[$cols[2]], 0, 5 ) == "meta_" )
			// $ret_ele =create_meta_element(mb_substr($get_data[$cols[2]], 5, mb_strlen($get_data[$cols[2]])) , $number);
			$ret_ele = create_meta_element( $get_data, $number );
		else if( $get_data[$cols[2]] == "sel_tag" )
			$ret_ele = create_tag_element( $get_data, $number );
		else
			$ret_ele = create_category_element( $get_data, $number );
	}
	return $ret_ele;
}

/////////////////////////////////////////////////
//アーカイブ（archive）のエレメント作成
/////////////////////////////////////////////////
function create_archive_element( $get_data = array(), $number )
{
	global $wpdb, $cols, $manag_no, $feadvns_search_target, $feadvns_show_count, $feadvns_include_sticky;
	
	$ret_ele = $showcnt = $sp = null;

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
	
	// 件数を表示するorしない
	$showcnt = db_op_get_value( $feadvns_show_count . $manag_no );

	// キャッシュ
	if ( false === ( $get_archive = feas_cache_judgment( 'archive', $number ) ) )
	{
		$sql  = " SELECT DISTINCT YEAR(post_date) AS `year`, MONTH(post_date) AS `month` , count( DISTINCT ID ) AS cnt ,CONCAT(YEAR(post_date),LPAD(MONTH(post_date), 2, '0')) AS ym FROM $wpdb->posts AS p ";
		$sql .= " WHERE p.post_type IN( $target_pt ) AND p.post_status = 'publish' ";
		if( $stickey != '' )
			$sql .= " AND p.ID NOT IN ( $stickey ) ";
		$sql .= " GROUP BY ym ";
		
		if( isset( $get_data[$cols[5]] ) )
		{
			switch( $get_data[$cols[5]] )
			{
				case 0:
					$sql .= " ORDER BY ym  ASC "; // ymは年と月を繋いだ値。例：201203
					break;
				case 1:
					$sql .= " ORDER BY ym  DESC ";
					break;
				default:
					$sql .= " ORDER BY ym ASC";
					break;
			}
		}
		$get_archive = $wpdb->get_results( $sql );
	
		feas_cache_create( 'archive', $number, $get_archive );
	}

	switch( $get_data[$cols[4]] )
	{
		case 1:
			$ret_ele  = "<select name='search_element_" . esc_attr( $number ) . "' id='feas_" . esc_attr( $manag_no ) . "_" . esc_attr( $number ) . "'>\n";

			$ret_ele .= "<option id='feas_" . esc_attr( $manag_no ) . "_" . esc_attr( $number ) . "_none' value=''>---未指定---</option>\n";
			for( $i_arc = 0, $cnt_arc = count( $get_archive ); $i_arc < $cnt_arc; $i_arc++ )
			{
				$selected = '';
				if( isset( $_GET['search_element_' . $number] ) )
				{
					if( $_GET['search_element_' . $number] == $get_archive[$i_arc]->year . $get_archive[$i_arc]->month )
						$selected = ' selected="selected" ';
				}
				$arc_cnt = '';
				if( $showcnt == "yes" )
					$arc_cnt = " (" . $get_archive[$i_arc]->cnt. ") ";
					
				$disp_archive = $get_archive[$i_arc]->year . $get_archive[$i_arc]->month;
				$ret_ele .= "<option id='feas_" . esc_attr( $manag_no ) . "_" . esc_attr( $number ) . "_" . esc_attr( $i_arc ) . "' value='" . esc_attr( $get_archive[$i_arc]->year . $get_archive[$i_arc]->month ) . "'" . $selected . " >" . esc_html( $get_archive[$i_arc]->year . "年" . $get_archive[$i_arc]->month . "月" . $arc_cnt ) . "</option>\n";
			}
			$ret_ele .= "</select>\n";
			break;
			
		case 2:
			$ret_ele  = "<select name='search_element_" . esc_attr( $number ) . "[]' id='feas_" . esc_attr( $manag_no ) . "_" . esc_attr( $number ) . "' size='5' multiple='multiple'>\n";

			$ret_ele .= "<option id='feas_" . esc_attr( $manag_no ) . "_" . esc_attr( $number ) . "_none' value=''>---未指定---</option>";
			for( $i_arc = 0, $cnt_arc = count( $get_archive ); $i_arc < $cnt_arc; $i_arc++ )
			{
				$selected = '';
				if( isset( $_GET["search_element_" . $number] ) )
				{
					for( $i_lists = 0, $cnt_lists = count( $_GET["search_element_" . $number] ); $i_lists < $cnt_lists; $i_lists++ )
					{
						if( isset( $_GET["search_element_" . $number][$i_lists] ) )
						{
							if( $_GET["search_element_" . $number][$i_lists] == $get_archive[$i_arc]->year . $get_archive[$i_arc]->month )
								$selected = ' selected="selected"';
						}
					}
				}
				
				$arc_cnt = '';
				if( $showcnt == "yes" )
					$arc_cnt = " (" . $get_archive[$i_arc]->cnt. ") ";
					
				$ret_ele .= "<option id='feas_" . esc_attr( $manag_no ) . "_" . esc_attr( $number ) . "_" . esc_attr( $i_arc ) . "' value='" . esc_attr( $get_archive[$i_arc]->year . $get_archive[$i_arc]->month ) . "'" . $selected . " >" . esc_html( $get_archive[$i_arc]->year . "年" . $get_archive[$i_arc]->month . "月" . $arc_cnt ) . "</option>\n";
			}
	
			$ret_ele .= "</select>\n";
			break;
			
		case 3:
			for( $i_arc = 0, $cnt_arc = count( $get_archive ); $i_arc < $cnt_arc; $i_arc++ )
			{
				$checked = '';
				if( isset( $_GET['search_element_' . $number . "_" . $i_arc] ) )
				{
					if( $_GET['search_element_' . $number . "_" . $i_arc] == $get_archive[$i_arc]->year . $get_archive[$i_arc]->month )
						$checked = ' checked="checked"';
				}

				$arc_cnt = '';
				if( $showcnt == "yes" )
					$arc_cnt = " (" . $get_archive[$i_arc]->cnt . ") ";
					
				$ret_ele .= "<label id='feas_" . esc_attr( $manag_no ) . "_" . esc_attr( $number ) . "_" . esc_attr( $i_arc ) . "'><input type='checkbox' name='search_element_" . esc_attr( $number ) . "_" . esc_attr( $i_arc ) . "' value='" . esc_attr( $get_archive[$i_arc]->year . $get_archive[$i_arc]->month ) . "' " . $checked . " />" . esc_html( $get_archive[$i_arc]->year . "年" . $get_archive[$i_arc]->month . "月" . $arc_cnt ) . "</label>\n";
			}
			break;
			
		case 4:
			for( $i_arc = 0, $cnt_arc = count( $get_archive ); $i_arc < $cnt_arc; $i_arc++ )
			{
				$checked = '';
				if( isset( $_GET['search_element_' .$number] ) )
				{
					if( $_GET['search_element_' . $number] == $get_archive[$i_arc]->year . $get_archive[$i_arc]->month )
						$checked = ' checked="checked"';
				}

				$arc_cnt = '';
				if( $showcnt == "yes" )
					$arc_cnt = " (" . $get_archive[$i_arc]->cnt . ") ";
					
				$ret_ele .= "<label id='feas_" . esc_attr( $manag_no ) . "_" . esc_attr( $number ) . "_" . esc_attr( $i_arc ) . "'><input type='radio' name='search_element_" . esc_attr( $number ) . "' value='" . esc_attr( $get_archive[$i_arc]->year . $get_archive[$i_arc]->month ) . "' " . $checked . " />" . esc_html( $get_archive[$i_arc]->year . "年" . $get_archive[$i_arc]->month . "月" . $arc_cnt ) . "</label>";
			}
			break;
			
		case 5:
			$ret_ele .= "<input type='text' name='s_keyword_" . esc_attr( $number ) . "' id='feas_" . esc_attr( $manag_no ) . "_" . esc_attr( $number ) . "' value='" . search_result( "keywords", $number ) . "' />";
			if( $get_data[$cols[21]] === 'no' )
				$ret_ele .= create_specifies_the_key_element( $get_data, $number );
			break;
		
		default:
			$ret_ele .= "<input type='text' name='s_keyword_" . esc_attr( $number ) . "' id='feas_" . esc_attr( $manag_no ) . "_" . esc_attr( $number ) . "' value='" . search_result( "keywords", $number ) . "' />";
			break;
	}

	return $ret_ele;
}

/////////////////////////////////////////////////
// タクソノミー（taxonomy）のエレメント作成
/////////////////////////////////////////////////
function create_category_element( $get_data = array(), $number )
{
	global $wpdb, $cols, $manag_no, $feadvns_search_target, $feadvns_show_count, $feadvns_include_sticky, $form_count, $total_cnt, $wp_version, $cols_transient;

	// 初期化
	$nocnt = false;
	$sql = $excat = $exids = $exid = $target_pt = $target_sp = $showcnt = $ret_ele = $to = $taxonomy = null;
	$excat_array = $sticky = $q_term_id = $sp = array();
	
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
	
	if( $target_pt )
	{
		// 検索対象のPost Typ以外の記事は件数から除外するため、ID一覧を取得
		$sql  = " SELECT ID FROM $wpdb->posts AS p ";
		$sql .= " WHERE p.post_type NOT IN ( $target_pt ) "; 
		$sp   = $wpdb->get_col( $sql ); // Array
	}

	// 固定記事(Sticky Posts)を検索対象から省く設定の場合、カウントに含めない
	$target_sp = db_op_get_value( $feadvns_include_sticky . $manag_no );
		
	// Sticky Postsを検索対象に含まない場合、該当記事IDを除外
	if( $target_sp != 'yes' )
	{
		$sticky = get_option( 'sticky_posts' );
		if( $sticky != array() )
			$sp = array_merge( $sp, $sticky ); // Post Typeの除外IDにマージ
	}
	
	// カテゴリ毎の件数を表示する/しないの設定を取得
	$showcnt = db_op_get_value( $feadvns_show_count . $manag_no );
	
/*
	if( $get_data[$cols[2]] != "par_cat" && ! is_numeric( $get_data[$cols[2]] ) && ($wp_version >= '3.0')){

		$args = array( 'public' => true, '_builtin' => false );
		$ctaxonomy = get_taxonomies( $args ,'objects' );

		foreach( $ctaxonomy as $ctax ){ 
			if( $get_data[$cols[2]] === $ctax->name ){
				$get_data[$cols[2]] = 0; // 親タームIDとして「0」を代入
				$taxonomy = $ctax->name;
			}
		}
	} else {
		if( $get_data[$cols[2]] == "par_cat" ){
			$get_data[$cols[2]] = 0; // 親タームIDとして「0」を代入
			$taxonomy = 'category';
		}
	}
*/
	// タクソノミのトップ階層の場合
	if( substr( $get_data[$cols[2]], 0, 4 ) == "par_" )
	{
		$taxonomy = substr( $get_data[$cols[2]], 4, strlen( $get_data[$cols[2]] ) - 4 ); // タクソノミ名を指定
		$get_data[$cols[2]] = 0; // parentとして0を代入
	}
	
	// 除外タームIDが設定されている場合
	if( isset( $get_data[$cols[11]] ) )
	{
		$excat = $get_data[$cols[11]];
		if( $excat )
		{
			$excat = str_replace( "，", ",", trim( $excat ) );
			$excat = str_replace( "、", ",", $excat );
			$excat_array = explode( ",", $excat );
	
			for( $i = 0, $cnt = count( $excat_array ); $i < $cnt ; $i++ )
			{
				$exids .= esc_sql( $excat_array[$i] );
				if( $i + 1 < $cnt )
					$exids .= ",";
			}
		}
	}
	
	// 0件のタームを表示しない設定の場合
	if( isset( $get_data[$cols[14]] ) && $get_data[$cols[14]] == 'no' )
		$nocnt = true;
	
	if( isset( $get_data[$cols[5]] ) )
	{
		switch ( $get_data[$cols[5]] )
		{
			case 0:
				$to = " t.term_id ASC ";
				break;
			case 1:
				$to = " t.term_id DESC ";
				break;
			case 2:
				$to = " t.name ASC ";
				break;
			case 3:
				$to = " t.name DESC ";
				break;
			case 4:
				$to = " t.slug ASC ";
				break;
			case 5:
				$to = " t.slug DESC ";
				break;
			case 6:
				$to = " t.term_order ASC ";
				break;
			case 7:
				$to = " RAND() ";
				break;
			default:
				$to = " t.term_id ASC ";
				break;
		}
	}
	else 
		$to = " t.term_id ASC ";
	
	if ( false === ( $output_form = feas_cache_judgment( $cols_transient[1], $number ) ) )
	{	
		$get_cats_cnt = null;
		$get_cnt = array();
		
		// カウント非表示
		if( is_null( $showcnt ) && $nocnt )
		{
			$sp = '';
			if( $sticky != array() )
			{
				for( $i = 0; $cnt = count( $sticky ), $i < $cnt; $i++ )
				{
					$sp .= esc_sql( $sticky[$i] );
					if( $i + 1 < $cnt )
						$sp .= ',';
				}
			}
			
			// カテゴリーを取得する
			$sql  = " SELECT t.term_id, t.name, count( DISTINCT object_id ) AS cnt FROM $wpdb->terms AS t";
			$sql .= " LEFT JOIN $wpdb->term_taxonomy AS tt ON t.term_id = tt.term_id";
			$sql .= " LEFT JOIN $wpdb->term_relationships AS tr ON tt.term_taxonomy_id = tr.term_taxonomy_id";
			$sql .= " LEFT JOIN $wpdb->posts ON $wpdb->posts.ID = tr.object_id";
			$sql .= " WHERE $wpdb->posts.post_type IN( $target_pt )";
			$sql .= " AND tt.parent = " . esc_sql( $get_data[$cols[2]] );
			if( isset( $taxonomy ) )
				$sql .= " AND tt.taxonomy = '" . esc_sql( $taxonomy ) . "'";
			if( $nocnt )
				$sql .= " AND tt.count != 0 ";
			if( $exids )
				$sql .= " AND t.term_id NOT IN ( $exids ) ";
			if( $sp != '' )
				$sql .= " AND tr.object_id NOT IN ( $sp ) ";
			$sql .= " AND $wpdb->posts.post_status = 'publish' ";
			$sql .= " GROUP BY t.term_id ";
			$sql .= " ORDER BY " . esc_sql( $to ) . " ";	
			
			$get_cats = $wpdb->get_results( $sql );
			$cnt_ele = count( $get_cats );
				
			for( $i = 0; $i < $cnt_ele; $i++ )
			{
				$get_cnt[$i] = 1;
			}
		
		} else { // カウントを表示
			
			// ターム一覧を取得
			$sql  = null;
			$sql  = " SELECT t.term_id, t.name, tt.count FROM $wpdb->terms AS t";
			$sql .= " LEFT JOIN $wpdb->term_taxonomy AS tt ON t.term_id = tt.term_id";
			$sql .= " WHERE tt.parent = " . esc_sql( $get_data[$cols[2]] ); 
			if( isset( $taxonomy ) )
				$sql .= " AND tt.taxonomy = '" . esc_sql( $taxonomy ) . "'";
			if( $nocnt )
				$sql .= " AND tt.count != 0";
			if( $exids )
				$sql .= " AND t.term_id NOT IN ( $exids )";
			$sql .= " ORDER BY " . esc_sql( $to ) . " ";
		
			$get_cats = $wpdb->get_results( $sql );
			
			// ターム毎のカウント数を取得
			for( $i = 0; $cnt_ele = count( $get_cats ), $i < $cnt_ele; $i++ )
			{
				$sql  = null;
				$sql  = " SELECT p.ID FROM $wpdb->posts AS p";
				$sql .= " LEFT JOIN $wpdb->term_relationships AS tr  ON p.ID = tr.object_id ";
				$sql .= " LEFT JOIN $wpdb->term_taxonomy AS tt ON tr.term_taxonomy_id = tt.term_taxonomy_id";
				$sql .= " WHERE tt.term_id = " . esc_sql( $get_cats[$i]->term_id );
				//$sql .=" AND tt.taxonomy='$taxonomy'";
				$sql .= " AND p.post_status = 'publish' ";
		
				$get_cats_cnt = $wpdb->get_results( $sql );
				
				$get_cnt[$i] = count( $get_cats_cnt );
				
				if( $get_cats_cnt )
				{
					// ターム毎の件数に除外記事を含めない（Post Type / Sticky Posts）
					for( $ii = 0; $ii < $get_cnt[$i]; $ii++ )
					{
						if( in_array( $get_cats_cnt[$ii]->ID , ( array ) $sp ) )
							$get_cnt[$i] = $get_cnt[$i] - 1;
					}
				}
			}
		}
	
		$output_form = array( 0 => $get_cats, 1 => $get_cats_cnt, 2 => $get_cnt, 3 => $cnt_ele );
		feas_cache_create( $cols_transient[1], $number, $output_form );
	}
	
	$get_cats = $output_form[0];
	$get_cats_cnt = $output_form[1];
	$get_cnt = $output_form[2];
	$cnt_ele = $output_form[3];
	
	// 表示する階層の深さの指定が未入力の場合、-1 (=無制限)を代入
	if( $get_data[$cols[10]] == "" || $get_data[$cols[10]] == null || !is_numeric( $get_data[$cols[10]] ) )
		$check_cnt = -1;
	else
		$check_cnt = intval( $get_data[$cols[10]] );
	
	// 階層が０(=現在の階層のみ表示)以外の場合、子カテゴリ表示のためにGET値を代入してcreate_child_op等に渡す
	if( $check_cnt != "0" )
	{
		if( isset( $_GET['search_element_' . $number ] ) )
		{
			if( is_array( $_GET['search_element_' . $number ] ) )
				$q_term_id = esc_html( $_GET['search_element_' . $number] );
			else
				$q_term_id[] = esc_html( $_GET['search_element_' . $number] );
		}
	}
	
	switch ( $get_data[$cols[4]] )
	{
		case 1:
			if( $get_data[$cols[19]] == 'no' )
			{	// Ajax
				$ret_ele  = '<div id="ajax_filtering_' . esc_attr( $manag_no ) . '_' . esc_attr( $number ) . '" class="search_element_' . esc_attr( $number ) . '">';
				$ret_ele .= "<select name='search_element_" . esc_attr( $number ) . "[]' class='0' onChange='ajax_filtering_next(" . esc_attr( $manag_no ) . ", " . esc_attr( $number ) . ", 0 )'>\n";
				$ret_ele .= "<option id='feas_" . esc_attr( $manag_no ) . "_" . esc_attr( $number ) . "_none' value=''>---未指定---</option>";
				$showcnt  = 'no';
				$check_cnt = 0;

				for( $i_ele = 0; $i_ele < $cnt_ele; $i_ele++ )
				{
					// 0件タームは表示しない場合（post_status処理後の件数を再評価）
					if( $nocnt && $get_cnt[$i_ele] == 0 )
						continue;
					
					$selected = '';
					if( isset( $_GET['search_element_' . $number] ) )
					{
						if( $_GET['search_element_' . $number][0] == $get_cats[$i_ele]->term_id )
						{
							$selected = ' selected="selected"';
							$checked_before = $get_cats[$i_ele]->term_id;
						}
					}
					// カテゴリ毎の件数を表示する設定の場合、件数を代入
					$cat_cnt = '';
					if( $showcnt == "yes" )
						$cat_cnt = " (" . $get_cnt[$i_ele] . ") ";
						
					$ret_ele .= "<option id='feas_" . esc_attr( $manag_no ) . "_" . esc_attr( $number ) . "_" . esc_attr( $i_ele ) . "' class='feas_clevel_01' value='" . esc_attr( $get_cats[$i_ele]->term_id ) . "' " . $selected . " >" . esc_html( $get_cats[$i_ele]->name . $cat_cnt ) . "</option>\n";
					$form_count = 0;
				}
				$ret_ele .= "</select>\n";
				
				
				if( isset( $_GET['search_element_' . $number] ) && is_array( $_GET['search_element_' . $number] ) )
				{
					for( $outer = 0; $outer < ( count( $_GET['search_element_' . $number] ) - 1 ); $outer++ )
					{
						if( !isset( $_GET['search_element_' . $number][$outer] ) )
							break;
						
						// ターム一覧を取得
						$sql  = null;
						$sql  = " SELECT t.term_id, t.name, tt.count FROM $wpdb->terms AS t";
						$sql .= " LEFT JOIN $wpdb->term_taxonomy AS tt ON t.term_id = tt.term_id";
						$sql .= " WHERE tt.parent = " . esc_sql( $_GET[ 'search_element_' . $number ][ $outer ] ); 
						if( isset( $taxonomy ) )
							$sql .= " AND tt.taxonomy = '" . esc_sql( $taxonomy ) . "'";
						if( $nocnt )
							$sql .= " AND tt.count != 0";
						if( $exids )
							$sql .= " AND t.term_id NOT IN ( $exids )";
						$sql .= " ORDER BY " . esc_sql( $to ) . " ";
					
						$get_cats = $wpdb->get_results( $sql );
						
						// ターム毎のカウント数を取得
						for( $i = 0; $cnt_ele = count( $get_cats ), $i < $cnt_ele; $i++ )
						{
							$sql  = null;
							$sql  = " SELECT p.ID FROM $wpdb->posts AS p";
							$sql .= " LEFT JOIN $wpdb->term_relationships AS tr  ON p.ID = tr.object_id ";
							$sql .= " LEFT JOIN $wpdb->term_taxonomy AS tt ON tr.term_taxonomy_id = tt.term_taxonomy_id";
							$sql .= " WHERE tt.term_id = " . esc_sql( $get_cats[$i]->term_id );
							//$sql .=" AND tt.taxonomy='$taxonomy'";
							$sql .= " AND p.post_status = 'publish' ";
					
							$get_cats_cnt = $wpdb->get_results( $sql );
							
							$get_cnt[$i] = count( $get_cats_cnt );
							
							if( $get_cats_cnt )
							{
								// カテゴリ毎の件数に、除外記事を含めない
								for( $ii = 0; $ii < $get_cnt[$i]; $ii++ )
								{
									if( in_array( $get_cats_cnt[$ii]->ID , ( array ) $sp ) )
										$get_cnt[$i] = $get_cnt[$i] - 1;
								}
							}
						}
						
						$ret_ele .= "<select name='search_element_" . esc_attr( $number ) . "[]' class='" . ( esc_attr( $outer ) + 1 ) . "' onChange='ajax_filtering_next( " . esc_attr( $manag_no ) . ", " . esc_attr( $number ) . ", " . ( esc_attr( $outer ) + 1 ) . " )'>\n";
						$ret_ele .= "<option id='feas_" . esc_attr( $manag_no ) . "_" . esc_attr( $number ) . "_none' value='" . $checked_before . "'>---未指定---</option>";
						
						for( $inner = 0; $inner < $cnt_ele; $inner++ )
						{
							// 0件タームは表示しない場合（post_status処理後の件数を再評価）
							//if( $nocnt && $get_cnt[$i_ele] == 0 )
								//continue;
							
							$selected = '';
							if( $_GET['search_element_' . $number][( $outer + 1 )] == $get_cats[$inner]->term_id )
							{
								$selected = ' selected="selected"';
								$checked_before = $get_cats[$inner]->term_id;
							}
							
							$cat_cnt = '';
							if( $showcnt == "yes" )
								$cat_cnt = " (" . $get_cnt[$inner] . ") ";
								
							$ret_ele .= "<option id='feas_" . esc_attr( $manag_no ) . "_" . esc_attr( $number ) . "_" . esc_attr( $i_ele ) . "' class='feas_clevel_01' value='" . esc_attr( $get_cats[$inner]->term_id ) . "' " . $selected . " >" . esc_html( $get_cats[$inner]->name . $cat_cnt ) . "</option>\n";
							
							$form_count = 0;
						}
						$ret_ele .= "</select>\n";
					}
				}
				
				$ret_ele .= "</div>\n";
			
			} else { // 普通のドロップダウン
				
				$ret_ele  = "<select name='search_element_" . esc_attr( $number ) . "' id='feas_" . esc_attr( $manag_no ) . "_" . esc_attr( $number ) . "'>\n";
				$ret_ele .= "<option id='feas_" . esc_attr( $manag_no ) . "_" . esc_attr( $number ) . "_none' value=''>---未指定---</option>";
				
				for( $i_ele = 0; $i_ele < $cnt_ele; $i_ele++ )
				{
					// 0件タームは表示しない場合（post_status処理後の件数を再評価）
					if( $nocnt && $get_cnt[$i_ele] == 0 )
						continue;
					
					$selected = '';
					if( isset( $_GET['search_element_' . $number] ) )
					{
						if( $_GET['search_element_' . $number] == $get_cats[$i_ele]->term_id )
							$selected = ' selected="selected"';
					}
					
					// カテゴリ毎の件数を表示する設定の場合、件数を代入
					$cat_cnt = '';
					if( $showcnt == "yes" )
					{
						$cat_cnt = " (" . $get_cnt[$i_ele] . ") ";
					}
	
					$ret_ele .= "<option id='feas_" . esc_attr( $manag_no ) . "_" . esc_attr( $number ) . "_" . esc_attr( $i_ele ) . "' class='feas_clevel_01' value='" . esc_attr( $get_cats[$i_ele]->term_id ) . "' " . $selected . " >" . esc_html( $get_cats[$i_ele]->name . $cat_cnt ) . "</option>\n";
					
					$form_count = 0;
					
					// 階層が０(=現在の階層のみ表示)以外の場合、子カテゴリを表示
					if( $check_cnt != "0" )
						// 子カテゴリ取得
						$ret_ele .= create_child_op( $get_cats[$i_ele]->term_id, $check_cnt, $class_cnt = 2, $q_term_id, $nocnt, $exids, $sticky, $showcnt, null, $taxonomy, $i_ele, $number, $sp, $to );
				}
				$ret_ele .= "</select>\n";
			}
			break;

		case 2:
			$ret_ele  = "<select name='search_element_" . esc_attr( $number ) . "[]' id='feas_" . esc_attr( $manag_no ). "_" . esc_attr( $number ) . "' size='5' multiple='multiple'>\n";
			$ret_ele .= "<option id='feas_" . esc_attr( $manag_no ) . "_" . esc_attr( $number ) . "_none' value=''>---未指定---</option>";

			for( $i_ele = 0, $cnt_ele = count( $get_cats ); $i_ele < $cnt_ele; $i_ele++ )
			{
				// 0件タームは表示しない場合（post_status処理後の件数を再評価）
				if( $nocnt && $get_cnt[$i_ele] == 0 )
					continue;
				
				$selected = '';
				if( isset( $_GET["search_element_" . $number] ) )
				{
					for( $i_lists = 0, $cnt_lists = count( $_GET["search_element_" . $number] ); $i_lists < $cnt_lists; $i_lists++ )
					{
						if( isset( $_GET["search_element_" . $number][$i_lists] ) )
						{
							if( $_GET["search_element_" . $number][$i_lists] == $get_cats[$i_ele]->term_id )
								$selected = ' selected="selected"';
						}
					}
				}
				
				// カテゴリ毎の件数を表示する設定の場合、件数を代入
				$cat_cnt = '';
				if( $showcnt == "yes" )
					$cat_cnt = " (" . $get_cnt[$i_ele] . ") ";
				
				$ret_ele .= "<option id='feas_" . esc_attr( $manag_no ) . "_" . esc_attr( $number ) . "_" . esc_attr( $i_ele ) . "' class='feas_clevel_01' value='" . esc_attr( $get_cats[$i_ele]->term_id ) . "' " . $selected . " >" . esc_html( $get_cats[$i_ele]->name . $cat_cnt ) . "</option>";
				$form_count = 0;
				
				// 階層が０(=現在の階層のみ表示)以外の場合、子カテゴリを表示
				if( $check_cnt != 0 )
					// 子カテゴリ取得 2011/03/13｜宮澤　加筆修正　2011/5/3
					$ret_ele .= create_child_op( $get_cats[$i_ele]->term_id, $check_cnt, $class_cnt = 2, $q_term_id, $nocnt, $exids, $sticky, $showcnt, null, $taxonomy, $i_ele, $number, $sp, $to );
			}
			$ret_ele .= "</select>\n";
			break;

		case 3:
			$ret_chi = array();
			$total_cnt = 0; // 子カテゴリのチェックボックスと累積生成カウント数を取得のため
			
			for( $i_ele = 0, $cnt_ele = count( $get_cats ); $i_ele < $cnt_ele; $i_ele++ )
			{
				// 0件タームは表示しない場合（post_status処理後の件数を再評価）
				if( $nocnt && $get_cnt[$i_ele] == 0 )
					continue;
				
				$checked = '';
				if( isset( $_GET['search_element_' . $number . '_' . $total_cnt] ) )
				{
					if( $_GET['search_element_' . $number . '_' . $total_cnt] == $get_cats[$i_ele]->term_id )
						$checked = ' checked="checked"';
				}

				$cat_cnt = '';
				if( $showcnt == "yes" )
					$cat_cnt = " (" . $get_cnt[$i_ele] . ") ";
				
				$ret_ele .= "<label class='feas_clevel_01' id='feas_" . esc_attr( $manag_no ) . "_" . esc_attr( $number ) . "_" . esc_attr( $i_ele ) . "'><input type='checkbox' name='search_element_" . esc_attr( $number ) . "_" . esc_attr( $total_cnt ) . "' value='" . esc_attr( $get_cats[$i_ele]->term_id ) . "' " . $checked . " />" . esc_html( $get_cats[$i_ele]->name . $cat_cnt ) . "</label>\n";
				
				$form_count = 0;  // labelのIDを振る用
				
				$total_cnt++;
				
				if( $check_cnt != 0 )
				{
					//if( $total_cnt == 0)
					//	$total_cnt = $cnt_ele;  //  生成済みのチェックボックス累積カウント
					
					// 子カテゴリ取得
					$ret_ele .= create_child_check( $get_cats[$i_ele]->term_id, "feas_clevel_", $check_cnt = -1, $class_cnt = 2, $nocnt, $exids, $sticky, $showcnt, $taxonomy, $i_ele, $number, $sp, $to );
					
				}
			}
			// search-controller に累積生成カウントを渡すため
			$ret_ele .= "<input type='hidden' name='search_element_" . esc_attr( $number ) . "_cnt' value='" . esc_attr( $total_cnt ) . "' />";
			break;

		case 4:
			for( $i_ele = 0, $cnt_ele = count( $get_cats ); $i_ele < $cnt_ele; $i_ele++ )
			{
				// 0件タームは表示しない場合（post_status処理後の件数を再評価）
				if( $nocnt && $get_cnt[$i_ele] == 0 )
					continue;
				
				$checked = '';
				if( isset( $_GET['search_element_' . $number] ) )
				{
					if( $_GET['search_element_' . $number] == $get_cats[$i_ele]->term_id )
						$checked = ' checked="checked"';
				}

				$cat_cnt = '';
				if( $showcnt == "yes" )
					$cat_cnt = " (" . $get_cnt[$i_ele] . ") ";
				
				$ret_ele .= "<label class='feas_clevel_01' id='feas_" . esc_attr( $manag_no ) . "_" . esc_attr( $number ) . "_" . esc_attr( $i_ele ) . "'><input type='radio' name='search_element_" . esc_attr( $number ) . "' value='" . esc_attr( $get_cats[$i_ele]->term_id ) . "' " . $checked . " />" . esc_attr( $get_cats[$i_ele]->name . $cat_cnt ) . "</label>";
				$form_count = 0;

				if( $check_cnt != 0 )
					// 子カテゴリ取得
					$ret_ele .= create_child_radio( $get_cats[$i_ele]->term_id, "feas_clevel_", $check_cnt, $class_cnt = 2, $nocnt, $exids, $sticky, $showcnt, $taxonomy, $i_ele, $number, $sp, $to );	
			}
			break;

		case 5:
			$ret_ele .= "<input type='text' name='s_keyword_" . esc_attr( $number ) . "' id='feas_" . esc_attr( $manag_no ) . "_" . esc_attr( $number ) . "' value='" . search_result( "keywords", $number ) . "' />";
			if( $get_data[$cols[21]] === 'no' )
				$ret_ele .= create_specifies_the_key_element( $get_data,$number );
			break;
			
		default:
			$ret_ele .= "<input type='text' name='s_keyword_" . esc_attr( $number ) . "' id='feas_" . esc_attr( $manag_no ) . "_" . esc_attr( $number ) . "' value='" . search_result( "keywords", $number ) . "' />";
			break;
	}

	return $ret_ele;
}

/////////////////////////////////////////////////
//カスタムフィールド（post_meta）のエレメント作成
/////////////////////////////////////////////////
function create_meta_element( $get_data = array(), $number )
{
	global $wpdb, $cols, $feadvns_show_count, $manag_no, $feadvns_include_sticky, $feadvns_search_target;

	$get_key = $get_unit = $get_kugiri = $sp = null;
	
	$get_key = mb_substr( $get_data[$cols[2]], 5, mb_strlen( $get_data[$cols[2]] ) );
	$get_kugiri = $get_data[$cols[18]];
	if( $get_data[$cols[17]] . $number != "" )
		$cfunit = $get_data[$cols[17]];

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
	
	// 固定記事(Sticky Posts)を検索対象から省く設定の場合、カウントに含めない
	$target_sp = db_op_get_value( $feadvns_include_sticky . $manag_no );
	if( $target_sp != 'yes' )
	{
		$sticky = get_option( 'sticky_posts' );
		if( $sticky != array() )
		{
			for( $i = 0; $cnt = count( $sticky ), $i < $cnt; $i++ )
			{
				$sp .= esc_sql( $sticky[$i] );
				if( $i + 1 < $cnt )
					$sp .= ',';
			}
		}
	}

	// キャッシュ
	if ( false === ( $get_metas = feas_cache_judgment( 'post_meta', $number ) ) )
	{
		$sql  = " SELECT post_id, meta_value, count( meta_value ) AS cnt FROM $wpdb->postmeta";
		$sql .= " LEFT JOIN $wpdb->posts ON $wpdb->posts.ID = $wpdb->postmeta.post_id";
		$sql .= " WHERE meta_key = '" . esc_sql( $get_key ) . "'";
		$sql .= " AND $wpdb->posts.post_type IN ( $target_pt )";
		$sql .= " AND $wpdb->posts.post_status = 'publish' ";
		if( $sp != null )
			$sql .= " AND post_id NOT IN ( $sp ) ";
		$sql .= " GROUP BY meta_value";
// 		$sql .= " ORDER BY meta_value+0 ASC";
		$get_metas = $wpdb->get_results( $sql );
	
		feas_cache_create( 'post_meta', $number, $get_metas );
	}
	
	if( db_op_get_value( $cols[22] . $manag_no . "_" . $number ) == 'yes' )
	{
		$fe_limit_free_input = true;
		$get_data[$cols[4]] = 'cf_limit_keyword';
	}
	
	$ret_ele = $showcnt = null;
	
	$showcnt = db_op_get_value( $feadvns_show_count . $manag_no );

	switch ( $get_data[$cols[4]] )
	{
		case 1:
			$ret_ele  = "<select name='search_element_" . esc_attr( $number ) . "' id='feas_" . esc_attr( $manag_no ) . "_" . esc_attr( $number ) . "'>\n";
			$ret_ele .= "<option id='feas_" . esc_attr( $manag_no ) . "_" . esc_attr( $number ) . "_none' value=''>---未指定---</option>";
		
			for( $i_ele = 0, $cnt_ele = count( $get_metas ); $i_ele < $cnt_ele; $i_ele++ )
			{
				$selected = '';
				if( isset( $_GET['search_element_' . $number] ) == true )
				{
					if( $_GET['search_element_' . $number ] == $get_metas[$i_ele]->meta_value )
						$selected = ' selected="selected"';
				}
				
				$cat_cnt = '';
				if( $showcnt == "yes" )
					$cat_cnt = " (" . $get_metas[$i_ele]->cnt . ") ";

				if( $get_kugiri == 'yes' && is_numeric( $get_metas[$i_ele]->meta_value ) )
					$cfdata = number_format( $get_metas[$i_ele]->meta_value );
				else 
					$cfdata = $get_metas[$i_ele]->meta_value;
				
				$ret_ele .= "<option id='feas_" . esc_attr( $manag_no ) . "_" . esc_attr( $number ) . "_" . esc_attr( $i_ele ) . "' value='" . esc_attr( $get_metas[$i_ele]->meta_value ) . "' $selected>" . esc_html( $cfdata . $cfunit . $cat_cnt ) . "</option>";
				//$ret_ele .="<option id='feas_".$manag_no."_".$number."_".$i_ele."' value='" . urlencode( mb_convert_encoding ($get_metas[$i_ele]->meta_value , 'SJIS') )."' $selected>" .$cfdata . $cfunit . $cat_cnt ."</option>";
			}

			$ret_ele .= "</select>";
			break;
			
		case 2:

			$ret_ele  = "<select name='search_element_" . esc_attr( $number ) . "[]' id='feas_" . esc_attr( $manag_no ) . "_" . esc_attr( $number ) . "' size='5' multiple='multiple'>\n";
			$ret_ele .= "<option id='feas_" . esc_attr( $manag_no ) . "_" . esc_attr( $number ) . "_none' value=''>---未指定---</option>";

			for( $i_ele = 0, $cnt_ele = count( $get_metas ); $i_ele < $cnt_ele; $i_ele++ )
			{
				$selected = '';
				for( $i_lists = 0, $cnt_lists = count( $_GET["search_element_" . $number] ); $i_lists < $cnt_lists; $i_lists++ )
				{
					if( isset( $_GET["search_element_" . $number][$i_lists] ) )
					{
						if( $_GET["search_element_" . $number][$i_lists] == $get_metas[$i_ele]->meta_value )
							$selected = ' selected="selected"';
					}
				}
				
				$cat_cnt = '';
				if( $showcnt == "yes" )
					$cat_cnt = " (" . $get_metas[$i_ele]->cnt . ") ";
				
				if( $get_kugiri == 'yes' && is_numeric( $get_metas[$i_ele]->meta_value ) )
					$cfdata = number_format( $get_metas[$i_ele]->meta_value );
				else
					$cfdata = $get_metas[$i_ele]->meta_value;
				
				$ret_ele .= "<option id='feas_" . esc_attr( $manag_no ) . "_" . esc_attr( $number ) . "_" . esc_attr( $i_ele ) . "' value='" . esc_attr( $get_metas[$i_ele]->meta_value ) . "'" . $selected . " >" . esc_html( $cfdata . $cfunit . $cat_cnt ) . "</option>";
			}
			$ret_ele .= "</select>\n";
			break;
			
		case 3:
			for( $i_ele = 0, $cnt_ele = count( $get_metas ); $i_ele < $cnt_ele; $i_ele++ )
			{
				$checked = '';
				if( isset( $_GET['search_element_' . $number . "_" . $i_ele] ) )
				{
					if( $_GET['search_element_' . $number . "_" . $i_ele] == $get_metas[$i_ele]->meta_value )
						$checked = ' checked="checked"';
				}
				
				$cat_cnt = null;
				if( $showcnt == "yes" )
					$cat_cnt = " (" . $get_metas[$i_ele]->cnt . ") ";
				
				if( $get_kugiri == 'yes' && is_numeric( $get_metas[$i_ele]->meta_value ) )
					$cfdata = number_format( $get_metas[$i_ele]->meta_value );
				else
					$cfdata = $get_metas[$i_ele]->meta_value;
				
				$ret_ele .= "<label id='feas_" . esc_attr( $manag_no ) . "_" . esc_attr( $number ) . "_" . esc_attr( $i_ele ) . "'><input type='checkbox' name='search_element_" . esc_attr( $number ) . "_" . esc_attr( $i_ele ) . "' value='" . esc_attr( $get_metas[$i_ele]->meta_value ) . "' " . $checked . " />" . esc_html( $cfdata . $cfunit . $cat_cnt ) . "</label>\n";
			}
			break;
			
		case 4:
			for( $i_ele = 0, $cnt_ele = count( $get_metas ); $i_ele < $cnt_ele; $i_ele++ )
			{
				$checked = '';
				if( isset( $_GET['search_element_' . $number] ) )
				{
					if( $_GET['search_element_' . $number] == $get_metas[$i_ele]->meta_value )
						$checked = ' checked="checked"';
				}
				
				$cat_cnt = '';
				if( $showcnt == "yes" )
					$cat_cnt = " (" . $get_metas[$i_ele]->cnt . ") ";
				
				if( $get_kugiri == 'yes' && is_numeric( $get_metas[$i_ele]->meta_value ) )
					$cfdata = number_format( $get_metas[$i_ele]->meta_value, 0, '.', ',' );
				else
					$cfdata = $get_metas[$i_ele]->meta_value;
				
				$ret_ele .= "<label id='feas_" . esc_attr( $manag_no ) . "_" . esc_attr( $number ) . "_" . esc_attr( $i_ele ) . "'><input type='radio' name='search_element_" . esc_attr( $number ) . "' value='" . esc_attr( $get_metas[$i_ele]->meta_value ) . "' " . $checked . " />" . esc_html( $cfdata . $cfunit . $cat_cnt ) . "</label>";
			}
			break;
			
		case 5:
			$ret_ele .= "<input type='text' name='s_keyword_" . esc_attr( $number ) . "' id='feas_" . esc_attr( $manag_no ) . "_" . esc_attr( $number ) . "' value='" . search_result( "keywords", $number ) . "' />";
			if( $get_data[$cols[21]] === 'no' )
				$ret_ele .= create_specifies_the_key_element( $get_data, $number );
			break;
			
		case 'cf_limit_keyword':
			$ret_ele .= "<input type='text' name='cf_limit_keyword_" . esc_attr( $number ) . "' id='feas_" . esc_attr( $manag_no ) . "_" . esc_attr( $number ) . "' value='" . search_result( "keywords", $number ) . "' />";
			break;
			
		default:
			$ret_ele .= "<input type='text' name='s_keyword_" . esc_attr( $number ) . "' id='feas_" . esc_attr( $manag_no ) . "_" . esc_attr( $number ) . "' value='" . search_result( "keywords", $number ) . "' />";
			break;
	}

	return $ret_ele;
}


/////////////////////////////////////////////////
//タグ（tag）のエレメント作成
/////////////////////////////////////////////////
function create_tag_element( $get_data = array(), $number )
{
	global $wpdb, $cols, $feadvns_show_count, $manag_no, $feadvns_include_sticky, $feadvns_search_target;
	$target_sp = $sp = $ret_ele = $showcnt = $ret_ele = null;
	$sticky = array();

	if( isset( $get_data[$cols[5]] ) )
	{
		switch ( $get_data[$cols[5]] )
		{
			case 0:
				$option_order = " ORDER BY t.term_id ASC ";
				break;
			case 1:
				$option_order = " ORDER BY t.term_id DESC ";
				break;
			case 2:
				$option_order = " ORDER BY t.name ASC ";
				break;
			case 3:
				$option_order = " ORDER BY t.name DESC ";
				break;
			case 4:
				$option_order = " ORDER BY t.slug ASC ";
				break;
			case 5:
				$option_order = " ORDER BY t.slug DESC ";
				break;
			case 6:
				$option_order = " ORDER BY t.term_order ASC ";
				break;
			default:
				$option_order = " ORDER BY t.term_id ASC ";
				break;
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
	
	// 件数を表示するorしない
	$showcnt = db_op_get_value( $feadvns_show_count . $manag_no );
		
	// 固定記事(Sticky Posts)を検索対象から省く設定の場合、カウントに含めない
	$target_sp = db_op_get_value( $feadvns_include_sticky . $manag_no );
	if( $target_sp != 'yes' )
	{
		$sticky = get_option( 'sticky_posts' );
		if( $sticky != array() )
		{
			for( $i = 0; $cnt = count( $sticky ), $i < $cnt; $i++ )
			{
				$sp .= esc_sql( $sticky[$i] );
				if( $i + 1 < $cnt )
					$sp .= ',';
			}
		}
	}

	// キャッシュ
	if( false === ( $get_tags = feas_cache_judgment( 'tag', $number ) ) )
	{
		// タグを取得する
		$sql  = " SELECT t.term_id, t.name, count( DISTINCT object_id ) AS cnt FROM $wpdb->term_relationships AS tr";
		$sql .= " LEFT JOIN $wpdb->term_taxonomy AS tt ON tr.term_taxonomy_id = tt.term_taxonomy_id";
		$sql .= " LEFT JOIN $wpdb->terms AS t ON tt.term_id = t.term_id";
		$sql .= " LEFT JOIN $wpdb->posts ON $wpdb->posts.ID = object_id";
		$sql .= " WHERE tt.taxonomy='post_tag'";
		$sql .= " AND $wpdb->posts.post_status = 'publish'";
		$sql .= " AND $wpdb->posts.post_type IN( $target_pt )";
		if( $sp != null )
			$sql .= " AND tr.object_id NOT IN ( $sp )";
		$sql .= " GROUP BY t.term_id";
		$sql .= $option_order;
		$get_tags = $wpdb->get_results( $sql );
				
		feas_cache_create( 'tag', $number, $get_tags );
	}
	$cnt_ele = count( $get_tags ); 

	switch ( $get_data[$cols[4]] )
	{
		case 1:
			$ret_ele  = "<select name='search_element_" . esc_attr( $number ) . "' id='feas_" . esc_attr( $manag_no ) . "_" . esc_attr( $number ) . "'>\n";

			$ret_ele .= "<option id='feas_" . esc_attr( $manag_no ) . "_" . esc_attr( $number ) . "_none' value=''>---未指定---</option>";

			for( $i_ele = 0; $i_ele < $cnt_ele; $i_ele++)
			{
				$selected = '';
				if( isset( $_GET['search_element_' . $number] ) )
				{
					if( $_GET['search_element_' . $number] == $get_tags[$i_ele]->term_id )
						$selected = ' selected="selected"';
				}

				$cat_cnt = '';
				if( $showcnt == "yes" )
					$cat_cnt = " (" . $get_tags[$i_ele]->cnt . ") ";
					
				$ret_ele .= "<option id='feas_" . esc_attr( $manag_no ) . "_" . esc_attr( $number ) . "_" . esc_attr( $i_ele ) . "' value='" . esc_attr( $get_tags[$i_ele]->term_id ) . "'" . $selected . " >" . esc_attr( $get_tags[$i_ele]->name . $cat_cnt ) . "</option>\n";
			}
	
			$ret_ele .= "</select>\n";
			break;

		case 2:
			$ret_ele  = "<select name='search_element_" . esc_attr( $number ) . "[]' id='feas_" . esc_attr( $manag_no ) . "_" . esc_attr( $number ) . "' size='5' multiple='multiple'>\n";

			$ret_ele .= "<option id='feas_" . esc_attr( $manag_no ) . "_" . esc_attr( $number ) . "_none' value=''>---未指定---</option>";

			for( $i_ele = 0; $i_ele < $cnt_ele; $i_ele++ )
			{
				$selected = '';
				for( $i_lists = 0, $cnt_lists = count( $_GET["search_element_" . $number] ); $i_lists < $cnt_lists; $i_lists++ )
				{
					if( isset( $_GET["search_element_" . $number][$i_lists] ) )
					{
						if( $_GET["search_element_" . $number][$i_lists] == $get_tags[$i_ele]->term_id )
							$selected = ' selected="selected"';
					}
				}

				$cat_cnt = '';
				if( $showcnt == "yes" )
					$cat_cnt = " (" . $get_tags[$i_ele]->cnt . ") ";
					
				$ret_ele .= "<option id='feas_" . esc_attr( $manag_no ) . "_" . esc_attr( $number ) . "_" . esc_attr( $i_ele ) . "' value='" . esc_attr( $get_tags[$i_ele]->term_id ) . "'" . $selected . " >" . esc_html( $get_tags[$i_ele]->name . $cat_cnt ) . "</option>";
			}
			$ret_ele .= "</select>\n";
			break;

		case 3:
			for( $i_ele = 0; $i_ele < $cnt_ele; $i_ele++ )
			{
				$checked = '';
				if( isset( $_GET['search_element_' . $number . "_" . $i_ele] ) )
				{
					if( $_GET['search_element_' . $number . "_" . $i_ele] == $get_tags[$i_ele]->term_id )
						$checked = ' checked="checked"';
				}

				$cat_cnt = '';
				if( $showcnt == "yes" )
					$cat_cnt = " (" . $get_tags[$i_ele]->cnt . ") ";
					
				$ret_ele .= "<label id='feas_" . esc_attr( $manag_no ) . "_" . esc_attr( $number ) . "_" . esc_attr( $i_ele ) . "'><input type='checkbox' name='search_element_" . esc_attr( $number ) . "_" . esc_attr( $i_ele ) . "' value='" . esc_attr( $get_tags[$i_ele]->term_id ) . "' " . $checked . " />" . esc_html( $get_tags[$i_ele]->name . $cat_cnt ) . "</label>\n";
			}
			break;

		case 4:
			for( $i_ele = 0; $i_ele < $cnt_ele; $i_ele++ )
			{
				$checked = '';
				if( isset( $_GET['search_element_' . $number] ) )
				{
					if( $_GET['search_element_' . $number] == $get_tags[$i_ele]->term_id )
						$checked = ' checked="checked"';
				}

				$cat_cnt = '';
				if( $showcnt == "yes" )
					$cat_cnt = " (" . $get_tags[$i_ele]->cnt . ") ";
					
				$ret_ele .= "<label id='feas_" . esc_attr( $manag_no ) . "_" . esc_attr( $number ) . "_" . esc_attr( $i_ele ) . "'><input type='radio' name='search_element_" . esc_attr( $number ) . "' value='" . esc_attr( $get_tags[$i_ele]->term_id ) . "' " . $checked . " />" . esc_html( $get_tags[$i_ele]->name . $cat_cnt ) . "</label>";
			}
			break;

		case 5:
			$ret_ele .= "<input type='text' name='s_keyword_" . esc_attr( $number ) . "' id='feas_" . esc_attr( $manag_no ) . "_" . esc_attr( $number ) . "' value='" . search_result( "keywords", $number ) . "' />";
			if( $get_data[$cols[21]] === 'no' )
				$ret_ele .= create_specifies_the_key_element( $get_data, $number );
			break;
		
		default:
			$ret_ele .= "<input type='text' name='s_keyword_" . esc_attr( $number ) . "' id='feas_" . esc_attr( $manag_no ) . "_" . esc_attr( $number ) . "' value='" . search_result( "keywords", $number ) . "' />";
			break;
	}

	return $ret_ele;
}

/////////////////////////////////////////////////////
//カスタムフィールドのキー指定検索エレメント作成 2012_3_14
/////////////////////////////////////////////////////
function create_specifies_the_key_element( $get_data = array(), $number )
{
	global $wpdb, $cols, $feadvns_show_count, $manag_no, $feadvns_include_sticky;
	$ret_ele = null;
	
	$meta_keys = explode( ',', $get_data['feadvns_cf_specify_key_'] );
	
	$i_ele = 0;
	foreach( $meta_keys as $val )
	{
		$ret_ele .= "<input type='hidden' name='cf_specify_key_" . esc_attr( $number ) . "_" . esc_attr( $i_ele ) . "' value='" . esc_attr( $val ) . "' />";
		$i_ele++;
	}
	$ret_ele .= "<input type='hidden' name='cf_specify_key_length_" . esc_attr( $number ) . "' value='" . ( esc_attr( $i_ele ) - 1 ) . "'/>";
		
	return $ret_ele;
}
?>