<?php

abstract class FlexiCache_Store_Index_Abstract {

	protected $_oDb;

	protected function _getDb ()
	{

		if (null === $this->_oDb) {
			$this->_oDb = $this->_getNewDb();
		}

		return $this->_oDb;

	}

	/**
	* Return boolean to indicate whether the an index is available.
	* At some point this file may support other kinds of index,
	* but for now, just return the result of FlexiCache_SqliteDb::libraryExists()
	*/
	public function isAvailable ()
	{
		return FlexiCache_SqliteDb::libraryExists();
	}

	public function getNumCachedResponses ($sUri)
	{

		/**
		* Select any fresh responses for $sUri
		*/
		$sSql = sprintf("SELECT key FROM response WHERE uri='%s' AND expires>%d",
			sqlite_escape_string($sUri),
			time()
		);

		$rResult = $this->_getDb()->query($sSql);

		return sqlite_num_rows($rResult);

	}

	/**
	* Update the index for $oResponse
	*/
	public function update ($oKey, $sIndexKey, $oResponse, $iDataSize)
	{

		try {

			/**
			* Begin transaction
			*/
			$this->_getDb()->query('BEGIN TRANSACTION');

			/**
			* Remove any existing entry before insert
			*/
			$rResult = $this->_getDb()->query(sprintf("DELETE FROM response WHERE key='%s'",
				sqlite_escape_string($sIndexKey)
			));

			/**
			* INSERT new entry
			*/
			$rResult = $this->_getDb()->query(sprintf("INSERT INTO response (key,uri,version,expires,size) VALUES ('%s','%s','%d','%d','%d')",
				sqlite_escape_string($sIndexKey),
				sqlite_escape_string($oKey->getUri()),
				sqlite_escape_string($oKey->getContentVersionConditionId()),
				sqlite_escape_string($oResponse->getExpiresTimestamp()),
				$iDataSize
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

	public function purgeExpired ($iLimit=null)
	{

		try {

			/**
			* Select responses whose expiry is outside of the allowed extension
			*/
			$sSql = sprintf("SELECT key FROM response WHERE expires<%d ORDER BY expires DESC",
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

			return $this->_deleteKeysByQueryResult($rResult);

		} catch (FlexiCache_Exception $oE) {

			return false;

		}

	}

	/**
	* Select items from the index matching URI for deletion. On successful
	* deletion from storage, also delete from database
	*/
	public function purgeUri ($sUri)
	{

		try {

			/**
			* Select any responses matching the URI
			*/
			$rResult = $this->_getDb()->query(sprintf("SELECT key FROM response WHERE uri='%s'",
				sqlite_escape_string($sUri)
			));

			if (0 == sqlite_num_rows($rResult)) {
				return false;
			}

			return $this->_deleteKeysByQueryResult($rResult);

		} catch (FlexiCache_Exception $oE) {

			return false;

		}

	}

	public function purge ()
	{

		try {

			$rResult = $this->_getDb()->query('SELECT key FROM response');

			if (0 == sqlite_num_rows($rResult)) {
				return false;
			}

			if (true == $this->_deleteKeysByQueryResult($rResult)) {

				/**
				* Reset database
				* http://www.sqlite.org/lang_vacuum.html
				*/
				$rResult = $this->_getDb()->query('VACUUM');

				return true;

			}

			return false;

		} catch (FlexiCache_Exception $oE) {

			return false;

		}

	}

	protected function _deleteKeyFromIndex ($sIndexKey)
	{

		$sSql = sprintf("DELETE FROM response WHERE key='%s'",
			sqlite_escape_string($sIndexKey)
		);

		$this->_getDb()->query($sSql);

	}

	/**
	* See Interface
	*/
	public function getStoreMetrics ()
	{
		return FlexiCache_Store::getSqliteStoreMetrics($this->_getDb(), 'SQLite index');
	}

	protected function _isCached ($IndexKey)
	{
		throw new FlexiCache_Exception(__METHOD__ . ' must be extended by a specific index class');
	}

	protected function _delete ($sIndexKey)
	{
		throw new FlexiCache_Exception(__METHOD__ . ' must be extended by a specific index class');
	}

	/**
	* Delete responses relating to keys found in $rResult, removing the rows
	* from the databass if the delete succeeds
	*/
	protected function _deleteKeysByQueryResult ($rResult)
	{

		/**
		* Begin transaction
		*/
		$this->_getDb()->query('BEGIN TRANSACTION');

		while (false != ($sIndexKey = sqlite_fetch_single($rResult))) {

			if (false == $this->_isCached($sIndexKey)) {

				/**
				* If the key doesn't exist in the cache, remove it from the index
				*/
				$this->_deleteKeyFromIndex($sIndexKey);

			} else {

				try {

					/**
					* If the cache item exists but couldn't be deleted, throw an exception
					*/
					if (false == $this->_delete($sIndexKey)) {
						throw new FlexiCache_Exception ("Couldn't delete indexed key: " . $sIndexKey);
					}

					/**
					* Otherwise remove it from the index
					*/
					$this->_deleteKeyFromIndex($sIndexKey);

				} catch (FlexiCache_Exception $oE) {

					/**
					* Nothing needs to happen here so long as the exception
					* class has logged the error.
					*/

				}

			}

		}

		/**
		* Commit
		*/
		$this->_getDb()->query('COMMIT');

		return true;

	}

}
