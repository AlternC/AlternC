<?php
/*
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
 Original Author of file: Camille Lafitte
 Purpose of file: Manage hook system.
 ----------------------------------------------------------------------
*/
/**
 * This class manage cron.
 * 
 * @copyright    AlternC-Team 2002-2005 http://alternc.org/
 */
class m_cron {

  /*---------------------------------------------------------------------------*/
  /** Constructor
  */
  function m_cron() {
  }
//FIXME rajouter contrainte NOT NULL sur uid dans la bdd

  function schedule() {
  return Array(
    Array('unit'=>1440, 'name'=>_("Daily")),
    Array('unit'=>60,   'name'=>_("Hour")),
    Array('unit'=>30,   'name'=>_("Half Hour")),
  );

  }

  function lst_cron() {
    global $cuid,$db,$err;
    $err->log("cron","lst_cron");
    $db->query("select * from cron where uid = $cuid order by url;");
    $r=Array();
    while ($db->next_record()) {
      $tmp=Array();
      $tmp['id']=$db->f('id');
      $tmp['url']=urldecode($db->f('url'));
      $tmp['user']=urldecode($db->f('user'));
      $tmp['password']=urldecode($db->f('password'));
      $tmp['schedule']=$db->f('schedule');
      $tmp['email']=urldecode($db->f('email'));
      $r[]=$tmp;
    }
    return $r;
  }

  function update($arr) {
    $ok=true;
    foreach ($arr as $a) {
      if (! isset($a['id'])) $a['id']=null;
      if (empty($a['url']) && is_null($a['id'])) continue;
      if (! $this->update_one($a['url'], $a['user'], $a['password'], $a['email'], $a['schedule'], $a['id']) ) {
        $ok=false;
      }
    }
    return $ok;
  }

  function delete_one($id) {
    global $db,$err,$cuid;
    $err->log("cron","delete_one");
    return $db->query("delete from cron where id=".intval($id)." and uid=$cuid limit 1;");
  }

  function update_one($url, $user, $password, $email, $schedule, $id=null) {
    global $db,$err,$quota,$cuid;
    $err->log("cron","update_one");

    if (empty($url) && !is_null($id)) {
      return $this->delete_one($id);
    }

    // FIXME check que l'url est une vrai URL

    $url=mysql_real_escape_string(urlencode($url));
    $user=mysql_real_escape_string(urlencode($user));
    if (empty($user)) $password='';
    $password=mysql_real_escape_string(urlencode($password));
    if (! checkmail($email) == 0 ) return false;
    $email=mysql_real_escape_string(urlencode($email));
    if (! $this->valid_schedule($schedule)) return false;

    if (is_null($id)) { // if a new insert, quotacheck
      $q = $quota->getquota("cron");
      if ( $q["u"] >= $q["t"] ) {
        $err->log("cron","update_one","quota problem");
        return false;
      }
    } else { // if not a new insert, check the $cuid
      $db->query("select uid from cron where id = $id;");
      if (! $db->next_record()) { return "false"; } // return false if pb
      if ( $db->f('uid') != $cuid ) {
        $err->log("cron","update_one","bad uid");
        return false;
      } 
    }

    $query = "INSERT INTO cron (id, uid, url, user, password, schedule, email) values ('$id', '$cuid', '$url', '$user', '$password', '$schedule', '$email') on duplicate key update url='$url', user='$user', password='$password', schedule='$schedule', email='$email', uid='$cuid';";
    return $db->query("$query");

  }

  function valid_schedule($s) {
    $s2 = intval($s);
    if ($s2 != $s) return false;
    $r=false;
    foreach ($this->schedule() as $cs ) {
      if ($cs['unit'] == $s) return true;
    }
    return $r;
  }

  function alternc_quota_names() {
    return "cron";
  }

  // return the used quota
  function alternc_get_quota() {
    global $cuid,$db,$err;
    $err->log("cron","alternc_get_quota");
    $db->query("select count(*) as cnt from cron where uid = $cuid;");

    if ($db->next_record()) {
        return $db->f('cnt');
    }
    return false;
    
  }

} /* Class cron */

?>
