<?php

function tail_log($log_file, $nbr_lines = 1000, $adaptive = true) {
	
	if (!(file_exists($log_file) && is_readable($log_file))) { return false; }
	
	$f_handle = @fopen($log_file,"rb");
	if ($f_handle === false) { return false; }
	
	if (!$adaptive) { $buffer = 4096; }
	else { $buffer = ($nbr_lines < 2 ? 64 : ($nbr_lines < 10 ? 512 : 4096)); }
	
	fseek($f_handle, -1, SEEK_END);
	
	if (fread($f_handle, 1) != "\n") $nbr_lines -= 1;
	
	// Start reading
	$output = '';
	$chunk = '';
	// While we would like more
	while (ftell($f_handle) > 0 && $nbr_lines >= 0) {
		// Figure out how far back we should jump
		$seek = min(ftell($f_handle), $buffer);
		// Do the jump (backwards, relative to where we are)
		fseek($f_handle, -$seek, SEEK_CUR);
		// Read a chunk and prepend it to our output
		$output = ($chunk = fread($f_handle, $seek)) . $output;
		// Jump back to where we started reading
		fseek($f_handle, -mb_strlen($chunk, '8bit'), SEEK_CUR);
		// Decrease our line counter
		$nbr_lines -= substr_count($chunk, "\n");
	}
	
	// While we have too many lines (Because of buffer size we might have read too many)
	while ($nbr_lines++ < 0) {
		// Find first newline and remove all text before that
		$output = substr($output, strpos($output, "\n") + 1);
	}
	
	// Close file
	fclose($f_handle);
	
	return explode("\n",$output);
}

function human_filesize($bytes, $decimals = 2) {
	$size = array('B', 'kB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB');
	$factor = floor((strlen($bytes) - 1) / 3);
	return sprintf("%.{$decimals}f", $bytes / pow(1024, $factor)) . @$size[$factor];
}

function get_log_size() {
	global $config, $log;
	
	$result = array(
		'name' => 'Log size',
		'alarm' => 'green',
	);

	$result['data'] = "<a href=\"" . htmlspecialchars($config['url_path']) . "utilities.php?action=view_logfile\">";
	
	if (!$log['size']) {
		$result['alarm'] = "red";
		$result['data'] .= "Log file not accessible</a>";
	} elseif ($log['size'] < 0) {
		$result['alarm'] = "red";
		$result['data'] .= "Log file is larger than 2GB</a>";
	} elseif ($log['size'] < 255999999) {
		$result['data'] .= human_filesize($log['size']) . "</a>";
	} else {
		$result['alarm'] = "yellow";
		$result['data'] .= human_filesize($log['size']) . " (Logfile is quite large)</a>";
	}
	
	return $result;
}

function get_poller_stats() {
	global $config, $log;
	
	$poller_interval = read_config_option("poller_interval");
	$result = array(
		'name' => "Poller stats (interval ".$poller_interval."s)",
		'alarm' => 'green',
	);
	
	$pollers_access = (db_fetch_assoc_prepared("select realm_id from user_auth_realm where user_id= ? and user_auth_realm.realm_id=3",array($_SESSION["sess_user_id"])))?true:false;
	
	// Check the poller duration through the pollers table
	if (db_table_exists("poller",false)) {
		$pollers_time = db_fetch_assoc("SELECT id, hostname, total_time FROM poller WHERE disabled != 'on'");
		if ($pollers_time) {
			$max_time = 0;
			$mean_time = 0;
			$result['detail'] = '';
			foreach($pollers_time as $time) {
				$result['detail'] .= ($pollers_access) ? 
					sprintf('<a href="%spollers.php?action=edit&amp;id=%s">%s (%s)</a><br/>',htmlspecialchars($config['url_path']),$time['id'],$time['hostname'],$time['total_time']):
					sprintf('%s (%s)<br/>',$time['hostname'],$time['total_time']);
				if ($max_time < $time['total_time']) $max_time = $time['total_time'];
				$mean_time += $time['total_time'];
			}
			$mean_time = $mean_time / count($pollers_time);
			if ($poller_interval/$mean_time < 1.2 || $poller_interval/$max_time < 1.2) {
				$result['alarm'] = "red";
				$result['data'] = "average: ".$mean_time."s | max: ".$max_time."s (Polling is almost reaching the limit)";
			} elseif ($poller_interval/$mean_time < 1.5 || $poller_interval/$max_time < 1.5) {
				$result['alarm'] = "yellow";
				$result['data'] = "average: ".$mean_time."s | max: ".$max_time."s (Polling is close to the limit)";
			} else {
				$result['data'] = "average: ".$mean_time."s | max: ".$max_time."s";
			}
		} else {
			$result['alarm'] = "red";
			$result['data'] = ($pollers_access)?"<a href=\"".htmlspecialchars($config['url_path'])."pollers.php\">No poller servers is active</a>":"No poller servers is active";
		}
	} elseif ($log['size'] && isset($log['lines'])) {
		$stats_lines = preg_grep('/STATS/',$log['lines']);
		if ($stats_lines) {
			$result['detail'] = '';
			$max_time = 0;
			$mean_time = 0;
			$count = 0;
			foreach ($stats_lines as $line) {
				$result['detail'] .= "$line<br/>";
				if (preg_match('/SYSTEM STATS: Time:([0-9.]+)/',$line,$matches)) {
					$count++;
					$mean_time += $matches[1];
					if ($max_time < $matches[1]) $max_time = $matches[1];
				}
			}
			$mean_time = $mean_time / $count;
			if ($poller_interval/$mean_time < 1.2 || $poller_interval/$max_time < 1.2) {
				$result['alarm'] = "red";
				$result['data'] = "average: ".$mean_time."s | max: ".$max_time."s (Polling is almost reaching the limit)";
			} elseif ($poller_interval/$mean_time < 1.5 || $poller_interval/$max_time < 1.5) {
				$result['alarm'] = "yellow";
				$result['data'] = "average: ".$mean_time."s | max: ".$max_time."s (Polling is close to the limit)";
			} else {
				$result['data'] = "average: ".$mean_time."s | max: ".$max_time."s";
			}
		} else {
			$result['alarm'] = "red";
			$result['data'] = "No stats found in the last $nbr_lines of the log file";
		}
	} else {
		$result['alarm'] = "red";
		$result['data'] = "No solution found to retrieve the pollers stats";
	}
	
	return $result;
}

function get_log_msg() {
	global $config, $log;
	
	$result = array(
		'name' => "Warning and error (in last ".$log['nbr_lines']." lines)",
		'alarm' => 'green',
	);
	
	if (!$log['size'] || !isset($log['lines'])) {
		$result['alarm'] = "red";
		$result['data'] = "Log file not accessible";
	} else {
		$result['detail'] = '';
		$error = 0;
		foreach($log['lines'] as $line) {
			if (preg_match('/(WARN|ERROR|FATAL)/',$line,$matches)) {
				$result['detail'] .= "$line<br/>";
				if (strcmp($matches[1],"WARN") && $error < 1) {
					$result['alarm'] = "yellow";
					$result['data'] = "There is warning in logs";
					$error = 1;
				} elseif ((strcmp($matches[1],"ERROR") || strcmp($matches[1],"FATAL")) && $error < 2) {
					$error = 2;
					$result['alarm'] = "red";
					$result['data'] = "There is error in logs";
				}
			}
		}
	}
	
	return $result;
}

function get_log() {
	global $config, $log;
	
	$result = array();

	if (read_config_option('intropage_log_analyze_enable')) {
		$log = array(
			'file' => read_config_option("path_cactilog"),
			'nbr_lines' => read_config_option("intropage_log_analyze_rows"),
		);
		$log['size'] = filesize($log['file']);
		$log['lines'] = tail_log($log['file'],$log['nbr_lines']);
	} else {
		$log = array(
			'size' => false,
			'file' => read_config_option("path_cactilog"),
			'nbr_lines' => 0,
		);
	}
	
	$result['log_size'] = get_log_size();
	$result['poller'] = get_poller_stats();
	$result['log_err'] = get_log_msg();
	
	return $result;
}

?>
