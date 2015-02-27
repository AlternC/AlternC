<?php

/**
 * Domain Api of AlternC, used by alternc-api package
 */
class Alternc_Api_Object_Mail extends Alternc_Api_Legacyobject {

    protected $mail; // m_mail instance

    function __construct($service) {
        global $mail;
        parent::__construct($service);
        $this->mail = $mail;
    }

    /** API Method from legacy class method mail->enum_domains() 
     * @param $options a hash with parameters transmitted to legacy call
     * @return Alternc_Api_Response whose content is the list of hosted domains 
     * for mails on this server 
     * (no more details as of now)
     */
    function listDomains($options) {
        global $cuid;
        $sql = "";
        $uid = $cuid;
        if ($this->isAdmin && isset($options["uid"])) {
            $uid = intval($options["uid"]);
        }

        $did = $this->mail->enum_domains($uid);
        if (!$did) {
            return $this->alterncLegacyErrorManager();
        } else {
            return new Alternc_Api_Response(array("content" => $did));
        }
    }

    /** API Method from legacy class method mail->available()
     * @param $options a hash with parameters transmitted to legacy call
     * mandatory parameters: mail(str)
     * @return Alternc_Api_Response telling TRUE or FALSE
     */
    function isAvailable($options) {
        if (!isset($options["mail"])) {
            return new Alternc_Api_Response(array("code" => self::ERR_INVALID_ARGUMENT, "message" => "Missing or invalid argument: " . "mail"));
        }
        $did = $this->mail->available($options["mail"]);
        if (!$did) {
            return $this->alterncLegacyErrorManager();
        } else {
            return new Alternc_Api_Response(array("content" => $did));
        }
    }

    /** API Method from legacy class method $mail->enum_domain_mails
     * ($dom_id = null, $search="", $offset=0, $count=30, $show_systemmails=false)
     * @param $options a hash with parameters transmitted to legacy call
     * mandatory parameters: 
     * non-mandatory: 
     * @return Alternc_Api_Response whose content is 
     */
    function getAll($options) {
        $defaults = array("dom_id" => null, "search" => "", "offset" => 0, "count" => 30, "show_systemmails" => false);
        foreach ($defaults as $key => $value) {
            if (!isset($options[$key])) {
                $options[$key] = $value;
            }
        }
        $did = $this->mail->enum_domain_mails($options["dom_id"], $options["search"], $options["offset"], $options["count"], $options["show_systemmails"]);
        if (!$did) {
            return $this->alterncLegacyErrorManager();
        } else {
            return new Alternc_Api_Response(array("content" => $did));
        }
    }

    /** API Method from legacy class method $mail->create
     * ($dom_id, $mail,$type="",$dontcheck=false){
     * @param $options a hash with parameters transmitted to legacy call
     * mandatory parameters: 
     * non-mandatory: 
     * @return Alternc_Api_Response whose content is 
     */
    function create($options) {
        $defaults = array("type" => "");
        $mandatory = array("dom_id", "mail");
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
        $did = $this->mail->create($options["dom_id"], $options["mail"], $options["type"]);
        if (!$did) {
            return $this->alterncLegacyErrorManager();
        } else {
            return new Alternc_Api_Response(array("content" => $did));
        }
    }

    /** API Method from legacy class method $mail->get_details($mail_id)
     * @param $options a hash with parameters transmitted to legacy call
     * mandatory parameters: mail_id
     * @return Alternc_Api_Response whose content is 
     */
    function get($options) {
        $mandatory = array("mail_id");
        $missing = "";
        foreach ($mandatory as $key) {
            if (!isset($options[$key])) {
                $missing.=$key . " ";
            }
        }
        if ($missing) {
            return new Alternc_Api_Response(array("code" => self::ERR_INVALID_ARGUMENT, "message" => "Missing or invalid argument: " . $missing));
        }
        $did = $this->mail->get_details($options["mail_id"]);
        if (!$did) {
            return $this->alterncLegacyErrorManager();
        } else {
            return new Alternc_Api_Response(array("content" => $did));
        }
    }

    /** API Method from legacy class method $mail->get_account_by_mail_id($mail_id)
     * @param $options a hash with parameters transmitted to legacy call
     * mandatory parameters: mail_id
     * @return Alternc_Api_Response whose content is 
     */
    function account($options) {
        $mandatory = array("mail_id");
        $missing = "";
        foreach ($mandatory as $key) {
            if (!isset($options[$key])) {
                $missing.=$key . " ";
            }
        }
        if ($missing) {
            return new Alternc_Api_Response(array("code" => self::ERR_INVALID_ARGUMENT, "message" => "Missing or invalid argument: " . $missing));
        }
        $did = $this->mail->get_account_by_mail_id($options["mail_id"]);
        if (!$did) {
            return $this->alterncLegacyErrorManager();
        } else {
            return new Alternc_Api_Response(array("content" => $did));
        }
    }

    /** API Method from legacy class method $mail->delete($mail_id)
     * @param $options a hash with parameters transmitted to legacy call
     * mandatory parameters: mail_id
     * @return Alternc_Api_Response whose content is 
     */
    function delete($options) {
        $mandatory = array("mail_id");
        $missing = "";
        foreach ($mandatory as $key) {
            if (!isset($options[$key])) {
                $missing.=$key . " ";
            }
        }
        if ($missing) {
            return new Alternc_Api_Response(array("code" => self::ERR_INVALID_ARGUMENT, "message" => "Missing or invalid argument: " . $missing));
        }
        $did = $this->mail->delete($options["mail_id"]);
        if (!$did) {
            return $this->alterncLegacyErrorManager();
        } else {
            return new Alternc_Api_Response(array("content" => $did));
        }
    }

    /** API Method from legacy class method $mail->undelete($mail_id)
     * @param $options a hash with parameters transmitted to legacy call
     * mandatory parameters: mail_id
     * @return Alternc_Api_Response whose content is 
     */
    function undelete($options) {
        $mandatory = array("mail_id");
        $missing = "";
        foreach ($mandatory as $key) {
            if (!isset($options[$key])) {
                $missing.=$key . " ";
            }
        }
        if ($missing) {
            return new Alternc_Api_Response(array("code" => self::ERR_INVALID_ARGUMENT, "message" => "Missing or invalid argument: " . $missing));
        }
        $did = $this->mail->undelete($options["mail_id"]);
        if (!$did) {
            return $this->alterncLegacyErrorManager();
        } else {
            return new Alternc_Api_Response(array("content" => $did));
        }
    }

    /** API Method from legacy class method $mail->delete($mail_id)
     * @param $options a hash with parameters transmitted to legacy call
     * mandatory parameters: mail_id, password
     * @return Alternc_Api_Response whose content is 
     */
    function passwd($options) {
        $mandatory = array("mail_id", "password");
        $missing = "";
        foreach ($mandatory as $key) {
            if (!isset($options[$key])) {
                $missing.=$key . " ";
            }
        }
        if ($missing) {
            return new Alternc_Api_Response(array("code" => self::ERR_INVALID_ARGUMENT, "message" => "Missing or invalid argument: " . $missing));
        }
        $did = $this->mail->set_passwd($options["mail_id"], $options["password"]);
        if (!$did) {
            return $this->alterncLegacyErrorManager();
        } else {
            return new Alternc_Api_Response(array("content" => $did));
        }
    }

    /** API Method from legacy class method $mail->enable($mail_id)
     * @param $options a hash with parameters transmitted to legacy call
     * mandatory parameters: mail_id
     * @return Alternc_Api_Response whose content is 
     */
    function enable($options) {
        $mandatory = array("mail_id");
        $missing = "";
        foreach ($mandatory as $key) {
            if (!isset($options[$key])) {
                $missing.=$key . " ";
            }
        }
        if ($missing) {
            return new Alternc_Api_Response(array("code" => self::ERR_INVALID_ARGUMENT, "message" => "Missing or invalid argument: " . $missing));
        }
        $did = $this->mail->enable($options["mail_id"]);
        if (!$did) {
            return $this->alterncLegacyErrorManager();
        } else {
            return new Alternc_Api_Response(array("content" => $did));
        }
    }

    /** API Method from legacy class method $mail->disable($mail_id)
     * @param $options a hash with parameters transmitted to legacy call
     * mandatory parameters: mail_id
     * @return Alternc_Api_Response whose content is 
     */
    function disable($options) {
        $mandatory = array("mail_id");
        $missing = "";
        foreach ($mandatory as $key) {
            if (!isset($options[$key])) {
                $missing.=$key . " ";
            }
        }
        if ($missing) {
            return new Alternc_Api_Response(array("code" => self::ERR_INVALID_ARGUMENT, "message" => "Missing or invalid argument: " . $missing));
        }
        $did = $this->mail->disable($options["mail_id"]);
        if (!$did) {
            return $this->alterncLegacyErrorManager();
        } else {
            return new Alternc_Api_Response(array("content" => $did));
        }
    }

    /** API Method from legacy class method $mail-> set_details
     * ($mail_id, $islocal, $quotamb, $recipients,$delivery="dovecot",$dontcheck=false)
     * @param $options a hash with parameters transmitted to legacy call
     * mandatory parameters: 
     * non-mandatory: 
     * @return Alternc_Api_Response whose content is 
     */
    function update($options) {
        $defaults = array("delivery" => "dovecot");
        $mandatory = array("mail_id", "islocal", "quotamb", "recipients");
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
        $did = $this->mail->set_details($options["mail_id"], $options["islocal"], $options["quotamb"], $options["recipients"], $options["delivery"]);
        if (!$did) {
            return $this->alterncLegacyErrorManager();
        } else {
            return new Alternc_Api_Response(array("content" => $did));
        }
    }

}

// class Alternc_Api_Object_Mail
