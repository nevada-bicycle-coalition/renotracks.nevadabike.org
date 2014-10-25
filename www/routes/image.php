<?php

$root_dir = dirname( dirname( __DIR__ ) );

if ( isset( $_GET['name'] ) ) {
	$name = preg_replace( '/[^A-Za-z0-9-_]/', '', $_GET['name'] );
	$file = $root_dir . '/uploads/' . $name . '.jpg';
} else {
	$file = $root_dir . '/www/images/RENO_Web.png';
}

// open the file in a binary mode
$fp = fopen($file, 'rb');

header("Content-Type: image/jpg");
header("Content-Length: " . filesize($file));

// dump the picture and stop the script
fpassthru($fp);
fclose($fp);
exit;
