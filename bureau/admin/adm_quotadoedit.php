<?php
/*
 $Id: adm_quotadoedit.php,v 1.3 2004/10/24 20:09:21 anonymous Exp $
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
 Purpose of file: Edit a member's quotas
 ----------------------------------------------------------------------
*/
require_once("../class/config.php");

if (!$admin->enabled) {
	__("This page is restricted to authorized staff");
	exit();
}

if ($submit) {

  $mem->su($uid);
  $qlist=$quota->qlist();
  reset($qlist);
  
  while (list($key,$val)=each($qlist)) {
    $var="q_".$key;
    $quota->setquota($key,$$var);
  }
  $mem->unsu();
  $error=_("The quotas has been successfully edited");
  include("adm_list.php");
  exit;
  
}


?>
