<?php

function get_database() {
	global $config;
	
	$result = array(
		'name' => 'Database check',
		'alarm' => 'green',
	);
	
	$damaged = 0;
	$memtables = 0;
	$db_check_level = read_config_option('intropage_db_check_level');
	
	$tables = db_fetch_assoc ("SHOW TABLES");
	foreach($tables as $key=>$val) {
		$row = db_fetch_row("check table ".current($val)." $db_check_level");
		if (preg_match('/^note$/i',$row["Msg_type"]) && preg_match('/doesn\'t support/i',$row["Msg_text"])) { $memtables++; }
		elseif (!preg_match('/OK/i',$row["Msg_text"])) { $damaged++; $result['detail'] .= "Table " . $row["Table"] . " status " . $row["Msg_text"] . "<br/>\n"; }
	}
	
	if ($damaged) { $return['alarm'] = "red"; }
	$return['data'] = "Damaged tables: $damaged, Memory tables: $memtables";
	
	return $return;
}

?>