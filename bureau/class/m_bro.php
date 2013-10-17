<?php
/*
  ----------------------------------------------------------------------
  AlternC - Web Hosting System
  Copyright (C) 2000-2012 by the AlternC Development Team.
  https://alternc.org/
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
  Purpose of file: file browser class.
  ----------------------------------------------------------------------
*/

/* Add the mime type list */
@include("mime.php");

/**
 * This class manage the file browser of AlternC
 * allow the file and directory management in the user account web folder
 */
class m_bro {

  /** How we draw the file in column */
  var $l_mode;

  /** download mode of a compressed folder */
  var $l_tgz;

  /** Shall we show icons or just names? */
  var $l_icons;

  /** What do we do after creating a file? */
  var $l_createfile;

  /** internal cache
   */
  var $mime_desc=array();

  /** internal cache
   */
  var $mime_icon=array();

  /** internal cache
   */
  var $mime_type=array();

  /** Font choice in the editor */
  var $l_editor_font=array("Arial, Helvetica, Sans-serif","Times, Bookman, Serif","Courier New, Courier, Fixed");

  /** font size in the editor */
  var $l_editor_size=array("18px","14px","12px","10px","8px","0.8em","0.9em","1em","1.1em","1.2em");

  /* ----------------------------------------------------------------- */
  /** Constructor */
  function m_bro() {
    $this->l_mode=array( 0=>_("1 column, detailed"), 1=>_("2 columns, short"), 2=>_("3 columns, short") );
    $this->l_tgz=array( 0=>_("tgz (Linux)"), 1=>_("tar.bz2 (Linux)"), 2=>_("zip (Windows/Dos)"), 3=>_("tar.Z (Unix)") );
    $this->l_icons=array( 0=>_("No"), 1=>_("Yes") );
    $this->l_createfile=array( 0=>_("Go back to the file manager"), 1=>_("Edit the newly created file") );
  }

  function hook_menu() {
    $obj = array( 
      'title'       => _("File browser"),
      'ico'         => 'images/folder.png',
      'link'        => 'bro_main.php',
      'pos'         => 40,
     ) ;

     return $obj;
  }



  /* ----------------------------------------------------------------- */
  /** Verifie un dossier relatif au dossier de l'utilisateur courant
   *
   * @param string $dir Dossier (absolu que l'on souhaite vrifier
   * @return string Retourne le nom du dossier vrifi, relatif au
   * dossier de l'utilisateur courant, ventuellement corrig.
   * ou FALSE si le dossier n'est pas dans le dossier de l'utilisateur.
   */
  function convertabsolute($dir,$strip=1) {
    global $mem;
    $root=$this->get_user_root($mem->user["login"]);
    // Sauvegarde du chemin de base.
    $root_alternc = $root ;
    // Passage du root en chemin rel (diffrent avec un lien)
    $root=realpath($root) ;
    // separer le chemin entre le repertoire et le fichier
    $file = basename($dir);
    $dir = dirname($dir);
    $dir=realpath($root."/".$dir);
    // verifier que le repertoire est dans le home de l'usager
    if (substr($dir,0,strlen($root))!=$root) {
      return false;
    }
 
    // recomposer le chemin
    $dir = $dir . '/' . $file;

    # Si on tente de mettre un '..' alors erreur 
    if ( preg_match("/\/\.\.\//", $dir) || preg_match("/\/\.\.$/", $dir) ) { 
      return false; 
    } 

    if ($strip) {
      $dir=substr($dir,strlen($root));
    } else {
      // si on ne strip pas, il faut enlever le chemin rel 
      // et mettre la racine d'alternc pour viter les
      // problmes de lien depuis /var /alternc ! 
      $dir=$root_alternc . substr($dir,strlen($root));
    }
    if (substr($dir,-1)=="/") {
      return substr($dir,0,strlen($dir)-1);
    } else
      return $dir;
  }


  /* ----------------------------------------------------------------- */
  /** Retourne le chemin complet vers la racine du repertoire de l'utilisateur.
   *  Returns the complete path to the root of the user's directory.
   *
   * @param string $login Username
   * @return string Returns the complete path to the root of the user's directory.
   */
  function get_user_root($login) {
    return getuserpath();
  }


  /* ----------------------------------------------------------------- */
  /** Retourne le chemin complet vers la racine du repertoire de l'utilisateur.
   *  Returns the complete path to the root of the user's directory.
   *
   * @param string $uid User id.
   * @return string Returns the complete path to the root of the user's directory.
   */
  function get_userid_root($uid) {
    global $admin;

    // FIXME [ML] Comment faire ca correctement?
    // C'est utilise' dans class/m_dom.php quand un utilisateur ajoute un domaine dans son compte
    // et nous devons savoir quel est le chemin complet vers la racine de son compte..

    $old_enabled = $admin->enabled;
    $admin->enabled = true;
    $member = $admin->get($uid);
    $admin->enabled = $old_enabled;

    return $this->get_user_root($member['login']);
  }


  /* ----------------------------------------------------------------- */
  /** Retourne un tableau contenant la liste des fichiers du dossier courant
   * Ce tableau contient tous les paramtres des fichiers du dossier courant
   * sous la forme d'un tableau index de tableaux associatifs comme suit :
   * $a["name"]=nom du fichier / dossier
   * $a["size"]=Taille totale du fichier / dossier + sous-dossier
   * $a["date"]=Date de dernire modification
   * $a["type"]=Type du fichier (1 pour fichier, 0 pour dossier)
   * @param string $dir dossier relatif au dossier racine du compte du
   * membre courant
   * @return array le tableau contenant les fichiers de $dir, et
   */
  function filelist($dir="", $showdirsize = false) {
    global $db,$cuid,$err;
    $db->query("UPDATE browser SET lastdir='$dir' WHERE uid='$cuid';");
    $absolute=$this->convertabsolute($dir,0);
    if (! file_exists($absolute)) {
      $err->raise('bro',_("This directory do not exist"));
      return false;
    }
    if ($dir = @opendir($absolute)) {
      while (($file = readdir($dir)) !== false) {
	if ($file!="." && $file!="..") {
	  $c[]=array("name"=>$file, "size"=>$this->fsize($absolute."/".$file, $showdirsize), "date"=>filemtime($absolute."/".$file), "type"=> (!is_dir($absolute."/".$file)) );
	}
      }
      closedir($dir);
    }
    if (isset ($c) && is_array($c)) {
      usort ($c, array("m_bro","_sort_filelist_name"));
      return $c;
    } else {
      return array();
    }
  }


  /* ----------------------------------------------------------------- */
  /** Retourne un tableau contenant les prfrences de l'utilisateur courant
   * Ce tableau aqssociatif contient les valeurs des champs de la table "browser"
   * pour l'utilisateur courant.
   * @return array Tableau des prfrences de l'utilisateur courant.
   */
  function GetPrefs() {
    global $db,$cuid;
    $db->query("SELECT * FROM browser WHERE uid='$cuid';");
    if ($db->num_rows()==0) {
      $db->query("INSERT INTO browser (editsizex, editsizey, listmode, showicons, downfmt, createfile, showtype, uid, editor_font, editor_size) VALUES (70, 21, 0, 0, 0, 0, 0, '$cuid','Arial, Helvetica, Sans-serif','12px');");
      $db->query("SELECT * FROM browser WHERE uid='$cuid';");
    }
    $db->next_record();
    return $db->Record;
  }


  /* ----------------------------------------------------------------- */
  /** Modifie les prfrences de l'utilisateur courant.
   *
   * @param integer $editsizex  Taille de l'diteur (nombre de colonnes)
   * @param integer $editsizey  Taille de l'diteur (nombre de lignes)
   * @param integer $listmode   Mode d'affichage de la liste des fichiers
   * @param integer $showicons  Faut-il afficher / cacher les icones des fichiers
   * @param integer $downfmt    Dans quel format faut-il tlcharger les dossiers compresss
   * @param integer $createfile Faut-il editer/revenir au browser aprs cration d'un fichier
   * @param integer $showtype Faut-il afficher le type mime des fichiers
   * @param integer $editor_font  Quelle police faut-il utiliser pour l'diteur
   * @param integer $editor_size  Quelle taille de police faut-il utiliser pour l'diteur
   * @param integer $golastdir  Faut-il revenir  la racine ou au dernier dossier visit ?
   * @return boolean TRUE
   */
  function SetPrefs($editsizex, $editsizey, $listmode, $showicons, $downfmt, $createfile, $showtype, $editor_font, $editor_size, $golastdir) {
    global $db,$cuid;
    $editsizex=intval($editsizex);	$editsizey=intval($editsizey);
    $listmode=intval($listmode);	$showicons=intval($showicons);
    $showtype=intval($showtype);	$downfmt=intval($downfmt);
    $createfile=intval($createfile);	$golastdir=intval($golastdir);
    $db->query("SELECT * FROM browser WHERE uid='$cuid';");
    if ($db->num_rows()==0) {
      $db->query("INSERT INTO browser (editsizex, editsizey, listmode, showicons, downfmt, createfile, showtype, uid, editor_font, editor_size, golastdir) VALUES (70, 21, 0, 0, 0, 0, 0, '".$this->uid."','Arial, Helvetica, Sans-serif','12px',1);");
    }
    $db->query("UPDATE browser SET editsizex='$editsizex', editsizey='$editsizey', listmode='$listmode', showicons='$showicons', downfmt='$downfmt', createfile='$createfile', showtype='$showtype', editor_font='$editor_font', editor_size='$editor_size', golastdir='$golastdir' WHERE uid='$cuid';");
    return true;
  }


  /* ----------------------------------------------------------------- */
  /** Retourne le nom du fichier icone associ au fichier donc le nom est $file
   * <b>Note</b>: Les fichiers icones sont mis en cache sur la page courante.
   * @param string $file Fichier dont on souhaite connaitre le fichier icone
   * @return string Fichier icone correspondant.
   */
  function icon($file) {
    global $bro_icon;
    if (!strpos($file,".") && substr($file,0,1)!=".") {
      return "file.png";
    }
    $t=explode(".",$file);
    if (!is_array($t))
      $ext=$t;
    else
      $ext=$t[count($t)-1];
    // Now seek the extension
    if (!isset($bro_icon[$ext]) || ! $bro_icon[$ext]) {
      return "file.png";
    } else {
      return $bro_icon[$ext].".png";
    }
  }


  /* ----------------------------------------------------------------- */
  /** Retourne le type mime associ au fichier donc le nom est $file
   * <b>Note</b>: Les types mimes sont mis en cache sur la page courante.
   * Le type mime est dtermin d'aprs l'extension du fichier.
   * @param string $file Fichier dont on souhaite connaitre le type mime
   * @return string Type mime / Sous type du fichier demand
   */
  function mime($file) {
    global $bro_type;
    if (!strpos($file,".") && substr($file,0,1)!=".") {
      return "File";
    }
    $t=explode(".",$file);
    if (!is_array($t))
      $ext=$t;
    else
      $ext=$t[count($t)-1];
    // Now seek the extension
    if (empty($bro_type[$ext])) {
      return "File";
    } else {
      return $bro_type[$ext];
    }
  }


  /* ----------------------------------------------------------------- */
  /** Retourne la taille du fichier $file
   * si $file est un dossier, retourne la taille de ce dossier et de tous
   * ses sous dossiers.
   * @param string $file Fichier dont on souhaite connaitre la taille
   * @param boolean $showdirsize recursively compute the directory size.
   * @return integer Taille du fichier en octets.
   */
  function fsize($file, $showdirsize = false) {
    if (is_dir($file)) {
      if ($showdirsize) {
        return $this->dirsize($file);
      } else {
        return "-";
      }
    } else {
      return filesize($file);
    }
  }


  /* ----------------------------------------------------------------- */
  /** Returns the size of a directory, by adding all it's files sizes
   * @param string $dir the directory whose size we want to compute 
   * @return integer the total size in bytes.
   */
  function dirsize($dir) {
    $totalsize = 0;

    if ($handle = opendir($dir)) {
      while (false !== ($file = readdir($handle))) {
        $nextpath = $dir . '/' . $file;

	if ($file != '.' && $file != '..' && !is_link($nextpath)) {
          if (is_dir($nextpath)) {
            $totalsize += $this->dirsize($nextpath);
          } elseif (is_file ($nextpath)) {
            $totalsize += filesize($nextpath);
          }
        }
      }
      closedir($handle);
    }
    return $totalsize;
  }


  /* ----------------------------------------------------------------- */
  /** Cre le dossier $file dans le dossier (parent) $dir
   * @param string $dir dossier dans lequel on veut crer un sous-dossier
   * @param string $file nom du dossier  crer
   * @return boolean TRUE si le dossier a t cr, FALSE si une erreur s'est produite.
   */
  function CreateDir($dir,$file) {
    global $db,$cuid,$err;
    $file=ssla($file);
    $absolute=$this->convertabsolute($dir."/".$file,0);
    #echo "$absolute";
    if ($absolute && (!file_exists($absolute))) {
      if (!mkdir($absolute,00777,true)) {
	$err->raise("bro",_("Cannot create the requested directory. Please check the permissions"));
	return false;
      }
      $db->query("UPDATE browser SET crff=1 WHERE uid='$cuid';");
      return true;
    } else {
      $err->raise("bro",_("File or folder name is incorrect"));
      return false;
    }
  }


  /* ----------------------------------------------------------------- */
  /** Cre un fichier vide dans un dossier
   * @param string $dir Dossier dans lequel on cre le nouveau fichier
   * @param string $file Fichier que l'on souhaite crer.
   * @return boolean TRUE si le dossier a t cr, FALSE si une erreur s'est produite.
   */
  function CreateFile($dir,$file) {
    global $db,$err,$cuid;
    $file=ssla($file);
    $absolute=$this->convertabsolute($dir."/".$file,0);
    if (!$absolute || file_exists($absolute)) {
      $err->raise("bro",_("File or folder name is incorrect"));
      return false;
    }
    if (!file_exists($absolute)) {
      if (!@touch($absolute)) {
	$err->raise("bro",_("Cannot create the requested file. Please check the permissions"));
	return false;
      }
    }
    $db->query("UPDATE browser SET crff=0 WHERE uid='$cuid';");
    return true;
  }


  /* ----------------------------------------------------------------- */
  /** Efface les fichiers du tableau $file_list dans le dossier $R
   * @param array $file_list Liste des fichiers  effacer.
   * @param string $R Dossier dans lequel on efface les fichiers
   * @return boolean TRUE si les fichiers ont t effacs, FALSE si une erreur s'est produite.
   */
  function DeleteFile($file_list,$R) {
    global $err, $mem;
    $root=realpath(getuserpath());
    $absolute=$this->convertabsolute($R,0);
    if (!$absolute && strpos($root,$absolute) === 0 && strlen($absolute) > (strlen($root)+1) ) {
      $err->raise("bro",_("File or folder name is incorrect"));
      return false;
    }
    for ($i=0;$i<count($file_list);$i++) {
      $file_list[$i]=ssla($file_list[$i]);
      if (!strpos($file_list[$i],"/") && file_exists($absolute."/".$file_list[$i])) { // Character / forbidden in a FILE name
	$this->_delete($absolute."/".$file_list[$i]);
      }
    }
    return true;
  }


  /* ----------------------------------------------------------------- */
  /** Renomme les fichier de $old du dossier $R en $new
   * @param string $R dossier dans lequel se trouve les fichiers  renommer.
   * @param array of string $old Ancien nom des fichiers
   * @param array of string $new Nouveau nom des fichiers
   * @return boolean TRUE si les fichiers ont t renomms, FALSE si une erreur s'est produite.
   */
  function RenameFile($R,$old,$new) {
    global $err;
    $absolute=$this->convertabsolute($R,0);
    if (!$absolute) {
      $err->raise("bro",_("File or folder name is incorrect"));
      return false;
    }
    $alea=".".time().rand(1000,9999);
    for ($i=0;$i<count($old);$i++) {
      $old[$i]=ssla($old[$i]); // strip slashes if needed
      $new[$i]=ssla($new[$i]);
      if (!strpos($old[$i],"/") && !strpos($new[$i],"/")) {  // caractre / interdit dans old ET dans new...
	@rename($absolute."/".$old[$i],$absolute."/".$old[$i].$alea);
      }
    }
    for ($i=0;$i<count($old);$i++) {
      if (!strpos($old[$i],"/") && !strpos($new[$i],"/")) {  // caractre / interdit dans old ET dans new...
      	@rename($absolute."/".$old[$i].$alea,$absolute."/".$new[$i]);
      }
    }

    return true;
  }


  /* ----------------------------------------------------------------- */
  /** Dplace les fichier de $d du dossier $old vers $new
   * @param array of string $d Liste des fichiers du dossier $old  dplacer
   * @param string $old dossier dans lequel se trouve les fichiers  dplacer.
   * @param string $new dossier vers lequel seront dplacs les fichiers.
   * @return boolean TRUE si les fichiers ont t renomms, FALSE si une erreur s'est produite.
   */
  function MoveFile($d,$old,$new) {
    global $err;
    $old=$this->convertabsolute($old,0);
    if (!$old) {
      $err->raise("bro",_("File or folder name is incorrect"));
      return false;
    }

    if ($new[0] != '/') {
      $new = $old . '/' . $new;
    }
    $new = $this->convertabsolute($new,0);

    if (!$new) {
      $err->raise("bro",_("File or folder name is incorrect"));
      return false;
    }
    if ($old==$new) {
      $err->raise("bro",_("You cannot move or copy a file to the same folder"));
      return false;
    }
    for ($i=0;$i<count($d);$i++) {
      $d[$i]=ssla($d[$i]); // strip slashes if needed
      if (!strpos($d[$i],"/") && file_exists($old."/".$d[$i]) && !file_exists($new."/".$d[$i])) {  
        if (!rename($old."/".$d[$i],$new."/".$d[$i]))
          $err->raise("bro", "error renaming $old/$d[$i] -> $new/$d[$i]");
      }
    }
    return true;
  }


  /* ----------------------------------------------------------------- */
  /** Change les droits d'acces aux fichier de $d du dossier $R en $p
   * @param string $R dossier dans lequel se trouve les fichiers  renommer.
   * @param array of string $old Ancien nom des fichiers
   * @param array of string $new Nouveau nom des fichiers
   * @param $verbose boolean shall we 'echo' what we did ?
   * @return boolean TRUE si les fichiers ont t renomms, FALSE si une erreur s'est produite.
   */
  function ChangePermissions($R,$d,$perm,$verbose=false) {
    global $err;
    $absolute=$this->convertabsolute($R,0);
    if (!$absolute) {
      $err->raise("bro",_("File or folder name is incorrect"));
      return false;
    }
    for ($i=0;$i<count($d);$i++) {
      $d[$i]=ssla($d[$i]); // strip slashes if needed
      if (!strpos($d[$i],"/")) {  // caractre / interdit dans le nom du fichier
        $m = fileperms($absolute."/". $d[$i]);

        // pour l'instant on se limite a "write" pour owner, puisque c'est le seul
        // cas interessant compte tenu de la conf de Apache pour AlternC..
        if ($perm[$i]['w']) {
          $m = $m | 128;
        } else {
	  $m = $m ^ 128;
        }
        $m = $m | ($perm[$i]['w'] ? 128 : 0); // 0600
        chmod($absolute."/".$d[$i], $m);
	if ($verbose) {
	  echo "chmod " . sprintf('%o', $m) . " file, was " . sprintf('%o', fileperms($absolute."/". $d[$i])). " -- " . $perm[$i]['w'];
	}
      }
    }

    return true;
  }


  /* ----------------------------------------------------------------- */
  /** Recoit un champ file upload (Global) et le stocke dans le dossier $R
   * Le champ file-upload originel doit s'appeler "userfile" et doit
   * bien tre un fichier d'upload.
   * @param string $R Dossier dans lequel on upload le fichier
   * @returns the path where the file resides or false if upload failed
   */
  function UploadFile($R) {
    global $_FILES,$err,$cuid,$action;
    $absolute=$this->convertabsolute($R,0);
    if (!$absolute) {
      $err->raise("bro",_("File or folder name is incorrect"));
      return false;
    }
    if (!strpos($_FILES['userfile']['name'],"/")) {
      if (is_uploaded_file($_FILES['userfile']['tmp_name'])) {
        if (!file_exists($absolute."/".$_FILES['userfile']['name'])) {
          @touch($absolute."/".$_FILES['userfile']['name']);
        }
        if (@move_uploaded_file($_FILES['userfile']['tmp_name'], $absolute."/".$_FILES['userfile']['name'])) {
          $action->fix_file($absolute."/".$_FILES['userfile']['name']);
	  return $absolute."/".$_FILES['userfile']['name'];
	} else {
	  $err->raise("bro",_("Cannot create the requested file. Please check the permissions"));
	  return false;
	}
      } else {
	$err->log("bro","uploadfile","Tentative d'attaque : ".$_FILES['userfile']['tmp_name']);
        return false;
      }
    }
    return $absolute."/".$_FILES['userfile']['name'];
  }


  /* ----------------------------------------------------------------- */
  /**
   * Extract an archive by using GNU and non-GNU tools
   * @param string $file is the full or relative path to the archive
   * @param string $dest is the path of the extract destination, the
   * same directory as the archive by default
   * @return boolean != 0 on error
   */
  function ExtractFile($file, $dest=null) {
    global $err,$cuid,$mem,$action;
    $file = $this->convertabsolute($file,0);
    if (is_null($dest)) {
      $dest = dirname($file);
    } else {
      $dest = $this->convertabsolute($dest,0);
    }
    if (!$file || !$dest) {
      $err->raise("bro",_("File or folder name is incorrect"));
      return 1;
    }
    $file = escapeshellarg($file);
    $dest_to_fix = $dest;
    $dest = escapeshellarg($dest);
    #$dest_to_fix=str_replace(getuserpath(),'',$dest);
	 
    // TODO new version of tar supports `tar xf ...` so there is no
    //     need to specify the compression format
    exec("tar -xf $file -C $dest", $void, $ret);
    if ($ret) {
      exec("tar -xjf $file -C $dest", $void, $ret);
    }
    if ($ret) {
      $cmd = "unzip -o $file -d $dest";
      exec($cmd, $void, $ret);
    }
    if ($ret) {
      $cmd = "gunzip $file";
      exec($cmd, $void, $ret);
    }
    if ($ret) {
      $err->raise("bro",_("I cannot find a way to extract the file %s, it is an unsupported compressed format"), $file);
    }
    // fix the perms of the extracted archive TODO: does it work???
    $action->fix_dir($dest_to_fix);
    return $ret;
  }


  /* ----------------------------------------------------------------- */
  /** Copy many files from point A to point B
   */
  function CopyFile($d,$old,$new) {
    global $err;
    $old=$this->convertabsolute($old,0);
    if (!$old) {
      $err->raise("bro",_("File or folder name is incorrect"));
      return false;
    }
    $new=$this->convertabsolute($new,0);
    if (!$new) {
      $err->raise("bro",_("File or folder name is incorrect"));
      return false;
    }
    if ($old==$new) {
      $err->raise("bro",_("You cannot move or copy a file to the same folder"));
      return false;
    }
    for ($i=0;$i<count($d);$i++) {
      $d[$i]=ssla($d[$i]); // strip slashes if needed
      if (!strpos($d[$i],"/") && file_exists($old."/".$d[$i]) && !file_exists($new."/".$d[$i])) {  
        $this->CopyOneFile($old."/".$d[$i],$new);
      }
    }
    return true;
  }


  /* ----------------------------------------------------------------- */
  /**
   * Copy a source to a destination by either copying recursively a
   * directory or by downloading a file with a URL (only http:// is
   * supported)
   * @param string $src is the path or URL
   * @param string $dest is the absolute path inside the users directory
   * @return boolean false on error
   *
   * Note that we assume that the inputs have been convertabsolute()'d
   */
  function CopyOneFile($src, $dest)  {
    global $err;
    $src = escapeshellarg($src);
    $dest = escapeshellarg($dest);
    exec("cp -Rpf $src $dest", $void, $ret);
    if ($ret) {
      $err->raise("bro","Errors happened while copying the source to destination. cp return value: %d", $ret);
      return false;
    }
    return true;
  }


  /* ----------------------------------------------------------------- */
  /** Affiche le chemin et les liens de la racine au dossier $path
   * Affiche autant de liens HTML (anchor) que le chemin $path contient de
   * niveaux de dossier. Chaque lien est associ  la page web $action
   *  laquelle on ajoute le paramtre R=+Le nom du dossier courant.
   * @param string $path Dossier vers lequel on trace le chemin
   * @param string $action Page web de destination des liens
   * @return string le code HTML ainsi obtenu.
   */
  function PathList($path,$action, $justparent=false) {
    $path=$this->convertabsolute($path,1);
    $a=explode("/",$path);
    if (!is_array($a)) $a=array($a);
    $c='';
    $R='';
    if ($justparent) {
      return "<a href=\"$action?R=".urlencode($a[count($a)-2].'/')."\">&uarr;</a>";
    }
    for($i=0;$i<count($a);$i++) {
      if ($a[$i]) {
	$R.=$a[$i]."/";
	$c.="<a href=\"$action?R=".urlencode($R)."\">".$a[$i]."</a>&nbsp;/&nbsp;";
      }
    }
    return $c;
  }


  /* ----------------------------------------------------------------- */
  /** Affiche le contenu d'un fichier pour un champ VALUE de textarea
   * Affiche le contenu du fichier $file dans le dossier $R. Le contenu
   * du fichier est reformat pour pouvoir entrer dans un champs TextArea
   * @param string $R Dossier dans lequel on cherche le fichier
   * @param string $file Fichier dont on souhaite obtenir le contenu.
   * @return boolean retourne TRUE si le fichier a bien t mis sur
   * echo, ou FALSE si une erreur est survenue.
   */
  function content($R,$file) {
    global $err;
    $absolute=$this->convertabsolute($R,0);
    $std="";
    if (!strpos($file,"/")) {
      $absolute.="/".$file;
      if (file_exists($absolute)) {
	$std=str_replace("<","&lt;",str_replace("&","&amp;",file_get_contents($absolute)));
      } else {
	$err->raise("bro",_("Cannot read the requested file. Please check the permissions"));
	return false;
      }
    } else {
      $err->raise("bro",_("File or folder name is incorrect"));
      return false;
    }
    return $std;
  }


  /** Internal cache for viewurl
   */
   var $cacheurl=array();


  /* ----------------------------------------------------------------- */
  // Return a browsing url if available.
  // Maintain a url cache (positive AND negative(-) cache)
  /* ----------------------------------------------------------------- */
  /** Retourne une url de navigation pour le fichier $name du dossier $dir
   * Les url sont mises en caches. Il se peut qu'aucune url n'existe, ou que
   * celle-ci soit protge par un .htaccess.
   * @param string $dir Dossier concern
   * @param string $name Fichier dont on souhaite obtenir une URL
   * @return string URL concerne, ou FALSE si aucune URL n'est disponible pour ce fichier
   */
  function viewurl($dir,$name) {
    global $db,$cuid;
    // Is it in cache ?
    if (substr($dir,0,1)=="/") $dir=substr($dir,1);
    if (substr($dir,-1)=="/") $dir=substr($dir,0,-1);
    $dir=str_replace("%2F", "/", urlencode($dir));
    $name=urlencode($name);
    if (!@$this->cacheurl["d".$dir]) {
      // On parcours $dir en remontant les /
      $end="";	$beg=$dir;	$tofind=true;
      while ($tofind) {
        $db->query("SELECT sub,domaine FROM sub_domaines WHERE compte='$cuid'
             AND type=0 AND (valeur='/$beg/' or valeur='/$beg');");
        $db->next_record();
        if ($db->num_rows()) {
          $tofind=false;
          $this->cacheurl["d".$dir]="http://".$db->f("sub").ife($db->f("sub"),".").$db->f("domaine").$end;
        }
        if (!$beg && $tofind) {
          $tofind=false;
          $this->cacheurl["d".$dir]="-";
	  // We did not find it ;(
        }
        if (($tt=strrpos($beg,"/"))!==false) {
          $end=substr($beg,$tt).$end; // = /topdir$end so $end starts AND ends with /
          $beg=substr($beg,0,$tt);
        } else {
          $end="/".$beg.$end;
          $beg="/";
        }
      }
    }
    if ($this->cacheurl["d".$dir] && $this->cacheurl["d".$dir]!="-") {
      return $this->cacheurl["d".$dir]."/".$name;
    } else {
      return false;
    }
  }


  /* ----------------------------------------------------------------- */
  /**
   */
  function can_edit($dir,$name) {
    global $mem,$err;
    $absolute="$dir/$name";
    $absolute=$this->convertabsolute($absolute,0);
    if (!$absolute) {
      $err->raise('bro',_("File not in authorized directory"));
      include('foot.php');
      exit;
    }
    $finfo = finfo_open(FILEINFO_MIME_TYPE); 
    $mime = finfo_file($finfo,$absolute);
    if ( substr($mime,0,5)=="text/" || $mime == "application/x-empty") {
      return true;
    }
    return false;
  }


  /* ----------------------------------------------------------------- */
  /** Return a HTML snippet representing an extraction function only if the mimetype of $name is supported
   */
  function is_extractable($dir,$name) {
    if ($parts = explode(".", $name)) {
      $ext = array_pop($parts);
      switch ($ext) {
      case "gz":
      case "bz":
      case "bz2":
	$ext = array_pop($parts) . $ext;
      /* FALLTHROUGH */
      case "tar.gz":
      case "tar.bz":
      case "tar.bz2":
      case "tgz":
      case "tbz":
      case "tbz2":
      case "tar":
      case "Z":
      case "zip":
        return true;
      }
    }
    return false;
  }

  // return true if file is a sql dump (end with .sql or .sql.gz)
  function is_sqlfile($dir,$name) {
    if ($parts = explode(".", $name)) {
      $ext = array_pop($parts);
      $ext2 = array_pop($parts) . '.'.$ext;
      if ( $ext=='sql' or $ext2=='sql.gz') return true;
    }
    return false;
  }


  /* ----------------------------------------------------------------- */
  /**
   */
  function download_link($dir,$file){
    global $err;
    $err->log("bro","download_link");
    header("Content-Disposition: attachment; filename=$file");
    header("Content-Type: application/force-download");
    header("Content-Transfer-Encoding: binary");
    $this->content_send($dir,$file);
  }


  /* ------------------------------------------------------------------ */
  /** Echoes the content of the file $file located in directory $R
   */
  function content_send($R,$file) {
    global $err;
    $absolute=$this->convertabsolute($R,0);
    if (!strpos($file,"/")) {
      $absolute.="/".$file;
      if (file_exists($absolute)) {
	readfile($absolute);
      }
    } else {
      $err->raise("bro",_("File or folder name is incorrect"));
      return false;
    }
  }


  /* ----------------------------------------------------------------- */
  /** Sauve le fichier $file dans le dossier $R avec pour contenu $texte
   * le contenu est issu d'un textarea, et ne DOIT PAS contenir de \ ajouts
   * automatiquement par addslashes
   * @param string $file Nom du fichier  sauver. S'il existe dj, il sera
   *  cras sans confirmation.
   * @param string $R Dossier dans lequel on modifie le fichier
   * @param string $texte texte du fichier  sauver dedans
   * @return boolean TRUE si tout s'est bien pass, FALSE si une erreur s'est produite.
   */
  function save($file,$R,$texte) {
    global $err;
    $absolute=$this->convertabsolute($R,0);
    if (!strpos($file,"/")) {
      $absolute.="/".$file;
      if (file_exists($absolute)) {
	$f=@fopen($absolute,"wb");
       	if ($f) {
	  fputs($f,$texte,strlen($texte));
	  fclose($f);
	} else {
	  $err->raise("bro",_("Cannot edit the requested file. Please check the permissions"));
	  return false;
	}
      }
    } else {
      $err->raise("bro",_("File or folder name is incorrect"));
      return false;
    }
  }


  /* ----------------------------------------------------------------- */
  /** Echo d'un flux .tar.Z contenant tout le contenu du dossier $dir
   * @param string $dir dossier  dumper, relatif  la racine du compte du membre.
   * @return void NE RETOURNE RIEN, et il faut Quitter le script immdiatement aprs
   */
  function DownloadZ($dir="") {
    global $mem;
    header("Content-Disposition: attachment; filename=".$mem->user["login"].".Z");
    header("Content-Type: application/x-Z");
    header("Content-Transfer-Encoding: binary");
    $d=escapeshellarg(".".$this->convertabsolute($dir,1));
    set_time_limit(0);
    passthru("/bin/tar -cZ -C ".getuserpath()."/".$mem->user["login"]."/ $d");
  }


  /* ----------------------------------------------------------------- */
  /** Echo d'un flux .tgz contenant tout le contenu du dossier $dir
   * @param string $dir dossier  dumper, relatif  la racine du compte du membre.
   * @return void NE RETOURNE RIEN, et il faut Quitter le script immdiatement aprs
   */
  function DownloadTGZ($dir="") {
    global $mem;
    header("Content-Disposition: attachment; filename=".$mem->user["login"].".tgz");
    header("Content-Type: application/x-tgz");
    header("Content-Transfer-Encoding: binary");
    $d=escapeshellarg(".".$this->convertabsolute($dir,1));
    set_time_limit(0);
    passthru("/bin/tar -cz -C ".getuserpath()."/ $d");
  }
  
  
  /* ----------------------------------------------------------------- */
  /** Echo d'un flux .tar.bz2 contenant tout le contenu du dossier $dir
   * @param string $dir dossier  dumper, relatif  la racine du compte du membre.
   * @return void NE RETOURNE RIEN, et il faut Quitter le script immdiatement aprs
   */
  function DownloadTBZ($dir="") {
    global $mem;
    header("Content-Disposition: attachment; filename=".$mem->user["login"].".tar.bz2");
    header("Content-Type: application/x-bzip2");
    header("Content-Transfer-Encoding: binary");
    $d=escapeshellarg(".".$this->convertabsolute($dir,1));
    set_time_limit(0);
    passthru("/bin/tar -cj -C ".getuserpath()."/ $d");
  }

  
  /* ----------------------------------------------------------------- */
  /** Echo d'un flux .ZIP contenant tout le contenu du dossier $dir
   * @param string $dir dossier  dumper, relatif  la racine du compte du membre.
   * @return void NE RETOURNE RIEN, et il faut Quitter le script immdiatement aprs
   */
  function DownloadZIP($dir="") {
    global $mem;
    header("Content-Disposition: attachment; filename=".$mem->user["login"].".zip");
    header("Content-Type: application/x-zip");
    header("Content-Transfer-Encoding: binary");
    $d=escapeshellarg($this->convertabsolute($dir,0));
    set_time_limit(0);
    passthru("/usr/bin/zip -r - $d");
  }

  
  /* ----------------------------------------------------------------- */
  /** Fonction de tri perso utilis par filelist.
   * @access private
   */
  function _sort_filelist_name($a,$b) {
    if ($a["type"] && !$b["type"]) return 1;
    if ($b["type"] && !$a["type"]) return -1;
    return $a["name"]>$b["name"];
  }


  /* ----------------------------------------------------------------- */
  /** Efface $file et tous ses sous-dossiers s'il s'agit d'un dossier
   * A UTILISER AVEC PRECAUTION !!!
   * @param string $file Fichier ou dossier  supprimer.
   * @access private
   */
  function _delete($file) {
    // permet d'effacer de nombreux fichiers
    @set_time_limit(0);
    //chmod($file,0777);
    if (is_dir($file)) {
      $handle = opendir($file);
      while($filename = readdir($handle)) {
	if ($filename != "." && $filename != "..") {
	  $this->_delete($file."/".$filename);
	}
      }
      closedir($handle);
      rmdir($file);
    } else {
      unlink($file);
    }
  }


  /*----------------------------------------------------------*/
  /** Function d'exportation de configuration appelé par la classe m_export via un hooks
   *Produit en sorti un tableau formatté ( pour le moment) en HTML
   */
  function alternc_export_conf() {
    global $db,$err;
    $err->log("bro","export_conf");
    $str="<table border=\"1\"><caption> Browser </caption>\n";
    $str="  <browser>\n";
    $pref=$this->GetPrefs();
    
    $i=1;
    foreach ($pref as $k=>$v) {
      if (($i % 2)==0){
	$str.="   <$k>$v</$k>\n"; 
      }
      $i++;
    }
    $str.=" </browser>\n";
    
    return $str;
  }


  /*----------------------------------------------------------*/
  /** Function d'exportation des données appelé par la classe m_export via un hooks
   *@param : le chemin destination du tarball produit.
   */
  function alternc_export_data($dir){
    global $mem,$err;
    $err->log("bro","export_data");
    $dir.="html/";
    if(!is_dir($dir)){ 
      if(!mkdir($dir))
	$err->raise("bro",_("Cannot create the requested directory. Please check the permissions"));
    }
    $timestamp=date("H:i:s");

    // relacher le lock global sinon ce download va geler alternc pour
    // tout le monde
    alternc_shutdown();
    if(exec("/bin/tar cvf -  ".getuserpath()."/ | gzip -9c > ".$dir."/".$mem->user['login']."_html_".$timestamp.".tar.gz")){
      $err->log("bro","export_data_succes");
    }else{
      $err->log("bro","export_data_failed");

    }

  }


} /* Class Browser */

