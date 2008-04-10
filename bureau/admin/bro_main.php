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
 ----------------------------------------------------------------------
*/
require_once("../class/config.php");

$p=$bro->GetPrefs();
if (!$R && $p["golastdir"]) {
  $R=$p["lastdir"];
}
$R=$bro->convertabsolute($R,1);
// on fait ?
if ($formu) {
  switch ($formu) {
  case 1:  // Créer le répertoire $R.$nomfich
    if (!$bro->CreateDir($R,$nomfich)) {
      print $err->errstr();
    }
    $p=$bro->GetPrefs();
    break;
  case 6: // Créer le fichier $R.$nomfich
    if (!$bro->CreateFile($R,$nomfich)) {
      print $err->errstr();
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
          print $err->errstr();
        }
      } elseif (!$cancel && is_array($d)) {
        include("head.php");
?>
</head>
<body>
  <h3><?php printf(_("Deleting files and/or directories")); ?> : </h3>
  <form action="bro_main.php" method="post">  
    <input type="hidden" name="formu" value="2" />
    <input type="hidden" name="actdel" value="1" />
    <input type="hidden" name="R" value="<?php echo $R?>" />
    <p class="error"><?php __("WARNING: Confirm the deletion of this files"); ?></p>
<?php foreach($d as $file){ ?>
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
        print $err->errstr();
      }
    }
    if ($actmove) {
      if (!$bro->MoveFile($d,$R,$actmoveto)) {
        print $err->errstr();
      }
    }
    break;
  case 4:  // Renommage Effectif...
    if (!$bro->RenameFile($R,$o,$d)) { // Rename $R (directory) $o (old) $d (new) names
      print $err->errstr();
    }
    break;
  case 3:  // Upload de fichier...
    if (!$bro->UploadFile($R)) {
      print $err->errstr();
    }
    break;
  case 7:  // Changement de permissions [ML]
    if (!$bro->ChangePermissions($R, $d, $perm)) {
      print $err->errstr();
    }
    break;
  }
}

if ($actextract) {
  print _("extracting...");
  if ($bro->ExtractFile($R. '/' . $file, $R)) {
    print $err->errstr();
    print _("failed");
  } else {
    print _("done");
  }
}

/* Creation de la liste des fichiers courants */
$c=$bro->filelist($R);
if ($c===false) $error=$err->errstr();

include("head.php");
?>
</head>
<body>

<div id="browser">
<h3><?php __("File browser"); ?></h3>
<hr />
<table width="100%"><tr><td valign="top">
<a href="bro_main.php?R=/"><?php echo $mem->user["login"]; ?></a>&nbsp;/&nbsp;<?php echo $bro->PathList($R,"bro_main.php") ?><br />
<small>
<?php if ($error) echo "<font color=\"red\">$error</font>"; ?>
</td><td valign="top" align="right">

<form action="bro_main.php" method="post" name="nn" id="nn">
<input type="hidden" name="R" value="<?php echo $R; ?>" />
<table id="add-file"><tr>
<td><input type="text" class="int" name="nomfich" size="22" maxlength="255" /></td>
<td><input type="submit" class="inb" value="<?php __("Create"); ?>" /></td>
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
if ($formu==2 && $actrename && count($d)) {
  echo "<form action=\"bro_main.php\" method=\"post\">\n";
  echo "<input type=\"hidden\" name=\"R\" value=\"$R\" />\n";
  echo "<input type=\"hidden\" name=\"formu\" value=\"4\" />\n";
  echo "<p>"._("Rename")."</p>";
  for ($i=0;$i<count($d);$i++) {
    $d[$i]=ssla($d[$i]);
    echo "<table>";
    echo "<tr><td>"._("Old Name:")."</td><td><input type=\"hidden\" name=\"o[$i]\" value=\"".$d[$i]."\" />".$d[$i]."</td></tr>";
    echo "<tr><td>"._("New Name:")."</td><td><input type=\"text\" class=\"int\" style=\"width: 350px\" name=\"d[$i]\" value=\"".$d[$i]."\" /></td></tr>";
    echo "</table>";
  }
  echo "<p><input type=\"submit\" class=\"inb\" name=\"submit\" value=\""._("Rename")."\" /></p>";
  echo "</form>\n";
  echo "<hr />\n";
}

/* [ML] Changer les permissions : */
if ($formu==2 && $_REQUEST['actperms'] && count($d)) {
  echo "<form action=\"bro_main.php\" method=\"post\">\n";
  echo "<input type=\"hidden\" name=\"R\" value=\"$R\" />\n";
  echo "<input type=\"hidden\" name=\"formu\" value=\"7\" />\n";
  echo "<p>"._("Permissions")."</p>";

  $tmp_absdir = $bro->convertabsolute($R,0);

  echo "<table border=\"1\">"; // FIXME, marco, ajouter classe css?
  echo "<tr>";
  // echo "<th>" . 'File' . "</th><th>&nbsp;</th><th>Owner</th><th>Group</th><th>Other</th>"; // FIXME , i18n
  echo "<th>" . 'File' . "</th><th>Permissions</th>"; // FIXME, i18n
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
    // echo "<label>read <input type=\"checkbox\" name=\"perm[$i][r]\" value=\"1\" ". (($modes & 0000400) ? 'checked="checked"' : '') ." />";
    echo "<label>write <input type=\"checkbox\" name=\"perm[$i][w]\" value=\"1\" ". (($modes & 0000200) ? 'checked="checked"' : '') ." />";
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
<hr />
<script type="text/javascript">
<!--
document.write("<input type=\"button\" value=\"<?php __("all/none"); ?>\" class=\"inb\" onclick=\"CheckAll();\" />");
//  -->
</script>
<input type="submit" class="inb" name="actdel" value="<?php __("Delete"); ?>" />

<input type="submit" class="inb" name="actrename" value="<?php __("Rename"); ?>" />
<input type="submit" class="inb" name="actperms" value="<?php __("Permissions"); ?>" /> <!-- [ML] -->
&nbsp;&nbsp;&nbsp;
<input type="submit" class="inb" name="actcopy" value="<?php __("Copy to"); ?>" />

<input type="submit" class="inb" name="actmove" value="<?php __("Move to"); ?>" />&nbsp;:&nbsp;<input type="text" class="int" name="actmoveto" value="" />
<script type="text/javascript">
<!--
document.write("<input type=\"button\" name=\"bff\" onclick=\"browseforfolder('main.actmoveto');\" value=\" ... \" class=\"inb\" />");
//  -->
</script>
     <hr />



<?php
     switch ($p["listmode"]) {
case 0:
/* AFFICHE 1 COLONNE DETAILLEE */
reset($c);
echo "<table width=\"100%\" style=\"border: 0px\" cellpadding=\"0\" cellspacing=\"0\">";
$col=1;
for($i=0;$i<count($c);$i++) {
$col=3-$col;
echo "<tr class=\"lst$col\">\n";
if ($c[$i]["type"]) {
echo "	<td width=\"28\"><input type=\"checkbox\" class=\"inc\" name=\"d[]\" value=\"".htmlentities($c[$i]["name"])."\" /></td>";
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
echo "<a href=\"bro_main.php?actextract=1&file=".urlencode($c[$i]["name"])."&amp;R=".urlencode($R)."\">";
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
echo "<td>Dossier</td>";
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
     <tr><td colspan="2">


     <form action="bro_main.php" enctype="multipart/form-data" method="post">
     <input type="hidden" name="R" value="<?php echo $R; ?>" />
     <input type="hidden" name="formu" value="3" />
     <hr />
     <?php __("Import this file"); ?>&nbsp;&nbsp;<input class="int" name="userfile" type="file" />
     <input type="hidden" name="MAX_FILE_SIZE" value="10000000" />
     <input type="submit" class="inb" name="upload" value="<?php __("Send"); ?>" />
     <hr />
     </form>
     <p>&nbsp;</p>

     <?php


     if ($id=$ftp->is_ftp($R)) {
echo _("There is an ftp account in this folder")." <a href=\"ftp_edit?id=".urlencode($id)."\">"._("Click here to edit this ftp account.")."</a><br />";
}
else {
echo "<a href=\"ftp_add.php?dir=".urlencode($R)."\">"._("Click here to create an ftp account in this folder.")."</a><br />";
}

if ($hta->is_protected($R)) {
echo "<a href=\"hta_edit.php?dir=$R\">"._("This folder has restricted access")."</a><br />";
}
else {
echo "<a href=\"hta_add.php?value=$R\">"._("Click here to protect this folder with login/password")."</a><br />";
}
?>
<br />
<a href="bro_tgzdown.php?dir=<?php echo $R; ?>"><?php __("Download"); ?></a> <?php __("this folder and its subfolders as a compressed file"); ?> (<?php echo $bro->l_tgz[$p["downfmt"]]; ?>)<br />
<a href="bro_pref.php"><?php __("Configure the file browser"); ?></a><br />

</td></tr></table>

</body>
</html>
