=== Nav Menus ===
Contributors: Denis-de-Bernardy & Mike_Koepke
Donate link: https://www.semiologic.com/donate/
Tags: semiologic
Requires at least: 3.1
Tested up to: 4.3
Stable tag: trunk

Lets you manage navigation menus on your site.


== Description ==

Lets you manage navigation menus on your site.


= Help Me! =

The [Semiologic Support Page](https://www.semiologic.com/support/) is the best place to report issues.


== Installation ==

1. Upload the plugin folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress


== Change Log ==

= 2.5 =

- Updated to use PHP5 constructors as WP deprecated PHP4 constructor type in 4.3.
- WP 4.3 compat
- Tested against PHP 5.6

= 2.4 =

- WP 4.0 compat

= 2.3.2 =

- Use more full proof WP version check to alter plugin behavior instead of relying on $wp_version constant.

= 2.3.1 =

- Added further WP 3.9 customizer compatibility

= 2.3 =

- WP 3.9 customizer compatibility
- Add additional css classes for customization
- The exclusion for pages in menu widget is now fixed.
- Internal cache cleared on WP upgrade
- Code refactoring
- WP 3.9 compat


= 2.2.1 =

- A few CSS tweaks for new WP 3.8 admin design
- WP 3.8 compat

= 2.2 =

- WP 3.6 compat
- PHP 5.4 compat

= 2.1.3 =

- Fixed incorrect url being generated for hierarchies with children of children.  url was being generated as parent/grandparent/child

= 2.1.2 =

- Fix caching issue with "This Page in Widgets" not refreshing on title or description updates

= 2.1.1 =

- Fix menu item display broken by JQuery update since WP 3.3

= 2.1 =

- WP 3.5 compat
- Fixed unknown index warning message

= 2.0.6 =

- jQuery compat / Nav Menus

= 2.0.5 =

- Disable the Custom Menu widget from WP 3.0, to avoid conflicts

= 2.0.4 =

- WP 3.0 compat

= 2.0.3 =

- Further cache improvements (fix priority)
- Fix a potential infinite loop

= 2.0.2 =

- Sem Cache 2.0 related tweaks
- Fix blog link on search/404 pages
- Apply filters to permalinks

= 2.0.1 =

- Improved local url identification
- WP 2.9 compat

= 2.0 =

- Complete rewrite
- WP_Widget class
- Localization
- Code enhancements and optimizations
