var navMenus = {
	onLabelChange: function(elt) {
		var t = jQuery(elt);
		t.val(jQuery.trim(t.val()));
		t.closest('div.nav_menu_item').children('.nav_menu_item_preview').children('a')
		.html(t.val());
	},
	
	onRefChange: function(elt) {
		var t = jQuery(elt);
		t.val(jQuery.trim(t.val()));
		t.closest('div.nav_menu_item').children('.nav_menu_item_preview').children('a')
		.attr('href', t.val());
	}
};

window.navMenus = navMenus;

jQuery(document).ready(function(){
	
	jQuery('.nav_menu_item_add').live('click', function() {
		var select = jQuery(this).siblings("select");
		var handle = select.val();
		
		if ( !handle ) {
			select.val('home');
			handle = 'home';
		}
		
		var menu = jQuery(this).closest('div.nav_menu_items');
		var items = menu.children('.nav_menu_item_sortables');
		var base = menu.children('.nav_menu_base').val();
		var item = jQuery('#nav_menu_item_defaults').children('.nav_menu_item-' + handle).clone();
		
		item.find('input').each(function() {
			var name = jQuery(this).attr('name');
			if ( name )
				jQuery(this).attr('name', base + name);
		});
		
		if ( !items.children('.nav_menu_item').size() ) {
			items.empty();
			items.append(item);
			items.sortable({});
		} else {
			items.append(item);
			items.sortable('refresh');
		}
		
		if ( handle == 'home' ) {
			handle = select.children('.nav_menu_item_pages').children(':first').val();
			if ( typeof handle != 'undefined' )
				select.val(handle);
		} else if ( handle.match(/^page-\d+/)) {
			handle = select.children().children('option[value="' + handle + '"] + :first').val();
			if ( typeof handle == 'undefined')
				handle = 'url';
			select.val(handle);
		}
	});
	
	jQuery('.nav_menu_item_remove').live('click', function() {
		var menu = jQuery(this).closest('div.nav_menu_items');
		var items = menu.children('.nav_menu_item_sortables');
		var item = jQuery(this).closest('div.nav_menu_item');
		var blank = jQuery('#nav_menu_item_defaults').children('.nav_menu_item_blank').clone();
		
		if ( items.children('.nav_menu_item').size() > 1 ) {
			item.remove();
			items.sortable('refresh');
		} else {
			items.sortable('disable');
			items.empty();
			items.append(blank);
		}
	});
	
	jQuery('div.nav_menu_item_sortables:has(.nav_menu_item)').sortable({});
});