<?php
////////////////////////////////////
//プラグイン共通関数
////////////////////////////////////
/*************************************************/
/*viewとcontrollerの
/*************************************************/
function func_management( $func_name = null )
{
	global $wpdb, $cols, $cols_order, $feadvns_max_line, $feadvns_max_line_order, $manag_no, $feadvns_search_b_label, $feadvns_max_page, $use_style_key, $style_body_key, $meta_sort_key, $pv_css, $feadvns_search_form_name, $feadvns_search_target, $feadvns_default_cat, $feadvns_sort_target, $feadvns_sort_order, $feadvns_sort_target_cfkey, $feadvns_sort_target_cfkey_as, $feadvns_empty_request, $feadvns_show_count, $feadvns_include_sticky, $kwds_for_view, $feadvns_kwds_target, $feadvns_kwds_yuragi;

	if( $func_name != null )
	{
		require_once( $func_name . "-controller.php" );
		require_once( $func_name . "-view.php" );
	}
}

/*************************************************/
/*DBのoptionsに指定キーが存在してるかのcheck
/*************************************************/
function db_op_field_check( $option_name = null )
{
	global $wpdb;

	if( $option_name == null )
		return null;

	$sql  = " SELECT option_id FROM $wpdb->options";
	$sql .= " WHERE option_name = '" . esc_sql( $option_name ) ."'";
	$sql .= " LIMIT 1";
	$get_date = $wpdb->get_results( $sql );

	if( isset( $get_date[0]->option_id ) )
		return $get_date[0]->option_id;
	else
		return null;
}
/*************************************************/
/*optionsのoption_value値を取得
/*************************************************/
function db_op_get_value( $option_name = null )
{
	global $wpdb;

/*
	$option = get_option($option_name,false);
	if($option !== false){
		return $option;
	}else{
		return null;
	}
*/

	$sql  = " SELECT option_value FROM $wpdb->options";
	$sql .= " WHERE option_name = '" . esc_sql( $option_name ) . "'";
	$sql .= " LIMIT 1";
	$check_data = $wpdb->get_results( $sql );

	if( isset( $check_data[0]->option_value ) )
		return $check_data[0]->option_value;
	else
		return null;
}

/*************************************************/
/*optionsのoption_value値を新規書き込み
/*************************************************/
function db_op_insert_value( $option_name = null, $option_value = null )
{
	global $wpdb, $wp_version;

	$sql  = " SELECT option_id FROM $wpdb->options";
	$sql .= " ORDER BY option_id DESC";
	$sql .= " LIMIT 1";
	$insert_id = $wpdb->get_results( $sql );
	$insert_id = ( $insert_id[0]->option_id + 1 );
	
	
	if( $option_value != null )
	{
		if( $wp_version >= '3.4' )
		{
			$insert_sql  = " INSERT INTO $wpdb->options";
			$insert_sql .= " (option_id,option_name,option_value,autoload )";
			$insert_sql .= " VALUES( " . esc_sql( $insert_id ) . ", '" . esc_sql( $option_name ) . "', '" . esc_sql( $option_value ) . "', 'yes' )";
		
		} else {
			
			$insert_sql  = " INSERT INTO $wpdb->options";
			$insert_sql .= " (option_id,blog_id,option_name,option_value,autoload )";
			$insert_sql .= " VALUES( " . esc_sql( $insert_id ) .", 0, '" . esc_sql( $option_name ) ."', '" . esc_sql( $option_value ) . "', 'yes' )";
		}
		$wpdb->get_results( $insert_sql );
	}
}

/*************************************************/
/*optionsのoption_value値を更新
/*************************************************/
function db_op_update_value( $option_name = null,  $option_value = null )
{
	global $wpdb;
	
/*
	if( update_option( $option_name, $option_value ) )
		return;
*/
	
	$sql  = " SELECT option_id FROM $wpdb->options";
	$sql .= " WHERE option_name= '" . esc_sql( $option_name ) . "'";
	$sql .= " LIMIT 1";
	$check_data = $wpdb->get_results( $sql );

	if( isset( $check_data[0]->option_id ) )
	{
		if( isset( $option_value ) && $option_value !== null )
		{
			$update_sql  = " UPDATE $wpdb->options"; 
			$update_sql .= " SET option_value = '" . esc_sql( $option_value ) . "'";
			$update_sql .= " WHERE option_id = " . esc_sql( $check_data[0]->option_id );
			$wpdb->get_results( $update_sql );
		}
	}
}

/*************************************************/
/*optionsのデータを消去
/*************************************************/
function db_op_delete_value( $option_name = null )
{
	global $wpdb;

	if( $option_name == null )
		return;
	else {
		$del_sql  = " DELETE FROM " . $wpdb->options;
		$del_sql .= " WHERE option_name ='" . esc_sql( $option_name ) . "'";
		$wpdb->get_results( $del_sql );
	}
}

/*************************************************/
/*optionsに新規登録（各種設定）
/*************************************************/
function db_op_insert( $option_name = null )
{
	global $wpdb, $wp_version;

	$sql  = " SELECT option_id FROM $wpdb->options";
	$sql .= " ORDER BY option_id DESC";
	$sql .= " LIMIT 1";
	$insert_id = $wpdb->get_results( $sql );
	$insert_id = ( $insert_id[0]->option_id + 1 );

	if( $wp_version >= '3.4' )
	{
		$insert_sql  = " INSERT INTO $wpdb->options";
		$insert_sql .= " ( option_id, option_name, option_value, autoload )";
		if( isset( $_POST[$option_name] ) )
			$insert_sql .= " VALUES( " . esc_sql( $insert_id ) . ", '" . esc_sql( $option_name ) . "', '" . esc_sql( $_POST[$option_name] ) . "', 'yes' )";
		else
			$insert_sql .= " VALUES( " . esc_sql( $insert_id ) . ",'" . esc_sql( $option_name ) . "', '', 'yes' )";
	}else{
		$insert_sql  = " INSERT INTO $wpdb->options";
		$insert_sql .= " (option_id,blog_id,option_name,option_value,autoload )";
		if( isset( $_POST[$option_name] ) )
			$insert_sql .= " VALUES(" . esc_sql( $insert_id ) . ", 0, '" . esc_sql( $option_name ) . "', '" . esc_sql( $_POST[$option_name] ) . "', 'yes' )";
		else
			$insert_sql .= " VALUES(" . esc_sql( $insert_id ) . ", 0, '" . esc_sql( $option_name ) . "', '', 'yes' )";
	}
	$wpdb->get_results( $insert_sql );
}
/*************************************************/
/*optionsに更新作業（各種設定）
/*************************************************/
function db_op_update( $option_name = null )
{
	global $wpdb;

	$sql  = " SELECT option_id FROM $wpdb->options";
	$sql .= " WHERE option_name='" . esc_sql( $option_name ) ."'";
	$sql .= " LIMIT 1";
	$check_data = $wpdb->get_results( $sql );

	if( isset( $check_data[0]->option_id ) )
	{
		if( isset( $_POST[$option_name] ) )
		{
			$update_sql  = " UPDATE $wpdb->options"; 
			$update_sql .= " SET option_value = '" . esc_sql( $_POST[$option_name] ) . "'";
			$update_sql .= " WHERE option_id = " . esc_sql( $check_data[0]->option_id );
			$wpdb->get_results( $update_sql );
		}
	}
}
/*************************************************/
/*optionsの値を取得
/*************************************************/
function db_op_get_data( $get_op_id = null )
{
	global $wpdb;

	if( $get_op_id != null )
	{
		$sql  = " SELECT option_value FROM $wpdb->options";
		$sql .= " WHERE option_id =" . esc_sql( $get_op_id );
		$sql .= " LIMIT 1";
		$get_data = $wpdb->get_results( $sql );
	}
	else
		return null;

	return $get_data[0]->option_value;
}
/*************************************************/
/*消去にチェックがついていたら$_POSTずらす
/*************************************************/
function check_del_line( $manag_no, $line_cnt )
{
	global $wpdb, $cols, $feadvns_max_line;

	$line_data = array();
	$ins_data = 0;

	// POSTずらす処理
	for( $i = 0; $i < $line_cnt; $i++ )
	{
		if( isset( $_POST[$cols[9] . $manag_no . "_" . $i] ) == false || $_POST[$cols[9] . $manag_no . "_" . $i] != "del" )
		{
			for( $i_ins =0, $cnt_ins = count( $cols ); $i_ins < $cnt_ins; $i_ins++ )
			{
				if( isset($_POST[$cols[$i_ins] . $manag_no . "_" . $i] ) )
					$line_data[$cols[$i_ins] . $manag_no . "_" . $ins_data] = $_POST[$cols[$i_ins] . $manag_no . "_" . $i];
				else
					$line_data[$cols[$i_ins] . $manag_no . "_" . $ins_data] = null;
			}
			$ins_data++;
		}
	}

	$_POST = $line_data;

	// 表示ラインの検索キー
	$line_key = $feadvns_max_line . $manag_no;

	// 新規
	$save_line_number = $ins_data;

	if( db_op_get_value( $line_key ) == null )
		db_op_insert_value( $line_key, $save_line_number );
	else //更新
		db_op_update_value( $line_key, $save_line_number );

	// DBの設定データを一次的に消去
	for( $i = 0; $i < $line_cnt; $i++ )
	{
		for( $i_ins = 0, $cnt_ins = count( $cols ); $i_ins < $cnt_ins; $i_ins++ )
		{
			db_op_delete_value( $cols[$i_ins] . $manag_no . "_" . $i );
		}
	}
	
	$_POST['ac'] = "update";
	return $ins_data;
}
/*************************************************/
/*消去にチェックがついていたら$_POSTずらす（ソート）
/*************************************************/
function check_del_line_order( $manag_no, $line_cnt )
{
	global $wpdb, $cols, $cols_order, $feadvns_max_line_order;

	$line_data = array();
	$ins_data = 0;

	// POSTずらす処理
	for( $i = 0; $i < $line_cnt; $i++ )
	{
		if( isset( $_POST[$cols_order[3] . $manag_no . "_" . $i] ) == false || $_POST[$cols_order[3] . $manag_no . "_" . $i] != "del" )
		{
			for( $i_ins = 0, $cnt_ins = count( $cols_order ); $i_ins < $cnt_ins; $i_ins++ )
			{
				if( isset( $_POST[$cols_order[$i_ins] . $manag_no . "_" . $i] ) )
					$line_data[$cols_order[$i_ins] . $manag_no ."_" . $ins_data] = $_POST[$cols_order[$i_ins] . $manag_no . "_" . $i ];
				else
					$line_data[$cols_order[$i_ins] . $manag_no ."_" . $ins_data] = null;
			}
			$ins_data++;
		}
	}

	$_POST = $line_data;

	// 表示ラインの検索キー
	$line_key = $feadvns_max_line_order . $manag_no;

	// 新規
	$save_line_number = $ins_data;

	if( db_op_get_value($line_key) == null )
		db_op_insert_value( $line_key, $save_line_number );
	else // 更新
		db_op_update_value( $line_key, $save_line_number );

	// DBの設定データを一次的に消去
	for( $i = 0; $i < $line_cnt; $i++ )
	{
		for( $i_ins = 0, $cnt_ins = count( $cols_order ); $i_ins < $cnt_ins; $i_ins++ )
		{
			db_op_delete_value( $cols_order[$i_ins] . $manag_no . "_" . $i );
		}
	}

	$_POST['ac'] = "update";
	return $ins_data;
}
/*************************************************/
/* 検索条件を格納
/*************************************************/
function insert_result( $data ){
	global $wp_query;
	if( $wp_query->is_main_query() )
	{
		if( isset( $_POST['search_result_data']) && ( $_POST['search_result_data'] != null ) )
			$_POST['search_result_data'] .= "," . $data;
		else
			$_POST['search_result_data'] = $data;
	}
}

function insert_kwds_result( $data , $key = 0 )
{
	global $wp_query;
	if( $wp_query->is_main_query() )
	{
		if( isset( $_POST['kwds_result_data_' . $key ] ) && ( $_POST['kwds_result_data_' . $key ] !== null ) )
			$_POST['kwds_result_data_' . $key ] .= ' ' . $data;
		else
			$_POST['kwds_result_data_' . $key ] = $data;
		// 全てのフリーワードフォームに入力された文字,ハイライト表示のキーワードなどに
		$_POST['kwds_result_data_all'][] = $data;
	}
}

function feas_insert_keys_result( $data, $key = 0 ){ //カスタムフィールドのフィールド名
	$_POST['keys_result_data_' . $key][] = $data;
}

/*************************************************/
/*  子カテゴリーがあった場合option作成
/*************************************************/
function create_child_op( $par_id = null, $check_cnt = -1, $class_cnt = 2, $q_term_id = array(), $nocnt = null, $exids = null, $sticky = array(), $showcnt = null, $manage_line = null, $taxonomy = false, $par_no = 0, $number = 0, $sp = array(), $to = " t.term_id ASC "){
	global $wpdb, $cols, $manag_no, $form_count, $feadvns_search_target, $feadvns_include_sticky, $feadvns_search_b_label;
	// 管理ページで子カテゴリに使用するため、オプション等を追加 2012.3.29:熊谷
	
	$ret_ele = null;

	if( $check_cnt == 0 || $par_id == null )
		return;
	
	if( is_null( $showcnt ) && $nocnt ){
		// 検索対象のPost Typeを取得
		//$target_pt = db_op_get_value( $feadvns_search_target . $manag_no );
		
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
		$sp = '';
		if( $target_sp != 'yes' )
		{
			$sticky = get_option( 'sticky_posts' );
			if( $sticky != array() )
			{
				for( $i = 0; $cnt = count( $sticky ), $i < $cnt; $i++ )
				{
					$sp .= "'" . esc_sql( $sticky[$i] ) . "'";
					if( $i + 1 < $cnt )
						$sp .= ',';
				}
			}
		}
		
		//  カテゴリーを取得する
		$sql  = " SELECT t.term_id, t.name, count( DISTINCT object_id ) AS cnt FROM $wpdb->terms AS t";
		$sql .= " LEFT JOIN $wpdb->term_taxonomy AS tt ON t.term_id = tt.term_id";
		$sql .= " LEFT JOIN $wpdb->term_relationships AS tr ON tt.term_taxonomy_id = tr.term_taxonomy_id";
		$sql .= " LEFT JOIN $wpdb->posts ON $wpdb->posts.ID = tr.object_id";
		$sql .= " WHERE $wpdb->posts.post_type IN( $target_pt )";
		$sql .= " AND tt.parent ='" . $par_id . "'";
		if( $taxonomy ) $sql .= " AND tt.taxonomy ='" . esc_sql( $taxonomy ) . "'";  //  カスタムタクソノミーを汎用的に検索するため、あえて限定的な条件を外す。
		if( $nocnt ) $sql .= " AND tt.count != 0 ";
		if( $exids ) $sql .= " AND t.term_id NOT IN ( $exids ) ";
		if( $sp != '' ) $sql .= " AND tr.object_id NOT IN ( $sp ) ";
		$sql .= " AND $wpdb->posts.post_status = 'publish' ";
		$sql .= " GROUP BY t.term_id ";
		$sql .= " ORDER BY " . esc_sql( $to ) . " ";
		
		$get_cats = $wpdb->get_results( $sql );
		$cnt_ele = count( $get_cats );
		
		for( $i = 0; $i < $cnt_ele; $i++ )
		{
			$get_cnt[$i] = 1;
		}
	
	} else { // カウントを表示。超遅い
		
		// ターム一覧を取得
		$sql  = null;
		$sql  = " SELECT t.term_id, t.name FROM " . $wpdb->terms . " AS t ";
		$sql .= " LEFT JOIN " . $wpdb->term_taxonomy . " AS tt ON t.term_id = tt.term_id ";
		$sql .= " WHERE tt.parent ='" . $par_id . "' "; 
		if( $taxonomy ) $sql .= " AND tt.taxonomy='" . esc_sql( $taxonomy ) . "' ";
		if( $nocnt ) $sql .= " AND tt.count != 0 ";
		if( $exids ) $sql .= " AND t.term_id NOT IN (" . esc_sql( $exids ) . ") ";
		$sql .= " ORDER BY " . esc_sql( $to ) . " ";	

		$get_cats = $wpdb->get_results( $sql );
		
		// ターム毎のカウント数を取得
		for( $i = 0; $cnt_ele = count( $get_cats ) ,$i < $cnt_ele; $i++ )
		{
			$sql  = null;
			$sql  = " SELECT p.ID FROM " . $wpdb->posts . " AS p";
			$sql .= " LEFT JOIN " . $wpdb->term_relationships . " AS tr ON p.ID = tr.object_id ";
			$sql .= " LEFT JOIN " . $wpdb->term_taxonomy . " AS tt ON tr.term_taxonomy_id = tt.term_taxonomy_id";
			$sql .= " WHERE tt.term_id ='" . esc_sql( $get_cats[$i]->term_id ) . "'";
			//$sql .=" AND tt.taxonomy='$taxonomy'";
			$sql .= " AND p.post_status = 'publish' ";
	
			$get_cats_cnt = $wpdb->get_results( $sql );
	
			$get_cnt[$i] = count( $get_cats_cnt );
			
			if( $get_cats_cnt ){
				// カテゴリ毎の件数に、除外記事を含めない（Post Type / Sticky Posts）
				for( $ii = 0; $ii < $get_cnt[$i]; $ii++ )
				{
					if( in_array( $get_cats_cnt[$ii]->ID, ( array ) $sp ) )
						$get_cnt[$i] = $get_cnt[$i] - 1;
				}
			}
		}
	}
	
	for( $i_ele = 0, $cnt_ele = count( $get_cats ); $i_ele < $cnt_ele; $i_ele++ )
	{
		// 0件タームは表示しない場合（post_status処理後の件数を再評価）
		if( $nocnt && $get_cnt[$i_ele] == 0 )
			continue;

		// $_GETで値が取得できないため
		$selected = null;	
		for( $i_lists = 0, $cnt_lists = count( $q_term_id ); $i_lists < $cnt_lists; $i_lists++ )
		{
			if( $q_term_id[$i_lists] )
			{
				if( $q_term_id[$i_lists] == $get_cats[$i_ele]->term_id )
					$selected =' selected="selected"';
			}
		}
		
		// 管理ページ用
		if( isset( $manage_line ) )
		{
			if( $_POST[$cols[2] . $manag_no . "_" . $manage_line ] )
			{
				if( $_POST[$cols[2] . $manag_no . "_" . $manage_line ] == $get_cats[$i_ele]->term_id )
					$selected = ' selected="selected" ';
			}
		}

		// $class_cntが10以下なら0を付ける
		if( $class_cnt < 10 )
			$d_class_cnt = "0" . $class_cnt;
		else
			$d_class_cnt = $class_cnt;

		$cat_cnt = null;
		if( $showcnt == "yes" )
			$cat_cnt = " (" . $get_cnt[$i_ele] . ") ";
				
		$nbsp = null;
		for( $i = 0; $i < $d_class_cnt; $i++)
		{
			$nbsp .= "&nbsp;&nbsp;";
		}
		
		$ret_ele .= "<option id='feas_". esc_attr( $manag_no ) . "_" . esc_attr( $number ) . "_" . esc_attr( $par_no ) . "_" . esc_attr( $form_count ) . "' class='feas_clevel_" . esc_attr( $d_class_cnt ) . "' value='" . esc_attr( $get_cats[$i_ele]->term_id ) . "'" . $selected . ">" . $nbsp . esc_html( $get_cats[$i_ele]->name . $cat_cnt ) . "</option>\n";
		$form_count++;
		
		// 階層の指定がある場合
		if( $check_cnt > 1 )
			$ret_ele .= create_child_op( $get_cats[$i_ele]->term_id, ( $check_cnt - 1 ), ( $class_cnt + 1 ), $q_term_id, $nocnt, $exids, $sticky, $showcnt, $manage_line, $taxonomy, $par_no, $number, $sp, $to );
		// 階層が未指定(=無制限)の場合
		else if( $check_cnt == -1 )
			$ret_ele .= create_child_op( $get_cats[$i_ele]->term_id, $check_cnt , ( $class_cnt + 1 ), $q_term_id, $nocnt, $exids, $sticky, $showcnt, $manage_line, $taxonomy, $par_no, $number, $sp, $to );

	}

	return $ret_ele;
}


/*************************************************/
/*子カテゴリーがあった場合checkbox作成
/*************************************************/
function create_child_check( $par_id = null, $ele_class = null, $check_cnt = 0, $class_cnt = 2, $nocnt = null, $exids = null, $sticky = array(), $showcnt = null, $taxonomy = false, $par_no = 0, $number = 0, $sp = array(), $to = " t.term_id ASC " )
{
	global $wpdb, $manag_no, $form_count, $total_cnt, $feadvns_search_target, $feadvns_include_sticky, $feadvns_search_b_label;
	
	$ret_ele = $ret_chi = null;
	
	if( $check_cnt == 0 || $par_id == null )
		return;

	if( is_null( $showcnt ) && $nocnt )
	{
		// 検索対象のPost Typeを取得
		//$target_pt = db_op_get_value( $feadvns_search_target . $manag_no );
		
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
		$sp = '';
		if( $target_sp != 'yes' )
		{
			$sticky = get_option( 'sticky_posts' );
			if( $sticky != array() )
			{
				for( $i = 0; $cnt = count( $sticky ), $i < $cnt; $i++ )
				{
					$sp .= "'" . esc_sql( $sticky[$i] ) . "'";
					if( $i + 1 < $cnt )
						$sp .= ',';
				}
			}
		}
		
		//  カテゴリーを取得する
		$sql  = " SELECT t.term_id, t.name, count( DISTINCT object_id ) AS cnt FROM $wpdb->terms AS t ";
		$sql .= " LEFT JOIN $wpdb->term_taxonomy AS tt ON t.term_id = tt.term_id";
		$sql .= " LEFT JOIN $wpdb->term_relationships AS tr ON tt.term_taxonomy_id = tr.term_taxonomy_id";
		$sql .= " LEFT JOIN $wpdb->posts ON $wpdb->posts.ID = tr.object_id ";
		$sql .= " WHERE $wpdb->posts.post_type IN( $target_pt )";
		$sql .= " AND tt.parent ='" . $par_id . "'";
		if( $taxonomy ) $sql .= " AND tt.taxonomy='" . esc_sql( $taxonomy ) . "'";
		if( $nocnt ) $sql .= " AND tt.count != 0 ";
		if( $exids ) $sql .= " AND t.term_id NOT IN ( $exids )";
		if( $sp != '' ) $sql .= " AND tr.object_id NOT IN ( $sp )";
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
		$sql  = " SELECT t.term_id, t.name FROM $wpdb->terms AS t ";
		$sql .= " LEFT JOIN $wpdb->term_taxonomy AS tt ON t.term_id = tt.term_id ";
		$sql .= " WHERE tt.parent ='" . $par_id . "' "; 
		if( $taxonomy ) $sql .= " AND tt.taxonomy='" . esc_sql( $taxonomy ) . "' ";
		if( $nocnt ) $sql .= " AND tt.count != 0 ";
		if( $exids ) $sql .= " AND t.term_id NOT IN ( $exids )";
		$sql .= " ORDER BY " . esc_sql( $to ) . " ";
	
		$get_cats = $wpdb->get_results( $sql );
		
		// ターム毎のカウント数を取得
		for( $i = 0; $cnt_ele = count( $get_cats ) ,$i < $cnt_ele; $i++ )
		{
			$sql  = null;
			$sql  = " SELECT p.ID FROM $wpdb->posts AS p";
			$sql .= " LEFT JOIN $wpdb->term_relationships AS tr  ON p.ID = tr.object_id";
			$sql .= " LEFT JOIN $wpdb->term_taxonomy AS tt ON tr.term_taxonomy_id = tt.term_taxonomy_id";
			$sql .= " WHERE tt.term_id = '" . esc_sql( $get_cats[$i]->term_id ) . "'";
			//$sql .=" AND tt.taxonomy='$taxonomy'";
			$sql .= " AND p.post_status = 'publish'";
	
			$get_cats_cnt = $wpdb->get_results( $sql );
	
			$get_cnt[$i] = count( $get_cats_cnt );
			
			if( $get_cats_cnt )
			{
				// カテゴリ毎の件数に、除外記事を含めない（Post Type / Sticky Posts）
				for( $ii = 0; $ii < $get_cnt[$i]; $ii++ )
				{
					if( in_array( $get_cats_cnt[$ii]->ID, ( array ) $sp ) )
						$get_cnt[$i] = $get_cnt[$i] - 1;
				}
			}
		}
	}

	for( $i_ele = 0; $i_ele < $cnt_ele; $i_ele++ )
	{
		// 0件タームは表示しない場合（post_status処理後の件数を再評価）
		if( $nocnt && $get_cnt[$i_ele] == 0 )
			continue;
		
		$checked = null;
		
		if( isset( $_GET['search_element_' . $number . '_' . $total_cnt] ) )
		{
			if( $_GET['search_element_' . $number . '_' . $total_cnt] == $get_cats[$i_ele]->term_id )
				$checked = ' checked="checked"';
		}

		// $class_cntが10以下なら0を付ける
		if( $class_cnt < 10 )
			$d_class_cnt = "0" . $class_cnt;
		else
			$d_class_cnt = $class_cnt;

		$cat_cnt = null;
		if( $showcnt == "yes" )
			$cat_cnt = " (" . $get_cnt[$i_ele] . ") ";
			
		$ret_ele .= "<label id='feas_" . esc_attr( $manag_no ) . "_" . esc_attr( $number ) . "_" . esc_attr( $par_no ) . "_" . esc_attr( $form_count ) . "' class='feas_clevel_" . esc_attr( $d_class_cnt ) . "' ><input type='checkbox' name='search_element_" . esc_attr( $number ) . "_" . $total_cnt . "' value='" . esc_attr( $get_cats[$i_ele]->term_id ) . "' " . $checked . " />" . esc_html( $get_cats[$i_ele]->name . $cat_cnt ) . "</label>\n";
		$total_cnt++;
		$form_count++;
		
		// 階層の指定がある場合
		if( $check_cnt > 1 )
			$ret_chi = create_child_check( $get_cats[$i_ele]->term_id, "feas_clevel_", $number, ( $check_cnt - 1 ), ( $class_cnt + 1 ), $nocnt, $exids, $sticky, $showcnt, $taxonomy, ( $par_no + 1 ), $number, $sp, $to );
		
		// 階層が未指定(=無制限)の場合
		else if( $check_cnt == -1 )
			$ret_chi = create_child_check( $get_cats[$i_ele]->term_id, "feas_clevel_", $number, $check_cnt, ( $class_cnt + 1 ), $nocnt, $exids, $sticky, $showcnt, $taxonomy, $par_no, $number, $sp, $to );
		
		// 生成されたチェックボックスを格納
		if( isset( $ret_chi ) )
			$ret_ele .= $ret_chi;
	}
	
	return $ret_ele;
}

/*************************************************/
/*子カテゴリーがあった場合radiobutton作成
/*************************************************/
function create_child_radio( $par_id = null, $ele_class = null, $check_cnt = 0, $class_cnt = 2, $nocnt, $exids, $sticky, $showcnt, $taxonomy = false, $par_no, $number = 0, $sp = array(), $to = " t.term_id ASC " )
{
	global $wpdb, $manag_no, $form_count, $feadvns_search_target, $feadvns_include_sticky, $feadvns_search_b_label;

	if( $par_id == null )
		return;
	
	if( is_null( $showcnt ) && $nocnt )
	{ // カウント非表示
		// 検索対象のPost Typeを取得
		//$target_pt = db_op_get_value( $feadvns_search_target . $manag_no );
		
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
		$sp = '';
		if( $target_sp != 'yes' ){
			$sticky = get_option( 'sticky_posts' );
			if( $sticky != array() ){
				for( $i = 0; $cnt = count( $sticky ), $i < $cnt; $i++ )
				{
					$sp .= '"' . $sticky[$i] . '"';
					if( $i + 1 < $cnt )
						$sp .= ',';
				}
			}
		}
		
		// カテゴリーを取得する
		$sql  = " SELECT t.term_id, t.name, count( DISTINCT object_id ) AS cnt FROM $wpdb->terms AS t";
		$sql .= " LEFT JOIN $wpdb->term_taxonomy AS tt ON t.term_id = tt.term_id";
		$sql .= " LEFT JOIN $wpdb->term_relationships AS tr ON tt.term_taxonomy_id = tr.term_taxonomy_id";
		$sql .= " LEFT JOIN $wpdb->posts ON $wpdb->posts.ID = tr.object_id";
		$sql .= " WHERE $wpdb->posts.post_type IN( $target_pt )";
		$sql .= " AND tt.parent ='" . $par_id . "'";
		if( $taxonomy ) $sql .= " AND tt.taxonomy='" . esc_sql( $taxonomy ) . "'";
		if( $nocnt ) $sql .= " AND tt.count != 0 ";
		if( $exids ) $sql .= " AND t.term_id NOT IN ( $exids )";
		if( $sp != '' ) $sql .= " AND tr.object_id NOT IN ( $sp )";
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
		$sql  = " SELECT t.term_id, t.name FROM $wpdb->terms AS t ";
		$sql .= " LEFT JOIN $wpdb->term_taxonomy AS tt ON t.term_id = tt.term_id ";
		$sql .= " WHERE tt.parent = '" . $par_id . "' "; 
		if( $taxonomy ) $sql .= " AND tt.taxonomy='" . esc_sql( $taxonomy ) . "' ";
		if( $nocnt ) $sql .= " AND tt.count != 0 ";
		if( $exids ) $sql .= " AND t.term_id NOT IN ( $exids ) ";
		$sql .= " ORDER BY " . esc_sql( $to ) . " ";
	
		$get_cats = $wpdb->get_results( $sql );
		
		// ターム毎のカウント数を取得
		for( $i = 0; $cnt_ele = count( $get_cats ) ,$i < $cnt_ele; $i++ )
		{
			$sql  = null;
			$sql  = " SELECT p.ID FROM $wpdb->posts AS p";
			$sql .= " LEFT JOIN $wpdb->term_relationships AS tr  ON p.ID = tr.object_id ";
			$sql .= " LEFT JOIN $wpdb->term_taxonomy AS tt ON tr.term_taxonomy_id = tt.term_taxonomy_id";
			$sql .= " WHERE tt.term_id ='" . esc_sql( $get_cats[$i]->term_id ) . "'";
			//$sql .=" AND tt.taxonomy='$taxonomy'";
			$sql .= " AND p.post_status = 'publish' ";
	
			$get_cats_cnt = $wpdb->get_results( $sql );
	
			$get_cnt[$i] = count( $get_cats_cnt );
			
			if( $get_cats_cnt )
			{
				// カテゴリ毎の件数に、除外記事を含めない（Post Type / Sticky Posts）
				for( $ii = 0; $ii < $get_cnt[$i]; $ii++ )
				{
					if( in_array( $get_cats_cnt[$ii]->ID , ( array ) $sp ) )
						$get_cnt[$i] = $get_cnt[$i] - 1;
				}
			}
		}
	}
	
	$ret_ele = null;
	
	for( $i_ele = 0, $cnt_ele = count( $get_cats ); $i_ele < $cnt_ele; $i_ele++ )
	{
		// 0件タームは表示しない場合（post_status処理後の件数を再評価）
		if( $nocnt && $get_cnt[$i_ele] == 0 )
			continue;
			
		$checked = null;
		
		if( isset( $_GET['search_element_' . $number ] ) )
		{
			if( $_GET['search_element_' . $number] == $get_cats[$i_ele]->term_id )
				$checked = ' checked="checked"';
		}

		//$class_cntが10以下なら0を付ける
		if( $class_cnt < 10 )
			$d_class_cnt = "0" . $class_cnt;
		else
			$d_class_cnt = $class_cnt;
		
		$cat_cnt = null;
		if( $showcnt == "yes" )
			$cat_cnt = " (" . $get_cnt[$i_ele] . ") ";

		$ret_ele .= "<label id='feas_" . esc_attr( $manag_no ) . "_" . esc_attr( $number ) . "_" . esc_attr( $par_no ) . "_" . esc_attr( $form_count ) . "' class='feas_clevel_" . esc_attr( $d_class_cnt ) . "' ><input type='radio' name='search_element_" . esc_attr( $number ) . "' value='" . esc_attr( $get_cats[$i_ele]->term_id ) . "' " . $checked . " />" . esc_html( $get_cats[$i_ele]->name . $cat_cnt ) . "</label>";
		$form_count++;

		// 階層の指定がある場合
		if( $check_cnt >1 )
			$ret_ele .= create_child_radio( $get_cats[$i_ele]->term_id, "feas_clevel_", $number, ( $check_cnt - 1 ), ( $class_cnt + 1 ), $nocnt, $exids, $sticky, $showcnt, $taxonomy, $par_no, $number, $sp, $to );
		// 階層が未指定(=無制限)の場合
		else if( $check_cnt == -1 )
			$ret_ele .= create_child_radio( $get_cats[$i_ele]->term_id, "feas_clevel_", $number, $check_cnt, ( $class_cnt + 1 ), $nocnt ,$exids, $sticky, $showcnt, $taxonomy, $par_no, $number, $sp, $to );
	}

	return $ret_ele;
}

/*************************************************/
/*子カテゴリー検索にチェックが合った場合に子カテゴリを取得する用
/*************************************************/
function get_cat_chi_ids( $par_id = null )
{
	global $wpdb;

	if( $par_id == null )
		return;

	$ret_ids = array();

	$sql  = " SELECT term_taxonomy_id FROM $wpdb->term_taxonomy";
	$sql .= " WHERE parent = " . esc_sql( $par_id );
	$get_ids = $wpdb->get_results( $sql );

	if( isset( $get_ids[0]->term_taxonomy_id) == true )
	{
		for( $i = 0, $cnt = count( $get_ids ); $i < $cnt; $i++ )
		{
			$ret_ids[] = $get_ids[$i]->term_taxonomy_id;
	
			$get_chi_ids = get_cat_chi_ids( $get_ids[$i]->term_taxonomy_id );
	
			for( $is =0, $cnt_s = count( $get_chi_ids ); $is < $cnt_s; $is++ )
			{
				$ret_ids[] = $get_chi_ids[ $is ];
			}
		}
	}
	return $ret_ids;
} 

/////////////////////////////////////////////////////////////////
//view側
/////////////////////////////////////////////////////////////////

/*************************************************/
/*optionsの値を取得
/*************************************************/
function data_to_post( $element_name = null )
{
	if( isset( $_POST[$element_name] ) )
	{
		$_POST[$element_name] = str_replace( "'", "\"", stripslashes( $_POST[$element_name] ) );

		return $_POST[$element_name];
	}
	else
		return null;
}

/*************************************************/
/*	post-typeを取得
/*************************************************/
function feas_posttype_lists( $manag_no = 0 )
{
	global $wp_version, $feadvns_search_target;

	$ret = null;
	$target_pt = array();
	
	// DBに登録された対象post_typeの値を取得
	$target_pt = db_op_get_value( $feadvns_search_target . $manag_no );
	$target_pt = str_replace( "'", "", $target_pt );
	$target_pt = explode( ",", $target_pt );
	
	if( $wp_version >= '3.0' )
	{
		$args   = array( 'public'  => true );
		$output = 'objects';
		$ptlist = get_post_types( $args, $output );
		
		foreach( $ptlist as $pt )
		{
			$pt_checked = '';
			if( in_array( $pt->name, $target_pt ) )
				$pt_checked = ' checked="checked"';
					
			$ret .= "<label><input type='checkbox' ";
			$ret .= "name='" . esc_attr( $feadvns_search_target . $manag_no ) . "[]' ";
			$ret .= "value='" . esc_attr( $pt->name ) . "' ";
			$ret .= $pt_checked . " /> ";
			$ret .= esc_html( $pt->label );
			$ret .= "（" . esc_html( $pt->name ) . "）</label>"; 
		}	

	} else { // 2.7以下
		
		$ptlist     = array();
		$pt_checked = array();
		
		$ptlist[0]['name']  = 'post';
		$ptlist[0]['label'] = '投稿';
		$ptlist[1]['name']  = 'page';
		$ptlist[1]['label'] = '固定ページ';
		$ptlist[2]['name']  = 'attachment';
		$ptlist[2]['label'] = '添付ファイル';
		
		for( $i = 0; $i < count( $ptlist ); $i++ )
		{
			if( in_array( $ptlist[$i]['name'], $target_pt ) )
				$pt_checked[$i] = ' checked="checked"';
					
			$ret .= "<label><input type='checkbox' ";
			$ret .= "name='" . esc_attr( $feadvns_search_target . $manag_no ) . "[]' ";
			$ret .= "value='" . esc_attr( $ptlist[$i]['name'] ) . "' ";
			$ret .= $pt_checked[$i] . " /> ";
			$ret .= esc_html( $ptlist[$i]['label'] );
			$ret .= "（" . esc_html( $ptlist[$i]['name'] ) . "）</label>"; 
		}
	}
	
	print( $ret );
}

function feas_delete_transient_all(){
	global $cols,$wpdb;
	/*
	$sql = "SELECT option_name FROM `$wpdb->options` WHERE `option_name` LIKE '_transient_feadvns_cache_number_%'";
	$get_cane_name = $wpdb->get_results($sql);
	
	if(is_array($get_cane_name)){
		$return = array();
		foreach($get_cane_name as $key){
			preg_match('/\d*$/',$key->option_name,$matches);
			delete_transient( $cols[23].$matches[0] );
			$return[] = $matches[0];
		}
	}*/
	$sql = "DELETE FROM `$wpdb->options` WHERE `option_name` LIKE '_transient_feadvns_cache_number_%' OR `option_name` LIKE '_transient_timeout_feadvns_cache_number_%'";
	if( false === $wpdb->query( $sql ) )
		$return = 'BDエラー';
	else
		$return = 'キャッシュを削除しました';
		
	return $return;
}
/*
function feas_delete_transient() {
	global $cols,$feadvns_max_page;
	$return = array();
	$get_form_max = db_op_get_value( $feadvns_max_page );
	$get_form_max++;
	for($i = 0 ; $i <= $get_form_max ; $i++){
		if(get_transient($cols[23].$i)){
			delete_transient($cols[23].$i);
			$return[] = $i;
		}
	}
	return $return;
}
add_action('wp_insert_post','feas_delete_transient');
add_action('edit_category','feas_delete_transient');
add_action('create_category','feas_delete_transient');*/

function feas_fix_pagenation( $url )
{
	if ( 'page' != get_option( 'show_on_front' ) )
		return $url;
	
	//$fixed_paged = get_query_var('page');
	
	$fixed_url = add_query_arg( array(
		'paged' => 2
	), $url );
	
	return $fixed_url;
}
//add_filter( 'get_pagenum_link', 'feas_fix_pagenation' );

function feas_add_query_vars_filter( $vars )
{
  $vars[] = "csp";
  return $vars;
}
add_filter( 'query_vars', 'feas_add_query_vars_filter' );

/*************************************************/
/*	Twenty Fifteenで「最近の投稿」ウィジェット使用時にFEASが効いてしまうので回避
/*************************************************/
function feas_remove_filters( $title ){
	
	remove_filter( 'posts_where', 'search_where_add' );
	
	return $title;
}
// remove_filter後、単純にタイトルだけ返す
add_action( 'widget_title', 'feas_remove_filters' );

/*************************************************/
/*	カテゴリ/タームの階層構造をインデント表示するoptionタグを生成
/*************************************************/
function feas_get_hierarchical_term_list( $args = '' ) {
			
	global $feadvns_default_cat, $cols;
	
	$list_html  = '';
	
	$defaults = array(
		'manag_no'  => $manag_no,
		'counter'   => 0,
		'parent'    => 0,
		'tax_name'  => 'category',
		'tax_label' => 'カテゴリー',
		'depth'     => 0,
		'echo'      => true,
	);
	$args = wp_parse_args( $args, $defaults );
		
	if ( 'post_format' == $args['tax_name'] )
		return;
	if ( -1 != $args['counter'] && 'post_tag' == $args['tax_name'] )
		return;								
	
	$term_args = array(
		'taxonomy'   => $args['tax_name'],
		'hide_empty' => 0,
		'parent'     => $args['parent'],
		'orderby'    => 'term_id',
		'order'      => 'ASC',
		);
	$terms = get_categories( $term_args );

	if ( $terms ) :
	
		// -1 = 全体設定の場合
		if ( -1 == $args['counter'] ) {
			// DBに登録されたデフォルトカテゴリ値（ID）を取得
			$saved_val = db_op_get_value( $feadvns_default_cat . $args['manag_no'] );
		} else {
			//$param_num  = $cols[2] . $args['manag_no'] . "_" . $args['counter'];
			$saved_val = db_op_get_value( $cols[2] . $args['manag_no'] . '_' . $args['counter'] );
			echo '<pre>';
			var_dump($saved_val);
			echo '</pre>';
		}
			
		// 全体設定の場合はトップカテゴリ/タームを表示しない
		if ( 0 == $args['parent'] && -1 != $args['counter'] ) {
			
			$selected = '';
			if ( 'par_' . $args['tax_name'] == $saved_val ) {
				$selected = ' selected="selected"';
			}
			
			$list_html = '<option value="par_' . esc_attr( $args['tax_name'] ) . '"' . $selected . '>' .  esc_html( $args['tax_label'] ) . ' (top)</option>';
		}

		
		foreach ( $terms as $term ) :
		
			$selected = '';
			if ( $term->term_id == $saved_val ) {
				$selected = ' selected="selected"';
			}
			
			$anc     = array();
			$nbsp    = '';
			$cnt_anc = 0;
			
			// 階層に応じてインデント表示
			if ( 0 != $args['depth'] ) {
				for ( $i_tab = 0; $i_tab < $args['depth']; $i_tab++ ) {
					$nbsp .= '&nbsp;&nbsp;';
				}
			}
			$list_html .= '<option value="' . esc_attr( $term->term_id ) . '"' . $selected . '>' . $nbsp . esc_html( $term->name ) . '</option>';
			
			// 子ターム以下を再帰生成
			$args2 = array(
				'manag_no'  => $args['manag_no'],
				'counter'   => $args['counter'],
				'parent'    => $term->term_id,
				'tax_name'  => $args['tax_name'],
				'tax_label' => $args['tax_label'],
				'depth'     => $args['depth'] + 1,
				'echo'      => false,
			);
			$list_html .= feas_get_hierarchical_term_list( $args2 );
			
		endforeach;
	
	endif;
	
	if ( true == $args['echo'] ) {
		echo $list_html;
	} else {
		return $list_html;
	}
}

/*************************************************/
/*	プラグインのアップデート通知箇所にメッセージを追記
/*************************************************/
function feas_add_message_on_plugins_page( $plugin_file, $plugin_data, $status ) {
	global $wp_list_table;
	
	if ( 'Upgrade' != $status ) {
		return;
	}
		
	list( $columns, $hidden ) = $wp_list_table->get_column_info();
	$colspan = count( $columns );
	$colspan = $colspan - 2;
?>
<tr class="active update">
	<th scope="row" class="check-column"></th>
	<td class="plugin-title column-primary"></td>
	<td colspan="<?php echo esc_attr( $colspan ); ?>">
		<p class="arrow_box"><b>ダッシュボード</b> > <b>更新</b> から更新してください．</p>	
	</td>
	<style>
	.arrow_box {
		position: relative;
		background: #ffdcdc;
		border: 4px solid #ffdcdc;
		padding: 0.2em !important;
	}
	.arrow_box:after, .arrow_box:before {
		top: 100%;
		left: 50%;
		border: solid transparent;
		content: " ";
		height: 0;
		width: 0;
		position: absolute;
		pointer-events: none;
	}
	
	.arrow_box:after {
		border-color: rgba(255, 220, 220, 0);
		border-top-color: #ffdcdc;
		border-width: 5px;
		margin-left: -5px;
	}
	.arrow_box:before {
		border-color: rgba(255, 220, 220, 0);
		border-top-color: #ffdcdc;
		border-width: 11px;
		margin-left: -11px;
	}
	</style>
</tr>
		
<?php
}
add_action( 'after_plugin_row_fe-advanced-search/plugin.php', 'feas_add_message_on_plugins_page', 10, 3 );

