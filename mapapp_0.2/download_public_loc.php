<?php

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
require_once "date.php";
require_once "error.php";
include "debug.php";
header('Content-Type: application/json');

$conn = connect_to_db();
$params = parse_params('download_public_loc.php');

$tok = $params[1];

if (!isset($tok) || $tok=="") {
	error_response(400, "Please insert all parameters");
}

$find_user_by_access_token_query =
	$conn->prepare("SELECT `phone_number`, `lat`, `lng`, `last_request`, `is_public` FROM `user` WHERE `access_token`=?");
$find_user_by_access_token_query->bind_param("s", $tok);
$find_user_by_access_token_query->execute();
$find_user_by_access_token_result = $find_user_by_access_token_query->get_result();

if ($find_user_by_access_token_result === false) {
	error_response(500, "Database error when finding user by token.");
}

$user_found_assoc = $find_user_by_access_token_result->fetch_assoc();
$id = $user_found_assoc['phone_number'];
$lat = floatval($user_found_assoc['lat']);
$lng = floatval($user_found_assoc['lng']);
$last_request = $user_found_assoc['last_request'];
$is_public = $user_found_assoc['is_public'];

/*if (!$is_public) {
	error_response(401, "You need to have your location set to public");
}*/

if ($find_user_by_access_token_result === false) {
	error_response(500, "Database error when querying for user.");
}

$max_dist = 15.0; // maximum distance in km

if (mysqli_num_rows($find_user_by_access_token_result) === 0) {
	error_response(401, "Access token invalid $tok");
} else if (mysqli_num_rows($find_user_by_access_token_result) === 1) {
	$find_nearby_points_query = $conn->prepare("SELECT phone_number, lat, lng, status, 2 * 6371 * asin(if(d>1, 1, if(d<-1, -1, d))) as distance from ".
		"(SELECT *, sqrt(sin(radians((? - lat)/2))*sin(radians((? - lat)/2)) + ".
		"cos(radians(lat)) * cos(radians(?)) * sin(radians((? - lng)/2))*sin(radians((? - lng)/2))) as d FROM user) t1 ".
		"WHERE phone_number!=? AND is_online=true AND is_public=true AND TIMESTAMPDIFF(SECOND, t1.last_update, ?)<0 ".
		"HAVING distance<?");

	$find_nearby_points_query->bind_param("dddddssd", $lat, $lat, $lat, $lng, $lng, $id, $last_request, $max_dist);
	$find_nearby_points_query->execute();
	$find_nearby_points_result = $find_nearby_points_query->get_result();
	if ($find_nearby_points_result === false) {
		error_response(500, "Database error when querying for close users");
	}
	$near_users = array();
	while ($row = mysqli_fetch_assoc($find_nearby_points_result)) {
		$near_users[] = new near_user($row['phone_number'], $row['lat'], $row['lng'], $row['status']);
	}


	$add_near_users_query = "";
	foreach ($near_users as $u) {
		$search_for_sent_to_user_query = $conn->prepare( "SELECT * FROM sent_to WHERE user_from=? AND user_to=?");
		$search_for_sent_to_user_query->bind_param("ss", $u->id, $id);
		$search_for_sent_to_user_query->execute();
		$search_for_sent_to_user_result = $search_for_sent_to_user_query->get_result();
		if ($search_for_sent_to_user_result === false) {
			error_response(500, "Database error when querying for sent users");
		}
		if (mysqli_num_rows($search_for_sent_to_user_result) === 0) {
			$add_near_users_query .= "INSERT INTO sent_to (user_from, user_to) values ('".$conn->escape_string($u->id)."', '".$conn->escape_string($id)."'); ";
		}
	}

	if ($add_near_users_query != "") {
		$add_near_users_result = $conn->multi_query($add_near_users_query);
		if ($add_near_users_result === false) {
			error_respose(500, "Database error when inserting sent users.");
		}
	}

	$find_users_no_longer_relevant_query = $conn->prepare("SELECT phone_number FROM user inner join sent_to on user_from=phone_number where user_to=? and ".
		"(is_online=false or is_public=false);");
	$find_users_no_longer_relevant_query->bind_param("s", $id);
	$find_users_no_longer_relevant_query->execute();
	$find_users_no_longer_relevant_result = $find_users_no_longer_relevant_query->get_result();
	if ($find_users_no_longer_relevant_result === false){
		error_response(500, "Database error when querying for users with null tokens.");
	}

	$delete_users = array();
	while($row = $find_users_no_longer_relevant_result->fetch_assoc()) {
		$delete_users[] = $row['phone_number'];
	}

	$remove_sent_users_with_null_tokens_query= "";
	foreach ($delete_users as $u) {
		$remove_sent_users_with_null_tokens_query .= "DELETE FROM sent_to WHERE user_from='".$conn->escape_string($u)."' AND user_to='".$conn->escape_string($id)."';";
	}

	if ($remove_sent_users_with_null_tokens_query !== "") {
		if ($conn->multi_query($remove_sent_users_with_null_tokens_query) === false) {
			error_response(500, "Database error when deleting logged out sent users.");
		}
	}
}

$now_timestamp = get_now_timestamp();
$update_last_request_query = $conn->prepare("UPDATE user SET last_request=? WHERE phone_number=?");
$update_last_request_query->bind_param("ss", $now_timestamp, $id);
$update_last_request_query->execute();
$update_last_request_result = $update_last_request_query->get_result();

echo json_encode(array("NearUsers"=>$near_users, "DeleteUsers"=>$delete_users));
$conn->close();
?>
