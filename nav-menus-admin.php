<?php
if ( !class_exists('widget_utils') )
{
	include dirname(__FILE__) . '/widget-utils.php';
}

class nav_menus_admin
{
	#
	# init()
	#

	function init()
	{
		add_action('admin_menu', array('nav_menus_admin', 'meta_boxes'));
		
		if ( get_option('nav_menus_cache') === false )
		{
			update_option('nav_menus_cache', array());
		}

		if ( version_compare(mysql_get_server_info(), '4.1', '<') )
		{
			add_action('admin_notices', array('nav_menus_admin', 'mysql_warning'));
			remove_action('widgets_init', array('nav_menus', 'widgetize'));
		}
		
		if ( strpos($_SERVER['REQUEST_URI'], '/wp-admin/widgets.php') !== false )
		{
			add_action('admin_head', array('nav_menus_admin', 'css'));
			add_action('admin_print_scripts', array('nav_menus_admin', 'register_scripts'));
		}
	} # init()
	
	
	#
	# register_scripts()
	#
	
	function register_scripts()
	{
		$plugin_path = plugin_basename(__FILE__);
		$plugin_path = preg_replace("/[^\/]+$/", '', $plugin_path);
		$plugin_path = '/wp-content/plugins/' . $plugin_path . 'js/';
		
		wp_enqueue_script( 'jquery-livequery', $plugin_path . 'jquery.livequery.js', array('jquery'),  '1.0.3' );
		wp_enqueue_script( 'dimensions' );
		wp_enqueue_script( 'jquery-ui-mouse', $plugin_path . 'ui.mouse.js', array('jquery'),  '1.5' );
		wp_enqueue_script( 'jquery-ui-draggable', $plugin_path . 'ui.draggable.js', array('jquery'),  '1.5' );
		wp_enqueue_script( 'jquery-ui-droppable', $plugin_path . 'ui.droppable.js', array('dimensions', 'jquery-ui-mouse', 'jquery-ui-draggable'),  '1.5' );
		wp_enqueue_script( 'jquery-ui-sortable', $plugin_path . 'ui.sortable.js', array('jquery-ui-draggable', 'jquery-ui-droppable'),  '1.5' );
		wp_enqueue_script( 'nav-menus', $plugin_path . 'admin.js', array('jquery-ui-sortable', 'jquery-livequery'),  '20080415' );
	} # register_scripts()
	
	
	#
	# css()
	#
	
	function css()
	{
		echo '<link rel="stylesheet" type="text/css" href="'
			. str_replace(
				ABSPATH,
				trailingslashit(site_url()),
				dirname(__FILE__) . '/css/admin.css'
				)
			. '">' . "\n";
	} # css()
	
	
	#
	# mysql_warning()
	#
	
	function mysql_warning()
	{
		echo '<div class="error">'
			. '<p><b style="color: firebrick;">Nav Menus Error</b><br /><b>Your MySQL version is lower than 4.1.</b> It\'s time to <a href="http://www.semiologic.com/resources/wp-basics/wordpress-server-requirements/">change hosts</a> if yours doesn\'t want to upgrade.</p>'
			. '</div>';
	} # mysql_warning()
	
	
	#
	# meta_boxes()
	#
	
	function meta_boxes()
	{
		widget_utils::page_meta_boxes();
		
		add_action('page_widget_config_affected', array('nav_menus_admin', 'widget_config_affected'));
	} # meta_boxes()


	#
	# widget_config_affected()
	#
	
	function widget_config_affected()
	{
		echo '<li>'
			. 'Nav Menus'
			. '</li>';
	} # widget_config_affected()


	#
	# widget_control()
	#

	function widget_control($widget_args)
	{
		global $wpdb;
		static $pages;
		static $page_labels;
		static $i = 1;
		
		global $wp_registered_widgets;
		static $updated = false;

		if ( is_numeric($widget_args) )
			$widget_args = array( 'number' => $widget_args );
		$widget_args = wp_parse_args( $widget_args, array( 'number' => -1 ) );
		extract( $widget_args, EXTR_SKIP ); // extract number

		$options = nav_menus::get_options();

		if ( !$updated && !empty($_POST['sidebar']) )
		{
			$sidebar = (string) $_POST['sidebar'];

			$sidebars_widgets = wp_get_sidebars_widgets();

			if ( isset($sidebars_widgets[$sidebar]) )
				$this_sidebar =& $sidebars_widgets[$sidebar];
			else
				$this_sidebar = array();

			foreach ( $this_sidebar as $_widget_id )
			{
				if ( array('nav_menus', 'display_widget') == $wp_registered_widgets[$_widget_id]['callback']
					&& isset($wp_registered_widgets[$_widget_id]['params'][0]['number'])
					)
				{
					$widget_number = $wp_registered_widgets[$_widget_id]['params'][0]['number'];
					if ( !in_array( "nav_menu-$widget_number", $_POST['widget-id'] ) ) // the widget has been removed.
						unset($options[$widget_number]);
					
					nav_menus::clear_cache();
				}
			}

			foreach ( (array) $_POST['nav-menu'] as $num => $opt ) {
				$title = strip_tags(stripslashes($opt['title']));
				
				$desc = isset($opt['desc']);
				
				$_items = (array) $opt['items'];
				
				$items = array();
				
				foreach ( $_items as $_item )
				{
					$item = array();

					$item['type'] = $_item['type'];
					
					if ( !in_array($item['type'], array('home', 'url', 'page')) )
					{
						continue;
					}
					
					$label = trim(strip_tags(stripslashes($_item['label'])));
					
					switch ( $item['type'] )
					{
						case 'home':
							$item['label'] = $label;
							break;
						case 'url':
							$item['ref'] = trim(strip_tags(stripslashes($_item['ref'])));
							$item['label'] = $label;
							break;
						case 'page':
							$item['ref'] = intval($_item['ref']);
							delete_post_meta($item['ref'], '_widgets_label');
							$page = get_post($item['ref']);
							if ( $page->post_title != $label )
							{
								add_post_meta($item['ref'], '_widgets_label', $label, true);
							}
							break;
					}
					
					$items[] = $item;
				}
				
				$options[$num] = compact( 'title', 'desc', 'items' );
			}

			update_option('nav_menus', $options);

			$updated = true;
		}
		
		
		if ( !isset($pages) )
		{
			$pages = (array) $wpdb->get_results("
				SELECT	posts.*,
						COALESCE(post_label.meta_value, post_title) as post_label
				FROM	$wpdb->posts as posts
				LEFT JOIN $wpdb->postmeta as post_label
				ON		post_label.post_id = posts.ID
				AND		post_label.meta_key = '_widgets_label'
				WHERE	post_type = 'page'
				AND		post_status = 'publish'
				AND		post_parent = 0
				ORDER BY menu_order, post_title
				");
			
			update_post_cache($pages);
			
			$page_labels = array();
			
			foreach ( $pages as $page )
			{
				$page_labels[$page->ID] = $page->post_label;
			}
		}
		
		
		if ( -1 == $number )
		{
			$ops = nav_menus::default_options();
			$number = '%i%';
		}
		else
		{
			$ops = $options[$number];
		}
		
		extract($ops);
		

		echo '<div style="margin: 0px 0px 6px 0px;">' . "\n"
			. '<div style="width: 100px; float: left; padding-top: 2px;">' . "\n"
			. '<label for="nav-menu-title-' . $number . '">'
			. __('Title', 'nav-menus')
			. '</label>'
			. '</div>' . "\n"
			. '<div style="width: 350px; float: right;">' . "\n"
			. '<input style="width: 320px;"'
			. ' id="nav-menu-title-' . $number . '" name="nav-menu[' . $number . '][title]"'
			. ' type="text" value="' . attribute_escape($title) . '"'
			. ' />'
			. '</div>' . "\n"
			. '<div style="clear: both;"></div>' . "\n"
			. '</div>' . "\n";

		echo '<div style="margin: 0px 0px 6px 0px;">' . "\n"
			. '<div style="width: 100px; float: left; padding-top: 2px;">'
			. '<label for="nav-menu-items-' . $number . '-select">'
			. __('Items', 'nav-menus')
			. '</label>'
			. '</div>' . "\n"
			. '<div style="width: 350px; float: right;">' . "\n";
		
		echo '<select id="nav-menu-items-' . $number . '-select" class="nav_menu_item_select"'
			. '>';
		
		echo '<optgroup label="' . attribute_escape('Url') . '">';
		
		$value = 'type=home&amp;url=' . urlencode(user_trailingslashit(get_option('home')));
		
		echo '<option value="' . $value . '"'
			. ' class="nav_menu_item_home"'
			. '>'
			. 'Home'
			. '</option>';
		
		$value = 'type=url';
		
		echo '<option value="' . $value . '"'
			. ' class="nav_menu_item_url"'
			. '>'
			. 'Url'
			. '</option>';
		
		echo '</optgroup>';
		
		echo '<optgroup label="' . attribute_escape('Page') . '">';
		
		foreach ( $pages as $page )
		{
			$value = 'type=page&amp;ref=' . $page->ID . '&amp;url=' . urlencode(get_permalink($page->ID));

			echo '<option value="' . $value . '"'
				. ' class="nav_menu_item_page"'
				. '>'
				. attribute_escape($page->post_title)
				. '</option>';
		}
		
		echo '</optgroup>';

		echo '</select>';
		
		echo '&nbsp;<input type="button" id="nav-menu-items-' . $number . '-add"'
			. ' class="nav_menu_item_button nav_menu_item_button_add"'
			. ' value="+" />';
		
		echo '<div id="nav-menu-items-' . $number . '" class="nav_menu_items"'
			. '>' . "\n";
		
		if ( !empty($items) )
		{
			foreach ( $items as $item )
			{
				$item_id = md5(serialize($item) . '-' . $i++);
				
				if ( $item['type'] == 'page' )
				{
					if ( !isset($page_labels[$item['ref']]) ) continue;
					$item['label'] = $page_labels[$item['ref']];
				}
				
				echo '<div class="button nav_menu_item" id="' . $item_id . '">' . "\n"
						. '<div class="nav_menu_item_header">' . "\n"
							. '<input type="text" class="nav_menu_item_label"'
								. ' name="nav-menu[' . $number . '][items][' . $item_id . '][label]"'
								. ' value="' . attribute_escape($item['label']) . '"'
								. ' />'
							. '<input type="hidden" name="nav-menu[' . $number . '][items][' . $item_id . '][type]"'
								. ' value="' . attribute_escape($item['type']) . '"'
								. ' />'
							. '&nbsp;<input type="button" id="' . $item_id . '-remove-' . $number . '"'
								. ' class="nav_menu_item_button nav_menu_item_button_remove"'
								. ' tabindex="-1" value="-" />'
							. '</div>' . "\n"
						. '<div class="nav_menu_item_content">' . "\n";

				switch ( $item['type'] )
				{
				case 'url':
					echo '<input type="text" name="nav-menu[' . $number . '][items][' . $item_id . '][ref]"'
						. ' class="nav_menu_item_ref"'
						. ' value="' . attribute_escape($item['ref']) . '"'
						. ' />';
					break;

				case 'home':
					$url = user_trailingslashit(get_option('home'));
					break;
				case 'page':
					echo '<input type="hidden" name="nav-menu[' . $number . '][items][' . $item_id . '][ref]"'
						. ' value="' . intval($item['ref']) . '"'
						. ' />';
					$url = get_permalink($item['ref']);
					break;
				}

				switch ( $item['type'] )
				{
				case 'home':
				case 'page':
					echo '&rarr;&nbsp;<a href="' . $url . '" class="nav_menu_item_preview" onclick="window.open(this.href); return false;">'
						. attribute_escape($item['label'])
						. '</a>';
					break;

				}

				echo '</div>' . "\n"
					. '</div>' . "\n";
			}
		}
		else
		{
			echo '<div class="nav_menu_item_empty">'
				. 'Empty Nav Menu'
				. '</div>';
		}

		echo '</div>' . "\n";
		
		echo '</div>' . "\n"
			. '<div style="clear: both;"></div>' . "\n"
			. '</div>' . "\n";

		echo '<div style="margin: 0px 0px 6px 0px;">' . "\n"
			. '<div style="width: 100px; float: left; padding-top: 2px;">' . "\n"
			. '&nbsp;'
			. '</div>' . "\n"
			. '<div style="width: 350px; float: right;">' . "\n"
			. '<label>'
			. '<input type="checkbox"'
			. ' id="nav-menu-desc-' . $number . '" name="nav-menu[' . $number . '][desc]"'
			. ( $desc
				? ' checked="checked"'
				: ''
				)
			. ' />'
			. '&nbsp;'
			. 'Show Descriptions'
			. '</label>'
			. '</div>' . "\n"
			. '<div style="clear: both;"></div>' . "\n"
			. '</div>' . "\n";
	} # widget_control()
} # nav_menus_admin

nav_menus_admin::init();
?>