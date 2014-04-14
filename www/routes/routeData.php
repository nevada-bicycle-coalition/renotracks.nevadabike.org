<?php
set_include_path( dirname( dirname( dirname( __FILE__ ) ) ) . '/include' . PATH_SEPARATOR . get_include_path() );
include_once('CoordFactory.php');
include_once('TripFactory.php');


if($_GET['t']=="get_coords_by_trip"){
	$max_age = 60*60*24;
	header( "Cache-Control: public,max-age=$max_age" );
	header( 'Expires: ' . gmdate( 'D, d M Y H:i:s', time() + $max_age ) . ' GMT' );
	header( 'Content-Type: application/json' );

	$obj = new CoordFactory();
	$coords = $obj->getCoordsByTrip($_GET['q']);
	header( 'Content-Length: '. strlen( $coords ) );
	echo $coords;
} else if ($_GET['t']=="get_trip_ids"){
	$obj = new TripFactory();
	echo $obj->getTrips();
} else {
	//no-op
}

