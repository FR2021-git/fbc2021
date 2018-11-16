<?php /* WordPress CMS Theme WSC Project. */ get_header(); ?>
<?php /* Template Name: コラム一覧*/ ?>

<?php if (have_posts()) : while (have_posts()) : the_post(); ?>

<?php $args = array(
    'post_type' => array('sc','ost','auto'),
    'tax_query' => array(
            'relation' => 'OR',
                array(
                'taxonomy'    => 'sc_sorte',
                'field'        => 'slug',
                'terms'        => array('mame', 'keiri')
            ),
            array(
                'taxonomy'    => 'ost_sorte',
                'field'        => 'slug',
                'terms'        => array('cafe', 'startup')
            ),
            array(
                'taxonomy'    => 'auto_sorte',
                'field'        => 'slug',
                'terms'        => 'closeup'
            )
    ),
    'orderby' => 'post_date',
    'order' => 'DESC',
    'posts_per_page' => 100
); 
$postlist = get_posts($args); ?>

<div id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
    <div class="row">

        <!-----------------------------------左メニュー--------------------------------->
        <section id="cat_menu" class="d-none d-sm-block col-md-3">
            <nav>
                <h2>経済誌ごとにコラムを読む</h2>
                <ul class="terms">
                    <p>ドイツ経済ニュース</p>
                    <?php wp_list_categories('include=1036,1126&taxonomy=sc_sorte&title_li='); ?>
                </ul>
                <ul>
                    <p>東欧経済ニュース</p>
                    <?php wp_list_categories('include=42,22541&taxonomy=ost_sorte&title_li='); ?>
                </ul>
                <ul>
                    <p>自動車産業ニュース</p>
                    <?php wp_list_categories('include=3285&taxonomy=auto_sorte&title_li='); ?>
                </ul>
            </nav>
        </section>

        <!------------------------------メインコンテンツ------------------------------>
        <section class="col-md-6 col">
            <h1>コラム 一覧</h1>
            <article id="read_column">
                    <?php foreach ($postlist as $post) : setup_postdata($post); ?>
                    <?php $kijitype = esc_html(get_post_type_object(get_post_type())->name); ?>
                <ul class="list_length shadow curve">
                    <li>
                        <a href="<?php the_permalink(); ?>">
                            <p class="title"><?php the_title(); ?></p>
                            <?php $terms = get_the_terms($post->ID, array('sc_sorte', 'ost_sorte', 'auto_sorte')); ?>
                                <p class="meta"><?php the_time('Y/n/j'); ?><br>
                            <?php echo get_post_type_object(get_post_type())->label; ?> | <?php foreach ($terms as $term) { echo $term->name; } ?></p>
                        </a>
                    </li>
                </ul>
                    <?php endforeach; ?>
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