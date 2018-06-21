<?php

/*
  ----------------------------------------------------------------------
  AlternC - Web Hosting System
  Copyright (C) 2002 by the AlternC Development Team.
  http://alternc.org/
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
  Purpose of file: DELETE an ssl certificate
  ----------------------------------------------------------------------
 */
require_once("../class/config.php");

$fields = array(
    "id" => array("post", "integer", ""),
    "delete" => array("post", "string", ""),
    "confirm" => array("post", "string", ""),
);
getFields($fields);

if (!isset($delete)) {
    require_once("ssl_list.php");
    exit();
}

$ok = $ssl->del_certificate($id);

if ($ok) $info=_("Your SSL Certificate has been deleted");

$error = $err->errstr();

require_once("ssl_list.php");

