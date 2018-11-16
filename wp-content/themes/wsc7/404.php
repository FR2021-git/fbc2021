<?php /* WordPress CMS Theme WSC Project. */ get_header(); ?>
<div id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
    <div class="row">
        <!------------------------------メインカラム------------------------------>
        <section id="page_main" class="col-md-9 col">
            <div id="404">
                <h1 class="pageTitle"><?php _e('Page not found'); ?></h1>
                <p>ページが移動／削除されてしまった可能性があります。<br>
                お手数ですが、URLをご確認いただくか、FBCのホームページトップより再度お探しください。</p>
                <p><?php _e('Apologies, but the page you requested could not be found. Perhaps searching will help.', 'wsc7'); ?></p>
            </div>
        </section>
        <!----------------------------グローバルサイドメニュー---------------------------->
        <?php wp_nav_menu(array('container_id' => 'globalSideMenu', 'theme_location' => 'globalMenu', 'depth' => 2, 'container_class' => 'col-md-3 d-none d-sm-block', 'menu_class' => 'shadow')); ?>
    </div>
</div>
<div id="toTop"><a href="#header">▲このページのトップへ</a></div>
<?php get_footer(); ?>
