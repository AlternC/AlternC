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

$root="/var/alternc/html/".substr($mem->user["login"],0,1)."/".$mem->user["login"]."/";
// pour utiliser 'la ou est browseforfolder', mettre dirname($HTTP_SERVER_VARS["PATH_TRANSLATED"]);

if (substr($file,0,1)!="/") $file="/".$file;
if (substr($file,-1)!="/") $file.="/";
if (!$file) $file="/";

$errbrowsefold=0;    /* Erreur lors de la création d'un dossier */
$brlist=array();    /* Liste des dossiers ... */
$maxlevel=0;


if (isset($select) && $select) {
        /* Go ahead, let's send the javascript ...*/
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd"><html xmlns="http://www.w3.org/1999/xhtml" xml:lang="fr" lang="fr">
<head>
<title>Browser for folder</title>
<link rel="stylesheet" href="styles/style.css" type="text/css" />
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />

<?php
        echo "<head><script type=\"text/javascript\">\n";
        echo "window.opener.document.".$caller.".value='".addslashes($file)."';\n";
        echo "window.opener.window.focus();\n";
        echo "window.close();\n";
        echo "</script>\n";
	echo "</head><body></body></html>";
        exit();
}

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="fr" lang="fr">
<head>
<title>Recherche d'un dossier</title>
<link rel="stylesheet" href="styles/style.css" type="text/css" />
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
<script type="text/javascript">
/* Fonction appellée lors du lancement d'un popup Fichier : */
function popupfile() {
        window.focus();
        if (document.forms["main"].file)
                document.forms["main"].file.focus();
}
</script>
</head>
<body onload="popupfile();">
<h3><?php __("Searching for a folder"); ?></h3>
<?php

$ar=browseforfolder($file);
if ($errbrowsefold) {
        /* Si le dossier spécifié n'existe pas ou est un fichier : */
        echo _("Error, cannot find this folder")."<br />";
        /* Retour : */
        echo "<a href=\"browseforfolder.php?caller=".urlencode($caller)."&amp;curdir=".$root."\">"._("Back to the root folder")."</a><br />";
} else {
        /* Sinon, tout va bien, on affiche le tableau */
        reset($ar);
?>
<form method="post" id="main" name="main" action="browseforfolder.php">
<p>
<input type="hidden" name="caller" value="<?php echo $caller; ?>" />
<input type="hidden" name="lastcurdir" value="<?php echo $curdir; ?>" />

<input type="text" class="int" name="file" size="20" value="<?php ehe($file); ?>" /><br />

<input type="submit" name="select" value="<?php __("Select"); ?>" class="inb" />&nbsp;
<input type="button" name="cancel" value="<?php __("Cancel"); ?>" class="inb" onclick="window.close();" />&nbsp;
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
                        echo "<a href=\"browseforfolder.php?caller=".urlencode($caller)."&amp;file=".urlencode($val["put"])."\">".$val["dir"]."</a>";
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
