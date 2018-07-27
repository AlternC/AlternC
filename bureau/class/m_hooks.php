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
 * This class manage hooks that any other class or panel page can call.
 * or hook to.
 * 
 * @copyright AlternC-Team 2000-2017 https://alternc.com/
 */
class m_hooks {


    /**
     * invoke() permet de lancer une fonction donné en parametre dans toute les classes
     * connues de alternc, avec les parametres donnés.
     * @param string $hname nom de la fonction "hooks" que l'on cherche dans les classes
     * @param array $hparam tableau contenant les parametres
     * @param array|string $hclass tableau contenant les classes spécifique qu'on veux appeler (si on veux pas TOUTE les appeler)
     * @return array with the return of each classes
     */
    function invoke($hname, $hparam = array(), $hclass = null) {

        // Si $hclass est defini, on veut appeler le hooks QUE pour UNE 
        // classe et pas pour toute.
        if (is_null($hclass)) {
            global $classes;
        } else {
            if (is_array($hclass)) {
                $classes = $hclass;
            } else {
                $classes = array($hclass);
            }
        }

        // On parcourt les classes, si la fonction qu'on cherche
        // existe on l'execute et on rajoute ce qu'elle a retourné dans
        // un tableau
        $val = array();
        foreach ($classes as $c) {
            global $$c;
            if (method_exists($$c, $hname)) {
                //$val[$$c]=call_user_func_array(array($$c,$hname), $hparam);
                $val[$c] = call_user_func_array(array($$c, $hname), $hparam);
            }
        }

        // On retourne le tout
        return $val;
    }


    /**
     * invoke each executable script of the directory (or the specified script)
     * @param string $scripts a script or a directory
     * @param array $parameters parameters for the scripts
     * @return boolean TRUE
     */
    function invoke_scripts($scripts, $parameters = array()) {

        // First, build the list of script we want to launch
        $to_launch = array();
        if (is_file($scripts)) {
            if (is_executable($scripts)) {
                $to_launch[] = $scripts;
            }
        } else if (is_dir($scripts)) {
            foreach (scandir($scripts) as $ccc) {
                # scandir returns the file names only
                $ccc = $scripts . '/' . $ccc;
                if (is_file($ccc) && is_executable($ccc)) {
                    $to_launch[] = $ccc;
                }
            }
        } else {
            // not a file nor a directory
            return false;
        }

        // Protect each parameters
        $parameters = array_map('escapeshellarg', $parameters);
        $params = implode(" ", $parameters);

        // Launch !
        foreach ($to_launch as $fi) {
            system($fi . " " . $params);
        }

        // TODO: return something more interesting than true
        return true;
    }

} /* Class hooks */

