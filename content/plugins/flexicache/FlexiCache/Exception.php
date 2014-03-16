<?php

class FlexiCache_Exception extends Exception {

	public function __construct ($sMessage)
	{
		parent::__construct($sMessage);
		self::_log($this);
	}

	/**
	* Return the location of the log file path
	*/
	private static function _getLogFilePath ()
	{
		return join(DIRECTORY_SEPARATOR, array(FlexiCache::getDataDir(),FlexiCache::getSiteHost().'.log'));
	}

	/**
	* Log an exception to file
	*/
	private static function _log ($oE)
	{

		$sLogFilePath = self::_getLogFilePath();

		if (false == ($rFp = fopen($sLogFilePath, 'a'))) {
			return;
		}

		if (false == flock($rFp, LOCK_EX)) {
			return;
		}

		$sMessage = sprintf("%s: %s (%s line %d)\n",
			date('Y-m-d H:i:s'),
			$oE->getMessage(),
			$oE->getFile(),
			$oE->getLine()
		);

		if (false == (fwrite($rFp, $sMessage, strlen($sMessage)))) {
			flock($rFp, LOCK_UN);
			return false;
		}

		fclose($rFp);

	}

	/**
	* Return the contents of the log file
	*/
	public static function getLog ()
	{

		if (false == file_exists($sLogFilePath = self::_getLogFilePath())) {
			return '';
		}

		return file_get_contents($sLogFilePath);

	}

	/**
	* Delete the log file
	*/
	public static function deleteLog ()
	{

		if (false == file_exists($sLogFilePath = self::_getLogFilePath())) {
			return true;
		}

		return unlink($sLogFilePath);

	}

}
