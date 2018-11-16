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
  /*¥*//*/
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
	background-color:  #FFFFFF;
	border-right: 1px solid #E1E1E1;
	border-bottom: 1px solid #E1E1E1;
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
	margin: 10px 0;
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
	
/*
.feas-members3 label,
.feas-members3 input,
.feas-members3 select {
	display: inline;
	vertical-align: middle;
	line-height: 20px;
	}
*/
	
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
.left { float: left; }
.right { float: right; }

#feadvns_sort_target_cfkey,
#feadvns_sort_target_cfkey_as {
	display: none;
}
#feas-head h2:before {
    content: '\f179';
    display: inline-block;
    -webkit-font-smoothing: antialiased;
    font: normal 29px/1 'dashicons';
    vertical-align: middle;
    margin-right: 0.3em;
}

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


<script type="text/javascript">
window.onload = function(){
	for(var ii =0; ii < <?php echo $line_cnt ?>; ii++){
	
		var ele_name ="<?php echo $cols[4] .$manag_no ."_"; ?>" +ii;
		var obj =document.getElementById(ele_name);

		//change_element(obj, i);

		var ele_more ="<?php echo $cols[2] .$manag_no ."_"; ?>" +ii;
		var obj_more =document.getElementById(ele_more);

		change_element_more(obj_more, ii);
	}
	cfkey_check();
}
</script>
<script  type="text/javascript">
function getElementsByClassNameIE(targetClass){
    var foundElements = new Array();
    if (document.all){
        var allElements = document.all;
    }
    else {
       var allElements = document.getElementsByTagName("*");
    }
    for (i=0,j=0;i<allElements.length;i++) {
        if (allElements[i].className == targetClass) {
            foundElements[j] = allElements[i];
            j++;
        }
    }  
    return foundElements;
}

var userAgent = window.navigator.userAgent.toLowerCase();
var appVersion = window.navigator.appVersion.toLowerCase();

if (userAgent.indexOf("msie") != -1) {
  if (appVersion.indexOf("msie 6.") != -1) {
    this_is_ie = true;
  } else if (appVersion.indexOf("msie 7.") != -1) {
    this_is_ie = true;
  } else if (appVersion.indexOf("msie 8.") != -1) {
    this_is_ie = true;
  } else {
  	this_is_ie = false;
  }
}else{
	this_is_ie = false;
}
</script>

<script type="text/javascript">
function change_element(th, num){
	var condition = 'feadvns_par_cat_<?php echo $manag_no;?>_'+num;
	var condition =document.getElementById(condition).value;

	if(condition == 'archive'){
		var condition_flag = 'archive';
	}else if(condition.match( new RegExp("^" + "meta_") )){
		var condition_flag = 'meta';
	}else if(condition == 'sel_tag'){
	
	}

//console.log(this_is_ie);
	var ele_cat_name ='<?php echo $cols[2] .$manag_no ."_" ?>' +num; //条件
	var cat_obj =document.getElementById(ele_cat_name);

	var ele_order_name ='<?php echo $cols[5] .$manag_no ."_" ?>' +num+'_cat'; //ならび順
	var order_obj =document.getElementById(ele_order_name);
	var ele_order_name_arc ='<?php echo $cols[5] .$manag_no ."_" ?>' +num+'_arc'; //ならび順
	var order_obj_arc =document.getElementById(ele_order_name_arc);

	var ele_kind_name ='<?php echo $cols[6] .$manag_no ."_" ?>' +num; //検索方法
	var kind_obj =document.getElementById(ele_kind_name);
	
	var cat_more_setting ='cat_more_setting_' +num; //フリーワード以外の時に出てくるテーブル、除外IDとか有る辺り。
	var cat_more_obj =document.getElementById(cat_more_setting);
	
	var kwds_more_setting ='<?php echo $cols[13] .$manag_no ."_" ?>' +num; //フリーワードのときに出てくるテーブル
	var kwds_obj =document.getElementById(kwds_more_setting);
	
	var cf_more_setting ='cf_more_setting_' +num;
	var cf_more_obj =document.getElementById(cf_more_setting); //範囲検索
	var limit_dynamic_node = 'limit_dynamic_node_<?php print($manag_no);?>_' + num; //範囲検索のアーカイブの時不要の物
	
	if(this_is_ie){
		var limit_node = getElementsByClassNameIE(limit_dynamic_node);
	
	} else {
		var limit_node = document.getElementsByClassName( limit_dynamic_node );
	}

	var ajax_class_setting = 'ajax_check_<?php print( $manag_no ); ?>_' + num; //Ajaxフィルタリング
	if( this_is_ie )
	{
		var ajax_class = getElementsByClassNameIE( ajax_class_setting );
	
	} else {
		
		var ajax_class = document.getElementsByClassName( ajax_class_setting );
	}
	switch(Number(th.value)){
		case 1: //ドロップダウン
			
			ajax_class[0].style.display ="";
			ajax_class[1].style.display ="";
			
			if(condition_flag == 'archive')
			{
				limit_node[0].style.visibility ="hidden";
				limit_node[1].style.visibility ="hidden";
				limit_node[2].style.visibility ="hidden";
				cf_more_obj.style.visibility ="visible";
				cf_more_obj.style.display ="block";
				order_obj.style.display ="none";
				order_obj_arc.style.display ="inline";
				order_obj.disabled = true;
				order_obj_arc.disabled = false;
				cat_more_obj.style.display ="none";
			
			} else if( condition_flag == 'meta' ){
				
				limit_node[0].style.visibility ="visible";
				limit_node[1].style.visibility ="visible";
				limit_node[2].style.visibility ="visible";
				
				order_obj.style.display ="none";
				order_obj_arc.style.display ="none";
				order_obj.disabled = false;
				order_obj_arc.disabled = true;
				
				cf_more_obj.style.visibility ="visible";
				cf_more_obj.style.display ="block";
				cat_more_obj.style.display ="none";
			
			} else {
				
				limit_node[0].style.visibility ="visible";
				limit_node[1].style.visibility ="visible";
				limit_node[2].style.visibility ="visible";
				order_obj.style.display ="inline";
				order_obj.disabled = false;
				order_obj_arc.disabled = true;
				cf_more_obj.style.visibility ="hidden";
				cf_more_obj.style.display ="none";
				cat_more_obj.style.display ="inline";
			}
			
			kind_obj.style.visibility ='hidden';
			cat_obj.style.display ="inline";
			
			kwds_obj.style.display ="none";
			break;

		case 2: //リストボックス
		case 3: //チェックボックス
			kind_obj.style.visibility ='visible';
			cat_obj.style.display ="inline";
			cat_more_obj.style.display ="inline";
			
			if(condition_flag == 'archive'){
				order_obj.style.display ="none";
				order_obj_arc.style.display ="inline";
				order_obj.disabled = true;
				order_obj_arc.disabled = false;
				cat_more_obj.style.display ="none";
			
			} else if( condition_flag == 'meta' ){
				
			} else {
				
				order_obj.style.display ="inline";
				order_obj_arc.style.display ="none";
				order_obj.disabled = false;
				order_obj_arc.disabled = true;
			}		
			kwds_obj.style.display ="none";
			cf_more_obj.style.visibility ="hidden";
			cf_more_obj.style.display ="none";
			ajax_class[0].style.display ="none";
			ajax_class[1].style.display ="none";
			break;

		case 4: //ラジオボタン
			
			kind_obj.style.visibility ='hidden';
			cat_obj.style.display ="inline";
			cat_more_obj.style.display ="inline";
			
			if(condition_flag == 'archive'){
				limit_node[0].style.visibility ="hidden";
				limit_node[1].style.visibility ="hidden";
				limit_node[2].style.visibility ="hidden";
				cf_more_obj.style.visibility ="visible";
				cf_more_obj.style.display ="block";
				order_obj.style.display ="none";
				order_obj_arc.style.display ="inline";
				cat_more_obj.style.display ="none";
			
			}else if(condition_flag){
				
				limit_node[0].style.visibility ="visible";
				limit_node[1].style.visibility ="visible";
				limit_node[2].style.visibility ="visible";
				order_obj.style.display ="none";
				order_obj_arc.style.display ="none";
				cf_more_obj.style.visibility ="visible";
				cf_more_obj.style.display ="block";
				cat_more_obj.style.display ="none";
			
			} else {
				
				limit_node[0].style.visibility ="visible";
				limit_node[1].style.visibility ="visible";
				limit_node[2].style.visibility ="visible";
				order_obj.style.display ="inline";
				order_obj_arc.style.display ="none";
				cf_more_obj.style.visibility ="hidden";
				cf_more_obj.style.display ="none";
				cat_more_obj.style.display ="inline";
			}	
			kwds_obj.style.display ="none";
			ajax_class[0].style.display ="none";
			ajax_class[1].style.display ="none";
			break;
			
		case 5: //フリーワード
			kwds_obj.style.visibility ="visible";
			kwds_obj.style.display ="block";		
			kind_obj.style.visibility ='hidden';
			cf_more_obj.style.visibility ="hidden";
			cf_more_obj.style.display ="none";
			cat_more_obj.style.display ="inline";
			cat_obj.style.display ="none";
			cat_more_obj.style.display="none";
			
			
				order_obj.style.display ="none";
				order_obj_arc.style.display ="none";
			
			
			ajax_class[0].style.display ="none";
			ajax_class[1].style.display ="none";
			break;
	}
}

function change_element_more( th, num ){ // th は関数が設置されたselect

	var ele_cat_more       = 'cat_more_setting_' + num;
	var more_obj           = document.getElementById( ele_cat_more );
	
	var ele_order_name     = '<?php echo $cols[5] . $manag_no . '_'; ?>' + num + '_cat'; // 並び順
	var order_obj          = document.getElementById( ele_order_name );
	
	var ele_order_name_arc = '<?php echo $cols[5] . $manag_no . '_'; ?>' + num + '_arc'; // 並び順：アーカイブ
	var order_obj_arc      = document.getElementById( ele_order_name_arc );
	
	var cf_more_setting    = 'cf_more_setting_' + num;
	var cf_more_obj        = document.getElementById( cf_more_setting ); // 範囲検索
	
	var limit_dynamic_node = 'limit_dynamic_node_<?php echo $manag_no; ?>_' + num; // 範囲検索のアーカイブの時不要の物
	
	if( this_is_ie )
	{
		var limit_node = getElementsByClassNameIE( limit_dynamic_node );
	
	} else {
		
		var limit_node = document.getElementsByClassName( limit_dynamic_node );
	}
	
	// カスタムフィールド or タグ
	if( th.value.match( new RegExp( '^' + 'meta_' ) ) || th.value == 'sel_tag' )
	{
		// カテゴリ,タクソノミ関連のオプション項目を隠す
		more_obj.style.visibility      = 'hidden';
		more_obj.style.display         = 'none';
		
		// 「並び順」ドロップダウン
		order_obj.style.display        = 'inline';
		order_obj.disabled             = false;
		
		// 「並び順」ドロップダウン - アーカイブ
		order_obj_arc.style.display    = 'none';
		order_obj_arc.disabled         = true;
		
		// 「範囲検索」の各パーツ
		limit_node[0].style.visibility = 'visible'; // 単位
		limit_node[1].style.visibility = 'visible'; // 自由入力
		limit_node[2].style.visibility = 'visible'; // 3桁半角カンマ区切り
		
		// 「範囲検索」
		cf_more_obj.style.visibility   = 'visible';
		cf_more_obj.style.display      = 'block';
	}
	
	// アーカイブ
	else if( th.value == 'archive' )
	{
		order_obj.style.display        = 'none';
		order_obj_arc.style.display    = 'inline';
		order_obj.disabled             = true;
		order_obj_arc.disabled         = false;
		limit_node[0].style.visibility = 'visible';
		limit_node[1].style.visibility = 'visible';
		limit_node[2].style.visibility = 'visible';
		cf_more_obj.style.visibility   = 'visible';
		cf_more_obj.style.display      = 'block';
	}
	
	// カテゴリ or タクソノミ
	else
	{
		more_obj.style.visibility      = 'visible';
		more_obj.style.display         = 'block';
		order_obj.style.display        = 'inline';
		order_obj_arc.style.display    = 'none';
		order_obj.disabled             = false;
		order_obj_arc.disabled         = true;
		
		limit_node[0].style.visibility = 'hidden';
		limit_node[1].style.visibility = 'hidden';
		limit_node[2].style.visibility = 'hidden';
		
		cf_more_obj.style.visibility   = 'hidden';
		cf_more_obj.style.display      = 'none';
	}

	change_element( document.getElementById( 'feadvns_kind_<?php echo $manag_no; ?>_' + num ), num );
}

// 「並び順」のダブりのチェック
function order_check()
{
	for( i = 0; i < <?php echo $line_cnt; ?>; i++ )
	{
		var ele_order_name = '<?php echo $cols[0] . $manag_no . "_"; ?>' + i;
		var obj = document.getElementById( ele_order_name );

		for( i_s = 0; i_s < i; i_s++ )
		{
			var check_order_name = '<?php echo $cols[0] . $manag_no . "_"; ?>' + i_s;
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

// Ajaxフィルタリングがチェックされた時、0件は表示しないにチェック
function ajax_check( th, manag_no, num )
{
	if( th.checked )
		document.getElementById( 'feadvns_nocnt_emptycat_' + manag_no + '_' + num ).checked = true;
}
// 0件は表示しないのチェックが外されたら、Ajaxフィルタリングのチェックを外す
function ajax_an_check( th, manag_no, num )
{
	if( !th.checked )
		document.getElementById( 'feadvns_kwds_ajax_' + manag_no + '_' + num ).checked = false;
}
// 初期の並び順、カスタムフィールドを選択時にキー指定ドロップダウンを表示
function cfkey_check()
{
	var cfkey = jQuery('#feadvns_sort_target').val();
	if( 'post_meta' == cfkey )
	{
		jQuery('#feadvns_sort_target_cfkey').attr( 'style', 'display:inline-block');
		jQuery('#feadvns_sort_target_cfkey_as').attr( 'style', 'display:inline-block');
		
		jQuery('#feadvns_sort_order').removeAttr( 'style' );
	
	} else if( 'rand' == cfkey ) {
		
		jQuery('#feadvns_sort_order').attr( 'style', 'display:none');
		
		jQuery('#feadvns_sort_target_cfkey').removeAttr( 'style' );
		jQuery('#feadvns_sort_target_cfkey_as').removeAttr( 'style' );
	
	} else {
		
		jQuery('#feadvns_sort_target_cfkey').removeAttr( 'style' );
		jQuery('#feadvns_sort_target_cfkey_as').removeAttr( 'style' );
		jQuery('#feadvns_sort_order').removeAttr( 'style' );
	}
}


</script>

<?php
/******************
表示部
*******************/
?>
<div class="wrap">
<div id="feas-admin">
	<div id="feas-head" class="clearfix">
		<?php //if( function_exists('screen_icon') ) screen_icon( 'options-general' ); ?>
		<h2>FE Advanced Search</h2>

		
		<?php if( ( isset( $_POST['ac'] ) && $_POST['ac'] == 'update' ) ||  ( isset( $_POST['gs'] ) && $_POST['gs'] == 'update' ) ) { ?>
			<div id="message"  class="updated">正常に設定が保存されました．</div>
		<?php } else if( isset( $_POST[ 'c_form_number' ] ) && $_POST[ 'c_form_number' ] == 'new' ) { ?>
			<div id="message"  class="updated">新しい検索フォームが作成されました．</div>
		<?php } else if( isset( $_POST[ 'c_form_number' ] ) && $_POST[ 'c_form_number' ] == 'del' ) { ?>
			<div id="message"  class="updated">検索フォームが削除されました．</div>
		<?php } else if( isset( $_POST[ 'line_action' ] ) && $_POST[ 'line_action' ] == 'add_line' ) { ?>
			<div id="message"  class="updated">検索条件が追加されました．</div>
		<?php } ?>
		
		<div id="feas-support">
			<a href="<?php bloginfo('wpurl'); ?>/wp-content/plugins/fe-advanced-search/manual/index.html" target="_blank">使用説明書を見る</a>｜<a href="http://forums.firstelement.jp/forum.php?id=6" target="_blank">フォーラム（掲示板） </a>｜<a href="mailto:info@firstelement.jp">メールで問い合わせる</a>
		</div>
	</div>

	<?php
	/******************
	フォームの選択プルダウン
	*******************/
	?>
	<div id="feas-selform" class="right">
		<form action="" method="post">
			<select name="c_form_number">
			<?php for( $i = 0; $i <= $get_form_max; $i++ )
			{
				$fname = $selected = '';
		
				if( $_POST['c_form_number'] == $i )
					$selected = ' selected="selected"';
				
				$fname = db_op_get_value( $feadvns_search_form_name . $i );
				
				if( !$fname )
					$fname = '（フォームID = ' . $i . '）';	
				?>
				
				<option value="<?php echo esc_attr( $i ); ?>"<?php echo $selected; ?>><?php echo esc_html( $fname ); ?></option>
					
				<?php
			}
			?>
				<option value="new">　新規作成　</option>
			<?php if( $_POST['c_form_number'] == $get_form_max && ( $i - 1 ) > 0 ){ ?>
				<option value="del">　削除　</option>
			<?php } ?>
			</select>
			<input type="submit" value="実行" class="button-secondary action" />
		</form>
	</div><!--feas-selform-->

	<?php
	/******************************
	検索フォーム全体の設定
	******************************/
	?>
	
	<h3 id="feas-sectitle" class="left">検索フォーム（No.<?php echo esc_html( $manag_no ); ?>）全体の設定</h3>
	
	<form action="" method="post" name="fm">    
		<div id="feas-sec" class="clearfix">
			<table class="widefat">
				<tr>
					<th>名称</th>
					<td><input type="text" id="<?php echo esc_attr( $feadvns_search_form_name . $manag_no ); ?>" name="<?php echo esc_attr( $feadvns_search_form_name . $manag_no ); ?>" value="<?php echo esc_attr( db_op_get_value( $feadvns_search_form_name . $manag_no ) ); ?>" style="width:80%;" /></td>
					<th>フォームID</th>
					<td><?php echo esc_html( $manag_no ); ?></td>
				</tr>
				<tr>
					<th>検索対象の投稿タイプ</th>
					<td colspan="3">
					<?php
					// PostTypeのチェックボックスを出力。functions.php参照。
					feas_posttype_lists( $manag_no );
	
					// StickyPosts
					$sp_checked = '';
					$target_sp = db_op_get_value( $feadvns_include_sticky . $manag_no );
					if( $target_sp == 'yes' )
						$sp_checked = ' checked="checked"';
					?>
					<label><input type="checkbox" name="<?php echo esc_attr( $feadvns_include_sticky . $manag_no ); ?>" value="yes"<?php echo $sp_checked; ?> /> 固定記事（Sticky Posts）</label>
					</td>
				</tr>
				<tr>
					<th>固定タクソノミ/ターム</th>
					<td>
						<?php
						$args = array( 'public' => true );
						$allTaxs = get_taxonomies( $args, 'objects' );			
						if( $allTaxs ):
						?>
						
						<select name="<?php echo esc_attr( $feadvns_default_cat . $manag_no ); ?>">
							<option value=""> なし </option>
							<?php foreach( $allTaxs as $tax ): ?>
								<?php if ( 'post_format' != $tax->name ) : ?><optgroup label="<?php echo esc_attr( $tax->label ); ?>"><?php endif; ?>
								<?php
								$args = array(
									'manag_no'  => $manag_no,
									'counter'   => -1,
									'parent'    => 0,
									'tax_name'  => $tax->name,
									'tax_label' => $tax->label,
									'depth'     => 0,
									'echo'      => true,
								);
								feas_get_hierarchical_term_list( $args );
								?>
								<?php if ( 'post_format' != $tax->name ) : ?></optgroup><?php endif; ?>
							<?php endforeach; ?>	
						</select>
							
						<?php endif; ?>
					</td>
					<th>
						検索条件に件数を表示
					</th>
					<td>
						<?php
						$sc_checked = '';
						if( db_op_get_value( $feadvns_show_count . $manag_no ) == 'yes' )
							$sc_checked = ' checked="checked"';
						?>
						<label><input type="checkbox" name="<?php echo esc_attr( $feadvns_show_count . $manag_no ); ?>" value="yes" <?php echo $sc_checked ; ?>> 表示する</label>
					</td>
				</tr>
				<tr>
					<th>検索結果の並び順</th>
					<td>
						<?php
						/************
						ターゲット
						************/
						?>
						<select id="feadvns_sort_target" name="<?php echo esc_attr( $feadvns_sort_target . $manag_no ); ?>" onChange="cfkey_check()">
							<?php
							/*
							参考: version 1.5.2 までの仕様
							$op_keys =array(0 =>"投稿日 ｜昇順", 1 =>"投稿日 ｜ 降順", 2 =>"タイトル ｜ 昇順", 3 =>"タイトル ｜ 降順", 4 =>"スラッグ ｜ 昇順", 5 =>"スラッグ ｜ 降順");
							*/
							$op_keys = array(
								'post_date'  => '投稿日時',
								'post_title' => 'タイトル',
								'post_name'  => 'スラッグ',
								'post_meta'  => 'カスタムフィールド',
								'rand'       => 'ランダム'
							);
							
							$i = 0;
							foreach( $op_keys as $k => $v ):
								$selected = '';
								$cTarget = db_op_get_value( $feadvns_sort_target . $manag_no );
								if( $cTarget == $k || $cTarget == $i * 2 || $cTarget == $i * 2 + 1 ) // 後方2つは、数値で保存していたversion1.5.2までの下位互換のため
									$selected = ' selected="selected"';
								?>
								<option value="<?php echo esc_attr( $k ); ?>"<?php echo $selected; ?>><?php echo esc_html( $v ); ?></option>
								<?php
								$i++;
							endforeach;
							?>
						</select>
							
						<?php
						/************
						カスタムフィールドのキー
						************/
						?>
						<select id="feadvns_sort_target_cfkey" name="<?php echo esc_attr( $feadvns_sort_target_cfkey . $manag_no ); ?>">
							<?php
							for( $i_meta = 0, $cnt_meta = count( $get_metas ); $i_meta < $cnt_meta; $i_meta++ )
							{
								$selected = '';
								$cTargetKey = db_op_get_value( $feadvns_sort_target_cfkey . $manag_no );
								if( $cTargetKey == $get_metas[$i_meta]->meta_key )
									$selected = ' selected="selected"';
								?>
								<option value="<?php echo esc_attr( $get_metas[$i_meta]->meta_key ); ?>"<?php echo $selected; ?>><?php echo esc_html( $get_metas[$i_meta]->meta_key ); ?></option>
								<?php
							}
							?>
						</select>
						
						<?php
						/************
						数値か文字か
						************/
						$selected_1 = $selected_2 = '';
						
						$cOrder_as = db_op_get_value( $feadvns_sort_target_cfkey_as . $manag_no );
						if( $cOrder_as == 'str' )
							$selected_2 = ' selected="selected"';
						else
							$selected_1 = ' selected="selected"';
						?>
						<select id="feadvns_sort_target_cfkey_as" name="<?php echo esc_attr( $feadvns_sort_target_cfkey_as . $manag_no ); ?>">
							<option value="int"<?php echo $selected_1; ?>>数値</option>
							<option value="str"<?php echo $selected_2; ?>>文字列</option>
						</select>
						
						<?php
						/************
						昇順・降順
						************/
						$selected_1 = $selected_2 = '';
						
						$cOrder = db_op_get_value( $feadvns_sort_order . $manag_no );
						if( $cOrder == 'desc' || $cTarget % 2 != 0 ) // 後方2つは、数値で保存していたversion1.5.2までの下位互換のため。奇数は降順。
							$selected_2 = ' selected="selected"';
						else
							$selected_1 = ' selected="selected"';
						?>
						<select id="feadvns_sort_order" name="<?php echo esc_attr( $feadvns_sort_order . $manag_no ); ?>">
							<option value="asc"<?php echo $selected_1; ?>>昇順</option>
							<option value="desc"<?php echo $selected_2; ?>>降順</option>
						</select>
							
					</td>
					<th>検索条件が指定されずに検索された場合</th>
					<td>
						<?php
						$ereq_selected_0 = $ereq_selected_1 = '';
						
						$ereq = db_op_get_value( $feadvns_empty_request . $manag_no );
						if( $ereq == 0 )
							$ereq_selected_0 = ' selected="selected"';
						else
							$ereq_selected_1 = ' selected="selected"';
						?>
						<select name="<?php echo esc_attr( $feadvns_empty_request . $manag_no ); ?>">
							<option value="0"<?php echo $ereq_selected_0; ?>>0件を返す</option>
							<option value="1"<?php echo $ereq_selected_1; ?>>固定タクソノミ/タームの記事一覧を表示</option>
						</select>
					</td>
				</tr>
			</table>
		</div><!--feas-sec-->
	
		<?php if( isset($_POST['c_form_number']) == true ){ ?>
		<input type="hidden" name="c_form_number" value="<?php echo esc_attr( $_POST['c_form_number'] ); ?>" />
		<?php } ?>
		<input type="submit" value="設定を保存" class ="button-primary action" />
		<input type="hidden" name="gs" value="update" />
	</form>

	<?php
	/******************************
		フォームの内容
	******************************/
	?>
	<h3 id="feas-sectitle2" class="left">フォームの内容</h3>
	
	<div class="right">
		<form action="" method="post" style="float:right;">
			<input type="submit" value="項目を追加" class ="button-secondary action" />
			<input type="hidden" name="line_action" value="add_line" />
			<input type="hidden" name="c_form_number" value="<?php echo esc_attr( $manag_no ); ?>" />
		</form>
	</div>
	
	<form action="" method="post" name="fm" onSubmit="return order_check();">    
		<table class="widefat" style="clear:both;">
			<thead>
				<tr>
					<th>ラベル</th>
					<th>条件</th>
					<th>形式</th>
					<th>並び順</th>
					<th>検索方法</th>
				</tr>
			</thead>
			<tbody>
			<?php for( $i = 0; $i < $line_cnt; $i++ ){
	
				// 「表示しない」設定の行は背景をグレイに
				$addclass_gray = null;
				if( db_op_get_value( $cols[1] .$manag_no ."_" . $i )  == 1 )
					$addclass_gray = "grayout";
				?>
				<tr class="widefat alternate <?php echo esc_attr( $addclass_gray ); ?>">
					<td>
						<input type="text" name="<?php echo esc_attr( $cols[3] . $manag_no . "_" ); ?><?php echo esc_attr( $i ) ?>" id="<?php echo esc_attr( $cols[3] . $manag_no . "_" ); ?><?php echo esc_attr( $i ) ?>" style="width:100%;" value="<?php echo esc_attr( data_to_post( $cols[3] . $manag_no . "_" . $i ) ); ?>">
					</td>
					<td>
						<?php $j_cf = "change_element_more( this, " . $i . " )"; ?>
		
						<select name="<?php echo esc_attr( $cols[2] . $manag_no . "_" . $i ); ?>" id="<?php echo esc_attr( $cols[2] . $manag_no . "_" . $i ); ?>" style="float:left;" onChange="<?php echo esc_attr( $j_cf ); ?>" onBlur="<?php echo esc_attr( $j_cf ); ?>">
							
							<?php
							$selected = '';
							if( "archive" == $_POST[$cols[2] . $manag_no . "_" . $i] && isset( $_POST[$cols[2] . $manag_no . "_" . $i ]) )
								$selected = ' selected="selected"';
							?>
							<optgroup label="アーカイブ">
								<option value="archive"<?php echo $selected; ?>>アーカイブ</option>
							</optgroup>
		                    
							<?php
							$selected = '';
							if( isset( $_POST[$cols[2] . $manag_no . "_" . $i ]) == true && $_POST[$cols[2] . $manag_no . "_" . $i ] == "sel_tag" )
								$selected = ' selected="selected"';
							?>
		                    <optgroup label="タグ">
								<option value="sel_tag"<?php echo $selected; ?>>タグ</option>
							</optgroup>
							
							<?php foreach( $allTaxs as $tax ): ?>
								
								<?php if ( 'post_format' != $tax->name && 'post_tag' != $tax->name ) : ?><optgroup label="<?php echo esc_attr( $tax->label ); ?>"><?php endif; ?>
								<?php
								$args = array(
									'manag_no'  => $manag_no,
									'counter'   => $i,
									'parent'    => 0,
									'tax_name'  => $tax->name,
									'tax_label' => $tax->label,
									'depth'     => 0,
									'echo'      => true,
								);
								feas_get_hierarchical_term_list( $args );
								?>
								<?php if ( 'post_format' != $tax->name && 'post_tag' != $tax->name ) : ?></optgroup><?php endif; ?>
								
							<?php endforeach; ?>
							
							<optgroup label="カスタムフィールド">
								<?php
								for($i_meta = 0, $cnt_meta = count( $get_metas ); $i_meta < $cnt_meta; $i_meta++ ):
								
									$selected = '';
									if( isset( $_POST[$cols[2] . $manag_no . "_" . $i ]) == true && $_POST[$cols[2] . $manag_no . "_" . $i ] == "meta_" . $get_metas[$i_meta]->meta_key )
										$selected = ' selected="selected"';
								?>
								<option value="meta_<?php echo esc_attr( $get_metas[$i_meta]->meta_key ); ?>"<?php echo $selected; ?>><?php echo esc_attr( $get_metas[$i_meta]->meta_key ); ?></option>
							<?php endfor; ?>
							</optgroup>
						</select>
					</td>
					
					<td>
						<?php $j_f = "change_element( this, " . $i . ")"; ?>
						<select name="<?php echo esc_attr( $cols[4] . $manag_no . "_" . $i ); ?>" id="<?php echo esc_attr( $cols[4] . $manag_no . "_" . $i ); ?>" onChange='<?php echo esc_attr( $j_f ); ?>' onBlur='<?php echo esc_attr( $j_f ); ?>'>
		
							<?php
							$op_keys = array( 1 => 'ドロップダウン', 2 => 'リストボックス', 3 => 'チェックボックス', 4 => 'ラジオボタン', 5 => 'フリーワード' );
	
							for( $i_op = 1, $cnt_op = count( $op_keys ); $i_op <= $cnt_op; $i_op++ )
							{
								$selected = '';
								if( isset( $_POST[ $cols[4] . $manag_no . "_" . $i ] ) == true && $_POST[$cols[4] . $manag_no . "_" . $i ] == $i_op )
									$selected = ' selected="selected"';
								?>
								<option value="<?php echo esc_attr( $i_op ); ?>"<?php echo $selected; ?>><?php echo esc_attr( $op_keys[$i_op] ); ?></option>
								<?php
							}
							?>
						</select>
					</td>
					<td>
						<select name="<?php echo esc_attr( $cols[5] . $manag_no . "_" . $i ); ?>" id="<?php echo esc_attr( $cols[5] . $manag_no . "_" . $i ); ?>_cat">
							<?php
							$op_keys = array( 0 => "ID ｜ 昇順", 1 => "ID ｜ 降順", 2 => "タイトル ｜ 昇順", 3  => "タイトル ｜ 降順", 4 => "スラッグ ｜ 昇順", 5 => "スラッグ ｜ 降順", 6 => "カスタム", 7 => "ランダム" );
		
							for( $i_op = 0, $cnt_op = count( $op_keys ); $i_op < $cnt_op; $i_op++ )
							{
								$selected = '';
								if( $i_op == $_POST[$cols[5] . $manag_no . "_" . $i ] && isset( $_POST[$cols[5] . $manag_no . "_" . $i ]) )
									$selected = ' selected="selected"';
								?>
								<option value="<?php echo esc_attr( $i_op ); ?>"<?php echo $selected; ?>><?php echo esc_html( $op_keys[$i_op] ); ?></option>
								<?php
							}
							?>
						</select>
						
						<select name="<?php echo esc_attr( $cols[5] . $manag_no . "_" . $i ); ?>" id="<?php echo esc_attr( $cols[5] . $manag_no . "_" . $i ); ?>_arc" >
							<?php
							$op_keys = array( 0 => '年月 ｜ 昇順', 1 => '年月 ｜ 降順' );
		
							for( $i_op = 0, $cnt_op = count( $op_keys ); $i_op < $cnt_op; $i_op++ )
							{
								$selected = '';
								if( isset($_POST[$cols[5] . $manag_no . "_" . $i ]) == true && $_POST[$cols[5] . $manag_no . "_" . $i ] == $i_op )
									$selected = ' selected="selected"';
								?>
								<option value="<?php echo esc_attr( $i_op ); ?>"<?php echo $selected; ?>><?php echo esc_html( $op_keys[$i_op] ); ?></option>
								<?php
							}
							?>
						</select>
					</td>
					<td>
						<select name="<?php echo esc_attr( $cols[6] . $manag_no . "_" . $i ); ?>" id="<?php echo esc_attr( $cols[6] .$manag_no ."_" ); ?><?php echo esc_attr( $i ); ?>" style="visibility:hidden">
							<?php
							$op_keys = array( 0 => 'ＯＲ検索', 1 => 'ＡＮＤ検索' );
		
							for( $i_op = 0, $cnt_op = count( $op_keys ); $i_op < $cnt_op; $i_op++ )
							{
								$selected = '';
								if( isset( $_POST[$cols[6] . $manag_no . "_" . $i ]) == true && $_POST[$cols[6] . $manag_no . "_" . $i ] == $i_op )
									$selected = ' selected="selected"';
								?>
								<option value="<?php echo esc_attr( $i_op ); ?>"<?php echo $selected; ?>><?php echo esc_attr( $op_keys[$i_op] ); ?></option>
								<?php
							}
							?>
						</select>
					</td>
				
				</tr><tr <?php if( $addclass_gray != '' ) echo 'class="' . esc_attr( $addclass_gray ) . '"'; ?>>
				
					<td colspan="2" style="vertical-align: top;">
					<div id="cat_more_setting_<?php echo esc_attr( $i ); ?>" style="float:left; visibility:hidden" class="feas-members4">
						<table>
							<tbody>
								<tr>
									<th>除外ID </th><td><input type="text" name="<?php echo esc_attr( $cols[11] . $manag_no . "_" . $i ); ?>" value="<?php echo esc_attr( $_POST[$cols[11] . $manag_no . "_" . $i] ); ?>" style="width: 60%" /></td>
									<?php
									$emptycat_checked = '';
									if( db_op_get_value( $cols[14] . $manag_no . "_" . $i ) == 'no' )
										$emptycat_checked = ' checked="checked"';
									?>
									<th></th><td><label><input type="checkbox" name="<?php echo esc_attr( $cols[14] . $manag_no . "_" . $i ); ?>" id="<?php echo esc_attr( $cols[14] . $manag_no . "_" . $i ); ?>" value="no"<?php echo $emptycat_checked; ?> onchange="ajax_an_check( this, <?php echo esc_attr( $manag_no ); ?>,<?php echo esc_attr( $i ); ?>);" />&nbsp;登録件数が0件のカテゴリ/タームは表示しない</label></td>
								</tr><tr>
									<th>階層 </th><td><input type="text" name="<?php echo esc_attr( $cols[10] . $manag_no . "_" . $i ); ?>" value="<?php echo esc_attr( $_POST[$cols[10] . $manag_no . "_" . $i ] ); ?>" style="width: 60%" /></td>
									<?php
									$emptycat_checked = '';
									if( db_op_get_value( $cols[19] . $manag_no . "_" . $i ) == 'no' )
										$emptycat_checked = ' checked="checked"';
									?>
									<th class="ajax_check_<?php echo esc_attr( $manag_no ); ?>_<?php echo esc_attr( $i ); ?>"></th><td class="ajax_check_<?php echo esc_attr( $manag_no ); ?>_<?php echo esc_attr( $i ); ?>"><label><input type="checkbox" name="<?php echo esc_attr( $cols[19] . $manag_no . "_" . $i ); ?>" id="<?php echo esc_attr( $cols[19] . $manag_no . "_" . $i ); ?>" value="no"<?php echo $emptycat_checked; ?> onchange="ajax_check( this, <?php echo esc_attr( $manag_no ); ?>, <?php echo esc_attr( $i ); ?>);" />&nbsp;Ajaxフィルタリング</label></td>
								</tr>
							</tbody>
						</table>
					</div>
						<div id="<?php echo esc_attr( $cols[13] . $manag_no . "_" . $i ); ?>" style="visibility:hidden" class="feas-members4">
							<h4>検索対象</h4>
							
							<?php
							$kwds_target = db_op_get_value( $cols[13] . $manag_no . "_" . $i );  // DBに保存済みのデータ取得（カンマ区切り）
							$kwds_target = explode( "," , $kwds_target );	  // カンマで分解、配列に格納	
							
							$k_data[0]['name']  = 'タイトル (post_title)';
							$k_data[0]['value'] = 'post_title';
							$k_data[1]['name']  = '本文 (post_content)';
							$k_data[1]['value'] = 'post_content';
							$k_data[2]['name']  = '抜粋 (post_excerpt)';
							$k_data[2]['value'] = 'post_excerpt';
							$k_data[3]['name']  = 'カスタムフィールド (meta_value)';
							$k_data[3]['value'] = 'meta_value';
							$k_data[4]['name']  = 'コメント (comment_content)';
							$k_data[4]['value'] = 'comment_content';
							$k_data[5]['name']  = '記事が属するカテゴリ/タグ名 (terms > name)';
							$k_data[5]['value'] = 'name';
							
							$kwds_checked = array();  // 配列を初期化
							
							for( $i_t = 0 ; $i_t < 6 ; $i_t++ )
							{
								$kwds_checked[ $i_t ] = '';
								if( isset( $kwds_target[$i_t] ) )
								{
									for( $ii_t = 0 , $k_cnt = count( $kwds_target ) ; $ii_t < $k_cnt ; $ii_t++ )
									{
										if( $kwds_target[ $ii_t ] == $k_data[$i_t]['value'] )
											$kwds_checked[ $i_t ] = ' checked="checked"';
									}
								}
								?>
								<label><input type="checkbox" name="<?php echo esc_attr( $cols[13] . $manag_no . "_" . $i ); ?>[]" id="<?php echo esc_attr( $cols[13] . $manag_no ); ?>_<?php echo esc_attr( $i ); ?>_<?php echo esc_attr( $i_t ); ?>" value="<?php echo esc_attr( $k_data[$i_t]['value'] ); ?>"<?php echo $kwds_checked[$i_t]; ?>/>&nbsp;<?php echo esc_attr( $k_data[$i_t]['name'] ); ?></label>
								
								<?php if( $i_t == 3 ): ?>
								<p style="padding-left: 20px;">
									<label>キー&nbsp;<input type="text" name="<?php echo esc_attr( $cols[20] . $manag_no . "_" . $i ); ?>" value="<?php echo esc_attr( $_POST[$cols[20] . $manag_no . '_' . $i ] ); ?>" style="width: 60%" /></label>
								</p>
								<?php endif;
							}
							?>
								<div id="<?php echo esc_attr( $cols[15] . $manag_no . "_" . $i ); ?>">
								<h4>ゆらぎ検索</h4>
								<?php
								$kwds_yuragi_checked = '';
								if( db_op_get_value( $cols[15] . $manag_no . "_" . $i ) == 'no' )
									$kwds_yuragi_checked = ' checked="checked"';
								?>
								<label><input type="checkbox" name="<?php echo esc_attr( $cols[15] . $manag_no . "_" . $i ); ?>" id="<?php echo esc_attr( $cols[15] . $manag_no . "_" . $i ); ?>" value="no"<?php echo $kwds_yuragi_checked; ?> />&nbsp;半角/全角を区別しない</label>
							</div>
							
						</div>
						<div id="cf_more_setting_<?php echo esc_attr( $i ); ?>" style="visibility:hidden" class="feas-members4">
							<h4>範囲検索</h4>
							<table>
								<tr>
									<td>
										<select name="<?php echo esc_attr( $cols[16] . $manag_no . "_" . $i ); ?>" id="<?php echo esc_attr( $cols[16] . $manag_no . "_" . $i ); ?>" >				
											<?php
											$cfrange_keys = array( 0 => "しない", 1 => "未満", 2 => "以下", 3 => "以上", 4 => "超" );
							
											for( $i_cfr = 0, $cnt_cfr = count( $cfrange_keys ); $i_cfr < $cnt_cfr; $i_cfr++ )
											{
												$selected = '';
												if( isset( $_POST[$cols[16] . $manag_no . "_" . $i ] ) == true && $_POST[ $cols[16] . $manag_no . "_" . $i ] == $i_cfr )
													$selected = ' selected="selected"';
												?>
												<option value="<?php echo esc_attr( $i_cfr ); ?>"<?php echo $selected; ?>><?php echo esc_html( $cfrange_keys[$i_cfr] ); ?></option>
												<?php
											}
											?>
										</select>
									</td>
									<td class="limit_dynamic_node_<?php echo esc_attr( $manag_no . '_' . $i ); ?>"><label>単位&nbsp;<input type="text" name="<?php echo esc_attr( $cols[17] . $manag_no . "_" . $i ); ?>" id="<?php echo esc_attr( $cols[17] . $manag_no . "_" . $i ); ?>" style="width: 4em" value="<?php echo esc_attr( data_to_post( $cols[17] . $manag_no . "_" . $i ) ); ?>" ></label></td>
									<?php
									$cf_freeword_checked = '';
									if( db_op_get_value( $cols[22] . $manag_no . "_" . $i ) == 'yes' )
										$cf_freeword_checked = ' checked="checked"';
									?>
									<td clospan="2"  class="limit_dynamic_node_<?php echo esc_attr( $manag_no . '_' . $i ); ?>"><label><input id="<?php echo esc_attr( $cols[22] . $manag_no . "_" . $i ); ?>" type="checkbox" name="<?php echo esc_attr( $cols[22] . $manag_no . "_" . $i ); ?>" value="yes" <?php echo $cf_freeword_checked; ?>>自由入力</label></td>
								</tr>
								<tr>
									<?php
									$cf_kugiri_checked = '';
									if( db_op_get_value( $cols[18] . $manag_no . "_" . $i ) == 'yes' )
										$cf_kugiri_checked = ' checked="checked"';
									?>
									<td colspan="2" class="limit_dynamic_node_<?php echo esc_attr( $manag_no . '_' . $i ); ?>"><label><input type="checkbox" name="<?php echo esc_attr( $cols[18] . $manag_no . "_" . $i ); ?>" id="<?php echo esc_attr( $cols[18] . $manag_no . "_" . $i ); ?>" value="yes" <?php echo $cf_kugiri_checked; ?> />&nbsp;3桁ごとに半角カンマで区切る</label></td>
								</tr>
							</table>	
						</div>
					</td>
					<td colspan="3" style="vertical-align: top;">
					<div class="feas-members2">
					<label>前に挿入 <input type="text" name="<?php echo esc_attr( $cols[7] . $manag_no . "_" . $i ); ?>" id="<?php echo esc_attr( $cols[7] . $manag_no . "_" . $i ); ?>" style="width: 80%" value="<?php echo esc_attr( data_to_post( $cols[7] . $manag_no . "_" . $i ) ); ?>" ></label><br />
					<label>後に挿入 <input type="text" name="<?php echo esc_attr( $cols[8] . $manag_no . "_" . $i ); ?>" id="<?php echo esc_attr( $cols[8] . $manag_no . "_" . $i ); ?>" style="width: 80%" value="<?php echo esc_attr( data_to_post( $cols[8] . $manag_no . "_" . $i ) ); ?>" ></label>
					</div>
					<div class="feas-members3">
						<label for="<?php echo esc_attr( $cols[0] . $manag_no . "_" . $i ); ?>">並び順
						<select name="<?php echo esc_attr( $cols[0] . $manag_no . "_" . $i ); ?>" id="<?php echo esc_attr( $cols[0] . $manag_no . "_" . $i ); ?>">
							<?php
								for($i_no = 0; $i_no < $line_cnt; $i_no++ )
								{
									$selected = '';
									if( isset( $_POST[$cols[0] . $manag_no . "_" . $i] ) == true )
									{
										if( $i == $i_no )
											$selected = ' selected="selected"';
									}
									else
									{
										if( $i_no == $line_cnt -1 )
											$selected = ' selected="selected"';
									}
								?>
								<option value="<?php echo esc_attr( $i_no ); ?>"<?php echo $selected; ?>><?php echo esc_html( $i_no + 1 ); ?></option>
							<?php } ?>
						</select></label>
						<label for="<?php echo esc_attr( $cols[1] . $manag_no . "_" . $i ); ?>">表　示
						<select name="<?php echo esc_attr( $cols[1] . $manag_no . "_" . $i ); ?>" id="<?php echo esc_attr( $cols[1] . $manag_no . "_" . $i ); ?>">
							<?php	
							$selected_0 = $selected_1 = '';
							
							if( isset( $_POST[$cols[1] . $manag_no . "_" . $i ] ) == true )
							{
								if( isset( $_POST[$cols[1] . $manag_no . "_" . $i ] ) == true && $_POST[$cols[1] . $manag_no . "_" . $i ] == 0 )
									$selected_0 = ' selected="selected"';
								else if( isset( $_POST[$cols[1] . $manag_no . "_" . $i ]) == true && $_POST[$cols[1] . $manag_no . "_" . $i ] == 1 )
									$selected_1 = ' selected="selected"';
							?>
							<?php } ?>
		
							<option value="0"<?php echo $selected_0; ?>>表示する</option>
							<option value="1"<?php echo $selected_1; ?>>表示しない</option>
						</select></label>
					<label for="<?php echo esc_attr( $cols[9] . $manag_no . "_" . $i ); ?>">消去 <input type="checkbox" name="<?php echo esc_attr( $cols[9] . $manag_no ."_" . $i ); ?>" id="<?php echo esc_attr( $cols[9] . $manag_no . "_" . $i ); ?>" value="del" /></label>
					</div>
					</td>
				</tr>
		
		<?php } if( $i > 0 ){ ?>
		
				<tr class="widefat alternate">
					<td>
					<input type="text" name="<?php echo esc_attr( $feadvns_search_b_label . $manag_no ); ?>" style="width: 100%" value="<?php echo esc_attr( data_to_post( $feadvns_search_b_label . $manag_no ) ); ?>" />
					</td>
					<td colspan="4">
					（検索ボタン）
					</td>
				</tr>
				<tr>
					<td colspan="2"></td>
					<td colspan="3" style="vertical-align: top;">
						<div class="feas-members2">
						<label>前に挿入 <input type="text" name="<?php echo esc_attr( $feadvns_search_b_label . $manag_no . '_before' ); ?>" style="width: 80%" value="<?php echo esc_attr( data_to_post( $feadvns_search_b_label . $manag_no . '_before' ) ); ?>"></label><br />
						<label>後に挿入 <input type="text" name="<?php echo esc_attr( $feadvns_search_b_label . $manag_no . '_after' ); ?>" style="width: 80%" value="<?php echo esc_attr( data_to_post( $feadvns_search_b_label . $manag_no . '_after' ) ); ?>"></label>
						</div>
					</td>
				</tr>
		<?php } else { ?>
				<tr>
					<td style="text-align:center;">検索条件がありません。</td>
				</tr>
		<?php } ?>
		
			</tbody>
			<tfoot>
				<tr>
					<th>ラベル</th>
					<th>条件</th>
					<th>形式</th>
					<th>並び順</th>
					<th>検索方法</th>
				</tr>
			</tfoot>
		</table>
	
	
		<?php if( isset( $_POST[ 'c_form_number'] ) ){ ?>
		<input type="hidden" name="c_form_number" value="<?php echo esc_attr( $_POST['c_form_number'] ); ?>" />
		<?php } ?>
		<input type="submit" value="設定を保存" class="button-primary action" />
		<input type="hidden" name="ac" value="update" />
	
		<div id="feas-pv">
			<h3>プレビュー</h3>
			<p class="text-small"><label>
			<?php
			$pvcss_checked = '';
			if( db_op_get_value( $pv_css . $manag_no ) == 'yes' )
				$pvcss_checked = ' checked="checked"';
			?>
				<input type="checkbox" name="<?php echo esc_attr( $pv_css . $manag_no ); ?>" id="<?php echo esc_attr( $pv_css . $manag_no ); ?>" value="yes"<?php echo $pvcss_checked; ?> /> 「フォーム外観」のCSSを適用する
			</label></p>
			<div id="nostyle" style="border:1px solid #000000;padding:10px;">
				<?php
				if ( is_ssl() ) {
					$src_url = home_url( "/", "https" );
				} else {
					$src_url = home_url( "/" );
				}
				?>
				<iframe class="autoHeight" src="<?php echo esc_url( $src_url ); ?>?feas_pv=1&amp;feas_mng_no=<?php echo esc_attr( $manag_no ); ?>&amp;feas_pv_type=search" width="100%" marginheight="0" scrolling="auto">プレビュー</iframe>
			</div>
		</div>
	
	</form>

<div id="feas-code-sample">
	<p style="color:black;">
	次のコードをコピーしてテンプレート（例：テンプレートフォルダ/index.php 等）内にペーストしてください。<br />
	語句の表現、divの有無等は自由に変えてください。<br />
	検索フォームの整形はご使用中のテンプレートフォルダ内のstyle.cssに追加設定を行って下さい。</p>
	<?php
	$disp_no = null;
	if( $manag_no > 0 )
		$disp_no = $manag_no;
	?>
<textarea onfocus="SelectText( this );" style="width: 700px; height: 25em;">
&lt;?php if( function_exists('create_searchform') ){ ?&gt;	
&lt;div id=&quot;feas-<?php echo esc_attr( $manag_no ); ?>&quot;&gt;
&lt;div id=&quot;feas-form-<?php echo esc_attr( $manag_no ); ?>&quot;&gt;
&lt;?php create_searchform(<?php echo esc_attr( $disp_no ); ?>); ?&gt;
&lt;/div&gt;
&lt;div id=&quot;feas-result-<?php echo esc_attr( $manag_no ); ?>&quot;&gt;
&lt;?php if( is_search() ){ ?&gt;
&lt;?php if( $add_where != null || $w_keyword != null ): ?&gt;
「&lt;?php search_result(); ?&gt;」の条件による検索結果 &lt;?php feas_count_posts(<?php if( $manag_no > 0 ){ echo $disp_no; } ?>); ?&gt; 件
&lt;?php else: ?&gt;
&lt;h3&gt;検索条件が指定されていません。&lt;/h3&gt;
&lt;?php endif; ?&gt;
&lt;?php } else { ?&gt;
現在の登録件数：&lt;?php feas_count_posts(<?php if( $manag_no > 0 ){ echo $disp_no; } ?>); ?&gt; 件
&lt;?php } ?&gt;
&lt;/div&gt;
&lt;/div&gt;
&lt;?php } ?&gt;</textarea>

<p style="color:black;">
ショートコードをご使用の場合は、下記コードをコピーして任意の投稿／ページ内にペーストしてください。
</p>
<p style="color:black;">検索フォーム</p>
<textarea onfocus="SelectText( this );" style="width: 700px; height: 2em;">
[create-searchform<?php if( $manag_no > 0 ){ print( " id=" . $disp_no ); } ?>]</textarea>
<p style="color:black;">該当件数表示</p>
<textarea onfocus="SelectText( this );" style="width: 700px; height: 2em;">
[feas-count-posts<?php if( $manag_no > 0 ){ print( " id=" . $disp_no ); } ?>]</textarea>

<p style="color:black;">
Ktai Style (携帯サイトプラグイン) テーマ用
</p>
<textarea onfocus="SelectText( this );" style="width: 700px; height: 27em;">
&lt;?php if( function_exists('create_searchform') ){ 
global $wp_query, $add_where, $w_keyword; ?&gt;
&lt;div id=&quot;feas-<?php echo esc_attr( $manag_no ); ?>&quot;&gt;
&lt;div id=&quot;feas-form-<?php echo esc_attr( $manag_no ); ?>&quot;&gt;
&lt;?php create_searchform(<?php echo esc_attr( $disp_no ); ?>); ?&gt;
&lt;/div&gt;
&lt;div id=&quot;feas-result-<?php echo esc_attr( $manag_no ); ?>&quot;&gt;
&lt;?php if( is_search() ){ ?&gt;
&lt;?php if( $add_where != null || $w_keyword != null ): ?&gt;
「&lt;?php search_result(); ?&gt;」の条件による検索結果 &lt;?php feas_count_posts(<?php if( $manag_no > 0 ){ echo $disp_no; } ?>); ?&gt; 件
&lt;?php else: ?&gt;
&lt;h3&gt;検索条件が指定されていません。&lt;/h3&gt;
&lt;?php endif; ?&gt;
&lt;?php } else { ?&gt;
現在の登録件数：&lt;?php feas_count_posts(<?php if( $manag_no > 0 ){ echo $disp_no; } ?>); ?&gt; 件
&lt;?php } ?&gt;
&lt;/div&gt;
&lt;/div&gt;
&lt;?php } ?&gt;</textarea>
	
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
