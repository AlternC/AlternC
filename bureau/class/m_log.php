<?php
/*
 $Id: m_log.php,v 1.4 2004/05/19 14:23:06 benjamin Exp $
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
 Original Author of file: Steven Lerider
 ----------------------------------------------------------------------
*/
/**
* Classe de gestion des erreurs apparaissant lors d'appels API.
*
* <p>Cette classe gère les logs utilisasteurs 
* </p>
* Copyleft {@link http://alternc.net/ AlternC Team}
* 
* @copyright    AlternC-Team 2002-11-01 http://alternc.net/
*/


class m_log {

  function m_log(){
  }

  function list_logs_directory($dir){
    global $cuid,$err;
    $err->log("log","list_logs_directory");

    $dir2=$dir;
    if ($dir = @opendir($dir)) {
      while (($file = readdir($dir)) !== false) {
        if ($file!="." && $file!=".." && realpath($dir2 . "/" . $file) == $dir2 . "/" . $file){
          $absfile=$dir2."/".$file;
          $c[]=array("name"=>$file, 
                     "creation_date"=>date("F d Y H:i:s.", filectime($absfile)),
                     "filesize"=>filesize($absfile),
                     "downlink"=>"logs_download.php?file=".urlencode($file),
                    );
        }    
      }
      closedir($dir);
    }
    usort($c,"compare_logname");
    return $c;

  }//list_logs

  function list_logs_directory_all($dirs){
    global $err;
    $err->log("log","get_logs_directory_all");
    $c=array();
    foreach($dirs as $dir=>$val){
      $c[$dir]=$this->list_logs_directory($val);
    }
    return $c;

  }
  
  function get_logs_directory(){
    global $cuid,$db,$err;
    $err->log("log","get_logs_directory");
    
    $db->query("select login from membres where uid=$cuid ;");
    if ($db->num_rows()==0) {
      $err->raise("log",1);
      return false;
    }
    $db->next_record();
    $c=array("dir"=>"/var/alternc/logs/".$cuid."-".$db->f("login"));
    return $c;
  }
  
  function download_link($file){
    global $err,$mem;
    $err->log("log","download_link");
    header("Content-Disposition: attachment; filename=".$mem->user["login"].".zip");
    header("Content-Type: application/force-download");
    header("Content-Transfer-Encoding: binary");
    $f=$this->get_logs_directory();
    $ff=$f['dir']."/".basename($file);
    set_time_limit(0);
    readfile($ff);
  }


} // end class

