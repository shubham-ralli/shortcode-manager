=== Shortcode Manager ===
Contributors: shubham110019  
Donate link: https://github.com/shubham110019  
Tags: shortcode, editor, custom code, code editor, admin  
Requires at least: 5.0  
Tested up to: 6.5  
Requires PHP: 7.2  
Stable tag: 1.0  
License: GPLv2 or later  
License URI: https://www.gnu.org/licenses/gpl-2.0.html  

Create and manage custom shortcodes directly from the WordPress admin area using a user-friendly interface with syntax highlighting.

== Description ==

**Shortcode Manager** lets you create, edit, and manage custom shortcodes from the WordPress dashboard. Each shortcode can be written using an integrated CodeMirror-powered editor with PHP/HTML syntax highlighting.

Use your created shortcode in posts, pages, widgets, or theme files using the simple syntax:  
`[sc name="your-slug"]`

**Key Features:**
* Create custom shortcodes from the admin panel
* Syntax-highlighted code editor for writing shortcode content
* Easy usage tracking – see where each shortcode is used
* Works in posts, pages, and text widgets
* Lightweight and easy to use

== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/shortcode-manager` directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the 'Plugins' screen in WordPress.
3. Go to **Shortcodes** in the WordPress admin menu to start creating your custom shortcodes.

== Frequently Asked Questions ==

= How do I use a shortcode created with this plugin? =  
Just use `[sc name="your-slug"]` in any post, page, or widget where `your-slug` is the post slug of your custom shortcode.

= Can I use HTML, CSS, or PHP inside the shortcode content? =  
Yes, you can write any valid code. PHP should be wrapped appropriately, and note that shortcode execution follows the usual WordPress behavior.

= Where can I see where a shortcode is used? =  
Each shortcode's admin row shows how many times it's used. You can also click to view detailed usage across content and widgets.

== Screenshots ==

1. Admin listing of created shortcodes
2. Code editor interface for writing shortcode content
3. Usage details page for tracking where shortcodes appear

== Changelog ==

= 1.0 =
* Initial release with shortcode editor, usage tracker, and admin UI.

== Upgrade Notice ==

= 1.0 =
Initial release.

== License ==

This plugin is licensed under the GPLv2 or later.
