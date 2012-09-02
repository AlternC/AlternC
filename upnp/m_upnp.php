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
    $db->query("SELECT enabled FROM upnp WHERE id=$id;");
    if (!$db->next_record()) {
      $err->raise("upnp",_("The required port is not currently defined"));
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

  

}