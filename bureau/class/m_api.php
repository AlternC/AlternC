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
 Purpose of file: API dispatcher
 ----------------------------------------------------------------------
*/

/**
 * Class to handle REST API requests and "AlternC shell" alike
 *
 * Copyleft {@link https://alternc.net/ AlternC Team}
 *
 */

class m_api {
    /* ---------------------------------------------------------------- */
    /**
     * Constructor
     */
    function m_api () {
        global $db;
        # nothing yet
    }

    /* ---------------------------------------------------------------- */
    /**
     * Call an arbitrary method from any AlternC class
     * Handle with care, both ends are sharpened
     *
     * @param $cl string Name of the class
     * @param $meth string Name of the method
     * @param $args array Array of arguments for the method ; optional
     * @return array Contains a return code stating if anything went wrong (0 is ok, -1 is bad), and the return value of the method
     */
    function call_raw($cl, $meth, $args = Null) {
        global $db,$err,$cuid;
        $err->log('api','$cl->$meth');

        $err_code = 0;
        $err_text = '';
        $ret_value = Null;

        # minimal sanity check
        if ($err_code==0) if (!is_string($cl) or !is_string($meth)) {
            $err_code = -1;
            $err_text = sprintf(_('Either class or method name is not a string'));
        }
        if ($err_code==0) if (($cl=='') or ($meth=='')) {
            $err_code = -1;
            $err_text = sprintf(_('Either class or method name is empty'));
        }
        if ($err_code==0) if (($args!=Null) and (!is_array($args))) {
            $err_code = -1;
            $err_text = sprintf(_('Arguments received is not an array'));
        }
        if ($err_code==0) if ($cl=='api') { # Yes, somebody wil try it...
            $err_code = -1;
            $err_text = sprintf(_('I won\'t let you call API through API'));
        }

        if ($err_code==0) if (! array_key_exists($cl, $GLOBALS)) {
            $err_code = -1;
            $err_text = sprintf(_('Instance of class %s dosen\'t exist'), $cl);
        } else {
            $class_obj = $GLOBALS[$cl];
        }
        if ($err_code==0) if (! is_object($class_obj)) {
            $err_code = -1;
            $err_text = sprintf(_('The class name "%s" dosen\'t look like an object instance'), $cl);
        }
        if ($err_code==0) if (substr(get_class($class_obj), 0, 2) != 'm_') {
            $err_code = -1;
            $err_text = sprintf(_('The class name "%s" dosen\'t look like an AlternC class'), $cl);
        }
        if ($err_code==0) if (! is_callable( array($class_obj, $meth) ) ) {
            $err_code = -1;
            $err_text = sprintf(_('Method %s of class %s is not callable'), $meth, $cl);
        }
        if ($err_code==0) if (! is_callable( array($class_obj, $meth) ) ) {
            $err_code = -1;
            $err_text = sprintf(_('Method %s of class %s is not callable'), $meth, $cl);
        }

        # checks went ok
        if ($err_code == 0) {
            set_error_handler(array("m_api", "_error_intercept"));
            try {
                if ($args == Null) {
                    $ret_value = call_user_func( array($class_obj, $meth) );
                } else {
                    $ret_value = call_user_func_array( array($class_obj, $meth), $args );
                }
            } catch (Exception $e) {
                $err_code = -2;
                $err_text = sprintf (_("Execution error: %s"), $e->getMessage());
                $ret_value = Null;  # nothing interesting should be there
            }
            restore_error_handler();
        }

        $ret = array (
            'err_code' => $err_code,
            'err_text' => $err_text,
            'ret_value' => $ret_value,
        );
        return $ret;
    }

    /* === Private methods -------------------------------------------- */

    /* ---------------------------------------------------------------- */
    /**
     * Custom error handler that throw an exception
     * Parameters are those defined in PHP set_error_handler
     *
     * @param $code integer Contains the level of the error raised,
     * @param $string string Contains the error message
     * @param $file string Contains the filename that the error was raised in ; optional
     * @param $line integer Contains the line number the error was raised at ; optional
     * @param $context array Points to the active symbol table at the point the error occurred ; optional
     */
    function _error_intercept($code, $string, $file, $line, $context) {
        // ignore supressed errors
        if (error_reporting() == 0) return;
        throw new Exception(sprintf("Code %s: %s in %s line %s)", $code, $string, $file, $line),$code);
    }


} /* -- API Class -- */

?>
