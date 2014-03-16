<?php

class FlexiCache_Config_Store_Memcache {

	/**
	* Connection details
	*/
	public $Host = 'localhost';
	public $Port = 11211;

	public function update ($aInput)
	{

		$oConfig = FlexiCache_Config::get('FlexiCache_Store_Memcache');

		if (isset($aInput['Host'])) {
			$oConfig->Host = trim((string) $aInput['Host']);
		}

		if (isset($aInput['Port'])) {
			$oConfig->Port = (float) $aInput['Port'];
		}

		if (false == FlexiCache_Store::factory(FlexiCache_Store::CLASS_MEMCACHE)->testConnection()) {
			FlexiCache_Wp_Admin::addUserMessage('Couldn\'t connect to Memcache server on ' . $oConfig->Host . ':' . $oConfig->Port);
		} else {
			FlexiCache_Wp_Admin::addUserMessage('Successfully connected to Memcache server on ' . $oConfig->Host . ':' . $oConfig->Port);
		}

		return true;

	}

}
