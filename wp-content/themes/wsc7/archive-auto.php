<?php /* WordPress CMS Theme WSC Project. */ get_header(); ?>
<?php /* Template Name: 自動車産業ニュースヘッドライン*/ ?>

<!--タクソノミーセット-->
<?php $taxonomies    = 'auto_sorte'; ?>

<!--タームスラッグ変数セット-->
<?php $slug_sogo        = 'auto-sogo'        ; ?>
<?php $slug_closeup        = 'closeup'            ; ?>
<?php $slug_tanshin        = 'auto-tanshin'    ; ?>
<?php $slug_medemiru    = 'auto-medemiru'    ; ?>
<?php $slug_west        = 'eur-west'        ; ?>
<?php $slug_eu            = 'eur-eu'            ; ?>

<!--タームネーム取得-->
<?php $args = array('orderby' => 'id'); ?>
<?php $terms = get_terms($taxonomies, $args); ?>
<?php foreach ($terms as $term) { ?>
    <?php $slug = $term->slug; ?>
    <?php     if    ($slug === $slug_sogo)        { $name_sogo    = $term->name; }
        else if    ($slug === $slug_closeup)    { $name_closeup    = $term->name; }
        else if    ($slug === $slug_tanshin)    { $name_tanshin    = $term->name; }
        else if    ($slug === $slug_medemiru)    { $slug            = null; }
        else if    ($slug === $slug_west)        { $slug            = null; }
        else if    ($slug === $slug_eu)        { $slug            = null; }
        else                                { $slugs_main[]    = $slug; $names_main[] = $term->name;} ?>
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
                        'exclude'            => '21451,16981, 3286',
                        'taxonomy'           => $taxonomies
                        ); ?>
                    <?php wp_list_categories($args); ?>
                </ul>
            </nav>
            <select name="archive-dropdown" onChange='document.location.href=this.options[this.selectedIndex].value;'>
                <option value=""><p>バックナンバーを見る</p></option>
                <?php wp_get_archives(array('type' => 'daily', 'format' => 'option', 'post_type'=>'auto')); ?>
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

            <!---------------------------------クローズアップ--------------------------->
            <article id="read_column">
                <h2><?php echo $name_closeup; ?></h2>
                <ul class="shadow curve list_length">
                    <?php if (have_posts()) : while (have_posts()) : the_post(); ?>
                        <?php if (has_term($slug_closeup, $taxonomies)) : ?>
                            <li>
                                <a href="<?php the_permalink(); ?>">
                                    <div class="flex">
                                        <img src="<?php echo get_template_directory_uri(); ?>/img/pic/<?php echo $slug_closeup; ?>.jpg" />
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
                        <?php if (has_term($slug_tanshin, $taxonomies)) : ?>
                            <li>
                                <div class="flex">
                                    <img src="<?php echo get_template_directory_uri(); ?>/img/pic/tanshin.jpg" />
                                    <p class="title"><?php the_title(); ?></p>
                                </div>
                                <?php echo $post->post_content; ?></p>
                            </li>
                        <?php endif; ?>
                    <?php endwhile; ?>
                    <?php endif; ?>
                </ul>
            </article>

            <!---------------------------------その他--------------------------->
            <?php $i = 0; ?>
            <?php foreach ($slugs_main as $slugm) { ?>
                <h2><?php echo $names_main[$i]; ?></h2>
                <div class="list_news">
                    <ul class="shadow curve list_length">
                        <?php if (have_posts()) : while (have_posts()) : the_post(); ?>
                            <?php if (has_term($slugm, $taxonomies)) : ?>
                                 <li><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></li>
                            <?php endif; ?>
                        <?php endwhile; ?>
                        <?php endif; ?>
                    </ul>
                </div>
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