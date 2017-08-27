<?php

function get_tholds() {
	global $config, $sql_where;
	
	$result = array(
		'name' => 'Thresholds',
		'alarm' => 'green',
	);
	
	if (!db_fetch_cell("SELECT directory FROM plugin_config where directory='thold' and status=1")) {
		$result['alarm'] = "grey";
		$result['data'] = "Thold plugin not installed/running\n";
	} elseif (!db_fetch_cell("SELECT DISTINCT user_id FROM user_auth_realm WHERE user_id = ".$_SESSION["sess_user_id"]." AND realm_id IN (SELECT id + 100 FROM plugin_realms WHERE file LIKE '%thold%')")) {
		$result['data'] = "You don't have permission\n";
	} else {
		$sql_join = "LEFT JOIN user_auth_perms ON ((thold_data.graph_template_id=user_auth_perms.item_id AND user_auth_perms.type=1 AND user_auth_perms.user_id= " . $_SESSION["sess_user_id"] . ") OR
			(thold_data.host_id=user_auth_perms.item_id AND user_auth_perms.type=3 AND user_auth_perms.user_id= " . $_SESSION["sess_user_id"] . ") OR
    		(thold_data.graph_template_id=user_auth_perms.item_id AND user_auth_perms.type=4 AND user_auth_perms.user_id= " . $_SESSION["sess_user_id"] . "))";
		
		$t_all = db_fetch_cell("SELECT COUNT(*) FROM thold_data $sql_join WHERE $sql_where");
		$t_brea = db_fetch_cell("SELECT COUNT(*) FROM thold_data $sql_join WHERE (thold_data.thold_alert!=0 OR thold_data.bl_alert>0) AND $sql_where");
		$t_trig = db_fetch_cell("SELECT COUNT(*) FROM thold_data $sql_join WHERE (thold_data.thold_alert!=0 OR thold_data.bl_fail_count >= thold_data.bl_fail_trigger) AND $sql_where");
		$t_disa = db_fetch_cell("SELECT COUNT(*) FROM thold_data $sql_join WHERE thold_data.thold_enabled='off' AND $sql_where");
		
		if ($t_brea > 0 || $t_trig > 0) { $result['alarm'] = "red"; }
		elseif ($t_disa > 0) { $result['alarm'] = "yellow"; }
		
		if (db_fetch_cell("SELECT COUNT(*) FROM user_auth_realm WHERE user_id = ".$_SESSION["sess_user_id"]." AND realm_id IN (SELECT id + 100 FROM plugin_realms WHERE file LIKE '%thold_graph.php%')")) {
			$result['data']  = "<a href=\"" . htmlspecialchars($config['url_path']) . "plugins/thold/thold_graph.php?action=thold&amp;status=-1&amp;data_template_id=-1&amp;host_id=-1&amp;site_id=-1&amp;rows=-1&amp;filter=\">All: $t_all</a> | \n";
			$result['data'] .= "<a href=\"" . htmlspecialchars($config['url_path']) . "plugins/thold/thold_graph.php?action=thold&amp;status=1&amp;data_template_id=-1&amp;host_id=-1&amp;site_id=-1&amp;rows=-1&amp;filter=\">Breached: $t_brea</a> | \n";
			$result['data'] .= "<a href=\"" . htmlspecialchars($config['url_path']) . "plugins/thold/thold_graph.php?action=thold&amp;status=3&amp;data_template_id=-1&amp;host_id=-1&amp;site_id=-1&amp;rows=-1&amp;filter=\">Trigged: $t_trig</a> | \n";
			$result['data'] .= "<a href=\"" . htmlspecialchars($config['url_path']) . "plugins/thold/thold_graph.php?action=thold&amp;status=0&amp;data_template_id=-1&amp;host_id=-1&amp;site_id=-1&amp;rows=-1&amp;filter=\">Disabled: $t_disa</a> | \n";
		} else {
			$result['data'] = "All: $t_all | \n";
			$result['data'] .= "Breached: $t_brea | \n";
			$result['data'] .= "Trigged: $t_trig | \n";
			$result['data'] .= "Disabled: $t_disa | \n";
		}
		if (read_config_option('intropage_display_pie_threshold') == "on")	{
			$result['pie'] = array('title' => 'Thresholds: ', 'label' => array("OK","Breached","Trigerred","Disabled"), 'data' => array($t_all-$t_brea-$t_trig-$t_disa,$t_brea,$t_trig,$t_disa));
		}
	}
	
	return $result;
}

?>
