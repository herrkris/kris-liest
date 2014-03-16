<?php

require_once 'Condition/Expire.php';

class FlexiCache_Config_Condition {

	const DATA_SOURCE_GET				= 1;
	const DATA_SOURCE_POST				= 2;
	const DATA_SOURCE_COOKIE			= 3;
	const DATA_SOURCE_ENV				= 4;
	const DATA_SOURCE_SERVER			= 5;
	const DATA_SOURCE_HEADER_REQUEST	= 6;
	const DATA_SOURCE_HEADER_RESPONSE	= 7;

	const MATCH_TYPE_EQUALS				= 1;
	const MATCH_TYPE_DOES_NOT_EQUAL		= 2;
	const MATCH_TYPE_CONTAINS			= 3;
	const MATCH_TYPE_DOES_NOT_CONTAIN	= 4;
	const MATCH_TYPE_BEGINS_WITH		= 5;
	const MATCH_TYPE_ENDS_WITH			= 6;
	const MATCH_TYPE_REGEX				= 7;

	private $_iId;
	private $_iSource;
	private $_sKey;
	private $_sValue;
	private $_iMatchType;
	private $_bIsEnabled;
	private $_sDescription;

	private static $_aValidSource = array (
		self::DATA_SOURCE_GET,
		self::DATA_SOURCE_COOKIE,
		self::DATA_SOURCE_ENV,
		self::DATA_SOURCE_SERVER,
	);

	private static $_aValidMatchType = array (
		self::MATCH_TYPE_EQUALS,
		self::MATCH_TYPE_DOES_NOT_EQUAL,
		self::MATCH_TYPE_CONTAINS,
		self::MATCH_TYPE_DOES_NOT_CONTAIN,
		self::MATCH_TYPE_BEGINS_WITH,
		self::MATCH_TYPE_REGEX
	);

	public function __construct ($iSource, $sKey, $sValue, $iMatchType, $bIsEnabled=true, $sDescription=null)
	{

		$this
			->setSource($iSource)
			->setKey($sKey)
			->setValue($sValue)
			->setMatchType($iMatchType)
			->setIsEnabled($bIsEnabled)
			->setDescription($sDescription)
		;

	}

	/**
	* Generate a new blank condition for adding to a form
	*/
	public static function getEmpty ()
	{

		$oCondition = new FlexiCache_Config_Condition(self::DATA_SOURCE_GET,null,null,self::MATCH_TYPE_EQUALS,false,null);
		$oCondition->setId(0);

		return $oCondition;

	}

	public static function getAvailableSourceOptions ()
	{

		$aOption = array();

		foreach (self::$_aValidSource as $iSource) {
			$aOption[$iSource] = self::getSourceName($iSource);
		}

		return $aOption;

	}

	public static function getAvailableMatchTypeOptions ()
	{

		$aOption = array();

		foreach (self::$_aValidMatchType as $iMatchType) {
			$aOption[$iMatchType] = self::getMatchTypeName($iMatchType);
		}

		return $aOption;

	}

	public static function getSourceName ($iSource)
	{

		switch ($iSource) {

			case self::DATA_SOURCE_GET:
				$sSourceName = '$_GET';
				break;
			case self::DATA_SOURCE_POST:
				$sSourceName = '$_POST';
				break;
			case self::DATA_SOURCE_COOKIE:
				$sSourceName = '$_COOKIE';
				break;
			case self::DATA_SOURCE_ENV:
				$sSourceName = '$_ENV';
				break;
			case self::DATA_SOURCE_SERVER:
				$sSourceName = '$_SERVER';
				break;
			case self::DATA_SOURCE_HEADER_REQUEST:
				$sSourceName = 'Request Header';
			case self::DATA_SOURCE_HEADER_RESPONSE:
				$sSourceName = 'Response Header';
				break;
			default:
				$sSourceName = 'Unknown';

		}

		return $sSourceName;

	}

	public static function getMatchTypeName ($iMatchType)
	{

		switch ($iMatchType) {

			case self::MATCH_TYPE_EQUALS:
				$sMatchTypeName = 'Equals';
				break;
			case self::MATCH_TYPE_DOES_NOT_EQUAL:
				$sMatchTypeName = 'Does not equal';
				break;
			case self::MATCH_TYPE_CONTAINS:
				$sMatchTypeName = 'Contains';
				break;
			case self::MATCH_TYPE_DOES_NOT_CONTAIN:
				$sMatchTypeName = 'Does not contains';
				break;
			case self::MATCH_TYPE_BEGINS_WITH:
				$sMatchTypeName = 'Begins with';
				break;
			case self::MATCH_TYPE_ENDS_WITH:
				$sMatchTypeName = 'Ends with';
				break;
			case self::MATCH_TYPE_REGEX:
				$sMatchTypeName = 'Regular Expression';
				break;
			default:
				$sMatchTypeName = 'Unknown';

		}

		return $sMatchTypeName;

	}

	private function _getSourceArray ()
	{

		switch ($this->getSource()) {

			case self::DATA_SOURCE_GET:
				$aSource = $_GET;
				break;
			case self::DATA_SOURCE_POST:
				$aSource = $_POST;
				break;
			case self::DATA_SOURCE_COOKIE:
				$aSource = $_COOKIE;
				break;
			case self::DATA_SOURCE_ENV:
				$aSource = $_ENV;
				break;
			case self::DATA_SOURCE_SERVER:
				$aSource = $_SERVER;
				break;
			case self::DATA_SOURCE_HEADER_REQUEST:
				$aSource = FlexiCache::getRequest()->getHeaders()->get();
				break;
			case self::DATA_SOURCE_HEADER_RESPONSE:
				$aSource = FlexiCache::getResponse()->getHeaders()->get();
				break;
			default:
				$aSource = null;

		}

		return $aSource;

	}

	/**
	* Wrapper to best available ereg function.
	* Use mb_ereg_match() if available, otherwise ereg()
	*/
	public function eregMatch ($sPattern, $sString)
	{

		if (true == function_exists('mb_eregi')) {
			return (false == mb_eregi($sPattern, $sString))?false:true;
		}

		if (true == function_exists('eregi')) {
			return (false == eregi($sPattern, $sString))?false:true;
		}

		throw new FlexiCache_Exception('No ereg function available');

	}

	public function validate ()
	{

		/**
		* This error condition should not occur as the data source is checked
		* when it is set
		*/
		if (null === ($aSource = $this->_getSourceArray())) {
			throw new FlexiCache_Exception('Invalid data source: ' . $this->getSource());
		}

		/**
		* Check if condition is enabled
		*/
		if (false == $this->getIsEnabled()) {
			return false;
		}

		/**
		* Check if key exists in data source
		*/
		if (false == isset($aSource[$this->getKey()])) {
			return false;
		}

		switch ($this->getMatchType()) {

			case self::MATCH_TYPE_EQUALS:
				$bMatch = (0 == strcasecmp($this->getValue(),$aSource[$this->getKey()]))?true:false;
				break;

			case self::MATCH_TYPE_DOES_NOT_EQUAL:
				$bMatch = (0 != strcasecmp($this->getValue(),$aSource[$this->getKey()]))?true:false;
				break;

			case self::MATCH_TYPE_CONTAINS:
				$bMatch = (false !== stristr($aSource[$this->getKey()],$this->getValue()))?true:false;
				break;

			case self::MATCH_TYPE_DOES_NOT_CONTAIN:
				$bMatch = (false === stristr($aSource[$this->getKey()],$this->getValue()))?true:false;
				break;

			case self::MATCH_TYPE_BEGINS_WITH:
				$bMatch = (0 === stripos($aSource[$this->getKey()],$this->getValue()))?true:false;
				break;

			case self::MATCH_TYPE_REGEX:
				$bMatch = ($this->eregMatch($this->getValue(), $aSource[$this->getKey()]))?true:false;
				break;

			default:
				$bMatch = false;

		}

		return $bMatch;

	}

	/**
	* Update the condition from the array
	*/
	public function update ($aCondition)
	{

		$this->setSource($aCondition['Source']);
		$this->setKey($aCondition['Key']);
		$this->setValue($aCondition['Value']);
		$this->setMatchType($aCondition['MatchType']);
		$this->setIsEnabled($aCondition['IsEnabled']);
		$this->setDescription($aCondition['Description']);

	}

	public function setId ($iId)
	{
		$this->_iId = (int) $iId;
	}

	public function getId ()
	{
		return $this->_iId;
	}

	public function setSource ($iSource)
	{

		if (false == in_array($iSource, self::$_aValidSource)) {
			throw new FlexiCache_Exception('Invalid data source: ' . $iSource);
		}

		$this->_iSource = $iSource;

		return $this;

	}

	public function getSource ()
	{
		return $this->_iSource;
	}

	public function setKey ($sKey)
	{
		$this->_sKey = trim((string)$sKey);
		return $this;
	}

	public function getKey ()
	{
		return $this->_sKey;
	}

	public function setValue ($sValue)
	{
		$this->_sValue = trim((string)$sValue);
		return $this;
	}

	public function getValue ()
	{
		return $this->_sValue;
	}

	public function setMatchType($iMatchType)
	{

		if (false == in_array($iMatchType, self::$_aValidMatchType)) {
			throw new FlexiCache_Exception('Invalid match type: ' . $iMatchType);
		}

		$this->_iMatchType = (int)$iMatchType;

		return $this;

	}

	public function getMatchType ()
	{
		return $this->_iMatchType;
	}

	public function setIsEnabled ($bIsEnabled)
	{
		$this->_bIsEnabled = (bool)$bIsEnabled;
		return $this;
	}

	public function getIsEnabled ()
	{
		return $this->_bIsEnabled;
	}

	public function setDescription ($sDescription)
	{
		$this->_sDescription = trim((string)$sDescription);
		return $this;
	}

	public function getDescription ()
	{
		return $this->_sDescription;
	}

}
