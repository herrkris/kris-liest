<?php

class FlexiCache_Wp_Admin_Htaccess {

	const WORDPRESS_COMMENT_BEGIN = '# BEGIN WordPress';

	const FLEXICACHE_COMMENT_BEGIN = '# BEGIN FlexiCache Standalone';
	const FLEXICACHE_COMMENT_END = '# END FlexiCache Standalone';

	public static function isIncluded ()
	{

		if (false == file_exists(self::_getFilePath())) {
			return false;
		}

		$sText = file_get_contents(self::_getFilePath());

		if (true == empty($sText)) {
			return false;
		}

		if (false === strstr($sText, self::FLEXICACHE_COMMENT_BEGIN)) {
			return false;
		}

		return true;

	}

	/**
	* Return boolean to indicate whether the .htaccess file exists
	*/
	public static function exists ()
	{

		if (true == file_exists(self::_getFilePath()) && true == is_file(self::_getFilePath())) {
			return true;
		}

		return false;

	}

	/**
	* Return boolean to indicate whether the .htaccess file is updatable
	*/
	public static function canUpdate ()
	{

		if (false == self::exists()) {
			return false;
		}

		if (false == preg_match('#apache#i', $_SERVER['SERVER_SOFTWARE'])) {
			return false;
		}

		if (false == is_writable(self::_getFilePath())) {
			return false;
		}

		return true;

	}

	/**
	* Return a string containing the .htaccess file modification, including comments
	*/
	public static function getIncludeTextWithComments ()
	{

		/**
		* Add comments
		*/
		return join("\n",array(self::FLEXICACHE_COMMENT_BEGIN,self::getIncludeText(),self::FLEXICACHE_COMMENT_END));

	}

	/**
	* Return a string containing the current .htaccess file contents
	*/
	public static function getExistingText ()
	{
		return trim(file_get_contents(self::_getFilePath()));
	}

	/**
	* Return a string containing the existing .htaccess file with standalone modification added
	* Does not actually write the file to disk
	*/
	public static function getAddStandaloneText ()
	{

		$sOldText = self::getExistingText();

		if (true == empty($sOldText)) {
			return false;
		}

		/**
		* Try to insert the new rules immediately before WordPress rules
		*/
		$sNewText = mb_ereg_replace(self::WORDPRESS_COMMENT_BEGIN, self::getIncludeTextWithComments() . "\n\n" . self::WORDPRESS_COMMENT_BEGIN, $sOldText);

		/**
		* If this didn't work (for example if the default WordPress comment
		* was not present), add the new rules at the beginning of the file.
		*/
		if (0 == strcmp($sNewText,$sOldText)) {
			$sNewText = join("\n\n", array(self::getIncludeTextWithComments(),$sOldText));
		}

		if (true == empty($sNewText)) {
			return false;
		}

		if ($sNewText == $sOldText) {
			return false;
		}

		return $sNewText;

	}

	/**
	* Return a string containing the existing .htaccess file with standalone modification removed
	* Does not actually write the file to disk
	*/
	public static function getRemoveStandaloneText ()
	{

		$sOldText = self::getExistingText();
		$sNewText = mb_ereg_replace(self::FLEXICACHE_COMMENT_BEGIN . '.*' . self::FLEXICACHE_COMMENT_END . '\s*', '', $sOldText);

		if (true == empty($sOldText) || true == empty($sNewText)) {
			return false;
		}

		if ($sNewText == $sOldText) {
			return false;
		}

		return $sNewText;

	}

	/**
	* Add the .htaccess file modification
	*/
	public static function add ()
	{

		if (false == self::canUpdate()) {
			return false;
		}

		$sNewText = self::getAddStandaloneText();

		if (false == $sNewText) {
			return false;
		}

		return self::_updateFile($sNewText);

	}

	/**
	* Remove the .htaccess file modification
	*/
	public static function remove ()
	{

		if (false == self::canUpdate()) {
			return false;
		}

		$sNewText = self::getRemoveStandaloneText();

		if (false == $sNewText) {
			return false;
		}

		return self::_updateFile(trim($sNewText)."\n");

	}

	private static function _getFilePath ()
	{
		return join(DIRECTORY_SEPARATOR,array($_SERVER['DOCUMENT_ROOT'],'.htaccess'));
	}

	private static function _updateFile ($sNewText)
	{

		$sFilePath = self::_getFilePath();

		try {

			if (false == ($rFp = @fopen($sFilePath, 'a'))) {
				throw new FlexiCache_Exception('Can\'t open file in append mode: ' . $sFilePath);
			}

			if (false == flock($rFp,LOCK_EX)) {
				throw new FlexiCache_Store_FlexiCache_Exception('Can\'t get exclusive lock on file: ' . $sFilePath);
			}

			if (false == ftruncate($rFp, 0)) {
				throw new FlexiCache_FlexiCache_Exception('Can\'t truncate file: ' . $sFilePath);
			}

			if (false == fwrite($rFp, $sNewText, strlen($sNewText))) {
				throw new FlexiCache_Store_FlexiCache_Exception('Can\'t write to file: ' . $sFilePath);
			}

			if (false == fclose($rFp)) {
				throw new FlexiCache_Store_FlexiCache_Exception('Can\'t close file: ' . $sFilePath);
			}

		} catch (FlexiCache_Exception $oE) {

			return false;

		}

		return true;

	}

	public static function getIncludeText ()
	{

return sprintf(
'<IfModule mod_rewrite.c>
	RewriteEngine On
	RewriteBase /
	RewriteCond %%{HTTP_HOST}        %s$
	RewriteCond %%{REQUEST_METHOD}   GET
	RewriteCond %%{REQUEST_URI}      !^/wp-
	RewriteCond %%{HTTP_COOKIE}      !(wordpress_logged_in|comment_author|wp-postpass)
	RewriteCond %%{REQUEST_FILENAME} !-f
	RewriteCond %%{REQUEST_FILENAME} !-d [OR]
	RewriteCond %%{REQUEST_URI}      ^/$
	RewriteRule ^(.*)               wp-content/plugins/%s/standalone.php [L]
</IfModule>',
	str_replace('.','\.',FlexiCache::getSiteHost()),
	FlexiCache_Wp::FLEXICACHE_PLUGIN_DIR
);

	}

}
