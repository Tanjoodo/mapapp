<?php
require_once "../error.php";

function get_group_user($conn, $uid, $gid) {
	$query = $conn->prepare("SELECT * FROM group_user WHERE uid=? AND gid=?");
	$query->bind_param('ss', $uid, $gid);
	$query->execute();
	$result = $query->get_result();
	if ($result === false) {
		error_response(500, "Database error while getting group user");
	}
	return $result;
}

function get_group_from_gid($conn, $gid) {
	$query = $conn->prepare("SELECT * FROM u_group WHERE id=?");
	$query->bind_param('s', $gid);
	$query->execute();
	$result = $query->get_result();
	if ($result === false) {
		error_response(500, "Database error getting group information");
	}
	return $result;
}
?>
