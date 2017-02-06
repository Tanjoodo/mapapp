<?php
/*error_reporting(E_ALL);
ini_set("display_startup_errors", 1);
ini_set("display_errors", 1);*/

class near_user {
	public $id;
	public $lat;
	public $lng;
	public $status;

	public function __construct($id, $lat, $lng, $status) {
		$this->id = $id;
		$this->lat = $lat;
		$this->lng = $lng;
		$this->status = $status;
	}
}

require_once "connect_db.php";
require_once "parse_params.php";
header('Content-Type: application/json');

$conn = connect_to_db();
$params = parse_params('download_loc.php');

$tok = $params[1];

$find_user_by_access_token_query =
	"SELECT `id`, `lat`, `lng`, `last_request` FROM `user` WHERE `access_token`='$tok'";
$find_user_by_access_token_result = $conn->query($find_user_by_access_token_query);

$user_found_assoc = $find_user_by_access_token_result->fetch_assoc();
$id = $user_found_assoc['id'];
$lat = floatval($user_found_assoc['lat']);
$lng = floatval($user_found_assoc['lng']);
$last_request = $user_found_assoc['last_request'];


if ($find_user_by_access_token_result === false) {
	header($_SERVER["SERVER_PROTOCOL"]." 401 Unauthorized", true, 401);
	$output = array("error"=>"Database error when querying for user.");
	echo json_encode($output);
	die();
}

$max_dist = 35; // maximum distance in km

if (mysqli_num_rows($find_user_by_access_token_result) === 0) {
	header($_SERVER["SERVER_PROTOCOL"]." 401 Unauthorized", true, 401);
	$output = array("error"=>"Access token invalid $tok");
	echo json_encode($output);
} else if (mysqli_num_rows($find_user_by_access_token_result) === 1) {
	$find_nearby_points_query = "SELECT id, lat, lng, status, 2 * 6371 * asin(if(d>1, 1, if(d<-1, -1, d))) as distance from ".
		"(SELECT *, sqrt(sin(radians(($lat - lat)/2))*sin(radians(($lat - lat)/2)) + ".
		"cos(radians(lat)) * cos(radians($lat)) * sin(radians(($lng - lng)/2))*sin(radians(($lng - lng)/2))) as d FROM user) t1 ".
		"WHERE id!='$id' AND access_token IS NOT NULL AND TIMESTAMPDIFF(SECOND, t1.last_update, '$last_request')<0 ".
		"HAVING distance<$max_dist";

	$find_nearby_points_result = $conn->query($find_nearby_points_query);
	if ($find_nearby_points_result === false) {
		header($_SERVER["SERVER_PROTOCOL"]." 500 Internal Server Error", true, 500);
		$output = array("error"=>"Database error when querying for close users ");
		echo json_encode($output);
		die();
	}
	$near_users = array();
	while ($row = mysqli_fetch_assoc($find_nearby_points_result)) {
		$near_users[] = new near_user($row['id'], $row['lat'], $row['lng'], $row['status']);
	}


	$add_near_users_query = "";
	foreach ($near_users as $u) {
		$search_for_sent_to_user_query = "SELECT * FROM sent_to WHERE user_from='".$u->id."' AND user_to='$id'";
		$search_for_sent_to_user_result = $conn->query($search_for_sent_to_user_query);
		if ($search_for_sent_to_user_result === false) {
			header($_SERVER["SERVER_PROTOCOL"]." 500 Internal Server Error", true, 500);
			$output = array("error"=>"Database error when querying for sent users ".$conn->error);
			echo json_encode($output);
			die();
		}
		if (mysqli_num_rows($search_for_sent_to_user_result) === 0) {
			$add_near_users_query .= "INSERT INTO sent_to (user_from, user_to) values ('".$u->id."', '$id'); ";
		}
	}

	if ($add_near_users_query != "") {
		$add_near_users_result = $conn->multi_query($add_near_users_query);
		if ($add_near_users_result === false) {
			header($_SERVER["SERVER_PROTOCOL"]." 500 Internal Server Error", true, 500);
			$output = array("error"=>"Database error when inserting sent users ".$conn->error);
			echo json_encode($output);
			die();
		}
	}

	$find_sent_users_with_null_tokens_query = "SELECT id FROM user inner join sent_to on user_from=id where user_to='$id' and access_token is null;";
	$find_sent_users_with_null_tokens_result = $conn->query($find_sent_users_with_null_tokens_query);

	$delete_users = array();
	while($row = $find_sent_users_with_null_tokens_result->fetch_assoc()) {
		$delete_users[] = $row['id'];
	}

	$remove_sent_users_with_null_tokens_query= "";
	foreach ($delete_users as $u) {
		$remove_sent_users_with_null_tokens_query .= "DELETE FROM sent_to WHERE user_from='".$u."' AND user_to='$id';";
		if ($conn->multi_query($remove_sent_users_with_null_tokens_query) === false) {
			header($_SERVER["SERVER_PROTOCOL"]." 500 Internal Server Error", true, 500);
			$output = array("error"=>"Database error when deleting logged out sent users ".$conn->error);
			echo json_encode($output);
			die();
		}
	}
}

date_default_timezone_set("Asia/Amman");
$now_timestamp = date("Y-m-d H-i-s");
$update_last_request_query = "UPDATE user SET last_request='$now_timestamp' WHERE id='$id'";
if ($conn->query($update_last_request_query) === false) {
	header($_SERVER["SERVER_PROTOCOL"]." 500 Internal Server Error", true, 500);
	$output = array("error"=>"Database error when updating last request time".$conn->error);
	echo json_encode($output);
	die();
}

echo json_encode(array("NearUsers"=>$near_users, "DeleteUsers"=>$delete_users));
$conn->close();
?>
