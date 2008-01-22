<?php
/*
 $Id: m_bro.php,v 1.15 2005/12/18 09:51:32 benjamin Exp $
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
 Original Author of file:
 Purpose of file:
 ----------------------------------------------------------------------
*/

/* Add the mime type list */
@include("mime.php");

/**
* Classe de gestion du navigateur de fichiers en ligne.
*
* Cette classe permet de gérer les fichiers, dossiers ...
* d'un membre hébergé.<br />
* Copyleft {@link http://alternc.net/ AlternC Team}
*
* @copyright    AlternC-Team 2002-11-01 http://alternc.net/
*
*/


class m_bro {

  /** Mode d'affichage des fichiers en colonne */
  var $l_mode=array(0=>"1 column, detailed",1=>"2 columns, short",2=>"3 columns, short");

  /** Mode de téléchargement d'un dossier compressé (zip,bz,tar,..) */
  var $l_tgz=array(0=>"tgz (Linux)",1=>"tar.bz2 (Linux)",2=>"zip (Windows/Dos)",3=>"tar.Z (Unix)");

  /** Faut-il afficher ou non les icones ? */
  var $l_icons=array(0=>"No",1=>"Yes");

  /** Que fait-on après la création d'un fichier ? */
  var $l_createfile=array(0=>"Go back to the file manager",1=>"Edit the newly created file");

  /** Cache des descriptions de fichier extraits de la base
   * @access private
   */
  var $mime_desc=array();

  /** Cache des icones extraits de la base
   * @access private
   */
  var $mime_icon=array();

  /** Cache des types mimes extraits de la base
   * @access private
   */
  var $mime_type=array();

  /** Choix des polices d'édition de fichiers */
  var $l_editor_font=array("Arial, Helvetica, Sans-serif","Times, Bookman, Serif","Courier New, Courier, Fixed");

  /** Choix des tailles de police d'édition de fichiers */
  var $l_editor_size=array("18px","14px","12px","10px","8px","0.8em","0.9em","1em","1.1em","1.2em");

  /* ----------------------------------------------------------------- */
  /** Constructeur */
  function m_bro() {
  }

  /* ----------------------------------------------------------------- */
  /** Vérifie un dossier relatif au dossier de l'utilisateur courant
   *
   * @param string $dir Dossier (absolu que l'on souhaite vérifier
   * @return string Retourne le nom du dossier vérifié, relatif au
   * dossier de l'utilisateur courant, éventuellement corrigé.
   * ou FALSE si le dossier n'est pas dans le dossier de l'utilisateur.
   */
  function convertabsolute($dir,$strip=1) {
    global $mem;
    $root="/var/alternc/html/".substr($mem->user["login"],0,1)."/".$mem->user["login"];
    // Sauvegarde du chemin de base.
    $root_alternc = $root ;
    // Passage du root en chemin réel (différent avec un lien)
    $root=realpath($root) ;
    // separer le chemin entre le repertoire et le fichier
    $file = basename($dir);
    $dir = dirname($dir);
    $dir=realpath($root."/".$dir);
    // verifier que le repertoire est dans le home de l'usgaer
    if (substr($dir,0,strlen($root))!=$root) {
      return false;
    } 
    // recomposer le chemin
    $dir = $dir . '/' . $file;
    if ($strip) {
      $dir=substr($dir,strlen($root));
    } else {
      // si on ne strip pas, il faut enlever le chemin réel 
      // et mettre la racine d'alternc pour éviter les
      // problèmes de lien depuis /var/alternc ! 
      $dir=$root_alternc . substr($dir,strlen($root));
    }
    if (substr($dir,-1)=="/") {
      return substr($dir,0,strlen($dir)-1);
    } else
      return $dir;
  }

  /* ----------------------------------------------------------------- */
  /** Retourne un tableau contenant la liste des fichiers du dossier courant
   * Ce tableau contient tous les paramètres des fichiers du dossier courant
   * sous la forme d'un tableau indexé de tableaux associatifs comme suit :
   * $a["name"]=nom du fichier / dossier
   * $a["size"]=Taille totale du fichier / dossier + sous-dossier
   * $a["date"]=Date de dernière modification
   * $a["type"]=Type du fichier (1 pour fichier, 0 pour dossier)
   * @param string $dir dossier relatif au dossier racine du compte du
   * membre courant
   * @return array le tableau contenant les fichiers de $dir, et
   */
  function filelist($dir="") {
    global $db,$cuid;
    $db->query("UPDATE browser SET lastdir='$dir' WHERE uid='$cuid';");
    $absolute=$this->convertabsolute($dir,0);
    if ($dir = @opendir($absolute)) {
      while (($file = readdir($dir)) !== false) {
	if ($file!="." && $file!="..") {
	  $c[]=array("name"=>$file, "size"=>$this->fsize($absolute."/".$file), "date"=>filemtime($absolute."/".$file), "type"=> (!is_dir($absolute."/".$file)) );
	}
      }
      closedir($dir);
    }
    if (is_array($c)) {
      usort ($c, array("m_bro","_sort_filelist_name"));
      return $c;
    } else {
      return array();
    }
  }

  /* ----------------------------------------------------------------- */
  /** Retourne un tableau contenant les préférences de l'utilisateur courant
   * Ce tableau aqssociatif contient les valeurs des champs de la table "browser"
   * pour l'utilisateur courant.
   * @return array Tableau des préférences de l'utilisateur courant.
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
  /** Modifie les préférences de l'utilisateur courant.
   *
   * @param integer $editsizex  Taille de l'éditeur (nombre de colonnes)
   * @param integer $editsizey  Taille de l'éditeur (nombre de lignes)
   * @param integer $listmode   Mode d'affichage de la liste des fichiers
   * @param integer $showicons  Faut-il afficher / cacher les icones des fichiers
   * @param integer $downfmt    Dans quel format faut-il télécharger les dossiers compressés
   * @param integer $createfile Faut-il editer/revenir au browser après création d'un fichier
   * @param integer $showtype Faut-il afficher le type mime des fichiers
   * @param integer $editor_font  Quelle police faut-il utiliser pour l'éditeur
   * @param integer $editor_size  Quelle taille de police faut-il utiliser pour l'éditeur
   * @param integer $golastdir  Faut-il revenir à la racine ou au dernier dossier visité ?
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
  /** Retourne le nom du fichier icone associé au fichier donc le nom est $file
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
    if (!$bro_icon[$ext]) {
	return "file.png";
    } else {
	return $bro_icon[$ext].".png";
    }
  }

  /* ----------------------------------------------------------------- */
  /** Retourne le type mime associé au fichier donc le nom est $file
   * <b>Note</b>: Les types mimes sont mis en cache sur la page courante.
   * Le type mime est déterminé d'après l'extension du fichier.
   * @param string $file Fichier dont on souhaite connaitre le type mime
   * @return string Type mime / Sous type du fichier demandé
   */
  function mime($file) {
    global $bro_type;
    if (!strpos($file,".") && substr($file,0,1)!=".") {
      return _("File");
    }
    $t=explode(".",$file);
    if (!is_array($t))
      $ext=$t;
    else
      $ext=$t[count($t)-1];
    // Now seek the extension
    if (!$bro_type[$ext]) {
	return _("File");
    } else {
	return _($bro_type[$ext]);
    }
  }

  /* ----------------------------------------------------------------- */
  /** Retourne la taille du fichier $file
   * si $file est un dossier, retourne la taille de ce dossier et de tous
   * ses sous dossiers.
   * @param string $file Fichier dont on souhaite connaitre la taille
   * @return integer Taille du fichier en octets.
   * TODO : create a du cache ...
   */
  function fsize($file) {
    if (is_dir($file)) {
      return "-";
    } else {
      return filesize($file);
    }
  }

  /* ----------------------------------------------------------------- */
  /** Crée le dossier $file dans le dossier (parent) $dir
   * @param string $dir dossier dans lequel on veut créer un sous-dossier
   * @param string $file nom du dossier à créer
   * @return boolean TRUE si le dossier a été créé, FALSE si une erreur s'est produite.
   */
  function CreateDir($dir,$file) {
    global $db,$cuid,$err;
    $file=ssla($file);
    $absolute=$this->convertabsolute($dir."/".$file,0);
    if ($absolute && !file_exists($absolute)) {
      mkdir($absolute,00777);
      $db->query("UPDATE browser SET crff=1 WHERE uid='$cuid';");
      return true;
    } else {
      $err->raise("bro",1);
      return false;
    }
  }

  /* ----------------------------------------------------------------- */
  /** Crée un fichier vide dans un dossier
   * @param string $dir Dossier dans lequel on crée le nouveau fichier
   * @param string $file Fichier que l'on souhaite créer.
   * @return boolean TRUE si le dossier a été créé, FALSE si une erreur s'est produite.
   */
  function CreateFile($dir,$file) {
    global $db,$err,$cuid;
    $file=ssla($file);
    $absolute=$this->convertabsolute($dir."/".$file,0);
    if (!$absolute) {
      $err->raise("bro",1);
      return false;
    }
    if (!file_exists($absolute)) {
      touch($absolute);
    }
    $db->query("UPDATE browser SET crff=0 WHERE uid='$cuid';");
    return true;
  }

  /* ----------------------------------------------------------------- */
  /** Efface les fichiers du tableau $file_list dans le dossier $R
   * @param array $file_list Liste des fichiers à effacer.
   * @param string $R Dossier dans lequel on efface les fichiers
   * @return boolean TRUE si les fichiers ont été effacés, FALSE si une erreur s'est produite.
   */
  function DeleteFile($file_list,$R) {
    global $err, $mem;
    $root=realpath("/var/alternc/html/".substr($mem->user["login"],0,1)."/".$mem->user["login"]);
    $absolute=$this->convertabsolute($R,0);
    if (!$absolute && strpos($root,$absolute) === 0 && strlen($absolute) > (strlen($root)+1) ) {
      $err->raise("bro",1);
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
   * @param string $R dossier dans lequel se trouve les fichiers à renommer.
   * @param array of string $old Ancien nom des fichiers
   * @param array of string $new Nouveau nom des fichiers
   * @return boolean TRUE si les fichiers ont été renommés, FALSE si une erreur s'est produite.
   */
  function RenameFile($R,$old,$new) {
    global $err;
    $absolute=$this->convertabsolute($R,0);
    if (!$absolute) {
      $err->raise("bro",1);
      return false;
    }
    $alea=".".time().rand(1000,9999);
    for ($i=0;$i<count($old);$i++) {
      $old[$i]=ssla($old[$i]); // strip slashes if needed
      $new[$i]=ssla($new[$i]);
      if (!strpos($old[$i],"/") && !strpos($new[$i],"/")) {  // caractère / interdit dans old ET dans new...
	@rename($absolute."/".$old[$i],$absolute."/".$old[$i].$alea);
      }
    }
    for ($i=0;$i<count($old);$i++) {
      if (!strpos($old[$i],"/") && !strpos($new[$i],"/")) {  // caractère / interdit dans old ET dans new...
	@rename($absolute."/".$old[$i].$alea,$absolute."/".$new[$i]);
      }
    }

    return true;
  }

  /* ----------------------------------------------------------------- */
  /** Déplace les fichier de $d du dossier $old vers $new
   * @param array of string $d Liste des fichiers du dossier $old à déplacer
   * @param string $old dossier dans lequel se trouve les fichiers à déplacer.
   * @param string $new dossier vers lequel seront déplacés les fichiers.
   * @return boolean TRUE si les fichiers ont été renommés, FALSE si une erreur s'est produite.
   */
  function MoveFile($d,$old,$new) {
    global $err;
    $old=$this->convertabsolute($old,0);
    if (!$old) {
      $err->raise("bro",1);
      return false;
    }
    $new=$this->convertabsolute($new,0);
    if (!$new) {
      $err->raise("bro",1);
      return false;
    }
    if ($old==$new) {
      $err->raise("bro",2);
      return false;
    }
    for ($i=0;$i<count($d);$i++) {
      $d[$i]=ssla($d[$i]); // strip slashes if needed
      if (!strpos($d[$i],"/") && file_exists($old."/".$d[$i]) && !file_exists($new."/".$d[$i])) {  
        @rename($old."/".$d[$i],$new."/".$d[$i]);
      }
    }
    return true;
  }

  /* ----------------------------------------------------------------- */
  /** Recoit un champ file upload (Global) et le stocke dans le dossier $R
   * Le champ file-upload originel doit s'appeler "userfile" et doit
   * bien être un fichier d'upload.
   * @param string $R Dossier dans lequel on upload le fichier
   */
  function UploadFile($R) {
    global $_FILES,$err;
    $absolute=$this->convertabsolute($R,0);
    if (!$absolute) {
      $err->raise("bro",1);
      return false;
    }
    if (!strpos($_FILES['userfile']['name'],"/")) {
      // move_uploaded_file($_FILES['userfile']['tmp_name'], $absolute."/".$_FILES['userfile']['name']);
      if (is_uploaded_file($_FILES['userfile']['tmp_name'])) {
        if (!file_exists($absolute."/".$_FILES['userfile']['name'])) {
          touch($absolute."/".$_FILES['userfile']['name']);
        }
        copy($_FILES['userfile']['tmp_name'], $absolute."/".$_FILES['userfile']['name']);
      } else {
	$err->log("bro","uploadfile","Tentative d'attaque : ".$_FILES['userfile']['tmp_name']);
      }
      // TODO delete this if it works :) 
      // move_uploaded_file($_FILES['userfile']['tmp_name'], $absolute."/".$_FILES['userfile']['name']);
    }
  }

  /**
   * Extract an archive by using GNU and non-GNU tools
   * @param string $file is the full or relative path to the archive
   * @param string $dest is the path of the extract destination
   * @return boolean != 0 on error
   */
  function ExtractFile($file, $dest="./")
  {
    global $err;
    static $i=0, $ret;
    $file = $this->convertabsolute($file,0);
    $dest = $this->convertabsolute($dest,0);
    if (!$file || !$dest) {
      $err->raise("bro",1);
      return false;
    }
    $file = escapeshellarg($file);
    $dest = escapeshellarg($dest);
    if ($i == 0) {
#TODO new version of tar supports `tar xf ...` so there is no
#     need to specify the compression format
      exec("tar -xzf '$file' -C '$dest'", $void, $ret);
    } else if ($i == 1) {
      exec("tar -xjf '$file' -C '$dest'", $void, $ret);
    } else if ($i == 2) {
      exec("unzip '$file' -d '$dest'", $void, $ret);
    } else {
      return $ret;
    }

    if ($ret) {
      $i++;
      $this->ExtractFile($file, $dest);
    }
    return $ret;
  }


  /**
   * Copy a source to a destination by either copying recursively a
   * directory or by downloading a file with a URL (only http:// is
   * supported)
   * @param string $name is the application name
   * @param string $src is the path or URL
   * @param string $dest is the absolute path inside the users directory
   * @return boolean false on error
   */
  function CopyFile($name, $src, $dest)
  {
    global $err;

    /*
     * XXX: Disabled functionality until audit is completed
     */
    /*
    if (substr($src, 0, 7) == "http://") {
      $filename = basename($src);
      $extractdir = tempnam("/tmp", "brouteur");
      unlink($extractdir);
      mkdir($extractdir);

      if (!$http = @fopen($src, "rb")) {
        // Try to get a handle on $http with fsockopen instead
        ereg('^http://([^/]+)(/.*)$', $src, $eregs);
        $hostname = $eregs[1];
        $path = $eregs[2];
        $http = @fsockopen($hostname, 80);
        @fputs($http, "GET $path HTTP/1.1\nHost: $hostname\n\n");
      }
      if ($http) {
        // Save the bits
        $f = fopen("$extractdir/$filename", "wb");
        while (!feof($http)) {
          $bin = fgets($http, 16384);
          fwrite($f, $bin);
#FIXME if (!trim($bin)) break;
        }
        fclose($f);
        fclose($http);
      } else {
        // Dammit, try with wget than
        exec("wget -q '$src' -O '$extractdir/$filename'", $void, $ret);
        if ($ret) {
          $error = _("Unable to download the web application's package.");
          return false;
        }
      }

      // Now extract that package
      if (!brouteur_extract("$extractdir/$filename", $extractdir)) {
        $error = _("Unable to extract the files");
        return false;
      }
      unlink("$extractdir/$filename");

      // Corrupt $src since we want to copy $extractdir/packagename
      $hd = opendir($extractdir);
      while ($file = readdir($hd)) {
        if ($file != "." && $file != "..") {
          $src = "$extractdir/$file";
          break;
        }
      }
    }
    */

    // Last step // Copy -R
    $src = $this->convertabsolute($src);
    $dest = $this->convertabsolute($dest);
    if (!$src || !$dest) {
      $err->raise("bro",1);
      return false;
    }
    $src = escapeshellarg($src);
    $dest = escapeshellarg($dest);
    // TODO: write a recursive copy function(?)
    exec("cp -Rpf '$src' '$dest'", $void, $ret);
    if ($ret) {
      $err->raise("bro","Errors happened while copying the source to destination. cp return value: %d", $ret);
      return false;
    }

    return true;
  }

  /* ----------------------------------------------------------------- */
  /** Affiche le chemin et les liens de la racine au dossier $path
   * Affiche autant de liens HTML (anchor) que le chemin $path contient de
   * niveaux de dossier. Chaque lien est associé à la page web $action
   * à laquelle on ajoute le paramètre R=+Le nom du dossier courant.
   * @param string $path Dossier vers lequel on trace le chemin
   * @param string $action Page web de destination des liens
   * @return string le code HTML ainsi obtenu.
   */
  function PathList($path,$action) {
    $path=$this->convertabsolute($path,1);
    $a=explode("/",$path);
    if (!is_array($a)) $a=array($a);
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
   * du fichier est reformaté pour pouvoir entrer dans un champs TextArea
   * @param string $R Dossier dans lequel on cherche le fichier
   * @param string $file Fichier dont on souhaite obtenir le contenu.
   * @return boolean retourne TRUE si le fichier a bien été émis sur
   * echo, ou FALSE si une erreur est survenue.
   */
  function content($R,$file) {
    global $err;
    $absolute=$this->convertabsolute($R,0);
    if (!strpos($file,"/")) {
      $absolute.="/".$file;
      if (file_exists($absolute)) {
	$content = @file($absolute);
	for($i=0;$i<count($content);$i++) {
	  echo str_replace("<","&lt;",str_replace("&","&amp;",$content[$i]));
	}
      }
    } else {
      $err->raise("bro",1);
      return false;
    }
  }

  /** Cache des urls pour VIEW
   * @access private
   */
  var $cacheurl=array();

  // Return a browsing url if available.
  // Maintain a url cache (positive AND negative(-) cache)
  /* ----------------------------------------------------------------- */
  /** Retourne une url de navigation pour le fichier $name du dossier $dir
   * Les url sont mises en caches. Il se peut qu'aucune url n'existe, ou que
   * celle-ci soit protégée par un .htaccess.
   * @param string $dir Dossier concerné
   * @param string $name Fichier dont on souhaite obtenir une URL
   * @return string URL concernée, ou FALSE si aucune URL n'est disponible pour ce fichier
   */
  function viewurl($dir,$name) {
    global $db,$cuid;
    // Is it in cache ?
    if (substr($dir,0,1)=="/") $dir=substr($dir,1);
    if (substr($dir,-1)=="/") $dir=substr($dir,0,-1);
    $dir=str_replace("%2F", "/", urlencode($dir));
    $name=urlencode($name);
    if (!$this->cacheurl["d".$dir]) {
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

  function content_send($R,$file) {
    global $err;
    $absolute=$this->convertabsolute($R,0);
    if (!strpos($file,"/")) {
      $absolute.="/".$file;
      if (file_exists($absolute)) {
	$content = @file($absolute);
	for($i=0;$i<count($content);$i++) {
	  echo stripslashes($content[$i]);
	}
      }
    } else {
      $err->raise("bro",1);
      return false;
    }
  }

  /* ----------------------------------------------------------------- */
  /** Sauve le fichier $file dans le dossier $R avec pour contenu $texte
   * le contenu est issu d'un textarea, et ne DOIT PAS contenir de \ ajoutés
   * automatiquement par addslashes
   * @param string $file Nom du fichier à sauver. S'il existe déjà, il sera
   *  écrasé sans confirmation.
   * @param string $R Dossier dans lequel on modifie le fichier
   * @param string $texte texte du fichier à sauver dedans
   * @return boolean TRUE si tout s'est bien passé, FALSE si une erreur s'est produite.
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
	}
      }
    } else {
      $err->raise("bro",1);
      return false;
    }
  }

  /* ----------------------------------------------------------------- */
  /** Echo d'un flux .tar.Z contenant tout le contenu du dossier $dir
   * @param string $dir dossier à dumper, relatif à la racine du compte du membre.
   * @return void NE RETOURNE RIEN, et il faut Quitter le script immédiatement après
   */
 function DownloadZ($dir="") {
    global $mem;
    header("Content-Disposition: attachment; filename=".$mem->user["login"].".tgz");
    header("Content-Type: application/x-Z");
    header("Content-Transfer-Encoding: binary");
    $d=escapeshellarg(".".$this->convertabsolute($dir,1));
    set_time_limit(0);
    passthru("/bin/tar -cZ -C /var/alternc/html/".substr($mem->user["login"],0,1)."/".$mem->user["login"]."/ $d");
  }

  /* ----------------------------------------------------------------- */
  /** Echo d'un flux .tgz contenant tout le contenu du dossier $dir
   * @param string $dir dossier à dumper, relatif à la racine du compte du membre.
   * @return void NE RETOURNE RIEN, et il faut Quitter le script immédiatement après
   */
 function DownloadTGZ($dir="") {
    global $mem;
    header("Content-Disposition: attachment; filename=".$mem->user["login"].".tgz");
    header("Content-Type: application/x-tgz");
    header("Content-Transfer-Encoding: binary");
    $d=escapeshellarg(".".$this->convertabsolute($dir,1));
    set_time_limit(0);
    passthru("/bin/tar -cz -C /var/alternc/html/".substr($mem->user["login"],0,1)."/".$mem->user["login"]."/ $d");
  }


  /* ----------------------------------------------------------------- */
  /** Echo d'un flux .tar.bz2 contenant tout le contenu du dossier $dir
   * @param string $dir dossier à dumper, relatif à la racine du compte du membre.
   * @return void NE RETOURNE RIEN, et il faut Quitter le script immédiatement après
   */
 function DownloadTBZ($dir="") {
    global $mem;
    header("Content-Disposition: attachment; filename=".$mem->user["login"].".tgz");
    header("Content-Type: application/x-bzip2");
    header("Content-Transfer-Encoding: binary");
    $d=escapeshellarg(".".$this->convertabsolute($dir,1));
    set_time_limit(0);
    passthru("/bin/tar -cj -C /var/alternc/html/".substr($mem->user["login"],0,1)."/".$mem->user["login"]."/ $d");
  }

  /* ----------------------------------------------------------------- */
  /** Echo d'un flux .ZIP contenant tout le contenu du dossier $dir
   * @param string $dir dossier à dumper, relatif à la racine du compte du membre.
   * @return void NE RETOURNE RIEN, et il faut Quitter le script immédiatement après
   */
 function DownloadZIP($dir="") {
    global $mem;
    header("Content-Disposition: attachment; filename=".$mem->user["login"].".tgz");
    header("Content-Type: application/x-zip");
    header("Content-Transfer-Encoding: binary");
    $d=escapeshellarg($this->convertabsolute($dir,0));
    set_time_limit(0);
    passthru("/usr/bin/zip -r - $d");
  }

  /* ----------------------------------------------------------------- */
  /** Fonction de tri perso utilisé par filelist.
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
   * @param string $file Fichier ou dossier à supprimer.
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


  /* ----------------------------------------------------------------- */
  /**
   * Exporte toutes les informations ftp du compte AlternC
   * @access private
   * EXPERIMENTAL 'sid' function ;) 
   */
  function alternc_export() {
    global $db,$err;
    $err->log("bro","export");
    $str="<bro>\n";
    $pref=$this->GetPrefs();
    foreach ($pref as $k=>$v) {
      $str.="  <pref>\n";
      $str.="    <".$k.">".xml_entities($v)."</".$k.">\n";
      $str.="  </pref>\n";
    }
    $str.="</bro>\n";
    
    return $str;
  }


} /* Classe BROUTEUR */

?>
