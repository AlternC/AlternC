<?php
/*
 $Id: mail_dodel.php,v 1.2 2003/06/10 06:45:16 root Exp $
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
 Purpose of file: Delete a mailbox
 ----------------------------------------------------------------------
*/
require_once("../class/config.php");


$fields = array (
	"d"    => array ("request", "array", ""),
	"domain"  => array ("request", "string", ""),
);
getFields($fields);

if (!is_array($d)) {
        $d[]=$d;
}

reset($d);

include_once ("head.php");

if ($confirm=="y") {

while (list($key,$val)=each($d)) {
	if (!$mail->del_mail($val)) {
		$error.=sprintf(_("The mailbox <b>%s</b> does not exist!")."<br />",$val);
	} else {
		$error.=sprintf(_("The mailbox <b>%s</b> has been deleted!")."<br />",$val); 
	}
}
include("mail_list.php");
 exit();
}

?>
</head>
<body>
<h3><?php __("Deleting mail accounts"); ?> : </h3>
<p><?php __("Please confirm the deletion of the following mail accounts:"); ?></p>
<form method="post" action="mail_del.php" id="main">
<p>
<input type="hidden" name="confirm" value="y" />
<input type="hidden" name="domain" value="<?php echo $domain; ?>" />

<?php

while (list($key,$val)=each($d)) {
  echo "<input type=\"hidden\" name=\"d[]\" value=\"$val\" />";
  echo $val."<br />";
}

?>
</p>
<p><input type="submit" class="inb" name="submit" value="<?php __("Delete the selected mailboxes"); ?>" /> - <input type="button" name="cancel" id="cancel" onclick="window.history.go(-1);" class="inb" value="<?php __("Don't delete accounts and go back to the mail list"); ?>"/>
</p>

<p>
<?php __("Warning: Deleting a mailbox will destroy all the emails it contains! You will <b>NOT</b> be able to get it back!"); ?>
</p>

</form>
<?php include_once("foot.php"); ?>
</body>
</html>