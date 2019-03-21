<?php

/**
 * Subdomain Api of AlternC, used by alternc-api package
 */
class Alternc_Api_Object_Subdomain extends Alternc_Api_Legacyobject {

    protected $dom; // m_dom instance

    function __construct($service) {
        global $dom;
        parent::__construct($service);
        $this->dom = $dom;
    }

    /** API Method from legacy class method dom->get_sub_domain_all($dom)
     * @param $options a hash with parameters transmitted to legacy call
     * must be the subdomain id ID
     * @return Alternc_Api_Response whose content is the list of subdomains on this server 
     */
    function get($options) {
        global $cuid;
        if ($this->isAdmin) {
            if (isset($options["uid"])) {
                $cuid = intval($options["uid"]);
            }
        }
        $mandatory = array("id");
        $missing = "";
        foreach ($mandatory as $key) {
            if (!isset($options[$key])) {
                $missing.=$key . " ";
            }
        }
        if ($missing) {
            return new Alternc_Api_Response(array("code" => self::ERR_INVALID_ARGUMENT, "message" => "Missing or invalid argument: " . $missing));
        }
        $this->dom->lock();
        $did = $this->dom->get_sub_domain_all($options["id"]);
        $this->dom->unlock();
        if (!$did) {
            return $this->alterncLegacyErrorManager();
        } else {
            return new Alternc_Api_Response(array("content" => $did));
        }
    }

    /** API Method from legacy class method dom->set_sub_domain($dom)
     * @param $options a hash with parameters transmitted to legacy call
     * must be $dom, $sub, $type, $dest and could be $sub_domain_id 
     * and $urgent
     * @return Alternc_Api_Response whose content is true or false 
     * if the change has been made
     */
    function set($options) {
        global $cuid,$L_INOTIFY_UPDATE_DOMAIN;
        if ($this->isAdmin) {
            if (isset($options["uid"])) {
                $cuid = intval($options["uid"]);
            }
        }
        $mandatory = array("dom", "sub", "type", "dest");
        $defaults = array("sub_domain_id" => null, "urgent" =>false);
        $missing = "";
        foreach ($mandatory as $key) {
            if (!isset($options[$key])) {
                $missing.=$key . " ";
            }
        }
        foreach ($defaults as $key => $value) {
            if (!isset($options[$key])) {
                $options[$key] = $value;
            }
        }
        if ($missing) {
            return new Alternc_Api_Response(array("code" => self::ERR_INVALID_ARGUMENT, "message" => "Missing or invalid argument: " . $missing));
        }
        $this->dom->lock();
        $did = $this->dom->set_sub_domain($options["dom"], $options["sub"], $options["type"], $options["dest"], $options["sub_domain_id"]);
        $this->dom->unlock();
        if (!$did) {
            return $this->alterncLegacyErrorManager();
        } else {
            if ($options["urgent"])
                touch($L_INOTIFY_UPDATE_DOMAIN);            
            return new Alternc_Api_Response(array("content" => $did));
        }
    }

    
    /** API Method from legacy class method dom->del_by_id($domid)
     * @param $options a hash with parameters transmitted to legacy call
     * must be $id (may be $urgent)
     * @return Alternc_Api_Response whose content is true or false 
     * if the change has been made
     */
    function del_by_id($options) {
        global $cuid,$L_INOTIFY_UPDATE_DOMAIN;
        if ($this->isAdmin) {
            if (isset($options["uid"])) {
                $cuid = intval($options["uid"]);
            }
        }
        $mandatory = array("id");
        $defaults = array("urgent" =>false);
        $missing = "";
        foreach ($mandatory as $key) {
            if (!isset($options[$key])) {
                $missing.=$key . " ";
            }
        }
        foreach ($defaults as $key => $value) {
            if (!isset($options[$key])) {
                $options[$key] = $value;
            }
        }
        if ($missing) {
            return new Alternc_Api_Response(array("code" => self::ERR_INVALID_ARGUMENT, "message" => "Missing or invalid argument: " . $missing));
        }
        $this->dom->lock();
        $did = $this->dom->del_sub_domain($options["id"]);
        $this->dom->unlock();
        if (!$did) {
            return $this->alterncLegacyErrorManager();
        } else {
            if ($options["urgent"])
                touch($L_INOTIFY_UPDATE_DOMAIN);            
            return new Alternc_Api_Response(array("content" => $did));
        }
    }

}

// class Alternc_Api_Object_Subdomain
