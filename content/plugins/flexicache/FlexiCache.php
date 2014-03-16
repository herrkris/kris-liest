<?php

require_once 'FlexiCache/Wp.php';
require_once 'FlexiCache/Config.php';
require_once 'FlexiCache/Exception.php';
require_once 'FlexiCache/Request.php';
require_once 'FlexiCache/Response.php';
require_once 'FlexiCache/SqliteDb.php';
require_once 'FlexiCache/Store.php';

/**
* Set this so PHP's strict mode doesn't complain
*/
date_default_timezone_set('Europe/London');

class FlexiCache {

	/**
	* Pages can be served as a WordPress plugin or in standalone mode
	*/
	const SERVE_MODE_WP_PLUGIN	= 1;
	const SERVE_MODE_STANDALONE	= 2;

	/**
	* Location for user data to be stored
	*/
	const DEFAULT_DATA_DIR = '_data';

	/**
	* Parameter passed to mt_rand() to determine whether a request
	* should trigger a clean-up of expired responses.
	*/
	const AVERAGE_REQUESTS_BEFORE_CLEANUP = 1000;

	/**
	* Maximum number of expired responses to delete on cleanup
	*/
	const EXPIRED_ITEMS_TO_DELETE_ON_CLEANUP = 200;

	/**
	* Initial time to wait for an uncached build to complete (seconds)
	*/
	const BUILD_SLEEP_TIME = 0.5;

	/**
	* Sleep time multiplier for successive uncached build checks
	*/
	const BUILD_SLEEP_TIME_MULTIPLIER = 1.25;

	/**
	* The maximum reasonable time we expect a page build to take (seconds)
	*/
	const MAX_EXPECTED_BUILD_TIME = 60;

	/**
	* Temporary text to store on in a response when it's being built for the
	* first time.  This is never served but indicates to subsequent processes
	* that the response is currently being built and there is no stale copy
	* to serve in the meantime.  Random code is just there to provide uniqueness.
	*/
	const FIRST_BUILD_INDICATOR = '<!-- first build 14e21e156f3d8ce8f0224404428de4f8 -->';

	/**
	* How much longer a store should hold a response after the response expires,
	* so that a stale response can be served in certain conditions
	*/
	const STORE_TIMEOUT_EXTENSION = 86400; // One day

	/**
	* Default is to store and serve cached items rather than not
	*/
	private static $_bStorable = true;
	private static $_bServable = true;

	/**
	* The store object
	*/
	private static $_oStore;

	/**
	* Current serve mode
	*/
	private static $_iServeMode;

	/**
	* Request and Response objects
	*/
	private static $_oRequest;
	private static $_oResponse;

	/**
	* Response body is stored here until Response object can be initialized
	*/
	private static $_sResponseBody;

	/**
	* Keep a note of how long things take
	*/
	private static $_iTimer;

	/**
	* Start in WP plugin mode
	*/
	public static function initWpPlugin ()
	{
		self::_setServeMode(self::SERVE_MODE_WP_PLUGIN);
		self::_main();
	}

	/**
	* Start in standalone mode
	*/
	public static function initStandalone ()
	{
		self::_setServeMode(self::SERVE_MODE_STANDALONE);
		self::_main();
	}

	/**
	* Main functionality, called either from initWpPlugin() or initStandalone()
	*/
	private static function _main ()
	{

		/**
		* If the plugin is disabled in its config, return here
		*/
		if (false == FlexiCache_Config::get('Main', 'Enabled')) {
			return;
		}

		/**
		* Security restriction: If the request is not GET, return here
		*/
		if ('GET' != $_SERVER['REQUEST_METHOD']) {
			return;
		}

		/**
		* Start the timer
		*/
		self::_startTimer();

		/**
		* Create a request object
		*/
		self::_setRequest(new FlexiCache_Request);

		/**
		* If we're running as a WordPress plugin, do the WordPress
		* security checks and return here if required.
		*/
		if (self::SERVE_MODE_WP_PLUGIN == self::getServeMode()) {

			if (true == FlexiCache_Wp::getDisableCaching()) {
				return;
			}

		}

		/**
		* Process any configured no-serve conditions
		*/
		if (false !== FlexiCache_Config::get('Main', 'ConditionSet_Serve')->validateAny()) {
			self::setIsServable(false);
		}

		/**
		* See if there's a valid (but not necessarily fresh) cached response
		*/
		$oResponse = self::_getValidResponse();

		/**
		* If the request is servable
		*/
		if (true == self::_getIsServable()) {

			/**
			* If there is a response, and it's fresh, and it's not the first build indicator, serve it.
			*/
			if ((false != $oResponse) && (false == $oResponse->hasExpired()) && (0 !== strcmp($oResponse->getBody(),self::FIRST_BUILD_INDICATOR))) {

				/**
				* Add a comment to the response object
				*/
				$oResponse->addComment(self::_getResponseServeComment());

				/**
				* Get the pre-cache time from the config and work out how long
				* is left before the response expires
				*/
				$iPreCacheTime = FlexiCache_Config::get('Main', 'PreCacheTime');
				$iTimeLeft = $oResponse->getExpiresTimestamp() - time();

				if (($iPreCacheTime > 0) && ($iTimeLeft < $iPreCacheTime) && (false == $oResponse->getHeaders()->hasComponent(FlexiCache_Headers::CUSTOM_CONTROL_KEY,'no-pre-cache'))) {

					/**
					* If pre-caching is enabled in config and not disabled in a custom control header,
					* and the cached entity is about to expire, send the response, close
					* the connection and don't exit so the script continues to run and cache
					* a new copy.
					*/

					self::_sendCloseFlush($oResponse);

				} else if (1 == mt_rand(1,self::AVERAGE_REQUESTS_BEFORE_CLEANUP)) {

					/**
					* Send the response, close the connection, clean up some
					* expired items, and exit.
					*/

					self::_sendCloseFlush($oResponse);
					self::_getStore()->purgeExpired(self::EXPIRED_ITEMS_TO_DELETE_ON_CLEANUP);
					exit;

				} else {

					/**
					* Pre-caching is not enabled or the cached entity has
					* plenty of time left, and mt_rand() didn't trigger
					* a cleanup.  So just send the response and exit.
					*/

					$oResponse->send();
					exit;

				} /* End pre-caching and clean-up conditions */

			} /* End if there's a valid cached response */

		} /* End if the request is servable */

		/**
		* If running in standalone mode, there will be nothing to store so return here.
		*/
		if (self::SERVE_MODE_STANDALONE == self::getServeMode()) {
			return;
		}

		/**
		* Process any configured no-store conditions
		*/
		if (false !== FlexiCache_Config::get('Main', 'ConditionSet_Store')->validateAny()) {
			self::_setIsStorable(false);
			return;
		}

		/**
		* If the response is currently being built for the first time
		*/
		if ((false != $oResponse) && (0 === strcmp($oResponse->getBody(),self::FIRST_BUILD_INDICATOR))) {

			/**
			* If 503 "Service Unavailable" responses are enabled, or if an error occurs while
			* waiting for the current build to complete, send a 503 and exit
			*/
			if ((true == FlexiCache_Config::get('Main','ServiceUnavailableResponseEnabled')) || (false == ($oResponse = self::_waitForFirstBuild()))) {
				self::_sendServiceUnavailableResponse();
				exit;
			}

			/**
			* Add a comment to the response object we waited for and send
			*/
			$oResponse->addComment(self::_getResponseServeComment());
			$oResponse->send();

			exit;

		} /* End if the response is currently being built for the first time */

		/**
		* If the response is storable
		*/
		if (true == self::getIsStorable()) {

			$bIsFirstBuild = ((false == isset($oResponse)) || (false == $oResponse))?true:false;

			/**
			* If there was no existing stored response, create a temporary one
			* with FIRST_BUILD_INDICATOR content
			*/
			if (true == $bIsFirstBuild) {
				$oResponse = new FlexiCache_Response;
				$oResponse->setBody(self::FIRST_BUILD_INDICATOR);
			}

			/**
			* If this is a first build, or "must-revalidate" has not been specifically
			* set in a custom control header
			*/
			if (true == $bIsFirstBuild || (false == $oResponse->getHeaders()->hasComponent(FlexiCache_Headers::CUSTOM_CONTROL_KEY,'must-revalidate'))) {

				/**
				* Create a temporary response timestamp to allow time for a new build
				* and write the response object to prevent other processes
				* from rebuilding at the same time.
				*/

				$iTempExpiresTimestamp = time() + self::_getExpiresExtensionSeconds();
				$oResponse->setExpiresTimestamp($iTempExpiresTimestamp);
				$oResponse->getHeaders()->set('Expires', self::getDateTimeString($iTempExpiresTimestamp));

				self::_getStore()->write(self::getRequest()->getKey(),$oResponse);

			}

			/**
			* Start output buffering, register a callback to storeResponseBody(),
			* and register write() to be run on PHP shutdown.
			*/
			ob_start(array(__CLASS__,'storeResponseBody'));
			add_action('shutdown', array(__CLASS__,'write'));

		} /* End if the response is storable */

	}

	/**
	* Return text for the HTML comment to be added when a response is served
	*/
	private static function _getResponseServeComment ()
	{

		return sprintf("This file was served in %0.3f seconds (serve mode: %s, content version id: %d)",
			round(microtime(true)-self::getTimer(),3),
			self::getServeModeString(),
			self::getRequest()->getKey()->getContentVersionConditionId()
		);

	}

	/**
	* Wait for a response other than a FIRST_BUILD_INDICATOR to be written
	* and return the response object
	*/
	private static function _waitForFirstBuild ()
	{

		/**
		* Initialize with default sleep time
		*/
		$iCurrentSleepMs = self::BUILD_SLEEP_TIME * 1000000;
		$iTotalSleepMs = 0;

		try {

			do {

				/**
				* Wait for a successively longer duration on each iteration rather
				* than continuing to poll in quick succession.
				*/
				usleep($iCurrentSleepMs);
				$iTotalSleepMs += $iCurrentSleepMs;
				$iCurrentSleepMs = (int) ($iCurrentSleepMs * self::BUILD_SLEEP_TIME_MULTIPLIER);

				/**
				* If we ever get to the point where we have waited for more than
				* self::MAX_EXPECTED_BUILD_TIME, give up, remove the lock file, and report an exception.
				*/
				if ($iTotalSleepMs > (self::MAX_EXPECTED_BUILD_TIME * 1000000)) {
					throw new FlexiCache_Exception('Build sleep time exceeded maximum defined duration: ' . self::MAX_EXPECTED_BUILD_TIME);
				}

				if (false == ($oResponse = self::_getValidResponse())) {
					throw new FlexiCache_Exception('Invalid response was returned');
				}

			} while (0 === strcmp($oResponse->getBody(),self::FIRST_BUILD_INDICATOR));

			return $oResponse;

		} catch (FlexiCache_Exception $oE) {

			return false;

		}

	}

	/**
	* Fetch a response and return it only if it's valid according to several tests.
	* Otherwise return false
	*/
	private static function _getValidResponse ()
	{

		$oResponse = self::_getStore()->fetch(self::getRequest()->getKey());

		if (false == ($oResponse instanceof FlexiCache_Response)) {

			/**
			* Not a response object, probably false (not found)
			*/

		} else if (false == $oResponse->getIsValid()) {

			/**
			* Invalid response, probably created with an out-of-date
			* version of the Response class
			*/

		} else if (self::getRequest()->getUri() != $oResponse->getRequestUri()) {

			/**
			* Current request URI does not match response's request URI.
			* Very unlikely but worth checking in case of md5 clash in
			* File storage, for example.
			*/

		} else {

			return $oResponse;

		}

		return false;

	}

	/**
	* Send a "Connection: close" header and flush all buffers after sending the
	* response.
	*/
	private static function _sendCloseFlush ($oResponse)
	{

		$oResponse->getHeaders()->set('Connection','close');
		$oResponse->send();

		/**
		* If output buffering is enabled, flush it
		*/
		if (0 != ob_get_level()) {
			ob_end_flush();
			ob_flush();
		}

		flush();

	}

	/**
	* Make a temporary copy of the response body until we are ready
	* to create a Response object.
	*/
	public static function storeResponseBody ($sData)
	{
		self::$_sResponseBody = $sData;
		return $sData;
	}

	/**
	* Process the custom control header (if present) which may change the
	* caching behaviour.  Remove this headers after processing so it is
	* not stored on the response object.
	*/
	private static function _processCustomControlHeader ()
	{

		/**
		* If a max-age header is set, set the expiry time using that value
		*/
		if (null !== ($iExpiresSeconds = self::getResponse()->getHeaders()->getComponent(FlexiCache_Headers::CUSTOM_CONTROL_KEY,'max-age'))) {
			self::getResponse()->setExpiresTimestamp(time() + $iExpiresSeconds);
		}

	}

	/**
	* Write the contents of the stored cache data to the store
	*/
	public static function write ()
	{

		/**
		* Set up response object
		*/

		self::_setResponse(new FlexiCache_Response);

		self::getResponse()->setBody(self::$_sResponseBody);

		self::getResponse()->addComment(sprintf("This is a cached file created %s (took %0.3f seconds to create)",
			self::getDateTimeString(),
			round(microtime(true)-self::getTimer(),3)
		));

		/**
		* If the response body is empty and there is no Location header set,
		* as a precautionary measure, treat this as an error in the
		* application and don't store it.
		*/
		if (0 == self::getResponse()->getBodyLength()) {

			if (null == self::getResponse()->getHeaders()->get('Location')) {
				return false;
			}

		}

		/**
		* Set the expiry timestamp on the response
		*/
		self::getResponse()->setExpiresTimestamp(self::getExpiresTimestamp());

		/**
		* Process the custom control header if set
		*/
		self::_processCustomControlHeader();

		/**
		* Set an 'Expires' header which matches the response's expiry time
		*/
		self::getResponse()->getHeaders()->set('Expires', self::getDateTimeString(self::getResponse()->getExpiresTimestamp()));

		try {

			self::_getStore()->write(self::getRequest()->getKey(),self::getResponse());

		} catch (FlexiCache_Store_Exception $oE) {

			return false;

		}

		return true;

	}

	/**
	* Return the path to the directory where the WordPress plugin lives
	*/
	public static function getPluginDir ()
	{
		return join (DIRECTORY_SEPARATOR, array(WP_PLUGIN_DIR,FlexiCache_Wp::FLEXICACHE_PLUGIN_DIR));
	}

	public static function getNumCachedResponses ($sUri)
	{
		try {

			return self::_getStore()->getNumCachedResponses($sUri);

		} catch (FlexiCache_Exception $oE) {

			return 'An error occurred fetching cache info';

		}

	}

	private static function _setServeMode ($iServeMode)
	{
		self::$_iServeMode = $iServeMode;
	}

	public static function getServeMode ()
	{
		return self::$_iServeMode;
	}

	private static function _setRequest ($oRequest)
	{
		self::$_oRequest = $oRequest;
	}

	public static function getRequest ()
	{
		return self::$_oRequest;
	}

	private static function _setResponse ($oResponse)
	{
		self::$_oResponse = $oResponse;
	}

	public static function getResponse ()
	{
		return self::$_oResponse;
	}

	private static function _getStore ()
	{

		if (null == self::$_oStore) {
			self::$_oStore = FlexiCache_Store::factory(FlexiCache_Config::get('Main', 'DefaultStore'));
		}

		return self::$_oStore;

	}

	private static function _setIsStorable ($bState)
	{
		self::$_bStorable = (bool) $bState;
	}

	public static function getIsStorable ()
	{
		return self::$_bStorable;
	}

	public static function setIsServable ($bState)
	{
		self::$_bServable = (bool) $bState;
	}

	private static function _getIsServable ()
	{
		return self::$_bServable;
	}

	public static function getServeModeString ()
	{
		switch (self::getServeMode()) {

			case self::SERVE_MODE_WP_PLUGIN:
				$sServeModeString = 'plugin';
				break;

			case self::SERVE_MODE_STANDALONE:
				$sServeModeString = 'standalone';
				break;

			default:
				$sServeModeString = 'unknown';

		}

		return $sServeModeString;

	}

	/**
	* Get the current hostname
	*/
	public static function getSiteHost ()
	{
		return $_SERVER['SERVER_NAME'];
	}

	/**
	* Set the cache enabled status to $bState and save the
	* config file
	*/
	private static function _setSaveCacheEnabled ($bState)
	{
		$oConfig = FlexiCache_Config::get('Main');
		$oConfig->Enabled = $bState;
		FlexiCache_Config::save();
	}

	/**
	* Purge the current store
	*/
	public static function purge ()
	{

		/**
		* If caching is currently enabled, temporarily disable it whilst
		* purging, noting the original state first.
		*/

		$bCacheEnabled = FlexiCache_Config::get('Main','Enabled');

		if (true == $bCacheEnabled) {
			self::_setSaveCacheEnabled(false);
		}

		$bResponse = self::_getStore()->purge();

		/**
		* Reset config state if requried
		*/
		if (true == $bCacheEnabled) {
			self::_setSaveCacheEnabled(true);
		}

		return $bResponse;

	}

	/**
	* Purge all files relating to the provided $sUri from the store
	*/
	public static function purgeUri ($sUri)
	{
		return self::_getStore()->purgeUri($sUri);
	}

	/**
	* The time (in seconds) by which to extend an expiry time on a stale response
	* when a new one is being built
	*/
	private static function _getExpiresExtensionSeconds ()
	{
		return self::MAX_EXPECTED_BUILD_TIME + FlexiCache_Config::get('Main', 'PreCacheTime');
	}

	public static function getExpiresSeconds ()
	{

		/**
		* If any expiry conditions match, return the result
		*/
		if (false !== ($iExpiresSeconds = FlexiCache_Config::get('Main', 'ConditionSet_Expire')->validateAnyExpires())) {
			return $iExpiresSeconds;
		}

		/**
		* Return the default expiry time
		*/
		return FlexiCache_Config::get('Main','DefaultExpiryTime');

	}

	public static function getExpiresTimestamp ()
	{
		return time() + self::getExpiresSeconds();
	}

	/**
	* Return a datetime string suitable for use in an "Expires" HTTP header
	*/
	public static function getDateTimeString ($iTimestamp=null)
	{

		if (null == $iTimestamp) {
			$iTimestamp = time();
		}

		return date('D, d M Y H:i:s T', $iTimestamp);

	}

	/**
	* Set the timer to the current time
	*/
	private static function _startTimer ()
	{
		self::$_iTimer = microtime(true);
	}

	/**
	* Get the value of the timer
	*/
	public static function getTimer ()
	{
		return self::$_iTimer;
	}

	public static function getDataDir ()
	{
		return join(DIRECTORY_SEPARATOR, array(FlexiCache::getPluginDir(),self::DEFAULT_DATA_DIR));
	}

	/**
	* Send a "503 Service Unavailable" response with a static file if specified
	*/
	private static function _sendServiceUnavailableResponse ()
	{

		if (null == ($iRetrySeconds = (float) FlexiCache_Config::get('Main','ServiceUnavailableResponseRetrySeconds'))) {

			/**
			* If for any reason this isn't set in the config, default to 5 seconds
			*/
			$iRetrySeconds = 5;

		}

		header('HTTP/1.1 503 Service Temporarily Unavailable');
		header('Status: 503 Service Temporarily Unavailable');
		header('Retry-After: ' . $iRetrySeconds);

		$sResponseFilePath = FlexiCache_Config::get('Main','ServiceUnavailableResponseFilePath');

		/**
		* If a response file path is specified and exists and is readable, send it out
		*/
		if (null != $sResponseFilePath && file_exists($sResponseFilePath) && (true == is_readable($sResponseFilePath))) {

			/**
			* If we were able to send the file, return here
			*/
			if (false != @readfile($sResponseFilePath)) {
				return;
			}

		}

		/**
		* Send the hard-coded default response
		*/
		echo '<!DOCTYPE html><html><head><title>Service Temporarily Unavailable</title></head><body><h1>Service Temporarily Unavailable</h1><p>Please try again in a little while.</p></body></html>';

		return;

	}

}
