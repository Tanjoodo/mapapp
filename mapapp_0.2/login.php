<?php

require_once "connect_db.php";
require_once "error.php";
include "debug.php";

function auth_fail() {
	error_response(401, "Username or password incorrect");
}

$conn = connect_to_db();

$id = $conn->escape_string($_POST["id"]);
$password = $_POST["password"];

if (!(isset($id) || isset($password))) {
	error_response(400, "One or more parameters missing");
}

$get_user_from_id_query = $conn->prepare("SELECT pass_hash, pass_salt FROM user WHERE phone_number=?");
$get_user_from_id_query->bind_param("s", $id);
$get_user_from_id_query->execute();
$get_user_from_id_result = $get_user_from_id_query->get_result();

if ($get_user_from_id_result->num_rows === 0) {
	auth_fail();
} else if ($get_user_from_id_result->num_rows === 1){
	$user_row = $get_user_from_id_result->fetch_assoc();
	$input_hash = hash("sha256", $password.$user_row["pass_salt"], false);
	if ($user_row["pass_hash"] === $input_hash) {
		$new_access_token = bin2hex(openssl_random_pseudo_bytes(16));
		$update_access_token_and_set_online_query = "UPDATE `user` ".
			"SET `access_token`='$new_access_token', `is_online`=true ".
			"WHERE `phone_number`='$id'";
		if ($conn->query($update_access_token_and_set_online_query) === false) {
			error_response(500, "Database error when updating access token and setting user to online");
		}
		$output = array("tok"=>$new_access_token);
		echo json_encode($output);
	} else {
		auth_fail();
	}
}
?>

