<?php
set_include_path( dirname( dirname( dirname( __FILE__ ) ) ) . '/include' . PATH_SEPARATOR . get_include_path() );
include_once('NoteFactory.php');


if($_GET['t']=="get_notes"){
	$max_age = 0;
	header( "Cache-Control: public,max-age=$max_age" );
	header( 'Expires: ' . gmdate( 'D, d M Y H:i:s', time() + $max_age ) . ' GMT' );
	header( 'Content-Type: application/json' );

	$obj = new NoteFactory();
	$notes = $obj->getNotes();
	header( 'Content-Length: '. strlen( $notes ) );
	echo $notes;
} else if ($_GET['t']=="get_trip_ids"){
	$obj = new TripFactory();
	echo $obj->getTrips();
} else {
	//no-op
}

