<?php
require_once "Database.php";
require_once "UserFactory.php";

$db = DatabaseConnectionFactory::getConnection();
$users = $db->query( "SELECT * FROM user" );
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
	<title>Submit a GPX</title>
	<meta name="description" content="">
	<meta name="viewport" content="width=device-width">
</head>
<body>
	<form enctype="multipart/form-data" action="parse.php" method="POST">
		<input type="hidden" name="MAX_FILE_SIZE" value="2000000" />
		<p>
			User:
			<select name="user_id">
				<?php while( $user = $users->fetch_object() ) : ?>
					<option value="<?php echo $user->id; ?>"><?php echo $user->id . ' ' . $user->email; ?></option>
				<?php endwhile; ?>
			</select>
		</p>

		<p>
			Purpose:
			<select name="purpose">
				<option>School</option>
				<option>Work</option>
				<option>Recreation</option>
				<option>Errand</option>
				<option>Exercise</option>
				<option>other</option>
			</select>
		</p>

		<p>
			Notes:
			<input name="notes" type="text" />
		</p>

		Send this file: <input name="gpxfile" type="file" />
		<input type="submit" value="Send File" />
	</form>
</body>
</html>
