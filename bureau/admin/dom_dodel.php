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
 * Delete a domain, confirm the deletion
 * 
 * @copyright AlternC-Team 2000-2017 https://alternc.com/ 
 */

require_once("../class/config.php");
include_once("head.php");

$fields = array (
	"domain"      => array ("request", "string", ""),
	"del_confirm" => array ("post",    "string", ""),
	"del_cancel"  => array ("post", "string", ""),
);
getFields($fields);

if ($del_confirm=="y") {
	if (!$dom->del_domain($domain)) {
		include("dom_edit.php");
		exit();
	}
}

if (! empty($del_cancel)) {
  $dom->lock();
  $dom->del_domain_cancel($domain);
  $dom->unlock();

  // The link to this function is disable : the del_domain_cancel function need some modification
  $msg->raise("INFO", "dom", __("Deletion have been successfully cancelled", "alternc", true));
  echo $msg->msg_html_all();
?>
  <p>
  <span class="ina"><a href="main.php" target="_parent"><?php __("Click here to continue"); ?></a></span>
  </p>
  <?php 
  exit();
}
if ($del_confirm!="y") {

?>
<h3><?php printf(__("Confirm the deletion of domain %s", "alternc", true),$domain); ?></h3>
<hr id="topbar"/>
<br />
<p class="alert alert-warning"><?php __("WARNING"); ?><br /><?php printf(__("Confirm the deletion of domain %s", "alternc", true),$domain); ?><br />

<?php __("This will delete the related sub-domains too."); ?></p>
<form method="post" action="dom_dodel.php" id="main">
 <?php csrf_get(); ?>
<p>
<input type="hidden" name="del_confirm" value="y" />
<input type="hidden" name="domain" value="<?php ehe($domain); ?>" />
 <input type="submit" class="inb ok" name="submit" value="<?php __("Yes, delete this domain name"); ?>" />
 <input type="button" class="inb cancel" name="non" value="<?php __("No, don't delete this domain name"); ?>" onclick="history.back()" />
</form>
<?php include_once("foot.php");
	exit();
}
?>
<h3><?php printf(__("Domain %s deleted", "alternc", true),$domain); ?></h3>
<hr id="topbar"/>
<br />
<?php 
$msg->raise("INFO", "dom", __("The domain %s has been successfully deleted.", "alternc", true),$domain);
echo $msg->msg_html_all();
?>
</p>
<span class="ina"><a href="main.php" target="_parent"><?php __("Click here to continue"); ?></a></span>
<?php $mem->show_help("del_domain"); ?>
</p>
<?php include_once("foot.php"); ?>
