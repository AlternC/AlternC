<?php

/**
 * Authentication API used by server to authenticate a user 
 * using his alternc login and password
 */
class Alternc_Api_Auth_Login implements Alternc_Api_Auth_Interface {

    private $db; // PDO object

    const ERR_INVALID_ARGUMENT = 1111201;

    /**
     * Constructor of the Login Api Auth
     *
     * @param $service an Alternc_Api_Service object
     * @return create the object
     */
    function __construct($service) {

        if (!($service instanceof Alternc_Api_Service))
            throw new \Exception("Invalid argument (service)", ERR_INVALID_ARGUMENT);

        $this->db = $service->getDb();
    }

    /**
     * Authenticate a user
     *
     * @param $options options, depending on the auth scheme, including uid for setuid users
     *   here, login is the AlternC username, and password is the password for this username.
     * @return an Alternc_Api_Token
     */
    function auth($options) {

        if (!isset($options["login"]) || !is_string($options["login"])) {
            throw new \Exception("Missing required parameter login", self::ERR_INVALID_ARGUMENT);
        }
        if (!isset($options["password"]) || !is_string($options["password"])) {
            throw new \Exception("Missing required parameter password", self::ERR_INVALID_ARGUMENT);
        }
        if (!isset($options["duration"]) || !intval($options["duration"])) {
            $options["duration"]=31; // default 1 month
        }

        if (!preg_match("#^[0-9a-zA-Z-]{1,32}$#", $options["login"])) { // FIXME : normalize this on AlternC !!!
            throw new \Exception("Invalid login", self::ERR_INVALID_LOGIN);
        }

        $stmt = $this->db->prepare("SELECT m.pass,m.enabled,m.uid,m.login,m.su FROM membres m WHERE m.login=?;");
        $stmt->execute(array($options["login"]));
        $me = $stmt->fetch(PDO::FETCH_OBJ);
        if (!$me) {
            return new Alternc_Api_Response(array("code" => ERR_INVALID_AUTH, "message" => "Invalid login or password"));
        }
        if (!password_verify($options["password"],$me->pass)) {
            return new Alternc_Api_Response(array("code" => ERR_DISABLED_ACCOUNT, "message" => "Invalid login or password"));            
        }
        if (!$me->enabled) {
            return new Alternc_Api_Response(array("code" => ERR_DISABLED_ACCOUNT, "message" => "Account is disabled"));
        }
        return Alternc_Api_Token::tokenGenerate(
            array("uid" => $me->uid, "isAdmin" => ($me->su != 0), "duration" => $options["duration"]), $this->db
        );
    }

    /**
     * instructions on how to use this Auth class
     * @return array("fields" => array("fields to send, required or not"), "description" => "description of this auth")
     */
    function instructions() {
        return array("fields" => array("login" => "AlternC user account", "password" => "AlternC's user password stored in membres table."),
            "description" => "Authenticate against an AlternC user and password, the same as for the control panel"
        );
    }

}

// class Alternc_Api_Auth_Login

