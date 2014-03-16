<?php

/**
* Extend the FlexiCache_Config_Condition class to create a Condition object
* with an additional property containing the expiry time
*/
class FlexiCache_Config_Condition_Expire extends FlexiCache_Config_Condition {

	private $_iExpiresSeconds;

	public function __construct ($iSource, $sKey, $sValue, $iMatchType, $bIsEnabled=true, $iExpiresSeconds=0, $sDescription=null)
	{

		parent::__construct($iSource, $sKey, $sValue, $iMatchType, $bIsEnabled, $sDescription);
		$this->setExpiresSeconds($iExpiresSeconds);

	}

	/**
	* Generate a new blank condition for adding to a form
	*/
	public static function getEmpty ()
	{

		$oCondition = new FlexiCache_Config_Condition_Expire(self::DATA_SOURCE_GET,null,null,self::MATCH_TYPE_EQUALS,false,0,null);
		$oCondition->setId(0);

		return $oCondition;

	}

	public function setExpiresSeconds ($iExpiresSeconds)
	{
		$this->_iExpiresSeconds = (float) $iExpiresSeconds;
	}

	public function getExpiresSeconds ()
	{
		return $this->_iExpiresSeconds;
	}

	public function update ($aCondition)
	{
		parent::update($aCondition);
		$this->setExpiresSeconds((float)$aCondition['ExpiresSeconds']);
	}

}
