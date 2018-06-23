<?php

/*
  ----------------------------------------------------------------------
  AlternC - Web Hosting System
  Copyright (C) 2000-2014 by the AlternC Development Team.
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
  Purpose of file: Manage SSL Certificates and HTTPS Hosting
  ----------------------------------------------------------------------
 */

// ----------------------------------------------------------------- 
/**
 * SSL Certificates management class
 */
class m_ssl {

    const STATUS_PENDING = 0; // we have a key / csr, but no CRT 
    const STATUS_OK = 1; // we have the key, csr, crt, chain
    const STATUS_EXPIRED = 99; // The certificate is now expired.

    public $error = "";

    // Includes one or more of those flags to see only those certificates 
    // when listing them: 
    const FILTER_PENDING = 1;
    const FILTER_OK = 2;
    const FILTER_EXPIRED = 4;

    const KEY_REPOSITORY = "/var/lib/alternc/ssl/private";
    const SPECIAL_CERTIFICATE_ID_PATH = "/var/lib/alternc/ssl/special_id.json";
    
    // ----------------------------------------------------------------- 
    /**
     * Constructor
     */
    function m_ssl() {
        global $L_FQDN;
        $this->last_certificate_id=variable_get('last_certificate_id',0,'Latest certificate ID parsed by update_domains. Do not change this unless you know what you are doing');
        $this->default_certificate_fqdn=variable_get('default_certificate_fqdn',$L_FQDN,'FQDN of the certificate we will use as a default one before getting a proper one through any provider. If unsure, keep the default');
    }

    
    // -----------------------------------------------------------------
    /** 
     * Return the list of special FQDN for which we'd like to obtain a certificate too.
     * (apart from sub+domaine from sub_domaines table) 
     * used by providers to get the certs they should generate
     * also used by update_domaines to choose which cert to use for a specific fqdn
     */
    function get_fqdn_specials() {
        global $L_FQDN;
        $specials=array($L_FQDN);
        $variables=array("fqdn_dovecot","fqdn_postfix","fqdn_proftpd","fqdn_mailman");
        foreach($variables as $var) {
            $value = variable_get($var,null);
            if ($value && !in_array($value,$specials)) {
                $specials[]=$value;
            }
        }
        return $specials;
    }


    // -----------------------------------------------------------------
    /**
     * set expired certificates as such : 
     */
    function expire_certificates() {
        global $db;
        $db->query("UPDATE certificates SET status=".self::STATUS_EXPIRED." WHERE status=".self::STATUS_OK." AND validend<NOW();");
    }


    // -----------------------------------------------------------------
    /** 
     * Crontab launched every minute 
     * to search for new certificates and launch web_action="UPDATE" 
     */
    function cron_new_certs() {
        global $db,$msg,$dom;
        $db->query("SELECT max(id) AS maxid FROM certificates;");
        if (!$db->next_record()) {
            $msg->raise("ERROR","ssl",_("FATAL: no certificates in certificates table, even the SnakeOil one??"));
            return false;
        }
        $maxid=$db->Record["maxid"];
        if ($maxid>$this->last_certificate_id) {
            $db->query("SELECT id,fqdn,altnames,sslcrt FROM certificates WHERE id>?",array($this->last_certificate_id));
            $certs=array();
            // fill an array of fqdn/altnames
            while ($db->next_record()) {
                if (!$db->Record["sslcrt"]) continue; // skip NOT FINALIZED certificates !!

                list($altnames)=explode("\n",$db->Record["altnames"]);
                $certs[]=array("id"=>$db->Record["id"],"fqdn"=>$db->Record["fqdn"]);
                foreach($altnames as $altname) {
                    $certs[]=array("id"=>$db->Record["id"],"fqdn"=>$altname);
                }
            }

            // get the list of subdomains-id that match the following FQDN (or wildcard)
            $updateids=array();
            foreach($certs as $cert) {
                $subids=$this->searchSubDomain($cert["fqdn"]);
                foreach($subids as $subid) {
                    $updateids[$subid]=$cert["id"];
                }
                // if this fqdn match a special domain, update its certificate (and mark service for reloading)
                $this->update_specials_match($cert["id"],$cert["fqdn"]);
            }

            // update those subdomains 
            $dom->lock();
            foreach($updateids as $id => $certid) {
                $db->query("UPDATE sub_domaines SET web_action=? WHERE id=?;",array("UPDATE",$id));
                $msg->raise("INFO","ssl",sprintf(_("Reloading domain %s as we have new certificate %s"),$id,$certid));
            }
            $dom->unlock();
            $this->last_certificate_id=$maxid;
        }
    }


    function fqdnmatch($cert,$fqdn) {
        if ($cert==$fqdn)
            return true;
        if (substr($cert,0,2)=="*." &&
        substr($cert,2)==substr($fqdn,strpos($fqdn,".")+1) )
            return true;
        return false;
    }
    
    // -----------------------------------------------------------------
    /**
     * update special system certificate that matches the cert fqdn:
     */
    function update_specials_match($id,$fqdn) {
        global $L_FQDN;

        if ($this->fqdnmatch($fqdn,$L_FQDN)) {
            // new certificate for the panel
            $this->copycert("alternc-panel",$id);
            exec("service apache2 reload");
        }
        $variables=array("fqdn_dovecot","fqdn_postfix","fqdn_proftpd","fqdn_mailman");
        foreach($variables as $var) {
            $value = variable_get($var,null);
            if ($value) {
                if ($this->fqdnmatch($fqdn,$value)) {
                    $this->copycert("alternc-".substr($var,5),$id);
                    exec("service ".substr($var,5)." reload");
                }
            }
        }
        
    }

    // -----------------------------------------------------------------
    /**
     * copy a certificate (by its ID) to the system files
     * set the correct permissions
     * try to minimize zero-file-size risk or timing attack
     */
    function copycert($target,$id) {
        global $db;
        $db->query("SELECT * FROM certificate WHERE id=?",array($id));
        if (!$db->next_record()) return false;
        if (!file_put_contents("/etc/ssl/certs/".$target.".crt.tmp",trim($db->Record["sslcrt"])."\n".trim($db->Record["sslchain"])))
            return false;
        chown("/etc/ssl/certs/".$target.".crt.tmp","root");
        chgrp("/etc/ssl/certs/".$target.".crt.tmp","ssl-cert");
        chmod("/etc/ssl/certs/".$target.".crt.tmp",0755);
        if (!file_put_contents("/etc/ssl/private/".$target.".key.tmp",$db->Record["sslkey"])) 
            return false;
        chown("/etc/ssl/private/".$target.".key.tmp","root");
        chgrp("/etc/ssl/private/".$target.".key.tmp","ssl-cert");
        chmod("/etc/ssl/private/".$target.".key.tmp",0750);
        
        rename("/etc/ssl/certs/".$target.".crt.tmp","/etc/ssl/certs/".$target.".crt");
        rename("/etc/ssl/private/".$target.".key.tmp","/etc/ssl/private/".$target.".key");
        return true;
    }


    // -----------------------------------------------------------------
    /**
     * search for a FQDN as a fqdn or a wildcard in all subdomains currently hosted
     * return a list of subdomain-id
     */
    function searchSubDomain($fqdn) {
        global $db;
        $db->query("SELECT sd.id FROM sub_domaines sd, domaines_type dt WHERE dt.name=sd.type AND dt.only_dns=0 AND
(CONCAT(sd.sub,IF(sd.sub!='','.',''),sd.domaine)=?
OR CONCAT('*.',SUBSTRING(CONCAT(sd.sub,IF(sd.sub!='','.',''),sd.domaine),
INSTR(CONCAT(sd.sub,IF(sd.sub!='','.',''),sd.domaine),'.')+1))=?
);",
        array($fqdn,$fqdn));
        $ids=array();
        while ($db->next_record()) {
            $ids[]=$db->Record["id"];
        }
        return $ids;
    }

    
    // -----------------------------------------------------------------
    /**
     * delete old certificates (expired for more than a year)
     */
    function delete_old_certificates() {
        global $db;
        $db->query("SELECT c.id,sd.id AS used FROM certificates c LEFT JOIN sub_domaines sd ON sd.certificate_id=c.id WHERE c.status=".self::STATUS_EXPIRED." AND c.validend<DATE_SUB(NOW(), INTERVAL 12 MONTH) AND c.validend!='0000-00-00 00:00:00';");
        while ($db->next_record()) {
            if ($db->Record["used"]) {
                continue; // this certificate is used (even though it's expired :/ ) 
            }
            $CRTDIR = self::KEY_REPOSITORY . "/" . floor($db->Record["id"]/1000);
            @unlink($CRTDIR."/".$db->Record["id"].".crt");
            @unlink($CRTDIR."/".$db->Record["id"].".key");
            @unlink($CRTDIR."/".$db->Record["id"].".chain");
            $d=opendir($CRTDIR);
            $empty=true;
            while (($c=readdir($d))!==false) {
                if (is_file($CRTDIR."/".$c)) {
                    $empty=false;
                    break;
                }
            }
            closedir($d);
            if ($empty) {
                rmdir($CRTDIR);
            }
        }
    }
    
    
    // ----------------------------------------------------------------- 
    /** Return all the SSL certificates for an account (or the searched one)
     * @param $filter an integer telling which certificate we want to see (see FILTER_* constants above)
     * the default is showing all certificate, but only Pending and OK certificates, not expired or shared one 
     * when there is more than 10.
     * @return array all the ssl certificate this user can use 
     * (each array is the content of the certificates table)
     */
    function get_list(&$filter = null) {
        global $db, $msg, $cuid;
        $msg->log("ssl", "get_list");
        $this->expire_certificates();
        $r = array();
        // If we have no filter, we filter by default on pending and ok certificates if there is more than 10 of them for the same user.
        if (is_null($filter)) {
            $filter = (self::FILTER_PENDING | self::FILTER_OK);
        }
        // filter the filter values :) 
        $filter = ($filter & (self::FILTER_PENDING | self::FILTER_OK | self::FILTER_EXPIRED));
        // Here filter can't be null (and will be returned to the caller !)
        $sql = "";
        $sql = " uid='$cuid' ";
        $sql.=" AND status IN (-1";
        if ($filter & self::FILTER_PENDING) {
            $sql.="," . self::STATUS_PENDING;
        }
        if ($filter & self::FILTER_OK) {
            $sql.="," . self::STATUS_OK;
        }
        if ($filter & self::FILTER_EXPIRED) {
            $sql.="," . self::STATUS_EXPIRED;
        }
        $sql.=") ";
        $db->query("SELECT *, UNIX_TIMESTAMP(validstart) AS validstartts, UNIX_TIMESTAMP(validend) AS validendts FROM certificates WHERE $sql ORDER BY validstart DESC;");
        if ($db->num_rows()) {
            while ($db->next_record()) {
                $r[] = $db->Record;
            }
            return $r;
        } else {
            $msg->raise("INFO", "ssl", _("No SSL certificates available"));
            return array();
        }
    }

    

    // ----------------------------------------------------------------- 
    /** Generate a new CSR, a new Private RSA Key, for FQDN.
     * @param $fqdn string the FQDN of the domain name for which we want a CSR.
     * a wildcard certificate must start by *.
     * @param $provider string a provider if necessary
     * @return integer the Certificate ID created in the MySQL database
     * or false if an error occurred
     */
    function new_csr($fqdn, $provider="manual") {
        global $db, $msg, $cuid;
        $msg->log("ssl", "new_csr");
        if (substr($fqdn, 0, 2) == "*.") {
            $f = substr($fqdn, 2);
        } else {
            $f = $fqdn;
        }
        if (checkfqdn($f)) {
            $msg->raise("ERROR","ssl", _("Bad FQDN domain name"));
            return false;
        }
        putenv("OPENSSL_CONF=/etc/alternc/openssl.cnf");
        $pkey = openssl_pkey_new();
        if (!$pkey) {
            $msg->raise("ERROR","ssl", _("Can't generate a private key (1)"));
            return false;
        }
        $privKey = "";
        if (!openssl_pkey_export($pkey, $privKey)) {
            $msg->raise("ERROR","ssl", _("Can't generate a private key (2)"));
            return false;
        }
        $dn = array("commonName" => $fqdn);
        // override the (not taken from openssl.cnf) digest to use SHA-2 / SHA256 and not SHA-1 or MD5 :
        $config = array("digest_alg" => "sha256");
        $csr = openssl_csr_new($dn, $pkey, $config);
        $csrout = "";
        openssl_csr_export($csr, $csrout);
        $db->query("INSERT INTO certificates SET uid=?, status=?, fqdn=?, altnames='', validstart=NOW(), sslcsr=?, sslkey=?, provider=?;",array($cuid, self::STATUS_PENDING, $fqdn, $csrout, $privKey, $provider));
        if (!($id = $db->lastid())) {
            $msg->raise("ERROR","ssl", _("Can't generate a CSR"));
            return false;
        }
        return $id;
    }

    
    // ----------------------------------------------------------------- 
    /** Return all informations of a given certificate for the current user.
     * @param $id integer the certificate by id
     * @param $anyuser integer if you want to search cert for any user, set this to true
     * @return array all the informations of the current certificate as a hash.
     */
    function get_certificate($id, $anyuser=false) {
        global $db, $msg, $cuid;
        $msg->log("ssl", "get_certificate");
        $id = intval($id);
        $sql="";
        if (!$anyuser) {
            $sql=" AND uid='".intval($cuid)."' ";
        }
        $db->query("SELECT *, UNIX_TIMESTAMP(validstart) AS validstartts, UNIX_TIMESTAMP(validend) AS validendts FROM certificates WHERE id=? $sql;",array($id));
        if (!$db->next_record()) {
            $msg->raise("ERROR","ssl", _("Can't find this Certificate"));
            return false;
        }
        return $db->Record;
    }

    
    // -----------------------------------------------------------------
    /** Return all the valid certificates that can be used for a specific FQDN
     * return the list of certificates by order of preference 
     * (the 2 last will be the default FQDN and the snakeoil if necessary)
     * keys: id, provider, crt, chain, key, validstart, validend
     */
    function get_valid_certs($fqdn, $provider="") {
        global $db, $msg, $cuid;
        $this->expire_certificates();

        $db->query("SELECT *, UNIX_TIMESTAMP(validstart) AS validstartts, UNIX_TIMESTAMP(validend) AS validendts FROM certificates WHERE status=".self::STATUS_OK." ORDER BY validstart DESC;");

        $good=array(); // list of good certificates
        $ugly=array(); // good but not with the right provider 
        $bad=array(); // our snakeoil 

        $wildcard="*".substr($fqdn,strpos($fqdn,"."));
        $defaultwild="*".substr($this->default_certificate_fqdn,strpos($this->default_certificate_fqdn,"."));

        while($db->next_record()) {
            $found=false;
            if ($db->Record["fqdn"]==$fqdn || $db->Record["fqdn"]==$wildcard) {
                $found=true;
                
            } else {
                $alts=explode("\n",$db->Record["altnames"]);
                foreach($alts as $alt) {
                    if ($alt==$fqdn || $alt==$wildcard) {
                        $found=true;
                        break;
                    }
                }
            }
            if ($found) {
                if ($provider=="" || $provider==$db->Record["provider"]) {
                    $good[]=$db->Record;
                } else {
                    $ugly[]=$db->Record;
                }
            }
            // search for the default one, the one used by the panel
            if (!count($bad)) {
                $found=false;
                if ($db->Record["fqdn"]==$this->default_certificate_fqdn || $db->Record["fqdn"]==$defaultwild) {
                    $found=true;
                } else {
                    $alts=explode("\n",$db->Record["altnames"]);
                    foreach($alts as $alt) {
                        if ($alt==$this->default_certificate_fqdn || $alt==$defaultwild) {
                            $found=true;
                            break;
                        }
                    }
                }
                if ($found) {
                    $bad=$db->Record;
                }
            }
        }
        // add the one with the bad provider
        if (count($ugly)) {
            $good=array_merge($good,$ugly);
        }
        // add the panel/default one
        if (count($bad)) {
            $good[]=$bad;
        }
        // Add the Snakeoil : #0
        $db->query("SELECT * FROM certificates WHERE id=0;");
        if ($db->next_record()) {
            $good[]=$db->Record;
        }
        return $good;
    }


    // ----------------------------------------------------------------- 
    /** Import an existing ssl Key, Certificate and (maybe) a Chained Cert
     * @param $key string the X.509 PEM-encoded RSA key
     * @param $crt string the X.509 PEM-encoded certificate, which *must* 
     * be the one signing the private RSA key in $key (we will check that anyway...)
     * @param $chain string the X.509 PEM-encoded list of SSL Certificate chain if intermediate authorities
     * TODO: check that the chain is effectively a chain to the CRT ...
     * @param $provider string the ssl cert provider  
     * @return integer the ID of the newly created certificate in the table 
     * or false if an error occurred
     */
    function import_cert($key, $crt, $chain = "", $provider = "") {
        global $cuid, $msg, $db;
        $msg->log("ssl", "import_cert");

        $result = $this->check_cert($crt, $chain, $key);
        if ($result === false) {
            $msg->raise("ERROR","ssl", $this->error);
            return false;
        }
        list($crt, $chain, $key, $crtdata) = $result;

        $validstart = $crtdata['validFrom_time_t'];
        $validend = $crtdata['validTo_time_t'];
        $fqdn = $crtdata["subject"]["CN"];
        $altnames = $this->parseAltNames($crtdata["extensions"]["subjectAltName"]);

        // Search for an existing cert:
        $db->query("SELECT id FROM certificates WHERE crt=?;",array($crt));
        if ($db->next_record()) {
            $msg->raise("ERROR","ssl", _("Certificate already exists in database"));
            return false;
        }

        // Everything is PERFECT and has been thoroughly checked, let's insert those in the DB !
        $db->query(
            "INSERT INTO certificates SET uid='?', status=?, shared=0, fqdn=?, altnames=?, validstart=FROM_UNIXTIME(?), validend=FROM_UNIXTIME(?), sslkey=?, sslcrt=?, sslchain=?, provider=?;",
            array($cuid,self::STATUS_OK,$fqdn,$altnames,intval($validstart),intval($validend),$key,$crt,$chain,$provider)
        );
        if (!($id = $db->lastid())) {
            $msg->raise("ERROR","ssl", _("Can't save the Key/Crt/Chain now. Please try later."));
            return false;
        }
        return $id;
    }

    
    // ----------------------------------------------------------------- 
    /** Import an ssl certificate into an existing certificate entry in the DB.
     * (finalize an enrollment process)
     * @param $certid integer the ID in the database of the SSL Certificate
     * @param $crt string the X.509  PEM-encoded certificate, which *must* 
     * be the one signing the private RSA key in certificate $certid
     * @param $chain string the X.509 PEM-encoded list of SSL Certificate chain if intermediate authorities
     * @return integer the ID of the updated certificate in the table
     * or false if an error occurred
     */
    function finalize($certid, $crt, $chain) {
        global $cuid, $msg, $db;
        $msg->log("ssl", "finalize");

        $certid = intval($certid);
        $result = $this->check_cert($crt, $chain, "", $certid);
        if ($result === false) {
            $msg->raise("ERROR","ssl", $this->error);
            return false;
        }
        list($crt, $chain, $key, $crtdata) = $result;

        $validstart = $crtdata['validFrom_time_t'];
        $validend = $crtdata['validTo_time_t'];
        $fqdn = $crtdata["subject"]["CN"];
        $altnames = $this->parseAltNames($crtdata["extensions"]["subjectAltName"]);

        // Everything is PERFECT and has been thoroughly checked, let's insert those in the DB !
        if (!$db->query(
            "INSERT INTO certificates (status,fqdn,altnames,validstart,validend,sslcrt,sslchain,sslcsr) 
SELECT ?,?,?, FROM_UNIXTIME(?), FROM_UNIXTIME(?), ?, ?, sslcsr FROM certificate WHERE id=?;",
            array(self::STATUS_OK, $fqdn, $altnames, $validstart, $validend, $crt, $chain, $certid)        
        )) {
            $msg->raise("ERROR","ssl", _("Can't save the Crt/Chain now. Please try later."));
            return false;
        }
        $newid=$db->lastid();
        $db->query("DELETE FROM certificates WHERE id=?;",array($certid));
        return $newid;
    }

    
    // ----------------------------------------------------------------- 
    /** Function called by a hook when an AlternC member is deleted.
     * @access private
     * TODO: delete unused ssl certificates ?? > do this in the crontab.
     */
    function alternc_del_member() {
        global $db, $msg, $cuid;
        $msg->log("ssl", "alternc_del_member");
        $db->query("UPDATE certificates SET uid=2000 WHERE uid=?;",array($cuid));
        return true;
    }

    
    //  ----------------------------------------------------------------- 
    /** Launched by hosting_functions.sh launched by update_domaines.sh
     * Action may be create/postinst/delete/enable/disable
     * Change the template for this domain name to have the proper CERTIFICATE
     * An algorithm determine the best possible certificate, which may be a BAD one 
     * (like a generic admin-shared or self-signed for localhost as a last chance)
     */
    public function updateDomain($action, $type, $fqdn, $mail = 0, $value = "") {
        global $db, $msg;
        $msg->log("ssl", "update_domain($action,$type,$fqdn)");

        // the domain type must be a "dns_only=false" one:
        if (!($domtype=$dom->domains_type_get($type)) || $domtype["dns_only"]==true) {
            return; // nothing to do : this domain type does not involve Vhosts
        }

        if ($action == "postinst") {
            $msg->log("ssl", "update_domain:CREATE($action,$type,$fqdn)");
            $offset = 0;
            $found = false;
            do { // try each subdomain (strtok-style) and search them in sub_domaines table:
                $db->query(
                    "SELECT * FROM sub_domaines WHERE sub=? AND domaine=? AND web_action NOT IN ('','OK') AND type=?",
                    array(substr($fqdn, 0, $offset), substr($fqdn, $offset + ($offset != 0)), $type)
                );
                if ($db->next_record()) {
                    $found = true;
                    break;
                }
                $offset = strpos($fqdn, ".", $offset+1);
                //No more dot, we prevent an infinite loop
                if (!$offset) {
                    break;
                }
            } while (true);
            if (!$found) {
                echo "FATAL: didn't found fqdn $fqdn in sub_domaines table !\n";
                return;
            }
            // found and $db point to it:
            $subdom = $db->Record;
            $TARGET_FILE = "/var/lib/alternc/apache-vhost/" . substr($subdom["compte"], -1) . "/" . $subdom["compte"] . "/" . $fqdn . ".conf";
            $cert = $this->searchBestCert($subdom,$fqdn);
            // $cert[crt/key/chain] are path to the proper files
            
            // edit apache conf file to set the certificate:
            $s = file_get_contents($TARGET_FILE);
            $s = str_replace("%%CRT%%", $cert["crt"], $s);
            $s = str_replace("%%KEY%%", $cert["key"], $s);
            if (isset($cert["sslchain"]) && $cert["sslchain"]) {
                $s = str_replace("%%CHAINLINE%%", "SSLCertificateChainFile " . $cert["chain"], $s);
            } else {
                $s = str_replace("%%CHAINLINE%%", "", $s);
            }
            file_put_contents($TARGET_FILE, $s);
            // Edit certif_hosts:
            $db->query("UPDATE sub_domaines SET certificate_id=? WHERE id=?;",array($cert["id"], $subdom["id"]));
        } // action==create

    }

    
    //  ---------------------------------------------------------------- 
    /** Search for the best certificate for a user and a fqdn 
     * Return a hash with crt, key and maybe chain.
     * they are the full path to the best certificate for this FQDN.
     * if necessary, use "default_certificate_fqdn" or a "snakeoil"
     * @param $subdom array the subdomain entry from sub_domaines table
     * @param $fqdn string the fully qualified domain name to search for
     * @return array an has with crt key chain
     */
    public function searchBestCert($subdom,$fqdn) {
        global $db;

        // get the first good certificate: 
        list($cert) = $this->get_valid_certs($fqdn, $subdom["provider"]);

        // we split the certificates by 1000
        $CRTDIR = self::KEY_REPOSITORY . "/" . floor($cert["id"]/1000);
        @mkdir($CRTDIR);
        if (
            !file_exists($CRTDIR . "/" . $cert["id"].".crt") ||
            !file_exists($CRTDIR . "/" . $cert["id"].".key")) {
            // write the files (first time we use a certificate)
            file_put_contents($CRTDIR . "/" . $cert["id"].".crt", $cert["sslcrt"]);
            file_put_contents($CRTDIR . "/" . $cert["id"].".key", $cert["sslkey"]);
            if (isset($cert["sslchain"]) && $cert["sslchain"]) {
                file_put_contents($CRTDIR . "/" . $cert["id"] . ".chain", $cert["sslchain"]);
            }
        }
        // we have the files, let's fill the output array :
        $output=array(
            "crt" => $CRTDIR . "/" . $cert["id"].".crt",
            "key" => $CRTDIR . "/" . $cert["id"].".key",
        );
        if (file_exists($CRTDIR . "/" . $cert["id"].".chain")) {
            $output["chain"] = $CRTDIR . "/" . $cert["id"].".chain";
        }
        return $output;
    }

    
    // ----------------------------------------------------------------- 
    /** Export every information for an AlternC's account
     * @access private
     * EXPERIMENTAL 'sid' function ;) 
     */
    function alternc_export_conf() {
        global $db, $msg, $cuid;
        $msg->log("ssl", "export");
        $str = "  <ssl>";
        $db->query("SELECT COUNT(*) AS cnt FROM certificates WHERE uid='$cuid' AND status!=" . self::STATUS_EXPIRED);
        while ($db->next_record()) {
            $str.="   <id>" . ($db->Record["id"]) . "</id>\n";
            $str.="   <csr>" . ($db->Record["sslcsr"]) . "</key>\n";
            $str.="   <key>" . ($db->Record["sslkey"]) . "<key>\n";
            $str.="   <crt>" . ($db->Record["sslcrt"]) . "</crt>\n";
            $str.="   <chain>" . ($db->Record["sslchain"]) . "<chain>\n";
        }
        $str.=" </ssl>\n";
        return $str;
    }

    
    // ----------------------------------------------------------------- 
    /** Returns the list of alternate names of an X.509 SSL Certificate 
     * from the attribute list.
     * @param $str string the $crtdata["extensions"]["subjectAltName"] from openssl
     * @return array an array of FQDNs
     */
    function parseAltNames($str) {
        $mat = array();
        if (preg_match_all("#DNS:([^,]*)#", $str, $mat, PREG_PATTERN_ORDER)) {
            return implode("\n", $mat[1]);
        } else {
            return "";
        }
    }

    // ----------------------------------------------------------------- 
    /** Check that a crt is a proper certificate
     * @param $crt string an SSL Certificate
     * @param $chain string is a list of certificates
     * @param $key string  is a rsa key associated with certificate 
     * @param $certid if no key is specified, use it from this certificate ID in the table
     * @return array the crt, chain, key, crtdata(array) after a proper reformatting  
     * or false if an error occurred (in that case $this->error is filled)
     */
    function check_cert($crt, $chain, $key = "", $certid = null) {
        global $db;
        // Check that the key crt and chain are really SSL certificates and keys
        $crt = trim(str_replace("\r\n", "\n", $crt)) . "\n";
        $key = trim(str_replace("\r\n", "\n", $key)) . "\n";
        $chain = trim(str_replace("\r\n", "\n", $chain)) . "\n";

        $this->error = "";
        if (trim($key) == "" && !is_null($certid)) {
            // find it in the DB : 
            $db->query("SELECT sslkey FROM certificates WHERE id=?;",array(intval($certid)));
            if (!$db->next_record()) {
                $this->error.=_("Can't find the private key in the certificate table, please check your form.");
                return false;
            }
            $key = $db->f("sslkey");
            $key = trim(str_replace("\r\n", "\n", $key)) . "\n";
        }

        if (substr($crt, 0, 28) != "-----BEGIN CERTIFICATE-----\n" ||
                substr($crt, -26, 26) != "-----END CERTIFICATE-----\n") {
            $this->error.=_("The certificate must begin by BEGIN CERTIFICATE and end by END CERTIFICATE lines. Please check you pasted it in PEM form.") . "<br>\n";
        }
        if (trim($chain) &&
                (substr($chain, 0, 28) != "-----BEGIN CERTIFICATE-----\n" ||
                substr($chain, -26, 26) != "-----END CERTIFICATE-----\n")) {
            $this->error.=_("The chained certificate must begin by BEGIN CERTIFICATE and end by END CERTIFICATE lines. Please check you pasted it in PEM form.") . "<br>\n";
        }
        if ((substr($key, 0, 32) != "-----BEGIN RSA PRIVATE KEY-----\n" ||
                substr($key, -30, 30) != "-----END RSA PRIVATE KEY-----\n") &&
                (substr($key, 0, 28) != "-----BEGIN PRIVATE KEY-----\n" ||
                substr($key, -26, 26) != "-----END PRIVATE KEY-----\n")) {
            $this->error.=_("The private key must begin by BEGIN (RSA )PRIVATE KEY and end by END (RSA )PRIVATE KEY lines. Please check you pasted it in PEM form.") . "<br>\n";
        }
        if ($this->error) {
            return false;
        }

        // We split the chained certificates in individuals certificates : 
        $chains = array();
        $status = 0;
        $new = "";
        $lines = explode("\n", $chain);
        foreach ($lines as $line) {
            if ($line == "-----BEGIN CERTIFICATE-----" && $status == 0) {
                $status = 1;
                $new = $line . "\n";
                continue;
            }
            if ($line == "-----END CERTIFICATE-----" && $status == 1) {
                $status = 0;
                $new.=$line . "\n";
                $chains[] = $new;
                $new = "";
                continue;
            }
            if ($status == 1) {
                $new.=$line . "\n";
            }
        }
        // here chains contains all the ssl certificates in the chained certs.
        // Now we check those using Openssl functions (real check :) ) 
        $rchains = array();
        $i = 0;
        foreach ($chains as $tmpcert) {
            $i++;
            $tmpr = openssl_x509_read($tmpcert);
            if ($tmpr === false) {
                $this->error.=sprintf(_("The %d-th certificate in the chain is invalid"), $i) . "<br>\n";
            } else {
                $rchains[] = $tmpr;
            }
        }
        $rcrt = openssl_x509_read($crt);
        $crtdata = openssl_x509_parse($crt);
        if ($rcrt === false || $crtdata === false) {
            $this->error.=_("The certificate is invalid.") . "<br>\n";
        }

        $rkey = openssl_pkey_get_private($key);
        if ($rkey === false) {
            $this->error.=_("The private key is invalid.") . "<br>\n";
        }
        if (!$this->error) {
            // check that the private key and the certificates are matching :
            if (!openssl_x509_check_private_key($rcrt, $rkey)) {
                $this->error.=_("The private key is not the one signed inside the certificate.") . "<br>\n";
            }
        }
        if (!$this->error) {
            // Everything is fine, let's recreate crt, chain, key from our internal OpenSSL structures:
            if (!openssl_x509_export($rcrt, $crt)) {
                $this->error.=_("Can't export your certificate as a string, please check its syntax.") . "<br>\n";
            }
            $chain = "";
            foreach ($rchains as $r) {
                if (!openssl_x509_export($r, $tmp)) {
                    $this->error.=_("Can't export one of your chained certificates as a string, please check its syntax.") . "<br>\n";
                } else {
                    $chain.=$tmp;
                }
            }
            if (!openssl_pkey_export($rkey, $key)) {
                $this->error.=_("Can't export your private key as a string, please check its syntax.") . "<br>\n";
            }
        }
        return array($crt, $chain, $key, $crtdata);
    }

}

/* Class m_ssl */
