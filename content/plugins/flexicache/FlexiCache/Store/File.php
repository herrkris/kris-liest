<?php

require_once 'Abstract.php';
require_once 'Interface.php';
require_once 'Exception.php';
require_once 'Directory.php';
require_once 'Index/Abstract.php';
require_once 'File/Index.php';

class FlexiCache_Store_File extends FlexiCache_Store_Abstract implements FlexiCache_Store_Interface {

	const DATA_FILE_EXTENSION = 'ser';

	/**
	* This can be checked more than once on a write()
	*/
	private $_aBucketPath = array();

	private $_oIndex;

	protected function _getIndex ()
	{

		if (null === $this->_oIndex) {
			$this->_oIndex = new FlexiCache_Store_File_Index;
		}

		return $this->_oIndex;

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

		return (true == $this->hasCheckFails())?false:true;

	}

	/**
	* Return the path to the bucket directory for the given key
	*/
	private function _getBucketPath ($oKey)
	{

		if (false == isset($this->_aBucketPath[(string)$oKey])) {

			$sBucket = substr((string) $oKey, 0, $this->_getConfig('BucketLevel'));
			$this->_aBucketPath[(string)$oKey] = join(DIRECTORY_SEPARATOR, array(FlexiCache_Store_Directory::getHostPath(), $sBucket));

		}

		return $this->_aBucketPath[(string)$oKey];

	}

	/**
	* Return the path to the cache file
	*/
	private function _getFilePath ($oKey)
	{
		$sFilename = (string) $oKey . '.' . self::DATA_FILE_EXTENSION;
		return join(DIRECTORY_SEPARATOR, array($this->_getBucketPath($oKey),$sFilename));
	}

	public function getNumCachedResponses ($sUri)
	{

		if ($this->_getIndex()->isAvailable()) {
			return $this->_getIndex()->getNumCachedResponses($sUri);
		}

		return false;

	}

	/**
	* Attempt to fetch a response object from the cache.
	* Return a FlexiCache_Response object or false if not found.
	*/
	public function fetch (FlexiCache_Request_Key $oKey)
	{

		try {

			if (false == file_exists($sFilePath = $this->_getFilePath($oKey))) {
				return false;
			}

			if (false == ($rFp = fopen($sFilePath,'r'))) {
				throw new FlexiCache_Store_Exception('Couldn\'t open file: ' . $sFilePath);
			}

			/**
			* Get read lock or return false.  Blocking lock waits for the file to be
			* ready rather than returning on failure, which would result in a new
			* dynamic page being served instead of of waiting for the cached version
			* to be ready
			*/
			if (false == flock($rFp, LOCK_SH)) {
				return false;
			}

			$sResponse = stream_get_contents($rFp);

			/**
			* Closing the file should release the lock automatically
			*/
			if (false == fclose($rFp)) {
				throw new FlexiCache_Store_Exception('Can\'t close file: ' . $sPath);
			}

			/**
			* In case stream_get_contents() returned false
			*/
			if (false == $sResponse) {
				return false;
			}

			/**
			* Response string should never be empty as it is a serialized object
			*/
			if (true == empty($sResponse)) {
				return false;
			}

			/**
			* Mute unserialize() errors and return false if response string is corrupted
			*/
			if (false == ($oResponse = @unserialize($sResponse))) {
				throw new FlexiCache_Exception('Response could not be unserialized');
			}

			return $oResponse;

		} catch (FlexiCache_Exception $oE) {

			return false;

		}

	}

	/**
	* Write response object to file
	*/
	public function write (FlexiCache_Request_Key $oKey, FlexiCache_Response $oResponse)
	{

		try {

			/**
			* Ensure host directory exists
			*/
			if (false == FlexiCache_Store_Directory::checkCreate(FlexiCache_Store_Directory::getHostPath())) {
				return false;
			}

			/**
			* Ensure bucket directory exists
			*/
			if (false == FlexiCache_Store_Directory::checkCreate($this->_getBucketPath($oKey))) {
				return false;
			}

			$sFilePath = $this->_getFilePath($oKey);

			if (true == file_exists($sFilePath)) {

				/**
				* If the file already exists, open in read+ mode which won't
				* automatically truncate it.
				*/
				if (false == ($rFp = @fopen($sFilePath, 'r+'))) {
					throw new FlexiCache_Store_Exception('Can\'t open file for read+: ' . $sFilePath);
				}

				/**
				* If we can't get a [non-blocking] READ lock we know that another
				* process is currently writing to the file, so close the file
				* pointer and return here.
				*/
				if (false == flock($rFp,LOCK_SH|LOCK_NB)) {
					fclose($rFp);
					return false;
				}

			} else {

				/**
				* Open file in write mode
				*/
				if (false == ($rFp = @fopen($sFilePath, 'w'))) {
					throw new FlexiCache_Store_Exception('Can\'t open file for writing: ' . $sFilePath);
				}

			}

			/**
			* Get a WRITE lock
			*/
			if (false == flock($rFp,LOCK_EX)) {
				throw new FlexiCache_Store_Exception('Can\'t get exclusive lock: ' . $sFilePath);
			}

			$sData = serialize($oResponse);
			$iDataSize = strlen($sData);

			/**
			* Try to write the index first and don't continue if it fails.
			* This avoids the possibility of having files in the filesystem
			* which do not exist in the index if an error occurs writing
			* the index after the file is written.  It's OK to have entries
			* in the index which don't exist in the filesystem as they'll
			* eventually be cleaned up automatically.
			*/
			if ($this->_getIndex()->isAvailable()) {

				if (false == $this->_getIndex()->update($oKey, $sFilePath, $oResponse, $iDataSize)) {
					fclose($rFp);
					return false;
				}

			}

			/**
			* Truncate the file to zero length.  This is required in case the file
			* existed previously.
			*/
			if (false == ftruncate($rFp, 0)) {
				fclose($rFp);
				throw new FlexiCache_Store_Exception('Can\'t truncate file: ' . $sFilePath);
			}

			/**
			* Write the data
			*/
			if (false == fwrite($rFp, $sData, $iDataSize)) {
				fclose($rFp);
				throw new FlexiCache_Store_Exception('Can\'t write to file: ' . $sFilePath);
			}

			/**
			* Closing the file should release the lock automatically
			*/
			if (false == fclose($rFp)) {
				throw new FlexiCache_Store_Exception('Can\'t close file: ' . $sFilePath);
			}

			return true;

		} catch (FlexiCache_Exception $oE) {

			return false;

		}

	}

	private function _openDir ($sDir)
	{

		try {

			if (false == ($rDp = opendir($sDir))) {
				throw new FlexiCache_Store_Exception(__METHOD__ . '() can\'t open directory for reading: ' . $sDir);
			}

			return $rDp;

		} catch (FlexiCache_Exception $oE) {

			return false;

		}

	}

	public function purgeUri ($sUri)
	{

		try {

			/**
			* If the host directory path doesn't exist, there's nothing to purge
			*/
			if (false == FlexiCache_Store_Directory::exists(FlexiCache_Store_Directory::getHostPath())) {
				return false;
			}

			/**
			* Use index if possible
			*/
			if ($this->_getIndex()->isAvailable()) {
				return $this->_getIndex()->purgeUri($sUri);
			}

			/**
			* Generate a key to identify the default version for this URI
			*/
			$oKey = new FlexiCache_Request_Key($sUri, null);

			/**
			* Get file hash and bucket directory from key
			*/
			$sFilePathPrefix = substr($oKey,0,32);
			$sBucketPath = $this->_getBucketPath($oKey);

			/**
			* If the file has not yet been cached, the bucket directory will not exist
			*/
			if (false == FlexiCache_Store_Directory::exists($sBucketPath)) {
				return false;
			}

			/**
			* If we can't open the directory, return false.
			*/
			if (false == ($rDpBucket = $this->_openDir($sBucketPath))) {
				return false;
			}

			while (false !== ($sFile = readdir($rDpBucket))) {

				/**
				* Match any file which begins with the URI's hash, to ensure all
				* versions of the file are removed
				*/
				if ($sFilePathPrefix == substr($sFile,0,32)) {

					$sFilePath = join(DIRECTORY_SEPARATOR,array($sBucketPath,$sFile));

					if (false == unlink($sFilePath)) {
						throw new FlexiCache_Store_Exception(__METHOD__ . '() can\'t delete file: ' . $sFilePath);
					}

				}

			}

			closedir($rDpBucket);

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
		* Otherwise don't bother - it's too costly to look inside all
		* the items on the filesystem and determine if they've expired.
		*/
		return false;

	}

	public function purge ()
	{

		/**
		* If the host directory path doesn't exist, there's nothing to purge
		*/
		if (false == FlexiCache_Store_Directory::exists($sHostPath = FlexiCache_Store_Directory::getHostPath())) {
			return false;
		}

		/**
		* Use index if possible
		*/
		if ($this->_getIndex()->isAvailable()) {
			return $this->_getIndex()->purge();
		}

		$rDp = $this->_openDir($sHostPath);

		while (false !== ($sBucket = readdir($rDp))) {

			if ('.' != substr($sBucket,0,1)) {

				$sBucketPath = join(DIRECTORY_SEPARATOR,array($sHostPath,$sBucket));

				if (is_dir($sBucketPath)) {
					$this->_purgeBucketDir($sBucketPath);
				}

			}

		}

		return true;

	}

	private function _purgeBucketDir ($sBucketPath)
	{

		try {

			if (false == ($rDpBucket = opendir($sBucketPath))) {
				throw new FlexiCache_Store_Exception(__METHOD__ . '() can\'t open bucket directory for reading: ' . $sBucketPath);
			}

			while (false !== ($sFile = readdir($rDpBucket))) {

				if ('.' != substr($sFile,0,1)) {

					$sFilePath = join(DIRECTORY_SEPARATOR,array($sBucketPath,$sFile));

					if (false == unlink($sFilePath)) {
						throw new FlexiCache_Store_Exception(__METHOD__ . '() can\'t delete file: ' . $sFilePath);
					}

				}

			}

			closedir($rDpBucket);

			if (false == rmdir($sBucketPath)) {
				throw new FlexiCache_Store_Exception(__METHOD__ . '() can\'t delete bucket directory: ' . $sBucketPath);
			}

			return true;

		} catch (FlexiCache_Exception $oE) {

			return false;

		}

	}

	public function getActivityArray ()
	{

		$aActivity = array ();

		$bDirExists = FlexiCache_Store_Directory::exists($sHostPath = FlexiCache_Store_Directory::getHostPath());

		$aActivity['Host directory exists'] = (true == $bDirExists)?'Yes':'No';

		if (false == $bDirExists) {
			return $aActivity;
		}

		$aActivity['Using SQLite index'] = (true == $this->_getIndex()->isAvailable())?'Yes':'No';

		if (false != ($oMetrics = $this->_getStoreMetrics())) {
			$aActivity = array_merge($aActivity, $oMetrics->getActivityArray());
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
			return $this->_getIndex()->getStoreMetrics();
		}

		if (false == class_exists('RecursiveIteratorIterator')) {
			return false;
		}

		$iNumResponses = $iSize = 0;

		$oMetrics = new FlexiCache_Store_Metrics;

		foreach(new RecursiveIteratorIterator(new RecursiveDirectoryIterator(FlexiCache_Store_Directory::getHostPath())) as $oFile){

			if ((true == $oFile->isFile()) && (true == preg_match('#\.'.self::DATA_FILE_EXTENSION.'#', $oFile->getFilename()))) {

				$iSize += $oFile->getSize();
				++$iNumResponses;

			}

		}

		$oMetrics
			->setNumResponses($iNumResponses)
			->setSize($iSize)
			->setSource('Filesystem')
		;

		return $oMetrics;

	}

}
