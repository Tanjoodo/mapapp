<?php
require_once "../connect_db.php";
require_once "../error.php";
require_once "../user.php";
include "../debug.php";
require_once "groups.php";

class friend {
	public $id;
	public $name;

	public function __construct($id, $name) {
		$this->id = $id;
		$this->name = $name;
	}

}

function row_to_friend($row) {
	if ($row['screen_name'] !== ""){
		return new friend(
			$row['uid'],
			$row['screen_name']
		);
	} else {
		return new friend(
			$row['uid'],
			$row['uid']
		);
	}
}

$conn = connect_to_db();

$tok = $_GET['tok'];
$gid = $_GET['gid'];

if (!(isset($tok) && isset($gid))) {
	error_response(400, 'Please provide all parameters');
}

$user_from_tok = get_user_from_tok($conn, $tok);

if ($user_from_tok->num_rows === 0) {
	error_response(401, 'Invalid access token');
} else if ($user_from_tok->num_rows === 1) {
	$uid = $user_from_tok->fetch_assoc()['phone_number'];
	$group = get_group_from_gid($conn, $gid);
	if ($group->num_rows === 0) {
		error_response(400, 'Group does not exist');
	} else if ($group->num_rows === 1) {
		$group_user = get_group_user($conn, $uid, $gid);
		if ($group_user->num_rows === 0) {
			error_response(401, "You do not belong to this group");
		} else {
			$get_friends_query = $conn->prepare(' SELECT uid, screen_name FROM group_user '.
				'INNER JOIN user ON uid=phone_number WHERE gid=? AND uid<>?');
			$get_friends_query->bind_param('ss', $gid, $uid);
			$get_friends_query->execute();

			$get_friends_result = $get_friends_query->get_result();
			if ($get_friends_result === false) {
				error_response(500, "Database error getting friends");
			}

			echo json_encode(array('friends'=>array_map(
				'row_to_friend',
				$get_friends_result->fetch_all(MYSQLI_ASSOC)
			)));
		}

	}
}
?>
