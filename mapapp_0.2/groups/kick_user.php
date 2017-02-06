<?php
require_once "../connect_db.php";
require_once "../error.php";
require_once "../user.php";
include "../debug.php";
require_once "groups.php";

$conn = connect_to_db();

$tok = $_GET['tok'];
$gid = $_GET['gid'];
$id = $_GET['id'];

if (!(isset($tok) && isset($gid) & isset($id))) {
	error_response(500, 'Please provide all parameters');
}

$user_from_tok = get_user_from_tok($conn, $tok);

if ($user_from_tok->num_rows === 0) {
	error_response(401, 'Invalid access token');
} else if ($user_from_tok->num_rows === 1) {
	$uid = $user_from_tok->fetch_assoc()['phone_number'];
	$group_user = get_group_user($conn, $uid, $gid);
	if ($group_user->num_rows === 0) {
		error_response(401, 'You do not belong to this group');
	} else if ($group_user->num_rows === 1) {
		$is_admin = $group_user->fetch_assoc()['is_admin'];
		if (!$is_admin) {
			error_response(401, 'You do not have permission to do this');
		} else {
			$kick_user_query = $conn->prepare('DELETE FROM group_user WHERE uid=? AND gid=? AND uid<>?');
			$kick_user_query->bind_param('sss', $id, $gid, $uid);
			$kick_user_query->execute();

			if ($kick_user_query->affected_rows === 1) {
				echo json_encode(array('message'=>'User kicked successfully'));
			} else {
				error_response(400, 'User does not exist or you tried to kick yourself');
			}
		}
	}
}
?>
