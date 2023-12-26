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
  Purpose of file: Create / Import an SSL Certificate
  ----------------------------------------------------------------------
 */
require_once("../class/config.php");

if (!isset($is_include)) {
    $fields = array(
        "key" => array("post", "string", ""),
        "crt" => array("post", "string", ""),
        "chain" => array("post", "string", ""),
    );
    getFields($fields);
}

if (!$key && !$crt) {
    $error = _("Please enter an ssl key and a certificate");
    require_once("ssl_new.php");
    exit();
}

$id = $ssl->import_cert($key, $crt, $chain);
$error = $err->errstr();
if ($error) {
    require_once("ssl_new.php");
    exit();
}

header("Location: /ssl_view.php?id=" . $id);


