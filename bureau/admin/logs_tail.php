<?php
/*
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
*/

/**
 * Show the end of a log file and refresh automatically every few seconds
 * similar to a tail -f in a console
 *
 * @copyright AlternC-Team 2000-2017 https://alternc.com/ 
 */

require_once("../class/config.php");

$fields = array (
        "file"     => array ("request", "string", ""),
        "autoreload"     => array ("request", "integer", "1"),
        "lines"     => array ("request", "integer", "20"),
);
getFields($fields);

include_once("head.php");

$string=$log->tail($file,$lines);
if (!$string) {
  $file=__("unknown", "alternc", true);
}
?>
<h3><?php __("Follow a recent log"); ?></h3>
<p><?php printf(__("Please find below the last lines of file <b>%s</b>", "alternc", true),$file); ?></p>
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
  $alines=array(10=>10, 20=>20, 30=>30, 50=>50, 100=>100, 200=>200, 500=>500, 1000=>1000);
eoption($alines,$lines);
?>
</select> <?php __("Last lines shown"); ?>  
&nbsp;
<?php echo "<a class=\"inb\" href=\"logs_download.php?file=".$file."\">".__("Download", "alternc", true)."</a>"; ?>
<a class="inb" href="logs_list.php" ><?php __("Back to the logs list"); ?></a> 
<hr id="topbar"/>
<br />
<?php
echo $msg->msg_html_all();
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
