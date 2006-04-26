<?php
/*
 $Id: lst_downsub.php,v 1.1.1.1 2003/03/26 17:41:29 root Exp $
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
 Original Author of file: Louis Sylvain
 Purpose of file: download the subscribers list of a mailing-list 
 ----------------------------------------------------------------------
*/
require_once("../class/config.php");


// on récupère le nom de la liste et on la met au format qui est utilisée dans la table
$r=$sympa->get_ml($id);
if ($r["premier"]==1) {
	$temp=explode("@", $r["list"]);
	$liste=$temp[0];
} else {
	$liste=str_replace("@","_",$r["list"]);
}

header("Content-Type: text/plain");
// header("Content-Type: text/html"); 
header("Content-disposition: Attachment;filename=\"liste_".$liste.".txt\"");
/*
header("Pragma: no-cache");
header("Expires: 0"); */

$db->query("SELECT user_subscriber from subscriber_table where list_subscriber='$liste';");
while ($db->next_record()) {
   echo $db->Record["user_subscriber"]."\r\n";
}
?>