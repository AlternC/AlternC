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
 * File editor part of AlternC file manager / browser.
 * 
 * @copyright AlternC-Team 2000-2017 https://alternc.com/  
 */

 require_once("../class/config.php");

// We check it ourself : not fatal
define("NOCSRF",true);

$fields = array (
	"editfile"    		=> array ("request", "string", ""),
	"texte"    		=> array ("post", "string", ""),
	"save"    		=> array ("post", "string", ""),
	"saveret"    		=> array ("post", "string", ""),
	"cancel"    		=> array ("post", "string", ""),
	"R"	    		=> array ("request", "string", ""),
);
getFields($fields);

$editing=false;

$R=$bro->convertabsolute($R,1);
$p=$bro->GetPrefs();

if (isset($cancel) && $cancel) {
	include("bro_main.php");
	exit();
}

if (isset($saveret) && $saveret) {
    $editing=true;

    // Thanks to this, we bring you back to the EDIT form if the CSRF is invalid.
    // Allows you to re-submit
    // FIXME - doesn't work
/*    $csrf_check=false;
    if (count($_POST) && !defined("NOCSRF")) {
        if (csrf_check()<=0) {
            $csrf_check = true;
        }
    }*/
    
    if ($bro->save($editfile,$R,$texte)) {
        $msg->raise("INFO", "bro", _("Your file %s has been saved")." (".format_date(_('%3$d-%2$d-%1$d %4$d:%5$d'),date("Y-m-d H:i:s")).")", $editfile);
        include("bro_main.php");
        exit();
    }
}
if (isset($save) && $save) {
  if ($bro->save($editfile,$R,$texte)) {
    $msg->raise("INFO", "bro", _("Your file %s has been saved")." (".format_date(_('%3$d-%2$d-%1$d %4$d:%5$d'),date("Y-m-d H:i:s")).")", $editfile);
  }
}

$addhead['css'][]='<link rel="stylesheet" href="/javascript/prettify/prettify.css" type="text/css" />';
$addhead['js'][]='<script src="/javascript/prettify/prettify.js" type="text/javascript"></script>';
include_once("head.php");

?>
<p>
<?php
echo $msg->msg_html_all();
?>
<h3><?php echo _("File editing")." <code>".ehe($R,false)."/<b>".ehe($editfile,false)."</b></code><br />"; ?></h3>
</p>

<?php
$content=$bro->content($R,$editfile);
?>

<form action="bro_editor.php" method="post"><br />
  <?php csrf_get(); ?>
<div id="tabsfile">
  <ul>
    <li class="view"><a href="#tabsfile-view"><?php __("View"); ?></a></li>
    <li class="edit"><a href="#tabsfile-edit"><?php __("Edit"); ?></a></li>
  </ul>

<div id="tabsfile-view">
<?php
echo "<pre class='prettyprint' id='file_content_view' >$content</pre>";
?>
</div>

<div id="tabsfile-edit">
<textarea id='file_content_editor' class="int" style="font-family: <?php echo $p["editor_font"]; ?>; font-size: <?php echo $p["editor_size"]; ?>; width: 90%; height: 400px;" name="texte"><?php
  if (empty($content)) { 
    $error=_("This file is empty");
  } else {
    echo $content;  
  }
?></textarea>
</div>
</div><!-- tabsfile -->
<br/>
<?php if (!empty($error)) echo "<p class=\"alert alert-danger\">".$error."</p>"; ?>
	<input type="hidden" name="editfile" value="<?php ehe($editfile); ?>" />
	<input type="hidden" name="R" value="<?php ehe($R); ?>" />

	<input type="submit" class="inb" value="<?php __("Save"); ?>" name="save" />
	<input type="submit" class="inb" value="<?php __("Save &amp; Quit"); ?>" name="saveret" />
	<input type="submit" class="inb" value="<?php __("Quit"); ?>" name="cancel" />
<br />
</form>

<script type="text/javascript">
$(function() {
    prettyPrint();
    $( "#tabsfile" ).tabs();
<?php if ($editing) { ?>
    $( "#tabsfile-edit" ).tabs( "option", "active", 1 );
<?php } ?>
});

$('#tabsfile').on('tabsbeforeactivate', function(event, ui){
    var b = $('#file_content_editor').val();
    $('#file_content_view').text( b );
    $('#file_content_view').removeClass('prettyprinted');
    PR.prettyPrint();
});
</script>


<?php include_once("foot.php"); ?>
