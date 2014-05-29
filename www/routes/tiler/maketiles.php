<?php
/**
 * Create map tiles from track data.
 */
ini_set( 'memory_limit', '200M' );

if ( !isset( $argc ) )
	die( 'CLI dude' );

set_include_path( dirname( dirname( dirname( dirname( __FILE__ ) ) ) ) . '/include' . PATH_SEPARATOR . get_include_path() );
require_once( 'CoordFactory.php' );
require_once( 'GoogleMapUtility.php' );
require_once( 'HeatMap.php' );

$defaults = array(
	'tile_dir' => 'all/',
	'sw_lat' => 39.4,
	'sw_lng' => -120,
	'ne_lat' => 39.7,
	'ne_lng' => -119.6,
	'max_zoom' => 16,
	'start_zoom' => 16,
	'spot_radius' => 0,
	'spot_dimming' => 75,
	'tile_size_factor' => 0.5,
	'zero_radius_final_zoom' => 17,
);
$long_options = array_map( function ( $name ) {
	return $name . '::';
}, array_keys( $defaults ) );

$options = getopt( 'v', $long_options );
foreach ( $defaults as $name => $value ) {
	if ( isset( $options[$name] ) ) {
		if ( is_bool( $defaults[$name] ) )
			$options[$name] = !preg_match( '/(n|no|off|false|0)/i', $options[$name] );
		else if ( is_numeric( $defaults[$name] ) )
			$options[$name] = floatval( $options[$name] );
	} else {
		$options[$name] = $defaults[$name];
	}
}
$options['tile_dir'] = rtrim( $options['tile_dir'], '/' ) . '/';

$meta = startMetaFile( $options );

$from_tile = GoogleMapUtility::getTileXY( $options['ne_lat'], $options['sw_lng'], $options['start_zoom'] );
$to_tile = GoogleMapUtility::getTileXY( $options['sw_lat'], $options['ne_lng'], $options['start_zoom'] );

$count = 0;
$total = ( $to_tile->x - $from_tile->x ) * ( $to_tile->y - $from_tile->y );
for ( $x = $from_tile->x; $x <= $to_tile->x; $x += 1 ) {
	for ( $y = $from_tile->y; $y <= $to_tile->y; $y += 1 ) {
		$count++;
		if ( isset( $options['v'] ) )
			echo 'Making tile ' . $count . ' of ' . $total . "\n";
		makeGrayscaleTiles( $x, $y, $options['start_zoom'], $options );
	}
}

$dir_iterator =  new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $options['tile_dir'] ) );
foreach ( $dir_iterator as $name => $fileinfo ) {
	if ( strpos( $name, '.png' ) ) {
		$im = imagecreatefrompng( $name );
		imagealphablending( $im, false );
		imagesavealpha( $im, true );
		HeatMap::applyColorScale( $im, HeatMap::$WITH_ALPHA, HeatMap::$GRADIENT_CLASSIC );
		imagepng( $im, $name );
		imagedestroy( $im );
	}
}

finishMetaFile( $meta );

exit( 0 );


/**
 * Query the database for stats on the overall tile area and write them to a meta.json
 * file in the root of the tile directory tree with a start time.
 *
 * @param array $options
 * @return array metadata
 */
function startMetaFile( $options ) {
	$coords_count = CoordFactory::getCoordsInBoxCount( $options['sw_lat'], $options['sw_lng'], $options['ne_lat'], $options['ne_lng'] );
	$trip_id_result = CoordFactory::getTripIDsInBoxResult( $options['sw_lat'], $options['sw_lng'], $options['ne_lat'], $options['ne_lng'] );
	$meta = $options + array(
			'trip_count' => $trip_id_result->num_rows,
			'coordinate_count' => $coords_count,
			'started' => date( 'c' ),
		);
	$trip_id_result->free();

	if ( !file_exists( $options['tile_dir'] ) )
		mkdir( $options['tile_dir'], 0705 );
	file_put_contents( $options['tile_dir'] . 'meta.json', json_encode( $meta ) );
	return $meta;
}

/**
 * Re-write the metadata file with a finish time.
 * @param $meta
 */
function finishMetaFile( $meta ) {
	$meta['finished'] = date( 'c' );
	file_put_contents( $meta['tile_dir'] . 'meta.json', json_encode( $meta ) );
}

function makeGrayscaleTiles( $x, $y, $zoom, $args ) {

	list( $swlat, $swlng, $nelat, $nelng ) = tileBox( $x, $y, $zoom, $args );

	if (
		( $nelat <= $args['sw_lat'] ) ||
		( $swlat >= $args['ne_lat'] ) ||
		( $nelng <= $args['sw_lng'] ) ||
		( $swlng >= $args['ne_lng'] )
	) {
		//No geodata so skip
		return;
	}

	$coords = CoordFactory::getCoordsInBox( $swlat, $swlng, $nelat, $nelng );
	if ( 0 == count( $coords ) ) {
		return;
	}

	$center_lat = ($nelat + $swlat)/2;
	$center_lng = ($nelng + $swlng)/2;
	while( $zoom > 1 ) {
		drawOnTile( $coords, $x, $y, $zoom, $args );
		$zoom--;
		$next_tile = GoogleMapUtility::getTileXY( $center_lat, $center_lng, $zoom );
		$x = $next_tile->x;
		$y = $next_tile->y;
	}
}

/**
 * Draw data on a map tile.
 *
 * @param array $coords
 * @param int $x Google tile x coordinate.
 * @param int $y Google tile y coordinate.
 * @param int $zoom Google zoom level (z coordinate).
 * @param array $args
 */
function drawOnTile( $coords, $x, $y, $zoom, $args ) {

	$dir = $args['tile_dir'] . $zoom;

	$y_dir = $dir . '/'. $y;
	$tilename = $y_dir . '/' . $x . '.png';
	$im = file_exists( $tilename ) ? imagecreatefrompng( $tilename ) : null;

	$size = GoogleMapUtility::TILE_SIZE;
	$offset = $args['spot_radius'];
	$spots = array();
	foreach ( $coords as $coord ) {
		$point = GoogleMapUtility::getOffsetPixelCoords( $coord->latitude, $coord->longitude, $zoom, $x, $y );
		//Count result only in the tile
		if (
			( $point->x > -$offset ) &&
			( $point->x < ( $size + $offset ) ) &&
			( $point->y > -$offset ) &&
			( $point->y < ( $size + $offset ) )
		) {
			$spots[] = new HeatMapPoint( $point->x, $point->y );
		}
	}

	if ( empty( $spots ) ) {
		return;
	}
	if ( !file_exists( $dir ) ) {
		mkdir( $dir, 0705 );
	}
	if ( !file_exists( $y_dir ) ) {
		mkdir( $y_dir, 0705 );
	}
	flush();

	$dimming = Max( 1, Min( 255, $args['spot_dimming'] ) );
	$accrual_step = Max( 1, 255 >> ( $args['zero_radius_final_zoom'] - $zoom ) );
	$im = HeatMap::renderGrayscaleImage(
		$spots,
		$size,
		$size,
		heatMap::$WITH_ALPHA,
		$args['spot_radius'],
		$dimming,
		$accrual_step,
		$im
	);
	unset( $spots );
	// store the tile
	imagepng( $im, $tilename );
	imagedestroy( $im );
	unset( $im );
}

function tileBox( $X, $Y, $zoom, $args ) {
	$rect = GoogleMapUtility::getTileRect( $X, $Y, $zoom );

	if ( $args['spot_radius'] > 1 ) {

		//A tile can contain part of a spot with center in an adjacent tile (overlaps).
		//Knowing the spot radius (in pixels) and zoom level, a smart way to process tiles would be to compute the box
		// (in decimal degrees) containing only spots that can be drawn on current tile. We choose a simpler solution
		// by increeasing  geo bounds by 2*TILE_SIZE_FACTOR whatever the zoom level and spot radius.
		$extend_X = $rect->width * $args['size_factor']; //in decimal degrees
		$extend_Y = $rect->height * $args['size_factor']; //in decimal degrees
		$box = array(
			$rect->y - $extend_Y,
			$rect->x - $extend_X,
			$args['sw_lat'] + $rect->height + 2 * $extend_Y,
			$args['sw_lng'] + $rect->width + 2 * $extend_X,
		);

	} else {

		$box = array(
			$rect->y,
			$rect->x,
			$rect->y + $rect->height,
			$rect->x + $rect->width,
		);
	}

	return $box;
}

