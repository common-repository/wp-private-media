=== WP Private Media ===
Contributors: webheadllc
Tags:  secure, upload, private, documents, private content, logged in only, protected
Requires at least: 4.9
Tested up to: 4.9.8
Stable tag: 1.0.1
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

 Allows media to be uploaded through the Media Library and not be accessible to the public.

== Description ==
WP Private Media is plugin that allows you to upload files to the WordPress Media Library and keep it from being viewed by the public.  Only logged in users will be able to view your files.  This plugin is perfect for those just wanting private files for their subscribers and not a full membership plugin.

When someone tries to visit the file or image URL and isn't logged in, they'll be directed to your website's 403 page.  This is similar to how WordPress redirects non-logged in users for private posts and pages.  


**INSTRUCTIONS**
To upload private media, find the "Add Private" menu item under Media in the sidebar menu.  Upload your image or document similar to uploading a normal media item.

To add private media, click on the "Add Private Media" button above the WordPress classic editor.  Find or upload your image or document and insert into post.

If you don't see any of these items, make sure your role has been granted access in the Settings -> WP Private Media page.  You'll need to be an administrator to access the Settings page.  

**PRO VERSION**
The Pro version features options to restrict specific roles of users instead of allowing any logged in user to view the private media.

Another feature of the Pro version is Google Analytics tracking.  If you enter your Google Analytics Tracking ID, whenever a private media file gets accessed or downloaded, an event will be sent to Google Analytics allowing you to track file downloads.

Learn more on the plugin's website:  https://webheadcoder.com/wp-private-media/


**SUPPORTED SERVERS**
Any server can use this plugin, but you will need to do some extra configurations if you are not using a server that utilizes a .htaccess file.  

If you're on a Apache server or a IIS server that supports .htaccess and WordPress has read/write access to the .htaccess file (majority of WordPress sites do), you don't need to worry about anything outside of WordPress.

If you don't have a server that uses the .htaccess file, this plugin will let you know.  You'll just need to configure a redirect in your server configuration.

== Installation ==
Install the plugin and activate it on your WordPress plugins page.

**SUPPORTED SERVERS**
Any server can use this plugin, but you will need to do some extra configurations if you are not using a server that utilizes a .htaccess file.  

If you're on a Apache server or a IIS server that supports .htaccess and WordPress has read/write access to the .htaccess file (majority of WordPress sites do), you don't need to worry about anything outside of WordPress.

If you don't have a server that uses the .htaccess file, this plugin will let you know.  You'll just need to configure a redirect in your server configuration.

== Changelog ==

= 1.0.1 =
fixed activation issues.  

= 1.0 =
added warning when deactivating the plugin.  
added information about Pro version.  

= 0.1 =
Initial release