<?php
/*
Plugin Name: 	SVG Simple Inliner
Plugin URI:		https://github.com/dataninja/wp-plugin-svg-simple-inliner
Description: 	Inline SVG files before rendering for direct styling/animation of SVG elements using CSS/JS.
Version: 		0.1.0
Author: 		jenkin
Author URI: 	http://www.dataninja.it
Text Domain: 	svg-simple-inliner
Domain Path:	/languages
License: 		GPLv3
License URI:	http://www.gnu.org/licenses/gpl-3.0.html

	Copyright 2017 and beyond | Dataninja (email : info@dataninja.it)

*/

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Global variables
 */
$svgs_plugin_version = '0.1.0';									// for use on admin pages
$plugin_file = plugin_basename(__FILE__);							// plugin file for reference
define( 'DTNJ_SVGS_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );	// define the absolute plugin path for includes
define( 'DTNJ_SVGS_PLUGIN_URL', plugin_dir_url( __FILE__ ) );		// define the plugin url for use in enqueue
$dtnj_svgs_options = get_option('dtnj_svgs_settings');			// retrieve our plugin settings from the options table

/**
 * Replace img tag including svg image with svg content.
 *
 * @uses is_single()
 */
function dtnj_svg_inliner($content) {

	$ext = '.svg';

	if ( is_single() && !empty($content) ) {
	
		$dom = new DOMDocument();
		$dom->loadHTML($content);
		$imgs = $dom->getElementsByTagName('img');
		
		foreach ( $imgs as $img ) {
		
			$src = $img->getAttribute('src');
			if ( $src && substr( $src, -strlen( $ext ) ) == $ext ) {
				$svg = $dom->createDocumentFragment();
				$svg->appendXML( file_get_contents( ABSPATH . wp_make_link_relative( $src ) ) );
				$img->parentNode->replaceChild( $svg , $img );
			}
			
		}
		
		$content = $dom->saveHTML();
		
	}	
	
	return $content;
	
}

add_filter( 'the_content', 'dtnj_svg_inliner' );
