
Coding Convention
----------------

We are using the following coding convention

Graphically
----------------

The classes are structured like that.
texts prefixed by "##" are comments to explain the convention with an example. Do not use them in your code. 

<?php
## The header below is on every head of php file use current year instead of 2012. ##
/*
 ----------------------------------------------------------------------
 AlternC - Web Hosting System
 Copyright (C) 2000-2012 by the AlternC Development Team.
 https://alternc.org/
 ----------------------------------------------------------------------
 LICENSE

 This program is free software; you can redistribute it and/or
 modify it under the terms of the GNU General Public License (GPL)
 as published by the Free Software Foundation; either version 2
 of the License, or (at your option) any later version.

 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 To read the license please visit http://www.gnu.org/copyleft/gpl.html
 ----------------------------------------------------------------------
 Purpose of file: Manage mailing-lists with Mailman
 ----------------------------------------------------------------------
*/
## Enter the purpose of this file up there ^^ ##

## the name of the class is the same as the file (m_dom, m_mailman...) ##
class m_mailman {


## 2 blank lines here and between each functions, give it some space ;) ##
## the following line of -- allow you to visually quickly find a function ##
## then the comment after it is using /** notation, so that phpdoc can build a self-made documentation of our classes ##
  /* ----------------------------------------------------------------- */
  /** ## here we put a full long description of the function's behavior, in english ##
   * ## this description may span multiple lines ##
   * @param $domain string ## each param has its @param line, followed by the param name ($domain) type (string) and description ##
   * @return array ## a @return line is added when the function returns anything, followed by the returned type and description ##
   */
  function enum_ml($domain = null, $order_by = array('domain', 'list')) {
## function which consist of more than one word are separated by _ ##
## private functions are prefixed by "private" and their name starts by "_" ##
    global $err,$db,$cuid;
## use the globals for $db (database mapper), $cuid (uid of current alternc's user) $err (error/log mapper) 
    $err->log("mailman","enum_ml");
## when calling an important function, log it that way ##
## when raising an error, use the following syntax ##
    $err->raise("classname",__("text in english", "alternc", true));
  }
}
/* at the end of a php-only file, we don't put a ?> */

?>

Syntax in code :
----------------

  function names starting by "hook_" are hooks called that way:

global $hooks;
$res=$hooks->invoke("hook_class_method_name",array($param1,$param2));

$params1 & 2 are sent as parameters to the hooked functions of each files.

the hook function name must have the CALLING class name after hook_ 
like hook_admin_del_member for a hook in "admin" class.

$res is an array with the returned data as values, for each function called in a class. 
The key in that array is the classname called.

