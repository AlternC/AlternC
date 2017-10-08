<?php

/*
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
*/

/**
 * Handle messages (error, warning, info, ok) appearing in API calls. 
 *
 * <p>This class handles messages appearing while calling API functions of AlternC
 * Those messages are stored as a number (class-id) and a message
 * localized messages are available</p>
 * <p>This class also handle inserting those messages into the logging 
 * system in /var/log/alternc/bureau.log
 * </p>
 * 
 * @copyright AlternC-Team 2000-2017 https://alternc.com/
 */
class m_messages {

    /** Contains the messages and their ID */
    var $arrMessages = array();

    var $logfile = "/var/log/alternc/bureau.log";

    /** List of possible message level */
    var $ARRLEVEL = array("ERROR", "ALERT", "INFO");

    /** CSS classes for each level */
    var $ARRCSS = array(
        // emergency critical warning notice debug : currently not used (from PSR-3)
        "ERROR" => "alert-danger",
        "ALERT" => "alert-warning",
        "INFO" => "alert-success",
    );

    public function __construct() {
        $this->init_msgs();
    }


    /**
     * Record a message, insert it into the logfile.
     * 
     * This function records a message, add it to the logfile,
     * and make it available for the web panel to print it later.
     *
     * @param string $cat The category of the msg array to work with
     * @param integer $clsid Which class raises this message
     * @param mixed $msg The message
     * @param array $param Non-mandatory array of string parameter for this message
     * @return boolean TRUE if the message got recorded, FALSE if not.
     *
     */
    function raise($level = "ERROR", $clsid, $msg, $param = "") {
        $arrInfos  = array();

        $level = strtoupper($level);
        if (! in_array($level, $this->ARRLEVEL)) {
            throw new Exception('Missing or unknown level in a raise() call');
        }

        $arrInfos['clsid'] = $clsid;
        $arrInfos['msg'] = $msg;
        $arrInfos['param'] = is_array($param)?$param:(empty($param)?"":array($param));

        $this->arrMessages[$level][] = $arrInfos;

        $this->logAlternC($level,$arrInfos);
        return true;
    }
    

    /**
     * Reset the stored messages array
     */
    function init_msgs() {
        //	$me=debug_backtrace();  $this->log("messages", "init_msgs from ".$me[1]["class"].".".$me[1]["function"]);	
        foreach ($this->ARRLEVEL as $v) {
            $this->arrMessages[$v] = array();
        }
    }


    /**
     * Tell if there are stored messages for a specific level
     * or for all levels (if level is empty)
     *
     * @param string $level The level of the msg array to work with (if empty or not set, use all levels)
     * @return boolean TRUE if there is/are msg recorded.
     *
     */
    function has_msgs($level="") {
        $level = strtoupper($level);
        if (in_array($level, $this->ARRLEVEL)) {
            return (count($this->arrMessages[$level]) > 0);
        } else {
            foreach ($this->arrMessages as $v) {
                if (count($v) > 0)
                    return true;
            }
            return false;
        }
    }


    /**
     * Return a string of concateneted messages of all recorded messages
     * or only the last message
     *
     * @param string $level The level of the msg array to work with
     * @param boolean $all show all the messages or only the last one
     * 
     * @access private 
     * @return string Message.
     *
     */
    function msg_str($level = "ERROR", $all = true) {
        $str = "";

        $level = strtoupper($level);
        if (! in_array($level, $this->ARRLEVEL)) {
            throw new Exception('Missing or unknown level in a raise() call');
        }

        if (! $this->has_msgs($level))
            return "";

        if ($all) {
            foreach ($this->arrMessages[$level] as $k => $arrMsg) {
                $args = $arrMsg['param'];

                if (is_array($args) && count($args) > 0) {
                    array_unshift($args, $arrMsg['msg']);
                    $str .= call_user_func_array("sprintf", $args) . "\n";
                } else
                    $str .= $arrMsg['msg'] . "\n";
            }

        } else {
            $i = count($this->arrMessages[$level]) - 1;
            if ($i > 0) {
                $arr_msg=$this->arrMessages[$level][$i];
                $args = $arr_msg['param'];
                if (is_array($args) && count($args) > 0) {
                    array_unshift($args, $arr_msg['msg']);
                    $str = call_user_func_array("sprintf", $args);
                } else
                    $str = $arr_msg['msgId'];
            }
        }

        return $str;
    }


    /**
     * Return a message in HTML form with associated CSS
     *
     * @param string $level The level of the msg array to work with
     * @param string $sep The separator used to concatenate msgs
     * @param boolean $all show all the messages or only the last one
     *
     * @return string HTML message
     */
    function msg_html($level = "ERROR", $all = true) {
        $level = strtoupper($level);
        if (! in_array($level, $this->ARRLEVEL)) {
            throw new Exception('Missing or unknown level in a raise() call');
        }

        if (count($this->arrMessages[$level]) == 0)
            return "";

        $str = $this->msg_str($level, $all);
        $str = "<div class='alert " . $this->ARRCSS[$level] . "'>" . nl2br($str) . "</div>";

        return $str;
    }


    /**
     * Return all the messages of all levels in HTML form with associated CSS
     *
     * @param string $sep The separator used to concatenate msgs
     * @param boolean $all show all the messages or only the last one
     *
     * @return string HTML message
     */
    function msg_html_all($all = true, $init = false) {
        $msg="";

        $msg.=$this->msg_html("ERROR", $all);
        $msg.=$this->msg_html("INFO", $all);
        $msg.=$this->msg_html("ALERT", $all);

        if ($init)
            $this->init_msgs();

        return $msg;
    }


    /**
     * Log a message into /var/log/alternc/bureau.log
     * 
     * This function logs the last message in the /var/log/alternc folder
     * allowing sysadmins to know what's happened.
     * automatically called by raise()
     * @param string $level the error level
     * @param array $arrMsg the array containing message info.
     * @access private
     */
    function logAlternC($level = "ERROR", $arrMsg) {
        global $mem;
        
        $args = $arrMsg['param'];
        
        if (is_array($args) && count($args) > 0) {
            array_unshift($args, $arrMsg['msg']);
            $str = call_user_func_array("sprintf", $args);
        } else
            $str = $arrMsg['msg'];

        @file_put_contents(
            $this->logfile, 
            date("d/m/Y H:i:s") . " - " . get_remote_ip() . " - $level - " . $mem->user["login"] . " - " . $str . "\n",
            FILE_APPEND
        );
    }


    /**
     * Log an API function call into /var/log/alternc/bureau.log
     *
     * This function logs in /var/log/alternc an API function call of AlternC
     *
     * @param integer $clsid Number of the class doing the call
     * @param string $function Name of the called function
     * @param string $param non-mandatory parameters of the API call
     * @return boolean TRUE if the log where successfull, FALSE if not
     *
     */
    function log($clsid, $function, $param = "") {
        global $mem;
        return @file_put_contents(
            $this->logfile, 
            date("d/m/Y H:i:s") . " - " . get_remote_ip() . " - CALL - " . $mem->user["login"] . " - $clsid - $function - $param\n", 
            FILE_APPEND
        );
    }

} /* Class m_messages */
