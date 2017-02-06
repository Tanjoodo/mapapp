<?php
/*error_reporting(E_ALL);
ini_set("display_startup_errors", 1);
ini_set("display_errors", 1);*/

header('Content-Type: application/json');

require_once "connect_db.php";
require_once "parse_params.php";
$conn = connect_to_db();
$params = parse_params('request_token.php');
$id = $params[1];
$tok = $params[2];
$lat = $params[3];
$lng = $params[4];
if (!(isset($id) && isset($lat) && isset($lng) && isset($tok))) {
	header($_SERVER["SERVER_PROTOCOL"]." 401 Unauthorized", true, 401);
	$output = array("error"=>"Please provide all parameters");
	echo json_encode($output);
	die();
}

if (!preg_match("/^[0-9a-fA-F]{16}$/", $id)) {
	header($_SERVER["SERVER_PROTOCOL"]." 401 Unauthorized", true, 401);
	$output = array("error"=>"Malformed ID $id");
	echo json_encode($output);
	die();
} 

$id = $conn->escape_string($id);
$lat = floatval($lat);
$lng = floatval($lng);

$get_user_from_id_query = "SELECT `access_token`, `status` FROM `user` WHERE `id`='$id'";
$get_user_from_id_result = $conn->query($get_user_from_id_query);
if ($get_user_from_id_result === false) {
	header($_SERVER["SERVER_PROTOCOL"]." 500 Internal Server Error", true, 500);
	$output = array("error"=>"Database error when getting user information");
	echo json_encode($output);
}

$rows = mysqli_num_rows($get_user_from_id_result);
if ($rows === 0) {
	global $id, $lat, $lng, $get_user_from_id_result;
	date_default_timezone_set("Asia/Amman");
	$now_timestamp = date("Y-m-d H-i-s");
	$default_status = $conn->escape_string("Hey there! I'm using MapApp.");
	$insert_new_user_query = 
		"INSERT INTO `user` (`id`, `lng`, `lat`, `status`, `last_update`) VALUES ('$id', '$lng', '$lat', '$default_status', '$now_timestamp')";
	$insert_new_user_result = $conn->query($insert_new_user_query);
	if ($insert_new_user_result === false) {
		header($_SERVER["SERVER_PROTOCOL"]." 500 Internal Server Error", true, 500);
		$output = array("error"=>"Database error when inserting new user");
		echo json_encode($output);
		die();
	}

	// Perform query again after inserting the user into the database
	$get_user_from_id_result = $conn->query($get_user_from_id_query);
} 

$result_assoc = $get_user_from_id_result->fetch_assoc();
$access_token = $result_assoc["access_token"];
$status = $result_assoc["status"];
if (!isset($access_token) || $access_token == $tok) {
	global $id;
	$new_access_token = bin2hex(openssl_random_pseudo_bytes(16));
	$update_access_token_query =
		"UPDATE `user` SET `access_token`='$new_access_token' WHERE `id`='$id'";
	if (!$conn->query($update_access_token_query)){
		header($_SERVER["SERVER_PROTOCOL"]." 500 Internal Server Error", true, 500);
		$output = array("error"=>"Database error when updating user info");
		echo json_encode($output);
		die();
	}
	$output = array("access_token"=>$new_access_token, "status"=>$status);
	echo json_encode($output);
} else {
	header($_SERVER["SERVER_PROTOCOL"]." 401 Unauthorized", true, 401);
	$output = array("error"=>"ID already logged in.");
	echo json_encode($output);
	die();
}
$conn->close();
?>
