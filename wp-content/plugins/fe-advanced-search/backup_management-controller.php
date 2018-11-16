<?php
/*************************************************/
/*	設定データをエクスポート
/*************************************************/
function feas_export_settings_data(){
	global $wpdb;
	
	// エクスポート
	if ( array_key_exists( 'file', $_POST ) && isset( $_POST['file'] ) && "export" == $_POST['file'] ) {
		
		if( check_admin_referer( 'feas-nonce-key', 'feas-backup' ) ){
			
			$e = new WP_Error();
			
			// FEAS関連を抽出
			$sql      = " SELECT * FROM {$wpdb->options} AS op ";
			$sql	 .= " WHERE op.option_name LIKE 'feadvns_%' ";
			$sql	 .= " OR op.option_name LIKE 'feas_%' ";
			
			$results  = $wpdb->get_results( $sql, ARRAY_A );
	
			if( !empty( $results ) ){
				
				$csv = '';
				
				// CSV作成
				foreach( $results as $result ){
				    $i = 0;
				    foreach( $result as $key => $val ){
				        $csv .= mb_convert_encoding( $val, 'SJIS', 'UTF-8' );
				        if( $i < count( $result )-1 )
				        	$csv .= ',';
				        $i++;
				    }
				    $csv .= "\r\n";
				}
				
				// 出力
				$title = "feas_setting_data_" . date( "YmdHis" ) . ".csv";
				header( 'Content-Type: application/octet-stream' );
				header( 'Content-Disposition: attachment; filename=' . $title );
				echo $csv;
				
				exit;
				
			} else {
			
				$e->add( 'error', 'FE Advanced Searchに関連するデータはありません' );
				set_transient( 'feas_import_settings_data', $e->get_error_messages(), 10 );
			
			}
			
			wp_safe_redirect( menu_page_url( 'backup_management', false ) );
		}
	}
}
add_action( 'admin_init', 'feas_export_settings_data' );


/*************************************************/
/*	設定データをインポート
/*************************************************/
function feas_import_settings_data(){
		
	// インポート
	if ( array_key_exists( 'action', $_POST ) && isset( $_POST['action'] ) && "save" == $_POST['action'] ) {
			
		if ( false != wp_verify_nonce( $_GET['_wpnonce'], 'feas-import-upload' ) ) {
				
			$e = new WP_Error();
			
			if( UPLOAD_ERR_OK == $_FILES['import']['error'] ){
				
				$filename = '';
				
				// CSV形式のみ
				$mimes = array( 'application/vnd.ms-excel', 'text/plain', 'text/csv', 'text/tsv' );
				
				if( in_array( $_FILES['import']['type'], $mimes ) ){
					
					$tempfile = $_FILES['import']['tmp_name'];
					$filename = $_FILES['import']['name'];
					$filename = mb_convert_encoding( $filename, 'UTF-8', 'auto' );
					
					// 一時ファイルから移動・保存
					$result   = move_uploaded_file( $tempfile, $filename );
					
					if( false == $result ){
						
						$e->add( 'error', 'ファイルの移動に失敗しました' );
						set_transient( 'feas_import_settings_data', $e->get_error_messages(), 10 );
						set_transient( 'feas_import_settings_notice_flag', 'error', 10 );
					}
					
				} else {
					
					$e->add( 'error', 'CSV形式のデータではありません' );
					set_transient( 'feas_import_settings_data', $e->get_error_messages(), 10 );
					set_transient( 'feas_import_settings_notice_flag', 'error', 10 );
					
				}				
			}
			else if( UPLOAD_ERR_NO_FILE == $_FILES['import']['error'] ) {
				
				$e->add( 'error', 'ファイルがアップロードされませんでした' );
				set_transient( 'feas_import_settings_data', $e->get_error_messages(), 10 );
				set_transient( 'feas_import_settings_notice_flag', 'error', 10 );
			}
			else {
				
				$e->add( 'error', 'ファイルのアップロードに失敗しました' );
				set_transient( 'feas_import_settings_data', $e->get_error_messages(), 10 );
				set_transient( 'feas_import_settings_notice_flag', 'error', 10 );
			}
			
			if( !empty( $filename ) ){
				
				// ファイル読み込み
				$buf = file_get_contents( $filename ); 
				$buf = mb_convert_encoding( $buf, 'UTF-8', 'SJIS' );
				
				// 配列に格納
				$lines = explode( "\r\n", $buf ); 
				foreach ( $lines as $line ) {
				    $records[] = explode( ',', $line ); 
				}
				
				// オプション情報のキャッシュを削除
				wp_cache_delete( 'alloptions', 'options' );
				
				$status = null;
				$i = 0;
				foreach( $records as $record ){
					
					$name     = ( !empty( $record[1] ) ) ? $record[1] : false;
					if( false === $name )
						continue;
					$value    = $record[2];
					$autoload = $record[3];
					
					// 行ごとにオプションテーブルに格納
					$status = update_option( $name, $value, $autoload );
					if( true == $status )
						$i++;
				}
				
				if( 0 < $i ){
					$e->add( 'ok', 'ファイルのインポートに成功しました' );
					set_transient( 'feas_import_settings_data', $e->get_error_messages(), 10 );
					set_transient( 'feas_import_settings_notice_flag', 'updated', 10 );
				} else {
					$e->add( 'ok', 'データに変更はありません' );
					set_transient( 'feas_import_settings_data', $e->get_error_messages(), 10 );
					set_transient( 'feas_import_settings_notice_flag', 'updated', 10 );
				}
			}
				
			wp_safe_redirect( menu_page_url( 'backup_management', false ) );
		}
	}
}
add_action( 'admin_init', 'feas_import_settings_data' );

/*************************************************/
/*	メッセージ
/*************************************************/
function feas_admin_notices(){
	
	$messages    = get_transient( 'feas_import_settings_data' );
	$notice_flag = get_transient( 'feas_import_settings_notice_flag' );
	
	if( 'updated' == $notice_flag )
		$add_class = 'updated';
	else
		$add_class = 'error';
	
	if( $messages ):
?>
		<div class="<?php echo esc_attr( $add_class ); ?>">
			<ul>
				<?php foreach( $messages as $message ): ?>
					<li><?php echo esc_html( $message ); ?></li>
				<?php endforeach; ?>
			</ul>
		</div>
<?php
	endif;
}

add_action( 'admin_notices', 'feas_admin_notices' );

/*************************************************/
/*	インポート用CSVアップロードフォーム
/*************************************************/
function feas_import_upload_form( $action ) {
 
    /**
     * wp_import_upload_form を複製（ほぼそのまま）
     */
    $bytes = apply_filters( 'import_upload_size_limit', wp_max_upload_size() );
    $size = size_format( $bytes );
    $upload_dir = wp_upload_dir();
    if ( ! empty( $upload_dir['error'] ) ) :
        ?><div class="error"><p><?php _e('Before you can upload your import file, you will need to fix the following error:'); ?></p>
        <p><strong><?php echo $upload_dir['error']; ?></strong></p></div><?php
    else :
?>
<form enctype="multipart/form-data" id="import-upload-form" method="post" class="wp-upload-form" action="<?php echo esc_url( wp_nonce_url( $action, 'feas-import-upload' ) ); ?>">
<p>
<label for="upload"><?php _e( 'Choose a file from your computer:' ); ?></label> (<?php printf( __('Maximum size: %s' ), $size ); ?>)
<input type="file" id="upload" name="import" size="25" />
<input type="hidden" name="action" value="save" />
<input type="hidden" name="max_file_size" value="<?php echo $bytes; ?>" />
</p>
<?php submit_button( __('Upload file and import'), 'primary' ); ?>
</form>
<?php
    endif;
}