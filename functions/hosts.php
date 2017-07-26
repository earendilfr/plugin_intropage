<?php

function get_hosts() {
	global $config, $allowed_hosts, $console_access, $sql_where;
	
	$result = array(
		'name' => 'Hosts',
		'alarm' => "green",
	);
	
	$h_all  = db_fetch_cell ("SELECT count(id) FROM host WHERE id IN ($allowed_hosts)");
	$h_up   = db_fetch_cell ("SELECT count(id) FROM host WHERE id IN ($allowed_hosts) AND status='3' AND disabled=''");
	$h_down = db_fetch_cell ("SELECT count(id) FROM host WHERE id IN ($allowed_hosts) AND status='1' AND disabled=''");
	$h_reco = db_fetch_cell ("SELECT count(id) FROM host WHERE id IN ($allowed_hosts) AND status='2' AND disabled=''");
	$h_disa = db_fetch_cell ("SELECT count(id) FROM host WHERE id IN ($allowed_hosts) AND disabled='on'");
	
	if ($h_down > 0) { $result['alarm'] = "red"; }
	elseif ($h_disa > 0) { $result['alarm'] = "yellow"; }
	
	if ($console_access) {
		$result['data'] = "<a href=\"" . htmlspecialchars($config['url_path']) . "host.php?host_status=-1\">All: $h_all</a> | \n";
	    $result['data'] .= "<a href=\"" . htmlspecialchars($config['url_path']) . "host.php?host_status=3\">Up: $h_up</a> | \n";
	    $result['data'] .= "<a href=\"" . htmlspecialchars($config['url_path']) . "host.php?host_status=1\">Down: $h_down</a> | \n";
	    $result['data'] .= "<a href=\"" . htmlspecialchars($config['url_path']) . "host.php?host_status=-2\">Disabled: $h_disa</a> | \n";
	    $result['data'] .= "<a href=\"" . htmlspecialchars($config['url_path']) . "host.php?host_status=2\">Recovering: $h_reco</a>\n";
	} else {
		$result['data'] = "All: $h_all | \n";
	    $result['data'] .= "Up: $h_up | \n";
	    $result['data'] .= "Down: $h_down | \n";
	    $result['data'] .= "Disabled: $h_disa | \n";
	    $result['data'] .= "Recovering: $h_reco\n";
	}
	if (read_config_option('intropage_display_pie_host') == "on") {
		$result['pie'] = array('title' => 'Hosts: ', 'label' => array("Up","Down","Recovering","Disabled"), 'data' => array($h_up,$h_down,$h_reco,$h_disa));
	}
	
	return $result;
}

function get_hosts_same_description() {
	global $config, $allowed_hosts;
	
	$result = array(
		'name' => 'Devices with the same description',
		'alarm' => 'green',
		'detail' => '',
	);
	
	$sql_duplicate = db_fetch_assoc("SELECT id,description, count(*) AS count FROM host WHERE id IN ($allowed_hosts) GROUP BY description HAVING count(*)>1");
	$result['data'] = count($sql_duplicate);
	if (count($sql_duplicate)) {
		$result['alarm'] = "red";
		foreach($sql_duplicate as $row) {
			$sql_hosts = db_fetch_assoc_prepared("SELECT id,description FROM host WHERE id IN ($allowed_hosts) AND description = ?",array($host['description']));
			foreach ($sql_hosts as $item) {
				$result['detail'] .= sprintf("<a href=\"%shost.php?action=edit&amp;id=%d\">%s</a>(ID %s)<br/>\n",htmlspecialchars($config['url_path']),$item['id'],$item['description'],$item['id']);
			}
		}
	}
	
	return $result;
}

function get_hosts_tree() {
	global $config, $allowed_hosts;
	
	$result = array(
		'name' => 'Devices in more then one tree',
		'alarm' => 'green',
		'detail' => '',
	);
	
	$sql_multiple = db_fetch_assoc ("SELECT host.id, host.description, count(*) AS count FROM host INNER JOIN graph_tree_items ON (host.id = graph_tree_items.host_id) GROUP BY description HAVING count(*)>1");
	$result['data'] = count($sql_multiple);
	if (count($sql_multiple)) {
		$result['alarm'] = "red";
		foreach($sql_multiple as $row) {
			$sql_hosts = db_fetch_assoc_prepared("SELECT graph_tree.id as gtid, host.description, graph_tree_items.title, graph_tree_items.parent, graph_tree.name FROM host INNER JOIN graph_tree_items ON (host.id = graph_tree_items.host_id) INNER JOIN graph_tree ON (graph_tree_items.graph_tree_id = graph_tree.id) WHERE host.id = ?",array($row['id']));
			foreach($sql_hosts as $host) {
				$parent = $host['parent'];
				$tree = $host['name'] . " / ";
				while ($parent != 0) {
					$sql_parent = db_fetch_row("SELECT parent, title FROM graph_tree_items WHERE id = $parent");
					$parent = $sql_parent['parent'];
					$tree .= $sql_parent['title'] . " / ";
				}
				$result['detail'] .= sprintf("<a href=\"%stree.php?action=edit&id=%d\">Node: %s | Tree: %s</a><br/>\n",htmlspecialchars($config['url_path']),$host['gtid'],$host['description'],$tree);
			}
		}
	}
	
	return $result;
}

function get_hosts_no_graph() {
	global $config, $allowed_hosts;
	
	$result = array(
		'name' => 'Hosts without graphs',
		'alarm' => 'green',
		'detail' => '',
	);
	
	$sql_no_graphs = db_fetch_assoc("SELECT id , description FROM host WHERE id IN ($allowed_hosts) AND id NOT IN (SELECT DISTINCT host_id FROM graph_local) AND snmp_version != 0");
	$result['data'] = count($sql_no_graphs);
	if ($sql_no_graphs) {
		$result['alarm'] = "red";
		foreach($sql_no_graphs as $row) {
			$result['detail'] .= sprintf("<a href=\"%shost.php?action=edit&amp;id=%2$d\">%s (ID: %2$d)</a><br/>\n",htmlspecialchars($config['url_path']),$row['id'],$row['description']);
		}
	}
	return $result;
}

function get_hosts_no_tree() {
	global $config, $allowed_hosts;
	
	$result = array(
		'name' => 'Hosts without tree',
		'alarm' => 'green',
		'detail' => '',
	);
	
	$sql_no_graphs = db_fetch_assoc("SELECT id , description FROM host WHERE id IN ($allowed_hosts) AND id NOT IN (SELECT DISTINCT host_id FROM graph_tree_items)");
		$result['data'] = count($sql_no_graphs);
	if ($sql_no_graphs) {
		$result['alarm'] = "red";
		foreach($sql_no_graphs as $row) {
			$result['detail'] .= sprintf("<a href=\"%shost.php?action=edit&amp;id=%d\">%s (ID: %d)</a><br/>\n",htmlspecialchars($config['url_path']),$row['id'],$row['description'],$row['id']);
		}
	}
	return $result;
}

function get_datasources() {
	global $config, $input_types;
	
	$result = array(
		'pie' => array(
			'title' => 'Datasources: ',
			'label' => array(),
			'data' => array(),
		),
	);
	$sql_ds = db_fetch_assoc("SELECT data_input.type_id, COUNT(data_input.type_id) AS total FROM data_local INNER JOIN data_template_data ON (data_local.id = data_template_data.local_data_id) LEFT JOIN data_input ON (data_input.id=data_template_data.data_input_id) LEFT JOIN data_template ON (data_local.data_template_id=data_template.id) WHERE local_data_id<>0 group by type_id LIMIT 6");
	if ($sql_ds) {
		foreach ($sql_ds as $item) {
			if (array_key_exists($item['type_id'],$input_types)) {
				array_push($result['pie']['label'],preg_replace('/script server/','SS',$input_types[$item['type_id']]));
				array_push($result['pie']['data'],$item['total']);
			}
		}
	}
	
	return $result;
}

function get_hosttemplates() {
	global $config, $allowed_hosts;
	
	$result = array(
		'pie' => array(
			'title' => 'Host Templates: ',
			'label' => array(),
			'data' => array(),
		),
	);
	
	$sql_ht = db_fetch_assoc("SELECT host_template.id as id, name, count(host.host_template_id) AS total FROM host_template LEFT JOIN host ON (host_template.id = host.host_template_id) AND host.id IN ($allowed_hosts) GROUP by host_template_id ORDER BY total desc LIMIT 6");
	if ($sql_ht) {
		foreach ($sql_ht as $item) {
			array_push($result['pie']['label'],$item['name']);
			array_push($result['pie']['data'],$item['total']);
		}
	}
	
	return $result;
}

?>
