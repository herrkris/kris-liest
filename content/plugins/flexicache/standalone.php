<?php

/**
* Attempt to serve a page directly from FlexiCache without including any WordPress
* files.  If no page is served, continue through to serve via WordPress as usual
*/

require_once 'FlexiCache.php';

/**
* Define WP_PLUGIN_DIR here to save including any WordPress files
*/
define('WP_PLUGIN_DIR', join(DIRECTORY_SEPARATOR,array($_SERVER['DOCUMENT_ROOT'],'wp-content','plugins')));

FlexiCache::initStandalone();

/**
* If the file wasn't served above we continue to generate the page in WordPress
* and cache appropriately.  Since we know there isn't a version already cached
* we can save some time by disabling checking in plugin mode.
*/
FlexiCache::setIsServable(false);

chdir($_SERVER['DOCUMENT_ROOT']);
require_once('index.php');
