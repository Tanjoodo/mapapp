<?php
require_once "../connect_db.php";
require_once "../error.php";
require_once "../user.php";
require_once "../date.php";
include "../debug.php";
require_once "groups.php";

$conn = connect_to_db();

$tok = $_GET['tok'];
$title = $_GET['title'];

if (!(isset($tok) && isset($title))) {
	error_response(400, "Please provide all parameters");
}

$title = urldecode($title);

$user_from_tok = get_user_from_tok($conn, $tok);
if ($user_from_tok->num_rows === 0) {
	error_response(401, 'Invalid access token');
} else if ($user_from_tok->num_rows === 1) {
	$uid = $user_from_tok->fetch_assoc()["phone_number"];
	$new_gid = bin2hex(openssl_random_pseudo_bytes(16));
	$new_group_query = $conn->prepare("INSERT INTO u_group (id, title) VAlUES (?, ?)");
	$new_group_query->bind_param('ss', $new_gid, $title);
	$new_group_query->execute();

	$now_timestamp = get_now_timestamp();
	$def_is_admin = 1;
	$def_confirmed = 1;
	$add_user_query = $conn->prepare("INSERT INTO group_user VALUES (?, ?, ?, ?, ?, ?)");
	$add_user_query->bind_param(
		'ssiiss',
		$new_gid,
		$uid,
		$def_is_admin,
		$def_confirmed,
		$now_timestamp,
		$uid
	);
	$add_user_query->execute();
	echo json_encode(array("message"=>"New group created successfully"));
}
?>
