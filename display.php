<?php

function display_informations() {
	global $config, $colors, $poller_options, $console_access, $allowed_hosts, $sql_allowed_hosts, $sql_where;

	if (!api_user_realm_auth('intropage.php'))	{
		print "Intropage - permission denied";
		print "<br/><br/>";
		return false;
	}

	// Retrieve global configuration options
	$display_layout = read_config_option("intropage_display_layout");
	$debug = read_config_option("intropage_debug");
	$current_user = db_fetch_row_prepared('SELECT * FROM user_auth WHERE id= ?',array($_SESSION['sess_user_id']));
	$sql_where = get_graph_permissions_sql($current_user['policy_graphs'], $current_user['policy_hosts'], $current_user['policy_graph_templates']);
	$allowed_hosts = array();
	$sql_allowed_hosts = '';
    $sql = "SELECT distinct host.id as id FROM host
		LEFT JOIN graph_local ON (host.id = graph_local.host_id)
		LEFT JOIN graph_templates_graph ON (graph_templates_graph.local_graph_id = graph_local.id)
		LEFT JOIN user_auth_perms ON ((graph_templates_graph.local_graph_id=user_auth_perms.item_id AND user_auth_perms.type=1 AND user_auth_perms.user_id= ?) OR
			(host.id=user_auth_perms.item_id AND user_auth_perms.type=3 AND user_auth_perms.user_id= ?) OR
			(graph_templates_graph.id=user_auth_perms.item_id AND user_auth_perms.type=4 AND user_auth_perms.user_id= ?))
		WHERE graph_templates_graph.local_graph_id=graph_local.id and  $sql_where";
    $sql_result = db_fetch_assoc_prepared($sql,array($_SESSION['sess_user_id'],$_SESSION['sess_user_id'],$_SESSION['sess_user_id']));
    if ($sql_result) {
        foreach ($sql_result as $item) { array_push($allowed_hosts,$item['id']); }
		$sql_allowed_hosts = implode(',',array_fill(0,sizeof($allowed_hosts),'?'));
    }
	
	// Retrieve access
	$console_access = (db_fetch_assoc_prepared("select realm_id from user_auth_realm where user_id= ? and user_auth_realm.realm_id=8",array($_SESSION["sess_user_id"])))?true:false;
	
	// Start
	$values = array();
	
	// Check Hosts
	include_once($config['base_path'] . '/plugins/intropage/functions/hosts.php');
	$values['hosts'] = get_hosts();
	
	// Check Thresholds
	include_once($config['base_path'] . '/plugins/intropage/functions/tholds.php');
	$values['thold'] = get_tholds();
	
	// Check Database
	if (read_config_option('intropage_db_check') == "on") {
		include_once($config['base_path'] . '/plugins/intropage/functions/database.php');
		$values['dbcheck'] = get_database();
	}
	
	// Check MACTRACK
	include_once($config['base_path'] . '/plugins/intropage/functions/mactrack.php');
	$values['mactrack'] = get_mactrack();
	
	// Check NTP
	if (read_config_option('intropage_ntp_enable')) {
		include_once($config['base_path'] . '/plugins/intropage/functions/ntp.php');
		$values['ntp'] = get_time();
	}
	
	// Check log file
	if ($console_access && read_config_option('intropage_log_analyze_enable')) {
		include_once($config['base_path'] . '/plugins/intropage/functions/log.php');
		$values = array_merge($values,get_log());
	} else {
		include_once($config['base_path'] . '/plugins/intropage/functions/log.php');
		$values['poller'] = get_poller_stats();
	}
	
	// Check logins
	if ($console_access && read_config_option('intropage_login_analyze_enable')) {
		include_once($config['base_path'] . '/plugins/intropage/functions/login.php');
		$values['login'] = get_login();
	}
	
	// Check Host - same description
	if ($console_access && read_config_option('intropage_device_same_desc_check')) {
		include_once($config['base_path'] . '/plugins/intropage/functions/hosts.php');
		$values['description'] = get_hosts_same_description();
	}
	
	// Check Host - in more then one tree
	if 	($console_access && read_config_option('intropage_device_more_tree_check')) {
		include_once($config['base_path'] . '/plugins/intropage/functions/hosts.php');
		$values['more_tree'] = get_hosts_tree();
	}
	
	// Check Host - without graphs
	if 	($console_access && read_config_option('intropage_device_without_graph_check')) {
		include_once($config['base_path'] . '/plugins/intropage/functions/hosts.php');
		$values['without_graph'] = get_hosts_no_graph();
	}
	
	// Check Host - without tree
	if 	($console_access && read_config_option('intropage_device_without_tree_check')) {
		include_once($config['base_path'] . '/plugins/intropage/functions/hosts.php');
		$values['without_tree'] = get_hosts_no_tree();
	}
	
	// Check Host - without monitor
	if ($console_access && read_config_option('intropage_device_not_monitored_check')) {
		include_once($config['base_path'] . '/plugins/intropage/functions/monitor.php');
		$values['monitor'] = get_hosts_monitor();
	}
	
	// Get Datasources
	if (read_config_option("intropage_display_pie_datasource")) {
		include_once($config['base_path'] . '/plugins/intropage/functions/hosts.php');
		$values['datasources'] = get_datasources();
	}
	
	// Get Hosts templates
	if (read_config_option("intropage_display_pie_template")) {
		include_once($config['base_path'] . '/plugins/intropage/functions/hosts.php');
		$values['templates'] = get_hosttemplates();
	}
	
	print "<table><tr style=\"vertical-align: top; \"><td>\n";
	// Display
	$display_level = read_config_option('intropage_display_level');
	html_start_box("<strong>Alerts</strong>", "650", '', "3", 'left', '');
	html_header(array(__(' '),__('Name'),__('Value')));
	$count = 0;
	foreach ($values as $id => $val) {
		if (!isset($val['alarm'])) continue;
		if ($display_level = 2 || ($display_level = 1 && ($val['alarm'] == "yellow" || $val['alarm'] == "red")) || ($display_level = 0 && $val['alarm'] == "red")) {
			form_alternate_row($count,true);
			printf("<td><img src=\"%splugins/intropage/images/alert_%s.png\" /></td>\n",htmlspecialchars($config['url_path']),$val['alarm']);
			printf("<td style=\"vertical-align: top;\"><strong>%s</strong></td>\n",$val['name']);
			printf("<td>%s",(isset($val['data']) && !is_null($val['data'])) ? $val['data'] : '');
			if (isset($val['detail']) && !(is_null($val['detail']) || $val['detail'] == '')) {
				printf("<span style='float: right'><a href='#' onclick=\"hide_display('block_%s');\">View/hide details</a></span></td></tr>\n",$id);
				form_alternate_row($count,true);
				print("<td colspan='3'>\n");
				printf("<div id=\"block_%s\" style=\"display: none\">",$id);
				print($val['detail']);
				print("</div>");
			}
			print "</td>\n";
			form_end_row();
			$count++;
		}
	}
	html_end_box();
	print "</td>";
	
	// Display TopX
	if (read_config_option("intropage_display_topx")) {
		if ($display_layout == "horizontal" || $display_layout == "bestfit")
			print "<td>\n";
		else
			print "</tr><tr><td><table><tr><td>\n";
		
		$count = 0;
		// --- Top 5 host with bad ping
		html_start_box("<strong>Top 5 hosts with the worst ping response</strong>", "315", '', "3", 'left', '');
		html_header(array(__('Host'),__('avg'),__('cur')));
		$sql = "SELECT description, id , avg_time, cur_time FROM host WHERE host.id IN ($sql_allowed_hosts) ORDER BY avg_time DESC LIMIT 5";
		$sql_worst_host = db_fetch_assoc_prepared($sql,$allowed_hosts);
		foreach($sql_worst_host as $host) {
			form_alternate_row($count,true);
			if ($console_access) { print "<td style=\"padding-right: 2em;\"><a href=\"".htmlspecialchars($config['url_path'])."host.php?action=edit&amp;id=".$host['id']."\">".$host['description']."</a></td>\n"; }
			else { print "<td style=\"padding-right: 2em;\">".$host['description']."</td>\n"; }
			
			print "<td style=\"padding-right: 2em; text-align: right;\">" . round($host['avg_time'],2) . "</td>\n";
			print "<td style=\"padding-right: 2em; text-align: right;\">" . round($host['cur_time'],2) . "</td>\n";
			form_end_row();
		}
		html_end_box(false);
		
		if ($display_layout == "horizontal" || $display_layout == "bestfit")	
			print "<br style=\"clear: both;\"/><br/>\n";
	    else
			print"</td><td width=\"10\">&nbsp;</td><td>\n";	  
		
		$count = 0;
		// --- Top 5 host with lowest availability
		html_start_box("<strong>Top 5 hosts with the lowest availability</strong>", "315", '', "3", 'left', '');
		html_header(array(__('Host'),__('Availability')));
		
		$sql = "SELECT description, id, availability FROM host WHERE host.id IN ($sql_allowed_hosts) ORDER BY availability DESC LIMIT 5";
		$sql_lowest_host = db_fetch_assoc_prepared($sql,$allowed_hosts);
		foreach($sql_lowest_host as $host) {
			form_alternate_row($count,true);
			if ($console_access) { printf("<td style=\"padding-right: 2em;\"><a href=\"%shost.php?action=edit&amp;id=%d\">%s</a></td>\n",htmlspecialchars($config['url_path']),$host['id'],$host['description']); }
			else { printf("<td style=\"padding-right: 2em;\"></td>\n",htmlspecialchars($config['url_path']),$host['id'],$host['description']); }
			
			print "<td style=\"padding-right: 2em; text-align: right;\">" . round(trim($host['availability']),2) . "%</td>\n";
			form_end_row();
		}
		html_end_box(false);
		
		if ($display_layout == "horizontal" || $display_layout == "bestfit")	
			print "";
	    else	
			print "</td></tr></table>\n"; // end of inner table
	}
	
	// Display PIE
	$gd = function_exists ("imagecreatetruecolor") ? true:false;
	if ($gd) {
		if ($display_layout == "horizontal") { print "\n"; }
	    else { print"</tr><tr>\n"; }
		$count = 0;
		foreach($values as $id => $val) {
			if (!isset($val['pie'])) continue;
			elseif (!array_sum($val['pie']['data'])) continue;
			if ($count % 2 == 0) print "</tr><tr>\n";
			print "<td><br/><div style=\"width: 400px; height: 400px;\">\n";
			print "<div><canvas id=\"pie_$id\"></canvas>\n";
			print "<script type='text/javascript'>\n";
			$pie_labels = implode('","',$val['pie']['label']);
			$pie_values = implode(',',$val['pie']['data']);
			$pie_title = $val['pie']['title'];
			print <<<EOF
var $id = document.getElementById("pie_$id").getContext("2d");
new Chart($id, {
	type: 'pie',
	data: {
		labels: ["$pie_labels"],
		datasets: [{
			backgroundColor: [ "#2ecc71", "#e74c3c", "#3498db", "#9b59b6", "#f1c40f", "#33ffe6", ],
			data: [$pie_values]
		}]
	},
	options: {
		title: { display: true, text: "$pie_title" },
		legend: { 
			display: true, 
			position: 'right', 
			labels: { 
				usePointStyle: true,
				
			}
		},
		tooltipTemplate: "<%= value %>%"
	}	
});

EOF;
			print "</script></div></td>\n";
			$count++;
		}
	} else {
	}
	
	print "</td></tr></table>\n";
	
	// ----------------- OS informations
	if ($console_access && read_config_option("intropage_display_os_info")) {
		print "<br style=\"clear: both;\" />";
		
		if ($poller_options[read_config_option("poller_type")] == 'spine' && file_exists(read_config_option("path_spine")) && (function_exists('is_executable')) && (is_executable(read_config_option("path_spine")))) {
			$spine_version = "SPINE";
			exec(read_config_option("path_spine") . " --version", $out_array);
			if (sizeof($out_array)) {
				$spine_version = $out_array[0];
			}
			print "<strong>Poller type: </strong><a href=\"" . htmlspecialchars($config['url_path']) .  "settings.php?tab=poller\">$spine_version</a>\n";
		} else {
			print "<strong>Poller type: </strong><a href=\"" . htmlspecialchars($config['url_path']) .  "settings.php?tab=poller\">".$poller_options[read_config_option("poller_type")]."</a>\n";
		}
		
		print "<br/><strong>Running on:</strong> ";
		if (function_exists("php_uname")) { print php_uname(); }
		else { print PHP_OS; }
		print "<br/>";
	}
	
	print <<<EOF
<script type="text/javascript">
function hide_display (id)      {
    var temp = document.getElementById(id);

    if (temp.style.display=='block')
        temp.style.display='none';
    else
        temp.style.display='block';
    return false;
}
</script>

EOF;
	
	return true;
}

?>
