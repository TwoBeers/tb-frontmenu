=== TB Frontmenu ===
Contributors: tbcrew
Tags: menu
Requires at least: 3.9
Tested up to: 4.0
Stable tag: trunk
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

== Description ==

a cool squared menu

== Installation ==

= Manual installation =

1. Upload the `tb-frontmenu` folder to the `/wp-content/plugins/` directory

= Installation using *Add New Plugin* =

1. From your Admin UI (Dashboard), use the menu to select Plugins -> Add New
2. Search for *tb-frontmenu*
3. Click the *Install* button to open the plugin*s repository listing
4. Click the *Install* button

= Activation and Use =

1. Activate the plugin through the *Plugins* menu in WordPress
2. From your Admin UI (Dashboard), select Appearance -> Menu
3. Assign a menu (create one if needed) to the *TB Frontmenu* location
4. From your Admin UI (Dashboard), use the menu to select Appearance -> TB Frontmenu
5. Configure settings, and save
6. From your Admin UI (Dashboard), select Appearance -> Editor
7. Select the template where you want to add the menu, paste `<?php do_action('tb_frontmenu_display'); ?>` in the desired location, and save

== Frequently Asked Questions ==

= How many level of hierachy are supported? =

The plugin supports 2 levels of hierarchy

= Where should I paste the code for displaying the menu?  =

You can insert the code where you like the menu to be displayed, usually somewhere in header.php

= I can't use my images in the menu? It keeps saying me *image must be bigger than 640x360 px*... =

The plugin adds a new image size (called *tb-frontpage-thumb*), which is 640px wide and 360px tall. Your images can be used only if they are equal or bigger than this

= The items in the menu are not aligned, because the images have not the same proportion. What's wrong? =

The plugin adds a new image size, but if you use images that were uploaded before installing the plugin, they probably does have the correct size.
You need a plugin that rebuilds the thumbnails of your images, eg. [AJAX Thumbnail Rebuild](https://wordpress.org/plugins/ajax-thumbnail-rebuild/)

= The menu looks good, but it still needs some style adjustments. How to do it? =

You can either:
1. modify the style.css file in the plugin package ( not suggested ).
2. install a simple plugin for adding custom css code ( **highly suggested!** ). eg [Simple Custom CSS](https://wordpress.org/plugins/simple-custom-css/) or [Jetpack](https://wordpress.org/plugins/jetpack/)

= The Plugin settings are quite self-explanatory, but... what's the meaning of the *responsive threshold* option? =

The plugin has a responsive layout, which means that it adapts to its container, and when the screen is small, it uses a compact layout.
It's impossible for the plugin to know when it should fall to the compat mode (its container may vary a lot from a theme to another), therefore you have to tell the plugin the screen resolution at which it must do it.
1. Just activate the *test mode* option
2. Go to you site. You'll see a small banner before the menu, which tells you the actual screen width
3. Reduce the width of your browser window to find the desired width at which the plugin should fall to compact mode.
4. Go back to the options page, set the *responsive threshold* accordingly and deactivate the *test mode*.

= I've got other questions. Where can I find more support? =

Here : [TB Frontmenu repository](https://github.com/TwoBeers/tb-frontmenu/)

== Screenshots ==

Screenshots coming soon.

== Filters ==

The plugin has some filters:

1. *tb_frontmenu_image_size* - change the image size
2. *tb_frontmenu_visibility* - use your conditions for displaying the menu
3. *tb_frontmenu_default_options* - change the default options

== Changelog ==

= 1.0 =

* Initial Release

== Upgrade Notice ==

= 1.0 =

* Initial Release
