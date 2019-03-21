<?php

/**
 * Domain Api of AlternC, used by alternc-api package
 */
class Alternc_Api_Object_Domain extends Alternc_Api_Legacyobject {

    protected $dom; // m_dom instance

    function __construct($service) {
        global $dom;
        parent::__construct($service);
        $this->dom = $dom;
    }

    /** API Method from legacy class method dom->get_domain_list()
     * @param $options a hash with parameters transmitted to legacy call
     * may be "uid" to only return domains for a specific user-id
     * (if you are not admin, this WILL only list YOUR domains anyway)
     * may be "offset" and/or "count" to do paging.
     * @return Alternc_Api_Response whose content is the list of hosted domains on this server 
     * (no more details as of now)
     */
    function find($options) {
        global $cuid;
        $sql = "";
        if ($this->isAdmin) {
            if (isset($options["uid"])) {
                $uid = intval($options["uid"]);
            } else {
                $uid = -1;
            }
        } else {
            $uid = $cuid;
        }
        if ($uid != -1) {
            $sql = " WHERE compte=$uid ";
        } else {
            $sql = "";
        }
        $stmt = $this->db->prepare("SELECT * FROM domaines $sql ORDER BY domaine");
        $stmt->execute();
        $result = array();
        while ($me = $stmt->fetch(PDO::FETCH_OBJ)) {
            $result[$me->domaine] = $me;
        }
        list($offset, $count) = $this->offsetAndCount($options, count($result));
        if ($offset != -1 || $count != -1) {
            $result = array_slice($result, $offset, $count);
        }
        return new Alternc_Api_Response(array("content" => $result));
    }

    /** API Method from legacy class method dom->get_domain_all($dom)
     * @param $options a hash with parameters transmitted to legacy call
     * musr be the domain name $dom
     * @return Alternc_Api_Response whose content is the list of domain info and subdomains 
     */
    function get($options) {
        global $cuid;
        if ($this->isAdmin) {
            if (isset($options["uid"])) {
                $cuid = intval($options["uid"]);
            }
        }
        $mandatory = array("dom");
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
        $did = $this->dom->get_domain_all($options["dom"]);
        $this->dom->unlock();
        if (!$did) {
            return $this->alterncLegacyErrorManager();
        } else {
            return new Alternc_Api_Response(array("content" => $did));
        }
    }

    /** API Method from legacy class method dom->add_domain()
     * @param $options a hash with parameters transmitted to legacy call
     * mandatory parameters: domain(str), dns(bool)
     * non-mandatory: noerase(bool, only admins), force(bool, only admins), isslave(bool), slavedom(str)
     * @return Alternc_Api_Response whose content is the newly created DOMAIN id
     */
    function add($options) {
        $mandatory = array("domain", "dns");
        $defaults = array("noerase" => false, "force" => false, "isslave" => false, "slavedom" => "");
        $missing = "";
        foreach ($mandatory as $key) {
            if (!isset($options[$key])) {
                $missing.=$key . " ";
            }
        }
        if ($missing) {
            return new Alternc_Api_Response(array("code" => self::ERR_INVALID_ARGUMENT, "message" => "Missing or invalid argument: " . $missing));
        }
        foreach ($defaults as $key => $value) {
            if (!isset($options[$key])) {
                $options[$key] = $value;
            }
        }
        if (!$this->isAdmin) { // only admin can change the options below:
            $options["noerase"] = false;
            $options["force"] = false;
        }
        $did = $this->dom->add_domain($options["domain"], $options["dns"], $options["noerase"], $options["force"], $options["isslave"], $options["slavedom"]);
        if (!$did) {
            return $this->alterncLegacyErrorManager();
        } else {
            return new Alternc_Api_Response(array("content" => $did));
        }
    }

    /** API Method from legacy class method dom->edit_domain()
     * @param $options a hash with parameters transmitted to legacy call
     * mandatory parameters: domain(str), dns(bool)
     * non-mandatory: noerase(bool, only admins), force(bool, only admins), isslave(bool), slavedom(str)
     * @return Alternc_Api_Response whose content is the newly created DOMAIN id
     */
    function update($options) {
        $mandatory = array("domain", "dns", "gesmx");
        $defaults = array("force" => false, "ttl" => 86400);
        $missing = "";
        foreach ($mandatory as $key) {
            if (!isset($options[$key])) {
                $missing.=$key . " ";
            }
        }
        if ($missing) {
            return new Alternc_Api_Response(array("code" => self::ERR_INVALID_ARGUMENT, "message" => "Missing or invalid argument: " . $missing));
        }
        foreach ($defaults as $key => $value) {
            if (!isset($options[$key])) {
                $options[$key] = $value;
            }
        }
        if (!$this->isAdmin) { // only admin can change the options below:
            $options["force"] = false;
        }
        $did = $this->dom->edit_domain($options["domain"], $options["dns"], $options["gesmx"], $options["force"], $options["ttl"]);
        if (!$did) {
            return $this->alterncLegacyErrorManager();
        } else {
            return new Alternc_Api_Response(array("content" => $did));
        }
    }

    /** API Method from legacy class method dom->del_domain()
     * @param $options a hash with parameters transmitted to legacy call
     * mandatory parameters: domain
     * @return Alternc_Api_Response TRUE if the domain has been marked for deletion.
     */
    function del($options) {
        if (!isset($options["domain"])) {
            return new Alternc_Api_Response(array("code" => self::ERR_ALTERNC_FUNCTION, "message" => "Missing or invalid argument: DOMAIN"));
        }
        $result = $this->dom->del_domain($options["domain"]);
        if (!$result) {
            return $this->alterncLegacyErrorManager();
        } else {
            return new Alternc_Api_Response(array("content" => true));
        }
    }

    


    
}

// class Alternc_Api_Object_Domain
