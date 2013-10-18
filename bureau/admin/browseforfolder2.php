<?php
/*
   Navigateur de dossiers en php. (BrowseForFolder in win32 api :)
   Version 1.0
   Notes :
   Benjamin Sonntag 23/12/2001 Version initiale: n'utilise qu'un seul uid : 1...
   Fichier :
   browseforfolder.php3 : Dialogue de navigation BrowseForFolder
   $caller = composant form appelant (de la forme forms['main'].component )

   function browseforfolder(caller) {
   eval("file=document."+caller+".value");
   w=window.open("browseforfolder.php?caller="+caller+"&file="+file,"browseforfolder","width=300,height=400,scrollbars,left=100,top=100");
   }

   requires : ife($test,$iftrue,$iffalse) function : 

   function ife($test,$true,$false="") {
   if ($test)
   return $true;
   else
   return $false;
   }

   BrowseForFolder($curdir); Retourne le tableau avec la liste des dossiers à afficher dans
   la fonction browseforfolder sachant que le dossier actuel et curdir
   retourne un tableau de tableau de la forme :
   dir => "directory"      Nom du dossier
   level => 0-n            Niveau du dossier (0=racine 1,2 ...)
   put => "/sub/sub/directory" Contenu de la variable post à ajouter pour la balise A si ="" c'est le dossier courant.
   Si probleme, positionne $errbrowsefold
   Sinon, retourne le tableau et $maxlevel contient le nombre maximum de sous-dossiers.
 */
include("../class/config.php");

// FIXME Refaire ce truc hein...
$fields = array (
    "caller"               => array ("request", "string", ""),
    "select"               => array ("request", "string", ""),
    "curdir"               => array ("request", "string", ""),
    "lastcurdir"           => array ("request", "string", ""),
    "file"                 => array ("request", "string", ""),
    "bid"                  => array ("request", "string", ""),
    );
getFields($fields);

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

function browseforfolder($curdir) {
  global $maxlevel,$root,$brlist;
  $maxlevel=0;
  $pat=explode("/",$curdir);
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
<script src="js/jquery.min_embedded.js" type="text/javascript"></script>
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
  window.parent.document.<?php echo $caller; ?>.value = addslashes( $("#file").val() );
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
    <p>
    <input type="hidden" name="caller" value="<?php echo $caller; ?>" />
    <input type="hidden" name="lastcurdir" value="<?php echo $curdir; ?>" />
    <input type="hidden" name="bid" value="<?php echo $bid; ?>" />

    <input type="text" class="int" id="file" name="file" size="20" value="<?php ehe($file); ?>" /><br />

    <input type="button" name="select" value="<?php __("Select"); ?>" class="inb" onclick="retour();" />&nbsp;
  <input type="button" name="cancel" value="<?php __("Cancel"); ?>" class="inb" onclick="window.parent.jQuery('#<?php echo $bid; ?>').dialog('close');" />&nbsp;
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
        echo "<a href=\"browseforfolder2.php?caller=".urlencode($caller)."&amp;bid=".$bid."&amp;file=".urlencode($val["put"])."\">".$val["dir"]."</a>";
      } else {
        echo "<b>".$val["dir"]."</b>";
      }
      echo "</td>\n</tr>\n";
    }
} // OK ?
?>
</table>
</body>
</html>
