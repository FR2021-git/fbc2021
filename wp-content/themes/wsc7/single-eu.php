<?php /* WordPress CMS Theme WSC Project. */ get_header(); ?>

<!--タクソノミー変数セット-->
<?php $taxonomies = 'eu_sorte'; ?>

<?php if (have_posts()) : while (have_posts()) : the_post(); ?>
    <div id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
        <div class="row">
            <!------------------------------メインカラム------------------------------>
            <section id="page_main" class="col-md-9 col">
                <section id="kiji_sum" class="shadow curve">
                    <div class="kiji_header">
                        <!-パンくずリスト記述->
                        <div class="breadcrumbs kiji_wo d-none d-sm-block">
                            <?php if (function_exists('bcn_display')) { bcn_display(); } ?>
                        </div>
                        <!-タイトル記述->
                        <h1><?php echo the_title(); ?></h1>
                        <!-メタ情報記述->
                        <?php $gou = get_post_meta($post->ID, "gou", true); ?>
                        <p class="data"><?php the_time(__('Y.n.j', 'wsc7')); ?>発行 No.<?php echo $gou; ?>号</p>
                        <!-メタ情報記述->
                        <div class="meta">
                            <?php the_taxonomies('before=<ul>&after=</ul>&sep=<li></li>'); ?>
                        </div>
                    </div>
                    <!-記事本文記述->
                    <div class="kiji_text">
                        <?php the_content(__('more')); ?>
                    </div>
                    </section>
            </section>

            <!----------------------------グローバルサイドメニュー---------------------------->
            <?php wp_nav_menu(array('container_id' => 'globalSideMenu', 'theme_location' => 'globalMenu', 'depth' => 2, 'container_class' => 'col-md-3 d-none d-sm-block', 'menu_class' => 'shadow')); ?>
        </div>
    </div>
<?php endwhile; ?>
<?php endif; ?>
<div id="toTop"><a href="#header">▲このページのトップへ</a></div>
<?php get_footer(); ?>