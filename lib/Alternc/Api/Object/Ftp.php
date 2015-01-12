<?php

/**
 * Ftp Api of AlternC, used by alternc-api package
 */
class Alternc_Api_Object_Ftp extends Alternc_Api_Legacyobject {

    protected $ftp; // m_ftp instance

    function __construct($service) {
        global $ftp;
        parent::__construct($service);
        $this->ftp = $ftp;
    }

    /** API Method from legacy class method ftp->add_ftp()
     * @param $options a hash with parameters transmitted to legacy call
     * mandatory parameters: prefix, login, pass, dir
     * @return Alternc_Api_Response whose content is the newly created UID
     */
    function add($options) {
        $mandatory = array("prefix", "login", "pass", "dir");
        $missing = "";
        foreach ($mandatory as $key) {
            if (!isset($options[$key])) {
                $missing.=$key . " ";
            }
        }
        if ($missing) {
            return new Alternc_Api_Response(array("code" => self::ERR_INVALID_ARGUMENT, "message" => "Missing or invalid argument: " . $missing));
        }
        $ftpid = $this->ftp->add_ftp($options["prefix"], $options["login"], $options["pass"], $options["dir"]);
        if (!$ftpid) {
            return $this->alterncLegacyErrorManager();
        } else {
            return new Alternc_Api_Response(array("content" => $ftpid));
        }
    }

    /** API Method from legacy class method ftp->put_ftp_details()
     * @param $options a hash with parameters transmitted to legacy call
     * mandatory parameters: id
     * non-mandatory: prefix, login, pass, dir
     * @return Alternc_Api_Response whose content is the updated UID
     */
    function update($options) {
        $defaults = array("prefix", "login", "dir");
        if (!isset($options["id"])) {
            return new Alternc_Api_Response(array("code" => self::ERR_INVALID_ARGUMENT, "message" => "Missing or invalid argument: ID"));
        }
        $id = intval($options["id"]);
        $old = $this->ftp->get_ftp_details($id);
        if (!$old) {
            return new Alternc_Api_Response(array("code" => self::ERR_NOT_FOUND, "message" => "FTP Account not found"));
        }
        foreach ($defaults as $key) {
            if (!isset($options[$key])) {
                $options[$key] = $old[$key];
            }
        }
        if (!isset($options["pass"]))
            $options["pass"] = "";
        $result = $this->ftp->put_ftp_details($id, $options["prefix"], $options["login"], $options["pass"], $options["dir"]);
        if (!$result) {
            return $this->alterncLegacyErrorManager();
        } else {
            return new Alternc_Api_Response(array("content" => $result));
        }
    }

    /** API Method from legacy class method ftp->del_ftp()
     * @param $options a hash with parameters transmitted to legacy call
     * mandatory parameters: id
     * @return Alternc_Api_Response TRUE if the FTP account has been deleted.
     */
    function del($options) {
        if (!isset($options["id"])) {
            return new Alternc_Api_Response(array("code" => self::ERR_ALTERNC_FUNCTION, "message" => "Missing or invalid argument: ID"));
        }
        $result = $this->ftp->delete_ftp(intval($options["id"]));
        if (!$result) {
            return $this->alterncLegacyErrorManager();
        } else {
            return new Alternc_Api_Response(array("content" => true));
        }
    }

    /** API Method from legacy class method ftp->get_list()
     * @param $options a hash with parameters transmitted to legacy call
     * non-mandatory parameters: 
     *  Any of: offset(int=0), count(int=+inf)
     * @return Alternc_Api_Response An array with all matching FTP account informations as hashes
     */
    function find($options) {
        $result = $this->ftp->get_list();
        if (!$result) {
            return $this->alterncLegacyErrorManager();
        } else {
            list($offset, $count) = $this->offsetAndCount($options, count($result));
            if ($offset != -1 || $count != -1) {
                $result = array_slice($result, $offset, $count);
            }
            return new Alternc_Api_Response(array("content" => $result));
        }
    }

    /** API Method from legacy class method ftp->is_ftp()
     * @param $options a hash with parameters transmitted to legacy call
     * mandatory parameters: DIR
     * @return Alternc_Api_Response Integer the ID of the account in this folder, or FALSE
     */
    function isFtp($options) {
        if (!isset($options["dir"])) {
            return new Alternc_Api_Response(array("code" => self::ERR_INVALID_ARGUMENT, "message" => "Missing or invalid argument: DIR"));
        }
        $result = $this->ftp->is_ftp($options["dir"]);
        if (!$result) {
            return $this->alterncLegacyErrorManager();
        } else {
            return new Alternc_Api_Response(array("content" => $result));
        }
    }

}

// class Alternc_Api_Object_Ftp