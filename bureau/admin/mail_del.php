<?php
/*
 ----------------------------------------------------------------------
 AlternC - Web Hosting System
 Copyright (C) 2000-2012 by the AlternC Development Team.
 https://alternc.org/
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
 Purpose of file: Delete one or more mailboxes
 ----------------------------------------------------------------------
*/
require_once("../class/config.php");


$fields = array (
	"d"    => array ("post", "array", ""),
	"domain_id"  => array ("post", "integer", ""),
	"confirm" => array("post", "string", "n"),
);
getFields($fields);

if (!is_array($d)) {
        $d[]=$d;
}

reset($d);

include_once ("head.php");

if ($confirm=="y") {
  while (list($key,$val)=each($d)) {
    // Validate that this email is owned by me...
    if (!($email = $mail->is_it_my_mail($val))) {
      continue;
    }

    // Search for that address:
    $db->query("SELECT a.id, NOT ISNULL(m.id) AS islocal FROM address a LEFT JOIN mailbox m ON m.address_id=a.id WHERE a.id= ? ;", array($val));

    if (!$db->next_record()) {
      $msg->raise('Error', "mail", _("The email %s does not exist, it can't be deleted"), $email);
      continue;
    }

    if ($mail->delete($val)) {
      if ($db->f("islocal")) {
	$msg->raise('Ok', "mail", _("The email %s has been marked for deletion"), $email);
      } else {
        $msg->raise('Ok', "mail", _("The email %s has been successfully deleted"), $email);
      }
    }
  }
  include("mail_list.php");
  exit();
}

?>
<h3><?php __("Deleting mail accounts"); ?> : </h3>
<hr id="topbar"/>
<br />
<p><?php __("Please confirm the deletion of the following mail accounts:"); ?></p>
<form method="post" action="mail_del.php" id="main">
  <?php csrf_get(); ?>
<p>
<input type="hidden" name="confirm" value="y" />
<input type="hidden" name="domain_id" value="<?php ehe($domain_id); ?>" />

<ul>
<?php
while (list($key,$val)=each($d)) {
  $m=$mail->get_details($val);
  echo "<input type=\"hidden\" name=\"d[]\" value=\"".ehe($val,false)."\" />";
  echo "<li><b>".$m["address"]."@".$m["domain"]."</b></li>";
}
?>
</ul>
</p>
<p>
<input type="submit" class="inb" name="submit" value="<?php __("Confirm the deletion"); ?>" /> - <input type="button" name="cancel" id="cancel" onclick="window.history.go(-1);" class="inb" value="<?php __("Don't delete anything and go back to the email list"); ?>"/>
</p>

<p class="warningmsg">
<?php __("Warning: Deleting an email address will destroy all the messages it contains! You will <b>NOT</b> be able to get it back!"); ?>
</p>

</form>
<?php include_once("foot.php"); ?>
