=== Decategorizer ===
Contributors: aesqe
Donate link: http://skyphe.org/code/wordpress/decategorizer/
Tags: category base, url, permalinks
Requires at least: 2.5.0
Tested up to: 2.5.1
Stable tag: trunk

This plugin adds a filter (which removes category_base text from 
permalinks) on get_pagenum_link, wp_list_categories and the_category 
functions.

== Description == 

Basically, it turns your URL's from something like 
"http://yourdomain/category/news/" to "http://yourdomain/news/". 

It is meant to be used together with Urban Giraffe's "Redirection" 
plugin [Redirection](http://urbangiraffe.com/plugins/redirection/) 
and a few redirections already set up. If used by itself, you'll 
just get lots of "Error 404 - Not found" pages.

== Installation == 

1.	Place the whole 'decategorizer' folder into your wordpress' 
	installation folder, under 'wp-content\plugins'.
2.	Go to WordPress administration->plugins page and make sure that 
	the plugin's name is on the list, but do not activate it just 
	yet.
3.	Install the Urban Giraffe's Redirection plugin the same way, 
	if you haven't already done so.
4.	No further setup is required for Decategorizer, but you will 
	need to set up a few redirection rules in WordPress admin under 
	Manage->Redirection. 

	(Note: replace YOUR_CATEGORY_BASE in target URL with your 
	category base text. If you're using the default option, 
	"category", then put that instead (without quotes).) 

	1.	Source URL: 
		/([12][09][012789][0-9][-].*|(?![12][09][012789][0-9].*))/(.*)/(.*)/page/(.*)/
		Target URL:
		/YOUR_CATEGORY_BASE/$1/$2/$3/page/$4/
		Put a check next to "Regex" 

	2.	Source URL:
		/([12][09][012789][0-9][-].*|(?![12][09][012789][0-9].*))/(.*)/page/(.*)/
		Target URL:
		/YOUR_CATEGORY_BASE/$1/$2/page/$3/
		Put a check next to "Regex" 

	3.	Source URL: 
		/([12][09][012789][0-9][-].*|(?![12][09][012789][0-9].*))/page/(.*)/ 
		Target URL:
		/YOUR_CATEGORY_BASE/$1/page/$2/
		Put a check next to "Regex" 

	4.	For each subcategory, you will have to add a new 
		redirection rule. 
		Sorry, it won't work any other way :( At least it's 
		something you have to do only once and let it be. 

		For example: if your main category is "consequat", and 
		you have two subcategories, "aliquam" and "news" your 
		redirection rule will look like this:
		
		Source URL: /consequat/aliquam/news/
		Target URL: /YOUR_CATEGORY_BASE/consequat/aliquam/news/
		Do not place a check next to "Regex" or it won't work.
		
		Just one subcategory:
		
		Source URL: /consequat/aliquam/
		Target URL: /YOUR_CATEGORY_BASE/consequat/aliquam/
		And so on.
5.	Now go activate the Decategorizer plugin.
6.	That should be it.

== More info ==

To see the end result before you decide to install, please visit 
[wordpress.skyphe.org](http://wordpress.skyphe.org) and 
browse around. You'll notice that no url contains "category" and still 
everything works just fine. 