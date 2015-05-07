<?php

/**
 * Passowrd Policy Api of AlternC, used by alternc-api package
 */
class Alternc_Api_Object_Policy extends Alternc_Api_Legacyobject {

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
        $result = $this->admin->editPolicy($options["policy"], $options["minsize"], $options["maxsize"], $options["classcount"], $options["allowlogin"]);
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

    /** API Method from legacy class method admin->checkPolicy($policy,$login,$password)
     * @param $options a hash with parameters transmitted to legacy call
     * mandatory parameters: policy login password
     * @return Alternc_Api_Response TRUE if the password match the policy
     */
    function check($options) {
        $mandatory = array("policy", "login", "password");
        $missing = "";
        foreach ($mandatory as $key) {
            if (!isset($options[$key])) {
                $missing.=$key . " ";
            }
        }
        if ($missing) {
            return new Alternc_Api_Response(array("code" => self::ERR_INVALID_ARGUMENT, "message" => "Missing or invalid argument: " . $missing));
        }
        $result = $this->admin->checkPolicy($options["policy"], $options["login"], $options["password"]);
        if (!$result) {
            return $this->alterncLegacyErrorManager();
        } else {
            return new Alternc_Api_Response(array("content" => true));
        }
    }

}

// class Alternc_Api_Object_Account