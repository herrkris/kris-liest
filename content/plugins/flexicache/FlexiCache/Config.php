<?php

require_once 'Exception.php';
require_once 'Config/Form.php';
require_once 'Config/Main.php';
require_once 'Config/Store/File.php';
require_once 'Config/Store/Memcache.php';
require_once 'Config/Store/Sqlite.php';

class FlexiCache_Config {

	/**
	* Filename of the config file
	*/
	const FILENAME = 'config.ser';

	/**
	* If the object definition changes such that a serialized response
	* should be considered invalid, update this number to trigger the plugin
	* to use default config instead.  Now stored as a string because storing
	* as a float added lots of random additional decimal places when
	* serialized.
	*/
	const OBJECT_VERSION			= '0.9.9.2';

	/**
	* This is compared to self::OBJECT_VERSION on wakeup() and the object
	* considered invalid if they don't match
	*/
	private $_fObjectVersion = self::OBJECT_VERSION;

	/**
	* Static instances of config array and file path
	*/
	private static $_aConfig;
	private static $_sFilePath;

	public $Main;

	public $FlexiCache_Store_File;
	public $FlexiCache_Store_Memcache;
	public $FlexiCache_Store_Sqlite;

	public function __construct ()
	{

		$this->Main = new FlexiCache_Config_Main;

		$this->FlexiCache_Store_File  = new FlexiCache_Config_Store_File;
		$this->FlexiCache_Store_Memcache  = new FlexiCache_Config_Store_Memcache;
		$this->FlexiCache_Store_Sqlite = new FlexiCache_Config_Store_Sqlite;

	}

	public function __wakeup ()
	{

		/**
		* If the config object version is invalid, reconstruct the config with
		* default settings.
		*/
		if (false == isset($this->_fObjectVersion) || ($this->_fObjectVersion != self::OBJECT_VERSION)) {
			$this->__construct();
		}

	}

	/**
	* Return the path to the config file
	*/
	public static function getFilePath ()
	{

		if (null == self::$_sFilePath) {
			self::$_sFilePath = join(DIRECTORY_SEPARATOR, array(FlexiCache::getDataDir(),self::FILENAME));
		}

		return self::$_sFilePath;

	}

	/**
	* Load a serialized config array from file.
	* Return true on success or false on failure.
	*/
	public static function load ()
	{

		$sFilePath = self::getFilePath();

		if (false == file_exists($sFilePath)) {
			return null;
		}

		try {

			if (false == ($rFp = fopen($sFilePath, 'r'))) {
				throw new FlexiCache_Exception('Config file cannot be opened for reading: ' . $sFilePath);
			}

			if (false == flock($rFp,LOCK_SH)) {
				throw new FlexiCache_Exception('Config file cannot be locked for reading: ' . $sFilePath);
			}

			if (false == ($sConfig = stream_get_contents($rFp))) {
				throw new FlexiCache_Exception('Config file stream cannot be read: ' . $sFilePath);
			}

			if (false == fclose($rFp)) {
				throw new FlexiCache_Exception('Config file cannot be closed: ' . $sFilePath);
			}

			if (false == ($aConfig = @unserialize($sConfig))) {
				throw new FlexiCache_Exception('Config file could not be unserialized: ' . $sFilePath);
			}

			if (false == is_array($aConfig)) {
				throw new FlexiCache_Exception('Config is not an array: ' . $sFilePath);
			}

		} catch (FlexiCache_Exception $oE) {

			return false;

		}

		return $aConfig;

	}


	/**
	* Serialize the config array to a string and save to file.
	* Return true on success or false on failure.
	*/
	public static function save ()
	{

		$sConfig = serialize(self::$_aConfig);

		$sFilePath = self::getFilePath();

		try {

			if (false == ($rFp = fopen($sFilePath, 'a'))) {
				throw new FlexiCache_Exception('Can\'t open config file for appending: ' . $sFilePath);
			}

			if (false == flock($rFp,LOCK_EX)) {
				throw new FlexiCache_Exception('Can\'t get exclusive lock on config file: ' . $sFilePath);
			}

			if (false == ftruncate($rFp, 0)) {
				throw new FlexiCache_Exception('Can\'t truncate config file: ' . $sFilePath);
			}

			if (false == fwrite($rFp, $sConfig, strlen($sConfig))) {
				throw new FlexiCache_Exception('Can\'t write config to file: ' . $sFilePath);
			}

			if (false == fclose($rFp)) {
				throw new FlexiCache_Exception('Can\'t close config file: ' . $sFilePath);
			}

		} catch (FlexiCache_Exception $oE) {

			return false;

		}

		return true;

	}

	/**
	* Get a config object or the value of a specified key from the config
	*/
	public static function get ($sSection=null, $sKey=null)
	{

		/**
		* A section must be requested
		*/
		if (null == $sSection) {
			throw new FlexiCache_Exception(__METHOD__ . ' called without section');
		}

		if (null == self::$_aConfig) {

			/**
			* If a config array can't be loaded, create a new one
			*/
			if (null == (self::$_aConfig = self::load())) {
				self::$_aConfig = array();
			}

		}

		/**
		* If there is no entry for the current site host, create one with
		* default settings
		*/
		if (false == isset(self::$_aConfig[FlexiCache::getSiteHost()])) {
			self::$_aConfig[FlexiCache::getSiteHost()] = new FlexiCache_Config;
		}

		/**
		* Load config for current site host into $oConfig
		*/
		$oConfig = self::$_aConfig[FlexiCache::getSiteHost()];

		/**
		* Set this null so we can test later if an object's been assigned
		* if required
		*/
		$oDefaultConfig = null;

		/**
		* A very old version of the plugin may result in an incomplete object
		* being returned after an upgrade, so in that case it needs to be reset
		* to default.  This can be removed once old versions of the plugin are
		* no longer in use.
		* #todo
		*/
		if (false == is_object($oConfig)) {
			$oConfig = new FlexiCache_Config;
		}

		/**
		* If the section is invalid in the config, see if it exists in the
		* default config, and if so, use that
		*/
		if (false == isset($oConfig->$sSection)) {

			$oDefaultConfig = new FlexiCache_Config;

			if (false == isset($oDefaultConfig->$sSection)) {
				throw new FlexiCache_Exception('Config does not have an entry for ' . $sSection);
			}

			$oConfig->$sSection = $oDefaultConfig->$sSection;

		}

		/**
		* Replace $oConfig with config for selected section
		*/
		$oConfig = $oConfig->$sSection;

		if (null !== $sKey) {

			/**
			* If the key exists in the config, return the value
			*/

			if (isset($oConfig->$sKey)) {
				return $oConfig->$sKey;
			}

			/**
			* Otherwise, if the key exists in the default config, return that value
			*/

			if (null == $oDefaultConfig) {
				$oDefaultConfig = new FlexiCache_Config;
			}

			if (isset($oDefaultConfig->$sSection->$sKey)) {
				return $oDefaultConfig->$sSection->$sKey;
			}

			/**
			* Otherwise, return null
			*/
			return null;

		}

		/**
		* If no key is specified, return the entire config object
		*/
		return $oConfig;

	}

	public static function reset ()
	{
		self::$_aConfig[FlexiCache::getSiteHost()] = new FlexiCache_Config;
	}

}
