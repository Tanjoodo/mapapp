<?php 
require_once "../connect_db.php";
require_once "../error.php";
require_once "../user.php";
include "../debug.php";
require_once "groups.php";

$conn = connect_to_db();

$tok = $_GET["tok"];
$gid = $_GET["gid"];

if (!(isset($tok) && isset($gid))) {
	error_response(400, "Please provide all parameters");
}

$user_from_tok = get_user_from_tok($conn, $tok);
if ($user_from_tok->num_rows === 0) {
	error_response(401, "Invalid token $tok");
} else if ($user_from_tok->num_rows === 1) {
	$group_result = get_group_from_gid($conn, $gid);
	$uid = $user_from_tok->fetch_assoc()['phone_number'];
	if ($group_result->num_rows === 0) {
		error_response(401, "Group does not exist");
	} else if ($group_result->num_rows === 1) {
		$group_user_result = get_group_user($conn, $uid, $gid);
		if ($group_user_result->num_rows === 0) {
			error_response (401, "You do not belong to this group");
		} else if ($group_user_result->num_rows === 1) {
			$user_row = $group_user_result->fetch_assoc();
			$is_admin = $user_row['is_admin'];
			if ($is_admin) {
				$get_all_users_query = $conn->prepare
					("SELECT uid FROM group_user WHERE gid=? AND uid<>?");
				$get_all_users_query->bind_param('ss', $gid, $uid);
				$get_all_users_query->execute();
				$get_all_users_result = $get_all_users_query->get_result();
				if ($get_all_users_result === false) {
					error_response(500, "Database error while getting list of other group users");
				}
				if ($get_all_users_result->num_rows === 0) {
					$delete_query = $conn->prepare
						("DELETE FROM group_user WHERE uid=? AND gid=?");
					$delete_query->bind_param('ss', $uid, $gid);
					$delete_query->execute();

					$delete_query = $conn->prepare
						("DELETE FROM u_group WHERE id=?");
					$delete_query->bind_param('s', $gid);
					$delete_query->execute();
				} else {
					$user_count = $get_all_users_result->num_rows;
					$new_admin = rand(0, $user_count - 1);
					$users = $get_all_users_result->fetch_all(MYSQLI_ASSOC);
					$admin_uid = $users[$new_admin]['uid'];
					$make_admin_query = $conn->prepare
						('UPDATE group_user SET is_admin=1 WHERE uid=? AND gid=?');
					$make_admin_query->bind_param('ss', $admin_uid, $gid);
					$make_admin_query->execute();
					$delete_query = $conn->prepare
						("DELETE FROM group_user WHERE uid=? AND gid=?");
					$delete_query->bind_param('ss', $uid, $gid);
					$delete_query->execute();
				}
			} else {

				$delete_query = $conn->prepare
					("DELETE FROM group_user WHERE uid=? AND gid=?");
				$delete_query->bind_param('ss', $uid, $gid);
				$delete_query->execute();
			}
		} else {
			error_response(500, "More than one user with the same token (!!!)");

		}
	}
}

echo json_encode(array("message"=>"Group left successfully"));

?>
