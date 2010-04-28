<?php

function update_locale($langpath) {
  $locales=array();
  $f=@fopen("/etc/locale.gen","rb");
  if ($f) {
    while ($s=fgets($f,1024)) {
      if (preg_match("/^([a-z][a-z]_[A-Z][A-Z])/",trim($s),$mat) && file_exists($langpath . '/' . $mat[1])) {
	$locales[$mat[1]]=$mat[1];
      }
    }
    fclose($f);
  }
  return $locales;
}

// setlang is on the link at the login page
if (isset($_REQUEST["setlang"])) {
  $lang=$_REQUEST["setlang"];
}

$langpath = bindtextdomain("alternc", "/var/alternc/bureau/locales");

// Create or update a locale.php file if it is outdated.
$locales = update_locale($langpath);

if (!(isset($lang))) {  // Use the browser first preferred language
  $lang=strtolower(substr(trim($_SERVER["HTTP_ACCEPT_LANGUAGE"]),0,5));
}


if (!$locales[$lang]) { // Requested language not found in locales
  // treat special cases such as en_AU or fr_BF : use the language only, not the country.
  $ll=substr($lang,0,2);
  foreach($locales as $l) {
    if (substr($l,0,2)==$ll) {
      $lang=$l;
      break;
    }
  }
}

if (!isset($locales[$lang])) $lang=$locales[0]; 

if (isset($setlang) && isset($lang)) {
  setcookie("lang",$lang);
}

// User chose a non existent language, select the first available one 
if ($lang == NULL) {
  $lang = "en_US";
}

/* Language ok, set the locale environment */
putenv("LC_MESSAGES=".$lang);
putenv("LANG=".$lang);
putenv("LANGUAGE=".$lang);
// this locale MUST be selected in "dpkg-reconfigure locales"
setlocale(LC_ALL,$lang); 
textdomain("alternc");

if (_("") && preg_match("#charset=([A-Za-z0-9\.-]*)#",_(""),$mat)) {
  $charset=$mat[1];
 }

?>
