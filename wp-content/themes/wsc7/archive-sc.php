<?php /* WordPress CMS Theme WSC Project. */ get_header(); ?>
<?php /* Template Name: ドイツ経済ニュースヘッドライン*/ ?>

<!--タクソノミーセット-->
<?php $taxonomies    = 'sc_sorte'; ?>

<!--タームスラッグ変数セット-->
<?php $slug_sogo        = 'sc-sogo'     ; ?>
<?php $slug_mame        = 'mame'        ; ?>
<?php $slug_tanshin     = 'tanshin'     ; ?>
<?php $slug_keiri       = 'keiri'       ; ?>
<?php $slug_medemiru    = 'sc-medemiru' ; ?>
<?php $slug_kawase      = 'kawase'      ; ?>

<!--タームネーム取得-->
<?php $args = array('orderby' => 'id'); ?>
<?php $terms = get_terms($taxonomies, $args); ?>
<?php foreach ($terms as $term) { ?>
    <?php $slug = $term->slug; ?>
    <?php if ($slug === $slug_sogo) {
        $name_sogo = $term->name; 
    } else if ($slug === $slug_mame) {
        $name_mame    = $term->name;
    } else if ($slug === $slug_tanshin) {
        $name_tanshin    = $term->name;
    } else if ($slug === $slug_keiri) {
        $name_keiri    = $term->name;
    } else if ($slug === $slug_medemiru) {
        $slug = null;
    } else if ($slug === $slug_kawase) {
        $slug = null;
    } else {
        $slugs_main[] = $slug; $names_main[] = $term->name; 
    } ?>
<?php } ?>

<div id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
    <div class="row">

        <!-----------------------------------左メニュー--------------------------------->
        <section id="cat_menu" class="d-none d-sm-block col-md-3">
            <nav>
                <h2>誌面区分ごとに過去の記事一覧を見る</h2>
                <ul>
                    <?php 
                        $args = array(
                        'orderby'            => 'ID',
                        'order'              => 'ASC',
                        'title_li'           => '',
                        'exclude'            => '1032, 1033',
                        'taxonomy'           => $taxonomies
                    ); ?>
                    <?php wp_list_categories($args); ?>
                </ul>
            </nav>
            <select name="archive-dropdown" onChange='document.location.href=this.options[this.selectedIndex].value;'>
                <option value=""><p>バックナンバーを見る</p></option>
                <?php wp_get_archives(array( 'type' => 'daily', 'format' => 'option', 'post_type'=>'ost')); ?>
            </select>
        </section>

        <!------------------------------メインコンテンツ------------------------------>
        <section class="col-md-6 col">
            <?php $post = $posts[0]; ?>
            <h1><?php the_time(__('Y.n.j', 'wsc7')); ?>号ヘッドライン</h1>

            <!---------------------------------総合--------------------------->
            <article class="list_news">
                <h2><?php echo $name_sogo; ?></h2>
                <ul class="shadow curve list_length">
                    <?php if (have_posts()) : while (have_posts()) : the_post(); ?>
                        <?php if (has_term($slug_sogo, $taxonomies)) : ?>
                             <li><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></li>
                        <?php endif; ?>
                    <?php endwhile; ?>
                    <?php endif; ?>
                </ul>
            </article>

            <!---------------------------------豆知識--------------------------->
            <article id="read_column">
                <h2><?php echo $name_mame; ?></h2>
                <ul class="shadow curve list_length">
                    <?php if (have_posts()) : while (have_posts()) : the_post(); ?>
                        <?php if (has_term($slug_mame, $taxonomies)) : ?>
                            <li>
                                <a href="<?php the_permalink(); ?>">
                                    <div class="flex">
                                        <img src="<?php echo get_template_directory_uri(); ?>/img/pic/<?php echo $slug_mame; ?>.jpg" />
                                        <p class="title"><?php the_title(); ?></p>
                                    </div>
                                    <?php if (mb_strlen($post->post_content, 'UTF-8')>110) {
                                            $content= mb_substr($post->post_content, 0, 110, 'UTF-8'); ?>
                                    <p><?php echo $content ?><span class="more_read"><?php echo '… 続きを読む' ?></span><?php } else {echo $post->post_content;} ?></p>
                                </a>
                            </li>
                        <?php endif; ?>
                    <?php endwhile; ?>
                    <?php endif; ?>
                </ul>
            </article>

            <!---------------------------------短信--------------------------->
            <article id="read_column">
                <h2><?php echo $name_tanshin; ?></h2>
                <ul class="shadow curve list_length">
                    <?php if (have_posts()) : while (have_posts()) : the_post(); ?>
                        <?php if (has_term($slug_keiri, $taxonomies)) : ?>
                            <?php $keiri = 0; ?>
                        <?php endif; ?>
                        <?php if (has_term($slug_tanshin, $taxonomies)) : ?>
                            <li>
                                <div class="flex">
                                    <img src="<?php echo get_template_directory_uri(); ?>/img/pic/<?php echo $slug_tanshin; ?>.jpg" />
                                    <p class="title"><?php the_title(); ?></p>
                                </div>
                                <?php echo $post->post_content; ?></p>
                            </li>
                        <?php endif; ?>
                    <?php endwhile; ?>
                    <?php endif; ?>
                </ul>
            </article>

            <!---------------------------------経理--------------------------->
            <?php if ($keiri === 0) : ?>
                <article id="read_column">
                    <h2><?php echo $name_keiri; ?></h2>
                    <ul class="shadow curve list_length">
                        <?php if (have_posts()) : while (have_posts()) : the_post(); ?>
                            <?php if (has_term($slug_keiri, $taxonomies)) : ?>
                                <li>
                                    <a href="<?php the_permalink(); ?>">
                                        <div class="flex">
                                            <img src="<?php echo get_template_directory_uri(); ?>/img/pic/<?php echo $slug_keiri; ?>.jpg" />
                                            <p class="title"><?php the_title(); ?></p>
                                        </div>
                                        <?php if (mb_strlen($post->post_content, 'UTF-8')>110) {
                                                $content= mb_substr($post->post_content, 0, 110, 'UTF-8'); ?>
                                        <p><?php echo $content ?><span class="more_read"><?php echo '… 続きを読む' ?></span><?php } else {echo $post->post_content;} ?></p>
                                    </a>
                                </li>
                            <?php endif; ?>
                        <?php endwhile; ?>
                        <?php endif; ?>
                    </ul>
                </article>
            <?php endif; ?>

            <!---------------------------------企業・経済産業--------------------------->
            <?php $i = 0; ?>
            <?php foreach ($slugs_main as $slugm) { ?>
                <article class="list_news">
                    <h2><?php echo $names_main[$i]; ?></h2>
                    <ul class="shadow curve list_length">
                        <?php if (have_posts()) : while (have_posts()) : the_post(); ?>
                            <?php if (has_term($slugm, $taxonomies)) : ?>
                                 <li><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></li>
                            <?php endif; ?>
                        <?php endwhile; ?>
                        <?php endif; ?>
                    </ul>
                </article>
                <?php ++$i; ?>
            <?php } ?>
        </section>

        <!----------------------------グローバルサイドメニュー---------------------------->
        <?php wp_nav_menu(array('container_id' => 'globalSideMenu', 'theme_location' => 'globalMenu', 'depth' => 2, 'container_class' => 'col-md-3 d-none d-sm-block', 'menu_class' => 'shadow')); ?>

    <!---row end--->
    </div>
<!---postclass end--->
</div>
<div id="toTop"><a href="#header">▲このページのトップへ</a></div>
<?php get_footer(); ?>