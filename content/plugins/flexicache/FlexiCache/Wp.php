<?php

class FlexiCache_Wp {

	/**
	* The directory in which this plugin exists, within WordPress's WP_PLUGIN_DIR
	*/
	const FLEXICACHE_PLUGIN_DIR = 'flexicache';

	/**
	* Add WordPress actions
	*/
	public static function init ()
	{

		add_action('init', array('FlexiCache','initWpPlugin'));

		/**
		* Register actions to perform on post transitions
		*/

		add_action('transition_post_status', array(__CLASS__, 'purgePostOnTransition'), null, 3);

		if (true == FlexiCache_Config::get('Main','PurgeOnPostPublish')) {

			/**
			* Purge all items on post publish
			*/
			add_action('publish_post', array('FlexiCache','purge'));

		}

		if (true == FlexiCache_Config::get('Main','PurgeOnPagePublish')) {

			/**
			* Purge all items on page publish
			*/
			add_action('publish_page', array('FlexiCache','purge'));

		}

		/**
		* The rest of this method is only applicable when being called from
		* a WordPress admin page.
		*/
		if (false == self::isWpAdminPage()) {
			return;
		}

		require_once 'Wp/Admin.php';

		/**
		* Dashboard
		*/
		add_action('right_now_content_table_end', array('FlexiCache_Wp_Admin','renderDashboardRightNowInfo'));

		/**
		* Options page
		*/
		add_action('admin_menu', array('FlexiCache_Wp_Admin','addOptionsPage'));

		/**
		* Hook to display edit box
		*/
		add_action('submitpost_box', array('FlexiCache_Wp_Admin','addPostEditBox'));
		add_action('submitpage_box', array('FlexiCache_Wp_Admin','addPageEditBox'));

	}

	/**
	* Return boolean to indicate whether the current install is WordPress MU
	* Technique based on http://development.pressible.org/eric/detecting-wpmu
	*/
	public static function isMu ()
	{

		$sMuPluginDir = WP_CONTENT_DIR . DIRECTORY_SEPARATOR . 'mu-plugins';

		if (false == file_exists($sMuPluginDir)) {
			return false;
		}

		if (false == is_dir($sMuPluginDir)) {
			return false;
		}

		return true;

	}

	/**
	* Return boolean to indicate whether the current install is running Multisite
	*/
	public static function isMultisite ()
	{

		if (false == function_exists('is_multisite')) {
			return false;
		}

		if (false == is_multisite()) {
			return false;
		}

		return true;

	}

	/**
	* Read the plugin version from the plugin file and return a string
	*/
	public static function getPluginVersion ()
	{

		$sPluginFilePath = sprintf("%s/wp-plugin.php", FlexiCache::getPluginDir());

		if (false == file_exists($sPluginFilePath)) {
			return "Can't find plugin file";
		}

		if (false == ($sFile = file_get_contents($sPluginFilePath))) {
			return "Can't read plugin file";
		}

		if (false == preg_match('#Version:\s*([^\r\n]+)[\r\n]#', $sFile, $aCapture)) {
			return "Can't find version information in plugin file";
		}

		return trim($aCapture[1]);

	}

	/**
	* Return boolean to indicate whether caching should be disabled
	* for the current request
	*/
	public static function getDisableCaching ()
	{

		/**
		* Security restriction: If a user is logged in to WordPress, don't use caching
		*/
		if (true == self::_getIsLoggedIn()) {
			return true;
		}

		/**
		* Security restriction: If the current page is an admin page, return
		* This should be covered by the above rule, but sometimes that one is
		* disabled while testing.
		*/
		if (true == self::isWpAdminPage()) {
			return true;
		}

		/**
		* Don't cache the login page or /wp-admin/ which will redirect to the
		* login page.
		*/
		if (true == preg_match('#^/wp-(admin|login)#', FlexiCache::getRequest()->getUri())) {
			return true;
		}

		foreach ($_COOKIE as $sKey=>$sVal) {

			/**
			* If a comment_author cookie set, don't store the page as it may contain pre-filled
			* comment forms with their details in.
			*/
			if (0 === strpos($sKey, 'comment_author')) {
				return true;
			}

			/**
			* If a passworded page access cookie is set, don't store the page
			*/
			if (0 === strpos($sKey, 'wp-postpass')) {
				return true;
			}

		}

		return false;

	}

	/**
	* Return boolean to indicate whether a user is logged in to WordPress
	*/
	private static function _getIsLoggedIn ()
	{

		if ((true == function_exists('is_user_logged_in')) && (true == is_user_logged_in())) {
			return true;
		}

		return false;

	}

	/**
	* Return boolean to indicate whether the current page request is
	* for a WordPress admin page
	*/
	public static function isWpAdminPage ()
	{

		if ((true == function_exists('is_admin')) && (true == is_admin())) {
			return true;
		}

		return false;

	}

	/**
	* Return a post/page path given its id or false if not found
	*/
	public static function getPathFromPostId ($iPostId)
	{

		if (false == $sUrl = get_permalink($iPostId)) {
			return false;
		}

		if (false == ($aUrl = parse_url($sUrl))) {
			return false;
		}

		if (false == (is_array($aUrl))) {
			return false;
		}

		/**
		* parse_url() returns an empty path for "/" so we need to handle
		* this one differently.  First check the response doesn't look
		* 'broken', and if not, assume that empty means "/"
		*/

		if (false == (isset($aUrl['scheme']))) {
			return false;
		}

		if (true == empty($aUrl['path'])) {
			return '/';
		}

		return $aUrl['path'];

	}

	/**
	* Wrapper for purgePost() registered as a callback for the "transition_post_status" action
	*/
	public static function purgePostOnTransition ($sNewStatus=null, $sOldStatus=null, $oPost=null)
	{
		return self::purgePost($oPost);
	}

	/**
	* Purge the given post from the cache
	*/
	public static function purgePost ($oPost=null)
	{


		if (false == is_object($oPost)) {
			return;
		}

		if (false == isset($oPost->ID)) {
			return;
		}

		if (false == ($sPath = self::getPathFromPostId($oPost->ID))) {
			return false;
		}

		FlexiCache::purgeUri($sPath);

	}

	/**
	* Things to do when the plugin is deactivated
	*/
	public static function deactivatePlugin ()
	{

		/**
		* Remove the standalone .htaccess modification if there is one.
		*/

		/**
		* This is a bad idea for WordPress MU
		*/
//		require_once 'Wp/Admin/Htaccess.php';
//		FlexiCache_Wp_Admin_Htaccess::remove();

		/**
		* Disable caching in the config
		*/
		$oConfig = FlexiCache_Config::get('Main');
		$oConfig->Enabled = false;

		FlexiCache_Config::save();

	}

}
