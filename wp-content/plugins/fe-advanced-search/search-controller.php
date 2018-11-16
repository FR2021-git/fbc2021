<?php
//////////////////////////////////////////////
//検索本体
//////////////////////////////////////////////
function search_where_add( $where ){
	
	global
		$wpdb,
		$wp_query,
		$cols,
		$manag_no,
		$add_where,
		$w_keyword,
		$feadvns_search_target,
		$manag_no,
		$feadvns_default_cat,
		$feadvns_empty_request,
		$feadvns_include_sticky;
		
	//  フォームNo.取得
	if( isset( $_GET[ 'fe_form_no' ] ) == true && is_numeric( $_GET[ 'fe_form_no' ] ) )
		$manag_no = intval( $_GET[ 'fe_form_no' ] );
	else
		$manag_no = 0;
	
	//  検索が実行された場合
	if( isset($_GET[ 'csp' ]) && $_GET[ 'csp' ] == "search_add" && $wp_query->is_main_query() ){
	
		//  条件数（行数）取得
		if( is_array( $_GET['feadvns_max_line_'.$manag_no] ) )
			$max_c_cnt = 1;
		else
			$max_c_cnt = ( $_GET['feadvns_max_line_'.$manag_no] +1 );
	
		$add_where = null;
		$keywords = null;
		
		//  検索対象のpost_typeを取得
		//$target_pt = db_op_get_value( $feadvns_search_target . $manag_no );
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
		
		//  基本SQL文生成
		$where = " AND " . $wpdb->posts .".post_type IN (" . $target_pt . ") AND " . $wpdb->posts .".post_status = 'publish' ";
		
		//  メインデータ取得
		$get_ret_data = create_where_get_data( $max_c_cnt, $keywords );

		//  フリーワード検索とそれ以外を分離処理
		$add_where = $get_ret_data[ 0 ];
		$keywords = $get_ret_data[ 1 ]; 

		$w_keyword = null;
		$kw_keys = array();

		//  各キーワード検索ボックスの行数を取得
		if( is_array( $keywords ) == true )
			$kw_keys = array_keys( $keywords );

		//  フリーワード入力があったら、検索窓の出現回数分キーワード検索を実行	
		for( $i_kw = 0, $kw_cnt = count( $keywords ); $i_kw < $kw_cnt; $i_kw++ ){	
			if( $keywords[ $kw_keys[ $i_kw ]] != null && $keywords[ $kw_keys[ $i_kw ] ] != "" && $keywords[ $kw_keys[ $i_kw ] ] != " " ){
				//  検索対象を取得
				$kwds_target = db_op_get_value( $cols[13] . $manag_no . "_" . $kw_keys[ $i_kw ] );
				//  ゆらぎ：全角半角の区別
				$kwds_yuragi = db_op_get_value( $cols[15] . $manag_no . "_" . $kw_keys[ $i_kw ] );
				
				//  検索実行
				$w_keyword .= create_where_keyword( $keywords[ $kw_keys[ $i_kw ] ] , $kwds_target , $kwds_yuragi , $manag_no , $kw_keys[ $i_kw ] );
				
			}
		}
		
		// search.phpがあった時、検索結果をsearch.phpへ返す為のフラグ
		$wp_query->is_search   = true;
		$wp_query->is_singular = false;
		$wp_query->is_home     = false;
		
		$exid = null;
		
		//  固定記事を検索対象から省く
		$target_sp = db_op_get_value( $feadvns_include_sticky . $manag_no );
		if( $target_sp != 'yes' ){
			$sticky = get_option( 'sticky_posts' );
			if( $sticky != array() ){
				foreach( $sticky as $sticky_id ){
					if( $exid != null )
						$exid .= " AND ";
					$exid .= $wpdb->posts.".ID != " .$sticky_id;
				}
				$exid = " AND ( " . $exid . " ) ";
			}else
				$exid = null;
		}
		
		$cwhere = null;
		
		//  初期設定カテゴリ取得
		$dcat = array();
		$dcat['cat'] = db_op_get_value( $feadvns_default_cat . $manag_no );
		if( $dcat['cat'] != "" ) {
			$cwhere = create_where_single_cat( $dcat );
			$cwhere =  " AND " . $cwhere;
		}
		
		if( $add_where != "" || $w_keyword != null ){
			$ret_where = $where . $add_where . $w_keyword . $exid . $cwhere;
		}
		else {
			//  検索条件が指定されていなかった時に返す内容（初期カテゴリ or 0件）
			$ereq = db_op_get_value( $feadvns_empty_request .$manag_no );
			if( $ereq == 1 ){
				$ret_where = $where . $exid . $cwhere;
			}else
				$ret_where = $where ." AND ( ".$wpdb->posts.".ID = -9999)";
		}
	}
	else
		$ret_where = $where;

	if(!is_admin()){
		//フロントをページ固定にすると結果件数が拾えないため追加
		$sql  =" SELECT count(ID) AS cnt FROM " .$wpdb->posts;
		
		// Unknown column エラーが出ていたので追加。 熊谷 2013/3/8
		//$sql .=" LEFT JOIN " .$wpdb->postmeta ." ON " .$wpdb->posts .".id = " .$wpdb->postmeta .".post_id";
		$sql .=" LEFT JOIN " .$wpdb->term_relationships ." ON " .$wpdb->posts .".ID = " .$wpdb->term_relationships .".object_id";
		$sql .=" LEFT JOIN " .$wpdb->term_taxonomy ." ON " .$wpdb->term_relationships .".term_taxonomy_id = " .$wpdb->term_taxonomy .".term_taxonomy_id";
		$sql .=" LEFT JOIN " .$wpdb->terms ." ON " .$wpdb->term_taxonomy .".term_id = " .$wpdb->terms .".term_id";
		
		$sql .=" WHERE (1=1)" .$ret_where;
		$ret_number = $wpdb->get_results( $sql );
		if( isset( $ret_number[0]->cnt ) )
			$wp_query->found_posts = $ret_number[0]->cnt;
		else
			$wp_query->found_posts = 0;
	}
	
	return $ret_where;
}

//////////////////////////////////////////////
//  where作成のためにデータを取得
//////////////////////////////////////////////
function create_where_get_data( $max_c_cnt, $keywords ){
	global $wpdb, $cols, $manag_no, $feadvns_search_target;

	$get_archive = array();
	$get_pm_elements = array();
	$get_elements = array();
	
	for( $i_num =0, $i_counter =0, $i_cnt_meta =0; $i_num < $max_c_cnt; $i_num++ ){

		$get_ele_value = db_op_get_value( $cols[4] . $manag_no . "_" . $i_num );

		////////////////  アーカイブ検索  ////////////////
		if( db_op_get_value( $cols[2] .$manag_no ."_" .$i_num ) == "archive" ){
			
			//  チェックボックス
			if( $get_ele_value == 3 ){
				
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
	
				$sql  = " SELECT DISTINCT YEAR(post_date) AS `year`, MONTH(post_date) AS `month` FROM " . $wpdb->posts;
				$sql .= " WHERE " . $wpdb->posts . ".post_type IN( $target_pt ) AND " . $wpdb->posts . ".post_status = 'publish'";
				//$sql .= $option_order;
				$cnt_archive = $wpdb->get_results( $sql );
				$get_cat_cnt = count( $cnt_archive );

				for( $check_cnt =0; $check_cnt < $get_cat_cnt; $check_cnt++ ){
					if( isset( $_GET[ "search_element_" . $i_num ."_" . $check_cnt] ) == true ){
						$get_archive[] =  array(
							'date' => esc_sql($_GET[ "search_element_" . $i_num . "_" . $check_cnt ]),
							'range' => ''
						);
					}
				}
			}
			
			//  フリーキーワード
			else if( $get_ele_value ==5 ){
				
				if( isset( $_GET[ 's_keyword_' . $i_num ] ) == true )
					//$keywords .=" " .$_GET['s_keyword_' .$i_num];
					$keywords[ $i_num ] = $_GET[ 's_keyword_' . $i_num ];
			}
			
			//  リストボックス
			else if( $get_ele_value == 2 ){
				
				if( isset( $_GET[ 'search_element_' . $i_num ] ) == true && $_GET[ 'search_element_' . $i_num ] != null ){
				
					$get_archive_array = $_GET['search_element_' .$i_num];
					
					if( $get_archive_array != array() ){
						for( $i_arc =0, $cnt_arc = count( $get_archive_array ); $i_arc < $cnt_arc; $i_arc++ ){
							if( $get_archive_array[ $i_arc ] != null ){
								$get_archive[] = array(
									'date' => esc_sql($get_archive_array[ $i_arc ]),
									'range' => ''
								);
							}
						}
					}
				}
			}
			
			//  その他
			else {
				if( isset( $_GET[ 'search_element_' . $i_num ]) == true && $_GET[ 'search_element_' . $i_num ] != null ){
					$get_archive[] = array(
						'date' => esc_sql($_GET[ 'search_element_' . $i_num ]),
						'range' => db_op_get_value( $cols[16] . $manag_no . "_" . $i_num )
					);
				}
			}
		}
		
		////////////////  カスタムフィールド検索  ////////////////
		else if( mb_substr( db_op_get_value( $cols[2] . $manag_no . "_" . $i_num ) , 0 , 5 ) == "meta_" ){
			
			$get_key = mb_substr( db_op_get_value( $cols[2] . $manag_no . "_" . $i_num ) , 5 , mb_strlen( db_op_get_value( $cols[2] . $manag_no . "_" . $i_num ) ) );
			
			//  選択されているキーのすべての値の数をカウント
			$sql  = " SELECT count( DISTINCT " . $wpdb->postmeta .".meta_value ) As cnt FROM " . $wpdb->postmeta;
			$sql .= " WHERE " .$wpdb->postmeta .".meta_key ='" . $get_key ."' ";
			$sql .= " LIMIT 1";

			$get_meta_cnt = $wpdb->get_results( $sql );
			$get_meta_cnt = $get_meta_cnt[0]->cnt;

			$get_metas = array();
	
			//  チェックボックス
			if( $get_ele_value == 3 ){

					for( $check_cnt =0; $check_cnt < $get_meta_cnt; $check_cnt++ ){
						if( isset( $_GET[ "search_element_" .$i_num ."_" .$check_cnt] ) ){
/*
							if( $get_metas != null )
								$get_metas .= ",";
*/
		
							$get_metas[] = $_GET[ "search_element_" . $i_num . "_" . $check_cnt ];
						}
					}

					if( $get_metas !=null ){
						$get_pm_elements[ $i_cnt_meta ][ 'key' ] = $get_key;	//  meta_key
						$get_pm_elements[ $i_cnt_meta ][ 'metas' ] = $get_metas;	//  カンマ区切りのmeta_value
						$get_pm_elements[ $i_cnt_meta ][ 'plural' ] = "1";		//  複数選択？ yes =1
						$get_pm_elements[ $i_cnt_meta ][ 'and' ] = db_op_get_value( $cols[6] . $manag_no . "_" . $i_num );  //  検索方法は？ or =0, and =1
						$i_cnt_meta++;
					}
					
			}
			
			//  フリーキーワード
			else if( $get_ele_value == 5 ){
				
				if( isset( $_GET[ 's_keyword_' .$i_num ] ) == true )
					//$keywords .= " " .$_GET[ 's_keyword_' .$i_num ];
					$keywords[ $i_num ] = $_GET[ 's_keyword_' . $i_num ];
			}
			
			//  リストボックス
			else if( $get_ele_value == 2 ){
			
				if( isset( $_GET[ 'search_element_' . $i_num ] ) == true && $_GET[ 'search_element_' . $i_num ] != null ){

					$get_metas_array = $_GET[ 'search_element_' . $i_num ];
		
					if( $get_metas_array != array() ){

						for( $i_cat_list =0, $cnt_cat_list = count( $get_metas_array ); $i_cat_list < $cnt_cat_list; $i_cat_list++ ){
							if( $get_metas != null )
								$get_metas .= ",";
		
							$get_metas .= esc_sql($get_metas_array[ $i_cat_list ]);
						}
						
						if( $get_metas !=null ){
							$get_pm_elements[ $i_cnt_meta ][ 'key' ] = $get_key;  //  meta_key
							$get_pm_elements[ $i_cnt_meta ][ 'metas' ] = $get_metas;  //  カンマ区切りのmeta_value
							$get_pm_elements[ $i_cnt_meta ][ 'plural' ] = "1";  //  複数選択？ yes =1
							$get_pm_elements[ $i_cnt_meta ][ 'and' ] = db_op_get_value( $cols[6] . $manag_no . "_" . $i_num );  //  検索方法は？ or =0, and =1
							$i_cnt_meta++;
						}
					}
				}
			}
			//  その他
			else {
				//フリーワードの範囲検索
				if(isset( $_GET[ 'cf_limit_keyword_' . $i_num ] ) == true && $_GET[ 'cf_limit_keyword_' . $i_num ] != null ){
					
					$get_pm_elements[ $i_cnt_meta ][ 'key' ] = $get_key;  //  meta_key
					$get_pm_elements[ $i_cnt_meta ][ 'metas' ] = esc_sql($_GET[ 'cf_limit_keyword_' . $i_num ]);  //  meta_value
					$get_pm_elements[ $i_cnt_meta ][ 'plural' ] = "0";  //  複数選択？ yes =1
					$get_pm_elements[ $i_cnt_meta ][ 'and' ] = db_op_get_value( $cols[6] . $manag_no . "_" . $i_num );  //  検索方法は？ or =0, and =1
					$get_pm_elements[ $i_cnt_meta ][ 'range' ] = db_op_get_value( $cols[16] . $manag_no . "_" . $i_num );
					$get_pm_elements[ $i_cnt_meta ][ 'unit' ] = db_op_get_value( $cols[17] . $manag_no . "_" . $i_num );
					$get_pm_elements[ $i_cnt_meta ][ 'kugiri' ] = db_op_get_value( $cols[18] . $manag_no . "_" . $i_num );
					$get_pm_elements[ $i_cnt_meta ][ 'free_word' ] = db_op_get_value( $cols[22] . $manag_no . "_" . $i_num );
					$get_pm_elements[ $i_cnt_meta ][ 'number' ] = $i_num ;
					$i_cnt_meta++;
					
				}elseif( isset( $_GET[ 'search_element_' . $i_num ] ) == true && $_GET[ 'search_element_' . $i_num ] != null ){
					$get_pm_elements[ $i_cnt_meta ][ 'key' ] = $get_key;  //  meta_key
					$get_pm_elements[ $i_cnt_meta ][ 'metas' ] = esc_sql($_GET[ 'search_element_' . $i_num ]);  //  meta_value
					$get_pm_elements[ $i_cnt_meta ][ 'plural' ] = "0";  //  複数選択？ yes =1
					$get_pm_elements[ $i_cnt_meta ][ 'and' ] = db_op_get_value( $cols[6] . $manag_no . "_" . $i_num );  //  検索方法は？ or =0, and =1
					$get_pm_elements[ $i_cnt_meta ][ 'range' ] = db_op_get_value( $cols[16] . $manag_no . "_" . $i_num );
					$get_pm_elements[ $i_cnt_meta ][ 'unit' ] = db_op_get_value( $cols[17] . $manag_no . "_" . $i_num );
					$get_pm_elements[ $i_cnt_meta ][ 'kugiri' ] = db_op_get_value( $cols[18] . $manag_no . "_" . $i_num );
					$get_pm_elements[ $i_cnt_meta ][ 'free_word' ] = db_op_get_value( $cols[22] . $manag_no . "_" . $i_num );
					$get_pm_elements[ $i_cnt_meta ][ 'number' ] = $i_num ;
					$i_cnt_meta++;
					
				}
				
			}
		}
		////////////////  カテゴリ検索  ////////////////
		else {
			
			 //  チェックボックス
			if( $get_ele_value ==3 ){
	
				//  選択されているカテゴリ/タグ数取得
				//  カテゴリ検索（子カテゴリ検索のため、formのhiddenから累積生成チェックボックス数を取得）
				//if( is_numeric( db_op_get_value( $cols[2] .$manag_no ."_" .$i_num ) ) || db_op_get_value( $cols[2] .$manag_no ."_" .$i_num ) == "par_cat" ){
				if( is_numeric( db_op_get_value( $cols[2] .$manag_no ."_" .$i_num ) ) || db_op_get_value( $cols[2] .$manag_no ."_" .$i_num ) == "par_cat" || isset($_GET[ "search_element_" . $i_num . "_cnt" ])){
					$total_cnt = intval($_GET[ "search_element_" . $i_num . "_cnt" ]);
				//  タグ検索
				} else {
					$sql  =" SELECT count(" .$wpdb->terms .".term_id ) As cnt FROM " .$wpdb->terms;
					$sql .=" LEFT JOIN " .$wpdb->term_taxonomy ." ON " .$wpdb->terms .".term_id = " .$wpdb->term_taxonomy .".term_id";
					$sql .=" WHERE " .$wpdb->term_taxonomy .".taxonomy ='post_tag' ";
					$sql .=" AND parent ='" .db_op_get_value($cols[2] .$manag_no ."_" .$i_num) ."'";
					$sql .=" LIMIT 1";
					$total_cnt =$wpdb->get_results($sql);
					$total_cnt =$total_cnt[0]->cnt;
				}

				$get_cats = null;

				for( $check_cnt = 0; $check_cnt < $total_cnt; $check_cnt++ ){
					if( isset( $_GET[ "search_element_" . $i_num .'_'. $check_cnt] ) ){
						if( $get_cats != null )
							$get_cats .= ",";

						$get_cats .= esc_sql($_GET[ "search_element_" . $i_num .'_'. $check_cnt]);
					}
				}

				if( $get_cats != null ){
					$get_elements[ $i_counter ][ 'cat' ] = $get_cats;  //  カンマ区切りのカテゴリID
					$get_elements[ $i_counter ][ 'plural' ] = "1";  //  複数選択？ yes =1
					$get_elements[ $i_counter ][ 'and' ] = db_op_get_value( $cols[6] . $manag_no . "_" . $i_num );  //  検索方法は？ or =0, and =1
					//$get_elements[ $i_counter ][ 'search_chi' ] = db_op_get_value( $cols[11] . $manag_no ."_" . $i_num );  //  子カテゴリまで検索する しない =0, する =1
					$i_counter++;
				}
			}
			
			//  フリーキーワード
			else if( $get_ele_value == 5 ){
				
				if( isset( $_GET[ 's_keyword_' . $i_num ] ) == true )
					//$keywords .=" " .$_GET['s_keyword_' .$i_num];
					$keywords[ $i_num ] = $_GET[ 's_keyword_' . $i_num ];
			}
			
			//  リストボックス
			else if( $get_ele_value == 2 ){

				if( isset( $_GET[ 'search_element_' . $i_num ] ) == true && $_GET[ 'search_element_' . $i_num] != null ){

					$get_cats_array = $_GET[ 'search_element_' . $i_num ];
		
					if( $get_cats_array != array() ){
						$get_cats = null;
		
						for( $i_cat_list =0, $cnt_cat_list =count( $get_cats_array ); $i_cat_list < $cnt_cat_list; $i_cat_list++ ){
							if( $get_cats != null )
								$get_cats .= ",";
		
							$get_cats .= esc_sql($get_cats_array[ $i_cat_list ]);
						}

						if( $get_cats !=null ){
							$get_elements[ $i_counter ][ 'cat' ] = $get_cats;  //  カテゴリ
							$get_elements[ $i_counter ][ 'plural' ] = "1";  //  複数選択？ yes =1
							$get_elements[ $i_counter ][ 'and' ] = db_op_get_value( $cols[6] . $manag_no . "_" . $i_num );  //  検索方法は？ or =0, and =1
							//$get_elements[ $i_counter ][ 'search_chi' ] = db_op_get_value( $cols[11] . $manag_no . "_" . $i_num );  //  子カテゴリまで検索する しない =0, する =1
							$i_counter++;
						}
						
					}
				}
			}
			//  その他
			else {
				
				if( isset( $_GET[ 'search_element_' . $i_num ] ) == true && $_GET[ 'search_element_' . $i_num] != null ){
					if(is_array($_GET['search_element_' .$i_num])){
						$cate = end($_GET[ 'search_element_' . $i_num ]);
					}else{
						$cate = $_GET[ 'search_element_' . $i_num ];
					}
					if($cate != ''){
						$get_elements[ $i_counter ][ 'cat' ] = esc_sql($cate);  //  カテゴリ
						$get_elements[ $i_counter ][ 'plural' ] = "0";  //  複数選択？ no=0
						$get_elements[ $i_counter ][ 'and' ] = db_op_get_value( $cols[6] . $manag_no . "_" . $i_num );  //  検索方法は？ or =0, and =1
						//$get_elements[ $i_counter ][ 'search_chi' ] = db_op_get_value( $cols[11] .$manag_no . "_" . $i_num );	//  子カテゴリまで検索する しない =0, する =1
						$i_counter++;
					}
				}
			}
		}
	}
	
	//  アーカイブの検索本体、該当記事ID取得
	$add_where = create_where_archive( $get_archive );

	//  カスタムフィールドの検索本体、該当記事ID取得
	$add_where .= create_where_meta( $get_pm_elements, $i_cnt_meta );

	//  カテゴリの検索本体、該当記事ID取得
	$add_where .= create_where_category( $get_elements, $i_counter );

	$ret[0] = $add_where;
	$ret[1] = $keywords;

	return $ret;
}

//////////////////////////////////////////////
//  アーカイブ(年月)検索
//////////////////////////////////////////////
function create_where_archive( $datas = array() ){
	global $wpdb, $cols, $manag_no;

	// archiveがなかった時は条件なし
	if( $datas == array() )
		return null;

	$sql  = " SELECT ID FROM " . $wpdb->posts;
	for( $i_arc =0, $cnt_arc = count( $datas ); $i_arc < $cnt_arc; $i_arc++ ){
		if( $i_arc == 0 )
			$sql .= " WHERE (";
		else
			$sql .= " AND ";

		if(isset($datas[0]['date'])){ // 多重配列判定
			$year = substr( $datas[ $i_arc ]['date'], 0 , 4 );
			$month = substr( $datas[ $i_arc ]['date'], 4 , strlen( $datas[ $i_arc ]['date'] ) );
			$last_day = date("t", mktime(0, 0, 0, $month, 1, $year));
		}else{
			$year = substr( $datas[ $i_arc ], 0 , 4 );
			$month = substr( $datas[ $i_arc ], 4 , strlen( $datas[ $i_arc ] ) );
		}

		if( $month == 12 ){
			$next_year = $year +1;
			$next_month =1;
		}
		else{
			$next_year = $year;
			$next_month = $month +1;
		}

		switch(intval($datas[ $i_arc ]['range'])){
			case 1:
				$sql .= " ( post_date < '" . $year . "-" . $month . "-01 00:00:00" . "')";
				$rangeKey = "未満";
				break;
			case 2:
				$sql .= " ( post_date <= '" . $year . "-" . $month . "-" . $last_day . " 00:00:00" . "')";
				$rangeKey = "以下";
				break;
			case 3:
				$sql .= " ( post_date >= '" . $year . "-" . $month . "-01 00:00:00" . "')";
				$rangeKey = "以上";
				break;
			case 4:
				$sql .= " ( post_date > '" . $year . "-" . $month . "-" . $last_day . " 00:00:00" . "')";
				$rangeKey = "超";
				break;
			default:
				$sql .= " ( ";
				$sql .= " post_date >='" . $year . "-" . $month . "-01 00:00:00" . "'";
				$sql .= " AND ";
				$sql .= " post_date <'" . $next_year . "-" . $next_month . "-01 00:00:00" . "') ";
				$rangeKey = "";
				break;
		}

		if( ( $i_arc +1 ) == $cnt_arc )
			$sql .= " ) ";

		//  検索条件をテンプレートに表示させるため
		if( $year != null )
			insert_result( $year . "年" . $month . "月" .$rangeKey);
	}

	if( $i_arc == 0 )
		$sql .= " WHERE ".$wpdb->posts.".ID = -9999 ";

	$get_archive_ids = $wpdb->get_results( $sql );
	
	//where文を作成
	$ret = null;
	
	for( $i_ids =0, $cnt_ids = count( $get_archive_ids ); $i_ids < $cnt_ids; $i_ids++ ){
		if( $i_ids != 0 )
			$ret .= " OR ";

		$ret .= $wpdb->posts.".ID = " . $get_archive_ids[ $i_ids ]->ID;
	}

	if( $i_ids == 0 )
		$ret = $wpdb->posts.".ID = -9999";

	$ret = " AND (" . $ret . " ) ";
	
	return $ret;
}

//////////////////////////////////////////////
//  カテゴリ検索
//////////////////////////////////////////////
function create_where_category( $datas, $i_counter ){
	global $wpdb, $cols, $manag_no;

	$r_ret = null;
	
	for( $i_datas = 0 , $cnt_datas = count( $datas ); $i_datas < $cnt_datas; $i_datas++ ){

		if( $i_datas > 0 )
			$r_ret .= " AND ";

		//  単一選択形式（ドロップダウン等）で尚且つ子カテゴリ検索がない場合
		if( $datas[ $i_datas ][ 'plural' ] == "0" /*&& $datas[ $i_datas ][ 'search_chi' ] == "0"*/ ){
			$r_ret .= create_where_single_cat( $datas[ $i_datas ] );
		}
		else {
			$r_ret .= create_where_plural_cat( $datas[ $i_datas ] );
		}
	}

	$ret = null;
	if( $r_ret != null )
		$ret = " AND (" . $r_ret . ")";

	if($ret ==null && $i_counter !=0){
		$ret .=" AND ".$wpdb->posts.".ID = -9999 ";
	}

	return $ret;
}

//////////////////////////////////////////////
//  カテゴリ検索本体｜単一選択形式 (ドロップダウン等)
//////////////////////////////////////////////
function create_where_single_cat( $data )
{
	global $wpdb, $cols, $manag_no;
	
	$ret = null;
	
	$sql  = ' SELECT tr.object_id, t.name';
	$sql .= ' FROM ' . $wpdb->term_relationships . ' AS tr';
	$sql .= ' LEFT JOIN ' . $wpdb->term_taxonomy . ' AS tt ON tt.term_taxonomy_id = tr.term_taxonomy_id';
	$sql .= ' LEFT JOIN ' . $wpdb->terms . ' AS t ON tt.term_id = t.term_id';
	$sql .= ' WHERE tt.term_id = ' . esc_sql( $data['cat'] );
	
	$get_ids = $wpdb->get_results( $sql );

	$cat_names = array();

	if ( $get_ids ) {
		$cnt_ids = count( $get_ids );
		
		for ( $i_ids = 0; $i_ids < $cnt_ids; $i_ids++ ) {
			if ( 0 == $i_ids ) {
				$ret .= " ( ";
			} else {
				$ret .= " OR ";
			}
			$ret .= $wpdb->posts . ".ID = " . esc_sql( $get_ids[$i_ids]->object_id );
	
			if ( ( $i_ids + 1 ) == $cnt_ids )
				$ret .= " ) ";
		}
		
	} else {
		$ret = " ( " . $wpdb->posts . ".ID = -9999 ) ";
	}
		
	// 検索条件を収納
	$term_data = get_term_by( 'id', $data['cat'], 'category' );
	$cat_names = $term_data->name;
	insert_result( $cat_names );
	
	return $ret;
}

//////////////////////////////////////////////
//  カテゴリ検索本体｜複数選択形式 (チェックボックス等)
//////////////////////////////////////////////
function create_where_plural_cat( $data ){
	global $wpdb, $cols, $manag_no;

	$ret = null;

	$get_cats = explode( "," , $data[ 'cat' ] );

	//  検索条件をテンプレートに表示させるためにカテゴリ/タグ名を取得
	$sql_name  =" SELECT name, taxonomy FROM " . $wpdb->term_taxonomy;
	$sql_name .=" LEFT JOIN " .$wpdb->terms ." ON " . $wpdb->term_taxonomy . ".term_id =" . $wpdb->terms . ".term_id";
	
	$cat_names = array();
	
	for( $i_query =0, $cnt_query = count( $get_cats ); $i_query < $cnt_query; $i_query++ ){
		
		$sql_name_add =" WHERE ( ". $wpdb->terms . ".term_id =" . $get_cats[ $i_query ] . ")";
		
		$get_names = $wpdb->get_results( $sql_name . $sql_name_add );
		$cat_names[] = $get_names[0]->name;
	}
	
	//  重複を消去
	$cat_names = array_unique( $cat_names );
	//  キー取得
	$c_keys = array_keys( $cat_names );

	//  結果を収納
	for( $i_ck =0, $cnt_ck = count( $c_keys ); $i_ck < $cnt_ck; $i_ck++ ){
		insert_result( $cat_names[ $c_keys[ $i_ck ] ] );
	}

	//  検索本体
	$sql_id  = " SELECT object_id, name, taxonomy FROM " . $wpdb->term_relationships;
	$sql_id .= " LEFT JOIN " . $wpdb->term_taxonomy . " ON " . $wpdb->term_relationships . ".term_taxonomy_id =" . $wpdb->term_taxonomy . ".term_taxonomy_id";
	$sql_id .= " LEFT JOIN " . $wpdb->terms . " ON " . $wpdb->term_taxonomy . ".term_id =" . $wpdb->terms . ".term_id";

	//  チェックされたカテゴリ１つずつ、該当する記事IDを取得して配列$idsに格納
	for( $i_cat =0 , $cnt_cat = count( $get_cats ); $i_cat < $cnt_cat; $i_cat++ ){
	
		$sql_id_add = " WHERE ( ";
		$sql_id_add .= $wpdb->term_taxonomy . ".term_id =" . $get_cats[ $i_cat ];

		//  子カテゴリ検索をする場合 //使われていない筈
/*
		if( $data[ 'search_chi' ] == "1" ){ 
			$get_cat_ids = get_cat_chi_ids( $get_cats[ $i_cat ] );
			for( $i_chi_cat = 0, $cnt_chi_cat = count( $get_cat_ids ); $i_chi_cat < $cnt_chi_cat; $i_chi_cat++ ){

				if( $data[ 'and' ] == 0 ){
					$sql_id_add .= " OR ";
				}
				else {
					$sql_id_add .= " AND ";
				}

				$sql_id_add .= $wpdb->term_taxonomy . ".term_id =" . $get_cat_ids[ $i_chi_cat ];
			}
		}
*/
		
		$sql_id_add .= ")";
		$get_ids = $wpdb->get_results( $sql_id . $sql_id_add );
		
		if( $get_ids ) {
			$ids = array();
			
			for($i_ids = 0, $cnt_ids = count( $get_ids ); $i_ids < $cnt_ids; $i_ids++ ){
		
				if( $get_ids[ $i_ids ]->object_id != null )
					$ids[ $i_cat ][ $i_ids ] = $get_ids[ $i_ids ]->object_id;
			}	
		}
	
		//  OR検索の時
		if( $data[ 'and' ] == "0" ){
			
			if($i_cat == 0){
				$left_ids =  ( array ) $ids[ $i_cat ];
			}
			else {
			
				//  前回のループの結果に現在のループの結果を結合
				$left_ids = array_merge( $left_ids, ( array ) $ids[ $i_cat ] );
				//  重複を削除
				$left_ids = array_unique( $left_ids );
			}
		
		//  AND検索の時
		}
		else {
			
			if( $i_cat == 0 )
				$left_ids =  ( array ) $ids[ $i_cat ];
			
			//  複数のカテゴリに渡って該当する記事IDを、array_intersectで抽出（各配列に共通する値を選別）
			//  １つ前のカテゴリ検索の結果（記事ID群）を、現在のカテゴリ検索の結果でフィルタリング
			$left_ids = array_intersect( $left_ids , ( array ) $ids[ $i_cat ] );
		}
	}

	if( $left_ids ){
		
		$num_id = count( $left_ids );
		$i_id = 0;
		
		foreach ( $left_ids as $key => $left_id ){
			
			if( $i_id == 0 )
				$ret = " ( ";
			else
				$ret .= " OR ";
			
			$ret .= $wpdb->posts.".ID = " . $left_id;
			
			if( $i_id +1 == $num_id )
				$ret .= " ) ";
			
			$i_id++;
		}
	} 
	else
		$ret = " ( ".$wpdb->posts.".ID = -9999 ) ";
	
	return $ret;
}

//////////////////////////////////////////////
//  フリーワード（キーワード）検索
//////////////////////////////////////////////
function create_where_keyword( $keywords, $kwds_target = "", $kwds_yuragi = "yes", $manag_no, $number ){
	global $wpdb, $feas_mojicode, $cols, $feadvns_search_target;
	
	$specify_key_switch = db_op_get_value($cols[21] . $manag_no . "_" . $number );
	
	$collate = '';
	
	// ゆらぎ
	if( "no" == $kwds_yuragi ) { // 半角全角を区別しない
		
		if ( 'utf8mb4' === $wpdb->charset || ( ! $wpdb->charset && $wpdb->has_cap( 'utf8mb4' ) ) ) {
			$collate = 'COLLATE utf8mb4_unicode_ci';
		} else {
			$collate = 'COLLATE utf8_unicode_ci';
		}
			
	} else {
		
		if ( 'utf8mb4' === $wpdb->charset || ( ! $wpdb->charset && $wpdb->has_cap( 'utf8mb4' ) ) ) {
			$collate = 'COLLATE utf8mb4_general_ci';
		} else {
			$collate = 'COLLATE utf8_general_ci';
		}
					
	}	
	
	$keywords = str_replace( "　", " ", stripslashes( $keywords ) );
	preg_match_all( '/".*?("|$)|((?<=[\\s])|^)[^\\s]+/', $keywords, $matches );
	$keywords = array_map(create_function( '$a', 'return trim($a, "\\"\'\\n\\r ");'), $matches[0] );
	
	if( is_array( $keywords ) ){
		foreach( $keywords as $word ){
			$ret_ary[] = esc_sql( $word );
		}
		$keywords = $ret_ary;
	} else {
		$keywords = esc_sql( $keywords );
	}
	
	//  検索対象の指定がDBにない場合
	if( $kwds_target == "" ){
		$kwds_target[0] = 'post_title';
		$kwds_target[1] = 'post_content';
		$kwds_target[2] = 'name';
		$kwds_target[3] = 'meta_value';
	} else {
		$kwds_target = explode( ",", $kwds_target );	
	}
	
	//  DBから取得したデータから「0」を省く
	foreach( $kwds_target  as $k_target ){
		if( $k_target != "0" )
			$kt[] = $k_target;	
	}
	
	// キーワードの数を取得
	$kw_cnt = count( $keywords );
	
	// キーワードが複数だった場合にINNER JOINするためのフラグ
	$comment_join_flag = false;
	$term_join_flag    = false;
	$pm_join_flag      = false;
	
	if ( in_array( 'comment_content', $kt ) )
		$comment_join_flag = true;
	if ( in_array( 'name', $kt ) )
		$term_join_flag = true;
	if ( in_array( 'meta_value', $kt ) )
		$pm_join_flag = true;
	
	$sql = " SELECT distinct " .$wpdb->posts .".ID FROM " .$wpdb->posts;
	// キーワードが複数だった場合に、キーワードの数分、INNER JOINする
	if ( true == $pm_join_flag ) {
		for ( $i = 0; $i < $kw_cnt; $i++ ) {
			$sql .= " LEFT JOIN {$wpdb->postmeta} AS pm{$i} ON {$wpdb->posts}.ID = pm{$i}.post_id";
		}
	}
	if ( true == $comment_join_flag ) {
		for ( $i = 0; $i < $kw_cnt; $i++ ) {
			$sql .= " LEFT JOIN {$wpdb->comments} AS comm{$i} ON {$wpdb->posts}.ID = comm{$i}.comment_post_ID";
		}
	}
	if ( true == $term_join_flag ) {
		for ( $i = 0; $i < $kw_cnt; $i++ ) {
			$sql .= " LEFT JOIN {$wpdb->term_relationships} AS tr{$i} ON {$wpdb->posts}.ID = tr{$i}.object_id";
			$sql .= " LEFT JOIN {$wpdb->term_taxonomy} AS tt{$i} ON tr{$i}.term_taxonomy_id = tt{$i}.term_taxonomy_id";
			$sql .= " LEFT JOIN {$wpdb->terms} AS t{$i} ON tt{$i}.term_id = t{$i}.term_id";
		}
	}
	$sql .= " WHERE ( 1 = 1 ) ";
	
	//$target_pt = db_op_get_value( $feadvns_search_target . $manag_no );
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
	
	$sql .= " AND $wpdb->posts.post_type IN ( $target_pt )";
					
	// キーワードのエンコード（携帯対応）
	for( $ii_key = 0 , $cnt_ii_key = count( $keywords ); $ii_key < $cnt_ii_key ; $ii_key++ ) {
		if( function_exists( 'is_ktai' ) && is_ktai() == true )
			$feas_mojicode = "SJIS";
		else
			$feas_mojicode = "UTF-8";

		$keywords[$ii_key] = mb_convert_encoding( $keywords[$ii_key] , "UTF-8" , $feas_mojicode );
		$kwds[$ii_key] = $keywords[$ii_key];
	}

	for( $i_key = 0, $cnt_key = count( $kwds ); $i_key < $cnt_key; $i_key++ ) {

		//if($i_key > 0){$sql .= " OR ( ";}else{$sql .= " AND ( ";} //or検索用
		$sql .= " AND ( ";
		
		for( $i_tg = 0 , $i_cnt = 0 , $tg_cnt = count( $kt ); $i_tg < $tg_cnt; $i_tg++ ) {
			if( 'post_title' == $kt[$i_tg] ) {
				if( $i_cnt != 0 )
					$sql .= " OR ";
				$sql .= "( $wpdb->posts.post_title LIKE '%{$kwds[$i_key]}%' {$collate} )";
				$i_cnt++;
			}
			if( 'post_content' == $kt[$i_tg] ) {
				if( $i_cnt != 0 )
					$sql .= " OR ";
				$sql .= "( $wpdb->posts.post_content LIKE '%{$kwds[$i_key]}%' {$collate} )";
				$i_cnt++;
			}
			if( 'post_excerpt' == $kt[$i_tg] ) {
				if( $i_cnt != 0 )
					$sql .= " OR ";
				$sql .= "( $wpdb->posts.post_excerpt LIKE '%{$kwds[$i_key]}%' {$collate} )";
				$i_cnt++;
			}
			if( 'name' == $kt[$i_tg] ) {
				if( $i_cnt != 0 )
					$sql .= " OR ";
				$sql .= "( t{$i_key}.name LIKE '%{$kwds[$i_key]}%' {$collate} )";
				$i_cnt++;
			}
			
			if( 'meta_value' == $kt[$i_tg] ) {
				
				if( 0 != $i_cnt )
					$sql .= " OR ";
				
				if( 'no' == $specify_key_switch ) { // キー指定
					
					for( $i_sk = 0 ; $i_sk <= intval( $_GET['cf_specify_key_length_' . $number] ); $i_sk++ ) {
						
						if( isset( $_GET['cf_specify_key_' . $number . '_' . $i_sk] ) && ( null != $_GET['cf_specify_key_' . $number . '_' . $i_sk] ) ) {
							
							if( function_exists( 'is_ktai' ) && true == is_ktai() ) {
								$words = mb_convert_encoding( $_GET['cf_specify_key_' . $number . '_' . $i_sk] , "UTF-8", "SJIS" );
							} else {
								$words = $_GET['cf_specify_key_' . $number . '_' . $i_sk];
							}
							
							$cf_key = '\'' . esc_sql( $words ) . '\'';
							
							//insert_result( $words );
							feas_insert_keys_result( $words , $manag_no ); // カスタムフィールドのキーを格納
							
							$specify_key_word[] = $kwds[$i_key];
							
							if( 0 != $i_sk )
								$sql .= " OR ";
							
							$sql .= " (";
							$sql .= " pm{$i_key}.meta_key IN ( $cf_key )";
							$sql .= " AND pm{$i_key}.meta_value LIKE '%{$kwds[$i_key]}%' {$collate}";	
							$sql .= " )";
						}
					}
					
				} else {
					
					$sql .= "( pm{$i_key}.meta_value LIKE '%{$kwds[$i_key]}%' {$collate} ) ";
					
					// 先頭にアンダースコアがつくmeta_keyのvalueを検索対象から「すべて」除外するには下記をコメントイン
					//$sql .= " AND " . $wpdb->postmeta . ".meta_key NOT LIKE BINARY '@_%' ESCAPE '@' ";
					$sql .= " AND pm{$i_key}.meta_key  != '_wp_trash_meta_time' ";
					$sql .= " AND pm{$i_key}.meta_key  != '_wp_trash_meta_status' ";
					$sql .= " AND pm{$i_key}.meta_key  != '_wp_page_template' ";
					$sql .= " AND pm{$i_key}.meta_key  != '_wp_old_slug' ";
					$sql .= " AND pm{$i_key}.meta_key  != '_wp_attachment_metadata' ";
					$sql .= " AND pm{$i_key}.meta_key  != '_wp_attached_file' ";
					$sql .= " AND pm{$i_key}.meta_key  != '_encloseme' ";
					$sql .= " AND pm{$i_key}.meta_key  != '_edit_lock' ";
					$sql .= " AND pm{$i_key}.meta_key  != '_edit_last' ";
				}
				
				$i_cnt++;
			}
			
			if( 'comment_content' == $kt[$i_tg] ) {
				
				if( 0 != $i_cnt )
					$sql .= " OR ";
					
				$sql .= "( comm{$i_key}.comment_content LIKE '%{$kwds[$i_key]}%' {$collate} )";
				
				$i_cnt++;
			}
		}
		$sql .= " ) ";
		
		//  検索条件としてテンプレートに表示させるため収納
		if( is_array( $kwds[ $i_key ] ) ){
			insert_result( stripslashes( $kwds[ $i_key ][0]) );
			insert_kwds_result( stripslashes( $kwds[ $i_key ][0] ) , $number );
		} else {
			insert_result( stripslashes( $kwds[ $i_key ] ) );
			insert_kwds_result( stripslashes( $kwds[ $i_key ] ) , $number );
		}

	}
  
//var_dump($sql);
  
	$get_ids = $wpdb->get_results( $sql );
	$ret = null;
	
	/*if(isset($specify_key_word)) //メタキー指定検索
		$ret .= feas_specify_key_srarch($specify_key_word,$number);*/
		
	//  該当記事IDを収納
	for( $i_pid =0, $cnt_pid = count( $get_ids ); $i_pid < $cnt_pid; $i_pid++ ){
		if( $i_pid == 0 ){
			$ret .= " AND ( ";
		}else{
			$ret .= " OR ";
		}

		$ret .= $wpdb->posts.".ID = " .$get_ids[$i_pid]->ID;

		if( ( $i_pid +1) ==$cnt_pid)
			$ret .=" )";
	}

	if( $ret == null )
		$ret .= " AND ( ".$wpdb->posts.".ID = -9999 )";

//var_dump($ret);
  
	return $ret;
}

//////////////////////////////////////////////
//  カスタムフィールド検索
//////////////////////////////////////////////
function create_where_meta( $datas , $i_cnt_meta ){
	global $wpdb, $cols, $manag_no;

	$r_ret = null;
	
	for( $i_datas = 0, $cnt_datas = count( $datas ); $i_datas < $cnt_datas; $i_datas++ ){

		if( $i_datas > 0 )
			$r_ret .= " AND ";

		//  フリーワードで範囲検索
		if( isset($datas[ $i_datas ]['free_word']) && $datas[ $i_datas ]['free_word'] == 'yes' ){
			$r_ret .= create_where_single_meta( $datas[ $i_datas ] );
		}else
		//  単一選択形式の場合（ドロップダウン/ ラジオボタン）
		if( $datas[ $i_datas ][ 'plural' ] == "0" ){
			$r_ret .= create_where_single_meta( $datas[ $i_datas ] );
		}
		//  複数選択形式の場合（チェックボックス/ リストボックス）
		else {
			$r_ret .= create_where_plural_meta( $datas[ $i_datas ] );
		}
	}

	$ret = null;
	
	if( $r_ret != null )
		$ret = " AND " . $r_ret ." ";

	//  検索条件が設定され、かつ該当記事がない場合
	if( $ret == null && $i_cnt_meta != 0 ){
		$ret .= " AND ( ".$wpdb->posts.".ID = -9999 ) ";
	}
	
	return $ret;
}

//////////////////////////////////////////////
//  カスタムフィールド検索本体｜単一選択形式
//////////////////////////////////////////////
function create_where_single_meta( $datas ){
	global $wpdb;
	
	if( $datas['free_word'] == 'yes' ){
		$keywords = stripslashes($datas[ 'metas' ]);
		$keywords = str_replace( "　", " ", $datas[ 'metas' ]);
		if( mb_strlen( $keywords ) != strlen( $keywords ) ){
			$keywords = mb_convert_kana( $keywords , "a" , "UTF-8" );
			//$keywords = mb_convert_kana( $keywords , "a" , "SJIS" );
			
		}/* else { 半角を全角にする必要は無いか？？
			$kwds[$ii_key][0] = $keywords[ $ii_key ];
			$kwds[$ii_key][1] = mb_convert_kana( $keywords , "A" , "UTF-8" );
		}*/

		$keywords = str_replace( ",", "", $datas[ 'metas' ]);
		$keywords = explode( ' ', $keywords);
		$keywords = $keywords[0];
		
		if(is_numeric($keywords)){
			$datas[ 'metas' ] = $keywords;
			//insert_result( $keywords );
			insert_kwds_result( $keywords , $datas[ 'number' ] );
		}else{
			return(" ( ".$wpdb->posts.".ID = -9999) ");
		}
	}
	
	$get_id_data = $rangeKey = null;

	if ( function_exists( 'is_ktai' ) && true == is_ktai() ) {
		$datas['metas'] = mb_convert_encoding( $datas['metas'], 'UTF-8', 'SJIS' );
	}

	$value_is_int = false;
	
	if ( is_numeric( $unformatted_value = str_replace( ',', '', $datas['metas'] ) ) ) {
		$value_is_int = true;
	}
	
	//  検索条件( =meta_value )に該当する記事を取得
	$sql  = " SELECT post_id FROM " . $wpdb->postmeta;
	$sql .= " WHERE ( meta_key = '" . esc_sql( $datas['key'] ) . "' ";
		
	// 範囲検索 and 値が数値である場合のみ
	if ( in_array( $datas['range'], array( 1, 2, 3, 4 ) ) && true == $value_is_int ) {
		
		//$cfdata = str_replace( ",", "", $datas['metas'] );
	
		if( '1' == $datas['range'] ) {
			$sql .= ' AND meta_value+0 < ' . esc_sql( $unformatted_value ) . ' )';
			$rangeKey = '未満';
		
		} elseif( '2' == $datas['range'] ) {
			$sql .= ' AND meta_value+0 <= ' . esc_sql( $unformatted_value ) . ' )';
			$rangeKey = '以下';
		
		} elseif( '3' == $datas['range'] ) {
			$sql .= ' AND meta_value+0 >= ' . esc_sql( $unformatted_value ) . ' )';
			$rangeKey = '以上';
		
		} elseif( '4' == $datas['range'] ) {
			$sql .= ' AND meta_value+0 > ' . esc_sql( $unformatted_value ) . ' )';
			$rangeKey = '超';
		}
		
	// 範囲検索しない or 値が数値でない場合
	} else {
		
		$sql .= " AND ( meta_value = '" . esc_sql( $datas['metas'] ) . "'";
		$sql .= " OR meta_value = REPLACE( '" . esc_sql( $datas['metas'] ) . "', ',', '' ) ) )";
		//$sql .= " AND meta_value ='" . mb_convert_encoding( $datas[ 'metas' ] , "UTF-8" ,"SJIS"). "')";
		$rangeKey = '';
	}

	$get_id_data = $wpdb->get_results( $sql );
	
	// 検索条件をテンプレート表示するために収納
	
	// 区切り and 数値 = カンマ区切りしたもの + 単位 + 範囲
	if ( 'yes' == $datas['kugiri'] && $value_is_int ) {
		$formatted_value = number_format( $unformatted_value, 0, '.', ',' ) . $datas['unit'] . $rangeKey;
	// 区切り and 数値ではない = そのまま表示
	} elseif ( 'yes' == $datas['kugiri'] && false == $value_is_int ) {
		$formatted_value = $datas['metas'];
	// 区切らない = カンマ除去したもの
	} else {
		$formatted_value = $unformatted_value;	
	}
	insert_result( $formatted_value );

	$ret = null;
		
	//  該当記事からpost_idを抽出、戻り値(SQL文)を生成
	if ( $get_id_data ) {
		
		for ( $i_ids = 0, $cnt_ids = count( $get_id_data ); $i_ids < $cnt_ids; $i_ids++ ) {
			
			if ( 0 == $i_ids ) {
				$ret .= ' ( ';
			} else {
				$ret .= ' OR ';
			}
	
			$ret .= $wpdb->posts.'.ID = ' . $get_id_data[ $i_ids ]->post_id;
	
			if ( ( $i_ids + 1 ) == $cnt_ids ) {
				$ret .= ' ) ';
			}
		}
		
	} else {
		$ret = ' ( ' . $wpdb->posts . '.ID = -9999 ) ';
	}

	return $ret;
}

//////////////////////////////////////////////
//  カスタムフィールド検索本体｜複数選択形式
//////////////////////////////////////////////
function create_where_plural_meta( $datas ) {
	
	global $wpdb;
	
	$get_metas = $datas['metas']; // 配列
	
	if ( function_exists( 'is_ktai' ) && true == is_ktai() ) {
		foreach ( $get_metas as $key => $val ) {
			$get_metas[ $key ] = mb_convert_encoding( $val, "UTF-8", "SJIS" );
		}
	}
	
	$get_id_data = array();
	
	// 検索条件(=meta_value)ごとに該当する記事を取得
	for ( $i_data = 0, $cnt_data = count( $get_metas ); $i_data < $cnt_data; $i_data++ ) {
		
		$sql  = " SELECT $wpdb->postmeta.post_id FROM $wpdb->postmeta";
		$sql .= " WHERE ( $wpdb->postmeta.meta_key = '" . esc_sql( $datas['key'] ) . "'";
		$sql .= " AND ( $wpdb->postmeta.meta_value = '" . esc_sql( $get_metas[$i_data] ) . "'";
		$sql .= " OR $wpdb->postmeta.meta_value = REPLACE( '" . esc_sql( $get_metas[$i_data] ) . "', ',', '' ) ) )";
		$get_id_data[] = $wpdb->get_results( $sql );
		
		$value_is_int = false;
	
		if ( is_numeric( $unformatted_value = str_replace( ',', '', $get_metas[$i_data] ) ) ) {
			$value_is_int = true;
		}
		
		// 区切り and 数値 = カンマ区切りしたもの
		if ( 'yes' == $datas['kugiri'] && $value_is_int ) {
			$formatted_value = number_format( $unformatted_value, 0, '.', ',' );
		// 区切り and 数値ではない = そのまま表示
		} elseif ( 'yes' == $datas['kugiri'] && false == $value_is_int ) {
			$formatted_value = $get_metas[$i_data];
		// 区切らない = カンマ除去したもの
		} else {
			$formatted_value = $unformatted_value;	
		}
		// 検索条件をテンプレートに表示するために収納
		insert_result( $formatted_value );
	}
	
	$get_id = array();
	
	//  該当記事からpost_idを抽出
	for ( $i_data = 0, $cnt_data = count( $get_id_data ); $i_data < $cnt_data; $i_data++ ) {
		for ( $s_data = 0, $s_cnt = count( $get_id_data[ $i_data ] ); $s_data < $s_cnt; $s_data++ ) {
			$get_id[] = $get_id_data[ $i_data ][ $s_data ]->post_id;
		}
	}
	
	// 検索条件が１つ以上の時は重複チェックをする
	if ( count( $get_metas ) > 1 ) {
		
		$get_data = array();
		
		// OR検索
		if ( $datas['and'] == 0 ) {
	
			// 重複を削除
			$get_id = array_unique( $get_id );
			
			foreach ( $get_id as $value ) {
				$get_data[] = $value;
			}
		
		}
		
		// AND検索
		else { 
			
			//  重複しているpost_id ( = 複数条件に該当 ) のみ残す
			if ( true == is_array( $get_id ) ) {
				$arrayValue = array_count_values( $get_id ); // 取得したpost_idの出現回数をカウントする（key: post_id,  value: 出現回数）
				$arraykey = array_keys( $arrayValue, 1 ); // 重複していない値 ( = 出現回数が1 ) のキー(post_id)を取り出す
		
				for ( $i = 0; $i < count( $arraykey ); $i++ ) {
					unset( $arrayValue[ $arraykey[ $i ] ] ); // 重複していない要素 ( = 条件に当てはまらないpost_id ) を削除
				}
					
				if ( 0 != count( $arrayValue ) ) {
						
					$a_keys = array_keys( $arrayValue );
					
					// post_idの出現回数が条件の数と同じ(以上)の記事が該当データなので取得
					$check_cnt = count( $get_metas );  //  条件数
					for ( $i_data = 0, $i_cnt = count( $a_keys ); $i_data < $i_cnt; $i_data++ ) {
						if ( $arrayValue[ $a_keys[ $i_data ] ] >= $check_cnt ) {
							$get_data[] = $a_keys[ $i_data ];
						}
					}				
				}
			}
		}
	
	} else {
		$get_data = $get_id;
	}

	$ret = null;
	
	// 戻り値（SQL文）生成
	for ( $i_data = 0, $cnt_data = count( $get_data ); $i_data < $cnt_data; $i_data++ ) {
	
		if ( null != $get_data[ $i_data ] ) {	
			
			if ( 0 == $i_data ) {
				$ret .= " ( ";
			} else {
				$ret .= " OR ";
			}	
			$ret .= " $wpdb->posts.ID = " . esc_sql( $get_data[ $i_data ] );
		}
		
		if ( ( $i_data + 1 ) == $cnt_data ) {
			$ret .= " ) ";
		}
	}

	// 検索条件が設定され、かつ該当記事がない場合
	if ( 0 != $get_metas && null == $ret ) {
		$ret .= " ( $wpdb->posts.ID = -9999 ) ";
	}
	
	return $ret;
}

//////////////////////////////////////////////
//  フリーワード、メタキー指定検索
//////////////////////////////////////////////
function feas_specify_key_srarch($keywords,$number){
	global $wpdb,$manag_no;
	
	$cf_key=null;
	$cf_key_array = array();
	
	for( $i=0 ; $i <= intval( $_GET['cf_specify_key_length_'.$number] ) ; $i++ ) {
		if( isset( $_GET['cf_specify_key_'.$number.'_'.$i] ) && ( $_GET['cf_specify_key_'.$number.'_'.$i] != null ) ) {
			if( $cf_key != null )
				$cf_key.=',';
				
			if( function_exists('is_ktai') && is_ktai() == true ){
				$words = mb_convert_encoding( $_GET['cf_specify_key_'.$number.'_'.$i] , "UTF-8" , "SJIS");
			}else{
				$words = $_GET['cf_specify_key_'.$number.'_'.$i];
			}
				
			$cf_key.= '\''.esc_sql( $words ) .'\'';
			
			insert_result( $words );
			feas_insert_keys_result( $words , $manag_no ); //カスタムフィールドのキーを格納
			
			array_push( $cf_key_array , $words );
		}
	}
	
	$sql  = " SELECT DISTINCT post_id FROM " .$wpdb->postmeta;
	$sql .= " LEFT JOIN " .$wpdb->posts. " ON " .$wpdb->postmeta.".post_id = ".$wpdb->posts.".ID ";
	$sql .= " WHERE meta_key IN (" .$cf_key .")";
	
	$sql .= ' AND (';
	$temporary = '';
	foreach($keywords as $val){
		if($temporary != ''){$temporary .= ' OR';}
		$temporary .= ' meta_value LIKE \'%'.$val.'%\'';
		$keyword_array[] = $val;
	}
	$sql .= $temporary.' )';
	$sql .= " AND post_status ='publish'";
	$get_data = $wpdb->get_results( $sql );
	
	for( $i_data = 0, $cnt_data = count( $get_data ); $i_data < $cnt_data; $i_data++ ){
		
		$get_post_custom = get_post_custom($get_data[ $i_data ]->post_id);
		//$mache_export = true; //2012.8.17 熊谷 “全角半角区別しない”がされないので修正
		foreach($keywords as $val){ //キーワード
			$mache_export = true; //2012.8.17 熊谷
			$val = stripslashes($val);
			
			foreach($cf_key_array as $key){ //post_metaキー
				if(@strstr($get_post_custom[$key][0],$val)){
					$mach_count = true;
					break;
				}else{
					$mach_count = false;
				}
			}
			
			if((!$mach_count) || (!$mache_export)){
				$mache_export = false;
			}
			if($mache_export){
				if( $get_data[ $i_data ] != null ){
					if( $ret == '' )
						$ret = "AND ( ";
					else
						$ret .= " OR ";
						
					$ret .= $wpdb->posts.".ID =" .$get_data[ $i_data ]->post_id;
				}
			}
		} //foreachキーワード
	}
	
	//  検索条件が設定され、かつ該当記事がない場合
	if( $ret != null )
		$ret .= " )";
	
	return $ret;
}
?>
