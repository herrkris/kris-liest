<?php

require_once 'Headers.php';

class FlexiCache_Response {

	/**
	* If the object definition changes such that a serialized response
	* should be considered invalid, update this number to trigger a fail
	* in the getIsValid() method.  Now stored as a string because storing
	* as a float added lots of random additional decimal places when
	* serialized.
	*/
	const OBJECT_VERSION			= '1.0.7.5';

	/**
	* Prefix for HTML comments
	*/
	const COMMENT_PREFIX			= 'FlexiCache';

	/**
	* Types of body encoding which are implemented
	*/
	const BODY_ENCODING_PLAINTEXT	= 1;
	const BODY_ENCODING_GZIP		= 2;
	const BODY_ENCODING_DEFLATE		= 3;

	/**
	* This is compared to self::OBJECT_VERSION on wakeup() and the object
	* considered invalid if they don't match
	*/
	private $_fObjectVersion = self::OBJECT_VERSION;

	/**
	* The response's validity, which may be set to false on wakeup() or
	* if the object has expired.
	*/
	private $_bIsValid = true;

	/**
	* Request URI
	*/
	private $_sRequestUri;

	/**
	* Headers
	*/
	private $_oHeaders;

	/**
	* Created time as a unix timestamp
	*/
	private $_iCreatedTimestamp;

	/**
	* Expiry time as a unix timestamp
	*/
	private $_iExpiresTimestamp;

	/**
	* Mime-type, derived from the Content-Type header
	*/
	private $_sMimeType;

	/**
	* Response body array for different encodings
	*/
	private $_aBody = array();

	/**
	* Length of uncompressed response body
	*/
	private $_iBodyLength = array();

	/**
	* ETag
	*/
	private $_sEtag;

	/**
	* An array of comments to be appended when the response is sent
	*/
	private $_aComment = array();

	public function __construct ()
	{

		$this->_setCreatedTimestamp(time());

		$this->_setRequestUri(FlexiCache::getRequest()->getUri());

		$this->_setHeaders(new FlexiCache_Headers);
		$this->_importHeaders();

	}

	public function __wakeup ()
	{

		if ($this->_fObjectVersion != self::OBJECT_VERSION) {
			$this->_setIsValid(false);
		}

	}

	private function _setRequestUri ($sUri)
	{
		$this->_sRequestUri = $sUri;
	}

	public function getRequestUri ()
	{
		return $this->_sRequestUri;
	}

	protected function _setHeaders ($oHeaders)
	{
		$this->_oHeaders = $oHeaders;
	}

	public function getHeaders ()
	{
		return $this->_oHeaders;
	}

	private function _importHeaders ()
	{

		$aHeader = headers_list();

		foreach ($aHeader as $sHeader) {

			list($sKey,$sVal) = preg_split('#\s*:\s*#', $sHeader, 2);
			$this->getHeaders()->set($sKey,$sVal);

		}

		/**
		* Set mime-type which will be used later to check whether we should
		* add comments or not
		*/
		$this->_setMimeType($this->getHeaders()->getComponent('Content-Type', 0));

		/**
		* This is not required
		*/
		$this->getHeaders()->remove('Pragma');

	}

	/**
	* If neither gzdecode() nor gzuncompress() functions are available, we
	* must keep a plain-text copy
	*/
	public static function getMustStorePlainText ()
	{

		if (function_exists('gzuncompress')) {
			return false;
		}

		if (function_exists('gzdecode')) {
			return false;
		}

		return true;

	}

	private function _getCanSendGzipEncodedBody ()
	{

		if (false == FlexiCache_Config::get('Main', 'EnableServeGzip')) {
			return false;
		}

		if (false == FlexiCache::getRequest()->getHeaders()->hasComponent('accept-encoding','gzip')) {
			return false;
		}

		if (false === $this->getBody(self::BODY_ENCODING_GZIP)) {
			return false;
		}

		return true;

	}

	private function _getCanSendDeflateEncodedBody ()
	{

		if (false == FlexiCache_Config::get('Main', 'EnableServeDeflate')) {
			return false;
		}

		if (false == FlexiCache::getRequest()->getHeaders()->hasComponent('accept-encoding','deflate')) {
			return false;
		}

		if (false === $this->getBody(self::BODY_ENCODING_DEFLATE)) {
			return false;
		}

		return true;

	}

	/**
	* Output the headers and body of the current object
	*/
	public function send ()
	{

		/**
		* Remove custom control header before sending
		*/
		$this->getHeaders()->remove(FlexiCache_Headers::CUSTOM_CONTROL_KEY);

        if (true == $this->_getCanSendGzipEncodedBody()) {

			/**
			* Send gzip-encoded body
			*/

			$sBody = $this->getBody(self::BODY_ENCODING_GZIP);

			$this->getHeaders()->set('Content-Encoding', 'gzip');
			$this->getHeaders()->set('Content-Length', strlen($sBody));

			$this->_sendHeaders();

			echo $sBody;

        } else if (true == $this->_getCanSendDeflateEncodedBody()) {

			/**
			* Send deflate-encoded body
			*/

			$sBody = $this->getBody(self::BODY_ENCODING_DEFLATE);

			$this->getHeaders()->set('Content-Encoding', 'deflate');
			$this->getHeaders()->set('Content-Length', strlen($sBody));

			$this->_sendHeaders();

			echo $sBody;

		} else {

			/**
			* Send plain-text response
			*/

			$sBody = $this->getBody(self::BODY_ENCODING_PLAINTEXT);

			if (false === $sBody) {

				/**
				* Either gzdecode() or gzuncompress() must be present on the
				* hosting installation as checked by getMustStorePlainText() above
				*/
				if (function_exists('gzdecode')) {
					$sBody = gzdecode($this->getBody(self::BODY_ENCODING_GZIP));
				} else {
					$sBody = gzuncompress($this->getBody(self::BODY_ENCODING_DEFLATE));
				}

			}

			/**
			* Add comments if they're enabled in the config
			*/
			if (true == FlexiCache_Config::get('Main', 'Comments')) {
				$sBody .= $this->_getCommentsString();
			}

			/**
			* Set content-length including comments
			*/
			$this->getHeaders()->set('Content-Length', strlen($sBody));

			/**
			* ETag
			*/
			$this->getHeaders()->set('ETag', $this->getEtag());

			$this->_sendHeaders();

			echo $sBody;

		}

	}

	private function _sendHeaders ()
	{

		/**
		* Add an additional custom header before sending
		*/
		$aFlexiCacheHeaderComponent = array (
			'cached',
			'serve-mode=' . FlexiCache::getServeModeString(),
			'created=' . FlexiCache::getDateTimeString($this->getCreatedTimestamp()),
			'expires=' . FlexiCache::getDateTimeString($this->getExpiresTimestamp())
		);
		$this->getHeaders()->set(FlexiCache_Headers::CUSTOM_OUTPUT_KEY, join('; ', $aFlexiCacheHeaderComponent));

		/**
		* Determine random additional for client expiry headers depending on config
		*/
		if (0 != ($iExpiresHeaderRandMax = FlexiCache_Config::get('Main', 'ExpiresHeaderRandMax'))) {
			$iExpiresHeaderRandValue = mt_rand(0, $iExpiresHeaderRandMax);
		} else {
			$iExpiresHeaderRandValue = 0;
		}

		/**
		* Set a dynamic Cache-Control header
		*/
		$aCacheControlHeaderComponent = array (
			'must-revalidate',
			'max-age=' . ($this->getExpiresTimestamp() + $iExpiresHeaderRandValue - time())
		);
		$this->getHeaders()->set('Cache-Control', join('; ', $aCacheControlHeaderComponent));

		/**
		* Get response headers
		*/
		$aHeader = $this->getHeaders()->get();

		/**
		* If a non-zero random expiry header amount is set, rewrite the response's
		* "Expires" header to include the additional random element.
		*/
		if (0 != $iExpiresHeaderRandValue) {
			$aHeader['Expires'] = FlexiCache::getDateTimeString($this->getExpiresTimestamp() + $iExpiresHeaderRandValue);
		}

		/**
		* Send response headers
		*/
		foreach ($aHeader as $sKey=>$sVal) {
			header(sprintf("%s: %s", $sKey, $sVal));
		}

	}

	/**
	* Return a string of comments to add to the response if
	* the response is of an appropriate mime-type
	*/
	private function _getCommentsString ()
	{

		$aComment = $this->_getComments();

		if (true == empty($aComment)) {
			return '';
		}

		/**
		* Don't add comments unless it's an HTML or XML file
		*/
		if (false == preg_match('#^text/(html|xhtml|xml)$#', $this->getMimeType())) {
			return '';
		}

		$sCommentsString = '';

		foreach ($aComment as $sComment) {

			$sCommentsString .= sprintf("\n<!-- %s: %s -->",
				self::COMMENT_PREFIX,
				htmlspecialchars($sComment)
			);

		}

		return $sCommentsString;

	}

	private function _setBodyLength ($iBodyLength)
	{
		$this->_iBodyLength = (int) $iBodyLength;
	}

	public  function getBodyLength ()
	{
		return $this->_iBodyLength;
	}

	private function _setEtag ($sEtag)
	{
		$this->_sEtag = $sEtag;
	}

	public  function getEtag ()
	{
		return $this->_sEtag;
	}

	public function setBody ($sBody)
	{

		/**
		* Note body length so we can check the decompressed length if
		* a plain-text version is not stored
		*/
		$this->_setBodyLength(strlen($sBody));

		/**
		* Set ETag
		*/
		$this->_setEtag(md5($sBody));

		/**
		* Always store gzip- and deflate- encoded files
		*/
		$this->_aBody[self::BODY_ENCODING_GZIP] = gzencode($sBody,FlexiCache_Config::get('Main','CompressionLevel'),FORCE_GZIP);
		$this->_aBody[self::BODY_ENCODING_DEFLATE] = gzcompress($sBody,FlexiCache_Config::get('Main','CompressionLevel'));

		/**
		* Store plain-text if enabled in config or if we *must* store it due to
		* lack of decompression functionality on the server.
		*/
		if ((true == FlexiCache_Config::get('Main', 'EnableStorePlainText')) || (true == self::getMustStorePlainText())) {
			$this->_aBody[self::BODY_ENCODING_PLAINTEXT] = $sBody;
		} else {
			$this->_aBody[self::BODY_ENCODING_PLAINTEXT] = false;
		}

	}

	public function getBody ($iEncoding=self::BODY_ENCODING_PLAINTEXT)
	{

		if (false == isset($this->_aBody[$iEncoding])) {
			return null;
		}

		return $this->_aBody[$iEncoding];

	}

	private function _setCreatedTimestamp ($iTimestamp)
	{
		$this->_iCreatedTimestamp = $iTimestamp;
	}

	public function getCreatedTimestamp ()
	{
		return $this->_iCreatedTimestamp;
	}

	public function setExpiresTimestamp ($iTimestamp)
	{
		$this->_iExpiresTimestamp = (float) $iTimestamp;
	}

	public function getExpiresTimestamp ()
	{
		return $this->_iExpiresTimestamp;
	}

	private function _setIsValid ($bState)
	{
		$this->_bIsValid = (bool) $bState;
	}

	public function getIsValid ()
	{
		return $this->_bIsValid;
	}

	public function hasExpired ()
	{
		return (time()>$this->getExpiresTimestamp())?true:false;
	}

	private function _setMimeType ($sMimeType)
	{
		$this->_sMimeType = $sMimeType;
	}

	public function getMimeType ()
	{
		return $this->_sMimeType;
	}

	public function addComment ($sComment)
	{
		array_push($this->_aComment, $sComment);
	}

	private function _getComments ()
	{
		return $this->_aComment;
	}

}
