<?php

interface FlexiCache_Store_Index_Interface {

	/**
	* Return boolean to indicate whether the index is available
	* for use
	*/
	public function isAvailable();

	/**
	* Return an integer representing the number of fresh cached responses
	*/
	public function getNumCachedResponses ($sUri);

	/**
	* Update the index
	*/
	public function update ($oKey, $sIndexKey, $oResponse, $iDataSize);

	/**
	* Delete expired items from the index and associated store
	*/
	public function purgeExpired ($iLimit=null);

	/**
	* Delete items relating to $SuRI from the index and associated store
	*/
	public function purgeUri ($sUri);

	/**
	* Delete all items from the store
	*/
	public function purge ();

	/**
	* Return an object of type FlexiCache_Store_Metrics with information
	* about the current store index
	*/
	public function getStoreMetrics ();

}
