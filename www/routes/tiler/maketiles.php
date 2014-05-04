<?php
/**
 * Create map tiles from track data.
 */
ini_set( 'memory_limit', '2G' );

if ( !isset( $argc ) )
	die( 'CLI dude' );

set_include_path( dirname( dirname( dirname( dirname( __FILE__ ) ) ) ) . '/include' . PATH_SEPARATOR . get_include_path() );
require_once( 'CoordFactory.php' );
require_once( 'GoogleMapUtility.php' );
require_once( 'HeatMap.php' );

$defaults = array(
	'tile_dir' => 'tile/',
	'sw_lat' => 39.4,
	'sw_lng' => -120,
	'ne_lat' => 39.7,
	'ne_lng' => -119.7,
	'max_zoom' => 16,
	'start_zoom' => 16,
	'overwrite' => true,
	'spot_radius' => 0,
	'spot_dimming_level' => 75,
	'tile_size_factor' => 0.5,
	'zero_radius_final_zoom' => 17,
);
$long_options = array_map( function ( $name ) {
	return $name . '::';
}, array_keys( $defaults ) );

$options = getopt( '', $long_options );
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


$coords_result = CoordFactory::getCoordsInBoxResult( $options['sw_lat'], $options['sw_lng'], $options['ne_lat'], $options['ne_lng'] );
$trip_id_result = CoordFactory::getTripIDsInBoxResult( $options['sw_lat'], $options['sw_lng'], $options['ne_lat'], $options['ne_lng'] );
$meta = $options + array(
		'trip_count' => $trip_id_result->num_rows,
		'coordinate_count' => $coords_result->num_rows,
		'started' => date( 'c' ),
	);
$trip_id_result->free();
$coords_result->free();

file_put_contents( $options['tile_dir'] . 'meta.json', json_encode( $meta ) );

for ( $zoom = $options['start_zoom']; $zoom > 1; $zoom -= 1 ) {
	$from_tile = GoogleMapUtility::getTileXY( $options['ne_lat'], $options['sw_lng'], $zoom );
	$to_tile = GoogleMapUtility::getTileXY( $options['sw_lat'], $options['ne_lng'], $zoom );

	for ( $x = $from_tile->x; $x <= $to_tile->x; $x += 1 ) {
		for ( $y = $from_tile->y; $y <= $to_tile->y; $y += 1 ) {
			makeTile( $x, $y, $zoom, $options );
		}
	}
}

$meta['finished'] = date( 'c' );
file_put_contents( $options['tile_dir'] . 'meta.json', json_encode( $meta ) );

exit( 0 );


/**
 * Query datapoints at a Google tile address and create a tile image.
 *
 * @param int $X Google tile x coordinate.
 * @param int $Y Google tile y coordinate.
 * @param int $zoom Google zoom level (z coordinate).
 * @param array $args
 *          string tile_dir     output directory
 *          bool   overwrite    whether to replace existing tiles
 *          double sw_lat       minimum latitude of tile coverage
 *          double sw_lng       minimum longitude of tile coverage
 */
function makeTile( $X, $Y, $zoom, $args = array() ) {

	$dir = $args['tile_dir'] . $zoom;

	$tilename = $dir . '/' . $X . '_' . $Y . '.png';
	if ( $args['overwrite'] or !file_exists( $tilename ) ) {

		list( $swlat, $swlng, $nelat, $nelng ) = tileBox( $X, $Y, $zoom, $args );

		if (
			( $nelat <= $args['sw_lat'] ) ||
			( $swlat >= $args['ne_lat'] ) ||
			( $nelng <= $args['sw_lng'] ) ||
			( $swlng >= $args['ne_lng'] )
		) {
			//No geodata so skip
			return;
		}

		$result = CoordFactory::getCoordsInBoxResult( $swlat, $swlng, $nelat, $nelng );
		if ( 0 == $result->num_rows ) {
			$result->free();
			return;
		}

		$offset = $args['spot_radius'];
		$spots = array();
		while ( $coord = $result->fetch_object( 'Coord' ) ) {
			$point = GoogleMapUtility::getOffsetPixelCoords( $coord->latitude, $coord->longitude, $zoom, $X, $Y );
			//Count result only in the tile
			if (
				( $point->x > -$offset ) &&
				( $point->x < ( GoogleMapUtility::TILE_SIZE + $offset ) ) &&
				( $point->y > -$offset ) &&
				( $point->y < ( GoogleMapUtility::TILE_SIZE + $offset ) )
			) {
				$spots[] = new HeatMapPoint( $point->x, $point->y );
			}
		}
		$result->free();

		if ( empty( $spots ) ) {
			return;
		}
		if ( !file_exists( $dir ) ) {
			mkdir( $dir, 0705 );
		}
		echo "Create $tilename\n";
		flush();
		//All the magics is in HeatMap class :)
		if ( $args['spot_radius'] > 1 ) {
			$dimming = Max( 1, Min( 255, $args['spot_dimming'] ) );
		} else {
			$dimming = Max( 1, 255 >> ( $args['zero_radius_final_zoom'] - $zoom ) );
		}
		$im = HeatMap::createImage(
			$spots,
			GoogleMapUtility::TILE_SIZE,
			GoogleMapUtility::TILE_SIZE,
			heatMap::$WITH_ALPHA,
			$args['spot_radius'],
			$dimming,
			HeatMap::$GRADIENT_CLASSIC
		);
		unset( $spots );
		// store the tile
		imagepng( $im, $tilename );
		imagedestroy( $im );
		unset( $im );
	}
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

