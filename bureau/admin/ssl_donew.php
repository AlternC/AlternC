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
        "fqdn" => array("request", "string", ""),
        "fqdnt" => array("request", "string", ""),
    );
    getFields($fields);
}

if (!$fqdn && !$fqdnt) {
    $error = __("Please enter a proper domain name", "alternc", true);
    require_once("ssl_new.php");
    exit();
}

if ($fqdnt)
    $d = $fqdnt;
if ($fqdn)
    $d = $fqdn;

$id = $ssl->new_csr($d);
$error = $err->errstr();
if ($error) {
    require_once("ssl_new.php");
    exit();
}

$is_include = true;
require_once("ssl_view.php");

