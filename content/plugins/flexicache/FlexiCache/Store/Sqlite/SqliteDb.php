<?php

class FlexiCache_Store_Sqlite_SqliteDb extends FlexiCache_SqliteDb {
	protected $_sDbSchema = 'CREATE TABLE response (uri CHAR(512), version INTEGER, expires INTEGER, object BLOB, size INTEGER, PRIMARY KEY (uri,version)); CREATE INDEX fetch ON response(uri,version,expires); CREATE INDEX expires ON response(expires);';
	protected $_sFilename = 'response.db';
}
