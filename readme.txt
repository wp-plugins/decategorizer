=== Decategorizer ===
Contributors: aesqe
Donate link: http://skyphe.org/code/wordpress/decategorizer/
Tags: category, category base, url, uri, links, permalinks, redirection
Requires at least: 2.5.0
Tested up to: 2.6
Stable tag: trunk

This plugin adds a filter (which removes category_base text from 
permalinks) on get_pagenum_link, wp_list_categories and the_category 
functions.

== Description == 

Basically, it turns your URL's from something like 
"http://yourdomain/category/news/" to "http://yourdomain/news/". 

It is meant to be used together with 
[Urban Giraffe's Redirection](http://urbangiraffe.com/plugins/redirection/) 
plugin and a few redirections already set up. If used by 
itself, you'll just get lots of "Error 404 - Not found" pages.

I recommend using Redirection version 2.x.

**Changelog:**

0.2 - added the filter to 'category_link' as well. No more "/category/" 
in links when using "Google XML Sitemaps" and "Dagon Design Sitemap 
Generator" plugins :)

== Installation == 

1.	Place the whole 'decategorizer' folder into your wordpress' 
	installation folder, under 'wp-content\plugins'.
2.	Go to WordPress administration->plugins page and make sure that 
	the plugin's name is on the list, but do not activate it just 
	yet.
3.	Install Urban Giraffe's Redirection plugin the same way, 
	if you haven't already done so.
4.	No further setup is required for Decategorizer, but you will 
	need to set up a few redirection rules in WordPress admin under 
	Manage->Redirection. 

	A note: no need to replace your 'category_base' option, the plugin
	will reset it to '/category'.
	
	...and a question: Are TAG, AUTHOR, SEARCH and COMMENTS the only 
	things besides categories that have pagination? I based this on 
	the $wp_rewrite->rules output and haven't noticed pagination 
	anywhere else. Drop me an e-mail if I'm forgetting something, or
	just add it by yourself
	(put '|^/NEW_TYPE/' after '|^/commments/').

	1.	Source URL: 
			`(?!^/[\d]{4}/|^/tag/|^/author/|^/search/|^/comments/)^/(.+)/page/([\d]+)/`
			
			Important: if you're using Redirection plugin v 1.x, you 
			must replace the two + (plus) characters in regexp 
			with * (star) characters - just use the regexp below:
			
			`(?!^/[\d]{4}/|^/tag/|^/author/|^/search/|^/comments/)^/(.*)/page/([\d]*)/`
			
		Target URL:
			`/category/$1/page/$2/`
		
		Put a check next to "Regex"
		
		Type:
			Simple Redirection 
		Method:
			Pass-through

	2.	For each category that has a child category/categories, you 
		will have to add a new redirection rule/rules. 
		Sorry, it won't work any other way...

		For example: if your parent category is "consequat", and 
		it has two child categories - "aliquam" and "news" - your 
		redirection rules will look like this:
		
		Source URL:
			/consequat/aliquam/
		Target URL: 
			/category/consequat/aliquam/
		
		Don't put a check next to "Regex"
		
		Type:
			Simple Redirection 
		Method:
			Pass-through
			
		---------------------------------------------------
			
		Source URL:
			/consequat/aliquam/news/
		Target URL: 
			/category/consequat/aliquam/news/
		
		Don't put a check next to "Regex"
		
		Type:
			Simple Redirection 
		Method:
			Pass-through
			
		---------------------------------------------------
			
	NOTE: trailing slashes are a must, both in case 1. and 2.
	
5.	Now go activate the 'Decategorizer' plugin.
6.	You're done.

== More info ==

To see the end result before you decide to install (and, possibly, 
start writing redirections for all 150 of your categories), please 
visit [wordpress.skyphe.org](http://wordpress.skyphe.org) and 
browse around. You'll notice that no url contains "category" and still 
everything works just fine.

== Plans for the next version? ==

(added on August 4th 2008 at 10:50PM)

The only thing that comes to my mind is automatic creation of 
redirection rules when the plugin is activated, and addition of a new 
rule each time a new child category is created or modified.

I do intend to implement that.