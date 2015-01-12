<?php

/**
 * Passowrd Policy Api of AlternC, used by alternc-api package
 */
class Alternc_Api_Object_Policy extends Alternc_Api_Legacyobject {

    const ERR_NOT_FOUND = 11151603;

    function __construct($service) {
        global $admin, $cuid;
        if (!($service instanceof Alternc_Api_Service)) {
            throw new \Exception("Bad argument: service is not an Alternc_Api_Service", self::ERR_INVALID_ARGUMENT);
        }
        // We store the global $cuid to AlternC legacy classes
        $this->cuid = $cuid = $service->token->uid;
        $this->isAdmin = $service->token->isAdmin;
        // We use the global $admin from AlternC legacy classes
        $this->admin = $admin;
        // Set the legacy rights:
        $this->admin->enabled = $this->isAdmin;
    }

    /** API Method from legacy class method admin->editPolicy($policy,$minsize,$maxsize,$classcount,$allowlogin)
     * @param $options a hash with parameters transmitted to legacy call
     * mandatory parameters: policy minsize maxsize classcount allowlogin
     * @return Alternc_Api_Response TRUE if the password policy has been updated
     */
    function update($options) {
        $mandatory = array("policy", "minsize", "maxsize", "classcount", "allowlogin");
        $missing = "";
        foreach ($mandatory as $key) {
            if (!isset($options[$key])) {
                $missing.=$key . " ";
            }
        }
        if ($missing) {
            return new Alternc_Api_Response(array("code" => self::ERR_INVALID_ARGUMENT, "message" => "Missing or invalid argument: " . $missing));
        }
        $result = $this->admin->editPolicy($options["policy"], 
                $options["minsize"], $options["maxsize"], $options["classcount"], $options["allowlogin"]);
        if (!$result) {
            return $this->alterncLegacyErrorManager();
        } else {
            return new Alternc_Api_Response(array("content" => true));
        }
    }

    /** API Method from legacy class method admin->listPasswordPolicies()
     * @param $options a hash with parameters transmitted to legacy call
     * no options is used.
     * @return Alternc_Api_Response An array with all password policies
     */
    function find($options) {
        $result = $this->admin->listPasswordPolicies();

        if (!$result) {
            return $this->alterncLegacyErrorManager();
        } else {
            return new Alternc_Api_Response(array("content" => $result));
        }
    }

}

// class Alternc_Api_Object_Account