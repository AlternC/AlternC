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

define('ALTERNC_API_LOCATION', '/api');

/* Class local to this function, may it be useful to externalize ? */

class AC_Rest_Api {

    /* -------------------------------------------------------------- */
    /**
     * Constructor
     */
    function AC_Rest_Api($method, $request, $accept) {
        $this->_prepare_ok = true;
        $this->_did_execute = false;
        $this->_method = $method;
        $this->_request = $request;
        $this->_out_format = $this->_choose_output_format($accept);

        $this->_err_code = 200;
        $this->_err_text = 'Ok';
        $this->_output = '';

        if (! $this->_parse_request($request['rest_path'])) {
            $this->_prepare_ok = false;
        }
    }

    /* -------------------------------------------------------------- */
    /**
     * Execute the request
     */
    function execute() {
        global $api;
        $this->_did_execute = true;
        switch ($this->_request_object) {
            case 'raw':
                $api_ret = $api->call_raw($this->_request_parts['class'], $this->_request_parts['method']);
                $this->_output = $api_ret;
                break;
        }
    }

    /* -------------------------------------------------------------- */
    /**
     * Tell if we are ready to execute
     *
     * @return boolean
     */
    function prepare_ok() {
        return $this->_prepare_ok;
    }

    /* -------------------------------------------------------------- */
    /**
     * Do the output to http client
     */
    function send_output() {
        if ((! $this->_did_execute) and ($this->_err_code == 200)) {
            # Strange behaviour detected !
            $this->_err_code = 418;
            $this->_err_text = 'I\'m a tea pot';
        }
        header("HTTP/1.1 {$this->_err_code} {$this->_err_text}");
        header("Content-Type: {$this->_out_format}; charset=UTF-8");
        if ($this->_err_code >= 400) {  # Error condition
            $out_data = array ('Error' => array ('Code' => $this->_err_code, 'Text' => $this->_err_text));
        } else {
            $out_data = $this->_output;
        }
        switch ($this->_out_format) {
            case 'text/html':
                $output_text = '';
                $output_text .= "<html><body><xmp>\n";
                $output_text .= print_r($out_data, true);
                $output_text .= "</xmp></body></html>\n";
                break;
            case 'text/plain':
                $output_text = print_r($out_data, true);
                break;
            case 'application/json':
                $output_text = json_encode($out_data);
                break;
        }
        header("Content-Length: ". strlen($output_text));

        echo $output_text;
    }

    /* === Private methods ------------------------------------------ */

    /* -------------------------------------------------------------- */
    /**
     * Choose which output format to use
     *
     * @param $accept string An HTTP accept string
     * @return string The chosen output format
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

    /* -------------------------------------------------------------- */
    /**
     * Parse the path of request
     *
     * @param $path string Path containing the ref of the object
     * @return array Parsed result
     */
    function _parse_request($path) {
        $parts = split('/', $path);
#        print_r($parts);
        $req_obj = $parts[1];   # « 0 » is empty
        switch ($req_obj) {
            case 'raw':
                if (! in_array ($this->_method, array ('GET', 'POST'))) {
                    $this->_err_code = 405;
                    $this->_err_text = sprintf(_('Method %s is not allowed for raw call'), $this->_method);
                    return false;
                }
                $this->_request_object = 'raw';
                $this->_request_parts = array ('class' => $parts[2], 'method' => $parts[3]);
                break;
            default:
                $this->_err_code = 404;
                $this->_err_text = sprintf(_('Unknown object type %s'), $req_obj);
                return false;
                break;
        }
        return true;
    }

}

# parse request
$fields = array (
    "SCRIPT_URL"        => array ("server", "string", ""),
    "HTTP_ACCEPT"       => array ("server", "string", "application/json"),
    "REQUEST_METHOD"    => array ("server", "string", "GET"),
    "class_name"        => array ("get", "string", ""),
    "method_name"       => array ("get", "string", ""),
    "arg1"       => array ("get", "string", ""),
);
$qvars = getFields($fields);
#print_r($qvars);

$request = array();
$request['method'] = $qvars['REQUEST_METHOD'];
$request['rest_path'] = $qvars['SCRIPT_URL'];
{   # $location_len is disposable
    $location_len = strlen(ALTERNC_API_LOCATION);
    if (substr($request['rest_path'], 0, $location_len) != ALTERNC_API_LOCATION) {
        die(_("This should never happen."));
    }
    $request['rest_path'] = substr($request['rest_path'], $location_len);
}

$rest_req = new AC_Rest_Api($qvars['REQUEST_METHOD'], $request, $qvars['HTTP_ACCEPT']);

# check auth, if required

# do something with AlternC api class
if ($rest_req->prepare_ok()) {
    $rest_req->execute();
}

# return the response
$rest_req->send_output();

#phpinfo();
flush();

?>
