<?php
/*************************************************
 *	ソート表示用関数
 *************************************************/
function custom_sort( $order = null )
{
	global $wpdb, $wp_query, $cols, $cols_order, $manag_no, $feadvns_sort_target, $feadvns_sort_order, $meta_sort_key, $feadvns_sort_target_cfkey_as;
	
	// 検索が実行された場合
	if( !( isset( $_GET['csp'] ) && $_GET['csp'] == "search_add" ) )
		return $order;

	if( isset( $_GET['fe_form_no'] ) == true && is_numeric( $_GET['fe_form_no'] ) )
		$manag_no = intval( $_GET['fe_form_no'] );
	else
		$manag_no = 0;

	$get_date = new stdClass;
	
	// ターゲットを取得
	if( isset( $_GET['s_target'] ) == true )
		$get_date->option_value = $_GET['s_target'];
	else
	{
		$sql  = " SELECT * FROM  $wpdb->options";
		$sql .= " WHERE option_name ='$feadvns_sort_target$manag_no'";
		$sql .= " LIMIT 1 ";
		$get_date = $wpdb->get_results( $sql );
		if( $get_date )
			$get_date = $get_date[0];
	}

	$get_sort_data = new stdClass;
	
	// 並び順を取得
	if( isset( $_GET['s_order'] ) == true )
		$get_sort_data->option_value = $_GET['s_order'];
	else
	{
		$sql  = " SELECT * FROM  $wpdb->options";
		$sql .= " WHERE option_name='$feadvns_sort_order$manag_no'";
		$sql .= " LIMIT 1 ";
		$get_sort_data = $wpdb->get_results( $sql );
		if( $get_sort_data )
			$get_sort_data = $get_sort_data[0];
	}
	
	$get_cfkey_as = '';

	// ターゲットを取得
	if( isset( $_GET['csfk_as'] ) == true )
		$get_cfkey_as = $_GET['csfk_as'];
	else
	{	
		$sql  = " SELECT * FROM  $wpdb->options";
		$sql .= " WHERE option_name='$feadvns_sort_target_cfkey_as$manag_no'";
		$sql .= " LIMIT 1 ";
		$get_cfkey_as = $wpdb->get_results( $sql );
		if( $get_cfkey_as )
			$get_cfkey_as = $get_cfkey_as[0]->option_value;
	}

	// 降順昇順を取得
	$get_sort = ' DESC ';
	if( isset( $get_sort_data->option_value ) == true && $get_sort_data->option_value != null )
	{
		if( 'up' == $get_sort_data->option_value || 'asc' == $get_sort_data->option_value )
			$get_sort = ' ASC ';
	}

	$sort_order = '';
	if( isset( $get_date->option_value ) == true && $get_date->option_value != null )
	{
		switch( $get_date->option_value )
		{		
			case "post_date": // 投稿日
				$sort_order  = "$wpdb->posts.post_date $get_sort, ";
				$sort_order .= "$wpdb->posts.post_title ASC ";
				break;
				
			case "post_title": // 投稿タイトル
				$sort_order  = "$wpdb->posts.post_title $get_sort, ";
				$sort_order .= "$wpdb->posts.post_date DESC ";
				break;
				
			case "post_name": // 投稿スラッグ
				$sort_order  = "$wpdb->posts.post_name $get_sort, ";
				$sort_order .= "$wpdb->posts.post_date DESC ";
				break;
			
			case "post_meta": // カスタムフィールド
				$sort_order  = "cf IS NULL ASC, ";
				$sort_order .= "cf ASC, ";
				if( 'int' == $get_cfkey_as )
					$sort_order .= "LPAD( cf_v, 15, '0' ) $get_sort, "; // 桁を合わせる
				else
					$sort_order .= "cf_v $get_sort, ";
				$sort_order .= "$wpdb->posts.post_date DESC ";
				break;
			
			case "rand": // ランダム
				$sort_order = " RAND() ";
				break;
/* Old
			case "title":
				$sort_order  = $wpdb->posts . ".post_title " . $get_sort . " , ";
				$sort_order .= $wpdb->posts . ".post_date DESC ";
				break;

			case "date":
				$sort_order  = $wpdb->posts . ".post_date " . $get_sort . " , ";
				$sort_order .= $wpdb->posts . ".post_title ASC ";
				break;

			case "custom_field":
				if( isset( $_GET['csfk'] ) == true && $_GET['csfk'] != null )
				{
					$sort_order  = " cf IS NULL ASC, ";
					$sort_order .= " cf ASC, ";
					$sort_order .= " LPAD( cf_v, 15, '0' ) " . $get_sort . " , ";
					$sort_order .= $wpdb->posts .".post_date DESC ";
				
				} else {
					
					$sort_order  = $wpdb->postmeta . ".meta_key " . $get_sort . " , ";
					$sort_order .= $wpdb->postmeta . ".meta_value " . $get_sort . " , ";
					$sort_order .= $wpdb->posts . ".post_date DESC ";
				}
				break;

			case "category":
				$sort_order  = $wpdb->terms .".slug " .$get_sort .",";
				$sort_order .= ( $set_cf_key ) ? $sort_cf2 : '';
				$sort_order .= $wpdb->posts .".post_title ASC ";
				
			case "tag":
				$sort_order  = "tn DESC,";
				$sort_order .= "tname " .$get_sort .",";
				$sort_order .= ( $set_cf_key ) ? $sort_cf2 : '';
				$sort_order .= $wpdb->posts .".post_date DESC";
				break;
*/
			
			default:
				$sort_order = "$wpdb->posts.post_date DESC ";
				break;
		}
	}
	else
		return $order;
	
	return $sort_order;
}


/*************************************************
 *	ソート用に連結する
 *************************************************/
function join_datas( $get_data = null )
{
	global $wpdb;

	//if(isset($_GET['searchbutton']) ==false){
	if( !( isset( $_GET['csp'] ) && $_GET['csp'] == "search_add" ) )
		return $get_data;

	$join_ret  = " LEFT JOIN " .$wpdb->postmeta ." ON " .$wpdb->posts .".id = " .$wpdb->postmeta .".post_id";
	$join_ret .= " LEFT JOIN " .$wpdb->term_relationships ." ON " .$wpdb->posts .".id = " .$wpdb->term_relationships .".object_id";
	$join_ret .= " LEFT JOIN " .$wpdb->term_taxonomy ." ON " .$wpdb->term_relationships .".term_taxonomy_id = " .$wpdb->term_taxonomy .".term_taxonomy_id";
	$join_ret .= " LEFT JOIN " .$wpdb->terms ." ON " .$wpdb->term_taxonomy .".term_id = " .$wpdb->terms .".term_id";

	return $join_ret;
}


/*************************************************
 *	GROUP BYをする
 *************************************************/
function groupby_datas( $get_data = null )
{
	global $wpdb;

	//if(isset($_GET['searchbutton']) ==false){
	if( !(isset($_GET[ 'csp' ]) && $_GET[ 'csp' ] == "search_add") ){
		return $get_data;
	}

	$group_ret =$wpdb->posts .".id";
	return $group_ret;
}


/*************************************************
 *	ソート用に仮に一回実行しソートをして出力する
 *************************************************/
function sort_add_field( $get_sql_data = null )
{
	global $wpdb, $manag_no, $feadvns_sort_target, $feadvns_sort_target_cfkey;
	
	if( !( isset( $_GET[ 'csp' ] ) && $_GET[ 'csp' ] == "search_add" ) )
		return $get_sql_data;

	$get_date = new stdClass;
	
	// ターゲットを取得
	if( isset( $_GET['s_target']) == true )
		$get_date->option_value = $_GET['s_target'];
	else
	{
		$sql  = " SELECT * FROM $wpdb->options";
		$sql .= " WHERE option_name ='$feadvns_sort_target$manag_no'";
		$sql .= " LIMIT 1 ";
		$get_date = $wpdb->get_results( $sql );
		if( $get_date )
			$get_date = $get_date[0];
	}
	
	if( isset( $get_date->option_value ) == true && $get_date->option_value != null )
	{
		// ユーザーソート
		if( isset( $_GET['csfk'] ) == true && $_GET['csfk'] != null )
			$sortKey = $_GET['csfk'];
		
		// 初期
		elseif( 'post_meta' == $get_date->option_value )
		{
			// ソートのデータを取得
			$sortKey = $feadvns_sort_target_cfkey . $manag_no;
			$sortKey = db_op_get_value( $sortKey );
		}
		
		//if( 'custom_field' == $get_date->option_value || 'post_meta' == $get_date->option_value )
		if( 'post_meta' == $get_date->option_value )
		{
			$or_sort  = " ( SELECT IF( " . $wpdb->postmeta . ".meta_key != '" . esc_sql( $sortKey ) . "', 1, 0 ) FROM " . $wpdb->posts . " AS pt ";
			$or_sort .= " LEFT JOIN " . $wpdb->postmeta . " ON pt.ID = " . $wpdb->postmeta . ".post_id";
			$or_sort .= " WHERE pt.ID = " . $wpdb->posts . ".ID";
			$or_sort .= " AND " . $wpdb->postmeta . ".meta_key != '_edit_last'";
			$or_sort .= " AND " . $wpdb->postmeta . ".meta_key != '_edit_lock'";
			$or_sort .= " AND " . $wpdb->postmeta . ".meta_key = '" . esc_sql( $sortKey ) . "'";
			$or_sort .= " LIMIT 1";
			$or_sort .= " ) AS cf,";

			$or_sort .= " ( SELECT IF( " . $wpdb->postmeta . ".meta_key !='" . esc_sql( $sortKey ) . "', NULL, " . $wpdb->postmeta . ".meta_value )" . " FROM " . $wpdb->posts . " AS ptv";
			$or_sort .= " LEFT JOIN " . $wpdb->postmeta . " ON ptv.ID = " . $wpdb->postmeta . ".post_id";
			$or_sort .= " WHERE ptv.ID =" . $wpdb->posts . ".ID";
			$or_sort .= " AND " . $wpdb->postmeta . ".meta_key != '_edit_last'";
			$or_sort .= " AND " . $wpdb->postmeta . ".meta_key != '_edit_lock'";
			$or_sort .= " AND " . $wpdb->postmeta . ".meta_key = '" . esc_sql( $sortKey ) . "'";
			$or_sort .= " LIMIT 1";
			$or_sort .= " ) AS cf_v";	
			$get_sql_data = str_replace( $wpdb->posts . ".* FROM " . $wpdb->posts, $wpdb->posts . ".*, " . $or_sort . " FROM " . $wpdb->posts, $get_sql_data );
		}

		// 未使用
		if( $get_date->option_value == "tag" )
		{
			// 並び順を取得
			if( isset( $_GET['s_order'] ) == true )
				$get_sort_data->option_value = $_GET['s_order'];
			else
			{
				$sql  = " SELECT * FROM " .$wpdb->options;
				$sql .= " WHERE option_name = '" .$feadvns_sort_order .$manag_no ."'";
				$sql .= " LIMIT 1";
				$get_sort_data = $wpdb->get_results( $sql );
			}

			// 降順昇順を取得
			$get_sort = " DESC ";
			if( isset( $get_sort_data->option_value ) == true && $get_sort_data->option_value != null )
			{
				if( $get_sort_data->option_value == "up" )
					$get_sort = " ASC ";
			}

			$or_sort  = "( SELECT " .$wpdb->term_taxonomy .".taxonomy FROM " .$wpdb->posts ." AS pt ";
			$or_sort .= " LEFT JOIN " .$wpdb->postmeta ." ON pt.id = " .$wpdb->postmeta .".post_id";
			$or_sort .= " LEFT JOIN " .$wpdb->term_relationships ." ON pt.id = " .$wpdb->term_relationships .".object_id";
			$or_sort .= " LEFT JOIN " .$wpdb->term_taxonomy ." ON " .$wpdb->term_relationships .".term_taxonomy_id = " .$wpdb->term_taxonomy .".term_taxonomy_id";
			$or_sort .= " LEFT JOIN " .$wpdb->terms ." ON " .$wpdb->term_taxonomy .".term_id = " .$wpdb->terms .".term_id";
			$or_sort .= " WHERE pt.id =" .$wpdb->posts .".id";
			$or_sort .= " ORDER BY " .$wpdb->term_taxonomy .".taxonomy DESC";
			$or_sort .= " LIMIT 1";
			$or_sort .= " ) AS tn,";

			$or_sort .= "( SELECT " .$wpdb->terms .".name FROM " .$wpdb->posts ." AS pt ";
			$or_sort .= " LEFT JOIN " .$wpdb->postmeta ." ON pt.id = " .$wpdb->postmeta .".post_id";
			$or_sort .= " LEFT JOIN " .$wpdb->term_relationships ." ON pt.id = " .$wpdb->term_relationships .".object_id";
			$or_sort .= " LEFT JOIN " .$wpdb->term_taxonomy ." ON " .$wpdb->term_relationships .".term_taxonomy_id = " .$wpdb->term_taxonomy .".term_taxonomy_id";
			$or_sort .= " LEFT JOIN " .$wpdb->terms ." ON " .$wpdb->term_taxonomy .".term_id = " .$wpdb->terms .".term_id";
			$or_sort .= " WHERE pt.id =" .$wpdb->posts .".id";
			$or_sort .= " ORDER BY " .$wpdb->terms .".name " .$get_sort;
			$or_sort .= " LIMIT 1";
			$or_sort .= " ) AS tname";

			$get_sql_data = str_replace( $wpdb->posts .".* FROM ".$wpdb->posts, $wpdb->posts .".*, " .$or_sort ." FROM " .$wpdb->posts, $get_sql_data );
		}
	}
	
	return $get_sql_data;
}

/*************************************************/
/*ソート用並び替えを表示
/*************************************************/
function feas_sort_menu( $id = 0, $shortcode_f = null ){
	global $wpdb, $feadvns_max_line_order, $manag_order_no, $cols, $cols_order, $meta_sort_key;

	if( is_numeric( $id ) )
		$id = absint( $id );
	else
		$id = 0;
		
	$manag_order_no = $id;
	
	$keys = array_keys( $_GET );
	$get_st = null;
	
	for( $i = 0, $cnt = count( $keys ); $i < $cnt; $i++ )
	{
		if( $keys[$i] != "s_target" && $keys[$i] != "s_order" )
		{
			if( $i > 0 )
				$get_st .= "&amp;";
			
			// リストボックス形式の場合
			if( is_array( $_GET[$keys[$i]] ) )
			{
				for( $i_key = 0, $cnt_key = count( $_GET[$keys[$i]] ); $i_key < $cnt_key; $i_key++ )
				{
					if( $i_key > 0 )
						$get_st .= "&amp;";
					$get_st .= $keys[$i] . "%5B%5D=" . $_GET[$keys[$i]][$i_key];
				}
			}
			// その他
			else
				$get_st .= $keys[$i] . "=" . $_GET[$keys[$i]];
		}
	}

	// オーダー番号取得
	$sql  = " SELECT option_name FROM " .$wpdb->options;
	$sql .= " WHERE option_name LIKE '" .$cols_order[2]. $manag_order_no ."_"."%'";
	$sql .= " ORDER BY option_value ASC";
	$get_op_sort = $wpdb->get_results( $sql );

	for( $i = 0, $cnt = count( $get_op_sort ); $i < $cnt; $i++ )
	{
		// 並び順取得
		$get_order[] = substr( $get_op_sort[$i]->option_name, -1 );
	}

	// ソートのデータを取得
	$line_key = $feadvns_max_line_order . $manag_order_no;
	$line_cnt = db_op_get_value( $line_key );
		
	// ソートデータ取得
	$ret_disp = null;
	for( $i = 0; $i < $line_cnt; $i++ )
	{
		if( db_op_get_value( $cols_order[1] . $manag_order_no . "_" . $i ) == "0" )
		{
			$ret_disp .= str_replace( '\\', '', db_op_get_value( $cols_order[4] . $manag_order_no . "_" . $get_order[$i] ) );
			$ret_disp .= str_replace( '\\', '', db_op_get_value( $cols_order[6] . $manag_order_no . "_" . $get_order[$i] ) );
			
			$sort_btn_up   = str_replace( '\\', '', db_op_get_value( $cols_order[7] . $manag_order_no . "_" . $get_order[$i] ) );
			$sort_btn_down = str_replace( '\\', '', db_op_get_value( $cols_order[8] . $manag_order_no . "_" . $get_order[$i] ) );

			// カスタムフィールド
			if( db_op_get_value( $cols_order[0] . $manag_order_no . "_" . $get_order[$i] ) == 'post_meta' )
			{
				$get_meta_key = db_op_get_value( $cols_order[9] . $manag_order_no . "_" . $get_order[$i] );
				$get_csfk_as = db_op_get_value( $cols_order[10] . $manag_order_no . "_" . $get_order[$i] );

				$ret_disp .= "<span class='feas-sl-" . ( $i + 1 ) . "-up'><a href='" . get_option( "home" ) . "/?" . $get_st . "&amp;s_target=post_meta&amp;s_order=up&amp;csfk=" . $get_meta_key . "&amp;csfk_as=" . $get_csfk_as . "'>" . $sort_btn_up ."</a></span>";
				$ret_disp .= "<span class='feas-sl-" . ( $i + 1 ) . "-down'><a href='" . get_option( "home" ) . "/?" . $get_st . "&amp;s_target=post_meta&amp;s_order=down&amp;csfk=" . $get_meta_key . "&amp;csfk_as=". $get_csfk_as . "'>" . $sort_btn_down ."</a></span>";
			
			} else {
				
				$ret_disp .= "<span class='feas-sl-" . ( $i + 1 ) . "-up'><a href='" .get_option( "home" ) . "/?" . $get_st . "&amp;s_target=" . db_op_get_value( $cols_order[0] . $manag_order_no . "_" .$get_order[$i] ) . "&amp;s_order=up'>" . $sort_btn_up . "</a></span>";
				$ret_disp .= "<span class='feas-sl-" . ( $i + 1 ) . "-down'><a href='" .get_option( "home" ) . "/?" . $get_st . "&amp;s_target=" . db_op_get_value( $cols_order[0] . $manag_order_no . "_" .$get_order[$i] ) . "&amp;s_order=down'>" . $sort_btn_down . "</a></span>";
			}
			
			$ret_disp .= str_replace( '\\', '', db_op_get_value( $cols_order[5] . $manag_order_no . "_" . $get_order[$i] ) );
		}
	}

	if( $shortcode_f == null )
		print( $ret_disp );
	else
		return $ret_disp;
}
?>
