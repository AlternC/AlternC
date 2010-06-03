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

if($_POST["del_confirm"] == "y"){
  if (!is_array($d)) {
    $d[]=$d;
  }

  reset($d);
  while (list($key,$val)=each($d)) {
    if (!$admin->checkcreator($val)) {
      __("This page is restricted to authorized staff");
      exit();
    }
    if (!($u=$admin->get($val)) || !$admin->del_mem($val)) {
      $error.=sprintf(_("Member '%s' does not exist"),$val)."<br />";
    } else {
      $error.=sprintf(_("Member %s successfully deleted"),$u["login"])."<br />";
    }
  }
  include("adm_list.php");
  exit();
} else {
  if (!is_array($d) || count($d)==0) {
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
      <p class="error"><?php __("WARNING : Confirm the deletion of the users"); ?></p>
      <p>
      <?php
        foreach($d as $userid){
          $membre=$admin->get($userid);
          echo "<input type=\"hidden\" name=\"d[]\" value=\"$userid\" />".$membre['login']."<br/>";
        }
      ?>
      </p>
      <blockquote>
        <input type="submit" class="inb" name="confirm" value="<?php __("Yes"); ?>" />&nbsp;&nbsp;
        <input type="button" class="inb" name="cancel" value="<?php __("No"); ?>" onclick="document.location='adm_list.php';" />
      </blockquote>
    </form>
    </body>
    </html>
    <?php  
}

?>
