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
 Purpose of file: Manage hook system.
 ----------------------------------------------------------------------
*/

/**
 * This class manage web-cron tasks
 */
class m_cron {


  /*---------------------------------------------------------------------------*/
  /** Constructor
  */
  function m_cron() {
  }
  //FIXME add a  NOT NULL constraint on uid in the DB
  function schedule() {
    return Array(
		 Array('unit'=>1440, 'name'=>_("Daily")),
		 Array('unit'=>60,   'name'=>_("Hour")),
		 Array('unit'=>30,   'name'=>_("Half Hour")),
		 );
  }

  
  /*---------------------------------------------------------------------------*/
  /** List the crontab for the current user.
   * @return array an hash for each crontab.
   */
  function lst_cron() {
    global $cuid,$db,$err;
    $err->log("cron","lst_cron");
    $db->query("SELECT * FROM cron WHERE uid = $cuid ORDER BY url;");
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


  /*---------------------------------------------------------------------------*/
  /** update the crontab 
   * @param $arr array the crontab information, including its ID
   * @return boolean TRUE if the crontab has been edited
  */
  function update($arr) {
    $ok=true;
    foreach ($arr as $a) {
      if (! isset($a['id'])) $a['id']=null;
      if (empty($a['url']) && is_null($a['id'])) continue;
      if (! $this->_update_one($a['url'], $a['user'], $a['password'], $a['email'], $a['schedule'], $a['id']) ) {
        $ok=false;
      }
    }
    return $ok;
  }
  

  /*---------------------------------------------------------------------------*/
  /** delete a crontab 
   * @param $id the id of the crontab to delete
   * @return boolean TRUE if the crontab has been deleted
  */
  function delete_one($id) {
    global $db,$err,$cuid;
    $err->log("cron","delete_one");
    return $db->query("DELETE FROM cron WHERE id=".intval($id)." AND uid=$cuid LIMIT 1;");
  }
  

  /*---------------------------------------------------------------------------*/
  /** update a crontab, 
   * @return boolean TRUE if the crontab has been edited
  */
  private function _update_one($url, $user, $password, $email, $schedule, $id=null) {
    global $db,$err,$quota,$cuid;
    $err->log("cron","update_one");

    if (empty($url) && !is_null($id)) {
      return $this->delete_one($id);
    }

    // FIXME check the url property
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
        $err->raise("cron",_("You seems to be over-quota."));
        return false;
      }
    } else { // if not a new insert, check the $cuid
      $db->query("SELECT uid FROM cron WHERE id = $id;");
      if (! $db->next_record()) { return "false"; } // return false if pb
      if ( $db->f('uid') != $cuid ) {
        $err->raise("cron",_("Identity problem"));
        return false;
      } 
    }
    $query = "REPLACE INTO cron (id, uid, url, user, password, schedule, email) VALUES ('$id', '$cuid', '$url', '$user', '$password', '$schedule', '$email') ;";
    return $db->query("$query");
  }


  /*---------------------------------------------------------------------------*/
  /** validate a crontab schedule
   * @param $s array schedule paramters
   * @return boolean TRUE if the schedule is valid
  */
  function valid_schedule($s) {
    $s2 = intval($s);
    if ($s2 != $s) return false;
    $r=false;
    foreach ($this->schedule() as $cs ) {
      if ($cs['unit'] == $s) return true;
    }
    return $r;
  }

  /*---------------------------------------------------------------------------*/
  /** hook for quota computation
   */
  function hook_quota_get() {
    global $cuid,$db,$err;
    $err->log("cron","alternc_get_quota");
    $q=Array("name"=>"cron", "description"=>_("Scheduled tasks"), "used"=>0);
    $db->query("select count(*) as cnt from cron where uid = $cuid;");
    if ($db->next_record()) {
        $q['used']=$db->f('cnt');
    }
    return $q;
  }


} /* Class cron */
