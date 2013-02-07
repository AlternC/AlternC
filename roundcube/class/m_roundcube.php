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
  Purpose of file: Manage Roundcube webmail configuration
  ----------------------------------------------------------------------
*/

/**
* This class handle roundcube's webmail
* hook the main panel page to add a link to the webmail
*/
class m_roundcube {

  /* ----------------------------------------------------------------- */
  /** Hook called by the homepage or the /webmail link
   * to redirect the user to a known webmail url.
   * the variable 'webmail_redirect' tells which webmail has the priority. 
   * @return string the URL of the webmail
   */
  function hook_admin_webmail() {
    global $db;
    // Search for the domain where the panel is hosted, then search for a webmail in it.
    $i=2;
    $domain="";
    if (!empty($_SERVER["HTTP_HOST"]))  { 
      do { // for each domain part (search panel.alternc.org then alternc.org then org, if the current panel is at www.panel.alternc.org)
	$expl=explode(".",$_SERVER["HTTP_HOST"],$i);
	if (count($expl)>=2) {
	  list($host,$dompart)=$expl;
	  // We search for a 'squirrelmail' subdomain in that domain
	  $db->query("SELECT * FROM sub_domaines s WHERE s.domaine='".addslashes($dompart)."' AND s.type='roundcube';");
	  if ($db->next_record()) {
	    $domain=$db->Record;
	    return "http://".$domain["sub"].(($domain["sub"])?".":"").$domain["domaine"];
	  }
	}
	$i++;
      } while (strpos($dompart,'.')!==false);
    }
    
    // not found: search for a webmail in the admin user account
    $db->query("SELECT * FROM sub_domaines s WHERE s.compte=2000 AND s.type='roundcube';");
    if ($db->next_record()) {
      $domain=$db->Record;
      return "http://".$domain["sub"].(($domain["sub"])?".":"").$domain["domaine"];
    }

  }


   /* ----------------------------------------------------------------- */
  /** Hook called when an email is REALLY deleted (by the cron, not just in the panel) 
   * @param mail_id integer the ID of the mail in the AlternC database
   * @param fullmail string the deleted mail himself in the form of john@domain.tld
   * @return boolean
   */
  function hook_mail_delete_for_real($mail_id, $fullmail) {
    // Include Roundcube configuration
    // Delete from the roundcube configuration

    // Use cleandb.sh filled by roundcube ? http://trac.roundcube.net/browser/github/bin/cleandb.sh

    include_once("/etc/roundcube/debian-db.php");

    switch ($dbtype) {
      case "sqlite":
        $rcdb = "sqlite:///$basepath/$dbname?mode=0640";
        $dbh = new PDO("sqlite:/$basepath/$dbname");
        break;
      default:
        if ($dbport != '') $dbport=":$dbport";
        if ($dbserver == '') $dbserver="localhost";
        $dbh= new PDO("$dbtype:host=$dbserver;dbname=$dbname;dbport=$dbport", $dbuser, $dbpass);
        $rcdb = "$dbtype:$dbuser:$dbpass@$dbserver$dbport/$dbname";
        break;
    }

    $req = $dbh->query("SELECT user_id FROM users WHERE username = '$fullmail'");

    foreach ( $req->fetchAll() as $t ) {
      if (empty($t['user_id'])) continue ;
      $rcuser_id=$t['user_id'];

      $dbh->query("DELETE from contactgroupmembers where contactgroup_id in (select contactgroup_id from contactgroups where user_id = $rcuser_id) ; ");
      $dbh->query("DELETE from contactgroups where user_id = $rcuser_id ; ");
      $dbh->query("DELETE from contacts where user_id = $rcuser_id ; ");
      $dbh->query("DELETE from identities where user_id = $rcuser_id ; ");
      $dbh->query("DELETE from users where user_id = $rcuser_id ; ");
    } //foreach

  }

} /* Class Roundcube */





