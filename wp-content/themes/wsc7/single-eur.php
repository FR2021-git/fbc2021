<?php /* WordPress CMS Theme WSC Project. */ get_header(); ?>

<!--タクソノミー変数セット-->
<?php $taxonomies = 'eur_sorte'; ?>

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

                <section id="related" class="shadow curve">
                    <?php if (function_exists("get_yuzo_related_posts")) { get_yuzo_related_posts(); } ?>

                    <h2>欧州経済ウオッチャー無料記事</h2>
                    <?php $args = array(
                        'post_type' => 'eur',
                        'posts_per_page'    => 4,
                        'orderby'            => 'date',
                        'tax_query'            => array(
                            array(
                                'taxonomy'    => 'free_article',
                                'field'        => 'slug',
                                'terms'        => 'eur-free'
                            )
                        )
                    );
                    $postlist = get_posts($args); ?>
                    <div class="yuzo_related_post style-3">
                        <?php foreach ($postlist as $post) : setup_postdata($post); ?>
                            <div class="relatedthumb yuzo-list">
                                <a class="link-list yuzo__text--title" href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                                <span class="yuzo_text"><?php echo mb_substr(get_the_excerpt(), 0, 100); ?>&hellip;</span>
                            </div>
                        <?php endforeach; ?>
                        <a class="more" href="<?php echo home_url('/'); ?>free_article">過去の無料記事を読む</a>
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