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
 * An HTML page to browse for a folder on a remote server and choose it using 
 * Javascript. Chroot the user to its root, 
 * 
 * @copyright AlternC-Team 2000-2017 https://alternc.com/ 
 */

include("../class/config.php");

$fields = array (
    "caller"               => array ("request", "string", ""),
    "select"               => array ("request", "string", ""),
    "curdir"               => array ("request", "string", ""),
    "lastcurdir"           => array ("request", "string", ""),
    "file"                 => array ("request", "string", ""),
    "bid"                  => array ("request", "string", ""),
    );
getFields($fields);

/**
 * @param integer $pos
 * @param integer $level
 * @param string $curdir
 */
function _subbrowse($curdir,$pos,$level) {
  global $maxlevel,$root,$brlist;
  if ($level>$maxlevel)
    $maxlevel=$level;
  $rot=substr($curdir,0,$pos);
  $next=@strpos($curdir,"/",$pos+1);
  $nextstr=substr($curdir,$pos+1,$next-$pos-1);
  $c=opendir($root.$rot);
  $i=0; $tmp = array();
  while ($r=readdir($c)) { 
    if (is_dir($root.$rot."/".$r) && $r!="." && $r!="..") { $tmp[$i++]=$r; }
  }
  sort($tmp);  
  foreach ($tmp as $r) {
    /* Ajout */
    $brlist[]=array("dir"=>$r,"level"=>$level,"put"=> ife($curdir==$rot."/".$r."/","",$rot."/".$r));
    if ($r==$nextstr) {
      _subbrowse($curdir,$next,$level+1);
    }
  }
}

/**
 * @param string $curdir
 */
function browseforfolder($curdir) {
  global $maxlevel,$root,$brlist;
  $maxlevel=0;
  $brlist=array(array("dir"=>"/","level"=>0,"put"=> ife($curdir=="/","","/") ));
  _subbrowse($curdir,0,1);
  return $brlist;
}

$root=getuserpath();
// pour utiliser 'la ou est browseforfolder', mettre dirname($HTTP_SERVER_VARS["PATH_TRANSLATED"]);

if (substr($file,0,1)!="/") $file="/".$file;
if (substr($file,-1)!="/") $file.="/";
if (!$file) $file="/";

$errbrowsefold=0;    /* Erreur lors de la création d'un dossier */
$brlist=array();    /* Liste des dossiers ... */
$maxlevel=0;
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="fr" lang="fr">
<head>
<title>Recherche d'un dossier</title>
<link rel="stylesheet" href="styles/style.css" type="text/css" />
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
<script src="/javascript/jquery/jquery.min.js" type="text/javascript"></script>
<script type="text/javascript">
/* Fonction appellée lors du lancement d'un popup Fichier : */
function popupfile() {
  window.focus();
  if (document.forms["main"].file)
    document.forms["main"].file.focus();
}

function addslashes(ch) {
  ch = ch.replace(/\\/g,"\\\\")
    ch = ch.replace(/\'/g,"\\'")
    ch = ch.replace(/\"/g,"\\\"")
    return ch
}

/* Fontion de retour de la valeur selectionnee */
function retour() {
  window.parent.jQuery('#<?php echo $caller; ?>').val( $("#file").val() );
  window.parent.jQuery('#<?php echo $bid; ?>').dialog('close');
  return false;
}

</script>
</head>
<body class="light" onload="popupfile();">
<h3><?php __("Searching for a folder"); ?></h3>
<?php

$ar=browseforfolder($file);
if ($errbrowsefold) {
  /* Si le dossier spécifié n'existe pas ou est un fichier : */
  echo _("Error, cannot find this folder")."<br />";
  /* Retour : */
  echo "<a href=\"browseforfolder2.php?caller=".urlencode($caller)."&amp;curdir=".$root."&amp;bid=".$bid."\">"._("Back to the root folder")."</a><br />";
} else {
  /* Sinon, tout va bien, on affiche le tableau */
  reset($ar);
  ?>
    <form method="post" id="main" name="main" action="browseforfolder2.php">
       <?php csrf_get(); ?>
    <p>
    <input type="hidden" name="caller" value="<?php ehe($caller); ?>" />
    <input type="hidden" name="lastcurdir" value="<?php ehe($curdir); ?>" />
    <input type="hidden" name="bid" value="<?php ehe($bid); ?>" />

    <input type="text" class="int" id="file" name="file" size="20" value="<?php ehe($file); ?>" /><br />

    <input type="button" name="select" value="<?php __("Select"); ?>" class="inb" onclick="retour();" />&nbsp;
  <input type="button" name="cancel" value="<?php __("Cancel"); ?>" class="inb" onclick="window.parent.jQuery('#<?php ehe($bid); ?>').dialog('close');" />&nbsp;
  </p>
    </form>

    <table style="border: 0" cellspacing="2" cellpadding="0">

    <?php
    while (list($key,$val)=each($ar)) {
      echo "<tr>\n";
      for ($i=0;$i<$val["level"];$i++)
        echo "<td width=\"16\"></td>";
      if ($val["put"]!="") {
        ?>
          <td width="16"><img src="icon/folder.png" width="16" height="16" alt="" /></td>
          <?php
      } else {
        ?>
          <td width="16"><img src="icon/openfold.png" width="16" height="16" alt="" /></td>
          <?php
      }
      echo "<td colspan=\"".($maxlevel-$val["level"]+1)."\">";
      if ($val["put"]!="") {
          echo "<a href=\"browseforfolder2.php?caller=".eue($caller,false)."&amp;bid=".eue($bid,false)."&amp;file=".eue($val["put"],false)."\">".ehe($val["dir"],false)."</a>";
      } else {
          echo "<b>".ehe($val["dir"],false)."</b>";
      }
      echo "</td>\n</tr>\n";
    }
} // OK ?
?>
</table>
</body>
</html>
