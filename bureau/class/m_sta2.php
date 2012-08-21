<?php
/*
 $Id: m_sta2.php,v 1.10 2005/12/18 09:51:32 benjamin Exp $
 ----------------------------------------------------------------------
 AlternC - Web Hosting System
 Copyright (C) 2002 by the AlternC Development Team.
 http://alternc.org/
 ----------------------------------------------------------------------
 Based on:
 Valentin Lacambre's web hosting softwares: http://altern.org/
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
 Purpose of file: Manage raw apache log availability for the users
 ----------------------------------------------------------------------
*/

/**
* This class manages raw Apache log file for the end users.
* 
* This class allow each AlternC's account to get its raw apache log 
* file put in its user space every day. <br>
* The file is stored in the user space and will grow from time to time...
* 
* @copyright    AlternC's Team 2002-2005 http://alternc.org/
* 
*/
class m_sta2 {

  /* ----------------------------------------------------------------- */
  /**
   * Constructor, dummy
   */
  function m_sta2() {
  }

  /* ----------------------------------------------------------------- */
  /** Hook function that returns the quota names for this class
   * 
   * @return string the quota names for this class
   */
  function alternc_quota_names() {
    return "sta2";
  } 


  /* ----------------------------------------------------------------- */
  /** Returns the list of domains and/or subdomains for this account
   * 
   * @return array returns an array with all the domains / subdomains for this account.
   */
  function host_list() {
    global $db,$err,$cuid;
    $r=array();
    $db->query("SELECT domaine,sub FROM sub_domaines WHERE compte='$cuid' ORDER BY domaine,sub;");
    while ($db->next_record()) {
      if ($db->f("sub")) {
	$r[]=$db->f("sub").".".$db->f("domaine");
      } else {
	$r[]=$db->f("domaine");
      }
    }
    return $r;
  }

  /* ----------------------------------------------------------------- */
  /** Draw option html tags of ths allowed domains / subdomains for the account.
   * 
   * @param $current string The current selected value in the list
   */
  function select_host_list($current) {
    $r=$this->host_list();
    reset($r);
    while (list($key,$val)=each($r)) {
      if ($current==$val) $c=" selected=\"selected\""; else $c="";
      echo "<option$c>$val</option>";
    }
    return true;
  }

  /* ----------------------------------------------------------------- */
  /** Hook function that delete a user's raw stats.
   */
  function alternc_del_member() {
    global $db,$err,$cuid;
    $err->log("sta2","del_member");
    $db->query("DELETE FROM stats2 WHERE mid='$cuid';");
    return true;
  }

  /* ----------------------------------------------------------------- */
  /** Hook function that delete a user's domain, called by m_dom.
   * @param string $dom is the domain that is to be deleted.
   */
  function alternc_del_domain($dom) {
    global $db,$err,$cuid;
    $err->log("sta2","del_dom",$dom);
    // Suppression des stats apache brutes : 
    $db->query("SELECT * FROM stats2 WHERE mid='$cuid' AND hostname like '%$dom'");
    $cnt=0;
    $t=array();
    while ($db->next_record()) {
      $cnt++;
      $t[]=$db->f("hostname");
    }
    // on détruit les jeux de stats associés au préfixe correspondant :
    for($i=0;$i<$cnt;$i++) {
      $db->query("DELETE FROM stats2 WHERE mid='$cuid' AND hostname='".$t[$i]."';");
    }
    return true;
  }

  /* ----------------------------------------------------------------- */
  /** Returns an array with the user's raw stat list
   * The returned array is as follow : 
   * $r[0-n]["id"] = Id of the raw stat set.
   * $r[0-n]["hostname"]= Domain
   * $r[0-n]["folder"]= Destination's folder (in the user space)
   * 
   * @return array Returns the array or FALSE if an error occured.
   */
  function get_list_raw() {
    global $db,$err,$cuid;
    $err->log("sta2","get_list_raw");
    $r=array();
    $db->query("SELECT id, hostname, folder FROM stats2 WHERE mid='$cuid' ORDER BY hostname;");
    if ($db->num_rows()) {
      while ($db->next_record()) {
	// We skip /var/alternc/html/u/user
        // FIXME: utiliser ALTERNC_HTML au lieu de /var/alternc/html/
	preg_match("/^\/var\/alternc\/html\/.\/[^\/]*\/(.*)/", $db->f("folder"),$match);
	$r[]=array(
		   "id"=>$db->f("id"),
		   "hostname"=>$db->f("hostname"),
		   "folder"=>$match[1]
		   );
      }
      return $r;
    } else {
      $err->raise("sta2",2);
      return false;
    }
  }

  /* ----------------------------------------------------------------- */
  /** Get the details of a raw statistic set.
   * 
   * This function returns the details of a raw statistic set (raw logs)
   * The returned value is an associative array as follow : 
   * $ret["id"] = raw stat id.
   * $ret["hostname"] = the domain we get the raw log.
   * $ret["folder"] = the destination folder for the logs (inside the user space)
   * @param $id string The raw stat number we want details of.
   * @return array returns an array with the raw log parameters or FALSE if an error occured.
   */
  function get_stats_details_raw($id) {
    global $db,$err,$cuid;
    $err->log("sta2","get_stats_details_raw",$id);
    $r=array();
    $db->query("SELECT id, hostname, folder FROM stats2 WHERE mid='$cuid' AND id='$id';");
    if ($db->num_rows()) {
      $db->next_record();
      // We skip /var/alternc/html/u/user
      // FIXME: utiliser ALTERNC_HTML au lieu de /var/alternc/html/
      preg_match("/^\/var\/alternc\/html\/.\/[^\/]*\/(.*)/", $db->f("folder"),$match);
      return array(
		   "id"=>$db->f("id"),
		   "hostname"=> $db->f("hostname"),
		   "folder"=>$match[1]
		   );
    } else {
      $err->raise("sta2",3);
      return false;
    }
  }

  /* ----------------------------------------------------------------- */
  /** Edit a raw statistic set.
   * 
   * This function edit a raw statistic set.
   * $folder is the new destination folder inside the user space where the log
   * file will be put.
   * @param $id integer The raw statistic number we are changing
   * @param $folder string new destination folder
   * @return boolean TRUE if the set has been changed, FALSE if an error occured.
   */
  function put_stats_details_raw($id,$folder) {
    global $db,$err,$bro,$mem,$cuid;
    $err->log("sta2","put_stats_details_raw",$id);
    $db->query("SELECT count(*) AS cnt FROM stats2 WHERE id='$id' and mid='$cuid';");
    $db->next_record();
    if (!$db->f("cnt")) {
      $err->raise("sta2",3);
      return false;
    }
    // TODO : replace with ,1 on convertabsolute call, and delete "/Var/alternc.../" at the query. ???
    $folder=$bro->convertabsolute($folder);
    if (substr($folder,0,1)=="/") {
      $folder=substr($folder,1);
    }
    $db->query("UPDATE stats2 SET folder='".getuserpath()."/$folder', mid='$cuid' WHERE id='$id';");
    return true;
  }

  /* ----------------------------------------------------------------- */
  /** Delete a raw statistic set
   * 
   * This function erase the raw statistic set pointed to by $id.
   * The raw log files that may be present in the folder will NOT be deleted.
   * @param $id integer is the set that has to be deleted.
   * @return boolean TRUE if the raw stat has been deleted, FALSE if an error occured.
   */
  function delete_stats_raw($id) {
    global $db,$err,$cuid;
    $err->log("sta2","delete_stats_raw",$id);
    $db->query("SELECT hostname FROM stats2 WHERE id='$id' and mid='$cuid';");
    if (!$db->num_rows()) {
      $err->raise("sta2",3);
      return false;
    }
    $db->next_record();
    $db->query("DELETE FROM stats2 WHERE id='$id'");
    return true;
  }

  /* ----------------------------------------------------------------- */
  /** Create a new raw statistic set (raw log)
   * This function create a new raw log set for the current user.
   * The raw statistics allow any user to get its raw apache log put daily in 
   * one of its folders in its user space.
   * @param $hostname string this is the domain name (hosted by the current user) 
   *  for which we want raw logs
   * @param $dir string this is the folder where we will put the raw log files.
   * @return boolean TRUE if the set has been created, or FALSE if an error occured.
   */
  function add_stats_raw($hostname,$dir) {
    global $db,$err,$quota,$bro,$mem,$cuid;
    $err->log("sta2","add_stats_raw",$hostname);
    // TODO : utiliser le second param de convertabsolute pour simplification.
    $dir=$bro->convertabsolute($dir);
    if (substr($dir,0,1)=="/") {
      $dir=substr($dir,1);
    }
    if ($quota->cancreate("sta2")) {
      $db->query("INSERT INTO stats2 (hostname,folder,mid) VALUES ('$hostname','".getuserpath()."/$dir','$cuid')");
      return true;
    } else {
      $err->raise("sta2",1);
      return false;
    }
  }

  /* ----------------------------------------------------------------- */
  /** Quota computing Hook function
   * This is the quota computing hook function for sta2. It computes the
   * used quota of raw stats for the current user.
   * @param $name string name of the quota
   * @return integer the number of service used or false if an error occured
   * @access private
   */
  function alternc_get_quota($name) {
    global $db,$err,$cuid;
    if ($name=="sta2") {
      $err->log("sta2","get_quota");
      $db->query("SELECT COUNT(*) AS cnt FROM stats2 WHERE mid='$cuid'");
      $db->next_record();
      return $db->f("cnt");
    } else return false;
  }


  /* ----------------------------------------------------------------- */
  /**
   * Exporte toutes les informations states brutes du compte.
   * @access private
   * EXPERIMENTAL 'sid' function ;) 
   */
  function alternc_export_conf() {
    global $db,$err;
    $err->log("sta2","export");
    $f=$this->get_list_raw();
    $str="<sta2>\n";
    foreach ($f as $d) {
      $str.="  <stats>\n";
      $str.="    <hostname>".($s[hostname])."</hostname>\n";
      $str.="    <folder>".($s[folder])."</folder>\n";
      $str.="  </stats>\n";
    }
    $str.="</sta2>\n";
    return $str;
  }


} /* CLASSE m_sta2 */

?>
