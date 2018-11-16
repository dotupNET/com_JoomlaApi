<?php
/**
 * @package API plugins
 * @copyright Copyright (C) 2009 2014 Techjoomla, Tekdi Technologies Pvt. Ltd. All rights reserved.
 * @license GNU GPLv2 <http://www.gnu.org/licenses/old-licenses/gpl-2.0.html>
 * @link http://www.techjoomla.com
*/

defined('_JEXEC') or die( 'Restricted access' );

jimport('joomla.plugin.plugin');

class ApiUserAuth extends ApiPlugin
{
	public function __construct(&$subject, $config = array())
	{
		parent::__construct($subject, $config = array());

		ApiResource::addIncludePath(dirname(__FILE__).'/ApiUserAuth');
		
		/*load language file for plugin frontend*/ 
		$lang = JFactory::getLanguage(); 
		$lang->load('ApiUserAuth', JPATH_ADMINISTRATOR,'',true);

		//$this->allowAutoKeyGeneration(false);

		// Set the login resource to be public
        $this->setResourceAccess('login', 'public','post');
		$this->setResourceAccess('users', 'protected', 'post');
		$this->setResourceAccess('config', 'protected', 'get');
	}
}
