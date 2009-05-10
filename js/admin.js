jQuery(document).ready(function(){
	jQuery("input.nav_menu_item_label").livequery('change', function() {
		this.value = jQuery.trim(this.value);
		jQuery(this).parent().siblings("div.nav_menu_item_preview").children("a").html(this.value);
	});
	
	jQuery("input.nav_menu_item_ref").livequery('change', function() {
		this.value = jQuery.trim(this.value);
		jQuery(this).parent().siblings("div.nav_menu_item_preview").children("a").attr('href', this.value);
	});
	
	jQuery("div.nav_menu_item_sortables").livequery(function() {
		var items = jQuery(this).children("div.nav_menu_item_sortables");
		if ( items.children("div.nav_menu_item").size() )
			items.sortable({});
	});
	
	jQuery("input.nav_menu_item_add").livequery('click', function() {
		var select = jQuery(this).parent().children("select");
		var handle = select.val();
		
		if ( !handle ) {
			select.val('home');
			handle = 'home';
		}
		
		var menu = jQuery(this).parent().parent();
		var items = menu.children("div.nav_menu_item_sortables");
		var item = menu.children("div.nav_menu_item_defaults").children("div.nav_menu_item-" + handle).clone();
		item.find(":disabled").removeAttr('disabled');
		
		if ( !items.children("div.nav_menu_item").size() ) {
			items.empty();
			items.append(item);
			items.sortable({});
		} else {
			items.append(item);
			items.sortable('refresh');
		}
		
		if ( handle == 'home' ) {
			handle = select.children("optgroup.nav_menu_item_pages").children("option:first").val();
			if ( typeof handle != 'undefined' )
				select.val(handle);
		} else if ( handle.match(/^page-\d+/)) {
			handle = select.children("optgroup").children("option[value=" + handle + "] + :first").val();
			if ( typeof handle == 'undefined')
				handle = 'url';
			select.val(handle);
		}
	});
	
	jQuery("input.nav_menu_item_remove").livequery('click', function() {
		var menu = jQuery(this).parent().parent().parent().parent();
		var items = menu.children("div.nav_menu_item_sortables");
		var item = jQuery(this).parent().parent();
		var blank = menu.children("div.nav_menu_item_defaults").children("div.nav_menu_item_blank").clone();
		
		if ( items.children("div.nav_menu_item").size() > 1 ) {
			item.remove();
			items.sortable('refresh');
		} else {
			items.sortable('disable');
			items.empty();
			items.append(blank);
		}
	});
});