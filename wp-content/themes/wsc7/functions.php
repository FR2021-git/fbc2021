<?php /* WordPress CMS Theme WSC Project. */

/*load_theme_textdomain*/
load_theme_textdomain( 'wsc7', TEMPLATEPATH . '/languages'  );

/*register_sidebar
if(function_exists('register_sidebars'))
register_sidebars(7, array(
	'name' => 'side-widget-%d','wsc7',
	'id' => 'side-widget-area',
	'description' => __( 'side-widget', 'wsc7'),
    'before_widget' => '<nav id="%1$s" class="side-widget shadow">',
    'after_widget' => '</nav>',
    'before_title' => '<p class="widget-title">',
    'after_title' => '</p>',
) );*/


register_sidebar(array(2,
    'name' => __('footer-widget', 'wsc7'),
	'id' => 'footer-widget-area',
	'description' => __( 'footer-widget', 'wsc7'),
    'before_widget' => '<div id="%1$s" class="footer-widget">',
    'after_widget' => '</div>',
    'before_title' => '<p class="widget_title">',
    'after_title' => '</p>',
));


/*post-thumbnails*/
add_theme_support( 'post-thumbnails', array( 'post','information' ) );
set_post_thumbnail_size(60, 60 );

/*automatic-feed-links*/
add_theme_support( 'automatic-feed-links' );


/*content_width*/
if ( ! isset( $content_width ) ) $content_width = 640;


/*register_nav_menus*/
register_nav_menus( array(
	'header-menu' => __( 'header-menu', 'wsc7'),
	'footer-menu' => __( 'footer-menu', 'wsc7'),
) );

register_nav_menu('globalMenu', 'globalMenu');
register_nav_menu('globalMenuLogin', 'globalMenuLogin');

/*nav_menus description*/
function prefix_nav_description( $item_output, $item, $depth, $args ) {
 if ( !empty( $item->description ) ) {
 $item_output = str_replace( '">' . $args->link_before . $item->title, '">' . $args->link_before . '<strong>' . $item->title . '</strong>' . '<p class="menu-item-description">' . $item->description . '</p>' , $item_output );
 }
 return $item_output;
}
add_filter( 'walker_nav_menu_start_el', 'prefix_nav_description', 10, 4 );

/*add_editor_style*/
add_editor_style();

/*add_custom_background*/
add_custom_background();

/*add_custom_image_header*/
add_custom_image_header('','admin_header_style');
function admin_header_style() {}
define('NO_HEADER_TEXT',true);
define('HEADER_IMAGE','%s/img/header.png');
define('HEADER_IMAGE_WIDTH',900);
define('HEADER_IMAGE_HEIGHT',350);


global $my_archives_post_type;
add_filter( 'getarchives_where', 'my_getarchives_where', 10, 2 );
function my_getarchives_where( $where, $r ) {
  global $my_archives_post_type;
  if ( isset($r['post_type']) ) {
    $my_archives_post_type = $r['post_type'];
    $where = str_replace( '\'post\'', '\'' . $r['post_type'] . '\'', $where );
  } else {
    $my_archives_post_type = '';
  }
  return $where;
}
add_filter( 'get_archives_link', 'my_get_archives_link' );
function my_get_archives_link( $link_html ) {
  global $my_archives_post_type;
  if ( '' != $my_archives_post_type )
    $add_link .= '?post_type=' . $my_archives_post_type;
	$link_html = preg_replace("/href=\'(.+)\'\s/","href='$1".$add_link." '",$link_html);
 
  return $link_html;
}


function manage_posts_columns($columns) {
    $columns['published_date'] = "出版日";
    $columns['gou'] = "号数";
    return $columns;
}
function add_column($column_name, $post_id) {
    if( $column_name == 'published_date' ) {
        $stitle = get_post_meta($post_id, 'published_date', true);
    }
    if( $column_name == 'gou' ) {
        $stitle = get_post_meta($post_id, 'gou', true);
    }
    if ( isset($stitle) && $stitle ) {
        echo attribute_escape($stitle);
    } else {
        echo __('None');
    }
}
add_filter( 'manage_posts_columns', 'manage_posts_columns' );
add_action( 'manage_posts_custom_column', 'add_column', 10, 2 );


function kokki_like($atts) {
    $img = get_template_directory_uri();
    extract(shortcode_atts(array(
        'name' => 'ななし',
        'animal' => ''
    ), $atts));
    if ( $name == 'england' ) {
    $animal = '<img src="' . $img . '/img/kokki_england.png"</img>';
    return $animal;
    }
    if ( $name == 'deutschland' ) {
    $animal = '<img src="' . $img . '/img/kokki_deutschland.png"</img>';
    return $animal;
    }
    if ( $name == 'france' ) {
    $animal = '<img src="' . $img . '/img/kokki_france.png"</img>';
    return $animal;
    }
    if ( $name == 'rosia' ) {
    $animal = '<img src="' . $img . '/img/kokki_rosia.png"</img>';
    return $animal;
    }
    if ( $name == 'chech' ) {
    $animal = '<img src="' . $img . '/img/kokki_chech.png"</img>';
    return $animal;
    }
    if ( $name == 'italy' ) {
    $animal = '<img src="' . $img . '/img/kokki_italy.png"</img>';
    return $animal;
    }
    if ( $name == 'austria' ) {
    $animal = '<img src="' . $img . '/img/kokki_austria.png"</img>';
    return $animal;
    }
    if ( $name == 'netherlands' ) {
    $animal = '<img src="' . $img . '/img/kokki_netherlands.png"</img>';
    return $animal;
    }
    if ( $name == 'hungary' ) {
    $animal = '<img src="' . $img . '/img/kokki_hungary.png"</img>';
    return $animal;
    }else {
    $animal = '<img src="' . $img . '/img/kokki_eu.png"</img>';
    return $animal;
    }
}
add_shortcode('kokki', 'kokki_like');

/**
 * 検索の対象をタイトルのみにします。
 */
function posts_search_title_only( $orig_search, $query ) {
	if ( $query->is_search() && $query->is_main_query() && ! is_admin() ) {
		// 4.4, 4.5のWP_Query::parse_search()の処理を流用しています。(検索語の分割処理などはすでにquery_vars上にセット済のため省きます)
		global $wpdb;
		$search = '';

		$q = $query->query_vars;
		$n = ! empty( $q['exact'] ) ? '' : '%';
		$searchand = '';

		foreach ( $q['search_terms'] as $term ) {
			$include = '-' !== substr( $term, 0, 1 );
			if ( $include ) {
				$like_op  = 'LIKE';
				$andor_op = 'OR';
			} else {
				$like_op  = 'NOT LIKE';
				$andor_op = 'AND';
				$term     = substr( $term, 1 );
			}
			$like = $n . $wpdb->esc_like( $term ) . $n;
			// 検索対象をタイトルのみにします。
			$search .= $wpdb->prepare( "{$searchand}(($wpdb->posts.post_title $like_op %s))", $like );
			$searchand = ' AND ';
		}
		if ( ! empty( $search ) ) {
			$search = " AND ({$search}) ";
			if ( ! is_user_logged_in() )
				$search .= " AND ($wpdb->posts.post_password = '') ";
		}
		return $search;
	}
	else {
		return $orig_search;
	}
}
add_filter( 'posts_search', 'posts_search_title_only', 10, 2 );

function my_pre_comment_author_name($name)
{
        $user = wp_get_current_user();
        if ($user->ID && isset($_POST['author'])) {
                $name = trim(strip_tags($_POST['author']));
        }
        return $name;
}
add_filter('pre_comment_author_name', 'my_pre_comment_author_name');


add_filter('comment_form_default_fields', 'custom_comment_form_fields');
function custom_comment_form_fields($fields) {
	$commenter = wp_get_current_commenter();
	$req = get_option('require_name_email');
	$aria_req = ($req ? " aria-required='true'" : '');
	$fields =  array(
		'author' => '<p class="comment-form-author">' . '<label for="author">' . __( 'Name' ) . ( $req ? ' <span class="required">*</span>' : '' ) . '</label> ' .'<input id="author" name="author" type="text" value="' . esc_attr( $commenter['comment_author'] ) . '" size="30"' . $aria_req . ' /></p>',
		'email' => '<p class="comment-form-email"><label for="email">' . __( 'Email' ) . ( $req ? ' <span class="required">*</span>' : '' ) . '</label> ' . '<input id="email" name="email" ' . ( $html5 ? 'type="email"' : 'type="text"' ) . ' value="' . esc_attr(  $commenter['comment_author_email'] ) . '" size="30"' . $aria_req . ' /></p>',
		'url' => '',
	);
	return $fields;
}


add_filter('comment_form_defaults', 'custom_comment_form');
function custom_comment_form($args) {
	$args['comment_notes_after'] = '';
	return $args;
}

// オリジナル comment_form in wp-includes/comment-template.php
add_filter( "comment_form_defaults", "my_comment_form_defaults");
function my_comment_form_defaults($defaults){
//  'comment_field'        => '<p class="comment-form-comment"><label for="comment">' . _x( 'Comment', 'noun' ) . '</label><textarea id="comment" name="comment" cols="45" rows="8" aria-required="true"></textarea></p>',
    $defaults['comment_field'] = '<p class="comment-form-comment"><textarea id="comment" name="comment" cols="45" rows="4" aria-required="true" placeholder="' . _x( 'Comment', 'noun' ) . '"></textarea></p>';
    return $defaults;
}

/*固定ページで抜粋の機能を有効化*/
add_post_type_support('page','excerpt');

?>
