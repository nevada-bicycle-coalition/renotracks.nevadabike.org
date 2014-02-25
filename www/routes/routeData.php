<?php
include_once('CoordFactory.php');
include_once('TripFactory.php');

if($_POST['t']=="get_coords_by_trip"){	
	$obj = new CoordFactory();	
	echo $obj->getCoordsByTrip($_POST['q']);
} else if ($_POST['t']=="get_trip_ids"){
	$obj = new TripFactory();
	echo $obj->getTrips();
} else {
	//no-op
}

