<?php /* WordPress CMS Theme WSC Project. */ get_header(); ?>
<?php /* Template Name: 無料記事一覧*/ ?>

<?php if (have_posts()) : while (have_posts()) : the_post(); ?>

    <?php $args = array(
        'post_type' => array('sc','ost','eur','auto'),
        'posts_per_page'    => 100,
        'orderby'            => 'date',
        'tax_query'            => array(
            array(
                'taxonomy'    => 'free_article',
                'field'        => 'slug',
                'terms'        => array ('sc-free','ost-free','eur-free','auto-free')
            )
        )
    );
    $postlist = get_posts($args); ?>

<div id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
    <div class="row">

        <!-----------------------------------左メニュー--------------------------------->
        <section id="cat_menu" class="d-none d-sm-block col-md-3">
            <nav>
                <h2>経済誌ごとに無料記事を読む</h2>
                <ul>
                    <?php 
                        $args = array(
                        'orderby'        => 'ID',
                        'order'          => 'ASC',
                        'title_li'        => '',
                        'exclude'        => '3065',
                        'taxonomy'        => 'free_article'
                        ); ?>
                    <?php wp_list_categories($args); ?>
                </ul>
            </nav>
        </section>
        <!------------------------------メインコンテンツ------------------------------>
        <section class="col-md-6 col">
            <h1>無料記事 一覧</h1>
            <article class="list_news">
                <ul class="list_length shadow curve">
                    <?php foreach ($postlist as $post) : setup_postdata($post); ?>
                        <li>
                            <a href="<?php the_permalink(); ?>">
                                <?php the_title(); ?><span class="meta"><?php the_time('Y/n/j'); ?></span>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </article>
        </section>

        <!----------------------------グローバルサイドメニュー---------------------------->
        <?php wp_nav_menu(array('container_id' => 'globalSideMenu', 'theme_location' => 'globalMenu', 'depth' => 2, 'container_class' => 'col-md-3 d-none d-sm-block', 'menu_class' => 'shadow')); ?>

    <!---row end--->
    </div>
<!---postclass end--->
</div>
<?php endwhile; ?>
<?php endif; ?>
<div id="toTop"><a href="#header">▲このページのトップへ</a></div>
<?php get_footer(); ?>