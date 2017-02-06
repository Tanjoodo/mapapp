<?php
define("ERROR_500", "Internal Server Error");
define("ERROR_400", "Bad Request");
define("ERROR_401", "Unauthorized");
function error_response($error_code, $error_message, $error_desc = 0) {
	if ($error_desc === 0) {
		switch($error_code) {
		case 500:
			$error_desc = ERROR_500;
			break;
		case 401:
			$error_desc = ERROR_401;
			break;
		case 400:
			$error_desc = ERROR_400;
			break;
		default:
			$error_desc = "";
		}
	}

	header($_SERVER["SERVER_PROTOCOL"]." ".$error_code." ".$error_desc, true);
	$output = array("error"=>$error_message);
	echo json_encode($output);
	die();
}
?>
