<?php
/*
 $Id: logs_download.php,v 1.2 2004/09/06 18:14:36 anonymous Exp $
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
 Original Author of file: Sonntag Benjamin
 Purpose of file: Return the current folder in a compressed file
 ----------------------------------------------------------------------
*/
require_once("../class/config.php");

$fields = array (
        "file"     => array ("request", "string", ""),
        "autoreload"     => array ("request", "integer", "1"),
        "lines"     => array ("request", "integer", "20"),
);
getFields($fields);

if (empty($file)) {
$error="";
}

include_once("head.php");

$string=$log->tail($file,$lines);
if (!$string) {
  $file=_("unknown");
}
?>
<h3><?php __("Follow a recent log"); ?></h3>
<p><?php printf(_("Please find below the last lines of file <b>%s</b>"),$file); ?></p>
<form method="get" action="logs_tail.php" name="update" id="update">
  <input type="hidden" name="file" value="<?php ehe($file); ?>" />
  <input type="hidden" name="autoreload" value="<?php ehe($autoreload); ?>" />
<?php if ($autoreload) {
?>
<input type="button" class="inb" name="autoreload" value="<?php __("Stop Auto Reload"); ?>" onclick="document.location='logs_tail.php?file=<?php eue($file); ?>&autoreload=0&lines=<?php eue($lines); ?>'"/>
<?php
} else {
?>
<input type="button" class="inb" name="autoreload" value="<?php __("Auto Reload"); ?>" onclick="document.location='logs_tail.php?file=<?php eue($file); ?>&autoreload=1&lines=<?php eue($lines); ?>'"/>
<?php
} ?>
<select id="lines" name="lines" onchange="document.forms['update'].submit()">
  <?php
  $alines=array(10=>10, 20=>20, 30=>30, 50=>50, 100=>100, 200=>200, 500=>500, 1000=>1000, 5000=>5000);
eoption($alines,$lines);
?>
</select> <?php __("Last lines shown"); ?>  
&nbsp;
<?php echo "<a class=\"inb\" href=\"logs_download.php?file=".$file."\">"._("Download")."</a>"; ?>
<a class="inb" href="logs_list.php" ><?php __("Back to the logs list"); ?></a> 
<hr id="topbar"/>
<br />
<?php
if (isset($error) && $error) {
  echo "<p class=\"alert alert-danger\">$error</p>";
}


?>
<pre style="    white-space: pre-wrap;       /* CSS 3 */
    white-space: -moz-pre-wrap;  /* Mozilla, since 1999 */
    white-space: -pre-wrap;      /* Opera 4-6 */
    white-space: -o-pre-wrap;    /* Opera 7 */
    word-wrap: break-word;       /* Internet Explorer 5.5+ */" >
<?php echo $string; ?>
</pre>
<?php
  if ($autoreload) {
?>
<script type="text/javascript">
window.setTimeout("document.location=document.location",5000);
</script>
<?php
  }
require_once("foot.php");
?>
