<?php
/*
 $Id: bro_main.php,v 1.11 2004/09/06 18:14:36 anonymous Exp $
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
 Original Author of file: Benjamin Sonntag, Remi
 Purpose of file: Online file Browser of AlternC
 TODO : Voir ??? + Déplacer / Copier 
 ----------------------------------------------------------------------
*/
require_once("../class/config.php");
include_once ("head.php");

$fields = array (
		 "R"    => array ("request", "string", ""),
		 "formu"       => array ("request", "integer", ""),
		 "actextract"    => array ("request", "string", ""),
		 "fileextract"    => array ("request", "string", ""),
		 "actdel"    => array ("request", "string", ""),
		 "actcopy"    => array ("request", "string", ""),
		 "actmove"    => array ("request", "string", ""),
		 "actmoveto"    => array ("request", "string", ""),
		 );
getFields($fields);


$p=$bro->GetPrefs();
if (! isset($R)) $R='';
if (!$R && $p["golastdir"]) {
  $R=$p["lastdir"];
}
$R=$bro->convertabsolute($R,1);
// on fait ?
if (isset($formu) && $formu) {
  switch ($formu) {
  case 1:  // Créer le répertoire $R.$nomfich
    if (!$bro->CreateDir($R,$nomfich)) {
      $error = $err->errstr();
    }
    $p=$bro->GetPrefs();
    break;
  case 6: // Créer le fichier $R.$nomfich
    if (!$bro->CreateFile($R,$nomfich)) {
      $error = $err->errstr();
    }
    $p=$bro->GetPrefs();
    if ($p["createfile"]==1) {
      $file=$nomfich;
      include("bro_editor.php");
      exit();
    }
    break;
  case 2:  // act vaut Supprimer Copier ou Renommer.
    if ($actdel) {
      if ($del_confirm != "") { 
        if (!$bro->DeleteFile($d,$R)) {
          $error = $err->errstr();
        }
      } elseif (!$cancel && is_array($d)) {
        include_once("head.php");
?>
  <h3><?php printf(_("Deleting files and/or directories")); ?> : </h3>
  <form action="bro_main.php" method="post" name="main" id="main">  
    <input type="hidden" name="formu" value="2" />
    <input type="hidden" name="actdel" value="1" />
    <input type="hidden" name="R" value="<?php echo $R?>" />
    <p class="error"><?php __("WARNING: Confirm the deletion of this files"); ?></p>
<?php foreach($_REQUEST["d"] as $file){ ?>
	<p><?php echo stripslashes($file); ?></p>
        <input type="hidden" name="d[]" value="<?php echo htmlentities(stripslashes($file)); ?>" />
<?php } ?>
    <blockquote>
      <input type="submit" class="inb" name="del_confirm" value="<?php __("Yes"); ?>" />&nbsp;&nbsp;
      <input type="submit" class="inb" name="cancel" value="<?php __("No"); ?>" />
    </blockquote>
  </form>
</body>
</html>
<?php
        exit();
      }
    }
    if ($actcopy) {
      if (!$bro->CopyFile($d,$R,$actmoveto)) {
        $error = $err->errstr();
      }
    }
    if ($actmove) {
      if (!$bro->MoveFile($d,$R,$actmoveto)) {
        $error = $err->errstr();
      }
    }
    break;
  case 4:  // Renommage Effectif...
    if (!$bro->RenameFile($R,$o,$d)) { // Rename $R (directory) $o (old) $d (new) names
      $error = $err->errstr();
    } 
    break;
  case 3:  // Upload de fichier...
    if (!$bro->UploadFile($R)) {
      $error = $err->errstr();
    }
    break;
  case 7:  // Changement de permissions [ML]
    if (!$bro->ChangePermissions($R, $d, $perm)) {
      $error = $err->errstr();
    }
    break;
  }
}

if (isset($actextract) && $actextract) {
  print _("extracting...")."<br />\n"; flush();
  if ($bro->ExtractFile($R. '/' . $fileextract, $R)) {
    echo "<p class=\"error\">";
    print $err->errstr();
    print _("failed")."<br />\n";
    echo "</p>";
  } else {
    print _("done")."<br />\n";
  }
}

/* Creation de la liste des fichiers courants */
$c=$bro->filelist($R, isset($_REQUEST['showdirsize'])?$_REQUEST['showdirsize']:null );
if ($c===false) $error=$err->errstr();

?>
<h3><?php __("File browser"); ?></h3>
<table border="0" width="100%" cellspacing="0">
<tr><td>

<hr />


<p class="breadcrumb">
 <?php __("Path"); ?> / <a href="bro_main.php?R=/"><?php echo $mem->user["login"]; ?></a>&nbsp;/&nbsp;<?php echo $bro->PathList($R,"bro_main.php") ?>
</p>

<?php if (isset($error) && $error) echo "<p class=\"error\">$error</p>"; ?>

<table><tr>
<td class="formcell">

     <form action="bro_main.php" enctype="multipart/form-data" method="post">
     <input type="hidden" name="R" value="<?php echo $R; ?>" />
     <input type="hidden" name="formu" value="3" />

     <?php __("Send one file:"); ?><br />
<input class="int" name="userfile" type="file" />
     <input type="hidden" name="MAX_FILE_SIZE" value="10000000" />
<br />
     <input type="submit" id="sendthisfile" class="ina" value="<?php __("Send this file"); ?>" />

     </form>

</td>
<td style="width: 20px">&nbsp;</td>
<td class="formcell">

<?php __("New file or folder:"); ?><br />
<form action="bro_main.php" method="post" name="nn" id="nn">
<input type="hidden" name="R" value="<?php echo $R; ?>" />
<table><tr>
<td><input type="text" class="int" name="nomfich" id="nomfich" size="22" maxlength="255" /></td>
<td><input type="submit" class="ina" value="<?php __("Create"); ?>" /></td>
</tr><tr><td>
<input type="radio" class="inc" id="nfile" onclick="document.nn.nomfich.focus();" name="formu" value="6" <?php if (!$p["crff"]) echo "checked=\"checked\""; ?> /><label for="nfile">&nbsp;<?php __("File"); ?></label>
<input type="radio" class="inc" id="nfold" onclick="document.nn.nomfich.focus();" name="formu" value="1" <?php if ($p["crff"]) echo "checked=\"checked\""; ?> /><label for="nfold">&nbsp;<?php __("Folder"); ?></label>
</td><td></td></tr></table>
</form>
</td></tr>
</table>


</td></tr>
<tr><td valign="top">

<?php
/* Renommer / Copier / Déplacer les fichiers : */
if (isset($formu) && $formu==2 && $actrename && count($d)) {
  echo "<table cellpadding=\"6\">\n";
  echo "<form action=\"bro_main.php\" method=\"post\">\n";
  echo "<input type=\"hidden\" name=\"R\" value=\"$R\" />\n";
  echo "<input type=\"hidden\" name=\"formu\" value=\"4\" />\n";
  echo "<tr><th colspan=\"2\">"._("Rename")."</th></tr>";
  for ($i=0;$i<count($d);$i++) {
    $d[$i]=ssla($d[$i]);
    echo "<tr><td><input type=\"hidden\" name=\"o[$i]\" value=\"".$d[$i]."\" />".$d[$i]."</td>";
    echo "<td><input type=\"text\" class=\"int\" name=\"d[$i]\" value=\"".$d[$i]."\" /></td></tr>";
  }
  echo "<tr><td colspan=\"2\" align=\"center\"><input type=\"submit\" class=\"inb\" name=\"submit\" value=\""._("Rename")."\" /></td></tr>";
  echo "</table></form>\n";
  echo "<hr />\n";
}

/* [ML] Changer les permissions : */
if (isset($formu) && $formu==2 && $_REQUEST['actperms'] && count($d)) {
  echo "<form action=\"bro_main.php\" method=\"post\">\n";
  echo "<input type=\"hidden\" name=\"R\" value=\"$R\" />\n";
  echo "<input type=\"hidden\" name=\"formu\" value=\"7\" />\n";
  echo "<p>"._("Permissions")."</p>";

  $tmp_absdir = $bro->convertabsolute($R,0);

  echo "<table border=\"1\" cellpadding=\"4\" cellspacing=\"0\">";
  echo "<tr>";
  echo "<th>" . _("File") . "</th><th>"._("Permissions")."</th>"; 
  echo "</tr>";

  for ($i=0;$i<count($d);$i++) {
    $d[$i]=ssla($d[$i]);
    $stats = stat($tmp_absdir . '/' . $d[$i]);
    $modes = $stats[2];

    echo "<tr>";
    echo "<td>".$d[$i]."</td>";

    // Owner
    echo "<td>";
    echo "<input type=\"hidden\" name=\"d[$i]\" value=\"".$d[$i]."\" />";
    echo "<label for=\"permw$i\">"._("write")."</label> <input type=\"checkbox\" id=\"permw$i\" name=\"perm[$i][w]\" value=\"1\" ". (($modes & 0000200) ? 'checked="checked"' : '') ." />";
    echo "</td>";

    echo "</tr>";
  }

  echo "</table>";

  echo "<p><input type=\"submit\" class=\"inb\" name=\"submit\" value=\""._("Change permissions")."\" /></p>";
  echo "</form>\n";
  echo "<hr />\n";
}

/* We draw the file list and button bar only if there is files here ! */
if (count($c)) {

?>
<form action="bro_main.php" method="post" name="main" id="main">
<input type="hidden" name="R" value="<?php echo $R; ?>" />
<input type="hidden" name="formu" value="2" />

<br />


<table width="100%" style="border: 0px">
<tr><td class="lst2" style="padding: 4px 4px 8px 4px">

<input type="submit" class="ina" name="actdel" value="<?php __("Delete"); ?>" />
<input type="submit" class="ina" name="actrename" value="<?php __("Rename"); ?>" />
<input type="submit" class="ina" name="actperms" value="<?php __("Permissions"); ?>" /> 
&nbsp; |&nbsp;
<input type="submit" class="ina" name="actcopy" value="<?php __("Copy"); ?>" />
<input type="submit" class="ina" name="actmove" value="<?php __("Move"); ?>" />
<?php __("To"); ?> 
<input type="text" class="int" name="actmoveto" value="" />
<script type="text/javascript">
<!--
document.write("<input type=\"button\" name=\"bff\" onclick=\"browseforfolder('main.actmoveto');\" value=\" Choisir un r&eacute;pertoire \" class=\"bff\" />");
//  -->
</script>

</td></tr>

</table>


<?php
     switch ($p["listmode"]) {
case 0:
/* AFFICHE 1 COLONNE DETAILLEE */
reset($c);
echo "<table width=\"100%\" style=\"border: 0px\" cellpadding=\"2\" cellspacing=\"0\">";
?>
<tr><th>
<script type="text/javascript">
<!--
document.write("<input type=\"checkbox\" id=\"checkall\" value=\"1\" class=\"inb\" onclick=\"CheckAll();\" />");
//  -->
</script>
</th>
<?php if ($p["showicons"]) { ?>
<th></th>
      <?php } ?>
<th><?php __("Filename"); ?></th>
<th><?php __("Size"); ?></th>
<th><?php __("Last modification"); ?></th>
<?php if ($p["showtype"]) { ?>
<th><?php __("File Type"); ?></th>
				 <?php } ?>
<th></th>
</tr>
<?php

$col=1;
for($i=0;$i<count($c);$i++) {
$col=3-$col;
echo "<tr class=\"lst$col\">\n";
if ($c[$i]["type"]) {
echo "	<td width=\"28\"><input type=\"checkbox\" class=\"inc\" name=\"d[]\" value=\"".$c[$i]["name"]."\" /></td>";
if ($p["showicons"]) {
echo "<td width=\"28\"><img src=\"icon/".$bro->icon($c[$i]["name"])."\" width=\"16\" height=\"16\" alt=\"\" /></td>";
}
echo "<td><a href=\"";
echo "bro_editor.php?file=".urlencode($c[$i]["name"])."&amp;R=".urlencode($R);
echo "\">".htmlentities($c[$i]["name"])."</a></td>\n";
echo "	<td>".format_size($c[$i]["size"])."</td>";
echo "<td>".format_date('%3$d-%2$d-%1$d %4$d:%5$d',date("Y-m-d H:i:s",$c[$i]["date"]))."<br /></td>";
if ($p["showtype"]) {
echo "<td>"._($bro->mime($c[$i]["name"]))."</td>";
}
$vu=$bro->viewurl($R,$c[$i]["name"]);
if ($vu) {
echo "<td><a href=\"$vu\">"._("View")."</a>";
} else {
echo "<td>&nbsp;";
}
$e = $bro->is_extractable($R,$c[$i]["name"]);
if ($e) {
  echo " <a href=\"bro_main.php?actextract=1&amp;fileextract=".urlencode($c[$i]["name"])."&amp;R=".urlencode($R)."\">";
  echo _("Extract");
  echo "</a>";
}

echo "</td>\n";
} else {           // DOSSIER :
echo "	<td width=\"28\"><input type=\"checkbox\" class=\"inc\" name=\"d[]\" value=\"".htmlentities($c[$i]["name"])."\" /></td>";
if ($p["showicons"]) {
echo "<td width=\"28\"><img src=\"icon/folder.png\" width=\"16\" height=\"16\" alt=\"\" /></td>";
}
echo "<td><b><a href=\"";
echo "bro_main.php?R=".urlencode($R."/".$c[$i]["name"]);
echo "\">".htmlentities($c[$i]["name"])."/</a></b></td>\n";
echo "	<td>".format_size($c[$i]["size"])."</td>";
echo "<td>".format_date('%3$d-%2$d-%1$d %4$d:%5$d',date("Y-m-d h:i:s",$c[$i]["date"]))."<br /></td>";
if ($p["showtype"]) {
  echo "<td>"._("Folder")."</td>";
}
echo "<td>&nbsp;";
echo "</td>\n";
}

echo "</tr>\n";
}
echo "</table>";
break;
case 1:
/* AFFICHE 2 COLONNES COURTES */
reset($c);
echo "<table width=\"100%\" border=0 cellpadding=0 cellspacing=0>";
echo "<tr><td valign=\"top\" width=\"50%\">";
echo "<table width=\"100%\" border=0 cellpadding=0 cellspacing=0>";
$col=1;
for($i=0;$i<round(count($c)/2);$i++) {
$col=3-$col;
echo "<tr class=\"lst$col\">\n";
if ($c[$i]["type"]) {
echo "	<td width=\"28\"><input type=\"checkbox\" class=\"inc\" name=\"d[]\" value=\"".$c[$i]["name"]."\" /></td><td><a href=\"";
echo "bro_editor.php?file=".urlencode($c[$i]["name"])."&amp;R=".urlencode($R);
echo "\">".htmlentities($c[$i]["name"])."</a></td>\n";
echo "	<td>".format_size($c[$i]["size"])."</td><td>";
$vu=$bro->viewurl($R,$c[$i]["name"]);
if ($vu) {
echo "<td><a href=\"$vu\">"._("V")."</a>";
} else {
echo "<td>&nbsp;";
}
echo "</td>\n";
} else {
echo "	<td width=\"28\"><input TYPE=checkbox class=\"inc\" name=\"d[]\" value=\"".$c[$i]["name"]."\"></td><td><b><a href=\"";
echo "bro_main.php?R=".urlencode($R."/".$c[$i]["name"]);
echo "\">".htmlentities($c[$i]["name"])."/</a></b></td>\n";
echo "	<td>".format_size($c[$i]["size"])."</td><td>";
echo "&nbsp;";
echo "</td>\n";
}

echo "</tr>\n";
}
echo "</table>";
echo "</td><td valign=\"top\" width=\"50%\">";
echo "<table width=\"100%\" border=0 cellpadding=0 cellspacing=0>";
$col=1;
for($i=round(count($c)/2);$i<count($c);$i++) {
$col=3-$col;
echo "<tr class=\"lst$col\">\n";
if ($c[$i]["type"]) {
echo "	<td width=\"28\"><input TYPE=checkbox class=\"inc\" name=\"d[]\" value=\"".$c[$i]["name"]."\"></td><td><a href=\"";
echo "bro_editor.php?file=".urlencode($c[$i]["name"])."&amp;R=".urlencode($R);
echo "\">".htmlentities($c[$i]["name"])."</a></td>\n";
echo "	<td>".format_size($c[$i]["size"])."</td><td>";
$vu=$bro->viewurl($R,$c[$i]["name"]);
if ($vu) {
echo "<td><a href=\"$vu\">"._("V")."</a>";
} else {
echo "<td>&nbsp;";
}
echo "</td>\n";
} else {
echo "	<td width=\"28\"><input TYPE=checkbox class=\"inc\" name=\"d[]\" value=\"".$c[$i]["name"]."\"></td><td><b><a href=\"";
echo "bro_main.php?R=".urlencode($R."/".$c[$i]["name"]);
echo "\">".htmlentities($c[$i]["name"])."/</a></b></td>\n";
echo "	<td>".format_size($c[$i]["size"])."</td><td>";
echo "&nbsp;";
echo "</td>\n";
}

echo "</tr>\n";
}
echo "</table>";
echo "</td></tr>";
echo "</table>";
break;
case 2:
/* AFFICHE 3 COLONNES COURTES */
reset($c);
echo "<table width=\"100%\" border=0 cellpadding=0 cellspacing=0>";
echo "<tr><td valign=\"top\" width=\"33%\">";
echo "<table width=\"100%\" border=0 cellpadding=0 cellspacing=0>";
$col=1;
for($i=0;$i<round(count($c)/3);$i++) {
$col=3-$col;
echo "<tr class=\"lst$col\">\n";
if ($c[$i]["type"]) {
echo "	<td width=\"28\"><input TYPE=checkbox class=\"inc\" name=\"d[]\" value=\"".$c[$i]["name"]."\"></td><td><a href=\"";
echo "bro_editor.php?file=".urlencode($c[$i]["name"])."&amp;R=".urlencode($R);
echo "\">".htmlentities($c[$i]["name"])."</a></td>\n";
echo "	<td>".format_size($c[$i]["size"])."</td><td>";
$vu=$bro->viewurl($R,$c[$i]["name"]);
if ($vu) {
echo "<td><a href=\"$vu\">"._("V")."</a>";
} else {
echo "<td>&nbsp;";
}
echo "</td>\n";
} else {
echo "	<td width=\"28\"><input TYPE=checkbox class=\"inc\" name=\"d[]\" value=\"".$c[$i]["name"]."\"></td><td><b><a href=\"";
echo "bro_main.php?R=".urlencode($R."/".$c[$i]["name"]);
echo "\">".htmlentities($c[$i]["name"])."/</a></b></td>\n";
echo "	<td>".format_size($c[$i]["size"])."</td><td>";
echo "&nbsp;";
echo "</td>\n";
}

echo "</tr>\n";
}
echo "</table>";
echo "</td><td valign=\"top\" width=\"33%\">";
echo "<table width=\"100%\" border=0 cellpadding=0 cellspacing=0>";
$col=1;
for($i=round(count($c)/3);$i<round(2*count($c)/3);$i++) {
$col=3-$col;
echo "<tr class=\"lst$col\">\n";
if ($c[$i]["type"]) {
echo "	<td width=\"28\"><input TYPE=checkbox class=\"inc\" name=\"d[]\" value=\"".$c[$i]["name"]."\"></td><td><a href=\"";
echo "bro_editor.php?file=".urlencode($c[$i]["name"])."&amp;R=".urlencode($R);
echo "\">".htmlentities($c[$i]["name"])."</a></td>\n";
echo "	<td>".format_size($c[$i]["size"])."</td><td>";
$vu=$bro->viewurl($R,$c[$i]["name"]);
if ($vu) {
echo "<td><a href=\"$vu\">"._("V")."</a>";
} else {
echo "<td>&nbsp;";
}

echo "</td>\n";
} else {
echo "	<td width=\"28\"><input TYPE=checkbox class=\"inc\" name=\"d[]\" value=\"".$c[$i]["name"]."\"></td><td><b><a href=\"";
echo "bro_main.php?R=".urlencode($R."/".$c[$i]["name"]);
echo "\">".htmlentities($c[$i]["name"])."/</a></b></td>\n";
echo "	<td>".format_size($c[$i]["size"])."</td><td>";
echo "&nbsp;";
echo "</td>\n";
}

echo "</tr>\n";
}
echo "</table>";
echo "</td><td valign=\"top\" width=\"33%\">";
echo "<table width=\"100%\" border=0 cellpadding=0 cellspacing=0>";
$col=1;
for($i=round(2*count($c)/3);$i<count($c);$i++) {
$col=3-$col;
echo "<tr class=\"lst$col\">\n";
if ($c[$i]["type"]) {
echo "	<td width=\"28\"><input TYPE=checkbox class=\"inc\" name=\"d[]\" value=\"".$c[$i]["name"]."\"></td><td><a href=\"";
echo "bro_editor.php?file=".urlencode($c[$i]["name"])."&amp;R=".urlencode($R);
echo "\">".htmlentities($c[$i]["name"])."</a></td>\n";
echo "	<td>".format_size($c[$i]["size"])."</td><td>";
$vu=$bro->viewurl($R,$c[$i]["name"]);
if ($vu) {
echo "<td><a href=\"$vu\">"._("View")."</a>";
} else {
echo "<td>&nbsp;";
}
echo "</td>\n";
} else {
echo "	<td width=\"28\"><input TYPE=checkbox class=\"inc\"  name=\"d[]\" value=\"".$c[$i]["name"]."\"></td><td><b><a href=\"";
echo "bro_main.php?R=".urlencode($R."/".$c[$i]["name"]);
echo "\">".htmlentities($c[$i]["name"])."/</a></b></td>\n";
echo "	<td>".format_size($c[$i]["size"])."</td><td>";
echo "&nbsp;";
echo "</td>\n";
}

echo "</tr>\n";
}
echo "</table>";
echo "</td></tr>";
echo "</table>";
break;
}
?>
     </form>
<?php
	 } // is there any files here ?
else {
  echo "<p class=\"error\">"._("No files in this folder")."</p>";
}
?>

     </td></tr>
     <tr><td colspan="2" style="">

     <p>&nbsp;</p>

<p>
<span class="ina"><a href="bro_main.php?R=<?php echo $R; ?>&showdirsize=1"><?php __("Show size of directories"); ?></a></span> <?php __("(slow)"); ?>
</p><p>&nbsp;</p><p>
<span class="ina"><?php
if ($hta->is_protected($R)) {
echo "<a href=\"hta_edit.php?dir=".(($R)?$R:"/")."\">"._("Edit this folder's protection")."</a>";
}
else {
  echo "<a href=\"hta_add.php?value=".(($R)?$R:"/")."\">"._("Protect this folder")."</a>";
}
?></span> <?php __("with a login and a password"); ?>
</p><p>
<span class="ina">
  <a href="bro_tgzdown.php?dir=<?php echo $R; ?>"><?php __("Download this folder"); ?></a>
</span> &nbsp; 
  <?php printf(_("as a %s file"),$bro->l_tgz[$p["downfmt"]]); ?>
</span>
</p>	
     <?php

     if ($id=$ftp->is_ftp($R)) {
?>
<span class="ina">
	 <a href="ftp_edit.php?id=<?php ehe($id); ?>"><?php __("Edit the ftp account"); ?></a> 
</span> &nbsp; <?php __("that exists in this folder"); ?>
<?php
}
else {
?>
<span class="ina">
    <a href="ftp_add.php?dir=<?php ehe($R); ?>"><?php __("Create an ftp account in this folder"); ?></a>
</span> &nbsp;
<?php
}

?>
<p>&nbsp;</p>
<p>
<span class="ina">
  <a href="bro_pref.php"><?php __("Configure the file editor"); ?></a>
</span> 
</p>
</td></tr></table>
<?php include_once("foot.php"); ?>
