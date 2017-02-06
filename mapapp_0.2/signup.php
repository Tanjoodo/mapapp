<?php
require_once "connect_db.php";
require_once "error.php";
require_once "user.php";
require_once "random_string.php";
include "debug.php";
header('Content-Type: application/json');

$conn = connect_to_db();

$id = $_POST["id"];
$password = $_POST["password"];

if (!preg_match("/^\+([0-9]{1,3}|[0-9]{1,3}\-[0-9]{1,3})[1-9][0-9]{1,}$/", $id)) {
	error_response(400, "Invalid phone number: $id");
}

$default_status = "Hello there! I'm using MapApp!";
$default_public = 1;
$default_online = 0;

$get_user_from_id_query = $conn->prepare("SELECT `phone_number` FROM user WHERE phone_number=?");
$get_user_from_id_query->bind_param('s', $id);
$get_user_from_id_query->execute();
$get_user_from_id_result = $get_user_from_id_query->get_result();

if ($get_user_from_id_result->num_rows === 0) {
	$pass_salt = random_str(16);
	$pass_hash = hash("sha256", $password.$pass_salt, false);
	$access_token = bin2hex(openssl_random_pseudo_bytes(16));
	$add_new_user_query = $conn->prepare(
		"INSERT INTO user ".
		"(phone_number, status, access_token, is_public, pass_hash, pass_salt, is_online, screen_name)".
		"VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
	);

	$add_new_user_query->bind_param(
		"sssissis",
		$id,
		$default_status,
		$access_token,
		$default_public,
		$pass_hash,
		$pass_salt,
		$default_online,
		$id
	);
	$add_new_user_query->execute();
	$add_new_user_result = $add_new_user_query->get_result(); 
	// TODO find why this false positives
	/*if ($add_new_user_result->error_message) {
		error_response(500, "Database error while adding new user");
	}*/

	echo json_encode(array("tok"=>$access_token));
} else {
	error_response(400, "Invalid username/password");
}

?>
