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
    function available($options) {
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
    function listMails($options) {
        $defaults = array("dom_id" => null, "search" => "", "offset" => 0, "count" => 30, "show_systemmails" => false);
        foreach ($defaults as $key => $value) {
            if (!isset($options[$key])) {
                $options[$key] = $value;
            }
        }
        $did = $this->mail->enum_domain_mails($options["dom_id"], $options["search"], 
                $options["offset"], $options["count"], $options["show_systemmails"]);
        if (!$did) {
            return $this->alterncLegacyErrorManager();
        } else {
            return new Alternc_Api_Response(array("content" => $did));
        }
    }
}

// class Alternc_Api_Object_Mail
