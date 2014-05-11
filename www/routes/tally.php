<?php
set_include_path( dirname( dirname( dirname( __FILE__ ) ) ) . '/include' . PATH_SEPARATOR . get_include_path() );
include_once('TripFactory.php');
$after = '2014-05-10';
$before = '2014-05-17';

$total = TripFactory::getTripMileage( $after, $before );
$women = TripFactory::getTripMileageByAttribute( 'user.gender', 1, $after, $before );
$men = TripFactory::getTripMileageByAttribute( 'user.gender', 2, $after, $before );
$under18 = TripFactory::getTripMileageByAttribute( 'user.age', 1, $after, $before );
$eighteen24 = TripFactory::getTripMileageByAttribute( 'user.age', 2, $after, $before );
$twentyfive34 = TripFactory::getTripMileageByAttribute( 'user.age', 3, $after, $before );
$thirtyfive44 = TripFactory::getTripMileageByAttribute( 'user.age', 4, $after, $before );
$fourtyfive54 = TripFactory::getTripMileageByAttribute( 'user.age', 5, $after, $before );
$fiftyfive64 = TripFactory::getTripMileageByAttribute( 'user.age', 6, $after, $before );
$sixtyfiveup = TripFactory::getTripMileageByAttribute( 'user.age', 7, $after, $before );
$commute = TripFactory::getTripMileageByAttribute( 'purpose', 'Commute', $after, $before );
$errand = TripFactory::getTripMileageByAttribute( 'purpose', 'Errand', $after, $before );
$exercise = TripFactory::getTripMileageByAttribute( 'purpose', 'Exercise', $after, $before );
$work = TripFactory::getTripMileageByAttribute( 'purpose', 'Work-Related', $after, $before );
$school = TripFactory::getTripMileageByAttribute( 'purpose', 'School', $after, $before );
$shopping = TripFactory::getTripMileageByAttribute( 'purpose', 'Shopping', $after, $before );
$social = TripFactory::getTripMileageByAttribute( 'purpose', 'Social', $after, $before );
$other = TripFactory::getTripMileageByAttribute( 'purpose', 'Other', $after, $before );

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
	<title>RenoTracks Bike To Work Week Stats</title>
	<meta name="description" content="">
	<meta name="viewport" content="width=device-width">

	<link rel="stylesheet" href="css/bootstrap.min.css">
	<link rel="stylesheet" href="http://cdn.leafletjs.com/leaflet-0.7/leaflet.css"/>
	<link rel="stylesheet" href="css/bootstrap-responsive.min.css">
	<link rel="stylesheet" href="css/main.css">

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
				<li><a href="/routes/">Map</a></li>
				<li class="active"><a href=".">Bike to Work Week</a></li>
			</ul>

		</div>
	</div>
</div>

<section class="masthead">
	<div class="container">
		<h1>Miles tracked for bike to work, school, fun week May 10-16</h1>
	</div>
</section>

<section class="stats row-fluid">
	<div class="span4">
		<div class="well">
			<p>Total</p>
			<p><span class="stat"><?php echo $total; ?></span></p>
		</div>
	</div>
	<div class="span4">
		<div class="well">
			<p>Women</p>
			<p><span class="stat"><?php echo $women; ?></span></p>
		</div>
	</div>
	<div class="span4">
		<div class="well">
			<p>Men</p>
			<p><span class="stat"><?php echo $men; ?></span></p>
		</div>
	</div>
</section>

<section class="stats row-fluid">
	<div class="span4">
		<div class="well">
			<p>Under 18</p>
			<p><span class="stat"><?php echo $under18; ?></span></p>
		</div>
	</div>
	<div class="span4">
		<div class="well">
			<p>18-24</p>
			<p><span class="stat"><?php echo $eighteen24; ?></span></p>
		</div>
	</div>
	<div class="span4">
		<div class="well">
			<p>25-34</p>
			<p><span class="stat"><?php echo $twentyfive34; ?></span></p>
		</div>
	</div>
</section>

<section class="stats row-fluid">
	<div class="span4">
		<div class="well">
			<p>35-44</p>
			<p><span class="stat"><?php echo $thirtyfive44; ?></span></p>
		</div>
	</div>
	<div class="span4">
		<div class="well">
			<p>45-54</p>
			<p><span class="stat"><?php echo $fourtyfive54; ?></span></p>
		</div>
	</div>
	<div class="span4">
		<div class="well">
			<p>55-64</p>
			<p><span class="stat"><?php echo $fiftyfive64; ?></span></p>
		</div>
	</div>
</section>

<section class="stats row-fluid">
	<div class="span4">
		<div class="well">
			<p>65+</p>
			<p><span class="stat"><?php echo $sixtyfiveup; ?></span></p>
		</div>
	</div>
	<div class="span4">
		<div class="well">
			<p>Commute</p>
			<p><span class="stat"><?php echo $commute; ?></span></p>
		</div>
	</div>
	<div class="span4">
		<div class="well">
			<p>Errand</p>
			<p><span class="stat"><?php echo $errand; ?></span></p>
		</div>
	</div>
</section>

<section class="stats row-fluid">
	<div class="span4">
		<div class="well">
			<p>Exercise</p>
			<p><span class="stat"><?php echo $exercise; ?></span></p>
		</div>
	</div>
	<div class="span4">
		<div class="well">
			<p>School</p>
			<p><span class="stat"><?php echo $school; ?></span></p>
		</div>
	</div>
	<div class="span4">
		<div class="well">
			<p>Social</p>
			<p><span class="stat"><?php echo $social; ?></span></p>
		</div>
	</div>
</section>

<section class="stats row-fluid">
	<div class="span4">
		<div class="well">
			<p>Work-Related</p>
			<p><span class="stat"><?php echo $work; ?></span></p>
		</div>
	</div>
	<div class="span4">
		<div class="well">
			<p>Other</p>
			<p><span class="stat"><?php echo $other; ?></span></p>
		</div>
	</div>
	<div class="span4">
		<div class="well">
			<p>Pump up those numbers! Also check out the <a href="http://bikenevada.org">Commuter Challenge!</a></p>
		</div>
	</div>
</section>

<script src="//ajax.googleapis.com/ajax/libs/jquery/1.8.3/jquery.min.js"></script>
<script>window.jQuery || document.write( '<script src="js/vendor/jquery-1.8.3.min.js"><\/script>' )</script>

<script src="js/vendor/bootstrap.min.js"></script>


</body>
</html>
