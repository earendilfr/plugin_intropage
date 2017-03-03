<?php

function get_login() {
	global $config;
	
	$result = array(
		'name' => "Last 10 logins",
		'alarm' => 'green',
		'data' => 'Without Failed logins',
		'detail' => '',
	);
	
	$sql_user_log = db_fetch_assoc("SELECT user_log.username, user_auth.full_name, user_log.time, user_log.result, user_log.ip FROM user_auth INNER JOIN user_log ON user_auth.username = user_log.username ORDER  BY user_log.time desc LIMIT 10");
	foreach($sql_user_log as $row) {
		$result['detail'] .= sprintf("%s  - user: %s |IP: %s | result: %s<br/>",$row['time'],$row['username'],$row['ip'],($row['result'] == 0) ? "Failed" : "Success");
		if ($row['result'] == 0) {
			$result['alarm'] = "red";
			$result['data'] = "Failed logins occured";
		}
	}
	
	$loggin_access = (db_fetch_assoc("select realm_id from user_auth_realm where user_id='" . $_SESSION["sess_user_id"] . "' and user_auth_realm.realm_id=19"))?true:false;
	if ($result['detail'] && $loggin_access)	    
		$result['detail'] .= "<a href=\"" . htmlspecialchars($config['url_path']) . "utilities.php?action=view_user_log\">Full log</a><br/>\n";
	
	return $result;
}

?>