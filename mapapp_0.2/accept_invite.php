<?php
require_once "connect_db.php";
require_once "error.php";
require_once "user.php";
require_once "parse_params.php";
include "debug.php";

$conn = connect_to_db();
$params = parse_params("accept_invite.php");

$tok = $params[1];
$gid = $params[2];
$response = $params[3];

if (!(isset($tok) && isset($gid) && isset($response))) {
	error_response(400, "One or more parameter missing");
} else if (!($response === "yes" | $response === "no")) {
	error_response(400, "invalid response");
}

$user_row = get_user_from_tok($conn, $tok);

if ($user_row->num_rows === 0) {
	error_response(401, "Invalid access token $tok");
} else if ($user_row->num_rows === 1) {
	$uid = $user_row->fetch_assoc()["phone_number"];
	$get_user_in_group_query = 
		$conn->prepare("SELECT confirmed FROM group_user WHERE uid=? AND gid=?");
	$get_user_in_group_query->bind_param("ss", $uid, $gid);
	$get_user_in_group_query->execute();
	$get_user_in_group_result = $get_user_in_group_query->get_result();

	if ($get_user_in_group_result === false) {
		error_response(500, "Error while getting the user's rescord in group");
	}

	if ($get_user_in_group_result->num_rows === 0) {
		error_response(400, "You have not been invited to this group");
	} else if ($get_user_in_group_result->num_rows === 1) {
		if ($get_user_in_group_result->fetch_assoc()["confirmed"] === 1) {
			error_response(400, "You are already in this group");
		}

		if ($response === "yes") {
			$accept_query = $conn->prepare("UPDATE group_user SET confirmed=1 WHERE uid=? AND gid=?");
			$accept_query->bind_param("ss", $uid, $gid);
			$accept_query->execute();
		} else if ($response === "no") {
			$decline_query = $conn->prepare("DELETE FROM group_user WHERE uid=? AND gid=?");
			$decline_query->bind_param("ss", $uid, $gid);
			$decline_query->execute();
		}
		echo json_encode(array("message"=>"Request successful"));
	}
} else {
	error_response(400, "More than one user with the same access token (!!!)");
}
?>
