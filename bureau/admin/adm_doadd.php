<?php
/*
 $Id: adm_doadd.php,v 1.8 2006/01/24 05:03:30 joe Exp $
 ----------------------------------------------------------------------
 AlternC - Web Hosting System
 Copyright (C) 2006 Le réseau Koumbit Inc.
 http://koumbit.org/
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
 Purpose of file: Create a new member
 ----------------------------------------------------------------------
*/
require_once("../class/config.php");

if (!$admin->enabled) {
	__("This page is restricted to authorized staff");
	exit;
}

if ($pass != $passconf) {
	$error = _("Passwords do not match");
	include("adm_add.php");
	exit();
}
if (!($u=$admin->add_mem($login, $pass, $nom, $prenom, $nmail, $canpass, $type, 0, $notes))) {
	$error=$err->errstr();
	include ("adm_add.php");
	exit;
} else {

  // Add here all what you want when an account is created !
  $mem->su($u);
  
  /*
   * 0 = pas d'hébergement dns, en effet, pas besoin vu que les
   * domaines *.koumbit.net sont bien sur le serveur
   *
   * 1 = noerase = empêche à l'utilisateur de modifier le dns ou de
   * supprimer le domaine
   *
   * 1 = force = ne tient pas compte du whois ou des droits de tld
   */

  if (($create_dom == 1)  && !is_null($create_dom_list)) {
      # make sure we don't have multiple dots there
      $dom->lock();
      $dom->add_domain($login.".".preg_replace("/^\.\.*/", "", $create_dom_list),1,1,1);
      $dom->unlock();
  }

  $ftp->add_ftp($login,"",$pass,"/");
  $mem->unsu();
 
 $error=_("The new member has been successfully created");

 include("adm_list.php");
 exit;
}
?>
