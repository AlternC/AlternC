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
 * This class shows error or access logs of web server to the web panel
 * 
 * @copyright AlternC-Team 2000-2017 https://alternc.com/
 */
class m_log {

    /**
     * List all logs files in a directory 
     */
    function list_logs_directory($dir) {
        global $cuid, $msg;
        $msg->debug("log", "list_logs_directory");

        $c = array();
        foreach (glob("${dir}/*log*") as $absfile) {
            $c[] = array("name" => basename($absfile),
            "creation_date" => date("F d Y H:i:s", filectime($absfile)),
            "mtime" => filemtime($absfile),
            "filesize" => filesize($absfile),
            "downlink" => urlencode(basename($absfile)),
            );
        }
        usort($c, "m_log::compare_logtime");
        return $c;
    }


    /**
     * Used by list_logs_directory to sort
     */
    private function compare_logname($a, $b) {
        return strcmp($a['name'], $b['name']);
    }


    /**
     * Used by list_logs_directory to sort
     */
    private function compare_logtime($a, $b) {
        return $b['mtime'] - $a['mtime'];
    }


    /**
     * hook called by the menu class
     * to add menu to the left panel
     */
    function hook_menu() {
        $obj = array(
            'title' => __("Logs", "alternc", true),
            'link' => 'logs_list.php',
            'pos' => 130,
        );

        return $obj;
    }

    /**
     * list all log files in all log directories
     */
    function list_logs_directory_all($dirs) {
        global $msg;
        $msg->debug("log", "get_logs_directory_all");
        $c = array();
        foreach ($dirs as $dir => $val) {
            $c[$dir] = $this->list_logs_directory($val);
        }
        return $c;
    }


    function get_logs_directory() {
        global $cuid, $mem, $msg;
        $msg->debug("log", "get_logs_directory");
        // Return an array to allow multiple directory in the future
        if (defined('ALTERNC_LOGS_ARCHIVE')) {
            $c = array("dir" => ALTERNC_LOGS_ARCHIVE . "/" . $cuid . "-" . $mem->user["login"]);
        } else {
            $c = array("dir" => ALTERNC_LOGS . "/" . $cuid . "-" . $mem->user["login"]);
        }
        return $c;
    }


    /**
     * download a log file
     */
    function download_link($file) {
        global $msg;
        $msg->log("log", "download_link");
        header("Content-Disposition: attachment; filename=" . $file . "");
        header("Content-Type: application/force-download");
        header("Content-Transfer-Encoding: binary");
        $f = $this->get_logs_directory();
        $ff = $f['dir'] . "/" . basename($file);
        set_time_limit(0);
        readfile($ff);
    }

    /** 
     * show the last lines of a file
     */
    function tail($file, $lines = 20) {
        global $msg;
        $msg->debug("log", "tail");
        $lines = intval($lines);
        if ($lines <= 0) {
            $lines = 20;
        }
        $f = $this->get_logs_directory();
        $ff = $f['dir'] . "/" . basename($file);
        $out=array();
        exec("tail -" . $lines . " " . escapeshellarg($ff), $out);
        return implode("\n", $out);
    }

} /* class m_log */
