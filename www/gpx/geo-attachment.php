<?php /*
Plugin Name: Geo Attachment
Plugin URI: 
Description: Transform an uploaded GPX file to KML with stats.
Version: 0.2
Author: Dylan Kuhn
Author URI: http://www.cyberhobo.net/
Minimum WordPress Version Required: 2.6
*/

/*
Copyright (c) 2005-2009 Dylan Kuhn

This program is free software; you can redistribute it
and/or modify it under the terms of the GNU General Public
License as published by the Free Software Foundation;
either version 2 of the License, or (at your option) any
later version.

This program is distributed in the hope that it will be
useful, but WITHOUT ANY WARRANTY; without even the implied
warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR
PURPOSE. See the GNU General Public License for more
details.
*/

/**
 * The Geo Attachment class & singleton
 */
if ( !class_exists( 'GeoAttachment' ) ) {

require_once( 'GeoCalc.class.php' );

class GeoAttachment {
	var $dir_path;
	var $url_path;
	var $basename;
	var $attachment_file;
	var $parser;
	var $writer;
	var $custom_values = array();
	var $activity = 'hiking';
	var $activity_maps = array( 
		'hiking' => array(
			'distance_mi' => array( 'meta_key' => 'miles_hiked', 'format' => '%01.1f' ),
			'hours' => array( 'meta_key' => 'Hours Tracked', 'format' => '%01.1f' ),
			'elevation_gain_ft' => array( 'meta_key' => 'feet_elevation_gain', 'format' => '%d' ),
			'top_ft' => array( 'meta_key' => 'Highpoint (ft)', 'format' => '%d' ),
			'bottom_ft' => array( 'meta_key' => 'Lowpoint (ft)', 'format' => '%d' ),
		),
		'biking' => array(
			'distance_mi' => array( 'meta_key' => 'miles_biked',   'format' => '%01.1f' ),
			'hours' => array( 'meta_key' => 'Hours Tracked', 'format' => '%01.1f' ),
			'elevation_gain_ft' => array( 'meta_key' => 'Bike Elevation Gain (Ft)', 'format' => '%d' ),
			'top_ft' => array( 'meta_key' => 'Highpoint (ft)', 'format' => '%d' ),
			'bottom_ft' => array( 'meta_key' => 'Lowpoint (ft)', 'format' => '%d' )
		)
	);

	/**
	 * PHP4 Constructor
	 */
	function GeoAttachment() {

		// Initialize members
		$this->dir_path = dirname( __FILE__ );
		$this->basename = plugin_basename( __FILE__ );
		$dir_name = substr( $this->basename, 0, strpos( $this->basename, '/' ) );
		$this->url_path = trailingslashit( WP_PLUGIN_URL ) . $dir_name;
		load_plugin_textdomain( 'GeoAttachment', 'wp-content/plugins/'.$dir_name, $dir_name );

		// Add our tag handler to run before Geo Mashup's
		add_filter( 'content_save_pre', array( &$this, 'content_save_pre' ), 8 );

		// Save post makes the attachment
		add_action( 'save_post', array( &$this, 'save_post'), 8, 2 );
	}

	function content_save_pre( $content ) {
		global $shortcode_tags;
		// Piggyback on the shortcode interface to find inline tags [geo_mashup_save_location ...] 

		if ( !in_array( 'geo_attachment', array_keys( $shortcode_tags ) ) ) {
			add_shortcode( 'geo_attachment', 'is_null' );
			$pattern = get_shortcode_regex( );
			$content = preg_replace_callback('/'.$pattern.'/s', array( &$this, 'replace_attach_pre_shortcode' ), $content);
		}
		if ( !in_array( 'custom_field', array_keys( $shortcode_tags ) ) ) {
			add_shortcode( 'custom_field', 'is_null' );
			$pattern = get_shortcode_regex( );
			$content = preg_replace_callback('/'.$pattern.'/s', array( &$this, 'replace_custom_pre_shortcode' ), $content);
		}
		return $content;
	}

	function replace_custom_pre_shortcode( $shortcode_match ) {
		$replacement = $shortcode_match[0];
		$tag_index = array_search( 'custom_field',  $shortcode_match ); 
		if ( $tag_index !== false ) {
			// There is an attachment tag
			$atts = shortcode_parse_atts( stripslashes( $shortcode_match[ $tag_index + 1 ] ) );
			if ( is_array( $atts ) ) {
				$this->custom_values = array_merge( $this->custom_values, $atts );
			} 
			$replacement = '';
		}
		return $replacement;
	}

	function replace_attach_pre_shortcode( $shortcode_match ) {
		// Not good for multiple matches (repeated calls)
		$replacement = $shortcode_match[0];
		$tag_index = array_search( 'geo_attachment',  $shortcode_match ); 
		if ( $tag_index !== false ) {
			// There is an attachment tag
			$atts = shortcode_parse_atts( stripslashes( $shortcode_match[ $tag_index + 1 ] ) );
			if ( isset( $atts['activity'] ) && isset( $this->activity_maps[ $atts['activity'] ] ) ) {
				$this->activity = $atts['activity'];
			}

			$file = $atts['file'];
			$date = get_the_date( 'Y/m/d' );
			if ( $file and $date ) {

				// Find the attachment file in uploads
				$upload_dir = wp_upload_dir( $date );
				$file = path_join( $upload_dir['path'], $atts['file'] );
				if ( ! is_readable( $file ) ) {
					$upload_dir = wp_upload_dir();
					$file = path_join( $upload_dir['path'], $atts['file'] );
				}

				if ( stripos( $file, '.kml' ) == strlen( $file ) - 4 ) {

					$this->attachment_file = $file;
					$this->parser = new GeoAttachmentKmlParser( $file );

				} else if ( stripos( $file, '.gpx' ) == strlen( $file ) - 4 ) {

					$this->attachment_file = str_replace( 'gpx', 'kml', $file );
					$this->writer = new GeoAttachmentKmlWriter( $this->attachment_file );
					$callbacks = array();
					if ( !is_wp_error( $this->writer->status ) ) {
						$callbacks['add_wpt'] = array( &$this->writer, 'add_waypoint' );
						$callbacks['open_trk'] = array( &$this->writer, 'open_track');
						$callbacks['add_trk_name'] = array( &$this->writer, 'add_name' );
						$callbacks['open_trkseg'] = array( &$this->writer, 'open_line_string');
						$callbacks['add_trkpt'] = array( &$this->writer, 'add_line_point');
						$callbacks['close_trkseg'] = array( &$this->writer, 'close_line_string');
						$callbacks['close_trk'] = array( &$this->writer, 'close_placemark');
						$this->parser = new GeoAttachmentGpxParser( $file, $callbacks );
						$this->writer->close();
					}
				}
				// things have gone okay, strip the tag
				$replacement = '';
			}
		}
		return $replacement;
	}

	function save_post( $post_id, $post ) {
		if ( 'revision' == $post->post_type ) {
			return;
		}

		// Associate any accumulated data with the post here
		if ( $this->writer && $this->writer->status == 200 ) {
			$upload_dir = wp_upload_dir();
			$attachment_basename = basename( $this->attachment_file );
			$attachment_parts = split( '.', $attachment_basename );
			$attachment = array(
				'post_title' => $attachment_parts[0], 
				'guid' => trailingslashit( $upload_dir['url'] ) . $attachment_basename,
				'post_mime_type' => 'application/octet-stream'
			);
			wp_insert_attachment( $attachment, $this->attachment_file, $post_id );
			$this->writer = null;
		}
		if ( $this->parser && $this->parser->stats ) {

			$stats = &$this->parser->stats;

			if ( isset( $stats['center_lat'] ) && isset( $stats['center_lon'] ) ) {

				// Save Geo Mashup location
				$loc = array( 'lat' => $stats['center_lat'], 'lng' => $stats['center_lon'] );
				GeoMashupDB::set_object_location( 'post', $post_id, $loc );

			}

			$map = &$this->activity_maps[ $this->activity ];
			foreach( $stats as $name => $value ) {
				if ( isset( $map[$name] ) ) {
					update_post_meta( $post_id, $map[$name]['meta_key'], sprintf( $map[$name]['format'], $value ) );
				}
			}
		}
		$this->parser = null;

		// Add requested custom fields
		foreach( $this->custom_values as $label => $value ) {
			update_post_meta( $post_id, $label, $value );
		}
	}

} // end Geo Attachment class

// Instantiate
$geo_attachment = new GeoAttachment();

class GeoAttachmentParser {
	var $status;
	var $file_path;
	var $xml_parser;
	var $element_stack = array();
	var $element_depth = 0;
	var $in_track = false;
	var $stats = array();
	var $pt = null;
	var $last_pt = null;
	var $callbacks;
	var $geo_calc;

	function GeoAttachmentParser( $file_path, $callbacks = array() ) {
		// Assume existant and readable file for now
		$this->callbacks = $callbacks;
		$this->file_path = $file_path;
		$this->geo_calc = new GeoCalc();
		$this->status = $this->parse();
	}

	function parse() {
		$this->xml_parser = xml_parser_create();
		xml_parser_set_option( $this->xml_parser, XML_OPTION_CASE_FOLDING, false );
		xml_set_element_handler( $this->xml_parser, array( &$this, 'start_element' ), array( &$this, 'end_element' ) );
		xml_set_character_data_handler( $this->xml_parser, array( &$this, 'character_data' ) );
		if ( !( $fp = @fopen( $this->file_path, "r" ) ) ) {
			return new WP_Error( 404, 'Failed to open KML file.', $this->file_path );
		}

		while ( $data = fread( $fp, 4096 ) ) {
			if ( !xml_parse( $this->xml_parser, $data, feof( $fp ) ) ) {
				$code = xml_get_error_code( $this->xml_parser );
				$message = sprintf( 'XML Error: %s at line %d', xml_error_string( $code ),
					xml_get_current_line_number( $this->xml_parser ) );
				xml_parser_free( $this->xml_parser );
				return new WP_Error( $code, $message );
			}
		}
		xml_parser_free( $this->xml_parser );
		return 200;
	}

	function start_element( $parser, $name, $atts ) {
		array_push( $this->element_stack, array( $name, $atts ) );
		$this->element_depth++;
	}

	function end_element( $parser, $name ) {
		array_pop( $this->element_stack );	
		$this->element_depth--;
	}

	function character_data( $parser, $data ) {
		// override
	}

	function extend_stats() {
		// lat / lon bounds
		if ( empty( $this->stats['north'] ) ) {
			$this->stats['north'] = $this->stats['south'] = $this->pt['lat'];
			$this->stats['east'] = $this->stats['west'] = $this->pt['lon'];
		} else {
			if ( $this->pt['lat'] < $this->stats['south'] ) {
				$this->stats['south'] = $this->pt['lat'];
			}
			if ( $this->pt['lat'] > $this->stats['north'] ) {
				$this->stats['north'] = $this->pt['lat'];
			}
			if ( $this->pt['lon'] < $this->stats['west'] ) {
				$this->stats['west'] = $this->pt['lon'];
			}
			if ( $this->pt['lon'] > $this->stats['east'] ) {
				$this->stats['east'] = $this->pt['lon'];
			}
		}
		if ( $this->in_track ) {
			$this->extend_track_stats();
		}
	}

	function extend_track_stats() {

		// Timestamp
		if ( isset( $this->pt['time'] ) ) {
			$this->pt['unixtime'] = strtotime( $this->pt['time'] );
		}

		// last point stats
		if ( !empty( $this->last_pt ) ) {

			$distance_km = $this->geo_calc->EllipsoidDistance(
				$this->last_pt['lat'], 
				$this->last_pt['lon'],
				$this->pt['lat'],
				$this->pt['lon']
			);

			if ( isset( $this->pt['unixtime']) && isset( $this->last_pt['unixtime'] ) ) {
				$time_s = $this->pt['unixtime'] - $this->last_pt['unixtime'];
			}

			// elevation gain/loss
			if ( isset( $this->pt['ele'] ) && isset( $this->last_pt['ele'] ) ) {
				$ele_delta = $this->pt['ele'] - $this->last_pt['ele'];
				if ( $ele_delta > 0 ) {
					if ( isset( $this->stats['elevation_gain'] ) ) {
						$this->stats['elevation_gain'] += $ele_delta;
					} else {
						$this->stats['elevation_gain'] = 0;
					}
				} else {
					if ( isset( $this->stats['elevation_loss'] ) ) {
						$this->stats['elevation_loss'] -= $ele_delta;
					} else {
						$this->stats['elevation_loss'] = 0;
					}
				} 
				// Adjust distance for height to please Pythagoras
				$ele_km = $ele_delta / 1000;
				$distance_km = sqrt( $distance_km*$distance_km + $ele_km*$ele_km );
			}

			// distance
			if ( empty( $this->stats['distance'] ) ) {
				$this->stats['distance'] = $distance_km;
			} else { 
				$this->stats['distance'] += $distance_km;
			}
		}

		// elevation bounds
		if ( isset( $this->pt['ele'] ) ) {
			if ( empty( $this->stats['bottom'] ) ) {
				$this->stats['bottom'] = $this->stats['top'] = $this->pt['ele'];
			} else {
				if ( $this->pt['ele'] < $this->stats['bottom'] ) {
					$this->stats['bottom'] = $this->pt['ele'];
				}
				if ( $this->pt['ele'] > $this->stats['top'] ) {
					$this->stats['top'] = $this->pt['ele'];
				}
			}
		}

		// time bounds
		if ( !empty( $this->pt['time'] ) && ( $unixtime = strtotime( $this->pt['time'] ) ) ) {
			if ( empty( $this->stats['start_time'] ) ) {
				$this->stats['start_time'] = $this->stats['finish_time'] = $unixtime;
			} else {
				if ( $unixtime < $this->stats['start_time'] ) {
					$this->stats['start_time'] = $unixtime; 
				}
				if ( $unixtime > $this->stats['finish_time'] ) {
					$this->stats['finish_time'] = $unixtime;
				}
			}
		}

		$this->last_pt = $this->pt;
	}

	function finish_stats() {
		// TODO: dateline adjusment
		$this->stats['center_lat'] = ( $this->stats['south'] + $this->stats['north'] ) / 2;
		$this->stats['center_lon'] = ( $this->stats['west'] + $this->stats['east'] ) / 2;

		if ( !empty( $this->stats['start_time'] ) ) {
			$this->stats['hours'] = ( $this->stats['finish_time'] - $this->stats['start_time'] ) / 3600.0; 
		}

		if ( isset( $this->stats['distance'] ) ) {
			$this->stats['distance_mi'] = ConvKilometersToMiles( $this->stats['distance'] );
		}

		if ( isset( $this->stats['elevation_gain'] ) ) {
			$this->stats['elevation_gain_ft'] = ConvKilometersToMiles( $this->stats['elevation_gain']/1000.0 ) * 5280.0;
			$this->stats['elevation_loss_ft'] = ConvKilometersToMiles( $this->stats['elevation_loss']/1000.0 ) * 5280.0;
			$this->stats['top_ft'] = ConvKilometersToMiles( $this->stats['top']/1000.0 ) * 5280.0;
			$this->stats['bottom_ft'] = ConvKilometersToMiles( $this->stats['bottom']/1000.0 ) * 5280.0;
		}
	}
}

class GeoAttachmentGpxParser extends GeoAttachmentParser {
	var $name = '';

	/**
	 * PHP4 Constructor
	 */
	function GeoAttachmentGpxParser( $gpx_path, $callbacks = null ) {
		parent::GeoAttachmentParser( $gpx_path, $callbacks );
	}


	function character_data( $parser, $data ) {
		if ( $this->element_depth > 2 ) {

			$element = &$this->element_stack[ $this->element_depth - 1 ];
			$parent = &$this->element_stack[ $this->element_depth - 2 ];

			if ( 'name' == $element[0] ) {
				$this->name .= $data;
			}
			if ( 'trkpt' == $parent[0] || 'wpt' == $parent[0] ) {
				if ( empty( $this->pt[$element[0]] ) ) {
					$this->pt[$element[0]] = $data;
				} else { 
					$this->pt[$element[0]] .= $data;
				}
			}
		}
	}

	function start_element( $parser, $name, $atts ) {
		switch ( $name ) {
		case 'trk':
			$this->in_track = true;
			if ( !empty( $this->last_pt ) ) {
				$this->last_pt = null;
			}
			if ( isset( $this->callbacks['open_trk'] ) ) {
				call_user_func( $this->callbacks['open_trk'] );
			}
			break;

		case 'trkseg':
			if ( isset( $this->callbacks['open_trkseg'] ) ) {
				call_user_func( $this->callbacks['open_trkseg'] );
			}
			break;

		case 'trkpt':
		case 'wpt':
			$this->pt = array( 
				'lat' => 0,
				'lon' => 0,
				'ele' => 0,
				'time' => ''
			);
			break;
		}
		parent::start_element( $parser, $name, $atts );
	}

	function end_element( $parser, $name ) {
		if ( 'name' == $name ) {
			if ( 'trk' == $this->element_stack[ $this->element_depth - 2][0] && 
				isset( $this->callbacks['add_trk_name'] ) ) {
				call_user_func( $this->callbacks['add_trk_name'], $this->name );
			}
			$this->name = '';
		}	
		if ( 'trkpt' == $name || 'wpt' == $name ) {
			// merge lat & lon attributes
			$this->pt = array_merge( $this->pt, $this->element_stack[ $this->element_depth - 1 ][1] );
			$this->extend_stats();
			if ( 'trkpt' == $name && isset( $this->callbacks['add_trkpt'] ) ) {
				call_user_func( $this->callbacks['add_trkpt'], $this->pt );
			} else if ( 'wpt' == $name && isset( $this->callbacks['add_wpt'] ) ) {
				call_user_func( $this->callbacks['add_wpt'], $this->pt );
			} 
		}
		if ( 'trk' == $name && isset( $this->callbacks['close_trk'] ) ) {
			$this->in_track = false;
			call_user_func( $this->callbacks['close_trk'] );
		} else if ( 'trkseg' == $name && isset( $this->callbacks['close_trkseg'] ) ) {
			call_user_func( $this->callbacks['close_trkseg'], $this->pt );
		} else if ( 'gpx' == $name ) {
			$this->finish_stats();
		}
		parent::end_element( $parser, $name );
	}
}

class GeoAttachmentKmlParser extends GeoAttachmentParser {

	/**
	 * PHP4 Constructor
	 */
	function GeoAttachmentKmlParser( $kml_path ) {
		parent::GeoAttachmentParser( $kml_path );
	}

	function character_data( $parser, $data ) {
		if ( $this->element_depth > 2 &&
			$this->element_stack[ $this->element_depth - 3 ][0] == 'Placemark' &&
			$this->element_stack[ $this->element_depth - 2 ][0] == 'LineString' &&
			$this->element_stack[ $this->element_depth - 1 ][0] == 'coordinates' ) {

			//echo 'Track Data: ' . $data . '<br/>';
		}
	}
}

class GeoAttachmentKmlWriter {
	var $fp;
	var $file;
	var $status;
	var $folder = '';
	var $indent;

	/**
	 * PHP4 Constructor
	 */
	function GeoAttachmentKmlWriter( $kml_path ) {
		$this->status = 200;
		if ( !( $this->fp = @fopen( $kml_path, 'w' ) ) ) {
			$this->status = new WP_Error( 403, 'Failed to open ' . $kml_path );
		}
		$this->file = $kml_path;
		$header = <<<EOD
<?xml version="1.0" encoding="UTF-8"?>
<kml xmlns="http://earth.google.com/kml/2.1"
	xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
  <Document>
    <name>$kml_path</name>
<!-- Normal track style -->
    <Style id="track_n">
		<LineStyle>
			<color>7d0000ff</color>
			<width>1.5</width>
		</LineStyle>
      <IconStyle>
        <Icon>
          <href>http://earth.google.com/images/kml-icons/track-directional/track-none.png</href>
        </Icon>
      </IconStyle>
    </Style>
<!-- Highlighted track style -->
    <Style id="track_h">
      <IconStyle>
        <scale>1.2</scale>
        <Icon>
          <href>http://earth.google.com/images/kml-icons/track-directional/track-none.png</href>
        </Icon>
      </IconStyle>
    </Style>
    <StyleMap id="track">
      <Pair>
        <key>normal</key>
        <styleUrl>#track_n</styleUrl>
      </Pair>
      <Pair>
        <key>highlight</key>
        <styleUrl>#track_h</styleUrl>
      </Pair>
    </StyleMap>
<!-- Normal waypoint style -->
    <Style id="waypoint_n">
      <IconStyle>
        <Icon>
          <href>http://maps.google.com/mapfiles/kml/pal4/icon61.png</href>
        </Icon>
      </IconStyle>
    </Style>
<!-- Highlighted waypoint style -->
    <Style id="waypoint_h">
      <IconStyle>
        <scale>1.2</scale>
        <Icon>
          <href>http://maps.google.com/mapfiles/kml/pal4/icon61.png</href>
        </Icon>
      </IconStyle>
    </Style>

EOD;
		$this->write( $header );
		$this->indent = 2;
	}

	function write( $content ) {
		if ( 200 == $this->status ) {
			if ( fwrite( $this->fp, $content ) === false ) {
				$this->status = new WP_Error( 500, 'Failed to write ' . $this->file );
			}
		}
	}

	function write_line( $content ) {
		$indent_delta = ( substr_count( $content, '<' ) - ( 2 * substr_count( $content, '</' ) ) );
		if ( $indent_delta < 0 ) {
			$this->indent += $indent_delta;
		}
		if ( $this->indent > 0 ) {
			$this->write( str_repeat( '  ', $this->indent ) );
		} else {
			$this->indent = 0;
		}
		$this->write( $content . "\r\n" );
		if ( $indent_delta > 0 ) {
			$this->indent += $indent_delta;
		}
	}

	function open_folder( $name ) {
		// 1 folder deep only currently
		if ( !empty( $this->folder ) ) {
			$this->close_folder();
		}
		$this->write_line( '<Folder>' );
		$this->write_line( "<name>$name</name>" );
		$this->folder = $name;
	}

	function close_folder() {
		// 1 folder deep only currently
		if ( !empty( $this->folder ) ) {
			$this->write_line( "</Folder>" );
			$this->folder = '';
		}
	}

	function add_waypoint( $wpt ) {
		if ( $this->folder != 'Waypoints' ) {
			$this->open_folder( 'Waypoints' );
		}
		$this->open_placemark();
		if ( !empty( $wpt['name'] ) ) {
			$this->add_name( $wpt['name'] );
		}
		$this->write_line( "<styleUrl>#waypoint</styleUrl>" );
		if ( !empty( $wpt['time'] ) ) {
			$this->write_line( "<time>{$wpt['time']}</time>" );
		}
		if ( !empty( $wpt['desc'] ) ) {
			$this->write_line( "<description>{$wpt['desc']}</description>" );
		} else if ( !empty( $wpt['cmt'] ) ) {
			$this->write_line( "<description>{$wpt['cmt']}</description>" );
		}

		$this->write_line( '<Point>' );
		$this->write_line( sprintf( "<coordinates>%01.6f,%01.6f,%01.6f</coordinates>", 
				$wpt['lon'], $wpt['lat'], $wpt['ele'] ) );
		$this->write_line( '</Point>' );
		$this->close_placemark();
	}

	function open_track() {
		if ( $this->folder != 'Tracks' ) {
			$this->open_folder( 'Tracks' );
		}
		$this->open_placemark();
		$this->write_line( '<styleUrl>#track</styleUrl>' );
	}

	function open_placemark() {
		$this->write_line( '<Placemark>' );
	}

	function close_placemark() {
		$this->write_line( '</Placemark>' );
	}

	function add_name( $name ) {
		if ( !empty( $name ) ) {
			$this->write_line( "<name>$name</name>" );
		}
	}

	function open_line_string( ) {
		$this->write_line( '<LineString>' );
		$this->write_line( '<tessellate>1</tessellate>' );
		$this->write_line( '<coordinates>' );
	}

	function close_line_string() {
		$this->write_line( '</coordinates>' );
		$this->write_line( '</LineString>' );
	}

	function add_line_point( $trkpt ) {
		$this->write_line( sprintf( '%01.6f,%01.6f,%01.6f', 
			$trkpt['lon'], $trkpt['lat'], $trkpt['ele'] ) );
	}

	function close( ) {
		$this->close_folder();
		$this->write_line( '</Document>' );
		$this->write_line( '</kml>' );
		fclose( $this->fp );
	}
}

} // end if Geo Mashup Custom class exists


