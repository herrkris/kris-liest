<?php

class FlexiCache_Store_Memcache_Index_SqliteDb extends FlexiCache_SqliteDb {
	protected $_sDbSchema = 'CREATE TABLE response (key CHAR (1024), uri CHAR(512), version INTEGER, expires INTEGER, size INTEGER, PRIMARY KEY (key)); CREATE INDEX uri ON response(uri); CREATE INDEX version ON response(version); CREATE INDEX expires ON response(expires);';
	protected $_sFilename = 'memcache-index.db';
}
