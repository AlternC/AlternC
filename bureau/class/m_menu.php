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
 * This class manage the left menu of AlternC
 * 
 * @copyright AlternC-Team 2000-2017 https://alternc.com/
 */
class m_menu {

    /**
     * get all menus to display, 
     * uses hooks
     */
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

    /** 
     * utilitary function used by usort() to order menus
     */
    function order_menu($a, $b) {
        return $a['pos'] > $b['pos'];
    }

    /**
     * some menus that don't have an attached class
     */
    function system_menu() {
        global $help_baseurl, $lang_translation, $locales;

        $m = array(
            'home' =>
            array(
                'title' => _("Home / Information"),
                'link' => 'main.php',
                'pos' => 0,
            ),
            'logout' =>
            array(
                'title' => _("Logout"),
                'link' => 'mem_logout.php',
                'pos' => 170,
            ),
            'help' =>
            array(
                'title' => _("Online help"),
                'target' => 'help',
                'link' => $help_baseurl,
                'pos' => 140,
            ),
            'lang' =>
            array(
                'title' => _("Languages"),
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

} /* Class m_menu */
