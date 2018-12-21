<?php /* WordPress CMS Theme WSC Project. */ get_header(); ?>

<div id="content" class="container-fluid">
  <div id="wrap">
    <div id="main" class="row" role="main" itemprop="mainContentOfPage" itemscope="itemscope" itemtype="http://schema.org/NewsArticle">
      <?php if (have_posts()) : while (have_posts()) : the_post(); ?>

        <!--ユーザー情報取得-->
        <?php
          global $current_user;
          $current_user = wp_get_current_user();
          $accounttype0 = $current_user->account_type[0];
          $accounttype1 = $current_user->account_type[1];
          $accounttype2 = $current_user->account_type[2];
          $accounttype3 = $current_user->account_type[3];
        ?>
        <!--カテゴリー取得-->
        <?php
          $category = get_the_category();
          $cat   = $category[0]->slug; 
          $catn  = $category[0]->cat_name;
          $catnl = mb_substr($catn, 0, mb_strlen($catn)-2, 'utf-8');
        ?>
        <?php 
          global $post;
          $cf = get_post_meta($post->ID);
          $facebook_page_url = '';
          $facebook_page_url = get_option('facebook_page_url');
          $post_cat = '';
        ?>

        <div class="col-md-1">
        </div >

        <div id="main_inner" class="col-md-8 col">

          <article id="post-<?php the_id(); ?>" <?php post_class(); ?> itemscope="itemscope" itemtype="http://schema.org/BlogPosting">
          
            <div class="breadcrumb-area">
              <div class="wrap">
                <?php if (function_exists('bcn_display')) { bcn_display(); } ?>
              </div>
            </div>

            <header class="post-header">
              <div class="cat-name">
                <span><?php echo $catn; ?></span>
              </div>
              <h1 class="post-title" itemprop="headline"><?php the_title(); ?></h1>
            </header>

            <div class="post-meta-area">
              <ul class="post-meta list-inline">
                <li class="date" itemprop="datePublished" datetime="<?php the_time('c');?>"><i class="fa fa-clock-o"></i> <?php the_time('Y.m.d'); ?></li>
              </ul>
              <ul class="post-meta-comment">
                <li class="comments">
                  <i class="fa fa-comments"></i> <span class="count"><?php comments_number('0', '1', '%'); ?></span>
                </li>
              </ul>
            </div>
            
            <?php if( get_the_post_thumbnail() ) : ?>
              <div class="post-thumbnail">
                <?php the_post_thumbnail(array(1200, 630, true)); ?>
              </div>
            <?php endif; ?>

            <section class="post-content" itemprop="ArticleBody">
              <?php
                $args = array(
                  'before' => '<div class="pagination">',
                  'after' => '</div>',
                  'link_before' => '<span>',
                  'link_after' => '</span>'
                );
              ?>

              <?php if($cat == 'scp' && $accounttype0 == 'sc' || $accounttype1 == 'sc' || $accounttype2 == 'sc' || $accounttype3 == 'sc') : ?>
                  <p><?php the_content(); ?></p>
                  <?php wp_link_pages($args); ?>
              <?php elseif ($cat == 'ostp' && $accounttype0 == 'ost' || $accounttype1 == 'ost' || $accounttype2 == 'ost' || $accounttype3 == 'ost') : ?>
                  <p><?php the_content(); ?></p>
                  <?php wp_link_pages($args); ?>
              <?php elseif ($cat == 'eurp' && $accounttype0 == 'eu' || $accounttype1 == 'eu' || $accounttype2 == 'eu' || $accounttype3 == 'eu') : ?>
                  <p><?php the_content(); ?></p>
                  <?php wp_link_pages($args); ?>
              <?php elseif ($cat == 'autop' && $accounttype0 == 'auto' || $accounttype1 == 'auto' || $accounttype2 == 'auto' || $accounttype3 == 'auto') : ?>
                  <p><?php the_content(); ?></p>
                  <?php wp_link_pages($args); ?>
              <?php else: ?>
                <p><?php echo mb_substr(get_the_excerpt(), 0, 70); ?>[&hellip;]</p>
                <p>この記事は、<?php echo $catnl; ?>ご購読のお客様がお読みいただけます。<br>閲覧するにはログインしてください。<br>
                <a href="<?php echo home_url(); ?>/business/newsletter/">ニューズレターのご購読のご案内はこちら</a></p>
                <form id="loginform" method="post" action="<?php echo home_url(); ?>/login/"> 
                  <table>
                    <tr>
                      <td><label>メールアドレス</label></td>
                      <td><input type="text" name="log" id="user_login" class="input imedisabled user_login" value="" tabindex="1" /></td>
                    </tr>
                    <tr>
                      <td><label>パスワード</label>
                      <td><input type="password" name="pwd" id="user_pass" class="input user_pass" value="" tabindex="2" /></td>
                    </tr>
                    <tr>
                      <td colspan="2">
                        <label class="forgetmenot"><input name="rememberme" type="checkbox" id="rememberme" value="forever" tabindex="3" checked="checked" /> ログイン情報を記憶</label>
                        <p class="submit">
                          <input type="submit" name="wp-submit" id="wp-submit" class="submit login" value="ログイン &raquo;" tabindex="4" />
                          <input type="hidden" name="redirect_to" value="<?php echo  (is_ssl() ? 'https' : 'http') . '://' . $_SERVER["HTTP_HOST"] . $_SERVER["REQUEST_URI"];?>" />
                          <input type="hidden" name="testcookie" value="1" />
                        </p>
                      </td>
                    </tr>
                  </table>
                </form>
              <?php endif; ?>
            </section>

            <?php $args = array(
                'orderby'            => 'post_date',
                'order'              => 'DESC',
                'post_type'          => 'post',
                'category_name'      =>  $cat,
                'post_status'        => 'publish', 
                'posts_per_page'     => '10'
            );
            $postlist = get_posts($args); ?>

            <footer class="post-footer">
              <ul class="post-footer-list">
                <li class="cat"><i class="fa fa-folder"></i><?php the_category(', '); ?>
                  <ul>
                    <?php foreach ($postlist as $post) : setup_postdata($post); ?>
                    <li>
                      <i class="fa fa-file"></i>
                      <a href="<?php the_permalink(); ?>">
                        <?php the_title(); ?>
                      </a>
                      <span class="meta">｜<?php the_time('n/j'); ?></span>
                    </li>
                    <?php endforeach; ?>
                  </ul>
                </li>
                <?php 
                  $posttags = get_the_tags();
                  if ($posttags) { ?>
                    <li class="tag"><i class="fa fa-tag"></i><?php the_tags(''); ?></li>
                <?php } ?>
              </ul>
            </footer>

            <?php if (!isset($accounttype0, $accounttype1, $accounttype2, $accounttype3)) { ?>
              <!-- CTA BLOCK -->
              <div class="post-cta">
                <h4 class="cta-post-title"><?php echo $catnl; ?> 申し込み</h4>
                <div class="post-cta-inner">
                  <div class="cta-post-content clearfix">
                    <div class="post-cta-cont">
                      <?php if ($cat=='scp') { ?>
                        <p>ヨーロッパ産業経済の中心地、ドイツの動向に特化した週刊情報誌です。</p>
                        <p>ドイツの経済・産業・社会情報、独・日系企業情報など。<br>
                        交通スト、暴風雨情報など、現地ならではの時事情報も配信します。</p>
                      <?php } elseif ($cat == 'ostp') { ?>
                        <p>中・東欧、CIS諸国、ロシアに特化した週刊情報誌です。<br>
                        入手しづらい中東欧の経済・産業・社会情報、企業の東欧での動向を現地から即時レポート。</p>
                      <?php } ?>
                      <p>6ヶ月 600 EURO / 12ヶ月 1000 EUR</p>
                      <table>
                        <tr>
                          <td>週刊ニューズレター</td>
                          <td>: 毎週水曜日にPDF配信・web公開</td>
                        </tr>
                        <tr>
                          <td>速報</td>
                          <td>: 毎日メール配信・web公開</td>
                        </tr>
                      </table>
                    </div>
                  </div>
                  <?php echo do_shortcode('[contact-form-7 id="1391" title="ニューズレター申し込み"]'); ?>
                </div>
              </div>
            <?php } ?>

          </article>
          <div id="toTop"><a href="#header">▲このページのトップへ</a></div>
        </div>
      <?php endwhile; ?>
      <?php endif; ?>

      <?php get_sidebar(); ?>

    </div><!-- /main -->
  </div><!-- /wrap -->
</div><!-- /content -->

<?php get_footer(); ?>