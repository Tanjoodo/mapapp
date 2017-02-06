<?php
header('Content-Type: application/json');

require_once "connect_db.php";
require_once "parse_params.php";
require_once "error.php";
require_once "user.php";
require_once "date.php";
include "debug.php";

$conn = connect_to_db();
$params = parse_params('update_pos.php');

$tok = $params[1];
$lat = floatval($params[2]);
$lng = floatval($params[3]);
$status = $params[4];

$find_user_by_access_token_result = get_user_from_tok($conn, $tok);

if ($find_user_by_access_token_result === false) {
	error_response(500,"Database error when querying for user.");
}

if (mysqli_num_rows($find_user_by_access_token_result) === 0) {
	error_response(401, "Access token invalid $tok");
} else if (mysqli_num_rows($find_user_by_access_token_result) === 1) {
	global $tok, $lat, $lng, $status;
	$now_timestamp = get_now_timestamp();
	//TODO figure out why this doesn't work with prepared statements and make it use them.
	$estatus = $conn->escape_string($status);
	$etok = $conn->escape_string($tok);
	$update_user_info_query =
		"UPDATE `user` SET `lat`='$lat',`lng`='$lng',`status`='$estatus',`last_update`='$now_timestamp' WHERE `access_token`='$etok'";
	
	$update_user_info_result = $conn->query($update_user_info_query);
	if ($update_user_info_result === false) {
		error_response(500, "Error when updating user's info.");
	}
	$output = array("message" => "User info updated successfully");
	echo json_encode($output);
} else {
	error_response(500, "More than one user with the same access token (!!!)");
}
$conn->close();
?>
