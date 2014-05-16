<?php
set_include_path( dirname( dirname( dirname( __FILE__ ) ) ) . '/include' . PATH_SEPARATOR . get_include_path() );
include_once('TripFactory.php');
$after = '2014-05-10';
$before = '2014-05-17';

$trips = json_encode( TripFactory::getTripsByBoundingBox( 39.52518, 0.003, -119.817056, 0.003, $after, $before ) );
?>
<!DOCTYPE html>
<!--[if lt IE 7]>
<html class="no-js lt-ie9 lt-ie8 lt-ie7"> <![endif]-->
<!--[if IE 7]>
<html class="no-js lt-ie9 lt-ie8"> <![endif]-->
<!--[if IE 8]>
<html class="no-js lt-ie9"> <![endif]-->
<!--[if gt IE 8]><!-->
<html class="no-js"> <!--<![endif]-->
<head>
	<meta charset="utf-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
	<title>Visualizing RenoTracks Data</title>
	<meta name="description" content="">
	<meta name="viewport" content="width=device-width">

	<link rel="stylesheet" href="css/bootstrap.min.css">
	<link rel="stylesheet" href="http://cdn.leafletjs.com/leaflet-0.7/leaflet.css"/>
	<link rel="stylesheet" href="css/bootstrap-responsive.min.css">
	<link rel="stylesheet" href="css/main.css">

	<script type="text/javascript">
		var _gaq = _gaq || [];
		_gaq.push( ['_setAccount', ''] );
		_gaq.push( ['_trackPageview'] );

		(function () {
			var ga = document.createElement( 'script' );
			ga.type = 'text/javascript';
			ga.async = true;
			ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
			var s = document.getElementsByTagName( 'script' )[0];
			s.parentNode.insertBefore( ga, s );
		})();
	</script>
</head>
<body>
<!--[if lt IE 7]>
<p class="chromeframe">You are using an <strong>outdated</strong> browser. Please <a href="http://browsehappy.com/">upgrade
	your browser</a> or <a href="http://www.google.com/chromeframe/?redirect=true">activate Google Chrome Frame</a> to
	improve your experience.</p>
<![endif]-->

<!-- This code is taken from http://twitter.github.com/bootstrap/examples/hero.html -->

<div class="navbar navbar-inverse navbar-fixed-top">
	<div class="navbar-inner">
		<div class="container">

			<span class="brand">RenoTracks</span>

			<ul class="nav">
				<li><a href="/">Home</a></li>
				<li class="active"><a href=".">Map</a></li>
				<li class="tally.php"><a href="tally.php">Bike to Work Week</a></li>
			</ul>

		</div>
	</div>
</div>

<div class="row-fluid">
	<div class="span12">
		<div id="mapBody"></div>
	</div>

</div>
<!-- /container -->

<script src="//ajax.googleapis.com/ajax/libs/jquery/1.8.3/jquery.min.js"></script>
<script>window.jQuery || document.write( '<script src="js/vendor/jquery-1.8.3.min.js"><\/script>' )</script>
<script src="http://cdn.leafletjs.com/leaflet-0.7/leaflet.js"></script>

<script src="js/vendor/bootstrap.min.js"></script>

<script>
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

var App = {
	lines: {},

	fetchData: function(query) {
		var self = App;
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
			this.lines[data[0].trip_id] = L.polyline(latlngs, {color: 'red', weight: 3, opacity: 0.1})
				.on( 'click', function( e ) {
					console.log( data[0].trip_id );
				} )
				.addTo(map);
		}
	}
};

var trips = <?php echo $trips; ?>;
$.each( trips, function( id ) {
	App.fetchData(id);
});


</script>

</body>
</html>
