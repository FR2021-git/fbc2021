<?php
/*
Plugin Name: Custom Post Type List Widget
Plugin URI: http://www.blogseye.com
Description: Displays a list of Custom Posts Type entries in a sidebar.
Author: Keith P. Graham
Version: 1.1
Requires at least: 3.0
Author URI: http://www.blogseye.com
Tested up to: 3.1

*/

class Widget_kpg_cpl extends WP_Widget {

   /** constructor */
    function Widget_kpg_cpl() {
        parent::WP_Widget(false, $name = 'Custom Post Type List Widget');	
    }


    /** @see WP_Widget::form */
    function form($instance) {				
		// outputs the options form on admin
		// this is the html to display the options
		
		// title, Post type, functionality, max lines, display type

		$title = esc_attr($instance['title']);
		$post_type = esc_attr($instance['post_type']);
		$orderby = esc_attr($instance['orderby']);
		$maxlines = esc_attr($instance['maxlines']);
		$pformat = esc_attr($instance['pformat']);
		$desc = esc_attr($instance['desc']);
		$trunc = esc_attr($instance['trunc']);
		if (empty($desc)||$desc=='false') $desc='false'; else $desc='true';
		if (empty($trunc)||$trunc=='false') $trunc='false'; else $trunc='true';
       ?>
	   
<fieldset style="border:thin black solid;padding:2px;"><legend>Include Post-Type description</legend>	
		<input class="widefat" id="<?php echo $this->get_field_id('desc'); ?>" name="<?php echo $this->get_field_name('desc'); ?>" type="checkbox" value="true" <?php if ($desc=='true') echo "checked=\"true\""; ?> />
</fieldset>
<fieldset style="border:thin black solid;padding:2px;"><legend>Shorten Long Titles</legend>	
		<input class="widefat" id="<?php echo $this->get_field_id('trunc'); ?>" name="<?php echo $this->get_field_name('trunc'); ?>" type="checkbox" value="true" <?php if ($trunc=='true') echo "checked=\"true\""; ?> />
</fieldset>
	   
<fieldset style="border:thin black solid;padding:2px;"><legend>Title:</legend>	
		<input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo $title; ?>" />
</fieldset>
<fieldset style="border:thin black solid;padding:2px;"><legend>Post Type:</legend>	
<select id="<?php echo $this->get_field_id('post_type'); ?>" name="<?php echo $this->get_field_name('post_type'); ?>">
 <?php
		$post_types=get_post_types('','names'); 
		
		$badtypes=array('nav_menu_item','revision','attachment'); // others????
		foreach ($post_types as $ptype ) {
			if (!in_array($ptype,$badtypes)) {
				echo "<option value=\"$ptype\" ";
				if ($post_type==$ptype) echo 'selected="true"';
				echo ">$ptype</option>";
			}
		}
?>
</select>	
</fieldset>	   
<fieldset style="border:thin black solid;padding:2px;"><legend>Count:</legend>	
<select id="<?php echo $this->get_field_id('maxlines'); ?>" name="<?php echo $this->get_field_name('maxlines'); ?>">
	<option value="all"<?php if ($maxlines=="all") echo ' selected="true"';  ?>>All posts</option>
	<option value="1"<?php if ($maxlines=="1") echo ' selected="true"';  ?>>1 post</option>
	<option value="4"<?php if ($maxlines=="4") echo ' selected="true"';  ?>>4 posts</option>
	<option value="5"<?php if ($maxlines=="5") echo ' selected="true"';  ?>>5 posts</option>
	<option value="6"<?php if ($maxlines=="6") echo ' selected="true"';  ?>>6 posts</option>
	<option value="7"<?php if ($maxlines=="7") echo ' selected="true"';  ?>>7 posts</option>
	<option value="8"<?php if ($maxlines=="8") echo ' selected="true"';  ?>>8 posts</option>
	<option value="9"<?php if ($maxlines=="9") echo ' selected="true"';  ?>>9 posts</option>
	<option value="10"<?php if ($maxlines=="10") echo ' selected="true"';  ?>>10 posts</option>
	<option value="15"<?php if ($maxlines=="15") echo ' selected="true"';  ?>>15 posts</option>
	<option value="20"<?php if ($maxlines=="20") echo ' selected="true"';  ?>>20 posts</option>
	<option value="day1"<?php if ($maxlines=="day1") echo ' selected="true"';  ?>>Today</option>
	<option value="day2"<?php if ($maxlines=="day2") echo ' selected="true"';  ?>>2 Days</option>
	<option value="day3"<?php if ($maxlines=="day3") echo ' selected="true"';  ?>>3 Days</option>
	<option value="day4"<?php if ($maxlines=="day4") echo ' selected="true"';  ?>>4 Days</option>
	<option value="day5"<?php if ($maxlines=="day5") echo ' selected="true"';  ?>>5 Days</option>
	<option value="day7"<?php if ($maxlines=="day7") echo ' selected="true"';  ?>>Last Week</option>
	<option value="day10"<?php if ($maxlines=="day10") echo ' selected="true"';  ?>>10 Days</option>
	<option value="day14"<?php if ($maxlines=="day14") echo ' selected="true"';  ?>>Last Fortnight</option>
	<option value="day30"<?php if ($maxlines=="day30") echo ' selected="true"';  ?>>Last 30 Days</option>
	<option value="day90"<?php if ($maxlines=="day90") echo ' selected="true"';  ?>>Last 90 Days</option>
</select>	
</fieldset>
<fieldset style="border:thin black solid;padding:2px;"><legend>Sort Order:</legend>	
<select id="<?php echo $this->get_field_id('orderby'); ?>" name="<?php echo $this->get_field_name('orderby'); ?>">
	<option value="menu_order, post_date"<?php if ($orderby=="menu_order, post_date") echo ' selected="true"';  ?>>Menu Order</option>
	<option value="post_date desc"<?php if ($orderby=="post_date desc") echo ' selected="true"';  ?>>Date (Newest first)</option>
	<option value="post_date"<?php if ($orderby=="post_date") echo ' selected="true"';  ?>>Date (oldest first)</option>
	<option value="upper(post_title)"<?php if ($orderby=="upper(post_title)") echo ' selected="true"';  ?>>Alphabetical Title</option>
	<option value="random"<?php if ($orderby=="random") echo ' selected="true"';  ?>>Random</option>
	<option value="comment_count desc,post_date desc"<?php if ($orderby=="comment_count desc,post_date desc") echo ' selected="true"';  ?>>Number of Comments</option>
	<option value="upper(post_author), upper(post_title)"<?php if ($orderby=="upper(post_author), upper(post_title)") echo ' selected="true"';  ?>>Author, Title</option>
	<option value="upper(post_author), post_date desc"<?php if ($orderby=="upper(post_author), post_date desc") echo ' selected="true"';  ?>>Author, Date (Newest first)</option>
	<option value="post_modified desc, post_date desc"<?php if ($orderby=="post_modified desc, post_date desc") echo ' selected="true"';  ?>>Recently Modified</option>
</select>	
</fieldset>

<fieldset style="border:thin black solid;padding:2px;"><legend>Format:</legend>	
<select id="<?php echo $this->get_field_id('pformat'); ?>" name="<?php echo $this->get_field_name('pformat'); ?>">
	<option value="ul"<?php if ($pformat=="ul") echo ' selected="true"';  ?>>Unordered List</option>
	<option value="drop"<?php if ($pformat=="drop") echo ' selected="true"';  ?>>Drop Down</option>
	<option value="br"<?php if ($pformat=="br") echo ' selected="true"';  ?>>BR line Breaks</option>
</select>	
</fieldset>

<?PHP
		// end of the functional section
	}

    /** @see WP_Widget::update */
    function update($new_instance, $old_instance) {				
		// processes widget options to be saved
		// have to update the new instance
		return $new_instance;
	}

    /** @see WP_Widget::widget */
    function widget($args, $instance) {	
		// start of the display section
		echo "\r\n<!-- Start of Custom Post Type List Widget -->\r\n";
		// outputs the content of the widget
		extract( $args );
		$title = esc_attr($instance['title']);
		$post_type = esc_attr($instance['post_type']);
		$orderby = esc_attr($instance['orderby']);
		$maxlines = esc_attr($instance['maxlines']);
		$pformat = esc_attr($instance['pformat']);
		$desc = esc_attr($instance['desc']);
		$trunc = esc_attr($instance['trunc']);
		$pdesc='';
		if ($desc=='true') {
			$obj=get_post_type_object($post_type);
			$pdesc=$obj->description;
		}
		

		if (empty($title)) $title='';
		if (empty($post_type)) $post_type='post';
		if (empty($orderby)) $pfunc='post_date desc';
		if (empty($maxlines)) $maxlines=-1;
		if (empty($pformat)) $pformat='ul';
		if (empty($desc)||$desc=='false') $desc='false'; else $desc='true';
		if (empty($trunc)||$trunc=='false') $trunc='false'; else $trunc='true';
		
		$sort=$orderby;
		if ($sort=='random') $sort='ID';
		// get posts seems to always work
		$datelimit='';
		if (substr($maxlines,0,3)=='day') $datelimit=date('Y-m-d',time()-(substr($maxlines,3)*24*60*60));
		$items=kpg_cpl_get_results($sort,$post_type,$datelimit);
		if (empty($items)) {
			//echo "\r\n\r\n found nothing for post type $post_type \r\n\r\n";
			return;
		}
		if ($orderby=='random') {
			shuffle($items);
		}
		
		// now go through the list
		$max=$maxlines;
		if ($maxlines=='all'||$maxlines==-1||substr($maxlines,0,3)=='day') {
			$max=count($items);
		}
		$out='';
		global $wp_query;
		$thePostID = $wp_query->post->ID;

		foreach ($items as $post) {
			$max--;
			if ($max<0) break;
			// post needs title, permalink and parent (if we are doing hierarchical)
			$post_title=$post->post_title;
			$comment_count=$post->comment_count;
			//$post_title=htmlentities($post_title);
			$ID=$post->ID;
			$cpi='';
			if ($thePostID==$ID) {
			   $cpi=' current_page_item';
			}
			$post_link=get_permalink($ID);
			$post_author=$post->post_author;
			$authordata = get_userdata( $post_author );
			$post_author=$authordata->display_name;
			if (empty($post_author)) $post_author=$authordata->user_nicename;
			if (empty($post_author)) $post_author='anonymous';
			if (strpos(' '.$orderby,'post_author')) $post_title=$post_author.': '.$post_title;
			if (strpos(' '.$orderby,'comment_count')) $post_title=$post_title.'('.$comment_count.')';
			if ($trunc=='true') {
				if (strlen($post_title)>35) $post_title=substr($post_title,0,32).'...';
			}
			switch($pformat) {
				case 'ul';
					$out.= "\r\n<li class=\"custom_post_list_item$cpi\"><a href=\"$post_link\" title=\"$post_title\" class=\"custom_post_item$cpi\" >$post_title</a></li>";
					break;
				case 'drop';
					$out.= "\r\n<option value=\"$post_link\">$post_title</option>";
					break;
				case 'br';
					$out.= "\r\n<a href=\"$post_link\" title=\"$post_title\" class=\"custom_post_item$cpi\" >$post_title</a><br/>";
					break;
				default;
					$out.= "\r\n<a href=\"$post_link\" title=\"$post_title\" class=\"custom_post_item$cpi\" >$post_title</a><br/>";
			}

			
		}
		if ( !empty( $out ) ) {
			echo $before_widget;
			if ( $title) {
				echo $before_title . $title . $after_title;
			}
			if ($desc=='true') echo '<br/>'.$pdesc;
			switch($pformat) {
				case 'ul';
					echo "<ul>";
					break;
				case 'drop';
					echo "<select class=\"custom_post_select\" onchange='document.location.href=this.options[this.selectedIndex].value;'>";
					echo "\r\n<option value=\"#\" selected=\"true\">Select Post</option>";
					break;
				case 'br';
					echo "<br/>";
					break;
				default;
					echo "<br/>";
			}
			// out goes out here;
			echo $out; 
			// close the ul or select or add a blank line
			switch($pformat) {
				case 'ul';
					echo "</ul>";
					break;
				case 'drop';
					echo "</select>";
					break;
				case 'br';
					echo "<br/>";
					break;
				default;
					echo "<br/>";
			}
			echo $after_widget;
		}
		echo "\r\n<!-- end of Custom Post Type List Widget -->\r\n";

	}

}
function kpg_cpl_sc($atts, $content=null) {
	extract( shortcode_atts( array(
		'title' => '',
		'post_type' => 'post_type',
		'pformat' => 'pformat',
		'trunc' => 'trunc',
		'orderby'=> 'post_date desc',
		'style' => '',
		'lstyle' => '',
		'astyle' => '',
		'count'=> -1
		), $atts ) );
		$sort=$orderby;
		$maxlines=$count;
		if ($sort=='random') $sort='ID';
		// get posts seems to always work
		$datelimit='';
		if (substr($maxlines,0,3)=='day') $datelimit=date('Y-m-d',time()-(substr($maxlines,3)*24*60*60));
	$items=kpg_cpl_get_results($sort,$post_type,$datelimit);
	$out='';
	if (empty($items)) return '<!-- no custom post shortcode results returned -->';
	if ($orderby=='random') {
		shuffle($items);
	}
	// now go through the list
	$max=$maxlines;
	if ($maxlines=='all'||$maxlines==-1||substr($maxlines,0,3)=='day') {
		$max=count($items);
	}
	global $wp_query;
	$thePostID = $wp_query->post->ID;

	foreach ($items as $post) {
		$max--;
		if ($max<0) break;
		// post needs title, permalink and parent (if we are doing hierarchical)
		$post_title=$post->post_title;
		$comment_count=$post->comment_count;
		//$post_title=htmlentities($post_title);
		$ID=$post->ID;
		$cpi='';
		if ($thePostID==$ID) {
		   $cpi=' current_page_item';
		}
		$post_link=get_permalink($ID);
		$post_author=$post->post_author;
		$authordata = get_userdata( $post_author );
		$post_author=$authordata->display_name;
		if (empty($post_author)) $post_author=$authordata->user_nicename;
		if (empty($post_author)) $post_author='anonymous';
		if (strpos(' '.$orderby,'post_author')) $post_title=$post_author.': '.$post_title;
		if (strpos(' '.$orderby,'comment_count')) $post_title=$post_title.'('.$comment_count.')';
		if ($trunc=='true') {
			if (strlen($post_title)>35) $post_title=substr($post_title,0,32).'...';
		}
		switch($pformat) {
			case 'ul';
				$out.= "\r\n<li class=\"custom_post_list_item$cpi\"><a href=\"$post_link\" title=\"$post_title\" class=\"custom_post_item$cpi\" >$post_title</a></li>";
				break;
			case 'drop';
				$out.= "\r\n<option value=\"$post_link\">$post_title</option>";
				break;
			case 'br';
				$out.= "\r\n<a href=\"$post_link\" title=\"$post_title\" class=\"custom_post_item$cpi\" >$post_title</a><br/>";
				break;
			default;
				$out.= "\r\n<a href=\"$post_link\" title=\"$post_title\" class=\"custom_post_item$cpi\" >$post_title</a><br/>";
		}

			
	}
	$pre='';
	$post='';
	if ( !empty( $out ) ) {
		if ( $title) {
			$pre.= '<h4>' . $title . '</h4>';
		}
		//if ($desc=='true') $pre.=.$pdesc;
		switch($pformat) {
			case 'ul';
				$pre.=  "<ul>";
				break;
			case 'drop';
				$pre.=  "<select class=\"custom_post_select\" onchange='document.location.href=this.options[this.selectedIndex].value;'>";
				$pre.=  "\r\n<option value=\"#\" selected=\"true\">Select Post</option>";
				break;
			case 'br';
				//$pre.=  "<br/>";
				break;
			default;
				//$pre.=  "<br/>";
		}
		// out goes out here;
		
		// close the ul or select or add a blank lin
		switch($pformat) {
			case 'ul';
				$post.= "</ul>";
				break;
			case 'drop';
				$post.= "</select>";
				break;
			case 'br';
				$post.= "<br/>";
				break;
			default;
				$post.= "<br/>";
		}
		
	}
	return $pre.$out.$post. "\r\n<!-- end of Custom Post Type List Widget Shortcode -->\r\n";

}
// moved out of class in order to be accessed by shortcodes
function kpg_cpl_get_results($sort,$post_type,$datelimit) {
	global $wpdb;
	$dd='';
	if (!empty($datelimit)) {
		//echo "<br/>$datelimit<br/>";
		$dd=" AND POST_DATE>DATE('$datelimit')" ;
	}
	$sql="SELECT ID,post_title,post_author,comment_count, post_date, menu_order, post_modified FROM ".$wpdb->posts." WHERE post_status = 'publish' $dd and post_type='$post_type' ORDER by $sort";
	//echo "<br/> $sql <br/>";
	
	$results=$wpdb->get_results($sql);
	return $results;
}


add_shortcode('custpost', 'kpg_cpl_sc');

// wp_get_archives(apply_filters('widget_archives_args', array('type' => 'monthly', 'show_post_count' => $c))); 
add_action('widgets_init', create_function('', 'return register_widget("Widget_kpg_cpl");'));

// add the options page, just to make sure it is working and include examples on how to make it work 

	add_action('admin_menu', 'kpg_cpl_sc_admin');	
	function kpg_cpl_sc_admin() {
		add_options_page('Custom Post List', 'Custom Post List', 'manage_options','custom_post_list','kpg_cpl_sc_control');
	}
	// options page
	function kpg_cpl_sc_control() {
		// documentation - html only	
?>
<div class="wrap">
<h2>Custom Post Type List Widget is installed and working correctly</h3>
<p>The Custom Post Type List Widget places a list of your custom post type entries in the sidebar widget, or a WordPress Shortcode can be included on any page or post.
</p><p>
This is similar to the standard Pages or Recent Posts widget, but with the option to display Custom Post Types.
</p><p>
All options work on standard Page and Post entries as well as custom post types. 
</p><p>
Note: This widget does not create custom post types. There are many good widgets to create and manage custom post types. Use a Custom Post Type Widget to create your custom post type before using this widget.
</p><p>
A Wordpress shorcode can placed anywhere on any page and it will be replaced by a list oof links to custom post types.
</p><p>
The format is: [custpost post_type="" trunc="" pformat"" title="" count="" orderby=""] <br/>
where:<br/>
  * post_type=post, page or a valid custom post type name<br/>
  * title=title above list<br/>
  * count=max number of items (use -1 to display all)<br/>
  * trunc=default true - shorten long titles to 32 characters, false use long titles  <br/>
  * pformat=post formatting options, either br (simple list), drop (dropdown list), ul (unordered list)<br/>
  * orderby=order of posts (valid sql order by clauses).<br/>
Valid order values:<br/>
    * post_date<br/>
    * post_date desc<br/>
    * upper(post_title)<br/>
    * comment_count desc,post_date desc<br/>
    * upper(post_author), upper(post_title)<br/>
    * upper(post_author), post_date desc<br/>
	* random - randmomize list each time displayed.<
</p><p>
Examples of shortcodes. On my blog I have a post type named "book-reviews". Standard post types include "post" or "page".
</p><p>
[custpost post_type="book-reviews" trunc="true" pformat="ul" title="Book Reviews"  orderby="comment_count desc,post_date desc"]
</p><p>
[custpost post_type="post" trunc="false" pformat="br" title="Random Post" count="4" orderby="random"]
</p><p>
[custpost post_type="page" trunc="true" pformat="drop" title="Recent Pages" count="6" orderby="post_date desc"]

</p>
<hr/>
<p>Keith Graham is also the Author a collection of Science Fiction Stories. If you find this plugin useful, you might like to to <strong>Buy My Book of Short Stories</strong>.</p>
<p><a href="http://www.amazon.com/gp/product/1456336584?ie=UTF8&tag=thenewjt30page&linkCode=as2&camp=1789&creative=390957&creativeASIN=1456336584">Error Message Eyes: A Programmer's Guide to the Digital Soul</a><img src="http://www.assoc-amazon.com/e/ir?t=thenewjt30page&l=as2&o=1&a=1456336584" width="1" height="1" border="0" alt="" style="border:none !important; margin:0px !important;" />
</p>
<p>A link on your blog to one of my personal sites would be appreciated.</p>
<p>Keith Graham</p>
<p>
<a href="http://www.blogseye.com" target="_blank">Blog&apos;s Eye</a> (My Wordpress Plugins and other PHP coding projects) <br />
<a href="http://www.cthreepo.com/blog" target="_blank">Wandering Blog</a>(My personal Blog) <br />
<a href="http://www.cthreepo.com" target="_blank">Resources for Science Fiction</a> (Writing Science Fiction) <br />
<a href="http://www.jt30.com" target="_blank">The JT30 Page</a> (Amplified Blues Harmonica) <br />
<a href="http://www.harpamps.com" target="_blank">Harp Amps</a> (Vacuum Tube Amplifiers for Blues) <br />
<a href="http://www.westnyackhoney.com" target="_blank">My Beekeeping site</a></p>
</div>

<?php
	
	}



