<?php
/*
 $Id: piwik_userlist.php, author: François Serman <fser>
 ----------------------------------------------------------------------
 AlternC - Web Hosting System
 Copyright (C) 2002 by the AlternC Development Team.
 http://alternc.org/
 ----------------------------------------------------------------------
 Based on:
 Valentin Lacambre's web hosting softwares: http://altern.org/
 ----------------------------------------------------------------------
 LICENSE

 This program is free software; you can redistribute it and/or
 modify it under the terms of the GNU General Public License (GPL)
 as published by the Free Software Foundation; either version 2
 of the License, or (at your option) any later version.

 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 To read the license please visit http://www.gnu.org/copyleft/gpl.html
 ----------------------------------------------------------------------
 Purpose of file: Admin piwik users right for piwik sites
 ----------------------------------------------------------------------
*/

require_once("../class/config.php");
include_once("head.php");

$fields = array (
	"user_name"	      => array ("post", "string", FALSE),
	"site_id"	      => array ("post", "integer", -1),
	"right"		      => array ("post", "string", FALSE),
);
getFields($fields);

if ($user_name === FALSE)
{
	$error = _('No piwik user specified');
}
else
{
	// Add a user to a piwik website
	if ($site_id != -1 && $right !== FALSE) {
		$db->query("SELECT COUNT(*) AS ok FROM piwik_sites WHERE uid=? AND piwik_id=?;",array($cuid,$site_id));
		$db->next_record();
		if ($db->f('ok')!=1)
		{
			$error = _("You don't own this piwik website");
		}
		else
		{
			$db->query("SELECT COUNT(*) AS ok FROM piwik_users WHERE uid=? AND login=?",array($cuid,$user_name));
			$db->next_record();
			if ($db->f('ok')!=1)
			{
				$error = _("You don't own this piwik user");
			}
			else
			{
				$piwik_rights = array("noaccess", "view", "admin");
				if (in_array($right, $piwik_rights))
				{
					$api_data = $piwik->site_set_user_right($site_id, $user_name, $right);
					if ($api_data === FALSE)
						echo $error;
					else
						__('success');
				}
				else
				{
					$error = _("This right does not exist");
				}
			}
		}
	}

	$user_piwik_sites = array();
	$db->query("SELECT piwik_id FROM piwik_sites WHERE uid=?",array($cuid));
	while ($db->next_record()) 
		array_push($user_piwik_sites, $db->f('piwik_id'));
	// Weird behaviour of php: array_push products an array such as:
	// array_push(array(1,2,3) , 4) produces
	// array(0 => 1, 1 => 2, 2 => 3, 3 => 4)
	// So for further comparison, we need to exchange keys and values
	$user_piwik_sites = array_flip($user_piwik_sites);

	$user_piwik_users = array();
	$db->query("SELECT login FROM piwik_users WHERE uid=?",arary($cuid));
	while ($db->next_record())
		array_push ($user_piwik_users, $db->f('login'));
	// Swap keys and values, see user_piwik_sites
	$user_piwik_users = array_flip($user_piwik_users);
}


if (isset($error) && $error) {
  	echo "<p class=\"alert alert-danger\">$error</p>";
	exit;
}
?>
<h3><?php printf('%s "%s"', _("Rights for user"), $user_name); ?></h3>
<?php
$raw_sites = $piwik->get_site_list();
$piwik_sites = array();
foreach ($raw_sites AS $site) {
	$piwik_sites[ $site->idsite ] = array('name' => $site->name, 'url' => $site->main_url);
}

$raw_access = $piwik->get_site_access($user_name);

$piwik_user_sites = array_intersect_ukey($piwik_sites, $user_piwik_sites, "strcmp");
$available_user_sites = $piwik_user_sites;

echo '<ul>';
foreach ($raw_access AS $access)
{
	unset($available_user_sites[ $access->site ]);
	printf("<li>%s -> %s</li>\n", $piwik_sites[ $access->site ]['name'], $access->access);
}
echo '</ul>';
if (count($available_user_sites)>0)
{
?>
<h3><?php printf('%s "%s"', _("Add rights to user"), $user_name); ?></h3>
<ul>
<?php
foreach ($available_user_sites AS $current_id_site => $available_user_site)
{
	printf('<li>%s <form method="post"><input type="hidden" name="site_id" value="%d">
<input type="hidden" name="csrf" value="'.csrf_get(true).'" />
<select name="right">
	<option value="noaccess">%s</option>
	<option value="view">%s</option>
	<option value="admin">%s</option>
</select>
<input type="submit" name="add" value="ajouter" class="inb" /></form></li>', $available_user_site['name'], $current_id_site, _("noacces"), _("view"), _("admin"));
}
?>
</li>
<?php 
}
include_once("foot.php"); ?>
