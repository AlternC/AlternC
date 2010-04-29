<?php
/*
 $Id: bro_pref.php,v 1.2 2003/06/10 06:45:16 root Exp $
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
 Purpose of file: Configuration of the file browser
 ----------------------------------------------------------------------
*/
require_once("../class/config.php");

if ($submit) {
	$bro->SetPrefs($editsizex, $editsizey, $listmode, $showicons, $downfmt, $createfile, $showtype, $editor_font, $editor_size, $golastdir);
	$error=_("Your preferences have been updated.");
	include("bro_main.php");
	exit;
}
$p=$bro->GetPrefs();

include_once("head.php");

?>
<?php if ($error) echo "<font color=\"red\">$error</font><br />"; ?>
<h3><?php __("File browser preferences"); ?></h3>
<form action="bro_pref.php" method="post">


<table cellpadding="6" border="1" cellspacing="0">
<tr><td><?php __("Horizontal window size"); ?></td><td><select class="inl" name="editsizex">
<?php
for($i=50;$i<=200;$i+=10) {
	echo "<option";
	if ($p["editsizex"]==$i) echo " selected";
	echo ">$i";
}
?></select></td></tr>
<tr><td><?php __("Vertical window size"); ?></td><td><select class="inl" name="editsizey">
<?php
for($i=4;$i<=80;$i+=2) {
	echo "<option";
	if ($p["editsizey"]==$i) echo " selected";
	echo ">$i";
}
?></select></td></tr>
<tr><td><?php __("File editor font name"); ?></td><td><select class="inl" name="editor_font">
<?php
for($i=0;$i<count($bro->l_editor_font);$i++) {
	echo "<option";
	if ($p["editor_font"]==$bro->l_editor_font[$i]) echo " selected";
	echo ">"._($bro->l_editor_font[$i]);
}
?></select></td></tr>
<tr><td><?php __("File editor font size"); ?></td><td><select class="inl" name="editor_size">
<?php
for($i=0;$i<count($bro->l_editor_size);$i++) {
	echo "<option";
	if ($p["editor_size"]==$bro->l_editor_size[$i]) echo " selected";
	echo ">"._($bro->l_editor_size[$i]);
}
?></select></td></tr>
<tr><td><?php __("File list view"); ?></td><td><select class="inl" name="listmode">
<?php
for($i=0;$i<count($bro->l_mode);$i++) {
	echo "<option";
	if ($p["listmode"]==$i) echo " selected";
	echo " value=\"$i\">"._($bro->l_mode[$i])."</option>";
}
?></select></td></tr>
<tr><td><?php __("Downloading file format"); ?></td><td><select class="inl" name="downfmt">
<?php
for($i=0;$i<count($bro->l_tgz);$i++) {
	echo "<option";
	if ($p["downfmt"]==$i) echo " selected";
	echo " value=\"$i\">"._($bro->l_tgz[$i])."</option>";
}
?></select></td></tr>
<tr><td><?php __("What to do after creating a file"); ?></td><td><select class="inl" name="createfile">
<?php
for($i=0;$i<count($bro->l_createfile);$i++) {
	echo "<option";
	if ($p["createfile"]==$i) echo " selected";
	echo " value=\"$i\">"._($bro->l_createfile[$i])."</option>";
}
?></select></td></tr>
<tr><td><?php __("Show icons?"); ?></td><td><select class="inl" name="showicons">
<?php
for($i=0;$i<count($bro->l_icons);$i++) {
	echo "<option";
	if ($p["showicons"]==$i) echo " selected";
	echo " value=\"$i\">"._($bro->l_icons[$i])."</option>";
}
?></select></td></tr>
<tr><td><?php __("Show file types?"); ?></td><td><select class="inl" name="showtype">
<?php
for($i=0;$i<count($bro->l_icons);$i++) {
	echo "<option";
	if ($p["showtype"]==$i) echo " selected";
	echo " value=\"$i\">"._($bro->l_icons[$i])."</option>";
}
?></select></td></tr>
<tr><td><?php __("Remember last visited directory?"); ?></td><td><select class="inl" name="golastdir">
<?php
for($i=0;$i<count($bro->l_icons);$i++) {
	echo "<option";
	if ($p["golastdir"]==$i) echo " selected";
	echo " value=\"$i\">"._($bro->l_icons[$i])."</option>";
}
?></select></td></tr>

<tr class="trbtn"><td colspan="2">
  <input type="submit" name="submit" class="inb" value="<?php __("Change my settings"); ?>" />
  <input type="button" class="inb" name="cancel" value="<?php __("Cancel"); ?>" onclick="document.location='bro_main.php'"/>

</td></tr>
</table>

</form>
<?php include_once("foot.php"); ?>