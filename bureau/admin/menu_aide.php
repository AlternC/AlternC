<?php
/*
 $Id: menu_aide.php,v 1.3 2004/05/19 14:23:06 benjamin Exp $
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
 Purpose of file:
 ----------------------------------------------------------------------
*/

global $lang;

$doc_lang = substr($lang, 0, 2); // ex: get "fr" only, not "fr_CA"
$doc_url  = 'http://alternc.org/wiki/Documentation/En/User';

switch ($doc_lang) {
  case 'fr':
    $doc_url = 'http://alternc.org/wiki/Documentation/Fr/Utilisateur';
    break;

  case 'es':
    $doc_url = 'http://alternc.org/wiki/Documentation/Es';
    break;
}

echo '<dd><a href="' . $doc_url . '" target="help">' . _("Online help") . '</a></dd>';
