<?php
/*
 adm_passpolicy.php
 ----------------------------------------------------------------------
 AlternC - Web Hosting System
 Copyright (C) 2002-2010 by the AlternC Development Team.
 http://alternc.org/
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
 Purpose of file: Manage the password policy for AlternC
 ----------------------------------------------------------------------
*/
require_once("../class/config.php");

if (!$admin->enabled) {
	__("This page is restricted to authorized staff");
	exit();
}

$fields = array (
	"edit"   => array ("request", "string", ""),

	"minsize"    => array ("request", "integer", "0"),
	"maxsize" => array ("request", "integer", "64"),
	"classcount" => array ("request", "integer", "0"),
	"allowlogin" => array ("request", "integer", "0"),
);

getFields($fields);


include_once("head.php");

?>
<h3><?php __("Manage Password Policy"); ?></h3>
<hr id="topbar"/>
<br />
<?php
	if ($error) {
	  echo "<p class=\"error\">$error</p>";
	}

$c=$admin->listPasswordPolicies();
//echo "<pre>"; print_r($c); echo "</pre>";

if ($doedit) {
  if (!$c[$doedit]) {
    echo "<p class=\"error\">"._("Policy not found")."</p>";
  } else {
    // Change it ;) 
    if ($admin->editPolicy($doedit,$minsize,$maxsize,$classcount,$allowlogin)) {
      echo "<p class=\"info\">"._("Policy changed")."</p>";
      unset($edit);
      $c=$admin->listPasswordPolicies();
    } else {
      echo "<p class=\"error\">"._("Cannot edit the policy, an error occurred")."</p>";
    }
  }
}

if ($edit) {
  if (!$c[$edit]) {
    echo "<p class=\"error\">"._("Policy not found")."</p>";
  } else {
?>

 <p><?php __("Please choose which policy you want to apply to this password kind:"); ?></p>

																	     <p><b><?php echo $c[$edit]["description"]; ?></b></p>

<form method="post" name="adm_passpolicy.php">
<input type="hidden" name="doedit" value="<?php echo $edit; ?>"/> 
<table class="tlist">
<tr>
  <th><?php __("Minimum Password Size:"); ?></th>
						<td><select class="inl" name="minsize" id="minsize"><?php for($i=0;$i<=64;$i++) {
						  echo "<option";
						  if ($c[$edit]["minsize"]==$i) echo " selected=\"selected\"";
						  echo ">$i</option>";
						}
?></td></tr>
  <tr><th><?php __("Maximum Password Size:"); ?></th>
						<td><select class="inl" name="maxsize" id="maxsize"><?php for($i=0;$i<=64;$i++) {
						  echo "<option";
						  if ($c[$edit]["maxsize"]==$i) echo " selected=\"selected\"";
						  echo ">$i</option>";
						}
?></td></tr>
  <tr>  <th><?php __("In how many classes of characters must be the password (at least):"); ?></th>
						<td><select class="inl" name="classcount" id="classcount"><?php for($i=0;$i<=4;$i++) {
						  echo "<option";
						  if ($c[$edit]["classcount"]==$i) echo " selected=\"selected\"";
						  echo ">$i</option>";
						}
?></td></tr>
  <tr>  <th><?php __("Do we allow the password to be like the login?"); ?></th>
						<td>
      <input type="radio" name="allowlogin" id="allowlogin0" value="0" <?php cbox(!$c[$edit]["allowlogin"]); ?> />&nbsp;<?php __("No"); ?>
      <input type="radio" name="allowlogin" id="allowlogin1" value="1" <?php cbox($c[$edit]["allowlogin"]); ?> />&nbsp;<?php __("Yes"); ?>
</td></tr>
</table>
<p><input type="submit" class="inb" name="go" value="<?php __("Apply this password policy"); ?>" /> &nbsp; 
<input type="button" class="inb" name="cancel" value="<?php __("Cancel and go back to the policy list"); ?>" onclick="document.location='adm_passpolicy.php'" /></p>
</form>

      <p><?php __("The classes of characters are : <br />1. Low-case letters (a-z)<br />2. Upper-case letters (A-Z)<br />3. Figures (0-9)<br />4. Ascii symbols (!\"#$%&'()*+,-./:;<=>?@[\\]^_`)<br />5. Non-Ascii symbols (~יאגפ...)"); ?></p>
</p>

<?php
    require_once("foot.php");
    exit();
  }
}
    
if (is_array($c)) {

?>
<p>
<?php __("Here is the list of the password policies for each place a password may be needed in AlternC's services. For each of those password kind, you can choose which policy will be applied to passwords. A policy is a minimum and maximum password size, and how many classes of characters must appear in the password. You can also forbid (or not) to use the login or part of it as a password."); ?>
</p>

<table class="tlist">
    <tr><th rowspan="2"> </th><th rowspan="2"><?php __("Password Kind"); ?></th><th colspan="4"><?php __("Password Policy"); ?></th></tr>
<tr>
  <th><?php __("Min Size"); ?></th>
  <th><?php __("Max Size"); ?></th>
  <th><?php __("Complexity"); ?></th>
  <th><?php __("Allow Password=Login?"); ?></th>
</tr>
<?php
$col=1;
 foreach($c as $v) {
 $col=3-$col;
?>

<tr class="lst<?php echo $col; ?>">
<td class="center">
   <div class="ina"><a href="adm_passpolicy.php?edit=<?php echo urlencode($v["name"]); ?>"><img src="images/edit.png" alt="<?php __("Edit"); ?>" /><?php __("Edit"); ?></a></div>
</td>
 <td><?php echo $v["description"]; ?></td>
 <td class="center"><?php echo $v["minsize"]; ?></td>
 <td class="center"><?php echo $v["maxsize"]; ?></td>
 <td class="center"><?php echo $v["classcount"]; ?></td>
 <td class="center"><?php if ($v["allowlogin"]) __("Yes"); else __("No"); ?></td>
</tr>
<?php
}
?>
</table>

    <?php } ?>

<?php include_once("foot.php"); ?>
