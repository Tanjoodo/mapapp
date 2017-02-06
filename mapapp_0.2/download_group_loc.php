<?php

class group_user {
	public $id;
	public $lat;
	public $lng;
	public $status;
	public $online;
	public $name;

	public function __construct($id, $lat, $lng, $status, $online, $name) {
		$this->id = $id;
		$this->lat = $lat;
		$this->lng = $lng;
		$this->status = $status;

		if ($online === 0) {
			$this->online = false;
		} else {
			$this->online = true;
		}

		if ($name === "") {
			$this->name = $id;
		} else {
			$this->name = $name;
		}
	}
}

function row_to_user($row) {
	if (isset($row["screen_name"])) {
		return new group_user(
			$row["phone_number"],
			$row["lat"],
			$row["lng"],
			$row["status"],
			$row["is_online"],
			$row["screen_name"]
		);
	}

	return new group_user(
		$row["phone_number"],
		$row["lat"],
		$row["lng"],
		$row["status"],
		$row["is_online"],
		$row["phone_number"]
	);
}

require_once "connect_db.php";
require_once "parse_params.php";
require_once "error.php";
require_once "user.php";
include "debug.php";
header('Content-Type: application/json');

$conn = connect_to_db();
$params = parse_params('download_group_loc.php');

$tok = $params[1];
$gid = $params[2];

if (!(isset($tok) && isset($gid))) {
	error_response(400, "Please insert all parameters");
}

$get_uid_from_token_result = get_user_from_tok($conn, $tok);
if ($get_uid_from_token_result === false) {
	error_response(500, "Database error when getting uid from token.");
}

if ($get_uid_from_token_result->num_rows === 0) {
	error_response(401, "Access token is invalid");
} else if ($get_uid_from_token_result->num_rows === 1) {
	$uid = $get_uid_from_token_result->fetch_assoc()['phone_number'];

	$check_user_is_in_group_query = $conn->prepare("SELECT * FROM ".
		"(SELECT `uid` FROM `group_user` WHERE `gid`=?) g_u ".
		"WHERE `uid`=?");
	$check_user_is_in_group_query->bind_param('ss', $gid, $uid);
	$check_user_is_in_group_query->execute();

	$check_user_is_in_group_result = $check_user_is_in_group_query->get_result();

	if ($check_user_is_in_group_result === false) {
		error_response(500, "Database error when checking if user is in group");
	}


	if ($check_user_is_in_group_result->num_rows === 0) {
		error_response(
			401,
			"You do not belong in this group or this group does not exist."
		);
	}

	$get_group_users_query = $conn->prepare(
		"SELECT * FROM user WHERE phone_number IN (SELECT uid from group_user WHERE gid=?) AND phone_number<>?"
	);
	$get_group_users_query->bind_param("ss", $gid, $uid);
	$get_group_users_query->execute();
	$get_group_users_result = $get_group_users_query->get_result();

	if ($get_group_users_result === false) {
		error_response(500, "Database error when getting group users");
	}

	$users = array("Users"=>array_map(
		'row_to_user',
		$get_group_users_result->fetch_all(MYSQLI_ASSOC)
	));

	echo json_encode($users);
}
?>
