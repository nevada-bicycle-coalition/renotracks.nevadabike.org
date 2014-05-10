<?php

set_include_path( dirname( dirname( __FILE__ ) ) . '/include' . PATH_SEPARATOR . get_include_path() );
require_once "TripFactory.php";

if ( !isset( $argc ) )
	die( 'CLI dude' );

$trips = json_decode( TripFactory::getTrips() );

foreach ( $trips as $trip ) {
	if ( $trip->n_coord ) {
		if ( TripFactory::updateStats( $trip->id ) and !$trip->distance_mi )
			echo "Filled in trip {$trip->id}.\n";
	}
}
exit();

