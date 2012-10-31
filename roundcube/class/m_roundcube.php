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
    do { // for each domain part (search panel.alternc.org then alternc.org then org, if the current panel is at www.panel.alternc.org)
      list($host,$dompart)=explode(".",$_SERVER["HTTP_HOST"],$i);
      // We search for a 'roundcube' subdomain in that domain
      $db->query("SELECT * FROM sub_domaines s WHERE s.domaine='".addslashes($dompart)."' AND s.type='roundcube';");
      if ($db->next_record()) {
	$domain=$db->Record;
	return "http://".$domain["sub"].(($domain["sub"])?".":"").$domain["domaine"];
      }
      $i++;
    } while (strpos($dompart,'.')!==false);
    
    // not found: search for a webmail in the admin user account
    $db->query("SELECT * FROM sub_domaines s WHERE s.compte=2000 AND s.type='roundcube';");
    if ($db->next_record()) {
      $domain=$db->Record;
      return "http://".$domain["sub"].(($domain["sub"])?".":"").$domain["domaine"];
    }

  }

} /* Class Roundcube */





