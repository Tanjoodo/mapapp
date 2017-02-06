<?php
error_reporting(E_ALL);
ini_set("display_startup_errors", 1);
ini_set("display_errors", 1);

//header('Content-Type: application/json');

require_once "connect_db.php";
require_once "parse_params.php";
$conn = connect_to_db();
$params = parse_params('logout.php');
$tok = $params[1];

if (!isset($tok)) {
	header($_SERVER["SERVER_PROTOCOL"]." 401 Unauthorized", true, 401);
	$output = array("error"=>"Please provide all parameters");
	echo json_encode($output);
	die();
}

$get_user_from_token_query =
	"SELECT id FROM user WHERE access_token='$tok'";
$get_user_from_token_result = $conn->query($get_user_from_token_query);

if ($get_user_from_token_result === false) {
	header($_SERVER["SERVER_PROTOCOL"]." 500 INTERNAL SERVER ERROR", true, 500);
	$output = array("error"=>"Error accessing database to get user from token");
	echo json_encode($output);
	die();
}

if ($get_user_from_token_result->num_rows === 0) {
	header($_SERVER["SERVER_PROTOCOL"]." 401 Unauthorized", true, 401);
	$output = array("error"=>"Invalid access token");
	echo json_encode($output);
	die();
} else if ($get_user_from_token_result->num_rows === 1) {
	$id = $get_user_from_token_result->fetch_assoc()['id'];
	$nullify_access_token_query = 
		"UPDATE user SET access_token=NULL WHERE id='$id'";
	if ($conn->query($nullify_access_token_query) === false) {
		header($_SERVER["SERVER_PROTOCOL"]." 500 INTERNAL SERVER ERROR", true, 500);
		$output = array("error"=>"Error nullifying access token");
		echo json_encode($output);
		die();
	} else {
		$output = array("message"=>"User logged out successfully");
		echo json_encode($output);
	}
}

$conn->close();
?>
