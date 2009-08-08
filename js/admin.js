jQuery(document).ready(function(){
	jQuery('.nav_menu_item_add').live('click', function() {
		var select = jQuery(this).parent().children("select");
		var handle = select.val();
		
		if ( !handle ) {
			select.val('home');
			handle = 'home';
		}
		
		var menu = jQuery(this).closest('.nav_menu_items');
		var items = menu.children('.nav_menu_item_sortables');
		var item = menu.children('.nav_menu_item_defaults').children('.nav_menu_item-' + handle).clone();
		item.find(':disabled').removeAttr('disabled');
		
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
			handle = select.children().children('option[value=' + handle + '] + :first').val();
			if ( typeof handle == 'undefined')
				handle = 'url';
			select.val(handle);
		}
	});
	
	jQuery('.nav_menu_item_remove').live('click', function() {
		var menu = jQuery(this).closest('.nav_menu_items');
		var items = menu.children('.nav_menu_item_sortables');
		var item = jQuery(this).closest('.nav_menu_item');
		var blank = menu.children('.nav_menu_item_defaults').children('.nav_menu_item_blank').clone();

		if ( items.children('.nav_menu_item').size() > 1 ) {
			item.remove();
			items.sortable('refresh');
		} else {
			items.sortable('disable');
			items.empty();
			items.append(blank);
		}
	});
	
	jQuery('.nav_menu_item_sortables:has(.nav_menu_item)').sortable({});
	
	jQuery('.nav_menu_item_label', this).livequery('change', function() {
		this.value = jQuery.trim(this.value);
		jQuery(this).parent().siblings('.nav_menu_item_preview').children("a").html(this.value);
	});

	jQuery('.nav_menu_item_ref', this).livequery('change', function() {
		this.value = jQuery.trim(this.value);
		jQuery(this).parent().siblings('.nav_menu_item_preview').children('a').attr('href', this.value);
	});
});