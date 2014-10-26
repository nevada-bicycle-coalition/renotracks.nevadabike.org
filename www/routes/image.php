<?php

$root_dir = dirname( dirname( __dir__ ) );

if ( isset( $_GET['name'] ) ) {
	$name = preg_replace( '/[^A-Za-z0-9-_]/', '', $_GET['name'] );
	$file = $root_dir . '/uploads/' . $name . '.jpg';

	if ( isset( $_GET['size'] ) and 'thumb' == $_GET['size']  ) {
		$file = thumb( $file );
	}

} else {
	$file = $root_dir . '/www/images/reno_web.png';
}

// open the file in a binary mode
$fp = fopen($file, 'rb');

header("content-type: image/jpg");
header("content-length: " . filesize($file));

// dump the picture and stop the script
fpassthru($fp);
fclose($fp);
exit;

function thumb( $file ) {
	$thumb_file = str_replace( '.jpg', '.thumb.jpg', $file );

	if ( file_exists( $thumb_file ) )
		return $thumb_file;

	$img = imagecreatefromjpeg($file);
	$width = imagesx( $img );
	$height = imagesy( $img );

	$thumb_width = 200;
	$thumb_height = floor( $height * ( $thumb_width / $width ) );

	$tmp_img = imagecreatetruecolor( $thumb_width, $thumb_height );

	imagecopyresampled( $tmp_img, $img, 0, 0, 0, 0, $thumb_width, $thumb_height, $width, $height );

	imagejpeg( $tmp_img, $thumb_file, 100 );
	imagedestroy($img);

	return $thumb_file;
}
