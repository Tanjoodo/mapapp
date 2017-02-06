<?php
require_once "connect_db.php";
require_once "error.php";
require_once "user.php";
require_once "parse_params.php";
include "debug.php";

class invitation {
	public $gid;
	public $inviter;
	public $date;

	public function __construct($gid, $inviter, $date) {
		$this->gid = $gid;
		$this->inviter = $inviter;
		$this->date = $date;
	}
}

$conn = connect_to_db();
$params = parse_params('get_invites.php');

$tok = $params[1];
if (!isset($tok) || $tok === "") {
	error_response(400, "Missing token parameter");
}

$user_row = get_user_from_tok($conn, $tok);
$uid = $user_row->fetch_assoc()["phone_number"];

if ($user_row->num_rows === 0) {
	error_response(401, "Invalid token $tok");
}

function row_to_invitation ($row) {
	return new invitation(
		$row["gid"],
		$row["inviter"],
		$row["date_invited"]
	);
}

if ($user_row->num_rows === 1) {
	$get_invites_query = $conn->prepare("SELECT * FROM group_user WHERE uid=? AND confirmed=0");
	$get_invites_query->bind_param('s', $uid);
	$get_invites_query->execute();

	$get_invites_result = $get_invites_query->get_result();
	$output = array("invites"=>array_map(
		'row_to_invitation',
		$get_invites_result->fetch_all(MYSQLI_ASSOC)
	));
	echo json_encode($output);
} else {
	error_response(400, "More than one user with the same token (!!!)");
}
?>

