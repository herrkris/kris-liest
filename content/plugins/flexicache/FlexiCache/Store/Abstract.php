<?php

abstract class FlexiCache_Store_Abstract {

	protected $_oConfig;
	protected $_bIsEnabled = true;
	protected $_aCheckFail = array();

	protected function _getConfig ($sKey)
	{
		return FlexiCache_Config::get(get_class($this), $sKey);
	}

	protected function _setIsEnabled ($bEnabled)
	{
		$this->_bIsEnabled = (bool) $bEnabled;
	}

	public function getIsEnabled ()
	{
		return $this->_bIsEnabled;
	}

	/**
	* Return boolean to indicate whether the current store has an index
	* which is currently available
	*/
	public function hasAvailableIndex ()
	{

		if (null == ($oIndex = $this->_getIndex())) {
			return false;
		}

		return $oIndex->isAvailable();

	}

	protected function _getIndex ()
	{
		return null;
	}

	public function check ()
	{

		/**
		* Check data directory exists, and if so, whether it's writable
		*/

		$sRootDir = FlexiCache_Config::get('Main','CacheDir');

		if (false == file_exists($sRootDir) || false == is_dir($sRootDir)) {
			$this->_addCheckFail('Data directory does not exist: ' . $sRootDir);
		} else if (false == is_writable($sRootDir)) {
			$this->_addCheckFail('Data directory is not writable: ' . $sRootDir);
		}

	}

	protected function _addCheckFail ($sString)
	{

		if (false == in_array($sString, $this->_aCheckFail)) {
			array_push($this->_aCheckFail, $sString);
		}

		$this->_setIsEnabled(false);

	}

	public function getCheckFails ()
	{
		return $this->_aCheckFail;
	}

	public function hasCheckFails ()
	{
		return (true == empty($this->_aCheckFail))?false:true;
	}

}
