<?php

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * This file is part of the PEAR Console_CommandLine package.
 *
 * PHP version 5
 *
 * LICENSE: This source file is subject to the MIT license that is available
 * through the world-wide-web at the following URI:
 * http://opensource.org/licenses/mit-license.
 *
 * @category  Console 
 * @package   Console_CommandLine
 * @author    David JEAN LOUIS <izimobil@gmail.com>
 * @copyright 2007 David JEAN LOUIS
 * @license   http://opensource.org/licenses/mit-license. MIT License 
 * @version   CVS: $Id$
 * @link      http://pear..net/package/Console_CommandLine
 * @since     File available since release 0.1.0
 * @filesource
 */

/**
 * The Outputter interface.
 */


/**
 * Console_CommandLine default Outputter.
 *
 * @category  Console
 * @package   Console_CommandLine
 * @author    David JEAN LOUIS <izimobil@gmail.com>
 * @copyright 2007 David JEAN LOUIS
 * @license   http://opensource.org/licenses/mit-license. MIT License 
 * @version   Release: 1.2.0
 * @link      http://pear..net/package/Console_CommandLine
 * @since     Class available since release 0.1.0
 */
class Console_CommandLine_Outputter_Default implements Console_CommandLine_Outputter
{
    // stdout() {{{

    /**
     * Writes the message $msg to STDOUT.
     *
     * @param string $msg The message to output
     *
     * @return void
     */
    public function stdout($msg)
    {
        if (defined('STDOUT')) {
            fwrite(STDOUT, $msg);
        } else {
            echo $msg;
        }
    }

    // }}}
    // stderr() {{{

    /**
     * Writes the message $msg to STDERR.
     *
     * @param string $msg The message to output
     *
     * @return void
     */
    public function stderr($msg)
    {
        if (defined('STDERR')) {
            fwrite(STDERR, $msg);
        } else {
            echo $msg;
        }
    }

    // }}}
}


/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * This file is part of the PEAR Console_CommandLine package.
 *
 * PHP version 5
 *
 * LICENSE: This source file is subject to the MIT license that is available
 * through the world-wide-web at the following URI:
 * http://opensource.org/licenses/mit-license.
 *
 * @category  Console 
 * @package   Console_CommandLine
 * @author    David JEAN LOUIS <izimobil@gmail.com>
 * @copyright 2007 David JEAN LOUIS
 * @license   http://opensource.org/licenses/mit-license. MIT License 
 * @version   CVS: $Id$
 * @link      http://pear..net/package/Console_CommandLine
 * @since     File available since release 0.1.0
 * @filesource
 */

/**
 * Class that represent a command line element (an option, or an argument).
 *
 * @category  Console
 * @package   Console_CommandLine
 * @author    David JEAN LOUIS <izimobil@gmail.com>
 * @copyright 2007 David JEAN LOUIS
 * @license   http://opensource.org/licenses/mit-license. MIT License 
 * @version   Release: 1.2.0
 * @link      http://pear..net/package/Console_CommandLine
 * @since     Class available since release 0.1.0
 */
abstract class Console_CommandLine_Element
{
    // Public properties {{{

    /**
     * The element name.
     *
     * @var string $name Element name
     */
    public $name;

    /**
     * The name of variable displayed in the usage message, if no set it 
     * defaults to the "name" property.
     *
     * @var string $help_name Element "help" variable name
     */
    public $help_name;

    /**
     * The element description.
     *
     * @var string $description Element description
     */
    public $description;

    /**
     * The default value of the element if not provided on the command line.
     *
     * @var mixed $default Default value of the option.
     */
    public $default;

    /**
     * Custom errors messages for this element
     *
     * This array is of the form:
     * <code>
     * 
     * array(
     *     $messageName => $messageText,
     *     $messageName => $messageText,
     *     ...
     * );
     * ?>
     * </code>
     *
     * If specified, these messages override the messages provided by the
     * default message provider. For example:
     * <code>
     * 
     * $messages = array(
     *     'ARGUMENT_REQUIRED' => 'The argument foo is required.',
     * );
     * ?>
     * </code>
     *
     * @var array
     * @see Console_CommandLine_MessageProvider_Default
     */
    public $messages = array();

    // }}}
    // __construct() {{{

    /**
     * Constructor.
     *
     * @param string $name   The name of the element
     * @param array  $params An optional array of parameters
     *
     * @return void
     */
    public function __construct($name = null, $params = array()) 
    {
        $this->name = $name;
        foreach ($params as $attr => $value) {
            if (property_exists($this, $attr)) {
                $this->$attr = $value;
            }
        }
    }

    // }}}
    // toString() {{{

    /**
     * Returns the string representation of the element.
     *
     * @return string The string representation of the element
     * @todo use __toString() instead
     */
    public function toString()
    {
        return $this->help_name;
    }
    // }}}
    // validate() {{{

    /**
     * Validates the element instance and set it's default values.
     *
     * @return void
     * @throws Console_CommandLine_Exception
     */
    public function validate()
    {
        // if no help_name passed, default to name
        if ($this->help_name == null) {
            $this->help_name = $this->name;
        }
    }

    // }}}
}


/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * This file is part of the PEAR Console_CommandLine package.
 *
 * PHP version 5
 *
 * LICENSE: This source file is subject to the MIT license that is available
 * through the world-wide-web at the following URI:
 * http://opensource.org/licenses/mit-license.
 *
 * @category  Console
 * @package   Console_CommandLine
 * @author    David JEAN LOUIS <izimobil@gmail.com>
 * @copyright 2007 David JEAN LOUIS
 * @license   http://opensource.org/licenses/mit-license. MIT License
 * @version   CVS: $Id$
 * @link      http://pear..net/package/Console_CommandLine
 * @since     File available since release 0.1.0
 * @filesource
 */

/**
 * Include base element class.
 */


/**
 * Class that represent a command line argument.
 *
 * @category  Console
 * @package   Console_CommandLine
 * @author    David JEAN LOUIS <izimobil@gmail.com>
 * @copyright 2007 David JEAN LOUIS
 * @license   http://opensource.org/licenses/mit-license. MIT License
 * @version   Release: 1.2.0
 * @link      http://pear..net/package/Console_CommandLine
 * @since     Class available since release 0.1.0
 */
class Console_CommandLine_Argument extends Console_CommandLine_Element
{
    // Public properties {{{

    /**
     * Setting this to true will tell the parser that the argument expects more
     * than one argument and that argument values should be stored in an array.
     *
     * @var boolean $multiple Whether the argument expects multiple values
     */
    public $multiple = false;

    /**
     * Setting this to true will tell the parser that the argument is optional
     * and can be ommited.
     * Note that it is not a good practice to make arguments optional, it is
     * the role of the options to be optional, by essence.
     *
     * @var boolean $optional Whether the argument is optional or not.
     */
    public $optional = false;

    /**
     * An array of possible values for the argument.
     *
     * @var array $choices Valid choices for the argument
     */
    public $choices = array();

    // }}}
    // validate() {{{

    /**
     * Validates the argument instance.
     *
     * @return void
     * @throws Console_CommandLine_Exception
     * @todo use exceptions
     */
    public function validate()
    {
        // check if the argument name is valid
        if (!preg_match('/^[a-zA-Z_\x7f-\xff]+[a-zA-Z0-9_\x7f-\xff]*$/',
            $this->name)) {
            Console_CommandLine::triggerError(
                'argument_bad_name',
                E_USER_ERROR,
                array('{$name}' => $this->name)
            );
        }
        if (!$this->optional && $this->default !== null) {
            Console_CommandLine::triggerError(
                'argument_no_default',
                E_USER_ERROR
            );
        }
        parent::validate();
    }

    // }}}
}


/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * This file is part of the PEAR Console_CommandLine package.
 *
 * PHP version 5
 *
 * LICENSE: This source file is subject to the MIT license that is available
 * through the world-wide-web at the following URI:
 * http://opensource.org/licenses/mit-license.
 *
 * @category  Console
 * @package   Console_CommandLine
 * @author    David JEAN LOUIS <izimobil@gmail.com>
 * @author    Michael Gauthier <mike@silverorange.com>
 * @copyright 2007 David JEAN LOUIS, 2009 silverorange
 * @license   http://opensource.org/licenses/mit-license. MIT License
 * @version   CVS: $Id$
 * @link      http://pear..net/package/Console_CommandLine
 * @since     File available since release 1.1.0
 * @filesource
 */

/**
 * Common interfacefor message providers that allow overriding with custom
 * messages
 *
 * Message providers may optionally implement this interface.
 *
 * @category  Console
 * @package   Console_CommandLine
 * @author    David JEAN LOUIS <izimobil@gmail.com>
 * @author    Michael Gauthier <mike@silverorange.com>
 * @copyright 2007 David JEAN LOUIS, 2009 silverorange
 * @license   http://opensource.org/licenses/mit-license. MIT License
 * @version   Release: 1.2.0
 * @link      http://pear..net/package/Console_CommandLine
 * @since     Interface available since release 1.1.0
 */
interface Console_CommandLine_CustomMessageProvider
{
    // getWithCustomMesssages() {{{

    /**
     * Retrieves the given string identifier corresponding message.
     *
     * For a list of identifiers please see the provided default message
     * provider.
     *
     * @param string $code     The string identifier of the message
     * @param array  $vars     An array of template variables
     * @param array  $messages An optional array of messages to use. Array
     *                         indexes are message codes.
     *
     * @return string
     * @see Console_CommandLine_MessageProvider
     * @see Console_CommandLine_MessageProvider_Default
     */
    public function getWithCustomMessages(
        $code, $vars = array(), $messages = array()
    );

    // }}}
}


/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * This file is part of the PEAR Console_CommandLine package.
 *
 * PHP version 5
 *
 * LICENSE: This source file is subject to the MIT license that is available
 * through the world-wide-web at the following URI:
 * http://opensource.org/licenses/mit-license.
 *
 * @category  Console 
 * @package   Console_CommandLine
 * @author    David JEAN LOUIS <izimobil@gmail.com>
 * @copyright 2007 David JEAN LOUIS
 * @license   http://opensource.org/licenses/mit-license. MIT License 
 * @version   CVS: $Id$
 * @link      http://pear..net/package/Console_CommandLine
 * @since     File available since release 0.1.0
 * @filesource
 */

/**
 * Message providers common interface, all message providers must implement
 * this interface.
 *
 * @category  Console
 * @package   Console_CommandLine
 * @author    David JEAN LOUIS <izimobil@gmail.com>
 * @copyright 2007 David JEAN LOUIS
 * @license   http://opensource.org/licenses/mit-license. MIT License 
 * @version   Release: 1.2.0
 * @link      http://pear..net/package/Console_CommandLine
 * @since     Class available since release 0.1.0
 */
interface Console_CommandLine_MessageProvider
{
    // get() {{{

    /**
     * Retrieves the given string identifier corresponding message.
     * For a list of identifiers please see the provided default message 
     * provider.
     *
     * @param string $code The string identifier of the message
     * @param array  $vars An array of template variables
     *
     * @return string
     * @see Console_CommandLine_MessageProvider_Default
     */
    public function get($code, $vars=array());

    // }}}
}


/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * This file is part of the PEAR Console_CommandLine package.
 *
 * PHP version 5
 *
 * LICENSE: This source file is subject to the MIT license that is available
 * through the world-wide-web at the following URI:
 * http://opensource.org/licenses/mit-license.
 *
 * @category  Console 
 * @package   Console_CommandLine
 * @author    David JEAN LOUIS <izimobil@gmail.com>
 * @copyright 2007 David JEAN LOUIS
 * @license   http://opensource.org/licenses/mit-license. MIT License 
 * @version   CVS: $Id$
 * @link      http://pear..net/package/Console_CommandLine
 * @since     File available since release 0.1.0
 * @filesource
 */

/**
 * Required by this class.
 */


/**
 * Class that represent the StoreFalse action.
 *
 * The execute method store the boolean 'false' in the corrsponding result
 * option array entry (the value is true if the option is not present in the 
 * command line entered by the user).
 *
 * @category  Console
 * @package   Console_CommandLine
 * @author    David JEAN LOUIS <izimobil@gmail.com>
 * @copyright 2007 David JEAN LOUIS
 * @license   http://opensource.org/licenses/mit-license. MIT License 
 * @version   Release: 1.2.0
 * @link      http://pear..net/package/Console_CommandLine
 * @since     Class available since release 0.1.0
 */
class Console_CommandLine_Action_StoreFalse extends Console_CommandLine_Action
{
    // execute() {{{

    /**
     * Executes the action with the value entered by the user.
     *
     * @param mixed $value  The option value
     * @param array $params An array of optional parameters
     *
     * @return string
     */
    public function execute($value = false, $params = array())
    {
        $this->setResult(false);
    }

    // }}}
}


/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * This file is part of the PEAR Console_CommandLine package.
 *
 * PHP version 5
 *
 * LICENSE: This source file is subject to the MIT license that is available
 * through the world-wide-web at the following URI:
 * http://opensource.org/licenses/mit-license.
 *
 * @category  Console 
 * @package   Console_CommandLine
 * @author    David JEAN LOUIS <izimobil@gmail.com>
 * @copyright 2007 David JEAN LOUIS
 * @license   http://opensource.org/licenses/mit-license. MIT License 
 * @version   CVS: $Id$
 * @link      http://pear..net/package/Console_CommandLine
 * @since     File available since release 0.1.0
 * @filesource
 */

/**
 * Required by this class.
 */


/**
 * Class that represent the StoreTrue action.
 *
 * The execute method store the boolean 'true' in the corrsponding result
 * option array entry (the value is false if the option is not present in the 
 * command line entered by the user).
 *
 * @category  Console
 * @package   Console_CommandLine
 * @author    David JEAN LOUIS <izimobil@gmail.com>
 * @copyright 2007 David JEAN LOUIS
 * @license   http://opensource.org/licenses/mit-license. MIT License 
 * @version   Release: 1.2.0
 * @link      http://pear..net/package/Console_CommandLine
 * @since     Class available since release 0.1.0
 */
class Console_CommandLine_Action_StoreTrue extends Console_CommandLine_Action
{
    // execute() {{{

    /**
     * Executes the action with the value entered by the user.
     *
     * @param mixed $value  The option value
     * @param array $params An array of optional parameters
     *
     * @return string
     */
    public function execute($value = false, $params = array())
    {
        $this->setResult(true);
    }
    // }}}
}


/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * This file is part of the PEAR Console_CommandLine package.
 *
 * PHP version 5
 *
 * LICENSE: This source file is subject to the MIT license that is available
 * through the world-wide-web at the following URI:
 * http://opensource.org/licenses/mit-license.
 *
 * @category  Console 
 * @package   Console_CommandLine
 * @author    David JEAN LOUIS <izimobil@gmail.com>
 * @copyright 2007 David JEAN LOUIS
 * @license   http://opensource.org/licenses/mit-license. MIT License 
 * @version   CVS: $Id$
 * @link      http://pear..net/package/Console_CommandLine
 * @since     File available since release 0.1.0
 * @filesource
 */

/**
 * Required by this class.
 */


/**
 * Class that represent the List action, a special action that simply output an 
 * array as a list.
 *
 * @category  Console
 * @package   Console_CommandLine
 * @author    David JEAN LOUIS <izimobil@gmail.com>
 * @copyright 2007 David JEAN LOUIS
 * @license   http://opensource.org/licenses/mit-license. MIT License 
 * @version   Release: 1.2.0
 * @link      http://pear..net/package/Console_CommandLine
 * @since     Class available since release 0.1.0
 */
class Console_CommandLine_Action_List extends Console_CommandLine_Action
{
    // execute() {{{

    /**
     * Executes the action with the value entered by the user.
     * Possible parameters are:
     * - message: an alternative message to display instead of the default 
     *   message,
     * - delimiter: an alternative delimiter instead of the comma,
     * - post: a string to append after the message (default is the new line 
     *   char).
     *
     * @param mixed $value  The option value
     * @param array $params An optional array of parameters
     *
     * @return string
     */
    public function execute($value = false, $params = array())
    {
        $list = isset($params['list']) ? $params['list'] : array();
        $msg  = isset($params['message']) 
            ? $params['message'] 
            : $this->parser->message_provider->get('LIST_DISPLAYED_MESSAGE');
        $del  = isset($params['delimiter']) ? $params['delimiter'] : ', ';
        $post = isset($params['post']) ? $params['post'] : "\n";
        $this->parser->outputter->stdout($msg . implode($del, $list) . $post);
        exit(0);
    }
    // }}}
}


/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * This file is part of the PEAR Console_CommandLine package.
 *
 * PHP version 5
 *
 * LICENSE: This source file is subject to the MIT license that is available
 * through the world-wide-web at the following URI:
 * http://opensource.org/licenses/mit-license.
 *
 * @category  Console 
 * @package   Console_CommandLine
 * @author    David JEAN LOUIS <izimobil@gmail.com>
 * @copyright 2007 David JEAN LOUIS
 * @license   http://opensource.org/licenses/mit-license. MIT License 
 * @version   CVS: $Id$
 * @link      http://pear..net/package/Console_CommandLine
 * @since     File available since release 0.1.0
 * @filesource
 */

/**
 * Required by this class.
 */


/**
 * Class that represent the Version action, a special action that displays the
 * version string of the program.
 *
 * @category  Console
 * @package   Console_CommandLine
 * @author    David JEAN LOUIS <izimobil@gmail.com>
 * @copyright 2007 David JEAN LOUIS
 * @license   http://opensource.org/licenses/mit-license. MIT License 
 * @version   Release: 1.2.0
 * @link      http://pear..net/package/Console_CommandLine
 * @since     Class available since release 0.1.0
 */
class Console_CommandLine_Action_Version extends Console_CommandLine_Action
{
    // execute() {{{

    /**
     * Executes the action with the value entered by the user.
     *
     * @param mixed $value  The option value
     * @param array $params An array of optional parameters
     *
     * @return string
     */
    public function execute($value = false, $params = array())
    {
        return $this->parser->displayVersion();
    }
    // }}}
}


/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * This file is part of the PEAR Console_CommandLine package.
 *
 * PHP version 5
 *
 * LICENSE: This source file is subject to the MIT license that is available
 * through the world-wide-web at the following URI:
 * http://opensource.org/licenses/mit-license.
 *
 * @category  Console 
 * @package   Console_CommandLine
 * @author    David JEAN LOUIS <izimobil@gmail.com>
 * @copyright 2007 David JEAN LOUIS
 * @license   http://opensource.org/licenses/mit-license. MIT License 
 * @version   CVS: $Id$
 * @link      http://pear..net/package/Console_CommandLine
 * @since     File available since release 0.1.0
 * @filesource
 */

/**
 * Required by this class.
 */


/**
 * Class that represent the StoreFloat action.
 *
 * The execute method store the value of the option entered by the user as a
 * float in the result option array entry, if the value passed is not a float
 * an Exception is raised.
 *
 * @category  Console
 * @package   Console_CommandLine
 * @author    David JEAN LOUIS <izimobil@gmail.com>
 * @copyright 2007 David JEAN LOUIS
 * @license   http://opensource.org/licenses/mit-license. MIT License 
 * @version   Release: 1.2.0
 * @link      http://pear..net/package/Console_CommandLine
 * @since     Class available since release 0.1.0
 */
class Console_CommandLine_Action_StoreFloat extends Console_CommandLine_Action
{
    // execute() {{{

    /**
     * Executes the action with the value entered by the user.
     *
     * @param mixed $value  The option value
     * @param array $params An array of optional parameters
     *
     * @return string
     * @throws Console_CommandLine_Exception
     */
    public function execute($value = false, $params = array())
    {
        if (!is_numeric($value)) {

            throw Console_CommandLine_Exception::factory(
                'OPTION_VALUE_TYPE_ERROR',
                array(
                    'name'  => $this->option->name,
                    'type'  => 'float',
                    'value' => $value
                ),
                $this->parser
            );
        }
        $this->setResult((float)$value);
    }
    // }}}
}


/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * This file is part of the PEAR Console_CommandLine package.
 *
 * PHP version 5
 *
 * LICENSE: This source file is subject to the MIT license that is available
 * through the world-wide-web at the following URI:
 * http://opensource.org/licenses/mit-license.
 *
 * @category  Console 
 * @package   Console_CommandLine
 * @author    David JEAN LOUIS <izimobil@gmail.com>
 * @copyright 2007 David JEAN LOUIS
 * @license   http://opensource.org/licenses/mit-license. MIT License 
 * @version   CVS: $Id$
 * @link      http://pear..net/package/Console_CommandLine
 * @since     File available since release 0.1.0
 * @filesource
 */

/**
 * Required by this class.
 */


/**
 * Class that represent the StoreArray action.
 *
 * The execute method appends the value of the option entered by the user to 
 * the result option array entry.
 *
 * @category  Console
 * @package   Console_CommandLine
 * @author    David JEAN LOUIS <izimobil@gmail.com>
 * @copyright 2007 David JEAN LOUIS
 * @license   http://opensource.org/licenses/mit-license. MIT License 
 * @version   Release: 1.2.0
 * @link      http://pear..net/package/Console_CommandLine
 * @since     Class available since release 0.1.0
 */
class Console_CommandLine_Action_StoreArray extends Console_CommandLine_Action
{
    // Protected properties {{{

    /**
     * Force a clean result when first called, overriding any defaults assigned.
     *
     * @var object $firstPass First time this action has been called.
     */
    protected $firstPass = true;

    // }}}
    // execute() {{{

    /**
     * Executes the action with the value entered by the user.
     *
     * @param mixed $value  The option value
     * @param array $params An optional array of parameters
     *
     * @return string
     */
    public function execute($value = false, $params = array())
    {
        $result = $this->getResult();
        if (null === $result || $this->firstPass) {
            $result          = array();
            $this->firstPass = false;
        }
        $result[] = $value;
        $this->setResult($result);
    }
    // }}}
}


/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * This file is part of the PEAR Console_CommandLine package.
 *
 * PHP version 5
 *
 * LICENSE: This source file is subject to the MIT license that is available
 * through the world-wide-web at the following URI:
 * http://opensource.org/licenses/mit-license.
 *
 * @category  Console 
 * @package   Console_CommandLine
 * @author    David JEAN LOUIS <izimobil@gmail.com>
 * @copyright 2007 David JEAN LOUIS
 * @license   http://opensource.org/licenses/mit-license. MIT License 
 * @version   CVS: $Id$
 * @link      http://pear..net/package/Console_CommandLine
 * @since     File available since release 0.1.0
 * @filesource
 */

/**
 * Required by this class.
 */


/**
 * Class that represent the StoreInt action.
 *
 * The execute method store the value of the option entered by the user as an
 * integer in the result option array entry, if the value passed is not an 
 * integer an Exception is raised.
 *
 * @category  Console
 * @package   Console_CommandLine
 * @author    David JEAN LOUIS <izimobil@gmail.com>
 * @copyright 2007 David JEAN LOUIS
 * @license   http://opensource.org/licenses/mit-license. MIT License 
 * @version   Release: 1.2.0
 * @link      http://pear..net/package/Console_CommandLine
 * @since     Class available since release 0.1.0
 */
class Console_CommandLine_Action_StoreInt extends Console_CommandLine_Action
{
    // execute() {{{

    /**
     * Executes the action with the value entered by the user.
     *
     * @param mixed $value  The option value
     * @param array $params An array of optional parameters
     *
     * @return string
     * @throws Console_CommandLine_Exception
     */
    public function execute($value = false, $params = array())
    {
        if (!is_numeric($value)) {

            throw Console_CommandLine_Exception::factory(
                'OPTION_VALUE_TYPE_ERROR',
                array(
                    'name'  => $this->option->name,
                    'type'  => 'int',
                    'value' => $value
                ),
                $this->parser
            );
        }
        $this->setResult((int)$value);
    }
    // }}}
}


/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * This file is part of the PEAR Console_CommandLine package.
 *
 * PHP version 5
 *
 * LICENSE: This source file is subject to the MIT license that is available
 * through the world-wide-web at the following URI:
 * http://opensource.org/licenses/mit-license.
 *
 * @category  Console 
 * @package   Console_CommandLine
 * @author    David JEAN LOUIS <izimobil@gmail.com>
 * @copyright 2007 David JEAN LOUIS
 * @license   http://opensource.org/licenses/mit-license. MIT License 
 * @version   CVS: $Id$
 * @link      http://pear..net/package/Console_CommandLine
 * @since     File available since release 0.1.0
 * @filesource
 */

/**
 * Required by this class.
 */


/**
 * Class that represent the Callback action.
 *
 * The result option array entry value is set to the return value of the
 * callback defined in the option.
 *
 * There are two steps to defining a callback option:
 *   - define the option itself using the callback action
 *   - write the callback; this is a function (or method) that takes five
 *     arguments, as described below.
 *
 * All callbacks are called as follows:
 * <code>
 * callable_func(
 *     $value,           // the value of the option
 *     $option_instance, // the option instance
 *     $result_instance, // the result instance
 *     $parser_instance, // the parser instance
 *     $params           // an array of params as specified in the option
 * );
 * </code>
 * and *must* return the option value.
 *
 * @category  Console
 * @package   Console_CommandLine
 * @author    David JEAN LOUIS <izimobil@gmail.com>
 * @copyright 2007 David JEAN LOUIS
 * @license   http://opensource.org/licenses/mit-license. MIT License 
 * @version   Release: 1.2.0
 * @link      http://pear..net/package/Console_CommandLine
 * @since     Class available since release 0.1.0
 */
class Console_CommandLine_Action_Callback extends Console_CommandLine_Action
{
    // execute() {{{

    /**
     * Executes the action with the value entered by the user.
     *
     * @param mixed $value  The value of the option
     * @param array $params An optional array of parameters
     *
     * @return string
     */
    public function execute($value = false, $params = array())
    {
        $this->setResult(call_user_func($this->option->callback, $value,
            $this->option, $this->result, $this->parser, $params));
    }
    // }}}
}


/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * This file is part of the PEAR Console_CommandLine package.
 *
 * PHP version 5
 *
 * LICENSE: This source file is subject to the MIT license that is available
 * through the world-wide-web at the following URI:
 * http://opensource.org/licenses/mit-license.
 *
 * @category  Console 
 * @package   Console_CommandLine
 * @author    David JEAN LOUIS <izimobil@gmail.com>
 * @copyright 2007 David JEAN LOUIS
 * @license   http://opensource.org/licenses/mit-license. MIT License 
 * @version   CVS: $Id$
 * @link      http://pear..net/package/Console_CommandLine
 * @since     File available since release 0.1.0
 * @filesource
 */

/**
 * Required by this class.
 */


/**
 * Class that represent the Version action.
 *
 * The execute methode add 1 to the value of the result option array entry.
 * The value is incremented each time the option is found, for example
 * with an option defined like that:
 *
 * <code>
 * $parser->addOption(
 *     'verbose',
 *     array(
 *         'short_name' => '-v',
 *         'action'     => 'Counter'
 *     )
 * );
 * </code>
 * If the user type:
 * <code>
 * $ script. -v -v -v
 * </code>
 * or: 
 * <code>
 * $ script. -vvv
 * </code>
 * the verbose variable will be set to to 3.
 *
 * @category  Console
 * @package   Console_CommandLine
 * @author    David JEAN LOUIS <izimobil@gmail.com>
 * @copyright 2007 David JEAN LOUIS
 * @license   http://opensource.org/licenses/mit-license. MIT License 
 * @version   Release: 1.2.0
 * @link      http://pear..net/package/Console_CommandLine
 * @since     Class available since release 0.1.0
 */
class Console_CommandLine_Action_Counter extends Console_CommandLine_Action
{
    // execute() {{{

    /**
     * Executes the action with the value entered by the user.
     *
     * @param mixed $value  The option value
     * @param array $params An optional array of parameters
     *
     * @return string
     */
    public function execute($value = false, $params = array())
    {
        $result = $this->getResult();
        if ($result === null) {
            $result = 0;
        }
        $this->setResult(++$result);
    }
    // }}}
}


/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * This file is part of the PEAR Console_CommandLine package.
 *
 * PHP version 5
 *
 * LICENSE: This source file is subject to the MIT license that is available
 * through the world-wide-web at the following URI:
 * http://opensource.org/licenses/mit-license.
 *
 * @category  Console 
 * @package   Console_CommandLine
 * @author    David JEAN LOUIS <izimobil@gmail.com>
 * @copyright 2007 David JEAN LOUIS
 * @license   http://opensource.org/licenses/mit-license. MIT License 
 * @version   CVS: $Id$
 * @link      http://pear..net/package/Console_CommandLine
 * @since     File available since release 0.1.0
 * @filesource
 */

/**
 * Required by this class.
 */


/**
 * Class that represent the StoreString action.
 *
 * The execute method store the value of the option entered by the user as a 
 * string in the result option array entry.
 *
 * @category  Console
 * @package   Console_CommandLine
 * @author    David JEAN LOUIS <izimobil@gmail.com>
 * @copyright 2007 David JEAN LOUIS
 * @license   http://opensource.org/licenses/mit-license. MIT License 
 * @version   Release: 1.2.0
 * @link      http://pear..net/package/Console_CommandLine
 * @since     Class available since release 0.1.0
 */
class Console_CommandLine_Action_StoreString extends Console_CommandLine_Action
{
    // execute() {{{

    /**
     * Executes the action with the value entered by the user.
     *
     * @param mixed $value  The option value
     * @param array $params An array of optional parameters
     *
     * @return string
     */
    public function execute($value = false, $params = array())
    {
        $this->setResult((string)$value);
    }
    // }}}
}


/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * This file is part of the PEAR Console_CommandLine package.
 *
 * PHP version 5
 *
 * LICENSE: This source file is subject to the MIT license that is available
 * through the world-wide-web at the following URI:
 * http://opensource.org/licenses/mit-license.
 *
 * @category  Console 
 * @package   Console_CommandLine
 * @author    David JEAN LOUIS <izimobil@gmail.com>
 * @copyright 2007 David JEAN LOUIS
 * @license   http://opensource.org/licenses/mit-license. MIT License 
 * @version   CVS: $Id$
 * @link      http://pear..net/package/Console_CommandLine
 * @since     File available since release 0.1.0
 * @filesource
 */

/**
 * Required by this class.
 */


/**
 * Class that represent the Help action, a special action that displays the
 * help message, telling the user how to use the program.
 *
 * @category  Console
 * @package   Console_CommandLine
 * @author    David JEAN LOUIS <izimobil@gmail.com>
 * @copyright 2007 David JEAN LOUIS
 * @license   http://opensource.org/licenses/mit-license. MIT License 
 * @version   Release: 1.2.0
 * @link      http://pear..net/package/Console_CommandLine
 * @since     Class available since release 0.1.0
 */
class Console_CommandLine_Action_Help extends Console_CommandLine_Action
{
    // execute() {{{

    /**
     * Executes the action with the value entered by the user.
     *
     * @param mixed $value  The option value
     * @param array $params An optional array of parameters
     *
     * @return string
     */
    public function execute($value = false, $params = array())
    {
        return $this->parser->displayUsage();
    }
    // }}}
}


/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * This file is part of the PEAR Console_CommandLine package.
 *
 * PHP version 5
 *
 * LICENSE: This source file is subject to the MIT license that is available
 * through the world-wide-web at the following URI:
 * http://opensource.org/licenses/mit-license.
 *
 * @category  Console 
 * @package   Console_CommandLine
 * @author    David JEAN LOUIS <izimobil@gmail.com>
 * @copyright 2007 David JEAN LOUIS
 * @license   http://opensource.org/licenses/mit-license. MIT License 
 * @version   CVS: $Id$
 * @link      http://pear..net/package/Console_CommandLine
 * @since     File available since release 0.1.0
 * @filesource
 */

/**
 * Required by this class.
 */


/**
 * Class that represent the Password action, a special action that allow the 
 * user to specify the password on the commandline or to be prompted for 
 * entering it.
 *
 * @category  Console
 * @package   Console_CommandLine
 * @author    David JEAN LOUIS <izimobil@gmail.com>
 * @copyright 2007 David JEAN LOUIS
 * @license   http://opensource.org/licenses/mit-license. MIT License 
 * @version   Release: 1.2.0
 * @link      http://pear..net/package/Console_CommandLine
 * @since     Class available since release 0.1.0
 */
class Console_CommandLine_Action_Password extends Console_CommandLine_Action
{
    // execute() {{{

    /**
     * Executes the action with the value entered by the user.
     *
     * @param mixed $value  The option value
     * @param array $params An array of optional parameters
     *
     * @return string
     */
    public function execute($value = false, $params = array())
    {
        $this->setResult(empty($value) ? $this->_promptPassword() : $value);
    }
    // }}}
    // _promptPassword() {{{

    /**
     * Prompts the password to the user without echoing it.
     *
     * @return string
     * @todo not echo-ing the password does not work on windows is there a way 
     *       to make this work ?
     */
    private function _promptPassword()
    {
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            fwrite(STDOUT,
                $this->parser->message_provider->get('PASSWORD_PROMPT_ECHO'));
            @flock(STDIN, LOCK_EX);
            $passwd = fgets(STDIN);
            @flock(STDIN, LOCK_UN);
        } else {
            fwrite(STDOUT, $this->parser->message_provider->get('PASSWORD_PROMPT'));
            // disable echoing
            system('stty -echo');
            @flock(STDIN, LOCK_EX);
            $passwd = fgets(STDIN);
            @flock(STDIN, LOCK_UN);
            system('stty echo');
        }
        return trim($passwd);
    }
    // }}}
}


/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * This file is part of the PEAR Console_CommandLine package.
 *
 * PHP version 5
 *
 * LICENSE: This source file is subject to the MIT license that is available
 * through the world-wide-web at the following URI:
 * http://opensource.org/licenses/mit-license.
 *
 * @category  Console 
 * @package   Console_CommandLine
 * @author    David JEAN LOUIS <izimobil@gmail.com>
 * @copyright 2007 David JEAN LOUIS
 * @license   http://opensource.org/licenses/mit-license. MIT License 
 * @version   CVS: $Id$
 * @link      http://pear..net/package/Console_CommandLine
 * @since     File available since release 0.1.0
 * @filesource
 */

/**
 * Outputters common interface, all outputters must implement this interface.
 *
 * @category  Console
 * @package   Console_CommandLine
 * @author    David JEAN LOUIS <izimobil@gmail.com>
 * @copyright 2007 David JEAN LOUIS
 * @license   http://opensource.org/licenses/mit-license. MIT License 
 * @version   Release: 1.2.0
 * @link      http://pear..net/package/Console_CommandLine
 * @since     Class available since release 0.1.0
 */
interface Console_CommandLine_Outputter
{
    // stdout() {{{

    /**
     * Processes the output for a message that should be displayed on STDOUT.
     *
     * @param string $msg The message to output
     *
     * @return void
     */
    public function stdout($msg);

    // }}}
    // stderr() {{{

    /**
     * Processes the output for a message that should be displayed on STDERR.
     *
     * @param string $msg The message to output
     *
     * @return void
     */
    public function stderr($msg);

    // }}}
}


/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * This file is part of the PEAR Console_CommandLine package.
 *
 * PHP version 5
 *
 * LICENSE: This source file is subject to the MIT license that is available
 * through the world-wide-web at the following URI:
 * http://opensource.org/licenses/mit-license.
 *
 * @category  Console 
 * @package   Console_CommandLine
 * @author    David JEAN LOUIS <izimobil@gmail.com>
 * @copyright 2007 David JEAN LOUIS
 * @license   http://opensource.org/licenses/mit-license. MIT License 
 * @version   CVS: $Id$
 * @link      http://pear..net/package/Console_CommandLine
 * @since     File available since release 0.1.0
 * @filesource
 */

/**
 * The renderer interface.
 */


/**
 * Console_CommandLine default renderer.
 *
 * @category  Console
 * @package   Console_CommandLine
 * @author    David JEAN LOUIS <izimobil@gmail.com>
 * @copyright 2007 David JEAN LOUIS
 * @license   http://opensource.org/licenses/mit-license. MIT License 
 * @version   Release: 1.2.0
 * @link      http://pear..net/package/Console_CommandLine
 * @since     Class available since release 0.1.0
 */
class Console_CommandLine_Renderer_Default implements Console_CommandLine_Renderer
{
    // Properties {{{

    /**
     * Integer that define the max width of the help text.
     *
     * @var integer $line_width Line width
     */
    public $line_width = 75;

    /**
     * Integer that define the max width of the help text.
     *
     * @var integer $line_width Line width
     */
    public $options_on_different_lines = false;

    /**
     * An instance of Console_CommandLine.
     *
     * @var Console_CommandLine $parser The parser
     */
    public $parser = false;

    // }}}
    // __construct() {{{

    /**
     * Constructor.
     *
     * @param object $parser A Console_CommandLine instance
     *
     * @return void
     */
    public function __construct($parser = false) 
    {
        $this->parser = $parser;
    }

    // }}}
    // usage() {{{

    /**
     * Returns the full usage message.
     *
     * @return string The usage message
     */
    public function usage()
    {
        $ret = '';
        if (!empty($this->parser->description)) { 
            $ret .= $this->description() . "\n\n";
        }
        $ret .= $this->usageLine() . "\n";
        if (count($this->parser->commands) > 0) {
            $ret .= $this->commandUsageLine() . "\n";
        }
        if (count($this->parser->options) > 0) {
            $ret .= "\n" . $this->optionList() . "\n";
        }
        if (count($this->parser->args) > 0) {
            $ret .= "\n" . $this->argumentList() . "\n";
        }
        if (count($this->parser->commands) > 0) {
            $ret .= "\n" . $this->commandList() . "\n";
        }
        $ret .= "\n";
        return $ret;
    }
    // }}}
    // error() {{{

    /**
     * Returns a formatted error message.
     *
     * @param string $error The error message to format
     *
     * @return string The error string
     */
    public function error($error)
    {
        $ret = 'Error: ' . $error . "\n";
        if ($this->parser->add_help_option) {
            $name = $this->name();
            $ret .= $this->wrap($this->parser->message_provider->get('PROG_HELP_LINE',
                array('progname' => $name))) . "\n";
            if (count($this->parser->commands) > 0) {
                $ret .= $this->wrap($this->parser->message_provider->get('COMMAND_HELP_LINE',
                    array('progname' => $name))) . "\n";
            }
        }
        return $ret;
    }

    // }}}
    // version() {{{

    /**
     * Returns the program version string.
     *
     * @return string The version string
     */
    public function version()
    {
        return $this->parser->message_provider->get('PROG_VERSION_LINE', array(
            'progname' => $this->name(),
            'version'  => $this->parser->version
        )) . "\n";
    }

    // }}}
    // name() {{{

    /**
     * Returns the full name of the program or the sub command
     *
     * @return string The name of the program
     */
    protected function name()
    {
        $name   = $this->parser->name;
        $parent = $this->parser->parent;
        while ($parent) {
            if (count($parent->options) > 0) {
                $name = '[' 
                    . strtolower($this->parser->message_provider->get('OPTION_WORD',
                          array('plural' => 's'))) 
                    . '] ' . $name;
            }
            $name = $parent->name . ' ' . $name;
            $parent = $parent->parent;
        }
        return $this->wrap($name);
    }

    // }}}
    // description() {{{

    /**
     * Returns the command line description message.
     *
     * @return string The description message
     */
    protected function description()
    {
        return $this->wrap($this->parser->description);
    }

    // }}}
    // usageLine() {{{

    /**
     * Returns the command line usage message
     *
     * @return string the usage message
     */
    protected function usageLine()
    {
        $usage = $this->parser->message_provider->get('USAGE_WORD') . ":\n";
        $ret   = $usage . '  ' . $this->name();
        if (count($this->parser->options) > 0) {
            $ret .= ' [' 
                . strtolower($this->parser->message_provider->get('OPTION_WORD'))
                . ']';
        }
        if (count($this->parser->args) > 0) {
            foreach ($this->parser->args as $name=>$arg) {
                $arg_str = $arg->help_name;
                if ($arg->multiple) {
                    $arg_str .= '1 ' . $arg->help_name . '2 ...';
                }
                if ($arg->optional) {
                    $arg_str = '[' . $arg_str . ']';
                }
                $ret .= ' ' . $arg_str;
            }
        }
        return $this->columnWrap($ret, 2);
    }

    // }}}
    // commandUsageLine() {{{

    /**
     * Returns the command line usage message for subcommands.
     *
     * @return string The usage line
     */
    protected function commandUsageLine()
    {
        if (count($this->parser->commands) == 0) {
            return '';
        }
        $ret = '  ' . $this->name();
        if (count($this->parser->options) > 0) {
            $ret .= ' [' 
                . strtolower($this->parser->message_provider->get('OPTION_WORD'))
                . ']';
        }
        $ret       .= " <command>";
        $hasArgs    = false;
        $hasOptions = false;
        foreach ($this->parser->commands as $command) {
            if (!$hasArgs && count($command->args) > 0) {
                $hasArgs = true;
            }
            if (!$hasOptions && ($command->add_help_option || 
                $command->add_version_option || count($command->options) > 0)) {
                $hasOptions = true;
            }
        }
        if ($hasOptions) {
            $ret .= ' [options]';
        }
        if ($hasArgs) {
            $ret .= ' [args]';
        }
        return $this->columnWrap($ret, 2);
    }

    // }}}
    // argumentList() {{{

    /**
     * Render the arguments list that will be displayed to the user, you can 
     * override this method if you want to change the look of the list.
     *
     * @return string The formatted argument list
     */
    protected function argumentList()
    {
        $col  = 0;
        $args = array();
        foreach ($this->parser->args as $arg) {
            $argstr = '  ' . $arg->toString();
            $args[] = array($argstr, $arg->description);
            $ln     = strlen($argstr);
            if ($col < $ln) {
                $col = $ln;
            }
        }
        $ret = $this->parser->message_provider->get('ARGUMENT_WORD') . ":";
        foreach ($args as $arg) {
            $text = str_pad($arg[0], $col) . '  ' . $arg[1];
            $ret .= "\n" . $this->columnWrap($text, $col+2);
        }
        return $ret;
    }

    // }}}
    // optionList() {{{

    /**
     * Render the options list that will be displayed to the user, you can 
     * override this method if you want to change the look of the list.
     *
     * @return string The formatted option list
     */
    protected function optionList()
    {
        $col     = 0;
        $options = array();
        foreach ($this->parser->options as $option) {
            $delim    = $this->options_on_different_lines ? "\n" : ', ';
            $optstr   = $option->toString($delim);
            $lines    = explode("\n", $optstr);
            $lines[0] = '  ' . $lines[0];
            if (count($lines) > 1) {
                $lines[1] = '  ' . $lines[1];
                $ln       = strlen($lines[1]);
            } else {
                $ln = strlen($lines[0]);
            }
            $options[] = array($lines, $option->description);
            if ($col < $ln) {
                $col = $ln;
            }
        }
        $ret = $this->parser->message_provider->get('OPTION_WORD') . ":";
        foreach ($options as $option) {
            if (count($option[0]) > 1) {
                $text = str_pad($option[0][1], $col) . '  ' . $option[1];
                $pre  = $option[0][0] . "\n";
            } else {
                $text = str_pad($option[0][0], $col) . '  ' . $option[1];
                $pre  = '';
            }
            $ret .= "\n" . $pre . $this->columnWrap($text, $col+2);
        }
        return $ret;
    }

    // }}}
    // commandList() {{{

    /**
     * Render the command list that will be displayed to the user, you can 
     * override this method if you want to change the look of the list.
     *
     * @return string The formatted subcommand list
     */
    protected function commandList()
    {

        $commands = array();
        $col      = 0;
        foreach ($this->parser->commands as $cmdname=>$command) {
            $cmdname    = '  ' . $cmdname;
            $commands[] = array($cmdname, $command->description, $command->aliases);
            $ln         = strlen($cmdname);
            if ($col < $ln) {
                $col = $ln;
            }
        }
        $ret = $this->parser->message_provider->get('COMMAND_WORD') . ":";
        foreach ($commands as $command) {
            $text = str_pad($command[0], $col) . '  ' . $command[1];
            if ($aliasesCount = count($command[2])) {
                $pad = '';
                $text .= ' (';
                $text .= $aliasesCount > 1 ? 'aliases: ' : 'alias: ';
                foreach ($command[2] as $alias) {
                    $text .= $pad . $alias;
                    $pad   = ', ';
                }
                $text .= ')';
            }
            $ret .= "\n" . $this->columnWrap($text, $col+2);
        }
        return $ret;
    }

    // }}}
    // wrap() {{{

    /**
     * Wraps the text passed to the method.
     *
     * @param string $text The text to wrap
     * @param int    $lw   The column width (defaults to line_width property)
     *
     * @return string The wrapped text
     */
    protected function wrap($text, $lw=null)
    {
        if ($this->line_width > 0) {
            if ($lw === null) {
                $lw = $this->line_width;
            }
            return wordwrap($text, $lw, "\n", false);
        }
        return $text;
    }

    // }}}
    // columnWrap() {{{

    /**
     * Wraps the text passed to the method at the specified width.
     *
     * @param string $text The text to wrap
     * @param int    $cw   The wrap width
     *
     * @return string The wrapped text
     */
    protected function columnWrap($text, $cw)
    {
        $tokens = explode("\n", $this->wrap($text));
        $ret    = $tokens[0];
        $chunks = $this->wrap(trim(substr($text, strlen($ret))), 
            $this->line_width - $cw);
        $tokens = explode("\n", $chunks);
        foreach ($tokens as $token) {
            if (!empty($token)) {
                $ret .= "\n" . str_repeat(' ', $cw) . $token;
            }
        }
        return $ret;
    }

    // }}}
}


/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * This file is part of the PEAR Console_CommandLine package.
 *
 * PHP version 5
 *
 * LICENSE: This source file is subject to the MIT license that is available
 * through the world-wide-web at the following URI:
 * http://opensource.org/licenses/mit-license.
 *
 * @category  Console 
 * @package   Console_CommandLine
 * @author    David JEAN LOUIS <izimobil@gmail.com>
 * @copyright 2007 David JEAN LOUIS
 * @license   http://opensource.org/licenses/mit-license. MIT License 
 * @version   CVS: $Id$
 * @link      http://pear..net/package/Console_CommandLine
 * @since     File available since release 0.1.0
 * @filesource
 */

/**
 * Class that represent an option action.
 *
 * @category  Console
 * @package   Console_CommandLine
 * @author    David JEAN LOUIS <izimobil@gmail.com>
 * @copyright 2007 David JEAN LOUIS
 * @license   http://opensource.org/licenses/mit-license. MIT License 
 * @version   Release: 1.2.0
 * @link      http://pear..net/package/Console_CommandLine
 * @since     Class available since release 0.1.0
 */
abstract class Console_CommandLine_Action
{
    // Properties {{{

    /**
     * A reference to the result instance.
     *
     * @var Console_CommandLine_Result $result The result instance
     */
    protected $result;

    /**
     * A reference to the option instance.
     *
     * @var Console_CommandLine_Option $option The action option
     */
    protected $option;

    /**
     * A reference to the parser instance.
     *
     * @var Console_CommandLine $parser The parser
     */
    protected $parser;

    // }}}
    // __construct() {{{

    /**
     * Constructor
     *
     * @param Console_CommandLine_Result $result The result instance
     * @param Console_CommandLine_Option $option The action option
     * @param Console_CommandLine        $parser The current parser
     *
     * @return void
     */
    public function __construct($result, $option, $parser)
    {
        $this->result = $result;
        $this->option = $option;
        $this->parser = $parser;
    }

    // }}}
    // getResult() {{{

    /**
     * Convenience method to retrieve the value of result->options[name].
     *
     * @return mixed The result value or null
     */
    public function getResult()
    {
        if (isset($this->result->options[$this->option->name])) {
            return $this->result->options[$this->option->name];
        }
        return null;
    }

    // }}}
    // format() {{{

    /**
     * Allow a value to be pre-formatted prior to being used in a choices test.
     * Setting $value to the new format will keep the formatting.
     *
     * @param mixed &$value The value to format
     *
     * @return mixed The formatted value
     */
    public function format(&$value)
    {
        return $value;
    }

    // }}}
    // setResult() {{{

    /**
     * Convenience method to assign the result->options[name] value.
     *
     * @param mixed $result The result value
     *
     * @return void
     */
    public function setResult($result)
    {
        $this->result->options[$this->option->name] = $result;
    }

    // }}}
    // execute() {{{

    /**
     * Executes the action with the value entered by the user.
     * All children actions must implement this method.
     *
     * @param mixed $value  The option value
     * @param array $params An optional array of parameters
     *
     * @return string
     */
    abstract public function execute($value = false, $params = array());
    // }}}
}


/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * This file is part of the PEAR Console_CommandLine package.
 *
 * PHP version 5
 *
 * LICENSE: This source file is subject to the MIT license that is available
 * through the world-wide-web at the following URI:
 * http://opensource.org/licenses/mit-license.
 *
 * @category  Console 
 * @package   Console_CommandLine
 * @author    David JEAN LOUIS <izimobil@gmail.com>
 * @copyright 2007 David JEAN LOUIS
 * @license   http://opensource.org/licenses/mit-license. MIT License 
 * @version   CVS: $Id$
 * @link      http://pear..net/package/Console_CommandLine
 * @since     File available since release 0.1.0
 * @filesource
 */

/**
 * A lightweight class to store the result of the command line parsing.
 *
 * @category  Console
 * @package   Console_CommandLine
 * @author    David JEAN LOUIS <izimobil@gmail.com>
 * @copyright 2007 David JEAN LOUIS
 * @license   http://opensource.org/licenses/mit-license. MIT License 
 * @version   Release: 1.2.0
 * @link      http://pear..net/package/Console_CommandLine
 * @since     Class available since release 0.1.0
 */
class Console_CommandLine_Result
{
    // Public properties {{{

    /**
     * The result options associative array.
     * Key is the name of the option and value its value.
     *
     * @var array $options Result options array
     */
    public $options = array();

    /**
     * The result arguments array.
     *
     * @var array $args Result arguments array
     */
    public $args = array();

    /**
     * Name of the command invoked by the user, false if no command invoked.
     *
     * @var string $command_name Result command name
     */
    public $command_name = false;

    /**
     * A result instance for the subcommand.
     *
     * @var Console_CommandLine_Result Result instance for the subcommand
     */
    public $command = false;

    // }}}
}


/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * This file is part of the PEAR Console_CommandLine package.
 *
 * PHP version 5
 *
 * LICENSE: This source file is subject to the MIT license that is available
 * through the world-wide-web at the following URI:
 * http://opensource.org/licenses/mit-license.
 *
 * @category  Console 
 * @package   Console_CommandLine
 * @author    David JEAN LOUIS <izimobil@gmail.com>
 * @copyright 2007 David JEAN LOUIS
 * @license   http://opensource.org/licenses/mit-license. MIT License 
 * @version   CVS: $Id$
 * @link      http://pear..net/package/Console_CommandLine
 * @since     File available since release 0.1.0
 * @filesource
 */

/**
 * The message provider interface.
 */


/**
 * The custom message provider interface.
 */


/**
 * Lightweight class that manages messages used by Console_CommandLine package, 
 * allowing the developper to customize these messages, for example to 
 * internationalize a command line frontend.
 *
 * @category  Console
 * @package   Console_CommandLine
 * @author    David JEAN LOUIS <izimobil@gmail.com>
 * @copyright 2007 David JEAN LOUIS
 * @license   http://opensource.org/licenses/mit-license. MIT License 
 * @version   Release: 1.2.0
 * @link      http://pear..net/package/Console_CommandLine
 * @since     Class available since release 0.1.0
 */
class Console_CommandLine_MessageProvider_Default
    implements Console_CommandLine_MessageProvider,
    Console_CommandLine_CustomMessageProvider
{
    // Properties {{{

    /**
     * Associative array of messages
     *
     * @var array $messages
     */
    protected $messages = array(
        'OPTION_VALUE_REQUIRED'   => 'Option "{$name}" requires a value.',
        'OPTION_VALUE_UNEXPECTED' => 'Option "{$name}" does not expect a value (got "{$value}").',
        'OPTION_VALUE_NOT_VALID'  => 'Option "{$name}" must be one of the following: "{$choices}" (got "{$value}").',
        'ARGUMENT_VALUE_NOT_VALID'=> 'Argument "{$name}" must be one of the following: "{$choices}" (got "{$value}").',
        'OPTION_VALUE_TYPE_ERROR' => 'Option "{$name}" requires a value of type {$type} (got "{$value}").',
        'OPTION_AMBIGUOUS'        => 'Ambiguous option "{$name}", can be one of the following: {$matches}.',
        'OPTION_UNKNOWN'          => 'Unknown option "{$name}".',
        'ARGUMENT_REQUIRED'       => 'You must provide at least {$argnum} argument{$plural}.',
        'PROG_HELP_LINE'          => 'Type "{$progname} --help" to get help.',
        'PROG_VERSION_LINE'       => '{$progname} version {$version}.',
        'COMMAND_HELP_LINE'       => 'Type "{$progname} <command> --help" to get help on specific command.',
        'USAGE_WORD'              => 'Usage',
        'OPTION_WORD'             => 'Options',
        'ARGUMENT_WORD'           => 'Arguments',
        'COMMAND_WORD'            => 'Commands',
        'PASSWORD_PROMPT'         => 'Password: ',
        'PASSWORD_PROMPT_ECHO'    => 'Password (warning: will echo): ',
        'INVALID_CUSTOM_INSTANCE' => 'Instance does not implement the required interface',
        'LIST_OPTION_MESSAGE'     => 'lists valid choices for option {$name}',
        'LIST_DISPLAYED_MESSAGE'  => 'Valid choices are: ',
        'INVALID_SUBCOMMAND'      => 'Command "{$command}" is not valid.',
        'SUBCOMMAND_REQUIRED'     => 'Please enter one of the following command: {$commands}.',
    );

    // }}}
    // get() {{{

    /**
     * Retrieve the given string identifier corresponding message.
     *
     * @param string $code The string identifier of the message
     * @param array  $vars An array of template variables
     *
     * @return string
     */
    public function get($code, $vars = array())
    {
        if (!isset($this->messages[$code])) {
            return 'UNKNOWN';
        }
        return $this->replaceTemplateVars($this->messages[$code], $vars);
    }

    // }}}
    // getWithCustomMessages() {{{

    /**
     * Retrieve the given string identifier corresponding message.
     *
     * @param string $code     The string identifier of the message
     * @param array  $vars     An array of template variables
     * @param array  $messages An optional array of messages to use. Array
     *                         indexes are message codes.
     *
     * @return string
     */
    public function getWithCustomMessages(
        $code, $vars = array(), $messages = array()
    ) {
        // get message
        if (isset($messages[$code])) {
            $message = $messages[$code];
        } elseif (isset($this->messages[$code])) {
            $message = $this->messages[$code];
        } else {
            $message = 'UNKNOWN';
        }
        return $this->replaceTemplateVars($message, $vars);
    }

    // }}}
    // replaceTemplateVars() {{{

    /**
     * Replaces template vars in a message
     *
     * @param string $message The message
     * @param array  $vars    An array of template variables
     *
     * @return string
     */
    protected function replaceTemplateVars($message, $vars = array())
    {
        $tmpkeys = array_keys($vars);
        $keys    = array();
        foreach ($tmpkeys as $key) {
            $keys[] = '{$' . $key . '}';
        }
        return str_replace($keys, array_values($vars), $message);
    }

    // }}}
}


/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * This file is part of the PEAR Console_CommandLine package.
 *
 * PHP version 5
 *
 * LICENSE: This source file is subject to the MIT license that is available
 * through the world-wide-web at the following URI:
 * http://opensource.org/licenses/mit-license.
 *
 * @category  Console 
 * @package   Console_CommandLine
 * @author    David JEAN LOUIS <izimobil@gmail.com>
 * @copyright 2007 David JEAN LOUIS
 * @license   http://opensource.org/licenses/mit-license. MIT License 
 * @version   CVS: $Id$
 * @link      http://pear..net/package/Console_CommandLine
 * @since     File available since release 0.1.0
 * @filesource
 */

/**
 * Required by this class.
 */



/**
 * Class that represent a commandline option.
 *
 * @category  Console
 * @package   Console_CommandLine
 * @author    David JEAN LOUIS <izimobil@gmail.com>
 * @copyright 2007 David JEAN LOUIS
 * @license   http://opensource.org/licenses/mit-license. MIT License 
 * @version   Release: 1.2.0
 * @link      http://pear..net/package/Console_CommandLine
 * @since     Class available since release 0.1.0
 */
class Console_CommandLine_Option extends Console_CommandLine_Element
{
    // Public properties {{{

    /**
     * The option short name (ex: -v).
     *
     * @var string $short_name Short name of the option
     */
    public $short_name;

    /**
     * The option long name (ex: --verbose).
     *
     * @var string $long_name Long name of the option
     */
    public $long_name;

    /**
     * The option action, defaults to "StoreString".
     *
     * @var string $action Option action
     */
    public $action = 'StoreString';

    /**
     * An array of possible values for the option. If this array is not empty 
     * and the value passed is not in the array an exception is raised.
     * This only make sense for actions that accept values of course.
     *
     * @var array $choices Valid choices for the option
     */
    public $choices = array();

    /**
     * The callback function (or method) to call for an action of type 
     * Callback, this can be any callable supported by the  function 
     * call_user_func.
     * 
     * Example:
     *
     * <code>
     * $parser->addOption('myoption', array(
     *     'short_name' => '-m',
     *     'long_name'  => '--myoption',
     *     'action'     => 'Callback',
     *     'callback'   => 'myCallbackFunction'
     * ));
     * </code>
     *
     * @var callable $callback The option callback
     */
    public $callback;

    /**
     * An associative array of additional params to pass to the class 
     * corresponding to the action, this array will also be passed to the 
     * callback defined for an action of type Callback, Example:
     *
     * <code>
     * // for a custom action
     * $parser->addOption('myoption', array(
     *     'short_name'    => '-m',
     *     'long_name'     => '--myoption',
     *     'action'        => 'MyCustomAction',
     *     'action_params' => array('foo'=>true, 'bar'=>false)
     * ));
     *
     * // if the user type:
     * // $ <yourprogram> -m spam
     * // in your MyCustomAction class the execute() method will be called
     * // with the value 'spam' as first parameter and 
     * // array('foo'=>true, 'bar'=>false) as second parameter
     * </code>
     *
     * @var array $action_params Additional parameters to pass to the action
     */
    public $action_params = array();

    /**
     * For options that expect an argument, this property tells the parser if 
     * the option argument is optional and can be ommited.
     *
     * @var bool $argumentOptional Whether the option arg is optional or not
     */
    public $argument_optional = false;

    /**
     * For options that uses the "choice" property only.
     * Adds a --list-<choice> option to the parser that displays the list of 
     * choices for the option.
     *
     * @var bool $add_list_option Whether to add a list option or not
     */
    public $add_list_option = false;

    // }}}
    // Private properties {{{

    /**
     * When an action is called remember it to allow for multiple calls.
     *
     * @var object $action_instance Placeholder for action
     */
    private $_action_instance = null;

    // }}}
    // __construct() {{{

    /**
     * Constructor.
     *
     * @param string $name   The name of the option
     * @param array  $params An optional array of parameters
     *
     * @return void
     */
    public function __construct($name = null, $params = array()) 
    {
        parent::__construct($name, $params);
        if ($this->action == 'Password') {
            // special case for Password action, password can be passed to the 
            // commandline or prompted by the parser
            $this->argument_optional = true;
        }
    }

    // }}}
    // toString() {{{

    /**
     * Returns the string representation of the option.
     *
     * @param string $delim Delimiter to use between short and long option
     *
     * @return string The string representation of the option
     * @todo use __toString() instead
     */
    public function toString($delim = ", ")
    {
        $ret     = '';
        $padding = '';
        if ($this->short_name != null) {
            $ret .= $this->short_name;
            if ($this->expectsArgument()) {
                $ret .= ' ' . $this->help_name;
            }
            $padding = $delim;
        }
        if ($this->long_name != null) {
            $ret .= $padding . $this->long_name;
            if ($this->expectsArgument()) {
                $ret .= '=' . $this->help_name;
            }
        }
        return $ret;
    }

    // }}}
    // expectsArgument() {{{

    /**
     * Returns true if the option requires one or more argument and false 
     * otherwise.
     *
     * @return bool Whether the option expects an argument or not
     */
    public function expectsArgument()
    {
        if ($this->action == 'StoreTrue' || $this->action == 'StoreFalse' ||
            $this->action == 'Help' || $this->action == 'Version' ||
            $this->action == 'Counter' || $this->action == 'List') {
            return false;
        }
        return true;
    }

    // }}}
    // dispatchAction() {{{

    /**
     * Formats the value $value according to the action of the option and 
     * updates the passed Console_CommandLine_Result object.
     *
     * @param mixed                      $value  The value to format
     * @param Console_CommandLine_Result $result The result instance
     * @param Console_CommandLine        $parser The parser instance
     *
     * @return void
     * @throws Console_CommandLine_Exception
     */
    public function dispatchAction($value, $result, $parser)
    {
        $actionInfo = Console_CommandLine::$actions[$this->action];
        if (true === $actionInfo[1]) {
            // we have a "builtin" action
            $tokens = explode('_', $actionInfo[0]);

        }
        $clsname = $actionInfo[0];
        if ($this->_action_instance === null) {
            $this->_action_instance  = new $clsname($result, $this, $parser);
        }

        // check value is in option choices
        if (!empty($this->choices) && !in_array($this->_action_instance->format($value), $this->choices)) {
            throw Console_CommandLine_Exception::factory(
                'OPTION_VALUE_NOT_VALID',
                array(
                    'name'    => $this->name,
                    'choices' => implode('", "', $this->choices),
                    'value'   => $value,
                ),
                $parser,
                $this->messages
            );
        }
        $this->_action_instance->execute($value, $this->action_params);
    }

    // }}}
    // validate() {{{

    /**
     * Validates the option instance.
     *
     * @return void
     * @throws Console_CommandLine_Exception
     * @todo use exceptions instead
     */
    public function validate()
    {
        // check if the option name is valid
        if (!preg_match('/^[a-zA-Z_\x7f-\xff]+[a-zA-Z0-9_\x7f-\xff]*$/',
            $this->name)) {
            Console_CommandLine::triggerError('option_bad_name',
                E_USER_ERROR, array('{$name}' => $this->name));
        }
        // call the parent validate method
        parent::validate();
        // a short_name or a long_name must be provided
        if ($this->short_name == null && $this->long_name == null) {
            Console_CommandLine::triggerError('option_long_and_short_name_missing',
                E_USER_ERROR, array('{$name}' => $this->name));
        }
        // check if the option short_name is valid
        if ($this->short_name != null && 
            !(preg_match('/^\-[a-zA-Z]{1}$/', $this->short_name))) {
            Console_CommandLine::triggerError('option_bad_short_name',
                E_USER_ERROR, array(
                    '{$name}' => $this->name, 
                    '{$short_name}' => $this->short_name
                ));
        }
        // check if the option long_name is valid
        if ($this->long_name != null && 
            !preg_match('/^\-\-[a-zA-Z]+[a-zA-Z0-9_\-]*$/', $this->long_name)) {
            Console_CommandLine::triggerError('option_bad_long_name',
                E_USER_ERROR, array(
                    '{$name}' => $this->name, 
                    '{$long_name}' => $this->long_name
                ));
        }
        // check if we have a valid action
        if (!is_string($this->action)) {
            Console_CommandLine::triggerError('option_bad_action',
                E_USER_ERROR, array('{$name}' => $this->name));
        }
        if (!isset(Console_CommandLine::$actions[$this->action])) {
            Console_CommandLine::triggerError('option_unregistered_action',
                E_USER_ERROR, array(
                    '{$action}' => $this->action,
                    '{$name}' => $this->name
                ));
        }
        // if the action is a callback, check that we have a valid callback
        if ($this->action == 'Callback' && !is_callable($this->callback)) {
            Console_CommandLine::triggerError('option_invalid_callback',
                E_USER_ERROR, array('{$name}' => $this->name));
        }
    }

    // }}}
    // setDefaults() {{{

    /**
     * Set the default value according to the configured action.
     *
     * Note that for backward compatibility issues this method is only called 
     * when the 'force_options_defaults' is set to true, it will become the
     * default behaviour in the next major release of Console_CommandLine.
     *
     * @return void
     */
    public function setDefaults()
    {
        if ($this->default !== null) {
            // already set
            return;
        }
        switch ($this->action) {
        case 'Counter':
        case 'StoreInt':
            $this->default = 0;
            break;
        case 'StoreFloat':
            $this->default = 0.0;
            break;
        case 'StoreArray':
            $this->default = array();
            break;
        case 'StoreTrue':
            $this->default = false;
            break;
        case 'StoreFalse':
            $this->default = true;
            break;
        default:
            return;
        }
    }

    // }}}
}


/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * This file is part of the PEAR Console_CommandLine package.
 *
 * PHP version 5
 *
 * LICENSE: This source file is subject to the MIT license that is available
 * through the world-wide-web at the following URI:
 * http://opensource.org/licenses/mit-license.
 *
 * @category  Console 
 * @package   Console_CommandLine
 * @author    David JEAN LOUIS <izimobil@gmail.com>
 * @copyright 2007 David JEAN LOUIS
 * @license   http://opensource.org/licenses/mit-license. MIT License 
 * @version   CVS: $Id$
 * @link      http://pear..net/package/Console_CommandLine
 * @since     File available since release 0.1.0
 * @filesource
 */

/**
 * File containing the parent class.
 */


/**
 * Class that represent a command with option and arguments.
 *
 * This class exist just to clarify the interface but at the moment it is 
 * strictly identical to Console_CommandLine class, it could change in the
 * future though.
 *
 * @category  Console
 * @package   Console_CommandLine
 * @author    David JEAN LOUIS <izimobil@gmail.com>
 * @copyright 2007 David JEAN LOUIS
 * @license   http://opensource.org/licenses/mit-license. MIT License 
 * @version   Release: 1.2.0
 * @link      http://pear..net/package/Console_CommandLine
 * @since     Class available since release 0.1.0
 */
class Console_CommandLine_Command extends Console_CommandLine
{
    // Public properties {{{

    /**
     * An array of aliases for the subcommand.
     *
     * @var array $aliases Aliases for the subcommand.
     */
    public $aliases = array();

    // }}}
    // __construct() {{{

    /**
     * Constructor.
     *
     * @param array  $params An optional array of parameters
     *
     * @return void
     */
    public function __construct($params = array()) 
    {
        if (isset($params['aliases'])) {
            $this->aliases = $params['aliases'];
        }
        parent::__construct($params);
    }

    // }}}
}


/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * This file is part of the PEAR Console_CommandLine package.
 *
 * PHP version 5
 *
 * LICENSE: This source file is subject to the MIT license that is available
 * through the world-wide-web at the following URI:
 * http://opensource.org/licenses/mit-license.
 *
 * @category  Console 
 * @package   Console_CommandLine
 * @author    David JEAN LOUIS <izimobil@gmail.com>
 * @copyright 2007 David JEAN LOUIS
 * @license   http://opensource.org/licenses/mit-license. MIT License 
 * @version   CVS: $Id$
 * @link      http://pear..net/package/Console_CommandLine
 * @since     File available since release 0.1.0
 * @filesource
 */

/**
 * Include the PEAR_Exception class
 */

/**
 * PEAR_Exception
 *
 * PHP version 5
 *
 * @category   pear
 * @package    PEAR_Exception
 * @author     Tomas V. V. Cox <cox@idecnet.com>
 * @author     Hans Lellelid <hans@velum.net>
 * @author     Bertrand Mansion <bmansion@mamasam.com>
 * @author     Greg Beaver <cellog@php.net>
 * @copyright  1997-2009 The Authors
 * @license    http://opensource.org/licenses/bsd-license.php New BSD License
 * @link       http://pear.php.net/package/PEAR_Exception
 * @since      File available since Release 1.0.0
 */


/**
 * Base PEAR_Exception Class
 *
 * 1) Features:
 *
 * - Nestable exceptions (throw new PEAR_Exception($msg, $prev_exception))
 * - Definable triggers, shot when exceptions occur
 * - Pretty and informative error messages
 * - Added more context info available (like class, method or cause)
 * - cause can be a PEAR_Exception or an array of mixed
 *   PEAR_Exceptions/PEAR_ErrorStack warnings
 * - callbacks for specific exception classes and their children
 *
 * 2) Ideas:
 *
 * - Maybe a way to define a 'template' for the output
 *
 * 3) Inherited properties from PHP Exception Class:
 *
 * protected $message
 * protected $code
 * protected $line
 * protected $file
 * private   $trace
 *
 * 4) Inherited methods from PHP Exception Class:
 *
 * __clone
 * __construct
 * getMessage
 * getCode
 * getFile
 * getLine
 * getTraceSafe
 * getTraceSafeAsString
 * __toString
 *
 * 5) Usage example
 *
 * <code>
 *  require_once 'PEAR/Exception.php';
 *
 *  class Test {
 *     function foo() {
 *         throw new PEAR_Exception('Error Message', ERROR_CODE);
 *     }
 *  }
 *
 *  function myLogger($pear_exception) {
 *     echo $pear_exception->getMessage();
 *  }
 *  // each time a exception is thrown the 'myLogger' will be called
 *  // (its use is completely optional)
 *  PEAR_Exception::addObserver('myLogger');
 *  $test = new Test;
 *  try {
 *     $test->foo();
 *  } catch (PEAR_Exception $e) {
 *     print $e;
 *  }
 * </code>
 *
 * @category   pear
 * @package    PEAR_Exception
 * @author     Tomas V.V.Cox <cox@idecnet.com>
 * @author     Hans Lellelid <hans@velum.net>
 * @author     Bertrand Mansion <bmansion@mamasam.com>
 * @author     Greg Beaver <cellog@php.net>
 * @copyright  1997-2009 The Authors
 * @license    http://opensource.org/licenses/bsd-license.php New BSD License
 * @version    Release: 1.0.0beta1
 * @link       http://pear.php.net/package/PEAR_Exception
 * @since      Class available since Release 1.0.0
 *
 */
class PEAR_Exception extends Exception
{
    const OBSERVER_PRINT = -2;
    const OBSERVER_TRIGGER = -4;
    const OBSERVER_DIE = -8;
    protected $cause;
    private static $_observers = array();
    private static $_uniqueid = 0;
    private $_trace;

    /**
     * Supported signatures:
     *  - PEAR_Exception(string $message);
     *  - PEAR_Exception(string $message, int $code);
     *  - PEAR_Exception(string $message, Exception $cause);
     *  - PEAR_Exception(string $message, Exception $cause, int $code);
     *  - PEAR_Exception(string $message, PEAR_Error $cause);
     *  - PEAR_Exception(string $message, PEAR_Error $cause, int $code);
     *  - PEAR_Exception(string $message, array $causes);
     *  - PEAR_Exception(string $message, array $causes, int $code);
     * @param string exception message
     * @param int|Exception|PEAR_Error|array|null exception cause
     * @param int|null exception code or null
     */
    public function __construct($message, $p2 = null, $p3 = null)
    {
        if (is_int($p2)) {
            $code = $p2;
            $this->cause = null;
        } elseif (is_object($p2) || is_array($p2)) {
            // using is_object allows both Exception and PEAR_Error
            if (is_object($p2) && !($p2 instanceof Exception)) {
                if (!class_exists('PEAR_Error') || !($p2 instanceof PEAR_Error)) {
                    throw new PEAR_Exception('exception cause must be Exception, ' .
                        'array, or PEAR_Error');
                }
            }
            $code = $p3;
            if (is_array($p2) && isset($p2['message'])) {
                // fix potential problem of passing in a single warning
                $p2 = array($p2);
            }
            $this->cause = $p2;
        } else {
            $code = null;
            $this->cause = null;
        }
        parent::__construct($message, $code);
        $this->signal();
    }

    /**
     * @param mixed $callback  - A valid php callback, see php func is_callable()
     *                         - A PEAR_Exception::OBSERVER_* constant
     *                         - An array(const PEAR_Exception::OBSERVER_*,
     *                           mixed $options)
     * @param string $label    The name of the observer. Use this if you want
     *                         to remove it later with removeObserver()
     */
    public static function addObserver($callback, $label = 'default')
    {
        self::$_observers[$label] = $callback;
    }

    public static function removeObserver($label = 'default')
    {
        unset(self::$_observers[$label]);
    }

    /**
     * @return int unique identifier for an observer
     */
    public static function getUniqueId()
    {
        return self::$_uniqueid++;
    }

    private function signal()
    {
        foreach (self::$_observers as $func) {
            if (is_callable($func)) {
                call_user_func($func, $this);
                continue;
            }
            settype($func, 'array');
            switch ($func[0]) {
                case self::OBSERVER_PRINT :
                    $f = (isset($func[1])) ? $func[1] : '%s';
                    printf($f, $this->getMessage());
                    break;
                case self::OBSERVER_TRIGGER :
                    $f = (isset($func[1])) ? $func[1] : E_USER_NOTICE;
                    trigger_error($this->getMessage(), $f);
                    break;
                case self::OBSERVER_DIE :
                    $f = (isset($func[1])) ? $func[1] : '%s';
                    die(printf($f, $this->getMessage()));
                    break;
                default:
                    trigger_error('invalid observer type', E_USER_WARNING);
            }
        }
    }

    /**
     * Return specific error information that can be used for more detailed
     * error messages or translation.
     *
     * This method may be overridden in child exception classes in order
     * to add functionality not present in PEAR_Exception and is a placeholder
     * to define API
     *
     * The returned array must be an associative array of parameter => value like so:
     * <pre>
     * array('name' => $name, 'context' => array(...))
     * </pre>
     * @return array
     */
    public function getErrorData()
    {
        return array();
    }

    /**
     * Returns the exception that caused this exception to be thrown
     * @access public
     * @return Exception|array The context of the exception
     */
    public function getCause()
    {
        return $this->cause;
    }

    /**
     * Function must be public to call on caused exceptions
     * @param array
     */
    public function getCauseMessage(&$causes)
    {
        $trace = $this->getTraceSafe();
        $cause = array('class'   => get_class($this),
                       'message' => $this->message,
                       'file' => 'unknown',
                       'line' => 'unknown');
        if (isset($trace[0])) {
            if (isset($trace[0]['file'])) {
                $cause['file'] = $trace[0]['file'];
                $cause['line'] = $trace[0]['line'];
            }
        }
        $causes[] = $cause;
        if ($this->cause instanceof PEAR_Exception) {
            $this->cause->getCauseMessage($causes);
        } elseif ($this->cause instanceof Exception) {
            $causes[] = array('class'   => get_class($this->cause),
                              'message' => $this->cause->getMessage(),
                              'file' => $this->cause->getFile(),
                              'line' => $this->cause->getLine());
        } elseif (class_exists('PEAR_Error') && $this->cause instanceof PEAR_Error) {
            $causes[] = array('class' => get_class($this->cause),
                              'message' => $this->cause->getMessage(),
                              'file' => 'unknown',
                              'line' => 'unknown');
        } elseif (is_array($this->cause)) {
            foreach ($this->cause as $cause) {
                if ($cause instanceof PEAR_Exception) {
                    $cause->getCauseMessage($causes);
                } elseif ($cause instanceof Exception) {
                    $causes[] = array('class'   => get_class($cause),
                                   'message' => $cause->getMessage(),
                                   'file' => $cause->getFile(),
                                   'line' => $cause->getLine());
                } elseif (class_exists('PEAR_Error') && $cause instanceof PEAR_Error) {
                    $causes[] = array('class' => get_class($cause),
                                      'message' => $cause->getMessage(),
                                      'file' => 'unknown',
                                      'line' => 'unknown');
                } elseif (is_array($cause) && isset($cause['message'])) {
                    // PEAR_ErrorStack warning
                    $causes[] = array(
                        'class' => $cause['package'],
                        'message' => $cause['message'],
                        'file' => isset($cause['context']['file']) ?
                                            $cause['context']['file'] :
                                            'unknown',
                        'line' => isset($cause['context']['line']) ?
                                            $cause['context']['line'] :
                                            'unknown',
                    );
                }
            }
        }
    }

    public function getTraceSafe()
    {
        if (!isset($this->_trace)) {
            $this->_trace = $this->getTrace();
            if (empty($this->_trace)) {
                $backtrace = debug_backtrace();
                $this->_trace = array($backtrace[count($backtrace)-1]);
            }
        }
        return $this->_trace;
    }

    public function getErrorClass()
    {
        $trace = $this->getTraceSafe();
        return $trace[0]['class'];
    }

    public function getErrorMethod()
    {
        $trace = $this->getTraceSafe();
        return $trace[0]['function'];
    }

    public function __toString()
    {
        if (isset($_SERVER['REQUEST_URI'])) {
            return $this->toHtml();
        }
        return $this->toText();
    }

    public function toHtml()
    {
        $trace = $this->getTraceSafe();
        $causes = array();
        $this->getCauseMessage($causes);
        $html =  '<table style="border: 1px" cellspacing="0">' . "\n";
        foreach ($causes as $i => $cause) {
            $html .= '<tr><td colspan="3" style="background: #ff9999">'
               . str_repeat('-', $i) . ' <b>' . $cause['class'] . '</b>: '
               . htmlspecialchars($cause['message']) . ' in <b>' . $cause['file'] . '</b> '
               . 'on line <b>' . $cause['line'] . '</b>'
               . "</td></tr>\n";
        }
        $html .= '<tr><td colspan="3" style="background-color: #aaaaaa; text-align: center; font-weight: bold;">Exception trace</td></tr>' . "\n"
               . '<tr><td style="text-align: center; background: #cccccc; width:20px; font-weight: bold;">#</td>'
               . '<td style="text-align: center; background: #cccccc; font-weight: bold;">Function</td>'
               . '<td style="text-align: center; background: #cccccc; font-weight: bold;">Location</td></tr>' . "\n";

        foreach ($trace as $k => $v) {
            $html .= '<tr><td style="text-align: center;">' . $k . '</td>'
                   . '<td>';
            if (!empty($v['class'])) {
                $html .= $v['class'] . $v['type'];
            }
            $html .= $v['function'];
            $args = array();
            if (!empty($v['args'])) {
                foreach ($v['args'] as $arg) {
                    if (is_null($arg)) $args[] = 'null';
                    elseif (is_array($arg)) $args[] = 'Array';
                    elseif (is_object($arg)) $args[] = 'Object('.get_class($arg).')';
                    elseif (is_bool($arg)) $args[] = $arg ? 'true' : 'false';
                    elseif (is_int($arg) || is_double($arg)) $args[] = $arg;
                    else {
                        $arg = (string)$arg;
                        $str = htmlspecialchars(substr($arg, 0, 16));
                        if (strlen($arg) > 16) $str .= '&hellip;';
                        $args[] = "'" . $str . "'";
                    }
                }
            }
            $html .= '(' . implode(', ',$args) . ')'
                   . '</td>'
                   . '<td>' . (isset($v['file']) ? $v['file'] : 'unknown')
                   . ':' . (isset($v['line']) ? $v['line'] : 'unknown')
                   . '</td></tr>' . "\n";
        }
        $html .= '<tr><td style="text-align: center;">' . ($k+1) . '</td>'
               . '<td>{main}</td>'
               . '<td>&nbsp;</td></tr>' . "\n"
               . '</table>';
        return $html;
    }

    public function toText()
    {
        $causes = array();
        $this->getCauseMessage($causes);
        $causeMsg = '';
        foreach ($causes as $i => $cause) {
            $causeMsg .= str_repeat(' ', $i) . $cause['class'] . ': '
                   . $cause['message'] . ' in ' . $cause['file']
                   . ' on line ' . $cause['line'] . "\n";
        }
        return $causeMsg . $this->getTraceAsString();
    }
}

/**
 * Interface for custom message provider.
 */


/**
 * Class for exceptions raised by the Console_CommandLine package.
 *
 * @category  Console
 * @package   Console_CommandLine
 * @author    David JEAN LOUIS <izimobil@gmail.com>
 * @copyright 2007 David JEAN LOUIS
 * @license   http://opensource.org/licenses/mit-license. MIT License 
 * @version   Release: 1.2.0
 * @link      http://pear..net/package/Console_CommandLine
 * @since     Class available since release 0.1.0
 */
class Console_CommandLine_Exception extends PEAR_Exception
{
    // Codes constants {{{

    /**#@+
     * Exception code constants.
     */
    const OPTION_VALUE_REQUIRED   = 1;
    const OPTION_VALUE_UNEXPECTED = 2;
    const OPTION_VALUE_TYPE_ERROR = 3;
    const OPTION_UNKNOWN          = 4;
    const ARGUMENT_REQUIRED       = 5;
    const INVALID_SUBCOMMAND      = 6;
    /**#@-*/

    // }}}
    // factory() {{{

    /**
     * Convenience method that builds the exception with the array of params by
     * calling the message provider class.
     *
     * @param string              $code     The string identifier of the
     *                                      exception.
     * @param array               $params   Array of template vars/values
     * @param Console_CommandLine $parser   An instance of the parser
     * @param array               $messages An optional array of messages
     *                                      passed to the message provider.
     *
     * @return object an instance of Console_CommandLine_Exception
     */
    public static function factory(
        $code, $params, $parser, array $messages = array()
    ) {
        $provider = $parser->message_provider;
        if ($provider instanceof Console_CommandLine_CustomMessageProvider) {
            $msg = $provider->getWithCustomMessages(
                $code,
                $params,
                $messages
            );
        } else {
            $msg = $provider->get($code, $params);
        }
        $const = 'Console_CommandLine_Exception::' . $code;
        $code  = defined($const) ? constant($const) : 0;
        return new Console_CommandLine_Exception($msg, $code);
    }

    // }}}
}


/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * This file is part of the PEAR Console_CommandLine package.
 *
 * PHP version 5
 *
 * LICENSE: This source file is subject to the MIT license that is available
 * through the world-wide-web at the following URI:
 * http://opensource.org/licenses/mit-license.
 *
 * @category  Console
 * @package   Console_CommandLine
 * @author    David JEAN LOUIS <izimobil@gmail.com>
 * @copyright 2007 David JEAN LOUIS
 * @license   http://opensource.org/licenses/mit-license. MIT License
 * @version   CVS: $Id$
 * @link      http://pear..net/package/Console_CommandLine
 * @since     File available since release 0.1.0
 * @filesource
 */

/**
 * Required file
 */


/**
 * Parser for command line xml definitions.
 *
 * @category  Console
 * @package   Console_CommandLine
 * @author    David JEAN LOUIS <izimobil@gmail.com>
 * @copyright 2007 David JEAN LOUIS
 * @license   http://opensource.org/licenses/mit-license. MIT License
 * @version   Release: @package_version@
 * @link      http://pear..net/package/Console_CommandLine
 * @since     Class available since release 0.1.0
 */
class Console_CommandLine_XmlParser
{
    // parse() {{{

    /**
     * Parses the given xml definition file and returns a
     * Console_CommandLine instance constructed with the xml data.
     *
     * @param string $xmlfile The xml file to parse
     *
     * @return Console_CommandLine A parser instance
     */
    public static function parse($xmlfile)
    {
        if (!is_readable($xmlfile)) {
            Console_CommandLine::triggerError('invalid_xml_file',
                E_USER_ERROR, array('{$file}' => $xmlfile));
        }
        $doc = new DomDocument();
        $doc->load($xmlfile);
        self::validate($doc);
        $nodes = $doc->getElementsByTagName('command');
        $root  = $nodes->item(0);
        return self::_parseCommandNode($root, true);
    }

    // }}}
    // parseString() {{{

    /**
     * Parses the given xml definition string and returns a
     * Console_CommandLine instance constructed with the xml data.
     *
     * @param string $xmlstr The xml string to parse
     *
     * @return Console_CommandLine A parser instance
     */
    public static function parseString($xmlstr)
    {
        $doc = new DomDocument();
        $doc->loadXml($xmlstr);
        self::validate($doc);
        $nodes = $doc->getElementsByTagName('command');
        $root  = $nodes->item(0);
        return self::_parseCommandNode($root, true);
    }

    // }}}
    // validate() {{{

    /**
     * Validates the xml definition using Relax NG.
     *
     * @param DomDocument $doc The document to validate
     *
     * @return boolean Whether the xml data is valid or not.
     * @throws Console_CommandLine_Exception
     * @todo use exceptions
     */
    public static function validate($doc)
    {
        if (is_dir('@data_dir@' . DIRECTORY_SEPARATOR . 'Console_CommandLine')) {
            $rngfile = '@data_dir@' . DIRECTORY_SEPARATOR
                . 'Console_CommandLine' . DIRECTORY_SEPARATOR . 'data'
                . DIRECTORY_SEPARATOR . 'xmlschema.rng';
        } else {
            $rngfile = dirname(__FILE__) . DIRECTORY_SEPARATOR . '..'
                . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'data'
                . DIRECTORY_SEPARATOR . 'xmlschema.rng';
        }
        if (!is_readable($rngfile)) {
            Console_CommandLine::triggerError('invalid_xml_file',
                E_USER_ERROR, array('{$file}' => $rngfile));
        }
        return $doc->relaxNGValidate($rngfile);
    }

    // }}}
    // _parseCommandNode() {{{

    /**
     * Parses the root command node or a command node and returns the
     * constructed Console_CommandLine or Console_CommandLine_Command instance.
     *
     * @param DomDocumentNode $node       The node to parse
     * @param bool            $isRootNode Whether it is a root node or not
     *
     * @return mixed Console_CommandLine or Console_CommandLine_Command
     */
    private static function _parseCommandNode($node, $isRootNode = false)
    {
        if ($isRootNode) {
            $obj = new Console_CommandLine();
        } else {

            $obj = new Console_CommandLine_Command();
        }
        foreach ($node->childNodes as $cNode) {
            $cNodeName = $cNode->nodeName;
            switch ($cNodeName) {
            case 'name':
            case 'description':
            case 'version':
                $obj->$cNodeName = trim($cNode->nodeValue);
                break;
            case 'add_help_option':
            case 'add_version_option':
            case 'force_posix':
                $obj->$cNodeName = self::_bool(trim($cNode->nodeValue));
                break;
            case 'option':
                $obj->addOption(self::_parseOptionNode($cNode));
                break;
            case 'argument':
                $obj->addArgument(self::_parseArgumentNode($cNode));
                break;
            case 'command':
                $obj->addCommand(self::_parseCommandNode($cNode));
                break;
            case 'aliases':
                if (!$isRootNode) {
                    foreach ($cNode->childNodes as $subChildNode) {
                        if ($subChildNode->nodeName == 'alias') {
                            $obj->aliases[] = trim($subChildNode->nodeValue);
                        }
                    }
                }
                break;
            case 'messages':
                $obj->messages = self::_messages($cNode);
                break;
            default:
                break;
            }
        }
        return $obj;
    }

    // }}}
    // _parseOptionNode() {{{

    /**
     * Parses an option node and returns the constructed
     * Console_CommandLine_Option instance.
     *
     * @param DomDocumentNode $node The node to parse
     *
     * @return Console_CommandLine_Option The built option
     */
    private static function _parseOptionNode($node)
    {

        $obj = new Console_CommandLine_Option($node->getAttribute('name'));
        foreach ($node->childNodes as $cNode) {
            $cNodeName = $cNode->nodeName;
            switch ($cNodeName) {
            case 'choices':
                foreach ($cNode->childNodes as $subChildNode) {
                    if ($subChildNode->nodeName == 'choice') {
                        $obj->choices[] = trim($subChildNode->nodeValue);
                    }
                }
                break;
            case 'messages':
                $obj->messages = self::_messages($cNode);
                break;
            default:
                if (property_exists($obj, $cNodeName)) {
                    $obj->$cNodeName = trim($cNode->nodeValue);
                }
                break;
            }
        }
        if ($obj->action == 'Password') {
            $obj->argument_optional = true;
        }
        return $obj;
    }

    // }}}
    // _parseArgumentNode() {{{

    /**
     * Parses an argument node and returns the constructed
     * Console_CommandLine_Argument instance.
     *
     * @param DomDocumentNode $node The node to parse
     *
     * @return Console_CommandLine_Argument The built argument
     */
    private static function _parseArgumentNode($node)
    {

        $obj = new Console_CommandLine_Argument($node->getAttribute('name'));
        foreach ($node->childNodes as $cNode) {
            $cNodeName = $cNode->nodeName;
            switch ($cNodeName) {
            case 'description':
            case 'help_name':
            case 'default':
                $obj->$cNodeName = trim($cNode->nodeValue);
                break;
            case 'multiple':
                $obj->multiple = self::_bool(trim($cNode->nodeValue));
                break;
            case 'optional':
                $obj->optional = self::_bool(trim($cNode->nodeValue));
                break;
            case 'choices':
                foreach ($cNode->childNodes as $subChildNode) {
                    if ($subChildNode->nodeName == 'choice') {
                        $obj->choices[] = trim($subChildNode->nodeValue);
                    }
                }
                break;
            case 'messages':
                $obj->messages = self::_messages($cNode);
                break;
            default:
                break;
            }
        }
        return $obj;
    }

    // }}}
    // _bool() {{{

    /**
     * Returns a boolean according to true/false possible strings.
     *
     * @param string $str The string to process
     *
     * @return boolean
     */
    private static function _bool($str)
    {
        return in_array(strtolower((string)$str), array('true', '1', 'on', 'yes'));
    }

    // }}}
    // _messages() {{{

    /**
     * Returns an array of custom messages for the element
     *
     * @param DOMNode $node The messages node to process
     *
     * @return array an array of messages
     *
     * @see Console_CommandLine::$messages
     * @see Console_CommandLine_Element::$messages
     */
    private static function _messages(DOMNode $node)
    {
        $messages = array();

        foreach ($node->childNodes as $cNode) {
            if ($cNode->nodeType == XML_ELEMENT_NODE) {
                $name  = $cNode->getAttribute('name');
                $value = trim($cNode->nodeValue);

                $messages[$name] = $value;
            }
        }

        return $messages;
    }

    // }}}
}


/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * This file is part of the PEAR Console_CommandLine package.
 *
 * PHP version 5
 *
 * LICENSE: This source file is subject to the MIT license that is available
 * through the world-wide-web at the following URI:
 * http://opensource.org/licenses/mit-license.
 *
 * @category  Console 
 * @package   Console_CommandLine
 * @author    David JEAN LOUIS <izimobil@gmail.com>
 * @copyright 2007 David JEAN LOUIS
 * @license   http://opensource.org/licenses/mit-license. MIT License 
 * @version   CVS: $Id$
 * @link      http://pear..net/package/Console_CommandLine
 * @since     File available since release 0.1.0
 * @filesource
 */

/**
 * Renderers common interface, all renderers must implement this interface.
 *
 * @category  Console
 * @package   Console_CommandLine
 * @author    David JEAN LOUIS <izimobil@gmail.com>
 * @copyright 2007 David JEAN LOUIS
 * @license   http://opensource.org/licenses/mit-license. MIT License 
 * @version   Release: 1.2.0
 * @link      http://pear..net/package/Console_CommandLine
 * @since     Class available since release 0.1.0
 */
interface Console_CommandLine_Renderer
{
    // usage() {{{

    /**
     * Returns the full usage message.
     *
     * @return string The usage message
     */
    public function usage();

    // }}}
    // error() {{{

    /**
     * Returns a formatted error message.
     *
     * @param string $error The error message to format
     *
     * @return string The error string
     */
    public function error($error);

    // }}}
    // version() {{{

    /**
     * Returns the program version string.
     *
     * @return string The version string
     */
    public function version();

    // }}}
}


/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * This file is part of the PEAR Console_CommandLine package.
 *
 * A full featured package for managing command-line options and arguments 
 * hightly inspired from python optparse module, it allows the developper to 
 * easily build complex command line interfaces.
 *
 * PHP version 5
 *
 * LICENSE: This source file is subject to the MIT license that is available
 * through the world-wide-web at the following URI:
 * http://opensource.org/licenses/mit-license.
 *
 * @category  Console 
 * @package   Console_CommandLine
 * @author    David JEAN LOUIS <izimobil@gmail.com>
 * @copyright 2007 David JEAN LOUIS
 * @license   http://opensource.org/licenses/mit-license. MIT License 
 * @version   CVS: $Id$
 * @link      http://pear..net/package/Console_CommandLine
 * @since     Class available since release 0.1.0
 * @filesource
 */

/**
 * Required unconditionally
 */





/**
 * Main class for parsing command line options and arguments.
 * 
 * There are three ways to create parsers with this class:
 * <code>
 * // direct usage
 * $parser = new Console_CommandLine();
 *
 * // with an xml definition file
 * $parser = Console_CommandLine::fromXmlFile('path/to/file.xml');
 *
 * // with an xml definition string
 * $validXmlString = '..your xml string...';
 * $parser = Console_CommandLine::fromXmlString($validXmlString);
 * </code>
 *
 * @category  Console
 * @package   Console_CommandLine
 * @author    David JEAN LOUIS <izimobil@gmail.com>
 * @copyright 2007 David JEAN LOUIS
 * @license   http://opensource.org/licenses/mit-license. MIT License 
 * @version   Release: 1.2.0
 * @link      http://pear..net/package/Console_CommandLine
 * @since     File available since release 0.1.0
 * @example   docs/examples/ex1.
 * @example   docs/examples/ex2.
 */
class Console_CommandLine
{
    // Public properties {{{

    /**
     * Error messages.
     *
     * @var array $errors Error messages
     * @todo move this to Console_CommandLine_MessageProvider
     */
    public static $errors = array(
        'option_bad_name'                    => 'option name must be a valid  variable name (got: {$name})',
        'argument_bad_name'                  => 'argument name must be a valid  variable name (got: {$name})',
        'argument_no_default'                => 'only optional arguments can have a default value',
        'option_long_and_short_name_missing' => 'you must provide at least an option short name or long name for option "{$name}"',
        'option_bad_short_name'              => 'option "{$name}" short name must be a dash followed by a letter (got: "{$short_name}")',
        'option_bad_long_name'               => 'option "{$name}" long name must be 2 dashes followed by a word (got: "{$long_name}")',
        'option_unregistered_action'         => 'unregistered action "{$action}" for option "{$name}".',
        'option_bad_action'                  => 'invalid action for option "{$name}".',
        'option_invalid_callback'            => 'you must provide a valid callback for option "{$name}"',
        'action_class_does_not_exists'       => 'action "{$name}" class "{$class}" not found, make sure that your class is available before calling Console_CommandLine::registerAction()',
        'invalid_xml_file'                   => 'XML definition file "{$file}" does not exists or is not readable',
        'invalid_rng_file'                   => 'RNG file "{$file}" does not exists or is not readable'
    );

    /**
     * The name of the program, if not given it defaults to argv[0].
     *
     * @var string $name Name of your program
     */
    public $name;

    /**
     * A description text that will be displayed in the help message.
     *
     * @var string $description Description of your program
     */
    public $description = '';

    /**
     * A string that represents the version of the program, if this property is 
     * not empty and property add_version_option is not set to false, the
     * command line parser will add a --version option, that will display the
     * property content.
     *
     * @var    string $version
     * @access public
     */
    public $version = '';

    /**
     * Boolean that determine if the command line parser should add the help
     * (-h, --help) option automatically.
     *
     * @var bool $add_help_option Whether to add a help option or not
     */
    public $add_help_option = true;

    /**
     * Boolean that determine if the command line parser should add the version
     * (-v, --version) option automatically.
     * Note that the version option is also generated only if the version 
     * property is not empty, it's up to you to provide a version string of 
     * course.
     *
     * @var bool $add_version_option Whether to add a version option or not
     */
    public $add_version_option = true;

    /**
     * Boolean that determine if providing a subcommand is mandatory.
     *
     * @var bool $subcommand_required Whether a subcommand is required or not
     */
    public $subcommand_required = false;

    /**
     * The command line parser renderer instance.
     *
     * @var    object that implements Console_CommandLine_Renderer interface
     * @access protected
     */
    public $renderer = false;

    /**
     * The command line parser outputter instance.
     *
     * @var Console_CommandLine_Outputter An outputter
     */
    public $outputter = false;

    /**
     * The command line message provider instance.
     *
     * @var Console_CommandLine_MessageProvider A message provider instance
     */
    public $message_provider = false;

    /**
     * Boolean that tells the parser to be POSIX compliant, POSIX demands the 
     * following behavior: the first non-option stops option processing.
     *
     * @var bool $force_posix Whether to force posix compliance or not
     */
    public $force_posix = false;

    /**
     * Boolean that tells the parser to set relevant options default values, 
     * according to the option action.
     *
     * @see Console_CommandLine_Option::setDefaults()
     * @var bool $force_options_defaults Whether to force option default values
     */
    public $force_options_defaults = false;

    /**
     * An array of Console_CommandLine_Option objects.
     *
     * @var array $options The options array
     */
    public $options = array();

    /**
     * An array of Console_CommandLine_Argument objects.
     *
     * @var array $args The arguments array
     */
    public $args = array();

    /**
     * An array of Console_CommandLine_Command objects (sub commands).
     *
     * @var array $commands The commands array
     */
    public $commands = array();

    /**
     * Parent, only relevant in Command objects but left here for interface 
     * convenience.
     *
     * @var Console_CommandLine The parent instance
     * @todo move Console_CommandLine::parent to Console_CommandLine_Command
     */
    public $parent = false;

    /**
     * Array of valid actions for an option, this array will also store user 
     * registered actions.
     *
     * The array format is:
     * <pre>
     * array(
     *     <ActionName:string> => array(<ActionClass:string>, <builtin:bool>)
     * )
     * </pre>
     *
     * @var array $actions List of valid actions
     */
    public static $actions = array(
        'StoreTrue'   => array('Console_CommandLine_Action_StoreTrue', true),
        'StoreFalse'  => array('Console_CommandLine_Action_StoreFalse', true),
        'StoreString' => array('Console_CommandLine_Action_StoreString', true),
        'StoreInt'    => array('Console_CommandLine_Action_StoreInt', true),
        'StoreFloat'  => array('Console_CommandLine_Action_StoreFloat', true),
        'StoreArray'  => array('Console_CommandLine_Action_StoreArray', true),
        'Callback'    => array('Console_CommandLine_Action_Callback', true),
        'Counter'     => array('Console_CommandLine_Action_Counter', true),
        'Help'        => array('Console_CommandLine_Action_Help', true),
        'Version'     => array('Console_CommandLine_Action_Version', true),
        'Password'    => array('Console_CommandLine_Action_Password', true),
        'List'        => array('Console_CommandLine_Action_List', true),
    );

    /**
     * Custom errors messages for this command
     *
     * This array is of the form:
     * <code>
     * 
     * array(
     *     $messageName => $messageText,
     *     $messageName => $messageText,
     *     ...
     * );
     * ?>
     * </code>
     *
     * If specified, these messages override the messages provided by the
     * default message provider. For example:
     * <code>
     * 
     * $messages = array(
     *     'ARGUMENT_REQUIRED' => 'The argument foo is required.',
     * );
     * ?>
     * </code>
     *
     * @var array
     * @see Console_CommandLine_MessageProvider_Default
     */
    public $messages = array();

    // }}}
    // {{{ Private properties

    /**
     * Array of options that must be dispatched at the end.
     *
     * @var array $_dispatchLater Options to be dispatched
     */
    private $_dispatchLater = array();

    // }}}
    // __construct() {{{

    /**
     * Constructor.
     * Example:
     *
     * <code>
     * $parser = new Console_CommandLine(array(
     *     'name'               => 'yourprogram', // defaults to argv[0]
     *     'description'        => 'Description of your program',
     *     'version'            => '0.0.1', // your program version
     *     'add_help_option'    => true, // or false to disable --help option
     *     'add_version_option' => true, // or false to disable --version option
     *     'force_posix'        => false // or true to force posix compliance
     * ));
     * </code>
     *
     * @param array $params An optional array of parameters
     *
     * @return void
     */
    public function __construct(array $params = array()) 
    {
        if (isset($params['name'])) {
            $this->name = $params['name'];
        } else if (isset($argv) && count($argv) > 0) {
            $this->name = $argv[0];
        } else if (isset($_SERVER['argv']) && count($_SERVER['argv']) > 0) {
            $this->name = $_SERVER['argv'][0];
        } else if (isset($_SERVER['SCRIPT_NAME'])) {
            $this->name = basename($_SERVER['SCRIPT_NAME']);
        }
        if (isset($params['description'])) {
            $this->description = $params['description'];
        }
        if (isset($params['version'])) {
            $this->version = $params['version'];
        }
        if (isset($params['add_version_option'])) {
            $this->add_version_option = $params['add_version_option'];
        }
        if (isset($params['add_help_option'])) {
            $this->add_help_option = $params['add_help_option'];
        }
        if (isset($params['subcommand_required'])) {
            $this->subcommand_required = $params['subcommand_required'];
        }
        if (isset($params['force_posix'])) {
            $this->force_posix = $params['force_posix'];
        } else if (getenv('POSIXLY_CORRECT')) {
            $this->force_posix = true;
        }
        if (isset($params['messages']) && is_array($params['messages'])) {
            $this->messages = $params['messages'];
        }
        // set default instances
        $this->renderer         = new Console_CommandLine_Renderer_Default($this);
        $this->outputter        = new Console_CommandLine_Outputter_Default();
        $this->message_provider = new Console_CommandLine_MessageProvider_Default();
    }

    // }}}
    // accept() {{{

    /**
     * Method to allow Console_CommandLine to accept either:
     *  + a custom renderer, 
     *  + a custom outputter,
     *  + or a custom message provider
     *
     * @param mixed $instance The custom instance
     *
     * @return void
     * @throws Console_CommandLine_Exception if wrong argument passed
     */
    public function accept($instance) 
    {
        if ($instance instanceof Console_CommandLine_Renderer) {
            if (property_exists($instance, 'parser') && !$instance->parser) {
                $instance->parser = $this;
            }
            $this->renderer = $instance;
        } else if ($instance instanceof Console_CommandLine_Outputter) {
            $this->outputter = $instance;
        } else if ($instance instanceof Console_CommandLine_MessageProvider) {
            $this->message_provider = $instance;
        } else {
            throw Console_CommandLine_Exception::factory(
                'INVALID_CUSTOM_INSTANCE',
                array(),
                $this,
                $this->messages
            );
        }
    }

    // }}}
    // fromXmlFile() {{{

    /**
     * Returns a command line parser instance built from an xml file.
     *
     * Example:
     * <code>
     * require_once 'Console/CommandLine.';
     * $parser = Console_CommandLine::fromXmlFile('path/to/file.xml');
     * $result = $parser->parse();
     * </code>
     *
     * @param string $file Path to the xml file
     *
     * @return Console_CommandLine The parser instance
     */
    public static function fromXmlFile($file) 
    {

        return Console_CommandLine_XmlParser::parse($file);
    }

    // }}}
    // fromXmlString() {{{

    /**
     * Returns a command line parser instance built from an xml string.
     *
     * Example:
     * <code>
     * require_once 'Console/CommandLine.';
     * $xmldata = 'xml version="1.0" encoding="utf-8" standalone="yes"?>
     * <command>
     *   <description>Compress files</description>
     *   <option name="quiet">
     *     <short_name>-q</short_name>
     *     <long_name>--quiet</long_name>
     *     <description>be quiet when run</description>
     *     <action>StoreTrue/action>
     *   </option>
     *   <argument name="files">
     *     <description>a list of files</description>
     *     <multiple>true</multiple>
     *   </argument>
     * </command>';
     * $parser = Console_CommandLine::fromXmlString($xmldata);
     * $result = $parser->parse();
     * </code>
     *
     * @param string $string The xml data
     *
     * @return Console_CommandLine The parser instance
     */
    public static function fromXmlString($string) 
    {

        return Console_CommandLine_XmlParser::parseString($string);
    }

    // }}}
    // addArgument() {{{

    /**
     * Adds an argument to the command line parser and returns it.
     *
     * Adds an argument with the name $name and set its attributes with the
     * array $params, then return the Console_CommandLine_Argument instance
     * created.
     * The method accepts another form: you can directly pass a 
     * Console_CommandLine_Argument object as the sole argument, this allows
     * you to contruct the argument separately, in order to reuse it in
     * different command line parsers or commands for example.
     *
     * Example:
     * <code>
     * $parser = new Console_CommandLine();
     * // add an array argument
     * $parser->addArgument('input_files', array('multiple'=>true));
     * // add a simple argument
     * $parser->addArgument('output_file');
     * $result = $parser->parse();
     * print_r($result->args['input_files']);
     * print_r($result->args['output_file']);
     * // will print:
     * // array('file1', 'file2')
     * // 'file3'
     * // if the command line was:
     * // myscript. file1 file2 file3
     * </code>
     *
     * In a terminal, the help will be displayed like this:
     * <code>
     * $ myscript. install -h
     * Usage: myscript. <input_files...> <output_file>
     * </code>
     *
     * @param mixed $name   A string containing the argument name or an
     *                      instance of Console_CommandLine_Argument
     * @param array $params An array containing the argument attributes
     *
     * @return Console_CommandLine_Argument the added argument
     * @see Console_CommandLine_Argument
     */
    public function addArgument($name, $params = array())
    {
        if ($name instanceof Console_CommandLine_Argument) {
            $argument = $name;
        } else {

            $argument = new Console_CommandLine_Argument($name, $params);
        }
        $argument->validate();
        $this->args[$argument->name] = $argument;
        return $argument;
    }

    // }}}
    // addCommand() {{{

    /**
     * Adds a sub-command to the command line parser.
     *
     * Adds a command with the given $name to the parser and returns the 
     * Console_CommandLine_Command instance, you can then populate the command
     * with options, configure it, etc... like you would do for the main parser
     * because the class Console_CommandLine_Command inherits from
     * Console_CommandLine.
     *
     * An example:
     * <code>
     * $parser = new Console_CommandLine();
     * $install_cmd = $parser->addCommand('install');
     * $install_cmd->addOption(
     *     'verbose',
     *     array(
     *         'short_name'  => '-v',
     *         'long_name'   => '--verbose',
     *         'description' => 'be noisy when installing stuff',
     *         'action'      => 'StoreTrue'
     *      )
     * );
     * $parser->parse();
     * </code>
     * Then in a terminal:
     * <code>
     * $ myscript. install -h
     * Usage: myscript. install [options]
     *
     * Options:
     *   -h, --help     display this help message and exit
     *   -v, --verbose  be noisy when installing stuff
     *
     * $ myscript. install --verbose
     * Installing whatever...
     * $
     * </code>
     *
     * @param mixed $name   A string containing the command name or an
     *                      instance of Console_CommandLine_Command
     * @param array $params An array containing the command attributes
     *
     * @return Console_CommandLine_Command the added subcommand
     * @see    Console_CommandLine_Command
     */
    public function addCommand($name, $params = array())
    {
        if ($name instanceof Console_CommandLine_Command) {
            $command = $name;
        } else {

            $params['name'] = $name;
            $command        = new Console_CommandLine_Command($params);
            // some properties must cascade to the child command if not 
            // passed explicitely. This is done only in this case, because if 
            // we have a Command object we have no way to determine if theses 
            // properties have already been set
            $cascade = array(
                'add_help_option',
                'add_version_option',
                'outputter',
                'message_provider',
                'force_posix',
                'force_options_defaults'
            );
            foreach ($cascade as $property) {
                if (!isset($params[$property])) {
                    $command->$property = $this->$property;
                }
            }
            if (!isset($params['renderer'])) {
                $renderer          = clone $this->renderer;
                $renderer->parser  = $command;
                $command->renderer = $renderer;
            }
        }
        $command->parent = $this;
        $this->commands[$command->name] = $command;
        return $command;
    }

    // }}}
    // addOption() {{{

    /**
     * Adds an option to the command line parser and returns it.
     *
     * Adds an option with the name $name and set its attributes with the
     * array $params, then return the Console_CommandLine_Option instance
     * created.
     * The method accepts another form: you can directly pass a 
     * Console_CommandLine_Option object as the sole argument, this allows
     * you to contruct the option separately, in order to reuse it in different
     * command line parsers or commands for example.
     *
     * Example:
     * <code>
     * $parser = new Console_CommandLine();
     * $parser->addOption('path', array(
     *     'short_name'  => '-p',  // a short name
     *     'long_name'   => '--path', // a long name
     *     'description' => 'path to the dir', // a description msg
     *     'action'      => 'StoreString',
     *     'default'     => '/tmp' // a default value
     * ));
     * $parser->parse();
     * </code>
     *
     * In a terminal, the help will be displayed like this:
     * <code>
     * $ myscript. --help
     * Usage: myscript. [options]
     *
     * Options:
     *   -h, --help  display this help message and exit
     *   -p, --path  path to the dir
     *
     * </code>
     *
     * Various methods to specify an option, these 3 commands are equivalent:
     * <code>
     * $ myscript. --path=some/path
     * $ myscript. -p some/path
     * $ myscript. -psome/path
     * </code>
     *
     * @param mixed $name   A string containing the option name or an
     *                      instance of Console_CommandLine_Option
     * @param array $params An array containing the option attributes
     *
     * @return Console_CommandLine_Option The added option
     * @see    Console_CommandLine_Option
     */
    public function addOption($name, $params = array())
    {
        if ($name instanceof Console_CommandLine_Option) {
            $opt = $name;
        } else {
            $opt = new Console_CommandLine_Option($name, $params);
        }
        $opt->validate();
        if ($this->force_options_defaults) {
            $opt->setDefaults();
        }
        $this->options[$opt->name] = $opt;
        if (!empty($opt->choices) && $opt->add_list_option) {
            $this->addOption('list_' . $opt->name, array(
                'long_name'     => '--list-' . $opt->name,
                'description'   => $this->message_provider->get(
                    'LIST_OPTION_MESSAGE',
                    array('name' => $opt->name)
                ),
                'action'        => 'List',
                'action_params' => array('list' => $opt->choices),
            ));
        }
        return $opt;
    }

    // }}}
    // displayError() {{{

    /**
     * Displays an error to the user via stderr and exit with $exitCode if its
     * value is not equals to false.
     *
     * @param string $error    The error message
     * @param int    $exitCode The exit code number (default: 1). If set to
     *                         false, the exit() function will not be called
     *
     * @return void
     */
    public function displayError($error, $exitCode = 1)
    {
        $this->outputter->stderr($this->renderer->error($error));
        if ($exitCode !== false) {
            exit($exitCode);
        }
    }

    // }}}
    // displayUsage() {{{

    /**
     * Displays the usage help message to the user via stdout and exit with
     * $exitCode if its value is not equals to false.
     *
     * @param int $exitCode The exit code number (default: 0). If set to
     *                      false, the exit() function will not be called
     *
     * @return void
     */
    public function displayUsage($exitCode = 0)
    {
        $this->outputter->stdout($this->renderer->usage());
        if ($exitCode !== false) {
            exit($exitCode);
        }
    }

    // }}}
    // displayVersion() {{{

    /**
     * Displays the program version to the user via stdout and exit with
     * $exitCode if its value is not equals to false.
     *
     *
     * @param int $exitCode The exit code number (default: 0). If set to
     *                      false, the exit() function will not be called
     *
     * @return void
     */
    public function displayVersion($exitCode = 0)
    {
        $this->outputter->stdout($this->renderer->version());
        if ($exitCode !== false) {
            exit($exitCode);
        }
    }

    // }}}
    // findOption() {{{

    /**
     * Finds the option that matches the given short_name (ex: -v), long_name
     * (ex: --verbose) or name (ex: verbose).
     *
     * @param string $str The option identifier
     *
     * @return mixed A Console_CommandLine_Option instance or false
     */
    public function findOption($str)
    {
        $str = trim($str);
        if ($str === '') {
            return false;
        }
        $matches = array();
        foreach ($this->options as $opt) {
            if ($opt->short_name == $str || $opt->long_name == $str ||
                $opt->name == $str) {
                // exact match
                return $opt;
            }
            if (substr($opt->long_name, 0, strlen($str)) === $str) {
                // abbreviated long option
                $matches[] = $opt;
            }
        }
        if ($count = count($matches)) {
            if ($count > 1) {
                $matches_str = '';
                $padding     = '';
                foreach ($matches as $opt) {
                    $matches_str .= $padding . $opt->long_name;
                    $padding      = ', ';
                }
                throw Console_CommandLine_Exception::factory(
                    'OPTION_AMBIGUOUS',
                    array('name' => $str, 'matches' => $matches_str),
                    $this,
                    $this->messages
                );
            }
            return $matches[0];
        }
        return false;
    }
    // }}}
    // registerAction() {{{

    /**
     * Registers a custom action for the parser, an example:
     *
     * <code>
     *
     * // in this example we create a "range" action:
     * // the user will be able to enter something like:
     * // $ <program> -r 1,5
     * // and in the result we will have:
     * // $result->options['range']: array(1, 5)
     *
     * require_once 'Console/CommandLine.';
     * require_once 'Console/CommandLine/Action.';
     *
     * class ActionRange extends Console_CommandLine_Action
     * {
     *     public function execute($value=false, $params=array())
     *     {
     *         $range = explode(',', str_replace(' ', '', $value));
     *         if (count($range) != 2) {
     *             throw new Exception(sprintf(
     *                 'Option "%s" must be 2 integers separated by a comma',
     *                 $this->option->name
     *             ));
     *         }
     *         $this->setResult($range);
     *     }
     * }
     * // then we can register our action
     * Console_CommandLine::registerAction('Range', 'ActionRange');
     * // and now our action is available !
     * $parser = new Console_CommandLine();
     * $parser->addOption('range', array(
     *     'short_name'  => '-r',
     *     'long_name'   => '--range',
     *     'action'      => 'Range', // note our custom action
     *     'description' => 'A range of two integers separated by a comma'
     * ));
     * // etc...
     *
     * </code>
     *
     * @param string $name  The name of the custom action
     * @param string $class The class name of the custom action
     *
     * @return void
     */
    public static function registerAction($name, $class) 
    {
        if (!isset(self::$actions[$name])) {
            if (!class_exists($class)) {
                self::triggerError('action_class_does_not_exists',
                    E_USER_ERROR,
                    array('{$name}' => $name, '{$class}' => $class));
            }
            self::$actions[$name] = array($class, false);
        }
    }

    // }}}
    // triggerError() {{{

    /**
     * A wrapper for programming errors triggering.
     *
     * @param string $msgId  Identifier of the message
     * @param int    $level  The  error level
     * @param array  $params An array of search=>replaces entries
     *
     * @return void
     * @todo remove Console::triggerError() and use exceptions only
     */
    public static function triggerError($msgId, $level, $params=array()) 
    {
        if (isset(self::$errors[$msgId])) {
            $msg = str_replace(array_keys($params),
                array_values($params), self::$errors[$msgId]); 
            trigger_error($msg, $level);
        } else {
            trigger_error('unknown error', $level);
        }
    }

    // }}}
    // parse() {{{

    /**
     * Parses the command line arguments and returns a
     * Console_CommandLine_Result instance.
     *
     * @param integer $userArgc Number of arguments (optional)
     * @param array   $userArgv Array containing arguments (optional)
     *
     * @return Console_CommandLine_Result The result instance
     * @throws Exception on user errors
     */
    public function parse($userArgc=null, $userArgv=null)
    {
        $this->addBuiltinOptions();
        if ($userArgc !== null && $userArgv !== null) {
            $argc = $userArgc;
            $argv = $userArgv;
        } else {
            list($argc, $argv) = $this->getArgcArgv();
        }
        // build an empty result
        $result = new Console_CommandLine_Result();
        if (!($this instanceof Console_CommandLine_Command)) {
            // remove script name if we're not in a subcommand
            array_shift($argv);
            $argc--;
        }
        // will contain arguments
        $args = array();
        foreach ($this->options as $name=>$option) {
            $result->options[$name] = $option->default;
        }
        // parse command line tokens
        while ($argc--) {
            $token = array_shift($argv);
            try {
                if ($cmd = $this->_getSubCommand($token)) {
                    $result->command_name = $cmd->name;
                    $result->command      = $cmd->parse($argc, $argv);
                    break;
                } else {
                    $this->parseToken($token, $result, $args, $argc);
                }
            } catch (Exception $exc) {
                throw $exc;
            }
        }
        // Parse a null token to allow any undespatched actions to be despatched.
        $this->parseToken(null, $result, $args, 0);
        // Check if an invalid subcommand was specified. If there are
        // subcommands and no arguments, but an argument was provided, it is
        // an invalid subcommand.
        if (   count($this->commands) > 0
            && count($this->args) === 0
            && count($args) > 0
        ) {
            throw Console_CommandLine_Exception::factory(
                'INVALID_SUBCOMMAND',
                array('command' => $args[0]),
                $this,
                $this->messages
            );
        }
        // if subcommand_required is set to true we must check that we have a
        // subcommand.
        if (   count($this->commands)
            && $this->subcommand_required
            && !$result->command_name
        ) {
            throw Console_CommandLine_Exception::factory(
                'SUBCOMMAND_REQUIRED',
                array('commands' => implode(array_keys($this->commands), ', ')),
                $this,
                $this->messages
            );
        }
        // minimum argument number check
        $argnum = 0;
        foreach ($this->args as $name=>$arg) {
            if (!$arg->optional) {
                $argnum++;
            }
        }
        if (count($args) < $argnum) {
            throw Console_CommandLine_Exception::factory(
                'ARGUMENT_REQUIRED',
                array('argnum' => $argnum, 'plural' => $argnum>1 ? 's': ''),
                $this,
                $this->messages
            );
        }
        // handle arguments
        $c = count($this->args);
        foreach ($this->args as $name=>$arg) {
            $c--;
            if ($arg->multiple) {
                $result->args[$name] = $c ? array_splice($args, 0, -$c) : $args;
            } else {
                $result->args[$name] = array_shift($args);
            }
            if (!$result->args[$name] && $arg->optional && $arg->default) {
                $result->args[$name] = $arg->default;
            }
            // check value is in argument choices
            if (!empty($this->args[$name]->choices)) {
                foreach ($result->args[$name] as $value) {
                    if (!in_array($value, $arg->choices)) {
                        throw Console_CommandLine_Exception::factory(
                            'ARGUMENT_VALUE_NOT_VALID',
                            array(
                                'name'    => $name,
                                'choices' => implode('", "', $arg->choices),
                                'value'   => implode(' ', $result->args[$name]),
                            ),
                            $this,
                            $arg->messages
                        );
                    }
                }
            }
        }
        // dispatch deferred options
        foreach ($this->_dispatchLater as $optArray) {
            $optArray[0]->dispatchAction($optArray[1], $optArray[2], $this);
        }
        return $result;
    }

    // }}}
    // parseToken() {{{

    /**
     * Parses the command line token and modifies *by reference* the $options
     * and $args arrays.
     *
     * @param string $token  The command line token to parse
     * @param object $result The Console_CommandLine_Result instance
     * @param array  &$args  The argv array
     * @param int    $argc   Number of lasting args
     *
     * @return void
     * @access protected
     * @throws Exception on user errors
     */
    protected function parseToken($token, $result, &$args, $argc)
    {
        static $lastopt  = false;
        static $stopflag = false;
        $last  = $argc === 0;
        if (!$stopflag && $lastopt) {
            if (substr($token, 0, 1) == '-') {
                if ($lastopt->argument_optional) {
                    $this->_dispatchAction($lastopt, '', $result);
                    if ($lastopt->action != 'StoreArray') {
                        $lastopt = false;
                    }
                } else if (isset($result->options[$lastopt->name])) {
                    // case of an option that expect a list of args
                    $lastopt = false;
                } else {
                    throw Console_CommandLine_Exception::factory(
                        'OPTION_VALUE_REQUIRED',
                        array('name' => $lastopt->name),
                        $this,
                        $this->messages
                    );
                }
            } else {
                // when a StoreArray option is positioned last, the behavior
                // is to consider that if there's already an element in the
                // array, and the commandline expects one or more args, we
                // leave last tokens to arguments
                if ($lastopt->action == 'StoreArray'
                    && !empty($result->options[$lastopt->name])
                    && count($this->args) > ($argc + count($args))
                ) {
                    if (!is_null($token)) {
                        $args[] = $token;
                    }
                    return;
                }
                if (!is_null($token) || $lastopt->action == 'Password') {
                    $this->_dispatchAction($lastopt, $token, $result);
                }
                if ($lastopt->action != 'StoreArray') {
                    $lastopt = false;
                }
                return;
            }
        }
        if (!$stopflag && substr($token, 0, 2) == '--') {
            // a long option
            $optkv = explode('=', $token, 2);
            if (trim($optkv[0]) == '--') {
                // the special argument "--" forces in all cases the end of 
                // option scanning.
                $stopflag = true;
                return;
            }
            $opt = $this->findOption($optkv[0]);
            if (!$opt) {
                throw Console_CommandLine_Exception::factory(
                    'OPTION_UNKNOWN',
                    array('name' => $optkv[0]),
                    $this,
                    $this->messages
                );
            }
            $value = isset($optkv[1]) ? $optkv[1] : false;
            if (!$opt->expectsArgument() && $value !== false) {
                throw Console_CommandLine_Exception::factory(
                    'OPTION_VALUE_UNEXPECTED',
                    array('name' => $opt->name, 'value' => $value),
                    $this,
                    $this->messages
                );
            }
            if ($opt->expectsArgument() && $value === false) {
                // maybe the long option argument is separated by a space, if 
                // this is the case it will be the next arg
                if ($last && !$opt->argument_optional) {
                    throw Console_CommandLine_Exception::factory(
                        'OPTION_VALUE_REQUIRED',
                        array('name' => $opt->name),
                        $this,
                        $this->messages
                    );
                }
                // we will have a value next time
                $lastopt = $opt;
                return;
            }
            if ($opt->action == 'StoreArray') {
                $lastopt = $opt;
            }
            $this->_dispatchAction($opt, $value, $result);
        } else if (!$stopflag && substr($token, 0, 1) == '-') {
            // a short option
            $optname = substr($token, 0, 2);
            if ($optname == '-') {
                // special case of "-": try to read stdin
                $args[] = file_get_contents('://stdin');
                return;
            }
            $opt = $this->findOption($optname);
            if (!$opt) {
                throw Console_CommandLine_Exception::factory(
                    'OPTION_UNKNOWN',
                    array('name' => $optname),
                    $this,
                    $this->messages
                );
            }
            // parse other options or set the value
            // in short: handle -f<value> and -f <value>
            $next = substr($token, 2, 1);
            // check if we must wait for a value
            if ($next === false) {
                if ($opt->expectsArgument()) {
                    if ($last && !$opt->argument_optional) {
                        throw Console_CommandLine_Exception::factory(
                            'OPTION_VALUE_REQUIRED',
                            array('name' => $opt->name),
                            $this,
                            $this->messages
                        );
                    }
                    // we will have a value next time
                    $lastopt = $opt;
                    return;
                }
                $value = false;
            } else {
                if (!$opt->expectsArgument()) { 
                    if ($nextopt = $this->findOption('-' . $next)) {
                        $this->_dispatchAction($opt, false, $result);
                        $this->parseToken('-' . substr($token, 2), $result,
                            $args, $last);
                        return;
                    } else {
                        throw Console_CommandLine_Exception::factory(
                            'OPTION_UNKNOWN',
                            array('name' => $next),
                            $this,
                            $this->messages
                        );
                    }
                }
                if ($opt->action == 'StoreArray') {
                    $lastopt = $opt;
                }
                $value = substr($token, 2);
            }
            $this->_dispatchAction($opt, $value, $result);
        } else {
            // We have an argument.
            // if we are in POSIX compliant mode, we must set the stop flag to 
            // true in order to stop option parsing.
            if (!$stopflag && $this->force_posix) {
                $stopflag = true;
            }
            if (!is_null($token)) {
                $args[] = $token;
            }
        }
    }

    // }}}
    // addBuiltinOptions() {{{

    /**
     * Adds the builtin "Help" and "Version" options if needed.
     *
     * @return void
     */
    public function addBuiltinOptions()
    {
        if ($this->add_help_option) {
            $helpOptionParams = array(
                'long_name'   => '--help',
                'description' => 'show this help message and exit',
                'action'      => 'Help'   
            );
            if (!($option = $this->findOption('-h')) || $option->action == 'Help') {
                // short name is available, take it
                $helpOptionParams['short_name'] = '-h';
            }
            $this->addOption('help', $helpOptionParams);
        }
        if ($this->add_version_option && !empty($this->version)) {
            $versionOptionParams = array(
                'long_name'   => '--version',
                'description' => 'show the program version and exit',
                'action'      => 'Version'   
            );
            if (!$this->findOption('-v')) {
                // short name is available, take it
                $versionOptionParams['short_name'] = '-v';
            }
            $this->addOption('version', $versionOptionParams);
        }
    } 

    // }}}
    // getArgcArgv() {{{

    /**
     * Tries to return an array containing argc and argv, or trigger an error
     * if it fails to get them.
     *
     * @return array The argc/argv array
     * @throws Console_CommandLine_Exception 
     */
    protected function getArgcArgv()
    {
        if (php_sapi_name() != 'cli') {
            // we have a web request
            $argv = array($this->name);
            if (isset($_REQUEST)) {
                foreach ($_REQUEST as $key => $value) {
                    if (!is_array($value)) {
                        $value = array($value);
                    }
                    $opt = $this->findOption($key);
                    if ($opt instanceof Console_CommandLine_Option) {
                        // match a configured option
                        $argv[] = $opt->short_name ? 
                            $opt->short_name : $opt->long_name;
                        foreach ($value as $v) {
                            if ($opt->expectsArgument()) {
                                $argv[] = isset($_REQUEST[$key]) ? urldecode($v) : $v;
                            } else if ($v == '0' || $v == 'false') {
                                array_pop($argv);
                            }
                        }
                    } else if (isset($this->args[$key])) {
                        // match a configured argument
                        foreach ($value as $v) {
                            $argv[] = isset($_REQUEST[$key]) ? urldecode($v) : $v;
                        }
                    }
                }
            }
            return array(count($argv), $argv);
        }
        if (isset($argc) && isset($argv)) {
            // case of register_argv_argc = 1
            return array($argc, $argv);
        }
        if (isset($_SERVER['argc']) && isset($_SERVER['argv'])) {
            return array($_SERVER['argc'], $_SERVER['argv']);
        }
        return array(0, array());
    }

    // }}}
    // _dispatchAction() {{{

    /**
     * Dispatches the given option or store the option to dispatch it later.
     *
     * @param Console_CommandLine_Option $option The option instance
     * @param string                     $token  Command line token to parse
     * @param Console_CommandLine_Result $result The result instance
     *
     * @return void
     */
    private function _dispatchAction($option, $token, $result)
    {
        if ($option->action == 'Password') {
            $this->_dispatchLater[] = array($option, $token, $result);
        } else {
            $option->dispatchAction($token, $result, $this);
        }
    }
    // }}}
    // _getSubCommand() {{{

    /**
     * Tries to return the subcommand that matches the given token or returns
     * false if no subcommand was found.
     *
     * @param string $token Current command line token
     *
     * @return mixed An instance of Console_CommandLine_Command or false
     */
    private function _getSubCommand($token)
    {
        foreach ($this->commands as $cmd) {
            if ($cmd->name == $token || in_array($token, $cmd->aliases)) {
                return $cmd;
            }
        }
        return false;
    }

    // }}}
}
