<?php

abstract class FlexiCache_SqliteDb {

	/**
	* DB connection
	*/
	protected $_rConnection;

	/**
	* Directory path for the index file
	*/
	protected $_sDbDir;

	/**
	* Return boolean to indicate whether or not the SQLite
	* library exists on this system
	*/
	public static function libraryExists ()
	{

		if (true == function_exists('sqlite_open')) {
			return true;
		}

		return false;

	}

	/**
	* Return the path to the directory where the database file should be stored
	*/
	protected function _getDbDir ()
	{

		if (null === $this->_sDbDir) {
			$this->_sDbDir = join(DIRECTORY_SEPARATOR,array(FlexiCache_Config::get('Main','CacheDir'),FlexiCache::getSiteHost()));
		}

		return $this->_sDbDir;

	}

	public function getPath ()
	{

		if (false == FlexiCache_Store_Directory::checkCreate($this->_getDbDir())) {
			throw new FlexiCache_Exception('Directory doesn\'t exist and couldn\'t be created: ' . $this->_getDbDir());
		}

		return join(DIRECTORY_SEPARATOR,array($this->_getDbDir(),$this->_sFilename));

	}

	/**
	* Perform a query, throwing an exception on failure
	*/
	public function query ($sSql)
	{

		$rQuery = @sqlite_query($this->_getConnection(),$sSql);

		if (false === $rQuery) {

			throw new FlexiCache_Exception(sprintf("sqlite error: %s [query: %s]",
				$this->getLastErrorString(),
				$sSql
			));

		}

		return $rQuery;

	}

	public function getSchema ()
	{
		return $this->_sDbSchema;
	}

	public function getLastErrorString ()
	{
		return sqlite_error_string(sqlite_last_error($this->_getConnection()));
	}

	/**
	* Return a connection to an SQLite database, creating one
	* if it doesn't yet exist
	*/
	protected function _getConnection ()
	{

		if (null === $this->_rConnection) {

			$sDbPath = $this->getPath();

			/**
			* Note whether the database file exists
			*/
			$bDbExists = (true == file_exists($sDbPath))?true:false;

			/**
			* sqlite_open() will create the database file if it doesn't already exist
			*/
			if (false == $rDb = sqlite_open($sDbPath)) {
				throw new FlexiCache_Store_Exception("Can't open sqlite database: " . $sDbPath);
			}

			$this->_rConnection = $rDb;

			if (false == $bDbExists) {

				/**
				* If it didn't exist before sqlite_open(), the schema won't exist,
				* so create it here
				*/

				$rResult = sqlite_query($this->_getConnection(), $this->_sDbSchema);

				if (false === $rResult) {
					throw new FlexiCache_Store_Exception("Can't create schema");
				}

			}

		}

		return $this->_rConnection;

	}

}
