<?php
/*
Plugin Name: Decategorizer
Plugin URI: http://skyphe.org/code/wordpress/decategorizer/
Description: **THIS PLUGIN IS NOW OBSOLETE, PLEASE SWITCH TO 'WP NO CATEGORY BASE' PLUGIN**
Author: Bruno "Aesqe" Babic
Version: 0.7.1.2
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

// set up the constants
if ( !defined( 'WP_CONTENT_URL' ) )
	define( 'WP_CONTENT_URL', get_option('siteurl') . '/wp-content' );
if ( !defined( 'WP_CONTENT_DIR' ) )
	define( 'WP_CONTENT_DIR', ABSPATH . 'wp-content' );
if ( !defined( 'WP_PLUGIN_URL' ) )
	define( 'WP_PLUGIN_URL', WP_CONTENT_URL. '/plugins' );
if ( !defined( 'WP_PLUGIN_DIR' ) )
	define( 'WP_PLUGIN_DIR', WP_CONTENT_DIR . '/plugins' );

/* borrowed some plugin checking code from "Dashboard Fixer" by http://www.viper007bond.com in the first two functions below :) */

// check if Redirection is installed
function decategorizer_ri_checker()
{
	global $wp_version, $redirection, $wpdb, $table_prefix;
	
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
		$message  = '<div class="updated fade"><p>' . $message . '</p></div>';
		
		deactivate_plugins(plugin_basename(__FILE__));
	}

	echo $message;
}

// check if redirections exist; add them if they do not
function decategorizer_check_redirects()
{
	global $redirection, $wpdb, $table_prefix, $wp_rewrite, $wp_version, $pagenow;
	
	if( false === get_option("decategorizer_excluded_user_paths") )
		add_option("decategorizer_excluded_user_paths", "");
	
	$arg_one = func_get_arg(0);
	
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
			return $arg_one;
	}
	else
	{
		return $arg_one;
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
				$cr_message .= "Group created, ID option added<br />";
			}
			else
			{
				update_option("decategorizer_group_id", $deca_id);
				$cr_message .= "Group created, ID option updated<br />";
			}
		}
	}
	else
	{
		$deca_id = $wpdb->get_var( "SELECT `id` FROM " . $redirection_groups . " WHERE name='Decategorizer'" );

		if( false === get_option("decategorizer_group_id") )
		{	
			add_option("decategorizer_group_id", $deca_id);
			$cr_message .= "Group ID option added<br />";
		}
		else
		{
			update_option("decategorizer_group_id", $deca_id);
			$cr_message .= "Group ID option updated<br />";
		}
		
		$cr_message .= "Group exists (id: " . get_option("decategorizer_group_id") . ")<br />";
	}

	// handle function arguments

	if( 1 < func_num_args() )
	{
		$arg_two = func_get_arg(1);
		
		// if permalink structure is changed to default
		if( false !== strpos($arg_one, "%") && ("" == $arg_two || false === $arg_two || null === $arg_two) )
		{
			$delete_items = $wpdb->query("DELETE FROM " . $redirection_items . " WHERE `group_id`='" . get_option('decategorizer_group_id') . "'");
			return $arg_one;
		}
		
		// for versions prior to 2.8
		// force $wp_rewrite restart so get_category_link() uses the new structure
		if( 2.8 > (float)substr($wp_version, 0, 3) )
			$wp_rewrite->init();
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
		$page_for_posts = '|^' . $blog_folder . '/' . $page->post_name . '/';
	}
	
	if( false !== strpos(get_option("permalink_structure"), "%post_id%") )
		$date_archives_lead = "/date";
	else
		$date_archives_lead = "";
		
	$user_paths	= "";
	
	if( false !== get_option("decategorizer_excluded_user_paths") )
	{
		$up = str_replace("\r\n", "\n", trim(get_option("decategorizer_excluded_user_paths")));
		
		if( false !== strpos($up, "\n") )
		{
			$up = explode("\n", $up);
			$up = array_unique($up);
		}
		else
		{
			$up = array(get_option("decategorizer_excluded_user_paths"));
		}
		
		foreach( $up as $sup )
		{
			$sup = trim($sup);

			if( "" != $sup )
			{
				if( "" != $blog_folder && false === strpos($sup, $blog_folder) )
					$sup = $blog_folder . "/" . ltrim($sup, "/");
				
				$user_paths .= $sup . '|^';
			}
		}
	}
	
	// construct the regular expression
	$the_regexp = '(?!^' . 
					$blog_folder . $permalink_lead . $date_archives_lead . '/[\d]{4}/|^' . 	// skip if url starts with date
					$blog_folder . '/' . $tag_base . '/|^' . 								// or tag
					$blog_folder . $permalink_lead . '/author/|^' . 						// or author
					$blog_folder . '/search/|^' . 											// or if it's a search
					$blog_folder . '/comments/|^' . 										// or comments
					$blog_folder . '/' . $category_base . '/' . 							// or it has category_base in url -> do a 301
					$page_for_posts . '|^' . 												// or if it's home front page
					$user_paths  . 															// or the user added paths
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

	// get a list of category permalinks
	$deca_cat_list = get_categories( array() );
	
	foreach( $deca_cat_list as $dcat )
	{
		$cat_uris[] = get_category_link( $dcat->term_id );
	}

	// end if there are no categories
	// or, somehow, permalink structure is the default one
	if( empty( $cat_uris ) || false !== strpos($cat_uris[0], "?cat=") )
		return $arg_one;

	// add main regexp into database as a redirection item
	$values = "'', '" . addslashes( get_option('decategorizer_regexp') ) . "', '1', '', '', '', '" . get_option('decategorizer_group_id') . "','enabled','pass','0','" . $blog_folder . "/" . $category_base . "/\$1/page/\$2\$3\$4','url'";

	$result = $wpdb->query( "INSERT INTO " . $redirection_items . " 
						     (id, url, regex, position, last_count, last_access, group_id, status, action_type, action_code, action_data, match_type) 
							 VALUES(" . $values . ")" );
	
	$cr_message .= "Main regexp added to DB<br />";
	
	// add a 301 redirect for old permalinks
	if( "" != get_option("category_base") )
		$permalink_lead = "";
	
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
	
	if( false !== $debug && "options-permalink.php" == $pagenow )
		echo '<div class="updated fade"><p>' . $cr_message . '</p></div>';
		
	decategorizer_log($cr_message);
		
	return $arg_one;
}

function decategorizer_options()
{
	if(isset($_POST["decategorizer_excluded_user_paths"]))
	{
		update_option("decategorizer_excluded_user_paths", $_POST["decategorizer_excluded_user_paths"]);
	}
	
	if( function_exists("add_settings_section") )
	{
		add_settings_section("decategorizer_section", "Decategorizer options", "decategorizer_options_sections", "permalink");
	
		add_settings_field("decategorizer_excluded_user_paths", 
						   "<p style='margin-top: -0.3em;'>Here you can add page slugs/paths which should be excluded from Decategorizer paging redirection.</p>
							<p>If, for example, you have a page with custom queries and you're using 'category style paging' (/page/2/) for that page,
							add the path to that page here (without /page/number/).</p>
							<p>Each path must be on a new line.</p>
							<p>Example:<br /><code>/my-page-name/my-subpage/</code><br /><code>/my-page-two/subpage-three/</code></p>", 
						   create_function("", 'return decategorizer_options_output( array("name" => "decategorizer_excluded_user_paths") );'), 
						   "permalink", 
						   "decategorizer_section");
		register_setting("permalink", "decategorizer_excluded_user_paths");
	}
}
add_action("admin_init", "decategorizer_options");

function decategorizer_options_output($args)
{
	echo "<textarea cols='42' rows='14' name='" . $args["name"] . "' id='" . $args["name"] . "'>" . get_option($args["name"]) . "</textarea>";
}

function decategorizer_options_sections($what)
{
	echo "";
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
		decategorizer_log("Decategorizer group id option not found! Redirections added by the plugin have not been deleted!");
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

// write log
function decategorizer_log( $data )
{
	$data = date("Y-m-d@H:i:s") . "\n" . str_replace("<br />", "\n", $data) . "\n";
	$filename = str_replace("\\", "/", WP_PLUGIN_DIR) . "/" . basename(dirname( __FILE__ )) . "/decategorizer_log.txt";
	
	if( @file_exists($filename) )
		$data .= @implode("", @file($filename, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES)) . "\n";
	
	$file = @fopen($filename, "w+t");

	if( false !== $file )
	{		
		@fwrite($file, $data);
		
		if( 102400 < (filesize($filename) + strlen($data)) )
			@ftruncate($file, 102400);
	}
	
	@fclose($file);
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

if( 2.8 > (float)substr($wp_version, 0, 3) )
	add_action('update_option_permalink_structure', 'decategorizer_check_redirects', 100, 2);
else
	add_action('permalink_structure_changed', 		'decategorizer_check_redirects', 100, 2);

add_action('update_option_category_base', 						'decategorizer_check_redirects', 100, 2);
add_action('update_option_tag_base', 							'decategorizer_check_redirects', 100, 2);
add_action('update_option_home', 								'decategorizer_check_redirects', 100, 2);
add_action('update_option_decategorizer_excluded_user_paths',	'decategorizer_check_redirects', 100, 2);

add_action('edit_category', 		'decategorizer_check_redirects');
add_action('delete_category', 		'decategorizer_check_redirects');
add_action('wp_insert_post', 		'decategorizer_check_redirects');
add_action('delete_post', 			'decategorizer_check_redirects');

add_filter('category_link', 		'decategorizer', 100, 1);
add_filter('get_pagenum_link', 		'decategorizer', 100, 1);
add_filter('wp_list_categories',	'decategorizer', 100, 1);
add_filter('the_category',			'decategorizer', 100, 1);
?>