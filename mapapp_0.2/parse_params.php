<?php
// returns an array of URL arguments that are URL decoded.
function parse_params($file_name) {
	return explode('/', urldecode(substr(getenv('REQUEST_URI'), 
		strpos(getenv('REQUEST_URI'), $file_name.'/'))));
}
?>
