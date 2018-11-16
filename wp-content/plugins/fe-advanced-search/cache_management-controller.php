<?php 
/* cache_management-controller */


/*
DBにキャッシュ設定のデータが有るか確認。
・キャッシュのオンオフ
・キャッシュ時間

パージー
$_postで


*/
global $feas_cache_enable, $feas_cache_time;

if( isset( $_POST['feas_cache_page'] ) && ( $_POST['feas_cache_enable'] == 'enable' ) ) {

	$check = db_op_field_check( $feas_cache_enable );
	if( $check == null )
		db_op_insert_value( $feas_cache_enable, 'enable' );
	else
		db_op_update_value( $feas_cache_enable, 'enable' );

} elseif( isset( $_POST['feas_cache_page'] ) && !isset( $_POST['feas_cache_enable'] ) ) {

	db_op_delete_value( $feas_cache_enable );

}

if( isset( $_POST['feas_cache_time'] ) && ( ctype_digit( $_POST['feas_cache_time'] ) ) ) {

	$check = db_op_field_check( $feas_cache_time );
	if( $check == null )
		db_op_insert_value( $feas_cache_time, intval( $_POST['feas_cache_time'] ) );
	else
		db_op_update_value( $feas_cache_time, intval( $_POST['feas_cache_time'] ) );
}

if( isset( $_POST['feas_cache_cache'] ) && ( $_POST['feas_cache_cache'] == '全てのキャッシュを削除する' ) ) {
	$return_console = feas_delete_transient_all();
} else {
	$return_console = null;
}

//model

/*function feas_get_transient_list(){ //キャッシュされてる一覧
	global $cols,$manag_no,$feadvns_max_page;
	
	$return = array();
	$get_form_max = db_op_get_value( $feadvns_max_page );
	$get_form_max++;
	for($i = 0 ; $i <= $get_form_max ; $i++){
		if($get = get_transient($cols[23].$i)){
			
			$return[] = array( 'id' => $i , 'val' => $get);
		}
	}
	return $return;
}

$get_transient_list = feas_get_transient_list();*/

$cache_flag = db_op_get_value( $feas_cache_enable );
$cache_time = db_op_get_value( $feas_cache_time );

?>