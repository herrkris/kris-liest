<?php

require_once 'Index/SqliteDb.php';

class FlexiCache_Store_Memcache_Index extends FlexiCache_Store_Index_Abstract implements FlexiCache_Store_Index_Interface {

	protected function _getNewDb ()
	{
		return new FlexiCache_Store_Memcache_Index_SqliteDb;
	}

	protected function _isCached ($sIndexKey)
	{

		if (false == FlexiCache_Store::factory(FlexiCache_Store::CLASS_MEMCACHE)->getByKeyString($sIndexKey)) {
			return false;
		}

		return true;

	}

	protected function _delete ($sIndexKey)
	{

		if (false == FlexiCache_Store::factory(FlexiCache_Store::CLASS_MEMCACHE)->delete($sIndexKey)) {
			return false;
		}

		return true;

	}

}
