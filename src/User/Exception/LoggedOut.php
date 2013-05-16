<?php
/**
 * @author     matthieu.napoli
 * @package    User
 * @subpackage Exception
 */

/**
 * The user is logged out, and needs to be redirected to log-in page
 *
 * @package    User
 * @subpackage Exception
 *
 * @see Core_Exception_User
 */
class User_Exception_LoggedOut extends Core_Exception_User
{

    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct('User', 'exceptions', 'mustBeLoggedIn');
    }

}
