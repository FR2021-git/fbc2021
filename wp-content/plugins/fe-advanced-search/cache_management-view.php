<?php 
/* cache_management-view */
?>
<div class="wrap">
	<div id="feas-admin">
		<div id="feas-head" class="clearfix">
			<?php if( function_exists('screen_icon') )
				screen_icon( 'options-general' ); ?>
				
			<h2>FE Advanced Search &raquo; キャッシュ設定</h2>
			<?php
			if($return_console != null){
					echo '<div id="message" class="updated fade">';
					echo $return_console;
					echo '</div>';
			}?>
			<div id="feas-support">
				<a href="<?php bloginfo('wpurl'); ?>/wp-content/plugins/fe-advanced-search/manual/index.html" target="_blank">使用説明書を見る</a>｜<a href="http://forums.firstelement.jp/forum.php?id=6" target="_blank">フォーラム（掲示板）</a>｜<a href="mailto:info@firstelement.jp">メールで問い合わせる</a>
			</div>
		</div><!-- feas-head -->

		
		<form method="post">
			<div id="feas-sec" class="clearfix" style="width: 400px;">
				<table class="widefat">
					<tr>
						<th>
							キャッシュを使用する
						</th>
						<td>
							<input type="checkbox" name="feas_cache_enable" value="enable" <?php if($cache_flag == 'enable'){ echo 'checked=checked' ;}?>>
						</td>
					</tr>
					<tr>
						<th>
							キャッシュ有効期限
						</th>
						<td>
							<span style="font-size: 0.9em;">0秒に設定すると永久にキャッシュし続ける</span><br />
							<input type="text" name="feas_cache_time" value="<?php if($cache_time !== null){ echo $cache_time; } ?>">秒
						</td>
					</tr>
				</table>
			</div>
			<input type="submit" class="button-primary action" name="feas_cache_page" value="変更">
		</form>
		
		
		
		<h3>キャッシュの削除</h3>
		<form method="post">
			<input type="submit" class="button-primary action" name="feas_cache_cache" value="全てのキャッシュを削除する">
		</form>
		
		<!-- <h3>キャッシュ状況</h3>
		<div>
			<?php /*
			if($get_transient_list != null){
				echo '<p>現在<ul>';
				foreach($get_transient_list as $key){
					
					echo '<li>ID.'.$key['id'].'：'.db_op_get_value( $feadvns_search_form_name . $key['id'] ).'</li>';
					
				}
				echo '</ul>がキャッシュされています</p>';
			}else{
				echo '<p>現在キャッシュされているフォームは有りません</p>';
			}
			*/?>
		</div> -->
	</div><!-- feas-admin -->
	<style type="text/css"> <!--

.clearfix:after {
  content: "."; 
  display: block;
  clear: both;
  height: 0;
  visibility: hidden;
}

.clearfix {
  min-height: 1px;
}

* html .clearfix {
  height: 1px;
  /*\*//*/
  height: auto;
  overflow: hidden;
  /**/
}

.text-small {
	font-size:  80%;
	font-weight:  normal;
}

#wpcontent select{
	height: auto;
	}

	
#feas-support {
	font-size: 12px;
	float: right;
	width:  55%;
	height:  auto;
	/*line-height:  30px;*/
	text-align:  right;
	/*margin-top:  3px;*/
	}
	
#feas-selform {
	width: 30%;
	height:  auto;
	float:  right;
	text-align:  right;
	}
	
h3.#feas-sectitle {
	width: 70%;
	height:  auto;
	float:  left;
	}	

#feas-sec th {
	width: 40%;
	background-color:  #FFFFFF;
	border-right: 1px solid #E1E1E1;
	border-bottom: 1px solid #E1E1E1;
	white-space: nowrap;
	}

#feas-sec td {
	background-color:  #F9F9F9;
	border-bottom: 1px solid #E1E1E1;
}
	
#feas-pv {
	margin:  10px 0;
	}
	
#feas-pv h3 {
	margin:  0 0 0.5em;
	}
	
#feas-pv p {
	margin:  0 0 0.5em;
	}
	
#feas-code-sample {
	/*width:100%;*/
	/*background-color: #FFEFE8;*/
	color: #FF6600;
	margin: 10px 0;
	padding: 5px;
	}
#feas-code-sample textarea {
	font-size: 12px;
	background-color: #FFFFCC;
	}
	
#feas-admin table {
	width: 100%;
	}
	
#feas-admin .action {
	margin: 5px 0;
	}
	
#feas-admin table td {
	padding: 5px 2px;
	vertical-align: middle;
	}
	
.feas-members2 {
	text-align: right;
	padding: 5px;
	}
	
.feas-members3 {
	text-align: right;
	padding: 5px;
	font-size: 80%;
	height: 20px;
	}
	
.feas-members3 label,
.feas-members3 input,
.feas-members3 select {
	display: inline;
	vertical-align: middle;
	line-height: 20px;
	}
	
.feas-members4 {
	text-align: left;
	padding: 5px;
	float: left;
	}
	
.grayout,
.grayout input {
	background-color:  #eee;
}
#message {
	/*clear: both;
	float: left;
	width: 35%;*/
	}
#feas-admin h4 {
	margin:  0;
}
.feas-members4 label {
	display: block;
	/*width: 100px;*/
}
.feas-members4 th,
.feas-members4 td {
	border:  none;
	padding: 0 !important;
	margin: 0;
	font-weight: normal;
	line-height: 1em;
}
--> </style>
</div><!-- wrap -->