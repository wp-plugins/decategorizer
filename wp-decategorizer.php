<?php
/*
Plugin Name: Decategorizer
Plugin URI: http://skyphe.org/code/wordpress/decategorizer/
Description: Removes "/category/" (category_base text) from your site links. It will do you no good by itself - it is meant to be used in conjuction with a plugin by Urban Giraffe called 'Redirection' (http://urbangiraffe.com/plugins/redirection/). Please read the complete tutorial on the plugin's homepage.
Author: Bruno "Aesqe" Babic
Version: 0.1
Author URI: http://skyphe.org
////////////////////////////////////////////////////////////////////
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
////////////////////////////////////////////////////////////////////
*/

$cat_base = $option->category_base;

if(empty($cat_base))
{
	$cat_base = "category";
}

function ae_decategorizer($permalink)
{
	global $cat_base;

	if(strstr($permalink,"/".$cat_base."/"))
	{
		$permalink = str_replace($cat_base."/","",$permalink);
	}
	return $permalink;
}

add_filter('get_pagenum_link', 'ae_decategorizer');
add_filter('wp_list_categories','ae_decategorizer');
add_filter('the_category','ae_decategorizer');
?>