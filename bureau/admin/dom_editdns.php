<?php
/*
 $Id: dom_editdns.php,v 1.3 2003/06/10 11:18:27 root Exp $
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
 Purpose of file: Edit the dns parameters of a domain
 ----------------------------------------------------------------------
*/
require_once("../class/config.php");
include_once("head.php");

$fields = array (
	"domain"    => array ("request", "string", ""),
	"dns"       => array ("post", "integer", 1),
	"email"     => array ("post", "integer", 1),
	"ttl"       => array ("post", "integer", 86400),
);
getFields($fields);

$dom->lock();

$r = $dom->get_domain_all($domain);
if ($r["dns"] == $dns && $r["mail"] == $email && $r["zonettl"] == $ttl) {
  $msg->raise("INFO", "dom", _("No change has been requested..."));
} else if ($dom->edit_domain($domain,$dns,$email,0,$ttl)) {
  $msg->raise("INFO", "dom", _("The domain %s has been changed."),$domain);
  $t = time();
// XXX: we assume the cron job is at every 5 minutes
  $msg->raise("INFO", "dom", _("The modifications will take effect at %s.  Server time is %s."), array(date('H:i:s', ($t-($t%300)+300)), date('H:i:s', $t)));
}
$dom->unlock();

include("dom_edit.php");
exit();
?>
