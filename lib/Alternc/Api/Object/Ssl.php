<?php

/**
 * SSL Api of AlternC, used by alternc-api package
 */
class Alternc_Api_Object_Ssl extends Alternc_Api_Legacyobject {

  protected $ssl;
  
  function __construct($service) {
    global $ssl;
    parent::__construct($service);
    // We use the global $ssl from AlternC legacy classes
    $this->ssl=$ssl;
  }

  /** API Method from legacy class get_list()
   * @param $options a hash with parameters transmitted to legacy call
   *  filter = the kind of ssl certificates to show or not show
   * @return Alternc_Api_Response whose content is an array of hashes containing all corresponding certificates informations
   */
  function getList($options) {
    if (isset($options["filter"]) && intval($options["filter"])) {
      $filter=intval($options["filter"]);
    } else {
      $filter=null;
    }
    $ssllist=$this->ssl->get_list($filter);
    return new Alternc_Api_Response( array("content" => $ssllist) );
  }


  /** API Method from legacy class new_csr()
   * @param $options a hash with parameters transmitted to legacy call
   *  fqdn = the DNS name to create a CSR to
   * @return Alternc_Api_Response whose content is the CSR ID in the certificate database
   */
  function newCsr($options) {
    if (!isset($options["fqdn"]) || !is_string($options["fqdn"])) {
      return new Alternc_Api_Response( array("code" => self::ERR_INVALID_ARGUMENT, "message" => "Missing or invalid argument: FQDN") );
    }
    $certid=$this->ssl->new_csr($options["fqdn"]);
    if ($certid===false) {
      return $this->alterncLegacyErrorManager();
    }
    return new Alternc_Api_Response( array("content" => $certid) );
  }


  /** API Method from legacy class get_certificate()
   * @param $options a hash with parameters transmitted to legacy call
   *  id = the ID of the certificate in the certifiate table to get
   * @return Alternc_Api_Response whose content is a hash with all informations for that certificate
   */
  function getCertificate($options) {
    if (!isset($options["id"]) || !intval($options["id"])) {
      return new Alternc_Api_Response( array("code" => self::ERR_INVALID_ARGUMENT, "message" => "Missing or invalid argument: ID") );
    }
    $certinfo=$this->ssl->get_certificate(intval($options["id"]));
    if ($certinfo===false) {
      return $this->alterncLegacyErrorManager();
    }
    return new Alternc_Api_Response( array("content" => $certinfo) );
  }


  /** API Method from legacy class share()
   * @param $options a hash with parameters transmitted to legacy call
   *  id = the ID of the certificate to share or unshare
   *  action = boolean telling to share(true) or unshare(false) this certificate
   * @return Alternc_Api_Response true.
   */
  function share($options) {
    if (!isset($options["id"]) || !intval($options["id"])) {
      return new Alternc_Api_Response( array("code" => self::ERR_INVALID_ARGUMENT, "message" => "Missing or invalid argument: ID") );
    }
    if (!isset($options["action"]) ) {
      return new Alternc_Api_Response( array("code" => self::ERR_INVALID_ARGUMENT, "message" => "Missing or invalid argument: ACTION") );
    }
    $isok=$this->ssl->share(intval($options["id"]), (intval($options["action"]))? true : false );
    if ($isok===false) {
      return $this->alterncLegacyErrorManager();
    }
    return new Alternc_Api_Response( array("content" => $isok) );
  }


  /** API Method from legacy class import_cert()
   * @param $options a hash with parameters transmitted to legacy call
   *  key, crt, chain = key and crt (both mandatory) and chain (not mandatory) to import
   * @return Alternc_Api_Response the ID of the newly created certificate in the table.
   */
  function importCert($options) {
    if (!isset($options["key"]) || !is_string($options["key"])) {
      return new Alternc_Api_Response( array("code" => self::ERR_INVALID_ARGUMENT, "message" => "Missing or invalid argument: KEY") );
    }
    if (!isset($options["crt"]) || !is_string($options["crt"])) {
      return new Alternc_Api_Response( array("code" => self::ERR_INVALID_ARGUMENT, "message" => "Missing or invalid argument: CRT") );
    }
    if (isset($options["chain"])) {
      if (!is_string($options["chain"])) {
	return new Alternc_Api_Response( array("code" => self::ERR_INVALID_ARGUMENT, "message" => "Invalid argument: CHAIN") );
      }
    } else {
      $options["chain"]="";
    }

    $certid=$this->ssl->import_cert($options["key"],$options["crt"],$options["chain"]);
    if ($certid===false) {
      return $this->alterncLegacyErrorManager();
    }
    return new Alternc_Api_Response( array("content" => $certid) );
  }


  /** API Method from legacy class finalize()
   * @param $options a hash with parameters transmitted to legacy call
   *  second part of the new_csr() call, finalize a certificate creation
   *  id = ID of the certificate to finalize in the table.
   *  crt = Certificate data
   *  chain = Chained Certificate date (not mandatory)
   * @return Alternc_Api_Response the ID of the updated certificate in the table.
   */
  function finalize($options) {
    if (!isset($options["id"]) || !intval($options["id"])) {
      return new Alternc_Api_Response( array("code" => self::ERR_INVALID_ARGUMENT, "message" => "Missing or invalid argument: ID") );
    }
    if (!isset($options["crt"]) || !is_string($options["crt"])) {
      return new Alternc_Api_Response( array("code" => self::ERR_INVALID_ARGUMENT, "message" => "Missing or invalid argument: CRT") );
    }
    if (isset($options["chain"])) {
      if (!is_string($options["chain"])) {
	return new Alternc_Api_Response( array("code" => self::ERR_INVALID_ARGUMENT, "message" => "Invalid argument: CHAIN") );
      }
    } else {
      $options["chain"]="";
    }

    $certid=$this->ssl->finalize(intval($options["id"]),$options["crt"],$options["chain"]);
    if ($certid===false) {
      return $this->alterncLegacyErrorManager();
    }
    return new Alternc_Api_Response( array("content" => $certid) );
  }


  /** API Method from legacy class alias_add()
   * @param $options a hash with parameters transmitted to legacy call
   *  add the alias 'name' with the content value 'value' in the global apache configuration
   *  @return Alternc_Api_Response true 
   */
  function aliasAdd($options) {
    if (!isset($options["name"]) || !is_string($options["name"])) {
      return new Alternc_Api_Response( array("code" => self::ERR_INVALID_ARGUMENT, "message" => "Missing or invalid argument: NAME") );
    }
    if (!isset($options["content"]) || !is_string($options["content"])) {
      return new Alternc_Api_Response( array("code" => self::ERR_INVALID_ARGUMENT, "message" => "Missing or invalid argument: CONTENT") );
    }

    $isok=$this->ssl->alias_add($options["name"],$options["content"]);
    if ($isok===false) {
      return $this->alterncLegacyErrorManager();
    }
    return new Alternc_Api_Response( array("content" => $isok) );
  }


  /** API Method from legacy class alias_del()
   * @param $options a hash with parameters transmitted to legacy call
   *  del the alias 'name' in the global apache configuration
   *  @return Alternc_Api_Response true 
   */
  function aliasDel($options) {
    if (!isset($options["name"]) || !is_string($options["name"])) {
      return new Alternc_Api_Response( array("code" => self::ERR_INVALID_ARGUMENT, "message" => "Missing or invalid argument: NAME") );
    }

    $isok=$this->ssl->alias_del($options["name"]);
    if ($isok===false) {
      return $this->alterncLegacyErrorManager();
    }
    return new Alternc_Api_Response( array("content" => $isok) );
  }



} // class Alternc_Api_Object_Ssl