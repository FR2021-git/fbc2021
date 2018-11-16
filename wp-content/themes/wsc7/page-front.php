<?php /* WordPress CMS Theme WSC Project. */ get_header(); ?>
<?php if (have_posts()) : while (have_posts()) : the_post(); ?>
    <div id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
        <div class="row">
            <!----------------------------------左カラム------------------------------->
            <section class="d-none d-sm-block col-md-3">
                <!----------------------------------プロフィール------------------------------->
                <article id="profile" class="shadow list_pic">
                    <a href="<?php echo home_url('/'); ?>/profil">
                        <div class="flex">
                            <img src="<?php echo get_template_directory_uri(); ?>/img/profil.jpg" />
                            <h1><?php bloginfo('name'); wp_title(); ?> GmbH</h1>
                        </div>
                        <p><?php bloginfo('description'); ?><br>4誌の週刊ニューズレターを発行、市場調査や貿易業務も行います。</p>
                        <p class="more">会社概要を見る</p>
                    </a>
                </article>
                <script type='text/javascript' src='https://darksky.net/widget/default/50.1109221,8.6821267/uk12/en.js?width=100%&height=350&title=Frankfurt&textColor=002678&bgColor=f7f7f7&transparency=false&skyColor=002678&fontFamily=Default&customFont=&units=uk&htColor=002678&ltColor=002678&displaySum=yes&displayHeader=yes'></script>
            </section>
            <!------------------------------メインカラム------------------------------>
            <section class="col-md-6 col">
                <!-------------------------------------速報----------------------------------->
                <article id="prompt_news" class="list_news">
                    <h2>速報記事</h2>
                    <?php $args = array(
                        'orderby'            => 'post_date',
                        'order'                => 'DESC',
                        'post_type'            => 'post',
                        'post_status'        => 'publish', 
                        'posts_per_page'    => '10'
                    );
                    $postlist = get_posts($args); ?>
                    <ul class="list_length shadow curve">
                        <?php foreach ($postlist as $post) : setup_postdata($post); ?>
                        <li>
                            <a href="<?php the_permalink(); ?>">
                                <b><?php the_title(); ?></b>
                                <div class="meta"><?php the_time('n/j'); ?></div>
                            </a>
                        </li>
                        <?php endforeach; ?>
                        <a class="more" href="<?php echo home_url('/'); ?>category">さらに読む</a>
                    </ul>
                </article>
                <!-----------------------------------------無料記事-------------------------------->
                <article id="freearticles">
                    <h2>無料公開記事</h2>
                    <?php $args = array(
                        'post_type' => array('sc','ost','eur','auto'),
                        'posts_per_page'    => 5,
                        'orderby'            => 'date',
                        'order'                => 'DESC',
                        'tax_query'            => array(
                            array(
                                'taxonomy'    => 'free_article',
                                'field'        => 'slug',
                                'terms'        => array('sc-free','ost-free','eur-free','auto-free')
                                )
                            )
                        ); 
                        $postlist = get_posts($args); ?>
                    <ul class="list_length shadow curve">
                        <?php foreach ($postlist as $post) : setup_postdata($post); ?>
                        <?php $kijitypelabel = get_post_type_object(get_post_type())->label; ?>
                        <li>
                            <a href="<?php the_permalink(); ?>">
                                <p class="title"><?php the_title(); ?></p>
                                <p class="meta"><?php echo $kijitypelabel ?> | <?php the_time('n/j'); ?>号</p>
                            </a>
                            <p><?php if (mb_strlen($post->post_content, 'UTF-8')>110) {
                                    $content= mb_substr($post->post_content, 0, 110, 'UTF-8'); ?>
                                    <?php echo $content ?><a ="<?php the_permalink(); ?>"><?php echo '… 続きを読む' ?></a>
                                <?php } else {echo $post->post_content; } ?>
                            </p>
                        </li>
                        <?php endforeach; ?>
                        <a class="more" href="<?php echo home_url('/'); ?>free_article">過去の無料記事を読む</a>
                    </ul>
                </article>
                <!-----------------------------------------コラム--------------------------------->
                <article id="read_column">
                    <h2>コラム集</h2>
                    <!-コラム取得->
                    <?php $args = array(
                        'post_type' => array('sc','ost','auto'),
                        'tax_query' => array(
                                'relation' => 'OR',
                                    array(
                                    'taxonomy'    => 'sc_sorte',
                                    'field'        => 'slug',
                                    'terms'        => 'mame'
                                ),
                                array(
                                    'taxonomy'    => 'ost_sorte',
                                    'field'        => 'slug',
                                    'terms'        => 'cafe'
                                ),
                                array(
                                    'taxonomy'    => 'auto_sorte',
                                    'field'        => 'slug',
                                    'terms'        => 'closeup'
                                )
                        ),
                        'orderby'                => 'post_date',
                        'order'                    => 'DESC',
                        'posts_per_page'        => 3
                    ); 
                    $postlist = get_posts($args); ?>
                    <ul class="list_length shadow curve">
                        <?php foreach ($postlist as $post) : setup_postdata($post); ?>
                        <?php $kijitype = esc_html(get_post_type_object(get_post_type())->name); ?>
                        <?php $kijitypelabel = get_post_type_object(get_post_type())->label; ?>
                        <li>
                            <a href="<?php the_permalink(); ?>">
                                <img class="inline" src="
                                <?php if ($kijitype == 'sc') : ?>
                                            <?php echo get_template_directory_uri(); ?>/img/topsc_cc.jpg" />
                                        <?php elseif ($kijitype == 'ost') : ?>
                                            <?php echo get_template_directory_uri(); ?>/img/topost.jpg" />
                                        <?php elseif ($kijitype == 'auto') : ?>
                                            <?php echo get_template_directory_uri(); ?>/img/topauto.jpg" />
                                        <?php endif; ?>
                                <p class="title inline"><?php the_title(); ?></p>
                                <p class="meta"><?php echo $kijitypelabel ?> | <?php the_time('n/j'); ?>号</p>
                            </a>
                            <p><?php if (mb_strlen($post->post_content, 'UTF-8')>80) {
                                    $content= mb_substr($post->post_content, 0, 80, 'UTF-8'); ?>
                                    <?php echo $content ?><a href="<?php the_permalink(); ?>"><?php echo ' … 続きを読む' ?></a>
                                <?php } else {echo $post->post_content; } ?>
                                
                            </p>
                        </li>
                        <?php endforeach; ?>
                        <a class="more" href="<?php echo home_url('/'); ?>archive-column">他のコラムも読む</a>
                    </ul>
                </article>
            </section>
            <!----------------------------グローバルサイドメニュー---------------------------->
            <?php wp_nav_menu(array('container_id' => 'globalSideMenu', 'theme_location' => 'globalMenu', 'depth' => 2, 'container_class' => 'col-md-3 d-none d-sm-block', 'menu_class' => 'shadow')); ?>
        </div>
    </div>
<?php endwhile; ?>
<?php endif; ?>
<div id="toTop"><a href="#header">▲このページのトップへ</a></div>
<?php get_footer(); ?>