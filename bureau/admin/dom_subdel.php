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
 * Form to confirm the deletion of a subdomain
 *
 * @copyright AlternC-Team 2000-2017 https://alternc.com/ 
 */

require_once("../class/config.php");
include_once("head.php");

$fields = array (
  "sub_domain_id"       => array ("request", "integer", ""),
);
getFields($fields);

$dom->lock();
$r=$dom->get_sub_domain_all($sub_domain_id);
$dom->unlock();

$dt=$dom->domains_type_lst();
if (!$isinvited && $dt[strtolower($r['type'])]["enable"] != "ALL" ) {
  $msg->raise("ERROR", "dom", __("This page is restricted to authorized staff", "alternc", true));
  echo $msg->msg_html_all();
  exit();
}

?>
<h3><?php printf(__("Deleting subdomain %s", "alternc", true),ife($r['name'],$r['name'].".").$r['domain']); ?> : </h3>
<?php
if ($msg->has_msgs("ERROR")) {
  echo $msg->msg_html_all();
  include_once("foot.php");
  exit();
}
?>
<hr id="topbar"/>
<br />
<form action="dom_subdodel.php" method="post">
  <?php csrf_get(); ?>
  <p class="alert alert-warning">
    <input type="hidden" name="sub_domain_id" value="<?php ehe($sub_domain_id); ?>" />
    <?php __("WARNING : You are going to delete a sub-domain."); ?></p>
    <p><?php 
      __("Informations about the subdomain you're going to delete:");
      echo "<ul>";
      echo "<li>".__("Entry:", "alternc", true)." ".( empty($r['name'])?'':$r['name'].".").$r['domain']."</li>";
      echo "<li>".__("Type:", "alternc", true)." ".__($r['type_desc'], "alternc", true)."</li>";
      if (!empty($r['dest'])) {
        echo "<li>".__("Value:", "alternc", true)." ".__($r['dest'], "alternc", true)."</li>";
      }
      echo "</ul>";
      echo "<br/>";
      __("Do you really want to delete it?");
      ?>
    </p>
    <blockquote>
      <input type="submit" class="inb" name="confirm" value="<?php __("Yes"); ?>" />&nbsp;&nbsp;
      <span class="ina"><a href="dom_edit.php?domain=<?php echo urlencode($r['domain']) ?>"><?php __("No"); ?></a></span></p>
    </blockquote>
</form>
<?php include_once("foot.php"); ?>
