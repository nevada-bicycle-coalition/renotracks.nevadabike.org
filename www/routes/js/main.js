// create the map content
var renoCenter = new L.LatLng( 39.519933, -119.789643 );
var map = L.map('mapBody', {
    center: renoCenter,
    zoom: 13
});

// add an OpenStreetMap tile layer
var stamenUrl = 'http://{s}.tile.stamen.com/toner/{z}/{x}/{y}.png';
var stamenAttribution = 'Map tiles by <a href="http://stamen.com">Stamen Design</a>, under <a href="http://creativecommons.org/licenses/by/3.0">CC BY 3.0</a>. Data by <a href="http://openstreetmap.org">OpenStreetMap</a>, under <a href="http://creativecommons.org/licenses/by-sa/3.0">CC BY SA</a>.';

var mapTileLayer = new L.TileLayer(stamenUrl, {maxZoom: 18, attribution: stamenAttribution, opacity: 0.5 });
map.addLayer(mapTileLayer);

var tilesVisible = true;


var geojsonLayer;


var Trips ={
	init: function(config) {
		this.trip_count = 1;
		this.config = config;
		if (config.tripId) {
			this.trips = [ config.tripId ];
			this.config.lineWeight = 5;
			this.config.lineOpacity = 0.9;
			Trips.fetchData( config.tripId );
		} else {
			this.config.lineWeight = 2;
			this.config.lineOpacity = 0.2;
			this.trips = this.fetchTrips();
		}
	},
	fetchTrips: function(query) {
		var self = Trips;
		$.ajax({
			url: 'routeData.php',
			type: 'POST',
			data: {
				t:'get_trip_ids',
				}, 
			dataType: 'json',
			success: function(results) {
				$('.trip_total').text(results.length);
				for(var n in results){
		 			self.fetchData(results[n].id);
			 	}			 
				self.trips = results;
				
			}
		});
		return self.trips;
	},
	fetchData: function(query) {
		var self = Trips;
		$.ajax({
			url: 'routeData.php',
			type: 'POST',
			data: {
				q:query,
				t:'get_coords_by_trip',
				}, 
			dataType: 'json',
			success: function(results) {
				self.data = results;
				self.attachPolyline();
			}
		});
		return self.data;
	},
	attachPolyline: function() {
		var latlng,
			polyline;

			latlngs = new Array();

		$(this.data).each(function() {
			self = $(this)[0]; 
			latlng = new L.LatLng(self.latitude,self.longitude);
			latlngs.push(latlng);
		});	
		polyline = L.polyline(latlngs, {color: 'red', weight: this.config.lineWeight, opacity: this.config.lineOpacity}).addTo(map);
		$('.trip_count').text(this.trip_count++);
	}
}


jQuery( '.btn.streets' ).button('toggle').on( 'click', function() {
	if ( $(this).hasClass( 'active' ) ) {
		mapTileLayer.setOpacity(0);
	} else {
		mapTileLayer.setOpacity(0.5);
	}
} );

jQuery( '.btn.rtc' ).on( 'click', function() {
	var rtc_url = "http://geocommons.com/datasets/402220/features.json?lat=" +
		renoCenter.lat + "&amp;lon=" + renoCenter.lng + "&amp;radius=25&amp;callback=?";
	var rtc_styles = {};
	var rtc_groups = {};
	var rtc_colors = [
		'#1f77b4', '#ff7f0e', '#2ca02c', '#d62728', '#9467bd', '#8c564b', '#e377c2', '#98df8a', '#bcbd22', '#17becf'
	];
	var $button = jQuery( this ).text( 'Loading...' );
	jQuery.getJSON(rtc_url, function(data){
		var $buttonGroup = $button.parent();
		$button.detach();
		 geojsonLayer = new L.GeoJSON( data, {
			style: function( feature ) {
				var style = { weight: 3 };
				if ( !rtc_styles.hasOwnProperty( feature.properties.Type ) ) {
					rtc_styles[feature.properties.Type] = {
						weight: 3,
						color: rtc_colors.pop()
					};
				}
				return rtc_styles[feature.properties.Type];
			},
			onEachFeature: function( feature, layer ) {
				if ( !rtc_groups[feature.properties.Type] ) {
					rtc_groups[feature.properties.Type] = new L.layerGroup();
					rtc_groups[feature.properties.Type].addTo( map );
					$buttonGroup.append(
						jQuery( '<button type="button" class="btn active"></button>' )
							.text( feature.properties.Type )
							.data( { type: feature.properties.Type } )
							.css( 'color', rtc_styles[feature.properties.Type].color )
							.click( function() {
								var $btn = jQuery( this );
									$btn.toggleClass( 'active' );
								if ( $btn.hasClass( 'active' ) ) {
									map.addLayer( rtc_groups[feature.properties.Type] );
								} else {
									map.removeLayer( rtc_groups[feature.properties.Type] );
								}
							} )
					);
				}
				rtc_groups[feature.properties.Type].addLayer( layer );
			}
		} );
	});
});
