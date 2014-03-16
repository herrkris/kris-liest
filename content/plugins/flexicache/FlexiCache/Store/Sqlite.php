<?php

require_once 'Abstract.php';
require_once 'Interface.php';
require_once 'Exception.php';
require_once 'Directory.php';
require_once 'Sqlite/SqliteDb.php';

class FlexiCache_Store_Sqlite extends FlexiCache_Store_Abstract implements FlexiCache_Store_Interface {

	private $_oDb;

	private function _getDb ()
	{

		if (null === $this->_oDb) {
			$this->_oDb = new FlexiCache_Store_Sqlite_SqliteDb;
		}

		return $this->_oDb;

	}

	public function check ()
	{

		/**
		* Abstract method contains data directory checks
		*/
		parent::check();

		/**
		* Check for library availability
		*/
		if (false == FlexiCache_SqliteDb::libraryExists()) {
			$this->_addCheckFail('SQLite library is not available on this system');
		}

		if ($this->hasCheckFails()) {
			$this->_setIsEnabled(false);
		}

		return (true == $this->hasCheckFails())?false:true;

	}


	public function getNumCachedResponses ($sUri)
	{

		/**
		* Select any response matching the key, regardless of expiry time
		*/
		$sSql = sprintf("SELECT version FROM response WHERE uri='%s' AND expires>%d",
			$sUri,
			time()
		);

		$rResult = $this->_getDb()->query($sSql);

		return sqlite_num_rows($rResult);

	}


	public function fetch (FlexiCache_Request_Key $oKey)
	{

		try {

			/**
			* Select any response matching the key, regardless of expiry time
			*/
			$rResult = $this->_getDb()->query(sprintf("SELECT object FROM response WHERE uri='%s' AND version='%d' LIMIT 1",
				sqlite_escape_string($oKey->getUri()),
				sqlite_escape_string($oKey->getContentVersionConditionId())
			));

			if (1 !== sqlite_num_rows($rResult)) {
				return false;
			}

			$sResponse = sqlite_fetch_single($rResult);

			if (false == ($oResponse = @unserialize($sResponse))) {
				throw new FlexiCache_Exception('Response could not be unserialized');
			}

			return $oResponse;

		} catch (FlexiCache_Exception $oE) {

			return false;

		}

	}

	public function write (FlexiCache_Request_Key $oKey, FlexiCache_Response $oResponse)
	{

		try {

			/**
			* Begin transaction
			*/
			$this->_getDb()->query('BEGIN TRANSACTION');

			/**
			* Remove any existing entry before insert
			*/
			$rResult = $this->_getDb()->query(sprintf("DELETE FROM response WHERE uri='%s' AND version='%d'",
				sqlite_escape_string($oKey->getUri()),
				sqlite_escape_string($oKey->getContentVersionConditionId())
			));

			$sResponse = serialize($oResponse);

			/**
			* INSERT new entry
			*/
			$rResult = $this->_getDb()->query(sprintf("INSERT INTO response (uri,version,expires,object,size) VALUES ('%s','%d','%d','%s','%d')",
				sqlite_escape_string($oKey->getUri()),
				sqlite_escape_string($oKey->getContentVersionConditionId()),
				sqlite_escape_string($oResponse->getExpiresTimestamp()),
				sqlite_escape_string($sResponse),
				strlen($sResponse)
			));

			/**
			* Commit
			*/
			$this->_getDb()->query('COMMIT');

			return true;

		} catch (FlexiCache_Exception $oE) {

			return false;

		}

	}

	public function purge ()
	{

		try {

//			$rResult = $this->_getDb()->query('DELETE FROM response');

			/**
			* Drop table and rebuild instead of DELETE to enable change of
			* schema between plugin versions.
			*/
			$rResult = $this->_getDb()->query('DROP TABLE response');
			$rResult = $this->_getDb()->query($this->_getDb()->getSchema());

			/**
			* Reset database
			* http://www.sqlite.org/lang_vacuum.html
			*/
			$this->_getDb()->query('VACUUM');

			return true;

		} catch (FlexiCache_Exception $oE) {

			return false;

		}

	}

	public function purgeExpired ($iLimit=null)
	{

		try {

			/**
			* Begin transaction
			*/
			$this->_getDb()->query('BEGIN TRANSACTION');

			/**
			* Ideally we would use:
			* "DELETE FROM response WHERE expires<%d ORDER BY expires DESC LIMIT %d"
			* but "ORDER BY" and "LIMIT" are optionally compiled into in SQLite for
			* DELETE clauses so it's best to assume they're not there and do it
			* the long way.
			*/
			$sSql = sprintf("SELECT uri, version FROM response WHERE expires<%d ORDER BY expires DESC",
				time()-FlexiCache::STORE_TIMEOUT_EXTENSION
			);

			if (null != $iLimit) {
				$sSql .= sprintf(" LIMIT %d", $iLimit);
			}

			$rResult = $this->_getDb()->query($sSql);

			/**
			* Return true if there's nothing to delete as this can be considered
			* a success
			*/
			if (0 == sqlite_num_rows($rResult)) {
				return true;
			}

			while (false != ($aRow = @sqlite_fetch_array($rResult))) {

				$sSql = sprintf("DELETE FROM response WHERE uri='%s' AND version='%d'",
					sqlite_escape_string($aRow['uri']),
					sqlite_escape_string($aRow['version'])
				);

				$this->_getDb()->query($sSql);

			}

			$this->_getDb()->query('COMMIT');

			return true;

		} catch (FlexiCache_Exception $oE) {

			return false;

		}

	}

	public function purgeUri ($sUri)
	{

		try {

			$rResult = $this->_getDb()->query(sprintf("DELETE FROM response WHERE uri='%s'",
				sqlite_escape_string($sUri)
			));

		} catch (FlexiCache_Exception $oE) {

			return false;

		}

		return true;

	}

	public function getActivityArray ()
	{

		$aActivity = array();

		$bDbExists = (false == file_exists($this->_getDb()->getPath()))?false:true;

		$aActivity['Database exists'] = (true == $bDbExists)?'Yes':'No';
		$aActivity['SQlite library encoding'] = sqlite_libencoding();

		if (false == $bDbExists) {
			return $aActivity;
		}

		if (false != ($oMetrics = $this->_getStoreMetrics())) {
			$aActivity = array_merge($aActivity, $oMetrics->getActivityArray());
		}

		return $aActivity;

	}

	private function _getStoreMetrics ()
	{
		return FlexiCache_Store::getSqliteStoreMetrics($this->_getDb(), 'SQlite');
	}

}
