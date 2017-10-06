<?php
/*
 $Id: dom_subdel.php,v 1.3 2003/08/13 23:31:47 root Exp $
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
 Purpose of file: delete a subdomain
 ----------------------------------------------------------------------
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
  $msg->raise("ERROR", "dom", _("This page is restricted to authorized staff"));
  echo $msg->msg_html_all();
  exit();
}

?>
<h3><?php printf(_("Deleting subdomain %s"),ife($r['name'],$r['name'].".").$r['domain']); ?> : </h3>
<?php
if ($msg->has_msgs('Error')) {
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
      echo "<li>"._("Entry:")." ".( empty($r['name'])?'':$r['name'].".").$r['domain']."</li>";
      echo "<li>"._("Type:")." "._($r['type_desc'])."</li>";
      if (!empty($r['dest'])) {
        echo "<li>"._("Value:")." "._($r['dest'])."</li>";
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
