<?php
/*
Plugin Name: Decategorizer
Plugin URI: http://skyphe.org/code/wordpress/decategorizer/
Description: Removes "/category/" (category_base text) from your site links.
It will do you no good by itself - it is meant to be used in conjuction with 
a plugin by John Godley (Urban Giraffe) called 'Redirection' 
(http://urbangiraffe.com/plugins/redirection/). Please read the complete 
tutorial on the plugin's homepage.
Author: Bruno "Aesqe" Babic
Version: 0.4
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

// get 'category_base' value and trim the forward and trailing slashes
$category_base = get_option('category_base');

if( "" == $category_base )
{
	$category_base = "category";
}

$category_base = trim( $category_base, "/" );



// -> check if redirections exist; add them if they don't

function check_redirects()
{
	global $table_prefix, $wpdb, $category_base;
	
	/* save the regexp in options table */
	if( !get_option('decategorizer_regexp') )
	{
		add_option('decategorizer_regexp', '(?!^/[\d]{4}/|^/tag/|^/author/|^/search/|^/comments/)^/(.+)/page/([\d]+)/');
	}
	else
	{
		update_option('decategorizer_regexp', '(?!^/[\d]{4}/|^/tag/|^/author/|^/search/|^/comments/)^/(.+)/page/([\d]+)/');
	}

	/*/////////////////////////// create 'Decategorizer' redirection group ///////////////////////////*/
	
	$redirection_groups = $table_prefix . "redirection_groups";
	$redirection_items  = $table_prefix . "redirection_items";
	$terms 				= $table_prefix . "terms";
	$term_taxonomy 		= $table_prefix . "term_taxonomy";
	
	$table_test = mysql_query("SHOW TABLE STATUS LIKE '" . $redirection_groups. "'") or die(mysql_error());
	
	if( 1 != mysql_num_rows($table_test) )
	{
		return false;
	}

	$query_groups = "SELECT name,position FROM " . $redirection_groups;
	$groups = $wpdb->get_results( $query_groups );
	
	foreach( $groups as $group )
	{
		if( strstr($group->name, "Decategorizer") )
		{
			$group_exists = "yes";
		}
		$position = $group->position;
	}

	if( "yes" != $group_exists )
	{
		$values = "'','Decategorizer','1','1','enabled','" . ($position+1) . "'";
		$insert_group = "INSERT INTO " . $redirection_groups . "(id,name,tracking,module_id,status,position) VALUES(" . $values . ")";
		$result = mysql_query( $insert_group ) or die( mysql_error() );
		
		if( $result )
		{
			$get_deca_id = "SELECT id FROM " . $redirection_groups . " WHERE name = 'Decategorizer'";
			$deca_id 	 = $wpdb->get_results( $get_deca_id );
			$deca_id 	 = $deca_id[0]->id;
			
			if( !get_option("decategorizer_group_id") )
			{	
				add_option("decategorizer_group_id", $deca_id);
			}
			else
			{
				update_option("decategorizer_group_id", $deca_id);
			}
		}
	}

	/*/////////////////////////// create redirections ///////////////////////////*/
	
	/*
	(id,url,regex,position,last_count,last_access,group_id,status,action_type,action_code,action_data,match_type)
	('',$source,0|1,$max_position,'','',get_option('redirection_group_id'),enabled,pass,0,$target,'url')
	*/
	
	/* add main regexp */
	
	$deca_regexp = get_option('decategorizer_regexp');
	
	$check_if_regexp = "SELECT url FROM " . $redirection_items . " WHERE url = '" . addslashes($deca_regexp) . "'";
	$regexp_r = $wpdb->get_results( $check_if_regexp );

	if( empty( $regexp_r ) )
	{
		$values = "'','" . addslashes( $deca_regexp ) . "',1,'','','','" . get_option('decategorizer_group_id') . "','enabled','pass',0,'/" . $category_base . "/$1/page/$2/','url'";
		
		$insert_regexp = "
		INSERT INTO " . $redirection_items . "(id,url,regex,position,last_count,last_access,group_id,status,action_type,action_code,action_data,match_type) 
		VALUES(" . $values . ")";
		
		$result = mysql_query( $insert_regexp ) or die( mysql_error() );
	}	
	
	$r = array();
	
	/* inspired by wp_list_categories() */
	$categories = get_categories( $r );
	$output = walk_category_tree( $categories, 0, $r );

	/*/////////////////////////// the section below is a bit messy and, like, not optimized at all 8D ///////////////////////////*/
	/*/////////////////////////// i'll clean it up for next version /////////////////////////////////////////////////////////////*/
	
	$output = preg_match_all( "%<a href=\"(.*)\" title%", $output, $matches );
	$cat_uris = $matches[1];
	
	$blog_slash_count = substr_count( get_bloginfo('url'), "/" );
	
	foreach( $cat_uris as $key => $value )
	{
		if( (substr_count( $value, "/" ) - $blog_slash_count) == 2 )
		{
			unset( $cat_uris[$key] );
		}
	}
	
	$jk = 0;
	
	if( empty( $cat_uris ) )
	{
		return false;
	}
	
	foreach( $cat_uris as $uri )
	{
		$redirs[$jk]['source'] = str_replace( get_bloginfo('url'), "", $uri );
		$redirs[$jk]['target'] = "/" . $category_base . $redirs[$jk]['source'];
		$jk++;
	}

	foreach( $redirs as $redir )
	{
		$values = "'','" . $redir['source'] . "',0,'','','','" . get_option('decategorizer_group_id') . "','enabled','pass',0,'" . $redir['target'] . "','url'";
		
		$test_q = "SELECT * from " . $redirection_items . " WHERE url = '" . $redir['source'] . "'";
		$test_r = $wpdb->get_results( $test_q );

		if(empty($test_r))
		{
			$insert_redirs = "
			INSERT INTO " . $redirection_items . "(id,url,regex,position,last_count,last_access,group_id,status,action_type,action_code,action_data,match_type) 
			VALUES(" . $values . ")";
			
			$result = mysql_query( $insert_redirs ) or die( mysql_error() );
		}		
	}
}


// -> the 'easy' part :)

function decategorizer( $output )
{
	global $category_base;
	
	if( strstr( $output, "/" . $category_base . "/" ) )				// search for 'category_base' in permalinks
	{
		$output = str_replace( "/" . $category_base, "", $output );	// remove 'category_base' from permalinks
	}
	
	return $output;
}

// i hope these four cover all the bases :)
add_action('init',					'check_redirects');
add_filter('category_link', 		'decategorizer', 100, 1);
add_filter('get_pagenum_link', 		'decategorizer', 100, 1);
add_filter('wp_list_categories',	'decategorizer', 100, 1);
add_filter('the_category',			'decategorizer', 100, 1);
?>