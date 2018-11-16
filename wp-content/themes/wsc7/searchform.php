<?php /* WordPress CMS Theme WSC Project. */ ?>

<form role="search" method="get" class="searchform" action="<?php bloginfo('url'); ?>" >
<input type="text" value="" name="s" class="s searchbox"  value="<?php the_search_query(); ?>" />
<input type="submit" class="searchsubmit" value="検索" />
</form>


