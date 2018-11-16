<div class="wrap">
<div id="feas-admin">
<div id="feas-head" class="clearfix">
<h2>FE Advanced Search &raquo; フォーム外観</h2>

	<?php if( ( isset( $_POST['style_update'] ) && $_POST['style_update'] == 'update' )) { ?>
		<div id="message"  class="updated">正常に設定が保存されました．</div>
	<?php } ?>
	
	<div id="feas-support">
		<a href="<?php bloginfo('wpurl'); ?>/wp-content/plugins/fe-advanced-search/manual/index.html" target="_blank">使用説明書を見る</a>｜<a href="http://forums.firstelement.jp/forum.php?id=6" target="_blank">フォーラム（掲示板）</a>｜<a href="mailto:info@firstelement.jp">メールで問い合わせる</a>
	</div>
</div>


<div id="feas-selform">
	<form action='' method='POST'>
		<select name='c_form_number'>
		<?php for( $i =0 ; $i <= $get_form_max ; $i++ ){

				$fname = null;
				$selected =null;
		
				if( $_POST['c_form_number'] == $i ){
					$selected = "selected=\"selected\" ";
				}
				$fname = db_op_get_value( $feadvns_search_form_name . $i );
				
				if( $fname )
					$fname = $fname;
				else
					$fname = "（フォームID = " . $i . "）";
				
				print( "<option value='" . $i . "' " . $selected . ">" . $fname . "</option>" );
				
		} ?>
				<!-- <option value='new'>　新規作成　</option> -->
		<?php if( $_POST['c_form_number'] == $get_form_max && ( $i -1 ) > 0 ){ ?>
				<!-- <option value='del'>　削除　</option> -->
		<?php } ?>
		</select>
	
		<input type='submit' value='実行' class='button-secondary action' />
	</form>
</div><!--feas-selform-->

<form action='' method='POST'>
	<label><input type='checkbox' name='use_style' value='use'<?php echo $use_checked; ?>> 下記スタイルシートを使用する</label>
	<br />

	<p>スタイルシート（CSS）</p>
	<textarea name='style_body' style='width:650px;height:240px;'><?php echo stripslashes( $style_body ); ?></textarea>
<!-- 	<textarea name='style_body' style='width:650px;height:240px;'><?php echo esc_textarea( $style_body ); ?></textarea> -->
	<br />
	<input type='submit' value='保　存' class='button-primary action'>
	<input type='hidden' name='style_update' value='update'>
	<input type='hidden' name='style_id' value='<?php echo $form_id;?>'>
</form>

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

</div>
</div>