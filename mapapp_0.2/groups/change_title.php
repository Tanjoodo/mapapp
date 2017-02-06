<?php
require_once "../connect_db.php";
require_once "../error.php";
require_once "../user.php";
include "../debug.php";
require_once "groups.php";

$conn = connect_to_db();

$tok = $_GET['tok'];
$gid = $_GET['gid'];
$title = $_GET['title'];

if (!(isset($tok) && isset($gid) && isset($title))) {
	error_response(400, 'Please provide all parameters');
}

$title = urldecode($title);
$user_from_tok = get_user_from_tok($conn, $tok);

if ($user_from_tok->num_rows === 0) {
	error_response(401, 'Invalid access token');
} else if ($user_from_tok->num_rows === 1) {
	$uid = $user_from_tok->fetch_assoc()['phone_number'];
	$group_from_gid = get_group_from_gid($conn, $gid);
	if ($group_from_gid->num_rows === 0) {
		error_response(400, 'Group does not exist');
	} else if ($group_from_gid->num_rows === 1) {
		$group_user = get_group_user($conn, $uid, $gid);
		if ($group_user->num_rows === 0) {
			error_response(401, 'You do not belong to this group');
		} else if ($group_user->num_rows === 1) {
			$is_admin = $group_user->fetch_assoc()['is_admin'];
			if ($is_admin) {
				$change_title_query = $conn->prepare("UPDATE u_group SET title=? WHERE id=?");
				$change_title_query->bind_param('ss', $title, $gid);
				$change_title_query->execute();
				echo json_encode(array('message'=>'Title updated successfully'));
			} else {
				error_response(401, 'You do not have permission to update the title');
			}
		}  else {
			error_response(500, 'Multiple entries for gid/uid pair');
		}
	} else {
		error_response(500, 'Multiple groups with the same gid');
	}
} else {
	error_response(500, 'Multiple users with the same access token (!!!)');
}
?>

