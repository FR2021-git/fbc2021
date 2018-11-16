<?php /* WordPress CMS Theme WSC Project. */ get_header(); ?>

<div id="wrap">
	<div id="main">
		<div class="content">
			<?php $post = $posts[0]; ?>
			<?php if (is_category()) { ?>
			<h1 class="pageTitle"><?php single_cat_title(); ?></h1>
			<?php } elseif( is_tag() ) { ?>
			<h1 class="pageTitle">Tag &#8216;<?php single_tag_title(); ?>&#8217;</h1>
			<?php } elseif (is_day()) { ?>
			<h1 class="pageTitle"><?php the_time(__('Y.n.j', 'wsc7')); ?></h1>
			<?php } elseif (is_month()) { ?>
			<h1 class="pageTitle"><?php the_time(__('Y.n', 'wsc7')); ?></h1>
			<?php } elseif (is_year()) { ?>
			<h1 class="pageTitle"><?php the_time(__('Y', 'wsc7')); ?></h1>
			<?php } elseif (is_author()) { ?>
			<h1 class="pageTitle">Author Archive</h1>
			<?php } elseif (isset($_GET['paged']) && !empty($_GET['paged'])) { ?>
			<h1 class="pageTitle">Archives</h1>
			<?php } elseif ( is_post_type_archive('sc_headline') )  { ?>
			<h1 class="pageTitle">ドイツ経済ウオッチャー過去ヘッドライン一覧</h1>
			<?php } elseif ( is_post_type_archive('ost_headline') )  { ?>
			<h1 class="pageTitle">東欧経済ウオッチャー過去ヘッドライン一覧</h1>
			<?php } elseif ( is_post_type_archive('eur_headline') )  { ?>
			<h1 class="pageTitle">欧州経済ウオッチャー過去ヘッドライン一覧</h1>
			<?php } elseif ( is_post_type_archive('auto_headline') )  { ?>
			<h1 class="pageTitle">自動車産業ニュース過去ヘッドライン一覧</h1>
			<?php } ?>

			<?php if (have_posts()) :  while (have_posts()) : the_post(); ?>

				<div id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
						<p class="entry-title"><a href="<?php the_permalink(); ?>"><?php the_date(); ?></a></p>
				</div>
			<?php endwhile; ?>

				<?php if(function_exists('wp_pagenavi')): ?>
					<?php wp_pagenavi(); ?>
				<?php else : ?>
					<div class="navigation cf">
						<div class="alignleft"><?php previous_posts_link(__( 'Previous page' )) ?></div>
						<div class="alignright"><?php next_posts_link(__( 'Next page' )) ?></div>
					</div>
				<?php endif; ?>

			<?php else : php endif; ?>

		</div>
	</div>

	<?php get_sidebar(); ?>
</div>
<?php get_footer(); ?>