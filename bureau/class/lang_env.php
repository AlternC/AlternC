<?php

$lang_translation = array(# If you comment lang here, it won't be displayed. 
    "fr_FR" => "Français",
    "en_US" => "English",
    "es_ES" => "Español",
    #			"it_IT" => "Italiano",
    #			"de_DE" => "Deutsch",
    #			"pt_BR" => "Portuguese",
    #    "nl_NL" => "Dutch",
);

global $arr_lang_translation;
$arr_lang_translation = $lang_translation; // not pretty but I don't want side effect right now

function update_locale($langpath) {
    global $arr_lang_translation;
    $locales = array();
    $file = file("/etc/locale.gen", FILE_SKIP_EMPTY_LINES);
    if (!is_array($file)) {
        return $locales;
    }
    foreach ($file as $v) {
        if ((preg_match("/^([a-z][a-z]_[A-Z][A-Z])/", trim($v), $mat) && file_exists($langpath . '/' . $mat[1]))) {
            if (!array_key_exists($mat[1], $arr_lang_translation)) {
                continue;
            }
            $locales[$mat[1]] = $mat[1];
        }
    }
    if (!count($locales)) {
        $locales = array("en_US" => "en_US");
    }
    return $locales;
}

// setlang is on the link at the login page
if (isset($_REQUEST["setlang"])) {
    $lang = $_REQUEST["setlang"];
    $setlang = $_REQUEST["setlang"];
} elseif (isset($_COOKIE['lang'])) {
    $lang = $_COOKIE['lang'];
}

$langpath = bindtextdomain("alternc", ALTERNC_LOCALES);

// Create or update a locale.php file if it is outdated.
$locales = update_locale($langpath);

// Default to en_US : 
if (!isset($_SERVER["HTTP_ACCEPT_LANGUAGE"])) {
    $_SERVER["HTTP_ACCEPT_LANGUAGE"] = "en_US";
}

if (!(isset($lang))) {  // Use the browser first preferred language
    $lang = strtolower(substr(trim($_SERVER["HTTP_ACCEPT_LANGUAGE"]), 0, 5));
}


if (!isset($locales[$lang])) { // Requested language not found in locales
    // treat special cases such as en_AU or fr_BF : use the language only, not the country.
    $ll = substr($lang, 0, 2);
    foreach ($locales as $l) {
        if (substr($l, 0, 2) == $ll) {
            $lang = $l;
            break;
        }
    }
}

if (!isset($locales[$lang])) {
    foreach($locales as $lang) break; // take the first lang
}
if (isset($setlang) && isset($lang)) {
    setcookie("lang", $lang);
}

// User chose a non existent language, select the first available one 
if ($lang == NULL) {
    $lang = "en_US";
}

/* Language ok, set the locale environment */
putenv("LC_MESSAGES=" . $lang);
putenv("LANG=" . $lang);
putenv("LANGUAGE=" . $lang);
// this locale MUST be selected in "dpkg-reconfigure locales"
setlocale(LC_ALL, $lang);
textdomain("alternc");

$empty = "";
if (_($empty) && preg_match("#charset=([A-Za-z0-9\.-]*)#", _($empty), $mat)) {
    $charset = $mat[1];
}
if (!isset($charset) || !$charset) {
    $charset = "UTF-8";
}
bind_textdomain_codeset("alternc", "$charset");
