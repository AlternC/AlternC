<?php

/**
 * Authentication API used by server to authenticate a user using a 
 * specific method.
 */
interface Alternc_Api_Auth_Interface {

  /**
   * contructor :
   * $service is an Alternc_Api_Service object having a getDb() method
   */
  function __constructor($service);


  /**
   * auth takes options specific to the auth itself
   * returns an Alternc_Api_Token object
   */
  function auth($options);


  /** 
   * instructions on how to use this Auth class
   * @return array("fields" => array("fields to send, required or not"), "description" => "description of this auth")
   */
  function instructions();

}

