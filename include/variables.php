<?php

$intropage_settings = array(
	"intropage_ntp_header" => array(
		"friendly_name" => "NTP settings",
		"method" => "spacer",
	),
	"intropage_ntp_enable" => array(
		"friendly_name" => "Allow NTP",
		"description" => "if checked this plugin is allowed to check time against NTP server",
		"method" => "checkbox",
		"default" => "on",
	),
	"intropage_ntp_server" => array(
		"friendly_name" => "IP or DNS name of NTP server",
		"description" => "Insert IP or DNS name of NTP server",
		"method" => "textbox",
		"max_length" => 50,
		"default" => "pool.ntp.org",
	),
	"intropage_log_analyse_header" => array(
		"friendly_name" => "Log analyse settings",
		"method" => "spacer",
	),
	"intropage_log_analyze_enable" => array(
		"friendly_name" => "Allow cacti log analyse",
		"description" => "if checked this plugin is allowed to analyse cacti log file",
		"method" => "checkbox",
		"default" => "on",
	),
	"intropage_log_analyze_rows" => array(
		"friendly_name" => "Number of lines",
		"description" => "How many lines of log will be analysed. Big number may causes slow page load",
		"method" => "textbox",
		"max_length" => 5,
		"default" => "1000",
	),
	"intropage_login_analyse_header" => array(
		"friendly_name" => "Login analyse settings",
		"method" => "spacer",
	),
	"intropage_login_analyze_enable" => array(
		"friendly_name" => "Allow logins analyse",
		"description" => "if checked this plugin is allowed to analyse logins log",
		"method" => "checkbox",
		"default" => "on",
	),
	"intropage_login_analyse_db" => array(
		"friendly_name" => "Analyze MySQL database",
		"method" => "spacer",
	),
	"intropage_db_check" => array(
		"friendly_name" => "Allow MySQL database check",
		"description" => "if checked this plugin is allowed to analyse MySQL database. It may causes slow page load",
		"method" => "checkbox",
		"default" => "on",
	),
	"intropage_db_check_level" => array(
		"friendly_name" => "Level of db check",
		"description" => "Quick - no check rows for inforccert links<br/>Fast - check only not properly closed tables<br/>Changed - check tables changed from last check<br/>Medium - with rows scan<br/>Extended - full rows and keys<br/><strong>Medium and extended may causes slow page load!</strong>",
		"method" => "drop_array",
		"array" => array("QUICK" => "Quick", "FAST" => "Fast", "CHANGED" => "Changed", "MEDIUM" => "Medium", "EXTENDED"  => "Extended"),
		"default" => "Medium",
	),
	"intropage_graph_and_tree_analyze" => array(
		"friendly_name" => "Analyze graphs and trees",
		"method" => "spacer",
	),
	"intropage_device_same_desc_check" => array(
		"friendly_name" => "Allow check for devices with the same description",
		"description" => "if checked this plugin is allowed to search for devices with the same description",
		"method" => "checkbox",
		"default" => "on",
	),
	"intropage_device_more_tree_check" => array(
		"friendly_name" => "Allow check for devices in more then one tree",
		"description" => "if checked this plugin is allowed to search for devices which are in more then one tree",
		"method" => "checkbox",
		"default" => "on",
	),
	"intropage_device_without_graph_check" => array(
		"friendly_name" => "Allow check for devices without graphs",
		"description" => "if checked this plugin is allowed to search for devices withhout graphs",
		"method" => "checkbox",
		"default" => "on",
	),
	"intropage_device_without_tree_check" => array(
		"friendly_name" => "Allow check for devices without tree",
		"description" => "if checked this plugin is allowed to search for devices which aren't in any tree",
		"method" => "checkbox",
		"default" => "on",
	),
	"intropage_device_not_monitored_check" => array(
		"friendly_name" => "Allow check for not monitored devices",
		"description" => "if checked this plugin is allowed to search for devices which aren't monitored",
		"method" => "checkbox",
		"default" => "on",
	),
	"intropage_display_header" => array(
		"friendly_name" => "Display settings",
		"method" => "spacer",
	),
	"intropage_display_layout" => array(
		"friendly_name" => "Layout",
		"description" => "Left to right or up to down",
		"method" => "drop_array",
		"array" => array("horizontal" => "Horizontal", "vertical" => "Vertical", "bestfit" => "Best fit"),
		"default" => "bestfit",
	),
	"intropage_display_level" => array(
		"friendly_name" => "Display",
		"description" => "What will you see",
		"method" => "drop_array",
		"array" => array("0" => "Only errors", "1" => "Errors and warnings", "2" => "All",),
		"default" => "2",
	),
	"intropage_display_os_info" => array(
		"friendly_name" => "Display type of OS and poller type",
		"description" => "if checked this plugin is displays information about OS and poller",
		"method" => "checkbox",
		"default" => "on",
	),
	"intropage_display_pie_host" => array(
		"friendly_name" => "Display pie graph for hosts (up/down/recovering/..)",
		"description" => "if checked this plugin displays pie graph for hosts. It needs GD library",
		"method" => "checkbox",
		"default" => "on",
	),
	"intropage_display_pie_threshold" => array(
		"friendly_name" => "Display pie graph for thresholds (ok/trigerred/..)",
		"description" => "if checked this plugin  displays pie graph for thresholds. It needs GD library",
		"method" => "checkbox",
		"default" => "on",
	),
	"intropage_display_pie_datasource" => array(
		"friendly_name" => "Display pie graph for datasources (SNMP/script/ ..)",
		"description" => "if checked this plugin displays pie graph for data sources. It needs GD library",
		"method" => "checkbox",
		"default" => "on",
	),
	"intropage_display_pie_template" => array(
		"friendly_name" => "Display pie graph for templates (generic/win/printer/..)",
		"description" => "if checked this plugin displays pie graph for templates. It needs GD library",
		"method" => "checkbox",
		"default" => "on",
	),
	"intropage_display_pie_mactrack" => array(
		"friendly_name" => "Display pie graph for Mactrack plugin",
		"description" => "if checked this plugin displays pie graph for Mactrack. It needs GD library",
		"method" => "checkbox",
		"default" => "on",
	),
	"intropage_display_topx" => array(
		"friendly_name" => "Display top 5 devices with the worst ping and availability",
		"description" => "if checked this plugin displays table of hosts with the worse ping and availability",
		"method" => "checkbox",
		"default" => "on",
	),

	"intropage_other_header" => array(
		"friendly_name" => "Others",
		"method" => "spacer",
	),
	"intropage_debug" => array(
		"friendly_name" => "Display debug information (wall clock)",
		"description" => "if checked this plugin displays execution time of particular components",
		"method" => "checkbox",
		"default" => "off",
	),
);

?>