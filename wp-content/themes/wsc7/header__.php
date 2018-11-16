<?php /* WordPress CMS Theme WSC Project. */ ?>
<!DOCTYPE html>
<html <?php language_attributes($doctype); ?>>
<head>
<meta charset="<?php bloginfo( 'charset' ); ?>">
<title><?php bloginfo('name'); wp_title(); ?></title>
<meta name="description" content= <?php bloginfo('description'); ?>>
<meta name="keywords" content="欧州経済, ニューズレター, 市場調査, サンプル入手サービス, ロングリストショートリスト">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
<meta name="author" content="I/FBC GmbH" />
<link rel="stylesheet" href="<?php bloginfo('stylesheet_url'); ?>" type="text/css">

<!--[if lt IE 9]>
	<script src="http://html5shim.googlecode.com/svn/trunk/html5.js"></script>
	<script src="http://css3-mediaqueries-js.googlecode.com/svn/trunk/css3-mediaqueries.js"></script>
<![endif]-->

<?php wp_enqueue_script( 'jquery' ); ?>
<?php if ( is_singular() ) wp_enqueue_script( 'comment-reply' ); wp_head(); ?>

<!-- header スマホ用 -->
<script src="<?php echo get_template_directory_uri(); ?>/js/responsive-nav.js"></script>
</head>

<!-- sideFix -->
<script type="text/javascript">
jQuery(function($) { var nav = $('#sideFix'), offset = nav.offset(); 
$(window).scroll(function (){ 
if($(window).scrollTop() > offset.top - 20 ) { nav.addClass('fixed');} else { nav.removeClass('fixed');}
});});
</script>

<!-- Analytics conversion -->
<script>
document.addEventListener( 'wpcf7mailsent', function( event ) {
    ga('send', 'event', 'Contact Form', 'submit');
}, false );
</script>

<!-- dropdown(service > newsletter) -->
<script type="text/javascript">
jQuery(function($){
  $(".to_panel").each(function(){
  var btn = $(this);
  var panel = $(".mod_slidelink_panel_entry");
  btn.click(function(){
    var id = btn.prop("id");
    if($(".mod_slidelink_panel_entry."+id).css("display")=="none"){
			if($(".mod_slidelink_panel_entry.open").size()){
		  $("#slidepanel ul.slidebtn li").removeClass("open").addClass("close");
			$(".mod_slidelink_panel_entry.open:not(animated)").removeClass("open").addClass("close").slideUp("fast",function(){
			$(".mod_slidelink_panel_entry."+id).slideDown("fast").removeClass("close").addClass("open");
			$(btn).removeClass("close").addClass("open");})
			}else{
			$(".mod_slidelink_panel_entry."+id).slideDown("fast").removeClass("close").addClass("open");
			$(btn).removeClass("close").addClass("open");
			}
    }else{
		$(".mod_slidelink_panel_entry."+id).slideUp("fast");
		$(".mod_slidelink_panel_entry."+id).removeClass("open").addClass("close");
		$(btn).removeClass("open").addClass("close");}

		});
  });
});
</script>


<body <?php body_class(); ?>>
	<!-最新号日付取得->
	<?php $args = array('post_type' => 'sc','posts_per_page' => 1); $postlist = get_posts( $args ); ?>
	<?php foreach ($postlist as $post) : setup_postdata($post); ?>
		<?php global $midashi_date_sc; $midashi_date_sc = get_the_time('m/d'); ?>
	<?php endforeach; ?>

	<?php $args = array('post_type' => 'ost','posts_per_page' => 1); $postlist = get_posts( $args ); ?>
	<?php foreach ($postlist as $post) : setup_postdata($post); ?>
		<?php global $midashi_date_ost; $midashi_date_ost= get_the_time('m/d'); ?>
	<?php endforeach; ?>

	<?php $args = array('post_type' => 'eur','posts_per_page' => 1); $postlist = get_posts( $args ); ?>
	<?php foreach ($postlist as $post) : setup_postdata($post); ?>
		<?php global $midashi_date_eur; $midashi_date_eur= get_the_time('m/d'); ?>
	<?php endforeach; ?>

	<?php $args = array('post_type' => 'auto','posts_per_page' => 1); $postlist = get_posts( $args ); ?>
	<?php foreach ($postlist as $post) : setup_postdata($post); ?>
		<?php global $midashi_date_auto; $midashi_date_auto= get_the_time('m/d'); ?>
	<?php endforeach; ?>

	<!-ユーザー情報取得->
	<?php global $current_user;
		$current_user = wp_get_current_user();
		$accounttype0 = $current_user->account_type[0];
		$accounttype1 = $current_user->account_type[1];
		$accounttype2 = $current_user->account_type[2];
		$accounttype3 = $current_user->account_type[3]; ?>

	<!-- mobile用ログインフォーム -->
	<div id="loginform_mb">
		<?php if ( is_user_logged_in() ) { ?>
			<?php echo $current_user->user_login . " 様ログイン中";
			?><a class="right logout" href="http://fbc.de/login/?action=logout">ログアウト</a>
		<?php }; ?>
	</div>
	<!-- mobile用グローバルメニュー -->
	<div id="newest_letters_mb">
		<ul>
		<li><a href="<?php echo home_url( '/' ); ?>/sc">ドイツ経済ニュース最新号</a></li>
		<li><a href="<?php echo home_url( '/' ); ?>/ost">東欧経済ニュース最新号</a></li>
		<li><a href="<?php echo home_url( '/' ); ?>/eur">欧州経済ウオッチャー最新号</a></li>
		<li><a href="<?php echo home_url( '/' ); ?>/auto">自動車産業ニュース最新号</a></li>
		</ul>
	</div>

	<!-- header -->
	<header>
        <!-- ヘッダー上部 -->
        <div class="header_top">
		    <div id="site-title"><a href="<?php echo home_url(); ?>"><img src="<?php echo get_template_directory_uri(); ?>/img/logo.png"  alt="<?php bloginfo('name'); ?>" /></a></div>
            <div id="header-search"><?php get_search_form(); ?></div>
		    <div id="header-loginform">
			    <?php if ( is_user_logged_in() ) { ?>
				    <?php echo $current_user->user_login . " 様ログイン中";
				    ?>
				    <br><a href="<?php echo home_url( '/wp-login.php?action=logout' ); ?>">ログアウトする</a>
			    <?php }; ?>
    		</div>
		</div>

		<!-- globalMenu -->
		<nav id="" class="header_bottom">
			<ul>
				<li><a href="<?php echo home_url(); ?>"><b>トップ</b></a></li>
				<!-sc最新号を読む->
				<li class="newest_letters_footers"><a class="nodec" href="<?php echo home_url( '/' ); ?>sc">
					<img src="<?php echo get_template_directory_uri(); ?>/img/topsc.jpg" />
					<p><b>ドイツ経済ニュース</b></br>最新<?php echo $midashi_date_sc; ?>号</p></a>
				</li>				
				
				<!-OST最新号を読む->
				<li class="newest_letters_footers"><a class="nodec" href="<?php echo home_url( '/' ); ?>ost">
					<img src="<?php echo get_template_directory_uri(); ?>/img/topost.jpg" />
					<p><b>東欧経済ニュース</b></br>最新<?php echo $midashi_date_ost; ?>号</p></a>
				</li>

				<!-EUR最新号を読む->
				<li class="newest_letters_footers"><a class="nodec" href="<?php echo home_url( '/' ); ?>eur">
					<img src="<?php echo get_template_directory_uri(); ?>/img/topeur.jpg" />
					<p><b>欧州経済ウオッチャー</b></br>最新<?php echo $midashi_date_eur; ?>号</p></a>
				</li>

				<!-auto最新号を読む->
				<li class="newest_letters_footers"><a class="nodec" href="<?php echo home_url( '/' ); ?>auto">
					<img src="<?php echo get_template_directory_uri(); ?>/img/topauto.jpg" />
					<p><b>自動車産業ニュース</b></br>最新<?php echo $midashi_date_auto; ?>号</p></a>
				</li>
			</ul>
		</nav>

		<!-- スマホ用loginボタン -->
		<a href="#login" class="toggle_mb loginform-toggle left">ログイン</a>
		<!-- スマホ用menuボタン -->
		<a href="#nav" class="toggle_mb nav-toggle right">Menu</a>
		<div class="mb_cf"></div>
	</header>

