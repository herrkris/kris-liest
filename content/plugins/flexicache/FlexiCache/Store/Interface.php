<?php

interface FlexiCache_Store_Interface {

	/**
	* Check that the storage engine has everything required for it to
	* work, such as permissions.  If not, this method should disable the
	* engine by calling $this->_setIsEnabled(false)
	*/
	public function check ();

	/**
	* Return the number of cached responses for $sUri
	*/
	public function getNumCachedResponses ($sUri);

	/**
	* Fetch a response identified by $oKey from the store, regardless
	* of expiry time.
	*/
	public function fetch (FlexiCache_Request_Key $oKey);

	/**
	* Write $oResponse to the store, indexed by $oKey
	*/
	public function write (FlexiCache_Request_Key $oKey, FlexiCache_Response $oResponse);

	/**
	* Delete all cached items for the current host
	*/
	public function purge ();

	/**
	* Delete expired items, up to a maximum of $iLimit.  If $iLimit
	* is null, delete all expired items
	*/
	public function purgeExpired ($iLimit=null);

	/**
	* Delete all cached items for the supplied URI for the current host
	*/
	public function purgeUri ($sUri);

	/**
	* Return a string indicating how many files are cached, and anything
	* else appropriate, for display in the admin area
	*/
	public function getActivityArray();

}
