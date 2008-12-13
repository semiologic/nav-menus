<?php
/*
Plugin Name: Nav Menus
Plugin URI: http://www.semiologic.com/software/widgets/nav-menus/
Description: WordPress widgets that let you create navigation menus
Author: Denis de Bernardy
Version: 1.1
Author URI: http://www.getsemiologic.com
Update Service: http://version.semiologic.com/wordpress
Update Tag: nav_menus
Update Package: http://www.semiologic.com/media/software/widgets/nav-menus/nav-menus.zip
*/

/*
Terms of use
------------

This software is copyright Mesoconcepts Ltd, and is distributed under the terms of the Mesoconcepts license. In a nutshell, you may freely use it for any purpose, but may not redistribute it without written permission.

http://www.mesoconcepts.com/license/
**/


load_plugin_textdomain('nav-menus','wp-content/plugins/nav-menus');

class nav_menus
{
	#
	# init()
	#

	function init()
	{
		add_action('widgets_init', array('nav_menus', 'widgetize'));

		foreach ( array(
				'save_post',
				'delete_post',
				'switch_theme',
				'update_option_active_plugins',
				'update_option_show_on_front',
				'update_option_page_on_front',
				'update_option_page_for_posts',
				'generate_rewrite_rules',
				) as $hook)
		{
			add_action($hook, array('nav_menus', 'clear_cache'));
		}

		register_activation_hook(__FILE__, array('nav_menus', 'clear_cache'));
		register_deactivation_hook(__FILE__, array('nav_menus', 'clear_cache'));
	} # init()


	#
	# widgetize()
	#

	function widgetize()
	{
		$options = nav_menus::get_options();
		
		$widget_options = array('classname' => 'nav_menu', 'description' => __( "A navigation menu") );
		$control_options = array('width' => 500, 'id_base' => 'nav_menu');
		
		$id = false;

		# registered widgets
		foreach ( array_keys($options) as $o )
		{
			if ( !is_numeric($o) ) continue;
			$id = "nav_menu-$o";

			wp_register_sidebar_widget($id, __('Nav Menu'), array('nav_menus', 'display_widget'), $widget_options, array( 'number' => $o ));
			wp_register_widget_control($id, __('Nav Menu'), array('nav_menus_admin', 'widget_control'), $control_options, array( 'number' => $o ) );
		}
		
		# default widget if none were registered
		if ( !$id )
		{
			$id = "nav_menu-1";
			wp_register_sidebar_widget($id, __('Nav Menu'), array('nav_menus', 'display_widget'), $widget_options, array( 'number' => -1 ));
			wp_register_widget_control($id, __('Nav Menu'), array('nav_menus_admin', 'widget_control'), $control_options, array( 'number' => -1 ) );
		}
	} # widgetize()


	#
	# display_widget()
	#

	function display_widget($args, $widget_args = 1)
	{
		if ( is_numeric($widget_args) )
			$widget_args = array( 'number' => $widget_args );
		$widget_args = wp_parse_args( $widget_args, array( 'number' => -1 ) );
		extract( $widget_args, EXTR_SKIP );
		
		$number = intval($number);
		
		if ( is_page() )
		{
			$page_id = intval($GLOBALS['wp_query']->get_queried_object_id());
		}
		else
		{
			$page_id = false;
			if ( is_home() && !is_paged() )
			{
				$context = 'home';
			}
			elseif ( !is_search() && !is_404() )
			{
				$context = 'blog';
			}
			else
			{
				$context = 'search';
			}
		}
		
		# front end: serve cache if available
		if ( !is_admin() )
		{
			if ( is_page() )
			{
				if ( in_array(
						'_nav_menus_cache_' . $number,
						(array) get_post_custom_keys($page_id)
						)
					)
				{
					$cache = get_post_meta($page_id, '_nav_menus_cache_' . $number, true);
					echo $cache;
					return;
				}
			}
			else
			{
				$cache = get_option('nav_menus_cache');

				if ( isset($cache[$number][$context]) )
				{
					echo $cache[$number][$context];
					return;
				}
			}
		}
		
		# get options
		$options = nav_menus::get_options();
		$options = $options[$number];
		
		# admin area: serve a formatted title
		if ( is_admin() )
		{
			echo $args['before_widget']
				. $args['before_title'] . $options['title'] . $args['after_title']
				. $args['after_widget'];

			return;
		}
		
		# init
		global $wpdb;
		global $post_label;
		global $post_desc;
		$page_ids = array();
		static $ancestors;
		static $children = array();
		
		$exclude_sql = "
			SELECT	post_id
			FROM	$wpdb->postmeta
			WHERE	meta_key = '_widgets_exclude'
			";
		
		# all: fetch root page ids
		foreach ( $options['items'] as $key => $item )
		{
			if ( $item['type'] == 'page' )
			{
				$page_ids[] = intval($item['ref']);
			}
			elseif ( $item['type'] == 'home'
				&& get_option('show_on_front') == 'page'
				&& get_option('page_on_front')
				)
			{
				$page_ids[] = intval(get_option('page_on_front'));
			}
		}
		
		# all: fetch root page details
		if ( $page_ids )
		{
			$fetch_ids = array_diff($page_ids, array_keys((array) $post_label));
		}
		
		if ( $fetch_ids )
		{
			$fetch_ids_sql = implode(', ', $fetch_ids);
			
			$pages = (array) $wpdb->get_results("
				SELECT	posts.*,
						COALESCE(post_label.meta_value, post_title) as post_label,
						COALESCE(post_desc.meta_value, '') as post_desc
				FROM	$wpdb->posts as posts
				LEFT JOIN $wpdb->postmeta as post_label
				ON		post_label.post_id = posts.ID
				AND		post_label.meta_key = '_widgets_label'
				LEFT JOIN $wpdb->postmeta as post_desc
				ON		post_desc.post_id = posts.ID
				AND		post_desc.meta_key = '_widgets_desc'
				WHERE	post_type = 'page'
				AND		post_status = 'publish'
				AND		post_parent = 0
				AND		ID IN ( $fetch_ids_sql )
				");
			
			update_post_cache($pages);
			
			foreach ( $pages as $page )
			{
				$post_label[$page->ID] = $page->post_label;
				$post_desc[$page->ID] = $page->post_desc;
			}
		
			# catch invalid pages
			foreach ( $options['items'] as $key => $item )
			{
				if ( $item['type'] == 'page' && !isset($post_label[$item['ref']]) )
				{
					unset($options['items'][$key]);
				}
			}
		}
		
		# page: fetch ancestors
		if ( !$page_id )
		{
			$ancestors = array();
		}
		elseif ( !isset($ancestors) )
		{
			$ancestors = array($page_id);
			
			if ( !in_array($page_id, $page_ids) )
			{
				# current page is in the wp cache already
				$page = wp_cache_get($page_id, 'posts');
				
				if ( !isset($post_label[$page_id]) )
				{
					if ( $page_label = get_post_meta($page_id, '_widgets_label', true) )
					{
						$post_label[$page_id] = $page_label;
					}
					else
					{
						$post_label[$page_id] = $page->post_title;
					}
					
					$post_desc[$page_id] = get_post_meta($page_id, '_widgets_desc', true);
				}
				
				if ( $page->post_parent != 0 )
				{
					# traverse pages until we bump into the trunk
					do {
						$page = (object) $wpdb->get_row("
							SELECT	posts.*,
									COALESCE(post_label.meta_value, post_title) as post_label,
									COALESCE(post_desc.meta_value, '') as post_desc
							FROM	$wpdb->posts as posts
							LEFT JOIN $wpdb->postmeta as post_label
							ON		post_label.post_id = posts.ID
							AND		post_label.meta_key = '_widgets_label'
							LEFT JOIN $wpdb->postmeta as post_desc
							ON		post_desc.post_id = posts.ID
							AND		post_desc.meta_key = '_widgets_desc'
							WHERE	post_type = 'page'
							AND		post_status = 'publish'
							AND		ID = $page->post_parent
							");

						$pages = array($page);
						update_post_cache($pages);

						$post_label[$page->ID] = $page->post_label;
						$post_desc[$page->ID] = $page->post_desc;

						array_unshift($ancestors, $page->ID);
					} while ( $page->post_parent > 0 ); # > 0 to stop at unpublished pages if necessary
				}
			}
		}
		
		# page: fetch children
		if ( $page_id )
		{
			# fetch children of each ancestor
			foreach ( $ancestors as $ancestor_id )
			{
				if ( !isset($children[$ancestor_id]) )
				{
					$children[$ancestor_id] = array();
					
					$pages = (array) $wpdb->get_results("
						SELECT	posts.*,
								COALESCE(post_label.meta_value, post_title) as post_label,
								COALESCE(post_desc.meta_value, '') as post_desc
						FROM	$wpdb->posts as posts
						LEFT JOIN $wpdb->postmeta as post_label
						ON		post_label.post_id = posts.ID
						AND		post_label.meta_key = '_widgets_label'
						LEFT JOIN $wpdb->postmeta as post_desc
						ON		post_desc.post_id = posts.ID
						AND		post_desc.meta_key = '_widgets_desc'
						WHERE	post_type = 'page'
						AND		post_status = 'publish'
						AND		post_parent = $ancestor_id
						AND		ID NOT IN ( $exclude_sql )
						ORDER BY menu_order, LOWER(COALESCE(post_label.meta_value, post_title))
						");

					update_post_cache($pages);

					foreach ( $pages as $page )
					{
						$children[$ancestor_id][] = $page->ID;

						$post_label[$page->ID] = $page->post_label;
						$post_desc[$page->ID] = $page->post_desc;
					}
				}
			}
		}
		
		# all: fetch relevant children, in order to set the correct branch or leaf class
		$parent_ids = $page_ids;
		
		if ( $page_id )
		{
			foreach ( $ancestors as $ancestor_id )
			{
				$parent_ids = array_merge($parent_ids, $children[$ancestor_id]);
			}
		}
		
		$parent_ids = array_diff($parent_ids, array_keys($children));
		$parent_ids = array_unique($parent_ids);
		
		if ( $parent_ids )
		{
			$parent_ids_sql = implode(', ', $parent_ids);
			
			$pages = (array) $wpdb->get_results("
				SELECT	posts.*,
						COALESCE(post_label.meta_value, post_title) as post_label,
						COALESCE(post_desc.meta_value, '') as post_desc
				FROM	$wpdb->posts as posts
				LEFT JOIN $wpdb->postmeta as post_label
				ON		post_label.post_id = posts.ID
				AND		post_label.meta_key = '_widgets_label'
				LEFT JOIN $wpdb->postmeta as post_desc
				ON		post_desc.post_id = posts.ID
				AND		post_desc.meta_key = '_widgets_desc'
				WHERE	post_type = 'page'
				AND		post_status = 'publish'
				AND		post_parent IN ( $parent_ids_sql )
				AND		ID NOT IN ( $exclude_sql )
				ORDER BY menu_order, LOWER(COALESCE(post_label.meta_value, post_title))
				");

			update_post_cache($pages);

			foreach ( $pages as $page )
			{
				$children[$page->post_parent][] = $page->ID;

				$post_label[$page->ID] = $page->post_label;
				$post_desc[$page->ID] = $page->post_desc;
			}
		}
		
		$o = '';

		# fetch output
		if ( $options['items'] )
		{
			$o .= $args['before_widget'] . "\n"
				. ( $options['title']
					? ( $args['before_title'] . $options['title'] . $args['after_title'] . "\n" )
					: ''
					);

			$o .= '<ul>' . "\n";
			
			foreach ( $options['items'] as $item )
			{
				switch ( $item['type'] )
				{
				case 'url':
					$o .= nav_menus::display_url($item);
					break;
				
				case 'home':
					if ( get_option('show_on_front') != 'page' || !get_option('page_on_front') )
					{
						$o .= nav_menus::display_home($item);
						break;
					}
					else
					{
						$item['ref'] = get_option('page_on_front');
					}
				case 'page':
					$o .= nav_menus::display_page($item['ref'], $page_id, $ancestors, $children, $options['desc']);
					break;
				}
			}
			
			$o .= '</ul>' . "\n";
			
			$o .= $args['after_widget'] . "\n";
		}
		
		# cache
		if ( is_page() )
		{
			add_post_meta($page_id, '_nav_menus_cache_' . $number, $o, true);
		}
		else
		{
			$cache[$number][$context] = $o;
			update_option('nav_menus_cache', $cache);
		}

		# display
		echo $o;
	} # display_widget()
	
	
	#
	# display_url()
	#
	
	function display_url($item)
	{
		$classes = array();
		
		# process link
		$link = $item['label'];
		
		$link = '<a href="' . htmlspecialchars($item['ref']) . '">'
			. $link
			. '</a>';
		
		# process classes
		$classes[] = 'nav__' . preg_replace("/[^0-9a-z]+/i", "_", strtolower($item['label']));
		
		$classes = array_unique($classes);
		
		return '<li class="nav_leaf">'
			. '<span class="' . implode(' ', $classes) . '">'
			. $link
			. '</span>'
			. '</li>' . "\n";
	} # display_url()
	
	
	#
	# display_home()
	#
	
	function display_home($item)
	{
		$classes = array();
		
		# process link
		$link = $item['label'];
		
		if ( !( is_front_page() && !is_paged() ) )
		{
			$link = '<a href="' . htmlspecialchars(get_option('home')) . '">'
				. $link
				. '</a>';
		}
		
		# process classes
		$classes[] = 'nav__' . preg_replace("/[^0-9a-z]+/i", "_", strtolower($item['label']));
		
		if ( !is_page() && !is_search() && !is_404() )
		{
			$classes[] = 'nav_active';
		}
		
		$classes = array_unique($classes);
		
		return '<li class="nav_home">'
			. '<span class="' . implode(' ', $classes) . '">'
			. $link
			. '</span>'
			. '</li>' . "\n";
	} # display_home()


	#
	# display_page()
	#
	
	function display_page($item_id, $page_id, $ancestors, $children, $desc)
	{
		$is_home_page = ( get_option('show_on_front') == 'page' )
				&& ( get_option('page_on_front') == $item_id );
		$is_blog_page = ( get_option('show_on_front') == 'page' )
				&& ( get_option('page_for_posts') == $item_id );
		
		global $post_label;
		global $post_desc;
		$classes = array();
		
		# process link
		$link = $post_label[$item_id];
		
		if ( ( $page_id != $item_id )
			&& !( $is_blog_page && is_home() && !is_paged() )
			)
		{
			$link = '<a href="' . htmlspecialchars(get_permalink($item_id)) . '">'
				. $link
				. '</a>';
		}
		
		# process classes
		if ( $is_home_page )
		{
			$li_class = 'nav_home';
		}
		elseif ( $is_blog_page )
		{
			$li_class = 'nav_blog';
		}
		elseif ( $children[$item_id] )
		{
			$li_class = 'nav_branch';
		}
		else
		{
			$li_class = 'nav_leaf';
		}

		$classes[] = 'nav__' . preg_replace("/[^0-9a-z]+/i", "_", strtolower($post_label[$item_id]));
		
		if ( $page_id && in_array($item_id, $ancestors)
			|| $is_blog_page && !is_page()
			)
		{
			$classes[] = 'nav_active';
		}
		
		$classes = array_unique($classes);
		
		$o = '<li class="' . $li_class . '">'
			. '<span class="' . implode(' ', $classes) . '">'
			. $link
			. '</span>';
		
		if ( $desc && $post_desc[$item_id] )
		{
			$o .= wpautop($post_desc[$item_id]);
		}
		
		# display children if there are children and if that item is in the page's ancestors
		if ( $children[$item_id] && in_array($item_id, $ancestors) )
		{
			$o .= "\n" . '<ul>' . "\n";
			
			foreach ( $children[$item_id] as $child_id )
			{
				$o .= nav_menus::display_page($child_id, $page_id, $ancestors, $children, $desc);
			}
			
			$o .= '</ul>' . "\n";
		}
		
		$o .= '</li>' . "\n";
		
		return $o;
	} # display_page()


	#
	# clear_cache()
	#

	function clear_cache($in = null)
	{
		global $wpdb;
		
		update_option('nav_menus_cache', array());
		$wpdb->query("DELETE FROM $wpdb->postmeta WHERE meta_key LIKE '_nav_menus_cache%'");
		
		return $in;
	} # clear_cache()


	#
	# get_options()
	#

	function get_options()
	{
		if ( ( $o = get_option('nav_menus') ) === false )
		{
			$o = array();

			update_option('nav_menus', $o);
		}

		return $o;
	} # get_options()


	#
	# default_options()
	#

	function default_options()
	{
		return array(
			'title' => __('Browse'),
			'desc' => false,
			'items' => array(),
			);
	} # default_options()
} # nav_menus

nav_menus::init();


if ( is_admin() )
{
	include dirname(__FILE__) . '/nav-menus-admin.php';
}
?>