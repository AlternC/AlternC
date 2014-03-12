<?php
$pathList = array_merge( array("."),explode(PATH_SEPARATOR,get_include_path()));
set_include_path(implode(PATH_SEPARATOR, $pathList));
require_once('AutoLoader.php');
// Register the directory to your include files
AutoLoader::registerDirectory('lib');
AutoLoader::registerDirectory('../bureau/class');
AutoLoader::registerDirectory('.');

