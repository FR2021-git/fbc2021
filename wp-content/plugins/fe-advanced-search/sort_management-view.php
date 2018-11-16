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
#feas-selform select,
#feas-selform input[type="submit"] {
	margin: 0.2em 0;
}	
h3.#feas-sectitle {
	width: 70%;
	height:  auto;
	float:  left;
	}
	
#feas-sec th {
	width: 20%;
	background-color:  #efefef;
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
	margin: 10px 0;
	}
	
#feas-code-sample textarea {
	font-size: 12px;
	background-color: #FFFFCC;
	}
	
#feas-admin {
	margin: 0 0 20px;
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
/* 	display: inline; */
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
.feadvns_sort_target_cfkey,
.feadvns_sort_target_cfkey_as {
	display: none;
}
.left { float: left; }
.right { float: right; }

@media only screen and (max-width: 768px) {
    table thead{
        display: block;
        float: left;
    }
    table tbody{
        display: block;
        width: auto;
        position: relative;
        overflow-x: scroll;
        white-space: nowrap;
    }
    table tbody tr{
        display: inline-block;
        vertical-align: top;
    }
    table th,
    table td {
	    display: block;
    }
    table td input {
	    float: left;
	    margin: 0 5px 3px 0;
    }
    table tfoot {
	    display: none;
    }
}
--> </style>

<div class="wrap">
<div id="feas-admin">
<div id="feas-head" class="clearfix">
	<h2>FE Advanced Search &raquo; ソート設定</h2>
	
	<?php if( ( isset( $_POST['ac'] ) && $_POST['ac'] == 'update' ) ||  ( isset( $_POST['gs'] ) && $_POST['gs'] == 'update' ) ) { ?>
		<div id="message"  class="updated">正常に設定が保存されました．</div>
	<?php } else if( isset( $_POST[ 'c_form_number' ] ) && $_POST[ 'c_form_number' ] == 'new' ) { ?>
		<div id="message"  class="updated">新しい検索フォームが作成されました．</div>
	<?php } else if( isset( $_POST[ 'c_form_number' ] ) && $_POST[ 'c_form_number' ] == 'del' ) { ?>
		<div id="message"  class="updated">ソートボタンが削除されました．</div>
	<?php } else if( isset( $_POST[ 'line_action' ] ) && $_POST[ 'line_action' ] == 'add_line' ) { ?>
		<div id="message"  class="updated">ソートボタンが追加されました．</div>
	<?php } ?>
	
	<div id="feas-support">
		<a href="<?php bloginfo('wpurl'); ?>/wp-content/plugins/fe-advanced-search/manual/index.html" target="_blank">使用説明書を見る</a>｜<a href="http://forums.firstelement.jp/forum.php?id=6" target="_blank">フォーラム（掲示板）</a>｜<a href="mailto:info@firstelement.jp">メールで問い合わせる</a>
	</div>
</div>

	<?php
	/******************
	フォームの選択プルダウン
	*******************/
	?>
	<div id="feas-selform" class="right">
		<form action="" method="post">
			<select name="c_order_number">
			<?php for( $i = 0; $i <= $get_form_max; $i++ )
			{
				$fname = $selected = '';
		
				if( $_POST['c_order_number'] == $i )
					$selected = 'selected="selected"';
					
				$fname = db_op_get_value( $feadvns_search_form_name . $i );
				
				if( $fname )
					$fname = $fname;
				else
					$fname = '（フォームID = ' . $i . '）';
				?>
				
				<option value="<?php echo esc_attr( $i ); ?>" <?php echo $selected; ?>><?php echo esc_html( $fname ); ?></option>
					
				<?php
			}
			?>
			</select>
			<input type="submit" value="選択" class="button-secondary action" />
		</form>
	</div><!--feas-selform-->

<div class="clearfix" style="clear:both;">
	
	<h3 id="feas-sectitle" class="left">検索フォーム「<?php echo esc_html( db_op_get_value( $feadvns_search_form_name . $manag_order_no ) ); ?>（No.<?php echo esc_html( $manag_order_no ); ?>）」に対応するソートボタンの設定</h3>
	
	<!--
	<div id="feas-sec" class="clearfix">
		<table class="widefat">
			<tr>
				<th>検索フォームの名称</th>
				<td><?php echo esc_attr( db_op_get_value( $feadvns_search_form_name . $manag_order_no ) ); ?></td>
				<th>フォームID</th>
				<td><?php echo esc_html( $manag_order_no ); ?></td>
			</tr>
		</table>
	</div>
	-->
	
	<?php
	/******************************
		ソートボタンの内容
	******************************/
	?>
	
	
	<form action='' method='POST' style='float:right;'>
		<input type='submit' value='項目を追加' class='button-secondary action' />
		<input type='hidden' name='line_action' value='add_line' />
		<input type='hidden' name='c_order_number' value='<?php echo esc_attr( $manag_order_no ); ?>' />
	</form>
	
	<form action='' method='POST' name='fm' onSubmit ="return order_check();">
	
		<table class="widefat" style='clear:both;'>
			<thead>
				<tr>
					<th style="width: 10%;">ラベル</th>
					<th style="width: 50%;">ターゲット</th>
					<th style="width: 15%;">昇順テキスト/画像</th>
					<th style="width: 15%;">降順テキスト/画像</th>
					<th style="width: 10%;">&nbsp;</th>
				</tr>
			</thead>
			<tbody>
				<?php for( $i = 0; $i < $line_cnt; $i++ ){ 
				
					// 「表示しない」設定の行は背景をグレイに
					$addclass_gray = null;
					if( db_op_get_value( $cols_order[1] . $manag_order_no . "_" . $i ) == 1 )
						$addclass_gray = "grayout";
				?>
					<tr class="widefat alternate <?php echo esc_attr( $addclass_gray ); ?>">
						<td>
							<input type='text' name='<?php echo esc_attr( $cols_order[6] . $manag_order_no . "_" . $i ); ?>' id='<?php echo esc_attr( $cols_order[6] . $manag_order_no . "_" . $i ); ?>' style='width:150px' value='<?php echo esc_attr( data_to_post( $cols_order[6] . $manag_order_no . "_" . $i ) ); ?>'>
						</td>
						<td>
								<?php $j_cf = 'cfkey_check(' . $i .')'; ?>
								
								<?php
								/************
								ターゲット
								************/
								?>
								<select id="<?php echo esc_attr( $cols_order[0] . $manag_order_no . "_" . $i ); ?>" name="<?php echo esc_attr( $cols_order[0] . $manag_order_no . "_" . $i ); ?>" style="float:left;" onChange="<?php echo esc_attr( $j_cf ); ?>" onBlur="<?php echo esc_attr( $j_cf ); ?>" onChange="cfkey_check()">
									<?php
									$op_keys = array(
										'post_date'  => '投稿日時',
										'post_title' => 'タイトル',
										'post_name'  => 'スラッグ',
										'post_meta'  => 'カスタムフィールド',
										'rand'       => 'ランダム'
									);
									
									foreach( $op_keys as $k => $v ):
										$selected = '';
										if( isset( $_POST[$cols_order[0] . $manag_order_no . "_" . $i] ) == true && $_POST[$cols_order[0] . $manag_order_no . "_" . $i] == $k )
											$selected = 'selected="selected"';
										?>
										<option value="<?php echo esc_attr( $k ); ?>" <?php echo $selected; ?> ><?php echo esc_html( $v ); ?></option>
										<?php
									endforeach;
									?>
								</select>
								
								<?php
								/************
								カスタムフィールドのキー
								************/
								?>
								<select id="<?php echo $cols_order[9] . $manag_order_no . "_" . $i; ?>" class="feadvns_sort_target_cfkey" name="<?php echo $cols_order[9] . $manag_order_no . "_" . $i; ?>">
									<?php
									for( $i_meta = 0, $cnt_meta = count( $get_metas ); $i_meta < $cnt_meta; $i_meta++ )
									{
										$selected = '';
										if( isset( $_POST[$cols_order[9] . $manag_order_no . "_" . $i] ) == true && $_POST[$cols_order[9] . $manag_order_no . "_" . $i] == $get_metas[$i_meta]->meta_key )
											$selected = 'selected="selected"';
										?>
										<option value="<?php echo esc_attr( $get_metas[$i_meta]->meta_key ); ?>" <?php echo $selected; ?>><?php echo esc_html( $get_metas[$i_meta]->meta_key ); ?></option>
										<?php
									}
									?>
								</select>
													
								<?php
								/************
								数値か文字か
								************/
								$selected_1 = $selected_2 = '';
								
								if( isset( $_POST[$cols_order[10] . $manag_order_no . "_" . $i] ) == true && $_POST[$cols_order[10] . $manag_order_no . "_" . $i] == 'str' )
									$selected_2 = 'selected="selected"';
								else
									$selected_1 = 'selected="selected"';
								?>
								
								<select id="<?php echo $cols_order[10] . $manag_order_no . "_" . $i; ?>" class="feadvns_sort_target_cfkey_as" name="<?php echo $cols_order[10] . $manag_order_no . "_" . $i; ?>">
									<option value="int" <?php echo $selected_1; ?>>数値</option>
									<option value="str" <?php echo $selected_2; ?>>文字</option>
								</select>
						</td>
						<td>
								<input type='text' name='<?php echo esc_attr( $cols_order[7] . $manag_order_no . "_" . $i ); ?>' id='<?php echo esc_attr( $cols_order[7] . $manag_order_no . "_" . $i ); ?>' style='width:150px' value='<?php echo esc_attr( data_to_post( $cols_order[7] . $manag_order_no . "_" . $i ) ); ?>' >
						</td>
						<td>
								<input type='text' name='<?php echo esc_attr( $cols_order[8] . $manag_order_no . "_" . $i ); ?>' id='<?php echo esc_attr( $cols_order[8] . $manag_order_no . "_" . $i ); ?>' style='width:150px' value='<?php echo esc_attr( data_to_post( $cols_order[8] . $manag_order_no . "_" . $i ) ); ?>'>
						</td>
						<td>
						</td>
					</tr>
					
					<tr class="<?php echo esc_attr( $addclass_gray ); ?>">
						<td colspan="2" style="vertical-align: top;"></td>
						<td colspan="3" style="vertical-align: top;">
								<div class="feas-members2">
										<label>前に挿入 <input type='text' name='<?php echo esc_attr( $cols_order[4] . $manag_order_no . "_" . $i ); ?>' id='<?php echo esc_attr( $cols_order[4] . $manag_order_no . "_" . $i ); ?>' style='width: 80%'  value='<?php echo esc_attr( data_to_post( $cols_order[4] . $manag_order_no . "_" . $i ) ); ?>' ></label><br />
										<label>後に挿入 <input type='text' name='<?php echo esc_attr( $cols_order[5] . $manag_order_no . "_" . $i ); ?>' id='<?php echo esc_attr( $cols_order[5] .  $manag_order_no . "_" . $i ); ?>' style='width: 80%'  value='<?php echo esc_attr( data_to_post( $cols_order[5] . $manag_order_no . "_" . $i ) ); ?>' ></label>
								</div>
								<div class="feas-members3">
										<label for='<?php echo esc_attr( $cols_order[2] . $manag_order_no . "_" . $i ); ?>'>並び順
										<select name='<?php echo esc_attr( $cols_order[2] . $manag_order_no . "_" . $i ); ?>' id='<?php echo esc_attr( $cols_order[2] . $manag_order_no . "_" . $i ); ?>'>
											<?php
												for( $i_no = 0; $i_no < $line_cnt; $i_no++ )
												{
													$selected = null;
													if( isset( $_POST[$cols_order[2] . $manag_order_no . "_" . $i]) == true )
													{
														if( $i == $i_no )
															$selected = 'selected="selected"';
													
													} else {
														
														if( $i_no == $line_cnt - 1 ){
															$selected ='selected="selected"';
														}
													}
												?>
												<option value='<?php echo esc_attr( $i_no ); ?>' <?php echo $selected; ?> ><?php echo esc_attr( $i_no + 1 ); ?></option>
											<?php
												}
											?>
										</select></label>
					
										<label for='<?php echo esc_attr( $cols_order[1] . $manag_order_no . "_" . $i ); ?>'>表　示</label>
										<select name='<?php echo esc_attr( $cols_order[1] . $manag_order_no . "_" . $i ); ?>' id='<?php echo esc_attr( $cols_order[1] . $manag_order_no . "_" . $i ); ?>'>
											<?php
/*
												if( isset( $_POST[$cols_order[1] . $manag_order_no . "_" . $i] ) == true )
												{
													$selected_0 = $selected_1 = null;
					
													if( isset( $_POST[$cols_order[1] . $manag_order_no . "_" . $i] ) == true && $_POST[$cols_order[1] .$manag_order_no . "_" . $i] == 0 )
														$selected_0 = 'selected="selected"';
													else if( isset( $_POST[$cols_order[1] . $manag_order_no . "_" . $i] ) == true && $_POST[$cols_order[1] .$manag_order_no . "_" . $i] == 1 )
														$selected_1 = 'selected="selected"';
												}
*/
												
												$selected_0 = $selected_1 = '';
						
												$disp_order = db_op_get_value( $cols_order[1] .$manag_order_no . "_" . $i );
												if( $disp_order == 1 )
													$selected_1 = ' selected="selected"';
												else
													$selected_0 = ' selected="selected"';
											?>
											<option value='0' <?php echo $selected_0; ?> >表示する</option>
											<option value='1' <?php echo $selected_1; ?> >表示しない</option>
										</select>
										<label for='<?php echo esc_attr( $cols_order[3] . $manag_order_no . "_" . $i ); ?>'>消去<input type='checkbox' name='<?php echo esc_attr( $cols_order[3] . $manag_order_no . "_" . $i ); ?>' id='<?php echo esc_attr( $cols_order[3] .$manag_order_no . "_" . $i ); ?>' value='del'></label>
								</div>
						</td>
					</tr>
					<?php } if( $i == 0 ){ ?>
				<tr>
					<td colspan="5" style='text-align:center;'>ソート条件がありません。</td>
				</tr>
		<?php } ?>
			</tbody>
			<tfoot>
				<tr>
					<th>ターゲット</th>
					<th>ラベル</th>
					<th>昇順テキスト/画像</th>
					<th>降順テキスト/画像</th>
					<th></th>
				</tr>
			</tfoot>
		</table>
	
	
		<input type='submit' value='設定を保存' class='button-primary action' />
	
		<?php if( isset( $_POST['c_order_number']) == true ){ ?>
		
			<input type='hidden' name='c_order_number' value='<?php echo esc_attr( $_POST['c_order_number'] ); ?>' />
		
		<?php } ?>
	
		<input type='hidden' name='ac' value='update' />
	
		<?php
		$disp_no = '';
		if( $manag_order_no > 0 )
			$disp_no = $manag_order_no;
		?>
		
<!--
		<h3>プレビュー</h3>
		<div id='nostyle' style='border:1px solid #000000;padding:10px;' >
		<?php //feas_sort_menu( esc_html( $disp_no ) ); ?>
		</div>
-->
		<div id="feas-pv">
			<h3>プレビュー</h3>
			<p class="text-small"><label>
			<?php
				$pvcss_checked = '';
				if( db_op_get_value( $pv_css . $manag_no ) == 'yes' )
					$pvcss_checked = 'checked="checked"';
			?>
				<input type="checkbox" name="<?php echo esc_attr( $pv_css . $manag_order_no ); ?>" id="<?php echo esc_attr( $pv_css . $manag_order_no ); ?>" value="yes" <?php echo esc_attr( $pvcss_checked ); ?> /> 「フォーム外観」のCSSを適用する
			</label></p>
			<div id="nostyle" style="border:1px solid #000000;padding:10px;">
				<iframe class="autoHeight" src="<?php bloginfo( 'url' );?>?feas_pv=1&feas_mng_no=<?php echo esc_attr( $manag_order_no ); ?>&feas_pv_type=sort" width="100%" marginheight="0" scrolling="auto">プレビュー</iframe>
			</div>
		</div>
	
	</form>

</div>

<div id="feas-code-sample">
<h3>サンプルコード</h3>
	<p>
	次のコードをコピーしてテンプレート（例：テンプレートフォルダ/index.php 等）内に検索フォームの上部などにペーストしてください。<br />
	語句の表現、HTML等は自由に変えてください。<br />
	検索フォームの整形はお使いのテンプレートフォルダ内のstyle.cssに追加設定を行って下さい。</p>
<textarea onfocus="SelectText( this );" style="width: 700px; height: 20em;">&lt;?php if(function_exists('feas_sort_menu')){ ?&gt;		
&lt;h4&gt;検索結果を並べ替える&lt;/h4&gt;
&lt;div id=&quot;feas-sort-menu&quot;&gt;
&lt;?php feas_sort_menu(<?php echo esc_textarea( $disp_no ); ?>); ?&gt;
&lt;/div&gt;
&lt;?php } ?&gt</textarea>



	<p>
	ショートコードをお使いの場合は、下記コードをコピーして任意の投稿／ページ内にペーストしてください。</p>
	<textarea onfocus="SelectText( this );" style="width: 700px; height: 2em;">[feas-sort-menu<?php if( $manag_order_no > 0 ){ echo esc_textarea( " id=" . $disp_no ); } ?>]</textarea>
	
	<script type="text/javascript">
	  function SelectText( element )
	  {
	      window.setTimeout(
	          function() { element.select(); },
	          0
	          );
	  }
	</script>
	
</div>

</div>
</div>

<script type="text/javascript">

function order_check()
{
	for( i = 0; i < <?php echo esc_html( $line_cnt ); ?>; i++ )
	{
		var ele_order_name = '<?php echo esc_html( $cols_order[2] . $manag_order_no ); ?>_' + i;
		var obj = document.getElementById( ele_order_name );

		for( i_s = 0; i_s < i; i_s++ )
		{
			var check_order_name = '<?php echo esc_html( $cols_order[2] . $manag_order_no ); ?>_' + i_s;
			var check_obj = document.getElementById( check_order_name );

			if( obj.value == check_obj.value )
			{
				alert( "並び順が同じものがあります。" );
				return false;
			}
		}
	}
	return true;
}

// 初期の並び順、カスタムフィールドを選択時にキー指定ドロップダウンを表示
function cfkey_check( line_cnt )
{
	var cfkey = jQuery( '#<?php echo esc_html( $cols_order[0] . $manag_order_no ); ?>_' + line_cnt ).val();
	if( 'post_meta' == cfkey )
	{
		jQuery( '#<?php echo $cols_order[9] . $manag_order_no; ?>_' + line_cnt ).attr( 'style', 'display:inline-block' );
		jQuery( '#<?php echo $cols_order[10] . $manag_order_no; ?>_' + line_cnt ).attr( 'style', 'display:inline-block' );
		
		jQuery( '#<?php echo $cols_order[8] . $manag_order_no; ?>_' + line_cnt ).removeAttr( 'disabled' );
	
	} else if( 'rand' == cfkey ) {
		
		jQuery( '#<?php echo $cols_order[8] . $manag_order_no; ?>_'+ line_cnt ).attr( 'disabled', 'disabled');
		
		jQuery( '#<?php echo $cols_order[9] . $manag_order_no; ?>_'+ line_cnt ).removeAttr( 'style' );
		jQuery( '#<?php echo $cols_order[10] . $manag_order_no; ?>_'+ line_cnt ).removeAttr( 'style' );
	
	} else {
		
		jQuery( '#<?php echo $cols_order[9] . $manag_order_no; ?>_' + line_cnt ).removeAttr( 'style' );
		jQuery( '#<?php echo $cols_order[10] . $manag_order_no; ?>_' + line_cnt ).removeAttr( 'style' );
		jQuery( '#<?php echo $cols_order[8] . $manag_order_no; ?>_' + line_cnt ).removeAttr( 'disabled' );
	}
}

window.onload = function()
{
	for( var i = 0; i < <?php echo esc_html( $line_cnt ); ?>; i++ )
	{
		cfkey_check(i);
	}
}

</script>