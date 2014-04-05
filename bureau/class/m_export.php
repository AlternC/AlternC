<?php
  /** Constructor
Classe d'export de compte Alternc.
Cette classe ce contente d'invoquer les fonctions d'exportation de configuration et de données d'un compte,
presentes dans les classes concernées.
  */
Class m_export {
     /**
      * 
      */
    function m_export() {
    }

     /**
      * 
      * @global m_hooks     $hooks
      * @return type
      */
    function export_conf(){
        global $hooks;
        $config=$hooks->invoke('alternc_export_conf');
        return $config;
    }

/** le repertoire de base est passé en paramettre puis en construit une arborescence de la forme
<dir>/<user>/<timestamp/ qui contiendra les dossier de dump html et sql
*/    
     /**
      * 
      * @global m_hooks     $hooks
      * @global    m_mem   $mem
      * @param type $dir
      */
    function export_data($dir){
        global $hooks, $mem;


        $hooks->invoke('alternc_export_data', Array($dir));
    }

}// export Class end

