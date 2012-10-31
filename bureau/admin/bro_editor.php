<?php
/*
 $Id: bro_editor.php,v 1.5 2005/05/03 14:49:06 anarcat Exp $
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
 Purpose of file: Editor of the browser
 ----------------------------------------------------------------------
*/
require_once("../class/config.php");

$fields = array (
	"editfile"    		=> array ("request", "string", ""),
	"texte"    		=> array ("post", "string", ""),
	"save"    		=> array ("post", "string", ""),
	"saveret"    		=> array ("post", "string", ""),
	"cancel"    		=> array ("post", "string", ""),
	"R"	    		=> array ("request", "string", ""),
);
getFields($fields);

$editfile=ssla($editfile);
$texte=ssla($texte);

$R=$bro->convertabsolute($R,1);
$p=$bro->GetPrefs();

if (isset($cancel) && $cancel) {
	include("bro_main.php");
	exit();
}
if (isset($saveret) && $saveret) {
  if ($bro->Save($editfile,$R,$texte)) {
    $error=sprintf(_("Your file %s has been saved"),$editfile)." (".format_date(_('%3$d-%2$d-%1$d %4$d:%5$d'),date("Y-m-d H:i:s")).")";
  } else {
    $error=$err->errstr();
  }
  include("bro_main.php");
  exit();
}
if (isset($save) && $save) {
  if ($bro->Save($editfile,$R,$texte)) {
    $error=sprintf(_("Your file %s has been saved"),$editfile)." (".format_date(_('%3$d-%2$d-%1$d %4$d:%5$d'),date("Y-m-d H:i:s")).")";
  } else {
    $error=$err->errstr();
  }
}

include_once("head.php");

?>
<p>
<?php if (isset($error) && $error) echo "<p class=\"error\">$error</p>"; ?>
<h3><?php echo _("File editing")." <code>$R/<b>$editfile</b></code><br />"; ?></h3>
</p>
<form action="bro_editor.php" method="post"><br />
<textarea class="int" style="font-family: <?php echo $p["editor_font"]; ?>; font-size: <?php echo $p["editor_size"]; ?>; width: 90%; height: 400px;" name="texte"><?php
  $failed=false;
  $content=$bro->content($R,$editfile);
  if (empty($content)) { 
    $failed=true;
  } else {
    echo $content;  
  }
?></textarea>
<?php if ($failed) echo "<p class=\"error\">".$err->errstr()."</p>"; ?>
	<input type="hidden" name="editfile" value="<?php echo str_replace("\"","&quot;",$editfile); ?>" />
	<input type="hidden" name="R" value="<?php echo str_replace("\"","&quot;",$R); ?>" />

	<input type="submit" class="inb" value="<?php __("Save"); ?>" name="save" />
	<input type="submit" class="inb" value="<?php __("Save &amp; Quit"); ?>" name="saveret" />
	<input type="submit" class="inb" value="<?php __("Quit"); ?>" name="cancel" />
<br />
</form>
<?php include_once("foot.php"); ?>
