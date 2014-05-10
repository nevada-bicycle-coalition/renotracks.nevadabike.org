<?php

require_once "GeoCalc.php";
require_once "Trip.php";
require_once "CoordFactory.php";

class TripAnalyzer {
	/** @var Trip $trip */
	protected $trip;
	protected $in_track = false;
	protected $stats = array();

	protected $pt = null;
	protected $last_coord = null;
	protected $geo_calc;

	public function __construct( $trip ) {
		$this->trip = $trip;
		$this->geo_calc = new GeoCalc();
	}

	public function getStats() {
		if ( empty( $this->stats ) )
			$this->computeStats();
		return $this->stats;
	}

	protected function computeStats() {
		$this->stats = array();
		$result = CoordFactory::getCoordsByTripResult( $this->trip->id );
		if ( $result->num_rows == 0 ) {
			$result->close();
			return;
		}
		$preceding_coord = null;
		while( $coord = $result->fetch_object( CoordFactory::$class ) ) {
			$this->extend_stats( $coord, $preceding_coord );
			$preceding_coord = $coord;
		}
		$this->finish_stats();
	}

	/**
	 * @param Coord $coord
	 * @param Coord $preceding_coord
	 */
	protected function extend_stats( $coord, $preceding_coord ) {
		// lat / lon bounds
		if ( empty( $this->stats['north_bound'] ) ) {
			$this->stats['north_bound'] = $this->stats['south_bound'] = $coord->latitude;
			$this->stats['east_bound'] = $this->stats['west_bound'] = $coord->longitude;
		} else {
			if ( $coord->latitude < $this->stats['south_bound'] ) {
				$this->stats['south_bound'] = $coord->latitude;
			}
			if ( $coord->latitude > $this->stats['north_bound'] ) {
				$this->stats['north_bound'] = $coord->latitude;
			}
			if ( $coord->longitude < $this->stats['west_bound'] ) {
				$this->stats['west_bound'] = $coord->longitude;
			}
			if ( $coord->longitude > $this->stats['east_bound'] ) {
				$this->stats['east_bound'] = $coord->longitude;
			}
		}

		// Timestamp
		$time = strtotime( $coord->recorded );
		$last_time = $preceding_coord ? strtotime( $preceding_coord->recorded ) : $time;

		// last point stats
		if ( $preceding_coord ) {

			$distance_km = $this->geo_calc->EllipsoidDistance(
				$preceding_coord->latitude,
				$preceding_coord->longitude,
				$coord->latitude,
				$coord->longitude
			);

			$time_s = $time - $last_time;

			// elevation gain/loss
			if ( !empty( $coord->altitude ) && !empty( $preceding_coord->altitude ) ) {
				$ele_delta = $coord->altitude - $preceding_coord->altitude;
				$this->stats['elevation_gain_m'] = 0;
				$this->stats['elevation_loss_m'] = 0;
				if ( $ele_delta > 0 ) {
					$this->stats['elevation_gain_m'] += $ele_delta;
				} else {
					$this->stats['elevation_loss_m'] -= $ele_delta;
				}
				// Adjust distance for height to please Pythagoras
				$ele_km = $ele_delta / 1000;
				$distance_km = sqrt( $distance_km*$distance_km + $ele_km*$ele_km );
			}

			// distance
			if ( empty( $this->stats['distance_km'] ) ) {
				$this->stats['distance_km'] = $distance_km;
			} else {
				$this->stats['distance_km'] += $distance_km;
			}
		}

		// elevation bounds
		if ( !empty( $coord->altitude ) ) {
			if ( empty( $this->stats['bottom_m'] ) ) {
				$this->stats['bottom_m'] = $this->stats['top_m'] = $coord->altitude;
			} else {
				if ( $coord->altitude < $this->stats['bottom_m'] ) {
					$this->stats['bottom_m'] = $coord->altitude;
				}
				if ( $coord->altitude > $this->stats['top_m'] ) {
					$this->stats['top_m'] = $coord->altitude;
				}
			}
		}
	}

	protected function finish_stats() {
		// TODO: dateline adjusment
		$this->stats['center_lat'] = ( $this->stats['south_bound'] + $this->stats['north_bound'] ) / 2;
		$this->stats['center_lng'] = ( $this->stats['west_bound'] + $this->stats['east_bound'] ) / 2;

		if ( !empty( $this->stats['start_time'] ) ) {
			$this->stats['hours'] = ( $this->stats['finish_time'] - $this->stats['start_time'] ) / 3600.0;
		}

		if ( isset( $this->stats['distance_km'] ) ) {
			$this->stats['distance_mi'] = ConvKilometersToMiles( $this->stats['distance_km'] );
		}

		if ( isset( $this->stats['elevation_gain_m'] ) ) {
			$this->stats['elevation_gain_ft'] = ConvKilometersToMiles( $this->stats['elevation_gain_m']/1000.0 ) * 5280.0;
			$this->stats['elevation_loss_ft'] = ConvKilometersToMiles( $this->stats['elevation_loss_m']/1000.0 ) * 5280.0;
			$this->stats['top_ft'] = ConvKilometersToMiles( $this->stats['top_m']/1000.0 ) * 5280.0;
			$this->stats['bottom_ft'] = ConvKilometersToMiles( $this->stats['bottom_m']/1000.0 ) * 5280.0;
		}
	}
}

