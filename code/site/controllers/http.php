<?php
/**
 * @package     Joomla.Site
 * @subpackage  Com_api
 *
 * @copyright   Copyright (C) 2009-2014 Techjoomla, Tekdi Technologies Pvt. Ltd. All rights reserved.
 * @license     GNU GPLv2 <http://www.gnu.org/licenses/old-licenses/gpl-2.0.html>
 * @link        http://techjoomla.com
 * Work derived from the original RESTful API by Techjoomla (https://github.com/techjoomla/Joomla-REST-API)
 * and the com_api extension by Brian Edgerton (http://www.edgewebworks.com)
 */
defined('_JEXEC') or die('Restricted access');

jimport('joomla.application.component.controller');
jimport('joomla.plugin.helper');

/**
 * ApiControllerHttp class
 *
 * @since  1.0
 */
class ApiControllerHttp extends ApiController
{
	public $callbackname = 'callback';


	public function display($cachable = false, $urlparams = array())
	{
		$this->resetDocumentType();
		$app = JFactory::getApplication();
		$name = $app->input->get('app', '', 'CMD');

		$params = JComponentHelper::getParams('com_api');
		$callMethod = $app->input->getMethod();
		$httpOrigin = $app->input->server->getString('HTTP_REFERER', '');

		$JUriObj = JUri::getInstance($httpOrigin);
		$referer = $JUriObj->toString(array('scheme', 'host', 'port'));

		// Special method for OPTIONS method
		if ((! empty($params->get("allow_cors"))))
		{
			$corsUrls = $params->get('cors', "*");

			if ($corsUrls === "*")
			{
				header("Access-Control-Allow-Origin: " . '*');
			}
			else
			{
				$corsUrlsArray = array_map('trim', explode(',', $corsUrls));

				if (in_array($referer, $corsUrlsArray))
				{
					header("Access-Control-Allow-Origin: " . $referer);
				}
			}

			header("Access-Control-Allow-Methods: " . strtoupper($params->get("allow_cors")));
		}

		if ($callMethod === "OPTIONS")
		{
			header("Content-type: application/json");
			header("Access-Control-Allow-Headers: " . $params->get("allow_headers"));

			jexit();
		}

		try
		{
		  $app->setHeader('status', 200);
			$resource_response = ApiPlugin::getInstance($name)->fetchResource();

			echo $this->respond($resource_response);
		}
		catch (Exception $e)
		{
      http_response_code($e->getCode());
			echo $this->respond($e);
		}
	}

	/**
	 * Send the response in the correct format
	 *
	 * @param   OBJECT  $response  exception
	 *
	 * @return  json
	 *
	 * @since 2.0
	 */
	private function respond($response)
	{
		$app = JFactory::getApplication();
		$accept = $app->input->server->get('HTTP_ACCEPT', 'application/json', 'STRING');
		$compatibility = $app->input->server->get('HTTP_X_COMPATIBILITY_MODE', 0, 'INT');

		// Enforce JSON in compatibility mode
		if ($compatibility)
		{
			$output = new \stdClass;
			header("Content-type: application/json");

      if ($response instanceof ApiException)
      {
        $output->message = $response->getMessage();
        $output->code = $response->getCode();
        $output->data = $response->getData();
      }
      elseif ($response instanceof Exception)
      {
        $output->message = $response->getMessage();
        $output->code = $response->getCode();
      }
			else
			{
				$output = $response->get('response');
			}

			echo json_encode($output);

			die();
		}

		switch ($accept)
		{
			case 'application/json':
			default:
      header("Content-type: application/json");
      header("status: 400");
				$format = 'json';
			break;

			case 'application/xml':
				header("Content-type: application/xml");
				$format = 'xml';
			break;
		}

		$output_overrride = JPATH_ROOT . '/templates/' . $app->getTemplate() . '/' . $format . '/api.php';

		if (file_exists($output_overrride))
		{
			require_once $output_overrride;
		}
		else
		{
			require_once JPATH_COMPONENT . '/libraries/response/' . $format . 'response.php';
		}

		$classname = 'API' . ucfirst($format) . 'Response';
		$output = new $classname($response);

		echo $output->__toString();

		jexit();
	}

	/**
	 * Resets the document type to format=raw
	 *
	 * @todo Figure out if there is a better way to do this
	 *
	 * @return void
	 *
	 * @since 0.1
	 */
	private function resetDocumentType()
	{
		JResponse::clearHeaders();
	}
}
