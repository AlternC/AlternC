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
    const FILTER_SHARED = 8;
    const SSL_INCRON_FILE = "/var/run/alternc-ssl/generate_certif_alias";

    var $myDomainesTypes = array("vhost-ssl", "vhost-mixssl", "panel-ssl", "roundcube-ssl", "squirrelmail-ssl", "php52-ssl", "php52-mixssl", "url-ssl");

    const KEY_REPOSITORY = "/var/lib/alternc/ssl/private";

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
     * Hook to add the "ssl certificate" menu in the Panel
     */
    function hook_menu() {
        global $quota, $db, $cuid;
        $q = $quota->getquota("ssl");
        $obj = null;
        if ($q['t'] > 0) {
            $obj = array(
                'title' => _("SSL Certificates"),
                'ico' => 'images/ssl.png',
                'link' => 'toggle',
                'pos' => 130,
                'links' => array(),
            );

            if ($quota->cancreate("ssl")) {
                $obj['links'][] = array(
                    'ico' => 'images/new.png',
                    'txt' => _("New SSL certificate"),
                    'url' => "ssl_new.php",
                    'class' => '',
                );
            }

            // or admin shared >0 !
            $db->query("SELECT COUNT(*) AS cnt FROM certificates WHERE uid='$cuid' OR shared=1");
            $used = $q['u'];
            if ($db->next_record()) {
                $used = $db->f("cnt");
            }
            if ($used > 0) { // if there are some SSL certificates
                $obj['links'][] = array(
                    'txt' => _("List SSL Certificates"),
                    'url' => "ssl_list.php"
                );
            }
        }
        return $obj;
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
        // Expire expired certificates:
        $db->query("UPDATE certificates SET status=".self::STATUS_EXPIRED." WHERE status=".self::STATUS_OK." AND validend<NOW();");
        $r = array();
        // If we have no filter, we filter by default on pending and ok certificates if there is more than 10 of them for the same user.
        if (is_null($filter)) {
            $db->query("SELECT count(*) AS cnt FROM certificates WHERE uid='$cuid' OR shared=1;");
            $db->next_record();
            if ($db->f("cnt") > 10) {
                $filter = (self::FILTER_PENDING | self::FILTER_OK);
            } else {
                $filter = (self::FILTER_PENDING | self::FILTER_OK | self::FILTER_EXPIRED | self::FILTER_SHARED);
            }
        }
        // filter the filter values :) 
        $filter = ($filter & (self::FILTER_PENDING | self::FILTER_OK | self::FILTER_EXPIRED | self::FILTER_SHARED));
        // Here filter can't be null (and will be returned to the caller !)
        $sql = "";
        if ($filter & self::FILTER_SHARED) {
            $sql = " (uid='$cuid' OR shared=1) ";
        } else {
            $sql = " uid='$cuid' ";
        }
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
        $db->query("SELECT *, UNIX_TIMESTAMP(validstart) AS validstartts, UNIX_TIMESTAMP(validend) AS validendts FROM certificates WHERE $sql ORDER BY shared, fqdn;");
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
    /** Return all the Vhosts of this user using SSL certificates 
     * @return array all the ssl certificate  and hosts of this user 
     */
    function get_vhosts() {
        global $db, $msg, $cuid;
        $msg->log("ssl", "get_vhosts");
        $r=array();
        $db->query("SELECT ch.*, UNIX_TIMESTAMP(c.validstart) AS validstartts, UNIX_TIMESTAMP(c.validend) AS validendts, sd.domaine, sd.sub "
                . "FROM certif_hosts ch LEFT JOIN certificates c ON ch.certif=c.id "
                . ", sub_domaines sd WHERE sd.id=ch.sub AND ch.uid=$cuid "
                . "ORDER BY sd.domaine, sd.sub;");
        if ($db->num_rows()) {
            while ($db->next_record()) {
                $r[] = $db->Record;
            }
            return $r;
        } else {
            $msg->raise("INFO","ssl", _("You currently have no hosting using SSL certificate"));
            return array();
        }
    }

    // ----------------------------------------------------------------- 
    /** Generate a new CSR, a new Private RSA Key, for FQDN.
     * @param $fqdn string the FQDN of the domain name for which we want a CSR.
     * a wildcard certificate must start by *.
     * @return integer the Certificate ID created in the MySQL database
     * or false if an error occurred
     */
    function new_csr($fqdn) {
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
        $db->query("INSERT INTO certificates SET uid='$cuid', status=" . self::STATUS_PENDING . ", shared=0, fqdn='" . addslashes($fqdn) . "', altnames='', validstart=NOW(), sslcsr='" . addslashes($csrout) . "', sslkey='" . addslashes($privKey) . "';");
        if (!($id = $db->lastid())) {
            $msg->raise("ERROR","ssl", _("Can't generate a CSR"));
            return false;
        }
        return $id;
    }

    // ----------------------------------------------------------------- 
    /** Return all informations of a given certificate for the current user.
     * @return array all the informations of the current certificate as a hash.
     */
    function get_certificate($id) {
        global $db, $msg, $cuid;
        $msg->log("ssl", "get_certificate");
        $id = intval($id);
        $db->query("SELECT *, UNIX_TIMESTAMP(validstart) AS validstartts, UNIX_TIMESTAMP(validend) AS validendts FROM certificates WHERE (uid='$cuid' OR (shared=1 AND status=" . self::STATUS_OK . ") ) AND id='$id';");
        if (!$db->next_record()) {
            $msg->raise("ERROR","ssl", _("Can't find this Certificate"));
            return false;
        }
        return $db->Record;
    }

    // ----------------------------------------------------------------- 
    /** Delete a Certificate for the current user.
     * @return boolean TRUE if the certificate has been deleted successfully.
     */
    function del_certificate($id) {
        global $db, $msg, $cuid;
        $msg->log("ssl", "del_certificate");
        $id = intval($id);
        $db->query("SELECT * FROM certificates WHERE uid='$cuid' AND id='$id';");
        if (!$db->next_record()) {
            $msg->raise("ERROR","ssl", _("Can't find this Certificate"));
            return false;
        }
        $fqdn = $db->Record["fqdn"];
        $altnames = $db->Record["altnames"];
        $db->query("DELETE FROM certificates  WHERE uid='$cuid' AND id='$id';");
        // Update any existing VHOST using this cert/key
        $this->updateTrigger($fqdn, $altnames);
        return true;
    }

    // ----------------------------------------------------------------- 
    /** Share (or unshare) an ssl certificate
     * @param $id integer the id of the certificate in the table.
     * @param $action integer share (1) or unshare (0) this certificate
     * @return boolean
     */
    function share($id, $action = 1) {
        global $db, $msg, $cuid;
        $msg->log("ssl", "share");
        $id = intval($id);
        $db->query("SELECT * FROM certificates WHERE uid='$cuid' AND status=" . self::STATUS_OK . " AND id='$id';");
        if (!$db->next_record()) {
            $msg->raise("ERROR","ssl", _("Can't find this Certificate"));
            return false;
        }
        if ($action) {
            $action = 1;
            $this->updateTrigger($db->Record["fqdn"], $db->Record["altnames"]);
        } else {
            $action = 0;
        }
        $db->query("UPDATE certificates SET shared=$action WHERE id='$id';");
        return true;
    }

    // -----------------------------------------------------------------
    /** Return all the valid certificates that can be used for a specific FQDN
     * return the list of certificates by order of preference (2 lasts bein the default FQDN and the snakeoil if necessary)
     * keys: id, provider, crt, chain, key, validstart, validend
     */
    function get_valid_certs($fqdn, $provider="") {
        global $db, $msg, $cuid;
        $db->query("SELECT * FROM certificates WHERE status=".self::STATUS_OK." ORDER BY validstart DESC;");
        $good=array(); // list of good certificates
        $bof=array(); // good but not with the right provider 
        $bad=array(); 
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
                    $bof[]=$db->Record;
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
            // TODO : manages BAD (default) and UGLY (snakeoil)
        }
        // add the one with the bad provider
        if (count($bof)) {
            $good=array_merge($good,$bof);
        }
        if (count($bad)) {
            $good[]=$bad;
        }
        // $ugly Add the Snakeoil : #0
        $db->query("SELECT * FROM certificates WHERE id=0;");
        if ($db->next_record()) {
            $good[]=$db->Record;
        }
        return $good;
    }


    // ----------------------------------------------------------------- 
    /** Return all the subdomains that can be ssl-enabled for the current account.
     * @return array of strings : all the subdomains. 
     * Excludes the one for which a cert is already available
     */
    function get_new_advice() {
        global $db, $msg, $cuid;
        $msg->log("ssl", "get_new_advice");
        $r = array();
        // my certificates, either OK or PENDING (not expired) or the SHARED one (only OK then)
        $db->query("SELECT fqdn FROM certificates WHERE
      (uid='$cuid' AND status IN (" . self::STATUS_PENDING . "," . self::STATUS_OK . ") ) 
   OR (shared=1 AND status=" . self::STATUS_OK . ") 
   ORDER BY shared, fqdn;");
        $r = array();
        while ($db->next_record()) {
            $r[] = $db->f("fqdn");
        }
        // Now we get all our subdomains for certain domaines_types
        $db->query("SELECT sub,domaine FROM sub_domaines WHERE compte='$cuid' AND type IN ('vhost', 'url', 'roundcube', 'squirrelmail', 'panel', 'php52');");
        $advice = array();
        while ($db->next_record()) {
            $me = $db->f("sub");
            if ($me) {
                $me.=".";
            }
            $me.=$db->f("domaine");
            if (!in_array($me, $r) && !in_array($me, $advice)) {
                $advice[] = $me;
            }
            if (!in_array("*." . $db->f("domaine"), $r) && !in_array("*." . $db->f("domaine"), $advice)) {
                $advice[] = "*." . $db->f("domaine");
            }
        }
        sort($advice);
        return($advice);
    }

    // ----------------------------------------------------------------- 
    /** Import an existing ssl Key, Certificate and (maybe) a Chained Cert
     * @param $key string the X.509 PEM-encoded RSA key
     * @param $crt string the X.509  PEM-encoded certificate, which *must* 
     * be the one signinf the private RSA key in $key
     * @param $chain string the X.509 PEM-encoded list of SSL Certificate chain if intermediate authorities
     * @return integer the ID of the newly created certificate in the table
     * @return string the ssl cert provider 
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
        $sql = "INSERT INTO certificates SET uid='?', status=?, shared=0, fqdn=?, altnames=?, validstart=FROM_UNIXTIME(?), validend=FROM_UNIXTIME(?), sslkey=?, sslcrt=?, sslchain=?, provider=?;";
        $db->query($sql,array($cuid,self::STATUS_OK,$fqdn,$altnames,intval($validstart),intval($validend),$key,$crt,$chain,$provider));
        if (!($id = $db->lastid())) {
            $msg->raise("ERROR","ssl", _("Can't save the Key/Crt/Chain now. Please try later."));
            return false;
        }
        $this->updateTrigger($fqdn, $altnames);
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
        $sql = "UPDATE certificates SET status=" . self::STATUS_OK . ", shared=0, fqdn='" . addslashes($fqdn) . "', altnames='" . addslashes($altnames) . "', validstart=FROM_UNIXTIME(" . intval($validstart) . "), validend=FROM_UNIXTIME(" . intval($validend) . "), sslcrt='" . addslashes($crt) . "', sslchain='" . addslashes($chain) . "' WHERE id='$certid' ;";
        if (!$db->query($sql)) {
            $msg->raise("ERROR","ssl", _("Can't save the Crt/Chain now. Please try later."));
            return false;
        }
        $this->updateTrigger($fqdn, $altnames);
        return $certid;
    }

    // ----------------------------------------------------------------- 
    /** Function called by a hook when an AlternC member is deleted.
     * @access private
     * TODO: delete unused ssl certificates ?? > do this in the crontab.
     */
    function alternc_del_member() {
        global $db, $msg, $cuid;
        $msg->log("ssl", "alternc_del_member");
        $db->query("UPDATE certificates SET ssl_action='DELETE' WHERE uid='$cuid'");
        return true;
    }

    // ----------------------------------------------------------------- 
    /** Hook which returns the used quota for the $name service for the current user.
     * @param $name string name of the quota 
     * @return integer the number of service used or false if an error occured
     * @access private
     */
    function hook_quota_get() {
        global $db, $msg, $cuid;
        $msg->log("ssl", "getquota");
        $q = Array("name" => "ssl", "description" => _("SSL Certificates"), "used" => 0);
        $db->query("SELECT COUNT(*) AS cnt FROM certificates WHERE uid='$cuid' AND status!=" . self::STATUS_EXPIRED);
        if ($db->next_record()) {
            $q['used'] = $db->f("cnt");
        }
        return $q;
    }

    //  ----------------------------------------------------------------- 
    /** Launched by functions in this class
     * when a certificate is validated, expired or shared.
     * so that existing vhost using expired or self-signed certificates
     * may have the chance to use a proper one automagically
     * @param string $fqdn the FQDN of the certificate 
     * @param string $altnames any alternative names this certificate may have.
     */
    public function updateTrigger($fqdn, $altnames = "") {
        global $db;
        $fqdns = array($fqdn);
        $an = explode("\n", $altnames);
        foreach ($an as $a)
            if (trim($a))
                $fqdns[] = trim($a);
        $db->query("UPDATE sub_domaines SET web_action='UPDATE' WHERE "
                . "if(LENGTH(sub)>0,CONCAT(sub,'.',domaine),domaine) IN ('" . implode("','", $fqdns) . "') "
                . "AND type LIKE '%ssl';");
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
        if (!in_array($type, $this->myDomainesTypes)) {
            return; // nothing to do : the type is not our to start with ;) 
        }
        if ($action == "postinst") {
            $msg->log("ssl", "update_domain:CREATE($action,$type,$fqdn)");
            $offset = 0;
            $found = false;
            do { // try each subdomain (strtok-style) and search them in sub_domaines table:
                $db->query("SELECT * FROM sub_domaines WHERE "
                        . "sub='" . substr($fqdn, 0, $offset) . "' AND domaine='" . substr($fqdn, $offset + ($offset != 0)) . "' "
                        . "AND web_action NOT IN ('','OK') AND type='" . $type . "';");
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
            $cert = $this->searchBestCert($subdom["compte"], $fqdn);
            // DEBUG             echo "Return from searchBestCert(" . $subdom["compte"] . "," . $fqdn . ") is ";            print_r($cert);
            // Save crt/key/chain into KEY_REPOSITORY
            $CRTDIR = self::KEY_REPOSITORY . "/" . $subdom["compte"];
            @mkdir($CRTDIR);
            // Don't *overwrite* existing self-signed certificates in KEY_REPOSITORY
            if (isset($cert["selfsigned"]) &&
                    file_exists($CRTDIR . "/" . $fqdn . ".crt") &&
                    file_exists($CRTDIR . "/" . $fqdn . ".key")) {
                echo "Self-Signed certificate reused...\n";
            } else {
                file_put_contents($CRTDIR . "/" . $fqdn . ".crt", $cert["sslcrt"]);
                file_put_contents($CRTDIR . "/" . $fqdn . ".key", $cert["sslkey"]);
                if (isset($cert["sslchain"]) && $cert["sslchain"]) {
                    file_put_contents($CRTDIR . "/" . $fqdn . ".chain", $cert["sslchain"]);
                }
            }
            // edit apache conf file to set the certificate:
            $s = file_get_contents($TARGET_FILE);
            $s = str_replace("%%CRT%%", $CRTDIR . "/" . $fqdn . ".crt", $s);
            $s = str_replace("%%KEY%%", $CRTDIR . "/" . $fqdn . ".key", $s);
            if (isset($cert["sslchain"]) && $cert["sslchain"]) {
                $s = str_replace("%%CHAINLINE%%", "SSLCertificateChainFile " . $CRTDIR . "/" . $fqdn . ".chain", $s);
            } else {
                $s = str_replace("%%CHAINLINE%%", "", $s);
            }
            file_put_contents($TARGET_FILE, $s);
            // Edit certif_hosts:
            $db->query("DELETE FROM certif_hosts WHERE sub=" . $subdom["id"] . ";");
            $db->query("INSERT INTO certif_hosts SET "
                    . "sub=" . intval($subdom["id"]) . ", "
                    . "certif=" . intval($cert["id"]) . ", "
                    . "uid=" . intval($subdom["compte"]) . ";");
        } // action==create
        if ($action == "delete") {
            $msg->log("ssl", "update_domain:DELETE($action,$type,$fqdn)");
            $offset = 0;
            $found = false;
            do { // try each subdomain (strtok-style) and search them in sub_domaines table:
                $db->query("SELECT * FROM sub_domaines WHERE "
                        . "sub='" . substr($fqdn, 0, $offset) . "' AND domaine='" . substr($fqdn, $offset + ($offset != 0)) . "' "
                        . "AND web_action NOT IN ('','OK') AND type='" . $type . "';");
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
            $db->query("DELETE FROM certif_hosts WHERE sub=" . $subdom["id"] . ";");
        }
    }

    //  ---------------------------------------------------------------- 
    /** Search for the best certificate for a user and a fqdn 
     * Return a hash with sslcrt, sslkey and maybe sslchain.
     * return ANYWAY : if necessary, return a newly created (and stored in KEY_REPOSITORY localhost self-signed certificate...
     */
    public function searchBestCert($uid, $fqdn) {
        global $db;
        $uid = intval($uid);
        // 1st search for a valid certificate in my account or shared by the admin:
        // the ORDER BY make it so that we try VALID then EXPIRED one (sad)
        $wildcard = "*." . substr($fqdn, strpos($fqdn, ".") + 1);
        $db->query("SELECT * FROM certificates WHERE (status=".self::STATUS_OK." OR status=".self::STATUS_EXPIRED.") "
                . "AND (uid=" . $uid . " OR shared=1) "
                . "AND (fqdn='" . $fqdn . "' OR fqdn='" . $wildcard . "' OR altnames LIKE '%" . $fqdn . "%') "
                . "ORDER BY (validstart<=NOW() AND validend>=NOW()) DESC, validstart DESC ");
        while ($db->next_record()) {
	  // name
            if ($db->Record["fqdn"] == $fqdn) {
                return $db->Record;
            }
	    // or alternative names 
            $altnames = explode("\n", $db->Record["altnames"]);
            foreach ($altnames as $altname) {
                if (trim($altname) == $fqdn) {
                    return $db->Record;
                }
            }
	    // or wildcard
	    if ($db->Record["fqdn"] == $wildcard) {
	      return $db->Record;
	    }
        }
        // not found, we generate a one-time self-signed certificate for this host.
        $crt = $this->selfSigned($fqdn);
        $crt["uid"] = $uid;
        return $crt;
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
    /** Add (immediately) a global alias to the HTTP 
     * certif_alias table and add it to apache configuration
     * by launching a incron action. 
     * name is the name of the alias, starting by /
     * content is the content of the filename stored at this location
     * If an alias with the same name already exists, return false.
     * if the alias has been properly defined, return true.
     * @return boolean
     */
    function alias_add($name, $content) {
        global $msg, $cuid, $db;
        $db->query("SELECT name FROM certif_alias WHERE name='" . addslashes($name) . "';");
        if ($db->next_record()) {
            $msg->raise("ERROR","ssl", _("Alias already exists"));
            return false;
        }
        $db->query("INSERT INTO certif_alias SET name='" . addslashes($name) . "', content='" . addslashes($content) . "', uid=" . intval($cuid) . ";");
        touch(self::SSL_INCRON_FILE);
        return true;
    }

    // ----------------------------------------------------------------- 
    /** Removes (immediately) a global alias to the HTTP 
     * certif_alias table and add it to apache configuration
     * by launching a incron action. 
     * name is the name of the alias, starting by /
     * @return boolean
     */
    function alias_del($name) {
        global $msg, $cuid, $db;
        $db->query("SELECT name FROM certif_alias WHERE name='" . addslashes($name) . "' AND uid=" . intval($cuid) . ";");
        if (!$db->next_record()) {
            $msg->raise("ERROR","ssl", _("Alias not found"));
            return false;
        }
        $db->query("DELETE FROM certif_alias WHERE name='" . addslashes($name) . "' AND uid=" . intval($cuid) . ";");
        touch(self::SSL_INCRON_FILE);
        return true;
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
            $db->query("SELECT sslkey FROM certificates WHERE id=" . intval($certid) . ";");
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

    // -----------------------------------------------------------------
    /** Generate a self-signed certificate
     * 
     * @param string $fqdn the fully qualified domain name to set as commonName for the certificate
     * @return hash an array similar to a certificate DB row containing everything (sslcrt, sslcsr, sslkey, sslchain)
     */
    private function selfSigned($fqdn) {
        global $msg;
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
        $crt = openssl_csr_sign($csr, null, $pkey, 3650, $config);
        $crtout = "";
        openssl_x509_export($crt, $crtout);
        return array("id" => 0, "status" => 1, "shared" => 0, "fqdn" => $fqdn, "altnames" => "",
            "validstart" => date("Y-m-d H:i:s"), "validend" => date("Y-m-d H:i:s", time() + 86400 * 10 * 365.249),
            "sslcsr" => $csrout, "sslcrt" => $crtout, "sslkey" => $privKey, "sslchain" => "",
            "selfsigned" => true,
        );
    }


    function dummy() {
      _("Locally hosted forcing HTTPS");
      _("Locally hosted HTTP and HTTPS");
      _("HTTPS AlternC panel access");
      _("HTTPS Roundcube Webmail");
      _("HTTPS Squirrelmail Webmail");
      _("php52 forcing HTTPS");
      _("php52 HTTP and HTTPS");
    }

}

/* Class m_ssl */
