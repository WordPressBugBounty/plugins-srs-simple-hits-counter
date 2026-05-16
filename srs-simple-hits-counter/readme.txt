=== SRS Simple Hits Counter ===
Contributors: SandyRig
Tags: hits, visitor, counter, page-views, analytics, insights
Requires at least: 3.4
Tested up to: 6.9.4
Stable tag: 2.2.1
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Display unique visitor or page-view counts anywhere on your site using widgets or shortcodes — Simple, very lightweight and no third-party code.

== Description ==

SRS Simple Hits Counter is a simple, very lightweight visitor counter plugin for WordPress that tracks Unique Visitors and Page-views — without causing render blocking or straining your site.

You can display your visitor counter anywhere on your site using widgets or shortcodes. Show Unique Visitors, Page-views, or both — just use two copies of the widget or shortcode to display both counters together.

Note: The counter keeps running in the background even when no widget or shortcode is active. To completely stop counting, you need to disable the plugin.

= Features =

* AJAX based counter ignores most bots or crawlers
* Monthly and weekly graph in admin
* Show the Unique Visitors, Page-views count or both
* Ability to reset the counter to any number any time
* Can be shown anywhere on the site using Widgets and Shortcode
* Counter works and shows data in admin even when no widget or short-code is active

= SHORTCODES =

[srs_total_visitors] for Unique Visitors
[srs_total_pageViews] for Page-views

[DEMO](https://atif.rocks/srs-simple-hits-counter/)

== Installation ==

= From your WordPress dashboard =

1. Visit 'Plugins > Add New'
2. Search for 'SRS Simple Hits Counter'
3. Activate 'SRS Simple Hits Counter' from your Plugins page.
4. Use widget to display the counter in footer or sidebar etc.
5. Use the shortcode to add the counter to any part of the site.

= From WordPress.org =

1. Download 'SRS Simple Hits Counter'.
2. Upload the 'SRS Simple Hits Counter' directory to your '/wp-content/plugins/' directory, using your favorite method (ftp, sftp, scp, etc...)
3. Activate 'SRS Simple Hits Counter' from your Plugins page.
4. Use widget to display the counter in footer or sidebar etc.
5. Use the shortcode to add the counter to any part of the site.


== Screenshots ==

1. Go to Appearance > Widgets and look for the widget 'SRS Simple Hits Counter' Select the widget area and click 'Add Widget'.
2. Select 'Visitors' radio button to show Unique vistors to your site.
3. Select 'Page Views' radio button to show count for every page load.
4. Widget Demo

== Changelog ==

= 2.2.1 - 16th May 2026 =
* Transparent background bug fix
* Increased counter size allow larger numbers
* Banner and thumbnail update

= 2.2 - 13th May 2026 =
* Added counter customization feature that let's create a unique look and feel for the counter according you your site design
* Security updates
* Other code optimizations

= 2.1 - 19th Jan 2025 =
* Added popular content section to dashboard
* Updated UI for settings page
* Bug fixes on visitors and views reset functions
* Added admin stylesheet

= 2.0.1 - 17th Jan 2025 =
* Fixed - PHP Warnings and erros in certain conditions

= 2.0 - 17th Jan 2025 =
* Updated admin dashboard for better analytics view

= 1.1.1 - 20th DEC 2023 =
* Security Bug Patch

= 1.1.0 - 20th JULY 2020 =
* Added the ability to reset plugin data deleting all the existing data in the plugin's database table and start fresh
* Security and other updates
* Graph libraries updated

= 1.0.5 - 17th JULY 2020 =
* Critical Security Update

= 1.0.4 - 6th JULY 2020 =
* Security Updates

= 1.0.3 - 4th JUNE 2018 =
* Removed PHP Session based user tracking if favor of session cookie to help with caching
* Moved js files to load in footer to improve Google PageSpeed score by avoiding render-blocking.

= 1.0.2 - 14th AUG 2017 =
* Critical update, fixed migration bug in 1.0.1

= 1.0.1 - 8th AUG 2017 =
* Critical bug fix

= 1.0.0 - 8th AUG 2017 =
* Added weekly and monthly graph

= 0.1.6 - 2nd AUG 2017 =
* Added the option to show commas in the counter
* Changed to Ajax based counting to filter out bots and crawlers

= 0.1.5 - 6th DEC 2016 =
* Created options page under the settings menu and moved counter reset settings to that page.

= 0.1.4 - 17th AUG 2015 =
* Removed the extra closing tag for page-views shortcode.

= 0.1.3 - 4th Feb 2015 =
* Added CSS classes to identify and add custom style to the counters span tags.

= 0.1.2 - 24th Jan 2015 =
* Critical - Unique Visitors bug fix.

= 0.1.1 - 18th Dec 2014 =
* Fixed a PHP notice.

= 0.1.0 - 17th Dec 2014 =
* Simple counter to show Unique Visitors or Page-views on your site.