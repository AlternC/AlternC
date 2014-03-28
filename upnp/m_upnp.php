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
  Purpose of file: Manage UPnP ports forwarding from router
  ----------------------------------------------------------------------
*/

/**
* This class handle UPnP forwarding from a IGD/UPnP compliant router
* you need this only when you are behind a IGD-compliant router
* each class may exports a hook that defined named port/protocol to
* forward to the local IP address of the server.
* this class manage the upnp table 
* and its configuration from admin control panel
*/
class m_upnp {


  /* ----------------------------------------------------------------- */
  /** get the list of current upnp forwards and their status
   * @return array the attributes of all port-forwards
   */
  function get_forward_list() {
    global $db,$err;
    $err->log("upnp","get_forward_list");
    $db->query("SELECT * FROM upnp");
    $res=array();
    while ($db->next_record()) {
      $res[]=$db->Record;
    }
    return $res;
  }


  /* ----------------------------------------------------------------- */
  /** enable a upnp port in the upnp table
   * @param integer the id of the port to enable
   * @return boolean TRUE if the port has been properly forwarded
   * FALSE if an error occurred
   */
  function enable_port($id) {
    global $db,$err;
    $id=intval($id);
    $err->log("upnp","enable_port($id)");
    $db->query("SELECT enabled FROM upnp WHERE id=$id;");
    if (!$db->next_record()) {
      $err->raise("upnp",_("The required port is not currently defined"));
      return false;
    }
    if (!$db->f("enabled")) {
      $db->query("UPDATE upnp SET enabled=1 WHERE id=$id;");
      $err->raise("upnp",_("The specified upnp port is now enabled"));
      return true;
    }
    $err->raise("upnp",_("The specified upnp port is already enabled"));
    return true;
  }


  /* ----------------------------------------------------------------- */
  /** disable a upnp port in the upnp table
   * @param integer the id of the port to disable
   * @return boolean TRUE if the port has been properly forwarded
   * FALSE if an error occurred
   */
  function disable_port($id) {
    global $db,$err;
    $id=intval($id);
    $err->log("upnp","disable_port($id)");
    $db->query("SELECT enabled,mandatory FROM upnp WHERE id=$id;");
    if (!$db->next_record()) {
      $err->raise("upnp",_("The required port is not currently defined"));
      return false;
    }
    if ($db->f("mandatory")) {
      $err->raise("upnp",_("You can't disable that mandatory port forward"));
      return false;
    }
    if ($db->f("enabled")) {
      $db->query("UPDATE upnp SET enabled=0 WHERE id=$id;");
      $err->raise("upnp",_("The specified upnp port is now disabled"));
      return true;
    }
    $err->raise("upnp",_("The specified upnp port is already disabled"));
    return true;
  }


  /* ----------------------------------------------------------------- */
  /** cron launched every minute to check the status of UPnP forwarding
   */
  function cron() {
    global $hooks,$db,$L_INTERNAL_IP,$PUBLIC_IP;
    // if we check anything less than 5 minutes ago, or if the upnp table is empty, let's make a check...
    $db->query("SELECT UNIX_TIMESTAMP(lastcheck) AS lc, * FROM upnp ORDER BY lastcheck ASC;");
    $forwards=array();
    $bigcheck=false;
    if (!$db->next_record()) {
      $bigcheck=true;
    } else {
      if ($db->f("lc")+600<time()) { 
	$bigcheck=true;
      }
      do {
	$db->Record["found"]=false;
	$forwards[]=$db->Record;
      } while ($db->next_record());
    }
    
    if ($bigcheck) {
      // Do the check first by calling the hooks & comparing the arrays
      $res=$hooks->invoke("hooks_upnp_list");
      foreach($res as $c=>$tmp) {
	if (is_array($tmp) && count($tmp)) {
	  foreach($tmp as $name=>$v) {
	    
	    // We compare the hook array with the forwards array
	    $found=false;
	    for($i=0;$i<count($forwards);$i++) {
	      if ($forwards[$i]["class"]==$c && 
		  $forwards[$i]["name"]==$name && 
		  $forwards[$i]["protocol"]==$v["protocol"] && 
		  $forwards[$i]["port"]==$v["port"] && 
		  $forwards[$i]["mandatory"]==$v["mandatory"]) {
		// Found it and unchanged
		$forwards[$i]["found"]=true;
		$found=true;
	      }
	    } // compare with forwards class.
	    if (!$found) {
	      // Mark it for creation
	      $db->query("INSERT INTO upnp SET mandatory='".addslashes($v["mandatory"])."', protocol='".addslashes($v["protocol"])."', port='".addslashes($v["port"])."', name='".addslashes($name)."', action='CREATE'");
	      $id=$db->last_id();
	      $forwards[]=array("id"=>$id, "mandatory" => intval($v["mandatory"]), "protocol" => $v["protocol"], "port" => intval($v["port"]), "name" => $name, "action" => "CREATE");
	    }
	  } // for each port forward in that class
	} 
      } // for each hooked class
      // Now We search the "not found" and remove them from the array
      for($i=0;$i<count($forwards);$i++) {
	if (!$forwards[$i]["found"]) {
	  $forwards[$i]["action"]="DELETING";
	  $db->query("UPDATE upnp SET action='DELETING' WHERE id=".$forwards[$i]["id"].";");
	}
      }
      
    } // bigcheck ?
    
    // Ask for the current upnp status of forwarded ports
    $status=array(); $statusout=array(); $bad=false;
    unset($out);
    exec("upnpc -l 2>&1",$res,$out);
    if ( is_array($out) && !empty($out)) {
      foreach($out as $line) {
        // example line:  1 TCP   222->192.168.0.5:22   'libminiupnpc' ''
        if (preg_match("#^ *([0-9]+) (TCP|UDP) *([0-9]+)\-\>([0-9\.]+):([0-9]+) *#",$line,$mat)) {
  	if ($mat[4]==$L_INTERNAL_IP) {
  	  $status[]=array("protocol" => $mat[2], "port" => $mat[3]);
  	} else {
  	  $statusout[]=array("protocol" => $mat[2], "port" => $mat[3], "ip" => $mat[4]);
  	}
        }
        if (preg_match("#No IGD UPnP Device found on the network#",$line)) {
  	$bad=true;
        }
      } // For each line in upnpc -l (check list)
    }

    // No UPnP peripheral !! maybe you should not have installed AlternC-upnp altogether ? 
    if ($bad) {
      foreach($forwards as $f) {
	if ($f["action"]!="OK") {
	  $db->query("UPDATE upnp SET lastupdate=NOW(), lastcheck=NOW(), result='No UPnP device detected in your network !' WHERE id=".$f["id"].";");
	} else {
	  $db->query("UPDATE upnp SET lastupdate=NOW(), lastcheck=NOW(), WHERE id=".$f["id"].";");
	}
      }
      return;
    }

    // Now, for each forward, we either 
    // * check it (via upnpc -l parsing)
    // * add it (via upnpc -a)
    // * remove it (via upnpc -d)
    foreach($forwards as $f) {
      switch ($f["action"]) {
      case "OK": // check
	$found=false;
	foreach($status as $s) {
	  if ($s["port"]==$f["port"] && $s["protocol"]==$s["protocol"]) {
	    $found=true;
	    $db->query("UPDATE upnp SET lastcheck=NOW() WHERE id=".$f["id"].";");
	  }
	}
	if (!$found) {
	  // Search this protocol+port in the OTHER list ... if found, tell it ...
	}
	break;
      case "CREATE": 
	break;
      case "DELETE":
      case "DELETING":
	break;
      case "DISABLE":
	break;
      case "ENABLE":
	break;
      }
    }


  } // CRON function
  



  

} /* Class UPnP */

