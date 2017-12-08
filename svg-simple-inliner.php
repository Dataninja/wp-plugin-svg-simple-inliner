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
 * Replace img tag including svg image with svg content.
 *
 * @uses is_single()
 */
function dtnj_svg_inliner($content) {

	$ext = '.svg';

	if ( !empty($content) ) {
	
		$dom = new DOMDocument();
 		libxml_use_internal_errors(true);
		$dom->loadHTML(mb_convert_encoding($content, 'HTML-ENTITIES', 'UTF-8'));
 		libxml_clear_errors();
		
		/* Conversion from a DOMNodeList (http://php.net/manual/en/class.domnodelist.php) to a simple Array.
		 * I can't iterate over the DOMNodeList directly, because I remove the node inside the loop and DOMNodeList behaves as a generator.
		 */
		$imgs = Array();
		foreach ( $dom->getElementsByTagName('img') as $img ) {
			$imgs[] = $img;
		}
		
		/* Now I can iterate over the array */
		foreach ( $imgs as $img ) {
		
			$current_site = get_blog_details(get_current_blog_id());
			$src = $img->getAttribute('src');
			$img_classes = explode( ' ', $img->getAttribute('class') );
			$container = $img->parentNode;
			
			if ( $src && substr( $src, -strlen( $ext ) ) == $ext ) {
			
				$fragment = $dom->createDocumentFragment();
				$svg_path = ABSPATH . str_replace( $current_site->path , "" , wp_make_link_relative( $src ) );
				$fragment->appendXML(
					preg_replace(
						'#<\?xml[^>]+\?>#',
						'',
						mb_convert_encoding( file_get_contents( $svg_path ), 'HTML-ENTITIES', 'UTF-8' )
					)
				);
				
				$container->replaceChild( $fragment , $img );
				
				$svg = $container->getElementsByTagName('svg')[0];
				
				$svg_classes = explode( ' ', $svg->getAttribute('class') );
				array_push( $svg_classes, 'svg-content' );
				$svg->setAttribute( 'class' , implode( ' ', array_unique( array_merge( $svg_classes , $img_classes ) ) ) );
				
				$container_classes = explode( ' ', $container->getAttribute('class') );
				array_push( $container_classes, 'svg-container' );
				$container->setAttribute( 'class', implode( ' ', array_unique($container_classes) ) );

			}
			
		}
		
		$svgs = $dom->getElementsByTagName('svg');
		foreach ( $svgs as $svg ) {
		
			if ( strpos( $svg->getAttribute('class') , 'svg-content' ) === false ) {
				continue;
			}
		
			$viewBox = array_map(
				'floatval',
				explode( ' ', $svg->getAttribute('viewBox') ?: "0 0 0 0" )
			);
			
			$dimensions = Array(
				floatval($svg->getAttribute('width') ?: '0'),
				floatval($svg->getAttribute('height') ?: '0')
			);
			
			if ( !$viewBox[2] && $dimensions[0] ) {
				$viewBox[2] = $dimensions[0];
			}
			
			if ( !$viewBox[3] && $dimensions[1] ) {
				$viewBox[3] = $dimensions[1];
			}
			
			$width = $viewBox[2] - $viewBox[0];
			$height = $viewBox[3] - $viewBox[1];
			$aspectRatio = $width / $height;
			
			$svg->removeAttribute('width');
			$svg->removeAttribute('height');
			$svg->setAttribute( 'viewBox', implode( ' ', $viewBox ) );
			$svg->setAttribute( 'preserveAspectRatio', 'xMidYMid meet' );
			
			$svg->parentNode->setAttribute( 'data-width', $width );
			$svg->parentNode->setAttribute( 'data-height', $height );
			$svg->parentNode->setAttribute( 'data-aspect-ratio', $aspectRatio );
			
		}
		
		$content = $dom->saveHTML();
		
	}	
	
	return $content;
	
}

add_filter( 'the_content', 'dtnj_svg_inliner', 999 );
