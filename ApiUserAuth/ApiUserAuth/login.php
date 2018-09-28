<?php
/**
 * @package API plugins
 * @copyright Copyright (C) 2009 2014 Techjoomla, Tekdi Technologies Pvt. Ltd. All rights reserved.
 * @license GNU GPLv2 <http://www.gnu.org/licenses/old-licenses/gpl-2.0.html>
 * @link http://www.techjoomla.com
 */

defined('_JEXEC') or die( 'Restricted access' );

jimport('joomla.plugin.plugin');
jimport('joomla.html.html');
jimport('joomla.application.component.controller');
jimport('joomla.application.component.model');
jimport('joomla.user.helper');
jimport('joomla.user.user');
jimport('joomla.application.component.helper');

JModelLegacy::addIncludePath(JPATH_SITE . 'components/com_api/models');
require_once JPATH_SITE . '/components/com_api/libraries/authentication/user.php';
require_once JPATH_SITE . '/components/com_api/libraries/authentication/login.php';
require_once JPATH_SITE . '/components/com_api/models/key.php';
require_once JPATH_SITE . '/components/com_api/models/keys.php';

class Login extends ApiResource
{
	public function get()
	{
		$this->plugin->setResponse( JText::_('ApiUserAuth_GET_METHOD_NOT_ALLOWED_MESSAGE'));
	}

	public function post()
	{
		$app = JFactory::getApplication();

		if (array_key_exists("HTTP_USERNAME", $_SERVER)) {
			$username = $_SERVER['HTTP_USERNAME'];
			if (array_key_exists("HTTP_PASSWORD", $_SERVER)) {
				$password = $_SERVER['HTTP_PASSWORD'];
			}
		} else {
			$username = $app->input->get('username', 0, 'STRING');
			$password = $app->input->get('password', 0, 'STRING');
		}

		$userId = $this->loadUserByCredentials($username, $password);

		if($userId===false){
			ApiError::raiseError(400, 'Invalid credentials', 'APIException');
			return;
		}
		$this->plugin->setResponse($this->keygen());
	}

	public function keygen()
	{
		//init variable
		$obj = new stdclass;
		$umodel = new JUser;
		$user = $umodel->getInstance();       

		$app = JFactory::getApplication();
		$username = $app->input->get('username', 0, 'STRING');

		$user = JFactory::getUser();
		$id = JUserHelper::getUserId($username);

		$kmodel = new ApiModelKey;
		$model = new ApiModelKeys;
		$key = null;
		// Get login user hash
		//$kmodel->setState('user_id', $user->id);
		$kmodel->setState('user_id', $id);
		$log_hash = $kmodel->getList();
		$log_hash = (!empty($log_hash))?$log_hash[count($log_hash) - count($log_hash)]:$log_hash;

		if( !empty($log_hash) )
		{
			$key = $log_hash->hash;
		}
		elseif( $key == null || empty($key) )
		{
				// Create new key for user
				$data = array(
				'userid' => $user->id,
				'domain' => '' ,
				'state' => 1,
				'id' => '',
				'task' => 'save',
				'c' => 'key',
				'ret' => 'index.php?option=com_api&view=keys',
				'option' => 'com_api',
				JSession::getFormToken() => 1
				);

				$result = $kmodel->save($data);
				$key = $result->hash;
				
		}
		
		if( !empty($key) )
		{
			$obj->auth = $key;
			$obj->code = '200';
			//$obj->id = $user->id;
			$obj->id = $id;
		}
		else
		{
			$obj->code = 403;
			$obj->message = JText::_('ApiUserAuth_BAD_REQUEST_MESSAGE');
		}
		return( $obj );
	
	}

	private function loadUserByCredentials($user, $pass)
	{
		jimport('joomla.user.authentication');

		$authenticate = JAuthentication::getInstance();

		$response = $authenticate->authenticate(array('username' => $user, 'password' => $pass), $options = array());

		if ($response->status === JAuthentication::STATUS_SUCCESS)
		{
			$userId = JUserHelper::getUserId($response->username);

			if ($userId === false)
			{
				return false;
			}
		}
		else
		{
			return false;
		}

		return $userId;
	}
}
