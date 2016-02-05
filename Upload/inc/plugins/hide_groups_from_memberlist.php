<?php

if(!defined('IN_MYBB'))
{
	die('This file cannot be accessed directly.');
}


function hide_groups_from_memberlist_info()
{
	return array (
		'name' => 'Hide groups from memberlist',
		'description' => 'Hides groups from the memberlist page',
		'website' => 'http://github.com/dequeues',
		'author' => 'Nathan',
		'authorsite' => 'http://github.com/dequeues',
		'version' => '1.1',
		'compatibility' => '18'
	);
}

if(!defined("PLUGINLIBRARY"))
{
    define("PLUGINLIBRARY", MYBB_ROOT."inc/plugins/pluginlibrary.php");
}

function hide_groups_from_memberlist_activate()
{
	global $db, $PL;
	$PL or require_once PLUGINLIBRARY;

	$number_groups_query = $db->simple_select("settinggroups", "COUNT(*) AS num_groups");
	$number_groups = (int)$db->fetch_field($number_groups_query, "num_groups");

	$setting_group = array (
		'name' => 'hidegroupsfrommemberlist',
		'title' => 'Hide groups from memberlist',
		'description' => 'Hides groups from the memberlist page',
		'disporder' => ((int)$number_groups + 1),
	);
	$gid = $db->insert_query('settinggroups', $setting_group);


	$settings = array (
		'hidegroupsfrommemberlist_enabled' => array (
			'title' => 'Enabled?',
			'description' => 'Whether the plugin is enabled or not',
			'optionscode' => 'onoff',
			'value' => 1
		),
		'hidegroupsfrommemberlist_groupstohide' => array (
			'title' => 'Groups to hide from memberlist',
			'description' => 'Set which groups do not get shown on the memberlist',
			'optionscode' => 'groupselect'
		)
	);

	$disporder = 1;
	foreach ($settings as $name => $setting)
	{
		$setting['name'] = $name;
		$setting['gid'] = $gid;
		$setting['disporder'] = $disporder;
		$db->insert_query('settings', $setting);
		$disporder++;
	}

	rebuild_settings();

	$search = array('while($user = $db->fetch_array($query))', '{', '$user = $plugins->run_hooks("memberlist_user", $user);', '$alt_bg = alt_trow();');
	$PL->edit_core('hide_groups_from_memberlist', 'memberlist.php',
		array(
			'search' => $search,
			'replace' => '$hide_groups = explode(\',\', $mybb->settings[\'hidegroupsfrommemberlist_groupstohide\']);
				while($user = $db->fetch_array($query))
				{
					$user = $plugins->run_hooks("memberlist_user", $user);
					if (in_array($user[\'usergroup\'], $hide_groups))
					{
						continue;
					}
					$alt_bg = alt_trow();'
		),
		true
	);

	$search = array('$group = array();');
	$PL->edit_core('hide_groups_from_memberlist', 'memberlist.php',
		array(
			'search' => $search,
			'replace' => '$group = array();
			$hidegroups = explode(\',\', $mybb->settings[\'hidegroupsfrommemberlist_groupstohide\']);
			foreach($hidegroups as $hgid)
			{
				$group[] = (int)$hgid;
			}'
		),
		true
	);
}

function hide_groups_from_memberlist_deactivate()
{
	global $db, $PL;
	$PL or require_once PLUGINLIBRARY;

	$PL->edit_core('hide_groups_from_memberlist', 'memberlist.php',
		array (),
		true
	);

	$db->delete_query('settinggroups', "name ='hidegroupsfrommemberlist'");
	$db->delete_query('settings', "name LIKE ('hidegroupsfrommemberlist_%')");

	rebuild_settings();
}
