<?php
/*
 $Id: local.php,v 1.6 2005/04/01 16:40:16 benjamin Exp $
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
 Original Author of file:
 Purpose of file: Variables spécifique au serveur (locales)
 ----------------------------------------------------------------------
*/
global $L_MYSQL_HOST,$L_MYSQL_LOGIN,$L_MYSQL_PWD,$L_MYSQL_DATABASE,$L_MYSQL_CLIENT,$L_SHOWVERSION,$L_VERSION,$L_FQDN,$L_HOSTING,$L_NS2,$L_NS1,$L_MX;

$L_MX="%%mx%%";
$L_NS1="%%ns1%%";
$L_NS2="%%ns2%%";
$L_HOSTING="%%hosting%%";
$L_FQDN="%%fqdn%%";

$L_MYSQL_HOST="%%dbhost%%";
$L_MYSQL_LOGIN="%%dbuser%%";
$L_MYSQL_PWD="%%dbpwd%%";
$L_MYSQL_DATABASE="%%dbname%%";
$L_MYSQL_CLIENT="%%dbclient%%";

$L_SHOWVERSION=1; /* Faut-il afficher la version d'AlternC dans le menu ? */
$L_VERSION="%%version%%"; /* Contient la version d'AlternC, ne pas modifier */

/* ATTENTION : AUCUNE CARACTERE APRES LE ? > SUIVANT !!! */

?>
