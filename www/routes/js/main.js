// create the map content
var renoCenter = new L.LatLng( 39.519933, -119.789643 );
var map = L.map('mapBody', {
	center: renoCenter,
	zoom: 13
});

var mapTileLayer = new L.TileLayer(
	'http://services.arcgisonline.com/ArcGIS/rest/services/Canvas/World_Light_Gray_Base/MapServer/tile/{z}/{y}/{x}',
	{maxZoom: 19, attribution: 'Tiles: &copy; Esri' }
);
map.addLayer(mapTileLayer);

map.addLayer(
	new L.TileLayer(
		'http://services.arcgisonline.com/ArcGIS/rest/services/Canvas/World_Light_Gray_Reference/MapServer/tile/{z}/{y}/{x}',
		{maxZoom: 19, attribution: 'Tiles: &copy; Esri' }
	)
);

var tilesVisible = true;


var geojsonLayer;


var Trips ={
	init: function(config) {
		this.tripCount = 1;
		this.userIds = {};
		this.userCount = 0;
		this.lines = {};
		this.config = config;
		if (config.tripId) {
			this.trips = [ config.tripId ];
			this.config.lineWeight = 5;
			this.config.lineOpacity = 0.9;
			Trips.fetchData( config.tripId );
		} else {
			this.config.lineWeight = 2;
			this.config.lineOpacity = 0.05;
			this.trips = this.fetchTrips();
		}
	},
	countTrip: function(trip) {
		if ( trip.user_id in this.userIds ) {
			this.userIds[trip.user_id]++;
		} else {
			this.userIds[trip.user_id] = 0;
			this.userCount++;
		}
	},
	fetchTrips: function(query) {
		var self = Trips;
		$.ajax({
			url: 'routeData.php',
			type: 'POST',
			data: {
				t:'get_trip_ids'
				}, 
			dataType: 'json',
			success: function(results) {
				$('.trip_total').text(results.length);
				for(var n in results){
					self.countTrip(results[n]);
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
				t:'get_coords_by_trip'
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
			polyline,
			data = this.data,
			latlngs = [];

		$(this.data).each(function() {
			self = $(this)[0]; 
			latlng = new L.LatLng(self.latitude,self.longitude);
			latlngs.push(latlng);
		});

		if ( latlngs.length ) {
			this.lines[data[0].trip_id] = L.polyline(latlngs, {color: 'red', weight: this.config.lineWeight, opacity: this.config.lineOpacity})
				.on( 'click', function( e ) {
					console.log( data[0].trip_id );
				} )
				.addTo(map);
		}
		$('.trip_count').text(this.tripCount++);
		$('.user_count').text(this.userCount);
	}
}


jQuery( '.btn.streets' ).button('toggle').on( 'click', function() {
	if ( $(this).hasClass( 'active' ) ) {
		mapTileLayer.setOpacity(0);
	} else {
		mapTileLayer.setOpacity(1);
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
					rtc_groups[feature.properties.Type];
					$buttonGroup.append(
						jQuery( '<button type="button" class="btn"></button>' )
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
} ).click();

