<?php
include_once('CoordFactory.php');
include_once('TripFactory.php');

$max_age = 60*60*24;
header( "Cache-Control: public,max-age=$max_age" );
header( 'Expires: ' . gmdate( 'D, d M Y H:i:s', time() + $max_age ) . ' GMT' );
header( 'Content-Type: application/json' );

if($_GET['t']=="get_coords_by_trip"){
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

