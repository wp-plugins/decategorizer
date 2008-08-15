=== Decategorizer ===
Contributors: aesqe
Donate link: http://skyphe.org/code/wordpress/decategorizer/
Tags: category, category base, url, uri, links, permalinks, redirection
Requires at least: 2.5.0
Tested up to: 2.6.1
Stable tag: trunk

This plugin removes 'category_base' from your permalinks. It is meant
to be used with John Godley's 'Redirection' plugin.

== Description == 

Basically, it turns your URL's from something like 
"http://yourdomain/category/news/" to "http://yourdomain/news/". 

It is meant to be used together with 
[John Godley's 'Redirection'](http://urbangiraffe.com/plugins/redirection/) 
plugin. If used by itself, you'll just get lots of 
"Error 404 - Not found" pages.

I recommend using Redirection version 2.x.
(Tested with version 2.0.4)

**August 15th, 2008, ~9pm**
Actually, I'm not even sure if it works with older versions now :|
Will check tomorrow.

**Changelog:**

0.3		- added automatic creation of redirection 
rules. PLEASE NOTE: Although I've been testing the plugin for the past 
two hours, do try it at home first.
REMINDER TO SELF: addslashes(), INSERT, save your sanity...

0.2.1	- instead of setting back category_base to its default value, 
the current value is used, so one's permalinks don't get broken if they 
decide to deactivate the plugin. Sorry for that, current users :/
Please check your 'category_base' value.

0.2		- added the filter to 'category_link' as well. No more "/category/" 
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
4.	Activate 'Redirection' plugin.
5.	No further setup is required for Decategorizer, but you might 
	need to set up a few redirection rules in WordPress admin under 
	Manage->Redirection. 
	
	
	/**//**//**//**//**//**//**//**//**//**//**//**//**//**//**//**//
	
	UNLESS SOMETHING WEIRD HAPPENED DURING THE PLUGIN ACTIVATION, 
	STEPS 5.1 AND 5.2 DESCRIBED BELOW SHOULDN'T BE NECESSARY.
	I WON'T DELETE THE TEXT, JUST IN CASE SOMETHING WEIRD DID HAPPEN 
	DURING THE PLUGIN ACTIVATION :)
	
	/**//**//**//**//**//**//**//**//**//**//**//**//**//**//**//**//
	
	
	A note: replace YOUR-CATEGORY-BASE in target URL below with your 
	category base text. If you're using the default option, 
	"category", then put that in, instead (without quotes, of course).
	
	...and a question: Are TAG, AUTHOR, SEARCH and COMMENTS the only 
	things besides categories that have pagination? I based this on 
	the $wp_rewrite->rules output and haven't noticed pagination 
	anywhere else. Drop me an e-mail if I'm forgetting something, or
	just add it by yourself
	(put '|^/NEW_TYPE/' after '|^/commments/').

	5.1.	Source URL: 
			`(?!^/[\d]{4}/|^/tag/|^/author/|^/search/|^/comments/)^/(.+)/page/([\d]+)/`
			
			Important: if you're using Redirection plugin v 1.x, you 
			must replace the two + (plus) characters in regexp 
			with * (star) characters - just use the regexp below:
			
			`(?!^/[\d]{4}/|^/tag/|^/author/|^/search/|^/comments/)^/(.*)/page/([\d]*)/`
			
		Target URL:
			`/YOUR-CATEGORY-BASE/$1/page/$2/`
		
		Put a check next to "Regex"
		
		Type:
			Simple Redirection 
		Method:
			Pass-through

	5.2.	For each category that has a child category/categories, you 
		will have to add a new redirection rule/rules. 
		Sorry, it won't work any other way...

		For example: if your parent category is "consequat", and 
		it has two child categories - "aliquam" and "news" - your 
		redirection rules will look like this:
		
		Source URL:
			/consequat/aliquam/
		Target URL: 
			/YOUR-CATEGORY-BASE/consequat/aliquam/
		
		Don't put a check next to "Regex"
		
		Type:
			Simple Redirection 
		Method:
			Pass-through
			
		---------------------------------------------------
			
		Source URL:
			/consequat/aliquam/news/
		Target URL: 
			/YOUR-CATEGORY-BASE/consequat/aliquam/news/
		
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

To see the end result before you decide to install, please 
visit [wordpress.skyphe.org](http://wordpress.skyphe.org) and 
browse around. You'll notice that no url contains "category" and still 
everything works just fine.

I don't think I've ever written a readme before. Is it full of 
spelling and grammar errors? Too long? I suck at explaining how things 
work? Drop me an e-mail if something's bugging you about this text :)

== Plans for the next version? ==

(added on August 15th 2008 at 9:27PM)

No idea. Leave a comment here or on my site, or send me 
an e-mail if you would like to see something added to the plugin.

Maybe an admin page that holds a single checkbox to enable/disable 
the 'init' call? It might save a few milliseconds. I'm not kidding :)

