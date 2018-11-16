<?php /* WordPress CMS Theme WSC Project. */ get_header(); ?>
<?php if (have_posts()) : while (have_posts()) : the_post(); ?>
    <div id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
        <div class="row">
            <!------------------------------メインカラム------------------------------>
            <section id="page_main" class="col-md-9 col">
                <?php if (is_page(array ('1393', '1095'))) { ?>
                    <div id="midashi">
                        <img src = <?php echo get_template_directory_uri(); ?>/img/page/profil_top.jpg" />
                        <h1><?php wp_title(''); ?><br><span><?php bloginfo('description'); ?></span></h1>
                    </div>
                <?php } else { ?>
                    <div id="midashi">
                        <img src = <?php echo get_template_directory_uri(); ?>/img/page/profil_top.jpg" />
                        <h1><?php wp_title(''); ?><br><span><?php the_excerpt(); ?></span></h1>
                    </div>
                <?php } ?>

                <?php the_content(__('more')); ?>
            </section>
            <!----------------------------グローバルサイドメニュー---------------------------->
            <?php wp_nav_menu(array('container_id' => 'globalSideMenu', 'theme_location' => 'globalMenu', 'depth' => 2, 'container_class' => 'col-md-3 d-none d-sm-block', 'menu_class' => 'shadow')); ?>
        </div>
    </div>
<?php endwhile; ?>
<?php endif; ?>
<div id="toTop"><a href="#header">▲このページのトップへ</a></div>
<?php get_footer(); ?>