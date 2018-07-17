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
 * Manages APACHE 2.4+ vhosts templates in AlternC 3.5+
 * 
 * @copyright AlternC-Team 2000-2018 https://alternc.com/
 */
class m_apache {

    var $shouldreload;

    // only values allowed for https in subdomaines table.
    var $httpsmodes=array("http","https","both"); 
    
    // Slave AlternC instances can know the last reload time thanks to this
    var $reloadfile="/run/alternc/apache-reload";
    // Where do we find apache template files ?
    var $templatedir="/etc/alternc/templates/apache2";
    // Where do we store all Apache vhosts ?
    var $vhostroot="/var/lib/alternc/apache-vhost/";

    // launched before any action by updatedomains 
    function hook_updatedomains_web_pre() {
        $this->shouldreload=false;
    }

    // launched for each FQDN for which we want a new vhost template 
    function hook_updatedomains_web_add($subdomid) {
        global $msg,$db;

        $db->query("SELECT sd.*, dt.only_dns, dt.has_https_option, m.login FROM domaines_type dt, sub_domaines sd LEFT JOIN membres m ON m.uid=sd.compte WHERE dt.name=sd.type AND sd.web_action!='OK' AND id=?;",array($subdomid));
        $db->next_record();
        $subdom=$db->Record;

        // security : only AlternC account's UIDs
        if ($subdom["compte"]<1999) {
            $msg->raise("ERROR","apache","Subdom ".$subdom["id"]." for domain ".$subdom["sub"].".".$subdom["domaine"]." has id ".$subdom["compte"].". Skipped");
            return 1;
        }

        // search for the template file:
        $template = $this->templatedir."/".strtolower($subdom["type"]);
        if ($subdom["has_https_option"] && in_array($subdom["https"],$this->httpsmodes)) {
            $template.="-".$subdom["https"];
        }
        $template.=".conf";
        if (!is_file($template)) {
            $msg->raise("ERROR","apache","Template $template not found for subdom ".$subdom["id"]." for domain ".$subdom["sub"].".".$subdom["domaine"].". Skipped");
            return 1;
        }

        $subdom["fqdn"]=$subdom["sub"].(($subdom["sub"])?".":"").$subdom["domaine"];
        // SSL information $subdom["certificate_id"] may be ZERO => it means "take id 0 which is snakeoil cert"
        $cert = $ssl->get_certificate_path($subdom["certificate_id"]);
        if ($cert["chain"]) {
            $chainline="SSLCertificateChainFile ".$cert["chain"];
        } else {
            $chainline="";
        }
        // Replace needed vars in template file
        $tpl=file_get_contents($template);
        $tpl = strtr($tpl, array(
            "%%LOGIN%%" => $subdom['login'],
            "%%fqdn%%" => $subdom['fqdn'],
            "%%document_root%%" => getuserpath($subdom['login']) . $subdom['valeur'],
            "%%account_root%%" => getuserpath($subdom['login']),
            "%%redirect%%" => $subdom['valeur'],
            "%%UID%%" => $subdom['compte'],
            "%%GID%%" => $subdom['compte'],
            "%%mail_account%%" => $subdom['mail'],
            "%%user%%" => "FIXME",
            "%%CRT%%" => $cert["cert"],
            "%%KEY%%" => $cert["key"],
            "%%CHAINLINE%%" => $chainline,
        ));
        // and write the template
        $confdir = $this->vhostroot."/".substr($subdom["compte"],-1)."/".$subdom["compte"];
        @mkdir($confdir,0755,true);
        file_put_contents($confdir."/".$subdom["fqdn"].".conf");
        $this->shouldreload=true;

        return 0; // shell meaning => OK ;) 
    } // hook_updatedomains_web_add


    // ------------------------------------------------------------
    /**
     *  launched for each FQDN for which we want to delete a vhost template 
     */
    function hook_updatedomains_web_del($subdomid) {
        $db->query("SELECT sd.*, dt.only_dns, dt.has_https_option, m.login FROM domaines_type dt, sub_domaines sd LEFT JOIN membres m ON m.uid=sd.compte WHERE dt.name=sd.type AND sd.web_action!='OK' AND id=?;",array($subdomid));
        $db->next_record();
        $subdom=$db->Record;
        $confdir = $this->vhostroot."/".substr($subdom["compte"],-1)."/".$subdom["compte"];
        @unlink($confdir."/".$subdom["fqdn"].".conf");
        $this->shouldreload=true;
    }

    
    // ------------------------------------------------------------
    /** 
     * launched at the very end of updatedomains 
     */
    function hook_updatedomains_web_post() {
        global $msg;
        if ($this->shouldreload) {

            // concatenate all files into one
            $this->concat();

            // reload apache 
            $ret=0;
            exec("apache2ctl graceful 2>&1",$out,$ret);
            touch($this->reloadfile);
            if ($ret!=0) {
                $msg->raise("ERROR","apache","Error while reloading apache, error code is $ret\n".implode("\n",$out));
            } else {
                $msg->raise("INFO","apache","Apache reloaded");
            }
        }
        
    }

    // ------------------------------------------------------------
    /** 
     * Concatenate all files under $this->vhostroot
     * into one (mindepth=2 though), 
     * this function is faster than any shell stuff :D 
     */
    private function concat() {
        global $msg;
        $d=opendir($this->vhostroot);
        $f=fopen($this->vhostroot."/vhosts_all.conf.new","wb");
        if (!$f) {
            $msg->raise("FATAL","apache","Can't write vhosts_all file");
            return false;
        }
        while (($c=readdir($d))!==false) {
            if (substr($c,0,1)!="." && is_dir($this->vhostroot."/".$c)) {
                $this->subconcat($f,$this->vhostroot."/".$c);
            }
        }
        closedir($d);
        fclose($f);
    }
    
    private function subconcat($f,$root) {
        // recursive cat :)
        $d=opendir($root);
        while (($c=readdir($d))!==false) {
            if (substr($c,0,1)!=".") {
                if (is_dir($root."/".$c)) {
                    $this->subconcat($f,$root."/".$c); // RECURSIVE CALL
                }
                if (is_file($root."/".$c)) {
                    fputs($f,file_get_contents($root."/".$c)."\n");
                }
            }
        }
        closedir($d);
    }
    
} // m_apache

