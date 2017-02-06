<?php

header('Content-Type: application/json');

require_once "connect_db.php";
require_once "parse_params.php";
require_once "error.php";
require_once "user.php";
include "debug.php";

$conn = connect_to_db();
$params = parse_params('logout.php');
$tok = $params[1];

if (!isset($tok)) {
	error_response(401, "Please provide all parameters");
}

$get_user_from_token_result = get_user_from_tok($conn, $tok);

if ($get_user_from_token_result === false) {
	error_response(500, "Error accessing database to get user from token");
}

if ($get_user_from_token_result->num_rows === 0) {
	error_response(401, "Invalid access token");
} else if ($get_user_from_token_result->num_rows === 1) {
	$id = $get_user_from_token_result->fetch_assoc()['phone_number'];
	$log_out_user_query = $conn->prepare("UPDATE user SET access_token=NULL,is_online=0 WHERE phone_number=?");
	$log_out_user_query->bind_param("s", $id);
	$log_out_user_query->execute();

	if ($conn->error != "") {
		error_response(500,"Databse error logging out user ");
	} else {
		$output = array("message"=>"User logged out successfully");
		echo json_encode($output);
	}
}

$conn->close();
?>
