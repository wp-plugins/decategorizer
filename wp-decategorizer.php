<?php
/*
Plugin Name: Decategorizer
Plugin URI: http://skyphe.org/code/wordpress/decategorizer/
Description: Removes "/category/" (category_base text) from your site links.
It will do you no good by itself - it is meant to be used in conjuction with 
a plugin by John Godley (Urban Giraffe) called 'Redirection' 
(http://urbangiraffe.com/plugins/redirection/). Please read the complete 
tutorial on the plugin's homepage or in the plugin's readme.txt file.
Author: Bruno "Aesqe" Babic
Version: 0.6.2
Author URI: http://skyphe.org

////////////////////////////////////////////////////////////////////////////

    This program is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program.  If not, see <http://www.gnu.org/licenses/>.
	
////////////////////////////////////////////////////////////////////////////

*/

/* borrowed some plugin checking code from "Dashboard Fixer" by http://www.viper007bond.com in the first two functions below :) */

// check if Redirection is installed
function decategorizer_ri_checker()
{
	global $wp_version, $redirection, $wpdb, $redirection_groups, $table_prefix;
	
	$message = '';
	$redirection_groups = $table_prefix . "redirection_groups";

	if ( 2.5 > (float)substr($wp_version, 0, 3) )
	{
		$wpde_error = true;
		$message .= '"Decategorizer" requires WordPress version 2.5 or later, please upgrade.<br />';
	}
	else
	{
		if ( !empty($redirection) && "redirection" == $redirection->plugin_name )
		{
			if( $wpdb->get_var("SHOW TABLES LIKE '" . $redirection_groups . "'") != $redirection_groups )
			{
				$wpde_error = true;

				$message .= 'Redirection is installed, but its MySQL tables are not created yet &mdash; 
							 please visit the <a href="admin.php?page=redirection.php">plugin\'s options page</a> 
							 to create the tables and then come back here to activate "Decategorizer". 
							 Thank you for understanding :)<br />';
			}
			else
			{
				$wpde_error = false;
			}
		}
		else
		{
			$wpde_error = true;
			$message .= '<a href="http://urbangiraffe.com/plugins/redirection/">"Redirection" plugin</a> is not 
						 installed, please install it before activating "Decategorizer".<br />';
		}
	}
	
	if ( false !== $wpde_error )
	{
		$message .= '"Decategorizer" has been automatically <strong>deactivated</strong>.<br />';
		$message = '<div class="updated fade"><p>' . $message . '</p></div>';
		
		deactivate_plugins(plugin_basename(__FILE__));
	}

	echo $message;
}

// check if redirections exist; add them if they do not
function decategorizer_check_redirects()
{
	global $redirection, $wpdb, $table_prefix, $wp_rewrite, $wp_object_cache, $wp_version;
	
	$debug = false;
	
	if( false !== get_option("decategorizer_debug") )
		$debug = true;
	
	$cr_message .= "Group ID: " . get_option('decategorizer_group_id') . "<br />";
	
	$redirection_groups = $table_prefix . "redirection_groups";
	$redirection_items  = $table_prefix . "redirection_items";
	
	// no redirection plugin => exit
	if ( !empty($redirection) && "redirection" == $redirection->plugin_name )
	{		
		if( $wpdb->get_var("SHOW TABLES LIKE '" . $redirection_groups . "'") != $redirection_groups )
			return false;
	}
	else
	{
		return false;
	}
	
	// create 'Decategorizer' redirection group if it does not exist already
	$groups = $wpdb->get_results( "SELECT `name`, `position` FROM " . $redirection_groups . " ORDER BY `position` ASC ");

	foreach( $groups as $group )
	{
		if( "Decategorizer" == $group->name )
			$group_exists = true;

		$position = intval($group->position) + 1;
	}

	if( true !== $group_exists )
	{
		$cr_message .= "Group does not exist, creating...<br />";
		
		$values = "'', 'Decategorizer', '1', '1', 'enabled', '" . $position . "'";
		$result = $wpdb->query( "INSERT INTO " . $redirection_groups . " (id, name, tracking, module_id, status, position) VALUES(" . $values . ")" );
		
		if( $result )
		{
			$deca_id = $wpdb->get_var( "SELECT `id` FROM " . $redirection_groups . " WHERE name='Decategorizer'" );

			if( false === get_option("decategorizer_group_id") )
			{	
				add_option("decategorizer_group_id", $deca_id);
				$cr_message .= "Group created, ID added<br />";
			}
			else
			{
				update_option("decategorizer_group_id", $deca_id);
				$cr_message .= "Group created, ID updated<br />";
			}
		}
	}
	else
	{
		$deca_id = $wpdb->get_var( "SELECT `id` FROM " . $redirection_groups . " WHERE name='Decategorizer'" );

		if( false === get_option("decategorizer_group_id") )
		{	
			add_option("decategorizer_group_id", $deca_id);
			$cr_message .= "Group ID added<br />";
		}
		else
		{
			update_option("decategorizer_group_id", $deca_id);
			$cr_message .= "Group ID updated<br />";
		}
		
		$cr_message .= "Group exists (id: " . get_option("decategorizer_group_id") . ")<br />";
	}

	// handle function arguments
	$arg_one = func_get_arg(0);
	
	if( 1 < func_num_args() )
	{
		$arg_two = func_get_arg(1);
		
		// if permalink structure is changed to default
		if( false !== strpos($arg_one, "%") && ("" == $arg_two || false === $arg_two || null === $arg_two) )
		{
			$delete_items = $wpdb->query("DELETE FROM " . $redirection_items . " WHERE `group_id`='" . get_option('decategorizer_group_id') . "'");
			echo '<div class="updated fade"><p>Decategorizer redirections deleted.</p></div>';
			return;
		}
		
		// for versions prior to 2.8
		if( 2.8 >= (float)substr($wp_version, 0, 3) )
		{
			// force $wp_rewrite restart
			// so get_category_link() uses the new structure
			$wp_rewrite->init();
		}
	}
	

	
	// get blog subfolder
	// returns empty string or /FOLDER or /FOLDER/SUBFOLDER, etc.
	$server_name = preg_match("#http://([^/]+)[/]?(.*)#", get_bloginfo('url'), $matches);	
	$blog_folder = trim( str_replace("http://" . $matches[1], "", get_bloginfo('url')) );
	
	$cr_message .= "Blog subfolder : " . $blog_folder . "<br />";

	// get category_base, tag_base and page_for_posts values
	$category_base 	= trim( get_option('category_base'), "/" );
	$tag_base 		= trim( get_option('tag_base'), "/" );
	$page_for_posts = get_option('page_for_posts');
	
	$ccb = $category_base;
	
	// get permalink structure
	$permalink_lead = substr(get_option("permalink_structure"), 0, strpos(get_option("permalink_structure"), '%'));
	
	// check for leading string
	if( "/" != $permalink_lead )
		$permalink_lead = "/" . trim($permalink_lead, "/");
	else
		$permalink_lead = "";
	
	// take care of defaults
	if( "" == $category_base )
	{
		$category_base = "category";
		$ccb = "category";
		if("" != $permalink_lead)
			$category_base = trim($permalink_lead, "/") . "/category";
	}
	
	if( "" == $tag_base )
	{
		$tag_base = "tag";
		if("" != $permalink_lead)
			$tag_base = trim($permalink_lead, "/") . "/tag";
	}
	
	if( "" != $page_for_posts )
	{
		$page = get_page(intval($page_for_posts));
		$page_for_posts = '|^/' . $page->post_name . '/';
	}
	
	if( false !== strpos(get_option("permalink_structure"), "%post_id%") )
		$date_archives_lead = "/date";
	else
		$date_archives_lead = "";
	
	// construct the regular expression
	$the_regexp = '(?!^' . 
					$blog_folder . $permalink_lead . $date_archives_lead . '/[\d]{4}/|^' . 	// skip if url starts with date
					$blog_folder . '/' . $tag_base . '/|^' . 								// or tag
					$blog_folder . $permalink_lead . '/author/|^' . 						// or author
					$blog_folder . '/search/|^' . 											// or if it's a search
					$blog_folder . '/comments/|^' . 										// or comments
					$blog_folder . '/' . $category_base . '/' . 							// or it has category_base in url -> do a 301
					$page_for_posts . '|^' . 												// or if it's home front page
					$blog_folder . '/page/)^';												// or a paged home page
				
				if( "" != $permalink_lead && "" == get_option("category_base") )
					$the_regexp .= $blog_folder . $permalink_lead . '/(.+)/page/([\d]+)([/]?)((\?.*)?)';
				else
					$the_regexp .= $blog_folder . '/(.+)/page/([\d]+)([/]?)((\?.*)?)';
	
	// save the regexp in options table
	if( false === get_option('decategorizer_regexp') )
	{
		add_option('decategorizer_regexp', $the_regexp);
		$cr_message .= "Regexp option added<br />";
	}
	else
	{
		update_option('decategorizer_regexp', $the_regexp);
		$cr_message .= "Regexp option updated<br />";
	}

	/*/////////////////////////// create redirections ///////////////////////////*/
	
	// delete old items
	$delete_items = $wpdb->query( "DELETE FROM " . $redirection_items . " WHERE `group_id`='" . get_option('decategorizer_group_id') . "'" );
	$cr_message .= "Previous items deleted<br />";

	// inspired by wp_list_categories()
	// gets us a html list of all categories
	$deca_cat_list = get_categories( array("hide_empty" => false) );
	
	foreach( $deca_cat_list as $dcat )
	{
		$cat_uris[] = get_category_link( $dcat->term_id );
	}

	// end if there are no categories
	// or, somehow, permalink structure is the default one
	if( empty( $cat_uris ) || false !== strpos($cat_uris[0], "?cat=") )
		return false;

	// add main regexp into database as a redirection item
	$values = "'', '" . addslashes( get_option('decategorizer_regexp') ) . "', '1', '', '', '', '" . get_option('decategorizer_group_id') . "','enabled','pass','0','" . $blog_folder . "/" . $category_base . "/\$1/page/\$2\$3\$4','url'";

	$result = $wpdb->query( "INSERT INTO " . $redirection_items . " 
						     (id, url, regex, position, last_count, last_access, group_id, status, action_type, action_code, action_data, match_type) 
							 VALUES(" . $values . ")" );
	
	$cr_message .= "Main regexp added to DB<br />";
	
	// add a 301 redirect for old permalinks
	$values = "'', '" . addslashes( "^" . $blog_folder . "/" . $category_base . "/(.+)" ) . "', 1, '', '', '', '" . get_option('decategorizer_group_id') . "', 'enabled', 'url', '301', '" . $blog_folder . $permalink_lead . "/\$1', 'url'";

	$result = $wpdb->query( "INSERT INTO " . $redirection_items . " 
						     (id, url, regex, position, last_count, last_access, group_id, status, action_type, action_code, action_data, match_type) 
						     VALUES(" . $values . ")" );
	
	$cr_message .= "301s added<br />";
	
	// add category redirections
	
	$jk = 0;

	foreach( $cat_uris as $uri )
	{
		$redirs[$jk]['source'] = rtrim(str_replace( get_bloginfo('url'), "", $uri ), "/");
		$redirs[$jk]['target'] = $redirs[$jk]['source'];
		
		if( false !== strpos( $redirs[$jk]['source'], "/" . trim($ccb, "/") . "/" ) )
			$redirs[$jk]['source'] = str_replace( "/" . trim($ccb, "/"), "", rtrim($redirs[$jk]['source'], "/") );
		
		$redirs[$jk]['source'] = "^" . $blog_folder . preg_replace("#/+#", "/", $redirs[$jk]['source']) . "([/]?|/feed[/]?)((\\\?.+)?)$";
		$redirs[$jk]['target'] = $blog_folder . $redirs[$jk]['target'] . "$1$2";
		
		$jk++;
	}

	$cr_message .= "Redirections configured<br />";

	foreach( $redirs as $redir )
	{
		$values = "'', '" . $redir['source'] . "', '1', '', '', '', '" . get_option('decategorizer_group_id') . "', 'enabled', 'pass', '0', '" . $redir['target'] . "', 'url'";
		
		$result = $wpdb->query( "INSERT INTO " . $redirection_items . " 
							     (id, url, regex, position, last_count, last_access, group_id, status, action_type, action_code, action_data, match_type) 
								 VALUES(" . $values . ")" );
		
		$cr_message .= "Redirection " . current($redir) . " added<br />";
	}
	
	if( false === $debug )
		$cr_message = "Decategorizer redirections updated.";
	
	echo '<div class="updated fade"><p>' . $cr_message . '</p></div>';
	
	return $arg_one;
}

function decategorizer_deactivate()
{
	global $table_prefix, $wpdb;

	if( false !== get_option('decategorizer_group_id') )
	{
		$delete_items = $wpdb->query("DELETE FROM " . $table_prefix . "redirection_items WHERE `group_id`='" . get_option('decategorizer_group_id') . "'");
																														   
		delete_option("decategorizer_regexp");
		delete_option("decategorizer_group_id");
	}
	else
	{
		echo "Decategorizer group id option not found! Redirections added by the plugin have not been deleted!";
	}
}

// remove category_base value from permalinks
// and take care of the trailing slash
function decategorizer( $output )
{
	if( !is_admin() )
	{
		$ps = get_option('permalink_structure');
		
		if( "" == $ps )
			return $output;
			
		$category_base = trim( get_option('category_base'), "/" );

		if( "" == $category_base )
			$category_base = "category";
		
		if( false !== strpos( $output, "/" . $category_base . "/" ) )
			$output = str_replace( "/" . $category_base, "", rtrim($output, "/") );
			
		$check_for_extension = preg_match("#^.*(\.+[^/]*)$#", $ps, $em);
		$permalink_extension = $em[1];
		
		if( 
		   ( "/" == substr($ps, -1) || 
			 "" != $permalink_extension ) && 
		   "/" != substr(trim($output), -1) && 
			">" != substr(trim($output), -1) && 
			!is_search()
		  )
		{
			$output .= "/";
		}
	}
	
	return $output;
}

/* hooks */

// print notices on top of admin pages
function decategorizer_redirection_installed_check()
{
	add_action( 'admin_notices', 'decategorizer_ri_checker' );
}

// deactivation
add_action('deactivate_' . basename(dirname( __FILE__ )) . '/' . basename(__FILE__), 'decategorizer_deactivate');

// activation
register_activation_hook(__FILE__, 'decategorizer_redirection_installed_check');
register_activation_hook(__FILE__, 'decategorizer_check_redirects');

//...
add_action('admin_head', 'decategorizer_redirection_installed_check');

add_action('update_option_permalink_structure', 'decategorizer_check_redirects', 100, 2);
add_action('update_option_category_base', 		'decategorizer_check_redirects', 100, 2);
add_action('update_option_tag_base', 			'decategorizer_check_redirects', 100, 2);
add_action('update_option_home', 				'decategorizer_check_redirects', 100, 2);
add_action('permalink_structure_changed', 		'decategorizer_check_redirects', 100, 2);

add_action('create_category', 		'decategorizer_check_redirects');
add_action('edit_category', 		'decategorizer_check_redirects');
add_action('delete_category', 		'decategorizer_check_redirects');

add_filter('category_link', 		'decategorizer', 100, 1);
add_filter('get_pagenum_link', 		'decategorizer', 100, 1);
add_filter('wp_list_categories',	'decategorizer', 100, 1);
add_filter('the_category',			'decategorizer', 100, 1);
?>