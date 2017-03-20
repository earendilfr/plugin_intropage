<?php

function plugin_intropage_install() {
	api_plugin_register_hook('intropage', 'config_form','intropage_config_form', 'include/settings.php');
	api_plugin_register_hook('intropage', 'config_settings', 'intropage_config_settings', 'include/settings.php');
	api_plugin_register_hook('intropage', 'login_options_navigate', 'intropage_login_options_navigate', 'include/settings.php');
	
	api_plugin_register_hook('intropage', 'top_header_tabs', 'intropage_show_tab', 'include/tab.php');
	api_plugin_register_hook('intropage', 'top_graph_header_tabs', 'intropage_show_tab', 'include/tab.php');
	
	api_plugin_register_hook('intropage', 'console_after', 'intropage_console_after', 'include/settings.php');

	api_plugin_register_hook('intropage', 'user_admin_setup_sql_save', 'intropage_user_admin_setup_sql_save', 'include/settings.php');

	api_plugin_register_realm('intropage', 'intropage.php', 'Plugin Intropage - view', 1);
	
	intropage_setup_database();
}

function plugin_intropage_uninstall () {
	db_execute("DELETE FROM settings WHERE name LIKE 'intropage_%'");
}

function plugin_intropage_version()	{
	global $config;
	$info = parse_ini_file($config['base_path'] . '/plugins/intropage/INFO', true);
	return $info['info'];
}

function plugin_intropage_upgrade() {
	// Here we will upgrade to the newest version
	intropage_check_upgrade();
	return false;
}

function plugin_intropage_check_config () {
	// Here we will check to ensure everything is configured
	intropage_check_upgrade();
	return true;
}

function intropage_check_upgrade() {
	// If action need to be done for upgrade, add it.
	
	$oldv = db_fetch_cell('SELECT version FROM plugin_config WHERE directory="intropage"');
	if ($oldv < 0.9) {
		api_plugin_db_add_column ('user_auth',array('name' => 'intropage_opts', 'type' => 'tinyint(1)', 'NULL' => false, 'default' => '0'));
		db_execute('UPDATE plugin_hooks SET function="intropage_config_form", file="include/settings.php" WHERE name="intropage" AND hook="config_form"');
		db_execute('UPDATE plugin_hooks SET function="intropage_config_settings", file="include/settings.php" WHERE name="intropage" AND hook="config_settings"');
		db_execute('UPDATE plugin_hooks SET function="intropage_show_tab", file="include/tab.php" WHERE name="intropage" AND hook="top_header_tabs"');
		db_execute('UPDATE plugin_hooks SET function="intropage_show_tab", file="include/tab.php" WHERE name="intropage" AND hook="top_graph_header_tabs"');
		db_execute('UPDATE plugin_hooks SET function="intropage_login_options_navigate", file="include/settings.php" WHERE name="intropage" AND hook="login_options_navigate"');
		db_execute('UPDATE plugin_hooks SET function="intropage_console_after", file="include/settings.php" WHERE name="intropage" AND hook="console_after"');
		db_execute('UPDATE user_auth set login_opts=1 WHERE login_opts in (4,5)');
	}
}

function intropage_setup_database() {
	global $config, $intropage_settings;
	api_plugin_db_add_column ('intropage', 'user_auth',array('name' => 'intropage_opts', 'type' => 'tinyint(1)', 'NULL' => false, 'default' => '0'));
	
	include_once($config['base_path'] . '/plugins/intropage/include/variables.php');
	$sql_insert = '';
	foreach ($intropage_settings as $key=>$value)   {
		if (isset($value['default']) && !db_fetch_cell("SELECT value FROM settings WHERE name='$key'")) {
			if ($sql_insert != '') $sql_insert .= ",";
			$sql_insert .= sprintf("(%s,%s)",db_qstr($key),db_qstr($value['default']));
		}
	}
	if ($sql_insert != '') {
		db_execute("INSERT INTO settings (name, value) VALUES $sql_insert");
	}
}

?>
