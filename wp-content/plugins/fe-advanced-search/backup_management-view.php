<?php
/******************
表示部
*******************/
?>
<div class="wrap">
	<div id="feas-admin">
		<div id="feas-head" class="clearfix">
			<h2>FE Advanced Search</h2>
			<p>FE Advanced Searchに関するすべてのデータをCSV形式でエクスポート、インポートします。</p>
		</div>
		
		<?php
		/******************************
		検索フォーム全体の設定
		******************************/
		?>
		
		<h3 id="feas-sectitle" class="left">設定データのエクスポート</h3>
	
		<form action="" method="post">	
			<input type="hidden" name="file" value="export">
			<input type="submit" value="ダウンロード" class="button-secondary action" />
			<?php wp_nonce_field( 'feas-nonce-key', 'feas-backup' ); ?>
		</form>
		
		<h3 id="feas-sectitle" class="left">設定データのインポート</h3>
		
		<?php
			feas_import_upload_form( menu_page_url( 'backup_management', false ) );
		?>
		
	</div>
</div>