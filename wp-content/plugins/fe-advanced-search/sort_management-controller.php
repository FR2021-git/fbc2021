<?php

//////////////////////////////////////////////
//ORDER BY時に呼ばれる
//////////////////////////////////////////////

	// 作成済みフォームの検索フォーム数を取得
	$get_form_max = db_op_get_value( $feadvns_max_page );
	
	// 何行表示するか(初期値)
	$line_cnt = 1;

	// 現在のページ取得
	if( isset( $_POST['c_order_number'] ) == true && $_POST['c_order_number'] != null )
		$manag_order_no = $_POST['c_order_number'];
	else
		$manag_order_no = 0;

	// 表示ラインの検索キー
	$line_key  = $feadvns_max_line_order . $manag_order_no;
	$disp_line = db_op_get_value( $line_key );

	if( isset( $disp_line ) == true && $disp_line != null ) {
		$line_cnt = $disp_line;
	} else {
		db_op_insert_value( $line_key, $line_cnt );
	}

	// DB保存処理
	// ラインが変化していたら処理
	if( isset( $_POST['line_action'] ) )
	{
		$save_line_number = null;

		if( $_POST['line_action'] == "add_line" )
		{
			// 一個ラインを増やす
			// 新規
			$save_line_number = $line_cnt + 1;

			if( db_op_get_value( $line_key ) == null )
				db_op_insert_value( $line_key, $save_line_number );
			else // 更新
				db_op_update_value( $line_key, $save_line_number );

			$line_cnt = $save_line_number;
		}
	}
	
	// 消去関係
	$check_del = '';
	for( $i = 0; $i < $line_cnt; $i++ )
	{
		if( isset( $_POST[$cols_order[3] . $manag_order_no . "_" . $i] ) && $_POST[$cols_order[3] . $manag_order_no . "_" . $i] == "del" )
			$check_del = "check";
	}

	// 消去にチェックされていたら処理
	if( $check_del != null )
		$line_cnt = check_del_line_order( $manag_order_no, $line_cnt );

	// カテゴリー取得
	$sql  = " SELECT $wpdb->term_taxonomy.term_id, name FROM $wpdb->terms ";
	$sql .= " LEFT JOIN $wpdb->term_taxonomy ON $wpdb->terms.term_id = $wpdb->term_taxonomy.term_id ";
	$sql .= " WHERE $wpdb->term_taxonomy.taxonomy='category' ";
	$get_cats = $wpdb->get_results( $sql );

	// ソート
	$sort_cat[0] = new StdClass();
	$sort_cat[0]->term_id = "par_cat";
	$sort_cat[0]->name = "トップカテゴリ（ID = 0）";
	for( $i_sort = 0, $cnt_sort = count( $get_cats ); $i_sort < $cnt_sort; $i_sort++ )
	{
		$max_cat = count( $sort_cat );
		$sort_cat[$max_cat] = new StdClass();
		$sort_cat[$max_cat]->term_id = $get_cats[$i_sort]->term_id;
		$sort_cat[$max_cat]->name = $get_cats[$i_sort]->name;
	}
	$get_cats = $sort_cat;

	// postmetaを取得
	$sql  = " SELECT DISTINCT meta_key FROM $wpdb->postmeta";
	$sql .= " WHERE meta_key NOT LIKE '@_%' ESCAPE '@' ";  // WPが自動生成するアンダースコアから始まるpostmetaを除外
	//$sql .=" WHERE meta_key !='_edit_lock'";
	//$sql .=" AND meta_key !='_edit_last'";
	$get_metas = $wpdb->get_results( $sql );
	
	// DB検索キーを格納
	$db_search_key_sort = array();

	for( $i_line = 0; $i_line < $line_cnt; $i_line++ )
	{
		if( isset( $_POST["feadvns_order_sort_" . $manag_order_no . "_" . $i_line] ) )
			$db_search_key_sort[$i_line]['order'] = $_POST["feadvns_order_sort_" . $manag_order_no . "_" . $i_line];
		else 
			$db_search_key_sort[$i_line]['order'] = null;
			
		$db_search_key_sort[$i_line]['line'] = $i_line;
	}

	// ソート（宮澤）
	$order_date = array();
	foreach ( $db_search_key_sort as $v )
	{
		$order_date[] = $v['order'];
	}
	array_multisort( $order_date, SORT_ASC, $db_search_key_sort );
	
	// $_POSTに代入する値を作成（宮澤）
	$sort_data = array();
	for( $i_p = 0, $cnt_p = count( $db_search_key_sort ); $i_p < $cnt_p; $i_p++ )
	{
		// col
		for( $i_cols = 0, $cnt_cols = count( $cols_order ); $i_cols < $cnt_cols; $i_cols++ )
		{
			if( isset( $_POST[$cols_order[$i_cols] . $manag_order_no . "_" . $db_search_key_sort[$i_p]['line']] ) )
				$sort_data[$cols_order[$i_cols] . $manag_order_no . "_" . $i_p] = $_POST[$cols_order[$i_cols] . $manag_order_no . "_" . $db_search_key_sort[$i_p]['line']];
		}
	}

	// $_POSTへ代入（宮澤）
	$s_keys = array_keys( $sort_data );
	for( $i_cp = 0, $cnt_cp = count( $s_keys ); $i_cp < $cnt_cp; $i_cp++ )
	{
		$_POST[$s_keys[$i_cp]] = $sort_data[$s_keys[$i_cp]];
	}	

	for( $i_line = 0; $i_line < $line_cnt; $i_line++ )
	{
		// col
		for( $i_cols = 0, $cnt_cols = count( $cols_order ); $i_cols < $cnt_cols; $i_cols++ )
		{
			// OPのキー作成
			$s_key = $cols_order[$i_cols] . $manag_order_no . "_" . $i_line;

			// DB検索キーを格納
			$db_search_key[] = $s_key;
		}
	}

	// DB保存処理
	// 各種設定
	for( $i_s_key = 0, $cnt_s_key = count( $db_search_key ); $i_s_key < $cnt_s_key; $i_s_key++ )
	{
		// キーが登録されているかチェック
		$check_id = db_op_field_check( $db_search_key[$i_s_key] );

		if( $check_id == null )
		{
			if( isset($_POST['ac']) == true && $_POST['ac'] == "update" )
				// DB新規登録
				db_op_insert( $db_search_key[$i_s_key] );
		
		} else {
			
			if( isset( $_POST['ac'] ) == true && $_POST['ac'] == "update" )
				// DB更新
				db_op_update( $db_search_key[$i_s_key] );
		}

		// 初期値取得
		if( !isset( $_POST[$db_search_key[$i_s_key]] ) )
			$_POST[$db_search_key[$i_s_key]] = db_op_get_data( $check_id );
	}
	
	if( isset( $_POST['ac'] ) == true && $_POST['ac'] == "update" )
	{	
		// プレビューへのCSS適用
		$check = db_op_field_check( $pv_css . $manag_no );
		if( isset( $_POST[$pv_css . $manag_no] ) )
		{
			if( $check == null )
				db_op_insert_value($pv_css .$manag_no, $_POST[$pv_css . $manag_no]);
			else
			{
				if( isset( $_POST[$pv_css . $manag_no] ) )
					db_op_update_value( $pv_css . $manag_no, $_POST[$pv_css . $manag_no] );
				else
					db_op_update_value( $pv_css . $manag_no, 'no');
			}
		}
	}

	//フォームを取得（宮澤）
	//search_key
	//$form_max_name =$feadvns_max_page ."max";

	//formのMax値を取得（宮澤）
	//$get_form_max =db_op_get_value($form_max_name);
/*
	if($get_form_max ==null){
		db_op_insert_value($form_max_name, 0);
		$get_form_max =0;
	}
*/

	//（宮澤）ここまで
	
	if( isset( $_POST['c_order_number']) == true )
	{
		if( $_POST['c_order_number'] == "new" )
		{
			$get_form_max = ( $get_form_max + 1 );
			db_op_update_value( $form_max_name, $get_form_max );
			$_POST['c_order_number'] = $get_form_max;
		
		} else if( $_POST['c_order_number'] == "del" ){
			
			if( $get_form_max > 0 )
			{
				$get_form_max = ( $get_form_max - 1 );
				$_POST['c_order_number'] = $get_form_max;
			}
		}
	}
?>