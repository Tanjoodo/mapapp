<?php
function get_user_from_tok($conn, $tok) {
	$get_user_from_token_query = $conn->prepare("SELECT phone_number FROM user WHERE access_token=?");
	$get_user_from_token_query->bind_param('s', $tok);
	$get_user_from_token_query->execute();
	$get_user_from_token_result = $get_user_from_token_query->get_result();
	return $get_user_from_token_result;
}
?>
