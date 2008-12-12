
nav_menus = {
	init : function() {
		if ( jQuery(this).children("div.nav_menu_item").size() )
		{
			jQuery(this).sortable({});
		}
	},
	
	add : function() {
		var number = this.id.match(/(?:[0-9]+|header|footer)/);
		var menu = '#' + this.id.replace(/-add$/, '');
		var select = menu + '-select';
		
		jQuery(select).find("option:selected").each(function() {
			this.getItem = nav_menus.getItem;
			var item = this.getItem(number);

			if ( jQuery(menu).children("div.nav_menu_item_empty").size() )
			{
				jQuery(menu).html(item);
				jQuery(menu).sortable({});
			}
			else
			{
				jQuery(menu).append(item);
				jQuery(menu).sortable('refresh');
			}
		});
	},
	
	remove : function() {
		var item = '#' + this.id.match(/^[0-9a-f]+/);
		var menu = '#nav-menu-items-' + this.id.match(/(?:[0-9]+|header|footer)$/);
		
		if ( jQuery(menu).children().size() > 1 )
		{
			var newHTML = '';
			
			jQuery(menu).children().not(item).each(function() {
				newHTML += '<div class="button nav_menu_item" id="' + this.id + '">'
					+ jQuery(this).html()
					+ '</div>';
			});
			
			jQuery(menu).html(newHTML);
			jQuery(menu).sortable('refresh');
		}
		else
		{
			jQuery(menu).sortable("destroy");
			jQuery(menu).html(nav_menus.getPlaceHolder());
		}
	},
	
	newId : function() {
		var id = '';
		var i;
		var letters = '0123456789abcdef';
		
		for ( i = 0; i < 32; i++ )
		{
			id = id + letters.charAt(Math.floor(letters.length * Math.random()));
		}
		
		return id;
	},
	
	getPlaceHolder : function() {
		return '<div class="nav_menu_item_empty">'
			+ 'Empty Nav Menu'
			+ '</div>';
	},
	
	getItem : function(number) {
		var item_id;
		var label = this.text;
		var details = this.value;
		var i;
		var keyval;
		var type;
		var ref;
		var url;
		var str;
		
		details = details.replace(/&amp;/ig, '&');
		details = details.split('&');
		
		for ( i = 0; i < details.length; i++ )
		{
			keyval = details[i];

			keyval = keyval.split('=');
			
			switch ( keyval[0] )
			{
				case 'type':
					type = decodeURIComponent(keyval[1]);
					if ( type == 'url' )
					{
						label = 'Url Caption';
					}
					break;
				case 'ref':
					ref = decodeURIComponent(keyval[1]);
					break;
				case 'url':
					url = decodeURIComponent(keyval[1]);
					break;
			}
		}
		
		do {
			item_id = nav_menus.newId();
		} while ( jQuery('#nav-menu-item-' + id).size() );
		
		str = '<div class="button nav_menu_item" id="' + item_id + '">'
			+ '<div class="nav_menu_item_header">'
			+ '<input type="text" class="nav_menu_item_label"'
				+ ' name="nav-menu[' + number + '][items][' + item_id + '][label]"'
				+ ' value="' + label + '"'
				+ ' />'
			+ '<input type="hidden" name="nav-menu[' + number + '][items][' + item_id + '][type]"'
				+ ' value="' + type + '"'
				+ ' />'
			+ '&nbsp;<input type="button" id="' + item_id + '-remove-' + number + '"'
				+ ' class="nav_menu_item_button nav_menu_item_button_remove"'
				+ ' tabindex="-1" value="-" />'
			+ '</div>'
			+ '<div class="nav_menu_item_content">';

		switch ( type )
		{
		case 'url':
			str += '<input type="text" class="nav_menu_item_ref"'
				+ ' name="nav-menu[' + number + '][items][' + item_id + '][ref]"'
				+ ' value="http://"'
				+ ' />';
			break;

		case 'page':
			str += '<input type="hidden" name="nav-menu[' + number + '][items][' + item_id + '][ref]"'
				+ ' value="' + ref + '"'
				+ ' />';
			break;
		}
		
		switch ( type )
		{
		case 'home':
		case 'page':
			str += '&rarr;&nbsp;<a href="' + url + '" class="nav_menu_item_preview" target="_blank">' +  label + '</a>';
			break;
		}
		
		str += '</div>';
		
		return str;
	},
	
	onLabelChange : function() {
		this.value = jQuery.trim(this.value);
		var item = '#' + this.name.replace(new RegExp('nav-menu\\[.*?\\]\\[items\\]\\[(.*?)\\]\\[label\\]'), '$1');
		jQuery(item).find("a.nav_menu_item_preview").html(this.value);
	}
};

/*
 * Attach Behaviors
 */
jQuery("div.nav_menu_items").livequery( nav_menus.init );
jQuery("input.nav_menu_item_button_add").livequery( 'click', nav_menus.add );
jQuery("input.nav_menu_item_button_remove").livequery( 'click', nav_menus.remove );
jQuery("input.nav_menu_item_label").livequery('change', nav_menus.onLabelChange );