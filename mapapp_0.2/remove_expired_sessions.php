<?php
require_once "date.php";
/*error_reporting(E_ALL);
ini_set("display_startup_errors", 1);
ini_set("display_errors", 1);*/

require_once "connect_db.php";

$conn = connect_to_db();
$now_timestamp = get_now_timestamp();
$minutes_until_expired = 30;

$remove_expired_users_query = "UPDATE user SET access_token=NULL WHERE TIMESTAMPDIFF(MINUTE, last_request, '$now_timestamp') >= $minutes_until_expired ".
	"AND access_token IS NOT NULL";

if ($conn->query($remove_expired_users_query) === false) {
	echo "Error accessing database ";
}
?>
