<?php
/*
 $Id: m_authip.php
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
 Original Author of file: Fufroma
 ----------------------------------------------------------------------
*/
/**
* Classe de gestion des IP authorisée
**/
class m_authip {

  // Return all the IP address define by this user
  function list_ip() {
    global $db, $cuid;

    $r = array();
    $db->query("SELECT * FROM authorised_ip WHERE uid='$cuid';");
    while ($db->next_record()) {
      $r[$db->f('id')]=$db->Record;
      if ( (checkip($db->f('ip'))   && $db->f('subnet') == 32) ||
           (checkipv6($db->f('ip')) && $db->f('subnet') == 128) ) {
        $r[$db->f('id')]['ip_human']=$db->f('ip');
      } else {
        $r[$db->f('id')]['ip_human']=$db->f('ip')."/".$db->f('subnet');
      }

    }
    return $r;
  }

  // Delete an IP in authorised_ip
  function ip_delete($id) {
    global $db, $cuid;
    $id=intval($id);
    
    $db->query("SELECT id FROM authorised_ip_affected where authorised_ip_id ='$id';");
    while ($db->next_record()) {
      $this->ip_affected_delete($db->f('id'));
    }
    if (! $db->query("delete from authorised_ip where id='$id' and uid='$cuid' limit 1;") ) {
      echo "query failed: ".$db->Error;
      return false;
    }
    return true;
  }

  // Insert or update in authorised_ip
  function ip_save($id, $ipsub, $infos, $uid=null) {
    global $db, $mem;

    // If we ask for uid=0, we have to check to be super-user
    // else, juste use global cuid;
    if ($uid === 0 && $mem->checkRight() ) {
      $cuid=0;
    } else {
      global $cuid;
    } 

    $id=intval($id);
    $infos=mysql_real_escape_string($infos);

    // Extract subnet from ipsub
    $tmp=explode('/',$ipsub);
    $ip=$tmp[0];
    $subnet=intval($tmp[1]);

    // Error if $ip not an IP
    if ( ! checkip($ip) && ! checkipv6($ip) ) {
        echo "Failed : not an IP address";
        return false;
    }

    // Check the subnet, if not defined, give a /32 or a /128
    if ( ! $subnet ) {
      if ( checkip($ip) ) $subnet=32;
      else $subnet=128;
    }

    // An IPv4 can't have subnet > 32
    if (checkip($ip) && $subnet > 32 ) $subnet=32;
      
    if ($id) { // Update
      if (! $db->query("update authorised_ip set ip='$ip', subnet='$subnet', infos='$infos' where id='$id' and uid='$cuid' ;") ) {
        echo "query failed: ".$db->Error;
        return false;
      }
      // TODO hooks update
    } else { // Insert
      if (! $db->query("insert into authorised_ip (uid, ip, subnet, infos) values ('$cuid', '$ip', '$subnet', '$infos' );") ) {
        echo "query failed: ".$db->Error;
        return false;
      }
    }
    return true;
  }

  // Function called by alternc when you delete a member
  function alternc_del_member($l_uid) {
    $db->query("SELECT id FROM authorised_ip WHERE uid ='$l_uid';");
    while ($db->next_record()) {
      $this->ip_delete($db->f('id'));
    }
    return true;
  }


  function get_auth_class() {
    global $classes;
    $authclass=array();

    foreach ($classes as $c) {
      global $$c;
      if ( method_exists($$c, "authip_class") ) {
        $a=$$c->authip_class();
        $a['class']=$c;
        $authclass[$a['protocol']]=$a;
      }
    }
    return $authclass;
  }

  // Save in ip_affected_save
  function ip_affected_save($authorised_ip_id, $protocol, $parameters, $id=null) {
    global $db;
    $authorised_ip_id=intval($authorised_ip_id);
    $protocol=mysql_real_escape_string($protocol);
    $parameters=mysql_real_escape_string($parameters);

    if ($id) {
      $id=intval($id);
      if (! $db->query("update authorised_ip_affected set authorised_ip_id='$authorised_ip_id', protocol='$protocol', parameters='$parameters' where id ='$id' limit 1;") ) {
        echo "query failed: ".$db->Error;
        return false;
      }
      // TODO hooks update
    } else {
      if (! $db->query("insert into authorised_ip_affected (authorised_ip_id, protocol, parameters) values ('$authorised_ip_id', '$protocol', '$parameters');") ) {
        echo "query failed: ".$db->Error;
        return false;
      }
      // TODO hooks insert
    }
    return true;
  }

  // Delete an IP in authorised_ip_affected
  function ip_affected_delete($id) {
    global $db;
    $id=intval($id);
    if (! $db->query("delete from authorised_ip_affected where id='$id' limit 1;") ) {
      echo "query failed: ".$db->Error;
      return false;
    }
    // TODO hooks delete
    return true;
  }


  function list_affected() {
    global $db, $cuid;

    $r = array();
    $db->query("SELECT * FROM authorised_ip_affected WHERE authorised_ip_id in (select id from authorised_ip where uid = '$cuid');");
    while ($db->next_record()) {
      $r[]=$db->Record;
    }
    return $r;
  }
// TODO :
// hooks on créations/update/delete



}; /* Classe m_authip */
