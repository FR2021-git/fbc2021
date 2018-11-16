<?php /* WordPress CMS Theme WSC Project. */ ?>
<hr />

<div  id="comments">
<?php
	if ('comments.php' == basename($_SERVER['SCRIPT_FILENAME'])) die ('Please do not load this page directly. Thanks!');
	if (!empty($post->post_password)) {
	if ($_COOKIE['wp-postpass_' . COOKIEHASH] != $post->post_password) { ?>
	<p class="nocomments">This post is password protected. Enter the password to view comments.</p>
	<?php return; }}

	$oddcomment = 'class="alt" ';
?>

<?php if ($comments) : ?>
	<h3><?php _e('Comments','wsc7'); ?></h3>
			<ol class="commentlist">
				<?php wp_list_comments( array() ); ?>
			</ol>
<?php paginate_comments_links(); ?>

<?php else : ?>
<?php endif; ?>

<?php if ('open' == $post->comment_status) : ?>
<?php if ( get_option('comment_registration') && !$user_ID ) : ?>
<p><a href="<?php echo get_option('siteurl'); ?>/wp-login.php?redirect_to=<?php the_permalink(); ?>">login</a></p>
<?php else : ?>
<?php comment_form(); ?>
<?php endif; ?>
<?php endif; ?>
</div>