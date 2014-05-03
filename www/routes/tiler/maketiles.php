<?php
/*
*DISCLAIMER
* 
*THIS SOFTWARE IS PROVIDED BY THE AUTHOR 'AS IS' AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES *OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE AUTHOR BE LIABLE FOR ANY DIRECT, INDIRECT, *INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF *USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT *(INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
*
*	@author: Olivier G. <olbibigo_AT_gmail_DOT_com>
*	@version: 1.0
*	@history:
*		1.0	creation
*/
ini_set('memory_limit', '1G');

if ( !isset( $argc ) )
	die( 'CLI dude' );

set_include_path( dirname( dirname( dirname( dirname( __FILE__ ) ) ) ) . '/include' . PATH_SEPARATOR . get_include_path() );
require_once( 'CoordFactory.php' );

require_once( 'GoogleMapUtility.php' );
require_once( 'HeatMap.php' );

//Root folder to store generated tiles
define( 'TILE_DIR', 'tile/' );
//Covered geographic areas
define( 'MIN_LAT', 39.4 );
define( 'MAX_LAT', 39.7 );
define( 'MIN_LNG', -120 );
define( 'MAX_LNG', -119.7 );
define( 'MAX_ZOOM', 16 );
define( 'TILE_SIZE_FACTOR', 0.5 );
define( 'SPOT_RADIUS', 0 );
define( 'SPOT_DIMMING_LEVEL', 127 );

$overwrite = isset( $argv[1] ) ? (bool)$argv[1] : true;
$start_zoom = isset( $argv[2] ) ? intval( $argv[2] ) : MAX_ZOOM;
for( $zoom = $start_zoom; $zoom > 1; $zoom -= 1 ) {
	$from_tile = GoogleMapUtility::getTileXY( MAX_LAT, MIN_LNG, $zoom );
	$to_tile = GoogleMapUtility::getTileXY( MIN_LAT, MAX_LNG, $zoom );

	for ( $x = $from_tile->x; $x <= $to_tile->x; $x += 1 ) {
		for ( $y = $from_tile->y; $y <= $to_tile->y; $y += 1 ) {
			makeTile( $x, $y, $zoom, $overwrite );
		}
	}
}


function makeTile( $X, $Y, $zoom, $overwrite = true ) {

	$dir = TILE_DIR . $zoom;

	$tilename = $dir . '/' . $X . '_' . $Y . '.png';
	if ( $overwrite or !file_exists( $tilename ) ) {
		$rect = GoogleMapUtility::getTileRect( $X, $Y, $zoom );
		//A tile can contain part of a spot with center in an adjacent tile (overlaps).
		//Knowing the spot radius (in pixels) and zoom level, a smart way to process tiles would be to compute the box (in decimal degrees) containing only spots that can be drawn on current tile. We choose a simpler solution by increeasing  geo bounds by 2*TILE_SIZE_FACTOR whatever the zoom level and spot radius.
		$extend_X = $rect->width * TILE_SIZE_FACTOR; //in decimal degrees
		$extend_Y = $rect->height * TILE_SIZE_FACTOR; //in decimal degrees
		$swlat = $rect->y - $extend_Y;
		$swlng = $rect->x - $extend_X;
		$nelat = $swlat + $rect->height + 2 * $extend_Y;
		$nelng = $swlng + $rect->width + 2 * $extend_X;

		if ( ( $nelat <= MIN_LAT ) || ( $swlat >= MAX_LAT ) || ( $nelng <= MIN_LNG ) || ( $swlng >= MAX_LNG ) ) {
			//No geodata so skip
			return;
		}

		$result = CoordFactory::getCoordsInBox( $swlat, $swlng, $nelat, $nelng );
		if ( 0 == $result->num_rows ) {
			$result->free();
			return;
		}

		$offset = SPOT_RADIUS;
		$spots = array();
		while( $coord = $result->fetch_object( 'Coord' ) ) {
			$point = GoogleMapUtility::getOffsetPixelCoords( $coord->latitude, $coord->longitude, $zoom, $X, $Y );
			//Count result only in the tile
			if ( ( $point->x > -$offset ) && ( $point->x < ( GoogleMapUtility::TILE_SIZE + $offset ) ) && ( $point->y > -$offset ) && ( $point->y < ( GoogleMapUtility::TILE_SIZE + $offset ) ) ) {
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
		$dimming = Max( 1, SPOT_DIMMING_LEVEL >> ( MAX_ZOOM - $zoom ) );
		$im = HeatMap::createImage( $spots, GoogleMapUtility::TILE_SIZE, GoogleMapUtility::TILE_SIZE, heatMap::$WITH_ALPHA, SPOT_RADIUS, $dimming, HeatMap::$GRADIENT_CLASSIC );
		unset( $spots );
		// store the tile
		imagepng( $im, $tilename );
		imagedestroy( $im );
		unset( $im );
	}
}


