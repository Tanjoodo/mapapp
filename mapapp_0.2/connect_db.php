<?php
function connect_to_db() {
	$server_name = "localhost";
	$username = "mapapp_backend";
	$password = "jlJZXWnLZNY4Y54b";
	$db_name = "mapapp2";
	$conn = new mysqli($server_name, $username, $password, $db_name);
	if ($conn->connect_error) {
		die("Connection error: ".$conn->connect_error);
	}
	return $conn;
}
?>
