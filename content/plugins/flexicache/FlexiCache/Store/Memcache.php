<?php

require_once 'Abstract.php';
require_once 'Interface.php';
require_once 'Exception.php';
require_once 'Directory.php';
require_once 'Index/Abstract.php';
require_once 'Memcache/Index.php';

class FlexiCache_Store_Memcache extends FlexiCache_Store_Abstract implements FlexiCache_Store_Interface {

	private $_oMemcache;
	private $_oIndex;

	protected function _getIndex ()
	{

		if (null === $this->_oIndex) {
			$this->_oIndex = new FlexiCache_Store_Memcache_Index;
		}

		return $this->_oIndex;

	}

	/**
	* Since there is a store for all sites using the Memcache server, the
	* site hostname must be included in the key
	*/
	private function _getKeyString ($oKey)
	{
		return FlexiCache::getSiteHost() . '.' . (string)$oKey;
	}

	public function check ()
	{

		/**
		* Abstract method contains data directory checks
		*/
		parent::check();

		if ($this->hasCheckFails()) {
			$this->_setIsEnabled(false);
		}

		/**
		* Check for library availability
		*/
		if (false == self::libraryIsPresent()) {
			$this->_addCheckFail('Memcache library is not available on this system');
		}

		return (true == $this->hasCheckFails())?false:true;

	}

	private function _getConnection ()
	{

		if (null == $this->_oMemcache) {

			$this->_oMemcache = new Memcache;

			if (false == @$this->_oMemcache->connect($this->_getConfig('Host'),$this->_getConfig('Port'))) {
				$this->_oMemcache = false;
			}

		}

		return $this->_oMemcache;

	}

	/**
	* Return boolean to indicate whether the PHP Memcache library is present
	*/
	public function libraryIsPresent ()
	{

		if (true == class_exists('Memcache')) {
			return true;
		}

		return false;

	}

	public function testConnection ()
	{

		if (false == self::libraryIsPresent()) {
			return false;
		}

		if (false == $this->_getConnection()) {
			return false;
		}

		return true;

	}

	public function delete ($sKey)
	{

		if (false == $this->_getConnection()->delete($sKey, 0)) {
			return false;
		}

		return true;

	}

	public function getNumCachedResponses ($sUri)
	{

		if ($this->_getIndex()->isAvailable()) {
			return $this->_getIndex()->getNumCachedResponses($sUri);
		}

		return false;

	}

	public function getByKeyString ($sKey)
	{
		return $this->_getConnection()->get($sKey);
	}

	/**
	* Attempt to fetch a response object from the cache.
	* Return a FlexiCache_Response object or false if not found.
	*/
	public function fetch (FlexiCache_Request_Key $oKey)
	{

		try {

			if (false == $this->_getConnection()) {
				return false;
			}

			if (false == ($oResponse = $this->getByKeyString($this->_getKeyString($oKey)))) {
				return false;
			}

			return $oResponse;

		} catch (FlexiCache_Exception $oE) {

			return false;

		}

	}

	/**
	* Write response object to the cache
	*/
	public function write (FlexiCache_Request_Key $oKey, FlexiCache_Response $oResponse)
	{

		try {

			if (false == $this->_getConnection()) {
				return false;
			}

			$sKeyString = $this->_getKeyString($oKey);

			if ($this->_getIndex()->isAvailable()) {

				$iDataSize = strlen(serialize($oResponse));

				if (false == $this->_getIndex()->update($oKey, $sKeyString, $oResponse, $iDataSize)) {
					return false;
				}

			}

			/**
			* Set Memcache's expiry later than the response's expiry so that we can serve a stale
			* response in certain conditions
			*/
			$iMemcacheExpiresTimestamp = $oResponse->getExpiresTimestamp() + FlexiCache::STORE_TIMEOUT_EXTENSION;

			/**
			* Try set()
			*/
			if (false === ($this->_getConnection()->set($sKeyString, $oResponse, null, $iMemcacheExpiresTimestamp))) {
				return false;
			}

			return true;

		} catch (FlexiCache_Exception $oE) {

			return false;

		}

	}

	public function purgeUri ($sUri)
	{

		try {

			/**
			* Use index if possible
			*/
			if ($this->_getIndex()->isAvailable()) {
				return $this->_getIndex()->purgeUri($sUri);
			}

			/**
			* Make a list of possible content version ids which could be
			* stored, starting with "0" which is the default if no
			* conditions match
			*/

			if (false == ($oConditionSet = FlexiCache_Config::get('Main', 'ConditionSet_ContentVersion'))) {
				throw new FlexiCache_Exception('Couldn\'t get content version condition set');
			}

			$aContentVersionId = array(0);

			foreach ($oConditionSet->getConditions() as $oCondition) {
				array_push($aContentVersionId, $oCondition->getId());
			}

			/**
			* Foreach content version id, if a cached version exists, delete it
			*/
			foreach ($aContentVersionId as $iContentVersionId) {

				$oKey = new FlexiCache_Request_Key($sUri, $iContentVersionId);

				if (false != $this->fetch($oKey)) {

					$sKeyString = $this->_getKeyString($oKey);

					if (false == $this->delete($sKeyString)) {
						throw new FlexiCache_Exception('Couldn\'t delete item from memcache: ' . $sKeyString);
					}

				}

			}

			return true;

		} catch (FlexiCache_Exception $oE) {

			return false;

		}

	}

	public function purgeExpired ($iLimit=null)
	{

		/**
		* Use index if possible
		*/
		if ($this->_getIndex()->isAvailable()) {
			return $this->_getIndex()->purgeExpired($iLimit);
		}

		/**
		* Memcache looks after its own garbage collection
		*/
		return true;

	}

	public function purge ()
	{

		/**
		* Use index if possible
		*/
		if ($this->_getIndex()->isAvailable()) {
			return $this->_getIndex()->purge();
		}

		if (true == $this->_getConnection()->flush()) {
			return true;
		}

		return false;

	}

	public function getActivityArray ()
	{

		$aActivity = array ();

		$aActivity['Memcache server online'] = (true == $this->_getConnection())?'Yes':'No';

		$aActivity['Using SQLite index'] = (true == $this->_getIndex()->isAvailable())?'Yes':'No';

		if (false == $this->_getConnection()) {
			return $aActivity;
		}

		$aMemcacheStats = $this->_getConnection()->getStats();

		$aActivity['Storage limit'] = FlexiCache_Store_Metrics::getSizeString($aMemcacheStats['limit_maxbytes']);
		$aActivity['Storage used (by all sites using this Memcache server)'] = FlexiCache_Store_Metrics::getSizeString($aMemcacheStats['bytes']);

		$aActivity['Using SQLite index'] = (true == $this->_getIndex()->isAvailable())?'Yes':'No';

		if (false != ($oMetrics = $this->_getStoreMetrics())) {
			$aActivity = array_merge($aActivity, $oMetrics->getActivityArray());
		}

		if (0 < $aMemcacheStats['cmd_get']) {

			$aActivity['Memcache server total hits (% of total requests)'] = sprintf('%s (%s%%)',
				number_format($aMemcacheStats['get_hits']),
				round(($aMemcacheStats['get_hits']/$aMemcacheStats['cmd_get'])*100,2)
			);

		}

		return $aActivity;

	}

	/**
	* Return an object of type FlexiCache_Store_Metrics with information
	* about the current store, or false if no stats exist.
	*/
	private function _getStoreMetrics ()
	{

		if (false == FlexiCache_Store_Directory::exists(FlexiCache_Store_Directory::getHostPath())) {
			return false;
		}

		/**
		* Use index if possible
		*/
		if ($this->_getIndex()->isAvailable()) {
			return $this->_getIndex()->getStoreMetrics($iNumResponses, $iStoreSize, $sSource);
		}

		/**
		* Can't query Memcache directly
		*/
		return false;

	}

}
