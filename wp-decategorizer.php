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
Version: 0.6
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
	$wp_version = substr($wp_version, 0, 3);
	$redirection_groups = $table_prefix . "redirection_groups";

	if ( 2.5 > (float)$wp_version )
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
	global $redirection, $wpdb, $table_prefix, $wp_rewrite, $wp_object_cache;

	$arg_one = func_get_arg(0);
	
	if( 1 < func_num_args() )
	{
		$arg_two = func_get_arg(1);
		
		// permalink changed to default
		if( false !== strpos($arg_one, "%") && ("" == $arg_two || false === $arg_two || null === $arg_two) )
		{
			$delete_items = $wpdb->query("DELETE FROM " . $table_prefix . "redirection_items" . " WHERE `group_id`='" . get_option('decategorizer_group_id') . "'");
			return;
		}
		else
		{
			$wp_rewrite->permalink_structure = $arg_two;
		}
	}
	
	$redirection_groups = $table_prefix . "redirection_groups";
	$redirection_items  = $table_prefix . "redirection_items";
	
	if ( !empty($redirection) && "redirection" == $redirection->plugin_name )
	{		
		if( $wpdb->get_var("SHOW TABLES LIKE '" . $redirection_groups . "'") != $redirection_groups )
			return false;
	}
	else
	{
		return false;
	}
	
	$pl = preg_match("#([^%]*)%(.*)#", get_option("permalink_structure"), $permalink_lead);

	if( "/" != $permalink_lead[1] )
		$permalink_lead = "/" . trim($permalink_lead[1], "/");
	else
		$permalink_lead = "";
	
	// get blog subfolder
	// returns empty string or /FOLDER or /FOLDER/SUBFOLDER, etc.
	$server_name = preg_match("#http://([^/]+)[/]?(.*)#", get_bloginfo('url'), $matches);	
	$blog_folder = trim( str_replace("http://" . $matches[1], "", get_bloginfo('url')) );
	
	$cr_message = "Blog folder : " . $blog_folder . "<br />";

	// get category_base and tag_base values
	$category_base 	= trim( get_option('category_base'), "/" );
	$tag_base 		= trim( get_option('tag_base'), "/" );
	$page_for_posts = get_option('page_for_posts');
	
	if( "" == $category_base )
		$category_base = "category";
	
	if( "" == $tag_base )
		$tag_base = "tags";

	if( "" != $page_for_posts )
	{
		$page = get_page(intval($page_for_posts));
		$page_for_posts = '|^/' . $page->post_name . '/';
	}
	
	$the_regexp = '(?!^' . $blog_folder . $permalink_lead . '/[\d]{4}/|^' . $blog_folder . '/' . $tag_base . '/|^' . $blog_folder . $permalink_lead . '/author/|^' . $blog_folder . '/search/|^' . $blog_folder . '/comments/|^' . $blog_folder . '/' . $category_base . '/' . $page_for_posts . '|^' . $blog_folder . '/page/)^' . $blog_folder . '/(.+)/page/([\d]+)([/]?)((\?.*)?)';
	
	// save the regexp in options table
	if( false === get_option('decategorizer_regexp') )
	{
		add_option('decategorizer_regexp', $the_regexp);
		$cr_message .= "Regexp added<br />";
	}
	else
	{
		update_option('decategorizer_regexp', $the_regexp);
		$cr_message .= "Regexp updated<br />";
	}

	// create 'Decategorizer' redirection group
	$groups = $wpdb->get_results( "SELECT `name`, `position` FROM " . $redirection_groups . " ORDER BY `position` ASC ");

	foreach( $groups as $group )
	{
		if( "Decategorizer" == $group->name )
			$group_exists = true;

		$position = intval($group->position) + 1;
	}

	if( true !== $group_exists )
	{
		$values = "'', 'Decategorizer', '1', '1', 'enabled', '" . $position . "'";
		$result = $wpdb->query( "INSERT INTO " . $redirection_groups . " (id, name, tracking, module_id, status, position) VALUES(" . $values . ")" );
		
		if( $result )
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
		}
	}

	/*/////////////////////////// create redirections ///////////////////////////*/
	
	$delete_query = "DELETE FROM " . $redirection_items . " WHERE `group_id`='" . get_option('decategorizer_group_id') . "'";
	$delete_items = $wpdb->query($delete_query);
	
	$cr_message .= "Previous items deleted<br />";
	
	// inspired by wp_list_categories()
	$r = array( "hide_empty" => false );
	$output = walk_category_tree( get_categories($r), 0, $r );

	$output = preg_match_all( "#<a href=[\"'](.*)[\"']\s+title#", $output, $matches );
	$cat_uris = $matches[1];

	// end if there are no populated subcategories
	if( empty( $cat_uris ) || false !== strpos($cat_uris[0], "?cat=") )
		return false;

	// add main regexp
	$values = "'', '" . addslashes( get_option('decategorizer_regexp') ) . "', '1', '', '', '', '" . get_option('decategorizer_group_id') . "','enabled','pass','0','" . $blog_folder . "/" . $category_base . "/\$1/page/\$2\$3\$4','url'";
	
	$insert_regexp = "
	INSERT INTO " . $redirection_items . 
	"(id, url, regex, position, last_count, last_access, group_id, status, action_type, action_code, action_data, match_type) 
	VALUES(" . $values . ")";

	$result = $wpdb->query( $insert_regexp );
	
	$cr_message .= "Main regexp added<br />";
	
	// add a 301 redirect for old permalinks
	$values = "'', '" . addslashes( "^" . $blog_folder . "/" . $category_base . "/(.+)" ) . "', 1, '', '', '', '" . get_option('decategorizer_group_id') . "', 'enabled', 'url', '301', '" . $blog_folder . "/\$1', 'url'";
	
	$insert_301 = "
	INSERT INTO " . $redirection_items . 
	"(id, url, regex, position, last_count, last_access, group_id, status, action_type, action_code, action_data, match_type) 
	VALUES(" . $values . ")";

	$result = $wpdb->query( $insert_301 );
	
	$cr_message .= "301s added<br />";
	
	/* add category redirections */
	
	$jk = 0;

	foreach( $cat_uris as $uri )
	{
		$redirs[$jk]['source'] = rtrim(str_replace( get_bloginfo('url'), "", $uri ), "/");
		$redirs[$jk]['target'] = $redirs[$jk]['source'];
		
		if( false !== strpos( $redirs[$jk]['source'], "/" . $category_base . "/" ) )
			$redirs[$jk]['source'] = str_replace( "/" . $category_base, "", rtrim($redirs[$jk]['source'], "/") );
		
		$redirs[$jk]['source'] = "^" . $blog_folder . str_replace("//", "/", $redirs[$jk]['source']) . "([/]?|/feed[/]?)((\\\?.+)?)$";
		$redirs[$jk]['target'] = $blog_folder . $redirs[$jk]['target'] . "$1$2";
		
		$jk++;
	}

	$cr_message .= "Walker URLs modified<br />";

	foreach( $redirs as $redir )
	{
		$values = "'', '" . $redir['source'] . "', '1', '', '', '', '" . get_option('decategorizer_group_id') . "', 'enabled', 'pass', '0', '" . $redir['target'] . "', 'url'";
		
		$insert_redirs = "
		INSERT INTO " . $redirection_items . 
		"(id, url, regex, position, last_count, last_access, group_id, status, action_type, action_code, action_data, match_type) 
		VALUES(" . $values . ")";
		
		$result = $wpdb->query( $insert_redirs );
		
		$cr_message .= "Redirection " . current($redir) . " added<br />";
	}
	
	//echo '<div class="updated fade"><p>' . $cr_message . '</p></div>';
	
	return $arg_one;
}

function decategorizer_deactivate()
{
	global $table_prefix, $wpdb;
	
	delete_option("decategorizer_regexp");
	delete_option("decategorizer_group_id");

	$delete_items = $wpdb->query("DELETE FROM " . $table_prefix . "redirection_items" . " WHERE `group_id`='" . get_option('decategorizer_group_id') . "'");
}

// the 'easy' part :)
function decategorizer( $output )
{
	$check_for_extension = preg_match("#^.*(\.+[^/]*)$#", get_option('permalink_structure'), $em);
	$permalink_extension = $em[1];
	
	if( !is_admin() )
	{
		if( "" == get_option('permalink_structure') )
			return false;
		
		$category_base = trim( get_option('category_base'), "/" );
		
		if( "" == $category_base )
			$category_base = "category";
		
		if( false !== strpos( $output, "/" . $category_base . "/" ) )
			$output = str_replace( "/" . $category_base, "", rtrim($output, "/") );
	
		if( 
		   ( "/" == substr(get_option('permalink_structure'), -1) || "" != $permalink_extension ) && 
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

add_action('update_option_permalink_structure', 'decategorizer_check_redirects', 10, 2);
add_action('update_option_category_base', 		'decategorizer_check_redirects', 10, 2);
add_action('update_option_tag_base', 			'decategorizer_check_redirects', 10, 2);
add_action('update_option_home', 				'decategorizer_check_redirects', 10, 2);

add_action('create_category', 		'decategorizer_check_redirects');
add_action('edit_category', 		'decategorizer_check_redirects');
add_action('delete_category', 		'decategorizer_check_redirects');

add_filter('category_link', 		'decategorizer', 100, 1);
add_filter('get_pagenum_link', 		'decategorizer', 100, 1);
add_filter('wp_list_categories',	'decategorizer', 100, 1);
add_filter('the_category',			'decategorizer', 100, 1);
?>