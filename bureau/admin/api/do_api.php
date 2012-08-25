<?php
/**
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
 Purpose of file: Entry point for REST API
 ----------------------------------------------------------------------
*/

require_once("../../class/config.php");

class AC_Rest_Api {
    /*
     * Choose which output format to use
     *
     * @param accept string An HTTP accept string
     */
    function choose_output_format($accept) {
        $accepts = split(',', $accept);
        $found = false;
        foreach ($accepts as $acc) {
            $acc_split = split(';', $acc);
            $value = $acc_split[0];
            if (in_array($value, array ('text/html', 'text/plain', 'application/json'))) {
                $output = $value;
                $found = true;
            }
            if ($found) { break; }
        }
        if (! $found) {
            $output = 'application/json';
        }
        return $output;
    }
}

# parse request
$fields = array (
    "SCRIPT_URL"        => array ("server", "string", ""),
    "HTTP_ACCEPT"       => array ("server", "string", "application/json"),
);
$qvars = getFields($fields);
print_r($qvars);
$outfmt = AC_Rest_Api::choose_output_format($qvars['HTTP_ACCEPT']);
print $outfmt;

# check auth, if required

# do something with AlternC api class

# return the response

?>
