<?php

/**
 * Any Legacy AlternC Api should use this class as a parent 
 * to be able to handle properly the access rights & error messages
 *
 * @author benjamin
 */
class Alternc_Api_Legacyobject {

    protected $admin; // m_admin instance
    protected $cuid; // current user id
    protected $isAdmin; // is it an Admin account?
    protected $db; // PDO DB access to AlternC's database.

    const ERR_INVALID_ARGUMENT = 111201;
    const ERR_ALTERNC_FUNCTION = 111202;

    function __construct($service) {
        global $admin, $cuid;
        if (!($service instanceof Alternc_Api_Service)) {
            throw new \Exception("Bad argument: service is not an Alternc_Api_Service", self::ERR_INVALID_ARGUMENT);
        }
        // We store the global $cuid to AlternC legacy classes
        $this->db = $service->db;
        $this->cuid = $cuid = $service->token->uid;
        $this->isAdmin = $service->token->isAdmin;
        // We use the global $admin from AlternC legacy classes
        $this->admin = $admin;
        // Set the legacy rights:
        $this->admin->enabled = $this->isAdmin;
    }

    /** return a proper Alternc_Api_Response from an error class and error string 
     * from AlternC legacy class
     */
    protected function alterncLegacyErrorManager() {
        global $err;
        return new Alternc_Api_Response(array("code" => self::ERR_ALTERNC_FUNCTION, "message" => "[" . $err->clsid . "] " . $err->error));
    }

    /** ensure that offset & count are set properly from $options.
     */
    protected function offsetAndCount($options, $max) {
        $offset = -1;
        $count = -1;
        if (isset($options["count"]))
            $count = intval($options["count"]);
        if (isset($options["offset"]))
            $offset = intval($options["offset"]);
        if ($offset != -1 || $count != -1) {
            if ($offset < 0 || $offset > $max)
                $offset = 0;
            if ($count < 0 || $count > 1000)
                $count = 1000;
        }
        return array($offset, $count);
    }

}

// Aternc_Api_Legacyobject 

