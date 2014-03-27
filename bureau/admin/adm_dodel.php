<?php
/*
 $Id: adm_dodel.php,v 1.2 2004/05/19 14:23:06 benjamin Exp $
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
 Original Author of file: Benjamin Sonntag
 Purpose of file: Delete a member
 ----------------------------------------------------------------------
*/
require_once("../class/config.php");

if (!$admin->enabled) {
  __("This page is restricted to authorized staff");
  exit();
}

$fields = array (
		 "accountList" => array ("request", "array", array()),
		 "del_confirm" => array("request", "string", ""),
);
getFields($fields);

if($del_confirm == "y"){
  $error = "";
  foreach ($accountList as $key => $val) {
    if (!$admin->checkcreator($val)) {
      __("This page is restricted to authorized staff");
      exit();
    }
    if (!($u=$admin->get($val)) || !$admin->del_mem($val)) {
      $error .= sprintf(_("Member '%s' does not exist"),$val)."<br />";
    } else {
      $error .= sprintf(_("Member %s successfully deleted"),$u["login"])."<br />";
    }
  }
  include("adm_list.php");
  exit();
} else {
  if (!is_array($accountList) || count($accountList)==0) {
    $error=_("Please check the accounts you want to delete");
    require("adm_list.php");
    exit();
  } 
    include("head.php");
    ?>
    </head>
    <body>
    <h3><?php printf(_("Deleting users")); ?> : </h3>
    <form action="adm_dodel.php" method="post">
      <input type="hidden" name="action" value="delete" />
      <input type="hidden" name="del_confirm" value="y" />
      <p class="alert alert-warning"><?php __("WARNING : Confirm the deletion of the users"); ?></p>
      <p>
		  <ul>
			  <?php
				foreach($accountList as $userid){
				  $membre   = $admin->get($userid);
				  echo "<li><input type=\"hidden\" name=\"accountList[]\" value=\"$userid\" />".$membre['login']."</li>";
				}
			  ?>
		  </ul>
      </p>
      <blockquote>
	  <input type="submit" class="inb ok" name="confirm" value="<?php __("Yes, delete those accounts"); ?>" />&nbsp;&nbsp;
    <input type="button" class="inb cancel" name="cancel" value="<?php __("No, don't delete those accounts"); ?>" onclick="document.location='adm_list.php';" />
      </blockquote>
    </form>
    <?php  
    include('foot.php');
}

?>
