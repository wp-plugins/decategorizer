=== Decategorizer ===
Contributors: aesqe
Donate link: http://skyphe.org/code/wordpress/decategorizer/
Tags: category, category base, url, uri, links, permalinks, redirection
Requires at least: 2.5.0
Tested up to: 2.7-almost-beta
Stable tag: trunk

"Decategorizer" removes 'category_base' from your permalinks.
No special setup or .htaccess editing is required.

== Description == 

"Decategorizer" will turn your URL's from something like 
"http://yourdomain/category/news/" to "http://yourdomain/news/". 

It is meant to be used together with John Godley's
['Redirection'](http://urbangiraffe.com/plugins/redirection/) 
plugin. "Decategorizer" will disable itself if Redirection is 
not installed.

Redirection version 2.x. is recommended (Last tested with v2.1.7)

**Changelog:**

0.5.3 = february 10th 2009
changed category redirections into regexes and added $ (end of string) 
at the end of expressions to make sure it parses category URLs _only_.
also tested with wordpress MU 2.7 (and Redirection 2.1.7) and everything 
seems to work just fine :)

0.5.2	= october 31st
minor code changes

0.5.1	= october 30th 2008
fixed:
	static homepage pagination
	static posts page pagination
	301 redirection for paginated category/tag archives with slugs including category_base

0.5		= october 26th 2008
Plugin will now automatically disable itself if "Redirection" is 
not installed/activated.
Added notifications on top of the admin screens.
Added 301 redirection for old permalinks containing /category_base/
(thanks Utilaje!).
Added support for permalinks without trailing slash (thanks PH!).
Redirections are now added when the plugin is activated, and on a 
few other occasions (see bottom of plugin file for all the hooks).
Plugin no longer runs on each and every pageload. Hooray :)

0.4		- plugin now checks if redirection tables exist (d'oh) before 
starting to work. <del>It also checks whether adding redirections is 
actually needed (if you have no child categories, for example).</del>
-> removed in 0.5 for compatibility reasons ('/%postname%' permalinks).

0.3		- added automatic creation of redirection 
rules. PLEASE NOTE: Although I've been testing the plugin for the past 
two hours, do try it at home first.
REMINDER TO SELF: addslashes(), INSERT, save your sanity...

0.2.1	- instead of setting back category_base to its default value, 
the current value is used, so one's permalinks don't get broken if 
they decide to deactivate the plugin. Sorry for that, current users :/
Please check your 'category_base' value.

0.2		- added the filter to 'category_link' as well. No more 
"/category/" in links when using "Google XML Sitemaps" and "Dagon 
Design Sitemap Generator" plugins :)

== Installation == 

1.	Place the whole 'decategorizer' folder into your wordpress' 
	installation folder, under 'wp-content\plugins'.
2.	Go to WordPress administration->plugins page and make sure that 
	the plugin's name is on the list, but do not activate it just 
	yet.
3.	Install Urban Giraffe's Redirection plugin the same way, 
	if you haven't already done so.
4.  Go to Redirection plugin's options page so its database tables
	can be created.
4.	Activate 'Redirection' plugin.
5.	You're done.

== More info ==

To see the end result before you decide to install, please 
visit [wordpress.skyphe.org](http://wordpress.skyphe.org) and 
browse around. You'll notice that no url contains "category" and still 
everything works just fine.

I don't think I've ever written a readme before. Is it full of 
spelling and grammar errors? Too long? I suck at explaining how things 
work? Drop me an e-mail if something's bugging you about this text :)

== Plans for the next version? ==

(modified on February 10th 2009 at 11:36PM)

Make it a "Redirection" extending class - does that make any sense?

Or maybe move from "Redirection" completely and just write the values
to the .htaccess file?

Leave a comment here (Wordpress forums) or on my site, or send me an 
e-mail if you would like to see something added to the plugin.

Thanks for reading this :)