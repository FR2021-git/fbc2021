<?php
	//何行表示するか(初期値)
	$line_cnt =1;

	//以下、feas_set_manag_no()に移動
	//////////// ここから ////////////
/*
	//formの数を取得
	$count_form =db_op_get_value($feadvns_max_page ."max");
	
	//現在のページ取得
	if( isset($_POST['c_form_number']) == true ){
		if( $_POST['c_form_number'] == "new" )
			$manag_no = ( $count_form+1 );
		else
			$manag_no = $_POST['c_form_number'];
	}
	else{
		$manag_no =0;
	}
*/
	//////////// ここまで ////////////

	//表示ラインの検索キー
	$line_key =$feadvns_max_line .$manag_no;
	$disp_line =db_op_get_value($line_key);

	if(isset($disp_line) ==true && $disp_line !=null){
		$line_cnt =$disp_line;
	}

	//DB保存処理
	//ラインが変化していたら処理
	if(isset($_POST['line_action'])){
		$save_line_number =null;

		if($_POST['line_action'] =="add_line"){
			//一個ラインを増やす。
			//新規
			$save_line_number =$line_cnt +1;

			if(db_op_get_value($line_key) ==null){
				db_op_insert_value($line_key, $save_line_number);
			}
			else{ //更新
				db_op_update_value($line_key, $save_line_number);
			}

			$line_cnt =$save_line_number;
		}
	}

	$check_del =null;
	for($i =0; $i <$line_cnt; $i++){
			if( (isset($_POST[$cols[9] .$manag_no ."_" .$i])) && ($_POST[$cols[9] .$manag_no ."_" .$i] =="del")){
				$check_del ="check";
			}
	}

	//消去にチェックされていたら処理
	if($check_del !=null)
		$line_cnt =check_del_line($manag_no, $line_cnt);
	
	//  オプション値によるcolsの調整
	for($i =0; $i <$line_cnt; $i++){
		
		//  フリーワード検索のターゲットを半角カンマ区切りで格納	
		if( isset( $_POST[ $cols[13] .$manag_no ."_" .$i ] ) ){

			$kw_cnt = count( $_POST[$cols[13] .$manag_no ."_" .$i ] );
			$kwds_target = null;

			for($ii =0; $ii < 6; $ii++){
					//if( $_POST[$cols[13] .$manag_no ."_" .$i ][$ii] == '' ){
					if( !isset($_POST[$cols[13] .$manag_no ."_" .$i ][$ii]) ){
						$kwds_target .= "0";
					}else{
						$kwds_target .= esc_sql($_POST[$cols[13] .$manag_no ."_" .$i ][$ii]);
					}

					if( $ii +1 != 6)
						$kwds_target  .= ",";
			}
			$_POST[ $cols[13] .$manag_no ."_" .$i ]  = $kwds_target;
		}
		else {
			$_POST[ $cols[13] .$manag_no ."_" .$i ]  = ''; //チェックがすべて外れていた時
		}
		
		//  空のカテゴリは表示するorしない
		if( isset( $_POST[$cols[14] .$manag_no ."_" .$i] ) == false )
			$_POST[ $cols[14] .$manag_no ."_" .$i ] = 'yes';
		
		//  フリーワード検索時のゆらぎ検索指定：チェックが付いている時 'no'
		if( isset( $_POST[$cols[15] .$manag_no ."_" .$i] ) == false )
			$_POST[ $cols[15] .$manag_no ."_" .$i ] = 'yes';
		
		//  カスタムフィールド検索時、数値を千の位毎に半角カンマで区切る指定：チェックが付いている時 'yes'
		if( isset( $_POST[$cols[18] .$manag_no ."_" .$i] ) == false )
			$_POST[ $cols[18] .$manag_no ."_" .$i ] = 'no';
			
		//  Ajaxフィルタリング
		if( isset( $_POST[$cols[19] .$manag_no ."_" .$i] ) == false )
			$_POST[ $cols[19] .$manag_no ."_" .$i ] = 'yes';
			
		//メタキー指定検索
		if( (isset($_POST[$cols[20] .$manag_no ."_" .$i])) && ($_POST[$cols[20] .$manag_no ."_" .$i] != '') ){
			$_POST[ $cols[21] .$manag_no ."_" .$i ] = 'no';
		}else{
			$_POST[ $cols[21] .$manag_no ."_" .$i ] = false;
		}
			
		if( isset( $_POST[$cols[22] .$manag_no ."_" .$i] ) == false )
			$_POST[ $cols[22] .$manag_no ."_" .$i ] = false;
	}

	//カテゴリー取得
	//$sql  =" SELECT " .$wpdb->term_taxonomy .".term_id, name , parent FROM " .$wpdb->terms;
	$sql  =" SELECT * FROM " .$wpdb->terms;
	$sql .=" LEFT JOIN " .$wpdb->term_taxonomy ." ON " .$wpdb->terms .".term_id = " .$wpdb->term_taxonomy .".term_id";
	$sql .=" WHERE " .$wpdb->term_taxonomy .".taxonomy='category'";
	$get_cats =$wpdb->get_results($sql);
	
	
	// カスタムタクソノミー取得
	global $wp_version;
	if( $wp_version >= '3.0' ){
		$args = array(
			'public'   => true,
			'_builtin'  => false
		);
		$taxonomies = get_taxonomies( $args ,'objects' ); 
		//$get_terms = array();
		$cnt = 0;
		foreach ( $taxonomies as $taxonomy ) {
			/*
			$sql  =" SELECT " .$wpdb->term_taxonomy .".term_id, name, taxonomy FROM " .$wpdb->terms;
			$sql .=" LEFT JOIN " .$wpdb->term_taxonomy ." ON " .$wpdb->terms .".term_id = " .$wpdb->term_taxonomy .".term_id";
			$sql .=" WHERE " .$wpdb->term_taxonomy .".taxonomy = '" . $taxonomy->name . "' ";
			*/
	
			$get_terms[$cnt]['label'] = $taxonomy->label;
			$get_terms[$cnt]['name'] = $taxonomy->name;
	
			$termlist = get_terms( $taxonomy->name, array( 'hide_empty' => 0, 'get' => 'all' ) );
			
			//foreach( $termlist as $tlist ){
				//$get_terms[$cnt]['terms'][] = $tlist->name;
				//$get_terms[$cnt][] = $tlist->name;
			//}
	
			$cnt++;
		}
	}
	
	//ソート
	$sort_cat[0] = new StdClass();
	$sort_cat[0]->term_id ="par_cat";
	$sort_cat[0]->name ="トップカテゴリ（ID = 0）";
	for($i_sort =0, $cnt_sort =count($get_cats); $i_sort <$cnt_sort; $i_sort++){
		$max_cat =count($sort_cat);

		if($get_cats[$i_sort]->parent == 0){
			$sort_cat[$max_cat] = new StdClass();
			$sort_cat[$max_cat]->term_id =$get_cats[$i_sort]->term_id;
			$sort_cat[$max_cat]->name =$get_cats[$i_sort]->name;
		}
	}
	$get_cats =$sort_cat;


	//postmetaを取得
	$sql  =" SELECT DISTINCT meta_key FROM " .$wpdb->postmeta;
	$sql .=" WHERE meta_key NOT LIKE '@_%' ESCAPE '@' ";  //  WPが自動生成するアンダースコアから始まるpostmetaを除外
	//$sql .=" WHERE meta_key !='_edit_lock'";
	//$sql .=" AND meta_key !='_edit_last'";
	$get_metas = $wpdb->get_results($sql);

	//DB検索キーを格納
	$db_search_key_sort =array();

	for($i_line =0; $i_line <$line_cnt; $i_line++){
		if(isset($_POST["feadvns_disp_number_" .$manag_no ."_" .$i_line])){
			$db_search_key_sort[$i_line]['order'] =$_POST["feadvns_disp_number_" .$manag_no ."_" .$i_line]; 
		}else{
			$db_search_key_sort[$i_line]['order'] = null;
		}
		$db_search_key_sort[$i_line]['line'] =$i_line;
	}

	//ソート
	$order_date = array();
	foreach ($db_search_key_sort as $v){
		$order_date[] = $v['order'];
	}
	array_multisort($order_date, SORT_ASC, $db_search_key_sort);

	//$_POSTに代入する値を作成
	$sort_data =array();
	for($i_p =0, $cnt_p =count($db_search_key_sort); $i_p <$cnt_p; $i_p++){
		//col
		for($i_cols =0, $cnt_cols =count($cols); $i_cols <$cnt_cols; $i_cols++){
			if(isset($_POST[$cols[$i_cols] .$manag_no ."_" .$db_search_key_sort[$i_p]['line']])){
				$sort_data[$cols[$i_cols] .$manag_no ."_" .$i_p] =$_POST[$cols[$i_cols] .$manag_no ."_" .$db_search_key_sort[$i_p]['line']];
			}else{
				$sort_data[$cols[$i_cols] .$manag_no ."_" .$i_p] =null;
			}
		}
	}

	//$_POSTへ代入
	$s_keys =array_keys($sort_data);
	for($i_cp =0, $cnt_cp =count($s_keys); $i_cp <$cnt_cp; $i_cp++){
		$_POST[$s_keys[$i_cp]] = $sort_data[$s_keys[$i_cp]];
	}

	//DB検索キーを格納
	$db_search_key =array();

	for($i_line =0; $i_line <$line_cnt; $i_line++){
		//col
		for($i_cols =0, $cnt_cols =count($cols); $i_cols <$cnt_cols; $i_cols++){
			//OPのキー作成
			$s_key =$cols[$i_cols] .$manag_no ."_" .$i_line;

			//DB検索キーを格納
			$db_search_key[] =$s_key;
		}
	}

	//DB保存処理
	//各種設定
	for($i_s_key =0, $cnt_s_key =count($db_search_key); $i_s_key <$cnt_s_key; $i_s_key++){
		//キーが登録されているかチェック
		$check_id =db_op_field_check($db_search_key[$i_s_key]);

		if($check_id ==null){
			//サブミット押されていたら
			if(isset($_POST['ac']) ==true && $_POST['ac'] =="update" ){
				//DB新規登録
				db_op_insert($db_search_key[$i_s_key]);
			}
		}
		else{
			//サブミット押されていたら
			if(isset($_POST['ac']) ==true && $_POST['ac'] =="update" ){
				//DB更新
				db_op_update($db_search_key[$i_s_key]);
			}
		}

		//初期値取得
		if( !isset( $_POST[$db_search_key[$i_s_key]] ) )
			$_POST[$db_search_key[$i_s_key]] = db_op_get_data( $check_id );

	}

	if( isset( $_POST['ac'] ) == true && $_POST['ac'] == "update" ){
		$check = db_op_field_check( $feadvns_search_b_label . $manag_no );

		if(isset($_POST[$feadvns_search_b_label . $manag_no])){
			if( $check == null ){
				//検索ボタン新規登録
				db_op_insert_value( $feadvns_search_b_label . $manag_no, $_POST[$feadvns_search_b_label . $manag_no] );
			}
			else{
				//検索ボタン更新
				db_op_update_value( $feadvns_search_b_label . $manag_no, $_POST[$feadvns_search_b_label . $manag_no] );
			}
		}

		$check = db_op_field_check( $feadvns_search_b_label . $manag_no . "_before" );
		if(isset($_POST[$feadvns_search_b_label . $manag_no ."_before"])){
			if( $check == null ){
				db_op_insert_value( $feadvns_search_b_label . $manag_no . "_before", $_POST[$feadvns_search_b_label . $manag_no ."_before"] );
			}
			else{
				db_op_update_value($feadvns_search_b_label .$manag_no ."_before", $_POST[$feadvns_search_b_label .$manag_no ."_before"]);
			}
		}

		$check =db_op_field_check($feadvns_search_b_label .$manag_no ."_after");
		if(isset($_POST[$feadvns_search_b_label .$manag_no ."_after"])){
			if($check ==null){
				db_op_insert_value($feadvns_search_b_label .$manag_no ."_after", $_POST[$feadvns_search_b_label .$manag_no ."_after"]);
			}
			else{
				db_op_update_value($feadvns_search_b_label .$manag_no ."_after", $_POST[$feadvns_search_b_label .$manag_no ."_after"]);
			}
		}

		// 結果ソート
		if( isset( $_POST[$feadvns_search_b_label . $manag_no . "_sort"] ) )
		{
			$check = db_op_field_check( $feadvns_search_b_label . $manag_no . "_sort" );
			if( $check == null )
			{
				db_op_insert_value( $feadvns_search_b_label . $manag_no . "_sort", $_POST[$feadvns_search_b_label . $manag_no . "_sort"] );
			
			} else {
				
				db_op_update_value( $feadvns_search_b_label . $manag_no . "_sort", $_POST[$feadvns_search_b_label . $manag_no . "_sort"] );
			}
		}
			
		// プレビューへのCSS適用
		if( isset( $_POST[$pv_css . $manag_no] ) && $_POST[$pv_css . $manag_no] != null )
		{
			$check = db_op_field_check( $pv_css . $manag_no );
			if( $check == null )
				db_op_insert_value( $pv_css . $manag_no, $_POST[$pv_css . $manag_no] );
			else
				db_op_update_value( $pv_css . $manag_no, $_POST[$pv_css . $manag_no] );
		}
		else
			db_op_delete_value( $pv_css . $manag_no );
	}
	
	// 設定ページ上部の全体設定フォーム
	if( isset( $_POST['gs'] ) && $_POST['gs'] == "update" )
	{	
		// フォームの名称登録
		$check = db_op_field_check( $feadvns_search_form_name . $manag_no );
		if( $check == null )
		{
			db_op_insert_value( $feadvns_search_form_name . $manag_no, $_POST[$feadvns_search_form_name . $manag_no] );
		
		} else {
			
			if( isset( $_POST[$feadvns_search_form_name . $manag_no] ) )
				db_op_update_value( $feadvns_search_form_name . $manag_no, $_POST[$feadvns_search_form_name . $manag_no] );
		}
		
		// 検索対象のpost_typeを設定
		db_op_delete_value( $feadvns_search_target . $manag_no );
		if( isset( $_POST[$feadvns_search_target . $manag_no] ) )
		{
			$ptvalue = null;
			$cnt = count( $_POST[$feadvns_search_target . $manag_no] );
			
			for( $i = 0; $i < $cnt; $i++)
			{
				if( ! is_null( $ptvalue ) )
					$ptvalue .= ",";
				//$ptvalue .=  "'" . $_POST[$feadvns_search_target . $manag_no][$i] . "'";
				$ptvalue .=  $_POST[$feadvns_search_target . $manag_no][$i];
			}
			db_op_insert_value( $feadvns_search_target . $manag_no , $ptvalue );
		}
		
		//初期設定カテゴリ
		$check = db_op_field_check( $feadvns_default_cat . $manag_no );
		if( $check == null )
		{
			db_op_insert_value( $feadvns_default_cat .$manag_no, $_POST[$feadvns_default_cat .$manag_no] );
		
		} else {
			
			if( isset( $_POST[$feadvns_default_cat .$manag_no] ) && $_POST[$feadvns_default_cat .$manag_no]  != "")
				db_op_update_value( $feadvns_default_cat .$manag_no, $_POST[$feadvns_default_cat .$manag_no] );
			else
				db_op_delete_value( $feadvns_default_cat .$manag_no );
		}
		
		// 検索結果の並び順 - ターゲット
		$check = db_op_field_check( $feadvns_sort_target .$manag_no );
		if( $check == null ){
			db_op_insert_value( $feadvns_sort_target .$manag_no, $_POST[ $feadvns_sort_target . $manag_no ] );
		}
		else {
			if( isset( $_POST[ $feadvns_sort_target . $manag_no ] ) && $_POST[ $feadvns_sort_target . $manag_no ]  != "" )
					db_op_update_value( $feadvns_sort_target . $manag_no, $_POST[ $feadvns_sort_target . $manag_no ] );
			else
					db_op_delete_value( $feadvns_sort_target . $manag_no );
		}
		
		// 検索結果の並び順 - 昇順降順
		$check = db_op_field_check( $feadvns_sort_order .$manag_no );
		if( $check == null ){
			db_op_insert_value( $feadvns_sort_order .$manag_no, $_POST[ $feadvns_sort_order . $manag_no ] );
		}
		else {
			if( isset( $_POST[ $feadvns_sort_order . $manag_no ] ) && $_POST[ $feadvns_sort_order . $manag_no ]  != "" )
					db_op_update_value( $feadvns_sort_order . $manag_no, $_POST[ $feadvns_sort_order . $manag_no ] );
			else
					db_op_delete_value( $feadvns_sort_order . $manag_no );
		}
		
		// 検索結果の並び順 - カスタムフィールドのキー
		$check = db_op_field_check( $feadvns_sort_target_cfkey .$manag_no );
		if( $check == null ){
			db_op_insert_value( $feadvns_sort_target_cfkey .$manag_no, $_POST[ $feadvns_sort_target_cfkey . $manag_no ] );
		}
		else {
			if( isset( $_POST[ $feadvns_sort_target_cfkey . $manag_no ] ) && $_POST[ $feadvns_sort_target_cfkey . $manag_no ]  != "" )
					db_op_update_value( $feadvns_sort_target_cfkey . $manag_no, $_POST[ $feadvns_sort_target_cfkey . $manag_no ] );
			else
					db_op_delete_value( $feadvns_sort_target_cfkey . $manag_no );
		}
		
		// 検索結果の並び順 - 数値or文字
		$check = db_op_field_check( $feadvns_sort_target_cfkey_as .$manag_no );
		if( $check == null ){
			db_op_insert_value( $feadvns_sort_target_cfkey_as .$manag_no, $_POST[ $feadvns_sort_target_cfkey_as . $manag_no ] );
		}
		else {
			if( isset( $_POST[ $feadvns_sort_target_cfkey_as . $manag_no ] ) && $_POST[ $feadvns_sort_target_cfkey_as . $manag_no ]  != "" )
					db_op_update_value( $feadvns_sort_target_cfkey_as . $manag_no, $_POST[ $feadvns_sort_target_cfkey_as . $manag_no ] );
			else
					db_op_delete_value( $feadvns_sort_target_cfkey_as . $manag_no );
		}
		
		//  検索条件が未指定の場合
		$check = db_op_field_check(  $feadvns_empty_request .$manag_no );
		if( $check ==null ){
			db_op_insert_value( $feadvns_empty_request .$manag_no, $_POST[$feadvns_empty_request .$manag_no] );
		}
		else{
			db_op_update_value( $feadvns_empty_request .$manag_no, $_POST[$feadvns_empty_request .$manag_no] );
		}
		
		//ドロップダウン内に件数を表示する設定
		db_op_delete_value( $feadvns_show_count .$manag_no );
		if( isset( $_POST[$feadvns_show_count .$manag_no] ) ){

			$showcnt = null;
			$showcnt =  $_POST[$feadvns_show_count .$manag_no];
			
			db_op_insert_value( $feadvns_show_count .$manag_no , $showcnt );
		}
		
		//固定記事（Sticky Posts）を検索対象に含む設定
		db_op_delete_value( $feadvns_include_sticky .$manag_no );
		if( isset( $_POST[$feadvns_include_sticky .$manag_no] ) ){

			$target_sp = null;
			$target_sp =  $_POST[$feadvns_include_sticky .$manag_no];
			
			db_op_insert_value( $feadvns_include_sticky .$manag_no , $target_sp );
		}
		
	}


	//検索ボタンのラベル取得
	if( isset( $_POST[$feadvns_search_b_label . $manag_no] ) == false ){
		$s_b_label = "検　索";

		$get_data = db_op_get_value( $feadvns_search_b_label . $manag_no );

		if( isset( $get_data ) == true && $get_data != null )
			$s_b_label = $get_data;

		$_POST[$feadvns_search_b_label . $manag_no] = $s_b_label;
	}

	$_POST[$feadvns_search_b_label .$manag_no ."_before"] = db_op_get_value( $feadvns_search_b_label . $manag_no . "_before" );
	$_POST[$feadvns_search_b_label .$manag_no ."_after"] = db_op_get_value( $feadvns_search_b_label . $manag_no . "_after" );
	$_POST[$feadvns_search_b_label .$manag_no ."_sort"] = db_op_get_value( $feadvns_search_b_label . $manag_no . "_sort" );

	//  作成済みフォームの数（ID）を取得
	$get_form_max = db_op_get_value( $feadvns_max_page );

	//  最初のフォームの場合、ID=0を保存
	if(  $get_form_max == null ||  $get_form_max == "" ){
		db_op_insert_value( $feadvns_max_page, "0" );
		$get_form_max = 0;
	}

	if( isset( $_POST['c_form_number'] ) == true ){
		
		if( $_POST['c_form_number'] == "new" ){
			$get_form_max = ( $get_form_max + 1 );
			db_op_update_value( $feadvns_max_page , $get_form_max );
			$_POST['c_form_number'] = $get_form_max;
		}
		else if( $_POST['c_form_number'] == "del" ){
			if( $get_form_max > 0 ){
				$get_form_max =( $get_form_max -1 );

				db_op_update_value( $feadvns_max_page , $get_form_max );

				$_POST['c_form_number'] = $get_form_max;
			}
		}
	}
	
	
	wp_enqueue_script( 'iframe-auto-height', plugins_url() . '/' . str_replace( basename( __FILE__ ), "", plugin_basename( __FILE__ ) ) . 'jquery.autoheight.js', array( 'jquery' ) );
?>