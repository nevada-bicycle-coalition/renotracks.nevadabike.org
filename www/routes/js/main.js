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
		var marker_location = new L.LatLng( note.latitude, note.longitude, note.altitude );

		var note_source = [
			{
				icon_data: {icon: 'road', markerColor:'blue'},
				title: "Pavement Issue"
			},
			{
				icon_data: {icon: 'car', markerColor:'blue'},
				title: "Traffic Signal"
			},
			{
				icon_data: {icon: 'warning', markerColor:'blue'},
				title: "Enforcement"
			},
			{
				icon_data: {icon: 'frown-o', markerColor:'blue'},
				title: "Rack Em Up - Future"
			},
			{
				icon_data: {icon: 'truck', markerColor:'blue'},
				title: "Bike Lane Issue"
			},
			{
				icon_data: {icon: 'exclamation', markerColor:'beige'},
				title: "Note This Issue"
			},
			{
				icon_data: {icon: 'smile-o', markerColor:'blue'},
				title: "Rack Em Up - Now"
			},
			{
				icon_data: {icon: 'bicycle', markerColor:'green'},
				title: "Bike Shop"
			},
			{
				icon_data: {icon: 'users', markerColor:'blue'},
				title: "Public Restroom"
			},
			{
				icon_data: {icon: 'thumbs-o-up', markerColor:'blue'},
				title: "Secret Passage"
			},
			{
				icon_data: {icon: 'tint', markerColor:'blue'},
				title: "Water Fountain"
			},
			{
				icon_data: {icon: 'eye', markerColor:'blue'},
				title: "Note This Asset"
			}
		]
		note_data = note_source[note.note_type];

		note_data.icon_data['prefix'] = 'fa'

		var icon = L.AwesomeMarkers.icon(note_data.icon_data);
		var marker = L.marker(marker_location, {icon: icon})
		var popup_html = "<b>"+note_data.title+"</b>"
		if(note.details != "")
		{
			popup_html += "<br>details: "+ note.details
		}
		if(note.image_url !== "")
		{
      popup_html += '<br><a href="image.php?name='+note.image_url+'" data-lightbox="image-'+note.id+'"><img src="image.php?name='+note.image_url+'&size=thumb" style="width: 200px"/></a>'
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

jQuery( '.btn.notes' ).on( 'click', function() {
	if ( $(this).hasClass( 'active' ) ) {
		Trips.notes.forEach(function (note) {
			map.removeLayer(note.marker)
	})
	} else {
		Trips.notes.forEach(function (note) {
			note.marker.addTo(map)
	})
	}
} );

jQuery( '.btn.parks' ).on( 'click', function() {
	if ( $(this).hasClass( 'active' ) ) {
		map.removeLayer(parks_layer)
	} else {
		parks_layer.addTo(map)
	}
} );

var funParkMarker = {
    radius: 8,
    fillColor: "#2ca02c",
    color: "#000",
    weight: 1,
    opacity: 1,
    fillOpacity: 0.8
};

var parkMarker = {
		radius: 5,
		fillColor: "#2ca02c",
		color: "#000",
		weight: 1,
		opacity: 1,
		fillOpacity: 0.8
};

var parks_url = "js/parks.json"
var parks_layer;
jQuery.getJSON(parks_url, function(data) {
	parks_layer = new L.GeoJSON (data, {
    pointToLayer: function (feature, latlng) {
        return L.circleMarker(latlng, feature.properties.Trails == 'Yes' ? funParkMarker : parkMarker);
    },
		onEachFeature: function (feature, layer) {
	  	var popup = "<b>"+feature.properties["Park Name"]+"</b>"
			popup+= '<br>'+(feature.properties.Trails == 'Yes' ? '<i class="fa fa-check-square-o"></i>':'No') +' Bike Trails'
			popup+= '<br>'+(feature.properties["Drinking Fountain"] == 'Yes' ? '<i class="fa fa-check-square-o"></i>':'No') +' Drinking Fountain'
			popup += "<br>"+feature.properties.Description
			layer.bindPopup(popup);
		}
	})
})

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
					
					//Create coloumn holder
					$buttonGroup.append(
						this.$newColumn = $('<div class="col-xs-6"></div>')
							.append(
								//Append new button to column
								$( '<button type="button" class="btn btn-default"></button>' )
									.text( feature.properties.Type )
									.data( { type: feature.properties.Type } )
									.click( function() {
										var $btn = jQuery( this );
										$btn.toggleClass( 'active' );
										if ( $btn.hasClass( 'active' ) ) {
											map.addLayer( rtc_groups[feature.properties.Type] );
										} else {
											map.removeLayer( rtc_groups[feature.properties.Type] );
										}; $(this).blur();
									}),
									$('<span class="col-xs-12"></span>')
									.css( 'background-color', rtc_styles[feature.properties.Type].color )
									.css( 'height', '5px')
							)
					);
				}
				rtc_groups[feature.properties.Type].addLayer( layer );
			}
		} );
	});
} ).click();
