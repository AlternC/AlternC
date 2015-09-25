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
  Purpose of file: Manage hook system.
  ----------------------------------------------------------------------
 */

/**
 * This class manage menu.
 * 
 * @copyright    AlternC-Team 2002-2005 http://alternc.org/
 */
class m_menu {
    /* --------------------------------------------------------------------------- */

    /** Constructor
     * menu([$mid]) Constructeur de la classe menu, ne fait rien pour le moment
     */
    function m_menu() {
        
    }

    function getmenu() {
        global $hooks, $quota, $mem;

        // Force rebuilding quota, in case of add or edit of the quota and cache not up-to-date
        $mesq = $quota->getquota("", true); // rebuild quota
        // Get menu objects
        $lsto = $hooks->invoke('hook_menu');

        // Get system menu
        $sm = $this->system_menu();

        // Merge it !
        $lst = array_merge($sm, $lsto);

        // Sort it
        uasort($lst, 'm_menu::order_menu');

        // Get user specific menu visibility options
        $mop = $mem->session_tempo_params_get('menu_toggle');

        foreach ($lst as $k => $v) {

            if (empty($v)) {
                unset($lst[$k]);
                continue;
            }

            // Set the javascript toggle link for menu asking for it
            if ($v['link'] == 'toggle') {
                $lst[$k]['link'] = 'javascript:menu_toggle(\'menu-' . $k . '\');';
            }

            // Be sure that the default visibility is true
            if (!isset($lst[$k]['visibility'])) {
                $lst[$k]['visibility'] = true;
            }

            // Set the user's specific visibility option
            if (isset($mop["menu-$k"])) {
                if ($mop["menu-$k"] == "hidden") {
                    $lst[$k]['visibility'] = false;
                }
                if ($mop["menu-$k"] == "visible") {
                    $lst[$k]['visibility'] = true;
                }
            }

            if (isset($mesq[$k])) { // if there are some quota for this class
                // Hide the menu if there are none and not allowed to create
                if ($mesq[$k]['t'] < 1 && $mesq[$k]['u'] < 1) {
                    unset($lst[$k]);
                    continue;
                }

                // Set the quota in the menu object
                $lst[$k]['quota_used'] = $mesq[$k]['u'];
                $lst[$k]['quota_total'] = $mesq[$k]['t'];
            } // end if there are some quota for this class
        }

        return $lst;
    }

    function order_menu($a, $b) {
        // Use to order the menu with a usort
        return $a['pos'] > $b['pos'];
    }

    function system_menu() {
        // Here some needed menu who don't have a class
        global $help_baseurl, $lang_translation, $locales;

        $m = array(
            'home' =>
            array(
                'title' => _("Home / Information"),
                'ico' => 'images/home.png',
                'link' => 'main.php',
                'pos' => 0,
            ),
            'logout' =>
            array(
                'title' => _("Logout"),
                'ico' => 'images/exit.png',
                'link' => 'mem_logout.php',
                'pos' => 170,
            ),
            'help' =>
            array(
                'title' => _("Online help"),
                'ico' => 'images/help.png',
                'target' => 'help',
                'link' => $help_baseurl,
                'pos' => 140,
            ),
            'lang' =>
            array(
                'title' => _("Languages"),
                'ico' => '/images/lang.png',
                'visibility' => false,
                'link' => 'toggle',
                'links' => array(),
                'pos' => 150,
            )
        );
        foreach ($locales as $l) {
            $m['lang']['links'][] = array('txt' => (isset($lang_translation[$l])) ? $lang_translation[$l] : $l, 'url' => "/login.php?setlang=$l");
        }
        return $m;
    }

}

/* Class menu */
