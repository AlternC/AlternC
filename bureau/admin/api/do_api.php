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

/* We will handle auth ourselves */
define('NOCHECK', true);

require_once("../../class/config.php");

class AC_Rest_Api {

    /*
     * Constructor
     */
    function AC_Rest_Api($method, $request, $accept) {
        $this->_method = $method;
        $this->_request = $request;
        $this->_out_format = $this->_choose_output_format($accept);
    }

    /*
     * Choose which output format to use
     *
     * @param accept string An HTTP accept string
     */
    function _choose_output_format($accept) {
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

    function send_output() {
        header("Content-Type: {$this->_out_format}; charset=UTF-8");
        $out_data = array ('text' => "This is the output from REST request");
        switch ($this->_out_format) {
            case 'text/html':
                echo "<html><body><xmp>\n";
                print_r($out_data);
                echo "</xmp></body></html>\n";
                break;
            case 'text/plain':
                print_r($out_data);
                break;
            case 'application/json':
                echo json_encode($out_data);
                break;
        }
    }
}

# parse request
$fields = array (
    "SCRIPT_URL"        => array ("server", "string", ""),
    "HTTP_ACCEPT"       => array ("server", "string", "application/json"),
    "REQUEST_METHOD"    => array ("server", "string", "GET"),
);
$qvars = getFields($fields);
#print_r($qvars);

$rest_req = new AC_Rest_Api($qvars['REQUEST_METHOD'], array(), $qvars['HTTP_ACCEPT']);

$rest_req->send_output();

# check auth, if required

# do something with AlternC api class

# return the response

#phpinfo();

?>
