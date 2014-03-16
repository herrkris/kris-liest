<?php

class FlexiCache_Request_Key {

	/**
	* Request URI
	*/
	private $_sUri;

	/**
	* Content version id to indicate browser type
	*/
	private $_iContentVersionConditionId;

	public function __construct ($sUri, $iContentVersionConditionId)
	{
		$this->_setUri($sUri);
		$this->_setContentVersionConditionId($iContentVersionConditionId);
	}

	private function _setUri ($sUri)
	{
		$this->_sUri = $sUri;
	}

	public function getUri ()
	{
		return $this->_sUri;
	}

	private function _setContentVersionConditionId ($iContentVersionConditionId)
	{
		$this->_iContentVersionConditionId = (int)$iContentVersionConditionId;
	}

	public function getContentVersionConditionId ()
	{
		return $this->_iContentVersionConditionId;
	}

	public function __toString ()
	{
		return join('.', array(md5($this->getUri()), $this->getContentVersionConditionId()));
	}

}
