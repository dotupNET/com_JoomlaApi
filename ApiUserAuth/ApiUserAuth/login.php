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

        $body = json_decode(file_get_contents('php://input'), TRUE);

        $username = $body['username'];
        $password = $body['password'];

		$user = $this->loadUserByCredentials($username, $password);

		if($user===false){
			ApiError::raiseError(400, 'Invalid credentials', 'APIException');
			return;
		}

		$token = $this->keygen($user);

		$user->token = $token;
		$user->password = "";
		$this->plugin->setResponse($user);
	}

	public function keygen($user)
	{
		//init variable
		$obj = new stdclass;
        $key = null;

        // Get login user hash
		$kmodel = new ApiModelKeyShit();
		$kmodel->setState('user_id', $user->id);

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
		
		if( empty($key) ){
            if($user===false){
                ApiError::raiseError(400, 'Authentication failed', 'APIException');
                return;
            }
		}

		return $key;

	}

	private function loadUserByCredentials($user, $pass)
	{
		jimport('joomla.user.authentication');

		$authenticate = JAuthentication::getInstance();

		$response = $authenticate->authenticate(array('username' => $user, 'password' => $pass), $options = array());

		if ($response->status === JAuthentication::STATUS_SUCCESS)
		{
		    return $response;
		}
		else
		{
			return false;
		}

	}
}
