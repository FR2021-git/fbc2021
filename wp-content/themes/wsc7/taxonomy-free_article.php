<?php /* WordPress CMS Theme WSC Project. */ get_header(); ?>
<?php /* Template Name: 無料記事タクソノミーアーカイブ*/ ?>
<?php $post = $posts[0]; ?>
<?php $post_type = get_post_type(); ?>

<div id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
    <div class="row">

        <!-----------------------------------左メニュー--------------------------------->
        <section id="cat_menu" class="d-none d-sm-block col-md-3">
            <nav>
                <h2>経済誌ごとに無料記事を読む</h2>
                <ul>
                    <?php 
                        $args = array(
                        'orderby'            => 'ID',
                        'order'              => 'ASC',
                        'title_li'           => '',
                        'exclude'            => '3065',
                        'taxonomy'           => $taxonomy
                        ); ?>
                    <?php wp_list_categories($args); ?>
                    <li><a href= "/free_article">全ての無料記事をまとめて見る</a></li>
                </ul>
            </nav>
        </section>
        <!------------------------------メインコンテンツ------------------------------>
        <section class="col-md-6 col">
            <h1><?php echo esc_html(get_post_type_object(get_post_type())->label); ?> 無料記事 一覧</h1>
            <!-------------------------------------記事一覧----------------------------------->
            <article class="list_news">
                <ul class="list_length shadow curve">
                    <?php if (have_posts()) : while (have_posts()) : the_post(); ?>
                        <li>
                            <a href="<?php the_permalink(); ?>">
                                <?php the_title(); ?><span class="meta"><?php the_time('Y/n/j'); ?></span>
                            </a>
                        </li>
                    <?php endwhile; ?><?php endif; ?>
                </ul>
            </article>


        </section>
        <!----------------------------グローバルサイドメニュー---------------------------->
        <?php wp_nav_menu(array('container_id' => 'globalSideMenu', 'theme_location' => 'globalMenu', 'depth' => 2, 'container_class' => 'col-md-3 d-none d-sm-block', 'menu_class' => 'shadow')); ?>

    <!---row end--->
    </div>
<!---postclass end--->
</div>
<div id="toTop"><a href="#header">▲このページのトップへ</a></div>
<?php get_footer(); ?>