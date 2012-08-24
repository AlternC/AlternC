<?php

require_once("../class/config.php");
if (!defined("QUOTASONE")) return;

?><!-- Les Mails -->
<center>
	    <p><h3><center><?php __("Account"); ?> <span style="font-weight: bold;"><?php echo $c["login"]; ?></span></center></h3></p>
<?php

	list($totalweb)=@mysql_fetch_array(mysql_query("SELECT SUM(size) FROM size_web WHERE uid = '" . $c["uid"] . "'"));

	  echo "<p>"._("Web Space:")." ";
	  echo sprintf("%.1f", $totalweb / 1024)."&nbsp;"._("MB");
	echo "</p>";

?>
<div style="width: 550px">
<table class="tedit">
<thead>
<tr>
   <th><?php __("Domains"); ?></th>
   <th><?php __("Emails"); ?></th>
   <th><?php __("Space"); ?></th>
</tr>
</thead>
<tbody>
<?php


  $s=mysql_query("SELECT * FROM domaines WHERE compte='".$c["uid"]."';");
  $totalmail=0;
  while ($d=mysql_fetch_array($s)) {
    list($mstmp)=@mysql_fetch_array(mysql_query("SELECT SUM(size) FROM size_mail WHERE alias LIKE '%\_".$d["domaine"]."';"));
    $totalmail+=$mstmp;
  }

  $s=mysql_query("SELECT * FROM domaines WHERE compte='".$c["uid"]."';");
  while ($d=mysql_fetch_array($s)) {
    $t=mysql_query("SELECT alias,size FROM size_mail WHERE alias LIKE '%\_".$d["domaine"]."' ORDER BY alias;");
    while ($e=mysql_fetch_array($t)) {
      echo "<tr><td>".$d["domaine"]."</td>";
      echo "<td>".str_replace("_","@",$e["alias"])."</td>";
      echo "<td";
      if ($mode!=2) echo " style=\"text-align: right\"";
      echo ">";
      $ms=$e["size"];
			if ($totalmail)
				$pc=intval(100*$ms/$totalmail);
			else
				$pc=0;
      if ($mode==0) {
	echo sprintf("%.1f", $ms / 1024)."&nbsp;"._("MB");
      } elseif ($mode==1) {
	echo sprintf("%.1f", $pc)."&nbsp;%";
      } else {
	echo "<img src=\"hippo_bleue.gif\" style=\"width: ".(2*$pc)."px; height: 16px\" alt=\"".$pc."%\" title=\"".$pc."\"/>";
      }
      echo "</td></tr>";
    }
  }
?>
</tbody>
</table>
    <p>&nbsp;</p>

<table class="tedit">
<thead>
<tr>
  <th><?php __("DB"); ?></th>
  <th><?php __("Space"); ?></th>
</tr>
</thead>
<tbody>
<?php

    // Espace DB :
    list($totaldb)=@mysql_fetch_array(mysql_query("SELECT SUM(size) FROM size_db WHERE db='".$c["login"]."' OR db LIKE '".$c["login"]."\_%';"));
    $s=mysql_query("SELECT db,size FROM size_db WHERE db='".$c["login"]."' OR db LIKE '".$c["login"]."\_%';");
  while ($d=mysql_fetch_array($s)) {
    echo "<tr><td>".$d["db"]."</td><td";
    if ($mode!=2) echo " style=\"text-align: right\"";
    echo ">";
    $ds=$d["size"];
		if ($totaldb)
			$pc=intval(100*$ds/$totaldb);
		else
			$pc=0;
    if (isset($mode) && $mode==0) {
      echo sprintf("%.1f", $ds / 1024/1024)."&nbsp;"._("MB");
    } elseif (isset($mode) &&$mode==1) {
      echo sprintf("%.1f", $pc)."&nbsp;%";
    } else {
      echo "<img src=\"hippo_bleue.gif\" style=\"width: ".(2*$pc)."px; height: 16px\" alt=\"".$pc."%\" title=\"".$pc."%\"/>";
    }
    echo "</td></tr>";
  }
?>
</tbody>
</table>

<?php
    list($totallist)=@mysql_fetch_array(mysql_query("SELECT SUM(size) FROM size_mailman WHERE uid='".$c["uid"]."'"));
  if ($totallist) {
    ?>
    <p>&nbsp;</p>
		 
<table class="tedit">
<thead>
<tr>
  <th><?php __("Lists"); ?></th>
  <th><?php __("Space"); ?></th>
</tr>
</thead>
<tbody>
<?php

    // Espace Liste :
    $s=mysql_query("SELECT list,size FROM size_mailman WHERE uid='".$c["uid"]."' ORDER BY list ASC");
  while ($d=mysql_fetch_array($s)) {
    echo "<tr><td>".$d["list"]."</td><td";
    if ($mode!=2) echo " style=\"text-align: right\"";
    echo ">";
    $ds=$d["size"];
		if ($totallist)
			$pc=intval(100*$ds/$totallist);
		else
			$pc=0;
    if ($mode==0) {
      echo sprintf("%.1f", $ds / 1024)."&nbsp;"._("MB");
    } elseif ($mode==1) {
      echo sprintf("%.1f", $pc)."&nbsp;%";
    } else {
      echo "<img src=\"hippo_bleue.gif\" style=\"width: ".(2*$pc)."px; height: 16px\" alt=\"".$pc."%\" title=\"".$pc."%\"/>";
    }
    echo "</td></tr>";
  }
?>
</tbody>
</table>

    <?php } ?>
</div>
</center>
