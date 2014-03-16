<?php

require_once 'Exception.php';

class FlexiCache_Store_Directory {

	private static $_sHostPath;

	/**
	* Return the path to the directory for the current hostname.
	* This is required when caching for multiple sites in WordPress MU
	*/
	public static function getHostPath ()
	{

		if (null === self::$_sHostPath) {
			self::$_sHostPath = join(DIRECTORY_SEPARATOR, array(FlexiCache_Config::get('Main','CacheDir'),FlexiCache::getSiteHost()));
		}

		return self::$_sHostPath;

	}

	/**
	* Return boolean to indicate whether $sDir exists and is a directory
	*/
	public static function exists ($sDir)
	{

		if (false == file_exists($sDir)) {
			return false;
		}

		if (false == is_dir($sDir)) {
			return false;
		}

		return true;

	}

	/**
	* Check a directory exists, and if not, create it.  Return true if the
	* directory exists or is created, otherwise false.
	*/
	public static function checkCreate ($sDir)
	{

		if (true == self::exists($sDir)) {
			return true;
		}

		if (true == mkdir($sDir)) {
			return true;
		}

		return false;

	}

}
