<?php /* WordPress CMS Theme WSC Project. */ get_header(); ?>
<div id="post-<?php the_ID(); ?>">
    <div class="row">
        <!------------------------------メインカラム------------------------------>
        <section id="page_main" class="col-md-9 col">
            <section class="shadow curve">
            <?php if (have_posts()) : ?>
                <h1 class="pageTitle"><?php _e('')?>検索キーワード: <?php the_search_query(); ?><br>検索結果 <?php echo $wp_query->found_posts; ?> 件</h1>
                <ul class="each_search list_length">
                    <?php while (have_posts()) : the_post(); ?>
                        <?php $kijitypelabel = get_post_type_object(get_post_type())->label; ?>
                        <li>
                            <a href="<?php the_permalink(); ?>">
                                <div class="cf">
                                    <h3><?php the_title(); ?></h3>
                                    <p class="meta"><?php echo $kijitypelabel ?> | <?php the_time('Y/n/j'); ?></p>
                                </div>
                                <p><?php if (mb_strlen($post->post_content, 'UTF-8') > 50) {
                                        $content= mb_substr($post->post_content, 0, 50, 'UTF-8'); ?>
                                        <?php echo $content . '[…]' ?>
                                    <?php } else {echo $post->post_content; } ?>
                                </p>
                            </a>
                            <div class="tax">
                                <?php echo get_the_term_list($post->ID, 'company', '', '');?><?php echo get_the_term_list($post->ID, 'sum_kubun', '', '');?>
                            </div>
                        </li>
                    <?php endwhile; ?>
                </ul>
            <?php else : ?>
                <h1 class="pageTitle"><?php _e('検索結果')?>検索キーワード: <?php the_search_query(); ?><br>検索結果 0 件</h1>
                <div class="search_box"><?php get_search_form(); ?></div>
            <?php endif; ?>
            <?php if (function_exists('wp_pagenavi')) : ?>
                <?php wp_pagenavi(); ?>
            <?php endif; ?>
            </section>
        </section>

        <!----------------------------グローバルサイドメニュー---------------------------->
        <?php wp_nav_menu(array('container_id' => 'globalSideMenu', 'theme_location' => 'globalMenu', 'depth' => 2, 'container_class' => 'col-md-3 d-none d-sm-block', 'menu_class' => 'shadow')); ?>
    </div>
</div>
