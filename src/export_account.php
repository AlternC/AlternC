#!/usr/bin/php -q
<?php
/**
* Script permettant d'exporter la configuration d'un compte Alternc ainsi que les données associé
* A lancer par exemple avant une suppression de compte.
* Les seuls arguments necessaires sont l'iud du compte à exporter, ainsi que le repertoire ou le dump sera effectué.
* l'export ce fait sous l'arborescence <dir>/<login>/date/ ou les dumps de configuration sont déposées.
* Deux sous dossier sont ensuite crée : html ( dump des fichiers web et de la configuration du compte ) 
* et un dossier sql contenant toutes les bases de données de l'utilisateur.
*/


require("/var/alternc/bureau/class/config_nochk.php");

global $L_VERSION;

if(!chdir("/var/alternc/bureau")){
    exit(1);
}
if($argc != 3 ){
    echo "usage : export.php <uid> <directory>\n";
    exit (1);
}

$my_id=$argv[1];
# TODO here test $my_id is numeric
if((intval($my_id))==0 || (intval($myid)==1)){
    echo "bad argument: expecting a 4 digit IUD";
    exit (1);
}

$dir=$argv[2];
echo "\n === Export of account $my_id === to $dir\n\n";


# Connect in this user
$admin->enabled=1;
$mem->su($my_id);
  


        if(!is_dir($dir)){
            if(!mkdir($dir)){
                echo "creating dir : ".$dir." failed";     
                exit(1);
            }
        }
        $dir.=$mem->user["login"]."/";
        if(!is_dir($dir)){
            if(!mkdir($dir)){
                echo "creating dir : ".$dir." failed";     
                exit(1);
            }
        }
        $timestamp=date("Y:m:d");
        $dir.=$timestamp."/";
        if(!is_dir($dir)){
            if(!mkdir($dir)){
                echo "creating dir : ".$dir." failed";     
                exit(1);
            }
        }





# Get the conf
$conf_user=Array();
$conf_user=$export->export_conf();

$file_conf=$dir."dump_conf_".date("H:i:s");
echo $file_conf;
$file=fopen($file_conf."dump","ab");
fputs($file,"<?xml version=\"1.0\" encoding=\"ISO-8859-1\"?\> \n");

fputs($file,"<version>:".$L_VERSION."</version>\n");
fputs($file,"<user>".$mem->user["login"]."</user>\n");
foreach($conf_user as $string){
    fputs($file,$string);
}
//fputs($file,"</html></body>");
fclose($file);



# Get the data
$export->export_data($dir);
$mem->unsu();
exit(0);

?>
