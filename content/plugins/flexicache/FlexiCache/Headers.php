<?php

class FlexiCache_Headers {

	/**
	* Keys to use for custom headers
	*/
	const CUSTOM_CONTROL_KEY	= 'X-FlexiCache-Control';
	const CUSTOM_OUTPUT_KEY		= 'X-FlexiCache';

	/**
	* Array of header key/value pairs
	*/
	private $_aHeader = array ();

	/**
	* Array of arrays keyed on header key for each component in header value
	*/
	private $_aComponent;

	/**
	* Set an individual header
	*/
	public function set ($sKey, $sValue)
	{
		$this->_aHeader[trim($sKey)] = trim($sValue);
	}

	/**
	* Return an individual header value if a key is specified, otherwise
	* return the entire header array
	*/
	public function get ($sKey=null)
	{

		if (null == $sKey) {
			return $this->_aHeader;
		}

		if (false == isset($this->_aHeader[$sKey])) {
			return null;
		}

		return $this->_aHeader[$sKey];

	}

	/**
	* Remove a header specified by $sKey if it is set
	*/
	public function remove ($sKey)
	{

		if (isset($this->_aHeader[$sKey])) {
			unset($this->_aHeader[$sKey]);
		}

	}

	/**
	* Split header values into components and store them separately and lower-case.
	* Note: This method does not correctly parse all header formats, but it
	* works on the ones we need to use.
	*/
	private function _setComponents ()
	{

		$this->_aComponent = array();

		foreach ($this->get() as $sKey=>$sValue) {

			$this->aComponent[$sKey=strtolower($sKey)] = array();

			$aComponent = preg_split('#[;,]\s*#', strtolower($sValue));
			$iComponent = 0;

			foreach ($aComponent as $sComponent) {

				if (true == preg_match('#^([^=]+)(?:\s*=\s*(.*))?#', $sComponent, $aCapture)) {

					$sComponentKey = $aCapture[1];
					$sComponentValue = (true == isset($aCapture[2]))?$aCapture[2]:null;

					/**
					* Store component key by numerical index, and component value by component key
					*/
					$this->_aComponent[$sKey][$iComponent++] = $sComponentKey;
					$this->_aComponent[$sKey][$sComponentKey] = $sComponentValue;

				}

			}

		}

	}

	/**
	* Return boolean to indicate whether the specified header has the
	* specified component set, regardless of whether the component's value
	* is empty or null.  Component key should be string or integer.
	*/
	public function hasComponent ($sHeader, $mComponentKey)
	{

		if (null == $this->_aComponent) {
			$this->_setComponents();
		}

		if (false == isset($this->_aComponent[$sHeader=strtolower($sHeader)])) {
			return false;
		}

		if (false == array_key_exists(strtolower($mComponentKey),$this->_aComponent[$sHeader])) {
			return false;
		}

		return true;

	}

	/**
	* Return an individual header component.  Component key should be string
	* or integer
	*/
	public function getComponent ($sHeader, $mComponentKey)
	{

		if (false == $this->hasComponent($sHeader=strtolower($sHeader), $mComponentKey=strtolower($mComponentKey))) {
			return null;
		}

		return $this->_aComponent[$sHeader][$mComponentKey];

	}

}
