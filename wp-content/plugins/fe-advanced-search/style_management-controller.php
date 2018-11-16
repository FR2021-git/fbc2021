<?php
	// 初期のスタイル取得
	require_once( 'default_style.php' );

	// 使用する場合はcheckedをつける
	$use_checked = null;

	if( isset( $_POST['style_update'] ) && $_POST['style_update'] == 'update' )
	{
		$use_value =0;
		//styleを使用するかのcheck
		if( isset( $_POST['use_style'] ) )
			$use_value = 1;
		
		$form_id = intval( $_POST['style_id'] );

		$sql  = " SELECT option_id FROM " . $wpdb->options;
		$sql .= " WHERE option_name ='" . $use_style_key . $form_id . "'";
		$sql .= " LIMIT 1";
		$get_date = $wpdb->get_results( $sql );

		if( isset( $get_date[0]->option_id ) == true )
			db_op_update_value( $use_style_key . $form_id, $use_value );
		else
			db_op_insert_value( $use_style_key . $form_id, $use_value );

		//styleを取得
		if( isset( $_POST['style_body'] ) )
		{
			if(db_op_field_check( $style_body_key . $form_id ) != null )
				db_op_update_value( $style_body_key . $form_id, $_POST['style_body'] );
			else
				db_op_insert_value( $style_body_key . $form_id, $_POST['style_body'] );
		}
		
		$_POST['c_form_number'] = $form_id;
	}

	if( !isset( $_POST['style_id'] ) )
		$form_id = $manag_no;

	if( db_op_get_value( $use_style_key . $form_id ) == 1 )
		$use_checked = ' checked="checked"';

	$get_style_body = db_op_get_value( $style_body_key . $form_id );
	
	if( $get_style_body != null )
		$style_body = $get_style_body;
	else
		$style_body = feas_default_style( $form_id );

	// 作成済みフォームの数（ID）を取得
	$get_form_max = db_op_get_value( $feadvns_max_page );

?>