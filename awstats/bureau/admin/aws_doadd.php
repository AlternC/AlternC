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
 Purpose of file:
 ----------------------------------------------------------------------
*/
require_once("../class/config.php");

$fields = array (
        "hostname"              => array ("post", "string", ""),
        "awsusers"              => array ("post", "array", ""),
        "hostaliases"           => array ("post", "array", ""),
);
getFields($fields);

if ($aws->check_host_available($hostname)) {
    $r=$aws->add_stats($hostname,$awsusers,$hostaliases,1);
    if (!$r) {
        include("aws_add.php");
        exit();
    } else {
        $msg->raise('Ok', "aws", _("The statistics has been successfully created"));
	    include("aws_list.php");
	    exit();
    }
}
else {
    include("aws_add.php");
    exit();
}

?>
