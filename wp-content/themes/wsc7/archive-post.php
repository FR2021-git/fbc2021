<?php /* WordPress CMS Theme WSC Project. */ get_header(); ?>
<?php /* Template Name: 速報一覧*/ ?>

<?php if (have_posts()) : while (have_posts()) : the_post(); ?>
    <?php $args = array(
        'orderby'            => 'post_date',
        'order'                => 'DESC',
        'post_type'            => 'post',
        'post_status'        => 'publish', 
        'posts_per_page'    => '100'
    );
    $postlist = get_posts($args); ?>
    <?php $h1='速報' ?>

<div id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
    <div class="row">

        <!-----------------------------------左メニュー--------------------------------->
        <section id="cat_menu" class="d-none d-sm-block col-md-3">
            <nav>
                <h2>経済誌ごとに速報を読む</h2>
                <ul>
                    <?php 
                        $args = array(
                        'orderby'            => 'ID',
                        'order'              => 'ASC',
                        'title_li'           => '',
                        'exclude'            => '1'
                        ); ?>
                    <?php wp_list_categories($args); ?>
                </ul>
            </nav>
        </section>
        <!------------------------------メインコンテンツ------------------------------>
        <section class="col-md-6 col">
            <h1><?php echo $h1; ?> 一覧</h1>
            <article class="list_news">
                <?php $pub_date; ?>
                <?php foreach ($postlist as $post) : setup_postdata($post); ?>
                    <?php $my_date = the_date('', '<h2>', '</h2>', false); ?>
                    <?php if ($my_date == $pubdate) : ?>
                        <li>
                            <a href="<?php the_permalink(); ?>">
                                <?php the_title(); ?>
                                <div class="meta"></div>
                            </a>
                        </li>
                    <?php else: ?>
                        <?php if ($my_date !== null) { ?> </ul> <?php } ?> 
                        <ul class="list_length shadow curve">
                            <?php echo $my_date; ?>
                            <li>
                                <a href="<?php the_permalink(); ?>">
                                    <?php the_title(); ?>
                                </a>
                            </li>
                    <?php endif; ?>
                    <?php $pub_date = $my_date; ?>
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