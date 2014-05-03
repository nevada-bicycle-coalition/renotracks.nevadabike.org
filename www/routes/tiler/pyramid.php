<?php
/**
 * Generating lower zoom level tiles from higher ones almost works, but they degrade.
 */
ini_set('memory_limit', '512M');

if ( !isset( $argc ) )
	die( 'CLI dude' );

require_once( 'GoogleMapUtility.php' );

//Root folder to store generated tiles
define( 'TILE_DIR', 'tile/' );
//Covered geographic areas
define( 'MIN_LAT', 39.4 );
define( 'MAX_LAT', 39.7 );
define( 'MIN_LNG', -120 );
define( 'MAX_LNG', -119.7 );
define( 'MAX_ZOOM', 16 );

$start_zoom = ( isset( $argv[1] ) ? intval( $argv[1] ) : MAX_ZOOM - 1 );

for( $zoom = $start_zoom; $zoom > 1; $zoom -= 1 ) {
	$from_tile = GoogleMapUtility::getTileXY( MAX_LAT, MIN_LNG, $zoom );
	$to_tile = GoogleMapUtility::getTileXY( MIN_LAT, MAX_LNG, $zoom );

	for ( $x = $from_tile->x; $x <= $to_tile->x; $x += 1 ) {
		for ( $y = $from_tile->y; $y <= $to_tile->y; $y += 1 ) {
			composeTile( $x, $y, $zoom, TILE_DIR );
		}
	}
}

function composeTile( $x, $y, $zoom, $dir, $empty_tile = 'empty.png' ) {

	$rect = GoogleMapUtility::getTileRect( $x, $y, $zoom );
	$source_zoom = $zoom + 1;
	$source_upper_left = GoogleMapUtility::getTileXY( $rect->y + $rect->height, $rect->x, $source_zoom );

	$size = GoogleMapUtility::TILE_SIZE;
	$composite_image = imagecreatetruecolor( $size * 2, $size * 2 );
	imagealphablending( $composite_image, false );
	imagesavealpha( $composite_image, true );

	$empty_count = 0;
	for ( $i = 0; $i <= 1; $i += 1 ) {
		for( $j = 0; $j <= 1; $j += 1 ) {
			$source_x = $source_upper_left->x + $i;
			$source_y = $source_upper_left->y + $j;
			$source_file = $dir . $source_zoom . '/' . $source_x . '_' . $source_y . '.png';
			if ( !file_exists( $source_file ) ) {
				$source_file = $empty_tile;
				$empty_count++;
			}
			$source_image = imagecreatefrompng( $source_file );
			imagecopy( $composite_image, $source_image, $i * $size, $j * $size, 0, 0, GoogleMapUtility::TILE_SIZE, GoogleMapUtility::TILE_SIZE );
			imagedestroy( $source_image );
		}
	}

	if ( 4 == $empty_count )
		return;

	$tilename = $dir . $zoom . '/' . $x . '_' . $y . '.png';
	if ( !file_exists( $dir . $zoom ) )
		mkdir( $dir . $zoom );

	$image = imagecreatetruecolor( $size, $size );
	imagealphablending( $image, false );
	imagesavealpha( $image, true );

	imagefilter( $composite_image, IMG_FILTER_PIXELATE, 2 );
	imagecopyresampled( $image, $composite_image, 0, 0, 0, 0, $size, $size, $size * 2, $size * 2 );
	imagedestroy( $composite_image );

	imagepng( $image, $tilename );
	imagedestroy( $image );

	var_dump( $tilename );
}
