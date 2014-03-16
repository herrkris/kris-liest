<?php

require_once 'Exception.php';
require_once 'Store/Index/Interface.php';

if (true == FlexiCache_Wp::isWpAdminPage()) {
	require_once 'Store/Metrics.php';
}

class FlexiCache_Store {

	/**
	* Class name strings
	*/
	const CLASS_MEMCACHE	= 'Memcache';
	const CLASS_SQLITE		= 'Sqlite';
	const CLASS_FILE		= 'File';

	public static function factory ($sStore)
	{

		$sClass = join('_', array(__CLASS__,$sStore));
		$sClassFile = 'Store/' . $sStore . '.php';

		if (false == include_once($sClassFile)) {
			throw new FlexiCache_Exception(__METHOD__ . ' can\'t open ' . $sClassFile);
		}

		return new $sClass;

	}

	public static function getEngines ()
	{

		return array(
			self::CLASS_FILE		=> 'Filesystem',
			self::CLASS_MEMCACHE	=> 'Memcache',
			self::CLASS_SQLITE		=> 'SQLite'
		);

	}

	public static function getEngineDisplayName ($sEngine)
	{

		$aEngine = self::getEngines();

		if (true == isset($aEngine[$sEngine])) {
			return $aEngine[$sEngine];
		}

		return 'Unknown';

	}

	/**
	* Get metrics on the provided database, return an object of type
	* FlexiCache_Wp_Admin_Metrics with source set to $sSource
	*/
	public static function getSqliteStoreMetrics ($oDb, $sSource='Unknown')
	{

		$oMetrics = new FlexiCache_Store_Metrics;

		try {

			$rResult = $oDb->query('SELECT COUNT(expires) AS numresponses, SUM(size) AS storesize FROM response');

			if (0 == sqlite_num_rows($rResult)) {
				return $oMetrics;
			}

			$aRow = sqlite_fetch_array($rResult);

			$oMetrics
				->setNumResponses($aRow['numresponses'])
				->setSize($aRow['storesize'])
				->setSource($sSource)
			;

			$rResult = $oDb->query(sprintf('SELECT expires FROM response WHERE expires<%d', time()));

			$oMetrics->setNumExpiredResponses(sqlite_num_rows($rResult));

			return $oMetrics;

		} catch (FlexiCache_Exception $oE) {

			return false;

		}

	}

}
