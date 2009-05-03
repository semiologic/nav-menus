<?php
/*
Plugin Name: Nav Menus
Plugin URI: http://www.semiologic.com/software/nav-menus/
Description: WordPress widgets that let you create navigation menus
Version: 2.0 alpha
Author: Denis de Bernardy
Author URI: http://www.getsemiologic.com
*/

/*
Terms of use
------------

This software is copyright Mesoconcepts (http://www.mesoconcepts.com), and is distributed under the terms of the Mesoconcepts license. In a nutshell, you may freely use it for any purpose, but may not redistribute it without written permission.

http://www.mesoconcepts.com/license/
**/


load_plugin_textdomain('nav-menus', null, basename(dirname(__FILE__)) . '/lang');


/**
 * nav_menu
 *
 * @package Nav Menus
 **/

add_action('widgets_init', array('nav_menu', 'widgets_init'));
add_action('load-widgets.php', array('nav_menu', 'admin_init'));

class nav_menu extends WP_Widget {
	/**
	 * widgets_init()
	 *
	 * @return void
	 **/

	function widgets_init() {
		register_widget('nav_menu');
	} # widgets_init()
	
	
	/**
	 * admin_init()
	 *
	 * @return void
	 **/

	function admin_init() {
		add_action('admin_print_scripts', array('nav_menu', 'admin_print_scripts'));
		add_action('admin_print_styles', array('nav_menu', 'admin_print_styles'));
	} # admin_init()
	
	
	/**
	 * admin_print_scripts()
	 *
	 * @return void
	 **/

	function admin_print_scripts() {
		$folder = plugin_dir_url(__FILE__) . 'js/';
		wp_enqueue_script('jquery-livequery', $folder . 'jquery.livequery.js', array('jquery'),  '1.1', true);
		wp_enqueue_script( 'nav-menus', $folder . 'admin.js', array('jquery-ui-sortable', 'jquery-livequery'),  '20090502', true);
	} # admin_print_scripts()
	
	
	/**
	 * admin_print_styles()
	 *
	 * @return void
	 **/

	function admin_print_styles() {
		$folder = plugin_dir_url(__FILE__) . 'css/';
		wp_enqueue_style('nav-menus', $folder . 'admin.css', null, '20090422');
	} # admin_print_styles()
	
	
	/**
	 * nav_menu()
	 *
	 * @return void
	 **/

	function nav_menu() {
		$widget_ops = array(
			'classname' => 'nav_menu',
			'description' => __("A navigation menu", 'nav-menus'),
			);
		$control_ops = array(
			'width' => 330,
			);
		
		$this->WP_Widget('nav_menu', __('Nav Menu', 'nav-menus'), $widget_ops, $control_ops);
	} # nav_menu()
	
	
	/**
	 * widget()
	 *
	 * @param array $args widget args
	 * @param array $instance widget options
	 * @return void
	 **/

	function widget($args, $instance) {
		extract($args);
		$instance = wp_parse_args($instance, nav_menu::defaults());
		extract($instance, EXTR_SKIP);
		
		if ( is_admin() ) {
			echo $before_widget
				. $before_title . $title . $after_title
				. $after_widget;
			return;
		} elseif ( $o = wp_cache_get($widget_id, 'widget') ) {
			echo $o;
			return;
		} elseif ( !$items ) {
			wp_cache_set($widget_id, '', 'widget');
			return;
		}
		
		ob_start();
		
		nav_menu::cache_pages();
		
		echo $before_widget;
		
		if ( $title )
			echo $before_title . $title . $after_title;
		
		echo '<ul>' . "\n";
		
		foreach ( $items as $item ) {
			switch ( $item['type'] ) {
			case 'home':
				nav_menu::display_home($item['label']);
				break;
			case 'url':
				nav_menu::display_url($item);
				break;
			case 'page':
				nav_menu::display_page($item['ref']);
				break;
			}
		}
		
		echo '</li>';
		
		echo $after_widget;
		
		$o = ob_get_clean();
		
		#wp_cache_set($widget_id, $o, 'widget');
		
		echo $o;
	} # widget()
	
	
	/**
	 * display_home()
	 *
	 * @param string $label
	 * @return void
	 **/

	function display_home($label) {
		$url = user_trailingslashit(get_option('home'));
		$classes = array('nav_home');
		
		if ( !is_page() )
			$classes[] = 'nav_active';
		
		echo '<li class="' . implode(' ', $classes) . '">'
			. '<a href="' . clean_url($url) . '" title="' . attr(get_option('blogname')) . '">'
			. $label
			. '</a>'
			. '</li>' . "\n";
	} # display_home()
	
	
	/**
	 * display_url()
	 *
	 * @param array $item
	 * @return void
	 **/

	function display_url($item) {
		extract($item, EXTR_SKIP);
		$classes = array('nav_url');
		
		echo '<li class="' . implode(' ', $classes) . '">'
			. '<a href="' . clean_url($url) . '" title="' . attr($label) . '">'
			. $label
			. '</a>'
			. '</li>' . "\n";
	} # display_url()
	
	
	/**
	 * display_page()
	 *
	 * @param int $ref
	 * @return void
	 **/

	function display_page($ref) {
		$page = get_page($ref);
		
		if ( !$page )
			return;
		
		if ( is_page() ) {
			global $wp_the_query;
			$page_id = $wp_the_query->get_queried_object_id();
		} else {
			$page_id = 0;
		}
		
		$label = get_post_meta('_widgets_label', $page->ID, true);
		if ( !$label )
			$label = $page->post_title;
		
		$url = get_permalink($page->ID);
		
		$ancestors = wp_cache_get('page_ancestors_' . $page_id, 'widget');
		$children = wp_cache_get('page_children_' . $page->ID, 'widget');
		
		$classes = array("nav_page-$page->ID");
		if ( $children )
			$classes[] = 'nav_branch';
		else
			$classes[] = 'nav_leaf';
		
		echo '<li class="' . implode(' ', $classes) . '">'
			. '<a href="' . clean_url($url) . '" title="' . attr($label) . '"'
				. ( $page->ID == $page_id || in_array($page->ID, $ancestors)
					? ' class="nav_active"'
					: ''
					)
				. '>'
			. $label
			. '</a>';
		
		if ( $children ) {
			echo "\n"
				. '<ul>' . "\n";
			foreach ( $children as $child ) {
				nav_menu::display_page($child);
			}
			echo '</ul>' . "\n";
		}
		
		echo '</li>' . "\n";
	} # display_page()
	
	
	/**
	 * cache_pages()
	 *
	 * @return void
	 **/

	function cache_pages() {
		global $wpdb;
		global $wp_the_query;
		
		if ( is_page() ) {
			$page_id = $wp_the_query->get_queried_object_id();
			$page = get_page($page_id);
		} else {
			$page_id = 0;
			$page = null;
		}
		
		$ancestors = wp_cache_get('page_ancestors_' . $page_id, 'widget');
		if ( $ancestors === false ) {
			$ancestors = array();
			while ( $page && $page->post_parent != 0 ) {
				$ancestors[] = (int) $page->post_parent;
				$page = get_page($page->post_parent);
			}
			$ancestors = array_reverse($ancestors);
			wp_cache_set('page_ancestors_' . $page_id, $ancestors, 'widget');
		}
		
		array_unshift($ancestors, 0);
		if ( $page_id )
			$ancestors[] = $page_id;
		
		$cached = true;
		foreach ( $ancestors as $ancestor ) {
			$cached = is_array(wp_cache_get('page_children_' . $ancestor, 'widget'));
			if ( $cached === false )
				break;
		}
		
		if ( !$cached ) {
			$roots = (array) $wpdb->get_col("
				SELECT	posts.ID
				FROM	$wpdb->posts as posts
				WHERE	posts.post_type = 'page'
				AND		posts.post_parent = 0
				");
			
			$pages = array();
			$parent_ids = array_merge($roots, $ancestors, array($page_id));
			$parent_ids = array_unique($parent_ids);
			
			$children = array();
			$to_cache = array();
			
			if ( $parent_ids ) {
				$pages = (array) $wpdb->get_results("
					SELECT	posts.*
					FROM	$wpdb->posts as posts
					WHERE	posts.post_type = 'page'
					AND		posts.post_parent IN ( " . implode(',', $parent_ids) . " )
					ORDER BY posts.menu_order, posts.post_title
					");
				update_post_cache($pages);
			}
			
			foreach ( $parent_ids as $parent_id )
				$children[$parent_id] = array();
			
			foreach ( $pages as $page ) {
				$children[$page->post_parent][] = $page->ID;
				$to_cache[] = $page->ID;
			}
			
			foreach ( $children as $parent => $childs ) {
				wp_cache_set('page_children_' . $parent, $childs, 'widget');
			}
			
			update_postmeta_cache($to_cache);
		}
	} # cache_pages()
	
	
	/**
	 * update()
	 *
	 * @param array $new_instance new widget options
	 * @param array $old_instance old widget options
	 * @return array $instance
	 **/

	function update($new_instance, $old_instance) {
		$instance = nav_menu::defaults();
		$instance['title'] = strip_tags($new_instance['title']);
		$instance['desc'] = isset($new_instance['desc']);
		foreach ( array_keys((array) $new_instance['items']['type']) as $key ) {
			$item = array();
			$item['type'] = $new_instance['items']['type'][$key];
			
			if ( !in_array($item['type'], array('home', 'url', 'page')) ) {
				continue;
			}
			
			$label = trim(strip_tags(stripslashes($new_instance['items']['label'][$key])));
			
			switch ( $item['type'] ) {
				case 'home':
					$item['label'] = $label;
					break;
				case 'url':
					$item['ref'] = trim(strip_tags(stripslashes($new_instance['items']['ref'][$key])));
					$item['label'] = $label;
					break;
				case 'page':
					$item['ref'] = intval($new_instance['items']['ref'][$key]);
					$page = get_post($item['ref']);
					if ( $page->post_title != $label ) {
						update_post_meta($item['ref'], '_widgets_label', $label);
					} else {
						delete_post_meta($item['ref'], '_widgets_label');
					}
					break;
			}
			
			$instance['items'][] = $item;
		}
		
		return $instance;
	} # update()
	
	
	/**
	 * form()
	 *
	 * @param array $instance widget options
	 * @return void
	 **/

	function form($instance) {
		$instance = wp_parse_args($instance, nav_menu::defaults());
		static $pages;
		
		if ( !isset($pages) ) {
			global $wpdb;
			$pages = $wpdb->get_results("
				SELECT	posts.*,
						COALESCE(post_label.meta_value, post_title) as post_label
				FROM	$wpdb->posts as posts
				LEFT JOIN $wpdb->postmeta as post_label
				ON		post_label.post_id = posts.ID
				AND		post_label.meta_key = '_widgets_label'
				WHERE	posts.post_type = 'page'
				AND		posts.post_status = 'publish'
				AND		posts.post_parent = 0
				ORDER BY posts.menu_order, posts.post_title
				");
			update_post_cache($pages);
		}
		
		extract($instance, EXTR_SKIP);
		
		echo '<h3>' . __('Config', 'nav-menus') . '</h3>' . "\n";
		
		echo '<p>'
			. '<label>'
			. __('Title:', 'nav-menus') . '<br />' . "\n"
			. '<input type="text" class="widefat"'
				. ' name="' . $this->get_field_name('title') . '"'
				. ' value="' . attribute_escape($title) . '"'
				. ' />'
			. '</p>' . "\n";
		
		echo '<p>'
			. '<label>'
			. '<input type="checkbox"'
				. ' name="' . $this->get_field_name('desc') . '"'
				. checked($desc, true, false)
				. ' />'
			. '&nbsp;'
			. __('Show Descriptions', 'nav-menus') . "\n"
			. '</p>' . "\n";
		
		echo '<h3>' . __('Menu Items') . '</h3>' . "\n";
		
		echo '<p>'
			. 'Drag and drop menu items to rearrange them.'
			. '</p>' . "\n";
		
		echo '<div class="nav_menu_items">' . "\n";
		
		echo '<div class="nav_menu_items_controller">' . "\n";
		
		echo '<select class="nav_menu_item_select"'
			. ' name="' . $this->get_field_name('dropdown') . '">' . "\n"
			. '<option value="">'
				. attribute_escape(__('- Select a menu item -', 'nav-menus'))
				. '</option>' . "\n"
			. '<optgroup label="' . attribute_escape(__('Special', 'nav-menu')) . '">' . "\n"
			. '<option value="home" class="nav_menu_item_home">'
				. __('Home', 'nav-menu')
				. '</option>' . "\n"
			. '<option value="url" class="nav_menu_item_url">'
				. __('Url', 'nav-menu')
				. '</option>' . "\n"
			. '</optgroup>' . "\n"
			. '<optgroup class="nav_menu_item_pages"'
				. ' label="' . attribute_escape(__('Pages', 'nav-menu')) . '"'
				. '>' . "\n"
			;
		
		foreach ( $pages as $page ) {
			echo '<option value="page-' . $page->ID . '">'
				. attribute_escape($page->post_label)
				. '</option>' . "\n";
		}
		
		echo '</optgroup>' . "\n";
		
		echo '</select>';
		
		echo '&nbsp;';
		
		echo '<input type="button" class="nav_menu_item_add" value="&nbsp;+&nbsp;" />' . "\n";
		
		echo '</div>' . "\n"; # controller
		
		echo '<div class="nav_menu_item_defaults" style="display: none;">' . "\n";
		
		echo '<div class="nav_menu_item_blank">' . "\n"
			. '<p>' . __('Empty Navigation Menu', 'nav-menus') . '</p>' . "\n"
			. '</div>' . "\n";
		
		$default_items = array(
			array(
				'type' => 'home',
				'label' => __('Home', 'nav-menus'),
				),
			array(
				'type' => 'url',
				'ref' => 'http://',
				'label' => __('Url Label', 'nav-menus'),
				),
			);
		
		foreach ( $pages as $page ) {
			$default_items[] = array(
				'type' => 'page',
				'ref' => $page->ID,
				'label' => $page->post_label,
				);
		}
		
		foreach ( $default_items as $item ) {
			$label = $item['label'];
			$type = $item['type'];
			switch ( $type ) {
			case 'home':
				$ref = 'home';
				$url = user_trailingslashit(get_option('home'));
				$handle = 'home';
				break;
			case 'url':
				$ref = $item['ref'];
				$url = $ref;
				$handle = 'url';
				break;
			case 'page':
				$ref = $item['ref'];
				$url = get_permalink($ref);
				$handle = 'page-' . $ref;
				$page = get_post($ref);
				$label = $page->post_label;
				break;
			}
			
			echo '<div class="nav_menu_item nav_menu_item-' . $handle . ' button">' . "\n"
				. '<div class="nav_menu_item_data">' ."\n"
				. '<input type="text" class="nav_menu_item_label" disabled="disabled"'
					. ' name="' . $this->get_field_name('items') . '[label][]"'
					. ' value="' . attribute_escape($label) . '"'
					. ' />' . "\n"
				. '&nbsp;'
				. '<input type="button" class="nav_menu_item_remove" disabled="disabled"'
					. ' value="&nbsp;-&nbsp;" />' . "\n"
				. '<input type="hidden" disabled="disabled"'
					. ' class="nav_menu_item_type"'
					. ' name="' . $this->get_field_name('items') . '[type][]"'
					. ' value="' . $type . '"'
					. ' />' . "\n"
				. '<input type="' . ( $handle == 'url' ? 'text' : 'hidden' ) . '" disabled="disabled"'
					. ' class="nav_menu_item_ref"'
					. ' name="' . $this->get_field_name('items') . '[ref][]"'
					. ' value="' . $ref . '"'
					. ' />' . "\n"
				. '</div>' . "\n" # data
				. '<div class="nav_menu_item_preview">' . "\n"
				. '&rarr;&nbsp;<a href="' . htmlspecialchars($url) . '"'
					. ' onclick="window.open(this.href); return false;">'
					. $label
					. '</a>'
				. '</div>' . "\n" # preview
				. '</div>' . "\n"; # item
		}
		
		echo '</div>' . "\n"; # defaults
		
		echo '<div class="nav_menu_item_sortables">' . "\n";
		
		// autofill empty widgets with default items
		if ( !$items )
			$items = $default_items;
		
		foreach ( $items as $item ) {
			$label = $item['label'];
			$type = $item['type'];
			switch ( $type ) {
			case 'home':
				$ref = 'home';
				$url = user_trailingslashit(get_option('home'));
				$handle = 'home';
				break;
			case 'url':
				$ref = $item['ref'];
				$url = $ref;
				$handle = 'url';
				break;
			case 'page':
				$ref = $item['ref'];
				$url = get_permalink($ref);
				$handle = 'page-' . $ref;
				$page = get_post($ref);
				$label = $page->post_label;
				break;
			}
			
			echo '<div class="nav_menu_item nav_menu_item-' . $handle . ' button">' . "\n"
				. '<div class="nav_menu_item_data">' ."\n"
				. '<input type="text" class="nav_menu_item_label"'
					. ' name="' . $this->get_field_name('items') . '[label][]"'
					. ' value="' . attribute_escape($label) . '"'
					. ' />' . "\n"
				. '&nbsp;'
				. '<input type="button" class="nav_menu_item_remove" value="&nbsp;-&nbsp;" />' . "\n"
					. '<input type="hidden"'
						. ' class="nav_menu_item_type"'
						. ' name="' . $this->get_field_name('items') . '[type][]"'
						. ' value="' . $type . '"'
						. ' />' . "\n"
				. '<input type="' . ( $handle == 'url' ? 'text' : 'hidden' ) . '"'
					. ' class="nav_menu_item_ref"'
					. ' name="' . $this->get_field_name('items') . '[ref][]"'
					. ' value="' . $ref . '"'
					. ' />' . "\n"
				. '</div>' . "\n" # data
				. '<div class="nav_menu_item_preview">' . "\n"
				. '&rarr;&nbsp;<a href="' . htmlspecialchars($url) . '"'
					. ' onclick="window.open(this.href); return false;">'
					. $label
					. '</a>'
				. '</div>' . "\n" # preview
				. '</div>' . "\n"; # item
		}
		
		if ( !$items ) {
			echo '<div class="nav_menu_item_blank">' . "\n"
				. '<p>' . __('Empty Navigation Menu', 'nav-menus') . '</p>' . "\n"
				. '</div>' . "\n";
		}
		
		echo '</div>' . "\n"; # sortables
		
		echo '</div>' . "\n"; # items
	} # form()
	
	
	/**
	 * defaults()
	 *
	 * @return array $instance default options
	 **/

	function defaults() {
		return array(
			'title' => __('Browse', 'nav-menus'),
			'desc' => false,
			'items' => array(),
			);
	} # defaults()
} # nav_menu




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
				'update_option_sidebars_widgets',
				'update_option_sem5_options',
				'update_option_sem6_options',
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
		$link = ( $item['label'] ? $item['label'] : __('Untitled') );
		
		$link = '<a href="' . htmlspecialchars($item['ref']) . '">'
			. $link
			. '</a>';
		
		# process classes
		$classes[] = 'nav__' . preg_replace("/[^0-9a-z]+/i", "_", strtolower($item['label']));
		
		$classes = array_unique($classes);
		
		static $site_domain;
		
		if ( !isset($site_domain) )
		{
			$site_domain = get_option('home');
			$site_domain = parse_url($site_domain);
			$site_domain = $site_domain['host'];
			
			if ( $site_domain != 'localhost' && !preg_match("/\d+(\.\d+){3}/", $site_domain) )
			{
				$tlds = array('wattle.id.au', 'emu.id.au', 'csiro.au', 'name.tr', 'conf.au', 'info.tr', 'info.au', 'gov.au', 'k12.tr', 'lel.br', 'ltd.uk', 'mat.br', 'jor.br', 'med.br', 'net.hk', 'net.eg', 'net.cn', 'net.br', 'net.au', 'mus.br', 'mil.tr', 'mil.br', 'net.lu', 'inf.br', 'fnd.br', 'fot.br', 'fst.br', 'g12.br', 'gb.com', 'gb.net', 'gen.tr', 'ggf.br', 'gob.mx', 'gov.br', 'gov.cn', 'gov.hk', 'gov.tr', 'idv.tw', 'imb.br', 'ind.br', 'far.br', 'net.mx', 'se.com', 'rec.br', 'qsl.br', 'psi.br', 'psc.br', 'pro.br', 'ppg.br', 'pol.tr', 'se.net', 'slg.br', 'vet.br', 'uk.net', 'uk.com', 'tur.br', 'trd.br', 'tmp.br', 'tel.tr', 'srv.br', 'plc.uk', 'org.uk', 'ntr.br', 'not.br', 'nom.br', 'no.com', 'net.uk', 'net.tw', 'net.tr', 'net.ru', 'odo.br', 'oop.br', 'org.tw', 'org.tr', 'org.ru', 'org.lu', 'org.hk', 'org.cn', 'org.br', 'org.au', 'web.tr', 'eun.eg', 'zlg.br', 'cng.br', 'com.eg', 'bio.br', 'agr.br', 'biz.tr', 'cnt.br', 'art.br', 'com.hk', 'adv.br', 'cim.br', 'com.mx', 'arq.br', 'com.ru', 'com.tr', 'bmd.br', 'com.tw', 'adm.br', 'ecn.br', 'edu.br', 'etc.br', 'eng.br', 'esp.br', 'com.au', 'com.br', 'ato.br', 'com.cn', 'eti.br', 'edu.au', 'bel.tr', 'edu.tr', 'asn.au', 'jl.cn', 'mo.cn', 'sh.cn', 'nm.cn', 'js.cn', 'jx.cn', 'am.br', 'sc.cn', 'sn.cn', 'me.uk', 'co.jp', 'ne.jp', 'sx.cn', 'ln.cn', 'co.uk', 'co.at', 'sd.cn', 'tj.cn', 'cq.cn', 'qh.cn', 'gs.cn', 'gr.jp', 'dr.tr', 'ac.jp', 'hb.cn', 'ac.cn', 'gd.cn', 'pp.ru', 'xj.cn', 'xz.cn', 'yn.cn', 'av.tr', 'fm.br', 'fj.cn', 'zj.cn', 'gx.cn', 'gz.cn', 'ha.cn', 'ah.cn', 'nx.cn', 'tv.br', 'tw.cn', 'bj.cn', 'id.au', 'or.at', 'hn.cn', 'ad.jp', 'hl.cn', 'hk.cn', 'ac.uk', 'hi.cn', 'he.cn', 'or.jp', 'name', 'info', 'aero', 'com', 'net', 'org', 'biz', 'edu', 'int', 'mil', 'ua', 'st', 'tw', 'sg', 'uk', 'au', 'za', 'yu', 'ws', 'at', 'us', 'vg', 'as', 'va', 'tv', 'pt', 'si', 'sk', 'ag', 'sm', 'ca', 'su', 'al', 'am', 'tc', 'th', 'tm', 'ro', 'tn', 'to', 'ru', 'se', 'sh', 'eu', 'dk', 'ie', 'il', 'de', 'cz', 'cy', 'cx', 'is', 'it', 'jp', 'ke', 'kr', 'la', 'hu', 'hm', 'hk', 'fi', 'fj', 'fo', 'fr', 'es', 'gb', 'eg', 'ge', 'ee', 'gl', 'ac', 'gr', 'gs', 'li', 'lk', 'cd', 'nl', 'no', 'cc', 'by', 'br', 'nu', 'nz', 'bg', 'be', 'ba', 'az', 'pk', 'ch', 'ck', 'cl', 'lt', 'lu', 'lv', 'ma', 'mc', 'md', 'mk', 'mn', 'ms', 'mt', 'mx', 'dz', 'cn', 'pl');
				
				$site_len = strlen($site_domain);
				
				for ( $i = 0; $i < count($tlds); $i++ )
				{
					$tld = $tlds[$i];
					$tld_len = strlen($tld);
					
					# drop stuff that's too short
					if ( $site_len < $tld_len + 2 ) continue;
					
					# catch stuff like blahco.uk
					if ( substr($site_domain, -1 * $tld_len - 1, 1) != '.' ) continue;
					
					# match?
					if ( substr($site_domain, -1 * $tld_len) != $tld ) continue;
					
					# extract domain
					$site_domain = substr($site_domain, 0, $site_len - $tld_len - 1);
					$site_domain = explode('.', $site_domain);
					$site_domain = array_pop($site_domain);
					$site_domain = $site_domain . '.' . $tld;
				}
			}
		}
		
		$link_domain = $item['ref'];
		$link_domain = parse_url($link_domain);
		$link_domain = $link_domain['host'];
		
		if ( $site_domain == $link_domain
			|| substr($link_domain, -1 * strlen($site_domain) - 1) == ( '.' . $site_domain )
			)
		{
			$li_class = 'nav_branch';
		}
		else
		{
			$li_class = 'nav_leaf';
		}
		
		return '<li class="' . $li_class . '">'
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
		$link = ( $item['label'] ? $item['label'] : __('Untitled') );
		
		if ( !( is_front_page() && !is_paged() ) )
		{
			$link = '<a href="' . htmlspecialchars(user_trailingslashit(get_option('home'))) . '">'
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
		$link = ( $post_label[$item_id] ? $post_label[$item_id] : __('Untitled') );
		
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
		$wpdb->query("DELETE FROM $wpdb->postmeta WHERE meta_key LIKE '\_nav\_menus\_cache%'");
		
		return $in;
	} # clear_cache()
} # nav_menus

if ( is_admin() && !class_exists('widget_utils') )
	include dirname(__FILE__) . '/widget-utils.php';
?>