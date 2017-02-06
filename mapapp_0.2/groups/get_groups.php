<?php
require_once "../connect_db.php";
require_once "../error.php";
require_once "../user.php";
include "../debug.php";

class group {
	public $gid;
	public $title;

	public function __construct($gid, $title) {
		$this->gid = $gid;
		$this->title = $title;
	}
}

function row_to_group($row) {
	return new group($row["gid"], $row["title"]);
}

$conn = connect_to_db();

$tok = $_GET["tok"];

if (!isset($tok)) {
	error_response(400, "Missing parameters");
}

$user_row = get_user_from_tok($conn, $tok);
if ($user_row->num_rows === 0) {
	error_response(401, "Invalid token $tok");
} else if ($user_row->num_rows === 1) {
	$uid = $user_row->fetch_assoc()["phone_number"];
	$get_groups_query = $conn->prepare
		("SELECT gid, title FROM group_user INNER JOIN u_group ON gid=id WHERE uid=? AND confirmed=1");
	$get_groups_query->bind_param("s", $uid);
	$get_groups_query->execute();

	$get_groups_result = $get_groups_query->get_result();

	if ($get_groups_result === false) {
		error_response(500, "Database error while getting user's groups");
	}

	echo json_encode(array("groups"=>array_map(
		'row_to_group',
		$get_groups_result->fetch_all(MYSQLI_ASSOC)
	)));
} else {
	error_response(400, "Multiple users with the same token (!!!)");
}
?>
