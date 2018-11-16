=== Custom Post Type List Widget ===
Tags: Custom Post Type, List, sidebar, widget
Stable tag: 1.1
Requires at least: 3.0
Tested up to: 3.1
Contributors: Keith P. Graham
Donate link: http://www.amazon.com/gp/product/1456336584?ie=UTF8&tag=thenewjt30page&linkCode=as2&camp=1789&creative=390957&creativeASIN=1456336584

Displays a list of Custom Posts Type entries in a sidebar.

== Description ==
The Custom Post Type List Widget places a list of your custom post type entries in the sidebar widget, or a WordPress Shortcode can be included on any page or post.

This is similar to the standard Pages or Recent Posts widget, but with the option to display Custom Post Types.

All options work on standard Page and Post entries as well as custom post types. 

Note: This widget does not create custom post types. There are many good widgets to create and manage custom post types. Use a Custom Post Type Widget to create your custom post type before using this widget.

The widget can appear multiple times in the sidebar for different Custom Post Types or different options.

== Installation ==

1. Download the plugin.
2. Upload the plugin to your wp-content/plugins directory.
3. Activate the plugin (all configuration is done through the widget).
4. Drag the widget to a sidebar.
5. Edit the sidebar title if needed.
6. Select the Custom Post Type
7. Select the count of lines to display. This can be All, a number, or a date period for recent entries.
8. Select sort order. This can be Alphabetically, Date, Comment Count, Author, Menu order, or random. Choosing random will select the posts randomly.
9. Enter the display type as unordered, dropdown List, or simple text with line breaks.
10. Save

You can alternatively include a shortcode on any page to show the list. The options are the same as for the widget.
[custpost post_type="" trunc="" pformat="" title="" count="" orderby=""] 
  * post_type=post, page or a valid custom post type
  * title=title above list
  * count=max number of items (use -1 to display all), also valid values are 'day' for today, 'day1', 'day2', etc. to display posts from the last n days.
  * trunc=default true - shorten long titles to 32 characters, false use long titles. 
  * pformat=post formatting options, either br (simple list), drop (dropdown list), ul (unordered list)
  * orderby=order of posts (valid sql order by clauses).
Valid order values:
    * post_date
    * post_date desc
    * upper(post_title)
    * comment_count desc,post_date desc
    * upper(post_author), upper(post_title)
    * upper(post_author), post_date desc
	* random - randmomize list each time displayed.


== Changelog ==

= 0.5 =
* initial test release

= 0.6 =
* Added ability to show Custom Post Type description. Added the ablity to show just one random post.

= 0.7 =
* Fixed a problem with drop downs unable to select first item.

= 0.8 =
* added shortcode. Made long title truncation optional.

= 0.9 =
* added current_page_item class if same page id as current page.

= 1.0 =
* fixed issue with current_page_class.

= 1.1 =
* Fixed short code count parameter



== Support ==
This plugin is in active development. All feedback is welcome on "<a href="http://www.blogseye.com/" title="Wordpress plugin: Custom Post Type List Widget">program development pages</a>".

If you find this plugin useful and you wish to support me, you can buy my book of short stories (cheap) <a href="http://www.amazon.com/gp/product/1456336584?ie=UTF8&tag=thenewjt30page&linkCode=as2&camp=1789&creative=390957&creativeASIN=1456336584">Error Message Eyes: A Programmer's Guide to the Digital Soul</a> 

At the very least please visit my websites and, if appropriate, add a link to one on your blog: 
<a href="http://www.blogseye.com" target="_blank">Blog&apos;s Eye</a> (My Wordpress Plugins and other PHP coding projects) <br />
<a href="http://www.cthreepo.com/blog" target="_blank">Wandering Blog </a>(My personal Blog) <br />
<a href="http://www.jt30.com" target="_blank">The JT30 Page</a> (Amplified Blues Harmonica) <br />
<a href="http://www.harpamps.com" target="_blank">Harp Amps</a> (Vacuum Tube Amplifiers for Blues) <br />
<a href="http://www.westnyackhoney.com" target="_blank">My Beekeeping site</a></p>


