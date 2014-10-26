// create the map content
var renoCenter = new L.LatLng( 39.519933, -119.789643 );
var map = L.map('mapBody', {
	center: renoCenter,
	zoom: 13,
	zoomControl: false,
	maxZoom: 16
} ).on( 'resize', function( e ) {
	var $container = jQuery( map.getContainer() ),
		height = jQuery( window ).height() - jQuery( '.navbar' ).height();
	$container.height( height );
} );
map.addControl( L.control.zoom( { position: 'topright' } ) );
map.fireEvent( 'resize' );
var mapTileLayer = new L.TileLayer(
	'http://services.arcgisonline.com/ArcGIS/rest/services/Canvas/World_Light_Gray_Base/MapServer/tile/{z}/{y}/{x}',
	{maxZoom: 16, attribution: 'Tiles: &copy; Esri' }
);
map.addLayer(mapTileLayer);

map.addLayer(
	new L.TileLayer(
		'http://services.arcgisonline.com/ArcGIS/rest/services/Canvas/World_Light_Gray_Reference/MapServer/tile/{z}/{y}/{x}',
		{maxZoom: 19, attribution: 'Tiles: &copy; Esri' }
	)
);

var tripTileLayer = new L.TileLayer(
	'tiler/all/{z}/{y}/{x}.png',
	{maxZoom: 16 }
);
map.addLayer( tripTileLayer );

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
			this.fetchTileMeta();
		}
		this.fetchNotes()
	},
	fetchTileMeta: function() {
		$.ajax({
			url: 'tiler/all/meta.json',
			type: 'GET',
			dataType: 'json',
			success: function( results ) {
				$( '.trip_count' ).text( results.trip_count );
				$( '.coordinate_count' ).text( results.coordinate_count );

			}
		})
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
			type: 'GET',
			data: {
				t:'get_trip_ids'
				},
			dataType: 'json',
			cache: true,
			success: function(results) {
				$('.trip_total').text(results.length);
				for(var n in results){
					self.countTrip(results[n]);
		 			//self.fetchData(results[n].id);
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
			type: 'GET',
			data: {
				q:query,
				t:'get_coords_by_trip'
				},
			dataType: 'json',
			cache: true,
			success: function(results) {
				self.data = results;
				self.attachPolyline();
			}
		});
		return self.data;
	},
	fetchNotes: function() {
		var self = Trips;
		$.ajax({
			url: 'noteData.php',
			type: 'GET',
			data: {
				t:'get_notes'
				},
			dataType: 'json',
			cache: true,
			success: function(results) {
				self.notes = results;
				results.forEach(function (result) {
					result.marker = self.generateMarker(result)
				})
			}
		});
		return self.notes;
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
	},
	generateMarker: function (note) {
	//	(0, 'Pavement issue');
	//	(1, 'Traffic signal');
	//	(2, 'Enforcement');
	//	(3, 'Rack Em Up - park future' );
	//	(4, 'Bike lane issue');
	//	(5, 'Note this issue');
	//	(6, 'Rack Em Up - park now');
	//	(7, 'Bike shops');
	//	(8, 'Public restrooms');
	//	(9, 'Secret passage');
	//	(10, 'Water fountain');
	//	(11, 'Note this asset');
		var marker_location = new L.LatLng( note.latitude, note.longitude, note.altitude );

		var icon_data = {}
		switch(note.note_type) {
    case '0': //Pavement Issue
				icon_data = {icon: 'road', markerColor: 'blue'}
				note["title"] = "Pavement Issue"
				break;
		case '1': //Traffic Signal
				icon_data = {icon: 'car', markerColor: 'blue'}
				note["title"] = "Traffic Signal"
				break;
		case '2': //Enforcement
				icon_data = {icon: 'warning', markerColor: 'blue'}
				note["title"] = "Enforcement"
				break;
		case '3': //Rack Em Up - Future
				icon_data = {icon: 'frown-o', markerColor: 'blue'}
				note["title"] = "Rack Em Up - Future"
				break;
		case '4': //Bike Lane Issue
				icon_data = {icon: 'truck', markerColor: 'blue'}
				note["title"] = "Bike Lane Issue"
				break;
		case '5': //Note this issue
				icon_data = {icon: 'question', markerColor: 'blue'}
				note["title"] = "Note This Issue"
				break;
		case '6': //Rack Em Up - now
				icon_data = {icon: 'smile-o', markerColor: 'blue'}
				note["title"] = "Rack Em Up - Now"
				break;
		case '7': //Bike Lane Issue
				icon_data = {icon: 'bicycle', markerColor: 'green'}
				note["title"] = "Bike Shop"
				break;
		case '8': //Public Restrooms
				icon_data = {icon: 'users', markerColor: 'blue'}
				note["title"] = "Public Restroom"
				break;
		case '9': //Secret Passage
				icon_data = {icon: 'thumbs-o-up', markerColor: 'blue'}
				note["title"] = "Secret Passage"
				break;
		case '10': //Water Fountain
				icon_data = {icon: 'tint', markerColor: 'blue'}
				note["title"] = "Water Fountain"
				break;
		case '11': //Note this asset
				icon_data = {icon: 'eye', markerColor: 'blue'}
				note["title"] = "Note This Asset"
				break;
    default:
				icon_data = {icon: 'tags', markerColor: 'red'}
				note["title"] = "Unknown"
				break;
		}

		icon_data['prefix'] = 'fa'

		var icon = L.AwesomeMarkers.icon(icon_data);
		var marker = L.marker(marker_location, {icon: icon})
		var popup_html = "<b>"+note.title+"</b>"
		if(note.details != "")
		{
			popup_html += "<br>details: "+ note.details
		}
		if(note.image_url !== "")
		{
      popup_html += '<br><a href="image.php?name='+note.image_url+'" data-lightbox="image-'+note.id+'"><img src="image.php?name='+note.image_url+'" style="width: 200px"/></a>'
		}
		marker.bindPopup(popup_html)
		return marker
	}
}


jQuery( '.btn.streets' ).button('toggle').on( 'click', function() {
	if ( $(this).hasClass( 'active' ) ) {
		mapTileLayer.setOpacity(0);
	} else {
		mapTileLayer.setOpacity(1);
	}
} );

jQuery( '.btn.trips' ).button('toggle').on( 'click', function() {
	if ( $(this).hasClass( 'active' ) ) {
		tripTileLayer.setOpacity(0);
	} else {
		tripTileLayer.setOpacity(1);
	}
} );

jQuery( '.btn.notes' ).button('toggle').on( 'click', function() {
	if ( $(this).hasClass( 'active' ) ) {
		Trips.notes.forEach(function (note) {
			note.marker.addTo(map)
		})
	} else {
		Trips.notes.forEach(function (note) {
			map.removeLayer(note.marker)
		})
	}
} );

jQuery( '.btn.rtc' ).on( 'click', function() {
	var rtc_url = "js/reno-improvements.json";
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
