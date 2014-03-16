<?php

require_once 'Index/SqliteDb.php';

class FlexiCache_Store_File_Index extends FlexiCache_Store_Index_Abstract implements FlexiCache_Store_Index_Interface {

	protected function _getNewDb ()
	{
		return new FlexiCache_Store_File_Index_SqliteDb;
	}

	protected function _isCached ($sIndexKey)
	{

		if (false == file_exists($sIndexKey)) {
			return false;
		}

		return true;

	}

	protected function _delete ($sIndexKey)
	{

		if (false == unlink($sIndexKey)) {
			return false;
		}

		return true;

	}

}
