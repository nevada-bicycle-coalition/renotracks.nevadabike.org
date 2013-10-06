<?php
require_once "TripFactory.php";
require_once "CoordFactory.php";

if ( empty( $_POST['user_id'] ) ) {
	echo "Missing user ID.";
	die();
}

if ( !empty( $_FILES['gpxfile']['error'] ) ) {
	echo  $_FILES['gpxfile']['error'];
	die();
}
if ( !is_readable( $_FILES['gpxfile']['tmp_name'] ) ) {
	echo 'Can\'t read uploaded file.';
	die();
}
$xml = simplexml_load_file( $_FILES['gpxfile']['tmp_name'] );
if ( !xml ) {
	echo 'Can\'t parse XML.';
	die();
}

$start = $xml->trk[0]->trkseg[0]->trkpt[0]->time;
if ( ! $trip = TripFactory::insert( $_POST['user_id'], $_POST['purpose'], $_POST['notes'], $start ) ) {
	echo "Trip creation failed.";
	die();
}

$count = 0;
foreach( $xml->trk as $trk ) {

	foreach ( $trk->trkseg as $trkseg ) {
		foreach ( $trkseg->trkpt as $trkpt ) {
			CoordFactory::insert(
				$trip->id,
				$trkpt->time,
				$trkpt['lat'],
				$trkpt['lon'],
				$trkpt->ele,
				$trkpt->speed
			);
			$stop = $trkpt->time;
			$count++;
		}
	}
}

header( 'Location: ../routes/?id=' . $trip->id );

