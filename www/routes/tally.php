<?php
set_include_path( dirname( dirname( dirname( __FILE__ ) ) ) . '/include' . PATH_SEPARATOR . get_include_path() );
include_once('TripFactory.php');
$after = null; // Set to null for all-time
$before = null; // Set to null for all-time
$heading = 'Miles tracked by RenoTracks'; // Miles tracked by RenoTracks for all-time

$total = TripFactory::getTripMileage( $after, $before );
$total_count = TripFactory::getTripCount( $after, $before );
$women = TripFactory::getTripMileageByAttribute( 'user.gender', 1, $after, $before );
$women_count = TripFactory::getTripCountByAttribute( 'user.gender', 1, $after, $before );
$men = TripFactory::getTripMileageByAttribute( 'user.gender', 2, $after, $before );
$men_count = TripFactory::getTripCountByAttribute( 'user.gender', 2, $after, $before );
$under18 = TripFactory::getTripMileageByAttribute( 'user.age', 1, $after, $before );
$under18_count = TripFactory::getTripCountByAttribute( 'user.age', 1, $after, $before );
$eighteen24 = TripFactory::getTripMileageByAttribute( 'user.age', 2, $after, $before );
$eighteen24_count = TripFactory::getTripCountByAttribute( 'user.age', 2, $after, $before );
$twentyfive34 = TripFactory::getTripMileageByAttribute( 'user.age', 3, $after, $before );
$twentyfive34_count = TripFactory::getTripCountByAttribute( 'user.age', 3, $after, $before );
$thirtyfive44 = TripFactory::getTripMileageByAttribute( 'user.age', 4, $after, $before );
$thirtyfive44_count = TripFactory::getTripCountByAttribute( 'user.age', 4, $after, $before );
$fourtyfive54 = TripFactory::getTripMileageByAttribute( 'user.age', 5, $after, $before );
$fourtyfive54_count = TripFactory::getTripCountByAttribute( 'user.age', 5, $after, $before );
$fiftyfive64 = TripFactory::getTripMileageByAttribute( 'user.age', 6, $after, $before );
$fiftyfive64_count = TripFactory::getTripCountByAttribute( 'user.age', 6, $after, $before );
$sixtyfiveup = TripFactory::getTripMileageByAttribute( 'user.age', 7, $after, $before );
$sixtyfiveup_count = TripFactory::getTripCountByAttribute( 'user.age', 7, $after, $before );
$commute = TripFactory::getTripMileageByAttribute( 'purpose', 'Commute', $after, $before );
$commute_count = TripFactory::getTripCountByAttribute( 'purpose', 'Commute', $after, $before );
$errand = TripFactory::getTripMileageByAttribute( 'purpose', 'Errand', $after, $before );
$errand_count = TripFactory::getTripCountByAttribute( 'purpose', 'Errand', $after, $before );
$exercise = TripFactory::getTripMileageByAttribute( 'purpose', 'Exercise', $after, $before );
$exercise_count = TripFactory::getTripCountByAttribute( 'purpose', 'Exercise', $after, $before );
$work = TripFactory::getTripMileageByAttribute( 'purpose', 'Work-Related', $after, $before );
$work_count = TripFactory::getTripCountByAttribute( 'purpose', 'Work-Related', $after, $before );
$school = TripFactory::getTripMileageByAttribute( 'purpose', 'School', $after, $before );
$school_count = TripFactory::getTripCountByAttribute( 'purpose', 'School', $after, $before );
$shopping = TripFactory::getTripMileageByAttribute( 'purpose', 'Shopping', $after, $before );
$shopping_count = TripFactory::getTripCountByAttribute( 'purpose', 'Shopping', $after, $before );
$social = TripFactory::getTripMileageByAttribute( 'purpose', 'Social', $after, $before );
$social_count = TripFactory::getTripCountByAttribute( 'purpose', 'Social', $after, $before );
$other = TripFactory::getTripMileageByAttribute( 'purpose', 'Other', $after, $before );
$other_count = TripFactory::getTripCountByAttribute( 'purpose', 'Other', $after, $before );

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
	<title>RenoTracks Stats</title>
	<meta name="description" content="">
	<meta name="viewport" content="width=device-width">

	<link rel="stylesheet" href="css/bootstrap.min.css">
	<link rel="stylesheet" href="http://cdn.leafletjs.com/leaflet-0.7/leaflet.css"/>
	<link rel="stylesheet" href="css/main.css">

</head>
<body>
<!--[if lt IE 7]>
<p class="chromeframe">You are using an <strong>outdated</strong> browser. Please <a href="http://browsehappy.com/">upgrade
	your browser</a> or <a href="http://www.google.com/chromeframe/?redirect=true">activate Google Chrome Frame</a> to
	improve your experience.</p>
<![endif]-->

<!-- This code is taken from http://twitter.github.com/bootstrap/examples/hero.html -->

<nav class="navbar navbar-inverse" role="navigation">
	<div class="container-fluid">
		<div class="navbar-header">
				<button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#bs-example-navbar-collapse-1">
					<span class="sr-only">Toggle navigation</span>
					<span class="icon-bar"></span>
					<span class="icon-bar"></span>
					<span class="icon-bar"></span>
				</button>
				<a class="navbar-brand" href="#">RenoTracks</a>
			</div>
			<div class="collapse navbar-collapse" id="bs-example-navbar-collapse-1">
				<ul class="nav navbar-nav">
				<li><a href="/">Home</a></li>
				<li><a href=".">Map</a></li>
				<li class="active"><a href="tally.php">Tallies</a></li>
			</ul>
			</div>
	</div>
</nav>

<section class="masthead">
	<div class="container">
		<h1><?php echo $heading; ?></h1>
	</div>
</section>

<section class="stats row-fluid">
	<div class="col-md-4">
		<div class="well">
			<p>Total</p>
			<p><span class="stat" title="<?php echo $total_count; ?> trips"><?php echo $total; ?></span></p>
		</div>
	</div>
	<div class="col-md-4">
		<div class="well">
			<p>Women</p>
			<p><span class="stat" title="<?php echo $women_count; ?> trips"><?php echo $women; ?></span></p>
		</div>
	</div>
	<div class="col-md-4">
		<div class="well">
			<p>Men</p>
			<p><span class="stat" title="<?php echo $men_count; ?> trips"><?php echo $men; ?></span></p>
		</div>
	</div>
</section>

<section class="stats row-fluid">
	<div class="col-md-4">
		<div class="well">
			<p>Under 18</p>
			<p><span class="stat" title="<?php echo $under18_count; ?> trips"><?php echo $under18; ?></span></p>
		</div>
	</div>
	<div class="col-md-4">
		<div class="well">
			<p>18-24</p>
			<p><span class="stat" title="<?php echo $eighteen24_count; ?> trips"><?php echo $eighteen24; ?></span></p>
		</div>
	</div>
	<div class="col-md-4">
		<div class="well">
			<p>25-34</p>
			<p><span class="stat" title="<?php echo $twentyfive34_count; ?> trips"><?php echo $twentyfive34; ?></span></p>
		</div>
	</div>
</section>

<section class="stats row-fluid">
	<div class="col-md-4">
		<div class="well">
			<p>35-44</p>
			<p><span class="stat" title="<?php echo $thirtyfive44_count; ?> trips"><?php echo $thirtyfive44; ?></span></p>
		</div>
	</div>
	<div class="col-md-4">
		<div class="well">
			<p>45-54</p>
			<p><span class="stat" title="<?php echo $fourtyfive54_count; ?> trips"><?php echo $fourtyfive54; ?></span></p>
		</div>
	</div>
	<div class="col-md-4">
		<div class="well">
			<p>55-64</p>
			<p><span class="stat" title="<?php echo $fiftyfive64_count; ?> trips"><?php echo $fiftyfive64; ?></span></p>
		</div>
	</div>
</section>

<section class="stats row-fluid">
	<div class="col-md-4">
		<div class="well">
			<p>65+</p>
			<p><span class="stat" title="<?php echo $sixtyfiveup_count; ?> trips"><?php echo $sixtyfiveup; ?></span></p>
		</div>
	</div>
	<div class="col-md-4">
		<div class="well">
			<p>Commute</p>
			<p><span class="stat" title="<?php echo $commute_count; ?> trips"><?php echo $commute; ?></span></p>
		</div>
	</div>
	<div class="col-md-4">
		<div class="well">
			<p>Errand</p>
			<p><span class="stat" title="<?php echo $errand_count; ?> trips"><?php echo $errand; ?></span></p>
		</div>
	</div>
</section>

<section class="stats row-fluid">
	<div class="col-md-4">
		<div class="well">
			<p>Exercise</p>
			<p><span class="stat" title="<?php echo $exercise_count; ?> trips"><?php echo $exercise; ?></span></p>
		</div>
	</div>
	<div class="col-md-4">
		<div class="well">
			<p>School</p>
			<p><span class="stat" title="<?php echo $school_count; ?> trips"><?php echo $school; ?></span></p>
		</div>
	</div>
	<div class="col-md-4">
		<div class="well">
			<p>Social</p>
			<p><span class="stat" title="<?php echo $social_count; ?> trips"><?php echo $social; ?></span></p>
		</div>
	</div>
</section>

<section class="stats row-fluid">
	<div class="col-md-4">
		<div class="well">
			<p>Work-Related</p>
			<p><span class="stat" title="<?php echo $work_count; ?> trips"><?php echo $work; ?></span></p>
		</div>
	</div>
	<div class="col-md-4">
		<div class="well">
			<p>Other</p>
			<p><span class="stat" title="<?php echo $other_count; ?> trips"><?php echo $other; ?></span></p>
		</div>
	</div>
	<div class="col-md-4">
		<div class="well">
			<p>KEEP CALM AND TRACK ON</p>
		</div>
	</div>
</section>

<script src="//ajax.googleapis.com/ajax/libs/jquery/1.8.3/jquery.min.js"></script>
<script>window.jQuery || document.write( '<script src="js/vendor/jquery-1.8.3.min.js"><\/script>' )</script>

<script src="js/vendor/bootstrap.min.js"></script>


</body>
</html>
