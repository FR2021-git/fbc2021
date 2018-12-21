<?php
/**
 * WordPress CMS Theme WSC Project.
 */
?>
<!DOCTYPE html>
<html lang="ja">

<head>
<meta charset="UTF-8">
<title><?php bloginfo('name'); ?></title>
<?php wp_head(); ?>
<meta name="author" content="Ishiguro/FBC GmbH" />
<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />

<link rel="SHORTCUT ICON" href="<?php echo get_template_directory_uri(); ?>/img/favicon.ico" />
<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.1.3/css/bootstrap.min.css">
<link rel="stylesheet" href="<?php bloginfo('stylesheet_url'); ?>" type="text/css" />
<link href="https://maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css" rel="stylesheet">

<link rel="alternate" href="<?php echo home_url(); ?>" hreflang="ja" />

<script type="application/ld+json">
{
  "@context": "http://schema.org",
  "@type": "NewsArticle",
  "mainEntityOfPage": {
    "@type": "WebPage",
    "@id": "https://example.org/article"
  },
  "publisher": {
     "name": "FBC GmbH",
     "@type": "Organization",
     "logo": {
        "@type": "ImageObject",
        "url": "https://fbc.de/wp-content/themes/wsc7/img/logo.png"
     }
  }
}
</script>

<!-- 最新号日付取得 -->
<?php $args = array('post_type' => 'sc','posts_per_page' => 1); $postlist = get_posts($args); ?>
<?php foreach ($postlist as $post) : setup_postdata($post); ?>
    <?php global $midashi_date_sc; $midashi_date_sc = get_the_time('m/d'); ?>
<?php endforeach; ?>
<?php $args = array('post_type' => 'ost','posts_per_page' => 1); $postlist = get_posts($args); ?>
<?php foreach ($postlist as $post) : setup_postdata($post); ?>
    <?php global $midashi_date_ost; $midashi_date_ost= get_the_time('m/d'); ?>
<?php endforeach; ?>
<?php $args = array('post_type' => 'eur','posts_per_page' => 1); $postlist = get_posts($args); ?>
<?php foreach ($postlist as $post) : setup_postdata($post); ?>
    <?php global $midashi_date_eur; $midashi_date_eur= get_the_time('m/d'); ?>
<?php endforeach; ?>
<?php $args = array('post_type' => 'auto','posts_per_page' => 1); $postlist = get_posts($args); ?>
<?php foreach ($postlist as $post) : setup_postdata($post); ?>
    <?php global $midashi_date_auto; $midashi_date_auto= get_the_time('m/d'); ?>
<?php endforeach; ?>

<!-- ユーザー情報取得 -->
<?php global $current_user;
$current_user = wp_get_current_user();
$accounttype0 = $current_user->account_type[0];
$accounttype1 = $current_user->account_type[1];
$accounttype2 = $current_user->account_type[2];
$accounttype3 = $current_user->account_type[3]; ?>



</head>

<body <?php body_class(); ?>>

<header role="banner" itemscope="itemscope" itemtype="http://schema.org/WPHeader">

<nav class="navbar navbar-expand-md navbar-light fixed-top" style="background-color:#fff;">
        <a id="site-title" class="navbar-brand col-6 col-md-2" href="<?php echo home_url(); ?>"><img src="<?php echo get_template_directory_uri(); ?>/img/logo.png"  alt="<?php bloginfo('name'); ?>" /></a>
        <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarsGlobal" aria-controls="navbarsGlobal" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarsGlobal">
            <!-- モバイル用メニュー -->
            <?php wp_nav_menu(array('menu' => 'global_mobile_menu', 'depth' => 1, 'menu_class' => 'd-md-none')); ?>

            <!-- 真ん中 -->
            <div id="header_middle" class="col-md-9 d-none d-sm-block">
                <!-- 検索 -->
                <div class="text-center"><?php get_search_form(); ?></div>
                <!-- 最新号メニュー -->
                <ul class="flex ard">
                    <!--sc最新号を読む-->
                    <li class="newest_letters_footers"><a class="nodec" href="<?php echo home_url('/'); ?>sc">
                        <img src="<?php echo get_template_directory_uri(); ?>/img/topsc.jpg" />
                        <p><b>ドイツ経済誌</b></br>最新<?php echo $midashi_date_sc; ?>号</p></a>
                    </li>         
                    <!--OST最新号を読む-->
                    <li class="newest_letters_footers"><a class="nodec" href="<?php echo home_url('/'); ?>ost">
                        <img src="<?php echo get_template_directory_uri(); ?>/img/topost.jpg" />
                        <p><b>東欧経済誌</b></br>最新<?php echo $midashi_date_ost; ?>号</p></a>
                    </li>
                    <!--EUR最新号を読む-->
                    <li class="newest_letters_footers"><a class="nodec" href="<?php echo home_url('/'); ?>eur">
                        <img src="<?php echo get_template_directory_uri(); ?>/img/topeur.jpg" />
                        <p><b>欧州経済誌</b></br>最新<?php echo $midashi_date_eur; ?>号</p></a>
                    </li>
                    <!--auto最新号を読む-->
                    <li class="newest_letters_footers"><a class="nodec" href="<?php echo home_url('/'); ?>auto">
                        <img src="<?php echo get_template_directory_uri(); ?>/img/topauto.jpg" />
                        <p><b>自動車産業誌</b></br>最新<?php echo $midashi_date_auto; ?>号</p></a>
                    </li>
                </ul>
            </div>
            <!-- ログインフォーム -->
            <div id="header_right" class="LoginFormDiv col-md-3">
                <?php if (is_user_logged_in()) { ?>
                    <p class="text-right">
                        <?php echo $current_user->user_login . " 様ログイン中"; ?><br>
                        <a href="<?php echo wp_logout_url(home_url()); ?>">ログアウトする</a>
                    </p>
                <?php } else { ?>
                    <form class="loginform" method="post" action="<?php echo home_url(); ?>/login/">
                        <p><label>メールアドレス<input type="text" name="log" id="user_login" class="input imedisabled user_login" value="" tabindex="1" /></label></p>
                        <p><label>パスワード<input type="password" name="pwd" id="user_pass" class="input user_pass" value="" tabindex="2" /></label></p>
                        <p class="inline"><label><input name="rememberme" type="checkbox" id="rememberme" value="forever" tabindex="3" checked="checked" /> ログイン情報を記憶</label></p>
                        <p class="submit inline">
                            <input type="submit" name="wp-submit" id="wp-submit" class="submit login" value="ログイン &raquo;" tabindex="4" />
                            <input type="hidden" name="redirect_to" value="<?php echo  (is_ssl() ? 'https' : 'http') . '://' . $_SERVER["HTTP_HOST"] . $_SERVER["REQUEST_URI"];?>" />
                            <input type="hidden" name="testcookie" value="1" />
                        </p>
                    </form>
                <?php }; ?>
            </div>
        </div>
    </nav>
</header>