<?php
require_once "../connect_db.php";
require_once "../error.php";
require_once "../user.php";
require_once "../date.php";
include "../debug.php";
require_once "groups.php";

$conn = connect_to_db();

$tok = $_GET['tok'];
$gid = $_GET['gid'];
$id = $_GET['id'];

if (!(isset($tok) && isset($gid) && isset($id))) {
	error_response(400, 'Please provide all parameters');
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
			$invitee = get_group_user($conn, $id, $gid);
			if ($invitee->num_rows === 1) {
				$confirmed = $invitee->fetch_assoc()['confirmed'];
				if ($confirmed) {
					error_response(400, 'User already in group');
				} else {
					error_response(400, 'User already invited to group');
				}
			} else if ($invitee->num_rows === 0) {
				$now_timestamp = get_now_timestamp();
				$def_is_admin = 0;
				$def_confirmed = 0;
				$invite_query = $conn->prepare("INSERT INTO group_user VALUES (?, ?, ?, ?, ?, ?)");
				$invite_query->bind_param(
					'ssiiss',
					$gid,
					$id,
					$def_is_admin,
					$def_confirmed,
					$now_timestamp,
					$uid
				);
				$invite_query->execute();
				echo json_encode(array('message'=>'Invitation sent.'));
			}
		}
	}
}
?>
