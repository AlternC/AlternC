<?php
/*
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
*/

/**
 * Delete one or more AlternC's accounts
 * of course, confirm the deletion
 * 
 * @copyright AlternC-Team 2000-2017 https://alternc.com/ 
 */

require_once("../class/config.php");

if (!$admin->enabled) {
  $msg->raise("ERROR", "admin", __("This page is restricted to authorized staff", "alternc", true));
  echo $msg->msg_html_all();
  exit();
}

$fields = array (
		 "accountList" => array ("post", "array", array()),
		 "del_confirm" => array("post", "string", ""),
);
getFields($fields);

if($del_confirm == "y"){
  foreach ($accountList as $key => $val) {
    if (!$admin->checkcreator($val)) {
      $msg->raise("ERROR", "admin", __("This page is restricted to authorized staff", "alternc", true));
      echo $msg->msg_html_all();
      exit();
    }
    if (!($u=$admin->get($val)) || !$admin->del_mem($val)) {
      $msg->raise("ERROR", "admin", __("Member '%s' does not exist", "alternc", true),$val);
    } else {
      $msg->raise("INFO", "admin", __("Member %s successfully deleted", "alternc", true),$u["login"]);
    }
  }
  include("adm_list.php");
  exit();
} else {
  if (!is_array($accountList) || count($accountList)==0) {
    $msg->raise("ERROR", "admin", __("Please check the accounts you want to delete", "alternc", true));
    require("adm_list.php");
    exit();
  } 
    include("head.php");
    ?>
    </head>
    <body>
    <h3><?php printf(__("Deleting users", "alternc", true)); ?> : </h3>
    <form action="adm_dodel.php" method="post">
 <?php csrf_get(); ?>
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
