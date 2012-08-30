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
  Purpose of file: Manage Squirrelmail webmail configuration
  ----------------------------------------------------------------------
*/

/**
* This class handle squirrelmail's webmail
* hook the main panel page to add a link to the webmail
*/
class m_squirrelmail {

  function hook_admin_webmail() {
    global $db;
    // not found ? use admin account (2000)
    $account=2000;
    // Search for the domain where the panel is hosted, then search for a webmail in it.
    $i=2;
    $domain="";
    do { // for each domain part (search panel.alternc.org then alternc.org then org, if the current panel is at www.panel.alternc.org)
      list($host,$domain)=explode(".",$_SERVER["HTTP_HOST"],$i);
      $dompart=$mat[1];
      // We search for a 'squirrelmail' subdomain in that domain
      $db->query("SELECT * FROM subdomaines s WHERE s.domaine='".addslashes($dompart)."' AND s.type='squirrelmail';");
      if ($db->next_record()) {
	$domain=$db->Record;
	return "<p><a href=\"http://".$dompart["sub"].(($dompart["sub"])?".":"").$dompart["domaine"]."\">"._("To read your mail in a browser, click here to use the Squirrelmail Webmail")."</a></p>\n";
      }
      $i++;
    } while (strpos($dompart,'.')!==false);
  
 // not found: search for a webmail in the admin user account
    $db->query("SELECT * FROM subdomaines s WHERE s.compte=2000 AND s.type='squirrelmail';");
    if ($db->next_record()) {
      $domain=$db->Record;
      return "<p><a href=\"http://".$dompart["sub"].(($dompart["sub"])?".":"").$dompart["domaine"]."\">"._("To read your mail in a browser, click here to use the Squirrelmail Webmail")."</a></p>\n";
    }

  }

} /* Class Squirrelmail */





