<?php
/*
 main.php
 ----------------------------------------------------------------------
 AlternC - Web Hosting System
 Copyright (C) 2002-2017 by the AlternC Development Team.
 https://alternc.com/
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
 Purpose of file: Main page shown after login, display misc information
 ----------------------------------------------------------------------
*/
require_once("../class/config.php");

include_once("head.php");

// Show last login information :
echo "<p>";
__("Last Login: ");

if ($mem->user["lastlogin"]=="0000-00-00 00:00:00") {
  __("Never");
} else { 
  echo format_date(_('the %3$d-%2$d-%1$d at %4$d:%5$02d'),$mem->user["lastlogin"]); 
  printf("&nbsp;"._('from: <code> %1$s </code>')."<br />",$mem->user["lastip"]);
}
  echo "</p>";

if ($mem->user["lastfail"]) {
	printf(_("%1\$d login failed since last login")."<br />",$mem->user["lastfail"]);
}

echo $msg->msg_html_all();
if (!empty($error) ) { echo "<p class='alert alert-danger'>$error</p>";$error=''; } 

$feed_url = variable_get('rss_feed', '', 'This is an RSS feed that will be displayed on the users homepages when they log in.', array('desc'=>'URL','type'=>'string'));
if (!empty($feed_url)) {
  $cache_time = 60*5; // 5 minutes
  $cache_file = "/tmp/alterncpanel_cache_main.rss";
  $timedif = @(time() - filemtime($cache_file));

  if (file_exists($cache_file) && $timedif < $cache_time) {
    $string = file_get_contents($cache_file);
  } else {
    $string = file_get_contents("$feed_url");
    file_put_contents($cache_file,$string);
  }
  $xml = @simplexml_load_string($string);

  if ( ! $xml === FALSE ) {
    echo '<div align="center"><table class="tedit" cellspacing="0" cellpadding="6">';
    echo "<tr><th colspan='2'><a target='_blank' style='font-size: 18px;font-weight: bold;color: #10507C;' href='".$xml->channel->link."'>".$xml->channel->title."</a><br/><i>".$xml->channel->description."</i></th></tr>";
    //echo '<tr><th>'._("Title").'</th><th>'._("Date").'</th></tr>';
    $count = 0;
    $max = 5;
    foreach ($xml->channel->item as $val) {
      if ($count < $max) {
        echo "<tr>\n<td ".(empty($val->pubDate)?'colpan=2':'').'><a target="_blank" href="'.$val->link.'">'.$val->title.'</a></td>';
        if (!empty($val->pubDate)) { 
          echo '<td>'.strftime("%d/%m/%Y" , strtotime($val->pubDate)).'</td>'; 
        }
        echo '</tr>';
      }
      $count++;
    } //foreach
    echo "</table></div>\n";
    echo "<br/>";
  } // $xml === FALSE
} // empty feed_url

if($admin->enabled) {
  $expiring = $admin->renew_get_expiring_accounts();

  if(!empty($expiring) ) {
    echo "<h2>" . _("Expired or about to expire accounts") . "</h2>\n";
    echo "<table cellspacing=\"2\" cellpadding=\"4\">\n";
    echo "<tr><th>"._("uid")."</th><th>"._("Last name, surname")."</th><th>"._("Expiry")."</th></tr>\n";
    if (is_array($expiring)) {
      foreach($expiring as $account) {
        echo "<tr class=\"exp{$account['status']}\"><td>{$account['uid']}</td>";
        if($admin->checkcreator($account['uid'])) {
          echo "<td><a href=\"adm_edit.php?uid={$account['uid']}\">{$account['nom']}, {$account['prenom']}</a></td>";
        }else{
          echo "<td>{$account['nom']}, {$account['prenom']}</td>";
        }
        echo "<td>{$account['expiry']}</td></tr>\n";
      }
    }
    echo "</table>\n";
  }

  echo "<hr/><p>";
  __("You are using the AlternC Panel. You can contact the AlternC community for information or feedback by joining the mailing-list");
  echo "&nbsp;<a target='_blank' href='http://lists.alternc.org/listinfo/users'>users@alternc.org</a>";
  echo "</p>";
} // if $admin->enabled

$blocks=$hooks->invoke("hook_homepageblock");
uasort($blocks, function($a, $b) {return $a->pos<$b->pos ? -1 : 1;});
foreach ($blocks as $v) {
	if (property_exists($v, "call")) {
		$func=$v->call;
		$func();
	}
	if (property_exists($v, "include")) {
		include $v->include;
	}
}

?>
<?php include_once("foot.php"); ?>
