<?php
/*error_reporting(E_ALL);
ini_set("display_startup_errors", 1);
ini_set("display_errors", 1);*/

header('Content-Type: application/json');

require_once "connect_db.php";
require_once "parse_params.php";
$conn = connect_to_db();
$params = parse_params('update_pos.php');

$tok = $conn->escape_string($params[1]);
$lat = floatval($params[2]);
$lng = floatval($params[3]);
$status = $conn->escape_string($params[4]);

$find_user_by_access_token_query =
	"SELECT `id` FROM `user` WHERE `access_token`='$tok'";
$find_user_by_access_token_result = $conn->query($find_user_by_access_token_query);

if ($find_user_by_access_token_result === false) {
	header($_SERVER["SERVER_PROTOCOL"]." 401 Unauthorized", true, 401);
	$output = array("error"=>"Database error when querying for user.");
	echo json_encode($output);
}

if (mysqli_num_rows($find_user_by_access_token_result) === 0) {
	header($_SERVER["SERVER_PROTOCOL"]." 401 Unauthorized", true, 401);
	$output = array("error"=>"Access token invalid $tok");
	echo json_encode($output);
} else if (mysqli_num_rows($find_user_by_access_token_result) === 1) {
	global $tok, $lat, $lng, $status;
	date_default_timezone_set("Asia/Amman");
	$now_timestamp = date("Y-m-d H-i-s");
	$update_user_info_query =
		"UPDATE `user` SET `lat`='$lat',`lng`='$lng',`status`='$status',`last_update`='$now_timestamp' WHERE `access_token`='$tok'";
	$update_user_info_result = $conn->query($update_user_info_query);
	if ($update_user_info_result === false) {
		die("Error when updating user's info.");
	}
	$output = array("message" => "User info updated successfully");
	echo json_encode($output);
} else {
	header($_SERVER["SERVER_PROTOCOL"]." 500 Internal Server Error", true, 500);
	echo json_encode(array("message"=>"More than one user with the same access token (!!!)"));
}
$conn->close();
?>
