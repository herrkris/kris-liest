<?php

require_once 'Headers.php';
require_once 'Request/Key.php';

class FlexiCache_Request {

	/**
	* Request URI
	*/
	private $_sUri;

	/**
	* Request headers
	*/
	private $_oHeaders;

	/**
	* Key for request
	*/
	private $_oKey;

	public function __construct ()
	{

		$this->_setUri();

		$this->_setHeaders(new FlexiCache_Headers);

		/**
		* ConditionSet::validateAny() returns false if no condition matches,
		* so cast this to an int to produce "0" for default version
		*/
		$iContentVersionConditionId = (int)FlexiCache_Config::get('Main', 'ConditionSet_ContentVersion')->validateAny();

		$this->_setKey(new FlexiCache_Request_Key($this->getUri(),$iContentVersionConditionId));

		$this->_importHeaders();

	}

	/**
	* Import request headers
	*/
	private function _importHeaders ()
	{

		foreach ($_SERVER as $sKey=>$sVal) {

			if ('HTTP_' == substr($sKey,0,5)) {

				/**
				* Request headers are considered case-insensitive.
				* Replace '_' with '-' to match the HTTP spec.
				* e.g. "HTTP_ACCEPT_ENCODING" becomes "accept-encoding"
				*/
				$sKey = strtolower(str_replace('_','-',substr($sKey,5)));
				$this->getHeaders()->set($sKey,$sVal);

			}

		}

	}

	private function _setUri ()
	{
		$this->_sUri = $_SERVER['REQUEST_URI'];
	}

	public function getUri ()
	{
		return $this->_sUri;
	}

	private function _setHeaders ($oHeaders)
	{
		$this->_oHeaders = $oHeaders;
	}

	public function getHeaders ()
	{
		return $this->_oHeaders;
	}

	private function _setKey ($oKey)
	{
		$this->_oKey = $oKey;
	}

	public function getKey ()
	{
		return $this->_oKey;
	}

}
