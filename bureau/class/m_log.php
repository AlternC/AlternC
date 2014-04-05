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
  Purpose of file: Manage Log files for users 
  ----------------------------------------------------------------------
*/

/**
* Classe de gestion des erreurs apparaissant lors d'appels API.
*/
class m_log {

   /**
    * 
    */
  function m_log(){
  }

   /**
    * 
    * @global m_mem       $mem
    * @global m_err       $err
    * @param type $dir
    * @return type
    */
  function list_logs_directory($dir){
    global $cuid,$err;
    $err->log("log","list_logs_directory");

    $c=array();
    foreach( glob("${dir}/*log*") as $absfile) {
        $c[]=array("name"=>basename($absfile), 
                   "creation_date"=>date("F d Y H:i:s.", filectime($absfile)),
                   "filesize"=>filesize($absfile),
                   "downlink"=>"logs_download.php?file=".urlencode(basename($absfile)),
                  );
    }
    usort($c,"m_log::compare_logname");
    return $c;

  }//list_logs

  // Used by list_logs_directory to sort
  private function compare_logname($a, $b) {
    return strcmp($a['name'],$b['name']);
  }


   /**
    * 
    * @return string
    */
  function hook_menu() {
    $obj = array(
      'title'       => _("Logs"),
      'ico'         => 'images/logs.png',
      'link'        => 'logs_list.php',
      'pos'         => 130,
     ) ;

     return $obj;
  }

   /**
    * 
    * @global m_err       $err
    * @param type $dirs
    * @return type
    */
  function list_logs_directory_all($dirs){
    global $err;
    $err->log("log","get_logs_directory_all");
    $c=array();
    foreach($dirs as $dir=>$val){
      $c[$dir]=$this->list_logs_directory($val);
    }
    return $c;

  }
  
   /**
    * 
    * @global m_mem       $mem
    * @global    m_mem   $mem
    * @global m_err       $err
    * @return string
    */
  function get_logs_directory(){
    global $cuid,$mem,$err;
    $err->log("log","get_logs_directory");
    // Return an array to allow multiple directory in the future
    if(defined('ALTERNC_LOGS_ARCHIVE')){
      $c=array("dir"=>ALTERNC_LOGS_ARCHIVE."/".$cuid."-".$mem->user["login"]);
    }else{
      $c=array("dir"=>ALTERNC_LOGS."/".$cuid."-".$mem->user["login"]);
    }
    return $c;
  }
  
   /**
    * 
    * @global m_err       $err
    * @global    m_mem   $mem
    * @param type $file
    */
  function download_link($file){
    global $err,$mem;
    $err->log("log","download_link");
    header("Content-Disposition: attachment; filename=".$file); 
    header("Content-Type: application/force-download");
    header("Content-Transfer-Encoding: binary");
    $f=$this->get_logs_directory();
    $ff=$f['dir']."/".basename($file);
    set_time_limit(0);
    readfile($ff);
  }


} // end class

