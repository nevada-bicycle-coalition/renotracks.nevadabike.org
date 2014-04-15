<?php
/*
 *
 *TEMPLATE FOR Database CLASS. EDIT APPROPRIATELY AND RENAME TO "Database.php" 
 *
 */
 
require_once( 'Util.php' );

abstract class DatabaseConnection extends mysqli
{

	public function __construct( $host, $user, $password, $database )
	{
		parent::__construct( $host, $user, $password, $database );

		if ( mysqli_connect_error() )
			throw new DatabaseConnectionException();
	}

	public function query( $query )
	{
		if ( !($result = parent::query( $query ) ) )
			Util::log( __METHOD__ . "() ERROR {$this->errno}: {$this->error}: \"{$query}\"" );
		
		return $result;
	}
}

class LocalDatabaseConnection extends DatabaseConnection 
{
	public function __construct()
	{
		$services = getenv("VCAP_SERVICES");
		if ( $services ) {
			$services_json = json_decode( $services, true );
			$mysql_config = $services_json["mysql-5.1"][0]["credentials"];
			$username = $mysql_config["username"];
			$password = $mysql_config["password"];
			$hostname = $mysql_config["hostname"];
			$port = $mysql_config["port"];
			$host = $hostname;
			$db = $mysql_config["name"];
		} else {
			$username = getenv( 'RT_DB_USER' );
			$password = getenv( 'RT_DB_PASS' );
			$host = getenv( 'RT_DB_HOST' );
			$db = getenv( 'RT_DB_NAME' );
		}

		parent::__construct( $host, $username, $password, $db );
	}
}

class DatabaseConnectionFactory 
{
	static protected $connection = null;

	public static function getConnection()
	{
		if ( self::$connection )
			return self::$connection;
		else
			return self::$connection = new LocalDatabaseConnection();
	}
}

class DatabaseException extends Exception
{
	public function __construct( $message, $code )
	{
		parent::__construct( $message, $code );
	}
}

class DatabaseConnectionException extends DatabaseException
{
	public function __construct( $message=null, $code=null )
	{
		if ( !$message )
			mysqli_connect_error();

		if ( !$code )
			mysqli_connect_errno();

		parent::__construct( mysqli_connect_error(), mysqli_connect_errno() );
	}
}

