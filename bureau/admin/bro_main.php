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
 * A file browser / manager for AlternC
 * Warning: complex spaghetti-style code below.
 * allow an account user to browse files, move, copy, upload, rename,
 * and set permissions
 * also, uncompress tarballs and zips, and import SQL files
 * 
 * @copyright AlternC-Team 2000-2017 https://alternc.com/  
 */

 require_once("../class/config.php");
include_once ("head.php");

$fields = array (
    "R"           => array ("request", "string", ""),
    "o"           => array ("request", "array", array()),
    "d"           => array ("request", "array", array()),
    "perm"        => array ("post",    "array", array()),
    "formu"       => array ("post", "integer", ""),
    "actextract"  => array ("request", "string", ""),
    "fileextract" => array ("request", "string", ""),
    "actperms"    => array ("post", "string", ""),
    "actdel"      => array ("post", "string", ""),
    "actcopy"     => array ("post", "string", ""),
    "actrename"   => array ("post", "string", ""),
    "actmove"     => array ("post", "string", ""),
    "actmoveto"   => array ("post", "string", ""),
    "nomfich"     => array ("post", "string", ""),
    "del_confirm" => array ("request", "string", ""),
    "cancel"      => array ("request", "string", ""),
    "showdirsize" => array ("request", "integer", "0"),
    "nomfich"     => array ("post", "string", ""),
    );

## does not intend to edit oversize files.
$memory_limit=ini_get("memory_limit");
if (preg_match("#([mk])#i", $memory_limit, $out))
	$memory_limit=$memory_limit*1024*($out[1]=="M"?1024:1);

getFields($fields);

$p=$bro->GetPrefs();
if (! isset($R)) $R='';
if (!$R && $p["golastdir"]) {
  $R=$p["lastdir"];
}
$R=$bro->convertabsolute($R,1);
// on fait ?
if (!empty($formu) && $formu) {
  $absolute = $bro->convertabsolute($R, false);
  switch ($formu) {
    case 1:  // Create the folder $R.$nomfich
      if ($bro->CreateDir($R,$nomfich)) {
        $msg->raise("INFO", "bro", __("The folder '%s' was successfully created", "alternc", true), $nomfich);
      }
      $p=$bro->GetPrefs();
      break;
    case 6: // Create the file $R.$nomfich
      if ($bro->CreateFile($R,$nomfich)) {
        $msg->raise("INFO", "bro", __("The file '%s' was successfully created", "alternc", true), $nomfich);
      }
      $p=$bro->GetPrefs();
      if ($p["createfile"]==1) {
        $editfile=$nomfich;
        include("bro_editor.php");
        exit();
      }
      break;
    case 2:  // act vaut Supprimer Copier ou Renommer.
      if ($actdel) {
        if (!empty($del_confirm) ) { 
          if ($bro->DeleteFile($d,$R)) {
	    foreach ($d as $v) {
	      if (is_dir($absolute . "/" . $v))
                $msg->raise("INFO", "bro", __("The folder '%s' was successfully deleted", "alternc", true), $v);
	      else
                $msg->raise("INFO", "bro", __("The file '%s' was successfully deleted", "alternc", true), $v);
	    }
          }
        } elseif (empty($cancel) && count($d)) {
          include_once("head.php");
          ?>
            <h3><?php printf(__("Deleting files and/or directories", "alternc", true)); ?> : </h3>
            <form action="bro_main.php" method="post" name="main" id="main">  
 <?php csrf_get(); ?>
            <input type="hidden" name="formu" value="2" />
            <input type="hidden" name="actdel" value="1" />
            <input type="hidden" name="R" value="<?php ehe($R)?>" />
            <p class="alert alert-warning"><?php __("WARNING: Confirm the deletion of this files"); ?></p>
            <h2><?php echo $mem->user["login"].$R."/"; ?></h2>
            <ul>
            <?php foreach($d as $editfile){ ?>
          <li><b> <?php ehe($editfile); ?></b></li>
              <input type="hidden" name="d[]" value="<?php ehe($editfile); ?>" />
            <?php } ?>
            </ul>
                <blockquote>
                <input type="submit" class="inb ok" name="del_confirm" value="<?php __("Yes, delete those files/folders"); ?>" />&nbsp;&nbsp;
          <input type="submit" class="inb cancel" name="cancel" value="<?php __("No, don't delete those files/folders");  ?>" />
            </blockquote>
            </form>
            <?php
            include_once("foot.php");
          exit();
        }
      }
      if ($actcopy && count($d)) {
        if ($bro->CopyFile($d,$R,$actmoveto)) {
	  if (count($d) == 1) {
	    if (is_dir($absolute . "/" . $d[0]))
	      $msg->raise("INFO", "bro", __("The folder '%s' was successfully copied to '%s'", "alternc", true), array($d[0], $actmoveto));
	    else
	      $msg->raise("INFO", "bro", __("The file '%s' was successfully copied to '%s'", "alternc", true), array($d[0], $actmoveto));
	  } else
            $msg->raise("INFO", "bro", __("The files / folders were successfully copied", "alternc", true));
        }
      }
      if ($actmove && count($d)) {
        if ($bro->MoveFile($d,$R,$actmoveto)) {
	  if (count($d) == 1) {
	    if (is_dir($absolute . "/" . $d[0]))
	      $msg->raise("INFO", "bro", __("The folder '%s' was successfully moved to '%s'", "alternc", true), array($d[0], $actmoveto));
	    else
	      $msg->raise("INFO", "bro", __("The file '%s' was successfully moved to '%s'", "alternc", true), array($d[0], $actmoveto));
	  } else
            $msg->raise("INFO", "bro", __("The files / folders were successfully moved", "alternc", true));
        }
      }
      break;
    case 4:  // Renommage Effectif...
      if ($bro->RenameFile($R,$o,$d)) { // Rename $R (directory) $o (old) $d (new) names
	if (count($d) == 1) {
	  if (is_dir($absolute . "/" . $d[0]))
	    $msg->raise("INFO", "bro", __("The folder '%s' was successfully renamed to '%s'", "alternc", true), array($o[0], $d[0]));
	  else
	    $msg->raise("INFO", "bro", __("The file '%s' was successfully renamed to '%s'", "alternc", true), array($o[0], $d[0]));
	} else
          $msg->raise("INFO", "bro", __("The files / folders were successfully renamed", "alternc", true));
      } 
      break;
    case 3:  // Upload de fichier...
      if ($bro->UploadFile($R)) {
        $msg->raise("INFO", "bro", __("The file '%s' was successfully uploaded", "alternc", true), $_FILES['userfile']['name']);
      }
      break;
    case 7:  // Changement de permissions [ML]
      if ($bro->ChangePermissions($R, $d, $perm)) {
	$msg->raise("INFO", "bro", __("The permissions were successfully set", "alternc", true));
      }
      break;
  }
}

if (isset($actextract) && $actextract) {
  if ($bro->ExtractFile($R. '/' . $fileextract, $R)) {
    $msg->raise("INFO", "bro", __("The extraction of the file '%s' succeeded", "alternc", true), $fileextract);
  }
}

?>
<h3><?php __("File browser"); ?></h3>
<table border="0" width="100%" cellspacing="0">
<tr><td>

<hr />

<p class="breadcrumb">
<?php __("Path"); ?> / <a href="bro_main.php?R=/"><?php echo $mem->user["login"]; ?></a>&nbsp;/&nbsp;<?php echo $bro->PathList($R,"bro_main.php") ?>
</p>

<?php
/* Creation de la liste des fichiers courants */
$c=$bro->filelist($R, $showdirsize );
if ($c===false) {
  echo $msg->msg_html_all();
  require_once('foot.php');
  exit;
}

echo $msg->msg_html_all();
?>

<table><tr>
<td class="formcell">

<form action="bro_main.php" enctype="multipart/form-data" method="post">
   <?php csrf_get(); ?>
<input type="hidden" name="R" value="<?php ehe($R); ?>" />
<input type="hidden" name="formu" value="3" />

<?php __("Send one file:"); ?><br />
<input class="int" name="userfile" type="file" />
<br />
<input type="submit" id="sendthisfile" class="ina" value="<?php __("Send this file"); ?>" />
<?php echo sprintf(__("Warning: max size: %s", "alternc", true),$bro->getMaxAllowedUploadSize() ); ?>
<?php __("(If you upload a compressed file, <br />you will be able to uncompress it after.)"); ?></form>

</td>
<td style="width: 20px">&nbsp;</td>
<td class="formcell">

<?php __("New file or folder:"); ?><br />
<form action="bro_main.php" method="post" name="nn" id="nn">
   <?php csrf_get(); ?>
<input type="hidden" name="R" value="<?php ehe($R); ?>" />
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
/* ' */
/* Rename / Copy / Move files: */
if (isset($formu) && $formu==2 && isset($actrename) && $actrename && count($d)) {
  echo "<table cellpadding=\"6\">\n";
  echo "<form action=\"bro_main.php\" method=\"post\">\n";
  csrf_get(); 
  echo "<input type=\"hidden\" name=\"R\" value=\"".ehe($R,false)."\" />\n";
  echo "<input type=\"hidden\" name=\"formu\" value=\"4\" />\n";
  echo "<tr><th colspan=\"2\">".__("Rename", "alternc", true)."</th></tr>";
  for ($i=0;$i<count($d);$i++) {
    $d[$i]=ssla($d[$i]);
    echo "<tr><td><input type=\"hidden\" name=\"o[$i]\" value=\"".ehe($d[$i],false)."\" />".ehe($d[$i],false)."</td>";
    echo "<td><input type=\"text\" class=\"int\" name=\"d[$i]\" value=\"".ehe($d[$i],false)."\" /></td></tr>";
  }
  echo "<tr><td colspan=\"2\" align=\"center\"><input type=\"submit\" class=\"inb\" name=\"submit\" value=\"".__("Rename", "alternc", true)."\" /></td></tr>";
  echo "</table></form>\n";
  echo "<hr />\n";
}

/* [ML] Changer les permissions : */
if ($formu==2 && ! (empty($actperms)) && count($d)) {
  echo "<form action=\"bro_main.php\" method=\"post\">\n";
  csrf_get();
  echo "<input type=\"hidden\" name=\"R\" value=\"".ehe($R,false)."\" />\n";
  echo "<input type=\"hidden\" name=\"formu\" value=\"7\" />\n";
  echo "<p>".__("Permissions", "alternc", true)."</p>";

  $tmp_absdir = $bro->convertabsolute($R,0);

  echo "<table border=\"1\" cellpadding=\"4\" cellspacing=\"0\">";
  echo "<tr>";
  echo "<th>" . __("File", "alternc", true) . "</th><th>".__("Permissions", "alternc", true)."</th>"; 
  echo "</tr>";

  for ($i=0;$i<count($d);$i++) {
    $d[$i]=ssla($d[$i]);
    $stats = stat($tmp_absdir . '/' . $d[$i]);
    $modes = $stats[2];

    echo "<tr>";
    echo "<td>".ehe($d[$i],false)."</td>";

    // Owner
    echo "<td>";
    echo "<input type=\"hidden\" name=\"d[$i]\" value=\"".ehe($d[$i],false)."\" />";
    echo "<label for=\"permw$i\">".__("write", "alternc", true)."</label> <input type=\"checkbox\" id=\"permw$i\" name=\"perm[$i][w]\" value=\"1\" ". (($modes & 0000200) ? 'checked="checked"' : '') ." />";
    echo "</td>";

    echo "</tr>";
  }

  echo "</table>";

  echo "<p><input type=\"submit\" class=\"inb\" name=\"submit\" value=\"".__("Change permissions", "alternc", true)."\" /></p>";
  echo "</form>\n";
  echo "<hr />\n";
}

/* We draw the file list and button bar only if there is files here ! */
if (count($c)) {

  ?>
    <form action="bro_main.php" method="post" name="main" id="main">
   <?php csrf_get(); ?>
    <input type="hidden" name="R" value="<?php ehe($R); ?>" />
    <input type="hidden" name="formu" value="2" />

    <br />


    <table width="100%" style="border: 0px">
    <tr><td class="" style="padding: 4px 4px 8px 4px">

    <input type="submit" class="ina" name="actdel" value="<?php __("Delete"); ?>" />
    <input type="submit" class="ina" name="actrename" value="<?php __("Rename"); ?>" />
    <input type="submit" class="ina" name="actperms" value="<?php __("Permissions"); ?>" /> 
    &nbsp; |&nbsp;
  <input type="submit" class="ina" name="actcopy" value="<?php __("Copy"); ?>" onClick=" return actmoveto_not_empty();"/>
    <input type="submit" class="ina" name="actmove" value="<?php __("Move"); ?>" onClick=" return actmoveto_not_empty();"/>
    <?php __("To"); ?> 
    <input type="text" class="int" id="actmoveto" name="actmoveto" value="" />
    <?php display_browser( "" , "actmoveto" ); ?>

    </td></tr>

    </table>

<script type="text/javascript">
function actmoveto_not_empty() {
  if ( $('#actmoveto').val() =='' ) {
    alert("<?php __("Please select a destination folder");?>");
    return false;
  }
  return true;
}
</script>



    <?php
    switch ($p["listmode"]) {
      case 0:
        /* AFFICHE 1 COLONNE DETAILLEE */
        reset($c);
        echo "<table width=\"100%\" id='tab_files_w_details' class=\"tlist\" style=\"border: 0px\" cellpadding=\"2\" cellspacing=\"0\"><thead>";
        ?>
          <tr><th>
            <input type="checkbox" id="checkall" value="1" class="inb" onclick="CheckAll();" />
          </th>
          <?php if ($p["showicons"]) { ?>
              <th style="text-align: center;"><?php if (!empty($R)) { echo $bro->PathList($R,"bro_main.php",true);  }?></th>
              <?php } ?>
              <th><?php __("Filename"); ?></th>
              <th><?php __("Size"); ?></th>
              <th><?php __("Last modification"); ?></th>
              <?php if ($p["showtype"]) { ?>
                <th><?php __("File Type"); ?></th>
                  <?php } ?>
                  <th></th>
                  </tr></thead><tbody>
<?php

        for($i=0;$i<count($c);$i++) {
          echo "<tr class=\"lst\">\n";
          if ($c[$i]["type"]) {
              echo "  <td width=\"28\"><input type=\"checkbox\" class=\"inc\" name=\"d[]\" value=\"".ehe($c[$i]["name"],false)."\" /></td>";
            if ($p["showicons"]) {
              echo "<td style='text-align: center;' width=\"28\"><img src=\"icon/".$bro->icon($c[$i]["name"])."\" width=\"16\" height=\"16\" alt=\"\" /></td>";
            }
            echo "<td><a href=\"";
            $canedit = $bro->can_edit($R,$c[$i]["name"]);
            if ($canedit&&($c[$i]["size"]<$memory_limit)) {
              echo "bro_editor.php?editfile=".urlencode($c[$i]["name"])."&amp;R=".urlencode($R);
            } else {
              echo "bro_downloadfile.php?dir=".urlencode($R)."&amp;file=".urlencode($c[$i]["name"]);
            }
            echo "\">"; ehe($c[$i]["name"]); 
            echo"</a>";
            if (!($c[$i]["permissions"] & 0000200)) {
                echo " (<a href=\"bro_main.php?actperms=Permissions&R=".urlencode($R)."&amp;formu=2&amp;d[]=".urlencode($c[$i]["name"])."\">".__("protected", "alternc", true)."</a>)";
            }
            echo "</td>\n";
            echo "  <td data-sort-value=\"".$c[$i]["size"]."\">".format_size($c[$i]["size"])."</td>";
            echo "<td data-sort-value=\"".$c[$i]["date"]."\">".format_date(__('%3$d-%2$d-%1$d %4$d:%5$d', "alternc", true),date("Y-m-d H:i:s",$c[$i]["date"]))."<br /></td>";
            if ($p["showtype"]) {
              echo "<td>".__($bro->mime($c[$i]["name"], "alternc", true))."</td>";
            }
            $vu=$bro->viewurl($R,$c[$i]["name"]);
            if ($vu) {
              echo "<td><a href=\"$vu\">".__("View", "alternc", true)."</a>";
            } else {
              echo "<td>&nbsp;";
            }
            $e = $bro->is_extractable($c[$i]["name"]);
            if ($e) {
              echo " <a href=\"bro_main.php?actextract=1&amp;fileextract=".urlencode($c[$i]["name"])."&amp;R=".urlencode($R)."\">";
              echo __("Extract", "alternc", true);
              echo "</a>";
            }
            $ez = $bro->is_sqlfile($c[$i]["name"]);
            if ($ez) {
              echo " <a href=\"javascript:;\" onClick=\"$('#rest_db_$i').toggle();\">";
              echo __("Restore SQL", "alternc", true);
              echo "</a>";
              echo "<div id='rest_db_$i' style='display:none;'><fieldset><legend>".__("Restore SQL", "alternc", true)."</legend>".__("In which database to you want to restore this dump?", "alternc", true);
              echo "<br/>";
              echo "<input type='hidden' name ='filename' value='".ehe($R."/".$c[$i]["name"],false)."' />";
              $dbl=array(); foreach ($mysql->get_dblist() as $v) { $dbl[]=$v['db'];}
              echo "<select id='db_name_$i'>"; eoption($dbl,'',true); echo "</select>" ;
              echo "<a href='javascript:;' onClick='window.location=\"sql_restore.php?filename=".eue($R."/".$c[$i]["name"],false)."&amp;id=\"+encodeURIComponent($(\"#db_name_$i\").val()) ;'>".__("Restore it", "alternc", true)."</a>";
              echo "</fieldset></div>";
            }

            echo "</td>\n";
          } else {           // DOSSIER :
              echo "  <td width=\"28\"><input type=\"checkbox\" class=\"inc\" name=\"d[]\" value=\"".ehe($c[$i]["name"],false)."\" /></td>";
            if ($p["showicons"]) {
              echo "<td width=\"28\" style='text-align: center;'><img src=\"icon/folder.png\" width=\"16\" height=\"16\" alt=\"\" /></td>";
            }
            echo "<td><b><a href=\"";
            echo "bro_main.php?R=".eue($R."/".$c[$i]["name"],false);
            echo "\">"; ehe($c[$i]["name"]); echo "/</a></b></td>\n";
            echo "  <td data-sort-value=\"".$c[$i]["size"]."\">".format_size($c[$i]["size"])."</td>";
            echo "<td data-sort-value=\"".$c[$i]["date"]."\">".format_date(__('%3$d-%2$d-%1$d %4$d:%5$d', "alternc", true),date("Y-m-d h:i:s",$c[$i]["date"]))."<br /></td>";
            if ($p["showtype"]) {
              echo "<td>".__("Folder", "alternc", true)."</td>";
            }
            echo "<td>&nbsp;";
            echo "</td>\n";
          }

          echo "</tr>\n";
        }
        echo "</tbody></table>";
        break;
      case 1:
        /* AFFICHE 2 COLONNES COURTES */
        reset($c);
        echo "<table width=\"100%\" border=0 cellpadding=0 cellspacing=0>";
        echo "<tr><td valign=\"top\" width=\"50%\">";
        echo "<table width=\"100%\" border=0 cellpadding=0 cellspacing=0>";
        for($i=0;$i<round(count($c)/2);$i++) {
          echo "<tr class=\"lst\">\n";
          if ($c[$i]["type"]) {
              echo "  <td width=\"28\"><input type=\"checkbox\" class=\"inc\" name=\"d[]\" value=\"".ehe($c[$i]["name"],false)."\" /></td>";
            echo "<td><a href=\"";
            $canedit = $bro->can_edit($R,$c[$i]["name"]);
            if ($canedit && ($c[$i]["size"]<$memory_limit)) {
                echo "bro_editor.php?editfile=".eue($c[$i]["name"],false)."&amp;R=".eue($R,false);
            } else {
                echo "bro_downloadfile.php?dir=".eue($R,false)."&amp;file=".eue($c[$i]["name"],false);
            }
            echo "\">"; ehe($c[$i]["name"]); 
            echo "</a></td>\n";
            echo "  <td>".format_size($c[$i]["size"])."</td><td>";
            $vu=$bro->viewurl($R,$c[$i]["name"]);
            if ($vu) {
              echo "<td><a href=\"$vu\">".__("V", "alternc", true)."</a>";
            } else {
              echo "<td>&nbsp;";
            }
            echo "</td>\n";
          } else {
              echo "  <td width=\"28\"><input type=\"checkbox\" class=\"inc\" name=\"d[]\" value=\"".ehe($c[$i]["name"],false)."\"></td><td><b><a href=\"";
              echo "bro_main.php?R=".eue($R."/".$c[$i]["name"],false);
              echo "\">".ehe($c[$i]["name"],false)."/</a></b></td>\n";
            echo "  <td>".format_size($c[$i]["size"])."</td><td>";
            echo "&nbsp;";
            echo "</td>\n";
          }

          echo "</tr>\n";
        }
        echo "</table>";
        echo "</td><td valign=\"top\" width=\"50%\">";
        echo "<table width=\"100%\" border=0 cellpadding=0 cellspacing=0>";
        for($i=round(count($c)/2);$i<count($c);$i++) {
          echo "<tr class=\"lst\">\n";
          if ($c[$i]["type"]) {
              echo "  <td width=\"28\"><input type=\"checkbox\" class=\"inc\" name=\"d[]\" value=\"".ehe($c[$i]["name"],false)."\"></td><td><a href=\"";
            $canedit = $bro->can_edit($R,$c[$i]["name"]);
            if ($canedit && ($c[$i]["size"]<$memory_limit)) {
              echo "bro_editor.php?editfile=".urlencode($c[$i]["name"])."&amp;R=".urlencode($R);
            } else {
              echo "bro_downloadfile.php?dir=".urlencode($R)."&amp;file=".urlencode($c[$i]["name"]);
            }
            echo "\">"; ehe($c[$i]["name"]); 
            echo "</a></td>\n";
            echo "  <td>".format_size($c[$i]["size"])."</td><td>";
            $vu=$bro->viewurl($R,$c[$i]["name"]);
            if ($vu) {
              echo "<td><a href=\"$vu\">".__("V", "alternc", true)."</a>";
            } else {
              echo "<td>&nbsp;";
            }
            echo "</td>\n";
          } else {
              echo "  <td width=\"28\"><input type=\"checkbox\" class=\"inc\" name=\"d[]\" value=\"".ehe($c[$i]["name"],false)."\"></td><td><b><a href=\"";
              echo "bro_main.php?R=".eue($R."/".$c[$i]["name"],false);
            echo "\">".ehe($c[$i]["name"],false)."/</a></b></td>\n";
            echo "  <td>".format_size($c[$i]["size"])."</td><td>";
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
        for($i=0;$i<round(count($c)/3);$i++) {
          echo "<tr class=\"lst\">\n";
          if ($c[$i]["type"]) {
              echo "  <td width=\"28\"><input type=\"checkbox\" class=\"inc\" name=\"d[]\" value=\"".ehe($c[$i]["name"],false)."\"></td><td><a href=\"";
            $canedit = $bro->can_edit($R,$c[$i]["name"]);
            if ($canedit&&($c[$i]["size"]<$memory_limit)) {
              echo "bro_editor.php?editfile=".eue($c[$i]["name"],false)."&amp;R=".eue($R,false);
            } else {
              echo "bro_downloadfile.php?dir=".eue($R,false)."&amp;file=".eue($c[$i]["name"],false);
            }
            echo "\">"; ehe($c[$i]["name"],false); 
            echo "</a></td>\n";
            echo "  <td>".format_size($c[$i]["size"])."</td><td>";
            $vu=$bro->viewurl($R,$c[$i]["name"]);
            if ($vu) {
              echo "<td><a href=\"$vu\">".__("V", "alternc", true)."</a>";
            } else {
              echo "<td>&nbsp;";
            }
            echo "</td>\n";
          } else {
              echo "  <td width=\"28\"><input type=\"checkbox\" class=\"inc\" name=\"d[]\" value=\"".ehe($c[$i]["name"],false)."\"></td><td><b><a href=\"";
            echo "bro_main.php?R=".eue($R."/".$c[$i]["name"],false);
            echo "\">".ehe($c[$i]["name"],false)."/</a></b></td>\n";
            echo "  <td>".format_size($c[$i]["size"])."</td><td>";
            echo "&nbsp;";
            echo "</td>\n";
          }

          echo "</tr>\n";
        }
        echo "</table>";
        echo "</td><td valign=\"top\" width=\"33%\">";
        echo "<table width=\"100%\" border=0 cellpadding=0 cellspacing=0>";
        for($i=round(count($c)/3);$i<round(2*count($c)/3);$i++) {
          echo "<tr class=\"lst\">\n";
          if ($c[$i]["type"]) {
              echo "  <td width=\"28\"><input type=\"checkbox\" class=\"inc\" name=\"d[]\" value=\"".ehe($c[$i]["name"],false)."\"></td><td><a href=\"";
            $canedit = $bro->can_edit($R,$c[$i]["name"]);
            if ($canedit&&($c[$i]["size"]<$memory_limit)) {
              echo "bro_editor.php?editfile=".eue($c[$i]["name"],false)."&amp;R=".eue($R,false);
            } else {
              echo "bro_downloadfile.php?dir=".eue($R,false)."&amp;file=".eue($c[$i]["name"],false);
            }
            echo "\">"; ehe($c[$i]["name"],false); 
            echo "</a></td>\n";
            echo "  <td>".format_size($c[$i]["size"])."</td><td>";
            $vu=$bro->viewurl($R,$c[$i]["name"]);
            if ($vu) {
              echo "<td><a href=\"$vu\">".__("V", "alternc", true)."</a>";
            } else {
              echo "<td>&nbsp;";
            }

            echo "</td>\n";
          } else {
              echo "  <td width=\"28\"><input type=\"checkbox\" class=\"inc\" name=\"d[]\" value=\"".ehe($c[$i]["name"],false)."\"></td><td><b><a href=\"";
            echo "bro_main.php?R=".eue($R."/".$c[$i]["name"],false);
            echo "\">".ehe($c[$i]["name"],false)."/</a></b></td>\n";
            echo "  <td>".format_size($c[$i]["size"])."</td><td>";
            echo "&nbsp;";
            echo "</td>\n";
          }

          echo "</tr>\n";
        }
        echo "</table>";
        echo "</td><td valign=\"top\" width=\"33%\">";
        echo "<table width=\"100%\" border=0 cellpadding=0 cellspacing=0>";
        for($i=round(2*count($c)/3);$i<count($c);$i++) {
          echo "<tr class=\"lst\">\n";
          if ($c[$i]["type"]) {
              echo "  <td width=\"28\"><input type=\"checkbox\" class=\"inc\" name=\"d[]\" value=\"".ehe($c[$i]["name"],false)."\"></td><td><a href=\"";
            $canedit = $bro->can_edit($R,$c[$i]["name"]);
            if ($canedit && ($c[$i]["size"]<$memory_limit)) {
              echo "bro_editor.php?editfile=".eue($c[$i]["name"],false)."&amp;R=".eue($R,false);
            } else {
              echo "bro_downloadfile.php?dir=".eue($R)."&amp;file=".eue($c[$i]["name"]);
            }
            echo "\">"; ehe($c[$i]["name"],false); 
            echo "</a></td>\n";
            echo "  <td>".format_size($c[$i]["size"])."</td><td>";
            $vu=$bro->viewurl($R,$c[$i]["name"]);
            if ($vu) {
              echo "<td><a href=\"$vu\">".__("View", "alternc", true)."</a>";
            } else {
              echo "<td>&nbsp;";
            }
            echo "</td>\n";
          } else {
              echo "  <td width=\"28\"><input type=\"checkbox\" class=\"inc\"  name=\"d[]\" value=\"".ehe($c[$i]["name"],false)."\"></td><td><b><a href=\"";
            echo "bro_main.php?R=".eue($R."/".$c[$i]["name"],false);
            echo "\">".ehe($c[$i]["name"],false)."/</a></b></td>\n";
            echo "  <td>".format_size($c[$i]["size"])."</td><td>";
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
  echo "<p class=\"alert alert-info\">".__("No files in this folder", "alternc", true)."</p>";
}
?>

</td></tr>
<tr><td colspan="2" style="">

<br/>

<div class="showdirsize_button">
<span class="ina"><a href="bro_main.php?R=<?php eue(($R)?$R:"/",false); ?>&amp;showdirsize=1"><?php __("Show size of directories"); ?></a></span> <?php __("(slow)"); ?><br />&nbsp;<br />
</div>
<span class="ina"><?php
if ($hta->is_protected($R)) {
    echo "<a href=\"hta_edit.php?dir=".eue(($R)?$R:"/",false)."\">".__("Edit this folder's protection", "alternc", true)."</a>";
}
else {
    echo "<a href=\"hta_add.php?dir=".eue(($R)?$R:"/",false)."\">".__("Protect this folder", "alternc", true)."</a>";
}
?></span> <?php __("with a login and a password"); ?>
</p><p>
<span class="ina">
<a href="bro_tgzdown.php?dir=<?php eue(($R)?$R:"/"); ?>"><?php __("Download this folder"); ?></a>
</span> &nbsp; 
<?php printf(__("as a %s file", "alternc", true),$bro->l_tgz[$p["downfmt"]]); ?>
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
    <a href="ftp_edit.php?create=1&amp;dir=<?php ehe($R); ?>"><?php __("Create an ftp account in this folder"); ?></a>
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

<script type="text/javascript">
$(document).ready(function() {
  $("#tab_files_w_details").tablesorter({
    textExtraction: function(node) {
        var attr = $(node).attr('data-sort-value');
        if (typeof attr !== 'undefined' && attr !== false) {
            return attr;
        }
        return $(node).text(); 
    } 
   });
}); 
</script>

<?php include_once("foot.php"); ?>
