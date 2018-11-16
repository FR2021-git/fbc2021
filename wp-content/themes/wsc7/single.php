<?php /* WordPress CMS Theme WSC Project. */ get_header(); ?>

<!-ユーザー情報取得->
<?php global $current_user;
    $current_user= wp_get_current_user();
    $accounttype0 = $current_user->account_type[0];
    $accounttype1 = $current_user->account_type[1];
    $accounttype2 = $current_user->account_type[2];
    $accounttype3 = $current_user->account_type[3]; ?>

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
                        <p class="data"><?php the_time(__('Y.n.j', 'wsc7')); ?></p>
                    </div>
                    <!-記事本文->
                    <div class="kiji_text">

                        <?php $cats = get_the_category(); $cat = $cats[0]->slug; $catn = $cats[0]->name; $catnn =  mb_substr($catn, 0, mb_strlen($catn)- 2); ?>

                        <?php if($cat == 'scp' && $accounttype0 == 'sc' || $accounttype1 == 'sc' || $accounttype2 == 'sc' || $accounttype3 == 'sc') : ?>
                                <p><?php the_content(); ?></p>
                        <?php elseif ($cat == 'ostp' && $accounttype0 == 'ost' || $accounttype1 == 'ost' || $accounttype2 == 'ost' || $accounttype3 == 'ost') : ?>
                            <p><?php the_content(); ?></p>
                        <?php elseif ($cat == 'eurp' && $accounttype0 == 'eu' || $accounttype1 == 'eu' || $accounttype2 == 'eu' || $accounttype3 == 'eu') : ?>
                            <p><?php the_content(); ?></p>
                        <?php elseif ($cat == 'autop' && $accounttype0 == 'auto' || $accounttype1 == 'auto' || $accounttype2 == 'auto' || $accounttype3 == 'auto') : ?>
                            <p><?php the_content(); ?></p>
                        <?php else: ?>
                                <p><?php echo mb_substr(get_the_excerpt(), 0, 70); ?>[&hellip;]</p>
                                <p>この記事は、<?php echo $catnn; ?>ご購読のお客様がお読みいただけます。<br>閲覧するにはログインしてください。</p>
                                <form id="loginform" method="post" action="<?php echo home_url(); ?>/login/"> 
                                    <p><label>メールアドレス<input type="text" name="log" id="user_login" class="input imedisabled user_login" value="" tabindex="1" /></label></p>
                                    <p><label>パスワード<input type="password" name="pwd" id="user_pass" class="input user_pass" value="" tabindex="2" /></label></p>
                                    <p class="forgetmenot">
                                    <label><input name="rememberme" type="checkbox" id="rememberme" value="forever" tabindex="3" checked="checked" /> ログイン情報を記憶</label></p>
                                    <p class="submit">
                                    <input type="submit" name="wp-submit" id="wp-submit" class="submit login" value="ログイン &raquo;" tabindex="4" />
                                    <input type="hidden" name="redirect_to" value="<?php echo  (is_ssl() ? 'https' : 'http') . '://' . $_SERVER["HTTP_HOST"] . $_SERVER["REQUEST_URI"];?>" />
                                    <input type="hidden" name="testcookie" value="1" />
                                    </p>
                                </form>
                                <div class="free_monitor_in">
                                    <a href="<?php echo home_url(); ?>/business/newsletter/">ニューズレターの詳細情報はこちら</a>
                                </div>
                        <?php endif; ?>
                    </div>
                </section>
                <?php if (!isset($accounttype0, $accounttype1, $accounttype2, $accounttype3)) { ?>
                <h2>ニューズレター購読/無料トライアル申し込み</h2>
                <?php echo do_shortcode('[contact-form-7 id="1391" title="ニューズレター申し込み"]'); } ?>

                <section id="related" class="shadow curve">
                    <h2>速報</h2>
                    <?php $args = array(
                        'orderby'            => 'post_date',
                        'order'                => 'DESC',
                        'post_type'            => 'post',
                        'post_status'        => 'publish', 
                        'posts_per_page'    => '4'
                    );
                    $postlist = get_posts($args); ?>
                    <div class="yuzo_related_post style-3">
                        <?php foreach ($postlist as $post) : setup_postdata($post); ?>
                            <div class="relatedthumb yuzo-list">
                                <a class="link-list yuzo__text--title" href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <h2>無料記事</h2>
                    <?php $args = array(
                        'post_type' => array('sc','ost','eur','auto'),
                        'posts_per_page'    => 5,
                        'orderby'            => 'date',
                        'tax_query'            => array(
                            array(
                                'taxonomy'    => 'free_article',
                                'field'        => 'slug',
                                'terms'        => array ('sc-free', 'ost-free', 'eur-free', 'auto-free')
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