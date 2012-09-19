<?php
/*
 ----------------------------------------------------------------------
 AlternC - Web Hosting System
 Copyright (C) 2000-2012 by the AlternC Development Team.
 https://alternc.org/
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
 Purpose of file: listing of mail accounts for one domain.
 ----------------------------------------------------------------------
*/

require_once("../class/config_nochk.php");


  $res=$hooks->invoke("hook_admin_webmail");
if (($wr=variable_get("webmail_redirect")) && isset($res[$wr]) && $res[$wr]) {
  $url=$res[$wr];
} else {
  foreach($res as $r) if ($r!==false) { $url=$r; break; }
}
if ($url)  {
  header("Location: $url"); 
} else {
  header("Location: /nowebmail");
}

